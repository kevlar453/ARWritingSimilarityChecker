"""
AR Tilik - AI Gateway & Antrean Narasi Forensik Terdistribusi
Implementasi Gateway Multi-Provider (Groq L1 -> Gemini L2 -> Degradasi Jujur L3),
Antrean MariaDB, Caching Deterministik, Circuit Breaker, dan Budget Guard Harian.
"""

import json
import logging
import os
import re
import time
import hashlib
import urllib.request
import urllib.error
from datetime import datetime
from typing import Dict, Any, Optional, List, Tuple

from worker.database import get_connection
from worker.gemini_forensic import (
    create_privacy_preserving_sample,
    postprocess_ai_claim,
    call_gemini_api,
    get_available_gemini_keys,
    mark_key_success,
    mark_key_rate_limited,
    mark_key_exhausted,
    mark_key_inactive,
    log_gemini_metric
)

logger = logging.getLogger("artilik.ai_gateway")

CONFIG_FILE_PATH = os.path.join(os.path.dirname(os.path.abspath(__file__)), "ai_gateway_config.json")
PROMPT_VERSION = "v1.2-forensic"

# In-memory circuit breaker state
# { provider_name: { "failures": int, "cooldown_until": float } }
_CIRCUIT_BREAKER_STATE: Dict[str, Dict[str, Any]] = {}


def load_gateway_config() -> Dict[str, Any]:
    """Memuat konfigurasi AI Gateway dari berkas JSON dengan fallback nilai aman."""
    default_config = {
        "max_concurrent": 2,
        "poll_interval_sec": 5,
        "backoff_sec": [5, 15, 45],
        "circuit_breaker": {
            "failures": 3,
            "cooldown_min": 10
        },
        "cache_ttl_days": 30,
        "deferred_retry_max": 12,
        "providers": [
            {
                "name": "groq",
                "enabled": True,
                "model": "llama-3.3-70b-versatile",
                "daily_token_cap": 90000,
                "rpm_cap": 25,
                "endpoint": "https://api.groq.com/openai/v1/chat/completions"
            },
            {
                "name": "gemini",
                "enabled": True,
                "model": "gemini-flash-lite-latest",
                "keys_round_robin": True
            }
        ]
    }
    if os.path.exists(CONFIG_FILE_PATH):
        try:
            with open(CONFIG_FILE_PATH, "r", encoding="utf-8") as f:
                loaded = json.load(f)
                default_config.update(loaded)
        except Exception as e:
            logger.warning(f"[AI GATEWAY] Gagal membaca {CONFIG_FILE_PATH}: {e}. Memakai konfigurasi bawaan.")
    return default_config


def compute_payload_hash(sample_text: str, prompt_version: str = PROMPT_VERSION) -> str:
    """Menghitung SHA-256 hash dari sampel naskah dan versi prompt untuk pencarian cache dan deduplikasi."""
    norm_sample = re.sub(r"\s+", " ", sample_text).strip()
    raw = f"{prompt_version}::{norm_sample}".encode("utf-8")
    return hashlib.sha256(raw).hexdigest()


def check_narrative_cache(conn, payload_hash: str, ttl_days: int = 30) -> Optional[Dict[str, Any]]:
    """
    Mengecek apakah hasil narasi untuk payload_hash sudah ada di tabel ai_narrative_jobs
    dengan status 'done' dalam jendela waktu TTL.
    """
    try:
        cursor = conn.cursor(dictionary=True)
        cursor.execute("""
            SELECT result, created_at
            FROM ai_narrative_jobs
            WHERE payload_hash = %s 
              AND status = 'done' 
              AND result IS NOT NULL
              AND created_at >= NOW() - INTERVAL %s DAY
            ORDER BY id DESC
            LIMIT 1
        """, (payload_hash, ttl_days))
        row = cursor.fetchone()
        cursor.close()
        if row and row.get("result"):
            res = json.loads(row["result"]) if isinstance(row["result"], str) else row["result"]
            res["cache_hit"] = True
            res["cached_from"] = str(row["created_at"])
            return res
    except Exception as e:
        logger.warning(f"[AI GATEWAY] Gagal memeriksa cache narasi: {e}")
    return None


def get_provider_daily_tokens(conn, provider: str) -> int:
    """Mengambil total konsumsi token harian untuk provider tertentu dari tabel ai_provider_usage."""
    today = time.strftime("%Y-%m-%d")
    try:
        cursor = conn.cursor(dictionary=True)
        cursor.execute("""
            SELECT (tokens_in + tokens_out) AS total_tokens
            FROM ai_provider_usage
            WHERE provider = %s AND usage_date = %s
        """, (provider, today))
        row = cursor.fetchone()
        cursor.close()
        if row and row.get("total_tokens") is not None:
            return int(row["total_tokens"])
    except Exception as e:
        logger.warning(f"[AI GATEWAY] Gagal mengambil metrik token {provider}: {e}")
    return 0


def record_provider_usage(conn, provider: str, tokens_in: int = 0, tokens_out: int = 0, rate_limit_hit: bool = False):
    """Mencatat atau memperbarui konsumsi token dan permintaan ke ai_provider_usage."""
    today = time.strftime("%Y-%m-%d")
    rl_val = 1 if rate_limit_hit else 0
    try:
        cursor = conn.cursor()
        cursor.execute("""
            INSERT INTO ai_provider_usage (provider, usage_date, tokens_in, tokens_out, requests, rate_limit_hits, created_at, updated_at)
            VALUES (%s, %s, %s, %s, 1, %s, NOW(), NOW())
            ON DUPLICATE KEY UPDATE
                tokens_in = tokens_in + VALUES(tokens_in),
                tokens_out = tokens_out + VALUES(tokens_out),
                requests = requests + 1,
                rate_limit_hits = rate_limit_hits + VALUES(rate_limit_hits),
                updated_at = NOW()
        """, (provider, today, tokens_in, tokens_out, rl_val))
        conn.commit()
        cursor.close()
    except Exception as e:
        logger.warning(f"[AI GATEWAY] Gagal memperbarui pemakaian provider {provider}: {e}")


def is_circuit_breaker_active(provider: str) -> bool:
    """Memeriksa apakah circuit breaker untuk provider sedang dalam masa cooldown."""
    state = _CIRCUIT_BREAKER_STATE.get(provider)
    if not state:
        return False
    cooldown_until = state.get("cooldown_until", 0.0)
    if time.time() < cooldown_until:
        remaining = int(cooldown_until - time.time())
        logger.info(f"[AI GATEWAY] Provider '{provider}' sedang dalam cooldown ({remaining}s tersisa).")
        return True
    return False


def record_circuit_failure(provider: str, config: Dict[str, Any]):
    """Mencatat kegagalan provider. Jika mencapai batas, aktifkan jeda cooldown."""
    cb_cfg = config.get("circuit_breaker", {})
    max_fails = cb_cfg.get("failures", 3)
    cooldown_min = cb_cfg.get("cooldown_min", 10)

    state = _CIRCUIT_BREAKER_STATE.setdefault(provider, {"failures": 0, "cooldown_until": 0.0})
    state["failures"] += 1
    if state["failures"] >= max_fails:
        state["cooldown_until"] = time.time() + (cooldown_min * 60)
        logger.warning(f"[AI GATEWAY] Circuit breaker aktif untuk '{provider}': {state['failures']} gagal beruntun. Cooldown {cooldown_min} menit.")


def record_circuit_success(provider: str):
    """Mereset status circuit breaker ketika provider berhasil memproses permintaan."""
    _CIRCUIT_BREAKER_STATE[provider] = {"failures": 0, "cooldown_until": 0.0}


def get_available_groq_keys(conn=None) -> List[str]:
    """Mengambil daftar kunci API Groq aktif dari environment variable dan basis data."""
    keys: List[str] = []
    # 1. Dari Environment
    env_keys = os.environ.get("GROQ_API_KEYS") or os.environ.get("GROQ_API_KEY", "")
    if env_keys.strip():
        for k in env_keys.split(","):
            clean_k = k.strip()
            if clean_k and clean_k not in keys:
                keys.append(clean_k)

    # 2. Dari Tabel google_api_keys (provider = 'groq')
    close_after = False
    if conn is None:
        try:
            conn = get_connection()
            close_after = True
        except Exception:
            conn = None

    if conn:
        try:
            cursor = conn.cursor(dictionary=True)
            cursor.execute("""
                SELECT api_key FROM google_api_keys 
                WHERE provider = 'groq' AND status = 'active'
                ORDER BY error_count ASC, usage_count ASC
            """)
            rows = cursor.fetchall() or []
            cursor.close()
            for r in rows:
                ak = r.get("api_key", "").strip()
                if ak and ak not in keys:
                    keys.append(ak)
        except Exception as e:
            logger.warning(f"[AI GATEWAY] Gagal membaca kunci Groq dari basis data: {e}")
        finally:
            if close_after:
                conn.close()

    return keys


def call_groq_api(api_key: str, model_name: str, prompt: str, timeout: int = 40) -> Dict[str, Any]:
    """
    Memanggil Groq Cloud OpenAI-compatible endpoint dengan format JSON output.
    Mendukung penangkapan header Retry-After pada respon HTTP 429.
    """
    url = "https://api.groq.com/openai/v1/chat/completions"
    headers = {
        "Authorization": f"Bearer {api_key}",
        "Content-Type": "application/json",
        "User-Agent": "ARTilik-ForensicGateway/2.0"
    }
    payload = {
        "model": model_name or "llama-3.3-70b-versatile",
        "messages": [
            {
                "role": "system",
                "content": "Anda adalah Auditor Forensik Integritas Akademik Independen untuk sistem AR Tilik. Analisis draf karya ilmiah dan kembalikan HANYA format JSON valid."
            },
            {
                "role": "user",
                "content": prompt
            }
        ],
        "response_format": {"type": "json_object"},
        "temperature": 0.2
    }

    req_data = json.dumps(payload).encode("utf-8")
    req = urllib.request.Request(url, data=req_data, headers=headers)

    t0 = time.time()
    try:
        with urllib.request.urlopen(req, timeout=timeout) as response:
            latency_ms = int((time.time() - t0) * 1000)
            res_body = response.read().decode("utf-8")
            data = json.loads(res_body)
            choices = data.get("choices", [])
            usage = data.get("usage", {})
            tokens_in = usage.get("prompt_tokens", 0)
            tokens_out = usage.get("completion_tokens", 0)

            if choices:
                content = choices[0].get("message", {}).get("content", "").strip()
                return {
                    "success": True,
                    "raw_text": content,
                    "status_code": 200,
                    "latency_ms": latency_ms,
                    "tokens_in": tokens_in,
                    "tokens_out": tokens_out,
                    "model_executed": model_name
                }
            return {
                "success": False,
                "error": "Respon pilihan kosong dari Groq",
                "status_code": 200,
                "latency_ms": latency_ms
            }
    except urllib.error.HTTPError as e:
        latency_ms = int((time.time() - t0) * 1000)
        err_content = e.read().decode("utf-8", errors="ignore")
        retry_after = e.headers.get("Retry-After")
        retry_seconds = int(float(retry_after)) if retry_after else None
        return {
            "success": False,
            "status_code": e.code,
            "error": f"HTTP {e.code}: {err_content[:300]}",
            "retry_after_sec": retry_seconds,
            "latency_ms": latency_ms,
            "is_quota": (e.code == 429)
        }
    except Exception as e:
        latency_ms = int((time.time() - t0) * 1000)
        return {
            "success": False,
            "status_code": 500,
            "error": str(e),
            "latency_ms": latency_ms
        }


def build_forensic_prompt(document_text: str, substantive_text: str = "", title: str = "", doc_metadata: Optional[Dict[str, Any]] = None) -> Tuple[str, str]:
    """Menyusun system prompt dan sampel teks terfragmentasi naskah (≤ 4.500 karakter)."""
    content_source = substantive_text if (substantive_text and len(substantive_text.strip()) >= 500) else document_text
    truncated_content = create_privacy_preserving_sample(content_source, max_chars=4500)

    meta = doc_metadata or {}
    if meta.get("created") or meta.get("modified"):
        metadata_note = (
            f"Metadata berkas: dibuat {meta.get('created') or 'tidak diketahui'}, "
            f"terakhir diubah {meta.get('modified') or 'tidak diketahui'}."
            + (" Berkas ini berasal dari SEBELUM era AI generatif; indikasi AI hampir pasti RENDAH."
               if meta.get("is_pre_llm_era") else "")
        )
    else:
        metadata_note = "Metadata berkas: tidak tersedia."

    current_year = time.strftime("%Y")
    current_date_str = time.strftime("%d %B %Y")

    system_prompt = f"""Anda adalah Auditor Forensik Integritas Akademik Independen untuk sistem AR Tilik.
Tugas Anda adalah menganalisis draf karya ilmiah/proposal berikut untuk mendeteksi inkonsistensi logika, 
kontradiksi metodologi, distorsi sitasi (halusinasi AI), dan memberikan panduan pertanyaan ujian lisan bagi dosen.

Konteks Waktu Saat Ini: Tahun {current_year} (per tanggal {current_date_str}).
Catatan Penting Garis Waktu: Pemeriksaan dilakukan pada tahun {current_year}. Publikasi dan data bertahun <= {current_year} (termasuk tahun {int(current_year)-1} dan {current_year}) adalah referensi yang realistis dan sah dalam garis waktu naskah saat ini, jangan anggap tahun {current_year} atau sebelumnya sebagai "sitasi masa depan". Hanya tandai anomali masa depan jika terdapat rujukan dengan tahun > {current_year} atau fabrikasi sitasi fiktif.

{metadata_note}
ATURAN PENILAIAN "tingkat_indikasi_ai" (wajib dipatuhi, untuk mencegah tuduhan keliru terhadap mahasiswa):
1. Default-nya "RENDAH". Jika ragu, jawab "RENDAH".
2. "SEDANG"/"TINGGI" HANYA boleh dipilih bila ada bukti TEKSTUAL konkret ciri keluaran AI, misalnya: sisa percakapan chatbot
   ("Tentu, berikut..."), frasa templat generik yang berulang tanpa isi spesifik, tokoh/jurnal/sitasi yang jelas fiktif,
   atau struktur kalimat identik antar-bagian.
3. Hal berikut BUKAN bukti AI, karena lazim pada tulisan mahasiswa: kontradiksi atau perubahan klaim di akhir tulisan,
   asumsi pribadi yang diakui penulis, kelemahan metodologi, sitasi tidak baku, daftar pustaka berupa URL atau tidak ada,
   gaya naratif/rangkuman, topik sejarah, salah ketik. Laporkan hal-hal ini HANYA pada
   "inkonsistensi_metodologi_dan_logika", "integritas_sitasi", dan "pertanyaan_kritis_dosen", jangan jadikan dasar indikasi AI.
4. Jika memilih "SEDANG"/"TINGGI", isi "bukti_kutipan_ai" dengan minimal 2 kutipan PERSIS (verbatim, maks. 200 karakter)
   yang disalin dari naskah. Tanpa kutipan yang dapat diverifikasi, klaim akan dibatalkan otomatis.
5. Naskah bisa saja ditulis jauh sebelum tahun 2022, ketika AI generatif belum ada.
6. Pedoman Istilah/Terminologi: Gunakan istilah "tulisan mahasiswa" atau "karya tulis manusia asli". DILARANG menggunakan istilah rancu seperti "tulisan tangan manusia" karena seluruh berkas di sistem ini merupakan dokumen digital/ketikan.

Judul Dokumen: {title or 'Karya Ilmiah'}

Cuplikan Representatif Naskah (Data Minimization: hanya sampel terfragmentasi dari bagian substantif untuk melindungi privasi karya):
\"\"\"{truncated_content}\"\"\"

Lakukan audit kritis dan kembalikan HANYA format JSON valid dengan struktur persis berikut:
{{
    "tingkat_indikasi_ai": "TINGGI" | "SEDANG" | "RENDAH",
    "alasan_indikasi_ai": "Ringkasan CIRI TEKSTUAL yang mendasari penilaian (gunakan istilah 'tulisan mahasiswa' atau 'karya tulis manusia', jangan gunakan 'tulisan tangan')",
    "bukti_kutipan_ai": ["kutipan persis dari naskah (wajib minimal 2 jika SEDANG/TINGGI, kosongkan jika RENDAH)"],
    "inkonsistensi_metodologi_dan_logika": [
        "Jelaskan kontradiksi jika ada (misal: menyebut regresi tetapi diagram/lampiran menuliskan uji-t, atau variabel di teori berbeda dengan di kuesioner)"
    ],
    "integritas_sitasi": [
        "Jelaskan anomali kutipan jika ada (misal: nama tokoh dijadikan author, tahun referensi janggal, format daftar pustaka berantakan)"
    ],
    "pertanyaan_kritis_dosen": [
        {{
            "fokus": "Metodologi / Teori / Definisi Operasional / Sitasi",
            "tingkat_urgensi": "KRITIS" | "TINGGI" | "SEDANG",
            "pertanyaan": "Pertanyaan lisan tajam yang wajib ditanyakan penguji untuk menguji pemahaman mahasiswa",
            "alasan": "Mengapa pertanyaan ini penting diajukan (tujuan pengujian bagi dosen)",
            "strategi_jawaban": "Rekomendasi taktik & strategi pembelaan argumen metodologis bagi mahasiswa saat menjawab pertanyaan ini di hadapan dewan penguji sidang"
        }}
    ]
}}
"""
    return system_prompt, truncated_content


def enqueue_narrative_job(
    conn,
    submission_id: int,
    report_id: Optional[int],
    document_text: str,
    substantive_text: str = "",
    title: str = "",
    doc_metadata: Optional[Dict[str, Any]] = None
) -> Dict[str, Any]:
    """
    Mendaftarkan pekerjaan narasi forensik ke antrean basis data:
    1. Ekstrak sampel fragmentasi naskah (≤ 4.500 karakter).
    2. Cek Cache narasi (hit -> langsung 'done' tanpa panggil API).
    3. Cek Deduplikasi (jika sudah ada job aktif ber-hash sama, tempel ke job tersebut).
    4. Masukkan ke tabel ai_narrative_jobs dengan status 'queued'.
    """
    config = load_gateway_config()
    system_prompt, sample_text = build_forensic_prompt(document_text, substantive_text, title, doc_metadata)
    p_hash = compute_payload_hash(sample_text, PROMPT_VERSION)

    # 1. Cek Cache
    cache_ttl = int(config.get("cache_ttl_days", 30))
    cached = check_narrative_cache(conn, p_hash, ttl_days=cache_ttl)
    if cached:
        logger.info(f"[AI GATEWAY] Cache hit untuk hash {p_hash[:12]}... (Doc #{submission_id})")
        # Simpan jejak bahwa job ini selesai via cache
        cursor = conn.cursor()
        cursor.execute("""
            INSERT INTO ai_narrative_jobs (submission_id, report_id, payload_hash, payload, status, result, attempts)
            VALUES (%s, %s, %s, %s, 'done', %s, %s)
        """, (
            submission_id,
            report_id,
            p_hash,
            sample_text,
            json.dumps(cached, ensure_ascii=False),
            json.dumps([{"provider": "cache", "at": time.strftime("%Y-%m-%dT%H:%M:%S"), "ms": 0, "code": 200}])
        ))
        conn.commit()
        job_id = cursor.lastrowid
        cursor.close()
        return {
            "job_id": job_id,
            "status": "done",
            "cache_hit": True,
            "result": cached
        }

    # 2. Cek Deduplikasi (job queued atau processing yang identik)
    cursor = conn.cursor(dictionary=True)
    cursor.execute("""
        SELECT id, status FROM ai_narrative_jobs
        WHERE payload_hash = %s AND status IN ('queued', 'processing')
        ORDER BY id ASC LIMIT 1
    """, (p_hash,))
    existing_job = cursor.fetchone()
    if existing_job:
        cursor.close()
        logger.info(f"[AI GATEWAY] Dedupe: menempel ke job #{existing_job['id']} yang sedang aktif.")
        return {
            "job_id": existing_job["id"],
            "status": existing_job["status"],
            "cache_hit": False,
            "deduped": True
        }

    # 3. Masukkan Job Baru ke Antrean
    meta_payload = {
        "title": title,
        "sample_text": sample_text,
        "doc_metadata": doc_metadata or {},
        "full_text_len": len(document_text),
        "prompt": system_prompt
    }
    cursor.execute("""
        INSERT INTO ai_narrative_jobs (submission_id, report_id, payload_hash, payload, status, attempts)
        VALUES (%s, %s, %s, %s, 'queued', '[]')
    """, (
        submission_id,
        report_id,
        p_hash,
        json.dumps(meta_payload, ensure_ascii=False)
    ))
    conn.commit()
    job_id = cursor.lastrowid
    cursor.close()

    logger.info(f"[AI GATEWAY] Mendaftarkan Job #{job_id} ke antrean ai_narrative_jobs (Doc #{submission_id}, Report #{report_id})")
    return {
        "job_id": job_id,
        "status": "queued",
        "cache_hit": False
    }


def execute_job_with_gateway(job: Dict[str, Any], conn) -> Dict[str, Any]:
    """
    Mengeksekusi sebuah pekerjaan narasi melalui AI Gateway:
    Rantai Provider: Groq (L1) -> Gemini Round-Robin (L2) -> Degradasi Jujur (L3).
    """
    config = load_gateway_config()
    job_id = job["id"]
    report_id = job.get("report_id")
    p_hash = job["payload_hash"]
    raw_payload = job["payload"]
    attempts: List[Dict[str, Any]] = []

    try:
        attempts = json.loads(job.get("attempts") or "[]")
    except Exception:
        attempts = []

    # Parse payload naskah
    payload_data = {}
    if isinstance(raw_payload, str):
        try:
            payload_data = json.loads(raw_payload)
        except Exception:
            payload_data = {"sample_text": raw_payload}
    else:
        payload_data = raw_payload or {}

    prompt = payload_data.get("prompt")
    sample_text = payload_data.get("sample_text", "")
    title = payload_data.get("title", "")
    doc_metadata = payload_data.get("doc_metadata") or {}

    # Jika prompt belum dibuat di payload, bangun sekarang
    if not prompt:
        prompt, _ = build_forensic_prompt(sample_text, title=title, doc_metadata=doc_metadata)

    # ── LAPIS 1: GROQ ──
    groq_keys = get_available_groq_keys(conn)
    groq_model = "llama-3.3-70b-versatile"
    groq_cap = 90000
    groq_used = get_provider_daily_tokens(conn, "groq")

    can_use_groq = bool(groq_keys) and (groq_used < groq_cap) and not is_circuit_breaker_active("groq")

    if can_use_groq:
        logger.info(f"[AI GATEWAY] [Job #{job_id}] Mencoba Lapis 1: Groq ({groq_model})...")
        for g_key in groq_keys:
            res_groq = call_groq_api(g_key, groq_model, prompt)
            attempt_rec = {
                "provider": "groq",
                "at": time.strftime("%Y-%m-%dT%H:%M:%S"),
                "ms": res_groq.get("latency_ms", 0),
                "code": res_groq.get("status_code", 500)
            }
            attempts.append(attempt_rec)

            if res_groq.get("success"):
                record_provider_usage(conn, "groq", res_groq.get("tokens_in", 0), res_groq.get("tokens_out", 0))
                record_circuit_success("groq")
                clean_text = res_groq.get("raw_text", "").strip()
                try:
                    clean_text = re.sub(r"^```(?:json)?\s*", "", clean_text, flags=re.MULTILINE)
                    clean_text = re.sub(r"\s*```$", "", clean_text, flags=re.MULTILINE)
                    parsed_audit = postprocess_ai_claim(json.loads(clean_text), sample_text, doc_metadata)
                    final_result = {
                        "status": "completed",
                        "is_available": True,
                        "provider_used": "groq",
                        "model_used": groq_model,
                        "audit": parsed_audit,
                        "privacy_protection": {
                            "full_document_transmitted": False,
                            "sample_char_count": len(sample_text),
                            "sampling_method": "stratified_fragmented_window"
                        }
                    }
                    _finalize_job_success(conn, job_id, report_id, final_result, attempts)
                    return final_result
                except Exception as parse_err:
                    logger.warning(f"[AI GATEWAY] Gagal mengurai JSON respon Groq: {parse_err}")
            else:
                record_provider_usage(conn, "groq", rate_limit_hit=res_groq.get("is_quota", False))
                record_circuit_failure("groq", config)
                logger.warning(f"[AI GATEWAY] Groq gagal ({res_groq.get('status_code')}): {res_groq.get('error')}. Melanjutkan ke L2...")
                # Backoff singkat sebelum failover ke Gemini
                time.sleep(1.0)
                break
    else:
        logger.info(f"[AI GATEWAY] [Job #{job_id}] Lapis 1 (Groq) dilewati (kunci: {len(groq_keys)}, used: {groq_used}/{groq_cap}, circuit_active: {is_circuit_breaker_active('groq')}).")

    # ── LAPIS 2: GEMINI ROUND-ROBIN ──
    gemini_keys = get_available_gemini_keys(conn)
    can_use_gemini = bool(gemini_keys) and not is_circuit_breaker_active("gemini")

    if can_use_gemini:
        logger.info(f"[AI GATEWAY] [Job #{job_id}] Mencoba Lapis 2: Google Gemini (Pool Round-Robin)...")
        for k in gemini_keys:
            key_id = k.get("id")
            key_name = k.get("key_name", "Kunci")
            api_key = k["api_key"]
            model_name = k.get("model_name") or "gemini-flash-lite-latest"

            t0 = time.time()
            res_gem = call_gemini_api(api_key, model_name, prompt)
            latency_ms = int((time.time() - t0) * 1000)

            attempt_rec = {
                "provider": "gemini",
                "key_name": key_name,
                "at": time.strftime("%Y-%m-%dT%H:%M:%S"),
                "ms": latency_ms,
                "code": res_gem.get("status_code", 500)
            }
            attempts.append(attempt_rec)

            if res_gem.get("success"):
                mark_key_success(key_id, conn)
                record_provider_usage(conn, "gemini", tokens_in=1000, tokens_out=500)
                record_circuit_success("gemini")
                raw_text = res_gem.get("raw_text", "").strip()
                try:
                    clean_text = re.sub(r"^```(?:json)?\s*", "", raw_text, flags=re.MULTILINE)
                    clean_text = re.sub(r"\s*```$", "", clean_text, flags=re.MULTILINE)
                    parsed_audit = postprocess_ai_claim(json.loads(clean_text), sample_text, doc_metadata)
                    final_result = {
                        "status": "completed",
                        "is_available": True,
                        "provider_used": "gemini",
                        "model_used": res_gem.get("model_executed") or model_name,
                        "key_used": key_name,
                        "audit": parsed_audit,
                        "privacy_protection": {
                            "full_document_transmitted": False,
                            "sample_char_count": len(sample_text),
                            "sampling_method": "stratified_fragmented_window"
                        }
                    }
                    _finalize_job_success(conn, job_id, report_id, final_result, attempts)
                    return final_result
                except Exception as parse_err:
                    logger.warning(f"[AI GATEWAY] Gagal mengurai JSON respon Gemini: {parse_err}")
            else:
                is_quota = res_gem.get("is_quota", False)
                record_provider_usage(conn, "gemini", rate_limit_hit=is_quota)
                record_circuit_failure("gemini", config)
                err_msg = res_gem.get("error", "Error")
                if is_quota:
                    mark_key_rate_limited(key_id, err_msg, cooldown_seconds=60, conn=conn)
                logger.warning(f"[AI GATEWAY] Gemini kunci #{key_id} gagal ({res_gem.get('status_code')}).")

    # ── LAPIS 3: DEGRADASI JUJUR ──
    logger.warning(f"[AI GATEWAY] [Job #{job_id}] Seluruh provider gagal/habis kuota. Menerapkan Degradasi Jujur (L3).")
    total_fails = len(attempts)
    max_retries = int(config.get("deferred_retry_max", 12))

    new_status = "deferred" if total_fails < max_retries else "failed"
    err_note = "Kapasitas harian LLM sedang padat / kuota antrean tertunda." if new_status == "deferred" else "Gagal setelah melampaui batas maksimum percobaan."

    cursor = conn.cursor()
    cursor.execute("""
        UPDATE ai_narrative_jobs
        SET status = %s,
            attempts = %s,
            error_note = %s,
            updated_at = NOW()
        WHERE id = %s
    """, (new_status, json.dumps(attempts), err_note[:250], job_id))
    conn.commit()
    cursor.close()

    # Perbarui juga di laporan agar UI langsung menyajikan status yang jujur
    deferred_result = {
        "status": new_status,
        "is_available": False,
        "message": "Analisis narasi AI tertunda karena antrean. Hasil angka & sumber di atas tetap valid.",
        "audit": None
    }
    _sync_report_evidence(conn, report_id, deferred_result)
    return deferred_result


def _finalize_job_success(conn, job_id: int, report_id: Optional[int], result: Dict[str, Any], attempts: List[Dict[str, Any]]):
    """Menyimpan hasil narasi sukses ke ai_narrative_jobs dan menyinkronkan ke reports."""
    cursor = conn.cursor()
    res_json = json.dumps(result, ensure_ascii=False)
    cursor.execute("""
        UPDATE ai_narrative_jobs
        SET status = 'done',
            result = %s,
            attempts = %s,
            error_note = NULL,
            updated_at = NOW()
        WHERE id = %s
    """, (res_json, json.dumps(attempts), job_id))
    conn.commit()
    cursor.close()

    if report_id:
        _sync_report_evidence(conn, report_id, result)


def _sync_report_evidence(conn, report_id: Optional[int], narrative_result: Dict[str, Any]):
    """Menyinkronkan data narasi ke field evidence_data pada tabel reports."""
    if not report_id:
        return
    try:
        cursor = conn.cursor(dictionary=True)
        cursor.execute("SELECT evidence_data FROM reports WHERE id = %s", (report_id,))
        rep = cursor.fetchone()
        if not rep:
            cursor.close()
            return

        ev = json.loads(rep["evidence_data"]) if rep.get("evidence_data") else {}
        ev["gemini_forensic"] = narrative_result
        if "v1_1_presentation" in ev and isinstance(ev["v1_1_presentation"], dict):
            if "v1_2_evolution" in ev["v1_1_presentation"] and isinstance(ev["v1_1_presentation"]["v1_2_evolution"], dict):
                ev["v1_1_presentation"]["v1_2_evolution"]["gemini_forensic"] = narrative_result

        cursor.execute("UPDATE reports SET evidence_data = %s, updated_at = NOW() WHERE id = %s", (
            json.dumps(ev, ensure_ascii=False),
            report_id
        ))
        conn.commit()
        cursor.close()
    except Exception as e:
        logger.error(f"[AI GATEWAY] Gagal menyinkronkan laporan #{report_id}: {e}")
