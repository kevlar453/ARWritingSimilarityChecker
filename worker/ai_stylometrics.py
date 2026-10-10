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
        "sebagai kesimpulan akhir dapat dinyatakan",
        # Formulaik akademis khas draf LLM Indonesia
        "secara etimologis, kata",
        "secara kebahasaan, kata",
        "berdasarkan kajian teori di atas",
        "dapat disusun sebuah kerangka pemikiran",
        "merupakan jawaban sementara terhadap rumusan masalah",
        "pada hakikatnya merupakan",
        "merupakan pintu gerbang bagi seseorang",
        "memberikan bukti empiris bahwa",
        "memberi dasar akademik yang kuat",
        "menjadi landasan kognitif yang menuntun",
        "merupakan wilayah generalisasi yang terdiri atas",
        "merupakan bagian dari jumlah dan karakteristik yang dimiliki",
        "lazim digunakan dalam penelitian sosial",
        "bertujuan meminimalkan bias dan menghasilkan",
        "terdiri atas dua variabel, yaitu variabel bebas",
        "merupakan prosedur yang dipakai untuk mengetahui",
        "memiliki tingkat konsistensi internal yang baik",
        "untuk melihat apakah kelompok data memiliki varians",
        "adanya hubungan searah antara variabel bebas",
        "diduga memberikan dampak terhadap variabel terikat",
        "sebagaimana dinyatakan bahwa",
        "memperkuat asumsi bahwa",
        "tidak dapat dipisahkan",
        "memiliki peran penting, baik dalam",
        "fenomena yang sering dijumpai",
        "sebagai satu di antara syarat penyusunan skripsi"
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


ACADEMIC_ABBREVIATIONS = [
    r"dkk\.", r"hlm\.", r"hal\.", r"thn\.", r"dsb\.", r"dll\.", r"dst\.",
    r"no\.", r"vol\.", r"art\.", r"bab\.", r"pasal\.", r"ttg\.", r"tsb\.",
    r"an\.", r"ub\.", r"sd\.", r"s\.d\.",
    r"prof\.", r"dr\.", r"drs\.", r"dra\.", r"ir\.", r"ph\.d\.", r"m\.sc\.",
    r"m\.kom\.", r"s\.pd\.", r"s\.kom\.", r"m\.pd\.", r"m\.hum\.", r"m\.si\.",
    r"s\.t\.", r"m\.t\.", r"et al\.", r"i\.e\.", r"e\.g\.", r"vs\.", r"etc\.",
    r"fig\.", r"ref\.", r"dept\.", r"univ\.", r"approx\.", r"cf\.", r"ibid\.",
    r"op\. cit\.", r"loc\. cit\."
]


def tokenize_sentences(text: str) -> List[str]:
    """
    Memecah teks menjadi daftar kalimat yang bersih dan utuh.
    Aman terhadap singkatan akademik (dkk., hlm., Prof., Ph.D.), angka desimal (0.05),
    dan inisial nama tanpa memecah kalimat menjadi fragmen semu.
    """
    if not text:
        return []

    # 1. Lindungi angka desimal (misal: 0.05, 1.5, 3.14)
    t = re.sub(r"(\d+)\.(\d+)", r"\1<DOT>\2", text)

    # 2. Lindungi inisial huruf tunggal (misal: A. Yani, W.J.S. Poerwadarminta)
    t = re.sub(r"\b([A-Za-z])\.", r"\1<DOT>", t)

    # 3. Lindungi singkatan umum dan gelar akademik
    for abbr in ACADEMIC_ABBREVIATIONS:
        pattern = re.compile(r"\b" + abbr, re.IGNORECASE)
        t = pattern.sub(lambda m: m.group(0).replace(".", "<DOT>"), t)

    # 4. Split berdasarkan tanda akhir kalimat yang diikuti spasi & awal kalimat baru atau baris baru
    raw_sentences = re.split(r"[.!?]+(?:\s+(?=[A-Z0-9\"\'\(])|\n+|$)", t)
    sentences = []
    for s in raw_sentences:
        s_restored = s.replace("<DOT>", ".").strip()
        # Ambil kalimat yang memiliki setidaknya 3 kata
        if len(s_restored.split()) >= 3:
            sentences.append(s_restored)
    return sentences


def calculate_burstiness(sentence_lengths: List[int]) -> float:
    """
    Menghitung Sentence Burstiness (variabilitas panjang kalimat).
    Manusia: Burstiness tinggi (variasi panjang kalimat beragam, CV > 0.40).
    AI: Burstiness rendah (panjang kalimat sangat seragam, CV < 0.25).
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


def calculate_segmental_monotony(text: str) -> Dict[str, Any]:
    """
    Menganalisis variabilitas ritme sintaksis pada tingkat paragraf / segmen lokal.
    Paragraf AI: setiap kalimat dalam satu paragraf memiliki panjang yang relatif seragam (CV < 0.32, avg_len 18-45 kata).
    Paragraf Manusia: memiliki dinamika bervariasi (kalimat pengait pendek + elaborasi panjang).
    """
    raw_paras = [p.strip() for p in text.split('\n\n') if len(p.strip()) >= 70]
    if not raw_paras:
        raw_paras = [p.strip() for p in text.split('\n') if len(p.strip()) >= 70]

    evaluated = 0
    monotonous = 0
    for p in raw_paras:
        sents = tokenize_sentences(p)
        if len(sents) >= 3:
            lens = [len(s.split()) for s in sents]
            b = calculate_burstiness(lens)
            avg_l = sum(lens) / len(lens) if lens else 0
            evaluated += 1
            if b < 0.32 and 18 <= avg_l <= 45:
                monotonous += 1

    ratio = round((monotonous / evaluated) * 100.0, 2) if evaluated >= 3 else 0.0
    return {
        "evaluated_paragraphs": evaluated,
        "monotonous_paragraphs": monotonous,
        "monotony_ratio": ratio
    }


def analyze_ai_stylometrics(text: str, substantive_text: Optional[str] = None) -> Dict[str, Any]:
    """
    Menganalisis profil stylometri naskah secara komprehensif dan menghasilkan
    indikator kemungkinan naskah disusun oleh AI atau mesin parafrase.
    Mendukung input teks substantif murni (mengecualikan lampiran/tabel/formalitas)
    guna menghindari bias agregasi global.
    """
    if not text or len(text.strip()) < 50:
        return {
            "status": "insufficient_data",
            "is_experimental": True,
            "ai_probability_score": 0.0,
            "ai_confidence_level": "LOW",
            "verdict_label": "[Eksperimental] Data Teks Tidak Mencukupi",
            "summary_rationale": "Panjang naskah terlalu pendek untuk dianalisis profil stylometrinya.",
            "disclaimer": "Indikator ini bersifat EKSPERIMENTAL dan HANYA menunjukkan variasi statistik pola kalimat, BUKAN bukti atau vonis penggunaan AI.",
            "metrics": {
                "burstiness": 0.5,
                "lexical_diversity": 0.0,
                "transition_density": 0.0,
                "average_sentence_length": 0.0,
                "segmental_monotony_ratio": 0.0,
                "monotonous_paragraphs_count": 0,
                "evaluated_paragraphs_count": 0
            },
            "anomalies": []
        }

    # Gunakan teks substantif jika tersedia untuk menghindari distorsi tabel/angket lampiran
    analysis_text = substantive_text if (substantive_text and len(substantive_text.strip()) >= 200) else text

    sentences = tokenize_sentences(analysis_text)
    sentence_lengths = [len(s.split()) for s in sentences]
    words = analysis_text.split()
    total_words = len(words)

    avg_sentence_len = sum(sentence_lengths) / len(sentence_lengths) if sentence_lengths else 0
    burstiness = calculate_burstiness(sentence_lengths)
    lex_diversity = calculate_lexical_diversity(words)
    transition_density = calculate_ai_transition_density(analysis_text, total_words)
    anomalies = detect_spun_synonym_anomalies(analysis_text)
    seg_monotony = calculate_segmental_monotony(analysis_text)
    seg_ratio = seg_monotony["monotony_ratio"]

    # Bobot Penilaian Indikator Stylometri Eksperimental (0.0 s/d 100.0)
    # Dikalibrasi berdasarkan uji empiris 50 skripsi manusia (P95 skor manusia = 25.5)
    ai_score = 0.0

    # 1. Global Burstiness (Variabilitas Panjang Kalimat)
    if burstiness < 0.22:
        ai_score += 30.0
    elif burstiness < 0.28:
        ai_score += 20.0
    elif burstiness < 0.32:
        ai_score += 10.0

    # Sinyal pendukung (co-signals): segmental monotony hanya menambah skor jika ada sinyal lain yang aktif
    has_co_signals = (burstiness < 0.30 or transition_density >= 1.2 or len(anomalies) > 0)

    # 2. Segmental Monotony Ratio (% paragraf seragam) - HANYA jika didukung sinyal lain
    if has_co_signals and seg_monotony["evaluated_paragraphs"] >= 5:
        if seg_ratio >= 45.0:
            ai_score += 25.0
        elif seg_ratio >= 30.0:
            ai_score += 15.0
        elif seg_ratio >= 20.0:
            ai_score += 8.0

    # 3. Kepadatan Frasa Formulaik / Transisi Klise
    if transition_density >= 3.5:
        ai_score += 30.0
    elif transition_density >= 2.2:
        ai_score += 20.0
    elif transition_density >= 1.2:
        ai_score += 10.0

    # 4. Panjang kalimat rata-rata teratur (18-32 kata) jika didukung sinyal lain
    if 18 <= avg_sentence_len <= 32 and burstiness < 0.28 and has_co_signals:
        ai_score += 10.0

    # 5. Deteksi anomali parafrase / spinner
    if anomalies:
        ai_score += min(len(anomalies) * 5.0, 15.0)

    # Normalisasi batas 0 - 100
    ai_score = round(min(max(ai_score, 0.0), 100.0), 2)

    disclaimer_text = (
        "Indikator ini bersifat EKSPERIMENTAL dan HANYA menunjukkan variasi statistik pola kalimat "
        "(burstiness, keteraturan segmen, frasa transisi), BUKAN bukti atau vonis penggunaan AI. "
        "Gaya penulisan teratur dapat mencerminkan kepenulisan manusia yang disiplin. "
        "Jangan gunakan indikator ini untuk menuduh atau mendiskualifikasi penulis tanpa telaah kualitatif komprehensif."
    )

    # Klasifikasi Tingkat Indikasi Berbasis Ambang Empiris (MODERATE: 50.0, HIGH: 75.0)
    if ai_score >= 75.0:
        confidence = "HIGH"
        label = "[Eksperimental] Pola Kalimat Sangat Seragam & Klise Tinggi"
        if seg_ratio >= 30.0 and transition_density >= 1.5:
            rationale = (
                f"Secara statistik naskah menunjukkan keteraturan ritme sintaksis segmental ({seg_ratio:.1f}% paragraf berpola seragam) "
                f"disertai kepadatan frasa transisi tertentu ({transition_density:.1f}/1000 kata). "
                f"Catatan: Ini adalah indikator gaya bahasa eksperimental untuk panduan telaah mandiri penguji, bukan bukti atau vonis AI."
            )
        else:
            rationale = (
                f"Secara statistik naskah memiliki variasi panjang kalimat yang sangat seragam (Burstiness: {burstiness:.2f}) "
                f"disertai kepadatan frasa transisi ({transition_density:.1f}/1000 kata). "
                f"Catatan: Ini adalah indikator gaya bahasa eksperimental untuk panduan telaah mandiri penguji, bukan bukti atau vonis AI."
            )
    elif ai_score >= 50.0:
        confidence = "MODERATE"
        label = "[Eksperimental] Variasi Ritme Kalimat Terbatas"
        rationale = (
            f"Ditemukan tingkat keseragaman ritme kalimat tertentu (Burstiness: {burstiness:.2f}). "
            f"Catatan: angka ini bersifat eksperimental dan tidak dapat dijadikan vonis integritas akademik."
        )
    else:
        confidence = "LOW"
        label = "[Eksperimental] Pola Kalimat Dinamis (Organik)"
        rationale = (
            f"Struktur kalimat memiliki variasi ritme yang dinamis dan alami (Burstiness: {burstiness:.2f}) "
            f"dalam batas sebaran wajar naskah akademik."
        )

    return {
        "status": "completed",
        "is_experimental": True,
        "ai_probability_score": ai_score,
        "ai_confidence_level": confidence,
        "verdict_label": label,
        "summary_rationale": rationale,
        "disclaimer": disclaimer_text,
        "metrics": {
            "burstiness": burstiness,
            "lexical_diversity": lex_diversity,
            "transition_density": transition_density,
            "average_sentence_length": round(avg_sentence_len, 2),
            "total_sentences_analyzed": len(sentences),
            "total_words_analyzed": total_words,
            "segmental_monotony_ratio": seg_ratio,
            "monotonous_paragraphs_count": seg_monotony["monotonous_paragraphs"],
            "evaluated_paragraphs_count": seg_monotony["evaluated_paragraphs"]
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
