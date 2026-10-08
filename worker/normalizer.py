import re
import unicodedata

# Peta pembersihan karakter tak kasat mata (Zero-Width Space, Joiners, BOM, Soft Hyphen, dll.)
_INVISIBLE_CHARS = (
    "\u200b\u200c\u200d\u2060\ufeff\u00ad"
    "\u200e\u200f\u202a\u202b\u202c\u202d\u202e"
    "\u2061\u2062\u2063\u2064\u2066\u2067\u2068\u2069"
)
_INVISIBLE = dict.fromkeys(map(ord, _INVISIBLE_CHARS), None)

def normalize_text(text: str) -> str:
    """
    Normalisasi teks karya ilmiah:
    1. Unicode NFKC normalization
    2. Pembersihan karakter tak kasat mata (zero-width characters & soft hyphen)
    3. Singkirkan placeholder elemen non-teks (grafik, naskah kaligrafi, rumus)
    4. Tangani pemenggalan kata di akhir baris (hyphenation)
    5. Lowercase
    6. Ganti whitespace/newline berlebih menjadi satu spasi
    """
    if not text:
        return ""

    text = unicodedata.normalize("NFKC", text)
    # Singkirkan karakter tak kasat mata yang sering digunakan untuk memecah n-gram
    text = text.translate(_INVISIBLE)
    # Singkirkan penanda naskah kaligrafi, grafik, dan rumus matematika agar tidak memicu kecocokan kemiripan semu
    text = re.sub(r'\[[📜📊📐][^\]]+\]', ' ', text)
    # Satukan kata yang terpenggal di ujung baris (contoh: peneli-\n tian -> penelitian)
    text = re.sub(r'(\w+)-\s*\n\s*(\w+)', r'\1\2', text)
    text = text.lower()
    text = re.sub(r'\s+', ' ', text)
    return text.strip()

def tokenize(text: str) -> list[str]:
    """
    Tokenisasi teks menjadi list kata yang sadar Unicode:
    - Mendukung alfabet Latin, aksara loanword, Yunani/akademis, Arab, dan abjad non-ASCII lainnya
    - Mempertahankan tanda hubung kata ulang (contoh: buku-buku, masing-masing, COVID-19)
    - Mempertahankan tanda petik internal (contoh: Al-Qur'an, Jum'at)
    - Menghilangkan tanda baca di pinggir kata (.,;:!?"'()[]{}`*)
    """
    if not text:
        return []

    # Ekstraksi token kata Unicode yang mendukung tanda hubung dan apostrof internal
    words = re.findall(r"[^\W_]+(?:[-\'][^\W_]+)*", text.lower(), re.UNICODE)
    return words
