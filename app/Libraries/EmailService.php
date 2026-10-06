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
}
