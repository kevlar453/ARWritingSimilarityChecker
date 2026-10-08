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

def test_content_tokens_filtering():
    from worker.tfidf import content_tokens
    raw_tokens = ["yang", "dan", "dalam", "dengan", "penelitian", "liturgi", "12", "a", "pastoral"]
    filtered = content_tokens(raw_tokens)
    assert "yang" not in filtered
    assert "dan" not in filtered
    assert "dalam" not in filtered
    assert "dengan" not in filtered
    assert "12" not in filtered  # digit
    assert "a" not in filtered   # short token
    assert "penelitian" in filtered
    assert "liturgi" in filtered
    assert "pastoral" in filtered

def test_compute_sublinear_tf():
    from worker.tfidf import compute_tf
    tokens = ["liturgi", "liturgi", "liturgi", "pastoral"]
    tf_sub = compute_tf(tokens, sublinear=True)
    assert tf_sub["liturgi"] > tf_sub["pastoral"]
    assert math.isclose(tf_sub["liturgi"], 1.0 + math.log(3), rel_tol=1e-5)
    assert math.isclose(tf_sub["pastoral"], 1.0 + math.log(1), rel_tol=1e-5)
