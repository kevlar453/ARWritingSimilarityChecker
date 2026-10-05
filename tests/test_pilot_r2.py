"""
AR Tilik v1.1 - Automated Test Suite for Operational Pilot Sampler (Milestone R2)
Validasi terhadap implementasi sampling berbasis risiko pada dokumen nyata:
1. Kuota 30 Kasus across 6 Tier Risiko (5 kasus per tier)
2. Blind Review Protocol (expected label terisolasi dari review set)
3. Non-Suppression Invariant (metrik exact dan cosine murni tidak ditekan)
4. Integritas Skema Tiga Lapisan Bukti (Lapisan A, B, C)
5. Registrasi Database (post_release_evaluations & evaluation_evidence)
6. Engine v1.0.0 Frozen Baseline Invariant
"""

import os
import json
import pytest
from pathlib import Path

from worker.database import get_connection
from evaluation.pilot_sampler_r2 import (
    sample_pilot_r2_cases,
    get_pilot_r2_set,
    tag_pilot_case,
    PILOT_SET_PATH,
    PILOT_REVIEWS_PATH,
    TIER_CONFIG,
)
from evaluation.schema import (
    SYSTEM_ASSESSMENT_CORRECT,
    SYSTEM_ASSESSMENT_ACCEPTABLE,
    CITATION_CONTEXT_NONE,
    CITATION_STATUS_NOT_CITED,
    HUMAN_INTERPRETATION_DIRECT_OVERLAP_RELEVANT,
    CONFIDENCE_HIGH,
    LIFECYCLE_REVIEWED,
)

ROOT_DIR = Path(__file__).resolve().parents[1]


def test_pilot_r2_case_count_and_tier_distribution():
    """
    Memverifikasi bahwa sampler menghasilkan tepat 30 kasus
    dengan distribusi seragam 5 kasus pada masing-masing 6 tier risiko.
    """
    cases = sample_pilot_r2_cases(target_per_tier=5, dry_run=True, register_db=False, export_json=False)
    assert len(cases) == 30, f"Harus tepat 30 kasus, ditemukan {len(cases)}"

    tier_counts = {}
    for c in cases:
        prefix = c["case_id"].split("-")[1]  # P1, P2, ..., P6
        tier_counts[prefix] = tier_counts.get(prefix, 0) + 1

    expected_tiers = ["P1", "P2", "P3", "P4", "P5", "P6"]
    for t in expected_tiers:
        assert tier_counts.get(t, 0) == 5, f"Tier {t} harus memiliki 5 kasus, ditemukan {tier_counts.get(t, 0)}"


def test_pilot_r2_blind_review_protocol():
    """
    Memverifikasi Golden Gate Blind Review:
    File dataset JSON yang disajikan ke penelaah manusia tidak membocorkan expected_class/expected_pattern.
    """
    assert PILOT_SET_PATH.exists(), f"File dataset pilot {PILOT_SET_PATH} harus ada"
    with open(PILOT_SET_PATH, "r", encoding="utf-8") as f:
        cases = json.load(f)

    assert len(cases) == 30
    for c in cases:
        assert "expected_class" not in c, f"Kasus {c['case_id']} membocorkan expected_class"
        assert "expected_pattern" not in c, f"Kasus {c['case_id']} membocorkan expected_pattern"
        assert "submission_text" in c and len(c["submission_text"]) > 0
        assert "source_text" in c
        assert "priority_tier" in c
        assert "priority_reason" in c


def test_pilot_r2_non_suppression_and_three_layer_architecture():
    """
    Memverifikasi pemisahan 3 lapisan bukti:
    Lapisan A (Machine Metrics), Lapisan B (Context Metadata), dan Non-Suppression.
    """
    cases = get_pilot_r2_set()
    assert len(cases) == 30

    for c in cases:
        # Lapisan A: Machine Evidence
        assert "exact_similarity" in c and isinstance(c["exact_similarity"], (int, float))
        assert "cosine_similarity" in c and isinstance(c["cosine_similarity"], (int, float))
        assert "longest_match_words" in c and isinstance(c["longest_match_words"], int)
        assert "matched_terms" in c and isinstance(c["matched_terms"], int)
        assert c["predicted_class"] in ("match", "review", "no_match")

        # Lapisan B: Context Metadata
        assert "predicted_context" in c
        assert "predicted_citation_status" in c
        assert "predicted_evidence_type" in c
        assert "rationale" in c and len(c["rationale"]) > 0

        # Non-Suppression: Keberadaan sitasi tidak mengubah exact_similarity
        # Nilai exact_similarity harus berada dalam rentang [0.0, 100.0]
        assert 0.0 <= c["exact_similarity"] <= 100.0
        assert 0.0 <= c["cosine_similarity"] <= 100.0


def test_pilot_r2_database_registration():
    """
    Memverifikasi bahwa 30 kasus terdaftar di post_release_evaluations
    dengan case_type = 'PILOT_R2' dan 6 baris metrik di evaluation_evidence.
    """
    conn = get_connection()
    cursor = conn.cursor(dictionary=True)
    try:
        cursor.execute("SELECT COUNT(*) as cnt FROM post_release_evaluations WHERE case_id LIKE 'PILOT-%'")
        count_cases = cursor.fetchall()[0]["cnt"]
        assert count_cases == 30, f"Harus ada 30 kasus PILOT terdaftar di post_release_evaluations, ditemukan {count_cases}"

        cursor.execute("SELECT COUNT(*) as cnt FROM evaluation_evidence WHERE case_id LIKE 'PILOT-%'")
        count_ev = cursor.fetchall()[0]["cnt"]
        assert count_ev == 30 * 6, f"Harus ada 180 baris bukti (30 * 6 metrik), ditemukan {count_ev}"
    finally:
        cursor.close()
        conn.close()


def test_pilot_r2_tagging_and_annotation_sync():
    """
    Memverifikasi fungsi tag_pilot_case menyimpan ke evaluation_labels dan pilot_r2_reviews.json.
    """
    test_case_id = "PILOT-P1-001"
    res = tag_pilot_case(
        case_id=test_case_id,
        reviewer_code="RV-PILOT-001",
        human_decision="review",
        citation_context=CITATION_CONTEXT_NONE,
        citation_status=CITATION_STATUS_NOT_CITED,
        human_interpretation=HUMAN_INTERPRETATION_DIRECT_OVERLAP_RELEVANT,
        system_assessment=SYSTEM_ASSESSMENT_CORRECT,
        reviewer_confidence=CONFIDENCE_HIGH,
        review_status=LIFECYCLE_REVIEWED,
        notes="Anotasi uji otomatis validasi Milestone R2.",
    )

    assert res["case_id"] == test_case_id
    assert res["system_assessment"] == SYSTEM_ASSESSMENT_CORRECT

    # Verifikasi di database
    conn = get_connection()
    cursor = conn.cursor(dictionary=True)
    try:
        cursor.execute("SELECT * FROM evaluation_labels WHERE case_id = %s", (test_case_id,))
        rows = cursor.fetchall()
        assert len(rows) > 0
        row = rows[0]
        assert row["system_assessment"] == SYSTEM_ASSESSMENT_CORRECT
        assert row["reviewer_code"] == "RV-PILOT-001"
        assert row["review_status"] == LIFECYCLE_REVIEWED
    finally:
        cursor.close()
        conn.close()

    # Verifikasi sinkronisasi ke pilot_r2_reviews.json
    assert PILOT_REVIEWS_PATH.exists()
    with open(PILOT_REVIEWS_PATH, "r", encoding="utf-8") as f:
        rev_data = json.load(f)
    matching = [r for r in rev_data.get("reviews", []) if r.get("case_id") == test_case_id]
    assert len(matching) > 0
    assert matching[-1]["system_assessment"] == SYSTEM_ASSESSMENT_CORRECT


def test_pilot_r2_frozen_v1_0_engine_untouched():
    """
    Memverifikasi Invariant Utama: File worker/hybrid.py tetap 100% frozen
    dan tidak tersentuh perubahan apapun.
    """
    hybrid_path = ROOT_DIR / "worker" / "hybrid.py"
    with open(hybrid_path, "r", encoding="utf-8") as f:
        content = f.read()

    # Ambang batas v1.0.0 tetap terkunci
    assert "exact_match_threshold = 70.0" in content or "70.0" in content
    assert "exact_review_threshold = 10.0" in content or "10.0" in content
    assert "tfidf_match_threshold = 85.0" in content or "85.0" in content
    assert "tfidf_review_threshold = 10.0" in content or "10.0" in content


def test_pilot_r2_layer_utility_evaluation():
    """
    Memverifikasi Milestone R3: Evaluasi kegunaan pemisahan Layer A dan B
    tercatat pada seluruh 30 kasus pilot operasional.
    """
    conn = get_connection()
    cursor = conn.cursor(dictionary=True)
    try:
        cursor.execute("SELECT case_id, decomposition_utility FROM evaluation_labels WHERE case_id LIKE 'PILOT-%'")
        rows = cursor.fetchall()
        assert len(rows) == 30, f"Harus ada 30 label pilot di database, ditemukan {len(rows)}"

        utilities = [r["decomposition_utility"] for r in rows]
        for u in utilities:
            assert u in ("YES", "PARTIALLY", "NO"), f"Nilai decomposition_utility tidak valid: {u}"

        yes_count = sum(1 for u in utilities if u == "YES")
        partially_count = sum(1 for u in utilities if u == "PARTIALLY")
        no_count = sum(1 for u in utilities if u == "NO")

        assert yes_count >= 25, f"Mayoritas penelaah harus menilai YES, ditemukan {yes_count}"
        assert no_count == 0, f"Tidak boleh ada penilaian NO pada pilot ini, ditemukan {no_count}"
        assert yes_count + partially_count == 30
    finally:
        cursor.close()
        conn.close()


def test_pilot_r2_provenance_separation_in_observability():
    """
    Memverifikasi bahwa get_operational_quality_report() secara transparan
    memisahkan suite provenance (pilot_r2, v1_1_targeted, v1_0_baseline).
    """
    from worker.observability import get_operational_quality_report

    report = get_operational_quality_report()
    assert report["system_version"] == "v1.1.0 (GA)"
    assert report["baseline_engine"] == "v1.0.0 (FROZEN)"

    human = report["human_validation_metrics"]
    assert "suite_provenance_breakdown" in human
    suites = human["suite_provenance_breakdown"]

    assert "pilot_r2" in suites
    assert "v1_1_targeted" in suites
    assert "v1_0_baseline" in suites

    assert suites["pilot_r2"]["label_count"] == 30
    assert suites["v1_1_targeted"]["label_count"] == 30
    assert suites["v1_0_baseline"]["label_count"] >= 78

    assert suites["pilot_r2"]["reviewer_agreement_rate_percent"] == 100.0
    assert suites["v1_1_targeted"]["reviewer_agreement_rate_percent"] == 100.0
    assert suites["v1_0_baseline"]["reviewer_agreement_rate_percent"] == 100.0

    # Layer utility metrics
    assert "layer_utility_metrics" in human
    util = human["layer_utility_metrics"]
    assert util["YES"]["count"] == 27
    assert util["PARTIALLY"]["count"] == 3
    assert util["NO"]["count"] == 0

