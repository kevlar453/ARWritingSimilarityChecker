<?php

namespace App\Controllers;

use App\Libraries\EmailService;
use App\Libraries\SpamShield;
use CodeIgniter\Database\Exceptions\DatabaseException;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Database;

class LegalController extends BaseController
{
    /**
     * Halaman & Konten Kebijakan Privasi (Privacy Policy) untuk Akademisi
     */
    public function privacy()
    {
        $data = [
            'title'       => 'Kebijakan Privasi & Kedaulatan Data Akademik — AR Tilik',
            'lastUpdated' => '07 Oktober 2026',
        ];

        if ($this->request->isAJAX() || stripos($this->request->getHeaderLine('Accept'), 'application/json') !== false) {
            return $this->response->setJSON([
                'status' => 'success',
                'title'  => $data['title'],
                'html'   => view('legal/privacy_content', $data),
            ]);
        }

        return view('legal/privacy', $data);
    }

    /**
     * Halaman & Konten Ketentuan Layanan (Terms of Service / ToS) untuk Akademisi
     */
    public function terms()
    {
        $data = [
            'title'       => 'Ketentuan Layanan (Terms of Service) — AR Tilik',
            'lastUpdated' => '07 Oktober 2026',
        ];

        if ($this->request->isAJAX() || stripos($this->request->getHeaderLine('Accept'), 'application/json') !== false) {
            return $this->response->setJSON([
                'status' => 'success',
                'title'  => $data['title'],
                'html'   => view('legal/terms_content', $data),
            ]);
        }

        return view('legal/terms', $data);
    }

    /**
     * Menyediakan tantangan anti-spammer (Math Captcha & Time Token) via AJAX
     */
    public function challenge(): ResponseInterface
    {
        $shield = new SpamShield();
        return $this->response->setJSON([
            'status'     => 'success',
            'challenge'  => $shield->generateMathChallenge(),
            'time_token' => $shield->generateTimeToken(),
        ]);
    }

    /**
     * Endpoint API AJAX untuk memvalidasi keaktifan dan keabsahan email secara real-time
     */
    public function validateEmail(): ResponseInterface
    {
        $email = trim((string) ($this->request->getVar('email') ?? ''));
        $spamShield = new SpamShield();

        $result = $spamShield->validateEmailComprehensive($email);

        if (! $result['valid']) {
            return $this->response->setStatusCode(400)->setJSON([
                'status'     => 'error',
                'valid'      => false,
                'reason'     => $result['reason'],
                'suggestion' => $result['suggestion'] ?? null,
                'domain'     => $result['domain'] ?? '',
            ]);
        }

        return $this->response->setJSON([
            'status'     => 'success',
            'valid'      => true,
            'message'    => 'Alamat email aktif dan terverifikasi di server internet.',
            'suggestion' => $result['suggestion'] ?? null,
            'domain'     => $result['domain'],
            'mx_host'    => $result['mx_host'] ?? null,
        ]);
    }

    /**
     * Mengirimkan pesan/kontak pengunjung ke admin@tilik.arsetontong.top
     * Dilengkapi perlindungan multi-lapis anti-spammer:
     * 1. Honeypot check (field jebakan bot)
     * 2. Time-token validation (mencegah auto-submission instant)
     * 3. Math challenge verification (HMAC signed)
     * 4. Comprehensive Email Screener (Syntax, Disposable, Gibberish/Fake, Real-time DNS MX)
     * 5. IP rate limiting
     */
    public function sendMessage(): ResponseInterface
    {
        $spamShield = new SpamShield();
        $ip = $this->request->getIPAddress();
        $postData = $this->getRequestData();

        // 1. Anti-Spam: Cek Honeypot
        if ($spamShield->isHoneypotTriggered($postData, ['website_hp', 'fax_number'])) {
            // Beri respons sukses palsu agar bot tidak mengulang upaya manipulasi
            return $this->response->setJSON([
                'status'  => 'success',
                'message' => 'Pesan Anda telah diterima oleh sistem AR Tilik.',
            ]);
        }

        // 2. Anti-Spam: Verifikasi Token Waktu (Time Token)
        $timeToken = (string) ($postData['security_token'] ?? '');
        $timeCheck = $spamShield->validateTimeToken($timeToken, 1.5, 7200);
        if (! $timeCheck['valid']) {
            return $this->response->setStatusCode(400)->setJSON([
                'status'  => 'error',
                'message' => 'Validasi keamanan formulir gagal (' . $timeCheck['reason'] . '). Harap muat ulang halaman.',
            ]);
        }

        // 3. Anti-Spam: Verifikasi Math Challenge (Pertanyaan Matematika Sederhana Bertanda Tangan HMAC)
        $mathToken = (string) ($postData['captcha_token'] ?? $postData['math_token'] ?? '');
        $mathAnswer = (string) ($postData['captcha_ans'] ?? $postData['math_answer'] ?? '');
        if (! $spamShield->validateMathChallenge($mathToken, $mathAnswer)) {
            return $this->response->setStatusCode(400)->setJSON([
                'status'  => 'error',
                'message' => 'Jawaban verifikasi anti-spam tidak tepat atau sesi kedaluwarsa. Silakan hitung ulang.',
            ]);
        }

        // 4. Ekstraksi & Validasi Input Utama
        $name = trim((string) ($postData['name'] ?? ''));
        $email = strtolower(trim((string) ($postData['email'] ?? '')));
        $institution = trim((string) ($postData['institution'] ?? ''));
        $subject = trim((string) ($postData['subject'] ?? 'Pertanyaan Umum'));
        $message = trim((string) ($postData['message'] ?? ''));

        if (mb_strlen($name) < 2 || mb_strlen($name) > 100) {
            return $this->response->setStatusCode(400)->setJSON([
                'status'  => 'error',
                'message' => 'Harap cantumkan nama lengkap Anda (minimal 2 karakter).',
            ]);
        }

        // 5. Anti-Spam: Penyaringan Email Menyeluruh (Keaktifan, DNS MX, Anti-Gibberish, Anti-Burner)
        $emailFilter = $spamShield->validateEmailComprehensive($email);
        if (! $emailFilter['valid']) {
            return $this->response->setStatusCode(400)->setJSON([
                'status'     => 'error',
                'message'    => $emailFilter['reason'],
                'suggestion' => $emailFilter['suggestion'] ?? null,
            ]);
        }

        // 7. Anti-Spam: Rate Limiting (Maks. 3 pesan per 15 menit per IP)
        $rateCheck = $spamShield->checkRateLimit('contact_form', $email, $ip, 3, 900);
        if (! $rateCheck['allowed']) {
            return $this->response->setStatusCode(429)->setJSON([
                'status'  => 'error',
                'message' => 'Terlalu banyak permintaan dari jaringan Anda. ' . $rateCheck['reason'] . '.',
            ]);
        }

        if (mb_strlen($message) < 15) {
            return $this->response->setStatusCode(400)->setJSON([
                'status'  => 'error',
                'message' => 'Pesan Anda terlalu singkat. Mohon jelaskan kebutuhan atau pertanyaan Anda (minimal 15 karakter).',
            ]);
        }

        if (mb_strlen($message) > 4000) {
            return $this->response->setStatusCode(400)->setJSON([
                'status'  => 'error',
                'message' => 'Panjang pesan melebihi batas maksimal (4000 karakter).',
            ]);
        }

        // Catat attempt rate limiting
        $spamShield->recordFailedAttempt('contact_form', $email, $ip, 3, 900, 900);

        // 8. Simpan ke Basis Data (Tabel contact_messages)
        $db = Database::connect();
        $messageData = [
            'name'        => $name,
            'email'       => $email,
            'institution' => !empty($institution) ? $institution : null,
            'subject'     => $subject,
            'message'     => $message,
            'ip_address'  => $ip,
            'user_agent'  => mb_substr((string) ($this->request->getUserAgent() ?? ''), 0, 255),
            'status'      => 'unread',
            'created_at'  => date('Y-m-d H:i:s'),
            'updated_at'  => date('Y-m-d H:i:s'),
        ];

        try {
            $db->table('contact_messages')->insert($messageData);
        } catch (\Throwable $e) {
            log_message('error', 'Gagal menyimpan pesan kontak ke basis data: ' . $e->getMessage());
        }

        // 9. Kirimkan Notifikasi Email ke admin@tilik.arsetontong.top
        $emailService = new EmailService();
        $emailSent = $emailService->sendContactNotification($messageData);

        return $this->response->setJSON([
            'status'     => 'success',
            'email_sent' => $emailSent,
            'message'    => 'Terima kasih, pesan Anda telah berhasil dikirimkan ke tim pengembang AR Tilik (admin@tilik.arsetontong.top). Kami akan menindaklanjuti pesan Anda dalam 1x24 jam kerja.',
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
}
