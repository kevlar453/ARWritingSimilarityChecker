#!/usr/bin/env python3
"""
AR Tilik - Index Cross-Lingual Sources to Corpus & Qdrant (Phase 3)
Mendaftarkan seluruh 50 naskah rujukan (source_text) lintas bahasa ke tabel documents,
document_pages, dan document_chunks serta mengindeks titik vektornya ke Qdrant.
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


def index_cross_lingual_sources():
    print("=" * 65)
    print("   AR Tilik - Indeks Sumber Lintas Bahasa ke Korpus (Fase 3)   ")
    print("=" * 65)

    dataset_path = PROJECT_ROOT / "datasets" / "artilik_cross_lingual_benchmark_dataset.json"
    if not dataset_path.exists():
        print(f"[X] File {dataset_path} tidak ditemukan!")
        return

    with open(dataset_path, "r", encoding="utf-8") as f:
        cases = json.load(f)

    # Ambil kasus plagiarisme positif yang memiliki source_text
    source_map = {}
    for c in cases:
        if c.get("is_plagiarized") and c.get("source_text"):
            src = c["source_text"].strip()
            if src not in source_map:
                source_map[src] = c

    print(f"[*] Ditemukan {len(source_map)} kalimat sumber rujukan lintas bahasa unik.")

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
            pair = case_info.get("pair", "XLN")
            domain = case_info.get("domain", "Umum")
            title = f"Rujukan Benchmark Lintas Bahasa [{case_id}] - {pair} ({domain})"

            cursor.execute("SELECT id FROM documents WHERE title = %s AND document_type = 'corpus'", (title,))
            existing = cursor.fetchone()

            if existing:
                doc_id = existing["id"]
            else:
                cursor.execute(
                    """
                    INSERT INTO documents (
                        user_id, title, author, original_filename, mime_type, file_size,
                        storage_path, status, document_type, corpus_visibility, source_type,
                        indexing_status, created_at, updated_at
                    ) VALUES (
                        NULL, %s, 'AR Tilik Cross-Lingual Research', %s, 'text/plain', %s,
                        '', 'completed', 'corpus', 'corpus', 'other',
                        'completed', NOW(), NOW()
                    )
                    """,
                    (title, f"{case_id.lower()}_source.txt", len(src_text.encode('utf-8')))
                )
                doc_id = cursor.lastrowid
                conn.commit()

                cursor.execute(
                    """
                    INSERT INTO document_pages (document_id, page_number, text, created_at)
                    VALUES (%s, 1, %s, NOW())
                    """,
                    (doc_id, src_text)
                )

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

            pages = [{"page_number": 1, "text": src_text}]
            count = lens.index_corpus_document(doc_id, pages)
            indexed_vectors += count
            print(f"[✓] {case_id} ({pair}): Doc #{doc_id} terindeks ke Qdrant ({count} vektor)")

        print("=" * 65)
        print(f"[✓] Selesai: {inserted_count} dokumen korpus baru, {indexed_vectors} vektor terindeks ke Qdrant.")
        print("=" * 65)
    finally:
        cursor.close()
        conn.close()


if __name__ == "__main__":
    index_cross_lingual_sources()
