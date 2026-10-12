"""
Unit tests for worker/external_citation_verifier.py
"""

import pytest
from worker.external_citation_verifier import (
    extract_publication_title,
    _sanitize_title_for_query,
    verify_single_entry,
    verify_bibliography_list,
)


def test_extract_publication_title():
    # APA style with year
    t1 = extract_publication_title(
        "Pratama, D., & Kusuma, A. (2022). Efektivitas pembelajaran berbasis digital di sekolah menengah pertama. Jurnal Inovasi Pendidikan Indonesia, 8(3), 201–215."
    )
    assert t1 == "Efektivitas pembelajaran berbasis digital di sekolah menengah pertama"

    # APA single book
    t2 = extract_publication_title(
        "Gilster, P. (1997). Digital literacy. New York: Wiley."
    )
    assert t2 == "Digital literacy"

    # Quoted IEEE title
    t3 = extract_publication_title(
        '[1] J. Doe, "Quantum Computing Principles," IEEE Trans. Computers, vol. 12, 2021.'
    )
    assert "Quantum Computing Principles" in t3


def test_sanitize_title():
    raw = "Metode Penelitian: Kuantitatif, Kualitatif, dan R&D!"
    clean = _sanitize_title_for_query(raw)
    assert clean == "Metode Penelitian Kuantitatif Kualitatif dan R D"
    assert "&" not in clean


def test_verify_known_real_publication():
    res = verify_single_entry(
        raw_text="Gilster, P. (1997). Digital literacy. New York: Wiley.",
        author_keys=["gilster"],
        year="1997"
    )
    assert res["status"] == "verified"
    assert "Terverifikasi Sahih" in res["badge_label"]
    assert res.get("confidence_score", 0) > 0.5


def test_verify_suspected_fabricated_publication():
    res = verify_single_entry(
        raw_text="Pratama, D., & Kusuma, A. (2022). Efektivitas pembelajaran berbasis digital di sekolah menengah pertama. Jurnal Inovasi Pendidikan Indonesia, 8(3), 201–215.",
        author_keys=["pratama", "kusuma"],
        year="2022"
    )
    assert res["status"] == "suspected_fake"
    assert "Terindikasi Fiktif" in res["badge_label"]


def test_verify_bibliography_list():
    entries = [
        {
            "raw_text": "Gilster, P. (1997). Digital literacy. New York: Wiley.",
            "author_keys": ["gilster"],
            "year": "1997"
        },
        {
            "raw_text": "Pratama, D., & Kusuma, A. (2022). Efektivitas pembelajaran berbasis digital di sekolah menengah pertama. Jurnal Inovasi Pendidikan Indonesia, 8(3), 201–215.",
            "author_keys": ["pratama", "kusuma"],
            "year": "2022"
        }
    ]
    res = verify_bibliography_list(entries)
    assert res["enabled"] is True
    assert res["total_checked"] == 2
    assert res["verified_count"] == 1
    assert res["suspected_fake_count"] == 1
    assert res["has_suspected_fakes"] is True
    assert res["veracity_rate"] == 50.0
