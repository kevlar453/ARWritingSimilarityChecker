from collections import defaultdict

CLASSES = [
    "match",
    "review",
    "no_match",
]

def binary_label(label: str) -> str:
    """
    Untuk evaluasi binary:
    match + review -> similar
    no_match -> not_similar
    """
    if label in ("match", "review"):
        return "similar"
    return "not_similar"

def calculate_binary_metrics(results: list[dict]) -> dict:
    tp = fp = tn = fn = 0
    for result in results:
        expected = binary_label(result["expected"])
        predicted = binary_label(result["predicted"])
        
        if expected == "similar" and predicted == "similar":
            tp += 1
        elif expected == "not_similar" and predicted == "similar":
            fp += 1
        elif expected == "not_similar" and predicted == "not_similar":
            tn += 1
        elif expected == "similar" and predicted == "not_similar":
            fn += 1
            
    precision = tp / (tp + fp) if (tp + fp) else 0.0
    recall = tp / (tp + fn) if (tp + fn) else 0.0
    f1 = (2 * precision * recall / (precision + recall)) if (precision + recall) else 0.0
    
    return {
        "tp": tp,
        "fp": fp,
        "tn": tn,
        "fn": fn,
        "precision": precision,
        "recall": recall,
        "f1": f1
    }

def calculate_multiclass_metrics(results: list[dict]) -> dict:
    matrix = defaultdict(lambda: defaultdict(int))
    for result in results:
        expected = result["expected"]
        predicted = result["predicted"]
        matrix[expected][predicted] += 1
        
    metrics = {}
    for label in CLASSES:
        tp = matrix[label][label]
        fp = sum(matrix[other][label] for other in CLASSES if other != label)
        fn = sum(matrix[label][other] for other in CLASSES if other != label)
        
        precision = tp / (tp + fp) if (tp + fp) else 0.0
        recall = tp / (tp + fn) if (tp + fn) else 0.0
        f1 = (2 * precision * recall / (precision + recall)) if (precision + recall) else 0.0
        
        metrics[label] = {
            "tp": tp,
            "fp": fp,
            "fn": fn,
            "precision": precision,
            "recall": recall,
            "f1": f1
        }
        
    macro_f1 = sum(item["f1"] for item in metrics.values()) / len(CLASSES) if CLASSES else 0.0
    
    # Konversi matrix ke standard dict
    plain_matrix = {}
    for r in CLASSES:
        plain_matrix[r] = {}
        for c in CLASSES:
            plain_matrix[r][c] = matrix[r][c]
            
    return {
        "matrix": plain_matrix,
        "classes": metrics,
        "macro_f1": macro_f1
    }

def classify_error(expected: str, predicted: str) -> str | None:
    if expected == predicted:
        return None
    if expected == "no_match" and predicted != "no_match":
        return "false_positive"
    if expected != "no_match" and predicted == "no_match":
        return "false_negative"
    return "class_confusion"
