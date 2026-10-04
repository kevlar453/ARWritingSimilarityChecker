"""
AR Tilik - Evaluation Dataset & Annotation Schema
Mendefinisikan tipe data, kontrak data evaluasi, dan model hasil evaluasi v1.0.0
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

# Level Mutasi
MUTATION_LEXICAL = "LEXICAL_EDIT"
MUTATION_DELETION_INSERTION = "DELETION_INSERTION"
MUTATION_RESTRUCTURING = "RESTRUCTURING"
MUTATION_PARAPHRASE = "PARAPHRASE"
MUTATION_MIXED = "MIXED_TRANSFORMATION"

# Kategori Adversarial
ADV_METHODOLOGY = "METHODOLOGY_BOILERPLATE"
ADV_CITATION = "CITATION_PROTECTED"
ADV_QUOTE = "EXACT_QUOTE"
ADV_TERMINOLOGY = "TERMINOLOGY_OVERLAP"

# Label Penilaian Sistem oleh Reviewer Manusia
SYSTEM_ASSESSMENT_CORRECT = "CORRECT"
SYSTEM_ASSESSMENT_ACCEPTABLE = "ACCEPTABLE"
SYSTEM_ASSESSMENT_TOO_STRICT = "TOO_STRICT"
SYSTEM_ASSESSMENT_TOO_LENIENT = "TOO_LENIENT"
SYSTEM_ASSESSMENT_UNCERTAIN = "UNCERTAIN"

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
    metadata: Dict[str, Any] = field(default_factory=dict)

    def to_dict(self) -> Dict[str, Any]:
        return asdict(self)

    @classmethod
    def from_dict(cls, data: Dict[str, Any]) -> "EvaluationCase":
        return cls(**data)

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
    evaluated_at: str = field(default_factory=lambda: datetime.now().isoformat())

    def to_dict(self) -> Dict[str, Any]:
        return asdict(self)

    @classmethod
    def from_dict(cls, data: Dict[str, Any]) -> "EvaluationResult":
        return cls(**data)

@dataclass
class HumanReviewLabel:
    case_id: str
    reviewer_code: str               # e.g. RV-001
    human_decision: str              # match | review | no_match
    evidence_relevant: bool = False
    common_phrase: bool = False
    properly_cited: bool = False
    needs_investigation: bool = False
    false_positive: bool = False
    system_assessment: str = SYSTEM_ASSESSMENT_CORRECT
    notes: Optional[str] = None
    reviewed_at: str = field(default_factory=lambda: datetime.now().isoformat())

    def to_dict(self) -> Dict[str, Any]:
        return asdict(self)

    @classmethod
    def from_dict(cls, data: Dict[str, Any]) -> "HumanReviewLabel":
        return cls(**data)
