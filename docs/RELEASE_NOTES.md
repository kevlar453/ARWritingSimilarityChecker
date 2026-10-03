# Release Notes — AR Tilik v1.0.0-RC1

**Tanggal Rilis**: 03 Oktober 2026  
**Status**: Release Candidate 1 (`v1.0.0-rc1`)  
**Commit**: `b692f16`  
**Repositori**: [https://github.com/kevlar453/ARWritingSimilarityChecker](https://github.com/kevlar453/ARWritingSimilarityChecker)

---

## 🎯 Ringkasan Eksekutif

AR Tilik (*Academic Writing Similarity & Evidence Checker*) v1.0.0-RC1 adalah rilis kandidat produksi pertama yang mengimplementasikan arsitektur **Dual-Lens Textual Evidence Decomposition**:
1. **Lensa Sekuensial (Exact Matcher)**: Mengukur kesamaan kata berturut-turut (*word 5-gram shingling*) dan panjang pasase terpanjang tanpa *double counting*.
2. **Lensa Leksikal (TF-IDF Vectorizer)**: Mengukur kesamaan orientasi kosakata (*sparse cosine similarity*) dan bobot signifikansi istilah (*IDF*) di seluruh korpus institusi.
3. **Evidence Decomposition Layer**: Menggabungkan bukti dari kedua lensa secara transparan ke dalam 5 pola bukti (*evidence patterns*) objektif disertai penjelasan alasan (*explainability rationale*) di bawah prinsip keadilan akademik.

---

## ✨ Fitur & Kapabilitas Utama

### 1. Arsitektur Dual-Lens & Sintesis Bukti
- **Normalisasi Bukti Terstandarisasi (`EvidenceContract`)**: Mengintegrasikan metrik exact (similarity, kata cocok, pasase terpanjang, max IDF) dengan metrik leksikal (kosinus, shared terms, average IDF).
- **Pola Bukti Objektif**:
  - `VERBATIM_MATCH` $\to$ Keputusan: `MATCH`
  - `COPY_EDIT` $\to$ Keputusan: `REVIEW`
  - `COMMON_ACADEMIC_PHRASE` $\to$ Keputusan: `REVIEW`
  - `SEMANTIC_PARAPHRASE` $\to$ Keputusan: `REVIEW`
  - `PARTIAL_DIRECT_MATCH` $\to$ Keputusan: `REVIEW`
  - `NO_EVIDENCE` $\to$ Keputusan: `NO_MATCH`
- **Source Agreement Tracking**: Menandai apakah sumber rujukan teratas yang terdeteksi oleh kedua lensa saling sepakat (*sources agree*).

### 2. Antarmuka Web Evidence Decomposition
- **Layout 4-Tier Terstruktur**:
  - *Tier 1*: Keputusan (`MATCH`, `REVIEW`, `NO MATCH`), Pola Bukti, dan Status Kesepakatan.
  - *Tier 2*: Side-by-Side Dual-Lens Card (Exact Evidence vs TF-IDF Evidence).
  - *Tier 3*: Kartu *"WHY THIS RESULT?"* dengan narasi transparan alasan ilmiah.
  - *Tier 4*: Daftar Sumber Terdeteksi dan Rincian Pasase yang Cocok.
- **Modal Interaktif `[View passage]`**: Komparasi berdampingan cuplikan teks submisi versus dokumen rujukan lengkap dengan konteks nomor kata dan skor signifikansi. Dilengkapi kontrol keyboard `Escape` dan klik backdrop.
- **Penanganan Status Anggun**: Dukungan visual untuk *loading state* (polling otomatis saat analisis berlangsung) dan *error state*.

### 3. Utilitas Baris Perintah (`./artilik`)
- `./artilik test`: Menjalankan suite pengujian unit (28 tests).
- `./artilik readiness`: Menjalankan pengujian ketahanan produksi 6 skenario anomali.
- `./artilik regression [--cleanup]`: Menjalankan pengujian regresi pipeline E2E penuh 50 kasus uji benchmark standar dengan pembersihan otomatis.
- `./artilik worker [--once]`: Background worker daemon pemroses antrean jobs.
- `./artilik benchmark --engine [hybrid|tfidf|idf|exact]`: Pengujian benchmark laboratorium.

---

## 📊 Hasil Evaluasi & Sertifikasi Mutu

| Kategori Pengujian | Lingkup | Hasil Aktual | Status |
|---|---|---|:---:|
| **Automated Unit Tests** | 7 modul pengujian (`tests/`) | **28 / 28 passed** (0 error, 0 fail) | **LULUS** |
| **Production Readiness** | 6 skenario anomali ekstrem | **6 / 6 edge cases passed** | **LULUS** |
| **Standard Benchmark** | 50 kasus uji terstandarisasi | **Macro F1 = 1.0000**, Binary F1 = 1.0000 | **LULUS** |
| **E2E Pipeline Regression** | HTTP API $\to$ Worker $\to$ DB $\to$ HTML | **50 / 50 cases identical**, 0 misclassifications | **LULUS** |

---

## 🚫 Kebijakan Freeze v1.0 (Locked)
Konfigurasi berikut dikunci secara permanen dan tidak boleh diubah:
- `DEFAULT_EXACT_MATCH_SIMILARITY` = `70.0%`
- `DEFAULT_EXACT_REVIEW_SIMILARITY` = `10.0%`
- `DEFAULT_TFIDF_MATCH_SIMILARITY` = `85.0%`
- `DEFAULT_TFIDF_REVIEW_SIMILARITY` = `10.0%`
- `DEFAULT_EXACT_MATCH_LONGEST` = `50 kata`
- `DEFAULT_EXACT_REVIEW_LONGEST` = `10 kata`
- `DEFAULT_SIGNIFICANT_MIN_IDF` = `0.5`
- `SHINGLE_SIZE` = `5 kata`

---

## 🗺️ Roadmap Pasca-RC Menuju v1.0.0 Final
1. **Release Candidate Audit & Evidence Package** (Selesai pada rilis ini).
2. **Real-world Pilot Testing** (Evaluasi menggunakan dokumen nyata/tesis utuh).
3. **Observability & Logging Baseline**.
4. **Bug-Fix Only Phase**.
5. **v1.0.0 General Availability (GA) Release**.
