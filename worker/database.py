import os
import json
from pathlib import Path
import mysql.connector

# Load .env manually if exists without extra external dependency
def load_env():
    env_path = Path(__file__).resolve().parents[1] / ".env"
    if env_path.exists():
        with open(env_path, "r", encoding="utf-8") as f:
            for line in f:
                line = line.strip()
                if line and not line.startswith("#") and "=" in line:
                    key, val = line.split("=", 1)
                    key = key.strip()
                    val = val.strip().strip("'\"")
                    if key not in os.environ:
                        os.environ[key] = val

load_env()

def get_connection():
    return mysql.connector.connect(
        host=os.getenv("DB_HOST", "127.0.0.1"),
        port=int(os.getenv("DB_PORT", "3306")),
        user=os.getenv("DB_USER", "ymakarius"),
        password=os.getenv("DB_PASSWORD", "Segalang$$65216"),
        database=os.getenv("DB_NAME", "db_artilik"),
        charset="utf8mb4",
        use_pure=True
    )

def get_next_job(connection):
    cursor = connection.cursor(dictionary=True)
    cursor.execute("""
        SELECT *
        FROM jobs
        WHERE status = 'pending'
          AND available_at <= NOW()
        ORDER BY id ASC
        LIMIT 1
    """)
    job = cursor.fetchone()
    cursor.close()
    return job

def mark_job_processing(connection, job_id):
    cursor = connection.cursor()
    cursor.execute("""
        UPDATE jobs
        SET status = 'processing',
            attempts = attempts + 1,
            updated_at = NOW()
        WHERE id = %s
    """, (job_id,))
    connection.commit()
    cursor.close()

def mark_job_completed(connection, job_id):
    cursor = connection.cursor()
    cursor.execute("""
        UPDATE jobs
        SET status = 'completed',
            updated_at = NOW()
        WHERE id = %s
    """, (job_id,))
    connection.commit()
    cursor.close()

def mark_job_failed(connection, job_id, error):
    cursor = connection.cursor()
    cursor.execute("""
        UPDATE jobs
        SET status = 'failed',
            error_message = %s,
            updated_at = NOW()
        WHERE id = %s
    """, (str(error), job_id))
    connection.commit()
    cursor.close()

def recover_stuck_jobs(connection, timeout_minutes: int = 15) -> int:
    """
    Memulihkan jobs yang macet dalam status 'processing' lebih lama dari timeout_minutes:
    - Jika attempts < 3: dikembalikan ke 'pending' untuk dicoba kembali.
    - Jika attempts >= 3: ditandai 'failed' agar tidak looping tanpa henti.
    Jika job berupa similarity_check yang gagal permanen, perbarui juga status reports terkait.
    """
    cursor = connection.cursor(dictionary=True)
    cursor.execute("""
        SELECT id, type, payload, attempts
        FROM jobs
        WHERE status = 'processing'
          AND updated_at < NOW() - INTERVAL %s MINUTE
    """, (timeout_minutes,))
    stuck_jobs = cursor.fetchall()
    
    recovered_count = 0
    for job in stuck_jobs:
        job_id = job["id"]
        attempts = job["attempts"]
        job_type = job["type"]
        
        if attempts < 3:
            cursor.execute("""
                UPDATE jobs
                SET status = 'pending',
                    updated_at = NOW()
                WHERE id = %s
            """, (job_id,))
            print(f"  [RECOVER] Job #{job_id} ({job_type}) macet > {timeout_minutes}m. Dikembalikan ke 'pending' (percobaan ke-{attempts}).")
        else:
            err_msg = f"Job dibatalkan otomatis karena melebihi batas waktu pemrosesan ({timeout_minutes} menit) sebanyak 3 kali."
            cursor.execute("""
                UPDATE jobs
                SET status = 'failed',
                    error_message = %s,
                    updated_at = NOW()
                WHERE id = %s
            """, (err_msg, job_id))
            print(f"  [RECOVER] Job #{job_id} ({job_type}) macet permanen. Ditandai 'failed'.")
            
            # Jika job adalah similarity_check, update reports dan kembalikan kuota user
            if job_type == "similarity_check":
                try:
                    payload = json.loads(job["payload"]) if isinstance(job["payload"], str) else job["payload"]
                    report_id = payload.get("report_id")
                    if report_id:
                        cursor.execute("""
                            UPDATE reports
                            SET status = 'failed',
                                error_message = %s,
                                updated_at = NOW()
                            WHERE id = %s AND status = 'processing'
                        """, (err_msg, report_id))
                        # Refund kuota
                        cursor.execute("""
                            SELECT d.user_id 
                            FROM documents d 
                            JOIN reports r ON r.document_id = d.id 
                            WHERE r.id = %s
                        """, (report_id,))
                        user_row = cursor.fetchone()
                        if user_row and user_row["user_id"]:
                            uid = user_row["user_id"]
                            cursor.execute("""
                                UPDATE users 
                                SET free_submissions_used_this_week = free_submissions_used_this_week - 1,
                                    updated_at = NOW()
                                WHERE id = %s AND free_submissions_used_this_week > 0
                            """, (uid,))
                            if cursor.rowcount == 0:
                                cursor.execute("""
                                    UPDATE users 
                                    SET bonus_submissions = bonus_submissions + 1,
                                        updated_at = NOW()
                                    WHERE id = %s
                                """, (uid,))
                            print(f"  [QUOTA REFUND] Kuota dikembalikan untuk User #{uid} (Report #{report_id} timeout).")
                except Exception as ex:
                    print(f"  [RECOVER WARNING] Gagal mengupdate report/kuota untuk job #{job_id}: {ex}")
                    
        recovered_count += 1
        
    connection.commit()
    cursor.close()
    return recovered_count
