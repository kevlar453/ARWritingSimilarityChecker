<?php

namespace App\Models;

use CodeIgniter\Model;

class AmbassadorProfileModel extends Model
{
    protected $table            = 'ambassador_profiles';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'application_id',
        'user_id',
        'referral_code',
        'status',
        'certificate_code',
        'certificate_hash',
        'graduated_at',
        'probation_start',
        'probation_end',
        'probation_target_promos',
        'probation_target_transactions',
        'commission_rate_percent',
        'payout_channel',
        'payout_account_number',
        'payout_account_name',
        'welcome_sent_at',
        'created_at',
        'updated_at',
    ];

    /**
     * Mengaktivasi Duta Kampus & menetapkan kode referral unik (Tahap 4)
     */
    public function activateAmbassador(int $applicationId, string $referralCode, array $config = []): array
    {
        $db = \Config\Database::connect();
        $appModel = new AmbassadorApplicationModel();
        $app = $appModel->find($applicationId);

        if (!$app) {
            return ['success' => false, 'message' => 'Berkas pendaftaran tidak ditemukan.'];
        }

        $userId = (int) $app['user_id'];
        if (!$userId) {
            // Jika user belum memiliki ID di aplikasi, cari berdasarkan email
            $u = $db->table('users')->where('email', $app['email'])->get()->getRowArray();
            if ($u) {
                $userId = (int) $u['id'];
                $appModel->update($applicationId, ['user_id' => $userId]);
            } else {
                return ['success' => false, 'message' => 'Akun pengguna AR Tilik dengan email tersebut belum terdaftar di sistem.'];
            }
        }

        // Pastikan kode referral bersih & uppercase
        $code = strtoupper(preg_replace('/[^A-Z0-9\-]/', '', trim($referralCode)));
        if (empty($code)) {
            $code = 'TILIK-' . strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', $app['institution']), 0, 4)) . '-' . rand(100, 999);
        }

        // Cek duplikasi kode
        $existingCode = $this->where('referral_code', $code)->first();
        if ($existingCode && (int)$existingCode['user_id'] !== $userId) {
            $code .= '-' . rand(10, 99);
        }

        $now = date('Y-m-d H:i:s');
        $probationStart = date('Y-m-d');
        $probationEnd = date('Y-m-d', strtotime('+30 days'));

        $existingProfile = $this->where('user_id', $userId)->first();
        $dataProfile = [
            'application_id'                => $applicationId,
            'user_id'                       => $userId,
            'referral_code'                 => $code,
            'status'                        => 'probation',
            'probation_start'               => $probationStart,
            'probation_end'                 => $probationEnd,
            'probation_target_promos'       => $config['target_promos'] ?? 8,
            'probation_target_transactions' => $config['target_transactions'] ?? 3,
            'commission_rate_percent'       => $config['commission_rate'] ?? 20.00,
            'welcome_sent_at'               => $now,
            'updated_at'                    => $now,
        ];

        if ($existingProfile) {
            $this->update($existingProfile['id'], $dataProfile);
            $profileId = $existingProfile['id'];
        } else {
            $dataProfile['created_at'] = $now;
            $profileId = $this->insert($dataProfile);
        }

        // Perbarui status pendaftaran menjadi 'active'
        $appModel->update($applicationId, [
            'status'     => 'active',
            'updated_at' => $now,
        ]);

        // Aktifkan akun premium pengguna selama masa probation (30 hari)
        $db->table('users')->where('id', $userId)->update([
            'subscription_tier'       => 'skripsi',
            'subscription_expires_at' => date('Y-m-d 23:59:59', strtotime('+30 days')),
            'updated_at'              => $now,
        ]);

        return [
            'success'       => true,
            'profile_id'    => $profileId,
            'referral_code' => $code,
            'referral_url'  => base_url('ref/' . $code),
            'message'       => 'Duta Kampus berhasil diaktivasi! Kode referral dan paket premium telah aktif.',
        ];
    }

    /**
     * Ambil data profil dan performa duta berdasarkan user_id
     */
    public function getProfileWithStats(int $userId): ?array
    {
        $db = \Config\Database::connect();
        $profile = $this->where('user_id', $userId)->first();
        if (!$profile) {
            return null;
        }

        $code = $profile['referral_code'];

        // Hitung total teman yang mendaftar menggunakan referral
        $totalRegistered = $db->table('users')->where('referred_by_code', $code)->countAllResults();

        // Hitung total transaksi dari teman yang mendaftar
        $totalPaidTransactions = 0;
        $totalTurnover = 0.0;
        if ($totalRegistered > 0) {
            $referredUserIds = $db->table('users')->select('id')->where('referred_by_code', $code)->get()->getResultArray();
            $ids = array_column($referredUserIds, 'id');
            if (!empty($ids)) {
                $txRows = $db->table('payment_transactions')
                    ->select('id, amount')
                    ->whereIn('user_id', $ids)
                    ->whereIn('transaction_status', ['settlement', 'capture'])
                    ->get()
                    ->getResultArray();

                $totalPaidTransactions = count($txRows);
                foreach ($txRows as $tx) {
                    $totalTurnover += (float) $tx['amount'];
                }
            }
        }

        // Hitung total bukti promosi bulan ini & bukti yang disetujui
        $currentMonth = date('Y-m');
        $totalPromosThisMonth = $db->table('ambassador_activity_proofs')
            ->where('ambassador_id', $profile['id'])
            ->where('month_period', $currentMonth)
            ->countAllResults();

        $approvedPromosTotal = $db->table('ambassador_activity_proofs')
            ->where('ambassador_id', $profile['id'])
            ->where('status', 'approved')
            ->countAllResults();

        // Hitung estimasi komisi
        $commissionRate = (float) $profile['commission_rate_percent'];
        $estimatedCommission = ($totalTurnover * $commissionRate) / 100.0;

        $targetPromos = (int) ($profile['probation_target_promos'] ?? 8);
        $targetTransactions = (int) ($profile['probation_target_transactions'] ?? 3);
        $isGraduated = ($profile['status'] === 'graduated') || !empty($profile['certificate_code']);
        $isEligible = ($approvedPromosTotal >= $targetPromos) || ($totalPaidTransactions >= $targetTransactions);

        $profile['total_registered']         = $totalRegistered;
        $profile['total_transactions']       = $totalPaidTransactions;
        $profile['total_turnover']           = $totalTurnover;
        $profile['promos_this_month']        = $totalPromosThisMonth;
        $profile['approved_promos_total']    = $approvedPromosTotal;
        $profile['estimated_commission']     = $estimatedCommission;
        $profile['referral_url']             = base_url('ref/' . $code);
        $profile['is_graduated']             = $isGraduated;
        $profile['is_eligible_graduation']   = $isEligible;
        $profile['certificate_url']         = !empty($profile['certificate_code']) ? base_url('ambassador/certificate/' . $profile['certificate_code']) : null;
        $profile['certificate_pdf_url']     = !empty($profile['certificate_code']) ? base_url('ambassador/certificate/' . $profile['certificate_code'] . '/pdf') : null;

        return $profile;
    }

    /**
     * Meluluskan Duta Kampus & Menerbitkan Piagam Digital Resmi (Kelulusan Tahap 5 ke 6)
     */
    public function graduate(int $profileId, ?int $adminId = null, string $adminNotes = ''): array
    {
        $db = \Config\Database::connect();
        $profile = $this->find($profileId);
        if (!$profile) {
            return ['success' => false, 'message' => 'Profil Duta Kampus tidak ditemukan.'];
        }

        $now = date('Y-m-d H:i:s');
        $code = $profile['certificate_code'];
        if (empty($code)) {
            // Format kode piagam resmi: ART-AMB-XXXX-XXXX
            $randomHex = strtoupper(bin2hex(random_bytes(4)));
            $code = 'ART-AMB-' . substr($randomHex, 0, 4) . '-' . substr($randomHex, 4, 4);
        }

        // Hitung SHA-256 Hash Keaslian Kriptografis
        $hashData = "ARTILIK_AMBASSADOR|{$profileId}|{$profile['user_id']}|{$code}|{$now}";
        $certHash = hash('sha256', $hashData);

        $this->update($profileId, [
            'status'           => 'graduated',
            'certificate_code' => $code,
            'certificate_hash' => $certHash,
            'graduated_at'     => $now,
            'updated_at'       => $now,
        ]);

        // Perpanjang langganan premium Duta Aktif (1 tahun penuh)
        $db->table('users')->where('id', $profile['user_id'])->update([
            'subscription_tier'       => 'skripsi',
            'subscription_expires_at' => date('Y-m-d 23:59:59', strtotime('+365 days')),
            'updated_at'              => $now,
        ]);

        // Ambil data untuk notifikasi
        $user = $db->table('users')->where('id', $profile['user_id'])->get()->getRowArray();
        $app = $db->table('ambassador_applications')->where('id', $profile['application_id'])->get()->getRowArray();
        $ambName = $app['full_name'] ?? ($user['name'] ?? 'Duta Kampus');
        $ambCampus = $app['institution'] ?? ($user['institution'] ?? 'Perguruan Tinggi');

        // Kirim Notifikasi ke Telegram
        try {
            $telegram = new \App\Libraries\TelegramNotifier(\App\Libraries\TelegramNotifier::getActivityChatId());
            $certUrl = base_url('ambassador/certificate/' . $code);
            $msg = "🎓 <b>KELULUSAN RESMI DUTA KAMPUS AR TILIK</b>\n\n"
                 . "Selamat kepada:\n"
                 . "👤 <b>{$ambName}</b>\n"
                 . "🏛️ <b>{$ambCampus}</b>\n"
                 . "🏷️ Kode Referral: <code>{$profile['referral_code']}</code>\n"
                 . "📜 No. Piagam: <code>{$code}</code>\n"
                 . "🕒 Waktu Lulus: {$now} WIB\n\n"
                 . "🔗 <a href=\"{$certUrl}\">Lihat Piagam Penghargaan Digital</a>";
            $telegram->sendMessage($msg);
        } catch (\Throwable $e) {
            log_message('warning', 'Gagal kirim notifikasi kelulusan duta ke Telegram: ' . $e->getMessage());
        }

        return [
            'success'          => true,
            'message'          => "Duta Kampus {$ambName} berhasil diluluskan dan Piagam Digital resmi telah diterbitkan!",
            'certificate_code' => $code,
            'certificate_hash' => $certHash,
            'certificate_url'  => base_url('ambassador/certificate/' . $code),
            'graduated_at'     => $now,
        ];
    }

    /**
     * Mengambil profil duta lengkap berdasarkan kode sertifikat publik
     */
    public function getByCertificateCode(string $code): ?array
    {
        $db = \Config\Database::connect();
        $profile = $this->where('certificate_code', $code)->first();
        if (!$profile) {
            return null;
        }

        $app = $db->table('ambassador_applications')->where('id', $profile['application_id'])->get()->getRowArray();
        $user = $db->table('users')->where('id', $profile['user_id'])->get()->getRowArray();

        $approvedPromosTotal = $db->table('ambassador_activity_proofs')
            ->where('ambassador_id', $profile['id'])
            ->where('status', 'approved')
            ->countAllResults();

        $totalRegistered = $db->table('users')->where('referred_by_code', $profile['referral_code'])->countAllResults();

        return [
            'profile'               => $profile,
            'application'           => $app,
            'user'                  => $user,
            'ambassador_name'       => $app['full_name'] ?? ($user['name'] ?? 'Duta Kampus'),
            'institution'           => $app['institution'] ?? ($user['institution'] ?? 'Perguruan Tinggi'),
            'major'                 => $app['major'] ?? 'Akademik',
            'semester'              => $app['semester'] ?? '-',
            'referral_code'         => $profile['referral_code'],
            'certificate_code'      => $profile['certificate_code'],
            'certificate_hash'      => $profile['certificate_hash'],
            'graduated_at'          => $profile['graduated_at'] ?? $profile['updated_at'],
            'approved_promos_total' => $approvedPromosTotal,
            'total_registered'      => $totalRegistered,
        ];
    }
}

