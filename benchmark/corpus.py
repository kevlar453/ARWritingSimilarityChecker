from benchmark.dataset import BASE_SOURCE

COMMON_PHRASES = {
    "very_common": "penelitian ini bertujuan untuk mengetahui",
    "common": "berdasarkan hasil penelitian yang telah dilakukan",
    "less_common": "data diperoleh melalui proses pengumpulan data",
    "rare": "hasil penelitian menunjukkan bahwa terdapat"
}

def generate_corpus() -> list[dict]:
    """
    Menghasilkan 20 dokumen korpus dengan distribusi frekuensi frasa:
    - Dokumen 1 (Benchmark Source 01) memuat BASE_SOURCE (untuk uji Identical & Copy-Edit)
    - very_common: 20/20 (100%)
    - common: 10/20 (50%)
    - less_common: 4/20 (20%)
    - rare: 1/20 (5%)
    """
    documents = []
    for index in range(20):
        phrases = []
        # 20/20
        phrases.append(COMMON_PHRASES["very_common"])
        
        # 10/20
        if index < 10:
            phrases.append(COMMON_PHRASES["common"])
            
        # 4/20
        if index < 4:
            phrases.append(COMMON_PHRASES["less_common"])
            
        # 1/20
        if index == 0:
            phrases.append(COMMON_PHRASES["rare"])
            
        # Dokumen 01 menjadi sumber referensi utama untuk kasus Identical & Copy-Edit
        if index == 0:
            body = (
                BASE_SOURCE
                + " "
                + " ".join(phrases)
                + ". Dokumen sumber 1 membahas objek penelitian dengan karakteristik yang berbeda."
            )
        else:
            body = (
                " ".join(phrases)
                + ". "
                + f"Dokumen sumber {index + 1} membahas objek penelitian "
                + "dengan karakteristik yang berbeda. "
                + "Metode penelitian disesuaikan dengan kebutuhan penelitian."
            )
        
        documents.append({
            "name": f"Benchmark Source {index + 1:02d}",
            "text": body
        })
        
    return documents
