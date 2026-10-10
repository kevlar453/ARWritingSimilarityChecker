import hashlib
import json
import re
import requests
import pytest
import pymysql

BASE_URL = "http://localhost:8085"
SERVER_KEY = "Mid-server-cqqRTSt9Ecy9qMGYQ8lDJh2U"

def get_db_connection():
    return pymysql.connect(
        host="127.0.0.1",
        user="ymakarius",
        password="Segalang$$65216",
        database="db_artilik",
        cursorclass=pymysql.cursors.DictCursor,
        autocommit=True
    )

def test_payment_packages_endpoint():
    res = requests.get(f"{BASE_URL}/payment/packages")
    assert res.status_code == 200, f"Expected 200, got {res.status_code}"
    data = res.json()
    assert data.get("status") == "success"
    assert "client_key" in data
    packages = {p["id"]: p for p in data.get("packages", [])}
    assert "topup_3" in packages
    assert "topup_10" in packages
    assert "subs_skripsi" in packages
    assert "subs_dosen" in packages
    assert packages["topup_3"]["amount"] == 10000
    assert packages["topup_3"]["submissions"] == 3
    assert packages["topup_10"]["amount"] == 25000
    assert packages["topup_10"]["submissions"] == 10
    assert packages["subs_skripsi"]["amount"] == 25000
    assert packages["subs_skripsi"]["submissions"] == 20
    assert packages["subs_dosen"]["amount"] == 75000
    assert packages["subs_dosen"]["submissions"] == 75

def test_unauthenticated_create_snap():
    res = requests.post(f"{BASE_URL}/payment/create-snap", data={"package_id": "topup_3"})
    assert res.status_code == 401, f"Expected 401, got {res.status_code}"

def test_full_payment_lifecycle():
    # 1. Pastikan ada user uji aktif di database
    conn = get_db_connection()
    with conn.cursor() as cur:
        test_email = "test_payer_midtrans@arsetontong.top"
        cur.execute("DELETE FROM users WHERE email = %s", (test_email,))
        cur.execute("""
            INSERT INTO users (name, email, password_hash, institution, role, status, week_reset_at, bonus_submissions, created_at, updated_at)
            VALUES (%s, %s, %s, 'Institut Teknologi Bandung', 'user', 'active', NOW() + INTERVAL 7 DAY, 0, NOW(), NOW())
        """, ("Test Payer", test_email, "$2y$10$wN9a8N4o0gG8O1u2v3w4x.9gH7k8L9m0n1p2q3r4s5t6u7v8w9x0y"))
        user_id = cur.lastrowid
        conn.commit()

    # 2. Login user
    session = requests.Session()
    # Fetch login security token
    sec_res = session.get(f"{BASE_URL}/auth/security-token")
    assert sec_res.status_code == 200
    sec_token = sec_res.json().get("security_token")

    login_res = session.post(f"{BASE_URL}/auth/login", data={
        "email": test_email,
        "password": "wrong_dummy_password", # we will set session manually via test or simulate session
    })

    # Since we can set session or directly test snap creation with simulated authenticated user:
    # Let's verify by testing snap token directly or setting a valid password
    import subprocess
    hashed = subprocess.check_output(["php", "-r", "echo password_hash('Pass1234!', PASSWORD_BCRYPT);"]).decode().strip()
    with conn.cursor() as cur:
        cur.execute("UPDATE users SET password_hash = %s WHERE id = %s", (hashed, user_id))
        conn.commit()

    sec_res = session.get(f"{BASE_URL}/auth/security-token")
    sec_token = sec_res.json().get("security_token")
    login_res = session.post(f"{BASE_URL}/auth/login", data={
        "email": test_email,
        "password": "Pass1234!",
        "security_token": sec_token
    })
    assert login_res.status_code == 200, f"Login failed: {login_res.text}"
    assert login_res.json().get("status") == "success"

    # 3. Create Snap Token for topup_3
    snap_res = session.post(f"{BASE_URL}/payment/create-snap", data={"package_id": "topup_3"})
    assert snap_res.status_code == 200, f"Create snap failed: {snap_res.text}"
    snap_data = snap_res.json()
    assert snap_data.get("status") == "success"
    order_id = snap_data.get("order_id")
    snap_token = snap_data.get("snap_token")
    assert order_id.startswith("TILIK-")
    assert snap_token is not None and len(snap_token) > 10
    assert snap_data.get("order_detail") == "Test Payer dari Institut Teknologi Bandung order paket Top-Up 3 Dokumen"

    # 4. Verifikasi record di payment_transactions berstatus pending
    with conn.cursor() as cur:
        cur.execute("SELECT * FROM payment_transactions WHERE order_id = %s", (order_id,))
        trx = cur.fetchone()
        assert trx is not None
        assert trx["transaction_status"] == "pending"
        assert float(trx["amount"]) == 10000.0
        assert trx["submissions_added"] == 3

    # 5. Uji Webhook dengan Invalid Signature (Harus 403)
    fake_payload = {
        "order_id": order_id,
        "status_code": "200",
        "gross_amount": "10000.00",
        "signature_key": "invalid_signature_hex",
        "transaction_status": "settlement",
        "payment_type": "qris",
    }
    wh_bad = requests.post(f"{BASE_URL}/payment/webhook", json=fake_payload)
    assert wh_bad.status_code == 403, f"Expected 403 for bad signature, got {wh_bad.status_code}"

    # 6. Uji Webhook dengan Valid Signature
    status_code = "200"
    gross_amount = "10000.00"
    sig_raw = f"{order_id}{status_code}{gross_amount}{SERVER_KEY}"
    valid_sig = hashlib.sha512(sig_raw.encode("utf-8")).hexdigest()

    valid_payload = {
        "order_id": order_id,
        "status_code": status_code,
        "gross_amount": gross_amount,
        "signature_key": valid_sig,
        "transaction_status": "settlement",
        "payment_type": "qris",
        "fraud_status": "accept"
    }
    wh_good = requests.post(f"{BASE_URL}/payment/webhook", json=valid_payload)
    assert wh_good.status_code == 200, f"Expected 200 for good webhook, got {wh_good.status_code}: {wh_good.text}"
    assert wh_good.json().get("status") == "settlement_success"

    # 7. Verifikasi Kuota User Terkreditkan (+3)
    with conn.cursor() as cur:
        cur.execute("SELECT bonus_submissions FROM users WHERE id = %s", (user_id,))
        u = cur.fetchone()
        assert u["bonus_submissions"] == 3, f"Expected bonus_submissions=3, got {u['bonus_submissions']}"

        cur.execute("SELECT transaction_status, paid_at FROM payment_transactions WHERE order_id = %s", (order_id,))
        trx_after = cur.fetchone()
        assert trx_after["transaction_status"] == "settlement"
        assert trx_after["paid_at"] is not None

    # 8. Uji Idempotensi Webhook (Kirim payload yang sama kedua kali, kuota TIDAK boleh bertambah lagi)
    wh_repeat = requests.post(f"{BASE_URL}/payment/webhook", json=valid_payload)
    assert wh_repeat.status_code == 200
    assert wh_repeat.json().get("status") == "already_settled"

    with conn.cursor() as cur:
        cur.execute("SELECT bonus_submissions FROM users WHERE id = %s", (user_id,))
        u_repeat = cur.fetchone()
        assert u_repeat["bonus_submissions"] == 3, f"Quota must remain 3, but became {u_repeat['bonus_submissions']}"

    # 9. Uji Paket Langganan (Pejuang Skripsi -> +20 submisi dan subscription_tier 'skripsi')
    snap_sub = session.post(f"{BASE_URL}/payment/create-snap", data={"package_id": "subs_skripsi"})
    assert snap_sub.status_code == 200
    sub_order_id = snap_sub.json().get("order_id")

    sub_gross = "25000.00"
    sub_sig = hashlib.sha512(f"{sub_order_id}{status_code}{sub_gross}{SERVER_KEY}".encode("utf-8")).hexdigest()
    sub_payload = {
        "order_id": sub_order_id,
        "status_code": status_code,
        "gross_amount": sub_gross,
        "signature_key": sub_sig,
        "transaction_status": "settlement",
        "payment_type": "bank_transfer",
        "fraud_status": "accept"
    }
    wh_sub = requests.post(f"{BASE_URL}/payment/webhook", json=sub_payload)
    assert wh_sub.status_code == 200

    with conn.cursor() as cur:
        cur.execute("SELECT bonus_submissions, subscription_tier, subscription_expires_at FROM users WHERE id = %s", (user_id,))
        u_sub = cur.fetchone()
        # 3 (sebelumnya) + 20 = 23
        assert u_sub["bonus_submissions"] == 23
        assert u_sub["subscription_tier"] == "skripsi"
        assert u_sub["subscription_expires_at"] is not None

        # Clean up test user & transactions
        cur.execute("DELETE FROM payment_transactions WHERE user_id = %s", (user_id,))
        cur.execute("DELETE FROM users WHERE id = %s", (user_id,))
        conn.commit()
    conn.close()
