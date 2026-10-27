#!/usr/bin/env python3
"""
AR Tilik - Index Benchmark Sources to Corpus & Qdrant
Mendaftarkan seluruh naskah rujukan (source_text) dari dataset 80 kasus benchmark
ke dalam tabel documents/document_pages/document_chunks sebagai korpus resmi,
serta mengindeks vektornya ke Qdrant (artilik_corpus_semantic).
"""

import os
import sys
import json
from pathlib import Path

PROJECT_ROOT = Path(__file__).resolve().parents[1]
if str(PROJECT_ROOT) not in sys.path:
    sys.path.insert(0, str(PROJECT_ROOT))

from worker.database import get_connection
from worker.semantic_lens import SemanticLens, is_semantic_engine_ready


def index_benchmark_sources():
    print("=" * 65)
    print("   AR Tilik - Indeks Sumber Benchmark Semantik ke Korpus   ")
    print("=" * 65)

    dataset_path = PROJECT_ROOT / "datasets" / "artilik_semantic_paraphrase_benchmark.json"
    if not dataset_path.exists():
        print(f"[X] File {dataset_path} tidak ditemukan!")
        return

    with open(dataset_path, "r", encoding="utf-8") as f:
        cases = json.load(f)

    # Ambil kasus yang memiliki source_text unik
    source_map = {}
    for c in cases:
        if c.get("is_plagiarized") and c.get("source_text"):
            src = c["source_text"].strip()
            if src not in source_map:
                source_map[src] = c

    print(f"[*] Ditemukan {len(source_map)} kalimat sumber rujukan unik dari {len(cases)} kasus benchmark.")

    if not is_semantic_engine_ready():
        print("[X] Mesin semantik / Qdrant tidak siap!")
        return

    lens = SemanticLens()
    conn = get_connection()
    cursor = conn.cursor(dictionary=True)

    inserted_count = 0
    indexed_vectors = 0

    try:
        for src_text, case_info in source_map.items():
            case_id = case_info["case_id"]
            domain = case_info.get("domain", "Umum")
            title = f"Rujukan Benchmark Semantik [{case_id}] - {domain}"

            # Cek apakah sudah pernah terdaftar
            cursor.execute("SELECT id FROM documents WHERE title = %s AND document_type = 'corpus'", (title,))
            existing = cursor.fetchone()

            if existing:
                doc_id = existing["id"]
            else:
                # Insert documents
                cursor.execute(
                    """
                    INSERT INTO documents (
                        user_id, title, author, original_filename, mime_type, file_size,
                        storage_path, status, document_type, corpus_visibility, source_type,
                        indexing_status, created_at, updated_at
                    ) VALUES (
                        NULL, %s, 'AR Tilik Benchmark Research', %s, 'text/plain', %s,
                        '', 'completed', 'corpus', 'corpus', 'other',
                        'completed', NOW(), NOW()
                    )
                    """,
                    (title, f"{case_id.lower()}_source.txt", len(src_text.encode('utf-8')))
                )
                doc_id = cursor.lastrowid
                conn.commit()

                # Insert document_pages
                cursor.execute(
                    """
                    INSERT INTO document_pages (document_id, page_number, text, created_at)
                    VALUES (%s, 1, %s, NOW())
                    """,
                    (doc_id, src_text)
                )

                # Insert document_chunks
                words = src_text.split()
                cursor.execute(
                    """
                    INSERT INTO document_chunks (
                        document_id, page_id, chunk_index, text, normalized_text,
                        word_count, start_word_position, end_word_position, created_at
                    ) VALUES (
                        %s, 0, 0, %s, %s, %s, 0, %s, NOW()
                    )
                    """,
                    (doc_id, src_text, src_text.lower(), len(words), max(0, len(words) - 1))
                )
                conn.commit()
                inserted_count += 1

            # Indeks ke Qdrant
            pages = [{"page_number": 1, "text": src_text}]
            count = lens.index_corpus_document(doc_id, pages)
            indexed_vectors += count
            print(f"[✓] {case_id} ({domain[:25]}): Doc #{doc_id} terindeks ke Qdrant ({count} vektor)")

        print("=" * 65)
        print(f"[✓] Selesai: {inserted_count} dokumen korpus baru ditambahkan, {indexed_vectors} titik vektor diindeks ke Qdrant.")
        print("=" * 65)
    finally:
        cursor.close()
        conn.close()


if __name__ == "__main__":
    index_benchmark_sources()
