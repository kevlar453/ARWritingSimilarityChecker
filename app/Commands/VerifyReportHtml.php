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

        $html = view('dashboard/report', [
            'report'              => $report,
            'document'            => $document,
            'evidenceData'        => $evidenceData,
            'sources'             => [],
            'matches'             => [],
            'highAttentionCount'  => 0,
            'commonPassagesCount' => 0,
            'submissionText'      => $document['title'] ?? '',
            'sourceTexts'         => [],
            'semanticLens'        => $evidenceData['semantic_lens'] ?? null,
            'aiStylometrics'      => null,
            'crossLingual'        => null,
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
        
        if (str_contains($html, 'Mandarin (LB: 72%)')) {
            CLI::write("[✓] VALIDASI BERHASIL: Lencana 'Mandarin (LB: 72%)' TERBUKTI MUNCUL DI HTML!", 'green');
        } else {
            CLI::error("[X] Lencana 'Mandarin (LB: 72%)' tidak ditemukan di HTML.");
        }

        if (str_contains($html, 'Rujukan Benchmark Lintas Bahasa [XLN-ZH-001]')) {
            CLI::write("[✓] Judul Dokumen Pembanding 'XLN-ZH-001' TERBUKTI TERTERA DI PASASE!", 'green');
        }

        if (str_contains($html, '89.1%') || str_contains($html, '89,1%')) {
            CLI::write("[✓] Skor Kemiripan Makna Puncak 89.1% TERBUKTI MUNCUL DI KPI!", 'green');
        }

        if (str_contains($html, 'Kebijakan Ambang Sensitivitas')) {
            CLI::write("[✓] Saklar Kebijakan Ambang (0,75 vs 0,70) AKTIF!", 'green');
        }
    }
}
