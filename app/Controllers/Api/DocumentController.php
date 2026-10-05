<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\DocumentModel;
use App\Models\ReportModel;

class DocumentController extends BaseController
{
    public function create()
    {
        $file = $this->request->getFile('document');
        if (! $file || ! $file->isValid()) {
            return $this->response->setStatusCode(400)->setJSON([
                'status'  => 'error',
                'message' => 'File tidak valid atau belum diunggah.'
            ]);
        }

        $allowed = [
            'application/pdf',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'text/plain'
        ];

        $mime = $file->getMimeType();
        if (! in_array($mime, $allowed, true) && ! in_array($file->getClientExtension(), ['pdf', 'docx', 'txt'], true)) {
            return $this->response->setStatusCode(415)->setJSON([
                'status'  => 'error',
                'message' => 'Hanya format PDF, DOCX, dan TXT yang didukung.'
            ]);
        }

        if ($file->getSize() > 25 * 1024 * 1024) {
            return $this->response->setStatusCode(413)->setJSON([
                'status'  => 'error',
                'message' => 'Ukuran file maksimal adalah 25 MB.'
            ]);
        }

        $storagePath = WRITEPATH . 'documents/';
        if (! is_dir($storagePath)) {
            mkdir($storagePath, 0775, true);
        }

        $filename = $file->getRandomName();
        $file->move($storagePath, $filename);

        $documentModel = new DocumentModel();
        $documentId = $documentModel->insert([
            'user_id'           => null,
            'title'             => pathinfo($file->getClientName(), PATHINFO_FILENAME),
            'original_filename' => $file->getClientName(),
            'mime_type'         => $mime,
            'file_size'         => $file->getSize(),
            'storage_path'      => $storagePath . $filename,
            'status'            => 'uploaded',
            'document_type'     => 'submission',
            'corpus_visibility' => $this->request->getPost('corpus_visibility') ?? 'private',
            'indexing_status'   => 'not_required',
            'created_at'        => date('Y-m-d H:i:s'),
            'updated_at'        => date('Y-m-d H:i:s'),
        ]);

        return $this->response->setStatusCode(201)->setJSON([
            'status'      => 'success',
            'document_id' => $documentId,
            'filename'    => $file->getClientName(),
            'message'     => 'Dokumen berhasil diunggah.'
        ]);
    }

    public function show($id)
    {
        $documentModel = new DocumentModel();
        $document = $documentModel->find($id);

        if (! $document) {
            return $this->response->setStatusCode(404)->setJSON([
                'status'  => 'error',
                'message' => 'Dokumen tidak ditemukan.'
            ]);
        }

        // Ambil laporan terbaru jika ada
        $reportModel = new ReportModel();
        $report = $reportModel->where('document_id', $id)->orderBy('id', 'DESC')->first();

        return $this->response->setJSON([
            'status'   => 'success',
            'data'     => $document,
            'report'   => $report
        ]);
    }

    public function check($id)
    {
        $documentModel = new DocumentModel();
        $document = $documentModel->find($id);

        if (! $document) {
            return $this->response->setStatusCode(404)->setJSON([
                'status'  => 'error',
                'message' => 'Dokumen tidak ditemukan.'
            ]);
        }

        $engine = $this->request->getPost('engine') ?: 'hybrid';

        $reportModel = new ReportModel();
        $reportId = $reportModel->insert([
            'document_id'        => $id,
            'engine'             => $engine,
            'predicted_decision' => 'no_match',
            'overall_similarity' => 0,
            'source_count'       => 0,
            'match_count'        => 0,
            'status'             => 'pending',
            'created_at'         => date('Y-m-d H:i:s'),
        ]);

        $db = db_connect();
        $db->table('jobs')->insert([
            'type'         => 'similarity_check',
            'payload'      => json_encode([
                'document_id' => (int) $id,
                'report_id'   => (int) $reportId,
                'engine'      => $engine,
            ]),
            'status'       => 'pending',
            'available_at' => date('Y-m-d H:i:s'),
            'created_at'   => date('Y-m-d H:i:s'),
            'updated_at'   => date('Y-m-d H:i:s'),
        ]);

        return $this->response->setStatusCode(202)->setJSON([
            'status'      => 'queued',
            'document_id' => (int) $id,
            'report_id'   => (int) $reportId,
            'message'     => 'Pemeriksaan similarity berhasil dijadwalkan ke antrean worker.'
        ]);
    }

    public function enroll($id)
    {
        $documentModel = new DocumentModel();
        $document = $documentModel->find($id);

        if (! $document) {
            return $this->response->setStatusCode(404)->setJSON([
                'status'  => 'error',
                'message' => 'Dokumen tidak ditemukan.'
            ]);
        }

        if ($document['document_type'] === 'corpus') {
            return $this->response->setJSON([
                'status'  => 'already_enrolled',
                'message' => 'Dokumen ini sudah terdaftar sebagai korpus institusi.'
            ]);
        }

        $db = db_connect();
        $db->transStart();

        // 1. Daftarkan fingerprints unik naskah ke fingerprint_documents
        $db->query("
            INSERT IGNORE INTO fingerprint_documents (fingerprint, document_id, created_at)
            SELECT DISTINCT fingerprint, ?, NOW()
            FROM fingerprints
            WHERE document_id = ?
        ", [(int)$id, (int)$id]);

        // 2. Perbarui status dokumen menjadi corpus institusi
        $documentModel->update($id, [
            'document_type'     => 'corpus',
            'corpus_visibility' => 'institution',
            'indexing_status'   => 'completed',
            'updated_at'        => date('Y-m-d H:i:s')
        ]);

        // 3. Perbarui fingerprint_stats untuk menghitung ulang DF/IDF korpus
        $db->query("
            INSERT INTO fingerprint_stats (fingerprint, document_frequency, created_at, updated_at)
            SELECT fd.fingerprint, COUNT(DISTINCT fd.document_id), NOW(), NOW()
            FROM fingerprint_documents fd
            INNER JOIN documents d ON d.id = fd.document_id
            WHERE d.document_type = 'corpus'
              AND (d.benchmark_run_id IS NULL OR d.benchmark_run_id = 0)
            GROUP BY fd.fingerprint
            ON DUPLICATE KEY UPDATE
                document_frequency = VALUES(document_frequency),
                updated_at = NOW()
        ");

        $db->transComplete();

        if ($db->transStatus() === false) {
            return $this->response->setStatusCode(500)->setJSON([
                'status'  => 'error',
                'message' => 'Gagal mendaftarkan naskah ke korpus institusi.'
            ]);
        }

        return $this->response->setJSON([
            'status'  => 'success',
            'message' => "Naskah '{$document['title']}' berhasil didaftarkan sebagai Korpus Institusi permanen."
        ]);
    }
}
