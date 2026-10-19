#!/usr/bin/env python3
"""
AR Tilik - Dedicated AI Narrative Queue Worker
Menangani pemrosesan antrean narasi forensik cerdas (ai_narrative_jobs)
dengan kontrol konkurensi (maks 2), FIFO berdasarkan created_at, dan graceful shutdown.
"""

import sys
import os
import time
import signal
import argparse
import logging
from typing import Optional

BASE_DIR = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
if BASE_DIR not in sys.path:
    sys.path.insert(0, BASE_DIR)

from worker.database import get_connection
from worker.ai_gateway import (
    load_gateway_config,
    execute_job_with_gateway,
    check_narrative_cache
)

logging.basicConfig(
    level=logging.INFO,
    format="%(asctime)s [%(levelname)s] [AI-WORKER] %(message)s"
)
logger = logging.getLogger("artilik.narrative_worker")

_SHUTDOWN_REQUESTED = False


def _signal_handler(signum, frame):
    global _SHUTDOWN_REQUESTED
    sig_name = "SIGTERM" if signum == signal.SIGTERM else "SIGINT"
    logger.info(f"Menerima sinyal {sig_name}. Mempersiapkan graceful shutdown...")
    _SHUTDOWN_REQUESTED = True


def count_active_jobs(conn) -> int:
    """Menghitung jumlah pekerjaan yang saat ini sedang berstatus 'processing'."""
    try:
        cursor = conn.cursor(dictionary=True)
        cursor.execute("SELECT COUNT(*) AS total FROM ai_narrative_jobs WHERE status = 'processing'")
        row = cursor.fetchone()
        cursor.close()
        return int(row.get("total", 0)) if row else 0
    except Exception as e:
        logger.error(f"Gagal memeriksa status processing: {e}")
        return 0


def fetch_and_lock_next_job(conn, specific_job_id: Optional[int] = None) -> Optional[dict]:
    """
    Mengambil tugas queued tertua (FIFO) dan langsung mengubah statusnya ke 'processing' secara atomik.
    Juga mengambil tugas 'deferred' jika tidak ada tugas 'queued' baru.
    """
    cursor = conn.cursor(dictionary=True)
    try:
        if specific_job_id:
            cursor.execute("""
                SELECT * FROM ai_narrative_jobs
                WHERE id = %s AND status IN ('queued', 'deferred')
                LIMIT 1
            """, (specific_job_id,))
        else:
            # Prioritaskan 'queued' terlebih dahulu, baru 'deferred' yang sudah melewati jeda 5 menit
            cursor.execute("""
                SELECT * FROM ai_narrative_jobs
                WHERE status = 'queued'
                   OR (status = 'deferred' AND updated_at <= NOW() - INTERVAL 5 MINUTE)
                ORDER BY CASE WHEN status = 'queued' THEN 0 ELSE 1 END ASC, created_at ASC
                LIMIT 1
            """)

        job = cursor.fetchone()
        if job:
            cursor.execute("""
                UPDATE ai_narrative_jobs 
                SET status = 'processing', updated_at = NOW() 
                WHERE id = %s AND status IN ('queued', 'deferred')
            """, (job["id"],))
            conn.commit()
            if cursor.rowcount > 0:
                job["status"] = "processing"
                return job
        return None
    except Exception as e:
        logger.error(f"Gagal mengambil & mengunci job: {e}")
        conn.rollback()
        return None
    finally:
        cursor.close()


def run_single_pass(specific_job_id: Optional[int] = None) -> bool:
    """Mengeksekusi satu siklus pengambilan dan pemrosesan job."""
    config = load_gateway_config()
    max_concurrent = int(config.get("max_concurrent", 2))

    conn = get_connection()
    try:
        active = count_active_jobs(conn)
        if active >= max_concurrent and not specific_job_id:
            logger.debug(f"Batas konkurensi tercapai ({active}/{max_concurrent}). Menunda pengambilan job baru.")
            return False

        job = fetch_and_lock_next_job(conn, specific_job_id)
        if not job:
            return False

        job_id = job["id"]
        logger.info(f"Memproses Job #{job_id} (Submission #{job['submission_id']}, Report #{job.get('report_id')})...")
        execute_job_with_gateway(job, conn)
        logger.info(f"Job #{job_id} selesai dieksekusi.")
        return True
    finally:
        conn.close()


def main():
    signal.signal(signal.SIGINT, _signal_handler)
    signal.signal(signal.SIGTERM, _signal_handler)

    parser = argparse.ArgumentParser(description="AR Tilik - AI Narrative Queue Worker")
    parser.add_argument("--daemon", action="store_true", help="Jalankan sebagai daemon loop berulang")
    parser.add_argument("--once", action="store_true", help="Jalankan satu putaran dan keluar (cocok untuk cron)")
    parser.add_argument("--job-id", type=int, help="Proses ID pekerjaan tertentu secara langsung")
    args = parser.parse_args()

    config = load_gateway_config()
    poll_interval = int(config.get("poll_interval_sec", 5))

    if args.job_id:
        logger.info(f"Mode eksekusi manual untuk Job #{args.job_id}")
        run_single_pass(specific_job_id=args.job_id)
        return

    if args.once:
        logger.info("Menjalankan worker antrean mode single-pass...")
        processed_any = False
        while not _SHUTDOWN_REQUESTED:
            did_work = run_single_pass()
            if did_work:
                processed_any = True
            else:
                break
        logger.info(f"Single-pass selesai. Memproses tugas: {processed_any}")
        return

    # Default: daemon mode
    logger.info(f"Memulai worker antrean narasi dalam mode daemon (interval polling: {poll_interval}s)...")
    while not _SHUTDOWN_REQUESTED:
        try:
            did_work = run_single_pass()
            if not did_work:
                time.sleep(poll_interval)
        except Exception as e:
            logger.error(f"Kesalahan pada loop daemon: {e}")
            time.sleep(poll_interval)

    logger.info("Worker dihentikan secara graceful.")


if __name__ == "__main__":
    main()
