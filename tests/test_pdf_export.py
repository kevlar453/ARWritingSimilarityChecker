import urllib.request
import urllib.parse
import json
import pytest
from worker.database import get_connection
from worker.exporter import export_report_pdf, generate_verification_hash

BASE_URL = "http://localhost:8085"

def get_latest_completed_report_id() -> int:
    conn = get_connection()
    try:
        cursor = conn.cursor(dictionary=True)
        cursor.execute("SELECT id FROM reports WHERE status = 'completed' ORDER BY id DESC LIMIT 1")
        row = cursor.fetchone()
        return row["id"] if row else 1
    finally:
        conn.close()

def test_generate_verification_hash():
    h1 = generate_verification_hash(1, 10, "2026-10-03 10:00:00", 75.5)
    h2 = generate_verification_hash(1, 10, "2026-10-03 10:00:00", 75.5)
    h3 = generate_verification_hash(2, 10, "2026-10-03 10:00:00", 75.5)
    
    assert h1.startswith("ART-")
    assert h1 == h2  # Deterministik
    assert h1 != h3  # ID berbeda -> hash berbeda

def test_export_report_pdf_direct():
    report_id = get_latest_completed_report_id()
    pdf_bytes = export_report_pdf(report_id)
    
    assert isinstance(pdf_bytes, bytes)
    assert len(pdf_bytes) > 5000
    assert pdf_bytes.startswith(b"%PDF-")

def test_export_report_pdf_http_endpoint():
    report_id = get_latest_completed_report_id()
    url = f"{BASE_URL}/report/{report_id}/export"
    
    req = urllib.request.Request(url, method="GET")
    with urllib.request.urlopen(req, timeout=15) as resp:
        assert resp.getcode() == 200
        headers = dict(resp.headers)
        assert "application/pdf" in headers.get("Content-Type", "")
        body = resp.read()
        assert len(body) > 5000
        assert body.startswith(b"%PDF-")

def test_preview_report_pdf_http_endpoint():
    report_id = get_latest_completed_report_id()
    url = f"{BASE_URL}/report/{report_id}/preview"
    
    req = urllib.request.Request(url, method="GET")
    with urllib.request.urlopen(req, timeout=15) as resp:
        assert resp.getcode() == 200
        headers = dict(resp.headers)
        assert "text/html" in headers.get("Content-Type", "")
        html = resp.read().decode("utf-8")
        assert "LEMBAR HASIL UJI KEMIRIPAN TEKS AKADEMIK" in html
        assert "AR TILIK" in html
        assert "Prinsip Keadilan Akademik" in html
