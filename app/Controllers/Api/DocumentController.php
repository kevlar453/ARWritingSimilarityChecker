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
}
