# Panduan Integrasi AR Tilik untuk Open Journal Systems (OJS 3.x)

> 📕 **DOKUMENTASI RESMI VERSI PDF SIAP UNDUH (v1.3.0)**:  
> Buku panduan lengkap berformat PDF visual resolusi tinggi (8 Halaman) edisi v1.3.0 Production kini telah tersedia. Dilengkapi dengan **diagram alur kerja end-to-end, konsep komputasi Dual-Lens, modul Audit Forensik Cerdas AI, Panduan Pertanyaan Telaah Redaksi, tangkapan layar langkah-demi-langkah, contoh kode PHP siap pakai (`artilik_ojs_runner.php`), spesifikasi REST API publik v1, serta informasi keunggulan & kemitraan institusi AR Tilik**.  
> 👉 **[Unduh Panduan Integrasi OJS 3.x (PDF)](Panduan_Integrasi_OJS_ARTilik.pdf)** *(atau buka via browser: `/integrations/ojs/pdf`)*

> [!NOTE]
> **Karakteristik Arsitektur & Prinsip Kejujuran Teknis:**  
> Paket integrasi ini secara bawaan berwujud **PHP Client SDK & Integration Library** dengan eksekutor CLI mandiri (`artilik_ojs_runner.php`), bukan plugin visual PKP (*native GUI plugin*) yang otomatis aktif begitu file disalin ke sembarang folder.  
> Agar terintegrasi ke dalam alur kerja editorial OJS, developer atau pengelola teknis jurnal perlu melakukan pengkabelan (*wiring*) — baik melalui pemanggilan skrip dari *cron/daemon*, penyematan pada formulir keputusan editor, maupun pendaftaran hook PKP OJS (`HookRegistry`).  
> *(Bagi pengembang yang membutuhkan kerangka PKP Generic Plugin siap pakai dengan `version.xml` dan `HookRegistry`, kami juga menyediakan boilerplate pada folder `pkp-plugin/` atau unduhan paket ZIP).*

Modul ini memungkinkan pengelola jurnal ilmiah di lingkungan kampus yang menggunakan **PKP Open Journal Systems (OJS 3.x)** untuk melakukan pengecekan orisinalitas naskah secara terprogram menggunakan mesin **Dual-Lens Academic Writing Similarity Checker (AR Tilik)** yang dilengkapi penalaran semantik **Audit Forensik Cerdas AI**.

---

## 🚀 Fitur Unggulan Integrasi OJS (Platform v1.3.0)

1. **Pengecekan Naskah melalui Pustaka Mandiri & REST API**:
   - Menyediakan pustaka konektor PHP (`ARTilikOJSConnector.php`) dan REST API v1 untuk mengevaluasi naskah artikel jurnal secara terprogram dalam alur penelaahan editor.
2. **Mesin Evaluasi Dual-Lens Akurat**:
   - **Lensa 1 (5-Gram Exact Matching)**: mendeteksi pasase salin-tempel literal kata-demi-kata.
   - **Lensa 2 (TF-IDF Cosine Vector)**: mendeteksi kemiripan orientasi kosakata, parafrase kalimat, dan istilah topikal ilmiah.
3. **Audit Forensik Cerdas AI (Google Gemini Semantics)**:
   - Memvalidasi sitasi dan referensi naskah guna mengidentifikasi fabrikasi rujukan/halusinasi AI (*hallucinated citations*), inkonsistensi metodologi, dan anomali struktur teks ilmiah.
4. **Panduan Pertanyaan Telaah Redaksi & Mitra Bestari**:
   - Menghasilkan 3 set pertanyaan telaah kritis terarah yang dapat langsung disalin Dewan Redaksi/Reviewer ke formulir *Review Discussions* atau lembar permintaan revisi (*Revision Request*).
5. **Isolasi Teks Substantif (Section-Aware)**:
   - Secara cerdas memisahkan batang tubuh naskah (Pendahuluan s/d Kesimpulan) dan mengecualikan judul/cover, lampiran, serta daftar pustaka agar penilaian adil bagi penulis jurnal.
6. **Sertifikat Keabsahan Digital Berkode QR**:
   - Menghasilkan bukti keabsahan ber-hash kriptografis SHA-256 dengan tautan verifikasi publik (`https://tilik.arsetontong.top/verify/ART-...`) sebagai bukti dukung pemenuhan kepatuhan akreditasi ARJUNA & indeksasi SINTA.
7. **Unduh PDF Laporan Resmi**:
   - Lampirkan sertifikat PDF langsung ke riwayat peninjauan (*Review Discussions*) OJS untuk mitra bestari dan penulis.

---

## 🔄 Dua Mode Komunikasi: Polling vs Webhook Callback

Saat mengintegrasikan naskah OJS ke AR Tilik, terdapat dua metode pemantauan hasil:

| Fitur | Mode 1: Outbound Polling (`waitForCompletion`) | Mode 2: Inbound Webhook (`callback_url`) |
| :--- | :--- | :--- |
| **Arah Koneksi** | **Outbound murni** (Server OJS / Laptop $\rightarrow$ AR Tilik) | **Inbound callback** (AR Tilik $\rightarrow$ Server OJS) |
| **Kebutuhan Jaringan** | **Tanpa Public IP / Tanpa Port Forwarding / Tanpa Tunnel** | Memerlukan URL publik yang dapat dijangkau server cloud |
| **Uji di Localhost / Docker** | **Langsung jalan 100% tanpa konfigurasi tambahan** | Butuh Tunnel publik (Cloudflare Tunnel / ngrok) |
| **Kesesuaian Lingkungan** | **Rekomendasi Utama** untuk Laptop pengembang, PKP containers, intranet kampus | Cocok untuk server OJS produksi dengan domain FQDN aktif |

> [!TIP]
> **Rekomendasi Pengujian:** Gunakan **Mode Polling (`waitForCompletion()`)** untuk seluruh pengujian awal dan server intranet kampus. Anda tidak perlu menyetel port forwarding, firewall, ataupun tunnel publik.

---

## 🧪 Rencana Uji Termurah & Paling Praktis (End-to-End Test)

Sebelum menghabiskan waktu memasang dan mengonfigurasi OJS secara penuh, Anda dapat menguji seluruh alur kerja integrasi (*end-to-end*) hanya dalam waktu < 40 detik:

### Langkah 1: Jalankan Script Runner Mandiri
```bash
# 1. Unduh konektor dan skrip runner
curl -sO https://tilik.arsetontong.top/integrations/ojs/ARTilikOJSConnector.php
curl -sO https://tilik.arsetontong.top/integrations/ojs/artilik_ojs_runner.php

# 2. Pasang API Key Anda (dibuat di dashboard AR Tilik)
export ARTILIK_API_KEY="artilik_ojs_YOUR_SECRET_API_KEY_HERE"

# 3. Jalankan pengujian pada satu berkas PDF naskah
php artilik_ojs_runner.php /path/to/manuscript.pdf "Judul Artikel Jurnal" "Dr. Nama Penulis" "sub_101"
```

Skrip ini akan secara otomatis:
1. Mengunggah berkas PDF naskah ke mesin evaluasi AR Tilik melalui REST API v1.
2. Memantau antrean komputasi secara asinkron hingga selesai (Mode Polling).
3. Mengembalikan skor kemiripan terpadu, dekomposisi Dual-Lens (Lensa 1 & 2), putusan redaksi, tautan laporan web, hasil Audit Forensik AI, panduan pertanyaan telaah reviewer, dan tautan sertifikat QR SHA-256.

### Langkah 2: Lanjutkan ke Wiring OJS
Setelah siklus pada Langkah 1 terbukti sukses 100%, Anda siap menghubungkan pustaka konektor ke alur kerja OJS yang sebenarnya.

---

## 🛠️ Langkah Integrasi & Wiring di Server OJS

### 1. Buat API Key di AR Tilik
1. Masuk ke Dashboard AR Tilik (`https://tilik.arsetontong.top/dashboard`).
2. Buka menu **Integrasi API & OJS**.
3. Klik **Buat API Key Baru**, beri nama jurnal Anda (contoh: `Jurnal Teknologi Informasi SINTA 2`).
4. Salin API Key yang dihasilkan (format: `artilik_ojs_...`).

### 2. Pilihan Model Integrasi

#### Opsi A: Sebagai Custom Library / Script Automation (Paling Fleksibel)
Salin pustaka konektor ke direktori script/library OJS Anda:
```bash
mkdir -p /var/www/ojs/tools/artilik/
cp ARTilikOJSConnector.php /var/www/ojs/tools/artilik/
cp artilik_ojs_runner.php /var/www/ojs/tools/artilik/
chmod 644 /var/www/ojs/tools/artilik/*.php
```

Contoh pemanggilan dalam kode PHP penelaahan naskah:
```php
require_once '/var/www/ojs/tools/artilik/ARTilikOJSConnector.php';

// Inisialisasi konektor dengan API Key Anda
$connector = new ARTilikOJSConnector(
    'artilik_ojs_YOUR_SECRET_API_KEY_HERE',
    'https://tilik.arsetontong.top/api/v1'
);

try {
    // 1. Kirim naskah artikel yang diunggah penulis
    $filePath = '/path/to/ojs/files/journals/1/articles/124/submission/124-1-456-1-SM.pdf';
    $response = $connector->submitManuscript(
        $filePath,
        'Analisis Algoritma Dual-Lens pada Deteksi Orisinalitas',
        'Budi Santoso',
        'ojs_sub_124'
    );

    $docId = $response['data']['document_id'];
    $reportId = $response['data']['report_id'];
    echo "Naskah berhasil diterima! Doc ID: #{$docId}\n";

    // 2. Tunggu hasil komputasi selesai (Polling otomatis tanpa butuh tunnel)
    $result = $connector->waitForCompletion($docId, 90, 3);
    
    $similarity   = $result['data']['overall_similarity'];
    $webReportUrl = $result['data']['web_report_url'];
    $pdfExportUrl = $result['data']['pdf_export_url'];

    echo "Tingkat Kemiripan: {$similarity}%\n";
    echo "Tautan Laporan: {$webReportUrl}\n";
    echo "Unduh PDF Sertifikat: {$pdfExportUrl}\n";

    // 3. Ambil ringkasan Audit Forensik Cerdas AI & Pertanyaan Telaah
    $forensicAudit = $connector->getForensicAudit($reportId);
    if ($forensicAudit) {
        $questions = $connector->getEditorialQuestions($reportId);
        // Pertanyaan telaah ini dapat disisipkan ke formulir tanggapan Review Discussions OJS
    }

} catch (Exception $e) {
    echo "Terjadi kendala: " . $e->getMessage() . "\n";
}
```

#### Opsi B: Memakai PKP Generic Plugin Boilerplate (`HookRegistry` & GUI Settings)
Bagi tim yang ingin menyusun plugin formal berstruktur PKP OJS 3.x lengkap dengan formulir pengaturan GUI:
1. Unduh paket `artilik-ojs-plugin.zip` dari portal AR Tilik atau ekstrak folder `pkp-plugin/artilik`.
2. Letakkan di direktori OJS: `/var/www/ojs/plugins/generic/artilik/`.
3. Struktur berkas yang terpasang:
   * `plugins/generic/artilik/version.xml` (Metadata deskriptor plugin PKP)
   * `plugins/generic/artilik/index.php` (Entry point bootstrap)
   * `plugins/generic/artilik/ArtilikPlugin.inc.php` (Kelas turunan `GenericPlugin`, aksi GUI, dan hook)
   * `plugins/generic/artilik/ArtilikSettingsForm.inc.php` (Form validasi dan penyimpanan pengaturan)
   * `plugins/generic/artilik/templates/settings.tpl` (Template antarmuka GUI pengaturan API Key)
   * `plugins/generic/artilik/ARTilikOJSConnector.php` (SDK klien koneksi)
4. Masuk ke antarmuka web OJS sebagai Journal Manager/Administrator:
   * Buka **Settings > Website > Plugins > Installed Plugins**.
   * Cari **AR Tilik Writing Similarity & AI Forensic Checker**, lalu centang kotak untuk mengaktifkannya.
   * Klik tombol **Settings** (Pengaturan) di bawah nama plugin, masukkan API Key Anda (`artilik_ojs_...`), lalu klik **Simpan**.
5. Daftarkan hook event editorial decision di `ArtilikPlugin.inc.php`:
   ```php
   HookRegistry::register('EditorAction::recordDecision', function($hookName, $args) {
       $submission = $args[0];
       $decision   = $args[1];
       // Panggil ARTilikOJSConnector untuk memproses naskah artikel utama
       return false;
   });
   ```

### 3. Pengujian Mandiri dengan Docker Compose (PKP OJS Containers)
Bagi pengembang yang ingin mensimulasikan instalasi OJS 3.3.x lokal secara cepat dan terisolasi:

> [!NOTE]
> **Catatan Docker PKP:** OJS memerlukan basis data relasional (MariaDB/MySQL) untuk dapat berjalan. Gunakan image resmi PKP `pkpofficial/ojs:stable-3_3_0` bersama container MariaDB menggunakan **Docker Compose**.

Contoh berkas `docker-compose.yml` siap pakai (tersedia pada berkas `docker-compose.example.yml`):
```yaml
version: '3.8'

services:
  ojs-db:
    image: mariadb:10.5
    container_name: ojs-mariadb
    restart: unless-stopped
    environment:
      MYSQL_ROOT_PASSWORD: ojspassword
      MYSQL_DATABASE: ojs
      MYSQL_USER: ojs
      MYSQL_PASSWORD: ojspassword
    volumes:
      - ojs-db-data:/var/lib/mysql

  ojs-app:
    image: pkpofficial/ojs:stable-3_3_0
    container_name: ojs-web
    restart: unless-stopped
    ports:
      - "8080:80"
    environment:
      OJS_DB_HOST: ojs-db
      OJS_DB_USER: ojs
      OJS_DB_PASSWORD: ojspassword
      OJS_DB_NAME: ojs
    volumes:
      - ojs-files-data:/var/www/files
      - ./pkp-plugin/artilik:/var/www/html/plugins/generic/artilik
    depends_on:
      - ojs-db

volumes:
  ojs-db-data:
  ojs-files-data:
```

Langkah pengujian:
```bash
# 1. Jalankan container OJS + MariaDB
docker compose up -d

# 2. Buka browser pada http://localhost:8080
# 3. Masuk ke Settings -> Website -> Plugins -> Installed Plugins -> Aktifkan AR Tilik -> Buka Settings Form!
```

---

## 📡 Spesifikasi REST API Endpoint (v1.3.0)

| Method | Endpoint | Keterangan |
| :--- | :--- | :--- |
| `POST` | `/api/v1/check` | Mengunggah dan menjadwalkan pengecekan naskah |
| `GET` | `/api/v1/status/{id}` | Mengecek status komputasi naskah (pending/processing/completed) |
| `GET` | `/api/v1/report/{id}` | Mengambil data JSON laporan audit lengkap (Dual-Lens, Audit Forensik Cerdas, Pertanyaan Telaah) |
| `GET` | `/api/v1/quota` | Mengecek sisa kuota dan limit API Key |

Header Autentikasi:
```http
X-API-Key: artilik_ojs_YOUR_KEY
Accept: application/json
```

Contoh Payload Data Respon Laporan (`GET /api/v1/report/{id}`):
```json
{
  "status": "success",
  "data": {
    "report_id": 894,
    "document_id": 3182,
    "title": "Analisis Algoritma Dual-Lens",
    "overall_similarity": 11.4,
    "predicted_decision": "no_match",
    "dual_lens": {
      "exact_matcher": { "similarity": 2.1, "matched_words": 42 },
      "tfidf_vectorizer": { "similarity": 9.3, "shared_terms": 128 }
    },
    "forensic_audit": {
      "verdict": "VERIFIED_AUTHENTIC",
      "model": "gemini-flash-lite-latest",
      "confidence_score": 92.5,
      "oral_defense_questions": [
        "Bagaimana Anda memverifikasi konsistensi parameter model pada bab 3 terhadap temuan tabel 4?",
        "Apakah terdapat referensi primer tambahan untuk klaim metodologis pada paragraf 2?"
      ]
    },
    "verification": {
      "code": "ART-8921-X992-K102",
      "certificate_url": "https://tilik.arsetontong.top/verify/ART-8921-X992-K102",
      "pdf_report_url": "https://tilik.arsetontong.top/report/894/export"
    }
  }
}
```
