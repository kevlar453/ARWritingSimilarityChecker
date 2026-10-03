import argparse
import sys
from pathlib import Path
from collections import Counter

sys.path.append(
    str(Path(__file__).resolve().parents[1])
)

from benchmark.database import get_connection
from benchmark.engines import run_engine

def get_test_case(
    connection,
    run_id: int,
    case_name: str
):
    cursor = connection.cursor(dictionary=True)

    cursor.execute(
        """
        SELECT
            tc.id,
            tc.name,
            tc.category,
            tc.expected_result,
            tc.submission_document_id
        FROM test_cases tc
        INNER JOIN documents d
            ON d.id = tc.submission_document_id
        WHERE tc.name = %s
          AND d.benchmark_run_id = %s
        ORDER BY tc.id DESC
        LIMIT 1
        """,
        (case_name, run_id)
    )

    row = cursor.fetchone()
    cursor.close()

    return row

def print_match(match, index):
    print()
    print("-" * 70)
    print(f"MATCH #{index}")
    print("-" * 70)

    print(f"Source document : {match['source_document_id']}")
    print(f"Submission      : kata {match['submission_start']} - {match['submission_end']}")
    print(f"Source          : kata {match['source_start']} - {match['source_end']}")
    print(f"Word count      : {match['word_count']} kata")
    print(f"Submission page : {match['submission_page']}")
    print(f"Source page     : {match['source_page']}")
    print(f"Match type      : {match['match_type']}")
    print(f"Average IDF     : {match['average_idf']:.6f}")
    print(f"Max IDF         : {match['max_idf']:.6f}")
    print(f"Significance    : {match['significance']:.4f}")
    print(f"Commonness      : {match['commonness']}")

def diagnose_case(
    connection,
    run_id: int,
    case_name: str,
    engine: str
):
    case = get_test_case(
        connection,
        run_id,
        case_name
    )

    if not case:
        print(f"[!] Case {case_name} tidak ditemukan pada Run #{run_id}.")
        return

    print()
    print("=" * 70)
    print(f"         AR TILIK - DIAGNOSTIC REPORT: {case_name}")
    print("=" * 70)

    print(f"Run ID          : {run_id}")
    print(f"Case            : {case['name']}")
    print(f"Category        : {case['category']}")
    print(f"Expected        : {case['expected_result']}")
    print(f"Submission ID   : {case['submission_document_id']}")
    print(f"Engine          : {engine.upper()}")

    result = run_engine(
        connection,
        case["submission_document_id"],
        engine,
        run_id
    )

    print()
    print("-" * 70)
    print("SUMMARY")
    print("-" * 70)

    print(f"Total words     : {result['total_words']}")

    if engine == "hybrid":
        print(f"Hybrid Decision : {result['predicted_decision'].upper()}")
        print(f"Evidence Pattern: {result['evidence_pattern']}")
        print(f"Evidence Agreement: {result['evidence_agreement']}")
        print(f"Sources Agree   : {'YES' if result['sources_agree'] else 'NO'}")
        print()
        print("-" * 70)
        print("EXPLAINABILITY RATIONALE")
        print("-" * 70)
        print(f" {result['rationale']}")
        print()
        print("-" * 70)
        print("SIDE-BY-SIDE EVIDENCE COMPARISON")
        print("-" * 70)
        print(f" {'METRIK':<24} | {'EXACT EVIDENCE':<19} | {'TF-IDF EVIDENCE':<20}")
        print("-" * 70)
        print(f" {'Similarity Score':<24} | {result['exact_similarity']}%{'':<12} | {result['cosine_similarity']}%")
        print(f" {'Evidence Terms/Words':<24} | {result['matched_words']} matched words{'':<3} | {result['shared_terms_count']} distinct terms")
        print(f" {'Longest Contiguous':<24} | {result['longest_match_words']} words{'':<11} | N/A (Topical Model)")
        print(f" {'Significance / Avg IDF':<24} | Max IDF: {result['exact_evidence']['max_idf']}{'':<5} | Avg IDF: {result['average_shared_term_idf']}")
        print(f" {'Detected Sources':<24} | {result['exact_evidence']['source_count']} source(s){'':<6} | {result['tfidf_evidence']['source_count']} source(s)")
        print("-" * 70)
        return

    if engine == "tfidf":
        print(f"Cosine Similarity : {result['cosine_similarity']}%")
        print(f"Raw Cosine Score  : {result['raw_cosine']}")
        print(f"Shared Terms Count: {result['shared_terms_count']} distinct term(s)")
        print(f"Avg Shared Term IDF: {result['average_shared_term_idf']}")
        print(f"Top Shared Terms  : {', '.join(result['top_shared_terms'])}")
        print(f"Source count      : {result['source_count']}")

        sources = result.get("sources", [])
        if not sources:
            print("\n[!] Tidak ada sumber korpus yang melewati batas cosine threshold.")
            return

        print()
        print("-" * 70)
        print("TOP MATCHING SOURCES (RANKED BY COSINE SIMILARITY)")
        print("-" * 70)
        for src in sources[:8]:
            print(f" #{src['source_rank']} Dokumen #{src['source_document_id']} ({src['source_title']})")
            print(f"    Cosine Score : {src['cosine_score']} ({src['cosine_similarity']}%)")
            print(f"    Shared Terms : {src['shared_terms_count']} kata ({', '.join(src['top_shared_terms'])})")
            print(f"    Avg Term IDF : {src['average_shared_idf']}")
        return

    # EXACT & IDF Engine
    print(f"Matched words   : {result['matched_words']}")
    print(f"Similarity      : {result['overall_similarity']}%")
    print(f"Longest match   : {result['longest_match_words']} words")
    print(f"Source count    : {result['source_count']}")
    print(f"Match count     : {result['match_count']}")

    matches = result.get("matches", [])

    if not matches:
        print()
        print("[!] Tidak ada matched passage yang terdeteksi.")
        return

    # 1. Distribusi Commonness
    commonness_counter = Counter(m.get("commonness", "unknown") for m in matches)
    print()
    print("-" * 70)
    print("COMMONNESS DISTRIBUTION")
    print("-" * 70)
    for c_level in ["very_high", "high", "medium", "low", "very_low"]:
        cnt = commonness_counter.get(c_level, 0)
        print(f" {c_level:<12} : {cnt:>3} passage(s)")

    # 2. Distribusi IDF
    idf_counter = Counter(f"{m.get('average_idf', 0.0):.6f}" for m in matches)
    print()
    print("-" * 70)
    print("IDF DISTRIBUTION")
    print("-" * 70)
    for idf_val, cnt in sorted(idf_counter.items()):
        print(f" IDF {idf_val} : {cnt:>3} passage(s)")

    # 3. Profil Signifikansi & High-Attention Passages
    high_attention = sum(1 for m in matches if m.get("commonness") in ("low", "very_low"))
    medium_attention = sum(1 for m in matches if m.get("commonness") == "medium")
    common_passages = sum(1 for m in matches if m.get("commonness") in ("high", "very_high"))

    print()
    print("-" * 70)
    print("SIGNIFICANCE & ATTENTION PROFILE")
    print("-" * 70)
    print(f" High-attention passages (Frasa Khas/Spesifik) : {high_attention}")
    print(f" Medium-attention passages                     : {medium_attention}")
    print(f" Common generic passages (Frasa Umum/Klise)    : {common_passages}")

    # Cetak 10 pasase pertama
    print()
    print("-" * 70)
    print(f"DETAIL MATCH PASSAGES (Menampilkan {min(10, len(matches))} dari {len(matches)})")
    print("-" * 70)

    for index, match in enumerate(matches[:10], start=1):
        print_match(match, index)

def main():
    parser = argparse.ArgumentParser(
        description="AR Tilik benchmark diagnostic"
    )

    parser.add_argument(
        "--run",
        type=int,
        required=True,
        help="Benchmark run ID"
    )

    parser.add_argument(
        "--case",
        required=True,
        help="Test case, contoh TC-011"
    )

    parser.add_argument(
        "--engine",
        choices=["exact", "idf", "tfidf", "hybrid"],
        default="exact",
        help="Engine yang digunakan"
    )

    args = parser.parse_args()

    connection = get_connection()

    try:
        diagnose_case(
            connection,
            args.run,
            args.case,
            args.engine
        )
    finally:
        connection.close()

if __name__ == "__main__":
    main()
