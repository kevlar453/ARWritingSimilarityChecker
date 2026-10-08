from worker.extractor import extract_document
from worker.normalizer import normalize_text, tokenize
from worker.shingler import generate_shingles
from worker.evasion_detector import deobfuscate_for_indexing

def clear_document_index(connection, document_id: int):
    cursor = connection.cursor()
    cursor.execute("DELETE FROM fingerprints WHERE document_id = %s", (document_id,))
    cursor.execute("DELETE FROM fingerprint_documents WHERE document_id = %s", (document_id,))
    cursor.execute("DELETE FROM document_chunks WHERE document_id = %s", (document_id,))
    cursor.execute("DELETE FROM document_pages WHERE document_id = %s", (document_id,))
    connection.commit()
    cursor.close()

def save_page(connection, document_id: int, page_number: int, text: str) -> int:
    cursor = connection.cursor()
    cursor.execute("""
        INSERT INTO document_pages (document_id, page_number, text, created_at)
        VALUES (%s, %s, %s, NOW())
        ON DUPLICATE KEY UPDATE text = VALUES(text)
    """, (document_id, page_number, text))
    page_id = cursor.lastrowid
    cursor.close()
    return page_id

def save_chunk(
    connection,
    document_id: int,
    page_id: int,
    chunk_index: int,
    text: str,
    normalized_text: str,
    word_count: int,
    start_word_pos: int,
    end_word_pos: int
) -> int:
    cursor = connection.cursor()
    cursor.execute("""
        INSERT INTO document_chunks (
            document_id, page_id, chunk_index, text, normalized_text,
            word_count, start_word_position, end_word_position, created_at
        )
        VALUES (%s, %s, %s, %s, %s, %s, %s, %s, NOW())
    """, (
        document_id, page_id, chunk_index, text, normalized_text,
        word_count, start_word_pos, end_word_pos
    ))
    chunk_id = cursor.lastrowid
    cursor.close()
    return chunk_id

def save_fingerprints(
    connection,
    document_id: int,
    chunk_id: int,
    shingles: list[dict],
    absolute_offset: int = 0
):
    if not shingles:
        return
    cursor = connection.cursor()
    values = []
    for shingle in shingles:
        abs_pos = absolute_offset + shingle["position"]
        values.append((
            document_id,
            chunk_id,
            shingle["fingerprint"],
            abs_pos
        ))
        if len(values) >= 1000:
            cursor.executemany("""
                INSERT INTO fingerprints (document_id, chunk_id, fingerprint, position, created_at)
                VALUES (%s, %s, %s, %s, NOW())
            """, values)
            values = []
    if values:
        cursor.executemany("""
            INSERT INTO fingerprints (document_id, chunk_id, fingerprint, position, created_at)
            VALUES (%s, %s, %s, %s, NOW())
        """, values)
    cursor.close()

def save_document_fingerprints(
    connection,
    document_id: int,
    shingles: list[dict],
    word_chunk_ids: list[int]
):
    """
    Menyimpan seluruh fingerprint dokumen secara berkesinambungan (cross-page).
    Setiap shingle diasosiasikan dengan chunk_id dari kata awal shingle tersebut.
    """
    if not shingles:
        return
    cursor = connection.cursor()
    values = []
    for shingle in shingles:
        pos = shingle["position"]
        chunk_id = word_chunk_ids[pos]
        values.append((
            document_id,
            chunk_id,
            shingle["fingerprint"],
            pos
        ))
        if len(values) >= 1000:
            cursor.executemany("""
                INSERT INTO fingerprints (document_id, chunk_id, fingerprint, position, created_at)
                VALUES (%s, %s, %s, %s, NOW())
            """, values)
            values = []
    if values:
        cursor.executemany("""
            INSERT INTO fingerprints (document_id, chunk_id, fingerprint, position, created_at)
            VALUES (%s, %s, %s, %s, NOW())
        """, values)
    cursor.close()

def save_fingerprint_documents(
    connection,
    document_id: int,
    shingles: list[dict]
):
    if not shingles:
        return
    unique_fps = {item["fingerprint"] for item in shingles}
    cursor = connection.cursor()
    values = [(fp, document_id) for fp in unique_fps]
    for start in range(0, len(values), 1000):
        batch = values[start:start + 1000]
        cursor.executemany("""
            INSERT IGNORE INTO fingerprint_documents (fingerprint, document_id, created_at)
            VALUES (%s, %s, NOW())
        """, batch)
    cursor.close()

def rebuild_fingerprint_stats(connection, benchmark_run_id: int | None = None):
    cursor = connection.cursor()
    if benchmark_run_id is not None:
        cursor.execute("""
            DELETE fs FROM fingerprint_stats fs
            WHERE fs.fingerprint IN (
                SELECT fd.fingerprint
                FROM fingerprint_documents fd
                INNER JOIN documents d ON d.id = fd.document_id
                WHERE d.benchmark_run_id = %s
            )
        """, (benchmark_run_id,))
        
        cursor.execute("""
            INSERT INTO fingerprint_stats (fingerprint, document_frequency, created_at, updated_at)
            SELECT fd.fingerprint, COUNT(DISTINCT fd.document_id), NOW(), NOW()
            FROM fingerprint_documents fd
            INNER JOIN documents d ON d.id = fd.document_id
            WHERE d.benchmark_run_id = %s
              AND d.document_type = 'corpus'
            GROUP BY fd.fingerprint
            ON DUPLICATE KEY UPDATE
                document_frequency = VALUES(document_frequency),
                updated_at = NOW()
        """, (benchmark_run_id,))
    else:
        cursor.execute("""
            INSERT INTO fingerprint_stats (fingerprint, document_frequency, created_at, updated_at)
            SELECT fd.fingerprint, COUNT(DISTINCT fd.document_id), NOW(), NOW()
            FROM fingerprint_documents fd
            INNER JOIN documents d ON d.id = fd.document_id
            WHERE d.document_type = 'corpus'
              AND (d.benchmark_run_id IS NULL OR d.benchmark_run_id = 0)
            GROUP BY fd.fingerprint
            ON DUPLICATE KEY UPDATE
                document_frequency = VALUES(document_frequency),
                updated_at = NOW()
        """)
    connection.commit()
    cursor.close()

def index_document(
    connection,
    document_id: int,
    storage_path: str,
    mime_type: str | None = None,
    is_corpus: bool = False
):
    try:
        clear_document_index(connection, document_id)
        pages = extract_document(storage_path, mime_type)
        
        total_pages = len(pages)
        all_words = []
        word_chunk_ids = []
        
        for page_idx, page in enumerate(pages):
            raw_text = page["text"]
            # Netralkan manipulasi karakter campuran (homoglif) sebelum tokenisasi
            deobf_text = deobfuscate_for_indexing(raw_text)
            norm_text = normalize_text(deobf_text)
            words = tokenize(norm_text)
            word_count = len(words)
            
            if word_count == 0:
                continue
                
            page_id = save_page(connection, document_id, page["page_number"], raw_text)
            start_pos = len(all_words)
            end_pos = start_pos + word_count - 1
            
            chunk_id = save_chunk(
                connection=connection,
                document_id=document_id,
                page_id=page_id,
                chunk_index=page_idx,
                text=raw_text,
                normalized_text=" ".join(words),
                word_count=word_count,
                start_word_pos=start_pos,
                end_word_pos=end_pos
            )
            
            all_words.extend(words)
            word_chunk_ids.extend([chunk_id] * word_count)
            
        if not all_words:
            cursor = connection.cursor()
            cursor.execute("""
                UPDATE documents
                SET page_count = %s,
                    status = 'failed',
                    indexing_status = 'failed',
                    updated_at = NOW()
                WHERE id = %s
            """, (total_pages, document_id))
            connection.commit()
            cursor.close()
            return

        # Buat shingle berkesinambungan lintas halaman dari seluruh kata dokumen
        shingles = generate_shingles(all_words, size=5)
        
        # Simpan fingerprints dan dokumen referensi secara atomik
        save_document_fingerprints(
            connection=connection,
            document_id=document_id,
            shingles=shingles,
            word_chunk_ids=word_chunk_ids
        )
        
        if is_corpus:
            save_fingerprint_documents(connection, document_id, shingles)
            
        cursor = connection.cursor()
        cursor.execute("""
            UPDATE documents
            SET page_count = %s,
                status = 'completed',
                indexing_status = 'completed',
                updated_at = NOW()
            WHERE id = %s
        """, (total_pages, document_id))
        connection.commit()
        cursor.close()
        
        if is_corpus:
            rebuild_fingerprint_stats(connection)
            
    except Exception as err:
        connection.rollback()
        try:
            cursor = connection.cursor()
            cursor.execute("""
                UPDATE documents
                SET status = 'failed',
                    indexing_status = 'failed',
                    updated_at = NOW()
                WHERE id = %s
            """, (document_id,))
            connection.commit()
            cursor.close()
        except Exception:
            pass
        raise err
