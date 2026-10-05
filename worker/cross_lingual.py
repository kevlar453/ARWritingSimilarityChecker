"""
AR Tilik - Cross-Lingual Semantic Similarity Engine (ID <-> EN)
Modul pendeteksi naskah hasil terjemahan langsung (direct translation) dari literatur
internasional berbahasa Inggris ke dalam bahasa Indonesia tanpa pencantuman sitasi yang sah.
"""

import re
import math
from typing import Dict, List, Set, Tuple, Any, Optional

# Kamus Pemetaan Konsep Akademik Dwibahasa (Bilingual Academic Concept Lexicon)
BILINGUAL_ACADEMIC_LEXICON = {
    # Metodologi & Penelitian
    "penelitian": "research",
    "studi": "research",
    "kajian": "research",
    "penyelidikan": "research",
    "metode": "method",
    "metodologi": "methodology",
    "pendekatan": "approach",
    "kualitatif": "qualitative",
    "kuantitatif": "quantitative",
    "analisis": "analysis",
    "menganalisis": "analysis",
    "hasil": "result",
    "temuan": "finding",
    "kesimpulan": "conclusion",
    "hipotesis": "hypothesis",
    "variabel": "variable",
    "indikator": "indicator",
    "instrumen": "instrument",
    "wawancara": "interview",
    "kuesioner": "questionnaire",
    "angket": "questionnaire",
    "observasi": "observation",
    "sampel": "sample",
    "populasi": "population",
    "signifikan": "significant",
    "signifikansi": "significance",
    "korelasi": "correlation",
    "regresi": "regression",
    "pengaruh": "influence",
    "dampak": "impact",
    "hubungan": "relationship",
    "efektivitas": "effectiveness",
    "efisiensi": "efficiency",
    "implementasi": "implementation",
    "penerapan": "implementation",
    "evaluasi": "evaluation",
    "faktor": "factor",
    "konteks": "context",
    "teori": "theory",
    "konsep": "concept",
    "paradigma": "paradigm",
    "literatur": "literature",
    "pustaka": "literature",
    "sumber": "source",

    # Pendidikan & Pembelajaran
    "pendidikan": "education",
    "pembelajaran": "learning",
    "pengajaran": "teaching",
    "sekolah": "school",
    "guru": "teacher",
    "pendidik": "educator",
    "siswa": "student",
    "peserta didik": "student",
    "mahasiswa": "student",
    "kurikulum": "curriculum",
    "karakter": "character",
    "moral": "moral",
    "moralitas": "morality",
    "etika": "ethics",
    "nilai": "value",
    "kompetensi": "competence",
    "keterampilan": "skill",
    "prestasi": "achievement",
    "motivasi": "motivation",
    "minat": "interest",

    # Teologi, Agama & Sosial Humaniora
    "teologi": "theology",
    "doktrin": "doctrine",
    "iman": "faith",
    "kepercayaan": "belief",
    "gereja": "church",
    "jemaat": "congregation",
    "pelayanan": "ministry",
    "kepemimpinan": "leadership",
    "pemimpin": "leader",
    "pastoral": "pastoral",
    "eksegesis": "exegesis",
    "hermeneutika": "hermeneutics",
    "teks": "text",
    "narasi": "narrative",
    "alkitab": "bible",
    "kitab suci": "scripture",
    "perjanjian baru": "new testament",
    "perjanjian lama": "old testament",
    "pertumbuhan": "growth",
    "transformasi": "transformation",
    "masyarakat": "society",
    "sosial": "social",
    "budaya": "culture",
    "kultural": "cultural",
    "komunitas": "community",
    "keluarga": "family",
    "generasi": "generation"
}

# Stopwords dwibahasa yang diabaikan dalam proyeksi konsep
CROSS_LINGUAL_STOPWORDS = {
    "yang", "di", "dan", "dari", "ke", "dalam", "untuk", "pada", "adalah", "ini", "itu",
    "dengan", "oleh", "secara", "sebagai", "dapat", "akan", "juga", "karena", "tersebut",
    "the", "of", "and", "in", "to", "a", "is", "that", "for", "it", "as", "was", "with",
    "by", "on", "are", "this", "be", "from", "at", "which", "an", "have", "has", "were"
}


def detect_language(text: str) -> str:
    """
    Heuristik sederhana mendeteksi apakah naskah dominan Bahasa Indonesia (ID) atau Inggris (EN).
    """
    id_markers = {"yang", "dan", "di", "dari", "ini", "dengan", "untuk", "pada", "adalah", "sebagai"}
    en_markers = {"the", "and", "of", "in", "to", "is", "that", "for", "with", "as"}

    tokens = [w.lower() for w in re.findall(r"\b[a-zA-Z]{2,}\b", text[:3000])]
    if not tokens:
        return "UNKNOWN"

    id_count = sum(1 for t in tokens if t in id_markers)
    en_count = sum(1 for t in tokens if t in en_markers)

    if id_count >= en_count * 1.5:
        return "ID"
    elif en_count >= id_count * 1.5:
        return "EN"
    return "MIXED"


def lemmatize_token(word: str) -> str:
    """Normalisasi sederhana bentuk jamak dan infleksi kata bahasa Inggris."""
    w = word.lower()
    if w.endswith("ies") and len(w) > 4:
        return w[:-3] + "y"
    if w.endswith("es") and len(w) > 4 and not w.endswith("ses") and not w.endswith("thes"):
        return w[:-2]
    # Lindungi kata yang berakhiran -is (analysis, hypothesis, thesis), -us (corpus, focus, status), dan -ss (process, across)
    if w.endswith(("is", "us", "ss")):
        return w
    if w.endswith("s") and len(w) > 3:
        return w[:-1]
    return w


def project_text_to_canonical_concepts(text: str) -> List[str]:
    """
    Memetakan token teks (baik teks Indonesia maupun Inggris) ke representasi konsep kanonikal.
    """
    clean_text = text.lower()
    words = re.findall(r"\b[a-zA-Z]{3,}\b", clean_text)
    concepts = []

    for w in words:
        if w in CROSS_LINGUAL_STOPWORDS:
            continue
        # Jika ada di kamus Indonesia -> petakan ke konsep kanonikal
        if w in BILINGUAL_ACADEMIC_LEXICON:
            concepts.append(lemmatize_token(BILINGUAL_ACADEMIC_LEXICON[w]))
        else:
            # Jika teks asli bahasa Inggris, lemmatisasi dan simpan
            concepts.append(lemmatize_token(w))

    return concepts


def generate_concept_shingles(concepts: List[str], size: int = 2) -> Set[str]:
    """Menghasilkan shingle konsep sekuensial (n-gram)."""
    if len(concepts) < size:
        return set(["_".join(concepts)]) if concepts else set()
    shingles = set()
    for i in range(len(concepts) - size + 1):
        shingle = "_".join(concepts[i:i + size])
        shingles.add(shingle)
    return shingles


def generate_unordered_concept_pairs(concepts: List[str], window_size: int = 4) -> Set[str]:
    """
    Menghasilkan pasangan konsep tanpa urutan (co-occurrence pair) dalam jendela kata tertentu.
    Ini mengatasi pergeseran urutan kata akibat perbedaan kaidah DM (Indonesia) dan MD (Inggris).
    """
    pairs = set()
    for i in range(len(concepts)):
        for j in range(i + 1, min(i + window_size + 1, len(concepts))):
            c1, c2 = concepts[i], concepts[j]
            if c1 != c2:
                pair = tuple(sorted([c1, c2]))
                pairs.add(f"{pair[0]}--{pair[1]}")
    return pairs


def calculate_cross_lingual_similarity(
    submission_text: str,
    source_text: str
) -> Dict[str, Any]:
    """
    Menghitung kesamaan semantik lintas bahasa antara naskah submisi dan naskah sumber.
    Mendeteksi indikasi terjemahan langsung tanpa sitasi (Direct Translation).
    """
    lang_sub = detect_language(submission_text)
    lang_src = detect_language(source_text)

    # Cek apakah ada pasangan dwibahasa (ID <-> EN)
    is_cross_lingual_pair = (lang_sub == "ID" and lang_src == "EN") or (lang_sub == "EN" and lang_src == "ID")

    sub_concepts = project_text_to_canonical_concepts(submission_text)
    src_concepts = project_text_to_canonical_concepts(source_text)

    if not sub_concepts or not src_concepts:
        return {
            "is_cross_lingual_match": False,
            "cross_lingual_score": 0.0,
            "confidence": "NONE",
            "detected_pair": f"{lang_src} -> {lang_sub}",
            "concept_overlap_percent": 0.0,
            "shared_concept_count": 0,
            "matched_concepts": [],
            "rationale": "Data konsep tidak mencukupi untuk analisis lintas bahasa."
        }

    # 1. Analisis Bag-of-Concepts Overlap
    set_sub = set(sub_concepts)
    set_src = set(src_concepts)
    common_concepts = set_sub.intersection(set_src)
    min_vocab_len = min(len(set_sub), len(set_src))
    concept_overlap = (len(common_concepts) / min_vocab_len) * 100 if min_vocab_len > 0 else 0.0

    # 2. Analisis Unordered Concept Pairs (Window-based Co-occurrence)
    sub_pairs = generate_unordered_concept_pairs(sub_concepts, window_size=4)
    src_pairs = generate_unordered_concept_pairs(src_concepts, window_size=4)
    common_pairs = sub_pairs.intersection(src_pairs)
    min_pairs_len = min(len(sub_pairs), len(src_pairs))
    pair_overlap = (len(common_pairs) / min_pairs_len) * 100 if min_pairs_len > 0 else 0.0

    # 3. Analisis Sequential Concept Shingles
    sub_shingles = generate_concept_shingles(sub_concepts, size=2)
    src_shingles = generate_concept_shingles(src_concepts, size=2)
    common_shingles = sub_shingles.intersection(src_shingles)

    # Skor agregat kemiripan konsep lintas bahasa (Composite Cross-Lingual Score)
    # Konsep overlap (45%), Pasangan relasi konsep (45%), Sekuensial langsung (10%)
    composite_score = round(
        (concept_overlap * 0.45) + (pair_overlap * 0.45) + (min(len(common_shingles) * 5.0, 10.0)),
        2
    )
    composite_score = min(max(composite_score, 0.0), 100.0)

    # Deteksi Flag Translation
    is_translation_match = False
    confidence = "NONE"
    rationale = "Tidak ditemukan indikasi kemiripan lintas bahasa."

    if is_cross_lingual_pair:
        if composite_score >= 35.0 or len(common_pairs) >= 5 or len(common_concepts) >= 8:
            is_translation_match = True
            if composite_score >= 50.0 or len(common_pairs) >= 10:
                confidence = "HIGH"
            else:
                confidence = "MODERATE"

            rationale = (
                f"Terdeteksi indikasi terjemahan langsung lintas bahasa ({lang_src} ke {lang_sub}). "
                f"Terdapat {len(common_concepts)} kesesuaian konsep inti dan {len(common_pairs)} pasangan relasi gagasan "
                f"dengan tingkat kemiripan semantik terstruktur sebesar {composite_score:.1f}%."
            )

    return {
        "is_cross_lingual_match": is_translation_match,
        "cross_lingual_score": composite_score,
        "confidence": confidence,
        "detected_pair": f"{lang_src} -> {lang_sub}",
        "concept_overlap_percent": round(concept_overlap, 2),
        "shared_concept_count": len(common_concepts),
        "shared_pairs_count": len(common_pairs),
        "matched_concepts": sorted(list(common_concepts))[:15],
        "rationale": rationale
    }


if __name__ == "__main__":
    # Contoh simulasi: Artikel asli berbahasa Inggris vs Naskah mahasiswa bahasa Indonesia hasil terjemahan
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
    print("=== HASIL ANALISIS CROSS-LINGUAL SIMILARITY ===")
    print(f"Pasangan Bahasa       : {res['detected_pair']}")
    print(f"Terjemahan Terdeteksi : {'YA' if res['is_cross_lingual_match'] else 'TIDAK'} ({res['confidence']})")
    print(f"Skor Kesamaan Konsep  : {res['cross_lingual_score']}%")
    print(f"Jumlah Pasangan Relasi: {res['shared_pairs_count']}")
    print(f"Konsep Yang Cocok     : {', '.join(res['matched_concepts'])}")
    print(f"Narasi Analisis       : {res['rationale']}")
