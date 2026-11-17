"""
Uji Komprehensif P1-1: Webhook Callback OJS/LMS + HMAC-SHA256 + Anti-SSRF + OJS Receiver.
"""

from __future__ import annotations

import hashlib
import hmac
import io
import json
import logging
import os
import socket
import subprocess
import sys
import tempfile
import time
from pathlib import Path
from typing import Any, Dict, List
from unittest.mock import MagicMock

import pytest
import requests

PROJECT_ROOT = Path(__file__).resolve().parents[1]
if str(PROJECT_ROOT) not in sys.path:
    sys.path.insert(0, str(PROJECT_ROOT))

from worker.database import get_connection, load_env
from worker.webhook_crypto import decrypt_webhook_secret, encrypt_webhook_secret, secret_fingerprint
from worker.webhook_dispatcher import (
    SSRFBlockedError,
    build_similarity_webhook_payload,
    compute_hmac_signature,
    dispatch_webhook_if_configured,
    send_webhook_request,
    validate_webhook_url,
)

load_env()


def _find_free_port() -> int:
    with socket.socket(socket.AF_INET, socket.SOCK_STREAM) as s:
        s.bind(("127.0.0.1", 0))
        return int(s.getsockname()[1])


def _wait_http_ready(url: str, timeout: float = 8.0) -> None:
    deadline = time.time() + timeout
    while time.time() < deadline:
        try:
            r = requests.get(url, timeout=1.0)
            if r.status_code < 500:
                return
        except Exception:
            time.sleep(0.15)
    raise RuntimeError(f"PHP test server tidak siap pada {url}")


def _public_resolver(_host: str, _port: int) -> List[str]:
    return ["93.184.216.34"]


# =========================================================================
# 1. Cross-language Encryption Compatibility (PHP <-> Python AES-256-GCM)
# =========================================================================
def test_cross_language_webhook_crypto_php_python():
    secret = "whsec_" + "a1b2c3d4" * 6

    # Python -> PHP
    py_cipher = encrypt_webhook_secret(secret)
    assert py_cipher.startswith("v1:")
    assert secret not in py_cipher

    php_code = r"""
require 'vendor/autoload.php';
$env = file('.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
foreach ($env as $line) {
    if ($line === '' || $line[0] === '#' || strpos($line, '=') === false) continue;
    [$k, $v] = explode('=', $line, 2);
    $_ENV[trim($k)] = trim($v);
    putenv(trim($k) . '=' . trim($v));
}
require 'app/Libraries/WebhookCrypto.php';
$pyCipher = $argv[1];
$plain = \App\Libraries\WebhookCrypto::decryptSecret($pyCipher);
$phpCipher = \App\Libraries\WebhookCrypto::encryptSecret($plain);
$fp = \App\Libraries\WebhookCrypto::fingerprint($plain);
echo json_encode(['plain' => $plain, 'php_cipher' => $phpCipher, 'fp' => $fp]);
"""
    proc = subprocess.run(
        ["php", "-r", php_code, py_cipher],
        cwd=str(PROJECT_ROOT),
        capture_output=True,
        text=True,
        check=True,
    )
    out = json.loads(proc.stdout)
    assert out["plain"] == secret
    assert out["fp"] == secret_fingerprint(secret)
    assert out["php_cipher"].startswith("v1:")
    assert decrypt_webhook_secret(out["php_cipher"]) == secret


# =========================================================================
# 2. Anti-SSRF Validation (Python & PHP Parity)
# =========================================================================
@pytest.mark.parametrize(
    "bad_url",
    [
        "http://169.254.169.254/",
        "https://169.254.169.254/latest/meta-data",
        "http://localhost/",
        "https://localhost/artilik/webhook",
        "https://sub.localhost/webhook",
        "https://journal.local/webhook",
        "https://127.0.0.1/webhook",
        "https://10.0.0.5/webhook",
        "https://172.16.10.2/webhook",
        "https://192.168.1.50/webhook",
        "https://100.64.0.1/webhook",
        "https://0.0.0.0/webhook",
        "https://[::1]/webhook",
        "https://[fe80::1]/webhook",
        "https://[fc00::1]/webhook",
        "https://[::ffff:127.0.0.1]/webhook",
        "https://[::ffff:169.254.169.254]/webhook",
        "http://journal.example.org/webhook",
        "file:///etc/passwd",
        "https://user:pass@journal.example.org/webhook",
        "https://2130706433/webhook",
        "https://0x7f000001/webhook",
        "https://0177.0.0.1/webhook",
    ],
)
def test_anti_ssrf_rejects_unsafe_urls_in_python_and_php(bad_url: str):
    with pytest.raises(SSRFBlockedError):
        validate_webhook_url(bad_url, resolver=_public_resolver)

    php_check = r"""
require 'app/Libraries/WebhookUrlValidator.php';
$res = \App\Libraries\WebhookUrlValidator::validate($argv[1], fn($h) => ['93.184.216.34']);
echo json_encode($res);
"""
    proc = subprocess.run(
        ["php", "-r", php_check, bad_url],
        cwd=str(PROJECT_ROOT),
        capture_output=True,
        text=True,
        check=True,
    )
    php_res = json.loads(proc.stdout)
    assert php_res["valid"] is False, f"PHP loloskan URL SSRF berbahaya: {bad_url}"


def test_anti_ssrf_rejects_dns_resolving_to_private_or_metadata_ip():
    def mixed_dns_resolver(_host: str, _port: int) -> List[str]:
        return ["93.184.216.34", "169.254.169.254"]

    with pytest.raises(SSRFBlockedError):
        validate_webhook_url("https://rebind.example.org/webhook", resolver=mixed_dns_resolver)

    php_check = r"""
require 'app/Libraries/WebhookUrlValidator.php';
$res = \App\Libraries\WebhookUrlValidator::validate(
    'https://rebind.example.org/webhook',
    fn($h) => ['93.184.216.34', '169.254.169.254']
);
echo json_encode($res);
"""
    proc = subprocess.run(
        ["php", "-r", php_check],
        cwd=str(PROJECT_ROOT),
        capture_output=True,
        text=True,
        check=True,
    )
    assert json.loads(proc.stdout)["valid"] is False


def test_anti_ssrf_accepts_public_https_url():
    norm, host, port, ips = validate_webhook_url(
        "https://jurnal.example.ac.id/index.php/jti/artilik/webhook",
        resolver=_public_resolver,
    )
    assert norm.startswith("https://")
    assert host == "jurnal.example.ac.id"
    assert port == 443
    assert ips == ["93.184.216.34"]


# =========================================================================
# 3. Redirect Protection, Timeout, Backoff Retry & No Secret in Logs
# =========================================================================
def test_webhook_redirect_to_private_ip_blocked_and_max_redirects_enforced(caplog):
    secret = "whsec_secret_do_not_leak_in_logs_999"
    body = b'{"event":"similarity_check.completed","report_id":1}'

    # Redirect ke metadata IP harus ditolak dengan rejected_ssrf tanpa retry
    def ssrf_redirect_session_factory(_host: str, _ips: List[str]):
        sess = MagicMock()
        resp = MagicMock()
        resp.status_code = 307
        resp.headers = {"Location": "http://169.254.169.254/latest/meta-data"}
        resp.text = "Redirect"
        sess.post.return_value = resp
        return sess

    with caplog.at_level(logging.INFO, logger="artilik.webhook"):
        res_ssrf = send_webhook_request(
            callback_url="https://jurnal.example.ac.id/webhook",
            raw_body=body,
            webhook_secret=secret,
            event_type="similarity_check.completed",
            delivery_id="whdel_test_ssrf_redir",
            resolver=_public_resolver,
            session_factory=ssrf_redirect_session_factory,
            sleep_fn=lambda _s: None,
        )
    assert res_ssrf["status"] == "rejected_ssrf"
    assert res_ssrf["attempt_count"] == 1
    assert secret not in caplog.text
    assert secret_fingerprint(secret) in caplog.text

    # Redirect beruntun melebihi batas max_redirects=2 harus ditolak
    def loop_redirect_session_factory(_host: str, _ips: List[str]):
        sess = MagicMock()
        resp = MagicMock()
        resp.status_code = 307
        resp.headers = {"Location": "https://jurnal.example.ac.id/webhook-hop"}
        resp.text = "Loop"
        sess.post.return_value = resp
        return sess

    res_loop = send_webhook_request(
        callback_url="https://jurnal.example.ac.id/webhook",
        raw_body=body,
        webhook_secret=secret,
        event_type="similarity_check.completed",
        delivery_id="whdel_test_loop",
        max_redirects=2,
        resolver=_public_resolver,
        session_factory=loop_redirect_session_factory,
        sleep_fn=lambda _s: None,
    )
    assert res_loop["status"] == "failed"
    assert "melebihi batas" in (res_loop["last_error"] or "").lower()


def test_webhook_retry_backoff_and_hmac_headers():
    secret = "whsec_retry_secret_value_abc123"
    body = b'{"report_id":77,"status":"completed"}'
    observed_calls: List[Dict[str, Any]] = []
    sleeps: List[float] = []

    call_counter = {"n": 0}

    def flaky_session_factory(_host: str, _ips: List[str]):
        sess = MagicMock()

        def fake_post(url, data, headers, timeout, allow_redirects, verify):
            call_counter["n"] += 1
            observed_calls.append({"url": url, "headers": dict(headers), "timeout": timeout, "allow_redirects": allow_redirects, "verify": verify})
            resp = MagicMock()
            if call_counter["n"] < 3:
                resp.status_code = 503
                resp.text = "Service Unavailable"
            else:
                resp.status_code = 200
                resp.text = '{"status":"ok"}'
            return resp

        sess.post.side_effect = fake_post
        return sess

    result = send_webhook_request(
        callback_url="https://jurnal.example.ac.id/index.php/jti/artilik/webhook",
        raw_body=body,
        webhook_secret=secret,
        event_type="similarity_check.completed",
        delivery_id="whdel_retry_ok",
        timeout=10.0,
        max_attempts=3,
        backoff_delays=(2.0, 5.0),
        resolver=_public_resolver,
        session_factory=flaky_session_factory,
        sleep_fn=lambda s: sleeps.append(s),
    )

    assert result["status"] == "delivered"
    assert result["attempt_count"] == 3
    assert result["http_status"] == 200
    assert sleeps == [2.0, 5.0]
    assert len(observed_calls) == 3
    assert observed_calls[0]["timeout"] == 10.0
    assert observed_calls[0]["allow_redirects"] is False
    assert observed_calls[0]["verify"] is True
    expected_sig = compute_hmac_signature(body, secret)
    assert observed_calls[0]["headers"]["X-ARTilik-Signature-256"] == expected_sig
    assert observed_calls[0]["headers"]["X-ARTilik-Signature"] == expected_sig


# =========================================================================
# 4. OJS 3 Plugin Receiver (ArtilikHandler + ARTilikOJSConnector HMAC Verify)
# =========================================================================
def test_ojs_plugin_webhook_receiver_verifies_hmac_and_stores_submission_result():
    secret = "whsec_ojs_integration_test_987654321"
    payload = build_similarity_webhook_payload(
        report={
            "id": 9991,
            "document_id": 8881,
            "verification_code": "ART-TEST-9991",
            "status": "completed",
            "overall_similarity": 16.25,
            "exact_similarity": 5.5,
            "cosine_similarity": 38.0,
            "predicted_decision": "minor_revision",
            "match_count": 6,
            "source_count": 2,
            "longest_match_words": 19,
            "evidence_data": json.dumps({"substantive_similarity": 12.75}),
        },
        external_id="1240",
        delivery_id="whdel_ojs_rx_001",
    )
    raw_body = json.dumps(payload, separators=(",", ":"), ensure_ascii=False).encode("utf-8")
    valid_sig = compute_hmac_signature(raw_body, secret)

    php_harness = r"""
require 'public/integrations/ojs/pkp-plugin/artilik/ArtilikHandler.inc.php';

class FakeSubmission {
    private int $id;
    private int $contextId;
    public function __construct(int $id, int $contextId) { $this->id = $id; $this->contextId = $contextId; }
    public function getId() { return $this->id; }
    public function getData($k) { return $k === 'contextId' ? $this->contextId : 3; }
}

class FakePlugin {
    public array $settings = [];
    public function __construct(string $secret) { $this->settings['1:webhookSecret'] = $secret; }
    public function getSetting($contextId, $name) { return $this->settings["{$contextId}:{$name}"] ?? null; }
    public function updateSetting($contextId, $name, $value, $type = null) { $this->settings["{$contextId}:{$name}"] = $value; }
}

class TestableArtilikHandler extends ArtilikHandler {
    public FakePlugin $plugin;
    public FakeSubmission $sub;
    public string $rawBody;
    public array $headers;
    public int $lastStatus = 0;
    public array $lastResp = [];
    public function __construct($plugin, $sub, $rawBody, $headers) {
        $this->plugin = $plugin;
        $this->sub = $sub;
        $this->rawBody = $rawBody;
        $this->headers = $headers;
    }
    protected function resolvePluginInstance() { return $this->plugin; }
    protected function findSubmission(int $submissionId) { return $submissionId === 1240 ? $this->sub : null; }
    protected function readRawBody(): string { return $this->rawBody; }
    protected function collectRequestHeaders(): array { return $this->headers; }
    protected function sendJsonResponse(int $statusCode, array $payload) {
        $this->lastStatus = $statusCode;
        $this->lastResp = $payload;
        return true;
    }
}

class FakeContext { public function getId() { return 1; } }
class FakeRequest { public function getContext() { return new FakeContext(); } }

$_SERVER['REQUEST_METHOD'] = 'POST';
$rawBody = $argv[1];
$sigHeader = $argv[2];
$secret = $argv[3];

$plugin = new FakePlugin($secret);
$sub = new FakeSubmission(1240, 1);

$hInvalid = new TestableArtilikHandler($plugin, $sub, $rawBody, ['X-ARTilik-Signature-256' => 'sha256=' . str_repeat('0', 64)]);
$hInvalid->webhook([], new FakeRequest());

$hValid = new TestableArtilikHandler($plugin, $sub, $rawBody, ['X-ARTilik-Signature-256' => $sigHeader]);
$hValid->webhook([], new FakeRequest());

echo json_encode([
    'invalid_status' => $hInvalid->lastStatus,
    'valid_status'   => $hValid->lastStatus,
    'valid_resp'     => $hValid->lastResp,
    'stored_json'    => json_decode($plugin->getSetting(1, 'webhookResult_1240'), true),
]);
"""
    proc = subprocess.run(
        ["php", "-r", php_harness, raw_body.decode("utf-8"), valid_sig, secret],
        cwd=str(PROJECT_ROOT),
        capture_output=True,
        text=True,
        check=True,
    )
    out = json.loads(proc.stdout)
    assert out["invalid_status"] == 401
    assert out["valid_status"] == 200
    assert out["valid_resp"]["verified"] is True
    assert out["valid_resp"]["stored"] is True
    assert out["valid_resp"]["submission_id"] == 1240
    assert out["stored_json"]["overall_similarity"] == 16.25
    assert out["stored_json"]["substantive_similarity"] == 12.75
    assert out["stored_json"]["verification_code"] == "ART-TEST-9991"


# =========================================================================
# 5. Live End-to-End DB + Public API Endpoint + Webhook Delivery Table
# =========================================================================
def test_api_key_generation_encrypts_secret_and_rejects_ssrf_callbacks_live():
    conn = get_connection()
    created_key_id = None
    doc_id = None
    rep_id = None
    valid_doc_id = None
    valid_rep_id = None
    user_id = None
    orig_bonus = None
    orig_free_used = None

    port = _find_free_port()
    base_url = f"http://127.0.0.1:{port}"
    php_srv = subprocess.Popen(
        ["php", "-S", f"127.0.0.1:{port}", "-t", "public"],
        cwd=str(PROJECT_ROOT),
        stdout=subprocess.DEVNULL,
        stderr=subprocess.DEVNULL,
    )

    try:
        _wait_http_ready(f"{base_url}/")

        cursor = conn.cursor(dictionary=True)
        cursor.execute("SELECT id, bonus_submissions, free_submissions_used_this_week FROM users WHERE status = 'active' ORDER BY id ASC LIMIT 1")
        user_row = cursor.fetchone()
        assert user_row is not None
        user_id = int(user_row["id"])
        orig_bonus = int(user_row["bonus_submissions"] or 0)
        orig_free_used = int(user_row["free_submissions_used_this_week"] or 0)
        cursor.execute("UPDATE users SET bonus_submissions = GREATEST(bonus_submissions, 50) WHERE id = %s", (user_id,))
        conn.commit()
        cursor.execute("SELECT bonus_submissions FROM users WHERE id = %s", (user_id,))
        baseline_bonus = int(cursor.fetchone()["bonus_submissions"])
        conn.commit()

        # 5a. Generate API Key melalui ApiKeyModel::generateKey() (membuat encrypted webhook_secret)
        gen_php = r"""
define('HOMEPATH', getcwd() . '/');
require 'vendor/codeigniter4/framework/system/Test/bootstrap.php';
$dotenv = new \CodeIgniter\Config\DotEnv(HOMEPATH);
$dotenv->load();
$db = \Config\Database::connect('default');
$model = new \App\Models\ApiKeyModel($db);
$res = $model->generateKey((int)$argv[1], 'Pytest Webhook Key', 'ojs', 500, 60);
$keys = $model->getUserKeys((int)$argv[1]);
echo json_encode(['generated' => $res, 'listed_first' => $keys[0] ?? null]);
"""
        proc = subprocess.run(
            ["php", "-r", gen_php, str(user_id)],
            cwd=str(PROJECT_ROOT),
            capture_output=True,
            text=True,
            check=True,
        )
        gen_data = json.loads(proc.stdout)
        created = gen_data["generated"]
        created_key_id = int(created["id"])
        raw_key = created["raw_key"]
        webhook_secret = created["webhook_secret"]
        assert webhook_secret.startswith("whsec_")
        assert created["webhook_secret_fingerprint"] == secret_fingerprint(webhook_secret)
        # Pastikan getUserKeys tidak membocorkan webhook_secret atau raw_key
        assert "webhook_secret" not in gen_data["listed_first"]
        assert "api_key" not in gen_data["listed_first"]

        # Verifikasi kolom terenkripsi di MariaDB bisa didekripsi worker Python
        conn.commit()
        cursor.execute("SELECT webhook_secret, webhook_secret_fingerprint, quota_used FROM api_keys WHERE id = %s", (created_key_id,))
        db_key_row = cursor.fetchone()
        assert db_key_row["webhook_secret"].startswith("v1:")
        assert decrypt_webhook_secret(db_key_row["webhook_secret"]) == webhook_secret

        # 5b. Percobaan callback ke http://169.254.169.254/ dan http://localhost/ via POST /api/v1/check ditolak (HTTP 422)
        for bad_cb in ("http://169.254.169.254/", "http://localhost/"):
            resp = requests.post(
                f"{base_url}/api/v1/check",
                headers={"X-API-Key": raw_key},
                data={
                    "title": "SSRF Rejection Live Test",
                    "external_id": "9001",
                    "text": "Teks uji coba valid untuk memastikan validasi anti-SSRF menolak callback URL berbahaya sebelum potong kuota.",
                    "callback_url": bad_cb,
                },
                timeout=8.0,
            )
            assert resp.status_code == 422, f"Expected 422 for {bad_cb}, got {resp.status_code}: {resp.text}"
            j = resp.json()
            assert j["status"] == "error"
            assert "anti-ssrf" in j["message"].lower()

        # Pastikan kuota pengguna & quota_used API key TIDAK berkurang akibat penolakan SSRF
        conn.commit()
        cursor.execute("SELECT quota_used FROM api_keys WHERE id = %s", (created_key_id,))
        assert int(cursor.fetchone()["quota_used"]) == 0
        cursor.execute("SELECT bonus_submissions FROM users WHERE id = %s", (user_id,))
        assert int(cursor.fetchone()["bonus_submissions"]) == baseline_bonus

        # 5b-regresi: Submit valid dengan callback_url HTTPS publik (arsetontong.top resolusi publik) memotong tepat 1 kuota & mengisi jobs.payload
        ok_sub = requests.post(
            f"{base_url}/api/v1/check",
            headers={"X-API-Key": raw_key},
            data={
                "title": "Regression Quota Check Callback",
                "external_id": "ojs_sub_4401",
                "text": "Penelitian empiris mengenai deteksi kemiripan teks akademik menggunakan algoritma berbasis n-gram dan ruang vektor TF-IDF pada repository ilmiah kampus untuk pengujian regresi kuota dan antrean.",
                "callback_url": "https://tilik.arsetontong.top/index.php/jti/artilik/webhook",
            },
            timeout=8.0,
        )
        assert ok_sub.status_code == 202, f"Expected 202 on valid public HTTPS callback, got {ok_sub.status_code}: {ok_sub.text}"
        ok_data = ok_sub.json()["data"]
        valid_doc_id = int(ok_data["document_id"])
        valid_rep_id = int(ok_data["report_id"])
        conn.commit()
        cursor.execute("SELECT quota_used FROM api_keys WHERE id = %s", (created_key_id,))
        assert int(cursor.fetchone()["quota_used"]) == 1
        cursor.execute("SELECT payload FROM jobs WHERE JSON_EXTRACT(payload, '$.report_id') = %s ORDER BY id DESC LIMIT 1", (valid_rep_id,))
        job_row = cursor.fetchone()
        assert job_row is not None
        job_payload = json.loads(job_row["payload"])
        assert job_payload["callback_url"] == "https://tilik.arsetontong.top/index.php/jti/artilik/webhook"
        assert job_payload["external_id"] == "ojs_sub_4401"
        assert int(job_payload["api_key_id"]) == created_key_id
        cursor.execute("SELECT storage_path FROM documents WHERE id = %s", (valid_doc_id,))
        doc_path_row = cursor.fetchone()
        if doc_path_row and doc_path_row.get("storage_path"):
            p = PROJECT_ROOT / "writable" / doc_path_row["storage_path"]
            if p.exists():
                p.unlink()

        # 5c. Uji dispatch_webhook_if_configured mencatat rejected_ssrf ke tabel webhook_deliveries
        cursor.execute(
            """
            INSERT INTO documents (user_id, title, original_filename, storage_path, mime_type, file_size, status, document_type, corpus_visibility, created_at, updated_at)
            VALUES (%s, 'Webhook Test Doc', 'webhook_test.txt', 'documents/submissions/webhook_test.txt', 'text/plain', 100, 'completed', 'submission', 'private', NOW(), NOW())
            """,
            (user_id,),
        )
        doc_id = int(cursor.lastrowid)
        cursor.execute(
            """
            INSERT INTO reports (document_id, engine, predicted_decision, overall_similarity, verification_code, status, created_at, updated_at)
            VALUES (%s, 'hybrid', 'no_match', 9.50, 'ART-TEST-WH01', 'completed', NOW(), NOW())
            """,
            (doc_id,),
        )
        rep_id = int(cursor.lastrowid)
        conn.commit()

        ssrf_dispatch_res = dispatch_webhook_if_configured(
            job_id=None,
            payload={
                "document_id": doc_id,
                "report_id": rep_id,
                "callback_url": "http://169.254.169.254/latest/meta-data",
                "external_id": "sub_1240",
                "api_key_id": created_key_id,
            },
            timeout=5.0,
            max_attempts=3,
            backoff_delays=(0.01, 0.01),
        )
        assert ssrf_dispatch_res is not None
        assert ssrf_dispatch_res["status"] == "rejected_ssrf"

        conn.commit()
        cursor.execute("SELECT status, attempt_count, secret_fingerprint, last_error FROM webhook_deliveries WHERE report_id = %s ORDER BY id DESC LIMIT 1", (rep_id,))
        del_row = cursor.fetchone()
        assert del_row is not None
        assert del_row["status"] == "rejected_ssrf"
        assert int(del_row["attempt_count"]) == 0
        assert del_row["secret_fingerprint"] == created["webhook_secret_fingerprint"]

        # 5d. Uji wiring worker.worker.process_job -> decrypt DB secret -> sign HMAC -> OJS ArtilikHandler -> DB delivered
        import worker.worker as worker_mod
        import worker.webhook_dispatcher as wh_mod

        orig_proc_sim = worker_mod.process_similarity_check
        orig_send = wh_mod.send_webhook_request
        captured_post: Dict[str, Any] = {}

        def mock_ojs_transport(
            *,
            callback_url,
            raw_body,
            webhook_secret,
            event_type,
            delivery_id,
            timeout=10.0,
            max_attempts=3,
            backoff_delays=(2.0, 5.0),
        ):
            def ojs_session_factory(_host, _ips):
                sess = MagicMock()

                def post_into_php_handler(url, data, headers, timeout, allow_redirects, verify):
                    verify_php = r"""
require 'public/integrations/ojs/pkp-plugin/artilik/ArtilikHandler.inc.php';
class FakeSub {
    public function getId() { return 1240; }
    public function getData($k) { return $k === 'contextId' ? 1 : 3; }
}
class FakePlug {
    public array $settings = [];
    public function __construct(string $s) { $this->settings['1:webhookSecret'] = $s; }
    public function getSetting($c, $n) { return $this->settings["{$c}:{$n}"] ?? null; }
    public function updateSetting($c, $n, $v, $t = null) { $this->settings["{$c}:{$n}"] = $v; }
}
class TestHandler extends ArtilikHandler {
    private $plug; private $sub; private $body; private $hdrs;
    public int $status = 0; public array $resp = [];
    public function __construct($p, $s, $b, $h) { $this->plug=$p; $this->sub=$s; $this->body=$b; $this->hdrs=$h; }
    protected function resolvePluginInstance() { return $this->plug; }
    protected function findSubmission(int $id) { return $id === 1240 ? $this->sub : null; }
    protected function readRawBody(): string { return $this->body; }
    protected function collectRequestHeaders(): array { return $this->hdrs; }
    protected function sendJsonResponse(int $c, array $p) { $this->status=$c; $this->resp=$p; return true; }
}
class FakeCtx { public function getId() { return 1; } }
class FakeReq { public function getContext() { return new FakeCtx(); } }
$_SERVER['REQUEST_METHOD'] = 'POST';
$plug = new FakePlug($argv[3]);
$h = new TestHandler($plug, new FakeSub(), $argv[1], json_decode($argv[2], true));
$h->webhook([], new FakeReq());
echo json_encode(['status' => $h->status, 'resp' => $h->resp, 'stored' => json_decode($plug->getSetting(1, 'webhookResult_1240'), true)]);
"""
                    proc_h = subprocess.run(
                        ["php", "-r", verify_php, data.decode("utf-8"), json.dumps(dict(headers)), webhook_secret],
                        cwd=str(PROJECT_ROOT),
                        capture_output=True,
                        text=True,
                        check=True,
                    )
                    captured_post["handler_out"] = json.loads(proc_h.stdout)
                    resp = MagicMock()
                    resp.status_code = captured_post["handler_out"]["status"]
                    resp.text = json.dumps(captured_post["handler_out"]["resp"])
                    return resp

                sess.post.side_effect = post_into_php_handler
                return sess

            return orig_send(
                callback_url=callback_url,
                raw_body=raw_body,
                webhook_secret=webhook_secret,
                event_type=event_type,
                delivery_id=delivery_id,
                timeout=timeout,
                max_attempts=max_attempts,
                backoff_delays=(0.01, 0.01),
                resolver=_public_resolver,
                session_factory=ojs_session_factory,
                sleep_fn=lambda _s: None,
            )

        worker_mod.process_similarity_check = lambda document_id, report_id, engine="hybrid": None
        wh_mod.send_webhook_request = mock_ojs_transport
        try:
            worker_mod.process_job({
                "id": 987654,
                "type": "similarity_check",
                "payload": {
                    "document_id": doc_id,
                    "report_id": rep_id,
                    "engine": "hybrid",
                    "callback_url": "https://jurnal.example.ac.id/index.php/jti/artilik/webhook",
                    "external_id": "1240",
                    "api_key_id": created_key_id,
                },
            })
        finally:
            worker_mod.process_similarity_check = orig_proc_sim
            wh_mod.send_webhook_request = orig_send

        assert captured_post["handler_out"]["status"] == 200
        assert captured_post["handler_out"]["resp"]["verified"] is True
        assert captured_post["handler_out"]["stored"]["overall_similarity"] == 9.5

        conn.commit()
        cursor.execute("SELECT status, attempt_count, http_status FROM webhook_deliveries WHERE job_id = 987654 ORDER BY id DESC LIMIT 1")
        ok_row = cursor.fetchone()
        assert ok_row is not None
        assert ok_row["status"] == "delivered"
        assert int(ok_row["attempt_count"]) == 1
        assert int(ok_row["http_status"]) == 200

        # 5e. Verifikasi unduhan paket PKP OJS Plugin ZIP via web route
        zip_resp = requests.get(f"{base_url}/integrations/ojs/artilik-ojs-plugin.zip", timeout=5.0)
        assert zip_resp.status_code == 200
        import zipfile
        with zipfile.ZipFile(io.BytesIO(zip_resp.content)) as zf:
            names = zf.namelist()
            assert "artilik/ArtilikHandler.inc.php" in names
            assert "artilik/ArtilikPlugin.inc.php" in names

        # Kembalikan baseline user & bersihkan data uji
        cursor.execute("DELETE FROM webhook_deliveries WHERE report_id = %s", (rep_id,))
        cursor.execute("DELETE FROM reports WHERE id = %s", (rep_id,))
        cursor.execute("DELETE FROM documents WHERE id = %s", (doc_id,))
        cursor.execute("DELETE FROM api_keys WHERE id = %s", (created_key_id,))
        conn.commit()
        cursor.close()
    finally:
        php_srv.terminate()
        try:
            php_srv.wait(timeout=4)
        except Exception:
            php_srv.kill()
        if created_key_id or user_id is not None:
            try:
                c2 = conn.cursor()
                if valid_rep_id:
                    c2.execute("DELETE FROM jobs WHERE JSON_EXTRACT(payload, '$.report_id') = %s", (valid_rep_id,))
                    c2.execute("DELETE FROM webhook_deliveries WHERE report_id = %s", (valid_rep_id,))
                    c2.execute("DELETE FROM reports WHERE id = %s", (valid_rep_id,))
                if valid_doc_id:
                    c2.execute("DELETE FROM documents WHERE id = %s", (valid_doc_id,))
                if rep_id:
                    c2.execute("DELETE FROM webhook_deliveries WHERE report_id = %s", (rep_id,))
                    c2.execute("DELETE FROM reports WHERE id = %s", (rep_id,))
                if doc_id:
                    c2.execute("DELETE FROM documents WHERE id = %s", (doc_id,))
                if created_key_id:
                    c2.execute("DELETE FROM api_keys WHERE id = %s", (created_key_id,))
                if user_id is not None and orig_bonus is not None:
                    c2.execute(
                        "UPDATE users SET bonus_submissions = %s, free_submissions_used_this_week = %s WHERE id = %s",
                        (orig_bonus, orig_free_used, user_id),
                    )
                conn.commit()
                c2.close()
            except Exception:
                pass
        conn.close()
