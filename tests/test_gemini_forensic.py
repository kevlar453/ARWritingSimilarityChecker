"""
Unit Test Suite untuk Google Gemini Forensic Audit & Pool Round-Robin
worker/gemini_forensic.py
"""

import json
import pytest
from unittest.mock import patch, MagicMock
import urllib.error

from worker.gemini_forensic import (
    get_available_gemini_keys,
    call_gemini_api,
    audit_proposal_with_gemini,
    mark_key_success,
    mark_key_rate_limited,
)


def test_gemini_audit_no_keys_in_db():
    """Memastikan sistem tidak crash dan mengembalikan status 'skipped' jika tidak ada kunci aktif."""
    with patch("worker.gemini_forensic.get_available_gemini_keys", return_value=[]):
        res = audit_proposal_with_gemini(
            document_text="Teks proposal skripsi.",
            substantive_text="Metode penelitian kuantitatif.",
            title="Pengujian Sistem",
        )
        assert res["status"] == "skipped"
        assert res["is_available"] is False
        assert "Tidak ada Google API Key aktif" in res["message"]


def test_call_gemini_api_success():
    """Memastikan call_gemini_api mem-parse response JSON dengan sukses."""
    mock_response = MagicMock()
    mock_payload = {
        "candidates": [
            {
                "content": {
                    "parts": [
                        {"text": json.dumps({
                            "tingkat_indikasi_ai": "RENDAH",
                            "alasan_indikasi_ai": "Naskah menunjukkan gaya penulisan manusia.",
                            "inkonsistensi_metodologi_dan_logika": [],
                            "integritas_sitasi": [],
                            "pertanyaan_kritis_dosen": [
                                {
                                    "fokus": "Metodologi",
                                    "pertanyaan": "Mengapa memilih regresi linier?",
                                    "alasan": "Untuk menguji dasar pemilihan model."
                                }
                            ]
                        })}
                    ]
                }
            }
        ]
    }
    mock_response.read.return_value = json.dumps(mock_payload).encode("utf-8")
    mock_response.__enter__.return_value = mock_response

    with patch("urllib.request.urlopen", return_value=mock_response):
        res = call_gemini_api("AIzaSyFakeKey", "gemini-3.6-flash", "test prompt")
        assert res["success"] is True
        assert res["status_code"] == 200
        parsed = json.loads(res["raw_text"])
        assert parsed["tingkat_indikasi_ai"] == "RENDAH"


def test_call_gemini_api_quota_exhausted():
    """Memastikan error 429 atau RESOURCE_EXHAUSTED dideteksi sebagai kuota habis."""
    error = urllib.error.HTTPError(
        url="https://generativelanguage.googleapis.com",
        code=429,
        msg="Too Many Requests",
        hdrs={},
        fp=MagicMock(read=lambda: b'{"error": {"code": 429, "message": "RESOURCE_EXHAUSTED: Quota exceeded"}}')
    )

    with patch("urllib.request.urlopen", side_effect=error):
        res = call_gemini_api("AIzaSyFakeKey", "gemini-3.6-flash", "test prompt")
        assert res["success"] is False
        assert res["is_quota"] is True
        assert res["status_code"] == 429


def test_audit_proposal_round_robin_failover():
    """
    Memverifikasi failover otomatis: Kunci #1 habis kuota, sistem langsung
    melanjutkan ke Kunci #2 dan menghasilkan audit sukses.
    """
    mock_keys = [
        {"id": 1, "key_name": "Key Akun A", "api_key": "KEY_A", "model_name": "gemini-3.6-flash"},
        {"id": 2, "key_name": "Key Akun B", "api_key": "KEY_B", "model_name": "gemini-3.6-flash"},
    ]

    def mock_call_api(api_key, model_name, prompt, timeout=30):
        if api_key == "KEY_A":
            return {"success": False, "is_quota": True, "error": "RESOURCE_EXHAUSTED", "status_code": 429}
        elif api_key == "KEY_B":
            valid_audit_json = json.dumps({
                "tingkat_indikasi_ai": "SEDANG",
                "alasan_indikasi_ai": "Terdapat frasa transisi generik AI pada Bab 2.",
                "bukti_kutipan_ai": [
                    "Proposal penelitian pengaruh AI terhadap produktivitas kerja",
                    "dalam meningkatkan efisiensi operasional organisasi"
                ],
                "inkonsistensi_metodologi_dan_logika": ["Populasi di bab 1 ditulis 100, tapi di bab 3 ditulis 50."],
                "integritas_sitasi": ["Tahun sitasi Kotler (2025) padahal buku diterbitkan 2021."],
                "pertanyaan_kritis_dosen": [
                    {
                        "fokus": "Metodologi",
                        "pertanyaan": "Berapa jumlah pasti sampel penelitian Anda?",
                        "alasan": "Terdapat perbedaan angka antara Bab 1 dan Bab 3."
                    }
                ]
            })
            return {"success": True, "raw_text": f"```json\n{valid_audit_json}\n```", "status_code": 200}
        return {"success": False, "error": "Unknown key"}

    with patch("worker.gemini_forensic.get_available_gemini_keys", return_value=mock_keys), \
         patch("worker.gemini_forensic.call_gemini_api", side_effect=mock_call_api), \
         patch("worker.gemini_forensic.mark_key_rate_limited") as mock_rate_limit, \
         patch("worker.gemini_forensic.mark_key_success") as mock_success:

        res = audit_proposal_with_gemini(
            document_text="Proposal penelitian pengaruh AI terhadap produktivitas kerja dalam meningkatkan efisiensi operasional organisasi...",
            substantive_text="Metodologi penelitian...",
            title="Pengaruh AI Terhadap Produktivitas",
        )

        # Pastikan Kunci #1 ditandai rate limited
        mock_rate_limit.assert_called_once()
        assert mock_rate_limit.call_args[0][0] == 1

        # Pastikan Kunci #2 berhasil dipakai
        mock_success.assert_called_once()
        assert mock_success.call_args[0][0] == 2

        # Pastikan audit selesai dan datanya valid
        assert res["status"] == "completed"
        assert res["key_used"] == "Key Akun B"
        audit = res["audit"]
        assert audit["tingkat_indikasi_ai"] == "SEDANG"
        assert len(audit["inkonsistensi_metodologi_dan_logika"]) == 1
        assert len(audit["pertanyaan_kritis_dosen"]) == 1


def test_calculate_exponential_backoff():
    """Memverifikasi perhitungan backoff eksponensial dan jitter."""
    from worker.gemini_forensic import calculate_exponential_backoff

    b0 = calculate_exponential_backoff(error_count=0)
    assert 60 <= b0 <= 72  # 60s + 2-10s jitter

    b1 = calculate_exponential_backoff(error_count=1)
    assert 120 <= b1 <= 132  # 120s + 2-10s jitter

    b4 = calculate_exponential_backoff(error_count=4)
    assert 900 <= b4 <= 912  # Capped at 900s + 2-10s jitter


def test_audit_proposal_daily_exhausted_failover():
    """
    Memverifikasi bahwa jika kunci terkena batas kuota harian (RPD),
    fungsi mark_key_exhausted dipanggil (bukan mark_key_rate_limited biasa).
    """
    from worker.gemini_forensic import mark_key_exhausted

    mock_keys = [
        {"id": 10, "key_name": "Key Harian Habis", "api_key": "KEY_EXHAUSTED", "model_name": "gemini-3.6-flash", "error_count": 0},
        {"id": 11, "key_name": "Key Cadangan", "api_key": "KEY_BACKUP", "model_name": "gemini-3.6-flash", "error_count": 0},
    ]

    def mock_call_api(api_key, model_name, prompt, timeout=30):
        if api_key == "KEY_EXHAUSTED":
            return {
                "success": False,
                "is_quota": True,
                "is_daily_quota": True,
                "error": "RESOURCE_EXHAUSTED: Quota exceeded for quota metric 'Generate Content API requests per day'",
                "status_code": 429
            }
        elif api_key == "KEY_BACKUP":
            valid_json = json.dumps({
                "tingkat_indikasi_ai": "RENDAH",
                "alasan_indikasi_ai": "Tulisan asli manusia.",
                "inkonsistensi_metodologi_dan_logika": [],
                "integritas_sitasi": [],
                "pertanyaan_kritis_dosen": []
            })
            return {"success": True, "raw_text": valid_json, "status_code": 200}
        return {"success": False, "error": "Unknown"}

    with patch("worker.gemini_forensic.get_available_gemini_keys", return_value=mock_keys), \
         patch("worker.gemini_forensic.call_gemini_api", side_effect=mock_call_api), \
         patch("worker.gemini_forensic.mark_key_exhausted") as mock_exhausted, \
         patch("worker.gemini_forensic.mark_key_rate_limited") as mock_rate_limit, \
         patch("worker.gemini_forensic.mark_key_success") as mock_success:

        res = audit_proposal_with_gemini(
            document_text="Contoh naskah uji coba...",
            substantive_text="Metode pengujian...",
            title="Uji Batas Harian"
        )

        # Kunci #10 harus ditandai exhausted, BUKAN rate-limited biasa
        mock_exhausted.assert_called_once()
        assert mock_exhausted.call_args[0][0] == 10
        mock_rate_limit.assert_not_called()

        # Kunci #11 berhasil
        mock_success.assert_called_once()
        assert mock_success.call_args[0][0] == 11
        assert res["status"] == "completed"


def test_audit_proposal_disabled_on_invalid_key():
    """Memverifikasi bahwa kunci HTTP 400/403 (invalid/revoked) langsung dinonaktifkan (mark_key_inactive)."""
    mock_keys = [
        {"id": 99, "key_name": "Key Rusak", "api_key": "KEY_INVALID", "model_name": "gemini-3.6-flash"},
        {"id": 100, "key_name": "Key Sehat", "api_key": "KEY_HEALTHY", "model_name": "gemini-3.6-flash"},
    ]

    def mock_call_api(api_key, model_name, prompt, timeout=30):
        if api_key == "KEY_INVALID":
            return {
                "success": False,
                "is_quota": False,
                "error": "API_KEY_INVALID: API key not valid. Please pass a valid API key.",
                "status_code": 400
            }
        return {
            "success": True,
            "raw_text": json.dumps({"tingkat_indikasi_ai": "RENDAH", "alasan_indikasi_ai": "", "inkonsistensi_metodologi_dan_logika": [], "integritas_sitasi": [], "pertanyaan_kritis_dosen": []}),
            "status_code": 200
        }

    with patch("worker.gemini_forensic.get_available_gemini_keys", return_value=mock_keys), \
         patch("worker.gemini_forensic.call_gemini_api", side_effect=mock_call_api), \
         patch("worker.gemini_forensic.mark_key_inactive") as mock_inactive, \
         patch("worker.gemini_forensic.mark_key_success") as mock_success:

        res = audit_proposal_with_gemini(
            document_text="Teks uji...",
            title="Uji Kunci Invalid"
        )

        mock_inactive.assert_called_once()
        assert mock_inactive.call_args[0][0] == 99
        mock_success.assert_called_once()
        assert mock_success.call_args[0][0] == 100
        assert res["status"] == "completed"


def test_log_gemini_metric_and_summary(tmp_path):
    """Memverifikasi pencatatan berkas log JSONL metrik dan pembuatan ringkasan harian."""
    from worker.gemini_forensic import log_gemini_metric, get_gemini_daily_summary
    metrics_file = str(tmp_path / "test_gemini_metrics.jsonl")

    with patch("worker.gemini_forensic.METRICS_LOG_PATH", metrics_file):
        log_gemini_metric("success", "Key A", "AIzaSyKeyA12345678", {"latency_s": 1.25})
        log_gemini_metric("ratelimited_rpm", "Key A", "AIzaSyKeyA12345678", {"cooldown_s": 65})
        log_gemini_metric("ratelimited_daily", "Key B", "AIzaSyKeyB87654321", {})

        summary = get_gemini_daily_summary(metrics_path=metrics_file)
        assert summary["total_success"] == 1
        assert summary["total_429"] == 2
        assert len(summary["per_key"]) >= 2

