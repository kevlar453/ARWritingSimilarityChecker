# AR Tilik v1.0.0 — Architecture & Baseline Freeze Specification

**Dokumen Resmi Pembekuan (Freeze v1.0)**  
**Tanggal Pembekuan**: 03 Oktober 2026  
**Status**: **FROZEN / LOCKED**  
**Sistem**: AR Tilik (*Academic Writing Similarity & Evidence Checker*)  
**Lokasi Basis Kode**: `/var/www/html/ARWritingSimilarityChecker`  

---

## 1. Filosofi & Prinsip Keadilan Akademik

AR Tilik dirancang sebagai instrumen telaah kemiripan teks akademis yang menjunjung tinggi keadilan, transparansi, dan objektivitas ilmiah.

1. **Evidence-Based, Not Punitive**:
   - Sistem mendeteksi dan mengukur **bukti tekstual** (*textual evidence*) dan **signifikansi terminologi** (*terminological significance*), bukan memvonis niat penulis.
   - **0 Toleransi Istilah Menghakimi**: Seluruh antarmuka, laporan, log, dan basis kode **dilarang keras** menggunakan kata *"plagiat"* atau *"plagiasi"*. Istilah yang diakui adalah *"kesamaan tekstual"*, *"orientasi kosakata"*, *"pasase sekuensial"*, dan *"frasa umum akademik"*.
2. **Konteks Akademik adalah Kunci**:
   - Persentase kesamaan tinggi $\neq$ pelanggaran integritas akademik. Kesamaan pada kutipan langsung, definisi umum, rumus, dan metodologi standar dianalisis secara proporsional agar tidak membebani dosen maupun mahasiswa.

---

## 2. Arsitektur Dual-Lens & Evidence Layer (Frozen)

Sistem memadukan dua mesin komplementer yang bekerja secara independen sebelum disintesis oleh Evidence Layer:

```
                          ┌───────────────────────────┐
                          │   Submisi Karya Ilmiah    │
                          └─────────────┬─────────────┘
                                        │
                    ┌───────────────────┴───────────────────┐
                    ▼                                       ▼
       ┌─────────────────────────┐             ┌─────────────────────────┐
       │   EXACT MATCHER LENS    │             │   TF-IDF VECTORIZER     │
       │ (5-Gram Sequential Pass)│             │  (Sparse Cosine Space)  │
       └────────────┬────────────┘             └────────────┬────────────┘
                    │                                       │
                    │ Raw Exact Evidence                    │ Raw Lexical Evidence
                    ▼                                       ▼
       ┌────────────────────────────────────────────────────────┐
       │           HYBRID EVIDENCE DECOMPOSITION LAYER          │
       │  - Evidence Contract Normalization                     │
       │  - Explicit Rule-Based Aggregation                     │
       │  - Pattern Synthesis & Source Agreement                │
       │  - Transparent Explainability Rationale Generation     │
       └────────────────────────────┬───────────────────────────┘
                                    │
                                    ▼
       ┌────────────────────────────────────────────────────────┐
       │            WEB DASHBOARD & EVIDENCE REPORT             │
       │  [Decision] [Pattern] [Exact vs TF-IDF] [Rationale]    │
       │  [Interactive Side-by-Side Passage Modal Comparison]   │
       └────────────────────────────────────────────────────────┘
```

### A. Lensa 1: Exact Matcher (`worker/matcher.py`)
- **Unit Analisis**: *Word 5-Gram Shingling* yang di-hash dengan FNV-1a.
- **Cakupan Union**: Menghitung cakupan kata tanpa *double-counting* dari banyak sumber.
- **Metrik Utama**:
  - `overall_similarity` (% cakupan kata terhadap total kata submisi).
  - `matched_words` (jumlah kata yang cocok).
  - `longest_match_words` (panjang kata berturut-turut terpanjang).
  - `max_idf` & `average_idf` (bobot signifikansi shingle).

### B. Lensa 2: TF-IDF Vectorizer (`worker/tfidf.py`)
- **Unit Analisis**: *Document Frequency (DF)* seluruh korpus dan *Cosine Similarity* vektor jarang (*sparse vector*).
- **Karakteristik**: Kebal terhadap sisipan sitasi di tengah kalimat (*citation noise*) dan variasi format penulisan.
- **Metrik Utama**:
  - `cosine_similarity` (% kesamaan arah vektor leksikal).
  - `shared_terms_count` (jumlah kosakata unik yang beririsan).
  - `average_shared_term_idf` (rata-rata signifikansi kosakata bersama).
  - `top_shared_terms` (daftar istilah teratas yang berkontribusi pada dot product).

### C. Hybrid Evidence Layer (`worker/hybrid.py`)
Aturan sintesis baku (*aggregation rules*) mengkategorisasikan bukti ke dalam 5 pola transparan:

| Pola Bukti (*Evidence Pattern*) | Syarat Logis Agregasi | Keputusan (*Decision*) | Kesepakatan (*Agreement*) |
|---|---|:---:|:---:|
| **`VERBATIM_MATCH`** | $(Sim_{exact} \ge 70\% \lor Longest \ge 50) \land Sim_{tfidf} \ge 85\%$ | **MATCH** | STRONG_AGREEMENT |
| **`COPY_EDIT`** | $(Sim_{exact} \ge 70\% \lor Longest \ge 10) \land (10\% \le Sim_{tfidf} < 85\%)$ | **REVIEW** | PARTIAL_AGREEMENT |
| **`COMMON_ACADEMIC_PHRASE`** | $(Sim_{exact} \ge 10\% \land Max\ IDF \le 1.0) \lor (Sim_{tfidf} \ge 10\% \land Avg\ IDF < 0.5)$ | **REVIEW** | SIGNIFICANCE_DIVERGENCE |
| **`SEMANTIC_PARAPHRASE`** | $Sim_{exact} < 10\% \land Sim_{tfidf} \ge 10\% \land Avg\ IDF \ge 0.5$ | **REVIEW** | COMPLEMENTARY_EVIDENCE |
| **`PARTIAL_DIRECT_MATCH`** | $(Sim_{exact} \ge 10\% \lor Longest \ge 10) \land Max\ IDF > 1.0$ | **REVIEW** | LOCALIZED_EXACT_EVIDENCE |
| **`NO_EVIDENCE`** | Tidak memenuhi kriteria di atas | **NO_MATCH** | NO_EVIDENCE |

---

## 3. Ambang Batas Resmi (Frozen Thresholds)

Ambang batas berikut didefinisikan pada [`benchmark/baseline_hybrid_v1.json`](benchmark/baseline_hybrid_v1.json) dan [`worker/hybrid.py`](worker/hybrid.py), dan **tidak boleh diubah tanpa benchmark regresi formal**:

```python
DEFAULT_EXACT_MATCH_SIMILARITY = 70.0     # Ambang batas vonis exact match
DEFAULT_EXACT_REVIEW_SIMILARITY = 10.0    # Ambang batas telaah exact review
DEFAULT_TFIDF_MATCH_SIMILARITY = 85.0     # Ambang batas leksikal penuh match
DEFAULT_TFIDF_REVIEW_SIMILARITY = 10.0    # Ambang batas leksikal telaah review
DEFAULT_EXACT_MATCH_LONGEST = 50          # Panjang pasase mutlak verbatim (kata)
DEFAULT_EXACT_REVIEW_LONGEST = 10         # Panjang pasase signifikan copy-edit (kata)
DEFAULT_SIGNIFICANT_MIN_IDF = 0.5         # Bobot ambang batas keunikan kata
SHINGLE_SIZE = 5                          # Ukuran n-gram shingle dasar (kata)
```

---

## 4. Sertifikasi Baseline Benchmark (Run #21)

Hasil evaluasi 50 kasus uji standar benchmark yang dibekukan:

- **Total Kasus Uji**: 50 kasus
  - 10 Identical (Verbatim)
  - 10 Copy-Edit (Intervensi kata)
  - 10 Paraphrase (Parafrasa topikal)
  - 10 Common Academic Phrase (Frasa standar ilmiah)
  - 10 Unrelated (Naskah tanpa korelasi)
- **Metrik Evaluasi**:
  - **Macro F1 Score**: `1.0000` (100.0%)
  - **Binary F1 Score**: `1.0000` (100.0%)
  - **Binary Precision**: `1.0000` (100.0%)
  - **Binary Recall**: `1.0000` (100.0%)
- **Matriks Konfusi Resmi**:
  ```
  EXPECTED \ PRED      MATCH      REVIEW    NO_MATCH
  --------------------------------------------------
  match                   10           0           0
  review                   0          30           0
  no_match                 0           0          10
  --------------------------------------------------
  Total Salah Klasifikasi: 0
  ```

---

## 5. Spesifikasi Tampilan UI (Evidence Decomposition)

Antarmuka laporan pada [`app/Views/dashboard/report.php`](app/Views/dashboard/report.php) membagi presentasi bukti menjadi 4 tier terstruktur:

1. **Tier 1 (Status Sintesis)**:
   - Keputusan objektif (`MATCH`, `REVIEW`, `NO MATCH`).
   - Pola bukti dengan deskripsi informatif.
   - Status kesepakatan antar-lensa (*Agreement*).
2. **Tier 2 (Side-by-Side Dual-Lens)**:
   - *Kolom Kiri*: EXACT EVIDENCE (Similarity %, Matched words, Longest passage, Max IDF).
   - *Kolom Kanan*: TF-IDF EVIDENCE (Cosine similarity %, Shared terms, Average IDF, Detected sources, Top Shared Vocabulary pills).
3. **Tier 3 (Explainability Rationale)**:
   - Kartu *"WHY THIS RESULT?"* berisi narasi transparan alasan ilmiah di balik putusan.
4. **Tier 4 (Source Evidence & Passage Modal)**:
   - Tabel komparasi per sumber korpus.
   - Tombol interaktif `[View passage]` membuka modal dialog yang membandingkan teks submisi vs teks sumber berdampingan secara presisi (*exact snippet context*).
5. **Academic Fairness Principle Banner**:
   - Banner edukasi kepatuhan integritas akademik.

---

## 6. Prosedur Perlindungan Regresi & Quality Gates Menuju Fase 3

Sebelum setiap pengembangan pada **Fase 3** (seperti *Citation Parser*, *Semantic Vector Embeddings*, atau *Multi-Corpus Isolation*), sistem wajib lolos 3 gerbang (*Quality Gates*) secara berurutan:

```bash
# Gerbang 1: Uji Unit Python
./artilik test
# Syarat: 28/28 Unit Tests PASSED (0 error, 0 fail)

# Gerbang 2: Uji Ketahanan Produksi & Edge Cases
./artilik readiness
# Syarat: 6/6 Kasus Anomali PASSED (0-byte, mikro, 3.000 kata, unicode, duplikat, view)

# Gerbang 3: Uji Regresi Pipeline End-to-End
./artilik regression --cleanup
# Syarat: 50/50 Kasus Uji PASSED melalui API -> Worker -> DB -> HTML (Macro F1 = 1.0000)
```

---

## 7. Batasan & Integritas Klaim Ilmiah (Scientific Claim Boundaries)

Dokumentasi dan publikasi resmi AR Tilik wajib mematuhi batasan klaim ilmiah terukur berikut:

1. **Klaim yang Sah dan Terbukti (Empirically Validated)**:
   - AR Tilik terbukti berhasil melewati benchmark terstandarisasi 50 kasus uji dan pengujian *End-to-End Pipeline Regression* dengan **0 kesalahan klasifikasi** (Macro F1 = `1.0000`, Binary F1 = `1.0000`).
   - Seluruh 28 pengujian unit terotomasi dan 6 pengujian ketahanan produksi (*edge cases*: dokumen kosong, mikro-teks, naskah panjang 3.000 kata, Unicode/rumus matematika, duplikasi, dan integritas rendering view) lulus dengan status 100% *clean*.
   - Rantai penelusuran bukti (*traceability*) dari `Decision` $\to$ `Pattern` $\to$ `Exact Evidence` $\to$ `TF-IDF Evidence` $\to$ `Source Evidence` $\to$ `Passage Detail Modal` terhubung utuh dan transparan.

2. **Klaim yang Dilarang (Overclaiming / Unsupported Generalization)**:
   - **Dilarang keras mengklaim**: *"Sistem sudah terbukti akurat untuk semua jenis tulisan akademik di dunia nyata."*
   - Dataset benchmark adalah instrumen kalibrasi dan pengujian terstandarisasi yang merepresentasikan tipologi umum (verbatim, copy-edit, parafrasa, frasa klise ilmiah, dan naskah acak berlainan topik), bukan jaminan generalisasi universal terhadap seluruh variasi bahasa manusia di dunia nyata.
   - Posisi dokumentasi ilmiah yang jujur, terukur, dan berintegritas ini memperkuat legitimasi ilmiah dan kredibilitas profesional proyek AR Tilik.

---
*AR Tilik Core Architecture v1.0.0 — Frozen, Certified, and Release Candidate Ready.*
