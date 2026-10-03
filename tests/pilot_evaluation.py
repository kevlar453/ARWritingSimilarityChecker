import json
import time
import urllib.request
from pathlib import Path

BASE_URL = "http://localhost:8085"
PDF_PATH = "/home/humanitarianism/.gemini/antigravity/brain/b3a33caa-d57b-42c4-8bcc-f2c0475717f3/.user_uploaded/media_1791015799802.pdf"

def run_pilot():
    print("======================================================================")
    print("      AR TILIK — REAL-WORLD PILOT EVALUATION (174-PAGE THESIS)        ")
    print("======================================================================")
    
    if not Path(PDF_PATH).exists():
        print(f"[!] File not found: {PDF_PATH}")
        return

    file_size_mb = Path(PDF_PATH).stat().st_size / (1024 * 1024)
    print(f"[*] Berkas Uji: Tesis Magister Teologi (Emiliana Vivi, 2026)")
    print(f"[*] Lokasi     : {PDF_PATH}")
    print(f"[*] Ukuran     : {file_size_mb:.2f} MB")
    print()

    def post_multipart(url: str, fields: dict, files: dict):
        boundary = "----WebKitFormBoundaryArtilikProdTest"
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
        with urllib.request.urlopen(req, timeout=30) as response:
            return response.getcode(), json.loads(response.read().decode("utf-8"))

    # 1. Upload via HTTP API
    print("[1] Mengunggah dokumen melalui HTTP API (POST /api/documents)...")
    with open(PDF_PATH, "rb") as f:
        pdf_bytes = f.read()

    t0_upload = time.perf_counter()
    code, upload_res = post_multipart(
        f"{BASE_URL}/api/documents",
        {"corpus_visibility": "private"},
        {"document": ("Tesis_Emiliana_Vivi_2026.pdf", pdf_bytes, "application/pdf")}
    )
    upload_duration = time.perf_counter() - t0_upload

    doc_id = upload_res["document_id"]
    print(f" [✓] Dokumen tersimpan: Doc ID #{doc_id} dalam {upload_duration:.3f}s")
    print()

    # 2. Queue Similarity Check
    print("[2] Memicu similarity check (POST /api/documents/{id}/check)...")
    req_check = urllib.request.Request(
        f"{BASE_URL}/api/documents/{doc_id}/check",
        data=b"engine=hybrid",
        headers={"Content-Type": "application/x-www-form-urlencoded"},
        method="POST"
    )
    with urllib.request.urlopen(req_check) as resp:
        check_res = json.loads(resp.read().decode("utf-8"))

    report_id = check_res["report_id"]
    print(f" [✓] Antrean terdaftar: Report ID #{report_id}, Status: {check_res['status']}")
    print()

    # 3. Poll for Worker Processing
    print("[3] Menunggu worker menyelesaikan analisis...")
    t0_work = time.perf_counter()
    max_wait = 120
    poll_interval = 1.0
    report_data = None

    while time.perf_counter() - t0_work < max_wait:
        req_report = urllib.request.Request(f"{BASE_URL}/api/reports/{report_id}", method="GET")
        with urllib.request.urlopen(req_report) as resp:
            report_res = json.loads(resp.read().decode("utf-8"))
            data = report_res.get("data", {})
            if data.get("status") in ["completed", "failed"]:
                report_data = data
                break
        time.sleep(poll_interval)
        print(f"  ... memproses (elapsed: {time.perf_counter() - t0_work:.1f}s)", end="\r")

    print()
    total_elapsed = time.perf_counter() - t0_work

    if not report_data or report_data.get("status") != "completed":
        print(f"[!] Gagal atau timeout: {report_data}")
        return

    print(f" [✓] Worker selesai dalam {total_elapsed:.2f} detik!")
    print()

    # 4. Display Report Breakdown
    print("======================================================================")
    print("         HASIL EVALUASI PILOT — LAPORAN EVIDENCE DECOMPOSITION        ")
    print("======================================================================")
    print(f"Report ID           : #{report_data['id']}")
    print(f"Document ID         : #{report_data['document_id']}")
    print(f"Engine              : {report_data['engine'].upper()}")
    print(f"Predicted Decision  : {report_data['predicted_decision'].upper()}")
    print(f"Evidence Pattern    : {report_data['evidence_pattern']}")
    print(f"Evidence Agreement  : {report_data['evidence_agreement']}")
    print(f"Sources Agree       : {'YA' if report_data['sources_agree'] else 'TIDAK / N/A'}")
    print(f"Overall Similarity  : {float(report_data['overall_similarity']):.2f}%")
    print(f"Exact Similarity    : {float(report_data['exact_similarity']):.2f}%")
    print(f"Cosine Similarity   : {float(report_data['cosine_similarity']):.2f}%")
    print(f"Longest Match       : {report_data['longest_match_words']} kata")
    print(f"Shared Terms Count  : {report_data['shared_terms_count']}")
    print(f"Average Shared IDF  : {float(report_data['average_shared_term_idf'] or 0):.4f}")
    print(f"Source Count        : {report_data['source_count']}")
    print(f"Match Count         : {report_data['match_count']}")
    print()
    print("PENJELASAN ILMIAH (EXPLAINABILITY RATIONALE):")
    print(f"\"{report_data['rationale']}\"")
    print()

    # 5. Fetch Sources
    req_src = urllib.request.Request(f"{BASE_URL}/api/reports/{report_id}/sources", method="GET")
    with urllib.request.urlopen(req_src) as resp:
        src_res = json.loads(resp.read().decode("utf-8"))
        sources = src_res.get("data", [])

    print(f"DAFTAR SUMBER RUJUKAN TERDETEKSI ({len(sources)} sumber):")
    for s in sources[:5]:
        print(f"  - [{s.get('source_rank', 0)}] Doc #{s['source_document_id']}: {s.get('title', 'Unknown')} "
              f"| Sim: {float(s['similarity']):.1f}% (Exact: {float(s.get('exact_similarity', 0)):.1f}%, Cosine: {float(s.get('cosine_similarity', 0)):.1f}%)")
    print()

    # 6. Fetch Matches
    req_mat = urllib.request.Request(f"{BASE_URL}/api/reports/{report_id}/matches", method="GET")
    with urllib.request.urlopen(req_mat) as resp:
        mat_res = json.loads(resp.read().decode("utf-8"))
        matches = mat_res.get("data", [])

    print(f"RINCIAN PASASE COCOK ({len(matches)} pasase):")
    for m in matches[:5]:
        print(f"  - Submisi Kata [{m['submission_start']}:{m['submission_end']}] ({m['word_count']} kata) "
              f"vs Doc #{m['source_document_id']} | Type: {m['match_type']} | Max IDF: {m.get('max_idf')} | Signif: {m.get('significance')}")

    print("======================================================================")
    print(f"[✓] Pilot URL Laporan Web: {BASE_URL}/dashboard/report/{report_id}")
    print("======================================================================")

if __name__ == "__main__":
    run_pilot()
