"""
AR Tilik - National Scholarly Harvester (Neliti & GARUDA)
Modul pemanen otomatis karya ilmiah dari repositori nasional Indonesia:
1. Neliti (Repositori Riset & Jurnal Nasional) via OAI-PMH 2.0 & Web Catalog
2. GARUDA / Garba Rujukan Digital Kemdiktisaintek (Portal Jurnal Terakreditasi Nasional)
"""

import html
import os
import sys
import time
import json
import urllib.request
import urllib.parse
import xml.etree.ElementTree as ET
import re
import hashlib
from pathlib import Path
from datetime import datetime
from typing import Optional, List, Dict, Any, Tuple

# Pastikan PROJECT_ROOT diprioritaskan pada sys.path
PROJECT_ROOT = Path(__file__).resolve().parents[1]
script_dir = str(Path(__file__).resolve().parent)
if script_dir in sys.path:
    sys.path.remove(script_dir)
if str(PROJECT_ROOT) not in sys.path:
    sys.path.insert(0, str(PROJECT_ROOT))

from worker.database import get_connection, load_env
from worker.indexer import index_document, rebuild_fingerprint_stats
from worker.corpus_harvester import is_already_indexed

load_env()

DEFAULT_CORPUS_STORAGE = PROJECT_ROOT / "writable" / "documents" / "corpus"
USER_AGENT = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36 (ARTilik National Harvester; +https://tilik.arsetontong.top)"

OAI_NAMESPACES = {
    "oai": "http://www.openarchives.org/OAI/2.0/",
    "oai_dc": "http://www.openarchives.org/OAI/2.0/oai_dc/",
    "dc": "http://purl.org/dc/elements/1.1/"
}

GARUDA_BASE_URL = "https://garuda.kemdiktisaintek.go.id"
NELITI_OAI_URL = "https://www.neliti.com/oai"


def download_stream(url: str, dest_path: Path, max_bytes: int = 25 * 1024 * 1024, timeout: int = 25) -> bool:
    """Mengunduh berkas dengan batasan ukuran dan timeout."""
    req = urllib.request.Request(url, headers={"User-Agent": USER_AGENT})
    try:
        with urllib.request.urlopen(req, timeout=timeout) as resp:
            downloaded = 0
            with open(dest_path, "wb") as f:
                while chunk := resp.read(65536):
                    downloaded += len(chunk)
                    if downloaded > max_bytes:
                        f.close()
                        dest_path.unlink(missing_ok=True)
                        return False
                    f.write(chunk)
            return True
    except Exception:
        dest_path.unlink(missing_ok=True)
        return False


def clean_text(text: str) -> str:
    """Membersihkan tag XML / HTML, entitas HTML, normalisasi spasi, dan memperbaiki mojibake."""
    if not text:
        return ""
    text = html.unescape(text)
    text = re.sub(r"<[^>]+>", " ", text)
    text = re.sub(r"\s+", " ", text).strip()
    try:
        text = text.encode("cp1252").decode("utf-8")
    except (UnicodeEncodeError, UnicodeDecodeError):
        pass
    return text


class NationalHarvester:
    """Mesin Pemanen Korpus Nasional (Neliti & GARUDA)."""

    def __init__(self, corpus_dir: Path = DEFAULT_CORPUS_STORAGE):
        self.corpus_dir = corpus_dir
        self.corpus_dir.mkdir(parents=True, exist_ok=True)

    def log_harvest_start(self, source: str, source_name: str, query: str = "") -> int:
        """Mencatat dimulainya proses pemanenan ke tabel harvest_logs."""
        conn = get_connection()
        try:
            cursor = conn.cursor()
            cursor.execute("""
                INSERT INTO harvest_logs (
                    source, source_name, query_or_set, items_found, items_indexed,
                    items_skipped, items_failed, status, started_at, created_at
                ) VALUES (%s, %s, %s, 0, 0, 0, 0, 'running', NOW(), NOW())
            """, (source, source_name, query[:255] if query else None))
            conn.commit()
            log_id = cursor.lastrowid
            return log_id
        finally:
            conn.close()

    def log_harvest_finish(
        self,
        log_id: int,
        items_found: int,
        items_indexed: int,
        items_skipped: int,
        items_failed: int,
        status: str,
        summary: str,
        error_msg: str = "",
        duration: float = 0.0
    ):
        """Memperbarui rekapitulasi pemanenan di tabel harvest_logs."""
        conn = get_connection()
        try:
            cursor = conn.cursor()
            cursor.execute("""
                UPDATE harvest_logs SET
                    items_found = %s,
                    items_indexed = %s,
                    items_skipped = %s,
                    items_failed = %s,
                    status = %s,
                    summary = %s,
                    error_message = %s,
                    duration_seconds = %s,
                    finished_at = NOW()
                WHERE id = %s
            """, (
                items_found,
                items_indexed,
                items_skipped,
                items_failed,
                status,
                summary,
                error_msg if error_msg else None,
                round(duration, 2),
                log_id
            ))
            conn.commit()
        finally:
            conn.close()

    def harvest_neliti(self, query: str = "", limit: int = 15) -> Dict[str, Any]:
        """
        Memanen publikasi ilmiah dari Neliti (melalui OAI-PMH atau query pencarian).
        """
        start_time = time.time()
        log_id = self.log_harvest_start("neliti", "Neliti - Portal Riset Indonesia", query)
        found_count = 0
        indexed_count = 0
        skipped_count = 0
        failed_count = 0
        results = []

        try:
            # Menggunakan OAI-PMH ListRecords
            params = {"verb": "ListRecords", "metadataPrefix": "oai_dc"}
            oai_url = f"{NELITI_OAI_URL}?{urllib.parse.urlencode(params)}"
            req = urllib.request.Request(oai_url, headers={"User-Agent": USER_AGENT})

            with urllib.request.urlopen(req, timeout=30) as resp:
                xml_data = resp.read()

            root = ET.fromstring(xml_data)
            records = root.findall(".//oai:record", OAI_NAMESPACES)
            found_count = len(records)

            conn = get_connection()
            try:
                for rec in records:
                    if indexed_count >= limit:
                        break

                    # Cek deleted header
                    header = rec.find("oai:header", OAI_NAMESPACES)
                    if header is not None and header.get("status") == "deleted":
                        continue

                    title_el = rec.find(".//dc:title", OAI_NAMESPACES)
                    if title_el is None or not title_el.text:
                        continue
                    title = clean_text(title_el.text)

                    # Jika query ditentukan, filter berdasarkan kecocokan kata kunci
                    if query and query.lower() not in title.lower():
                        desc_el = rec.find(".//dc:description", OAI_NAMESPACES)
                        desc_text = desc_el.text if desc_el is not None and desc_el.text else ""
                        if query.lower() not in desc_text.lower():
                            continue

                    creator_el = rec.find(".//dc:creator", OAI_NAMESPACES)
                    author = clean_text(creator_el.text) if creator_el is not None and creator_el.text else "Peneliti Neliti"

                    desc_el = rec.find(".//dc:description", OAI_NAMESPACES)
                    abstract = clean_text(desc_el.text) if desc_el is not None and desc_el.text else ""

                    ident_els = rec.findall(".//dc:identifier", OAI_NAMESPACES)
                    urls = [clean_text(i.text) for i in ident_els if i.text and i.text.startswith("http")]
                    landing_url = urls[0] if urls else "https://www.neliti.com"

                    # Simpan ke korpus
                    ingest_res = self._ingest_article(
                        conn,
                        title=title,
                        author=author,
                        abstract=abstract,
                        source_portal="Neliti (Repositori Nasional)",
                        landing_url=landing_url,
                        prefix="neliti"
                    )

                    if ingest_res["status"] == "indexed":
                        indexed_count += 1
                        results.append(ingest_res)
                    elif ingest_res["status"] == "skipped":
                        skipped_count += 1
                    else:
                        failed_count += 1

                # Rebuild fingerprint stats jika ada yang berhasil diindeks
                if indexed_count > 0:
                    try:
                        rebuild_fingerprint_stats(conn)
                    except Exception as rf_err:
                        print(f"[REBUILD STATS NOTICE] {rf_err}")

            finally:
                conn.close()

            status = "completed" if indexed_count > 0 else ("partial" if skipped_count > 0 else "completed")
            summary = f"Pemanenan Neliti selesai: {indexed_count} naskah baru terindeks, {skipped_count} dilewati (duplikat), {failed_count} gagal."
            duration = time.time() - start_time
            self.log_harvest_finish(log_id, found_count, indexed_count, skipped_count, failed_count, status, summary, "", duration)

            return {
                "source": "neliti",
                "status": status,
                "found": found_count,
                "indexed": indexed_count,
                "skipped": skipped_count,
                "failed": failed_count,
                "duration": round(duration, 2),
                "summary": summary,
                "items": results
            }

        except Exception as e:
            duration = time.time() - start_time
            err_msg = str(e)
            summary = f"Pemanenan Neliti mengalami galat: {err_msg}"
            self.log_harvest_finish(log_id, found_count, indexed_count, skipped_count, failed_count, "failed", summary, err_msg, duration)
            return {
                "source": "neliti",
                "status": "failed",
                "error": err_msg,
                "indexed": indexed_count,
                "duration": round(duration, 2)
            }

    def harvest_garuda(self, query: str = "informatika", limit: int = 15, page: Optional[int] = None) -> Dict[str, Any]:
        """
        Memanen jurnal terakreditasi dari GARUDA Kemdiktisaintek.
        """
        start_time = time.time()
        log_id = self.log_harvest_start("garuda", "GARUDA Dikti (Garba Rujukan Digital)", query)
        found_count = 0
        indexed_count = 0
        skipped_count = 0
        failed_count = 0
        results = []

        if page is None:
            # Rotasi halaman 1 - 5 agar tiap hari memanen artikel baru
            page = (int(datetime.now().strftime("%j")) % 5) + 1

        try:
            encoded_query = urllib.parse.quote_plus(query if query else "penelitian")
            url = f"{GARUDA_BASE_URL}/documents?q={encoded_query}&page={page}"
            req = urllib.request.Request(url, headers={"User-Agent": USER_AGENT})

            with urllib.request.urlopen(req, timeout=30) as resp:
                html = resp.read().decode("utf-8", errors="ignore")

            # Ekstraksi blok artikel
            article_blocks = re.findall(
                r"<div class=[\"\x27]article-item[\"\x27]>(.*?)(?=<div class=[\"\x27]article-item[\"\x27]>|\Z)",
                html,
                re.DOTALL | re.IGNORECASE
            )
            found_count = len(article_blocks)

            conn = get_connection()
            try:
                for block in article_blocks:
                    if indexed_count >= limit:
                        break

                    # Ekstrak judul
                    m_title = re.search(r"<a class=[\"\x27]title-article[\"\x27][^>]*>(.*?)</a>", block, re.DOTALL | re.IGNORECASE)
                    if not m_title:
                        continue
                    title = clean_text(m_title.group(1))

                    # Ekstrak penulis
                    authors = []
                    for m_auth in re.finditer(r"<a class=[\"\x27]author-article[\"\x27][^>]*>(.*?)</a>", block, re.DOTALL | re.IGNORECASE):
                        a_name = clean_text(m_auth.group(1))
                        if a_name:
                            authors.append(a_name)
                    author_str = "; ".join(authors) if authors else "Peneliti Jurnal GARUDA"

                    # Ekstrak publisher / jurnal
                    m_pub = re.search(r"<xmp class=[\"\x27]subtitle-article[\"\x27]>(.*?)</xmp>", block, re.DOTALL | re.IGNORECASE)
                    publisher = clean_text(m_pub.group(1)) if m_pub else "Jurnal Ilmiah Terakreditasi Nasional"

                    # Ekstrak tautan unduh PDF langsung
                    m_dl = re.search(r"<a[^>]+href=[\"\x27](http[^\"\x27]+download[^\"\x27]*)[\"\x27][^>]*>", block, re.IGNORECASE)
                    pdf_url = m_dl.group(1) if m_dl else ""

                    # Ekstrak landing URL
                    m_view = re.search(r"<a[^>]+href=[\"\x27](http[^\"\x27]+view[^\"\x27]*)[\"\x27][^>]*>", block, re.IGNORECASE)
                    landing_url = m_view.group(1) if m_view else f"{GARUDA_BASE_URL}/documents"

                    # Ingest artikel
                    ingest_res = self._ingest_article(
                        conn,
                        title=title,
                        author=author_str,
                        abstract="",
                        source_portal=f"GARUDA ({publisher})",
                        landing_url=landing_url,
                        pdf_url=pdf_url,
                        prefix="garuda"
                    )

                    if ingest_res["status"] == "indexed":
                        indexed_count += 1
                        results.append(ingest_res)
                    elif ingest_res["status"] == "skipped":
                        skipped_count += 1
                    else:
                        failed_count += 1

                if indexed_count > 0:
                    try:
                        rebuild_fingerprint_stats(conn)
                    except Exception as rf_err:
                        print(f"[REBUILD STATS NOTICE] {rf_err}")

            finally:
                conn.close()

            status = "completed" if indexed_count > 0 else ("partial" if skipped_count > 0 else "completed")
            summary = f"Pemanenan GARUDA selesai: {indexed_count} naskah baru terindeks, {skipped_count} dilewati (duplikat), {failed_count} gagal."
            duration = time.time() - start_time
            self.log_harvest_finish(log_id, found_count, indexed_count, skipped_count, failed_count, status, summary, "", duration)

            return {
                "source": "garuda",
                "status": status,
                "found": found_count,
                "indexed": indexed_count,
                "skipped": skipped_count,
                "failed": failed_count,
                "duration": round(duration, 2),
                "summary": summary,
                "items": results
            }

        except Exception as e:
            duration = time.time() - start_time
            err_msg = str(e)
            summary = f"Pemanenan GARUDA mengalami galat: {err_msg}"
            self.log_harvest_finish(log_id, found_count, indexed_count, skipped_count, failed_count, "failed", summary, err_msg, duration)
            return {
                "source": "garuda",
                "status": "failed",
                "summary": summary,
                "error": err_msg,
                "indexed": indexed_count,
                "duration": round(duration, 2)
            }

    def _ingest_article(
        self,
        conn,
        title: str,
        author: str,
        abstract: str,
        source_portal: str,
        landing_url: str,
        pdf_url: str = "",
        prefix: str = "national"
    ) -> Dict[str, Any]:
        """
        Menyimpan dan mengindeks satu artikel ke dalam korpus institusi AR Tilik.
        """
        # Batasi panjang judul agar tidak melebihi VARCHAR(255) di basis data
        title = title[:250].strip() if title else "Karya Ilmiah Nasional"

        # Cek duplikasi judul di tabel documents
        cursor = conn.cursor(dictionary=True)
        cursor.execute("SELECT id FROM documents WHERE document_type = 'corpus' AND title = %s LIMIT 1", (title,))
        exists = cursor.fetchone()
        cursor.close()

        if exists:
            return {"status": "skipped", "title": title, "reason": "Sudah terindeks di korpus"}

        unique_hash = hashlib.md5(f"{prefix}_{title}_{landing_url}".encode("utf-8")).hexdigest()[:12]
        saved_path = None
        mime_type = "text/plain"

        # 1. Coba unduh PDF jika ada tautan unduh langsung
        if pdf_url:
            candidate_pdf = self.corpus_dir / f"{prefix}_{unique_hash}.pdf"
            try:
                if download_stream(pdf_url, candidate_pdf, max_bytes=20 * 1024 * 1024, timeout=20):
                    with open(candidate_pdf, "rb") as f:
                        magic = f.read(5)
                    if magic == b"%PDF-":
                        saved_path = candidate_pdf
                        mime_type = "application/pdf"
                    else:
                        candidate_pdf.unlink(missing_ok=True)
            except Exception:
                candidate_pdf.unlink(missing_ok=True)

        # 2. Fallback: simpan sebagai dokumen teks ilmiah terstruktur dengan metadata lengkap
        if not saved_path:
            text_filename = f"{prefix}_{unique_hash}.txt"
            saved_path = self.corpus_dir / text_filename
            content = (
                f"JUDUL KARYA ILMIAH: {title}\n"
                f"PENULIS: {author}\n"
                f"SUMBER PORTAL: {source_portal}\n"
                f"TAUTAN ASLI: {landing_url}\n\n"
                f"RINGKASAN DAN ABSTRAK KARYA ILMIAH:\n"
                f"{abstract if abstract else f'Naskah ilmiah terpublikasi resmi pada portal nasional {source_portal}. Rujukan orisinalitas terakreditasi.'}\n"
            )
            with open(saved_path, "w", encoding="utf-8") as f:
                f.write(content)
            mime_type = "text/plain"

        file_size = saved_path.stat().st_size
        filename = saved_path.name

        cursor = conn.cursor()
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
        conn.commit()
        doc_id = cursor.lastrowid
        cursor.close()

        # Eksekusi pengindeksan n-gram & chunks
        try:
            index_document(conn, doc_id, str(saved_path), mime_type)

            return {
                "status": "indexed",
                "document_id": doc_id,
                "title": title,
                "author": author,
                "source": source_portal
            }
        except Exception as e:
            cursor = conn.cursor()
            cursor.execute("UPDATE documents SET status = 'failed', indexing_status = 'failed' WHERE id = %s", (doc_id,))
            conn.commit()
            cursor.close()
            return {"status": "failed", "document_id": doc_id, "title": title, "error": str(e)}

    def run_scheduled_harvests(self) -> List[Dict[str, Any]]:
        """Menjalankan seluruh jadwal pemanenan korpus aktif dari tabel harvest_schedules."""
        conn = get_connection()
        schedules = []
        try:
            cursor = conn.cursor(dictionary=True)
            cursor.execute("SELECT * FROM harvest_schedules WHERE is_active = 1")
            schedules = cursor.fetchall()
        finally:
            conn.close()

        results = []
        for sched in schedules:
            source = sched["source"]
            keywords = [k.strip() for k in sched.get("keywords", "").split(",") if k.strip()]
            # Rotasi kata kunci otomatis berdasarkan hari (day of year) agar dinamis & tidak mentok di satu topik
            day_offset = int(datetime.now().strftime("%j"))
            chosen_kw = keywords[day_offset % len(keywords)] if keywords else "pendidikan"
            limit = int(sched.get("limit_per_run") or 10)

            print(f"[HARVEST CRON] Memulai pemanenan '{sched['name']}' ({source}) keyword: '{chosen_kw}' (rotasi #{day_offset % len(keywords) + 1}/{len(keywords)}) limit: {limit}...")

            if source == "neliti":
                res = self.harvest_neliti(query=chosen_kw, limit=limit)
            elif source == "garuda":
                res = self.harvest_garuda(query=chosen_kw, limit=limit)
            else:
                # all
                res_n = self.harvest_neliti(query=chosen_kw, limit=limit // 2 or 5)
                res_g = self.harvest_garuda(query=chosen_kw, limit=limit // 2 or 5)
                res = {"source": "all", "neliti": res_n, "garuda": res_g}

            results.append(res)

            # Update last_run_at di schedule
            conn = get_connection()
            try:
                cursor = conn.cursor()
                cursor.execute("UPDATE harvest_schedules SET last_run_at = NOW(), updated_at = NOW() WHERE id = %s", (sched["id"],))
                conn.commit()
            finally:
                conn.close()

        return results


if __name__ == "__main__":
    import argparse

    parser = argparse.ArgumentParser(description="AR Tilik National Scholarly Harvester")
    parser.add_argument("--source", choices=["neliti", "garuda", "all"], default="all", help="Sumber portal naskah")
    parser.add_argument("--query", type=str, default="", help="Kata kunci pencarian naskah")
    parser.add_argument("--limit", type=int, default=5, help="Jumlah maksimum naskah baru yang diindeks")
    parser.add_argument("--scheduled", action="store_true", help="Jalankan seluruh jadwal pemanenan korpus aktif")

    args = parser.parse_args()
    harvester = NationalHarvester()

    if args.scheduled:
        print("[*] Menjalankan pemanenan terjadwal dari basis data konfigurasi...")
        res = harvester.run_scheduled_harvests()
        print(f"[✓] Pemanenan terjadwal selesai. Hasil: {json.dumps(res, indent=2)}")
    else:
        if args.source == "neliti":
            print(f"[*] Menjalankan pemanenan Neliti (query: '{args.query}', limit: {args.limit})...")
            res = harvester.harvest_neliti(query=args.query, limit=args.limit)
            print(f"[✓] Selesai: {res['summary']}")
        elif args.source == "garuda":
            print(f"[*] Menjalankan pemanenan GARUDA (query: '{args.query}', limit: {args.limit})...")
            res = harvester.harvest_garuda(query=args.query or "informatika", limit=args.limit)
            print(f"[✓] Selesai: {res['summary']}")
        else:
            print(f"[*] Menjalankan pemanenan kombinasi Neliti & GARUDA (limit: {args.limit})...")
            res_n = harvester.harvest_neliti(query=args.query, limit=args.limit // 2 or 3)
            print(f" - Neliti: {res_n.get('summary')}")
            res_g = harvester.harvest_garuda(query=args.query or "informatika", limit=args.limit // 2 or 3)
            print(f" - GARUDA: {res_g.get('summary')}")
