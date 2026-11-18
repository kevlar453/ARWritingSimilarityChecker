"""
AR Tilik - External Citation & Publication Verifier (OpenAlex & Crossref)
Memverifikasi keabsahan ontologis rujukan ilmiah untuk mendeteksi sitasi fiktif / halusinasi AI
melalui pangkalan data metadata karya ilmiah global (>250 juta rekaman via OpenAlex & Crossref API).

Fitur:
1. Ekstraksi judul karya dari entri Daftar Pustaka berbagai gaya (APA, IEEE, Harvard, MLA).
2. Panggilan API terukur ke OpenAlex (Polite Pool) dengan fallback ke Crossref.
3. Cache persisten di MariaDB (external_citation_cache) untuk efisiensi latensi.
4. Klasifikasi status: VERIFIED_REAL, SUSPECTED_FABRICATED, UNVERIFIED.
5. Toleran terhadap kesalahan jaringan (graceful fallback).
"""

import os
import re
import ssl
import json
import time
import socket
import hashlib
import difflib
import http.client
import threading
import unicodedata
import urllib.error
import urllib.request
import urllib.parse
from concurrent.futures import ThreadPoolExecutor, as_completed
from typing import Callable, Dict, List, Any, Optional, Tuple


OPENALEX_BASE_URL = "https://api.openalex.org/works"
CROSSREF_BASE_URL = "https://api.crossref.org/works"
DEFAULT_MAILTO = "admin@tilik.arsetontong.top"
REQUEST_TIMEOUT = 3.5  # Detik per entri
MAX_CONCURRENT_REQUESTS = 3


def _get_mailto() -> str:
    """Mengambil email polite pool dari variabel lingkungan."""
    return os.environ.get("OPENALEX_MAILTO") or DEFAULT_MAILTO


def extract_publication_title(raw_entry: str, author_keys: Optional[List[str]] = None, year: Optional[str] = None) -> str:
    """
    Mengekstrak judul karya ilmiah dari teks mentah entri daftar pustaka.
    """
    if not raw_entry:
        return ""

    text = raw_entry.strip()

    # 1. Judul dalam tanda kutip (sering di IEEE atau format tertentu)
    q_match = re.search(r'[\"“\']([^\"”\']{8,200})[\"”\']', text)
    if q_match:
        cand = q_match.group(1).strip(' .,;:')
        if len(cand.split()) >= 2:
            return cand

    # 2. Format standar Author-Year: Penulis (YYYY). Judul artikel...
    # Cari pola tahun dalam kurung: (1997), (2022a), dsb.
    y_match = re.search(r'\((?:19|20)\d{2}[a-z]?\)[.\s]*', text)
    if y_match:
        after_year = text[y_match.end():].strip()
        # Ambil kalimat/frasa pertama setelah tahun hingga tanda titik, tanya, atau seru
        parts = [p.strip(' .,;:') for p in re.split(r'\.\s+|\?\s+|\!\s+', after_year) if p.strip()]
        if parts:
            cand = parts[0]
            # Hanya gabungkan jika bagian pertama hanya 1 kata tunggal dan bagian kedua bukan penerbit/tempat
            if len(cand.split()) == 1 and len(parts) > 1 and ':' not in parts[1] and len(parts[1].split()) >= 2:
                cand = f"{cand}: {parts[1]}"
            return cand.strip(' .,;:')

    # 3. Format bernomor IEEE tanpa kutip: [1] Author, Title, Journal...
    num_match = re.match(r'^(?:\[\d+\]|\d+\.)\s*(.+)$', text)
    if num_match:
        body = num_match.group(1).strip()
        # Potong setelah nama penulis jika ada koma ganda
        comma_parts = [p.strip(' .,;:') for p in body.split(',') if p.strip()]
        if len(comma_parts) >= 2:
            # Ambil bagian kedua sebagai kandidat judul
            return comma_parts[1]

    # 4. Fallback umum: ambil 120 karakter pertama dan bersihkan
    cleaned = re.sub(r'^(?:\[\d+\]|\d+\.)\s*', '', text)
    # Hapus awalan penulis jika ada titik
    dot_parts = [p.strip(' .,;:') for p in cleaned.split('. ') if p.strip()]
    if len(dot_parts) >= 2:
        return dot_parts[1]

    return cleaned[:100].strip(' .,;:')


def _sanitize_title_for_query(title: str) -> str:
    """Membersihkan tanda baca khusus yang dapat merusak query parser OpenAlex/Crossref."""
    if not title:
        return ""
    # Hapus karakter non-alfanumerik selain spasi dan tanda hubung dasar
    clean = re.sub(r'[^a-zA-Z0-9\s\-]', ' ', title)
    # Rapikan spasi berlebih
    return re.sub(r'\s+', ' ', clean).strip()


def _get_cache_connection():
    """Membuka koneksi ke MariaDB untuk keperluan cache verifikasi sitasi."""
    try:
        from worker.database import get_connection
        return get_connection()
    except Exception:
        return None


def get_cached_verification(query_hash: str) -> Optional[Dict[str, Any]]:
    """Mengambil hasil verifikasi dari tabel external_citation_cache."""
    conn = _get_cache_connection()
    if not conn:
        return None
    try:
        cursor = conn.cursor(dictionary=True)
        cursor.execute(
            """SELECT status, confidence_score, openalex_id, doi, matched_title,
                      matched_authors, matched_year, cited_by_count
               FROM external_citation_cache WHERE query_hash = %s LIMIT 1""",
            (query_hash,)
        )
        row = cursor.fetchone()
        cursor.close()
        return row
    except Exception:
        return None
    finally:
        try:
            conn.close()
        except Exception:
            pass


def save_cached_verification(
    query_hash: str,
    raw_title: str,
    author_key: Optional[str],
    year: Optional[str],
    status: str,
    confidence_score: float,
    openalex_id: Optional[str] = None,
    doi: Optional[str] = None,
    matched_title: Optional[str] = None,
    matched_authors: Optional[str] = None,
    matched_year: Optional[int] = None,
    cited_by_count: int = 0
):
    """Menyimpan hasil verifikasi ke tabel external_citation_cache."""
    conn = _get_cache_connection()
    if not conn:
        return
    try:
        cursor = conn.cursor()
        now = time.strftime('%Y-%m-%d %H:%M:%S')
        cursor.execute(
            """INSERT INTO external_citation_cache
               (query_hash, raw_title, author_key, year, status, confidence_score,
                openalex_id, doi, matched_title, matched_authors, matched_year,
                cited_by_count, created_at, updated_at)
               VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)
               ON DUPLICATE KEY UPDATE
               status = VALUES(status), confidence_score = VALUES(confidence_score),
                openalex_id = VALUES(openalex_id), doi = VALUES(doi),
                matched_title = VALUES(matched_title), matched_authors = VALUES(matched_authors),
                matched_year = VALUES(matched_year), cited_by_count = VALUES(cited_by_count),
                updated_at = VALUES(updated_at)""",
            (
                query_hash, raw_title[:490], author_key[:95] if author_key else None,
                year[:10] if year else None, status, confidence_score,
                openalex_id[:95] if openalex_id else None, doi[:250] if doi else None,
                matched_title[:490] if matched_title else None, matched_authors,
                matched_year, cited_by_count, now, now
            )
        )
        conn.commit()
        cursor.close()
    except Exception:
        pass
    finally:
        try:
            conn.close()
        except Exception:
            pass


def _calculate_similarity(str1: str, str2: str) -> float:
    """Menghitung rasio kesamaan string menggunakan SequenceMatcher."""
    if not str1 or not str2:
        return 0.0
    return difflib.SequenceMatcher(None, str1.lower().strip(), str2.lower().strip()).ratio()


def _check_author_match(candidate_authors: List[str], target_authors: List[str]) -> bool:
    """Mengecek apakah salah satu penulis target terdapat pada nama-nama penulis kandidat."""
    if not candidate_authors or not target_authors:
        return False
    cand_lower = " ".join(candidate_authors).lower()
    for ta in target_authors:
        clean_ta = ta.lower().strip()
        if len(clean_ta) >= 3 and clean_ta in cand_lower:
            return True
    return False


def query_openalex(clean_title: str, target_authors: List[str], target_year: Optional[str]) -> Optional[Dict[str, Any]]:
    """Melakukan pencarian karya ilmiah di OpenAlex."""
    mailto = _get_mailto()
    encoded_title = urllib.parse.quote(clean_title)
    url = f"{OPENALEX_BASE_URL}?filter=title.search:{encoded_title}&per-page=3&mailto={mailto}"

    headers = {
        "User-Agent": f"ARTilik-CitationVerifier/1.0 (https://tilik.arsetontong.top; mailto:{mailto})"
    }
    req = urllib.request.Request(url, headers=headers)

    try:
        with urllib.request.urlopen(req, timeout=REQUEST_TIMEOUT) as resp:
            data = json.loads(resp.read().decode("utf-8"))
            results = data.get("results", [])
            if not results:
                return None

            best_item = None
            best_sim = 0.0
            best_author_match = False

            for item in results:
                cand_title = item.get("title") or ""
                sim = _calculate_similarity(clean_title, cand_title)

                cand_authors = [
                    a.get("author", {}).get("display_name", "")
                    for a in item.get("authorships", [])
                ]
                auth_match = _check_author_match(cand_authors, target_authors)

                cand_year = item.get("publication_year")
                year_match = False
                if target_year and cand_year:
                    try:
                        year_match = abs(int(target_year) - int(cand_year)) <= 1
                    except Exception:
                        pass

                # Kriteria kesesuaian:
                # 1. Judul sangat mirip (>= 0.82) dan ada kesesuaian penulis atau tahun
                # 2. Judul hampir identik (>= 0.88)
                if (sim >= 0.82 and (auth_match or year_match)) or (sim >= 0.88):
                    if sim > best_sim:
                        best_sim = sim
                        best_item = item
                        best_author_match = auth_match

            if best_item:
                authors_str = ", ".join([
                    a.get("author", {}).get("display_name", "")
                    for a in best_item.get("authorships", [])[:5]
                ])
                return {
                    "source": "openalex",
                    "openalex_id": best_item.get("id"),
                    "doi": best_item.get("doi"),
                    "matched_title": best_item.get("title"),
                    "matched_authors": authors_str,
                    "matched_year": best_item.get("publication_year"),
                    "cited_by_count": best_item.get("cited_by_count", 0),
                    "similarity": round(best_sim, 3),
                    "author_matched": best_author_match
                }
            return None
    except Exception:
        return None


def query_crossref(clean_title: str, target_authors: List[str], target_year: Optional[str]) -> Optional[Dict[str, Any]]:
    """Pencarian cadangan via Crossref API jika OpenAlex belum mengindeks."""
    mailto = _get_mailto()
    encoded_title = urllib.parse.quote(clean_title)
    url = f"{CROSSREF_BASE_URL}?query.title={encoded_title}&rows=3&mailto={mailto}"

    headers = {
        "User-Agent": f"ARTilik-CitationVerifier/1.0 (mailto:{mailto})"
    }
    req = urllib.request.Request(url, headers=headers)

    try:
        with urllib.request.urlopen(req, timeout=REQUEST_TIMEOUT) as resp:
            data = json.loads(resp.read().decode("utf-8"))
            items = data.get("message", {}).get("items", [])
            for item in items:
                titles = item.get("title", [])
                cand_title = titles[0] if titles else ""
                sim = _calculate_similarity(clean_title, cand_title)

                cand_authors = [
                    f"{a.get('given', '')} {a.get('family', '')}".strip()
                    for a in item.get("author", [])
                ]
                auth_match = _check_author_match(cand_authors, target_authors)

                cand_year = None
                date_parts = item.get("issued", {}).get("date-parts", [[]])
                if date_parts and date_parts[0]:
                    cand_year = date_parts[0][0]

                if sim >= 0.85 or (sim >= 0.75 and auth_match):
                    doi = item.get("DOI")
                    doi_url = f"https://doi.org/{doi}" if doi else None
                    return {
                        "source": "crossref",
                        "openalex_id": None,
                        "doi": doi_url,
                        "matched_title": cand_title,
                        "matched_authors": ", ".join(cand_authors[:5]),
                        "matched_year": cand_year,
                        "cited_by_count": item.get("is-referenced-by-count", 0),
                        "similarity": round(sim, 3),
                        "author_matched": auth_match
                    }
            return None
    except Exception:
        return None


def verify_single_entry(raw_text: str, author_keys: List[str], year: Optional[str]) -> Dict[str, Any]:
    """
    Memverifikasi satu entri rujukan daftar pustaka secara komprehensif.
    """
    extracted_title = extract_publication_title(raw_text, author_keys, year)
    sanitized_title = _sanitize_title_for_query(extracted_title)

    # Buat hash query untuk caching
    hash_seed = f"{sanitized_title.lower()}_{'_'.join(sorted(author_keys or []))}_{year or ''}"
    q_hash = hashlib.sha256(hash_seed.encode("utf-8")).hexdigest()[:32]

    # 1. Cek basis data cache
    cached = get_cached_verification(q_hash)
    if cached:
        return {
            "query_hash": q_hash,
            "raw_text": raw_text,
            "extracted_title": extracted_title,
            "status": cached["status"],
            "confidence_score": float(cached["confidence_score"]),
            "badge_label": _get_badge_label(cached["status"]),
            "openalex_id": cached.get("openalex_id"),
            "doi": cached.get("doi"),
            "matched_title": cached.get("matched_title"),
            "matched_authors": cached.get("matched_authors"),
            "matched_year": cached.get("matched_year"),
            "cited_by_count": cached.get("cited_by_count", 0),
            "cached": True
        }

    # Jika judul terlalu pendek atau tidak bermakna (misal cuma URL atau angka acak)
    title_words = sanitized_title.split()
    if len(title_words) < 2 or len(sanitized_title) < 8:
        res = {
            "query_hash": q_hash,
            "raw_text": raw_text,
            "extracted_title": extracted_title,
            "status": "unverified",
            "confidence_score": 0.3,
            "badge_label": "Format Non-Standar / Tidak Dapat Diekstrak",
            "cached": False
        }
        return res

    # 2. Query OpenAlex API
    match = query_openalex(sanitized_title, author_keys, year)

    # 3. Fallback Crossref jika belum ketemu
    if not match:
        match = query_crossref(sanitized_title, author_keys, year)

    # 4. Tentukan Keputusan Verifikasi
    if match:
        status = "verified"
        confidence = float(match.get("similarity", 0.9))
        openalex_id = match.get("openalex_id")
        doi = match.get("doi")
        matched_title = match.get("matched_title")
        matched_authors = match.get("matched_authors")
        matched_year = match.get("matched_year")
        cited_by_count = int(match.get("cited_by_count") or 0)
    else:
        # Jika judul cukup spesifik (>= 4 kata) dan dicari di >250 juta paper tidak ada hasil sama sekali
        if len(title_words) >= 4:
            status = "suspected_fake"
            confidence = 0.88 if len(title_words) >= 6 else 0.75
        else:
            status = "unverified"
            confidence = 0.50
        openalex_id = None
        doi = None
        matched_title = None
        matched_authors = None
        matched_year = None
        cited_by_count = 0

    # Simpan ke cache
    save_cached_verification(
        query_hash=q_hash,
        raw_title=extracted_title,
        author_key=",".join(author_keys) if author_keys else None,
        year=year,
        status=status,
        confidence_score=confidence,
        openalex_id=openalex_id,
        doi=doi,
        matched_title=matched_title,
        matched_authors=matched_authors,
        matched_year=matched_year,
        cited_by_count=cited_by_count
    )

    return {
        "query_hash": q_hash,
        "raw_text": raw_text,
        "extracted_title": extracted_title,
        "status": status,
        "confidence_score": confidence,
        "badge_label": _get_badge_label(status),
        "openalex_id": openalex_id,
        "doi": doi,
        "matched_title": matched_title,
        "matched_authors": matched_authors,
        "matched_year": matched_year,
        "cited_by_count": cited_by_count,
        "cached": False
    }


def _get_badge_label(status: str) -> str:
    """Mengembalikan label status bahasa Indonesia yang ramah pembaca."""
    if status == "verified":
        return "Terverifikasi Sahih (Indeks Global)"
    elif status == "suspected_fake":
        return "Terindikasi Fiktif (Halusinasi AI)"
    elif status == "unverified":
        return "Belum Terindeks / Perlu Penelusuran"
    return "Status Tidak Diketahui"


def verify_bibliography_list(entries: List[Any], max_items: int = 15) -> Dict[str, Any]:
    """
    Memverifikasi daftar entri bibliografi secara konkuren (multithreaded).
    Dibatasi hingga max_items (default 15) untuk menjaga SLA waktu respons.
    """
    if not entries:
        return {
            "enabled": True,
            "total_checked": 0,
            "verified_count": 0,
            "suspected_fake_count": 0,
            "unverified_count": 0,
            "verification_rate": 100.0,
            "items": []
        }

    # Ambil maksimal entri untuk audit eksternal
    target_entries = entries[:max_items]
    verified_results = []

    # Jalankan dengan ThreadPoolExecutor
    with ThreadPoolExecutor(max_workers=MAX_CONCURRENT_REQUESTS) as executor:
        future_map = {}
        for idx, entry in enumerate(target_entries):
            # Mendukung format objek BibliographyEntry atau dict
            if hasattr(entry, "raw_text"):
                raw = entry.raw_text
                authors = getattr(entry, "author_keys", [])
                yr = getattr(entry, "year", None)
            elif isinstance(entry, dict):
                raw = entry.get("raw_text") or entry.get("text", "")
                authors = entry.get("author_keys", [])
                yr = entry.get("year")
            else:
                raw = str(entry)
                authors = []
                yr = None

            f = executor.submit(verify_single_entry, raw, authors, yr)
            future_map[f] = idx

        results_by_idx = {}
        for future in as_completed(future_map):
            idx = future_map[future]
            try:
                res = future.result()
                results_by_idx[idx] = res
            except Exception as e:
                results_by_idx[idx] = {
                    "raw_text": "",
                    "extracted_title": "",
                    "status": "unverified",
                    "confidence_score": 0.0,
                    "badge_label": f"Gagal Verifikasi: {e}",
                    "cached": False
                }

    for i in range(len(target_entries)):
        if i in results_by_idx:
            verified_results.append(results_by_idx[i])

    v_count = sum(1 for r in verified_results if r.get("status") == "verified")
    f_count = sum(1 for r in verified_results if r.get("status") == "suspected_fake")
    u_count = sum(1 for r in verified_results if r.get("status") == "unverified")
    total = len(verified_results)

    # Tingkat keaslian pustaka (Veracity Rate)
    # Jika ada sitasi terindikasi fiktif, veracity rate turun drastis
    veracity_rate = 100.0
    if total > 0:
        veracity_rate = round((v_count / total) * 100.0, 1)

    return {
        "enabled": True,
        "total_checked": total,
        "verified_count": v_count,
        "suspected_fake_count": f_count,
        "unverified_count": u_count,
        "veracity_rate": veracity_rate,
        "has_suspected_fakes": f_count > 0,
        "items": verified_results
    }


# ==============================================================================
# Alat Publik /cek-sitasi — Verifikasi Daftar Pustaka Bebas Skor (v1)
# Menggunakan ulang utilitas ekstraksi, sanitasi, & Polite Pool Crossref/OpenAlex
# ==============================================================================

PUBLIC_API_TIMEOUT = 4.5
_OPENALEX_PACE_LOCK = threading.Lock()
_OPENALEX_LAST_CALL = 0.0
_OPENALEX_MIN_INTERVAL = 0.90
PUBLIC_UNVERIFIED_REASON = (
    "Tidak ditemukan di Crossref dan OpenAlex. Bisa berarti sitasi fabrikasi, "
    "salah ketik, atau sumber tidak terindeks — periksa manual sebelum menyimpulkan."
)
PUBLIC_UNAVAILABLE_REASON = (
    "Layanan Crossref atau OpenAlex sedang lambat atau tidak dapat dihubungi saat ini. "
    "Silakan ulangi pemeriksaan beberapa saat lagi."
)

_RE_PUBLIC_DOI = re.compile(
    r'(?:https?://(?:dx\.)?doi\.org/|doi:\s*)?\b(10\.\d{4,9}/[-._;()/:A-Za-z0-9]+)',
    re.IGNORECASE,
)
_RE_BIB_HEADING_LINE = re.compile(
    r'^(?:[0-9.\s]*\b(?:daftar\s+pustaka|references?|bibliography|bibliografi|'
    r'daftar\s+referensi|kepustakaan|rujukan|literature\s+cited|works\s+cited)\b)\s*:?\s*$',
    re.IGNORECASE,
)


def _fold_text(text: str) -> str:
    """Normalisasi diakritik Unicode & huruf kecil untuk perbandingan lintas aksen."""
    if not text:
        return ""
    nfkd = unicodedata.normalize("NFKD", text)
    stripped = "".join(c for c in nfkd if not unicodedata.combining(c))
    return re.sub(r'\s+', ' ', stripped).strip().lower()


def _normalize_author_surname(name: str) -> str:
    """Normalisasi nama penulis (berbagi pola dengan citation_auditor)."""
    try:
        from worker.citation_auditor import normalize_author_name
        return normalize_author_name(name)
    except Exception:
        cleaned = re.sub(r'[^a-zA-Z\s]', ' ', _fold_text(name)).strip()
        toks = cleaned.split()
        return toks[-1] if toks else ""


def _extract_doi_from_entry(raw_entry: str) -> Optional[str]:
    if not raw_entry:
        return None
    m = _RE_PUBLIC_DOI.search(raw_entry)
    if not m:
        return None
    doi = m.group(1).strip().rstrip('.,;)]"\'')
    if '/' not in doi or len(doi) < 7:
        return None
    return doi


def _strip_urls_and_doi(text: str) -> str:
    cleaned = _RE_PUBLIC_DOI.sub(' ', text or '')
    cleaned = re.sub(r'https?://\S+|www\.\S+', ' ', cleaned, flags=re.IGNORECASE)
    return re.sub(r'\s+', ' ', cleaned).strip()


def _extract_public_entry_metadata(raw_entry: str) -> Dict[str, Any]:
    """Mengekstrak DOI, tahun, penulis, dan judul dari satu entri daftar pustaka."""
    raw = re.sub(r'\s+', ' ', raw_entry).strip()
    doi = _extract_doi_from_entry(raw)
    text_no_url = _strip_urls_and_doi(raw)

    # Prioritaskan tahun dalam tanda kurung (2021) sebelum tahun bebas agar nomor volume tidak tertukar
    y_paren = re.search(r'\(((?:19|20)\d{2})[a-z]?\)', text_no_url)
    y_bare = re.search(r'\b((?:19|20)\d{2})[a-z]?\b', text_no_url)
    year_match = y_paren or y_bare
    year = year_match.group(1) if year_match else None

    # Ekstrak kandidat nama belakang penulis dari prefiks sebelum tahun / judul
    author_keys: List[str] = []
    body_without_num = re.sub(r'^(?:\[\d+\]|\(\d+\)|\d+[\.\)])\s*', '', text_no_url).strip()

    # Prefiks penulis sebelum tahun jika format Author-Year
    author_prefix = ""
    if y_paren and y_paren.start() > 0:
        author_prefix = text_no_url[:y_paren.start()]
        author_prefix = re.sub(r'^(?:\[\d+\]|\(\d+\)|\d+[\.\)])\s*', '', author_prefix).strip()
    else:
        # Format IEEE/numbered: ambil blok sebelum tanda kutip judul atau sebelum koma/titik kedua
        q_pos = re.search(r'[\"“]', body_without_num)
        if q_pos and q_pos.start() > 2:
            author_prefix = body_without_num[:q_pos.start()]
        elif ',' in body_without_num:
            author_prefix = body_without_num.split('.', 1)[0][:100]

    stop_tokens = {
        "dkk", "dan", "and", "al", "et", "vol", "no", "pp", "hal", "hlm",
        "jurnal", "journal", "prosiding", "proceedings", "in", "dalam", "the",
        "of", "on", "for", "with", "ed", "eds", "doi", "http", "https", "retrieved",
        "diakses", "available", "press", "university", "universitas", "ieee", "acm",
    }
    if author_prefix:
        folded_pref = _fold_text(author_prefix)
        # Tangkap token nama (>= 3 huruf alfabet agar inisial tunggal A., B. tidak menyaring salah)
        for tok in re.findall(r'[a-z]{3,}', folded_pref):
            norm = _normalize_author_surname(tok)
            if norm and len(norm) >= 3 and norm not in stop_tokens and norm not in author_keys:
                author_keys.append(norm)
        author_keys = author_keys[:6]

    # Ekstrak judul dengan extract_publication_title lalu sempurnakan bila IEEE tanpa kutip / bare title
    extracted_title = extract_publication_title(text_no_url, author_keys, year)
    if (not extracted_title or len(extracted_title.split()) < 2) and text_no_url:
        extracted_title = body_without_num.split('. ')[0].strip(' .,;:')

    # Bersihkan sisa prefiks nomor IEEE jika terbawa di extracted_title
    extracted_title = re.sub(r'^(?:\[\d+\]|\(\d+\)|\d+[\.\)])\s*', '', extracted_title).strip(' .,;:')

    # Jika pengguna menempel entri format Author, I. Year. Title. tanpa tanda kurung tahun
    if not y_paren and y_bare and not re.search(r'[\"“]', text_no_url):
        after_bare_y = text_no_url[y_bare.end():].strip(' .:,;')
        if after_bare_y:
            cand_part = re.split(r'\.\s+|\?\s+|\!\s+', after_bare_y)[0].strip(' .,;:')
            if len(cand_part.split()) >= 2:
                extracted_title = cand_part

    return {
        "raw_text": raw,
        "doi": doi,
        "year": year,
        "author_keys": author_keys,
        "extracted_title": extracted_title,
    }


def _looks_like_new_public_entry_start(line: str, buf: List[str]) -> bool:
    """Menentukan apakah baris berikutnya adalah awal entri sitasi baru saat mem-parse teks."""
    if not buf:
        return True

    stripped = line.strip()
    buf_text = " ".join(buf).strip()

    # 1. Penomoran jelas [1], (1), 1., 1)
    if re.match(r'^(?:\[\d+\]|\(\d+\)|\d{1,3}[\.\)])\s+\S', stripped):
        return True

    # 2. Baris lanjutan jelas (diawali huruf kecil, URL/DOI terbungkus PDF, kata penghubung, metadata vol/hal)
    is_cont = bool(
        (stripped and stripped[0].islower())
        or re.match(
            r'^(?:https?://|www\.|doi:|available\b|retrieved\b|diakses\b|in\s|dalam\s|'
            r'(?:jurnal|journal|proceedings|prosiding|transactions|conference|ieee|acm|springer|'
            r'elsevier|wiley|sage|taylor|vol\.|volume|no\.|issue|pp\.|hal\.|hlm\.|ed\.|eds\.|'
            r'isbn|issn|penerbit|press)\b|[\d\s\-–,;:\.\(\)]+$)',
            stripped,
            re.IGNORECASE,
        )
    )
    buf_has_doi = bool(_extract_doi_from_entry(buf_text))
    buf_has_year = bool(re.search(r'\b(?:19|20)\d{2}\b', buf_text))
    buf_looks_doi_only = bool(re.match(r'^(?:https?://(?:dx\.)?doi\.org/|doi:\s*|10\.\d{4,9}/)', buf_text, re.IGNORECASE)) and len(buf_text.split()) <= 2

    if re.match(r'^10\.\d{4,9}/\S+$', stripped) and (buf_has_doi or buf_looks_doi_only or (buf_has_year and len(buf_text) >= 25 and buf_text.endswith('.'))):
        return True

    if re.match(r'^(?:https?://(?:dx\.)?doi\.org/|doi:\s*|10\.\d{4,9}/)', stripped, re.IGNORECASE):
        # Jika entri sebelumnya hanya DOI polos atau sudah punya DOI sendiri, baris ini entri baru
        return buf_has_doi or buf_looks_doi_only

    if is_cont:
        return False

    # 3. Awal Author-Year atau "Nama, A." atau judul bertanda kutip baru bila buffer sebelumnya cukup utuh
    starts_author_year = bool(
        re.match(r'^[A-ZÀ-ÖØ-Ý][^\n]{1,65}\((?:(?:19|20)\d{2}[a-z]?|n\.d\.|t\.th\.)\)', stripped)
        or re.match(r'^[A-ZÀ-ÖØ-Ý][a-zA-Z\-\']+\s*,\s*[A-Z]\.', stripped)
    )
    if starts_author_year and (buf_has_year or buf_has_doi or len(buf_text) >= 25):
        return True

    # 4. Jika buffer sebelumnya sudah tampak lengkap (bertahun/DOI dan diakhiri tanda baca/angka halaman)
    buf_ends_complete = bool(re.search(r'(?:\.|\d|https?://\S+|10\.\d{4,9}/\S+)$', buf_text))
    next_has_bib_signal = bool(
        re.search(r'\b(?:19|20)\d{2}\b|10\.\d{4,9}/|[\"“]', stripped)
    )
    if (buf_has_year or buf_has_doi) and buf_ends_complete and next_has_bib_signal:
        return True

    return False


def parse_public_bibliography_input(raw_text: str) -> List[Dict[str, Any]]:
    """
    Memecah tempelan daftar pustaka (per baris ataupun satu blok teks) menjadi entri terstruktur.
    Teks tidak disimpan ke disk atau basis data.
    """
    if not raw_text or not raw_text.strip():
        return []

    normalized = raw_text.replace("\r\n", "\n").replace("\r", "\n").strip()
    paragraphs = re.split(r'\n\s*\n+', normalized)

    raw_entries: List[str] = []
    for para in paragraphs:
        lines = [ln.strip() for ln in para.split("\n") if ln.strip()]
        buf: List[str] = []
        for ln in lines:
            if _RE_BIB_HEADING_LINE.match(ln):
                continue
            if buf and _looks_like_new_public_entry_start(ln, buf):
                joined = " ".join(buf).strip()
                if len(joined) >= 6:
                    raw_entries.append(joined)
                buf = [ln]
            else:
                buf.append(ln)
        if buf:
            joined = " ".join(buf).strip()
            if len(joined) >= 6:
                raw_entries.append(joined)

    return [_extract_public_entry_metadata(item) for item in raw_entries]


def _get_public_cached(query_hash: str) -> Optional[Dict[str, Any]]:
    conn = _get_cache_connection()
    if not conn:
        return None
    try:
        cur = conn.cursor(dictionary=True)
        cur.execute(
            """SELECT status, source, matched_title, matched_authors, matched_year,
                      doi, openalex_id, source_url, reason
               FROM public_citation_cache
               WHERE query_hash = %s AND expires_at > NOW()
               LIMIT 1""",
            (query_hash,),
        )
        row = cur.fetchone()
        cur.close()
        return row
    except Exception:
        return None
    finally:
        try:
            conn.close()
        except Exception:
            pass


def _save_public_cached(query_hash: str, result: Dict[str, Any]) -> None:
    status = result.get("status")
    if status not in ("verified", "doubtful", "unverified"):
        return
    conn = _get_cache_connection()
    if not conn:
        return
    try:
        cur = conn.cursor()
        ttl_days = 14 if status in ("verified", "doubtful") else 1
        expires_at = time.strftime("%Y-%m-%d %H:%M:%S", time.localtime(time.time() + ttl_days * 86400))
        now = time.strftime("%Y-%m-%d %H:%M:%S")
        # Privasi: untuk entri tak terverifikasi jangan simpan judul/teks pengguna, cukup hash + status
        is_public_meta = status in ("verified", "doubtful")
        cur.execute(
            """INSERT INTO public_citation_cache
               (query_hash, status, source, matched_title, matched_authors, matched_year,
                doi, openalex_id, source_url, reason, expires_at, created_at, updated_at)
               VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)
               ON DUPLICATE KEY UPDATE
               status = VALUES(status), source = VALUES(source),
               matched_title = VALUES(matched_title), matched_authors = VALUES(matched_authors),
               matched_year = VALUES(matched_year), doi = VALUES(doi),
               openalex_id = VALUES(openalex_id), source_url = VALUES(source_url),
               reason = VALUES(reason), expires_at = VALUES(expires_at),
               updated_at = VALUES(updated_at)""",
            (
                query_hash,
                status,
                result.get("source"),
                (result.get("matched_title") or "")[:490] if is_public_meta and result.get("matched_title") else None,
                result.get("matched_authors") if is_public_meta else None,
                result.get("matched_year") if is_public_meta else None,
                (result.get("doi") or "")[:250] if is_public_meta and result.get("doi") else None,
                (result.get("openalex_id") or "")[:95] if is_public_meta and result.get("openalex_id") else None,
                (result.get("source_url") or "")[:490] if is_public_meta and result.get("source_url") else None,
                (result.get("reason") or "")[:490] if is_public_meta and result.get("reason") else None,
                expires_at,
                now,
                now,
            ),
        )
        conn.commit()
        cur.close()
    except Exception:
        pass
    finally:
        try:
            conn.close()
        except Exception:
            pass


def _title_similarity(target_title: str, cand_title: str) -> float:
    t1 = _fold_text(re.sub(r'[^a-zA-Z0-9\s]', ' ', target_title or ''))
    t2 = _fold_text(re.sub(r'[^a-zA-Z0-9\s]', ' ', cand_title or ''))
    if not t1 or not t2:
        return 0.0
    sim = difflib.SequenceMatcher(None, t1, t2).ratio()
    # Tangani judul dengan/tanpa subjudul (dipisah ':')
    if ':' in (cand_title or '') and ':' not in (target_title or ''):
        cand_main = _fold_text(re.sub(r'[^a-zA-Z0-9\s]', ' ', cand_title.split(':', 1)[0]))
        if len(cand_main.split()) >= 3:
            sim = max(sim, difflib.SequenceMatcher(None, t1, cand_main).ratio())
    return sim


def _parse_crossref_item(item: Dict[str, Any]) -> Dict[str, Any]:
    titles = item.get("title") or []
    cand_title = titles[0] if titles else ""
    cand_authors = [
        f"{a.get('given', '')} {a.get('family', '')}".strip() or (a.get("name") or "")
        for a in (item.get("author") or [])
    ]
    cand_authors = [a for a in cand_authors if a]
    cand_year = None
    for date_field in ("issued", "published-print", "published-online", "created"):
        parts = (item.get(date_field) or {}).get("date-parts", [[]])
        if parts and parts[0] and parts[0][0]:
            try:
                cand_year = int(parts[0][0])
                break
            except Exception:
                pass
    doi_raw = (item.get("DOI") or "").strip()
    doi_url = f"https://doi.org/{doi_raw}" if doi_raw else None
    source_url = doi_url or (item.get("URL") or None)
    return {
        "source": "crossref",
        "openalex_id": None,
        "doi": doi_url,
        "source_url": source_url,
        "matched_title": cand_title or None,
        "matched_authors": ", ".join(cand_authors[:5]) if cand_authors else None,
        "matched_authors_list": cand_authors,
        "matched_year": cand_year,
    }


def _parse_openalex_item(item: Dict[str, Any]) -> Dict[str, Any]:
    cand_authors = [
        (a.get("author") or {}).get("display_name", "")
        for a in (item.get("authorships") or [])
    ]
    cand_authors = [a for a in cand_authors if a]
    oa_id = item.get("id")
    doi_val = item.get("doi")
    source_url = doi_val or oa_id
    cand_year = item.get("publication_year")
    try:
        cand_year = int(cand_year) if cand_year is not None else None
    except Exception:
        cand_year = None
    return {
        "source": "openalex",
        "openalex_id": oa_id,
        "doi": doi_val,
        "source_url": source_url,
        "matched_title": item.get("title"),
        "matched_authors": ", ".join(cand_authors[:5]) if cand_authors else None,
        "matched_authors_list": cand_authors,
        "matched_year": cand_year,
    }


_DEFAULT_SSL_CONTEXT = ssl.create_default_context()


class _IPv4FirstHTTPSHandler(urllib.request.HTTPSHandler):
    """Membuka koneksi HTTPS dengan prioritas IPv4 agar tidak tersendat rute IPv6 yang terputus."""

    class _Conn(http.client.HTTPSConnection):
        def connect(self):
            host = self.host
            port = self.port
            last_err = None
            infos = []
            for attempt in range(2):
                try:
                    infos = socket.getaddrinfo(host, port, socket.AF_INET, socket.SOCK_STREAM)
                    if infos:
                        break
                except socket.gaierror as gerr:
                    last_err = gerr
                    if attempt == 0:
                        time.sleep(0.2)
            if not infos:
                try:
                    infos = socket.getaddrinfo(host, port, socket.AF_UNSPEC, socket.SOCK_STREAM)
                except socket.gaierror as gerr:
                    raise last_err or gerr

            read_timeout = self.timeout if self.timeout is not socket._GLOBAL_DEFAULT_TIMEOUT else PUBLIC_API_TIMEOUT
            conn_timeout = min(float(read_timeout), 2.0)
            connected_sock = None
            for family, socktype, proto, _, sa in infos[:2]:
                sock = None
                try:
                    sock = socket.socket(family, socktype, proto)
                    sock.settimeout(conn_timeout)
                    if self.source_address:
                        sock.bind(self.source_address)
                    sock.connect(sa)
                    sock.settimeout(read_timeout)
                    connected_sock = sock
                    break
                except OSError as exc:
                    last_err = exc
                    if sock is not None:
                        try:
                            sock.close()
                        except Exception:
                            pass
            if connected_sock is None:
                raise last_err or OSError(f"Tidak dapat terhubung ke {host}:{port}")
            self.sock = self._context.wrap_socket(connected_sock, server_hostname=self._tunnel_host or host)

    def https_open(self, req):
        return self.do_open(lambda host, **kw: self._Conn(host, context=_DEFAULT_SSL_CONTEXT, **kw), req)


_PUBLIC_OPENER = urllib.request.build_opener(_IPv4FirstHTTPSHandler())


def _http_get_json_ipv4_first(url: str, headers: Dict[str, str], timeout: float) -> Tuple[Optional[Dict[str, Any]], int]:
    req = urllib.request.Request(url, headers=headers)
    try:
        with _PUBLIC_OPENER.open(req, timeout=timeout) as resp:
            return json.loads(resp.read().decode("utf-8")), int(resp.status)
    except urllib.error.HTTPError as he:
        return None, int(he.code)
    except Exception:
        return None, 0


def query_crossref_by_doi(doi: str, timeout: float = PUBLIC_API_TIMEOUT) -> Tuple[Optional[Dict[str, Any]], bool]:
    """Mengambil metadata DOI langsung dari Crossref. Return (candidate, had_network_or_server_error)."""
    mailto = _get_mailto()
    encoded_doi = urllib.parse.quote(doi.strip(), safe="")
    url = f"{CROSSREF_BASE_URL}/{encoded_doi}?mailto={urllib.parse.quote(mailto)}"
    headers = {"User-Agent": f"ARTilik-CitationChecker/1.0 (https://tilik.arsetontong.top; mailto:{mailto})"}
    data, code = _http_get_json_ipv4_first(url, headers, timeout)
    if data is not None:
        item = data.get("message")
        if isinstance(item, dict) and item:
            return _parse_crossref_item(item), False
        return None, False
    if code == 404:
        return None, False
    return None, True


def query_openalex_by_doi(doi: str, timeout: float = PUBLIC_API_TIMEOUT) -> Tuple[Optional[Dict[str, Any]], bool]:
    """Fallback pencarian DOI langsung ke OpenAlex bila Crossref DOI lambat/terganggu."""
    mailto = _get_mailto()
    api_key = os.environ.get("OPENALEX_API_KEY", "").strip()
    key_suffix = f"&api_key={urllib.parse.quote(api_key)}" if api_key else ""
    encoded_doi = urllib.parse.quote(f"https://doi.org/{doi.strip()}", safe=":/")
    url = f"{OPENALEX_BASE_URL}/{encoded_doi}?mailto={urllib.parse.quote(mailto)}{key_suffix}"
    data, code = _openalex_fetch_json(url, mailto, timeout)
    if data is not None and isinstance(data, dict) and data.get("id"):
        return _parse_openalex_item(data), False
    if code == 404:
        return None, False
    return None, True


def query_public_crossref_candidates(
    clean_title: str,
    raw_entry: str,
    timeout: float = PUBLIC_API_TIMEOUT,
) -> Tuple[List[Dict[str, Any]], bool]:
    """Mencari kandidat publikasi di Crossref berdasarkan judul atau kueri bibliografis."""
    mailto = _get_mailto()
    headers = {"User-Agent": f"ARTilik-CitationChecker/1.0 (https://tilik.arsetontong.top; mailto:{mailto})"}
    q_val = clean_title if len(clean_title.split()) >= 2 else _sanitize_title_for_query(raw_entry)[:180]
    if not q_val:
        return [], False
    param_key = "query.title" if len(clean_title.split()) >= 2 else "query.bibliographic"
    url = f"{CROSSREF_BASE_URL}?{param_key}={urllib.parse.quote(q_val)}&rows=8&mailto={urllib.parse.quote(mailto)}"
    data, _ = _http_get_json_ipv4_first(url, headers, timeout)
    if data is not None:
        items = (data.get("message") or {}).get("items") or []
        return [_parse_crossref_item(it) for it in items if isinstance(it, dict)], False
    return [], True


def _openalex_fetch_json(url: str, mailto: str, timeout: float) -> Tuple[Optional[Dict[str, Any]], int]:
    """Memanggil OpenAlex secara terukur dengan prioritas IPv4 agar tidak memicu burst HTTP 429."""
    global _OPENALEX_LAST_CALL
    with _OPENALEX_PACE_LOCK:
        elapsed = time.monotonic() - _OPENALEX_LAST_CALL
        if elapsed < _OPENALEX_MIN_INTERVAL:
            time.sleep(_OPENALEX_MIN_INTERVAL - elapsed)
        _OPENALEX_LAST_CALL = time.monotonic()

    headers = {"User-Agent": f"ARTilik-CitationChecker/1.0 (https://tilik.arsetontong.top; mailto:{mailto})"}
    return _http_get_json_ipv4_first(url, headers, timeout)


def query_public_openalex_candidates(
    clean_title: str,
    timeout: float = PUBLIC_API_TIMEOUT,
) -> Tuple[List[Dict[str, Any]], bool]:
    """Mencari kandidat publikasi di OpenAlex berdasarkan judul (termasuk fallback query search saat filter dibatasi)."""
    if len(clean_title.split()) < 2:
        return [], False
    mailto = _get_mailto()
    api_key = os.environ.get("OPENALEX_API_KEY", "").strip()
    key_suffix = f"&api_key={urllib.parse.quote(api_key)}" if api_key else ""
    encoded_q = urllib.parse.quote(clean_title)

    urls = [
        f"{OPENALEX_BASE_URL}?filter=title.search:{encoded_q}&per-page=5&mailto={urllib.parse.quote(mailto)}{key_suffix}",
        f"{OPENALEX_BASE_URL}?search={encoded_q}&per-page=5&mailto={urllib.parse.quote(mailto)}{key_suffix}",
    ]
    last_code = 0
    for idx, url in enumerate(urls):
        data, code = _openalex_fetch_json(url, mailto, timeout)
        if data is not None:
            items = data.get("results") or []
            return [_parse_openalex_item(it) for it in items if isinstance(it, dict)], False
        last_code = code
        if idx == 0 and code == 0:
            time.sleep(0.4)
            continue
        break

    return [], (last_code != 404)


def _author_matches_candidate(cand_authors: List[str], target_authors: List[str]) -> Optional[bool]:
    if not target_authors or not cand_authors:
        return None
    cand_folded = _fold_text(" ".join(cand_authors))
    for ta in target_authors:
        if len(ta) >= 3 and _fold_text(ta) in cand_folded:
            return True
    return False


def _title_content_overlap(t1: str, t2: str) -> float:
    """Mengukur tumpang tindih kata bermakna di luar kata penghubung dan awalan akademis umum."""
    generic_stops = {
        "the", "and", "for", "with", "from", "that", "into", "using", "based", "study",
        "analysis", "effect", "effects", "dan", "yang", "pada", "dari", "untuk", "dengan",
        "dalam", "atau", "terhadap", "analisis", "pengaruh", "penerapan", "implementasi",
        "efektivitas", "pembelajaran", "berbasis", "sekolah", "menengah", "pertama",
        "atas", "dasar", "metode", "sistem", "model", "studi", "kasus",
    }
    w1 = {w for w in _fold_text(re.sub(r'[^a-zA-Z0-9\s]', ' ', t1)).split() if len(w) >= 3 and w not in generic_stops}
    w2 = {w for w in _fold_text(re.sub(r'[^a-zA-Z0-9\s]', ' ', t2)).split() if len(w) >= 3 and w not in generic_stops}
    if not w1 or not w2:
        return 0.0
    return len(w1 & w2) / len(w1 | w2)


def _evaluate_candidate(
    meta: Dict[str, Any],
    cand: Dict[str, Any],
    doi_direct: bool = False,
) -> Tuple[str, float, Optional[str]]:
    """Mengklasifikasikan satu kandidat terhadap entri rujukan: verified | doubtful | none."""
    clean_title = _sanitize_title_for_query(meta.get("extracted_title") or "")
    cand_title = cand.get("matched_title") or ""
    cand_year = cand.get("matched_year")
    target_year = meta.get("year")
    target_authors = meta.get("author_keys") or []
    cand_authors_list = cand.get("matched_authors_list") or []
    src_label = "Crossref" if cand.get("source") == "crossref" else "OpenAlex"

    year_match = True
    if target_year and cand_year:
        try:
            year_match = abs(int(target_year) - int(cand_year)) <= 1
        except Exception:
            year_match = True

    auth_match = _author_matches_candidate(cand_authors_list, target_authors)
    sim = _title_similarity(clean_title, cand_title) if clean_title and cand_title else 0.0

    if doi_direct:
        has_substantive_title = len(clean_title.split()) >= 3
        if has_substantive_title and cand_title and sim < 0.55:
            return (
                "doubtful",
                sim + 0.35,
                f"DOI ditemukan di {src_label}, tetapi judul pada basis data berbeda dengan sitasi Anda — periksa kesesuaian DOI.",
            )
        if target_year and cand_year and not year_match:
            return (
                "doubtful",
                max(sim, 0.75),
                f"DOI ditemukan di {src_label}, tetapi tahun terbit berbeda (tertulis {target_year}, tercatat {cand_year}).",
            )
        return ("verified", max(sim, 0.99), None)

    if not cand_title:
        return ("none", 0.0, None)

    # Klasifikasi pencarian tanpa DOI (Judul + Penulis + Tahun)
    if sim >= 0.85 and year_match and (auth_match is True or auth_match is None or sim >= 0.93 and not target_authors):
        if auth_match is False and target_authors and cand_authors_list:
            return (
                "doubtful",
                sim - 0.05,
                f"Judul cocok di {src_label}, tetapi nama penulis berbeda dengan catatan basis data — periksa penulis atau kemungkinan sitasi keliru.",
            )
        return ("verified", sim, None)

    # Tolak kemiripan frasa umum bila penulis tidak cocok dan kata inti tidak bertumpang tindih kuat
    content_overlap = _title_content_overlap(clean_title, cand_title)
    if auth_match is False and sim < 0.85 and content_overlap < 0.45:
        return ("none", sim, None)
    if auth_match is False and target_year and cand_year and not year_match and sim < 0.85:
        return ("none", sim, None)

    # Cocok sebagian -> Meragukan + tampilkan kandidat terdekat & alasan spesifik
    if sim >= 0.74 or (sim >= 0.58 and auth_match is True and year_match):
        if target_year and cand_year and not year_match and (auth_match is not False):
            reason = f"Judul mirip di {src_label}, tetapi tahun terbit berbeda (tertulis {target_year}, tercatat {cand_year})."
        elif auth_match is False:
            reason = f"Judul mirip di {src_label}, tetapi nama penulis pada basis data berbeda."
        else:
            reason = f"Kecocokan judul parsial ditemukan di {src_label} — periksa detail judul, tahun, dan penulis."
        score = sim + (0.08 if auth_match is True else 0.0) + (0.05 if year_match and target_year and cand_year else 0.0)
        return ("doubtful", score, reason)

    return ("none", sim, None)


def _format_public_result(
    index: int,
    raw_text: str,
    status: str,
    source: Optional[str] = None,
    cand: Optional[Dict[str, Any]] = None,
    reason: Optional[str] = None,
    cached: bool = False,
) -> Dict[str, Any]:
    label_map = {
        "verified": "Terverifikasi",
        "doubtful": "Meragukan",
        "unverified": "Tidak terverifikasi",
        "unavailable": "Tidak dapat diperiksa saat ini",
    }
    if status == "unverified":
        reason = reason or PUBLIC_UNVERIFIED_REASON
    elif status == "unavailable":
        reason = reason or PUBLIC_UNAVAILABLE_REASON

    return {
        "index": index,
        "raw_text": raw_text,
        "status": status,
        "status_label": label_map.get(status, "Tidak terverifikasi"),
        "source": source,
        "matched_title": cand.get("matched_title") if cand else None,
        "matched_authors": cand.get("matched_authors") if cand else None,
        "matched_year": cand.get("matched_year") if cand else None,
        "doi": cand.get("doi") if cand else None,
        "openalex_id": cand.get("openalex_id") if cand else None,
        "source_url": cand.get("source_url") if cand else None,
        "reason": reason,
        "cached": cached,
    }


def verify_public_single_entry(index: int, meta: Dict[str, Any]) -> Dict[str, Any]:
    """Memverifikasi satu entri sitasi publik dengan cache hash, DOI Crossref, lalu Crossref -> OpenAlex."""
    raw_text = meta.get("raw_text") or ""
    norm_key = "v1:" + re.sub(r'\s+', ' ', _fold_text(raw_text))
    q_hash = hashlib.sha256(norm_key.encode("utf-8")).hexdigest()

    cached = _get_public_cached(q_hash)
    if cached:
        return _format_public_result(
            index=index,
            raw_text=raw_text,
            status=cached["status"],
            source=cached.get("source"),
            cand=cached,
            reason=cached.get("reason"),
            cached=True,
        )

    doi = meta.get("doi")
    clean_title = _sanitize_title_for_query(meta.get("extracted_title") or "")
    had_error = False

    # 1. Jika ada DOI -> cek langsung ke Crossref, fallback cepat ke OpenAlex DOI bila perlu
    if doi:
        doi_cand, doi_err = query_crossref_by_doi(doi)
        if not doi_cand:
            oa_doi_cand, oa_doi_err = query_openalex_by_doi(doi)
            if oa_doi_cand:
                doi_cand = oa_doi_cand
                doi_err = False
            else:
                doi_err = doi_err or oa_doi_err
        had_error = had_error or doi_err
        if doi_cand:
            verdict, _, reason = _evaluate_candidate(meta, doi_cand, doi_direct=True)
            res = _format_public_result(index, raw_text, verdict, source=doi_cand.get("source", "crossref"), cand=doi_cand, reason=reason)
            _save_public_cached(q_hash, res)
            return res

        # Jika DOI 404 (tidak ditemukan) dan ada judul memadai, cek kandidat berbasis judul -> Meragukan jika ada
        if not doi_err and len(clean_title.split()) >= 3:
            cr_cands, cr_err = query_public_crossref_candidates(clean_title, raw_text)
            had_error = had_error or cr_err
            cands = list(cr_cands)
            if not cands:
                oa_cands, oa_err = query_public_openalex_candidates(clean_title)
                had_error = had_error or oa_err
                cands.extend(oa_cands)
            best_cand = None
            best_score = 0.0
            for c in cands:
                v, sc, _ = _evaluate_candidate(meta, c, doi_direct=False)
                if v in ("verified", "doubtful") and sc > best_score:
                    best_cand = c
                    best_score = sc
            if best_cand:
                src_name = "Crossref" if best_cand.get("source") == "crossref" else "OpenAlex"
                res = _format_public_result(
                    index,
                    raw_text,
                    "doubtful",
                    source=best_cand.get("source"),
                    cand=best_cand,
                    reason=f"DOI tidak ditemukan di Crossref, namun karya berjudul serupa ditemukan di {src_name} — periksa kembali penulisan DOI.",
                )
                _save_public_cached(q_hash, res)
                return res

        if doi_err:
            # Crossref DOI gagal/timeout; jika punya judul coba OpenAlex sebelum menyatakan unavailable
            if len(clean_title.split()) >= 3:
                oa_cands, oa_err = query_public_openalex_candidates(clean_title)
                had_error = had_error or oa_err
                for c in oa_cands:
                    v, _, r = _evaluate_candidate(meta, c, doi_direct=False)
                    if v in ("verified", "doubtful"):
                        res = _format_public_result(index, raw_text, v, source="openalex", cand=c, reason=r)
                        _save_public_cached(q_hash, res)
                        return res
            return _format_public_result(index, raw_text, "unavailable")

        res = _format_public_result(index, raw_text, "unverified")
        _save_public_cached(q_hash, res)
        return res

    # 2. Tanpa DOI: entri terlalu pendek / tidak dapat diekstrak
    if len(clean_title.split()) < 2 or len(clean_title) < 6:
        res = _format_public_result(index, raw_text, "unverified")
        return res

    # 3. Cari berdasarkan judul + penulis + tahun ke Crossref dahulu, lalu OpenAlex bila Crossref kosong
    best_verified: Optional[Tuple[float, Dict[str, Any], Optional[str]]] = None
    best_doubtful: Optional[Tuple[float, Dict[str, Any], Optional[str]]] = None

    cr_cands, cr_err = query_public_crossref_candidates(clean_title, raw_text)
    had_error = had_error or cr_err
    for c in cr_cands:
        v, sc, r = _evaluate_candidate(meta, c, doi_direct=False)
        if v == "verified" and (best_verified is None or sc > best_verified[0]):
            best_verified = (sc, c, r)
        elif v == "doubtful" and (best_doubtful is None or sc > best_doubtful[0]):
            best_doubtful = (sc, c, r)

    if best_verified:
        _, cand, reason = best_verified
        res = _format_public_result(index, raw_text, "verified", source=cand.get("source"), cand=cand, reason=reason)
        _save_public_cached(q_hash, res)
        return res

    # Hanya panggil OpenAlex bila Crossref belum menghasilkan Terverifikasi
    oa_cands, oa_err = query_public_openalex_candidates(clean_title)
    had_error = had_error or oa_err
    for c in oa_cands:
        v, sc, r = _evaluate_candidate(meta, c, doi_direct=False)
        if v == "verified" and (best_verified is None or sc > best_verified[0]):
            best_verified = (sc, c, r)
        elif v == "doubtful" and (best_doubtful is None or sc > best_doubtful[0]):
            best_doubtful = (sc, c, r)

    if best_verified:
        _, cand, reason = best_verified
        res = _format_public_result(index, raw_text, "verified", source=cand.get("source"), cand=cand, reason=reason)
        _save_public_cached(q_hash, res)
        return res

    if best_doubtful:
        _, cand, reason = best_doubtful
        res = _format_public_result(index, raw_text, "doubtful", source=cand.get("source"), cand=cand, reason=reason)
        _save_public_cached(q_hash, res)
        return res

    if had_error:
        return _format_public_result(index, raw_text, "unavailable")

    res = _format_public_result(index, raw_text, "unverified")
    _save_public_cached(q_hash, res)
    return res


def verify_public_citation_list(
    raw_text: str,
    max_items: int = 10,
    on_progress: Optional[Callable[[Dict[str, Any], int, int], None]] = None,
) -> Dict[str, Any]:
    """
    Memverifikasi hingga max_items sitasi secara paralel tanpa menyimpan teks input.
    Tidak mengembalikan skor persentase atau klaim 'palsu'.
    """
    parsed = parse_public_bibliography_input(raw_text)
    if not parsed:
        return {
            "error_code": "empty",
            "message": "Tidak ada entri sitasi yang terdeteksi. Tempel minimal satu baris rujukan atau DOI.",
            "total_entries": 0,
            "items": [],
        }

    if len(parsed) > max_items:
        return {
            "error_code": "too_many",
            "message": f"Maksimal {max_items} sitasi per pemeriksaan. Anda menempel {len(parsed)} entri — silakan kurangi menjadi maksimal {max_items} sitasi.",
            "total_entries": len(parsed),
            "max_entries": max_items,
            "items": [],
        }

    total = len(parsed)
    results_by_idx: Dict[int, Dict[str, Any]] = {}
    completed = 0

    with ThreadPoolExecutor(max_workers=min(4, total)) as executor:
        future_map = {
            executor.submit(verify_public_single_entry, idx + 1, meta): idx
            for idx, meta in enumerate(parsed)
        }
        for fut in as_completed(future_map):
            idx = future_map[fut]
            try:
                item_res = fut.result()
            except Exception:
                item_res = _format_public_result(idx + 1, parsed[idx].get("raw_text") or "", "unavailable")
            results_by_idx[idx] = item_res
            completed += 1
            if on_progress is not None:
                on_progress(item_res, completed, total)

    items = [results_by_idx[i] for i in range(total)]
    verified_cnt = sum(1 for r in items if r["status"] == "verified")
    doubtful_cnt = sum(1 for r in items if r["status"] == "doubtful")
    unverified_cnt = sum(1 for r in items if r["status"] == "unverified")
    unavailable_cnt = sum(1 for r in items if r["status"] == "unavailable")
    cache_hits = sum(1 for r in items if r.get("cached"))

    return {
        "total_entries": total,
        "verified_count": verified_cnt,
        "doubtful_count": doubtful_cnt,
        "unverified_count": unverified_cnt,
        "unavailable_count": unavailable_cnt,
        "cache_hits": cache_hits,
        "summary_text": f"{verified_cnt} dari {total} sitasi terverifikasi",
        "items": items,
    }
