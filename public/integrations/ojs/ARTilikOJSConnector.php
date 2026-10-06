<?php
/**
 * AR Tilik - Open Journal Systems (OJS 3.x) Connector
 * 
 * Library integrasi otomatis untuk pengelola Jurnal Ilmiah berbasis PKP OJS 3.x.
 * Memungkinkan naskah submisi penulis (Author Submission) dicek kemiripannya
 * secara otomatis dan melampirkan Sertifikat Keabsahan AR Tilik pada Editorial Decision.
 *
 * Lisensi: MIT
 * Website: https://tilik.arsetontong.top
 */

class ARTilikOJSConnector
{
    private string $apiKey;
    private string $apiEndpoint;
    private int $timeout;

    /**
     * @param string $apiKey API Key yang dibuat di dashboard AR Tilik
     * @param string $apiEndpoint URL dasar instalasi AR Tilik (default: https://tilik.arsetontong.top/api/v1)
     * @param int $timeout Batas waktu koneksi HTTP dalam detik
     */
    public function __construct(
        string $apiKey,
        string $apiEndpoint = 'https://tilik.arsetontong.top/api/v1',
        int $timeout = 60
    ) {
        $this->apiKey = trim($apiKey);
        $this->apiEndpoint = rtrim($apiEndpoint, '/');
        $this->timeout = $timeout;
    }

    /**
     * Mengirim naskah submisi OJS ke mesin AR Tilik
     * 
     * @param string $filePath Path berkas PDF/DOCX naskah di server OJS
     * @param string $title Judul naskah artikel
     * @param string $author Nama penulis primer
     * @param string $ojsSubmissionId ID Submisi OJS (contoh: 'submission_4512')
     * @param string|null $callbackWebhookUrl URL Webhook OJS saat pengecekan selesai (opsional)
     * @return array Hasil respon API
     * @throws Exception
     */
    public function submitManuscript(
        string $filePath,
        string $title,
        string $author = '',
        string $ojsSubmissionId = '',
        ?string $callbackWebhookUrl = null
    ): array {
        if (! file_exists($filePath)) {
            throw new InvalidArgumentException("Berkas naskah tidak ditemukan: {$filePath}");
        }

        $url = "{$this->apiEndpoint}/check";
        $cFile = new CURLFile(
            realpath($filePath),
            mime_content_type($filePath) ?: 'application/pdf',
            basename($filePath)
        );

        $postData = [
            'document'          => $cFile,
            'title'             => $title,
            'author'            => $author,
            'ojs_submission_id' => $ojsSubmissionId,
        ];

        if ($callbackWebhookUrl) {
            $postData['callback_url'] = $callbackWebhookUrl;
        }

        return $this->executeCurl($url, 'POST', $postData, true);
    }

    /**
     * Mengecek status pemrosesan naskah di AR Tilik
     * 
     * @param int $documentId ID Dokumen yang diperoleh dari submitManuscript()
     * @return array Status dan skor kemiripan jika sudah selesai
     * @throws Exception
     */
    public function checkStatus(int $documentId): array
    {
        $url = "{$this->apiEndpoint}/status/{$documentId}";
        return $this->executeCurl($url, 'GET');
    }

    /**
     * Mengambil laporan kemiripan lengkap (Dual-Lens Analysis)
     * 
     * @param int $reportId ID Laporan AR Tilik
     * @return array Data laporan kemiripan
     * @throws Exception
     */
    public function getDetailedReport(int $reportId): array
    {
        $url = "{$this->apiEndpoint}/report/{$reportId}";
        return $this->executeCurl($url, 'GET');
    }

    /**
     * Menunggu hingga hasil selesai dihitung (Polling Helper)
     * 
     * @param int $documentId ID Dokumen
     * @param int $maxWaitSeconds Batas waktu tunggu maksimal (detik)
     * @param int $pollInterval Jarak interval pengecekan (detik)
     * @return array Hasil status akhir
     * @throws Exception
     */
    public function waitForCompletion(int $documentId, int $maxWaitSeconds = 90, int $pollInterval = 3): array
    {
        $startTime = time();
        while (time() - $startTime < $maxWaitSeconds) {
            $statusData = $this->checkStatus($documentId);
            $docStatus = $statusData['data']['status'] ?? 'pending';

            if ($docStatus === 'completed' || $docStatus === 'failed') {
                return $statusData;
            }

            sleep($pollInterval);
        }

        throw new RuntimeException("Waktu tunggu pengecekan naskah habis ({$maxWaitSeconds} detik).");
    }

    /**
     * Mengecek sisa kuota API Key
     * 
     * @return array Status kuota
     * @throws Exception
     */
    public function getQuota(): array
    {
        $url = "{$this->apiEndpoint}/quota";
        return $this->executeCurl($url, 'GET');
    }

    /**
     * Eksekutor cURL
     */
    private function executeCurl(string $url, string $method = 'GET', array $data = [], bool $isMultipart = false): array
    {
        $ch = curl_init();

        $headers = [
            "X-API-Key: {$this->apiKey}",
            "Accept: application/json"
        ];

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            if (! $isMultipart) {
                $headers[] = 'Content-Type: application/json';
            }
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            throw new RuntimeException("Koneksi ke server AR Tilik gagal: {$curlError}");
        }

        $decoded = json_decode($response, true);
        if ($httpCode >= 400) {
            $msg = $decoded['message'] ?? "HTTP Galat {$httpCode}";
            throw new RuntimeException("API AR Tilik Error [{$httpCode}]: {$msg}");
        }

        return $decoded ?: ['raw' => $response];
    }
}
