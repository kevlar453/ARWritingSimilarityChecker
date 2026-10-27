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

        // Ambil statistik riil sistem untuk transparansi ilmiah (selaras dengan dashboard produksi)
        $totalCorpus = $db->table('documents')
            ->where('document_type', 'corpus')
            ->where('benchmark_run_id IS NULL', null, false)
            ->countAllResults();

        $submissionsRegistered = $db->table('documents')
            ->where('document_type', 'submission')
            ->where('user_id IS NOT NULL', null, false)
            ->countAllResults();

        $submissionsAnonymous = $db->table('documents')
            ->where('document_type', 'submission')
            ->where('user_id IS NULL', null, false)
            ->where('benchmark_run_id IS NULL', null, false)
            ->countAllResults();

        $totalOrganicSubmissions = $submissionsRegistered + $submissionsAnonymous;
        $totalSubmissions = $submissionsRegistered;

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
            'currentUser'              => $currentUser,
            'quotaInfo'                => $quotaInfo,
            'totalCorpus'              => $totalCorpus,
            'totalSubmissions'         => $totalSubmissions,
            'submissionsRegistered'    => $submissionsRegistered,
            'submissionsAnonymous'     => $submissionsAnonymous,
            'totalOrganicSubmissions'  => $totalOrganicSubmissions,
            'totalReports'             => $totalReports,
            'visitorStats'             => $visitorStats,
            'authSecurityToken'        => $authSecurityToken,
            'approvedTestimonials'     => $approvedTestimonials,
            'testimonialStats'         => $testimonialStats,
        ]);
    }

    /**
     * Whitepaper Riset & Analisis Komparatif: AR Tilik vs ZeroGPT
     */
    public function researchZerogpt(): string
    {
        $metrics = \App\Libraries\SystemSpecs::getLiveMetrics();
        $capabilities = \App\Libraries\SystemSpecs::getCapabilities();

        return view('landing/research_zerogpt', [
            'title'        => 'Perbandingan AR Tilik vs ZeroGPT — Analisis Berbasis Riset & Whitepaper Integritas',
            'metrics'      => $metrics,
            'capabilities' => $capabilities,
        ]);
    }

    /**
     * Whitepaper Riset & Analisis Komparatif: AR Tilik vs Turnitin (& iThenticate)
     */
    public function researchTurnitin(): string
    {
        $metrics = \App\Libraries\SystemSpecs::getLiveMetrics();
        $capabilities = \App\Libraries\SystemSpecs::getCapabilities();

        return view('landing/research_turnitin', [
            'title'        => 'Perbandingan AR Tilik vs Turnitin — Analisis untuk Jurnal & Kampus Indonesia',
            'metrics'      => $metrics,
            'capabilities' => $capabilities,
        ]);
    }

    /**
     * Whitepaper Riset & Publikasi Benchmark Terbuka Naskah Akademik Indonesia
     */
    public function benchmark()
    {
        $this->response->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        $this->response->setHeader('Pragma', 'no-cache');
        $this->response->setHeader('Expires', '0');

        $metrics = \App\Libraries\SystemSpecs::getLiveMetrics();
        $capabilities = \App\Libraries\SystemSpecs::getCapabilities();

        return $this->response->setBody(view('landing/benchmark', [
            'title'        => 'Benchmark Terbuka Naskah Akademik Indonesia — Evaluasi Empiris AR Tilik',
            'metrics'      => $metrics,
            'capabilities' => $capabilities,
        ]));
    }

    /**
     * Unduh Dataset Benchmark Terbuka (JSON) untuk Reproduksi Independen
     */
    public function downloadBenchmarkDataset(string $dataset = 'targeted_80')
    {
        $cleanDataset = preg_replace('/\.json$/i', '', strtolower(trim($dataset)));

        $fileMap = [
            'targeted_80' => ROOTPATH . 'evaluation_dataset_v1_1_targeted_80/cases.json',
            'v1_500'      => ROOTPATH . 'evaluation_dataset_v1/cases.json',
        ];

        $targetPath = $fileMap[$cleanDataset] ?? $fileMap['targeted_80'];
        if (! file_exists($targetPath)) {
            return $this->response->setStatusCode(404)->setBody('Berkas dataset evaluasi tidak ditemukan.');
        }

        $filename = ($cleanDataset === 'v1_500')
            ? 'artilik_indonesia_academic_benchmark_500_cases.json'
            : 'artilik_indonesia_targeted_contextual_80_cases.json';

        $content = file_get_contents($targetPath);

        return $this->response
            ->setHeader('Content-Type', 'application/json; charset=utf-8')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setHeader('Content-Length', (string) strlen($content))
            ->setHeader('Cache-Control', 'public, max-age=86400')
            ->setBody($content);
    }
}


