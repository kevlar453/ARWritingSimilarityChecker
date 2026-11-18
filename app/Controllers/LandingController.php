<?php

namespace App\Controllers;

use App\Models\UserModel;
use CodeIgniter\HTTP\ResponseInterface;

class LandingController extends BaseController
{
    /**
     * Menyusun data bersama untuk halaman publik utama & subhalaman beranda.
     */
    private function buildSharedPageData(string $activeNav, bool $includeCorpusStats = false, bool $includeTestimonials = false): array
    {
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

        // Catat kunjungan pengunjung unik beserta atribusi UTM dari Alat Cek Sitasi
        $visitorModel = new \App\Models\VisitorModel();
        $ip = $this->request->getIPAddress();
        $userAgent = $this->request->getUserAgent()->getAgentString();
        $visitorStats = $visitorModel->recordVisit($ip, $userAgent);
        \App\Libraries\CitationFunnelTracker::recordArrival($this->request->getGet('utm_source'));

        $spamShield = new \App\Libraries\SpamShield();
        $authSecurityToken = $spamShield->generateTimeToken();

        $data = [
            'activeNav'         => $activeNav,
            'currentUser'       => $currentUser,
            'quotaInfo'         => $quotaInfo,
            'visitorStats'      => $visitorStats,
            'authSecurityToken' => $authSecurityToken,
        ];

        if ($includeCorpusStats) {
            $db = db_connect();
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

            $totalReports = $db->table('reports')
                ->where('status', 'completed')
                ->countAllResults();

            $data['totalCorpus']             = $totalCorpus;
            $data['totalSubmissions']        = $submissionsRegistered;
            $data['submissionsRegistered']   = $submissionsRegistered;
            $data['submissionsAnonymous']    = $submissionsAnonymous;
            $data['totalOrganicSubmissions'] = $submissionsRegistered + $submissionsAnonymous;
            $data['totalReports']            = $totalReports;
        }

        if ($includeTestimonials) {
            $testimonialModel = new \App\Models\TestimonialModel();
            $data['approvedTestimonials'] = $testimonialModel->getApprovedForPublic(12);
            $data['testimonialStats']     = $testimonialModel->getStats();
        }

        return $data;
    }

    /**
     * Menampilkan Beranda ringkas AR Tilik (/)
     */
    public function index(): string
    {
        return view('landing/index', $this->buildSharedPageData('beranda', true, true));
    }

    /**
     * Menampilkan halaman pembuktian teknis, Tiga Lensa, Tiga Lapis & Dual-Reader (/fitur)
     */
    public function features(): string
    {
        return view('landing/fitur', $this->buildSharedPageData('fitur'));
    }

    /**
     * Menampilkan halaman khusus integrasi Open Journal Systems (OJS 3.x) & Webhook (/ojs)
     */
    public function ojs(): string
    {
        return view('landing/ojs', $this->buildSharedPageData('ojs'));
    }

    /**
     * Menampilkan halaman tarif, paket berlangganan, top-up & solusi kampus (/harga)
     */
    public function pricing(): string
    {
        return view('landing/harga', $this->buildSharedPageData('harga'));
    }

    /**
     * Menampilkan halaman filosofi, Gotong Royong Korpus, testimoni lengkap & Duta Kampus (/tentang)
     */
    public function about(): string
    {
        return view('landing/tentang', $this->buildSharedPageData('tentang', false, true));
    }

    /**
     * Menampilkan halaman Tanya Jawab Lengkap (/faq)
     */
    public function faq(): string
    {
        return view('landing/faq', $this->buildSharedPageData('faq'));
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
        $this->response->setHeader('Cache-Control', 'public, max-age=300');

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


