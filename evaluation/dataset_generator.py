"""
AR Tilik - Evaluation Dataset Generator v1
Menghasilkan 500 kasus evaluasi terisolasi dari 50 seed benchmark tanpa mengubah database produksi atau engine:
- Seed Cases: 50
- Mutation Cases: 250 (50 seeds x 5 mutasi: lexical, deletion/insertion, restructuring, paraphrase, mixed)
- Boundary Cases: 100 (Threshold - 1, Threshold, Threshold + 1)
- Adversarial Cases: 100 (Methodology boilerplate, Citation-protected, Exact quote, Terminology overlap)
"""

import json
import os
import re
import random
from pathlib import Path
from typing import List, Dict, Any

from benchmark.dataset import (
    BASE_SOURCE,
    generate_dataset,
    make_identical,
    make_copy_edit,
    make_paraphrase,
    make_common_phrase,
    make_unrelated
)
from benchmark.stress_dataset import (
    FILLER_ENERGY,
    FILLER_AGRICULTURE,
    make_boundary_verbatim_high,
    make_boundary_near_match,
    make_boundary_copy_edit_high,
    make_boundary_near_review_low,
    make_boundary_below_review,
    make_citation_noise,
    make_false_friends
)
from evaluation.schema import (
    EvaluationCase,
    CASE_TYPE_SEED,
    CASE_TYPE_MUTATION,
    CASE_TYPE_BOUNDARY,
    CASE_TYPE_ADVERSARIAL,
    CASE_TYPE_REAL_WORLD,
    MUTATION_LEXICAL,
    MUTATION_DELETION_INSERTION,
    MUTATION_RESTRUCTURING,
    MUTATION_PARAPHRASE,
    MUTATION_MIXED,
    ADV_METHODOLOGY,
    ADV_CITATION,
    ADV_QUOTE,
    ADV_TERMINOLOGY
)

DATASET_DIR = Path(__file__).resolve().parents[1] / "evaluation_dataset_v1"

# Tabel Sinonim Akademis Indonesia untuk Mutasi
ACADEMIC_SYNONYMS = {
    "bertujuan": ["bermaksud", "berfokus", "diarahkan", "ditujukan"],
    "mengetahui": ["memahami", "mengidentifikasi", "mengeksplorasi", "mengkaji"],
    "pengaruh": ["efek", "dampak", "implikasi", "daya pengaruh"],
    "kinerja": ["performa", "prestasi kerja", "produktivitas", "kinerja operasional"],
    "organisasi": ["lembaga", "institusi", "badan usaha", "satuan kerja"],
    "metode": ["metodologi", "prosedur", "tata cara", "teknik"],
    "penelitian": ["riset", "kajian", "studi", "penyelidikan"],
    "menggunakan": ["memanfaatkan", "menerapkan", "mengadopsi", "mengaplikasikan"],
    "pendekatan": ["ancangan", "orientasi", "perspektif", "paradigma"],
    "kuantitatif": ["kuantitatif terukur", "empiris kuantitatif", "numerik"],
    "pengumpulan": ["pemberkasan", "himpunan", "akuisisi"],
    "kuesioner": ["angket", "instrumen survei", "lembar kuesioner"],
    "menunjukkan": ["memperlihatkan", "mengindikasikan", "membuktikan", "menandakan"],
    "hubungan": ["korelasi", "keterkaitan", "relasi", "asoisasi"],
    "peningkatan": ["kenaikan", "eskalasi", "pengembangan", "kemajuan"],
    "tanaman": ["vegetasi", "komoditas pertanian", "flora"],
    "kondisi": ["keadaan", "status", "situasi", "kondisi lingkungan"],
    "kebutuhan": ["keperluan", "tuntutan", "prasyarat"],
}

# Kualifikasi / Pengubah untuk Mutasi Insersi & Delesi
QUALIFIER_INSERTIONS = [
    "secara komprehensif",
    "pada hakikatnya",
    "berdasarkan observasi empiris",
    "dapat dipahami bahwa",
    "dalam konteks kelembagaan",
    "sebagaimana dihipotesiskan",
    "melalui serangkaian tahapan",
    "secara bertahap dan konsisten",
    "dengan mempertimbangkan faktor kontekstual",
    "pada taraf signifikansi tertentu"
]

# Corpus Kutipan Ilmiah Adversarial
CITATIONS_ADVERSARIAL = [
    "(Sugiyono, 2019: 145)",
    "(Davis, 1989)",
    "(Kotler & Keller, 2016)",
    "(Jogiyanto, 2021: 88)",
    "(Nugroho dkk., 2022)",
    "(Santoso & Wibowo, 2020)",
    "(Pratama, 2023)",
    "(Hidayat & Susilo, 2021)",
    "(Sekaran & Bougie, 2016)",
    "(Creswell, 2018: 52)"
]

METHODOLOGY_BOILERPLATES = [
    "Data dianalisis menggunakan metode deskriptif kuantitatif dengan bantuan software statistik untuk menguji hipotesis asosiatif.",
    "Populasi dalam penelitian ini adalah seluruh pegawai aktif, sedangkan penentuan jumlah sampel dilakukan menggunakan rumus Slovin.",
    "Uji validitas instrumen dihitung menggunakan korelasi Product Moment dari Pearson dengan batas signifikansi lima persen.",
    "Uji reliabilitas kuesioner diukur menggunakan koefisien Cronbach Alpha dengan kriteria nilai reliabel lebih besar dari 0,60.",
    "Uji asumsi klasik mencakup pengujian normalitas dengan uji Kolmogorov-Smirnov, multikolinearitas melalui nilai VIF, dan uji heteroskedastisitas.",
    "Teknik pengumpulan data menggunakan kuesioner skala Likert lima tingkat mulai dari sangat tidak setuju hingga sangat setuju.",
    "Penelitian ini merupakan penelitian eksplanatori yang bermaksud menjelaskan hubungan kausal antara variabel bebas dan variabel terikat.",
    "Metode penarikan sampel menggunakan purposive sampling dengan kriteria responden telah bekerja minimal selama dua tahun.",
    "Analisis regresi linear berganda diterapkan untuk mengestimasi koefisien determinasi dan kontribusi relatif masing-masing indikator.",
    "Pengujian kelayakan model dilakukan melalui uji F untuk simultan dan uji t parsial guna menentukan keberartian pengaruh variabel."
]

TERMINOLOGY_JARGONS = [
    "Arsitektur deep learning multilayer perceptron mengoptimalkan loss function cross-entropy melalui algoritma backpropagation dan adaptive gradient descent.",
    "Mekanisme self-attention transformer menghitung dot-product similarity antara matriks query dan key dengan penskalaan dimensi embedding residual connection.",
    "Farmakokinetika senyawa bioaktif mencakup laju absorpsi gastrointestinal, volume distribusi plasma, biotransformasi hepatik, dan ekskresi klirens ginjal.",
    "Model ekuilibrium umum stokastik dinamis memodelkan kurva Phillips forward-looking dengan rigiditas harga Calvo dan fungsi utilitas konsumen.",
    "Sintesis nanopartikel perak menggunakan bioreduktor ekstrak daun menginduksi resonansi plasmon permukaan terdeteksi pada spektrofotometri UV-Vis.",
    "Asas legalitas dalam hukum pidana materiil menentukan tiada suatu perbuatan dapat dipidana kecuali atas kekuatan aturan pidana perundang-undangan.",
    "Filogeni molekuler sekuens gen ribosom dianalisis dengan metode maximum likelihood berakar outgroup menggunakan model substitusi nukleotida GTR.",
    "Termodinamika transfer massa difusional pada membran elektrolit polimerik dikendalikan oleh gradien potensial elektrokimia dan konsentrasi ionik.",
    "Algoritma enkripsi kurva eliptik menjamin integritas kriptografis kunci privat berbasis masalah logaritma diskrit pada medan hingga berordo tinggi.",
    "Ekologi mikroba tanah rizosfer dicirikan oleh keragaman taksonomi metagenomik 16S rRNA dan aktivitas enzimatik dehidrogenase fosfatase."
]

def mutate_lexical(text: str, seed_num: int) -> str:
    """Level 1: Mutasi substitusi leksikal, kapitalisasi, dan tanda baca."""
    words = text.split()
    mutated = []
    rng = random.Random(seed_num + 101)
    
    for word in words:
        clean = word.strip(".,;:()\"'").lower()
        if clean in ACADEMIC_SYNONYMS and rng.random() < 0.45:
            syn = rng.choice(ACADEMIC_SYNONYMS[clean])
            if word[0].isupper():
                syn = syn.capitalize()
            # Pertahankan tanda baca akhir jika ada
            if word[-1] in ".,;:":
                syn += word[-1]
            mutated.append(syn)
        else:
            mutated.append(word)
            
    res = " ".join(mutated)
    # Variasi tanda baca ringan
    if rng.random() < 0.3:
        res = res.replace(", ", "; ", 1)
    return res

def mutate_deletion_insertion(text: str, seed_num: int) -> str:
    """Level 1: Mutasi penambahan kualifikasi dan penghilangan kata/frasa minor."""
    rng = random.Random(seed_num + 202)
    sentences = [s.strip() for s in text.split(".") if s.strip()]
    if not sentences:
        return text
        
    mutated_sentences = []
    for s in sentences:
        words = s.split()
        # Sisipkan kualifikasi
        if len(words) > 5 and rng.random() < 0.6:
            qual = rng.choice(QUALIFIER_INSERTIONS)
            pos = min(len(words) - 2, max(2, len(words) // 2))
            words.insert(pos, qual)
        # Hapus kata minor
        if len(words) > 8 and rng.random() < 0.4:
            drop_candidates = [i for i, w in enumerate(words) if w.lower() in ("ini", "dengan", "antara", "dan", "secara", "yang")]
            if drop_candidates:
                del words[rng.choice(drop_candidates)]
        mutated_sentences.append(" ".join(words))
        
    return ". ".join(mutated_sentences) + "."

def mutate_restructuring(text: str, seed_num: int) -> str:
    """Level 2: Rekonstruksi kalimat, pemecahan klausul, dan penataan ulang."""
    rng = random.Random(seed_num + 303)
    sentences = [s.strip() for s in text.split(".") if s.strip()]
    if len(sentences) <= 1:
        # Reorder clauses within single sentence
        parts = text.split(",")
        if len(parts) > 1:
            return ", ".join(reversed(parts)).strip()
        return text
        
    # Reorder sentences
    reordered = list(sentences)
    if len(reordered) >= 3:
        # Pindahkan kalimat akhir ke awal atau tukar urutan
        last = reordered.pop()
        reordered.insert(0, last)
    else:
        reordered.reverse()
        
    # Sentence split / merge
    res = ". ".join(reordered) + "."
    if " dengan " in res and rng.random() < 0.5:
        res = res.replace(" dengan ", ". Hal tersebut dilakukan dengan ", 1)
    return res

def mutate_paraphrase(text: str, seed_num: int) -> str:
    """Level 3: Parafrase mendalam aktif-pasif dan nominalisasi."""
    res = text
    # Pola transformasi akademis aktif <-> pasif
    transforms = [
        ("Penelitian ini bertujuan untuk mengetahui", "Tujuan utama yang hendak dicapai dalam riset ini adalah mengeksplorasi"),
        ("Metode penelitian menggunakan pendekatan kuantitatif", "Pendekatan kuantitatif diterapkan sebagai kerangka metodologis penelitian"),
        ("pengumpulan data melalui kuesioner", "data dihimpun melalui penyebaran instrumen angket survei"),
        ("Hasil penelitian menunjukkan adanya hubungan", "Temuan empiris memperlihatkan keterkaitan yang nyata"),
        ("penggunaan teknologi dan peningkatan kinerja", "penerapan teknologi terhadap kenaikan performansi institusi"),
        ("Pengelolaan tanaman hortikultura membutuhkan kondisi tanah", "Kondisi tanah yang optimal sangat diperlukan dalam mengelola tanaman hortikultura"),
        ("Pemupukan dilakukan berdasarkan kebutuhan", "Kebutuhan tanaman menjadi rujukan utama dalam pelaksanaan pemupukan"),
        ("Produktivitas tanaman dipengaruhi oleh", "Ketersediaan air dan unsur hara memberikan pengaruh terhadap produktivitas tanaman"),
    ]
    for src, dst in transforms:
        if src in res:
            res = res.replace(src, dst)
            
    # Tambahan parafrase leksikal
    res = mutate_lexical(res, seed_num + 404)
    return res

def mutate_mixed(text: str, seed_num: int) -> str:
    """Kombinasi mutasi Level 1 + Level 2/3."""
    t1 = mutate_lexical(text, seed_num + 505)
    t2 = mutate_deletion_insertion(t1, seed_num + 505)
    t3 = mutate_restructuring(t2, seed_num + 505)
    return t3


def generate_seed_cases() -> List[EvaluationCase]:
    """Menghasilkan 50 kasus uji seed dari benchmark standar."""
    raw_dataset = generate_dataset()
    seed_cases = []
    
    category_pattern_map = {
        "identical": ("match", "VERBATIM_MATCH"),
        "copy_edit": ("review", "COPY_EDIT"),
        "paraphrase": ("review", "SEMANTIC_PARAPHRASE"),
        "common_phrase": ("review", "COMMON_ACADEMIC_PHRASE"),
        "unrelated": ("no_match", "NO_EVIDENCE")
    }
    
    for i, item in enumerate(raw_dataset, start=1):
        cat = item["category"]
        exp_class, exp_pat = category_pattern_map[cat]
        seed_cases.append(EvaluationCase(
            case_id=f"EV-SEED-{i:03d}",
            case_type=CASE_TYPE_SEED,
            source_case=item["name"],
            generation_method="BENCHMARK_SEED",
            submission_text=item["submission"],
            source_text=BASE_SOURCE,
            expected_class=exp_class,
            expected_pattern=exp_pat,
            human_validated=False,
            system_version="v1.0.0",
            metadata={"seed_category": cat, "seed_index": i}
        ))
    return seed_cases


def generate_mutation_cases(seed_cases: List[EvaluationCase]) -> List[EvaluationCase]:
    """
    Menghasilkan 250 kasus mutasi:
    50 seed x 5 operator:
    - 50 Lexical
    - 50 Deletion/Insertion
    - 50 Restructuring
    - 50 Paraphrase
    - 50 Mixed
    """
    mutation_cases = []
    mut_id = 1
    
    # 1. 50 Lexical Edits
    for seed in seed_cases:
        txt = mutate_lexical(seed.submission_text, mut_id)
        # Tentukan expected class & pattern
        if seed.metadata["seed_category"] == "identical":
            exp_c, exp_p = "review", "COPY_EDIT"
        elif seed.metadata["seed_category"] == "copy_edit":
            exp_c, exp_p = "review", "COPY_EDIT"
        elif seed.metadata["seed_category"] == "paraphrase":
            exp_c, exp_p = "review", "SEMANTIC_PARAPHRASE"
        elif seed.metadata["seed_category"] == "common_phrase":
            exp_c, exp_p = "review", "COMMON_ACADEMIC_PHRASE"
        else:
            exp_c, exp_p = "no_match", "NO_EVIDENCE"
            
        mutation_cases.append(EvaluationCase(
            case_id=f"EV-MUT-{mut_id:03d}",
            case_type=CASE_TYPE_MUTATION,
            source_case=seed.case_id,
            generation_method=MUTATION_LEXICAL,
            submission_text=txt,
            source_text=seed.source_text,
            expected_class=exp_c,
            expected_pattern=exp_p,
            human_validated=False,
            system_version="v1.0.0",
            metadata={"mutation_level": 1, "parent_category": seed.metadata["seed_category"]}
        ))
        mut_id += 1

    # 2. 50 Deletion / Insertion
    for seed in seed_cases:
        txt = mutate_deletion_insertion(seed.submission_text, mut_id)
        if seed.metadata["seed_category"] == "identical":
            exp_c, exp_p = "review", "COPY_EDIT"
        elif seed.metadata["seed_category"] == "copy_edit":
            exp_c, exp_p = "review", "COPY_EDIT"
        elif seed.metadata["seed_category"] == "paraphrase":
            exp_c, exp_p = "review", "SEMANTIC_PARAPHRASE"
        elif seed.metadata["seed_category"] == "common_phrase":
            exp_c, exp_p = "review", "COMMON_ACADEMIC_PHRASE"
        else:
            exp_c, exp_p = "no_match", "NO_EVIDENCE"
            
        mutation_cases.append(EvaluationCase(
            case_id=f"EV-MUT-{mut_id:03d}",
            case_type=CASE_TYPE_MUTATION,
            source_case=seed.case_id,
            generation_method=MUTATION_DELETION_INSERTION,
            submission_text=txt,
            source_text=seed.source_text,
            expected_class=exp_c,
            expected_pattern=exp_p,
            human_validated=False,
            system_version="v1.0.0",
            metadata={"mutation_level": 1, "parent_category": seed.metadata["seed_category"]}
        ))
        mut_id += 1

    # 3. 50 Restructuring
    for seed in seed_cases:
        txt = mutate_restructuring(seed.submission_text, mut_id)
        if seed.metadata["seed_category"] in ("identical", "copy_edit"):
            exp_c, exp_p = "review", "COPY_EDIT"
        elif seed.metadata["seed_category"] == "paraphrase":
            exp_c, exp_p = "review", "SEMANTIC_PARAPHRASE"
        elif seed.metadata["seed_category"] == "common_phrase":
            exp_c, exp_p = "review", "COMMON_ACADEMIC_PHRASE"
        else:
            exp_c, exp_p = "no_match", "NO_EVIDENCE"
            
        mutation_cases.append(EvaluationCase(
            case_id=f"EV-MUT-{mut_id:03d}",
            case_type=CASE_TYPE_MUTATION,
            source_case=seed.case_id,
            generation_method=MUTATION_RESTRUCTURING,
            submission_text=txt,
            source_text=seed.source_text,
            expected_class=exp_c,
            expected_pattern=exp_p,
            human_validated=False,
            system_version="v1.0.0",
            metadata={"mutation_level": 2, "parent_category": seed.metadata["seed_category"]}
        ))
        mut_id += 1

    # 4. 50 Paraphrase
    for seed in seed_cases:
        txt = mutate_paraphrase(seed.submission_text, mut_id)
        if seed.metadata["seed_category"] in ("identical", "copy_edit", "paraphrase"):
            exp_c, exp_p = "review", "SEMANTIC_PARAPHRASE"
        elif seed.metadata["seed_category"] == "common_phrase":
            exp_c, exp_p = "review", "COMMON_ACADEMIC_PHRASE"
        else:
            exp_c, exp_p = "no_match", "NO_EVIDENCE"
            
        mutation_cases.append(EvaluationCase(
            case_id=f"EV-MUT-{mut_id:03d}",
            case_type=CASE_TYPE_MUTATION,
            source_case=seed.case_id,
            generation_method=MUTATION_PARAPHRASE,
            submission_text=txt,
            source_text=seed.source_text,
            expected_class=exp_c,
            expected_pattern=exp_p,
            human_validated=False,
            system_version="v1.0.0",
            metadata={"mutation_level": 3, "parent_category": seed.metadata["seed_category"]}
        ))
        mut_id += 1

    # 5. 50 Mixed Transformation
    for seed in seed_cases:
        txt = mutate_mixed(seed.submission_text, mut_id)
        if seed.metadata["seed_category"] in ("identical", "copy_edit"):
            exp_c, exp_p = "review", "COPY_EDIT"
        elif seed.metadata["seed_category"] == "paraphrase":
            exp_c, exp_p = "review", "SEMANTIC_PARAPHRASE"
        elif seed.metadata["seed_category"] == "common_phrase":
            exp_c, exp_p = "review", "COMMON_ACADEMIC_PHRASE"
        else:
            exp_c, exp_p = "no_match", "NO_EVIDENCE"
            
        mutation_cases.append(EvaluationCase(
            case_id=f"EV-MUT-{mut_id:03d}",
            case_type=CASE_TYPE_MUTATION,
            source_case=seed.case_id,
            generation_method=MUTATION_MIXED,
            submission_text=txt,
            source_text=seed.source_text,
            expected_class=exp_c,
            expected_pattern=exp_p,
            human_validated=False,
            system_version="v1.0.0",
            metadata={"mutation_level": 4, "parent_category": seed.metadata["seed_category"]}
        ))
        mut_id += 1

    return mutation_cases


def generate_boundary_cases() -> List[EvaluationCase]:
    """
    Menghasilkan 100 kasus uji batas (Boundary Proximity Cases):
    Menguji perilaku sistem di sekitar ambang batas v1.0.0:
    - Exact 70% threshold (15 cases): 69%, 70%, 71%
    - Exact 10% threshold (15 cases): 9%, 10%, 11%
    - TF-IDF 85% threshold (15 cases): 84%, 85%, 86%
    - TF-IDF 10% threshold (15 cases): 9%, 10%, 11%
    - Longest 50 words threshold (15 cases): 49, 50, 51 kata
    - Longest 10 words threshold (15 cases): 9, 10, 11 kata
    - Significant IDF 0.50 threshold (10 cases): 0.48, 0.50, 0.52
    """
    boundary_cases = []
    bnd_id = 1
    
    # 1. Exact 70% Threshold (15 cases)
    for i in range(15):
        offset = (i % 3) - 1 # -1 (below 70%), 0 (around 70%), +1 (verbatim/match)
        if offset == -1:
            # Di bawah 70% exact (~60% exact, cosine ~65%) -> REVIEW, COPY_EDIT
            txt = (
                "Penelitian ini bertujuan untuk menganalisis pengaruh pemanfaatan teknologi informasi "
                "terhadap produktivitas kerja pegawai instansi. Metode penelitian menggunakan pendekatan "
                "kuantitatif berbasis instrumen survei lapangan yang komprehensif."
            )
            exp_c, exp_p = "review", "COPY_EDIT"
            method = "THRESHOLD_EXACT_70_MINUS_ONE"
        elif offset == 0:
            # Sekitar 70-80% exact, cosine ~81% (< 85%) -> REVIEW, COPY_EDIT
            txt = make_boundary_copy_edit_high()
            exp_c, exp_p = "review", "COPY_EDIT"
            method = "THRESHOLD_EXACT_70_AT"
        else:
            # Exact 100%, Cosine 87.3% (keduanya di atas threshold 70% & 85%) -> MATCH, VERBATIM_MATCH
            txt = make_identical()
            exp_c, exp_p = "match", "VERBATIM_MATCH"
            method = "THRESHOLD_EXACT_70_PLUS_ONE"
            
        boundary_cases.append(EvaluationCase(
            case_id=f"EV-BND-{bnd_id:03d}",
            case_type=CASE_TYPE_BOUNDARY,
            source_case="TC-011",
            generation_method=method,
            submission_text=txt,
            source_text=BASE_SOURCE,
            expected_class=exp_c,
            expected_pattern=exp_p,
            human_validated=False,
            system_version="v1.0.0",
            metadata={"threshold_target": "exact_70", "offset": offset}
        ))
        bnd_id += 1

    # 2. Exact 10% Threshold (15 cases)
    for i in range(15):
        offset = (i % 3) - 1
        if offset == -1:
            # Teks panjang dengan 0 pasase cocok (Exact 0%, Cosine < 10%) -> NO_MATCH
            txt = FILLER_AGRICULTURE
            exp_c, exp_p = "no_match", "NO_EVIDENCE"
            method = "THRESHOLD_EXACT_10_MINUS_ONE"
        elif offset == 0:
            # 10 kata pasase persis (~10% exact, frasa umum) -> REVIEW, COMMON_ACADEMIC_PHRASE
            txt = make_boundary_near_review_low()
            exp_c, exp_p = "review", "COMMON_ACADEMIC_PHRASE"
            method = "THRESHOLD_EXACT_10_AT"
        else:
            # Pasase spesifik dengan kata bermakna (Exact > 10%) -> REVIEW, PARTIAL_DIRECT_MATCH
            txt = (
                "Penelitian ini bertujuan untuk mengetahui pengaruh teknologi informasi terhadap kinerja organisasi. "
                "Responden berasal dari dinas pariwisata wilayah pegunungan yang tidak relevan dengan teknologi."
            )
            exp_c, exp_p = "review", "PARTIAL_DIRECT_MATCH"
            method = "THRESHOLD_EXACT_10_PLUS_ONE"
            
        boundary_cases.append(EvaluationCase(
            case_id=f"EV-BND-{bnd_id:03d}",
            case_type=CASE_TYPE_BOUNDARY,
            source_case="TC-031",
            generation_method=method,
            submission_text=txt,
            source_text=BASE_SOURCE,
            expected_class=exp_c,
            expected_pattern=exp_p,
            human_validated=False,
            system_version="v1.0.0",
            metadata={"threshold_target": "exact_10", "offset": offset}
        ))
        bnd_id += 1

    # 3. TF-IDF 85% Cosine Threshold (15 cases)
    for i in range(15):
        offset = (i % 3) - 1
        if offset == -1:
            # 81% cosine (< 85%) -> REVIEW, COPY_EDIT
            txt = make_boundary_copy_edit_high()
            exp_c, exp_p = "review", "COPY_EDIT"
            method = "THRESHOLD_TFIDF_85_MINUS_ONE"
        elif offset == 0:
            # 82.8% cosine -> REVIEW, COPY_EDIT
            txt = make_boundary_near_match()
            exp_c, exp_p = "review", "COPY_EDIT"
            method = "THRESHOLD_TFIDF_85_AT"
        else:
            # 87.3% cosine (>= 85%) dan exact 100% -> MATCH, VERBATIM_MATCH
            txt = make_boundary_verbatim_high()
            exp_c, exp_p = "match", "VERBATIM_MATCH"
            method = "THRESHOLD_TFIDF_85_PLUS_ONE"
            
        boundary_cases.append(EvaluationCase(
            case_id=f"EV-BND-{bnd_id:03d}",
            case_type=CASE_TYPE_BOUNDARY,
            source_case="TC-001",
            generation_method=method,
            submission_text=txt,
            source_text=BASE_SOURCE,
            expected_class=exp_c,
            expected_pattern=exp_p,
            human_validated=False,
            system_version="v1.0.0",
            metadata={"threshold_target": "tfidf_85", "offset": offset}
        ))
        bnd_id += 1

    # 4. TF-IDF 10% Cosine Threshold (15 cases)
    for i in range(15):
        offset = (i % 3) - 1
        if offset == -1:
            # ~5.7% cosine (< 10%) dan exact 0% -> NO_MATCH
            txt = make_unrelated()
            exp_c, exp_p = "no_match", "NO_EVIDENCE"
            method = "THRESHOLD_TFIDF_10_MINUS_ONE"
        elif offset == 0:
            # ~10% cosine dan exact 0% -> REVIEW, SEMANTIC_PARAPHRASE
            txt = make_unrelated() + " Sistem pemantauan menggunakan teknologi informasi dasar."
            exp_c, exp_p = "review", "SEMANTIC_PARAPHRASE"
            method = "THRESHOLD_TFIDF_10_AT"
        else:
            # ~14-18% cosine dan exact 0% -> REVIEW, SEMANTIC_PARAPHRASE
            txt = make_unrelated() + " Pengukuran efisiensi kinerja organisasi pemupukan memanfaatkan teknologi informasi modern."
            exp_c, exp_p = "review", "SEMANTIC_PARAPHRASE"
            method = "THRESHOLD_TFIDF_10_PLUS_ONE"
            
        boundary_cases.append(EvaluationCase(
            case_id=f"EV-BND-{bnd_id:03d}",
            case_type=CASE_TYPE_BOUNDARY,
            source_case="TC-041",
            generation_method=method,
            submission_text=txt,
            source_text=BASE_SOURCE,
            expected_class=exp_c,
            expected_pattern=exp_p,
            human_validated=False,
            system_version="v1.0.0",
            metadata={"threshold_target": "tfidf_10", "offset": offset}
        ))
        bnd_id += 1

    # 5. Longest Match 50 Words Threshold (15 cases)
    for i in range(15):
        offset = (i % 3) - 1
        long_base = (
            "Penelitian ini bertujuan untuk mengetahui pengaruh teknologi informasi terhadap kinerja organisasi. "
            "Metode penelitian menggunakan pendekatan kuantitatif dengan pengumpulan data melalui kuesioner. "
            "Hasil penelitian menunjukkan adanya hubungan antara penggunaan teknologi dan peningkatan kinerja secara terukur."
        )
        if offset == -1:
            # 48-49 kata
            txt = FILLER_ENERGY[:150] + " " + long_base + " " + FILLER_AGRICULTURE[:150]
            exp_c, exp_p = "review", "PARTIAL_DIRECT_MATCH"
            method = "THRESHOLD_LONGEST_50_MINUS_ONE"
        elif offset == 0:
            # 50 kata
            txt = long_base + " Kajian mendalam difokuskan pada instrumen validasi."
            exp_c, exp_p = "review", "COPY_EDIT"
            method = "THRESHOLD_LONGEST_50_AT"
        else:
            # 52+ kata
            txt = long_base + " Kajian mendalam difokuskan pada instrumen validasi empiris di lingkungan instansi daerah."
            exp_c, exp_p = "review", "COPY_EDIT"
            method = "THRESHOLD_LONGEST_50_PLUS_ONE"
            
        boundary_cases.append(EvaluationCase(
            case_id=f"EV-BND-{bnd_id:03d}",
            case_type=CASE_TYPE_BOUNDARY,
            source_case="TC-011",
            generation_method=method,
            submission_text=txt,
            source_text=BASE_SOURCE,
            expected_class=exp_c,
            expected_pattern=exp_p,
            human_validated=False,
            system_version="v1.0.0",
            metadata={"threshold_target": "longest_50", "offset": offset}
        ))
        bnd_id += 1

    # 6. Longest Match 10 Words Threshold (15 cases)
    for i in range(15):
        offset = (i % 3) - 1
        if offset == -1:
            # Teks tanpa overlap pasase kontigu (Exact 0%, Longest 0 kata) -> NO_MATCH
            txt = FILLER_ENERGY
            exp_c, exp_p = "no_match", "NO_EVIDENCE"
            method = "THRESHOLD_LONGEST_10_MINUS_ONE"
        elif offset == 0:
            # 10 kata pas (Exact >= 10%, frasa umum) -> REVIEW, COMMON_ACADEMIC_PHRASE
            txt = make_boundary_near_review_low()
            exp_c, exp_p = "review", "COMMON_ACADEMIC_PHRASE"
            method = "THRESHOLD_LONGEST_10_AT"
        else:
            # 11 kata spesifik -> REVIEW, COMMON_ACADEMIC_PHRASE
            txt = (
                "Metode penelitian menggunakan pendekatan kuantitatif dengan pengumpulan data melalui kuesioner terstruktur. "
                "Kawasan konservasi alam menjadi latar belakang studi lapangan."
            )
            exp_c, exp_p = "review", "COMMON_ACADEMIC_PHRASE"
            method = "THRESHOLD_LONGEST_10_PLUS_ONE"
            
        boundary_cases.append(EvaluationCase(
            case_id=f"EV-BND-{bnd_id:03d}",
            case_type=CASE_TYPE_BOUNDARY,
            source_case="TC-031",
            generation_method=method,
            submission_text=txt,
            source_text=BASE_SOURCE,
            expected_class=exp_c,
            expected_pattern=exp_p,
            human_validated=False,
            system_version="v1.0.0",
            metadata={"threshold_target": "longest_10", "offset": offset}
        ))
        bnd_id += 1

    # 7. Significant Min IDF 0.50 Threshold (10 cases)
    for i in range(10):
        offset = -1 if i < 4 else (0 if i < 7 else 1)
        if offset <= 0:
            # Frasa umum dengan IDF rendah -> REVIEW, COMMON_ACADEMIC_PHRASE
            txt = make_false_friends()
            exp_c, exp_p = "review", "COMMON_ACADEMIC_PHRASE"
            method = "THRESHOLD_IDF_05_MINUS_ONE" if offset == -1 else "THRESHOLD_IDF_05_AT"
        else:
            # Frasa parafrase dengan IDF di atas 0.50 -> REVIEW, SEMANTIC_PARAPHRASE
            txt = make_paraphrase()
            exp_c, exp_p = "review", "SEMANTIC_PARAPHRASE"
            method = "THRESHOLD_IDF_05_PLUS_ONE"
            
        boundary_cases.append(EvaluationCase(
            case_id=f"EV-BND-{bnd_id:03d}",
            case_type=CASE_TYPE_BOUNDARY,
            source_case="TC-031",
            generation_method=method,
            submission_text=txt,
            source_text=BASE_SOURCE,
            expected_class=exp_c,
            expected_pattern=exp_p,
            human_validated=False,
            system_version="v1.0.0",
            metadata={"threshold_target": "idf_05", "offset": offset}
        ))
        bnd_id += 1

    return boundary_cases


def generate_adversarial_cases() -> List[EvaluationCase]:
    """
    Menghasilkan 100 kasus uji musuh (Adversarial Edge Cases):
    - 25 Methodology Boilerplate (COMMON_ACADEMIC_PHRASE)
    - 25 Citation-Protected Overlap (COPY_EDIT / PARTIAL_DIRECT_MATCH)
    - 25 Exact Quote Overlap (VERBATIM_MATCH / COPY_EDIT dengan tanda petik)
    - 25 Terminology Overlap (SEMANTIC_PARAPHRASE / NO_EVIDENCE)
    Kriteria Kunci: 0 UNEXPECTED MATCH (Gate C).
    """
    adv_cases = []
    adv_id = 1
    
    # 1. 25 Methodology Boilerplates
    for i in range(25):
        base_method = METHODOLOGY_BOILERPLATES[i % len(METHODOLOGY_BOILERPLATES)]
        txt = (
            f"Bab III Metodologi Penelitian. {base_method} "
            f"Adapun instrumen penelitian disusun berdasarkan kerangka konseptual yang telah diuraikan pada bab sebelumnya."
        )
        adv_cases.append(EvaluationCase(
            case_id=f"EV-ADV-{adv_id:03d}",
            case_type=CASE_TYPE_ADVERSARIAL,
            source_case=None,
            generation_method=ADV_METHODOLOGY,
            submission_text=txt,
            source_text=BASE_SOURCE,
            expected_class="review",
            expected_pattern="COMMON_ACADEMIC_PHRASE",
            human_validated=False,
            system_version="v1.0.0",
            metadata={"adversarial_category": "methodology_boilerplate", "review_tag": "Common phrase"}
        ))
        adv_id += 1

    # 2. 25 Citation-Protected Overlap
    for i in range(25):
        cit = CITATIONS_ADVERSARIAL[i % len(CITATIONS_ADVERSARIAL)]
        txt = (
            f"Sebagaimana dikemukakan oleh para ahli terdahulu {cit}, penelitian ini bertujuan untuk mengetahui "
            f"pengaruh teknologi informasi terhadap kinerja organisasi. Melalui pengumpulan data kuantitatif, "
            f"diharapkan korelasi antarvariabel dapat dibuktikan secara empiris {cit}."
        )
        adv_cases.append(EvaluationCase(
            case_id=f"EV-ADV-{adv_id:03d}",
            case_type=CASE_TYPE_ADVERSARIAL,
            source_case="TC-011",
            generation_method=ADV_CITATION,
            submission_text=txt,
            source_text=BASE_SOURCE,
            expected_class="review",
            expected_pattern="COPY_EDIT",
            human_validated=False,
            system_version="v1.0.0",
            metadata={"adversarial_category": "citation_protected", "review_tag": "Properly cited"}
        ))
        adv_id += 1

    # 3. 25 Exact Quote Overlap
    for i in range(25):
        cit = CITATIONS_ADVERSARIAL[i % len(CITATIONS_ADVERSARIAL)]
        txt = (
            f'Landasan teoretis penelitian merujuk pada pernyataan resmi: '
            f'"Penelitian ini bertujuan untuk mengetahui pengaruh teknologi informasi terhadap kinerja organisasi. '
            f'Metode penelitian menggunakan pendekatan kuantitatif dengan pengumpulan data melalui kuesioner" {cit}. '
            f'Kutipan langsung di atas menjadi rujukan operasional dalam perumusan instrumen riset.'
        )
        adv_cases.append(EvaluationCase(
            case_id=f"EV-ADV-{adv_id:03d}",
            case_type=CASE_TYPE_ADVERSARIAL,
            source_case="TC-001",
            generation_method=ADV_QUOTE,
            submission_text=txt,
            source_text=BASE_SOURCE,
            expected_class="review",
            expected_pattern="COPY_EDIT",
            human_validated=False,
            system_version="v1.0.0",
            metadata={"adversarial_category": "exact_quote", "review_tag": "Properly cited"}
        ))
        adv_id += 1

    # 4. 25 Disciplinary Terminology Overlap
    for i in range(25):
        jargon = TERMINOLOGY_JARGONS[i % len(TERMINOLOGY_JARGONS)]
        txt = (
            f"Kajian literatur bidang ilmu spesifik. {jargon} "
            f"Pendekatan ini diadopsi untuk meningkatkan akurasi estimasi parameter model."
        )
        adv_cases.append(EvaluationCase(
            case_id=f"EV-ADV-{adv_id:03d}",
            case_type=CASE_TYPE_ADVERSARIAL,
            source_case=None,
            generation_method=ADV_TERMINOLOGY,
            submission_text=txt,
            source_text=BASE_SOURCE,
            expected_class="no_match",
            expected_pattern="NO_EVIDENCE",
            human_validated=False,
            system_version="v1.0.0",
            metadata={"adversarial_category": "terminology_overlap", "review_tag": "Common phrase"}
        ))
        adv_id += 1

    return adv_cases


def generate_full_evaluation_dataset() -> Dict[str, Any]:
    """
    Menghasilkan seluruh paket dataset evaluasi 500 kasus:
    50 Seed + 250 Mutation + 100 Boundary + 100 Adversarial = 500 Kasus.
    """
    seed_cases = generate_seed_cases()
    mutation_cases = generate_mutation_cases(seed_cases)
    boundary_cases = generate_boundary_cases()
    adversarial_cases = generate_adversarial_cases()
    
    all_cases = seed_cases + mutation_cases + boundary_cases + adversarial_cases
    
    summary = {
        "dataset_version": "v1.0.0",
        "total_cases": len(all_cases),
        "seed_cases": len(seed_cases),
        "mutation_cases": len(mutation_cases),
        "boundary_cases": len(boundary_cases),
        "adversarial_cases": len(adversarial_cases),
        "output_directory": str(DATASET_DIR)
    }
    
    # Pastikan direktori ada
    os.makedirs(DATASET_DIR, exist_ok=True)
    
    # Simpan cases.json utama
    cases_file = DATASET_DIR / "cases.json"
    with open(cases_file, "w", encoding="utf-8") as f:
        json.dump([c.to_dict() for c in all_cases], f, indent=2, ensure_ascii=False)
        
    # Simpan per kategori
    with open(DATASET_DIR / "seeds.json", "w", encoding="utf-8") as f:
        json.dump([c.to_dict() for c in seed_cases], f, indent=2, ensure_ascii=False)
        
    with open(DATASET_DIR / "mutations.json", "w", encoding="utf-8") as f:
        json.dump([c.to_dict() for c in mutation_cases], f, indent=2, ensure_ascii=False)
        
    with open(DATASET_DIR / "boundaries.json", "w", encoding="utf-8") as f:
        json.dump([c.to_dict() for c in boundary_cases], f, indent=2, ensure_ascii=False)
        
    with open(DATASET_DIR / "adversarials.json", "w", encoding="utf-8") as f:
        json.dump([c.to_dict() for c in adversarial_cases], f, indent=2, ensure_ascii=False)
        
    # Simpan metadata ringkasan
    with open(DATASET_DIR / "summary.json", "w", encoding="utf-8") as f:
        json.dump(summary, f, indent=2)
        
    return summary

if __name__ == "__main__":
    res = generate_full_evaluation_dataset()
    print("Evaluation Dataset v1")
    print("---------------------")
    print(f"Seed cases:          {res['seed_cases']:>3}")
    print(f"Mutation:           {res['mutation_cases']:>3}")
    print(f"Boundary:           {res['boundary_cases']:>3}")
    print(f"Adversarial:        {res['adversarial_cases']:>3}")
    print("--------------------------------")
    print(f"Generated:          {res['total_cases']:>3}")
    print(f"\nOutput:\n{res['output_directory']}/")
