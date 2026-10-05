import pytest
import os
import shutil
import tempfile
import zipfile
import uuid
from pathlib import Path

from worker.corpus_harvester import (
    clean_title_from_filename,
    detect_source_type_from_metadata,
    detect_mime_type,
    get_file_sha256,
    unpack_zip_archive,
    enroll_submission_as_corpus,
    ingest_file
)
from worker.database import get_connection
from worker.indexer import index_document


def test_clean_title_from_filename():
    assert clean_title_from_filename("2024_Skripsi_Budi_Santoso_Sistem_Informasi.pdf") == "Skripsi Budi Santoso Sistem Informasi"
    assert clean_title_from_filename("01-jurnal-pendidikan-islam.docx") == "Jurnal Pendidikan Islam"
    assert clean_title_from_filename("tesis_analisis_hermeneutika.txt") == "Tesis Analisis Hermeneutika"


def test_detect_source_type_from_metadata():
    assert detect_source_type_from_metadata("skripsi_mahasiswa.pdf") == "thesis"
    assert detect_source_type_from_metadata("disertasi_doktoral.pdf") == "thesis"
    assert detect_source_type_from_metadata("jurnal_stt_arastamar.docx") == "journal"
    assert detect_source_type_from_metadata("prosiding_seminar_nasional.pdf") == "journal"
    assert detect_source_type_from_metadata("buku_ajar_eksegesis.pdf") == "book"
    assert detect_source_type_from_metadata("dokumen_acak.pdf") == "thesis"  # default to thesis


def test_detect_mime_type():
    assert detect_mime_type(".pdf") == "application/pdf"
    assert detect_mime_type(".docx") == "application/vnd.openxmlformats-officedocument.wordprocessingml.document"
    assert detect_mime_type(".txt") == "text/plain"


def test_unpack_zip_archive():
    with tempfile.TemporaryDirectory() as tmp_dir:
        tmp_path = Path(tmp_dir)
        zip_file = tmp_path / "bundle.zip"
        
        # Buat ZIP berisi berkas dummy
        with zipfile.ZipFile(zip_file, "w") as zf:
            zf.writestr("skripsi_1.txt", "Teks bab satu skripsi mahasiswa...")
            zf.writestr("jurnal_2.txt", "Teks abstrak jurnal penelitian...")
            zf.writestr("subfolder/tesis_3.txt", "Teks bab metodologi penelitian...")
            zf.writestr("gambar.png", "binary_data")  # Bukan format yang didukung
            
        extract_dir = tmp_path / "extracted"
        extracted = unpack_zip_archive(zip_file, extract_dir)
        
        # Harus hanya mengekstrak berkas teks/naskah (3 file), lewati png
        assert len(extracted) == 3
        extracted_names = {f.name for f in extracted}
        assert "skripsi_1.txt" in extracted_names
        assert "jurnal_2.txt" in extracted_names
        assert "tesis_3.txt" in extracted_names


def test_ingest_and_enroll_corpus_flow():
    """
    Uji end-to-end ingestion berkas ke korpus dan promosi submission menjadi korpus institusi.
    """
    conn = get_connection()
    created_doc_ids = []
    try:
        with tempfile.TemporaryDirectory() as tmp_dir:
            tmp_path = Path(tmp_dir)
            corpus_storage = tmp_path / "corpus_storage"
            archive_dir = tmp_path / "archive_dir"
            
            uid = uuid.uuid4().hex[:8]
            sample_filename = f"Skripsi_Uji_Harvester_{uid}.txt"
            sample_file = tmp_path / sample_filename
            sample_file.write_text(
                "Penelitian ini menganalisis implementasi kurikulum merdeka pada sekolah dasar di daerah perkotaan. "
                "Metodologi yang digunakan adalah pendekatan kualitatif deskriptif dengan pengumpulan data wawancara. "
                "Hasil analisis menunjukkan bahwa kesiapan guru memiliki korelasi signifikan terhadap efektivitas pembelajaran.",
                encoding="utf-8"
            )
            
            res = ingest_file(
                connection=conn,
                file_path=sample_file,
                corpus_dir=corpus_storage,
                archive_dir=archive_dir
            )
            
            assert res["status"] == "success"
            doc_id = res["document_id"]
            assert doc_id > 0
            created_doc_ids.append(doc_id)
            
            # Verifikasi di database
            cursor = conn.cursor(dictionary=True)
            cursor.execute("SELECT * FROM documents WHERE id = %s", (doc_id,))
            doc = cursor.fetchone()
            assert doc["document_type"] == "corpus"
            assert doc["status"] == "completed"
            assert doc["indexing_status"] == "completed"
            
            # Cek fingerprints terdaftar
            cursor.execute("SELECT COUNT(*) AS c FROM fingerprint_documents WHERE document_id = %s", (doc_id,))
            fd_count = cursor.fetchone()["c"]
            assert fd_count > 0
            cursor.close()
            
            # 2. Cek deteksi duplikasi jika berkas yang sama di-ingest ulang
            res_dup = ingest_file(
                connection=conn,
                file_path=archive_dir / sample_filename,
                corpus_dir=corpus_storage
            )
            assert res_dup["status"] == "skipped"
            
            # 3. Uji Self-Growing Enrollment: Buat submission baru, lalu daftarkan ke korpus
            cursor = conn.cursor()
            cursor.execute("""
                INSERT INTO documents (
                    title, original_filename, mime_type, file_size,
                    storage_path, status, document_type, corpus_visibility,
                    source_type, indexing_status, created_at, updated_at
                ) VALUES (
                    %s, %s, 'text/plain', 100,
                    %s, 'completed', 'submission', 'private',
                    'student', 'completed', NOW(), NOW()
                )
            """, (f"Submission Mahasiswa {uid}", f"sub_{uid}.txt", str(archive_dir / sample_filename)))
            sub_id = cursor.lastrowid
            conn.commit()
            cursor.close()
            created_doc_ids.append(sub_id)
            
            # Indeks sebagai submission biasa
            index_document(conn, sub_id, str(archive_dir / sample_filename), is_corpus=False)
            
            # Promosikan ke korpus institusi
            enroll_res = enroll_submission_as_corpus(conn, sub_id)
            assert enroll_res["status"] == "success"
            
            # Verifikasi setelah dipromosikan
            cursor = conn.cursor(dictionary=True)
            cursor.execute("SELECT * FROM documents WHERE id = %s", (sub_id,))
            sub_doc = cursor.fetchone()
            assert sub_doc["document_type"] == "corpus"
            assert sub_doc["corpus_visibility"] == "institution"
            
            cursor.execute("SELECT COUNT(*) AS c FROM fingerprint_documents WHERE document_id = %s", (sub_id,))
            sub_fd_count = cursor.fetchone()["c"]
            assert sub_fd_count > 0
            cursor.close()
            
    finally:
        cursor = conn.cursor()
        for did in created_doc_ids:
            cursor.execute("DELETE FROM fingerprint_documents WHERE document_id = %s", (did,))
            cursor.execute("DELETE FROM fingerprints WHERE document_id = %s", (did,))
            cursor.execute("DELETE FROM document_chunks WHERE document_id = %s", (did,))
            cursor.execute("DELETE FROM document_pages WHERE document_id = %s", (did,))
            cursor.execute("DELETE FROM documents WHERE id = %s", (did,))
        conn.commit()
        cursor.close()
        conn.close()
