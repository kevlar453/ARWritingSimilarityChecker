"""
AR Tilik v1.2 - Smart Section Parser
Modul segmentasi struktur naskah karya ilmiah (skripsi/tesis/disertasi/artikel ilmiah).

Membedah naskah ke dalam 4 zona fungsional:
1. FRONT_MATTER: Cover, Lembar Persetujuan/Pengesahan, Pernyataan Keaslian/Bebas Plagiasi,
                 Kata Pengantar, Abstrak, Daftar Isi/Tabel/Lampiran.
2. BODY_TEXT: Bab I (Pendahuluan), Bab II (Landasan Teori), Bab IV (Hasil & Pembahasan),
              Bab V (Kesimpulan & Saran).
3. METHODOLOGY: Bab III (Metodologi Penelitian) - dipisahkan karena konvensi formulaik.
4. BACK_MATTER: Daftar Pustaka (Bibliografi) dan Lampiran (Kuesioner/Surat/Transkrip).

Invarian:
- Zero mutation pada baseline engine v1.0.0 (worker/hybrid.py tetap frozen).
- Non-Suppression: Angka raw similarity tetap tersedia utuh; segmentasi menyediakan
  lensa granular (sub-scores) untuk menyingkap bias administratif.
"""

from dataclasses import dataclass, field
from enum import Enum
import re
from typing import Optional, List, Dict, Any


class SectionZone(str, Enum):
    """
    Zona makro fungsional dari karya ilmiah.
    """
    FRONT_MATTER = "FRONT_MATTER"      # Halaman administratif / formalitas depan
    BODY_TEXT = "BODY_TEXT"            # Substansi inti pemikiran (Bab I, II, IV, V)
    METHODOLOGY = "METHODOLOGY"        # Metodologi (Bab III)
    BACK_MATTER = "BACK_MATTER"        # Daftar Pustaka & Lampiran
    UNKNOWN = "UNKNOWN"

    @classmethod
    def from_str(cls, value: Optional[str]) -> "SectionZone":
        if not value:
            return cls.UNKNOWN
        try:
            return cls(str(value).strip().upper())
        except (ValueError, KeyError):
            return cls.UNKNOWN


class SectionType(str, Enum):
    """
    Klasifikasi mikroseksi akademik spesifik.
    """
    COVER = "COVER"
    PERSETUJUAN = "PERSETUJUAN"
    PENGESAHAN = "PENGESAHAN"
    PERNYATAAN_KEASLIAN = "PERNYATAAN_KEASLIAN"
    PERNYATAAN_BEBAS_PLAGIASI = "PERNYATAAN_BEBAS_PLAGIASI"
    ABSTRAK = "ABSTRAK"
    ABSTRACT = "ABSTRACT"
    KATA_PENGANTAR = "KATA_PENGANTAR"
    DAFTAR_ISI = "DAFTAR_ISI"
    DAFTAR_TABEL = "DAFTAR_TABEL"
    DAFTAR_LAMPIRAN = "DAFTAR_LAMPIRAN"
    DAFTAR_GAMBAR = "DAFTAR_GAMBAR"

    BAB_1_PENDAHULUAN = "BAB_1_PENDAHULUAN"
    BAB_2_LANDASAN_TEORI = "BAB_2_LANDASAN_TEORI"
    BAB_3_METODE_PENELITIAN = "BAB_3_METODE_PENELITIAN"
    BAB_4_HASIL_PEMBAHASAN = "BAB_4_HASIL_PEMBAHASAN"
    BAB_5_KESIMPULAN_SARAN = "BAB_5_KESIMPULAN_SARAN"
    BAB_UMUM = "BAB_UMUM"

    DAFTAR_PUSTAKA = "DAFTAR_PUSTAKA"
    LAMPIRAN = "LAMPIRAN"

    OTHER_FRONT = "OTHER_FRONT"
    OTHER_BODY = "OTHER_BODY"
    OTHER_BACK = "OTHER_BACK"
    UNKNOWN = "UNKNOWN"


@dataclass
class ParsedSection:
    """
    Representasi segmen struktur dokumen.
    """
    section_id: str
    section_zone: SectionZone
    section_type: SectionType
    title: str
    start_char: int
    end_char: int
    word_count: int
    character_count: int
    start_page: Optional[int] = None
    end_page: Optional[int] = None
    text: str = ""
    is_core_substance: bool = False
    is_administrative: bool = False

    def to_dict(self) -> Dict[str, Any]:
        return {
            "section_id": self.section_id,
            "section_zone": self.section_zone.value,
            "section_type": self.section_type.value,
            "title": self.title,
            "start_char": self.start_char,
            "end_char": self.end_char,
            "word_count": self.word_count,
            "character_count": self.character_count,
            "start_page": self.start_page,
            "end_page": self.end_page,
            "is_core_substance": self.is_core_substance,
            "is_administrative": self.is_administrative,
        }


@dataclass
class DocumentSegmentationResult:
    """
    Hasil dekomposisi struktur keseluruhan dokumen.
    """
    total_words: int
    total_characters: int
    total_sections: int
    sections: List[ParsedSection] = field(default_factory=list)
    zone_word_distribution: Dict[str, int] = field(default_factory=dict)
    zone_percentage_distribution: Dict[str, float] = field(default_factory=dict)
    core_substance_word_count: int = 0
    core_substance_percentage: float = 0.0
    administrative_word_count: int = 0
    administrative_percentage: float = 0.0
    low_confidence: bool = False
    confidence_note: Optional[str] = None

    def to_dict(self) -> Dict[str, Any]:
        return {
            "total_words": self.total_words,
            "total_characters": self.total_characters,
            "total_sections": self.total_sections,
            "zone_word_distribution": self.zone_word_distribution,
            "zone_percentage_distribution": self.zone_percentage_distribution,
            "core_substance_word_count": self.core_substance_word_count,
            "core_substance_percentage": round(self.core_substance_percentage, 2),
            "administrative_word_count": self.administrative_word_count,
            "administrative_percentage": round(self.administrative_percentage, 2),
            "low_confidence": self.low_confidence,
            "confidence_note": self.confidence_note,
            "sections": [s.to_dict() for s in self.sections],
        }


# =========================================================================
# Pattern Rules untuk Deteksi Heading Akademik Bahasa Indonesia
# =========================================================================

# Pola Bab Utama: menerima token apapun setelah BAB (mis. BAB I, BAB 1, BAB 1V, BAB IV, BAB lV, BAB |V)
BAB_LINE_PATTERN = re.compile(
    r"(?m)^[^\S\r\n]*(BAB\s+[A-Za-z0-9|]{1,5})\b(?:[^\S\r\n]*[:.\-–—]?[^\S\r\n]*(.*))?$",
    re.IGNORECASE
)

_ROMAN_MAP = {
    "I": 1, "II": 2, "III": 3, "IV": 4, "V": 5,
    "VI": 6, "VII": 7, "VIII": 8, "IX": 9, "X": 10
}

def _bab_number(label: str) -> Optional[int]:
    token = label.strip().split()[-1].upper()
    if token.isdigit():
        return int(token)
    # Toleransi typo umum penulisan naskah: angka 1, huruf L, atau garis pipa | sebagai I
    token = token.replace("1", "I").replace("L", "I").replace("|", "I")
    return _ROMAN_MAP.get(token)

KNOWN_BAB_SUBTITLES = {
    "PENDAHULUAN", "LANDASAN TEORI", "KAJIAN TEORI", "KAJIAN PUSTAKA", "TINJAUAN PUSTAKA",
    "KERANGKA TEORITIS", "METODE PENELITIAN", "METODOLOGI PENELITIAN",
    "METODE DAN DESAIN PENELITIAN", "HASIL DAN PEMBAHASAN", "HASIL PENELITIAN",
    "PEMBAHASAN", "KESIMPULAN DAN SARAN", "KESIMPULAN", "PENUTUP"
}


def _detect_subsequent_subtitle(full_text: str, m_end: int) -> str:
    """
    Jika baris BAB tidak memiliki subtitle pada baris yang sama,
    periksa apakah baris berikutnya adalah subtitle bab yang valid (<= 7 kata).
    """
    remaining = full_text[m_end:]
    lines = remaining.split("\n", 3)
    for line in lines[:2]:
        clean = line.strip()
        if not clean:
            continue
        words = clean.split()
        if len(words) <= 7:
            upper_clean = clean.upper()
            if any(sub in upper_clean for sub in KNOWN_BAB_SUBTITLES) or clean.isupper():
                return clean
        break
    return ""

# Pola Spesifik Front Matter
FRONT_MATTER_PATTERNS = [
    (re.compile(r"(?im)^\s*(?:HALAMAN\s+)?(?:JUDUL|COVER)\s*$"), SectionType.COVER),
    (re.compile(r"(?im)^\s*(?:LEMBAR\s+)?PERSETUJUAN(?:\s+PEMBIMBING)?\s*$"), SectionType.PERSETUJUAN),
    (re.compile(r"(?im)^\s*(?:LEMBAR\s+)?PENGESAHAN(?:\s+PENGUJI)?\s*$"), SectionType.PENGESAHAN),
    (re.compile(r"(?im)^\s*(?:HALAMAN\s+)?PERNYATAAN\s+KEASLIAN(?:\s+TULISAN)?\s*$"), SectionType.PERNYATAAN_KEASLIAN),
    (re.compile(r"(?im)^\s*(?:HALAMAN\s+)?PERNYATAAN\s+BEBAS\s+PLAGIASI?\s*$"), SectionType.PERNYATAAN_BEBAS_PLAGIASI),
    (re.compile(r"(?im)^\s*ABSTRAK\s*$"), SectionType.ABSTRAK),
    (re.compile(r"(?im)^\s*ABSTRACT\s*$"), SectionType.ABSTRACT),
    (re.compile(r"(?im)^\s*(?:KATA\s+PENGANTAR|PRAKATA)\s*$"), SectionType.KATA_PENGANTAR),
    (re.compile(r"(?im)^\s*DAFTAR\s+ISI\s*$"), SectionType.DAFTAR_ISI),
    (re.compile(r"(?im)^\s*DAFTAR\s+TABEL\s*$"), SectionType.DAFTAR_TABEL),
    (re.compile(r"(?im)^\s*DAFTAR\s+LAMPIRAN\s*$"), SectionType.DAFTAR_LAMPIRAN),
    (re.compile(r"(?im)^\s*DAFTAR\s+(?:GAMBAR|BAGAN|GRAFIK)\s*$"), SectionType.DAFTAR_GAMBAR),
]

# Pola Spesifik Back Matter
BACK_MATTER_PATTERNS = [
    (re.compile(r"(?im)^\s*(?:DAFTAR\s+PUSTAKA|BIBLIOGRAFI|REFERENSI|DAFTAR\s+RUJUKAN)\s*$"), SectionType.DAFTAR_PUSTAKA),
    (re.compile(r"(?im)^\s*(?:LAMPIRAN(?:\s+[A-Z0-9\.\-]+)?)\s*$"), SectionType.LAMPIRAN),
]


def _classify_bab_title(bab_label: str, subtitle: str) -> tuple[Optional[SectionZone], Optional[SectionType]]:
    """
    Menentukan Zona dan Tipe Seksi dari nomor BAB hasil normalisasi dan judulnya.
    Mengembalikan (None, None) bila bukan nomor bab valid agar kandidat semu dilewati.
    """
    num = _bab_number(bab_label)
    sub = subtitle.strip().upper() if subtitle else ""

    # Jika judul menyebut METODE/METODOLOGI, prioritaskan METHODOLOGY
    if "METODE" in sub or "METODOLOGI" in sub:
        return SectionZone.METHODOLOGY, SectionType.BAB_3_METODE_PENELITIAN

    if num == 1:
        return SectionZone.BODY_TEXT, SectionType.BAB_1_PENDAHULUAN
    elif num == 2:
        return SectionZone.BODY_TEXT, SectionType.BAB_2_LANDASAN_TEORI
    elif num == 3:
        return SectionZone.METHODOLOGY, SectionType.BAB_3_METODE_PENELITIAN
    elif num == 4:
        return SectionZone.BODY_TEXT, SectionType.BAB_4_HASIL_PEMBAHASAN
    elif num == 5:
        return SectionZone.BODY_TEXT, SectionType.BAB_5_KESIMPULAN_SARAN
    elif num is not None and num > 5:
        return SectionZone.BODY_TEXT, SectionType.BAB_UMUM
    elif num is None:
        if any(kw in sub for kw in ["HASIL", "PEMBAHASAN"]):
            return SectionZone.BODY_TEXT, SectionType.BAB_4_HASIL_PEMBAHASAN
        elif any(kw in sub for kw in ["KESIMPULAN", "PENUTUP"]):
            return SectionZone.BODY_TEXT, SectionType.BAB_5_KESIMPULAN_SARAN
        elif any(kw in sub for kw in ["PENDAHULUAN", "LATAR BELAKANG"]):
            return SectionZone.BODY_TEXT, SectionType.BAB_1_PENDAHULUAN
        elif any(kw in sub for kw in ["TEORI", "PUSTAKA"]):
            return SectionZone.BODY_TEXT, SectionType.BAB_2_LANDASAN_TEORI
        return None, None
    return None, None


def _count_words(text: str) -> int:
    tokens = text.split()
    return len(tokens)


def parse_document_sections(
    text: str,
    pages: Optional[List[Dict[str, Any]]] = None
) -> DocumentSegmentationResult:
    """
    Membedah teks dokumen utuh ke dalam seksi-seksi struktural akademik.
    Mendukung input teks murni atau kumpulan halaman terstruktur dari worker/extractor.py.
    """
    if not text or not text.strip():
        return DocumentSegmentationResult(
            total_words=0,
            total_characters=0,
            total_sections=0,
            sections=[],
            zone_word_distribution={},
            zone_percentage_distribution={},
            core_substance_word_count=0,
            core_substance_percentage=0.0,
            administrative_word_count=0,
            administrative_percentage=0.0
        )

    # 1. Identifikasi Titik-Titik Heading
    # Kumpulkan semua kandidat pembatas: (start_char, title, SectionZone, SectionType)
    heading_candidates: List[Dict[str, Any]] = []

    # A. Cek Pola BAB
    for m in BAB_LINE_PATTERN.finditer(text):
        bab_label = m.group(1).strip()
        raw_sub = m.group(2).strip() if m.group(2) else ""
        if not raw_sub:
            raw_sub = _detect_subsequent_subtitle(text, m.end())
        zone, stype = _classify_bab_title(bab_label, raw_sub)
        if zone is None or stype is None:
            continue
        full_title = f"{bab_label} {raw_sub}".strip()
        heading_candidates.append({
            "pos": m.start(),
            "title": full_title,
            "zone": zone,
            "type": stype
        })

    # Fallback Bab IV: jika Bab IV belum tertangkap, periksa baris standalone 'HASIL DAN PEMBAHASAN' / 'HASIL PENELITIAN'
    has_bab4 = any(c["type"] == SectionType.BAB_4_HASIL_PEMBAHASAN for c in heading_candidates)
    if not has_bab4:
        for m in re.finditer(r"(?im)^[^\S\r\n]*(?:HASIL\s+DAN\s+PEMBAHASAN|HASIL\s+PENELITIAN(?:\s+DAN\s+PEMBAHASAN)?)\s*$", text):
            heading_candidates.append({
                "pos": m.start(),
                "title": m.group(0).strip(),
                "zone": SectionZone.BODY_TEXT,
                "type": SectionType.BAB_4_HASIL_PEMBAHASAN
            })

    # B. Cek Pola Front Matter
    for pattern, stype in FRONT_MATTER_PATTERNS:
        for m in pattern.finditer(text):
            title = m.group(0).strip()
            heading_candidates.append({
                "pos": m.start(),
                "title": title,
                "zone": SectionZone.FRONT_MATTER,
                "type": stype
            })

    # C. Cek Pola Back Matter
    for pattern, stype in BACK_MATTER_PATTERNS:
        for m in pattern.finditer(text):
            title = m.group(0).strip()
            heading_candidates.append({
                "pos": m.start(),
                "title": title,
                "zone": SectionZone.BACK_MATTER,
                "type": stype
            })

    # Urutkan pembatas berdasarkan posisi kemunculan dalam teks
    heading_candidates.sort(key=lambda x: x["pos"])

    # Filter seksi semu dari Daftar Isi:
    # Identifikasi titik dimulainya Bab I yang sesungguhnya (berisi teks substantif >= 100 kata)
    bab1_candidates = [h for h in heading_candidates if h["type"] == SectionType.BAB_1_PENDAHULUAN]
    real_bab1_pos = None
    for b1 in bab1_candidates:
        next_pos = len(text)
        for h in heading_candidates:
            if h["pos"] > b1["pos"]:
                next_pos = h["pos"]
                break
        if _count_words(text[b1["pos"]:next_pos]) >= 100:
            real_bab1_pos = b1["pos"]
            break

    # Setiap kandidat BAB yang posisinya berada sebelum real_bab1_pos adalah entri daftar isi
    filtered_cand = []
    for h in heading_candidates:
        if real_bab1_pos and h["pos"] < real_bab1_pos and ("BAB" in h["type"].value or h["zone"] in (SectionZone.BODY_TEXT, SectionZone.METHODOLOGY)):
            continue
        filtered_cand.append(h)
    heading_candidates = filtered_cand

    # 2. Eliminasi Duplikasi Posisi / Overlap Ketat
    filtered_headings: List[Dict[str, Any]] = []
    last_pos = -1
    for h in heading_candidates:
        if h["pos"] > last_pos:
            filtered_headings.append(h)
            last_pos = h["pos"]

    # 3. Potong Teks Menjadi ParsedSection
    sections: List[ParsedSection] = []

    # Jika ada teks sebelum heading pertama (misalnya Halaman Judul/Cover tanpa kata 'COVER')
    if filtered_headings and filtered_headings[0]["pos"] > 0:
        pre_text = text[:filtered_headings[0]["pos"]].strip()
        if pre_text:
            p_words = _count_words(pre_text)
            sections.append(ParsedSection(
                section_id="SECTION_0_PRE_FRONT",
                section_zone=SectionZone.FRONT_MATTER,
                section_type=SectionType.COVER,
                title="HALAMAN SAMPUL / JUDUL",
                start_char=0,
                end_char=filtered_headings[0]["pos"],
                word_count=p_words,
                character_count=len(pre_text),
                text=pre_text,
                is_core_substance=False,
                is_administrative=True
            ))

    # Proses setiap heading
    for idx, h in enumerate(filtered_headings):
        start_char = h["pos"]
        end_char = filtered_headings[idx + 1]["pos"] if (idx + 1 < len(filtered_headings)) else len(text)
        sec_text = text[start_char:end_char].strip()
        w_count = _count_words(sec_text)
        c_count = len(sec_text)

        zone = h["zone"]
        stype = h["type"]

        is_core = (zone == SectionZone.BODY_TEXT)
        is_admin = (zone in (SectionZone.FRONT_MATTER, SectionZone.BACK_MATTER))

        sections.append(ParsedSection(
            section_id=f"SECTION_{len(sections)+1}_{stype.value}",
            section_zone=zone,
            section_type=stype,
            title=h["title"],
            start_char=start_char,
            end_char=end_char,
            word_count=w_count,
            character_count=c_count,
            text=sec_text,
            is_core_substance=is_core,
            is_administrative=is_admin
        ))

    # Jika tidak ditemukan heading sama sekali, jadikan satu dokumen umum
    if not sections:
        total_w = _count_words(text)
        sections.append(ParsedSection(
            section_id="SECTION_1_BODY",
            section_zone=SectionZone.BODY_TEXT,
            section_type=SectionType.OTHER_BODY,
            title="DOKUMEN UTUH",
            start_char=0,
            end_char=len(text),
            word_count=total_w,
            character_count=len(text),
            text=text,
            is_core_substance=True,
            is_administrative=False
        ))

    # 4. Hitung Distribusi Metrik Zona
    total_words = sum(s.word_count for s in sections)
    total_chars = sum(s.character_count for s in sections)

    zone_word_dist: Dict[str, int] = {
        SectionZone.FRONT_MATTER.value: 0,
        SectionZone.BODY_TEXT.value: 0,
        SectionZone.METHODOLOGY.value: 0,
        SectionZone.BACK_MATTER.value: 0,
        SectionZone.UNKNOWN.value: 0,
    }

    core_substance_words = 0
    administrative_words = 0

    for s in sections:
        zone_key = s.section_zone.value
        zone_word_dist[zone_key] = zone_word_dist.get(zone_key, 0) + s.word_count

        if s.is_core_substance:
            core_substance_words += s.word_count
        if s.is_administrative:
            administrative_words += s.word_count

    # Persentase per zona
    zone_pct_dist: Dict[str, float] = {}
    if total_words > 0:
        for k, v in zone_word_dist.items():
            zone_pct_dist[k] = round((v / total_words) * 100.0, 2)
        core_pct = (core_substance_words / total_words) * 100.0
        admin_pct = (administrative_words / total_words) * 100.0
    else:
        for k in zone_word_dist.keys():
            zone_pct_dist[k] = 0.0
        core_pct = 0.0
        admin_pct = 0.0

    # Sanity check kewajaran distribusi zona naskah
    meth_pct = zone_pct_dist.get(SectionZone.METHODOLOGY.value, 0.0)
    low_confidence = False
    confidence_note = None
    if meth_pct > 25.0:
        low_confidence = True
        confidence_note = f"Proporsi Metodologi terdeteksi tidak lazim ({meth_pct:.1f}% > 25%), kemungkinan ada bab hasil/pembahasan yang tidak terdeteksi."

    return DocumentSegmentationResult(
        total_words=total_words,
        total_characters=total_chars,
        total_sections=len(sections),
        sections=sections,
        zone_word_distribution=zone_word_dist,
        zone_percentage_distribution=zone_pct_dist,
        core_substance_word_count=core_substance_words,
        core_substance_percentage=round(core_pct, 2),
        administrative_word_count=administrative_words,
        administrative_percentage=round(admin_pct, 2),
        low_confidence=low_confidence,
        confidence_note=confidence_note
    )


# =========================================================================
# Utilitas Ekstraksi & Filter Berbasis Zona
# =========================================================================

def filter_text_by_zones(
    segmentation: DocumentSegmentationResult,
    allowed_zones: List[SectionZone]
) -> str:
    """
    Menggabungkan teks hanya dari zona-zona yang diizinkan.
    Contoh: mengecualikan FRONT_MATTER dan BACK_MATTER.
    """
    allowed_zone_values = {z.value for z in allowed_zones}
    matched_texts = [
        s.text for s in segmentation.sections
        if s.section_zone.value in allowed_zone_values and s.text
    ]
    return "\n\n".join(matched_texts)


def get_core_substance_text(segmentation: DocumentSegmentationResult) -> str:
    """
    Mengambil hanya teks substansi utama (Bab I, Bab II, Bab IV, Bab V).
    Menghilangkan halaman administratif, bab metode standar, dan lampiran.
    """
    return filter_text_by_zones(segmentation, [SectionZone.BODY_TEXT])


def get_substantive_including_methodology(segmentation: DocumentSegmentationResult) -> str:
    """
    Mengambil teks ilmiah inti termasuk bab metodologi (Bab I s.d. Bab V),
    mengabaikan halaman formalitas depan dan lampiran belakang.
    """
    return filter_text_by_zones(segmentation, [SectionZone.BODY_TEXT, SectionZone.METHODOLOGY])
