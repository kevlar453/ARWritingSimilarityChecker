"""
Integration Tests for Evidence-Type Presentation (WP-03)
Validates the separation of Direct Textual Overlap vs Lexical / Topic Similarity,
Divergence states, Context Layer attachment, and Decision Invariance (Regression Guard).
"""

import pytest
from worker.contract_v1_1 import (
    CitationContext,
    CitationStatus,
    CitationConfidence,
    EvidenceType,
)
from worker.evidence_presenter import (
    group_sources_by_evidence_type,
    check_evidence_divergence,
    present_hybrid_report,
)
from worker.citation_detector import CitationDetectionResult


def test_case_1_high_exact_uncited():
    """
    Test 1: Exact tinggi + tidak ada sitasi.
    Direct textual overlap terisi, context = NONE / NOT_CITED, decision = match.
    """
    report = {
        "exact_similarity": 85.0,
        "cosine_similarity": 80.0,
        "overall_similarity": 85.0,
        "longest_match_words": 45,
        "matched_words": 120,
        "shared_terms_count": 25,
        "average_shared_term_idf": 2.1,
        "predicted_decision": "match",
        "evidence_pattern": "VERBATIM_MATCH",
        "evidence_agreement": "STRONG_AGREEMENT",
    }
    sources = [
        {
            "source_document_id": 1,
            "source_title": "Sumber A",
            "exact_similarity": 85.0,
            "cosine_similarity": 80.0,
            "matched_words": 120,
        }
    ]
    text = "Algoritma hybrid menggabungkan pencocokan eksak dan kemiripan kosinus tf-idf secara paralel."

    pres = present_hybrid_report(report, sources=sources, evidence_text=text)

    # 1. Direct textual overlap terisi
    direct = pres["source_evidence"]["direct_textual_overlap"]
    assert direct["has_direct_overlap"] is True
    assert direct["count"] == 1
    assert direct["sources"][0]["display_score"] == 85.0

    # 2. Context terdeteksi NONE
    ctx = pres["context"]
    assert ctx["citation_context"] == CitationContext.NONE.value
    assert ctx["citation_status"] == CitationStatus.NOT_CITED.value

    # 3. Decision tetap MATCH
    assert pres["system_decision"]["decision"] == "match"
    assert pres["human_interpretation"]["requires_investigation"] is False  # it's match


def test_case_2_high_exact_parenthetical_citation():
    """
    Test 2: Exact tinggi + parenthetical citation.
    Direct overlap terisi, context = PARENTHETICAL_CITATION / CITED,
    Decision tetap MATCH (tidak otomatis diubah menjadi no_match).
    """
    report = {
        "exact_similarity": 82.0,
        "cosine_similarity": 78.0,
        "overall_similarity": 82.0,
        "longest_match_words": 38,
        "matched_words": 95,
        "predicted_decision": "match",
        "evidence_pattern": "VERBATIM_MATCH",
    }
    sources = [
        {"source_document_id": 2, "exact_similarity": 82.0, "cosine_similarity": 78.0, "matched_words": 95}
    ]
    text = "Menurut Smith (2024), dekomposisi bukti harus memisahkan overlap langsung dari kesamaan leksikal."

    pres = present_hybrid_report(report, sources=sources, evidence_text=text)

    assert pres["source_evidence"]["direct_textual_overlap"]["has_direct_overlap"] is True
    assert pres["context"]["citation_context"] == CitationContext.PARENTHETICAL_CITATION.value
    assert pres["context"]["citation_status"] == CitationStatus.CITED.value
    assert pres["context"]["context_span"] == "Menurut Smith (2024)"

    # Regression Guard: Decision v1.0.0 tetap MATCH
    assert pres["system_decision"]["decision"] == "match"


def test_case_3_high_exact_direct_quote():
    """
    Test 3: Exact tinggi + direct quote.
    Direct overlap terisi, context = DIRECT_QUOTE / QUOTE, Decision tetap MATCH.
    """
    report = {
        "exact_similarity": 90.0,
        "cosine_similarity": 85.0,
        "overall_similarity": 90.0,
        "longest_match_words": 42,
        "matched_words": 110,
        "predicted_decision": "match",
    }
    sources = [{"source_document_id": 3, "exact_similarity": 90.0, "cosine_similarity": 85.0, "matched_words": 110}]
    text = 'Penulis menegaskan bahwa "bukti kemiripan leksikal tidak boleh dianggap sebagai vonis otomatis plagiarisme".'

    pres = present_hybrid_report(report, sources=sources, evidence_text=text)

    assert pres["source_evidence"]["direct_textual_overlap"]["has_direct_overlap"] is True
    assert pres["context"]["citation_context"] == CitationContext.DIRECT_QUOTE.value
    assert pres["context"]["citation_status"] == CitationStatus.QUOTE.value
    assert pres["system_decision"]["decision"] == "match"


def test_case_4_high_exact_block_quote():
    """
    Test 4: Exact tinggi + block quote.
    Direct overlap terisi, context = BLOCK_QUOTE / QUOTE, Decision tetap REVIEW/MATCH.
    """
    report = {
        "exact_similarity": 75.0,
        "cosine_similarity": 70.0,
        "overall_similarity": 75.0,
        "longest_match_words": 30,
        "matched_words": 80,
        "predicted_decision": "match",
    }
    sources = [{"source_document_id": 4, "exact_similarity": 75.0, "cosine_similarity": 70.0, "matched_words": 80}]
    text = "    Setiap sistem peninjauan integritas akademik harus menempatkan telaah manusia sebagai penentu akhir substantif, bukan menyerahkan vonis kepada metrik numerik semata."

    pres = present_hybrid_report(report, sources=sources, evidence_text=text)

    assert pres["source_evidence"]["direct_textual_overlap"]["has_direct_overlap"] is True
    assert pres["context"]["citation_context"] == CitationContext.BLOCK_QUOTE.value
    assert pres["context"]["citation_status"] == CitationStatus.QUOTE.value
    assert pres["system_decision"]["decision"] == "match"


def test_case_5_high_tfidf_zero_exact_divergence():
    """
    Test 5: TF-IDF tinggi + Exact ≈ 0.
    Direct overlap KOSONG (0 sumber), Lexical similarity terisi,
    Divergence notice aktif dengan pesan transparan.
    """
    report = {
        "exact_similarity": 0.0,
        "cosine_similarity": 78.5,
        "overall_similarity": 78.5,
        "longest_match_words": 0,
        "matched_words": 0,
        "shared_terms_count": 28,
        "average_shared_term_idf": 2.45,
        "predicted_decision": "review",
        "evidence_pattern": "SEMANTIC_PARAPHRASE",
    }
    sources = [
        {"source_document_id": 5, "exact_similarity": 0.0, "cosine_similarity": 78.5, "matched_words": 0}
    ]
    text = "Makalah ini mendiskusikan kerangka integritas akademik komprehensif pada perguruan tinggi."

    pres = present_hybrid_report(report, sources=sources, evidence_text=text)

    # Direct overlap harus KOSONG karena exact = 0
    direct = pres["source_evidence"]["direct_textual_overlap"]
    assert direct["has_direct_overlap"] is False
    assert direct["count"] == 0

    # Lexical similarity terisi
    lexical = pres["source_evidence"]["lexical_topic_similarity"]
    assert lexical["has_lexical_similarity"] is True
    assert lexical["count"] == 1
    assert lexical["sources"][0]["display_score"] == 78.5

    # Divergence notice aktif
    div = pres["source_evidence"]["divergence"]
    assert div is not None
    assert div["has_divergence"] is True
    assert "Divergensi Bukti" in div["message"]
    assert "Exact: 0.00%" in div["message"]

    # Decision tetap REVIEW sesuai v1.0.0
    assert pres["system_decision"]["decision"] == "review"


def test_case_6_high_tfidf_paraphrase_cited():
    """
    Test 6: TF-IDF tinggi + paraphrase + citation.
    Direct overlap KOSONG, Lexical terisi, context = PARENTHETICAL_CITATION / CITED,
    Divergence aktif, Decision tetap REVIEW.
    """
    report = {
        "exact_similarity": 0.0,
        "cosine_similarity": 65.0,
        "overall_similarity": 65.0,
        "longest_match_words": 0,
        "matched_words": 0,
        "predicted_decision": "review",
        "evidence_pattern": "SEMANTIC_PARAPHRASE",
    }
    sources = [{"source_document_id": 6, "exact_similarity": 0.0, "cosine_similarity": 65.0, "matched_words": 0}]
    text = "Dekomposisi bukti memisahkan overlap langsung dari kemiripan leksikal (Smith, 2024)."

    pres = present_hybrid_report(report, sources=sources, evidence_text=text)

    assert pres["source_evidence"]["direct_textual_overlap"]["has_direct_overlap"] is False
    assert pres["source_evidence"]["lexical_topic_similarity"]["has_lexical_similarity"] is True
    assert pres["context"]["citation_context"] == CitationContext.PARENTHETICAL_CITATION.value
    assert pres["context"]["citation_status"] == CitationStatus.CITED.value
    assert pres["source_evidence"]["divergence"]["has_divergence"] is True
    assert pres["system_decision"]["decision"] == "review"


def test_case_7_common_academic_phrase():
    """
    Test 7: Common academic phrase.
    Exact sedang (52.4%), Cosine 35.9%, pattern = COMMON_ACADEMIC_PHRASE,
    Decision tetap REVIEW.
    """
    report = {
        "exact_similarity": 52.4,
        "cosine_similarity": 35.9,
        "overall_similarity": 52.4,
        "longest_match_words": 11,
        "matched_words": 22,
        "predicted_decision": "review",
        "evidence_pattern": "COMMON_ACADEMIC_PHRASE",
        "evidence_agreement": "PARTIAL_AGREEMENT",
    }
    sources = [{"source_document_id": 7, "exact_similarity": 52.4, "cosine_similarity": 35.9, "matched_words": 22}]
    text = "Berdasarkan hasil penelitian yang telah dilakukan, dapat disimpulkan bahwa metode ini efektif."

    pres = present_hybrid_report(report, sources=sources, evidence_text=text)

    assert pres["source_evidence"]["direct_textual_overlap"]["has_direct_overlap"] is True
    assert pres["source_evidence"]["lexical_topic_similarity"]["has_lexical_similarity"] is True
    assert pres["system_decision"]["evidence_pattern"] == "COMMON_ACADEMIC_PHRASE"
    assert pres["system_decision"]["decision"] == "review"


def test_case_8_methodology_boilerplate():
    """
    Test 8: Methodology boilerplate.
    Exact rendah/sedang, Cosine sedang, Decision = review.
    """
    report = {
        "exact_similarity": 20.0,
        "cosine_similarity": 45.0,
        "overall_similarity": 45.0,
        "longest_match_words": 10,
        "matched_words": 30,
        "predicted_decision": "review",
        "evidence_pattern": "METHODOLOGY_BOILERPLATE",
    }
    sources = [{"source_document_id": 8, "exact_similarity": 20.0, "cosine_similarity": 45.0, "matched_words": 30}]
    text = "Metode penelitian yang digunakan adalah pendekatan kualitatif deskriptif dengan studi pustaka."

    pres = present_hybrid_report(report, sources=sources, evidence_text=text)

    assert pres["system_decision"]["decision"] == "review"
    assert pres["system_decision"]["evidence_pattern"] == "METHODOLOGY_BOILERPLATE"


def test_case_9_unknown_context():
    """
    Test 9: UNKNOWN context.
    Fragmen teks ambigu 'xx (??)' menghasilkan context UNKNOWN tanpa merusak struktur presentasi.
    """
    report = {
        "exact_similarity": 60.0,
        "cosine_similarity": 55.0,
        "overall_similarity": 60.0,
        "longest_match_words": 18,
        "matched_words": 40,
        "predicted_decision": "review",
    }
    sources = [{"source_document_id": 9, "exact_similarity": 60.0, "cosine_similarity": 55.0, "matched_words": 40}]
    text = "Fragmentasi teks tidak lengkap ... xx (??) tanpa konteks sitasi teridentifikasi."

    pres = present_hybrid_report(report, sources=sources, evidence_text=text)

    assert pres["context"]["citation_context"] == CitationContext.UNKNOWN.value
    assert pres["context"]["citation_status"] == CitationStatus.UNKNOWN.value
    assert pres["context"]["citation_confidence"] == CitationConfidence.UNKNOWN.value
    assert pres["system_decision"]["decision"] == "review"


@pytest.mark.parametrize("base_decision", ["match", "review", "no_match"])
@pytest.mark.parametrize("citation_text,expected_ctx", [
    ("Teks murni tanpa sitasi", CitationContext.NONE.value),
    ("Menurut Smith (2024), ini sitasi naratif", CitationContext.PARENTHETICAL_CITATION.value),
    ("Teks dengan rujukan inline [Smith, 2024]", CitationContext.INLINE_CITATION.value),
    ('Teks dengan "kutipan langsung persis"', CitationContext.DIRECT_QUOTE.value),
    ("    Kutipan blok berindentasi empat spasi.", CitationContext.BLOCK_QUOTE.value),
    ("Teks dengan rujukan footnote dokumen[1].", CitationContext.FOOTNOTE.value),
    ("Smith, J. (2024). Academic Integrity. Journal of Science, 1(1), 1-10.", CitationContext.BIBLIOGRAPHY.value),
    ("Teks dengan fragmen tanda tanya xx (??)", CitationContext.UNKNOWN.value),
])
def test_case_10_regression_guard_invariant(base_decision, citation_text, expected_ctx):
    """
    Test 10 (Regression Guard):
    Memverifikasi bahwa kehadiran APAPUN dari 8 variasi konteks sitasi
    TIDAK PERNAH mengubah keputusan sistem v1.0.0 (MATCH / REVIEW / NO_MATCH).
    Invarian mutlak: decision output v1.1 == decision output v1.0.0.
    """
    report = {
        "exact_similarity": 75.0 if base_decision == "match" else (25.0 if base_decision == "review" else 2.0),
        "cosine_similarity": 70.0 if base_decision == "match" else (30.0 if base_decision == "review" else 4.0),
        "overall_similarity": 75.0 if base_decision == "match" else (25.0 if base_decision == "review" else 2.0),
        "predicted_decision": base_decision,
    }

    pres = present_hybrid_report(report, sources=[], evidence_text=citation_text)

    # 1. Konteks terdeteksi sesuai ekspektasi
    assert pres["context"]["citation_context"] == expected_ctx

    # 2. INVARIANT CHECK: Keputusan sistem v1.0.0 persis sama
    assert pres["system_decision"]["decision"] == base_decision
    assert pres["system_decision"]["decision_invariant_guaranteed"] is True
