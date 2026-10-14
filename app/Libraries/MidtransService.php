<?php

namespace App\Libraries;

/**
 * MidtransService
 * Integrasi resmi Midtrans Snap API & Webhook Notification untuk AR Tilik.
 */
class MidtransService
{
    protected string $serverKey;
    protected string $clientKey;
    protected bool $isProduction;
    protected string $snapApiUrl;
    protected string $statusApiUrl;
    protected string $notificationWebhookUrl;

    public function __construct()
    {
        $serverKey = env('midtrans.serverKey');
        $clientKey = env('midtrans.clientKey');
        if (empty($serverKey) || empty($clientKey)) {
            log_message('critical', 'Midtrans API keys are not configured in environment (.env).');
        }
        $this->serverKey = (string) ($serverKey ?? '');
        $this->clientKey = (string) ($clientKey ?? '');
        $isProd = env('midtrans.isProduction');
        $this->isProduction = ($isProd === true || $isProd === 'true' || $isProd === 1 || $isProd === '1');

        if ($this->isProduction) {
            $this->snapApiUrl = 'https://app.midtrans.com/snap/v1/transactions';
            $this->statusApiUrl = 'https://api.midtrans.com/v2';
        } else {
            $this->snapApiUrl = 'https://app.sandbox.midtrans.com/snap/v1/transactions';
            $this->statusApiUrl = 'https://api.sandbox.midtrans.com/v2';
        }

        $this->notificationWebhookUrl = 'https://tilik.arsetontong.top/payment/webhook';
    }

    public function getClientKey(): string
    {
        return $this->clientKey;
    }

    public function isProduction(): bool
    {
        return $this->isProduction;
    }

    /**
     * Daftar paket kuota dan langganan resmi AR Tilik
     */
    public static function getPackages(): array
    {
        return [
            'topup_3' => [
                'id'          => 'topup_3',
                'name'        => 'Top-Up 3 Dokumen',
                'category'    => 'topup',
                'amount'      => 10000,
                'submissions' => 3,
                'tier'        => null,
                'days'        => 0,
                'badge'       => 'Paling Terjangkau',
                'badge_color' => 'bg-emerald-500/20 text-emerald-400 border-emerald-500/30',
                'description' => '3 Submisi Cek Plagiarisme, Masa Aktif Selamanya (Tanpa Kedaluwarsa).',
                'features'    => [
                    '3 submisi dokumen tambahan',
                    'Kredit aktif selamanya (tanpa kedaluwarsa)',
                    'Deteksi hybrid n-gram + semantik AI',
                    'Ekspor PDF & tautan verifikasi publik'
                ]
            ],
            'topup_10' => [
                'id'          => 'topup_10',
                'name'        => 'Top-Up 10 Dokumen',
                'category'    => 'topup',
                'amount'      => 25000,
                'submissions' => 10,
                'tier'        => null,
                'days'        => 0,
                'badge'       => 'Paling Populer',
                'badge_color' => 'bg-amber-500/20 text-amber-300 border-amber-500/30',
                'description' => '10 Submisi Cek Plagiarisme, Hemat 16%, Masa Aktif Selamanya.',
                'features'    => [
                    '10 submisi dokumen tambahan (Rp 2.500 / cek)',
                    'Kredit aktif selamanya (tanpa kedaluwarsa)',
                    'Semua algoritma pencocokan tingkat lanjut',
                    'Ekspor PDF & tautan verifikasi publik'
                ]
            ],
            'subs_skripsi' => [
                'id'          => 'subs_skripsi',
                'name'        => 'Paket Pejuang Skripsi',
                'category'    => 'subscription',
                'amount'      => 25000,
                'submissions' => 20,
                'tier'        => 'skripsi',
                'days'        => 30,
                'badge'       => 'Rekomendasi Mahasiswa',
                'badge_color' => 'bg-cyan-500/20 text-cyan-300 border-cyan-500/30',
                'description' => '20 Submisi Dokumen / Bulan khusus persiapan sidang dan revisi bab skripsi.',
                'features'    => [
                    '20 submisi dokumen per bulan',
                    'Prioritas antrean pemrosesan cepat',
                    'Laporan kemiripan interaktif & ekspor PDF',
                    'Masa aktif langganan 30 hari'
                ]
            ],
            'subs_dosen' => [
                'id'          => 'subs_dosen',
                'name'        => 'Paket Dosen & Peneliti Pro',
                'category'    => 'subscription',
                'amount'      => 75000,
                'submissions' => 75,
                'tier'        => 'dosen_pro',
                'days'        => 30,
                'badge'       => 'Kapasitas Besar',
                'badge_color' => 'bg-purple-500/20 text-purple-300 border-purple-500/30',
                'description' => '75 Submisi Dokumen / Bulan untuk cek tugas kelas, tesis, & draf jurnal.',
                'features'    => [
                    '75 submisi dokumen per bulan (Rp 1.000 / cek)',
                    'Alokasi antrean pemrosesan dokumen prioritas',
                    'Dukungan file dokumen besar hingga 30 MB',
                    'Masa aktif langganan 30 hari'
                ]
            ],
        ];
    }

    /**
     * Dapatkan detail paket berdasarkan package_id
     */
    public static function getPackage(string $packageId): ?array
    {
        $packages = self::getPackages();
        return $packages[$packageId] ?? null;
    }

    /**
     * Membuat Snap Token transaksi Midtrans
     */
    public function createSnapTransaction(array $params): array
    {
        $ch = curl_init();
        $payload = json_encode($params);

        $headers = [
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: Basic ' . base64_encode($this->serverKey . ':'),
            'X-Append-Notification: ' . $this->notificationWebhookUrl,
        ];

        curl_setopt_array($ch, [
            CURLOPT_URL            => $this->snapApiUrl,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT        => 20,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            throw new \RuntimeException('Midtrans connection error: ' . $curlError);
        }

        $decoded = json_decode($response, true);
        if ($httpCode !== 201 && $httpCode !== 200) {
            $errMsg = $decoded['error_messages'][0] ?? ($decoded['message'] ?? 'Status ' . $httpCode);
            throw new \RuntimeException('Midtrans API error: ' . $errMsg);
        }

        if (empty($decoded['token'])) {
            throw new \RuntimeException('Snap token missing from Midtrans response.');
        }

        return $decoded;
    }

    /**
     * Verifikasi signature SHA-512 webhook Midtrans
     */
    public function verifyWebhookSignature(string $orderId, string $statusCode, string $grossAmount, string $receivedSignature): bool
    {
        $calculated = hash('sha512', $orderId . $statusCode . $grossAmount . $this->serverKey);
        return hash_equals($calculated, $receivedSignature);
    }

    /**
     * Periksa status transaksi secara real-time ke API Midtrans
     */
    public function getTransactionStatus(string $orderId): ?array
    {
        $ch = curl_init();
        $url = $this->statusApiUrl . '/' . rawurlencode($orderId) . '/status';

        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_HTTPGET        => true,
            CURLOPT_HTTPHEADER     => [
                'Accept: application/json',
                'Authorization: Basic ' . base64_encode($this->serverKey . ':'),
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT        => 10,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response === false || $httpCode >= 400) {
            return null;
        }

        return json_decode($response, true);
    }
}
