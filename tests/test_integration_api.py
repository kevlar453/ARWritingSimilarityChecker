"""
AR Tilik - Automated Test Suite for LMS/OJS Integration Endpoint
Menguji integrasi API: POST /api/integration/check
"""

import hashlib
import os
import sys
import io
import json
import urllib.request
import urllib.parse
from pathlib import Path
import pytest

PROJECT_ROOT = Path(__file__).resolve().parents[1]
if str(PROJECT_ROOT) not in sys.path:
    sys.path.insert(0, str(PROJECT_ROOT))

from worker.database import get_connection, load_env

load_env()

BASE_URL = os.getenv("TEST_BASE_URL", "http://localhost:8085")


def get_or_create_test_api_key() -> str:
    conn = get_connection()
    try:
        cursor = conn.cursor(dictionary=True)
        cursor.execute("SELECT id FROM users WHERE status = 'active' ORDER BY id ASC LIMIT 1")
        user = cursor.fetchone()
        user_id = user["id"] if user else 1

        # Pastikan user uji memiliki kuota submisi yang cukup
        cursor.execute("UPDATE users SET bonus_submissions = 100, free_submissions_used_this_week = 0 WHERE id = %s", (user_id,))

        raw_key = "artilik_ojs_test_mock_key_integration_123"
        hashed = hashlib.sha256(raw_key.encode()).hexdigest()
        cursor.execute("SELECT id FROM api_keys WHERE api_key = %s", (hashed,))
        if not cursor.fetchone():
            cursor.execute("""
                INSERT INTO api_keys (user_id, key_name, key_prefix, api_key, system_type, quota_limit, quota_used, is_active, created_at, updated_at)
                VALUES (%s, 'Test OJS Key', 'artilik_ojs_tes...', %s, 'ojs', 1000, 0, 1, NOW(), NOW())
            """, (user_id, hashed))
        conn.commit()
        return raw_key
    finally:
        conn.close()


def test_integration_endpoint_contract_and_submission():
    """
    Uji endpoint POST /api/integration/check dengan dokumen submisi uji coba.
    """
    boundary = "----WebKitFormBoundary7MA4YWxkTrZu0gW"
    test_content = (
        "PENGARUH MOTIVASI DAN MINAT TERHADAP PRESTASI BELAJAR SISWA PADA MATA DIKLAT K3 DI SMK. "
        "Penelitian ini bertujuan untuk mengetahui pengaruh motivasi belajar dan minat belajar "
        "secara bersama-sama terhadap prestasi belajar siswa pada mata diklat keselamatan dan kesehatan kerja."
    )
    
    body = io.BytesIO()
    # Field: document
    body.write(f"--{boundary}\r\n".encode("utf-8"))
    body.write(b'Content-Disposition: form-data; name="document"; filename="skripsi_k3_smk.txt"\r\n')
    body.write(b"Content-Type: text/plain\r\n\r\n")
    body.write(test_content.encode("utf-8"))
    body.write(b"\r\n")
    
    # Field: title
    body.write(f"--{boundary}\r\n".encode("utf-8"))
    body.write(b'Content-Disposition: form-data; name="title"\r\n\r\n')
    body.write("Pengaruh Motivasi dan Minat K3 SMK Sedayu (OJS Pilot Test)".encode("utf-8"))
    body.write(b"\r\n")
    
    # Field: engine
    body.write(f"--{boundary}\r\n".encode("utf-8"))
    body.write(b'Content-Disposition: form-data; name="engine"\r\n\r\n')
    body.write(b"hybrid\r\n")
    body.write(f"--{boundary}--\r\n".encode("utf-8"))
    
    payload = body.getvalue()
    
    api_key = get_or_create_test_api_key()
    req = urllib.request.Request(
        f"{BASE_URL}/api/integration/check",
        data=payload,
        headers={
            "Content-Type": f"multipart/form-data; boundary={boundary}",
            "User-Agent": "Moodle-LMS-ARTilik-Plugin/1.0",
            "X-API-Key": api_key
        },
        method="POST"
    )
    
    try:
        with urllib.request.urlopen(req, timeout=10) as resp:
            status_code = resp.status
            resp_body = json.loads(resp.read().decode("utf-8"))
    except urllib.error.HTTPError as err:
        status_code = err.code
        resp_body = json.loads(err.read().decode("utf-8"))
    
    assert status_code == 202, f"Expected 202 Accepted, got {status_code}: {resp_body}"
    assert resp_body["status"] == "queued"
    assert "document_id" in resp_body
    assert "report_id" in resp_body
    assert "verification_code" in resp_body
    assert resp_body["verification_code"].startswith("ART-")
    assert "report_url" in resp_body
    assert "verification_url" in resp_body
    assert "pdf_export_url" in resp_body
    assert "status_api_url" in resp_body
    
    # Verifikasi status antrean di database
    doc_id = resp_body["document_id"]
    rep_id = resp_body["report_id"]
    conn = get_connection()
    try:
        cursor = conn.cursor(dictionary=True)
        cursor.execute("SELECT status, document_type FROM documents WHERE id = %s", (doc_id,))
        doc_row = cursor.fetchone()
        assert doc_row is not None
        assert doc_row["document_type"] == "submission"
        
        cursor.execute("SELECT status, verification_code FROM reports WHERE id = %s", (rep_id,))
        rep_row = cursor.fetchone()
        assert rep_row is not None
        assert rep_row["verification_code"] == resp_body["verification_code"]

        # Pembersihan rekaman uji coba
        cursor.execute("DELETE FROM jobs WHERE JSON_EXTRACT(payload, '$.document_id') = %s", (doc_id,))
        cursor.execute("DELETE FROM reports WHERE id = %s", (rep_id,))
        cursor.execute("DELETE FROM documents WHERE id = %s", (doc_id,))
        conn.commit()
        cursor.close()
    finally:
        conn.close()


def test_integration_endpoint_validation_missing_file():
    """
    Uji validasi jika dokumen tidak disertakan (harus return 400).
    """
    boundary = "----WebKitFormBoundaryEmpty"
    body = io.BytesIO()
    body.write(f"--{boundary}\r\n".encode("utf-8"))
    body.write(b'Content-Disposition: form-data; name="title"\r\n\r\n')
    body.write(b"No Document Test\r\n")
    body.write(f"--{boundary}--\r\n".encode("utf-8"))
    payload = body.getvalue()
    
    api_key = get_or_create_test_api_key()
    req = urllib.request.Request(
        f"{BASE_URL}/api/integration/check",
        data=payload,
        headers={
            "Content-Type": f"multipart/form-data; boundary={boundary}",
            "X-API-Key": api_key
        },
        method="POST"
    )
    
    try:
        urllib.request.urlopen(req, timeout=5)
        assert False, "Harus mengembalikan HTTP Error 400"
    except urllib.error.HTTPError as err:
        assert err.code == 400
        data = json.loads(err.read().decode("utf-8"))
        assert data["status"] == "error"
