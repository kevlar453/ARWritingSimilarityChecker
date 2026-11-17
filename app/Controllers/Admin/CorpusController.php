<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\DocumentModel;
use App\Libraries\QueueService;

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

        $ext = strtolower($file->getClientExtension());
        $mime = $file->getMimeType() ?: 'application/octet-stream';
        $allowedExts = ['pdf', 'docx', 'txt', 'tex'];
        $allowedMimes = [
            'pdf'  => ['application/pdf', 'application/x-pdf'],
            'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip', 'application/octet-stream'],
            'txt'  => ['text/plain'],
            'tex'  => ['text/x-tex', 'application/x-tex', 'text/plain', 'application/octet-stream']
        ];

        if (! in_array($ext, $allowedExts, true) || ! isset($allowedMimes[$ext]) || ! in_array($mime, $allowedMimes[$ext], true)) {
            return $this->response->setStatusCode(415)->setJSON([
                'status'  => 'error',
                'message' => 'Hanya format PDF, DOCX, TXT, dan LaTeX (.TEX) yang valid dan didukung.'
            ]);
        }

        $storagePath = WRITEPATH . 'documents/corpus/';
        if (! is_dir($storagePath)) {
            mkdir($storagePath, 0775, true);
        }

        $size = $file->getSize();
        $clientName = $file->getClientName();

        $filename = $file->getRandomName();
        try {
            $file->move($storagePath, $filename);
        } catch (\Throwable $e) {
            log_message('error', '[ADMIN CORPUS UPLOAD MOVE FAILED] ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON([
                'status'  => 'error',
                'message' => 'Gagal menyimpan berkas dokumen korpus ke penyimpanan repositori peladen.',
            ]);
        }

        $faculty = trim((string) ($this->request->getPost('faculty') ?? ''));
        $department = trim((string) ($this->request->getPost('department') ?? ''));

        $userId = session()->get('user_id');
        $documentModel = new DocumentModel();
        $docId = $documentModel->insert([
            'user_id'           => $userId ?: null,
            'title'             => pathinfo($clientName, PATHINFO_FILENAME),
            'original_filename' => $clientName,
            'mime_type'         => $mime,
            'file_size'         => $size,
            'storage_path'      => $storagePath . $filename,
            'status'            => 'uploaded',
            'document_type'     => 'corpus',
            'corpus_visibility' => $this->request->getPost('corpus_visibility') ?? 'institution',
            'source_type'       => $this->request->getPost('source_type') ?? 'other',
            'faculty'           => !empty($faculty) ? $faculty : null,
            'department'        => !empty($department) ? $department : null,
            'indexing_status'   => 'pending',
            'created_at'        => date('Y-m-d H:i:s'),
            'updated_at'        => date('Y-m-d H:i:s'),
        ]);

        if ($userId) {
            $userModel = new \App\Models\UserModel();
            $userModel->recordCorpusUpload($userId);
        }

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
        if (! empty($doc['storage_path']) && file_exists($doc['storage_path'])) {
            @unlink($doc['storage_path']);
        }

        $db = db_connect();
        $db->table('report_sources')->where('source_document_id', $id)->delete();
        $db->table('fingerprints')->where('document_id', $id)->delete();
        $db->table('fingerprint_documents')->where('document_id', $id)->delete();
        $db->table('document_chunks')->where('document_id', $id)->delete();
        $db->table('document_pages')->where('document_id', $id)->delete();
        $model->delete($id);

        // Bersihkan vektor semantik Qdrant via antrean background
        try {
            QueueService::dispatch('delete_corpus', ['document_id' => (int) $id]);
        } catch (\Throwable $e) {
            // Failsafe jika koneksi antrean terkendala
        }

        return $this->response->setJSON([
            'status'  => 'success',
            'message' => "Dokumen korpus #{$id} berhasil dihapus."
        ]);
    }

    /**
     * Menjalankan modul Python worker secara aman tanpa pembungkusan escapeshellcmd ganda
     */
    private function executeWorkerModule(string $pythonBin, string $module, array $args = []): string
    {
        $rootPath = escapeshellarg(rtrim(ROOTPATH, '/'));
        $escapedArgs = implode(' ', array_map('escapeshellarg', $args));
        $cmd = "cd {$rootPath} && PYTHONPATH={$rootPath} " . escapeshellcmd($pythonBin) . ' -m ' . escapeshellarg($module) . ($escapedArgs !== '' ? ' ' . $escapedArgs : '') . ' 2>&1';
        return (string) shell_exec($cmd);
    }

    public function syncDropzone()
    {
        $pythonBin = ROOTPATH . '.venv/bin/python';
        $script = ROOTPATH . 'worker/corpus_harvester.py';

        if (! file_exists($pythonBin) || ! file_exists($script)) {
            return $this->response->setStatusCode(500)->setJSON([
                'status'  => 'error',
                'message' => 'Skrip Python harvester tidak ditemukan di lingkungan server.'
            ]);
        }

        $output = $this->executeWorkerModule($pythonBin, 'worker.corpus_harvester', ['--dropzone']);

        $hasError = (strpos($output, 'Traceback') !== false) || (strpos($output, 'Error:') !== false);

        if ($hasError) {
            return $this->response->setStatusCode(500)->setJSON([
                'status'  => 'error',
                'message' => 'Terjadi kendala saat pemindaian dropzone.',
                'output'  => $output
            ]);
        }

        return $this->response->setJSON([
            'status'  => 'success',
            'message' => 'Pemindaian direktori dropzone berhasil dijalankan.',
            'output'  => $output
        ]);
    }

    public function autoDiscover()
    {
        $pythonBin = ROOTPATH . '.venv/bin/python';
        $script = ROOTPATH . 'worker/auto_discovery_harvester.py';

        if (! file_exists($pythonBin) || ! file_exists($script)) {
            return $this->response->setStatusCode(500)->setJSON([
                'status'  => 'error',
                'message' => 'Skrip Python auto discovery tidak ditemukan di lingkungan server.'
            ]);
        }

        $mode = $this->request->getPost('mode') ?: 'query';
        $query = trim($this->request->getPost('query') ?: '');
        $limit = max(1, min((int) ($this->request->getPost('limit') ?: 5), 30));

        if ($mode === 'registry') {
            $output = $this->executeWorkerModule($pythonBin, 'worker.auto_discovery_harvester', ['--registry', '--limit', (string) $limit]);
        } else {
            if (empty($query)) {
                return $this->response->setStatusCode(400)->setJSON([
                    'status'  => 'error',
                    'message' => 'Topik atau kata kunci pencarian karya ilmiah wajib diisi.'
                ]);
            }
            $output = $this->executeWorkerModule($pythonBin, 'worker.auto_discovery_harvester', ['--query', $query, '--limit', (string) $limit]);
        }

        $hasError = (strpos($output, 'Traceback') !== false) || (strpos($output, 'Error:') !== false);

        if ($hasError) {
            return $this->response->setStatusCode(500)->setJSON([
                'status'  => 'error',
                'message' => 'Terjadi kendala saat melakukan pencarian otomatis karya ilmiah.',
                'output'  => $output
            ]);
        }

        return $this->response->setJSON([
            'status'  => 'success',
            'message' => 'Pencarian dan pengindeksan korpus otomatis berhasil dieksekusi.',
            'output'  => $output
        ]);
    }

    public function harvestNeliti()
    {
        $pythonBin = ROOTPATH . '.venv/bin/python';
        if (! file_exists($pythonBin)) {
            $pythonBin = 'python3';
        }
        $query = trim($this->request->getPost('query') ?: '');
        $limit = max(1, min((int) ($this->request->getPost('limit') ?: 10), 50));

        $output = $this->executeWorkerModule($pythonBin, 'worker.national_harvester', ['--source', 'neliti', '--query', $query, '--limit', (string) $limit]);

        return $this->response->setJSON([
            'status'  => 'success',
            'message' => 'Proses pemanenan naskah dari portal Neliti selesai dijalankan.',
            'output'  => $output
        ]);
    }

    public function harvestGaruda()
    {
        $pythonBin = ROOTPATH . '.venv/bin/python';
        if (! file_exists($pythonBin)) {
            $pythonBin = 'python3';
        }
        $query = trim($this->request->getPost('query') ?: 'informatika');
        $limit = max(1, min((int) ($this->request->getPost('limit') ?: 10), 50));

        $output = $this->executeWorkerModule($pythonBin, 'worker.national_harvester', ['--source', 'garuda', '--query', $query, '--limit', (string) $limit]);

        return $this->response->setJSON([
            'status'  => 'success',
            'message' => 'Proses pemanenan jurnal dari GARUDA Dikti selesai dijalankan.',
            'output'  => $output
        ]);
    }

    public function harvestScheduled()
    {
        $pythonBin = ROOTPATH . '.venv/bin/python';
        if (! file_exists($pythonBin)) {
            $pythonBin = 'python3';
        }

        $output = $this->executeWorkerModule($pythonBin, 'worker.national_harvester', ['--scheduled']);

        return $this->response->setJSON([
            'status'  => 'success',
            'message' => 'Pemanenan otomatis terjadwal (Neliti & GARUDA) berhasil dieksekusi.',
            'output'  => $output
        ]);
    }

    public function harvestLogs()
    {
        $db = db_connect();
        $logs = $db->table('harvest_logs')
                   ->orderBy('id', 'DESC')
                   ->limit(30)
                   ->get()
                   ->getResultArray();

        $schedules = $db->table('harvest_schedules')
                        ->orderBy('id', 'ASC')
                        ->get()
                        ->getResultArray();

        return $this->response->setJSON([
            'status'    => 'success',
            'logs'      => $logs,
            'schedules' => $schedules
        ]);
    }

    public function updateHarvestSchedule()
    {
        $id = (int) $this->request->getPost('id');
        $isActive = (int) ($this->request->getPost('is_active') ?? 1);
        $keywords = trim($this->request->getPost('keywords') ?: '');
        $limit = max(1, min((int) ($this->request->getPost('limit_per_run') ?: 15), 100));
        $frequency = $this->request->getPost('frequency') ?: 'daily';

        $db = db_connect();
        $data = [
            'is_active'     => $isActive,
            'limit_per_run' => $limit,
            'frequency'     => $frequency,
            'updated_at'    => date('Y-m-d H:i:s'),
        ];
        if (! empty($keywords)) {
            $data['keywords'] = $keywords;
        }

        $db->table('harvest_schedules')->where('id', $id)->update($data);

        return $this->response->setJSON([
            'status'  => 'success',
            'message' => 'Pengaturan jadwal pemanenan korpus berhasil diperbarui.'
        ]);
    }

    private function queueIndexJob($documentId)
    {
        QueueService::dispatch('index_corpus', ['document_id' => (int) $documentId]);
    }
}
