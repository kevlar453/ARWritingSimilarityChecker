import pytest
from benchmark.stress_dataset import generate_stress_dataset
from worker.hybrid import aggregate_evidence, EvidenceContract

def test_generate_stress_dataset():
    dataset = generate_stress_dataset()
    assert len(dataset) == 50
    categories = set(c["category"] for c in dataset)
    assert "needle_haystack" in categories
    assert "mosaic_patchwriting" in categories
    assert "citation_noise" in categories
    assert "false_friends" in categories

def test_needle_in_haystack_hybrid_detection():
    # Simulasi naskah 400 kata dengan pasase 30 kata (exact sim 7.5%, tfidf sim 8.0%)
    exact_ev: EvidenceContract = {
        "engine": "exact",
        "similarity": 7.5,
        "matched_terms": 30,
        "longest_match": 30,
        "source_count": 1,
        "match_count": 1,
        "avg_idf": 2.99,
        "max_idf": 2.9957,
        "top_sources": [{"source_document_id": 1}]
    }
    tfidf_ev: EvidenceContract = {
        "engine": "tfidf",
        "similarity": 8.0,
        "matched_terms": 25,
        "longest_match": None,
        "source_count": 1,
        "match_count": 1,
        "avg_idf": 2.65,
        "max_idf": None,
        "top_sources": [{"source_document_id": 1}]
    }
    analysis = aggregate_evidence(exact_ev, tfidf_ev)
    # Harus tetap review karena longest_match=30 dengan high IDF
    assert analysis["decision"] == "review"
    assert analysis["pattern"] == "PARTIAL_DIRECT_MATCH"

def test_citation_noise_tfidf_recovery():
    # Simulasi gangguan sitasi: exact terpotong (sim 8.0%, longest 4 kata), tapi tfidf cosine kuat (65.0%)
    exact_ev: EvidenceContract = {
        "engine": "exact",
        "similarity": 8.0,
        "matched_terms": 4,
        "longest_match": 4,
        "source_count": 1,
        "match_count": 1,
        "avg_idf": 1.2,
        "max_idf": 1.5,
        "top_sources": [{"source_document_id": 1}]
    }
    tfidf_ev: EvidenceContract = {
        "engine": "tfidf",
        "similarity": 65.0,
        "matched_terms": 22,
        "longest_match": None,
        "source_count": 1,
        "match_count": 1,
        "avg_idf": 2.60,
        "max_idf": None,
        "top_sources": [{"source_document_id": 1}]
    }
    analysis = aggregate_evidence(exact_ev, tfidf_ev)
    assert analysis["decision"] == "review"
    assert analysis["pattern"] == "SEMANTIC_PARAPHRASE"
