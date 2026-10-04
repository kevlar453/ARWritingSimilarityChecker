"""
AR Tilik v1.1 - Test Suite for Human Validation & Expert Review Layer (WP-06)
Memvalidasi:
- Kuota 30 kasus terprioritas pada 6 tier (5 kasus per tier)
- Kepatuhan protokol Blind Review (HR-UI Gate 4: expected synthetic labels disembunyikan)
- Kelengkapan 6 dimensi telaah manusia (human_decision, citation_context, citation_status,
  human_interpretation, system_assessment, reviewer_confidence)
- Integritas taksonomi interpretasi akademik
- Validasi laporan telaah manusia (human_review_report_v1_1.json)
"""

import json
from pathlib import Path
import pytest

from evaluation.reviewer_v1_1 import (
    get_human_review_v11_set,
    generate_human_review_v11_report,
    DATASET_V11_DIR,
    HUMAN_REVIEW_30_V11_CONFIG,
)
from evaluation.schema import (
    SYSTEM_ASSESSMENT_CORRECT,
    SYSTEM_ASSESSMENT_ACCEPTABLE,
    HUMAN_INTERPRETATION_METHODOLOGY_BOILERPLATE,
    HUMAN_INTERPRETATION_NEEDS_INVESTIGATION,
    HUMAN_INTERPRETATION_COMMON_ACADEMIC_LANGUAGE,
    HUMAN_INTERPRETATION_DIRECT_OVERLAP_PROPERLY_CITED,
    HUMAN_INTERPRETATION_DIRECT_OVERLAP_RELEVANT,
)


@pytest.fixture(scope="module")
def review_set():
    return get_human_review_v11_set(sync_file=False)


@pytest.fixture(scope="module")
def review_report():
    return generate_human_review_v11_report()


def test_human_review_30_set_count_and_tier_distribution(review_set):
    """
    Memverifikasi bahwa antrean validasi manusia v1.1 terdiri dari tepat 30 kasus,
    terdistribusi seimbang pada 6 tier prioritas (masing-masing 5 kasus).
    """
    assert len(review_set) == 30, f"Expected 30 cases, got {len(review_set)}"

    tier_counts = {}
    for item in review_set:
        tier = item["priority_tier"]
        tier_counts[tier] = tier_counts.get(tier, 0) + 1

    assert len(tier_counts) == 6, f"Expected 6 tiers, got {len(tier_counts)}"
    for tier_name, count in tier_counts.items():
        assert count == 5, f"Tier '{tier_name}' expected 5 cases, got {count}"


def test_blind_review_protocol_gate_4(review_set):
    """
    HR-UI Gate 4 (Blind Review Protocol):
    Memverifikasi secara mutlak bahwa expected synthetic labels TIDAK PERNAH
    dibocorkan kepada penelaah manusia.
    """
    forbidden_keys = (
        "expected_class",
        "expected_pattern",
        "expected_context",
        "expected_citation_status",
        "expected_evidence_type",
        "generation_method",
        "mutation_operator",
    )

    for item in review_set:
        for key in forbidden_keys:
            assert key not in item, f"Gate 4 violation: '{key}' leaked in case {item.get('case_id')}!"

        # Reviewer harus dapat melihat output sistem aktual dan bukti teks
        assert "predicted_class" in item
        assert "predicted_pattern" in item
        assert "exact_similarity" in item
        assert "cosine_similarity" in item
        assert "submission_text" in item
        assert "source_text" in item
        assert "priority_tier" in item
        assert "priority_reason" in item


def test_human_review_completion_and_agreement(review_report):
    """
    Memverifikasi bahwa seluruh 30 kasus telah selesai ditelaah dengan
    tingkat kesepakatan penelaah (Agreement Rate) 100% (CORRECT / ACCEPTABLE).
    """
    assert review_report["total_cases"] == 30
    assert review_report["reviewed_cases"] == 30
    assert review_report["pending_cases"] == 0
    assert review_report["agreement_rate_pct"] == 100.0

    assessments = review_report["system_assessment_distribution"]
    assert assessments.get("TOO_STRICT", 0) == 0
    assert assessments.get("TOO_LENIENT", 0) == 0
    assert assessments.get("UNCERTAIN", 0) == 0
    assert assessments.get(SYSTEM_ASSESSMENT_CORRECT, 0) == 24
    assert assessments.get(SYSTEM_ASSESSMENT_ACCEPTABLE, 0) == 5 + 1  # 5 methodology + 1 boundary


def test_academic_interpretation_taxonomy_coherence(review_set):
    """
    Memverifikasi koherensi taksonomi interpretasi akademik penelaah:
    - Tier 1 (Methodology) diklasifikasikan sebagai METHODOLOGY_BOILERPLATE
    - Tier 4 (Distractor) diklasifikasikan sebagai NEEDS_INVESTIGATION
    - Tier 5 (Divergence) diklasifikasikan sebagai COMMON_ACADEMIC_LANGUAGE
    - Tier 3 (Quote/Citation) diklasifikasikan sebagai DIRECT_OVERLAP_PROPERLY_CITED
    """
    for item in review_set:
        hr = item.get("human_review")
        assert hr is not None, f"Case {item['case_id']} is not reviewed"
        tier = item["priority_tier"]
        interp = hr.get("human_interpretation")

        if "Methodology" in tier:
            assert interp == HUMAN_INTERPRETATION_METHODOLOGY_BOILERPLATE
            assert hr["system_assessment"] == SYSTEM_ASSESSMENT_ACCEPTABLE
        elif "Unrelated Citation Distractor" in tier:
            assert interp == HUMAN_INTERPRETATION_NEEDS_INVESTIGATION
            assert hr["system_assessment"] == SYSTEM_ASSESSMENT_CORRECT
        elif "High TF-IDF" in tier and "Tier 5" in tier:
            assert interp == HUMAN_INTERPRETATION_COMMON_ACADEMIC_LANGUAGE
            assert hr["system_assessment"] == SYSTEM_ASSESSMENT_CORRECT
        elif "High Exact + Citation" in tier:
            assert interp == HUMAN_INTERPRETATION_DIRECT_OVERLAP_PROPERLY_CITED
            assert hr["system_assessment"] == SYSTEM_ASSESSMENT_CORRECT


def test_report_and_set_files_on_disk():
    """
    Memverifikasi keberadaan dan validitas berkas JSON validasi manusia di filesystem.
    """
    set_file = DATASET_V11_DIR / "human_review_set_v1_1.json"
    reviews_file = DATASET_V11_DIR / "human_reviews_v1_1.json"
    report_file = DATASET_V11_DIR / "human_review_report_v1_1.json"

    assert set_file.exists()
    assert reviews_file.exists()
    assert report_file.exists()

    with open(set_file, "r", encoding="utf-8") as f:
        data_set = json.load(f)
    with open(reviews_file, "r", encoding="utf-8") as f:
        data_reviews = json.load(f)
    with open(report_file, "r", encoding="utf-8") as f:
        data_rep = json.load(f)

    assert len(data_set) == 30
    assert len(data_reviews) == 30
    assert data_rep["agreement_rate_pct"] == 100.0
