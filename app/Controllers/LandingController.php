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

        $spamShield = new \App\Libraries\SpamShield();
        $authSecurityToken = $spamShield->generateTimeToken();

        // Testimoni Publik Terverifikasi (Fase 1 Social Proof & SEO)
        $testimonialModel = new \App\Models\TestimonialModel();
        $approvedTestimonials = $testimonialModel->getApprovedForPublic(12);
        $testimonialStats = $testimonialModel->getStats();

        return view('landing/index', [
            'currentUser'          => $currentUser,
            'quotaInfo'            => $quotaInfo,
            'totalCorpus'          => $totalCorpus,
            'totalSubmissions'     => $totalSubmissions,
            'totalReports'         => $totalReports,
            'visitorStats'         => $visitorStats,
            'authSecurityToken'    => $authSecurityToken,
            'approvedTestimonials' => $approvedTestimonials,
            'testimonialStats'     => $testimonialStats,
        ]);
    }

    /**
     * Whitepaper Riset & Analisis Komparatif: AR Tilik vs ZeroGPT
     */
    public function researchZerogpt(): string
    {
        return view('landing/research_zerogpt', [
            'title' => 'Perbandingan AR Tilik vs ZeroGPT — Analisis Berbasis Riset & Whitepaper Integritas',
        ]);
    }
}
