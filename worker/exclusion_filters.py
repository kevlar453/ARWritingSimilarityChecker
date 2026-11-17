"""
AR Tilik - Single Source of Truth Pengecualian Pasase & Perhitungan Skor Murni (P1-3).

Menyatukan logika klasifikasi pengecualian (Kutipan, Frasa Baku, Formalitas Depan,
dan Bibliografi) serta rumus union interval kata agar UI (report.php) dan pengekspor PDF
(exporter.py) selalu menghasilkan angka dan daftar pengecualian yang persis sama.
"""

from __future__ import annotations

import argparse
import json
import re
from typing import Any, Dict, List, Optional, Sequence, Tuple

from worker.boilerplate_detector import BoilerplateCategory, detect_academic_boilerplate
from worker.citation_detector import RE_BIB_ENTRY
from worker.database import get_connection
from worker.section_parser import SectionZone, parse_document_sections
from worker.similarity import merge_ranges

EXCLUSION_SCHEMA_VERSION = 2

FILTER_LABELS: Dict[str, str] = {
    "quotes": "Kutipan (Tanda Petik)",
    "boilerplate": "Frasa Baku (Boilerplate)",
    "front_matter": "Formalitas Depan (Front Matter)",
    "bibliography": "Bibliografi / Daftar Pustaka",
}

_TOKEN_RE = re.compile(r"[^\W_]+(?:[-\'][^\W_]+)*", re.UNICODE)

# Pola deteksi kutipan langsung multi-format pada teks penuh (dibatasi agar tidak melintasi paragraf kosong)
_QUOTE_PATTERNS: Tuple[re.Pattern[str], ...] = (
    re.compile(r'"((?:(?!\n\s*\n)[^"]){3,3500})"'),
    re.compile(r"[“„]((?:(?!\n\s*\n)[^”]){3,3500})”"),
    re.compile(r"«((?:(?!\n\s*\n)[^»]){3,3500})»"),
    re.compile(r"‘((?:(?!\n\s*\n)[^’]){15,3500})’"),
    re.compile(r"<blockquote[^>]*>(.*?)</blockquote>", re.IGNORECASE | re.DOTALL),
    re.compile(r"(?:^>\s+.*(?:\n|$))+", re.MULTILINE),
    re.compile(r"(?:^(?: {4,}|\t+)\S.*(?:\n|$)){2,}", re.MULTILINE),
)

# Pola heading Daftar Pustaka / Referensi (mendukung penomoran bab/bagian opsional)
_RE_BIB_SECTION_HEADING = re.compile(
    r"(?im)^[^\S\r\n]*(?:(?:BAB\s+[IVXLCDM\d]+|[IVXLCDM\d]+|[A-Z])\.?\s+)?"
    r"(?:daftar\s+pustaka|daftar\s+referensi|daftar\s+rujukan|bibliografi|"
    r"bibliography|references?|literature\s+cited|works\s+cited|sumber\s+rujukan)"
    r"[^\S\r\n]*[:.\-]?[^\S\r\n]*$"
)

_RE_FRONT_MATTER_TEXT = re.compile(
    r"(?i)\b(?:lembar\s+(?:pengesahan|persetujuan)|pernyataan\s+(?:keaslian|bebas\s+plagiasi)|"
    r"kata\s+pengantar|daftar\s+(?:isi|tabel|gambar|lampiran))\b"
)

_RE_BIB_HEADER_WORDS = re.compile(
    r"(?i)\b(?:daftar\s+pustaka|daftar\s+referensi|bibliografi|references|bibliography|works\s+cited)\b"
)


def tokenize_with_spans(submission_text: str) -> List[Tuple[int, int]]:
    """Menghasilkan daftar (start_char, end_char) token yang selaras dengan worker/reporter.py."""
    if not submission_text:
        return []
    return [m.span() for m in _TOKEN_RE.finditer(submission_text.lower())]


def extract_quote_char_spans(submission_text: str) -> List[Tuple[int, int]]:
    """
    Menemukan seluruh rentang karakter kutipan langsung ("...", “...”, «...», blockquote)
    pada teks dokumen utuh sehingga tanda petik di tepi pasase tidak terpotong oleh batas snippet.
    """
    if not submission_text:
        return []

    spans: List[Tuple[int, int]] = []
    for pat in _QUOTE_PATTERNS:
        for m in pat.finditer(submission_text):
            # Gunakan rentang penuh dari tanda kutip buka ke tanda kutip tutup
            s_char, e_char = m.span()
            if e_char > s_char:
                spans.append((s_char, e_char))

    if not spans:
        return []

    spans.sort()
    merged: List[Tuple[int, int]] = [spans[0]]
    for s_char, e_char in spans[1:]:
        prev_s, prev_e = merged[-1]
        if s_char <= prev_e:
            merged[-1] = (prev_s, max(prev_e, e_char))
        else:
            merged.append((s_char, e_char))
    return merged


def _find_bibliography_start_char(submission_text: str) -> Optional[int]:
    """Menemukan indeks karakter awal bagian Daftar Pustaka bila tersedia di teks."""
    if not submission_text:
        return None
    text_len = len(submission_text)
    candidates = [m.start() for m in _RE_BIB_SECTION_HEADING.finditer(submission_text)]
    if not candidates:
        return None
    # Pilih heading daftar pustaka terakhir di paruh akhir naskah (>= 30% panjang teks)
    for pos in reversed(candidates):
        if pos >= int(text_len * 0.30):
            return pos
    return candidates[-1]


def _char_overlap_ratio(char_start: int, char_end: int, spans: List[Tuple[int, int]]) -> float:
    length = max(1, char_end - char_start)
    overlap = 0
    for s_c, e_c in spans:
        if e_c <= char_start:
            continue
        if s_c >= char_end:
            break
        overlap += max(0, min(char_end, e_c) - max(char_start, s_c))
    return min(1.0, overlap / length)


def _extract_paragraph_bounds(text: str, pos: int) -> Tuple[int, int]:
    prev_break = text.rfind("\n\n", 0, pos)
    p_start = 0 if prev_break == -1 else prev_break + 2
    next_break = text.find("\n\n", pos)
    p_end = len(text) if next_break == -1 else next_break
    return p_start, p_end


def _find_zone_for_char(char_pos: int, sections: Sequence[Any]) -> Tuple[str, str]:
    for sec in sections:
        start_c = getattr(sec, "start_char", None) if not isinstance(sec, dict) else sec.get("start_char")
        end_c = getattr(sec, "end_char", None) if not isinstance(sec, dict) else sec.get("end_char")
        if start_c is not None and end_c is not None and start_c <= char_pos < end_c:
            zone = getattr(sec, "section_zone", None) if not isinstance(sec, dict) else sec.get("zone")
            if hasattr(zone, "value"):
                zone = zone.value
            title = getattr(sec, "title", None) if not isinstance(sec, dict) else sec.get("title")
            return str(zone or "UNKNOWN"), str(title or "UNKNOWN")
    return "UNKNOWN", "UNKNOWN"


def _compute_single_match_flags(
    *,
    match: Dict[str, Any],
    submission_text: str,
    token_spans: List[Tuple[int, int]],
    sub_tokens: List[str],
    quote_spans: List[Tuple[int, int]],
    bib_start_char: Optional[int],
    sections: Sequence[Any],
) -> Dict[str, Any]:
    s_start = int(match.get("submission_start") or 0)
    w_count = int(match.get("word_count") or 0)
    s_end = int(match["submission_end"]) if match.get("submission_end") is not None else (s_start + max(0, w_count - 1))

    char_start = 0
    char_end = 0
    if token_spans and 0 <= s_start < len(token_spans):
        safe_end_idx = min(max(s_start, s_end), len(token_spans) - 1)
        char_start = token_spans[s_start][0]
        char_end = token_spans[safe_end_idx][1]

    # Zona & nama seksi dari segmenter bila belum ada atau masih UNKNOWN
    zone_val = str(match.get("zone") or "").strip()
    sec_title = str(match.get("section_name") or "").strip()
    if (not zone_val or zone_val == "UNKNOWN") and sections and token_spans and 0 <= s_start < len(token_spans):
        zone_val, sec_title = _find_zone_for_char(char_start, sections)
        match["zone"] = zone_val
        match["section_name"] = sec_title
    zone_upper = zone_val.upper() if zone_val else "UNKNOWN"

    # Teks pasase mentah + potongan ber-tanda-baca asli
    passage_raw = ""
    if submission_text and char_end > char_start:
        passage_raw = submission_text[char_start:char_end]
    if not passage_raw and match.get("submission_snippet"):
        passage_raw = str(match["submission_snippet"])
    if not passage_raw and match.get("passage_text"):
        passage_raw = str(match["passage_text"])

    passage_norm_slice = ""
    if sub_tokens and 0 <= s_start < len(sub_tokens):
        passage_norm_slice = " ".join(sub_tokens[s_start : s_end + 1])

    # Evaluasi boilerplate bila belum tersedia
    bp_cat = str(match.get("boilerplate_category") or "NONE").upper()
    has_bp_field = "is_boilerplate" in match
    if (not has_bp_field or not match.get("is_boilerplate")) and (passage_norm_slice or passage_raw):
        target_for_bp = passage_norm_slice or passage_raw
        bp_res = detect_academic_boilerplate(target_for_bp, zone=zone_upper)
        if not bp_res.is_boilerplate and sub_tokens and 0 <= s_start < len(sub_tokens):
            ctx_slice = sub_tokens[max(0, s_start - 2) : min(len(sub_tokens), s_end + 4)]
            bp_res = detect_academic_boilerplate(" ".join(ctx_slice), zone=zone_upper)
        if bp_res.is_boilerplate:
            match["is_boilerplate"] = True
            bp_cat = bp_res.category.value
            match["boilerplate_category"] = bp_cat
            match["boilerplate_phrase"] = bp_res.matched_phrase
            match["boilerplate_rationale"] = bp_res.rationale
        elif not has_bp_field:
            match["is_boilerplate"] = False
            match["boilerplate_category"] = "NONE"

    raw_is_bp = bool(match.get("is_boilerplate"))

    # 1. Quote flag: tumpang tindih karakter >= 60% dengan rentang kutipan langsung / block quote
    is_quote = False
    if quote_spans and char_end > char_start:
        overlap = _char_overlap_ratio(char_start, char_end, quote_spans)
        if overlap >= 0.60:
            is_quote = True
    if not is_quote and passage_raw:
        # Fallback jika dokumen mentah tidak tersedia tapi snippet memiliki tanda kutip sepasang yang membungkus mayoritas teks
        for pat in _QUOTE_PATTERNS[:4]:
            qm = pat.search(passage_raw)
            if qm and (qm.end() - qm.start()) / max(1, len(passage_raw)) >= 0.60:
                is_quote = True
                break
    if str(match.get("citation_status") or "").upper() == "QUOTE":
        is_quote = True

    # 2. Bibliography flag: zona BACK_MATTER, kategori BIBLIOGRAPHIC_ENTRY, setelah heading Daftar Pustaka, atau entri pustaka formal
    is_bib = False
    if zone_upper == SectionZone.BACK_MATTER.value:
        is_bib = True
    elif bp_cat == BoilerplateCategory.BIBLIOGRAPHIC_ENTRY.value:
        is_bib = True
    elif bib_start_char is not None and char_end > char_start and char_start >= bib_start_char:
        is_bib = True
    elif _RE_BIB_HEADER_WORDS.search(sec_title) or _RE_BIB_HEADER_WORDS.search(passage_raw):
        is_bib = True
    elif submission_text and char_end > char_start:
        p_s, p_e = _extract_paragraph_bounds(submission_text, char_start)
        para_txt = submission_text[p_s:p_e]
        if RE_BIB_ENTRY.search(para_txt):
            is_bib = True

    # 3. Front Matter flag: zona FRONT_MATTER, kategori administratif/hukum/daftar isi, atau frasa formalitas depan
    is_front = False
    if zone_upper == SectionZone.FRONT_MATTER.value:
        is_front = True
    elif bp_cat in (
        BoilerplateCategory.ADMINISTRATIVE_FORMULA.value,
        BoilerplateCategory.LEGAL_CITATION.value,
        BoilerplateCategory.TABLE_OF_CONTENTS.value,
    ):
        is_front = True
    elif _RE_FRONT_MATTER_TEXT.search(passage_raw):
        is_front = True

    # 4. Boilerplate filter flag: formulasi baku non-bibliografi (agar filter Frasa Baku & Bibliografi tidak saling menelan)
    is_bp_filter = raw_is_bp and (bp_cat not in (
        BoilerplateCategory.BIBLIOGRAPHIC_ENTRY.value,
        BoilerplateCategory.TABLE_OF_CONTENTS.value,
    ))

    flags = {
        "quote": bool(is_quote),
        "boilerplate": bool(is_bp_filter),
        "front_matter": bool(is_front),
        "bibliography": bool(is_bib),
    }

    match["exclusion_flags"] = flags
    match["is_quote"] = flags["quote"]
    match["is_front_matter"] = flags["front_matter"]
    match["is_bibliography"] = flags["bibliography"]
    return flags


def annotate_matches_with_exclusions(
    submission_text: str,
    matches: List[Dict[str, Any]],
    sections: Optional[Sequence[Any]] = None,
    token_spans: Optional[List[Tuple[int, int]]] = None,
) -> List[Dict[str, Any]]:
    """
    Memperkaya seluruh match dengan metadata zona & exclusion_flags server-side secara deterministik.
    Mengembalikan daftar matches yang telah diperkaya secara in-place.
    """
    if not matches:
        return matches

    text = submission_text or ""
    spans = token_spans if token_spans is not None else tokenize_with_spans(text)
    sub_tokens = [text[s:e].lower() for s, e in spans] if spans and text else []
    quote_spans = extract_quote_char_spans(text)
    bib_start_char = _find_bibliography_start_char(text)

    resolved_sections = sections
    if resolved_sections is None and text.strip():
        try:
            seg = parse_document_sections(text)
            resolved_sections = seg.sections
        except Exception:
            resolved_sections = []

    for m in matches:
        _compute_single_match_flags(
            match=m,
            submission_text=text,
            token_spans=spans,
            sub_tokens=sub_tokens,
            quote_spans=quote_spans,
            bib_start_char=bib_start_char,
            sections=resolved_sections or [],
        )

    return matches


def get_match_exclusion_flags(match: Dict[str, Any]) -> Dict[str, bool]:
    """Membaca 4 flag pengecualian secara aman dari struktur match apa pun."""
    flags = match.get("exclusion_flags")
    if isinstance(flags, dict) and all(k in flags for k in ("quote", "boilerplate", "front_matter", "bibliography")):
        return {
            "quote": bool(flags["quote"]),
            "boilerplate": bool(flags["boilerplate"]),
            "front_matter": bool(flags["front_matter"]),
            "bibliography": bool(flags["bibliography"]),
        }

    bp_cat = str(match.get("boilerplate_category") or "NONE").upper()
    zone_upper = str(match.get("zone") or "UNKNOWN").upper()
    raw_snip = str(match.get("submission_snippet") or match.get("passage_text") or "")

    return {
        "quote": bool(match.get("is_quote")) or (str(match.get("citation_status") or "").upper() == "QUOTE"),
        "boilerplate": bool(match.get("is_boilerplate")) and bp_cat not in (
            BoilerplateCategory.BIBLIOGRAPHIC_ENTRY.value,
            BoilerplateCategory.TABLE_OF_CONTENTS.value,
        ),
        "front_matter": bool(match.get("is_front_matter"))
        or zone_upper == SectionZone.FRONT_MATTER.value
        or bp_cat in (
            BoilerplateCategory.ADMINISTRATIVE_FORMULA.value,
            BoilerplateCategory.LEGAL_CITATION.value,
            BoilerplateCategory.TABLE_OF_CONTENTS.value,
        )
        or bool(_RE_FRONT_MATTER_TEXT.search(raw_snip)),
        "bibliography": bool(match.get("is_bibliography"))
        or zone_upper == SectionZone.BACK_MATTER.value
        or bp_cat == BoilerplateCategory.BIBLIOGRAPHIC_ENTRY.value
        or bool(_RE_BIB_HEADER_WORDS.search(raw_snip)),
    }


def count_union_words(intervals: Sequence[Tuple[int, int]]) -> int:
    """Menghitung total kata unik dari gabungan interval tertutup [start, end] (anti double-counting)."""
    valid = [(int(s), int(e)) for s, e in intervals if int(e) >= int(s)]
    if not valid:
        return 0
    merged = merge_ranges(valid)
    return sum(max(0, e - s + 1) for s, e in merged)


def compute_filtered_score(
    raw_overall_similarity: float,
    matches: Sequence[Dict[str, Any]],
    *,
    exclude_quotes: bool = False,
    exclude_boilerplate: bool = False,
    exclude_front_matter: bool = False,
    exclude_bibliography: bool = False,
) -> Dict[str, Any]:
    """
    Port persis logika recalculateLiveScore() (report.php):
    - Gabung seluruh interval kata unik [submission_start, submission_end] (union)
    - Kecualikan pasase sesuai filter aktif
    - skor_murni = (kata_aktif / kata_total) * skor_mentah
    """
    all_intervals: List[Tuple[int, int]] = []
    active_intervals: List[Tuple[int, int]] = []
    active_matches: List[Dict[str, Any]] = []
    excluded_matches_count = 0

    for m in matches:
        w_count = int(m.get("word_count") or 0)
        s_start = int(m.get("submission_start") or 0)
        s_end = int(m["submission_end"]) if m.get("submission_end") is not None else (s_start + max(0, w_count - 1))

        if w_count > 0 and s_end >= s_start:
            all_intervals.append((s_start, s_end))

        flags = get_match_exclusion_flags(m)
        excluded_reasons: List[str] = []
        if exclude_quotes and flags["quote"]:
            excluded_reasons.append("Kutipan")
        if exclude_boilerplate and flags["boilerplate"]:
            excluded_reasons.append("Frasa Baku")
        if exclude_front_matter and flags["front_matter"]:
            excluded_reasons.append("Formalitas Depan")
        if exclude_bibliography and flags["bibliography"]:
            excluded_reasons.append("Bibliografi")

        if excluded_reasons:
            excluded_matches_count += 1
        else:
            active_matches.append(m)
            if w_count > 0 and s_end >= s_start:
                active_intervals.append((s_start, s_end))

    total_union_words = count_union_words(all_intervals)
    active_union_words = count_union_words(active_intervals)
    excluded_words = max(0, total_union_words - active_union_words)

    raw_score = float(raw_overall_similarity or 0.0)
    if total_union_words > 0:
        adjusted_score = (active_union_words / total_union_words) * raw_score
    elif excluded_matches_count > 0:
        adjusted_score = 0.0
    else:
        adjusted_score = raw_score
    adjusted_score = max(0.0, min(raw_score, adjusted_score))

    active_filter_keys: List[str] = []
    active_filter_labels: List[str] = []
    if exclude_quotes:
        active_filter_keys.append("quotes")
        active_filter_labels.append(FILTER_LABELS["quotes"])
    if exclude_boilerplate:
        active_filter_keys.append("boilerplate")
        active_filter_labels.append(FILTER_LABELS["boilerplate"])
    if exclude_front_matter:
        active_filter_keys.append("front_matter")
        active_filter_labels.append(FILTER_LABELS["front_matter"])
    if exclude_bibliography:
        active_filter_keys.append("bibliography")
        active_filter_labels.append(FILTER_LABELS["bibliography"])

    return {
        "has_active_filters": bool(active_filter_keys),
        "active_filter_keys": active_filter_keys,
        "active_filter_labels": active_filter_labels,
        "raw_similarity": raw_score,
        "filtered_similarity": adjusted_score,
        "rounded_raw_similarity": round(raw_score, 2),
        "rounded_filtered_similarity": round(adjusted_score, 2),
        "total_union_words": total_union_words,
        "active_union_words": active_union_words,
        "excluded_words": excluded_words,
        "excluded_passages_count": excluded_matches_count,
        "total_passages_count": len(matches),
        "active_matches": active_matches,
    }


def build_match_key(m: Dict[str, Any]) -> str:
    return f"{int(m.get('source_document_id') or 0)}_{int(m.get('submission_start') or 0)}_{int(m.get('submission_end') or 0)}"


def merge_db_and_evidence_matches(
    db_matches: List[Dict[str, Any]],
    evidence_matches: Sequence[Dict[str, Any]],
    submission_text: str = "",
) -> List[Dict[str, Any]]:
    """
    Menyelaraskan baris report_matches dari SQL dengan anotasi dari evidence_data['matches'],
    serta menghitung exclusion_flags secara server-side bila belum tersimpan.
    """
    ev_exact_map: Dict[str, Dict[str, Any]] = {}
    ev_start_map: Dict[str, Dict[str, Any]] = {}
    for em in evidence_matches or []:
        ev_exact_map[build_match_key(em)] = em
        ev_start_map[f"{int(em.get('source_document_id') or 0)}_{int(em.get('submission_start') or 0)}"] = em

    enriched: List[Dict[str, Any]] = []
    needs_annotation = False
    for row in db_matches:
        item = dict(row)
        k_exact = build_match_key(item)
        k_start = f"{int(item.get('source_document_id') or 0)}_{int(item.get('submission_start') or 0)}"
        ev = ev_exact_map.get(k_exact) or ev_start_map.get(k_start)
        if ev:
            for fld in (
                "is_boilerplate",
                "boilerplate_category",
                "boilerplate_phrase",
                "boilerplate_rationale",
                "zone",
                "section_name",
                "exclusion_flags",
                "is_quote",
                "is_front_matter",
                "is_bibliography",
                "citation_status",
            ):
                if fld in ev and fld not in item:
                    item[fld] = ev[fld]
                elif fld in ev and item.get(fld) in (None, "", "NONE", "UNKNOWN"):
                    item[fld] = ev[fld]
                elif fld == "exclusion_flags" and fld in ev:
                    item[fld] = ev[fld]
        if "exclusion_flags" not in item:
            needs_annotation = True
        enriched.append(item)

    if needs_annotation or not ev_exact_map:
        annotate_matches_with_exclusions(submission_text, enriched)

    return enriched


def heal_report_exclusions(report_id: int) -> bool:
    """
    Memperkaya dan menyimpan exclusion_flags ke evidence_data di database untuk laporan
    historis maupun baru agar UI dan PDF membaca sumber kebenaran server yang sama.
    """
    conn = get_connection()
    try:
        cursor = conn.cursor(dictionary=True)
        cursor.execute("SELECT id, document_id, evidence_data FROM reports WHERE id = %s LIMIT 1", (report_id,))
        report = cursor.fetchone()
        if not report:
            return False

        ev_raw = report.get("evidence_data")
        ev_data = json.loads(ev_raw) if isinstance(ev_raw, str) and ev_raw.strip() else (ev_raw if isinstance(ev_raw, dict) else {})

        cursor.execute(
            "SELECT text FROM document_chunks WHERE document_id = %s ORDER BY chunk_index ASC, id ASC",
            (int(report["document_id"]),),
        )
        sub_chunks = cursor.fetchall()
        submission_text = "\n\n".join(c["text"] for c in sub_chunks if c.get("text"))

        cursor.execute(
            """
            SELECT source_document_id, submission_start, submission_end, word_count, match_type, similarity
            FROM report_matches
            WHERE report_id = %s
            ORDER BY submission_start ASC
            """,
            (report_id,),
        )
        db_matches = cursor.fetchall()

        ev_matches = ev_data.get("matches") if isinstance(ev_data.get("matches"), list) else []
        if ev_matches:
            annotate_matches_with_exclusions(submission_text, ev_matches)
            target_matches = ev_matches
        else:
            target_matches = merge_db_and_evidence_matches(db_matches, [], submission_text=submission_text)
            ev_data["matches"] = target_matches

        # Ringkas cakupan kategori pengecualian di metadata evidence_data
        ev_data["exclusion_schema_version"] = EXCLUSION_SCHEMA_VERSION
        ev_data["exclusion_summary"] = {
            "quote_matches": sum(1 for m in target_matches if get_match_exclusion_flags(m)["quote"]),
            "boilerplate_matches": sum(1 for m in target_matches if get_match_exclusion_flags(m)["boilerplate"]),
            "front_matter_matches": sum(1 for m in target_matches if get_match_exclusion_flags(m)["front_matter"]),
            "bibliography_matches": sum(1 for m in target_matches if get_match_exclusion_flags(m)["bibliography"]),
        }

        cursor.execute(
            "UPDATE reports SET evidence_data = %s, updated_at = NOW() WHERE id = %s",
            (json.dumps(ev_data, ensure_ascii=False), report_id),
        )
        conn.commit()
        cursor.close()
        return True
    finally:
        conn.close()


if __name__ == "__main__":
    parser = argparse.ArgumentParser(description="AR Tilik Server-Side Exclusion Filter Annotator")
    parser.add_argument("--heal-report", type=int, required=True, help="Perbarui exclusion_flags pada evidence_data laporan")
    args = parser.parse_args()
    ok = heal_report_exclusions(args.heal_report)
    if not ok:
        raise SystemExit(1)
