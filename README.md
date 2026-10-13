# AR Tilik — Academic Writing Similarity & Evidence Checker

<p align="center">
  <img src="https://img.shields.io/badge/version-v1.4.0--Production-indigo.svg" alt="Version">
  <img src="https://img.shields.io/badge/status-production--ready-emerald.svg" alt="Status">
  <img src="https://img.shields.io/badge/automated%20tests-120%2B%20passed-brightgreen.svg" alt="Tests">
  <img src="https://img.shields.io/badge/forensic%20engine-Google%20Gemini%20AI-blue.svg" alt="AI Engine">
  <img src="https://img.shields.io/badge/integrations-PKP%20OJS%203.x-emerald.svg" alt="OJS 3.x">
  <img src="https://img.shields.io/badge/PHP-8.1%2B-777BB4.svg" alt="PHP">
  <img src="https://img.shields.io/badge/Python-3.12%2B-3776AB.svg" alt="Python">
</p>

**AR Tilik** adalah sistem penelaah kemiripan karya ilmiah berbasis bukti tekstual (*textual evidence*), orientasi leksikal (*lexical similarity*), kesadaran konteks akademik (*citation context awareness*), dan penalaran semantik **Audit Forensik Cerdas AI** yang menjunjung tinggi keadilan akademik (*academic fairness*).

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

## 🔬 Arsitektur Sistem Multi-Lapisan (v1.3.0 Production)

```text
                           ┌───────────────────────────┐
                           │   Submisi Karya Ilmiah    │
                           │  (Web UI / PKP OJS 3 API) │
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
 │ 2. CITATION & STYLOMETRIC ANALYSIS LAYER (v1.1 & v1.2)                        │
 │    - Deteksi deterministik sitasi & isolasi teks substantif (section-aware)    │
 │    - AI Stylometrics: Monotoni paragraf, frasa formulaik akademik             │
 │    - Cross-lingual similarity & deteksi parafrase lintas-bahasa               │
 └─────────────────────────────────┬─────────────────────────────────────────────┘
                                   │
 ┌─────────────────────────────────▼─────────────────────────────────────────────┐
 │ 3. AUDIT FORENSIK CERDAS AI & ORAL DEFENSE GUIDE (v1.3.0)                     │
 │    - Google Gemini AI multi-key pool (round-robin + rate-limit failover)      │
 │    - Verifikasi fabrikasi sitasi (mendeteksi referensi semu / halusinasi AI)  │
 │    - 3 Set pertanyaan kritis telaah untuk Mitra Bestari & Meja Ujian Sidang   │
 └─────────────────────────────────┬─────────────────────────────────────────────┘
                                   │
 ┌─────────────────────────────────▼─────────────────────────────────────────────┐
 │ 4. SERTIFIKASI DIGITAL, KOLABORASI & OJS INTEGRATION (v1.3.0)                 │
 │    - Sertifikat Keabsahan Digital ber-hash SHA-256 & QR Code verifikasi publik│
 │    - Kolaborasi berbagi dokumen ilmiah (Secure Share Token & User Permissions)│
 │    - Integrasi native PKP OJS 3.x (REST API v1 + ARTilikOJSConnector.php)     │
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

# 10. Simulasi pengujian alur 6 tahap Duta Kampus & generator piagam digital
php spark ambassador:test

# 11. Simulasi pengujian siklus penuh program referral reguler Member-Get-Member
php spark referral:test
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

## 🤝 Ekosistem Pertumbuhan &amp; Jejaring Akademik (v1.4.0)

AR Tilik v1.4.0 menghadirkan modul pertumbuhan organik dan kemitraan akademik terpadu:

### 1. Program Kemitraan Duta Kampus Digital (6 Tahap)
- **Seleksi Asinkron & Verifikasi Identitas**: Verifikasi KTM mahasiswa berprestasi dengan enkripsi berkas privat.
- **Kode Referral Kampus**: Pelacakan atribusi konversi transparan dengan skema bagi hasil komisi 20%.
- **Masa Percobaan (Probation)**: Target 8 bukti aktivitas promosi / 3 transaksi afiliasi per periode.
- **Dashboard Kinerja Real-time**: Metrik omzet, jumlah pendaftar, estimasi komisi, dan riwayat penarikan dana (*payout*).

### 2. Penerbitan Piagam Penghargaan Digital Resmi (Fitur 2)
- **Generator PDF Standar Arsip**: Layout presisi A4 Landscape WeasyPrint 69.0 dengan bingkai ganda Guilloché klasik, roset sudut emas, lambang resmi AR Tilik (watermark), dan segel timbul emas 3D.
- **Kriptografi &amp; QR Code Verifikasi**: Dilengkapi `certificate_code` unik, nomor SK resmi, dan sidik jari integritas SHA-256 yang dapat diverifikasi publik di `/ambassador/certificate/{code}`.
- **Integrasi LinkedIn Profile**: Tombol 1-klik untuk menambahkan kredensial Duta Integritas Akademik langsung ke profil LinkedIn pengguna.

### 3. Program Referral Reguler Member-Get-Member (Fitur 3)
- **Akses Universal Seluruh Pengguna**: Setiap pengguna terdaftar memiliki kode referral pribadi (`REF-XXXX-YYYY`) dan tautan undangan `/ref/{code}` di dashboard utama.
- **Insentif Dua Arah Adil**:
  - **Pengundang**: Menerima **+2 bonus kuota cek naskah gratis** saat rekan mengaktifkan email, dan **+5 kuota gratis** saat rekan melakukan transaksi perdana.
  - **Sahabat Terundang**: Menerima **+1 bonus kuota cek naskah gratis** dan **diskon langsung 10%** pada pembelian paket langganan perdana di Midtrans.
- **Privasi Data Etis (*Ethical Masking*)**: Nama dan alamat email rekan pada tabel riwayat undangan disamarkan secara etis untuk melindungi kerahasiaan data pribadi.

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

- **Brosur &amp; Prospektus Eksekutif Institusi (PDF)**: [`public/docs/Brosur_Eksekutif_ARTilik.pdf`](public/docs/Brosur_Eksekutif_ARTilik.pdf)
- **Buku Panduan Integrasi OJS 3.x (PDF)**: [`public/integrations/ojs/Panduan_Integrasi_OJS_ARTilik.pdf`](public/integrations/ojs/Panduan_Integrasi_OJS_ARTilik.pdf)
- **Dokumentasi REST API & Konektor OJS**: [`public/integrations/ojs/README.md`](public/integrations/ojs/README.md)
- **Release Notes v1.1.0**: [`docs/V1_1_RELEASE_NOTES.md`](docs/V1_1_RELEASE_NOTES.md)
- **Laporan Observabilitas & Kepatuhan SLA**: [`docs/V1_1_OBSERVABILITY.md`](docs/V1_1_OBSERVABILITY.md)
- **Laporan Validasi Panel Pakar Manusia**: [`docs/V1_1_HUMAN_VALIDATION_RESULTS.md`](docs/V1_1_HUMAN_VALIDATION_RESULTS.md)
- **Laporan Hasil Evaluasi Terarah 80 Kasus**: [`docs/V1_1_EVALUATION_RESULTS.md`](docs/V1_1_EVALUATION_RESULTS.md)
- **Rencana Implementasi v1.1**: [`docs/V1_1_IMPLEMENTATION_PLAN.md`](docs/V1_1_IMPLEMENTATION_PLAN.md)
- **Spesifikasi Pembekuan Engine Baseline v1.0.0**: [`docs/POST_RELEASE_FINDINGS_V1_1_BACKLOG.md`](docs/POST_RELEASE_FINDINGS_V1_1_BACKLOG.md)
