import re
import unicodedata

def normalize_text(text: str) -> str:
    """
    Normalisasi teks karya ilmiah:
    1. Unicode NFKC normalization
    2. Tangani pemenggalan kata di akhir baris (hyphenation)
    3. Lowercase
    4. Ganti whitespace/newline berlebih menjadi satu spasi
    """
    if not text:
        return ""
    
    text = unicodedata.normalize("NFKC", text)
    # Singkirkan penanda naskah kaligrafi agar tidak memicu kecocokan kemiripan semu
    text = re.sub(r'\[📜[^\]]+\]', ' ', text)
    # Satukan kata yang terpenggal di ujung baris (contoh: peneli-\n tian -> penelitian)
    text = re.sub(r'(\w+)-\s*\n\s*(\w+)', r'\1\2', text)
    text = text.lower()
    text = re.sub(r'\s+', ' ', text)
    return text.strip()

def tokenize(text: str) -> list[str]:
    """
    Tokenisasi teks menjadi list kata:
    - Menghilangkan tanda baca di pinggir kata (.,;:!?"'()[]{}`*)
    - Mempertahankan tanda hubung kata ulang (contoh: buku-buku, masing-masing)
    """
    if not text:
        return []
    
    # Cari urutan karakter alfanumerik (bisa memiliki tanda hubung di tengah untuk kata ulang)
    words = re.findall(r'\b[a-zA-Z0-9]+(?:-[a-zA-Z0-9]+)*\b', text.lower())
    return words
