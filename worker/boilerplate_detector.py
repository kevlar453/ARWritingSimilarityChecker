"""
AR Tilik v1.2 - Academic Boilerplate & Methodology Phrase Detector
Mendeteksi formulasi metodologi baku, prosedur analisis statistik, transisi akademik umum,
dan formula administratif pada pasase kesamaan teks secara deterministik.

Tujuan:
Membantu dewan penelaah membedakan kesamaan teks yang timbul akibat konvensi bahasa
ilmiah standar (Academic Formulaic Conventions) dari penyalinan ide substantif.

Invarian:
- Zero mutation pada baseline engine v1.0.0 (worker/hybrid.py tetap frozen).
- Non-Suppression: Deteksi boilerplate tidak mengurangi angka kesamaan teks mentah,
  melainkan menyajikan taksonomi objektif pada Lapisan Konteks (Layer B).
"""

from dataclasses import dataclass, field
from enum import Enum
import re
from typing import Optional, List, Dict, Any


class BoilerplateCategory(str, Enum):
    """
    Kategori formulasi ilmiah standar.
    """
    ACADEMIC_TRANSITION = "ACADEMIC_TRANSITION"          # Frasa transisi/pengantar argumen baku
    METHODOLOGY_PROCEDURE = "METHODOLOGY_PROCEDURE"      # Formulasi baku metode & sampling
    STATISTICAL_ANALYSIS = "STATISTICAL_ANALYSIS"        # Prosedur uji statistik terstandarisasi (SPSS, Likert, dll)
    ADMINISTRATIVE_FORMULA = "ADMINISTRATIVE_FORMULA"    # Kalimat template pengesahan/keaslian/kata pengantar
    LEGAL_CITATION = "LEGAL_CITATION"                    # Rujukan regulasi perundang-undangan dan instrumen hukum baku
    BIBLIOGRAPHIC_ENTRY = "BIBLIOGRAPHIC_ENTRY"          # Entri daftar pustaka / rujukan literatur
    TABLE_OF_CONTENTS = "TABLE_OF_CONTENTS"              # Baris judul dan nomor halaman daftar isi
    NONE = "NONE"
    UNKNOWN = "UNKNOWN"

    @classmethod
    def from_str(cls, value: Optional[str]) -> "BoilerplateCategory":
        if not value:
            return cls.NONE
        try:
            return cls(str(value).strip().upper())
        except (ValueError, KeyError):
            return cls.UNKNOWN


@dataclass
class BoilerplateDetectionResult:
    """
    Hasil klasifikasi formulasi ilmiah baku pada pasase teks.
    """
    is_boilerplate: bool
    category: BoilerplateCategory
    matched_phrase: Optional[str]
    start_pos: Optional[int]
    end_pos: Optional[int]
    rationale: str
    coverage_ratio: float = 0.0

    def to_dict(self) -> Dict[str, Any]:
        return {
            "is_boilerplate": self.is_boilerplate,
            "category": self.category.value,
            "matched_phrase": self.matched_phrase,
            "start_pos": self.start_pos,
            "end_pos": self.end_pos,
            "rationale": self.rationale,
            "coverage_ratio": round(self.coverage_ratio, 2),
        }


# =========================================================================
# Katalog Pola Deterministik Formulasi Ilmiah Baku (Bahasa Indonesia)
# =========================================================================

BOILERPLATE_RULES = [
    # 1. Prosedur Uji Statistik & Psikometri
    (
        re.compile(r"(?i)\b(?:uji|analisis)\s+validitas\s+(?:menggunakan|berdasarkan)\s+(?:nilai\s+)?corrected\s+item[- ]total\s+correlation\b"),
        BoilerplateCategory.STATISTICAL_ANALYSIS,
        "Formulasi baku prosedur pengujian validitas instrumen statistik (Corrected Item-Total Correlation)."
    ),
    (
        re.compile(r"(?i)\b(?:uji|analisis)\s+reliabilitas\s+(?:menggunakan|berdasarkan)\s+(?:koefisien\s+)?cronbach['’]?s?\s+alpha\b"),
        BoilerplateCategory.STATISTICAL_ANALYSIS,
        "Formulasi baku prosedur pengujian reliabilitas instrumen (Cronbach's Alpha)."
    ),
    (
        re.compile(r"(?i)\b(?:uji|analisis)\s+normalitas\s+(?:menggunakan|berdasarkan)\s+(?:shapiro[- ]wilk|kolmogorov[- ]smirnov)(?:\s+test)?\b"),
        BoilerplateCategory.STATISTICAL_ANALYSIS,
        "Formulasi baku prosedur pengujian asumsi sebaran normal data parametrik."
    ),
    (
        re.compile(r"(?i)\b(?:korelasi\s+pearson|regresi\s+linear\s+(?:sederhana|berganda|bertahap))\s+(?:digunakan\s+untuk|dilakukan\s+untuk)\b"),
        BoilerplateCategory.STATISTICAL_ANALYSIS,
        "Formulasi konvensi penggunaan alat uji inferensial statistik."
    ),
    (
        re.compile(r"(?i)\bdengan\s+bantuan\s+(?:program|software)\s+(?:ibm\s+)?spss(?:\s+(?:statistic|statistics)?\s*\d*)?\b"),
        BoilerplateCategory.STATISTICAL_ANALYSIS,
        "Keterangan standar penggunaan perangkat lunak statistik (SPSS)."
    ),
    (
        re.compile(r"(?i)\b(?:skala\s+likert\s+(?:lima|5|empat|4)\s+(?:tingkat|poin|kategori))\b"),
        BoilerplateCategory.STATISTICAL_ANALYSIS,
        "Formulasi deskripsi instrumen pengukuran psikometri skala Likert."
    ),
    (
        re.compile(r"(?i)\b(?:analisis\s+mediasi|mediator\s+penuh|mediasi\s+parsial)\b.*\b(?:baron\s+dan\s+kenny|hayes)\b"),
        BoilerplateCategory.STATISTICAL_ANALYSIS,
        "Formulasi rujukan standar metodologi analisis variabel mediasi statistik."
    ),

    # 2. Metodologi Penelitian & Teknik Sampling
    (
        re.compile(r"(?i)\b(?:pendekatan|desain)\s+mixed\s+methods\s+(?:dengan\s+desain\s+)?sequential\s+explanatory\b"),
        BoilerplateCategory.METHODOLOGY_PROCEDURE,
        "Formulasi nomenklatur baku metodologi penelitian campuran (Sequential Explanatory)."
    ),
    (
        re.compile(r"(?i)\b(?:teknik|metode)\s+(?:pengambilan\s+sampel|sampling)\s+(?:dilakukan\s+secara|menggunakan)\s+(?:purposive|total|random)\s+sampling\b"),
        BoilerplateCategory.METHODOLOGY_PROCEDURE,
        "Formulasi terstandarisasi teknik penentuan subjek dan penarikan sampel penelitian."
    ),
    (
        re.compile(r"(?i)\b(?:pengumpulan\s+data\s+dilakukan\s+melalui\s+wawancara\s+mendalam|pedoman\s+wawancara\s+semi\s+terstruktur)\b"),
        BoilerplateCategory.METHODOLOGY_PROCEDURE,
        "Formulasi standar protokol pengumpulan data kualitatif."
    ),
    (
        re.compile(r"(?i)\b(?:keabsahan\s+data\s+(?:dalam\s+penelitian\s+ini\s+)?dijaga\s+melalui\s+teknik\s+triangulasi|triangulasi\s+sumber\s+dan\s+member\s+check)\b"),
        BoilerplateCategory.METHODOLOGY_PROCEDURE,
        "Formulasi baku konvensi keabsahan dan uji kredibilitas data kualitatif."
    ),

    # 3. Frasa Transisi & Penghubung Akademik
    (
        re.compile(r"(?i)\bberdasarkan\s+hasil\s+penelitian(?:\s+yang\s+telah(?:\s+dilakukan)?)?\b"),
        BoilerplateCategory.ACADEMIC_TRANSITION,
        "Frasa penghubung baku untuk penyajian kesimpulan dan interpretasi temuan ilmiah."
    ),
    (
        re.compile(r"(?i)\bberdasarkan\s+(?:uraian\s+)?latar\s+belakang\s+(?:di\s+atas|tersebut)\b"),
        BoilerplateCategory.ACADEMIC_TRANSITION,
        "Frasa transisi baku antara latar belakang menuju perumusan masalah."
    ),
    (
        re.compile(r"(?i)\bpenelitian\s+ini\s+bertujuan\s+(?:untuk\s+)?(?:menganalisis|mengetahui|menguji|mendeskripsikan|memperoleh|menjelaskan)?\b"),
        BoilerplateCategory.ACADEMIC_TRANSITION,
        "Formulasi standar pernyataan tujuan penelitian ilmiah."
    ),
    (
        re.compile(r"(?i)\bhasil\s+penelitian\s+menunjukkan\s+bahwa\b"),
        BoilerplateCategory.ACADEMIC_TRANSITION,
        "Frasa pengantar standar interpretasi hasil dan pengujian hipotesis."
    ),
    (
        re.compile(r"(?i)\bdapat\s+disimpulkan\s+bahwa\b"),
        BoilerplateCategory.ACADEMIC_TRANSITION,
        "Frasa sintesis baku penutup bab pembahasan dan kesimpulan."
    ),
    (
        re.compile(r"(?i)\bvariabel\s+(?:yang\s+)?(?:lain\s+)?akan\s+(?:meningkat|menurun|berubah)\b"),
        BoilerplateCategory.STATISTICAL_ANALYSIS,
        "Formulasi deskripsi arah hubungan atau kausalitas antar variabel statistik."
    ),

    # 4. Formulasi Administratif Dokumen
    (
        re.compile(r"(?i)\bmenyatakan\s+(?:dengan\s+sesungguhnya\s+)?bahwa\s+(?:tesis|skripsi|disertasi|makalah)\s+ini\s+(?:benar-benar\s+)?merupakan\s+hasil\s+karya\s+(?:saya\s+)?sendiri\b"),
        BoilerplateCategory.ADMINISTRATIVE_FORMULA,
        "Template klausul hukum standar pernyataan keaslian karya ilmiah perguruan tinggi."
    ),
    (
        re.compile(r"(?i)\bbukan\s+(?:mengambil\s+alih|merupakan\s+hasil\s+plagiasi)\s+(?:tulisan|karya)\s+orang\s+lain\b"),
        BoilerplateCategory.ADMINISTRATIVE_FORMULA,
        "Kalimat baku surat pernyataan bebas plagiasi perguruan tinggi."
    ),
    (
        re.compile(r"(?i)\bapabila\s+di\s+kemudian\s+hari\s+terbukti\s+bahwa.*bersedia\s+menerima.*sanksi\s+akademik\b"),
        BoilerplateCategory.ADMINISTRATIVE_FORMULA,
        "Klausul sanksi administratif standar pada lembar formalitas perguruan tinggi."
    ),
    (
        re.compile(r"(?i)\bpuji\s+(?:dan\s+)?syukur\s+(?:penulis\s+panjatkan\s+)?(?:ke\s*hadirat|kepada)\s+(?:tuhan|allah)(?:\s+yang\s+(?:maha\s+esa|maha\s+kuasa|maha\s+pengasih))?\b"),
        BoilerplateCategory.ADMINISTRATIVE_FORMULA,
        "Formula pembuka baku doa/kata pengantar karya ilmiah di Indonesia."
    ),
    (
        re.compile(r"(?i)\b(?:syukur\s+penulis\s+panjatkan\s+kehadirat\s+tuhan|puji\s+syukur\s+kehadirat)\b"),
        BoilerplateCategory.ADMINISTRATIVE_FORMULA,
        "Formula pembuka baku kata pengantar karya ilmiah."
    ),
    (
        re.compile(r"(?i)\b(?:pihak(?:-pihak)?\s+yang\s+telah\s+membantu\s+penulis|penulis\s+mengucapkan\s+terima\s+kasih\s+kepada)\b"),
        BoilerplateCategory.ADMINISTRATIVE_FORMULA,
        "Formulasi baku ucapan apresiasi/terima kasih pada kata pengantar."
    ),
    (
        re.compile(r"(?i)\b(?:saran\s+yang\s+dapat\s+diberikan\s+(?:adalah\s+)?sebagai\s+berikut|adapun\s+saran\s+yang\s+dapat\s+diberikan)\b"),
        BoilerplateCategory.ACADEMIC_TRANSITION,
        "Formulasi baku pengantar saran penutup karya ilmiah."
    ),
    (
        re.compile(r"(?i)\bsebagai\s+salah\s+satu\s+syarat\s+untuk\s+memperoleh\s+gelar\b"),
        BoilerplateCategory.ADMINISTRATIVE_FORMULA,
        "Keterangan administratif formalitas pengajuan ujian kelulusan program studi."
    ),
    (
        re.compile(r"(?i)\b(?:semester\s+(?:ganjil|genap)|tahun\s+akademik|tahun\s+ajaran)\s+\d{4}(?:[/-]\d{4})?\b"),
        BoilerplateCategory.ADMINISTRATIVE_FORMULA,
        "Formulasi administratif baku penetapan periode akademik semester dan tahun ajaran."
    ),
    (
        re.compile(r"(?i)\b(?:pengangkatan|penunjukan|susunan)\s+dosen\s+penguji(?:\s+(?:[ivx\d]+|utama|ketua|anggota))?\s+(?:skripsi|tesis|disertasi)\b"),
        BoilerplateCategory.ADMINISTRATIVE_FORMULA,
        "Formulasi administratif penugasan sivitas akademika dalam pengujian tugas akhir."
    ),
    (
        re.compile(r"(?i)\b(?:keputusan\s+dekan|keputusan\s+rektor)\b"),
        BoilerplateCategory.ADMINISTRATIVE_FORMULA,
        "Nomenklatur baku produk surat keputusan kepemimpinan perguruan tinggi."
    ),

    # 5. Rujukan Regulasi Perundang-Undangan & Produk Hukum Baku
    (
        re.compile(r"(?i)\b(?:undang-undang|peraturan\s+pemerintah|peraturan\s+menteri|surat\s+keputusan|keputusan\s+menteri)\s+nomor\b"),
        BoilerplateCategory.LEGAL_CITATION,
        "Rujukan formal peraturan perundang-undangan atau produk hukum akademik baku."
    ),

    # 6. Entri Bibliografi / Daftar Pustaka Formal
    (
        re.compile(r"(?i)\b[A-Z][a-z]+,\s+[A-Z]\.\s*(?:[A-Z]\.)?\s*\(\d{4}\)"),
        BoilerplateCategory.BIBLIOGRAPHIC_ENTRY,
        "Format standar entri referensi/daftar pustaka akademik."
    ),

    # 7. Format Indeks / Penomoran Daftar Isi
    (
        re.compile(r"(?i)\b(?:halaman\s+judul|lembar\s+persetujuan|daftar\s+isi|bab\s+[ivx\d]+)\b.*(?:\.{2,}|…|\b(?:i{1,3}|iv|v|\d+)\s*$)"),
        BoilerplateCategory.TABLE_OF_CONTENTS,
        "Format penomoran halaman/tata letak indeks daftar isi."
    ),
]


def detect_academic_boilerplate(text: str, zone: Optional[str] = None) -> BoilerplateDetectionResult:
    """
    Memeriksa apakah potongan pasase teks mengandung formulasi ilmiah baku.
    Mendukung penentuan berbasis zona naskah (FRONT_MATTER, BACK_MATTER, METHODOLOGY, BODY_TEXT).
    Mengembalikan hasil kategorisasi deterministik dan penjelasan objektif.
    """
    if not text or not text.strip():
        return BoilerplateDetectionResult(
            is_boilerplate=False,
            category=BoilerplateCategory.NONE,
            matched_phrase=None,
            start_pos=None,
            end_pos=None,
            rationale="Teks kosong atau tidak mengandung pasase.",
            coverage_ratio=0.0
        )

    words = text.split()
    total_words = len(words)

    # 1. Zona BACK_MATTER otomatis adalah daftar pustaka / lampiran
    if zone == "BACK_MATTER":
        return BoilerplateDetectionResult(
            is_boilerplate=True,
            category=BoilerplateCategory.BIBLIOGRAPHIC_ENTRY,
            matched_phrase=text[:80] + ("..." if len(text) > 80 else ""),
            start_pos=0,
            end_pos=len(text),
            rationale="Pasase berada pada zona Daftar Pustaka/Lampiran (Back Matter) yang bersifat non-substantif.",
            coverage_ratio=1.0
        )

    # 2. Cek kecocokan katalog pola regex
    for pattern, category, rationale in BOILERPLATE_RULES:
        m = pattern.search(text)
        if m:
            matched_str = m.group(0).strip()
            matched_words = len(matched_str.split())
            coverage = matched_words / total_words if total_words > 0 else 1.0

            # Kriteria: pasase pendek (<= 20 kata), berada di FRONT_MATTER, atau cakupan frasa memadai (>= 0.25)
            if total_words <= 20 or zone == "FRONT_MATTER" or coverage >= 0.25:
                return BoilerplateDetectionResult(
                    is_boilerplate=True,
                    category=category,
                    matched_phrase=matched_str,
                    start_pos=m.start(),
                    end_pos=m.end(),
                    rationale=rationale,
                    coverage_ratio=coverage
                )

    # 3. Zona FRONT_MATTER untuk pasase pendek otomatis administratif
    if zone == "FRONT_MATTER" and total_words <= 25:
        return BoilerplateDetectionResult(
            is_boilerplate=True,
            category=BoilerplateCategory.ADMINISTRATIVE_FORMULA,
            matched_phrase=text[:60] + ("..." if len(text) > 60 else ""),
            start_pos=0,
            end_pos=len(text),
            rationale="Pasase berada pada zona Formalitas Depan (Front Matter) administratif.",
            coverage_ratio=1.0
        )

    return BoilerplateDetectionResult(
        is_boilerplate=False,
        category=BoilerplateCategory.NONE,
        matched_phrase=None,
        start_pos=None,
        end_pos=None,
        rationale="Teks merupakan kalimat spesifik penulis, bukan formulasi baku terdaftar.",
        coverage_ratio=0.0
    )
