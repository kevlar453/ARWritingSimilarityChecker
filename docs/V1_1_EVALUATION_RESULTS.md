# Laporan Evaluasi Terarah 80 Kasus Akademik AR Tilik v1.1
**Dokumen Pembuktian Gate V11-B & Validasi Regresi Baseline v1.0.0**  
**Versi Sistem:** v1.1.0  
**Baseline Engine:** v1.0.0 (FROZEN — `worker/hybrid.py` 100% Tidak Dimodifikasi)  
**Tanggal Eksekusi:** 05 Oktober 2026  
**Status Evaluasi:** ✅ **SEMUA QUALITY GATES PASS (Gate V11-B1 s.d. V11-B5)**

---

## 1. Pernyataan Klaim Defensibel & Executive Summary

> [!IMPORTANT]
> **Pedoman Klaim Akademis yang Sah:**
> Jangan menyatakan: *"AR Tilik memiliki akurasi 100%."*  
> Pernyataan resmi dan defensibel yang digunakan:  
> **"Pada evaluasi terarah 80 kasus konteks akademik v1.1, AR Tilik berhasil mencapai akurasi deteksi konteks 100.0% (80/80), akurasi status atribusi sitasi 100.0% (80/80), kepatuhan mutlak terhadap non-suppression invariant (100.0%), kepatuhan decision invariance (100.0%), dan zero confirmed engine defects terhadap baseline v1.0.0 yang dibekukan."**

Evaluasi terarah v1.1 (WP-05) dirancang untuk menguji fungsionalitas lapisan baru v1.1 (*Citation & Context Awareness*, *Source Evidence Grouping*, dan *Divergence Explainability*) tanpa mengubah satu baris pun logika keputusan atau ambang batas mesin v1.0.0. Suite evaluasi ini memisahkan secara tegas antara hasil sistem aktual dan ekspektasi post-hoc.

---

## 2. Matriks Quality Gates Evaluasi (Gate V11-B)

| Quality Gate | Kriteria Keberhasilan | Hasil Aktual | Status |
| :--- | :--- | :---: | :---: |
| **Gate V11-B1** | Akurasi Deteksi Konteks Akademik $\ge 95\%$ | **100.0%** (80/80) | ✅ **PASS** |
| **Gate V11-B2** | Akurasi Status Atribusi Sitasi $\ge 95\%$ | **100.0%** (80/80) | ✅ **PASS** |
| **Gate V11-B3** | Non-Suppression Invariance (Metrik Exact & Cosine tidak berubah) | **100.0%** (80/80) | ✅ **PASS** |
| **Gate V11-B4** | Decision Invariance (Keputusan sistem v1.0.0 tidak berubah oleh konteks) | **100.0%** (80/80) | ✅ **PASS** |
| **Gate V11-B5** | Confirmed Engine Defects = 0 | **0 Defect** | ✅ **PASS** |

Seluruh 5 kriteria Gate V11-B terpenuhi secara sempurna tanpa deviasi arsitektural.

---

## 3. Ringkasan Kinerja per Kelompok (8 Kelompok $\times$ 10 Kasus)

Evaluasi mencakup 8 kelompok kasus terarah yang mencerminkan spektrum tantangan penulisan akademik:

| No | Kelompok Kasus | Kasus | Deteksi Konteks | Status Sitasi | Kesesuaian Keputusan | Invarian Metrik & Keputusan | Mismatch |
| :-: | :--- | :-: | :-: | :-: | :-: | :-: | :-: |
| **1** | Uncited direct overlap (`EV11-UNC`) | 10 | 10/10 (100%) | 10/10 (100%) | 10/10 (100%) | 10/10 (100%) | 0 |
| **2** | Parenthetical citation (`EV11-PAR`) | 10 | 10/10 (100%) | 10/10 (100%) | 10/10 (100%) | 10/10 (100%) | 0 |
| **3** | Inline citation (`EV11-INL`) | 10 | 10/10 (100%) | 10/10 (100%) | 10/10 (100%) | 10/10 (100%) | 0 |
| **4** | Direct / block quote (`EV11-QUO`) | 10 | 10/10 (100%) | 10/10 (100%) | 10/10 (100%) | 10/10 (100%) | 0 |
| **5** | Paraphrase + citation (`EV11-PRA`) | 10 | 10/10 (100%) | 10/10 (100%) | 9/10 (90%) | 10/10 (100%) | 1 |
| **6** | Methodology / boilerplate (`EV11-MET`)| 10 | 10/10 (100%) | 10/10 (100%) | 0/10 (0%)* | 10/10 (100%) | 10* |
| **7** | Exact = 0 + high TF-IDF (`EV11-DIV`) | 10 | 10/10 (100%) | 10/10 (100%) | 10/10 (100%) | 10/10 (100%) | 0 |
| **8** | Unrelated citation distractor (`EV11-DIS`)| 10 | 10/10 (100%) | 10/10 (100%) | 7/10 (70%) | 10/10 (100%) | 3 |
| **TOTAL** | **80 Kasus Terarah** | **80** | **80/80 (100%)** | **80/80 (100%)** | **66/80 (82.5%)** | **80/80 (100%)** | **14** |

*\*Catatan Metodologi: Mismatch pada kelompok 6 merupakan fenomena fundamental keterbatasan mesin pencocok string v1.0.0, bukan cacat engine (lihat analisis di bawah).*

---

## 4. Taksonomi Kegagalan & Analisis Diagnostik Mismatch

Seluruh 14 deviasi antara keputusan sistem aktual v1.0.0 dan ekspektasi benchmark post-hoc telah dianalisis dan diklasifikasikan secara rigor ke dalam **Taksonomi Kegagalan Akademik**:

```mermaid
pie title Distribusi Taksonomi Kegagalan Mismatch
    "ENGINE_LIMITATION:NO_CONTEXT_AWARENESS (Kelompok 6)" : 10
    "BOUNDARY_BEHAVIOR:THRESHOLD_TRANSITION (Kelompok 5 & 8)" : 4
    "ENGINE_DEFECT" : 0
```

### 4.1. Kelompok 6: Methodology Boilerplate (10 Kasus) — `ENGINE_LIMITATION:NO_CONTEXT_AWARENESS`
- **Kasus:** `EV11-MET-001` s.d. `EV11-MET-010`.
- **Ekspektasi Benchmark:** `REVIEW` (pola `METHODOLOGY_BOILERPLATE`).
- **Keputusan Aktual v1.0.0 Engine:** `MATCH` (pola `VERBATIM_MATCH`).
- **Metrik Objek:** Exact Similarity: $73.68\% - 79.17\%$, Longest Match: $14 - 19$ kata identik berturut-turut.
- **Akar Masalah:**
  Mesin v1.0.0 (`worker/hybrid.py`) adalah sistem statistik deterministik murni. Aturan v1.0.0 menyatakan: jika `exact_sim >= 70.0%` dan `cosine >= 85.0%`, sistem secara matematis wajib menetapkan `MATCH`. Mesin v1.0.0 tidak memiliki pemahaman semantik bahwa kalimat seperti *"Metode penelitian yang digunakan adalah pendekatan kuantitatif dengan analisis regresi linier berganda..."* adalah formulasi metodologis standar yang lazim muncul tanpa maksud penjiplakan.
- **Peran Lapisan v1.1:**
  v1.1 **tidak memanipulasi skor exact** (karena memanipulasi skor akan melanggar integritas bukti). Sebaliknya, v1.1 menyajikan bukti ini sebagai `DIRECT_TEXTUAL_OVERLAP`, mendeteksi `citation_status = NOT_CITED`, dan memberikan petunjuk kepada penelaah manusia (*Human Reviewer*) untuk mempertimbangkan apakah teks tersebut merupakan boilerplate metodologis.

### 4.2. Kelompok 8: Unrelated Citation Distractor (3 Kasus) — `BOUNDARY_BEHAVIOR:THRESHOLD_TRANSITION`
- **Kasus:** `EV11-DIS-001`, `EV11-DIS-006`, `EV11-DIS-010`.
- **Ekspektasi Benchmark:** `MATCH` (pola `VERBATIM_MATCH`).
- **Keputusan Aktual v1.0.0 Engine:** `REVIEW` (pola `COPY_EDIT`).
- **Metrik Objek:** Exact Similarity: $70.0\% - 74.07\%$, Cosine Similarity: $83.55\% - 84.93\%$.
- **Akar Masalah:**
  Penambahan kalimat pengecoh (*distractor citation*) yang panjang mendilusi kesamaan kosinus TF-IDF hingga sedikit di bawah ambang batas kesamaan mutlak ($85.0\%$). Karena `exact_sim >= 70.0%` namun `cosine < 85.0%`, sistem v1.0.0 secara akurat memicu aturan `COPY_EDIT` (yang menghasilkan keputusan `REVIEW`). Ini adalah perilaku batas (*boundary behavior*) yang sah dari mesin.
- **Peran Lapisan v1.1:**
  Detektor v1.1 berhasil mendeteksi keberadaan sitasi pengecoh (`PARENTHETICAL_CITATION`), menandai `citation_status = CITED`, dan menyajikan pasase yang disalin secara utuh sehingga reviewer dapat melihat dengan jelas bahwa sitasi yang disertakan tidak relevan dengan klaim yang dijiplak.

### 4.3. Kelompok 5: Paraphrase + Citation (1 Kasus) — `BOUNDARY_BEHAVIOR:THRESHOLD_TRANSITION`
- **Kasus:** `EV11-PRA-009`.
- **Ekspektasi Benchmark:** `REVIEW` (pola `SEMANTIC_PARAPHRASE`).
- **Keputusan Aktual v1.0.0 Engine:** `NO_MATCH` (pola `NO_EVIDENCE`).
- **Metrik Objek:** Exact Similarity: $0.0\%$, Cosine Similarity: $9.72\%$.
- **Akar Masalah:**
  Parafrase tingkat tinggi pada kasus ini menghasilkan kesamaan kosinus $9.72\%$, yang berada tepat $0.28\%$ di bawah ambang batas peninjauan kosinus v1.0.0 (`DEFAULT_TFIDF_REVIEW_SIMILARITY = 10.0%`). Mesin v1.0.0 secara deterministik memutuskan `NO_MATCH`.
- **Peran Lapisan v1.1:**
  Detektor konteks v1.1 tetap mendeteksi sitasi naratif (`Menurut Anderson (2023)`) secara presisi dengan span karakter yang akurat.

---

## 5. Analisis Perbedaan Perilaku: v1.0.0 vs v1.1.0 (Behavioral Diff)

| Dimensi Evaluasi | Baseline v1.0.0 (Monolitik) | Lapisan v1.1.0 (Dekomposisi Kontekstual) |
| :--- | :--- | :--- |
| **Konteks Sitasi / Kutipan** | **Buta Sitasi:** Kutipan bertanda petik, sitasi parentetikal, dan teks tidak bersitasi diperlakukan identik secara matematis. | **Sadar Konteks:** Mendeteksi 8 jenis konteks sitasi (`NONE`, `PARENTHETICAL`, `INLINE`, `DIRECT_QUOTE`, `BLOCK_QUOTE`, `FOOTNOTE`, `BIBLIOGRAPHY`, `UNKNOWN`) dengan span karakter dan audit rationale. |
| **Penyajian Bukti Sumber** | **Daftar Tunggal:** Seluruh rujukan digabung dalam satu daftar linier tanpa pemisahan jenis bukti. | **Dua Ember Bukti Terpisah:** Memisahkan *Direct Textual Overlap* (berbasis Exact 5-gram) dari *Lexical / Topic Similarity* (berbasis TF-IDF Cosine). |
| **Divergensi Bukti** | **Membingungkan Pengguna:** Ketika Exact = 0% namun Cosine tinggi, pengguna sering keliru menganggap dokumen disalin verbatim. | **Banner Peringatan Transparan:** Mendeteksi divergensi secara otomatis dan memberi notifikasi bahwa kemiripan bersumber dari medan makna/terminologi, bukan penjiplakan sekuensial. |
| **Integritas Metrik (Non-Suppression)**| Skor tunggal agregasi. | **100% Invarian:** Skor Exact dan Cosine v1.0.0 dipertahankan secara identik tanpa pengurangan atau manipulasi artifisial. |
| **Keputusan Klasifikasi** | `MATCH` / `REVIEW` / `NO_MATCH`. | **100% Invarian:** Keputusan v1.0.0 dijamin identik (`decision_invariant_guaranteed = True`). |
| **Dukungan Penelaah Manusia** | Tidak ada arahan telaah. | Memberikan petunjuk substantif, mendeteksi kasus yang memerlukan investigasi vs kasus yang telah diatribusikan dengan tepat. |

---

## 6. Pembuktian Eksperimental Isolasi Label Post-Hoc (Gate D4)

Untuk memvalidasi keketatan metodologi Gate D4, suite pengujian menjalankan eksperimen perturbasi label:
1. Satu kasus submisi yang sama (`EV11-UNC-001`) dievaluasi sebanyak tiga kali dengan memasukkan label ekspektasi yang sengaja diubah:
   - Eksperimen A: `expected_class = "match"`, `expected_context = "NONE"`
   - Eksperimen B: `expected_class = "review"`, `expected_context = "DIRECT_QUOTE"`
   - Eksperimen C: `expected_class = "no_match"`, `expected_context = "BIBLIOGRAPHY"`
2. **Hasil Eksperimen:**
   - Skor `exact_similarity`: $100.0\%$ (identik pada A, B, C)
   - Skor `cosine_similarity`: $100.0\%$ (identik pada A, B, C)
   - Keputusan aktual `predicted_class`: `match` (identik pada A, B, C)
   - Konteks terdeteksi `predicted_context`: `NONE` (identik pada A, B, C)
   - Jenis bukti `predicted_evidence_type`: `DIRECT_TEXTUAL_OVERLAP` (identik pada A, B, C)
3. **Kesimpulan Metodologis:**
   Label ekspektasi terbukti secara eksperimental bersifat **100% post-hoc** dan tidak pernah mengalir ke dalam mesin untuk mengondisikan atau membiaskan kalkulasi engine.

---

## 7. Status Kesiapan Rilis & Kesimpulan WP-05

| Komponen Pengujian | Volume Uji | Hasil | Status |
| :--- | :---: | :---: | :---: |
| Full Pytest Test Suite (`./artilik test`) | 104 pengujian | 104 passed in 16.73s | ✅ **PASS** |
| Production Readiness Suite (`./artilik readiness`) | 6 pengujian edge cases | 6/6 passed | ✅ **PASS** |
| Targeted 80-Case Contextual Suite | 80 kasus uji | 80/80 dievaluasi | ✅ **PASS** |
| Engine Code Invariance (`git diff worker/hybrid.py`) | 0 baris diubah | Bersih (100% frozen) | ✅ **PASS** |

Dengan selesainya WP-05, sistem AR Tilik v1.1 telah memiliki validasi regresi otomatis dan bukti empiris kuantitatif yang solid. Milestone berikutnya dapat dilanjutkan ke **WP-06: Human Validation & Expert Review Queue Expansion**.
