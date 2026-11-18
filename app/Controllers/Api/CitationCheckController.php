<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;

class CitationCheckController extends BaseController
{
    public const DAILY_LIMIT_PER_IP = 5;
    public const MAX_CITATIONS_PER_CHECK = 10;
    public const MAX_INPUT_CHARS = 15000;

    /**
     * Menghitung hash IP anonim tanpa menyimpan IP mentah ataupun teks sitasi.
     */
    public static function hashIpAddress(string $ip): string
    {
        $cleanIp = trim($ip) !== '' ? trim($ip) : '0.0.0.0';
        $salt = (string) (env('encryption.key') ?: 'artilik-public-citation-checker-salt');
        return hash_hmac('sha256', $cleanIp, $salt);
    }

    /**
     * Membaca sisa kuota harian untuk ditampilkan di halaman /cek-sitasi tanpa menambah pemakaian.
     *
     * @return array{limit:int,used:int,remaining:int}
     */
    public static function readDailyIpQuotaStatus(string $ip): array
    {
        $limit = self::DAILY_LIMIT_PER_IP;
        try {
            $db = db_connect();
            if (! $db->tableExists('public_citation_daily_quota')) {
                return ['limit' => $limit, 'used' => 0, 'remaining' => $limit];
            }
            $row = $db->table('public_citation_daily_quota')
                ->select('checks_used')
                ->where('check_date', date('Y-m-d'))
                ->where('ip_hash', self::hashIpAddress($ip))
                ->get()
                ->getRowArray();
            $used = (int) ($row['checks_used'] ?? 0);
            return [
                'limit'     => $limit,
                'used'      => $used,
                'remaining' => max(0, $limit - $used),
            ];
        } catch (\Throwable $e) {
            return ['limit' => $limit, 'used' => 0, 'remaining' => $limit];
        }
    }

    /**
     * POST /api/v1/citation-check
     * Endpoint publik gratis tanpa API key: maks 10 sitasi/cek, maks 5 cek/hari per IP.
     */
    public function check()
    {
        $rawText = $this->readCitationsInput();
        if (trim($rawText) === '') {
            return $this->response->setStatusCode(422)->setJSON([
                'status'  => 'error',
                'code'    => 422,
                'message' => 'Daftar pustaka masih kosong. Tempel minimal satu baris sitasi atau DOI untuk diperiksa.',
            ]);
        }

        if (mb_strlen($rawText, 'UTF-8') > self::MAX_INPUT_CHARS) {
            return $this->response->setStatusCode(422)->setJSON([
                'status'  => 'error',
                'code'    => 422,
                'message' => 'Teks terlalu panjang (maksimal ' . number_format(self::MAX_INPUT_CHARS) . ' karakter). Pastikan hanya menempel maksimal 10 entri daftar pustaka.',
            ]);
        }

        // Hitung jumlah entri terlebih dahulu agar input >10 entri ditolak tanpa memotong kuota harian
        $parsedCount = $this->parseCitationCount($rawText);
        if ($parsedCount === null) {
            return $this->response->setStatusCode(500)->setJSON([
                'status'  => 'error',
                'code'    => 500,
                'message' => 'Sistem sedang sibuk memproses masukan. Silakan coba sesaat lagi.',
            ]);
        }

        if ($parsedCount === 0) {
            return $this->response->setStatusCode(422)->setJSON([
                'status'  => 'error',
                'code'    => 422,
                'message' => 'Tidak ada entri sitasi yang terdeteksi. Tempel daftar pustaka atau DOI yang valid.',
            ]);
        }

        if ($parsedCount > self::MAX_CITATIONS_PER_CHECK) {
            return $this->response->setStatusCode(422)->setJSON([
                'status'  => 'error',
                'code'    => 422,
                'message' => sprintf(
                    'Maksimal %d sitasi dalam satu kali pemeriksaan. Terdeteksi %d entri — silakan kurangi agar tidak melebihi batas.',
                    self::MAX_CITATIONS_PER_CHECK,
                    $parsedCount
                ),
            ]);
        }

        // Batas laju harian per IP (5 cek/hari per IP) secara atomik di basis data
        $ipHash = self::hashIpAddress((string) $this->request->getIPAddress());
        $quotaClaim = $this->claimDailyQuota($ipHash);
        $this->applyQuotaHeaders($this->response, $quotaClaim);

        if (! $quotaClaim['allowed']) {
            return $this->response->setStatusCode(429)->setJSON([
                'status'      => 'error',
                'code'        => 429,
                'message'     => sprintf(
                    'Batas pemakaian gratis tercapai (maksimal %d pemeriksaan per hari per alamat IP). Silakan kembali besok atau coba AR Tilik lengkap (gratis 3 cek naskah/minggu).',
                    self::DAILY_LIMIT_PER_IP
                ),
                'retry_after' => $quotaClaim['retry_after'],
                'quota'       => [
                    'limit'     => self::DAILY_LIMIT_PER_IP,
                    'used'      => $quotaClaim['used'],
                    'remaining' => 0,
                ],
            ]);
        }

        $wantsStream = $this->wantsNdjsonStream();
        if ($wantsStream) {
            return $this->streamVerification($rawText, $ipHash, $quotaClaim);
        }

        $result = $this->runPythonVerifierJson($rawText);
        if (! is_array($result) || ! empty($result['error_code'])) {
            $this->refundDailyQuota($ipHash);
            return $this->response->setStatusCode(500)->setJSON([
                'status'  => 'error',
                'code'    => 500,
                'message' => $result['message'] ?? 'Terjadi gangguan saat memeriksa sitasi ke Crossref/OpenAlex. Kuota harian Anda tidak terpotong.',
            ]);
        }

        $this->recordAnonymousCountLog($ipHash, $result);

        return $this->response->setStatusCode(200)->setJSON([
            'status' => 'success',
            'quota'  => [
                'limit'     => self::DAILY_LIMIT_PER_IP,
                'used'      => $quotaClaim['used'],
                'remaining' => $quotaClaim['remaining'],
            ],
            'data'   => $result,
        ]);
    }

    private function readCitationsInput(): string
    {
        if (stripos($this->request->getHeaderLine('Content-Type'), 'application/json') !== false) {
            $decoded = json_decode((string) $this->request->getBody(), true);
            if (is_array($decoded) && isset($decoded['citations']) && is_scalar($decoded['citations'])) {
                return (string) $decoded['citations'];
            }
        }
        $postVal = $this->request->getPost('citations');
        return is_scalar($postVal) ? (string) $postVal : '';
    }

    private function wantsNdjsonStream(): bool
    {
        if ($this->request->getPost('stream') === '1') {
            return true;
        }
        $accept = $this->request->getHeaderLine('Accept');
        if (stripos($accept, 'application/x-ndjson') !== false) {
            return true;
        }
        if (stripos($this->request->getHeaderLine('Content-Type'), 'application/json') !== false) {
            $decoded = json_decode((string) $this->request->getBody(), true);
            return is_array($decoded) && ! empty($decoded['stream']);
        }
        return false;
    }

    private function resolvePythonCommand(): array
    {
        $pythonBin = ROOTPATH . '.venv/bin/python';
        if (! file_exists($pythonBin)) {
            $pythonBin = 'python3';
        }
        $scriptPath = ROOTPATH . 'worker/citation_check_cli.py';
        return [$pythonBin, $scriptPath];
    }

    private function parseCitationCount(string $rawText): ?int
    {
        [$pythonBin, $scriptPath] = $this->resolvePythonCommand();
        $cmd = escapeshellarg($pythonBin) . ' ' . escapeshellarg($scriptPath) . ' --parse-only';
        $proc = @proc_open($cmd, [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, ROOTPATH);
        if (! is_resource($proc)) {
            return null;
        }
        fwrite($pipes[0], json_encode(['text' => $rawText], JSON_UNESCAPED_UNICODE));
        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[2]);
        $exit = proc_close($proc);

        if ($exit !== 0 || ! is_string($stdout) || trim($stdout) === '') {
            log_message('error', 'CitationCheck parse-only failed exit=' . $exit . ' err=' . substr((string) $stderr, 0, 200));
            return null;
        }
        $decoded = json_decode(trim($stdout), true);
        return isset($decoded['total_entries']) ? (int) $decoded['total_entries'] : null;
    }

    private function runPythonVerifierJson(string $rawText): ?array
    {
        [$pythonBin, $scriptPath] = $this->resolvePythonCommand();
        $cmd = escapeshellarg($pythonBin) . ' ' . escapeshellarg($scriptPath) . ' --max-items ' . self::MAX_CITATIONS_PER_CHECK;
        $proc = @proc_open($cmd, [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, ROOTPATH);
        if (! is_resource($proc)) {
            return null;
        }
        fwrite($pipes[0], json_encode(['text' => $rawText], JSON_UNESCAPED_UNICODE));
        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[2]);
        $exit = proc_close($proc);

        if ($exit !== 0 || ! is_string($stdout) || trim($stdout) === '') {
            log_message('error', 'CitationCheck verifier failed exit=' . $exit . ' err=' . substr((string) $stderr, 0, 200));
            return null;
        }
        $decoded = json_decode(trim($stdout), true);
        return is_array($decoded) ? $decoded : null;
    }

    private function streamVerification(string $rawText, string $ipHash, array $quotaClaim): ResponseInterface
    {
        [$pythonBin, $scriptPath] = $this->resolvePythonCommand();
        $cmd = escapeshellarg($pythonBin) . ' ' . escapeshellarg($scriptPath) . ' --stream --max-items ' . self::MAX_CITATIONS_PER_CHECK;

        if (session_status() === PHP_SESSION_ACTIVE) {
            @session_write_close();
        }
        // Kirim header streaming seketika lalu baca keluaran NDJSON baris demi baris
        while (ob_get_level() > 0) {
            @ob_end_flush();
        }
        header('Content-Type: application/x-ndjson; charset=utf-8');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Connection: close');
        header('X-Accel-Buffering: no');
        header('X-RateLimit-Limit: ' . self::DAILY_LIMIT_PER_IP);
        header('X-RateLimit-Remaining: ' . (int) $quotaClaim['remaining']);
        header('X-RateLimit-Reset: ' . (int) $quotaClaim['reset_at']);

        echo json_encode([
            'event' => 'quota',
            'quota' => [
                'limit'     => self::DAILY_LIMIT_PER_IP,
                'used'      => $quotaClaim['used'],
                'remaining' => $quotaClaim['remaining'],
            ],
        ], JSON_UNESCAPED_UNICODE) . "\n";
        @flush();

        $proc = @proc_open($cmd, [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, ROOTPATH);
        if (! is_resource($proc)) {
            $this->refundDailyQuota($ipHash);
            echo json_encode([
                'event'   => 'error',
                'message' => 'Gagal memulai layanan pemeriksa sitasi. Kuota harian Anda tidak terpotong.',
            ], JSON_UNESCAPED_UNICODE) . "\n";
            @flush();
            return $this->response->setStatusCode(200);
        }

        fwrite($pipes[0], json_encode(['text' => $rawText], JSON_UNESCAPED_UNICODE));
        fclose($pipes[0]);

        $doneData = null;
        while (! feof($pipes[1])) {
            $line = fgets($pipes[1]);
            if ($line === false) {
                break;
            }
            $trimmed = trim($line);
            if ($trimmed === '') {
                continue;
            }
            $evt = json_decode($trimmed, true);
            if (is_array($evt) && ($evt['event'] ?? '') === 'done' && is_array($evt['data'] ?? null)) {
                $doneData = $evt['data'];
            }
            echo $trimmed . "\n";
            @flush();
        }
        fclose($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[2]);
        $exit = proc_close($proc);

        if ($doneData !== null) {
            $this->recordAnonymousCountLog($ipHash, $doneData);
        } elseif ($exit !== 0) {
            $this->refundDailyQuota($ipHash);
            log_message('error', 'CitationCheck stream failed exit=' . $exit . ' err=' . substr((string) $stderr, 0, 200));
            echo json_encode([
                'event'   => 'error',
                'message' => 'Pemeriksaan terhenti karena gangguan internal. Kuota harian Anda dipulihkan.',
            ], JSON_UNESCAPED_UNICODE) . "\n";
            @flush();
        }

        exit;
    }

    /**
     * Klaim kuota harian 5 cek/hari per IP secara atomik agar aman terhadap burst paralel.
     *
     * @return array{allowed:bool,limit:int,used:int,remaining:int,retry_after:int,reset_at:int}
     */
    private function claimDailyQuota(string $ipHash): array
    {
        $limit = self::DAILY_LIMIT_PER_IP;
        $today = date('Y-m-d');
        $now = date('Y-m-d H:i:s');
        $resetAt = strtotime('tomorrow');
        $retryAfter = max(1, $resetAt - time());

        try {
            $db = db_connect();
            $db->query(
                'INSERT IGNORE INTO public_citation_daily_quota (check_date, ip_hash, checks_used, updated_at)
                 VALUES (?, ?, 0, ?)',
                [$today, $ipHash, $now]
            );
            $db->query(
                'UPDATE public_citation_daily_quota
                 SET checks_used = checks_used + 1, updated_at = ?
                 WHERE check_date = ? AND ip_hash = ? AND checks_used < ?',
                [$now, $today, $ipHash, $limit]
            );
            $affected = $db->affectedRows();
            $row = $db->table('public_citation_daily_quota')
                ->select('checks_used')
                ->where('check_date', $today)
                ->where('ip_hash', $ipHash)
                ->get()
                ->getRowArray();
            $used = (int) ($row['checks_used'] ?? 0);

            return [
                'allowed'     => $affected === 1,
                'limit'       => $limit,
                'used'        => $used,
                'remaining'   => $affected === 1 ? max(0, $limit - $used) : 0,
                'retry_after' => $retryAfter,
                'reset_at'    => $resetAt,
            ];
        } catch (\Throwable $e) {
            log_message('error', 'CitationCheck claimDailyQuota error: ' . $e->getMessage());
            return [
                'allowed'     => true,
                'limit'       => $limit,
                'used'        => 0,
                'remaining'   => $limit,
                'retry_after' => $retryAfter,
                'reset_at'    => $resetAt,
            ];
        }
    }

    private function refundDailyQuota(string $ipHash): void
    {
        try {
            $db = db_connect();
            $db->query(
                'UPDATE public_citation_daily_quota
                 SET checks_used = GREATEST(0, checks_used - 1), updated_at = ?
                 WHERE check_date = ? AND ip_hash = ?',
                [date('Y-m-d H:i:s'), date('Y-m-d'), $ipHash]
            );
        } catch (\Throwable $e) {
            log_message('debug', 'CitationCheck refundDailyQuota skip: ' . $e->getMessage());
        }
    }

    private function applyQuotaHeaders(ResponseInterface $response, array $quota): void
    {
        $response
            ->setHeader('X-RateLimit-Limit', (string) $quota['limit'])
            ->setHeader('X-RateLimit-Remaining', (string) $quota['remaining'])
            ->setHeader('X-RateLimit-Reset', (string) $quota['reset_at']);

        if (empty($quota['allowed'])) {
            $response->setHeader('Retry-After', (string) $quota['retry_after']);
        }
    }

    /**
     * Log statistik harian murni (hanya angka hitungan, TIDAK pernah menyimpan teks sitasi).
     */
    private function recordAnonymousCountLog(string $ipHash, array $result): void
    {
        try {
            $db = db_connect();
            if (! $db->tableExists('public_citation_check_logs')) {
                return;
            }
            $db->table('public_citation_check_logs')->insert([
                'check_date'        => date('Y-m-d'),
                'ip_hash'           => $ipHash,
                'citations_count'   => (int) ($result['total_entries'] ?? 0),
                'verified_count'    => (int) ($result['verified_count'] ?? 0),
                'doubtful_count'    => (int) ($result['doubtful_count'] ?? 0),
                'unverified_count'  => (int) ($result['unverified_count'] ?? 0),
                'unavailable_count' => (int) ($result['unavailable_count'] ?? 0),
                'cache_hits'        => (int) ($result['cache_hits'] ?? 0),
                'created_at'        => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $e) {
            log_message('debug', 'CitationCheck log error: ' . $e->getMessage());
        }
    }
}
