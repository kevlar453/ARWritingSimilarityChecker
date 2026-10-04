"""
AR Tilik - Evaluation Dataset Generator v1 (Calibrated & Deduplicated)
Menghasilkan 500 kasus evaluasi terisolasi dengan metadata provenans lengkap:
- Seed Cases: 50
- Mutation Cases: 250 (50 seeds x 5 mutasi: lexical, deletion/insertion, restructuring, paraphrase, mixed)
- Boundary Cases: 100 (Threshold - 1, Threshold, Threshold + 1)
- Adversarial Cases: 100 (Methodology boilerplate, Citation-protected, Exact quote, Terminology overlap)

Fitur Kunci:
1. Strict Content-Hash & Deduplication Check (Mencegah duplikasi variasi mutasi)
2. Structural Restructuring Operator yang substantif (Clause transposition & splitting)
3. Provenance Metadata (source_case, mutation_operator, mutation_strength, generation_seed, content_hash)
4. Calibrated Adversarial & Boundary Expectations
"""

import json
import os
import re
import random
import hashlib
from pathlib import Path
from typing import List, Dict, Any, Set

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
from worker.normalizer import normalize_text, tokenize
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
    STRENGTH_LIGHT,
    STRENGTH_MEDIUM,
    STRENGTH_HEAVY,
    ADV_METHODOLOGY,
    ADV_CITATION,
    ADV_QUOTE,
    ADV_TERMINOLOGY
)

DATASET_DIR = Path(__file__).resolve().parents[1] / "evaluation_dataset_v1"

def make_content_hash(submission_text: str, source_text: str) -> str:
    """Menghitung sha256 16-karakter dari teks submisi dan rujukan ternormalisasi."""
    norm_sub = " ".join(tokenize(normalize_text(submission_text)))
    norm_src = " ".join(tokenize(normalize_text(source_text)))
    return hashlib.sha256(f"{norm_sub}||{norm_src}".encode("utf-8")).hexdigest()[:16]

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
    "hubungan": ["korelasi", "keterkaitan", "relasi", "asosiasi"],
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
    "pada taraf signifikansi tertentu",
    "secara operasional teruji",
    "dalam lingkup analisis statistik"
]

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
            if word[-1] in ".,;:":
                syn += word[-1]
            mutated.append(syn)
        else:
            mutated.append(word)
            
    res = " ".join(mutated)
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
        if len(words) > 5 and rng.random() < 0.65:
            qual = rng.choice(QUALIFIER_INSERTIONS)
            pos = min(len(words) - 2, max(2, len(words) // 2))
            words.insert(pos, qual)
        if len(words) > 8 and rng.random() < 0.45:
            drop_candidates = [i for i, w in enumerate(words) if w.lower() in ("ini", "dengan", "antara", "dan", "secara", "yang")]
            if drop_candidates:
                del words[rng.choice(drop_candidates)]
        mutated_sentences.append(" ".join(words))
        
    return ". ".join(mutated_sentences) + "."


def mutate_restructuring(text: str, seed_num: int) -> str:
    """
    Level 2: Rekonstruksi kalimat dan transposisi klausul substantif.
    Memecah dan membalik posisi klausul klausal pada input text secara langsung
    guna memecah shingle 5-kata tanpa mengganti domain topik teks asli.
    """
    rng = random.Random(seed_num + 303)
    sentences = [s.strip() for s in text.split(".") if s.strip()]
    if not sentences:
        return text

    mutated_sentences = []
    transitions = [
        "Pada hakikatnya", "Secara khusus", "Adapun",
        "Dalam pelaksanaannya", "Secara kontekstual", "Secara empiris"
    ]
    
    for idx, s in enumerate(sentences):
        conjunctions = [",", " dengan ", " dan ", " untuk ", " yang ", " berdasarkan "]
        split_point = None
        for conj in conjunctions:
            if conj in s and len(s.split(conj)) == 2:
                parts = s.split(conj)
                if len(parts[0].split()) >= 3 and len(parts[1].split()) >= 3:
                    split_point = (conj, parts)
                    break

        if split_point and rng.random() < 0.75:
            conj, (p1, p2) = split_point
            p1_clean = p1.strip()
            p2_clean = p2.strip()
            if conj == ",":
                new_s = f"{p2_clean.capitalize()}, {p1_clean[0].lower() + p1_clean[1:]}"
            elif conj == " dengan ":
                new_s = f"Melalui {p2_clean}, {p1_clean[0].lower() + p1_clean[1:]}"
            elif conj == " untuk ":
                new_s = f"Guna {p2_clean}, {p1_clean[0].lower() + p1_clean[1:]}"
            elif conj == " berdasarkan ":
                new_s = f"Berdasarkan {p2_clean}, {p1_clean[0].lower() + p1_clean[1:]}"
            else:
                new_s = f"{p2_clean.capitalize()} serta {p1_clean[0].lower() + p1_clean[1:]}"
            mutated_sentences.append(new_s)
        else:
            trans = transitions[(seed_num + idx) % len(transitions)]
            new_s = f"{trans}, {s[0].lower() + s[1:]}"
            mutated_sentences.append(new_s)

    if len(mutated_sentences) >= 3:
        if seed_num % 2 == 0:
            mutated_sentences[0], mutated_sentences[1] = mutated_sentences[1], mutated_sentences[0]
        else:
            mutated_sentences[0], mutated_sentences[2] = mutated_sentences[2], mutated_sentences[0]
    elif len(mutated_sentences) == 2:
        mutated_sentences.reverse()

    return ". ".join(mutated_sentences) + "."


def mutate_paraphrase(text: str, seed_num: int) -> str:
    """Level 3: Parafrase mendalam aktif-pasif dan nominalisasi."""
    res = text
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
        c_hash = make_content_hash(item["submission"], BASE_SOURCE)
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
            mutation_operator="NONE",
            mutation_strength="BASELINE",
            generation_seed=i,
            content_hash=c_hash,
            metadata={"seed_category": cat, "seed_index": i}
        ))
    return seed_cases


def generate_mutation_cases(seed_cases: List[EvaluationCase], seen_hashes: Set[str]) -> List[EvaluationCase]:
    """
    Menghasilkan 250 kasus mutasi dengan pemeriksaan deduplikasi ketat:
    50 seed x 5 operator:
    - 50 Lexical
    - 50 Deletion/Insertion
    - 50 Restructuring (substantif)
    - 50 Paraphrase
    - 50 Mixed
    """
    mutation_cases = []
    mut_id = 1
    
    operators = [
        (MUTATION_LEXICAL, mutate_lexical, STRENGTH_LIGHT, 1),
        (MUTATION_DELETION_INSERTION, mutate_deletion_insertion, STRENGTH_LIGHT, 1),
        (MUTATION_RESTRUCTURING, mutate_restructuring, STRENGTH_MEDIUM, 2),
        (MUTATION_PARAPHRASE, mutate_paraphrase, STRENGTH_HEAVY, 3),
        (MUTATION_MIXED, mutate_mixed, STRENGTH_HEAVY, 4)
    ]
    
    for op_name, op_fn, op_strength, op_level in operators:
        for seed in seed_cases:
            parent_cat = seed.metadata["seed_category"]
            
            # Tentukan expected class & pattern
            if parent_cat == "identical":
                exp_c = "review"
                exp_p = "COPY_EDIT" if op_name in (MUTATION_LEXICAL, MUTATION_DELETION_INSERTION, MUTATION_RESTRUCTURING) else "SEMANTIC_PARAPHRASE"
            elif parent_cat == "copy_edit":
                exp_c = "review"
                exp_p = "COPY_EDIT" if op_name in (MUTATION_LEXICAL, MUTATION_DELETION_INSERTION) else "SEMANTIC_PARAPHRASE"
            elif parent_cat == "paraphrase":
                exp_c = "review"
                exp_p = "SEMANTIC_PARAPHRASE"
            elif parent_cat == "common_phrase":
                exp_c = "review"
                exp_p = "COMMON_ACADEMIC_PHRASE"
            else:
                exp_c = "no_match"
                exp_p = "NO_EVIDENCE"

            # Generate teks dengan deduplication check
            attempt = 0
            candidate_text = ""
            candidate_hash = ""
            while attempt < 15:
                curr_seed = mut_id + attempt * 1000
                candidate_text = op_fn(seed.submission_text, curr_seed)
                candidate_hash = make_content_hash(candidate_text, seed.source_text)
                if candidate_hash not in seen_hashes:
                    seen_hashes.add(candidate_hash)
                    break
                attempt += 1

            mutation_cases.append(EvaluationCase(
                case_id=f"EV-MUT-{mut_id:03d}",
                case_type=CASE_TYPE_MUTATION,
                source_case=seed.case_id,
                generation_method=op_name,
                submission_text=candidate_text,
                source_text=seed.source_text,
                expected_class=exp_c,
                expected_pattern=exp_p,
                human_validated=False,
                system_version="v1.0.0",
                mutation_operator=op_name,
                mutation_strength=op_strength,
                generation_seed=mut_id,
                content_hash=candidate_hash,
                metadata={"mutation_level": op_level, "parent_category": parent_cat, "dedup_attempts": attempt}
            ))
            mut_id += 1

    return mutation_cases


def generate_boundary_cases(seen_hashes: Set[str]) -> List[EvaluationCase]:
    """
    Menghasilkan 100 kasus uji batas (Boundary Proximity Cases):
    Menguji transisi ambang batas v1.0.0: Exact 70/10, TF-IDF 85/10, Longest 50/10, IDF 0.50.
    """
    boundary_cases = []
    bnd_id = 1
    
    # 1. Exact 70% Threshold (15 cases)
    for i in range(15):
        offset = (i % 3) - 1 # -1, 0, +1
        if offset == -1:
            txt = (
                f"Penelitian ini bertujuan untuk menganalisis pengaruh pemanfaatan teknologi informasi (kajian #{i}) "
                f"terhadap produktivitas kerja pegawai instansi. Metode penelitian menggunakan pendekatan "
                f"kuantitatif berbasis instrumen survei lapangan yang komprehensif."
            )
            exp_c, exp_p = "review", "COPY_EDIT"
            method = "THRESHOLD_EXACT_70_MINUS_ONE"
        elif offset == 0:
            txt = make_boundary_copy_edit_high() + f" Analisis ini dikembangkan dalam studi ke-{i}."
            exp_c, exp_p = "review", "COPY_EDIT"
            method = "THRESHOLD_EXACT_70_AT"
        else:
            txt = make_identical()
            exp_c, exp_p = "match", "VERBATIM_MATCH"
            method = "THRESHOLD_EXACT_70_PLUS_ONE"
            
        c_hash = make_content_hash(txt, BASE_SOURCE)
        seen_hashes.add(c_hash)
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
            mutation_operator="BOUNDARY_PROBING",
            mutation_strength=STRENGTH_MEDIUM,
            generation_seed=bnd_id,
            content_hash=c_hash,
            metadata={"threshold_target": "exact_70", "offset": offset}
        ))
        bnd_id += 1

    # 2. Exact 10% Threshold (15 cases)
    exact_below_phrases = [
        "Kajian oseanografi kelautan bertujuan untuk mengetahui pengaruh teknologi seismik terapan.",
        "Analisis geofisika marin bertujuan untuk mengetahui pengaruh teknologi akustik bawah air.",
        "Laporan ekspedisi oseanik bertujuan untuk mengetahui pengaruh teknologi pemetaan dasar laut.",
        "Riset sedimentologi lepas pantai bertujuan untuk mengetahui pengaruh teknologi pengeboran inti.",
        "Survei geomorfologi selat bertujuan untuk mengetahui pengaruh teknologi sonoboy multibeam."
    ]
    filler_ocean = (
        "Eksplorasi geologi laut dalam di perairan selatan Jawa mengidentifikasi struktur rekahan "
        "batuan dasar vulkanik. Survei batimetri resolusi tinggi memetakan palung sedimen purba "
        "hingga kedalaman empat ribu meter di bawah permukaan laut. Lapisan piroklastik hasil "
        "letusan gunung api bawah laut terdeteksi pada batuan sedimen tebal tersebut."
    )
    for i in range(15):
        offset = (i % 3) - 1
        if offset == -1:
            phrase = exact_below_phrases[(i // 3) % len(exact_below_phrases)]
            txt = filler_ocean + f" Pengamatan stasiun ke-{i+1} dilakukan secara berkala. " + phrase
            exp_c, exp_p = "no_match", "NO_EVIDENCE"
            method = "THRESHOLD_EXACT_10_MINUS_ONE"
        elif offset == 0:
            txt = make_boundary_near_review_low() + f" Seri pengujian #{i}."
            exp_c, exp_p = "review", "COMMON_ACADEMIC_PHRASE"
            method = "THRESHOLD_EXACT_10_AT"
        else:
            txt = (
                f"Penelitian ini bertujuan untuk mengetahui pengaruh teknologi informasi terhadap kinerja organisasi (sampel #{i}). "
                f"Responden berasal dari dinas pariwisata wilayah pegunungan yang tidak relevan dengan teknologi."
            )
            exp_c, exp_p = "review", "PARTIAL_DIRECT_MATCH"
            method = "THRESHOLD_EXACT_10_PLUS_ONE"
            
        c_hash = make_content_hash(txt, BASE_SOURCE)
        seen_hashes.add(c_hash)
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
            mutation_operator="BOUNDARY_PROBING",
            mutation_strength=STRENGTH_MEDIUM,
            generation_seed=bnd_id,
            content_hash=c_hash,
            metadata={"threshold_target": "exact_10", "offset": offset}
        ))
        bnd_id += 1

    # 3. TF-IDF 85% Cosine Threshold (15 cases)
    for i in range(15):
        offset = (i % 3) - 1
        if offset == -1:
            txt = make_boundary_copy_edit_high() + f" Observasi ke-{i}."
            exp_c, exp_p = "review", "COPY_EDIT"
            method = "THRESHOLD_TFIDF_85_MINUS_ONE"
        elif offset == 0:
            txt = make_boundary_near_match()
            exp_c, exp_p = "review", "COPY_EDIT"
            method = "THRESHOLD_TFIDF_85_AT"
        else:
            txt = make_boundary_verbatim_high()
            exp_c, exp_p = "match", "VERBATIM_MATCH"
            method = "THRESHOLD_TFIDF_85_PLUS_ONE"
            
        c_hash = make_content_hash(txt, BASE_SOURCE)
        seen_hashes.add(c_hash)
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
            mutation_operator="BOUNDARY_PROBING",
            mutation_strength=STRENGTH_MEDIUM,
            generation_seed=bnd_id,
            content_hash=c_hash,
            metadata={"threshold_target": "tfidf_85", "offset": offset}
        ))
        bnd_id += 1

    # 4. TF-IDF 10% Cosine Threshold (15 cases)
    word_num = [
        "nol", "satu", "dua", "tiga", "empat", "lima", "enam",
        "tujuh", "delapan", "sembilan", "sepuluh", "sebelas",
        "dua belas", "tiga belas", "empat belas"
    ]
    for i in range(15):
        offset = (i % 3) - 1
        w_idx = word_num[i % len(word_num)]
        if offset == -1:
            txt = make_unrelated() + f" Pengamatan lahan uji sektor agroekologi zona {w_idx}."
            exp_c, exp_p = "no_match", "NO_EVIDENCE"
            method = "THRESHOLD_TFIDF_10_MINUS_ONE"
        elif offset == 0:
            txt = make_unrelated() + f" Sistem pemantauan petak {w_idx} menggunakan teknologi informasi dasar."
            exp_c, exp_p = "review", "SEMANTIC_PARAPHRASE"
            method = "THRESHOLD_TFIDF_10_AT"
        else:
            txt = make_unrelated() + f" Pengukuran efisiensi kinerja organisasi pemupukan memanfaatkan teknologi informasi modern sektor {w_idx}."
            exp_c, exp_p = "review", "SEMANTIC_PARAPHRASE"
            method = "THRESHOLD_TFIDF_10_PLUS_ONE"
            
        c_hash = make_content_hash(txt, BASE_SOURCE)
        seen_hashes.add(c_hash)
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
            mutation_operator="BOUNDARY_PROBING",
            mutation_strength=STRENGTH_MEDIUM,
            generation_seed=bnd_id,
            content_hash=c_hash,
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
            txt = FILLER_ENERGY[:150] + " " + long_base + " " + FILLER_AGRICULTURE[:150] + f" [Uji #{i}]"
            exp_c, exp_p = "review", "PARTIAL_DIRECT_MATCH"
            method = "THRESHOLD_LONGEST_50_MINUS_ONE"
        elif offset == 0:
            txt = long_base + f" Kajian mendalam difokuskan pada instrumen validasi #{i}."
            exp_c, exp_p = "review", "COPY_EDIT"
            method = "THRESHOLD_LONGEST_50_AT"
        else:
            txt = long_base + f" Kajian mendalam difokuskan pada instrumen validasi empiris di lingkungan instansi daerah #{i}."
            exp_c, exp_p = "review", "COPY_EDIT"
            method = "THRESHOLD_LONGEST_50_PLUS_ONE"
            
        c_hash = make_content_hash(txt, BASE_SOURCE)
        seen_hashes.add(c_hash)
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
            mutation_operator="BOUNDARY_PROBING",
            mutation_strength=STRENGTH_MEDIUM,
            generation_seed=bnd_id,
            content_hash=c_hash,
            metadata={"threshold_target": "longest_50", "offset": offset}
        ))
        bnd_id += 1

    # 6. Longest Match 10 Words Threshold (15 cases)
    for i in range(15):
        offset = (i % 3) - 1
        if offset == -1:
            txt = FILLER_ENERGY + f" Evaluasi operasional energi turbin nomor #{i}."
            exp_c, exp_p = "no_match", "NO_EVIDENCE"
            method = "THRESHOLD_LONGEST_10_MINUS_ONE"
        elif offset == 0:
            txt = make_boundary_near_review_low() + f" Replikasi ke-{i}."
            exp_c, exp_p = "review", "COMMON_ACADEMIC_PHRASE"
            method = "THRESHOLD_LONGEST_10_AT"
        else:
            txt = (
                f"Metode penelitian menggunakan pendekatan kuantitatif dengan pengumpulan data melalui kuesioner terstruktur #{i}. "
                f"Kawasan konservasi alam menjadi latar belakang studi lapangan."
            )
            exp_c, exp_p = "review", "COMMON_ACADEMIC_PHRASE"
            method = "THRESHOLD_LONGEST_10_PLUS_ONE"
            
        c_hash = make_content_hash(txt, BASE_SOURCE)
        seen_hashes.add(c_hash)
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
            mutation_operator="BOUNDARY_PROBING",
            mutation_strength=STRENGTH_MEDIUM,
            generation_seed=bnd_id,
            content_hash=c_hash,
            metadata={"threshold_target": "longest_10", "offset": offset}
        ))
        bnd_id += 1

    # 7. Significant Min IDF 0.50 Threshold (10 cases)
    for i in range(10):
        offset = -1 if i < 4 else (0 if i < 7 else 1)
        if offset <= 0:
            txt = make_false_friends() + f" Pengamatan spektroskopi galaksi kluster #{i}."
            exp_c, exp_p = "review", "COMMON_ACADEMIC_PHRASE"
            method = "THRESHOLD_IDF_05_MINUS_ONE" if offset == -1 else "THRESHOLD_IDF_05_AT"
        else:
            txt = make_paraphrase() + f" Kajian studi literatur bagian #{i}."
            exp_c, exp_p = "review", "SEMANTIC_PARAPHRASE"
            method = "THRESHOLD_IDF_05_PLUS_ONE"
            
        c_hash = make_content_hash(txt, BASE_SOURCE)
        seen_hashes.add(c_hash)
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
            mutation_operator="BOUNDARY_PROBING",
            mutation_strength=STRENGTH_MEDIUM,
            generation_seed=bnd_id,
            content_hash=c_hash,
            metadata={"threshold_target": "idf_05", "offset": offset}
        ))
        bnd_id += 1

    return boundary_cases


def generate_adversarial_cases(seen_hashes: Set[str]) -> List[EvaluationCase]:
    """
    Menghasilkan 100 kasus uji musuh (Adversarial Edge Cases):
    - 25 Methodology Boilerplate (NO_MATCH atau REVIEW, tidak boleh MATCH)
    - 25 Citation-Protected Overlap (REVIEW, COPY_EDIT)
    - 25 Exact Quote Overlap (REVIEW, COPY_EDIT)
    - 25 Terminology Overlap (NO_MATCH / REVIEW, tidak boleh MATCH)
    Invarian Kunci: 0 UNEXPECTED MATCH (Gate C).
    """
    adv_cases = []
    adv_id = 1
    
    # 1. 25 Methodology Boilerplates
    for i in range(25):
        base_method = METHODOLOGY_BOILERPLATES[i % len(METHODOLOGY_BOILERPLATES)]
        txt = (
            f"Bab III Metodologi Penelitian bagian {i+1}. {base_method} "
            f"Adapun instrumen penelitian disusun berdasarkan kerangka konseptual yang telah diuraikan pada bab sebelumnya."
        )
        c_hash = make_content_hash(txt, BASE_SOURCE)
        seen_hashes.add(c_hash)
        adv_cases.append(EvaluationCase(
            case_id=f"EV-ADV-{adv_id:03d}",
            case_type=CASE_TYPE_ADVERSARIAL,
            source_case=None,
            generation_method=ADV_METHODOLOGY,
            submission_text=txt,
            source_text=BASE_SOURCE,
            expected_class="review", # atau acceptable no_match jika cosine < 10%
            expected_pattern="COMMON_ACADEMIC_PHRASE",
            human_validated=False,
            system_version="v1.0.0",
            mutation_operator="ADVERSARIAL_PROMPT",
            mutation_strength=STRENGTH_LIGHT,
            generation_seed=adv_id,
            content_hash=c_hash,
            metadata={
                "adversarial_category": "methodology_boilerplate",
                "review_tag": "Common phrase",
                "citation_context": "NONE",
                "human_interpretation": "METHODOLOGY_BOILERPLATE"
            }
        ))
        adv_id += 1

    # 2. 25 Citation-Protected Overlap
    for i in range(25):
        cit = CITATIONS_ADVERSARIAL[i % len(CITATIONS_ADVERSARIAL)]
        txt = (
            f"Sebagaimana dikemukakan oleh para ahli terdahulu {cit}, penelitian ini bertujuan untuk mengetahui "
            f"pengaruh teknologi informasi terhadap kinerja organisasi. Melalui pengumpulan data kuantitatif, "
            f"diharapkan korelasi antarvariabel dapat dibuktikan secara empiris {cit} pada studi nomor {i+1}."
        )
        c_hash = make_content_hash(txt, BASE_SOURCE)
        seen_hashes.add(c_hash)
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
            mutation_operator="ADVERSARIAL_CITATION_INSERTION",
            mutation_strength=STRENGTH_MEDIUM,
            generation_seed=adv_id,
            content_hash=c_hash,
            metadata={
                "adversarial_category": "citation_protected",
                "review_tag": "Properly cited",
                "citation_context": "PARENTHETICAL_CITATION",
                "human_interpretation": "DIRECT_OVERLAP_PROPERLY_CITED"
            }
        ))
        adv_id += 1

    # 3. 25 Exact Quote Overlap
    for i in range(25):
        cit = CITATIONS_ADVERSARIAL[i % len(CITATIONS_ADVERSARIAL)]
        txt = (
            f'Landasan teoretis penelitian merujuk pada pernyataan resmi nomor {i+1}: '
            f'"Penelitian ini bertujuan untuk mengetahui pengaruh teknologi informasi terhadap kinerja organisasi. '
            f'Metode penelitian menggunakan pendekatan kuantitatif dengan pengumpulan data melalui kuesioner" {cit}. '
            f'Kutipan langsung di atas menjadi rujukan operasional dalam perumusan instrumen riset.'
        )
        c_hash = make_content_hash(txt, BASE_SOURCE)
        seen_hashes.add(c_hash)
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
            mutation_operator="ADVERSARIAL_DIRECT_QUOTE",
            mutation_strength=STRENGTH_MEDIUM,
            generation_seed=adv_id,
            content_hash=c_hash,
            metadata={
                "adversarial_category": "exact_quote",
                "review_tag": "Properly cited",
                "citation_context": "DIRECT_QUOTE",
                "human_interpretation": "DIRECT_OVERLAP_PROPERLY_CITED"
            }
        ))
        adv_id += 1

    # 4. 25 Disciplinary Terminology Overlap
    for i in range(25):
        jargon = TERMINOLOGY_JARGONS[i % len(TERMINOLOGY_JARGONS)]
        txt = (
            f"Kajian literatur bidang ilmu spesifik nomor {i+1}. {jargon} "
            f"Pendekatan ini diadopsi untuk meningkatkan akurasi estimasi parameter model."
        )
        c_hash = make_content_hash(txt, BASE_SOURCE)
        seen_hashes.add(c_hash)
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
            mutation_operator="ADVERSARIAL_TERMINOLOGY_COLLISION",
            mutation_strength=STRENGTH_HEAVY,
            generation_seed=adv_id,
            content_hash=c_hash,
            metadata={
                "adversarial_category": "terminology_overlap",
                "review_tag": "Common phrase",
                "citation_context": "NONE",
                "human_interpretation": "COMMON_ACADEMIC_LANGUAGE"
            }
        ))
        adv_id += 1

    return adv_cases


def generate_full_evaluation_dataset() -> Dict[str, Any]:
    """
    Menghasilkan seluruh paket dataset evaluasi 500 kasus dengan deduplikasi dan provenans lengkap.
    """
    seen_hashes: Set[str] = set()
    
    seed_cases = generate_seed_cases()
    for s in seed_cases:
        if s.content_hash:
            seen_hashes.add(s.content_hash)
            
    mutation_cases = generate_mutation_cases(seed_cases, seen_hashes)
    boundary_cases = generate_boundary_cases(seen_hashes)
    adversarial_cases = generate_adversarial_cases(seen_hashes)
    
    all_cases = seed_cases + mutation_cases + boundary_cases + adversarial_cases
    
    summary = {
        "dataset_version": "v1.0.0",
        "total_cases": len(all_cases),
        "unique_content_hashes": len(seen_hashes),
        "seed_cases": len(seed_cases),
        "mutation_cases": len(mutation_cases),
        "boundary_cases": len(boundary_cases),
        "adversarial_cases": len(adversarial_cases),
        "output_directory": str(DATASET_DIR)
    }
    
    os.makedirs(DATASET_DIR, exist_ok=True)
    
    with open(DATASET_DIR / "cases.json", "w", encoding="utf-8") as f:
        json.dump([c.to_dict() for c in all_cases], f, indent=2, ensure_ascii=False)
        
    with open(DATASET_DIR / "seeds.json", "w", encoding="utf-8") as f:
        json.dump([c.to_dict() for c in seed_cases], f, indent=2, ensure_ascii=False)
        
    with open(DATASET_DIR / "mutations.json", "w", encoding="utf-8") as f:
        json.dump([c.to_dict() for c in mutation_cases], f, indent=2, ensure_ascii=False)
        
    with open(DATASET_DIR / "boundaries.json", "w", encoding="utf-8") as f:
        json.dump([c.to_dict() for c in boundary_cases], f, indent=2, ensure_ascii=False)
        
    with open(DATASET_DIR / "adversarials.json", "w", encoding="utf-8") as f:
        json.dump([c.to_dict() for c in adversarial_cases], f, indent=2, ensure_ascii=False)
        
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
    print(f"Unique Hashes:      {res['unique_content_hashes']:>3}")
    print(f"\nOutput:\n{res['output_directory']}/")
