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

        // Ambil potongan teks untuk modal/drawer [View Passage] menggunakan posisi kata akurat
        foreach ($matches as &$m) {
            $srcDocId = (int) $m['source_document_id'];

            // Potongan submisi berdasarkan posisi kata
            $sChunk = $db->table('document_chunks')
                         ->where('document_id', $report['document_id'])
                         ->where('start_word_position <=', (int) $m['submission_start'])
                         ->orderBy('start_word_position', 'DESC')
                         ->limit(1)
                         ->get()->getRowArray();
            if ($sChunk) {
                $sw = preg_split('/\s+/', trim($sChunk['text']));
                $sOffset = max(0, (int) $m['submission_start'] - (int) $sChunk['start_word_position']);
                $sCount = max(1, (int) $m['word_count']);
                $m['submission_snippet'] = implode(' ', array_slice($sw, $sOffset, $sCount));
            } else {
                $m['submission_snippet'] = 'Teks submisi tidak tersedia.';
            }

            // Potongan dokumen sumber berdasarkan posisi kata
            $srcChunk = $db->table('document_chunks')
                          ->where('document_id', $srcDocId)
                          ->where('start_word_position <=', (int) $m['source_start'])
                          ->orderBy('start_word_position', 'DESC')
                          ->limit(1)
                          ->get()->getRowArray();
            if ($srcChunk) {
                $srcw = preg_split('/\s+/', trim($srcChunk['text']));
                $srcOffset = max(0, (int) $m['source_start'] - (int) $srcChunk['start_word_position']);
                $srcCount = max(1, (int) $m['word_count']);
                $m['source_snippet'] = implode(' ', array_slice($srcw, $srcOffset, $srcCount));
            } else {
                $m['source_snippet'] = 'Teks sumber tidak tersedia.';
            }
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

    public function exportPdf($id)
    {
        $reportModel = new ReportModel();
        $docModel = new DocumentModel();

        $report = $reportModel->find($id);
        if (! $report) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound("Laporan #{$id} tidak ditemukan.");
        }

        $document = $docModel->find($report['document_id']);
        $rawTitle = $document['title'] ?? 'Dokumen';
        $safeTitle = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $rawTitle);
        $filename = "Laporan_AR_Tilik_#{$id}_{$safeTitle}.pdf";

        // Eksekusi worker exporter via Python
        $projectRoot = ROOTPATH;
        $pythonBin = $projectRoot . '.venv/bin/python';
        if (! file_exists($pythonBin)) {
            $pythonBin = 'python3';
        }

        $cmd = escapeshellcmd($pythonBin) . ' -m worker.exporter --report ' . (int) $id . ' --stdout';

        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = proc_open($cmd, $descriptors, $pipes, $projectRoot);
        if (! is_resource($process)) {
            return $this->response->setStatusCode(500)->setBody("Gagal menginisialisasi proses generator PDF.");
        }

        fclose($pipes[0]);
        $pdfContent = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[2]);

        $exitCode = proc_close($process);

        if ($exitCode !== 0 || empty($pdfContent)) {
            log_message('error', "PDF export failed: {$stderr}");
            return $this->response->setStatusCode(500)->setBody("Gagal menghasilkan PDF laporan: " . esc($stderr));
        }

        return $this->response
            ->setHeader('Content-Type', 'application/pdf')
            ->setHeader('Content-Disposition', 'inline; filename="' . $filename . '"')
            ->setHeader('Content-Length', (string) strlen($pdfContent))
            ->setHeader('Cache-Control', 'private, max-age=0, must-revalidate')
            ->setBody($pdfContent);
    }

    public function previewPdf($id)
    {
        $reportModel = new ReportModel();
        $docModel = new DocumentModel();
        $db = db_connect();

        $report = $reportModel->find($id);
        if (! $report) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound("Laporan #{$id} tidak ditemukan.");
        }

        $document = $docModel->find($report['document_id']);
        $evidenceData = !empty($report['evidence_data']) ? (is_string($report['evidence_data']) ? json_decode($report['evidence_data'], true) : $report['evidence_data']) : [];

        $sources = $db->table('report_sources rs')
                      ->select('rs.*, d.title AS source_title, d.original_filename AS source_filename, d.source_type')
                      ->join('documents d', 'd.id = rs.source_document_id', 'left')
                      ->where('rs.report_id', $id)
                      ->orderBy('rs.source_rank', 'ASC')
                      ->get()->getResultArray();

        $matches = $db->table('report_matches rm')
                      ->select('rm.*, d.title AS source_title, d.original_filename AS source_filename')
                      ->join('documents d', 'd.id = rm.source_document_id', 'left')
                      ->where('rm.report_id', $id)
                      ->orderBy('rm.word_count', 'DESC')
                      ->orderBy('rm.submission_start', 'ASC')
                      ->get()->getResultArray();

        foreach ($matches as &$m) {
            $srcDocId = (int) $m['source_document_id'];

            $sChunk = $db->table('document_chunks')
                         ->where('document_id', $report['document_id'])
                         ->where('start_word_position <=', (int) $m['submission_start'])
                         ->orderBy('start_word_position', 'DESC')
                         ->limit(1)
                         ->get()->getRowArray();
            if ($sChunk) {
                $sw = preg_split('/\s+/', trim($sChunk['text']));
                $sOffset = max(0, (int) $m['submission_start'] - (int) $sChunk['start_word_position']);
                $sCount = max(1, (int) $m['word_count']);
                $m['submission_snippet'] = implode(' ', array_slice($sw, $sOffset, $sCount));
            } else {
                $m['submission_snippet'] = 'Teks submisi tidak tersedia.';
            }

            $srcChunk = $db->table('document_chunks')
                          ->where('document_id', $srcDocId)
                          ->where('start_word_position <=', (int) $m['source_start'])
                          ->orderBy('start_word_position', 'DESC')
                          ->limit(1)
                          ->get()->getRowArray();
            if ($srcChunk) {
                $srcw = preg_split('/\s+/', trim($srcChunk['text']));
                $srcOffset = max(0, (int) $m['source_start'] - (int) $srcChunk['start_word_position']);
                $srcCount = max(1, (int) $m['word_count']);
                $m['source_snippet'] = implode(' ', array_slice($srcw, $srcOffset, $srcCount));
            } else {
                $m['source_snippet'] = 'Teks sumber tidak tersedia.';
            }
        }
        unset($m);

        return view('dashboard/report_pdf', [
            'report'       => $report,
            'document'     => $document,
            'evidenceData' => $evidenceData,
            'sources'      => $sources,
            'matches'      => $matches,
        ]);
    }
}
