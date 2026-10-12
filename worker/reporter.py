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

        if document.get("file_purged_at") or document.get("storage_path") == "[PURGED]":
            raise ValueError(
                "Naskah telah dimusnahkan secara mandiri dari peladen demi kedaulatan privasi (Zero-Leakage Guarantee). "
                "Pemeriksaan ulang tidak dapat dilakukan tanpa mengunggah kembali naskah baru."
            )
            
        # 3. Cek apakah dokumen sudah di-index atau belum
        if document.get("indexing_status") != "completed" and document.get("storage_path"):
            index_document(
                connection=connection,
                document_id=document_id,
                storage_path=document["storage_path"],
                mime_type=document.get("mime_type"),
                is_corpus=(document.get("document_type") == "corpus")
            )
            # Muat ulang data dokumen setelah pengindeksan selesai
            cursor.execute("SELECT * FROM documents WHERE id = %s", (document_id,))
            document = cursor.fetchone()

        # Validasi kecukupan teks dokumen (Guard against scanned PDFs / empty documents)
        cursor.execute("""
            SELECT COALESCE(SUM(word_count), 0) AS total_words
            FROM document_chunks
            WHERE document_id = %s
        """, (document_id,))
        doc_word_count = int(cursor.fetchone()["total_words"] or 0)
        if doc_word_count < 15:
            raise ValueError(
                "Naskah tidak memuat teks yang memadai untuk dianalisis (kurang dari 15 kata). "
                "Kemungkinan naskah merupakan hasil pemindaian (scan) berupa gambar tanpa lapisan teks OCR "
                "atau dokumen dalam keadaan kosong."
            )

        # 3b. Pastikan dokumen memiliki judul karya ilmiah asli (bukan nama berkas mentah)
        cur_doc_title = (document.get("title") or "").strip()
        orig_fname = (document.get("original_filename") or "").strip()
        from pathlib import Path
        fname_stem = Path(orig_fname).stem.strip()
        if not cur_doc_title or cur_doc_title.lower() == fname_stem.lower() or cur_doc_title.lower() == orig_fname.lower():
            cursor.execute(
                "SELECT text FROM document_chunks WHERE document_id = %s ORDER BY chunk_index ASC LIMIT 2",
                (document_id,)
            )
            early_chunk_rows = cursor.fetchall()
            if early_chunk_rows:
                from worker.section_parser import extract_academic_title
                chunk_sample = "\n".join(r["text"] for r in early_chunk_rows if r.get("text"))
                detected_title = extract_academic_title(chunk_sample)
                if detected_title:
                    cursor.execute("UPDATE documents SET title = %s WHERE id = %s", (detected_title, document_id))
                    connection.commit()
                    document["title"] = detected_title

        # 3c. Just-In-Time (JIT) Topic Harvesting (Pencarian Korpus Sebidang Otomatis)
        jit_harvest_res = None
        if document.get("document_type") != "corpus":
            doc_title = document.get("title") or document.get("original_filename") or ""
            cursor.execute(
                "SELECT text FROM document_chunks WHERE document_id = %s ORDER BY chunk_index ASC LIMIT 5",
                (document_id,)
            )
            early_chunks = cursor.fetchall()
            early_text = " ".join(c["text"] for c in early_chunks if c.get("text")) if early_chunks else None

            if doc_title:
                try:
                    from worker.jit_harvester import harvest_topic_references_jit
                    jit_harvest_res = harvest_topic_references_jit(
                        title=doc_title,
                        text=early_text,
                        max_works=3,
                        connection=connection
                    )
                except Exception as jit_err:
                    print(f"  [JIT HARVESTER WARNING] Gagal menjalankan JIT harvester untuk Doc #{document_id}: {jit_err}")
                    jit_harvest_res = {"status": "error", "error_message": str(jit_err)}

        # 4. Jalankan similarity check sesuai engine
        engine = (engine or "hybrid").lower()
        
        if engine == "hybrid":
            result = run_hybrid_similarity_check(
                connection=connection,
                submission_document_id=document_id,
                corpus_scope="production"
            )
            overall_sim = result["exact_similarity"]
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

        # Tandai sumber rujukan yang ditemukan melalui JIT topic harvester
        if jit_harvest_res and isinstance(jit_harvest_res, dict):
            discovered_ids = {w["document_id"] for w in jit_harvest_res.get("discovered_works", []) if w.get("document_id")}
            for src in result.get("sources", []):
                if src.get("source_document_id") in discovered_ids:
                    src["is_jit_discovered"] = True

        # 4b. Ekstraksi Konteks Sitasi, Presentasi Terdekomposisi, & Telemetri Observabilitas v1.1
        try:
            submission_text = ""
            sub_tokens = []
            cursor.execute(
                "SELECT text, normalized_text FROM document_chunks WHERE document_id = %s ORDER BY chunk_index ASC",
                (document_id,)
            )
            chunks = cursor.fetchall()
            if chunks:
                submission_text = " ".join(c["text"] for c in chunks if c.get("text"))
                norm_combined = " ".join(c["normalized_text"] for c in chunks if c.get("normalized_text"))
                sub_tokens = norm_combined.split() if norm_combined else submission_text.split()
            elif document.get("storage_path") and os.path.exists(document["storage_path"]):
                from worker.extractor import extract_document
                pages = extract_document(document["storage_path"], document.get("mime_type"))
                submission_text = " ".join(p["text"] for p in pages if p.get("text"))
                sub_tokens = submission_text.split()

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
            segmentation_res = parse_document_sections(submission_text, expected_total_words=doc_word_count)

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
                    top_k=10,
                    idf_table=result.get("idf_dict"),
                    filter_stopwords=True
                )

            # 3. Academic Boilerplate Detector
            boilerplate_res = detect_academic_boilerplate(submission_text)

            # 4. Stylometric & AI-Generated Text Indicator (Fitur 2)
            from worker.section_parser import get_substantive_including_methodology
            subst_body_text = get_substantive_including_methodology(segmentation_res) if segmentation_res else ""
            ai_stylometrics_res = analyze_ai_stylometrics(
                text=submission_text,
                substantive_text=subst_body_text
            )
            result["ai_stylometrics"] = ai_stylometrics_res

            # 5. Cross-Lingual Semantic Similarity Engine (Fitur 5 / Opsi A)
            from worker.cross_lingual import scan_cross_lingual_corpus
            cross_lingual_res = scan_cross_lingual_corpus(
                submission_text=submission_text,
                connection=connection,
                candidate_sources=result.get("sources", [])
            )
            result["cross_lingual"] = cross_lingual_res

            # Jika terdeteksi direct translation match terhadap dokumen korpus asing yang belum ada di daftar sources,
            # sematkan ke dalam daftar sources agar pengguna bisa mengeklik dan membukanya di Layar Ganda!
            if cross_lingual_res and cross_lingual_res.get("is_cross_lingual_match"):
                cl_doc_id = cross_lingual_res.get("source_document_id")
                if cl_doc_id:
                    existing_src_ids = {s.get("source_document_id") for s in result.get("sources", [])}
                    if cl_doc_id not in existing_src_ids:
                        result.setdefault("sources", []).append({
                            "source_document_id": cl_doc_id,
                            "source_title": cross_lingual_res.get("source_title", f"Dokumen #{cl_doc_id}"),
                            "source_type": "corpus",
                            "source_rank": len(result.get("sources", [])) + 1,
                            "exact_similarity": 0.0,
                            "cosine_similarity": float(cross_lingual_res.get("cross_lingual_score", 0.0)),
                            "matched_words": len(cross_lingual_res.get("matched_concepts", [])),
                            "matched_shingles": 0,
                            "cross_lingual": cross_lingual_res,
                            "is_cross_lingual_discovered": True,
                        })

            # 6. Citation & Bibliography Consistency Auditor (Pilar 1 - Audit Konsistensi Sitasi)
            from worker.citation_auditor import audit_citations_and_bibliography
            citation_audit_res = audit_citations_and_bibliography(submission_text, segmentation_res)
            result["citation_audit"] = citation_audit_res.to_dict()

            # 7. Academic Integrity & Anti-Cheat Evasion Detector (Opsi 2)
            from worker.evasion_detector import detect_document_evasion
            evasion_res = detect_document_evasion(
                text=submission_text,
                storage_path=document.get("storage_path"),
                mime_type=document.get("mime_type")
            )
            result["evasion_detection"] = evasion_res.to_dict()

            # 8a. Metadata berkas (tanggal dibuat/diubah) sebagai konteks era pra-LLM
            doc_meta = None
            try:
                from worker.extractor import extract_document_metadata
                if document.get("storage_path") and os.path.exists(document["storage_path"]):
                    doc_meta = extract_document_metadata(document["storage_path"], document.get("mime_type"))
            except Exception as meta_err:
                print(f"  [METADATA WARNING] Gagal membaca metadata berkas Doc #{document_id}: {meta_err}")
            result["document_metadata"] = doc_meta

            # 8. Google Gemini AI Semantic Forensic Auditor (Pool Round-Robin)
            gemini_forensic_res = None
            try:
                from worker.gemini_forensic import audit_proposal_with_gemini
                gemini_forensic_res = audit_proposal_with_gemini(
                    document_text=submission_text,
                    substantive_text=subst_body_text,
                    title=document.get("title", ""),
                    connection=connection,
                    doc_metadata=doc_meta
                )
                result["gemini_forensic"] = gemini_forensic_res
            except Exception as gem_err:
                print(f"  [GEMINI FORENSIC WARNING] Gagal menjalankan audit Gemini untuk Doc #{document_id}: {gem_err}")
                result["gemini_forensic"] = {
                    "status": "skipped",
                    "is_available": False,
                    "message": f"Audit semantik Google Gemini dilewati: {gem_err}"
                }

            # 9. Self-Collision & Revision Protection Telemetry
            doc_user_id = document.get("user_id")
            excluded_user_corpus_count = 0
            if doc_user_id:
                try:
                    cursor.execute("""
                        SELECT COUNT(*) AS total
                        FROM documents
                        WHERE user_id = %s
                          AND document_type = 'corpus'
                          AND id != %s
                    """, (doc_user_id, document_id))
                    row_excluded = cursor.fetchone()
                    if row_excluded:
                        excluded_user_corpus_count = row_excluded.get("total", 0)
                except Exception:
                    pass

            result["self_collision_protection"] = {
                "active": True,
                "user_id": doc_user_id,
                "excluded_own_corpus_count": excluded_user_corpus_count,
                "note": "Naskah korpus milik sendiri dikecualikan dari pemindaian plagiarisme untuk mencegah false positive pada naskah revisi."
            }

            # Pemetaan rentang karakter token untuk menentukan zonasi setiap pasase
            import re
            token_spans = [m.span() for m in re.finditer(r"[^\W_]+(?:[-\'][^\W_]+)*", submission_text.lower(), re.UNICODE)]

            def _find_zone(token_idx: int) -> tuple[str, str]:
                if 0 <= token_idx < len(token_spans):
                    char_pos = token_spans[token_idx][0]
                    for s in segmentation_res.sections:
                        if s.start_char <= char_pos < s.end_char:
                            return s.section_zone.value, s.title
                return "UNKNOWN", "UNKNOWN"

            # Anotasi pasase individu dengan teks pasase, zonasi struktural & status boilerplate
            for match in result.get("matches", []):
                s_start = match.get("submission_start", 0)
                s_end = match.get("submission_end", s_start)
                zone_val, sec_title = _find_zone(s_start)
                match["zone"] = zone_val
                match["section_name"] = sec_title

                p_text = ""
                if 0 <= s_start < len(sub_tokens):
                    passage_slice = sub_tokens[s_start : s_end + 1]
                    p_text = " ".join(passage_slice)
                    match["passage_text"] = p_text

                bp_pass = detect_academic_boilerplate(p_text, zone=zone_val) if p_text else None
                if bp_pass and not bp_pass.is_boilerplate and 0 <= s_start < len(sub_tokens):
                    ctx_slice = sub_tokens[max(0, s_start - 2) : min(len(sub_tokens), s_end + 4)]
                    ctx_text = " ".join(ctx_slice)
                    bp_pass = detect_academic_boilerplate(ctx_text, zone=zone_val)

                if bp_pass:
                    match["is_boilerplate"] = bp_pass.is_boilerplate
                    match["boilerplate_category"] = bp_pass.category.value
                    match["boilerplate_phrase"] = bp_pass.matched_phrase
                    match["boilerplate_rationale"] = bp_pass.rationale
                    match["coverage_ratio"] = getattr(bp_pass, "coverage_ratio", 0.0)
                else:
                    match["is_boilerplate"] = False
                    match["boilerplate_category"] = "NONE"

                match["is_substantive"] = (
                    not match.get("is_boilerplate") and
                    zone_val not in ("FRONT_MATTER", "BACK_MATTER")
                )

            # Hitung metrik kemiripan substantif (di luar boilerplate dan zona administratif/pustaka)
            from worker.similarity import merge_ranges
            sub_matches = [m for m in result.get("matches", []) if m.get("is_substantive")]
            sub_longest = max((m.get("word_count", 0) for m in sub_matches), default=0)
            sub_ranges = [(m["submission_start"], m["submission_end"]) for m in sub_matches if "submission_start" in m]
            merged_sub = merge_ranges(sub_ranges)
            sub_covered_words = sum(end - start + 1 for start, end in merged_sub)
            sub_sim = round((sub_covered_words / max(1, doc_word_count)) * 100.0, 2)

            cursor.execute("SELECT COUNT(*) AS total_docs FROM documents WHERE document_type = 'corpus'")
            c_row = cursor.fetchone()
            total_corpus_docs = int(c_row["total_docs"] or 0) if c_row else 0

            # Re-evaluasi keputusan Hybrid dengan metrik substantif dan cakupan korpus
            if engine == "hybrid":
                from worker.hybrid import aggregate_evidence
                exact_ev = result["exact_evidence"]
                exact_ev["substantive_longest"] = sub_longest
                exact_ev["substantive_similarity"] = sub_sim
                exact_ev["substantive_words"] = sub_covered_words
                exact_ev["corpus_docs_count"] = total_corpus_docs

                re_analysis = aggregate_evidence(
                    exact_evidence=exact_ev,
                    tfidf_evidence=result["tfidf_evidence"],
                    corpus_docs_count=total_corpus_docs
                )

                predicted_decision = re_analysis["decision"]
                evidence_pattern = re_analysis["pattern"]
                evidence_agreement = re_analysis["agreement"]
                rationale = re_analysis["rationale"]

                result["predicted_decision"] = predicted_decision
                result["evidence_pattern"] = evidence_pattern
                result["evidence_agreement"] = evidence_agreement
                result["rationale"] = rationale
                result["substantive_longest_match_words"] = sub_longest
                result["substantive_similarity"] = sub_sim
                result["substantive_matched_words"] = sub_covered_words
                result["corpus_docs_count"] = total_corpus_docs

            result["document_title"] = document.get("title") or ""
            result["original_filename"] = document.get("original_filename") or ""

            result["substantive_summary"] = {
                "substantive_similarity": sub_sim,
                "substantive_longest_words": sub_longest,
                "substantive_matched_words": sub_covered_words,
                "total_words": doc_word_count,
                "corpus_docs_count": total_corpus_docs,
            }

            # 6. XGBoost Academic Risk Synthesizer (Penyintesis Risiko Akademik Berbobot ML)
            from worker.risk_classifier import predict_academic_risk_xgboost

            matches_list = result.get("matches", [])
            total_matches_cnt = len(matches_list)
            bp_count = sum(1 for m in matches_list if m.get("is_boilerplate"))
            bp_ratio = (bp_count / total_matches_cnt) if total_matches_cnt > 0 else (0.8 if boilerplate_res.is_boilerplate else 0.0)

            cited_count = sum(1 for m in matches_list if detect_citation_context(m.get("passage_text", "")).citation_status in ("CITED", "ATTRIBUTED"))
            q_ratio = (cited_count / total_matches_cnt) if total_matches_cnt > 0 else (0.8 if context_res.citation_status in ("CITED", "ATTRIBUTED") else 0.0)

            # Keadilan Akademik: Stylometri bersifat eksperimental dan belum divalidasi.
            # Jangan masukkan ke risk_classifier agar mahasiswa tidak terkena risiko vonis risiko AI.
            burstiness_val = 0.5
            trans_density_val = 0.0

            cl_score = 0.0
            if cross_lingual_res:
                if hasattr(cross_lingual_res, "cross_lingual_score"):
                    cl_score = float(cross_lingual_res.cross_lingual_score)
                elif isinstance(cross_lingual_res, dict):
                    cl_score = float(cross_lingual_res.get("cross_lingual_score", 0.0))

            xgboost_risk_res = predict_academic_risk_xgboost(
                exact_sim=exact_sim,
                cosine_sim=cosine_sim,
                longest_match_words=longest_match,
                avg_idf=avg_idf,
                shared_terms_count=shared_terms_count,
                source_count=source_count,
                burstiness=burstiness_val,
                transition_density=trans_density_val,
                cross_lingual_score=cl_score,
                boilerplate_ratio=bp_ratio,
                quote_ratio=q_ratio
            )
            result["xgboost_risk"] = xgboost_risk_res
            result["jit_harvesting"] = jit_harvest_res

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
                xgboost_risk=xgboost_risk_res,
                jit_harvesting=jit_harvest_res,
                citation_audit=citation_audit_res,
                evasion_detection=evasion_res,
                gemini_forensic=gemini_forensic_res,
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

        # 9. Notifikasi Email Pengguna Otomatis (Failsafe)
        try:
            from worker.email_notifier import notify_user_report_completed
            c_score = None
            if citation_audit_res is not None and hasattr(citation_audit_res, "health_score"):
                c_score = float(citation_audit_res.health_score)
            ai_score = None
            if isinstance(ai_stylometrics_res, dict) and "ai_probability_score" in ai_stylometrics_res:
                ai_score = float(ai_stylometrics_res["ai_probability_score"])
            ev_score = None
            if evasion_res is not None and hasattr(evasion_res, "evasion_risk_score"):
                ev_score = float(evasion_res.evasion_risk_score)
            cl_score = None
            if cross_lingual_res and isinstance(cross_lingual_res, dict) and cross_lingual_res.get("is_cross_lingual_match"):
                cl_score = float(cross_lingual_res.get("cross_lingual_score", 0.0))

            notify_user_report_completed(
                document_id=document_id,
                report_id=report_id,
                document_title=document.get("title", f"Dokumen #{document_id}"),
                decision=predicted_decision,
                overall_similarity=overall_sim,
                exact_similarity=exact_sim,
                cosine_similarity=cosine_sim,
                citation_health_score=c_score,
                ai_probability_score=ai_score,
                evasion_risk_score=ev_score,
                cross_lingual_score=cl_score,
            )
        except Exception as mail_err:
            print(f"  [EMAIL WARNING] Gagal mengirim notifikasi email untuk Report #{report_id}: {mail_err}")

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

        # Kembalikan kuota submisi ke pengguna jika report gagal akibat kesalahan sistem worker
        try:
            cursor.execute("""
                SELECT d.user_id 
                FROM documents d 
                JOIN reports r ON r.document_id = d.id 
                WHERE r.id = %s
            """, (report_id,))
            user_row = cursor.fetchone()
            if user_row and user_row[0]:
                uid = user_row[0]
                cursor.execute("""
                    UPDATE users 
                    SET free_submissions_used_this_week = free_submissions_used_this_week - 1,
                        updated_at = NOW()
                    WHERE id = %s AND free_submissions_used_this_week > 0
                """, (uid,))
                if cursor.rowcount == 0:
                    cursor.execute("""
                        UPDATE users 
                        SET bonus_submissions = bonus_submissions + 1,
                            updated_at = NOW()
                        WHERE id = %s
                    """, (uid,))
                print(f"  [QUOTA REFUND] Kuota berhasil dikembalikan untuk User #{uid} (Report #{report_id} gagal).")
        except Exception as q_err:
            print(f"  [QUOTA REFUND WARNING] Gagal mengembalikan kuota: {q_err}")

        connection.commit()
        cursor.close()
        raise e
    finally:
        connection.close()
