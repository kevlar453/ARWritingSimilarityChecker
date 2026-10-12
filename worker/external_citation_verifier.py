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
import json
import time
import hashlib
import difflib
import urllib.request
import urllib.parse
from concurrent.futures import ThreadPoolExecutor, as_completed
from typing import Dict, List, Any, Optional, Tuple


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
