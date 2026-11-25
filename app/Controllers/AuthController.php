<?php

namespace App\Controllers;

use App\Libraries\EmailService;
use App\Libraries\GoogleOAuthClient;
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
        $resetAt = date('Y-m-d 00:00:00', strtotime('next monday'));
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
            'verification_emails_sent'        => 1,
            'last_verification_email_sent_at' => $now,
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

        // Catat pendaftaran untuk rate limit IP pada lingkungan produksi
        $env = getenv('CI_ENVIRONMENT') ?: ($_ENV['CI_ENVIRONMENT'] ?? (defined('ENVIRONMENT') ? ENVIRONMENT : 'production'));
        if ($env === 'production' || ! in_array($ip, ['127.0.0.1', '::1'], true)) {
            $spamShield->recordFailedAttempt('register', $ip, $ip, 5, 3600, 3600);
        }

        // Buat kode referral unik personal untuk pengguna baru
        $userModel->ensureReferralCode((int) $userId);
        \App\Libraries\CitationFunnelTracker::recordRegistration($data['utm_source'] ?? $this->request->getGet('utm_source'));

        $user = $userModel->find($userId);

        // Kirimkan email verifikasi aktivasi akun melalui server SMTP
        $emailService = new EmailService();
        $emailSent = $emailService->sendVerificationEmail($user, $token);

        return $this->response->setStatusCode(201)->setJSON([
            'status'             => 'success',
            'needs_verification' => true,
            'email_sent'         => $emailSent,
            'message'            => "Pendaftaran berhasil! Tautan verifikasi aktivasi akun telah dikirimkan ke {$email}. Silakan periksa Kotak Masuk (Inbox) atau folder SPAM / Junk email Anda untuk mengaktifkan akun sebelum masuk.",
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

        // Berikan reward Member-Get-Member (+2 kuota cek naskah) ke pengundang
        try {
            $referralModel = new \App\Models\ReferralModel();
            $referralModel->awardVerificationBonus((int) $user['id']);
        } catch (\Throwable $e) {
            log_message('error', 'Gagal memproses bonus referral verifikasi: ' . $e->getMessage());
        }

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
        $nowStr = date('Y-m-d H:i:s');
        $emailsSent = max(1, (int)($user['verification_emails_sent'] ?? 1)) + 1;

        $userModel->update($user['id'], [
            'verification_token'              => $token,
            'verification_token_expires_at'   => $tokenExpiresAt,
            'verification_emails_sent'        => $emailsSent,
            'last_verification_email_sent_at' => $nowStr,
            'updated_at'                      => $nowStr,
        ]);

        $user['verification_token'] = $token;
        $emailService = new EmailService();
        $sent = $emailService->sendVerificationEmail($user, $token);

        return $this->response->setJSON([
            'status'     => 'success',
            'email_sent' => $sent,
            'message'    => "Tautan verifikasi baru berhasil dikirimkan ke {$email}. Silakan periksa Kotak Masuk (Inbox) atau folder SPAM / Junk email Anda.",
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

        if ($user && empty($user['password_hash'])) {
            return $this->respondError(
                'Akun ini terdaftar melalui Google dan belum memiliki kata sandi lokal. Silakan klik "Masuk dengan Google", atau pilih "Lupa kata sandi?" untuk membuat kata sandi baru.',
                401
            );
        }

        if (! $user || ! password_verify($password, (string) $user['password_hash'])) {
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
                'message'            => 'Alamat email Anda belum diverifikasi. Silakan periksa Kotak Masuk (Inbox) atau folder SPAM / Junk email aktivasi Anda, atau klik "Kirim Ulang Email Aktivasi".',
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
        $secFetchSite = strtolower($this->request->getHeaderLine('Sec-Fetch-Site'));
        $secFetchDest = strtolower($this->request->getHeaderLine('Sec-Fetch-Dest'));
        if ($secFetchSite === 'cross-site' || in_array($secFetchDest, ['image', 'script', 'style', 'iframe', 'embed', 'object'], true)) {
            return $this->response->setStatusCode(403)->setBody('Permintaan lintas situs ditolak.');
        }

        if (session_status() === PHP_SESSION_ACTIVE) {
            session()->destroy();
        } else {
            session()->remove(['user_id', 'user_name', 'user_email', 'user_institution', 'user_role', 'is_logged_in']);
        }
        return redirect()->to(base_url())->with('message', 'Anda telah berhasil keluar.');
    }

    /**
     * Fitur Penghapusan Akun Mandiri oleh Pengguna (Right to Erasure / UU PDP No. 27/2022)
     * Mengharuskan verifikasi kata sandi saat ini sebelum pemusnahan akun dan dokumen.
     */
    public function deleteAccount(): ResponseInterface
    {
        $userId = (int) (session()->get('user_id') ?? 0);
        if (! $userId) {
            return $this->respondError('Sesi login telah berakhir. Silakan masuk kembali.', 401);
        }

        $userModel = new UserModel();
        $user = $userModel->find($userId);
        if (! $user) {
            if (session_status() === PHP_SESSION_ACTIVE) {
            session()->destroy();
        } else {
            session()->remove(['user_id', 'user_name', 'user_email', 'user_institution', 'user_role', 'is_logged_in']);
        }
            return $this->respondError('Pengguna tidak ditemukan.', 404);
        }

        // Jangan izinkan self-delete jika pengguna adalah satu-satunya admin aktif
        if ($user['role'] === 'admin') {
            $otherAdmins = $userModel->where('role', 'admin')
                                     ->where('id !=', $userId)
                                     ->where('status', 'active')
                                     ->countAllResults();
            if ($otherAdmins === 0) {
                return $this->respondError('Akun Administrator utama tidak dapat dihapus demi keberlangsungan sistem.', 400);
            }
        }

        $data = $this->getRequestData();
        $password = (string) ($data['password'] ?? '');

        if (empty($user['password_hash'])) {
            $confirmPhrase = strtoupper(trim((string) ($data['confirm_phrase'] ?? $password)));
            if ($confirmPhrase !== 'HAPUS') {
                return $this->respondError('Untuk akun Google, silakan ketik kata HAPUS untuk mengonfirmasi pemusnahan akun.', 400);
            }
        } else {
            if ($password === '') {
                return $this->respondError('Mohon masukkan kata sandi Anda untuk konfirmasi keamanan.', 400);
            }

            if (! password_verify($password, (string) $user['password_hash'])) {
                return $this->respondError('Kata sandi yang Anda masukkan tidak sesuai.', 401);
            }
        }

        $deleted = $userModel->deleteUserPermanently($userId);
        if (! $deleted) {
            return $this->respondError('Gagal memproses pemusnahan akun karena kendala sistem.', 500);
        }

        if (session_status() === PHP_SESSION_ACTIVE) {
            session()->destroy();
        } else {
            session()->remove(['user_id', 'user_name', 'user_email', 'user_institution', 'user_role', 'is_logged_in']);
        }

        log_message('notice', "[USER SELF DELETE] Pengguna #{$userId} ({$user['email']}) berhasil menghapus akunnya secara mandiri.");

        return $this->response->setJSON([
            'status'   => 'success',
            'message'  => 'Akun Anda beserta seluruh dokumen dan riwayatnya telah berhasil dimusnahkan secara permanen.',
            'redirect' => base_url(),
        ]);
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
            if (session_status() === PHP_SESSION_ACTIVE) {
            session()->destroy();
        } else {
            session()->remove(['user_id', 'user_name', 'user_email', 'user_institution', 'user_role', 'is_logged_in']);
        }
            return $this->response->setJSON([
                'authenticated' => false,
                'quota_info'    => $userModel->getQuotaInfo(null),
            ]);
        }

        return $this->response->setJSON([
            'authenticated' => true,
            'user'          => [
                'id'           => $user['id'],
                'name'         => $user['name'],
                'email'        => $user['email'],
                'institution'  => $user['institution'],
                'has_password' => ! empty($user['password_hash']),
                'has_google'   => ! empty($user['google_id']),
            ],
            'quota_info'    => $userModel->getQuotaInfo($user),
        ]);
    }

    /**
     * Memulai alur autentikasi Google OAuth 2.0 + PKCE S256
     */
    public function googleRedirect()
    {
        $mode = ($this->request->getGet('mode') === 'register') ? 'register' : 'login';
        $oauth = new GoogleOAuthClient();

        if (! $oauth->isConfigured()) {
            return redirect()->to(base_url("/?auth={$mode}&oauth_error=not_configured"))
                ->with('auth_notice', 'Fitur masuk menggunakan Google belum dikonfigurasi oleh pengelola sistem.');
        }

        $redirectTarget = self::normalizeGoogleRedirect((string) ($this->request->getGet('redirect') ?? ''));

        $rawRef = (string) ($this->request->getGet('ref')
            ?? session()->get('referred_by_code')
            ?? $this->request->getCookie('referred_by_code')
            ?? '');
        $rawRef = strtoupper(trim($rawRef));
        $referralCode = preg_match('/^[A-Z0-9_\-]{4,32}$/', $rawRef) ? $rawRef : null;

        $utmSource = trim((string) ($this->request->getGet('utm_source') ?? ''));
        $utmSource = $utmSource !== '' ? mb_substr(preg_replace('/[^a-zA-Z0-9_\-]/', '', $utmSource), 0, 64) : null;

        $state        = GoogleOAuthClient::generateState();
        $codeVerifier = GoogleOAuthClient::generateCodeVerifier();
        $challenge    = GoogleOAuthClient::computeCodeChallenge($codeVerifier);

        session()->set('google_oauth_tx', [
            'intent'        => 'auth',
            'state'         => $state,
            'code_verifier' => $codeVerifier,
            'redirect'      => $redirectTarget,
            'mode'          => $mode,
            'referral_code' => $referralCode,
            'utm_source'    => $utmSource,
            'created_at'    => time(),
        ]);

        return redirect()->to($oauth->buildAuthorizationUrl($state, $challenge));
    }

    /**
     * Memulai alur penyambungan akun Google ke akun pengguna aktif saat ini (lintas email)
     */
    public function googleLinkRedirect()
    {
        $userId = (int) (session()->get('user_id') ?? 0);
        if ($userId <= 0) {
            return redirect()->to(base_url('/?auth=login'))
                ->with('auth_notice', 'Silakan masuk terlebih dahulu untuk menyambungkan akun Google.');
        }

        $userModel = new UserModel();
        $user = $userModel->find($userId);
        if (! $user || ($user['status'] ?? '') !== 'active') {
            return redirect()->to(base_url('dashboard'))
                ->with('error', 'Akun Anda tidak aktif atau tidak ditemukan.');
        }

        if (! empty($user['google_id'])) {
            return redirect()->to(base_url('dashboard'))
                ->with('message', 'Akun Anda sudah terhubung dengan akun Google.');
        }

        $oauth = new GoogleOAuthClient();
        if (! $oauth->isConfigured()) {
            return redirect()->to(base_url('dashboard'))
                ->with('error', 'Fitur masuk menggunakan Google belum dikonfigurasi oleh pengelola sistem.');
        }

        $state        = GoogleOAuthClient::generateState();
        $codeVerifier = GoogleOAuthClient::generateCodeVerifier();
        $challenge    = GoogleOAuthClient::computeCodeChallenge($codeVerifier);

        session()->set('google_oauth_tx', [
            'intent'        => 'link',
            'link_user_id'  => $userId,
            'state'         => $state,
            'code_verifier' => $codeVerifier,
            'redirect'      => base_url('dashboard'),
            'mode'          => 'login',
            'created_at'    => time(),
        ]);

        return redirect()->to($oauth->buildAuthorizationUrl($state, $challenge));
    }

    /**
     * Melepaskan tautan akun Google dari akun pengguna aktif (wajib sudah memiliki kata sandi lokal)
     */
    public function googleUnlink()
    {
        $userId = (int) (session()->get('user_id') ?? 0);
        if ($userId <= 0) {
            return $this->respondError('Sesi tidak valid. Silakan masuk kembali.', 401);
        }

        $userModel = new UserModel();
        $user = $userModel->find($userId);
        if (! $user) {
            return $this->respondError('Pengguna tidak ditemukan.', 404);
        }

        if (empty($user['google_id'])) {
            return redirect()->to(base_url('dashboard'))
                ->with('message', 'Akun ini tidak memiliki tautan Google aktif.');
        }

        if (empty($user['password_hash'])) {
            return redirect()->to(base_url('dashboard'))
                ->with('error', 'Tautan Google tidak dapat dilepaskan karena akun Anda belum memiliki kata sandi lokal. Silakan atur kata sandi lewat menu Lupa Kata Sandi terlebih dahulu.');
        }

        $userModel->update($userId, [
            'google_id'  => null,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return redirect()->to(base_url('dashboard'))
            ->with('message', 'Tautan akun Google berhasil dilepaskan dari akun Anda.');
    }

    /**
     * Menangani callback dari Google OAuth 2.0, memverifikasi PKCE + ID Token, lalu masuk/mendaftar/menautkan akun
     */
    public function googleCallback()
    {
        $tx = session()->get('google_oauth_tx');
        session()->remove('google_oauth_tx');

        $isLinkIntent = (is_array($tx) && ($tx['intent'] ?? 'auth') === 'link');
        $mode         = (is_array($tx) && ($tx['mode'] ?? '') === 'register') ? 'register' : 'login';

        $errorParam = trim((string) ($this->request->getGet('error') ?? ''));
        if ($errorParam !== '') {
            $errCode = ($errorParam === 'access_denied') ? 'cancelled' : 'failed';
            $errMsg  = ($errorParam === 'access_denied')
                ? 'Proses autentikasi dengan akun Google dibatalkan.'
                : 'Autentikasi Google tidak dapat diselesaikan. Silakan coba kembali.';
            return $this->redirectGoogleError($isLinkIntent, $mode, $errCode, $errMsg);
        }

        $code  = trim((string) ($this->request->getGet('code') ?? ''));
        $state = trim((string) ($this->request->getGet('state') ?? ''));

        if (
            ! is_array($tx)
            || $code === ''
            || $state === ''
            || ! hash_equals((string) ($tx['state'] ?? ''), $state)
            || (time() - (int) ($tx['created_at'] ?? 0)) > 600
        ) {
            return $this->redirectGoogleError(
                $isLinkIntent,
                $mode,
                'invalid_state',
                'Sesi autentikasi Google tidak valid atau telah kedaluwarsa. Silakan coba kembali.'
            );
        }

        $oauth = new GoogleOAuthClient();
        if (! $oauth->isConfigured()) {
            return $this->redirectGoogleError(
                $isLinkIntent,
                $mode,
                'not_configured',
                'Fitur masuk menggunakan Google belum dikonfigurasi oleh pengelola sistem.'
            );
        }

        try {
            $tokens  = $oauth->exchangeCode($code, (string) ($tx['code_verifier'] ?? ''));
            $profile = $oauth->verifyIdToken((string) $tokens['id_token']);
        } catch (\Throwable $e) {
            log_message('warning', 'Google OAuth callback gagal: ' . $e->getMessage());
            return $this->redirectGoogleError(
                $isLinkIntent,
                $mode,
                'failed',
                'Gagal memverifikasi akun Google Anda. Pastikan email Google aktif dan silakan coba kembali.'
            );
        }

        if ($isLinkIntent) {
            $sessionUserId = (int) (session()->get('user_id') ?? 0);
            $expectedUserId = (int) ($tx['link_user_id'] ?? 0);
            if ($sessionUserId <= 0 || $sessionUserId !== $expectedUserId) {
                return redirect()->to(base_url('/?auth=login&oauth_error=invalid_state'))
                    ->with('auth_notice', 'Sesi pengguna berubah saat menautkan akun Google. Silakan masuk kembali.');
            }

            $linkRes = $this->linkGoogleToUser($sessionUserId, $profile);
            if (! ($linkRes['ok'] ?? false)) {
                return redirect()->to(base_url('dashboard'))
                    ->with('error', (string) ($linkRes['error'] ?? 'Gagal menautkan akun Google.'));
            }

            return redirect()->to(base_url('dashboard'))
                ->with('message', 'Akun Google (' . $profile['email'] . ') berhasil disambungkan! Kini Anda dapat masuk menggunakan akun Google ini.');
        }

        $result = $this->resolveOrCreateGoogleUser(
            $profile,
            $tx['referral_code'] ?? null,
            $tx['utm_source'] ?? null
        );

        if (! ($result['ok'] ?? false) || empty($result['user'])) {
            return redirect()->to(base_url("/?auth={$mode}&oauth_error=account_error"))
                ->with('auth_notice', (string) ($result['error'] ?? 'Akun Google tidak dapat digunakan untuk masuk.'));
        }

        $userModel = new UserModel();
        $user = $userModel->refreshWeeklyQuota($result['user']);

        $now = date('Y-m-d H:i:s');
        $userModel->update($user['id'], [
            'last_login_at' => $now,
            'updated_at'    => $now,
        ]);
        $user['last_login_at'] = $now;

        $spamShield = new SpamShield();
        $spamShield->clearRateLimit('login', (string) $user['email'], $this->request->getIPAddress());

        $this->setUserSession($user);

        $redirectUrl = (string) ($tx['redirect'] ?? base_url('dashboard'));
        return redirect()->to($redirectUrl);
    }

    /**
     * Menyambungkan akun Google terverifikasi secara manual ke akun pengguna aktif (termasuk lintas email).
     *
     * @return array{ok:bool, user:?array, error:?string}
     */
    public function linkGoogleToUser(int $userId, array $profile): array
    {
        $googleId  = trim((string) ($profile['google_id'] ?? ''));
        $email     = strtolower(trim((string) ($profile['email'] ?? '')));
        $avatarUrl = $profile['avatar_url'] ?? null;

        if ($userId <= 0 || $googleId === '' || $email === '' || empty($profile['email_verified'])) {
            return [
                'ok'    => false,
                'user'  => null,
                'error' => 'Profil Google tidak memiliki email terverifikasi yang valid.',
            ];
        }

        $userModel = new UserModel();
        $targetUser = $userModel->find($userId);

        if (! $targetUser || ($targetUser['status'] ?? '') !== 'active') {
            return [
                'ok'    => false,
                'user'  => null,
                'error' => 'Akun AR Tilik Anda tidak aktif atau tidak ditemukan.',
            ];
        }

        if (! empty($targetUser['google_id'])) {
            if ($targetUser['google_id'] === $googleId) {
                return ['ok' => true, 'user' => $targetUser, 'error' => null];
            }

            return [
                'ok'    => false,
                'user'  => null,
                'error' => 'Akun AR Tilik ini sudah terhubung dengan akun Google lain. Lepaskan tautan sebelumnya terlebih dahulu bila ingin mengganti.',
            ];
        }

        $existingOwner = $userModel->where('google_id', $googleId)->first();
        if ($existingOwner && (int) $existingOwner['id'] !== $userId) {
            return [
                'ok'    => false,
                'user'  => null,
                'error' => 'Akun Google tersebut sudah terhubung dengan akun AR Tilik lain. Gunakan akun Google lain atau lepaskan tautannya terlebih dahulu.',
            ];
        }

        $updates = [
            'google_id'  => $googleId,
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        if ($avatarUrl) {
            $updates['avatar_url'] = $avatarUrl;
        }

        $userModel->update($userId, $updates);

        return ['ok' => true, 'user' => $userModel->find($userId), 'error' => null];
    }

    private function redirectGoogleError(bool $isLinkIntent, string $mode, string $errCode, string $message)
    {
        if ($isLinkIntent) {
            return redirect()->to(base_url('dashboard'))->with('error', $message);
        }

        return redirect()->to(base_url("/?auth={$mode}&oauth_error={$errCode}"))
            ->with('auth_notice', $message);
    }

    /**
     * Menemukan, menautkan, mengaktifkan, atau membuat akun pengguna dari profil Google terverifikasi.
     *
     * @return array{ok:bool, user:?array, error:?string, created:bool}
     */
    public function resolveOrCreateGoogleUser(array $profile, ?string $referralCode = null, ?string $utmSource = null): array
    {
        $googleId  = trim((string) ($profile['google_id'] ?? ''));
        $email     = strtolower(trim((string) ($profile['email'] ?? '')));
        $name      = trim((string) ($profile['name'] ?? ''));
        $avatarUrl = $profile['avatar_url'] ?? null;

        if ($googleId === '' || $email === '' || empty($profile['email_verified'])) {
            return [
                'ok'      => false,
                'user'    => null,
                'error'   => 'Profil Google tidak memiliki email terverifikasi yang valid.',
                'created' => false,
            ];
        }

        $userModel = new UserModel();
        $now = date('Y-m-d H:i:s');

        $user = $userModel->where('google_id', $googleId)->first();
        if ($user) {
            if (! in_array($user['status'], ['active', 'pending_verification'], true)) {
                return [
                    'ok'      => false,
                    'user'    => null,
                    'error'   => 'Akun Anda dinonaktifkan. Silakan hubungi administrator.',
                    'created' => false,
                ];
            }

            $updates = [];
            if ($avatarUrl && ($user['avatar_url'] ?? null) !== $avatarUrl) {
                $updates['avatar_url'] = $avatarUrl;
            }
            if ($user['status'] === 'pending_verification') {
                $updates['status']                        = 'active';
                $updates['verification_token']            = null;
                $updates['verification_token_expires_at'] = null;
                $updates['email_verified_at']             = $user['email_verified_at'] ?: $now;
            }
            if (! empty($updates)) {
                $updates['updated_at'] = $now;
                $userModel->update($user['id'], $updates);
                $user = $userModel->find($user['id']);
            }

            return ['ok' => true, 'user' => $user, 'error' => null, 'created' => false];
        }

        $userByEmail = $userModel->where('email', $email)->first();
        if ($userByEmail) {
            if (! empty($userByEmail['google_id']) && $userByEmail['google_id'] !== $googleId) {
                return [
                    'ok'      => false,
                    'user'    => null,
                    'error'   => 'Alamat email ini sudah terhubung dengan akun Google lain.',
                    'created' => false,
                ];
            }

            if (! in_array($userByEmail['status'], ['active', 'pending_verification'], true)) {
                return [
                    'ok'      => false,
                    'user'    => null,
                    'error'   => 'Akun Anda dinonaktifkan. Silakan hubungi administrator.',
                    'created' => false,
                ];
            }

            $wasPending = ($userByEmail['status'] === 'pending_verification');
            $linkUpdates = [
                'google_id'  => $googleId,
                'updated_at' => $now,
            ];
            if ($avatarUrl) {
                $linkUpdates['avatar_url'] = $avatarUrl;
            }
            if (trim((string) ($userByEmail['name'] ?? '')) === '' && $name !== '') {
                $linkUpdates['name'] = $name;
            }
            if ($wasPending) {
                $linkUpdates['status']                        = 'active';
                $linkUpdates['verification_token']            = null;
                $linkUpdates['verification_token_expires_at'] = null;
                $linkUpdates['email_verified_at']             = $now;
            }

            $userModel->update($userByEmail['id'], $linkUpdates);

            if ($wasPending) {
                try {
                    (new \App\Models\ReferralModel())->awardVerificationBonus((int) $userByEmail['id']);
                } catch (\Throwable $e) {
                    log_message('error', 'Gagal memproses bonus referral Google linking: ' . $e->getMessage());
                }
            }

            return ['ok' => true, 'user' => $userModel->find($userByEmail['id']), 'error' => null, 'created' => false];
        }

        // Pendaftaran pengguna baru melalui akun Google (email sudah terverifikasi oleh Google)
        $cleanRef = $referralCode ? strtoupper(trim($referralCode)) : null;
        if ($cleanRef !== null && ! preg_match('/^[A-Z0-9_\-]{4,32}$/', $cleanRef)) {
            $cleanRef = null;
        }
        $bonusSubmissions = $cleanRef ? 1 : 0;
        $resetAt = date('Y-m-d 00:00:00', strtotime('next monday'));

        $newUserId = $userModel->insert([
            'name'                            => $name !== '' ? mb_substr($name, 0, 150) : 'Pengguna Google',
            'email'                           => $email,
            'google_id'                       => $googleId,
            'avatar_url'                      => $avatarUrl,
            'password_hash'                   => null,
            'institution'                     => 'Institusi Akademik',
            'role'                            => 'user',
            'status'                          => 'active',
            'verification_token'              => null,
            'verification_token_expires_at'   => null,
            'verification_emails_sent'        => 0,
            'last_verification_email_sent_at' => null,
            'email_verified_at'               => $now,
            'free_submissions_used_this_week' => 0,
            'week_reset_at'                   => $resetAt,
            'bonus_submissions'               => $bonusSubmissions,
            'referred_by_code'                => $cleanRef,
            'uploaded_corpus_count'           => 0,
            'last_login_at'                   => $now,
            'created_at'                      => $now,
            'updated_at'                      => $now,
        ]);

        if (! $newUserId) {
            return [
                'ok'      => false,
                'user'    => null,
                'error'   => 'Gagal membuat akun baru dari profil Google. Silakan coba lagi.',
                'created' => false,
            ];
        }

        $userModel->ensureReferralCode((int) $newUserId);

        if ($cleanRef !== null) {
            try {
                (new \App\Models\ReferralModel())->awardVerificationBonus((int) $newUserId);
            } catch (\Throwable $e) {
                log_message('error', 'Gagal memproses bonus referral Google signup: ' . $e->getMessage());
            }
        }

        try {
            \App\Libraries\CitationFunnelTracker::recordRegistration($utmSource);
        } catch (\Throwable $e) {
            log_message('error', 'Gagal mencatat funnel registrasi Google: ' . $e->getMessage());
        }

        return ['ok' => true, 'user' => $userModel->find((int) $newUserId), 'error' => null, 'created' => true];
    }

    /**
     * Memastikan tujuan redirect pasca-OAuth selalu tetap berada di origin internal aplikasi.
     */
    public static function normalizeGoogleRedirect(?string $raw): string
    {
        $candidate = trim((string) $raw);
        if (
            $candidate === ''
            || strpos($candidate, '//') !== false
            || strpos($candidate, '\\') !== false
            || strpos($candidate, ':') !== false
            || preg_match('/[\x00-\x1F\x7F]/', $candidate)
        ) {
            return base_url('dashboard');
        }

        $trimmed = ltrim($candidate, '/');
        if ($trimmed === '' || ! preg_match('/^[a-zA-Z0-9][a-zA-Z0-9\/_\-\.\?\=\&\%]*$/', $trimmed)) {
            return base_url('dashboard');
        }

        return base_url($trimmed);
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
        session()->regenerate(true);
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
