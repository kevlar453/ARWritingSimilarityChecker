from worker.similarity import (
    merge_ranges,
    calculate_overall_similarity,
    calculate_longest_match,
    segment_word_count
)

def test_merge_ranges():
    result = merge_ranges([
        (10, 20),
        (15, 25),
        (30, 35)
    ])
    assert result == [
        (10, 25),
        (30, 35)
    ]

def test_no_double_count():
    matches = [
        {"submission_position": 10},
        {"submission_position": 11},
        {"submission_position": 12}
    ]
    result = calculate_overall_similarity(
        matches,
        total_words=100,
        shingle_size=5
    )
    assert result["matched_words"] == 7
    assert result["similarity"] == 7.0

def test_overlapping_sources_do_not_double_count():
    matches = [
        {"submission_position": 10},
        {"submission_position": 10},
        {"submission_position": 11}
    ]
    result = calculate_overall_similarity(
        matches,
        total_words=100,
        shingle_size=5
    )
    assert result["matched_words"] == 6
    assert result["similarity"] == 6.0

def test_empty_matches():
    result = calculate_overall_similarity(
        [],
        total_words=100
    )
    assert result["matched_words"] == 0
    assert result["similarity"] == 0.0

def test_longest_segment():
    segments = [
        [
            {"submission_position": 10},
            {"submission_position": 11},
            {"submission_position": 12}
        ],
        [
            {"submission_position": 50}
        ]
    ]
    result = calculate_longest_match(
        segments,
        shingle_size=5
    )
    assert result == 7
