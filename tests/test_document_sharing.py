import os
import sys
import json
from pathlib import Path
import pytest
import requests

PROJECT_ROOT = Path(__file__).resolve().parents[1]
if str(PROJECT_ROOT) not in sys.path:
    sys.path.insert(0, str(PROJECT_ROOT))

from worker.database import get_connection, load_env

load_env()
BASE_URL = os.getenv("TEST_BASE_URL", "http://localhost:8085")
VALID_BCRYPT_HASH = "$2y$12$pv/1NIA53w5RZt5n/NCLHOyN5k0/rZqZoW3KUfhPJ4a9dZjA1FghG"  # password123


def test_document_sharing_complete_flow():
    conn = get_connection()
    conn.autocommit = True
    with conn.cursor(dictionary=True) as cur:
        # Bersihkan data uji lama
        cur.execute("DELETE FROM users WHERE email IN ('share_owner@artilik.test', 'share_recip_reg@artilik.test', 'share_recip_new@artilik.test')")
        
        # 1. Buat User A (Owner)
        cur.execute("""
            INSERT INTO users (name, email, password_hash, role, status, week_reset_at, created_at, updated_at)
            VALUES ('Owner User', 'share_owner@artilik.test', %s, 'user', 'active', NOW() + INTERVAL 7 DAY, NOW(), NOW())
        """, (VALID_BCRYPT_HASH,))
        owner_id = cur.lastrowid

        # 2. Buat User B (Penerima yang sudah terdaftar)
        cur.execute("""
            INSERT INTO users (name, email, password_hash, role, status, week_reset_at, created_at, updated_at)
            VALUES ('Recipient B', 'share_recip_reg@artilik.test', %s, 'user', 'active', NOW() + INTERVAL 7 DAY, NOW(), NOW())
        """, (VALID_BCRYPT_HASH,))
        recip_b_id = cur.lastrowid

        # 3. Buat dokumen milik User A
        cur.execute("""
            INSERT INTO documents (user_id, title, original_filename, mime_type, file_size, storage_path, status, document_type, corpus_visibility, is_shared, share_token, created_at, updated_at)
            VALUES (%s, 'Karya Ilmiah Algoritma Similarity', 'skripsi_algoritma.pdf', 'application/pdf', 1024, '/tmp/dummy.pdf', 'completed', 'submission', 'private', 0, NULL, NOW(), NOW())
        """, (owner_id,))
        doc_id = cur.lastrowid

        # 4. Buat laporan hasil uji
        cur.execute("""
            INSERT INTO reports (document_id, engine, predicted_decision, overall_similarity, status, verification_code, created_at, updated_at)
            VALUES (%s, 'hybrid', 'no_match', 12.50, 'completed', 'CERT-TEST-SHARE-123', NOW(), NOW())
        """, (doc_id,))
        report_id = cur.lastrowid

    # Sesi login Owner
    sess_owner = requests.Session()
    sec_token = sess_owner.get(f"{BASE_URL}/auth/security-token").json().get("token")
    login_res = sess_owner.post(f"{BASE_URL}/auth/login", headers={"Accept": "application/json"}, data={
        "email": "share_owner@artilik.test",
        "password": "password123",
        "security_token": sec_token
    })
    assert login_res.status_code == 200, f"Owner login failed: {login_res.text}"

    # Sesi login User B
    sess_b = requests.Session()
    sec_token_b = sess_b.get(f"{BASE_URL}/auth/security-token").json().get("token")
    login_b_res = sess_b.post(f"{BASE_URL}/auth/login", headers={"Accept": "application/json"}, data={
        "email": "share_recip_reg@artilik.test",
        "password": "password123",
        "security_token": sec_token_b
    })
    assert login_b_res.status_code == 200, f"User B login failed: {login_b_res.text}"

    # Sebelum dishare: User B TIDAK boleh bisa membuka report (harus 403 Forbidden)
    unauthorized_res = sess_b.get(f"{BASE_URL}/dashboard/report/{report_id}")
    assert unauthorized_res.status_code == 403, f"Expected 403 before sharing, got {unauthorized_res.status_code}"

    # =========================================================================
    # TEST 1: User A membagikan naskah ke User B (Email Terdaftar)
    # =========================================================================
    share_res = sess_owner.post(f"{BASE_URL}/api/documents/{doc_id}/shares", data={
        "email": "share_recip_reg@artilik.test",
        "permission": "view_all"
    })
    assert share_res.status_code == 200, f"Sharing to User B failed: {share_res.text}"
    share_data = share_res.json()
    assert share_data.get("status") == "success"
    assert share_data.get("is_registered") is True
    assert "share_url" in share_data

    # User B sekarang BISA membuka report!
    authorized_res = sess_b.get(f"{BASE_URL}/dashboard/report/{report_id}")
    assert authorized_res.status_code == 200, f"Expected 200 after sharing, got {authorized_res.status_code}"
    assert "Algoritma Similarity" in authorized_res.text
    assert "Dibagikan" in authorized_res.text

    # User B membuka dashboard dan naskah muncul di tab "Dibagikan dengan Saya"
    dash_b_res = sess_b.get(f"{BASE_URL}/dashboard")
    assert dash_b_res.status_code == 200
    assert "Dibagikan dengan Saya" in dash_b_res.text
    assert "Algoritma Similarity" in dash_b_res.text
    assert "Owner User" in dash_b_res.text

    # =========================================================================
    # TEST 2: User A membagikan ke User C (Belum Terdaftar)
    # =========================================================================
    new_email = "share_recip_new@artilik.test"
    share_c_res = sess_owner.post(f"{BASE_URL}/api/documents/{doc_id}/shares", data={
        "email": new_email,
        "permission": "view_all"
    })
    assert share_c_res.status_code == 200
    share_c_data = share_c_res.json()
    assert share_c_data.get("is_registered") is False
    assert "harus mendaftar akun AR Tilik terlebih dahulu" in share_c_data.get("message", "")

    # Cek di database: status harus 'pending_registration'
    with conn.cursor(dictionary=True) as cur:
        cur.execute("SELECT * FROM document_shares WHERE recipient_email = %s", (new_email,))
        share_c_row = cur.fetchone()
        assert share_c_row is not None
        assert share_c_row["status"] == "pending_registration"
        assert share_c_row["shared_with_user_id"] is None

    # Sekarang User C mendaftar akun AR Tilik & diaktifkan
    with conn.cursor(dictionary=True) as cur:
        cur.execute("""
            INSERT INTO users (name, email, password_hash, role, status, week_reset_at, created_at, updated_at)
            VALUES ('User C New', %s, %s, 'user', 'active', NOW() + INTERVAL 7 DAY, NOW(), NOW())
        """, (new_email, VALID_BCRYPT_HASH))
        user_c_id = cur.lastrowid

    # User C login
    sess_c = requests.Session()
    sec_token_c = sess_c.get(f"{BASE_URL}/auth/security-token").json().get("token")
    login_c_res = sess_c.post(f"{BASE_URL}/auth/login", headers={"Accept": "application/json"}, data={
        "email": new_email,
        "password": "password123",
        "security_token": sec_token_c
    })
    assert login_c_res.status_code == 200

    # Setelah login, setUserSession otomatis mengklaim pending share!
    with conn.cursor(dictionary=True) as cur:
        cur.execute("SELECT * FROM document_shares WHERE recipient_email = %s", (new_email,))
        claimed_row = cur.fetchone()
        assert claimed_row is not None
        assert claimed_row["status"] == "active"
        assert claimed_row["shared_with_user_id"] == user_c_id

    # User C sekarang bisa langsung membuka report dan melihat di dashboard
    rep_c_res = sess_c.get(f"{BASE_URL}/dashboard/report/{report_id}")
    assert rep_c_res.status_code == 200
    assert "Algoritma Similarity" in rep_c_res.text

    # =========================================================================
    # TEST 3: Shareable Link dibuka oleh Tamu Belum Login (harus redirect ke register)
    # =========================================================================
    with conn.cursor(dictionary=True) as cur:
        cur.execute("SELECT share_token FROM documents WHERE id = %s", (doc_id,))
        doc_token = cur.fetchone()["share_token"]
        assert doc_token is not None

    sess_guest = requests.Session()
    guest_link_res = sess_guest.get(f"{BASE_URL}/documents/shared/{doc_token}", allow_redirects=False)
    # Harus 302 redirect ke register landing page
    assert guest_link_res.status_code == 302
    redirect_loc = guest_link_res.headers.get("Location", "")
    assert "auth=register" in redirect_loc
    assert doc_token in redirect_loc

    # =========================================================================
    # TEST 4: Pencabutan Hak Akses (Revoke)
    # =========================================================================
    # Ambil share_id untuk User B
    with conn.cursor(dictionary=True) as cur:
        cur.execute("SELECT id FROM document_shares WHERE document_id = %s AND shared_with_user_id = %s", (doc_id, recip_b_id))
        b_share_id = cur.fetchone()["id"]

    revoke_res = sess_owner.delete(f"{BASE_URL}/api/documents/{doc_id}/shares/{b_share_id}")
    assert revoke_res.status_code == 200
    assert revoke_res.json().get("status") == "success"

    # Setelah dicabut, User B kembali mendapatkan 403 Forbidden
    denied_again = sess_b.get(f"{BASE_URL}/dashboard/report/{report_id}")
    assert denied_again.status_code == 403

    # =========================================================================
    # TEST 5: Proteksi Hak Akses (User B tidak boleh membagikan naskah User A)
    # =========================================================================
    illegal_share_res = sess_b.post(f"{BASE_URL}/api/documents/{doc_id}/shares", data={
        "email": "another@test.com"
    })
    assert illegal_share_res.status_code == 403

    conn.close()
    print("\n✅ SEMUA PENGUJIAN FITUR SHARING (MODEL A + B) BERHASIL DENGAN SEMPURNA!")
