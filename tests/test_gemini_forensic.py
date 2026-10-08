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
            document_text="Proposal penelitian pengaruh AI terhadap produktivitas kerja...",
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
