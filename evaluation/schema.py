"""
AR Tilik - Evaluation Dataset & Annotation Schema
Mendefinisikan tipe data, kontrak data evaluasi, taksonomi kegagalan (Failure Taxonomy),
konteks sitasi, interpretasi manusia, dan metadata provenans v1.0.0
"""

from dataclasses import dataclass, field, asdict
from typing import Optional, Dict, Any, List
from datetime import datetime

# Kasus Evaluasi
CASE_TYPE_SEED = "SEED"
CASE_TYPE_MUTATION = "MUTATION"
CASE_TYPE_BOUNDARY = "BOUNDARY"
CASE_TYPE_ADVERSARIAL = "ADVERSARIAL"
CASE_TYPE_REAL_WORLD = "REAL_WORLD"

# Level & Operator Mutasi
MUTATION_LEXICAL = "LEXICAL_EDIT"
MUTATION_DELETION_INSERTION = "DELETION_INSERTION"
MUTATION_RESTRUCTURING = "RESTRUCTURING"
MUTATION_PARAPHRASE = "PARAPHRASE"
MUTATION_MIXED = "MIXED_TRANSFORMATION"

# Kekuatan Mutasi (Mutation Strength)
STRENGTH_LIGHT = "LIGHT"
STRENGTH_MEDIUM = "MEDIUM"
STRENGTH_HEAVY = "HEAVY"

# Kategori Adversarial
ADV_METHODOLOGY = "METHODOLOGY_BOILERPLATE"
ADV_CITATION = "CITATION_PROTECTED"
ADV_QUOTE = "EXACT_QUOTE"
ADV_TERMINOLOGY = "TERMINOLOGY_OVERLAP"

# Konteks Sitasi (Citation Context)
CITATION_CONTEXT_NONE = "NONE"
CITATION_CONTEXT_INLINE = "INLINE_CITATION"
CITATION_CONTEXT_PARENTHETICAL = "PARENTHETICAL_CITATION"
CITATION_CONTEXT_BLOCK_QUOTE = "BLOCK_QUOTE"
CITATION_CONTEXT_DIRECT_QUOTE = "DIRECT_QUOTE"
CITATION_CONTEXT_FOOTNOTE = "FOOTNOTE"
CITATION_CONTEXT_BIBLIOGRAPHY = "BIBLIOGRAPHY"
CITATION_CONTEXT_UNKNOWN = "UNKNOWN"

# Interpretasi Manusia (Human Interpretation)
HUMAN_INTERPRETATION_DIRECT_OVERLAP_RELEVANT = "DIRECT_OVERLAP_RELEVANT"
HUMAN_INTERPRETATION_DIRECT_OVERLAP_PROPERLY_CITED = "DIRECT_OVERLAP_PROPERLY_CITED"
HUMAN_INTERPRETATION_COMMON_ACADEMIC_LANGUAGE = "COMMON_ACADEMIC_LANGUAGE"
HUMAN_INTERPRETATION_METHODOLOGY_BOILERPLATE = "METHODOLOGY_BOILERPLATE"
HUMAN_INTERPRETATION_NEEDS_INVESTIGATION = "NEEDS_INVESTIGATION"
HUMAN_INTERPRETATION_FALSE_POSITIVE = "FALSE_POSITIVE"
HUMAN_INTERPRETATION_INSUFFICIENT_EVIDENCE = "INSUFFICIENT_EVIDENCE"

# Status Akademik Bukti (Citation Status)
CITATION_STATUS_NOT_CITED = "NOT_CITED"
CITATION_STATUS_CITED = "CITED"
CITATION_STATUS_PARTIALLY_CITED = "PARTIALLY_CITED"
CITATION_STATUS_QUOTE = "QUOTE"
CITATION_STATUS_PARAPHRASED_WITH_CITATION = "PARAPHRASED_WITH_CITATION"
CITATION_STATUS_NOT_APPLICABLE = "NOT_APPLICABLE"
CITATION_STATUS_UNKNOWN = "UNKNOWN"

# Tingkat Keyakinan Reviewer (Reviewer Confidence)
CONFIDENCE_HIGH = "HIGH"
CONFIDENCE_MEDIUM = "MEDIUM"
CONFIDENCE_LOW = "LOW"

# Siklus Hidup Peninjauan (Review Lifecycle)
LIFECYCLE_PENDING = "PENDING"
LIFECYCLE_UNDER_REVIEW = "UNDER_REVIEW"
LIFECYCLE_REVIEWED = "REVIEWED"
LIFECYCLE_VALIDATED = "VALIDATED"

# Penilaian Sistem oleh Reviewer Manusia
SYSTEM_ASSESSMENT_CORRECT = "CORRECT"
SYSTEM_ASSESSMENT_ACCEPTABLE = "ACCEPTABLE"
SYSTEM_ASSESSMENT_TOO_STRICT = "TOO_STRICT"
SYSTEM_ASSESSMENT_TOO_LENIENT = "TOO_LENIENT"
SYSTEM_ASSESSMENT_UNCERTAIN = "UNCERTAIN"

# Taksonomi Kegagalan Evaluasi (Failure Taxonomy)
FAILURE_NONE = "NONE"

# 1. Generator Error
FAILURE_GEN_INVALID_MUTATION = "GENERATOR_ERROR:INVALID_MUTATION"
FAILURE_GEN_DUPLICATE_MUTATION = "GENERATOR_ERROR:DUPLICATE_MUTATION"
FAILURE_GEN_INSUFFICIENT_TRANSFORMATION = "GENERATOR_ERROR:INSUFFICIENT_TRANSFORMATION"

# 2. Expectation Error
FAILURE_EXP_WRONG_EXPECTED_CLASS = "EXPECTATION_ERROR:WRONG_EXPECTED_CLASS"
FAILURE_EXP_NON_REPRESENTATIVE_CASE = "EXPECTATION_ERROR:NON_REPRESENTATIVE_CASE"

# 3. Boundary Behavior
FAILURE_BOUNDARY_BEHAVIOR = "BOUNDARY_BEHAVIOR:THRESHOLD_TRANSITION"

# 4. Engine Limitation
FAILURE_LIM_NO_CONTEXT_AWARENESS = "ENGINE_LIMITATION:NO_CONTEXT_AWARENESS"
FAILURE_LIM_NO_CITATION_AWARENESS = "ENGINE_LIMITATION:NO_CITATION_AWARENESS"
FAILURE_LIM_NO_SEMANTIC_CONTEXT = "ENGINE_LIMITATION:NO_SEMANTIC_CONTEXT"

# 5. Engine Defect
FAILURE_ENGINE_DEFECT = "ENGINE_DEFECT"


@dataclass
class EvaluationCase:
    case_id: str
    case_type: str                   # SEED | MUTATION | BOUNDARY | ADVERSARIAL | REAL_WORLD
    source_case: Optional[str]        # e.g. TC-011, or None
    generation_method: str           # LEXICAL_EDIT, THRESHOLD_MINUS_ONE, etc.
    submission_text: str
    source_text: str
    expected_class: Optional[str]     # match | review | no_match | None
    expected_pattern: Optional[str]   # VERBATIM_MATCH, COPY_EDIT, etc.
    human_validated: bool = False
    system_version: str = "v1.0.0"
    mutation_operator: Optional[str] = None
    mutation_strength: Optional[str] = STRENGTH_MEDIUM
    generation_seed: Optional[int] = None
    content_hash: Optional[str] = None
    failure_class: Optional[str] = None
    metadata: Dict[str, Any] = field(default_factory=dict)

    def to_dict(self) -> Dict[str, Any]:
        return asdict(self)

    @classmethod
    def from_dict(cls, data: Dict[str, Any]) -> "EvaluationCase":
        # Pastikan backward-compatibility jika field baru belum ada
        fields = cls.__dataclass_fields__.keys()
        filtered = {k: v for k, v in data.items() if k in fields}
        return cls(**filtered)


@dataclass
class EvaluationResult:
    case_id: str
    case_type: str
    predicted_class: str
    predicted_pattern: str
    agreement: str
    exact_similarity: float
    cosine_similarity: float
    longest_match_words: int
    matched_terms: int
    avg_idf: float
    max_idf: float
    is_pass: bool
    rationale: str
    failure_class: Optional[str] = None
    content_hash: Optional[str] = None
    evaluated_at: str = field(default_factory=lambda: datetime.now().isoformat())

    def to_dict(self) -> Dict[str, Any]:
        return asdict(self)

    @classmethod
    def from_dict(cls, data: Dict[str, Any]) -> "EvaluationResult":
        fields = cls.__dataclass_fields__.keys()
        filtered = {k: v for k, v in data.items() if k in fields}
        return cls(**filtered)


@dataclass
class HumanReviewLabel:
    case_id: str
    reviewer_code: str               # e.g. RV-001
    human_decision: str              # match | review | no_match | properly_cited
    citation_context: str = CITATION_CONTEXT_NONE
    citation_status: str = CITATION_STATUS_NOT_CITED
    human_interpretation: str = HUMAN_INTERPRETATION_DIRECT_OVERLAP_RELEVANT
    evidence_relevant: bool = False
    common_phrase: bool = False
    properly_cited: bool = False
    needs_investigation: bool = False
    false_positive: bool = False
    system_assessment: str = SYSTEM_ASSESSMENT_CORRECT
    reviewer_confidence: str = CONFIDENCE_HIGH
    review_status: str = LIFECYCLE_REVIEWED
    notes: Optional[str] = None
    reviewed_at: str = field(default_factory=lambda: datetime.now().isoformat())

    def to_dict(self) -> Dict[str, Any]:
        return asdict(self)

    @classmethod
    def from_dict(cls, data: Dict[str, Any]) -> "HumanReviewLabel":
        fields = cls.__dataclass_fields__.keys()
        filtered = {k: v for k, v in data.items() if k in fields}
        return cls(**filtered)
