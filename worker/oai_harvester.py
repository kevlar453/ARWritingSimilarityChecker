"""
AR Tilik - OAI-PMH Academic Repository Harvester
Modul pemanen otomatis naskah karya ilmiah (skripsi, tesis, jurnal) dari repositori
perguruan tinggi berbasis protokol OAI-PMH 2.0 (EPrints, DSpace, OJS, Neliti, dll).
"""

import os
import sys
import urllib.request
import urllib.parse
import xml.etree.ElementTree as ET
import re
import hashlib
from pathlib import Path
from typing import Optional, List, Dict, Any, Tuple

# Ensure project root is prioritized on sys.path and remove script dir to avoid package shadowing
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

OAI_NAMESPACES = {
    "oai": "http://www.openarchives.org/OAI/2.0/",
    "oai_dc": "http://www.openarchives.org/OAI/2.0/oai_dc/",
    "dc": "http://purl.org/dc/elements/1.1/"
}

USER_AGENT = "ARTilik-OAIHarvester/1.2 (Academic Similarity Harvester; +https://tilik.arsetontong.top)"


def clean_xml_tag(tag: str) -> str:
    """Menghilangkan namespace URI dari tag XML agar mudah diakses."""
    if "}" in tag:
        return tag.split("}", 1)[1]
    return tag


def fetch_url_content(url: str, timeout: int = 25) -> Tuple[bytes, str]:
    """Mengambil konten dari URL via HTTP GET dengan timeout dan user-agent standar."""
    req = urllib.request.Request(
        url,
        headers={"User-Agent": USER_AGENT}
    )
    with urllib.request.urlopen(req, timeout=timeout) as resp:
        content = resp.read()
        content_type = resp.headers.get_content_type()
        return content, content_type


def download_file_stream(url: str, dest_path: Path, max_bytes: int = 30 * 1024 * 1024, timeout: int = 30) -> bool:
    """Mengunduh berkas dengan batasan ukuran maksimum dan streaming."""
    req = urllib.request.Request(
        url,
        headers={"User-Agent": USER_AGENT}
    )
    try:
        with urllib.request.urlopen(req, timeout=timeout) as resp:
            downloaded = 0
            with open(dest_path, "wb") as f:
                while chunk := resp.read(65536):
                    downloaded += len(chunk)
                    if downloaded > max_bytes:
                        # Melebihi batas ukuran
                        f.close()
                        dest_path.unlink(missing_ok=True)
                        return False
                    f.write(chunk)
            return True
    except Exception as err:
        dest_path.unlink(missing_ok=True)
        return False


class OAIHarvester:
    """
    Kolektor protokol OAI-PMH 2.0 untuk memanen metadata dan naskah dari repositori kampus.
    """

    def __init__(self, endpoint_url: str, corpus_dir: Path = DEFAULT_CORPUS_STORAGE):
        self.endpoint_url = endpoint_url.strip()
        self.corpus_dir = corpus_dir
        self.corpus_dir.mkdir(parents=True, exist_ok=True)

    def identify(self) -> Dict[str, Any]:
        """
        Memeriksa status dan kapabilitas repositori OAI-PMH target (Verb: Identify).
        """
        query = urllib.parse.urlencode({"verb": "Identify"})
        url = f"{self.endpoint_url}?{query}" if "?" not in self.endpoint_url else f"{self.endpoint_url}&{query}"

        content, _ = fetch_url_content(url)
        root = ET.fromstring(content)

        info = {
            "status": "connected",
            "endpoint": self.endpoint_url,
            "repository_name": "Unknown Repository",
            "protocol_version": "2.0",
            "admin_email": None
        }

        for elem in root.iter():
            tag = clean_xml_tag(elem.tag)
            if tag == "repositoryName":
                info["repository_name"] = elem.text or ""
            elif tag == "protocolVersion":
                info["protocol_version"] = elem.text or "2.0"
            elif tag == "adminEmail":
                info["admin_email"] = elem.text or ""

        return info

    def list_sets(self) -> List[Dict[str, str]]:
        """
        Mendaftar set/koleksi yang tersedia di repositori (Verb: ListSets).
        """
        query = urllib.parse.urlencode({"verb": "ListSets"})
        url = f"{self.endpoint_url}?{query}" if "?" not in self.endpoint_url else f"{self.endpoint_url}&{query}"

        content, _ = fetch_url_content(url)
        root = ET.fromstring(content)

        sets = []
        for set_elem in root.iter():
            if clean_xml_tag(set_elem.tag) == "set":
                spec, name = "", ""
                for child in set_elem:
                    ctag = clean_xml_tag(child.tag)
                    if ctag == "setSpec":
                        spec = child.text or ""
                    elif ctag == "setName":
                        name = child.text or ""
                if spec:
                    sets.append({"spec": spec, "name": name})

        return sets

    def harvest_records(
        self,
        set_spec: Optional[str] = None,
        from_date: Optional[str] = None,
        until_date: Optional[str] = None,
        limit: int = 20,
        download_fulltext: bool = True
    ) -> Dict[str, Any]:
        """
        Memanen data publikasi karya ilmiah menggunakan Verb ListRecords dan metadataPrefix=oai_dc.
        """
        summary = {
            "endpoint": self.endpoint_url,
            "set_spec": set_spec,
            "requested_limit": limit,
            "total_harvested": 0,
            "indexed_count": 0,
            "skipped_count": 0,
            "failed_count": 0,
            "records": []
        }

        resumption_token = None
        conn = get_connection()

        try:
            while summary["total_harvested"] < limit:
                params = {}
                if resumption_token:
                    params["verb"] = "ListRecords"
                    params["resumptionToken"] = resumption_token
                else:
                    params["verb"] = "ListRecords"
                    params["metadataPrefix"] = "oai_dc"
                    if set_spec:
                        params["set"] = set_spec
                    if from_date:
                        params["from"] = from_date
                    if until_date:
                        params["until"] = until_date

                query = urllib.parse.urlencode(params)
                url = f"{self.endpoint_url}?{query}" if "?" not in self.endpoint_url else f"{self.endpoint_url}&{query}"

                try:
                    content, _ = fetch_url_content(url)
                    root = ET.fromstring(content)
                except Exception as req_err:
                    summary["error"] = f"Gagal menghubungi OAI endpoint: {req_err}"
                    break

                # Parse records dalam page saat ini
                page_records = self._parse_records_from_xml(root)
                if not page_records:
                    break

                for rec in page_records:
                    if summary["total_harvested"] >= limit:
                        break

                    summary["total_harvested"] += 1
                    ingest_res = self._ingest_oai_record(conn, rec, download_fulltext=download_fulltext)
                    summary["records"].append(ingest_res)

                    if ingest_res["status"] == "indexed":
                        summary["indexed_count"] += 1
                    elif ingest_res["status"] == "skipped":
                        summary["skipped_count"] += 1
                    else:
                        summary["failed_count"] += 1

                # Cek apakah ada resumptionToken untuk pagination
                resumption_token = None
                for elem in root.iter():
                    if clean_xml_tag(elem.tag) == "resumptionToken":
                        if elem.text and elem.text.strip():
                            resumption_token = elem.text.strip()
                        break

                if not resumption_token:
                    break

            # Jika ada dokumen baru yang diindeks, perbarui fingerprint stats
            if summary["indexed_count"] > 0:
                rebuild_fingerprint_stats(conn)

        finally:
            conn.close()

        return summary

    def _parse_records_from_xml(self, root: ET.Element) -> List[Dict[str, Any]]:
        """Mengekstrak data setiap record dari elemen XML ListRecords."""
        records = []
        for record_elem in root.iter():
            if clean_xml_tag(record_elem.tag) != "record":
                continue

            # Header info
            header = record_elem.find(".//{http://www.openarchives.org/OAI/2.0/}header")
            if header is None:
                for child in record_elem:
                    if clean_xml_tag(child.tag) == "header":
                        header = child
                        break

            if header is not None and header.attrib.get("status") == "deleted":
                continue  # Lewati record yang sudah dihapus

            oai_id = ""
            if header is not None:
                for child in header:
                    if clean_xml_tag(child.tag) == "identifier":
                        oai_id = child.text or ""
                        break

            # Metadata dc info
            metadata_elem = record_elem.find(".//{http://www.openarchives.org/OAI/2.0/}metadata")
            if metadata_elem is None:
                for child in record_elem:
                    if clean_xml_tag(child.tag) == "metadata":
                        metadata_elem = child
                        break

            if metadata_elem is None:
                continue

            title = ""
            creators = []
            descriptions = []
            dates = []
            subjects = []
            identifiers = []
            types = []

            for child in metadata_elem.iter():
                tag_name = clean_xml_tag(child.tag)
                val = (child.text or "").strip()
                if not val:
                    continue

                if tag_name == "title" and not title:
                    title = val
                elif tag_name == "creator":
                    creators.append(val)
                elif tag_name == "description":
                    descriptions.append(val)
                elif tag_name == "date":
                    dates.append(val)
                elif tag_name == "subject":
                    subjects.append(val)
                elif tag_name == "identifier":
                    identifiers.append(val)
                elif tag_name == "type":
                    types.append(val)

            if not title:
                continue

            records.append({
                "oai_identifier": oai_id,
                "title": title,
                "creators": creators,
                "description": "\n\n".join(descriptions),
                "date": dates[0] if dates else "",
                "subjects": subjects,
                "identifiers": identifiers,
                "types": types
            })

        return records

    def _ingest_oai_record(self, connection, record: Dict[str, Any], download_fulltext: bool = True) -> Dict[str, Any]:
        """
        Menyimpan dan mengindeks record OAI ke dalam database korpus.
        Jika ada PDF full-text, unduh dan indeks. Jika tidak, simpan ringkasan abstrak & metadata.
        """
        title = record["title"].strip()
        oai_id = record["oai_identifier"]
        unique_hash = hashlib.md5(f"{oai_id}_{title}".encode("utf-8")).hexdigest()[:12]

        # Cari calon URL PDF dari identifiers
        pdf_url = None
        landing_url = None
        for id_val in record["identifiers"]:
            if id_val.startswith("http://") or id_val.startswith("https://"):
                if id_val.lower().endswith(".pdf") or "/download/" in id_val.lower() or "/bitstream/" in id_val.lower():
                    pdf_url = id_val
                elif not landing_url:
                    landing_url = id_val

        saved_path = None
        mime_type = "text/plain"
        source_type = "repository"

        # Cek heuristik jenis sumber
        combined_types = " ".join(record["types"] + record["subjects"]).lower()
        if "thesis" in combined_types or "skripsi" in combined_types or "tesis" in combined_types:
            source_type = "thesis"
        elif "article" in combined_types or "journal" in combined_types or "jurnal" in combined_types:
            source_type = "journal"

        # Coba unduh PDF jika diperintahkan dan URL ditemukan
        if download_fulltext and pdf_url:
            candidate_file = self.corpus_dir / f"oai_{unique_hash}.pdf"
            if download_file_stream(pdf_url, candidate_file):
                # Verifikasi magic header PDF
                with open(candidate_file, "rb") as test_f:
                    header = test_f.read(5)
                if header == b"%PDF-":
                    saved_path = candidate_file
                    mime_type = "application/pdf"
                else:
                    candidate_file.unlink(missing_ok=True)

        # Fallback: Buat dokumen teks akademik komprehensif dari metadata & abstrak
        if not saved_path:
            text_filename = f"oai_{unique_hash}.txt"
            saved_path = self.corpus_dir / text_filename

            creators_str = ", ".join(record["creators"]) if record["creators"] else "Anonim / Institusi"
            subjects_str = ", ".join(record["subjects"]) if record["subjects"] else "-"
            source_ref = pdf_url or landing_url or oai_id

            structured_content = (
                f"JUDUL: {title}\n"
                f"PENULIS: {creators_str}\n"
                f"TAHUN / TANGGAL: {record['date']}\n"
                f"SUBJEK / KATA KUNCI: {subjects_str}\n"
                f"IDENTIFIER OAI: {oai_id}\n"
                f"TAUTAN SUMBER: {source_ref}\n\n"
                f"ABSTRAK DAN DESKRIPSI KARYA ILMIAH:\n"
                f"{record['description'] or 'Tidak ada abstrak yang disediakan oleh repositori.'}\n"
            )

            with open(saved_path, "w", encoding="utf-8") as f:
                f.write(structured_content)
            mime_type = "text/plain"

        file_size = saved_path.stat().st_size
        filename = saved_path.name

        # Periksa duplikasi sebelum insert
        if is_already_indexed(connection, filename, file_size, title):
            saved_path.unlink(missing_ok=True)
            return {
                "status": "skipped",
                "title": title,
                "oai_id": oai_id,
                "reason": "Sudah terindeks di korpus"
            }

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
                %s, 'processing', NOW(), NOW()
            )
        """, (
            title,
            filename,
            mime_type,
            file_size,
            str(saved_path),
            source_type
        ))
        doc_id = cursor.lastrowid
        connection.commit()
        cursor.close()

        try:
            # Indeks shingle dan chunk dokumen
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
                "oai_id": oai_id,
                "format": mime_type,
                "source_type": source_type
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


if __name__ == "__main__":
    import argparse
    parser = argparse.ArgumentParser(description="AR Tilik OAI-PMH Repository Harvester")
    parser.add_argument("--url", required=True, help="URL endpoint OAI-PMH repositori kampus (contoh: https://repo.ac.id/cgi/oai2)")
    parser.add_argument("--set", dest="set_spec", default=None, help="Spesifikasi Set/Koleksi repositori (contoh: thesis, electronic_theses)")
    parser.add_argument("--limit", type=int, default=10, help="Batas maksimum dokumen yang dipanen (default: 10)")
    parser.add_argument("--no-fulltext", dest="fulltext", action="store_false", help="Hanya panen metadata dan abstrak, lewati download PDF")
    parser.add_argument("--identify", action="store_true", help="Hanya periksa koneksi dan identitas repositori target")
    parser.add_argument("--list-sets", action="store_true", help="Tampilkan daftar koleksi/set yang tersedia")
    args = parser.parse_args()

    harvester = OAIHarvester(args.url)

    if args.identify:
        info = harvester.identify()
        print("=== IDENTITAS REPOSITORI OAI-PMH ===")
        print(f"Nama Repositori  : {info.get('repository_name')}")
        print(f"Versi Protokol   : {info.get('protocol_version')}")
        print(f"Email Pengelola  : {info.get('admin_email')}")
        print(f"Endpoint URL     : {info.get('endpoint')}")
        sys.exit(0)

    if args.list_sets:
        sets = harvester.list_sets()
        print(f"=== DAFTAR KOLEKSI ({len(sets)} SET) ===")
        for s in sets:
            print(f"• [{s['spec']}] {s['name']}")
        sys.exit(0)

    print(f"[*] Memulai panen dari: {args.url}")
    if args.set_spec:
        print(f"[*] Filter Set: {args.set_spec}")
    print(f"[*] Target naskah: {args.limit} dokumen (Unduh Full-Text: {'Ya' if args.fulltext else 'Tidak'})")

    res = harvester.harvest_records(
        set_spec=args.set_spec,
        limit=args.limit,
        download_fulltext=args.fulltext
    )

    print("\n" + "=" * 50)
    print("        HASIL PANEN REPOSITORI OAI-PMH             ")
    print("=" * 50)
    print(f"Total terpindai : {res.get('total_harvested', 0)}")
    print(f"Berhasil diindeks: {res.get('indexed_count', 0)}")
    print(f"Dilewati (duplikat): {res.get('skipped_count', 0)}")
    print(f"Gagal           : {res.get('failed_count', 0)}")
    print("=" * 50)
    for r in res.get("records", []):
        icon = "✓" if r["status"] == "indexed" else ("-" if r["status"] == "skipped" else "✗")
        print(f" [{icon}] {r.get('title')[:60]}... ({r.get('status')})")
