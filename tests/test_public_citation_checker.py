"""
Test Suite:
1. Perbaikan AdSense di beranda (tidak ada placeholder kosong & tidak menyebarkan Auto Ads acak).
2. Fitur Alat Gratis /cek-sitasi & POST /api/v1/citation-check (parser, kejujuran status,
   cache hash bebas teks mentah, NDJSON streaming, batas 10 sitasi/cek, batas 5 cek/hari per IP, UTM tracking).
"""

import json
import os
import socket
import subprocess
import sys
import time
import urllib.error
import urllib.parse
import urllib.request
from pathlib import Path

import pytest

PROJECT_ROOT = Path(__file__).resolve().parents[1]
if str(PROJECT_ROOT) not in sys.path:
    sys.path.insert(0, str(PROJECT_ROOT))

from worker.database import get_connection, load_env
from worker.external_citation_verifier import (
    PUBLIC_UNAVAILABLE_REASON,
    PUBLIC_UNVERIFIED_REASON,
    parse_public_bibliography_input,
    verify_public_citation_list,
)

load_env()


def _find_free_port() -> int:
    with socket.socket(socket.AF_INET, socket.SOCK_STREAM) as s:
        s.bind(("127.0.0.1", 0))
        return int(s.getsockname()[1])


def _wait_http_ready(url: str, timeout: float = 8.0) -> None:
    deadline = time.time() + timeout
    while time.time() < deadline:
        try:
            with urllib.request.urlopen(url, timeout=2.0) as resp:
                if resp.status < 500:
                    return
        except Exception:
            time.sleep(0.15)
    raise RuntimeError(f"Server tidak siap di {url}")


def test_public_parser_handles_headings_newlines_wrapped_lines_and_dois():
    raw = """Daftar Pustaka
1. Vaswani, A., Shazeer, N. (2017). Attention is all you need. Advances in Neural Information Processing Systems, 30.
2. LeCun, Y., Bengio, Y., & Hinton, G. (2015). Deep learning. Nature, 521(7553), 436-444. https://doi.org/10.1038/nature14539
Mikolov, T., Sutskever, I., Chen, K., Corrado, G., & Dean, J. (2013).
Distributed representations of words and phrases and their compositionality. NeurIPS.
10.1145/3442188.3445922
"""
    entries = parse_public_bibliography_input(raw)
    assert len(entries) == 4
    assert entries[0]["year"] == "2017"
    assert "vaswani" in entries[0]["author_keys"]
    assert entries[1]["doi"] == "10.1038/nature14539"
    assert "Distributed representations" in entries[2]["raw_text"]
    assert entries[2]["year"] == "2013"
    assert entries[3]["doi"] == "10.1145/3442188.3445922"


def test_public_verifier_classification_honesty_cache_and_unavailable(monkeypatch):
    import worker.external_citation_verifier as ecv

    # Pastikan test terisolasi dari cache sebelumnya
    conn = get_connection()
    cur = conn.cursor()
    cur.execute("DELETE FROM public_citation_cache")
    conn.commit()

    def fake_cr_doi(doi, timeout=ecv.PUBLIC_API_TIMEOUT):
        if doi == "10.1038/nature14539":
            return ({
                "source": "crossref",
                "openalex_id": None,
                "doi": "https://doi.org/10.1038/nature14539",
                "source_url": "https://doi.org/10.1038/nature14539",
                "matched_title": "Deep learning",
                "matched_authors": "Yann LeCun, Yoshua Bengio, Geoffrey Hinton",
                "matched_authors_list": ["Yann LeCun", "Yoshua Bengio", "Geoffrey Hinton"],
                "matched_year": 2015,
            }, False)
        return None, False

    def fake_cr_search(clean_title, raw_entry, timeout=ecv.PUBLIC_API_TIMEOUT):
        low = clean_title.lower()
        if "bert" in low:
            return ([{
                "source": "crossref",
                "openalex_id": None,
                "doi": "https://doi.org/10.18653/v1/n19-1423",
                "source_url": "https://doi.org/10.18653/v1/n19-1423",
                "matched_title": "BERT: Pre-training of Deep Bidirectional Transformers for Language Understanding",
                "matched_authors": "Jacob Devlin, Ming-Wei Chang",
                "matched_authors_list": ["Jacob Devlin", "Ming-Wei Chang"],
                "matched_year": 2019,
            }], False)
        if "pembelajaran berbasis digital" in low:
            # Simulasi false-positive frasa boilerplate sekolah Indonesia (penulis & topik berbeda)
            return ([{
                "source": "crossref",
                "openalex_id": None,
                "doi": "https://doi.org/10.9999/fake-prefix-noise",
                "source_url": "https://doi.org/10.9999/fake-prefix-noise",
                "matched_title": "Sosialisasi Pembelajaran Matematika Berbasis Etnomatematika di Sekolah Menengah Pertama",
                "matched_authors": "Ani Wijaya",
                "matched_authors_list": ["Ani Wijaya"],
                "matched_year": 2023,
            }], False)
        if "timeout test" in low:
            return [], True
        return [], False

    def fake_oa_search(clean_title, timeout=ecv.PUBLIC_API_TIMEOUT):
        low = clean_title.lower()
        if "digital literacy" in low:
            return ([{
                "source": "openalex",
                "openalex_id": "https://openalex.org/W2610368133",
                "doi": None,
                "source_url": "https://openalex.org/W2610368133",
                "matched_title": "Digital Literacy",
                "matched_authors": "Paul Gilster",
                "matched_authors_list": ["Paul Gilster"],
                "matched_year": 1997,
            }], False)
        if "timeout test" in low:
            return [], True
        return [], False

    monkeypatch.setattr(ecv, "query_crossref_by_doi", fake_cr_doi)
    monkeypatch.setattr(ecv, "query_openalex_by_doi", lambda doi, timeout=5.0: (None, False))
    monkeypatch.setattr(ecv, "query_public_crossref_candidates", fake_cr_search)
    monkeypatch.setattr(ecv, "query_public_openalex_candidates", fake_oa_search)

    sample = "\n".join([
        "LeCun, Y., Bengio, Y., & Hinton, G. (2015). Deep learning. https://doi.org/10.1038/nature14539",
        "Gilster, P. (1997). Digital literacy. New York: Wiley.",
        "Devlin, J. (2023). BERT: Pre-training of deep bidirectional transformers for language understanding. NAACL.",
        "Pratama, D., & Kusuma, A. (2022). Efektivitas pembelajaran berbasis digital di sekolah menengah pertama. Jurnal Fiktif, 8(3), 201-215.",
        "Doe, J. (2024). Timeout test study on crossref latency. Journal of Network Issues.",
    ])

    try:
        res1 = verify_public_citation_list(sample, max_items=10)
        assert res1["total_entries"] == 5
        assert res1["verified_count"] == 2
        assert res1["doubtful_count"] == 1
        assert res1["unverified_count"] == 1
        assert res1["unavailable_count"] == 1
        assert res1["summary_text"] == "2 dari 5 sitasi terverifikasi"
        # Pastikan tidak ada persentase atau skor kepalsuan
        serialized = json.dumps(res1, ensure_ascii=False)
        assert "%" not in serialized
        assert "veracity_rate" not in res1
        assert "Palsu" not in serialized

        items = res1["items"]
        assert items[0]["status"] == "verified"
        assert items[0]["source_url"] == "https://doi.org/10.1038/nature14539"
        assert items[1]["status"] == "verified"
        assert items[1]["source"] == "openalex"
        assert items[2]["status"] == "doubtful"
        assert "tahun terbit berbeda" in (items[2]["reason"] or "").lower()
        assert items[3]["status"] == "unverified"
        assert items[3]["status_label"] == "Tidak terverifikasi"
        assert items[3]["reason"] == PUBLIC_UNVERIFIED_REASON
        assert items[4]["status"] == "unavailable"
        assert items[4]["status_label"] == "Tidak dapat diperiksa saat ini"
        assert items[4]["reason"] == PUBLIC_UNAVAILABLE_REASON

        # Privasi cache: entri tak terverifikasi tidak menyimpan teks pengguna di DB, dan 'unavailable' tidak di-cache
        cur.execute("SELECT status, matched_title FROM public_citation_cache WHERE status = 'unverified'")
        unv_rows = cur.fetchall()
        assert len(unv_rows) == 1
        assert unv_rows[0][1] is None

        # Run ke-2 harus hit cache untuk 4 entri non-unavailable
        monkeypatch.setattr(ecv, "query_crossref_by_doi", lambda *a, **k: pytest.fail("Should hit cache"))
        res2 = verify_public_citation_list(sample, max_items=10)
        assert res2["cache_hits"] == 4
        assert res2["items"][4]["status"] == "unavailable"
    finally:
        cur.execute("DELETE FROM public_citation_cache")
        conn.commit()
        cur.close()
        conn.close()


def test_landing_adsense_clean_and_cek_sitasi_http_e2e_rate_limit_and_utm():
    conn = get_connection()
    cur = conn.cursor(dictionary=True)

    # Bersihkan sisa kuota / log uji untuk IP 127.0.0.1 hari ini & catat state funnel
    cur.execute("DELETE FROM public_citation_daily_quota WHERE check_date = CURDATE()")
    cur.execute("DELETE FROM public_citation_check_logs WHERE check_date = CURDATE()")
    cur.execute("DELETE FROM public_citation_funnel_stats WHERE stat_date = CURDATE() AND utm_source = 'cek-sitasi'")
    conn.commit()

    # 1. Server bawaan: memakai 3 unit slot PARIWARA resmi (4774338502, 1341525808, 7905712963)
    #    tanpa memicu script ?client= Auto Ads acak, serta width:100% + auto-collapse unfilled
    port_default = _find_free_port()
    base_default = f"http://127.0.0.1:{port_default}"
    srv_default = subprocess.Popen(
        ["php", "-S", f"127.0.0.1:{port_default}", "-t", "public"],
        cwd=str(PROJECT_ROOT),
        stdout=subprocess.DEVNULL,
        stderr=subprocess.DEVNULL,
    )

    # 2. Server dengan slot dinonaktifkan via env ('off'): tidak boleh merender kotak kosong
    port_ad = _find_free_port()
    base_ad = f"http://127.0.0.1:{port_ad}"
    env_ad = os.environ.copy()
    env_ad["ADSENSE_SLOT_1"] = "off"
    env_ad["ADSENSE_SLOT_2"] = "off"
    env_ad["ADSENSE_SLOT_3"] = "off"
    env_ad["ADSENSE_AUTO_ADS"] = "false"
    srv_ad = subprocess.Popen(
        ["php", "-S", f"127.0.0.1:{port_ad}", "-t", "public"],
        cwd=str(PROJECT_ROOT),
        env=env_ad,
        stdout=subprocess.DEVNULL,
        stderr=subprocess.DEVNULL,
    )

    try:
        _wait_http_ready(f"{base_default}/")
        _wait_http_ready(f"{base_ad}/")

        # Cek beranda bawaan: 3 slot PARIWARA resmi terpasang & bebas Auto Ads acak + link /cek-sitasi ada
        landing_html = urllib.request.urlopen(f"{base_default}/", timeout=10).read().decode("utf-8")
        assert 'meta name="google-adsense-account" content="ca-pub-9248253613564140"' in landing_html
        assert 'data-ad-slot="4774338502"' in landing_html
        assert 'data-ad-slot="1341525808"' in landing_html
        assert 'data-ad-slot="7905712963"' in landing_html
        assert 'style="display:block;width:100%;"' in landing_html
        assert 'data-ad-slot-wrapper="1"' in landing_html
        assert 'data-ad-slot-wrapper="2"' in landing_html
        assert 'data-ad-slot-wrapper="3"' in landing_html
        assert "var autoAdsEnabled = false;" in landing_html
        assert f"{base_default}/cek-sitasi" in landing_html or "/cek-sitasi" in landing_html

        # Cek mode off: nol <ins class="adsbygoogle"> kosong
        landing_off_html = urllib.request.urlopen(f"{base_ad}/", timeout=10).read().decode("utf-8")
        assert '<ins class="adsbygoogle"' not in landing_off_html
        assert "data-ad-slot-wrapper" not in landing_off_html

        # Cek funnel UTM arrival tracking: kunjungi /?utm_source=cek-sitasi
        urllib.request.urlopen(f"{base_default}/?utm_source=cek-sitasi&utm_medium=cta", timeout=10).read()
        conn.commit()
        cur.execute("SELECT cta_arrivals FROM public_citation_funnel_stats WHERE stat_date = CURDATE() AND utm_source = 'cek-sitasi'")
        funnel_row = cur.fetchone()
        assert funnel_row is not None and int(funnel_row["cta_arrivals"]) >= 1

        # Cek halaman publik /cek-sitasi tanpa login
        cek_html = urllib.request.urlopen(f"{base_default}/cek-sitasi", timeout=10).read().decode("utf-8")
        assert "Daftar pustaka yang Anda tempel tidak disimpan" in cek_html
        assert "Pemeriksaan ini hanya mengecek keberadaan sitasi di basis data yang dirujuk, bukan menilai kebenaran isi naskah." in cek_html
        assert "Periksa naskah lengkap dengan AR Tilik" in cek_html
        assert "utm_source=cek-sitasi" in cek_html
        assert "tingkat kepalsuan" not in cek_html.lower()

        # Tolak >10 sitasi tanpa memotong kuota 5/hari
        eleven_lines = "\n".join([f"{i}. Penulis, A. (202{i%5}). Judul artikel uji ke {i}. Jurnal Uji." for i in range(1, 12)])
        req_11 = urllib.request.Request(
            f"{base_default}/api/v1/citation-check",
            data=json.dumps({"citations": eleven_lines}).encode("utf-8"),
            headers={"Content-Type": "application/json"},
            method="POST",
        )
        with pytest.raises(urllib.error.HTTPError) as exc11:
            urllib.request.urlopen(req_11, timeout=15)
        assert exc11.value.code == 422

        # Siapkan 1 entri cache deterministik untuk uji HTTP E2E streaming & rate-limit (bebas fluktuasi WiFi)
        import hashlib
        short_valid = "LeCun, Y., Bengio, Y., & Hinton, G. (2015). Deep learning. https://doi.org/10.1038/nature14539"
        norm_short_hash = hashlib.sha256(("v1:" + short_valid.lower()).encode("utf-8")).hexdigest()
        cur.execute(
            """
            INSERT INTO public_citation_cache (query_hash, status, source, matched_title, matched_authors, matched_year, doi, source_url, expires_at, created_at, updated_at)
            VALUES (%s, 'verified', 'crossref', 'Deep learning', 'Yann LeCun, Yoshua Bengio, Geoffrey Hinton', 2015, 'https://doi.org/10.1038/nature14539', 'https://doi.org/10.1038/nature14539', DATE_ADD(NOW(), INTERVAL 1 DAY), NOW(), NOW())
            ON DUPLICATE KEY UPDATE status = 'verified', expires_at = DATE_ADD(NOW(), INTERVAL 1 DAY)
            """,
            (norm_short_hash,),
        )
        conn.commit()

        # Jalankan 5 cek valid berturut-turut via NDJSON stream (cek #1) dan JSON (cek #2..#5) -> ke-6 harus HTTP 429!
        req_stream = urllib.request.Request(
            f"{base_default}/api/v1/citation-check",
            data=json.dumps({"citations": short_valid, "stream": True}).encode("utf-8"),
            headers={"Content-Type": "application/json", "Accept": "application/x-ndjson"},
            method="POST",
        )
        with urllib.request.urlopen(req_stream, timeout=20) as sresp:
            assert sresp.status == 200
            lines = [json.loads(ln) for ln in sresp.read().decode("utf-8").splitlines() if ln.strip()]
            events = [m.get("event") for m in lines]
            assert "quota" in events and "start" in events and "progress" in events and "done" in events
            done_msg = [m for m in lines if m.get("event") == "done"][0]["data"]
            assert done_msg["total_entries"] == 1
            assert done_msg["items"][0]["status"] in ("verified", "unavailable")

        for step_num in range(2, 6):
            req_ok = urllib.request.Request(
                f"{base_default}/api/v1/citation-check",
                data=json.dumps({"citations": short_valid}).encode("utf-8"),
                headers={"Content-Type": "application/json"},
                method="POST",
            )
            with urllib.request.urlopen(req_ok, timeout=20) as okresp:
                assert okresp.status == 200
                body = json.loads(okresp.read().decode("utf-8"))
                assert body["status"] == "success"
                assert body["quota"]["used"] == step_num
                assert body["quota"]["remaining"] == 5 - step_num

        # Permintaan ke-6 pada IP yang sama harus dibalas 429 berbahasa Indonesia
        req_6 = urllib.request.Request(
            f"{base_default}/api/v1/citation-check",
            data=json.dumps({"citations": short_valid}).encode("utf-8"),
            headers={"Content-Type": "application/json"},
            method="POST",
        )
        with pytest.raises(urllib.error.HTTPError) as exc6:
            urllib.request.urlopen(req_6, timeout=10)
        assert exc6.value.code == 429
        err6 = json.loads(exc6.value.read().decode("utf-8"))
        assert "maksimal 5 pemeriksaan per hari" in err6["message"].lower()
        assert int(exc6.value.headers.get("Retry-After", "0")) >= 1

        # Pastikan log hitungan mencatat 5 baris tanpa menyimpan teks mentah apa pun
        conn.commit()
        cur.execute("SELECT COUNT(*) AS cnt FROM public_citation_check_logs WHERE check_date = CURDATE()")
        assert int(cur.fetchone()["cnt"]) == 5
        cur.execute("SHOW COLUMNS FROM public_citation_check_logs")
        log_cols = {r["Field"] for r in cur.fetchall()}
        assert "raw_text" not in log_cols and "citations" not in log_cols
    finally:
        for proc in (srv_default, srv_ad):
            proc.terminate()
            try:
                proc.wait(timeout=3)
            except Exception:
                proc.kill()
        cur.execute("DELETE FROM public_citation_daily_quota WHERE check_date = CURDATE()")
        cur.execute("DELETE FROM public_citation_check_logs WHERE check_date = CURDATE()")
        cur.execute("DELETE FROM public_citation_funnel_stats WHERE stat_date = CURDATE() AND utm_source = 'cek-sitasi'")
        cur.execute("DELETE FROM public_citation_cache")
        conn.commit()
        cur.close()
        conn.close()
