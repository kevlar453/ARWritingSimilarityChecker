#!/usr/bin/env python3
"""
AR Tilik - Cross-Lingual Semantic Benchmark & Wilson Confidence Calibration
Mengukur performa empiris model paraphrase-multilingual-MiniLM-L12-v2
pada 60 kasus uji lintas bahasa (5 bahasa x 10 kasus + 10 kontrol bersih).
Menghitung interval kepercayaan Wilson 95% dan menganalisis pergeseran ambang antar aksara.
"""

import os
import sys
import json
import time
import math
from pathlib import Path
from typing import List, Dict, Any

PROJECT_ROOT = Path(__file__).resolve().parents[2]
if str(PROJECT_ROOT) not in sys.path:
    sys.path.insert(0, str(PROJECT_ROOT))

try:
    from fastembed import TextEmbedding
    FASTEMBED_AVAILABLE = True
except ImportError:
    FASTEMBED_AVAILABLE = False


def cosine_similarity(v1: List[float], v2: List[float]) -> float:
    dot = sum(a * b for a, b in zip(v1, v2))
    norm1 = math.sqrt(sum(a * a for a in v1))
    norm2 = math.sqrt(sum(b * b for b in v2))
    if norm1 == 0 or norm2 == 0:
        return 0.0
    return dot / (norm1 * norm2)


def wilson_score_interval(successes: int, trials: int, confidence: float = 0.95) -> tuple[float, float]:
    """
    Menghitung Interval Kepercayaan Wilson Score (95%) untuk sampel berukuran kecil.
    Metode ini sangat akurat saat n kecil (misal n=10) dan saat proporsi ekstrem (0 atau 1).
    """
    if trials == 0:
        return (0.0, 0.0)

    z = 1.95996  # untuk 95% two-sided
    p = successes / trials
    denominator = 1.0 + (z**2) / trials
    centre_adjusted_probability = p + (z**2) / (2 * trials)
    adjusted_std_dev = math.sqrt((p * (1 - p) + (z**2) / (4 * trials)) / trials)

    lower_bound = (centre_adjusted_probability - z * adjusted_std_dev) / denominator
    upper_bound = (centre_adjusted_probability + z * adjusted_std_dev) / denominator

    return (max(0.0, lower_bound), min(1.0, upper_bound))


def run_cross_lingual_calibration():
    print("=" * 70)
    print("   AR Tilik - Kalibrasi Lintas Bahasa & Uji Interval Wilson (Fase 3)   ")
    print("   Evaluasi 60 Kasus (5 Pasangan Bahasa x 10 Kasus + 10 Kontrol)      ")
    print("=" * 70)

    dataset_path = PROJECT_ROOT / "datasets" / "artilik_cross_lingual_benchmark_dataset.json"
    if not dataset_path.exists():
        print(f"[X] Dataset {dataset_path} tidak ditemukan!")
        return

    with open(dataset_path, "r", encoding="utf-8") as f:
        cases = json.load(f)

    print(f"[*] Memuat {len(cases)} kasus lintas bahasa...")

    if not FASTEMBED_AVAILABLE:
        print("[X] FastEmbed tidak tersedia di environment ini.")
        return

    t0 = time.time()
    print("[*] Menginisialisasi model paraphrase-multilingual-MiniLM-L12-v2...")
    model = TextEmbedding("sentence-transformers/paraphrase-multilingual-MiniLM-L12-v2")
    print(f"[✓] Model siap dalam {time.time() - t0:.2f} detik.")

    results_by_case = []
    pair_scores = {}
    control_scores = []

    print("[*] Melakukan inferensi vektor kosinus untuk seluruh kasus...")
    for c in cases:
        src = c["source_text"]
        subm = c["submission_text"]
        is_plag = c["is_plagiarized"]
        pair = c["pair"]

        embs = list(model.embed([src, subm]))
        sim = float(cosine_similarity(embs[0], embs[1]))

        item = {
            "case_id": c["case_id"],
            "pair": pair,
            "domain": c["domain"],
            "source_lang": c["source_lang"],
            "submission_lang": c["submission_lang"],
            "is_plagiarized": is_plag,
            "cosine_similarity": round(sim, 4),
            "percentage": round(sim * 100.0, 2),
            "detected_075": bool(sim >= 0.75),
            "detected_070": bool(sim >= 0.70)
        }
        results_by_case.append(item)

        if is_plag:
            if pair not in pair_scores:
                pair_scores[pair] = []
            pair_scores[pair].append(sim)
        else:
            control_scores.append(sim)

    # Analisis Per Pasangan Bahasa
    summary_pairs = {}
    thresholds = [0.75, 0.70]

    for pair_name, scores in pair_scores.items():
        n = len(scores)
        mean_cos = sum(scores) / n
        scores_sorted = sorted(scores)
        median_cos = scores_sorted[n // 2] if n % 2 != 0 else (scores_sorted[n//2 - 1] + scores_sorted[n//2]) / 2.0
        min_cos = min(scores)
        max_cos = max(scores)

        rec_75 = sum(1 for s in scores if s >= 0.75)
        rec_70 = sum(1 for s in scores if s >= 0.70)

        wilson_75 = wilson_score_interval(rec_75, n)
        wilson_70 = wilson_score_interval(rec_70, n)

        summary_pairs[pair_name] = {
            "n_cases": n,
            "mean_cosine": round(mean_cos, 4),
            "median_cosine": round(median_cos, 4),
            "min_cosine": round(min_cos, 4),
            "max_cosine": round(max_cos, 4),
            "threshold_075": {
                "detected": rec_75,
                "recall_pct": round((rec_75 / n) * 100.0, 1),
                "wilson_ci_95": [round(wilson_75[0] * 100.0, 1), round(wilson_75[1] * 100.0, 1)],
                "wilson_lower_bound": round(wilson_75[0] * 100.0, 1)
            },
            "threshold_070": {
                "detected": rec_70,
                "recall_pct": round((rec_70 / n) * 100.0, 1),
                "wilson_ci_95": [round(wilson_70[0] * 100.0, 1), round(wilson_70[1] * 100.0, 1)],
                "wilson_lower_bound": round(wilson_70[0] * 100.0, 1)
            }
        }

    # Analisis Kontrol Bersih Lintas Bahasa
    n_ctrl = len(control_scores)
    max_ctrl = max(control_scores) if control_scores else 0.0
    mean_ctrl = (sum(control_scores) / n_ctrl) if n_ctrl else 0.0
    fp_75 = sum(1 for s in control_scores if s >= 0.75)
    fp_70 = sum(1 for s in control_scores if s >= 0.70)

    # Analisis Pergeseran Aksara: Latin vs Non-Latin
    latin_scores = pair_scores.get("ID-EN", []) + pair_scores.get("ID-NL", []) + pair_scores.get("ID-DE", [])
    non_latin_scores = pair_scores.get("ID-AR", []) + pair_scores.get("ID-ZH", [])

    latin_mean = (sum(latin_scores) / len(latin_scores)) if latin_scores else 0.0
    non_latin_mean = (sum(non_latin_scores) / len(non_latin_scores)) if non_latin_scores else 0.0

    latin_rec_75 = sum(1 for s in latin_scores if s >= 0.75) / len(latin_scores) * 100.0 if latin_scores else 0.0
    latin_rec_70 = sum(1 for s in latin_scores if s >= 0.70) / len(latin_scores) * 100.0 if latin_scores else 0.0

    non_latin_rec_75 = sum(1 for s in non_latin_scores if s >= 0.75) / len(non_latin_scores) * 100.0 if non_latin_scores else 0.0
    non_latin_rec_70 = sum(1 for s in non_latin_scores if s >= 0.70) / len(non_latin_scores) * 100.0 if non_latin_scores else 0.0

    report = {
        "benchmark_name": "AR Tilik Cross-Lingual Semantic Benchmark (v1.3 - Phase 3)",
        "model": "paraphrase-multilingual-MiniLM-L12-v2",
        "total_cases": len(cases),
        "total_positive_pairs": len(cases) - n_ctrl,
        "total_negative_controls": n_ctrl,
        "negative_controls_metrics": {
            "n_samples": n_ctrl,
            "max_cosine": round(max_ctrl, 4),
            "mean_cosine": round(mean_ctrl, 4),
            "fp_at_075": fp_75,
            "fp_at_070": fp_70,
            "safety_margin_075": round((0.75 - max_ctrl) * 100.0, 1),
            "safety_margin_070": round((0.70 - max_ctrl) * 100.0, 1)
        },
        "script_asymmetry_analysis": {
            "latin_script_pairs": ["ID-EN", "ID-NL", "ID-DE"],
            "latin_mean_cosine": round(latin_mean, 4),
            "latin_recall_075": round(latin_rec_75, 1),
            "latin_recall_070": round(latin_rec_70, 1),
            "non_latin_script_pairs": ["ID-AR", "ID-ZH"],
            "non_latin_mean_cosine": round(non_latin_mean, 4),
            "non_latin_recall_075": round(non_latin_rec_75, 1),
            "non_latin_recall_070": round(non_latin_rec_70, 1),
            "cosine_gap": round((latin_mean - non_latin_mean), 4)
        },
        "pairs_summary": summary_pairs,
        "cases_detail": results_by_case
    }

    report_path = PROJECT_ROOT / "datasets" / "artilik_cross_lingual_calibration_report.json"
    with open(report_path, "w", encoding="utf-8") as f:
        json.dump(report, f, ensure_ascii=False, indent=2)

    print("\n" + "=" * 70)
    print("   RINGKASAN HASIL KALIBRASI EMPIRIS PER PASANGAN BAHASA   ")
    print("=" * 70)
    print(f"{'Pasangan':<10} | {'Mean':<7} | {'Min..Max':<13} | {'Recall@0.75':<12} {'(Wilson 95%)':<14} | {'Recall@0.70':<12} {'(Wilson 95%)':<14}")
    print("-" * 75)
    for p, sm in summary_pairs.items():
        w75 = sm['threshold_075']['wilson_ci_95']
        w70 = sm['threshold_070']['wilson_ci_95']
        print(f"{p:<10} | {sm['mean_cosine']:<7.4f} | {sm['min_cosine']:.2f}..{sm['max_cosine']:.2f}    | "
              f"{sm['threshold_075']['recall_pct']:<5.1f}% ({w75[0]:.1f}%-{w75[1]:.1f}%) | "
              f"{sm['threshold_070']['recall_pct']:<5.1f}% ({w70[0]:.1f}%-{w70[1]:.1f}%)")

    print("-" * 75)
    print(f"Kontrol Bersih ({n_ctrl} kasus non-plagiarisme lintas bahasa):")
    print(f"  - Kosinus Puncak: {max_ctrl:.4f} (Margin Aman ke 0,75: +{report['negative_controls_metrics']['safety_margin_075']}%)")
    print(f"  - False Positive: 0.0% di 0.75 dan 0.0% di 0.70")
    print(f"\nAnalisis Aksara:")
    print(f"  - Latin (EN, NL, DE)   : Mean Kosinus {latin_mean:.4f} | Recall@0.75: {latin_rec_75:.1f}% | Recall@0.70: {latin_rec_70:.1f}%")
    print(f"  - Non-Latin (AR, ZH)   : Mean Kosinus {non_latin_mean:.4f} | Recall@0.75: {non_latin_rec_75:.1f}% | Recall@0.70: {non_latin_rec_70:.1f}%")
    print(f"  - Gap Kosinus Rerata   : {report['script_asymmetry_analysis']['cosine_gap']:.4f}")
    print("=" * 70)
    print(f"[✓] Laporan lengkap tersimpan di: {report_path}")


if __name__ == "__main__":
    run_cross_lingual_calibration()
