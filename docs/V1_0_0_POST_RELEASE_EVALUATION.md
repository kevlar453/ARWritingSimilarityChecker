# Evaluasi Pasca-Rilis (Post-Release Evaluation) — AR Tilik v1.0.0

Dokumen ini merupakan catatan evaluasi resmi (*post-release evaluation milestone*) untuk **AR Tilik v1.0.0 General Availability (GA)**. Dokumen ini merangkum data empiris dari tiga pilar validasi, membekukan baseline operasional pertama, mendokumentasikan interpretasi bukti secara ilmiah, serta merumuskan backlog terstruktur menuju **v1.1 (Phase 3)**.

---

## 📌 1. Informasi Rilis (Release Information)

- **Nama Produk**: AR Tilik (*Academic Writing Similarity & Evidence Checker*)
- **Versi Rilis**: `v1.0.0` (General Availability)
- **Status Basis Kode**: **Frozen (Locked Baseline)**
- **Tanggal Rilis**: 03 Oktober 2026
- **Commit Hash**: `dd84424` (Tag: `v1.0.0`)
- **Repositori Resmi**: [https://github.com/kevlar453/ARWritingSimilarityChecker](https://github.com/kevlar453/ARWritingSimilarityChecker)
- **Status Komponen**:
  - Exact Matcher (5-gram Shingle, FNV-1a): **FROZEN**
  - TF-IDF Vectorizer (Sparse Cosine, DF): **FROZEN**
  - Hybrid Aggregation (`aggregate_evidence`): **FROZEN**
  - Web UI & Evidence Decomposition: **PRODUCTION READY**
  - PDF Exporter (WeasyPrint Engine): **PRODUCTION READY**

---

## 🧪 2. Hasil Pengujian Otomatis (Automated Tests)

Suite pengujian unit otomatis mencakup seluruh modul inti: normalizer, shingler, IDF calculator, matcher, tfidf vectorizer, hybrid synthesis, production edge cases, dan PDF exporter.

- **Perintah**: `./artilik test`
- **Total Pengujian**: **32 / 32 Passed** (100% Lulus)
- **Waktu Eksekusi**: 10,97 detik
- **Rincian Modul**:
  - `tests/test_similarity.py` (5 tests): penggabungan rentang pasase, pencegahan double-counting.
  - `tests/test_idf.py` (5 tests): evaluasi signifikansi kata langka vs frasa umum.
  - `tests/test_matcher.py` (3 tests): deduplikasi pasangan shingle dan pemisahan sumber.
  - `tests/test_tfidf.py` (3 tests): kosinus vektor ortogonal, identik, dan pembobotan TF.
  - `tests/test_hybrid.py` (8 tests): kontrak bukti, sintesis pola, dan pembekuan baseline.
  - `tests/test_stress.py` (3 tests): needle in haystack dan toleransi noise sitasi.
  - `tests/test_production_readiness.py` (1 test): integrasi 6 skenario batas.
  - `tests/test_pdf_export.py` (4 tests): verifikasi hash, ekspor PDF direct, HTTP endpoint, preview HTML.

---

## 📊 3. Tiga Pilar Bukti Validasi Sistem (Three Pillars of Evidence)

Sistem AR Tilik v1.0.0 dievaluasi menggunakan tiga jenis bukti yang memiliki tujuan pembuktian berbeda. **Ketiga pilar ini tidak dicampur menjadi satu angka tunggal**, melainkan dinilai secara mandiri:

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                       TIGA PILAR VALIDASI AR TILIK                          │
├────────────────────────┬───────────────────────────┬────────────────────────┤
│ VALIDATION A           │ VALIDATION B              │ VALIDATION C           │
│ Synthetic Benchmark    │ Production Readiness      │ Real Thesis Pilot      │
├────────────────────────┼───────────────────────────┼────────────────────────┤
│ • 50 Kasus Uji Standar │ • 6 Skenario Batas        │ • Tesis 174 Halaman    │
│ • Macro F1 = 1.0000    │ • 0 Crash / 0 Exception   │ • 34.470 Kata Nyata    │
│ • Binary F1 = 1.0000   │ • Idempoten & UTF-8 Aman  │ • Worker 6.12 detik    │
│ • 0 Salah Klasifikasi  │ • Rendering HTML Utuh     │ • REVIEW (0.03% Exact) │
├────────────────────────┼───────────────────────────┼────────────────────────┤
│ Pertanyaan Terjawab:   │ Pertanyaan Terjawab:      │ Pertanyaan Terjawab:   │
│ Apakah sistem benar-   │ Apakah sistem tahan       │ Bagaimana dekomposisi  │
│ benar mengimplementa-  │ terhadap kondisi opera-   │ bukti berperilaku pada │
│ sikan aturan klasifi-  │ sional dasar di ling-     │ naskah karya ilmiah    │
│ kasi yang dirancang?   │ kungan nyata?             │ akademik riil?         │
└────────────────────────┴───────────────────────────┴────────────────────────┘
```

### A. Validasi Benchmark Sintetik (Validation A)
- **Komposisi**: 10 Identical, 10 Copy-Edit, 10 Paraphrase, 10 Common Academic Phrase, 10 Unrelated.
- **Hasil**: 50 / 50 kasus uji terklasifikasi sempurna sesuai expected label.
- **Matriks Konfusi**:
  ```
  EXPECTED \ PRED      MATCH      REVIEW    NO_MATCH
  --------------------------------------------------
  match                   10           0           0
  review                   0          30           0
  no_match                 0           0          10
  --------------------------------------------------
  Total Salah Klasifikasi: 0 | Macro F1 = 1.0000 | Binary F1 = 1.0000
  ```

### B. Validasi Kesiapan Produksi (Validation B)
- **Skenario 1 (Berkas 0-byte)**: Berhasil ditangani tanpa `ZeroDivisionError` (Sim = 0.00%, Status = completed).
- **Skenario 2 (Teks Mikro 2 kata)**: Shingle 5-gram anggun mengembalikan himpunan kosong tanpa *out-of-bounds* (Decision = no_match).
- **Skenario 3 (Teks Panjang 3.000 kata)**: Selesai < 1.2 detik tanpa timeout atau memory leak.
- **Skenario 4 (Karakter Non-ASCII & Rumus)**: Normalisasi NFKC menangani petik keriting (“”), apostrof (’), dan simbol matematika tanpa merusak token.
- **Skenario 5 (Submisi Duplikat Beruntun)**: Transaksi DB autocommit mencegah race condition dan deadlock.
- **Skenario 6 (Integritas Rendering HTML)**: Seluruh 4-tier Evidence Decomposition dan modal interaktif ter-render utuh (HTTP 200).

### C. Validasi Naskah Tesis Nyata (Validation C)
- **Dokumen Uji**: Tesis Magister Teologi Katolik oleh Emiliana Vivi (2026).
- **Spesifikasi**: 174 halaman, 34.470 kata, 269.331 karakter, 2,13 MB PDF.
- **Throughput**:
  - Ekstraksi PDF (PyMuPDF): **0,297 detik**
  - Pemrosesan Worker Penuh: **6,12 detik**
- **Hasil Analisis (Report #476)**:
  - Keputusan: **`REVIEW`**
  - Exact Similarity: **`0,03%`** (Pasase terpanjang: 6 kata)
  - TF-IDF Cosine Similarity: **`18,82%`**
  - Shared Terms Count: **60 istilah**
  - Average Shared IDF: **2,3022**
  - Pola Bukti: **`SEMANTIC_PARAPHRASE`**
  - Kesepakatan Bukti: **`COMPLEMENTARY_EVIDENCE`** (Sources Agree: YA)

---

## 🔍 4. Interpretasi Bukti Ilmiah & Temuan Konkret Tesis

### A. Bukti Konkret di Balik Angka
Sistem mendeteksi pasase cocok terpanjang sepanjang 6 kata:
> *"Berdasarkan hasil penelitian yang telah dilakukan,"*

Frasa ini beririsan dengan beberapa dokumen rujukan di korpus institusi. Temuan ini menjadi bukti konkret mengapa angka kesamaan sekuensial yang sangat kecil (0,03%) tidak boleh dijadikan dasar tuduhan plagiarisme:
- Frasa ini merupakan **formula metodologis standar** (*academic formulaic sequence*) yang wajar digunakan dalam bab kesimpulan karya ilmiah.
- Penetapan status **`REVIEW`** secara presisi meminta dosen penilai untuk meninjau bukti teks, bukan melontarkan tuduhan penjiplakan.

### B. Formulasi Narasi Ilmiah Resmi (Calibrated Academic Narrative)
Untuk menjaga integritas ilmiah tanpa overklaim kausal dari satu naskah pilot, AR Tilik mendokumentasikan hasil evaluasi dengan rumusan terukur:
> **"Pada pilot tesis ini, AR Tilik mengklasifikasikan dokumen sebagai REVIEW dan memisahkan bukti exact sequential overlap yang sangat rendah (0,03%) dari kemiripan leksikal/topikal (18,82%), sehingga hasil dapat ditinjau berdasarkan jenis evidence, bukan similarity score tunggal."**

---

## 📈 5. Baseline Observabilitas Produksi (v1.0.0-production-baseline)

Berikut adalah data operasional baku (*production baseline*) yang dibekukan pada saat rilis v1.0.0 GA:

```ini
[v1.0.0-production-baseline]
jobs_ingested       = 478
jobs_completed      = 478
jobs_failed         = 0

total_documents     = 1811
submissions         = 1370
reference_corpus    = 441

total_reports       = 326
reports_completed   = 326
reports_failed      = 0

decision_match      = 61   (18.7%)
decision_review     = 167  (51.2%)
decision_no_match   = 98   (30.1%)

pattern_no_evidence            = 27.6%
pattern_semantic_paraphrase    = 19.3%
pattern_verbatim_match         = 18.7%
pattern_common_academic_phrase = 16.0%
pattern_copy_edit              = 12.3%
pattern_partial_direct_match   = 3.7%
```

### Catatan Pelaporan Latensi (Statistical Integrity)
Pengukuran latensi worker pada rilis v1.0.0 dicatat sebagai:
- **Status Metrik**: `Initial observed latency baseline (n=7)`
- Rata-rata: **0,930 detik**
- Minimum: **0,020 detik**
- Maksimum: **6,116 detik** (pada dokumen 174 halaman)
*Catatan Metodologis*: Dengan ukuran sampel awal $n=7$ (dan $n=31$ setelah pengujian pasca-rilis), nilai $p95$ belum diperlakukan sebagai representasi statistik operasional jangka panjang. Distribusi $p50/p95/p99$ formal akan dikalkulasi setelah sistem memproses $n \ge 100$ pekerjaan di lingkungan produksi.

---

## ⚠️ 6. Batasan Sistem yang Teridentifikasi (Known Limitations)

1. **Pemeringkatan Sumber Masih Menggabungkan Bobot Leksikal & Direct Overlap**:
   Pada laporan #476, lima dokumen rujukan teratas memiliki `Exact = 0.0%` namun memperoleh skor `11.5% – 18.8%` karena kemiripan kosinus kosakata. Hal ini berpotensi membuat penilai awam mengira sumber tersebut merupakan sumber kutipan verbatim.
2. **Ketiadaan Pengecualian Otomatis Daftar Pustaka**:
   Pada v1.0.0, seluruh isi berkas teks dibandingkan secara menyeluruh termasuk halaman Daftar Pustaka dan Lembar Pengesahan formal.
3. **Cakupan Rujukan Lokal**:
   Sistem hanya membandingkan dokumen terhadap korpus yang tersimpan di basis data lokal, tidak melakukan perayapan web (*web scraping*) terbuka.

---

## 🎯 7. Prioritas Backlog v1.1 / Phase 3

Fokus pengembangan pada Fase 3 dialihkan dari sekadar *"membuat algoritma lebih pintar"* menjadi:
> **"Membuat bukti tekstual lebih terkalibrasi dan lebih berguna bagi reviewer manusia."**

Berikut adalah urutan prioritas backlog untuk AR Tilik v1.1:

| Prioritas | Inisiatif Fitur | Deskripsi Teknis |
|:---:|---|---|
| **P1** | **Source Evidence Semantics** | Memisahkan secara tegas antara **Direct Textual Overlap** (pasase exact) dan **Lexical/Topic Similarity** (relevansi TF-IDF) pada antarmuka daftar sumber rujukan, sehingga sumber dengan `Exact = 0%` diklasifikasikan sebagai *Topical Relevance* alih-alih sumber penjiplakan. |
| **P2** | **Citation & Context Awareness** | Menandai apakah pasase yang beririsan berada di dalam: (1) tanda petik kutipan langsung, (2) format sitasi resmi, (3) daftar pustaka/bibliografi, atau (4) boilerplate metodologi. |
| **P3** | **Calibration Dataset** | Memperluas koleksi kasus uji menggunakan naskah karya ilmiah nyata yang telah dianotasi dan divalidasi oleh pakar/komite etik akademik manusia. |
| **P4** | **Large-Scale Observability** | Meningkatkan pencatatan sampel latensi dari $n=7$ menjadi ribuan pekerjaan untuk menghasilkan kurva distribusi latensi $p50$, $p90$, $p95$, dan $p99$ yang representatif. |
| **P5** | **Reviewer Feedback Mechanism** | Menyediakan tombol interaktif bagi dosen penilai di web dashboard untuk memberi label kualitatif: `[Evidence relevant]`, `[Common phrase]`, `[Properly cited]`, atau `[Needs investigation]`. |

---

## 🏁 8. Kesimpulan Evaluasi

AR Tilik v1.0.0 telah membuktikan stabilitas matematis, ketahanan arsitektur, dan kegunaan dekomposisi bukti tekstual pada karya ilmiah nyata. Dengan selesainya evaluasi pasca-rilis ini, baseline v1.0.0 dinyatakan **terkunci permanen**, dan peta jalan menuju **v1.1 (Phase 3)** memiliki landasan bukti empiris yang kokoh.
