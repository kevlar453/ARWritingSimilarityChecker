<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\ApiKeyModel;
use App\Models\DocumentModel;
use App\Models\ReportModel;

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
            $authHeader = $this->request->getGet('api_key');
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
            $clientFilename = $file->getClientName();
            if (empty($title)) {
                $title = pathinfo($clientFilename, PATHINFO_FILENAME);
            }
            $mimeType = $file->getMimeType() ?: 'application/octet-stream';
            $fileSize = $file->getSize();
            $randomName = $file->getRandomName();
            $file->move($storagePath, $randomName);
            $savedFile = $storagePath . $randomName;
        } else {
            // 2. Dukungan Pengiriman Teks Langsung
            $rawText = $this->request->getPost('text') ?: $this->request->getPost('content');
            if (! empty($rawText)) {
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
            return $this->response->setStatusCode(400)->setJSON([
                'status'  => 'error',
                'code'    => 400,
                'message' => 'Naskah tidak ditemukan. Sertakan berkas file (multipart/form-data "document") atau teks (POST "text").'
            ]);
        }

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

        // Buat Report awal berstatus pending
        $reportId = $this->reportModel->insert([
            'document_id'        => $docId,
            'engine'             => 'hybrid',
            'predicted_decision' => 'no_match',
            'status'             => 'pending',
            'created_at'         => date('Y-m-d H:i:s'),
            'updated_at'         => date('Y-m-d H:i:s'),
        ]);

        // Masukkan job antrean worker
        $db = db_connect();
        $db->table('jobs')->insert([
            'type'         => 'similarity_check',
            'payload'      => json_encode([
                'document_id'  => (int) $docId,
                'report_id'    => (int) $reportId,
                'engine'       => 'hybrid',
                'callback_url' => $callbackUrl,
                'external_id'  => $extRef,
                'api_key_id'   => (int) $keyData['id'],
            ]),
            'status'       => 'pending',
            'available_at' => date('Y-m-d H:i:s'),
            'created_at'   => date('Y-m-d H:i:s'),
            'updated_at'   => date('Y-m-d H:i:s'),
        ]);

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
        if (! $doc) {
            return $this->response->setStatusCode(404)->setJSON([
                'status'  => 'error',
                'message' => "Dokumen #{$documentId} tidak ditemukan."
            ]);
        }

        $report = $this->reportModel->where('document_id', $documentId)->orderBy('id', 'DESC')->first();
        $baseUrl = rtrim(base_url(), '/');

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
                'verification_code'  => $report['verification_code'] ?? null,
                'report_url'         => $report ? "{$baseUrl}/api/v1/report/{$report['id']}" : null,
                'web_report_url'     => $report ? "{$baseUrl}/report/{$report['id']}" : null,
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
        $certCode = $report['verification_code'] ?: ('ART-' . strtoupper(substr(md5($report['id'] . $report['created_at']), 0, 12)));

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
                    'pdf_report_url'  => "{$baseUrl}/report/{$reportId}/export",
                    'web_report_url'  => "{$baseUrl}/report/{$reportId}",
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
