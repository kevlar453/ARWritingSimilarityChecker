<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\DocumentModel;

class CorpusController extends BaseController
{
    public function index()
    {
        $model = new DocumentModel();
        $corpusDocs = $model->where('document_type', 'corpus')
                            ->orderBy('id', 'DESC')
                            ->findAll();

        return $this->response->setJSON([
            'status' => 'success',
            'data'   => $corpusDocs
        ]);
    }

    public function create()
    {
        $file = $this->request->getFile('document');
        if (! $file || ! $file->isValid()) {
            return $this->response->setStatusCode(400)->setJSON([
                'status'  => 'error',
                'message' => 'File dokumen korpus tidak valid.'
            ]);
        }

        $storagePath = WRITEPATH . 'documents/corpus/';
        if (! is_dir($storagePath)) {
            mkdir($storagePath, 0775, true);
        }

        $mime = $file->getMimeType() ?: 'text/plain';
        $size = $file->getSize();
        $clientName = $file->getClientName();

        $filename = $file->getRandomName();
        $file->move($storagePath, $filename);

        $documentModel = new DocumentModel();
        $docId = $documentModel->insert([
            'user_id'           => null,
            'title'             => pathinfo($clientName, PATHINFO_FILENAME),
            'original_filename' => $clientName,
            'mime_type'         => $mime,
            'file_size'         => $size,
            'storage_path'      => $storagePath . $filename,
            'status'            => 'uploaded',
            'document_type'     => 'corpus',
            'corpus_visibility' => $this->request->getPost('corpus_visibility') ?? 'institution',
            'source_type'       => $this->request->getPost('source_type') ?? 'other',
            'indexing_status'   => 'pending',
            'created_at'        => date('Y-m-d H:i:s'),
            'updated_at'        => date('Y-m-d H:i:s'),
        ]);

        $this->queueIndexJob($docId);

        return $this->response->setStatusCode(202)->setJSON([
            'status'      => 'indexing',
            'document_id' => $docId,
            'message'     => 'Dokumen korpus berhasil diunggah dan dijadwalkan untuk pengindeksan.'
        ]);
    }

    public function show($id)
    {
        $model = new DocumentModel();
        $doc = $model->where('document_type', 'corpus')->find($id);

        if (! $doc) {
            return $this->response->setStatusCode(404)->setJSON([
                'status'  => 'error',
                'message' => 'Dokumen korpus tidak ditemukan.'
            ]);
        }

        return $this->response->setJSON([
            'status' => 'success',
            'data'   => $doc
        ]);
    }

    public function reindex($id)
    {
        $model = new DocumentModel();
        $doc = $model->where('document_type', 'corpus')->find($id);

        if (! $doc) {
            return $this->response->setStatusCode(404)->setJSON([
                'status'  => 'error',
                'message' => 'Dokumen korpus tidak ditemukan.'
            ]);
        }

        $model->update($id, [
            'indexing_status' => 'pending',
            'updated_at'      => date('Y-m-d H:i:s')
        ]);

        $this->queueIndexJob($id);

        return $this->response->setJSON([
            'status'  => 'success',
            'message' => "Pengindeksan ulang untuk dokumen #{$id} berhasil dijadwalkan."
        ]);
    }

    public function delete($id)
    {
        $model = new DocumentModel();
        $doc = $model->where('document_type', 'corpus')->find($id);

        if (! $doc) {
            return $this->response->setStatusCode(404)->setJSON([
                'status'  => 'error',
                'message' => 'Dokumen korpus tidak ditemukan.'
            ]);
        }

        // Hapus file fisik jika ada
        if (file_exists($doc['storage_path'])) {
            unlink($doc['storage_path']);
        }

        $db = db_connect();
        $db->table('fingerprints')->where('document_id', $id)->delete();
        $db->table('fingerprint_documents')->where('document_id', $id)->delete();
        $db->table('document_chunks')->where('document_id', $id)->delete();
        $db->table('document_pages')->where('document_id', $id)->delete();
        $model->delete($id);

        return $this->response->setJSON([
            'status'  => 'success',
            'message' => "Dokumen korpus #{$id} berhasil dihapus."
        ]);
    }

    private function queueIndexJob($documentId)
    {
        $db = db_connect();
        $db->table('jobs')->insert([
            'type'         => 'index_corpus',
            'payload'      => json_encode(['document_id' => (int) $documentId]),
            'status'       => 'pending',
            'available_at' => date('Y-m-d H:i:s'),
            'created_at'   => date('Y-m-d H:i:s'),
            'updated_at'   => date('Y-m-d H:i:s'),
        ]);
    }
}
