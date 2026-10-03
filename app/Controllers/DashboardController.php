<?php

namespace App\Controllers;

use App\Models\DocumentModel;
use App\Models\ReportModel;

class DashboardController extends BaseController
{
    public function index()
    {
        $docModel = new DocumentModel();
        $reportModel = new ReportModel();
        $db = db_connect();

        // Dokumen submisi terbaru dengan laporan terakhir
        $submissions = $docModel->where('document_type', 'submission')
                                ->where('benchmark_run_id IS NULL', null, false)
                                ->orderBy('id', 'DESC')
                                ->findAll(20);

        foreach ($submissions as &$sub) {
            $latestReport = $reportModel->where('document_id', $sub['id'])
                                        ->orderBy('id', 'DESC')
                                        ->first();
            $sub['latest_report'] = $latestReport;
        }
        unset($sub);

        // Dokumen korpus terbaru
        $corpusDocs = $docModel->where('document_type', 'corpus')
                               ->where('benchmark_run_id IS NULL', null, false)
                               ->orderBy('id', 'DESC')
                               ->findAll(15);

        // Riwayat benchmark runs
        $benchmarkRuns = $db->table('benchmark_runs')->orderBy('id', 'DESC')->get(10)->getResultArray();

        // Statistik
        $totalSubmissions = $docModel->where('document_type', 'submission')->where('benchmark_run_id IS NULL', null, false)->countAllResults();
        $totalCorpus = $docModel->where('document_type', 'corpus')->where('benchmark_run_id IS NULL', null, false)->countAllResults();
        $totalReports = $reportModel->countAllResults();

        return view('dashboard/index', [
            'submissions'      => $submissions,
            'corpusDocs'       => $corpusDocs,
            'benchmarkRuns'    => $benchmarkRuns,
            'totalSubmissions' => $totalSubmissions,
            'totalCorpus'      => $totalCorpus,
            'totalReports'     => $totalReports,
        ]);
    }

    public function report($id)
    {
        $reportModel = new ReportModel();
        $docModel = new DocumentModel();
        $db = db_connect();

        $report = $reportModel->find($id);
        if (! $report) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound("Laporan #{$id} tidak ditemukan.");
        }

        $document = $docModel->find($report['document_id']);

        // Parse evidence_data JSON jika tersedia
        $evidenceData = [];
        if (!empty($report['evidence_data'])) {
            $evidenceData = is_string($report['evidence_data']) ? json_decode($report['evidence_data'], true) : $report['evidence_data'];
        }

        // Sources
        $sources = $db->table('report_sources rs')
                      ->select('rs.*, d.title AS source_title, d.original_filename AS source_filename, d.source_type')
                      ->join('documents d', 'd.id = rs.source_document_id', 'left')
                      ->where('rs.report_id', $id)
                      ->orderBy('rs.source_rank', 'ASC')
                      ->get()->getResultArray();

        // Matches
        $matches = $db->table('report_matches rm')
                      ->select('rm.*, d.title AS source_title, d.original_filename AS source_filename')
                      ->join('documents d', 'd.id = rm.source_document_id', 'left')
                      ->where('rm.report_id', $id)
                      ->orderBy('rm.submission_start', 'ASC')
                      ->get()->getResultArray();

        // Ambil potongan teks untuk modal/drawer [View Passage]
        $subChunk = $db->table('document_chunks')
                       ->where('document_id', $report['document_id'])
                       ->orderBy('chunk_index', 'ASC')
                       ->get()->getRowArray();
        $subWords = $subChunk ? preg_split('/\s+/', trim($subChunk['text'])) : [];

        $sourceCache = [];
        foreach ($matches as &$m) {
            $srcDocId = (int) $m['source_document_id'];
            if (!isset($sourceCache[$srcDocId])) {
                $srcChunk = $db->table('document_chunks')
                               ->where('document_id', $srcDocId)
                               ->orderBy('chunk_index', 'ASC')
                               ->get()->getRowArray();
                $sourceCache[$srcDocId] = $srcChunk ? preg_split('/\s+/', trim($srcChunk['text'])) : [];
            }
            $srcWords = $sourceCache[$srcDocId];

            $sStart = max(0, (int) $m['submission_start']);
            $sEnd = min(count($subWords), (int) $m['submission_end'] + 1);
            $m['submission_snippet'] = !empty($subWords) ? implode(' ', array_slice($subWords, $sStart, max(1, $sEnd - $sStart))) : 'Teks submisi tidak tersedia.';

            $srcStart = max(0, (int) $m['source_start']);
            $srcEnd = min(count($srcWords), (int) $m['source_end'] + 1);
            $m['source_snippet'] = !empty($srcWords) ? implode(' ', array_slice($srcWords, $srcStart, max(1, $srcEnd - $srcStart))) : 'Teks sumber tidak tersedia.';
        }
        unset($m);

        $highAttentionCount = 0;
        $commonPassagesCount = 0;
        foreach ($matches as $m) {
            $com = $m['commonness'] ?? 'low';
            if (in_array($com, ['low', 'very_low'], true)) {
                $highAttentionCount++;
            } elseif (in_array($com, ['high', 'very_high'], true)) {
                $commonPassagesCount++;
            }
        }

        return view('dashboard/report', [
            'report'              => $report,
            'document'            => $document,
            'evidenceData'        => $evidenceData,
            'sources'             => $sources,
            'matches'             => $matches,
            'highAttentionCount'  => $highAttentionCount,
            'commonPassagesCount' => $commonPassagesCount,
        ]);
    }
}
