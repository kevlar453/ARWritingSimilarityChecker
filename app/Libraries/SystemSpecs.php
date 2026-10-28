<?php

namespace App\Libraries;

/**
 * SystemSpecs — Spesifikasi Resmi & Metrik Sistem Riil AR Tilik
 *
 * Kelas ini menyediakan data dinamis mengenai kapabilitas, status pengujian,
 * dan metrik riil sistem AR Tilik untuk digunakan pada Landing Page, Whitepaper Riset,
 * dan Dokumentasi Resmi. Dengan kelas terpusat ini, setiap peningkatan sistem
 * (pertambahan korpus, pembaruan versi engine, atau hasil benchmark baru)
 * akan langsung terdistribusi secara otomatis ke seluruh materi publikasi.
 */
class SystemSpecs
{
    /**
     * Versi Arsitektur Mesin AR Tilik saat ini
     */
    public const ENGINE_VERSION = '3.4.2';

    /**
     * Nama Edisi Rilis
     */
    public const EDITION_NAME = 'Tiga Lensa Forensics Edition 2026';

    /**
     * Harga langganan publik resmi
     */
    public const SUBSCRIPTION_PRICE = 'Rp 450.000';
    public const SUBSCRIPTION_UNIT = 'bulan';

    /**
     * Mengambil metrik riil dari basis data produksi
     */
    public static function getLiveMetrics(): array
    {
        $db = \Config\Database::connect();

        try {
            $totalCorpus = (int) $db->table('documents')
                ->where('document_type', 'corpus')
                ->where('benchmark_run_id IS NULL', null, false)
                ->countAllResults();

            $submissionsRegistered = (int) $db->table('documents')
                ->where('document_type', 'submission')
                ->where('user_id IS NOT NULL', null, false)
                ->countAllResults();

            $submissionsAnonymous = (int) $db->table('documents')
                ->where('document_type', 'submission')
                ->where('user_id IS NULL', null, false)
                ->where('benchmark_run_id IS NULL', null, false)
                ->countAllResults();

            $totalOrganicSubmissions = $submissionsRegistered + $submissionsAnonymous;
            $totalSubmissions = $submissionsRegistered;

            $totalReports = (int) $db->table('reports')
                ->where('status', 'completed')
                ->countAllResults();
        } catch (\Throwable $e) {
            // Fallback aman jika query database mengalami kendala
            $totalCorpus = 0;
            $totalSubmissions = 0;
            $submissionsRegistered = 0;
            $submissionsAnonymous = 0;
            $totalOrganicSubmissions = 0;
            $totalReports = 0;
        }

        return [
            'total_corpus'              => $totalCorpus,
            'total_submissions'         => $totalSubmissions,
            'submissions_registered'    => $submissionsRegistered,
            'submissions_anonymous'     => $submissionsAnonymous,
            'total_organic_submissions' => $totalOrganicSubmissions,
            'total_reports'             => $totalReports,
            'engine_version'            => self::ENGINE_VERSION,
            'edition_name'              => self::EDITION_NAME,
            'price_label'               => self::SUBSCRIPTION_PRICE . '/' . self::SUBSCRIPTION_UNIT,
            'last_sync_date'            => date('d F Y'),
        ];
    }

    /**
     * Mengambil daftar kapabilitas sistem yang sudah terverifikasi di sistem
     */
    public static function getCapabilities(): array
    {
        return [
            'citation_verifier' => [
                'name'         => 'Verifikasi Sitasi Eksternal (Crossref & OpenAlex)',
                'status'       => 'verified',
                'status_label' => 'Terverifikasi & Aktif',
                'badge_class'  => 'good',
                'description'  => 'Setiap rujukan pada naskah diaudit langsung ke pangkalan data Crossref dan OpenAlex via API untuk mendeteksi sitasi fiktif (halusinasi AI).',
            ],
            'self_purge' => [
                'name'         => 'Kedaulatan Data & Penghapusan Mandiri (Self-Purge)',
                'status'       => 'verified',
                'status_label' => 'Terverifikasi & Aktif',
                'badge_class'  => 'good',
                'description'  => 'Penulis naskah memiliki hak penuh untuk memusnahkan berkas fisik (@unlink) dan menghapus teks ekstraksi dari basis data tanpa residu pada repository publik.',
            ],
            'ojs_integration' => [
                'name'         => 'Integrasi Open Journal Systems (OJS 3.x)',
                'status'       => 'verified',
                'status_label' => 'Tersedia & Terdokumentasi Resmi',
                'badge_class'  => 'good',
                'description'  => 'Konektor PHP OJS 3.x mandiri, script runner eksekusi otomatis, dan dokumen panduan teknis resmi 12 halaman telah tersedia untuk dipasang langsung.',
            ],
            'jit_harvester' => [
                'name'         => 'Pemanen Korpus Cerdas (JIT & Nasional)',
                'status'       => 'verified',
                'status_label' => 'Terverifikasi & Aktif',
                'badge_class'  => 'good',
                'description'  => 'Terhubung langsung dengan repositori ilmiah nasional Neliti dan GARUDA Dikti, serta memperkaya korpus secara otomatis saat pengujian berlangsung.',
            ],
            'cross_lingual' => [
                'name'         => 'Pencocokan Lintas Bahasa (Cross-Lingual EN⇄ID)',
                'status'       => 'verified',
                'status_label' => 'Terverifikasi & Aktif',
                'badge_class'  => 'good',
                'description'  => 'Mendeteksi parafrasa terjemahan mesin antara Bahasa Inggris dan Bahasa Indonesia pada naskah ilmiah.',
            ],
            'evasion_shield' => [
                'name'         => 'Perisai Anti-Manipulasi Tipografi',
                'status'       => 'verified',
                'status_label' => 'Terverifikasi & Aktif',
                'badge_class'  => 'good',
                'description'  => 'Mendeteksi substitusi karakter homoglif Cyrillic/Greek dan penyisipan zero-width space yang dipakai untuk mengelabui detektor konvensional.',
            ],
            'dual_lens' => [
                'name'         => 'Arsitektur Tiga Lensa & Risiko XGBoost',
                'status'       => 'verified',
                'status_label' => 'Terverifikasi & Aktif',
                'badge_class'  => 'good',
                'description'  => 'Menggabungkan 5-gram token sekuensial, orientasi semantik TF-IDF kosinus, pasase beririsan terpanjang, dan sintesis risiko akademik berbasis XGBoost terkalibrasi.',
            ],
        ];
    }
}
