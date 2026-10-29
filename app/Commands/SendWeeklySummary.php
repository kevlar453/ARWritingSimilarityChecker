<?php

namespace App\Commands;

use App\Libraries\EmailService;
use App\Models\UserModel;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class SendWeeklySummary extends BaseCommand
{
    /**
     * Grup CLI
     */
    protected $group = 'Emails';

    /**
     * Nama perintah CLI
     */
    protected $name = 'email:send-weekly-summary';

    /**
     * Deskripsi perintah CLI
     */
    protected $description = 'Mengirimkan email Ringkasan Mingguan (Weekly Summary & Academic Tips) ke seluruh pengguna aktif yang berlangganan.';

    /**
     * Definisi argumen dan opsi CLI
     */
    protected $arguments = [];
    protected $options = [
        '--user_id' => 'Kirim ringkasan hanya ke pengguna dengan ID tertentu (misal: --user_id=5)',
        '--preview' => 'Tampilkan pratinjau teks email di konsol tanpa benar-benar mengirim email (default: 1 pengguna pertama; gunakan --all untuk semua)',
        '--all'     => 'Saat mode pratinjau (--preview), tampilkan pratinjau untuk seluruh pengguna yang memenuhi syarat',
        '--force'   => 'Abaikan batas jeda 5 hari pengiriman terakhir (kirim paksa)',
    ];

    /**
     * Eksekusi Perintah
     */
    public function run(array $params)
    {
        try {
            $this->executeWeeklySummary($params);
        } catch (\Throwable $e) {
            $msg = $e->getMessage();
            if (stripos($msg, 'Broken pipe') !== false || stripos($msg, 'errno=32') !== false) {
                // Pipe downstream ditutup oleh pemanggil (misal: | head, | less, q). Berhenti dengan bersih tanpa peringatan.
                return;
            }
            throw $e;
        }
    }

    /**
     * Implementasi logika pengiriman ringkasan mingguan
     */
    protected function executeWeeklySummary(array $params): void
    {
        CLI::write("==================================================", 'yellow');
        CLI::write("    AR Tilik — Pengirim Email Ringkasan Mingguan  ", 'yellow');
        CLI::write("==================================================", 'yellow');

        $userModel = new UserModel();
        $emailService = new EmailService();
        $db = \Config\Database::connect();

        $targetUserId = CLI::getOption('user_id');
        $isPreview    = (bool) CLI::getOption('preview');
        $isForce      = (bool) CLI::getOption('force');
        $isAll        = (bool) CLI::getOption('all');

        // Dukungan format --user_id=123, --preview=1, --force=1 di CI4 CLI parser
        foreach (CLI::getOptions() as $optKey => $optVal) {
            if (str_starts_with($optKey, 'user_id=')) {
                $targetUserId = substr($optKey, strlen('user_id='));
            }
            if (str_starts_with($optKey, 'preview')) {
                $isPreview = true;
            }
            if (str_starts_with($optKey, 'force')) {
                $isForce = true;
            }
            if (str_starts_with($optKey, 'all')) {
                $isAll = true;
            }
        }

        $builder = $db->table('users');

        if ($targetUserId) {
            $builder->where('id', (int) $targetUserId);
        } else {
            $builder->where('status', 'active')
                    ->where('email_verified_at IS NOT NULL', null, false)
                    ->where('email_weekly_summary', 1);

            if (!$isForce) {
                $builder->groupStart()
                        ->where('last_weekly_summary_sent_at IS NULL', null, false)
                        ->orWhere('last_weekly_summary_sent_at <=', date('Y-m-d H:i:s', strtotime('-5 days')))
                        ->groupEnd();
            }
        }

        $users = $builder->get()->getResultArray();
        $totalUsers = count($users);

        if ($totalUsers === 0) {
            CLI::write("Tidak ada pengguna yang memenuhi syarat pengiriman ringkasan mingguan saat ini.", 'light_cyan');
            return;
        }

        CLI::write("Ditemukan {$totalUsers} pengguna target untuk diproses." . ($isPreview ? ' [MODE PRATINJAU / DRY-RUN]' : ''), 'cyan');

        if ($isPreview && empty($targetUserId) && !$isAll && $totalUsers > 1) {
            CLI::write("Mode pratinjau aktif: Menampilkan pratinjau untuk 1 pengguna pertama dari total {$totalUsers} pengguna.", 'light_cyan');
            CLI::write("Petunjuk: Gunakan opsi --user_id=<id> untuk pengguna tertentu atau tambahkan --all untuk menampilkan seluruhnya.\n", 'dark_gray');
            $users = array_slice($users, 0, 1);
            $totalUsers = 1;
        }

        // Rentang sepekan terakhir
        $startTs = strtotime('-7 days');
        $endTs   = time();
        $startDateTime = date('Y-m-d 00:00:00', $startTs);
        $endDateTime   = date('Y-m-d 23:59:59', $endTs);

        $tanggalMulai   = $this->formatTanggalIndo(date('Y-m-d', $startTs));
        $tanggalSelesai = $this->formatTanggalIndo(date('Y-m-d', $endTs));

        // Trik minggu ini (Rotasi mingguan)
        $triks = [
            1 => [
                'judul' => 'Periksa sitasi, bukan hanya kemiripan',
                'isi'   => 'AR Tilik memeriksa rujukan Anda ke basis data eksternal seperti OpenAlex dan Crossref. Kalau ada sitasi yang ditandai mencurigakan, klik sumbernya di laporan dan periksa apakah penulis, tahun, dan judulnya benar-benar ada. Satu sitasi fiktif yang lolos bisa lebih merugikan daripada beberapa kalimat yang mirip.'
            ],
            2 => [
                'judul' => 'Keluarkan daftar pustaka dari hitungan',
                'isi'   => 'Skor Anda bisa terlihat lebih tinggi karena daftar pustaka ikut terhitung sebagai teks yang sama dengan sumber lain. Aktifkan pengecualian bibliografi sebelum memeriksa, supaya skor mencerminkan isi naskah Anda, bukan daftar rujukannya.'
            ],
            3 => [
                'judul' => 'Bersihkan berkas tanpa kehilangan laporan',
                'isi'   => 'Kalau Anda sudah selesai dengan sebuah dokumen, gunakan "Musnahkan Berkas" untuk menghapus berkasnya dari server, sementara laporan, skor, sumber, dan sertifikatnya tetap aktif dan bisa diunduh. Berbeda dengan "Hapus Submisi", yang menghapus semuanya termasuk sertifikatnya. Perlu diingat, kuota yang sudah terpakai untuk proses pemeriksaan tidak kembali setelah dokumen dihapus, karena kuota terpotong saat ekstraksi dan analisis berjalan.'
            ],
        ];

        $currentWeek = (int) date('W');
        $trikIndex = ($currentWeek % 3) + 1;
        $activeTrik = $triks[$trikIndex];

        $successCount = 0;
        $failCount    = 0;

        foreach ($users as $index => $u) {
            $userId = (int) $u['id'];
            $uName  = $u['name'];
            $uEmail = $u['email'];

            CLI::write(sprintf("[%d/%d] Memproses user #%d: %s (%s)...", $index + 1, $totalUsers, $userId, $uName, $uEmail), 'white');

            // 1. Dokumen yang diperiksa dalam 7 hari terakhir
            $docsInWeek = $db->table('documents d')
                             ->select('d.id, d.title, d.created_at, r.overall_similarity, r.evidence_data')
                             ->join('reports r', 'r.document_id = d.id', 'inner')
                             ->where('d.user_id', $userId)
                             ->where('d.document_type', 'submission')
                             ->where('d.created_at >=', $startDateTime)
                             ->where('d.created_at <=', $endDateTime)
                             ->where('r.status', 'completed')
                             ->orderBy('d.id', 'DESC')
                             ->get()
                             ->getResultArray();

            $jumlahDokumen = count($docsInWeek);
            $totalSkor = 0.0;
            $sitasiTerverifikasi = 0;
            $sitasiMencurigakan = 0;

            foreach ($docsInWeek as $doc) {
                $totalSkor += (float) $doc['overall_similarity'];

                if (!empty($doc['evidence_data'])) {
                    $evData = json_decode($doc['evidence_data'], true);
                    if (!empty($evData['citation_audit'])) {
                        $cAudit = $evData['citation_audit'];
                        $totalFound = (int) ($cAudit['total_citations_found'] ?? 0);
                        $phantoms   = (int) ($cAudit['phantom_citations_count'] ?? 0);
                        $sitasiTerverifikasi += max(0, $totalFound - $phantoms);
                        $sitasiMencurigakan  += $phantoms;
                    }
                }
            }

            $rataRataSkor = $jumlahDokumen > 0 ? ($totalSkor / $jumlahDokumen) : 0.0;

            // 2. Kuota pengguna
            $quotaInfo = $userModel->getQuotaInfo($u);
            $kuotaTerpakai = (int) $quotaInfo['free_used'];
            $kuotaTotal    = (int) ($quotaInfo['free_limit'] + $quotaInfo['bonus_submissions']);
            $sisaKuotaText = !empty($quotaInfo['is_subscribed']) && $quotaInfo['subscription_tier'] === 'dosen_pro'
                ? 'Tanpa Batas (Dosen Pro)'
                : "{$quotaInfo['total_available']} dokumen";

            // 3. Dokumen terakhir pengguna (bisa dari minggu ini atau sebelumnya)
            $lastDocRow = $db->table('documents d')
                             ->select('d.title, d.created_at, r.overall_similarity')
                             ->join('reports r', 'r.document_id = d.id', 'inner')
                             ->where('d.user_id', $userId)
                             ->where('d.document_type', 'submission')
                             ->where('r.status', 'completed')
                             ->orderBy('d.id', 'DESC')
                             ->limit(1)
                             ->get()
                             ->getRowArray();

            $dokumenTerakhir = null;
            if ($lastDocRow) {
                $dokumenTerakhir = [
                    'title' => $lastDocRow['title'],
                    'date'  => $this->formatTanggalIndo(date('Y-m-d', strtotime($lastDocRow['created_at']))),
                    'score' => (float) $lastDocRow['overall_similarity'],
                ];
            }

            $summaryData = [
                'tanggal_mulai'        => $tanggalMulai,
                'tanggal_selesai'      => $tanggalSelesai,
                'jumlah_dokumen'       => $jumlahDokumen,
                'rata_rata_skor'       => $rataRataSkor,
                'sitasi_terverifikasi' => $sitasiTerverifikasi,
                'sitasi_mencurigakan'  => $sitasiMencurigakan,
                'kuota_terpakai'       => $kuotaTerpakai,
                'kuota_total'          => $kuotaTotal,
                'sisa_kuota'           => $sisaKuotaText,
                'dokumen_terakhir'     => $dokumenTerakhir,
                'judul_trik'           => $activeTrik['judul'],
                'isi_trik'             => $activeTrik['isi'],
            ];

            if ($isPreview) {
                CLI::write("------------------- [PREVIEW EMAIL] -------------------", 'yellow');
                CLI::write("Kepada: {$uEmail} ({$uName})", 'green');
                $unsubToken = !empty($u['unsubscribe_token']) ? $u['unsubscribe_token'] : 'preview-token';
                $unsubUrl = base_url("email/unsubscribe?token={$unsubToken}&type=weekly");
                
                $reflector = new \ReflectionClass($emailService);
                $method = $reflector->getMethod('buildWeeklySummaryAltText');
                $method->setAccessible(true);
                $altText = $method->invoke($emailService, $u, $summaryData, $unsubUrl);
                
                CLI::write($altText, 'light_gray');
                CLI::write("-------------------------------------------------------", 'yellow');
                $successCount++;
                continue;
            }

            // Kirim email
            $sent = $emailService->sendWeeklySummaryEmail($u, $summaryData);
            if ($sent) {
                CLI::write("  [✓] Sukses terkirim ke {$uEmail}", 'green');
                $successCount++;
            } else {
                CLI::error("  [✗] Gagal mengirim ke {$uEmail}");
                $failCount++;
            }

            // Jeda singkat antar pengiriman untuk kelancaran SMTP
            usleep(250000); // 250ms
        }

        CLI::newLine();
        CLI::write("Selesai memproses ringkasan mingguan!", 'green');
        CLI::write("Sukses: {$successCount} | Gagal: {$failCount}", $failCount > 0 ? 'red' : 'green');
    }

    /**
     * Memformat tanggal ke format Indonesia yang rapi
     */
    protected function formatTanggalIndo(string $dateYmd): string
    {
        $bulanIndo = [
            1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
            'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
        ];

        $time = strtotime($dateYmd);
        $d = (int) date('j', $time);
        $m = (int) date('n', $time);
        $y = date('Y', $time);

        return "{$d} " . ($bulanIndo[$m] ?? date('M', $time)) . " {$y}";
    }
}
