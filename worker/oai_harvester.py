"""
AR Tilik - OAI-PMH Academic Repository Harvester (P2-2 Pilot-Ready)
Modul pemanen otomatis naskah karya ilmiah (skripsi, tesis, jurnal) dari repositori
perguruan tinggi berbasis protokol OAI-PMH 2.0 (EPrints, DSpace, OJS, dll) dengan:
- Sanitasi XML kontrol karakter & penanganan error OAI-PMH
- Proteksi Anti-SSRF (validasi skema, IP privat, dan resolusi DNS sebelum request)
- Filter Open-Access Only & validasi minimal substansi teks (>= 25 kata)
- Pelacakan Provenance lengkap (oai_identifier, source_repository, source_url, harvest_batch_id)
"""

import hashlib
import http.client
import ipaddress
import os
import re
import socket
import sys
import time
import urllib.error
import urllib.parse
import urllib.request
import xml.etree.ElementTree as ET
from datetime import datetime
from pathlib import Path
from typing import Any, Dict, List, Optional, Tuple

PROJECT_ROOT = Path(__file__).resolve().parents[1]
script_dir = str(Path(__file__).resolve().parent)
if script_dir in sys.path:
    sys.path.remove(script_dir)
if str(PROJECT_ROOT) not in sys.path:
    sys.path.insert(0, str(PROJECT_ROOT))

from worker.corpus_harvester import is_already_indexed
from worker.database import get_connection, load_env
from worker.extractor import extract_document
from worker.indexer import index_document, rebuild_fingerprint_stats

load_env()
DEFAULT_CORPUS_STORAGE = PROJECT_ROOT / "writable" / "documents" / "corpus"

OAI_NAMESPACES = {
    "oai": "http://www.openarchives.org/OAI/2.0/",
    "oai_dc": "http://www.openarchives.org/OAI/2.0/oai_dc/",
    "dc": "http://purl.org/dc/elements/1.1/",
}

USER_AGENT = "ARTilik-OAIHarvester/1.3 (Academic Similarity Harvester; +https://tilik.arsetontong.top)"

_RE_XML_INVALID_CTRL = re.compile(r"[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]")
_RE_RESTRICTED_RIGHTS = re.compile(
    r"\b(closed[\s_-]*access|restricted[\s_-]*access|embargoed|embargo\s+until|not\s+openly\s+accessible|campus\s+access\s+only|internal\s+use\s+only|hanya\s+untuk\s+kalangan\s+internal|akses\s+terbatas|akses\s+tertutup)\b",
    re.IGNORECASE,
)


def clean_xml_tag(tag: str) -> str:
    """Menghilangkan namespace URI dari tag XML agar mudah diakses."""
    if "}" in tag:
        return tag.split("}", 1)[1]
    return tag


def _sanitize_xml_bytes(raw: bytes) -> str:
    text = raw.decode("utf-8", errors="ignore")
    return _RE_XML_INVALID_CTRL.sub("", text)


def _is_forbidden_ip(ip_str: str) -> bool:
    ip_obj = ipaddress.ip_address(ip_str)
    if getattr(ip_obj, "ipv4_mapped", None):
        ip_obj = ip_obj.ipv4_mapped
    return bool(
        ip_obj.is_private
        or ip_obj.is_loopback
        or ip_obj.is_link_local
        or ip_obj.is_reserved
        or ip_obj.is_multicast
        or ip_obj.is_unspecified
    )


def _validate_external_http_url(url: str, resolve_dns: bool = True) -> None:
    """Memvalidasi URL eksternal guna mencegah SSRF ke jaringan lokal/internal."""
    parsed = urllib.parse.urlparse(url)
    if parsed.scheme not in ("http", "https") or not parsed.hostname:
        raise ValueError(f"Skema atau hostname URL tidak diizinkan: {url}")
    if parsed.username or parsed.password:
        raise ValueError("URL dengan kredensial inline tidak diizinkan.")
    host = parsed.hostname.lower()
    if host in ("localhost", "localhost.localdomain", "127.0.0.1", "::1", "0.0.0.0"):
        raise ValueError(f"Akses ke host loopback ditolak: {host}")

    try:
        if _is_forbidden_ip(host):
            raise ValueError(f"Akses ke IP internal ditolak: {host}")
        return
    except ValueError as ve:
        if "ditolak" in str(ve):
            raise

    if resolve_dns:
        try:
            infos = socket.getaddrinfo(host, parsed.port or (443 if parsed.scheme == "https" else 80), type=socket.SOCK_STREAM)
        except OSError:
            # Biarkan urlopen menangani DNS error runtime apabila offline / mock
            return
        for info in infos:
            addr = info[4][0].split("%")[0]
            try:
                if _is_forbidden_ip(addr):
                    raise ValueError(f"DNS host '{host}' menunjuk ke IP internal yang ditolak: {addr}")
            except ValueError as ve:
                if "ditolak" in str(ve):
                    raise


class _SafeRedirectHandler(urllib.request.HTTPRedirectHandler):
    def redirect_request(self, req, fp, code, msg, headers, newurl):
        abs_url = urllib.parse.urljoin(req.full_url, newurl)
        _validate_external_http_url(abs_url, resolve_dns=True)
        return super().redirect_request(req, fp, code, msg, headers, newurl)


_SAFE_OPENER = urllib.request.build_opener(_SafeRedirectHandler())


def fetch_url_content(url: str, timeout: int = 25) -> Tuple[bytes, str]:
    """Mengambil konten dari URL via HTTP GET dengan timeout, anti-SSRF, dan user-agent standar."""
    _validate_external_http_url(url)
    req = urllib.request.Request(url, headers={"User-Agent": USER_AGENT})
    with _SAFE_OPENER.open(req, timeout=timeout) as resp:
        content = resp.read()
        content_type = resp.headers.get_content_type()
        return content, content_type


def download_file_stream(url: str, dest_path: Path, max_bytes: int = 30 * 1024 * 1024, timeout: int = 30) -> bool:
    """Mengunduh berkas dengan batasan ukuran, proteksi redirect SSRF, dan streaming."""
    try:
        _validate_external_http_url(url)
    except Exception:
        return False
    req = urllib.request.Request(url, headers={"User-Agent": USER_AGENT})
    try:
        with _SAFE_OPENER.open(req, timeout=timeout) as resp:
            if getattr(resp, "status", 200) >= 400:
                return False
            downloaded = 0
            with open(dest_path, "wb") as f:
                while chunk := resp.read(65536):
                    downloaded += len(chunk)
                    if downloaded > max_bytes:
                        f.close()
                        dest_path.unlink(missing_ok=True)
                        return False
                    f.write(chunk)
            return downloaded > 0
    except Exception:
        dest_path.unlink(missing_ok=True)
        return False


def _score_pdf_candidate_priority(url: str) -> Tuple[int, int]:
    """
    Skor prioritas kandidat PDF pada EPrints/DSpace (semakin kecil tuple = prioritas lebih tinggi).
    Menghindari mengunduh halaman cover saja bila fulltext/skripsi/bab tersedia.
    """
    low = url.lower()
    if any(k in low for k in ("cover", "sampul", "halaman_judul", "titlepage", "lembar_pengesahan")):
        return (3, len(url))
    if any(k in low for k in ("full", "skripsi", "thesis", "tesis", "disertasi", "artikel", "manuscript")):
        return (0, len(url))
    if "abstract" in low or "abstrak" in low:
        return (2, len(url))
    return (1, len(url))


def is_record_open_access(record: Dict[str, Any]) -> Tuple[bool, str]:
    """
    Menentukan apakah metadata oai_dc menunjukkan status Open Access.
    Mengembalikan (True, '') atau (False, alasan).
    """
    rights_text = " ".join(record.get("rights") or [])
    desc_text = str(record.get("description") or "")
    combined = f"{rights_text} {desc_text}"
    m = _RE_RESTRICTED_RIGHTS.search(combined)
    if m:
        return False, f"Ditandai akses tertutup/embargo ({m.group(0)})"
    return True, ""


def _oai_identifier_exists(connection, oai_identifier: str) -> Optional[int]:
    if not oai_identifier:
        return None
    cur = connection.cursor()
    cur.execute(
        "SELECT id FROM documents WHERE oai_identifier = %s LIMIT 1",
        (oai_identifier[:255],),
    )
    row = cur.fetchone()
    cur.close()
    return int(row[0]) if row else None


class OAIHarvester:
    """
    Kolektor protokol OAI-PMH 2.0 untuk memanen metadata dan naskah dari repositori kampus.
    """

    def __init__(self, endpoint_url: str, corpus_dir: Path = DEFAULT_CORPUS_STORAGE, repository_name: Optional[str] = None):
        self.endpoint_url = endpoint_url.strip()
        self.corpus_dir = corpus_dir
        self.corpus_dir.mkdir(parents=True, exist_ok=True)
        parsed = urllib.parse.urlparse(self.endpoint_url)
        self.repository_name = (repository_name or parsed.hostname or self.endpoint_url).strip()

    def identify(self) -> Dict[str, Any]:
        """Memeriksa status dan kapabilitas repositori OAI-PMH target (Verb: Identify)."""
        query = urllib.parse.urlencode({"verb": "Identify"})
        url = f"{self.endpoint_url}?{query}" if "?" not in self.endpoint_url else f"{self.endpoint_url}&{query}"

        content, _ = fetch_url_content(url)
        root = ET.fromstring(_sanitize_xml_bytes(content))

        info = {
            "status": "connected",
            "endpoint": self.endpoint_url,
            "repository_name": self.repository_name or "Unknown Repository",
            "protocol_version": "2.0",
            "admin_email": None,
        }

        for elem in root.iter():
            tag = clean_xml_tag(elem.tag)
            if tag == "repositoryName" and elem.text:
                info["repository_name"] = elem.text.strip()
                self.repository_name = info["repository_name"]
            elif tag == "protocolVersion" and elem.text:
                info["protocol_version"] = elem.text.strip()
            elif tag == "adminEmail" and elem.text:
                info["admin_email"] = elem.text.strip()

        return info

    def list_sets(self) -> List[Dict[str, str]]:
        """Mendaftar set/koleksi yang tersedia di repositori (Verb: ListSets)."""
        query = urllib.parse.urlencode({"verb": "ListSets"})
        url = f"{self.endpoint_url}?{query}" if "?" not in self.endpoint_url else f"{self.endpoint_url}&{query}"

        content, _ = fetch_url_content(url)
        root = ET.fromstring(_sanitize_xml_bytes(content))

        sets = []
        for set_elem in root.iter():
            if clean_xml_tag(set_elem.tag) == "set":
                spec, name = "", ""
                for child in set_elem:
                    ctag = clean_xml_tag(child.tag)
                    if ctag == "setSpec":
                        spec = (child.text or "").strip()
                    elif ctag == "setName":
                        name = (child.text or "").strip()
                if spec:
                    sets.append({"spec": spec, "name": name})

        return sets

    def _log_harvest_start(self, conn, query_or_set: str) -> Optional[int]:
        try:
            cur = conn.cursor()
            cur.execute(
                """
                INSERT INTO harvest_logs (
                    source, source_name, query_or_set, items_found, items_indexed,
                    items_skipped, items_failed, status, started_at, created_at
                ) VALUES ('oai_pmh', %s, %s, 0, 0, 0, 0, 'running', NOW(), NOW())
                """,
                (self.repository_name[:150], (query_or_set or self.endpoint_url)[:255]),
            )
            conn.commit()
            lid = int(cur.lastrowid)
            cur.close()
            return lid
        except Exception:
            return None

    def _log_harvest_finish(
        self,
        conn,
        log_id: Optional[int],
        summary: Dict[str, Any],
        duration_sec: float,
    ) -> None:
        if not log_id:
            return
        try:
            err = summary.get("error") or ""
            status = "failed" if err and summary["indexed_count"] == 0 else ("partial" if err or summary["failed_count"] > 0 else "success")
            msg = (
                f"OAI-PMH [{self.repository_name}] batch={summary.get('harvest_batch_id')}: "
                f"scanned={summary['total_harvested']}, indexed={summary['indexed_count']} "
                f"(PDF={summary['pdf_indexed_count']}, Abstract={summary['abstract_fallback_count']}), "
                f"skipped={summary['skipped_count']}, failed={summary['failed_count']}."
            )
            cur = conn.cursor()
            cur.execute(
                """
                UPDATE harvest_logs
                SET items_found = %s, items_indexed = %s, items_skipped = %s, items_failed = %s,
                    status = %s, summary = %s, error_message = %s, duration_seconds = %s, finished_at = NOW()
                WHERE id = %s
                """,
                (
                    summary["total_harvested"],
                    summary["indexed_count"],
                    summary["skipped_count"],
                    summary["failed_count"],
                    status,
                    msg[:1000],
                    err[:1000] if err else None,
                    round(duration_sec, 2),
                    log_id,
                ),
            )
            conn.commit()
            cur.close()
        except Exception:
            pass

    def harvest_records(
        self,
        set_spec: Optional[str] = None,
        from_date: Optional[str] = None,
        until_date: Optional[str] = None,
        limit: int = 20,
        download_fulltext: bool = True,
        open_access_only: bool = True,
        force_source_type: Optional[str] = None,
        request_delay: float = 0.25,
        harvest_batch_id: Optional[str] = None,
        log_harvest: bool = False,
    ) -> Dict[str, Any]:
        """
        Memanen data publikasi karya ilmiah menggunakan Verb ListRecords dan metadataPrefix=oai_dc.
        """
        t_start = time.perf_counter()
        batch_id = harvest_batch_id or f"oai_{datetime.now().strftime('%Y%m%d_%H%M%S')}"
        effective_limit = max(1, min(int(limit), 500))

        summary: Dict[str, Any] = {
            "endpoint": self.endpoint_url,
            "repository_name": self.repository_name,
            "set_spec": set_spec,
            "harvest_batch_id": batch_id,
            "requested_limit": effective_limit,
            "total_harvested": 0,
            "indexed_count": 0,
            "pdf_indexed_count": 0,
            "abstract_fallback_count": 0,
            "skipped_count": 0,
            "failed_count": 0,
            "filtered_closed_count": 0,
            "pages_fetched": 0,
            "duration_seconds": 0.0,
            "records": [],
        }

        resumption_token = None
        conn = get_connection()
        log_id = self._log_harvest_start(conn, set_spec or "") if log_harvest else None

        try:
            # Resolve repositoryName if default looks like raw host
            if "." in self.repository_name and " " not in self.repository_name:
                try:
                    self.identify()
                    summary["repository_name"] = self.repository_name
                except Exception:
                    pass

            while summary["total_harvested"] < effective_limit:
                params: Dict[str, str] = {"verb": "ListRecords"}
                if resumption_token:
                    params["resumptionToken"] = resumption_token
                else:
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
                    if summary["pages_fetched"] > 0 and request_delay > 0:
                        time.sleep(request_delay)
                    content, _ = fetch_url_content(url)
                    root = ET.fromstring(_sanitize_xml_bytes(content))
                    summary["pages_fetched"] += 1
                except Exception as req_err:
                    summary["error"] = f"Gagal menghubungi/mengurai OAI endpoint: {req_err}"
                    break

                # Tangani tag <error code="..."> resmi OAI-PMH
                oai_err = None
                for elem in root.iter():
                    if clean_xml_tag(elem.tag) == "error":
                        code = elem.attrib.get("code", "unknown")
                        oai_err = f"OAI error [{code}]: {(elem.text or '').strip()}"
                        break
                if oai_err:
                    if "noRecordsMatch" not in oai_err:
                        summary["error"] = oai_err
                    break

                page_records = self._parse_records_from_xml(root)
                if not page_records:
                    break

                for rec in page_records:
                    if summary["total_harvested"] >= effective_limit:
                        break

                    summary["total_harvested"] += 1
                    ingest_res = self._ingest_oai_record(
                        conn,
                        rec,
                        download_fulltext=download_fulltext,
                        open_access_only=open_access_only,
                        force_source_type=force_source_type,
                        set_spec=set_spec,
                        harvest_batch_id=batch_id,
                    )
                    summary["records"].append(ingest_res)

                    st = ingest_res.get("status")
                    if st == "indexed":
                        summary["indexed_count"] += 1
                        if ingest_res.get("format") == "application/pdf":
                            summary["pdf_indexed_count"] += 1
                        else:
                            summary["abstract_fallback_count"] += 1
                    elif st == "skipped":
                        summary["skipped_count"] += 1
                        if ingest_res.get("skip_category") == "non_open_access":
                            summary["filtered_closed_count"] += 1
                    else:
                        summary["failed_count"] += 1

                    if request_delay > 0 and download_fulltext:
                        time.sleep(min(request_delay, 0.25))

                resumption_token = None
                for elem in root.iter():
                    if clean_xml_tag(elem.tag) == "resumptionToken":
                        if elem.text and elem.text.strip():
                            resumption_token = elem.text.strip()
                        break

                if not resumption_token:
                    break

            if summary["indexed_count"] > 0:
                rebuild_fingerprint_stats(conn)

            summary["duration_seconds"] = round(time.perf_counter() - t_start, 2)
            self._log_harvest_finish(conn, log_id, summary, summary["duration_seconds"])
        finally:
            conn.close()

        return summary

    def _parse_records_from_xml(self, root: ET.Element) -> List[Dict[str, Any]]:
        """Mengekstrak data setiap record dari elemen XML ListRecords."""
        records = []
        for record_elem in root.iter():
            if clean_xml_tag(record_elem.tag) != "record":
                continue

            header = record_elem.find(".//{http://www.openarchives.org/OAI/2.0/}header")
            if header is None:
                for child in record_elem:
                    if clean_xml_tag(child.tag) == "header":
                        header = child
                        break

            if header is not None and header.attrib.get("status") == "deleted":
                continue

            oai_id = ""
            header_sets: List[str] = []
            if header is not None:
                for child in header:
                    ctag = clean_xml_tag(child.tag)
                    if ctag == "identifier":
                        oai_id = (child.text or "").strip()
                    elif ctag == "setSpec" and child.text:
                        header_sets.append(child.text.strip())

            metadata_elem = record_elem.find(".//{http://www.openarchives.org/OAI/2.0/}metadata")
            if metadata_elem is None:
                for child in record_elem:
                    if clean_xml_tag(child.tag) == "metadata":
                        metadata_elem = child
                        break

            if metadata_elem is None:
                continue

            title = ""
            creators: List[str] = []
            descriptions: List[str] = []
            dates: List[str] = []
            subjects: List[str] = []
            identifiers: List[str] = []
            relations: List[str] = []
            types: List[str] = []
            rights: List[str] = []

            for child in metadata_elem.iter():
                tag_name = clean_xml_tag(child.tag)
                val = (child.text or "").strip()
                if not val:
                    continue

                if tag_name == "title" and not title:
                    title = re.sub(r"\s+", " ", val).strip()
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
                elif tag_name == "relation":
                    relations.append(val)
                elif tag_name == "type":
                    types.append(val)
                elif tag_name == "rights":
                    rights.append(val)

            if not title:
                continue

            records.append({
                "oai_identifier": oai_id,
                "set_specs": header_sets,
                "title": title,
                "creators": creators,
                "description": "\n\n".join(descriptions),
                "date": dates[0] if dates else "",
                "subjects": subjects,
                "identifiers": identifiers,
                "relations": relations,
                "types": types,
                "rights": rights,
            })

        return records

    def _ingest_oai_record(
        self,
        connection,
        record: Dict[str, Any],
        download_fulltext: bool = True,
        open_access_only: bool = True,
        force_source_type: Optional[str] = None,
        set_spec: Optional[str] = None,
        harvest_batch_id: Optional[str] = None,
    ) -> Dict[str, Any]:
        """
        Menyimpan dan mengindeks record OAI ke dalam database korpus lengkap dengan provenance.
        """
        title = record["title"].strip()
        oai_id = (record.get("oai_identifier") or "").strip()
        unique_hash = hashlib.md5(f"{oai_id}_{title}".encode("utf-8")).hexdigest()[:12]

        if oai_id and _oai_identifier_exists(connection, oai_id):
            return {
                "status": "skipped",
                "title": title,
                "oai_id": oai_id,
                "reason": "OAI identifier sudah terindeks sebelumnya",
            }

        if open_access_only:
            oa_ok, oa_reason = is_record_open_access(record)
            if not oa_ok:
                return {
                    "status": "skipped",
                    "skip_category": "non_open_access",
                    "title": title,
                    "oai_id": oai_id,
                    "reason": oa_reason,
                }

        # Kumpulkan kandidat PDF & landing page (cek identifiers + relations)
        pdf_candidates: List[str] = []
        landing_url: Optional[str] = None
        for val in (record.get("identifiers", []) + record.get("relations", [])):
            v = val.strip()
            if v.startswith("http://") or v.startswith("https://"):
                low = v.lower()
                if low.endswith(".pdf") or "/download/" in low or "/bitstream/" in low:
                    if v not in pdf_candidates:
                        pdf_candidates.append(v)
                elif not landing_url:
                    landing_url = v

        pdf_candidates.sort(key=_score_pdf_candidate_priority)

        # Resolusi source_type
        source_type = force_source_type or "repository"
        if not force_source_type:
            combined_types = " ".join(
                (record.get("types") or [])
                + (record.get("subjects") or [])
                + (record.get("set_specs") or [])
                + ([set_spec] if set_spec else [])
            ).lower()
            if any(k in combined_types for k in ("thesis", "theses", "skripsi", "tesis", "disertasi", "74797065733d746865736973")):
                source_type = "thesis"
            elif any(k in combined_types for k in ("article", "journal", "jurnal")):
                source_type = "journal"

        saved_path: Optional[Path] = None
        mime_type = "text/plain"
        chosen_pdf_url: Optional[str] = None

        # 1. Coba unduh kandidat PDF Open Access (hingga 2 kandidat terbaik; pastikan valid %PDF- & punya teks >= 25 kata)
        if download_fulltext and pdf_candidates:
            for cand_idx, cand_url in enumerate(pdf_candidates[:3]):
                candidate_file = self.corpus_dir / f"oai_{unique_hash}_{cand_idx}.pdf"
                if download_file_stream(cand_url, candidate_file):
                    try:
                        with open(candidate_file, "rb") as test_f:
                            header = test_f.read(5)
                        if header == b"%PDF-":
                            pages = extract_document(str(candidate_file), "application/pdf")
                            extracted_words = sum(len((p.get("text") or "").split()) for p in (pages or []))
                            if extracted_words >= 25:
                                final_pdf = self.corpus_dir / f"oai_{unique_hash}.pdf"
                                if final_pdf != candidate_file:
                                    candidate_file.replace(final_pdf)
                                saved_path = final_pdf
                                mime_type = "application/pdf"
                                chosen_pdf_url = cand_url
                                break
                    except Exception:
                        pass
                candidate_file.unlink(missing_ok=True)

        # 2. Fallback ke teks metadata + abstrak bila PDF tidak terbuka/scanned, HANYA JIKA abstrak substantif >= 25 kata
        abstract_text = (record.get("description") or "").strip()
        if not saved_path:
            if open_access_only and len(abstract_text.split()) < 25:
                return {
                    "status": "skipped",
                    "skip_category": "non_open_access",
                    "title": title,
                    "oai_id": oai_id,
                    "reason": "Tidak ada PDF open-access terunduh dan abstrak terbuka < 25 kata",
                }

            saved_path = self.corpus_dir / f"oai_{unique_hash}.txt"
            creators_str = ", ".join(record["creators"]) if record["creators"] else "Anonim / Institusi"
            subjects_str = ", ".join(record["subjects"]) if record["subjects"] else "-"
            source_ref = chosen_pdf_url or (pdf_candidates[0] if pdf_candidates else None) or landing_url or oai_id

            structured_content = (
                f"JUDUL: {title}\n"
                f"PENULIS: {creators_str}\n"
                f"TAHUN / TANGGAL: {record['date']}\n"
                f"SUBJEK / KATA KUNCI: {subjects_str}\n"
                f"REPOSITORI ASAL: {self.repository_name}\n"
                f"IDENTIFIER OAI: {oai_id}\n"
                f"TAUTAN SUMBER: {source_ref}\n\n"
                f"ABSTRAK DAN DESKRIPSI KARYA ILMIAH:\n"
                f"{abstract_text or 'Tidak ada abstrak yang disediakan oleh repositori.'}\n"
            )
            with open(saved_path, "w", encoding="utf-8") as f:
                f.write(structured_content)
            mime_type = "text/plain"

        file_size = saved_path.stat().st_size
        filename = saved_path.name
        provenance_url = landing_url or chosen_pdf_url or (pdf_candidates[0] if pdf_candidates else None) or oai_id

        if is_already_indexed(connection, filename, file_size, title):
            saved_path.unlink(missing_ok=True)
            return {
                "status": "skipped",
                "title": title,
                "oai_id": oai_id,
                "reason": "Sudah terindeks di korpus",
            }

        author_str = ", ".join(record["creators"][:3]) if record.get("creators") else None
        if author_str and len(author_str) > 255:
            author_str = author_str[:252] + "..."

        cursor = connection.cursor()
        cursor.execute(
            """
            INSERT INTO documents (
                user_id, title, author, original_filename, mime_type, file_size,
                storage_path, status, document_type, corpus_visibility,
                source_type, oai_identifier, source_repository, source_url, harvest_batch_id,
                indexing_status, created_at, updated_at
            ) VALUES (
                NULL, %s, %s, %s, %s, %s,
                %s, 'uploaded', 'corpus', 'institution',
                %s, %s, %s, %s, %s,
                'processing', NOW(), NOW()
            )
            """,
            (
                title[:500],
                author_str,
                filename,
                mime_type,
                file_size,
                str(saved_path),
                source_type,
                oai_id[:255] if oai_id else None,
                self.repository_name[:255] if self.repository_name else None,
                provenance_url[:768] if provenance_url else None,
                harvest_batch_id[:64] if harvest_batch_id else None,
            ),
        )
        doc_id = cursor.lastrowid
        connection.commit()
        cursor.close()

        try:
            index_document(
                connection=connection,
                document_id=doc_id,
                storage_path=str(saved_path),
                mime_type=mime_type,
                is_corpus=True,
            )

            return {
                "status": "indexed",
                "document_id": doc_id,
                "title": title,
                "oai_id": oai_id,
                "source_repository": self.repository_name,
                "source_url": provenance_url,
                "format": mime_type,
                "source_type": source_type,
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
                "oai_id": oai_id,
                "error": str(err),
            }


if __name__ == "__main__":
    import argparse

    parser = argparse.ArgumentParser(description="AR Tilik OAI-PMH Repository Harvester")
    parser.add_argument("--url", required=True, help="URL endpoint OAI-PMH repositori kampus (contoh: https://eprints.undip.ac.id/cgi/oai2)")
    parser.add_argument("--set", dest="set_spec", default=None, help="Spesifikasi Set/Koleksi repositori (contoh: 74797065733D746865736973)")
    parser.add_argument("--limit", type=int, default=200, help="Batas maksimum record OAI yang dipindai (pilot default: 200)")
    parser.add_argument("--no-fulltext", dest="fulltext", action="store_false", help="Hanya panen metadata dan abstrak, lewati download PDF")
    parser.add_argument("--allow-closed-fallback", dest="oa_only", action="store_false", help="Izinkan fallback metadata singkat meskipun PDF tertutup")
    parser.add_argument("--source-type", dest="force_source_type", default=None, help="Paksa source_type (mis. thesis)")
    parser.add_argument("--repo-name", default=None, help="Label nama repositori untuk provenance")
    parser.add_argument("--delay", type=float, default=0.25, help="Jeda sopan (detik) antar permintaan HTTP")
    parser.add_argument("--log-harvest", action="store_true", help="Catat ringkasan ke tabel harvest_logs")
    parser.add_argument("--identify", action="store_true", help="Hanya periksa koneksi dan identitas repositori target")
    parser.add_argument("--list-sets", action="store_true", help="Tampilkan daftar koleksi/set yang tersedia")
    args = parser.parse_args()

    harvester = OAIHarvester(args.url, repository_name=args.repo_name)

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

    print(f"[*] Memulai panen OAI-PMH dari: {args.url}")
    if args.set_spec:
        print(f"[*] Filter Set : {args.set_spec}")
    print(f"[*] Target maks: {args.limit} record | Open-Access Only: {args.oa_only} | Full-Text PDF: {args.fulltext}")

    res = harvester.harvest_records(
        set_spec=args.set_spec,
        limit=args.limit,
        download_fulltext=args.fulltext,
        open_access_only=args.oa_only,
        force_source_type=args.force_source_type,
        request_delay=args.delay,
        log_harvest=args.log_harvest,
    )

    print("\n" + "=" * 60)
    print("        HASIL PANEN REPOSITORI OAI-PMH")
    print("=" * 60)
    print(f"Repositori          : {res.get('repository_name')}")
    print(f"Batch Provenance    : {res.get('harvest_batch_id')}")
    print(f"Total terpindai     : {res.get('total_harvested', 0)}")
    print(f"Berhasil diindeks   : {res.get('indexed_count', 0)} (PDF: {res.get('pdf_indexed_count', 0)}, Abstrak: {res.get('abstract_fallback_count', 0)})")
    print(f"Dilewati            : {res.get('skipped_count', 0)} (Non-OA/Minim Teks: {res.get('filtered_closed_count', 0)})")
    print(f"Gagal               : {res.get('failed_count', 0)}")
    print(f"Durasi              : {res.get('duration_seconds', 0.0)} detik")
    if res.get("error"):
        print(f"Peringatan OAI      : {res['error']}")
    print("=" * 60)
