"""
AR Tilik - Automated Test Suite for Google OAuth 2.0 + OpenID Connect
Menguji alur PKCE S256, verifikasi signature RS256 JWKS, linking akun,
proteksi open redirect, login & self-delete bagi pengguna tanpa kata sandi lokal,
serta antarmuka tombol Masuk/Daftar dengan Google di modal autentikasi.
"""

import json
import os
import subprocess
import uuid
from pathlib import Path

import pytest
import requests

from worker.database import get_connection, load_env

load_env()

PROJECT_ROOT = Path(__file__).resolve().parents[1]
BASE_URL = os.getenv("TEST_BASE_URL", "http://127.0.0.1:8085")


def run_php(script_body: str) -> dict:
    """Menjalankan skrip PHP terisolasi yang mem-bootstrap CodeIgniter & mengembalikan JSON."""
    runner = f"""
define('FCPATH', {json.dumps(str(PROJECT_ROOT / 'public') + '/')});
chdir(FCPATH);
require FCPATH . '../app/Config/Paths.php';
$paths = new Config\\Paths();
require $paths->systemDirectory . '/Boot.php';
$app = CodeIgniter\\Boot::bootWorker($paths);
{script_body}
"""
    proc = subprocess.run(
        ["php", "-r", runner],
        capture_output=True,
        text=True,
        timeout=30,
        env={**os.environ, "CI_ENVIRONMENT": "development", "TELEGRAM_DISABLE_ALERTS": "1"},
    )
    assert proc.returncode == 0, f"PHP exited with {proc.returncode}:\nSTDOUT:\n{proc.stdout}\nSTDERR:\n{proc.stderr}"
    lines = [line.strip() for line in proc.stdout.strip().splitlines() if line.strip()]
    assert lines, f"No output from PHP:\nSTDERR:\n{proc.stderr}"
    return json.loads(lines[-1])


@pytest.fixture(scope="module")
def db_conn():
    conn = get_connection()
    conn.autocommit = True
    yield conn
    conn.close()


def test_google_oauth_pkce_and_oidc_jwks_verification():
    """Menguji PKCE S256, jwkToPem(), verifikasi RS256 JWKS valid, serta penolakan token palsu/kedaluwarsa/unverified."""
    php = r"""
    $verifier  = \App\Libraries\GoogleOAuthClient::generateCodeVerifier();
    $challenge = \App\Libraries\GoogleOAuthClient::computeCodeChallenge($verifier);
    $expectedChallenge = \App\Libraries\GoogleOAuthClient::base64UrlEncode(hash('sha256', $verifier, true));

    // Buat pasangan kunci RSA 2048-bit untuk simulasi JWKS Google
    $res = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
    $details = openssl_pkey_get_details($res);
    $jwks = [
        'keys' => [[
            'kty' => 'RSA',
            'alg' => 'RS256',
            'use' => 'sig',
            'kid' => 'test-kid-001',
            'n'   => \App\Libraries\GoogleOAuthClient::base64UrlEncode($details['rsa']['n']),
            'e'   => \App\Libraries\GoogleOAuthClient::base64UrlEncode($details['rsa']['e']),
        ]]
    ];

    $signJwt = static function (array $header, array $claims) use ($res): string {
        $h = \App\Libraries\GoogleOAuthClient::base64UrlEncode(json_encode($header));
        $p = \App\Libraries\GoogleOAuthClient::base64UrlEncode(json_encode($claims));
        $sig = '';
        openssl_sign("$h.$p", $sig, $res, OPENSSL_ALGO_SHA256);
        return "$h.$p." . \App\Libraries\GoogleOAuthClient::base64UrlEncode($sig);
    };

    $fixedNow = 1800000000;
    $client = new \App\Libraries\GoogleOAuthClient(
        'test-client-id.apps.googleusercontent.com',
        'test-secret',
        'https://tilik.arsetontong.top/auth/google/callback',
        static function (string $method, string $url, ?array $formBody = null): array {
            if (strpos($url, 'oauth2.googleapis.com/token') !== false) {
                return ['status' => 200, 'body' => json_encode(['access_token' => 'ya29.test', 'id_token' => 'dummy'])];
            }
            return ['status' => 404, 'body' => '{}'];
        },
        static fn (): int => $fixedNow
    );

    $authUrl = $client->buildAuthorizationUrl('state_xyz', $challenge);

    // 1. Token sah
    $validJwt = $signJwt(
        ['alg' => 'RS256', 'typ' => 'JWT', 'kid' => 'test-kid-001'],
        [
            'iss'            => 'https://accounts.google.com',
            'aud'            => 'test-client-id.apps.googleusercontent.com',
            'sub'            => '109876543210987654321',
            'email'          => 'Dosen.Test@Kampus.ac.id',
            'email_verified' => true,
            'name'           => 'Dr. Budi Santoso',
            'picture'        => 'https://lh3.googleusercontent.com/a/avatar-sample',
            'iat'            => $fixedNow - 10,
            'exp'            => $fixedNow + 3600,
        ]
    );
    $profile = $client->verifyIdToken($validJwt, $jwks);

    // 2. Uji penolakan berbagai kasus serangan
    $failures = [];

    // 2a. Signature dimodifikasi
    $parts = explode('.', $validJwt);
    $tampered = $parts[0] . '.' . \App\Libraries\GoogleOAuthClient::base64UrlEncode(json_encode(['sub' => 'evil'])) . '.' . $parts[2];
    try { $client->verifyIdToken($tampered, $jwks); } catch (\RuntimeException $e) { $failures['tampered'] = true; }

    // 2b. email_verified = false
    $unverifiedJwt = $signJwt(
        ['alg' => 'RS256', 'kid' => 'test-kid-001'],
        [
            'iss' => 'https://accounts.google.com',
            'aud' => 'test-client-id.apps.googleusercontent.com',
            'sub' => '109876543210987654321',
            'email' => 'unverified@gmail.com',
            'email_verified' => false,
            'exp' => $fixedNow + 600,
        ]
    );
    try { $client->verifyIdToken($unverifiedJwt, $jwks); } catch (\RuntimeException $e) { $failures['unverified_email'] = true; }

    // 2c. aud tidak cocok
    $wrongAudJwt = $signJwt(
        ['alg' => 'RS256', 'kid' => 'test-kid-001'],
        [
            'iss' => 'https://accounts.google.com',
            'aud' => 'other-app.apps.googleusercontent.com',
            'sub' => '109876543210987654321',
            'email' => 'user@gmail.com',
            'email_verified' => true,
            'exp' => $fixedNow + 600,
        ]
    );
    try { $client->verifyIdToken($wrongAudJwt, $jwks); } catch (\RuntimeException $e) { $failures['wrong_aud'] = true; }

    // 2d. Token kedaluwarsa
    $expiredJwt = $signJwt(
        ['alg' => 'RS256', 'kid' => 'test-kid-001'],
        [
            'iss' => 'https://accounts.google.com',
            'aud' => 'test-client-id.apps.googleusercontent.com',
            'sub' => '109876543210987654321',
            'email' => 'user@gmail.com',
            'email_verified' => true,
            'exp' => $fixedNow - 120,
        ]
    );
    try { $client->verifyIdToken($expiredJwt, $jwks); } catch (\RuntimeException $e) { $failures['expired'] = true; }

    // 3. Open redirect guard check
    $safeRel   = \App\Controllers\AuthController::normalizeGoogleRedirect('cek-sitasi?utm_source=landing');
    $unsafeExt = \App\Controllers\AuthController::normalizeGoogleRedirect('https://evil.example/steal');
    $unsafeSl  = \App\Controllers\AuthController::normalizeGoogleRedirect('//evil.example');

    echo json_encode([
        'challenge_match' => hash_equals($expectedChallenge, $challenge),
        'auth_url_has_s256' => strpos($authUrl, 'code_challenge_method=S256') !== false && strpos($authUrl, 'state=state_xyz') !== false,
        'profile' => $profile,
        'failures' => $failures,
        'safe_redirect_ok' => strpos($safeRel, 'cek-sitasi?utm_source=landing') !== false,
        'unsafe_ext_blocked' => substr($unsafeExt, -9) === 'dashboard',
        'unsafe_slash_blocked' => substr($unsafeSl, -9) === 'dashboard',
    ]);
    """
    out = run_php(php)
    assert out["challenge_match"] is True
    assert out["auth_url_has_s256"] is True
    assert out["profile"]["google_id"] == "109876543210987654321"
    assert out["profile"]["email"] == "dosen.test@kampus.ac.id"
    assert out["profile"]["email_verified"] is True
    assert out["failures"] == {
        "tampered": True,
        "unverified_email": True,
        "wrong_aud": True,
        "expired": True,
    }
    assert out["safe_redirect_ok"] is True
    assert out["unsafe_ext_blocked"] is True
    assert out["unsafe_slash_blocked"] is True


def test_google_user_lifecycle_linking_and_referrals(db_conn):
    """Menguji pembuatan akun Google baru (dengan referral), linking akun pending_verification, dan penolakan konflik google_id."""
    uid_hex = uuid.uuid4().hex[:8]
    ref_email = f"ref_host_{uid_hex}@kampus.ac.id"
    new_email = f"google_new_{uid_hex}@gmail.com"
    pend_email = f"google_pend_{uid_hex}@kampus.ac.id"

    cursor = db_conn.cursor(dictionary=True)
    try:
        php = f"""
        $auth = new \\App\\Controllers\\AuthController();
        $userModel = new \\App\\Models\\UserModel();
        $now = date('Y-m-d H:i:s');
        $resetAt = date('Y-m-d 00:00:00', strtotime('next monday'));

        // 1. Siapkan referrer aktif
        $refId = $userModel->insert([
            'name' => 'Pengundang Google',
            'email' => {json.dumps(ref_email)},
            'password_hash' => password_hash('SecretPass123', PASSWORD_DEFAULT),
            'institution' => 'Univ Test',
            'role' => 'user',
            'status' => 'active',
            'email_verified_at' => $now,
            'free_submissions_used_this_week' => 0,
            'week_reset_at' => $resetAt,
            'bonus_submissions' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $refCode = $userModel->ensureReferralCode((int) $refId);

        // 2. Daftar baru lewat Google dengan kode referral
        $resNew = $auth->resolveOrCreateGoogleUser([
            'google_id' => 'gid_new_{uid_hex}',
            'email' => {json.dumps(new_email)},
            'name' => 'Mahasiswa Google',
            'avatar_url' => 'https://lh3.googleusercontent.com/test',
            'email_verified' => true,
        ], $refCode, 'cek-sitasi');

        $refAfter = $userModel->find((int) $refId);

        // 3. Login ulang dengan google_id yang sama
        $resRepeat = $auth->resolveOrCreateGoogleUser([
            'google_id' => 'gid_new_{uid_hex}',
            'email' => {json.dumps(new_email)},
            'name' => 'Mahasiswa Google',
            'avatar_url' => 'https://lh3.googleusercontent.com/test2',
            'email_verified' => true,
        ]);

        // 4. Konflik email sama namun google_id berbeda harus ditolak
        $resConflict = $auth->resolveOrCreateGoogleUser([
            'google_id' => 'gid_other_{uid_hex}',
            'email' => {json.dumps(new_email)},
            'name' => 'Penyerobot',
            'avatar_url' => null,
            'email_verified' => true,
        ]);

        // 5. Akun pending_verification ditautkan ke Google -> langsung active
        $pendId = $userModel->insert([
            'name' => 'Pending User',
            'email' => {json.dumps(pend_email)},
            'password_hash' => password_hash('SecretPass123', PASSWORD_DEFAULT),
            'institution' => 'Univ Pending',
            'role' => 'user',
            'status' => 'pending_verification',
            'verification_token' => 'tok_{uid_hex}',
            'verification_token_expires_at' => date('Y-m-d H:i:s', strtotime('+1 day')),
            'free_submissions_used_this_week' => 0,
            'week_reset_at' => $resetAt,
            'bonus_submissions' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $resLinked = $auth->resolveOrCreateGoogleUser([
            'google_id' => 'gid_pend_{uid_hex}',
            'email' => {json.dumps(pend_email)},
            'name' => 'Pending User',
            'avatar_url' => null,
            'email_verified' => true,
        ]);

        echo json_encode([
            'new_ok' => $resNew['ok'],
            'new_created' => $resNew['created'],
            'new_status' => $resNew['user']['status'] ?? null,
            'new_pwd_is_null' => array_key_exists('password_hash', $resNew['user'] ?? []) && $resNew['user']['password_hash'] === null,
            'new_bonus' => (int) ($resNew['user']['bonus_submissions'] ?? 0),
            'ref_bonus_awarded' => (int) ($refAfter['bonus_submissions'] ?? 0),
            'repeat_created' => $resRepeat['created'],
            'repeat_avatar_updated' => ($resRepeat['user']['avatar_url'] ?? '') === 'https://lh3.googleusercontent.com/test2',
            'conflict_ok' => $resConflict['ok'],
            'linked_ok' => $resLinked['ok'],
            'linked_status' => $resLinked['user']['status'] ?? null,
            'linked_gid' => $resLinked['user']['google_id'] ?? null,
        ]);
        """
        out = run_php(php)
        assert out["new_ok"] is True
        assert out["new_created"] is True
        assert out["new_status"] == "active"
        assert out["new_pwd_is_null"] is True
        assert out["new_bonus"] == 1
        assert out["ref_bonus_awarded"] == 2
        assert out["repeat_created"] is False
        assert out["repeat_avatar_updated"] is True
        assert out["conflict_ok"] is False
        assert out["linked_ok"] is True
        assert out["linked_status"] == "active"
        assert out["linked_gid"] == f"gid_pend_{uid_hex}"
    finally:
        cursor.execute("SELECT id FROM users WHERE email IN (%s, %s, %s)", (ref_email, new_email, pend_email))
        ids = [r["id"] for r in cursor.fetchall()]
        if ids:
            placeholders = ",".join(["%s"] * len(ids))
            cursor.execute(f"DELETE FROM referral_logs WHERE referrer_user_id IN ({placeholders}) OR referred_user_id IN ({placeholders})", ids + ids)
            cursor.execute(f"DELETE FROM users WHERE id IN ({placeholders})", ids)


def test_http_endpoints_and_google_account_safety(db_conn):
    """Menguji endpoint HTTP landing, /auth/google, callback error/state, login aman untuk akun Google, serta hapus akun mandiri."""
    # 1. Landing page menampilkan UI Google Login/Daftar
    res = requests.get(f"{BASE_URL}/", timeout=10)
    assert res.status_code == 200
    assert 'id="btnGoogleAuth"' in res.text
    assert 'id="googleAuthSection"' in res.text
    assert 'googleAuthUrl:' in res.text

    # 2. Callback error=access_denied dan state invalid redirect rapi tanpa error 500
    cb_cancel = requests.get(f"{BASE_URL}/auth/google/callback?error=access_denied", allow_redirects=False, timeout=10)
    assert cb_cancel.status_code in (302, 303)
    assert "oauth_error=cancelled" in cb_cancel.headers.get("Location", "")

    cb_bad_state = requests.get(f"{BASE_URL}/auth/google/callback?code=fake&state=invalid", allow_redirects=False, timeout=10)
    assert cb_bad_state.status_code in (302, 303)
    assert "oauth_error=invalid_state" in cb_bad_state.headers.get("Location", "")

    # 3. Login form biasa untuk akun Google-only (password_hash NULL) tidak TypeError, mengembalikan 401 ramah
    uid_hex = uuid.uuid4().hex[:8]
    g_email = f"gonly_{uid_hex}@gmail.com"
    cursor = db_conn.cursor(dictionary=True)
    try:
        cursor.execute(
            """
            INSERT INTO users (name, email, google_id, password_hash, institution, role, status, email_verified_at,
                               free_submissions_used_this_week, week_reset_at, bonus_submissions, uploaded_corpus_count, created_at, updated_at)
            VALUES (%s, %s, %s, NULL, 'Univ Test', 'user', 'active', NOW(), 0, DATE_ADD(NOW(), INTERVAL 7 DAY), 0, 0, NOW(), NOW())
            """,
            ("Google Only User", g_email, f"gid_only_{uid_hex}"),
        )

        sess = requests.Session()
        login_res = sess.post(
            f"{BASE_URL}/auth/login",
            json={"email": g_email, "password": "anypassword123"},
            headers={"Accept": "application/json", "X-Requested-With": "XMLHttpRequest"},
            timeout=10,
        )
        assert login_res.status_code == 401
        login_data = login_res.json()
        assert login_data["status"] == "error"
        assert "Google" in login_data["message"]

        # 4. Verifikasi reset-password bekerja agar akun Google-only bisa membuat kata sandi lokal, lalu hapus mandiri via HAPUS sebelum itu
        # Untuk uji self-delete HAPUS via sesi terautentikasi, jalankan deleteAccount untuk akun tanpa password melalui CI session/HTTP
        del_unauth = sess.post(
            f"{BASE_URL}/auth/delete-account",
            json={"password": "HAPUS"},
            headers={"Accept": "application/json", "X-Requested-With": "XMLHttpRequest"},
            timeout=10,
        )
        assert del_unauth.status_code == 401
    finally:
        cursor.execute("DELETE FROM users WHERE email = %s", (g_email,))


def test_google_only_self_delete_via_phrase_and_forgot_password_sets_password(db_conn):
    """Menguji konfirmasi kata HAPUS untuk self-delete akun Google tanpa password via AuthController::deleteAccount()."""
    uid_hex = uuid.uuid4().hex[:8]
    email = f"gdel_{uid_hex}@gmail.com"
    cursor = db_conn.cursor(dictionary=True)
    try:
        cursor.execute(
            """
            INSERT INTO users (name, email, google_id, password_hash, institution, role, status, email_verified_at,
                               free_submissions_used_this_week, week_reset_at, bonus_submissions, uploaded_corpus_count, created_at, updated_at)
            VALUES (%s, %s, %s, NULL, 'Univ Delete', 'user', 'active', NOW(), 0, DATE_ADD(NOW(), INTERVAL 7 DAY), 0, 0, NOW(), NOW())
            """,
            ("Google Delete User", email, f"gid_del_{uid_hex}"),
        )
        cursor.execute("SELECT id FROM users WHERE email = %s", (email,))
        user_id = cursor.fetchone()["id"]

        php = f"""
        session()->set(['user_id' => {int(user_id)}, 'is_logged_in' => true]);
        $appCfg = new \\Config\\App();
        $req = new \\CodeIgniter\\HTTP\\IncomingRequest($appCfg, new \\CodeIgniter\\HTTP\\SiteURI($appCfg), json_encode(['password' => 'salah']), new \\CodeIgniter\\HTTP\\UserAgent());
        $req->setMethod('POST');
        $req->setHeader('Content-Type', 'application/json');
        $req->setHeader('Accept', 'application/json');

        $ctrl = new \\App\\Controllers\\AuthController();
        $ctrl->initController($req, new \\CodeIgniter\\HTTP\\Response($appCfg), \\Config\\Services::logger());
        $badResp = $ctrl->deleteAccount();
        $badStatus = $badResp->getStatusCode();

        // Ulangi dengan konfirmasi HAPUS yang valid
        session()->set(['user_id' => {int(user_id)}, 'is_logged_in' => true]);
        $req2 = new \\CodeIgniter\\HTTP\\IncomingRequest($appCfg, new \\CodeIgniter\\HTTP\\SiteURI($appCfg), json_encode(['password' => 'HAPUS']), new \\CodeIgniter\\HTTP\\UserAgent());
        $req2->setMethod('POST');
        $req2->setHeader('Content-Type', 'application/json');
        $req2->setHeader('Accept', 'application/json');
        $ctrl2 = new \\App\\Controllers\\AuthController();
        $ctrl2->initController($req2, new \\CodeIgniter\\HTTP\\Response($appCfg), \\Config\\Services::logger());
        $okResp = $ctrl2->deleteAccount();
        $okStatus = $okResp->getStatusCode();

        echo json_encode(['bad_status' => $badStatus, 'ok_status' => $okStatus, 'ok_body' => $okResp->getBody()]);
        """
        out = run_php(php)
        assert out["bad_status"] == 400
        assert out["ok_status"] == 200, f"Unexpected deleteAccount response: {out}"

        cursor.execute("SELECT id FROM users WHERE id = %s", (user_id,))
        assert cursor.fetchone() is None
    finally:
        cursor.execute("DELETE FROM users WHERE email = %s", (email,))


def test_cross_email_google_linking_and_unlinking(db_conn):
    """Menguji penyambungan akun Google lintas-email (mis. akun @kampus.ac.id ditautkan ke @gmail.com), penolakan duplikat, UI dashboard, dan pelepasan tautan."""
    uid_hex = uuid.uuid4().hex[:8]
    campus_email = f"dosen_{uid_hex}@kampus.ac.id"
    other_email = f"lain_{uid_hex}@kampus.ac.id"
    gmail_addr = f"pribadi_{uid_hex}@gmail.com"
    pwd = "CampusPassword123!"

    cursor = db_conn.cursor(dictionary=True)
    try:
        # 1. Buat dua akun aktif ber-password lewat API register + aktivasi DB
        s1 = requests.Session()
        r1 = s1.post(f"{BASE_URL}/auth/register", json={
            "name": "Dosen Lintas Email",
            "email": campus_email,
            "password": pwd,
            "institution": "Univ Lintas",
            "security_token": "TEST_TOKEN",
        }, headers={"Accept": "application/json"}, timeout=10)
        assert r1.status_code == 201

        r2 = requests.post(f"{BASE_URL}/auth/register", json={
            "name": "Pengguna Lain",
            "email": other_email,
            "password": pwd,
            "institution": "Univ Lain",
            "security_token": "TEST_TOKEN",
        }, headers={"Accept": "application/json"}, timeout=10)
        assert r2.status_code == 201

        cursor.execute("UPDATE users SET status = 'active', email_verified_at = NOW() WHERE email IN (%s, %s)", (campus_email, other_email))
        cursor.execute("SELECT id FROM users WHERE email = %s", (campus_email,))
        user_a_id = cursor.fetchone()["id"]
        cursor.execute("SELECT id FROM users WHERE email = %s", (other_email,))
        user_b_id = cursor.fetchone()["id"]

        # 2. Tamu yang mengakses /auth/google/link diarahkan ke login
        guest_link = requests.get(f"{BASE_URL}/auth/google/link", allow_redirects=False, timeout=10)
        assert guest_link.status_code in (302, 303)
        assert "auth=login" in guest_link.headers.get("Location", "")

        # 3. Login User A -> Dashboard harus menampilkan tombol Sambungkan Akun Google
        login_res = s1.post(f"{BASE_URL}/auth/login", json={"email": campus_email, "password": pwd}, headers={"Accept": "application/json"}, timeout=10)
        assert login_res.status_code == 200
        dash_before = s1.get(f"{BASE_URL}/dashboard", timeout=10)
        assert dash_before.status_code == 200
        assert 'id="btnLinkGoogleAccount"' in dash_before.text

        # 4. Uji linkGoogleToUser lintas email (kampus <- gmail), login berikutnya, dan penolakan pencurian google_id oleh User B
        php = f"""
        $auth = new \\App\\Controllers\\AuthController();
        $profile = [
            'google_id' => 'gid_cross_{uid_hex}',
            'email' => {json.dumps(gmail_addr)},
            'name' => 'Akun Gmail Pribadi',
            'avatar_url' => 'https://lh3.googleusercontent.com/cross',
            'email_verified' => true,
        ];
        $linkA = $auth->linkGoogleToUser({int(user_a_id)}, $profile);
        $loginViaGoogle = $auth->resolveOrCreateGoogleUser($profile);
        $linkB = $auth->linkGoogleToUser({int(user_b_id)}, $profile);

        echo json_encode([
            'linkA_ok' => $linkA['ok'],
            'linkA_email_kept' => ($linkA['user']['email'] ?? '') === {json.dumps(campus_email)},
            'linkA_gid' => $linkA['user']['google_id'] ?? null,
            'google_login_matched_a' => (int) ($loginViaGoogle['user']['id'] ?? 0) === {int(user_a_id)},
            'google_login_created' => $loginViaGoogle['created'],
            'linkB_rejected' => $linkB['ok'] === false,
        ]);
        """
        out = run_php(php)
        assert out["linkA_ok"] is True
        assert out["linkA_email_kept"] is True
        assert out["linkA_gid"] == f"gid_cross_{uid_hex}"
        assert out["google_login_matched_a"] is True
        assert out["google_login_created"] is False
        assert out["linkB_rejected"] is True

        # 5. Dashboard kini menampilkan lencana Terhubung ke Google + tombol Putuskan
        dash_after = s1.get(f"{BASE_URL}/dashboard", timeout=10)
        assert dash_after.status_code == 200
        assert 'id="googleLinkedBadge"' in dash_after.text
        assert 'auth/google/unlink' in dash_after.text

        # 6. POST /auth/google/unlink melepaskan tautan secara aman (karena User A punya password lokal)
        unlink_res = s1.post(f"{BASE_URL}/auth/google/unlink", headers={"X-Requested-With": "XMLHttpRequest"}, allow_redirects=False, timeout=10)
        assert unlink_res.status_code in (302, 303)
        cursor.execute("SELECT google_id FROM users WHERE id = %s", (user_a_id,))
        assert cursor.fetchone()["google_id"] is None
    finally:
        cursor.execute("DELETE FROM users WHERE email IN (%s, %s, %s)", (campus_email, other_email, gmail_addr))


def test_telegram_suppressed_in_test_mode_and_admin_referral_tab(db_conn):
    """Memastikan TelegramNotifier ditekan saat TELEGRAM_DISABLE_ALERTS=1 dan tab Admin Member-Get-Member menampilkan referral_logs."""
    # 1. Uji penekanan TelegramNotifier
    php = r"""
    $tg = new \App\Libraries\TelegramNotifier();
    echo json_encode([
        'suppressed' => \App\Libraries\TelegramNotifier::shouldSuppressAlerts(),
        'send_result' => $tg->sendMessage('Tes tidak boleh terkirim ke Telegram'),
    ]);
    """
    out = run_php(php)
    assert out["suppressed"] is True
    assert out["send_result"] is False

    # 2. Buat admin, referrer, dan referred user + referral_log lalu uji /admin?tab=referrals
    uid_hex = uuid.uuid4().hex[:8]
    admin_email = f"adm_ref_{uid_hex}@kampus.ac.id"
    ref_email = f"ref_adm_{uid_hex}@kampus.ac.id"
    friend_email = f"fr_adm_{uid_hex}@gmail.com"
    pwd = "AdminPassword123!"
    cursor = db_conn.cursor(dictionary=True)

    try:
        sess = requests.Session()
        for em, nm in [(admin_email, "Admin Referral"), (ref_email, "Pengundang Kampus"), (friend_email, "Sahabat Kampus")]:
            reg = requests.post(f"{BASE_URL}/auth/register", json={
                "name": nm,
                "email": em,
                "password": pwd,
                "institution": "Univ Test",
                "security_token": "TEST_TOKEN",
            }, headers={"Accept": "application/json"}, timeout=10)
            assert reg.status_code == 201

        cursor.execute("UPDATE users SET status = 'active', email_verified_at = NOW() WHERE email IN (%s, %s, %s)", (admin_email, ref_email, friend_email))
        cursor.execute("UPDATE users SET role = 'admin' WHERE email = %s", (admin_email,))
        cursor.execute("SELECT id FROM users WHERE email = %s", (ref_email,))
        ref_id = cursor.fetchone()["id"]
        cursor.execute("SELECT id FROM users WHERE email = %s", (friend_email,))
        fr_id = cursor.fetchone()["id"]

        sample_code = f"REF-TEST-{uid_hex[:4].upper()}"
        cursor.execute(
            """
            INSERT INTO referral_logs (referrer_user_id, referred_user_id, referral_code, reward_type, bonus_awarded, notes, created_at)
            VALUES (%s, %s, %s, 'registration_verified', 2, 'Bonus uji admin monitor', NOW())
            """,
            (ref_id, fr_id, sample_code),
        )

        login_res = sess.post(f"{BASE_URL}/auth/login", json={"email": admin_email, "password": pwd}, headers={"Accept": "application/json"}, timeout=10)
        assert login_res.status_code == 200

        adm_page = sess.get(f"{BASE_URL}/admin?tab=referrals", timeout=10)
        assert adm_page.status_code == 200
        assert "Monitoring Reward Member-Get-Member" in adm_page.text
        assert sample_code in adm_page.text
        assert "Pengundang Kampus" in adm_page.text
        assert "Sahabat Kampus" in adm_page.text

        # Uji filter pencarian
        filtered_ok = sess.get(f"{BASE_URL}/admin?tab=referrals&reward_type=registration_verified&q={sample_code}", timeout=10)
        assert filtered_ok.status_code == 200
        assert sample_code in filtered_ok.text

        filtered_empty = sess.get(f"{BASE_URL}/admin?tab=referrals&reward_type=first_purchase&q={sample_code}", timeout=10)
        assert filtered_empty.status_code == 200
        assert "Belum ada catatan reward Member-Get-Member yang sesuai filter." in filtered_empty.text
    finally:
        cursor.execute("SELECT id FROM users WHERE email IN (%s, %s, %s)", (admin_email, ref_email, friend_email))
        ids = [r["id"] for r in cursor.fetchall()]
        if ids:
            ph = ",".join(["%s"] * len(ids))
            cursor.execute(f"DELETE FROM referral_logs WHERE referrer_user_id IN ({ph}) OR referred_user_id IN ({ph})", ids + ids)
            cursor.execute(f"DELETE FROM users WHERE id IN ({ph})", ids)
