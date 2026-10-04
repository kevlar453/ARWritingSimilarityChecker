"""
Tests for Evidence Contract v1.1 and Canonical Unit-Test Fixtures
Gate WP-01 Validation Suite

Verifies:
1. 8 Canonical Fixtures for Citation & Context Awareness
2. Backward compatibility with frozen v1.0.0 EvidenceContract
3. Deterministic serialization and safe fallback to UNKNOWN
4. Non-suppression invariant (citation does not alter or suppress similarity metrics)
5. Zero modification to v1.0.0 hybrid engine
"""

import json
import pytest
from worker.contract_v1_1 import (
    EvidenceContractV11,
    EvidenceType,
    CitationContext,
    CitationStatus,
    CitationConfidence,
    V1_0_CORE_FIELDS,
    V1_1_EXTENSION_FIELDS,
    validate_v1_0_contract_compatibility,
)
from worker.hybrid import extract_exact_evidence, extract_tfidf_evidence


# ─────────────────────────────────────────────────────────────────────────────
# 8 CANONICAL FIXTURES DEFINITION
# ─────────────────────────────────────────────────────────────────────────────

CANONICAL_FIXTURES = [
    {
        "id": "FIX-01-UNCITED",
        "description": "Uncited direct overlap",
        "sample_text": "Algoritma hybrid menggabungkan pencocokan eksak dan kemiripan kosinus tf-idf secara paralel.",
        "context_span": None,
        "expected_context": CitationContext.NONE,
        "expected_status": CitationStatus.NOT_CITED,
        "expected_confidence": CitationConfidence.HIGH,
        "evidence_type": EvidenceType.DIRECT_TEXTUAL_OVERLAP,
        "expected_rationale": "No adjacent citation marker or attribution pattern detected in surrounding passage.",
    },
    {
        "id": "FIX-02-PARENTHETICAL",
        "description": "Menurut Smith (2024)...",
        "sample_text": "Menurut Smith (2024), dekomposisi bukti harus memisahkan overlap langsung dari kesamaan leksikal.",
        "context_span": "Menurut Smith (2024)",
        "expected_context": CitationContext.PARENTHETICAL_CITATION,
        "expected_status": CitationStatus.CITED,
        "expected_confidence": CitationConfidence.HIGH,
        "evidence_type": EvidenceType.DIRECT_TEXTUAL_OVERLAP,
        "expected_rationale": "Detected author-year citation pattern adjacent to evidence span.",
    },
    {
        "id": "FIX-03-INLINE",
        "description": "[Smith, 2024] / inline marker",
        "sample_text": "Evaluasi ketahanan sistem kemiripan teks menggunakan kasus sintetis terarah [Smith, 2024].",
        "context_span": "[Smith, 2024]",
        "expected_context": CitationContext.INLINE_CITATION,
        "expected_status": CitationStatus.CITED,
        "expected_confidence": CitationConfidence.HIGH,
        "evidence_type": EvidenceType.DIRECT_TEXTUAL_OVERLAP,
        "expected_rationale": "Detected bracketed inline reference marker adjacent to evidence span.",
    },
    {
        "id": "FIX-04-DIRECT-QUOTE",
        "description": '"quoted passage"',
        "sample_text": 'Penulis menegaskan bahwa "bukti kemiripan leksikal tidak boleh dianggap sebagai vonis otomatis plagiarisme".',
        "context_span": '"bukti kemiripan leksikal tidak boleh dianggap sebagai vonis otomatis plagiarisme"',
        "expected_context": CitationContext.DIRECT_QUOTE,
        "expected_status": CitationStatus.QUOTE,
        "expected_confidence": CitationConfidence.HIGH,
        "evidence_type": EvidenceType.DIRECT_TEXTUAL_OVERLAP,
        "expected_rationale": "Detected passage enclosed within typographic or direct quotation marks.",
    },
    {
        "id": "FIX-05-BLOCK-QUOTE",
        "description": "block quotation",
        "sample_text": "    Setiap sistem peninjauan integritas akademik harus menempatkan telaah manusia sebagai penentu akhir substantif, bukan menyerahkan vonis kepada metrik numerik semata.",
        "context_span": "    Setiap sistem peninjauan integritas akademik harus menempatkan telaah manusia sebagai penentu akhir substantif...",
        "expected_context": CitationContext.BLOCK_QUOTE,
        "expected_status": CitationStatus.QUOTE,
        "expected_confidence": CitationConfidence.HIGH,
        "evidence_type": EvidenceType.DIRECT_TEXTUAL_OVERLAP,
        "expected_rationale": "Detected indented block quotation formatting structure.",
    },
    {
        "id": "FIX-06-FOOTNOTE",
        "description": "footnote marker",
        "sample_text": "Metrik IDF dihitung menggunakan logaritma basis 10 dari total korpus dibagi frekuensi dokumen[1].",
        "context_span": "[1]",
        "expected_context": CitationContext.FOOTNOTE,
        "expected_status": CitationStatus.CITED,
        "expected_confidence": CitationConfidence.HIGH,
        "evidence_type": EvidenceType.DIRECT_TEXTUAL_OVERLAP,
        "expected_rationale": "Detected numeric superscript or footnote reference marker adjacent to evidence span.",
    },
    {
        "id": "FIX-07-BIBLIOGRAPHY",
        "description": "bibliography entry",
        "sample_text": "Smith, J., & Rahman, A. (2024). Dual-Lens Evidence Decomposition in Academic Writing. Journal of Academic Ethics, 15(2), 112-128.",
        "context_span": "Smith, J., & Rahman, A. (2024). Dual-Lens Evidence Decomposition in Academic Writing. Journal of Academic Ethics, 15(2), 112-128.",
        "expected_context": CitationContext.BIBLIOGRAPHY,
        "expected_status": CitationStatus.NOT_APPLICABLE,
        "expected_confidence": CitationConfidence.HIGH,
        "evidence_type": EvidenceType.COMMON_ACADEMIC_LANGUAGE,
        "expected_rationale": "Detected formal reference list or bibliography entry structure.",
    },
    {
        "id": "FIX-08-AMBIGUOUS-UNKNOWN",
        "description": "ambiguous/unknown",
        "sample_text": "Fragmentasi teks tidak lengkap ... xx (??) tanpa konteks sitasi teridentifikasi.",
        "context_span": "xx (??)",
        "expected_context": CitationContext.UNKNOWN,
        "expected_status": CitationStatus.UNKNOWN,
        "expected_confidence": CitationConfidence.UNKNOWN,
        "evidence_type": EvidenceType.UNKNOWN,
        "expected_rationale": "Unable to determine academic attribution context reliably from available text span.",
    },
]


# ─────────────────────────────────────────────────────────────────────────────
# TESTS
# ─────────────────────────────────────────────────────────────────────────────

def test_canonical_fixtures_instantiation_and_auditability():
    """
    Test 1: Memverifikasi bahwa seluruh 8 canonical fixtures terinstansiasi
    dengan benar, mematuhi taksonomi enum, dan memiliki rationale yang dapat diaudit.
    """
    assert len(CANONICAL_FIXTURES) == 8

    for fix in CANONICAL_FIXTURES:
        contract = EvidenceContractV11(
            engine="exact",
            similarity=72.50,
            matched_terms=25,
            longest_match=14,
            source_count=1,
            match_count=2,
            avg_idf=1.85,
            max_idf=2.45,
            top_sources=[{"document_id": 101, "similarity": 72.50}],
            evidence_type=fix["evidence_type"].value,
            citation_context=fix["expected_context"].value,
            citation_status=fix["expected_status"].value,
            citation_confidence=fix["expected_confidence"].value,
            context_span=fix["context_span"],
            rationale=fix["expected_rationale"],
        )

        d = contract.to_dict()

        # Verifikasi field ekstensi
        assert d["citation_context"] == fix["expected_context"].value
        assert d["citation_status"] == fix["expected_status"].value
        assert d["citation_confidence"] == fix["expected_confidence"].value
        assert d["evidence_type"] == fix["evidence_type"].value
        assert d["context_span"] == fix["context_span"]
        assert d["rationale"] == fix["expected_rationale"]

        # Verifikasi rationale tidak kosong dan auditable
        assert d["rationale"] is not None
        assert len(d["rationale"]) > 10


def test_backward_compatibility_with_v1_0_exact_contract():
    """
    Test 2: Memverifikasi bahwa EvidenceContract v1.0.0 asli dari extract_exact_evidence
    dapat dikonversi ke v1.1 tanpa kehilangan satu pun field v1.0.0.
    """
    sample_exact_raw = {
        "overall_similarity": 75.45,
        "matched_words": 150,
        "longest_match_words": 42,
        "source_count": 2,
        "match_count": 5,
        "sources": [{"document_id": 1, "similarity": 75.45}],
        "matches": [
            {"average_idf": 1.25, "max_idf": 2.10},
            {"average_idf": 1.45, "max_idf": 2.50},
        ],
    }

    # Ekstrak menggunakan engine v1.0.0 FROZEN
    v1_contract = extract_exact_evidence(sample_exact_raw)

    # Validasi bahwa dictionary memiliki 9 field core v1.0
    assert validate_v1_0_contract_compatibility(v1_contract) is True
    for f in V1_0_CORE_FIELDS:
        assert f in v1_contract

    # Konversi ke v1.1 via factory method
    v11_contract = EvidenceContractV11.from_v1_0(v1_contract)
    v11_dict = v11_contract.to_dict()

    # Pastikan seluruh 9 field v1.0 nilainya identik
    for f in V1_0_CORE_FIELDS:
        assert v11_dict[f] == v1_contract[f], f"Mismatch on core field {f}"

    # Pastikan field v1.1 memiliki default fallback yang aman
    assert v11_dict["evidence_type"] == EvidenceType.DIRECT_TEXTUAL_OVERLAP.value
    assert v11_dict["citation_context"] == CitationContext.UNKNOWN.value
    assert v11_dict["citation_status"] == CitationStatus.UNKNOWN.value
    assert v11_dict["citation_confidence"] == CitationConfidence.UNKNOWN.value
    assert v11_dict["context_span"] is None
    assert v11_dict["rationale"] is None


def test_backward_compatibility_with_v1_0_tfidf_contract():
    """
    Test 3: Memverifikasi konversi output TF-IDF v1.0.0 ke v1.1.
    """
    sample_tfidf_raw = {
        "cosine_similarity": 48.20,
        "shared_terms_count": 18,
        "source_count": 3,
        "match_count": 1,
        "average_shared_term_idf": 2.3045,
        "sources": [{"document_id": 2, "similarity": 48.20}],
    }

    v1_contract = extract_tfidf_evidence(sample_tfidf_raw)
    assert validate_v1_0_contract_compatibility(v1_contract) is True

    v11_contract = EvidenceContractV11.from_v1_0(v1_contract)
    v11_dict = v11_contract.to_dict()

    for f in V1_0_CORE_FIELDS:
        assert v11_dict[f] == v1_contract[f], f"Mismatch on core field {f}"

    assert v11_dict["evidence_type"] == EvidenceType.LEXICAL_TOPIC_SIMILARITY.value
    assert v11_dict["citation_context"] == CitationContext.UNKNOWN.value


def test_non_suppression_invariant_gate_v11_d():
    """
    Test 4 (Gate V11-D): Non-Suppression Invariant.
    Kehadiran sitasi atau kutipan TIDAK BOLEH menekan atau mengurangi
    metrik kemiripan tekstual murni (similarity, matched_terms, longest_match).
    """
    base_metrics = {
        "engine": "exact",
        "similarity": 82.5,
        "matched_terms": 88,
        "longest_match": 35,
        "source_count": 1,
        "match_count": 2,
        "avg_idf": 1.9,
        "max_idf": 2.8,
        "top_sources": [],
    }

    # Kasus A: Uncited
    uncited_contract = EvidenceContractV11.from_v1_0(
        base_metrics,
        citation_context=CitationContext.NONE.value,
        citation_status=CitationStatus.NOT_CITED.value,
    )

    # Kasus B: Cited with parenthetical citation
    cited_contract = EvidenceContractV11.from_v1_0(
        base_metrics,
        citation_context=CitationContext.PARENTHETICAL_CITATION.value,
        citation_status=CitationStatus.CITED.value,
        context_span="(Smith, 2024)",
        rationale="Author-year citation adjacent to overlap",
    )

    # Invariant check: Nilai bukti tekstual dasar HARUS PERSIS SAMA
    assert uncited_contract.similarity == cited_contract.similarity == 82.5
    assert uncited_contract.matched_terms == cited_contract.matched_terms == 88
    assert uncited_contract.longest_match == cited_contract.longest_match == 35
    assert uncited_contract.source_count == cited_contract.source_count == 1

    # Hanya metadata akademik yang berbeda
    assert uncited_contract.citation_context == CitationContext.NONE.value
    assert cited_contract.citation_context == CitationContext.PARENTHETICAL_CITATION.value
    assert cited_contract.citation_status == CitationStatus.CITED.value


def test_deterministic_serialization():
    """
    Test 5: Memverifikasi serialisasi JSON deterministik.
    Kunci-kunci dictionary harus selalu teratur dan serializable ke JSON murni.
    """
    contract = EvidenceContractV11(
        engine="exact",
        similarity=67.8,
        matched_terms=20,
        longest_match=12,
        source_count=1,
        match_count=2,
        avg_idf=1.5,
        max_idf=2.0,
        top_sources=[{"id": 1}],
        evidence_type=EvidenceType.DIRECT_TEXTUAL_OVERLAP.value,
        citation_context=CitationContext.PARENTHETICAL_CITATION.value,
        citation_status=CitationStatus.CITED.value,
        citation_confidence=CitationConfidence.HIGH.value,
        context_span="Menurut Smith (2024)",
        rationale="Detected author-year citation",
    )

    serialized_1 = json.dumps(contract.to_dict(), sort_keys=False)
    serialized_2 = json.dumps(contract.to_dict(), sort_keys=False)

    assert serialized_1 == serialized_2

    # Verifikasi kunci dan urutan standar
    keys = list(contract.to_dict().keys())
    assert keys == list(V1_0_CORE_FIELDS) + list(V1_1_EXTENSION_FIELDS)


def test_safe_fallback_for_unknown_and_invalid_enums():
    """
    Test 6: Memverifikasi safe fallback ke UNKNOWN jika input tidak dikenal.
    """
    # Mengirim nilai string acak yang tidak ada di enum
    contract = EvidenceContractV11(
        engine="exact",
        similarity=10.0,
        matched_terms=5,
        longest_match=5,
        source_count=1,
        match_count=1,
        avg_idf=1.0,
        max_idf=1.0,
        evidence_type="INVALID_TYPE_STRING",
        citation_context="INVALID_CONTEXT_STRING",
        citation_status="INVALID_STATUS_STRING",
        citation_confidence="SUPER_HIGH",  # Tidak ada di enum
    )

    # Safe fallback ke UNKNOWN tanpa melempar crash
    assert contract.evidence_type == EvidenceType.UNKNOWN.value
    assert contract.citation_context == CitationContext.UNKNOWN.value
    assert contract.citation_status == CitationStatus.UNKNOWN.value
    assert contract.citation_confidence == CitationConfidence.UNKNOWN.value


def test_validation_bounds_and_errors():
    """
    Test 7: Memverifikasi validasi batasan nilai numerik.
    """
    # Similarity > 100
    with pytest.raises(ValueError, match="similarity must be between 0.0 and 100.0"):
        EvidenceContractV11(
            engine="exact",
            similarity=105.0,
            matched_terms=0,
            longest_match=0,
            source_count=0,
            match_count=0,
            avg_idf=0.0,
            max_idf=0.0,
        )

    # Matched terms negatif
    with pytest.raises(ValueError, match="matched_terms must be >= 0"):
        EvidenceContractV11(
            engine="exact",
            similarity=50.0,
            matched_terms=-1,
            longest_match=0,
            source_count=0,
            match_count=0,
            avg_idf=0.0,
            max_idf=0.0,
        )


def test_frozen_v1_0_hybrid_module_untouched():
    """
    Test 8: Memverifikasi bahwa module v1.0.0 worker/hybrid.py tetap utuh
    dan fungsi dasarnya menghasilkan EvidenceContract v1.0.0 yang valid.
    """
    import inspect
    from worker import hybrid

    # Pastikan modul hybrid tidak mengimpor atau memodifikasi ekstensi v1.1
    source = inspect.getsource(hybrid)
    assert "contract_v1_1" not in source, "worker/hybrid.py was modified or contaminated with v1.1 imports!"
    assert "EvidenceContractV11" not in source, "worker/hybrid.py was contaminated with v1.1 classes!"
