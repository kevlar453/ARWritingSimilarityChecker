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

        $semanticReport = null;
        $semanticReportPath = ROOTPATH . 'datasets/artilik_semantic_calibration_report.json';
        if (file_exists($semanticReportPath)) {
            $semanticReport = json_decode(file_get_contents($semanticReportPath), true);
        }

        return $this->response->setBody(view('landing/benchmark', [
            'title'           => 'Benchmark Terbuka Naskah Akademik Indonesia — Evaluasi Empiris AR Tilik',
            'metrics'         => $metrics,
            'capabilities'    => $capabilities,
            'semantic_report' => $semanticReport,
        ]));
    }

    /**
     * Unduh Dataset Benchmark Terbuka (JSON) untuk Reproduksi Independen
     */
    public function downloadBenchmarkDataset(string $dataset = 'targeted_80')
    {
        $cleanDataset = preg_replace('/\.json$/i', '', strtolower(trim($dataset)));

        $fileMap = [
            'targeted_80'          => ROOTPATH . 'evaluation_dataset_v1_1_targeted_80/cases.json',
            'v1_500'               => ROOTPATH . 'evaluation_dataset_v1/cases.json',
            'semantic_80'          => ROOTPATH . 'datasets/artilik_semantic_paraphrase_benchmark.json',
            'semantic_paraphrase'  => ROOTPATH . 'datasets/artilik_semantic_paraphrase_benchmark.json',
            'semantic_calibration' => ROOTPATH . 'datasets/artilik_semantic_calibration_report.json',
            'semantic_report'      => ROOTPATH . 'datasets/artilik_semantic_calibration_report.json',
            'cross_lingual_60'     => ROOTPATH . 'datasets/artilik_cross_lingual_benchmark_dataset.json',
            'cross_lingual'        => ROOTPATH . 'datasets/artilik_cross_lingual_benchmark_dataset.json',
            'cross_lingual_report' => ROOTPATH . 'datasets/artilik_cross_lingual_calibration_report.json',
            'latin_12'             => ROOTPATH . 'datasets/artilik_latin_benchmark_dataset.json',
            'latin'                => ROOTPATH . 'datasets/artilik_latin_benchmark_dataset.json',
            'latin_report'         => ROOTPATH . 'datasets/artilik_latin_calibration_report.json',
            'latin_calibration'    => ROOTPATH . 'datasets/artilik_latin_calibration_report.json',
        ];

        $targetPath = $fileMap[$cleanDataset] ?? $fileMap['targeted_80'];
        if (! file_exists($targetPath)) {
            return $this->response->setStatusCode(404)->setBody('Berkas dataset evaluasi tidak ditemukan.');
        }

        $filenameMap = [
            'v1_500'               => 'artilik_indonesia_academic_benchmark_500_cases.json',
            'semantic_80'          => 'artilik_semantic_paraphrase_80_cases.json',
            'semantic_paraphrase'  => 'artilik_semantic_paraphrase_80_cases.json',
            'semantic_calibration' => 'artilik_semantic_calibration_report.json',
            'semantic_report'      => 'artilik_semantic_calibration_report.json',
            'cross_lingual_60'     => 'artilik_cross_lingual_60_cases_benchmark.json',
            'cross_lingual'        => 'artilik_cross_lingual_60_cases_benchmark.json',
            'cross_lingual_report' => 'artilik_cross_lingual_calibration_report.json',
            'latin_12'             => 'artilik_latin_12_cases_benchmark.json',
            'latin'                => 'artilik_latin_12_cases_benchmark.json',
            'latin_report'         => 'artilik_latin_calibration_report.json',
            'latin_calibration'    => 'artilik_latin_calibration_report.json',
        ];

        $filename = $filenameMap[$cleanDataset] ?? 'artilik_indonesia_targeted_contextual_80_cases.json';

        $content = file_get_contents($targetPath);

        return $this->response
            ->setHeader('Content-Type', 'application/json; charset=utf-8')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setHeader('Content-Length', (string) strlen($content))
            ->setHeader('Cache-Control', 'public, max-age=86400')
            ->setBody($content);
    }
}


