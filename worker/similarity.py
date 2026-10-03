def merge_ranges(ranges: list[tuple[int, int]]) -> list[tuple[int, int]]:
    """
    Merge overlapping atau adjacent word ranges.
    Input:  [(10, 20), (18, 30), (40, 45)]
    Output: [(10, 30), (40, 45)]
    """
    if not ranges:
        return []
    
    sorted_ranges = sorted(ranges, key=lambda item: (item[0], item[1]))
    merged = [list(sorted_ranges[0])]
    
    for start, end in sorted_ranges[1:]:
        last_start, last_end = merged[-1]
        if start <= last_end + 1:
            merged[-1] = [last_start, max(last_end, end)]
        else:
            merged.append([start, end])
            
    return [tuple(item) for item in merged]

def segment_word_count(segment: list[dict], shingle_size: int = 5) -> int:
    """
    Menghitung jumlah kata yang dicakup oleh satu matched segment.
    """
    if not segment:
        return 0
    start = segment[0]["submission_position"]
    end = segment[-1]["submission_position"] + shingle_size - 1
    return end - start + 1

def segment_to_range(segment: list[dict], shingle_size: int = 5) -> tuple[int, int] | None:
    """
    Mengubah segment shingle menjadi rentang kata (start, end).
    """
    if not segment:
        return None
    start = segment[0]["submission_position"]
    end = segment[-1]["submission_position"] + shingle_size - 1
    return (start, end)

def calculate_overall_similarity(matches: list[dict], total_words: int, shingle_size: int = 5) -> dict:
    """
    Menghitung similarity berdasarkan union coverage pada submission.
    Tidak ada double-count overlap dari beberapa sumber.
    """
    if not matches or total_words <= 0:
        return {
            "matched_words": 0,
            "similarity": 0.0,
            "ranges": []
        }
    
    ranges = []
    for match in matches:
        position = match["submission_position"]
        ranges.append((position, position + shingle_size - 1))
        
    merged = merge_ranges(ranges)
    matched_words = sum(end - start + 1 for start, end in merged)
    matched_words = min(matched_words, total_words)
    
    similarity = (matched_words / total_words) * 100.0
    return {
        "matched_words": matched_words,
        "similarity": round(similarity, 2),
        "ranges": merged
    }

def calculate_longest_match(segments: list[list[dict]], shingle_size: int = 5) -> int:
    """
    Menghitung panjang kata berturut-turut terpanjang dari kumpulan segment.
    """
    if not segments:
        return 0
    return max(segment_word_count(seg, shingle_size) for seg in segments)

def calculate_source_similarity(matched_words: int, total_words: int) -> float:
    """
    Menghitung persentase similarity kontribusi per sumber terhadap submission.
    """
    if total_words <= 0:
        return 0.0
    return round((matched_words / total_words) * 100.0, 2)
