import json
import time
import sys
import signal
from pathlib import Path

# Ensure project root is prioritized on sys.path and avoid script directory shadowing
_parent_dir = str(Path(__file__).resolve().parents[1])
_script_dir = str(Path(__file__).resolve().parent)
if _script_dir in sys.path:
    sys.path.remove(_script_dir)
if _parent_dir not in sys.path:
    sys.path.insert(0, _parent_dir)

from worker.database import (
    get_connection,
    get_next_job,
    mark_job_processing,
    mark_job_completed,
    mark_job_failed,
    recover_stuck_jobs
)
from worker.indexer import index_document
from worker.reporter import process_similarity_check

# Flag untuk graceful shutdown
_shutdown_requested = False

def _handle_signal(signum, frame):
    global _shutdown_requested
    sig_name = "SIGTERM" if signum == signal.SIGTERM else "SIGINT"
    print(f"\n[!] Sinyal {sig_name} diterima. Menyiapkan penghentian worker secara graceful...")
    _shutdown_requested = True

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
    global _shutdown_requested
    print("==================================================")
    print("      AR Tilik Similarity Checker Worker         ")
    print("==================================================")
    print(f"Worker aktif (mode: {'daemon' if daemon else 'drain/once'})...")
    
    signal.signal(signal.SIGINT, _handle_signal)
    signal.signal(signal.SIGTERM, _handle_signal)

    # Jalankan recovery job yang macet saat startup
    try:
        startup_conn = get_connection()
        recovered = recover_stuck_jobs(startup_conn, timeout_minutes=15)
        if recovered > 0:
            print(f"[*] Startup: Berhasil memulihkan {recovered} job yang macet.")
        startup_conn.close()
    except Exception as rec_err:
        print(f"[!] Gagal menjalankan pemulihan job macet startup: {rec_err}")

    last_recovery_time = time.time()
    
    while not _shutdown_requested:
        # Jalankan recovery secara berkala setiap 5 menit (300 detik)
        now = time.time()
        if now - last_recovery_time > 300:
            try:
                rec_conn = get_connection()
                recover_stuck_jobs(rec_conn, timeout_minutes=15)
                rec_conn.close()
            except Exception as rec_err:
                print(f"[!] Gagal menjalankan pemulihan job macet periodik: {rec_err}")
            last_recovery_time = now

        try:
            conn = get_connection()
            job = get_next_job(conn)
            if not job:
                conn.close()
                if not daemon:
                    print("[✓] Semua antrean jobs selesai diproses.")
                    break
                # Tunggu 2 detik dengan interval pendek agar peka sinyal shutdown
                for _ in range(20):
                    if _shutdown_requested:
                        break
                    time.sleep(0.1)
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

    print("[✓] Worker AR Tilik telah dihentikan secara aman.")

if __name__ == "__main__":
    import argparse
    parser = argparse.ArgumentParser(description="AR Tilik Worker")
    parser.add_argument("--once", action="store_true", help="Proses antrean yang ada lalu keluar")
    args = parser.parse_args()
    run_worker(daemon=(not args.once))
