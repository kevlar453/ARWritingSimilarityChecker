<?php

namespace App\Commands;

use App\Libraries\EmailService;
use App\Models\UserModel;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class SendQuotaReminder extends BaseCommand
{
    /**
     * Grup CLI
     */
    protected $group = 'Emails';

    /**
     * Nama perintah CLI
     */
    protected $name = 'email:send-quota-reminder';

    /**
     * Deskripsi perintah CLI
     */
    protected $description = 'Mengirimkan surel pengingat saat kuota pemeriksaan pengguna habis di tengah pekan (0 submisi tersisa).';

    /**
     * Definisi argumen dan opsi CLI
     */
    protected $arguments = [];
    protected $options = [
        '--user_id' => 'Kirim atau pratinjau pengingat untuk ID pengguna tertentu (misal: --user_id=5)',
        '--to'      => 'Kirim salinan surel ke alamat email tujuan tertentu (misal: --to=admin@domain.com)',
        '--preview' => 'Tampilkan pratinjau teks surel di konsol tanpa mengirim surel',
        '--force'   => 'Kirim paksa dengan mengabaikan status pengiriman sebelumnya (last_quota_reminder_sent_at)',
    ];

    /**
     * Eksekusi Perintah
     */
    public function run(array $params)
    {
        date_default_timezone_set('Asia/Jakarta');

        CLI::write("==================================================", 'yellow');
        CLI::write("   AR Tilik — Pengirim Pengingat Kuota Habis      ", 'yellow');
        CLI::write("==================================================", 'yellow');

        $userModel    = new UserModel();
        $emailService = new EmailService();
        $db           = \Config\Database::connect();

        $targetUserId = CLI::getOption('user_id');
        $overrideTo   = CLI::getOption('to');
        $isPreview    = (bool) CLI::getOption('preview');
        $isForce      = (bool) CLI::getOption('force');

        foreach (CLI::getOptions() as $optKey => $optVal) {
            if (str_starts_with($optKey, 'user_id=')) {
                $targetUserId = substr($optKey, strlen('user_id='));
            }
            if (str_starts_with($optKey, 'to=')) {
                $overrideTo = substr($optKey, strlen('to='));
            }
            if (str_starts_with($optKey, 'preview')) {
                $isPreview = true;
            }
            if (str_starts_with($optKey, 'force')) {
                $isForce = true;
            }
        }

        $nowStr = date('Y-m-d H:i:s');
        $builder = $db->table('users');

        if ($targetUserId) {
            $builder->where('id', (int) $targetUserId);
            if (! $isForce && ! $isPreview) {
                $builder->where('status', 'active')
                        ->where('email_verified_at IS NOT NULL', null, false)
                        ->where('email_weekly_summary', 1)
                        ->where('free_submissions_used_this_week >=', 3)
                        ->where('bonus_submissions', 0)
                        ->where('last_quota_reminder_sent_at IS NULL', null, false);
            }
        } else {
            $builder->where('status', 'active')
                    ->where('email_verified_at IS NOT NULL', null, false)
                    ->where('email_weekly_summary', 1)
                    ->where('free_submissions_used_this_week >=', 3)
                    ->where('bonus_submissions', 0)
                    ->where('week_reset_at >', $nowStr);

            if (! $isForce) {
                $builder->where('last_quota_reminder_sent_at IS NULL', null, false);
            }
        }

        $users = $builder->get()->getResultArray();
        $totalUsers = count($users);

        if ($totalUsers === 0) {
            CLI::write("Tidak ada pengguna dengan kuota habis yang memerlukan pengiriman pengingat saat ini.", 'light_cyan');
            return;
        }

        CLI::write("Ditemukan {$totalUsers} pengguna target untuk diproses." . ($isPreview ? ' [MODE PRATINJAU / DRY-RUN]' : ''), 'cyan');

        $successCount = 0;
        $failCount    = 0;
        $skippedCount = 0;

        foreach ($users as $index => $u) {
            $userId = (int) $u['id'];
            $uName  = $u['name'];
            $uEmail = $u['email'];

            // Segarkan informasi kuota terlebih dahulu (memastikan lazy reset dijalankan bila sudah lewat Senin 00:00)
            $quotaInfo = $userModel->getQuotaInfo($u);

            // Lewati jika pengguna berlangganan paket tanpa batas (Dosen Pro aktif) kecuali mode preview/force spesifik
            if (!empty($quotaInfo['is_subscribed']) && ($quotaInfo['subscription_tier'] ?? '') === 'dosen_pro' && ! $isPreview && ! $isForce) {
                CLI::write("  [-] Dilewati user #{$userId} ({$uEmail}): Pelanggan aktif Dosen Pro (Tanpa Batas).", 'light_gray');
                $skippedCount++;
                continue;
            }

            // Pastikan setelah refresh kuota memang masih 0 (kecuali mode preview/force untuk uji coba)
            if (($quotaInfo['total_available'] ?? 0) > 0 && ! $isPreview && ! $isForce) {
                CLI::write("  [-] Dilewati user #{$userId} ({$uEmail}): Kuota tersedia ({$quotaInfo['total_available']} dokumen).", 'light_gray');
                $skippedCount++;
                continue;
            }

            CLI::write(sprintf("[%d/%d] Memproses user #%d: %s (%s)...", $index + 1, $totalUsers, $userId, $uName, $uEmail), 'white');

            if ($isPreview) {
                CLI::write("------------------- [PREVIEW EMAIL PENGINGAT KUOTA] -------------------", 'yellow');
                CLI::write("Kepada: {$uEmail} ({$uName})", 'green');
                $unsubToken = !empty($u['unsubscribe_token']) ? $u['unsubscribe_token'] : 'preview-token';
                $unsubUrl = base_url("email/unsubscribe?token={$unsubToken}&type=quota");

                $reflector = new \ReflectionClass($emailService);
                $method = $reflector->getMethod('buildQuotaExhaustedAltText');
                $method->setAccessible(true);
                $altText = $method->invoke($emailService, $u, $quotaInfo, $unsubUrl);

                CLI::write($altText, 'light_gray');
                CLI::write("-----------------------------------------------------------------------", 'yellow');
                $successCount++;
                continue;
            }

            $uTarget = $u;
            if (!empty($overrideTo)) {
                $uTarget['email'] = trim((string) $overrideTo);
                $uTarget['_is_test_copy'] = true;
            }
            $destEmail = $uTarget['email'];

            $sent = $emailService->sendQuotaExhaustedEmail($uTarget, $quotaInfo);
            if ($sent) {
                CLI::write("  [✓] Sukses terkirim ke {$destEmail}", 'green');
                $successCount++;
            } else {
                CLI::error("  [✗] Gagal mengirim ke {$destEmail}");
                $failCount++;
            }

            usleep(250000); // 250ms jeda antar pengiriman SMTP
        }

        CLI::newLine();
        CLI::write("Selesai memproses pengingat kuota habis!", 'green');
        CLI::write("Sukses: {$successCount} | Dilewati: {$skippedCount} | Gagal: {$failCount}", $failCount > 0 ? 'red' : 'green');
    }
}
