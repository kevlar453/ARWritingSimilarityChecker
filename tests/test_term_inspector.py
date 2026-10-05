"""
Unit tests untuk AR Tilik v1.2 Term-Level IDF Drill-Down Visualizer.
Memastikan dekomposisi dot product per kata akurat, transparan, dan memberikan
narasi penjelas objektif bagi penelaah akademik.
"""

import pytest
from worker.term_inspector import (
    TermSignificanceTier,
    compute_term_drilldown,
)


def test_empty_tokens():
    report = compute_term_drilldown([], ["halo", "dunia"], idf_table={})
    assert report.cosine_similarity == 0.0
    assert report.total_shared_terms == 0
    assert len(report.top_terms) == 0

    report2 = compute_term_drilldown(["halo"], [], idf_table={})
    assert report2.cosine_similarity == 0.0


def test_orthogonal_tokens():
    sub = ["kucing", "berlari", "di", "taman"]
    ref = ["pesawat", "terbang", "di", "angkasa"]
    # Hanya term "di" yang beririsan
    idf = {"kucing": 2.5, "berlari": 2.0, "taman": 1.8, "pesawat": 2.5, "terbang": 2.0, "angkasa": 2.0, "di": 0.2}

    report = compute_term_drilldown(sub, ref, idf_table=idf)
    assert report.total_shared_terms == 1
    assert report.top_terms[0].term == "di"
    assert report.top_terms[0].contribution_pct == 100.0
    assert report.top_terms[0].significance_tier == TermSignificanceTier.COMMON_VOCABULARY
    assert report.common_terms_count == 1
    assert report.disciplinary_terms_count == 0


def test_term_drilldown_math_and_proportions():
    sub = ["metode", "analisis", "data", "statistik", "regresi", "mediasi"]
    ref = ["pendekatan", "analisis", "regresi", "mediasi", "kuantitatif"]
    idf = {
        "metode": 0.8,
        "analisis": 1.2,
        "data": 0.5,
        "statistik": 1.5,
        "regresi": 2.8,   # High IDF -> Disciplinary
        "mediasi": 3.2,   # High IDF -> Disciplinary
        "pendekatan": 0.7,
        "kuantitatif": 1.4,
    }

    report = compute_term_drilldown(sub, ref, idf_table=idf, top_k=5)

    assert report.total_shared_terms == 3  # analisis, regresi, mediasi
    assert report.cosine_similarity > 0.0

    # Istilah dengan bobot tertinggi harus berada di peringkat 1 dan 2
    top_terms = [t.term for t in report.top_terms]
    assert "mediasi" in top_terms[:2]
    assert "regresi" in top_terms[:2]

    # Cek persentase kontribusi positif
    for t in report.top_terms:
        assert t.contribution_pct > 0.0
        assert t.dot_product > 0.0

    # Total kontribusi shared terms harus 100%
    total_pct = sum(t.contribution_pct for t in report.top_terms)
    assert round(total_pct, 1) == 100.0

    # Cek serialisasi dictionary
    data = report.to_dict()
    assert "top_contributing_terms" in data
    assert "explanation_narrative" in data
    assert len(data["top_contributing_terms"]) == 3


def test_theological_divergence_simulation_case():
    """
    Simulasi kasus divergensi empiris seperti Tesis Emiliana Vivi:
    Exact ≈ 0%, tetapi Cosine tinggi karena istilah teologi spesifik.
    """
    sub = [
        "adorasi", "ekaristi", "dalam", "sacramentum", "caritatis",
        "analisis", "pada", "pertumbuhan", "spiritualitas", "dan",
        "kesejahteraan", "psiko-spiritual", "omk", "paroki", "salib", "suci", "beduai"
    ]
    ref = [
        "penelitian", "ini", "mengkaji", "praktik", "adorasi", "ekaristi",
        "menurut", "sacramentum", "caritatis", "serta", "dampaknya", "bagi",
        "kaum", "muda", "omk", "di", "paroki", "salib", "suci", "beduai"
    ]

    idf_table = {
        "adorasi": 3.2,
        "ekaristi": 3.0,
        "sacramentum": 3.8,
        "caritatis": 3.8,
        "omk": 2.9,
        "paroki": 2.1,
        "salib": 2.4,
        "suci": 2.2,
        "beduai": 4.1,
        "analisis": 1.1,
        "pertumbuhan": 1.3,
        "spiritualitas": 2.0,
        "kesejahteraan": 1.5,
        "psiko-spiritual": 3.5,
        "dalam": 0.3,
        "pada": 0.2,
        "dan": 0.1,
    }

    report = compute_term_drilldown(sub, ref, idf_table=idf_table, top_k=10)

    # Verifikasi dominasi istilah disipliner
    assert report.disciplinary_terms_count >= 5
    assert report.disciplinary_contribution_pct >= 70.0

    # Verifikasi narasi penjelasan transparan
    assert "istilah spesifik disiplin ilmu" in report.explanation_narrative
    assert "sacramentum" in report.explanation_narrative or "caritatis" in report.explanation_narrative or "adorasi" in report.explanation_narrative
