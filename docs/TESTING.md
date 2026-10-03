# Laporan Pengujian Mutu Sistem AR Tilik (v1.0.0-RC)

Dokumen ini mendokumentasikan log eksekusi pengujian aktual, konfigurasi lingkungan, versi commit, ambang batas, dan hasil verifikasi regresi penuh pada **AR Tilik v1.0.0-RC1**.

---

## 1. Metadata Eksekusi Uji Resmi

| Parameter | Nilai / Konfigurasi Aktual |
|---|---|
| **Tanggal Pengujian** | 03 Oktober 2026, 14:45 WIB |
| **Versi Rilis** | `v1.0.0-rc1` |
| **Git Commit Hash** | `b692f168af1ac9efb72cfc88f4cc4465c8e00b0f` |
| **Branch** | `main` |
| **Sistem Operasi** | Linux (Ubuntu/Debian-based x86_64) |
| **Versi Python** | Python 3.14.7 (.venv) |
| **Versi PHP** | PHP 8.3 / CodeIgniter 4.6.0 |
| **Versi Database** | MariaDB 10.11+ / MySQLi |
| **Port Server HTTP** | `http://localhost:8085` |

---

## 2. Parameter & Ambang Batas Terkalibrasi (Frozen Config)

```python
# benchmark/config.py & worker/hybrid.py
SHINGLE_SIZE = 5                          # Ukuran 5-gram shingle dasar
BENCHMARK_CORPUS_SIZE = 20                # Jumlah korpus sumber kalibrasi

# Exact Matcher Thresholds
DEFAULT_EXACT_MATCH_SIMILARITY = 70.0     # Ambang batas vonis exact match (%)
DEFAULT_EXACT_REVIEW_SIMILARITY = 10.0    # Ambang batas telaah exact review (%)
DEFAULT_EXACT_MATCH_LONGEST = 50          # Pasase mutlak verbatim (kata)
DEFAULT_EXACT_REVIEW_LONGEST = 10         # Pasase signifikan copy-edit (kata)

# TF-IDF Cosine Vectorizer Thresholds
DEFAULT_TFIDF_MATCH_SIMILARITY = 85.0     # Ambang batas vonis kosinus leksikal (%)
DEFAULT_TFIDF_REVIEW_SIMILARITY = 10.0    # Ambang batas telaah kosinus (%)
DEFAULT_SIGNIFICANT_MIN_IDF = 0.5         # Ambang batas signifikansi keunikan kata
```

---

## 3. Hasil Pengujian Unit Terotomasi (Pytest Suite)

**Perintah**: `./artilik test`  
**Durasi**: 12.73 detik  
**Status**: **28 / 28 PASSED (100% Lulus, 0 Fail, 0 Error)**

```
tests/test_hybrid.py::test_extract_exact_evidence PASSED                 [  3%]
tests/test_hybrid.py::test_extract_tfidf_evidence PASSED                 [  7%]
tests/test_hybrid.py::test_aggregate_verbatim_match PASSED               [ 10%]
tests/test_hybrid.py::test_aggregate_copy_edit PASSED                    [ 14%]
tests/test_hybrid.py::test_aggregate_paraphrase PASSED                   [ 17%]
tests/test_hybrid.py::test_aggregate_common_phrase PASSED                [ 21%]
tests/test_hybrid.py::test_aggregate_unrelated PASSED                    [ 25%]
tests/test_hybrid.py::test_frozen_baseline_contract PASSED               [ 28%]
tests/test_idf.py::test_common_phrase PASSED                             [ 32%]
tests/test_idf.py::test_rare_phrase PASSED                               [ 35%]
tests/test_idf.py::test_frequency_ratio PASSED                           [ 39%]
tests/test_idf.py::test_commonness PASSED                                [ 42%]
tests/test_idf.py::test_evaluate_segment PASSED                          [ 46%]
tests/test_matcher.py::test_segment_requires_both_positions PASSED       [ 50%]
tests/test_matcher.py::test_different_sources_not_grouped PASSED         [ 53%]
tests/test_matcher.py::test_duplicate_pairs_removed PASSED               [ 57%]
tests/test_production_readiness.py::test_production_readiness PASSED     [ 60%]
tests/test_similarity.py::test_merge_ranges PASSED                       [ 64%]
tests/test_similarity.py::test_no_double_count PASSED                    [ 67%]
tests/test_similarity.py::test_overlapping_sources_do_not_double_count PASSED [ 71%]
tests/test_similarity.py::test_empty_matches PASSED                      [ 75%]
tests/test_similarity.py::test_longest_segment PASSED                    [ 78%]
tests/test_stress.py::test_generate_stress_dataset PASSED                [ 82%]
tests/test_stress.py::test_needle_in_haystack_hybrid_detection PASSED    [ 85%]
tests/test_stress.py::test_citation_noise_tfidf_recovery PASSED          [ 89%]
tests/test_tfidf.py::test_cosine_similarity_identical PASSED             [ 92%]
tests/test_tfidf.py::test_cosine_similarity_orthogonal PASSED            [ 96%]
tests/test_tfidf.py::test_compute_tf_and_vector PASSED                   [100%]
============================= 28 passed in 12.73s ==============================
```

---

## 4. Hasil Pengujian Ketahanan Produksi (Production Readiness)

**Perintah**: `./artilik readiness`  
**Status**: **6 / 6 Kasus Anomali LULUS (100% Safe)**

```
======================================================================
       AR TILIK - PRODUCTION READINESS & EDGE CASES TEST
======================================================================

[TEST 1] File kosong (0-byte)                -> [✓] Status: completed, Sim: 0.00%
[TEST 2] Mikro-teks (2 kata)                 -> [✓] Status: completed, Decision: no_match, Sim: 0.00%
[TEST 3] Teks panjang (3.000 kata)           -> [✓] Status: completed tanpa timeout
[TEST 4] Unicode, Math, Emoji, Petik Keriting -> [✓] Status: completed, Decision: review
[TEST 5] Submisi duplikat berturut-turut     -> [✓] Status: completed (Idempotent)
[TEST 6] Rendering HTML Dashboard Report     -> [✓] 100% Elemen ter-render utuh
======================================================================
 [✓] SEMUA PENGUJIAN PRODUCTION READINESS LULUS DENGAN AMAN!
======================================================================
```

---

## 5. Hasil Pengujian Regresi Pipeline End-to-End (E2E)

**Aliran Penuh**:
$$\text{Dataset Uji} \xrightarrow{\text{HTTP POST}} \text{API Upload} \xrightarrow{\text{Antrean Jobs}} \text{Worker Daemon} \xrightarrow{\text{SQL}} \text{DB Laporan} \xrightarrow{\text{HTTP GET}} \text{HTML View}$$

**Perintah**: `./artilik regression --cleanup`  
**Target**: 50 Kasus Uji Benchmark Standar  
**Status**: **50 / 50 PASSED (100% Keselarasan terhadap Baseline Run #21)**

### Ringkasan Metrik Aktual:
- **Total Kasus Uji**: 50
- **Binary Precision**: `1.0000`
- **Binary Recall**: `1.0000`
- **Binary F1 Score**: `1.0000`
- **Macro F1 Score**: `1.0000`
- **Total Kesalahan Klasifikasi**: `0`
- **Pembersihan Basis Data**: 50 dokumen uji dibersihkan secara bersih (*idempotent*).

### Matriks Konfusi Aktual (Confusion Matrix):
```
EXPECTED \ PREDICTED       MATCH      REVIEW    NO_MATCH
--------------------------------------------------------
match                         10           0           0
review                         0          30           0
no_match                       0           0          10
--------------------------------------------------------
Total Kesalahan Klasifikasi:   0
```

### Rekap Kasus Uji Per Kategori:
1. `TC-001 s.d. TC-010` (*identical*): Pred = **MATCH** | Exact = 100.0% | Cosine = 87.3% | Pattern = `VERBATIM_MATCH`
2. `TC-011 s.d. TC-020` (*copy_edit*): Pred = **REVIEW** | Exact = 81.2% | Cosine = 81.2% | Pattern = `COPY_EDIT`
3. `TC-021 s.d. TC-030` (*paraphrase*): Pred = **REVIEW** | Exact = 0.0% | Cosine = 41.3% | Pattern = `SEMANTIC_PARAPHRASE`
4. `TC-031 s.d. TC-040` (*common_phrase*): Pred = **REVIEW** | Exact = 52.4% | Cosine = 35.9% | Pattern = `COMMON_ACADEMIC_PHRASE`
5. `TC-041 s.d. TC-050` (*unrelated*): Pred = **NO_MATCH** | Exact = 0.0% | Cosine = 5.7% | Pattern = `NO_EVIDENCE`

---

## 6. Prosedur Golden Test untuk Pemeliharaan Berkelanjutan

Setiap calon kontributor atau developer yang mengubah kode di masa mendatang wajib menjalankan:
```bash
./artilik test && ./artilik readiness && ./artilik regression --cleanup
```
Jika hasil akhir tidak menghasilkan Macro F1 = 1.0000 dengan 0 kesalahan klasifikasi, perubahan tersebut dinyatakan melanggar kontrak mutu v1.0 dan **wajib dibatalkan**.
