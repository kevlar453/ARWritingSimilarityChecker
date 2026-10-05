import pytest
import tempfile
import uuid
from pathlib import Path

from worker.auto_discovery_harvester import (
    reconstruct_abstract,
    ingest_scholarly_work,
    BUILTIN_INDONESIAN_REPOSITORIES
)
from worker.database import get_connection


def test_reconstruct_abstract():
    inverted_index = {
        "Penelitian": [0],
        "ini": [1],
        "mengkaji": [2],
        "pendidikan": [3],
        "karakter": [4],
        "secara": [5],
        "komprehensif.": [6]
    }
    result = reconstruct_abstract(inverted_index)
    assert result == "Penelitian ini mengkaji pendidikan karakter secara komprehensif."

    assert reconstruct_abstract(None) == ""
    assert reconstruct_abstract({}) == ""


def test_builtin_indonesian_repositories_registry():
    assert len(BUILTIN_INDONESIAN_REPOSITORIES) >= 5
    for repo in BUILTIN_INDONESIAN_REPOSITORIES:
        assert "name" in repo
        assert "url" in repo
        assert repo["url"].startswith("http://") or repo["url"].startswith("https://")


def test_ingest_scholarly_work_flow():
    conn = get_connection()
    created_doc_ids = []
    try:
        with tempfile.TemporaryDirectory() as tmp_dir:
            tmp_path = Path(tmp_dir)
            corpus_storage = tmp_path / "corpus_storage"

            uid = uuid.uuid4().hex[:8]
            sample_work = {
                "title": f"Studi Hermeneutika Teks Naratif Kuno {uid}",
                "authors": ["Markus Jonathan", "Hana Priscilla"],
                "year": 2026,
                "doi": f"https://doi.org/10.1234/stt.{uid}",
                "pdf_url": None,
                "landing_url": "https://journal.example.ac.id/article/1",
                "source_name": "Jurnal Teologi dan Hermeneutika",
                "abstract": (
                    "Artikel ini membedah metode interpretasi teks kuno berbasis analisis sintaksis dan sosio-historis. "
                    "Hasil kajian menunjukkan bahwa pemahaman konteks primordial sangat krusial dalam menentukan makna orisinal."
                )
            }

            res = ingest_scholarly_work(conn, sample_work, corpus_dir=corpus_storage)
            assert res["status"] == "indexed"
            doc_id = res["document_id"]
            assert doc_id > 0
            created_doc_ids.append(doc_id)

            # Verifikasi di tabel documents
            cursor = conn.cursor(dictionary=True)
            cursor.execute("SELECT * FROM documents WHERE id = %s", (doc_id,))
            doc = cursor.fetchone()
            assert doc["document_type"] == "corpus"
            assert doc["status"] == "completed"
            assert doc["indexing_status"] == "completed"
            assert doc["source_type"] == "journal"

            # Verifikasi fingerprints
            cursor.execute("SELECT COUNT(*) AS c FROM fingerprint_documents WHERE document_id = %s", (doc_id,))
            fd_count = cursor.fetchone()["c"]
            assert fd_count > 0
            cursor.close()

            # Verifikasi deduplikasi jika di-ingest ulang
            res_dup = ingest_scholarly_work(conn, sample_work, corpus_dir=corpus_storage)
            assert res_dup["status"] == "skipped"

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
