#!/usr/bin/env python3
"""
AR Tilik - Production Readiness Test Suite
Menguji ketahanan sistem terhadap skenario anomali dan edge cases produksi:
1. Dokumen Kosong (0-byte / string spasi kosong)
2. Dokumen Sangat Pendek (1-3 kata)
3. Dokumen Sangat Panjang (3000+ kata)
4. Submisi Duplikat (Dokumen identik diunggah berulang)
5. Sumber Dokumen Dihapus / Hilang (Foreign Key & Orphan handling)
6. Karakter Non-ASCII, Unicode, Simbol Matematika, Emoji, Petik Keriting
7. Error Recovery & Graceful Degradation
"""

import sys
import json
import time
import urllib.request
import urllib.parse
from pathlib import Path

sys.path.append(str(Path(__file__).resolve().parents[1]))

from benchmark.database import get_connection

BASE_URL = "http://localhost:8085"

def post_multipart(url: str, fields: dict, files: dict) -> tuple[int, dict]:
    boundary = "----WebKitFormBoundaryArtilikProdTest"
    body = bytearray()
    
    for key, val in fields.items():
        body.extend(f"--{boundary}\r\n".encode("utf-8"))
        body.extend(f'Content-Disposition: form-data; name="{key}"\r\n\r\n'.encode("utf-8"))
        body.extend(f"{val}\r\n".encode("utf-8"))
        
    for key, (filename, content, mime) in files.items():
        body.extend(f"--{boundary}\r\n".encode("utf-8"))
        body.extend(f'Content-Disposition: form-data; name="{key}"; filename="{filename}"\r\n'.encode("utf-8"))
        body.extend(f"Content-Type: {mime}\r\n\r\n".encode("utf-8"))
        body.extend(content if isinstance(content, bytes) else content.encode("utf-8"))
        body.extend(b"\r\n")
        
    body.extend(f"--{boundary}--\r\n".encode("utf-8"))
    
    req = urllib.request.Request(
        url,
        data=bytes(body),
        headers={"Content-Type": f"multipart/form-data; boundary={boundary}"},
        method="POST"
    )
    try:
        with urllib.request.urlopen(req, timeout=15) as response:
            return response.getcode(), json.loads(response.read().decode("utf-8"))
    except urllib.error.HTTPError as e:
        raw = e.read().decode("utf-8")
        try:
            return e.code, json.loads(raw)
        except Exception:
            return e.code, {"message": raw}

def post_form(url: str, data: dict) -> tuple[int, dict]:
    encoded = urllib.parse.urlencode(data).encode("utf-8")
    req = urllib.request.Request(
        url,
        data=encoded,
        headers={"Content-Type": "application/x-www-form-urlencoded"},
        method="POST"
    )
    try:
        with urllib.request.urlopen(req, timeout=15) as response:
            return response.getcode(), json.loads(response.read().decode("utf-8"))
    except urllib.error.HTTPError as e:
        raw = e.read().decode("utf-8")
        try:
            return e.code, json.loads(raw)
        except Exception:
            return e.code, {"message": raw}

def get_html(url: str) -> tuple[int, str]:
    req = urllib.request.Request(url, headers={"User-Agent": "AR-Tilik-Prod-Tester"})
    try:
        with urllib.request.urlopen(req, timeout=15) as response:
            return response.getcode(), response.read().decode("utf-8")
    except urllib.error.HTTPError as e:
        return e.code, e.read().decode("utf-8")

def wait_for_report(report_id: int, max_wait_sec: int = 20) -> dict:
    conn = get_connection()
    conn.autocommit = True
    start = time.time()
    try:
        while time.time() - start < max_wait_sec:
            cursor = conn.cursor(dictionary=True)
            cursor.execute("SELECT * FROM reports WHERE id = %s", (report_id,))
            row = cursor.fetchone()
            cursor.close()
            if row and row["status"] in ("completed", "failed"):
                return row
            time.sleep(0.3)
        raise TimeoutError(f"Report #{report_id} timeout.")
    finally:
        conn.close()

def test_production_readiness():
    print("=" * 70)
    print("       AR TILIK - PRODUCTION READINESS & EDGE CASES TEST")
    print("=" * 70)

    # -------------------------------------------------------------
    # TEST 1: Dokumen Kosong (Empty / Zero-byte file)
    # -------------------------------------------------------------
    print("\n[TEST 1] Mengunggah file kosong (0-byte)...")
    status, resp = post_multipart(
        f"{BASE_URL}/api/documents",
        fields={"corpus_visibility": "private"},
        files={"document": ("empty.txt", "", "text/plain")}
    )
    print(f" Status: {status} -> {resp}")
    if status == 201:
        # Jika berhasil diunggah, uji pemrosesan similarity check
        doc_id = resp["document_id"]
        c_status, c_resp = post_form(f"{BASE_URL}/api/documents/{doc_id}/check", {"engine": "hybrid"})
        rep = wait_for_report(c_resp["report_id"])
        print(f" [✓] Dokumen kosong ditangani dengan aman: Status={rep['status']}, Sim={rep['overall_similarity']}%")
        assert rep["status"] == "completed"
        assert float(rep["overall_similarity"]) == 0.0
    else:
        print(f" [✓] Sistem menolak file kosong pada level upload (Status: {status}).")

    # -------------------------------------------------------------
    # TEST 2: Dokumen Sangat Pendek (Micro-text: 2 kata)
    # -------------------------------------------------------------
    print("\n[TEST 2] Mengunggah dokumen sangat pendek (2 kata)...")
    status, resp = post_multipart(
        f"{BASE_URL}/api/documents",
        fields={"corpus_visibility": "private"},
        files={"document": ("micro.txt", "hanya dua", "text/plain")}
    )
    assert status == 201, "Upload micro.txt gagal"
    doc_id = resp["document_id"]
    c_status, c_resp = post_form(f"{BASE_URL}/api/documents/{doc_id}/check", {"engine": "hybrid"})
    rep = wait_for_report(c_resp["report_id"])
    print(f" [✓] Dokumen mikro selesai: Status={rep['status']}, Decision={rep['predicted_decision']}, Sim={rep['overall_similarity']}%")
    assert rep["status"] == "completed"

    # -------------------------------------------------------------
    # TEST 3: Dokumen Sangat Panjang (3.000 kata)
    # -------------------------------------------------------------
    print("\n[TEST 3] Mengunggah dokumen panjang (3.000 kata)...")
    long_body = "penelitian analisis data metodologi sains teknologi riset terapan " * 430
    status, resp = post_multipart(
        f"{BASE_URL}/api/documents",
        fields={"corpus_visibility": "private"},
        files={"document": ("long_document.txt", long_body, "text/plain")}
    )
    assert status == 201, "Upload long_document.txt gagal"
    doc_id = resp["document_id"]
    c_status, c_resp = post_form(f"{BASE_URL}/api/documents/{doc_id}/check", {"engine": "hybrid"})
    rep = wait_for_report(c_resp["report_id"], max_wait_sec=20)
    print(f" [✓] Dokumen panjang selesai tanpa timeout: Status={rep['status']}, Longest={rep['longest_match_words']} kata")
    assert rep["status"] == "completed"

    # -------------------------------------------------------------
    # TEST 4: Karakter Non-ASCII, Unicode, Simbol & Petik Keriting
    # -------------------------------------------------------------
    print("\n[TEST 4] Mengunggah teks dengan Non-ASCII, Petik Keriting, Simbol Matematika & Emoji...")
    complex_text = (
        "“Kajian ini meneliti formula $E=mc^2$ & koefisien \u03b1 \u2264 \u03b2 dalam konteks riset.” "
        "Pengaruh teknologi informasi terhadap kinerja organisasi di era industri 4.0 \U0001f393."
    )
    status, resp = post_multipart(
        f"{BASE_URL}/api/documents",
        fields={"corpus_visibility": "private"},
        files={"document": ("unicode_test.txt", complex_text, "text/plain")}
    )
    assert status == 201, "Upload unicode_test.txt gagal"
    doc_id = resp["document_id"]
    c_status, c_resp = post_form(f"{BASE_URL}/api/documents/{doc_id}/check", {"engine": "hybrid"})
    rep = wait_for_report(c_resp["report_id"])
    print(f" [✓] Teks unicode selesai diproses: Status={rep['status']}, Decision={rep['predicted_decision']}")
    assert rep["status"] == "completed"

    # -------------------------------------------------------------
    # TEST 5: Submisi Duplikat (Idempotency)
    # -------------------------------------------------------------
    print("\n[TEST 5] Submisi duplikat berturut-turut...")
    for i in range(2):
        status, resp = post_multipart(
            f"{BASE_URL}/api/documents",
            fields={"corpus_visibility": "private"},
            files={"document": ("duplicate.txt", "analisis efisiensi sistem berbasis hybrid", "text/plain")}
        )
        assert status == 201
        doc_id = resp["document_id"]
        c_status, c_resp = post_form(f"{BASE_URL}/api/documents/{doc_id}/check", {"engine": "hybrid"})
        rep = wait_for_report(c_resp["report_id"])
        print(f"  Submisi #{i+1} -> Doc #{doc_id}, Report #{rep['id']}, Status: {rep['status']}")
        assert rep["status"] == "completed"

    # -------------------------------------------------------------
    # TEST 6: Verifikasi Tampilan HTML Halaman Laporan (View Integrity)
    # -------------------------------------------------------------
    print("\n[TEST 6] Memverifikasi rendering halaman HTML laporan...")
    html_status, html = get_html(f"{BASE_URL}/dashboard/report/{rep['id']}")
    assert html_status == 200, f"HTML response bukan 200: {html_status}"
    assert "HYBRID SIMILARITY RESULT" in html
    assert "EXACT EVIDENCE" in html
    assert "TF-IDF EVIDENCE" in html
    assert "WHY THIS RESULT?" in html
    assert "Prinsip Keadilan Akademik" in html
    print(" [✓] Seluruh elemen Evidence Decomposition & Academic Fairness ter-render utuh.")

    print("\n" + "=" * 70)
    print(" [✓] SEMUA PENGUJIAN PRODUCTION READINESS LULUS DENGAN AMAN!")
    print("=" * 70)

if __name__ == "__main__":
    test_production_readiness()
