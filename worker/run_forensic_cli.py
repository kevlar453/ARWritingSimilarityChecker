#!/usr/bin/env python3
"""
AR Tilik - CLI Runner for On-Demand Semantic Forensic Audit
Dipanggil oleh DashboardController ketika pengguna/admin memicu audit forensik cerdas.
"""

import sys
import os
import json

# Pastikan root direktori ada di sys.path dan subfolder worker tidak menimpa root namespace
BASE_DIR = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
SCRIPT_DIR = os.path.dirname(os.path.abspath(__file__))
while SCRIPT_DIR in sys.path:
    sys.path.remove(SCRIPT_DIR)
if BASE_DIR in sys.path:
    sys.path.remove(BASE_DIR)
sys.path.insert(0, BASE_DIR)

from worker.database import get_connection
from worker.ai_gateway import enqueue_narrative_job, execute_job_with_gateway
from worker.section_parser import parse_document_sections, get_substantive_including_methodology
from worker.extractor import extract_document_metadata
from worker.citation_auditor import audit_citations_and_bibliography


def main():
    if len(sys.argv) < 2:
        print(json.dumps({"success": False, "error": "Usage: run_forensic_cli.py <report_id>"}))
        sys.exit(1)

    report_id = int(sys.argv[1])
    conn = get_connection()
    cursor = conn.cursor(dictionary=True)

    try:
        # Ambil data report dan dokumen
        cursor.execute("SELECT id, document_id, evidence_data FROM reports WHERE id = %s", (report_id,))
        report = cursor.fetchone()
        if not report:
            print(json.dumps({"success": False, "error": f"Laporan #{report_id} tidak ditemukan"}))
            sys.exit(1)

        doc_id = report["document_id"]
        cursor.execute("SELECT id, title, storage_path, mime_type FROM documents WHERE id = %s", (doc_id,))
        doc = cursor.fetchone()
        if not doc:
            print(json.dumps({"success": False, "error": f"Dokumen #{doc_id} tidak ditemukan"}))
            sys.exit(1)

        # Ambil teks naskah dari chunks
        cursor.execute(
            "SELECT text FROM document_chunks WHERE document_id = %s ORDER BY chunk_index ASC",
            (doc_id,)
        )
        chunks = cursor.fetchall() or []
        submission_text = " ".join(c["text"] for c in chunks if c.get("text"))

        if not submission_text and doc.get("storage_path") and os.path.exists(doc["storage_path"]):
            from worker.extractor import extract_document
            pages = extract_document(doc["storage_path"], doc.get("mime_type"))
            submission_text = " ".join(p["text"] for p in pages if p.get("text"))

        if not submission_text:
            print(json.dumps({"success": False, "error": "Teks naskah kosong"}))
            sys.exit(1)

        # Segmentasi seksi untuk mengekstrak isi substantif
        substantive_body = ""
        try:
            seg = parse_document_sections(submission_text)
            if seg:
                substantive_body = get_substantive_including_methodology(seg)
        except Exception:
            substantive_body = submission_text[:25000]

        # Ekstrak metadata dokumen untuk mendeteksi era pra-LLM
        doc_meta = None
        if doc.get("storage_path") and os.path.exists(doc["storage_path"]):
            try:
                doc_meta = extract_document_metadata(doc["storage_path"], doc.get("mime_type"))
            except Exception:
                doc_meta = None

        # Jalankan audit melalui AI Gateway (Cache -> L1 Groq -> L2 Gemini -> L3 Degradasi Jujur)
        enq = enqueue_narrative_job(
            conn=conn,
            submission_id=doc_id,
            report_id=report_id,
            document_text=submission_text,
            substantive_text=substantive_body,
            title=doc.get("title", ""),
            doc_metadata=doc_meta
        )
        if enq.get("cache_hit"):
            audit_res = enq.get("result")
        else:
            job_id = enq["job_id"]
            cursor.execute("SELECT * FROM ai_narrative_jobs WHERE id = %s", (job_id,))
            job_row = cursor.fetchone()
            if job_row:
                audit_res = execute_job_with_gateway(job_row, conn)
            else:
                audit_res = {"status": "deferred", "is_available": False, "message": "Pekerjaan tidak ditemukan"}

        # Update evidence_data di reports
        evidence = {}
        if report.get("evidence_data"):
            try:
                evidence = json.loads(report["evidence_data"]) if isinstance(report["evidence_data"], str) else report["evidence_data"]
            except Exception:
                evidence = {}

        evidence["gemini_forensic"] = audit_res
        if doc_meta:
            evidence["document_metadata"] = doc_meta

        # Perbarui juga audit konsistensi sitasi dengan parser terbaru
        try:
            citation_audit_res = audit_citations_and_bibliography(submission_text, seg)
            evidence["citation_audit"] = citation_audit_res.to_dict()
        except Exception:
            pass

        # Sinkronkan juga ke v1_1_presentation.v1_2_evolution jika ada
        if "v1_1_presentation" in evidence and isinstance(evidence["v1_1_presentation"], dict):
            if "v1_2_evolution" in evidence["v1_1_presentation"] and isinstance(evidence["v1_1_presentation"]["v1_2_evolution"], dict):
                evidence["v1_1_presentation"]["v1_2_evolution"]["gemini_forensic"] = audit_res
                if doc_meta:
                    evidence["v1_1_presentation"]["v1_2_evolution"]["document_metadata"] = doc_meta
                if "citation_audit" in evidence:
                    evidence["v1_1_presentation"]["v1_2_evolution"]["citation_audit"] = evidence["citation_audit"]

        cursor.execute(
            "UPDATE reports SET evidence_data = %s, updated_at = NOW() WHERE id = %s",
            (json.dumps(evidence), report_id)
        )
        conn.commit()

        print(json.dumps({"success": True, "data": audit_res}))
    except Exception as e:
        print(json.dumps({"success": False, "error": str(e)}))
        sys.exit(1)
    finally:
        cursor.close()
        conn.close()


if __name__ == "__main__":
    main()
