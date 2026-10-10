from typing import TypedDict, Optional
from worker.matcher import run_similarity_check
from worker.tfidf import run_tfidf_similarity_check

# Default Thresholds untuk Analisis Bukti Hybrid
DEFAULT_EXACT_MATCH_SIMILARITY = 70.0
DEFAULT_EXACT_REVIEW_SIMILARITY = 10.0
DEFAULT_TFIDF_MATCH_SIMILARITY = 85.0
DEFAULT_TFIDF_REVIEW_SIMILARITY = 10.0
DEFAULT_EXACT_MATCH_LONGEST = 50
DEFAULT_EXACT_REVIEW_LONGEST = 10
DEFAULT_SUBSTANTIAL_LONGEST = 25
DEFAULT_SIGNIFICANT_MIN_IDF = 0.5

class EvidenceContract(TypedDict, total=False):
    """
    Kontrak data bukti seragam untuk setiap engine pemeriksa kemiripan.
    """
    engine: str                  # 'exact' | 'tfidf'
    similarity: float            # 0.0 - 100.0%
    matched_terms: int           # exact: matched words; tfidf: distinct shared terms
    longest_match: Optional[int] # exact: contiguous words; tfidf: None
    source_count: int            # jumlah dokumen sumber terdeteksi
    match_count: int             # jumlah segmen/rekaman pasase
    avg_idf: Optional[float]     # rata-rata IDF term bukti
    max_idf: Optional[float]     # IDF maksimum term bukti
    top_sources: list[dict]      # daftar sumber teratas yang relevan
    substantive_longest: Optional[int]      # pasase terpanjang di luar boilerplate & administratif
    substantive_similarity: Optional[float] # kemiripan sekuensial pada substansi pokok
    substantive_words: Optional[int]
    corpus_docs_count: Optional[int]

def extract_exact_evidence(exact_result: dict) -> EvidenceContract:
    """
    Mengekstrak dan menormalisasi output exact engine ke dalam EvidenceContract.
    """
    matches = exact_result.get("matches", [])
    avg_idf = (
        sum(m.get("average_idf", 0.0) for m in matches) / len(matches)
        if matches else 0.0
    )
    max_idf = max((m.get("max_idf", 0.0) for m in matches), default=0.0)
    
    return {
        "engine": "exact",
        "similarity": float(exact_result.get("overall_similarity", 0.0)),
        "matched_terms": int(exact_result.get("matched_words", 0)),
        "longest_match": int(exact_result.get("longest_match_words", 0)),
        "source_count": int(exact_result.get("source_count", 0)),
        "match_count": int(exact_result.get("match_count", 0)),
        "avg_idf": round(avg_idf, 4),
        "max_idf": round(max_idf, 4),
        "top_sources": exact_result.get("sources", [])[:5],
        "substantive_longest": exact_result.get("substantive_longest"),
        "substantive_similarity": exact_result.get("substantive_similarity"),
        "substantive_words": exact_result.get("substantive_words"),
    }

def extract_tfidf_evidence(tfidf_result: dict) -> EvidenceContract:
    """
    Mengekstrak dan menormalisasi output tfidf engine ke dalam EvidenceContract.
    """
    return {
        "engine": "tfidf",
        "similarity": float(tfidf_result.get("cosine_similarity", 0.0)),
        "matched_terms": int(tfidf_result.get("shared_terms_count", 0)),
        "longest_match": None,  # TF-IDF bukan passage matcher
        "source_count": int(tfidf_result.get("source_count", 0)),
        "match_count": int(tfidf_result.get("match_count", 0)),
        "avg_idf": float(tfidf_result.get("average_shared_term_idf", 0.0)),
        "max_idf": None,
        "top_sources": tfidf_result.get("sources", [])[:5]
    }

def collect_evidence(
    connection,
    submission_document_id: int,
    corpus_scope: str = "production",
    benchmark_run_id: Optional[int] = None,
    exclude_same_user: bool = True
) -> tuple[dict, dict, EvidenceContract, EvidenceContract]:
    """
    Fase 2.2: Mengumpulkan bukti mentah dari Exact Engine dan TF-IDF Engine
    tanpa mengubah atau mencemari hasil asli masing-masing engine.
    """
    # 1. Jalankan Exact Engine
    exact_raw = run_similarity_check(
        connection=connection,
        submission_document_id=submission_document_id,
        engine="exact",
        corpus_scope=corpus_scope,
        benchmark_run_id=benchmark_run_id,
        exclude_same_user=exclude_same_user
    )
    exact_evidence = extract_exact_evidence(exact_raw)
    
    # 2. Jalankan TF-IDF Engine
    tfidf_raw = run_tfidf_similarity_check(
        connection=connection,
        submission_document_id=submission_document_id,
        corpus_scope=corpus_scope,
        benchmark_run_id=benchmark_run_id,
        exclude_same_user=exclude_same_user
    )
    tfidf_evidence = extract_tfidf_evidence(tfidf_raw)
    
    return exact_raw, tfidf_raw, exact_evidence, tfidf_evidence

def aggregate_evidence(
    exact_evidence: EvidenceContract,
    tfidf_evidence: EvidenceContract,
    exact_match_sim: float = DEFAULT_EXACT_MATCH_SIMILARITY,
    exact_review_sim: float = DEFAULT_EXACT_REVIEW_SIMILARITY,
    tfidf_match_sim: float = DEFAULT_TFIDF_MATCH_SIMILARITY,
    tfidf_review_sim: float = DEFAULT_TFIDF_REVIEW_SIMILARITY,
    exact_match_longest: int = DEFAULT_EXACT_MATCH_LONGEST,
    exact_review_longest: int = DEFAULT_EXACT_REVIEW_LONGEST,
    min_significant_idf: float = DEFAULT_SIGNIFICANT_MIN_IDF,
    substantial_longest: int = DEFAULT_SUBSTANTIAL_LONGEST,
    corpus_docs_count: Optional[int] = None,
) -> dict:
    """
    Fase 2.3 & 2.4: Menggabungkan bukti Exact dan TF-IDF berdasarkan aturan eksplisit
    dan menghasilkan pola bukti (*evidence pattern*), keputusan (*decision*),
    serta penjelasan transparan (*explainability rationale*).
    Mendukung metrik substantif (non-boilerplate & non-administratif) untuk evaluasi yang adil.
    """
    exact_sim = exact_evidence["similarity"]
    longest = exact_evidence["longest_match"] or 0
    exact_max_idf = exact_evidence["max_idf"] or 0.0

    sub_longest = exact_evidence.get("substantive_longest")
    if sub_longest is None:
        sub_longest = longest

    sub_sim = exact_evidence.get("substantive_similarity")
    if sub_sim is None:
        sub_sim = exact_sim

    tfidf_sim = tfidf_evidence["similarity"]
    tfidf_avg_idf = tfidf_evidence["avg_idf"] or 0.0

    # Periksa kesepakatan sumber teratas (Source Agreement)
    exact_top_src = exact_evidence["top_sources"][0]["source_document_id"] if exact_evidence["top_sources"] else None
    tfidf_top_src = tfidf_evidence["top_sources"][0]["source_document_id"] if tfidf_evidence["top_sources"] else None
    sources_agree = (exact_top_src is not None and exact_top_src == tfidf_top_src)

    # 0. Kasus Korpus Kosong / Tidak Memadai (INSUFFICIENT_CORPUS)
    if corpus_docs_count is not None and corpus_docs_count == 0:
        pattern = "INSUFFICIENT_CORPUS"
        decision = "review"
        agreement = "INSUFFICIENT_CORPUS"
        rationale = (
            "Korpus pembanding belum memiliki dokumen rujukan pada bidang ini (0 dokumen). "
            "Hasil pemeriksaan belum dapat dikonfirmasi sebagai naskah bersih."
        )
        return {
            "decision": decision,
            "pattern": pattern,
            "agreement": agreement,
            "sources_agree": sources_agree,
            "rationale": rationale
        }

    # 1. Kasus Identik / Verbatim (VERBATIM_MATCH)
    # Bukti exact sangat tinggi DAN bukti leksikal kosinus sangat tinggi
    if (exact_sim >= exact_match_sim or longest >= exact_match_longest) and tfidf_sim >= tfidf_match_sim:
        pattern = "VERBATIM_MATCH"
        decision = "match"
        agreement = "STRONG_AGREEMENT"
        rationale = (
            f"Dokumen menunjukkan keselarasan kesamaan tekstual mutlak "
            f"(Exact: {exact_sim:.2f}%, Pasase Terpanjang: {longest} kata) "
            f"dan orientasi kosakata penuh (Cosine: {tfidf_sim:.2f}%)."
        )
        return {
            "decision": decision,
            "pattern": pattern,
            "agreement": agreement,
            "sources_agree": sources_agree,
            "rationale": rationale
        }

    # 2. Kasus Frasa Umum Akademis (COMMON_ACADEMIC_PHRASE)
    # A. Kemiripan muncul tetapi signifikansi IDF rendah (frasa pasaran DF >= 10, IDF <= 1.0)
    if exact_sim >= exact_review_sim and exact_max_idf <= 1.0:
        pattern = "COMMON_ACADEMIC_PHRASE"
        decision = "review"
        agreement = "SIGNIFICANCE_DIVERGENCE"
        rationale = (
            f"Kemiripan terdeteksi pada tingkat frasa (Exact: {exact_sim:.2f}%, Cosine: {tfidf_sim:.2f}%), "
            f"namun analisis signifikansi menunjukkan irisan didominasi oleh kosakata standar/frasa klise akademis "
            f"(Max IDF: {exact_max_idf:.4f})."
        )
        return {
            "decision": decision,
            "pattern": pattern,
            "agreement": agreement,
            "sources_agree": sources_agree,
            "rationale": rationale
        }

    # B. Kemiripan sekuensial berada pada bagian administratif atau daftar rujukan (bukan substansi naskah)
    if longest >= exact_review_longest and sub_longest < substantial_longest and sub_sim < exact_review_sim and tfidf_sim < tfidf_match_sim:
        pattern = "COMMON_ACADEMIC_PHRASE"
        decision = "review" if exact_sim >= exact_review_sim else "no_match"
        agreement = "ADMINISTRATIVE_CONVENTION"
        rationale = (
            f"Kemiripan sekuensial yang terdeteksi ({exact_sim:.2f}%, pasase terpanjang {longest} kata) "
            f"berada pada bagian administratif atau daftar rujukan, tanpa bukti duplikasi substantif "
            f"(Cosine: {tfidf_sim:.2f}%)."
        )
        return {
            "decision": decision,
            "pattern": pattern,
            "agreement": agreement,
            "sources_agree": sources_agree,
            "rationale": rationale
        }

    if exact_sim < exact_review_sim and tfidf_sim >= tfidf_review_sim and tfidf_avg_idf < min_significant_idf:
        pattern = "COMMON_ACADEMIC_PHRASE"
        decision = "review"
        agreement = "SIGNIFICANCE_DIVERGENCE"
        rationale = (
            f"Kemiripan leksikal terdeteksi (Cosine: {tfidf_sim:.2f}%), "
            f"namun disusun dari kosakata pasaran/umum (Avg IDF: {tfidf_avg_idf:.4f})."
        )
        return {
            "decision": decision,
            "pattern": pattern,
            "agreement": agreement,
            "sources_agree": sources_agree,
            "rationale": rationale
        }

    # 3. Kasus Copy-Edit (COPY_EDIT)
    # Ada pasase sekuensial substantif yang dipertahankan, namun kosinus global menunjukkan variasi kosakata
    if (sub_sim >= exact_match_sim or sub_longest >= substantial_longest) and (tfidf_sim < tfidf_match_sim and tfidf_sim >= tfidf_review_sim):
        pattern = "COPY_EDIT"
        decision = "review"
        agreement = "PARTIAL_AGREEMENT"
        rationale = (
            f"Ditemukan pasase berurutan yang substansial ({sub_longest} kata identik) "
            f"dengan variasi orientasi kosakata global (Cosine: {tfidf_sim:.2f}%)."
        )
        return {
            "decision": decision,
            "pattern": pattern,
            "agreement": agreement,
            "sources_agree": sources_agree,
            "rationale": rationale
        }

    # 4. Kasus Kemiripan Sekuensial Parsial / Dilusi Teks (PARTIAL_DIRECT_MATCH)
    if (sub_sim >= exact_review_sim or sub_longest >= substantial_longest) and exact_max_idf > 1.0:
        pattern = "PARTIAL_DIRECT_MATCH"
        decision = "review"
        agreement = "LOCALIZED_EXACT_EVIDENCE"
        rationale = (
            f"Ditemukan pasase kata berurutan signifikan (Longest: {sub_longest} kata, Exact: {exact_sim:.2f}%) "
            f"dengan rujukan terminologi spesifik (Max IDF: {exact_max_idf:.4f})."
        )
        return {
            "decision": decision,
            "pattern": pattern,
            "agreement": agreement,
            "sources_agree": sources_agree,
            "rationale": rationale
        }

    # 5. Kasus Parafrase Topikal (SEMANTIC_PARAPHRASE)
    # Tidak ada pasase sekuensial substantif (Exact substantif rendah), namun kemiripan leksikal substantif tinggi dengan IDF berbobot
    if sub_sim < exact_review_sim and sub_longest < substantial_longest and tfidf_sim >= tfidf_review_sim and tfidf_avg_idf >= min_significant_idf:
        pattern = "SEMANTIC_PARAPHRASE"
        decision = "review"
        agreement = "COMPLEMENTARY_EVIDENCE"
        rationale = (
            f"Tidak ditemukan pasase kata berurutan substantif (Exact: {exact_sim:.2f}%), "
            f"namun terdeteksi kemiripan leksikal topikal yang kuat (Cosine: {tfidf_sim:.2f}%) "
            f"dengan terminologi spesifik rujukan (Avg IDF: {tfidf_avg_idf:.4f})."
        )
        return {
            "decision": decision,
            "pattern": pattern,
            "agreement": agreement,
            "sources_agree": sources_agree,
            "rationale": rationale
        }

    # 6. Tidak Ada Bukti Kemiripan (NO_EVIDENCE)
    pattern = "NO_EVIDENCE"
    decision = "no_match"
    agreement = "MUTUAL_NEGATIVE"
    if corpus_docs_count is not None and corpus_docs_count > 0:
        rationale = (
            f"Tidak ditemukan bukti kesamaan pasase sekuensial maupun irisan kosakata "
            f"yang melewati ambang batas telaah pada korpus pembanding ({corpus_docs_count} dokumen) "
            f"(Exact: {exact_sim:.2f}%, Cosine: {tfidf_sim:.2f}%)."
        )
    else:
        rationale = (
            f"Tidak ditemukan bukti kesamaan pasase sekuensial maupun irisan kosakata "
            f"yang melewati ambang batas telaah (Exact: {exact_sim:.2f}%, Cosine: {tfidf_sim:.2f}%)."
        )
    return {
        "decision": decision,
        "pattern": pattern,
        "agreement": agreement,
        "sources_agree": sources_agree,
        "rationale": rationale
    }

def run_hybrid_similarity_check(
    connection,
    submission_document_id: int,
    corpus_scope: str = "production",
    benchmark_run_id: Optional[int] = None,
    exclude_same_user: bool = True
) -> dict:
    """
    Fungsi eksekusi terpadu Hybrid Evidence Layer untuk AR Tilik.
    Mengumpulkan bukti Exact & TF-IDF, menganalisis pola, dan memberikan keputusan terintegrasi.
    Mengecualikan korpus milik user yang sama untuk mencegah false positive self-plagiarism.
    """
    exact_raw, tfidf_raw, exact_ev, tfidf_ev = collect_evidence(
        connection=connection,
        submission_document_id=submission_document_id,
        corpus_scope=corpus_scope,
        benchmark_run_id=benchmark_run_id,
        exclude_same_user=exclude_same_user
    )
    
    analysis = aggregate_evidence(exact_ev, tfidf_ev)
    
    total_words = exact_raw.get("total_words", tfidf_raw.get("total_words", 0))
    
    # Gabungkan sumber terdeteksi dari Exact dan TF-IDF
    merged_sources_map = {}
    for s in exact_raw.get("sources", []):
        s_id = s["source_document_id"]
        merged_sources_map[s_id] = {
            "source_document_id": s_id,
            "source_title": s.get("source_title", f"Dokumen #{s_id}"),
            "matched_words": s.get("matched_words", 0),
            "matched_shingles": s.get("matched_shingles", 0),
            "exact_similarity": s.get("similarity", 0.0),
            "cosine_similarity": 0.0,
            "similarity": s.get("similarity", 0.0),
            "is_topical_only": False,
        }
    for s in tfidf_raw.get("sources", []):
        s_id = s["source_document_id"]
        if s_id in merged_sources_map:
            merged_sources_map[s_id]["cosine_similarity"] = s.get("cosine_similarity", 0.0)
            if "source_title" in s and not merged_sources_map[s_id].get("source_title"):
                merged_sources_map[s_id]["source_title"] = s["source_title"]
        else:
            merged_sources_map[s_id] = {
                "source_document_id": s_id,
                "source_title": s.get("source_title", f"Dokumen #{s_id}"),
                "matched_words": 0,
                "matched_shingles": 0,
                "exact_similarity": 0.0,
                "cosine_similarity": s.get("cosine_similarity", 0.0),
                "similarity": 0.0,
                "is_topical_only": True,
            }

    # Urutkan berdasarkan matched_words (cakupan exact) lebih dulu, baru kemiripan kosinus
    merged_sources = sorted(
        merged_sources_map.values(),
        key=lambda x: (x.get("matched_words", 0), x.get("exact_similarity", 0.0), x.get("cosine_similarity", 0.0)),
        reverse=True
    )
    for rank, s in enumerate(merged_sources, start=1):
        s["source_rank"] = rank

    return {
        "document_id": submission_document_id,
        "total_words": total_words,
        "engine": "hybrid",
        "predicted_decision": analysis["decision"],
        "evidence_pattern": analysis["pattern"],
        "evidence_agreement": analysis["agreement"],
        "sources_agree": analysis["sources_agree"],
        "rationale": analysis["rationale"],
        
        # Metrik Terpadu yang Transparan
        "overall_similarity": exact_ev["similarity"],   # Exact overlap baseline
        "cosine_similarity": tfidf_ev["similarity"],    # TF-IDF cosine baseline
        "exact_similarity": exact_ev["similarity"],
        "matched_words": exact_ev["matched_terms"],
        "longest_match_words": exact_ev["longest_match"] or 0,
        "shared_terms_count": tfidf_ev["matched_terms"],
        "average_shared_term_idf": tfidf_ev["avg_idf"] or 0.0,
        "source_count": len(merged_sources),
        "match_count": exact_ev["match_count"],
        
        # Bukti Mandiri Tiap Engine
        "exact_evidence": exact_ev,
        "tfidf_evidence": tfidf_ev,
        "sources": merged_sources,
        "matches": exact_raw.get("matches", []),
        "idf_dict": tfidf_raw.get("idf_dict", {})
    }
