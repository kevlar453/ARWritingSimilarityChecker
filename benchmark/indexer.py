from worker.normalizer import normalize_text, tokenize
from worker.shingler import generate_shingles
from benchmark.corpus import generate_corpus

def save_page(connection, document_id: int, text: str) -> int:
    cursor = connection.cursor()
    cursor.execute("""
        INSERT INTO document_pages (document_id, page_number, text, created_at)
        VALUES (%s, 1, %s, NOW())
        ON DUPLICATE KEY UPDATE text = VALUES(text)
    """, (document_id, text))
    page_id = cursor.lastrowid
    connection.commit()
    cursor.close()
    return page_id

def save_chunk(
    connection,
    document_id: int,
    page_id: int,
    normalized_text: str,
    word_count: int
) -> int:
    cursor = connection.cursor()
    start_pos = 0
    end_pos = word_count - 1 if word_count > 0 else 0
    cursor.execute("""
        INSERT INTO document_chunks (
            document_id, page_id, chunk_index, text, normalized_text,
            word_count, start_word_position, end_word_position, created_at
        )
        VALUES (%s, %s, 0, %s, %s, %s, %s, %s, NOW())
    """, (
        document_id, page_id, normalized_text, normalized_text,
        word_count, start_pos, end_pos
    ))
    chunk_id = cursor.lastrowid
    connection.commit()
    cursor.close()
    return chunk_id

def save_fingerprints(connection, document_id: int, chunk_id: int, shingles: list[dict]):
    if not shingles:
        return
    cursor = connection.cursor()
    values = [
        (document_id, chunk_id, item["fingerprint"], item["position"])
        for item in shingles
    ]
    cursor.executemany("""
        INSERT INTO fingerprints (document_id, chunk_id, fingerprint, position, created_at)
        VALUES (%s, %s, %s, %s, NOW())
    """, values)
    connection.commit()
    cursor.close()

def save_fingerprint_documents(connection, document_id: int, shingles: list[dict]):
    if not shingles:
        return
    unique_fps = {item["fingerprint"] for item in shingles}
    cursor = connection.cursor()
    values = [(fp, document_id) for fp in unique_fps]
    cursor.executemany("""
        INSERT IGNORE INTO fingerprint_documents (fingerprint, document_id, created_at)
        VALUES (%s, %s, NOW())
    """, values)
    connection.commit()
    cursor.close()

def index_text(connection, document_id: int, text: str, is_corpus: bool = False):
    norm_text = normalize_text(text)
    words = tokenize(norm_text)
    if not words:
        return
    
    page_id = save_page(connection, document_id, text)
    chunk_id = save_chunk(connection, document_id, page_id, " ".join(words), len(words))
    shingles = generate_shingles(words, size=5)
    
    save_fingerprints(connection, document_id, chunk_id, shingles)
    if is_corpus:
        save_fingerprint_documents(connection, document_id, shingles)

def create_document(
    connection,
    title: str,
    document_type: str,
    benchmark_run_id: int | None = None
) -> int:
    cursor = connection.cursor()
    cursor.execute("""
        INSERT INTO documents (
            title, original_filename, mime_type, file_size, storage_path,
            status, document_type, corpus_visibility, source_type,
            indexing_status, benchmark_run_id, created_at, updated_at
        )
        VALUES (
            %s, %s, 'text/plain', 0, '',
            'completed', %s, 'corpus', 'other',
            'completed', %s, NOW(), NOW()
        )
    """, (
        title,
        title + ".txt",
        document_type,
        benchmark_run_id
    ))
    doc_id = cursor.lastrowid
    connection.commit()
    cursor.close()
    return doc_id

def create_corpus(connection, run_id: int) -> list[dict]:
    corpus = generate_corpus()
    documents = []
    for item in corpus:
        doc_id = create_document(connection, item["name"], "corpus", run_id)
        index_text(connection, doc_id, item["text"], is_corpus=True)
        documents.append({
            "id": doc_id,
            "name": item["name"],
            "text": item["text"]
        })
    return documents

def rebuild_fingerprint_stats(connection, run_id: int):
    cursor = connection.cursor()
    # Hapus stats run ini jika ada
    cursor.execute("""
        DELETE fs FROM fingerprint_stats fs
        WHERE fs.fingerprint IN (
            SELECT fd.fingerprint
            FROM fingerprint_documents fd
            INNER JOIN documents d ON d.id = fd.document_id
            WHERE d.benchmark_run_id = %s
        )
    """, (run_id,))
    
    # Hitung Document Frequency aktual korpus run ini
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
    """, (run_id,))
    connection.commit()
    cursor.close()
