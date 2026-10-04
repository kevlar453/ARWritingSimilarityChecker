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

    /**
     * Memuat 30 kasus prioritas dari file dataset dan menyinkronkan status dari database.
     */
    private function getReviewSet(): array
    {
        if (!file_exists($this->reviewSetPath)) {
            // Panggil python helper untuk generate jika belum ada
            @exec(ROOTPATH . '.venv/bin/python -c "from evaluation.reviewer import get_human_review_30_set; get_human_review_30_set()"');
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

        return view('evaluation/detail', [
            'item'         => $item,
            'currentIndex' => $currentIndex,
            'totalCases'   => count($set),
            'prevCase'     => $prevCase,
            'nextCase'     => $nextCase,
            'existing'     => $existing,
        ]);
    }

    /**
     * Menyimpan hasil telaah manusia ke database & dataset file
     */
    public function save($caseId)
    {
        $set = $this->getReviewSet();
        $caseIds = array_column($set, 'case_id');
        $currentIndex = array_search($caseId, $caseIds);

        $reviewerCode = $this->request->getPost('reviewer_code') ?: 'RV-001';
        $interpretation = $this->request->getPost('human_interpretation') ?: 'DIRECT_OVERLAP_RELEVANT';
        $citationContext = $this->request->getPost('citation_context') ?: 'NONE';
        $citationStatus = $this->request->getPost('citation_status') ?: 'NOT_CITED';
        $assessment = $this->request->getPost('system_assessment') ?: 'CORRECT';
        $confidence = $this->request->getPost('reviewer_confidence') ?: 'HIGH';
        $notes = trim($this->request->getPost('notes') ?: '');

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
        $pyCmd = ROOTPATH . ".venv/bin/python -c \"from evaluation.reviewer import tag_case_full; tag_case_full('$caseId', '$reviewerCode', 'review', '$citationContext', '$citationStatus', '$interpretation', '$assessment', '$confidence', 'REVIEWED', $pyNotesArg)\"";
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
        ]);
    }
}
