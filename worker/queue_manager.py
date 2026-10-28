"""
AR Tilik - Redis Event-Driven Task Queue Manager (Fase 1 - v1.4)
Mendukung antrean terdistribusi berkecepatan tinggi dengan dual-priority (fast vs heavy)
dan fallback otomatis ke basis data MariaDB saat Redis offline.
"""

import os
import json
import logging
from typing import Dict, Any, Optional, Tuple

logger = logging.getLogger("artilik.queue_manager")

QUEUE_FAST = "artilik:queue:fast"
QUEUE_HEAVY = "artilik:queue:heavy"
QUEUE_DLQ = "artilik:queue:dead_letter"

_REDIS_CLIENT = None
_REDIS_CHECKED = False


def get_redis_client():
    """Mengambil atau menginisialisasi koneksi Redis tunggal (singleton)."""
    global _REDIS_CLIENT, _REDIS_CHECKED
    if _REDIS_CLIENT is not None:
        return _REDIS_CLIENT

    host = os.getenv("REDIS_HOST", "127.0.0.1")
    port = int(os.getenv("REDIS_PORT", "6379"))
    password = os.getenv("REDIS_PASSWORD", None)
    db = int(os.getenv("REDIS_DB", "0"))

    try:
        import redis
        client = redis.Redis(
            host=host,
            port=port,
            password=password,
            db=db,
            socket_timeout=2.0,
            socket_connect_timeout=2.0,
            decode_responses=True
        )
        if client.ping():
            _REDIS_CLIENT = client
            logger.info(f"[QUEUE] Terhubung ke Redis pada {host}:{port} (db: {db})")
            return _REDIS_CLIENT
    except Exception as e:
        logger.warning(f"[QUEUE] Redis tidak tersedia ({e}). Menggunakan mode fallback MariaDB.")
        _REDIS_CLIENT = None

    return None


def is_redis_available() -> bool:
    """Memeriksa apakah Redis aktif dan merespons PING."""
    client = get_redis_client()
    if client is None:
        return False
    try:
        return bool(client.ping())
    except Exception:
        return False


def determine_queue_priority(job_type: str, payload: Dict[str, Any]) -> str:
    """
    Menentukan prioritas antrean berdasarkan jenis tugas dan ukuran komputasi:
    - FAST: index_corpus, verifikasi singkat, atau re-index
    - HEAVY: similarity_check hybrid, JIT topic harvesting, gemini forensic audit
    """
    if job_type in ("index_corpus", "reindex", "notify", "delete_corpus"):
        return QUEUE_FAST

    # Jika similarity check tanpa engine hybrid berat, bisa masuk antrean fast
    engine = payload.get("engine", "hybrid") if isinstance(payload, dict) else "hybrid"
    if engine in ("exact", "idf"):
        return QUEUE_FAST

    return QUEUE_HEAVY


def enqueue_job(job_id: int, job_type: str, payload: Any, priority: Optional[str] = None) -> bool:
    """
    Mendorong tugas ke dalam antrean Redis.
    Jika Redis aktif, tugas di-push ke list sesuai prioritas (LPUSH).
    """
    client = get_redis_client()
    if not client:
        return False

    payload_dict = payload if isinstance(payload, dict) else (json.loads(payload) if isinstance(payload, str) else {})
    target_queue = priority or determine_queue_priority(job_type, payload_dict)

    job_packet = {
        "id": job_id,
        "type": job_type,
        "payload": payload_dict,
        "enqueued_at": os.getenv("CURRENT_TIME", "")
    }

    try:
        client.lpush(target_queue, json.dumps(job_packet))
        logger.debug(f"[QUEUE] Job #{job_id} ({job_type}) didorong ke {target_queue}")
        return True
    except Exception as e:
        logger.error(f"[QUEUE] Gagal mendorong Job #{job_id} ke Redis: {e}")
        return False


def pop_next_job(timeout: int = 2, queues: Optional[list] = None) -> Optional[Tuple[str, Dict[str, Any]]]:
    """
    Mengambil tugas berikutnya dengan blocking pop (BRPOP).
    Memprioritaskan QUEUE_FAST terlebih dahulu, baru QUEUE_HEAVY (atau list antrean kustom).
    Mengembalikan: (queue_name, job_data) atau None jika timeout.
    """
    client = get_redis_client()
    if not client:
        return None

    target_queues = queues if queues is not None else [QUEUE_FAST, QUEUE_HEAVY]

    try:
        # BRPOP memeriksa antrean dari kiri ke kanan secara urut prioritas
        item = client.brpop(target_queues, timeout=timeout)
        if item:
            queue_name, raw_data = item
            job_data = json.loads(raw_data)
            return queue_name, job_data
    except Exception as e:
        logger.error(f"[QUEUE] Gagal pop job dari Redis: {e}")

    return None


def get_queue_metrics() -> Dict[str, Any]:
    """Mengambil status metrik panjang antrean Redis."""
    client = get_redis_client()
    if not client:
        return {
            "redis_enabled": False,
            "fast_count": 0,
            "heavy_count": 0,
            "dlq_count": 0,
            "total_pending": 0
        }

    try:
        fast_cnt = client.llen(QUEUE_FAST)
        heavy_cnt = client.llen(QUEUE_HEAVY)
        dlq_cnt = client.llen(QUEUE_DLQ)
        return {
            "redis_enabled": True,
            "fast_count": fast_cnt,
            "heavy_count": heavy_cnt,
            "dlq_count": dlq_cnt,
            "total_pending": fast_cnt + heavy_cnt
        }
    except Exception as e:
        return {
            "redis_enabled": False,
            "error": str(e),
            "fast_count": 0,
            "heavy_count": 0,
            "dlq_count": 0,
            "total_pending": 0
        }


def push_to_dead_letter(job_data: Dict[str, Any], error_reason: str) -> bool:
    """Memindahkan tugas yang gagal berulang kali / fatal ke antrean dead letter."""
    client = get_redis_client()
    if not client:
        return False
    try:
        packet = dict(job_data)
        packet["error_reason"] = error_reason
        client.lpush(QUEUE_DLQ, json.dumps(packet))
        return True
    except Exception as e:
        logger.error(f"[QUEUE] Gagal mendorong ke DLQ: {e}")
        return False


def clear_all_queues():
    """Membersihkan seluruh antrean Redis (khusus testing & maintenance)."""
    client = get_redis_client()
    if client:
        try:
            client.delete(QUEUE_FAST, QUEUE_HEAVY, QUEUE_DLQ)
        except Exception:
            pass

