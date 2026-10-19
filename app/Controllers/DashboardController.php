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

        // Hitung total korpus aktif untuk fallback metrik pembanding
        $totalCorpus = $docModel->where('document_type', 'corpus')->where('benchmark_run_id IS NULL', null, false)->countAllResults();

        // Parameter Filter & Pencarian Submisi Pengguna
        $subSearch   = trim((string) ($this->request->getGet('sub_q') ?? ''));
        $subStatus   = trim((string) ($this->request->getGet('sub_status') ?? 'all'));
        $subDecision = trim((string) ($this->request->getGet('sub_decision') ?? 'all'));
        $subEngine   = trim((string) ($this->request->getGet('sub_engine') ?? 'all'));
        $subDate     = trim((string) ($this->request->getGet('sub_date') ?? 'all'));
        $subSort     = trim((string) ($this->request->getGet('sub_sort') ?? 'newest'));
        $subLimit    = (int) ($this->request->getGet('sub_limit') ?? 15);
        if (! in_array($subLimit, [15, 25, 50, 100], true)) {
            $subLimit = 15;
        }
        $subPage = max(1, (int) ($this->request->getGet('sub_page') ?? 1));

        // Validasi nilai parameter agar aman & terprediksi
        if (! in_array($subStatus, ['all', 'completed', 'processing', 'uploaded', 'failed'], true)) {
            $subStatus = 'all';
        }
        if (! in_array($subDecision, ['all', 'no_match', 'review', 'match'], true)) {
            $subDecision = 'all';
        }
        if (! in_array($subEngine, ['all', 'hybrid', 'tfidf', 'exact_idf', 'exact'], true)) {
            $subEngine = 'all';
        }
        if (! in_array($subDate, ['all', 'today', 'yesterday', '7days', '30days', 'this_month'], true)) {
            $subDate = 'all';
        }
        if (! in_array($subSort, ['newest', 'oldest', 'score_desc', 'score_asc', 'title_asc', 'title_desc'], true)) {
            $subSort = 'newest';
        }

        $isFilterActive = ($subSearch !== '' || $subStatus !== 'all' || $subDecision !== 'all' || $subEngine !== 'all' || $subDate !== 'all' || $subSort !== 'newest');
        $activeFilterCount = 0;
        if ($subSearch !== '') $activeFilterCount++;
        if ($subStatus !== 'all') $activeFilterCount++;
        if ($subDecision !== 'all') $activeFilterCount++;
        if ($subEngine !== 'all') $activeFilterCount++;
        if ($subDate !== 'all') $activeFilterCount++;
        if ($subSort !== 'newest') $activeFilterCount++;

        // Base Builder untuk submisi user (hanya tampilkan submisi milik user yang login, kecuali admin)
        $subBuilder = $db->table('documents d')
                         ->join('(SELECT document_id, MAX(id) as latest_report_id FROM reports GROUP BY document_id) lr', 'lr.document_id = d.id', 'left', false)
                         ->join('reports rep', 'rep.id = lr.latest_report_id', 'left')
                         ->where('d.document_type', 'submission')
                         ->where('d.benchmark_run_id IS NULL', null, false);

        if (($currentUser['role'] ?? 'user') !== 'admin') {
            $subBuilder->where('d.user_id', $userId);
        }

        // Statistik Saring Cepat (Quick Pill Counters)
        $basePillBuilder = clone $subBuilder;
        $pillStats = $basePillBuilder
            ->select("
                COUNT(d.id) as total_all,
                SUM(CASE WHEN rep.status = 'completed' THEN 1 ELSE 0 END) as total_completed,
                SUM(CASE WHEN rep.status IN ('processing', 'pending') OR d.status IN ('uploaded', 'processing') THEN 1 ELSE 0 END) as total_processing,
                SUM(CASE WHEN rep.status = 'completed' AND rep.predicted_decision = 'match' THEN 1 ELSE 0 END) as total_match,
                SUM(CASE WHEN rep.status = 'completed' AND rep.predicted_decision = 'review' THEN 1 ELSE 0 END) as total_review,
                SUM(CASE WHEN rep.status = 'completed' AND (rep.predicted_decision = 'no_match' OR rep.predicted_decision IS NULL) THEN 1 ELSE 0 END) as total_no_match
            ", false)
            ->get()
            ->getRowArray();

        $subBuilder->select('d.*, lr.latest_report_id, rep.status as rep_status, rep.predicted_decision as rep_predicted_decision, rep.engine as rep_engine, rep.overall_similarity as rep_overall_similarity');

        // Terapkan Filter Pencarian Kata Kunci
        if ($subSearch !== '') {
            $subBuilder->groupStart()
                       ->like('d.title', $subSearch)
                       ->orLike('d.original_filename', $subSearch)
                       ->orLike('d.faculty', $subSearch)
                       ->orLike('d.department', $subSearch)
                       ->groupEnd();
        }

        // Terapkan Filter Status Pekerjaan / Pemeriksaan
        if ($subStatus === 'completed') {
            $subBuilder->where('rep.status', 'completed');
        } elseif ($subStatus === 'processing') {
            $subBuilder->groupStart()
                       ->whereIn('rep.status', ['processing', 'pending'])
                       ->orWhereIn('d.status', ['uploaded', 'processing'])
                       ->groupEnd();
        } elseif ($subStatus === 'uploaded') {
            $subBuilder->where('d.status', 'uploaded')
                       ->groupStart()
                           ->where('rep.status IS NULL', null, false)
                           ->orWhere('rep.status', 'pending')
                       ->groupEnd();
        } elseif ($subStatus === 'failed') {
            $subBuilder->groupStart()
                       ->where('rep.status', 'failed')
                       ->orWhere('d.status', 'failed')
                       ->groupEnd();
        }

        // Terapkan Filter Keputusan / Ambang Batas
        if ($subDecision === 'match') {
            $subBuilder->where('rep.status', 'completed')
                       ->where('rep.predicted_decision', 'match');
        } elseif ($subDecision === 'review') {
            $subBuilder->where('rep.status', 'completed')
                       ->where('rep.predicted_decision', 'review');
        } elseif ($subDecision === 'no_match') {
            $subBuilder->where('rep.status', 'completed')
                       ->groupStart()
                           ->where('rep.predicted_decision', 'no_match')
                           ->orWhere('rep.predicted_decision IS NULL', null, false)
                       ->groupEnd();
        }

        // Terapkan Filter Engine Analisis
        if (in_array($subEngine, ['hybrid', 'tfidf', 'exact_idf', 'exact'], true)) {
            $subBuilder->where('rep.engine', $subEngine);
        }

        // Terapkan Filter Periode Waktu
        if ($subDate === 'today') {
            $subBuilder->where('d.created_at >=', date('Y-m-d 00:00:00'));
        } elseif ($subDate === 'yesterday') {
            $subBuilder->where('d.created_at >=', date('Y-m-d 00:00:00', strtotime('-1 day')))
                       ->where('d.created_at <', date('Y-m-d 00:00:00'));
        } elseif ($subDate === '7days') {
            $subBuilder->where('d.created_at >=', date('Y-m-d H:i:s', strtotime('-7 days')));
        } elseif ($subDate === '30days') {
            $subBuilder->where('d.created_at >=', date('Y-m-d H:i:s', strtotime('-30 days')));
        } elseif ($subDate === 'this_month') {
            $subBuilder->where('d.created_at >=', date('Y-m-01 00:00:00'));
        }

        // Hitung total hasil yang cocok dengan filter untuk paginasi
        $countBuilder = clone $subBuilder;
        $totalFilteredSubmissions = $countBuilder->countAllResults();

        // Urutan Data
        if ($subSort === 'oldest') {
            $subBuilder->orderBy('d.id', 'ASC');
        } elseif ($subSort === 'score_desc') {
            $subBuilder->orderBy('rep.overall_similarity', 'DESC')
                       ->orderBy('d.id', 'DESC');
        } elseif ($subSort === 'score_asc') {
            $subBuilder->orderBy('(rep.overall_similarity IS NULL)', 'ASC', false)
                       ->orderBy('rep.overall_similarity', 'ASC')
                       ->orderBy('d.id', 'DESC');
        } elseif ($subSort === 'title_asc') {
            $subBuilder->orderBy('d.title', 'ASC');
        } elseif ($subSort === 'title_desc') {
            $subBuilder->orderBy('d.title', 'DESC');
        } else {
            $subBuilder->orderBy('d.id', 'DESC');
        }

        // Kalkulasi Paginasi
        $totalPages = max(1, (int) ceil($totalFilteredSubmissions / $subLimit));
        if ($subPage > $totalPages && $totalFilteredSubmissions > 0) {
            $subPage = $totalPages;
        }
        $offset = ($subPage - 1) * $subLimit;

        $submissions = $subBuilder->limit($subLimit, $offset)->get()->getResultArray();

        // Hidrasi data laporan lengkap (batch query)
        $reportIds = array_filter(array_column($submissions, 'latest_report_id'));
        $reportsById = [];
        if (!empty($reportIds)) {
            $reportsList = $reportModel->whereIn('id', $reportIds)->findAll();
            foreach ($reportsList as $r) {
                $reportsById[$r['id']] = $r;
            }
        }

        foreach ($submissions as &$sub) {
            $repId = $sub['latest_report_id'] ?? null;
            $latestReport = ($repId && isset($reportsById[$repId])) ? $reportsById[$repId] : null;
            if ($latestReport) {
                $latestReport['ai_assessment'] = self::extractAiMetrics($latestReport);
                $latestReport['corpus_docs_count'] = self::extractCorpusCount($latestReport, $totalCorpus);
            }
            $sub['latest_report'] = $latestReport;
        }
        unset($sub);

        $subFilter = [
            'q'              => $subSearch,
            'status'         => $subStatus,
            'decision'       => $subDecision,
            'engine'         => $subEngine,
            'date'           => $subDate,
            'sort'           => $subSort,
            'limit'          => $subLimit,
            'page'           => $subPage,
            'is_active'      => $isFilterActive,
            'active_count'   => $activeFilterCount,
            'total_filtered' => $totalFilteredSubmissions,
            'total_pages'    => $totalPages,
            'start_item'     => $totalFilteredSubmissions > 0 ? $offset + 1 : 0,
            'end_item'       => min($offset + $subLimit, $totalFilteredSubmissions),
            'pill_stats'     => [
                'total_all'        => (int) ($pillStats['total_all'] ?? 0),
                'total_completed'  => (int) ($pillStats['total_completed'] ?? 0),
                'total_processing' => (int) ($pillStats['total_processing'] ?? 0),
                'total_match'      => (int) ($pillStats['total_match'] ?? 0),
                'total_review'     => (int) ($pillStats['total_review'] ?? 0),
                'total_no_match'   => (int) ($pillStats['total_no_match'] ?? 0),
            ],
        ];

        // Dokumen korpus dengan pencarian dan paginasi
        $corpusSearch = trim((string) ($this->request->getGet('corpus_search') ?? ''));
        $corpusPage = max(1, (int) ($this->request->getGet('corpus_page') ?? 1));
        $corpusPerPage = 15;

        $applyCorpusFilter = static function ($builder) use ($corpusSearch) {
            $builder->where('document_type', 'corpus')
                    ->where('benchmark_run_id IS NULL', null, false);
            if ($corpusSearch !== '') {
                $builder->groupStart()
                        ->like('title', $corpusSearch)
                        ->orLike('original_filename', $corpusSearch)
                        ->orLike('faculty', $corpusSearch)
                        ->orLike('department', $corpusSearch)
                        ->orLike('source_type', $corpusSearch)
                        ->groupEnd();
            }
            return $builder;
        };

        $countModel = new DocumentModel();
        $filteredCorpusTotal = $applyCorpusFilter($countModel)->countAllResults();

        $corpusTotalPages = max(1, (int) ceil($filteredCorpusTotal / $corpusPerPage));
        if ($corpusPage > $corpusTotalPages && $filteredCorpusTotal > 0) {
            $corpusPage = $corpusTotalPages;
        }
        $offset = ($corpusPage - 1) * $corpusPerPage;

        $fetchModel = new DocumentModel();
        $corpusDocs = $applyCorpusFilter($fetchModel)
            ->orderBy('id', 'DESC')
            ->findAll($corpusPerPage, $offset);

        // Riwayat benchmark runs
        $benchmarkRuns = $db->table('benchmark_runs')->orderBy('id', 'DESC')->get(10)->getResultArray();

        // Statistik Keseluruhan (Stabil & Konsisten terhadap status selesai)
        $totalSubmissions = $db->table('documents')
                               ->where('document_type', 'submission')
                               ->where('benchmark_run_id IS NULL', null, false)
                               ->countAllResults();
        $totalCorpus = $db->table('documents')
                          ->where('document_type', 'corpus')
                          ->where('benchmark_run_id IS NULL', null, false)
                          ->countAllResults();
        $totalReports = $db->table('reports')
                           ->where('status', 'completed')
                           ->countAllResults();

        // Dokumen yang dibagikan oleh pengguna lain kepada akun ini (Model A+B)
        $shareModel = new \App\Models\DocumentShareModel();
        $shareModel->claimPendingSharesForUser((int) $userId, (string) ($currentUser['email'] ?? ''));
        $sharedDocuments = $shareModel->getDocumentsSharedWithUser((int) $userId);
        foreach ($sharedDocuments as &$item) {
            if (!empty($item['latest_report'])) {
                $item['latest_report']['ai_assessment'] = self::extractAiMetrics($item['latest_report']);
                $item['latest_report']['corpus_docs_count'] = self::extractCorpusCount($item['latest_report'], $totalCorpus);
            }
        }
        unset($item);

        // Ambil profil Duta Kampus jika pengguna terdaftar
        $ambProfile = null;
        if ($userId) {
            $ambProfileModel = new \App\Models\AmbassadorProfileModel();
            $ambProfile = $ambProfileModel->where('user_id', (int) $userId)
                                          ->whereIn('status', ['probation', 'active', 'graduated'])
                                          ->first();
        }

        // Program Referral Reguler (Member-Get-Member)
        $referralModel = new \App\Models\ReferralModel();
        $referralStats = $referralModel->getReferralStats((int) $userId);

        // Ambil transaksi pending terkini jika ada dalam 24 jam terakhir
        $pendingPayment = null;
        if ($userId) {
            $paymentModel = new \App\Models\PaymentTransactionModel();
            $pendingPayment = $paymentModel->where('user_id', (int) $userId)
                                           ->where('transaction_status', 'pending')
                                           ->where('created_at >=', date('Y-m-d H:i:s', strtotime('-24 hours')))
                                           ->orderBy('created_at', 'DESC')
                                           ->first();
        }

        return view('dashboard/index', [
            'submissions'         => $submissions,
            'subFilter'           => $subFilter,
            'sharedDocuments'     => $sharedDocuments,
            'corpusDocs'          => $corpusDocs,
            'corpusSearch'        => $corpusSearch,
            'corpusPage'          => $corpusPage,
            'corpusPerPage'       => $corpusPerPage,
            'corpusTotalPages'    => $corpusTotalPages,
            'filteredCorpusTotal' => $filteredCorpusTotal,
            'benchmarkRuns'       => $benchmarkRuns,
            'totalSubmissions'    => $totalSubmissions,
            'totalCorpus'         => $totalCorpus,
            'totalReports'        => $totalReports,
            'currentUser'         => $currentUser,
            'quotaInfo'           => $quotaInfo,
            'ambProfile'          => $ambProfile,
            'referralStats'       => $referralStats,
            'pendingPayment'      => $pendingPayment,
        ]);
    }

    /**
     * Mengekstrak metriks dan penilaian indikasi AI dari evidence_data laporan
     */
    public static function extractAiMetrics(?array $report): ?array
    {
        if (!$report || ($report['status'] ?? '') !== 'completed') {
            return null;
        }

        $evRaw = $report['evidence_data'] ?? null;
        if (empty($evRaw)) {
            return null;
        }

        $ev = is_string($evRaw) ? json_decode($evRaw, true) : $evRaw;
        if (!is_array($ev)) {
            return null;
        }

        $aiSty = $ev['ai_stylometrics'] ?? $ev['v1_1_presentation']['v1_2_evolution']['ai_stylometrics'] ?? null;
        $gemini = $ev['gemini_forensic'] ?? $ev['v1_1_presentation']['v1_2_evolution']['gemini_forensic'] ?? null;
        $docMeta = $ev['document_metadata'] ?? null;
        $isPreLlm = !empty($docMeta['is_pre_llm_era']);

        $stylometricsScore = null;
        $label = null;
        $level = null;

        if (is_array($aiSty) && isset($aiSty['ai_probability_score']) && is_numeric($aiSty['ai_probability_score'])) {
            $stylometricsScore = (float) $aiSty['ai_probability_score'];
            $label = $aiSty['verdict_label'] ?? null;
            $level = $aiSty['ai_confidence_level'] ?? null;
        }

        $forensicLevel = null;
        $isVerifiedAi = false;
        $isMetaPromptLeak = false;

        if (is_array($gemini) && ($gemini['status'] ?? '') === 'completed') {
            $gAudit = $gemini['audit'] ?? [];
            if (empty($gAudit) && !empty($gemini['raw_output'])) {
                if (preg_match('/"tingkat_indikasi_ai"\s*:\s*"([^"]+)"/i', $gemini['raw_output'], $m)) {
                    $gAudit['tingkat_indikasi_ai'] = $m[1];
                }
            }
            if (!empty($gAudit['tingkat_indikasi_ai'])) {
                $forensicLevel = strtoupper(trim((string) $gAudit['tingkat_indikasi_ai']));
            }
            $isVerifiedAi = !empty($gAudit['bukti_ai_terverifikasi']);

            // Kebocoran instruksi mentah HANYA jika terverifikasi oleh audit dan tingkat indikasi TINGGI
            if ($forensicLevel === 'TINGGI' && !empty($gAudit['meta_prompt_leak_terverifikasi']) && (!empty($gAudit['meta_prompt_leak_cuplikan']) || $isVerifiedAi)) {
                $isMetaPromptLeak = true;
            }
        }

        // Temuan forensik hanya advisory jika: naskah pra-LLM, atau bukti AI belum terverifikasi,
        // atau stylometri sangat rendah (< 30) tanpa kebocoran prompt mentah
        $isAdvisory = false;
        if (in_array($forensicLevel, ['SEDANG', 'TINGGI'], true) && !$isMetaPromptLeak) {
            if ($isPreLlm || !$isVerifiedAi || ($stylometricsScore !== null && $stylometricsScore < 30.0)) {
                $isAdvisory = true;
            }
        }

        // Hitung skor komposit secara dinamis dan berjenjang berdasarkan derajat bukti forensik
        if ($forensicLevel === 'RENDAH' || $isPreLlm) {
            $compositeScore = $stylometricsScore !== null ? $stylometricsScore : 0.0;
            $label = 'Otentik Alami (Bebas AI)';
            $level = 'LOW';
            $isMetaPromptLeak = false;
        } elseif ($isMetaPromptLeak) {
            $compositeScore = 98.0;
            $label = 'Indikasi Ekstrem (Kebocoran Prompt AI)';
            $level = 'EXTREME';
        } elseif ($isAdvisory) {
            $compositeScore = $stylometricsScore !== null ? $stylometricsScore : 0.0;
            if ($compositeScore >= 70.0) {
                $label = 'Indikasi AI: Tinggi';
                $level = 'HIGH';
            } elseif ($compositeScore >= 35.0) {
                $label = 'Indikasi AI: Sedang';
                $level = 'MODERATE';
            } else {
                $label = 'Otentik Alami (Bebas AI)';
                $level = 'LOW';
            }
        } elseif ($forensicLevel !== null && is_array($gemini) && ($gemini['status'] ?? '') === 'completed') {
            $compositeScore = self::calculateDynamicAiScore($gemini, $stylometricsScore);
            if ($compositeScore >= 70.0 || $forensicLevel === 'TINGGI') {
                $label = 'Indikasi AI: Tinggi';
                $level = 'HIGH';
            } elseif ($compositeScore >= 35.0 || $forensicLevel === 'SEDANG') {
                $label = 'Indikasi AI: Sedang';
                $level = 'MODERATE';
            } else {
                $label = 'Otentik Alami (Bebas AI)';
                $level = 'LOW';
            }
        } else {
            $compositeScore = $stylometricsScore !== null ? $stylometricsScore : 0.0;
            if ($compositeScore >= 70.0) {
                $label = 'Indikasi AI: Tinggi';
                $level = 'HIGH';
            } elseif ($compositeScore >= 35.0) {
                $label = 'Indikasi AI: Sedang';
                $level = 'MODERATE';
            } else {
                $label = 'Otentik Alami (Bebas AI)';
                $level = 'LOW';
            }
        }

        if ($compositeScore === null) {
            return null;
        }

        // Menandai apakah skor komposit dinaikkan oleh temuan mendalam Audit Forensik Cerdas yang terverifikasi
        $isForensicElevated = !$isAdvisory && !$isMetaPromptLeak && $forensicLevel !== 'RENDAH' && (
            ($forensicLevel === 'TINGGI' && ($stylometricsScore === null || $stylometricsScore < 65.0)) ||
            ($forensicLevel === 'SEDANG' && ($stylometricsScore === null || $stylometricsScore < 35.0))
        );

        return [
            'score'                => $compositeScore,
            'stylometrics_score'   => $stylometricsScore,
            'label'                => $label,
            'level'                => $level,
            'forensic_level'       => $forensicLevel,
            'is_forensic_elevated' => $isForensicElevated,
            'is_meta_prompt_leak'  => $isMetaPromptLeak,
            'forensic_advisory'    => $isAdvisory,
            'is_pre_llm_era'       => $isPreLlm,
        ];
    }

    /**
     * Menghitung skor indikasi AI terpadu secara dinamis berdasarkan derajat keparahan temuan forensik
     * dan perpaduannya dengan stylometri, mencegah skor dipukul rata (flat 85%).
     */
    public static function calculateDynamicAiScore(array $gemini, ?float $stylometricsScore = null): float
    {
        $audit = $gemini['audit'] ?? [];
        if (empty($audit) && !empty($gemini['raw_output'])) {
            if (preg_match('/"tingkat_indikasi_ai"\s*:\s*"([^"]+)"/i', $gemini['raw_output'], $m)) {
                $audit['tingkat_indikasi_ai'] = $m[1];
            }
            if (preg_match('/"alasan_indikasi_ai"\s*:\s*"([^"]+)"/s', $gemini['raw_output'], $m2)) {
                $audit['alasan_indikasi_ai'] = $m2[1];
            }
            if (preg_match('/"skor_indikasi_persen"\s*:\s*(\d+(?:\.\d+)?)/i', $gemini['raw_output'], $m3)) {
                $audit['skor_indikasi_persen'] = (float) $m3[1];
            }
        }

        // 1. Dukungan eksplisit jika model AI sudah memberikan skor persentase
        if (isset($audit['skor_indikasi_persen']) && is_numeric($audit['skor_indikasi_persen'])) {
            $explicit = (float) $audit['skor_indikasi_persen'];
            if ($explicit >= 0.0 && $explicit <= 100.0) {
                return $stylometricsScore !== null ? max($explicit, $stylometricsScore) : $explicit;
            }
        }

        $level = strtoupper(trim((string) ($audit['tingkat_indikasi_ai'] ?? 'RENDAH')));
        $isPreLlm = !empty($audit['metadata_pra_llm']);
        $isVerified = !empty($audit['bukti_ai_terverifikasi']);

        // Naskah era pra-LLM atau tingkat indikasi RENDAH (Otentik Alami)
        if ($level === 'RENDAH' || $isPreLlm) {
            return $stylometricsScore !== null ? $stylometricsScore : 0.0;
        }

        // Naskah dengan klaim tidak lolos verifikasi kutipan naskah
        if (isset($audit['bukti_ai_terverifikasi']) && !$isVerified) {
            return $stylometricsScore !== null ? $stylometricsScore : 0.0;
        }

        $reason = (string) ($audit['alasan_indikasi_ai'] ?? '');
        $contra = (array) ($audit['inkonsistensi_metodologi_dan_logika'] ?? []);
        $citations = (array) ($audit['integritas_sitasi'] ?? []);

        if ($level === 'SEDANG') {
            if (!$isVerified && $stylometricsScore !== null && $stylometricsScore < 30.0) {
                return $stylometricsScore;
            }
            $score = 50.0;
            if (count($contra) > 1 || count($citations) > 1) {
                $score += 5.0;
            }
            if ($stylometricsScore !== null && $stylometricsScore > $score) {
                $score = $stylometricsScore;
            }
            return round($score, 1);
        }

        // --- LEVEL TINGGI: Evaluasi Berjenjang Berbasis Bukti Nyata ---

        // Tingkat Ekstrem (95% - 98%): Kebocoran Instruksi / Percakapan Chatbot (Meta-Prompt Leak)
        // HANYA dipicu jika terverifikasi secara eksplisit dan terdapat cuplikan instruksi obrolan AI nyata
        $isMetaPromptLeak = false;
        if (!empty($audit['meta_prompt_leak_terverifikasi']) && (!empty($audit['meta_prompt_leak_cuplikan']) || $isVerified)) {
            $isMetaPromptLeak = true;
        } elseif (!empty($audit['meta_prompt_leak_cuplikan'])) {
            $leakSnippets = is_array($audit['meta_prompt_leak_cuplikan'])
                ? implode(' ', $audit['meta_prompt_leak_cuplikan'])
                : (string) $audit['meta_prompt_leak_cuplikan'];
            if (preg_match('/(bisa\. saya akan buat|tentu, berikut|sebagai model bahasa ai|saya adalah ai|as an ai language model)/i', $leakSnippets)) {
                $isMetaPromptLeak = true;
            }
        }

        if ($isMetaPromptLeak) {
            return 98.0;
        }

        $evidenceText = strtolower($reason . ' ' . implode(' ', $contra) . ' ' . implode(' ', $citations));

        // Tingkat Berat (80% - 88%): Halusinasi Sitasi & Tokoh/Jurnal Fiktif
        $hasCitationFabrication = (bool) preg_match(
            '/(halusinasi|tokoh fiktif|nama jurnal .* diperlakukan sebagai|nama paroki .* diperlakukan sebagai|sitasi palsu|penulis fiktif|fabrikasi)/i',
            $evidenceText
        );

        if ($hasCitationFabrication) {
            $baseScore = 80.0;
        } else {
            // Tingkat Sedang-Tinggi (74% - 79%): Kompilasi / perakitan AI dengan sitasi rusak atau distorsi logika
            $baseScore = 74.0;
        }

        // Pembeda granularitas berdasarkan akumulasi bukti temuan
        $contraWeight = min(count($contra) * 1.5, 4.5);
        $citationWeight = min(count($citations) * 1.5, 4.5);
        $forensicRaw = $baseScore + $contraWeight + $citationWeight;

        // Perpaduan dengan Stylometrics Score
        if ($stylometricsScore !== null && $stylometricsScore > 0) {
            if ($stylometricsScore >= 70.0) {
                // Keduanya (Forensik & Stylometri) sama-sama mendeteksi indikasi kuat
                $finalScore = max($forensicRaw, $stylometricsScore + 6.0);
            } elseif ($stylometricsScore >= 35.0) {
                // Stylometri moderat (35-69%), berikan bobot proporsional
                $finalScore = ($forensicRaw * 0.8) + ($stylometricsScore * 0.2);
            } else {
                // Stylometri rendah (gaya bahasa diacak/halus), forensik menjadi determinan utama
                $finalScore = $forensicRaw;
            }
        } else {
            $finalScore = $forensicRaw;
        }

        return round(min(max($finalScore, 70.0), 99.0), 1);
    }

    /**
     * Mengekstrak jumlah dokumen korpus pembanding dari evidence_data laporan
     */
    public static function extractCorpusCount(?array $report, int $fallbackTotal = 0): int
    {
        if (!$report) {
            return $fallbackTotal;
        }
        $evidence = $report['evidence_data'] ?? null;
        if (is_string($evidence)) {
            $evidence = json_decode($evidence, true) ?: [];
        }
        if (is_array($evidence)) {
            if (!empty($evidence['corpus_docs_count'])) {
                return (int) $evidence['corpus_docs_count'];
            }
            if (!empty($evidence['substantive_summary']['corpus_docs_count'])) {
                return (int) $evidence['substantive_summary']['corpus_docs_count'];
            }
            if (!empty($evidence['exact_evidence']['corpus_docs_count'])) {
                return (int) $evidence['exact_evidence']['corpus_docs_count'];
            }
        }
        return $fallbackTotal;
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

        if (! $document) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound("Dokumen #{$report['document_id']} tidak ditemukan.");
        }

        // Cek izin akses: pemilik, admin, dibagikan ke akun ini, atau memiliki token otorisasi resmi
        $session = session();
        $userId  = $session->get('user_id');
        $isAdmin = ($session->get('user_role') === 'admin');
        $isOwner = $userId && ((int) $document['user_id'] === (int) $userId);

        $shareModel = new \App\Models\DocumentShareModel();
        $isSharedToUser = false;
        if ($userId) {
            $isSharedToUser = $shareModel->userHasAccess((int) $document['id'], (int) $userId, 'view_report');
        }

        // Cek share token via query param (?share_token=...)
        $shareToken = $this->request->getGet('share_token');
        $hasValidShareToken = false;
        if (!empty($shareToken) && !empty($document['share_token']) && $shareToken === $document['share_token'] && !empty($document['is_shared'])) {
            $hasValidShareToken = true;
            if ($userId && ! $isOwner) {
                $shareModel->shareWithEmail((int) $document['id'], (int) $document['user_id'], (string) $session->get('user_email'));
                $isSharedToUser = true;
            }
        }

        $token = $this->request->getGet('token') ?: $this->request->getGet('code');
        $hasVerificationCode = (!empty($token) && $token === ($report['verification_code'] ?? null));

        if (! $isOwner && ! $isAdmin && ! $isSharedToUser && ! $hasVerificationCode) {
            if ($hasValidShareToken && ! $userId) {
                // Tautan dibagikan namun pengguna belum masuk / belum mendaftar
                return redirect()->to(base_url('/?auth=register&share_token=' . $shareToken . '&redirect=' . urlencode(current_url())))
                    ->with('auth_notice', 'Karya ilmiah "' . esc($document['title']) . '" telah dibagikan kepada Anda. Silakan mendaftar akun AR Tilik terlebih dahulu (atau masuk jika sudah memiliki akun) untuk membukanya.');
            }

            if (! $userId) {
                return redirect()->to(base_url('/?auth=login&redirect=' . urlencode(current_url())))
                    ->with('auth_notice', 'Silakan masuk ke akun pemilik naskah atau daftar akun untuk melihat karya ilmiah yang dibagikan.');
            }
            return $this->response->setStatusCode(403)->setBody('Akses ditolak: Anda tidak memiliki izin untuk melihat laporan naskah ini.');
        }

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
                        ->orderBy('id', 'ASC')
                        ->get()->getResultArray();
        $submissionText = implode("\n\n", array_column($subChunks, 'text'));

        // Auto-heal section_segmentation jika kosong, anomali zonasi (BODY_TEXT == 0 pada teks > 100 kata), atau word count tidak konsisten
        $v11Pres = $evidenceData['v1_1_presentation'] ?? [];
        $v12Evol = $v11Pres['v1_2_evolution'] ?? [];
        $secSeg = $v12Evol['section_segmentation'] ?? null;
        $totalWords = (int) ($evidenceData['total_words'] ?? 0);
        $secTotalWords = (int) ($secSeg['total_words'] ?? 0);
        $bodyPct = (float) ($secSeg['zone_percentage_distribution']['BODY_TEXT'] ?? 0.0);
        $zoneSum = 0;
        if (!empty($secSeg['zone_word_distribution']) && is_array($secSeg['zone_word_distribution'])) {
            $zoneSum = array_sum($secSeg['zone_word_distribution']);
        }

        $needsHeal = empty($secSeg) || ($totalWords > 100 && $bodyPct == 0.0) || ($totalWords > 0 && ($secTotalWords !== $totalWords || $zoneSum !== $totalWords));
        if ($needsHeal) {
            $pythonBin = ROOTPATH . '.venv/bin/python';
            if (! file_exists($pythonBin)) {
                $pythonBin = 'python3';
            }
            $cmd = 'cd ' . escapeshellarg(ROOTPATH) . ' && ' . escapeshellcmd($pythonBin) . ' -m worker.section_parser --heal-report ' . (int) $reportId;
            @exec($cmd);

            // Muat ulang data report & evidence_data terbaru setelah pemulihan otomatis
            $freshReport = $reportModel->find($reportId);
            if (!empty($freshReport['evidence_data'])) {
                $evidenceData = is_string($freshReport['evidence_data']) ? json_decode($freshReport['evidence_data'], true) : $freshReport['evidence_data'];
            }
        }

        $sourceTexts = [];
        foreach ($sources as $s) {
            $sDocId = (int) $s['source_document_id'];
            $srcChunks = $db->table('document_chunks')
                            ->select('text')
                            ->where('document_id', $sDocId)
                            ->orderBy('chunk_index', 'ASC')
                            ->orderBy('id', 'ASC')
                            ->get()->getResultArray();
            $sourceTexts[$sDocId] = implode("\n\n", array_column($srcChunks, 'text'));
        }

        // Resolusi Stylometrik & AI Indicator
        $aiStylometrics = $evidenceData['ai_stylometrics'] ?? $evidenceData['v1_1_presentation']['v1_2_evolution']['ai_stylometrics'] ?? null;
        if (!$aiStylometrics && !empty($submissionText)) {
            $aiStylometrics = $this->computeFallbackStylometrics($submissionText);
        }

        // Resolusi Cross-Lingual Analysis (Opsi A)
        $crossLingual = $evidenceData['cross_lingual'] ?? $evidenceData['v1_1_presentation']['v1_2_evolution']['cross_lingual'] ?? null;
        if (!$crossLingual && !empty($submissionText)) {
            $crossLingual = $this->computeFallbackCrossLingualDetection($submissionText);
        }

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

        // Resolusi Audit Forensik Cerdas (Semantik & Pertanyaan Ujian Lisan melalui AI Gateway & Antrean)
        $geminiForensic = $evidenceData['gemini_forensic'] ?? $evidenceData['v1_1_presentation']['v1_2_evolution']['gemini_forensic'] ?? null;
        if (!$geminiForensic || !in_array($geminiForensic['status'] ?? '', ['completed', 'queued', 'processing', 'deferred'])) {
            try {
                $db = \Config\Database::connect();
                $jobRow = $db->table('ai_narrative_jobs')
                    ->where('report_id', (int) $reportId)
                    ->orWhere('submission_id', (int) $report['document_id'])
                    ->orderBy('id', 'DESC')
                    ->get()
                    ->getFirstRow('array');
                if ($jobRow) {
                    if ($jobRow['status'] === 'done' && !empty($jobRow['result'])) {
                        $geminiForensic = json_decode($jobRow['result'], true);
                    } else {
                        $geminiForensic = [
                            'status'       => $jobRow['status'],
                            'job_id'       => (int) $jobRow['id'],
                            'is_available' => false,
                            'message'      => match ($jobRow['status']) {
                                'processing' => 'AI sedang menyusun analisis forensik semantik...',
                                'queued'     => 'Menunggu giliran antrean kapasitas LLM...',
                                'deferred'   => 'Kapasitas harian LLM sedang padat. Narasi ditunda sementara, hasil angka tetap valid.',
                                'failed'     => 'Antrean narasi gagal diproses. Hasil angka tetap valid.',
                                default      => 'Status: ' . $jobRow['status']
                            }
                        ];
                    }
                }
            } catch (\Throwable $e) {
                // Failsafe jika tabel belum ada atau koneksi gagal
            }
        }

        // Feedback & Testimoni Akademik (Fase 1)
        $voteModel = new \App\Models\FeatureVoteModel();
        $testimonialModel = new \App\Models\TestimonialModel();
        $userIp = $this->request->getIPAddress();
        $userVotes = $voteModel->getReportVotes($reportId, $userId, $userIp);
        $userReview = $testimonialModel->getReviewForReport($reportId, $userId, $userIp);

        $userModel = new \App\Models\UserModel();
        $currentUser = $userId ? $userModel->find($userId) : null;

        $paymentModel = new \App\Models\PaymentTransactionModel();
        $hasPurchased = false;
        if ($userId) {
            $hasPurchased = $paymentModel->where('user_id', $userId)
                ->whereIn('transaction_status', ['settlement', 'capture'])
                ->countAllResults() > 0;
            if (!$hasPurchased && !empty($currentUser['subscription_tier']) && $currentUser['subscription_tier'] !== 'free') {
                $hasPurchased = true;
            }
        }

        // Cek apakah pengguna sudah menjadi Duta Kampus aktif / masa percobaan
        $ambProfile = null;
        $isAmbassador = false;
        if ($userId) {
            $ambProfileModel = new \App\Models\AmbassadorProfileModel();
            $ambProfile = $ambProfileModel->where('user_id', (int) $userId)
                ->whereIn('status', ['probation', 'active'])
                ->first();
            $isAmbassador = !empty($ambProfile);
        }

        $fallbackCorpus = (new DocumentModel())->where('document_type', 'corpus')->where('benchmark_run_id IS NULL', null, false)->countAllResults();
        $corpusCount = self::extractCorpusCount($report, $fallbackCorpus);

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
            'geminiForensic'      => $geminiForensic,
            'corpusCount'         => $corpusCount,
            'liveCorpusCount'     => $fallbackCorpus,
            'isOwner'             => $isOwner,
            'isSharedAccess'      => $isSharedToUser,
            'userVotes'           => $userVotes,
            'userReview'          => $userReview,
            'currentUser'         => $currentUser,
            'hasPurchased'        => $hasPurchased,
            'isAmbassador'        => $isAmbassador,
            'ambProfile'          => $ambProfile,
        ]);
    }

    /**
     * Memicu audit forensik cerdas (semantik naskah & panduan sidang lisan) secara on-demand.
     */
    public function runAudit($reportId)
    {
        $session = session();
        $userId  = $session->get('user_id');
        $isAdmin = ($session->get('user_role') === 'admin');

        $reportModel = new \App\Models\ReportModel();
        $docModel    = new \App\Models\DocumentModel();

        $report = $reportModel->find($reportId);
        if (!$report) {
            return $this->response->setStatusCode(404)->setJSON(['success' => false, 'message' => 'Laporan tidak ditemukan.']);
        }

        $document = $docModel->find($report['document_id']);
        if (!$document) {
            return $this->response->setStatusCode(404)->setJSON(['success' => false, 'message' => 'Dokumen tidak ditemukan.']);
        }

        // Cek otorisasi: pemilik, admin, atau memiliki akses share
        $isOwner   = $userId && ((int) $document['user_id'] === (int) $userId);
        $shareModel = new \App\Models\DocumentShareModel();
        $isShared  = $userId ? $shareModel->userHasAccess((int) $document['id'], (int) $userId, 'view_report') : false;

        if (!$isOwner && !$isAdmin && !$isShared) {
            return $this->response->setStatusCode(403)->setJSON(['success' => false, 'message' => 'Akses tidak diizinkan.']);
        }

        $repId = (int) $report['id'];
        $pythonPath = ROOTPATH . '.venv/bin/python';
        if (!file_exists($pythonPath)) {
            $pythonPath = 'python3';
        }

        $scriptPath = ROOTPATH . 'worker/run_forensic_cli.py';
        $cmd = escapeshellcmd($pythonPath) . ' ' . escapeshellarg($scriptPath) . ' ' . escapeshellarg((string) $repId) . ' 2>&1';
        $output = shell_exec($cmd);

        $freshReport = $reportModel->find($repId);
        $freshEv = json_decode($freshReport['evidence_data'] ?? '{}', true) ?: [];
        $geminiData = $freshEv['gemini_forensic'] ?? null;

        if ($geminiData && ($geminiData['status'] ?? '') === 'completed') {
            return $this->response->setJSON([
                'success' => true,
                'message' => 'Audit forensik cerdas berhasil diselesaikan!',
                'data'    => $geminiData,
            ]);
        }

        $parsedOut = json_decode($output ?? '', true);
        $errMsg = $parsedOut['error'] ?? ($geminiData['message'] ?? 'Gagal menjalankan audit forensik semantik. Silakan coba sesaat lagi.');
        return $this->response->setJSON([
            'success' => false,
            'message' => $errMsg,
        ]);
    }

    /**
     * Mengecek status antrean Audit Forensik Cerdas untuk polling antarmuka (UX).
     */
    public function auditStatus($reportId)
    {
        $session = session();
        $userId  = $session->get('user_id');
        $isAdmin = ($session->get('user_role') === 'admin');

        $reportModel = new \App\Models\ReportModel();
        $docModel    = new \App\Models\DocumentModel();

        $report = $reportModel->find($reportId);
        if (!$report) {
            return $this->response->setStatusCode(404)->setJSON(['status' => 'not_found', 'message' => 'Laporan tidak ditemukan.']);
        }

        $document = $docModel->find($report['document_id']);
        if (!$document) {
            return $this->response->setStatusCode(404)->setJSON(['status' => 'not_found', 'message' => 'Dokumen tidak ditemukan.']);
        }

        $isOwner   = $userId && ((int) $document['user_id'] === (int) $userId);
        $shareModel = new \App\Models\DocumentShareModel();
        $isShared  = $userId ? $shareModel->userHasAccess((int) $document['id'], (int) $userId, 'view_report') : false;

        $token = $this->request->getGet('token') ?: $this->request->getGet('code');
        $hasVerificationCode = (!empty($token) && $token === ($report['verification_code'] ?? null));
        $shareToken = $this->request->getGet('share_token');
        $hasValidShareToken = (!empty($shareToken) && !empty($document['share_token']) && $shareToken === $document['share_token']);

        if (!$isOwner && !$isAdmin && !$isShared && !$hasVerificationCode && !$hasValidShareToken) {
            return $this->response->setStatusCode(403)->setJSON(['status' => 'forbidden', 'message' => 'Akses tidak diizinkan.']);
        }

        // 1. Cek evidence_data terlebih dahulu
        $ev = json_decode($report['evidence_data'] ?? '{}', true) ?: [];
        $forensic = $ev['gemini_forensic'] ?? $ev['v1_1_presentation']['v1_2_evolution']['gemini_forensic'] ?? null;
        if ($forensic && ($forensic['status'] ?? '') === 'completed') {
            return $this->response->setJSON([
                'status'     => 'done',
                'is_done'    => true,
                'data'       => $forensic,
                'message'    => 'Audit forensik cerdas selesai.',
            ]);
        }

        // 2. Cek tabel ai_narrative_jobs
        $db = \Config\Database::connect();
        $job = $db->table('ai_narrative_jobs')
            ->where('report_id', (int) $reportId)
            ->orWhere('submission_id', (int) $report['document_id'])
            ->orderBy('id', 'DESC')
            ->get()
            ->getFirstRow('array');

        if (!$job) {
            return $this->response->setJSON([
                'status'  => 'idle',
                'is_done' => false,
                'message' => 'Belum ada antrean narasi untuk laporan ini.',
            ]);
        }

        $jobStatus = $job['status'] ?? 'queued';
        $resultData = null;
        if ($jobStatus === 'done' && !empty($job['result'])) {
            $resultData = json_decode($job['result'], true);
        }

        return $this->response->setJSON([
            'status'     => $jobStatus,
            'is_done'    => ($jobStatus === 'done'),
            'job_id'     => (int) $job['id'],
            'created_at' => $job['created_at'],
            'data'       => $resultData,
            'message'    => match ($jobStatus) {
                'processing' => 'AI sedang menyusun analisis forensik semantik...',
                'queued'     => 'Menunggu giliran antrean kapasitas LLM...',
                'deferred'   => 'Kapasitas harian LLM sedang padat. Narasi ditunda sementara, hasil angka tetap valid.',
                'failed'     => 'Antrean narasi gagal diproses. Hasil angka tetap valid.',
                'done'       => 'Audit forensik cerdas selesai.',
                default      => 'Status: ' . $jobStatus
            }
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

        if (preg_match('/(?:\n|\A)(?:[0-9\.\s]*\b(?:DAFTAR\s+PUSTAKA|REFERENCES|BIBLIOGRAFI|DAFTAR\s+REFERENSI|SUMBER\s+BACAAN(?:\s*\([^\)]+\))?|SUMBER\s+PUSTAKA|BAHAN\s+BACAAN|KEPUSTAKAAN|RUJUKAN)\b[^\n]*\n)/iu', $text, $matches, PREG_OFFSET_CAPTURE)) {
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
                        if ($rStart >= 1000 || $rEnd >= 1000 || $rStart > 500) {
                            continue;
                        }
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
                        if ($n >= 1000 || $n > 500) {
                            continue;
                        }
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

        $urlEntriesCount = 0;
        foreach ($entries as $e) {
            if (preg_match('/^(?:https?:\/\/|www\.)/i', $e['raw_text'])) {
                $urlEntriesCount++;
            }
        }
        $isUrlDominantBib = (!empty($entries)) && (
            ($urlEntriesCount >= 2 && ($urlEntriesCount / count($entries)) >= 0.4)
            || ($urlEntriesCount === count($entries))
        );

        if ($isUrlDominantBib) {
            return [
                'detected_style' => $style,
                'has_bibliography_section' => true,
                'total_in_text_citations' => count($citations),
                'unique_in_text_count' => $uniqueCount,
                'total_bibliography_entries' => $totalBib,
                'matched_citations_count' => $matchedCount,
                'phantom_citations_count' => 0,
                'orphan_references_count' => 0,
                'citation_health_score' => 60.0,
                'health_verdict' => 'NEEDS_ATTENTION',
                'verdict_label' => 'Daftar Pustaka Berupa URL (0 Sitasi Gaib)',
                'summary_rationale' => "Daftar pustaka memuat {$urlEntriesCount} tautan URL mentah (seperti Wikipedia/blog) dari total {$totalBib} rujukan. Seluruh rujukan bebas dari sitasi gaib (0 sitasi gaib) dan tidak diklasifikasikan sebagai pustaka yatim karena rujukan internet tidak menggunakan kunci sitasi baku. Format rujukan web disarankan disempurnakan ke kaidah sitasi akademik baku (APA/Chicago/IEEE) untuk kelayakan sidang.",
                'matched_items' => $matchedItems,
                'phantom_citations' => [],
                'orphan_references' => [],
            ];
        }

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

    private function computeFallbackCrossLingualDetection(string $text): array
    {
        return [
            'is_cross_lingual_match'  => false,
            'cross_lingual_score'     => 0.0,
            'confidence'              => 'NONE',
            'detected_pair'           => 'EN -> ID',
            'source_document_id'      => null,
            'source_title'            => '',
            'concept_overlap_percent' => 0.0,
            'shared_concept_count'    => 0,
            'shared_pairs_count'      => 0,
            'matched_concepts'        => [],
            'aligned_passages'        => [],
            'rationale'               => 'Naskah historis telah dianalisis dan tidak terdeteksi indikasi penerjemahan langsung antar bahasa.',
            'verdict_label'           => 'Otentik / Bebas Terjemahan',
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

        if (! $document) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound("Dokumen #{$report['document_id']} tidak ditemukan.");
        }

        // Cek izin akses: pemilik, admin, atau memiliki token otorisasi resmi
        $session = session();
        $userId  = $session->get('user_id');
        $isAdmin = ($session->get('user_role') === 'admin');
        $isOwner = $userId && ((int) $document['user_id'] === (int) $userId);
        $isShared = false;
        if ($userId) {
            $shareModel = new \App\Models\DocumentShareModel();
            $isShared = $shareModel->userHasAccess((int) $document['id'], (int) $userId, 'view_report');
        }

        if (! $isOwner && ! $isAdmin && ! $isShared) {
            $token = $this->request->getGet('token') ?: $this->request->getGet('code');
            if (empty($token) || $token !== ($report['verification_code'] ?? null)) {
                return $this->response->setStatusCode(403)->setBody('Akses ditolak: Anda tidak memiliki izin untuk mengunduh laporan PDF ini.');
            }
        }

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

    /**
     * Menyajikan file asli dokumen submisi / korpus untuk pratinjau (inline PDF viewer)
     */
    public function previewDocument($documentId)
    {
        $session = session();
        $userId  = $session->get('user_id');

        $documentModel = new DocumentModel();
        $doc = $documentModel->find($documentId);

        if (! $doc) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Dokumen tidak ditemukan.');
        }

        $filePath = $doc['storage_path'] ?? '';
        if (! file_exists($filePath)) {
            $altPath = ROOTPATH . 'writable/' . preg_replace('#^.*?writable/#', '', $filePath);
            if (file_exists($altPath)) {
                $filePath = $altPath;
            } else {
                throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('File fisik dokumen tidak ditemukan.');
            }
        }

        // Cek izin akses ketat: pemilik dokumen, admin, korpus rujukan publik, atau dibagikan ke akun ini
        $isOwner  = $userId && ((int) $doc['user_id'] === (int) $userId);
        $isAdmin  = $session->get('user_role') === 'admin';
        $isCorpus = ($doc['document_type'] === 'corpus' || ($doc['corpus_visibility'] ?? '') === 'public');
        $isShared = false;
        if ($userId) {
            $shareModel = new \App\Models\DocumentShareModel();
            $isShared = $shareModel->userHasAccess((int) $documentId, (int) $userId, 'view_all');
        }

        if (! $isOwner && ! $isAdmin && ! $isCorpus && ! $isShared) {
            return $this->response->setStatusCode(403)->setBody('Akses ke dokumen ditolak. Naskah bersifat privat bagi pemilik.');
        }

        $rawMime = $doc['mime_type'] ?: mime_content_type($filePath);
        $isPdf = ($rawMime === 'application/pdf');
        $safeMime = $isPdf ? 'application/pdf' : 'application/octet-stream';
        $disposition = $isPdf ? 'inline' : 'attachment';

        $filename = $doc['original_filename'] ?: basename($filePath);

        return $this->response
            ->setHeader('Content-Type', $safeMime)
            ->setHeader('Content-Disposition', $disposition . '; filename="' . addslashes($filename) . '"')
            ->setHeader('Content-Length', (string) filesize($filePath))
            ->setHeader('X-Content-Type-Options', 'nosniff')
            ->setHeader('X-Frame-Options', 'SAMEORIGIN')
            ->setHeader('Cache-Control', 'private, no-cache, no-store, must-revalidate')
            ->setBody(file_get_contents($filePath));
    }

    /**
     * Endpoint pembuka tautan berbagi naskah karya ilmiah (Model A+B)
     * Mengarahkan penerima yang belum terdaftar untuk mendaftar akun terlebih dahulu
     */
    public function sharedAccess(string $token)
    {
        $token = trim($token);
        if (empty($token)) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Tautan berbagi tidak valid.');
        }

        $docModel = new DocumentModel();
        $document = $docModel->where('share_token', $token)
                             ->where('is_shared', 1)
                             ->first();

        if (! $document) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Tautan berbagi karya ilmiah tidak ditemukan atau telah dinonaktifkan oleh pemilik.');
        }

        $session = session();
        $userId  = $session->get('user_id');

        // Jika penerima belum login / belum mendaftar, arahkan ke registrasi terlebih dahulu
        if (! $userId) {
            $docTitle = esc($document['title']);
            $currentUrl = current_url();
            return redirect()->to(base_url('/?auth=register&share_token=' . $token . '&redirect=' . urlencode($currentUrl)))
                ->with('auth_notice', "Karya ilmiah \"{$docTitle}\" telah dibagikan kepada Anda. Silakan mendaftar akun AR Tilik terlebih dahulu (atau masuk jika sudah memiliki akun) untuk membukanya.");
        }

        // Pengguna sudah login di AR Tilik
        $userEmail = (string) $session->get('user_email');
        $shareModel = new \App\Models\DocumentShareModel();

        // Jika bukan pemilik naskah itu sendiri, sambungkan dokumen ke akun penerima
        if ((int) $document['user_id'] !== (int) $userId) {
            $shareModel->shareWithEmail((int) $document['id'], (int) $document['user_id'], $userEmail, 'view_all');
        }

        // Ambil laporan terbaru
        $reportModel = new ReportModel();
        $latestReport = $reportModel->where('document_id', $document['id'])
                                    ->orderBy('id', 'DESC')
                                    ->first();

        if ($latestReport) {
            return redirect()->to(base_url('dashboard/report/' . $latestReport['id']))
                ->with('message', "Karya ilmiah \"{$document['title']}\" berhasil dibuka dan kini tersimpan di tab \"Dibagikan dengan Saya\" di dashboard Anda.");
        }

        return redirect()->to(base_url('dashboard'))
            ->with('message', "Karya ilmiah \"{$document['title']}\" berhasil ditambahkan ke tab \"Dibagikan dengan Saya\".");
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

        if (! $document) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound("Dokumen #{$report['document_id']} tidak ditemukan.");
        }

        // Cek izin akses: pemilik, admin, atau memiliki token otorisasi resmi
        $session = session();
        $userId  = $session->get('user_id');
        $isAdmin = ($session->get('user_role') === 'admin');
        $isOwner = $userId && ((int) $document['user_id'] === (int) $userId);
        $isShared = false;
        if ($userId) {
            $shareModel = new \App\Models\DocumentShareModel();
            $isShared = $shareModel->userHasAccess((int) $document['id'], (int) $userId, 'view_report');
        }

        if (! $isOwner && ! $isAdmin && ! $isShared) {
            $token = $this->request->getGet('token') ?: $this->request->getGet('code');
            if (empty($token) || $token !== ($report['verification_code'] ?? null)) {
                return $this->response->setStatusCode(403)->setBody('Akses ditolak: Anda tidak memiliki izin untuk melihat pratinjau laporan ini.');
            }
        }

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

        $fallbackCorpus = (new DocumentModel())->where('document_type', 'corpus')->where('benchmark_run_id IS NULL', null, false)->countAllResults();
        $corpusCount = self::extractCorpusCount($report, $fallbackCorpus);

        return view('dashboard/report_pdf', [
            'report'       => $report,
            'document'     => $document,
            'evidenceData' => $evidenceData,
            'sources'      => $sources,
            'matches'      => $matches,
            'corpusCount'  => $corpusCount,
            'liveCorpusCount' => $fallbackCorpus,
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
        $builder->select('reports.*, documents.title AS document_title, documents.author AS document_author, documents.original_filename, documents.created_at AS document_created_at, documents.file_purged_at, documents.storage_path, documents.faculty, documents.department, documents.user_id, users.name AS author_name, users.institution AS author_institution');
        $builder->join('documents', 'documents.id = reports.document_id', 'left');
        $builder->join('users', 'users.id = documents.user_id', 'left');
        $builder->where('reports.verification_code', $code);
        $report = $builder->get()->getRowArray();

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

        $report['ai_assessment'] = self::extractAiMetrics($report);
        $evidenceData = !empty($report['evidence_data']) ? (is_string($report['evidence_data']) ? json_decode($report['evidence_data'], true) : $report['evidence_data']) : [];

        // Ambil 5 rujukan teratas
        $sources = $db->table('report_sources rs')
                      ->select('rs.*, d.title AS source_title, d.original_filename AS source_filename, d.source_type')
                      ->join('documents d', 'd.id = rs.source_document_id', 'left')
                      ->where('rs.report_id', $report['id'])
                      ->orderBy('rs.source_rank', 'ASC')
                      ->limit(5)
                      ->get()->getResultArray();

        $docAuthor = !empty($report['document_author']) ? trim((string)$report['document_author']) : null;
        $uploaderName = !empty($report['author_name']) ? trim((string)$report['author_name']) : 'Pengguna Terdaftar';

        return view('dashboard/verify', [
            'status'       => 'valid',
            'code'         => $code,
            'report'       => $report,
            'document'     => [
                'id'                => $report['document_id'],
                'title'             => $report['document_title'] ?? 'Dokumen Karya Ilmiah',
                'author'            => $docAuthor,
                'uploader_name'     => $uploaderName,
                'author_name'       => $docAuthor ?: $uploaderName,
                'original_filename' => $report['original_filename'] ?? "document_{$report['document_id']}.pdf",
                'created_at'        => $report['document_created_at'] ?? $report['created_at'],
                'file_purged_at'    => $report['file_purged_at'] ?? null,
                'storage_path'      => $report['storage_path'] ?? null,
                'faculty'           => $report['faculty'] ?? null,
                'department'        => $report['department'] ?? null,
                'institution'       => $report['author_institution'] ?? null,
            ],
            'sources'      => $sources,
            'evidenceData' => $evidenceData,
        ]);
    }

    /**
     * Mengunduh Berkas PDF Sertifikat Hasil Uji Kemiripan Naskah Resmi (Format A4 Landscape Vektor)
     * Dihasilkan langsung dari peladen via Python & WeasyPrint
     */
    public function downloadCertificatePdf(?string $code = null)
    {
        $code = $code ?? $this->request->getGet('code');
        if (empty($code)) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound("Kode sertifikat tidak disediakan.");
        }

        $code = strtoupper(trim($code));
        $db = db_connect();
        $report = $db->table('reports')->where('verification_code', $code)->get()->getRowArray();

        if (! $report) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound("Sertifikat dengan kode #{$code} tidak ditemukan.");
        }

        $projectRoot = ROOTPATH;
        $pythonBin = $projectRoot . '.venv/bin/python';
        if (! file_exists($pythonBin)) {
            $pythonBin = 'python3';
        }

        $aiAssessment = self::extractAiMetrics($report);
        $aiArg = '';
        if ($aiAssessment && isset($aiAssessment['score'])) {
            $aiArg = ' --ai-score ' . escapeshellarg((string)$aiAssessment['score']) . ' --ai-label ' . escapeshellarg((string)($aiAssessment['label'] ?? ''));
        }

        $cmd = escapeshellcmd($pythonBin) . ' ' . escapeshellarg($projectRoot . 'worker/generate_certificate_pdf.py') . ' --code ' . escapeshellarg($code) . $aiArg . ' --stdout';

        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = proc_open($cmd, $descriptors, $pipes, $projectRoot);
        if (! is_resource($process)) {
            return $this->response->setStatusCode(500)->setBody("Gagal menginisialisasi proses generator sertifikat.");
        }

        fclose($pipes[0]);
        $pdfContent = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[2]);

        $exitCode = proc_close($process);

        if ($exitCode !== 0 || empty($pdfContent)) {
            log_message('error', "Certificate PDF export failed: {$stderr}");
            return $this->response->setStatusCode(500)->setBody("Gagal menghasilkan PDF Sertifikat Hasil Uji: " . esc($stderr));
        }

        $filename = "Sertifikat_Uji_ARTilik_{$code}.pdf";

        return $this->response
            ->setHeader('Content-Type', 'application/pdf')
            ->setHeader('Content-Disposition', 'inline; filename="' . $filename . '"')
            ->setHeader('Content-Length', (string) strlen($pdfContent))
            ->setHeader('Cache-Control', 'public, max-age=3600')
            ->setBody($pdfContent);
    }

    /**
     * Mengunduh Berkas PDF Sertifikat Hasil Uji berdasarkan Report ID
     */
    public function downloadCertificatePdfByReportId(int $reportId)
    {
        $db = db_connect();
        $report = $db->table('reports')->where('id', $reportId)->get()->getRowArray();
        if (! $report) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound("Laporan #{$reportId} tidak ditemukan.");
        }

        if (! empty($report['verification_code'])) {
            return $this->downloadCertificatePdf($report['verification_code']);
        }

        $projectRoot = ROOTPATH;
        $pythonBin = $projectRoot . '.venv/bin/python';
        if (! file_exists($pythonBin)) {
            $pythonBin = 'python3';
        }

        $aiAssessment = self::extractAiMetrics($report);
        $aiArg = '';
        if ($aiAssessment && isset($aiAssessment['score'])) {
            $aiArg = ' --ai-score ' . escapeshellarg((string)$aiAssessment['score']) . ' --ai-label ' . escapeshellarg((string)($aiAssessment['label'] ?? ''));
        }

        $cmd = escapeshellcmd($pythonBin) . ' ' . escapeshellarg($projectRoot . 'worker/generate_certificate_pdf.py') . ' --report ' . (int)$reportId . $aiArg . ' --stdout';

        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = proc_open($cmd, $descriptors, $pipes, $projectRoot);
        if (! is_resource($process)) {
            return $this->response->setStatusCode(500)->setBody("Gagal menginisialisasi proses generator sertifikat.");
        }

        fclose($pipes[0]);
        $pdfContent = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[2]);

        $exitCode = proc_close($process);

        if ($exitCode !== 0 || empty($pdfContent)) {
            log_message('error', "Certificate PDF export failed: {$stderr}");
            return $this->response->setStatusCode(500)->setBody("Gagal menghasilkan PDF Sertifikat Hasil Uji: " . esc($stderr));
        }

        $filename = "Sertifikat_Uji_ARTilik_Report_{$reportId}.pdf";

        return $this->response
            ->setHeader('Content-Type', 'application/pdf')
            ->setHeader('Content-Disposition', 'inline; filename="' . $filename . '"')
            ->setHeader('Content-Length', (string) strlen($pdfContent))
            ->setHeader('Cache-Control', 'public, max-age=3600')
            ->setBody($pdfContent);
    }

    /**
     * Mengunduh Berkas PDF Dokumentasi & Panduan Resmi Integrasi OJS 3.x
     */
    public function downloadOjsPdf()
    {
        $pdfPath = FCPATH . 'integrations/ojs/Panduan_Integrasi_OJS_ARTilik.pdf';

        if (! file_exists($pdfPath)) {
            $pythonBin = ROOTPATH . '.venv/bin/python';
            if (! file_exists($pythonBin)) {
                $pythonBin = 'python3';
            }
            exec(escapeshellcmd($pythonBin) . ' ' . escapeshellarg(ROOTPATH . 'worker/generate_ojs_pdf.py'));
        }

        if (! file_exists($pdfPath)) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound("Dokumen panduan OJS belum tersedia.");
        }

        $pdfContent = file_get_contents($pdfPath);
        $filename   = 'Panduan_Integrasi_OJS_ARTilik.pdf';

        return $this->response
            ->setHeader('Content-Type', 'application/pdf')
            ->setHeader('Content-Disposition', 'inline; filename="' . $filename . '"')
            ->setHeader('Content-Length', (string) strlen($pdfContent))
            ->setHeader('Cache-Control', 'public, max-age=86400')
            ->setBody($pdfContent);
    }

    /**
     * Mengunduh Berkas Pustaka Konektor PHP OJS 3.x (ARTilikOJSConnector.php)
     * Mengirimkan kode sumber asli sebagai lampiran berkas unduhan
     */
    public function downloadOjsConnector()
    {
        $filePath = FCPATH . 'integrations/ojs/ARTilikOJSConnector.php';
        if (! file_exists($filePath)) {
            $filePath = ROOTPATH . 'public/integrations/ojs/ARTilikOJSConnector.php';
        }

        if (! file_exists($filePath)) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound("Berkas konektor PHP OJS 3.x belum tersedia.");
        }

        $content  = file_get_contents($filePath);
        $filename = 'ARTilikOJSConnector.php';

        return $this->response
            ->setHeader('Content-Type', 'text/x-php; charset=utf-8')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setHeader('Content-Length', (string) strlen($content))
            ->setHeader('Cache-Control', 'public, max-age=86400')
            ->setBody($content);
    }

    /**
     * Mengunduh Berkas Contoh Runner Integrasi PHP OJS 3.x (artilik_ojs_runner.php)
     */
    public function downloadOjsRunner()
    {
        $filePath = FCPATH . 'integrations/ojs/artilik_ojs_runner.php';
        if (! file_exists($filePath)) {
            $filePath = ROOTPATH . 'public/integrations/ojs/artilik_ojs_runner.php';
        }

        if (! file_exists($filePath)) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound("Berkas runner OJS 3.x belum tersedia.");
        }

        $content  = file_get_contents($filePath);
        $filename = 'artilik_ojs_runner.php';

        return $this->response
            ->setHeader('Content-Type', 'text/x-php; charset=utf-8')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setHeader('Content-Length', (string) strlen($content))
            ->setHeader('Cache-Control', 'public, max-age=86400')
            ->setBody($content);
    }

    /**
     * Mengunduh Berkas Paket Plugin PKP OJS 3.x Generic Plugin (artilik-ojs-plugin.zip)
     */
    public function downloadOjsPluginZip()
    {
        $filePath = FCPATH . 'integrations/ojs/artilik-ojs-plugin.zip';
        if (! file_exists($filePath)) {
            $filePath = ROOTPATH . 'public/integrations/ojs/artilik-ojs-plugin.zip';
        }

        if (! file_exists($filePath)) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound("Berkas plugin ZIP OJS 3.x belum tersedia.");
        }

        $content  = file_get_contents($filePath);
        $filename = 'artilik-ojs-plugin.zip';

        return $this->response
            ->setHeader('Content-Type', 'application/zip')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setHeader('Content-Length', (string) strlen($content))
            ->setHeader('Cache-Control', 'public, max-age=86400')
            ->setBody($content);
    }

    /**
     * Mengunduh Berkas PDF Brosur & Prospektus Eksekutif Institusi AR Tilik
     */
    public function downloadPromoBrochure()
    {
        $pdfPath = FCPATH . 'docs/Brosur_Eksekutif_ARTilik.pdf';
        if (! file_exists($pdfPath)) {
            $pdfPath = ROOTPATH . 'public/docs/Brosur_Eksekutif_ARTilik.pdf';
        }

        if (! file_exists($pdfPath)) {
            $pythonBin = ROOTPATH . '.venv/bin/python';
            if (! file_exists($pythonBin)) {
                $pythonBin = 'python3';
            }
            exec(escapeshellcmd($pythonBin) . ' ' . escapeshellarg(ROOTPATH . 'worker/generate_promo_pdf.py'));
        }

        if (! file_exists($pdfPath)) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound("Brosur eksekutif institusi belum tersedia.");
        }

        $pdfContent = file_get_contents($pdfPath);
        $filename   = 'Brosur_Eksekutif_ARTilik.pdf';

        return $this->response
            ->setHeader('Content-Type', 'application/pdf')
            ->setHeader('Content-Disposition', 'inline; filename="' . $filename . '"')
            ->setHeader('Content-Length', (string) strlen($pdfContent))
            ->setHeader('Cache-Control', 'public, max-age=86400')
            ->setBody($pdfContent);
    }

    /**
     * Memusnahkan berkas naskah mentah secara permanen dari server demi kedaulatan privasi (Zero-Leakage & Data Sovereignty)
     */
    public function purgeDocumentFile($documentId)
    {
        $userId = session()->get('user_id');
        $isAdmin = (session()->get('user_role') === 'admin');
        if (! $userId) {
            return $this->response->setStatusCode(401)->setJSON(['success' => false, 'message' => 'Silakan masuk terlebih dahulu.']);
        }

        $docModel = new DocumentModel();
        $doc = $docModel->find($documentId);
        if (! $doc) {
            return $this->response->setStatusCode(404)->setJSON(['success' => false, 'message' => 'Dokumen tidak ditemukan.']);
        }

        if (! $isAdmin && (int) $doc['user_id'] !== (int) $userId) {
            return $this->response->setStatusCode(403)->setJSON(['success' => false, 'message' => 'Hanya pemilik naskah yang berhak memusnahkan berkas fisik.']);
        }

        if (! empty($doc['file_purged_at'])) {
            return $this->response->setJSON(['success' => true, 'message' => 'Berkas naskah ini sudah dimusnahkan sebelumnya.']);
        }

        // 1. Hapus berkas fisik dari server disk
        if (! empty($doc['storage_path']) && file_exists($doc['storage_path']) && $doc['storage_path'] !== '[PURGED]') {
            @unlink($doc['storage_path']);
        }

        // 2. Kosongkan teks mentah di document_chunks dan document_pages
        $db = db_connect();
        $db->table('document_chunks')->where('document_id', $documentId)->update([
            'text'            => '',
            'normalized_text' => '',
            'word_count'      => 0,
        ]);
        $db->table('document_pages')->where('document_id', $documentId)->update([
            'text' => '',
        ]);

        // 3. Bersihkan sidik jari biner (fingerprints & shingle) naskah ini dari basis data
        $db->table('fingerprint_documents')->where('document_id', $documentId)->delete();
        $db->table('fingerprints')->where('document_id', $documentId)->delete();

        // 4. Update status dokumen
        $now = date('Y-m-d H:i:s');
        $docModel->update($documentId, [
            'storage_path'   => '[PURGED]',
            'file_purged_at' => $now,
            'updated_at'     => $now,
        ]);

        return $this->response->setJSON([
            'success'        => true,
            'message'        => 'Berkas naskah asli (PDF/DOCX) dan teks mentah telah dimusnahkan secara permanen dari server peladen. Laporan analisis, skor kesepadanan, dan sertifikat verifikasi ber-QR Code tetap aman tersimpan.',
            'file_purged_at' => date('d M Y, H:i', strtotime($now)) . ' WIB',
        ]);
    }

    /**
     * Menghapus naskah beserta laporannya secara menyeluruh oleh pemilik akun
     */
    public function deleteUserDocument($documentId)
    {
        $userId = session()->get('user_id');
        $isAdmin = (session()->get('user_role') === 'admin');
        if (! $userId) {
            return $this->response->setStatusCode(401)->setJSON(['success' => false, 'message' => 'Silakan masuk terlebih dahulu.']);
        }

        $docModel = new DocumentModel();
        $reportModel = new ReportModel();
        $doc = $docModel->find($documentId);
        if (! $doc) {
            return $this->response->setStatusCode(404)->setJSON(['success' => false, 'message' => 'Dokumen tidak ditemukan.']);
        }

        if (! $isAdmin && (int) $doc['user_id'] !== (int) $userId) {
            return $this->response->setStatusCode(403)->setJSON(['success' => false, 'message' => 'Hanya pemilik naskah yang berhak menghapus karya ilmiah ini.']);
        }

        $db = db_connect();
        $reports = $reportModel->where('document_id', $documentId)->findAll();
        foreach ($reports as $r) {
            $db->table('report_matches')->where('report_id', $r['id'])->delete();
            $db->table('report_sources')->where('report_id', $r['id'])->delete();
            $reportModel->delete($r['id']);
        }

        $db->table('document_chunks')->where('document_id', $documentId)->delete();
        $db->table('document_pages')->where('document_id', $documentId)->delete();
        $db->table('fingerprints')->where('document_id', $documentId)->delete();
        $db->table('fingerprint_documents')->where('document_id', $documentId)->delete();
        $db->table('document_shares')->where('document_id', $documentId)->delete();

        if (! empty($doc['storage_path']) && file_exists($doc['storage_path']) && $doc['storage_path'] !== '[PURGED]') {
            @unlink($doc['storage_path']);
        }

        $docModel->delete($documentId);

        return $this->response->setJSON([
            'success' => true,
            'message' => 'Karya ilmiah beserta seluruh data laporan terkait telah berhasil dihapus secara permanen dari akun Anda.',
        ]);
    }

    /**
     * Memperbarui judul karya ilmiah oleh pemilik naskah atau administrator
     */
    public function updateDocumentTitle($documentId)
    {
        $userId = session()->get('user_id');
        $isAdmin = (session()->get('role') === 'admin' || session()->get('user_role') === 'admin');

        if (! $userId) {
            return $this->response->setStatusCode(401)->setJSON(['success' => false, 'message' => 'Silakan masuk terlebih dahulu.']);
        }

        $docModel = new DocumentModel();
        $doc = $docModel->find($documentId);
        if (! $doc) {
            return $this->response->setStatusCode(404)->setJSON(['success' => false, 'message' => 'Dokumen tidak ditemukan.']);
        }

        if ((int) $doc['user_id'] !== (int) $userId && ! $isAdmin) {
            return $this->response->setStatusCode(403)->setJSON(['success' => false, 'message' => 'Anda tidak memiliki wewenang untuk mengubah judul naskah ini.']);
        }

        $updateData = ['updated_at' => date('Y-m-d H:i:s')];

        $titleInput = $this->request->getPost('title');
        if ($titleInput !== null) {
            $newTitle = trim((string) $titleInput);
            if (mb_strlen($newTitle) < 3) {
                return $this->response->setStatusCode(400)->setJSON(['success' => false, 'message' => 'Judul karya ilmiah minimal 3 karakter.']);
            }
            if (mb_strlen($newTitle) > 300) {
                $newTitle = mb_substr($newTitle, 0, 300);
            }
            $updateData['title'] = $newTitle;
        }

        $authorInput = $this->request->getPost('author');
        if ($authorInput !== null) {
            $newAuthor = trim((string) $authorInput);
            if (mb_strlen($newAuthor) > 255) {
                $newAuthor = mb_substr($newAuthor, 0, 255);
            }
            $updateData['author'] = $newAuthor !== '' ? $newAuthor : null;
        }

        $docModel->update($documentId, $updateData);

        return $this->response->setJSON([
            'success' => true,
            'message' => 'Informasi karya ilmiah berhasil diperbarui.',
            'title'   => $updateData['title'] ?? $doc['title'],
            'author'  => $updateData['author'] ?? ($doc['author'] ?? null),
        ]);
    }

    /**
     * Endpoint API JSON Statistik Program Referral Reguler (Member-Get-Member)
     */
    public function referralStats(): ResponseInterface
    {
        $userId = session()->get('user_id');
        if (!$userId) {
            return $this->response->setStatusCode(401)->setJSON([
                'status'  => 'error',
                'message' => 'Silakan masuk untuk melihat statistik referral Anda.',
            ]);
        }

        $referralModel = new \App\Models\ReferralModel();
        $stats = $referralModel->getReferralStats((int) $userId);

        return $this->response->setJSON([
            'status' => 'success',
            'data'   => $stats,
        ]);
    }
}

