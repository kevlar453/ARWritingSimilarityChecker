import json
from worker.database import get_connection
from worker.matcher import run_similarity_check
from worker.tfidf import run_tfidf_similarity_check
from worker.hybrid import run_hybrid_similarity_check
from worker.indexer import index_document

def process_similarity_check(document_id: int, report_id: int, engine: str = "hybrid"):
    connection = get_connection()
    try:
        # 1. Update status report menjadi processing
        cursor = connection.cursor(dictionary=True)
        cursor.execute("UPDATE reports SET status = 'processing', updated_at = NOW() WHERE id = %s", (report_id,))
        connection.commit()
        
        # 2. Ambil dokumen
        cursor.execute("SELECT * FROM documents WHERE id = %s", (document_id,))
        document = cursor.fetchone()
        if not document:
            raise ValueError(f"Dokumen ID {document_id} tidak ditemukan.")
            
        # 3. Cek apakah dokumen sudah di-index atau belum
        cursor.execute("SELECT COUNT(*) AS cnt FROM fingerprints WHERE document_id = %s", (document_id,))
        fp_count = cursor.fetchone()["cnt"]
        if fp_count == 0 and document.get("storage_path"):
            index_document(
                connection=connection,
                document_id=document_id,
                storage_path=document["storage_path"],
                mime_type=document.get("mime_type"),
                is_corpus=(document.get("document_type") == "corpus")
            )
            
        # 4. Jalankan similarity check sesuai engine
        engine = (engine or "hybrid").lower()
        
        if engine == "hybrid":
            result = run_hybrid_similarity_check(
                connection=connection,
                submission_document_id=document_id,
                corpus_scope="production"
            )
            overall_sim = max(result["exact_similarity"], result["cosine_similarity"])
            exact_sim = result["exact_similarity"]
            cosine_sim = result["cosine_similarity"]
            predicted_decision = result["predicted_decision"]
            evidence_pattern = result["evidence_pattern"]
            evidence_agreement = result["evidence_agreement"]
            sources_agree = 1 if result["sources_agree"] else 0
            rationale = result["rationale"]
            shared_terms_count = result["shared_terms_count"]
            avg_idf = result["average_shared_term_idf"]
            longest_match = result["longest_match_words"]
            source_count = result["source_count"]
            match_count = result["match_count"]
        elif engine == "tfidf":
            result = run_tfidf_similarity_check(
                connection=connection,
                submission_document_id=document_id,
                corpus_scope="production"
            )
            overall_sim = result["cosine_similarity"]
            exact_sim = 0.0
            cosine_sim = result["cosine_similarity"]
            predicted_decision = "match" if cosine_sim >= 85.0 else ("review" if cosine_sim >= 10.0 else "no_match")
            evidence_pattern = "TOPICAL_COSINE"
            evidence_agreement = "SINGLE_ENGINE"
            sources_agree = 0
            rationale = (
                f"Pemeriksaan topikal leksikal menghasilkan kemiripan kosinus {cosine_sim:.2f}%. "
                f"Ditemukan {result.get('shared_terms_count', 0)} term beririsan."
            )
            shared_terms_count = result.get("shared_terms_count", 0)
            avg_idf = result.get("average_shared_term_idf", 0.0)
            longest_match = 0
            source_count = result["source_count"]
            match_count = result["match_count"]
        else: # exact / idf
            result = run_similarity_check(
                connection=connection,
                submission_document_id=document_id,
                engine=engine,
                corpus_scope="production"
            )
            overall_sim = result["overall_similarity"]
            exact_sim = result["overall_similarity"]
            cosine_sim = 0.0
            longest_match = result["longest_match_words"]
            predicted_decision = "match" if (overall_sim >= 70.0 or longest_match >= 50) else ("review" if (overall_sim >= 10.0 or longest_match >= 10) else "no_match")
            evidence_pattern = "SEQUENTIAL_EXACT" if engine == "exact" else "EXACT_IDF_SIGNIFICANCE"
            evidence_agreement = "SINGLE_ENGINE"
            sources_agree = 0
            rationale = (
                f"Pemeriksaan pasase sekuensial exact menghasilkan kemiripan {overall_sim:.2f}% "
                f"dengan pasase terpanjang {longest_match} kata."
            )
            shared_terms_count = result.get("matched_words", 0)
            avg_idf = 0.0
            source_count = result["source_count"]
            match_count = result["match_count"]

        # 5. Simpan summary ke tabel reports
        cursor.execute("""
            UPDATE reports
            SET engine = %s,
                predicted_decision = %s,
                evidence_pattern = %s,
                evidence_agreement = %s,
                sources_agree = %s,
                rationale = %s,
                exact_similarity = %s,
                cosine_similarity = %s,
                shared_terms_count = %s,
                average_shared_term_idf = %s,
                evidence_data = %s,
                overall_similarity = %s,
                source_count = %s,
                match_count = %s,
                longest_match_words = %s,
                status = 'completed',
                completed_at = NOW(),
                updated_at = NOW()
            WHERE id = %s
        """, (
            engine,
            predicted_decision,
            evidence_pattern,
            evidence_agreement,
            sources_agree,
            rationale,
            exact_sim,
            cosine_sim,
            shared_terms_count,
            avg_idf,
            json.dumps(result, ensure_ascii=False),
            overall_sim,
            source_count,
            match_count,
            longest_match,
            report_id
        ))
        
        # 6. Simpan report_sources
        cursor.execute("DELETE FROM report_sources WHERE report_id = %s", (report_id,))
        for src in result.get("sources", []):
            s_exact = src.get("exact_similarity", src.get("similarity", 0.0))
            s_cosine = src.get("cosine_similarity", 0.0)
            s_sim = src.get("similarity", max(s_exact, s_cosine))
            cursor.execute("""
                INSERT INTO report_sources (
                    report_id, source_document_id, matched_shingles, matched_words,
                    similarity, exact_similarity, cosine_similarity, source_rank, created_at
                )
                VALUES (%s, %s, %s, %s, %s, %s, %s, %s, NOW())
            """, (
                report_id,
                src["source_document_id"],
                src.get("matched_shingles", 0),
                src.get("matched_words", src.get("shared_terms_count", 0)),
                s_sim,
                s_exact,
                s_cosine,
                src.get("source_rank", 0)
            ))
            
        # 7. Simpan report_matches
        cursor.execute("DELETE FROM report_matches WHERE report_id = %s", (report_id,))
        for match in result.get("matches", []):
            cursor.execute("""
                INSERT INTO report_matches (
                    report_id, source_document_id, submission_start, submission_end,
                    source_start, source_end, word_count, match_type, similarity,
                    average_idf, max_idf, significance, commonness, created_at
                )
                VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, NOW())
            """, (
                report_id,
                match["source_document_id"],
                match["submission_start"],
                match["submission_end"],
                match["source_start"],
                match["source_end"],
                match["word_count"],
                match.get("match_type", "exact"),
                match.get("similarity", 100.0),
                match.get("average_idf"),
                match.get("max_idf"),
                match.get("significance"),
                match.get("commonness")
            ))
            
        connection.commit()
        cursor.close()
        return result
    except Exception as e:
        connection.rollback()
        cursor = connection.cursor()
        cursor.execute("""
            UPDATE reports
            SET status = 'failed',
                error_message = %s,
                updated_at = NOW()
            WHERE id = %s
        """, (str(e), report_id))
        connection.commit()
        cursor.close()
        raise e
    finally:
        connection.close()
