"""
AR Tilik - Google Gemini AI Forensic Audit Engine
Modul audit semantik, verifikasi integritas metodologi, deteksi halusinasi sitasi,
dan generator pertanyaan ujian skripsi berbasis Google Gemini API (Pool Round-Robin).
"""

import json
import logging
import os
import random
import re
import time
import urllib.request
import urllib.error
from datetime import datetime
from zoneinfo import ZoneInfo
from typing import Dict, List, Any, Optional

from worker.database import get_connection

logger = logging.getLogger("artilik.gemini_forensic")

# Lokasi berkas log metrik JSONL
METRICS_LOG_PATH = os.environ.get(
    "GEMINI_METRICS",
    os.path.join(os.path.dirname(os.path.dirname(os.path.abspath(__file__))), "writable", "logs", "gemini_metrics.jsonl")
)


def get_pacific_date_str() -> str:
    """Mengembalikan tanggal saat ini di zona waktu US Pacific Time (PT). Google Cloud mereset kuota harian saat Pacific Midnight."""
    try:
        return datetime.now(ZoneInfo("America/Los_Angeles")).strftime("%Y-%m-%d")
    except Exception:
        return time.strftime("%Y-%m-%d")


def calculate_exponential_backoff(error_count: int = 0, base_backoff: float = 60.0, max_backoff: float = 900.0) -> int:
    """
    Menghitung durasi jeda cooldown dengan pola backoff eksponensial dan jitter acak.
    Rumus: min(base * 2^(fails - 1), max) + random(2, 10).
    60s -> 120s -> 240s -> ... (maks 900s / 15 menit).
    """
    fails = max(1, int(error_count) + 1)
    backoff = min(base_backoff * (2 ** (min(fails, 5) - 1)), max_backoff)
    jitter = random.uniform(2.0, 10.0)
    return int(backoff + jitter)


def log_gemini_metric(event: str, key_name: str, api_key: str, extra: Optional[Dict[str, Any]] = None):
    """
    Mencatat aktivitas pemakaian kunci API ke format JSONL secara non-blocking.
    """
    key_fp = (api_key[:6] + "..." + api_key[-4:]) if len(api_key) > 10 else "masked_key"
    rec = {
        "ts": time.strftime("%Y-%m-%dT%H:%M:%S"),
        "event": event,
        "key_name": key_name,
        "key_fp": key_fp,
    }
    if extra:
        rec.update(extra)
    try:
        os.makedirs(os.path.dirname(METRICS_LOG_PATH), exist_ok=True)
        with open(METRICS_LOG_PATH, "a", encoding="utf-8") as f:
            f.write(json.dumps(rec) + "\n")
    except Exception:
        pass


def get_gemini_daily_summary(metrics_path: Optional[str] = None) -> Dict[str, Any]:
    """
    Membaca agregasi metrik harian dari berkas JSONL untuk pemantauan/diagnostik.
    """
    path = metrics_path or METRICS_LOG_PATH
    today = time.strftime("%Y-%m-%d")
    agg: Dict[str, Dict[str, int]] = {}
    try:
        if os.path.exists(path):
            with open(path, "r", encoding="utf-8") as f:
                for line in f:
                    try:
                        rec = json.loads(line)
                    except Exception:
                        continue
                    if not rec.get("ts", "").startswith(today):
                        continue
                    fp = rec.get("key_fp", "unknown")
                    d = agg.setdefault(fp, {"success": 0, "ratelimited_rpm": 0, "ratelimited_daily": 0, "error": 0})
                    evt = rec.get("event", "other")
                    d[evt] = d.get(evt, 0) + 1
    except Exception:
        pass

    total_ok = sum(d.get("success", 0) for d in agg.values())
    total_429 = sum(d.get("ratelimited_rpm", 0) + d.get("ratelimited_daily", 0) for d in agg.values())
    return {
        "date": today,
        "total_success": total_ok,
        "total_429": total_429,
        "per_key": agg
    }


def get_available_gemini_keys(conn=None) -> List[Dict[str, Any]]:
    """
    Mengambil daftar Google API Key aktif dari database dengan rotasi cerdas berbasis kesehatan:
    - Memulihkan kunci rate_limited yang masa jedanya (cooldown) telah selesai.
    - Memulihkan kunci exhausted jika hari ini di zona waktu Pacific Time sudah berganti.
    - Mengurutkan berdasarkan error_count ASC, usage_count ASC, last_used_at ASC (paling sehat & paling sepi).
    - Fallback ke env GEMINI_API_KEYS jika database tidak memiliki kunci aktif.
    """
    close_after = False
    if conn is None:
        conn = get_connection()
        close_after = True

    try:
        cursor = conn.cursor(dictionary=True)
        # 1. Pulihkan kunci rate_limited yang sudah melewati batas waktu jeda (cooldown)
        cursor.execute("""
            UPDATE google_api_keys 
            SET status = 'active', rate_limited_until = NULL 
            WHERE status = 'rate_limited' AND rate_limited_until <= NOW()
        """)

        # 2. Pulihkan kunci exhausted jika sudah melewati Midnight Pacific Time
        pt_today = get_pacific_date_str()
        cursor.execute("SELECT id, updated_at FROM google_api_keys WHERE status = 'exhausted'")
        exhausted_rows = cursor.fetchall() or []
        for erow in exhausted_rows:
            up_at = erow.get("updated_at")
            should_reset = False
            if up_at:
                try:
                    up_pt = up_at.astimezone(ZoneInfo("America/Los_Angeles")).strftime("%Y-%m-%d") if hasattr(up_at, "astimezone") else str(up_at)[:10]
                    if up_pt < pt_today:
                        should_reset = True
                except Exception:
                    should_reset = True
            else:
                should_reset = True

            if should_reset:
                cursor.execute("""
                    UPDATE google_api_keys 
                    SET status = 'active', rate_limited_until = NULL, error_count = 0 
                    WHERE id = %s
                """, (erow["id"],))

        conn.commit()

        # 3. Ambil kunci aktif diurutkan: error paling sedikit, penggunaan terendah, waktu pakai terlama
        cursor.execute("""
            SELECT id, key_name, api_key, model_name, usage_count, error_count, rate_limited_until
            FROM google_api_keys
            WHERE status = 'active' AND (rate_limited_until IS NULL OR rate_limited_until <= NOW())
            ORDER BY error_count ASC, usage_count ASC, COALESCE(last_used_at, '2000-01-01 00:00:00') ASC, id ASC
        """)
        keys = cursor.fetchall() or []
        cursor.close()

        # 4. Fallback ke environment variable jika tabel database kosong
        if not keys:
            env_raw = os.environ.get("GEMINI_API_KEYS", "")
            if env_raw.strip():
                raw_keys = [k.strip() for k in env_raw.split(",") if k.strip()]
                keys = [
                    {
                        "id": None,
                        "key_name": f"ENV_KEY_{i+1}",
                        "api_key": k,
                        "model_name": os.environ.get("GEMINI_DEFAULT_MODEL", "gemini-3.6-flash"),
                        "usage_count": 0,
                        "error_count": 0,
                        "rate_limited_until": None,
                    }
                    for i, k in enumerate(raw_keys)
                ]

        return keys
    except Exception as e:
        logger.error(f"[GEMINI] Gagal mengambil daftar kunci API dari database: {e}")
        return []
    finally:
        if close_after and conn:
            conn.close()


def mark_key_success(key_id: Optional[int], conn=None):
    """Mencatat penggunaan sukses pada API key, mereset counter gagal berturut-turut."""
    if not key_id:
        return
    close_after = False
    if conn is None:
        conn = get_connection()
        close_after = True
    try:
        cursor = conn.cursor()
        cursor.execute("""
            UPDATE google_api_keys 
            SET usage_count = usage_count + 1, 
                error_count = 0,
                last_used_at = NOW(), 
                rate_limited_until = NULL, 
                last_error_message = NULL
            WHERE id = %s
        """, (key_id,))
        conn.commit()
        cursor.close()
    except Exception as e:
        logger.warning(f"[GEMINI] Gagal mengupdate status sukses key #{key_id}: {e}")
    finally:
        if close_after and conn:
            conn.close()


def mark_key_rate_limited(key_id: Optional[int], error_msg: str = "", cooldown_seconds: Optional[int] = None, conn=None):
    """Menandai kunci terkena rate-limit / kuota sesaat (RPM) dengan masa cooldown eksponensial."""
    if not key_id:
        return
    if cooldown_seconds is None or cooldown_seconds <= 0:
        cooldown_seconds = calculate_exponential_backoff(0)
    close_after = False
    if conn is None:
        conn = get_connection()
        close_after = True
    try:
        cursor = conn.cursor()
        cursor.execute("""
            UPDATE google_api_keys 
            SET status = 'rate_limited', 
                error_count = error_count + 1,
                rate_limited_until = DATE_ADD(NOW(), INTERVAL %s SECOND),
                last_error_message = %s
            WHERE id = %s
        """, (cooldown_seconds, error_msg[:450], key_id))
        conn.commit()
        cursor.close()
        logger.warning(f"[GEMINI] Kunci #{key_id} terkena limit kuota. Dijeda selama {cooldown_seconds} detik.")
    except Exception as e:
        logger.error(f"[GEMINI] Gagal menandai rate limit key #{key_id}: {e}")
    finally:
        if close_after and conn:
            conn.close()


def mark_key_exhausted(key_id: Optional[int], error_msg: str = "", conn=None):
    """Menandai kunci kuota harian habis (RPD / RESOURCE_EXHAUSTED per day) agar istirahat sampai besok."""
    if not key_id:
        return
    close_after = False
    if conn is None:
        conn = get_connection()
        close_after = True
    try:
        cursor = conn.cursor()
        cursor.execute("""
            UPDATE google_api_keys 
            SET status = 'exhausted', 
                error_count = error_count + 1,
                last_error_message = %s
            WHERE id = %s
        """, (error_msg[:450], key_id))
        conn.commit()
        cursor.close()
        logger.warning(f"[GEMINI] Kunci #{key_id} kuota harian habis (exhausted).")
    except Exception as e:
        logger.error(f"[GEMINI] Gagal menandai kunci exhausted #{key_id}: {e}")
    finally:
        if close_after and conn:
            conn.close()


def mark_key_inactive(key_id: Optional[int], error_msg: str = "", conn=None):
    """Menonaktifkan kunci yang ditolak permanen (HTTP 400/401/403 atau invalid key)."""
    if not key_id:
        return
    close_after = False
    if conn is None:
        conn = get_connection()
        close_after = True
    try:
        cursor = conn.cursor()
        cursor.execute("""
            UPDATE google_api_keys 
            SET status = 'inactive', 
                error_count = error_count + 1,
                last_error_message = %s
            WHERE id = %s
        """, (error_msg[:450], key_id))
        conn.commit()
        cursor.close()
        logger.warning(f"[GEMINI] Kunci #{key_id} dinonaktifkan otomatis: {error_msg[:200]}")
    except Exception as e:
        logger.error(f"[GEMINI] Gagal menonaktifkan kunci #{key_id}: {e}")
    finally:
        if close_after and conn:
            conn.close()


def call_gemini_api(api_key: str, model_name: str, prompt: str, timeout: int = 45, can_fallback: bool = True) -> Dict[str, Any]:
    """
    Mengirimkan request ke Google Generative Language API via HTTP POST.
    """
    model = model_name or "gemini-3.5-flash"
    url = f"https://generativelanguage.googleapis.com/v1beta/models/{model}:generateContent?key={api_key}"

    payload = {
        "contents": [
            {
                "parts": [
                    {"text": prompt}
                ]
            }
        ],
        "generationConfig": {
            "temperature": 0.2,
            "maxOutputTokens": 8192,
            "responseMimeType": "application/json"
        }
    }

    req_data = json.dumps(payload).encode("utf-8")
    req = urllib.request.Request(
        url,
        data=req_data,
        headers={"Content-Type": "application/json"}
    )

    try:
        with urllib.request.urlopen(req, timeout=timeout) as response:
            res_body = response.read().decode("utf-8")
            data = json.loads(res_body)
            # Ambil seluruh teks jawaban dari seluruh parts
            candidates = data.get("candidates", [])
            if candidates:
                parts = candidates[0].get("content", {}).get("parts", [])
                if parts:
                    text_parts = [p.get("text", "") for p in parts if isinstance(p, dict) and "text" in p]
                    full_text = "".join(text_parts).strip()
                    if full_text:
                        return {
                            "success": True,
                            "raw_text": full_text,
                            "status_code": 200,
                            "model_executed": model,
                        }
            return {
                "success": False,
                "error": "Respon kosong dari Gemini",
                "status_code": 200,
                "model_executed": model,
            }
    except urllib.error.HTTPError as e:
        err_content = e.read().decode("utf-8", errors="ignore")
        # Jika model mengalami kendala 503 (high demand/overload) atau 404, lakukan fallback otomatis ke gemini-3.5-flash
        is_503 = (e.code == 503) or ("high demand" in err_content.lower()) or ("unavailable" in err_content.lower())
        is_404 = (e.code == 404)
        if can_fallback and (is_503 or is_404) and model != "gemini-3.5-flash":
            logger.info(f"[GEMINI] Model '{model}' menghasilkan HTTP {e.code}. Mencoba fallback ke 'gemini-3.5-flash'...")
            fallback_res = call_gemini_api(api_key, "gemini-3.5-flash", prompt, timeout=timeout, can_fallback=False)
            if fallback_res.get("success"):
                fallback_res["model_executed"] = "gemini-3.5-flash"
                return fallback_res

        is_quota = (e.code == 429) or ("RESOURCE_EXHAUSTED" in err_content)
        err_lower = err_content.lower()
        is_daily_quota = is_quota and ("per day" in err_lower or "daily" in err_lower or "quota exceeded for quota metric" in err_lower and "day" in err_lower)

        return {
            "success": False,
            "status_code": e.code,
            "error": err_content,
            "is_quota": is_quota,
            "is_daily_quota": is_daily_quota,
            "model_executed": model,
        }
    except Exception as e:
        err_str = str(e)
        if can_fallback and model != "gemini-3.5-flash" and ("timed out" in err_str.lower() or "timeout" in err_str.lower()):
            logger.info(f"[GEMINI] Model '{model}' mengalami timeout. Mencoba fallback ke 'gemini-3.5-flash'...")
            fallback_res = call_gemini_api(api_key, "gemini-3.5-flash", prompt, timeout=timeout, can_fallback=False)
            if fallback_res.get("success"):
                fallback_res["model_executed"] = "gemini-3.5-flash"
                return fallback_res
        return {
            "success": False,
            "status_code": 0,
            "error": err_str,
            "is_quota": False,
            "is_daily_quota": False
        }


# ─────────────────────────────────────────────────────────────────────────────
# VALIDASI KLAIM "INDIKASI AI" (anti false-positive)
# Prinsip: LLM tidak boleh menjadi hakim tunggal. Klaim SEDANG/TINGGI hanya dipertahankan
# bila (a) naskah bukan dari era pra-LLM menurut metadata, dan (b) setidaknya
# MIN_VERIFIED_QUOTES kutipan bukti yang diberikan LLM benar-benar ada di naskah.
# ─────────────────────────────────────────────────────────────────────────────
MIN_VERIFIED_QUOTES = 2
MIN_QUOTE_CHARS = 20

# Penanda kebocoran percakapan chatbot pada teks naskah (deterministik, bukan opini LLM)
META_PROMPT_LEAK_PATTERNS = [
    r"\bbisa\.\s*saya akan buat\b",
    r"\btentu,?\s*(?:berikut|ini)\s+(?:adalah\s+)?(?:draf|contoh|versi|bab|revisi)\b",
    r"\bsebagai (?:sebuah )?model bahasa(?: ai| buatan)?(?:,| saya| yang)\b",
    r"\bas an ai (?:language )?model\b",
    r"\bregenerate response\b",
    r"\bsaya tidak dapat (?:memenuhi|membantu) permintaan (?:ini|tersebut)\b",
]


def _norm_for_match(text: str) -> str:
    return re.sub(r"[\W_]+", " ", (text or "").lower()).strip()


def detect_meta_prompt_leak(full_text: str) -> List[str]:
    """Mencari sisa percakapan chatbot pada teks naskah. Mengembalikan daftar cuplikan yang cocok."""
    hits: List[str] = []
    for pat in META_PROMPT_LEAK_PATTERNS:
        for m in re.finditer(pat, full_text or "", flags=re.IGNORECASE):
            start = max(0, m.start() - 40)
            end = min(len(full_text), m.end() + 40)
            hits.append(full_text[start:end].replace("\n", " ").strip())
            if len(hits) >= 5:
                return hits
    return hits


def postprocess_ai_claim(audit: Dict[str, Any], full_text: str, doc_metadata: Optional[Dict[str, Any]] = None) -> Dict[str, Any]:
    """
    Memvalidasi klaim indikasi AI dari LLM terhadap naskah dan metadata.
    Menambahkan field: bukti_ai_terverifikasi, kutipan_terverifikasi, meta_prompt_leak_terverifikasi,
    catatan_validasi. Klaim yang tidak lolos diturunkan ke RENDAH (nilai asli disimpan di *_mentah).
    """
    audit = dict(audit or {})
    raw_level = str(audit.get("tingkat_indikasi_ai", "RENDAH") or "RENDAH").strip().upper()
    if raw_level not in ("RENDAH", "SEDANG", "TINGGI"):
        raw_level = "RENDAH"

    # 1. Kebocoran prompt: deterministik pada teks, tidak bergantung pada LLM
    leak_hits = detect_meta_prompt_leak(full_text)
    audit["meta_prompt_leak_terverifikasi"] = bool(leak_hits)
    audit["meta_prompt_leak_cuplikan"] = leak_hits

    # 2. Verifikasi kutipan bukti terhadap naskah
    norm_doc = _norm_for_match(full_text)
    quotes = audit.get("bukti_kutipan_ai") or []
    if not isinstance(quotes, list):
        quotes = []
    verified = []
    for q in quotes:
        nq = _norm_for_match(str(q))
        if len(nq) >= MIN_QUOTE_CHARS and nq in norm_doc:
            verified.append(str(q).strip())
    audit["kutipan_terverifikasi"] = verified

    # 3. Metadata era pra-LLM
    pre_llm = bool((doc_metadata or {}).get("is_pre_llm_era"))
    audit["metadata_pra_llm"] = pre_llm

    notes = []
    final_level = raw_level
    if raw_level in ("SEDANG", "TINGGI"):
        if pre_llm and not leak_hits:
            final_level = "RENDAH"
            notes.append(
                "Klaim indikasi AI diturunkan: metadata berkas menunjukkan naskah dibuat dan terakhir diubah "
                "sebelum era LLM generatif (sebelum 30 Nov 2022)."
            )
        elif len(verified) < MIN_VERIFIED_QUOTES:
            final_level = "RENDAH"
            notes.append(
                f"Klaim indikasi AI diturunkan: hanya {len(verified)} dari {len(quotes)} kutipan bukti yang dapat "
                f"diverifikasi pada naskah (minimal {MIN_VERIFIED_QUOTES})."
            )

    audit["tingkat_indikasi_ai_mentah"] = raw_level
    if final_level != raw_level:
        audit["alasan_indikasi_ai_mentah"] = audit.get("alasan_indikasi_ai", "")
        audit["alasan_indikasi_ai"] = (
            "Tidak ditemukan bukti tekstual konkret ciri keluaran AI yang dapat diverifikasi pada naskah. "
            "Gaya bahasa dan alur berpikir mencerminkan tulisan mahasiswa secara alami. "
            "Temuan lain (inkonsistensi logika/sitasi) disajikan terpisah sebagai catatan kualitas metodologi, bukan indikasi AI."
        )
    audit["tingkat_indikasi_ai"] = final_level
    audit["bukti_ai_terverifikasi"] = (final_level in ("SEDANG", "TINGGI")) and len(verified) >= MIN_VERIFIED_QUOTES
    audit["catatan_validasi"] = notes

    # Sanitasi istilah: ganti ungkapan keliru 'tulisan tangan' menjadi 'tulisan mahasiswa'
    if "alasan_indikasi_ai" in audit and isinstance(audit["alasan_indikasi_ai"], str):
        audit["alasan_indikasi_ai"] = re.sub(
            r'\btulisan\s+tangan(?:\s+manusia)?\b',
            'tulisan mahasiswa',
            audit["alasan_indikasi_ai"],
            flags=re.IGNORECASE
        )

    return audit


def audit_proposal_with_gemini(
    document_text: str,
    substantive_text: str = "",
    title: str = "",
    connection=None,
    doc_metadata: Optional[Dict[str, Any]] = None
) -> Dict[str, Any]:
    """
    Menjalankan audit forensik semantik menggunakan Google Gemini API
    dengan rotasi cerdas berbasis kesehatan, backoff eksponensial, dan failover otomatis.
    """
    keys = get_available_gemini_keys(connection)
    if not keys:
        return {
            "status": "skipped",
            "message": "Tidak ada Google API Key aktif di database. Fitur audit semantik dilewati.",
            "is_available": False
        }

    # Persiapkan ekstrak teks naskah (maksimal ~25.000 karakter agar hemat token dan cepat)
    content_sample = substantive_text if (substantive_text and len(substantive_text.strip()) >= 500) else document_text
    truncated_content = content_sample[:25000]

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

Potongan Naskah:
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

    # Eksekusi dengan Pool Cerdas Berbasis Kesehatan & Failover antar API Key
    last_error = ""
    for k in keys:
        key_id = k.get("id")
        key_name = k.get("key_name", "Kunci")
        api_key = k["api_key"]
        model_name = k.get("model_name") or "gemini-3.6-flash"

        t0 = time.time()
        logger.info(f"[GEMINI] Mencoba audit dengan kunci #{key_id} ('{key_name}', model={model_name})...")
        res = call_gemini_api(api_key, model_name, system_prompt)
        latency = round(time.time() - t0, 2)

        if res.get("success"):
            # Sukses: update DB dan log metrik
            mark_key_success(key_id, connection)
            log_gemini_metric("success", key_name, api_key, {
                "latency_s": latency,
                "model": res.get("model_executed") or model_name
            })
            raw_text = res.get("raw_text", "")
            try:
                # Bersihkan pembungkus markdown ```json ... ``` bila ada
                clean_json_str = re.sub(r"^```(?:json)?\s*", "", raw_text.strip(), flags=re.MULTILINE)
                clean_json_str = re.sub(r"\s*```$", "", clean_json_str.strip(), flags=re.MULTILINE)
                parsed_audit = postprocess_ai_claim(json.loads(clean_json_str), document_text, doc_metadata)
                return {
                    "status": "completed",
                    "is_available": True,
                    "model_used": res.get("model_executed") or model_name,
                    "key_used": key_name,
                    "audit": parsed_audit
                }
            except Exception as json_err:
                logger.warning(f"[GEMINI] Gagal mengurai JSON respon dari Gemini: {json_err}. Mencoba pemulihan regex...")
                ai_level_match = re.search(r'"tingkat_indikasi_ai"\s*:\s*"([^"]+)"', raw_text, re.IGNORECASE)
                ai_reason_match = re.search(r'"alasan_indikasi_ai"\s*:\s*"([^"]+)"', raw_text)
                
                fallback_audit = {
                    "tingkat_indikasi_ai": ai_level_match.group(1).upper() if ai_level_match else "RENDAH",
                    "alasan_indikasi_ai": ai_reason_match.group(1).strip() if ai_reason_match else raw_text[:500],
                    "bukti_kutipan_ai": [],
                    "inkonsistensi_metodologi_dan_logika": [],
                    "integritas_sitasi": [],
                    "pertanyaan_kritis_dosen": []
                }
                fallback_audit = postprocess_ai_claim(fallback_audit, document_text, doc_metadata)
                return {
                    "status": "completed",
                    "is_available": True,
                    "model_used": res.get("model_executed") or model_name,
                    "key_used": key_name,
                    "audit": fallback_audit,
                    "raw_output": raw_text
                }

        else:
            # Gagal: klasifikasi error untuk backoff & update status
            is_quota = res.get("is_quota", False)
            is_daily_quota = res.get("is_daily_quota", False)
            err_msg = res.get("error", "Unknown error")
            err_lower = err_msg.lower()
            last_error = err_msg

            if is_quota:
                if is_daily_quota or ("per day" in err_lower) or ("daily" in err_lower):
                    mark_key_exhausted(key_id, err_msg, conn=connection)
                    log_gemini_metric("ratelimited_daily", key_name, api_key, {"error": err_msg[:200]})
                    logger.warning(f"[GEMINI] Kuota harian (RPD) kunci '{key_name}' habis total. Ditandai exhausted.")
                else:
                    current_fails = k.get("error_count", 0)
                    cooldown = calculate_exponential_backoff(current_fails)
                    mark_key_rate_limited(key_id, err_msg, cooldown_seconds=cooldown, conn=connection)
                    log_gemini_metric("ratelimited_rpm", key_name, api_key, {"cooldown_s": cooldown, "error": err_msg[:200]})
                    logger.warning(f"[GEMINI] Kuota per-menit kunci '{key_name}' kena rate-limit (429/RPM). Backoff jeda {cooldown} detik.")
            elif res.get("status_code") in (400, 401, 403) or "denied access" in err_lower or "api_key_invalid" in err_lower or "permission_denied" in err_lower:
                mark_key_inactive(key_id, err_msg, conn=connection)
                log_gemini_metric("disabled", key_name, api_key, {"status_code": res.get("status_code"), "error": err_msg[:200]})
                logger.warning(f"[GEMINI] Kunci '{key_name}' ditolak/tidak valid ({res.get('status_code')}). Dinonaktifkan otomatis: {err_msg[:200]}")
            elif res.get("status_code") == 503 or "high demand" in err_lower or "unavailable" in err_lower:
                cooldown = int(30 + random.uniform(2, 8))
                mark_key_rate_limited(key_id, err_msg, cooldown_seconds=cooldown, conn=connection)
                log_gemini_metric("error_503", key_name, api_key, {"cooldown_s": cooldown})
                logger.warning(f"[GEMINI] Google sedang padat antrean / overload (503) untuk kunci '{key_name}'. Dijeda {cooldown} detik.")
            else:
                log_gemini_metric("error", key_name, api_key, {"status_code": res.get("status_code"), "error": err_msg[:200]})
                logger.warning(f"[GEMINI] Error pada kunci '{key_name}': {err_msg[:200]}")

    return {
        "status": "failed",
        "is_available": False,
        "message": f"Seluruh Google API Key dalam pool gagal merespon atau kuota habis: {last_error}"
    }
