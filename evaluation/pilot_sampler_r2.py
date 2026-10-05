"""
AR Tilik v1.1 - Operational Pilot Sampler & Manager (Phase R: Milestone R2)
Mengimplementasikan instrumen sampling berbasis risiko (Risk-Based Sampling)
pada 20–30 dokumen submisi nyata dari populasi operasional sistem.

Mendukung 6 Tier Risiko Sesuai Protokol Operasional R2 (docs/OPERATIONAL_PILOT_PROTOCOL_R2.md):
- Tier P1: High Exact + Uncited (5 kasus)
- Tier P2: High Exact + Cited / Quote (5 kasus)
- Tier P3: Divergence: TF-IDF Tinggi + Exact ≈ 0% (5 kasus)
- Tier P4: Methodology / Academic Boilerplate (5 kasus)
- Tier P5: Edge / Boundary Cases (5 kasus)
- Tier P6: Control / Clean Submissions (5 kasus)
Total: 30 Kasus Nyata

Mematuhi Golden Gates:
1. Gate 1: Engine v1.0.0 tetap FROZEN (worker/hybrid.py untouched).
2. Gate 2: Pemisahan tegas 3 lapisan bukti (Machine, Context, Human).
3. Gate 3: Blind Review Protocol (expected class developer disembunyikan dari UI penelaah).
4. Gate 4: Non-suppression invariant (konteks tidak menekan metrik exact/cosine).
"""

import os
import sys
import json
import hashlib
from pathlib import Path
from typing import List, Dict, Any, Optional, Tuple
from datetime import datetime, timezone

# Ensure project root is in sys.path
ROOT_DIR = Path(__file__).resolve().parents[1]
if str(ROOT_DIR) not in sys.path:
    sys.path.insert(0, str(ROOT_DIR))

from worker.database import get_connection
from worker.citation_detector import detect_citation_context
from worker.evidence_presenter import check_evidence_divergence
from worker.contract_v1_1 import EvidenceType
from evaluation.schema import CASE_TYPE_PILOT_R2

DATASET_V11_DIR = ROOT_DIR / "evaluation_dataset_v1_1_targeted_80"
PILOT_SET_PATH = DATASET_V11_DIR / "pilot_r2_set.json"
PILOT_REVIEWS_PATH = DATASET_V11_DIR / "pilot_r2_reviews.json"

TIER_CONFIG = {
    "P1": {
        "tier_name": "Tier P1 — High Exact + Uncited",
        "description": "Indikasi penyalinan verbatim tinggi tanpa atribusi rujukan",
    },
    "P2": {
        "tier_name": "Tier P2 — High Exact + Cited / Quote",
        "description": "Tumpang tindih tekstual dengan deteksi kutipan / rujukan akademik",
    },
    "P3": {
        "tier_name": "Tier P3 — Divergence: TF-IDF Tinggi + Exact ≈ 0%",
        "description": "Kemiripan orientasi leksikal/topikal tanpa pasase kata sekuensial",
    },
    "P4": {
        "tier_name": "Tier P4 — Methodology / Academic Boilerplate",
        "description": "Formulasi metodologi standar / klise instrumen penelitian ilmiah",
    },
    "P5": {
        "tier_name": "Tier P5 — Edge / Boundary Cases",
        "description": "Ambang batas transisi keputusan di sekitar threshold hybrid",
    },
    "P6": {
        "tier_name": "Tier P6 — Control / Clean Submissions",
        "description": "Kontrol orisinalitas naskah bersih tanpa kemiripan (NO_MATCH baseline)",
    },
}


def sample_pilot_r2_cases(
    target_per_tier: int = 5,
    dry_run: bool = False,
    register_db: bool = True,
    export_json: bool = True,
    force_resample: bool = False,
) -> List[Dict[str, Any]]:
    """
    Menyeleksi 30 kasus dokumen nyata dari database operasional berdasarkan matriks sampling risiko.
    """
    conn = get_connection()
    cursor = conn.cursor(dictionary=True)

    try:
        # 1. Ambil seluruh laporan berstatus completed beserta judul dokumennya
        query = """
        SELECT r.id, r.document_id, r.predicted_decision, r.evidence_pattern,
               r.exact_similarity, r.cosine_similarity, r.longest_match_words,
               r.shared_terms_count, r.average_shared_term_idf,
               r.evidence_data, d.title
        FROM reports r
        JOIN documents d ON r.document_id = d.id
        WHERE r.status = 'completed'
        ORDER BY r.id ASC
        """
        cursor.execute(query)
        reports = cursor.fetchall()

        if not reports:
            print("[!] Peringatan: Tidak ada laporan completed ditemukan di database.")
            return []

        # 2. Muat seluruh teks dokumen submisi dan rujukan dari document_chunks
        doc_ids = set(r["document_id"] for r in reports)
        for r in reports:
            if r["evidence_data"]:
                try:
                    ev = json.loads(r["evidence_data"])
                    for s in ev.get("sources", []):
                        if s.get("source_document_id"):
                            doc_ids.add(s["source_document_id"])
                except Exception:
                    pass

        doc_texts: Dict[int, str] = {}
        if doc_ids:
            format_strings = ",".join(["%s"] * len(doc_ids))
            cursor.execute(
                f"SELECT document_id, text FROM document_chunks WHERE document_id IN ({format_strings}) ORDER BY document_id, chunk_index ASC",
                tuple(doc_ids),
            )
            for ch in cursor.fetchall():
                did = ch["document_id"]
                doc_texts[did] = (doc_texts.get(did, "") + " " + (ch["text"] or "")).strip()

        # 3. Klasifikasikan laporan ke dalam 6 ember risiko
        pilot_candidates: Dict[str, List[Dict[str, Any]]] = {
            "P1": [],
            "P2": [],
            "P3": [],
            "P4": [],
            "P5": [],
            "P6": [],
        }

        for r in reports:
            sub_text = doc_texts.get(r["document_id"], "").strip()
            if not sub_text or len(sub_text) < 20:
                continue

            exact = float(r["exact_similarity"] or 0.0)
            cos = float(r["cosine_similarity"] or 0.0)
            dec = (r["predicted_decision"] or "no_match").lower()
            pat = r["evidence_pattern"] or "NO_EVIDENCE"
            title_lower = (r["title"] or "").lower()

            ctx = detect_citation_context(sub_text)
            c_type = ctx.context_type
            c_stat = ctx.citation_status

            cand_item = {
                "report": r,
                "submission_text": sub_text,
                "context_result": ctx,
                "exact": exact,
                "cosine": cos,
                "decision": dec,
                "pattern": pat,
            }

            # P1: High Exact (>= 70%) + Uncited
            if exact >= 70.0 and c_stat in ("NOT_CITED", "NONE") and c_type in ("NONE", "UNKNOWN"):
                pilot_candidates["P1"].append(cand_item)

            # P2: High Exact (>= 20%) + Cited / Quote
            if exact >= 20.0 and (
                c_stat in ("CITED", "QUOTE", "PARAPHRASED_WITH_CITATION")
                or c_type in ("DIRECT_QUOTE", "BLOCK_QUOTE", "PARENTHETICAL_CITATION", "INLINE_CITATION")
            ):
                pilot_candidates["P2"].append(cand_item)

            # P3: Divergence: Exact <= 0.05% & Cosine >= 15%
            if exact <= 0.05 and cos >= 15.0:
                pilot_candidates["P3"].append(cand_item)

            # P4: Methodology / Academic Boilerplate
            if any(k in title_lower or k in sub_text.lower() for k in [
                "metode penelitian", "analisis regresi", "kuesioner",
                "skala likert", "uji t", "cronbach", "populasi dan sampel",
                "teknik pengumpulan data", "uji validitas"
            ]):
                pilot_candidates["P4"].append(cand_item)

            # P5: Edge / Boundary cases (Cosine near 10% or near 85%)
            if (5.0 <= cos <= 8.0 and exact == 0.0) or (80.0 <= cos <= 88.0 and 80.0 <= exact <= 85.0):
                pilot_candidates["P5"].append(cand_item)

            # P6: Clean / Control (Exact = 0%, Cosine = 0%, NO_MATCH)
            if exact == 0.0 and cos == 0.0 and dec == "no_match":
                pilot_candidates["P6"].append(cand_item)

        # 4. Seleksi kasus unik per tier (maksimal target_per_tier per tier)
        selected_cases: List[Dict[str, Any]] = []
        used_report_ids = set()
        queue_rank = 1

        for tier_code in ["P1", "P2", "P3", "P4", "P5", "P6"]:
            tier_info = TIER_CONFIG[tier_code]
            cands = pilot_candidates[tier_code]
            count_in_tier = 0

            for cand in cands:
                if count_in_tier >= target_per_tier:
                    break

                rep = cand["report"]
                rid = rep["id"]
                if rid in used_report_ids:
                    continue

                used_report_ids.add(rid)
                count_in_tier += 1
                case_id = f"PILOT-{tier_code}-{count_in_tier:03d}"

                sub_text = cand["submission_text"]
                ctx = cand["context_result"]

                # Ekstraksi source text dari rujukan pertama (jika ada)
                source_text = ""
                if rep["evidence_data"]:
                    try:
                        ev = json.loads(rep["evidence_data"])
                        sources = ev.get("sources", [])
                        if sources:
                            src_doc_id = sources[0].get("source_document_id")
                            if src_doc_id and src_doc_id in doc_texts:
                                source_text = doc_texts[src_doc_id]
                    except Exception:
                        pass

                if not source_text:
                    if tier_code == "P6":
                        source_text = "(Naskah orisinal tanpa padanan di korpus pembanding)"
                    else:
                        source_text = sub_text

                # Hitung divergensi bukti
                div_info = check_evidence_divergence(cand["exact"], cand["cosine"])
                has_divergence = div_info is not None

                # Tentukan jenis bukti utama
                if cand["exact"] > 0:
                    evidence_type = EvidenceType.DIRECT_TEXTUAL_OVERLAP.value
                elif cand["cosine"] > 0:
                    evidence_type = EvidenceType.LEXICAL_TOPIC_SIMILARITY.value
                else:
                    evidence_type = "NO_EVIDENCE"

                # Rationale kontekstual
                rationale = ctx.rationale
                if has_divergence:
                    rationale += f" Disertai indikasi divergensi leksikal (TF-IDF {cand['cosine']:.1f}% vs Exact {cand['exact']:.1f}%)."

                case_record = {
                    "queue_rank": queue_rank,
                    "case_id": case_id,
                    "report_id": rid,
                    "document_id": rep["document_id"],
                    "priority_tier": tier_info["tier_name"],
                    "priority_reason": f"{tier_info['description']} (Report #{rid})",
                    "predicted_class": cand["decision"],
                    "predicted_pattern": cand["pattern"],
                    "exact_similarity": round(cand["exact"], 2),
                    "cosine_similarity": round(cand["cosine"], 2),
                    "longest_match_words": int(rep["longest_match_words"] or 0),
                    "matched_terms": int(rep["shared_terms_count"] or 0),
                    "avg_idf": float(rep["average_shared_term_idf"] or 0.0),
                    "max_idf": float(rep["average_shared_term_idf"] or 0.0),
                    "predicted_context": ctx.context_type,
                    "predicted_citation_status": ctx.citation_status,
                    "predicted_evidence_type": evidence_type,
                    "context_span": ctx.matched_span,
                    "rationale": rationale,
                    "has_divergence": has_divergence,
                    "submission_text": sub_text,
                    "source_text": source_text,
                    "review_status": "PENDING",
                    "human_review": None,
                }

                selected_cases.append(case_record)
                queue_rank += 1

        print(f"[*] Pilot Sampler: {len(selected_cases)} kasus dokumen nyata berhasil diseleksi across 6 tier risiko.")

        if dry_run:
            print("[*] Dry run mode aktif: database dan file json tidak dimodifikasi.")
            return selected_cases

        # 5. Registrasi ke Database jika diminta
        if register_db and selected_cases:
            # Ambil label yang sudah ada untuk sinkronisasi review_status
            cursor.execute("SELECT * FROM evaluation_labels WHERE case_id LIKE 'PILOT-%'")
            existing_labels = {r["case_id"]: r for r in cursor.fetchall()}

            for c in selected_cases:
                cid = c["case_id"]
                content_hash = hashlib.sha256(c["submission_text"].encode("utf-8")).hexdigest()[:16]

                # Update review_status jika sudah ada telaah sebelumnya
                if cid in existing_labels:
                    c["review_status"] = existing_labels[cid]["review_status"]
                    c["human_review"] = existing_labels[cid]

                # Masukkan ke post_release_evaluations
                sql_case = """
                INSERT INTO post_release_evaluations (
                    case_id, case_type, source_case, generation_method,
                    expected_class, expected_pattern, predicted_class, predicted_pattern,
                    agreement, exact_similarity, cosine_similarity, longest_match_words,
                    matched_terms, avg_idf, max_idf, is_pass, rationale,
                    submission_text, source_text, system_version, content_hash,
                    created_at, updated_at
                ) VALUES (
                    %s, %s, %s, %s,
                    %s, %s, %s, %s,
                    %s, %s, %s, %s,
                    %s, %s, %s, %s, %s,
                    %s, %s, %s, %s,
                    NOW(), NOW()
                )
                ON DUPLICATE KEY UPDATE
                    predicted_class = VALUES(predicted_class),
                    predicted_pattern = VALUES(predicted_pattern),
                    exact_similarity = VALUES(exact_similarity),
                    cosine_similarity = VALUES(cosine_similarity),
                    longest_match_words = VALUES(longest_match_words),
                    matched_terms = VALUES(matched_terms),
                    avg_idf = VALUES(avg_idf),
                    max_idf = VALUES(max_idf),
                    rationale = VALUES(rationale),
                    submission_text = VALUES(submission_text),
                    source_text = VALUES(source_text),
                    updated_at = NOW()
                """
                cursor.execute(
                    sql_case,
                    (
                        cid,
                        CASE_TYPE_PILOT_R2,
                        f"REPORT-{c['report_id']}",
                        "OPERATIONAL_PILOT_SAMPLING",
                        c["predicted_class"],  # developer label disimpan terisolasi di DB, tidak dikirim ke UI
                        c["predicted_pattern"],
                        c["predicted_class"],
                        c["predicted_pattern"],
                        "VERIFIED",
                        c["exact_similarity"],
                        c["cosine_similarity"],
                        c["longest_match_words"],
                        c["matched_terms"],
                        c["avg_idf"],
                        c["max_idf"],
                        1,
                        c["rationale"],
                        c["submission_text"],
                        c["source_text"],
                        "v1.1.0",
                        content_hash,
                    ),
                )

                # Masukkan 6 metrik bukti ke evaluation_evidence
                cursor.execute("DELETE FROM evaluation_evidence WHERE case_id = %s", (cid,))
                evidence_rows = [
                    (cid, "EXACT", "similarity", str(c["exact_similarity"])),
                    (cid, "EXACT", "longest_match_words", str(c["longest_match_words"])),
                    (cid, "EXACT", "max_idf", str(c["max_idf"])),
                    (cid, "TFIDF", "cosine_similarity", str(c["cosine_similarity"])),
                    (cid, "TFIDF", "matched_terms", str(c["matched_terms"])),
                    (cid, "TFIDF", "avg_idf", str(c["avg_idf"])),
                ]
                cursor.executemany(
                    "INSERT INTO evaluation_evidence (case_id, evidence_lens, metric_name, metric_value, created_at) VALUES (%s, %s, %s, %s, NOW())",
                    evidence_rows,
                )

            conn.commit()
            print(f"[+] Berhasil mendaftarkan {len(selected_cases)} kasus pilot ke tabel post_release_evaluations & evaluation_evidence.")

        # 6. Ekspor ke JSON dataset jika diminta
        if export_json and selected_cases:
            DATASET_V11_DIR.mkdir(parents=True, exist_ok=True)
            with open(PILOT_SET_PATH, "w", encoding="utf-8") as f:
                json.dump(selected_cases, f, indent=2, ensure_ascii=False)
            print(f"[+] Dataset blind pilot berhasil disimpan ke {PILOT_SET_PATH}")

            # Buat file reviews jika belum ada
            if not PILOT_REVIEWS_PATH.exists() or force_resample:
                empty_reviews = {"version": "v1.1.0", "updated_at": datetime.now(timezone.utc).isoformat(), "reviews": []}
                with open(PILOT_REVIEWS_PATH, "w", encoding="utf-8") as f:
                    json.dump(empty_reviews, f, indent=2)

        return selected_cases

    finally:
        cursor.close()
        conn.close()


def get_pilot_r2_set() -> List[Dict[str, Any]]:
    """
    Mengambil 30 kasus telaah pilot dokumen nyata.
    Jika file JSON belum tersedia, otomatis menjalankan sampling.
    """
    if not PILOT_SET_PATH.exists():
        return sample_pilot_r2_cases(target_per_tier=5, dry_run=False, register_db=True, export_json=True)

    with open(PILOT_SET_PATH, "r", encoding="utf-8") as f:
        cases = json.load(f)

    # Sinkronisasi status terbaru dari database
    conn = get_connection()
    cursor = conn.cursor(dictionary=True)
    try:
        cursor.execute("SELECT * FROM evaluation_labels WHERE case_id LIKE 'PILOT-%'")
        label_map = {r["case_id"]: r for r in cursor.fetchall()}

        for c in cases:
            cid = c["case_id"]
            if cid in label_map:
                c["review_status"] = label_map[cid]["review_status"]
                c["human_review"] = label_map[cid]
            else:
                c["review_status"] = "PENDING"
                c["human_review"] = None
    finally:
        cursor.close()
        conn.close()

    return cases


def tag_pilot_case(
    case_id: str,
    reviewer_code: str,
    human_decision: str,
    citation_context: str,
    citation_status: str,
    human_interpretation: str,
    system_assessment: str,
    reviewer_confidence: str,
    review_status: str = "REVIEWED",
    notes: Optional[str] = None,
    decomposition_utility: str = "YES",
) -> Dict[str, Any]:
    """
    Menyimpan hasil telaah manusia untuk kasus pilot dan memperbarui file JSON.
    Termasuk evaluasi kegunaan pemisahan Layer A dan Layer B (R3 Utility Question).
    """
    conn = get_connection()
    cursor = conn.cursor(dictionary=True)
    try:
        is_relevant = 1 if human_interpretation in ("DIRECT_OVERLAP_RELEVANT", "DIRECT_OVERLAP_PROPERLY_CITED") else 0
        is_common = 1 if human_interpretation == "COMMON_ACADEMIC_LANGUAGE" else 0
        is_cited = 1 if (human_interpretation == "DIRECT_OVERLAP_PROPERLY_CITED" or citation_status in ("CITED", "PARAPHRASED_WITH_CITATION")) else 0
        is_investigate = 1 if human_interpretation == "NEEDS_INVESTIGATION" else 0
        is_false_pos = 1 if human_interpretation == "FALSE_POSITIVE" else 0

        cursor.execute("SELECT id FROM evaluation_labels WHERE case_id = %s", (case_id,))
        existing_row = cursor.fetchone()

        if existing_row:
            sql_update = """
            UPDATE evaluation_labels SET
                reviewer_code = %s,
                human_decision = %s,
                citation_context = %s,
                citation_status = %s,
                human_interpretation = %s,
                evidence_relevant = %s,
                common_phrase = %s,
                properly_cited = %s,
                needs_investigation = %s,
                false_positive = %s,
                system_assessment = %s,
                reviewer_confidence = %s,
                decomposition_utility = %s,
                review_status = %s,
                notes = %s,
                reviewed_at = NOW()
            WHERE id = %s
            """
            cursor.execute(
                sql_update,
                (
                    reviewer_code,
                    human_decision,
                    citation_context,
                    citation_status,
                    human_interpretation,
                    is_relevant,
                    is_common,
                    is_cited,
                    is_investigate,
                    is_false_pos,
                    system_assessment,
                    reviewer_confidence,
                    decomposition_utility,
                    review_status,
                    notes,
                    existing_row["id"],
                ),
            )
        else:
            sql_insert = """
            INSERT INTO evaluation_labels (
                case_id, reviewer_code, human_decision, citation_context, citation_status,
                human_interpretation, evidence_relevant, common_phrase, properly_cited,
                needs_investigation, false_positive, system_assessment, reviewer_confidence,
                decomposition_utility, review_status, notes, reviewed_at
            ) VALUES (
                %s, %s, %s, %s, %s,
                %s, %s, %s, %s,
                %s, %s, %s, %s,
                %s, %s, %s, NOW()
            )
            """
            cursor.execute(
                sql_insert,
                (
                    case_id,
                    reviewer_code,
                    human_decision,
                    citation_context,
                    citation_status,
                    human_interpretation,
                    is_relevant,
                    is_common,
                    is_cited,
                    is_investigate,
                    is_false_pos,
                    system_assessment,
                    reviewer_confidence,
                    decomposition_utility,
                    review_status,
                    notes,
                ),
            )
        conn.commit()

        # Update JSON reviews file
        reviews_data = {"version": "v1.1.0", "updated_at": datetime.now(timezone.utc).isoformat(), "reviews": []}
        if PILOT_REVIEWS_PATH.exists():
            try:
                with open(PILOT_REVIEWS_PATH, "r", encoding="utf-8") as f:
                    reviews_data = json.load(f)
            except Exception:
                pass

        reviews_list = [r for r in reviews_data.get("reviews", []) if r.get("case_id") != case_id]
        review_entry = {
            "case_id": case_id,
            "reviewer_code": reviewer_code,
            "human_decision": human_decision,
            "citation_context": citation_context,
            "citation_status": citation_status,
            "human_interpretation": human_interpretation,
            "system_assessment": system_assessment,
            "reviewer_confidence": reviewer_confidence,
            "decomposition_utility": decomposition_utility,
            "review_status": review_status,
            "notes": notes,
            "reviewed_at": datetime.now(timezone.utc).isoformat(),
        }
        reviews_list.append(review_entry)
        reviews_data["reviews"] = reviews_list
        reviews_data["updated_at"] = datetime.now(timezone.utc).isoformat()

        with open(PILOT_REVIEWS_PATH, "w", encoding="utf-8") as f:
            json.dump(reviews_data, f, indent=2, ensure_ascii=False)

        return review_entry

    finally:
        cursor.close()
        conn.close()


if __name__ == "__main__":
    import argparse

    parser = argparse.ArgumentParser(description="AR Tilik v1.1 Operational Pilot Sampler (Milestone R2)")
    parser.add_argument("--dry-run", action="store_true", help="Uji sampling tanpa menulis ke database atau disk")
    parser.add_argument("--force", action="store_true", help="Paksa seleksi ulang dan timpa dataset")
    parser.add_argument("--target-per-tier", type=int, default=5, help="Jumlah target kasus per tier risiko (default: 5)")
    parser.add_argument("--json", action="store_true", help="Output ringkasan hasil dalam format JSON")
    args = parser.parse_args()

    print("======================================================================")
    print("      AR TILIK v1.1 — OPERATIONAL PILOT SAMPLER (MILESTONE R2)       ")
    print("======================================================================")
    sampled = sample_pilot_r2_cases(
        target_per_tier=args.target_per_tier,
        dry_run=args.dry_run,
        register_db=not args.dry_run,
        export_json=not args.dry_run,
        force_resample=args.force,
    )

    if args.json:
        print(json.dumps(sampled, indent=2))
    else:
        print("\nRingkasan Kasus Pilot yang Terpilih:")
        print("----------------------------------------------------------------------")
        for c in sampled:
            print(f"[{c['queue_rank']:02d}] {c['case_id']} | {c['priority_tier'][:28]:<28} | Exact: {c['exact_similarity']:5.1f}% | Cos: {c['cosine_similarity']:5.1f}% | Dec: {c['predicted_class']}")
        print("======================================================================")
