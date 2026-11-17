"""
Uji Komprehensif P1-2: Rate Limiting API Key Berbasis Redis (Fail-Open).
"""

from __future__ import annotations

import hashlib
import json
import os
import socket
import subprocess
import sys
import tempfile
import time
from datetime import datetime
from pathlib import Path

import pytest
import redis
import requests

PROJECT_ROOT = Path(__file__).resolve().parents[1]
if str(PROJECT_ROOT) not in sys.path:
    sys.path.insert(0, str(PROJECT_ROOT))

from worker.database import get_connection, load_env

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


def _wait_redis_ready(port: int, timeout: float = 5.0) -> redis.Redis:
    deadline = time.time() + timeout
    client = redis.Redis(host="127.0.0.1", port=port, socket_timeout=1.0, decode_responses=True)
    while time.time() < deadline:
        try:
            if client.ping():
                return client
        except Exception:
            time.sleep(0.1)
    raise RuntimeError(f"Redis uji coba tidak siap pada port {port}")


def test_rate_limit_61st_request_returns_429_and_fail_open_logs_when_redis_down():
    conn = get_connection()
    redis_port = _find_free_port()
    http_port = _find_free_port()

    redis_proc = subprocess.Popen(
        ["redis-server", "--port", str(redis_port), "--save", "", "--appendonly", "no", "--bind", "127.0.0.1"],
        stdout=subprocess.DEVNULL,
        stderr=subprocess.DEVNULL,
    )

    php_env = os.environ.copy()
    php_env["REDIS_HOST"] = "127.0.0.1"
    php_env["REDIS_PORT"] = str(redis_port)
    php_env["REDIS_DB"] = "0"
    php_env.pop("REDIS_PASSWORD", None)

    php_srv = subprocess.Popen(
        ["php", "-S", f"127.0.0.1:{http_port}", "-t", "public"],
        cwd=str(PROJECT_ROOT),
        env=php_env,
        stdout=subprocess.DEVNULL,
        stderr=subprocess.DEVNULL,
    )

    key_a_id = None
    key_b_id = None
    user_id = None
    orig_bonus = None
    orig_free_used = None
    created_doc_ids = []
    created_rep_ids = []

    try:
        r_client = _wait_redis_ready(redis_port)
        base_url = f"http://127.0.0.1:{http_port}"
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

        raw_key_a = f"artilik_ojs_rl_a_{int(time.time())}"
        raw_key_b = f"artilik_ojs_rl_b_{int(time.time())}"
        hash_a = hashlib.sha256(raw_key_a.encode()).hexdigest()
        hash_b = hashlib.sha256(raw_key_b.encode()).hexdigest()

        cursor.execute(
            """
            INSERT INTO api_keys (user_id, key_name, key_prefix, api_key, system_type, rate_limit_per_minute, quota_limit, quota_used, is_active, created_at, updated_at)
            VALUES (%s, 'Pytest RL Key A', 'artilik_ojs_rla...', %s, 'ojs', 60, 500, 0, 1, NOW(), NOW())
            """,
            (user_id, hash_a),
        )
        key_a_id = int(cursor.lastrowid)

        cursor.execute(
            """
            INSERT INTO api_keys (user_id, key_name, key_prefix, api_key, system_type, rate_limit_per_minute, quota_limit, quota_used, is_active, created_at, updated_at)
            VALUES (%s, 'Pytest RL Key B', 'artilik_ojs_rlb...', %s, 'ojs', 60, 500, 0, 1, NOW(), NOW())
            """,
            (user_id, hash_b),
        )
        key_b_id = int(cursor.lastrowid)
        conn.commit()

        # 1. Permintaan ke-1 s.d. ke-60 untuk Key A harus lolos seluruhnya
        session = requests.Session()
        for idx in range(1, 61):
            resp = session.get(f"{base_url}/api/v1/quota", headers={"X-API-Key": raw_key_a}, timeout=5.0)
            assert resp.status_code == 200, f"Permintaan #{idx} seharusnya 200, malah {resp.status_code}: {resp.text}"
            assert resp.headers.get("X-RateLimit-Limit") == "60"
            assert int(resp.headers.get("X-RateLimit-Remaining", -1)) == (60 - idx)

        # Verifikasi TTL Redis aktif (<= 60 detik)
        ttl_val = r_client.ttl(f"artilik:ratelimit:apikey:{key_a_id}")
        assert 1 <= ttl_val <= 60

        # 2. Permintaan ke-61 untuk Key A HARUS ditolak dengan HTTP 429 + Retry-After
        r61 = session.get(f"{base_url}/api/v1/quota", headers={"X-API-Key": raw_key_a}, timeout=5.0)
        assert r61.status_code == 429
        assert "Retry-After" in r61.headers
        retry_after = int(r61.headers["Retry-After"])
        assert 1 <= retry_after <= 60
        r61_json = r61.json()
        assert r61_json["status"] == "error"
        assert r61_json["code"] == 429
        assert int(r61_json["retry_after"]) == retry_after

        # Permintaan ke-62 ke POST /api/v1/check juga harus ditolak 429 + tidak mengurangi kuota total
        r62 = session.post(
            f"{base_url}/api/v1/check",
            headers={"X-API-Key": raw_key_a},
            data={"title": "Rate Limit Blocked Submit", "text": "Teks minimal cukup panjang untuk validasi submit api v1."},
            timeout=5.0,
        )
        assert r62.status_code == 429
        assert "Retry-After" in r62.headers

        # Endpoint /api/integration/check dengan X-API-Key A juga terkena batas 429
        r_int = session.post(
            f"{base_url}/api/integration/check",
            headers={"X-API-Key": raw_key_a},
            timeout=5.0,
        )
        assert r_int.status_code == 429
        assert "Retry-After" in r_int.headers

        # Kuota total (quota_used & bonus_submissions) TIDAK boleh berubah akibat request/429
        conn.commit()
        cursor.execute("SELECT quota_used FROM api_keys WHERE id = %s", (key_a_id,))
        assert int(cursor.fetchone()["quota_used"]) == 0
        cursor.execute("SELECT bonus_submissions FROM users WHERE id = %s", (user_id,))
        assert int(cursor.fetchone()["bonus_submissions"]) == baseline_bonus

        # 3. Lalu lintas normal Key B tidak boleh terganggu sama sekali
        rb = session.get(f"{base_url}/api/v1/quota", headers={"X-API-Key": raw_key_b}, timeout=5.0)
        assert rb.status_code == 200
        assert rb.headers.get("X-RateLimit-Remaining") == "59"

        # Kunci invalid tetap 401
        r_inv = session.get(f"{base_url}/api/v1/quota", headers={"X-API-Key": "artilik_ojs_invalid_xyz"}, timeout=5.0)
        assert r_inv.status_code == 401

        # 4. Matikan Redis -> Fail-Open tetap melayani permintaan + menulis warning/error ke log
        log_file = PROJECT_ROOT / "writable" / "logs" / f"log-{datetime.now().strftime('%Y-%m-%d')}.log"
        log_offset = log_file.stat().st_size if log_file.exists() else 0

        redis_proc.terminate()
        redis_proc.wait(timeout=4)

        r_failopen_quota = session.get(f"{base_url}/api/v1/quota", headers={"X-API-Key": raw_key_a}, timeout=5.0)
        assert r_failopen_quota.status_code == 200
        assert r_failopen_quota.headers.get("X-RateLimit-Policy") == "fail-open"

        # Uji juga POST /api/v1/check tetap berjalan saat Redis mati (recordUsage/quota tetap berfungsi)
        r_failopen_check = session.post(
            f"{base_url}/api/v1/check",
            headers={"X-API-Key": raw_key_a},
            data={
                "title": "Fail-Open Regression Check",
                "text": "Pengujian integrasi saat layanan Redis sedang tidak tersedia namun pemeriksaan tetap harus berjalan normal dengan kebijakan fail-open dan mencatat peringatan pada berkas log.",
            },
            timeout=8.0,
        )
        assert r_failopen_check.status_code == 202, f"Expected 202 under fail-open, got {r_failopen_check.status_code}: {r_failopen_check.text}"
        check_data = r_failopen_check.json()["data"]
        created_doc_ids.append(int(check_data["document_id"]))
        created_rep_ids.append(int(check_data["report_id"]))

        conn.commit()
        cursor.execute("SELECT quota_used FROM api_keys WHERE id = %s", (key_a_id,))
        assert int(cursor.fetchone()["quota_used"]) == 1

        # Periksa log CI4 mencatat insiden Redis mati tanpa membocorkan raw API key
        assert log_file.exists(), "Berkas log harian CI4 seharusnya terbentuk saat Redis mati."
        new_logs = log_file.read_text(encoding="utf-8", errors="ignore")[log_offset:]
        assert "[RATE_LIMIT] Redis tidak tersedia (fail-open aktif)" in new_logs
        assert f"api_key_id={key_a_id}" in new_logs
        assert "artilik_ojs_rla..." in new_logs
        assert raw_key_a not in new_logs

        cursor.execute("SELECT storage_path FROM documents WHERE id = %s", (created_doc_ids[0],))
        doc_row = cursor.fetchone()
        if doc_row and doc_row.get("storage_path"):
            p = PROJECT_ROOT / "writable" / doc_row["storage_path"]
            if p.exists():
                p.unlink()
        cursor.close()
    finally:
        php_srv.terminate()
        try:
            php_srv.wait(timeout=4)
        except Exception:
            php_srv.kill()

        if redis_proc.poll() is None:
            redis_proc.terminate()
            try:
                redis_proc.wait(timeout=4)
            except Exception:
                redis_proc.kill()

        try:
            c2 = conn.cursor()
            for rep_id in created_rep_ids:
                c2.execute("DELETE FROM jobs WHERE JSON_EXTRACT(payload, '$.report_id') = %s", (rep_id,))
                c2.execute("DELETE FROM reports WHERE id = %s", (rep_id,))
            for doc_id in created_doc_ids:
                c2.execute("DELETE FROM documents WHERE id = %s", (doc_id,))
            if key_a_id:
                c2.execute("DELETE FROM api_keys WHERE id = %s", (key_a_id,))
            if key_b_id:
                c2.execute("DELETE FROM api_keys WHERE id = %s", (key_b_id,))
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


def test_custom_per_key_rate_limit_threshold():
    conn = get_connection()
    redis_port = _find_free_port()
    http_port = _find_free_port()

    redis_proc = subprocess.Popen(
        ["redis-server", "--port", str(redis_port), "--save", "", "--appendonly", "no", "--bind", "127.0.0.1"],
        stdout=subprocess.DEVNULL,
        stderr=subprocess.DEVNULL,
    )
    php_env = os.environ.copy()
    php_env["REDIS_HOST"] = "127.0.0.1"
    php_env["REDIS_PORT"] = str(redis_port)
    php_env["REDIS_DB"] = "0"
    php_env.pop("REDIS_PASSWORD", None)

    php_srv = subprocess.Popen(
        ["php", "-S", f"127.0.0.1:{http_port}", "-t", "public"],
        cwd=str(PROJECT_ROOT),
        env=php_env,
        stdout=subprocess.DEVNULL,
        stderr=subprocess.DEVNULL,
    )

    custom_key_id = None
    try:
        _wait_redis_ready(redis_port)
        base_url = f"http://127.0.0.1:{http_port}"
        _wait_http_ready(f"{base_url}/")

        cursor = conn.cursor(dictionary=True)
        cursor.execute("SELECT id FROM users WHERE status = 'active' ORDER BY id ASC LIMIT 1")
        user_id = int(cursor.fetchone()["id"])

        raw_key = f"artilik_ojs_rl_custom_{int(time.time())}"
        cursor.execute(
            """
            INSERT INTO api_keys (user_id, key_name, key_prefix, api_key, system_type, rate_limit_per_minute, quota_limit, quota_used, is_active, created_at, updated_at)
            VALUES (%s, 'Pytest Custom RL Key', 'artilik_ojs_rlc...', %s, 'ojs', 5, 100, 0, 1, NOW(), NOW())
            """,
            (user_id, hashlib.sha256(raw_key.encode()).hexdigest()),
        )
        custom_key_id = int(cursor.lastrowid)
        conn.commit()
        cursor.close()

        for i in range(1, 6):
            r = requests.get(f"{base_url}/api/v1/quota", headers={"X-API-Key": raw_key}, timeout=5.0)
            assert r.status_code == 200
            assert r.headers.get("X-RateLimit-Limit") == "5"
            assert int(r.headers.get("X-RateLimit-Remaining", -1)) == (5 - i)

        r6 = requests.get(f"{base_url}/api/v1/quota", headers={"X-API-Key": raw_key}, timeout=5.0)
        assert r6.status_code == 429
        assert "Retry-After" in r6.headers
    finally:
        php_srv.terminate()
        try:
            php_srv.wait(timeout=4)
        except Exception:
            php_srv.kill()
        if redis_proc.poll() is None:
            redis_proc.terminate()
            try:
                redis_proc.wait(timeout=4)
            except Exception:
                redis_proc.kill()
        if custom_key_id:
            c2 = conn.cursor()
            c2.execute("DELETE FROM api_keys WHERE id = %s", (custom_key_id,))
            conn.commit()
            c2.close()
        conn.close()
