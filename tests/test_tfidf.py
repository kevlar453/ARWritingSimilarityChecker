import math
from worker.tfidf import (
    compute_tf,
    compute_idf_table,
    compute_document_vector,
    cosine_similarity
)

def test_cosine_similarity_identical():
    v1 = {"penelitian": 1.5, "teknologi": 2.0}
    v2 = {"penelitian": 1.5, "teknologi": 2.0}
    score = cosine_similarity(v1, v2)
    assert math.isclose(score, 1.0, rel_tol=1e-5)

def test_cosine_similarity_orthogonal():
    v1 = {"pertanian": 1.0, "tanaman": 2.0}
    v2 = {"komputer": 1.0, "jaringan": 2.0}
    score = cosine_similarity(v1, v2)
    assert score == 0.0

def test_compute_tf_and_vector():
    tokens = ["sistem", "informasi", "sistem"]
    tf = compute_tf(tokens)
    assert math.isclose(tf["sistem"], 2.0 / 3.0)
    assert math.isclose(tf["informasi"], 1.0 / 3.0)
    
    df = {"sistem": 2, "informasi": 1}
    idf_table = compute_idf_table(df, total_documents=10)
    
    vec = compute_document_vector(tf, idf_table)
    assert "sistem" in vec
    assert "informasi" in vec
    assert vec["sistem"] > 0
    assert vec["informasi"] > 0
