import pytest
import requests
import uuid
import mysql.connector

BASE_URL = "http://localhost:8085"

@pytest.fixture(scope="module")
def db_conn():
    conn = mysql.connector.connect(
        host="127.0.0.1",
        port=3306,
        user="ymakarius",
        password="Segalang$$65216",
        database="db_artilik"
    )
    conn.autocommit = True
    yield conn
    conn.close()


def test_admin_portal_guest_protection():
    """
    Tamu (belum login) yang mengakses portal admin harus dialihkan ke login,
    dan request API harus ditolak 401 Unauthorized.
    """
    guest_session = requests.Session()

    # 1. GET /admin -> redirect ke login
    res_page = guest_session.get(f"{BASE_URL}/admin", allow_redirects=False)
    assert res_page.status_code in (302, 307)
    assert "auth=login" in res_page.headers.get("Location", "")

    # 2. POST /admin/users/update tanpa login -> 401
    res_api = guest_session.post(f"{BASE_URL}/admin/users/update", data={
        "user_id": 999,
        "action": "adjust_quota",
        "bonus_submissions": 5
    }, headers={"Accept": "application/json"})
    assert res_api.status_code == 401
    assert "unauthorized" in res_api.json()["status"]


def test_admin_portal_regular_user_forbidden(db_conn):
    """
    Pengguna biasa (role='user') tidak boleh mengakses portal admin (403 / redirect ke dashboard).
    """
    session = requests.Session()
    random_str = uuid.uuid4().hex[:8]
    user_email = f"user_{random_str}@kampus.ac.id"
    user_password = "password12345"

    # Gunakan password hash yang cocok untuk 'testpassword'
    pw_hash = "$2y$12$9zpp2JotUGfsa0PuPEytf.zzILhmnywPLVYWwe90o8zk4XrrzBJxm"
    user_password = "testpassword"

    cursor = db_conn.cursor()
    # Buat user biasa aktif
    cursor.execute("""
        INSERT INTO users (name, email, password_hash, institution, role, status, email_verified_at, week_reset_at, created_at, updated_at)
        VALUES (%s, %s, %s, %s, 'user', 'active', NOW(), DATE_ADD(NOW(), INTERVAL 7 DAY), NOW(), NOW())
    """, (f"User Biasa {random_str}", user_email, pw_hash, "Univ Test"))
    user_id = cursor.lastrowid
    cursor.close()

    try:

        # Login sebagai user biasa
        res_login = session.post(f"{BASE_URL}/auth/login", json={
            "email": user_email,
            "password": user_password
        }, headers={"Accept": "application/json"})
        assert res_login.status_code == 200

        # Akses GET /admin -> dilarang (redirect ke dashboard)
        res_admin = session.get(f"{BASE_URL}/admin", allow_redirects=False)
        assert res_admin.status_code in (302, 307)
        assert "dashboard" in res_admin.headers.get("Location", "")

        # Akses POST /admin/users/update -> ditolak 403 Forbidden
        res_post = session.post(f"{BASE_URL}/admin/users/update", data={
            "user_id": user_id,
            "action": "adjust_quota",
            "bonus_submissions": 5
        }, headers={"Accept": "application/json"})
        assert res_post.status_code == 403
        assert "forbidden" in res_post.json()["status"]

    finally:
        # Cleanup
        cursor = db_conn.cursor()
        cursor.execute("DELETE FROM users WHERE id = %s", (user_id,))
        cursor.close()


def test_admin_portal_admin_user_access_and_management(db_conn):
    """
    Pengguna dengan role='admin' dapat mengakses Portal Admin dan melakukan
    manajemen pengguna, kuota, serta keamanan.
    """
    session = requests.Session()
    random_str = uuid.uuid4().hex[:8]
    admin_email = f"admin_{random_str}@artilik.id"
    admin_password = "testpassword"
    pw_hash = "$2y$12$9zpp2JotUGfsa0PuPEytf.zzILhmnywPLVYWwe90o8zk4XrrzBJxm"

    # Buat pengguna target untuk diuji perubahannya
    target_email = f"target_{random_str}@kampus.ac.id"
    cursor = db_conn.cursor()

    # Insert Admin
    cursor.execute("""
        INSERT INTO users (name, email, password_hash, institution, role, status, email_verified_at, week_reset_at, created_at, updated_at)
        VALUES (%s, %s, %s, 'AR Tilik Core', 'admin', 'active', NOW(), DATE_ADD(NOW(), INTERVAL 7 DAY), NOW(), NOW())
    """, (f"Super Admin {random_str}", admin_email, pw_hash))
    admin_id = cursor.lastrowid

    # Insert Target User
    cursor.execute("""
        INSERT INTO users (name, email, password_hash, institution, role, status, email_verified_at, free_submissions_used_this_week, bonus_submissions, week_reset_at, created_at, updated_at)
        VALUES (%s, %s, %s, 'Institut Teknologi Bandung', 'user', 'pending_verification', NULL, 3, 0, DATE_ADD(NOW(), INTERVAL 7 DAY), NOW(), NOW())
    """, (f"Target User {random_str}", target_email, pw_hash))
    target_id = cursor.lastrowid

    # Insert Dummy Security Lock Record
    cursor.execute("""
        INSERT INTO auth_rate_limits (ip_address, action_type, identifier, attempts, first_attempt_at, last_attempt_at, locked_until)
        VALUES ('192.168.1.99', 'login', %s, 5, NOW(), NOW(), DATE_ADD(NOW(), INTERVAL 15 MINUTE))
    """, (target_email,))
    lock_id = cursor.lastrowid
    cursor.close()

    try:
        # 1. Login sebagai Admin
        res_login = session.post(f"{BASE_URL}/auth/login", json={
            "email": admin_email,
            "password": admin_password
        }, headers={"Accept": "application/json"})
        assert res_login.status_code == 200

        # 2. Akses Dashboard Admin (Overview Tab)
        res_dash = session.get(f"{BASE_URL}/admin?tab=overview")
        assert res_dash.status_code == 200
        html = res_dash.text
        assert "Portal Admin" in html
        assert "Total Pengguna" in html
        assert "Submisi Dokumen" in html
        assert "Worker Queue" in html

        # 3. Akses Tab Pengguna
        res_users_tab = session.get(f"{BASE_URL}/admin?tab=users")
        assert res_users_tab.status_code == 200
        assert target_email in res_users_tab.text

        # 4. Aksi Admin: Verifikasi Email Manual
        res_verify = session.post(f"{BASE_URL}/admin/users/update", data={
            "user_id": target_id,
            "action": "verify_email"
        }, headers={"Accept": "application/json"})
        assert res_verify.status_code == 200
        assert res_verify.json()["status"] == "success"

        # 5. Aksi Admin: Tambah Bonus Kuota
        res_quota = session.post(f"{BASE_URL}/admin/users/update", data={
            "user_id": target_id,
            "action": "adjust_quota",
            "bonus_submissions": 5
        }, headers={"Accept": "application/json"})
        assert res_quota.status_code == 200
        assert res_quota.json()["user"]["bonus_submissions"] == "5" or res_quota.json()["user"]["bonus_submissions"] == 5

        # 6. Aksi Admin: Reset Kuota Mingguan
        res_reset = session.post(f"{BASE_URL}/admin/users/update", data={
            "user_id": target_id,
            "action": "reset_weekly_quota"
        }, headers={"Accept": "application/json"})
        assert res_reset.status_code == 200
        assert str(res_reset.json()["user"]["free_submissions_used_this_week"]) == "0"

        # 7. Akses Tab Keamanan
        res_sec_tab = session.get(f"{BASE_URL}/admin?tab=security")
        assert res_sec_tab.status_code == 200
        assert "192.168.1.99" in res_sec_tab.text

        # 8. Aksi Admin: Buka Kunci (Unblock)
        res_unblock = session.post(f"{BASE_URL}/admin/security/unblock", data={
            "id": lock_id
        }, headers={"Accept": "application/json"})
        assert res_unblock.status_code == 200
        assert res_unblock.json()["status"] == "success"

        # Verifikasi row unblock terhapus di database
        cursor = db_conn.cursor()
        cursor.execute("SELECT id FROM auth_rate_limits WHERE id = %s", (lock_id,))
        assert cursor.fetchone() is None
        cursor.close()

    finally:
        # Cleanup
        cursor = db_conn.cursor()
        cursor.execute("DELETE FROM users WHERE id IN (%s, %s)", (admin_id, target_id))
        cursor.execute("DELETE FROM auth_rate_limits WHERE id = %s", (lock_id,))
        cursor.close()
