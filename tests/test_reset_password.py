"""
AR Tilik - Automated Test Suite for Reset Password Feature
Menguji permohonan atur ulang kata sandi (forgot password), pembuatan token kriptografis,
validasi keamanan (anti-enumeration, rate limit, honeypot), halaman reset password,
dan penggantian kata sandi serta login dengan kata sandi baru.
"""

import os
import sys
import uuid
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


def test_forgot_password_validation():
    """
    Menguji validasi input permohonan atur ulang kata sandi:
    - Email tidak valid formatnya
    - Honeypot bot terpicu
    - Email yang tidak terdaftar (anti-enumeration response)
    """
    session = requests.Session()

    # 1. Format email tidak valid
    res_bad = session.post(f"{BASE_URL}/auth/forgot-password", json={
        "email": "invalid-email-format"
    }, headers={"Accept": "application/json"})
    assert res_bad.status_code == 400
    assert "valid" in res_bad.json()["message"].lower()

    # 2. Jebakan Honeypot bot
    res_bot = session.post(f"{BASE_URL}/auth/forgot-password", json={
        "email": "bot@kampus.ac.id",
        "website_hp": "http://spambot.com"
    }, headers={"Accept": "application/json"})
    assert res_bot.status_code == 400
    assert "tidak sah" in res_bot.json()["message"].lower()

    # 3. Email tidak terdaftar -> Respons aman standar industri agar tidak membocorkan enumerasi user
    fake_email = f"unknown_{uuid.uuid4().hex[:8]}@kampus.ac.id"
    res_unknown = session.post(f"{BASE_URL}/auth/forgot-password", json={
        "email": fake_email
    }, headers={"Accept": "application/json"})
    assert res_unknown.status_code == 200
    assert res_unknown.json()["status"] == "success"


def test_full_reset_password_lifecycle(db_conn):
    """
    Siklus lengkap alur Atur Ulang Kata Sandi (Password Reset Lifecycle):
    1. Daftarkan pengguna uji
    2. Ajukan permohonan atur ulang kata sandi (forgot-password)
    3. Periksa token kriptografis dan masa berlaku di basis data
    4. Uji akses GET reset-password dengan token tidak sah (harus redirect error)
    5. Uji akses GET reset-password dengan token sah (harus return 200 OK & form)
    6. Uji submit kata sandi tidak cocok / terlalu pendek (harus ditolak 400)
    7. Uji submit kata sandi baru yang valid (harus berhasil 200)
    8. Verifikasi pembersihan token di basis data dan aktivasi akun
    9. Login dengan kata sandi lama harus gagal (401)
    10. Login dengan kata sandi baru harus berhasil (200)
    11. Mencoba gunakan kembali token yang sama harus ditolak (400)
    """
    session = requests.Session()
    random_suffix = uuid.uuid4().hex[:8]
    test_email = f"peneliti_{random_suffix}@ugm.ac.id"
    old_password = "PasswordLama2026!"
    new_password = "PasswordBaruTeruji2026!"
    user_name = f"Peneliti Reset {random_suffix}"

    user_id = None

    try:
        # Step 1: Registrasi user
        res_reg = session.post(f"{BASE_URL}/auth/register", json={
            "name": user_name,
            "email": test_email,
            "password": old_password,
            "institution": "Universitas Gadjah Mada",
            "security_token": "TEST_TOKEN"
        }, headers={"Accept": "application/json"})
        assert res_reg.status_code == 201
        user_id = int(res_reg.json()["user"]["id"])

        # Step 2: Permohonan Atur Ulang Kata Sandi
        res_forgot = session.post(f"{BASE_URL}/auth/forgot-password", json={
            "email": test_email
        }, headers={"Accept": "application/json"})
        assert res_forgot.status_code == 200
        forgot_data = res_forgot.json()
        assert forgot_data["status"] == "success"
        assert forgot_data.get("email_sent") is True

        # Step 3: Verifikasi keberadaan token di DB
        cursor = db_conn.cursor()
        cursor.execute(
            "SELECT reset_password_token, reset_password_expires_at, status FROM users WHERE id = %s",
            (user_id,)
        )
        row = cursor.fetchone()
        cursor.close()

        assert row is not None
        reset_token = row[0]
        expires_at = row[1]
        assert reset_token is not None and len(reset_token) >= 32
        assert expires_at is not None

        # Step 4: GET dengan token palsu
        res_fake_token = session.get(
            f"{BASE_URL}/auth/reset-password?token=invalid_dummy_token_123",
            allow_redirects=False
        )
        assert res_fake_token.status_code in [302, 307]
        assert "reset=error" in res_fake_token.headers.get("Location", "")

        # Step 5: GET dengan token sah
        res_valid_page = session.get(f"{BASE_URL}/auth/reset-password?token={reset_token}")
        assert res_valid_page.status_code == 200
        assert "Atur Ulang Kata Sandi" in res_valid_page.text
        assert "Kata Sandi Baru" in res_valid_page.text

        # Step 6a: Validasi Password Mismatch
        res_mismatch = session.post(f"{BASE_URL}/auth/reset-password", json={
            "token": reset_token,
            "password": new_password,
            "password_confirm": "MismatchPassword999"
        }, headers={"Accept": "application/json"})
        assert res_mismatch.status_code == 400
        assert "tidak cocok" in res_mismatch.json()["message"].lower()

        # Step 6b: Validasi Password terlalu pendek (< 6)
        res_short = session.post(f"{BASE_URL}/auth/reset-password", json={
            "token": reset_token,
            "password": "123",
            "password_confirm": "123"
        }, headers={"Accept": "application/json"})
        assert res_short.status_code == 400
        msg_lower = res_short.json()["message"].lower()
        assert "minimal 8 karakter" in msg_lower or "minimal 6 karakter" in msg_lower

        # Step 7: Submit Password Baru yang Valid
        res_reset_success = session.post(f"{BASE_URL}/auth/reset-password", json={
            "token": reset_token,
            "password": new_password,
            "password_confirm": new_password
        }, headers={"Accept": "application/json"})
        assert res_reset_success.status_code == 200
        assert res_reset_success.json()["status"] == "success"

        # Step 8: Periksa basis data - token harus NULL dan status active
        cursor = db_conn.cursor()
        cursor.execute(
            "SELECT reset_password_token, reset_password_expires_at, status, email_verified_at FROM users WHERE id = %s",
            (user_id,)
        )
        updated_row = cursor.fetchone()
        cursor.close()

        assert updated_row[0] is None
        assert updated_row[1] is None
        assert updated_row[2] == "active"
        assert updated_row[3] is not None

        # Step 9: Login dengan password lama harus ditolak
        res_login_old = session.post(f"{BASE_URL}/auth/login", json={
            "email": test_email,
            "password": old_password
        }, headers={"Accept": "application/json"})
        assert res_login_old.status_code == 401

        # Step 10: Login dengan password baru harus berhasil
        res_login_new = session.post(f"{BASE_URL}/auth/login", json={
            "email": test_email,
            "password": new_password
        }, headers={"Accept": "application/json"})
        assert res_login_new.status_code == 200
        login_data = res_login_new.json()
        assert login_data["status"] == "success"
        assert int(login_data["user"]["id"]) == user_id

        # Step 11: Coba gunakan kembali token yang sudah dipakai
        res_reuse = session.post(f"{BASE_URL}/auth/reset-password", json={
            "token": reset_token,
            "password": "AnotherNewPassword2026!",
            "password_confirm": "AnotherNewPassword2026!"
        }, headers={"Accept": "application/json"})
        assert res_reuse.status_code == 400

    finally:
        if user_id:
            cursor = db_conn.cursor()
            cursor.execute("DELETE FROM users WHERE id = %s", (user_id,))
            cursor.close()
