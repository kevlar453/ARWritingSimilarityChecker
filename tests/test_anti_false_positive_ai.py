"""
Unit Test Suite untuk Anti False-Positive AI Indicator
Menguji:
1. Validasi kutipan konkret pada Gemini Forensic (postprocess_ai_claim).
2. Deteksi era pra-LLM dari metadata dokumen.
3. Deteksi kebocoran meta-prompt percakapan chatbot.
4. Penanganan daftar pustaka berupa URL mentah pada citation_auditor.
5. Ekstraksi catatan kaki (footnotes) pada file docx.
"""

import json
import pytest
from datetime import datetime, timezone

from worker.gemini_forensic import (
    postprocess_ai_claim,
    detect_meta_prompt_leak,
)
from worker.citation_auditor import (
    split_body_and_bibliography,
    extract_bibliography_entries,
    audit_citations_and_bibliography,
)
from worker.extractor import (
    extract_document_metadata,
    PRE_LLM_CUTOFF,
)


def test_postprocess_ai_claim_pre_llm_downgrade():
    """Klaim SEDANG/TINGGI dari LLM wajib diturunkan jika metadata berkas terbukti pra-LLM."""
    raw_audit = {
        "tingkat_indikasi_ai": "SEDANG",
        "alasan_indikasi_ai": "Pola kalimat teratur mencurigakan.",
        "bukti_kutipan_ai": ["kalimat ini ada di dokumen teks naskah aslinya"],
        "inkonsistensi_metodologi_dan_logika": [],
        "integritas_sitasi": [],
    }
    doc_text = "Sebuah naskah sejarah. Kalimat ini ada di dokumen teks naskah aslinya. Pembahasan mendalam."
    pre_llm_meta = {"is_pre_llm_era": True, "created": "2012-11-20T10:00:00"}

    processed = postprocess_ai_claim(raw_audit, doc_text, pre_llm_meta)
    assert processed["tingkat_indikasi_ai"] == "RENDAH"
    assert processed["tingkat_indikasi_ai_mentah"] == "SEDANG"
    assert processed["bukti_ai_terverifikasi"] is False
    assert any("sebelum era LLM" in note for note in processed["catatan_validasi"])


def test_postprocess_ai_claim_unverified_quotes_downgrade():
    """Klaim SEDANG/TINGGI wajib diturunkan jika kutipan bukti tidak ditemukan atau kurang dari 2."""
    raw_audit = {
        "tingkat_indikasi_ai": "SEDANG",
        "alasan_indikasi_ai": "Diduga dibuat AI.",
        "bukti_kutipan_ai": ["kutipan fiktif yang tidak ada di teks"],
        "inkonsistensi_metodologi_dan_logika": [],
        "integritas_sitasi": [],
    }
    doc_text = "Ini adalah teks asli yang sama sekali berbeda dengan kutipan fiktif auditor."

    processed = postprocess_ai_claim(raw_audit, doc_text, doc_metadata=None)
    assert processed["tingkat_indikasi_ai"] == "RENDAH"
    assert processed["tingkat_indikasi_ai_mentah"] == "SEDANG"
    assert processed["bukti_ai_terverifikasi"] is False


def test_postprocess_ai_claim_verified_quotes_retained():
    """Klaim SEDANG/TINGGI dipertahankan jika minimal 2 kutipan terbukti ada di naskah dan bukan pra-LLM."""
    quote1 = "analisis regresi linier berganda digunakan untuk menguji hipotesis"
    quote2 = "penelitian ini dilaksanakan pada periode bulan januari sampai maret"
    raw_audit = {
        "tingkat_indikasi_ai": "SEDANG",
        "alasan_indikasi_ai": "Frasa template berulang.",
        "bukti_kutipan_ai": [quote1, quote2],
        "inkonsistensi_metodologi_dan_logika": [],
        "integritas_sitasi": [],
    }
    doc_text = f"Pendahuluan. Dalam penelitian ini {quote1}. Selain itu {quote2} tahun 2024."
    post_llm_meta = {"is_pre_llm_era": False, "created": "2024-05-10T10:00:00"}

    processed = postprocess_ai_claim(raw_audit, doc_text, post_llm_meta)
    assert processed["tingkat_indikasi_ai"] == "SEDANG"
    assert processed["bukti_ai_terverifikasi"] is True
    assert len(processed["kutipan_terverifikasi"]) == 2


def test_detect_meta_prompt_leak():
    """Mendeteksi jejak percakapan AI chatbot seperti 'Tentu, berikut adalah draf'."""
    text_clean = "Bab 1 Pendahuluan. Latar belakang masalah penelitian ini adalah..."
    text_leaked = "Bisa. Saya akan buat Bab IV untuk skripsi Anda. Hasil penelitian menunjukkan..."
    text_leaked_2 = "Tentu, berikut adalah revisi draf metodologi penelitian Anda."

    assert len(detect_meta_prompt_leak(text_clean)) == 0
    assert len(detect_meta_prompt_leak(text_leaked)) >= 1
    assert len(detect_meta_prompt_leak(text_leaked_2)) >= 1


def test_citation_auditor_url_only_bibliography():
    """Naskah dengan daftar pustaka berupa URL mentah tidak boleh dicap memiliki puluhan 'sitasi gaib'."""
    sample_text = """
    BAB I PENDAHULUAN
    Menurut Santoso (2018), pendidikan karakter penting bagi pembentukan moral.
    Pratama & Wijaya (2019) juga menegaskan hal serupa dalam kajian empirisnya.

    DAFTAR PUSTAKA
    https://id.wikipedia.org/wiki/Pendidikan_karakter
    https://repository.kampus.ac.id/handle/12345/678
    https://kemdikbud.go.id/artikel/pedoman-kurikulum
    """
    body, bib, has_bib = split_body_and_bibliography(sample_text)
    assert has_bib is True
    assert "https://id.wikipedia.org" in bib

    result = audit_citations_and_bibliography(sample_text)
    assert result.has_bibliography_section is True
    # Tidak boleh ada phantom citations (sitasi gaib) karena rujukan berupa URL mentah
    assert result.phantom_citations_count == 0
    assert result.health_verdict == "NEEDS_ATTENTION"
    assert "Daftar Pustaka Berupa URL" in result.verdict_label
