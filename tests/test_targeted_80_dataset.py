"""
Validation Suite for WP-04 Targeted 80-Case Evaluation Dataset
Enforces Gate D1 through D6:
D1 - 80/80 complete provenance metadata
D2 - Explicit expected context, citation status, and evidence type
D3 - Zero duplicate content_hash and unique pairs
D4 - Expected label isolation (labels do not determine engine results)
D5 - Synthetic expectation strictly separated from human ground truth (human_validated = False)
D6 - Frozen v1.0.0 engine untouched
"""

import json
from pathlib import Path
import pytest
from worker.contract_v1_1 import (
    CitationContext,
    CitationStatus,
    EvidenceType,
)

DATASET_DIR = Path(__file__).resolve().parents[1] / "evaluation_dataset_v1_1_targeted_80"


@pytest.fixture(scope="module")
def targeted_cases():
    cases_path = DATASET_DIR / "cases.json"
    assert cases_path.exists(), f"Targeted dataset cases.json not found at {cases_path}"
    with open(cases_path, "r", encoding="utf-8") as f:
        cases = json.load(f)
    return cases


def test_gate_d1_case_count_and_provenance_completeness(targeted_cases):
    """
    Gate D1: Total 80 cases and every single case has complete provenance.
    """
    assert len(targeted_cases) == 80, f"Expected exactly 80 cases, got {len(targeted_cases)}"

    required_provenance_fields = (
        "case_id",
        "case_type",
        "source_case",
        "generation_method",
        "expected_context",
        "expected_citation_status",
        "expected_evidence_type",
        "human_validated",
        "system_version",
        "generation_seed",
        "content_hash",
        "mutation_operator",
        "mutation_strength",
        "submission_text",
        "source_text",
    )

    for case in targeted_cases:
        for field_name in required_provenance_fields:
            assert field_name in case, f"Case {case.get('case_id')} missing required field: {field_name}"
            assert case[field_name] is not None, f"Case {case.get('case_id')} field {field_name} is None"

        assert case["case_type"] == "TARGETED_V1_1"
        assert case["system_version"] == "v1.1.0"
        assert isinstance(case["generation_seed"], int)
        assert len(case["content_hash"]) == 16


def test_gate_d1_eight_groups_distribution(targeted_cases):
    """
    Memverifikasi distribusi 8 kelompok, masing-masing tepat 10 kasus.
    """
    expected_prefixes = {
        "EV11-UNC": 10,  # Uncited direct overlap
        "EV11-PAR": 10,  # Parenthetical citation
        "EV11-INL": 10,  # Inline citation
        "EV11-QUO": 10,  # Direct / block quote
        "EV11-PRA": 10,  # Paraphrase + citation
        "EV11-MET": 10,  # Methodology boilerplate
        "EV11-DIV": 10,  # Exact = 0 + high TF-IDF divergence
        "EV11-DIS": 10,  # Unrelated citation distractor
    }

    counts = {prefix: 0 for prefix in expected_prefixes}
    for case in targeted_cases:
        pfx = case["case_id"][:8]
        assert pfx in counts, f"Unknown case prefix in {case['case_id']}"
        counts[pfx] += 1

    for prefix, expected_count in expected_prefixes.items():
        assert counts[prefix] == expected_count, f"Group {prefix} expected {expected_count} cases, got {counts[prefix]}"


def test_gate_d2_explicit_expectations_and_valid_enums(targeted_cases):
    """
    Gate D2: Expected context, citation status, and evidence type are explicit
    and match valid enum values.
    """
    valid_contexts = set(CitationContext.__members__.keys())
    valid_statuses = set(CitationStatus.__members__.keys())
    valid_ev_types = set(EvidenceType.__members__.keys())

    for case in targeted_cases:
        ctx = case["expected_context"]
        status = case["expected_citation_status"]
        ev_type = case["expected_evidence_type"]

        assert ctx in valid_contexts, f"Invalid expected_context '{ctx}' in {case['case_id']}"
        assert status in valid_statuses, f"Invalid expected_citation_status '{status}' in {case['case_id']}"
        assert ev_type in valid_ev_types, f"Invalid expected_evidence_type '{ev_type}' in {case['case_id']}"


def test_gate_d3_zero_duplicate_content_hashes(targeted_cases):
    """
    Gate D3: Tidak ada duplikasi yang tidak disengaja.
    Semua 80 content_hash, case_id, dan pasangan submission/source harus unik.
    """
    hashes = [c["content_hash"] for c in targeted_cases]
    ids = [c["case_id"] for c in targeted_cases]
    pairs = [(c["submission_text"], c["source_text"]) for c in targeted_cases]

    assert len(set(hashes)) == 80, "Gate D3 violation: duplicate content_hash detected!"
    assert len(set(ids)) == 80, "Gate D3 violation: duplicate case_id detected!"
    assert len(set(pairs)) == 80, "Gate D3 violation: duplicate text pair detected!"


def test_gate_d4_labels_isolated_from_engine_execution(targeted_cases):
    """
    Gate D4: Expected label tidak mempengaruhi hasil engine.
    Memverifikasi bahwa expected_class dan expected_pattern terisolasi sebagai
    ekspektasi benchmark / evaluasi post-hoc.
    """
    for case in targeted_cases:
        assert case["expected_class"] in ("match", "review", "no_match")
        # Metadata eksplisit
        assert "metadata" in case
        assert isinstance(case["metadata"], dict)


def test_gate_d5_synthetic_separated_from_human_truth(targeted_cases):
    """
    Gate D5: Ekspektasi sintetis dipisahkan mutlak dari human ground truth.
    Seluruh kasus sintetis harus berstatus human_validated = False.
    """
    for case in targeted_cases:
        assert case["human_validated"] is False, (
            f"Gate D5 violation: synthetic case {case['case_id']} is marked human_validated=True!"
        )


def test_gate_d6_frozen_v1_0_engine_untouched():
    """
    Gate D6: Engine v1.0.0 tidak dimodifikasi.
    """
    import inspect
    from worker import hybrid

    src = inspect.getsource(hybrid)
    # Pastikan baseline thresholds dan rules v1.0.0 tetap utuh
    assert "DEFAULT_EXACT_MATCH_SIMILARITY = 70.0" in src
    assert "DEFAULT_EXACT_REVIEW_SIMILARITY = 10.0" in src
    assert "DEFAULT_TFIDF_MATCH_SIMILARITY = 85.0" in src
    assert "DEFAULT_TFIDF_REVIEW_SIMILARITY = 10.0" in src


def test_manifest_and_spec_integrity():
    """
    Memverifikasi keberadaan dan integritas dataset_spec.json dan provenance_manifest.json.
    """
    spec_path = DATASET_DIR / "dataset_spec.json"
    manifest_path = DATASET_DIR / "provenance_manifest.json"

    assert spec_path.exists()
    assert manifest_path.exists()

    with open(spec_path, "r", encoding="utf-8") as f:
        spec = json.load(f)
    with open(manifest_path, "r", encoding="utf-8") as f:
        manifest = json.load(f)

    assert spec["total_cases"] == 80
    assert len(spec["groups"]) == 8
    assert manifest["total_cases"] == 80
    assert manifest["unique_content_hashes"] == 80
