"""
AR Tilik - Autonomous Corpus Discovery & Scholarly Retrieval Engine
Modul pencarian korpus mandiri yang aktif menjelajahi basis data literatur akademik
terbuka (OpenAlex, CrossRef, DOAJ) serta katalog repositori perguruan tinggi Indonesia.
"""

import os
import sys
import json
import urllib.request
import urllib.parse
import hashlib
import re
from pathlib import Path
from typing import Optional, List, Dict, Any, Tuple

# Pastikan PROJECT_ROOT berada di urutan teratas sys.path dan script_dir tidak membayangi package worker
PROJECT_ROOT = Path(__file__).resolve().parents[1]
script_dir = str(Path(__file__).resolve().parent)
if script_dir in sys.path:
    sys.path.remove(script_dir)
if str(PROJECT_ROOT) not in sys.path:
    sys.path.insert(0, str(PROJECT_ROOT))

from worker.database import get_connection, load_env
from worker.indexer import index_document, rebuild_fingerprint_stats
from worker.corpus_harvester import is_already_indexed
from worker.oai_harvester import OAIHarvester, download_file_stream

load_env()

DEFAULT_CORPUS_STORAGE = PROJECT_ROOT / "writable" / "documents" / "corpus"
OPENALEX_API_URL = "https://api.openalex.org/works"
POLITE_MAILTO = os.getenv("OPENALEX_MAILTO", "admin@tilik.arsetontong.top")
USER_AGENT = "ARTilik-AutoDiscovery/1.2 (Academic Similarity Harvester; mailto:" + POLITE_MAILTO + ")"

# Katalog Bawaan Repositori Perguruan Tinggi Terbuka di Indonesia
BUILTIN_INDONESIAN_REPOSITORIES = [
    {
        "name": "Neliti - Portal Riset & Jurnal Indonesia",
        "url": "https://www.neliti.com/oai",
        "category": "national_portal"
    },
    {
        "name": "Universitas Kristen Satya Wacana (UKSW)",
        "url": "https://repository.uksw.edu/oai/request",
        "category": "university"
    },
    {
        "name": "Universitas Sanata Dharma (USD)",
        "url": "https://repository.usd.ac.id/cgi/oai2",
        "category": "university"
    },
    {
        "name": "UIN Sunan Kalijaga Yogyakarta",
        "url": "https://digilib.uin-suka.ac.id/cgi/oai2",
        "category": "islamic_studies"
    },
    {
        "name": "UIN Syarif Hidayatullah Jakarta",
        "url": "https://repository.uinjkt.ac.id/oai/request",
        "category": "islamic_studies"
    },
    {
        "name": "Universitas Negeri Yogyakarta (UNY)",
        "url": "http://eprints.uny.ac.id/cgi/oai2",
        "category": "education"
    },
    {
        "name": "Universitas Diponegoro (UNDIP)",
        "url": "https://eprints.undip.ac.id/cgi/oai2",
        "category": "university"
    }
]


def reconstruct_abstract(inverted_index: Optional[Dict[str, List[int]]]) -> str:
    """
    Rekonstruksi paragraf abstrak penuh dari struktur inverted index OpenAlex.
    """
    if not inverted_index or not isinstance(inverted_index, dict):
        return ""
    word_positions = []
    for word, positions in inverted_index.items():
        for pos in positions:
            word_positions.append((pos, word))
    word_positions.sort(key=lambda x: x[0])
    return " ".join([w for _, w in word_positions])


def search_openalex_works(
    query: str,
    limit: int = 10,
    open_access_only: bool = True
) -> List[Dict[str, Any]]:
    """
    Mencari karya ilmiah terpublikasi via OpenAlex API yang relevan dengan topik target.
    """
    params = {
        "search": query.strip(),
        "per-page": min(max(1, limit), 50),
        "mailto": POLITE_MAILTO,
        "sort": "relevance_score:desc"
    }
    if open_access_only:
        params["filter"] = "is_oa:true"

    url = f"{OPENALEX_API_URL}?{urllib.parse.urlencode(params)}"
    req = urllib.request.Request(url, headers={"User-Agent": USER_AGENT})

    works = []
    try:
        with urllib.request.urlopen(req, timeout=20) as resp:
            data = json.loads(resp.read().decode("utf-8"))
            for item in data.get("results", []):
                title = (item.get("title") or "").strip()
                if not title:
                    continue

                # Kumpulkan penulis
                authors = []
                for auth in item.get("authorships", []):
                    name = auth.get("author", {}).get("display_name")
                    if name:
                        authors.append(name)

                # Ekstrak abstrak
                abstract = reconstruct_abstract(item.get("abstract_inverted_index"))

                # Cari tautan PDF langsung jika tersedia
                primary_loc = item.get("primary_location") or {}
                oa_info = item.get("open_access") or {}
                pdf_url = primary_loc.get("pdf_url")
                if not pdf_url and oa_info.get("oa_url", "").lower().endswith(".pdf"):
                    pdf_url = oa_info.get("oa_url")

                landing_url = primary_loc.get("landing_page_url") or oa_info.get("oa_url") or item.get("doi")

                source_name = ""
                source_obj = primary_loc.get("source") or {}
                if isinstance(source_obj, dict):
                    source_name = source_obj.get("display_name") or ""

                works.append({
                    "openalex_id": item.get("id"),
                    "title": title,
                    "authors": authors,
                    "year": item.get("publication_year"),
                    "doi": item.get("doi"),
                    "pdf_url": pdf_url,
                    "landing_url": landing_url,
                    "source_name": source_name,
                    "abstract": abstract
                })
    except Exception as err:
        print(f"[OPENALEX ERROR] Gagal melakukan pencarian: {err}")

    return works


def ingest_scholarly_work(
    connection,
    work_data: Dict[str, Any],
    corpus_dir: Path = DEFAULT_CORPUS_STORAGE
) -> Dict[str, Any]:
    """
    Mengunduh (jika PDF) atau membuat dokumen teks ilmiah berbobot,
    lalu langsung mengindeksnya ke dalam korpus AR Tilik.
    """
    title = work_data["title"]
    corpus_dir.mkdir(parents=True, exist_ok=True)
    unique_hash = hashlib.md5(f"{title}_{work_data.get('doi', '')}".encode("utf-8")).hexdigest()[:12]

    # Cek apakah judul dokumen ini sudah terindeks di korpus
    cursor = connection.cursor(dictionary=True)
    cursor.execute("SELECT id FROM documents WHERE document_type = 'corpus' AND title = %s LIMIT 1", (title,))
    exists = cursor.fetchone()
    cursor.close()
    if exists:
        return {
            "status": "skipped",
            "title": title,
            "reason": "Dokumen sudah terdaftar di korpus"
        }

    saved_path = None
    mime_type = "text/plain"
    pdf_url = work_data.get("pdf_url")

    # 1. Coba unduh berkas PDF jika ada URL PDF langsung
    if pdf_url:
        candidate_pdf = corpus_dir / f"disc_{unique_hash}.pdf"
        try:
            if download_file_stream(pdf_url, candidate_pdf, max_bytes=25 * 1024 * 1024, timeout=20):
                # Validasi magic bytes PDF
                with open(candidate_pdf, "rb") as f:
                    magic = f.read(5)
                if magic == b"%PDF-":
                    saved_path = candidate_pdf
                    mime_type = "application/pdf"
                else:
                    candidate_pdf.unlink(missing_ok=True)
        except Exception:
            candidate_pdf.unlink(missing_ok=True)

    # 2. Jika berkas PDF tidak tersedia atau gagal unduh, susun naskah teks ilmiah lengkap
    if not saved_path:
        text_filename = f"disc_{unique_hash}.txt"
        saved_path = corpus_dir / text_filename

        authors_str = ", ".join(work_data.get("authors", [])) if work_data.get("authors") else "Peneliti Institusi"
        year_str = str(work_data.get("year") or "")
        source_str = work_data.get("source_name") or "Basis Data Riset Open Access"
        ref_url = work_data.get("landing_url") or work_data.get("doi") or work_data.get("openalex_id")

        text_content = (
            f"JUDUL KARYA ILMIAH: {title}\n"
            f"PENULIS: {authors_str}\n"
            f"TAHUN PUBLIKASI: {year_str}\n"
            f"SUMBER / JURNAL: {source_str}\n"
            f"DOI / TAUTAN SUMBER: {ref_url}\n\n"
            f"RINGKASAN DAN ABSTRAK KARYA ILMIAH:\n"
            f"{work_data.get('abstract') or 'Abstrak tidak disertakan dalam metadata repositori terbuka.'}\n"
        )
        with open(saved_path, "w", encoding="utf-8") as f:
            f.write(text_content)
        mime_type = "text/plain"

    file_size = saved_path.stat().st_size
    filename = saved_path.name

    # Simpan ke tabel documents
    cursor = connection.cursor()
    cursor.execute("""
        INSERT INTO documents (
            user_id, title, original_filename, mime_type, file_size,
            storage_path, status, document_type, corpus_visibility,
            source_type, indexing_status, created_at, updated_at
        ) VALUES (
            NULL, %s, %s, %s, %s,
            %s, 'uploaded', 'corpus', 'institution',
            'journal', 'processing', NOW(), NOW()
        )
    """, (
        title,
        filename,
        mime_type,
        file_size,
        str(saved_path)
    ))
    doc_id = cursor.lastrowid
    connection.commit()
    cursor.close()

    try:
        # Ekstraksi shingle dan indeks corpus
        index_document(
            connection=connection,
            document_id=doc_id,
            storage_path=str(saved_path),
            mime_type=mime_type,
            is_corpus=True
        )

        return {
            "status": "indexed",
            "document_id": doc_id,
            "title": title,
            "format": mime_type,
            "source": work_data.get("source_name") or "Open Access",
            "url": work_data.get("landing_url") or work_data.get("doi")
        }
    except Exception as err:
        cursor = connection.cursor()
        cursor.execute("UPDATE documents SET status = 'failed', indexing_status = 'failed' WHERE id = %s", (doc_id,))
        connection.commit()
        cursor.close()
        return {
            "status": "failed",
            "document_id": doc_id,
            "title": title,
            "error": str(err)
        }


def auto_discover_by_query(
    query: str,
    limit: int = 10,
    open_access_only: bool = True,
    corpus_dir: Path = DEFAULT_CORPUS_STORAGE
) -> Dict[str, Any]:
    """
    Alur terpadu: mencari karya ilmiah berdasarkan topik, mengunduh, dan mengindeksnya secara otomatis.
    """
    print(f"[*] Mencari karya ilmiah dengan topik: '{query}'...")
    candidates = search_openalex_works(query=query, limit=limit, open_access_only=open_access_only)
    print(f"[*] Ditemukan {len(candidates)} kandidat naskah terbuka.")

    summary = {
        "mode": "query",
        "query": query,
        "total_found": len(candidates),
        "indexed_count": 0,
        "skipped_count": 0,
        "failed_count": 0,
        "items": []
    }

    if not candidates:
        return summary

    conn = get_connection()
    try:
        for work in candidates:
            res = ingest_scholarly_work(conn, work, corpus_dir=corpus_dir)
            summary["items"].append(res)
            if res["status"] == "indexed":
                summary["indexed_count"] += 1
                print(f" [✓] Diindeks: {res['title'][:60]}... ({res['format']})")
            elif res["status"] == "skipped":
                summary["skipped_count"] += 1
                print(f" [-] Dilewati (sudah ada): {res['title'][:60]}...")
            else:
                summary["failed_count"] += 1
                print(f" [✗] Gagal: {res['title'][:60]}...")

        if summary["indexed_count"] > 0:
            rebuild_fingerprint_stats(conn)
    finally:
        conn.close()

    return summary


def auto_discover_from_registry(
    limit_per_repo: int = 5,
    max_repos: int = 3,
    corpus_dir: Path = DEFAULT_CORPUS_STORAGE
) -> Dict[str, Any]:
    """
    Memanen naskah secara otomatis dari katalog repositori perguruan tinggi di Indonesia.
    """
    summary = {
        "mode": "registry",
        "repos_attempted": 0,
        "indexed_count": 0,
        "skipped_count": 0,
        "failed_count": 0,
        "details": []
    }

    selected_repos = BUILTIN_INDONESIAN_REPOSITORIES[:max_repos]
    for repo in selected_repos:
        repo_name = repo["name"]
        repo_url = repo["url"]
        print(f"\n[*] Menghubungi repositori: {repo_name} ({repo_url})...")
        summary["repos_attempted"] += 1

        try:
            harvester = OAIHarvester(endpoint_url=repo_url, corpus_dir=corpus_dir)
            # Uji koneksi Identify
            id_info = harvester.identify()
            print(f"    Terhubung: {id_info.get('repository_name', repo_name)}")

            # Panen record terbaru
            h_res = harvester.harvest_records(
                limit=limit_per_repo,
                download_fulltext=True
            )
            summary["indexed_count"] += h_res.get("indexed_count", 0)
            summary["skipped_count"] += h_res.get("skipped_count", 0)
            summary["failed_count"] += h_res.get("failed_count", 0)
            summary["details"].append({
                "name": repo_name,
                "url": repo_url,
                "result": h_res
            })
        except Exception as repo_err:
            print(f"    [!] Kendala pada repositori {repo_name}: {repo_err}")
            summary["details"].append({
                "name": repo_name,
                "url": repo_url,
                "error": str(repo_err)
            })

    return summary


def auto_discover_from_submission_citations(
    document_id: int,
    max_search_citations: int = 5,
    corpus_dir: Path = DEFAULT_CORPUS_STORAGE
) -> Dict[str, Any]:
    """
    Mengekstrak judul-judul rujukan dalam naskah mahasiswa, lalu mencari dan
    mengindeks karya aslinya secara instan sebagai korpus pembanding.
    """
    conn = get_connection()
    extracted_titles = []

    try:
        cursor = conn.cursor(dictionary=True)
        cursor.execute("SELECT id, title FROM documents WHERE id = %s", (document_id,))
        doc = cursor.fetchone()
        if not doc:
            return {"status": "error", "message": f"Dokumen #{document_id} tidak ditemukan."}

        # Ambil halaman-halaman terakhir yang umumnya berisi Daftar Pustaka / Referensi
        cursor.execute("""
            SELECT page_number, text
            FROM document_pages
            WHERE document_id = %s
            ORDER BY page_number DESC
            LIMIT 5
        """, (document_id,))
        pages = cursor.fetchall()
        cursor.close()

        # Ekstrak kalimat atau baris sitasi yang menyerupai judul
        for page in reversed(pages):
            text = page["text"]
            # Cari judul dalam tanda kutip atau format penulisan sitasi standar
            matches = re.findall(r'[“"”]([^"“”]{15,120})[“"”]', text)
            for m in matches:
                clean = re.sub(r"[^\w\s]", "", m).strip()
                if len(clean.split()) >= 4 and clean not in extracted_titles:
                    extracted_titles.append(clean)

        # Jika regex kutipan minim, gunakan judul naskah itu sendiri sebagai benih topik
        if not extracted_titles:
            extracted_titles.append(doc["title"])

    finally:
        conn.close()

    print(f"[*] Terdeteksi {len(extracted_titles)} benih judul rujukan dari dokumen #{document_id}.")
    summary = {
        "mode": "document_citations",
        "document_id": document_id,
        "titles_queried": extracted_titles[:max_search_citations],
        "indexed_count": 0,
        "items": []
    }

    for query_item in extracted_titles[:max_search_citations]:
        q_res = auto_discover_by_query(query=query_item, limit=2, corpus_dir=corpus_dir)
        summary["indexed_count"] += q_res["indexed_count"]
        summary["items"].extend(q_res["items"])

    return summary


if __name__ == "__main__":
    import argparse
    parser = argparse.ArgumentParser(description="AR Tilik Autonomous Corpus Discovery Engine")
    parser.add_argument("--query", "-q", default=None, help="Kata kunci/topik karya ilmiah yang akan dicari dan diindeks secara otomatis")
    parser.add_argument("--limit", "-n", type=int, default=10, help="Jumlah maksimum karya ilmiah yang akan dipanen (default: 10)")
    parser.add_argument("--registry", action="store_true", help="Panen otomatis dari katalog repositori perguruan tinggi di Indonesia")
    parser.add_argument("--document", type=int, default=None, help="ID naskah submisi untuk diekstrak rujukan dan dicari karya aslinya")
    args = parser.parse_args()

    if args.registry:
        print("=== AUTONOMOUS HARVESTING DARI KATALOG REPOSITORI INDONESIA ===")
        res = auto_discover_from_registry(limit_per_repo=args.limit, max_repos=3)
        print("\n" + "=" * 55)
        print(f"Total Naskah Baru Diindeks : {res['indexed_count']}")
        print(f"Dilewati (Duplikat)        : {res['skipped_count']}")
        print(f"Gagal                      : {res['failed_count']}")
        print("=" * 55)
        sys.exit(0)

    if args.document:
        print(f"=== AUTONOMOUS CITATION DISCOVERY DARI DOKUMEN #{args.document} ===")
        res = auto_discover_from_submission_citations(document_id=args.document, max_search_citations=args.limit)
        print("\n" + "=" * 55)
        print(f"Total Karya Rujukan Diindeks : {res['indexed_count']}")
        print("=" * 55)
        sys.exit(0)

    query_term = args.query or "Pendidikan Agama Kristen"
    print(f"=== AUTONOMOUS SCHOLARLY DISCOVERY: '{query_term}' ===")
    res = auto_discover_by_query(query=query_term, limit=args.limit)
    print("\n" + "=" * 55)
    print(f"Total Ditemukan            : {res['total_found']}")
    print(f"Berhasil Diindeks          : {res['indexed_count']}")
    print(f"Dilewati (Duplikat)        : {res['skipped_count']}")
    print(f"Gagal                      : {res['failed_count']}")
    print("=" * 55)
