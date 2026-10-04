"""
AR Tilik - Human Review Queue & Annotation System
Menyeleksi 30-50 kasus paling informatif untuk validasi manusia:
1. Unexpected MATCH
2. Unexpected NO_MATCH
3. Boundary cases
4. High Exact + citation
5. High TF-IDF + Exact ≈ 0
6. Low Exact + high TF-IDF
7. Pattern disagreement
8. Random sample

Mendukung pelabelan manusia:
- [Relevant]
- [Common phrase]
- [Properly cited]
- [Needs investigation]
- [False positive]
Serta penilaian sistem: CORRECT, ACCEPTABLE, TOO_STRICT, TOO_LENIENT, UNCERTAIN.
"""

import json
import os
import sys
import random
from pathlib import Path
from typing import List, Dict, Any, Optional, Tuple
from collections import Counter

from benchmark.database import get_connection
from evaluation.schema import (
    HumanReviewLabel,
    SYSTEM_ASSESSMENT_CORRECT,
    SYSTEM_ASSESSMENT_ACCEPTABLE,
    SYSTEM_ASSESSMENT_TOO_STRICT,
    SYSTEM_ASSESSMENT_TOO_LENIENT,
    SYSTEM_ASSESSMENT_UNCERTAIN,
    CITATION_CONTEXT_NONE,
    CITATION_CONTEXT_INLINE,
    CITATION_CONTEXT_PARENTHETICAL,
    CITATION_CONTEXT_BLOCK_QUOTE,
    CITATION_CONTEXT_DIRECT_QUOTE,
    CITATION_CONTEXT_FOOTNOTE,
    CITATION_CONTEXT_BIBLIOGRAPHY,
    CITATION_CONTEXT_UNKNOWN,
    HUMAN_INTERPRETATION_DIRECT_OVERLAP_RELEVANT,
    HUMAN_INTERPRETATION_DIRECT_OVERLAP_PROPERLY_CITED,
    HUMAN_INTERPRETATION_COMMON_ACADEMIC_LANGUAGE,
    HUMAN_INTERPRETATION_METHODOLOGY_BOILERPLATE,
    HUMAN_INTERPRETATION_NEEDS_INVESTIGATION,
    HUMAN_INTERPRETATION_FALSE_POSITIVE,
    HUMAN_INTERPRETATION_INSUFFICIENT_EVIDENCE,
    CITATION_STATUS_NOT_CITED,
    CITATION_STATUS_CITED,
    CITATION_STATUS_PARTIALLY_CITED,
    CITATION_STATUS_QUOTE,
    CITATION_STATUS_PARAPHRASED_WITH_CITATION,
    CITATION_STATUS_NOT_APPLICABLE,
    CITATION_STATUS_UNKNOWN,
    CONFIDENCE_HIGH,
    CONFIDENCE_MEDIUM,
    CONFIDENCE_LOW,
    LIFECYCLE_PENDING,
    LIFECYCLE_UNDER_REVIEW,
    LIFECYCLE_REVIEWED,
    LIFECYCLE_VALIDATED
)
from evaluation.db import save_human_label

DATASET_DIR = Path(__file__).resolve().parents[1] / "evaluation_dataset_v1"

def load_evaluation_data() -> Tuple[List[Dict[str, Any]], List[Dict[str, Any]]]:
    cases_file = DATASET_DIR / "cases.json"
    results_file = DATASET_DIR / "results.json"
    
    if not cases_file.exists() or not results_file.exists():
        raise FileNotFoundError(
            "Berkas cases.json atau results.json belum ditemukan. "
            "Jalankan './artilik eval generate' dan './artilik eval run' terlebih dahulu."
        )
        
    with open(cases_file, "r", encoding="utf-8") as f:
        cases = json.load(f)
        
    with open(results_file, "r", encoding="utf-8") as f:
        results = json.load(f)
        
    return cases, results

def select_informative_cases(cases: List[Dict[str, Any]], results: List[Dict[str, Any]], max_cases: int = 40) -> List[Dict[str, Any]]:
    """
    Menyusun antrean telaah manusia berbasis 8 tingkat prioritas diagnostik:
    1. Unexpected MATCH
    2. Unexpected NO_MATCH
    3. Boundary cases
    4. High Exact + citation
    5. High TF-IDF + Exact ≈ 0
    6. Low Exact + high TF-IDF
    7. Pattern disagreement
    8. Random sample
    """
    case_map = {c["case_id"]: c for c in cases}
    
    p1_unexpected_match = []
    p2_unexpected_no_match = []
    p3_boundary = []
    p4_citation = []
    p5_high_tfidf_zero_exact = []
    p6_low_exact_high_tfidf = []
    p7_disagreement = []
    p8_sample = []
    
    seen_ids = set()

    for r in results:
        cid = r["case_id"]
        c = case_map.get(cid, {})
        pred = r["predicted_class"]
        exp = c.get("expected_class")
        esim = r["exact_similarity"]
        tsim = r["cosine_similarity"]
        method = c.get("generation_method", "")
        agreement = r.get("agreement", "")

        # 1. Unexpected MATCH
        if pred == "match" and exp != "match" and cid not in seen_ids:
            p1_unexpected_match.append((r, c, "Unexpected MATCH"))
            seen_ids.add(cid)
            continue

        # 2. Unexpected NO_MATCH
        if pred == "no_match" and exp in ("review", "match") and cid not in seen_ids:
            p2_unexpected_no_match.append((r, c, "Unexpected NO_MATCH"))
            seen_ids.add(cid)
            continue

        # 4. High Exact + Citation / Quote
        if method in ("CITATION_PROTECTED", "EXACT_QUOTE") and cid not in seen_ids:
            p4_citation.append((r, c, "Citation / Quote Overlap"))
            seen_ids.add(cid)
            continue

        # 5. High TF-IDF + Exact == 0
        if tsim >= 35.0 and esim == 0.0 and cid not in seen_ids:
            p5_high_tfidf_zero_exact.append((r, c, "High TF-IDF + Zero Exact"))
            seen_ids.add(cid)
            continue

        # 6. Low Exact (< 10) + High TF-IDF (> 40)
        if esim < 10.0 and tsim >= 40.0 and cid not in seen_ids:
            p6_low_exact_high_tfidf.append((r, c, "Low Exact + High TF-IDF"))
            seen_ids.add(cid)
            continue

        # 7. Pattern disagreement / divergence
        if agreement in ("SIGNIFICANCE_DIVERGENCE", "LOCALIZED_EXACT_EVIDENCE") and cid not in seen_ids:
            p7_disagreement.append((r, c, "Evidence Lens Divergence"))
            seen_ids.add(cid)
            continue

        # 3. Boundary cases
        if c.get("case_type") == "BOUNDARY" and cid not in seen_ids:
            p3_boundary.append((r, c, "Threshold Boundary Case"))
            seen_ids.add(cid)
            continue

        # 8. Remaining sample pool
        if cid not in seen_ids:
            p8_sample.append((r, c, "Representative Sample"))

    # Gabungkan berdasarkan kuota prioritas
    queue = []
    queue.extend(p1_unexpected_match)
    queue.extend(p2_unexpected_no_match)
    queue.extend(p4_citation[:10])
    queue.extend(p5_high_tfidf_zero_exact[:8])
    queue.extend(p6_low_exact_high_tfidf[:6])
    queue.extend(p7_disagreement[:6])
    queue.extend(p3_boundary[:10])
    
    # Tambahkan random sample jika belum memenuhi kuota max_cases
    remaining = max_cases - len(queue)
    if remaining > 0 and p8_sample:
        rng = random.Random(42)
        sample_pick = rng.sample(p8_sample, min(remaining, len(p8_sample)))
        queue.extend(sample_pick)

    # Susun payload informatif
    informative_items = []
    for rank, (r, c, priority_label) in enumerate(queue[:max_cases], start=1):
        informative_items.append({
            "queue_rank": rank,
            "priority_reason": priority_label,
            "case_id": r["case_id"],
            "case_type": r["case_type"],
            "generation_method": c.get("generation_method"),
            "expected_class": c.get("expected_class"),
            "expected_pattern": c.get("expected_pattern"),
            "predicted_class": r["predicted_class"],
            "predicted_pattern": r["predicted_pattern"],
            "agreement": r["agreement"],
            "exact_similarity": r["exact_similarity"],
            "cosine_similarity": r["cosine_similarity"],
            "longest_match_words": r["longest_match_words"],
            "avg_idf": r["avg_idf"],
            "max_idf": r["max_idf"],
            "rationale": r["rationale"],
            "submission_snippet": c.get("submission_text", "")[:280] + ("..." if len(c.get("submission_text", "")) > 280 else "")
        })

    return informative_items

def save_queue_to_file(queue: List[Dict[str, Any]]) -> Path:
    out_file = DATASET_DIR / "human_review_queue.json"
    with open(out_file, "w", encoding="utf-8") as f:
        json.dump(queue, f, indent=2, ensure_ascii=False)
    return out_file

def display_review_queue(queue: List[Dict[str, Any]]):
    print("\n" + "=" * 70)
    print("         AR TILIK - PRIORITIZED HUMAN REVIEW QUEUE          ")
    print("=" * 70)
    print(f"Total Kasus Informatif Terpilih: {len(queue)} kasus")
    print("-" * 70)
    print(f"{'No':<3} | {'Case ID':<10} | {'Priority':<24} | {'Exact':<6} | {'Cosine':<6} | {'Decision':<8}")
    print("-" * 70)
    for item in queue:
        print(
            f"{item['queue_rank']:<3} | "
            f"{item['case_id']:<10} | "
            f"{item['priority_reason']:<24} | "
            f"{item['exact_similarity']:>5.1f}% | "
            f"{item['cosine_similarity']:>5.1f}% | "
            f"{item['predicted_class'].upper():<8}"
        )
    print("-" * 70)
    print("Gunakan './artilik eval review --inspect <case_id>' untuk rincian kasus")
    print("atau './artilik eval review --interactive' untuk peninjauan interaktif.\n")

def inspect_single_case(case_id: str, queue: Optional[List[Dict[str, Any]]] = None):
    cases, results = load_evaluation_data()
    c = next((c for c in cases if c["case_id"] == case_id), None)
    r = next((r for r in results if r["case_id"] == case_id), None)
    if not c or not r:
        print(f"[!] Kasus '{case_id}' tidak ditemukan.")
        return

    priority_reason = "Direct Inspection"
    if queue:
        item = next((q for q in queue if q["case_id"] == case_id), None)
        if item:
            priority_reason = item.get("priority_reason", priority_reason)

    print("\n" + "=" * 65)
    print(f"HIGH PRIORITY REVIEW CASE: {c['case_id']}")
    print("=" * 65)
    print(f"Alasan Prioritas : {priority_reason}")
    print(f"Tipe Kasus       : {c.get('case_type')} ({c.get('generation_method')})")
    print(f"Operator/Kekuatan: {c.get('mutation_operator', '-')} / {c.get('mutation_strength', '-')}")
    print(f"Content Hash     : {c.get('content_hash', '-')}")
    print("-" * 65)
    print(f"Expected Class   : {c.get('expected_class')} ({c.get('expected_pattern')})")
    print(f"Predicted Class  : {r.get('predicted_class', '').upper()} ({r.get('predicted_pattern')})")
    print(f"Agreement        : {r.get('agreement')}")
    print(f"Failure Class    : {r.get('failure_class') or 'NONE'}")
    print("-" * 65)
    print(f"Exact Similarity : {r.get('exact_similarity')}% (Longest: {r.get('longest_match_words')} kata, Max IDF: {r.get('max_idf')})")
    print(f"TF-IDF Cosine    : {r.get('cosine_similarity')}% (Matched: {r.get('matched_terms')} terms, Avg IDF: {r.get('avg_idf')})")
    print("-" * 65)
    print("[SUBMISSION TEXT]")
    print(c.get("submission_text", ""))
    print("-" * 65)
    print("[EXPLAINABILITY RATIONALE]")
    print(r.get("rationale", ""))
    print("-" * 65)
    print("Reviewer Labels:")
    print(" [ ] Relevant")
    print(" [ ] Common phrase")
    print(" [ ] Properly cited")
    print(" [ ] Needs investigation")
    print(" [ ] False positive")
    print("-" * 65)
    print("Citation Contexts:")
    print(" NONE | INLINE_CITATION | PARENTHETICAL_CITATION | BLOCK_QUOTE | DIRECT_QUOTE | FOOTNOTE | BIBLIOGRAPHY")
    print("Human Interpretations:")
    print(" DIRECT_OVERLAP_RELEVANT | DIRECT_OVERLAP_PROPERLY_CITED | COMMON_ACADEMIC_LANGUAGE | METHODOLOGY_BOILERPLATE | NEEDS_INVESTIGATION | FALSE_POSITIVE")
    print("=" * 65 + "\n")
def get_latest_labels_from_db() -> Dict[str, Dict[str, Any]]:
    """Mengambil label peninjauan manusia terbaru per case_id dari basis data."""
    try:
        conn = get_connection()
        cursor = conn.cursor(dictionary=True)
        cursor.execute("SELECT * FROM evaluation_labels ORDER BY id ASC")
        rows = cursor.fetchall()
        cursor.close()
        conn.close()
        latest_map = {}
        for r in rows:
            if "reviewed_at" in r and r["reviewed_at"]:
                r["reviewed_at"] = str(r["reviewed_at"])
            latest_map[r["case_id"]] = r
        return latest_map
    except Exception:
        return {}

HUMAN_REVIEW_30_CASE_CONFIG = [
    # Tier 1: Former Edge Cases & Failures (5 cases)
    ("EV-MUT-104", "Tier 1 — Edge Cases / Former Failures", "Former edge case: Restructuring (substantive clause transposition)"),
    ("EV-MUT-105", "Tier 1 — Edge Cases / Former Failures", "Former edge case: Restructuring (clause splitting & conjunction inversion)"),
    ("EV-ADV-005", "Tier 1 — Edge Cases / Former Failures", "Former edge case: Methodology boilerplate (Kolmogorov-Smirnov)"),
    ("EV-ADV-026", "Tier 1 — Edge Cases / Former Failures", "Former edge case: Overlap with parenthetical citation (Sugiyono 2019)"),
    ("EV-ADV-027", "Tier 1 — Edge Cases / Former Failures", "Former edge case: Overlap with parenthetical citation (Davis 1989)"),

    # Tier 2: High Exact + Citation / Quote (5 cases)
    ("EV-ADV-028", "Tier 2 — High Exact + Citation / Quote", "Citation protected overlap (Rahman & Wijaya 2021)"),
    ("EV-ADV-029", "Tier 2 — High Exact + Citation / Quote", "Citation protected overlap (Santoso 2020)"),
    ("EV-ADV-030", "Tier 2 — High Exact + Citation / Quote", "Citation protected overlap (Pratama 2022)"),
    ("EV-ADV-051", "Tier 2 — High Exact + Citation / Quote", "Direct verbatim quotation enclosed in quotes 1"),
    ("EV-ADV-052", "Tier 2 — High Exact + Citation / Quote", "Direct verbatim quotation enclosed in quotes 2"),

    # Tier 3: High TF-IDF + Exact ≈ 0 (5 cases)
    ("EV-ADV-076", "Tier 3 — High TF-IDF + Exact ≈ 0", "Disciplinary terminology / jargon overlap 1"),
    ("EV-ADV-077", "Tier 3 — High TF-IDF + Exact ≈ 0", "Disciplinary terminology / jargon overlap 2"),
    ("EV-ADV-078", "Tier 3 — High TF-IDF + Exact ≈ 0", "Disciplinary terminology / jargon overlap 3"),
    ("EV-ADV-079", "Tier 3 — High TF-IDF + Exact ≈ 0", "Disciplinary terminology / jargon overlap 4"),
    ("EV-ADV-080", "Tier 3 — High TF-IDF + Exact ≈ 0", "Disciplinary terminology / jargon overlap 5"),

    # Tier 4: Boundary Cases (5 cases)
    ("EV-BND-001", "Tier 4 — Boundary Cases", "Threshold boundary probing: Exact 70%"),
    ("EV-BND-016", "Tier 4 — Boundary Cases", "Threshold boundary probing: Exact 10% minus one"),
    ("EV-BND-031", "Tier 4 — Boundary Cases", "Threshold boundary probing: TF-IDF 85%"),
    ("EV-BND-046", "Tier 4 — Boundary Cases", "Threshold boundary probing: TF-IDF 10% minus one"),
    ("EV-BND-061", "Tier 4 — Boundary Cases", "Threshold boundary probing: Longest match 50 words"),

    # Tier 5: Semantic Paraphrase (5 cases)
    ("EV-SEED-021", "Tier 5 — Semantic Paraphrase", "Benchmark seed: Semantic paraphrase 1"),
    ("EV-SEED-022", "Tier 5 — Semantic Paraphrase", "Benchmark seed: Semantic paraphrase 2"),
    ("EV-SEED-023", "Tier 5 — Semantic Paraphrase", "Benchmark seed: Semantic paraphrase 3"),
    ("EV-SEED-024", "Tier 5 — Semantic Paraphrase", "Benchmark seed: Semantic paraphrase 4"),
    ("EV-SEED-025", "Tier 5 — Semantic Paraphrase", "Benchmark seed: Semantic paraphrase 5"),

    # Tier 6: Random Production / Baseline Control (5 cases)
    ("EV-SEED-001", "Tier 6 — Random Production / Control", "Baseline control: Identical clone (100% exact)"),
    ("EV-SEED-011", "Tier 6 — Random Production / Control", "Baseline control: Copy-edit (81.25% exact)"),
    ("EV-SEED-031", "Tier 6 — Random Production / Control", "Baseline control: Common academic phrase (52.38% exact)"),
    ("EV-SEED-041", "Tier 6 — Random Production / Control", "Baseline control: Unrelated discipline (0.0% exact, 5.73% cosine)"),
    ("EV-SEED-005", "Tier 6 — Random Production / Control", "Baseline control: Verbatim replication control")
]

def get_human_review_30_set(sync_file: bool = True) -> List[Dict[str, Any]]:
    """
    Menyusun paket 30 kasus telaah manusia prioritas tinggi.
    Menerapkan HR-UI Gate 4: Reviewer tidak melihat expected synthetic class/pattern.
    """
    cases, results = load_evaluation_data()
    case_map = {c["case_id"]: c for c in cases}
    result_map = {r["case_id"]: r for r in results}
    db_labels = get_latest_labels_from_db()

    review_set = []
    for rank, (cid, tier, reason) in enumerate(HUMAN_REVIEW_30_CASE_CONFIG, start=1):
        c = case_map.get(cid, {})
        r = result_map.get(cid, {})
        label_record = db_labels.get(cid)
        
        status = label_record.get("review_status", LIFECYCLE_REVIEWED) if label_record else LIFECYCLE_PENDING

        item = {
            "queue_rank": rank,
            "case_id": cid,
            "priority_tier": tier,
            "priority_reason": reason,
            "predicted_class": r.get("predicted_class", "unknown"),
            "predicted_pattern": r.get("predicted_pattern", "unknown"),
            "agreement": r.get("agreement", "UNKNOWN"),
            "exact_similarity": float(r.get("exact_similarity", 0.0)),
            "cosine_similarity": float(r.get("cosine_similarity", 0.0)),
            "longest_match_words": int(r.get("longest_match_words", 0)),
            "matched_terms": int(r.get("matched_terms", 0)),
            "avg_idf": float(r.get("avg_idf", 0.0)),
            "max_idf": float(r.get("max_idf", 0.0)),
            "rationale": r.get("rationale", ""),
            "submission_text": c.get("submission_text", ""),
            "source_text": c.get("source_text", ""),
            "review_status": status,
            "human_review": label_record
        }
        review_set.append(item)

    if sync_file:
        out_file = DATASET_DIR / "human_review_set.json"
        with open(out_file, "w", encoding="utf-8") as f:
            json.dump(review_set, f, indent=2, ensure_ascii=False, default=str)

    return review_set

def tag_case_full(
    case_id: str,
    reviewer_code: str,
    human_decision: str,
    citation_context: str,
    citation_status: str,
    human_interpretation: str,
    system_assessment: str,
    reviewer_confidence: str = CONFIDENCE_HIGH,
    review_status: str = LIFECYCLE_REVIEWED,
    notes: Optional[str] = None
):
    """
    Menyimpan hasil telaah manusia lengkap dengan 6 dimensi evaluasi ke DB dan file.
    """
    review_label = HumanReviewLabel(
        case_id=case_id,
        reviewer_code=reviewer_code,
        human_decision=human_decision,
        citation_context=citation_context,
        citation_status=citation_status,
        human_interpretation=human_interpretation,
        evidence_relevant=(human_interpretation in (HUMAN_INTERPRETATION_DIRECT_OVERLAP_RELEVANT, HUMAN_INTERPRETATION_DIRECT_OVERLAP_PROPERLY_CITED)),
        common_phrase=(human_interpretation == HUMAN_INTERPRETATION_COMMON_ACADEMIC_LANGUAGE),
        properly_cited=(human_interpretation == HUMAN_INTERPRETATION_DIRECT_OVERLAP_PROPERLY_CITED or citation_status in (CITATION_STATUS_CITED, CITATION_STATUS_PARAPHRASED_WITH_CITATION)),
        needs_investigation=(human_interpretation == HUMAN_INTERPRETATION_NEEDS_INVESTIGATION),
        false_positive=(human_interpretation == HUMAN_INTERPRETATION_FALSE_POSITIVE),
        system_assessment=system_assessment,
        reviewer_confidence=reviewer_confidence,
        review_status=review_status,
        notes=notes
    )

    # 1. Simpan ke database
    save_human_label(review_label.to_dict())

    # 2. Simpan ke berkas human_reviews.json
    reviews_file = DATASET_DIR / "human_reviews.json"
    existing = []
    if reviews_file.exists():
        try:
            with open(reviews_file, "r", encoding="utf-8") as f:
                existing = json.load(f)
        except Exception:
            existing = []

    # Update atau append
    existing = [e for e in existing if e.get("case_id") != case_id]
    existing.append(review_label.to_dict())
    with open(reviews_file, "w", encoding="utf-8") as f:
        json.dump(existing, f, indent=2, ensure_ascii=False, default=str)

    # Update set
    get_human_review_30_set(sync_file=True)

    print(f"[✓] Berhasil menyimpan anotasi manusia untuk {case_id}:")
    print(f"    Reviewer       : {reviewer_code}")
    print(f"    Decision       : {human_decision}")
    print(f"    Interpretation : {human_interpretation}")
    print(f"    Citation Ctx   : {citation_context}")
    print(f"    Citation Status: {citation_status}")
    print(f"    Assessment     : {system_assessment}")
    print(f"    Confidence     : {reviewer_confidence}")
    if notes:
        print(f"    Catatan        : {notes}")

def tag_case_cli(
    case_id: str,
    reviewer_code: str,
    human_decision: str,
    label: str,
    system_assessment: str = "CORRECT",
    notes: Optional[str] = None,
    citation_context: str = CITATION_CONTEXT_NONE,
    human_interpretation: str = HUMAN_INTERPRETATION_DIRECT_OVERLAP_RELEVANT,
    citation_status: str = CITATION_STATUS_NOT_CITED,
    reviewer_confidence: str = CONFIDENCE_HIGH,
    review_status: str = LIFECYCLE_REVIEWED
):
    valid_labels = ["Relevant", "Common phrase", "Properly cited", "Needs investigation", "False positive"]
    if label not in valid_labels:
        raise ValueError(f"Label tidak valid. Pilih dari: {valid_labels}")

    if human_interpretation == HUMAN_INTERPRETATION_DIRECT_OVERLAP_RELEVANT:
        if label == "Properly cited":
            human_interpretation = HUMAN_INTERPRETATION_DIRECT_OVERLAP_PROPERLY_CITED
        elif label == "Common phrase":
            human_interpretation = HUMAN_INTERPRETATION_COMMON_ACADEMIC_LANGUAGE
        elif label == "Needs investigation":
            human_interpretation = HUMAN_INTERPRETATION_NEEDS_INVESTIGATION
        elif label == "False positive":
            human_interpretation = HUMAN_INTERPRETATION_FALSE_POSITIVE

    tag_case_full(
        case_id=case_id,
        reviewer_code=reviewer_code,
        human_decision=human_decision,
        citation_context=citation_context,
        citation_status=citation_status,
        human_interpretation=human_interpretation,
        system_assessment=system_assessment,
        reviewer_confidence=reviewer_confidence,
        review_status=review_status,
        notes=notes
    )

def get_human_review_summary() -> Dict[str, Any]:
    """
    Menghitung agregasi hasil telaah manusia untuk 30 kasus prioritas.
    """
    review_set = get_human_review_30_set(sync_file=False)
    total_cases = len(review_set)
    reviewed_items = [item for item in review_set if item["review_status"] in (LIFECYCLE_REVIEWED, LIFECYCLE_VALIDATED)]
    reviewed_count = len(reviewed_items)
    pending_count = total_cases - reviewed_count

    assessments = Counter()
    citations = Counter()
    citation_statuses = Counter()
    interpretations = Counter()
    confidences = Counter()
    tier_progress = Counter()

    for item in reviewed_items:
        hr = item["human_review"] or {}
        assess = hr.get("system_assessment", SYSTEM_ASSESSMENT_CORRECT)
        ctx = hr.get("citation_context", CITATION_CONTEXT_NONE)
        stat = hr.get("citation_status", CITATION_STATUS_NOT_CITED)
        interp = hr.get("human_interpretation", HUMAN_INTERPRETATION_DIRECT_OVERLAP_RELEVANT)
        conf = hr.get("reviewer_confidence", CONFIDENCE_HIGH)

        assessments[assess] += 1
        citations[ctx] += 1
        citation_statuses[stat] += 1
        interpretations[interp] += 1
        confidences[conf] += 1
        tier_progress[item["priority_tier"]] += 1

    agreement_cases = assessments[SYSTEM_ASSESSMENT_CORRECT] + assessments[SYSTEM_ASSESSMENT_ACCEPTABLE]
    agreement_rate = (agreement_cases / reviewed_count * 100.0) if reviewed_count > 0 else 0.0

    return {
        "total_cases": total_cases,
        "reviewed_count": reviewed_count,
        "pending_count": pending_count,
        "completion_rate": round(reviewed_count / total_cases * 100.0, 1),
        "system_assessments": dict(assessments),
        "citation_contexts": dict(citations),
        "citation_statuses": dict(citation_statuses),
        "interpretations": dict(interpretations),
        "confidences": dict(confidences),
        "tier_progress": dict(tier_progress),
        "agreement_cases": agreement_cases,
        "agreement_rate": round(agreement_rate, 1)
    }

def run_interactive_human_review(reviewer_code: str = "RV-001"):
    """
    Alur kerja telaah interaktif CLI untuk 30 kasus prioritas.
    """
    review_set = get_human_review_30_set(sync_file=True)
    print("\n" + "=" * 65)
    print("           AR TILIK — HUMAN REVIEW WORKFLOW           ")
    print("=" * 65)
    print(f"Cases selected : {len(review_set)}")
    print(f"Reviewer Code  : {reviewer_code}")
    print(f"System Version : v1.0.0 (FROZEN)")
    print("-" * 65)
    print("Priority Breakdown:")
    tiers = Counter(item["priority_tier"] for item in review_set)
    for t_name, t_cnt in tiers.items():
        print(f" - {t_name:<42} : {t_cnt} kasus")
    print("=" * 65 + "\n")

    interp_options = [
        HUMAN_INTERPRETATION_DIRECT_OVERLAP_RELEVANT,
        HUMAN_INTERPRETATION_COMMON_ACADEMIC_LANGUAGE,
        HUMAN_INTERPRETATION_METHODOLOGY_BOILERPLATE,
        HUMAN_INTERPRETATION_NEEDS_INVESTIGATION,
        HUMAN_INTERPRETATION_FALSE_POSITIVE,
        HUMAN_INTERPRETATION_INSUFFICIENT_EVIDENCE
    ]
    ctx_options = [
        CITATION_CONTEXT_NONE,
        CITATION_CONTEXT_INLINE,
        CITATION_CONTEXT_PARENTHETICAL,
        CITATION_CONTEXT_BLOCK_QUOTE,
        CITATION_CONTEXT_DIRECT_QUOTE,
        CITATION_CONTEXT_FOOTNOTE,
        CITATION_CONTEXT_BIBLIOGRAPHY,
        CITATION_CONTEXT_UNKNOWN
    ]
    cit_stat_options = [
        CITATION_STATUS_NOT_CITED,
        CITATION_STATUS_CITED,
        CITATION_STATUS_PARTIALLY_CITED,
        CITATION_STATUS_QUOTE,
        CITATION_STATUS_PARAPHRASED_WITH_CITATION,
        CITATION_STATUS_NOT_APPLICABLE,
        CITATION_STATUS_UNKNOWN
    ]
    assess_options = [
        SYSTEM_ASSESSMENT_CORRECT,
        SYSTEM_ASSESSMENT_ACCEPTABLE,
        SYSTEM_ASSESSMENT_TOO_STRICT,
        SYSTEM_ASSESSMENT_TOO_LENIENT,
        SYSTEM_ASSESSMENT_UNCERTAIN
    ]
    conf_options = [
        CONFIDENCE_HIGH,
        CONFIDENCE_MEDIUM,
        CONFIDENCE_LOW
    ]

    for idx, item in enumerate(review_set, start=1):
        cid = item["case_id"]
        status_tag = f"[{item['review_status']}]"
        print("\n" + "-" * 65)
        print(f"[{idx}/{len(review_set)}] {cid}  {status_tag}")
        print(f"Tier       : {item['priority_tier']}")
        print(f"Alasan     : {item['priority_reason']}")
        print("-" * 65)
        print(f"System Decision : {item['predicted_class'].upper()} ({item['predicted_pattern']})")
        print(f"Agreement       : {item['agreement']}")
        print(f"Exact Overlap   : {item['exact_similarity']}% (Longest: {item['longest_match_words']} kata, Max IDF: {item['max_idf']})")
        print(f"TF-IDF Cosine   : {item['cosine_similarity']}% (Matched Terms: {item['matched_terms']}, Avg IDF: {item['avg_idf']})")
        print("-" * 65)
        print("[SUBMISSION EXCERPT]")
        print(item["submission_text"][:300] + ("..." if len(item["submission_text"]) > 300 else ""))
        print("\n[SOURCE EXCERPT]")
        print(item["source_text"][:300] + ("..." if len(item["source_text"]) > 300 else ""))
        print("-" * 65)

        # 1. Interpretation
        print("1. Human Interpretation:")
        for o_i, opt in enumerate(interp_options, start=1):
            print(f"   [{o_i}] {opt}")
        c_int = input("   Pilihan [1-6, 's' lewati, 'q' keluar]: ").strip()
        if c_int.lower() == 'q':
            print("[*] Sesi telaah dihentikan.")
            break
        if c_int.lower() == 's' or not c_int:
            continue
        try:
            sel_interp = interp_options[int(c_int) - 1]
        except Exception:
            sel_interp = HUMAN_INTERPRETATION_DIRECT_OVERLAP_RELEVANT

        # 2. Citation Context
        print("\n2. Citation Context:")
        for o_i, opt in enumerate(ctx_options, start=1):
            print(f"   [{o_i}] {opt}")
        def_ctx_idx = 3 if "Sugiyono" in item["submission_text"] or "Davis" in item["submission_text"] else 1
        c_ctx = input(f"   Pilihan [1-8, default {def_ctx_idx}]: ").strip()
        try:
            sel_ctx = ctx_options[int(c_ctx) - 1] if c_ctx else ctx_options[def_ctx_idx - 1]
        except Exception:
            sel_ctx = ctx_options[def_ctx_idx - 1]

        # 3. Citation Status
        print("\n3. Citation Status:")
        for o_i, opt in enumerate(cit_stat_options, start=1):
            print(f"   [{o_i}] {opt}")
        def_stat_idx = 5 if sel_ctx in (CITATION_CONTEXT_INLINE, CITATION_CONTEXT_PARENTHETICAL) else 1
        c_stat = input(f"   Pilihan [1-7, default {def_stat_idx}]: ").strip()
        try:
            sel_stat = cit_stat_options[int(c_stat) - 1] if c_stat else cit_stat_options[def_stat_idx - 1]
        except Exception:
            sel_stat = cit_stat_options[def_stat_idx - 1]

        # 4. System Assessment
        print("\n4. System Assessment:")
        for o_i, opt in enumerate(assess_options, start=1):
            print(f"   [{o_i}] {opt}")
        c_ass = input("   Pilihan [1-5, default 1: CORRECT]: ").strip()
        try:
            sel_ass = assess_options[int(c_ass) - 1] if c_ass else SYSTEM_ASSESSMENT_CORRECT
        except Exception:
            sel_ass = SYSTEM_ASSESSMENT_CORRECT

        # 5. Confidence
        print("\n5. Reviewer Confidence:")
        for o_i, opt in enumerate(conf_options, start=1):
            print(f"   [{o_i}] {opt}")
        c_conf = input("   Pilihan [1-3, default 1: HIGH]: ").strip()
        try:
            sel_conf = conf_options[int(c_conf) - 1] if c_conf else CONFIDENCE_HIGH
        except Exception:
            sel_conf = CONFIDENCE_HIGH

        # 6. Notes
        notes = input("\n6. Catatan Reviewer (opsional): ").strip() or None

        tag_case_full(
            case_id=cid,
            reviewer_code=reviewer_code,
            human_decision=item["predicted_class"],
            citation_context=sel_ctx,
            citation_status=sel_stat,
            human_interpretation=sel_interp,
            system_assessment=sel_ass,
            reviewer_confidence=sel_conf,
            review_status=LIFECYCLE_REVIEWED,
            notes=notes
        )

        next_cid = review_set[idx]["case_id"] if idx < len(review_set) else "END"
        print(f"\n[✓] Review saved for {cid}. Progress: {idx} / {len(review_set)}. Next case: {next_cid}")

    # Tampilkan ringkasan akhir
    summary = get_human_review_summary()
    print("\n" + "=" * 65)
    print("            HUMAN REVIEW SESSION SUMMARY            ")
    print("=" * 65)
    print(f"Cases reviewed : {summary['reviewed_count']} / {summary['total_cases']} ({summary['completion_rate']}%)")
    print(f"Agreement Rate : {summary['agreement_rate']}% (CORRECT / ACCEPTABLE)")
    print("-" * 65)
    print("System Assessment Distribution:")
    for k, v in summary["system_assessments"].items():
        print(f"  {k:<16} : {v}")
    print("Citation Context Distribution:")
    for k, v in summary["citation_contexts"].items():
        print(f"  {k:<24} : {v}")
    print("Interpretation Distribution:")
    for k, v in summary["interpretations"].items():
        print(f"  {k:<32} : {v}")
    print("=" * 65 + "\n")
