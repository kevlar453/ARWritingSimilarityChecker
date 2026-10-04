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
from typing import List, Dict, Any, Optional

from evaluation.schema import (
    HumanReviewLabel,
    SYSTEM_ASSESSMENT_CORRECT,
    SYSTEM_ASSESSMENT_ACCEPTABLE,
    SYSTEM_ASSESSMENT_TOO_STRICT,
    SYSTEM_ASSESSMENT_TOO_LENIENT,
    SYSTEM_ASSESSMENT_UNCERTAIN
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

def inspect_single_case(case_id: str, queue: List[Dict[str, Any]]):
    item = next((q for q in queue if q["case_id"] == case_id), None)
    if not item:
        # Cari di cases.json jika tidak ada di queue top-k
        cases, results = load_evaluation_data()
        c = next((c for c in cases if c["case_id"] == case_id), None)
        r = next((r for r in results if r["case_id"] == case_id), None)
        if not c or not r:
            print(f"[!] Kasus '{case_id}' tidak ditemukan.")
            return
        item = {
            "queue_rank": "-",
            "priority_reason": "Direct Inspection",
            "case_id": case_id,
            "case_type": c.get("case_type"),
            "generation_method": c.get("generation_method"),
            "expected_class": c.get("expected_class"),
            "expected_pattern": c.get("expected_pattern"),
            "predicted_class": r.get("predicted_class"),
            "predicted_pattern": r.get("predicted_pattern"),
            "agreement": r.get("agreement"),
            "exact_similarity": r.get("exact_similarity"),
            "cosine_similarity": r.get("cosine_similarity"),
            "longest_match_words": r.get("longest_match_words"),
            "avg_idf": r.get("avg_idf"),
            "max_idf": r.get("max_idf"),
            "rationale": r.get("rationale"),
            "submission_snippet": c.get("submission_text", "")
        }

    print("\n" + "=" * 60)
    print(f"HIGH PRIORITY REVIEW CASE: {item['case_id']}")
    print("=" * 60)
    print(f"Alasan Prioritas : {item['priority_reason']}")
    print(f"Tipe Kasus       : {item['case_type']} ({item['generation_method']})")
    print("-" * 60)
    print(f"Pattern          : {item['predicted_pattern']}")
    print(f"Decision         : {item['predicted_class'].upper()}")
    print(f"Agreement        : {item['agreement']}")
    print(f"Exact Similarity : {item['exact_similarity']}% (Longest: {item['longest_match_words']} kata, Max IDF: {item['max_idf']})")
    print(f"TF-IDF Cosine    : {item['cosine_similarity']}% (Avg IDF: {item['avg_idf']})")
    print("-" * 60)
    print("[SUBMISSION TEXT]")
    print(item["submission_snippet"])
    print("-" * 60)
    print("[EXPLAINABILITY RATIONALE]")
    print(item["rationale"])
    print("-" * 60)
    print("Reviewer Labels:")
    print(" [ ] Relevant")
    print(" [ ] Common phrase")
    print(" [ ] Properly cited")
    print(" [ ] Needs investigation")
    print(" [ ] False positive")
    print("=" * 60 + "\n")

def tag_case_cli(
    case_id: str,
    reviewer_code: str,
    human_decision: str,
    label: str,
    system_assessment: str = "CORRECT",
    notes: Optional[str] = None
):
    valid_labels = ["Relevant", "Common phrase", "Properly cited", "Needs investigation", "False positive"]
    if label not in valid_labels:
        raise ValueError(f"Label tidak valid. Pilih dari: {valid_labels}")

    review_label = HumanReviewLabel(
        case_id=case_id,
        reviewer_code=reviewer_code,
        human_decision=human_decision,
        evidence_relevant=(label == "Relevant"),
        common_phrase=(label == "Common phrase"),
        properly_cited=(label == "Properly cited"),
        needs_investigation=(label == "Needs investigation"),
        false_positive=(label == "False positive"),
        system_assessment=system_assessment,
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
            
    existing.append(review_label.to_dict())
    with open(reviews_file, "w", encoding="utf-8") as f:
        json.dump(existing, f, indent=2, ensure_ascii=False)

    print(f"[✓] Berhasil menyimpan anotasi manusia untuk {case_id}:")
    print(f"    Reviewer   : {reviewer_code}")
    print(f"    Decision   : {human_decision}")
    print(f"    Label      : [{label}]")
    print(f"    Assessment : {system_assessment}")
    if notes:
        print(f"    Catatan    : {notes}")
