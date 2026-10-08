<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\ReportModel;
use App\Models\ReportSourceModel;
use App\Models\ReportMatchModel;
use App\Models\DocumentModel;
use App\Models\ApiKeyModel;

class ReportController extends BaseController
{
    /**
     * Memverifikasi hak akses terhadap laporan: pemilik, admin, atau memiliki token otorisasi
     */
    private function authorizeReportAccess($reportId, &$report = null, &$doc = null): bool
    {
        $model = new ReportModel();
        $report = $model->find($reportId);
        if (! $report) {
            return false;
        }

        $documentModel = new DocumentModel();
        $doc = $documentModel->find($report['document_id']);
        if (! $doc) {
            return false;
        }

        $session = session();
        $userId = $session->get('user_id');
        $isAdmin = ($session->get('user_role') === 'admin');

        // Jika pemilik dokumen atau administrator
        if ($isAdmin || ($userId && (int)$doc['user_id'] === (int)$userId)) {
            return true;
        }

        // Cek apakah naskah dibagikan kepada akun yang sedang login
        if ($userId) {
            $shareModel = new \App\Models\DocumentShareModel();
            if ($shareModel->userHasAccess((int)$doc['id'], (int)$userId, 'view_report')) {
                return true;
            }
        }

        // Cek token share jika disertakan di URL (?share_token=...)
        $shareToken = $this->request->getGet('share_token');
        if (!empty($shareToken) && !empty($doc['share_token']) && $shareToken === $doc['share_token'] && !empty($doc['is_shared'])) {
            if ($userId) {
                $shareModel = new \App\Models\DocumentShareModel();
                $shareModel->shareWithEmail((int)$doc['id'], (int)$doc['user_id'], (string)$session->get('user_email'));
            }
            return true;
        }

        // Jika disertakan token verifikasi resmi pada URL (?token= atau ?code=)
        $token = $this->request->getGet('token') ?: $this->request->getGet('code');
        if (!empty($token) && $token === ($report['verification_code'] ?? null)) {
            return true;
        }

        // Cek header API Key jika dipanggil dari luar
        $authHeader = $this->request->getHeaderLine('X-API-Key');
        if (empty($authHeader)) {
            $bearer = $this->request->getHeaderLine('Authorization');
            if (preg_match('/Bearer\s+(\S+)/i', $bearer, $matches)) {
                $authHeader = $matches[1];
            }
        }
        if (!empty($authHeader)) {
            $keyData = (new ApiKeyModel())->validateKey($authHeader);
            if ($keyData && (int)$doc['user_id'] === (int)$keyData['user_id']) {
                return true;
            }
        }

        return false;
    }

    public function show($id)
    {
        $report = null;
        $doc = null;
        if (! $this->authorizeReportAccess($id, $report, $doc)) {
            return $this->response->setStatusCode(404)->setJSON([
                'status'  => 'error',
                'message' => 'Laporan similarity tidak ditemukan.'
            ]);
        }

        // Jangan ekspos path fisik server
        unset($doc['storage_path']);

        return $this->response->setJSON([
            'status'   => 'success',
            'data'     => $report,
            'document' => $doc
        ]);
    }

    public function sources($id)
    {
        $report = null;
        $doc = null;
        if (! $this->authorizeReportAccess($id, $report, $doc)) {
            return $this->response->setStatusCode(404)->setJSON([
                'status'  => 'error',
                'message' => 'Laporan similarity tidak ditemukan.'
            ]);
        }

        $db = db_connect();
        $builder = $db->table('report_sources rs');
        $builder->select('rs.*, d.title AS source_title, d.original_filename AS source_filename, d.source_type');
        $builder->join('documents d', 'd.id = rs.source_document_id', 'left');
        $builder->where('rs.report_id', $id);
        $builder->orderBy('rs.source_rank', 'ASC');
        $sources = $builder->get()->getResultArray();

        return $this->response->setJSON([
            'status' => 'success',
            'data'   => $sources
        ]);
    }

    public function matches($id)
    {
        $report = null;
        $doc = null;
        if (! $this->authorizeReportAccess($id, $report, $doc)) {
            return $this->response->setStatusCode(404)->setJSON([
                'status'  => 'error',
                'message' => 'Laporan similarity tidak ditemukan.'
            ]);
        }

        $db = db_connect();
        $builder = $db->table('report_matches rm');
        $builder->select('rm.*, d.title AS source_title, d.original_filename AS source_filename');
        $builder->join('documents d', 'd.id = rm.source_document_id', 'left');
        $builder->where('rm.report_id', $id);
        $builder->orderBy('rm.submission_start', 'ASC');
        $matches = $builder->get()->getResultArray();

        return $this->response->setJSON([
            'status' => 'success',
            'data'   => $matches
        ]);
    }
}
