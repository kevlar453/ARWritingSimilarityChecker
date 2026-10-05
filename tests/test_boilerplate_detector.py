"""
Unit tests untuk AR Tilik v1.2 Academic Boilerplate Detector.
Memastikan formulasi baku metodologi, prosedur statistik, transisi ilmiah,
dan formula administratif terdeteksi secara akurat dan objektif.
"""

import pytest
from worker.boilerplate_detector import (
    BoilerplateCategory,
    detect_academic_boilerplate,
)


def test_empty_or_whitespace():
    res = detect_academic_boilerplate("")
    assert res.is_boilerplate is False
    assert res.category == BoilerplateCategory.NONE

    res2 = detect_academic_boilerplate("   \n\t  ")
    assert res2.is_boilerplate is False
    assert res2.category == BoilerplateCategory.NONE


def test_statistical_analysis_detection():
    # 1. SPSS
    t1 = "Analisis data diolah dengan bantuan software IBM SPSS Statistics 26 for Windows."
    res1 = detect_academic_boilerplate(t1)
    assert res1.is_boilerplate is True
    assert res1.category == BoilerplateCategory.STATISTICAL_ANALYSIS
    assert "SPSS" in res1.matched_phrase

    # 2. Cronbach Alpha
    t2 = "Uji reliabilitas menggunakan koefisien Cronbach's Alpha dengan batas 0.70."
    res2 = detect_academic_boilerplate(t2)
    assert res2.is_boilerplate is True
    assert res2.category == BoilerplateCategory.STATISTICAL_ANALYSIS

    # 3. Corrected Item-Total Correlation
    t3 = "Validitas instrumen dinilai melalui uji validitas menggunakan Corrected Item-Total Correlation."
    res3 = detect_academic_boilerplate(t3)
    assert res3.is_boilerplate is True
    assert res3.category == BoilerplateCategory.STATISTICAL_ANALYSIS

    # 4. Likert Scale
    t4 = "Kuesioner disusun menggunakan skala Likert lima tingkat dari sangat tidak setuju hingga sangat setuju."
    res4 = detect_academic_boilerplate(t4)
    assert res4.is_boilerplate is True
    assert res4.category == BoilerplateCategory.STATISTICAL_ANALYSIS


def test_methodology_procedure_detection():
    # 1. Mixed Methods Sequential Explanatory
    t1 = "Penelitian ini menggunakan pendekatan mixed methods dengan desain sequential explanatory."
    res1 = detect_academic_boilerplate(t1)
    assert res1.is_boilerplate is True
    assert res1.category == BoilerplateCategory.METHODOLOGY_PROCEDURE

    # 2. Purposive Sampling
    t2 = "Teknik pengambilan sampel dilakukan secara purposive sampling berdasarkan kriteria inklusi."
    res2 = detect_academic_boilerplate(t2)
    assert res2.is_boilerplate is True
    assert res2.category == BoilerplateCategory.METHODOLOGY_PROCEDURE


def test_academic_transition_detection():
    # Frasa 6 kata yang ditemukan pada tesis Emiliana Vivi
    t1 = "Berdasarkan hasil penelitian yang telah dilakukan, dapat dipahami bahwa korelasi bersifat signifikan."
    res1 = detect_academic_boilerplate(t1)
    assert res1.is_boilerplate is True
    assert res1.category == BoilerplateCategory.ACADEMIC_TRANSITION
    assert "berdasarkan hasil penelitian yang telah dilakukan" in res1.matched_phrase.lower()

    # Dapat disimpulkan bahwa
    t2 = "Dengan demikian, dapat disimpulkan bahwa pertumbuhan spiritual berperan sebagai mediator."
    res2 = detect_academic_boilerplate(t2)
    assert res2.is_boilerplate is True
    assert res2.category == BoilerplateCategory.ACADEMIC_TRANSITION


def test_administrative_formula_detection():
    # Pernyataan keaslian
    t1 = "Menyatakan dengan sesungguhnya bahwa tesis ini benar-benar merupakan hasil karya saya sendiri."
    res1 = detect_academic_boilerplate(t1)
    assert res1.is_boilerplate is True
    assert res1.category == BoilerplateCategory.ADMINISTRATIVE_FORMULA

    # Kata pengantar
    t2 = "Puji dan syukur penulis panjatkan ke hadirat Tuhan Yang Maha Esa atas berkat dan rahmat-Nya."
    res2 = detect_academic_boilerplate(t2)
    assert res2.is_boilerplate is True
    assert res2.category == BoilerplateCategory.ADMINISTRATIVE_FORMULA


def test_author_original_substantive_text():
    # Teks orisinal gagasan teologi/filsafat yang bukan template
    t = "Ekaristi mengalirkan daya transformatif yang mengakar pada keheningan batin kaum muda di Paroki Beduai."
    res = detect_academic_boilerplate(t)
    assert res.is_boilerplate is False
    assert res.category == BoilerplateCategory.NONE
    assert res.matched_phrase is None
