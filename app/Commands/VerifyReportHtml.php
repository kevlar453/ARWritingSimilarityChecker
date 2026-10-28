<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class VerifyReportHtml extends BaseCommand
{
    protected $group       = 'Testing';
    protected $name        = 'test:report-badge';
    protected $description = 'Verifikasi kemunculan badge kematangan bahasa pada HTML laporan';

    public function run(array $params)
    {
        $reportId = (int)($params[0] ?? 1196);
        $reportModel = new \App\Models\ReportModel();
        $documentModel = new \App\Models\DocumentModel();

        $report = $reportModel->find($reportId);
        if (!$report) {
            CLI::error("Report #{$reportId} tidak ditemukan!");
            return;
        }

        $document = $documentModel->find($report['document_id']);
        $evidenceData = json_decode($report['evidence_data'], true);

        CLI::write("Evidence semantic_lens keys: " . implode(', ', array_keys($evidenceData['semantic_lens'] ?? [])), 'cyan');
        if (isset($evidenceData['semantic_lens']['threshold_variants'])) {
            CLI::write("Variants: " . implode(', ', array_keys($evidenceData['semantic_lens']['threshold_variants'])), 'cyan');
            foreach ($evidenceData['semantic_lens']['threshold_variants'] as $k => $v) {
                CLI::write("Variant {$k} matches: " . count($v['top_matches'] ?? []), 'cyan');
                foreach ($v['top_matches'] ?? [] as $m) {
                    CLI::write("  - Doc ID: " . ($m['matched_document_id'] ?? '?') . " Score: " . ($m['semantic_score'] ?? '?'), 'cyan');
                }
            }
        }

        $db = \Config\Database::connect();
        $subChunks = $db->table('document_chunks')
                        ->select('text')
                        ->where('document_id', $report['document_id'])
                        ->orderBy('chunk_index', 'ASC')
                        ->orderBy('id', 'ASC')
                        ->get()->getResultArray();
        $submissionText = implode("\n\n", array_column($subChunks, 'text'));
        if (empty($submissionText)) {
            $submissionText = $document['title'] ?? '';
        }

        $detectedLang = \App\Libraries\LanguageDetector::detect(
            $submissionText,
            $document['title'] ?? ($report['document_title'] ?? '')
        );

        $semanticLens = $evidenceData['semantic_lens'] ?? null;
        if (!$semanticLens) {
            $overallSim = (float)($report['overall_similarity'] ?? 0.0);
            $inGreyZone = ($overallSim >= 5.0 && $overallSim < 25.0);
            $semanticLens = [
                'evaluated'            => true,
                'is_evaluated'         => true,
                'evaluation_reason'    => 'standard_baseline_audit',
                'semantic_score'       => 0.0,
                'semantic_cosine'      => 0.0,
                'paraphrase_detected'  => false,
                'zone'                 => $inGreyZone ? 'GREY_ZONE_RECHECK' : 'OUTSIDE_GREY_ZONE',
                'threshold_used'       => 0.75,
                'matched_chunks_count' => 0,
                'top_matches'          => [],
                'total_chunks_audited' => max(1, (int)(str_word_count($submissionText) / 200)),
                'model'                => 'paraphrase-multilingual-MiniLM-L12-v2',
                'threshold_variants'   => [
                    '0.75' => ['semantic_score' => 0.0, 'semantic_cosine' => 0.0, 'paraphrase_detected' => false, 'matched_chunks_count' => 0, 'top_matches' => []],
                    '0.70' => ['semantic_score' => 0.0, 'semantic_cosine' => 0.0, 'paraphrase_detected' => false, 'matched_chunks_count' => 0, 'top_matches' => []]
                ]
            ];
        }

        $html = view('dashboard/report', [
            'report'              => $report,
            'document'            => $document,
            'evidenceData'        => $evidenceData,
            'sources'             => [],
            'matches'             => [],
            'highAttentionCount'  => 0,
            'commonPassagesCount' => 0,
            'submissionText'      => $submissionText,
            'sourceTexts'         => [],
            'detectedLang'        => $detectedLang,
            'semanticLens'        => $semanticLens,
            'aiStylometrics'      => null,
            'crossLingual'        => $evidenceData['cross_lingual'] ?? null,
            'xgboostRisk'         => null,
            'jitHarvesting'       => null,
            'jitDiscoveredIds'    => [],
            'citationAudit'       => null,
            'evasionDetection'    => null,
            'geminiForensic'      => null,
            'corpusCount'         => 1492,
            'liveCorpusCount'     => 1492,
            'isOwner'             => true,
            'isSharedAccess'      => false,
            'userVotes'           => [],
            'userReview'          => null,
            'currentUser'         => null,
            'hasPurchased'        => true,
            'isAmbassador'        => false,
            'ambProfile'          => null,
        ]);

        CLI::write("=== HASIL RENDERING LAPORAN #{$reportId} ===", 'yellow');
        CLI::write("Bahasa Terdeteksi: {$detectedLang['name']} ({$detectedLang['code']})", 'cyan');
        CLI::write("Pasangan Bahasa : {$detectedLang['pair_code']} -> {$detectedLang['badge_label']}", 'cyan');

        // 1. Verifikasi Pernyataan Bahasa Eksplisit di Laporan
        if (str_contains($html, "Bahasa: <strong class=\"text-slate-800\">{$detectedLang['name']}</strong>") || str_contains($html, $detectedLang['name'])) {
            CLI::write("[✓] DETEKSI BAHASA EKSPLISIT: Bahasa '{$detectedLang['name']}' TERBUKTI TERCANTUM DI LAPORAN!", 'green');
        } else {
            CLI::error("[X] Bahasa tidak tercantum di laporan.");
        }

        // 2. Verifikasi Lencana Pasangan Relevan (misal ID-EN atau ID-ZH)
        if (str_contains($html, $detectedLang['badge_label'])) {
            CLI::write("[✓] LENCANA PASANGAN RELEVAN: '{$detectedLang['badge_label']}' TERBUKTI MUNCUL DI LAPORAN!", 'green');
        } else {
            CLI::error("[X] Lencana pasangan '{$detectedLang['badge_label']}' tidak ditemukan di HTML.");
        }

        // 3. Verifikasi Keberadaan Kode Pasangan (ID-EN / ID-ZH)
        if (str_contains($html, $detectedLang['pair_code'])) {
            CLI::write("[✓] KODE PASANGAN '{$detectedLang['pair_code']}' TERBUKTI DITEMUKAN PADA LAPORAN!", 'green');
        } else {
            CLI::error("[X] Kode pasangan '{$detectedLang['pair_code']}' tidak ditemukan di HTML.");
        }

        // 4. Verifikasi Teks 'Tervalidasi Terarah'
        if ($detectedLang['is_foreign'] && str_contains($html, 'Tervalidasi Terarah')) {
            CLI::write("[✓] Teks 'Tervalidasi Terarah' TERBUKTI HADIR DI HALAMAN LAPORAN!", 'green');
        }

        // Tampilkan Cuplikan Cuplikan Nyata di HTML
        CLI::write("\n--- CUPLIKAN HTML HEADER METADATA ---", 'yellow');
        if (preg_match('/<p class="text-xs sm:text-sm text-slate-500 mt-1 flex flex-wrap items-center.*?<\/p>/s', $html, $m)) {
            CLI::write(strip_tags($m[0]));
        }

        CLI::write("\n--- CUPLIKAN HTML PILAR SEMANTIK HEADER ---", 'yellow');
        if (preg_match('/<div class="flex items-center gap-2 flex-wrap">\s*<h3 class="font-bold text-base text-slate-800">.*?<\/div>/s', $html, $m2)) {
            CLI::write(strip_tags($m2[0]));
        }
    }
}
