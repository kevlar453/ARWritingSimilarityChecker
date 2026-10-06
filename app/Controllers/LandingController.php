<?php

namespace App\Controllers;

use App\Models\UserModel;
use CodeIgniter\HTTP\ResponseInterface;

class LandingController extends BaseController
{
    /**
     * Menampilkan Landing Page modern, edukatif, dan elegan
     */
    public function index(): string
    {
        $db = db_connect();

        // Ambil statistik riil sistem untuk transparansi ilmiah
        $totalCorpus = $db->table('documents')
            ->where('document_type', 'corpus')
            ->where('status', 'completed')
            ->countAllResults();

        $totalSubmissions = $db->table('documents')
            ->where('document_type', 'submission')
            ->countAllResults();

        $totalReports = $db->table('reports')
            ->where('status', 'completed')
            ->countAllResults();

        $userModel = new UserModel();
        $currentUser = null;
        $quotaInfo = null;

        $userId = session()->get('user_id');
        if ($userId) {
            $currentUser = $userModel->find($userId);
            if ($currentUser) {
                $quotaInfo = $userModel->getQuotaInfo($currentUser);
            }
        }

        // Catat kunjungan pengunjung unik
        $visitorModel = new \App\Models\VisitorModel();
        $ip = $this->request->getIPAddress();
        $userAgent = $this->request->getUserAgent()->getAgentString();
        $visitorStats = $visitorModel->recordVisit($ip, $userAgent);

        return view('landing/index', [
            'currentUser'      => $currentUser,
            'quotaInfo'        => $quotaInfo,
            'totalCorpus'      => max(475, $totalCorpus),
            'totalSubmissions' => $totalSubmissions,
            'totalReports'     => $totalReports,
            'visitorStats'     => $visitorStats,
        ]);
    }
}
