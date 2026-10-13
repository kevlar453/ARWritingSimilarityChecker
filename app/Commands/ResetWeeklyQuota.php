<?php

namespace App\Commands;

use App\Models\UserModel;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class ResetWeeklyQuota extends BaseCommand
{
    protected $group       = 'Quota';
    protected $name        = 'quota:reset-weekly';
    protected $description = 'Reset kuota gratis mingguan seluruh pengguna AR Tilik ke 0 setiap hari Senin 00:00 WIB';
    protected $usage       = 'quota:reset-weekly [options]';
    protected $arguments   = [];
    protected $options     = [
        '--force'   => 'Paksa eksekusi reset kuota tanpa memverifikasi apakah hari ini hari Senin',
        '--dry-run' => 'Simulasi perhitungan pengguna yang akan direset tanpa mengubah database',
    ];

    public function run(array $params)
    {
        date_default_timezone_set('Asia/Jakarta');
        $now = time();
        $isMonday = ((int) date('N', $now) === 1); // 1 = Senin
        $force = CLI::getOption('force') !== null;
        $dryRun = CLI::getOption('dry-run') !== null;

        CLI::write("==================================================", 'green');
        CLI::write("=== AR Tilik Weekly Quota Reset Engine (Senin) ===", 'green');
        CLI::write("==================================================", 'green');
        CLI::write("Waktu Eksekusi: " . date('Y-m-d H:i:s', $now) . " WIB", 'cyan');
        CLI::write("Hari: " . date('l', $now) . ($isMonday ? ' (Hari Reset Resmi)' : ' (Bukan Hari Senin)'), 'cyan');

        if (! $isMonday && ! $force && ! $dryRun) {
            CLI::write("[!] Hari ini bukan hari Senin (" . date('l', $now) . "). Reset mingguan otomatis ditangguhkan.", 'yellow');
            CLI::write("[i] Gunakan flag --force jika ingin mengeksekusi reset kuota di luar hari Senin.", 'white');
            return;
        }

        $userModel = new UserModel();
        $nextReset = date('Y-m-d 00:00:00', strtotime('next monday', $now));
        $nowStr = date('Y-m-d H:i:s', $now);

        // Hitung pengguna yang memenuhi kriteria reset
        $queryBuilder = $userModel;
        if ($force) {
            $usersToReset = $queryBuilder
                ->groupStart()
                    ->where('free_submissions_used_this_week >', 0)
                    ->orWhere('week_reset_at !=', $nextReset)
                    ->orWhere('week_reset_at IS NULL', null, false)
                ->groupEnd()
                ->countAllResults();
        } else {
            $usersToReset = $queryBuilder
                ->groupStart()
                    ->where('week_reset_at <=', $nowStr)
                    ->orWhere('week_reset_at IS NULL', null, false)
                ->groupEnd()
                ->countAllResults();
        }

        $totalUsers = $userModel->countAllResults();

        CLI::write("Total akun pengguna terdaftar : {$totalUsers}", 'white');
        CLI::write("Akun yang kuotanya akan direset : {$usersToReset}", 'yellow');

        if ($dryRun) {
            CLI::write("[i] Mode dry-run aktif. Tidak ada perubahan yang disimpan ke database.", 'yellow');
            CLI::write("[i] Jadwal reset berikutnya yang akan ditetapkan: {$nextReset} WIB", 'cyan');
            return;
        }

        CLI::write("[*] Menjalankan pembaruan kuota gratis serentak ke 0/3...", 'cyan');

        $affected = $userModel->resetAllWeeklyQuotas($force);

        CLI::write("[✓] Berhasil mereset kuota mingguan untuk {$affected} akun pengguna.", 'green');
        CLI::write("[✓] Waktu reset berikutnya ditetapkan pada: {$nextReset} WIB (Senin depan).", 'green');
    }
}
