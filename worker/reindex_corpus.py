#!/usr/bin/env python3
"""
Skrip Re-indexing Korpus AR Tilik ke Engine v1.2.
Memproses ulang seluruh dokumen korpus dengan tokenisasi sadar Unicode,
deobfuskasi homoglif, dan shingling berkesinambungan lintas halaman.
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
from worker.indexer import index_document, rebuild_fingerprint_stats

def reindex_all_corpus(limit: int | None = None, dry_run: bool = False):
    print("==================================================")
    print("   AR Tilik - Re-indexing Korpus ke Engine v1.2   ")
    print("==================================================")
    t0 = time.time()
    
    conn = get_connection()
    cursor = conn.cursor(dictionary=True)
    
    sql = """
        SELECT id, title, storage_path, mime_type, original_filename, indexing_status
        FROM documents
        WHERE document_type = 'corpus'
          AND (benchmark_run_id IS NULL OR benchmark_run_id = 0)
        ORDER BY id ASC
    """
    if limit:
        sql += f" LIMIT {int(limit)}"
        
    cursor.execute(sql)
    corpus_docs = cursor.fetchall()
    cursor.close()
    
    total = len(corpus_docs)
    print(f"[*] Ditemukan {total} dokumen korpus untuk diproses.")
    
    if total == 0:
        print("[✓] Tidak ada dokumen korpus yang perlu diproses.")
        conn.close()
        return

    success_count = 0
    skipped_count = 0
    failed_count = 0
    
    for idx, doc in enumerate(corpus_docs, start=1):
        doc_id = doc["id"]
        title = doc["title"] or doc["original_filename"] or f"Doc #{doc_id}"
        path = doc["storage_path"]
        
        # Tangani path relatif writable jika diperlukan
        if path and not os.path.exists(path):
            alt_path = os.path.join(_parent_dir, "writable", path.split("writable/")[-1])
            if os.path.exists(alt_path):
                path = alt_path
                
        if not path or not os.path.exists(path):
            print(f"[{idx}/{total}] [SKIP] Doc #{doc_id} ('{title[:40]}'): File fisik tidak ditemukan ({path})")
            skipped_count += 1
            continue
            
        if dry_run:
            print(f"[{idx}/{total}] [DRY-RUN] Doc #{doc_id} ('{title[:40]}'): Siap diindeks.")
            success_count += 1
            continue
            
        try:
            t_doc = time.time()
            index_document(
                connection=conn,
                document_id=doc_id,
                storage_path=path,
                mime_type=doc.get("mime_type"),
                is_corpus=True,
                rebuild_stats=False # Tunda kalkulasi global hingga seluruh batch selesai
            )
            duration = time.time() - t_doc
            print(f"[{idx}/{total}] [OK] Doc #{doc_id} ('{title[:40]}') selesai ({duration:.2f}s)")
            success_count += 1
        except Exception as e:
            print(f"[{idx}/{total}] [FAIL] Doc #{doc_id} ('{title[:40]}') gagal: {e}")
            failed_count += 1

    if not dry_run and success_count > 0:
        print("\n[*] Menghitung ulang fingerprint_stats korpus secara batch...")
        t_stats = time.time()
        rebuild_fingerprint_stats(conn)
        print(f"[✓] fingerprint_stats berhasil diperbarui ({time.time() - t_stats:.2f}s).")

    conn.close()
    total_duration = time.time() - t0
    print("\n==================================================")
    print(f"  Re-indexing Selesai dalam {total_duration:.2f} detik")
    print(f"  Sukses: {success_count} | Dilewati: {skipped_count} | Gagal: {failed_count}")
    print("==================================================")

if __name__ == "__main__":
    parser = argparse.ArgumentParser(description="Re-index Korpus AR Tilik v1.2")
    parser.add_argument("--limit", type=int, default=None, help="Batasi jumlah dokumen yang diproses")
    parser.add_argument("--dry-run", action="store_true", help="Uji tanpa menulis ke basis data")
    args = parser.parse_args()
    
    reindex_all_corpus(limit=args.limit, dry_run=args.dry_run)
