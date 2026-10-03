BASE_SOURCE = (
    "Penelitian ini bertujuan untuk mengetahui pengaruh teknologi informasi "
    "terhadap kinerja organisasi. Metode penelitian menggunakan pendekatan "
    "kuantitatif dengan pengumpulan data melalui kuesioner. Hasil penelitian "
    "menunjukkan adanya hubungan antara penggunaan teknologi dan peningkatan kinerja."
)

def make_identical() -> str:
    return BASE_SOURCE

def make_copy_edit() -> str:
    return (
        "Penelitian ini bertujuan untuk menganalisis dampak teknologi informasi "
        "terhadap kinerja organisasi. Metode penelitian menggunakan pendekatan "
        "kuantitatif dengan pengumpulan data melalui kuesioner. Hasil penelitian "
        "menunjukkan adanya hubungan antara penggunaan teknologi dan peningkatan kinerja."
    )

def make_paraphrase() -> str:
    return (
        "Studi ini dilakukan untuk menganalisis hubungan antara pemanfaatan teknologi "
        "informasi dan performa organisasi. Pendekatan kuantitatif digunakan dalam "
        "penelitian dengan instrumen berupa kuesioner. Temuan penelitian memperlihatkan "
        "adanya keterkaitan penggunaan teknologi dengan peningkatan performa organisasi."
    )

def make_common_phrase() -> str:
    return (
        "Penelitian ini bertujuan untuk mengetahui kondisi objek yang diamati. "
        "Berdasarkan hasil penelitian yang telah dilakukan, terdapat beberapa "
        "karakteristik yang perlu diperhatikan."
    )

def make_unrelated() -> str:
    return (
        "Pengelolaan tanaman hortikultura membutuhkan kondisi tanah yang sesuai. "
        "Pemupukan dilakukan berdasarkan kebutuhan tanaman dan kondisi lingkungan. "
        "Produktivitas tanaman dipengaruhi oleh ketersediaan air dan unsur hara."
    )

def generate_dataset() -> list[dict]:
    """
    Menghasilkan 50 test cases:
    - 10 Identical (expected: match)
    - 10 Copy-Edit (expected: review)
    - 10 Paraphrase (expected: review)
    - 10 Common Phrase (expected: review)
    - 10 Unrelated (expected: no_match)
    """
    cases = []
    generators = [
        ("identical", 10, "match", make_identical),
        ("copy_edit", 10, "review", make_copy_edit),
        ("paraphrase", 10, "review", make_paraphrase),
        ("common_phrase", 10, "review", make_common_phrase),
        ("unrelated", 10, "no_match", make_unrelated),
    ]
    
    case_number = 1
    for category, count, expected, generator in generators:
        for _ in range(count):
            cases.append({
                "name": f"TC-{case_number:03d}",
                "category": category,
                "submission": generator(),
                "expected": expected,
            })
            case_number += 1
            
    return cases
