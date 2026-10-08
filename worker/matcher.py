from collections import defaultdict
from worker.similarity import (
    merge_ranges,
    calculate_overall_similarity,
    calculate_longest_match,
    calculate_source_similarity
)
from worker.idf import (
    calculate_idf,
    calculate_frequency_ratio,
    classify_commonness,
    evaluate_segment,
    calculate_segment_commonness
)

SHINGLE_SIZE = 5
BATCH_SIZE = 500

def get_document_word_count(connection, document_id: int) -> int:
    cursor = connection.cursor(dictionary=True)
    cursor.execute("""
        SELECT COALESCE(SUM(word_count), 0) AS total_words
        FROM document_chunks
        WHERE document_id = %s
    """, (document_id,))
    row = cursor.fetchone()
    cursor.close()
    return int(row["total_words"] if row and row["total_words"] else 0)

def get_submission_fingerprints(connection, document_id: int) -> list[dict]:
    cursor = connection.cursor(dictionary=True)
    cursor.execute("""
        SELECT fingerprint, position, chunk_id
        FROM fingerprints
        WHERE document_id = %s
        ORDER BY position ASC
    """, (document_id,))
    rows = cursor.fetchall()
    cursor.close()
    return rows

def build_submission_fingerprint_map(fingerprints: list[dict]) -> dict:
    result = {}
    for item in fingerprints:
        fp = item["fingerprint"]
        result.setdefault(fp, []).append(item["position"])
    return result

def find_matching_fingerprints(
    connection,
    unique_fingerprints: list[bytes],
    submission_document_id: int,
    corpus_scope: str = "production",
    benchmark_run_id: int | None = None,
    batch_size: int = BATCH_SIZE
) -> list[dict]:
    if not unique_fingerprints:
        return []
    
    results = []
    for start in range(0, len(unique_fingerprints), batch_size):
        batch = unique_fingerprints[start:start + batch_size]
        placeholders = ",".join(["%s"] * len(batch))
        params = [submission_document_id] + batch
        
        sql = f"""
            SELECT
                f.fingerprint,
                f.document_id AS source_document_id,
                f.chunk_id AS source_chunk_id,
                f.position AS source_position
            FROM fingerprints f
            INNER JOIN documents d ON d.id = f.document_id
            WHERE f.document_id != %s
              AND f.fingerprint IN ({placeholders})
              AND d.document_type = 'corpus'
        """
        
        if corpus_scope == "benchmark":
            if benchmark_run_id is not None:
                sql += " AND d.benchmark_run_id = %s"
                params.append(benchmark_run_id)
        elif corpus_scope == "production":
            sql += """
                AND (d.benchmark_run_id IS NULL OR d.benchmark_run_id = 0)
                AND d.indexing_status = 'completed'
                AND d.corpus_visibility IN ('institution', 'corpus')
            """
        else:
            raise ValueError(f"corpus_scope tidak valid: '{corpus_scope}'")
            
        cursor = connection.cursor(dictionary=True)
        cursor.execute(sql, params)
        rows = cursor.fetchall()
        cursor.close()
        results.extend(rows)
        
    return results

def build_match_pairs(submission_map: dict, raw_matches: list[dict]) -> list[dict]:
    pairs = []
    for match in raw_matches:
        fp = match["fingerprint"]
        submission_positions = submission_map.get(fp, [])
        for sub_pos in submission_positions:
            pairs.append({
                "fingerprint": fp,
                "submission_position": sub_pos,
                "source_document_id": match["source_document_id"],
                "source_chunk_id": match["source_chunk_id"],
                "source_position": match["source_position"]
            })
    return pairs

def deduplicate_match_pairs(pairs: list[dict]) -> list[dict]:
    unique = {}
    for match in pairs:
        key = (
            match["source_document_id"],
            match["submission_position"],
            match["source_position"]
        )
        unique[key] = match
    return list(unique.values())

def group_matches_by_source(matches: list[dict]) -> dict[int, list[dict]]:
    grouped = defaultdict(list)
    for match in matches:
        source_id = match["source_document_id"]
        grouped[source_id].append(match)
    return grouped

def group_match_segments(matches: list[dict]) -> list[list[dict]]:
    """
    Mengelompokkan fingerprint yang berurutan secara kontigu menggunakan diagonal grouping:
    diagonal = source_position - submission_position.
    Hal ini mencegah fragmentasi segmen ketika suatu frasa muncul berulang kali di dokumen sumber.
    """
    if not matches:
        return []
    
    # Kelompokkan per (source_document_id, diagonal)
    diagonal_groups = defaultdict(list)
    for m in matches:
        diag = m["source_position"] - m["submission_position"]
        diagonal_groups[(m["source_document_id"], diag)].append(m)

    segments = []
    for (source_id, diag), group in diagonal_groups.items():
        # Urutkan berdasarkan submission_position
        sorted_group = sorted(group, key=lambda item: item["submission_position"])
        current = [sorted_group[0]]
        for match in sorted_group[1:]:
            prev = current[-1]
            sub_delta = match["submission_position"] - prev["submission_position"]
            if sub_delta == 1:
                current.append(match)
            elif sub_delta > 1:
                segments.append(current)
                current = [match]
            # jika sub_delta == 0 (duplikasi posisi), abaikan
        if current:
            segments.append(current)

    # Urutkan seluruh segmen berdasarkan submission_position agar deterministik dan berurutan
    segments.sort(key=lambda seg: (seg[0]["submission_position"], seg[0]["source_document_id"]))
    return segments

def load_document_page_map(connection, document_ids: list[int]) -> dict[int, list[dict]]:
    """
    Memuat mapping chunk-ke-halaman untuk daftar document_id dalam 1 query batch (mencegah N+1).
    Hasilnya berupa dict {document_id: [{'start': ..., 'end': ..., 'page_number': ...}, ...]}
    diurutkan berdasarkan start_word_position.
    """
    if not document_ids:
        return {}
    
    unique_ids = list(set(document_ids))
    placeholders = ",".join(["%s"] * len(unique_ids))
    cursor = connection.cursor(dictionary=True)
    cursor.execute(f"""
        SELECT dc.document_id, dc.start_word_position, dc.end_word_position, dp.page_number
        FROM document_chunks dc
        INNER JOIN document_pages dp ON dp.id = dc.page_id
        WHERE dc.document_id IN ({placeholders})
        ORDER BY dc.document_id, dc.start_word_position ASC
    """, unique_ids)
    rows = cursor.fetchall()
    cursor.close()
    
    page_map = defaultdict(list)
    for row in rows:
        page_map[row["document_id"]].append({
            "start": row["start_word_position"],
            "end": row["end_word_position"],
            "page_number": row["page_number"]
        })
    return page_map

def find_page_in_map(page_map: dict[int, list[dict]], document_id: int, position: int) -> int | None:
    """
    Mencari nomor halaman dari in-memory page map. Mengembalikan None jika tidak ditemukan.
    """
    chunks = page_map.get(document_id, [])
    for chunk in chunks:
        if chunk["start"] <= position <= chunk["end"]:
            return chunk["page_number"]
    return None

def get_page_for_position(connection, document_id: int, position: int) -> int | None:
    cursor = connection.cursor(dictionary=True)
    cursor.execute("""
        SELECT dp.page_number
        FROM document_chunks dc
        INNER JOIN document_pages dp ON dp.id = dc.page_id
        WHERE dc.document_id = %s
          AND %s BETWEEN dc.start_word_position AND dc.end_word_position
        LIMIT 1
    """, (document_id, position))
    row = cursor.fetchone()
    cursor.close()
    return row["page_number"] if row else None

def get_corpus_document_count(
    connection,
    corpus_scope: str = "production",
    benchmark_run_id: int | None = None
) -> int:
    cursor = connection.cursor(dictionary=True)
    sql = "SELECT COUNT(*) AS total FROM documents WHERE document_type = 'corpus'"
    params = []
    
    if corpus_scope == "benchmark":
        if benchmark_run_id is not None:
            sql += " AND benchmark_run_id = %s"
            params.append(benchmark_run_id)
    elif corpus_scope == "production":
        sql += """
            AND (benchmark_run_id IS NULL OR benchmark_run_id = 0)
            AND indexing_status = 'completed'
            AND corpus_visibility IN ('institution', 'corpus')
        """
    else:
        raise ValueError(f"corpus_scope tidak valid: '{corpus_scope}'")
        
    cursor.execute(sql, params)
    row = cursor.fetchone()
    cursor.close()
    return int(row["total"] if row and row["total"] else 0)

def get_fingerprint_stats(
    connection,
    fingerprints: list[bytes],
    batch_size: int = BATCH_SIZE
) -> dict[bytes, int]:
    if not fingerprints:
        return {}
    
    unique_fps = list(set(fingerprints))
    stats = {}
    cursor = connection.cursor(dictionary=True)
    
    for start in range(0, len(unique_fps), batch_size):
        batch = unique_fps[start:start + batch_size]
        placeholders = ",".join(["%s"] * len(batch))
        cursor.execute(f"""
            SELECT fingerprint, document_frequency
            FROM fingerprint_stats
            WHERE fingerprint IN ({placeholders})
        """, batch)
        for row in cursor.fetchall():
            stats[row["fingerprint"]] = int(row["document_frequency"])
            
    cursor.close()
    return stats

def enrich_matches_with_idf(
    matches: list[dict],
    fingerprint_stats: dict[bytes, int],
    total_documents: int
) -> list[dict]:
    enriched = []
    for match in matches:
        fp = match["fingerprint"]
        df = fingerprint_stats.get(fp, 1)
        idf = calculate_idf(total_documents, df)
        freq_ratio = calculate_frequency_ratio(df, total_documents)
        commonness = classify_commonness(freq_ratio)
        
        item = dict(match)
        item["document_frequency"] = df
        item["idf"] = round(idf, 6)
        item["frequency_ratio"] = round(freq_ratio, 6)
        item["commonness"] = commonness
        enriched.append(item)
    return enriched

def build_match_record(
    connection,
    submission_document_id: int,
    segment: list[dict],
    shingle_size: int = SHINGLE_SIZE,
    page_map: dict[int, list[dict]] | None = None
) -> dict | None:
    if not segment:
        return None
    
    first = segment[0]
    last = segment[-1]
    
    submission_start = first["submission_position"]
    submission_end = last["submission_position"] + shingle_size - 1
    source_start = first["source_position"]
    source_end = last["source_position"] + shingle_size - 1
    word_count = submission_end - submission_start + 1
    source_document_id = first["source_document_id"]
    
    eval_info = evaluate_segment(segment, shingle_size)
    commonness = calculate_segment_commonness(segment)
    
    if page_map is not None:
        submission_page = find_page_in_map(page_map, submission_document_id, submission_start)
        source_page = find_page_in_map(page_map, source_document_id, source_start)
    else:
        submission_page = get_page_for_position(connection, submission_document_id, submission_start)
        source_page = get_page_for_position(connection, source_document_id, source_start)
    
    return {
        "source_document_id": source_document_id,
        "submission_start": submission_start,
        "submission_end": submission_end,
        "source_start": source_start,
        "source_end": source_end,
        "word_count": word_count,
        "match_type": "exact",
        "submission_page": submission_page,
        "source_page": source_page,
        "average_idf": eval_info["average_idf"],
        "max_idf": eval_info["max_idf"],
        "significance": eval_info["significance"],
        "commonness": commonness
    }

def build_source_summaries(
    pairs: list[dict],
    total_words: int,
    shingle_size: int = SHINGLE_SIZE
) -> list[dict]:
    grouped = group_matches_by_source(pairs)
    sources = []
    
    for source_id, source_matches in grouped.items():
        segments = group_match_segments(source_matches)
        ranges = []
        for segment in segments:
            if not segment:
                continue
            start = segment[0]["submission_position"]
            end = segment[-1]["submission_position"] + shingle_size - 1
            ranges.append((start, end))
            
        merged = merge_ranges(ranges)
        matched_words = sum(end - start + 1 for start, end in merged)
        similarity = calculate_source_similarity(matched_words, total_words)
        
        sources.append({
            "source_document_id": source_id,
            "matched_words": matched_words,
            "similarity": similarity,
            "match_count": len(segments)
        })
        
    sources.sort(key=lambda item: item["matched_words"], reverse=True)
    for rank, src in enumerate(sources, start=1):
        src["source_rank"] = rank
        
    return sources

def run_similarity_check(
    connection,
    submission_document_id: int,
    engine: str = "exact",
    corpus_scope: str = "production",
    benchmark_run_id: int | None = None
) -> dict:
    """
    Fungsi eksekusi inti similarity checker AR Tilik.
    Mendukung engine 'exact' (baseline v1.0) dan 'idf' (significance v1.1).
    """
    total_words = get_document_word_count(connection, submission_document_id)
    submission_fingerprints = get_submission_fingerprints(connection, submission_document_id)
    
    empty_result = {
        "document_id": submission_document_id,
        "total_words": total_words,
        "overall_similarity": 0.0,
        "matched_words": 0,
        "longest_match_words": 0,
        "source_count": 0,
        "match_count": 0,
        "sources": [],
        "matches": []
    }
    
    if total_words <= 0 or not submission_fingerprints:
        return empty_result
    
    submission_map = build_submission_fingerprint_map(submission_fingerprints)
    unique_fingerprints = list(submission_map.keys())
    
    raw_matches = find_matching_fingerprints(
        connection=connection,
        unique_fingerprints=unique_fingerprints,
        submission_document_id=submission_document_id,
        corpus_scope=corpus_scope,
        benchmark_run_id=benchmark_run_id
    )
    
    if not raw_matches:
        return empty_result
    
    pairs = build_match_pairs(submission_map, raw_matches)
    pairs = deduplicate_match_pairs(pairs)
    
    # Ambil info DF & IDF jika korpus tersedia
    total_corpus_docs = get_corpus_document_count(connection, corpus_scope, benchmark_run_id)
    fingerprint_values = [p["fingerprint"] for p in pairs]
    fingerprint_stats = get_fingerprint_stats(connection, fingerprint_values)
    
    enriched_pairs = enrich_matches_with_idf(pairs, fingerprint_stats, total_corpus_docs)
    
    # Pengelompokan segment berurutan per source
    grouped_sources = group_matches_by_source(enriched_pairs)
    all_segments = []
    for source_id, source_matches in grouped_sources.items():
        all_segments.extend(group_match_segments(source_matches))
        
    similarity_result = calculate_overall_similarity(enriched_pairs, total_words, SHINGLE_SIZE)
    longest_match_words = calculate_longest_match(all_segments, SHINGLE_SIZE)
    
    needed_doc_ids = {submission_document_id}
    for seg in all_segments:
        if seg:
            needed_doc_ids.add(seg[0]["source_document_id"])
    page_map = load_document_page_map(connection, list(needed_doc_ids))
    
    match_records = []
    for segment in all_segments:
        record = build_match_record(connection, submission_document_id, segment, SHINGLE_SIZE, page_map=page_map)
        if record:
            match_records.append(record)
            
    source_summaries = build_source_summaries(enriched_pairs, total_words, SHINGLE_SIZE)
    
    return {
        "document_id": submission_document_id,
        "total_words": total_words,
        "overall_similarity": similarity_result["similarity"],
        "matched_words": similarity_result["matched_words"],
        "longest_match_words": longest_match_words,
        "source_count": len(source_summaries),
        "match_count": len(match_records),
        "sources": source_summaries,
        "matches": match_records
    }
