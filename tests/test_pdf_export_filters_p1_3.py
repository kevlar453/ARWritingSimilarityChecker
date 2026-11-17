"""
Uji Komprehensif P1-3: Deteksi Kutipan & Bibliografi Server-Side + Sinkronisasi Skor Layar & Ekspor PDF.
"""

from __future__ import annotations

import json
import os
import re
import socket
import subprocess
import sys
import time
from pathlib import Path
from typing import Any, Dict, List

import fitz
import pytest
import requests

PROJECT_ROOT = Path(__file__).resolve().parents[1]
if str(PROJECT_ROOT) not in sys.path:
    sys.path.insert(0, str(PROJECT_ROOT))

from worker.database import get_connection, load_env
from worker.exclusion_filters import (
    EXCLUSION_SCHEMA_VERSION,
    annotate_matches_with_exclusions,
    compute_filtered_score,
    count_union_words,
    extract_quote_char_spans,
    tokenize_with_spans,
)
from worker.exporter import export_report_pdf

load_env()


def _find_free_port() -> int:
    with socket.socket(socket.AF_INET, socket.SOCK_STREAM) as s:
        s.bind(("127.0.0.1", 0))
        return int(s.getsockname()[1])


def _wait_http_ready(url: str, timeout: float = 8.0) -> None:
    deadline = time.time() + timeout
    while time.time() < deadline:
        try:
            r = requests.get(url, timeout=1.0)
            if r.status_code < 500:
                return
        except Exception:
            time.sleep(0.15)
    raise RuntimeError(f"PHP server uji tidak siap pada {url}")


def _extract_pdf_text(pdf_bytes: bytes) -> str:
    doc = fitz.open(stream=pdf_bytes, filetype="pdf")
    try:
        return "\n".join(page.get_text("text") for page in doc)
    finally:
        doc.close()


def _build_realistic_test_doc() -> tuple[str, List[Dict[str, Any]], float]:
    doc_text = (
        "Uji Konsistensi Skor Ekspor PDF dan Filter Kutipan serta Bibliografi\n\n"
        "Abstrak\n\n"
        "Penelitian ini bertujuan untuk menganalisis efektivitas algoritma pembandingan dokumen berbasis n-gram "
        "dan kosinus vektor dalam membedakan antara kutipan langsung ilmiah dengan penyalinan teks biasa.\n\n"
        "1. Pendahuluan\n\n"
        "Perkembangan sistem deteksi kemiripan teks akademik menuntut transparansi metrik evaluasi. "
        "Penelaah harus mengetahui bagian mana yang berasal dari salinan tanpa kutipan, mana yang berupa kutipan langsung, "
        "dan mana yang merupakan entri daftar pustaka di akhir naskah sehingga penilaian akademik tetap objektif.\n\n"
        "2. Uraian Rujukan Tanpa Tanda Petik\n\n"
        "Indonesia merupakan salah satu negara agraris terbesar di dunia di mana sektor pertanian menjadi tulang punggung "
        "perekonomian nasional serta ketahanan pangan masyarakat pedesaan [1]. Pengendalian hama selama ini masih dilakukan "
        "secara konvensional dengan pestisida kimia yang dapat menurunkan kualitas tanah jika tanpa pengawasan tepat [2].\n\n"
        "3. Kutipan Langsung Dengan Tanda Petik dan Sitasi\n\n"
        'Mengenai solusi teknologi pertanian cerdas, artikel rujukan menyatakan: "Salah satu solusi yang menjanjikan '
        "adalah pemanfaatan teknologi Internet of Things dalam sistem pertanian cerdas untuk pemantauan kondisi lahan "
        'secara real-time serta deteksi dini keberadaan hama secara otomatis [3]." (Putra, Febriani & Pramana, 2025).\n\n'
        "Selain itu, laporan lapangan mencatat pula temuan berikut: “Implementasi perangkat sensor gerak dan pengusir hama "
        "berbasis mikrokontroler terbukti membantu petani menjaga produktivitas tanaman padi tanpa harus selalu berada "
        "di lahan sepanjang waktu [4].” (Putra, Febriani & Pramana, 2025).\n\n"
        "4. Pembahasan\n\n"
        "Dengan memisahkan kutipan bertanda petik dari uraian tanpa tanda petik, evaluator memperoleh gambaran yang jauh lebih jujur.\n\n"
        "Daftar Pustaka\n\n"
        "Putra, A. S., Febriani, O. M., & Pramana, A. A. (2025). Implementasi Kecerdasan Buatan dalam Pengembangan Aplikasi "
        "IoT Pengusir Hama. Jurnal Teknoinfo, 19(2), 245-250. https://doi.org/10.33365/teknoinfo.v19i2.193\n"
    )

    spans = tokenize_with_spans(doc_text)
    tokens = [doc_text[s:e].lower() for s, e in spans]

    def locate(phrase: str) -> tuple[int, int, int]:
        pw = [t.lower() for t in re.findall(r"[^\W_]+(?:[-\'][^\W_]+)*", phrase)]
        for i in range(len(tokens) - len(pw) + 1):
            if tokens[i : i + len(pw)] == pw:
                return i, i + len(pw) - 1, len(pw)
        raise ValueError(f"Frasa tidak ditemukan: {phrase}")

    spans_list = [
        # 1. Abstrak (Front Matter + Academic Transition boilerplate)
        locate("Penelitian ini bertujuan untuk menganalisis efektivitas algoritma pembandingan dokumen berbasis n-gram dan kosinus vektor"),
        # 2. Uraian tanpa tanda petik (tetap harus terhitung!)
        locate("Indonesia merupakan salah satu negara agraris terbesar di dunia di mana sektor pertanian menjadi tulang punggung perekonomian nasional serta ketahanan pangan masyarakat pedesaan"),
        # 3a. Kutipan lurus penuh ("...") + sub-pasase tumpang tindih di tengah kutipan (anti double-counting!)
        locate("Salah satu solusi yang menjanjikan adalah pemanfaatan teknologi Internet of Things dalam sistem pertanian cerdas untuk pemantauan kondisi lahan"),
        locate("pemanfaatan teknologi Internet of Things dalam sistem pertanian cerdas"),
        # 3b. Kutipan tipografis curly quotes (“...”)
        locate("Implementasi perangkat sensor gerak dan pengusir hama berbasis mikrokontroler terbukti membantu petani menjaga produktivitas tanaman padi"),
        # 4. Bibliografi (entri pendek & judul di bawah heading Daftar Pustaka, tanpa kata 'Daftar Pustaka' di dalam snippet!)
        locate("Putra A S Febriani O M"),
        locate("Implementasi Kecerdasan Buatan dalam Pengembangan Aplikasi IoT Pengusir Hama"),
    ]

    matches: List[Dict[str, Any]] = []
    for s_idx, e_idx, w_cnt in spans_list:
        matches.append({
            "source_document_id": 1,
            "submission_start": s_idx,
            "submission_end": e_idx,
            "source_start": 0,
            "source_end": max(0, w_cnt - 1),
            "word_count": w_cnt,
            "match_type": "exact",
            "similarity": 100.0,
        })

    total_doc_words = len(tokens)
    unique_matched = count_union_words([(m["submission_start"], m["submission_end"]) for m in matches])
    raw_sim = round((unique_matched / total_doc_words) * 100.0, 2)
    return doc_text, matches, raw_sim


def test_quote_and_bibliography_detection_accuracy():
    doc_text, matches, raw_sim = _build_realistic_test_doc()
    quote_spans = extract_quote_char_spans(doc_text)
    assert len(quote_spans) == 2

    annotate_matches_with_exclusions(doc_text, matches)

    # 0: Abstrak -> front_matter & boilerplate
    assert matches[0]["exclusion_flags"]["front_matter"] is True
    assert matches[0]["exclusion_flags"]["boilerplate"] is True
    assert matches[0]["exclusion_flags"]["quote"] is False
    assert matches[0]["exclusion_flags"]["bibliography"] is False

    # 1: Uraian dengan [1] tanpa tanda petik -> TIDAK dianggap quote atau biblio!
    assert matches[1]["exclusion_flags"] == {
        "quote": False,
        "boilerplate": False,
        "front_matter": False,
        "bibliography": False,
    }

    # 2, 3 (sub-match di tengah kutipan), 4 (curly quotes) -> seluruhnya terdeteksi QUOTE!
    assert matches[2]["exclusion_flags"]["quote"] is True
    assert matches[3]["exclusion_flags"]["quote"] is True
    assert matches[4]["exclusion_flags"]["quote"] is True

    # 5, 6 (di bawah Daftar Pustaka) -> terdeteksi BIBLIOGRAPHY, tidak masuk Frasa Baku
    assert matches[5]["exclusion_flags"]["bibliography"] is True
    assert matches[5]["exclusion_flags"]["boilerplate"] is False
    assert matches[6]["exclusion_flags"]["bibliography"] is True
    assert matches[6]["exclusion_flags"]["boilerplate"] is False

    # Pastikan sub-pasase #3 yang tumpang tindih dengan #2 tidak di-double-count saat menghitung skor
    res_qb = compute_filtered_score(raw_sim, matches, exclude_quotes=True, exclude_bibliography=True)
    assert res_qb["has_active_filters"] is True
    assert res_qb["excluded_passages_count"] == 5  # #2, #3, #4, #5, #6
    assert res_qb["rounded_filtered_similarity"] < raw_sim


def test_end_to_end_live_ui_and_exported_pdf_match_exactly():
    conn = get_connection()
    doc_text, base_matches, raw_sim = _build_realistic_test_doc()

    http_port = _find_free_port()
    base_url = f"http://127.0.0.1:{http_port}"
    php_srv = subprocess.Popen(
        ["php", "-S", f"127.0.0.1:{http_port}", "-t", "public"],
        cwd=str(PROJECT_ROOT),
        stdout=subprocess.DEVNULL,
        stderr=subprocess.DEVNULL,
    )

    test_doc_id = None
    test_rep_id = None
    test_src_doc_id = None
    ver_code = f"ART-P13-{int(time.time())}"

    try:
        _wait_http_ready(f"{base_url}/")
        cursor = conn.cursor(dictionary=True)

        cursor.execute("SELECT id FROM users WHERE status = 'active' ORDER BY id ASC LIMIT 1")
        user_id = int(cursor.fetchone()["id"])

        # Sumber korpus pembanding minimal agar report_sources & snippet bekerja nyata
        src_text = " ".join(["kata"] * 200)
        cursor.execute(
            """
            INSERT INTO documents (user_id, title, original_filename, storage_path, mime_type, file_size, status, document_type, corpus_visibility, created_at, updated_at)
            VALUES (%s, 'Jurnal Teknoinfo Sumber Uji P1-3', 'sumber_p13.txt', 'documents/corpus/sumber_p13.txt', 'text/plain', 500, 'completed', 'corpus', 'corpus', NOW(), NOW())
            """,
            (user_id,),
        )
        test_src_doc_id = int(cursor.lastrowid)
        cursor.execute(
            """
            INSERT INTO document_pages (document_id, page_number, text, created_at)
            VALUES (%s, 1, %s, NOW())
            """,
            (test_src_doc_id, src_text),
        )
        src_page_id = int(cursor.lastrowid)
        cursor.execute(
            """
            INSERT INTO document_chunks (document_id, page_id, chunk_index, text, normalized_text, word_count, start_word_position, end_word_position, created_at)
            VALUES (%s, %s, 0, %s, %s, 200, 0, 199, NOW())
            """,
            (test_src_doc_id, src_page_id, src_text, src_text),
        )

        # Dokumen submisi uji
        token_count = len(tokenize_with_spans(doc_text))
        cursor.execute(
            """
            INSERT INTO documents (user_id, title, original_filename, storage_path, mime_type, file_size, status, document_type, corpus_visibility, created_at, updated_at)
            VALUES (%s, 'Naskah Uji Filter P1-3 Kutipan Bibliografi', 'uji_p13.txt', 'documents/submissions/uji_p13.txt', 'text/plain', %s, 'completed', 'submission', 'private', NOW(), NOW())
            """,
            (user_id, len(doc_text.encode("utf-8"))),
        )
        test_doc_id = int(cursor.lastrowid)
        cursor.execute(
            """
            INSERT INTO document_pages (document_id, page_number, text, created_at)
            VALUES (%s, 1, %s, NOW())
            """,
            (test_doc_id, doc_text),
        )
        sub_page_id = int(cursor.lastrowid)
        cursor.execute(
            """
            INSERT INTO document_chunks (document_id, page_id, chunk_index, text, normalized_text, word_count, start_word_position, end_word_position, created_at)
            VALUES (%s, %s, 0, %s, %s, %s, 0, %s, NOW())
            """,
            (test_doc_id, sub_page_id, doc_text, doc_text.lower(), token_count, token_count - 1),
        )

        # Sengaja masukkan evidence_data TANPA exclusion_flags (mensimulasikan laporan lama seperti #1213!)
        legacy_ev_matches = []
        for m in base_matches:
            m_copy = dict(m)
            m_copy["source_document_id"] = test_src_doc_id
            legacy_ev_matches.append(m_copy)

        legacy_ev = {
            "total_words": token_count,
            "matches": legacy_ev_matches,
            "v1_1_presentation": {
                "v1_2_evolution": {
                    "section_segmentation": {
                        "total_words": token_count,
                        "core_substance_percentage": 60.0,
                        "zone_word_distribution": {"FRONT_MATTER": 25, "BODY_TEXT": max(1, token_count - 55), "METHODOLOGY": 0, "BACK_MATTER": 30},
                        "zone_percentage_distribution": {"FRONT_MATTER": 15.0, "BODY_TEXT": 70.0, "METHODOLOGY": 0.0, "BACK_MATTER": 15.0},
                    }
                }
            },
        }

        cursor.execute(
            """
            INSERT INTO reports (
                document_id, engine, predicted_decision, evidence_pattern, evidence_agreement, sources_agree, rationale,
                exact_similarity, cosine_similarity, overall_similarity, source_count, match_count, longest_match_words,
                verification_code, evidence_data, status, completed_at, created_at, updated_at
            )
            VALUES (%s, 'hybrid', 'match', 'VERBATIM_MATCH', 'STRONG_AGREEMENT', 1, 'Uji konsistensi filter layar vs PDF.',
                    %s, 30.0, %s, 1, %s, 22, %s, %s, 'completed', NOW(), NOW(), NOW())
            """,
            (test_doc_id, raw_sim, raw_sim, len(legacy_ev_matches), ver_code, json.dumps(legacy_ev)),
        )
        test_rep_id = int(cursor.lastrowid)

        cursor.execute(
            """
            INSERT INTO report_sources (report_id, source_document_id, matched_shingles, matched_words, similarity, exact_similarity, cosine_similarity, source_rank, created_at)
            VALUES (%s, %s, 20, 80, %s, %s, 30.0, 1, NOW())
            """,
            (test_rep_id, test_src_doc_id, raw_sim, raw_sim),
        )

        for m in legacy_ev_matches:
            cursor.execute(
                """
                INSERT INTO report_matches (report_id, source_document_id, submission_start, submission_end, source_start, source_end, word_count, match_type, similarity, created_at)
                VALUES (%s, %s, %s, %s, %s, %s, %s, 'exact', 100.0, NOW())
                """,
                (test_rep_id, test_src_doc_id, m["submission_start"], m["submission_end"], m["source_start"], m["source_end"], m["word_count"]),
            )
        conn.commit()

        # 1. Buka halaman laporan HTML -> server otomatis memperbaiki exclusion_flags pada evidence_data
        page_resp = requests.get(f"{base_url}/report/{test_rep_id}?token={ver_code}", timeout=15.0)
        assert page_resp.status_code == 200
        html = page_resp.text

        # Pastikan database ter-heal ke exclusion_schema_version == 2
        conn.commit()
        cursor.execute("SELECT evidence_data FROM reports WHERE id = %s", (test_rep_id,))
        healed_ev = json.loads(cursor.fetchone()["evidence_data"])
        assert healed_ev.get("exclusion_schema_version") == EXCLUSION_SCHEMA_VERSION

        # Ekstrak seluruh kartu .passage-card dari HTML layar untuk membuktikan atribut data-is-* tepat
        card_pattern = re.compile(
            r'data-sub-start="(\d+)"\s+data-sub-end="(\d+)"\s+data-word-count="(\d+)"\s+'
            r'data-is-boilerplate="([01])"\s+data-is-quote="([01])"\s+data-is-biblio="([01])"\s+data-is-front="([01])"'
        )
        ui_cards = card_pattern.findall(html)
        assert len(ui_cards) == len(legacy_ev_matches)

        def ui_compute_score(ex_q: bool, ex_bp: bool, ex_front: bool, ex_bib: bool) -> float:
            all_iv = []
            act_iv = []
            for s_s, s_e, wc, bp, qt, bib, fr in ui_cards:
                iv = (int(s_s), int(s_e))
                if int(wc) > 0:
                    all_iv.append(iv)
                excl = (
                    (ex_q and qt == "1")
                    or (ex_bp and bp == "1")
                    or (ex_front and fr == "1")
                    or (ex_bib and bib == "1")
                )
                if not excl and int(wc) > 0:
                    act_iv.append(iv)
            tot_u = count_union_words(all_iv)
            act_u = count_union_words(act_iv)
            return round((act_u / tot_u) * raw_sim, 2) if tot_u > 0 else raw_sim

        ui_raw = ui_compute_score(False, False, False, False)
        ui_qb = ui_compute_score(True, False, False, True)
        ui_all = ui_compute_score(True, True, True, True)
        assert ui_raw == round(raw_sim, 2)
        assert ui_qb < ui_raw
        assert ui_all < ui_qb

        # Pastikan tombol Unduh PDF membawa class sinkronisasi filter
        assert 'pdf-export-link' in html
        assert 'syncPdfExportLinks' in html

        # 2. Ekspor PDF via HTTP Endpoint TANPA filter -> harus memuat skor mentah
        pdf_raw_resp = requests.get(f"{base_url}/report/{test_rep_id}/export?token={ver_code}", timeout=30.0)
        assert pdf_raw_resp.status_code == 200
        assert pdf_raw_resp.content.startswith(b"%PDF-")
        pdf_raw_text = _extract_pdf_text(pdf_raw_resp.content)
        assert f"{ui_raw:.2f}%" in pdf_raw_text
        assert "Tidak ada filter aktif" in pdf_raw_text

        # 3. Ekspor PDF via HTTP Endpoint DENGAN Kutipan + Bibliografi aktif -> skor PDF === skor layar!
        pdf_qb_resp = requests.get(
            f"{base_url}/report/{test_rep_id}/export?token={ver_code}&exclude_quotes=1&exclude_bibliography=1",
            timeout=30.0,
        )
        assert pdf_qb_resp.status_code == 200
        assert pdf_qb_resp.content.startswith(b"%PDF-")
        pdf_qb_text = _extract_pdf_text(pdf_qb_resp.content)
        assert f"{ui_qb:.2f}%" in pdf_qb_text
        assert f"Mentah: {ui_raw:.2f}%" in pdf_qb_text
        assert "Kutipan (Tanda Petik)" in pdf_qb_text
        assert "Bibliografi / Daftar Pustaka" in pdf_qb_text

        # 4. Uji CLI worker.exporter langsung dengan keempat flag -> skor PDF === skor layar ui_all!
        cli_proc = subprocess.run(
            [
                str(PROJECT_ROOT / ".venv/bin/python"),
                "-m",
                "worker.exporter",
                "--report",
                str(test_rep_id),
                "--exclude-quotes",
                "--exclude-boilerplate",
                "--exclude-front-matter",
                "--exclude-bibliography",
                "--stdout",
            ],
            cwd=str(PROJECT_ROOT),
            capture_output=True,
            check=True,
            timeout=30,
        )
        assert cli_proc.stdout.startswith(b"%PDF-")
        pdf_all_text = _extract_pdf_text(cli_proc.stdout)
        assert f"{ui_all:.2f}%" in pdf_all_text
        assert "Frasa Baku (Boilerplate)" in pdf_all_text
        assert "Formalitas Depan (Front Matter)" in pdf_all_text

        cursor.close()
    finally:
        php_srv.terminate()
        try:
            php_srv.wait(timeout=4)
        except Exception:
            php_srv.kill()

        c2 = conn.cursor()
        try:
            if test_rep_id:
                c2.execute("DELETE FROM report_matches WHERE report_id = %s", (test_rep_id,))
                c2.execute("DELETE FROM report_sources WHERE report_id = %s", (test_rep_id,))
                c2.execute("DELETE FROM reports WHERE id = %s", (test_rep_id,))
            if test_doc_id:
                c2.execute("DELETE FROM document_chunks WHERE document_id = %s", (test_doc_id,))
                c2.execute("DELETE FROM document_pages WHERE document_id = %s", (test_doc_id,))
                c2.execute("DELETE FROM documents WHERE id = %s", (test_doc_id,))
            if test_src_doc_id:
                c2.execute("DELETE FROM document_chunks WHERE document_id = %s", (test_src_doc_id,))
                c2.execute("DELETE FROM document_pages WHERE document_id = %s", (test_src_doc_id,))
                c2.execute("DELETE FROM documents WHERE id = %s", (test_src_doc_id,))
            conn.commit()
        finally:
            c2.close()
            conn.close()
