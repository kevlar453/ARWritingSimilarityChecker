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

        $referredByCode = session()->get('referred_by_code') ?? $this->request->getCookie('referred_by_code') ?? ($data['referral_code'] ?? null);
        $referredByCode = $referredByCode ? strtoupper(trim((string)$referredByCode)) : null;
        $bonusSubmissions = $referredByCode ? 1 : 0; // Bonus 1x cek naskah gratis bagi pengguna via referral

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
            'bonus_submissions'               => $bonusSubmissions,
            'referred_by_code'                => $referredByCode,
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
     * Mengajukan permohonan atur ulang kata sandi (Forgot Password)
     */
    public function forgotPassword()
    {
        $data = $this->getRequestData();
        $spamShield = new SpamShield();
        $ip = $this->request->getIPAddress();

        // 1. Pemeriksaan Honeypot anti-bot
        if ($spamShield->isHoneypotTriggered($data)) {
            return $this->respondError('Permintaan tidak sah.', 400);
        }

        // 2. Proteksi Rate Limit Global IP (Maksimal 5 per IP per 15 menit)
        $ipRate = $spamShield->checkRateLimit('forgot_password', $ip, $ip, 5, 900);
        if (! $ipRate['allowed']) {
            return $this->respondError($ipRate['message'], 429);
        }

        $email = strtolower(trim((string) ($data['email'] ?? '')));

        if (empty($email) || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->respondError('Harap cantumkan alamat email yang valid.', 400);
        }

        // 3. Proteksi Rate Limit per Alamat Email (Maksimal 3 per 15 menit)
        $emailRate = $spamShield->checkRateLimit('forgot_password', $email, $ip, 3, 900);
        if (! $emailRate['allowed']) {
            return $this->respondError($emailRate['message'], 429);
        }

        $userModel = new UserModel();
        $user = $userModel->where('email', $email)->first();

        // Catat percobaan untuk mencegah serangan brute force / email bombing
        $spamShield->recordFailedAttempt('forgot_password', $email, $ip, 3, 900, 900);

        if (! $user) {
            // Standar keamanan: jangan bocorkan apakah email terdaftar atau tidak
            return $this->response->setJSON([
                'status'  => 'success',
                'message' => "Jika alamat {$email} terdaftar di AR Tilik, tautan atur ulang kata sandi telah dikirimkan ke kotak masuk Anda.",
            ]);
        }

        if ($user['status'] === 'inactive') {
            return $this->respondError('Akun ini telah dinonaktifkan. Silakan hubungi pengelola sistem.', 403);
        }

        // Buat token atur ulang sandi kriptografis berlaku 1 jam
        $token = bin2hex(random_bytes(32));
        $tokenExpiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));

        $userModel->update($user['id'], [
            'reset_password_token'      => $token,
            'reset_password_expires_at' => $tokenExpiresAt,
            'updated_at'                => date('Y-m-d H:i:s'),
        ]);

        $user['reset_password_token'] = $token;
        $emailService = new EmailService();
        $sent = $emailService->sendPasswordResetEmail($user, $token);

        return $this->response->setJSON([
            'status'     => 'success',
            'email_sent' => $sent,
            'message'    => "Tautan atur ulang kata sandi berhasil dikirimkan ke {$email}. Silakan periksa kotak masuk atau spam email Anda (berlaku 1 jam).",
        ]);
    }

    /**
     * Menampilkan halaman atur ulang kata sandi bagi pengguna yang mengklik tautan email
     */
    public function resetPasswordView()
    {
        $token = trim((string) ($this->request->getGet('token') ?? ''));

        if (empty($token)) {
            return redirect()->to(base_url('/?auth=login&reset=error&reason=empty_token'))
                ->with('error', 'Token atur ulang kata sandi tidak ditemukan.');
        }

        $userModel = new UserModel();
        $user = $userModel->where('reset_password_token', $token)->first();

        if (! $user) {
            return redirect()->to(base_url('/?auth=login&reset=error&reason=invalid_token'))
                ->with('error', 'Tautan atur ulang kata sandi tidak valid atau telah kedaluwarsa.');
        }

        $now = time();
        $expires = strtotime($user['reset_password_expires_at'] ?? '1970-01-01');
        if ($now > $expires) {
            return redirect()->to(base_url('/?auth=login&reset=error&reason=expired&email=' . urlencode($user['email'])))
                ->with('error', 'Tautan atur ulang kata sandi telah kedaluwarsa. Silakan ajukan permohonan baru.');
        }

        $spamShield = new SpamShield();
        $securityToken = $spamShield->generateTimeToken();

        // Samarkan email untuk privasi: m***@domain.com
        $parts = explode('@', $user['email']);
        $local = $parts[0];
        $maskedLocal = substr($local, 0, 1) . str_repeat('*', max(3, strlen($local) - 2)) . (strlen($local) > 1 ? substr($local, -1) : '');
        $maskedEmail = $maskedLocal . '@' . ($parts[1] ?? '');

        return view('auth/reset_password', [
            'token'         => $token,
            'userName'      => $user['name'],
            'userEmail'     => $maskedEmail,
            'securityToken' => $securityToken,
        ]);
    }

    /**
     * Memproses penggantian kata sandi baru (Reset Password Submit)
     */
    public function resetPassword()
    {
        $data = $this->getRequestData();
        $spamShield = new SpamShield();
        $ip = $this->request->getIPAddress();

        // 1. Pemeriksaan Honeypot anti-bot
        if ($spamShield->isHoneypotTriggered($data)) {
            return $this->respondError('Permintaan tidak sah.', 400);
        }

        $token = trim((string) ($data['token'] ?? ''));
        $password = (string) ($data['password'] ?? '');
        $passwordConfirm = (string) ($data['password_confirm'] ?? '');

        if (empty($token)) {
            return $this->respondError('Token atur ulang kata sandi tidak valid atau kosong.', 400);
        }

        if (empty($password) || empty($passwordConfirm)) {
            return $this->respondError('Kata sandi baru dan konfirmasi kata sandi wajib diisi.', 400);
        }

        if (strlen($password) < 8) {
            return $this->respondError('Kata sandi baru minimal 8 karakter demi keamanan akun Anda.', 400);
        }

        if ($password !== $passwordConfirm) {
            return $this->respondError('Konfirmasi kata sandi tidak cocok. Harap periksa kembali.', 400);
        }

        // 2. Proteksi Rate Limit Reset Password (Anti-Brute Force pada token)
        $rateCheck = $spamShield->checkRateLimit('reset_password', $token, $ip, 5, 900);
        if (! $rateCheck['allowed']) {
            return $this->respondError($rateCheck['message'], 429);
        }

        $userModel = new UserModel();
        $user = $userModel->where('reset_password_token', $token)->first();

        if (! $user) {
            $spamShield->recordFailedAttempt('reset_password', $token, $ip, 5, 900, 900);
            return $this->respondError('Tautan atur ulang kata sandi tidak valid atau sudah kedaluwarsa.', 400);
        }

        if (in_array($user['status'], ['banned', 'suspended', 'inactive'], true)) {
            return $this->respondError('Akun Anda dinonaktifkan atau ditangguhkan. Silakan hubungi administrator.', 403);
        }

        $now = time();
        $expires = strtotime($user['reset_password_expires_at'] ?? '1970-01-01');
        if ($now > $expires) {
            return $this->respondError('Tautan atur ulang kata sandi telah kedaluwarsa. Silakan ajukan permintaan baru.', 400);
        }

        // Status baru: aktifkan hanya jika sebelumnya pending_verification, jangan ubah jika sudah active
        $newStatus = ($user['status'] === 'pending_verification') ? 'active' : $user['status'];

        // Perbarui kata sandi baru & bersihkan token
        $currentDate = date('Y-m-d H:i:s');
        $userModel->update($user['id'], [
            'password_hash'             => password_hash($password, PASSWORD_DEFAULT),
            'reset_password_token'      => null,
            'reset_password_expires_at' => null,
            'status'                    => $newStatus,
            'email_verified_at'         => $user['email_verified_at'] ?: $currentDate,
            'updated_at'                => $currentDate,
        ]);

        // Bersihkan seluruh catatan pembatasan login untuk akun ini
        $spamShield->clearRateLimit('login', $user['email'], $ip);
        $spamShield->clearRateLimit('reset_password', $token, $ip);

        if ($this->request->isAJAX() || $this->request->getHeaderLine('Accept') === 'application/json' || stripos($this->request->getHeaderLine('Content-Type'), 'application/json') !== false) {
            return $this->response->setJSON([
                'status'   => 'success',
                'message'  => 'Kata sandi Anda berhasil diperbarui! Silakan masuk dengan kata sandi baru.',
                'redirect' => base_url('/?auth=login&reset=success'),
            ]);
        }

        return redirect()->to(base_url('/?auth=login&reset=success'))
            ->with('message', 'Kata sandi Anda berhasil diperbarui! Silakan masuk menggunakan kata sandi baru.');
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
        session()->regenerate();
        session()->set([
            'user_id'          => (int) $user['id'],
            'user_name'        => $user['name'],
            'user_email'       => $user['email'],
            'user_institution' => $user['institution'],
            'user_role'        => $user['role'],
            'is_logged_in'     => true,
        ]);

        // Klaim secara otomatis karya ilmiah yang dibagikan ke email ini sebelum akun mendaftar/aktif
        try {
            $shareModel = new \App\Models\DocumentShareModel();
            $shareModel->claimPendingSharesForUser((int) $user['id'], (string) $user['email']);
        } catch (\Throwable $e) {
            log_message('error', 'Gagal mengklaim share pending: ' . $e->getMessage());
        }
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
