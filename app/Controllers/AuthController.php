<?php

namespace App\Controllers;

use App\Libraries\EmailService;
use App\Libraries\SpamShield;
use App\Models\UserModel;
use CodeIgniter\HTTP\ResponseInterface;

class AuthController extends BaseController
{
    /**
     * Mendaftarkan pengguna baru dengan email valid & mengirimkan email verifikasi
     */
    public function register()
    {
        $data = $this->getRequestData();
        $spamShield = new SpamShield();
        $ip = $this->request->getIPAddress();

        // 1. Pemeriksaan Honeypot anti-bot
        if ($spamShield->isHoneypotTriggered($data)) {
            log_message('warning', "Spam bot honeypot terdeteksi pada registrasi dari IP {$ip}");
            return $this->respondError('Permintaan tidak sah terdeteksi sistem keamanan.', 400);
        }

        // 2. Proteksi Rate Limit Pendaftaran (Maksimal 5 pendaftaran per IP per jam)
        $ipRate = $spamShield->checkRateLimit('register', $ip, $ip, 5, 3600);
        if (! $ipRate['allowed']) {
            return $this->respondError($ipRate['message'], 429);
        }

        // 3. Pemeriksaan Token Waktu Formulir (Mencegah bot submit instan)
        $timeToken = $data['security_token'] ?? $data['form_time_token'] ?? null;
        $timingCheck = $spamShield->validateTimeToken($timeToken, 1.5, 7200);
        if (! $timingCheck['valid']) {
            return $this->respondError($timingCheck['reason'], 400);
        }

        $name = trim(strip_tags((string) ($data['name'] ?? '')));
        $email = strtolower(trim((string) ($data['email'] ?? '')));
        $password = (string) ($data['password'] ?? '');
        $institution = trim(strip_tags((string) ($data['institution'] ?? '')));

        if (empty($name) || empty($email) || empty($password)) {
            return $this->respondError('Nama, email, dan kata sandi wajib diisi.', 400);
        }

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->respondError('Alamat email tidak valid. Harap gunakan format email yang benar.', 400);
        }

        // 4. Blokir Penyedia Disposable / Temporary Email (Burner Mail)
        if ($spamShield->isDisposableEmail($email)) {
            return $this->respondError('Alamat email sementara (disposable/burner email) tidak diizinkan. Harap gunakan email institusi atau email resmi Anda.', 400);
        }

        // 5. Validasi Keberadaan Server Penerima Surat (DNS MX/A Record)
        [$dnsValid, $dnsMsg] = $spamShield->validateEmailDomainDns($email);
        if (! $dnsValid) {
            return $this->respondError($dnsMsg, 400);
        }

        if (strlen($password) < 6) {
            return $this->respondError('Kata sandi minimal 6 karakter demi keamanan akun Anda.', 400);
        }

        $userModel = new UserModel();
        $existing = $userModel->where('email', $email)->first();
        if ($existing) {
            if ($existing['status'] === 'pending_verification') {
                return $this->response->setStatusCode(409)->setJSON([
                    'status'             => 'error',
                    'needs_verification' => true,
                    'email'              => $email,
                    'message'            => 'Alamat email ini sudah terdaftar namun belum diverifikasi. Silakan periksa email aktivasi Anda atau ajukan kirim ulang.',
                ]);
            }
            return $this->respondError('Alamat email ini sudah terdaftar. Silakan langsung masuk.', 409);
        }

        $now = date('Y-m-d H:i:s');
        $resetAt = date('Y-m-d H:i:s', strtotime('+7 days'));
        $token = bin2hex(random_bytes(32));
        $tokenExpiresAt = date('Y-m-d H:i:s', strtotime('+24 hours'));

        $userId = $userModel->insert([
            'name'                            => $name,
            'email'                           => $email,
            'password_hash'                   => password_hash($password, PASSWORD_DEFAULT),
            'institution'                     => $institution ?: 'Institusi Akademik',
            'role'                            => 'user',
            'status'                          => 'pending_verification',
            'verification_token'              => $token,
            'verification_token_expires_at'   => $tokenExpiresAt,
            'email_verified_at'               => null,
            'free_submissions_used_this_week' => 0,
            'week_reset_at'                   => $resetAt,
            'bonus_submissions'               => 0,
            'uploaded_corpus_count'           => 0,
            'last_login_at'                   => null,
            'created_at'                      => $now,
            'updated_at'                      => $now,
        ]);

        if (! $userId) {
            return $this->respondError('Gagal mendaftarkan akun. Silakan coba kembali.', 500);
        }

        $user = $userModel->find($userId);

        // Kirimkan email verifikasi aktivasi akun melalui server SMTP
        $emailService = new EmailService();
        $emailSent = $emailService->sendVerificationEmail($user, $token);

        return $this->response->setStatusCode(201)->setJSON([
            'status'             => 'success',
            'needs_verification' => true,
            'email_sent'         => $emailSent,
            'message'            => "Pendaftaran berhasil! Tautan verifikasi aktivasi akun telah dikirimkan ke {$email}. Silakan buka kotak masuk atau spam email Anda untuk mengaktifkan akun sebelum masuk.",
            'user'               => [
                'id'          => $user['id'],
                'name'        => $user['name'],
                'email'       => $user['email'],
                'institution' => $user['institution'],
            ],
            // Catatan: Sesi login sengaja belum dibuka sampai email diverifikasi
        ]);
    }

    /**
     * Memverifikasi tautan aktivasi email dari pengguna
     */
    public function verifyEmail()
    {
        $token = trim((string) ($this->request->getGet('token') ?? ''));
        if (empty($token)) {
            return redirect()->to(base_url('/?auth=login&verified=error&reason=empty_token'))
                ->with('error', 'Token verifikasi tidak ditemukan.');
        }

        $userModel = new UserModel();
        $user = $userModel->where('verification_token', $token)->first();

        if (! $user) {
            return redirect()->to(base_url('/?auth=login&verified=error&reason=invalid_token'))
                ->with('error', 'Tautan verifikasi tidak valid atau akun Anda telah terverifikasi.');
        }

        $now = time();
        $expires = strtotime($user['verification_token_expires_at'] ?? '1970-01-01');
        if ($now > $expires) {
            return redirect()->to(base_url('/?auth=login&verified=error&reason=expired&email=' . urlencode($user['email'])))
                ->with('error', 'Tautan verifikasi telah kedaluwarsa. Silakan ajukan pengiriman ulang tautan aktivasi.');
        }

        // Aktifkan akun
        $currentDate = date('Y-m-d H:i:s');
        $userModel->update($user['id'], [
            'status'                        => 'active',
            'verification_token'            => null,
            'verification_token_expires_at' => null,
            'email_verified_at'             => $currentDate,
            'last_login_at'                 => $currentDate,
            'updated_at'                    => $currentDate,
        ]);

        $activeUser = $userModel->find($user['id']);
        $this->setUserSession($activeUser);

        return redirect()->to(base_url('dashboard?verified=success'))
            ->with('message', 'Alamat email Anda berhasil diverifikasi! Selamat datang di AR Tilik.');
    }

    /**
     * Mengirimkan ulang email verifikasi aktivasi akun
     */
    public function resendVerification()
    {
        $data = $this->getRequestData();
        $spamShield = new SpamShield();
        $ip = $this->request->getIPAddress();

        // 1. Pemeriksaan Honeypot anti-bot
        if ($spamShield->isHoneypotTriggered($data)) {
            return $this->respondError('Permintaan tidak sah.', 400);
        }

        $email = strtolower(trim((string) ($data['email'] ?? '')));

        if (empty($email) || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->respondError('Harap cantumkan alamat email yang valid.', 400);
        }

        // 2. Proteksi Rate Limit Kirim Ulang (Maksimal 3 kali per 15 menit)
        $rateCheck = $spamShield->checkRateLimit('resend_verification', $email, $ip, 3, 900);
        if (! $rateCheck['allowed']) {
            return $this->respondError($rateCheck['message'], 429);
        }

        $userModel = new UserModel();
        $user = $userModel->where('email', $email)->first();

        if (! $user) {
            return $this->response->setJSON([
                'status'  => 'success',
                'message' => "Jika alamat {$email} terdaftar dalam sistem, tautan verifikasi baru telah dikirimkan.",
            ]);
        }

        if ($user['status'] === 'active') {
            return $this->response->setJSON([
                'status'    => 'info',
                'is_active' => true,
                'message'   => 'Akun Anda sudah terverifikasi dan aktif. Silakan langsung masuk.',
            ]);
        }

        // Catat percobaan pengiriman ulang
        $spamShield->recordFailedAttempt('resend_verification', $email, $ip, 3, 900, 900);

        // Buat token baru berlaku 24 jam ke depan
        $token = bin2hex(random_bytes(32));
        $tokenExpiresAt = date('Y-m-d H:i:s', strtotime('+24 hours'));

        $userModel->update($user['id'], [
            'verification_token'            => $token,
            'verification_token_expires_at' => $tokenExpiresAt,
            'updated_at'                    => date('Y-m-d H:i:s'),
        ]);

        $user['verification_token'] = $token;
        $emailService = new EmailService();
        $sent = $emailService->sendVerificationEmail($user, $token);

        return $this->response->setJSON([
            'status'     => 'success',
            'email_sent' => $sent,
            'message'    => "Tautan verifikasi baru berhasil dikirimkan ke {$email}. Silakan periksa kotak masuk atau spam email Anda.",
        ]);
    }

    /**
     * Masuk ke akun pengguna dengan proteksi Anti-Brute-Force
     */
    public function login()
    {
        $data = $this->getRequestData();
        $spamShield = new SpamShield();
        $ip = $this->request->getIPAddress();

        // 1. Pemeriksaan Honeypot anti-bot
        if ($spamShield->isHoneypotTriggered($data)) {
            return $this->respondError('Permintaan tidak sah.', 400);
        }

        $email = strtolower(trim((string) ($data['email'] ?? '')));
        $password = (string) ($data['password'] ?? '');

        if (empty($email) || empty($password)) {
            return $this->respondError('Email dan kata sandi wajib diisi.', 400);
        }

        // 2. Proteksi Rate Limit & Anti-Brute-Force (Maksimal 5 kegagalan per 15 menit)
        $rateCheck = $spamShield->checkRateLimit('login', $email, $ip, 5, 900);
        if (! $rateCheck['allowed']) {
            return $this->respondError($rateCheck['message'], 429);
        }

        $userModel = new UserModel();
        $user = $userModel->where('email', $email)->first();

        if (! $user || ! password_verify($password, $user['password_hash'])) {
            // Catat kegagalan untuk memicu penguncian akun jika serangan brute force
            $spamShield->recordFailedAttempt('login', $email, $ip, 5, 900, 900);
            $recheck = $spamShield->checkRateLimit('login', $email, $ip, 5, 900);
            $remaining = $recheck['attempts_remaining'] ?? 0;

            $msg = 'Email atau kata sandi tidak cocok. Silakan periksa kembali.';
            if ($remaining > 0 && $remaining <= 3) {
                $msg .= " (Sisa {$remaining} kesempatan percobaan sebelum akses dikunci sementara).";
            } elseif ($remaining === 0) {
                $msg = 'Batas percobaan terlampaui. Akses masuk dibatasi sementara selama 15 menit demi keamanan.';
            }

            return $this->respondError($msg, 401);
        }

        if ($user['status'] === 'pending_verification') {
            return $this->response->setStatusCode(403)->setJSON([
                'status'             => 'error',
                'needs_verification' => true,
                'email'              => $email,
                'message'            => 'Alamat email Anda belum diverifikasi. Silakan periksa kotak masuk/spam email aktivasi Anda, atau klik "Kirim Ulang Email Aktivasi".',
            ]);
        }

        if ($user['status'] !== 'active') {
            return $this->respondError('Akun Anda dinonaktifkan. Silakan hubungi administrator.', 403);
        }

        // Berhasil login: bersihkan catatan rate limit untuk email & IP ini
        $spamShield->clearRateLimit('login', $email, $ip);

        // Perbarui masa kuota mingguan jika telah melewati 7 hari
        $user = $userModel->refreshWeeklyQuota($user);

        $now = date('Y-m-d H:i:s');
        $userModel->update($user['id'], [
            'last_login_at' => $now,
            'updated_at'    => $now,
        ]);

        $this->setUserSession($user);

        return $this->response->setJSON([
            'status'     => 'success',
            'message'    => "Selamat datang kembali, {$user['name']}!",
            'user'       => [
                'id'          => $user['id'],
                'name'        => $user['name'],
                'email'       => $user['email'],
                'institution' => $user['institution'],
            ],
            'quota_info' => $userModel->getQuotaInfo($user),
            'redirect'   => base_url('dashboard'),
        ]);
    }

    /**
     * Keluar dari sesi akun
     */
    public function logout()
    {
        session()->destroy();
        return redirect()->to(base_url())->with('message', 'Anda telah berhasil keluar.');
    }

    /**
     * Mengambil status autentikasi dan informasi kuota pengguna saat ini
     */
    public function status(): ResponseInterface
    {
        $userId = session()->get('user_id');
        if (! $userId) {
            return $this->response->setJSON([
                'authenticated' => false,
                'quota_info'    => (new UserModel())->getQuotaInfo(null),
            ]);
        }

        $userModel = new UserModel();
        $user = $userModel->find($userId);

        if (! $user) {
            session()->destroy();
            return $this->response->setJSON([
                'authenticated' => false,
                'quota_info'    => $userModel->getQuotaInfo(null),
            ]);
        }

        return $this->response->setJSON([
            'authenticated' => true,
            'user'          => [
                'id'          => $user['id'],
                'name'        => $user['name'],
                'email'       => $user['email'],
                'institution' => $user['institution'],
            ],
            'quota_info'    => $userModel->getQuotaInfo($user),
        ]);
    }

    /**
     * Mengambil token keamanan waktu formulir (Anti-Spam Time Token)
     */
    public function securityToken(): ResponseInterface
    {
        $shield = new SpamShield();
        return $this->response->setJSON([
            'status' => 'success',
            'token'  => $shield->generateTimeToken(),
        ]);
    }

    private function setUserSession(array $user): void
    {
        session()->set([
            'user_id'          => (int) $user['id'],
            'user_name'        => $user['name'],
            'user_email'       => $user['email'],
            'user_institution' => $user['institution'],
            'user_role'        => $user['role'],
            'is_logged_in'     => true,
        ]);
    }

    private function getRequestData(): array
    {
        $contentType = $this->request->getHeaderLine('Content-Type');
        if (stripos($contentType, 'application/json') !== false) {
            try {
                $json = $this->request->getJSON(true);
                if (is_array($json)) {
                    return $json;
                }
            } catch (\Throwable $e) {
                // Ignore and fall back to post
            }
        }

        return $this->request->getPost() ?: [];
    }

    private function respondError(string $message, int $statusCode): ResponseInterface
    {
        if ($this->request->isAJAX() || $this->request->getHeaderLine('Accept') === 'application/json') {
            return $this->response->setStatusCode($statusCode)->setJSON([
                'status'  => 'error',
                'message' => $message,
            ]);
        }

        return redirect()->back()->withInput()->with('error', $message);
    }
}
