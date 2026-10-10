<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\ApiKeyModel;
use App\Models\DocumentModel;
use App\Models\ReportModel;
use App\Libraries\QueueService;

class PublicApiController extends BaseController
{
    protected ApiKeyModel $apiKeyModel;
    protected DocumentModel $documentModel;
    protected ReportModel $reportModel;

    public function __construct()
    {
        $this->apiKeyModel   = new ApiKeyModel();
        $this->documentModel = new DocumentModel();
        $this->reportModel   = new ReportModel();
    }

    /**
     * Memvalidasi API Key dari Header atau Query Parameter
     */
    protected function authenticate(): ?array
    {
        $authHeader = $this->request->getHeaderLine('X-API-Key');
        if (empty($authHeader)) {
            $bearer = $this->request->getHeaderLine('Authorization');
            if (preg_match('/Bearer\s+(\S+)/i', $bearer, $matches)) {
                $authHeader = $matches[1];
            }
        }

        if (empty($authHeader)) {
            return null;
        }

        return $this->apiKeyModel->validateKey($authHeader);
    }

    /**
     * POST /api/v1/check
     * Menerima naskah artikel ilmiah dari OJS atau sistem eksternal untuk dicek
     */
    public function checkSubmission()
    {
        $keyData = $this->authenticate();
        if (! $keyData) {
            return $this->response->setStatusCode(401)->setJSON([
                'status'  => 'error',
                'code'    => 401,
                'message' => 'Autentikasi gagal. Sertakan API Key yang valid pada header X-API-Key atau Authorization: Bearer <token>.'
            ]);
        }

        if (! empty($keyData['quota_exceeded'])) {
            return $this->response->setStatusCode(429)->setJSON([
                'status'  => 'error',
                'code'    => 429,
                'message' => 'Batas kuota API Key ini telah habis. Silakan hubungi admin atau lakukan upgrade kuota.'
            ]);
        }

        $userId = (int) $keyData['user_id'];
        $title  = trim($this->request->getPost('title') ?: '');
        $author = trim($this->request->getPost('author') ?: '');
        $extRef = trim($this->request->getPost('external_id') ?: $this->request->getPost('ojs_submission_id') ?: '');
        $callbackUrl = trim($this->request->getPost('callback_url') ?: '');

        $storagePath = WRITEPATH . 'documents/submissions/';
        if (! is_dir($storagePath)) {
            mkdir($storagePath, 0775, true);
        }

        $savedFile = null;
        $mimeType = 'text/plain';
        $fileSize = 0;
        $clientFilename = '';

        // 1. Dukungan Unggah Berkas File (PDF, DOCX, TXT)
        $file = $this->request->getFile('document') ?: $this->request->getFile('file');
        if ($file && $file->isValid()) {
            $ext = strtolower($file->getClientExtension());
            $rawMime = $file->getMimeType();
            $allowedExts = ['pdf', 'docx', 'txt'];
            $allowedMimes = [
                'pdf'  => ['application/pdf', 'application/x-pdf'],
                'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip', 'application/octet-stream'],
                'txt'  => ['text/plain']
            ];
            if (! in_array($ext, $allowedExts, true) || ! isset($allowedMimes[$ext]) || ! in_array($rawMime, $allowedMimes[$ext], true)) {
                return $this->response->setStatusCode(415)->setJSON([
                    'status'  => 'error',
                    'code'    => 415,
                    'message' => 'Format berkas tidak didukung. Hanya file PDF, DOCX, dan TXT yang diperbolehkan.'
                ]);
            }
            if ($file->getSize() > 25 * 1024 * 1024) {
                return $this->response->setStatusCode(413)->setJSON([
                    'status'  => 'error',
                    'code'    => 413,
                    'message' => 'Ukuran berkas melebihi batas maksimal 25 MB.'
                ]);
            }

            // Periksa & potong kuota submisi akun pengguna pemilik kunci
            $userModel = new \App\Models\UserModel();
            $quotaDeducted = $userModel->deductSubmissionQuota($userId);
            if (! $quotaDeducted) {
                return $this->response->setStatusCode(403)->setJSON([
                    'status'  => 'error',
                    'code'    => 403,
                    'message' => 'Kuota submisi akun Anda tidak mencukupi untuk memproses naskah ini.'
                ]);
            }

            $clientFilename = $file->getClientName();
            if (empty($title)) {
                $title = pathinfo($clientFilename, PATHINFO_FILENAME);
            }
            $mimeType = $rawMime ?: 'application/octet-stream';
            $fileSize = $file->getSize();
            $randomName = $file->getRandomName();
            $file->move($storagePath, $randomName);
            $savedFile = $storagePath . $randomName;
        } else {
            // 2. Dukungan Pengiriman Teks Langsung
            $rawText = $this->request->getPost('text') ?: $this->request->getPost('content');
            if (! empty($rawText)) {
                // Periksa & potong kuota submisi akun pengguna pemilik kunci
                $userModel = new \App\Models\UserModel();
                $quotaDeducted = $userModel->deductSubmissionQuota($userId);
                if (! $quotaDeducted) {
                    return $this->response->setStatusCode(403)->setJSON([
                        'status'  => 'error',
                        'code'    => 403,
                        'message' => 'Kuota submisi akun Anda tidak mencukupi untuk memproses naskah ini.'
                    ]);
                }

                if (empty($title)) {
                    $title = 'Naskah Integrasi API ' . date('Y-m-d H:i');
                }
                $clientFilename = 'api_text_' . bin2hex(random_bytes(6)) . '.txt';
                $savedFile = $storagePath . $clientFilename;
                file_put_contents($savedFile, $rawText);
                $fileSize = filesize($savedFile);
                $mimeType = 'text/plain';
            }
        }

        if (! $savedFile || ! file_exists($savedFile)) {
            $userModel->refundSubmissionQuota($userId);
            return $this->response->setStatusCode(400)->setJSON([
                'status'  => 'error',
                'code'    => 400,
                'message' => 'Naskah tidak ditemukan. Sertakan berkas file (multipart/form-data "document") atau teks (POST "text"). Kuota submisi telah dipulihkan.'
            ]);
        }

        try {
            $faculty = trim((string) ($this->request->getPost('faculty') ?? ''));
            $department = trim((string) ($this->request->getPost('department') ?? ''));

            // Simpan ke tabel documents
            $docId = $this->documentModel->insert([
                'user_id'           => $userId,
                'title'             => $title,
                'original_filename' => $clientFilename,
                'mime_type'         => $mimeType,
                'file_size'         => $fileSize,
                'storage_path'      => $savedFile,
                'status'            => 'uploaded',
                'document_type'     => 'submission',
                'corpus_visibility' => 'private',
                'source_type'       => ($keyData['system_type'] === 'ojs') ? 'journal' : 'other',
                'faculty'           => !empty($faculty) ? $faculty : null,
                'department'        => !empty($department) ? $department : null,
                'indexing_status'   => 'not_required',
                'created_at'        => date('Y-m-d H:i:s'),
                'updated_at'        => date('Y-m-d H:i:s'),
            ]);

            // Buat kode verifikasi sertifikat acak aman (12 karakter hex acak kriptografis)
            $rnd = strtoupper(bin2hex(random_bytes(6)));
            $verificationCode = "ART-" . substr($rnd, 0, 4) . "-" . substr($rnd, 4, 4) . "-" . substr($rnd, 8, 4);

            // Buat Report awal berstatus pending
            $reportId = $this->reportModel->insert([
                'document_id'        => $docId,
                'engine'             => 'hybrid',
                'predicted_decision' => 'no_match',
                'verification_code'  => $verificationCode,
                'status'             => 'pending',
                'created_at'         => date('Y-m-d H:i:s'),
                'updated_at'         => date('Y-m-d H:i:s'),
            ]);

            // Masukkan job antrean worker via QueueService (Redis event-driven + MariaDB fallback)
            QueueService::dispatch('similarity_check', [
                'document_id'  => (int) $docId,
                'report_id'    => (int) $reportId,
                'engine'       => 'hybrid',
                'callback_url' => $callbackUrl,
                'external_id'  => $extRef,
                'api_key_id'   => (int) $keyData['id'],
            ]);
        } catch (\Throwable $e) {
            $userModel->refundSubmissionQuota($userId);
            log_message('error', 'PublicApiController checkSubmission error: ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON([
                'status'  => 'error',
                'code'    => 500,
                'message' => 'Terjadi kesalahan sistem saat menjadwalkan pemeriksaan. Kuota submisi Anda telah dipulihkan.'
            ]);
        }

        // Catat penggunaan kuota API Key
        $this->apiKeyModel->recordUsage((int) $keyData['id']);

        $baseUrl = rtrim(base_url(), '/');

        return $this->response->setStatusCode(202)->setJSON([
            'status'      => 'accepted',
            'message'     => 'Naskah berhasil diterima dan sedang diproses oleh Mesin Dual-Lens AR Tilik.',
            'data'        => [
                'document_id'            => (int) $docId,
                'report_id'              => (int) $reportId,
                'title'                  => $title,
                'external_id'            => $extRef ?: null,
                'processing_status'      => 'pending',
                'status_url'             => "{$baseUrl}/api/v1/status/{$docId}",
                'report_url'             => "{$baseUrl}/api/v1/report/{$reportId}",
                'web_view_url'           => "{$baseUrl}/report/{$reportId}",
                'estimated_wait_seconds' => 10,
            ]
        ]);
    }

    /**
     * GET /api/v1/status/(:num)
     * Mengecek status progres komputasi dokumen (pending -> processing -> completed)
     */
    public function getStatus($documentId)
    {
        $keyData = $this->authenticate();
        if (! $keyData) {
            return $this->response->setStatusCode(401)->setJSON([
                'status'  => 'error',
                'message' => 'Autentikasi gagal. Sertakan API Key yang valid.'
            ]);
        }

        $doc = $this->documentModel->find($documentId);
        if (! $doc || ((int) $doc['user_id'] !== (int) $keyData['user_id'])) {
            return $this->response->setStatusCode(404)->setJSON([
                'status'  => 'error',
                'message' => "Dokumen #{$documentId} tidak ditemukan."
            ]);
        }

        $report = $this->reportModel->where('document_id', $documentId)->orderBy('id', 'DESC')->first();
        $baseUrl = rtrim(base_url(), '/');
        $certCode = $report['verification_code'] ?? '';

        return $this->response->setJSON([
            'status' => 'success',
            'data'   => [
                'document_id'        => (int) $doc['id'],
                'title'              => $doc['title'],
                'status'             => $doc['status'],
                'report_id'          => $report ? (int) $report['id'] : null,
                'report_status'      => $report['status'] ?? 'pending',
                'overall_similarity' => $report ? (float) $report['overall_similarity'] : 0.0,
                'predicted_decision' => $report['predicted_decision'] ?? null,
                'verification_code'  => $certCode ?: null,
                'report_url'         => $report ? "{$baseUrl}/api/v1/report/{$report['id']}" : null,
                'web_report_url'     => $report ? "{$baseUrl}/report/{$report['id']}" . ($certCode ? "?token={$certCode}" : "") : null,
                'pdf_export_url'     => $report ? "{$baseUrl}/report/{$report['id']}/export" : null,
            ]
        ]);
    }

    /**
     * GET /api/v1/report/(:num)
     * Mengambil struktur JSON lengkap dari hasil audit kemiripan naskah
     */
    public function getReport($reportId)
    {
        $keyData = $this->authenticate();
        if (! $keyData) {
            return $this->response->setStatusCode(401)->setJSON([
                'status'  => 'error',
                'message' => 'Autentikasi gagal. Sertakan API Key yang valid.'
            ]);
        }

        $report = $this->reportModel->find($reportId);
        if (! $report) {
            return $this->response->setStatusCode(404)->setJSON([
                'status'  => 'error',
                'message' => "Laporan #{$reportId} tidak ditemukan."
            ]);
        }

        $doc = $this->documentModel->find($report['document_id']);
        if (! $doc || ((int) $doc['user_id'] !== (int) $keyData['user_id'])) {
            return $this->response->setStatusCode(404)->setJSON([
                'status'  => 'error',
                'message' => "Laporan #{$reportId} tidak ditemukan."
            ]);
        }

        $db = db_connect();

        $sources = $db->table('report_sources rs')
                      ->select('rs.*, d.title as source_title, d.source_type')
                      ->join('documents d', 'd.id = rs.source_document_id', 'left')
                      ->where('rs.report_id', $reportId)
                      ->orderBy('rs.source_rank', 'ASC')
                      ->limit(10)
                      ->get()
                      ->getResultArray();

        $baseUrl = rtrim(base_url(), '/');
        $certCode = $report['verification_code'] ?? '';
        if (empty($certCode)) {
            $rnd = strtoupper(bin2hex(random_bytes(6)));
            $certCode = "ART-" . substr($rnd, 0, 4) . "-" . substr($rnd, 4, 4) . "-" . substr($rnd, 8, 4);
            $this->reportModel->update($reportId, ['verification_code' => $certCode]);
        }

        $evidenceData = [];
        if (! empty($report['evidence_data'])) {
            $evidenceData = is_string($report['evidence_data']) ? json_decode($report['evidence_data'], true) : $report['evidence_data'];
        }
        $gemini = $evidenceData['gemini_forensic'] ?? $evidenceData['v1_1_presentation']['v1_2_evolution']['gemini_forensic'] ?? null;
        $forensicPayload = null;
        if (! empty($gemini) && is_array($gemini)) {
            $forensicPayload = [
                'status'                 => $gemini['status'] ?? 'completed',
                'model'                  => $gemini['model'] ?? 'gemini-3.6-flash',
                'verdict'                => $gemini['verdict'] ?? null,
                'summary'                => $gemini['summary'] ?? null,
                'confidence_score'       => $gemini['confidence_score'] ?? null,
                'recommendations'        => $gemini['recommendations'] ?? [],
                'oral_defense_questions' => $gemini['oral_defense_questions'] ?? [],
            ];
        }

        return $this->response->setJSON([
            'status' => 'success',
            'data'   => [
                'report_id'          => (int) $report['id'],
                'document_id'        => (int) $report['document_id'],
                'title'              => $doc['title'] ?? 'Naskah Akademik',
                'overall_similarity' => (float) $report['overall_similarity'],
                'predicted_decision' => $report['predicted_decision'],
                'evidence_pattern'   => $report['evidence_pattern'],
                'verification'       => [
                    'code'            => $certCode,
                    'certificate_url' => "{$baseUrl}/verify/{$certCode}",
                    'pdf_report_url'  => "{$baseUrl}/report/{$reportId}/export?token={$certCode}",
                    'web_report_url'  => "{$baseUrl}/report/{$reportId}?token={$certCode}",
                ],
                'dual_lens'          => [
                    'exact_matcher' => [
                        'similarity'    => (float) ($report['exact_similarity'] ?? 0),
                        'matched_words' => (int) ($report['longest_match_words'] ?? 0),
                        'method'        => '5-gram Sequential Shingling',
                    ],
                    'tfidf_vectorizer' => [
                        'similarity'   => (float) ($report['cosine_similarity'] ?? 0),
                        'shared_terms' => (int) ($report['shared_terms_count'] ?? 0),
                        'average_idf'  => (float) ($report['average_shared_term_idf'] ?? 0),
                        'method'       => 'Sparse Cosine Vector Space',
                    ],
                ],
                'forensic_audit'     => $forensicPayload,
                'sources_count'      => count($sources),
                'top_sources'        => array_map(function ($s) {
                    return [
                        'rank'        => (int) ($s['source_rank'] ?? 1),
                        'document_id' => (int) $s['source_document_id'],
                        'title'       => $s['source_title'] ?? ('Rujukan #' . $s['source_document_id']),
                        'type'        => $s['source_type'] ?? 'corpus',
                        'similarity'  => (float) ($s['exact_similarity'] ?? $s['similarity'] ?? 0),
                    ];
                }, $sources),
                'created_at'         => $report['created_at'],
                'completed_at'       => $report['completed_at'],
            ]
        ]);
    }

    /**
     * GET /api/v1/quota
     * Mengembalikan informasi kuota API Key pemohon
     */
    public function getQuota()
    {
        $keyData = $this->authenticate();
        if (! $keyData) {
            return $this->response->setStatusCode(401)->setJSON([
                'status'  => 'error',
                'message' => 'Autentikasi gagal. Sertakan API Key yang valid.'
            ]);
        }

        $quotaLimit = (int) $keyData['quota_limit'];
        $quotaUsed  = (int) $keyData['quota_used'];
        $remaining  = max(0, $quotaLimit - $quotaUsed);

        return $this->response->setJSON([
            'status' => 'success',
            'data'   => [
                'key_name'              => $keyData['key_name'],
                'key_prefix'            => $keyData['key_prefix'],
                'system_type'           => $keyData['system_type'],
                'quota_limit'           => $quotaLimit,
                'quota_used'            => $quotaUsed,
                'quota_remaining'       => $remaining,
                'rate_limit_per_minute' => (int) $keyData['rate_limit_per_minute'],
                'is_active'             => (bool) $keyData['is_active'],
                'last_used_at'          => $keyData['last_used_at'],
            ]
        ]);
    }
}
