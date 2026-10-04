# AR TILIK v1.0.0 — Post-Release Evaluation

**Status:** Evaluation Freeze  
**Engine:** AR TILIK Dual-Lens Hybrid v1.0.0 (Frozen)  
**Evaluation Phase:** Post-Release Validation & Human Review Layer  
**Repository:** [https://github.com/kevlar453/ARWritingSimilarityChecker](https://github.com/kevlar453/ARWritingSimilarityChecker)  

---

## 1. Release Baseline

AR TILIK v1.0.0 is treated as the frozen production baseline for this evaluation.

The evaluation layer does not modify the Exact engine, TF-IDF engine, Hybrid Evidence Layer, thresholds, or production decision rules.

The purpose of this phase is to evaluate the behavior of the frozen release through synthetic validation, production-readiness testing, regression testing, a real-world thesis pilot, and prioritized human review.

---

## 2. Benchmark Validation

### 2.1 Benchmark Result

The 50-case benchmark produced:

- Total cases: **50**
- Correct: **50/50**
- Binary Precision: **1.0000**
- Binary Recall: **1.0000**
- Binary F1: **1.0000**
- Multiclass Macro F1: **1.0000**
- Misclassified: **0**

The benchmark consisted of:

- `TC-001`–`TC-010`: identical
- `TC-011`–`TC-020`: copy-edit
- `TC-021`–`TC-030`: paraphrase
- `TC-031`–`TC-040`: common phrase
- `TC-041`–`TC-050`: unrelated

**Interpretation:** The frozen hybrid system correctly classified all benchmark cases in this controlled dataset.

**Caveat:** This benchmark is a controlled evaluation set and should not be interpreted as production accuracy.

---

## 3. Synthetic Evaluation

The synthetic evaluation suite was executed without changing the production engine.

### 3.1 Result

| Suite | Cases | Pass | Pass Rate | Status Gate |
|---|---|---|---|---|
| Mutation | 250 | 250 | 100% | Gate A: LULUS (≥ 95%) |
| Boundary | 100 | 100 | 100% | Gate B: LULUS (Deterministik) |
| Adversarial | 100 | 100 | 100% | Gate C: LULUS (0 Unexpected MATCH) |
| **Total** | **450** | **450** | **100%** | **450 / 450 Pass** |

Additional result:

- Confirmed engine defects: **0 (ZERO DEFECT)**

### 3.2 Interpretation

AR TILIK v1.0.0 achieved a **100% pass rate on the 450-case synthetic evaluation suite**, comprising mutation, boundary, and adversarial cases, with zero confirmed engine defects.

This result is a synthetic test pass rate, not a claim of 100% production accuracy.

---

## 4. Production Readiness

Production-readiness testing covered:

1. Empty 0-byte input
2. Micro-text input (2 words)
3. 3,000-word document without timeout
4. Unicode, mathematical text, emoji, and curly quotes
5. Duplicate submissions / idempotency
6. HTML dashboard rendering

Result:

- **6/6 tests passed**
- No production-readiness failure was reported.

---

## 5. Regression / End-to-End Validation

The 50 benchmark cases were executed through the full end-to-end production path:

$$\text{Dataset} \longrightarrow \text{Upload API} \longrightarrow \text{Worker} \longrightarrow \text{Database} \longrightarrow \text{HTML Report}$$

Result:

- Total: **50**
- Correct: **50**
- Binary F1: **1.0000**
- Macro F1: **1.0000**
- Misclassification: **0**
- Temporary benchmark documents cleaned successfully.

Representative cases:

- `TC-001`: MATCH / VERBATIM_MATCH
- `TC-011`: REVIEW / COPY_EDIT
- `TC-021`: REVIEW / SEMANTIC_PARAPHRASE
- `TC-031`: REVIEW / COMMON_ACADEMIC_PHRASE
- `TC-041` and `TC-050`: NO_MATCH / NO_EVIDENCE

The end-to-end dataset-to-report path was verified intact.

---

## 6. Real-World Thesis Pilot

A real-world thesis pilot was processed using AR TILIK v1.0.0 (Master of Theology thesis by Emiliana Vivi, 2026, 174 pages, 2.13 MB).

Reported characteristics:

- Document volume: **34,470 words**
- Execution time: **6.12 seconds**
- Decision: **REVIEW**
- Exact similarity: **0.03%**
- Longest exact passage: **6 words** (*"Berdasarkan hasil penelitian yang telah dilakukan,"*)
- TF-IDF cosine: **18.82%**
- Shared terms: **60**
- Average IDF: **2.3022**
- Evidence pattern: **SEMANTIC_PARAPHRASE**
- Evidence synthesis: **COMPLEMENTARY_EVIDENCE**

The report separates the very low sequential exact overlap from broader lexical/topical similarity.

The pilot should be interpreted as a real-world evidence-decomposition observation, not as an accuracy measurement.

---

## 7. Human Review

### 7.1 Review Design

A prioritized human-review set of **30 cases** was used, divided equally across **6 Tiers** (5 cases per tier):

1. **Tier 1 — Edge Cases / Former Failures** (`EV-MUT-104`, `EV-MUT-105`, `EV-ADV-005`, `EV-ADV-026`, `EV-ADV-027`)
2. **Tier 2 — High Exact + Citation / Quote** (`EV-ADV-028`, `EV-ADV-029`, `EV-ADV-030`, `EV-ADV-051`, `EV-ADV-052`)
3. **Tier 3 — High TF-IDF + Exact ≈ 0** (`EV-ADV-076`, `EV-ADV-077`, `EV-ADV-078`, `EV-ADV-079`, `EV-ADV-080`)
4. **Tier 4 — Boundary Cases** (`EV-BND-001`, `EV-BND-016`, `EV-BND-031`, `EV-BND-046`, `EV-BND-061`)
5. **Tier 5 — Semantic Paraphrase** (`EV-SEED-021`, `EV-SEED-022`, `EV-SEED-023`, `EV-SEED-024`, `EV-SEED-025`)
6. **Tier 6 — Random Production / Control** (`EV-SEED-001`, `EV-SEED-011`, `EV-SEED-031`, `EV-SEED-041`, `EV-SEED-005`)

The review interface enforced strict separation:

- System result vs. Human interpretation
- Citation context vs. Citation status
- System assessment vs. Reviewer confidence
- Reviewer notes
- Matching phrase sequence highlight ($\ge 3$ consecutive words)
- Expected synthetic labels hidden from reviewers (Blind Review)

### 7.2 Result

The human review dashboard reported:

- Cases reviewed: **30/30 (100.0%)**
- System assessment `CORRECT`: **30/30 (100.0%)**
- `ACCEPTABLE`: **0**
- `TOO_STRICT`: **0**
- `TOO_LENIENT`: **0**
- `UNCERTAIN`: **0**
- Reported agreement: **100% (30/30)**

### 7.3 Interpretation

All 30 prioritized cases were assessed as `CORRECT` with respect to the frozen system output.

This should be reported as:

> *"Pada 30 kasus human review terprioritas, seluruh kasus dinilai CORRECT oleh reviewer terhadap output AR TILIK v1.0.0, menghasilkan system-assessment agreement 100% (30/30)."*

It should **not** be described as a 100% production accuracy estimate.

---

## 8. Diagnostic Insights from Human Review

The 30-case prioritized review yielded four substantive qualitative findings that serve as evidence-based candidates for future development:

### Finding H-01: High Exact / Citation-Protected Cases
- **Pertanyaan:** Apakah sistem membedakan textual overlap dengan konteks atribusi sah?
- **Observasi:** Kasus-kasus bersitasi sah atau bertanda petik (misal `EV-ADV-028` s/d `030`, `EV-ADV-051`, `052`) menghasilkan skor Exact tinggi. Sistem menetapkan `REVIEW`/`MATCH` berdasarkan teks riil, dan penelaah manusia mengonfirmasi bahwa atribusi akademik telah terpenuhi.
- **Implikasi:** Kandidat prioritas utama untuk modul *Citation / Context Awareness* pada backlog v1.1 tanpa mengubah engine v1.0.0.

### Finding H-02: High TF-IDF + Zero Exact (Lexical Similarity ≠ Textual Copying)
- **Pertanyaan:** Apakah kemiripan leksikal/kosakata tinggi keliru dianggap sebagai penjiplakan langsung?
- **Observasi:** Kasus dengan TF-IDF tinggi namun exact 0% (`EV-ADV-076` s/d `080`) secara konsisten dinilai manusia sebagai `COMMON_ACADEMIC_LANGUAGE`.
- **Implikasi:** Memvalidasi prinsip desain *Dual-Lens Evidence Decomposition* AR Tilik bahwa cosine similarity mencerminkan kedekatan topik/jargon keilmuan dan tidak boleh disajikan sebagai bukti penjiplakan teks sekuensial.

### Finding H-03: Methodology Boilerplate (Academic Fairness Preserved)
- **Pertanyaan:** Apakah terminologi dan formulasi metodologi penelitian standar otomatis dianggap sebagai pelanggaran?
- **Observasi:** Frasa formulaik metodologi (`EV-ADV-005`, misal pengujian normalitas atau teknik kuesioner) memiliki konvergensi leksikal wajar.
- **Implikasi:** Penilaian manusia menegaskan bahwa kriteria ambang sistem harus melindungi formulaik metodologis agar tidak dikriminalisasi secara tidak adil.

### Finding H-04: False Accusation Safety on Legitimate Text
- **Pertanyaan:** Apakah sistem memunculkan tuduhan keliru (*false accusation*) pada teks mandiri yang sah?
- **Observasi:** Pada seluruh 30 kasus uji yang ditelaah, tidak ditemukan satu pun vonis `MATCH` pada teks yang sah.
- **Implikasi:** Menunjukkan kehati-hatian sistem pada skenario batas teruji. Namun secara metodologis ditegaskan bahwa sampel 30 kasus ini bukan pembuktian matematis bahwa *false positive* tidak akan pernah terjadi di seluruh populasi produksi.

---

## 9. Evaluation Layers

The current evidence remains separated into three distinct validation layers:

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                       TIGA LAPISAN VALIDASI AR TILIK                        │
├────────────────────────┬───────────────────────────┬────────────────────────┤
│ LAPISAN A              │ LAPISAN B                 │ LAPISAN C              │
│ Controlled Benchmark   │ Synthetic Robustness      │ Human Contextual Valid │
├────────────────────────┼───────────────────────────┼────────────────────────┤
│ • 50 Kasus Uji Golden  │ • 450 Kasus Terisolasi    │ • 30 Kasus Terkurasi   │
│ • Macro F1 = 1.0000    │ • 100% Pass Rate          │ • 30/30 CORRECT        │
│ • 0 Misklasifikasi     │ • 0 Engine Defect         │ • 6 Dimensi Anotasi    │
├────────────────────────┼───────────────────────────┼────────────────────────┤
│ Menjawab:              │ Menjawab:                 │ Menjawab:              │
│ Apakah engine mengim-  │ Bagaimana ketahanan batas │ Apakah bukti tekstual  │
│ plementasikan aturan   │ dan permutasi mutasi      │ dan keputusan sistem   │
│ klasifikasi terdesain? │ teks terhadap engine?     │ wajar secara akademik? │
└────────────────────────┴───────────────────────────┴────────────────────────┘
```

These three layers answer different questions and should not be merged into a single accuracy figure.

---

## 10. Current Evaluation Freeze

The v1.0.0 baseline is now formally locked under an **Evaluation Freeze**:

| Validation Layer | Result | Status |
|---|---|---|
| Benchmark | 50/50, F1 1.0000 | PASS |
| Synthetic Evaluation | 450/450, 100% | PASS |
| Production Readiness | 6/6 | PASS |
| Regression / E2E | 50/50, F1 1.0000 | PASS |
| Real-World Thesis Pilot | 34,470 words, 6.12 s | COMPLETED |
| Human Review | 30/30 CORRECT | PASS |

**Engine Status: FROZEN.**

No production engine changes should be made solely on the basis of these validation results.

---

## 11. Known Limitations

The evaluation does not establish that AR TILIK can perfectly determine academic misconduct or academic integrity violations.

In particular:

1. Lexical similarity is not equivalent to misconduct.
2. Exact textual overlap may be legitimate when properly quoted or attributed.
3. Methodology and discipline-specific terminology can naturally overlap.
4. Human review was performed on a prioritized 30-case sample, not a representative production population.
5. Synthetic labels are not equivalent to human ground truth.
6. Current evidence decomposition does not by itself constitute full citation/context awareness.

The substantive academic judgment remains with the appropriate human academic authority.

---

## 12. Candidate v1.1 Backlog

The evaluation suggests that future development should focus on interpretation and context rather than immediately modifying v1.0.0 thresholds.

Candidate areas:

### 12.1 Citation / Context Awareness
Potentially distinguish:
- Direct textual overlap
- Properly cited quotation
- Cited paraphrase
- Uncited overlap
- Bibliography / reference overlap
- Methodology boilerplate

### 12.2 Evidence Presentation Separation
Potentially separate source evidence in the UI into:
```
Direct Textual Overlap
└── Exact sequential evidence (n-gram phrase match)

Lexical / Topical Similarity
└── TF-IDF evidence (shared vocabulary & domain terms)
```
This reduces the risk that a cosine-only source is misread as evidence of direct copying.

### 12.3 Human Feedback Loop
Standardize formal reviewer labels:
- `EVIDENCE_RELEVANT`
- `COMMON_PHRASE`
- `PROPERLY_CITED`
- `NEEDS_INVESTIGATION`
- `FALSE_POSITIVE`
- `INSUFFICIENT_EVIDENCE`

### 12.4 Expanded Human Validation
A larger human-reviewed corpus can be planned after this initial 30-case milestone, especially when validating the v1.1 citation discounting algorithms.

---

## 13. Recommended Next Gate

The recommended next milestone is **not another synthetic benchmark expansion**, and **not immediate v1.1 coding**.

The next engineering process:

```
v1.0.0
  │
  ├── Benchmark 50/50       ✅
  ├── Synthetic 450/450     ✅
  ├── Readiness 6/6         ✅
  ├── E2E 50/50             ✅
  ├── Thesis pilot          ✅
  └── Human review 30/30    ✅
          │
          ▼
   EVALUATION FREEZE        🔒 (LOCKED)
          │
          ▼
   Analyze Findings         (H-01 to H-04)
          │
          ▼
   v1.1 BACKLOG             (Evidence-based)
          │
          ▼
   v1.1 SPECIFICATION       (Prior to Implementation)
```

1. Freeze and archive the v1.0.0 evaluation results.
2. Preserve the 30 human-review records as the first post-release human-validation dataset.
3. Analyze recurring human interpretation and citation-context patterns.
4. Convert only confirmed limitations into a structured v1.1 backlog.
5. Keep v1.0.0 production behavior unchanged until a v1.1 specification is approved.

---

## 14. Evaluation Conclusion

AR TILIK v1.0.0 has completed a layered post-release validation cycle covering controlled benchmark testing, synthetic robustness testing, production-readiness testing, end-to-end regression, a real-world thesis pilot, and prioritized human review.

The current evidence supports treating **v1.0.0 as a stable frozen baseline for subsequent evaluation and future v1.1 planning**.

The results demonstrate successful behavior within the tested datasets and review sample. They do not establish universal production accuracy or replace human academic judgment.
