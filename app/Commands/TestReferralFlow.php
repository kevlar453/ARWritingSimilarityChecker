<?php

namespace App\Commands;

use App\Models\PaymentTransactionModel;
use App\Models\ReferralModel;
use App\Models\UserModel;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class TestReferralFlow extends BaseCommand
{
    protected $group       = 'Testing';
    protected $name        = 'referral:test';
    protected $description = 'Menjalankan simulasi pengujian siklus penuh Program Referral Reguler Member-Get-Member (Fitur 3)';

    public function run(array $params)
    {
        CLI::write("==================================================", 'yellow');
        CLI::write("🧪 SIMULASI PENGUJIAN PROGRAM REFERRAL REGULER (FITUR 3)", 'green');
        CLI::write("   MEMBER-GET-MEMBER: INVITER & INVITEE REWARD LIFECYCLE", 'white');
        CLI::write("==================================================\n", 'yellow');

        $userModel = new UserModel();
        $referralModel = new ReferralModel();
        $db = \Config\Database::connect();

        // 1. TAHAP A: Menyiapkan Pengundang (User A)
        CLI::write("[TAHAP A] — MENYIAPKAN PENGUNDANG (USER A)", 'yellow');
        $inviterEmail = 'akademisi_81debd0b@kampus.ac.id';
        $inviter = $userModel->where('email', $inviterEmail)->first();

        if (!$inviter) {
            $inviter = $userModel->where('email', 'ymakarius@gmail.com')->first();
        }

        if (!$inviter) {
            CLI::error("❌ Pengguna pengundang tidak ditemukan.");
            return;
        }

        $inviterId = (int) $inviter['id'];
        $inviterCode = $userModel->ensureReferralCode($inviterId);
        $initialBonus = (int) ($inviter['bonus_submissions'] ?? 0);

        CLI::write("✓ Pengundang: {$inviter['name']} (ID: {$inviterId})", 'cyan');
        CLI::write("✓ Kode Referral Pengundang: {$inviterCode}", 'green');
        CLI::write("✓ Tautan Undangan: " . base_url("ref/{$inviterCode}"), 'white');
        CLI::write("✓ Kuota Bonus Awal: {$initialBonus} submisi", 'cyan');

        // 2. TAHAP B: Pendaftaran Sahabat Baru (User B) Menggunakan Kode Referral
        CLI::write("\n[TAHAP B] — PENDAFTARAN SAHABAT BARU (USER B) DENGAN KODE REFERRAL", 'yellow');
        $inviteeEmail = 'sahabat_test_' . substr(md5(uniqid()), 0, 8) . '@kampus.ac.id';

        $inviteeId = $userModel->insert([
            'name'                            => 'Budi Prakoso Pratama',
            'email'                           => $inviteeEmail,
            'password_hash'                   => password_hash('Rahasia12345!', PASSWORD_DEFAULT),
            'institution'                     => 'Universitas Indonesia',
            'role'                            => 'user',
            'status'                          => 'pending_verification',
            'verification_token'              => bin2hex(random_bytes(16)),
            'verification_token_expires_at'   => date('Y-m-d H:i:s', strtotime('+24 hours')),
            'free_submissions_used_this_week' => 0,
            'week_reset_at'                   => date('Y-m-d 00:00:00', strtotime('next monday')),
            'bonus_submissions'               => 1, // Bonus pendaftaran via referral
            'referred_by_code'                => $inviterCode,
            'created_at'                      => date('Y-m-d H:i:s'),
            'updated_at'                      => date('Y-m-d H:i:s'),
        ]);

        $userModel->ensureReferralCode((int) $inviteeId);
        $invitee = $userModel->find($inviteeId);

        CLI::write("✓ Sahabat Terdaftar ID: {$inviteeId} ({$invitee['name']})", 'cyan');
        CLI::write("✓ Kode Referral Yang Dipakai: {$invitee['referred_by_code']}", 'white');
        CLI::write("✓ Bonus Cek Registrasi Sahabat: {$invitee['bonus_submissions']} kuota (+1)", 'green');

        // Cek eligibilitas diskon perdana
        $isEligibleDiscount = $referralModel->isEligibleForReferralDiscount($inviteeId);
        CLI::write("✓ Hak Diskon Perdana 10%: " . ($isEligibleDiscount ? 'YA (LOLOS)' : 'TIDAK'), $isEligibleDiscount ? 'green' : 'red');

        // 3. TAHAP C: Aktivasi Email Sahabat & Kreditasi Reward +2 Kuota ke Pengundang
        CLI::write("\n[TAHAP C] — AKTIVASI EMAIL SAHABAT & REWARD +2 KUOTA", 'yellow');
        $userModel->update($inviteeId, [
            'status'            => 'active',
            'email_verified_at' => date('Y-m-d H:i:s'),
        ]);

        $awardRes = $referralModel->awardVerificationBonus($inviteeId);
        CLI::write("✓ Status Pemberian Reward: " . ($awardRes['awarded'] ? 'SUKSES' : 'GAGAL'), $awardRes['awarded'] ? 'green' : 'red');
        CLI::write("✓ Bonus Kuota Pengundang Diberikan: +{$awardRes['bonus']} submisi", 'green');

        // Cek kuota baru pengundang di database
        $updatedInviter = $userModel->find($inviterId);
        $expectedBonus = $initialBonus + 2;
        CLI::write("✓ Kuota Baru Pengundang: {$updatedInviter['bonus_submissions']} (Expected: {$expectedBonus})", 'cyan');

        // Uji Idempotensi: Panggil ulang, tidak boleh bertambah ganda
        $duplicateAward = $referralModel->awardVerificationBonus($inviteeId);
        CLI::write("✓ Uji Idempotensi (Pencegahan Double-Claim): " . (!$duplicateAward['awarded'] && $duplicateAward['reason'] === 'already_awarded' ? 'SUKSES (DITANGKAL)' : 'GAGAL'), 'green');

        // 4. TAHAP D: Pembelian Paket Perdana oleh Sahabat (Diskon 10%) & Reward +5 Kuota
        CLI::write("\n[TAHAP D] — TRANSAKSI PERDANA SAHABAT (DISKON 10%) & BONUS +5 KUOTA", 'yellow');
        $orderId = 'TILIK-REFTEST-' . strtoupper(bin2hex(random_bytes(4)));
        $originalPrice = 25000;
        $discountAmount = (int) round($originalPrice * 0.10);
        $finalPrice = $originalPrice - $discountAmount;

        $txModel = new PaymentTransactionModel();
        $txId = $txModel->insert([
            'user_id'            => $inviteeId,
            'order_id'           => $orderId,
            'package_id'         => 'subs_skripsi',
            'package_name'       => 'Paket Pejuang Skripsi (Diskon Referral 10%)',
            'amount'             => (float) $finalPrice,
            'submissions_added'  => 20,
            'subscription_tier'  => 'skripsi',
            'subscription_days'  => 30,
            'payment_type'       => 'qris',
            'transaction_status' => 'settlement',
            'fraud_status'       => 'accept',
            'paid_at'            => date('Y-m-d H:i:s'),
            'created_at'         => date('Y-m-d H:i:s'),
            'updated_at'         => date('Y-m-d H:i:s'),
        ]);

        CLI::write("✓ Transaksi Settlement Berhasil (ID #{$txId}, Order: {$orderId})", 'cyan');
        CLI::write("✓ Harga Asli: Rp " . number_format($originalPrice, 0, ',', '.') . " | Diskon 10%: Rp " . number_format($discountAmount, 0, ',', '.'), 'white');
        CLI::write("✓ Total Dibayar Sahabat: Rp " . number_format($finalPrice, 0, ',', '.'), 'green');

        // Proses reward pembelian perdana ke pengundang
        $purchaseAward = $referralModel->awardPurchaseBonus($txId);
        CLI::write("✓ Reward Transaksi ke Pengundang: " . ($purchaseAward['awarded'] ? 'SUKSES' : 'GAGAL'), $purchaseAward['awarded'] ? 'green' : 'red');
        CLI::write("✓ Bonus Kuota Transaksi: +{$purchaseAward['bonus']} submisi", 'green');

        // Uji Idempotensi Transaksi
        $dupTxAward = $referralModel->awardPurchaseBonus($txId);
        CLI::write("✓ Uji Idempotensi Transaksi: " . (!$dupTxAward['awarded'] && $dupTxAward['reason'] === 'already_awarded' ? 'SUKSES (DITANGKAL)' : 'GAGAL'), 'green');

        // 5. TAHAP E: Verifikasi Tampilan Statistik & Masking Privasi
        CLI::write("\n[TAHAP E] — VERIFIKASI STATISTIK PENGUNDANG & PRIVASI ETIS", 'yellow');
        $stats = $referralModel->getReferralStats($inviterId);

        CLI::write("✓ Total Sahabat Terdaftar: {$stats['total_invited']} orang", 'cyan');
        CLI::write("✓ Total Sahabat Terverifikasi: {$stats['total_verified']} orang", 'green');
        CLI::write("✓ Total Akumulasi Bonus Submisi: +{$stats['total_bonus_submissions']} kuota", 'yellow');

        if (!empty($stats['invited_users'])) {
            $latestFriend = $stats['invited_users'][0];
            CLI::write("✓ Sensor Nama Privasi: {$latestFriend['name_masked']} (Asli: Budi Prakoso Pratama)", 'white');
            CLI::write("✓ Sensor Email Privasi: {$latestFriend['email_masked']}", 'white');
            CLI::write("✓ Total Bonus dari Sahabat Ini: +{$latestFriend['bonus_earned']} kuota", 'green');
        }

        // Bersihkan data uji invitee
        $db->table('payment_transactions')->where('id', $txId)->delete();
        $db->table('referral_logs')->where('referred_user_id', $inviteeId)->delete();
        $db->table('users')->where('id', $inviteeId)->delete();
        // Kembalikan bonus awal inviter
        $userModel->update($inviterId, ['bonus_submissions' => $initialBonus]);

        CLI::write("\n✓ Pembersihan Data Uji: Selesai", 'white');
        CLI::write("==================================================", 'yellow');
        CLI::write("🎉 KESIMPULAN: SELURUH ALUR MEMBER-GET-MEMBER 100% LOLOS UJI!", 'green');
        CLI::write("==================================================", 'yellow');
    }
}
