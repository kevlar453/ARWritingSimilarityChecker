import math

def calculate_idf(total_documents: int, document_frequency: int) -> float:
    """
    Menghitung Inverse Document Frequency:
    IDF = log(N / DF)
    Jika DF = N, IDF = 0 (frasa muncul di semua dokumen, tidak diskriminatif).
    """
    if total_documents <= 0:
        return 0.0
    if document_frequency <= 0:
        return math.log(total_documents)
    # Proteksi nilai DF > N jika ada anomali
    if document_frequency >= total_documents:
        return 0.0
    return math.log(total_documents / document_frequency)

def calculate_frequency_ratio(document_frequency: int, total_documents: int) -> float:
    """
    Menghitung rasio ketersebaran fingerprint di seluruh dokumen korpus:
    ratio = DF / N
    """
    if total_documents <= 0:
        return 0.0
    return document_frequency / total_documents

def classify_commonness(frequency_ratio: float) -> str:
    """
    Mengelompokkan tingkat keumuman frasa akademik:
    - >= 0.80: very_high
    - >= 0.50: high
    - >= 0.20: medium
    - >= 0.05: low
    - < 0.05: very_low
    """
    if frequency_ratio >= 0.80:
        return "very_high"
    if frequency_ratio >= 0.50:
        return "high"
    if frequency_ratio >= 0.20:
        return "medium"
    if frequency_ratio >= 0.05:
        return "low"
    return "very_low"

def evaluate_segment(segment: list[dict], shingle_size: int = 5) -> dict:
    """
    Mengevaluasi passage matched segment berdasarkan metrik IDF.
    """
    if not segment:
        return {
            "word_count": 0,
            "average_idf": 0.0,
            "max_idf": 0.0,
            "significance": 0.0
        }
    
    idfs = [item.get("idf", 0.0) for item in segment]
    fingerprint_count = len(segment)
    word_count = fingerprint_count + shingle_size - 1
    average_idf = sum(idfs) / len(idfs) if idfs else 0.0
    max_idf = max(idfs) if idfs else 0.0
    significance = average_idf * word_count
    
    return {
        "word_count": word_count,
        "average_idf": round(average_idf, 6),
        "max_idf": round(max_idf, 6),
        "significance": round(significance, 6)
    }

def calculate_segment_commonness(segment: list[dict]) -> str:
    """
    Menghitung tingkat keumuman segment berdasarkan rata-rata frequency_ratio.
    """
    if not segment:
        return "unknown"
    ratios = [item.get("frequency_ratio", 0.0) for item in segment]
    average_ratio = sum(ratios) / len(ratios) if ratios else 0.0
    return classify_commonness(average_ratio)
