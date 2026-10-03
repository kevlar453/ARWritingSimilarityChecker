# AR Tilik — Academic Writing Similarity & Evidence Checker

<p align="center">
  <img src="https://img.shields.io/badge/version-v1.0.0--RC-indigo.svg" alt="Version">
  <img src="https://img.shields.io/badge/status-frozen-emerald.svg" alt="Status">
  <img src="https://img.shields.io/badge/unit%20tests-28%2F28%20passed-brightgreen.svg" alt="Unit Tests">
  <img src="https://img.shields.io/badge/benchmark%20macro%20F1-1.0000-blue.svg" alt="Benchmark F1">
  <img src="https://img.shields.io/badge/PHP-8.1%2B-777BB4.svg" alt="PHP">
  <img src="https://img.shields.io/badge/Python-3.12%2B-3776AB.svg" alt="Python">
</p>

**AR Tilik** adalah sistem penelaah kemiripan karya ilmiah berbasis bukti tekstual (*textual evidence*) dan signifikansi terminologi (*terminological significance*) yang menjunjung tinggi keadilan akademik (*academic fairness*).

Sistem dirancang khusus untuk membedakan antara **penyalinan verbatim mutlak**, **modifikasi kata (*copy-edit*)**, **parafrasa leksikal topikal**, dan **penggunaan frasa baku/klise akademis standar** tanpa pernah melontarkan tuduhan sepihak.

---

## 🏛️ Filosofi & Prinsip Keadilan Akademik

1. **Evidence-Based, Not Punitive**:
   Sistem menyajikan bukti tekstual terperinci untuk pertimbangan penelaah manusia (dosen/reviewer). Persentase kemiripan tinggi **bukan vonis otomatis pelanggaran integritas akademik**.
2. **0 Toleransi Istilah Menghakimi**:
   Seluruh antarmuka, laporan, log, dan basis kode **dilarang keras** menggunakan kata *"plagiat"* atau *"plagiasi"*. Istilah resmi yang digunakan adalah *"kesamaan tekstual"*, *"orientasi kosakata"*, *"pasase sekuensial"*, dan *"frasa umum akademik"*.
3. **Transparansi Tanpa Kotak Hitam (*Explainability Rationale*)**:
   Setiap analisis dilengkapi penjelasan kalimat terurai (*"WHY THIS RESULT?"*) yang menerangkan alasan ilmiah di balik putusan secara transparan.

---

## 🔬 Arsitektur Dual-Lens & Evidence Decomposition

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

### Pola Bukti Resmi (Evidence Patterns):
- **`VERBATIM_MATCH`** $\to$ Putusan: **`MATCH`** (Penyalinan pasase sekuensial mutlak dan orientasi leksikal tinggi).
- **`COPY_EDIT`** $\to$ Putusan: **`REVIEW`** (Pasase berurutan dipertahankan dengan intervensi modifikasi kata).
- **`COMMON_ACADEMIC_PHRASE`** $\to$ Putusan: **`REVIEW`** (Kemiripan didominasi oleh kosakata klise/baku metodologi ilmiah dengan Max IDF $\le 1.0$).
- **`SEMANTIC_PARAPHRASE`** $\to$ Putusan: **`REVIEW`** (Kemiripan leksikal topikal kuat tanpa pasase sekuensial panjang).
- **`PARTIAL_DIRECT_MATCH`** $\to$ Putusan: **`REVIEW`** (Pasase sekuensial terdeteksi pada fragmen teks tertentu).
- **`NO_EVIDENCE`** $\to$ Putusan: **`NO_MATCH`** (Bebas indikasi kesamaan sekuensial maupun leksikal).

---

## ⚡ Antarmuka Baris Perintah (`./artilik`)

AR Tilik dilengkapi utilitas baris perintah praktis:

```bash
# 1. Menjalankan seluruh pengujian unit Pytest (28 tests)
./artilik test

# 2. Menjalankan uji ketahanan produksi & edge cases (file kosong, mikro-teks, unicode, math, dll.)
./artilik readiness

# 3. Menjalankan pengujian regresi E2E penuh 50 kasus uji benchmark standar
./artilik regression [--cleanup]

# 4. Menjalankan benchmark per-engine
./artilik benchmark --engine hybrid
./artilik benchmark --engine tfidf
./artilik benchmark --engine exact

# 5. Menjalankan background worker daemon
./artilik worker [--once]
```

---

## 🛡️ Protokol Perlindungan Regresi (Golden Test)

Setiap perubahan kode berikutnya wajib memenuhi ketiga gerbang kualitas secara berurutan:
1. `./artilik test` $\to$ **28/28 passed**;
2. `./artilik readiness` $\to$ **6/6 edge cases passed**;
3. `./artilik regression --cleanup` $\to$ **50/50 cases passed** (Macro F1 = `1.0000`, Errors = `0`).

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
git clone git@github.com:kevlar45/ARWritingSimilarityChecker.git
cd ARWritingSimilarityChecker

# 2. Konfigurasi Lingkungan
cp .env.example .env
# Sesuaikan kredensial database di .env

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
- **Klaim yang Terbukti**: AR Tilik terbukti 100% lulus benchmark terstandarisasi 50 kasus uji dan pengujian *End-to-End Pipeline Regression* dengan 0 kesalahan klasifikasi (Macro F1 = 1.0000, Binary F1 = 1.0000), serta seluruh 28 pengujian unit dan 6 uji ketahanan produksi lulus sempurna.
- **Klaim yang Dilarang**: Dilarang mengklaim bahwa sistem sudah terbukti akurat untuk semua jenis tulisan akademik di dunia nyata. Dataset benchmark adalah instrumen kalibrasi dan pengujian terstandarisasi yang merepresentasikan tipologi umum karya tulis, bukan jaminan generalisasi universal terhadap seluruh variasi bahasa manusia di dunia nyata.

---

## 📄 Lisensi & Dokumen Terkait
- Spesifikasi Pembekuan v1.0: [`FREEZE_v1.0.md`](FREEZE_v1.0.md)
- Riwayat Evaluasi: Artifacts Walkthrough & Baseline Run #21
