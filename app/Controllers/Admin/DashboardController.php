<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\DocumentModel;
use App\Models\PaymentTransactionModel;
use App\Models\ReportModel;
use App\Models\UserModel;
use App\Models\VisitorModel;
use App\Libraries\QueueService;
use CodeIgniter\HTTP\ResponseInterface;

class DashboardController extends BaseController
{
    /**
     * Halaman Utama Portal Admin AR Tilik
     */
    public function index()
    {
        $db = db_connect();
        $userModel = new UserModel();
        $docModel = new DocumentModel();
        $reportModel = new ReportModel();
        $visitorModel = new VisitorModel();

        $activeTab = $this->request->getGet('tab') ?? 'overview';
        $searchQuery = trim((string) ($this->request->getGet('q') ?? ''));

        // 1. Metrik Pengguna (User Metrics)
        $totalUsers = $userModel->countAllResults();
        $activeUsers = $userModel->where('status', 'active')->countAllResults();
        $pendingUsers = $userModel->where('status', 'pending_verification')->countAllResults();
        $inactiveUsers = $userModel->where('status', 'inactive')->countAllResults();
        $adminUsers = $userModel->where('role', 'admin')->countAllResults();

        // 2. Metrik Dokumen & Antrean Worker (Document Queue Metrics)
        // Hitung submisi secara transparan & jujur: pisahkan antara pengguna terdaftar, anonim/tamu, dan uji benchmark
        $submissionsRegistered   = $docModel->where('document_type', 'submission')->where('user_id IS NOT NULL', null, false)->countAllResults();
        $submissionsAnonymous    = $docModel->where('document_type', 'submission')->where('user_id IS NULL', null, false)->where('benchmark_run_id IS NULL', null, false)->countAllResults();
        $submissionsBenchmark    = $docModel->where('document_type', 'submission')->where('benchmark_run_id IS NOT NULL', null, false)->countAllResults();
        $totalOrganicSubmissions = $submissionsRegistered + $submissionsAnonymous;
        $totalSubmissions        = $submissionsRegistered; // Metrik submisi berintegritas tinggi (prioritas pengguna terdaftar)

        $totalCorpus     = $docModel->where('document_type', 'corpus')->countAllResults();
        $corpusCompleted = $docModel->where('document_type', 'corpus')->where('status', 'completed')->countAllResults();
        $corpusFailed    = $docModel->where('document_type', 'corpus')->where('status', 'failed')->countAllResults();

        $queueUploaded   = $docModel->where('status', 'uploaded')->countAllResults();
        $queueProcessing = $docModel->where('status', 'processing')->countAllResults();
        $queueCompleted  = $docModel->where('status', 'completed')->countAllResults();
        $queueFailed     = $docModel->where('status', 'failed')->countAllResults();

        // Submisi minggu ini (prioritaskan pengguna terdaftar)
        $oneWeekAgo = date('Y-m-d H:i:s', strtotime('-7 days'));
        $submissionsThisWeek = $docModel->where('document_type', 'submission')
                                       ->where('user_id IS NOT NULL', null, false)
                                       ->where('created_at >=', $oneWeekAgo)
                                       ->countAllResults();

        // 3. Metrik Keamanan & Rate Limits
        $now = date('Y-m-d H:i:s');
        $activeLocksCount = $db->table('auth_rate_limits')
                               ->where('locked_until >', $now)
                               ->countAllResults();
        $totalSecurityEvents = $db->table('auth_rate_limits')->countAllResults();

        // 4. Metrik Pengunjung (Visitor Metrics)
        $visitorStats = $visitorModel->getStats();

        // 5. Data List: Pengguna
        $userBuilder = $userModel->orderBy('id', 'DESC');
        if ($searchQuery !== '') {
            $userBuilder->groupStart()
                        ->like('name', $searchQuery)
                        ->orLike('email', $searchQuery)
                        ->orLike('institution', $searchQuery)
                        ->groupEnd();
        }
        $usersList = $userBuilder->findAll(100);

        // 6. Data List: Dokumen Submisi & Laporan (Dukungan Panel Penyaringan & Pencarian Komprehensif)
        $docSearch = trim((string) ($this->request->getGet('doc_q') ?? ($activeTab === 'documents' ? $searchQuery : '')));
        $docType = trim((string) ($this->request->getGet('doc_type') ?? 'all'));
        $docStatus = trim((string) ($this->request->getGet('doc_status') ?? 'all'));
        $docScore = trim((string) ($this->request->getGet('doc_score') ?? 'all'));
        $docDate = trim((string) ($this->request->getGet('doc_date') ?? 'all'));
        $docSort = trim((string) ($this->request->getGet('doc_sort') ?? 'newest'));
        $docLimit = (int) ($this->request->getGet('doc_limit') ?? 50);
        if (! in_array($docLimit, [25, 50, 100, 200], true)) {
            $docLimit = 50;
        }
        $docPage = max(1, (int) ($this->request->getGet('doc_page') ?? 1));

        $isFilterActive = ($docSearch !== '' || $docType !== 'all' || $docStatus !== 'all' || $docScore !== 'all' || $docDate !== 'all' || $docSort !== 'newest');
        $activeFilterCount = 0;
        if ($docSearch !== '') $activeFilterCount++;
        if ($docType !== 'all') $activeFilterCount++;
        if ($docStatus !== 'all') $activeFilterCount++;
        if ($docScore !== 'all') $activeFilterCount++;
        if ($docDate !== 'all') $activeFilterCount++;
        if ($docSort !== 'newest') $activeFilterCount++;

        $docBuilder = $db->table('documents d')
                         ->select('d.*, u.name as user_name, u.email as user_email, lr.latest_report_id, rep.overall_similarity, rep.exact_similarity, rep.cosine_similarity, rep.status as report_status')
                         ->join('users u', 'u.id = d.user_id', 'left')
                         ->join('(SELECT document_id, MAX(id) as latest_report_id FROM reports GROUP BY document_id) lr', 'lr.document_id = d.id', 'left', false)
                         ->join('reports rep', 'rep.id = lr.latest_report_id', 'left');

        // Filter Pencarian Teks
        if ($docSearch !== '') {
            $docBuilder->groupStart()
                       ->like('d.title', $docSearch)
                       ->orLike('d.original_filename', $docSearch)
                       ->orLike('d.faculty', $docSearch)
                       ->orLike('d.department', $docSearch)
                       ->orLike('u.name', $docSearch)
                       ->orLike('u.email', $docSearch)
                       ->groupEnd();
        }

        // Filter Tipe Dokumen
        if (in_array($docType, ['submission', 'corpus'], true)) {
            $docBuilder->where('d.document_type', $docType);
        }

        // Filter Status Worker
        if (in_array($docStatus, ['uploaded', 'processing', 'completed', 'failed'], true)) {
            $docBuilder->where('d.status', $docStatus);
        }

        // Filter Rentang Skor Kemiripan
        if ($docScore === 'clean') {
            $docBuilder->where('rep.overall_similarity <', 15)
                       ->where('rep.overall_similarity IS NOT NULL', null, false);
        } elseif ($docScore === 'moderate') {
            $docBuilder->where('rep.overall_similarity >=', 15)
                       ->where('rep.overall_similarity <', 25);
        } elseif ($docScore === 'high') {
            $docBuilder->where('rep.overall_similarity >=', 25);
        } elseif ($docScore === 'corpus_ref') {
            $docBuilder->where('d.document_type', 'corpus');
        }

        // Filter Periode Waktu Masuk
        if ($docDate === 'today') {
            $docBuilder->where('d.created_at >=', date('Y-m-d 00:00:00'));
        } elseif ($docDate === 'yesterday') {
            $docBuilder->where('d.created_at >=', date('Y-m-d 00:00:00', strtotime('-1 day')))
                       ->where('d.created_at <', date('Y-m-d 00:00:00'));
        } elseif ($docDate === '7days') {
            $docBuilder->where('d.created_at >=', date('Y-m-d H:i:s', strtotime('-7 days')));
        } elseif ($docDate === '30days') {
            $docBuilder->where('d.created_at >=', date('Y-m-d H:i:s', strtotime('-30 days')));
        } elseif ($docDate === 'this_month') {
            $docBuilder->where('d.created_at >=', date('Y-m-01 00:00:00'));
        }

        // Hitung total hasil yang cocok dengan filter untuk paginasi
        $countBuilder = clone $docBuilder;
        $totalFilteredDocs = $countBuilder->countAllResults();

        // Urutan Data
        if ($docSort === 'oldest') {
            $docBuilder->orderBy('d.id', 'ASC');
        } elseif ($docSort === 'score_desc') {
            $docBuilder->orderBy('rep.overall_similarity', 'DESC')
                       ->orderBy('d.id', 'DESC');
        } elseif ($docSort === 'score_asc') {
            $docBuilder->orderBy('(rep.overall_similarity IS NULL)', 'ASC', false)
                       ->orderBy('rep.overall_similarity', 'ASC')
                       ->orderBy('d.id', 'DESC');
        } elseif ($docSort === 'title_asc') {
            $docBuilder->orderBy('d.title', 'ASC');
        } elseif ($docSort === 'title_desc') {
            $docBuilder->orderBy('d.title', 'DESC');
        } else {
            $docBuilder->orderBy('d.id', 'DESC');
        }

        // Kalkulasi Paginasi
        $totalPages = max(1, (int) ceil($totalFilteredDocs / $docLimit));
        if ($docPage > $totalPages) {
            $docPage = $totalPages;
        }
        $offset = ($docPage - 1) * $docLimit;

        $documentsList = $docBuilder->limit($docLimit, $offset)->get()->getResultArray();

        foreach ($documentsList as &$doc) {
            $doc['title'] = $this->cleanMojibake($doc['title'] ?? '');
            if (! empty($doc['latest_report_id'])) {
                $doc['report'] = [
                    'id'                 => $doc['latest_report_id'],
                    'overall_similarity' => (float) ($doc['overall_similarity'] ?? 0),
                    'exact_similarity'   => (float) ($doc['exact_similarity'] ?? 0),
                    'cosine_similarity'  => (float) ($doc['cosine_similarity'] ?? 0),
                    'status'             => $doc['report_status'] ?? 'completed',
                ];
            } else {
                $doc['report'] = null;
            }
        }
        unset($doc);

        $docFilter = [
            'q'              => $docSearch,
            'type'           => $docType,
            'status'         => $docStatus,
            'score'          => $docScore,
            'date'           => $docDate,
            'sort'           => $docSort,
            'limit'          => $docLimit,
            'page'           => $docPage,
            'is_active'      => $isFilterActive,
            'active_count'   => $activeFilterCount,
            'total_filtered' => $totalFilteredDocs,
            'total_pages'    => $totalPages,
            'start_item'     => $totalFilteredDocs > 0 ? $offset + 1 : 0,
            'end_item'       => min($offset + $docLimit, $totalFilteredDocs),
        ];

        // 7. Data List: Korpus Dokumen
        $corpusList = $docModel->where('document_type', 'corpus')
                               ->orderBy('id', 'DESC')
                               ->findAll(100);
        foreach ($corpusList as &$c) {
            $c['title'] = $this->cleanMojibake($c['title'] ?? '');
        }
        unset($c);

        // 8. Data List: Keamanan (Auth Rate Limits)
        $securityLogs = $db->table('auth_rate_limits')
                           ->orderBy('id', 'DESC')
                           ->limit(100)
                           ->get()
                           ->getResultArray();

        // 9. Data List: Pengunjung Harian (30 Hari Terakhir)
        $dailyVisitors = $db->table('landing_visitors')
                            ->select('visit_date, COUNT(DISTINCT visitor_hash) as unique_visitors, SUM(hits) as total_hits')
                            ->groupBy('visit_date')
                            ->orderBy('visit_date', 'DESC')
                            ->limit(30)
                            ->get()
                            ->getResultArray();

        // 10. Data Transaksi & Pembayaran Midtrans
        $paymentModel = new PaymentTransactionModel();

        // Pembersihan otomatis transaksi pending kedaluwarsa (> 24 jam) agar tidak menumpuk di log
        try {
            $paymentModel->autoExpirePendingTransactions(24);
        } catch (\Throwable $e) {
            log_message('error', 'Auto-expire pending payments error in admin overview: ' . $e->getMessage());
        }

        $paymentStats = $paymentModel->getPaymentStats();
        $paymentsList = $paymentModel->getAllTransactions(50);

        // 11. Data Lingkungan Sistem
        $systemInfo = [
            'php_version'         => PHP_VERSION,
            'memory_limit'        => ini_get('memory_limit'),
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size'       => ini_get('post_max_size'),
            'db_driver'           => $db->getPlatform(),
            'server_software'     => $_SERVER['SERVER_SOFTWARE'] ?? 'CLI / Built-in',
            'server_time'         => date('d M Y, H:i:s T'),
        ];

        // 12. Data Pemanen Korpus Nasional (Neliti & GARUDA)
        $harvestLogs = $db->table('harvest_logs')
                          ->orderBy('id', 'DESC')
                          ->limit(20)
                          ->get()
                          ->getResultArray();
        $harvestSchedules = $db->table('harvest_schedules')
                               ->orderBy('id', 'ASC')
                               ->get()
                               ->getResultArray();

        // 13. Data Kunci Integrasi API & OJS
        $apiKeysList = $db->table('api_keys ak')
                          ->select('ak.*, u.name as user_name, u.email as user_email')
                          ->join('users u', 'u.id = ak.user_id', 'left')
                          ->orderBy('ak.id', 'DESC')
                          ->limit(50)
                          ->get()
                          ->getResultArray();

        // 13b. Data Kunci Google AI / Gemini API Keys (Round-Robin Pool)
        $googleKeyModel = new \App\Models\GoogleApiKeyModel();
        $googleKeysList = $googleKeyModel->getAllKeys();
        $activeGoogleKeysCount = count(array_filter($googleKeysList, fn($k) => $k['status'] === 'active'));
        $keys503Count = 0;
        foreach ($googleKeysList as $gk) {
            if ($gk['status'] === 'active' && !empty($gk['last_error_message']) && (strpos($gk['last_error_message'], '503') !== false || stripos($gk['last_error_message'], 'high demand') !== false)) {
                $keys503Count++;
            }
        }
        $googleAiDegraded = ($activeGoogleKeysCount > 0 && ($keys503Count / $activeGoogleKeysCount) >= 0.5);

        // 14. Data Analitik Integritas Institusi (Opsi D)
        $avgSimRow = $db->table('reports')
                        ->select('AVG(overall_similarity) as avg_similarity, COUNT(id) as total_reports')
                        ->where('status', 'completed')
                        ->get()
                        ->getRowArray();
        $avgSimilarity = (float) ($avgSimRow['avg_similarity'] ?? 0);
        $totalReportsCompleted = (int) ($avgSimRow['total_reports'] ?? 0);

        // Distribusi Kategori Integritas
        $cleanCount = $db->table('reports')->where('status', 'completed')->where('overall_similarity <', 15)->countAllResults();
        $moderateCount = $db->table('reports')->where('status', 'completed')->where('overall_similarity >=', 15)->where('overall_similarity <', 25)->countAllResults();
        $substantialCount = $db->table('reports')->where('status', 'completed')->where('overall_similarity >=', 25)->where('overall_similarity <', 40)->countAllResults();
        $criticalCount = $db->table('reports')->where('status', 'completed')->where('overall_similarity >=', 40)->countAllResults();

        // Tren Bulanan (6 Bulan Terakhir)
        $monthlyTrends = $db->table('reports')
                            ->select("DATE_FORMAT(created_at, '%Y-%m') as period, COUNT(id) as total_submissions, AVG(overall_similarity) as avg_sim")
                            ->where('created_at >=', date('Y-m-d', strtotime('-6 months')))
                            ->groupBy('period')
                            ->orderBy('period', 'ASC')
                            ->get()
                            ->getResultArray();

        // Sumber Teratas yang Sering Menjadi Rujukan Kemiripan (Tampilkan Judul & Asal Naskah Valid)
        $topReferencedSources = $db->table('report_sources rs')
                                   ->select('rs.source_document_id, d.title, d.original_filename, d.author, d.source_type, d.corpus_visibility, d.faculty, d.department, COUNT(rs.id) as match_occurrences, AVG(rs.similarity) as avg_similarity')
                                   ->join('documents d', 'd.id = rs.source_document_id', 'inner')
                                   ->where('d.title IS NOT NULL')
                                   ->where('d.title !=', '')
                                   ->groupBy('rs.source_document_id, d.title, d.original_filename, d.author, d.source_type, d.corpus_visibility, d.faculty, d.department')
                                   ->orderBy('match_occurrences', 'DESC')
                                   ->limit(10)
                                   ->get()
                                   ->getResultArray();

        // Metrik Anti-Cheat & Keadilan Akademik (Optimalisasi SQL JSON Extract untuk efisiensi memori)
        $evasionCount = 0;
        $phantomCount = 0;
        $crossLingualCount = 0;

        try {
            $metricRow = $db->query("
                SELECT 
                    COUNT(CASE WHEN JSON_EXTRACT(evidence_data, '$.evasion.is_manipulated') = true OR JSON_EXTRACT(evidence_data, '$.evasion.is_manipulated') = 'true' OR JSON_EXTRACT(evidence_data, '$.evasion.is_manipulated') = 1 THEN 1 END) as evasion_cnt,
                    COUNT(CASE WHEN CAST(JSON_EXTRACT(evidence_data, '$.citation_audit.phantom_citations_count') AS UNSIGNED) > 0 THEN 1 END) as phantom_cnt,
                    COUNT(CASE WHEN JSON_EXTRACT(evidence_data, '$.cross_lingual.is_cross_lingual_match') = true OR JSON_EXTRACT(evidence_data, '$.cross_lingual.is_cross_lingual_match') = 'true' OR JSON_EXTRACT(evidence_data, '$.cross_lingual.is_cross_lingual_match') = 1 THEN 1 END) as cross_lingual_cnt
                FROM (
                    SELECT evidence_data FROM reports WHERE status = 'completed' AND evidence_data IS NOT NULL ORDER BY id DESC LIMIT 100
                ) sub
            ")->getRowArray();

            $evasionCount = (int) ($metricRow['evasion_cnt'] ?? 0);
            $phantomCount = (int) ($metricRow['phantom_cnt'] ?? 0);
            $crossLingualCount = (int) ($metricRow['cross_lingual_cnt'] ?? 0);
        } catch (\Throwable $e) {
            log_message('error', 'DashboardController JSON extraction fallback: ' . $e->getMessage());
        }

        // 14. Data Evaluasi Fitur & Testimoni Publik (Fase 1)
        $voteModel = new \App\Models\FeatureVoteModel();
        $testimonialModel = new \App\Models\TestimonialModel();
        $featureStats = $voteModel->getFeatureStats();
        $recentFeedback = $voteModel->getRecentFeedback(25);
        $testimonialStats = $testimonialModel->getStats();

        // Ambil daftar user_id yang pernah membeli paket apa saja
        $paidUserRows = $db->table('payment_transactions')
                           ->distinct()
                           ->select('user_id')
                           ->whereIn('transaction_status', ['settlement', 'capture'])
                           ->get()
                           ->getResultArray();
        $paidUserMap = array_column($paidUserRows, 'user_id', 'user_id');

        $testimonialsList = $db->table('testimonials t')
                               ->select('t.*, u.name as user_name, u.email as user_email, u.subscription_tier, d.title as document_title')
                               ->join('users u', 'u.id = t.user_id', 'left')
                               ->join('reports r', 'r.id = t.report_id', 'left')
                               ->join('documents d', 'd.id = r.document_id', 'left')
                               ->orderBy('t.id', 'DESC')
                               ->limit(100)
                               ->get()
                               ->getResultArray();

        foreach ($testimonialsList as &$item) {
            $uId = $item['user_id'];
            $tier = $item['subscription_tier'] ?? 'free';
            $item['has_purchased'] = ($uId && (isset($paidUserMap[$uId]) || $tier !== 'free'));
        }
        // 15. Data Program Duta Kampus & Seleksi Asinkron (Tahap 2, 3, 4, 5, 6)
        $ambAppModel = new \App\Models\AmbassadorApplicationModel();
        $ambProfileModel = new \App\Models\AmbassadorProfileModel();
        $ambStats = $ambAppModel->getAdminStats();
        $ambApplications = $ambAppModel->orderBy('id', 'DESC')->findAll(100);
        $ambProfiles = $db->table('ambassador_profiles ap')
                          ->select('ap.*, u.name as user_name, u.email as user_email, u.institution as user_institution')
                          ->join('users u', 'u.id = ap.user_id', 'left')
                          ->orderBy('ap.id', 'DESC')
                          ->limit(50)
                          ->get()
                          ->getResultArray();

        $ambProofs = $db->table('ambassador_activity_proofs ap')
                        ->select('ap.*, prof.referral_code, u.name as ambassador_name, u.email as ambassador_email, u.institution as ambassador_institution')
                        ->join('ambassador_profiles prof', 'prof.id = ap.ambassador_id', 'left')
                        ->join('users u', 'u.id = prof.user_id', 'left')
                        ->orderBy('ap.id', 'DESC')
                        ->limit(100)
                        ->get()
                        ->getResultArray();

        // 16. Data Analitik Prediktif XGBoost + Gemini AI
        $predictiveAnalytics = $this->getPredictiveAnalyticsData();

        return view('admin/index', [
            'activeTab'            => $activeTab,
            'searchQuery'          => $searchQuery,
            'metrics'              => [
                'total_users'               => $totalUsers,
                'active_users'              => $activeUsers,
                'pending_users'             => $pendingUsers,
                'inactive_users'            => $inactiveUsers,
                'admin_users'               => $adminUsers,
                'total_submissions'         => $totalSubmissions,
                'submissions_registered'    => $submissionsRegistered,
                'submissions_anonymous'     => $submissionsAnonymous,
                'submissions_benchmark'     => $submissionsBenchmark,
                'total_organic_submissions' => $totalOrganicSubmissions,
                'submissions_this_week'     => $submissionsThisWeek,
                'total_corpus'              => $totalCorpus,
                'corpus_completed'          => $corpusCompleted,
                'corpus_failed'             => $corpusFailed,
                'queue_uploaded'        => $queueUploaded,
                'queue_processing'      => $queueProcessing,
                'queue_completed'       => $queueCompleted,
                'queue_failed'          => $queueFailed,
                'active_locks_count'    => $activeLocksCount,
                'total_security_events' => $totalSecurityEvents,
                'visitor_stats'         => $visitorStats,
                'payment_stats'         => $paymentStats,
            ],
            'usersList'            => $usersList,
            'documentsList'        => $documentsList,
            'docFilter'            => $docFilter,
            'corpusList'           => $corpusList,
            'securityLogs'         => $securityLogs,
            'dailyVisitors'        => $dailyVisitors,
            'paymentsList'         => $paymentsList,
            'systemInfo'           => $systemInfo,
            'harvestLogs'          => $harvestLogs,
            'harvestSchedules'     => $harvestSchedules,
            'apiKeysList'          => $apiKeysList,
            'googleKeysList'       => $googleKeysList,
            'activeGoogleKeysCount'=> $activeGoogleKeysCount,
            'googleAiDegraded'     => $googleAiDegraded,
            'keys503Count'         => $keys503Count,
            'integrityAnalytics'   => [
                'avg_similarity'          => $avgSimilarity,
                'total_audited'           => $totalReportsCompleted,
                'clean_count'             => $cleanCount,
                'moderate_count'          => $moderateCount,
                'substantial_count'       => $substantialCount,
                'critical_count'          => $criticalCount,
                'monthly_trends'          => $monthlyTrends,
                'top_sources'             => $topReferencedSources,
                'evasion_count'           => $evasionCount,
                'phantom_count'           => $phantomCount,
                'cross_lingual_count'     => $crossLingualCount,
            ],
            'featureStats'         => $featureStats,
            'recentFeedback'       => $recentFeedback,
            'testimonialStats'     => $testimonialStats,
            'testimonialsList'     => $testimonialsList,
            'ambStats'             => $ambStats,
            'ambApplications'      => $ambApplications,
            'ambProfiles'          => $ambProfiles,
            'ambProofs'            => $ambProofs,
            'predictiveAnalytics'  => $predictiveAnalytics,
            'currentAdmin'         => $userModel->find(session()->get('user_id')),
        ]);
    }

    /**
     * Memperbarui data pengguna (Role, Status, Kuota, Verifikasi Email)
     */
    public function updateUser(): ResponseInterface
    {
        $userModel = new UserModel();
        $userId = (int) ($this->request->getPost('user_id') ?? 0);
        $action = (string) ($this->request->getPost('action') ?? '');

        if (! $userId) {
            return $this->response->setStatusCode(400)->setJSON([
                'status'  => 'error',
                'message' => 'ID pengguna tidak valid.',
            ]);
        }

        $user = $userModel->find($userId);
        if (! $user) {
            return $this->response->setStatusCode(404)->setJSON([
                'status'  => 'error',
                'message' => 'Pengguna tidak ditemukan.',
            ]);
        }

        $now = date('Y-m-d H:i:s');

        switch ($action) {
            case 'set_role':
                $role = (string) ($this->request->getPost('role') ?? 'user');
                if (! in_array($role, ['user', 'admin'], true)) {
                    return $this->response->setStatusCode(400)->setJSON(['status' => 'error', 'message' => 'Role tidak valid.']);
                }
                $userModel->update($userId, ['role' => $role, 'updated_at' => $now]);
                $msg = "Peran pengguna {$user['name']} berhasil diubah menjadi {$role}.";
                break;

            case 'set_status':
                $status = (string) ($this->request->getPost('status') ?? 'active');
                if (! in_array($status, ['active', 'pending_verification', 'inactive'], true)) {
                    return $this->response->setStatusCode(400)->setJSON(['status' => 'error', 'message' => 'Status tidak valid.']);
                }
                $updateData = ['status' => $status, 'updated_at' => $now];
                if ($status === 'active' && empty($user['email_verified_at'])) {
                    $updateData['email_verified_at'] = $now;
                }
                $userModel->update($userId, $updateData);
                $msg = "Status akun {$user['name']} berhasil diubah menjadi {$status}.";
                break;

            case 'verify_email':
                $userModel->update($userId, [
                    'status'            => 'active',
                    'email_verified_at' => $now,
                    'updated_at'        => $now,
                ]);
                $msg = "Email {$user['email']} berhasil diverifikasi secara manual. Akun kini aktif.";
                break;

            case 'adjust_quota':
                $bonusAmount = (int) ($this->request->getPost('bonus_submissions') ?? 0);
                $newBonus = max(0, ((int) $user['bonus_submissions']) + $bonusAmount);
                $userModel->update($userId, [
                    'bonus_submissions' => $newBonus,
                    'updated_at'        => $now,
                ]);
                $msg = "Bonus kuota {$user['name']} berhasil diperbarui menjadi {$newBonus} submisi.";
                break;

            case 'reset_weekly_quota':
                $userModel->update($userId, [
                    'free_submissions_used_this_week' => 0,
                    'week_reset_at'                   => date('Y-m-d 00:00:00', strtotime('next monday')),
                    'updated_at'                      => $now,
                ]);
                $msg = "Kuota gratis mingguan {$user['name']} berhasil direset ke 3 submisi (jadwal berikutnya: Senin depan).";
                break;

            case 'activate_trial_kampus':
                $trialDays = 14;
                $bonusGrant = 50;
                $expiresAt = date('Y-m-d H:i:s', strtotime("+{$trialDays} days"));
                $currentBonus = (int) ($user['bonus_submissions'] ?? 0);
                $newBonus = $currentBonus + $bonusGrant;

                $userModel->update($userId, [
                    'subscription_tier'       => 'trial_kampus',
                    'subscription_expires_at' => $expiresAt,
                    'bonus_submissions'       => $newBonus,
                    'status'                  => 'active',
                    'updated_at'              => $now,
                ]);

                // Otomatis terbitkan Kunci API Konektor OJS jika belum ada kunci aktif
                $apiKeyModel = new \App\Models\ApiKeyModel();
                $existingOjsKey = $apiKeyModel->where('user_id', $userId)
                                              ->where('system_type', 'ojs')
                                              ->where('is_active', 1)
                                              ->first();
                $generatedRawKey = null;
                $keyPrefix = null;
                if (! $existingOjsKey) {
                    $institutionLabel = !empty($user['institution']) ? $user['institution'] : $user['name'];
                    $keyName = 'Trial OJS - ' . $institutionLabel;
                    $keyResult = $apiKeyModel->generateKey(
                        $userId,
                        $keyName,
                        'ojs',
                        50,
                        60,
                        $expiresAt
                    );
                    $generatedRawKey = $keyResult['raw_key'] ?? null;
                    $keyPrefix = $keyResult['prefix'] ?? null;
                } else {
                    $keyPrefix = $existingOjsKey['key_prefix'];
                }

                $msg = "Program Uji Coba Resmi Jurnal & Kampus (14 Hari & 50 Kuota Submisi) berhasil diaktifkan untuk {$user['name']}!";
                $extraData = [
                    'ojs_key'    => $generatedRawKey,
                    'ojs_prefix' => $keyPrefix,
                    'expires_at' => $expiresAt,
                    'new_bonus'  => $newBonus,
                ];
                break;

            case 'set_subscription':
                $tier = (string) ($this->request->getPost('tier') ?? 'free');
                $days = (int) ($this->request->getPost('days') ?? 30);
                if (! in_array($tier, ['free', 'skripsi', 'dosen_pro', 'trial_kampus'], true)) {
                    return $this->response->setStatusCode(400)->setJSON(['status' => 'error', 'message' => 'Tier langganan tidak valid.']);
                }
                $expiresAt = ($tier === 'free') ? null : date('Y-m-d H:i:s', strtotime("+{$days} days"));
                $userModel->update($userId, [
                    'subscription_tier'       => $tier,
                    'subscription_expires_at' => $expiresAt,
                    'updated_at'              => $now,
                ]);
                $msg = "Status langganan {$user['name']} berhasil diubah menjadi {$tier}.";
                break;

            default:
                return $this->response->setStatusCode(400)->setJSON([
                    'status'  => 'error',
                    'message' => 'Aksi manajemen pengguna tidak dikenali.',
                ]);
        }

        $responseData = [
            'status'  => 'success',
            'message' => $msg,
            'user'    => $userModel->find($userId),
        ];
        if (isset($extraData)) {
            $responseData['extra'] = $extraData;
        }

        return $this->response->setJSON($responseData);
    }

    /**
     * Membuka kunci (Unblock) alamat IP / Akun dari tabel auth_rate_limits
     */
    public function unblockSecurity(): ResponseInterface
    {
        $db = db_connect();
        $id = (int) ($this->request->getPost('id') ?? 0);
        $identifier = trim((string) ($this->request->getPost('identifier') ?? ''));

        if ($id > 0) {
            $db->table('auth_rate_limits')->where('id', $id)->delete();
        } elseif ($identifier !== '') {
            $db->table('auth_rate_limits')->where('identifier', $identifier)->delete();
        } else {
            return $this->response->setStatusCode(400)->setJSON([
                'status'  => 'error',
                'message' => 'ID atau identitas target unblock tidak ditemukan.',
            ]);
        }

        return $this->response->setJSON([
            'status'  => 'success',
            'message' => 'Pembatasan keamanan berhasil dibuka (Unblocked).',
        ]);
    }

    /**
     * Menghapus seluruh status kunci keamanan (Reset All Rate Limits)
     */
    public function clearAllLocks(): ResponseInterface
    {
        $db = db_connect();
        $db->table('auth_rate_limits')->emptyTable();

        return $this->response->setJSON([
            'status'  => 'success',
            'message' => 'Seluruh rekaman pembatasan keamanan dan penguncian IP berhasil direset.',
        ]);
    }

    /**
     * Memproses ulang analisis dokumen (Re-process Worker)
     */
    public function reprocessDocument(): ResponseInterface
    {
        $docModel = new DocumentModel();
        $documentId = (int) ($this->request->getPost('document_id') ?? 0);

        if (! $documentId) {
            return $this->response->setStatusCode(400)->setJSON([
                'status'  => 'error',
                'message' => 'ID dokumen tidak valid.',
            ]);
        }

        $doc = $docModel->find($documentId);
        if (! $doc) {
            return $this->response->setStatusCode(404)->setJSON([
                'status'  => 'error',
                'message' => 'Dokumen tidak ditemukan.',
            ]);
        }

        $now = date('Y-m-d H:i:s');
        $docModel->update($documentId, [
            'status'          => 'uploaded',
            'indexing_status' => 'pending',
            'updated_at'      => $now,
        ]);

        QueueService::dispatch('process_document', ['document_id' => (int) $documentId]);

        return $this->response->setJSON([
            'status'  => 'success',
            'message' => "Dokumen '{$doc['title']}' telah dijadwalkan ulang untuk dianalisis oleh worker.",
        ]);
    }

    /**
     * Menghapus dokumen dari sistem
     */
    public function deleteDocument(): ResponseInterface
    {
        $docModel = new DocumentModel();
        $reportModel = new ReportModel();
        $documentId = (int) ($this->request->getPost('document_id') ?? 0);

        if (! $documentId) {
            return $this->response->setStatusCode(400)->setJSON([
                'status'  => 'error',
                'message' => 'ID dokumen tidak valid.',
            ]);
        }

        $doc = $docModel->find($documentId);
        if (! $doc) {
            return $this->response->setStatusCode(404)->setJSON([
                'status'  => 'error',
                'message' => 'Dokumen tidak ditemukan.',
            ]);
        }

        // Hapus laporan dan detail kemiripan
        $db = db_connect();
        $reports = $reportModel->select('id')->where('document_id', $documentId)->findAll();
        foreach ($reports as $r) {
            $db->table('report_matches')->where('report_id', $r['id'])->delete();
            $db->table('report_sources')->where('report_id', $r['id'])->delete();
            $reportModel->delete($r['id']);
        }

        // Hapus chunks & pages & sources
        $db->table('report_sources')->where('source_document_id', $documentId)->delete();
        $db->table('document_chunks')->where('document_id', $documentId)->delete();
        $db->table('document_pages')->where('document_id', $documentId)->delete();
        $db->table('fingerprints')->where('document_id', $documentId)->delete();
        $db->table('fingerprint_documents')->where('document_id', $documentId)->delete();
        $db->table('document_shares')->where('document_id', $documentId)->delete();

        // Hapus file fisik jika ada
        if (! empty($doc['storage_path']) && file_exists($doc['storage_path'])) {
            @unlink($doc['storage_path']);
        }

        $docModel->delete($documentId);

        return $this->response->setJSON([
            'status'  => 'success',
            'message' => "Dokumen '{$doc['title']}' berhasil dihapus dari sistem.",
        ]);
    }

    /**
     * Memperbarui visibilitas korpus (corpus, institution, private)
     */
    public function updateCorpusVisibility(): ResponseInterface
    {
        $docModel = new DocumentModel();
        $documentId = (int) ($this->request->getPost('document_id') ?? 0);
        $visibility = (string) ($this->request->getPost('visibility') ?? 'institution');

        if (! in_array($visibility, ['corpus', 'institution', 'private'], true)) {
            return $this->response->setStatusCode(400)->setJSON([
                'status'  => 'error',
                'message' => 'Visibilitas korpus tidak valid.',
            ]);
        }

        $doc = $docModel->find($documentId);
        if (! $doc || $doc['document_type'] !== 'corpus') {
            return $this->response->setStatusCode(404)->setJSON([
                'status'  => 'error',
                'message' => 'Korpus tidak ditemukan.',
            ]);
        }

        $docModel->update($documentId, [
            'corpus_visibility' => $visibility,
            'updated_at'        => date('Y-m-d H:i:s'),
        ]);

        return $this->response->setJSON([
            'status'  => 'success',
            'message' => "Visibilitas korpus '{$doc['title']}' berhasil diubah menjadi {$visibility}.",
        ]);
    }

    /**
     * Ekspor Rekapitulasi Integritas Naskah ke Format CSV (BAN-PT / LAM Ready)
     */
    public function exportIntegrityCsv()
    {
        $db = db_connect();
        $reports = $db->table('reports r')
                      ->select('r.id, r.document_id, d.title, d.faculty, d.department, u.name as author_name, u.institution, r.overall_similarity, r.exact_similarity, r.cosine_similarity, r.predicted_decision, r.verification_code, r.created_at, r.completed_at')
                      ->join('documents d', 'd.id = r.document_id', 'left')
                      ->join('users u', 'u.id = d.user_id', 'left')
                      ->where('r.status', 'completed')
                      ->orderBy('r.id', 'DESC')
                      ->limit(1000)
                      ->get()
                      ->getResultArray();

        $filename = 'Rekapitulasi_Integritas_AR_Tilik_' . date('Ymd_His') . '.csv';

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $output = fopen('php://output', 'w');
        // UTF-8 BOM agar Excel membacanya rapi
        fputs($output, "\xEF\xBB\xBF");

        // Header kolom CSV
        fputcsv($output, [
            'No',
            'ID Laporan',
            'ID Dokumen',
            'Judul Naskah',
            'Penulis / Mahasiswa',
            'Institusi',
            'Fakultas',
            'Program Studi',
            'Skor Kemiripan (%)',
            'Lensa 1 Exact (%)',
            'Lensa 2 Kosinus (%)',
            'Status Keputusan',
            'Nomor Sertifikat Digital',
            'Tanggal Audit',
        ]);

        foreach ($reports as $idx => $r) {
            fputcsv($output, [
                $idx + 1,
                $r['id'],
                $r['document_id'],
                $r['title'] ?? 'Naskah Tidak Berjudul',
                $r['author_name'] ?? 'Penulis Tamu / Eksternal',
                $r['institution'] ?? 'Institusi / Mandiri',
                $r['faculty'] ?? '-',
                $r['department'] ?? '-',
                number_format((float) ($r['overall_similarity'] ?? 0), 2),
                number_format((float) ($r['exact_similarity'] ?? 0), 2),
                number_format((float) ($r['cosine_similarity'] ?? 0), 2),
                strtoupper($r['predicted_decision'] ?? 'NO_MATCH'),
                $r['verification_code'] ?? '-',
                $r['created_at'],
            ]);
        }

        fclose($output);
        exit;
    }

    /**
     * Menyetujui testimoni untuk ditampilkan ke publik
     */
    public function approveTestimonial(int $id): ResponseInterface
    {
        $testimonialModel = new \App\Models\TestimonialModel();
        $testi = $testimonialModel->find($id);
        if (! $testi) {
            return $this->response->setStatusCode(404)->setJSON([
                'status'  => 'error',
                'message' => 'Testimoni tidak ditemukan.',
            ]);
        }

        $testimonialModel->update($id, [
            'is_approved' => 1,
            'updated_at'  => date('Y-m-d H:i:s'),
        ]);

        return $this->response->setJSON([
            'status'  => 'success',
            'message' => 'Testimoni berhasil disetujui dan siap tayang di publik.',
        ]);
    }

    /**
     * Menolak testimoni
     */
    public function rejectTestimonial(int $id): ResponseInterface
    {
        $testimonialModel = new \App\Models\TestimonialModel();
        $testi = $testimonialModel->find($id);
        if (! $testi) {
            return $this->response->setStatusCode(404)->setJSON([
                'status'  => 'error',
                'message' => 'Testimoni tidak ditemukan.',
            ]);
        }

        $testimonialModel->update($id, [
            'is_approved' => -1,
            'is_featured' => 0,
            'updated_at'  => date('Y-m-d H:i:s'),
        ]);

        return $this->response->setJSON([
            'status'  => 'success',
            'message' => 'Testimoni ditandai sebagai ditolak.',
        ]);
    }

    /**
     * Menandai atau mencabut tanda unggulan (Featured) testimoni
     */
    public function toggleFeaturedTestimonial(int $id): ResponseInterface
    {
        $testimonialModel = new \App\Models\TestimonialModel();
        $testi = $testimonialModel->find($id);
        if (! $testi) {
            return $this->response->setStatusCode(404)->setJSON([
                'status'  => 'error',
                'message' => 'Testimoni tidak ditemukan.',
            ]);
        }

        $newVal = empty($testi['is_featured']) ? 1 : 0;
        $updateData = [
            'is_featured' => $newVal,
            'updated_at'  => date('Y-m-d H:i:s'),
        ];
        // Jika belum disetujui, otomatis setujui saat dijadikan featured
        if ($newVal === 1 && (int)$testi['is_approved'] !== 1) {
            $updateData['is_approved'] = 1;
        }

        $testimonialModel->update($id, $updateData);

        return $this->response->setJSON([
            'status'      => 'success',
            'is_featured' => $newVal,
            'message'     => $newVal ? 'Testimoni dijadikan unggulan di landing page!' : 'Status unggulan testimoni dicabut.',
        ]);
    }

    /**
     * Menghapus testimoni
     */
    public function deleteTestimonial(int $id): ResponseInterface
    {
        $testimonialModel = new \App\Models\TestimonialModel();
        $testi = $testimonialModel->find($id);
        if (! $testi) {
            return $this->response->setStatusCode(404)->setJSON([
                'status'  => 'error',
                'message' => 'Testimoni tidak ditemukan.',
            ]);
        }

        $testimonialModel->delete($id);

        return $this->response->setJSON([
            'status'  => 'success',
            'message' => 'Testimoni berhasil dihapus.',
        ]);
    }

    /**
     * API Realtime Polling Metrik Pengunjung (Auto-Reload Angka Tanpa Refresh Halaman)
     */
    public function getVisitorsRealtime(): ResponseInterface
    {
        $db = db_connect();
        $visitorModel = new VisitorModel();
        $stats = $visitorModel->getStats();

        $today = date('Y-m-d');
        $todayRow = $db->table('landing_visitors')
                       ->select('visit_date, COUNT(DISTINCT visitor_hash) as unique_visitors, SUM(hits) as total_hits')
                       ->where('visit_date', $today)
                       ->groupBy('visit_date')
                       ->get()
                       ->getRowArray();

        $unique = (int) ($todayRow['unique_visitors'] ?? $stats['today_unique'] ?? 0);
        $hits = (int) ($todayRow['total_hits'] ?? $stats['today_hits'] ?? 0);
        $avg = $unique > 0 ? round($hits / $unique, 2) : 1.00;

        // Data tren harian untuk visualisasi grafik analitik (30 hari terakhir, kronologis ASC)
        $daily = $db->table('landing_visitors')
                    ->select('visit_date, COUNT(DISTINCT visitor_hash) as unique_visitors, SUM(hits) as total_hits')
                    ->groupBy('visit_date')
                    ->orderBy('visit_date', 'DESC')
                    ->limit(30)
                    ->get()
                    ->getResultArray();

        $daily = array_reverse($daily);

        $dailyFormatted = array_map(function ($row) {
            $u = (int) $row['unique_visitors'];
            $h = (int) $row['total_hits'];
            $a = $u > 0 ? round($h / $u, 2) : 1.00;
            return [
                'visit_date'      => $row['visit_date'],
                'full_date'       => date('d F Y', strtotime($row['visit_date'])),
                'unique_visitors' => $u,
                'total_hits'      => $h,
                'average_hits'    => number_format($a, 2),
            ];
        }, $daily);

        return $this->response->setJSON([
            'status' => 'success',
            'stats'  => [
                'total_unique' => (int) ($stats['total_unique'] ?? 0),
                'total_hits'   => (int) ($stats['total_hits'] ?? 0),
                'today_unique' => (int) ($stats['today_unique'] ?? 0),
                'today_hits'   => (int) ($stats['today_hits'] ?? 0),
            ],
            'today'  => [
                'visit_date'       => $today,
                'formatted_date'   => date('d F Y', strtotime($today)),
                'unique_visitors'  => $unique,
                'total_hits'       => $hits,
                'average_hits'     => number_format($avg, 2),
            ],
            'daily'     => $dailyFormatted,
            'synced_at' => date('H:i:s'),
        ]);
    }

    /**
     * API Realtime Polling Metrik Badge Sidebar Admin (Auto-Reload Nilai Tanpa Refresh Halaman)
     */
    public function getSidebarBadgesRealtime(): ResponseInterface
    {
        $db = db_connect();
        $userModel = new UserModel();
        $docModel = new DocumentModel();
        $paymentModel = new PaymentTransactionModel();
        $ambAppModel = new \App\Models\AmbassadorApplicationModel();
        $visitorModel = new VisitorModel();

        $totalUsers = $userModel->countAllResults();
        $queueCount = $docModel->whereIn('status', ['uploaded', 'processing'])->countAllResults();
        $totalCorpus = $docModel->where('document_type', 'corpus')->countAllResults();
        $totalAudited = $db->table('reports')->where('status', 'completed')->countAllResults();
        $totalApiKeys = $db->table('api_keys')->countAllResults();
        $activeGoogleKeys = $db->table('google_api_keys')->where('status', 'active')->countAllResults();
        $troubledGoogleKeys = $db->table('google_api_keys')->where('status !=', 'active')->countAllResults();
        $paymentStats = $paymentModel->getPaymentStats();
        $testimonialStats = (new \App\Models\TestimonialModel())->getStats();
        $ambStats = $ambAppModel->getAdminStats();
        $activeLocks = $db->table('auth_rate_limits')->where('locked_until >', date('Y-m-d H:i:s'))->countAllResults();
        $visitorStats = $visitorModel->getStats();

        return $this->response->setJSON([
            'status'    => 'success',
            'synced_at' => date('H:i:s'),
            'badges'    => [
                'users'                => (int) $totalUsers,
                'queue'                => (int) $queueCount,
                'corpus'               => (int) $totalCorpus,
                'analytics'            => (int) $totalAudited,
                'api_keys'             => (int) $totalApiKeys,
                'google_keys_active'   => (int) $activeGoogleKeys,
                'google_keys_troubled' => (int) $troubledGoogleKeys,
                'payments_settled'     => (int) ($paymentStats['settled_count'] ?? 0),
                'payments_pending'     => (int) ($paymentStats['pending_count'] ?? 0),
                'testimonials'         => (int) ($testimonialStats['total_reviews'] ?? 0),
                'testimonials_pending' => (int) ($testimonialStats['count_pending'] ?? 0),
                'ambassador_pending'   => (int) ($ambStats['pending_review'] ?? 0),
                'ambassador_active'    => (int) ($ambStats['active_ambassadors'] ?? 0),
                'security_locks'       => (int) $activeLocks,
                'visitors_today'       => (int) ($visitorStats['today_unique'] ?? 0),
            ]
        ]);
    }

    /**
     * Memperbaiki string dengan encoding rusak / mojibake (misal cp1252/latin1 yang terbaca sebagai UTF-8)
     */
    private function cleanMojibake(?string $str): string
    {
        if (empty($str)) {
            return '';
        }
        if (strpos($str, 'Ã') !== false || strpos($str, 'â') !== false) {
            $converted = @iconv('UTF-8', 'Windows-1252//IGNORE', $str);
            if ($converted !== false && mb_check_encoding($converted, 'UTF-8')) {
                return $converted;
            }
        }
        return $str;
    }

    /**
     * Mengambil data analitik prediktif XGBoost + Gemini terkini
     */
    protected function getPredictiveAnalyticsData(bool $forceRefresh = false): array
    {
        $cacheFile = WRITEPATH . 'cache/predictive_forecast_latest.json';
        if (! $forceRefresh && file_exists($cacheFile)) {
            $raw = file_get_contents($cacheFile);
            $decoded = json_decode($raw, true);
            if (is_array($decoded) && ($decoded['status'] ?? '') === 'success') {
                return $decoded;
            }
        }

        $pyPath = '/usr/bin/python3';
        if (file_exists(ROOTPATH . '.venv/bin/python3')) {
            $pyPath = ROOTPATH . '.venv/bin/python3';
        }
        $scriptPath = escapeshellarg(ROOTPATH . 'worker/predictive_analytics.py');
        $cmd = "{$pyPath} {$scriptPath} --action=" . ($forceRefresh ? 'run' : 'get') . " 2>&1";
        $output = shell_exec($cmd);

        if (file_exists($cacheFile)) {
            $raw = file_get_contents($cacheFile);
            $decoded = json_decode($raw, true);
            if (is_array($decoded) && ($decoded['status'] ?? '') === 'success') {
                return $decoded;
            }
        }

        // Jika output CLI langsung berupa JSON valid
        if ($output) {
            $cleanJson = trim($output);
            if (strpos($cleanJson, '{') !== false) {
                $sub = substr($cleanJson, strpos($cleanJson, '{'));
                $d = json_decode($sub, true);
                if (is_array($d) && ($d['status'] ?? '') === 'success') {
                    return $d;
                }
            }
        }

        return [
            'status'  => 'empty',
            'message' => 'Belum ada data proyeksi prediktif.',
        ];
    }

    /**
     * Endpoint API untuk memicu inferensi ulang XGBoost + Gemini secara on-demand
     */
    public function runPredictiveForecast(): ResponseInterface
    {
        $data = $this->getPredictiveAnalyticsData(true);
        return $this->response->setJSON($data);
    }

    /**
     * Endpoint API untuk mengambil data proyeksi prediktif terkini (JSON)
     */
    public function getPredictiveForecastData(): ResponseInterface
    {
        $data = $this->getPredictiveAnalyticsData(false);
        return $this->response->setJSON($data);
    }
}


