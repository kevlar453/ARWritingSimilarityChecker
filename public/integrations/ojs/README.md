# Panduan Integrasi AR Tilik untuk Open Journal Systems (OJS 3.x)

> 📕 **DOKUMENTASI RESMI VERSI PDF SIAP UNDUH (v1.3.0)**:  
> Buku panduan lengkap berformat PDF visual resolusi tinggi (8 Halaman) edisi v1.3.0 Production kini telah tersedia. Dilengkapi dengan **diagram alur kerja end-to-end, konsep komputasi Dual-Lens, modul Audit Forensik Cerdas AI, Panduan Pertanyaan Telaah Redaksi, tangkapan layar langkah-demi-langkah, contoh kode PHP siap pakai (`artilik_ojs_runner.php`), spesifikasi REST API publik v1, serta informasi keunggulan & kemitraan institusi AR Tilik**.  
> 👉 **[Unduh Panduan Integrasi OJS 3.x (PDF)](Panduan_Integrasi_OJS_ARTilik.pdf)** *(atau buka via browser: `/integrations/ojs/pdf`)*

Modul ini memungkinkan pengelola jurnal ilmiah di lingkungan kampus yang menggunakan **PKP Open Journal Systems (OJS 3.x)** untuk melakukan pengecekan orisinalitas naskah secara otomatis menggunakan mesin **Dual-Lens Academic Writing Similarity Checker (AR Tilik)** yang dilengkapi penalaran semantik **Audit Forensik Cerdas AI**.

---

## 🚀 Fitur Unggulan Integrasi OJS (Platform v1.3.0)

1. **Pengecekan Otomatis Saat Submisi Naskah**:
   - Menghubungkan hook `SubmissionFile::upload` atau `EditorAction` di OJS agar setiap artikel yang diunggah langsung dievaluasi tanpa langkah manual.
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
   - Menghasilkan bukti keabsahan ber-hash kriptografis SHA-256 dengan tautan verifikasi publik (`https://tilik.arsetontong.top/verify/ART-...`) yang siap dilampirkan pada arsip akreditasi SINTA & ARJUNA.
7. **Unduh PDF Laporan Resmi**:
   - Lampirkan sertifikat PDF langsung ke riwayat peninjauan (*Review Discussions*) OJS untuk mitra bestari dan penulis.

---

## 🛠️ Langkah Instalasi di Server OJS

### 1. Buat API Key di AR Tilik
1. Masuk ke Dashboard AR Tilik (`https://tilik.arsetontong.top/dashboard`).
2. Buka menu **Integrasi API & OJS**.
3. Klik **Buat API Key Baru**, beri nama jurnal Anda (contoh: `Jurnal Teknologi Informasi SINTA 2`).
4. Salin API Key yang dihasilkan (format: `artilik_ojs_...`).

### 2. Salin Library Konektor & Runner
Salin file `ARTilikOJSConnector.php` dan `artilik_ojs_runner.php` ke folder plugins atau direktori custom library OJS Anda:
```bash
cp ARTilikOJSConnector.php /var/www/ojs/plugins/generic/artilik/
cp artilik_ojs_runner.php /var/www/ojs/plugins/generic/artilik/
chmod 644 /var/www/ojs/plugins/generic/artilik/*.php
```

### 3. Menguji Eksekusi dengan Script Runner Mandiri
Anda dapat langsung menguji koneksi dan alur penelaahan naskah melalui baris perintah:
```bash
export ARTILIK_API_KEY="artilik_ojs_YOUR_SECRET_API_KEY_HERE"
php artilik_ojs_runner.php /path/to/manuscript.pdf "Judul Naskah" "Dr. Penulis" "sub_101"
```

### 4. Contoh Penggunaan dalam Script / Hook OJS

```php
require_once __DIR__ . '/ARTilikOJSConnector.php';

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

    // 2. Tunggu hasil komputasi selesai (polling otomatis)
    $result = $connector->waitForCompletion($docId, 60, 3);
    
    $similarity   = $result['data']['overall_similarity'];
    $webReportUrl = $result['data']['web_report_url'];
    $pdfExportUrl = $result['data']['pdf_export_url'];

    echo "Tingkat Kemiripan: {$similarity}%\n";
    echo "Tautan Laporan: {$webReportUrl}\n";
    echo "Unduh PDF Sertifikat: {$pdfExportUrl}\n";

    // 3. Ambil ringkasan Audit Forensik Cerdas AI & Pertanyaan Telaah (v1.3.0)
    $forensicAudit = $connector->getForensicAudit($reportId);
    if ($forensicAudit) {
        echo "Status Audit Forensik: " . $forensicAudit['verdict'] . "\n";
        $questions = $connector->getEditorialQuestions($reportId);
        // Pertanyaan ini dapat otomatis disisipkan ke form tanggapan Reviewer OJS
    }

} catch (Exception $e) {
    echo "Terjadi kendala: " . $e->getMessage() . "\n";
}
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
      "model": "gemini-3.6-flash",
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
