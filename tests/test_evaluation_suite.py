"""
AR Tilik - Tests for Evaluation Dataset Generator, Runner, and Human Review Queue
"""

import json
from pathlib import Path
from evaluation.dataset_generator import generate_full_evaluation_dataset, DATASET_DIR
from evaluation.evaluator import EvaluationRunner
from evaluation.reviewer import select_informative_cases, tag_case_cli
from evaluation.schema import EvaluationCase
from benchmark.database import get_connection

def test_evaluation_generator_500_cases():
    summary = generate_full_evaluation_dataset()
    assert summary["total_cases"] == 500
    assert summary["seed_cases"] == 50
    assert summary["mutation_cases"] == 250
    assert summary["boundary_cases"] == 100
    assert summary["adversarial_cases"] == 100
    
    cases_file = DATASET_DIR / "cases.json"
    assert cases_file.exists()
    with open(cases_file, "r") as f:
        cases = json.load(f)
    assert len(cases) == 500

def test_evaluation_runner_isolated():
    # Periksa jumlah dokumen produksi sebelum evaluasi
    conn = get_connection()
    cursor = conn.cursor(dictionary=True)
    cursor.execute("SELECT COUNT(*) as cnt FROM documents")
    docs_before = cursor.fetchone()["cnt"]
    cursor.execute("SELECT COUNT(*) as cnt FROM reports")
    reports_before = cursor.fetchone()["cnt"]
    cursor.execute("SELECT COUNT(*) as cnt FROM jobs")
    jobs_before = cursor.fetchone()["cnt"]
    cursor.close()
    conn.close()

    # Jalankan evaluasi pada subset 10 kasus uji
    cases_file = DATASET_DIR / "cases.json"
    with open(cases_file, "r") as f:
        cases = [EvaluationCase.from_dict(d) for d in json.load(f)[:10]]

    runner = EvaluationRunner()
    report = runner.run_suite(cases, sync_db=True)
    assert report["seed"]["total"] == 10
    assert report["seed"]["pass"] == 10

    # Pastikan tabel produksi TIDAK terpolusi sama sekali
    conn = get_connection()
    cursor = conn.cursor(dictionary=True)
    cursor.execute("SELECT COUNT(*) as cnt FROM documents")
    docs_after = cursor.fetchone()["cnt"]
    cursor.execute("SELECT COUNT(*) as cnt FROM reports")
    reports_after = cursor.fetchone()["cnt"]
    cursor.execute("SELECT COUNT(*) as cnt FROM jobs")
    jobs_after = cursor.fetchone()["cnt"]
    cursor.close()
    conn.close()

    assert docs_before == docs_after
    assert reports_before == reports_after
    assert jobs_before == jobs_after

def test_quality_gates():
    # Muat seluruh kasus dan jalankan
    cases_file = DATASET_DIR / "cases.json"
    with open(cases_file, "r") as f:
        cases = [EvaluationCase.from_dict(d) for d in json.load(f)]

    runner = EvaluationRunner()
    report = runner.run_suite(cases, sync_db=False)
    
    # Gate A: Mutasi >= 95%
    assert report["quality_gates"]["gate_a_mutation_pass"] is True
    assert report["mutation"]["pass_rate"] >= 95.0
    
    # Gate B: Boundary deterministik
    assert report["quality_gates"]["gate_b_boundary_deterministic"] is True
    assert report["boundary"]["pass_rate"] >= 90.0

    # Gate C: 0 Unexpected MATCH pada kasus adversarial
    assert report["quality_gates"]["gate_c_adversarial_zero_unexpected_match"] is True
    assert report["unexpected_match_adversarial"] == 0

def test_human_reviewer_selection_and_tagging():
    cases_file = DATASET_DIR / "cases.json"
    results_file = DATASET_DIR / "results.json"
    with open(cases_file, "r") as f:
        cases = json.load(f)
    with open(results_file, "r") as f:
        results = json.load(f)

    queue = select_informative_cases(cases, results, max_cases=35)
    assert len(queue) == 35
    # Pastikan prioritas teratas mencakup unexpected atau citation
    reasons = [q["priority_reason"] for q in queue]
    assert any("Citation" in r or "Unexpected" in r for r in reasons)

    # Test tagging
    tag_case_cli(
        case_id="EV-ADV-051",
        reviewer_code="RV-TEST",
        human_decision="review",
        label="Properly cited",
        system_assessment="CORRECT",
        notes="Uji anotasi otomatis"
    )

    conn = get_connection()
    cursor = conn.cursor(dictionary=True)
    cursor.execute("SELECT * FROM evaluation_labels WHERE case_id = 'EV-ADV-051'")
    row = cursor.fetchone()
    cursor.close()
    conn.close()

    assert row is not None
    assert row["reviewer_code"] == "RV-TEST"
    assert row["properly_cited"] == 1
    assert row["system_assessment"] == "CORRECT"
