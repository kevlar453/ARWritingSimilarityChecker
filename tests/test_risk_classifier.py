"""
Unit Test Suite untuk worker/risk_classifier.py
Memverifikasi fungsionalitas inferensi dan explainability model XGBoost Academic Risk Synthesizer.
"""

import pytest
from worker.risk_classifier import (
    extract_features_vector,
    predict_academic_risk_xgboost,
    FEATURE_NAMES,
)


def test_extract_features_vector_dimension():
    features = extract_features_vector(
        exact_sim=10.0,
        cosine_sim=20.0,
        longest_match_words=15,
        avg_idf=1.5,
        shared_terms_count=10,
        source_count=1,
        burstiness=0.45,
        transition_density=1.0,
        cross_lingual_score=20.0,
        boilerplate_ratio=0.1,
        quote_ratio=0.2,
    )
    assert len(features) == len(FEATURE_NAMES)
    assert len(features) == 11


def test_predict_academic_risk_original_document():
    # Dokumen orisinal mandiri
    res = predict_academic_risk_xgboost(
        exact_sim=0.0,
        cosine_sim=5.0,
        longest_match_words=0,
        avg_idf=0.0,
        shared_terms_count=2,
        source_count=1,
        burstiness=0.55,
        transition_density=0.2,
        cross_lingual_score=0.0,
        boilerplate_ratio=0.0,
        quote_ratio=0.0,
    )
    assert res["status"] == "completed"
    assert res["risk_level"] == "LOW"
    assert res["probabilities"]["p_original"] > res["probabilities"]["p_high_risk"]


def test_predict_academic_risk_high_verbatim():
    # Dokumen penjiplakan sekuensial panjang tanpa kutipan
    res = predict_academic_risk_xgboost(
        exact_sim=85.0,
        cosine_sim=88.0,
        longest_match_words=160,
        avg_idf=4.5,
        shared_terms_count=150,
        source_count=3,
        burstiness=0.30,
        transition_density=1.0,
        cross_lingual_score=0.0,
        boilerplate_ratio=0.0,
        quote_ratio=0.0,
    )
    assert res["status"] == "completed"
    assert res["risk_level"] == "HIGH"
    assert res["probabilities"]["p_high_risk"] > 0.5
    assert any("Pasase kontigu panjang" in d for d in res["explainable_drivers"])
