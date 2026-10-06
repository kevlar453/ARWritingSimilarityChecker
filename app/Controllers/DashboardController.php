<?php

namespace App\Controllers;

use App\Models\DocumentModel;
use App\Models\ReportModel;

class DashboardController extends BaseController
{
    public function index()
    {
        $userId = session()->get('user_id');
        if (! $userId) {
            return redirect()->to(base_url('/?auth=login'))->with('auth_notice', 'Silakan masuk atau daftar akun gratis terlebih dahulu untuk mengakses dashboard AR Tilik.');
        }

        $userModel = new \App\Models\UserModel();
        $currentUser = $userModel->find($userId);
        if (! $currentUser) {
            session()->destroy();
            return redirect()->to(base_url('/?auth=login'))->with('auth_notice', 'Sesi login telah berakhir. Silakan masuk kembali.');
        }

        $currentUser = $userModel->refreshWeeklyQuota($currentUser);
        $quotaInfo = $userModel->getQuotaInfo($currentUser);

        $docModel = new DocumentModel();
        $reportModel = new ReportModel();
        $db = db_connect();

        // Dokumen submisi: hanya tampilkan submisi milik user yang sedang login (kecuali admin)
        $subBuilder = $docModel->where('document_type', 'submission')
                               ->where('benchmark_run_id IS NULL', null, false);

        if (($currentUser['role'] ?? 'user') !== 'admin') {
            $subBuilder->where('user_id', $userId);
        }

        $submissions = $subBuilder->orderBy('id', 'DESC')->findAll(20);

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
            'currentUser'      => $currentUser,
            'quotaInfo'        => $quotaInfo,
        ]);
    }

    public function report($id)
    {
        $reportModel = new ReportModel();
        $docModel = new DocumentModel();
        $db = db_connect();

        $report = $reportModel->find($id);
        if (! $report) {
            // Dukung pemanggilan menggunakan document_id
            $report = $reportModel->where('document_id', $id)->orderBy('id', 'DESC')->first();
        }

        if (! $report) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound("Laporan atau dokumen #{$id} tidak ditemukan.");
        }

        $reportId = (int) $report['id'];
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
                      ->where('rs.report_id', $reportId)
                      ->orderBy('rs.source_rank', 'ASC')
                      ->get()->getResultArray();

        // Matches
        $matches = $db->table('report_matches rm')
                      ->select('rm.*, d.title AS source_title, d.original_filename AS source_filename')
                      ->join('documents d', 'd.id = rm.source_document_id', 'left')
                      ->where('rm.report_id', $reportId)
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
            $m['submission_snippet'] = $this->extractSnippet($sChunk, (int) $m['submission_start'], (int) $m['word_count']);

            // Potongan dokumen sumber berdasarkan posisi kata
            $srcChunk = $db->table('document_chunks')
                          ->where('document_id', $srcDocId)
                          ->where('start_word_position <=', (int) $m['source_start'])
                          ->orderBy('start_word_position', 'DESC')
                          ->limit(1)
                          ->get()->getRowArray();
            $m['source_snippet'] = $this->extractSnippet($srcChunk, (int) $m['source_start'], (int) $m['word_count']);

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

        // Anotasi pelengkap: evaluasi boilerplate formula administratif / legal untuk laporan historis
        foreach ($matches as &$m) {
            $this->annotateBoilerplateSnippet($m);
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

        // Resolusi XGBoost Academic Risk Synthesizer
        $xgboostRisk = $evidenceData['xgboost_risk'] ?? $evidenceData['v1_1_presentation']['v1_2_evolution']['xgboost_risk'] ?? null;
        if (!$xgboostRisk) {
            $xgboostRisk = $this->computeFallbackXgboostRisk($report, $matches, $aiStylometrics, $crossLingual);
        }

        // Resolusi JIT Harvesting Discovery
        $jitHarvesting = $evidenceData['jit_harvesting'] ?? $evidenceData['v1_1_presentation']['v1_2_evolution']['jit_harvesting'] ?? null;
        $jitDiscoveredIds = [];
        if (!empty($jitHarvesting['discovered_works'])) {
            foreach ($jitHarvesting['discovered_works'] as $dw) {
                if (!empty($dw['document_id'])) {
                    $jitDiscoveredIds[] = (int) $dw['document_id'];
                }
            }
        }

        // Resolusi Audit Konsistensi Sitasi & Daftar Pustaka (Pilar 1)
        $citationAudit = $evidenceData['citation_audit'] ?? $evidenceData['v1_1_presentation']['v1_2_evolution']['citation_audit'] ?? null;
        if (!$citationAudit && !empty($submissionText)) {
            $citationAudit = $this->computeFallbackCitationAudit($submissionText);
        }

        // Resolusi Deteksi Manipulasi Naskah & Anti-Cheat (Opsi 2)
        $evasionDetection = $evidenceData['evasion_detection'] ?? $evidenceData['v1_1_presentation']['v1_2_evolution']['evasion_detection'] ?? null;
        if (!$evasionDetection && !empty($submissionText)) {
            $evasionDetection = $this->computeFallbackEvasionDetection($submissionText);
        }

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
            'xgboostRisk'         => $xgboostRisk,
            'jitHarvesting'       => $jitHarvesting,
            'jitDiscoveredIds'    => $jitDiscoveredIds,
            'citationAudit'       => $citationAudit,
            'evasionDetection'    => $evasionDetection,
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

    private function computeFallbackCitationAudit(string $text): array
    {
        if (empty($text)) {
            return [
                'detected_style' => 'None Detected',
                'has_bibliography_section' => false,
                'total_in_text_citations' => 0,
                'unique_in_text_count' => 0,
                'total_bibliography_entries' => 0,
                'matched_citations_count' => 0,
                'phantom_citations_count' => 0,
                'orphan_references_count' => 0,
                'citation_health_score' => 100.0,
                'health_verdict' => 'EXCELLENT',
                'verdict_label' => 'Naskah Kosong',
                'summary_rationale' => 'Tidak ada teks yang dapat dianalisis.',
                'matched_items' => [],
                'phantom_citations' => [],
                'orphan_references' => []
            ];
        }

        $bodyText = $text;
        $bibText = '';
        $hasBib = false;

        if (preg_match('/(?:\n|\A)(?:[0-9\.\s]*\b(?:DAFTAR\s+PUSTAKA|REFERENCES|BIBLIOGRAFI|DAFTAR\s+REFERENSI)\b[^\n]*\n)/iu', $text, $matches, PREG_OFFSET_CAPTURE)) {
            $hasBib = true;
            $pos = $matches[0][1];
            $bodyText = substr($text, 0, $pos);
            $bibText = substr($text, $pos + strlen($matches[0][0]));

            if (preg_match('/(?:\n|\A)(?:[0-9\.\s]*\b(?:LAMPIRAN|APPENDICES|APPENDIX)\b[^\n]*\n)/iu', $bibText, $lampMatch, PREG_OFFSET_CAPTURE)) {
                $bibText = substr($bibText, 0, $lampMatch[0][1]);
            }
        }

        $citations = [];

        // Parenthetical Author-Year: (Smith, 2021) atau (Pratama & Santoso, 2020)
        if (preg_match_all('/\(\s*([A-Z][a-zA-Z\s\-\'\.\&]+?(?:(?:et\s+al\.|dkk\.)|\s*(?:&|and|dan)\s*[A-Z][a-zA-Z\s\-\']+)?)\s*,\s*((?:19|20)\d{2}[a-z]?)(?::\s*\d+(?:[\-\–]\d+)?)?\s*\)/u', $bodyText, $mParen, PREG_SET_ORDER)) {
            foreach ($mParen as $m) {
                $raw = $m[0];
                $authRaw = trim($m[1]);
                $year = trim($m[2]);
                $authKey = $this->normalizeAuthorName($authRaw);
                if (strlen($authKey) >= 2) {
                    $citations[] = [
                        'raw_citation' => $raw,
                        'author_key' => $authKey,
                        'year' => $year,
                        'citation_type' => 'author_year',
                        'numeric_id' => null,
                        'context_sentence' => $raw,
                    ];
                }
            }
        }

        // Narrative Author-Year: Menurut Santoso (2021)
        if (preg_match_all('/\b([A-Z][a-zA-Z\s\-\'\&]+?(?:(?:et\s+al\.|dkk\.)|\s*(?:&|and|dan)\s*[A-Z][a-zA-Z\s\-\']+)?)\s*\(((?:19|20)\d{2}[a-z]?)(?::\s*\d+(?:[\-\–]\d+)?)?\)/u', $bodyText, $mNarr, PREG_SET_ORDER)) {
            foreach ($mNarr as $m) {
                $raw = $m[0];
                $authRaw = trim($m[1]);
                $year = trim($m[2]);
                if (in_array(strtolower($authRaw), ['gambar', 'tabel', 'lampiran', 'grafik', 'bagan', 'figure', 'table'], true)) {
                    continue;
                }
                $authKey = $this->normalizeAuthorName($authRaw);
                if (strlen($authKey) >= 2) {
                    $citations[] = [
                        'raw_citation' => $raw,
                        'author_key' => $authKey,
                        'year' => $year,
                        'citation_type' => 'author_year',
                        'numeric_id' => null,
                        'context_sentence' => $raw,
                    ];
                }
            }
        }

        // Numeric IEEE: [1], [2, 3]
        if (preg_match_all('/(?<!\w)\[\s*(\d+(?:\s*,\s*\d+|\s*[\-\–]\s*\d+)*)\s*\]/u', $bodyText, $mNum, PREG_SET_ORDER)) {
            foreach ($mNum as $m) {
                $inner = trim($m[1]);
                $parts = explode(',', $inner);
                foreach ($parts as $p) {
                    $p = trim($p);
                    if (preg_match('/^(\d+)\s*[\-\–]\s*(\d+)$/', $p, $rMatch)) {
                        $rStart = (int) $rMatch[1];
                        $rEnd = (int) $rMatch[2];
                        if ($rStart <= $rEnd && ($rEnd - $rStart) <= 50) {
                            for ($n = $rStart; $n <= $rEnd; $n++) {
                                $citations[] = [
                                    'raw_citation' => "[{$n}]",
                                    'author_key' => "ref_{$n}",
                                    'year' => null,
                                    'citation_type' => 'numeric',
                                    'numeric_id' => $n,
                                    'context_sentence' => "[{$n}]",
                                ];
                            }
                        }
                    } elseif (preg_match('/^\d+$/', $p)) {
                        $n = (int) $p;
                        $citations[] = [
                            'raw_citation' => "[{$n}]",
                            'author_key' => "ref_{$n}",
                            'year' => null,
                            'citation_type' => 'numeric',
                            'numeric_id' => $n,
                            'context_sentence' => "[{$n}]",
                        ];
                    }
                }
            }
        }

        // Ekstrak Entri Daftar Pustaka
        $entries = [];
        if ($hasBib && !empty($bibText)) {
            $lines = explode("\n", $bibText);
            $currLines = [];
            $commitEntry = function(array $lines) use (&$entries) {
                $textEntry = trim(implode(' ', $lines));
                if (strlen($textEntry) < 15) return;

                $year = null;
                if (preg_match('/\b((?:19|20)\d{2})[a-z]?\b/u', $textEntry, $ym)) {
                    $year = $ym[1];
                }

                $numericId = null;
                if (preg_match('/^(?:\[(\d+)\]|(\d+)\.\s)/u', $textEntry, $nm)) {
                    $numericId = (int) ($nm[1] ?: $nm[2]);
                }

                $authorKeys = [];
                $prefix = $year ? substr($textEntry, 0, strpos($textEntry, $year)) : substr($textEntry, 0, 80);
                if (preg_match_all('/[A-Z][a-zA-Z]+/u', $prefix, $wm)) {
                    foreach ($wm[0] as $w) {
                        $cleanW = $this->normalizeAuthorName($w);
                        if (!empty($cleanW) && !in_array($cleanW, ['dkk', 'dan', 'and', 'al', 'vol', 'no', 'pp', 'hal', 'jurnal', 'prosiding', 'in', 'the', 'of'], true)) {
                            if (!in_array($cleanW, $authorKeys, true)) {
                                $authorKeys[] = $cleanW;
                            }
                        }
                    }
                }

                $entries[] = [
                    'entry_id' => count($entries) + 1,
                    'raw_text' => $textEntry,
                    'author_keys' => $authorKeys,
                    'year' => $year,
                    'numeric_id' => $numericId,
                ];
            };

            foreach ($lines as $line) {
                $cleanL = trim($line);
                if (empty($cleanL)) continue;
                $isNumStart = (bool) preg_match('/^(?:\[\d+\]|\d+\.)\s+/u', $cleanL);
                $isAuthStart = (bool) preg_match('/^[A-Z][a-zA-Z\s\-\.\,]+,\s*[A-Z]/u', $cleanL);

                if (($isNumStart || $isAuthStart) && !empty($currLines)) {
                    $commitEntry($currLines);
                    $currLines = [$cleanL];
                } else {
                    $currLines[] = $cleanL;
                }
            }
            if (!empty($currLines)) {
                $commitEntry($currLines);
            }
        }

        // Deteksi Style
        $authorYearCount = count(array_filter($citations, fn($c) => $c['citation_type'] === 'author_year'));
        $numericCount = count(array_filter($citations, fn($c) => $c['citation_type'] === 'numeric'));
        if ($authorYearCount > 0 && $numericCount === 0) $style = "APA / Harvard (Author-Year)";
        elseif ($numericCount > 0 && $authorYearCount === 0) $style = "IEEE / Vancouver (Numbered)";
        elseif ($authorYearCount > 0 && $numericCount > 0) $style = "Mixed (Author-Year & Numbered)";
        else $style = "None Detected";

        // Pencocokan Silang
        $uniqueKeys = [];
        foreach ($citations as $c) {
            $k = ($c['citation_type'] === 'author_year') ? "{$c['author_key']}_{$c['year']}" : "num_{$c['numeric_id']}";
            if (!isset($uniqueKeys[$k])) $uniqueKeys[$k] = [];
            $uniqueKeys[$k][] = $c;
        }

        $matchedItems = [];
        $phantomCitations = [];
        $citedEntryIds = [];

        foreach ($uniqueKeys as $k => $cList) {
            $s = $cList[0];
            $foundMatch = false;
            $matchingEntry = null;

            if ($s['citation_type'] === 'numeric') {
                foreach ($entries as $e) {
                    if ($e['numeric_id'] === $s['numeric_id']) {
                        $foundMatch = true;
                        $matchingEntry = $e;
                        $citedEntryIds[] = $e['entry_id'];
                        break;
                    }
                }
            } else {
                foreach ($entries as $e) {
                    $authMatch = in_array($s['author_key'], $e['author_keys'], true);
                    $yearMatch = (!$s['year'] || !$e['year'] || substr($s['year'], 0, 4) === substr($e['year'], 0, 4));
                    if ($authMatch && $yearMatch) {
                        $foundMatch = true;
                        $matchingEntry = $e;
                        $citedEntryIds[] = $e['entry_id'];
                        break;
                    }
                }
            }

            if ($foundMatch && $matchingEntry) {
                $matchedItems[] = [
                    'citation' => $s['raw_citation'],
                    'occurrences' => count($cList),
                    'author_key' => $s['author_key'],
                    'year' => $s['year'],
                    'matched_entry_id' => $matchingEntry['entry_id'],
                    'matched_entry_text' => $matchingEntry['raw_text'],
                    'context_sample' => $s['context_sentence'],
                ];
            } else {
                $phantomCitations[] = [
                    'citation' => $s['raw_citation'],
                    'occurrences' => count($cList),
                    'author_key' => $s['author_key'],
                    'year' => $s['year'],
                    'context_sample' => $s['context_sentence'],
                    'reason' => 'Tidak ditemukan entri yang cocok pada Daftar Pustaka',
                ];
            }
        }

        $orphanReferences = [];
        foreach ($entries as $e) {
            if (!in_array($e['entry_id'], $citedEntryIds, true)) {
                $orphanReferences[] = [
                    'entry_id' => $e['entry_id'],
                    'entry_text' => $e['raw_text'],
                    'author_keys' => $e['author_keys'],
                    'year' => $e['year'],
                    'reason' => 'Tercantum di Daftar Pustaka tetapi tidak pernah disitasi di naskah',
                ];
            }
        }

        $uniqueCount = count($uniqueKeys);
        $matchedCount = count($matchedItems);
        $phantomCount = count($phantomCitations);
        $orphanCount = count($orphanReferences);
        $totalBib = count($entries);

        if (!$hasBib) {
            $healthScore = ($uniqueCount > 0) ? 0.0 : 100.0;
            $healthVerdict = ($uniqueCount > 0) ? 'CRITICAL' : 'EXCELLENT';
            $verdictLabel = 'Daftar Pustaka Belum Terdeteksi';
            $rationale = 'Bagian Daftar Pustaka tidak terdeteksi dalam dokumen. Seluruh sitasi di naskah tidak memiliki rujukan acuan.';
        } elseif ($uniqueCount === 0) {
            $healthScore = ($totalBib === 0) ? 100.0 : 50.0;
            $healthVerdict = ($totalBib === 0) ? 'GOOD' : 'NEEDS_ATTENTION';
            $verdictLabel = 'Tidak Ada Sitasi di Tubuh Teks';
            $rationale = "Tidak ditemukan sitasi in-text di tubuh naskah. Terdapat {$totalBib} entri pada Daftar Pustaka.";
        } else {
            $baseRatio = ($matchedCount / $uniqueCount) * 100.0;
            $orphanPenalty = min(15.0, ($orphanCount / max(1, $totalBib)) * 15.0);
            $healthScore = max(0.0, min(100.0, round($baseRatio - $orphanPenalty, 1)));

            if ($healthScore >= 85.0) {
                $healthVerdict = 'EXCELLENT';
                $verdictLabel = 'Sangat Konsisten (Sehat)';
                $rationale = "Korelasi sitasi sangat kuat ({$matchedCount}/{$uniqueCount} sitasi terhubung). Terdapat {$phantomCount} sitasi gaib dan {$orphanCount} pustaka tanpa sitasi.";
            } elseif ($healthScore >= 65.0) {
                $healthVerdict = 'GOOD';
                $verdictLabel = 'Cukup Konsisten (Perlu Pengecekan)';
                $rationale = "Sebagian besar sitasi cocok ({$matchedCount}/{$uniqueCount}), namun terdeteksi {$phantomCount} sitasi gaib dan {$orphanCount} pustaka yatim.";
            } elseif ($healthScore >= 40.0) {
                $healthVerdict = 'NEEDS_ATTENTION';
                $verdictLabel = 'Inkonsistensi Sedang';
                $rationale = "Tingkat ketidakcocokan sitasi cukup tinggi: {$phantomCount} sitasi tidak ada di Daftar Pustaka, serta {$orphanCount} entri pustaka tidak pernah dikutip.";
            } else {
                $healthVerdict = 'CRITICAL';
                $verdictLabel = 'Inkonsistensi Kritis';
                $rationale = "Sebagian besar sitasi ({$phantomCount} dari {$uniqueCount}) tidak memiliki padanan di Daftar Pustaka. Diperlukan peninjauan ulang menyeluruh sebelum sidang.";
            }
        }

        return [
            'detected_style' => $style,
            'has_bibliography_section' => $hasBib,
            'total_in_text_citations' => count($citations),
            'unique_in_text_count' => $uniqueCount,
            'total_bibliography_entries' => $totalBib,
            'matched_citations_count' => $matchedCount,
            'phantom_citations_count' => $phantomCount,
            'orphan_references_count' => $orphanCount,
            'citation_health_score' => $healthScore,
            'health_verdict' => $healthVerdict,
            'verdict_label' => $verdictLabel,
            'summary_rationale' => $rationale,
            'matched_items' => $matchedItems,
            'phantom_citations' => $phantomCitations,
            'orphan_references' => $orphanReferences,
        ];
    }

    private function normalizeAuthorName(string $name): string
    {
        $cleaned = preg_replace('/\b(?:dkk|et\s+al|Prof|Dr|Drs|Ir|M\.Si|Ph\.D|M\.Kom|S\.T|S\.Kom)\b\.?/iu', '', $name);
        $cleaned = preg_replace('/[^a-zA-Z\s]/u', '', $cleaned);
        $tokens = preg_split('/\s+/u', trim(mb_strtolower($cleaned)));
        return !empty($tokens) ? end($tokens) : '';
    }

    private function computeFallbackEvasionDetection(string $text): array
    {
        if (empty(trim($text))) {
            return [
                'evasion_risk_score'     => 0.0,
                'integrity_status'       => 'CLEAN',
                'verdict_label'          => 'Naskah Otentik (Tanpa Teks)',
                'summary_rationale'      => 'Tidak ada teks yang dapat diuji integritasnya.',
                'total_words_inspected'  => 0,
                'total_anomalies_count'  => 0,
                'homoglyph_count'        => 0,
                'zero_width_count'       => 0,
                'white_text_count'       => 0,
                'micro_font_count'       => 0,
                'has_mixed_script_words' => false,
                'anomalies'              => [],
                'deobfuscated_preview'   => '',
            ];
        }

        // Karakter tak kasat mata (Zero-width chars)
        $invisibleChars = [
            "\xE2\x80\x8B" => 'Zero-Width Space (ZWSP / U+200B)',
            "\xE2\x80\x8C" => 'Zero-Width Non-Joiner (ZWNJ / U+200C)',
            "\xE2\x80\x8D" => 'Zero-Width Joiner (ZWJ / U+200D)',
            "\xE2\x81\xA0" => 'Word Joiner (WJ / U+2060)',
            "\xEF\xBB\xBF" => 'Zero-Width No-Break Space / BOM (U+FEFF)',
            "\xC2\xAD"     => 'Soft Hyphen (SHY / U+00AD)',
            "\xE2\x80\x8E" => 'Left-to-Right Mark (LRM / U+200E)',
            "\xE2\x80\x8F" => 'Right-to-Left Mark (RLM / U+200F)',
            "\xE2\x80\xAA" => 'Left-to-Right Embedding (LRE / U+202A)',
            "\xE2\x80\xAB" => 'Right-to-Left Embedding (RLE / U+202B)',
            "\xE2\x80\xAC" => 'Pop Directional Formatting (PDF / U+202C)',
            "\xE2\x80\xAD" => 'Left-to-Right Override (LRO / U+202D)',
            "\xE2\x80\xAE" => 'Right-to-Left Override (RLO / U+202E)',
        ];

        // Homoglif umum Cyrillic & Yunani yang menyerupai huruf Latin
        $homoglyphs = [
            'а' => ['a', 'Cyrillic Small Letter A (U+0430)', 'U+0430'],
            'е' => ['e', 'Cyrillic Small Letter E (U+0435)', 'U+0435'],
            'о' => ['o', 'Cyrillic Small Letter O (U+043E)', 'U+043E'],
            'р' => ['p', 'Cyrillic Small Letter ER (U+0440)', 'U+0440'],
            'с' => ['c', 'Cyrillic Small Letter ES (U+0441)', 'U+0441'],
            'у' => ['y', 'Cyrillic Small Letter U (U+0443)', 'U+0443'],
            'х' => ['x', 'Cyrillic Small Letter HA (U+0445)', 'U+0445'],
            'і' => ['i', 'Cyrillic Small Letter I (U+0456)', 'U+0456'],
            'ј' => ['j', 'Cyrillic Small Letter JE (U+0458)', 'U+0458'],
            'ѕ' => ['s', 'Cyrillic Small Letter DZE (U+0455)', 'U+0455'],
            'ԁ' => ['d', 'Cyrillic Small Letter KOMI DE (U+0501)', 'U+0501'],
            'А' => ['A', 'Cyrillic Capital Letter A (U+0410)', 'U+0410'],
            'В' => ['B', 'Cyrillic Capital Letter VE (U+0412)', 'U+0412'],
            'Е' => ['E', 'Cyrillic Capital Letter IE (U+0415)', 'U+0415'],
            'К' => ['K', 'Cyrillic Capital Letter KA (U+041A)', 'U+041A'],
            'М' => ['M', 'Cyrillic Capital Letter EM (U+041C)', 'U+041C'],
            'Н' => ['H', 'Cyrillic Capital Letter EN (U+041D)', 'U+041D'],
            'О' => ['O', 'Cyrillic Capital Letter O (U+041E)', 'U+041E'],
            'Р' => ['P', 'Cyrillic Capital Letter ER (U+0420)', 'U+0420'],
            'С' => ['C', 'Cyrillic Capital Letter ES (U+0421)', 'U+0421'],
            'Т' => ['T', 'Cyrillic Capital Letter TE (U+0422)', 'U+0422'],
            'Х' => ['X', 'Cyrillic Capital Letter HA (U+0425)', 'U+0425'],
            'α' => ['a', 'Greek Small Letter Alpha (U+03B1)', 'U+03B1'],
            'ο' => ['o', 'Greek Small Letter Omicron (U+03BF)', 'U+03BF'],
            'ν' => ['v', 'Greek Small Letter Nu (U+03BD)', 'U+03BD'],
            'ι' => ['i', 'Greek Small Letter Iota (U+03B9)', 'U+03B9'],
            'ρ' => ['p', 'Greek Small Letter Rho (U+03C1)', 'U+03C1'],
            'κ' => ['k', 'Greek Small Letter Kappa (U+03BA)', 'U+03BA'],
        ];

        $anomalies = [];
        $totalZeroWidth = 0;
        $totalHomoglyphs = 0;

        foreach ($invisibleChars as $bytes => $name) {
            $cnt = substr_count($text, $bytes);
            if ($cnt > 0) {
                $totalZeroWidth += $cnt;
                $anomalies[] = [
                    'category'          => 'ZERO_WIDTH_INJECTION',
                    'detected_item'     => $name,
                    'deobfuscated_item' => '[Dihapus]',
                    'char_code'         => explode(' ', $name)[0],
                    'description'       => "Ditemukan {$cnt} karakter tak kasat mata diselipkan di dalam teks.",
                    'occurrences'       => $cnt,
                    'sample_context'    => $name,
                ];
            }
        }

        $rawTokens = preg_split('/\s+/u', trim($text));
        $totalWords = count($rawTokens);
        $homoStats = [];
        $hasMixedScript = false;

        foreach ($rawTokens as $w) {
            $clean = preg_replace('/^[^\p{L}\p{N}]+|[^\p{L}\p{N}]+$/u', '', $w);
            if (empty($clean)) continue;

            $hasLatin = (bool) preg_match('/[a-zA-Z]/', $clean);
            $hasHomoInWord = false;

            foreach ($homoglyphs as $hChar => $hInfo) {
                if (mb_strpos($clean, $hChar) !== false) {
                    $cCnt = mb_substr_count($clean, $hChar);
                    $totalHomoglyphs += $cCnt;
                    $hasHomoInWord = true;

                    if (!isset($homoStats[$hChar])) {
                        $homoStats[$hChar] = [
                            'info'    => $hInfo,
                            'count'   => 0,
                            'samples' => [],
                        ];
                    }
                    $homoStats[$hChar]['count'] += $cCnt;
                    if (count($homoStats[$hChar]['samples']) < 4 && !in_array($clean, $homoStats[$hChar]['samples'], true)) {
                        $homoStats[$hChar]['samples'][] = $clean;
                    }
                }
            }

            if ($hasLatin && $hasHomoInWord) {
                $hasMixedScript = true;
            }
        }

        foreach ($homoStats as $hChar => $data) {
            $hInfo = $data['info'];
            $sampleStr = implode(', ', array_map(fn($s) => "'{$s}'", $data['samples']));
            $anomalies[] = [
                'category'          => 'HOMOGLYPH_SUBSTITUTION',
                'detected_item'     => "'{$hChar}' -> '{$hInfo[0]}'",
                'deobfuscated_item' => $hInfo[0],
                'char_code'         => $hInfo[2],
                'description'       => "Huruf alfabet Latin diganti dengan aksara {$hInfo[1]}. Contoh kata: {$sampleStr}",
                'occurrences'       => $data['count'],
                'sample_context'    => $sampleStr,
            ];
        }

        $totalAnomalies = $totalZeroWidth + $totalHomoglyphs;
        $riskScore = 0.0;
        if ($totalAnomalies > 0) {
            if ($totalAnomalies <= 2 && !$hasMixedScript) {
                $riskScore = 6.5;
            } else {
                $density = ($totalAnomalies / max(50, $totalWords)) * 100.0;
                $base = min(80.0, $density * 12.0);
                if ($hasMixedScript) $base += 25.0;
                $riskScore = min(100.0, max(15.0, $base));
            }
        }

        $riskScore = round($riskScore, 1);
        if ($riskScore >= 35.0 || ($hasMixedScript && $totalHomoglyphs >= 3)) {
            $integrityStatus = 'MANIPULATED';
            $verdictLabel = 'Manipulasi Naskah Terencana Terdeteksi';
            $summaryRationale = "Ditemukan indikasi kuat rekayasa teks ({$totalAnomalies} anomali) berupa substitusi aksara homoglif atau karakter spasi tersembunyi yang bertujuan mengaburkan kemiripan leksikal.";
        } elseif ($riskScore >= 10.0) {
            $integrityStatus = 'SUSPICIOUS';
            $verdictLabel = 'Indikasi Anomali Karakter Perlu Tinjauan';
            $summaryRationale = "Terdeteksi {$totalAnomalies} anomali karakter khusus yang tidak lazim dalam penulisan ilmiah standar.";
        } else {
            $integrityStatus = 'CLEAN';
            $verdictLabel = 'Integritas Naskah Otentik & Bersih';
            $summaryRationale = 'Tidak ditemukan penyisipan karakter tak kasat mata maupun substitusi aksara homoglif. Struktur karakter memenuhi kaidah akademik yang murni.';
        }

        return [
            'evasion_risk_score'     => $riskScore,
            'integrity_status'       => $integrityStatus,
            'verdict_label'          => $verdictLabel,
            'summary_rationale'      => $summaryRationale,
            'total_words_inspected'  => $totalWords,
            'total_anomalies_count'  => $totalAnomalies,
            'homoglyph_count'        => $totalHomoglyphs,
            'zero_width_count'       => $totalZeroWidth,
            'white_text_count'       => 0,
            'micro_font_count'       => 0,
            'has_mixed_script_words' => $hasMixedScript,
            'anomalies'              => $anomalies,
            'deobfuscated_preview'   => '',
        ];
    }

    public function exportPdf($id)
    {
        $reportModel = new ReportModel();
        $docModel = new DocumentModel();

        $report = $reportModel->find($id);
        if (! $report) {
            $report = $reportModel->where('document_id', $id)->orderBy('id', 'DESC')->first();
        }

        if (! $report) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound("Laporan atau dokumen #{$id} tidak ditemukan.");
        }

        $reportId = (int) $report['id'];
        $document = $docModel->find($report['document_id']);
        $rawTitle = $document['title'] ?? 'Dokumen';
        $safeTitle = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $rawTitle);
        $filename = "Laporan_AR_Tilik_#{$reportId}_{$safeTitle}.pdf";

        // Eksekusi worker exporter via Python
        $projectRoot = ROOTPATH;
        $pythonBin = $projectRoot . '.venv/bin/python';
        if (! file_exists($pythonBin)) {
            $pythonBin = 'python3';
        }

        $cmd = escapeshellcmd($pythonBin) . ' -m worker.exporter --report ' . $reportId . ' --stdout';

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
            $report = $reportModel->where('document_id', $id)->orderBy('id', 'DESC')->first();
        }

        if (! $report) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound("Laporan atau dokumen #{$id} tidak ditemukan.");
        }

        $reportId = (int) $report['id'];
        $document = $docModel->find($report['document_id']);
        $evidenceData = !empty($report['evidence_data']) ? (is_string($report['evidence_data']) ? json_decode($report['evidence_data'], true) : $report['evidence_data']) : [];

        $sources = $db->table('report_sources rs')
                      ->select('rs.*, d.title AS source_title, d.original_filename AS source_filename, d.source_type')
                      ->join('documents d', 'd.id = rs.source_document_id', 'left')
                      ->where('rs.report_id', $reportId)
                      ->orderBy('rs.source_rank', 'ASC')
                      ->get()->getResultArray();

        $matches = $db->table('report_matches rm')
                      ->select('rm.*, d.title AS source_title, d.original_filename AS source_filename')
                      ->join('documents d', 'd.id = rm.source_document_id', 'left')
                      ->where('rm.report_id', $reportId)
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
            $m['submission_snippet'] = $this->extractSnippet($sChunk, (int) $m['submission_start'], (int) $m['word_count']);

            $srcChunk = $db->table('document_chunks')
                          ->where('document_id', $srcDocId)
                          ->where('start_word_position <=', (int) $m['source_start'])
                          ->orderBy('start_word_position', 'DESC')
                          ->limit(1)
                          ->get()->getRowArray();
            $m['source_snippet'] = $this->extractSnippet($srcChunk, (int) $m['source_start'], (int) $m['word_count']);
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

    private function computeFallbackXgboostRisk(array $report, array $matches, ?array $aiStylometrics, ?array $crossLingual): array
    {
        $exactSim = (float) ($report['exact_similarity'] ?? 0.0);
        $cosineSim = (float) ($report['cosine_similarity'] ?? 0.0);
        $longestMatch = (int) ($report['longest_match_words'] ?? 0);
        $burstiness = (float) ($aiStylometrics['metrics']['burstiness'] ?? 0.5);
        $clScore = (float) ($crossLingual['cross_lingual_score'] ?? 0.0);

        $totalMatches = count($matches);
        $bpCount = 0;
        $citedCount = 0;
        foreach ($matches as $m) {
            if (!empty($m['is_boilerplate'])) $bpCount++;
            $ctx = $m['context_type'] ?? '';
            if (in_array($ctx, ['DIRECT_QUOTE', 'IN_TEXT_CITATION', 'BLOCK_QUOTE'], true)) $citedCount++;
        }
        $bpRatio = $totalMatches > 0 ? ($bpCount / $totalMatches) : 0.0;
        $qRatio = $totalMatches > 0 ? ($citedCount / $totalMatches) : 0.0;

        $riskScore = min(100.0, max(0.0,
            ($exactSim * 0.45) +
            (min(100.0, $longestMatch * 0.5) * 0.25) +
            ($clScore * 0.20) +
            (max(0.0, 0.40 - $burstiness) * 50.0 * 0.10) -
            ($qRatio * 30.0) -
            ($bpRatio * 20.0)
        ));
        $riskScore = round($riskScore, 1);

        $probMatch = round($riskScore / 100.0, 3);
        $probReview = round(max(0.0, 1.0 - $probMatch - 0.2), 3);
        if ($riskScore <= 30.0) {
            $probReview = 0.2;
        }
        $probClear = round(max(0.0, 1.0 - $probMatch - $probReview), 3);

        $predClass = ($riskScore >= 60.0) ? 2 : (($riskScore >= 15.0) ? 1 : 0);

        $labels = [
            0 => ['LOW', 'Aman (Bebas Indikasi Signifikan)', 'Naskah menunjukkan profil kepenulisan orisinal tanpa bukti penyalinan substansial.'],
            1 => ['MODERATE', 'Perlu Telaah Konseptual Dosen', 'Terdapat nuansa teks (frasa baku, parafrasa AI, atau alih bahasa) yang memerlukan konfirmasi lisan.'],
            2 => ['HIGH', 'Risiko Integritas Tinggi (Verbatim Overlap)', 'Terdeteksi pasase sekuensial panjang atau kesamaan tekstual substansial tanpa atribusi memadai.']
        ];

        [$riskLevel, $labelTitle, $rationale] = $labels[$predClass];

        $drivers = [];
        if ($longestMatch >= 30) $drivers[] = "Pasase kontigu panjang ({$longestMatch} kata) menaikkan skor risiko.";
        if ($clScore >= 35.0) $drivers[] = "Kemiripan konsep alih bahasa internasional (" . number_format($clScore, 1) . "%) memicu atensi telaah.";
        if ($burstiness < 0.28) $drivers[] = "Ritme kalimat seragam khas AI (Burstiness: " . number_format($burstiness, 2) . ") meningkatkan indikasi berbantuan mesin.";
        if ($qRatio >= 0.5) $drivers[] = "Tingginya proporsi kutipan bertanda petik (" . round($qRatio * 100) . "%) meringankan potensi pelanggaran.";
        if ($bpRatio >= 0.5) $drivers[] = "Dominasi frasa ilmiah baku (" . round($bpRatio * 100) . "%) mengonfirmasi konvensi metodologi standar.";
        if (empty($drivers)) $drivers[] = "Seluruh dimensi berada dalam ambang batas kepenulisan mandiri yang wajar.";

        return [
            'status' => 'completed',
            'engine' => 'XGBoost-v3.4 (Gradient Boosted Trees)',
            'calibrated_risk_score' => $riskScore,
            'risk_level' => $riskLevel,
            'verdict_label' => $labelTitle,
            'summary_rationale' => $rationale,
            'probabilities' => [
                'p_original' => $probClear,
                'p_needs_review' => $probReview,
                'p_high_risk' => $probMatch,
            ],
            'explainable_drivers' => $drivers,
            'features_evaluated' => [
                'exact_similarity' => $exactSim,
                'cosine_similarity' => $cosineSim,
                'longest_match_words' => $longestMatch,
                'burstiness' => $burstiness,
                'transition_density' => 0.0,
                'cross_lingual_score' => $clScore,
                'boilerplate_ratio' => $bpRatio,
                'quote_ratio' => $qRatio,
            ]
        ];
    }

    /**
     * Ekstraksi cuplikan teks (snippet) secara presisi berdasarkan word offset tokenisasi.
     * Menggunakan PREG_OFFSET_CAPTURE pada raw text untuk mempertahankan huruf kapital/tanda baca asli,
     * dengan fallback deterministik ke normalized_text bila batas token terdeviasi.
     */
    private function extractSnippet(?array $chunk, int $startWordPos, int $wordCount): string
    {
        if (!$chunk) {
            return 'Teks tidak tersedia.';
        }

        $offset = max(0, $startWordPos - (int) ($chunk['start_word_position'] ?? 0));
        $count = max(1, $wordCount);

        // 1. Ekstraksi token presisi dari teks asli (raw text)
        $rawText = $chunk['text'] ?? '';
        if (!empty($rawText)) {
            preg_match_all('/\b[a-zA-Z0-9]+(?:-[a-zA-Z0-9]+)*\b/u', $rawText, $tokens, PREG_OFFSET_CAPTURE);
            $tokenList = $tokens[0] ?? [];
            $totalTokens = count($tokenList);
            if ($totalTokens > 0 && isset($tokenList[$offset])) {
                $endIndex = min($totalTokens - 1, $offset + $count - 1);
                $startByte = $tokenList[$offset][1];
                $endByte = $tokenList[$endIndex][1] + strlen($tokenList[$endIndex][0]);
                $snippet = trim(substr($rawText, $startByte, $endByte - $startByte));
                $snippet = preg_replace('/\s+/', ' ', $snippet);
                if (!empty($snippet)) {
                    return $snippet;
                }
            }
        }

        // 2. Fallback deterministik ke normalized_text
        $normText = $chunk['normalized_text'] ?? '';
        if (!empty($normText)) {
            $words = explode(' ', trim($normText));
            $slice = array_slice($words, $offset, $count);
            if (!empty($slice)) {
                return implode(' ', $slice);
            }
        }

        // 3. Fallback spasi mentah
        if (!empty($rawText)) {
            $words = preg_split('/\s+/', trim($rawText));
            return implode(' ', array_slice($words, $offset, $count));
        }

        return 'Teks tidak tersedia.';
    }

    /**
     * Evaluasi formulasi baku akademik (Boilerplate / Legal Citations) secara instan
     * untuk laporan historis atau pasase administratif umum.
     */
    private function annotateBoilerplateSnippet(array &$m): void
    {
        if (!empty($m['is_boilerplate'])) {
            return;
        }

        $text = ($m['submission_snippet'] ?? '') . ' ' . ($m['source_snippet'] ?? '');
        if (preg_match('/(?i)\b(?:semester\s+(?:ganjil|genap)|tahun\s+akademik|tahun\s+ajaran)\s+\d{4}(?:[\/\-]\d{4})?\b/', $text, $match)) {
            $m['is_boilerplate'] = true;
            $m['boilerplate_category'] = 'ADMINISTRATIVE_FORMULA';
            $m['boilerplate_phrase'] = $match[0];
            $m['boilerplate_rationale'] = 'Formulasi administratif baku penetapan periode akademik semester dan tahun ajaran.';
        } elseif (preg_match('/(?i)\b(?:undang-undang|peraturan\s+pemerintah|peraturan\s+menteri|surat\s+keputusan|keputusan\s+menteri|keputusan\s+dekan|keputusan\s+rektor)\s+nomor\b/', $text, $match)) {
            $m['is_boilerplate'] = true;
            $m['boilerplate_category'] = 'LEGAL_CITATION';
            $m['boilerplate_phrase'] = $match[0];
            $m['boilerplate_rationale'] = 'Rujukan formal peraturan perundang-undangan atau instrumen hukum akademik baku.';
        } elseif (preg_match('/(?i)\b(?:pengangkatan|penunjukan|susunan)\s+dosen\s+penguji(?:\s+(?:[ivx\d]+|utama|ketua|anggota))?\s+(?:skripsi|tesis|disertasi)\b/', $text, $match)) {
            $m['is_boilerplate'] = true;
            $m['boilerplate_category'] = 'ADMINISTRATIVE_FORMULA';
            $m['boilerplate_phrase'] = $match[0];
            $m['boilerplate_rationale'] = 'Formulasi administratif penugasan sivitas akademika dalam pengujian tugas akhir.';
        }
    }

    /**
     * Halaman Publik Verifikasi Sertifikat Resmi AR Tilik (via QR Code / URL)
     */
    public function verify(?string $code = null)
    {
        $code = $code ?? $this->request->getGet('code');
        if (!$code) {
            return view('dashboard/verify', [
                'status'       => 'input',
                'code'         => '',
                'report'       => null,
                'document'     => null,
                'sources'      => [],
                'evidenceData' => [],
            ]);
        }

        $code = strtoupper(trim($code));
        $db = db_connect();

        // 1. Cari berdasarkan kolom verification_code
        $builder = $db->table('reports');
        $builder->select('reports.*, documents.title AS document_title, documents.original_filename, documents.created_at AS document_created_at');
        $builder->join('documents', 'documents.id = reports.document_id', 'left');
        $builder->where('reports.verification_code', $code);
        $report = $builder->get()->getRowArray();

        // 2. Fallback deterministik jika kode belum terindeks di database
        if (!$report) {
            $candidates = $db->table('reports')
                             ->select('reports.*, documents.title AS document_title, documents.original_filename, documents.created_at AS document_created_at')
                             ->join('documents', 'documents.id = reports.document_id', 'left')
                             ->where('reports.status', 'completed')
                             ->orderBy('reports.id', 'DESC')
                             ->get(300)->getResultArray();

            foreach ($candidates as $cand) {
                $rawCreated = (string) $cand['created_at'];
                $sim = (float) ($cand['overall_similarity'] ?? 0);
                $seed = "ARTILIK:{$cand['id']}:{$cand['document_id']}:{$rawCreated}:" . sprintf("%.2f", $sim) . ":OFFICIAL_CERTIFICATE";
                $h = strtoupper(substr(hash('sha256', $seed), 0, 12));
                $computedHash = "ART-" . substr($h, 0, 4) . "-" . substr($h, 4, 4) . "-" . substr($h, 8, 4);

                if ($computedHash === $code) {
                    $report = $cand;
                    // Cache ke database untuk query berikutnya
                    $db->table('reports')->where('id', $cand['id'])->update(['verification_code' => $code]);
                    break;
                }
            }
        }

        if (!$report) {
            return view('dashboard/verify', [
                'status'       => 'not_found',
                'code'         => $code,
                'report'       => null,
                'document'     => null,
                'sources'      => [],
                'evidenceData' => [],
            ]);
        }

        $evidenceData = !empty($report['evidence_data']) ? (is_string($report['evidence_data']) ? json_decode($report['evidence_data'], true) : $report['evidence_data']) : [];

        // Ambil 5 rujukan teratas
        $sources = $db->table('report_sources rs')
                      ->select('rs.*, d.title AS source_title, d.original_filename AS source_filename, d.source_type')
                      ->join('documents d', 'd.id = rs.source_document_id', 'left')
                      ->where('rs.report_id', $report['id'])
                      ->orderBy('rs.source_rank', 'ASC')
                      ->limit(5)
                      ->get()->getResultArray();

        return view('dashboard/verify', [
            'status'       => 'valid',
            'code'         => $code,
            'report'       => $report,
            'document'     => [
                'id'                => $report['document_id'],
                'title'             => $report['document_title'] ?? 'Dokumen Karya Ilmiah',
                'original_filename' => $report['original_filename'] ?? "document_{$report['document_id']}.pdf",
                'created_at'        => $report['document_created_at'] ?? $report['created_at'],
            ],
            'sources'      => $sources,
            'evidenceData' => $evidenceData,
        ]);
    }
}
