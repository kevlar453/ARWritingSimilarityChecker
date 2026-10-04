# AR TILIK v1.1 — Specification

**Baseline:** AR TILIK v1.0.0  
**Baseline Status:** FROZEN  
**Specification Status:** Draft Approved / Implementation Baseline  
**Primary Objective:** Improve academic-context interpretation without weakening the evidence-detection baseline.  

---

## 1. Purpose

AR TILIK v1.1 is a new evaluated version built on the frozen v1.0.0 baseline.

The purpose of v1.1 is **not** to replace textual similarity detection. It is to add contextual interpretation and clearer evidence presentation around the existing Exact + TF-IDF dual-lens architecture.

The initial scope is intentionally limited to two primary capabilities:

1. **Citation / Context Awareness**
2. **Direct Textual Overlap vs Lexical/Topic Similarity presentation**

Supporting evaluation infrastructure may be retained or extended where necessary, but should not expand the core scope without explicit approval.

---

## 2. Non-Goals

v1.1 must not:
- redefine academic misconduct;
- make disciplinary or ethical sanctions;
- treat similarity alone as proof of plagiarism;
- silently convert detected textual overlap into `NO_MATCH`;
- remove evidence because a citation is present;
- modify v1.0.0 production reports;
- alter v1.0.0 thresholds without a separately documented experiment;
- claim universal production accuracy from benchmark or synthetic results.

---

## 3. Frozen Baseline

The following v1.0.0 components are the reference baseline:
- Exact engine;
- TF-IDF engine;
- Hybrid Evidence Layer;
- existing thresholds;
- evidence contract;
- benchmark labels;
- production decision behavior.

Any intentional behavioral change must be versioned as v1.1 behavior and evaluated against the v1.0.0 baseline.

---

## 4. Feature V11-01 — Citation / Context Awareness

### 4.1 Objective
Identify contextual signals around detected evidence so reviewers can distinguish:
- direct overlap that appears uncited;
- direct overlap accompanied by citation;
- quotation;
- paraphrase with citation;
- bibliography/reference context;
- methodology or common academic language.

The feature enriches evidence. It does not erase or suppress underlying textual evidence.

### 4.2 Context Taxonomy
```text
NONE
INLINE_CITATION
PARENTHETICAL_CITATION
BLOCK_QUOTE
DIRECT_QUOTE
FOOTNOTE
BIBLIOGRAPHY
UNKNOWN
```
Where source parsing cannot establish the context reliably, use `UNKNOWN` rather than infer a positive citation state.

### 4.3 Citation Status
```text
NOT_CITED
CITED
PARTIALLY_CITED
QUOTE
PARAPHRASED_WITH_CITATION
NOT_APPLICABLE
UNKNOWN
```

### 4.4 Evidence Interpretation
```text
TEXTUAL EVIDENCE: Exact / TF-IDF evidence
ACADEMIC CONTEXT: Citation / quotation / methodology context
SYSTEM DECISION:  MATCH / REVIEW / NO_MATCH
```
The system must not automatically change `REVIEW` to `NO_MATCH` merely because citation context is detected.

### 4.5 Detection Scope for v1.1
Initial implementation focuses on deterministic, explainable context signals:
- parenthetical author/year patterns;
- common inline citation markers;
- quotation marks;
- block-quote formatting where reliably available;
- footnote markers where extraction preserves them;
- bibliography/reference sections;
- recognizable citation patterns in extracted text.

### 4.6 Context Boundary
Citation context should be associated with the relevant evidence span where possible. Unrelated citations elsewhere in the document should not be applied. If unavailable, use `UNKNOWN`.

---

## 5. Feature V11-02 — Evidence-Type Source Grouping

### 5.1 Objective
Prevent cosine-only similarity from being visually interpreted as direct textual copying.

Recommended presentation:
```text
SOURCE EVIDENCE
1. Direct Textual Overlap
   └── Exact sequential evidence
2. Lexical / Topic Similarity
   └── TF-IDF evidence
```

### 5.2 Direct Textual Overlap
Driven by Exact evidence: Exact similarity, longest exact passage, matched text, source excerpt, identifier. If Exact = 0, source does not appear in this group.

### 5.3 Lexical / Topic Similarity
Driven by TF-IDF evidence: TF-IDF cosine, shared terms, average/max IDF.

### 5.4 Divergence State
When Exact ≈ 0 and TF-IDF > 0, the report explicitly states that the source is topically related, but no sequential textual passage was detected.

---

## 6. Evidence Contract Extension

The v1.1 evidence contract extends:
```text
evidence_type
citation_context
citation_status
citation_confidence
context_span
```

Allowed values for `evidence_type`:
```text
DIRECT_TEXTUAL_OVERLAP
LEXICAL_TOPIC_SIMILARITY
COMMON_ACADEMIC_LANGUAGE
METHODOLOGY_BOILERPLATE
UNKNOWN
```

Existing v1.0.0 fields remain backward compatible.

---

## 7. Report/UI Requirements

The report preserves the existing overall decision while adding contextual decomposition.

---

## 8. Acceptance Gates

- **Gate V11-A (Regression):** Existing benchmark and E2E regression remain passing.
- **Gate V11-B (Context Detection):** New citation/context cases meet predefined targets.
- **Gate V11-C (Evidence Integrity):** Every contextual finding remains traceable to observable text.
- **Gate V11-D (No Evidence Suppression):** Citation detection must not suppress underlying Exact/TF-IDF evidence.
- **Gate V11-E (Human Agreement):** Contextual interpretation acceptable to reviewers.
- **Gate V11-F (Interpretation Safety):** Distinction between similarity and misconduct maintained.

---

## 9. Final Specification Principle

> **Detect first, decompose second, contextualize third, and leave substantive academic judgment to the reviewer.**
