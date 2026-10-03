import sys
import math
from pathlib import Path

sys.path.append(
    str(Path(__file__).resolve().parents[1])
)

from benchmark.database import get_connection
from benchmark.corpus import COMMON_PHRASES
from worker.normalizer import normalize_text, tokenize
from worker.shingler import generate_shingles

EXPECTED_DISTRIBUTION = [
    (
        "Phrase A (very_common)",
        COMMON_PHRASES["very_common"],
        20
    ),
    (
        "Phrase B (common)",
        COMMON_PHRASES["common"],
        10
    ),
    (
        "Phrase C (less_common)",
        COMMON_PHRASES["less_common"],
        4
    ),
    (
        "Phrase D (rare)",
        COMMON_PHRASES["rare"],
        1
    ),
]


def run_sanity_check(run_id: int):
    conn = get_connection()
    cursor = conn.cursor(dictionary=True)
    try:
        print("\n" + "=" * 75)
        print(
            f"      AR TILIK - SANITY CHECK BENCHMARK RUN #{run_id}"
        )
        print("=" * 75)

        # ---------------------------------------------------------
        # 1. Cek jumlah dokumen korpus
        # ---------------------------------------------------------
        cursor.execute(
            """
            SELECT COUNT(*) AS total
            FROM documents
            WHERE document_type = 'corpus'
              AND benchmark_run_id = %s
            """,
            (run_id,)
        )

        corpus_total = cursor.fetchone()["total"]

        print(
            f"[1] Dokumen korpus terdaftar : "
            f"{corpus_total} (Target: 20)"
        )

        if corpus_total != 20:
            raise AssertionError(
                f"Expected 20 corpus docs, got {corpus_total}"
            )

        # ---------------------------------------------------------
        # 2. Cek dokumen yang benar-benar memiliki fingerprint
        # ---------------------------------------------------------
        cursor.execute(
            """
            SELECT COUNT(DISTINCT document_id) AS total
            FROM fingerprint_documents
            WHERE document_id IN (
                SELECT id
                FROM documents
                WHERE benchmark_run_id = %s
                  AND document_type = 'corpus'
            )
            """,
            (run_id,)
        )

        indexed_docs = cursor.fetchone()["total"]

        print(
            f"[2] Dokumen dengan fingerprint: "
            f"{indexed_docs} (Target: 20)"
        )

        if indexed_docs != 20:
            raise AssertionError(
                f"Expected 20 indexed docs, got {indexed_docs}"
            )

        # ---------------------------------------------------------
        # 3. Validasi DF benchmark
        # ---------------------------------------------------------
        print(
            "\n[3] Validasi Spesifik Distribusi Frasa Benchmark:"
        )
        print("-" * 75)
        print(
            f"{'Label':<25}"
            f"{'Target DF':>10}"
            f"{'Actual DF':>12}"
            f"{'Stats DF':>12}"
            f"{'IDF':>12}"
            f"{'Status':>8}"
        )
        print("-" * 75)

        all_passed = True

        for label, text, target_df in EXPECTED_DISTRIBUTION:

            words = tokenize(
                normalize_text(text)
            )

            shingles = generate_shingles(
                words,
                size=5
            )

            if not shingles:
                print(
                    f"[!] Frasa '{text}' kurang dari 5 kata!"
                )
                all_passed = False
                continue

            # Gunakan fingerprint shingle pertama
            first_fp = shingles[0]["fingerprint"]

            # -----------------------------------------------------
            # Actual DF:
            # hitung LANGSUNG dari fingerprint_documents
            # khusus benchmark run ini.
            # -----------------------------------------------------
            cursor.execute(
                """
                SELECT COUNT(DISTINCT fd.document_id) AS document_frequency
                FROM fingerprint_documents fd
                INNER JOIN documents d
                    ON d.id = fd.document_id
                WHERE fd.fingerprint = %s
                  AND d.document_type = 'corpus'
                  AND d.benchmark_run_id = %s
                """,
                (first_fp, run_id)
            )

            row = cursor.fetchone()

            actual_df = int(
                row["document_frequency"]
                if row
                else 0
            )

            # -----------------------------------------------------
            # Stats DF:
            # nilai yang sedang digunakan engine
            # -----------------------------------------------------
            cursor.execute(
                """
                SELECT document_frequency
                FROM fingerprint_stats
                WHERE fingerprint = %s
                """,
                (first_fp,)
            )

            stats_row = cursor.fetchone()

            stats_df = int(
                stats_row["document_frequency"]
                if stats_row
                else 0
            )

            # -----------------------------------------------------
            # IDF berdasarkan DF benchmark aktual
            # -----------------------------------------------------
            if actual_df > 0:
                idf = math.log(
                    corpus_total / actual_df
                )
            else:
                idf = 0.0

            # -----------------------------------------------------
            # Validasi:
            # 1. actual DF harus sesuai desain
            # 2. stats DF harus sama dengan actual DF
            # -----------------------------------------------------
            status = "✓"

            if actual_df != target_df:
                status = "✗"
                all_passed = False

            if stats_df != actual_df:
                status = "!"
                all_passed = False

            print(
                f"{label:<25}"
                f"{target_df:>10}"
                f"{actual_df:>12}"
                f"{stats_df:>12}"
                f"{idf:>12.4f}"
                f"{status:>8}"
            )

        # ---------------------------------------------------------
        # 4. Kesimpulan
        # ---------------------------------------------------------
        print("-" * 75)

        if all_passed:
            print(
                "[✓] Semua distribusi DF benchmark VALID."
            )
            print(
                f"[✓] fingerprint_stats konsisten dengan DF aktual Run #{run_id}."
            )
            print()

        else:
            print(
                "[!] Validasi DF benchmark GAGAL."
            )
            print(
                "[!] Periksa indexing atau rebuild fingerprint_stats."
            )
            print()

            sys.exit(1)

    finally:
        cursor.close()
        conn.close()


if __name__ == "__main__":
    run_id = (
        int(sys.argv[1])
        if len(sys.argv) > 1
        else 1
    )
    run_sanity_check(run_id)
