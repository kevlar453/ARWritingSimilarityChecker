"""
AR Tilik - Academic Integrity & Evasion Detection Engine
Mendeteksi teknik manipulasi dan evasif naskah ilmiah:
1. Homoglif & Substitusi Lintas Skrip (Cyrillic/Greek lookalikes vs Latin)
2. Karakter Tak Kasat Mata (Zero-Width Space, Joiners, BOM, Soft Hyphen tersembunyi)
3. Teks Tersembunyi & Mikro-Font (White-Text #FFFFFF, font <= 1.5pt, w:vanish)
4. Deobfuskasi Otomatis & Perhitungan Skor Integritas Dokumen (Evasion Index)
"""

import os
import re
import unicodedata
from typing import Dict, List, Any, Optional, Tuple
from dataclasses import dataclass, field, asdict

# Peta substitusi Homoglif Cyrillic -> Latin
CYRILLIC_TO_LATIN = {
    '\u0430': ('a', 'Cyrillic Small Letter A (U+0430)'),
    '\u0435': ('e', 'Cyrillic Small Letter E (U+0435)'),
    '\u043e': ('o', 'Cyrillic Small Letter O (U+043E)'),
    '\u0440': ('p', 'Cyrillic Small Letter ER (U+0440)'),
    '\u0441': ('c', 'Cyrillic Small Letter ES (U+0441)'),
    '\u0443': ('y', 'Cyrillic Small Letter U (U+0443)'),
    '\u0445': ('x', 'Cyrillic Small Letter HA (U+0445)'),
    '\u0456': ('i', 'Cyrillic Small Letter I (U+0456)'),
    '\u0458': ('j', 'Cyrillic Small Letter JE (U+0458)'),
    '\u0455': ('s', 'Cyrillic Small Letter DZE (U+0455)'),
    '\u0501': ('d', 'Cyrillic Small Letter KOMI DE (U+0501)'),
    '\u0410': ('A', 'Cyrillic Capital Letter A (U+0410)'),
    '\u0412': ('B', 'Cyrillic Capital Letter VE (U+0412)'),
    '\u0415': ('E', 'Cyrillic Capital Letter IE (U+0415)'),
    '\u041a': ('K', 'Cyrillic Capital Letter KA (U+041A)'),
    '\u041c': ('M', 'Cyrillic Capital Letter EM (U+041C)'),
    '\u041d': ('H', 'Cyrillic Capital Letter EN (U+041D)'),
    '\u041e': ('O', 'Cyrillic Capital Letter O (U+041E)'),
    '\u0420': ('P', 'Cyrillic Capital Letter ER (U+0420)'),
    '\u0421': ('C', 'Cyrillic Capital Letter ES (U+0421)'),
    '\u0422': ('T', 'Cyrillic Capital Letter TE (U+0422)'),
    '\u0425': ('X', 'Cyrillic Capital Letter HA (U+0425)'),
}

# Peta substitusi Homoglif Yunani -> Latin
GREEK_TO_LATIN = {
    '\u03b1': ('a', 'Greek Small Letter Alpha (U+03B1)'),
    '\u03bf': ('o', 'Greek Small Letter Omicron (U+03BF)'),
    '\u03bd': ('v', 'Greek Small Letter Nu (U+03BD)'),
    '\u03b9': ('i', 'Greek Small Letter Iota (U+03B9)'),
    '\u03c1': ('p', 'Greek Small Letter Rho (U+03C1)'),
    '\u03ba': ('k', 'Greek Small Letter Kappa (U+03BA)'),
    '\u0391': ('A', 'Greek Capital Letter Alpha (U+0391)'),
    '\u0392': ('B', 'Greek Capital Letter Beta (U+0392)'),
    '\u0395': ('E', 'Greek Capital Letter Epsilon (U+0395)'),
    '\u0397': ('H', 'Greek Capital Letter Eta (U+0397)'),
    '\u0399': ('I', 'Greek Capital Letter Iota (U+0399)'),
    '\u039a': ('K', 'Greek Capital Letter Kappa (U+039A)'),
    '\u039c': ('M', 'Greek Capital Letter Mu (U+039C)'),
    '\u039d': ('N', 'Greek Capital Letter Nu (U+039D)'),
    '\u039f': ('O', 'Greek Capital Letter Omicron (U+039F)'),
    '\u03a1': ('P', 'Greek Capital Letter Rho (U+03A1)'),
    '\u03a4': ('T', 'Greek Capital Letter Tau (U+03A4)'),
    '\u03a7': ('X', 'Greek Capital Letter Chi (U+03A7)'),
}

ALL_HOMOGLYPHS = {**CYRILLIC_TO_LATIN, **GREEK_TO_LATIN}

# Karakter tak kasat mata (Invisible & Zero-Width)
INVISIBLE_CHARACTERS = {
    '\u200b': 'Zero-Width Space (ZWSP / U+200B)',
    '\u200c': 'Zero-Width Non-Joiner (ZWNJ / U+200C)',
    '\u200d': 'Zero-Width Joiner (ZWJ / U+200D)',
    '\u2060': 'Word Joiner (WJ / U+2060)',
    '\ufeff': 'Zero-Width No-Break Space / BOM (U+FEFF)',
    '\u00ad': 'Soft Hyphen (SHY / U+00AD)',
    '\u200e': 'Left-to-Right Mark (LRM / U+200E)',
    '\u200f': 'Right-to-Left Mark (RLM / U+200F)',
    '\u202a': 'Left-to-Right Embedding (LRE / U+202A)',
    '\u202b': 'Right-to-Left Embedding (RLE / U+202B)',
    '\u202c': 'Pop Directional Formatting (PDF / U+202C)',
    '\u202d': 'Left-to-Right Override (LRO / U+202D)',
    '\u202e': 'Right-to-Left Override (RLO / U+202E)',
    '\u2061': 'Function Application (U+2061)',
    '\u2062': 'Invisible Times (U+2062)',
    '\u2063': 'Invisible Separator (U+2063)',
    '\u2064': 'Invisible Plus (U+2064)',
    '\u2066': 'Left-to-Right Isolate (LRI / U+2066)',
    '\u2067': 'Right-to-Left Isolate (RLI / U+2067)',
    '\u2068': 'First Strong Isolate (FSI / U+2068)',
    '\u2069': 'Pop Directional Isolate (PDI / U+2069)',
}


@dataclass
class EvasionAnomaly:
    category: str  # HOMOGLYPH, ZERO_WIDTH, WHITE_TEXT, MICRO_FONT
    detected_item: str
    deobfuscated_item: str
    char_code: str
    description: str
    occurrences: int = 1
    sample_context: str = ""

    def to_dict(self) -> Dict[str, Any]:
        return asdict(self)


@dataclass
class EvasionDetectionResult:
    evasion_risk_score: float  # 0.0 s.d. 100.0%
    integrity_status: str     # CLEAN, SUSPICIOUS, MANIPULATED
    verdict_label: str
    summary_rationale: str
    total_words_inspected: int
    total_anomalies_count: int
    homoglyph_count: int
    zero_width_count: int
    white_text_count: int
    micro_font_count: int
    has_mixed_script_words: bool
    anomalies: List[Dict[str, Any]] = field(default_factory=list)
    deobfuscated_preview: str = ""

    def to_dict(self) -> Dict[str, Any]:
        return asdict(self)


def deobfuscate_text(text: str) -> str:
    """
    Menghilangkan karakter tak kasat mata dan menormalkan karakter homoglif
    menjadi huruf Latin standar.
    """
    if not text:
        return ""

    result = []
    for ch in text:
        if ch in INVISIBLE_CHARACTERS:
            continue
        if ch in ALL_HOMOGLYPHS:
            result.append(ALL_HOMOGLYPHS[ch][0])
        else:
            result.append(ch)

    return "".join(result)


def deobfuscate_for_indexing(text: str) -> str:
    """
    Menormalkan kata-kata yang memadukan skrip Latin dan Homoglif Cyrillic/Yunani
    menjadi alfabet Latin murni sebelum pembuatan shingle dan fingerprint.
    Karakter tak kasat mata (zero-width characters & soft hyphen) juga disingkirkan.
    Kata yang murni Cyrillic (misal rujukan berbahasa Rusia) atau murni Yunani (simbol statistik)
    tetap dipertahankan tanpa diubah.
    """
    if not text:
        return ""

    text = "".join(ch for ch in text if ch not in INVISIBLE_CHARACTERS)

    def _fix_mixed_word(match):
        w = match.group(0)
        if any(is_latin_char(c) for c in w) and any(c in ALL_HOMOGLYPHS for c in w):
            return "".join(ALL_HOMOGLYPHS[c][0] if c in ALL_HOMOGLYPHS else c for c in w)
        return w

    return re.sub(r"\S+", _fix_mixed_word, text)


def is_latin_char(ch: str) -> bool:
    """Memeriksa apakah karakter adalah alfabet Latin standar."""
    code = ord(ch)
    return (65 <= code <= 90) or (97 <= code <= 122)


def detect_document_evasion(
    text: str,
    storage_path: Optional[str] = None,
    mime_type: Optional[str] = None
) -> EvasionDetectionResult:
    """
    Pemeriksaan komprehensif atas potensi manipulasi naskah (anti-cheat).
    """
    if not text or len(text.strip()) == 0:
        return EvasionDetectionResult(
            evasion_risk_score=0.0,
            integrity_status="CLEAN",
            verdict_label="Naskah Otentik (Tanpa Teks)",
            summary_rationale="Tidak ada teks yang dapat diuji integritasnya.",
            total_words_inspected=0,
            total_anomalies_count=0,
            homoglyph_count=0,
            zero_width_count=0,
            white_text_count=0,
            micro_font_count=0,
            has_mixed_script_words=False,
            anomalies=[],
            deobfuscated_preview=""
        )

    # 1. Deteksi Karakter Tak Kasat Mata (Zero-Width Characters)
    zero_width_findings: Dict[str, Dict[str, Any]] = {}
    for ch in text:
        if ch in INVISIBLE_CHARACTERS:
            name = INVISIBLE_CHARACTERS[ch]
            hex_code = f"U+{ord(ch):04X}"
            key = f"{hex_code}_{name}"
            if key not in zero_width_findings:
                zero_width_findings[key] = {
                    "char": ch,
                    "code": hex_code,
                    "name": name,
                    "count": 0
                }
            zero_width_findings[key]["count"] += 1

    total_zero_width = sum(v["count"] for v in zero_width_findings.values())

    # 2. Deteksi Homoglif & Campuran Skrip pada Kata
    # Pisahkan teks menjadi token kata mentah (mempertahankan karakter non-ascii)
    raw_tokens = re.findall(r'\S+', text)
    total_words = len(raw_tokens)

    homoglyph_findings: Dict[str, Dict[str, Any]] = {}
    mixed_script_words: List[Dict[str, Any]] = []

    for word in raw_tokens:
        clean_word = re.sub(r'^[^\w]+|[^\w]+$', '', word)
        if not clean_word:
            continue

        has_latin = any(is_latin_char(c) for c in clean_word)
        has_homo = any(c in ALL_HOMOGLYPHS for c in clean_word)

        # HANYA hitung anomali jika kata tersebut mencampur huruf Latin dan Homoglif
        # Mencegah salah tuduh pada simbol statistik Yunani (Cronbach α, ρ, κ) atau rujukan bahasa Rusia murni
        if has_homo and has_latin:
            for ch in clean_word:
                if ch in ALL_HOMOGLYPHS:
                    target_lat, desc = ALL_HOMOGLYPHS[ch]
                    hex_code = f"U+{ord(ch):04X}"
                    key = f"{hex_code}_{desc}"
                    if key not in homoglyph_findings:
                        homoglyph_findings[key] = {
                            "char": ch,
                            "target": target_lat,
                            "desc": desc,
                            "code": hex_code,
                            "count": 0,
                            "samples": []
                        }
                    homoglyph_findings[key]["count"] += 1
                    if clean_word not in homoglyph_findings[key]["samples"] and len(homoglyph_findings[key]["samples"]) < 5:
                        homoglyph_findings[key]["samples"].append(clean_word)

            deobf_word = "".join(ALL_HOMOGLYPHS[c][0] if c in ALL_HOMOGLYPHS else c for c in clean_word)
            if len(mixed_script_words) < 15:
                mixed_script_words.append({
                    "original": clean_word,
                    "deobfuscated": deobf_word
                })

    total_homoglyphs = sum(v["count"] for v in homoglyph_findings.values())

    # 3. Deteksi Teks Tersembunyi & Mikro-Font (Jika File PDF atau DOCX Tersedia)
    white_text_count = 0
    micro_font_count = 0
    file_anomaly_samples: List[Dict[str, Any]] = []

    if storage_path and os.path.exists(storage_path):
        ext = os.path.splitext(storage_path)[1].lower()
        if ext == ".pdf" or (mime_type and "pdf" in mime_type):
            try:
                import fitz  # PyMuPDF
                doc = fitz.open(storage_path)
                for page_idx, page in enumerate(doc):
                    page_dict = page.get_text("dict")
                    for block in page_dict.get("blocks", []):
                        if "lines" not in block:
                            continue
                        for line in block.get("lines", []):
                            for span in line.get("spans", []):
                                span_text = span.get("text", "").strip()
                                if not span_text:
                                    continue
                                span_size = span.get("size", 12)
                                span_color = span.get("color", 0)

                                # Micro-font: <= 1.5 pt
                                if span_size <= 1.5:
                                    micro_font_count += len(span_text.split())
                                    if len(file_anomaly_samples) < 5:
                                        file_anomaly_samples.append({
                                            "type": "MICRO_FONT",
                                            "text": span_text[:60],
                                            "page": page_idx + 1,
                                            "detail": f"Ukuran font terdeteksi {span_size:.1f}pt (terlalu kecil untuk dibaca manusia)"
                                        })

                                # White-text: Color 16777215 (0xFFFFFF) atau RGB (255, 255, 255)
                                # fitz menyimpan warna RGB dalam integer: (R<<16) + (G<<8) + B
                                if span_color == 16777215 or span_color == 0xFFFFFF:
                                    white_text_count += len(span_text.split())
                                    if len(file_anomaly_samples) < 5:
                                        file_anomaly_samples.append({
                                            "type": "WHITE_TEXT",
                                            "text": span_text[:60],
                                            "page": page_idx + 1,
                                            "detail": "Warna teks putih murni (#FFFFFF) disisipkan pada latar"
                                        })
                doc.close()
            except Exception as pdf_err:
                pass

        elif ext == ".docx" or (mime_type and "wordprocessingml" in mime_type):
            try:
                from docx import Document
                doc = Document(storage_path)
                for p in doc.paragraphs:
                    for run in p.runs:
                        run_text = run.text.strip()
                        if not run_text:
                            continue
                        # Micro font
                        if run.font.size and run.font.size.pt <= 1.5:
                            micro_font_count += len(run_text.split())
                        # White text
                        if run.font.color and run.font.color.rgb and str(run.font.color.rgb).upper() == "FFFFFF":
                            white_text_count += len(run_text.split())
                        # Hidden (vanish)
                        if run.font.hidden:
                            white_text_count += len(run_text.split())
            except Exception as docx_err:
                pass

    # 4. Susun Daftar Anomali Terstruktur
    anomalies_list: List[Dict[str, Any]] = []

    # Tambahkan Zero-Width Anomali
    for key, item in zero_width_findings.items():
        anomalies_list.append(EvasionAnomaly(
            category="ZERO_WIDTH_INJECTION",
            detected_item=item["name"],
            deobfuscated_item="[Dihapus]",
            char_code=item["code"],
            description=f"Ditemukan {item['count']} karakter spasi tak kasat mata diselipkan di dalam teks.",
            occurrences=item["count"],
            sample_context=item["name"]
        ).to_dict())

    # Tambahkan Homoglif Anomali
    for key, item in homoglyph_findings.items():
        sample_str = ", ".join(f"'{s}'" for s in item["samples"][:3])
        anomalies_list.append(EvasionAnomaly(
            category="HOMOGLYPH_SUBSTITUTION",
            detected_item=f"'{item['char']}' -> '{item['target']}'",
            deobfuscated_item=item["target"],
            char_code=item["code"],
            description=f"Huruf alfabet Latin diganti dengan aksara {item['desc']}. Contoh kata: {sample_str}",
            occurrences=item["count"],
            sample_context=sample_str
        ).to_dict())

    # Tambahkan White-Text / Micro-Font Anomali
    for sample in file_anomaly_samples:
        anomalies_list.append(EvasionAnomaly(
            category="HIDDEN_TEXT_INJECTION",
            detected_item=sample["type"],
            deobfuscated_item=sample["text"],
            char_code=sample["type"],
            description=f"Halaman {sample['page']}: {sample['detail']}",
            occurrences=1,
            sample_context=sample["text"]
        ).to_dict())

    # 5. Hitung Skor Risiko Evasion (0% - 100%)
    total_anomalies = total_zero_width + total_homoglyphs + white_text_count + micro_font_count
    has_mixed = len(mixed_script_words) > 0

    risk_score = 0.0
    if total_words > 0:
        # Bobot:
        # Setiap homoglif yang membentuk mixed-script berbobot tinggi (upaya bypass disengaja)
        # Karakter invisible dalam jumlah banyak berbobot tinggi
        # White text / micro font langsung berbobot tinggi
        if total_anomalies == 0:
            risk_score = 0.0
        elif total_anomalies <= 2 and not has_mixed and white_text_count == 0 and micro_font_count == 0:
            # Kemungkinan accidental copy-paste karakter soft hyphen atau non-breaking space
            risk_score = 6.5
        else:
            # Perhitungan rasio terhadap panjang naskah
            density = (total_anomalies / max(50, total_words)) * 100.0
            base_score = min(80.0, density * 12.0)

            if has_mixed:
                base_score += 25.0  # Penalti keras untuk mixed-script
            if white_text_count > 0:
                base_score += 35.0  # Penalti keras untuk white text
            if micro_font_count > 0:
                base_score += 30.0  # Penalti keras untuk mikro font

            risk_score = min(100.0, max(15.0, base_score))

    # Tentukan Status Integritas & Rationale (bahasa berbasis bukti objektif)
    if (has_mixed and total_homoglyphs >= 3) or (has_mixed and total_anomalies >= 5):
        integrity_status = "MANIPULATED"
        verdict_label = "Percampuran Skrip Karakter Terdeteksi"
        sample_words = ", ".join(f"'{m['original']}'" for m in mixed_script_words[:3])
        summary_rationale = (
            f"Ditemukan {total_anomalies} anomali karakter, termasuk {total_homoglyphs} huruf non-Latin (Cyrillic/Yunani) "
            f"yang disisipkan di dalam kata berhuruf Latin (contoh: {sample_words}). "
            f"Disarankan penelaahan manual oleh penelaah/editor."
        )
    elif risk_score >= 10.0 or total_anomalies > 0:
        integrity_status = "SUSPICIOUS"
        verdict_label = "Indikasi Anomali Karakter Perlu Tinjauan"
        details = []
        if total_homoglyphs:
            details.append(f"{total_homoglyphs} karakter substitusi aksara")
        if total_zero_width:
            details.append(f"{total_zero_width} karakter tak kasat mata (zero-width)")
        if white_text_count:
            details.append(f"{white_text_count} kata teks berformat warna putih")
        if micro_font_count:
            details.append(f"{micro_font_count} kata mikro-font")
        detail_str = ", ".join(details) if details else f"{total_anomalies} anomali"
        summary_rationale = (
            f"Terdeteksi {detail_str} dalam naskah. "
            f"Perlu verifikasi apakah hal ini akibat format dokumen/salin-tempel atau modifikasi token n-gram."
        )
    else:
        integrity_status = "CLEAN"
        verdict_label = "Integritas Naskah Otentik & Bersih"
        summary_rationale = (
            "Tidak ditemukan penyisipan karakter tak kasat mata, substitusi aksara homoglif, maupun teks tersembunyi. "
            "Struktur karakter naskah memenuhi kaidah penulisan akademik yang wajar."
        )

    deobfuscated_sample = deobfuscate_text(text[:300]) if total_anomalies > 0 else ""

    return EvasionDetectionResult(
        evasion_risk_score=round(risk_score, 1),
        integrity_status=integrity_status,
        verdict_label=verdict_label,
        summary_rationale=summary_rationale,
        total_words_inspected=total_words,
        total_anomalies_count=total_anomalies,
        homoglyph_count=total_homoglyphs,
        zero_width_count=total_zero_width,
        white_text_count=white_text_count,
        micro_font_count=micro_font_count,
        has_mixed_script_words=has_mixed,
        anomalies=anomalies_list,
        deobfuscated_preview=deobfuscated_sample
    )
