<?php

namespace App\Controllers;

/**
 * Controller untuk Human Review & Evaluation Subsystem (Phase HR-1 Isolated Module)
 * Mematuhi 4 Golden Gates:
 * 1. HR-UI Gate 1: Engine v1.0.0 tetap FROZEN.
 * 2. HR-UI Gate 2: UI hanya membaca evidence yang telah dihasilkan.
 * 3. HR-UI Gate 3: Human assessment disimpan terpisah di evaluation_labels.
 * 4. HR-UI Gate 4: Reviewer tidak melihat expected class/pattern sintetik (Blind Review).
 */
class EvaluationController extends BaseController
{
    private string $reviewSetPath;
    private string $reviewsJsonPath;

    public function __construct()
    {
        $this->reviewSetPath = ROOTPATH . 'evaluation_dataset_v1/human_review_set.json';
        $this->reviewsJsonPath = ROOTPATH . 'evaluation_dataset_v1/human_reviews.json';
    }

    private function getSuite(?string $caseId = null): string
    {
        if ($caseId) {
            if (str_starts_with($caseId, 'PILOT-') || str_starts_with($caseId, 'EV-R2-')) {
                return 'pilot_r2';
            }
            if (str_starts_with($caseId, 'EV11-')) {
                return 'v1_1';
            }
            if (str_starts_with($caseId, 'EV-')) {
                return 'v1_0';
            }
        }
        $param = $this->request->getGet('suite');
        if ($param) {
            session()->set('evaluation_suite', $param);
            return $param;
        }
        return session()->get('evaluation_suite') ?? 'pilot_r2';
    }

    /**
     * Memuat 30 kasus prioritas dari file dataset dan menyinkronkan status dari database.
     */
    private function getReviewSet(?string $caseId = null): array
    {
        $suite = $this->getSuite($caseId);
        if ($suite === 'pilot_r2') {
            $this->reviewSetPath = ROOTPATH . 'evaluation_dataset_v1_1_targeted_80/pilot_r2_set.json';
            $this->reviewsJsonPath = ROOTPATH . 'evaluation_dataset_v1_1_targeted_80/pilot_r2_reviews.json';
            if (!file_exists($this->reviewSetPath)) {
                @exec(ROOTPATH . '.venv/bin/python evaluation/pilot_sampler_r2.py');
            }
        } elseif ($suite === 'v1_1') {
            $this->reviewSetPath = ROOTPATH . 'evaluation_dataset_v1_1_targeted_80/human_review_set_v1_1.json';
            $this->reviewsJsonPath = ROOTPATH . 'evaluation_dataset_v1_1_targeted_80/human_reviews_v1_1.json';
            if (!file_exists($this->reviewSetPath)) {
                @exec(ROOTPATH . '.venv/bin/python -c "from evaluation.reviewer_v1_1 import get_human_review_v11_set; get_human_review_v11_set()"');
            }
        } else {
            $this->reviewSetPath = ROOTPATH . 'evaluation_dataset_v1/human_review_set.json';
            $this->reviewsJsonPath = ROOTPATH . 'evaluation_dataset_v1/human_reviews.json';
            if (!file_exists($this->reviewSetPath)) {
                @exec(ROOTPATH . '.venv/bin/python -c "from evaluation.reviewer import get_human_review_30_set; get_human_review_30_set()"');
            }
        }

        if (file_exists($this->reviewSetPath)) {
            $content = file_get_contents($this->reviewSetPath);
            $set = json_decode($content, true) ?: [];
        } else {
            $set = [];
        }

        // Sinkronisasi status dari tabel evaluation_labels
        $db = db_connect();
        $builder = $db->table('evaluation_labels')->orderBy('id', 'ASC');
        $labels = $builder->get()->getResultArray();
        $labelMap = [];
        foreach ($labels as $l) {
            $labelMap[$l['case_id']] = $l;
        }

        foreach ($set as &$item) {
            $cid = $item['case_id'];
            if (isset($labelMap[$cid])) {
                $item['review_status'] = $labelMap[$cid]['review_status'] ?? 'REVIEWED';
                $item['human_review'] = $labelMap[$cid];
            } else {
                $item['review_status'] = 'PENDING';
                $item['human_review'] = null;
            }
        }
        unset($item);

        return $set;
    }

    /**
     * 1. Halaman Antrean Kasus (Review Queue) - /evaluation
     */
    public function index()
    {
        $set = $this->getReviewSet();

        $statusFilter = $this->request->getGet('status') ?: 'all';
        $patternFilter = $this->request->getGet('pattern') ?: 'all';
        $priorityFilter = $this->request->getGet('priority') ?: 'all';

        $filtered = array_filter($set, function ($item) use ($statusFilter, $patternFilter, $priorityFilter) {
            if ($statusFilter !== 'all') {
                if (strtolower($item['review_status']) !== strtolower($statusFilter)) {
                    return false;
                }
            }
            if ($patternFilter !== 'all') {
                if (strtoupper($item['predicted_pattern']) !== strtoupper($patternFilter)) {
                    return false;
                }
            }
            if ($priorityFilter !== 'all') {
                if (stripos($item['priority_tier'], $priorityFilter) === false) {
                    return false;
                }
            }
            return true;
        });

        $total = count($set);
        $reviewed = count(array_filter($set, fn($i) => in_array($i['review_status'], ['REVIEWED', 'VALIDATED'])));
        $pending = $total - $reviewed;

        $patterns = array_values(array_unique(array_column($set, 'predicted_pattern')));
        $priorities = array_values(array_unique(array_column($set, 'priority_tier')));

        return view('evaluation/queue', [
            'cases'          => array_values($filtered),
            'total'          => $total,
            'reviewed'       => $reviewed,
            'pending'        => $pending,
            'statusFilter'   => $statusFilter,
            'patternFilter'  => $patternFilter,
            'priorityFilter' => $priorityFilter,
            'patterns'       => $patterns,
            'priorities'     => $priorities,
            'currentSuite'   => $this->getSuite(),
        ]);
    }

    /**
     * 2. Halaman Telaah Detail Kasus (Review Detail) - /evaluation/:id
     * Menerapkan HR-UI Gate 4: Reviewer tidak melihat expected class/pattern.
     */
    public function detail($caseId)
    {
        $set = $this->getReviewSet();
        $item = null;
        $currentIndex = 0;
        foreach ($set as $idx => $c) {
            if ($c['case_id'] === $caseId) {
                $item = $c;
                $currentIndex = $idx + 1;
                break;
            }
        }

        if (!$item) {
            return redirect()->to('/evaluation')->with('error', "Kasus $caseId tidak ditemukan dalam antrean 30 kasus.");
        }

        $prevCase = $currentIndex > 1 ? $set[$currentIndex - 2]['case_id'] : null;
        $nextCase = $currentIndex < count($set) ? $set[$currentIndex]['case_id'] : null;

        $db = db_connect();
        $existing = $db->table('evaluation_labels')
                       ->where('case_id', $caseId)
                       ->orderBy('id', 'DESC')
                       ->get()
                       ->getRowArray();

        list($highlightedSubmission, $highlightedSource) = $this->highlightPhrases(
            $item['submission_text'],
            $item['source_text'],
            3
        );

        return view('evaluation/detail', [
            'item'                  => $item,
            'currentIndex'          => $currentIndex,
            'totalCases'            => count($set),
            'prevCase'              => $prevCase,
            'nextCase'              => $nextCase,
            'existing'              => $existing,
            'highlightedSubmission' => $highlightedSubmission,
            'highlightedSource'     => $highlightedSource,
        ]);
    }

    /**
     * Menghitung dan memberikan highlight pada pasase teks yang berurutan (>= 3 kata)
     */
    private function highlightPhrases(string $textA, string $textB, int $minWords = 3): array
    {
        preg_match_all("/\b[\p{L}\p{N}_\-]+\b/u", $textA, $matchesA, PREG_OFFSET_CAPTURE);
        preg_match_all("/\b[\p{L}\p{N}_\-]+\b/u", $textB, $matchesB, PREG_OFFSET_CAPTURE);

        $wordsA = $matchesA[0] ?? [];
        $wordsB = $matchesB[0] ?? [];

        $cleanA = array_map(fn($w) => mb_strtolower($w[0]), $wordsA);
        $cleanB = array_map(fn($w) => mb_strtolower($w[0]), $wordsB);

        $countA = count($cleanA);
        $countB = count($cleanB);

        if ($countA < $minWords || $countB < $minWords) {
            return [htmlspecialchars($textA), htmlspecialchars($textB)];
        }

        $usedA = array_fill(0, $countA, false);
        $usedB = array_fill(0, $countB, false);
        $rangesA = [];
        $rangesB = [];

        for ($len = min(30, $countA, $countB); $len >= $minWords; $len--) {
            for ($i = 0; $i <= $countA - $len; $i++) {
                if ($usedA[$i]) continue;
                $sliceA = array_slice($cleanA, $i, $len);

                for ($j = 0; $j <= $countB - $len; $j++) {
                    if ($usedB[$j]) continue;
                    $sliceB = array_slice($cleanB, $j, $len);

                    if ($sliceA === $sliceB) {
                        for ($k = 0; $k < $len; $k++) {
                            $usedA[$i + $k] = true;
                            $usedB[$j + $k] = true;
                        }
                        $startCharA = $wordsA[$i][1];
                        $endCharA = $wordsA[$i + $len - 1][1] + strlen($wordsA[$i + $len - 1][0]);
                        $rangesA[] = [$startCharA, $endCharA];

                        $startCharB = $wordsB[$j][1];
                        $endCharB = $wordsB[$j + $len - 1][1] + strlen($wordsB[$j + $len - 1][0]);
                        $rangesB[] = [$startCharB, $endCharB];
                        break;
                    }
                }
            }
        }

        $apply = function (string $text, array $ranges): string {
            if (empty($ranges)) {
                return htmlspecialchars($text);
            }
            usort($ranges, fn($a, $b) => $b[0] <=> $a[0]);
            $res = $text;
            foreach ($ranges as $r) {
                $len = $r[1] - $r[0];
                $sub = substr($res, $r[0], $len);
                $replacement = '<mark class="bg-amber-100 text-amber-950 font-semibold px-1 rounded border border-amber-300 shadow-xs">' . htmlspecialchars($sub) . '</mark>';
                $res = substr_replace($res, $replacement, $r[0], $len);
            }
            return $res;
        };

        return [$apply($textA, $rangesA), $apply($textB, $rangesB)];
    }

    /**
     * Menyimpan hasil telaah manusia ke database & dataset file
     */
    public function save($caseId)
    {
        $set = $this->getReviewSet();
        $caseIds = array_column($set, 'case_id');
        $currentIndex = array_search($caseId, $caseIds);

        $reviewerCode = trim($this->request->getPost('reviewer_code') ?: 'RV-001');
        $interpretation = $this->request->getPost('human_interpretation');
        $citationContext = $this->request->getPost('citation_context');
        $citationStatus = $this->request->getPost('citation_status');
        $assessment = $this->request->getPost('system_assessment');
        $confidence = $this->request->getPost('reviewer_confidence');
        $notes = trim($this->request->getPost('notes') ?: '');

        // Validasi ketat: seluruh 5 field pertimbangan manusia wajib diisi
        if (empty($interpretation) || empty($citationContext) || empty($citationStatus) || empty($assessment) || empty($confidence)) {
            return redirect()->to("/evaluation/$caseId")->with('error', "Gagal menyimpan: Seluruh 5 field pertimbangan manusia (Human Interpretation, Citation Context, Citation Status, System Assessment, Confidence) wajib dipilih sebelum menyimpan.");
        }

        $isRelevant = in_array($interpretation, ['DIRECT_OVERLAP_RELEVANT', 'DIRECT_OVERLAP_PROPERLY_CITED']) ? 1 : 0;
        $isCommon = ($interpretation === 'COMMON_ACADEMIC_LANGUAGE') ? 1 : 0;
        $isCited = ($interpretation === 'DIRECT_OVERLAP_PROPERLY_CITED' || in_array($citationStatus, ['CITED', 'PARAPHRASED_WITH_CITATION'])) ? 1 : 0;
        $isInvestigate = ($interpretation === 'NEEDS_INVESTIGATION') ? 1 : 0;
        $isFalsePositive = ($interpretation === 'FALSE_POSITIVE') ? 1 : 0;

        $db = db_connect();
        $db->table('evaluation_labels')->insert([
            'case_id'              => $caseId,
            'reviewer_code'        => $reviewerCode,
            'human_decision'       => 'review',
            'citation_context'     => $citationContext,
            'citation_status'      => $citationStatus,
            'human_interpretation' => $interpretation,
            'evidence_relevant'    => $isRelevant,
            'common_phrase'        => $isCommon,
            'properly_cited'       => $isCited,
            'needs_investigation'  => $isInvestigate,
            'false_positive'       => $isFalsePositive,
            'system_assessment'    => $assessment,
            'reviewer_confidence'  => $confidence,
            'review_status'        => 'REVIEWED',
            'notes'                => $notes ?: null,
            'reviewed_at'          => date('Y-m-d H:i:s'),
        ]);

        // Sinkronisasi via python backend
        $cleanNotes = $notes ? addslashes($notes) : '';
        $pyNotesArg = $notes ? "'$cleanNotes'" : "None";
        if (str_starts_with($caseId, 'PILOT-')) {
            $pyCmd = ROOTPATH . ".venv/bin/python -c \"from evaluation.pilot_sampler_r2 import tag_pilot_case; tag_pilot_case('$caseId', '$reviewerCode', 'review', '$citationContext', '$citationStatus', '$interpretation', '$assessment', '$confidence', 'REVIEWED', $pyNotesArg)\"";
        } elseif (str_starts_with($caseId, 'EV11-')) {
            $pyCmd = ROOTPATH . ".venv/bin/python -c \"from evaluation.reviewer_v1_1 import tag_case_v11; tag_case_v11('$caseId', '$reviewerCode', 'review', '$citationContext', '$citationStatus', '$interpretation', '$assessment', '$confidence', 'REVIEWED', $pyNotesArg)\"";
        } else {
            $pyCmd = ROOTPATH . ".venv/bin/python -c \"from evaluation.reviewer import tag_case_full; tag_case_full('$caseId', '$reviewerCode', 'review', '$citationContext', '$citationStatus', '$interpretation', '$assessment', '$confidence', 'REVIEWED', $pyNotesArg)\"";
        }
        @exec($pyCmd);

        // Redirect ke kasus berikutnya atau ke ringkasan jika selesai
        if ($currentIndex !== false && $currentIndex + 1 < count($caseIds)) {
            $nextCaseId = $caseIds[$currentIndex + 1];
            return redirect()->to("/evaluation/$nextCaseId")->with('success', "Anotasi untuk kasus $caseId berhasil disimpan. Melanjutkan ke $nextCaseId.");
        }

        return redirect()->to('/evaluation/summary')->with('success', "Seluruh kasus telah selesai ditelaah!");
    }

    /**
     * 3. Halaman Ringkasan Telaah Manusia (Review Summary) - /evaluation/summary
     */
    public function summary()
    {
        $set = $this->getReviewSet();
        $db = db_connect();
        $labels = $db->table('evaluation_labels')->orderBy('id', 'ASC')->get()->getResultArray();

        $latestLabels = [];
        foreach ($labels as $l) {
            $latestLabels[$l['case_id']] = $l;
        }

        $total = count($set);
        $reviewedCases = [];
        $firstPendingCaseId = null;
        foreach ($set as $c) {
            $cid = $c['case_id'];
            if (isset($latestLabels[$cid])) {
                $c['human_review'] = $latestLabels[$cid];
                $reviewedCases[] = $c;
            } elseif ($firstPendingCaseId === null) {
                $firstPendingCaseId = $cid;
            }
        }

        $reviewedCount = count($reviewedCases);
        $pendingCount = $total - $reviewedCount;

        $assessments = [];
        $citationContexts = [];
        $citationStatuses = [];
        $interpretations = [];
        $confidences = [];
        $tierProgress = [];

        foreach ($reviewedCases as $rc) {
            $hr = $rc['human_review'];
            $a = $hr['system_assessment'] ?? 'CORRECT';
            $ctx = $hr['citation_context'] ?? 'NONE';
            $stat = $hr['citation_status'] ?? 'NOT_CITED';
            $interp = $hr['human_interpretation'] ?? 'DIRECT_OVERLAP_RELEVANT';
            $conf = $hr['reviewer_confidence'] ?? 'HIGH';
            $tier = $rc['priority_tier'];

            $assessments[$a] = ($assessments[$a] ?? 0) + 1;
            $citationContexts[$ctx] = ($citationContexts[$ctx] ?? 0) + 1;
            $citationStatuses[$stat] = ($citationStatuses[$stat] ?? 0) + 1;
            $interpretations[$interp] = ($interpretations[$interp] ?? 0) + 1;
            $confidences[$conf] = ($confidences[$conf] ?? 0) + 1;
            $tierProgress[$tier] = ($tierProgress[$tier] ?? 0) + 1;
        }

        $agreementCases = ($assessments['CORRECT'] ?? 0) + ($assessments['ACCEPTABLE'] ?? 0);
        $agreementRate = $reviewedCount > 0 ? round(($agreementCases / $reviewedCount) * 100, 1) : 0;

        return view('evaluation/summary', [
            'total'              => $total,
            'reviewedCount'      => $reviewedCount,
            'pendingCount'       => $pendingCount,
            'completionRate'     => $total > 0 ? round(($reviewedCount / $total) * 100, 1) : 0,
            'assessments'        => $assessments,
            'citationContexts'   => $citationContexts,
            'citationStatuses'   => $citationStatuses,
            'interpretations'    => $interpretations,
            'confidences'        => $confidences,
            'tierProgress'       => $tierProgress,
            'agreementCases'     => $agreementCases,
            'agreementRate'      => $agreementRate,
            'reviewedCases'      => $reviewedCases,
            'firstPendingCaseId' => $firstPendingCaseId,
            'currentSuite'       => $this->getSuite(),
        ]);
    }

    /**
     * 4. Halaman Dasbor Kualitas Operasional (Operational Quality Dashboard) - /evaluation/quality
     */
    public function quality()
    {
        $currentSuite = $this->getSuite();
        $output = [];
        $returnVar = 0;
        @exec(ROOTPATH . '.venv/bin/python worker/observability.py --quality --json', $output, $returnVar);
        $rawJson = implode("\n", $output);
        $report = json_decode($rawJson, true) ?: [];

        return view('evaluation/quality', [
            'report'       => $report,
            'currentSuite' => $currentSuite,
        ]);
    }
}
