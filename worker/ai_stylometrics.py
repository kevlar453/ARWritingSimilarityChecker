"""
AR Tilik - Stylometric & AI-Generated Text Indicator Engine
Modul analisis gaya bahasa (stylometry) dan indikator teks buatan AI (LLM / QuillBot / Spinner)
secara objektif, terukur, dan berbasis keadilan akademik tanpa tuduhan sepihak.
"""

import math
import re
from typing import Dict, List, Any, Optional

# Kumpulan penanda transisi dan frasa klise yang lazim digenerasi oleh LLM (Bahasa Indonesia & Inggris)
AI_TRANSITIONAL_MARKERS = {
    "bahasa_indonesia": [
        "dalam era digital saat ini",
        "perlu digarisbawahi bahwa",
        "menarik untuk dicermati",
        "tidak dapat dipungkiri bahwa",
        "memiliki peran yang sangat krusial",
        "seiring dengan perkembangan zaman",
        "dapat disimpulkan bahwa secara keseluruhan",
        "pada dasarnya hal ini menunjukkan",
        "merupakan aspek yang tak terpisahkan",
        "memberikan kontribusi yang signifikan",
        "membuka ruang bagi terciptanya",
        "secara holistik dan komprehensif",
        "merupakan batu loncatan penting",
        "dalam konteks yang lebih luas",
        "menjadi katalisator utama",
        "terlebih lagi dalam dinamika",
        "patut menjadi catatan penting",
        "sebagai kesimpulan akhir dapat dinyatakan"
    ],
    "english": [
        "furthermore",
        "moreover",
        "in conclusion",
        "it is important to note that",
        "plays a crucial role in",
        "delves into",
        "serves as a testament to",
        "a multifaceted approach",
        "it is worth noting that",
        "in today's digital era",
        "tapestry of",
        "beacon of hope",
        "fosters a deeper understanding",
        "highlights the significance of",
        "navigating the complexities of"
    ]
}


def tokenize_sentences(text: str) -> List[str]:
    """Memecah teks menjadi daftar kalimat yang bersih."""
    # Split berdasarkan tanda titik, tanda tanya, atau tanda seru
    raw_sentences = re.split(r"[.!?]+(?:\s+|\n+|$)", text)
    sentences = []
    for s in raw_sentences:
        s_clean = s.strip()
        # Ambil kalimat yang memiliki setidaknya 3 kata
        if len(s_clean.split()) >= 3:
            sentences.append(s_clean)
    return sentences


def calculate_burstiness(sentence_lengths: List[int]) -> float:
    """
    Menghitung Sentence Burstiness (variabilitas panjang kalimat).
    Manusia: Burstiness tinggi (variasi panjang kalimat beragam, CV > 0.45).
    AI: Burstiness rendah (panjang kalimat sangat seragam, CV < 0.28).
    Formula: Koefisien Variasi (Standard Deviation / Mean).
    """
    if len(sentence_lengths) < 3:
        return 0.5  # Netral jika sampel kalimat terlalu sedikit

    mean_length = sum(sentence_lengths) / len(sentence_lengths)
    if mean_length == 0:
        return 0.0

    variance = sum((x - mean_length) ** 2 for x in sentence_lengths) / len(sentence_lengths)
    std_dev = math.sqrt(variance)
    coefficient_of_variation = std_dev / mean_length
    return round(coefficient_of_variation, 4)


def calculate_lexical_diversity(words: List[str]) -> float:
    """
    Menghitung TTR (Type-Token Ratio) dan kekayaan leksikal.
    Teks manusia yang alami memiliki rasio hapax legomena (kata yang muncul sekali) yang lebih organik.
    """
    if not words:
        return 0.0
    clean_words = [re.sub(r"[^\w]", "", w.lower()) for w in words if len(w) > 1]
    if not clean_words:
        return 0.0

    total_tokens = len(clean_words)
    unique_types = len(set(clean_words))
    # Normalized Root-TTR untuk mengompensasi panjang naskah
    root_ttr = unique_types / math.sqrt(total_tokens)
    return round(root_ttr, 4)


def calculate_ai_transition_density(text: str, total_words: int) -> float:
    """
    Menghitung kepadatan frasa transisi klise AI per 1.000 kata.
    """
    if total_words == 0:
        return 0.0

    lower_text = text.lower()
    marker_hits = 0

    all_markers = AI_TRANSITIONAL_MARKERS["bahasa_indonesia"] + AI_TRANSITIONAL_MARKERS["english"]
    for marker in all_markers:
        marker_hits += len(re.findall(r"\b" + re.escape(marker) + r"\b", lower_text))

    density_per_thousand = (marker_hits / total_words) * 1000
    return round(density_per_thousand, 3)


def detect_spun_synonym_anomalies(text: str) -> List[Dict[str, Any]]:
    """
    Mendeteksi anomali kata parafrase canggung khas spinner/QuillBot.
    (Misalnya: kombinasi kata sifat dan kata benda yang tidak lazim dalam kaidah bahasa Indonesia ilmiah).
    """
    anomalies = []
    # Pola frasa canggung hasil terjemahan/sinonim otomatis
    awkward_patterns = [
        (r"\b(penelitian yang luar biasa signifikan)\b", "Substitusi sinonim hiperbolis tidak wajar"),
        (r"\b(secara substansial berdampak pada)\b", "Kalkir parafrase mesin"),
        (r"\b(hasil penyelidikan ini mengutarakan)\b", "Substitusi kata kerja canggung ('penyelidikan' alih-alih 'penelitian')"),
        (r"\b(informasi yang didapatkan mengungkapkan)\b", "Pola parafrase generik"),
    ]
    for pattern, desc in awkward_patterns:
        matches = re.finditer(pattern, text, re.IGNORECASE)
        for m in matches:
            anomalies.append({
                "phrase": m.group(1),
                "rationale": desc,
                "position": m.start()
            })
    return anomalies


def analyze_ai_stylometrics(text: str) -> Dict[str, Any]:
    """
    Menganalisis profil stylometri naskah secara komprehensif dan menghasilkan
    indikator kemungkinan naskah disusun oleh AI atau mesin parafrase.
    """
    if not text or len(text.strip()) < 50:
        return {
            "status": "insufficient_data",
            "ai_probability_score": 0.0,
            "ai_confidence_level": "LOW",
            "verdict_label": "Data Teks Tidak Mencukupi",
            "summary_rationale": "Panjang naskah terlalu pendek untuk dianalisis profil stylometrinya.",
            "metrics": {
                "burstiness": 0.5,
                "lexical_diversity": 0.0,
                "transition_density": 0.0,
                "average_sentence_length": 0.0
            },
            "anomalies": []
        }

    sentences = tokenize_sentences(text)
    sentence_lengths = [len(s.split()) for s in sentences]
    words = text.split()
    total_words = len(words)

    avg_sentence_len = sum(sentence_lengths) / len(sentence_lengths) if sentence_lengths else 0
    burstiness = calculate_burstiness(sentence_lengths)
    lex_diversity = calculate_lexical_diversity(words)
    transition_density = calculate_ai_transition_density(text, total_words)
    anomalies = detect_spun_synonym_anomalies(text)

    # Bobot Penilaian AI Probability (0.0 s/d 100.0)
    # 1. Burstiness rendah (< 0.28) menyumbang indikasi AI tinggi (+35 poin)
    ai_score = 0.0
    if burstiness < 0.25:
        ai_score += 40.0
    elif burstiness < 0.32:
        ai_score += 25.0
    elif burstiness < 0.40:
        ai_score += 10.0

    # 2. Transisi klise AI tinggi (> 2.5 per 1000 kata) menyumbang +30 poin
    if transition_density >= 3.5:
        ai_score += 35.0
    elif transition_density >= 2.0:
        ai_score += 20.0
    elif transition_density >= 1.0:
        ai_score += 10.0

    # 3. Keseragaman panjang kalimat rata-rata khas LLM (18-28 kata)
    if 18 <= avg_sentence_len <= 28 and burstiness < 0.35:
        ai_score += 15.0

    # 4. Deteksi parafrase canggung / spinner
    if anomalies:
        ai_score += min(len(anomalies) * 5.0, 15.0)

    # Normalisasi batas 0 - 100
    ai_score = round(min(max(ai_score, 0.0), 100.0), 2)

    # Klasifikasi Tingkat Indikasi
    if ai_score >= 65.0:
        confidence = "HIGH"
        label = "Pola Susunan Terstruktur Khas AI (Tinggi)"
        rationale = (
            f"Naskah menunjukkan variasi panjang kalimat yang sangat seragam (Burstiness: {burstiness:.2f}) "
            f"disertai kepadatan frasa transisi generik ({transition_density:.1f}/1000 kata). "
            f"Disarankan penguji mengonfirmasi pemahaman konseptual mahasiswa secara lisan."
        )
    elif ai_score >= 38.0:
        confidence = "MODERATE"
        label = "Indikasi Parafrase AI Ringan / Berbantuan Mesin"
        rationale = (
            f"Ditemukan beberapa segmen dengan keteraturan sintaksis khas mesin (Burstiness: {burstiness:.2f}). "
            f"Naskah kemungkinan menggunakan alat bantu parafrase atau penyempurnaan kalimat digital."
        )
    else:
        confidence = "LOW"
        label = "Gaya Bahasa Alami Manusia (Organik)"
        rationale = (
            f"Struktur kalimat memiliki variasi ritme yang dinamis dan organik (Burstiness: {burstiness:.2f}) "
            f"dengan sebaran leksikal alami khas penulisan manusia mandiri."
        )

    return {
        "status": "completed",
        "ai_probability_score": ai_score,
        "ai_confidence_level": confidence,
        "verdict_label": label,
        "summary_rationale": rationale,
        "metrics": {
            "burstiness": burstiness,
            "lexical_diversity": lex_diversity,
            "transition_density": transition_density,
            "average_sentence_length": round(avg_sentence_len, 2),
            "total_sentences_analyzed": len(sentences),
            "total_words_analyzed": total_words
        },
        "anomalies": anomalies
    }


if __name__ == "__main__":
    sample_ai_text = (
        "Dalam era digital saat ini, pendidikan karakter memiliki peran yang sangat krusial dalam membentuk moralitas generasi muda. "
        "Tidak dapat dipungkiri bahwa perkembangan teknologi membuka ruang bagi terciptanya tantangan etika yang kompleks. "
        "Oleh karena itu, implementasi kurikulum holistik merupakan aspek yang tak terpisahkan dari keberhasilan sistem persekolahan. "
        "Furthermore, kolaborasi antara pendidik dan orang tua memberikan kontribusi yang signifikan dalam mencapai tujuan tersebut. "
        "Sebagai kesimpulan akhir dapat dinyatakan bahwa penguatan nilai-nilai luhur menjadi katalisator utama masa depan bangsa."
    )
    res = analyze_ai_stylometrics(sample_ai_text)
    print("=== HASIL ANALISIS STYLOMETRI AI ===")
    print(f"Probabilitas AI : {res['ai_probability_score']}% ({res['ai_confidence_level']})")
    print(f"Label           : {res['verdict_label']}")
    print(f"Alasan          : {res['summary_rationale']}")
    print(f"Metrik          : {res['metrics']}")
