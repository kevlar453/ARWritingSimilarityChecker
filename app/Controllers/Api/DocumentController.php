<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\DocumentModel;
use App\Models\ReportModel;
use App\Libraries\QueueService;

class DocumentController extends BaseController
{
    public function create()
    {
        $userId = session()->get('user_id');

        if (! $userId) {
            return $this->response->setStatusCode(401)->setJSON([
                'status'  => 'unauthorized',
                'message' => 'Silakan masuk atau daftar akun gratis terlebih dahulu untuk mengunggah naskah karya ilmiah.'
            ]);
        }

        $file = $this->request->getFile('document');
        if (! $file || ! $file->isValid()) {
            return $this->response->setStatusCode(400)->setJSON([
                'status'  => 'error',
                'message' => 'File tidak valid atau belum diunggah.'
            ]);
        }

        $ext = strtolower($file->getClientExtension());
        $mime = $file->getMimeType();
        $allowedExts = ['pdf', 'docx', 'txt'];
        $allowedMimes = [
            'pdf'  => ['application/pdf', 'application/x-pdf'],
            'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip', 'application/octet-stream'],
            'txt'  => ['text/plain']
        ];

        if (! in_array($ext, $allowedExts, true) || ! isset($allowedMimes[$ext]) || ! in_array($mime, $allowedMimes[$ext], true)) {
            return $this->response->setStatusCode(415)->setJSON([
                'status'  => 'error',
                'message' => 'Hanya format PDF, DOCX, dan TXT yang didukung.'
            ]);
        }

        if ($file->getSize() > 30 * 1024 * 1024) {
            return $this->response->setStatusCode(413)->setJSON([
                'status'  => 'error',
                'message' => 'Ukuran file maksimal adalah 30 MB.'
            ]);
        }

        // Periksa & potong kuota submisi SEBELUM memindahkan berkas fisik ke disk
        $userModel = new \App\Models\UserModel();
        $quotaDeducted = $userModel->deductSubmissionQuota($userId);
        if (! $quotaDeducted) {
            $quotaInfo = $userModel->getQuotaInfo($userId);
            return $this->response->setStatusCode(403)->setJSON([
                'status'     => 'quota_exceeded',
                'message'    => 'Batas kuota gratis 3 submisi minggu ini telah habis. Anda dapat menambah kuota dengan menyumbangkan 3 korpus karya ilmiah valid (tanpa duplikat).',
                'quota_info' => $quotaInfo,
            ]);
        }

        $storagePath = WRITEPATH . 'documents/';
        if (! is_dir($storagePath)) {
            mkdir($storagePath, 0775, true);
        }

        try {
            $filename = $file->getRandomName();
            $file->move($storagePath, $filename);

            $faculty = trim((string) ($this->request->getPost('faculty') ?? ''));
            $department = trim((string) ($this->request->getPost('department') ?? ''));

            $rawVisibility = (string) $this->request->getPost('corpus_visibility');
            $visibility = in_array($rawVisibility, ['private', 'institution', 'corpus'], true) ? $rawVisibility : 'private';

            $customTitle = trim((string) ($this->request->getPost('custom_title') ?: $this->request->getPost('title') ?: ''));
            $docTitle = !empty($customTitle) ? $customTitle : pathinfo($file->getClientName(), PATHINFO_FILENAME);

            $documentModel = new DocumentModel();
            $documentId = $documentModel->insert([
                'user_id'           => $userId,
                'title'             => $docTitle,
                'original_filename' => $file->getClientName(),
                'mime_type'         => $mime,
                'file_size'         => $file->getSize(),
                'storage_path'      => $storagePath . $filename,
                'status'            => 'uploaded',
                'document_type'     => 'submission',
                'corpus_visibility' => $visibility,
                'faculty'           => !empty($faculty) ? $faculty : null,
                'department'        => !empty($department) ? $department : null,
                'indexing_status'   => 'not_required',
                'created_at'        => date('Y-m-d H:i:s'),
                'updated_at'        => date('Y-m-d H:i:s'),
            ]);

            if (! $documentId) {
                throw new \RuntimeException('Gagal menyimpan metadata dokumen ke basis data.');
            }
        } catch (\Throwable $e) {
            $userModel->refundSubmissionQuota($userId);
            log_message('error', 'Gagal memproses unggahan dokumen: ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON([
                'status'  => 'error',
                'message' => 'Terjadi kesalahan sistem saat menyimpan dokumen. Kuota submisi Anda telah dipulihkan.'
            ]);
        }

        $quotaInfo = $userModel->getQuotaInfo($userId);

        return $this->response->setStatusCode(201)->setJSON([
            'status'      => 'success',
            'document_id' => $documentId,
            'filename'    => $file->getClientName(),
            'quota_info'  => $quotaInfo,
            'message'     => 'Dokumen berhasil diunggah.'
        ]);
    }

    /**
     * Endpoint Kontribusi Korpus Karya Ilmiah Pengguna (+3 Korpus = +1 Submisi)
     */
    public function contributeCorpus()
    {
        $userId = session()->get('user_id');
        if (! $userId) {
            return $this->response->setStatusCode(401)->setJSON([
                'status'  => 'error',
                'message' => 'Silakan masuk atau daftarkan akun terlebih dahulu untuk menyumbang korpus.',
            ]);
        }

        $file = $this->request->getFile('corpus_document') ?: $this->request->getFile('document');
        if (! $file || ! $file->isValid()) {
            return $this->response->setStatusCode(400)->setJSON([
                'status'  => 'error',
                'message' => 'Berkas korpus tidak valid atau belum diunggah.'
            ]);
        }

        $userModel = new \App\Models\UserModel();
        $user = $userModel->find($userId);
        if (! $user || $user['status'] !== 'active') {
            return $this->response->setStatusCode(403)->setJSON([
                'status'  => 'error',
                'message' => 'Akun Anda belum aktif atau belum diverifikasi untuk menyumbang korpus.',
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
                'message' => 'Hanya berkas format PDF, DOCX, dan TXT yang didukung.'
            ]);
        }

        if ($file->getSize() < 2048) {
            return $this->response->setStatusCode(400)->setJSON([
                'status'  => 'error',
                'message' => 'Ukuran berkas terlalu kecil (minimal 2 KB) untuk dianggap sebagai naskah karya ilmiah yang valid.'
            ]);
        }

        if ($file->getSize() > 30 * 1024 * 1024) {
            return $this->response->setStatusCode(413)->setJSON([
                'status'  => 'error',
                'message' => 'Ukuran berkas maksimal adalah 30 MB.'
            ]);
        }

        $db = db_connect();

        // Batasi kontribusi korpus maksimal 20 berkas per hari per akun untuk mencegah farming bot
        $todayCorpusCount = $db->table('documents')
            ->where('user_id', $userId)
            ->where('document_type', 'corpus')
            ->where('created_at >=', date('Y-m-d 00:00:00'))
            ->countAllResults();

        if ($todayCorpusCount >= 20) {
            return $this->response->setStatusCode(429)->setJSON([
                'status'  => 'error',
                'message' => 'Batas maksimal kontribusi korpus harian Anda (20 dokumen/hari) telah tercapai. Silakan coba kembali besok.'
            ]);
        }

        // Cek duplikasi berdasarkan nama berkas, judul, atau kesamaan ukuran berkas persis
        $targetTitle = pathinfo($file->getClientName(), PATHINFO_FILENAME);
        $fileSize = $file->getSize();
        $duplicate = $db->table('documents')
            ->where('document_type', 'corpus')
            ->groupStart()
                ->where('original_filename', $file->getClientName())
                ->orWhere('title', $targetTitle)
                ->orGroupStart()
                    ->where('file_size', $fileSize)
                    ->where('mime_type', $mime)
                ->groupEnd()
            ->groupEnd()
            ->get()
            ->getFirstRow();

        if ($duplicate) {
            return $this->response->setStatusCode(409)->setJSON([
                'status'  => 'duplicate',
                'message' => 'Karya ilmiah ini terdeteksi sebagai duplikat dari korpus yang sudah terdaftar. Harap unggah naskah rujukan lain yang belum ada di basis data.',
            ]);
        }

        $storagePath = WRITEPATH . 'documents/corpus/';
        if (! is_dir($storagePath)) {
            mkdir($storagePath, 0775, true);
        }

        $filename = $file->getRandomName();
        $file->move($storagePath, $filename);

        $customTitle = trim((string) $this->request->getPost('title'));
        $title = !empty($customTitle) ? $customTitle : $targetTitle;
        $sourceType = $this->request->getPost('source_type') ?: 'journal';
        $faculty = trim((string) ($this->request->getPost('faculty') ?? ''));
        $department = trim((string) ($this->request->getPost('department') ?? ''));

        $now = date('Y-m-d H:i:s');
        $documentModel = new DocumentModel();
        $docId = $documentModel->insert([
            'user_id'           => $userId,
            'title'             => $title,
            'original_filename' => $file->getClientName(),
            'mime_type'         => $mime,
            'file_size'         => $file->getSize(),
            'storage_path'      => $storagePath . $filename,
            'status'            => 'completed',
            'document_type'     => 'corpus',
            'corpus_visibility' => 'institution',
            'source_type'       => $sourceType,
            'faculty'           => !empty($faculty) ? $faculty : null,
            'department'        => !empty($department) ? $department : null,
            'indexing_status'   => 'completed',
            'created_at'        => $now,
            'updated_at'        => $now,
        ]);

        // Catat kontribusi pengguna & hitung reward (+3 Korpus = +1 Submisi)
        $userModel = new \App\Models\UserModel();
        $rewardResult = $userModel->recordCorpusUpload($userId);

        $msg = "Korpus '{$title}' berhasil diunggah dan diindeks ke repositori institusi.";
        if ($rewardResult['bonus_awarded']) {
            $msg .= " Selamat! Anda telah menyumbang 3 korpus valid dan mendapatkan bonus +1 submisi gratis!";
        } else {
            $needed = $rewardResult['quota_info']['corpus_to_next_bonus'];
            $msg .= " Sumbang {$needed} korpus valid lagi untuk mendapatkan +1 submisi gratis tambahan.";
        }

        return $this->response->setStatusCode(201)->setJSON([
            'status'        => 'success',
            'message'       => $msg,
            'document_id'   => $docId,
            'bonus_awarded' => $rewardResult['bonus_awarded'],
            'quota_info'    => $rewardResult['quota_info'],
        ]);
    }

    public function show($id)
    {
        $session = session();
        $userId  = $session->get('user_id');
        $isAdmin = ($session->get('user_role') === 'admin');

        $documentModel = new DocumentModel();
        $document = $documentModel->find($id);

        if (! $document) {
            return $this->response->setStatusCode(404)->setJSON([
                'status'  => 'error',
                'message' => 'Dokumen tidak ditemukan.'
            ]);
        }

        // Cek kepemilikan dokumen (kecuali admin, korpus publik, atau dibagikan ke pengguna)
        $isOwner = $userId && ((int) $document['user_id'] === (int) $userId);
        $shareModel = new \App\Models\DocumentShareModel();
        $isShared = $userId && $shareModel->userHasAccess((int) $id, (int) $userId, 'view_report');

        if (! $isOwner && ! $isAdmin && ! $isShared && $document['document_type'] !== 'corpus') {
            return $this->response->setStatusCode(404)->setJSON([
                'status'  => 'error',
                'message' => 'Dokumen tidak ditemukan.'
            ]);
        }

        // Ambil laporan terbaru jika ada
        $reportModel = new ReportModel();
        $report = $reportModel->where('document_id', $id)->orderBy('id', 'DESC')->first();

        // Jangan ekspos path fisik server
        unset($document['storage_path']);

        return $this->response->setJSON([
            'status'   => 'success',
            'data'     => $document,
            'report'   => $report
        ]);
    }

    public function check($id)
    {
        $session = session();
        $userId  = $session->get('user_id');
        $isAdmin = ($session->get('user_role') === 'admin');

        $documentModel = new DocumentModel();
        $document = $documentModel->find($id);

        if (! $document) {
            return $this->response->setStatusCode(404)->setJSON([
                'status'  => 'error',
                'message' => 'Dokumen tidak ditemukan.'
            ]);
        }

        // Cek hak akses pemilik
        $isOwner = $userId && ((int) $document['user_id'] === (int) $userId);
        if (! $isOwner && ! $isAdmin) {
            return $this->response->setStatusCode(403)->setJSON([
                'status'  => 'error',
                'message' => 'Akses ditolak: Anda tidak memiliki izin untuk memeriksa dokumen ini.'
            ]);
        }

        // Tolak pemeriksaan jika berkas fisik naskah sudah dimusnahkan secara mandiri (Zero-Leakage Guarantee)
        if (! empty($document['file_purged_at']) || ($document['storage_path'] ?? '') === '[PURGED]') {
            return $this->response->setStatusCode(400)->setJSON([
                'status'  => 'error',
                'message' => 'Pemeriksaan ulang tidak dapat dilakukan karena berkas fisik naskah asli telah dimusnahkan secara mandiri dari peladen demi kedaulatan privasi (Zero-Leakage Guarantee). Silakan unggah kembali naskah jika ingin melakukan pengujian baru.'
            ]);
        }

        // Potong kuota pengguna jika bukan admin dan dokumen ini adalah pengujian ulang (re-check)
        // Catatan: Dokumen baru yang belum pernah memiliki report sudah dipotong kuotanya saat upload().
        $reportModel = new ReportModel();
        $existingReportsCount = $reportModel->where('document_id', $id)->countAllResults();
        $userModel = new \App\Models\UserModel();
        $deductedInThisRequest = false;

        if (! $isAdmin && $userId && $existingReportsCount > 0) {
            $quotaDeducted = $userModel->deductSubmissionQuota($userId);
            if (! $quotaDeducted) {
                return $this->response->setStatusCode(403)->setJSON([
                    'status'  => 'quota_exceeded',
                    'message' => 'Batas kuota gratis minggu ini telah habis. Sumbang korpus atau lakukan top-up untuk melanjutkan.'
                ]);
            }
            $deductedInThisRequest = true;
        }

        $engine = $this->request->getPost('engine') ?: 'hybrid';

        try {
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

            QueueService::dispatch('similarity_check', [
                'document_id' => (int) $id,
                'report_id'   => (int) $reportId,
                'engine'      => $engine,
            ]);

            return $this->response->setStatusCode(202)->setJSON([
                'status'      => 'queued',
                'document_id' => (int) $id,
                'report_id'   => (int) $reportId,
                'message'     => 'Pemeriksaan similarity berhasil dijadwalkan ke antrean worker.'
            ]);
        } catch (\Throwable $e) {
            if ($deductedInThisRequest) {
                $userModel->refundSubmissionQuota($userId);
            }
            log_message('error', 'Gagal menjadwalkan pemeriksaan naskah: ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON([
                'status'  => 'error',
                'message' => 'Terjadi kesalahan sistem saat menjadwalkan pemeriksaan dokumen.'
            ]);
        }
    }

    public function enroll($id)
    {
        $session = session();
        $userId  = $session->get('user_id');
        $isAdmin = ($session->get('user_role') === 'admin');

        $documentModel = new DocumentModel();
        $document = $documentModel->find($id);

        if (! $document) {
            return $this->response->setStatusCode(404)->setJSON([
                'status'  => 'error',
                'message' => 'Dokumen tidak ditemukan.'
            ]);
        }

        // Hanya pemilik atau admin yang berhak mendaftarkan naskah menjadi korpus
        $isOwner = $userId && ((int) $document['user_id'] === (int) $userId);
        if (! $isOwner && ! $isAdmin) {
            return $this->response->setStatusCode(403)->setJSON([
                'status'  => 'error',
                'message' => 'Akses ditolak: Hanya pemilik naskah yang berhak mendaftarkan dokumen ke korpus.'
            ]);
        }

        if ($document['document_type'] === 'corpus') {
            return $this->response->setJSON([
                'status'  => 'already_enrolled',
                'message' => 'Dokumen ini sudah terdaftar sebagai korpus institusi.'
            ]);
        }

        $db = db_connect();

        // ═════════════════════════════════════════════════════════════════════════
        // CORPUS QUALITY GATE: Penyaringan Mutu & Integritas Sebelum Masuk Korpus
        // ═════════════════════════════════════════════════════════════════════════
        $reportModel = new \App\Models\ReportModel();
        $latestReport = $reportModel->where('document_id', $id)
                                    ->orderBy('id', 'DESC')
                                    ->first();

        // Syarat 1: Dokumen harus sudah selesai diperiksa
        if (! $latestReport || ($latestReport['status'] ?? '') !== 'completed') {
            return $this->response->setStatusCode(422)->setJSON([
                'status'  => 'error',
                'message' => 'Naskah belum dapat didaftarkan ke korpus: Pemeriksaan kemiripan naskah harus selesai dilakukan terlebih dahulu.'
            ]);
        }

        // Syarat 2: Ambang Batas Kemiripan Awal (Maksimal 25% untuk non-admin)
        $simScore = (float) ($latestReport['overall_similarity'] ?? 0.0);
        if (! $isAdmin && $simScore > 25.0) {
            return $this->response->setStatusCode(422)->setJSON([
                'status'  => 'error',
                'message' => "Naskah belum memenuhi standar mutu korpus: Tingkat kemiripan saat ini ({$simScore}%) melebihi batas maksimal 25%. Korpus institusi dipersyaratkan untuk naskah berintegritas tinggi."
            ]);
        }

        // Syarat 3: Panjang Substantif Minimum (Minimal 1.000 kata untuk non-admin)
        $wordCountRow = $db->query("SELECT COALESCE(SUM(word_count), 0) AS total_words FROM document_chunks WHERE document_id = ?", [(int)$id])->getRowArray();
        $totalWords = (int) ($wordCountRow['total_words'] ?? 0);
        if (! $isAdmin && $totalWords < 1000) {
            return $this->response->setStatusCode(422)->setJSON([
                'status'  => 'error',
                'message' => "Naskah belum memenuhi kelayakan korpus: Panjang naskah ({$totalWords} kata) kurang dari batas minimum karya ilmiah lengkap (1.000 kata)."
            ]);
        }

        // Syarat 4: Deteksi Integritas AI (Tingkat Indikasi AI Tidak Boleh Ekstrem Tinggi)
        $evData = [];
        if (!empty($latestReport['evidence_data'])) {
            $evData = is_array($latestReport['evidence_data']) ? $latestReport['evidence_data'] : json_decode((string)$latestReport['evidence_data'], true);
        }
        $geminiAudit = $evData['gemini_forensic']['audit'] ?? [];
        $aiLevel = strtoupper(trim((string)($geminiAudit['tingkat_indikasi_ai'] ?? '')));
        $aiProbability = (float) ($evData['ai_stylometrics']['ai_probability_score'] ?? 0.0);
        if (! $isAdmin && ($aiLevel === 'TINGGI' || $aiProbability >= 80.0)) {
            return $this->response->setStatusCode(422)->setJSON([
                'status'  => 'error',
                'message' => "Naskah belum memenuhi syarat korpus: Terdeteksi tingkat indikasi generatif AI yang dominan ({$aiLevel}). Korpus institusi diprioritaskan untuk karya orisinal manusia."
            ]);
        }

        $db->transStart();

        // Pembaruan Versi: Jika pengguna pernah mendaftarkan naskah korpus terdahulu, ganti versi lama agar tidak redundan
        $replacedOldCount = 0;
        if (!empty($document['user_id'])) {
            $prevCorpusDocs = $documentModel->where('user_id', $document['user_id'])
                                            ->where('document_type', 'corpus')
                                            ->where('id !=', $id)
                                            ->findAll();
            foreach ($prevCorpusDocs as $oldDoc) {
                $documentModel->update($oldDoc['id'], [
                    'document_type'     => 'submission',
                    'corpus_visibility' => 'private',
                    'updated_at'        => date('Y-m-d H:i:s'),
                ]);
                $db->query("DELETE FROM fingerprint_documents WHERE document_id = ?", [(int)$oldDoc['id']]);
                $replacedOldCount++;
            }
        }

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

        // Catat kontribusi korpus untuk pemilik dokumen (+3 Korpus = +1 Submisi)
        $bonusMsg = "";
        $targetUserId = $document['user_id'] ?: session()->get('user_id');
        if ($targetUserId) {
            $userModel = new \App\Models\UserModel();
            $rewardResult = $userModel->recordCorpusUpload((int)$targetUserId);
            if (!empty($rewardResult['bonus_awarded'])) {
                $bonusMsg = " Selamat! Sumbangan 3 korpus valid Anda telah tercapai dan mendapatkan bonus +1 submisi gratis!";
            } else if (isset($rewardResult['quota_info']['corpus_to_next_bonus'])) {
                $needed = $rewardResult['quota_info']['corpus_to_next_bonus'];
                $bonusMsg = " Sumbang {$needed} korpus valid lagi untuk klaim reward +1 submisi.";
            }
        }

        return $this->response->setJSON([
            'status'  => 'success',
            'message' => "Naskah '{$document['title']}' berhasil didaftarkan sebagai Korpus Institusi permanen.{$bonusMsg}"
        ]);
    }

    /**
     * Endpoint Integrasi Eksternal (OJS 3 & LMS Moodle Plugin)
     * Mengunggah dokumen dan langsung menjadwalkan pemeriksaan dalam 1 panggilan API.
     */
    public function checkSubmission()
    {
        $userId = session()->get('user_id');
        $apiKeyId = null;

        if (! $userId) {
            $apiKeyModel = new \App\Models\ApiKeyModel();
            $authHeader = $this->request->getHeaderLine('X-API-Key');
            if (empty($authHeader)) {
                $bearer = $this->request->getHeaderLine('Authorization');
                if (preg_match('/Bearer\s+(\S+)/i', $bearer, $matches)) {
                    $authHeader = $matches[1];
                }
            }
            if (!empty($authHeader)) {
                $keyData = $apiKeyModel->validateKey($authHeader);
                if ($keyData) {
                    $userId = (int) $keyData['user_id'];
                    $apiKeyId = (int) $keyData['id'];
                }
            }
        }

        if (! $userId) {
            return $this->response->setStatusCode(401)->setJSON([
                'status'  => 'error',
                'message' => 'Autentikasi gagal. Masuk atau sertakan API Key yang valid pada header X-API-Key.'
            ]);
        }

        $file = $this->request->getFile('document');
        if (! $file || ! $file->isValid()) {
            return $this->response->setStatusCode(400)->setJSON([
                'status'  => 'error',
                'message' => 'Berkas naskah tidak valid atau belum diunggah.'
            ]);
        }

        $ext = strtolower($file->getClientExtension());
        $mime = $file->getMimeType();
        $allowedExts = ['pdf', 'docx', 'txt'];
        $allowedMimes = [
            'pdf'  => ['application/pdf', 'application/x-pdf'],
            'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip', 'application/octet-stream'],
            'txt'  => ['text/plain']
        ];

        if (! in_array($ext, $allowedExts, true) || ! isset($allowedMimes[$ext]) || ! in_array($mime, $allowedMimes[$ext], true)) {
            return $this->response->setStatusCode(415)->setJSON([
                'status'  => 'error',
                'message' => 'Hanya format PDF, DOCX, dan TXT yang didukung.'
            ]);
        }

        if ($file->getSize() > 30 * 1024 * 1024) {
            return $this->response->setStatusCode(413)->setJSON([
                'status'  => 'error',
                'message' => 'Ukuran berkas maksimal adalah 30 MB.'
            ]);
        }

        // Potong kuota submisi pengguna
        $userModel = new \App\Models\UserModel();
        $quotaDeducted = $userModel->deductSubmissionQuota($userId);
        if (! $quotaDeducted) {
            return $this->response->setStatusCode(403)->setJSON([
                'status'  => 'error',
                'message' => 'Batas kuota submisi akun Anda tidak mencukupi.'
            ]);
        }

        if ($apiKeyId) {
            (new \App\Models\ApiKeyModel())->recordUsage($apiKeyId);
        }

        $storagePath = WRITEPATH . 'documents/';
        if (! is_dir($storagePath)) {
            mkdir($storagePath, 0775, true);
        }

        try {
            $filename = $file->getRandomName();
            $file->move($storagePath, $filename);

            $customTitle = trim((string)($this->request->getPost('custom_title') ?: $this->request->getPost('title') ?: ''));
            $title = !empty($customTitle) ? $customTitle : pathinfo($file->getClientName(), PATHINFO_FILENAME);
            $engine = $this->request->getPost('engine') ?: 'hybrid';
            $now = date('Y-m-d H:i:s');

            $faculty = trim((string) ($this->request->getPost('faculty') ?? ''));
            $department = trim((string) ($this->request->getPost('department') ?? ''));

            $documentModel = new DocumentModel();
            $documentId = $documentModel->insert([
                'user_id'           => $userId,
                'title'             => $title,
                'original_filename' => $file->getClientName(),
                'mime_type'         => $mime,
                'file_size'         => $file->getSize(),
                'storage_path'      => $storagePath . $filename,
                'status'            => 'uploaded',
                'document_type'     => 'submission',
                'corpus_visibility' => 'private',
                'faculty'           => !empty($faculty) ? $faculty : null,
                'department'        => !empty($department) ? $department : null,
                'indexing_status'   => 'not_required',
                'created_at'        => $now,
                'updated_at'        => $now,
            ]);

            // Buat kode verifikasi acak aman
            $rnd = strtoupper(bin2hex(random_bytes(6)));
            $certHash = "ART-" . substr($rnd, 0, 4) . "-" . substr($rnd, 4, 4) . "-" . substr($rnd, 8, 4);

            $reportModel = new ReportModel();
            $reportId = $reportModel->insert([
                'document_id'        => $documentId,
                'engine'             => $engine,
                'predicted_decision' => 'no_match',
                'overall_similarity' => 0,
                'source_count'       => 0,
                'match_count'        => 0,
                'verification_code'  => $certHash,
                'status'             => 'pending',
                'created_at'         => $now,
            ]);

            // Masukkan ke antrean worker via QueueService (Redis event-driven + MariaDB fallback)
            QueueService::dispatch('similarity_check', [
                'document_id' => (int) $documentId,
                'report_id'   => (int) $reportId,
                'engine'      => $engine,
            ]);
        } catch (\Throwable $e) {
            $userModel->refundSubmissionQuota($userId);
            log_message('error', 'Gagal memproses checkSubmission: ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON([
                'status'  => 'error',
                'message' => 'Terjadi kesalahan sistem saat menjadwalkan pemeriksaan. Kuota submisi Anda telah dipulihkan.'
            ]);
        }

        $publicUrl = rtrim(base_url(), '/');

        return $this->response->setStatusCode(202)->setJSON([
            'status'            => 'queued',
            'document_id'       => (int) $documentId,
            'report_id'         => (int) $reportId,
            'verification_code' => $certHash,
            'report_url'        => "{$publicUrl}/report/{$reportId}?token={$certHash}",
            'verification_url'  => "{$publicUrl}/verify/{$certHash}",
            'pdf_export_url'    => "{$publicUrl}/report/{$reportId}/export?token={$certHash}",
            'status_api_url'    => "{$publicUrl}/api/reports/{$reportId}",
            'message'           => 'Pemeriksaan similarity berhasil dijadwalkan ke antrean AR Tilik.'
        ]);
    }

    /**
     * Memeriksa hak pemilik atau admin atas dokumen
     */
    private function checkDocumentOwnerOrAdmin(int $documentId, &$document = null): ?\CodeIgniter\HTTP\ResponseInterface
    {
        $userId = session()->get('user_id');
        if (! $userId) {
            return $this->response->setStatusCode(401)->setJSON([
                'status'  => 'error',
                'message' => 'Silakan masuk terlebih dahulu.',
            ]);
        }

        $docModel = new DocumentModel();
        $document = $docModel->find($documentId);
        if (! $document) {
            return $this->response->setStatusCode(404)->setJSON([
                'status'  => 'error',
                'message' => 'Dokumen tidak ditemukan.',
            ]);
        }

        $isAdmin = (session()->get('user_role') === 'admin');
        if (! $isAdmin && (int) $document['user_id'] !== (int) $userId) {
            return $this->response->setStatusCode(403)->setJSON([
                'status'  => 'error',
                'message' => 'Hanya pemilik naskah yang berhak mengelola pembagian karya ilmiah.',
            ]);
        }

        return null;
    }

    /**
     * Mengambil daftar pengguna yang memiliki akses ke dokumen ini
     */
    public function listShares(int $documentId)
    {
        $doc = null;
        $authError = $this->checkDocumentOwnerOrAdmin($documentId, $doc);
        if ($authError) {
            return $authError;
        }

        $shareModel = new \App\Models\DocumentShareModel();
        $shares = $shareModel->getSharesForDocument($documentId);

        $shareUrl = null;
        if (!empty($doc['share_token'])) {
            $shareUrl = base_url('documents/shared/' . $doc['share_token']);
        }

        return $this->response->setJSON([
            'status'       => 'success',
            'document_id'  => $documentId,
            'title'        => $doc['title'],
            'is_shared'    => (bool) ($doc['is_shared'] ?? false),
            'share_token'  => $doc['share_token'] ?? null,
            'share_url'    => $shareUrl,
            'shares'       => $shares,
        ]);
    }

    /**
     * Menambahkan pengguna untuk berbagi karya ilmiah via email
     */
    public function addShare(int $documentId)
    {
        $doc = null;
        $authError = $this->checkDocumentOwnerOrAdmin($documentId, $doc);
        if ($authError) {
            return $authError;
        }

        $userId = (int) session()->get('user_id');
        $rawEmail = (string) ($this->request->getPost('email') ?? '');
        $email = strtolower(trim($rawEmail));
        $permission = (string) ($this->request->getPost('permission') ?? 'view_all');
        if (! in_array($permission, ['view_all', 'view_report'], true)) {
            $permission = 'view_all';
        }

        if (empty($email)) {
            return $this->response->setStatusCode(400)->setJSON([
                'status'  => 'error',
                'message' => 'Alamat email penerima wajib diisi.',
            ]);
        }

        // Pastikan dokumen memiliki share_token agar link undangan siap
        $docModel = new DocumentModel();
        if (empty($doc['share_token'])) {
            $token = bin2hex(random_bytes(16));
            $docModel->update($documentId, [
                'share_token' => $token,
                'is_shared'   => 1,
            ]);
            $doc['share_token'] = $token;
            $doc['is_shared'] = 1;
        }

        $shareModel = new \App\Models\DocumentShareModel();
        $result = $shareModel->shareWithEmail($documentId, $userId, $email, $permission);

        if (! $result['success']) {
            return $this->response->setStatusCode(400)->setJSON([
                'status'  => 'error',
                'message' => $result['message'],
            ]);
        }

        $shares = $shareModel->getSharesForDocument($documentId);
        $shareUrl = base_url('documents/shared/' . $doc['share_token']);

        return $this->response->setJSON([
            'status'        => 'success',
            'message'       => $result['message'],
            'is_registered' => $result['is_registered'],
            'share_url'     => $shareUrl,
            'shares'        => $shares,
        ]);
    }

    /**
     * Menghapus / mencabut hak akses sharing seorang pengguna
     */
    public function deleteShare(int $documentId, int $shareId)
    {
        $doc = null;
        $authError = $this->checkDocumentOwnerOrAdmin($documentId, $doc);
        if ($authError) {
            return $authError;
        }

        $shareModel = new \App\Models\DocumentShareModel();
        $share = $shareModel->where('id', $shareId)->where('document_id', $documentId)->first();
        if (! $share) {
            return $this->response->setStatusCode(404)->setJSON([
                'status'  => 'error',
                'message' => 'Catatan akses sharing tidak ditemukan.',
            ]);
        }

        $shareModel->delete($shareId);

        $shares = $shareModel->getSharesForDocument($documentId);

        return $this->response->setJSON([
            'status'  => 'success',
            'message' => 'Akses berbagi berhasil dicabut.',
            'shares'  => $shares,
        ]);
    }

    /**
     * Mengatur status tautan berbagi publik / regenerasi token tautan
     */
    public function toggleShareLink(int $documentId)
    {
        $doc = null;
        $authError = $this->checkDocumentOwnerOrAdmin($documentId, $doc);
        if ($authError) {
            return $authError;
        }

        $docModel = new DocumentModel();
        $isSharedParam = $this->request->getPost('is_shared');
        $regenerate = (bool) $this->request->getPost('regenerate');

        $isShared = ($isSharedParam !== null) ? ((int) $isSharedParam === 1 ? 1 : 0) : ($doc['is_shared'] ? 0 : 1);
        $token = $doc['share_token'];

        if (empty($token) || $regenerate) {
            $token = bin2hex(random_bytes(16));
        }

        $docModel->update($documentId, [
            'is_shared'   => $isShared,
            'share_token' => $token,
            'updated_at'  => date('Y-m-d H:i:s'),
        ]);

        $shareUrl = base_url('documents/shared/' . $token);

        return $this->response->setJSON([
            'status'      => 'success',
            'message'     => $isShared ? 'Tautan berbagi berhasil diaktifkan.' : 'Tautan berbagi dinonaktifkan.',
            'is_shared'   => (bool) $isShared,
            'share_token' => $token,
            'share_url'   => $shareUrl,
        ]);
    }
}
