"""
AR Tilik v1.1 - Evidence Presenter & Source Grouping Layer
Memisahkan penyajian bukti kemiripan menjadi dua kelompok mandiri:
1. Direct Textual Overlap (Didukung oleh Exact Sequential Matcher)
2. Lexical / Topic Similarity (Didukung oleh TF-IDF Vectorizer)

Menyertakan lapisan Konteks Akademik (Citation/Quote Context)
dengan garansi mutlak bahwa kehadiran konteks TIDAK MENGUBAH
keputusan sistem v1.0.0 (MATCH / REVIEW / NO_MATCH).

Baseline: v1.0.0 FROZEN (worker/hybrid.py)
Sprint: 1 (WP-03)
"""

from typing import Dict, Any, List, Optional
from worker.contract_v1_1 import (
    EvidenceContractV11,
    EvidenceType,
    CitationContext,
    CitationStatus,
    CitationConfidence,
)
from worker.citation_detector import (
    detect_citation_context,
    CitationDetectionResult,
)


def group_sources_by_evidence_type(sources: Optional[List[Dict[str, Any]]]) -> Dict[str, List[Dict[str, Any]]]:
    """
    Mengelompokkan daftar sumber rujukan menjadi 2 ember bukti terpisah:
    - direct_textual_overlap: sumber dengan exact_similarity > 0 atau matched_words > 0
    - lexical_topic_similarity: sumber dengan cosine_similarity > 0
    
    Sumber dapat muncul pada kedua kelompok jika memiliki bukti sekuensial DAN kemiripan kosinus.
    Sumber dengan exact_similarity = 0 TIDAK AKAN dimasukkan ke dalam direct_textual_overlap.
    """
    if not sources:
        return {
            "direct_textual_overlap": [],
            "lexical_topic_similarity": [],
        }

    direct_overlap: List[Dict[str, Any]] = []
    lexical_similarity: List[Dict[str, Any]] = []

    for src in sources:
        exact_val = float(src.get("exact_similarity", src.get("similarity", 0.0)))
        matched_words = int(src.get("matched_words", src.get("matched_terms", 0)))
        cosine_val = float(src.get("cosine_similarity", 0.0))

        # 1. Direct Textual Overlap: Mensyaratkan adanya bukti sekuensial nyata
        if exact_val > 0.0 or matched_words > 0:
            direct_overlap.append({
                **src,
                "evidence_type": EvidenceType.DIRECT_TEXTUAL_OVERLAP.value,
                "display_score": exact_val,
                "metric_label": f"Exact: {exact_val:.1f}% ({matched_words} kata)",
            })

        # 2. Lexical / Topic Similarity: Mensyaratkan adanya irisan bobot kosinus
        if cosine_val > 0.0:
            lexical_similarity.append({
                **src,
                "evidence_type": EvidenceType.LEXICAL_TOPIC_SIMILARITY.value,
                "display_score": cosine_val,
                "metric_label": f"Cosine: {cosine_val:.1f}%",
            })

    # Urutkan masing-masing bucket berdasarkan skor lensa yang relevan (descending)
    direct_overlap.sort(
        key=lambda s: float(s.get("exact_similarity", s.get("similarity", 0.0))),
        reverse=True
    )
    lexical_similarity.sort(
        key=lambda s: float(s.get("cosine_similarity", 0.0)),
        reverse=True
    )

    return {
        "direct_textual_overlap": direct_overlap,
        "lexical_topic_similarity": lexical_similarity,
    }


def check_evidence_divergence(exact_sim: float, cosine_sim: float) -> Optional[Dict[str, Any]]:
    """
    Mendeteksi apakah terdapat divergensi bukti (Exact ≈ 0 dan Cosine > 0).
    Jika terjadi divergensi, menghasilkan catatan transparan bahwa kemiripan
    bersifat topikal/leksikal, bukan penjiplakan sekuensial langsung.
    """
    exact_f = float(exact_sim)
    cosine_f = float(cosine_sim)

    if exact_f <= 0.05 and cosine_f > 0.0:
        return {
            "has_divergence": True,
            "divergence_type": "LEXICAL_WITHOUT_SEQUENTIAL_OVERLAP",
            "message": (
                "Divergensi Bukti: Dokumen rujukan menunjukkan kemiripan kosakata/topik "
                f"(TF-IDF Cosine: {cosine_f:.2f}%), namun tidak ditemukan pasase sekuensial "
                f"yang beririsan langsung (Exact: {exact_f:.2f}%). Skor kemiripan mencerminkan "
                "terminologi disipliner dan medan makna bersama, bukan penjiplakan teks verbatim."
            ),
            "recommendation": (
                "Penelaah disarankan meninjau keterkaitan topik dan penggunaan terminologi, "
                "bukan memperlakukan kemiripan sebagai bukti penyalinan pasase kata langsung."
            ),
        }

    return None


def present_hybrid_report(
    report_dict: Dict[str, Any],
    sources: Optional[List[Dict[str, Any]]] = None,
    evidence_text: Optional[str] = None,
    context_detection: Optional[CitationDetectionResult] = None,
    segmentation: Optional[Any] = None,
    term_drilldown: Optional[Any] = None,
    boilerplate_detection: Optional[Any] = None,
    ai_stylometrics: Optional[Any] = None,
    cross_lingual: Optional[Any] = None,
) -> Dict[str, Any]:
    """
    Membangun struktur presentasi bukti terdekomposisi lengkap v1.1.

    Menjamin hierarki:
    1. SOURCE EVIDENCE:
       - Direct Textual Overlap (Exact evidence)
       - Lexical / Topic Similarity (TF-IDF evidence)
       - Divergence Notice (jika Exact ≈ 0 dan Cosine > 0)
    2. CONTEXT:
       - citation_context
       - citation_status
       - citation_confidence
       - context_span
       - rationale
    3. SYSTEM DECISION:
       - Mempertahankan keputusan hybrid v1.0.0 (MATCH / REVIEW / NO_MATCH)
       - Invarian: Kehadiran sitasi TIDAK mengubah keputusan sistem
    4. HUMAN INTERPRETATION:
       - Rekomendasi telaah akademis
    """
    exact_sim = float(report_dict.get("exact_similarity", report_dict.get("overall_similarity", 0.0)))
    cosine_sim = float(report_dict.get("cosine_similarity", 0.0))
    overall_sim = float(report_dict.get("overall_similarity", max(exact_sim, cosine_sim)))
    v1_decision = str(report_dict.get("predicted_decision", "no_match")).lower()

    # 1. Kelompokkan Sumber Berdasarkan Jenis Bukti
    src_list = sources if sources is not None else report_dict.get("sources", [])
    grouped_sources = group_sources_by_evidence_type(src_list)

    # 2. Cek Kondisi Divergensi
    divergence = check_evidence_divergence(exact_sim, cosine_sim)

    # 3. Ekstraksi Konteks Sitasi / Akademik
    if context_detection is None:
        context_detection = detect_citation_context(evidence_text)

    # 4. Verifikasi Invariansi Keputusan (Regression Guard)
    # Keputusan sistem v1.0.0 TIDAK BOLEH BERUBAH oleh lapisan konteks
    system_decision = v1_decision

    # 5. Bangun Struktur Presentasi Lengkap v1.1
    return {
        "version": "1.1.0",
        "baseline_version": "1.0.0 (FROZEN)",
        "source_evidence": {
            "direct_textual_overlap": {
                "count": len(grouped_sources["direct_textual_overlap"]),
                "sources": grouped_sources["direct_textual_overlap"],
                "metric_summary": {
                    "exact_similarity": round(exact_sim, 2),
                    "longest_match_words": int(report_dict.get("longest_match_words", 0)),
                    "matched_words": int(report_dict.get("matched_words", 0)),
                },
                "has_direct_overlap": len(grouped_sources["direct_textual_overlap"]) > 0,
            },
            "lexical_topic_similarity": {
                "count": len(grouped_sources["lexical_topic_similarity"]),
                "sources": grouped_sources["lexical_topic_similarity"],
                "metric_summary": {
                    "cosine_similarity": round(cosine_sim, 2),
                    "shared_terms_count": int(report_dict.get("shared_terms_count", 0)),
                    "average_shared_term_idf": round(float(report_dict.get("average_shared_term_idf", 0.0)), 4),
                },
                "has_lexical_similarity": len(grouped_sources["lexical_topic_similarity"]) > 0,
            },
            "divergence": divergence,
        },
        "context": {
            "citation_context": context_detection.context_type,
            "citation_status": context_detection.citation_status,
            "citation_confidence": context_detection.confidence,
            "context_span": context_detection.matched_span,
            "span_start": context_detection.span_start,
            "span_end": context_detection.span_end,
            "rationale": context_detection.rationale,
            "secondary_matches": context_detection.secondary_matches,
        },
        "system_decision": {
            "decision": system_decision,
            "overall_similarity": round(overall_sim, 2),
            "evidence_pattern": report_dict.get("evidence_pattern", "UNKNOWN"),
            "evidence_agreement": report_dict.get("evidence_agreement", "UNKNOWN"),
            "decision_invariant_guaranteed": True,
        },
        "human_interpretation": {
            "prompt": (
                "Telaah substantif akademis: Periksa apakah kemiripan yang terdeteksi "
                "merupakan kutipan berizin, formulasi metodologis standar, atau bagian "
                "yang membutuhkan atribusi sitasi tambahan."
            ),
            "requires_investigation": system_decision == "review" and context_detection.citation_status == CitationStatus.NOT_CITED.value,
            "properly_attributed_indicated": context_detection.citation_status in (CitationStatus.CITED.value, CitationStatus.QUOTE.value),
        },
        "v1_2_evolution": {
            "section_segmentation": segmentation.to_dict() if hasattr(segmentation, "to_dict") else segmentation,
            "term_drilldown": term_drilldown.to_dict() if hasattr(term_drilldown, "to_dict") else term_drilldown,
            "boilerplate_detection": boilerplate_detection.to_dict() if hasattr(boilerplate_detection, "to_dict") else boilerplate_detection,
            "ai_stylometrics": ai_stylometrics.to_dict() if hasattr(ai_stylometrics, "to_dict") else ai_stylometrics,
            "cross_lingual": cross_lingual.to_dict() if hasattr(cross_lingual, "to_dict") else cross_lingual,
        }
    }
