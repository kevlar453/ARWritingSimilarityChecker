"""
AR Tilik - Kalibrasi false-positive indikasi AI pada dokumen PRA-LLM.

Dokumen yang dibuat sebelum 30 Nov 2022 pasti bukan buatan AI generatif, sehingga setiap
indikasi AI pada dokumen tersebut adalah false positive. Skrip ini mengukur seberapa sering
modul stylometri menandai dokumen-dokumen itu pada beberapa ambang, lalu menyarankan ambang
yang menjaga false-positive-rate di bawah target.

Pemakaian (dari root proyek):
    python -m evaluation.calibrate_pre_llm --dir /path/ke/skripsi_lama --target-fpr 0.02
    python -m evaluation.calibrate_pre_llm --dir /path --csv hasil.csv --only-pre-llm

Catatan:
- Hanya menguji stylometri (offline, tanpa DB/Gemini). Untuk menguji audit Gemini pada set yang
  sama, panggil audit_proposal_with_gemini() per dokumen dan bandingkan tingkat_indikasi_ai.
- --only-pre-llm membuang berkas yang metadatanya TIDAK terbukti pra-2022, agar kontrol negatif bersih.
"""
import argparse
import csv
import sys
from pathlib import Path

# Pastikan root direktori ada dalam sys.path
REPO_ROOT = Path(__file__).resolve().parent.parent
if str(REPO_ROOT) not in sys.path:
    sys.path.insert(0, str(REPO_ROOT))

from worker.extractor import extract_document, extract_document_metadata
from worker.section_parser import parse_document_sections, get_substantive_including_methodology
from worker.ai_stylometrics import analyze_ai_stylometrics

SUPPORTED = {".docx", ".pdf", ".txt"}
THRESHOLDS = (25.5, 35.0, 50.0, 70.0)  # P95 manusia, ambang dashboard MODERATE, MODERATE modul, HIGH dashboard


def percentile(values, q):
    if not values:
        return 0.0
    vals = sorted(values)
    k = (len(vals) - 1) * q
    lo, hi = int(k), min(int(k) + 1, len(vals) - 1)
    return vals[lo] + (vals[hi] - vals[lo]) * (k - lo)


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument("--dir", required=True, help="Folder berisi dokumen kontrol negatif (pra-2022)")
    ap.add_argument("--csv", help="Simpan hasil per dokumen ke CSV")
    ap.add_argument("--target-fpr", type=float, default=0.02, help="Target false-positive-rate (default 2%%)")
    ap.add_argument("--only-pre-llm", action="store_true", help="Hanya pakai berkas dengan metadata pra-LLM")
    args = ap.parse_args()

    files = sorted(p for p in Path(args.dir).rglob("*") if p.suffix.lower() in SUPPORTED)
    if not files:
        sys.exit(f"Tidak ada dokumen {sorted(SUPPORTED)} di {args.dir}")

    rows = []
    for f in files:
        try:
            meta = extract_document_metadata(str(f))
            if args.only_pre_llm and not meta["is_pre_llm_era"]:
                continue
            pages = extract_document(str(f))
            text = " ".join(p["text"] for p in pages if p.get("text"))
            seg = parse_document_sections(text)
            body = get_substantive_including_methodology(seg)
            res = analyze_ai_stylometrics(text, body)
            m = res["metrics"]
            rows.append({
                "file": f.name,
                "created": (meta["created"] or "")[:10],
                "pre_llm_meta": meta["is_pre_llm_era"],
                "words": m.get("total_words_analyzed", 0),
                "ai_score": res["ai_probability_score"],
                "burstiness": m.get("burstiness"),
                "transition_density": m.get("transition_density"),
                "monotony_ratio": m.get("segmental_monotony_ratio"),
            })
        except Exception as e:  # satu berkas rusak tidak boleh menghentikan kalibrasi
            print(f"[lewati] {f.name}: {e}", file=sys.stderr)

    if not rows:
        sys.exit("Tidak ada dokumen yang dapat dianalisis.")

    scores = [r["ai_score"] for r in rows]
    n = len(scores)
    print(f"\nDokumen kontrol negatif dianalisis : {n}")
    print(f"Skor stylometri  rata-rata={statistics.mean(scores):.1f}  median={statistics.median(scores):.1f}  "
          f"P95={percentile(scores, .95):.1f}  maks={max(scores):.1f}")
    print("\nAmbang  ->  false positive")
    for t in THRESHOLDS:
        fp = sum(1 for s in scores if s >= t)
        print(f"  >= {t:5.1f}  ->  {fp:3d}/{n}  ({fp / n * 100:5.1f}%)")

    # Ambang terkecil yang menjaga FPR <= target
    candidate = next((t for t in sorted(set(scores + [0.0])) if sum(1 for s in scores if s > t) / n <= args.target_fpr), max(scores))
    print(f"\nAmbang minimum agar FPR <= {args.target_fpr * 100:.1f}%: skor > {candidate:.1f}")
    print("Bandingkan dengan STYLO_CORROBORATION_MIN (DashboardController) dan ambang 35/70 di extractAiMetrics.")
    if n < 50:
        print(f"PERINGATAN: baru {n} dokumen; gunakan >= 50 agar estimasi FPR layak dipercaya.")

    worst = sorted(rows, key=lambda r: r["ai_score"], reverse=True)[:5]
    print("\n5 skor tertinggi (periksa manual):")
    for r in worst:
        print(f"  {r['ai_score']:5.1f}  {r['file']}  (burstiness={r['burstiness']}, transisi={r['transition_density']})")

    if args.csv:
        with open(args.csv, "w", newline="", encoding="utf-8") as fh:
            w = csv.DictWriter(fh, fieldnames=list(rows[0].keys()))
            w.writeheader()
            w.writerows(rows)
        print(f"\nCSV disimpan: {args.csv}")


if __name__ == "__main__":
    main()
