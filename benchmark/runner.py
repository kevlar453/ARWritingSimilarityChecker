import argparse
import sys
from pathlib import Path
from collections import Counter

# Ensure project root is in sys.path
sys.path.append(str(Path(__file__).resolve().parents[1]))

from benchmark.config import (
    MATCH_SIMILARITY,
    REVIEW_SIMILARITY,
    TFIDF_MATCH_SIMILARITY,
    MATCH_LONGEST,
    REVIEW_LONGEST,
    ENGINE_EXACT,
    ENGINE_IDF,
    ENGINE_TFIDF,
    ENGINE_HYBRID
)
from benchmark.database import get_connection
from benchmark.dataset import generate_dataset
from benchmark.stress_dataset import generate_stress_dataset
from benchmark.indexer import (
    create_document,
    create_corpus,
    index_text,
    rebuild_fingerprint_stats
)
from benchmark.metrics import (
    calculate_binary_metrics,
    calculate_multiclass_metrics,
    classify_error
)
from benchmark.engines import run_engine

def classify_similarity(
    similarity: float,
    longest_match_words: int,
    engine: str = "exact",
    avg_idf: float = 0.0,
    max_idf: float = 0.0
) -> str:
    """
    Klasifikasi hasil similarity menjadi:
    - match: Kemiripan kuat/identik
    - review: Ada indikasi kemiripan yang perlu ditinjau
    - no_match: Tidak ada kemiripan signifikan
    """
    if engine == ENGINE_TFIDF:
        if similarity >= TFIDF_MATCH_SIMILARITY:
            return "match"

        if similarity >= REVIEW_SIMILARITY:
            return "review"

        return "no_match"

    if engine == ENGINE_IDF:
        # Pada engine IDF: Jika hanya frasa umum dengan IDF sangat rendah (common phrase),
        # tidak boleh diangkat menjadi 'match', melainkan 'review' atau 'no_match'
        if max_idf == 0.0 and avg_idf == 0.0:
            if similarity >= REVIEW_SIMILARITY or longest_match_words >= REVIEW_LONGEST:
                return "review"
            return "no_match"
            
    if similarity >= MATCH_SIMILARITY or longest_match_words >= MATCH_LONGEST:
        return "match"
    if similarity >= REVIEW_SIMILARITY or longest_match_words >= REVIEW_LONGEST:
        return "review"
    return "no_match"

def create_benchmark_run(connection, engine: str, total_cases: int, run_name: str = "Academic Checker Benchmark") -> int:
    cursor = connection.cursor()
    cursor.execute("""
        INSERT INTO benchmark_runs (name, engine_version, total_cases, created_at)
        VALUES (%s, %s, %s, NOW())
    """, (run_name, engine, total_cases))
    run_id = cursor.lastrowid
    connection.commit()
    cursor.close()
    return run_id

def create_test_case(connection, case: dict, document_id: int) -> int:
    cursor = connection.cursor()
    cursor.execute("""
        INSERT INTO test_cases (name, category, source_document_id, submission_document_id, expected_result, created_at)
        VALUES (%s, %s, NULL, %s, %s, NOW())
    """, (case["name"], case["category"], document_id, case["expected"]))
    tc_id = cursor.lastrowid
    connection.commit()
    cursor.close()
    return tc_id

def run_case(connection, run_id: int, case: dict, engine: str) -> dict:
    doc_id = create_document(
        connection=connection,
        title=case["name"],
        document_type="submission",
        benchmark_run_id=run_id
    )
    index_text(connection, doc_id, case["submission"], is_corpus=False)
    tc_id = create_test_case(connection, case, doc_id)
    connection.commit()
    
    result = run_engine(connection, doc_id, engine, run_id)
    
    if engine == ENGINE_HYBRID:
        similarity_val = max(result["exact_similarity"], result["cosine_similarity"])
        predicted = result["predicted_decision"]
        matched_words = result["matched_words"]
        longest_match = result["longest_match_words"]
        avg_idf = result.get("average_shared_term_idf", 0.0)
        max_idf = result["exact_evidence"].get("max_idf", 0.0) or 0.0
    elif engine == ENGINE_TFIDF:
        similarity_val = result["cosine_similarity"]
        avg_idf = result.get("average_shared_term_idf", 0.0)
        max_idf = 0.0
        predicted = classify_similarity(
            similarity=similarity_val,
            longest_match_words=0,
            engine=engine,
            avg_idf=avg_idf
        )
        matched_words = result.get("shared_terms_count", 0)
        longest_match = 0
    else:
        similarity_val = result["overall_similarity"]
        matches = result.get("matches", [])
        avg_idf = sum(m.get("average_idf", 0.0) for m in matches) / len(matches) if matches else 0.0
        max_idf = max((m.get("max_idf", 0.0) for m in matches), default=0.0)
        predicted = classify_similarity(
            similarity=similarity_val,
            longest_match_words=result["longest_match_words"],
            engine=engine,
            avg_idf=avg_idf,
            max_idf=max_idf
        )
        matched_words = result["matched_words"]
        longest_match = result["longest_match_words"]
    
    error_type = classify_error(case["expected"], predicted)
    
    return {
        "test_case_id": tc_id,
        "case_name": case["name"],
        "category": case["category"],
        "expected": case["expected"],
        "predicted": predicted,
        "similarity": similarity_val,
        "matched_words": matched_words,
        "longest_match_words": longest_match,
        "source_count": result["source_count"],
        "match_count": result["match_count"],
        "average_idf": round(avg_idf, 4),
        "max_idf": round(max_idf, 4),
        "error_type": error_type
    }

def save_results(connection, run_id: int, results: list[dict]):
    cursor = connection.cursor()
    for res in results:
        cursor.execute("""
            INSERT INTO benchmark_results (
                benchmark_run_id, test_case_id, expected_result, predicted_result,
                similarity, matched_words, longest_match_words, source_count,
                match_count, error_type, created_at
            )
            VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, NOW())
        """, (
            run_id,
            res["test_case_id"],
            res["expected"],
            res["predicted"],
            res["similarity"],
            res["matched_words"],
            res["longest_match_words"],
            res["source_count"],
            res["match_count"],
            res["error_type"]
        ))
    connection.commit()
    cursor.close()

def update_run_metrics(connection, run_id: int, binary: dict, multiclass: dict):
    cursor = connection.cursor()
    cursor.execute("""
        UPDATE benchmark_runs
        SET precision_score = %s,
            recall_score = %s,
            f1_score = %s,
            macro_f1 = %s
        WHERE id = %s
    """, (
        binary["precision"],
        binary["recall"],
        binary["f1"],
        multiclass["macro_f1"],
        run_id
    ))
    connection.commit()
    cursor.close()

def print_report(run_id: int, engine: str, dataset_type: str, results: list[dict], binary: dict, multiclass: dict):
    print("\n" + "=" * 65)
    print("           AR TILIK - BENCHMARK REPORT RESULTS")
    print("=" * 65)
    print(f"Run ID       : {run_id}")
    print(f"Engine Mode  : {engine.upper()}")
    print(f"Dataset Type : {dataset_type.upper()} ({len(results)} cases)")
    print(f"Total Cases  : {len(results)}")
    
    print("\n[1] Binary Similarity Detection (Similar vs Not-Similar)")
    print("-" * 50)
    print(f"TP : {binary['tp']:<4} | FP : {binary['fp']:<4} | TN : {binary['tn']:<4} | FN : {binary['fn']:<4}")
    print(f"Precision    : {binary['precision']:.4f}")
    print(f"Recall       : {binary['recall']:.4f}")
    print(f"F1 Score     : {binary['f1']:.4f}")
    
    print("\n[2] Multiclass Evaluation (MATCH / REVIEW / NO_MATCH)")
    print("-" * 50)
    for label, val in multiclass["classes"].items():
        print(f"{label.upper():<10} -> Precision: {val['precision']:.4f} | Recall: {val['recall']:.4f} | F1: {val['f1']:.4f}")
    print(f"\nMacro F1     : {multiclass['macro_f1']:.4f}")

def print_confusion_matrix(multiclass: dict):
    matrix = multiclass["matrix"]
    labels = ["match", "review", "no_match"]
    print("\n[3] Confusion Matrix")
    print("-" * 50)
    print(f"{'EXPECTED \\ PRED':<16}{'MATCH':>10}{'REVIEW':>12}{'NO_MATCH':>12}")
    print("-" * 50)
    for expected in labels:
        row_str = f"{expected:<16}"
        for pred in labels:
            row_str += f"{matrix[expected].get(pred, 0):>10}  "
        print(row_str)

def print_errors(results: list[dict]):
    errors = [r for r in results if r["error_type"]]
    print("\n[4] Error Analysis")
    print("-" * 50)
    print(f"Total Misclassified Cases: {len(errors)}")
    
    if errors:
        counter = Counter((r["category"], r["error_type"]) for r in errors)
        for (cat, err_type), cnt in sorted(counter.items()):
            print(f" - {cat:<20} [{err_type:<16}] : {cnt} case(s)")
            
        print("\nDetail Kasus yang Salah:")
        for r in errors[:10]:  # Tampilkan 10 teratas
            print(f" * {r['case_name']} [{r['category']}] -> Exp: {r['expected']}, Pred: {r['predicted']}, Sim: {r['similarity']}%, IDF: {r['average_idf']}")

def run_benchmark(engine: str, dataset_type: str = "standard"):
    if dataset_type == "stress":
        dataset = generate_stress_dataset()
        run_name = "Academic Checker Stress Benchmark"
    else:
        dataset = generate_dataset()
        run_name = "Academic Checker Benchmark"
        
    conn = get_connection()
    try:
        run_id = create_benchmark_run(conn, engine, len(dataset), run_name)
        print("=" * 65)
        print(f"Memulai Benchmark Run #{run_id} (Engine: {engine.upper()} | Dataset: {dataset_type.upper()})")
        print("=" * 65)
        
        print("[*] Mengindeks korpus 20 dokumen benchmark...")
        corpus = create_corpus(conn, run_id)
        print(f"[✓] {len(corpus)} korpus dokumen berhasil di-indeks.")
        
        print("[*] Memperbarui statistik Document Frequency (DF)...")
        rebuild_fingerprint_stats(conn, run_id)
        print("[✓] Statistik DF selesai dihitung.")
        
        print(f"\n[*] Menjalankan {len(dataset)} kasus uji ({dataset_type})...")
        results = []
        for index, case in enumerate(dataset, start=1):
            res = run_case(conn, run_id, case, engine)
            results.append(res)
            status = "✓" if res["expected"] == res["predicted"] else "✗"
            print(f" [{index:02d}/{len(dataset)}] {status} {case['name']} ({case['category']}) -> {res['predicted']} (sim: {res['similarity']}%)")
            
        metric_input = [
            {"expected": r["expected"], "predicted": r["predicted"]}
            for r in results
        ]
        
        binary = calculate_binary_metrics(metric_input)
        multiclass = calculate_multiclass_metrics(metric_input)
        
        save_results(conn, run_id, results)
        update_run_metrics(conn, run_id, binary, multiclass)
        
        print_report(run_id, engine, dataset_type, results, binary, multiclass)
        print_confusion_matrix(multiclass)
        print_errors(results)
        
        return run_id, results
    finally:
        conn.close()

def main():
    parser = argparse.ArgumentParser(description="AR Tilik Benchmark Runner")
    parser.add_argument("--engine", choices=[ENGINE_EXACT, ENGINE_IDF, ENGINE_TFIDF, ENGINE_HYBRID], required=True, help="Engine type to test")
    parser.add_argument("--dataset", choices=["standard", "stress"], default="standard", help="Dataset type: standard (50 cases) or stress (50 boundary/stress cases)")
    args = parser.parse_args()
    run_benchmark(args.engine, args.dataset)

if __name__ == "__main__":
    main()
