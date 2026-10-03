import pytest
from worker.hybrid import (
    extract_exact_evidence,
    extract_tfidf_evidence,
    aggregate_evidence,
    EvidenceContract
)

def test_extract_exact_evidence():
    raw_exact = {
        "overall_similarity": 81.25,
        "matched_words": 26,
        "longest_match_words": 26,
        "source_count": 1,
        "match_count": 1,
        "matches": [
            {"average_idf": 2.9957, "max_idf": 2.9957}
        ],
        "sources": [{"source_document_id": 101, "similarity": 81.25}]
    }
    ev = extract_exact_evidence(raw_exact)
    assert ev["engine"] == "exact"
    assert ev["similarity"] == 81.25
    assert ev["matched_terms"] == 26
    assert ev["longest_match"] == 26
    assert ev["avg_idf"] == 2.9957
    assert ev["max_idf"] == 2.9957
    assert len(ev["top_sources"]) == 1

def test_extract_tfidf_evidence():
    raw_tfidf = {
        "cosine_similarity": 87.29,
        "shared_terms_count": 28,
        "source_count": 1,
        "match_count": 1,
        "average_shared_term_idf": 2.6045,
        "sources": [{"source_document_id": 101, "cosine_similarity": 87.29}]
    }
    ev = extract_tfidf_evidence(raw_tfidf)
    assert ev["engine"] == "tfidf"
    assert ev["similarity"] == 87.29
    assert ev["matched_terms"] == 28
    assert ev["longest_match"] is None
    assert ev["avg_idf"] == 2.6045

def test_aggregate_verbatim_match():
    exact_ev: EvidenceContract = {
        "engine": "exact",
        "similarity": 100.0,
        "matched_terms": 32,
        "longest_match": 32,
        "source_count": 1,
        "match_count": 1,
        "avg_idf": 2.60,
        "max_idf": 2.99,
        "top_sources": [{"source_document_id": 1}]
    }
    tfidf_ev: EvidenceContract = {
        "engine": "tfidf",
        "similarity": 87.29,
        "matched_terms": 28,
        "longest_match": None,
        "source_count": 1,
        "match_count": 1,
        "avg_idf": 2.60,
        "max_idf": None,
        "top_sources": [{"source_document_id": 1}]
    }
    analysis = aggregate_evidence(exact_ev, tfidf_ev)
    assert analysis["decision"] == "match"
    assert analysis["pattern"] == "VERBATIM_MATCH"
    assert analysis["sources_agree"] is True
    assert "mutlak" in analysis["rationale"]

def test_aggregate_copy_edit():
    exact_ev: EvidenceContract = {
        "engine": "exact",
        "similarity": 81.25,
        "matched_terms": 26,
        "longest_match": 26,
        "source_count": 1,
        "match_count": 1,
        "avg_idf": 2.63,
        "max_idf": 2.99,
        "top_sources": [{"source_document_id": 1}]
    }
    tfidf_ev: EvidenceContract = {
        "engine": "tfidf",
        "similarity": 81.22,
        "matched_terms": 26,
        "longest_match": None,
        "source_count": 1,
        "match_count": 1,
        "avg_idf": 2.63,
        "max_idf": None,
        "top_sources": [{"source_document_id": 1}]
    }
    analysis = aggregate_evidence(exact_ev, tfidf_ev)
    assert analysis["decision"] == "review"
    assert analysis["pattern"] == "COPY_EDIT"
    assert "substansial" in analysis["rationale"]

def test_aggregate_paraphrase():
    exact_ev: EvidenceContract = {
        "engine": "exact",
        "similarity": 0.0,
        "matched_terms": 0,
        "longest_match": 0,
        "source_count": 0,
        "match_count": 0,
        "avg_idf": 0.0,
        "max_idf": 0.0,
        "top_sources": []
    }
    tfidf_ev: EvidenceContract = {
        "engine": "tfidf",
        "similarity": 41.34,
        "matched_terms": 17,
        "longest_match": None,
        "source_count": 1,
        "match_count": 1,
        "avg_idf": 2.6978,
        "max_idf": None,
        "top_sources": [{"source_document_id": 1}]
    }
    analysis = aggregate_evidence(exact_ev, tfidf_ev)
    assert analysis["decision"] == "review"
    assert analysis["pattern"] == "SEMANTIC_PARAPHRASE"
    assert "topikal" in analysis["rationale"]

def test_aggregate_common_phrase():
    exact_ev: EvidenceContract = {
        "engine": "exact",
        "similarity": 52.38,
        "matched_terms": 11,
        "longest_match": 11,
        "source_count": 20,
        "match_count": 20,
        "avg_idf": 0.0,
        "max_idf": 0.0,
        "top_sources": [{"source_document_id": 5}]
    }
    tfidf_ev: EvidenceContract = {
        "engine": "tfidf",
        "similarity": 35.86,
        "matched_terms": 12,
        "longest_match": None,
        "source_count": 20,
        "match_count": 20,
        "avg_idf": 0.35,
        "max_idf": None,
        "top_sources": [{"source_document_id": 5}]
    }
    analysis = aggregate_evidence(exact_ev, tfidf_ev)
    assert analysis["decision"] == "review"
    assert analysis["pattern"] == "COMMON_ACADEMIC_PHRASE"
    assert "klise" in analysis["rationale"]

def test_aggregate_unrelated():
    exact_ev: EvidenceContract = {
        "engine": "exact",
        "similarity": 0.0,
        "matched_terms": 0,
        "longest_match": 0,
        "source_count": 0,
        "match_count": 0,
        "avg_idf": 0.0,
        "max_idf": 0.0,
        "top_sources": []
    }
    tfidf_ev: EvidenceContract = {
        "engine": "tfidf",
        "similarity": 5.73,
        "matched_terms": 4,
        "longest_match": None,
        "source_count": 1,
        "match_count": 1,
        "avg_idf": 1.91,
        "max_idf": None,
        "top_sources": [{"source_document_id": 1}]
    }
    analysis = aggregate_evidence(exact_ev, tfidf_ev)
    assert analysis["decision"] == "no_match"
    assert analysis["pattern"] == "NO_EVIDENCE"

def test_frozen_baseline_contract():
    import json
    from pathlib import Path
    baseline_file = Path(__file__).resolve().parents[1] / "benchmark" / "baseline_hybrid_v1.json"
    assert baseline_file.exists(), "Frozen baseline file must exist"
    
    with open(baseline_file) as f:
        data = json.load(f)
        
    assert data["version"] == "Hybrid v1 (Frozen Baseline)"
    assert data["benchmark_run_id"] == 21
    assert data["total_cases"] == 50
    assert data["metrics"]["macro_f1"] == 1.0
    assert len(data["cases"]) == 50
    assert data["thresholds"]["tfidf_match_similarity"] == 85.0
    assert data["thresholds"]["exact_match_similarity"] == 70.0

