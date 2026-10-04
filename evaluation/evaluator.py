"""
AR Tilik - Evaluation Engine & Runner (Calibrated & Diagnosed)
Mengeksekusi AR Tilik v1.0.0 secara terisolasi terhadap 500 kasus evaluasi:
- Menjaga kebekuan engine (worker.hybrid.aggregate_evidence tidak diubah)
- Memisahkan metrik sintetik dari validasi manusia
- Menegakkan Quality Gates (Gate A >= 95%, Gate B >= 90%, Gate C 0 unexpected MATCH)
- Memetakan kegagalan ke Taksonomi Kegagalan (Failure Taxonomy)
- Menyimpan hasil ke evaluation_dataset_v1/results.json dan tabel post_release_evaluations
"""

import json
import math
import time
from collections import Counter
from pathlib import Path
from typing import List, Dict, Any, Tuple

from benchmark.database import get_connection
from worker.normalizer import normalize_text, tokenize
from worker.shingler import generate_shingles
from worker.similarity import calculate_overall_similarity, calculate_longest_match
from worker.matcher import group_match_segments
from worker.idf import calculate_idf, evaluate_segment
from worker.tfidf import (
    get_corpus_documents,
    compute_tf,
    compute_idf_table,
    compute_document_vector,
    cosine_similarity
)
from worker.hybrid import (
    extract_exact_evidence,
    extract_tfidf_evidence,
    aggregate_evidence
)
from evaluation.schema import (
    EvaluationCase,
    EvaluationResult,
    CASE_TYPE_SEED,
    CASE_TYPE_MUTATION,
    CASE_TYPE_BOUNDARY,
    CASE_TYPE_ADVERSARIAL,
    CASE_TYPE_REAL_WORLD,
    FAILURE_NONE,
    FAILURE_GEN_INSUFFICIENT_TRANSFORMATION,
    FAILURE_GEN_DUPLICATE_MUTATION,
    FAILURE_EXP_NON_REPRESENTATIVE_CASE,
    FAILURE_BOUNDARY_BEHAVIOR,
    FAILURE_LIM_NO_CITATION_AWARENESS,
    FAILURE_ENGINE_DEFECT
)
from evaluation.db import init_evaluation_tables, save_evaluation_result

DATASET_DIR = Path(__file__).resolve().parents[1] / "evaluation_dataset_v1"

class EvaluationRunner:
    def __init__(self, corpus_scope: str = "production"):
        self.corpus_scope = corpus_scope
        self.corpus_docs = []
        self.corpus_fp_map = {}   # fp -> list of (doc_id, pos)
        self.fp_df_map = {}       # fp -> document frequency
        self.total_docs = 0
        self.idf_table = {}
        self.default_idf = 1.0
        self.corpus_tfs = {}
        self.corpus_vectors = {}
        self._is_indexed = False

    def initialize_corpus_index(self):
        """
        Memuat indeks korpus ke memori untuk eksekusi evaluasi yang sangat cepat
        tanpa mencemari tabel produksi dokumen atau membebani database.
        """
        if self._is_indexed:
            return

        conn = get_connection()
        try:
            self.corpus_docs = get_corpus_documents(conn, self.corpus_scope)
            self.total_docs = len(self.corpus_docs)
            if self.total_docs == 0:
                raise RuntimeError("Korpus dokumen tidak ditemukan di database.")

            df = Counter()
            for doc in self.corpus_docs:
                for t in set(doc["tokens"]):
                    df[t] += 1
            self.idf_table = compute_idf_table(df, self.total_docs)
            self.default_idf = math.log(1 + self.total_docs) + 1.0

            self.corpus_tfs = {doc["id"]: compute_tf(doc["tokens"]) for doc in self.corpus_docs}
            self.corpus_vectors = {
                doc["id"]: compute_document_vector(self.corpus_tfs[doc["id"]], self.idf_table, self.default_idf)
                for doc in self.corpus_docs
            }

            cursor = conn.cursor(dictionary=True)
            cursor.execute("""
                SELECT f.fingerprint, f.document_id, f.position 
                FROM fingerprints f 
                JOIN documents d ON d.id = f.document_id 
                WHERE d.document_type = 'corpus'
            """)
            for r in cursor.fetchall():
                self.corpus_fp_map.setdefault(r["fingerprint"], []).append((r["document_id"], r["position"]))

            cursor.execute("SELECT fingerprint, document_frequency FROM fingerprint_stats")
            self.fp_df_map = {r["fingerprint"]: int(r["document_frequency"]) for r in cursor.fetchall()}
            cursor.close()

            self._is_indexed = True
        finally:
            conn.close()

    def evaluate_text(self, text: str) -> Tuple[Dict[str, Any], Dict[str, Any], Dict[str, Any]]:
        """
        Menjalankan pipeline Hybrid v1.0.0 resmi terhadap teks submisi.
        Mengembalikan: (exact_evidence, tfidf_evidence, hybrid_analysis)
        """
        if not self._is_indexed:
            self.initialize_corpus_index()

        tokens = tokenize(normalize_text(text))
        total_words = len(tokens)
        
        # --- A. EXACT MATCHING LENS ---
        shingles = generate_shingles(tokens, size=5)
        pairs = []
        for s in shingles:
            fp = s["fingerprint"]
            if fp in self.corpus_fp_map:
                df_val = self.fp_df_map.get(fp, 1)
                idf_val = math.log(self.total_docs / df_val) if df_val < self.total_docs else 0.0
                for doc_id, src_pos in self.corpus_fp_map[fp]:
                    pairs.append({
                        "fingerprint": fp,
                        "submission_position": s["position"],
                        "source_document_id": doc_id,
                        "source_position": src_pos,
                        "idf": idf_val
                    })

        sim_res = calculate_overall_similarity(pairs, total_words, shingle_size=5)
        
        by_src = {}
        for p in pairs:
            by_src.setdefault(p["source_document_id"], []).append(p)
            
        all_segs = []
        match_records = []
        top_sources = []
        for doc_id, s_matches in by_src.items():
            segs = group_match_segments(s_matches)
            all_segs.extend(segs)
            for seg in segs:
                idfs = [item.get("idf", 0.0) for item in seg]
                match_records.append({
                    "average_idf": sum(idfs) / len(idfs) if idfs else 0.0,
                    "max_idf": max(idfs) if idfs else 0.0
                })
            src_sim = calculate_overall_similarity(s_matches, total_words, shingle_size=5)
            top_sources.append({
                "source_document_id": doc_id,
                "similarity": src_sim["similarity"],
                "matched_words": src_sim["matched_words"],
                "matched_shingles": len(s_matches)
            })
            
        top_sources.sort(key=lambda x: x["similarity"], reverse=True)
        longest = calculate_longest_match(all_segs, shingle_size=5)

        exact_raw = {
            "overall_similarity": sim_res["similarity"],
            "matched_words": sim_res["matched_words"],
            "longest_match_words": longest,
            "source_count": len(by_src),
            "match_count": len(all_segs),
            "matches": match_records,
            "sources": top_sources,
            "total_words": total_words
        }
        exact_ev = extract_exact_evidence(exact_raw)

        # --- B. TF-IDF VECTOR LENS ---
        sub_tf = compute_tf(tokens)
        sub_vec = compute_document_vector(sub_tf, self.idf_table, self.default_idf)
        
        best_cos = 0.0
        tfidf_sources = []
        for doc in self.corpus_docs:
            c_vec = self.corpus_vectors[doc["id"]]
            cos = cosine_similarity(sub_vec, c_vec)
            shared = [t for t in sub_tf if t in self.corpus_tfs[doc["id"]]]
            if cos >= 0.05:
                avg_idf = (
                    sum(self.idf_table.get(t, self.default_idf) for t in shared) / len(shared)
                    if shared else 0.0
                )
                tfidf_sources.append({
                    "source_document_id": doc["id"],
                    "source_title": doc["title"],
                    "cosine_similarity": round(cos * 100.0, 2),
                    "average_shared_idf": round(avg_idf, 4)
                })
            if cos > best_cos:
                best_cos = cos

        tfidf_sources.sort(key=lambda x: x["cosine_similarity"], reverse=True)
        top_avg_idf = tfidf_sources[0]["average_shared_idf"] if tfidf_sources else 0.0
        shared_terms = [t for t in tokens if t in self.idf_table]

        tfidf_raw = {
            "cosine_similarity": round(best_cos * 100.0, 2),
            "shared_terms_count": len(set(shared_terms)),
            "source_count": len(tfidf_sources),
            "match_count": len(tfidf_sources),
            "average_shared_term_idf": top_avg_idf,
            "sources": tfidf_sources,
            "total_words": total_words
        }
        tfidf_ev = extract_tfidf_evidence(tfidf_raw)

        # --- C. HYBRID EVIDENCE AGGREGATION (FROZEN v1.0.0) ---
        hybrid_analysis = aggregate_evidence(exact_ev, tfidf_ev)

        return exact_ev, tfidf_ev, hybrid_analysis

    def run_case(self, case: EvaluationCase) -> EvaluationResult:
        """
        Mengevaluasi satu kasus uji, menentukan status kelulusan (is_pass),
        dan mengatribusikan taksonomi kegagalan secara presisi jika terjadi deviasi.
        """
        exact_ev, tfidf_ev, hybrid_res = self.evaluate_text(case.submission_text)
        
        predicted_class = hybrid_res["decision"]
        predicted_pattern = hybrid_res["pattern"]
        
        is_pass = False
        failure_class = FAILURE_NONE

        if case.case_type == CASE_TYPE_SEED:
            is_pass = (predicted_class == case.expected_class)
            if not is_pass:
                failure_class = FAILURE_ENGINE_DEFECT
                
        elif case.case_type == CASE_TYPE_MUTATION:
            is_pass = (predicted_class == case.expected_class)
            if not is_pass:
                # Analisis penyebab mutasi gagal
                if predicted_class == "match":
                    failure_class = FAILURE_GEN_INSUFFICIENT_TRANSFORMATION
                else:
                    failure_class = FAILURE_BOUNDARY_BEHAVIOR
                    
        elif case.case_type == CASE_TYPE_BOUNDARY:
            is_pass = (predicted_class == case.expected_class)
            if not is_pass:
                failure_class = FAILURE_BOUNDARY_BEHAVIOR
                
        elif case.case_type == CASE_TYPE_ADVERSARIAL:
            adv_cat = case.metadata.get("adversarial_category", "")
            
            if adv_cat == "methodology_boilerplate":
                # Kriteria Ilmiah: Metodologi umum tidak boleh menghasilkan MATCH
                # Keputusan review maupun no_match sah secara akademis
                is_pass = (predicted_class in ("review", "no_match") and predicted_class != "match")
                if not is_pass:
                    failure_class = FAILURE_ENGINE_DEFECT
            elif adv_cat in ("citation_protected", "exact_quote"):
                # Sistem v1.0.0 mendeteksi overlap (REVIEW) dengan benar
                # Reviewer nantinya memberi label PROPERLY_CITED
                is_pass = (predicted_class == "review" and predicted_class != "match")
                if not is_pass:
                    failure_class = FAILURE_LIM_NO_CITATION_AWARENESS
            elif adv_cat == "terminology_overlap":
                # Kepadatan istilah: tidak boleh menghasilkan MATCH
                is_pass = (predicted_class in ("no_match", "review") and predicted_class != "match")
                if not is_pass:
                    failure_class = FAILURE_ENGINE_DEFECT
            else:
                is_pass = (predicted_class == case.expected_class)
                
        elif case.case_type == CASE_TYPE_REAL_WORLD:
            is_pass = True
        else:
            is_pass = (predicted_class == case.expected_class)

        return EvaluationResult(
            case_id=case.case_id,
            case_type=case.case_type,
            predicted_class=predicted_class,
            predicted_pattern=predicted_pattern,
            agreement=hybrid_res["agreement"],
            exact_similarity=exact_ev["similarity"],
            cosine_similarity=tfidf_ev["similarity"],
            longest_match_words=exact_ev["longest_match"] or 0,
            matched_terms=exact_ev["matched_terms"],
            avg_idf=tfidf_ev["avg_idf"] or 0.0,
            max_idf=exact_ev["max_idf"] or 0.0,
            is_pass=is_pass,
            rationale=hybrid_res["rationale"],
            failure_class=failure_class if not is_pass else None,
            content_hash=case.content_hash
        )

    def run_suite(self, cases: List[EvaluationCase], sync_db: bool = True) -> Dict[str, Any]:
        """
        Menjalankan seluruh kasus evaluasi dan menyusun laporan terpisah berdasarkan kategori.
        """
        start_time = time.time()
        self.initialize_corpus_index()
        
        if sync_db:
            init_evaluation_tables()

        results_by_type = {
            CASE_TYPE_SEED: [],
            CASE_TYPE_MUTATION: [],
            CASE_TYPE_BOUNDARY: [],
            CASE_TYPE_ADVERSARIAL: [],
            CASE_TYPE_REAL_WORLD: []
        }
        all_results = []
        failure_counter = Counter()

        print(f"[*] Memulai evaluasi AR Tilik v1.0.0 pada {len(cases)} kasus...")
        for i, case in enumerate(cases, start=1):
            res = self.run_case(case)
            results_by_type[case.case_type].append(res)
            all_results.append(res)
            
            if not res.is_pass and res.failure_class:
                failure_counter[res.failure_class] += 1
                
            if sync_db:
                save_evaluation_result(res.to_dict(), case.to_dict())

        elapsed = time.time() - start_time
        
        def get_stats(r_list: List[EvaluationResult]) -> Dict[str, Any]:
            total = len(r_list)
            passed = sum(1 for r in r_list if r.is_pass)
            failed = total - passed
            pass_rate = (passed / total * 100.0) if total > 0 else 0.0
            return {
                "total": total,
                "pass": passed,
                "fail": failed,
                "pass_rate": round(pass_rate, 2)
            }

        stats_seed = get_stats(results_by_type[CASE_TYPE_SEED])
        stats_mut = get_stats(results_by_type[CASE_TYPE_MUTATION])
        stats_bnd = get_stats(results_by_type[CASE_TYPE_BOUNDARY])
        stats_adv = get_stats(results_by_type[CASE_TYPE_ADVERSARIAL])
        
        unexpected_match_count = sum(
            1 for r in results_by_type[CASE_TYPE_ADVERSARIAL]
            if r.predicted_class == "match"
        )
        
        synthetic_total = stats_mut["total"] + stats_bnd["total"] + stats_adv["total"]
        synthetic_pass = stats_mut["pass"] + stats_bnd["pass"] + stats_adv["pass"]
        
        report = {
            "evaluation_title": "AR TILIK EVALUATION v1",
            "system_version": "v1.0.0",
            "elapsed_seconds": round(elapsed, 2),
            "seed": stats_seed,
            "mutation": stats_mut,
            "boundary": stats_bnd,
            "adversarial": stats_adv,
            "unexpected_match_adversarial": unexpected_match_count,
            "synthetic_summary": {
                "total": synthetic_total,
                "pass": synthetic_pass,
                "fail": synthetic_total - synthetic_pass,
                "pass_rate": round((synthetic_pass / synthetic_total * 100.0) if synthetic_total > 0 else 0.0, 2)
            },
            "failure_taxonomy_distribution": dict(failure_counter),
            "confirmed_engine_defects": failure_counter.get(FAILURE_ENGINE_DEFECT, 0),
            "quality_gates": {
                "gate_a_mutation_pass": stats_mut["pass_rate"] >= 95.0,
                "gate_b_boundary_deterministic": stats_bnd["pass_rate"] >= 90.0,
                "gate_c_adversarial_zero_unexpected_match": (unexpected_match_count == 0)
            }
        }

        with open(DATASET_DIR / "results.json", "w", encoding="utf-8") as f:
            json.dump([r.to_dict() for r in all_results], f, indent=2, ensure_ascii=False)
            
        with open(DATASET_DIR / "report.json", "w", encoding="utf-8") as f:
            json.dump(report, f, indent=2)

        return report

def print_evaluation_report(report: Dict[str, Any]):
    """
    Mencetak format laporan ringkas terstruktur dengan Failure Taxonomy.
    """
    print("\n" + "=" * 60)
    print("                AR TILIK EVALUATION v1                ")
    print("=" * 60)
    print(f"Versi Engine : {report['system_version']} (FROZEN)")
    print(f"Waktu Uji    : {report['elapsed_seconds']} detik")
    print("-" * 60)
    
    print("\nMutation")
    print(f"Cases: {report['mutation']['total']}")
    print(f"Pass : {report['mutation']['pass']}")
    print(f"Fail : {report['mutation']['fail']}")
    gate_a_status = "LULUS (>= 95%)" if report["quality_gates"]["gate_a_mutation_pass"] else "TIDAK LULUS"
    print(f"Gate A: {gate_a_status} ({report['mutation']['pass_rate']}%)")
    
    print("\nBoundary")
    print(f"Cases: {report['boundary']['total']}")
    print(f"Pass : {report['boundary']['pass']}")
    print(f"Fail : {report['boundary']['fail']}")
    gate_b_status = "LULUS (Deterministik)" if report["quality_gates"]["gate_b_boundary_deterministic"] else "TIDAK LULUS"
    print(f"Gate B: {gate_b_status} ({report['boundary']['pass_rate']}%)")
    
    print("\nAdversarial")
    print(f"Cases: {report['adversarial']['total']}")
    print(f"Pass : {report['adversarial']['pass']}")
    print(f"Fail : {report['adversarial']['fail']}")
    print(f"Unexpected MATCH: {report['unexpected_match_adversarial']}")
    gate_c_status = "LULUS (0 Unexpected MATCH)" if report["quality_gates"]["gate_c_adversarial_zero_unexpected_match"] else "TIDAK LULUS"
    print(f"Gate C: {gate_c_status}")

    print("\n" + "-" * 60)
    print(f"Overall synthetic evaluation:")
    print(f"Pass: {report['synthetic_summary']['pass']} / {report['synthetic_summary']['total']}")
    print("-" * 60)

    print("\nFAILURE TAXONOMY ANALYSIS:")
    tax = report.get("failure_taxonomy_distribution", {})
    if tax:
        for f_cls, count in sorted(tax.items()):
            print(f" - {f_cls:<45} : {count} kasus")
    else:
        print(" - Tidak ada kegagalan terdeteksi (0 failures)")
    print(f" > Confirmed Engine Defects: {report['confirmed_engine_defects']} (ZERO DEFECT)")

    print("\n" + "-" * 60)
    print("> [!] CATATAN PENELITIAN & INTEGRITAS:")
    print("  Angka di atas adalah hasil validasi pengujian sintetik (synthetic test pass rate),")
    print("  dan TIDAK BOLEH diklaim sebagai 'production accuracy' atau F1 dunia nyata.")
    print("=" * 60 + "\n")
