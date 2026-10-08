import math
from collections import Counter
from worker.normalizer import normalize_text, tokenize

INDONESIAN_STOPWORDS: set[str] = {
    "yang", "di", "dan", "dari", "ke", "dalam", "untuk", "pada", "adalah", "ini", "itu",
    "dengan", "oleh", "secara", "sebagai", "dapat", "akan", "juga", "karena", "tersebut",
    "bahwa", "serta", "atau", "antara", "terhadap", "bagi", "atas", "bila", "jika",
    "tidak", "bukan", "telah", "kami", "kita", "mereka", "anda", "saya", "dia", "hal",
    "suatu", "ada", "sudah", "lebih", "bisa", "hanya", "namun", "tetapi", "saja",
    "kemudian", "lalu", "sehingga", "maka", "agar", "supaya", "ketika", "saat", "sedangkan",
    "sementara", "tentang", "maupun", "seperti", "yaitu", "yakni", "selain", "masih", "sangat",
    "pun", "pula", "pernah", "belum", "para", "setiap", "tiap", "beberapa", "semua", "seluruh",
    "banyak", "sedikit", "lain", "lainnya", "sendiri", "paling", "begitu", "bagaimana",
    "mengapa", "apakah", "siapa", "apa", "mana", "ia", "olehnya", "adanya", "dst", "dsb", "dll",
    "adapun", "walaupun", "meskipun", "kendati", "sebab", "lagi", "hampir", "selalu", "sering",
    "terkadang", "kadang", "makin", "semakin", "tentu", "pasti", "bahkan", "tanpa", "sambil",
    "the", "of", "and", "in", "to", "a", "is", "that", "for", "it", "as", "was", "with",
    "by", "on", "are", "this", "be", "from", "at", "which", "an", "have", "has", "were"
}

def content_tokens(
    tokens: list[str],
    df: dict[str, int] | None = None,
    total_docs: int = 0,
    max_df_ratio: float = 0.5
) -> list[str]:
    """
    Menyaring token untuk Lensa 2 (TF-IDF):
    - Mengeliminasi kata fungsi/stopword bahasa Indonesia dan Inggris
    - Mengabaikan token pendek (panjang <= 2 karakter) dan digit murni
    - Mengabaikan kata umum berfrekuensi dokumen sangat tinggi (DF > 50% dokumen korpus)
    """
    out = [t for t in tokens if t not in INDONESIAN_STOPWORDS and len(t) > 2 and not t.isdigit()]
    if df and total_docs >= 20:
        out = [t for t in out if (df.get(t, 0) / total_docs) < max_df_ratio]
    return out

def compute_tf(tokens: list[str], sublinear: bool = False) -> dict[str, float]:
    """
    Term Frequency:
    - Default (sublinear=False): TF(t) = count(t) / len(tokens)
    - Sublinear (sublinear=True): TF(t) = 1.0 + ln(count(t))
    """
    if not tokens:
        return {}
    counts = Counter(tokens)
    if sublinear:
        return {term: 1.0 + math.log(count) for term, count in counts.items()}
    total = len(tokens)
    return {term: count / total for term, count in counts.items()}

def compute_idf_table(df: dict[str, int], total_documents: int) -> dict[str, float]:
    """
    Smooth Inverse Document Frequency:
    IDF(t) = ln((1 + N) / (1 + DF(t))) + 1.0
    """
    idfs = {}
    for term, doc_freq in df.items():
        idfs[term] = math.log((1 + total_documents) / (1 + doc_freq)) + 1.0
    return idfs

def compute_document_vector(
    tf: dict[str, float],
    idf_table: dict[str, float],
    default_idf: float = 1.0
) -> dict[str, float]:
    """
    Menghitung bobot TF-IDF per term:
    v(t) = TF(t) * IDF(t)
    """
    return {term: tf_val * idf_table.get(term, default_idf) for term, tf_val in tf.items()}

def cosine_similarity(v1: dict[str, float], v2: dict[str, float]) -> float:
    """
    Cosine similarity antara dua sparse vector:
    cos(v1, v2) = (v1 . v2) / (||v1|| * ||v2||)
    """
    if not v1 or not v2:
        return 0.0
    
    dot_product = sum(v1[term] * v2[term] for term in v1 if term in v2)
    norm1 = math.sqrt(sum(val * val for val in v1.values()))
    norm2 = math.sqrt(sum(val * val for val in v2.values()))
    
    if norm1 == 0.0 or norm2 == 0.0:
        return 0.0
        
    return dot_product / (norm1 * norm2)

def get_submission_tokens(connection, document_id: int) -> list[str]:
    cursor = connection.cursor(dictionary=True)
    cursor.execute("""
        SELECT text
        FROM document_pages
        WHERE document_id = %s
        ORDER BY page_number ASC
    """, (document_id,))
    rows = cursor.fetchall()
    cursor.close()
    
    full_text = " ".join(r["text"] for r in rows) if rows else ""
    return tokenize(normalize_text(full_text))

def get_corpus_documents(
    connection,
    corpus_scope: str = "production",
    benchmark_run_id: int | None = None
) -> list[dict]:
    cursor = connection.cursor(dictionary=True)
    sql = """
        SELECT d.id, d.title, dp.text
        FROM documents d
        INNER JOIN document_pages dp ON dp.document_id = d.id
        WHERE d.document_type = 'corpus'
    """
    params = []
    
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
        
    cursor.execute(sql, params)
    rows = cursor.fetchall()
    cursor.close()
    
    corpus_map = {}
    for r in rows:
        doc_id = r["id"]
        if doc_id not in corpus_map:
            corpus_map[doc_id] = {
                "id": doc_id,
                "title": r["title"],
                "texts": []
            }
        corpus_map[doc_id]["texts"].append(r["text"])
        
    documents = []
    for doc_id, data in corpus_map.items():
        full_text = " ".join(data["texts"])
        tokens = tokenize(normalize_text(full_text))
        documents.append({
            "id": doc_id,
            "title": data["title"],
            "tokens": tokens
        })
        
    return documents

def run_tfidf_similarity_check(
    connection,
    submission_document_id: int,
    corpus_scope: str = "production",
    benchmark_run_id: int | None = None,
    threshold: float = 0.05
) -> dict:
    """
    Menghitung kemiripan leksikal dan topikal (TF-IDF + Cosine Similarity)
    secara transparan tanpa memalsukan metrik exact.
    """
    submission_tokens = get_submission_tokens(connection, submission_document_id)
    total_words = len(submission_tokens)
    
    empty_result = {
        "document_id": submission_document_id,
        "total_words": total_words,
        "engine": "tfidf",
        "cosine_similarity": 0.0,
        "raw_cosine": 0.0,
        "overall_similarity": 0.0,  # Tetap ada untuk backward-compatibility
        "shared_terms_count": 0,
        "average_shared_term_idf": 0.0,
        "source_count": 0,
        "match_count": 0,
        "longest_match_words": 0,
        "top_shared_terms": [],
        "sources": [],
        "matches": []
    }
    
    if total_words == 0:
        return empty_result
        
    corpus_docs = get_corpus_documents(connection, corpus_scope, benchmark_run_id)
    if not corpus_docs:
        return empty_result
        
    total_docs = len(corpus_docs)
    
    # 1. Saring token konten korpus (mengeliminasi kata fungsi/stopword)
    df_raw = Counter()
    for doc in corpus_docs:
        doc["content_tokens"] = content_tokens(doc["tokens"])
        for term in set(doc["content_tokens"]):
            df_raw[term] += 1

    # Jika korpus cukup besar (>= 20 dokumen), saring kata dengan rasio kemunculan > 50%
    sub_filtered = content_tokens(submission_tokens, df_raw, total_docs, max_df_ratio=0.5)
    if total_docs >= 20:
        for doc in corpus_docs:
            doc["content_tokens"] = [t for t in doc["content_tokens"] if (df_raw.get(t, 0) / total_docs < 0.5)]

    # 2. Hitung Document Frequency (DF) nyata dan tabel IDF pada kosakata konten
    df = Counter()
    for doc in corpus_docs:
        for term in set(doc["content_tokens"]):
            df[term] += 1
            
    idf_table = compute_idf_table(df, total_docs)
    default_idf = math.log(1 + total_docs) + 1.0
    
    # 3. Hitung Term Frequency (TF) dan Vektor TF-IDF untuk korpus (sublinear TF)
    corpus_tfs = {doc["id"]: compute_tf(doc["content_tokens"], sublinear=True) for doc in corpus_docs}
    corpus_vectors = {
        doc["id"]: compute_document_vector(corpus_tfs[doc["id"]], idf_table, default_idf)
        for doc in corpus_docs
    }
    
    # 4. Hitung TF dan Vektor TF-IDF untuk submission (sublinear TF)
    sub_tf = compute_tf(sub_filtered, sublinear=True)
    sub_vector = compute_document_vector(sub_tf, idf_table, default_idf)
    
    # 5. Hitung Cosine Similarity terhadap setiap korpus
    sources = []
    all_shared_terms_global = set()
    
    for doc in corpus_docs:
        doc_id = doc["id"]
        doc_vec = corpus_vectors[doc_id]
        doc_tf_dict = corpus_tfs[doc_id]
        
        cosine_val = cosine_similarity(sub_vector, doc_vec)
        
        if cosine_val >= threshold:
            # Term-term bersama yang berkontribusi terhadap dot product
            shared_terms = [t for t in sub_tf if t in doc_tf_dict]
            all_shared_terms_global.update(shared_terms)
            
            # Hitung kontribusi per term: v_sub(t) * v_doc(t)
            term_contributions = [
                (t, sub_vector[t] * doc_vec[t], idf_table.get(t, default_idf))
                for t in shared_terms
            ]
            term_contributions.sort(key=lambda x: x[1], reverse=True)
            
            # Rata-rata IDF aktual dari term yang beririsan
            avg_shared_idf = (
                sum(item[2] for item in term_contributions) / len(term_contributions)
                if term_contributions else 0.0
            )
            # Contribution-weighted IDF
            total_dot = sum(item[1] for item in term_contributions)
            weighted_shared_idf = (
                sum(item[1] * item[2] for item in term_contributions) / total_dot
                if total_dot > 0 else 0.0
            )
            
            sim_percent = round(cosine_val * 100.0, 2)
            sources.append({
                "source_document_id": doc_id,
                "source_title": doc["title"],
                "cosine_similarity": sim_percent,
                "cosine_score": round(cosine_val, 4),
                "shared_terms_count": len(shared_terms),
                "top_shared_terms": [item[0] for item in term_contributions[:6]],
                "average_shared_idf": round(avg_shared_idf, 4),
                "weighted_shared_idf": round(weighted_shared_idf, 4)
            })
            
    # Urutkan sumber berdasarkan cosine similarity tertinggi
    sources.sort(key=lambda item: item["cosine_score"], reverse=True)
    for rank, src in enumerate(sources, start=1):
        src["source_rank"] = rank
        
    top_cosine_percent = sources[0]["cosine_similarity"] if sources else 0.0
    top_raw_cosine = sources[0]["cosine_score"] if sources else 0.0
    top_shared_terms = sources[0]["top_shared_terms"] if sources else []
    top_avg_idf = sources[0]["average_shared_idf"] if sources else 0.0
    top_weighted_idf = sources[0]["weighted_shared_idf"] if sources else 0.0
    
    # Susun match_records berbasis rincian topikal per sumber teratas
    match_records = []
    for src in sources[:5]:
        match_records.append({
            "source_document_id": src["source_document_id"],
            "source_title": src["source_title"],
            "cosine_similarity": src["cosine_similarity"],
            "cosine_score": src["cosine_score"],
            "shared_terms_count": src["shared_terms_count"],
            "top_shared_terms": src["top_shared_terms"],
            "average_shared_idf": src["average_shared_idf"],
            "weighted_shared_idf": src["weighted_shared_idf"],
            "match_type": "tfidf_cosine"
        })
        
    return {
        "document_id": submission_document_id,
        "total_words": total_words,
        "engine": "tfidf",
        "cosine_similarity": top_cosine_percent,
        "raw_cosine": top_raw_cosine,
        "overall_similarity": top_cosine_percent,  # Kompatibilitas metrik utama
        "shared_terms_count": sources[0]["shared_terms_count"] if sources else 0,
        "average_shared_term_idf": top_avg_idf,
        "weighted_shared_term_idf": top_weighted_idf,
        "top_shared_terms": top_shared_terms,
        "longest_match_words": 0,  # Tegaskan bahwa TF-IDF bukan pencari pasase berurutan
        "source_count": len(sources),
        "match_count": len(match_records),
        "sources": sources,
        "matches": match_records
    }
