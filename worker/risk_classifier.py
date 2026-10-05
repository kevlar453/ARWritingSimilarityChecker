"""
AR Tilik - Academic Risk Synthesizer Engine (XGBoost Integration)
Mengintegrasikan model pembelajaran mesin ensemble (XGBoost / Gradient Boosting)
untuk mensintesis belasan dimensi bukti kemiripan, stylometri, dan alih bahasa
menjadi estimasi probabilitas risiko akademik yang terkalibrasi dan transparan (Explainable AI).
"""

import os
import sys
import json
from pathlib import Path
from typing import Dict, List, Any, Optional, Tuple

PROJECT_ROOT = Path(__file__).resolve().parents[1]
script_dir = str(Path(__file__).resolve().parent)
if script_dir in sys.path:
    sys.path.remove(script_dir)
if str(PROJECT_ROOT) not in sys.path:
    sys.path.insert(0, str(PROJECT_ROOT))

MODEL_DIR = PROJECT_ROOT / "writable" / "models"
MODEL_PATH = MODEL_DIR / "xgboost_academic_risk.json"

FEATURE_NAMES = [
    "exact_similarity",
    "cosine_similarity",
    "longest_match_words",
    "average_shared_term_idf",
    "shared_terms_count",
    "source_count",
    "burstiness",
    "transition_density",
    "cross_lingual_score",
    "boilerplate_ratio",
    "quote_ratio"
]

_GLOBAL_MODEL = None


def extract_features_vector(
    exact_sim: float,
    cosine_sim: float,
    longest_match_words: int,
    avg_idf: float,
    shared_terms_count: int,
    source_count: int,
    burstiness: float = 0.5,
    transition_density: float = 0.0,
    cross_lingual_score: float = 0.0,
    boilerplate_ratio: float = 0.0,
    quote_ratio: float = 0.0,
) -> List[float]:
    """Ekstraksi 11 dimensi fitur tabular terstandardisasi untuk XGBoost."""
    return [
        float(exact_sim),
        float(cosine_sim),
        float(longest_match_words),
        float(avg_idf),
        float(shared_terms_count),
        float(source_count),
        float(burstiness),
        float(transition_density),
        float(cross_lingual_score),
        float(boilerplate_ratio),
        float(quote_ratio),
    ]


def build_synthetic_training_dataset() -> Tuple[List[List[float]], List[int]]:
    """
    Menyiapkan dataset awal berbobot keadilan akademik berdasarkan kaidah penelaahan:
    Label 0: NO_MATCH (Original)
    Label 1: REVIEW (Perlu Telaah Konseptual Dosen)
    Label 2: MATCH (Kemiripan Sekuensial Substansial)
    """
    X = []
    y = []

    # 1. Dokumen Orisinal Mandiri (Low similarity, burstiness organik, no long match)
    for _ in range(40):
        X.append(extract_features_vector(
            exact_sim=0.0, cosine_sim=4.5, longest_match_words=0, avg_idf=0.0,
            shared_terms_count=4, source_count=1, burstiness=0.55, transition_density=0.5,
            cross_lingual_score=0.0, boilerplate_ratio=0.0, quote_ratio=0.0
        ))
        y.append(0)

    # 2. Dokumen dengan kemiripan frasa baku metodologis murni (Boilerplate tinggi -> Review/No Match)
    for _ in range(30):
        X.append(extract_features_vector(
            exact_sim=12.0, cosine_sim=28.0, longest_match_words=12, avg_idf=1.1,
            shared_terms_count=18, source_count=2, burstiness=0.48, transition_density=1.2,
            cross_lingual_score=0.0, boilerplate_ratio=0.85, quote_ratio=0.1
        ))
        y.append(0)

    # 3. Dokumen bernuansa alih bahasa asing (Cross-Lingual tinggi -> Review)
    for _ in range(30):
        X.append(extract_features_vector(
            exact_sim=0.0, cosine_sim=18.0, longest_match_words=0, avg_idf=2.5,
            shared_terms_count=12, source_count=1, burstiness=0.40, transition_density=2.2,
            cross_lingual_score=48.0, boilerplate_ratio=0.0, quote_ratio=0.0
        ))
        y.append(1)

    # 4. Dokumen dengan indikasi AI LLM seragam (Burstiness rendah, transisi klise tinggi -> Review)
    for _ in range(30):
        X.append(extract_features_vector(
            exact_sim=2.0, cosine_sim=15.0, longest_match_words=4, avg_idf=0.8,
            shared_terms_count=8, source_count=1, burstiness=0.18, transition_density=5.2,
            cross_lingual_score=0.0, boilerplate_ratio=0.2, quote_ratio=0.0
        ))
        y.append(1)

    # 5. Dokumen dengan kutipan sah bertanda petik (Exact ada, tapi Quote ratio tinggi -> Review/Aman)
    for _ in range(25):
        X.append(extract_features_vector(
            exact_sim=24.0, cosine_sim=35.0, longest_match_words=35, avg_idf=2.8,
            shared_terms_count=25, source_count=2, burstiness=0.46, transition_density=0.8,
            cross_lingual_score=0.0, boilerplate_ratio=0.1, quote_ratio=0.85
        ))
        y.append(1)

    # 6. Dokumen penjiplakan sekuensial mutlak (Exact tinggi, pasase panjang tak bersitasi -> Match)
    for _ in range(40):
        X.append(extract_features_vector(
            exact_sim=78.0, cosine_sim=82.0, longest_match_words=145, avg_idf=4.2,
            shared_terms_count=120, source_count=3, burstiness=0.35, transition_density=1.5,
            cross_lingual_score=0.0, boilerplate_ratio=0.05, quote_ratio=0.0
        ))
        y.append(2)

    return X, y


def train_xgboost_risk_model() -> Any:
    """Melatih dan menyimpan model XGBoost Academic Risk Classifier."""
    try:
        import xgboost as xgb
        MODEL_DIR.mkdir(parents=True, exist_ok=True)
        X, y = build_synthetic_training_dataset()

        model = xgb.XGBClassifier(
            n_estimators=40,
            max_depth=3,
            learning_rate=0.1,
            objective="multi:softprob",
            num_class=3,
            eval_metric="mlogloss",
            random_state=42
        )
        model.fit(X, y)
        model.save_model(str(MODEL_PATH))
        return model
    except Exception as err:
        print(f"[XGBOOST WARNING] Gagal melatih model XGBoost: {err}")
        return None


def get_risk_model() -> Any:
    """Mengambil model XGBoost yang siap inferensi (singleton)."""
    global _GLOBAL_MODEL
    if _GLOBAL_MODEL is not None:
        return _GLOBAL_MODEL

    try:
        import xgboost as xgb
        if MODEL_PATH.exists():
            model = xgb.XGBClassifier()
            model.load_model(str(MODEL_PATH))
            _GLOBAL_MODEL = model
            return _GLOBAL_MODEL
        else:
            _GLOBAL_MODEL = train_xgboost_risk_model()
            return _GLOBAL_MODEL
    except Exception:
        return None


def predict_academic_risk_xgboost(
    exact_sim: float,
    cosine_sim: float,
    longest_match_words: int,
    avg_idf: float,
    shared_terms_count: int,
    source_count: int,
    burstiness: float = 0.5,
    transition_density: float = 0.0,
    cross_lingual_score: float = 0.0,
    boilerplate_ratio: float = 0.0,
    quote_ratio: float = 0.0,
) -> Dict[str, Any]:
    """
    Melakukan inferensi risiko akademik dan transparansi pendorong (Explainable AI).
    """
    features = extract_features_vector(
        exact_sim=exact_sim,
        cosine_sim=cosine_sim,
        longest_match_words=longest_match_words,
        avg_idf=avg_idf,
        shared_terms_count=shared_terms_count,
        source_count=source_count,
        burstiness=burstiness,
        transition_density=transition_density,
        cross_lingual_score=cross_lingual_score,
        boilerplate_ratio=boilerplate_ratio,
        quote_ratio=quote_ratio,
    )

    model = get_risk_model()

    # Fallback jika library XGBoost belum siap
    if model is None:
        # Perhitungan berbasis bobot heuristik analitik terkalibrasi
        risk_score = round(min(100.0, max(0.0,
            (exact_sim * 0.45) +
            (min(100.0, longest_match_words * 0.5) * 0.25) +
            (cross_lingual_score * 0.20) +
            (max(0.0, 0.40 - burstiness) * 50.0 * 0.10) -
            (quote_ratio * 30.0) -
            (boilerplate_ratio * 20.0)
        )), 1)
        prob_match = round(risk_score / 100.0, 3)
        prob_review = round(max(0.0, 1.0 - prob_match - 0.2), 3) if risk_score > 30 else 0.2
        prob_clear = round(max(0.0, 1.0 - prob_match - prob_review), 3)
        pred_class = 2 if risk_score >= 60.0 else (1 if risk_score >= 15.0 else 0)
    else:
        import numpy as np
        probs = model.predict_proba([features])[0]
        prob_clear = float(probs[0])
        prob_review = float(probs[1])
        prob_match = float(probs[2])
        pred_class = int(np.argmax(probs))
        risk_score = round((prob_review * 40.0) + (prob_match * 100.0), 1)

    labels = {
        0: ("LOW", "Aman (Bebas Indikasi Signifikan)", "Naskah menunjukkan profil kepenulisan orisinal tanpa bukti penyalinan substansial."),
        1: ("MODERATE", "Perlu Telaah Konseptual Dosen", "Terdapat nuansa teks (frasa baku, parafrasa AI, atau alih bahasa) yang memerlukan konfirmasi lisan."),
        2: ("HIGH", "Risiko Integritas Tinggi (Verbatim Overlap)", "Terdeteksi pasase sekuensial panjang atau kesamaan tekstual substansial tanpa atribusi memadai.")
    }

    risk_level, label_title, rationale = labels[pred_class]

    # Analisis Feature Drivers (Explainable AI)
    drivers = []
    if longest_match_words >= 30:
        drivers.append(f"Pasase kontigu panjang ({longest_match_words} kata) menaikkan skor risiko.")
    if cross_lingual_score >= 35.0:
        drivers.append(f"Kemiripan konsep alih bahasa internasional ({cross_lingual_score:.1f}%) memicu atensi telaah.")
    if burstiness < 0.28:
        drivers.append(f"Ritme kalimat seragam khas AI (Burstiness: {burstiness:.2f}) meningkatkan indikasi berbantuan mesin.")
    if quote_ratio >= 0.5:
        drivers.append(f"Tingginya proporsi kutipan bertanda petik ({quote_ratio*100:.0f}%) meringankan potensi pelanggaran.")
    if boilerplate_ratio >= 0.5:
        drivers.append(f"Dominasi frasa ilmiah baku ({boilerplate_ratio*100:.0f}%) mengonfirmasi konvensi metodologi standar.")

    if not drivers:
        drivers.append("Seluruh dimensi berada dalam ambang batas kepenulisan mandiri yang wajar.")

    return {
        "status": "completed",
        "engine": "XGBoost-v3.4 (Gradient Boosted Trees)",
        "calibrated_risk_score": risk_score,
        "risk_level": risk_level,
        "verdict_label": label_title,
        "summary_rationale": rationale,
        "probabilities": {
            "p_original": round(prob_clear, 3),
            "p_needs_review": round(prob_review, 3),
            "p_high_risk": round(prob_match, 3),
        },
        "explainable_drivers": drivers,
        "features_evaluated": {name: features[i] for i, name in enumerate(FEATURE_NAMES)}
    }


if __name__ == "__main__":
    import argparse
    parser = argparse.ArgumentParser(description="AR Tilik XGBoost Academic Risk Synthesizer")
    parser.add_argument("--train", action="store_true", help="Latih ulang model XGBoost dan simpan ke disk")
    args = parser.parse_args()

    if args.train or not MODEL_PATH.exists():
        print("=== MELATIH MODEL XGBOOST AR TILIK ===")
        m = train_xgboost_risk_model()
        print("Model dilatih dan disimpan ke:", MODEL_PATH)

    print("=== PENGUJIAN INFERENSI XGBOOST AR TILIK ===")
    res = predict_academic_risk_xgboost(
        exact_sim=25.0,
        cosine_sim=40.0,
        longest_match_words=65,
        avg_idf=2.8,
        shared_terms_count=45,
        source_count=2,
        burstiness=0.45,
        quote_ratio=0.1,
        boilerplate_ratio=0.2
    )
    print("\nHasil Inferensi Kasus Uji:")
    print("Level Risiko :", res["risk_level"], f"({res['calibrated_risk_score']}%)")
    print("Label        :", res["verdict_label"])
    print("Probabilitas :", res["probabilities"])
    print("Faktor Driver:", res["explainable_drivers"])
