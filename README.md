# AR Tilik — Academic Writing Similarity & Evidence Checker

<p align="center">
  <img src="https://img.shields.io/badge/version-v1.1.0--GA-indigo.svg" alt="Version">
  <img src="https://img.shields.io/badge/status-production--ready-emerald.svg" alt="Status">
  <img src="https://img.shields.io/badge/automated%20tests-116%2F116%20passed-brightgreen.svg" alt="Tests">
  <img src="https://img.shields.io/badge/human%20validation-100%25%20agreement-blue.svg" alt="Human Validation">
  <img src="https://img.shields.io/badge/latency%20overhead-%3C%20150ms%20(0.23ms%20avg)-teal.svg" alt="SLA">
  <img src="https://img.shields.io/badge/PHP-8.1%2B-777BB4.svg" alt="PHP">
  <img src="https://img.shields.io/badge/Python-3.12%2B-3776AB.svg" alt="Python">
</p>

**AR Tilik** adalah sistem penelaah kemiripan karya ilmiah berbasis bukti tekstual (*textual evidence*), orientasi leksikal (*lexical similarity*), dan kesadaran konteks akademik (*citation context awareness*) yang menjunjung tinggi keadilan akademik (*academic fairness*).

Sistem dirancang secara khusus untuk membedakan antara **penyalinan pasase sekuensial verbatim mutlak**, **modifikasi kata (*copy-edit*)**, **parafrasa leksikal topikal**, **kutipan berizin (*proper citations/quotes*)**, dan **penggunaan frasa baku metodologi ilmiah** tanpa melontarkan tuduhan sepihak.

---

## 🏛️ Filosofi & Prinsip Keadilan Akademik

1. **Detect $\to$ Decompose $\to$ Contextualize $\to$ Human Judgment**:
   Deteksi kesamaan mesin (*machine evidence detection*) adalah instrumen bukti objektif, **bukan vonis otomatis pelanggaran integritas akademik**. Penilaian substantif berada di tangan penelaah manusia (*dosen/reviewer*).
2. **0 Toleransi Istilah Menghakimi**:
   Seluruh antarmuka, laporan, log, dan basis kode **dilarang keras** menggunakan kata *"plagiat"* atau *"plagiasi"*. Istilah resmi yang digunakan adalah *"kesamaan tekstual"*, *"orientasi kosakata"*, *"pasase sekuensial"*, dan *"frasa baku akademik"*.
3. **Transparansi Tanpa Kotak Hitam (*Explainability Rationale*)**:
   Setiap analisis dilengkapi penjelasan kalimat terurai (*"WHY THIS RESULT?"*) yang menerangkan alasan ilmiah di balik putusan secara transparan.
4. **Non-Suppression Invariant**:
   Kehadiran sitasi atau kutipan memperkaya pemahaman konteks, namun **tidak pernah memanipulasi atau menekan angka persentase kemiripan tekstual murni**.

---

## 🔬 Arsitektur Sistem 4-Lapisan (v1.1.0)

```text
                           ┌───────────────────────────┐
                           │   Submisi Karya Ilmiah    │
                           └─────────────┬─────────────┘
                                         │
 ┌───────────────────────────────────────┴───────────────────────────────────────┐
 │ 1. DUAL-LENS MATCHING LAYER (v1.0.0 FROZEN Reference Baseline)                │
 │    ┌─────────────────────────┐             ┌─────────────────────────┐        │
 │    │   EXACT MATCHER LENS    │             │   TF-IDF VECTORIZER     │        │
 │    │ (5-Gram Sequential Pass)│             │  (Sparse Cosine Space)  │        │
 │    └────────────┬────────────┘             └────────────┬────────────┘        │
 │                 │ Raw Exact Evidence                    │ Raw Lexical Evidence│
 │                 ▼                                       ▼                     │
 │    ┌────────────────────────────────────────────────────────┐                 │
 │    │          HYBRID EVIDENCE AGGREGATION (FROZEN)          │                 │
 │    │  - Decision Invariant: MATCH / REVIEW / NO_MATCH       │                 │
 │    │  - Patterns: VERBATIM, COPY_EDIT, PARAPHRASE, etc.     │                 │
 │    └────────────────────────────┬───────────────────────────┘                 │
 └─────────────────────────────────┼─────────────────────────────────────────────┘
                                   │
 ┌─────────────────────────────────▼─────────────────────────────────────────────┐
 │ 2. CITATION & CONTEXT DETECTOR LAYER (v1.1)                                   │
 │    - Deteksi deterministik: Inline, Parenthetical, Direct Quote, Block Quote   │
 │    - Ekstraksi Context Span (posisi karakter) & Auditable Rationale           │
 │    - Penanganan status ambigu deterministik -> UNKNOWN                        │
 └─────────────────────────────────┬─────────────────────────────────────────────┘
                                   │
 ┌─────────────────────────────────▼─────────────────────────────────────────────┐
 │ 3. DECOMPOSED EVIDENCE PRESENTATION LAYER (v1.1)                              │
 │    - Source Group 1: Direct Textual Overlap (Exact > 0 atau Matched Words > 0)│
 │    - Source Group 2: Lexical / Topic Similarity (Cosine > 0)                  │
 │    - Divergence Alert Generator: Deteksi Exact ≈ 0% dengan Cosine > 0%        │
 └─────────────────────────────────┬─────────────────────────────────────────────┘
                                   │
 ┌─────────────────────────────────▼─────────────────────────────────────────────┐
 │ 4. HUMAN VALIDATION & OPERATIONAL OBSERVABILITY LAYER (v1.1)                  │
 │    - Expert Review Panel: 30 Kasus Terprioritas (100% Reviewer Agreement)     │
 │    - Real-Time SLA Tracking: Overhead < 150ms (p95: 0.186 ms)                 │
 │    - Structured JSON Lines Telemetry: writable/logs/v1_1_observability.log    │
 │    - Dashboard CLI & Web Report Interface (Interactive Passage Inspection)    │
 └───────────────────────────────────────────────────────────────────────────────┘
```

---

## ⚡ Antarmuka Baris Perintah (`./artilik`)

AR Tilik dilengkapi utilitas baris perintah praktis:

```bash
# 1. Menjalankan seluruh pengujian unit & regresi (116 tests)
./artilik test

# 2. Menjalankan uji ketahanan produksi & edge cases ekstrem
./artilik readiness

# 3. Menampilkan dashboard observabilitas operasional & kepatuhan SLA v1.1
./artilik observability

# 4. Menjalankan micro-benchmark latensi lintas ukuran dokumen (hingga 5.000 kata)
./artilik observability --benchmark

# 5. Mengambil data telemetri dalam format JSON terstruktur
./artilik observability --json

# 6. Menampilkan metrik inventori, kesehatan antrean, dan seksi [7] observabilitas
./artilik metrics

# 7. Menjalankan background worker daemon
./artilik worker [--once]

# 8. Ekspor laporan kemiripan ke format PDF bersertifikasi hash verifikasi
./artilik export --report <id> [--output <file.pdf>]

# 9. Menjalankan modul evaluasi
./artilik eval [generate|run|review]
```

---

## 🛡️ Matriks Sertifikasi Mutu & Release Gates v1.1.0

Rilis v1.1.0 telah diaudit dan lulus 100% pada seluruh kriteria rilis:

| Gerbang Mutu | Lingkup Evaluasi | Kriteria Toleransi | Hasil Aktual | Status |
|:---|:---|:---:|:---:|:---:|
| **Gate V11-A** | Automated Test Suite | Seluruh pengujian passing | **116 / 116 passed** | ✅ **PASS** |
| **Gate V11-B** | Context Detection | Akurasi deteksi konteks $\ge 95\%$ | **100.0% (80/80)** | ✅ **PASS** |
| **Gate V11-C** | Citation Status | Akurasi status atribusi $\ge 95\%$ | **100.0% (80/80)** | ✅ **PASS** |
| **Gate V11-D** | Evidence Traceability | Bukti memiliki span & rationale | **100.0% (80/80)** | ✅ **PASS** |
| **Gate V11-E** | Zero Suppression | Metrik exact & cosine tidak ditekan | **100.0% (86/86)** | ✅ **PASS** |
| **Gate V11-F** | Decision Invariance | Putusan v1.0.0 tidak berubah | **100.0% (86/86)** | ✅ **PASS** |
| **Gate V11-G** | Engine Frozen Integrity | 0 diff lines pada `worker/hybrid.py` | **0 baris modifikasi** | ✅ **PASS** |
| **Gate V11-H** | Human Validation | Reviewer agreement pada 30 kasus | **100.0% (30/30)** | ✅ **PASS** |
| **Gate V11-I** | Latency SLA Overhead | Parsing sitasi $< 150\text{ ms}$ | **0.2334 ms (p95: 0.186 ms)**| ✅ **PASS** |
| **Gate V11-J** | Production Readiness | 6 skenario edge cases & anomali | **6 / 6 passed** | ✅ **PASS** |

---

## 🚀 Panduan Instalasi Cepat

### Prasyarat:
- Linux / macOS
- PHP 8.1+ & Composer
- Python 3.12+ & Virtualenv
- MariaDB / MySQL 8.0+

### Langkah-Langkah:
```bash
# 1. Clone repositori
git clone git@github.com:kevlar453/ARWritingSimilarityChecker.git
cd ARWritingSimilarityChecker

# 2. Konfigurasi Lingkungan
cp .env.example .env
# Sesuaikan kredensial basis data pada .env

# 3. Import Skema Basis Data
mysql -u your_user -p db_artilik < schema.sql

# 4. Pasang Dependensi PHP & Python
composer install
python3 -m venv .venv
source .venv/bin/activate
pip install -r <(echo "mysql-connector-python>=8.0.0"; echo "pytest>=8.0.0")

# 5. Jalankan Worker Latar Belakang & Server Web
./artilik worker &
php spark serve --port 8085
```
Akses antarmuka web di: `http://localhost:8085/dashboard`

---

## 📖 Batasan & Integritas Klaim Ilmiah

AR Tilik memegang teguh batasan kejujuran ilmiah:
- **Klaim yang Terbukti**:
  - Pada *targeted evaluation* 80 kasus akademik terarah, deteksi konteks dan status sitasi mencapai 100% tanpa defek engine.
  - Pada validasi panel pakar independen 30 kasus terprioritas, tercapai tingkat kesepakatan 100% (24 CORRECT + 6 ACCEPTABLE).
  - Overhead komputasi parsing sitasi terbukti berada di bawah 150 ms (rata-rata 0.23 ms, p95: 0.186 ms).
- **Klaim yang Dilarang**:
  - Dilarang menggabungkan hasil evaluasi sintetis dengan validasi penelaah manusia menjadi klaim tunggal *"akurasi 100%"*.
  - Dilarang mengklaim bahwa sistem mampu menggantikan pertimbangan substantif dewan etik atau penelaah akademik.

---

## 📄 Dokumentasi Terkait

- **Release Notes v1.1.0**: [`docs/V1_1_RELEASE_NOTES.md`](docs/V1_1_RELEASE_NOTES.md)
- **Laporan Observabilitas & Kepatuhan SLA**: [`docs/V1_1_OBSERVABILITY.md`](docs/V1_1_OBSERVABILITY.md)
- **Laporan Validasi Panel Pakar Manusia**: [`docs/V1_1_HUMAN_VALIDATION_RESULTS.md`](docs/V1_1_HUMAN_VALIDATION_RESULTS.md)
- **Laporan Hasil Evaluasi Terarah 80 Kasus**: [`docs/V1_1_EVALUATION_RESULTS.md`](docs/V1_1_EVALUATION_RESULTS.md)
- **Rencana Implementasi v1.1**: [`docs/V1_1_IMPLEMENTATION_PLAN.md`](docs/V1_1_IMPLEMENTATION_PLAN.md)
- **Spesifikasi Pembekuan Engine Baseline v1.0.0**: [`docs/POST_RELEASE_FINDINGS_V1_1_BACKLOG.md`](docs/POST_RELEASE_FINDINGS_V1_1_BACKLOG.md)
