"""
AR Tilik v1.2 - Term-Level IDF Drill-Down Visualizer
Modul inspeksi kontribusi bobot istilah leksikal (TF-IDF Term-Level Decomposition).

Tujuan:
Membongkar secara transparan mengapa sebuah dokumen memiliki skor kemiripan leksikal
(TF-IDF Cosine), terutama pada kasus DIVERGENSI (Exact ≈ 0% vs TF-IDF > 0%).
Menghitung kontribusi dot-product setiap kata bersama:
    Contribution(t) = (TF_sub(t) * IDF(t)) * (TF_doc(t) * IDF(t))

Invarian:
- Zero mutation pada baseline engine v1.0.0 (worker/hybrid.py tetap frozen).
- Non-Suppression: Tidak mengubah skor kosinus murni, melainkan menyajikan rincian bobot transparan.
"""

from dataclasses import dataclass, field
from enum import Enum
import math
from typing import Optional, List, Dict, Any, Union
from collections import Counter
from worker.normalizer import normalize_text, tokenize


class TermSignificanceTier(str, Enum):
    """
    Tingkat signifikansi kata berdasarkan bobot IDF (Inverse Document Frequency).
    """
    DISCIPLINARY_KEYWORD = "DISCIPLINARY_KEYWORD"  # IDF >= 2.0 (sangat spesifik/khas topik)
    TOPICAL_TERM = "TOPICAL_TERM"                  # 1.0 <= IDF < 2.0 (istilah bidang kajian)
    COMMON_VOCABULARY = "COMMON_VOCABULARY"        # IDF < 1.0 (kosakata umum akademik)

    @classmethod
    def from_idf(cls, idf_val: float) -> "TermSignificanceTier":
        if idf_val >= 2.0:
            return cls.DISCIPLINARY_KEYWORD
        elif idf_val >= 1.0:
            return cls.TOPICAL_TERM
        else:
            return cls.COMMON_VOCABULARY


@dataclass
class TermContribution:
    """
    Rincian kontribusi satu istilah bersama terhadap kemiripan leksikal kosinus.
    """
    term: str
    sub_count: int
    doc_count: int
    sub_tf: float
    doc_tf: float
    idf: float
    sub_weight: float        # sub_tf * idf
    doc_weight: float        # doc_tf * idf
    dot_product: float       # sub_weight * doc_weight
    contribution_pct: float  # persentase terhadap total dot product
    significance_tier: TermSignificanceTier

    def to_dict(self) -> Dict[str, Any]:
        return {
            "term": self.term,
            "sub_count": self.sub_count,
            "doc_count": self.doc_count,
            "sub_tf": round(self.sub_tf, 5),
            "doc_tf": round(self.doc_tf, 5),
            "idf": round(self.idf, 4),
            "sub_weight": round(self.sub_weight, 5),
            "doc_weight": round(self.doc_weight, 5),
            "dot_product": round(self.dot_product, 6),
            "contribution_percentage": round(self.contribution_pct, 2),
            "contribution_pct": round(self.contribution_pct, 2),
            "significance_tier": self.significance_tier.value,
        }


@dataclass
class TermDrillDownReport:
    """
    Laporan dekomposisi leksikal terurai per istilah untuk penelaah akademik.
    """
    cosine_similarity: float
    total_shared_terms: int
    total_dot_product: float
    top_terms: List[TermContribution] = field(default_factory=list)
    disciplinary_terms_count: int = 0
    topical_terms_count: int = 0
    common_terms_count: int = 0
    disciplinary_contribution_pct: float = 0.0
    explanation_narrative: str = ""

    def to_dict(self) -> Dict[str, Any]:
        return {
            "cosine_similarity": round(self.cosine_similarity, 2),
            "total_shared_terms": self.total_shared_terms,
            "total_dot_product": round(self.total_dot_product, 6),
            "disciplinary_terms_count": self.disciplinary_terms_count,
            "topical_terms_count": self.topical_terms_count,
            "common_terms_count": self.common_terms_count,
            "disciplinary_contribution_percentage": round(self.disciplinary_contribution_pct, 2),
            "disciplinary_contribution_pct": round(self.disciplinary_contribution_pct, 2),
            "explanation_narrative": self.explanation_narrative,
            "top_contributing_terms": [t.to_dict() for t in self.top_terms],
        }


def compute_term_drilldown(
    submission_tokens: Optional[Union[List[str], str]] = None,
    reference_tokens: Optional[Union[List[str], str]] = None,
    idf_table: Optional[Dict[str, float]] = None,
    default_idf: float = 1.0,
    top_k: int = 15,
    submission_text: Optional[str] = None,
    reference_text: Optional[str] = None,
    filter_stopwords: bool = False,
) -> TermDrillDownReport:
    """
    Menghitung dekomposisi kontribusi per kata antara submisi dan satu dokumen rujukan.
    Mendukung input berupa list token maupun string teks utuh.
    """
    if submission_tokens is None and submission_text is not None:
        submission_tokens = tokenize(normalize_text(submission_text))
    elif isinstance(submission_tokens, str):
        submission_tokens = tokenize(normalize_text(submission_tokens))

    if reference_tokens is None and reference_text is not None:
        reference_tokens = tokenize(normalize_text(reference_text))
    elif isinstance(reference_tokens, str):
        reference_tokens = tokenize(normalize_text(reference_tokens))

    if filter_stopwords:
        from worker.tfidf import INDONESIAN_STOPWORDS
        if submission_tokens:
            submission_tokens = [t for t in submission_tokens if t not in INDONESIAN_STOPWORDS]
        if reference_tokens:
            reference_tokens = [t for t in reference_tokens if t not in INDONESIAN_STOPWORDS]

    if not submission_tokens or not reference_tokens:
        return TermDrillDownReport(
            cosine_similarity=0.0,
            total_shared_terms=0,
            total_dot_product=0.0,
            explanation_narrative="Tidak ada istilah yang beririsan."
        )

    if idf_table is None:
        idf_table = {}

    sub_counts = Counter(submission_tokens)
    ref_counts = Counter(reference_tokens)

    len_sub = len(submission_tokens)
    len_ref = len(reference_tokens)

    # Hitung TF sub dan ref
    sub_tf = {t: c / len_sub for t, c in sub_counts.items()}
    ref_tf = {t: c / len_ref for t, c in ref_counts.items()}

    # Hitung vektor TF-IDF
    sub_vec = {t: sub_tf[t] * idf_table.get(t, default_idf) for t in sub_tf}
    ref_vec = {t: ref_tf[t] * idf_table.get(t, default_idf) for t in ref_tf}

    # Hitung norma Euclidean
    sub_norm = math.sqrt(sum(v * v for v in sub_vec.values()))
    ref_norm = math.sqrt(sum(v * v for v in ref_vec.values()))

    # Cari shared terms
    shared_terms = set(sub_tf.keys()).intersection(ref_tf.keys())
    if not shared_terms or sub_norm == 0 or ref_norm == 0:
        return TermDrillDownReport(
            cosine_similarity=0.0,
            total_shared_terms=0,
            total_dot_product=0.0,
            explanation_narrative="Tidak ada istilah yang beririsan di antara kedua dokumen."
        )

    # Hitung dot product per term
    raw_dot_list = []
    total_dot = 0.0

    for t in shared_terms:
        w_sub = sub_vec[t]
        w_ref = ref_vec[t]
        dot = w_sub * w_ref
        total_dot += dot
        idf = idf_table.get(t, default_idf)
        raw_dot_list.append({
            "term": t,
            "sub_count": sub_counts[t],
            "doc_count": ref_counts[t],
            "sub_tf": sub_tf[t],
            "doc_tf": ref_tf[t],
            "idf": idf,
            "sub_weight": w_sub,
            "doc_weight": w_ref,
            "dot_product": dot,
            "tier": TermSignificanceTier.from_idf(idf),
        })

    # Cosine Similarity
    cosine_val = (total_dot / (sub_norm * ref_norm)) if (sub_norm * ref_norm) > 0 else 0.0
    cosine_pct = round(cosine_val * 100.0, 2)

    # Urutkan berdasarkan kontribusi dot product terbesar
    raw_dot_list.sort(key=lambda x: x["dot_product"], reverse=True)

    top_terms: List[TermContribution] = []
    disciplinary_count = 0
    topical_count = 0
    common_count = 0
    disciplinary_dot = 0.0

    for item in raw_dot_list:
        pct = (item["dot_product"] / total_dot * 100.0) if total_dot > 0 else 0.0
        tier = item["tier"]

        if tier == TermSignificanceTier.DISCIPLINARY_KEYWORD:
            disciplinary_count += 1
            disciplinary_dot += item["dot_product"]
        elif tier == TermSignificanceTier.TOPICAL_TERM:
            topical_count += 1
        else:
            common_count += 1

        if len(top_terms) < top_k:
            top_terms.append(TermContribution(
                term=item["term"],
                sub_count=item["sub_count"],
                doc_count=item["doc_count"],
                sub_tf=item["sub_tf"],
                doc_tf=item["doc_tf"],
                idf=item["idf"],
                sub_weight=item["sub_weight"],
                doc_weight=item["doc_weight"],
                dot_product=item["dot_product"],
                contribution_pct=pct,
                significance_tier=tier,
            ))

    disc_pct = (disciplinary_dot / total_dot * 100.0) if total_dot > 0 else 0.0

    # Susun Narasi Penjelasan Transparan bagi Reviewer
    top_3_terms = ", ".join(f"'{t.term}' ({t.contribution_pct:.1f}%)" for t in top_terms[:3]) if top_terms else "-"
    narrative = (
        f"Kemiripan leksikal {cosine_pct:.2f}% didorong oleh {len(shared_terms)} istilah bersama, "
        f"dengan kontributor utama: {top_3_terms}. "
    )
    if disc_pct >= 50.0:
        narrative += (
            f"Sebagian besar bobot ({disc_pct:.1f}%) berasal dari istilah spesifik disiplin ilmu "
            f"({disciplinary_count} kata kunci berbobot tinggi), mengindikasikan kesamaan medan makna "
            "dan topik penelitian yang serumpun."
        )
    elif common_count > (disciplinary_count + topical_count):
        narrative += (
            "Mayoritas irisan kosakata berasal dari istilah akademik umum, "
            "menunjukkan gaya penulisan ilmiah standar tanpa kekhususan topik yang tajam."
        )
    else:
        narrative += (
            "Distribusi istilah terbagi merata antara terminologi topik dan kosakata ilmiah standar."
        )

    return TermDrillDownReport(
        cosine_similarity=cosine_pct,
        total_shared_terms=len(shared_terms),
        total_dot_product=total_dot,
        top_terms=top_terms,
        disciplinary_terms_count=disciplinary_count,
        topical_terms_count=topical_count,
        common_terms_count=common_count,
        disciplinary_contribution_pct=round(disc_pct, 2),
        explanation_narrative=narrative,
    )
