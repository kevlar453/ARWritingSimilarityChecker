"""
AR Tilik - Cross-Lingual Semantic Similarity & Translation Plagiarism Engine (ID <-> EN)
Mendeteksi naskah hasil terjemahan langsung (direct translation / machine translation)
dari literatur internasional berbahasa Inggris ke dalam bahasa Indonesia tanpa atribusi
sitasi yang sah.

Fitur Utama:
1. Kamus Konsep Akademik Dwibahasa Lintas Disiplin (Metodologi, Kesehatan/Medis, TI, Ekonomi, Sosial-Humaniora)
2. Aturan Morfologis Kognat Latin/Yunani (Transformasi imbuhan serapan bahasa Indonesia <-> Inggris)
3. Ekstraksi Pasangan Pasase Dwibahasa Terpadan (Side-by-side aligned passage pairs)
4. Pemindai Korpus Multibahasa Terintegrasi (Multi-candidate corpus scanner)
5. Klasifikasi Tingkat Keyakinan & Edukasi Etik Sitasi Terjemahan
"""

import os
import re
import math
from typing import Dict, List, Set, Tuple, Any, Optional
from dataclasses import dataclass, field, asdict

# ═══════════════════════════════════════════════════════════════════════════════
# 1. KAMUS PEMETAAN KONSEP AKADEMIK DWIBAHASA (EXPANDED BILINGUAL ACADEMIC LEXICON)
# ═══════════════════════════════════════════════════════════════════════════════

BILINGUAL_ACADEMIC_LEXICON: Dict[str, str] = {
    # --- Metodologi, Statistik & Desain Penelitian ---
    "penelitian": "research",
    "meneliti": "research",
    "studi": "study",
    "kajian": "study",
    "penyelidikan": "investigation",
    "menyelidiki": "investigate",
    "metode": "method",
    "metodologi": "methodology",
    "pendekatan": "approach",
    "kualitatif": "qualitative",
    "kuantitatif": "quantitative",
    "analisis": "analysis",
    "menganalisis": "analyze",
    "dianalisis": "analyzed",
    "hasil": "result",
    "temuan": "finding",
    "menemukan": "find",
    "kesimpulan": "conclusion",
    "disimpulkan": "concluded",
    "menyimpulkan": "conclude",
    "hipotesis": "hypothesis",
    "variabel": "variable",
    "indikator": "indicator",
    "instrumen": "instrument",
    "wawancara": "interview",
    "kuesioner": "questionnaire",
    "angket": "questionnaire",
    "observasi": "observation",
    "mengamati": "observe",
    "sampel": "sample",
    "sampling": "sampling",
    "populasi": "population",
    "signifikan": "significant",
    "signifikansi": "significance",
    "korelasi": "correlation",
    "berkorelasi": "correlate",
    "regresi": "regression",
    "pengaruh": "influence",
    "mempengaruhi": "influence",
    "dipengaruhi": "influenced",
    "dampak": "impact",
    "berdampak": "impact",
    "hubungan": "relationship",
    "berhubungan": "relate",
    "efektivitas": "effectiveness",
    "efektif": "effective",
    "efisiensi": "efficiency",
    "efisien": "efficient",
    "implementasi": "implementation",
    "mengimplementasikan": "implement",
    "diimplementasikan": "implemented",
    "penerapan": "implementation",
    "menerapkan": "apply",
    "diterapkan": "applied",
    "evaluasi": "evaluation",
    "mengevaluasi": "evaluate",
    "dievaluasi": "evaluated",
    "faktor": "factor",
    "konteks": "context",
    "kontekstual": "contextual",
    "teori": "theory",
    "teoretis": "theoretical",
    "konsep": "concept",
    "konseptual": "conceptual",
    "paradigma": "paradigm",
    "literatur": "literature",
    "pustaka": "literature",
    "tinjauan": "review",
    "meninjau": "review",
    "sumber": "source",
    "data": "data",
    "triangulasi": "triangulation",
    "validitas": "validity",
    "valid": "valid",
    "reliabilitas": "reliability",
    "reliabel": "reliable",
    "tujuan": "objective",
    "bertujuan": "aim",
    "maksud": "purpose",
    "latar belakang": "background",
    "pembahasan": "discussion",
    "membahas": "discuss",
    "keterbatasan": "limitation",
    "saran": "recommendation",
    "rekomendasi": "recommendation",
    "kebaruan": "novelty",
    "kesenjangan": "gap",
    "kontribusi": "contribution",
    "implikasi": "implication",
    "abstrak": "abstract",
    "pengukuran": "measurement",
    "mengukur": "measure",
    "parameter": "parameter",
    "kriteria": "criteria",
    "kategori": "category",
    "sistematik": "systematic",
    "naratif": "narrative",
    "artikel": "article",
    "jurnal": "journal",
    "sitasi": "citation",
    "rujukan": "reference",
    "pustaka": "references",

    # --- Medis, Kesehatan & Hayati ---
    "kanker": "cancer",
    "tumor": "tumor",
    "payudara": "breast",
    "keganasan": "malignancy",
    "ganas": "malignant",
    "jinak": "benign",
    "lesi": "lesion",
    "deteksi": "detection",
    "mendeteksi": "detect",
    "terdeteksi": "detected",
    "dini": "early",
    "pengobatan": "treatment",
    "terapi": "therapy",
    "terapeutik": "therapeutic",
    "pemeriksaan": "examination",
    "memeriksa": "examine",
    "akurasi": "accuracy",
    "akurat": "accurate",
    "sensitivitas": "sensitivity",
    "spesifisitas": "specificity",
    "prediksi": "prediction",
    "memprediksi": "predict",
    "histopatologi": "histopathology",
    "histopatologis": "histopathological",
    "ultrasonografi": "ultrasonography",
    "biopsi": "biopsy",
    "pasien": "patient",
    "klinis": "clinical",
    "diagnosis": "diagnosis",
    "diagnostik": "diagnostic",
    "gejala": "symptom",
    "penyakit": "disease",
    "gangguan": "disorder",
    "bakteri": "bacteria",
    "antibakteri": "antibacterial",
    "ekstrak": "extract",
    "etanol": "ethanol",
    "daun": "leaf",
    "aktivitas": "activity",
    "uji": "test",
    "pengujian": "testing",
    "kadar": "level",
    "dosis": "dose",
    "efek": "effect",
    "infeksi": "infection",
    "respons": "response",
    "sel": "cell",
    "jaringan": "tissue",
    "darah": "blood",
    "depresi": "depression",
    "kecemasan": "anxiety",
    "otak": "brain",
    "usus": "gut",
    "sumbu": "axis",
    "emas": "gold",
    "standar": "standard",
    "prevalensi": "prevalence",
    "insidensi": "incidence",
    "mortalitas": "mortality",
    "morbiditas": "morbidity",
    "sintesis": "synthesis",
    "patologi": "pathology",
    "patogen": "pathogen",

    # --- Komputer, Data, Teknologi & Rekayasa ---
    "algoritma": "algorithm",
    "model": "model",
    "pemodelan": "modeling",
    "sistem": "system",
    "jaringan": "network",
    "kecerdasan": "intelligence",
    "buatan": "artificial",
    "pembelajaran mesin": "machine learning",
    "pembelajaran mendalam": "deep learning",
    "presisi": "precision",
    "kinerja": "performance",
    "arsitektur": "architecture",
    "pengembangan": "development",
    "mengembangkan": "develop",
    "dikembangkan": "developed",
    "perancangan": "design",
    "merancang": "design",
    "antarmuka": "interface",
    "pengguna": "user",
    "basis data": "database",
    "optimasi": "optimization",
    "mengoptimalkan": "optimize",
    "klasifikasi": "classification",
    "mengklasifikasikan": "classify",
    "kluster": "clustering",
    "simulasi": "simulation",
    "transaksi": "transaction",
    "pola": "pattern",
    "eksplorasi": "exploration",
    "mengeksplorasi": "explore",
    "perilaku": "behavior",
    "digital": "digital",
    "proyek": "project",
    "infrastruktur": "infrastructure",
    "otomatisasi": "automation",
    "otomatis": "automatic",
    "perangkat lunak": "software",
    "perangkat keras": "hardware",
    "komputasi": "computation",
    "komputasional": "computational",
    "keamanan": "security",
    "privasi": "privacy",
    "protokol": "protocol",
    "platform": "platform",

    # --- Ekonomi, Bisnis & Manajemen ---
    "ekonomi": "economy",
    "ekonomis": "economic",
    "keuangan": "financial",
    "pasar": "market",
    "konsumen": "consumer",
    "pelanggan": "customer",
    "strategi": "strategy",
    "strategis": "strategic",
    "manajemen": "management",
    "manajer": "manager",
    "produktivitas": "productivity",
    "kepuasan": "satisfaction",
    "loyalitas": "loyalty",
    "profitabilitas": "profitability",
    "pendapatan": "revenue",
    "laba": "profit",
    "biaya": "cost",
    "investasi": "investment",
    "investor": "investor",
    "tata kelola": "governance",
    "inovasi": "innovation",
    "kewirausahaan": "entrepreneurship",
    "organisasi": "organization",
    "organisasional": "organizational",
    "bisnis": "business",
    "perusahaan": "company",
    "rantai pasok": "supply chain",
    "sumber daya": "resource",

    # --- Pendidikan & Pembelajaran ---
    "pendidikan": "education",
    "edukasi": "education",
    "edukatif": "educational",
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
    "etis": "ethical",
    "nilai": "value",
    "kompetensi": "competence",
    "keterampilan": "skill",
    "prestasi": "achievement",
    "motivasi": "motivation",
    "minat": "interest",
    "persepsi": "perception",
    "kuis": "quiz",
    "kegiatan": "activity",
    "praktikum": "practicum",
    "daring": "online",
    "luring": "offline",
    "pelatihan": "training",
    "kepemimpinan": "leadership",
    "pemimpin": "leader",

    # --- Sosial, Humaniora & Teologi ---
    "masyarakat": "society",
    "sosial": "social",
    "budaya": "culture",
    "kultural": "cultural",
    "komunitas": "community",
    "keluarga": "family",
    "generasi": "generation",
    "transformasi": "transformation",
    "teologi": "theology",
    "teologis": "theological",
    "doktrin": "doctrine",
    "iman": "faith",
    "kepercayaan": "belief",
    "gereja": "church",
    "jemaat": "congregation",
    "pelayanan": "ministry",
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

    # --- Kata Sifat & Keterangan Akademik Umum ---
    "utama": "major",
    "primer": "primary",
    "sekunder": "secondary",
    "penting": "essential",
    "krusial": "crucial",
    "meningkatkan": "increase",
    "peningkatan": "increase",
    "menurunkan": "decrease",
    "penurunan": "decrease",
    "memperlihatkan": "demonstrate",
    "menunjukkan": "indicate",
    "berdasarkan": "based",
    "menggunakan": "use",
    "dilakukan": "conducted",
    "diperoleh": "retrieved",
    "didapatkan": "obtained",
    "komprehensif": "comprehensive",
    "relevan": "relevant",
    "berkelanjutan": "sustainable",
    "banyak": "widely",
    "umum": "common",
}

# Stopwords dwibahasa
CROSS_LINGUAL_STOPWORDS: Set[str] = {
    "yang", "di", "dan", "dari", "ke", "dalam", "untuk", "pada", "adalah", "ini", "itu",
    "dengan", "oleh", "secara", "sebagai", "dapat", "akan", "juga", "karena", "tersebut",
    "bahwa", "serta", "atau", "antara", "terhadap", "bagi", "atas", "bila", "jika",
    "the", "of", "and", "in", "to", "a", "is", "that", "for", "it", "as", "was", "with",
    "by", "on", "are", "this", "be", "from", "at", "which", "an", "have", "has", "were",
    "or", "but", "not", "their", "such", "than", "more", "most", "also", "into", "can"
}


# ═══════════════════════════════════════════════════════════════════════════════
# 2. DATA CLASSES & STRUKTUR HASIL
# ═══════════════════════════════════════════════════════════════════════════════

@dataclass
class AlignedPassagePair:
    submission_passage: str
    source_passage: str
    shared_concepts: List[str]
    passage_similarity: float

    def to_dict(self) -> Dict[str, Any]:
        return asdict(self)


@dataclass
class CrossLingualResult:
    is_cross_lingual_match: bool
    cross_lingual_score: float  # 0.0 s.d. 100.0%
    confidence: str            # HIGH, MODERATE, LOW, NONE
    detected_pair: str         # EN -> ID, ID -> EN, NONE
    source_document_id: Optional[int] = None
    source_title: str = ""
    concept_overlap_percent: float = 0.0
    shared_concept_count: int = 0
    shared_pairs_count: int = 0
    matched_concepts: List[str] = field(default_factory=list)
    aligned_passages: List[Dict[str, Any]] = field(default_factory=list)
    rationale: str = ""
    verdict_label: str = ""

    def to_dict(self) -> Dict[str, Any]:
        return asdict(self)


# ═══════════════════════════════════════════════════════════════════════════════
# 3. ATURAN MORFOLOGIS & PROYEKSI KONSEP KANONIKAL
# ═══════════════════════════════════════════════════════════════════════════════

def apply_morphological_cognates(word: str) -> str:
    """
    Mengubah akhiran serapan bahasa Indonesia berakar Latin/Yunani ke bentuk bahasa Inggris.
    """
    w = word.lower()
    if w.endswith("grafi"):
        return w[:-5] + "graphy"
    if w.endswith("logi"):
        return w[:-4] + "logy"
    if w.endswith("si") and len(w) > 4:
        return w[:-2] + "tion"
    if w.endswith("tas") and len(w) > 4:
        return w[:-3] + "ty"
    if w.endswith("if") and len(w) > 4:
        return w[:-2] + "ive"
    if w.endswith(("ik", "is")) and len(w) > 4:
        return w[:-2] + "ic"
    if w.endswith("ur") and len(w) > 4:
        return w[:-2] + "ure"
    if w.endswith("isme") and len(w) > 5:
        return w[:-4] + "ism"
    if w.endswith("nomi") and len(w) > 5:
        return w[:-4] + "nomy"
    return w


def lemmatize_english_token(word: str) -> str:
    """Normalisasi bentuk jamak dan infleksi kata bahasa Inggris."""
    w = word.lower()
    if w.endswith("ies") and len(w) > 4:
        return w[:-3] + "y"
    if w.endswith("es") and len(w) > 4 and not w.endswith(("ses", "thes", "ches", "shes")):
        return w[:-2]
    if w.endswith(("is", "us", "ss")):
        return w
    if w.endswith("s") and len(w) > 3:
        return w[:-1]
    return w


def project_token_to_concept(word: str) -> Optional[str]:
    """
    Memetakan sebuah token ke konsep kanonikal bersama:
    1. Cek kamus dwibahasa eksplisit
    2. Cek aturan morfologis kognat serapan
    3. Lemmatisasi kata bahasa Inggris
    """
    w = word.lower()
    if w in CROSS_LINGUAL_STOPWORDS or len(w) < 3:
        return None

    if w in BILINGUAL_ACADEMIC_LEXICON:
        return lemmatize_english_token(BILINGUAL_ACADEMIC_LEXICON[w])

    # Transformasi kognat serapan
    morph_w = apply_morphological_cognates(w)
    if morph_w != w:
        return lemmatize_english_token(morph_w)

    return lemmatize_english_token(w)


def project_text_to_canonical_concepts(text: str) -> List[str]:
    """Memetakan teks utuh ke daftar konsep kanonikal berurutan."""
    words = re.findall(r"\b[a-zA-Z]{3,}\b", text.lower())
    concepts = []
    for w in words:
        c = project_token_to_concept(w)
        if c and c not in CROSS_LINGUAL_STOPWORDS and len(c) > 2:
            concepts.append(c)
    return concepts


def generate_concept_shingles(concepts: List[str], size: int = 2) -> Set[str]:
    """Menghasilkan shingle konsep sekuensial (n-gram)."""
    if len(concepts) < size:
        return {"_".join(concepts)} if concepts else set()
    shingles = set()
    for i in range(len(concepts) - size + 1):
        shingles.add("_".join(concepts[i:i + size]))
    return shingles


def generate_unordered_concept_pairs(concepts: List[str], window_size: int = 4) -> Set[str]:
    """
    Menghasilkan pasangan konsep tanpa urutan dalam jendela kata tertentu.
    Mengakomodasi pergeseran pola DM (Indonesia) dan MD (Inggris).
    """
    pairs = set()
    for i in range(len(concepts)):
        for j in range(i + 1, min(i + window_size + 1, len(concepts))):
            c1, c2 = concepts[i], concepts[j]
            if c1 != c2:
                pair = tuple(sorted([c1, c2]))
                pairs.add(f"{pair[0]}--{pair[1]}")
    return pairs


def detect_language(text: str) -> str:
    """Mendeteksi apakah naskah dominan Bahasa Indonesia (ID) atau Inggris (EN)."""
    id_markers = {
        "yang", "dan", "di", "dari", "ini", "dengan", "untuk", "pada", "adalah", "sebagai",
        "bahwa", "penelitian", "hasil", "metode", "tersebut", "secara", "dapat", "dalam"
    }
    en_markers = {
        "the", "and", "of", "in", "to", "is", "that", "for", "with", "as", "by", "from",
        "research", "results", "study", "this", "were", "using", "between", "analysis"
    }

    tokens = [w.lower() for w in re.findall(r"\b[a-zA-Z]{2,}\b", text[:4000])]
    if not tokens:
        return "UNKNOWN"

    id_count = sum(1 for t in tokens if t in id_markers)
    en_count = sum(1 for t in tokens if t in en_markers)

    if id_count >= max(en_count * 1.4, 5):
        return "ID"
    elif en_count >= max(id_count * 1.4, 5):
        return "EN"
    return "MIXED"


# ═══════════════════════════════════════════════════════════════════════════════
# 4. EKSTRAKSI PASANGAN PASASE DWIBAHASA TERPADAN (ALIGNED PASSAGES)
# ═══════════════════════════════════════════════════════════════════════════════

def extract_aligned_passage_pairs(
    submission_text: str,
    source_text: str,
    max_pairs: int = 4
) -> List[Dict[str, Any]]:
    """
    Menemukan kalimat-kalimat yang berkorespondensi langsung antara submisi (ID)
    dan sumber (EN) berdasarkan irisan konsep kanonikal.
    """
    sub_sentences = [s.strip() for s in re.split(r"(?<=[.!?])\s+", submission_text) if len(s.strip()) > 30]
    src_sentences = [s.strip() for s in re.split(r"(?<=[.!?])\s+", source_text) if len(s.strip()) > 30]

    if not sub_sentences or not src_sentences:
        return []

    # Pra-hitung konsep per kalimat sumber
    src_parsed = []
    for s_sent in src_sentences[:150]:
        c_set = set(project_text_to_canonical_concepts(s_sent))
        if len(c_set) >= 3:
            src_parsed.append((s_sent, c_set))

    aligned_results = []
    used_src_indices = set()

    for sub_sent in sub_sentences[:150]:
        sub_concepts = set(project_text_to_canonical_concepts(sub_sent))
        if len(sub_concepts) < 3:
            continue

        best_score = 0.0
        best_match = None
        best_shared = []
        best_idx = -1

        for idx, (src_sent, src_concepts) in enumerate(src_parsed):
            if idx in used_src_indices:
                continue

            shared = sub_concepts.intersection(src_concepts)
            if len(shared) >= 3:
                # Jaccard overlap
                jaccard = len(shared) / len(sub_concepts.union(src_concepts))
                score = (len(shared) * 2.0) + (jaccard * 10.0)
                if score > best_score:
                    best_score = score
                    best_match = src_sent
                    best_shared = sorted(list(shared))
                    best_idx = idx

        if best_match and len(best_shared) >= 3:
            used_src_indices.add(best_idx)
            aligned_results.append({
                "submission_passage": sub_sent[:300] + ("..." if len(sub_sent) > 300 else ""),
                "source_passage": best_match[:300] + ("..." if len(best_match) > 300 else ""),
                "shared_concepts": best_shared[:10],
                "passage_similarity": round(min(best_score * 5.0, 98.0), 1)
            })
            if len(aligned_results) >= max_pairs:
                break

    # Urutkan berdasarkan kemiripan tertinggi
    aligned_results.sort(key=lambda x: x["passage_similarity"], reverse=True)
    return aligned_results


# ═══════════════════════════════════════════════════════════════════════════════
# 5. KALKULASI KESAMAAN LINTAS BAHASA (CORE SCORING)
# ═══════════════════════════════════════════════════════════════════════════════

def calculate_cross_lingual_similarity(
    submission_text: str,
    source_text: str,
    source_doc_id: Optional[int] = None,
    source_title: str = ""
) -> CrossLingualResult:
    """
    Menghitung kesamaan semantik terstruktur lintas bahasa antara dua dokumen.
    """
    lang_sub = detect_language(submission_text)
    lang_src = detect_language(source_text)

    is_cross_lingual_pair = (lang_sub == "ID" and lang_src == "EN") or (lang_sub == "EN" and lang_src == "ID")

    sub_concepts = project_text_to_canonical_concepts(submission_text)
    src_concepts = project_text_to_canonical_concepts(source_text)

    if not sub_concepts or not src_concepts:
        return CrossLingualResult(
            is_cross_lingual_match=False,
            cross_lingual_score=0.0,
            confidence="NONE",
            detected_pair=f"{lang_src} -> {lang_sub}",
            source_document_id=source_doc_id,
            source_title=source_title,
            rationale="Data konsep tidak mencukupi untuk analisis lintas bahasa.",
            verdict_label="Otentik / Bebas Terjemahan"
        )

    # 1. Bag-of-Concepts Overlap
    set_sub = set(sub_concepts)
    set_src = set(src_concepts)
    common_concepts = set_sub.intersection(set_src)
    min_vocab_len = min(len(set_sub), len(set_src))
    concept_overlap = (len(common_concepts) / min_vocab_len) * 100.0 if min_vocab_len > 0 else 0.0

    # 2. Unordered Concept Pairs (Co-occurrence)
    sub_pairs = generate_unordered_concept_pairs(sub_concepts, window_size=4)
    src_pairs = generate_unordered_concept_pairs(src_concepts, window_size=4)
    common_pairs = sub_pairs.intersection(src_pairs)
    min_pairs_len = min(len(sub_pairs), len(src_pairs))
    pair_overlap = (len(common_pairs) / min_pairs_len) * 100.0 if min_pairs_len > 0 else 0.0

    # 3. Sequential Concept Shingles
    sub_shingles = generate_concept_shingles(sub_concepts, size=2)
    src_shingles = generate_concept_shingles(src_concepts, size=2)
    common_shingles = sub_shingles.intersection(src_shingles)

    # Composite Score
    composite_score = round(
        (concept_overlap * 0.40) + (pair_overlap * 0.45) + (min(len(common_shingles) * 3.0, 15.0)),
        1
    )
    composite_score = min(max(composite_score, 0.0), 100.0)

    # Ekstraksi pasangan pasase dwibahasa
    aligned_passages = []
    if len(common_concepts) >= 4:
        aligned_passages = extract_aligned_passage_pairs(submission_text, source_text, max_pairs=3)

    # Evaluasi status kecurigaan terjemahan
    is_translation_match = False
    confidence = "NONE"
    verdict_label = "Otentik / Bebas Terjemahan"
    rationale = "Tidak terdeteksi indikasi penerjemahan langsung antar bahasa."

    if is_cross_lingual_pair:
        if composite_score >= 32.0 or (len(common_pairs) >= 8 and len(common_concepts) >= 10):
            is_translation_match = True
            if composite_score >= 48.0 or len(aligned_passages) >= 2:
                confidence = "HIGH"
                verdict_label = "Terindikasi Plagiarisme Terjemahan (High Confidence)"
            else:
                confidence = "MODERATE"
                verdict_label = "Terindikasi Parafrasa Lintas Bahasa"

            rationale = (
                f"Terdeteksi indikasi terjemahan langsung lintas bahasa ({lang_src} ke {lang_sub}). "
                f"Terdapat {len(common_concepts)} kesesuaian konsep inti, {len(common_pairs)} pasangan relasi gagasan, "
                f"dan {len(aligned_passages)} pasase kalimat terpadan dengan tingkat keselarasan semantik {composite_score:.1f}%."
            )
        elif composite_score >= 15.0:
            confidence = "LOW"
            verdict_label = "Irisan Terminologi Umum (Wajar)"
            rationale = (
                f"Ditemukan irisan kosakata akademis umum ({composite_score:.1f}%), "
                f"namun tidak cukup kuat untuk diindikasikan sebagai naskah terjemahan langsung."
            )

    return CrossLingualResult(
        is_cross_lingual_match=is_translation_match,
        cross_lingual_score=composite_score,
        confidence=confidence,
        detected_pair=f"{lang_src} -> {lang_sub}",
        source_document_id=source_doc_id,
        source_title=source_title,
        concept_overlap_percent=round(concept_overlap, 1),
        shared_concept_count=len(common_concepts),
        shared_pairs_count=len(common_pairs),
        matched_concepts=sorted(list(common_concepts))[:20],
        aligned_passages=aligned_passages,
        rationale=rationale,
        verdict_label=verdict_label
    )


# ═══════════════════════════════════════════════════════════════════════════════
# 6. PEMINDAI KORPUS MULTIBAHASA (MULTI-CANDIDATE SCANNER)
# ═══════════════════════════════════════════════════════════════════════════════

def scan_cross_lingual_corpus(
    submission_text: str,
    connection: Any,
    candidate_sources: Optional[List[Dict[str, Any]]] = None
) -> Dict[str, Any]:
    """
    Memindai naskah submisi terhadap kandidat dokumen multibahasa di basis data AR Tilik.
    Memeriksa kandidat dari exact/cosine sources dan dokumen korpus berbahasa Inggris lainnya.
    """
    sub_lang = detect_language(submission_text)
    best_result: Optional[CrossLingualResult] = None
    best_score = -1.0

    # 1. Periksa dokumen dari candidate_sources yang sudah ada
    checked_doc_ids: Set[int] = set()

    if candidate_sources:
        for src in candidate_sources[:5]:
            src_id = src.get("source_document_id")
            if not src_id or src_id in checked_doc_ids:
                continue
            checked_doc_ids.add(src_id)

            src_title = src.get("source_title", f"Dokumen #{src_id}")
            src_text = src.get("text") or ""

            if not src_text and connection:
                try:
                    with connection.cursor() as cursor:
                        cursor.execute(
                            "SELECT text FROM document_chunks WHERE document_id = %s ORDER BY chunk_index ASC LIMIT 30",
                            (src_id,)
                        )
                        chunks = cursor.fetchall()
                        if chunks:
                            src_text = " ".join(c["text"] for c in chunks if c.get("text"))
                except Exception:
                    pass

            if src_text:
                res = calculate_cross_lingual_similarity(
                    submission_text, src_text, source_doc_id=src_id, source_title=src_title
                )
                if res.cross_lingual_score > best_score:
                    best_score = res.cross_lingual_score
                    best_result = res

    # 2. Jika naskah adalah bahasa Indonesia, telusuri korpus dokumen bahasa Inggris di database
    if sub_lang == "ID" and connection:
        try:
            with connection.cursor(dictionary=True) as cursor:
                # Query dokumen korpus yang berpotensi berbahasa Inggris
                cursor.execute("""
                    SELECT id, title, storage_path, mime_type
                    FROM documents
                    WHERE document_type = 'corpus'
                      AND (
                        title LIKE '% the %' OR title LIKE '% of %' OR title LIKE 'The %'
                        OR title LIKE '% in %' OR title LIKE '% and %' OR title LIKE '% for %'
                      )
                    ORDER BY id DESC
                    LIMIT 20
                """)
                en_docs = cursor.fetchall()

                for ed in en_docs:
                    e_id = ed["id"]
                    if e_id in checked_doc_ids:
                        continue
                    checked_doc_ids.add(e_id)

                    # Ambil teks chunk
                    cursor.execute(
                        "SELECT text FROM document_chunks WHERE document_id = %s ORDER BY chunk_index ASC LIMIT 30",
                        (e_id,)
                    )
                    chunks = cursor.fetchall()
                    e_text = " ".join(c["text"] for c in chunks if c.get("text")) if chunks else ""

                    if not e_text and ed.get("storage_path") and os.path.exists(ed["storage_path"]):
                        from worker.extractor import extract_document
                        pages = extract_document(ed["storage_path"], ed.get("mime_type"))
                        e_text = " ".join(p["text"] for p in pages if p.get("text"))

                    if e_text:
                        res = calculate_cross_lingual_similarity(
                            submission_text, e_text, source_doc_id=e_id, source_title=ed["title"]
                        )
                        if res.cross_lingual_score > best_score:
                            best_score = res.cross_lingual_score
                            best_result = res
        except Exception as scan_err:
            print(f"[CROSS-LINGUAL WARNING] Gagal memindai korpus multibahasa: {scan_err}")

    if best_result is not None:
        return best_result.to_dict()

    # Fallback jika tidak ada kandidat dokumen
    return CrossLingualResult(
        is_cross_lingual_match=False,
        cross_lingual_score=0.0,
        confidence="NONE",
        detected_pair=f"EN -> {sub_lang}",
        rationale="Naskah dianalisis dan tidak ditemukan padanan terjemahan langsung.",
        verdict_label="Otentik / Bebas Terjemahan"
    ).to_dict()


if __name__ == "__main__":
    # Test Standalone
    original_english = (
        "Breast cancer remains a major health concern, and early detection is essential for improving treatment outcomes. "
        "Ultrasonography combined with the Breast Imaging Reporting and Data System (BI-RADS) is widely used to assess breast lesions "
        "and estimate their risk of malignancy. This literature review aimed to analyze the accuracy of BI-RADS classification "
        "on breast ultrasonography in predicting malignancy based on histopathological examination as the gold standard. "
        "A narrative literature review was conducted using articles retrieved from PubMed/NCBI, Google Scholar, and ScienceDirect."
    )

    translated_indonesian = (
        "Kanker payudara tetap menjadi masalah kesehatan utama, dan deteksi dini sangat penting untuk meningkatkan hasil pengobatan. "
        "Ultrasonografi yang dikombinasikan dengan Breast Imaging Reporting and Data System (BI-RADS) banyak digunakan untuk menilai lesi payudara "
        "dan memperkirakan risiko keganasan. Tinjauan literatur ini bertujuan untuk menganalisis akurasi klasifikasi BI-RADS pada "
        "ultrasonografi payudara dalam memprediksi keganasan berdasarkan pemeriksaan histopatologi sebagai standar emas. "
        "Tinjauan pustaka naratif dilakukan dengan menggunakan artikel yang diperoleh dari PubMed/NCBI, Google Scholar, dan ScienceDirect."
    )

    res = calculate_cross_lingual_similarity(translated_indonesian, original_english, source_title="BI-RADS Breast Cancer Study")
    print("=== TEST CROSS-LINGUAL ENGINE ===")
    print(f"Verdict        : {res.verdict_label}")
    print(f"Match Detected : {res.is_cross_lingual_match} ({res.confidence})")
    print(f"Score          : {res.cross_lingual_score}%")
    print(f"Shared Concepts: {len(res.matched_concepts)} terms -> {res.matched_concepts}")
    print(f"Aligned Pairs  : {len(res.aligned_passages)} pairs")
    for i, p in enumerate(res.aligned_passages, 1):
        print(f"  [Pair #{i} - Sim: {p['passage_similarity']}%]")
        print(f"   ID: {p['submission_passage']}")
        print(f"   EN: {p['source_passage']}")
        print(f"   Terms: {', '.join(p['shared_concepts'])}")
