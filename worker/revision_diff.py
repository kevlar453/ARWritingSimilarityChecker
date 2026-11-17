"""
AR Tilik - P2-1 Revision Diff Engine (v1 vs v2)
Membandingkan dua versi laporan kemiripan naskah (misalnya draf awal v1 vs revisi v2)
untuk menampilkan perkembangan skor, kata beririsan, perubahan sumber, serta status
per pasase: RESOLVED (Hilang/Selesai), IMPROVED (Berkurang/Direvisi),
UNCHANGED (Tetap Sama), EXPANDED (Bertambah), dan NEW (Baru Muncul).
"""

import json
import re
import sys
from pathlib import Path
from typing import Any, Dict, List, Optional, Sequence, Tuple

PROJECT_ROOT = Path(__file__).resolve().parents[1]
script_dir = str(Path(__file__).resolve().parent)
if script_dir in sys.path:
    sys.path.remove(script_dir)
if str(PROJECT_ROOT) not in sys.path:
    sys.path.insert(0, str(PROJECT_ROOT))

from worker.database import get_connection
from worker.exclusion_filters import (
    compute_filtered_score,
    count_union_words,
    merge_db_and_evidence_matches,
    tokenize_with_spans,
)

REVISION_DIFF_SCHEMA_VERSION = 1


def _tokenize_simple(text: str) -> List[str]:
    if not text:
        return []
    return re.findall(r"[a-z0-9]+", text.lower())


def _dedup_representative_passages(matches: Sequence[Dict[str, Any]]) -> List[Dict[str, Any]]:
    """
    Menyederhanakan daftar match beririsan menjadi unit pasase representatif per rentang
    (submission_start, submission_end) agar satu kalimat yang sama tidak dihitung ganda
    ketika cocok terhadap beberapa dokumen rujukan sekaligus.
    """
    by_span: Dict[Tuple[int, int], Dict[str, Any]] = {}
    for m in matches or []:
        try:
            s_start = int(m.get("submission_start") or 0)
            s_end = int(m.get("submission_end") or 0)
            wc = int(m.get("word_count") or max(0, s_end - s_start + 1))
        except (TypeError, ValueError):
            continue
        if wc <= 0 or s_end < s_start:
            continue

        key = (s_start, s_end)
        text = str(m.get("submission_snippet") or m.get("passage_text") or "").strip()
        sig = float(m.get("significance") or wc)
        existing = by_span.get(key)
        if existing is None or (len(text), sig) > (len(existing.get("_text", "")), float(existing.get("_sig", 0.0))):
            item = dict(m)
            item["submission_start"] = s_start
            item["submission_end"] = s_end
            item["word_count"] = wc
            item["_text"] = text
            item["_tokens"] = _tokenize_simple(text)
            item["_sig"] = sig
            by_span[key] = item

    ordered = sorted(by_span.values(), key=lambda x: (x["submission_start"], -x["word_count"]))
    return ordered


def _pair_similarity(p: Dict[str, Any], c: Dict[str, Any]) -> Tuple[float, float, float, int]:
    """
    Mengembalikan (combined_score, jaccard, containment, shared_unique_tokens).
    """
    tp = set(p.get("_tokens") or [])
    tc = set(c.get("_tokens") or [])
    if not tp or not tc:
        return 0.0, 0.0, 0.0, 0

    inter = len(tp & tc)
    if inter < 3:
        return 0.0, 0.0, 0.0, inter

    union = len(tp | tc)
    jaccard = inter / union if union > 0 else 0.0
    containment = inter / min(len(tp), len(tc))
    if jaccard < 0.25 and containment < 0.55:
        return 0.0, jaccard, containment, inter

    same_source = (
        p.get("source_document_id") is not None
        and c.get("source_document_id") is not None
        and int(p["source_document_id"]) == int(c["source_document_id"])
    )
    score = 0.5 * jaccard + 0.5 * containment + (0.12 if same_source else 0.0)
    return score, jaccard, containment, inter


def _extract_snippet_preview(m: Dict[str, Any], limit: int = 240) -> str:
    txt = str(m.get("submission_snippet") or m.get("passage_text") or "").strip()
    txt = re.sub(r"\s+", " ", txt)
    if len(txt) > limit:
        return txt[: limit - 3].rstrip() + "..."
    return txt


def compute_revision_diff(
    curr_report: Dict[str, Any],
    prev_report: Dict[str, Any],
    curr_matches: Sequence[Dict[str, Any]],
    prev_matches: Sequence[Dict[str, Any]],
    *,
    curr_doc: Optional[Dict[str, Any]] = None,
    prev_doc: Optional[Dict[str, Any]] = None,
    source_titles: Optional[Dict[int, str]] = None,
) -> Dict[str, Any]:
    """
    Menghitung diff terstruktur antara laporan sebelumnya (v1) dan laporan terkini (v2).
    """
    titles_map = source_titles or {}

    prev_raw = float(prev_report.get("overall_similarity") or 0.0)
    curr_raw = float(curr_report.get("overall_similarity") or 0.0)

    prev_ev = prev_report.get("evidence_data")
    if isinstance(prev_ev, str):
        try:
            prev_ev = json.loads(prev_ev)
        except Exception:
            prev_ev = {}
    if not isinstance(prev_ev, dict):
        prev_ev = {}

    curr_ev = curr_report.get("evidence_data")
    if isinstance(curr_ev, str):
        try:
            curr_ev = json.loads(curr_ev)
        except Exception:
            curr_ev = {}
    if not isinstance(curr_ev, dict):
        curr_ev = {}

    prev_sub_sim = (
        prev_ev.get("v1_1_presentation", {})
        .get("v1_2_evolution", {})
        .get("non_boilerplate_metrics", {})
        .get("substantive_similarity")
    )
    curr_sub_sim = (
        curr_ev.get("v1_1_presentation", {})
        .get("v1_2_evolution", {})
        .get("non_boilerplate_metrics", {})
        .get("substantive_similarity")
    )

    prev_qb = compute_filtered_score(
        prev_raw, prev_matches, exclude_quotes=True, exclude_bibliography=True
    )["filtered_similarity"]
    curr_qb = compute_filtered_score(
        curr_raw, curr_matches, exclude_quotes=True, exclude_bibliography=True
    )["filtered_similarity"]

    prev_union_words = count_union_words(
        [(int(m["submission_start"]), int(m["submission_end"])) for m in prev_matches if m.get("submission_end", -1) >= m.get("submission_start", 0)]
    )
    curr_union_words = count_union_words(
        [(int(m["submission_start"]), int(m["submission_end"])) for m in curr_matches if m.get("submission_end", -1) >= m.get("submission_start", 0)]
    )

    prev_items = _dedup_representative_passages(prev_matches)
    curr_items = _dedup_representative_passages(curr_matches)

    # Greedy optimal pairing antara pasase v1 dan pasase v2
    candidates: List[Tuple[float, int, int, float]] = []
    for pi, p in enumerate(prev_items):
        for ci, c in enumerate(curr_items):
            score, jacc, _cont, _inter = _pair_similarity(p, c)
            if score > 0.0:
                candidates.append((score, pi, ci, jacc))
    candidates.sort(key=lambda x: (-x[0], abs(prev_items[x[1]]["word_count"] - curr_items[x[2]]["word_count"])))

    matched_prev: Dict[int, Tuple[int, float]] = {}
    matched_curr: Dict[int, int] = {}
    for score, pi, ci, jacc in candidates:
        if pi in matched_prev or ci in matched_curr:
            continue
        matched_prev[pi] = (ci, jacc)
        matched_curr[ci] = pi

    passage_changes: List[Dict[str, Any]] = []
    counts = {
        "resolved": 0,
        "improved": 0,
        "unchanged": 0,
        "expanded": 0,
        "new": 0,
    }

    def _resolve_source_title(item: Optional[Dict[str, Any]]) -> str:
        if not item:
            return "-"
        sid = item.get("source_document_id")
        if item.get("source_title"):
            return str(item["source_title"])
        if sid is not None and int(sid) in titles_map:
            return titles_map[int(sid)]
        return f"Rujukan #{sid}" if sid is not None else "Korpus Rujukan"

    for pi, p in enumerate(prev_items):
        wc_p = int(p["word_count"])
        if pi not in matched_prev:
            counts["resolved"] += 1
            passage_changes.append({
                "status": "RESOLVED",
                "prev_word_count": wc_p,
                "curr_word_count": 0,
                "delta_words": -wc_p,
                "prev_start": p["submission_start"],
                "prev_end": p["submission_end"],
                "curr_start": None,
                "curr_end": None,
                "source_document_id": p.get("source_document_id"),
                "source_title": _resolve_source_title(p),
                "prev_snippet": _extract_snippet_preview(p),
                "curr_snippet": "",
                "section_name": p.get("section_name") or "",
            })
        else:
            ci, jacc = matched_prev[pi]
            c = curr_items[ci]
            wc_c = int(c["word_count"])
            dw = wc_c - wc_p
            same_len_tol = max(2, int(0.08 * wc_p))
            if jacc >= 0.85 and abs(dw) <= same_len_tol:
                st = "UNCHANGED"
                counts["unchanged"] += 1
            elif wc_c <= wc_p and (dw < 0 or jacc < 0.85):
                st = "IMPROVED"
                counts["improved"] += 1
            else:
                st = "EXPANDED"
                counts["expanded"] += 1

            passage_changes.append({
                "status": st,
                "prev_word_count": wc_p,
                "curr_word_count": wc_c,
                "delta_words": dw,
                "prev_start": p["submission_start"],
                "prev_end": p["submission_end"],
                "curr_start": c["submission_start"],
                "curr_end": c["submission_end"],
                "source_document_id": c.get("source_document_id") or p.get("source_document_id"),
                "source_title": _resolve_source_title(c),
                "prev_snippet": _extract_snippet_preview(p),
                "curr_snippet": _extract_snippet_preview(c),
                "section_name": c.get("section_name") or p.get("section_name") or "",
            })

    for ci, c in enumerate(curr_items):
        if ci not in matched_curr:
            wc_c = int(c["word_count"])
            counts["new"] += 1
            passage_changes.append({
                "status": "NEW",
                "prev_word_count": 0,
                "curr_word_count": wc_c,
                "delta_words": wc_c,
                "prev_start": None,
                "prev_end": None,
                "curr_start": c["submission_start"],
                "curr_end": c["submission_end"],
                "source_document_id": c.get("source_document_id"),
                "source_title": _resolve_source_title(c),
                "prev_snippet": "",
                "curr_snippet": _extract_snippet_preview(c),
                "section_name": c.get("section_name") or "",
            })

    # Urutkan agar RESOLVED, IMPROVED, NEW, EXPANDED muncul lebih dulu, diikuti UNCHANGED
    status_priority = {"RESOLVED": 0, "IMPROVED": 1, "NEW": 2, "EXPANDED": 3, "UNCHANGED": 4}
    passage_changes.sort(
        key=lambda x: (
            status_priority.get(x["status"], 9),
            -abs(int(x["delta_words"])),
            -(int(x["prev_word_count"]) + int(x["curr_word_count"])),
        )
    )

    total_prev_passages = len(prev_items)
    improved_or_resolved = counts["resolved"] + counts["improved"]
    resolution_rate_pct = (
        round((improved_or_resolved / total_prev_passages) * 100.0, 1)
        if total_prev_passages > 0
        else 0.0
    )

    # Ringkasan perubahan sumber rujukan
    prev_by_src: Dict[int, int] = {}
    curr_by_src: Dict[int, int] = {}
    for m in prev_items:
        if m.get("source_document_id") is not None:
            sid = int(m["source_document_id"])
            prev_by_src[sid] = prev_by_src.get(sid, 0) + int(m["word_count"])
    for m in curr_items:
        if m.get("source_document_id") is not None:
            sid = int(m["source_document_id"])
            curr_by_src[sid] = curr_by_src.get(sid, 0) + int(m["word_count"])

    source_changes = []
    for sid in sorted(set(prev_by_src.keys()) | set(curr_by_src.keys())):
        pw = prev_by_src.get(sid, 0)
        cw = curr_by_src.get(sid, 0)
        if pw > 0 and cw == 0:
            sst = "RESOLVED"
        elif pw == 0 and cw > 0:
            sst = "NEW"
        elif cw < pw:
            sst = "IMPROVED"
        elif cw > pw:
            sst = "EXPANDED"
        else:
            sst = "UNCHANGED"
        source_changes.append({
            "source_document_id": sid,
            "source_title": titles_map.get(sid, f"Rujukan #{sid}"),
            "prev_words": pw,
            "curr_words": cw,
            "delta_words": cw - pw,
            "status": sst,
        })
    source_changes.sort(key=lambda x: (-abs(x["delta_words"]), -max(x["prev_words"], x["curr_words"])))

    prev_rev_num = int((prev_doc or {}).get("revision_number") or 1)
    curr_rev_num = int((curr_doc or {}).get("revision_number") or (prev_rev_num + 1))

    return {
        "schema_version": REVISION_DIFF_SCHEMA_VERSION,
        "prev_report_id": int(prev_report["id"]),
        "curr_report_id": int(curr_report["id"]),
        "prev_document_id": int(prev_report.get("document_id") or (prev_doc or {}).get("id") or 0),
        "curr_document_id": int(curr_report.get("document_id") or (curr_doc or {}).get("id") or 0),
        "prev_revision_number": prev_rev_num,
        "curr_revision_number": curr_rev_num,
        "prev_title": (prev_doc or {}).get("title") or "",
        "curr_title": (curr_doc or {}).get("title") or "",
        "curr_revision_note": (curr_doc or {}).get("revision_note") or "",
        "metrics": {
            "prev_overall_similarity": round(prev_raw, 2),
            "curr_overall_similarity": round(curr_raw, 2),
            "delta_overall_similarity": round(curr_raw - prev_raw, 2),
            "prev_substantive_similarity": round(float(prev_sub_sim), 2) if prev_sub_sim is not None else None,
            "curr_substantive_similarity": round(float(curr_sub_sim), 2) if curr_sub_sim is not None else None,
            "delta_substantive_similarity": (
                round(float(curr_sub_sim) - float(prev_sub_sim), 2)
                if prev_sub_sim is not None and curr_sub_sim is not None
                else None
            ),
            "prev_filtered_quotes_bib_similarity": round(prev_qb, 2),
            "curr_filtered_quotes_bib_similarity": round(curr_qb, 2),
            "delta_filtered_quotes_bib_similarity": round(curr_qb - prev_qb, 2),
            "prev_matched_words": int(prev_union_words),
            "curr_matched_words": int(curr_union_words),
            "delta_matched_words": int(curr_union_words - prev_union_words),
            "prev_total_words": int(prev_ev.get("total_words") or 0),
            "curr_total_words": int(curr_ev.get("total_words") or 0),
            "prev_passages_count": total_prev_passages,
            "curr_passages_count": len(curr_items),
            "resolution_rate_pct": resolution_rate_pct,
        },
        "status_counts": counts,
        "source_changes": source_changes[:15],
        "passage_changes": passage_changes[:60],
        "total_passage_changes": len(passage_changes),
    }


def _fill_match_snippets(connection, document_id: int, matches: List[Dict[str, Any]]) -> None:
    if not matches:
        return
    needs_fill = any(not (m.get("submission_snippet") or m.get("passage_text")) for m in matches)
    if not needs_fill:
        return

    cur = connection.cursor(dictionary=True)
    cur.execute(
        "SELECT text FROM document_chunks WHERE document_id = %s ORDER BY chunk_index ASC",
        (document_id,),
    )
    chunks = cur.fetchall()
    cur.close()
    full_text = " ".join(c["text"] for c in chunks if c.get("text"))
    if not full_text.strip():
        return

    spans = tokenize_with_spans(full_text)
    if not spans:
        return

    for m in matches:
        if m.get("submission_snippet") or m.get("passage_text"):
            continue
        try:
            s_idx = max(0, int(m.get("submission_start") or 0))
            e_idx = min(len(spans) - 1, int(m.get("submission_end") or s_idx))
        except (TypeError, ValueError):
            continue
        if s_idx < len(spans) and e_idx >= s_idx:
            c0 = spans[s_idx][0]
            c1 = spans[e_idx][1]
            m["submission_snippet"] = full_text[c0:c1]


def get_revision_chain(connection, document_id: int) -> List[Dict[str, Any]]:
    """
    Mengembalikan daftar kronologis seluruh versi dokumen pada rantai revisi yang sama.
    """
    cur = connection.cursor(dictionary=True)
    cur.execute(
        "SELECT id, parent_document_id, revision_root_id, revision_number, revision_note, title, original_filename, created_at FROM documents WHERE id = %s",
        (document_id,),
    )
    doc = cur.fetchone()
    if not doc:
        cur.close()
        return []

    root_id = int(doc.get("revision_root_id") or doc["id"])
    # Walk backward if revision_root_id wasn't populated yet
    if not doc.get("revision_root_id") and doc.get("parent_document_id"):
        seen = {int(doc["id"])}
        curr_parent = doc.get("parent_document_id")
        while curr_parent and int(curr_parent) not in seen:
            seen.add(int(curr_parent))
            cur.execute("SELECT id, parent_document_id, revision_root_id FROM documents WHERE id = %s", (curr_parent,))
            prow = cur.fetchone()
            if not prow:
                break
            root_id = int(prow.get("revision_root_id") or prow["id"])
            curr_parent = prow.get("parent_document_id")

    cur.execute(
        """
        SELECT d.id AS document_id, d.parent_document_id, d.revision_root_id,
               d.revision_number, d.revision_note, d.title, d.original_filename, d.created_at,
               r.id AS latest_report_id, r.status AS report_status, r.overall_similarity,
               r.predicted_decision, r.verification_code, r.created_at AS report_created_at
        FROM documents d
        LEFT JOIN (
            SELECT document_id, MAX(id) AS max_id FROM reports GROUP BY document_id
        ) lr ON lr.document_id = d.id
        LEFT JOIN reports r ON r.id = lr.max_id
        WHERE d.id = %s OR d.revision_root_id = %s OR d.parent_document_id = %s
        ORDER BY COALESCE(d.revision_number, 1) ASC, d.id ASC
        """,
        (root_id, root_id, root_id),
    )
    rows = cur.fetchall()
    cur.close()

    # Dedup and ensure clean ordering
    out: List[Dict[str, Any]] = []
    seen_ids = set()
    for row in rows:
        did = int(row["document_id"])
        if did in seen_ids:
            continue
        seen_ids.add(did)
        out.append({
            "document_id": did,
            "parent_document_id": int(row["parent_document_id"]) if row.get("parent_document_id") else None,
            "revision_number": int(row.get("revision_number") or 1),
            "revision_note": row.get("revision_note") or "",
            "title": row.get("title") or "",
            "original_filename": row.get("original_filename") or "",
            "latest_report_id": int(row["latest_report_id"]) if row.get("latest_report_id") else None,
            "report_status": row.get("report_status") or "",
            "overall_similarity": float(row["overall_similarity"]) if row.get("overall_similarity") is not None else None,
            "predicted_decision": row.get("predicted_decision") or "",
            "verification_code": row.get("verification_code") or "",
            "created_at": str(row.get("created_at") or ""),
        })
    return out


def build_revision_diff_for_report(
    connection,
    curr_report_id: int,
    compare_report_id: Optional[int] = None,
) -> Optional[Dict[str, Any]]:
    """
    Memuat laporan target dan laporan induk/pembanding dari DB lalu menghitung diff revisi.
    """
    cur = connection.cursor(dictionary=True)
    cur.execute("SELECT * FROM reports WHERE id = %s", (curr_report_id,))
    curr_rep = cur.fetchone()
    if not curr_rep:
        cur.close()
        return None

    cur.execute("SELECT * FROM documents WHERE id = %s", (curr_rep["document_id"],))
    curr_doc = cur.fetchone()
    if not curr_doc:
        cur.close()
        return None

    prev_rep = None
    prev_doc = None
    if compare_report_id:
        cur.execute("SELECT * FROM reports WHERE id = %s", (compare_report_id,))
        prev_rep = cur.fetchone()
        if prev_rep:
            cur.execute("SELECT * FROM documents WHERE id = %s", (prev_rep["document_id"],))
            prev_doc = cur.fetchone()
    elif curr_doc.get("parent_document_id"):
        parent_doc_id = int(curr_doc["parent_document_id"])
        cur.execute("SELECT * FROM documents WHERE id = %s", (parent_doc_id,))
        prev_doc = cur.fetchone()
        if prev_doc:
            cur.execute(
                "SELECT * FROM reports WHERE document_id = %s AND status = 'completed' ORDER BY id DESC LIMIT 1",
                (parent_doc_id,),
            )
            prev_rep = cur.fetchone()
    else:
        # Cek apakah ini laporan ulang (re-check) pada dokumen yang sama? Tidak, atau cek child jika v1 dibuka
        cur.execute(
            """
            SELECT r.* FROM reports r
            WHERE r.document_id = %s AND r.id < %s AND r.status = 'completed'
            ORDER BY r.id DESC LIMIT 1
            """,
            (curr_rep["document_id"], curr_report_id),
        )
        # Hanya bandingkan terhadap dokumen induk secara default (jika ada parent_document_id)
        pass

    if not prev_rep or prev_rep.get("status") != "completed" or curr_rep.get("status") != "completed":
        cur.close()
        return None

    def _load_matches(rep_row: Dict[str, Any]) -> List[Dict[str, Any]]:
        rep_id = int(rep_row["id"])
        doc_id = int(rep_row["document_id"])
        cur.execute(
            """
            SELECT rm.*, d.title AS source_title
            FROM report_matches rm
            LEFT JOIN documents d ON d.id = rm.source_document_id
            WHERE rm.report_id = %s
            ORDER BY rm.submission_start ASC
            """,
            (rep_id,),
        )
        db_m = cur.fetchall()
        ev_raw = rep_row.get("evidence_data")
        ev = json.loads(ev_raw) if isinstance(ev_raw, str) and ev_raw else (ev_raw if isinstance(ev_raw, dict) else {})
        ev_m = ev.get("matches", []) if isinstance(ev, dict) else []
        merged = merge_db_and_evidence_matches(db_m, ev_m)
        _fill_match_snippets(connection, doc_id, merged)
        return merged

    curr_matches = _load_matches(curr_rep)
    prev_matches = _load_matches(prev_rep)

    src_ids = {
        int(m["source_document_id"])
        for m in (curr_matches + prev_matches)
        if m.get("source_document_id") is not None
    }
    source_titles: Dict[int, str] = {}
    if src_ids:
        placeholders = ",".join(["%s"] * len(src_ids))
        cur.execute(f"SELECT id, title FROM documents WHERE id IN ({placeholders})", tuple(src_ids))
        for row in cur.fetchall():
            source_titles[int(row["id"])] = str(row["title"] or f"Rujukan #{row['id']}")

    cur.close()

    return compute_revision_diff(
        curr_rep,
        prev_rep,
        curr_matches,
        prev_matches,
        curr_doc=curr_doc,
        prev_doc=prev_doc,
        source_titles=source_titles,
    )


if __name__ == "__main__":
    import argparse

    parser = argparse.ArgumentParser(description="AR Tilik Revision Diff Calculator (v1 vs v2)")
    parser.add_argument("--report", type=int, required=True, help="ID Laporan target (v2)")
    parser.add_argument("--compare-with", type=int, default=None, help="ID Laporan pembanding (opsional, default laporan induk)")
    parser.add_argument("--json", action="store_true", help="Output JSON terstruktur")
    args = parser.parse_args()

    conn = get_connection()
    try:
        diff = build_revision_diff_for_report(conn, args.report, args.compare_with)
        chain = []
        cur = conn.cursor(dictionary=True)
        cur.execute("SELECT document_id FROM reports WHERE id = %s", (args.report,))
        rrow = cur.fetchone()
        cur.close()
        if rrow:
            chain = get_revision_chain(conn, int(rrow["document_id"]))

        payload = {"diff": diff, "chain": chain}
        if args.json:
            print(json.dumps(payload, ensure_ascii=False))
        else:
            if not diff:
                print(f"Tidak ada pembanding revisi yang tersedia untuk Report #{args.report}.")
            else:
                m = diff["metrics"]
                print(f"=== REVISION DIFF: v{diff['prev_revision_number']} (#{diff['prev_report_id']}) vs v{diff['curr_revision_number']} (#{diff['curr_report_id']}) ===")
                print(f"Skor Mentah     : {m['prev_overall_similarity']:.2f}% -> {m['curr_overall_similarity']:.2f}% (Delta: {m['delta_overall_similarity']:+.2f}%)")
                print(f"Kata Cocok Unik : {m['prev_matched_words']} -> {m['curr_matched_words']} (Delta: {m['delta_matched_words']:+d} kata)")
                print(f"Status Pasase   : {diff['status_counts']}")
    finally:
        conn.close()
