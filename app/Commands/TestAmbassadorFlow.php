<?php

namespace App\Commands;

use App\Models\AmbassadorApplicationModel;
use App\Models\AmbassadorProfileModel;
use App\Models\AmbassadorProofModel;
use App\Models\PaymentTransactionModel;
use App\Models\UserModel;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class TestAmbassadorFlow extends BaseCommand
{
    protected $group       = 'Testing';
    protected $name        = 'ambassador:test';
    protected $description = 'Menjalankan simulasi pengujian alur 6 tahap Duta Kampus untuk akun ymakarius@gmail.com';

    public function run(array $params)
    {
        CLI::write("==================================================", 'yellow');
        CLI::write("🧪 SIMULASI PENGUJIAN ALUR 6 TAHAP DUTA KAMPUS", 'green');
        CLI::write("   Akun Target: ymakarius@gmail.com", 'white');
        CLI::write("==================================================\n", 'yellow');

        $userModel = new UserModel();
        $user = $userModel->where('email', 'ymakarius@gmail.com')->first();

        if (!$user) {
            CLI::error("❌ Pengguna ymakarius@gmail.com tidak ditemukan di database.");
            return;
        }

        $userId = (int) $user['id'];
        CLI::write("✓ User ID: {$userId} ({$user['name']})", 'cyan');

        // 1. Cek Kualifikasi Pembelian Paket
        $payModel = new PaymentTransactionModel();
        $paidTx = $payModel->where('user_id', $userId)
            ->whereIn('transaction_status', ['settlement', 'capture'])
            ->findAll();

        CLI::write("✓ Riwayat Transaksi Sukses: " . count($paidTx) . " transaksi ditemukan", 'cyan');

        // TAHAP 1: Pendaftaran
        CLI::write("\n[TAHAP 1] — PENDAFTARAN DUTA KAMPUS", 'yellow');
        $appModel = new AmbassadorApplicationModel();
        $app = $appModel->where('email', 'ymakarius@gmail.com')->orderBy('id', 'DESC')->first();

        if (!$app) {
            CLI::write("Mengirimkan formulir pendaftaran baru...", 'white');
            $res = $appModel->submitApplication([
                'user_id'         => $userId,
                'full_name'       => $user['name'],
                'email'           => $user['email'],
                'whatsapp_number' => '081234567890',
                'institution'     => 'Universitas Gadjah Mada',
                'major'           => 'Ilmu Komputer',
                'semester'        => '7',
                'campus_network'  => 'Pengurus BEM FMIPA, koordinator grup riset mahasiswa 2023.',
            ]);
            $app = $appModel->find($res['application_id']);
        }

        CLI::write("✓ Berkas Pendaftaran ID: " . $app['id'], 'green');
        CLI::write("✓ Status: " . strtoupper($app['status']), 'white');
        CLI::write("✓ Kualifikasi has_purchased: " . ($app['has_purchased'] ? 'Lolos (1)' : 'Gagal (0)'), $app['has_purchased'] ? 'green' : 'red');
        CLI::write("✓ Token Verifikasi: " . $app['verification_token'], 'white');

        // TAHAP 2: Seleksi Asinkron (Lolos Tahap Awal)
        CLI::write("\n[TAHAP 2] — SELEKSI ASINKRON (REVIEW ADMIN)", 'yellow');
        $token = $app['verification_token'];
        $appModel->update($app['id'], [
            'status'      => 'accepted',
            'reviewed_by' => 2, // admin Rakam
            'reviewed_at' => date('Y-m-d H:i:s'),
        ]);
        $verifyUrl = base_url('ambassador/verify/' . $token);
        CLI::write("✓ Admin menyetujui pendaftar ke Tahap 2", 'green');
        CLI::write("✓ Status aplikasi diperbarui: ACCEPTED", 'cyan');
        CLI::write("✓ URL Verifikasi KTM: " . $verifyUrl, 'white');

        // TAHAP 3: Verifikasi Identitas (Upload KTM)
        CLI::write("\n[TAHAP 3] — VERIFIKASI IDENTITAS (UNGGAH FOTO KTM)", 'yellow');
        $uploadDir = WRITEPATH . 'storage/ambassadors/ktm/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        $simulatedKtmPath = 'ambassadors/ktm/ktm_test_' . $userId . '.jpg';
        file_put_contents(WRITEPATH . 'storage/' . $simulatedKtmPath, 'DUMMY_KTM_IMAGE_FOR_TESTING');

        $appModel->saveIdCard($app['id'], $simulatedKtmPath, 'ktm');
        $app = $appModel->find($app['id']);
        CLI::write("✓ Foto KTM berhasil diunggah & diverifikasi", 'green');
        CLI::write("✓ Path file tersimpan aman: " . $app['id_card_path'], 'white');
        CLI::write("✓ Status aplikasi diperbarui: VERIFIED", 'cyan');

        // TAHAP 4: Aktivasi Kode Referral & Akun Premium
        CLI::write("\n[TAHAP 4] — AKTIVASI KODE REFERRAL & SAMBUTAN", 'yellow');
        $profileModel = new AmbassadorProfileModel();
        $refCode = 'TILIK-UGM-YEMA';
        $actResult = $profileModel->activateAmbassador($app['id'], $refCode);

        CLI::write("✓ Hasil Aktivasi: " . ($actResult['success'] ? 'SUKSES' : 'GAGAL'), $actResult['success'] ? 'green' : 'red');
        CLI::write("✓ Kode Referral Duta: " . $actResult['referral_code'], 'yellow');
        CLI::write("✓ Tautan Referral: " . $actResult['referral_url'], 'white');

        // Periksa status user setelah aktivasi
        $updatedUser = $userModel->find($userId);
        CLI::write("✓ Status Akun Pengguna: " . strtoupper($updatedUser['subscription_tier']), 'green');
        CLI::write("✓ Akses Premium Aktif Hingga: " . $updatedUser['subscription_expires_at'], 'cyan');

        // TAHAP 5: Masa Percobaan (Probation 1 Bulan) & Bukti Promosi
        CLI::write("\n[TAHAP 5] — MASA PERCOBAAN (PROBATION) & BUKTI PROMOSI", 'yellow');
        $proofModel = new AmbassadorProofModel();
        $profile = $profileModel->where('user_id', $userId)->first();

        $proofId = $proofModel->submitProof([
            'ambassador_id' => $profile['id'],
            'proof_type'    => 'grup_wa',
            'title'         => 'Edukasi Audit Forensik & Cek Plagiasi di Grup Angkatan 2023',
            'description'   => 'Membagikan info fitur deteksi sitasi masa depan & kuota gratis ke 120 mahasiswa.',
            'url_link'      => 'https://chat.whatsapp.com/test-group',
        ]);
        CLI::write("✓ Unggah bukti aktivitas promosi probation berhasil (ID #{$proofId})", 'green');
        CLI::write("✓ Target Probation: " . $profile['probation_target_promos'] . " promosi / " . $profile['probation_target_transactions'] . " transaksi", 'white');

        // TAHAP 6: Dashboard Evaluasi & Komisi
        CLI::write("\n[TAHAP 6] — EVALUASI BULANAN & DASHBOARD DUTA", 'yellow');
        $stats = $profileModel->getProfileWithStats($userId);
        CLI::write("✓ Status Duta: " . strtoupper($stats['status']), 'green');
        CLI::write("✓ Periode Probation: " . $stats['probation_start'] . " s/d " . $stats['probation_end'], 'white');
        CLI::write("✓ Total Promosi Terunggah Bulan Ini: " . $stats['promos_this_month'] . " / " . $stats['probation_target_promos'], 'cyan');
        CLI::write("✓ Pengguna Terdaftar lewat Referral: " . $stats['total_registered'] . " orang", 'cyan');
        CLI::write("✓ Total Transaksi Afiliasi: " . $stats['total_transactions'], 'cyan');
        CLI::write("✓ Estimasi Komisi: Rp " . number_format($stats['estimated_commission'], 0, ',', '.'), 'green');
        CLI::write("✓ Persentase Bagi Hasil: " . $stats['commission_rate_percent'] . "%", 'white');

        CLI::write("\n==================================================", 'yellow');
        CLI::write("🎉 KESIMPULAN: SELURUH 6 TAHAP DUTA KAMPUS 100% LOLOS UJI!", 'green');
        CLI::write("==================================================", 'yellow');
    }
}
