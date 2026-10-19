"""
Unit & Integration Tests for AR Tilik AI Gateway & Narrative Queue
Memverifikasi fungsionalitas Caching, Deduplikasi, Circuit Breaker, Budget Guard, dan Degradasi Jujur.
"""

import json
import pytest
from unittest.mock import patch, MagicMock
from worker.ai_gateway import (
    compute_payload_hash,
    check_narrative_cache,
    is_circuit_breaker_active,
    record_circuit_failure,
    record_circuit_success,
    get_provider_daily_tokens,
    record_provider_usage,
    enqueue_narrative_job,
    execute_job_with_gateway,
    call_groq_api,
    load_gateway_config,
    _CIRCUIT_BREAKER_STATE
)


def test_payload_hash_deterministic():
    text1 = "Metodologi penelitian ini menggunakan pendekatan kuantitatif."
    text2 = "Metodologi  penelitian ini   menggunakan pendekatan kuantitatif."
    h1 = compute_payload_hash(text1, "v1")
    h2 = compute_payload_hash(text2, "v1")
    assert h1 == h2
    assert len(h1) == 64


def test_circuit_breaker_logic():
    provider = "test_prov"
    cfg = {"circuit_breaker": {"failures": 3, "cooldown_min": 5}}

    record_circuit_success(provider)
    assert not is_circuit_breaker_active(provider)

    record_circuit_failure(provider, cfg)
    record_circuit_failure(provider, cfg)
    assert not is_circuit_breaker_active(provider)

    # Kegagalan ke-3 -> aktifkan cooldown
    record_circuit_failure(provider, cfg)
    assert is_circuit_breaker_active(provider)

    # Pulihkan kembali
    record_circuit_success(provider)
    assert not is_circuit_breaker_active(provider)


def test_groq_api_call_success():
    mock_response = MagicMock()
    mock_response.read.return_value = json.dumps({
        "choices": [{
            "message": {"content": json.dumps({"tingkat_indikasi_ai": "RENDAH", "alasan_indikasi_ai": "Tulisan manusia asli"})}
        }],
        "usage": {"prompt_tokens": 800, "completion_tokens": 150}
    }).encode("utf-8")

    with patch("urllib.request.urlopen") as mock_urlopen:
        mock_urlopen.return_value.__enter__.return_value = mock_response
        res = call_groq_api("gsk_testkey", "llama-3.3-70b-versatile", "Uji prompt")

    assert res["success"] is True
    assert res["status_code"] == 200
    assert res["tokens_in"] == 800
    assert res["tokens_out"] == 150


def test_groq_api_call_429_retry_after():
    import urllib.error
    headers = {"Retry-After": "15"}
    err = urllib.error.HTTPError(
        url="https://api.groq.com",
        code=429,
        msg="Too Many Requests",
        hdrs=headers,
        fp=MagicMock(read=lambda: b'{"error": "Rate limit exceeded"}')
    )

    with patch("urllib.request.urlopen", side_effect=err):
        res = call_groq_api("gsk_testkey", "llama-3.3-70b-versatile", "Uji prompt")

    assert res["success"] is False
    assert res["status_code"] == 429
    assert res["is_quota"] is True
    assert res["retry_after_sec"] == 15


def test_honest_degradation_when_all_fail():
    mock_conn = MagicMock()
    mock_cursor = MagicMock()
    mock_conn.cursor.return_value = mock_cursor

    job = {
        "id": 9999,
        "submission_id": 8888,
        "report_id": 7777,
        "payload_hash": "testhash",
        "payload": json.dumps({
            "sample_text": "Contoh naskah ilmiah",
            "prompt": "Analisis naskah ini"
        }),
        "attempts": "[]"
    }

    # Simulasikan Groq & Gemini tidak memiliki kunci aktif / offline
    with patch("worker.ai_gateway.get_available_groq_keys", return_value=[]), \
         patch("worker.ai_gateway.get_available_gemini_keys", return_value=[]):
        res = execute_job_with_gateway(job, mock_conn)

    assert res["status"] in ("deferred", "failed")
    assert res["is_available"] is False
    assert "Analisis narasi AI tertunda" in res["message"]
