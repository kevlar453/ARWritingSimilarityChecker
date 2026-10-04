# AR TILIK — Post-Release Findings & v1.1 Backlog

**Baseline:** AR TILIK v1.0.0  
**Status:** Frozen / Evaluation Complete  
**Purpose:** Menetapkan temuan pasca-rilis dan kandidat v1.1 tanpa mengubah baseline v1.0.0.  

---

## 1. Baseline Decision

Dua keputusan resmi menjadi dasar dokumen ini:

1. **Engine v1.0.0 resmi dibekukan.** Tidak ada penambahan kode, penyesuaian threshold, atau modifikasi aturan pada v1.0.0.
2. **Human Review Gate tercapai.** Telaah 30 kasus telah diselesaikan dan diarsipkan sebagai baseline validasi pasca-rilis.

Dengan demikian, setiap kebutuhan perubahan berikutnya harus diperlakukan sebagai kandidat **v1.1**, bukan patch terhadap v1.0.0.

---

## 2. Evidence Baseline

Validasi yang telah selesai:

| Validation Layer | Result | Interpretation |
|---|---|---|
| Controlled benchmark | 50/50, F1 1.0000 | Controlled classification validation |
| Synthetic evaluation | 450/450, 100% pass | Mutation, boundary, adversarial testing (0 defect) |
| Production readiness | 6/6 | Operational readiness & edge cases |
| E2E regression | 50/50, F1 1.0000 | Dataset → API → Worker → DB → HTML |
| Real-world thesis pilot | 34,470 words, 6.12 s | Real-world evidence decomposition (REVIEW, Exact 0.03%) |
| Human review | 30/30 CORRECT | Prioritized contextual validation (100% agreement) |

Angka-angka tersebut merupakan hasil pada dataset dan sample yang diuji. Tidak boleh digabung menjadi klaim universal tentang production accuracy.

---

## 3. Post-Release Findings

### Finding F-01 — Evidence Lens Divergence Is a Useful Distinction
AR TILIK dapat menunjukkan perbedaan antara:
- direct/sequential textual overlap melalui Exact;
- lexical/topical similarity melalui TF-IDF.

Contoh pada evaluasi menunjukkan kasus dengan Exact sangat rendah atau nol tetapi TF-IDF tetap menunjukkan similarity.

**Status:** Validated behavior / design strength.  
**Action:** Pertahankan dual-lens architecture pada v1.1.  

---

### Finding F-02 — Citation Context Is Outside the Core v1.0.0 Decision Model
Human review cases menunjukkan bahwa textual overlap dapat muncul bersama citation atau quotation context.

Implikasinya:
> *textual overlap detection dan academic interpretation adalah dua lapisan yang berbeda.*

**Status:** Confirmed product limitation, bukan confirmed engine defect.  
**Candidate v1.1:** Citation/context awareness.  

---

### Finding F-03 — Methodology and Academic Boilerplate Need Contextual Interpretation
Terminologi disipliner, formula metodologi, dan bahasa akademik standar dapat menghasilkan lexical overlap tanpa secara otomatis menunjukkan problematic copying.

**Status:** Confirmed contextual limitation.  
**Candidate v1.1:** Context-aware classification/presentation, terutama untuk methodology boilerplate dan common academic language.  

---

### Finding F-04 — Source Ranking Should Explicitly Separate Evidence Types
Source evidence yang memiliki TF-IDF similarity tetapi Exact = 0 dapat mudah disalahartikan sebagai direct textual overlap jika hanya dilihat sebagai satu daftar similarity.

**Status:** Confirmed presentation risk.  
**Candidate v1.1:** Source evidence grouping:
```text
SOURCE EVIDENCE
├── Direct textual overlap
│   └── Exact sequential evidence
└── Lexical/topic similarity
    └── TF-IDF evidence
```

---

### Finding F-05 — Human Review Layer Is Sufficient for Initial Post-Release Validation
The 30-case prioritized review was completed and all cases were assessed `CORRECT`.

**Status:** Baseline validation complete.  
**Action:** Freeze the 30-case review set and its annotations. Do not rewrite the review results to fit a future v1.1 design.  

---

### Finding F-06 — Synthetic Evaluation Should Not Be Expanded Solely to Increase the Pass Rate
The 450-case synthetic suite reached 100% pass with zero confirmed engine defects. Further synthetic cases should only be generated when they answer a defined evaluation question.

**Status:** Evaluation scope complete for v1.0.0.  
**Action:** Stop pass-rate-driven synthetic expansion. Future synthetic suites should be tied to explicit v1.1 hypotheses.  

---

## 4. v1.1 Candidate Backlog

### V11-01 — Citation/Context Awareness
**Priority:** High  
**Goal:** Distinguish textual evidence from academic attribution context.  
**Potential contexts:**
- `NONE`
- `INLINE_CITATION`
- `PARENTHETICAL_CITATION`
- `DIRECT_QUOTE`
- `BLOCK_QUOTE`
- `FOOTNOTE`
- `BIBLIOGRAPHY`
- `UNKNOWN`

**Constraint:** The feature should not silently convert textual overlap into `NO_MATCH`. It should enrich evidence interpretation.

---

### V11-02 — Evidence-Type Source Grouping
**Priority:** High  
**Goal:** Make the distinction between Exact and TF-IDF visually explicit in source ranking.  
**Suggested grouping:**
```text
Direct Textual Overlap
    Exact evidence

Lexical / Topic Similarity
    TF-IDF evidence
```

---

### V11-03 — Reviewer Feedback Loop
**Priority:** Medium  
**Potential labels:**
- `EVIDENCE_RELEVANT`
- `COMMON_PHRASE`
- `PROPERLY_CITED`
- `METHODOLOGY_BOILERPLATE`
- `NEEDS_INVESTIGATION`
- `FALSE_POSITIVE`
- `INSUFFICIENT_EVIDENCE`

---

### V11-04 — Expanded Human Validation Dataset
**Priority:** Medium  
Expand human validation only when concrete research or evaluation questions require it. Human-reviewed data must remain separate from synthetic labels.

---

### V11-05 — Larger Operational Observability Sample
**Priority:** Medium  
Track queue wait, extraction time, engine processing time, persistence time, total request-to-report time across a substantially larger production sample before making claims about p95/p99 latency.

---

## 5. Explicitly Out of Scope for v1.1 Unless New Evidence Appears

The following should not be changed merely because the evaluation was completed:
- Exact thresholds
- TF-IDF thresholds
- Hybrid decision rules
- `SHINGLE_SIZE`
- Existing benchmark labels
- Production v1.0.0 reports
- Frozen evidence contracts

---

## 6. Proposed v1.1 Acceptance Gates

A future v1.1 should not be released until it passes:
- **Gate V11-A (Regression):** All v1.0.0 benchmark and regression tests remain passing.
- **Gate V11-B (Synthetic):** New synthetic cases specifically targeting changed behavior pass defined gates.
- **Gate V11-C (Human Validation):** Changed behavior is tested on a new human-reviewed sample.
- **Gate V11-D (Evidence Integrity):** System evidence remains traceable to underlying submission/source text.
- **Gate V11-E (Interpretation Safety):** The system does not equate similarity alone with academic misconduct.

---

## 7. Versioning Rule

> **v1.0.0 is the frozen reference implementation. v1.1 is a new evaluated system, not a modified continuation of the frozen baseline.**
