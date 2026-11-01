"""
AR Tilik - Automated Test Suite for Visitor Geolocation & Demographic Analytics
Menguji resolusi geolokasi pengunjung (negara, wilayah/provinsi, kota),
kalkulasi agregasi analitik negara vs wilayah, dan endpoint polling real-time admin.
"""

import os
import sys
import requests
from pathlib import Path
import pytest

PROJECT_ROOT = Path(__file__).resolve().parents[1]
if str(PROJECT_ROOT) not in sys.path:
    sys.path.insert(0, str(PROJECT_ROOT))

from worker.database import get_connection, load_env

load_env()

BASE_URL = os.getenv("TEST_BASE_URL", "http://localhost:8085")


@pytest.fixture(scope="module")
def db_conn():
    conn = get_connection()
    conn.autocommit = True
    yield conn
    conn.close()


def test_landing_page_records_visit_with_geo():
    """Menguji bahwa akses ke landing page merekam kunjungan pengunjung."""
    session = requests.Session()
    res = session.get(f"{BASE_URL}/")
    assert res.status_code == 200

    conn = get_connection()
    try:
        cursor = conn.cursor(dictionary=True)
        cursor.execute("SELECT country_code, country_name, region_name FROM landing_visitors ORDER BY id DESC LIMIT 1")
        row = cursor.fetchone()
        assert row is not None
        assert row["country_code"] is not None
        assert row["country_name"] is not None
    finally:
        cursor.close()
        conn.close()


def test_admin_visitors_realtime_api_schema(db_conn):
    """Menguji respons JSON endpoint /admin/analytics/visitors-realtime memuat struktur geolokasi."""
    session = requests.Session()
    import uuid
    random_str = uuid.uuid4().hex[:8]
    admin_email = f"admin_{random_str}@artilik.id"
    admin_password = "testpassword"
    pw_hash = "$2y$12$9zpp2JotUGfsa0PuPEytf.zzILhmnywPLVYWwe90o8zk4XrrzBJxm"

    cursor = db_conn.cursor()
    cursor.execute("""
        INSERT INTO users (name, email, password_hash, institution, role, status, email_verified_at, week_reset_at, created_at, updated_at)
        VALUES (%s, %s, %s, 'AR Tilik Core', 'admin', 'active', NOW(), DATE_ADD(NOW(), INTERVAL 7 DAY), NOW(), NOW())
    """, (f"Admin Geo {random_str}", admin_email, pw_hash))
    admin_id = cursor.lastrowid
    cursor.close()

    try:
        # Login sebagai admin
        login_res = session.post(f"{BASE_URL}/auth/login", json={
            "email": admin_email,
            "password": admin_password
        }, headers={"Accept": "application/json"})
        assert login_res.status_code == 200

        res = session.get(f"{BASE_URL}/admin/analytics/visitors-realtime", headers={
            "X-Requested-With": "XMLHttpRequest"
        })
        assert res.status_code == 200
        data = res.json()

        assert data["status"] == "success"
        assert "stats" in data
        assert "daily" in data
        assert "geo" in data

        geo = data["geo"]
        assert "summary" in geo
        assert "countries" in geo
        assert "regions" in geo

        summary = geo["summary"]
        assert "total_countries" in summary
        assert "total_regions" in summary
        assert "domestic_unique" in summary
        assert "international_unique" in summary
        assert "domestic_share_pct" in summary
        assert "international_share_pct" in summary
    finally:
        cursor = db_conn.cursor()
        cursor.execute("DELETE FROM users WHERE id = %s", (admin_id,))
        cursor.close()
