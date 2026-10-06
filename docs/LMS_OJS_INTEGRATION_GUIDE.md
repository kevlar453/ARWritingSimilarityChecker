# Panduan & Spesifikasi Integrasi AR Tilik: LMS Moodle & Open Journal Systems (OJS 3)

Dokumen ini menyediakan spesifikasi resmi dan contoh implementasi teknis untuk menghubungkan platform pembelajaran daring (Moodle LMS) serta portal penerbitan jurnal ilmiah (OJS 3) dengan mesin penelaah kesepadanan **AR Tilik**.

---

## 1. Arsitektur Alur Integrasi (Single-Call Integration)

```
┌─────────────────────────┐                                 ┌─────────────────────────┐
│     Sistem Mitra        │                                 │        AR Tilik         │
│ (Moodle / OJS 3 Plugin) │                                 │  (Institutional Engine) │
└────────────┬────────────┘                                 └────────────┬────────────┘
             │                                                           │
             │ 1. POST /api/integration/check (Multipart)                │
             │    - document (PDF/DOCX/TXT)                              │
             │    - title (Judul karya ilmiah)                           │
             │    - engine (hybrid / exact / tfidf)                      │
             ├──────────────────────────────────────────────────────────►│
             │                                                           │ 2. Validasi & Ingesti
             │                                                           │ 3. Enqueue Job
             │                                                           │ 4. Generate Certificate Hash
             │ 5. Response 202 Accepted (JSON)                           │
             │    - status: queued                                       │
             │    - report_url, verification_url, pdf_export_url         │
             │◄──────────────────────────────────────────────────────────┤
             │                                                           │
             │ [Polling / Webhook Callback]                              │
             │ 6. GET /api/reports/{report_id}                           │
             ├──────────────────────────────────────────────────────────►│
             │ 7. Status Completed & Evidence Metrics                    │
             │◄──────────────────────────────────────────────────────────┤
```

---

## 2. Spesifikasi Endpoint API

### 2.1. Unggah & Periksa Naskah (`POST /api/integration/check`)
- **Endpoint**: `https://tilik.arsetontong.top/api/integration/check` (atau `http://localhost:8085/api/integration/check`)
- **Content-Type**: `multipart/form-data`
- **Parameter**:
  | Parameter | Tipe | Wajib | Keterangan |
  | :--- | :--- | :---: | :--- |
  | `document` | File | **Ya** | Berkas naskah mahasiswa/artikel (PDF, DOCX, TXT), maks. 25 MB. |
  | `title` | String | Opsional | Judul naskah (jika kosong, menggunakan nama berkas asli). |
  | `engine` | String | Opsional | Algoritma pembanding: `hybrid` (bawaan), `exact`, `tfidf`. |

- **Contoh Response Sukses (`202 Accepted`)**:
```json
{
  "status": "queued",
  "document_id": 2725,
  "report_id": 692,
  "verification_code": "ART-8F2A-4B1C-9D0E",
  "report_url": "https://tilik.arsetontong.top/report/692",
  "verification_url": "https://tilik.arsetontong.top/verify/ART-8F2A-4B1C-9D0E",
  "pdf_export_url": "https://tilik.arsetontong.top/report/692/export",
  "status_api_url": "https://tilik.arsetontong.top/api/reports/692",
  "message": "Pemeriksaan similarity berhasil dijadwalkan ke antrean AR Tilik."
}
```

---

### 2.2. Pemantauan Status Hasil (`GET /api/reports/{id}`)
- **Endpoint**: `https://tilik.arsetontong.top/api/reports/{id}`
- **Response Selesai (`200 OK`)**:
```json
{
  "status": "success",
  "data": {
    "report_id": 692,
    "status": "completed",
    "predicted_decision": "review",
    "overall_similarity": 18.25,
    "source_count": 4,
    "match_count": 12,
    "verification_code": "ART-8F2A-4B1C-9D0E",
    "report_url": "https://tilik.arsetontong.top/report/692",
    "verification_url": "https://tilik.arsetontong.top/verify/ART-8F2A-4B1C-9D0E"
  }
}
```

---

## 3. Contoh Implementasi Adaptor PHP (OJS 3 & Moodle)

Berikut kode modular PHP siap pakai untuk disematkan pada plugin OJS 3 atau event listener Moodle (`assignsubmission_file`):

```php
<?php
/**
 * AR Tilik Client SDK / Integration Helper
 */
class ARTilikClient
{
    private string $baseUrl;

    public function __construct(string $baseUrl = 'https://tilik.arsetontong.top')
    {
        $this->baseUrl = rtrim($baseUrl, '/');
    }

    /**
     * Mengirimkan naskah tugas/artikel ke antrean AR Tilik
     */
    public function submitDocument(string $filePath, string $title = '', string $engine = 'hybrid'): array
    {
        if (!file_exists($filePath)) {
            throw new InvalidArgumentException("Berkas naskah tidak ditemukan: {$filePath}");
        }

        $url = "{$this->baseUrl}/api/integration/check";
        $cfile = new CURLFile($filePath, mime_content_type($filePath), basename($filePath));

        $postFields = [
            'document' => $cfile,
            'title'    => $title ?: basename($filePath),
            'engine'   => $engine
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new RuntimeException("Gagal menghubungi AR Tilik: {$error}");
        }

        $data = json_decode($response, true);
        if ($httpCode !== 202 && $httpCode !== 200) {
            $msg = $data['message'] ?? "HTTP Error {$httpCode}";
            throw new RuntimeException("AR Tilik API Error: {$msg}");
        }

        return $data;
    }

    /**
     * Memeriksa status laporan
     */
    public function getReportStatus(int $reportId): array
    {
        $url = "{$this->baseUrl}/api/reports/{$reportId}";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);

        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode($response, true) ?: [];
    }
}
```

---

## 4. Keuntungan bagi Institusi Kampus
1. **Verifikasi Keaslian Instan (QR Code)**: Sertifikat resmi AR Tilik dapat langsung dipindai oleh dosen penguji atau editor jurnal via tautan `/verify/{hash}`.
2. **Tanpa Biaya Lisensi Komersial**: Sepenuhnya dioperasikan di peladen kampus sendiri dengan basis data korpus mandiri.
3. **Penyelarasan Repositori Otomatis**: Setiap naskah skripsi yang disetujui dapat otomatis didaftarkan menjadi korpus pembanding baru melalui endpoint `/api/documents/{id}/enroll`.
