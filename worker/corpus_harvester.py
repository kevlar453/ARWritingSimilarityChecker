"""
AR Tilik - Corpus Bulk Harvester & Dropzone Ingestion Engine
Modul otomasi penambahan dan pengindeksan korpus karya ilmiah dari direktori dropzone,
berkas arsip ZIP, maupun direktori lokal kampus.
"""

import os
import sys
import shutil
import hashlib
import zipfile
import re
from pathlib import Path
from typing import Optional, List, Dict, Any

# Ensure project root is prioritized on sys.path and remove script dir to avoid package shadowing
PROJECT_ROOT = Path(__file__).resolve().parents[1]
script_dir = str(Path(__file__).resolve().parent)
if script_dir in sys.path:
    sys.path.remove(script_dir)
if str(PROJECT_ROOT) not in sys.path:
    sys.path.insert(0, str(PROJECT_ROOT))

from worker.database import get_connection, load_env
from worker.indexer import index_document, rebuild_fingerprint_stats

load_env()
DEFAULT_DROPZONE_DIR = PROJECT_ROOT / "storage" / "corpus_dropzone"
DEFAULT_ARCHIVE_DIR = PROJECT_ROOT / "storage" / "corpus_archive"
DEFAULT_CORPUS_STORAGE = PROJECT_ROOT / "writable" / "documents" / "corpus"

SUPPORTED_EXTENSIONS = {".pdf", ".docx", ".txt"}


def get_file_sha256(path: Path) -> str:
    """Menghitung SHA-256 hash dari suatu berkas."""
    hasher = hashlib.sha256()
    with open(path, "rb") as f:
        while chunk := f.read(65536):
            hasher.update(chunk)
    return hasher.hexdigest()


def detect_source_type_from_metadata(filename: str, sample_text: str = "") -> str:
    """
    Heuristik cerdas mendeteksi tipe sumber karya ilmiah:
    - thesis: Skripsi, Tesis, Disertasi, Tugas Akhir
    - journal: Artikel Jurnal, Prosiding Seminar
    - book: Buku, Monograf, Modul Ajar
    - repository: Repositori Kampus Umum
    - other: Fallback
    """
    # Ganti underscore dan hyphen dengan spasi agar regex word boundary berfungsi sempurna
    raw = filename + " " + sample_text[:1000]
    combined = re.sub(r"[_\-]+", " ", raw).lower()

    if re.search(r"\b(skripsi|tesis|thesis|disertasi|dissertation|tugas akhir)\b", combined):
        return "thesis"
    if re.search(r"\b(jurnal|journal|artikel|article|prosiding|proceeding|konferensi|conference)\b", combined):
        return "journal"
    if re.search(r"\b(buku|book|monograf|monograph|modul|diktat|penerbit|isbn)\b", combined):
        return "book"
    if re.search(r"\b(repository|eprints|dspace|perpustakaan|pustaka)\b", combined):
        return "repository"
    return "thesis"  # Default primer di lingkungan perguruan tinggi


def clean_title_from_filename(filename: str) -> str:
    """
    Membersihkan nama file menjadi judul naskah yang rapi dan mudah dibaca.
    Contoh: '2024_Skripsi_Budi_Santoso_Sistem_Informasi.pdf' -> 'Skripsi Budi Santoso Sistem Informasi'
    """
    stem = Path(filename).stem
    # Hapus prefix angka/tanggal seperti '12345_', '2024-', '01.'
    stem = re.sub(r"^[\d\-_.]+", "", stem)
    # Ganti underscore dan hyphen menjadi spasi
    stem = re.sub(r"[_\-]+", " ", stem)
    # Rapikan spasi berlebih
    stem = re.sub(r"\s+", " ", stem).strip()
    return stem.title() if stem else Path(filename).stem


def detect_mime_type(ext: str) -> str:
    ext_lower = ext.lower()
    if ext_lower == ".pdf":
        return "application/pdf"
    elif ext_lower == ".docx":
        return "application/vnd.openxmlformats-officedocument.wordprocessingml.document"
    elif ext_lower == ".txt":
        return "text/plain"
    return "application/octet-stream"


def is_already_indexed(connection, filename: str, file_size: int, title: str) -> bool:
    """
    Mengecek apakah berkas sudah terindeks di korpus untuk mencegah duplikasi.
    """
    cursor = connection.cursor(dictionary=True)
    cursor.execute("""
        SELECT id FROM documents
        WHERE document_type = 'corpus'
          AND (
              (original_filename = %s AND file_size = %s)
              OR title = %s
          )
        LIMIT 1
    """, (filename, file_size, title))
    exists = cursor.fetchone()
    cursor.close()
    return exists is not None


def enroll_submission_as_corpus(connection, document_id: int) -> dict:
    """
    Mempromosikan naskah submission yang telah diverifikasi menjadi korpus institusi permanen
    (Self-Growing Repository feature).
    """
    cursor = connection.cursor(dictionary=True)
    cursor.execute("SELECT * FROM documents WHERE id = %s", (document_id,))
    doc = cursor.fetchone()

    if not doc:
        cursor.close()
        return {"status": "error", "message": f"Dokumen #{document_id} tidak ditemukan."}

    if doc["document_type"] == "corpus":
        cursor.close()
        return {
            "status": "already_enrolled",
            "document_id": document_id,
            "message": f"Dokumen #{document_id} ('{doc['title']}') sudah berstatus korpus institusi."
        }

    # 1. Daftarkan fingerprints unik submission ke fingerprint_documents
    cursor.execute("""
        INSERT IGNORE INTO fingerprint_documents (fingerprint, document_id, created_at)
        SELECT DISTINCT fingerprint, %s, NOW()
        FROM fingerprints
        WHERE document_id = %s
    """, (document_id, document_id))

    # 2. Perbarui status dokumen
    cursor.execute("""
        UPDATE documents
        SET document_type = 'corpus',
            corpus_visibility = 'institution',
            indexing_status = 'completed',
            updated_at = NOW()
        WHERE id = %s
    """, (document_id,))
    connection.commit()
    cursor.close()

    # 3. Perbarui statistik IDF seluruh korpus
    rebuild_fingerprint_stats(connection)

    return {
        "status": "success",
        "document_id": document_id,
        "title": doc["title"],
        "message": f"Dokumen #{document_id} ('{doc['title']}') berhasil didaftarkan sebagai korpus institusi."
    }


def unpack_zip_archive(zip_path: Path, extract_dir: Path) -> List[Path]:
    """
    Mengekstrak berkas ZIP secara aman dan mengembalikan daftar path berkas yang didukung.
    """
    extracted_files = []
    extract_dir.mkdir(parents=True, exist_ok=True)

    with zipfile.ZipFile(zip_path, "r") as archive:
        for member in archive.infolist():
            # Mencegah Directory Traversal (Zip Slip)
            filename = os.path.basename(member.filename)
            if not filename or member.is_dir():
                continue

            ext = os.path.splitext(filename)[1].lower()
            if ext in SUPPORTED_EXTENSIONS:
                target_path = extract_dir / filename
                with archive.open(member) as source, open(target_path, "wb") as target:
                    shutil.copyfileobj(source, target)
                extracted_files.append(target_path)

    return extracted_files


def ingest_file(
    connection,
    file_path: Path,
    corpus_dir: Path = DEFAULT_CORPUS_STORAGE,
    archive_dir: Optional[Path] = None,
    source_type: Optional[str] = None,
    visibility: str = "institution"
) -> Dict[str, Any]:
    """
    Mengimpor satu berkas ke dalam korpus AR Tilik dan langsung mengindeks shingle-nya.
    """
    if not file_path.exists() or not file_path.is_file():
        return {"status": "error", "message": f"Berkas tidak ditemukan: {file_path}"}

    ext = file_path.suffix.lower()
    if ext not in SUPPORTED_EXTENSIONS:
        return {"status": "ignored", "message": f"Format tidak didukung: {ext}"}

    file_size = file_path.stat().st_size
    if file_size == 0:
        return {"status": "error", "message": f"Berkas kosong (0 byte): {file_path.name}"}

    title = clean_title_from_filename(file_path.name)
    mime_type = detect_mime_type(ext)

    # Cek duplikasi
    if is_already_indexed(connection, file_path.name, file_size, title):
        # Jika diminta diarsip, tetap pindahkan agar dropzone bersih
        if archive_dir:
            archive_dir.mkdir(parents=True, exist_ok=True)
            dest_archive = archive_dir / file_path.name
            if dest_archive.exists():
                dest_archive = archive_dir / f"{dest_archive.stem}_{sha_short}{dest_archive.suffix}"
            shutil.move(str(file_path), str(dest_archive))
        return {
            "status": "skipped",
            "title": title,
            "filename": file_path.name,
            "message": "Dokumen dengan nama atau judul ini sudah terindeks di korpus."
        }

    # Tentukan path penyimpanan permanen
    corpus_dir.mkdir(parents=True, exist_ok=True)
    clean_stem = re.sub(r"[^\w\-.]", "_", file_path.stem)[:40]
    sha_short = get_file_sha256(file_path)[:10]
    stored_filename = f"corpus_{clean_stem}_{sha_short}{ext}"
    dest_path = corpus_dir / stored_filename

    # Salin berkas fisik ke direktori korpus
    shutil.copy2(str(file_path), str(dest_path))

    detected_source = source_type or detect_source_type_from_metadata(file_path.name)

    # Daftarkan ke tabel documents
    cursor = connection.cursor()
    cursor.execute("""
        INSERT INTO documents (
            user_id, title, original_filename, mime_type, file_size,
            storage_path, status, document_type, corpus_visibility,
            source_type, indexing_status, created_at, updated_at
        ) VALUES (
            NULL, %s, %s, %s, %s,
            %s, 'uploaded', 'corpus', %s,
            %s, 'processing', NOW(), NOW()
        )
    """, (
        title,
        file_path.name,
        mime_type,
        file_size,
        str(dest_path),
        visibility,
        detected_source
    ))
    doc_id = cursor.lastrowid
    connection.commit()
    cursor.close()

    try:
        # Indeks shingle, chunk, halaman, dan perbarui DF/IDF
        index_document(
            connection=connection,
            document_id=doc_id,
            storage_path=str(dest_path),
            mime_type=mime_type,
            is_corpus=True
        )

        # Pindahkan berkas sumber ke arsip jika dropzone aktif
        if archive_dir:
            archive_dir.mkdir(parents=True, exist_ok=True)
            dest_archive = archive_dir / file_path.name
            # Handle collision di direktori arsip
            if dest_archive.exists():
                dest_archive = archive_dir / f"{dest_archive.stem}_{sha_short}{dest_archive.suffix}"
            shutil.move(str(file_path), str(dest_archive))

        return {
            "status": "success",
            "document_id": doc_id,
            "title": title,
            "filename": file_path.name,
            "source_type": detected_source,
            "size_bytes": file_size,
            "message": f"Berhasil mengindeks korpus #{doc_id}: '{title}'"
        }

    except Exception as err:
        # Tandai gagal jika parsing korpus bermasalah
        cursor = connection.cursor()
        cursor.execute("UPDATE documents SET status = 'failed', indexing_status = 'failed' WHERE id = %s", (doc_id,))
        connection.commit()
        cursor.close()
        return {
            "status": "error",
            "document_id": doc_id,
            "title": title,
            "filename": file_path.name,
            "message": f"Gagal mengindeks: {err}"
        }


def harvest_dropzone(
    dropzone_dir: Path = DEFAULT_DROPZONE_DIR,
    archive_dir: Path = DEFAULT_ARCHIVE_DIR,
    corpus_dir: Path = DEFAULT_CORPUS_STORAGE,
    recursive: bool = True
) -> Dict[str, Any]:
    """
    Memindai dan memproses seluruh berkas yang ada di direktori dropzone.
    Mendukung pemrosesan otomatis file ZIP yang diekstrak.
    """
    dropzone_dir.mkdir(parents=True, exist_ok=True)
    archive_dir.mkdir(parents=True, exist_ok=True)
    corpus_dir.mkdir(parents=True, exist_ok=True)

    summary = {
        "dropzone_path": str(dropzone_dir),
        "total_scanned": 0,
        "indexed": 0,
        "skipped": 0,
        "failed": 0,
        "processed_items": []
    }

    # Temukan semua file di dropzone
    search_pattern = "**/*" if recursive else "*"
    all_entries = [p for p in dropzone_dir.glob(search_pattern) if p.is_file()]

    # Pisahkan ZIP dan berkas dokumen langsung
    files_to_process: List[Path] = []
    temp_unpacks: List[Path] = []

    for entry in all_entries:
        if entry.suffix.lower() == ".zip":
            # Ekstrak ZIP ke folder temporer dalam dropzone
            unpack_dest = dropzone_dir / f"_extracted_{entry.stem}_{os.getpid()}"
            extracted = unpack_zip_archive(entry, unpack_dest)
            files_to_process.extend(extracted)
            temp_unpacks.append(unpack_dest)
            # Pindahkan ZIP asal ke arsip
            dest_zip_archive = archive_dir / entry.name
            shutil.move(str(entry), str(dest_zip_archive))
        elif entry.suffix.lower() in SUPPORTED_EXTENSIONS:
            # Lewati berkas temporer internal
            if not entry.name.startswith("."):
                files_to_process.append(entry)

    summary["total_scanned"] = len(files_to_process)
    if not files_to_process:
        # Bersihkan folder temporer jika ada
        for td in temp_unpacks:
            shutil.rmtree(td, ignore_errors=True)
        return summary

    conn = get_connection()
    try:
        for file_item in files_to_process:
            res = ingest_file(
                connection=conn,
                file_path=file_item,
                corpus_dir=corpus_dir,
                archive_dir=archive_dir
            )
            summary["processed_items"].append(res)
            if res["status"] == "success":
                summary["indexed"] += 1
            elif res["status"] == "skipped":
                summary["skipped"] += 1
            else:
                summary["failed"] += 1

        # Rebuild stats sekali lagi di akhir untuk memastikan akurasi menyeluruh
        if summary["indexed"] > 0:
            rebuild_fingerprint_stats(conn)

    finally:
        conn.close()
        # Bersihkan direktori temporary ekstraksi ZIP
        for td in temp_unpacks:
            shutil.rmtree(td, ignore_errors=True)

    return summary


def import_custom_path(
    target_path: Path,
    corpus_dir: Path = DEFAULT_CORPUS_STORAGE,
    archive_dir: Optional[Path] = None,
    recursive: bool = True
) -> Dict[str, Any]:
    """
    Mengimpor berkas atau direktori kustom yang ditentukan oleh pengguna/admin.
    """
    if not target_path.exists():
        return {"status": "error", "message": f"Path tidak ditemukan: {target_path}"}

    if target_path.is_file():
        conn = get_connection()
        try:
            res = ingest_file(
                connection=conn,
                file_path=target_path,
                corpus_dir=corpus_dir,
                archive_dir=archive_dir
            )
            return {
                "total_scanned": 1,
                "indexed": 1 if res["status"] == "success" else 0,
                "skipped": 1 if res["status"] == "skipped" else 0,
                "failed": 1 if res["status"] in ("error", "failed") else 0,
                "processed_items": [res]
            }
        finally:
            conn.close()
    else:
        # Direktori kustom
        conn = get_connection()
        summary = {
            "target_path": str(target_path),
            "total_scanned": 0,
            "indexed": 0,
            "skipped": 0,
            "failed": 0,
            "processed_items": []
        }
        pattern = "**/*" if recursive else "*"
        candidates = [p for p in target_path.glob(pattern) if p.is_file() and p.suffix.lower() in SUPPORTED_EXTENSIONS]
        summary["total_scanned"] = len(candidates)

        try:
            for item in candidates:
                res = ingest_file(
                    connection=conn,
                    file_path=item,
                    corpus_dir=corpus_dir,
                    archive_dir=archive_dir
                )
                summary["processed_items"].append(res)
                if res["status"] == "success":
                    summary["indexed"] += 1
                elif res["status"] == "skipped":
                    summary["skipped"] += 1
                else:
                    summary["failed"] += 1

            if summary["indexed"] > 0:
                rebuild_fingerprint_stats(conn)
        finally:
            conn.close()

        return summary


if __name__ == "__main__":
    import argparse
    parser = argparse.ArgumentParser(description="AR Tilik Bulk Corpus Harvester & Dropzone Engine")
    parser.add_argument("path", nargs="?", default=None, help="Path ke file atau direktori yang akan diimpor")
    parser.add_argument("--dropzone", action="store_true", help="Proses berkas dalam folder default dropzone")
    parser.add_argument("--archive", action="store_true", default=True, help="Pindahkan berkas yang selesai ke arsip")
    parser.add_argument("--no-archive", dest="archive", action="store_false", help="Biarkan berkas di tempat asalnya")
    parser.add_argument("--enroll", type=int, help="Promosikan submission ID tertentu menjadi korpus institusi")
    args = parser.parse_args()

    if args.enroll:
        print(f"[*] Memproses pendaftaran submission #{args.enroll} ke korpus institusi...")
        connection = get_connection()
        try:
            result = enroll_submission_as_corpus(connection, args.enroll)
            print(f"[{'✓' if result['status'] == 'success' else '!'}] {result['message']}")
        finally:
            connection.close()
        sys.exit(0)

    if args.dropzone or not args.path:
        print(f"[*] Memindai direktori dropzone: {DEFAULT_DROPZONE_DIR}")
        res = harvest_dropzone(
            dropzone_dir=DEFAULT_DROPZONE_DIR,
            archive_dir=DEFAULT_ARCHIVE_DIR if args.archive else None
        )
    else:
        target = Path(args.path)
        print(f"[*] Mengimpor dari path kustom: {target}")
        res = import_custom_path(
            target_path=target,
            archive_dir=DEFAULT_ARCHIVE_DIR if args.archive else None
        )

    print("\n" + "=" * 50)
    print("      RINGKASAN PROSES HARVESTING KORPUS          ")
    print("=" * 50)
    print(f"Total berkas terpindai : {res.get('total_scanned', 0)}")
    print(f"Berhasil diindeks     : {res.get('indexed', 0)}")
    print(f"Dilewati (duplikat)   : {res.get('skipped', 0)}")
    print(f"Gagal                 : {res.get('failed', 0)}")
    print("=" * 50)
    for item in res.get("processed_items", []):
        icon = "✓" if item["status"] == "success" else ("-" if item["status"] == "skipped" else "✗")
        print(f" [{icon}] {item.get('title', item.get('filename'))}: {item.get('message', '')}")
