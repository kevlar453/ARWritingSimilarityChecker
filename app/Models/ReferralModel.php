<?php

namespace App\Models;

use CodeIgniter\Model;
use App\Libraries\TelegramNotifier;

class ReferralModel extends Model
{
    protected $table = 'referral_logs';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'referrer_user_id',
        'referred_user_id',
        'referral_code',
        'reward_type',
        'bonus_awarded',
        'notes',
        'created_at',
    ];
    protected $useTimestamps = false;

    /**
     * Dapatkan statistik program Member-Get-Member untuk pengguna
     */
    public function getReferralStats(int $userId): array
    {
        $userModel = new UserModel();
        $user = $userModel->find($userId);

        if (!$user) {
            return [
                'referral_code'           => '',
                'referral_url'            => '',
                'total_invited'           => 0,
                'total_verified'          => 0,
                'total_bonus_submissions' => 0,
                'invited_users'           => [],
            ];
        }

        $referralCode = $userModel->ensureReferralCode($userId);
        $referralUrl  = base_url('ref/' . $referralCode);

        $db = \Config\Database::connect();

        // 1. Ambil daftar teman yang mendaftar menggunakan referral_code ini
        $invitedRows = $db->table('users')
            ->select('id, name, email, status, email_verified_at, created_at')
            ->where('referred_by_code', $referralCode)
            ->orderBy('id', 'DESC')
            ->get()
            ->getResultArray();

        $totalInvited = count($invitedRows);
        $totalVerified = 0;
        $invitedUsers = [];

        // 2. Ambil catatan log reward untuk pengundang ini
        $rewards = $this->where('referrer_user_id', $userId)->findAll();
        $rewardMap = [];
        $totalBonusSubmissions = 0;

        foreach ($rewards as $r) {
            $key = $r['referred_user_id'] . '_' . $r['reward_type'];
            $rewardMap[$key] = (int) $r['bonus_awarded'];
            $totalBonusSubmissions += (int) $r['bonus_awarded'];
        }

        foreach ($invitedRows as $row) {
            $isVerified = (!empty($row['email_verified_at']) && $row['status'] === 'active');
            if ($isVerified) {
                $totalVerified++;
            }

            $bonusFromThisUser = ($rewardMap[$row['id'] . '_registration_verified'] ?? 0)
                               + ($rewardMap[$row['id'] . '_first_purchase'] ?? 0);

            $invitedUsers[] = [
                'id'            => (int) $row['id'],
                'name_masked'   => $this->maskName((string) $row['name']),
                'email_masked'  => $this->maskEmail((string) $row['email']),
                'status'        => $row['status'],
                'is_verified'   => $isVerified,
                'registered_at' => $row['created_at'] ? date('d M Y, H:i', strtotime($row['created_at'])) : '-',
                'bonus_earned'  => $bonusFromThisUser,
            ];
        }

        return [
            'referral_code'           => $referralCode,
            'referral_url'            => $referralUrl,
            'total_invited'           => $totalInvited,
            'total_verified'          => $totalVerified,
            'total_bonus_submissions' => $totalBonusSubmissions,
            'invited_users'           => $invitedUsers,
        ];
    }

    /**
     * Memeriksa apakah pengguna berhak mendapatkan diskon 10% Member-Get-Member pada checkout perdana
     */
    public function isEligibleForReferralDiscount(int $userId): bool
    {
        $db = \Config\Database::connect();
        $user = $db->table('users')->where('id', $userId)->get()->getRowArray();

        if (!$user || empty($user['referred_by_code'])) {
            return false;
        }

        $paidCount = $db->table('payment_transactions')
            ->where('user_id', $userId)
            ->whereIn('transaction_status', ['settlement', 'capture'])
            ->countAllResults();

        return ($paidCount === 0);
    }

    /**
     * Berikan bonus kuota +2 ke pengundang saat teman yang diundang mengaktifkan email
     */
    public function awardVerificationBonus(int $referredUserId): array
    {
        $db = \Config\Database::connect();
        $user = $db->table('users')->where('id', $referredUserId)->get()->getRowArray();

        if (!$user || empty($user['referred_by_code'])) {
            return ['awarded' => false, 'reason' => 'no_referrer_code'];
        }

        $code = strtoupper(trim((string) $user['referred_by_code']));

        // Cari pemilik kode di tabel users (Member reguler)
        $userModel = new UserModel();
        $referrer = $userModel->getByReferralCode($code);

        if (!$referrer) {
            // Mungkin ini referral milik Ambassador, yang dikelola oleh AmbassadorProfileModel
            return ['awarded' => false, 'reason' => 'referrer_not_regular_user'];
        }

        $referrerId = (int) $referrer['id'];

        // Proteksi self-referral
        if ($referrerId === (int) $referredUserId) {
            return ['awarded' => false, 'reason' => 'self_referral_prevented'];
        }

        // Cek apakah sudah pernah diberi bonus (idempotensi)
        $existing = $this->where('referrer_user_id', $referrerId)
            ->where('referred_user_id', $referredUserId)
            ->where('reward_type', 'registration_verified')
            ->first();

        if ($existing) {
            return ['awarded' => false, 'reason' => 'already_awarded'];
        }

        $now = date('Y-m-d H:i:s');
        $bonusAmount = 2; // +2 kuota cek gratis

        $db->transStart();

        // 1. Tambah bonus_submissions pengundang
        $db->query("
            UPDATE users 
            SET bonus_submissions = bonus_submissions + ?,
                updated_at = ?
            WHERE id = ?
        ", [$bonusAmount, $now, $referrerId]);

        // 2. Catat log transaksi reward
        $this->insert([
            'referrer_user_id' => $referrerId,
            'referred_user_id' => $referredUserId,
            'referral_code'    => $code,
            'reward_type'      => 'registration_verified',
            'bonus_awarded'    => $bonusAmount,
            'notes'            => 'Bonus aktivasi email sahabat (Member-Get-Member)',
            'created_at'       => $now,
        ]);

        $db->transComplete();

        if ($db->transStatus() === false) {
            return ['awarded' => false, 'reason' => 'db_transaction_failed'];
        }

        // Kirim notifikasi real-time via Telegram
        try {
            $telegram = new TelegramNotifier();
            $maskedFriend = $this->maskName((string) $user['name']);
            $msg = "🎁 <b>REWARD MEMBER-GET-MEMBER AR TILIK</b>\n\n"
                 . "• <b>Penerima Reward:</b> " . htmlspecialchars($referrer['name']) . " (" . htmlspecialchars($referrer['email']) . ")\n"
                 . "• <b>Sahabat Bergabung:</b> " . htmlspecialchars($maskedFriend) . "\n"
                 . "• <b>Kode Referral:</b> <code>{$code}</code>\n"
                 . "• <b>Bonus Diberikan:</b> +{$bonusAmount} Kuota Cek Naskah Gratis\n"
                 . "• <b>Status Akun Sahabat:</b> Terverifikasi Aktif\n"
                 . "• <b>Waktu:</b> {$now}";
            $telegram->sendMessage($msg);
        } catch (\Throwable $e) {
            log_message('error', 'Gagal kirim notif telegram referral reward: ' . $e->getMessage());
        }

        return [
            'awarded'  => true,
            'bonus'    => $bonusAmount,
            'referrer' => $referrer,
        ];
    }

    /**
     * Berikan bonus kuota +5 ke pengundang saat teman melakukan pembelian paket perdana
     */
    public function awardPurchaseBonus(int $transactionId): array
    {
        $db = \Config\Database::connect();
        $tx = $db->table('payment_transactions')->where('id', $transactionId)->get()->getRowArray();

        if (!$tx || !in_array($tx['transaction_status'], ['settlement', 'capture'], true)) {
            return ['awarded' => false, 'reason' => 'transaction_not_settled'];
        }

        $buyerId = (int) $tx['user_id'];
        $buyer = $db->table('users')->where('id', $buyerId)->get()->getRowArray();

        if (!$buyer || empty($buyer['referred_by_code'])) {
            return ['awarded' => false, 'reason' => 'no_referral_code'];
        }

        $code = strtoupper(trim((string) $buyer['referred_by_code']));

        // Cek apakah ini transaksi berbayar pertama pembeli
        $settledCount = $db->table('payment_transactions')
            ->where('user_id', $buyerId)
            ->whereIn('transaction_status', ['settlement', 'capture'])
            ->countAllResults();

        if ($settledCount > 1) {
            return ['awarded' => false, 'reason' => 'not_first_purchase'];
        }

        $userModel = new UserModel();
        $referrer = $userModel->getByReferralCode($code);

        if (!$referrer) {
            return ['awarded' => false, 'reason' => 'referrer_not_regular_user'];
        }

        $referrerId = (int) $referrer['id'];

        if ($referrerId === $buyerId) {
            return ['awarded' => false, 'reason' => 'self_referral_prevented'];
        }

        // Cek idempotensi reward pembelian
        $existing = $this->where('referrer_user_id', $referrerId)
            ->where('referred_user_id', $buyerId)
            ->where('reward_type', 'first_purchase')
            ->first();

        if ($existing) {
            return ['awarded' => false, 'reason' => 'already_awarded'];
        }

        $now = date('Y-m-d H:i:s');
        $bonusAmount = 5; // +5 kuota cek naskah gratis atas transaksi perdana sahabat

        $db->transStart();

        $db->query("
            UPDATE users 
            SET bonus_submissions = bonus_submissions + ?,
                updated_at = ?
            WHERE id = ?
        ", [$bonusAmount, $now, $referrerId]);

        $this->insert([
            'referrer_user_id' => $referrerId,
            'referred_user_id' => $buyerId,
            'referral_code'    => $code,
            'reward_type'      => 'first_purchase',
            'bonus_awarded'    => $bonusAmount,
            'notes'            => "Bonus transaksi perdana paket {$tx['package_name']} oleh sahabat",
            'created_at'       => $now,
        ]);

        $db->transComplete();

        if ($db->transStatus() === false) {
            return ['awarded' => false, 'reason' => 'db_transaction_failed'];
        }

        // Kirim notifikasi Telegram
        try {
            $telegram = new TelegramNotifier();
            $maskedBuyer = $this->maskName((string) $buyer['name']);
            $msg = "💎 <b>REWARD TRANSAKSI SAHABAT (MEMBER-GET-MEMBER)</b>\n\n"
                 . "• <b>Penerima Reward:</b> " . htmlspecialchars($referrer['name']) . "\n"
                 . "• <b>Sahabat:</b> " . htmlspecialchars($maskedBuyer) . "\n"
                 . "• <b>Paket Dibeli:</b> {$tx['package_name']} (Rp " . number_format($tx['amount'], 0, ',', '.') . ")\n"
                 . "• <b>Bonus Diberikan:</b> +{$bonusAmount} Kuota Cek Naskah Gratis\n"
                 . "• <b>Waktu:</b> {$now}";
            $telegram->sendMessage($msg);
        } catch (\Throwable $e) {
            log_message('error', 'Gagal kirim notif telegram purchase reward: ' . $e->getMessage());
        }

        return [
            'awarded'  => true,
            'bonus'    => $bonusAmount,
            'referrer' => $referrer,
        ];
    }

    /**
     * Sensor nama untuk menjaga privasi pengguna (misal: "Ahmad Dahlan" -> "Ahmad D****")
     */
    private function maskName(string $name): string
    {
        $name = trim($name);
        if (empty($name)) {
            return 'Sahabat Tilik';
        }

        $parts = explode(' ', $name);
        if (count($parts) === 1) {
            $len = mb_strlen($name);
            if ($len <= 2) return $name;
            return mb_substr($name, 0, 2) . str_repeat('*', max(1, $len - 2));
        }

        $first = array_shift($parts);
        $maskedParts = [$first];

        foreach ($parts as $p) {
            $pLen = mb_strlen($p);
            if ($pLen <= 1) {
                $maskedParts[] = $p;
            } else {
                $maskedParts[] = mb_substr($p, 0, 1) . str_repeat('*', min(4, $pLen - 1));
            }
        }

        return implode(' ', $maskedParts);
    }

    /**
     * Sensor alamat email untuk privasi (misal: "budi@gmail.com" -> "b***i@gmail.com")
     */
    private function maskEmail(string $email): string
    {
        $parts = explode('@', $email);
        if (count($parts) !== 2) {
            return '***@email.com';
        }

        $name = $parts[0];
        $domain = $parts[1];

        $len = strlen($name);
        if ($len <= 2) {
            $maskedName = substr($name, 0, 1) . '*';
        } else {
            $maskedName = substr($name, 0, 1) . '***' . substr($name, -1);
        }

        return $maskedName . '@' . $domain;
    }
}
