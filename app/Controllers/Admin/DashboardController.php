<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\DocumentModel;
use App\Models\PaymentTransactionModel;
use App\Models\ReportModel;
use App\Models\UserModel;
use App\Models\VisitorModel;
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
        $totalSubmissions = $docModel->where('document_type', 'submission')->countAllResults();
        $totalCorpus = $docModel->where('document_type', 'corpus')->countAllResults();

        $queueUploaded = $docModel->where('status', 'uploaded')->countAllResults();
        $queueProcessing = $docModel->where('status', 'processing')->countAllResults();
        $queueCompleted = $docModel->where('status', 'completed')->countAllResults();
        $queueFailed = $docModel->where('status', 'failed')->countAllResults();

        // Submisi minggu ini
        $oneWeekAgo = date('Y-m-d H:i:s', strtotime('-7 days'));
        $submissionsThisWeek = $docModel->where('document_type', 'submission')
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

        // 6. Data List: Dokumen Submisi & Laporan
        $docBuilder = $docModel->select('documents.*, users.name as user_name, users.email as user_email')
                               ->join('users', 'users.id = documents.user_id', 'left')
                               ->orderBy('documents.id', 'DESC');
        if ($searchQuery !== '') {
            $docBuilder->groupStart()
                       ->like('documents.title', $searchQuery)
                       ->orLike('documents.original_filename', $searchQuery)
                       ->orLike('documents.faculty', $searchQuery)
                       ->orLike('documents.department', $searchQuery)
                       ->orLike('users.name', $searchQuery)
                       ->orLike('users.email', $searchQuery)
                       ->groupEnd();
        }
        $documentsList = $docBuilder->findAll(100);

        // Ambil laporan terbaru untuk masing-masing dokumen
        $docIds = array_column($documentsList, 'id');
        $reportsMap = [];
        if (! empty($docIds)) {
            $reports = $reportModel->whereIn('document_id', $docIds)
                                   ->orderBy('id', 'DESC')
                                   ->findAll();
            foreach ($reports as $r) {
                if (! isset($reportsMap[$r['document_id']])) {
                    $reportsMap[$r['document_id']] = $r;
                }
            }
        }
        foreach ($documentsList as &$doc) {
            $doc['report'] = $reportsMap[$doc['id']] ?? null;
        }
        unset($doc);

        // 7. Data List: Korpus Dokumen
        $corpusList = $docModel->where('document_type', 'corpus')
                               ->orderBy('id', 'DESC')
                               ->findAll(100);

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

        // Sumber Teratas yang Sering Menjadi Rujukan Kemiripan
        $topReferencedSources = $db->table('report_sources rs')
                                   ->select('rs.source_document_id, d.title, d.source_type, COUNT(rs.id) as match_occurrences, AVG(rs.similarity) as avg_similarity')
                                   ->join('documents d', 'd.id = rs.source_document_id', 'left')
                                   ->groupBy('rs.source_document_id, d.title, d.source_type')
                                   ->orderBy('match_occurrences', 'DESC')
                                   ->limit(8)
                                   ->get()
                                   ->getResultArray();

        // Metrik Anti-Cheat & Keadilan Akademik
        $reportsSample = $db->table('reports')
                            ->select('id, evidence_data')
                            ->where('status', 'completed')
                            ->orderBy('id', 'DESC')
                            ->limit(100)
                            ->get()
                            ->getResultArray();

        $evasionCount = 0;
        $phantomCount = 0;
        $crossLingualCount = 0;
        foreach ($reportsSample as $rep) {
            if (! empty($rep['evidence_data'])) {
                $evData = json_decode($rep['evidence_data'], true);
                if (! empty($evData['evasion']['is_manipulated'])) {
                    $evasionCount++;
                }
                if (! empty($evData['citation_audit']['phantom_citations_count']) && $evData['citation_audit']['phantom_citations_count'] > 0) {
                    $phantomCount++;
                }
                if (! empty($evData['cross_lingual']['is_cross_lingual_match'])) {
                    $crossLingualCount++;
                }
            }
        }

        return view('admin/index', [
            'activeTab'            => $activeTab,
            'searchQuery'          => $searchQuery,
            'metrics'              => [
                'total_users'           => $totalUsers,
                'active_users'          => $activeUsers,
                'pending_users'         => $pendingUsers,
                'inactive_users'        => $inactiveUsers,
                'admin_users'           => $adminUsers,
                'total_submissions'     => $totalSubmissions,
                'submissions_this_week' => $submissionsThisWeek,
                'total_corpus'          => $totalCorpus,
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
                    'week_reset_at'                   => date('Y-m-d H:i:s', strtotime('+7 days')),
                    'updated_at'                      => $now,
                ]);
                $msg = "Kuota gratis mingguan {$user['name']} berhasil direset ke 3 submisi.";
                break;

            default:
                return $this->response->setStatusCode(400)->setJSON([
                    'status'  => 'error',
                    'message' => 'Aksi manajemen pengguna tidak dikenali.',
                ]);
        }

        return $this->response->setJSON([
            'status'  => 'success',
            'message' => $msg,
            'user'    => $userModel->find($userId),
        ]);
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

        $db = db_connect();
        $db->table('jobs')->insert([
            'type'         => 'process_document',
            'payload'      => json_encode(['document_id' => $documentId]),
            'status'       => 'pending',
            'available_at' => $now,
            'created_at'   => $now,
        ]);

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
        $reports = $reportModel->where('document_id', $documentId)->findAll();
        foreach ($reports as $r) {
            $db->table('report_matches')->where('report_id', $r['id'])->delete();
            $db->table('report_sources')->where('report_id', $r['id'])->delete();
            $reportModel->delete($r['id']);
        }

        // Hapus chunks & pages
        $db->table('document_chunks')->where('document_id', $documentId)->delete();
        $db->table('document_pages')->where('document_id', $documentId)->delete();
        $db->table('fingerprints')->where('document_id', $documentId)->delete();

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
}
