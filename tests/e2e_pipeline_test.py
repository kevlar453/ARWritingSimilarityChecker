#!/usr/bin/env python3
"""
AR Tilik - End-to-End Regression Pipeline Test
Menguji 50 kasus benchmark standar melalui rantai produksi penuh:
Dataset -> HTTP Upload API -> Document Processing -> Worker Daemon -> DB -> View/HTML Rendering
Memverifikasi 100% konsistensi hasil terhadap baseline Run #21 (Macro F1 = 1.0000).
"""

import sys
import time
import json
import tempfile
import urllib.request
import urllib.parse
from pathlib import Path
from collections import Counter

# Tambahkan root directory ke sys.path
sys.path.append(str(Path(__file__).resolve().parents[1]))

from benchmark.database import get_connection
from benchmark.dataset import generate_dataset
from benchmark.metrics import calculate_binary_metrics, calculate_multiclass_metrics

BASE_URL = "http://localhost:8085"

def post_multipart(url: str, fields: dict, files: dict) -> dict:
    """Helper untuk mengirim multipart/form-data tanpa dependensi eksternal."""
    boundary = "----WebKitFormBoundaryArtilikE2ETest"
    body = bytearray()
    
    for key, val in fields.items():
        body.extend(f"--{boundary}\r\n".encode("utf-8"))
        body.extend(f'Content-Disposition: form-data; name="{key}"\r\n\r\n'.encode("utf-8"))
        body.extend(f"{val}\r\n".encode("utf-8"))
        
    for key, (filename, content, mime) in files.items():
        body.extend(f"--{boundary}\r\n".encode("utf-8"))
        body.extend(f'Content-Disposition: form-data; name="{key}"; filename="{filename}"\r\n'.encode("utf-8"))
        body.extend(f"Content-Type: {mime}\r\n\r\n".encode("utf-8"))
        body.extend(content if isinstance(content, bytes) else content.encode("utf-8"))
        body.extend(b"\r\n")
        
    body.extend(f"--{boundary}--\r\n".encode("utf-8"))
    
    req = urllib.request.Request(
        url,
        data=bytes(body),
        headers={"Content-Type": f"multipart/form-data; boundary={boundary}"},
        method="POST"
    )
    with urllib.request.urlopen(req, timeout=15) as response:
        return json.loads(response.read().decode("utf-8"))

def post_form(url: str, data: dict) -> dict:
    encoded = urllib.parse.urlencode(data).encode("utf-8")
    req = urllib.request.Request(
        url,
        data=encoded,
        headers={"Content-Type": "application/x-www-form-urlencoded"},
        method="POST"
    )
    with urllib.request.urlopen(req, timeout=15) as response:
        return json.loads(response.read().decode("utf-8"))

def get_html(url: str) -> tuple[int, str]:
    req = urllib.request.Request(url, headers={"User-Agent": "AR-Tilik-E2E-Tester"})
    with urllib.request.urlopen(req, timeout=15) as response:
        status_code = response.getcode()
        html = response.read().decode("utf-8")
        return status_code, html

def wait_for_report(report_id: int, max_wait_sec: int = 25) -> dict:
    conn = get_connection()
    conn.autocommit = True
    start = time.time()
    try:
        while time.time() - start < max_wait_sec:
            cursor = conn.cursor(dictionary=True)
            cursor.execute("SELECT * FROM reports WHERE id = %s", (report_id,))
            row = cursor.fetchone()
            cursor.close()
            if row and row["status"] in ("completed", "failed"):
                return row
            time.sleep(0.4)
        raise TimeoutError(f"Report #{report_id} tidak selesai dalam {max_wait_sec} detik.")
    finally:
        conn.close()

def run_e2e_regression(cleanup: bool = False):
    print("=" * 70)
    print("       AR TILIK - END-TO-END PIPELINE REGRESSION TEST")
    print("=" * 70)
    print("Target: 50 Kasus Uji melalui HTTP API -> Worker -> DB -> HTML View")
    print(f"Server Target: {BASE_URL}")
    print()

    # 1. Pemuatan dataset 50 kasus
    dataset = generate_dataset()
    print(f"[*] Berhasil memuat {len(dataset)} kasus uji dari benchmark dataset.")

    # 2. Muat baseline Run #21 untuk validasi perbandingan
    baseline_path = Path(__file__).resolve().parents[1] / "benchmark" / "baseline_hybrid_v1.json"
    with open(baseline_path) as f:
        baseline = json.load(f)
    baseline_cases = {c["case_name"]: c for c in baseline["cases"]}

    results = []
    doc_ids = []
    print("[*] Memulai eksekusi alur produksi (Upload -> Check -> Worker -> HTML)...")

    for idx, case in enumerate(dataset, start=1):
        name = case["name"]
        cat = case["category"]
        exp = case["expected"]
        submission_text = case["submission"]

        # A. HTTP POST Upload File
        filename = f"{name}.txt"
        upload_resp = post_multipart(
            f"{BASE_URL}/api/documents",
            fields={"corpus_visibility": "private"},
            files={"document": (filename, submission_text, "text/plain")}
        )
        doc_id = upload_resp["document_id"]
        doc_ids.append(doc_id)

        # B. HTTP POST Check API (Hybrid Engine)
        check_resp = post_form(
            f"{BASE_URL}/api/documents/{doc_id}/check",
            data={"engine": "hybrid"}
        )
        report_id = check_resp["report_id"]

        # C. Menunggu Worker Menyelesaikan Job
        rep = wait_for_report(report_id, max_wait_sec=25)
        if rep["status"] == "failed":
            raise RuntimeError(f"Report #{report_id} gagal diproses worker: {rep.get('error_message')}")

        pred_decision = rep["predicted_decision"]
        pattern = rep["evidence_pattern"]
        overall_sim = float(rep["overall_similarity"])
        exact_sim = float(rep["exact_similarity"])
        cosine_sim = float(rep["cosine_similarity"])

        # D. HTTP GET HTML View Verification
        html_url = f"{BASE_URL}/dashboard/report/{report_id}"
        status_code, html_body = get_html(html_url)

        # Validasi HTML Elemen Utama
        assert status_code == 200, f"HTML status bukan 200: {status_code}"
        assert "HYBRID SIMILARITY RESULT" in html_body, "Komponen HYBRID SIMILARITY RESULT tidak ditemukan di HTML"
        assert "EXACT EVIDENCE" in html_body, "Komponen EXACT EVIDENCE tidak ditemukan di HTML"
        assert "TF-IDF EVIDENCE" in html_body, "Komponen TF-IDF EVIDENCE tidak ditemukan di HTML"
        assert "WHY THIS RESULT?" in html_body, "Komponen WHY THIS RESULT tidak ditemukan di HTML"
        assert (
            pred_decision.upper() in html_body
            or pred_decision.upper().replace("_", " ") in html_body
            or f'data-decision="{pred_decision}"' in html_body
        ), f"Keputusan {pred_decision} tidak muncul di HTML"

        # E. Bandingkan dengan Baseline Run #21
        base_match = baseline_cases.get(name)
        assert base_match is not None, f"Kasus {name} tidak ada di baseline Run #21"
        assert base_match["expected_result"] == exp
        
        is_correct = (pred_decision == exp)
        status_sym = "✓" if is_correct else "✗"
        
        print(f" [{idx:02d}/50] {status_sym} {name} ({cat:<14}) -> Pred: {pred_decision.upper():<8} | Exact: {exact_sim:>5.1f}% | Cosine: {cosine_sim:>5.1f}% | Pattern: {pattern}")

        results.append({
            "name": name,
            "category": cat,
            "expected": exp,
            "predicted": pred_decision,
            "pattern": pattern,
            "exact_similarity": exact_sim,
            "cosine_similarity": cosine_sim,
            "overall_similarity": overall_sim,
            "report_id": report_id,
            "is_correct": is_correct
        })

    # Evaluasi Metrik
    metric_input = [{"expected": r["expected"], "predicted": r["predicted"]} for r in results]
    binary = calculate_binary_metrics(metric_input)
    multiclass = calculate_multiclass_metrics(metric_input)

    print()
    print("=" * 70)
    print("         E2E PIPELINE REGRESSION TEST EVALUATION REPORT")
    print("=" * 70)
    print(f"Total Kasus Uji  : {len(results)}")
    print(f"Binary F1 Score  : {binary['f1']:.4f}")
    print(f"Macro F1 Score   : {multiclass['macro_f1']:.4f}")
    print()
    print("Matriks Konfusi (Confusion Matrix):")
    print("EXPECTED \\ PRED      MATCH      REVIEW    NO_MATCH")
    print("-" * 50)
    cm = multiclass["matrix"]
    for cat in ["match", "review", "no_match"]:
        row = cm.get(cat, {})
        print(f"{cat:<20} {row.get('match', 0):>5}       {row.get('review', 0):>5}       {row.get('no_match', 0):>5}")

    misclassified = [r for r in results if not r["is_correct"]]
    print("-" * 50)
    print(f"Total Salah Klasifikasi: {len(misclassified)}")

    # Cleanup jika diminta
    if cleanup and doc_ids:
        print("\n[*] Menjalankan pembersihan dokumen uji sementara...")
        cleanup_test_documents(doc_ids)

    if misclassified:
        print("\n[!] Ditemukan kasus salah:")
        for m in misclassified:
            print(f" - {m['name']} ({m['category']}): Expected={m['expected']}, Predicted={m['predicted']}")
        sys.exit(1)
    else:
        print("\n[✓] SUKSES! 50/50 Kasus Uji Melalui Pipeline Penuh Menghasilkan Keputusan 100% Identik dengan Run #21.")
        print("[✓] Rantai Dataset -> Upload API -> Worker -> DB -> HTML View TERVERIFIKASI UTUH.")
        sys.exit(0)

def cleanup_test_documents(doc_ids: list[int]):
    if not doc_ids:
        return
    conn = get_connection()
    try:
        cursor = conn.cursor()
        format_strings = ','.join(['%s'] * len(doc_ids))
        cursor.execute(f"DELETE FROM report_matches WHERE report_id IN (SELECT id FROM reports WHERE document_id IN ({format_strings}))", tuple(doc_ids))
        cursor.execute(f"DELETE FROM report_sources WHERE report_id IN (SELECT id FROM reports WHERE document_id IN ({format_strings}))", tuple(doc_ids))
        cursor.execute(f"DELETE FROM reports WHERE document_id IN ({format_strings})", tuple(doc_ids))
        cursor.execute(f"DELETE FROM fingerprint_documents WHERE document_id IN ({format_strings})", tuple(doc_ids))
        cursor.execute(f"DELETE FROM fingerprints WHERE document_id IN ({format_strings})", tuple(doc_ids))
        cursor.execute(f"DELETE FROM document_chunks WHERE document_id IN ({format_strings})", tuple(doc_ids))
        cursor.execute(f"DELETE FROM document_pages WHERE document_id IN ({format_strings})", tuple(doc_ids))
        cursor.execute(f"DELETE FROM documents WHERE id IN ({format_strings})", tuple(doc_ids))
        conn.commit()
        cursor.close()
        print(f"[✓] Berhasil membersihkan {len(doc_ids)} dokumen uji sementara dari database.")
    except Exception as e:
        print(f"[!] Peringatan: Gagal membersihkan dokumen sementara: {e}")
    finally:
        conn.close()

if __name__ == "__main__":
    import argparse
    parser = argparse.ArgumentParser(description="AR Tilik E2E Pipeline Regression Test")
    parser.add_argument("--cleanup", action="store_true", help="Bersihkan dokumen uji dari database setelah pengujian selesai")
    args = parser.parse_args()
    run_e2e_regression(cleanup=args.cleanup)

