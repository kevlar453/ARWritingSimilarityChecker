# Panduan Integrasi AR Tilik untuk Open Journal Systems (OJS 3.x)

> 📕 **DOKUMENTASI RESMI VERSI PDF SIAP UNDUH**:  
> Buku panduan lengkap berformat PDF visual resolusi tinggi (8 Halaman) kini telah tersedia. Buku panduan ini dilengkapi dengan **diagram alur kerja end-to-end, konsep komputasi Dual-Lens, tangkapan layar panduan langkah-demi-langkah, contoh kode PHP siap pakai, spesifikasi REST API publik v1, serta informasi keunggulan & kemitraan institusi AR Tilik**.  
> 👉 **[Unduh Panduan Integrasi OJS 3.x (PDF)](Panduan_Integrasi_OJS_ARTilik.pdf)** *(atau buka via browser: `/integrations/ojs/pdf`)*

Modul ini memungkinkan pengelola jurnal ilmiah di lingkungan kampus yang menggunakan **PKP Open Journal Systems (OJS 3.x)** untuk melakukan pengecekan orisinalitas naskah secara otomatis menggunakan mesin **Dual-Lens Academic Writing Similarity Checker (AR Tilik)**.

---

## 🚀 Fitur Integrasi OJS

1. **Pengecekan Otomatis Saat Submisi Naskah**:
   - Menghubungkan hook `SubmissionFile::upload` atau `EditorAction` di OJS agar setiap artikel yang diunggah langsung dievaluasi.
2. **Dual-Lens Evaluation**:
   - Lensa 1 (5-gram exact matching) mendeteksi pasase salin-tempel literal.
   - Lensa 2 (TF-IDF Cosine vector) mendeteksi kemiripan parafrase & konteks istilah.
3. **Sertifikat Keabsahan Digital**:
   - Menghasilkan tautan verifikasi resmi dengan kode QR (`https://tilik.arsetontong.top/verify/ART-...`).
4. **Unduh PDF Laporan Resmi**:
   - Lampirkan sertifikat PDF langsung ke riwayat peninjauan (*Review Discussions*) OJS untuk reviewer dan author.

---

## 🛠️ Langkah Instalasi di Server OJS

### 1. Buat API Key di AR Tilik
1. Masuk ke Dashboard AR Tilik (`https://tilik.arsetontong.top/dashboard`).
2. Buka menu **Integrasi API & OJS**.
3. Klik **Buat API Key Baru**, beri nama jurnal Anda (contoh: `Jurnal Teknologi Informasi SINTA 2`).
4. Salin API Key yang dihasilkan (format: `artilik_ojs_...`).

### 2. Salin Library Konektor
Salin file `ARTilikOJSConnector.php` ke folder plugins atau direktori custom library OJS Anda:
```bash
cp ARTilikOJSConnector.php /var/www/ojs/plugins/generic/artilik/
```

### 3. Contoh Penggunaan dalam Script / Hook OJS

```php
require_once __DIR__ . '/ARTilikOJSConnector.php';

// Inisialisasi konektor dengan API Key Anda
$connector = new ARTilikOJSConnector(
    'artilik_ojs_YOUR_SECRET_API_KEY_HERE',
    'https://tilik.arsetontong.top/api/v1'
);

try {
    // 1. Kirim naskah artikel
    $filePath = '/path/to/ojs/files/journals/1/articles/124/submission/124-1-456-1-SM.pdf';
    $response = $connector->submitManuscript(
        $filePath,
        'Analisis Algoritma Dual-Lens pada Deteksi Orisinalitas',
        'Budi Santoso',
        'ojs_sub_124'
    );

    $docId = $response['data']['document_id'];
    echo "Naskah berhasil diterima! Doc ID: #{$docId}\n";

    // 2. Tunggu hasil komputasi selesai (polling otomatis)
    $result = $connector->waitForCompletion($docId, 60, 3);
    
    $similarity = $result['data']['overall_similarity'];
    $webReportUrl = $result['data']['web_report_url'];
    $pdfExportUrl = $result['data']['pdf_export_url'];

    echo "Tingkat Kemiripan: {$similarity}%\n";
    echo "Tautan Laporan: {$webReportUrl}\n";
    echo "Unduh PDF Sertifikat: {$pdfExportUrl}\n";

} catch (Exception $e) {
    echo "Terjadi kendala: " . $e->getMessage() . "\n";
}
```

---

## 📡 Spesifikasi REST API Endpoint

| Method | Endpoint | Keterangan |
| :--- | :--- | :--- |
| `POST` | `/api/v1/check` | Mengunggah dan menjadwalkan pengecekan naskah |
| `GET` | `/api/v1/status/{id}` | Mengecek status komputasi naskah |
| `GET` | `/api/v1/report/{id}` | Mengambil data JSON laporan audit lengkap |
| `GET` | `/api/v1/quota` | Mengecek sisa kuota dan limit API Key |

Header Autentikasi:
```http
X-API-Key: artilik_ojs_YOUR_KEY
Accept: application/json
```
