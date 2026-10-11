<?php

namespace App\Controllers;

use App\Models\AmbassadorApplicationModel;
use App\Models\AmbassadorProfileModel;
use App\Models\AmbassadorProofModel;
use App\Models\PaymentTransactionModel;
use App\Models\UserModel;
use CodeIgniter\HTTP\ResponseInterface;

class AmbassadorController extends BaseController
{
    /**
     * Tahap 1 — Formulir Pendaftaran Ringan Duta Kampus
     */
    public function index()
    {
        $userId = session()->get('user_id');
        $userModel = new UserModel();
        $currentUser = $userId ? $userModel->find($userId) : null;

        $hasPurchased = false;
        $existingApp = null;
        $activeProfile = null;

        if ($userId) {
            $paymentModel = new PaymentTransactionModel();
            $hasPurchased = $paymentModel->where('user_id', $userId)
                ->whereIn('transaction_status', ['settlement', 'capture'])
                ->countAllResults() > 0;

            if (!$hasPurchased && !empty($currentUser['subscription_tier']) && $currentUser['subscription_tier'] !== 'free') {
                $hasPurchased = true;
            }

            $appModel = new AmbassadorApplicationModel();
            $existingApp = $appModel->where('user_id', $userId)->orderBy('id', 'DESC')->first();
            if (!$existingApp && !empty($currentUser['email'])) {
                $existingApp = $appModel->where('email', $currentUser['email'])->orderBy('id', 'DESC')->first();
            }

            $profileModel = new AmbassadorProfileModel();
            $activeProfile = $profileModel->getProfileWithStats($userId);
        }

        return view('ambassador/index', [
            'currentUser'   => $currentUser,
            'hasPurchased'  => $hasPurchased,
            'existingApp'   => $existingApp,
            'activeProfile' => $activeProfile,
        ]);
    }

    /**
     * Panduan 1 Halaman & Welcome Kit Duta Kampus (Materi & Template Promosi Siap Salin)
     */
    public function guide()
    {
        $session = session();
        $userId = $session->get('user_id');

        $userModel = new \App\Models\UserModel();
        $currentUser = $userId ? $userModel->find($userId) : null;

        $profile = null;
        if ($userId) {
            $profileModel = new AmbassadorProfileModel();
            $profile = $profileModel->getProfileWithStats($userId);
        }

        // Tentukan kode referral & URL default jika belum login atau belum aktif
        $referralCode = $profile['referral_code'] ?? 'TILIK-KAMPUS-NAMA';
        $referralUrl  = !empty($profile['referral_url']) ? $profile['referral_url'] : base_url('ref/' . $referralCode);

        return view('ambassador/guide', [
            'currentUser'  => $currentUser,
            'profile'      => $profile,
            'referralCode' => $referralCode,
            'referralUrl'  => $referralUrl,
        ]);
    }

    /**
     * Memproses Pengajuan Pendaftaran Duta Kampus (Tahap 1 POST)
     */
    public function apply(): ResponseInterface
    {
        $fullName = trim((string) $this->request->getPost('full_name'));
        $email = trim(strtolower((string) $this->request->getPost('email')));
        $wa = trim((string) $this->request->getPost('whatsapp_number'));
        $institution = trim((string) $this->request->getPost('institution'));
        $major = trim((string) $this->request->getPost('major'));
        $semester = trim((string) $this->request->getPost('semester'));
        $campusNetwork = trim((string) $this->request->getPost('campus_network'));

        if (empty($fullName) || empty($email) || empty($wa) || empty($institution) || empty($major) || empty($semester) || empty($campusNetwork)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Mohon lengkapi semua kolom formulir pendaftaran.',
            ])->setStatusCode(400);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Format alamat email tidak valid.',
            ])->setStatusCode(400);
        }

        $userId = session()->get('user_id') ? (int) session()->get('user_id') : null;
        $ip = $this->request->getIPAddress();

        $appModel = new AmbassadorApplicationModel();
        $result = $appModel->submitApplication([
            'user_id'         => $userId,
            'full_name'       => $fullName,
            'email'           => $email,
            'whatsapp_number' => $wa,
            'institution'     => $institution,
            'major'           => $major,
            'semester'        => $semester,
            'campus_network'  => $campusNetwork,
            'ip_address'      => $ip,
        ]);

        if (!$result['success']) {
            return $this->response->setJSON($result)->setStatusCode(400);
        }

        // Kirim notifikasi instan ke Telegram Admin / Grup (-1002426917612)
        try {
            \App\Libraries\TelegramNotifier::notifyAmbassadorApplication([
                'full_name'       => $fullName,
                'email'           => $email,
                'whatsapp_number' => $wa,
                'institution'     => $institution,
                'major'           => $major,
                'semester'        => $semester,
                'campus_network'  => $campusNetwork,
                'has_purchased'   => $result['has_purchased'] ?? false,
            ]);
        } catch (\Throwable $te) {
            log_message('warning', 'Gagal kirim notifikasi Telegram pendaftaran duta: ' . $te->getMessage());
        }

        return $this->response->setJSON([
            'success'            => true,
            'message'            => 'Pendaftaran Duta Kampus Anda berhasil dikirim! Tim AR Tilik akan meninjau data Anda pada batch seleksi mingguan.',
            'has_purchased'      => $result['has_purchased'],
            'verification_token' => $result['verification_token'],
        ]);
    }

    /**
     * Tahap 3 — Halaman Verifikasi Identitas (Upload Foto KTM/KTP)
     */
    public function verify(string $token)
    {
        $appModel = new AmbassadorApplicationModel();
        $app = $appModel->findByToken($token);

        if (!$app) {
            return redirect()->to('/ambassador')->with('error', 'Token verifikasi tidak valid atau telah kedaluwarsa.');
        }

        return view('ambassador/verify', [
            'app' => $app,
        ]);
    }

    /**
     * Memproses Unggahan Foto KTM / KTP (Tahap 3 POST)
     */
    public function submitVerification(string $token): ResponseInterface
    {
        $appModel = new AmbassadorApplicationModel();
        $app = $appModel->findByToken($token);

        if (!$app) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Token verifikasi identitas tidak ditemukan.',
            ])->setStatusCode(404);
        }

        $file = $this->request->getFile('id_card_file');
        $idType = $this->request->getPost('id_card_type') === 'ktp_dosen' ? 'ktp_dosen' : 'ktm';

        if (!$file || !$file->isValid()) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Mohon pilih foto KTM atau KTP yang jelas untuk diunggah.',
            ])->setStatusCode(400);
        }

        $allowedExts = ['jpg', 'jpeg', 'png', 'webp', 'pdf'];
        $ext = strtolower($file->getExtension());
        if (!in_array($ext, $allowedExts)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Format file harus berupa JPG, PNG, WEBP, atau PDF.',
            ])->setStatusCode(400);
        }

        if ($file->getSizeByUnit('mb') > 5.0) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Ukuran berkas maksimal 5 MB.',
            ])->setStatusCode(400);
        }

        $newName = 'id_' . $app['id'] . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
        $targetDir = WRITEPATH . 'uploads/ambassador_ids/';
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0775, true);
        }

        $file->move($targetDir, $newName);
        $savedPath = 'uploads/ambassador_ids/' . $newName;

        $appModel->saveIdCard((int) $app['id'], $savedPath, $idType);

        // Kirim notifikasi Telegram bahwa berkas identitas / KTM siap diverifikasi admin
        try {
            \App\Libraries\TelegramNotifier::notifyAmbassadorKtmUploaded([
                'id'              => $app['id'],
                'full_name'       => $app['full_name'],
                'email'           => $app['email'],
                'whatsapp_number' => $app['whatsapp_number'],
                'institution'     => $app['institution'],
                'id_card_type'    => $idType,
            ]);
        } catch (\Throwable $te) {
            log_message('warning', 'Gagal kirim notifikasi Telegram unggah KTM: ' . $te->getMessage());
        }

        return $this->response->setJSON([
            'success' => true,
            'message' => 'Foto identitas berhasil diunggah! Berkas Anda telah tersimpan aman dan siap diaktivasi oleh tim AR Tilik.',
        ]);
    }

    /**
     * Handler Tautan Referral (Tahap 4): /ref/{code}
     */
    public function ref(string $code)
    {
        $code = strtoupper(preg_replace('/[^A-Z0-9\-]/', '', trim($code)));
        if (!empty($code)) {
            // Simpan ke session & cookie 30 hari
            session()->set('referred_by_code', $code);
            setcookie('referred_by_code', $code, time() + (30 * 86400), '/', '', false, true);
        }

        return redirect()->to(base_url('/?ref=' . urlencode($code) . '&auth=register'));
    }

    /**
     * Tahap 5 & 6 — Dashboard Kinerja & Unggah Bukti Promosi Duta Kampus
     */
    public function dashboard()
    {
        $userId = session()->get('user_id');
        if (!$userId) {
            return redirect()->to(base_url('login?redirect=ambassador/dashboard'));
        }

        $profileModel = new AmbassadorProfileModel();
        $profile = $profileModel->getProfileWithStats($userId);

        if (!$profile) {
            return redirect()->to(base_url('ambassador'))->with('info', 'Anda belum terdaftar atau belum diaktivasi sebagai Duta Kampus.');
        }

        $proofModel = new AmbassadorProofModel();
        $proofs = $proofModel->getProofs($profile['id']);

        $payoutModel = new \App\Models\AmbassadorPayoutModel();
        $financial = $payoutModel->getFinancialSummary($profile['id'], (float) $profile['estimated_commission']);
        $payouts = $payoutModel->getPayouts($profile['id']);

        return view('ambassador/dashboard', [
            'profile'   => $profile,
            'proofs'    => $proofs,
            'financial' => $financial,
            'payouts'   => $payouts,
        ]);
    }

    /**
     * Unggah Bukti Promosi Bulanan (Tahap 5 & 6 POST)
     */
    public function submitProof(): ResponseInterface
    {
        $userId = session()->get('user_id');
        if (!$userId) {
            return $this->response->setJSON(['success' => false, 'message' => 'Silakan login terlebih dahulu.'])->setStatusCode(401);
        }

        $profileModel = new AmbassadorProfileModel();
        $profile = $profileModel->where('user_id', $userId)->first();
        if (!$profile) {
            return $this->response->setJSON(['success' => false, 'message' => 'Profil Duta Kampus tidak ditemukan.'])->setStatusCode(404);
        }

        $proofType = $this->request->getPost('proof_type') ?: 'story_wa';
        $title = trim((string)$this->request->getPost('title'));
        $description = trim((string)$this->request->getPost('description'));
        $urlLink = trim((string)$this->request->getPost('url_link'));

        if (empty($title)) {
            return $this->response->setJSON(['success' => false, 'message' => 'Judul bukti promosi wajib diisi.'])->setStatusCode(400);
        }

        $filePath = null;
        $file = $this->request->getFile('proof_file');
        if ($file && $file->isValid()) {
            $allowed = ['jpg', 'jpeg', 'png', 'webp', 'pdf'];
            $ext = strtolower($file->getExtension());
            if (in_array($ext, $allowed)) {
                $newName = 'proof_' . $profile['id'] . '_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                $targetDir = WRITEPATH . 'uploads/ambassador_proofs/';
                if (!is_dir($targetDir)) {
                    mkdir($targetDir, 0775, true);
                }
                $file->move($targetDir, $newName);
                $filePath = 'uploads/ambassador_proofs/' . $newName;
            }
        }

        $proofModel = new AmbassadorProofModel();
        $proofId = $proofModel->submitProof([
            'ambassador_id' => $profile['id'],
            'month_period'  => date('Y-m'),
            'proof_type'    => $proofType,
            'title'         => $title,
            'description'   => $description,
            'file_path'     => $filePath,
            'url_link'      => $urlLink ?: null,
        ]);

        // Kirim notifikasi Telegram bahwa bukti promosi masuk
        try {
            $userModel = new \App\Models\UserModel();
            $ambUser = $userModel->find($profile['user_id']);
            \App\Libraries\TelegramNotifier::notifyAmbassadorProofSubmitted([
                'proof_id'        => $proofId,
                'ambassador_name' => $ambUser['name'] ?? 'Duta Kampus',
                'institution'     => $ambUser['institution'] ?? '-',
                'referral_code'   => $profile['referral_code'],
                'proof_type'      => $proofType,
                'title'           => $title,
                'description'     => $description,
                'url_link'        => $urlLink,
                'has_file'        => !empty($filePath),
            ]);
        } catch (\Throwable $te) {
            log_message('warning', 'Gagal kirim notifikasi Telegram bukti promosi: ' . $te->getMessage());
        }

        return $this->response->setJSON([
            'success' => true,
            'message' => 'Bukti promosi berhasil diunggah! Akan ditinjau pada rekap bulanan.',
        ]);
    }

    /**
     * Mengajukan Penarikan Komisi Duta Kampus (Payout Request POST)
     */
    public function requestPayout(): ResponseInterface
    {
        $userId = session()->get('user_id');
        if (!$userId) {
            return $this->response->setJSON(['success' => false, 'message' => 'Silakan login terlebih dahulu.'])->setStatusCode(401);
        }

        $profileModel = new AmbassadorProfileModel();
        $profile = $profileModel->getProfileWithStats($userId);
        if (!$profile) {
            return $this->response->setJSON(['success' => false, 'message' => 'Profil Duta Kampus tidak ditemukan.'])->setStatusCode(404);
        }

        $amount = (float) $this->request->getPost('amount');
        $channel = trim((string) $this->request->getPost('payout_channel'));
        $accNumber = trim((string) $this->request->getPost('payout_account_number'));
        $accName = trim((string) $this->request->getPost('payout_account_name'));

        $payoutModel = new \App\Models\AmbassadorPayoutModel();
        $result = $payoutModel->submitPayoutRequest([
            'ambassador_id'         => $profile['id'],
            'user_id'               => $userId,
            'amount'                => $amount,
            'payout_channel'        => $channel,
            'payout_account_number' => $accNumber,
            'payout_account_name'   => $accName,
        ], (float) $profile['estimated_commission']);

        if (!$result['success']) {
            return $this->response->setJSON($result)->setStatusCode(400);
        }

        // Kirim notifikasi Telegram ke Admin agar segera ditransfer
        try {
            $userModel = new \App\Models\UserModel();
            $ambUser = $userModel->find($userId);
            \App\Libraries\TelegramNotifier::notifyAmbassadorPayoutRequested([
                'payout_code'           => $result['payout_code'],
                'ambassador_name'       => $ambUser['name'] ?? 'Duta Kampus',
                'referral_code'         => $profile['referral_code'],
                'institution'           => $ambUser['institution'] ?? '-',
                'amount'                => $amount,
                'payout_channel'        => $channel,
                'payout_account_number' => $accNumber,
                'payout_account_name'   => $accName,
                'remaining_balance'     => $result['remaining_balance'] ?? 0,
            ]);
        } catch (\Throwable $te) {
            log_message('warning', 'Gagal kirim notifikasi Telegram penarikan komisi: ' . $te->getMessage());
        }

        return $this->response->setJSON($result);
    }
}



