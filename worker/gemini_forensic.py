"""
AR Tilik - Google Gemini AI Forensic Audit Engine
Modul audit semantik, verifikasi integritas metodologi, deteksi halusinasi sitasi,
dan generator pertanyaan ujian skripsi berbasis Google Gemini API (Pool Round-Robin).
"""

import json
import logging
import re
import time
import urllib.request
import urllib.error
from typing import Dict, List, Any, Optional

from worker.database import get_connection

logger = logging.getLogger("artilik.gemini_forensic")


def get_available_gemini_keys(conn=None) -> List[Dict[str, Any]]:
    """
    Mengambil daftar Google API Key aktif dari database dengan rotasi round-robin
    (diurutkan berdasarkan last_used_at ASC).
    """
    close_after = False
    if conn is None:
        conn = get_connection()
        close_after = True

    try:
        cursor = conn.cursor(dictionary=True)
        # Pulihkan kunci rate_limited yang sudah melewati batas waktu jeda (cooldown)
        cursor.execute("""
            UPDATE google_api_keys 
            SET status = 'active', rate_limited_until = NULL 
            WHERE status = 'rate_limited' AND rate_limited_until <= NOW()
        """)
        conn.commit()

        # Ambil kunci yang aktif dan siap dipakai
        cursor.execute("""
            SELECT id, key_name, api_key, model_name, usage_count, error_count, rate_limited_until
            FROM google_api_keys
            WHERE status = 'active' AND (rate_limited_until IS NULL OR rate_limited_until <= NOW())
            ORDER BY COALESCE(last_used_at, '2000-01-01 00:00:00') ASC, id ASC
        """)
        keys = cursor.fetchall() or []
        cursor.close()
        return keys
    except Exception as e:
        logger.error(f"[GEMINI] Gagal mengambil daftar kunci API dari database: {e}")
        return []
    finally:
        if close_after and conn:
            conn.close()


def mark_key_success(key_id: int, conn=None):
    """Mencatat penggunaan sukses pada API key."""
    close_after = False
    if conn is None:
        conn = get_connection()
        close_after = True
    try:
        cursor = conn.cursor()
        cursor.execute("""
            UPDATE google_api_keys 
            SET usage_count = usage_count + 1, last_used_at = NOW(), rate_limited_until = NULL, last_error_message = NULL
            WHERE id = %s
        """, (key_id,))
        conn.commit()
        cursor.close()
    except Exception as e:
        logger.warning(f"[GEMINI] Gagal mengupdate status sukses key #{key_id}: {e}")
    finally:
        if close_after and conn:
            conn.close()


def mark_key_rate_limited(key_id: int, error_msg: str = "", cooldown_seconds: int = 60, conn=None):
    """Menandai kunci terkena rate-limit / kuota sesaat dengan masa cooldown."""
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

        return {
            "success": False,
            "status_code": e.code,
            "error": err_content,
            "is_quota": (e.code == 429) or ("RESOURCE_EXHAUSTED" in err_content),
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
            "is_quota": False
        }


def audit_proposal_with_gemini(
    document_text: str,
    substantive_text: str = "",
    title: str = "",
    connection=None
) -> Dict[str, Any]:
    """
    Menjalankan audit forensik semantik menggunakan Google Gemini API
    dengan rotasi Round-Robin dan failover otomatis saat kuota habis.
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

    current_year = time.strftime("%Y")
    current_date_str = time.strftime("%d %B %Y")

    system_prompt = f"""Anda adalah Auditor Forensik Integritas Akademik Independen untuk sistem AR Tilik.
Tugas Anda adalah menganalisis draf karya ilmiah/proposal berikut untuk mendeteksi inkonsistensi logika, 
kontradiksi metodologi, distorsi sitasi (halusinasi AI), dan memberikan panduan pertanyaan ujian lisan bagi dosen.

Konteks Waktu Saat Ini: Tahun {current_year} (per tanggal {current_date_str}).
Catatan Penting Garis Waktu: Pemeriksaan dilakukan pada tahun {current_year}. Publikasi dan data bertahun <= {current_year} (termasuk tahun {int(current_year)-1} dan {current_year}) adalah referensi yang realistis dan sah dalam garis waktu naskah saat ini, jangan anggap tahun {current_year} atau sebelumnya sebagai "sitasi masa depan". Hanya tandai anomali masa depan jika terdapat rujukan dengan tahun > {current_year} atau fabrikasi sitasi fiktif.

Judul Dokumen: {title or 'Karya Ilmiah'}

Potongan Naskah:
\"\"\"{truncated_content}\"\"\"

Lakukan audit kritis dan kembalikan HANYA format JSON valid dengan struktur persis berikut:
{{
    "tingkat_indikasi_ai": "TINGGI" | "SEDANG" | "RENDAH",
    "alasan_indikasi_ai": "Ringkasan penilaian apakah terdapat perakitan draf/template AI",
    "inkonsistensi_metodologi_dan_logika": [
        "Jelaskan kontradiksi jika ada (misal: menyebut regresi tetapi diagram/lampiran menuliskan uji-t, atau variabel di teori berbeda dengan di kuesioner)"
    ],
    "integritas_sitasi": [
        "Jelaskan anomali kutipan jika ada (misal: nama tokoh dijadikan author, tahun referensi janggal, format daftar pustaka berantakan)"
    ],
    "pertanyaan_kritis_dosen": [
        {{
            "fokus": "Metodologi / Teori / Definisi Operasional / Sitasi",
            "pertanyaan": "Pertanyaan lisan tajam yang wajib ditanyakan penguji untuk menguji pemahaman mahasiswa",
            "alasan": "Mengapa pertanyaan ini penting diajukan"
        }}
    ]
}}
"""

    # Eksekusi dengan Round-Robin & Failover antar API Key
    last_error = ""
    for k in keys:
        key_id = k["id"]
        key_name = k["key_name"]
        api_key = k["api_key"]
        model_name = k.get("model_name") or "gemini-3.6-flash"

        logger.info(f"[GEMINI] Mencoba audit dengan kunci #{key_id} ('{key_name}', model={model_name})...")
        res = call_gemini_api(api_key, model_name, system_prompt)

        if res.get("success"):
            # Sukses
            mark_key_success(key_id, connection)
            raw_text = res.get("raw_text", "")
            try:
                # Bersihkan pembungkus markdown ```json ... ``` bila ada
                clean_json_str = re.sub(r"^```(?:json)?\s*", "", raw_text.strip(), flags=re.MULTILINE)
                clean_json_str = re.sub(r"\s*```$", "", clean_json_str.strip(), flags=re.MULTILINE)
                parsed_audit = json.loads(clean_json_str)
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
                    "tingkat_indikasi_ai": ai_level_match.group(1).upper() if ai_level_match else "SEDANG",
                    "alasan_indikasi_ai": ai_reason_match.group(1).strip() if ai_reason_match else raw_text[:500],
                    "inkonsistensi_metodologi_dan_logika": [],
                    "integritas_sitasi": [],
                    "pertanyaan_kritis_dosen": []
                }
                return {
                    "status": "completed",
                    "is_available": True,
                    "model_used": res.get("model_executed") or model_name,
                    "key_used": key_name,
                    "audit": fallback_audit,
                    "raw_output": raw_text
                }
        else:
            # Gagal: cek apakah karena kuota / rate limit
            is_quota = res.get("is_quota", False)
            err_msg = res.get("error", "Unknown error")
            last_error = err_msg

            if is_quota:
                mark_key_rate_limited(key_id, err_msg, cooldown_seconds=60, conn=connection)
                logger.warning(f"[GEMINI] Kuota kunci '{key_name}' habis (429/ResourceExhausted). Beralih ke kunci berikutnya...")
            elif res.get("status_code") == 503 or "high demand" in err_msg.lower():
                logger.warning(f"[GEMINI] Google sedang padat antrean / high demand (503) untuk kunci '{key_name}'. Beralih ke kunci berikutnya...")
            elif res.get("status_code") in (400, 403) or "denied access" in err_msg.lower():
                try:
                    cursor = connection.cursor() if connection else get_connection().cursor()
                    cursor.execute("""
                        UPDATE google_api_keys 
                        SET status = 'inactive', 
                            error_count = error_count + 1,
                            last_error_message = %s 
                        WHERE id = %s
                    """, (err_msg[:450], key_id))
                    if connection:
                        connection.commit()
                except Exception as de_err:
                    logger.error(f"[GEMINI] Gagal menonaktifkan kunci #{key_id}: {de_err}")
                logger.warning(f"[GEMINI] Kunci '{key_name}' ditolak/tidak valid ({res.get('status_code')}). Dinonaktifkan otomatis: {err_msg}")
            else:
                logger.warning(f"[GEMINI] Error pada kunci '{key_name}': {err_msg}")

    return {
        "status": "failed",
        "is_available": False,
        "message": f"Seluruh Google API Key dalam pool gagal merespon atau kuota habis: {last_error}"
    }
