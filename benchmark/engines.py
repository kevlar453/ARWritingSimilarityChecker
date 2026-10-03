from worker.matcher import run_similarity_check
from worker.tfidf import run_tfidf_similarity_check
from worker.hybrid import run_hybrid_similarity_check

def run_engine(
    connection,
    submission_document_id: int,
    engine: str,
    benchmark_run_id: int
) -> dict:
    if engine == "hybrid":
        return run_hybrid_similarity_check(
            connection=connection,
            submission_document_id=submission_document_id,
            corpus_scope="benchmark",
            benchmark_run_id=benchmark_run_id
        )
    if engine == "tfidf":
        return run_tfidf_similarity_check(
            connection=connection,
            submission_document_id=submission_document_id,
            corpus_scope="benchmark",
            benchmark_run_id=benchmark_run_id
        )
    return run_similarity_check(
        connection=connection,
        submission_document_id=submission_document_id,
        engine=engine,
        corpus_scope="benchmark",
        benchmark_run_id=benchmark_run_id
    )
