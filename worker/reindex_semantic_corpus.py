#!/usr/bin/env python3
"""
AR Tilik - Semantic Corpus Backfill & Re-indexing Utility.
Mengindeks naskah korpus ke Qdrant (artilik_corpus_semantic) menggunakan
model FastEmbed MiniLM-L12 secara lokal dan efisien tanpa re-ekstraksi PDF.
"""

import os
import sys
import time
import argparse
from pathlib import Path

# Pastikan root proyek berada di PYTHONPATH
_parent_dir = str(Path(__file__).resolve().parents[1])
_script_dir = str(Path(__file__).resolve().parent)
if _script_dir in sys.path:
    sys.path.remove(_script_dir)
if _parent_dir not in sys.path:
    sys.path.insert(0, _parent_dir)

from worker.database import get_connection
from worker.semantic_lens import SemanticLens, is_semantic_engine_ready


def reindex_semantic_corpus(
    limit: int | None = None,
    batch_size: int = 50,
    dry_run: bool = False,
    single_doc_id: int | None = None
):
    print("=" * 60)
    print("   AR Tilik - Re-indexing Semantik Korpus (Qdrant Lens)   ")
    print("=" * 60)

    if not is_semantic_engine_ready():
        print("[!] PERINGATAN: Mesin semantik belum siap atau Qdrant offline.")
        if not dry_run:
            print("[X] Proses dibatalkan karena Qdrant tidak dapat dijangkau.")
            return

    lens = SemanticLens()
    conn = get_connection()
    cursor = conn.cursor(dictionary=True)

    if single_doc_id:
        sql = """
            SELECT id, title, document_type, indexing_status
            FROM documents
            WHERE id = %s AND document_type = 'corpus'
        """
        cursor.execute(sql, (single_doc_id,))
    else:
        sql = """
            SELECT id, title, document_type, indexing_status
            FROM documents
            WHERE document_type = 'corpus'
              AND indexing_status = 'completed'
            ORDER BY id ASC
        """
        if limit:
            sql += f" LIMIT {int(limit)}"
        cursor.execute(sql)

    corpus_docs = cursor.fetchall()
    total_docs = len(corpus_docs)
    cursor.close()

    print(f"[*] Ditemukan {total_docs} dokumen korpus berstatus completed.")
    if total_docs == 0:
        print("[✓] Tidak ada dokumen korpus yang perlu diproses.")
        conn.close()
        return

    start_time = time.time()
    success_count = 0
    skipped_count = 0
    total_chunks_indexed = 0

    page_cursor = conn.cursor(dictionary=True)

    for idx, doc in enumerate(corpus_docs, start=1):
        doc_id = doc["id"]
        title = doc["title"] or f"Doc #{doc_id}"

        # Ambil halaman langsung dari document_pages
        page_cursor.execute(
            "SELECT page_number, text FROM document_pages WHERE document_id = %s ORDER BY page_number ASC",
            (doc_id,)
        )
        pages = page_cursor.fetchall()

        if not pages or all(not (p.get("text") or "").strip() for p in pages):
            print(f"[{idx}/{total_docs}] [SKIP] Doc #{doc_id} ('{title[:35]}'): Teks halaman kosong.")
            skipped_count += 1
            continue

        if dry_run:
            word_count = sum(len(p.get("text", "").split()) for p in pages)
            print(f"[{idx}/{total_docs}] [DRY-RUN] Doc #{doc_id}: {len(pages)} hal, ~{word_count} kata.")
            success_count += 1
            continue

        try:
            t0 = time.time()
            indexed_count = lens.index_corpus_document(doc_id, pages)
            dur = time.time() - t0
            total_chunks_indexed += indexed_count
            success_count += 1
            if idx % 10 == 0 or idx == total_docs:
                elapsed = time.time() - start_time
                rate = success_count / elapsed if elapsed > 0 else 0
                remaining = (total_docs - idx) / rate if rate > 0 else 0
                print(f"[{idx}/{total_docs}] [OK] Doc #{doc_id} -> {indexed_count} potongan ({dur:.2f}s) | Laju: {rate:.1f} doc/s | ETA: {remaining:.1f}s")
        except Exception as e:
            print(f"[{idx}/{total_docs}] [FAIL] Doc #{doc_id} gagal diindeks: {e}")

    page_cursor.close()
    conn.close()

    total_time = time.time() - start_time
    print("=" * 60)
    print(f"[✓] Selesai: {success_count} dokumen sukses diindeks ({total_chunks_indexed} potongan semantik).")
    print(f"    Dilewati : {skipped_count} dokumen")
    print(f"    Waktu    : {total_time:.2f} detik")
    print("=" * 60)


if __name__ == "__main__":
    parser = argparse.ArgumentParser(description="AR Tilik Semantic Corpus Backfill")
    parser.add_argument("--limit", type=int, default=None, help="Batas maksimal dokumen")
    parser.add_argument("--dry-run", action="store_true", help="Jalankan simulasi tanpa upsert ke Qdrant")
    parser.add_argument("--doc-id", type=int, default=None, help="Hanya reindex satu dokumen ID spesifik")
    args = parser.parse_args()

    reindex_semantic_corpus(
        limit=args.limit,
        dry_run=args.dry_run,
        single_doc_id=args.doc_id
    )
