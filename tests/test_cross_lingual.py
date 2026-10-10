"""
Unit Test Suite untuk Modul worker/cross_lingual.py
Memverifikasi fungsionalitas Cross-Lingual Semantic Similarity Engine (ID <-> EN)
"""

import pytest
from worker.cross_lingual import (
    detect_language,
    lemmatize_english_token as lemmatize_token,
    project_text_to_canonical_concepts,
    generate_unordered_concept_pairs,
    calculate_cross_lingual_similarity,
)


def test_detect_language():
    id_text = "Penelitian ini dilakukan di sekolah dasar untuk menganalisis pengaruh kepemimpinan guru terhadap motivasi siswa."
    en_text = "This research was conducted in elementary schools to analyze the impact of teacher leadership on student motivation."

    assert detect_language(id_text) == "ID"
    assert detect_language(en_text) == "EN"


def test_lemmatize_token():
    assert lemmatize_token("findings") == "finding"
    assert lemmatize_token("interviews") == "interview"
    assert lemmatize_token("methodologies") == "methodology"
    assert lemmatize_token("analysis") == "analysis"


def test_project_text_to_canonical_concepts():
    text_id = "Penelitian kualitatif ini menganalisis etika kepemimpinan gereja dan pertumbuhan jemaat."
    concepts = project_text_to_canonical_concepts(text_id)

    assert "research" in concepts
    assert "qualitative" in concepts
    assert "leadership" in concepts
    assert "church" in concepts
    assert "growth" in concepts


def test_generate_unordered_concept_pairs():
    concepts = ["research", "leadership", "church"]
    pairs = generate_unordered_concept_pairs(concepts, window_size=2)

    assert "leadership--research" in pairs
    assert "church--research" in pairs
    assert "church--leadership" in pairs


def test_calculate_cross_lingual_similarity_direct_translation():
    original_english = (
        "This research investigates the relationship between pastoral leadership ethics and local church growth. "
        "The qualitative methodology utilizes in-depth interviews and observational case studies across urban congregations. "
        "The findings demonstrate that spiritual character competence has a significant influence on congregation transformation."
    )

    translated_indonesian = (
        "Penelitian ini menyelidiki hubungan antara etika kepemimpinan pastoral dan pertumbuhan gereja lokal. "
        "Metodologi kualitatif menggunakan wawancara mendalam dan studi kasus observasi pada jemaat perkotaan. "
        "Temuan menunjukkan bahwa kompetensi karakter rohani memiliki pengaruh yang signifikan terhadap transformasi jemaat."
    )

    res = calculate_cross_lingual_similarity(translated_indonesian, original_english)

    assert res["is_cross_lingual_match"] is True
    assert res["confidence"] in ["HIGH", "MODERATE"]
    assert res["cross_lingual_score"] >= 35.0
    assert res["shared_concept_count"] >= 10
    assert res["shared_pairs_count"] >= 15
    assert "leadership" in res["matched_concepts"]
    assert "church" in res["matched_concepts"]


def test_calculate_cross_lingual_similarity_unrelated():
    original_english = (
        "This research investigates the relationship between pastoral leadership ethics and local church growth."
    )

    unrelated_indonesian = (
        "Pertanian padi organik di desa Sukamaju menggunakan sistem pengairan tradisional dan pupuk kandang alami."
    )

    res = calculate_cross_lingual_similarity(unrelated_indonesian, original_english)

    assert res["is_cross_lingual_match"] is False
    assert res["confidence"] == "NONE"
    assert res["cross_lingual_score"] < 10.0


def test_calculate_cross_lingual_similarity_same_language_id_id():
    id_doc1 = (
        "Penelitian ini menggunakan metode kuantitatif untuk menguji hipotesis hubungan antara motivasi belajar dan hasil belajar siswa."
    )
    id_doc2 = (
        "Kajian ini meneliti pengaruh faktor kepemimpinan terhadap kinerja pegawai dengan analisis regresi dan pengujian hipotesis."
    )

    res = calculate_cross_lingual_similarity(id_doc1, id_doc2)

    # Dokumen dengan bahasa sama (ID <-> ID) tidak boleh menghasilkan skor cross-lingual semu
    assert res["is_cross_lingual_match"] is False
    assert res["confidence"] == "NONE"
    assert res["cross_lingual_score"] == 0.0
    assert "Bukan Pasangan Lintas Bahasa" in res["verdict_label"]


def test_calculate_cross_lingual_similarity_unrelated_academic_id_en():
    id_generic = (
        "Penelitian ini bertujuan untuk menganalisis data menggunakan metode kuantitatif. "
        "Hasil penelitian menunjukkan adanya pengaruh yang signifikan antara variabel independen dan dependen. "
        "Kesimpulan kajian ini memberikan implikasi teoritis dan praktis bagi pengembangan kurikulum sekolah dasar."
    )
    en_unrelated = (
        "The research aims to analyze data using quantitative methods. "
        "The study investigates leadership factors on employee performance with statistical hypothesis testing."
    )

    res = calculate_cross_lingual_similarity(id_generic, en_unrelated)

    # Kecocokan konsep metodologi generik secara kebetulan tidak boleh dilabeli MODERATE atau terjemahan langsung
    assert res["is_cross_lingual_match"] is False
    assert res["confidence"] in ["LOW", "NONE"]
    assert res["cross_lingual_score"] < 40.0


def test_lexicon_multi_mapping_pustaka_and_jaringan():
    from worker.cross_lingual import project_token_to_concepts

    pustaka_concepts = project_token_to_concepts("pustaka")
    assert "literature" in pustaka_concepts
    assert "reference" in pustaka_concepts

    jaringan_concepts = project_token_to_concepts("jaringan")
    assert "tissue" in jaringan_concepts
    assert "network" in jaringan_concepts

