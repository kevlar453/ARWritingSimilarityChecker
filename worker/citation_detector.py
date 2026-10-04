"""
AR Tilik v1.1 - Citation & Context Detector
Mendeteksi pola atribusi sitasi, kutipan langsung, block quote, footnote,
dan bibliografi secara deterministik di sekitar pasase bukti teks.

Baseline: v1.0.0 FROZEN (worker/hybrid.py)
Sprint: 1 (WP-02)

Prinsip Deteksi:
1. Deterministik & Transparan: Berbasis pola regex leksikal terstruktur.
2. Tidak Mengubah Nilai Bukti: Deteksi konteks memperkaya interpretasi tanpa
   menghapus atau menekan metrik Exact/TF-IDF.
3. Rationale Objektif: Menjelaskan MENGAPA konteks terdeteksi, bukan vonis plagiarisme.
4. Safe Fallback: Mengembalikan UNKNOWN bila pola ambigu atau tidak lengkap.
"""

import re
from dataclasses import dataclass, field
from typing import Optional, List, Dict, Any, Tuple

from worker.contract_v1_1 import (
    CitationContext,
    CitationStatus,
    CitationConfidence,
    EvidenceContractV11,
)


@dataclass
class CitationDetectionResult:
    """
    Hasil ekstraksi konteks akademik dan sitasi dari teks bukti.
    """
    context_type: str            # CitationContext enum value
    citation_status: str         # CitationStatus enum value
    confidence: str              # CitationConfidence enum value
    matched_span: Optional[str]  # Substring persis yang mendasari deteksi
    span_start: Optional[int]    # Indeks karakter awal (0-based)
    span_end: Optional[int]      # Indeks karakter akhir (0-based)
    rationale: str               # Penjelasan objektif bukti konteks yang terdeteksi
    secondary_matches: List[Dict[str, Any]] = field(default_factory=list)

    def to_dict(self) -> Dict[str, Any]:
        return {
            "context_type": self.context_type,
            "citation_status": self.citation_status,
            "confidence": self.confidence,
            "matched_span": self.matched_span,
            "span_start": self.span_start,
            "span_end": self.span_end,
            "rationale": self.rationale,
            "secondary_matches": self.secondary_matches,
        }


# ─────────────────────────────────────────────────────────────────────────────
# REGEX PATTERNS (DETERMINISTIC ACADEMIC CONTEXT RULES)
# ─────────────────────────────────────────────────────────────────────────────

# 1. Ambiguous / Corrupted Patterns -> UNKNOWN
RE_AMBIGUOUS_PATTERNS = [
    re.compile(r"(?:[a-zA-Z0-9_\-]*\s*)?(?:\(\s*\?+[\s\?]*\)|\(\s*\)|\bxx\s*\(\?\?\)|\[\s*\?+[\s\?]*\]|\[\s*\]|\(citation\s+needed\?*\))", re.IGNORECASE),
    re.compile(r"\([A-Za-z\s\.\,]+\s*,\s*\?{2,}\)"),  # e.g. (Smith, ??)
]

# 2. Block Quote Patterns
RE_BLOCKQUOTE_INDENT = re.compile(r"^(?: {4,}|\t+)(?:\S|\n)", re.MULTILINE)
RE_BLOCKQUOTE_MD = re.compile(r"^(?:>\s+)", re.MULTILINE)
RE_BLOCKQUOTE_TAG = re.compile(r"<blockquote[^>]*>(.*?)</blockquote>", re.DOTALL | re.IGNORECASE)

# 3. Direct Quotation Patterns
RE_DIRECT_QUOTE_DOUBLE = re.compile(r'\"([^\"]{3,})\"')
RE_DIRECT_QUOTE_TYPO = re.compile(r'[“„]([^”]{3,})[”]')
RE_DIRECT_QUOTE_GUILLEMET = re.compile(r'«([^»]{3,})»')
RE_DIRECT_QUOTE_SINGLE = re.compile(r"‘([^’]{3,})’")

# 4. Footnote Patterns (attached to word without space)
RE_FOOTNOTE_BRACKET = re.compile(r'(?<=\w)\[(\^?\d+)\]')
RE_FOOTNOTE_SUPERSCRIPT = re.compile(r'(?<=\w)([¹²³⁴⁵⁶⁷⁸⁹⁰]+)')

# 5. Inline Bracketed Citations (author-year or numeric list)
RE_INLINE_AUTHOR = re.compile(
    r'\[\s*(?:(?:bdk\.|cf\.|lihat|see)\s+)?([A-Z][a-zA-Z\s\-\'\,\.\&]+(?:et\s+al\.|dkk\.)?,?\s*(?:1[6-9]|20)\d{2}[a-z]?)\s*\]'
)
RE_INLINE_NUMERIC = re.compile(
    r'(?<!\w)\[\s*([1-9]\d*(?:\s*,\s*[1-9]\d*|\s*[\-\–]\s*[1-9]\d*)*)\s*\]'
)

# 6. Parenthetical Author-Year Citations
RE_NARRATIVE_CITATION = re.compile(
    r'\b((?:Menurut|Berdasarkan|Sebagaimana\s+(?:dinyatakan|dikemukakan)\s+oleh|Sesuai\s+(?:dengan)?|Dalam|Studi\s+oleh|Penelitian\s+oleh|Kajian\s+oleh|According\s+to|As\s+(?:stated|noted)\s+by)\s+([A-Z][a-zA-Z\s\-\'\&]+(?:(?:et\s+al\.|dkk\.)|\s*(?:&|and|dan)\s*[A-Z][a-zA-Z\s\-\']+)?)\s*\(((?:1[6-9]|20)\d{2}[a-z]?(?::\s*\d+(?:[\-\–]\d+)?)?)\))'
)
RE_NARRATIVE_SIMPLE = re.compile(
    r'\b(([A-Z][a-zA-Z\s\-\'\&]+(?:(?:et\s+al\.|dkk\.)|\s*(?:&|and|dan)\s*[A-Z][a-zA-Z\s\-\']+)?)\s*\(((?:1[6-9]|20)\d{2}[a-z]?(?::\s*\d+(?:[\-\–]\d+)?)?)\))'
)
RE_PARENTHETICAL = re.compile(
    r'(\(\s*(?:(?:bdk\.|cf\.|lihat|see)\s+)?(?:[a-zA-Z0-9_\-\s\.\,\'\&]+?oleh\s+)?([A-Z][a-zA-Z\s\-\'\&]+(?:(?:et\s+al\.|dkk\.)|\s*(?:&|and|dan)\s*[A-Z][a-zA-Z\s\-\']+)?)\,\s*(?:1[6-9]|20)\d{2}[a-z]?(?::\s*\d+(?:[\-\–]\d+)?)?\s*\))'
)

# 7. Bibliography / Reference List Entry
RE_BIB_HEADER = re.compile(
    r"^(?:daftar\s+pustaka|daftar\s+referensi|referensi|references|bibliography|works\s+cited)[\s\:\.]*$",
    re.IGNORECASE | re.MULTILINE
)
RE_BIB_ENTRY = re.compile(
    r"^[A-Z][a-zA-Z\s\-\'\,\.\&]+?\,\s*[A-Z\.]+(?:[a-zA-Z\s\-\'\,\.\&]+)?\s*\((?:19|20)\d{2}[a-z]?\)\.\s+.*?(?:\.|\d+[\-\–]\d+|\bpp?\.\s*\d+)",
    re.MULTILINE
)


def detect_citation_context(text: Optional[str]) -> CitationDetectionResult:
    """
    Menganalisis teks bukti untuk mendeteksi penanda konteks akademik secara deterministik.

    Parameter:
    - text: Pasase teks bukti yang akan dianalisis.

    Mengembalikan:
    - CitationDetectionResult berisi context_type, citation_status, confidence,
      matched_span, span_start, span_end, dan rationale yang dapat diaudit.
    """
    if not text or not str(text).strip():
        return CitationDetectionResult(
            context_type=CitationContext.NONE.value,
            citation_status=CitationStatus.NOT_CITED.value,
            confidence=CitationConfidence.HIGH.value,
            matched_span=None,
            span_start=None,
            span_end=None,
            rationale="No adjacent citation marker or attribution pattern detected in surrounding passage.",
        )

    raw_text = str(text)

    # ── Tahap 1: Cek Pola Ambigu / Rusak (UNKNOWN) ───────────────
    for pattern in RE_AMBIGUOUS_PATTERNS:
        m = pattern.search(raw_text)
        if m:
            return CitationDetectionResult(
                context_type=CitationContext.UNKNOWN.value,
                citation_status=CitationStatus.UNKNOWN.value,
                confidence=CitationConfidence.UNKNOWN.value,
                matched_span=m.group(0),
                span_start=m.start(),
                span_end=m.end(),
                rationale="Unable to determine academic attribution context reliably from available text span.",
            )

    # ── Tahap 2: Cek Bibliografi / Daftar Referensi ───────────────
    if RE_BIB_HEADER.search(raw_text):
        m_head = RE_BIB_HEADER.search(raw_text)
        return CitationDetectionResult(
            context_type=CitationContext.BIBLIOGRAPHY.value,
            citation_status=CitationStatus.NOT_APPLICABLE.value,
            confidence=CitationConfidence.HIGH.value,
            matched_span=m_head.group(0),
            span_start=m_head.start(),
            span_end=m_head.end(),
            rationale="Detected formal reference list or bibliography section heading.",
        )

    m_bib = RE_BIB_ENTRY.search(raw_text)
    if m_bib:
        return CitationDetectionResult(
            context_type=CitationContext.BIBLIOGRAPHY.value,
            citation_status=CitationStatus.NOT_APPLICABLE.value,
            confidence=CitationConfidence.HIGH.value,
            matched_span=raw_text.strip(),
            span_start=0,
            span_end=len(raw_text),
            rationale="Detected formal reference list or bibliography entry structure.",
        )

    # ── Tahap 3: Cek Block Quote (Indented / Markdown) ───────────
    m_bq = RE_BLOCKQUOTE_INDENT.search(raw_text) or RE_BLOCKQUOTE_MD.search(raw_text) or RE_BLOCKQUOTE_TAG.search(raw_text)
    if m_bq:
        span = raw_text[m_bq.start():].rstrip()
        return CitationDetectionResult(
            context_type=CitationContext.BLOCK_QUOTE.value,
            citation_status=CitationStatus.QUOTE.value,
            confidence=CitationConfidence.HIGH.value,
            matched_span=span,
            span_start=m_bq.start(),
            span_end=m_bq.start() + len(span),
            rationale="Detected indented block quotation formatting structure.",
        )

    # ── Tahap 4: Ekstraksi Cues Bersamaan (Kutipan & Sitasi) ───────
    direct_quotes: List[Tuple[str, int, int]] = []
    for pat in (RE_DIRECT_QUOTE_DOUBLE, RE_DIRECT_QUOTE_TYPO, RE_DIRECT_QUOTE_GUILLEMET, RE_DIRECT_QUOTE_SINGLE):
        for match in pat.finditer(raw_text):
            direct_quotes.append((match.group(0), match.start(), match.end()))

    footnotes: List[Tuple[str, int, int]] = []
    for pat in (RE_FOOTNOTE_BRACKET, RE_FOOTNOTE_SUPERSCRIPT):
        for match in pat.finditer(raw_text):
            footnotes.append((match.group(0), match.start(), match.end()))

    inline_citations: List[Tuple[str, int, int]] = []
    for pat in (RE_INLINE_AUTHOR, RE_INLINE_NUMERIC):
        for match in pat.finditer(raw_text):
            inline_citations.append((match.group(0), match.start(), match.end()))

    parenthetical_citations: List[Tuple[str, int, int]] = []
    for pat in (RE_NARRATIVE_CITATION, RE_PARENTHETICAL, RE_NARRATIVE_SIMPLE):
        for match in pat.finditer(raw_text):
            # Hindari duplikasi jika naratif sudah mencakup parenthetical
            span_str = match.group(0)
            if not any(match.start() >= p[1] and match.end() <= p[2] for p in parenthetical_citations):
                parenthetical_citations.append((span_str, match.start(), match.end()))

    # ── Tahap 5: Evaluasi Prioritas Konteks ───────────────────────

    # Kasus A: Direct Quote
    if direct_quotes:
        primary_quote = direct_quotes[0]
        secondary = []
        notes = ["Detected passage enclosed within typographic or direct quotation marks."]

        if parenthetical_citations:
            secondary.append({
                "context_type": CitationContext.PARENTHETICAL_CITATION.value,
                "span": parenthetical_citations[0][0],
            })
            notes.append(f"Accompanied by adjacent citation {parenthetical_citations[0][0]}.")
        elif inline_citations:
            secondary.append({
                "context_type": CitationContext.INLINE_CITATION.value,
                "span": inline_citations[0][0],
            })
            notes.append(f"Accompanied by adjacent citation {inline_citations[0][0]}.")

        return CitationDetectionResult(
            context_type=CitationContext.DIRECT_QUOTE.value,
            citation_status=CitationStatus.QUOTE.value,
            confidence=CitationConfidence.HIGH.value,
            matched_span=primary_quote[0],
            span_start=primary_quote[1],
            span_end=primary_quote[2],
            rationale=" ".join(notes),
            secondary_matches=secondary,
        )

    # Kasus B: Footnote (marker menempel pada kata)
    if footnotes:
        primary_fn = footnotes[0]
        return CitationDetectionResult(
            context_type=CitationContext.FOOTNOTE.value,
            citation_status=CitationStatus.CITED.value,
            confidence=CitationConfidence.HIGH.value,
            matched_span=primary_fn[0],
            span_start=primary_fn[1],
            span_end=primary_fn[2],
            rationale="Detected numeric superscript or footnote reference marker adjacent to evidence span.",
        )

    # Kasus C: Inline Citation (bracketed author-year atau numeric list)
    if inline_citations:
        primary_inline = inline_citations[0]
        return CitationDetectionResult(
            context_type=CitationContext.INLINE_CITATION.value,
            citation_status=CitationStatus.CITED.value,
            confidence=CitationConfidence.HIGH.value,
            matched_span=primary_inline[0],
            span_start=primary_inline[1],
            span_end=primary_inline[2],
            rationale="Detected bracketed inline reference marker adjacent to evidence span.",
        )

    # Kasus D: Parenthetical Citation (author-year atau naratif)
    if parenthetical_citations:
        primary_par = parenthetical_citations[0]
        return CitationDetectionResult(
            context_type=CitationContext.PARENTHETICAL_CITATION.value,
            citation_status=CitationStatus.CITED.value,
            confidence=CitationConfidence.HIGH.value,
            matched_span=primary_par[0],
            span_start=primary_par[1],
            span_end=primary_par[2],
            rationale="Detected author-year citation pattern adjacent to evidence span.",
        )

    # ── Tahap 6: Default Kasus Bersih Tanpa Konteks (NONE) ────────
    return CitationDetectionResult(
        context_type=CitationContext.NONE.value,
        citation_status=CitationStatus.NOT_CITED.value,
        confidence=CitationConfidence.HIGH.value,
        matched_span=None,
        span_start=None,
        span_end=None,
        rationale="No adjacent citation marker or attribution pattern detected in surrounding passage.",
    )


def enrich_contract_with_context(
    contract: EvidenceContractV11,
    evidence_text: Optional[str] = None
) -> EvidenceContractV11:
    """
    Menerapkan hasil Citation / Context Detector ke dalam objek EvidenceContractV11
    secara deterministik tanpa mengubah metrik kemiripan numerik asli (Exact / TF-IDF).
    """
    detection = detect_citation_context(evidence_text)

    contract.citation_context = detection.context_type
    contract.citation_status = detection.citation_status
    contract.citation_confidence = detection.confidence
    contract.context_span = detection.matched_span
    contract.rationale = detection.rationale

    return contract
