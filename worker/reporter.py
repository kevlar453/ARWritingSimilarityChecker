import os
import json
import time
from worker.database import get_connection
from worker.matcher import run_similarity_check
from worker.tfidf import run_tfidf_similarity_check
from worker.hybrid import run_hybrid_similarity_check
from worker.indexer import index_document
from worker.citation_detector import detect_citation_context
from worker.evidence_presenter import present_hybrid_report
from worker.observability import log_observability_event, verify_non_suppression_and_invariance

def process_similarity_check(document_id: int, report_id: int, engine: str = "hybrid"):
    t_start = time.perf_counter()
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

        duration = time.perf_counter() - t_start
        result["execution_time_seconds"] = round(duration, 3)

        # 4b. Ekstraksi Konteks Sitasi, Presentasi Terdekomposisi, & Telemetri Observabilitas v1.1
        try:
            submission_text = ""
            cursor.execute(
                "SELECT text FROM document_chunks WHERE document_id = %s ORDER BY chunk_index ASC",
                (document_id,)
            )
            chunks = cursor.fetchall()
            if chunks:
                submission_text = " ".join(c["text"] for c in chunks if c.get("text"))
            elif document.get("storage_path") and os.path.exists(document["storage_path"]):
                from worker.extractor import extract_document
                pages = extract_document(document["storage_path"], document.get("mime_type"))
                submission_text = " ".join(p["text"] for p in pages if p.get("text"))

            t_cite_start = time.perf_counter()
            context_res = detect_citation_context(submission_text)
            cite_latency_ms = (time.perf_counter() - t_cite_start) * 1000.0

            # 4c. Modul Evolusi v1.2: Section Parser, Term-Level Drilldown, Boilerplate Detector
            from worker.section_parser import parse_document_sections
            from worker.term_inspector import compute_term_drilldown
            from worker.boilerplate_detector import detect_academic_boilerplate
            from worker.ai_stylometrics import analyze_ai_stylometrics
            from worker.cross_lingual import calculate_cross_lingual_similarity

            # 1. Smart Section Parser
            segmentation_res = parse_document_sections(submission_text)

            # 2. Term-Level Drill-Down (jika terdapat dokumen rujukan)
            ref_text = ""
            if result.get("sources"):
                top_src_id = result["sources"][0]["source_document_id"]
                cursor.execute(
                    "SELECT text FROM document_chunks WHERE document_id = %s ORDER BY chunk_index ASC",
                    (top_src_id,)
                )
                src_chunks = cursor.fetchall()
                if src_chunks:
                    ref_text = " ".join(sc["text"] for sc in src_chunks if sc.get("text"))
                elif not src_chunks:
                    cursor.execute("SELECT storage_path, mime_type FROM documents WHERE id = %s", (top_src_id,))
                    src_doc = cursor.fetchone()
                    if src_doc and src_doc.get("storage_path") and os.path.exists(src_doc["storage_path"]):
                        from worker.extractor import extract_document
                        src_pages = extract_document(src_doc["storage_path"], src_doc.get("mime_type"))
                        ref_text = " ".join(p["text"] for p in src_pages if p.get("text"))

            term_drilldown_res = None
            if ref_text:
                term_drilldown_res = compute_term_drilldown(
                    submission_text=submission_text,
                    reference_text=ref_text,
                    top_k=10
                )

            # 3. Academic Boilerplate Detector
            boilerplate_res = detect_academic_boilerplate(submission_text)

            # 4. Stylometric & AI-Generated Text Indicator (Fitur 2)
            ai_stylometrics_res = analyze_ai_stylometrics(submission_text)
            result["ai_stylometrics"] = ai_stylometrics_res

            # 5. Cross-Lingual Semantic Similarity Engine (Fitur 5)
            cross_lingual_res = None
            if ref_text:
                cross_lingual_res = calculate_cross_lingual_similarity(submission_text, ref_text)
                if cross_lingual_res and result.get("sources"):
                    result["sources"][0]["cross_lingual"] = cross_lingual_res
            result["cross_lingual"] = cross_lingual_res

            # Anotasi pasase individu dengan teks pasase & status boilerplate
            sub_words = submission_text.split()
            for match in result.get("matches", []):
                s_start = match.get("submission_start", 0)
                s_end = match.get("submission_end", s_start)
                if 0 <= s_start < len(sub_words):
                    passage_slice = sub_words[s_start : s_end + 1]
                    p_text = " ".join(passage_slice)
                    match["passage_text"] = p_text
                    bp_pass = detect_academic_boilerplate(p_text)
                    if not bp_pass.is_boilerplate:
                        ctx_slice = sub_words[max(0, s_start - 2) : min(len(sub_words), s_end + 4)]
                        ctx_text = " ".join(ctx_slice)
                        bp_pass = detect_academic_boilerplate(ctx_text)

                    match["is_boilerplate"] = bp_pass.is_boilerplate
                    match["boilerplate_category"] = bp_pass.category.value
                    match["boilerplate_phrase"] = bp_pass.matched_phrase
                    match["boilerplate_rationale"] = bp_pass.rationale

            t_pres_start = time.perf_counter()
            v11_presentation = present_hybrid_report(
                report_dict=result,
                sources=result.get("sources", []),
                evidence_text=submission_text,
                context_detection=context_res,
                segmentation=segmentation_res,
                term_drilldown=term_drilldown_res,
                boilerplate_detection=boilerplate_res,
                ai_stylometrics=ai_stylometrics_res,
                cross_lingual=cross_lingual_res,
            )
            pres_latency_ms = (time.perf_counter() - t_pres_start) * 1000.0

            inv_check = verify_non_suppression_and_invariance(
                v1_exact=exact_sim,
                v1_cosine=cosine_sim,
                v1_decision=predicted_decision,
                presentation=v11_presentation
            )

            result["v1_1_presentation"] = v11_presentation
            result["v1_1_observability"] = {
                "citation_parsing_ms": round(cite_latency_ms, 4),
                "presentation_ms": round(pres_latency_ms, 4),
                "sla_passed": cite_latency_ms < 150.0,
                "context_type": context_res.context_type,
                "citation_status": context_res.citation_status,
                "citation_confidence": context_res.confidence,
                "has_divergence": v11_presentation.get("source_evidence", {}).get("divergence") is not None,
                "non_suppression_verified": inv_check["non_suppression_verified"],
                "decision_invariance_verified": inv_check["decision_invariance_verified"],
            }

            log_observability_event("SIMILARITY_CHECK_COMPLETED", {
                "report_id": report_id,
                "document_id": document_id,
                "engine": engine,
                "predicted_decision": predicted_decision,
                "exact_similarity": exact_sim,
                "cosine_similarity": cosine_sim,
                "duration_seconds": round(duration, 3),
                "citation_parsing_ms": round(cite_latency_ms, 4),
                "sla_passed": cite_latency_ms < 150.0,
                "context_type": context_res.context_type,
                "citation_status": context_res.citation_status,
                "has_divergence": v11_presentation.get("source_evidence", {}).get("divergence") is not None,
                "non_suppression_verified": inv_check["non_suppression_verified"],
                "decision_invariance_verified": inv_check["decision_invariance_verified"],
            })
        except Exception as obs_err:
            # Failsafe: error observabilitas tidak boleh menggagalkan penulisan laporan utama
            print(f"  [OBSERVABILITY WARNING] Gagal merekam metrik v1.1 untuk Report #{report_id}: {obs_err}")

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
        print(f"  [METRIC] Report #{report_id} Doc #{document_id} [{engine.upper()}] completed in {duration:.2f}s "
              f"-> {predicted_decision.upper()} ({evidence_pattern}) | Exact: {exact_sim:.1f}% | Cosine: {cosine_sim:.1f}%")

        # 8. Notifikasi Telegram Otomatis (Failsafe)
        try:
            from worker.telegram_notifier import notify_similarity_check_completed
            notify_similarity_check_completed(
                report_id=report_id,
                document_title=document.get("title", f"Dokumen #{document_id}"),
                decision=predicted_decision,
                exact_similarity=exact_sim,
                cosine_similarity=cosine_sim,
                longest_match_words=longest_match
            )
        except Exception as tele_err:
            print(f"  [TELEGRAM WARNING] Gagal mengirim notifikasi untuk Report #{report_id}: {tele_err}")

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
