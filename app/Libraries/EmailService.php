<?php

namespace App\Libraries;

use Config\Services;

class EmailService
{
    /**
     * Mengirimkan email verifikasi aktivasi akun ke pengguna baru atau pengingat berkala
     */
    public function sendVerificationEmail(array $user, string $token, int $reminderNumber = 1): bool
    {
        $email = Services::email();

        $verifyUrl = base_url("auth/verify-email?token=" . urlencode($token));
        $userName = esc($user['name'] ?? 'Pengguna');
        $userEmail = $user['email'];

        $subject = match ($reminderNumber) {
            2       => '[Pengingat] Verifikasi Alamat Email Anda — AR Tilik',
            3       => '[Peringatan Terakhir] Konfirmasi Email Sebelum Akun Dihapus — AR Tilik',
            default => 'Verifikasi Alamat Email Anda — AR Tilik'
        };

        $htmlMessage = $this->buildVerificationTemplate($userName, $userEmail, $verifyUrl, $reminderNumber);

        $email->setTo($userEmail);
        $email->setSubject($subject);
        $email->setMessage($htmlMessage);
        $email->setMailType('html');

        // Plain text version fallback
        $altIntro = match ($reminderNumber) {
            2       => "Kami mengingatkan kembali bahwa akun AR Tilik Anda belum diverifikasi.\n\nSilakan buka tautan berikut untuk mengaktifkan akun Anda:",
            3       => "Ini adalah pengingat terakhir (ke-3) dari AR Tilik. Jika Anda tidak memverifikasi alamat email Anda dalam waktu 24 jam ke depan, akun pendaftaran Anda akan dihapus secara otomatis dari sistem.\n\nTautan verifikasi akun Anda:",
            default => "Terima kasih telah mendaftar di AR Tilik (Sistem Penelaah Kesepadanan Naskah Akademik Mandiri & Keadilan Bukti).\n\nSilakan buka tautan berikut untuk memverifikasi alamat email Anda:"
        };

        $email->setAltMessage(
            "Yth. {$userName},\n\n" .
            "{$altIntro}\n" .
            "{$verifyUrl}\n\n" .
            "Tautan ini berlaku selama 24 jam.\n\n" .
            "Jika Anda tidak merasa mendaftar di AR Tilik, silakan abaikan email ini.\n\n" .
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
     * Menyusun template email HTML modern bertema akademis untuk aktivasi dan pengingat
     */
    protected function buildVerificationTemplate(string $name, string $recipientEmail, string $verifyUrl, int $reminderNumber = 1): string
    {
        $logoUrl = base_url('images/logo.png');

        $badgeHtml = match ($reminderNumber) {
            2       => '<div style="display: inline-block; padding: 6px 14px; background-color: #fef3c7; color: #b45309; border-radius: 20px; font-size: 12px; font-weight: 700; margin-bottom: 16px; border: 1px solid #fde68a;">🔔 Pengingat Aktivasi Akun (Email 2 dari 3)</div>',
            3       => '<div style="display: inline-block; padding: 6px 14px; background-color: #fee2e2; color: #b91c1c; border-radius: 20px; font-size: 12px; font-weight: 700; margin-bottom: 16px; border: 1px solid #fecaca;">⚠️ Peringatan Terakhir (Email 3 dari 3)</div>',
            default => '<div style="display: inline-block; padding: 6px 14px; background-color: #e0e7ff; color: #4338ca; border-radius: 20px; font-size: 12px; font-weight: 700; margin-bottom: 16px; border: 1px solid #c7d2fe;">✨ Pendaftaran Akun Baru</div>'
        };

        $heading = match ($reminderNumber) {
            2       => 'Pengingat: Verifikasi & Aktivasi Akun Anda',
            3       => 'Segera Konfirmasi Email Sebelum Akun Dihapus Otomatis',
            default => 'Konfirmasi & Aktivasi Akun Anda'
        };

        $bodyIntro = match ($reminderNumber) {
            2       => 'Kami melihat bahwa Anda telah mendaftarkan akun di platform <strong>AR Tilik</strong>, namun hingga saat ini alamat email Anda belum diverifikasi. Agar akun Anda dapat aktif dan mendapatkan kuota gratis submisi karya ilmiah, silakan klik tombol verifikasi di bawah ini:',
            3       => 'Ini adalah <strong>email pengingat terakhir (ke-3)</strong> dari sistem AR Tilik untuk akun Anda. Sesuai kebijakan kebersihan data dan pencegahan akun pasif, <strong>pendaftar yang tidak mengonfirmasi email setelah 3 kali pengiriman email akan dihapus datanya secara permanen</strong> dari basis data kami.<br><br>Jika Anda masih ingin menggunakan akun ini, silakan konfirmasi sebelum batas waktu berakhir:',
            default => 'Terima kasih telah mendaftarkan akun di platform <strong>AR Tilik</strong>. Untuk memastikan keabsahan akun dan mengaktifkan akses submisi gratis serta fitur analisis kesepadanan karya ilmiah, silakan klik tombol verifikasi di bawah ini:'
        };

        $buttonText = match ($reminderNumber) {
            2       => 'Verifikasi Akun Sekarang',
            3       => 'Verifikasi & Pertahankan Akun Saya',
            default => 'Aktifkan Akun & Verifikasi Email'
        };

        $btnColor = match ($reminderNumber) {
            3       => 'linear-gradient(135deg, #e11d48 0%, #be123c 100%)',
            default => 'linear-gradient(135deg, #4338ca 0%, #4f46e5 100%)'
        };

        $infoBox = match ($reminderNumber) {
            3       => '<div style="background-color: #fff1f2; border-left: 4px solid #e11d48; border-radius: 6px; padding: 14px 16px; margin: 24px 0;">
                            <p style="font-size: 12px; color: #9f1239; margin: 0 0 6px 0; line-height: 1.5;">
                                🚨 <strong>Batas Waktu Terakhir:</strong> Tautan verifikasi ini aktif selama <strong>24 jam</strong>. Jika tidak diverifikasi sebelum batas waktu berakhir, akun pendaftaran Anda akan dihapus secara otomatis dan permanen untuk membebaskan alamat email Anda.
                            </p>
                            <p style="font-size: 11px; color: #be123c; margin: 0; line-height: 1.4;">
                                💡 <em>Tips: Jika email ini masuk ke folder <strong>SPAM / Junk</strong> atau <em>Promosi</em>, tandai sebagai <strong>"Bukan Spam" (Not Spam)</strong> agar pemberitahuan selanjutnya masuk lancar ke Kotak Masuk Anda.</em>
                            </p>
                        </div>',
            default => '<div style="background-color: #f8fafc; border-left: 4px solid #4f46e5; border-radius: 6px; padding: 14px 16px; margin: 24px 0;">
                            <p style="font-size: 12px; color: #475569; margin: 0 0 6px 0; line-height: 1.5;">
                                ⏱️ <strong>Masa Berlaku:</strong> Tautan verifikasi ini aktif selama <strong>24 jam</strong>. Setelah lewat masa berlaku, Anda dapat mengajukan pengiriman ulang email aktivasi melalui halaman login.
                            </p>
                            <p style="font-size: 11px; color: #64748b; margin: 0; line-height: 1.4;">
                                💡 <em>Tips: Jika email ini diterima pada folder <strong>SPAM / Junk</strong> atau tab <em>Promosi</em>, silakan tandai <strong>"Bukan Spam" (Not Spam)</strong> agar laporan uji kesepadanan naskah Anda selalu masuk ke Kotak Masuk utama.</em>
                            </p>
                        </div>'
        };

        return <<<HTML
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$heading} — AR Tilik</title>
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
                            {$badgeHtml}
                            <h2 style="font-size: 18px; font-weight: 700; color: #0f172a; margin: 0 0 16px 0;">
                                {$heading}
                            </h2>
                            <p style="font-size: 14px; line-height: 1.6; color: #334155; margin: 0 0 16px 0;">
                                Yth. <strong>{$name}</strong>,
                            </p>
                            <p style="font-size: 14px; line-height: 1.6; color: #334155; margin: 0 0 24px 0;">
                                {$bodyIntro}
                            </p>

                            <!-- CTA Button -->
                            <div style="text-align: center; margin: 32px 0;">
                                <a href="{$verifyUrl}" target="_blank" style="display: inline-block; background: {$btnColor}; color: #ffffff; font-size: 15px; font-weight: 700; text-decoration: none; padding: 14px 36px; border-radius: 12px; box-shadow: 0 4px 14px rgba(79, 70, 229, 0.35); letter-spacing: 0.2px;">
                                    {$buttonText}
                                </a>
                            </div>

                            <!-- Info / Warning Box -->
                            {$infoBox}

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

    /**
     * Mengirimkan email Ringkasan Mingguan AR Tilik sesuai template dan aturan redaksional
     */
    public function sendWeeklySummaryEmail(array $user, array $summaryData): bool
    {
        $email = Services::email();

        $userModel = new \App\Models\UserModel();
        $unsubToken = !empty($user['unsubscribe_token']) ? $user['unsubscribe_token'] : $userModel->getOrCreateUnsubscribeToken((int)$user['id']);
        $unsubUrl = base_url("email/unsubscribe?token=" . urlencode($unsubToken) . "&type=weekly");

        $jumlahDokumen = (int) ($summaryData['jumlah_dokumen'] ?? 0);
        $subject = ($jumlahDokumen > 0)
            ? "Sepekan bersama AR Tilik: {$jumlahDokumen} dokumen diperiksa"
            : "Sepekan bersama AR Tilik: Ringkasan & Kuota Anda";

        $htmlMessage = $this->buildWeeklySummaryTemplate($user, $summaryData, $unsubUrl);
        $altMessage  = $this->buildWeeklySummaryAltText($user, $summaryData, $unsubUrl);

        $recipientEmail = $user['email'];

        $configEmail = config('Email');
        $fromEmail = !empty($configEmail->fromEmail) ? $configEmail->fromEmail : 'tilik@arsetontong.top';
        $email->setFrom($fromEmail, 'Makarius Yuwono — AR Tilik');
        $email->setReplyTo($fromEmail, 'Makarius Yuwono — AR Tilik');

        $email->setTo($recipientEmail);
        $email->setSubject($subject);
        $email->setMessage($htmlMessage);
        $email->setMailType('html');
        $email->setAltMessage($altMessage);

        // Header RFC 8058 One-Click Unsubscribe
        $email->setHeader('List-Unsubscribe', "<{$unsubUrl}>");
        $email->setHeader('List-Unsubscribe-Post', 'List-Unsubscribe=One-Click');

        try {
            $sent = $email->send();
            if (!$sent) {
                log_message('error', "Gagal mengirim ringkasan mingguan ke {$recipientEmail}: " . $email->printDebugger(['headers']));
            } else {
                // Catat waktu pengiriman terakhir
                $userModel->update($user['id'], [
                    'last_weekly_summary_sent_at' => date('Y-m-d H:i:s'),
                ]);
            }
            return (bool) $sent;
        } catch (\Throwable $e) {
            log_message('error', "Exception saat mengirim ringkasan mingguan ke {$recipientEmail}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Menyusun templat HTML Ringkasan Mingguan AR Tilik
     */
    protected function buildWeeklySummaryTemplate(array $user, array $summaryData, string $unsubscribeUrl): string
    {
        $hour = (int) date('H');
        if ($hour >= 5 && $hour < 11) {
            $sapaanWaktu = 'pagi';
        } elseif ($hour >= 11 && $hour < 15) {
            $sapaanWaktu = 'siang';
        } elseif ($hour >= 15 && $hour < 18) {
            $sapaanWaktu = 'sore';
        } else {
            $sapaanWaktu = 'pagi';
        }

        $nama = esc($user['name'] ?? 'Pengguna AR Tilik');
        $tanggalMulai = esc($summaryData['tanggal_mulai'] ?? date('d M Y', strtotime('-7 days')));
        $tanggalSelesai = esc($summaryData['tanggal_selesai'] ?? date('d M Y'));

        $jumlahDokumen = (int) ($summaryData['jumlah_dokumen'] ?? 0);
        $rataRataSkor = number_format((float) ($summaryData['rata_rata_skor'] ?? 0), 1);
        $sitasiTerverifikasi = (int) ($summaryData['sitasi_terverifikasi'] ?? 0);
        $sitasiMencurigakan = (int) ($summaryData['sitasi_mencurigakan'] ?? 0);
        $kuotaTerpakai = (int) ($summaryData['kuota_terpakai'] ?? 0);
        $kuotaTotal = (int) ($summaryData['kuota_total'] ?? 3);
        $sisaKuota = esc($summaryData['sisa_kuota'] ?? '3 dokumen');

        $dokumenTerakhir = $summaryData['dokumen_terakhir'] ?? null;
        $judulTrik = esc($summaryData['judul_trik'] ?? 'Periksa sitasi, bukan hanya kemiripan');
        $isiTrik = esc($summaryData['isi_trik'] ?? 'AR Tilik memeriksa rujukan Anda ke basis data eksternal seperti OpenAlex dan Crossref.');

        $linkPeriksa = base_url('dashboard?tab=upload');
        $linkLaporan = base_url('dashboard?tab=documents');

        // Blok Bagian Ringkasan
        if ($jumlahDokumen > 0) {
            $ringkasanHtml = <<<HTML
            <div style="background-color: #f8fafc; border-radius: 12px; border: 1px solid #e2e8f0; padding: 18px 22px; margin-bottom: 22px;">
                <div style="font-size: 14px; font-weight: 700; color: #0f172a; margin-bottom: 12px;">Ringkasan Anda minggu ini:</div>
                <ul style="margin: 0; padding-left: 20px; font-size: 13px; color: #334155; line-height: 1.8;">
                    <li><strong>Dokumen diperiksa:</strong> {$jumlahDokumen} dokumen</li>
                    <li><strong>Rata-rata skor kemiripan:</strong> {$rataRataSkor}%</li>
                    <li><strong>Sitasi yang terverifikasi:</strong> {$sitasiTerverifikasi} sitasi</li>
                    <li><strong>Sitasi mencurigakan yang ditandai:</strong> {$sitasiMencurigakan} sitasi</li>
                    <li><strong>Kuota terpakai minggu ini:</strong> {$kuotaTerpakai} dari {$kuotaTotal}</li>
                    <li><strong>Sisa kuota Anda:</strong> {$sisaKuota}</li>
                </ul>
            </div>
HTML;
        } else {
            $ringkasanHtml = <<<HTML
            <div style="background-color: #f8fafc; border-radius: 12px; border: 1px solid #e2e8f0; padding: 18px 22px; margin-bottom: 22px;">
                <div style="font-size: 14px; font-weight: 700; color: #0f172a; margin-bottom: 8px;">Ringkasan Anda minggu ini:</div>
                <p style="font-size: 13px; color: #475569; line-height: 1.6; margin: 0;">
                    Minggu ini Anda belum memeriksa dokumen. Kuota Anda sebesar <strong>{$sisaKuota}</strong> masih tersedia dan siap dipakai kapan saja.
                </p>
            </div>
HTML;
        }

        // Blok Dokumen Terakhir
        $dokumenTerakhirHtml = '';
        if (!empty($dokumenTerakhir) && !empty($dokumenTerakhir['title'])) {
            $judulDoc = esc($dokumenTerakhir['title']);
            $tglDoc   = esc($dokumenTerakhir['date'] ?? date('d M Y'));
            $skorDoc  = number_format((float)($dokumenTerakhir['score'] ?? 0), 1);
            $dokumenTerakhirHtml = <<<HTML
            <p style="font-size: 13px; color: #334155; line-height: 1.6; margin: 0 0 20px 0; padding: 12px 16px; background-color: #f1f5f9; border-radius: 8px;">
                Dokumen terakhir Anda: <strong>"{$judulDoc}"</strong>, diperiksa pada {$tglDoc}, dengan skor kemiripan <strong>{$skorDoc}%</strong>.
            </p>
HTML;
        }

        return <<<HTML
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ringkasan Mingguan AR Tilik</title>
</head>
<body style="margin:0; padding:0; background-color:#f8fafc; font-family:-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color:#1e293b; line-height: 1.6;">
    <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color:#f8fafc; padding: 30px 15px;">
        <tr>
            <td align="center">
                <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 600px; background-color: #ffffff; border-radius: 14px; overflow: hidden; border: 1px solid #e2e8f0; box-shadow: 0 4px 12px rgba(15, 23, 42, 0.04);">
                    <!-- Header -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #1e1b4b 0%, #312e81 100%); padding: 24px 28px; text-align: left;">
                            <div style="font-size: 20px; font-weight: 800; color: #ffffff; letter-spacing: -0.5px;">⚖️ AR Tilik</div>
                            <div style="font-size: 11px; color: #c7d2fe; margin-top: 2px;">Sistem Pemeriksa Kesamaan Naskah &amp; Integritas Akademik</div>
                        </td>
                    </tr>

                    <!-- Body -->
                    <tr>
                        <td style="padding: 28px 28px 20px 28px;">
                            <p style="font-size: 14px; color: #0f172a; margin: 0 0 16px 0;">
                                Selamat {$sapaanWaktu}, <strong>{$nama}</strong>,
                            </p>
                            <p style="font-size: 13px; color: #475569; margin: 0 0 20px 0;">
                                Berikut ringkasan penggunaan AR Tilik Anda selama sepekan terakhir, {$tanggalMulai} – {$tanggalSelesai}.
                            </p>

                            {$ringkasanHtml}
                            {$dokumenTerakhirHtml}

                            <!-- Catatan Jujur Soal Skor -->
                            <div style="background-color: #fffbeb; border-left: 3px solid #f59e0b; padding: 12px 16px; border-radius: 6px; margin-bottom: 22px;">
                                <div style="font-size: 12px; font-weight: 700; color: #92400e; margin-bottom: 4px;">Catatan jujur soal skor:</div>
                                <p style="font-size: 12px; color: #78350f; margin: 0; line-height: 1.5;">
                                    Skor kemiripan adalah ukuran kesamaan teks, bukan vonis plagiarisme. Dokumen dengan skor rendah tetap bisa mengandung sitasi yang bermasalah, dan dokumen dengan skor tinggi belum tentu menjiplak. Karena itu, selalu baca bagian yang ditandai di laporan, bukan hanya angkanya.
                                </p>
                            </div>

                            <!-- Trik Minggu Ini -->
                            <div style="background-color: #eef2ff; border: 1px solid #c7d2fe; border-radius: 10px; padding: 16px 18px; margin-bottom: 24px;">
                                <div style="font-size: 13px; font-weight: 800; color: #3730a3; margin-bottom: 6px;">
                                    💡 Trik minggu ini: {$judulTrik}
                                </div>
                                <p style="font-size: 12px; color: #312e81; margin: 0; line-height: 1.6;">
                                    {$isiTrik}
                                </p>
                            </div>

                            <!-- Lanjutkan dari sini -->
                            <div style="margin-bottom: 24px;">
                                <div style="font-size: 13px; font-weight: 700; color: #0f172a; margin-bottom: 8px;">Lanjutkan dari sini:</div>
                                <div style="font-size: 13px;">
                                    • <a href="{$linkPeriksa}" style="color: #4338ca; font-weight: 600; text-decoration: underline;">Periksa dokumen baru</a><br>
                                    • <a href="{$linkLaporan}" style="color: #4338ca; font-weight: 600; text-decoration: underline;">Lihat laporan saya</a>
                                </div>
                            </div>

                            <p style="font-size: 13px; color: #475569; margin: 0 0 16px 0; line-height: 1.6;">
                                Kalau ada laporan yang hasilnya membingungkan, balas saja email ini dan ceritakan bagian mana yang meragukan. Kami akan membantu membaca hasilnya bersama Anda.
                            </p>
                            <p style="font-size: 13px; color: #475569; margin: 0 0 20px 0;">
                                Terima kasih sudah mempercayakan pemeriksaan naskah Anda kepada AR Tilik.
                            </p>

                            <p style="font-size: 13px; color: #0f172a; margin: 0; line-height: 1.5;">
                                Salam,<br>
                                <strong>Makarius Yuwono — AR Tilik</strong><br>
                                <a href="https://tilik.arsetontong.top" style="color: #4338ca; text-decoration: none;">https://tilik.arsetontong.top</a>
                            </p>
                        </td>
                    </tr>

                    <!-- Footer Unsubscribe & Pengaturan -->
                    <tr>
                        <td style="background-color: #f8fafc; border-top: 1px solid #e2e8f0; padding: 20px 28px; text-align: center; font-size: 11px; color: #64748b; line-height: 1.6;">
                            Surel ini dikirimkan secara otomatis setiap awal pekan sebagai ringkasan penggunaan akun AR Tilik Anda.<br>
                            Jika tidak ingin menerima ringkasan mingguan lagi, Anda dapat <a href="{$unsubscribeUrl}" target="_blank" rel="noopener noreferrer" style="color: #4f46e5; font-weight: 600; text-decoration: underline;">berhenti berlangganan di sini</a> atau <a href="{$unsubscribeUrl}" target="_blank" rel="noopener noreferrer" style="color: #4f46e5; text-decoration: underline;">atur preferensi komunikasi surel Anda</a>.
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
     * Menyusun versi teks polos (AltText) untuk email ringkasan mingguan
     */
    protected function buildWeeklySummaryAltText(array $user, array $summaryData, string $unsubscribeUrl): string
    {
        $hour = (int) date('H');
        if ($hour >= 5 && $hour < 11) {
            $sapaanWaktu = 'pagi';
        } elseif ($hour >= 11 && $hour < 15) {
            $sapaanWaktu = 'siang';
        } elseif ($hour >= 15 && $hour < 18) {
            $sapaanWaktu = 'sore';
        } else {
            $sapaanWaktu = 'pagi';
        }

        $nama = $user['name'] ?? 'Pengguna';
        $tanggalMulai = $summaryData['tanggal_mulai'] ?? date('d M Y', strtotime('-7 days'));
        $tanggalSelesai = $summaryData['tanggal_selesai'] ?? date('d M Y');
        $jumlahDokumen = (int) ($summaryData['jumlah_dokumen'] ?? 0);
        $sisaKuota = $summaryData['sisa_kuota'] ?? '3 dokumen';

        $text = "Selamat {$sapaanWaktu}, {$nama},\n\n";
        $text .= "Berikut ringkasan penggunaan AR Tilik Anda selama sepekan terakhir, {$tanggalMulai} – {$tanggalSelesai}.\n\n";

        if ($jumlahDokumen > 0) {
            $rataRataSkor = number_format((float) ($summaryData['rata_rata_skor'] ?? 0), 1);
            $sitasiTerverifikasi = (int) ($summaryData['sitasi_terverifikasi'] ?? 0);
            $sitasiMencurigakan = (int) ($summaryData['sitasi_mencurigakan'] ?? 0);
            $kuotaTerpakai = (int) ($summaryData['kuota_terpakai'] ?? 0);
            $kuotaTotal = (int) ($summaryData['kuota_total'] ?? 3);

            $text .= "Ringkasan Anda minggu ini\n";
            $text .= "• Dokumen diperiksa: {$jumlahDokumen} dokumen\n";
            $text .= "• Rata-rata skor kemiripan: {$rataRataSkor}%\n";
            $text .= "• Sitasi yang terverifikasi: {$sitasiTerverifikasi} sitasi\n";
            $text .= "• Sitasi mencurigakan yang ditandai: {$sitasiMencurigakan} sitasi\n";
            $text .= "• Kuota terpakai minggu ini: {$kuotaTerpakai} dari {$kuotaTotal}\n";
            $text .= "• Sisa kuota Anda: {$sisaKuota}\n\n";

            $dokumenTerakhir = $summaryData['dokumen_terakhir'] ?? null;
            if (!empty($dokumenTerakhir) && !empty($dokumenTerakhir['title'])) {
                $judulDoc = $dokumenTerakhir['title'];
                $tglDoc   = $dokumenTerakhir['date'] ?? date('d M Y');
                $skorDoc  = number_format((float)($dokumenTerakhir['score'] ?? 0), 1);
                $text .= "Dokumen terakhir Anda: \"{$judulDoc}\", diperiksa pada {$tglDoc}, dengan skor kemiripan {$skorDoc}%.\n\n";
            }
        } else {
            $text .= "Ringkasan Anda minggu ini:\n";
            $text .= "Minggu ini Anda belum memeriksa dokumen. Kuota Anda sebesar {$sisaKuota} masih tersedia dan siap dipakai kapan saja.\n\n";
        }

        $text .= "Catatan jujur soal skor: skor kemiripan adalah ukuran kesamaan teks, bukan vonis plagiarisme. Dokumen dengan skor rendah tetap bisa mengandung sitasi yang bermasalah, dan dokumen dengan skor tinggi belum tentu menjiplak. Karena itu, selalu baca bagian yang ditandai di laporan, bukan hanya angkanya.\n\n";

        $judulTrik = $summaryData['judul_trik'] ?? '';
        $isiTrik = $summaryData['isi_trik'] ?? '';
        $text .= "Trik minggu ini: {$judulTrik}\n";
        $text .= "{$isiTrik}\n\n";

        $text .= "Lanjutkan dari sini:\n";
        $text .= "• Periksa dokumen baru: " . base_url('dashboard?tab=upload') . "\n";
        $text .= "• Lihat laporan saya: " . base_url('dashboard?tab=documents') . "\n\n";

        $text .= "Kalau ada laporan yang hasilnya membingungkan, balas saja email ini dan ceritakan bagian mana yang meragukan. Kami akan membantu membaca hasilnya bersama Anda.\n\n";
        $text .= "Terima kasih sudah mempercayakan pemeriksaan naskah Anda kepada AR Tilik.\n\n";
        $text .= "Salam,\nMakarius Yuwono — AR Tilik\nhttps://tilik.arsetontong.top\n\n";
        $text .= "---\n";
        $text .= "Surel ini dikirimkan secara otomatis setiap awal pekan sebagai ringkasan penggunaan akun AR Tilik Anda.\n";
        $text .= "Untuk berhenti berlangganan ringkasan mingguan atau mengatur preferensi surel, kunjungi tautan berikut:\n";
        $text .= "{$unsubscribeUrl}\n";

        return $text;
    }
}
