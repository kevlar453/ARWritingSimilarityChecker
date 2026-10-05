"""
AR Tilik - Just-In-Time (JIT) Dynamic Topic-Based Corpus Harvester
Secara otomatis mengekstrak kata kunci dan domain disipliner dari karya ilmiah yang diuji,
lalu secara mandiri mengunduh dan mengindeks literatur akademik sebidang dari basis data terbuka
(OpenAlex / Repositori Kampus) sebagai pembanding riil yang relevan sebelum pemeriksaan kemiripan.
"""

import os
import sys
import re
from pathlib import Path
from typing import Optional, List, Dict, Any, Tuple

# Pastikan PROJECT_ROOT berada di urutan teratas sys.path
PROJECT_ROOT = Path(__file__).resolve().parents[1]
script_dir = str(Path(__file__).resolve().parent)
if script_dir in sys.path:
    sys.path.remove(script_dir)
if str(PROJECT_ROOT) not in sys.path:
    sys.path.insert(0, str(PROJECT_ROOT))

from worker.database import get_connection
from worker.auto_discovery_harvester import search_openalex_works, ingest_scholarly_work

# Daftar frasa/kata pengisi generik judul skripsi/tesis Indonesia yang harus disaring
GENERIC_TITLE_FILLERS = [
    r"\bpengaruh\b", r"\bhubungan\b", r"\banalisis\b", r"\befektivitas\b",
    r"\bperbandingan\b", r"\bfaktor-faktor\s+yang\s+mempengaruhi\b", r"\bfaktor\b",
    r"\bterhadap\b", r"\bpada\b", r"\bdalam\b", r"\bdan\b", r"\batau\b",
    r"\bmata\s+diklat\b", r"\bmata\s+pelajaran\b", r"\bkelas\b", r"\bsemester\b",
    r"\btahun\s+ajaran\b", r"\btahun\s+akademik\b", r"\bstudi\s+kasus\b",
    r"\bdi\s+smk\s+negeri\b", r"\bdi\s+sma\s+negeri\b", r"\bdi\s+smp\s+negeri\b",
    r"\bdi\s+sd\s+negeri\b", r"\bdi\b", r"\bke\b", r"\bdari\b", r"\byang\b",
    r"\bsebagai\b", r"\bupaya\b", r"\bmeningkatkan\b", r"\bpeningkatan\b"
]


def extract_substantive_topics(title: str, text: Optional[str] = None) -> List[str]:
    """
    Mengekstrak kueri pencarian topik akademik yang substantif dari judul dan teks naskah.
    Menghilangkan frasa template administratif (misal: 'Pengaruh ... Terhadap ... Di SMK Negeri 1 Sedayu').
    """
    if not title:
        return []

    clean_title = title.lower()

    # Bersihkan tanda baca aneh
    clean_title = re.sub(r"[\"\'\(\)\[\]\:\;\,\.\-\/\\]+", " ", clean_title)

    # 1. Deteksi domain kejuruan / spesifik khusus (seperti K3, SMK, Keperawatan, dll.)
    special_domains = []
    if re.search(r"\b(keselamatan\s+dan\s+kesehatan\s+kerja|k3)\b", clean_title):
        special_domains.append("keselamatan dan kesehatan kerja SMK")
    if re.search(r"\b(smk|kejuruan|vokasi)\b", clean_title):
        if not special_domains:
            special_domains.append("pendidikan kejuruan SMK")
    if re.search(r"\b(teologi|pastoral|gereja|jemaat|alkitab)\b", clean_title):
        special_domains.append("kepemimpinan pastoral gereja")
    if re.search(r"\b(hukum|pidana|perdata|yuridis)\b", clean_title):
        special_domains.append("kajian yuridis hukum")

    # 2. Filter kata pengisi generik
    filtered = clean_title
    for filler in GENERIC_TITLE_FILLERS:
        filtered = re.sub(filler, " ", filtered, flags=re.IGNORECASE)

    # Ambil token kata yang tersisa (panjang >= 3)
    tokens = [w.strip() for w in filtered.split() if len(w.strip()) >= 3]

    queries = []

    # Prioritaskan special domain jika ada
    for sd in special_domains:
        if sd not in queries:
            queries.append(sd)

    if tokens:
        # Kueri kombinasi kata kunci inti (maksimal 5 kata pertama)
        core_query = " ".join(tokens[:5])
        if core_query and core_query not in queries:
            queries.append(core_query)

        # Jika ada lebih dari 3 kata, buat kueri alternatif 3 kata terakhir
        if len(tokens) >= 4:
            alt_query = " ".join(tokens[-3:])
            if alt_query and alt_query not in queries:
                queries.append(alt_query)

    return queries[:3]


def harvest_topic_references_jit(
    title: str,
    text: Optional[str] = None,
    max_works: int = 5,
    connection = None
) -> Dict[str, Any]:
    """
    Menjalankan Just-In-Time (JIT) Dynamic Scholarly Retrieval.
    Mencari dan langsung mengindeks naskah sebidang yang relevan ke dalam korpus rujukan.
    """
    queries = extract_substantive_topics(title, text)
    if not queries:
        return {
            "status": "skipped",
            "message": "Tidak dapat mengekstrak kata kunci topik dari judul naskah.",
            "discovered_works": []
        }

    close_conn = False
    if connection is None:
        connection = get_connection()
        close_conn = True

    discovered_works = []
    seen_titles = set()

    try:
        for q in queries:
            if len(discovered_works) >= max_works:
                break

            print(f"  [JIT HARVESTER] Menjelajahi literatur terbuka untuk topik: '{q}'...")
            works = search_openalex_works(query=q, limit=max_works, open_access_only=True)

            for w in works:
                w_title = w.get("title", "").strip()
                if not w_title or w_title.lower() in seen_titles:
                    continue
                seen_titles.add(w_title.lower())

                # Ingest & langsung indeks ke dalam korpus
                res = ingest_scholarly_work(connection=connection, work_data=w)
                if res.get("status") in ("indexed", "skipped"):
                    discovered_works.append({
                        "document_id": res.get("document_id"),
                        "title": w_title,
                        "year": w.get("year"),
                        "source_name": w.get("source_name") or "Jurnal Akademik Nasional/Internasional",
                        "doi": w.get("doi"),
                        "url": w.get("landing_url") or w.get("pdf_url"),
                        "ingest_status": res.get("status")
                    })

                if len(discovered_works) >= max_works:
                    break

        return {
            "status": "success",
            "queries_executed": queries,
            "total_discovered": len(discovered_works),
            "discovered_works": discovered_works
        }
    except Exception as e:
        print(f"  [JIT HARVESTER ERROR] Kendala saat mengambil korpus dinamis: {e}")
        return {
            "status": "error",
            "queries_executed": queries,
            "error_message": str(e),
            "discovered_works": discovered_works
        }
    finally:
        if close_conn:
            connection.close()


if __name__ == "__main__":
    import argparse
    parser = argparse.ArgumentParser(description="AR Tilik Just-In-Time (JIT) Topic Harvester")
    parser.add_argument("--title", type=str, default="PENGARUH MOTIVASI DAN MINAT TERHADAP PRESTASI SISWA PADA MATA DIKLAT KESELAMATAN DAN KESEHATAN KERJA DI SMK NEGERI 1 SEDAYU", help="Judul naskah yang diuji")
    parser.add_argument("--text", type=str, default=None, help="Teks atau cuplikan naskah")
    parser.add_argument("--max-works", type=int, default=3, help="Jumlah naskah maksimal yang diunduh")
    args = parser.parse_args()

    sample_title = args.title
    topics = extract_substantive_topics(sample_title, args.text)
    print("=== PENGUJIAN EKSTRAKSI TOPIK JIT ===")
    print("Judul Asli   :", sample_title)
    print("Kueri Terpilih:", topics)

    print("\n=== PENGUJIAN HARVESTING JIT ===")
    res = harvest_topic_references_jit(sample_title, args.text, max_works=args.max_works)
    print("Status       :", res["status"])
    print("Total Karya  :", res["total_discovered"])
    for w in res.get("discovered_works", []):
        print(f"- [{w.get('year')}] {w.get('title')} ({w.get('source_name')}) -> {w.get('ingest_status')}")
