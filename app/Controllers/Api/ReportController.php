<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\ReportModel;
use App\Models\ReportSourceModel;
use App\Models\ReportMatchModel;
use App\Models\DocumentModel;

class ReportController extends BaseController
{
    public function show($id)
    {
        $model = new ReportModel();
        $report = $model->find($id);

        if (! $report) {
            return $this->response->setStatusCode(404)->setJSON([
                'status'  => 'error',
                'message' => 'Laporan similarity tidak ditemukan.'
            ]);
        }

        $documentModel = new DocumentModel();
        $doc = $documentModel->find($report['document_id']);

        return $this->response->setJSON([
            'status'   => 'success',
            'data'     => $report,
            'document' => $doc
        ]);
    }

    public function sources($id)
    {
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
