"""
AR Tilik v1.1 - Test Suite for EvaluatorV11 and Evaluation Runner (WP-05)
Memvalidasi Quality Gates V11-B:
- V11-B1: Akurasi deteksi konteks akademik >= 95%
- V11-B2: Akurasi status atribusi sitasi >= 95%
- V11-B3: Non-suppression invariant 100% (metrik v1.0 tidak berubah)
- V11-B4: Decision invariance 100% (keputusan hybrid v1.0 tidak berubah oleh konteks)
- V11-B5: Zero confirmed engine defects
- Pembuktian eksperimental isolasi label post-hoc (Gate D4)
- Validasi taksonomi kegagalan akademik
"""

import copy
import json
from pathlib import Path
import pytest

from evaluation.evaluator_v1_1 import EvaluatorV11, EvaluationResultV11, TARGET_DATASET_DIR
from evaluation.schema import (
    EvaluationCase,
    CASE_TYPE_TARGETED_V1_1,
    FAILURE_NONE,
    FAILURE_ENGINE_DEFECT,
    FAILURE_LIM_NO_CONTEXT_AWARENESS,
    FAILURE_BOUNDARY_BEHAVIOR,
)


@pytest.fixture(scope="module")
def evaluator():
    runner = EvaluatorV11()
    runner.initialize_corpus_index()
    return runner


@pytest.fixture(scope="module")
def suite_report(evaluator):
    return evaluator.run_suite(sync_db=False)


def test_evaluator_runs_all_80_cases_and_generates_reports(evaluator, suite_report):
    """
    Memverifikasi bahwa runner mengevaluasi tepat 80 kasus dan menghasilkan
    report.json dan results.json yang valid.
    """
    assert suite_report["global_metrics"]["total_cases"] == 80
    assert len(suite_report["group_breakdown"]) == 8

    results_file = TARGET_DATASET_DIR / "results.json"
    report_file = TARGET_DATASET_DIR / "report.json"

    assert results_file.exists(), f"results.json not found at {results_file}"
    assert report_file.exists(), f"report.json not found at {report_file}"

    with open(results_file, "r", encoding="utf-8") as f:
        results = json.load(f)
    assert len(results) == 80

    with open(report_file, "r", encoding="utf-8") as f:
        report_data = json.load(f)
    assert report_data["system_version"] == "v1.1.0"
    assert report_data["baseline_version"] == "v1.0.0 (FROZEN)"


def test_gate_d4_post_hoc_label_isolation_experimental_proof(evaluator):
    """
    Pembuktian Eksperimental Gate D4:
    Menjalankan runner berulang kali pada teks yang sama dengan variasi expected_class,
    expected_context, dan expected_citation_status yang berbeda secara ekstrem.
    Membuktikan bahwa output engine aktual 100% independen dari metadata ekspektasi.
    """
    cases = evaluator.load_cases()
    base_case = cases[0]  # EV11-UNC-001

    case_as_match = copy.deepcopy(base_case)
    case_as_match.expected_class = "match"
    case_as_match.expected_context = "NONE"
    case_as_match.expected_citation_status = "NOT_CITED"

    case_as_review = copy.deepcopy(base_case)
    case_as_review.expected_class = "review"
    case_as_review.expected_context = "DIRECT_QUOTE"
    case_as_review.expected_citation_status = "QUOTE"

    case_as_no_match = copy.deepcopy(base_case)
    case_as_no_match.expected_class = "no_match"
    case_as_no_match.expected_context = "BIBLIOGRAPHY"
    case_as_no_match.expected_citation_status = "UNKNOWN"

    res1 = evaluator.run_case(case_as_match)
    res2 = evaluator.run_case(case_as_review)
    res3 = evaluator.run_case(case_as_no_match)

    # Output aktual engine HARUS 100% identik
    assert res1.predicted_class == res2.predicted_class == res3.predicted_class
    assert res1.predicted_context == res2.predicted_context == res3.predicted_context
    assert res1.predicted_citation_status == res2.predicted_citation_status == res3.predicted_citation_status
    assert res1.exact_similarity == res2.exact_similarity == res3.exact_similarity
    assert res1.cosine_similarity == res2.cosine_similarity == res3.cosine_similarity
    assert res1.predicted_evidence_type == res2.predicted_evidence_type == res3.predicted_evidence_type

    # Hanya status perbandingan post-hoc yang berbeda
    assert res1.decision_match is True
    assert res2.decision_match is False
    assert res3.decision_match is False


def test_gate_v11_b3_non_suppression_invariant_100_percent(suite_report):
    """
    Gate V11-B3: Non-Suppression Invariant.
    Pada 100% kasus uji (80/80), metrik Exact dan Cosine v1.0.0 tidak mengalami distorsi.
    """
    assert suite_report["quality_gates"]["gate_v11_b3_non_suppression_invariance_100"] is True
    assert suite_report["global_metrics"]["non_suppression_invariance_pct"] == 100.0


def test_gate_v11_b4_decision_invariance_100_percent(suite_report):
    """
    Gate V11-B4: Decision Invariance.
    Keputusan sistem v1.0.0 (MATCH / REVIEW / NO_MATCH) 100% terjaga dan tidak
    dimutasi oleh kehadiran anotasi sitasi.
    """
    assert suite_report["quality_gates"]["gate_v11_b4_decision_invariance_100"] is True
    assert suite_report["global_metrics"]["decision_invariance_pct"] == 100.0


def test_gate_v11_b1_and_b2_context_and_citation_status_accuracy(suite_report):
    """
    Gate V11-B1 & V11-B2: Akurasi deteksi konteks sitasi dan status atribusi >= 95%.
    """
    assert suite_report["quality_gates"]["gate_v11_b1_context_accuracy_ge_95"] is True
    assert suite_report["quality_gates"]["gate_v11_b2_citation_status_ge_95"] is True
    assert suite_report["global_metrics"]["context_detection_accuracy_pct"] == 100.0
    assert suite_report["global_metrics"]["citation_status_accuracy_pct"] == 100.0


def test_gate_v11_b5_zero_engine_defects_and_mismatch_taxonomy(suite_report):
    """
    Gate V11-B5: Zero Confirmed Engine Defects.
    Tidak ada cacat logika engine. Seluruh mismatch teratribusi secara objektif ke:
    1. ENGINE_LIMITATION:NO_CONTEXT_AWARENESS (10 kasus metodologi)
    2. BOUNDARY_BEHAVIOR:THRESHOLD_TRANSITION (4 kasus ambang batas)
    """
    assert suite_report["quality_gates"]["gate_v11_b5_engine_defects_zero"] is True
    dist = suite_report["failure_taxonomy_distribution"]
    assert dist.get(FAILURE_ENGINE_DEFECT, 0) == 0

    # 10 kasus metodologi boilerplate
    assert dist.get(FAILURE_LIM_NO_CONTEXT_AWARENESS) == 10
    # 4 kasus transisi boundary (1 paraphrase + 3 distractor dilation)
    assert dist.get(FAILURE_BOUNDARY_BEHAVIOR) == 4


def test_group_breakdown_consistency(suite_report):
    """
    Memverifikasi kepatuhan metrik untuk setiap 8 kelompok.
    """
    breakdown = suite_report["group_breakdown"]
    assert len(breakdown) == 8

    # Kelompok 1, 2, 3, 4, 7 harus 100% decision match
    perfect_decision_groups = [1, 2, 3, 4, 7]
    for g in breakdown:
        if g["group_index"] in perfect_decision_groups:
            assert g["decision_accuracy_pct"] == 100.0, f"Group {g['group_name']} decision accuracy != 100%"
        assert g["context_accuracy_pct"] == 100.0, f"Group {g['group_name']} context accuracy != 100%"
        assert g["citation_status_accuracy_pct"] == 100.0, f"Group {g['group_name']} citation status accuracy != 100%"
        assert g["decision_invariant_pct"] == 100.0
        assert g["non_suppression_pct"] == 100.0


def test_divergence_detection_group_7(evaluator):
    """
    Memverifikasi bahwa kasus kelompok 7 (Exact = 0 + high TF-IDF) secara konsisten
    menghasilkan deteksi divergensi bukti pada presentasi v1.1.
    """
    cases = evaluator.load_cases()
    group_7_cases = [c for c in cases if c.metadata.get("group_index") == 7]
    assert len(group_7_cases) == 10

    for c in group_7_cases:
        res = evaluator.run_case(c)
        assert res.has_divergence is True, f"Divergence not detected in {c.case_id}"
        assert res.exact_similarity <= 0.05
        assert res.cosine_similarity > 0.0
        assert res.predicted_evidence_type == "LEXICAL_TOPIC_SIMILARITY"
