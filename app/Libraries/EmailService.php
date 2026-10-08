<?php

namespace App\Libraries;

use Config\Services;

class EmailService
{
    /**
     * Mengirimkan email verifikasi aktivasi akun ke pengguna baru
     */
    public function sendVerificationEmail(array $user, string $token): bool
    {
        $email = Services::email();

        $verifyUrl = base_url("auth/verify-email?token=" . urlencode($token));
        $userName = esc($user['name'] ?? 'Pengguna');
        $userEmail = $user['email'];

        $subject = 'Verifikasi Alamat Email Anda — AR Tilik';
        $htmlMessage = $this->buildVerificationTemplate($userName, $userEmail, $verifyUrl);

        $email->setTo($userEmail);
        $email->setSubject($subject);
        $email->setMessage($htmlMessage);
        $email->setMailType('html');

        // Plain text version fallback
        $email->setAltMessage(
            "Yth. {$userName},\n\n" .
            "Terima kasih telah mendaftar di AR Tilik (Sistem Penelaah Kesepadanan Naskah Akademik Mandiri & Keadilan Bukti).\n\n" .
            "Silakan buka tautan berikut untuk memverifikasi alamat email Anda:\n" .
            "{$verifyUrl}\n\n" .
            "Tautan ini berlaku selama 24 jam.\n\n" .
            "Jika Anda tidak merasa mendaftar, silakan abaikan email ini.\n\n" .
            "— Tim AR Tilik"
        );

        try {
            $sent = $email->send();
            if (! $sent) {
                log_message('error', 'Gagal mengirim email verifikasi ke ' . $userEmail . ': ' . $email->printDebugger(['headers']));
            }
            return (bool) $sent;
        } catch (\Throwable $e) {
            log_message('error', 'Exception saat mengirim email verifikasi ke ' . $userEmail . ': ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Mengirimkan email atur ulang kata sandi (Password Reset) ke pengguna
     */
    public function sendPasswordResetEmail(array $user, string $token): bool
    {
        $email = Services::email();

        $resetUrl = base_url("auth/reset-password?token=" . urlencode($token));
        $userName = esc($user['name'] ?? 'Pengguna');
        $userEmail = $user['email'];

        $subject = 'Atur Ulang Kata Sandi Akun Anda — AR Tilik';
        $htmlMessage = $this->buildPasswordResetTemplate($userName, $userEmail, $resetUrl);

        $email->setTo($userEmail);
        $email->setSubject($subject);
        $email->setMessage($htmlMessage);
        $email->setMailType('html');

        // Plain text version fallback
        $email->setAltMessage(
            "Yth. {$userName},\n\n" .
            "Kami menerima permintaan untuk mengatur ulang kata sandi akun AR Tilik Anda.\n\n" .
            "Silakan buka tautan berikut untuk membuat kata sandi baru:\n" .
            "{$resetUrl}\n\n" .
            "Tautan ini hanya berlaku selama 1 jam.\n\n" .
            "Jika Anda tidak pernah meminta pengaturan ulang kata sandi, silakan abaikan email ini dan kata sandi Anda tidak akan berubah.\n\n" .
            "— Tim AR Tilik"
        );

        try {
            $sent = $email->send();
            if (! $sent) {
                log_message('error', 'Gagal mengirim email reset password ke ' . $userEmail . ': ' . $email->printDebugger(['headers']));
            }
            return (bool) $sent;
        } catch (\Throwable $e) {
            log_message('error', 'Exception saat mengirim email reset password ke ' . $userEmail . ': ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Menyusun template email HTML modern bertema akademis
     */
    protected function buildVerificationTemplate(string $name, string $recipientEmail, string $verifyUrl): string
    {
        $logoUrl = base_url('images/logo.png');

        return <<<HTML
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi Alamat Email — AR Tilik</title>
</head>
<body style="margin:0; padding:0; background-color:#f1f5f9; font-family:'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color:#1e293b; -webkit-font-smoothing:antialiased;">
    <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color:#f1f5f9; padding: 40px 15px;">
        <tr>
            <td align="center">
                <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 600px; background-color: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.01); border: 1px solid #e2e8f0;">
                    <!-- Header Banner -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #1e1b4b 0%, #312e81 50%, #4338ca 100%); padding: 36px 32px; text-align: center;">
                            <img src="{$logoUrl}" alt="AR Tilik Logo" width="64" height="64" style="display: block; margin: 0 auto 12px auto; object-fit: contain;">
                            <h1 style="color: #ffffff; font-size: 22px; font-weight: 800; margin: 0 0 6px 0; letter-spacing: -0.5px;">AR TILIK</h1>
                            <p style="color: #c7d2fe; font-size: 13px; font-weight: 500; margin: 0; letter-spacing: 0.2px;">Sistem Penelaah Kesepadanan Naskah Akademik</p>
                        </td>
                    </tr>

                    <!-- Body Content -->
                    <tr>
                        <td style="padding: 36px 32px;">
                            <h2 style="font-size: 18px; font-weight: 700; color: #0f172a; margin: 0 0 16px 0;">
                                Konfirmasi & Aktivasi Akun Anda
                            </h2>
                            <p style="font-size: 14px; line-height: 1.6; color: #334155; margin: 0 0 16px 0;">
                                Yth. <strong>{$name}</strong>,
                            </p>
                            <p style="font-size: 14px; line-height: 1.6; color: #334155; margin: 0 0 24px 0;">
                                Terima kasih telah mendaftarkan akun di platform <strong>AR Tilik</strong>. Untuk memastikan keabsahan akun dan mengaktifkan akses submisi gratis serta fitur analisis kesepadanan karya ilmiah, silakan klik tombol verifikasi di bawah ini:
                            </p>

                            <!-- CTA Button -->
                            <div style="text-align: center; margin: 32px 0;">
                                <a href="{$verifyUrl}" target="_blank" style="display: inline-block; background: linear-gradient(135deg, #4338ca 0%, #4f46e5 100%); color: #ffffff; font-size: 15px; font-weight: 700; text-decoration: none; padding: 14px 36px; border-radius: 12px; box-shadow: 0 4px 14px rgba(79, 70, 229, 0.35); letter-spacing: 0.2px;">
                                    Aktifkan Akun & Verifikasi Email
                                </a>
                            </div>

                            <!-- Info Box -->
                            <div style="background-color: #f8fafc; border-left: 4px solid #4f46e5; border-radius: 6px; padding: 14px 16px; margin: 24px 0;">
                                <p style="font-size: 12px; color: #475569; margin: 0; line-height: 1.5;">
                                    ⏱️ <strong>Masa Berlaku:</strong> Tautan verifikasi ini aktif selama <strong>24 jam</strong>. Setelah lewat masa berlaku, Anda dapat mengajukan pengiriman ulang email aktivasi melalui halaman login.
                                </p>
                            </div>

                            <!-- Direct Link Fallback -->
                            <p style="font-size: 12px; line-height: 1.5; color: #64748b; margin: 24px 0 0 0; word-break: break-all;">
                                Jika tombol di atas tidak berfungsi, salin dan buka URL berikut di peramban Anda:<br>
                                <a href="{$verifyUrl}" style="color: #4338ca; text-decoration: underline;">{$verifyUrl}</a>
                            </p>
                        </td>
                    </tr>

                    <!-- Footer Note -->
                    <tr>
                        <td style="background-color: #f8fafc; border-top: 1px solid #e2e8f0; padding: 24px 32px; text-align: center;">
                            <p style="font-size: 12px; line-height: 1.5; color: #64748b; margin: 0 0 8px 0;">
                                Email ini dikirimkan otomatis ke <strong>{$recipientEmail}</strong> oleh peladen resmi AR Tilik.
                            </p>
                            <p style="font-size: 11px; line-height: 1.5; color: #94a3b8; margin: 0;">
                                Jika Anda tidak pernah melakukan pendaftaran di AR Tilik, silakan abaikan email ini.<br>
                                &copy; 2026 AR Tilik — Integritas Akademik & Ekosistem Terbuka.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;
    }

    /**
     * Menyusun template email HTML atur ulang kata sandi bertema akademis
     */
    protected function buildPasswordResetTemplate(string $name, string $recipientEmail, string $resetUrl): string
    {
        $logoUrl = base_url('images/logo.png');

        return <<<HTML
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Atur Ulang Kata Sandi — AR Tilik</title>
</head>
<body style="margin:0; padding:0; background-color:#f1f5f9; font-family:'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color:#1e293b; -webkit-font-smoothing:antialiased;">
    <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color:#f1f5f9; padding: 40px 15px;">
        <tr>
            <td align="center">
                <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 600px; background-color: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.01); border: 1px solid #e2e8f0;">
                    <!-- Header Banner -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #1e1b4b 0%, #312e81 50%, #4338ca 100%); padding: 36px 32px; text-align: center;">
                            <img src="{$logoUrl}" alt="AR Tilik Logo" width="64" height="64" style="display: block; margin: 0 auto 12px auto; object-fit: contain;">
                            <h1 style="color: #ffffff; font-size: 22px; font-weight: 800; margin: 0 0 6px 0; letter-spacing: -0.5px;">AR TILIK</h1>
                            <p style="color: #c7d2fe; font-size: 13px; font-weight: 500; margin: 0; letter-spacing: 0.2px;">Sistem Penelaah Kesepadanan Naskah Akademik</p>
                        </td>
                    </tr>

                    <!-- Body Content -->
                    <tr>
                        <td style="padding: 36px 32px;">
                            <div style="display: inline-block; padding: 6px 12px; background-color: #e0e7ff; color: #4338ca; border-radius: 20px; font-size: 12px; font-weight: 700; margin-bottom: 16px;">
                                🔐 Keamanan Akun
                            </div>
                            <h2 style="font-size: 18px; font-weight: 700; color: #0f172a; margin: 0 0 16px 0;">
                                Permintaan Atur Ulang Kata Sandi
                            </h2>
                            <p style="font-size: 14px; line-height: 1.6; color: #334155; margin: 0 0 16px 0;">
                                Yth. <strong>{$name}</strong>,
                            </p>
                            <p style="font-size: 14px; line-height: 1.6; color: #334155; margin: 0 0 24px 0;">
                                Kami menerima permintaan untuk mengatur ulang kata sandi akun <strong>AR Tilik</strong> Anda yang terdaftar pada email ini. Untuk melanjutkan proses dan menentukan kata sandi baru Anda, silakan klik tombol di bawah ini:
                            </p>

                            <!-- CTA Button -->
                            <div style="text-align: center; margin: 32px 0;">
                                <a href="{$resetUrl}" target="_blank" style="display: inline-block; background: linear-gradient(135deg, #4338ca 0%, #4f46e5 100%); color: #ffffff; font-size: 15px; font-weight: 700; text-decoration: none; padding: 14px 36px; border-radius: 12px; box-shadow: 0 4px 14px rgba(79, 70, 229, 0.35); letter-spacing: 0.2px;">
                                    Atur Ulang Kata Sandi Sekarang
                                </a>
                            </div>

                            <!-- Info Box -->
                            <div style="background-color: #f8fafc; border-left: 4px solid #f59e0b; border-radius: 6px; padding: 14px 16px; margin: 24px 0;">
                                <p style="font-size: 12px; color: #475569; margin: 0; line-height: 1.5;">
                                    ⏱️ <strong>Masa Berlaku:</strong> Tautan pengaturan ulang kata sandi ini hanya berlaku selama <strong>1 jam</strong>.<br>
                                    🛡️ <strong>Bukan Anda?</strong> Jika Anda tidak pernah meminta perubahan kata sandi, abaikan email ini dengan aman. Kata sandi akun Anda tidak akan berubah.
                                </p>
                            </div>

                            <!-- Direct Link Fallback -->
                            <p style="font-size: 12px; line-height: 1.5; color: #64748b; margin: 24px 0 0 0; word-break: break-all;">
                                Jika tombol di atas tidak dapat diklik, salin dan buka tautan berikut di peramban Anda:<br>
                                <a href="{$resetUrl}" style="color: #4338ca; text-decoration: underline;">{$resetUrl}</a>
                            </p>
                        </td>
                    </tr>

                    <!-- Footer Note -->
                    <tr>
                        <td style="background-color: #f8fafc; border-top: 1px solid #e2e8f0; padding: 24px 32px; text-align: center;">
                            <p style="font-size: 12px; line-height: 1.5; color: #64748b; margin: 0 0 8px 0;">
                                Email ini dikirimkan otomatis oleh sistem keamanan AR Tilik untuk <strong>{$recipientEmail}</strong>.
                            </p>
                            <p style="font-size: 11px; line-height: 1.5; color: #94a3b8; margin: 0;">
                                &copy; 2026 AR Tilik — Integritas Akademik & Ekosistem Terbuka.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;
    }

    /**
     * Mengirimkan notifikasi pesan/kontak pengunjung ke admin@tilik.arsetontong.top
     */
    public function sendContactNotification(array $data): bool
    {
        $email = Services::email();
        $adminEmail = 'admin@tilik.arsetontong.top';

        $visitorName = esc($data['name'] ?? 'Pengunjung');
        $visitorEmail = filter_var($data['email'] ?? '', FILTER_SANITIZE_EMAIL);
        $subject = '[Pesan Pengunjung] ' . esc($data['subject'] ?? 'Pertanyaan Umum') . ' — ' . $visitorName;

        $htmlMessage = $this->buildContactNotificationTemplate($data);

        $email->setTo($adminEmail);
        $email->setReplyTo($visitorEmail, $visitorName);
        $email->setSubject($subject);
        $email->setMessage($htmlMessage);
        $email->setMailType('html');

        $plainText = "Pesan baru dari pengunjung AR Tilik:\n\n" .
            "Nama: " . ($data['name'] ?? '-') . "\n" .
            "Email: {$visitorEmail}\n" .
            "Institusi: " . ($data['institution'] ?? '-') . "\n" .
            "Subjek: " . ($data['subject'] ?? '-') . "\n" .
            "IP Address: " . ($data['ip_address'] ?? '-') . "\n" .
            "Waktu: " . date('Y-m-d H:i:s') . " WIB\n\n" .
            "Isi Pesan:\n" . ($data['message'] ?? '-') . "\n\n" .
            "Balas langsung ke email ini untuk merespons pengunjung ({$visitorEmail}).\n\n" .
            "— AR Tilik System Notification";
        $email->setAltMessage($plainText);

        try {
            $sent = $email->send();
            if (! $sent) {
                log_message('error', 'Gagal mengirim email pesan pengunjung ke ' . $adminEmail . ': ' . $email->printDebugger(['headers']));
            }
            return (bool) $sent;
        } catch (\Throwable $e) {
            log_message('error', 'Exception saat mengirim email pesan pengunjung: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Template email HTML untuk notifikasi pesan masuk admin
     */
    protected function buildContactNotificationTemplate(array $data): string
    {
        $logoUrl = base_url('images/logo.png');
        $name = esc($data['name'] ?? 'Pengunjung');
        $email = esc($data['email'] ?? '');
        $institution = esc($data['institution'] ?: '-');
        $subject = esc($data['subject'] ?? 'Pertanyaan Umum');
        $message = nl2br(esc($data['message'] ?? ''));
        $ip = esc($data['ip_address'] ?? '-');
        $time = date('d F Y, H:i:s') . ' WIB';
        $replyMailto = 'mailto:' . urlencode($email) . '?subject=' . urlencode('Re: ' . $subject . ' — AR Tilik');

        return <<<HTML
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pesan Masuk Pengunjung AR Tilik</title>
</head>
<body style="margin:0; padding:0; background-color:#f1f5f9; font-family:'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color:#1e293b; -webkit-font-smoothing:antialiased;">
    <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color:#f1f5f9; padding: 40px 15px;">
        <tr>
            <td align="center">
                <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 620px; background-color: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05); border: 1px solid #e2e8f0;">
                    <!-- Header -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 60%, #4338ca 100%); padding: 30px 32px; text-align: left;">
                            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
                                <tr>
                                    <td>
                                        <div style="display:flex; align-items:center; gap:12px;">
                                            <img src="{$logoUrl}" alt="AR Tilik" width="44" height="44" style="object-fit:contain; vertical-align:middle;">
                                            <div style="display:inline-block; vertical-align:middle; margin-left:10px;">
                                                <span style="color:#ffffff; font-size:18px; font-weight:800; display:block;">AR Tilik</span>
                                                <span style="color:#38bdf8; font-size:12px; font-weight:600;">Notifikasi Pesan Kontak Pengunjung</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td align="right">
                                        <span style="display:inline-block; padding:4px 10px; background:rgba(56,189,248,0.2); border:1px solid rgba(56,189,248,0.4); border-radius:20px; color:#e0f2fe; font-size:11px; font-weight:700;">Inquiry Baru</span>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Body -->
                    <tr>
                        <td style="padding: 32px;">
                            <h2 style="font-size: 17px; font-weight: 800; color: #0f172a; margin: 0 0 20px 0; border-bottom: 2px solid #f1f5f9; padding-bottom: 12px;">
                                {$subject}
                            </h2>

                            <!-- Metadata Table -->
                            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="margin-bottom: 24px; font-size: 13px; line-height: 1.6;">
                                <tr>
                                    <td width="120" style="color: #64748b; font-weight: 600; padding: 6px 0;">Pengirim:</td>
                                    <td style="color: #0f172a; font-weight: 700; padding: 6px 0;">{$name}</td>
                                </tr>
                                <tr>
                                    <td style="color: #64748b; font-weight: 600; padding: 6px 0;">Email:</td>
                                    <td style="color: #4338ca; font-weight: 700; padding: 6px 0;"><a href="mailto:{$email}" style="color:#4338ca; text-decoration:none;">{$email}</a></td>
                                </tr>
                                <tr>
                                    <td style="color: #64748b; font-weight: 600; padding: 6px 0;">Institusi:</td>
                                    <td style="color: #334155; padding: 6px 0;">{$institution}</td>
                                </tr>
                                <tr>
                                    <td style="color: #64748b; font-weight: 600; padding: 6px 0;">Waktu Kirim:</td>
                                    <td style="color: #64748b; padding: 6px 0;">{$time}</td>
                                </tr>
                                <tr>
                                    <td style="color: #64748b; font-weight: 600; padding: 6px 0;">IP Pelapor:</td>
                                    <td style="color: #64748b; font-family:monospace; padding: 6px 0;">{$ip}</td>
                                </tr>
                            </table>

                            <!-- Message Content -->
                            <div style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-left: 4px solid #4338ca; border-radius: 10px; padding: 18px 20px; margin-bottom: 26px;">
                                <div style="font-size: 11px; font-weight: 800; color: #4338ca; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px;">Uraian Pesan:</div>
                                <div style="font-size: 14px; line-height: 1.6; color: #1e293b;">
                                    {$message}
                                </div>
                            </div>

                            <!-- Action Button -->
                            <div style="text-align: center; margin: 30px 0 10px 0;">
                                <a href="{$replyMailto}" style="display: inline-block; background: linear-gradient(135deg, #4338ca 0%, #312e81 100%); color: #ffffff; text-decoration: none; font-size: 14px; font-weight: 700; padding: 12px 28px; border-radius: 10px; box-shadow: 0 4px 12px rgba(67, 56, 202, 0.25);">
                                    ✉️ Balas Pesan ke Pengirim Langsung
                                </a>
                            </div>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #f8fafc; border-top: 1px solid #e2e8f0; padding: 20px 32px; text-align: center;">
                            <p style="font-size: 11px; color: #94a3b8; margin: 0; line-height: 1.5;">
                                Notifikasi ini dikirimkan otomatis oleh sistem formulir kontak terlindungi spam AR Tilik ke <strong>admin@tilik.arsetontong.top</strong>.<br>
                                &copy; 2026 AR Tilik — Platform Penelaah Kesepadanan Naskah Akademik.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;
    }
}
