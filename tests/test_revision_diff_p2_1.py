"""
Test Suite P2-1: Revision Diff v1 vs v2
Menguji:
1. Logika komparasi pasase deterministik (RESOLVED, IMPROVED, UNCHANGED, EXPANDED, NEW).
2. Endpoint API & kontroler revisi (parent_document_id, validasi otorisasi, v1 tetap utuh).
3. Integrasi DB + HTML report page (#revisionDiffPanel & #revisionChainBar) + JSON Public API v1.
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
from worker.revision_diff import build_revision_diff_for_report, compute_revision_diff

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


def test_compute_revision_diff_statuses_and_metrics():
    prev_rep = {
        "id": 1001,
        "document_id": 2001,
        "overall_similarity": 60.0,
        "evidence_data": json.dumps({"total_words": 200}),
    }
    curr_rep = {
        "id": 1002,
        "document_id": 2002,
        "overall_similarity": 32.5,
        "evidence_data": json.dumps({"total_words": 210}),
    }

    prev_matches = [
        # 1. RESOLVED (hilang di v2)
        {
            "source_document_id": 50,
            "source_title": "Jurnal Pertanian A",
            "submission_start": 10,
            "submission_end": 29,
            "word_count": 20,
            "submission_snippet": "pengendalian hama padi secara kimiawi sering merusak ekosistem sawah dalam jangka panjang",
        },
        # 2. IMPROVED (dipotong menjadi lebih pendek di v2)
        {
            "source_document_id": 51,
            "source_title": "Jurnal IoT B",
            "submission_start": 40,
            "submission_end": 59,
            "word_count": 20,
            "submission_snippet": "sensor suhu kelembaban dan ultrasonik dihubungkan ke mikrokontroler esp32 untuk memantau kondisi lahan pertanian modern secara kontinu",
        },
        # 3. UNCHANGED (tetap persis di v2)
        {
            "source_document_id": 52,
            "source_title": "Jurnal Sistem C",
            "submission_start": 80,
            "submission_end": 94,
            "word_count": 15,
            "submission_snippet": "basis data relasional mariadb menyimpan seluruh rekaman telemetry secara terstruktur dan efisien",
        },
        # 4. Quote only match in v1
        {
            "source_document_id": 53,
            "source_title": "Buku Referensi D",
            "submission_start": 110,
            "submission_end": 124,
            "word_count": 15,
            "exclusion_flags": {"quote": True, "boilerplate": False, "front_matter": False, "bibliography": False},
            "submission_snippet": "transformasi pertanian presisi menuntut integrasi sensor lapangan dan analitik cloud",
        },
    ]

    curr_matches = [
        # Match 2 menjadi 11 kata (IMPROVED)
        {
            "source_document_id": 51,
            "source_title": "Jurnal IoT B",
            "submission_start": 45,
            "submission_end": 55,
            "word_count": 11,
            "submission_snippet": "sensor suhu kelembaban dan ultrasonik dihubungkan ke mikrokontroler esp32 untuk memantau",
        },
        # Match 3 tetap persis (UNCHANGED)
        {
            "source_document_id": 52,
            "source_title": "Jurnal Sistem C",
            "submission_start": 85,
            "submission_end": 99,
            "word_count": 15,
            "submission_snippet": "basis data relasional mariadb menyimpan seluruh rekaman telemetry secara terstruktur dan efisien",
        },
        # Match 4 tetap berupa kutipan
        {
            "source_document_id": 53,
            "source_title": "Buku Referensi D",
            "submission_start": 115,
            "submission_end": 129,
            "word_count": 15,
            "exclusion_flags": {"quote": True, "boilerplate": False, "front_matter": False, "bibliography": False},
            "submission_snippet": "transformasi pertanian presisi menuntut integrasi sensor lapangan dan analitik cloud",
        },
        # Match baru (NEW)
        {
            "source_document_id": 54,
            "source_title": "Artikel Baru E",
            "submission_start": 150,
            "submission_end": 161,
            "word_count": 12,
            "submission_snippet": "protokol komunikasi mqtt mengirim paket ukuran ringan antar node sensor menuju broker",
        },
    ]

    diff = compute_revision_diff(
        curr_rep,
        prev_rep,
        curr_matches,
        prev_matches,
        curr_doc={"id": 2002, "revision_number": 2, "title": "Draft v2", "revision_note": "Parafrase Bab 2"},
        prev_doc={"id": 2001, "revision_number": 1, "title": "Draft v1"},
        source_titles={50: "Jurnal Pertanian A", 51: "Jurnal IoT B", 52: "Jurnal Sistem C", 53: "Buku Referensi D", 54: "Artikel Baru E"},
    )

    assert diff["metrics"]["delta_overall_similarity"] == -27.5
    assert diff["metrics"]["prev_matched_words"] == 70
    assert diff["metrics"]["curr_matched_words"] == 53
    assert diff["metrics"]["delta_matched_words"] == -17
    assert diff["status_counts"]["resolved"] == 1
    assert diff["status_counts"]["improved"] == 1
    assert diff["status_counts"]["unchanged"] == 2
    assert diff["status_counts"]["new"] == 1
    assert diff["curr_revision_note"] == "Parafrase Bab 2"

    statuses = {c["source_document_id"]: c["status"] for c in diff["passage_changes"]}
    assert statuses[50] == "RESOLVED"
    assert statuses[51] == "IMPROVED"
    assert statuses[52] == "UNCHANGED"
    assert statuses[54] == "NEW"


def test_end_to_end_revision_tracking_api_authorization_and_report_ui():
    import hashlib
    import secrets
    import io

    conn = get_connection()
    http_port = _find_free_port()
    base_url = f"http://127.0.0.1:{http_port}"
    php_srv = subprocess.Popen(
        ["php", "-S", f"127.0.0.1:{http_port}", "-t", "public"],
        cwd=str(PROJECT_ROOT),
        stdout=subprocess.DEVNULL,
        stderr=subprocess.DEVNULL,
    )

    created_doc_ids = []
    created_rep_ids = []
    created_job_ids = []
    created_api_key_ids = []
    temp_files = []

    try:
        _wait_http_ready(f"{base_url}/")
        cur = conn.cursor(dictionary=True)

        cur.execute("SELECT id FROM users WHERE status = 'active' ORDER BY id ASC LIMIT 2")
        users = cur.fetchall()
        assert len(users) >= 1
        user1_id = int(users[0]["id"])
        user2_id = int(users[1]["id"]) if len(users) >= 2 else None

        # Pastikan user1 punya cukup kuota dan simpan snapshot untuk pemulihan di finally
        cur.execute("SELECT id, free_submissions_used_this_week, bonus_submissions FROM users WHERE id = %s", (user1_id,))
        user1_snapshot = cur.fetchone()
        cur.execute("UPDATE users SET free_submissions_used_this_week = 0, bonus_submissions = bonus_submissions + 2 WHERE id = %s", (user1_id,))

        # Buat API key untuk user1
        raw_key = "artilik_live_p21_" + secrets.token_hex(12)
        key_hash = hashlib.sha256(raw_key.encode()).hexdigest()
        cur.execute(
            """
            INSERT INTO api_keys (user_id, key_name, key_prefix, api_key, system_type, quota_limit, quota_used, rate_limit_per_minute, is_active, created_at, updated_at)
            VALUES (%s, 'Test P2-1 Revision Diff', %s, %s, 'ojs', 100, 0, 120, 1, NOW(), NOW())
            """,
            (user1_id, raw_key[:16], key_hash),
        )
        created_api_key_ids.append(int(cur.lastrowid))

        # Sumber korpus simulasi
        cur.execute(
            """
            INSERT INTO documents (user_id, title, original_filename, storage_path, mime_type, file_size, status, document_type, corpus_visibility, created_at, updated_at)
            VALUES (%s, 'Sumber Jurnal Revisi P2-1', 'src_rev.txt', 'documents/corpus/src_rev.txt', 'text/plain', 300, 'completed', 'corpus', 'corpus', NOW(), NOW())
            """,
            (user1_id,),
        )
        src_doc_id = int(cur.lastrowid)
        created_doc_ids.append(src_doc_id)

        # Buat Naskah v1 + Report v1 lengkap
        v1_text = " ".join(
            [
                "Sistem monitoring tanaman berbasis internet of things memakai mikrokontroler esp32 dan sensor kelembaban tanah otomatis.",
                "Pengolahan data curah hujan membantu petani mengatur jadwal irigasi sawah secara lebih efisien dan hemat air.",
            ]
        )
        cur.execute(
            """
            INSERT INTO documents (user_id, title, original_filename, storage_path, mime_type, file_size, status, document_type, corpus_visibility, revision_number, created_at, updated_at)
            VALUES (%s, 'Naskah Uji Revisi P2-1', 'naskah_v1.txt', 'documents/submissions/v1.txt', 'text/plain', 400, 'completed', 'submission', 'private', 1, NOW(), NOW())
            """,
            (user1_id,),
        )
        v1_doc_id = int(cur.lastrowid)
        created_doc_ids.append(v1_doc_id)

        cur.execute("INSERT INTO document_pages (document_id, page_number, text, created_at) VALUES (%s, 1, %s, NOW())", (v1_doc_id, v1_text))
        v1_page_id = int(cur.lastrowid)
        cur.execute(
            "INSERT INTO document_chunks (document_id, page_id, chunk_index, text, normalized_text, word_count, start_word_position, end_word_position, created_at) VALUES (%s, %s, 0, %s, %s, 30, 0, 29, NOW())",
            (v1_doc_id, v1_page_id, v1_text, v1_text.lower()),
        )

        v1_ver = f"ART-P21V1-{int(time.time())}"
        v1_ev = {
            "total_words": 30,
            "matches": [
                {
                    "source_document_id": src_doc_id,
                    "submission_start": 0,
                    "submission_end": 13,
                    "word_count": 14,
                    "match_type": "exact",
                    "passage_text": "sistem monitoring tanaman berbasis internet of things memakai mikrokontroler esp32 dan sensor kelembaban tanah",
                }
            ],
        }
        cur.execute(
            """
            INSERT INTO reports (document_id, engine, predicted_decision, overall_similarity, exact_similarity, cosine_similarity, source_count, match_count, longest_match_words, verification_code, evidence_data, status, completed_at, created_at, updated_at)
            VALUES (%s, 'hybrid', 'review', 46.67, 46.67, 25.0, 1, 1, 14, %s, %s, 'completed', NOW(), NOW(), NOW())
            """,
            (v1_doc_id, v1_ver, json.dumps(v1_ev)),
        )
        v1_rep_id = int(cur.lastrowid)
        created_rep_ids.append(v1_rep_id)
        cur.execute(
            "INSERT INTO report_sources (report_id, source_document_id, matched_shingles, matched_words, similarity, exact_similarity, cosine_similarity, source_rank, created_at) VALUES (%s, %s, 10, 14, 46.67, 46.67, 25.0, 1, NOW())",
            (v1_rep_id, src_doc_id),
        )
        cur.execute(
            "INSERT INTO report_matches (report_id, source_document_id, submission_start, submission_end, source_start, source_end, word_count, match_type, similarity, created_at) VALUES (%s, %s, 0, 13, 0, 13, 14, 'exact', 100.0, NOW())",
            (v1_rep_id, src_doc_id),
        )

        # Tambah dokumen milik user2 untuk uji penolakan lintas akun (IDOR protection)
        other_doc_id = None
        if user2_id:
            cur.execute(
                """
                INSERT INTO documents (user_id, title, original_filename, storage_path, mime_type, file_size, status, document_type, corpus_visibility, revision_number, created_at, updated_at)
                VALUES (%s, 'Milik User Lain', 'other.txt', 'documents/submissions/other.txt', 'text/plain', 50, 'completed', 'submission', 'private', 1, NOW(), NOW())
                """,
                (user2_id,),
            )
            other_doc_id = int(cur.lastrowid)
            created_doc_ids.append(other_doc_id)

        conn.commit()

        # 1. Jika user2 ada, pastikan upload v2 menunjuk parent milik user2 DITOLAK 403 tanpa memotong kuota
        if other_doc_id:
            payload = urllib.parse.urlencode({
                "title": "Percobaan IDOR",
                "text": "Teks revisi yang seharusnya ditolak karena bukan pemilik parent.",
                "parent_document_id": str(other_doc_id),
            }).encode("utf-8")
            req_forbidden = urllib.request.Request(
                f"{base_url}/api/v1/check",
                data=payload,
                headers={"X-API-Key": raw_key},
                method="POST",
            )
            with pytest.raises(urllib.error.HTTPError) as exc_info:
                urllib.request.urlopen(req_forbidden, timeout=10)
            assert exc_info.value.code == 403

        # 2. Unggah Naskah v2 via POST /api/v1/check dengan parent_document_id = v1_doc_id
        v2_payload = urllib.parse.urlencode({
            "title": "Naskah Uji Revisi P2-1 (v2)",
            "text": "Alat pantau lahan berbasis jaringan sensor nirkabel membantu mengoptimalkan pengairan sawah pertanian modern secara efektif.",
            "parent_document_id": str(v1_doc_id),
            "revision_note": "Parafrase total kalimat mikrokontroler esp32 di pendahuluan",
        }).encode("utf-8")
        req_v2 = urllib.request.Request(
            f"{base_url}/api/v1/check",
            data=v2_payload,
            headers={"X-API-Key": raw_key},
            method="POST",
        )
        with urllib.request.urlopen(req_v2, timeout=10) as resp:
            assert resp.status == 202
            body_v2 = json.loads(resp.read().decode("utf-8"))

        v2_doc_id = int(body_v2["data"]["document_id"])
        v2_rep_id = int(body_v2["data"]["report_id"])
        created_doc_ids.append(v2_doc_id)
        created_rep_ids.append(v2_rep_id)
        if body_v2["data"].get("job_id"):
            created_job_ids.append(int(body_v2["data"]["job_id"]))

        # Cek metadata revisi di DB & simpan path file sementara untuk cleanup
        conn.commit()
        cur.execute("SELECT parent_document_id, revision_root_id, revision_number, revision_note, storage_path FROM documents WHERE id = %s", (v2_doc_id,))
        v2_doc_row = cur.fetchone()
        if v2_doc_row.get("storage_path"):
            temp_files.append(Path(v2_doc_row["storage_path"]))
        assert int(v2_doc_row["parent_document_id"]) == v1_doc_id
        assert int(v2_doc_row["revision_root_id"]) == v1_doc_id
        assert int(v2_doc_row["revision_number"]) == 2
        assert "Parafrase total" in (v2_doc_row["revision_note"] or "")

        # Pastikan v1 tetap utuh & tidak tertimpa!
        cur.execute("SELECT overall_similarity, status FROM reports WHERE id = %s", (v1_rep_id,))
        v1_check = cur.fetchone()
        assert float(v1_check["overall_similarity"]) == 46.67
        assert v1_check["status"] == "completed"

        # Simulasikan v2 selesai dianalisis (match v1 hilang -> skor turun ke 0.00%, lalu hitung revision_diff)
        cur.execute("SELECT verification_code FROM reports WHERE id = %s", (v2_rep_id,))
        v2_ver = cur.fetchone()["verification_code"]
        v2_ev = {"total_words": 18, "matches": []}
        cur.execute(
            "UPDATE reports SET status = 'completed', overall_similarity = 0.00, exact_similarity = 0.00, cosine_similarity = 5.00, evidence_data = %s, completed_at = NOW() WHERE id = %s",
            (json.dumps(v2_ev), v2_rep_id),
        )
        conn.commit()

        # Hitung diff via module dan simpan ke evidence_data
        diff_res = build_revision_diff_for_report(conn, v2_rep_id)
        assert diff_res is not None
        assert diff_res["metrics"]["delta_overall_similarity"] == -46.67
        assert diff_res["status_counts"]["resolved"] == 1
        assert diff_res["metrics"]["resolution_rate_pct"] == 100.0
        v2_ev["revision_diff"] = diff_res
        cur.execute("UPDATE reports SET evidence_data = %s WHERE id = %s", (json.dumps(v2_ev), v2_rep_id))
        conn.commit()

        # 3. Buka UI report v2 -> pastikan #revisionChainBar dan #revisionDiffPanel tampil dengan angka delta & tautan v1!
        html_v2 = urllib.request.urlopen(f"{base_url}/report/{v2_rep_id}?token={v2_ver}", timeout=10).read().decode("utf-8")
        assert "revisionChainBar" in html_v2
        assert "revisionDiffPanel" in html_v2
        assert "46.67%" in html_v2
        assert "-46.67%" in html_v2
        assert "HILANG / SELESAI" in html_v2
        assert "v1" in html_v2 and "v2" in html_v2

        # 4. Pastikan JSON Public API v1 menyertakan field revision + diff
        req_api = urllib.request.Request(f"{base_url}/api/v1/report/{v2_rep_id}", headers={"X-API-Key": raw_key})
        api_rep = json.loads(urllib.request.urlopen(req_api, timeout=10).read().decode("utf-8"))
        assert api_rep["data"]["revision"]["revision_number"] == 2
        assert api_rep["data"]["revision"]["parent_document_id"] == v1_doc_id
        assert api_rep["data"]["revision"]["diff"]["status_counts"]["resolved"] == 1

        cur.close()
    finally:
        php_srv.terminate()
        try:
            php_srv.wait(timeout=3)
        except Exception:
            php_srv.kill()

        for tf in temp_files:
            try:
                tf.unlink(missing_ok=True)
            except Exception:
                pass

        c2 = conn.cursor()
        try:
            if "user1_snapshot" in locals() and user1_snapshot:
                c2.execute(
                    "UPDATE users SET free_submissions_used_this_week = %s, bonus_submissions = %s WHERE id = %s",
                    (
                        user1_snapshot["free_submissions_used_this_week"],
                        user1_snapshot["bonus_submissions"],
                        user1_snapshot["id"],
                    ),
                )
            for jid in created_job_ids:
                c2.execute("DELETE FROM jobs WHERE id = %s", (jid,))
            for rid in created_rep_ids:
                c2.execute("DELETE FROM report_matches WHERE report_id = %s", (rid,))
                c2.execute("DELETE FROM report_sources WHERE report_id = %s", (rid,))
                c2.execute("DELETE FROM reports WHERE id = %s", (rid,))
            for did in created_doc_ids:
                c2.execute("DELETE FROM document_chunks WHERE document_id = %s", (did,))
                c2.execute("DELETE FROM document_pages WHERE document_id = %s", (did,))
                c2.execute("DELETE FROM documents WHERE id = %s", (did,))
            for kid in created_api_key_ids:
                c2.execute("DELETE FROM api_keys WHERE id = %s", (kid,))
            conn.commit()
        finally:
            c2.close()
            conn.close()
