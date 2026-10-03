# Panduan Reproduksibilitas — AR Tilik v1.0

Dokumen ini menyediakan panduan langkah demi langkah (*step-by-step reproduction guide*) untuk mereplikasi seluruh lingkungan, menjalankan sistem, dan memverifikasi pengujian *golden baseline* AR Tilik secara mandiri.

---

## 💻 1. Spesifikasi Lingkungan Sistem

### Sistem Operasi & Arsitektur
- **OS**: Linux (Ubuntu 22.04 LTS / Debian 12 / sejenisnya).
- **Arsitektur**: x86_64 / ARM64.

### Dependensi Bahasa & Layanan
- **PHP**: `8.1` atau lebih tinggi.
  - Ekstensi PHP wajib: `php-cli`, `php-mysql`, `php-mbstring`, `php-curl`, `php-xml`, `php-intl`.
- **Database**: MySQL `8.0+` atau MariaDB `10.5+` (Engine: InnoDB).
- **Python**: `3.10` atau lebih tinggi (disarankan menggunakan *virtual environment* `.venv`).
- **Web Server**: Apache / Nginx / PHP built-in CLI (`php spark serve`).

---

## 🛠️ 2. Langkah Instalasi dari Awal

### Langkah 1: Kloning Repositori
```bash
git clone https://github.com/kevlar453/ARWritingSimilarityChecker.git
cd ARWritingSimilarityChecker
```

### Langkah 2: Konfigurasi Lingkungan (`.env`)
Salin berkas contoh konfigurasi:
```bash
cp .env.example .env
```
Sesuaikan parameter database di `.env`:
```ini
CI_ENVIRONMENT = development

database.default.hostname = localhost
database.default.database = ar_tilik_db
database.default.username = your_db_user
database.default.password = your_db_password
database.default.DBDriver = MySQLi
database.default.port = 3306

app.baseURL = 'http://localhost:8085/'
```

### Langkah 3: Inisialisasi Database
Jalankan migrasi CodeIgniter 4:
```bash
php spark migrate
```

### Langkah 4: Penyiapan Python Virtual Environment
```bash
python3 -m venv .venv
source .venv/bin/activate
pip install --upgrade pip
pip install -r requirements.txt
```
*Daftar paket utama dalam `requirements.txt`:*
- `pymupdf` (ekstraksi teks PDF)
- `numpy`, `scipy` (vektor TF-IDF dan kalkulasi sparse cosine)
- `mysql-connector-python` (konektivitas worker ke MariaDB/MySQL)
- `requests` (HTTP client untuk suite E2E regression)
- `python-dotenv` (pembacaan konfigurasi `.env`)
- `pytest` (framework automated unit testing)

### Langkah 5: Hak Akses Eksekusi CLI
Pastikan utilitas `./artilik` memiliki izin eksekusi:
```bash
chmod +x artilik
```

---

## 🚦 3. Menjalankan Layanan

### Menjalankan Web Server (Terminal 1)
```bash
php spark serve --port 8085
```
Aplikasi web kini dapat diakses melalui browser di `http://localhost:8085`.

### Menjalankan Background Worker Daemon (Terminal 2)
```bash
./artilik worker
```
Worker akan memantau tabel `jobs` secara non-blocking dan memproses antrean secara otomatis.

---

## 🧪 4. Menjalankan Suite Verifikasi (Golden Test Suite)

Untuk memverifikasi bahwa sistem terpasang sempurna dan tidak mengalami deviasi dari baseline v1.0, jalankan urutan berikut:

### 1. Automated Unit Tests (28 Kasus)
```bash
./artilik test
```
*Kriteria Lulus*: Seluruh 28 test lulus (`28 passed in ~12s`).

### 2. Production Readiness & Edge Cases (6 Skenario)
```bash
./artilik readiness
```
*Kriteria Lulus*: Seluruh 6 skenario edge case (0-byte, mikro, long-text, unicode, duplicate, HTML view) berstatus `[✓] SEMUA PENGUJIAN PRODUCTION READINESS LULUS DENGAN AMAN!`.

### 3. E2E Pipeline Regression Test (50 Kasus Uji Benchmark)
```bash
./artilik regression --cleanup
```
*Kriteria Lulus*: 
- `50 / 50 cases identical` dengan Run #21.
- `Binary F1 Score = 1.0000`
- `Macro F1 Score = 1.0000`
- `Total Salah Klasifikasi: 0`
- `Pembersihan dokumen uji sementara: Sukses`.

Jika ketiga perintah di atas menghasilkan status hijau, lingkungan instalasi Anda **100% tervalidasi identik dengan rilis resmi AR Tilik v1.0**.
