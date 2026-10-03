from worker.idf import (
    calculate_idf,
    calculate_frequency_ratio,
    classify_commonness,
    evaluate_segment
)

def test_common_phrase():
    assert calculate_idf(20, 20) == 0

def test_rare_phrase():
    result = calculate_idf(20, 1)
    assert round(result, 3) == 2.996

def test_frequency_ratio():
    result = calculate_frequency_ratio(4, 20)
    assert result == 0.2

def test_commonness():
    assert classify_commonness(1.0) == "very_high"
    assert classify_commonness(0.5) == "high"
    assert classify_commonness(0.25) == "medium"
    assert classify_commonness(0.05) == "low"
    assert classify_commonness(0.01) == "very_low"

def test_evaluate_segment():
    segment = [
        {"idf": 2.0, "frequency_ratio": 0.05},
        {"idf": 3.0, "frequency_ratio": 0.05}
    ]
    eval_info = evaluate_segment(segment, shingle_size=5)
    # 2 fingerprints + 5 - 1 = 6 words
    assert eval_info["word_count"] == 6
    assert eval_info["average_idf"] == 2.5
    assert eval_info["max_idf"] == 3.0
    assert eval_info["significance"] == 2.5 * 6
