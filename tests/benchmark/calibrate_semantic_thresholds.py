#!/usr/bin/env python3
"""
AR Tilik - Semantic Paraphrase Calibration Benchmark Runner.
Mengukur performa empiris model paraphrase-multilingual-MiniLM-L12-v2 pada 80 kasus:
- Parafrase Ringan, Sedang, Agresif
- Kontrol Dokumen Bersih (False Positive)
- Validasi Lintas Bahasa (EN, AR, ZH, NL, DE)
- Kalibrasi Ambang: 0.70, 0.75, 0.80
"""

import os
import sys
import json
import time
import math
from pathlib import Path
from typing import List, Dict, Any

_parent_dir = str(Path(__file__).resolve().parents[2])
if _parent_dir not in sys.path:
    sys.path.insert(0, _parent_dir)

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


def compute_lexical_overlap(text1: str, text2: str, n: int = 5) -> float:
    """Simulasi shingle n-gram leksikal v1.0 AR Tilik untuk perbandingan kontras."""
    words1 = [w.lower() for w in text1.split() if len(w) > 1]
    words2 = [w.lower() for w in text2.split() if len(w) > 1]
    if len(words1) < n or len(words2) < n:
        set1 = set(words1)
        set2 = set(words2)
        inter = len(set1.intersection(set2))
        union = len(set1.union(set2))
        return (inter / union * 100.0) if union else 0.0

    shingles1 = set(" ".join(words1[i:i+n]) for i in range(len(words1) - n + 1))
    shingles2 = set(" ".join(words2[i:i+n]) for i in range(len(words2) - n + 1))
    inter = len(shingles1.intersection(shingles2))
    union = len(shingles1.union(shingles2))
    return (inter / union * 100.0) if union else 0.0


def run_semantic_calibration():
    print("=" * 70)
    print("   AR Tilik - Kalibrasi Empiris Lensa Semantik (MiniLM-L12)   ")
    print("   Evaluasi 80 Kasus Terarah, Ambang 0.70 / 0.75 / 0.80      ")
    print("=" * 70)

    dataset_path = Path(_parent_dir) / "datasets" / "artilik_semantic_paraphrase_benchmark.json"
    if not dataset_path.exists():
        print(f"[X] Dataset benchmark tidak ditemukan di {dataset_path}")
        return

    with open(dataset_path, "r", encoding="utf-8") as f:
        cases = json.load(f)

    print(f"[*] Memuat {len(cases)} kasus benchmark...")

    if not FASTEMBED_AVAILABLE:
        print("[X] FastEmbed tidak terpasang di environment ini.")
        return

    t0 = time.time()
    print("[*] Menginisialisasi model sentence-transformers/paraphrase-multilingual-MiniLM-L12-v2...")
    model = TextEmbedding("sentence-transformers/paraphrase-multilingual-MiniLM-L12-v2")
    init_time = time.time() - t0
    print(f"[✓] Model siap dalam {init_time:.2f} detik.")

    thresholds = [0.70, 0.75, 0.80]
    results = []

    print("\n[*] Menjalankan inferensi embedding dan komputasi metrik...")
    for idx, case in enumerate(cases, start=1):
        source = case["source_text"]
        subm = case["submission_text"]
        is_plag = case["is_plagiarized"]
        group = case["group"]

        # Leksikal shingling
        lexical_score = compute_lexical_overlap(source, subm, n=5)

        # Semantik embedding
        emb = list(model.embed([source, subm]))
        sim = cosine_similarity(emb[0], emb[1])

        case_res = {
            "case_id": case["case_id"],
            "group": group,
            "domain": case["domain"],
            "is_plagiarized": is_plag,
            "source_lang": case["source_lang"],
            "submission_lang": case["submission_lang"],
            "lexical_similarity_pct": float(round(lexical_score, 2)),
            "semantic_cosine": float(round(sim, 4)),
            "semantic_similarity_pct": float(round(sim * 100.0, 2)),
            "decisions": {}
        }

        for th in thresholds:
            detected_plag = bool(sim >= th)
            if is_plag and detected_plag:
                eval_class = "TP" # Benar mendeteksi parafrase
            elif is_plag and not detected_plag:
                eval_class = "FN" # Lolos (False Negative)
            elif not is_plag and detected_plag:
                eval_class = "FP" # Alarm palsu / salah tuduh (False Positive)
            else:
                eval_class = "TN" # Benar menyatakan bersih (True Negative)

            case_res["decisions"][str(th)] = {
                "detected": bool(detected_plag),
                "confusion": eval_class
            }

        results.append(case_res)

    # Analisis Metrik per Ambang
    summary = {
        "benchmark_name": "AR Tilik Semantic Paraphrase Calibration Suite",
        "timestamp": time.strftime("%Y-%m-%d %H:%M:%S"),
        "total_cases": len(cases),
        "thresholds_evaluated": thresholds,
        "group_counts": {},
        "threshold_metrics": {},
        "group_breakdown": {},
        "cross_lingual_metrics": {},
        "mismatches_and_failures": []
    }

    for c in cases:
        g = c["group"]
        summary["group_counts"][g] = summary["group_counts"].get(g, 0) + 1

    for th in thresholds:
        th_key = str(th)
        tp = sum(1 for r in results if r["decisions"][th_key]["confusion"] == "TP")
        fp = sum(1 for r in results if r["decisions"][th_key]["confusion"] == "FP")
        fn = sum(1 for r in results if r["decisions"][th_key]["confusion"] == "FN")
        tn = sum(1 for r in results if r["decisions"][th_key]["confusion"] == "TN")

        precision = (tp / (tp + fp)) if (tp + fp) > 0 else 0.0
        recall = (tp / (tp + fn)) if (tp + fn) > 0 else 0.0
        f1 = (2 * precision * recall / (precision + recall)) if (precision + recall) > 0 else 0.0
        fpr_clean = (fp / 20.0) * 100.0 # persentase salah tuduh pada 20 dokumen bersih
        accuracy = ((tp + tn) / len(cases)) * 100.0

        summary["threshold_metrics"][th_key] = {
            "threshold": float(th),
            "TP": int(tp),
            "FP": int(fp),
            "FN": int(fn),
            "TN": int(tn),
            "accuracy_pct": float(round(accuracy, 2)),
            "precision_pct": float(round(precision * 100.0, 2)),
            "recall_pct": float(round(recall * 100.0, 2)),
            "f1_score": float(round(f1, 4)),
            "clean_false_positive_rate_pct": float(round(fpr_clean, 2))
        }

    # Performa per grup
    for group_name in summary["group_counts"]:
        group_results = [r for r in results if r["group"] == group_name]
        avg_lex = sum(r["lexical_similarity_pct"] for r in group_results) / len(group_results)
        avg_sem = sum(r["semantic_similarity_pct"] for r in group_results) / len(group_results)

        summary["group_breakdown"][group_name] = {
            "count": len(group_results),
            "avg_lexical_similarity_pct": float(round(avg_lex, 2)),
            "avg_semantic_similarity_pct": float(round(avg_sem, 2)),
            "detection_rate_at_0_70": float(round(sum(1 for r in group_results if r["decisions"]["0.7"]["detected"]) / len(group_results) * 100.0, 1)),
            "detection_rate_at_0_75": float(round(sum(1 for r in group_results if r["decisions"]["0.75"]["detected"]) / len(group_results) * 100.0, 1)),
            "detection_rate_at_0_80": float(round(sum(1 for r in group_results if r["decisions"]["0.8"]["detected"]) / len(group_results) * 100.0, 1)),
        }

    # Lintas Bahasa per pasangan bahasa
    lang_pairs = ["en", "ar", "zh", "nl", "de"]
    for lp in lang_pairs:
        lp_results = [r for r in results if r["group"] == "CROSS_LINGUAL" and r["submission_lang"] == lp]
        if lp_results:
            avg_cos = sum(r["semantic_cosine"] for r in lp_results) / len(lp_results)
            detected_075 = sum(1 for r in lp_results if r["semantic_cosine"] >= 0.75)
            summary["cross_lingual_metrics"][f"ID-{lp.upper()}"] = {
                "count": len(lp_results),
                "avg_cosine": float(round(avg_cos, 4)),
                "avg_similarity_pct": float(round(avg_cos * 100.0, 2)),
                "passed_at_0_75": f"{detected_075}/{len(lp_results)}",
                "status": "VALIDATED" if avg_cos >= 0.75 else "MARGINAL"
            }

    # Catat kasus kegagalan / false positive / false negative terbuka
    for r in results:
        # Jika ada ambang yang menghasilkan FP atau FN
        fp_thresholds = [th for th in ["0.7", "0.75", "0.8"] if r["decisions"][th]["confusion"] == "FP"]
        fn_thresholds = [th for th in ["0.7", "0.75", "0.8"] if r["decisions"][th]["confusion"] == "FN"]
        if fp_thresholds or fn_thresholds:
            summary["mismatches_and_failures"].append({
                "case_id": r["case_id"],
                "group": r["group"],
                "domain": r["domain"],
                "is_plagiarized": r["is_plagiarized"],
                "semantic_cosine": float(r["semantic_cosine"]),
                "lexical_similarity_pct": float(r["lexical_similarity_pct"]),
                "fp_thresholds": fp_thresholds,
                "fn_thresholds": fn_thresholds
            })

    # Cetak Hasil Ringkasan
    print("\n" + "=" * 70)
    print("HASIL KALIBRASI TIGA AMBANG SEMANTIK (ANTI-SALAH-TUDUH)")
    print("=" * 70)
    print(f"{'Ambang':<8} | {'Presisi':<10} | {'Recall':<10} | {'F1-Score':<10} | {'FP Dokumen Bersih':<20} | {'Akurasi Total':<12}")
    print("-" * 75)
    for th in thresholds:
        m = summary["threshold_metrics"][str(th)]
        print(f"{th:<8} | {m['precision_pct']:>8.1f}% | {m['recall_pct']:>8.1f}% | {m['f1_score']:>8.4f} | {m['clean_false_positive_rate_pct']:>17.1f}% | {m['accuracy_pct']:>10.1f}%")

    print("\n" + "=" * 70)
    print("PERBANDINGAN DAYA TANGKAP: LEKSIKAL VS SEMANTIK (MiniLM-L12)")
    print("=" * 70)
    print(f"{'Grup Evaluasi':<25} | {'Rata Leksikal':<14} | {'Rata Semantik':<14} | {'Deteksi @ 0.75':<14}")
    print("-" * 75)
    for g, b in summary["group_breakdown"].items():
        print(f"{g:<25} | {b['avg_lexical_similarity_pct']:>12.1f}% | {b['avg_semantic_similarity_pct']:>12.1f}% | {b['detection_rate_at_0_75']:>12.1f}%")

    print("\n" + "=" * 70)
    print("VALIDASI LINTAS BAHASA (ZERO-SHOT CROSS-LINGUAL)")
    print("=" * 70)
    for pair, data in summary["cross_lingual_metrics"].items():
        print(f"Pasangan {pair:<8}: Rata Kosinus = {data['avg_cosine']:.4f} ({data['avg_similarity_pct']}%) -> Lolos: {data['passed_at_0_75']} [{data['status']}]")

    print("\n" + "=" * 70)
    print(f"TRANSPARANSI KASUS KEGAGALAN / BATAS: {len(summary['mismatches_and_failures'])} KASUS")
    print("=" * 70)
    for m in summary["mismatches_and_failures"]:
        print(f"* [{m['case_id']}] {m['group']} ({m['domain']}): Cosine={m['semantic_cosine']:.4f} (Leksikal={m['lexical_similarity_pct']}%)")
        if m['fp_thresholds']:
            print(f"    -> ALARM PALSU (False Positive) pada ambang: {m['fp_thresholds']}")
        if m['fn_thresholds']:
            print(f"    -> LOLOS (False Negative) pada ambang: {m['fn_thresholds']}")

    # Simpan hasil
    out_file = Path(_parent_dir) / "datasets" / "artilik_semantic_calibration_report.json"
    with open(out_file, "w", encoding="utf-8") as f:
        json.dump({
            "summary": summary,
            "cases_evaluated": results
        }, f, ensure_ascii=False, indent=2)

    public_out = Path(_parent_dir) / "public" / "datasets" / "artilik_semantic_calibration_report.json"
    with open(public_out, "w", encoding="utf-8") as f:
        json.dump({
            "summary": summary,
            "cases_evaluated": results
        }, f, ensure_ascii=False, indent=2)

    print(f"\n[✓] Laporan kalibrasi lengkap disimpan di:\n    {out_file}\n    {public_out}")


if __name__ == "__main__":
    run_semantic_calibration()
