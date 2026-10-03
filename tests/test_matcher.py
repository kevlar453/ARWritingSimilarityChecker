from worker.matcher import (
    group_match_segments,
    deduplicate_match_pairs
)

def test_segment_requires_both_positions():
    matches = [
        {
            "submission_position": 10,
            "source_document_id": 1,
            "source_position": 100
        },
        {
            "submission_position": 11,
            "source_document_id": 1,
            "source_position": 101
        },
        {
            "submission_position": 12,
            "source_document_id": 1,
            "source_position": 900
        }
    ]
    segments = group_match_segments(matches)
    assert len(segments) == 2
    assert len(segments[0]) == 2
    assert len(segments[1]) == 1

def test_different_sources_not_grouped():
    matches = [
        {
            "submission_position": 10,
            "source_document_id": 1,
            "source_position": 100
        },
        {
            "submission_position": 11,
            "source_document_id": 2,
            "source_position": 101
        }
    ]
    segments = group_match_segments(matches)
    assert len(segments) == 2
    assert len(segments[0]) == 1
    assert len(segments[1]) == 1

def test_duplicate_pairs_removed():
    matches = [
        {
            "source_document_id": 1,
            "submission_position": 10,
            "source_position": 100
        },
        {
            "source_document_id": 1,
            "submission_position": 10,
            "source_position": 100
        },
        {
            "source_document_id": 1,
            "submission_position": 11,
            "source_position": 101
        }
    ]
    result = deduplicate_match_pairs(matches)
    assert len(result) == 2
