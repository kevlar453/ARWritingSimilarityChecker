"""
AR Tilik v1.1 - Evidence Contract & Context Schema
Memperluas Evidence Contract v1.0.0 dengan metadata konteks sitasi dan
klasifikasi tipe bukti akademik tanpa mengubah baseline engine v1.0.0.

Baseline: v1.0.0 FROZEN (worker/hybrid.py)
Sprint: 1 (WP-01)
"""

from dataclasses import dataclass, field
from enum import Enum
from typing import Optional, List, Dict, Any


class EvidenceType(str, Enum):
    """
    Taksonomi jenis bukti teks vs kemiripan leksikal v1.1.
    """
    DIRECT_TEXTUAL_OVERLAP = "DIRECT_TEXTUAL_OVERLAP"
    LEXICAL_TOPIC_SIMILARITY = "LEXICAL_TOPIC_SIMILARITY"
    COMMON_ACADEMIC_LANGUAGE = "COMMON_ACADEMIC_LANGUAGE"
    METHODOLOGY_BOILERPLATE = "METHODOLOGY_BOILERPLATE"
    UNKNOWN = "UNKNOWN"

    @classmethod
    def from_str(cls, value: Optional[str]) -> "EvidenceType":
        if not value:
            return cls.UNKNOWN
        try:
            return cls(str(value).strip().upper())
        except (ValueError, KeyError):
            return cls.UNKNOWN


class CitationContext(str, Enum):
    """
    Taksonomi konteks penandaan sitasi atau kutipan pada teks bukti.
    """
    NONE = "NONE"
    INLINE_CITATION = "INLINE_CITATION"
    PARENTHETICAL_CITATION = "PARENTHETICAL_CITATION"
    BLOCK_QUOTE = "BLOCK_QUOTE"
    DIRECT_QUOTE = "DIRECT_QUOTE"
    FOOTNOTE = "FOOTNOTE"
    BIBLIOGRAPHY = "BIBLIOGRAPHY"
    UNKNOWN = "UNKNOWN"

    @classmethod
    def from_str(cls, value: Optional[str]) -> "CitationContext":
        if not value:
            return cls.UNKNOWN
        try:
            return cls(str(value).strip().upper())
        except (ValueError, KeyError):
            return cls.UNKNOWN


class CitationStatus(str, Enum):
    """
    Status atribusi akademik pasase yang terdeteksi.
    """
    NOT_CITED = "NOT_CITED"
    CITED = "CITED"
    PARTIALLY_CITED = "PARTIALLY_CITED"
    QUOTE = "QUOTE"
    PARAPHRASED_WITH_CITATION = "PARAPHRASED_WITH_CITATION"
    NOT_APPLICABLE = "NOT_APPLICABLE"
    UNKNOWN = "UNKNOWN"

    @classmethod
    def from_str(cls, value: Optional[str]) -> "CitationStatus":
        if not value:
            return cls.UNKNOWN
        try:
            return cls(str(value).strip().upper())
        except (ValueError, KeyError):
            return cls.UNKNOWN


class CitationConfidence(str, Enum):
    """
    Tingkat kepastian deteksi konteks akademik.
    """
    HIGH = "HIGH"
    MEDIUM = "MEDIUM"
    LOW = "LOW"
    UNKNOWN = "UNKNOWN"

    @classmethod
    def from_str(cls, value: Optional[str]) -> "CitationConfidence":
        if not value:
            return cls.UNKNOWN
        try:
            return cls(str(value).strip().upper())
        except (ValueError, KeyError):
            return cls.UNKNOWN


# Field kunci v1.0.0 untuk verifikasi kompatibilitas
V1_0_CORE_FIELDS = (
    "engine",
    "similarity",
    "matched_terms",
    "longest_match",
    "source_count",
    "match_count",
    "avg_idf",
    "max_idf",
    "top_sources",
)

# Field ekstensi v1.1 untuk konteks akademik
V1_1_EXTENSION_FIELDS = (
    "evidence_type",
    "citation_context",
    "citation_status",
    "citation_confidence",
    "context_span",
    "rationale",
)


@dataclass
class EvidenceContractV11:
    """
    Kontrak data bukti v1.1 seragam untuk penganalisis kemiripan dan konteks akademik.
    Menjaga kompatibilitas ke belakang (backward compatibility) dengan v1.0.0.
    """
    # ── Core v1.0.0 Fields ───────────────────────────────────────
    engine: str
    similarity: float
    matched_terms: int
    longest_match: Optional[int]
    source_count: int
    match_count: int
    avg_idf: Optional[float]
    max_idf: Optional[float]
    top_sources: List[Dict[str, Any]] = field(default_factory=list)

    # ── v1.1 Extension Fields ────────────────────────────────────
    evidence_type: str = EvidenceType.UNKNOWN.value
    citation_context: str = CitationContext.UNKNOWN.value
    citation_status: str = CitationStatus.UNKNOWN.value
    citation_confidence: str = CitationConfidence.UNKNOWN.value
    context_span: Optional[str] = None
    rationale: Optional[str] = None

    def __post_init__(self):
        # Validasi & normalisasi field numerik v1.0
        self.engine = str(self.engine).strip().lower()
        self.similarity = round(float(self.similarity), 4)
        if self.similarity < 0.0 or self.similarity > 100.0:
            raise ValueError(f"similarity must be between 0.0 and 100.0, got {self.similarity}")

        self.matched_terms = int(self.matched_terms)
        if self.matched_terms < 0:
            raise ValueError(f"matched_terms must be >= 0, got {self.matched_terms}")

        if self.longest_match is not None:
            self.longest_match = int(self.longest_match)
            if self.longest_match < 0:
                raise ValueError(f"longest_match must be >= 0, got {self.longest_match}")

        self.source_count = int(self.source_count)
        if self.source_count < 0:
            raise ValueError(f"source_count must be >= 0, got {self.source_count}")

        self.match_count = int(self.match_count)
        if self.match_count < 0:
            raise ValueError(f"match_count must be >= 0, got {self.match_count}")

        if self.avg_idf is not None:
            self.avg_idf = round(float(self.avg_idf), 4)
            if self.avg_idf < 0.0:
                raise ValueError(f"avg_idf must be >= 0.0, got {self.avg_idf}")

        if self.max_idf is not None:
            self.max_idf = round(float(self.max_idf), 4)
            if self.max_idf < 0.0:
                raise ValueError(f"max_idf must be >= 0.0, got {self.max_idf}")

        if not isinstance(self.top_sources, list):
            self.top_sources = list(self.top_sources)

        # Normalisasi enum ekstensi v1.1 dengan fallback aman ke UNKNOWN
        self.evidence_type = EvidenceType.from_str(self.evidence_type).value
        self.citation_context = CitationContext.from_str(self.citation_context).value
        self.citation_status = CitationStatus.from_str(self.citation_status).value
        self.citation_confidence = CitationConfidence.from_str(self.citation_confidence).value

        if self.context_span is not None:
            span_str = str(self.context_span)
            if not span_str.strip():
                self.context_span = None
            else:
                self.context_span = span_str

        if self.rationale is not None:
            self.rationale = str(self.rationale).strip()
            if not self.rationale:
                self.rationale = None

    def to_dict(self) -> Dict[str, Any]:
        """
        Serialisasi deterministik dengan urutan kunci terstandar (v1.0 + v1.1).
        Menghasilkan tipe data primitif Python yang aman untuk JSON serialization.
        """
        return {
            # v1.0 core fields (preserving exact names and types)
            "engine": self.engine,
            "similarity": self.similarity,
            "matched_terms": self.matched_terms,
            "longest_match": self.longest_match,
            "source_count": self.source_count,
            "match_count": self.match_count,
            "avg_idf": self.avg_idf,
            "max_idf": self.max_idf,
            "top_sources": self.top_sources,
            # v1.1 extension fields
            "evidence_type": self.evidence_type,
            "citation_context": self.citation_context,
            "citation_status": self.citation_status,
            "citation_confidence": self.citation_confidence,
            "context_span": self.context_span,
            "rationale": self.rationale,
        }

    @classmethod
    def from_v1_0(
        cls,
        v1_contract: Dict[str, Any],
        evidence_type: Optional[str] = None,
        citation_context: Optional[str] = None,
        citation_status: Optional[str] = None,
        citation_confidence: Optional[str] = None,
        context_span: Optional[str] = None,
        rationale: Optional[str] = None,
    ) -> "EvidenceContractV11":
        """
        Factory untuk mengonversi kontrak v1.0.0 ke v1.1 tanpa mengubah data v1.0.0 asli.
        Field ekstensi v1.1 diisi dengan nilai aman (default UNKNOWN jika tidak disediakan).
        """
        for req in V1_0_CORE_FIELDS:
            if req not in v1_contract:
                raise KeyError(f"Missing required v1.0 core field: '{req}'")

        # Jika evidence_type tidak disediakan eksplisit, deduksikan nilai awal aman
        resolved_ev_type = evidence_type
        if resolved_ev_type is None:
            eng = str(v1_contract.get("engine", "")).lower()
            sim = float(v1_contract.get("similarity", 0.0))
            if eng == "exact" and sim > 0:
                resolved_ev_type = EvidenceType.DIRECT_TEXTUAL_OVERLAP.value
            elif eng == "tfidf" and sim > 0:
                resolved_ev_type = EvidenceType.LEXICAL_TOPIC_SIMILARITY.value
            else:
                resolved_ev_type = EvidenceType.UNKNOWN.value

        return cls(
            engine=v1_contract["engine"],
            similarity=v1_contract["similarity"],
            matched_terms=v1_contract["matched_terms"],
            longest_match=v1_contract["longest_match"],
            source_count=v1_contract["source_count"],
            match_count=v1_contract["match_count"],
            avg_idf=v1_contract["avg_idf"],
            max_idf=v1_contract["max_idf"],
            top_sources=v1_contract.get("top_sources", []),
            evidence_type=resolved_ev_type,
            citation_context=citation_context or CitationContext.UNKNOWN.value,
            citation_status=citation_status or CitationStatus.UNKNOWN.value,
            citation_confidence=citation_confidence or CitationConfidence.UNKNOWN.value,
            context_span=context_span,
            rationale=rationale,
        )

    @classmethod
    def from_dict(cls, data: Dict[str, Any]) -> "EvidenceContractV11":
        """
        Instansiasi dari kamus arbitrary (v1.0 atau v1.1).
        """
        return cls(
            engine=data.get("engine", "exact"),
            similarity=data.get("similarity", 0.0),
            matched_terms=data.get("matched_terms", 0),
            longest_match=data.get("longest_match"),
            source_count=data.get("source_count", 0),
            match_count=data.get("match_count", 0),
            avg_idf=data.get("avg_idf"),
            max_idf=data.get("max_idf"),
            top_sources=data.get("top_sources", []),
            evidence_type=data.get("evidence_type", EvidenceType.UNKNOWN.value),
            citation_context=data.get("citation_context", CitationContext.UNKNOWN.value),
            citation_status=data.get("citation_status", CitationStatus.UNKNOWN.value),
            citation_confidence=data.get("citation_confidence", CitationConfidence.UNKNOWN.value),
            context_span=data.get("context_span"),
            rationale=data.get("rationale"),
        )


def validate_v1_0_contract_compatibility(v1_dict: Dict[str, Any]) -> bool:
    """
    Memvalidasi apakah dictionary memiliki seluruh 9 field wajib v1.0.0.
    """
    return all(field in v1_dict for field in V1_0_CORE_FIELDS)
