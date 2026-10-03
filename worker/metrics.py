import json
import sys
from pathlib import Path

# Ensure root directory is on PYTHONPATH
sys.path.append(str(Path(__file__).resolve().parents[1]))

from worker.database import get_connection

def display_metrics():
    conn = get_connection()
    try:
        cursor = conn.cursor(dictionary=True)

        # 1. Jobs Status
        cursor.execute("SELECT status, COUNT(*) as cnt FROM jobs GROUP BY status")
        job_counts = {r["status"]: r["cnt"] for r in cursor.fetchall()}
        total_jobs = sum(job_counts.values())

        # 2. Document Counts
        cursor.execute("SELECT document_type, COUNT(*) as cnt FROM documents GROUP BY document_type")
        doc_counts = {r["document_type"]: r["cnt"] for r in cursor.fetchall()}
        total_docs = sum(doc_counts.values())

        # 3. Reports Status
        cursor.execute("SELECT status, COUNT(*) as cnt FROM reports GROUP BY status")
        report_counts = {r["status"]: r["cnt"] for r in cursor.fetchall()}
        total_reports = sum(report_counts.values())
        completed_reports = report_counts.get("completed", 0)
        failed_reports = report_counts.get("failed", 0)

        # 4. Decision Distribution
        cursor.execute("""
            SELECT predicted_decision, COUNT(*) as cnt 
            FROM reports 
            WHERE status = 'completed' AND predicted_decision IS NOT NULL
            GROUP BY predicted_decision
        """)
        decisions = {r["predicted_decision"]: r["cnt"] for r in cursor.fetchall()}
        total_decisions = sum(decisions.values()) or 1

        # 5. Evidence Patterns
        cursor.execute("""
            SELECT evidence_pattern, COUNT(*) as cnt 
            FROM reports 
            WHERE status = 'completed' AND evidence_pattern IS NOT NULL
            GROUP BY evidence_pattern
            ORDER BY cnt DESC
        """)
        patterns = [(r["evidence_pattern"], r["cnt"]) for r in cursor.fetchall()]

        # 6. Latency & Execution Time
        cursor.execute("""
            SELECT id, evidence_data 
            FROM reports 
            WHERE status = 'completed' AND evidence_data IS NOT NULL 
            ORDER BY id DESC 
            LIMIT 200
        """)
        latencies = []
        for r in cursor.fetchall():
            try:
                data = json.loads(r["evidence_data"])
                if "execution_time_seconds" in data:
                    latencies.append(float(data["execution_time_seconds"]))
            except Exception:
                pass

        # 7. Recent 5 Submissions
        cursor.execute("""
            SELECT r.id, r.document_id, d.title, d.original_filename, r.engine, 
                   r.predicted_decision, r.evidence_pattern, r.exact_similarity, 
                   r.cosine_similarity, r.status, r.created_at, r.completed_at,
                   r.evidence_data
            FROM reports r
            LEFT JOIN documents d ON r.document_id = d.id
            ORDER BY r.id DESC
            LIMIT 5
        """)
        recent_reports = cursor.fetchall()

        # Display Report
        print("======================================================================")
        print("         AR TILIK — SYSTEM OBSERVABILITY & BASELINE METRICS           ")
        print("======================================================================")
        print()
        print("[1] QUEUE & WORKER HEALTH")
        print("----------------------------------------------------------------------")
        print(f"Total Jobs Ingested  : {total_jobs}")
        print(f"  - Pending          : {job_counts.get('pending', 0)}")
        print(f"  - Processing       : {job_counts.get('processing', 0)}")
        print(f"  - Completed        : {job_counts.get('completed', 0)}")
        print(f"  - Failed           : {job_counts.get('failed', 0)}")
        worker_state = "IDLE (Antrean Kosong)" if job_counts.get('pending', 0) == 0 and job_counts.get('processing', 0) == 0 else "ACTIVE (Memproses Antrean)"
        print(f"Status Worker        : {worker_state}")
        print()

        print("[2] INVENTORY DOKUMEN & LAPORAN")
        print("----------------------------------------------------------------------")
        print(f"Total Dokumen        : {total_docs}")
        print(f"  - Dokumen Submisi  : {doc_counts.get('submission', 0)}")
        print(f"  - Korpus Rujukan   : {doc_counts.get('corpus', 0)}")
        print(f"Total Laporan        : {total_reports}")
        print(f"  - Sukses (Completed: {completed_reports} ({completed_reports/max(1, total_reports)*100:.1f}%)")
        print(f"  - Gagal (Failed)   : {failed_reports} ({failed_reports/max(1, total_reports)*100:.1f}%)")
        print()

        print("[3] DISTRIBUSI KEPUTUSAN (ACADEMIC FAIRNESS)")
        print("----------------------------------------------------------------------")
        m_cnt = decisions.get("match", 0)
        r_cnt = decisions.get("review", 0)
        n_cnt = decisions.get("no_match", 0)
        print(f"MATCH                : {m_cnt:4d} ({m_cnt/total_decisions*100:5.1f}%) [Kesamaan verbatim sekuensial tinggi]")
        print(f"REVIEW               : {r_cnt:4d} ({r_cnt/total_decisions*100:5.1f}%) [Kutipan, frasa umum, atau parafrasa]")
        print(f"NO_MATCH             : {n_cnt:4d} ({n_cnt/total_decisions*100:5.1f}%) [Tidak ditemukan bukti tekstual]")
        print()

        print("[4] DISTRIBUSI POLA BUKTI (EVIDENCE PATTERNS)")
        print("----------------------------------------------------------------------")
        for pat, cnt in patterns:
            print(f"  - {pat:<24}: {cnt:4d} ({cnt/total_decisions*100:5.1f}%)")
        print()

        print("[5] PERFORMA & LATENSI PEMROSESAN")
        print("----------------------------------------------------------------------")
        if latencies:
            avg_lat = sum(latencies) / len(latencies)
            min_lat = min(latencies)
            max_lat = max(latencies)
            sorted_lat = sorted(latencies)
            p95_lat = sorted_lat[int(len(sorted_lat) * 0.95)]
            print(f"Status Sampel        : Initial observed latency baseline (n={len(latencies)})")
            print(f"Rata-rata Latensi    : {avg_lat:.3f} detik")
            print(f"Min Latensi          : {min_lat:.3f} detik")
            print(f"Max Latensi          : {max_lat:.3f} detik")
            if len(latencies) >= 30:
                print(f"p95 Latensi          : {p95_lat:.3f} detik")
            else:
                print(f"Catatan Statistik    : n={len(latencies)} belum memenuhi syarat inferensi p95 operasional (memerlukan n>=30).")
        else:
            print("Belum ada data latensi presisi (Metrik aktif mulai v1.0.0+).")
        print()

        print("[6] 5 SUBMISI TERAKHIR")
        print("----------------------------------------------------------------------")
        for rep in recent_reports:
            r_id = rep["id"]
            d_id = rep["document_id"]
            name = rep["original_filename"] or rep["title"] or f"Doc #{d_id}"
            if len(name) > 28:
                name = name[:25] + "..."
            engine = (rep["engine"] or "HYBRID").upper()
            dec = (rep["predicted_decision"] or "-").upper()
            pat = rep["evidence_pattern"] or "-"
            exact = f"{float(rep['exact_similarity'] or 0):.1f}%"
            cos = f"{float(rep['cosine_similarity'] or 0):.1f}%"
            
            lat_str = ""
            if rep["evidence_data"]:
                try:
                    ed = json.loads(rep["evidence_data"])
                    if "execution_time_seconds" in ed:
                        lat_str = f" [{ed['execution_time_seconds']}s]"
                except Exception:
                    pass
                    
            print(f"#{r_id:<4} | Doc #{d_id:<4} | {name:<28} | {dec:<8} | {pat:<24} | Ex:{exact:>6} Cos:{cos:>6}{lat_str}")
        print("======================================================================")

    finally:
        conn.close()

if __name__ == "__main__":
    display_metrics()
