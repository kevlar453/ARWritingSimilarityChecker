import os
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
