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

            // Anotasi Academic Boilerplate dari evidenceData jika tersedia
            $m['is_boilerplate'] = false;
            $m['boilerplate_category'] = 'NONE';
            $m['boilerplate_phrase'] = null;
            $m['boilerplate_rationale'] = null;
        }
        unset($m);

        // Petakan metadata v1.2 dari evidenceData['matches']
        if (!empty($evidenceData['matches']) && is_array($evidenceData['matches'])) {
            $evMatchMap = [];
            foreach ($evidenceData['matches'] as $em) {
                $k = ($em['source_document_id'] ?? 0) . '_' . ($em['submission_start'] ?? 0);
                $evMatchMap[$k] = $em;
            }
            foreach ($matches as &$m) {
                $k = ((int) $m['source_document_id']) . '_' . ((int) $m['submission_start']);
                if (isset($evMatchMap[$k])) {
                    $m['is_boilerplate'] = !empty($evMatchMap[$k]['is_boilerplate']);
                    $m['boilerplate_category'] = $evMatchMap[$k]['boilerplate_category'] ?? 'NONE';
                    $m['boilerplate_phrase'] = $evMatchMap[$k]['boilerplate_phrase'] ?? null;
                    $m['boilerplate_rationale'] = $evMatchMap[$k]['boilerplate_rationale'] ?? null;
                }
            }
            unset($m);
        }

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

        // Ambil teks lengkap submisi & sumber untuk Layar Ganda (Dual-Reader)
        $subChunks = $db->table('document_chunks')
                        ->select('text')
                        ->where('document_id', $report['document_id'])
                        ->orderBy('chunk_index', 'ASC')
                        ->get()->getResultArray();
        $submissionText = implode(' ', array_column($subChunks, 'text'));

        $sourceTexts = [];
        foreach ($sources as $s) {
            $sDocId = (int) $s['source_document_id'];
            $srcChunks = $db->table('document_chunks')
                            ->select('text')
                            ->where('document_id', $sDocId)
                            ->orderBy('chunk_index', 'ASC')
                            ->get()->getResultArray();
            $sourceTexts[$sDocId] = implode(' ', array_column($srcChunks, 'text'));
        }

        // Resolusi Stylometrik & AI Indicator
        $aiStylometrics = $evidenceData['ai_stylometrics'] ?? $evidenceData['v1_1_presentation']['v1_2_evolution']['ai_stylometrics'] ?? null;
        if (!$aiStylometrics && !empty($submissionText)) {
            $aiStylometrics = $this->computeFallbackStylometrics($submissionText);
        }

        // Resolusi Cross-Lingual Analysis
        $crossLingual = $evidenceData['cross_lingual'] ?? $evidenceData['v1_1_presentation']['v1_2_evolution']['cross_lingual'] ?? null;

        return view('dashboard/report', [
            'report'              => $report,
            'document'            => $document,
            'evidenceData'        => $evidenceData,
            'sources'             => $sources,
            'matches'             => $matches,
            'highAttentionCount'  => $highAttentionCount,
            'commonPassagesCount' => $commonPassagesCount,
            'submissionText'      => $submissionText,
            'sourceTexts'         => $sourceTexts,
            'aiStylometrics'      => $aiStylometrics,
            'crossLingual'        => $crossLingual,
        ]);
    }

    private function computeFallbackStylometrics(string $text): array
    {
        $rawSentences = preg_split('/[.!?]+(?:\s+|\n+|$)/u', $text, -1, PREG_SPLIT_NO_EMPTY);
        $sentences = [];
        $lengths = [];
        foreach ($rawSentences as $s) {
            $words = preg_split('/\s+/u', trim($s));
            if (count($words) >= 3) {
                $sentences[] = trim($s);
                $lengths[] = count($words);
            }
        }
        $totalWords = str_word_count($text);
        if ($totalWords < 50 || count($lengths) < 3) {
            return [
                'status' => 'insufficient_data',
                'ai_probability_score' => 0.0,
                'ai_confidence_level' => 'LOW',
                'verdict_label' => 'Data Teks Tidak Mencukupi',
                'summary_rationale' => 'Panjang naskah terlalu pendek untuk dianalisis profil stylometrinya.',
                'metrics' => [
                    'burstiness' => 0.5,
                    'lexical_diversity' => 0.0,
                    'transition_density' => 0.0,
                    'average_sentence_length' => 0.0,
                    'total_sentences_analyzed' => count($lengths),
                    'total_words_analyzed' => $totalWords,
                ],
                'anomalies' => []
            ];
        }

        $mean = array_sum($lengths) / count($lengths);
        $variance = 0.0;
        foreach ($lengths as $l) {
            $variance += pow($l - $mean, 2);
        }
        $stdDev = sqrt($variance / count($lengths));
        $burstiness = round($stdDev / ($mean ?: 1), 4);

        $markers = [
            'dalam era digital saat ini', 'perlu digarisbawahi bahwa', 'menarik untuk dicermati',
            'tidak dapat dipungkiri bahwa', 'memiliki peran yang sangat krusial', 'seiring dengan perkembangan zaman',
            'dapat disimpulkan bahwa secara keseluruhan', 'pada dasarnya hal ini menunjukkan',
            'merupakan aspek yang tak terpisahkan', 'memberikan kontribusi yang signifikan',
            'furthermore', 'moreover', 'in conclusion', 'it is important to note that', 'plays a crucial role in',
            'delves into', 'a multifaceted approach'
        ];
        $lower = mb_strtolower($text);
        $markerHits = 0;
        foreach ($markers as $m) {
            $markerHits += substr_count($lower, $m);
        }
        $transDensity = round(($markerHits / ($totalWords ?: 1)) * 1000, 2);

        $aiScore = 0.0;
        if ($burstiness < 0.25) $aiScore += 40.0;
        elseif ($burstiness < 0.32) $aiScore += 25.0;
        elseif ($burstiness < 0.40) $aiScore += 10.0;

        if ($transDensity >= 3.5) $aiScore += 35.0;
        elseif ($transDensity >= 2.0) $aiScore += 20.0;
        elseif ($transDensity >= 1.0) $aiScore += 10.0;

        if ($mean >= 18 && $mean <= 28 && $burstiness < 0.35) $aiScore += 15.0;

        $aiScore = min(100.0, max(0.0, round($aiScore, 2)));

        if ($aiScore >= 65.0) {
            $conf = 'HIGH';
            $label = 'Pola Susunan Terstruktur Khas AI (Tinggi)';
            $rationale = "Naskah menunjukkan variasi panjang kalimat yang sangat seragam (Burstiness: " . number_format($burstiness, 2) . ") disertai kepadatan frasa transisi generik (" . number_format($transDensity, 1) . "/1000 kata). Disarankan konfirmasi pemahaman konseptual secara lisan.";
        } elseif ($aiScore >= 38.0) {
            $conf = 'MODERATE';
            $label = 'Indikasi Parafrase AI Ringan / Berbantuan Mesin';
            $rationale = "Ditemukan beberapa segmen dengan keteraturan sintaksis khas mesin (Burstiness: " . number_format($burstiness, 2) . "). Naskah kemungkinan menggunakan alat bantu parafrase digital.";
        } else {
            $conf = 'LOW';
            $label = 'Gaya Bahasa Alami Manusia (Organik)';
            $rationale = "Struktur kalimat memiliki variasi ritme yang dinamis dan organik (Burstiness: " . number_format($burstiness, 2) . ") khas penulisan manusia mandiri.";
        }

        return [
            'status' => 'completed',
            'ai_probability_score' => $aiScore,
            'ai_confidence_level' => $conf,
            'verdict_label' => $label,
            'summary_rationale' => $rationale,
            'metrics' => [
                'burstiness' => $burstiness,
                'lexical_diversity' => 0.0,
                'transition_density' => $transDensity,
                'average_sentence_length' => round($mean, 2),
                'total_sentences_analyzed' => count($lengths),
                'total_words_analyzed' => $totalWords,
            ],
            'anomalies' => []
        ];
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
