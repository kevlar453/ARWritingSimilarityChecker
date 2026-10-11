<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\AmbassadorApplicationModel;
use App\Models\AmbassadorProfileModel;
use App\Models\UserModel;
use CodeIgniter\HTTP\ResponseInterface;

class AmbassadorAdminController extends BaseController
{
    /**
     * Tahap 2 — Menerima Pendaftaran (Seleksi Asinkron & Minta Upload KTM)
     */
    public function acceptApplication(int $id): ResponseInterface
    {
        $appModel = new AmbassadorApplicationModel();
        $app = $appModel->find($id);

        if (!$app) {
            return $this->response->setJSON(['success' => false, 'message' => 'Data pendaftaran tidak ditemukan.'])->setStatusCode(404);
        }

        $adminId = session()->get('user_id');
        $token = $app['verification_token'] ?: bin2hex(random_bytes(24));
        $verifyUrl = base_url('ambassador/verify/' . $token);

        $appModel->update($id, [
            'status'             => 'accepted',
            'verification_token' => $token,
            'reviewed_by'        => $adminId,
            'reviewed_at'        => date('Y-m-d H:i:s'),
            'updated_at'         => date('Y-m-d H:i:s'),
        ]);

        // Template Pesan WhatsApp Penerimaan & Permintaan KTM
        $cleanWa = preg_replace('/[^0-9]/', '', $app['whatsapp_number']);
        if (str_starts_with($cleanWa, '0')) {
            $cleanWa = '62' . substr($cleanWa, 1);
        }

        $waText = "Halo kak *" . $app['full_name'] . "*,\n\n"
            . "Selamat! Pengajuan Anda untuk menjadi *Duta Kampus AR Tilik* (" . $app['institution'] . ") telah LOLOS seleksi tahap awal 🎉.\n\n"
            . "Satu langkah terakhir untuk verifikasi identitas (anti-fraud 1 orang = 1 akun duta):\n"
            . "Mohon unggah foto KTM atau tanda pengenal kampus Anda melalui tautan resmi berikut:\n"
            . $verifyUrl . "\n\n"
            . "Setelah diunggah, kode referral unik dan akun premium Anda akan segera kami aktifkan.\n\n"
            . "Salam hangat,\nTim Kemitraan Akademik AR Tilik";

        $waLink = 'https://wa.me/' . $cleanWa . '?text=' . urlencode($waText);

        return $this->response->setJSON([
            'success'   => true,
            'message'   => 'Pendaftar berhasil diterima! Silakan kirimkan tautan verifikasi KTM via WhatsApp.',
            'wa_link'   => $waLink,
            'verify_url'=> $verifyUrl,
        ]);
    }

    /**
     * Tahap 2 — Menolak Pendaftaran dengan Template Sopan
     */
    public function rejectApplication(int $id): ResponseInterface
    {
        $appModel = new AmbassadorApplicationModel();
        $app = $appModel->find($id);

        if (!$app) {
            return $this->response->setJSON(['success' => false, 'message' => 'Data pendaftaran tidak ditemukan.'])->setStatusCode(404);
        }

        $adminId = session()->get('user_id');
        $notes = $this->request->getPost('admin_notes') ?: 'Kuota kampus telah penuh atau belum memenuhi kriteria jaringan.';

        $appModel->update($id, [
            'status'       => 'rejected',
            'admin_notes'  => $notes,
            'reviewed_by'  => $adminId,
            'reviewed_at'  => date('Y-m-d H:i:s'),
            'updated_at'   => date('Y-m-d H:i:s'),
        ]);

        $cleanWa = preg_replace('/[^0-9]/', '', $app['whatsapp_number']);
        if (str_starts_with($cleanWa, '0')) {
            $cleanWa = '62' . substr($cleanWa, 1);
        }

        $waText = "Halo kak *" . $app['full_name'] . "*,\n\n"
            . "Terima kasih atas antusiasme Anda mendaftar program Duta Kampus AR Tilik.\n\n"
            . "Mohon maaf, saat ini kuota perwakilan untuk " . $app['institution'] . " pada batch ini telah terpenuhi. Data Anda telah kami simpan untuk batch pembukaan berikutnya.\n\n"
            . "Anda tetap dapat menggunakan seluruh fitur pemeriksaan naskah di https://tilik.arsetontong.top\n\n"
            . "Salam hormat,\nTim AR Tilik";

        $waLink = 'https://wa.me/' . $cleanWa . '?text=' . urlencode($waText);

        return $this->response->setJSON([
            'success' => true,
            'message' => 'Pendaftaran ditandai ditolak.',
            'wa_link' => $waLink,
        ]);
    }

    /**
     * Tahap 4 — Aktivasi Duta Kampus & Pembuatan Kode Referral (Welcome Packet)
     */
    public function activateAmbassador(int $id): ResponseInterface
    {
        $appModel = new AmbassadorApplicationModel();
        $app = $appModel->find($id);

        if (!$app) {
            return $this->response->setJSON(['success' => false, 'message' => 'Data pendaftaran tidak ditemukan.'])->setStatusCode(404);
        }

        $customCode = trim((string) $this->request->getPost('referral_code'));
        if (empty($customCode)) {
            // Format cerdas: TILIK-[SINGKATAN KAMPUS]-[NAMA DEPAN]
            $firstWord = explode(' ', trim($app['full_name']))[0] ?? 'DUTA';
            $firstWord = strtoupper(preg_replace('/[^A-Z]/', '', $firstWord));
            $instCode = strtoupper(substr(preg_replace('/[^A-Z]/', '', $app['institution']), 0, 4)) ?: 'CAMPUS';
            $customCode = 'TILIK-' . $instCode . '-' . $firstWord;
        }

        $profileModel = new AmbassadorProfileModel();
        $result = $profileModel->activateAmbassador($id, $customCode, [
            'target_promos'       => 8,
            'target_transactions' => 3,
            'commission_rate'     => 20.00,
        ]);

        if (!$result['success']) {
            return $this->response->setJSON($result)->setStatusCode(400);
        }

        // Susun Paket Sambutan WhatsApp (Welcome Packet)
        $cleanWa = preg_replace('/[^0-9]/', '', $app['whatsapp_number']);
        if (str_starts_with($cleanWa, '0')) {
            $cleanWa = '62' . substr($cleanWa, 1);
        }

        $refUrl = $result['referral_url'];
        $refCode = $result['referral_code'];

        $waText = "Halo kak *" . $app['full_name'] . "*,\n\n"
            . "Selamat datang di keluarga resmi *Duta Kampus AR Tilik*! 🚀🎓\n\n"
            . "Berikut adalah **Paket Sambutan Resmi** Anda:\n"
            . "1. Kode Referral Anda: *" . $refCode . "*\n"
            . "2. Tautan Referral Anda: " . $refUrl . "\n"
            . "3. Akun Premium Anda: *Telah Aktif Otomatis selama 30 Hari*\n\n"
            . "📋 **Ketentuan Masa Percobaan (Bulan 1):**\n"
            . "• Jalankan minimal 8x posting promosi (Story WA/IG/Grup angkatan) ATAU hasilkan 3 transaksi referral.\n"
            . "• Komisi bagi hasil 20% dicairkan di akhir bulan.\n"
            . "• Pantau statistik & unggah bukti promosi di: " . base_url('ambassador/dashboard') . "\n\n"
            . "Selamat berkontribusi menjaga integritas karya ilmiah kampus Anda!\n\n"
            . "Salam sukses,\nFounder AR Tilik";

        $waLink = 'https://wa.me/' . $cleanWa . '?text=' . urlencode($waText);

        return $this->response->setJSON([
            'success'       => true,
            'message'       => 'Duta Kampus berhasil diaktivasi! Paket sambutan siap dikirim.',
            'referral_code' => $refCode,
            'referral_url'  => $refUrl,
            'wa_link'       => $waLink,
        ]);
    }
}
