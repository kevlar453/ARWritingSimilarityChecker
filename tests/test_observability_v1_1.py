"""
AR Tilik v1.1 - Unit & Regression Tests for Operational Observability (WP-07)
Memvalidasi:
1. Perhitungan persentil latensi (p50, p90, p95, p99) & sample size guardrail (n >= 30).
2. Kepatuhan SLA overhead parsing sitasi (< 150ms) pada teks hingga 5.000 kata.
3. Integritas pencatatan event terstruktur (JSON Lines) ke berkas log.
4. Verifikasi deterministik invarian non-suppression dan decision invariance.
5. Integritas ringkasan metrik operasional dan quality gates WP-07.
6. Perlindungan keutuhan engine frozen v1.0.0 (worker/hybrid.py).
"""

import os
import sys
import json
import tempfile
from pathlib import Path
import pytest

# Ensure root directory is on PYTHONPATH
ROOT_DIR = Path(__file__).resolve().parents[1]
if str(ROOT_DIR) not in sys.path:
    sys.path.insert(0, str(ROOT_DIR))

from worker.observability import (
    calculate_percentiles,
    benchmark_citation_overhead,
    log_observability_event,
    verify_non_suppression_and_invariance,
    get_operational_observability_summary,
    SLA_TARGET_MAX_LATENCY_MS,
    MIN_SAMPLE_SIZE_FOR_PERCENTILES,
)
from worker.contract_v1_1 import CitationContext, CitationStatus
from worker.evidence_presenter import present_hybrid_report
import worker.hybrid as hybrid_mod


def test_calculate_percentiles_empty_and_small_sample():
    """Memverifikasi guardrail ukuran sampel statistik untuk n < 30."""
    # 1. Kasus kosong
    empty_res = calculate_percentiles([])
    assert empty_res["count"] == 0
    assert empty_res["statistically_valid_p95"] is False
    assert "Belum ada sampel" in empty_res["sample_note"]

    # 2. Kasus sampel kecil (n = 10 < 30)
    small_vals = [float(i) for i in range(1, 11)]
    small_res = calculate_percentiles(small_vals)
    assert small_res["count"] == 10
    assert small_res["statistically_valid_p95"] is False
    assert f"n=10 belum memenuhi syarat" in small_res["sample_note"]
    assert small_res["min_ms"] == 1.0
    assert small_res["max_ms"] == 10.0
    assert small_res["avg_ms"] == 5.5


def test_calculate_percentiles_large_sample_accuracy():
    """Memverifikasi akurasi matematika persentil pada sampel valid (n >= 30)."""
    # 100 nilai dari 1.0 hingga 100.0
    vals = [float(i) for i in range(1, 101)]
    res = calculate_percentiles(vals)

    assert res["count"] == 100
    assert res["statistically_valid_p95"] is True
    assert f"n=100 >= {MIN_SAMPLE_SIZE_FOR_PERCENTILES}" in res["sample_note"]
    assert res["min_ms"] == 1.0
    assert res["max_ms"] == 100.0
    assert res["avg_ms"] == 50.5
    assert res["p50_ms"] == 51.0
    assert res["p90_ms"] == 91.0
    assert res["p95_ms"] == 96.0
    assert res["p99_ms"] == 100.0


def test_citation_overhead_sla_compliance():
    """Memverifikasi overhead parsing sitasi memenuhi SLA target < 150ms pada variasi panjang teks."""
    word_sizes = [50, 250, 500, 1000, 3000, 5000]
    bench = benchmark_citation_overhead(word_counts=word_sizes, iterations=5)

    assert bench["sla_target_ms"] == SLA_TARGET_MAX_LATENCY_MS
    assert bench["is_sla_compliant"] is True
    assert bench["max_observed_avg_ms"] < SLA_TARGET_MAX_LATENCY_MS

    for b in bench["benchmarks"]:
        assert b["sla_passed"] is True
        assert b["avg_ms"] < SLA_TARGET_MAX_LATENCY_MS
        # Overhead bahkan pada 5.000 kata harus jauh di bawah 100ms
        assert b["avg_ms"] < 100.0
        assert b["budget_usage_percent"] < 100.0


def test_structured_event_logging():
    """Memverifikasi pencatatan event observabilitas berbasis JSON Lines."""
    with tempfile.TemporaryDirectory() as tmpdir:
        log_file = Path(tmpdir) / "test_observability.log"

        test_payload = {
            "report_id": 9999,
            "document_id": 8888,
            "engine": "hybrid",
            "predicted_decision": "review",
            "exact_similarity": 15.5,
            "cosine_similarity": 42.0,
            "citation_parsing_ms": 1.25,
            "sla_passed": True,
            "context_type": "INLINE_CITATION",
            "citation_status": "CITED",
            "has_divergence": False,
            "non_suppression_verified": True,
            "decision_invariance_verified": True,
        }

        log_observability_event("SIMILARITY_CHECK_COMPLETED", test_payload, log_file=log_file)

        assert log_file.exists()
        with open(log_file, "r", encoding="utf-8") as f:
            lines = f.readlines()

        assert len(lines) == 1
        record = json.loads(lines[0])
        assert record["event_type"] == "SIMILARITY_CHECK_COMPLETED"
        assert record["report_id"] == 9999
        assert record["document_id"] == 8888
        assert record["citation_parsing_ms"] == 1.25
        assert record["sla_passed"] is True
        assert "timestamp" in record


def test_verify_non_suppression_and_decision_invariance():
    """Memverifikasi detector anomali terhadap penekanan bukti atau mutasi keputusan."""
    # 1. Kasus valid identik
    rep_dict = {
        "exact_similarity": 75.0,
        "cosine_similarity": 88.0,
        "predicted_decision": "match",
    }
    presentation = present_hybrid_report(rep_dict)

    res = verify_non_suppression_and_invariance(
        v1_exact=75.0,
        v1_cosine=88.0,
        v1_decision="match",
        presentation=presentation
    )
    assert res["exact_unsuppressed"] is True
    assert res["cosine_unsuppressed"] is True
    assert res["non_suppression_verified"] is True
    assert res["decision_invariance_verified"] is True
    assert res["all_invariants_passed"] is True

    # 2. Kasus pelanggaran penekanan metrik (mutasi nilai exact)
    res_tampered_exact = verify_non_suppression_and_invariance(
        v1_exact=50.0,  # Tidak cocok dengan presentation (75.0)
        v1_cosine=88.0,
        v1_decision="match",
        presentation=presentation
    )
    assert res_tampered_exact["exact_unsuppressed"] is False
    assert res_tampered_exact["non_suppression_verified"] is False
    assert res_tampered_exact["all_invariants_passed"] is False

    # 3. Kasus pelanggaran invarian keputusan (mutasi keputusan)
    res_tampered_decision = verify_non_suppression_and_invariance(
        v1_exact=75.0,
        v1_cosine=88.0,
        v1_decision="review",  # Tidak cocok dengan presentation (match)
        presentation=presentation
    )
    assert res_tampered_decision["decision_invariance_verified"] is False
    assert res_tampered_decision["all_invariants_passed"] is False


def test_operational_observability_summary_quality_gates():
    """Memverifikasi kelengkapan metrik operasional dan status Quality Gates WP-07."""
    summary = get_operational_observability_summary()

    # Periksa struktur kunci
    assert "total_inspected_reports" in summary
    assert "v11_telemetry_count" in summary
    assert "citation_parsing_latency" in summary
    assert "total_pipeline_latency" in summary
    assert "context_distribution" in summary
    assert "status_distribution" in summary
    assert "divergence" in summary
    assert "non_suppression" in summary
    assert "quality_gates" in summary

    # Verifikasi seluruh jenis enum terwakili
    for ctx in CitationContext:
        assert ctx.value in summary["context_distribution"]

    for stat in CitationStatus:
        assert stat.value in summary["status_distribution"]

    # Verifikasi Quality Gates WP-07
    qg = summary["quality_gates"]
    assert qg["V11-SLA"] == "PASS"
    assert qg["V11-NON_SUPPRESSION"] == "PASS"
    assert qg["V11-DECISION_INVARIANCE"] == "PASS"
    assert summary["is_sla_compliant"] is True
    assert summary["non_suppression"]["invariant_guaranteed"] is True


def test_frozen_v1_0_engine_untouched():
    """Memverifikasi secara ketat bahwa worker/hybrid.py dan baseline threshold tetap terkunci (FROZEN)."""
    assert hybrid_mod.DEFAULT_EXACT_MATCH_SIMILARITY == 70.0
    assert hybrid_mod.DEFAULT_EXACT_REVIEW_SIMILARITY == 10.0
    assert hybrid_mod.DEFAULT_TFIDF_MATCH_SIMILARITY == 85.0
    assert hybrid_mod.DEFAULT_TFIDF_REVIEW_SIMILARITY == 10.0
    assert hybrid_mod.DEFAULT_EXACT_MATCH_LONGEST == 50
    assert hybrid_mod.DEFAULT_EXACT_REVIEW_LONGEST == 10
    assert hybrid_mod.DEFAULT_SIGNIFICANT_MIN_IDF == 0.5
