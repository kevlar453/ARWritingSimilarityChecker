"""
AR Tilik - Citation & Bibliography Consistency Auditor
Modul audit silang (cross-reference auditor) antara sitasi di dalam tubuh naskah (in-text citations)
dan entri Daftar Pustaka (bibliography/references list) secara deterministik dan objektif.

Mendeteksi:
1. Matched Citations: Sitasi dalam teks yang terbukti memiliki entri rujukan di Daftar Pustaka.
2. Phantom Citations (Sitasi Gaib): Sitasi yang dikutip di isi naskah, tetapi TIDAK ADA di Daftar Pustaka.
3. Orphan References (Pustaka Yatim): Entri di Daftar Pustaka yang TIDAK PERNAH disitasi di tubuh naskah.

Invarian:
- Zero mutation pada engine baseline (worker/hybrid.py tetap 100% frozen).
- Independen, deterministik, dan bebas ketergantungan eksternal berat.
"""

from dataclasses import dataclass, field
import re
import unicodedata
from typing import Dict, List, Any, Optional, Set, Tuple


@dataclass
class InTextCitation:
    raw_citation: str
    author_key: str
    year: Optional[str]
    citation_type: str  # 'author_year' | 'numeric'
    numeric_id: Optional[int] = None
    span_start: int = 0
    span_end: int = 0
    context_sentence: str = ""

    def to_dict(self) -> Dict[str, Any]:
        return {
            "raw_citation": self.raw_citation,
            "author_key": self.author_key,
            "year": self.year,
            "citation_type": self.citation_type,
            "numeric_id": self.numeric_id,
            "span_start": self.span_start,
            "span_end": self.span_end,
            "context_sentence": self.context_sentence,
        }


@dataclass
class BibliographyEntry:
    entry_id: int
    raw_text: str
    author_keys: List[str]
    year: Optional[str]
    numeric_id: Optional[int] = None

    def to_dict(self) -> Dict[str, Any]:
        return {
            "entry_id": self.entry_id,
            "raw_text": self.raw_text,
            "author_keys": self.author_keys,
            "year": self.year,
            "numeric_id": self.numeric_id,
        }


@dataclass
class CitationAuditResult:
    detected_style: str  # 'author_year' | 'numeric' | 'mixed' | 'none'
    has_bibliography_section: bool
    total_in_text_citations: int
    unique_in_text_count: int
    total_bibliography_entries: int
    matched_citations_count: int
    phantom_citations_count: int
    orphan_references_count: int
    citation_health_score: float  # 0.0 - 100.0%
    health_verdict: str           # 'EXCELLENT' | 'GOOD' | 'NEEDS_ATTENTION' | 'CRITICAL'
    verdict_label: str
    summary_rationale: str
    matched_items: List[Dict[str, Any]] = field(default_factory=list)
    phantom_citations: List[Dict[str, Any]] = field(default_factory=list)
    orphan_references: List[Dict[str, Any]] = field(default_factory=list)
    external_verification: Optional[Dict[str, Any]] = None

    def to_dict(self) -> Dict[str, Any]:
        return {
            "detected_style": self.detected_style,
            "has_bibliography_section": self.has_bibliography_section,
            "total_in_text_citations": self.total_in_text_citations,
            "unique_in_text_count": self.unique_in_text_count,
            "total_bibliography_entries": self.total_bibliography_entries,
            "matched_citations_count": self.matched_citations_count,
            "phantom_citations_count": self.phantom_citations_count,
            "orphan_references_count": self.orphan_references_count,
            "citation_health_score": round(self.citation_health_score, 1),
            "health_verdict": self.health_verdict,
            "verdict_label": self.verdict_label,
            "summary_rationale": self.summary_rationale,
            "matched_items": self.matched_items,
            "phantom_citations": self.phantom_citations,
            "orphan_references": self.orphan_references,
            "external_verification": self.external_verification,
        }


def normalize_author_name(name: str) -> str:
    """Membersihkan dan menormalisasi nama belakang / penulis untuk pencocokan."""
    if not name:
        return ""
    # Hapus aksen Unicode (diacritics: Saldaña -> Saldana)
    nfkd = unicodedata.normalize('NFKD', name)
    cleaned = "".join([c for c in nfkd if not unicodedata.combining(c)])
    # Hapus bentuk possessive 's (misal: Patton's -> Patton, Radcliffe-Brown's -> Radcliffe-Brown)
    cleaned = re.sub(r"\'s\b", '', cleaned)
    # Hapus gelar atau embel-embel
    cleaned = re.sub(r'\b(?:dkk|et\s+al|Prof|Dr|Drs|Ir|M\.Si|Ph\.D|M\.Kom|S\.T|S\.Kom)\b\.?', '', cleaned, flags=re.IGNORECASE)
    # Ganti karakter non-alfabet (seperti tanda hubung '-' atau titik) dengan spasi
    cleaned = re.sub(r'[^a-zA-Z\s]', ' ', cleaned).strip().lower()
    tokens = cleaned.split()
    return tokens[-1] if tokens else ""


def clean_bib_and_reclaim_footnotes(body_text: str, bib_text: str) -> Tuple[str, str]:
    """
    Memastikan bahwa teks Daftar Pustaka (bib_text) tidak terkontaminasi oleh Lampiran
    atau Catatan Kaki / Footnotes (misal hasil ekstraksi docx di mana footnotes ditaruh di akhir).
    Jika ditemukan [Catatan Kaki / Footnotes] di bib_text, blok tersebut dipisahkan dari bib_text
    dan dikembalikan ke body_text agar sitasi di catatan kaki dapat terhitung di naskah.
    """
    if not bib_text:
        return body_text, bib_text

    lampiran_pattern = re.compile(r'(?:\n|\A)\s*(?:[0-9\.\s]*\b(?:LAMPIRAN|APPENDICES|APPENDIX)\b[^\n]*\n?)', re.IGNORECASE)
    footnote_pattern = re.compile(r'(?:\n|\A)\s*(?:\[\s*(?:Catatan\s+Kaki|Footnotes?)(?:\s*/\s*(?:Footnotes?|Catatan\s+Kaki))?\s*\][^\n]*)', re.IGNORECASE)

    lamp_match = lampiran_pattern.search(bib_text)
    fn_match = footnote_pattern.search(bib_text)

    if fn_match:
        if lamp_match and lamp_match.start() > fn_match.start():
            fn_content = bib_text[fn_match.start():lamp_match.start()]
        else:
            fn_content = bib_text[fn_match.start():]
        body_text = (body_text + "\n\n" + fn_content).strip()

    cutoff_pos = None
    if lamp_match and fn_match:
        cutoff_pos = min(lamp_match.start(), fn_match.start())
    elif lamp_match:
        cutoff_pos = lamp_match.start()
    elif fn_match:
        cutoff_pos = fn_match.start()

    if cutoff_pos is not None:
        bib_text = bib_text[:cutoff_pos].strip()

    return body_text, bib_text


def split_body_and_bibliography(text: str, segmentation_res: Any = None) -> Tuple[str, str, bool]:
    """
    Memisahkan naskah menjadi teks tubuh (body) dan teks daftar pustaka (bibliography).
    """
    if not text:
        return "", "", False

    if isinstance(text, list):
        text = "\n\n".join(str(p) for p in text)

    # 1. Coba gunakan hasil segmentation_res bila tersedia
    if segmentation_res and hasattr(segmentation_res, "sections"):
        body_parts = []
        bib_parts = []
        has_bib = False
        for s in segmentation_res.sections:
            sec_type = getattr(s, "section_type", "")
            if isinstance(sec_type, str):
                type_val = sec_type
            else:
                type_val = getattr(sec_type, "value", str(sec_type))

            sec_text = getattr(s, "text", "")
            if any(k in type_val.upper() for k in ("DAFTAR_PUSTAKA", "BIBLIOGRAFI", "BIBLIOGRAPHY", "REFERENCES", "SUMBER_BACAAN", "RUJUKAN", "KEPUSTAKAAN")):
                bib_parts.append(sec_text)
                has_bib = True
            elif "LAMPIRAN" not in type_val.upper() and "COVER" not in type_val.upper():
                body_parts.append(sec_text)

        if has_bib and bib_parts:
            b_text, b_bib = clean_bib_and_reclaim_footnotes("\n".join(body_parts), "\n".join(bib_parts))
            return b_text, b_bib, True

    # 2. Heuristik regex deterministik untuk menemukan heading Daftar Pustaka
    bib_heading_pattern = re.compile(
        r'(?:\n|\A)(?:[0-9\.\s]*\b(?:DAFTAR\s+PUSTAKA|REFERENCES?|BIBLIOGRAPHY|BIBLIOGRAFI|DAFTAR\s+REFERENSI|SUMBER\s+BACAAN(?:\s+UTAMA|\s+PENDUKUNG)?|SUMBER\s+PUSTAKA|SUMBER\s+RUJUKAN|BAHAN\s+BACAAN|KEPUSTAKAAN|RUJUKAN|LITERATURE\s+CITED|WORKS\s+CITED)\b[^\n]*\n?)',
        re.IGNORECASE
    )
    matches = list(bib_heading_pattern.finditer(text))
    if matches:
        last_match = matches[-1]
        start_bib = last_match.end()
        body_text = text[:last_match.start()]
        bib_text = text[start_bib:]

        b_text, b_bib = clean_bib_and_reclaim_footnotes(body_text, bib_text)
        return b_text, b_bib, True

    # 3. Fallback: blok URL mentah di akhir naskah (tanpa heading) dianggap daftar rujukan
    lines = text.rstrip().split("\n")
    url_re = re.compile(r"^\s*(?:https?://|www\.)\S+\s*$", re.IGNORECASE)
    idx = len(lines)
    while idx > 0 and (not lines[idx - 1].strip() or url_re.match(lines[idx - 1])):
        idx -= 1
    url_count = sum(1 for ln in lines[idx:] if url_re.match(ln))
    if url_count >= 3:
        b_text, b_bib = clean_bib_and_reclaim_footnotes("\n".join(lines[:idx]), "\n".join(lines[idx:]))
        return b_text, b_bib, True

    return text, "", False


def extract_in_text_citations(body_text: str) -> List[InTextCitation]:
    """
    Mengekstrak seluruh sitasi dalam teks tubuh karya ilmiah.
    Mendukung gaya APA / Harvard (Author-Year) dan IEEE (Numbered).
    """
    citations: List[InTextCitation] = []
    if not body_text:
        return citations

    # 1. Parenthetical Author-Year:
    # Mendukung single (Smith, 2021), compound (Bansal et al., 2024; Masenya, 2021),
    # multi-tahun (Maarif, 2012, 2023), serta awalan (e.g., Howitt, 2002; see also Laurie et al., 2003)
    re_paren_block = re.compile(r'\(([^()]*?(?:19|20)\d{2}[^()]*?)\)')
    prefix_clean_re = re.compile(r'^(?:e\.g\.|see\s+also|see|cf\.|misalnya|contohnya|lihat|bandingkan)\b[,:\s]*', re.IGNORECASE)

    for m in re_paren_block.finditer(body_text):
        inner = m.group(1).strip()

        # Abaikan rentang tahun murni seperti (1999–2002) atau nomor halaman murni
        if re.match(r'^(?:19|20)\d{2}\s*[\-\–]\s*(?:19|20)\d{2}$', inner):
            continue
        if re.match(r'^\d+$', inner):
            continue

        # Pecah sitasi majemuk yang dipisahkan titik koma (;)
        parts = [p.strip() for p in inner.split(';') if p.strip()]

        for part in parts:
            cleaned_part = prefix_clean_re.sub('', part).strip()

            # Temukan semua tahun 4-digit dalam bagian ini (mendukung multi-tahun: Maarif, 2012, 2023)
            years_found = list(re.finditer(r'\b((?:19|20)\d{2}[a-z]?)\b', cleaned_part))
            if not years_found:
                continue

            first_year_m = years_found[0]
            author_segment = cleaned_part[:first_year_m.start()].strip()
            author_segment = re.sub(r'[\s,:\(\)]+$', '', author_segment)

            if not author_segment:
                continue

            words = [w for w in re.split(r'[\s,\&]+', author_segment) if w]
            if not words or not any(w[0].isupper() for w in words if w):
                continue

            # Hindari false positive seperti Gambar, Tabel, UU, dsb.
            if any(w.lower() in {"gambar", "tabel", "lampiran", "grafik", "bagan", "figure", "table", "hal", "hlm", "pp", "vol", "no", "uu", "pasal"} for w in words):
                continue

            auth_key = normalize_author_name(author_segment)
            if not auth_key or len(auth_key) < 2:
                continue

            start = max(0, m.start() - 60)
            end = min(len(body_text), m.end() + 60)
            ctx = body_text[start:end].replace('\n', ' ').strip()

            for ym in years_found:
                yr = ym.group(1).strip()
                citations.append(InTextCitation(
                    raw_citation=f"({cleaned_part})",
                    author_key=auth_key,
                    year=yr,
                    citation_type="author_year",
                    span_start=m.start(),
                    span_end=m.end(),
                    context_sentence=ctx
                ))

    # 2. Narrative Author-Year: Menurut Santoso (2021), Pratama dkk. (2020),
    # Miles, Huberman, and Saldaña (2014), Patton's (2015), Radcliffe-Brown (1940, 1965)
    re_narrative = re.compile(
        r'([^\n()\[\]]{2,100}?)\s*\(((?:19|20)\d{2}[a-z]?(?:\s*,\s*(?:19|20)\d{2}[a-z]?)*)(?::\s*\d+(?:[\-\–]\d+)?)?\)'
    )
    valid_connectors = {'and', '&', 'dan', 'et', 'al.', 'al', 'dkk.', 'dkk', 'van', 'von', 'de', 'der', 'la', 'le'}
    fp_keywords = {
        "gambar", "tabel", "lampiran", "grafik", "bagan", "figure", "table",
        "undang-undang", "uu", "perpres", "permendikbud", "tahun", "pada", "sejak",
        "dalam", "antara", "hingga", "dari", "periode", "era", "year", "in", "since"
    }

    for m in re_narrative.finditer(body_text):
        raw_prefix = m.group(1).strip()
        years_inner = m.group(2).strip()

        words = raw_prefix.split()
        collected = []
        for w in reversed(words):
            clean_w = re.sub(r'^[^\w]+|[^\w]+$', '', w).lower()
            if w and (w[0].isupper() or clean_w in valid_connectors or (len(w) > 1 and w[0] in "'\"" and w[1].isupper())):
                collected.append(w)
            else:
                break

        if not collected:
            continue
        collected.reverse()
        auth_raw = " ".join(collected)

        auth_key = normalize_author_name(auth_raw)
        if not auth_key or len(auth_key) < 2 or auth_key in fp_keywords:
            continue

        raw = f"{auth_raw} ({years_inner})"
        start = max(0, m.start() - 60)
        end = min(len(body_text), m.end() + 60)
        ctx = body_text[start:end].replace('\n', ' ').strip()

        # Mendukung multi-tahun naratif: Radcliffe-Brown (1940, 1965)
        for ym in re.finditer(r'\b((?:19|20)\d{2}[a-z]?)\b', years_inner):
            yr = ym.group(1).strip()
            citations.append(InTextCitation(
                raw_citation=raw,
                author_key=auth_key,
                year=yr,
                citation_type="author_year",
                span_start=m.start(),
                span_end=m.end(),
                context_sentence=ctx
            ))

    # 3. Numeric In-Text Citation (IEEE): [1], [2, 3], [4-6]
    re_num = re.compile(r'(?<!\w)\[\s*(\d+(?:\s*,\s*\d+|\s*[\-\–]\s*\d+)*)\s*\]')
    for m in re_num.finditer(body_text):
        raw = m.group(0)
        inner = m.group(1).strip()
        start = max(0, m.start() - 60)
        end = min(len(body_text), m.end() + 60)
        ctx = body_text[start:end].replace('\n', ' ').strip()
        
        # Ekstrak semua angka di dalam bracket
        # Handle range e.g. 4-6 -> 4, 5, 6
        for part in re.split(r'[,]', inner):
            part = part.strip()
            range_match = re.match(r'^(\d+)\s*[\-\–]\s*(\d+)$', part)
            if range_match:
                r_start, r_end = int(range_match.group(1)), int(range_match.group(2))
                # Abaikan rentang tahun seperti [1644-1912] atau [1912-1949] (bukan nomor referensi IEEE)
                if r_start >= 1000 or r_end >= 1000 or r_start > 500:
                    continue
                if r_start <= r_end and (r_end - r_start) <= 50:
                    for n in range(r_start, r_end + 1):
                        citations.append(InTextCitation(
                            raw_citation=f"[{n}]",
                            author_key=f"ref_{n}",
                            year=None,
                            citation_type="numeric",
                            numeric_id=n,
                            span_start=m.start(),
                            span_end=m.end(),
                            context_sentence=ctx
                        ))
            else:
                num_match = re.match(r'^\d+$', part)
                if num_match:
                    n = int(num_match.group(0))
                    # Abaikan 4-digit tahun tunggal e.g. [1969] jika bukan nomor urut referensi
                    if n >= 1000 or n > 500:
                        continue
                    citations.append(InTextCitation(
                        raw_citation=f"[{n}]",
                        author_key=f"ref_{n}",
                        year=None,
                        citation_type="numeric",
                        numeric_id=n,
                        span_start=m.start(),
                        span_end=m.end(),
                        context_sentence=ctx
                    ))

    return citations


def extract_bibliography_entries(bib_text: str) -> List[BibliographyEntry]:
    """
    Membedah teks Daftar Pustaka menjadi daftar entri individual beserta kunci penulis & tahun.
    """
    entries: List[BibliographyEntry] = []
    if not bib_text:
        return entries

    raw_lines = bib_text.split('\n')
    current_entry_lines: List[str] = []

    def commit_entry(lines: List[str]):
        text_entry = " ".join(lines).strip()
        if len(text_entry) < 15:
            return

        # Ekstrak tahun (4 digit)
        year_match = re.search(r'\b((?:19|20)\d{2})[a-z]?\b', text_entry)
        year = year_match.group(1) if year_match else None

        # Cek apakah bernomor IEEE: [1] atau 1.
        num_match = re.match(r'^\[(\d+)\]|^(\d+)\.\s', text_entry)
        numeric_id = int(num_match.group(1) or num_match.group(2)) if num_match else None

        # Ekstrak nama penulis di awal entri
        # Contoh: Santoso, B., & Pratama, A. (2021) -> Santoso, Pratama
        author_keys: List[str] = []
        prefix = text_entry[:80]
        # Cari kata-kata sebelum tahun
        if year_match:
            prefix = text_entry[:year_match.start()]

        # Normalisasi diakritik unicode (misal: Saldaña -> Saldana) agar nama cocok dengan token ASCII
        nfkd_prefix = unicodedata.normalize('NFKD', prefix)
        prefix_clean = "".join([c for c in nfkd_prefix if not unicodedata.combining(c)])

        words = re.findall(r'[A-Z][a-zA-Z]+', prefix_clean)
        for w in words:
            clean_w = normalize_author_name(w)
            if clean_w and clean_w not in {"dkk", "dan", "and", "al", "vol", "no", "pp", "hal", "jurnal", "prosiding", "in", "the", "of", "ed", "eds"}:
                if clean_w not in author_keys:
                    author_keys.append(clean_w)

        # Validasi karakteristik bibliografi minimum:
        # Menolak teks naratif / potongan footnote liar yang tidak sengaja lolos ke bib_text
        has_nd = bool(re.search(r'\b(?:n\.d\.|t\.th\.|no\s+date|tanpa\s+tahun)\b', text_entry, re.IGNORECASE))
        has_url_doi = bool(re.search(r'(?:https?://|www\.|doi\.org|10\.\d{4,9}/)', text_entry, re.IGNORECASE))
        has_academic_meta = bool(re.search(r'\b(?:jurnal|journal|vol\.|volume|no\.|issue|hal\.|hlm\.|pp\.|press|penerbit|publisher|university|universitas|skripsi|tesis|disertasi|prosiding|proceedings|isbn|issn)\b', text_entry, re.IGNORECASE))

        if year is None and numeric_id is None and not has_nd and not has_url_doi and not has_academic_meta:
            return

        entries.append(BibliographyEntry(
            entry_id=len(entries) + 1,
            raw_text=text_entry,
            author_keys=author_keys,
            year=year,
            numeric_id=numeric_id
        ))

    subheading_pattern = re.compile(r'^(?:sumber\s+bacaan|sumber\s+pendukung|sumber\s+rujukan|bacaan|rujukan)\b.*:$', re.IGNORECASE)

    for line in raw_lines:
        clean_l = line.strip()
        if not clean_l:
            continue

        if subheading_pattern.match(clean_l):
            if current_entry_lines:
                commit_entry(current_entry_lines)
                current_entry_lines = []
            continue

        # Cek apakah baris ini adalah awal entri baru:
        # Indikator 1: Dimulai dengan nomor [1] atau 1.
        is_num_start = bool(re.match(r'^(?:\[\d+\]|\d+\.)\s+', clean_l))
        # Indikator 2: Dimulai dengan nama penulis kapital dan baris sebelumnya sudah cukup panjang
        # Mendukung: "Santoso, B.", "Halawa, arius", "Fatmawati. (2007)", "John Bamba (Ed.). (2008)"
        is_author_start = bool(re.match(r'^[A-Z][a-zA-Z\s\-\.\,]+,\s*[a-zA-Z]', clean_l)) or bool(re.match(r'^[A-Z][a-zA-Z\s\-\.\,\'\(\)]+?\s*\((?:19|20\d{2}|n\.d\.)', clean_l))
        # Indikator 3: baris berupa URL mentah (satu URL = satu entri)
        is_url_start = bool(re.match(r'^(?:https?://|www\.)', clean_l, re.IGNORECASE))

        if (is_num_start or is_author_start or is_url_start) and current_entry_lines:
            commit_entry(current_entry_lines)
            current_entry_lines = [clean_l]
        else:
            current_entry_lines.append(clean_l)

    if current_entry_lines:
        commit_entry(current_entry_lines)

    return entries


def audit_citations_and_bibliography(
    submission_text: str,
    segmentation_res: Any = None,
    verify_external: bool = True
) -> CitationAuditResult:
    """
    Melakukan audit konsistensi naskah secara komprehensif, mencakup konsistensi
    internal (teks vs daftar pustaka) serta verifikasi keberadaan eksternal (OpenAlex/Crossref).
    """
    body_text, bib_text, has_bib = split_body_and_bibliography(submission_text, segmentation_res)
    citations = extract_in_text_citations(body_text)
    entries = extract_bibliography_entries(bib_text) if has_bib else []

    # Tentukan style sitasi dominan
    author_year_count = sum(1 for c in citations if c.citation_type == "author_year")
    numeric_count = sum(1 for c in citations if c.citation_type == "numeric")

    if author_year_count > 0 and numeric_count == 0:
        detected_style = "APA / Harvard (Author-Year)"
    elif numeric_count > 0 and author_year_count == 0:
        detected_style = "IEEE / Vancouver (Numbered)"
    elif author_year_count > 0 and numeric_count > 0:
        detected_style = "Mixed (Author-Year & Numbered)"
    else:
        detected_style = "None Detected"

    # Himpun sitasi unik dalam teks
    unique_in_text_keys: Dict[str, List[InTextCitation]] = {}
    for c in citations:
        key = f"{c.author_key}_{c.year or ''}" if c.citation_type == "author_year" else f"num_{c.numeric_id}"
        if key not in unique_in_text_keys:
            unique_in_text_keys[key] = []
        unique_in_text_keys[key].append(c)

    matched_items: List[Dict[str, Any]] = []
    phantom_citations: List[Dict[str, Any]] = []
    cited_entry_ids: Set[int] = set()

    for key, cite_group in unique_in_text_keys.items():
        sample_cite = cite_group[0]
        found_match = False
        matching_entry = None

        if sample_cite.citation_type == "numeric":
            for e in entries:
                if e.numeric_id == sample_cite.numeric_id:
                    found_match = True
                    matching_entry = e
                    cited_entry_ids.add(e.entry_id)
                    break
        else:
            # Author-Year: Cocokkan nama penulis belakang + tahun (jika tahun ada)
            for e in entries:
                # Cek apakah author_key ada di salah satu author_keys entri pustaka
                author_match = (sample_cite.author_key in e.author_keys)
                # Cek tahun (toleran jika tahun pustaka tidak terdeteksi)
                year_match = (not sample_cite.year or not e.year or sample_cite.year[:4] == e.year[:4])
                if author_match and year_match:
                    found_match = True
                    matching_entry = e
                    cited_entry_ids.add(e.entry_id)
                    break

        if found_match and matching_entry:
            matched_items.append({
                "citation": sample_cite.raw_citation,
                "occurrences": len(cite_group),
                "author_key": sample_cite.author_key,
                "year": sample_cite.year,
                "matched_entry_id": matching_entry.entry_id,
                "matched_entry_text": matching_entry.raw_text,
                "context_sample": sample_cite.context_sentence,
            })
        else:
            phantom_citations.append({
                "citation": sample_cite.raw_citation,
                "occurrences": len(cite_group),
                "author_key": sample_cite.author_key,
                "year": sample_cite.year,
                "context_sample": sample_cite.context_sentence,
                "reason": "Tidak ditemukan entri yang cocok pada Daftar Pustaka"
            })

    # Temukan Orphan References (Daftar Pustaka yang tidak pernah dikutip di naskah)
    orphan_references: List[Dict[str, Any]] = []
    for e in entries:
        if e.entry_id not in cited_entry_ids:
            orphan_references.append({
                "entry_id": e.entry_id,
                "entry_text": e.raw_text,
                "author_keys": e.author_keys,
                "year": e.year,
                "reason": "Tercantum di Daftar Pustaka tetapi tidak pernah disitasi di naskah"
            })

    unique_count = len(unique_in_text_keys)
    matched_count = len(matched_items)
    phantom_count = len(phantom_citations)
    orphan_count = len(orphan_references)
    total_bib = len(entries)

    # Daftar rujukan berupa/didominasi URL mentah: sitasi penulis-tahun tidak dapat dicocokkan secara menyeluruh.
    # Jangan melabeli sitasi sebagai "gaib" (phantom) karena itu keterbatasan format non-baku, bukan kepalsuan sitasi.
    url_entries_count = sum(1 for e in entries if re.match(r'^(?:https?://|www\.)', e.raw_text, re.IGNORECASE))
    is_url_dominant_bib = bool(entries) and (
        (url_entries_count >= 2 and (url_entries_count / len(entries)) >= 0.4)
        or (url_entries_count == len(entries))
    )
    if is_url_dominant_bib:
        phantom_citations = []
        return CitationAuditResult(
            detected_style=detected_style,
            has_bibliography_section=True,
            total_in_text_citations=len(citations),
            unique_in_text_count=len(unique_in_text_keys),
            total_bibliography_entries=len(entries),
            matched_citations_count=len(matched_items),
            phantom_citations_count=0,
            orphan_references_count=0,
            citation_health_score=60.0,
            health_verdict="NEEDS_ATTENTION",
            verdict_label="Daftar Pustaka Berupa URL (0 Sitasi Gaib)",
            summary_rationale=(
                f"Daftar pustaka memuat {url_entries_count} tautan URL mentah (seperti Wikipedia/blog) dari total {len(entries)} rujukan. "
                "Seluruh rujukan bebas dari sitasi gaib (0 sitasi gaib) dan tidak diklasifikasikan sebagai pustaka yatim "
                "karena rujukan internet tidak menggunakan kunci sitasi baku. "
                "Format rujukan web disarankan disempurnakan ke kaidah sitasi akademik baku (APA/Chicago/IEEE) untuk kelayakan sidang."
            ),
            matched_items=matched_items,
            phantom_citations=[],
            orphan_references=[]
        )

    # Perhitungan Skor Kesehatan Sitasi (0.0 - 100.0)
    if not has_bib:
        health_score = 0.0 if unique_count > 0 else 100.0
        health_verdict = "CRITICAL" if unique_count > 0 else "EXCELLENT"
        verdict_label = "Daftar Pustaka Belum Terdeteksi"
        rationale = "Bagian Daftar Pustaka tidak terdeteksi dalam dokumen. Seluruh sitasi di naskah tidak memiliki rujukan acuan."
    elif unique_count == 0:
        health_score = 100.0 if total_bib == 0 else 50.0
        health_verdict = "GOOD" if total_bib == 0 else "NEEDS_ATTENTION"
        verdict_label = "Tidak Ada Sitasi di Tubuh Teks"
        rationale = f"Tidak ditemukan sitasi in-text di tubuh naskah. Terdapat {total_bib} entri pada Daftar Pustaka."
    else:
        # Base match ratio: persentase sitasi naskah yang berhasil ditemukan di pustaka
        base_ratio = (matched_count / unique_count) * 100.0
        # Penalti ringan untuk orphan references (maks 15 poin)
        orphan_penalty = min(15.0, (orphan_count / max(1, total_bib)) * 15.0)
        health_score = max(0.0, min(100.0, base_ratio - orphan_penalty))

        if health_score >= 85.0:
            health_verdict = "EXCELLENT"
            verdict_label = "Sangat Konsisten (Sehat)"
            rationale = f"Korelasi sitasi sangat kuat ({matched_count}/{unique_count} sitasi terhubung). Terdapat {phantom_count} sitasi gaib dan {orphan_count} pustaka tanpa sitasi."
        elif health_score >= 65.0:
            health_verdict = "GOOD"
            verdict_label = "Cukup Konsisten (Perlu Pengecekan)"
            rationale = f"Sebagian besar sitasi cocok ({matched_count}/{unique_count}), namun terdeteksi {phantom_count} sitasi gaib dan {orphan_count} pustaka yatim."
        elif health_score >= 40.0:
            health_verdict = "NEEDS_ATTENTION"
            verdict_label = "Inkonsistensi Sedang"
            rationale = f"Tingkat ketidakcocokan sitasi cukup tinggi: {phantom_count} sitasi tidak ada di Daftar Pustaka, serta {orphan_count} entri pustaka tidak pernah dikutip."
        else:
            health_verdict = "CRITICAL"
            verdict_label = "Inkonsistensi Kritis"
            rationale = f"Sebagian besar sitasi ({phantom_count} dari {unique_count}) tidak memiliki padanan di Daftar Pustaka. Diperlukan peninjauan ulang menyeluruh sebelum sidang."

    # 5. Verifikasi Eksternal Keberadaan Karya Ilmiah via OpenAlex / Crossref
    external_ver_res = None
    if verify_external and entries:
        try:
            from worker.external_citation_verifier import verify_bibliography_list
            external_ver_res = verify_bibliography_list(entries)
        except Exception as e:
            external_ver_res = {
                "enabled": False,
                "error": str(e),
                "total_checked": 0,
                "verified_count": 0,
                "suspected_fake_count": 0,
                "unverified_count": 0,
                "items": []
            }

    if external_ver_res and external_ver_res.get("has_suspected_fakes"):
        f_count = external_ver_res.get("suspected_fake_count", 0)
        rationale += (
            f" [PERINGATAN INTEGRITAS: Terdeteksi {f_count} entri di Daftar Pustaka "
            f"yang tidak ditemukan pada pangkalan data publikasi ilmiah global "
            f"(OpenAlex & Crossref), mengindikasikan sitasi fiktif / halusinasi generator AI.]"
        )
        if health_verdict in ("EXCELLENT", "GOOD"):
            verdict_label = f"{verdict_label} — Terindikasi Sitasi Fiktif (AI)"

    return CitationAuditResult(
        detected_style=detected_style,
        has_bibliography_section=has_bib,
        total_in_text_citations=len(citations),
        unique_in_text_count=unique_count,
        total_bibliography_entries=total_bib,
        matched_citations_count=matched_count,
        phantom_citations_count=phantom_count,
        orphan_references_count=orphan_count,
        citation_health_score=health_score,
        health_verdict=health_verdict,
        verdict_label=verdict_label,
        summary_rationale=rationale,
        matched_items=matched_items,
        phantom_citations=phantom_citations,
        orphan_references=orphan_references,
        external_verification=external_ver_res,
    )
