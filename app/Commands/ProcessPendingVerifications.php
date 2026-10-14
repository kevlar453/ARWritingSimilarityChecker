<?php

namespace App\Commands;

use App\Libraries\EmailService;
use App\Models\UserModel;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class ProcessPendingVerifications extends BaseCommand
{
    protected $group       = 'Auth';
    protected $name        = 'auth:process-pending-verifications';
    protected $description = 'Proses otomatis pengingat email verifikasi pada jam yang sama pendaftar mendaftar, dan hapus akun jika sudah 3x terkirim namun tetap tidak diverifikasi.';
    protected $usage       = 'auth:process-pending-verifications [options]';
    protected $arguments   = [];
    protected $options     = [
        '--force-hour' => 'Paksa proses seluruh pendaftar yang jatuh tempo tanpa membatasi pada jam pendaftaran',
        '--dry-run'    => 'Simulasi audit tanpa mengirim email atau menghapus akun dari database',
    ];

    public function run(array $params)
    {
        date_default_timezone_set('Asia/Jakarta');
        $now = time();
        $nowStr = date('Y-m-d H:i:s', $now);
        $currentHour = (int) date('G', $now); // 0 sampai 23

        $forceHour = CLI::getOption('force-hour') !== null;
        $dryRun = CLI::getOption('dry-run') !== null;

        CLI::write("================================================================", 'green');
        CLI::write("=== AR Tilik Automated Pending Verifications & Purge Engine ===", 'green');
        CLI::write("================================================================", 'green');
        CLI::write("Waktu Eksekusi : " . date('Y-m-d H:i:s', $now) . " WIB (Jam: {$currentHour}:00 - {$currentHour}:59)", 'cyan');
        CLI::write("Mode Operasi   : " . ($dryRun ? 'DRY-RUN (Simulasi Saja)' : 'LIVE EXECUTION'), $dryRun ? 'yellow' : 'green');
        CLI::write("Filter Jam     : " . ($forceHour ? 'SEMUA JAM (Bypass --force-hour)' : "JAM KE-{$currentHour} SESUAI WAKTU DAFTAR"), 'cyan');

        $userModel = new UserModel();
        $emailService = new EmailService();

        // Ambil semua pengguna yang statusnya pending_verification
        $pendingUsers = $userModel->where('status', 'pending_verification')
                                  ->orderBy('id', 'ASC')
                                  ->findAll();

        $totalPending = count($pendingUsers);
        CLI::write("Total Pengguna Pending Verifikasi Saat Ini : {$totalPending}", 'white');

        if ($totalPending === 0) {
            CLI::write("[✓] Tidak ada pendaftar yang pending verifikasi. Sistem bersih.", 'green');
            return;
        }

        $sentCount = 0;
        $purgedCount = 0;
        $skippedCount = 0;

        foreach ($pendingUsers as $user) {
            $userId = (int) $user['id'];
            $email = $user['email'];
            $createdAt = $user['created_at'];
            $regHour = (int) date('G', strtotime($createdAt));
            $emailsSent = (int) ($user['verification_emails_sent'] ?? 1);

            $lastSentAt = !empty($user['last_verification_email_sent_at']) 
                ? $user['last_verification_email_sent_at'] 
                : $createdAt;
            $lastSentTimestamp = strtotime($lastSentAt);

            // 1. Pemeriksaan Kecocokan Jam Pendaftaran
            $isSameHour = ($regHour === $currentHour);
            if (! $isSameHour && ! $forceHour) {
                $skippedCount++;
                continue;
            }

            // 2. Pemeriksaan Waktu: Harus lebih dari 1 hari (H+1 atau lebih) sejak email terakhir
            $secondsSinceLast = $now - $lastSentTimestamp;
            $daysDiff = (int) ((strtotime(date('Y-m-d', $now)) - strtotime(date('Y-m-d', $lastSentTimestamp))) / 86400);

            // Setidaknya 1 hari kalender telah berlalu atau minimal 20 jam
            $isOverOneDay = ($daysDiff >= 1 || $secondsSinceLast >= (20 * 3600));

            if (! $isOverOneDay && ! $forceHour) {
                // Belum genap 1 hari sejak email terakhir
                $skippedCount++;
                continue;
            }

            // 3. Cabang Keputusan Berdasarkan Jumlah Email yang Pernah Terkirim
            if ($emailsSent < 3) {
                $nextNumber = $emailsSent + 1;
                $label = ($nextNumber === 3) ? 'Peringatan Terakhir (3/3)' : 'Pengingat (2/3)';

                CLI::write("----------------------------------------------------------------", 'white');
                CLI::write("[*] Calon Penerima Pengingat: #{$userId} - {$email}", 'yellow');
                CLI::write("    Terdaftar       : {$createdAt} (Jam {$regHour}:xx WIB)", 'white');
                CLI::write("    Email Terakhir  : {$lastSentAt} ({$daysDiff} hari lalu)", 'white');
                CLI::write("    Target Pengiriman: Email Ke-{$nextNumber} ({$label})", 'cyan');

                if ($dryRun) {
                    CLI::write("    -> [DRY-RUN] Email pengingat ke-{$nextNumber} disimulasikan terkirim.", 'yellow');
                    $sentCount++;
                    continue;
                }

                // Buat token verifikasi baru berlaku 24 jam ke depan
                $newToken = bin2hex(random_bytes(32));
                $newTokenExpiresAt = date('Y-m-d H:i:s', strtotime('+24 hours', $now));

                $sent = $emailService->sendVerificationEmail($user, $newToken, $nextNumber);

                if ($sent) {
                    $userModel->update($userId, [
                        'verification_token'              => $newToken,
                        'verification_token_expires_at'   => $newTokenExpiresAt,
                        'verification_emails_sent'        => $nextNumber,
                        'last_verification_email_sent_at' => $nowStr,
                        'updated_at'                      => $nowStr,
                    ]);
                    CLI::write("    -> [✓ SENT] Berhasil mengirimkan Email Pengingat ke-{$nextNumber} ke {$email}.", 'green');
                    log_message('info', "[AUTH] Berhasil mengirimkan email pengingat verifikasi ke-{$nextNumber} ke {$email} (User ID: {$userId}).");
                    $sentCount++;
                } else {
                    CLI::write("    -> [✗ ERROR] Gagal mengirimkan email ke {$email}. Akan dicoba kembali pada jadwal berikutnya.", 'red');
                    log_message('error', "[AUTH] Gagal mengirimkan email pengingat verifikasi ke {$email} (User ID: {$userId}).");
                }

            } else {
                // SUDAH 3x TERKIRIM TETAPI TIDAK DIVERIFIKASI -> CEK GRACE PERIOD SEBELUM PEMUSNAHAN
                // Berikan masa tenggang (grace period) minimal 48 jam (2 hari) sejak email peringatan ke-3 terkirim
                $gracePeriodSeconds = 48 * 3600; // 48 jam masa tenggang
                $timeSinceLastEmail = $now - $lastSentTimestamp;

                if ($timeSinceLastEmail < $gracePeriodSeconds && ! $forceHour) {
                    $hoursRemaining = (int) ceil(($gracePeriodSeconds - $timeSinceLastEmail) / 3600);
                    CLI::write("----------------------------------------------------------------", 'white');
                    CLI::write("[⏳ GRACE PERIOD] Akun dalam Masa Tenggang: #{$userId} - {$email}", 'yellow');
                    CLI::write("    Email Terkirim  : {$emailsSent} kali (Peringatan terakhir)", 'white');
                    CLI::write("    Email Terakhir  : {$lastSentAt}", 'white');
                    CLI::write("    Sisa Masa Tenggang: ~{$hoursRemaining} jam sebelum dibersihkan.", 'cyan');
                    $skippedCount++;
                    continue;
                }

                CLI::write("----------------------------------------------------------------", 'white');
                CLI::write("[!] Target Pemusnahan Data (Pasca Grace Period): #{$userId} - {$email}", 'red');
                CLI::write("    Terdaftar       : {$createdAt} (Jam {$regHour}:xx WIB)", 'white');
                CLI::write("    Email Terkirim  : {$emailsSent} kali (Maksimum 3x + Grace Period 48 Jam telah terlampaui)", 'white');
                CLI::write("    Status          : Tetap tidak diverifikasi setelah seluruh masa tenggang.", 'white');

                if ($dryRun) {
                    CLI::write("    -> [DRY-RUN] Akun pendaftar disimulasikan dihapus secara permanen.", 'yellow');
                    $purgedCount++;
                    continue;
                }

                $deleted = $userModel->purgeUnverifiedUser($userId);

                if ($deleted) {
                    CLI::write("    -> [🗑️ PURGED] Data akun pendaftar {$email} (ID: {$userId}) berhasil dimusnahkan secara permanen.", 'red');
                    log_message('info', "[AUTH PURGE] Akun pendaftar {$email} (ID: {$userId}) telah dimusnahkan otomatis karena tidak diverifikasi setelah 3 kali pengiriman pengingat.");
                    $purgedCount++;
                } else {
                    CLI::write("    -> [✗ ERROR] Gagal menghapus akun #{$userId}.", 'red');
                }
            }
        }

        CLI::write("================================================================", 'green');
        CLI::write("=== Ringkasan Eksekusi ===", 'green');
        CLI::write("Pengingat Terkirim    : {$sentCount}", 'cyan');
        CLI::write("Akun Dimusnahkan      : {$purgedCount}", 'yellow');
        CLI::write("Dilewati (Beda Jam/H) : {$skippedCount}", 'white');
        CLI::write("================================================================", 'green');
    }
}
