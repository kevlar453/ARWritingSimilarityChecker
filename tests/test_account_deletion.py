"""
AR Tilik - Automated Test Suite for Account Deletion Feature (Fitur Penghapusan Akun)
Menguji penghapusan akun oleh Administrator dan penghapusan akun mandiri oleh Pengguna (Right to Erasure / UU PDP).
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


def test_admin_delete_user_lifecycle(db_conn):
    """
    Menguji pemusnahan akun pengguna oleh Administrator melalui endpoint /admin/users/delete.
    Memverifikasi seluruh relasi (dokumen, chunks, laporan, transaksi, API key) terhapus bersih.
    """
    cursor = db_conn.cursor(dictionary=True)

    # 1. Daftarkan akun admin baru untuk tes ini
    admin_hex = uuid.uuid4().hex[:8]
    admin_email = f"admin_del_{admin_hex}@kampus.ac.id"
    admin_pwd = "AdminSecurePassword123!"

    admin_session = requests.Session()
    reg_adm = admin_session.post(f"{BASE_URL}/auth/register", json={
        "name": f"Admin Test {admin_hex}",
        "email": admin_email,
        "password": admin_pwd,
        "institution": "Kampus Administrator",
        "security_token": "TEST_TOKEN"
    }, headers={"Accept": "application/json"})
    assert reg_adm.status_code == 201

    # Upgrade akun ini menjadi admin dan aktifkan
    cursor.execute(
        "UPDATE users SET role = 'admin', status = 'active', email_verified_at = NOW() WHERE email = %s",
        (admin_email,)
    )
    cursor.execute("SELECT id FROM users WHERE email = %s", (admin_email,))
    admin_id = cursor.fetchone()['id']

    # Login admin
    login_adm = admin_session.post(f"{BASE_URL}/auth/login", json={
        "email": admin_email,
        "password": admin_pwd
    }, headers={"X-Requested-With": "XMLHttpRequest"})
    assert login_adm.status_code == 200

    # 2. Daftarkan akun target pengguna biasa
    target_hex = uuid.uuid4().hex[:8]
    target_email = f"target_del_{target_hex}@kampus.ac.id"
    target_pwd = "TargetPassword123!"

    target_session = requests.Session()
    reg_tgt = target_session.post(f"{BASE_URL}/auth/register", json={
        "name": f"Target User {target_hex}",
        "email": target_email,
        "password": target_pwd,
        "institution": "Institut Uji Hapus",
        "security_token": "TEST_TOKEN"
    }, headers={"Accept": "application/json"})
    assert reg_tgt.status_code == 201

    cursor.execute("UPDATE users SET status = 'active', email_verified_at = NOW() WHERE email = %s", (target_email,))
    cursor.execute("SELECT id FROM users WHERE email = %s", (target_email,))
    target_user_id = cursor.fetchone()['id']

    # 3. Buat dokumen & laporan untuk target user
    cursor.execute(
        """
        INSERT INTO documents (user_id, title, original_filename, mime_type, file_size, storage_path, document_type, status, created_at, updated_at)
        VALUES (%s, %s, %s, 'application/pdf', 1024, '/tmp/dummy_test_del.pdf', 'submission', 'completed', NOW(), NOW())
        """,
        (target_user_id, "Skripsi Target Uji Hapus", "skripsi.pdf")
    )
    doc_id = cursor.lastrowid

    cursor.execute(
        """
        INSERT INTO reports (document_id, overall_similarity, created_at, updated_at)
        VALUES (%s, 15.5, NOW(), NOW())
        """,
        (doc_id,)
    )
    report_id = cursor.lastrowid

    cursor.execute(
        """
        INSERT INTO document_pages (document_id, page_number, text, created_at)
        VALUES (%s, 1, 'Halaman 1 uji coba.', NOW())
        """,
        (doc_id,)
    )
    page_id = cursor.lastrowid

    cursor.execute(
        """
        INSERT INTO document_chunks (document_id, page_id, chunk_index, text, normalized_text, word_count, start_word_position, end_word_position, created_at)
        VALUES (%s, %s, 0, 'Teks paragraf uji coba.', 'teks paragraf uji coba', 4, 1, 4, NOW())
        """,
        (doc_id, page_id)
    )

    cursor.execute(
        """
        INSERT INTO api_keys (user_id, key_name, key_prefix, api_key, is_active, created_at, updated_at)
        VALUES (%s, 'Test Key Target', 'art_test', %s, 1, NOW(), NOW())
        """,
        (target_user_id, f"hash_key_{target_hex}")
    )

    # 4. Admin mencoba menghapus akun dirinya sendiri -> Harus Ditolak (400)
    self_del_res = admin_session.post(f"{BASE_URL}/admin/users/delete", data={
        "user_id": admin_id
    }, headers={"X-Requested-With": "XMLHttpRequest"})
    assert self_del_res.status_code == 400
    assert "tidak dapat menghapus akun administrator anda sendiri" in self_del_res.json()["message"].lower()

    # 5. Admin menghapus akun target user via /admin/users/delete -> Harus Sukses (200)
    del_res = admin_session.post(f"{BASE_URL}/admin/users/delete", data={
        "user_id": target_user_id
    }, headers={"X-Requested-With": "XMLHttpRequest"})
    assert del_res.status_code == 200, f"Gagal hapus user: {del_res.text}"
    del_json = del_res.json()
    assert del_json["status"] == "success"
    assert "berhasil dimusnahkan secara permanen" in del_json["message"].lower()

    # 6. Verifikasi pemusnahan total di database
    cursor.execute("SELECT id FROM users WHERE id = %s", (target_user_id,))
    assert cursor.fetchone() is None, "Pengguna target masih ada di tabel users!"

    cursor.execute("SELECT id FROM documents WHERE user_id = %s", (target_user_id,))
    assert cursor.fetchone() is None, "Dokumen pengguna masih ada di tabel documents!"

    cursor.execute("SELECT id FROM reports WHERE id = %s", (report_id,))
    assert cursor.fetchone() is None, "Laporan dokumen masih ada di tabel reports!"

    cursor.execute("SELECT id FROM document_chunks WHERE document_id = %s", (doc_id,))
    assert cursor.fetchone() is None, "Chunks dokumen masih ada di tabel document_chunks!"

    cursor.execute("SELECT id FROM api_keys WHERE user_id = %s", (target_user_id,))
    assert cursor.fetchone() is None, "API Key masih ada di tabel api_keys!"

    # Bersihkan akun admin uji coba
    cursor.execute("DELETE FROM users WHERE id = %s", (admin_id,))


def test_user_self_deletion_lifecycle(db_conn):
    """
    Menguji fitur penghapusan akun mandiri oleh pengguna melalui /auth/delete-account.
    Mengharuskan validasi kata sandi sebelum pemusnahan data.
    """
    cursor = db_conn.cursor(dictionary=True)
    random_hex = uuid.uuid4().hex[:8]
    user_email = f"self_delete_{random_hex}@kampus.ac.id"
    user_password = "PasswordRahasia123!"

    user_session = requests.Session()
    reg_res = user_session.post(f"{BASE_URL}/auth/register", json={
        "name": f"Self Delete User {random_hex}",
        "email": user_email,
        "password": user_password,
        "institution": "Institut Self Delete",
        "security_token": "TEST_TOKEN"
    }, headers={"Accept": "application/json"})
    assert reg_res.status_code == 201

    cursor.execute("UPDATE users SET status = 'active', email_verified_at = NOW() WHERE email = %s", (user_email,))
    cursor.execute("SELECT id FROM users WHERE email = %s", (user_email,))
    user_id = cursor.fetchone()['id']

    # Tambahkan dokumen milik user ini
    cursor.execute(
        """
        INSERT INTO documents (user_id, title, original_filename, mime_type, file_size, storage_path, document_type, status, created_at, updated_at)
        VALUES (%s, %s, %s, 'application/pdf', 1024, '/tmp/dummy_test_del_user.pdf', 'submission', 'completed', NOW(), NOW())
        """,
        (user_id, "Karya Ilmiah Mandiri", "karya.docx")
    )
    doc_id = cursor.lastrowid

    # 1. Login sebagai user
    login_res = user_session.post(f"{BASE_URL}/auth/login", json={
        "email": user_email,
        "password": user_password
    }, headers={"X-Requested-With": "XMLHttpRequest"})
    assert login_res.status_code == 200

    # 2. Coba hapus akun dengan kata sandi salah -> Harus Ditolak (401)
    wrong_pwd_res = user_session.post(f"{BASE_URL}/auth/delete-account", json={
        "password": "WrongPassword123!"
    }, headers={"X-Requested-With": "XMLHttpRequest"})
    assert wrong_pwd_res.status_code == 401
    assert "tidak sesuai" in wrong_pwd_res.json()["message"].lower()

    # Pastikan user masih ada
    cursor.execute("SELECT id FROM users WHERE id = %s", (user_id,))
    assert cursor.fetchone() is not None

    # 3. Hapus akun dengan kata sandi benar -> Sukses (200)
    correct_pwd_res = user_session.post(f"{BASE_URL}/auth/delete-account", json={
        "password": user_password
    }, headers={"X-Requested-With": "XMLHttpRequest"})
    assert correct_pwd_res.status_code == 200
    res_data = correct_pwd_res.json()
    assert res_data["status"] == "success"
    assert "berhasil dimusnahkan secara permanen" in res_data["message"].lower()

    # 4. Verifikasi bahwa user dan dokumennya terhapus bersih dari database
    cursor.execute("SELECT id FROM users WHERE id = %s", (user_id,))
    assert cursor.fetchone() is None, "Pengguna masih tersisa di database!"

    cursor.execute("SELECT id FROM documents WHERE id = %s", (doc_id,))
    assert cursor.fetchone() is None, "Dokumen pengguna masih tersisa di database!"

    # 5. Verifikasi sesi sudah hancur (status unauthenticated)
    status_res = user_session.get(f"{BASE_URL}/auth/status")
    assert status_res.status_code == 200
    assert status_res.json().get("authenticated") is False
