"""
Unit Test Suite untuk Modul worker/ai_stylometrics.py
Memverifikasi fungsionalitas pengukuran Stylometri & Indikator Teks AI (Burstiness, TTR, Marker Klise, Spun Text)
"""

import pytest
from worker.ai_stylometrics import (
    tokenize_sentences,
    calculate_burstiness,
    calculate_lexical_diversity,
    calculate_ai_transition_density,
    detect_spun_synonym_anomalies,
    analyze_ai_stylometrics,
)


def test_tokenize_sentences():
    text = "Ini adalah kalimat pertama. Kalimat kedua cukup panjang dan berisi analisis mendalam! Apakah ini kalimat ketiga? Ya tentu saja."
    sentences = tokenize_sentences(text)
    assert len(sentences) >= 3
    assert "Ini adalah kalimat pertama" in sentences[0]


def test_calculate_burstiness_human_vs_ai():
    # Pola AI: Kalimat seragam (panjang mirip)
    ai_lengths = [20, 21, 20, 22, 19, 21, 20]
    ai_cv = calculate_burstiness(ai_lengths)

    # Pola Manusia: Kalimat dinamis (ada sangat pendek, ada sangat panjang)
    human_lengths = [6, 35, 12, 48, 8, 26, 4]
    human_cv = calculate_burstiness(human_lengths)

    assert ai_cv < human_cv
    assert ai_cv < 0.20
    assert human_cv > 0.50


def test_calculate_lexical_diversity():
    repetitive_words = ["penelitian", "penelitian", "data", "data", "penelitian"] * 10
    diverse_words = ["metodologi", "kuantitatif", "analisis", "variabel", "populasi", "sampel", "regresi", "signifikansi", "hipotesis", "kesimpulan"]

    ttr_rep = calculate_lexical_diversity(repetitive_words)
    ttr_div = calculate_lexical_diversity(diverse_words)

    assert ttr_div > ttr_rep


def test_calculate_ai_transition_density():
    ai_dense_text = (
        "Dalam era digital saat ini, tidak dapat dipungkiri bahwa pendidikan karakter memiliki peran yang sangat krusial. "
        "Furthermore, perlu digarisbawahi bahwa kolaborasi orang tua memberikan kontribusi yang signifikan."
    )
    density = calculate_ai_transition_density(ai_dense_text, len(ai_dense_text.split()))
    assert density > 0.0


def test_detect_spun_synonym_anomalies():
    spun_text = "Berdasarkan evaluasi, ini merupakan penelitian yang luar biasa signifikan dan hasil penyelidikan ini mengutarakan kebenaran."
    anomalies = detect_spun_synonym_anomalies(spun_text)
    assert len(anomalies) >= 2
    assert any("penelitian yang luar biasa signifikan" in a["phrase"] for a in anomalies)


def test_analyze_ai_stylometrics_comprehensive():
    # Teks tipikal AI dengan kalimat seragam dan frasa transisi
    ai_text = (
        "Dalam era digital saat ini, pendidikan karakter memiliki peran yang sangat krusial dalam membentuk moralitas generasi muda. "
        "Tidak dapat dipungkiri bahwa perkembangan teknologi membuka ruang bagi terciptanya tantangan etika yang kompleks dan beragam. "
        "Oleh karena itu implementasi kurikulum holistik merupakan aspek yang tak terpisahkan dari keberhasilan sistem persekolahan kita. "
        "Furthermore kolaborasi antara pendidik dan orang tua memberikan kontribusi yang signifikan dalam mencapai cita-cita pendidikan. "
        "Sebagai kesimpulan akhir dapat dinyatakan bahwa penguatan nilai-nilai luhur menjadi katalisator utama masa depan bangsa Indonesia."
    )
    result = analyze_ai_stylometrics(ai_text)
    assert result["status"] == "completed"
    assert result["ai_probability_score"] >= 40.0
    assert result["verdict_label"] in [
        "Pola Susunan Terstruktur Khas AI (Tinggi)",
        "Indikasi Parafrase AI Ringan / Berbantuan Mesin"
    ]
    assert "burstiness" in result["metrics"]
    assert "transition_density" in result["metrics"]


def test_analyze_ai_stylometrics_insufficient_text():
    short_text = "Teks terlalu pendek."
    result = analyze_ai_stylometrics(short_text)
    assert result["status"] == "insufficient_data"
    assert result["ai_probability_score"] == 0.0
