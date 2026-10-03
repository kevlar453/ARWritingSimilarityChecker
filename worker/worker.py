import json
import time
import sys
from pathlib import Path

# Ensure root directory is on PYTHONPATH
sys.path.append(str(Path(__file__).resolve().parents[1]))

from worker.database import (
    get_connection,
    get_next_job,
    mark_job_processing,
    mark_job_completed,
    mark_job_failed
)
from worker.indexer import index_document
from worker.reporter import process_similarity_check

def process_job(job: dict):
    payload = json.loads(job["payload"]) if isinstance(job["payload"], str) else job["payload"]
    job_type = job["type"]
    
    if job_type == "index_corpus":
        doc_id = payload["document_id"]
        conn = get_connection()
        try:
            cursor = conn.cursor(dictionary=True)
            cursor.execute("SELECT * FROM documents WHERE id = %s", (doc_id,))
            doc = cursor.fetchone()
            cursor.close()
            if not doc:
                raise ValueError(f"Dokumen ID {doc_id} tidak ditemukan.")
            index_document(
                connection=conn,
                document_id=doc_id,
                storage_path=doc["storage_path"],
                mime_type=doc.get("mime_type"),
                is_corpus=True
            )
        finally:
            conn.close()
            
    elif job_type == "similarity_check":
        process_similarity_check(
            document_id=payload["document_id"],
            report_id=payload["report_id"],
            engine=payload.get("engine", "hybrid")
        )
    else:
        raise ValueError(f"Unknown job type: {job_type}")

def run_worker(daemon: bool = True):
    print("==================================================")
    print("      AR Tilik Similarity Checker Worker         ")
    print("==================================================")
    print(f"Worker aktif (mode: {'daemon' if daemon else 'drain/once'})...")
    
    while True:
        try:
            conn = get_connection()
            job = get_next_job(conn)
            if not job:
                conn.close()
                if not daemon:
                    print("[✓] Semua antrean jobs selesai diproses.")
                    break
                time.sleep(2)
                continue
                
            job_id = job["id"]
            print(f"[*] Memproses job #{job_id} ({job['type']})...")
            mark_job_processing(conn, job_id)
            conn.close()
            
            try:
                process_job(job)
                conn = get_connection()
                mark_job_completed(conn, job_id)
                print(f"[✓] Job #{job_id} selesai.")
            except Exception as err:
                print(f"[!] Job #{job_id} gagal: {err}")
                conn = get_connection()
                mark_job_failed(conn, job_id, err)
            finally:
                conn.close()
                
        except KeyboardInterrupt:
            print("\nWorker dihentikan oleh pengguna.")
            break
        except Exception as conn_err:
            print(f"[!] Error koneksi worker: {conn_err}")
            if not daemon:
                break
            time.sleep(3)

if __name__ == "__main__":
    import argparse
    parser = argparse.ArgumentParser(description="AR Tilik Worker")
    parser.add_argument("--once", action="store_true", help="Proses antrean yang ada lalu keluar")
    args = parser.parse_args()
    run_worker(daemon=(not args.once))
