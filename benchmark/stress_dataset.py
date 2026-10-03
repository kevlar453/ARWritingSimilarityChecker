import random
from benchmark.dataset import BASE_SOURCE

# Filler paragraphs bertema riset orisinal non-IT (ekonomi, energi terbarukan, biologi)
FILLER_ENERGY = (
    "Pengembangan energi baru terbarukan di kawasan pesisir menghadapi berbagai tantangan "
    "teknis dan regulasi. Turbin angin lepas pantai memerlukan material khusus yang tahan "
    "terhadap korosi air laut serta fluktuasi kecepatan angin musiman. Pemerintah daerah "
    "bersama konsorsium industri energi mulai menguji coba prototipe turbin dengan kapasitas "
    "daya terpasang sebesar lima megawatt. Evaluasi kelayakan investasi menunjukkan bahwa "
    "efisiensi konversi daya meningkat secara konsisten selama masa transisi operasional. "
    "Integrasi jaringan transmisi bawah laut menuju gardu induk daratan membutuhkan perencanaan "
    "matang guna meminimalkan lonjakan tegangan dan distorsi harmonik. Selain itu, aspek pembiayaan "
    "berkelanjutan dan skema kemitraan pemerintah swasta memegang peranan penting dalam "
    "menjamin stabilitas pasokan listrik jangka panjang bagi masyarakat pesisir kepulauan."
)

FILLER_AGRICULTURE = (
    "Budidaya tanaman kedelai varietas unggul pada lahan kering masam memerlukan "
    "perlakuan ameliorasi tanah melalui pemberian kapur pertanian dan pupuk hayati. "
    "Ketersediaan fosfor terlarut seringkali menjadi faktor pembatas utama dalam fase "
    "pembentukan bintil akar dan pengisian polong tanaman. Penerapan teknik irigasi tetes "
    "mampu mengoptimalkan penggunaan air irigasi sekaligus menekan pertumbuhan gulma liar. "
    "Hasil pemantauan agroekosistem menunjukkan peningkatan biomassa tanaman seiring dengan "
    "perbaikan kapasitas tukar kation dan aktivitas mikroorganisme tanah di sekitar rizosfer. "
    "Petani setempat mulai mengadopsi pola tanam rotasi legum dengan jagung untuk memutus "
    "siklus hidup hama penggerek batang dan mempertahankan kesuburan lapisan olah tanah."
)

# 1. Needle-in-a-Haystack Generator (Submisi panjang dengan pasase plagiat 30 kata di tengah)
def make_needle_haystack(variant_id: int) -> str:
    needle = (
        "Metode penelitian menggunakan pendekatan kuantitatif dengan pengumpulan data "
        "melalui kuesioner. Hasil penelitian menunjukkan adanya hubungan antara "
        "penggunaan teknologi dan peningkatan kinerja."
    )
    if variant_id % 2 == 0:
        return FILLER_ENERGY + " " + needle + " " + FILLER_AGRICULTURE
    return FILLER_AGRICULTURE + " " + needle + " " + FILLER_ENERGY

# 2. Boundary Proximity Generator
def make_boundary_verbatim_high() -> str:
    # 87.29% cosine (identical clone)
    return BASE_SOURCE

def make_boundary_near_match() -> str:
    # Diubah 1 kata minor di akhir -> tepat di sekitar threshold 85%
    return BASE_SOURCE[:-1] + " secara signifikan."

def make_boundary_copy_edit_high() -> str:
    # Diubah 2 kata -> ~81% cosine, pasase 26 kata
    return (
        "Penelitian ini bertujuan untuk menganalisis dampak teknologi informasi "
        "terhadap kinerja organisasi. Metode penelitian menggunakan pendekatan "
        "kuantitatif dengan pengumpulan data melalui kuesioner. Hasil penelitian "
        "menunjukkan adanya hubungan antara penggunaan teknologi dan peningkatan kinerja."
    )

def make_boundary_near_review_low() -> str:
    # 10 kata pasase persis dari BASE_SOURCE
    return (
        "Metode penelitian menggunakan pendekatan kuantitatif dengan pengumpulan data melalui kuesioner. "
        "Semua responden berasal dari dinas pariwisata daerah pegunungan yang tidak relevan."
    )

def make_boundary_below_review() -> str:
    # 8 kata (di bawah batas 10 kata pasase, topik pertanian)
    return (
        "Metode penelitian menggunakan pendekatan kuantitatif dengan pengumpulan tanaman teh "
        "pada musim kemarau panjang di dataran tinggi."
    )

# 3. Mosaic / Patchwriting Generator (Kombinasi multi-sumber)
def make_mosaic_patchwriting() -> str:
    part1 = "Penelitian ini bertujuan untuk mengetahui pengaruh teknologi informasi terhadap kinerja organisasi."
    part2 = "Dokumen sumber 2 membahas objek penelitian dengan karakteristik yang berbeda."
    filler = "Kajian pustaka disusun secara sistematis untuk memperkaya perspektif kajian teoritis."
    return part1 + " " + filler + " " + part2

# 4. Citation Noise Generator (Interleaved citation noise)
def make_citation_noise() -> str:
    return (
        "Penelitian ini (Santoso et al., 2021) bertujuan untuk mengetahui pengaruh teknologi "
        "informasi (Rahman & Wijaya, 2022) terhadap kinerja organisasi. Metode penelitian "
        "(Pratama, 2020) menggunakan pendekatan kuantitatif dengan pengumpulan data melalui "
        "kuesioner (Hidayat, 2023). Hasil penelitian menunjukkan adanya hubungan antara "
        "penggunaan teknologi dan peningkatan kinerja (Utomo & Susilo, 2024)."
    )

# 5. Micro-Submissions
def make_micro_matched() -> str:
    return "pengaruh teknologi informasi terhadap kinerja organisasi"

def make_micro_unrelated() -> str:
    return "hujan deras mengguyur perkebunan sawit"

# 6. False Friends / Buzzword Collisions
def make_false_friends() -> str:
    return (
        "Metode penelitian kuantitatif ini menganalisis data spektrum galaksi spiral "
        "menggunakan teleskop inframerah. Berdasarkan hasil penelitian yang telah dilakukan, "
        "kandungan hidrogen molekuler menunjukkan karakteristik pergeseran merah yang unik."
    )

def generate_stress_dataset() -> list[dict]:
    """
    Menghasilkan 50 kasus uji ekstrem:
    - 10 Needle-in-a-Haystack (expected: review)
    - 10 Boundary Proximity (5 expected review, 3 expected no_match, 2 expected match)
    - 10 Mosaic / Patchwriting (expected: review)
    - 10 Citation Noise (expected: review)
    - 5 Micro-Submissions (2 expected review, 3 expected no_match)
    - 5 False Friends (expected: no_match)
    """
    cases = []
    case_num = 1
    
    # 1. 10 Needle-in-a-Haystack
    for i in range(10):
        cases.append({
            "name": f"STC-{case_num:03d}",
            "category": "needle_haystack",
            "submission": make_needle_haystack(i),
            "expected": "review"
        })
        case_num += 1
        
    # 2. 10 Boundary Proximity
    boundary_specs = [
        ("boundary_verbatim", "match", make_boundary_verbatim_high),
        ("boundary_verbatim", "match", make_boundary_near_match),
        ("boundary_copy_edit", "review", make_boundary_copy_edit_high),
        ("boundary_copy_edit", "review", make_boundary_copy_edit_high),
        ("boundary_near_review", "review", make_boundary_near_review_low),
        ("boundary_near_review", "review", make_boundary_near_review_low),
        ("boundary_near_review", "review", make_boundary_near_review_low),
        ("boundary_below_review", "no_match", make_boundary_below_review),
        ("boundary_below_review", "no_match", make_boundary_below_review),
        ("boundary_below_review", "no_match", make_boundary_below_review),
    ]
    for cat, exp, fn in boundary_specs:
        cases.append({
            "name": f"STC-{case_num:03d}",
            "category": cat,
            "submission": fn(),
            "expected": exp
        })
        case_num += 1
        
    # 3. 10 Mosaic Patchwriting
    for _ in range(10):
        cases.append({
            "name": f"STC-{case_num:03d}",
            "category": "mosaic_patchwriting",
            "submission": make_mosaic_patchwriting(),
            "expected": "review"
        })
        case_num += 1
        
    # 4. 10 Citation Noise
    for _ in range(10):
        cases.append({
            "name": f"STC-{case_num:03d}",
            "category": "citation_noise",
            "submission": make_citation_noise(),
            "expected": "review"
        })
        case_num += 1
        
    # 5. 5 Micro-Submissions (2 review, 3 no_match)
    micro_specs = [
        ("micro_matched", "review", make_micro_matched),
        ("micro_matched", "review", make_micro_matched),
        ("micro_unrelated", "no_match", make_micro_unrelated),
        ("micro_unrelated", "no_match", make_micro_unrelated),
        ("micro_unrelated", "no_match", make_micro_unrelated),
    ]
    for cat, exp, fn in micro_specs:
        cases.append({
            "name": f"STC-{case_num:03d}",
            "category": cat,
            "submission": fn(),
            "expected": exp
        })
        case_num += 1
        
    # 6. 5 False Friends (expected: no_match)
    for _ in range(5):
        cases.append({
            "name": f"STC-{case_num:03d}",
            "category": "false_friends",
            "submission": make_false_friends(),
            "expected": "no_match"
        })
        case_num += 1
        
    return cases
