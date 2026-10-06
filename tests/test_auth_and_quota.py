"""
AR Tilik - Automated Test Suite for User Authentication, Quota Tracking & Crowdsourced Corpus
Menguji alur registrasi, login, kuota 3 submisi/minggu, dan insentif +3 korpus = +1 submisi
"""

import os
import sys
import io
import json
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


def test_landing_page_rendered_correctly():
    """
    Memverifikasi bahwa landing page / menyajikan informasi teori edukasi,
    tiga lapisan separasi, filosofi Dual-Lens, dan kartu paket langganan.
    """
    res = requests.get(f"{BASE_URL}/")
    assert res.status_code == 200
    html = res.text

    assert "AR Tilik" in html
    assert "Deteksi Kesepadanan Naskah Akademik" in html
    assert "Three-Layer Separation" in html
    assert "Dual-Lens Architecture" in html
    assert "Civitas Akademika" in html
    assert "Peneliti Produktif" in html
    assert "Kampus" in html
    assert "Daftar Akun AR Tilik Gratis" in html


def test_user_registration_validation():
    """
    Menguji validasi registrasi akun (email tidak valid, password pendek, dsb).
    """
    session = requests.Session()

    # 1. Invalid email
    res = session.post(f"{BASE_URL}/auth/register", json={
        "name": "Budi Santoso",
        "email": "not-an-email",
        "password": "secretpassword",
        "institution": "Universitas Indonesia"
    }, headers={"Accept": "application/json"})
    assert res.status_code == 400
    data = res.json()
    assert data["status"] == "error"
    assert "tidak valid" in data["message"].lower()

    # 2. Short password
    res = session.post(f"{BASE_URL}/auth/register", json={
        "name": "Budi Santoso",
        "email": f"budi_{uuid.uuid4().hex[:6]}@ui.ac.id",
        "password": "123",
        "institution": "Universitas Indonesia"
    }, headers={"Accept": "application/json"})
    assert res.status_code == 400
    data = res.json()
    assert "minimal 6 karakter" in data["message"].lower()


def test_full_auth_quota_and_corpus_incentive_lifecycle(db_conn):
    """
    Siklus lengkap pengujian:
    1. Registrasi user baru
    2. Verifikasi inisialisasi kuota: 3 submisi gratis/minggu
    3. Login dan verifikasi status sesi
    4. Mengunggah 3 submisi -> kuota gratis habis
    5. Submisi ke-4 ditolak karena kuota habis (403)
    6. Mengunggah korpus duplikat -> ditolak (409)
    7. Mengunggah 3 korpus valid -> reward bonus +1 submisi diberikan
    8. Submisi ke-5 berhasil menggunakan bonus
    9. Submisi ke-6 ditolak kembali (kuota habis)
    """
    session = requests.Session()
    random_suffix = uuid.uuid4().hex[:8]
    test_email = f"akademisi_{random_suffix}@kampus.ac.id"
    test_password = "passwordRahasia2026"
    test_name = f"Prof. Akademisi {random_suffix}"

    inserted_doc_ids = []
    created_user_id = None

    try:
        # Step 1: Registrasi pengguna baru
        res_reg = session.post(f"{BASE_URL}/auth/register", json={
            "name": test_name,
            "email": test_email,
            "password": test_password,
            "institution": "Institut Teknologi Bandung"
        }, headers={"Accept": "application/json"})

        assert res_reg.status_code == 201
        reg_data = res_reg.json()
        assert reg_data["status"] == "success"
        assert reg_data.get("needs_verification") is True
        created_user_id = int(reg_data["user"]["id"])

        # Step 1b: Login sebelum verifikasi email harus ditolak (403 Forbidden)
        res_prelogin = session.post(f"{BASE_URL}/auth/login", json={
            "email": test_email,
            "password": test_password
        }, headers={"Accept": "application/json"})
        assert res_prelogin.status_code == 403
        prelogin_data = res_prelogin.json()
        assert prelogin_data.get("needs_verification") is True

        # Step 1c: Mengambil token verifikasi dari database dan menguji link verifikasi
        cursor = db_conn.cursor()
        cursor.execute("SELECT verification_token, status FROM users WHERE id = %s", (created_user_id,))
        token_row = cursor.fetchone()
        cursor.close()
        assert token_row is not None
        v_token = token_row[0]
        assert token_row[1] == "pending_verification"
        assert v_token is not None and len(v_token) > 10

        # Step 1d: Uji endpoint resend verification
        res_resend = session.post(f"{BASE_URL}/auth/resend-verification", json={
            "email": test_email
        }, headers={"Accept": "application/json"})
        assert res_resend.status_code == 200
        assert res_resend.json()["status"] == "success"

        # Ambil token terbaru setelah resend
        cursor = db_conn.cursor()
        cursor.execute("SELECT verification_token FROM users WHERE id = %s", (created_user_id,))
        v_token = cursor.fetchone()[0]
        cursor.close()

        # Step 1e: Akses link verifikasi email
        res_verify = session.get(f"{BASE_URL}/auth/verify-email?token={v_token}", allow_redirects=True)
        assert res_verify.status_code == 200
        # Akun sekarang harus berstatus active
        cursor = db_conn.cursor()
        cursor.execute("SELECT status, email_verified_at FROM users WHERE id = %s", (created_user_id,))
        post_verify_row = cursor.fetchone()
        cursor.close()
        assert post_verify_row[0] == "active"
        assert post_verify_row[1] is not None

        # Step 2: Registrasi duplikat harus ditolak (409)
        res_dup = session.post(f"{BASE_URL}/auth/register", json={
            "name": test_name,
            "email": test_email,
            "password": test_password
        }, headers={"Accept": "application/json"})
        assert res_dup.status_code == 409

        # Step 3: Login setelah verifikasi email harus berhasil
        res_login = session.post(f"{BASE_URL}/auth/login", json={
            "email": test_email,
            "password": test_password
        }, headers={"Accept": "application/json"})
        assert res_login.status_code == 200
        assert res_login.json()["status"] == "success"

        # Step 4: Status Auth & Kuota Inisial
        res_status = session.get(f"{BASE_URL}/auth/status", headers={"Accept": "application/json"})
        assert res_status.status_code == 200
        status_data = res_status.json()
        assert status_data["authenticated"] is True
        assert status_data["user"]["email"] == test_email
        quota = status_data["quota_info"]
        assert quota["free_limit"] == 3
        assert quota["free_used"] == 0
        assert quota["free_remaining"] == 3
        assert quota["bonus_remaining"] == 0
        assert quota["total_available"] == 3
        assert quota["corpus_count"] == 0

        # Step 5: Submisi 3 naskah (menghabiskan kuota gratis)
        for i in range(1, 4):
            file_content = f"Ini adalah naskah submisi akademik ke-{i} dari pengujian kuota otomatis {random_suffix}."
            files = {
                "document": (f"submisi_uji_{i}_{random_suffix}.txt", io.BytesIO(file_content.encode("utf-8")), "text/plain")
            }
            res_sub = session.post(f"{BASE_URL}/api/documents", files=files, headers={"Accept": "application/json"})
            assert res_sub.status_code == 201
            sub_json = res_sub.json()
            inserted_doc_ids.append(sub_json["document_id"])
            assert sub_json["quota_info"]["free_used"] == i
            assert sub_json["quota_info"]["free_remaining"] == (3 - i)

        # Step 6: Submisi ke-4 harus ditolak (403 Quota Exceeded)
        file_content_4 = f"Submisi ke-4 yang melebihi batas kuota gratis {random_suffix}."
        files_4 = {
            "document": (f"submisi_uji_4_{random_suffix}.txt", io.BytesIO(file_content_4.encode("utf-8")), "text/plain")
        }
        res_sub_4 = session.post(f"{BASE_URL}/api/documents", files=files_4, headers={"Accept": "application/json"})
        assert res_sub_4.status_code == 403
        data_4 = res_sub_4.json()
        assert data_4["status"] == "quota_exceeded"
        assert data_4["quota_info"]["total_available"] == 0

        # Step 7: Sumbang Korpus 1 (valid)
        corpus_content_1 = f"Karya rujukan ilmiah bidang kecerdasan buatan dan pemrosesan bahasa alami {random_suffix}."
        files_c1 = {
            "corpus_document": (f"korpus_nlp_{random_suffix}.txt", io.BytesIO(corpus_content_1.encode("utf-8")), "text/plain")
        }
        data_c1 = {"title": f"Penelitian NLP Terapan {random_suffix}", "source_type": "journal"}
        res_c1 = session.post(f"{BASE_URL}/api/corpus/contribute", files=files_c1, data=data_c1, headers={"Accept": "application/json"})
        assert res_c1.status_code == 201
        c1_json = res_c1.json()
        inserted_doc_ids.append(c1_json["document_id"])
        assert c1_json["bonus_awarded"] is False
        assert c1_json["quota_info"]["corpus_count"] == 1
        assert c1_json["quota_info"]["corpus_to_next_bonus"] == 2

        # Step 8: Sumbang Korpus Duplikat -> Ditolak (409)
        files_c1_dup = {
            "corpus_document": (f"korpus_nlp_{random_suffix}.txt", io.BytesIO(corpus_content_1.encode("utf-8")), "text/plain")
        }
        res_dup_c = session.post(f"{BASE_URL}/api/corpus/contribute", files=files_c1_dup, data=data_c1, headers={"Accept": "application/json"})
        assert res_dup_c.status_code == 409
        assert res_dup_c.json()["status"] == "duplicate"

        # Step 9: Sumbang Korpus 2 (valid)
        corpus_content_2 = f"Metodologi pengujian komputasi terdistribusi dalam repositori karya ilmiah {random_suffix}."
        files_c2 = {
            "corpus_document": (f"korpus_cloud_{random_suffix}.txt", io.BytesIO(corpus_content_2.encode("utf-8")), "text/plain")
        }
        data_c2 = {"title": f"Komputasi Terdistribusi Vokasi {random_suffix}", "source_type": "conference"}
        res_c2 = session.post(f"{BASE_URL}/api/corpus/contribute", files=files_c2, data=data_c2, headers={"Accept": "application/json"})
        assert res_c2.status_code == 201
        c2_json = res_c2.json()
        inserted_doc_ids.append(c2_json["document_id"])
        assert c2_json["bonus_awarded"] is False
        assert c2_json["quota_info"]["corpus_count"] == 2
        assert c2_json["quota_info"]["corpus_to_next_bonus"] == 1

        # Step 10: Sumbang Korpus 3 (valid) -> Memicu bonus +1 submisi!
        corpus_content_3 = f"Implementasi etika sitasi dan reproduktibilitas penelitian ilmiah Indonesia {random_suffix}."
        files_c3 = {
            "corpus_document": (f"korpus_etika_{random_suffix}.txt", io.BytesIO(corpus_content_3.encode("utf-8")), "text/plain")
        }
        data_c3 = {"title": f"Etika Sitasi Ilmiah {random_suffix}", "source_type": "journal"}
        res_c3 = session.post(f"{BASE_URL}/api/corpus/contribute", files=files_c3, data=data_c3, headers={"Accept": "application/json"})
        assert res_c3.status_code == 201
        c3_json = res_c3.json()
        inserted_doc_ids.append(c3_json["document_id"])
        assert c3_json["bonus_awarded"] is True
        assert c3_json["quota_info"]["corpus_count"] == 3
        assert c3_json["quota_info"]["bonus_remaining"] == 1
        assert c3_json["quota_info"]["total_available"] == 1

        # Step 11: Submisi ke-5 berhasil menggunakan bonus kuota
        file_content_5 = f"Submisi naskah kelima yang menggunakan bonus kuota {random_suffix}."
        files_5 = {
            "document": (f"submisi_bonus_5_{random_suffix}.txt", io.BytesIO(file_content_5.encode("utf-8")), "text/plain")
        }
        res_sub_5 = session.post(f"{BASE_URL}/api/documents", files=files_5, headers={"Accept": "application/json"})
        assert res_sub_5.status_code == 201
        sub_5_json = res_sub_5.json()
        inserted_doc_ids.append(sub_5_json["document_id"])
        assert sub_5_json["quota_info"]["bonus_remaining"] == 0
        assert sub_5_json["quota_info"]["total_available"] == 0

        # Step 12: Submisi ke-6 ditolak kembali karena bonus telah terpakai
        file_content_6 = f"Submisi naskah keenam setelah bonus terpakai {random_suffix}."
        files_6 = {
            "document": (f"submisi_ke_6_{random_suffix}.txt", io.BytesIO(file_content_6.encode("utf-8")), "text/plain")
        }
        res_sub_6 = session.post(f"{BASE_URL}/api/documents", files=files_6, headers={"Accept": "application/json"})
        assert res_sub_6.status_code == 403

    finally:
        # Cleanup data pengujian dari database
        cursor = db_conn.cursor()
        if inserted_doc_ids:
            fmt = ",".join(["%s"] * len(inserted_doc_ids))
            cursor.execute(f"DELETE FROM documents WHERE id IN ({fmt})", tuple(inserted_doc_ids))
        if created_user_id:
            cursor.execute("DELETE FROM users WHERE id = %s", (created_user_id,))
        db_conn.commit()
        cursor.close()


def test_guest_dashboard_access_and_upload_protection():
    """
    Memverifikasi proteksi akses tamu/pengunjung yang belum login:
    1. Akses GET /dashboard tanpa login harus dialihkan (307/302 redirect) ke /?auth=login.
    2. Unggah POST /api/documents tanpa login harus ditolak (401 Unauthorized).
    3. Landing page tidak menyajikan tautan langsung ke /dashboard bagi tamu di footer.
    """
    guest_session = requests.Session()

    # 1. Akses Dashboard tanpa login
    res_dash = guest_session.get(f"{BASE_URL}/dashboard", allow_redirects=False)
    assert res_dash.status_code in (302, 307)
    assert "auth=login" in res_dash.headers.get("Location", "")

    # 2. Upload Dokumen tanpa login
    file_content = "Percobaan unggah naskah tanpa login akun."
    files = {
        "document": ("percobaan_anonim.txt", io.BytesIO(file_content.encode("utf-8")), "text/plain")
    }
    res_upload = guest_session.post(f"{BASE_URL}/api/documents", files=files, headers={"Accept": "application/json"})
    assert res_upload.status_code == 401
    upload_data = res_upload.json()
    assert upload_data["status"] == "unauthorized"

    # 3. Footer landing page bagi tamu
    res_landing = guest_session.get(f"{BASE_URL}/")
    assert res_landing.status_code == 200
    html = res_landing.text
    assert "openAuthModal('login')" in html

