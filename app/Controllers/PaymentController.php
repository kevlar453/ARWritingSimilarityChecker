<?php

namespace App\Controllers;

use App\Models\PaymentTransactionModel;
use App\Models\UserModel;
use App\Libraries\MidtransService;
use App\Libraries\TelegramNotifier;
use CodeIgniter\HTTP\ResponseInterface;

class PaymentController extends BaseController
{
    protected PaymentTransactionModel $transModel;
    protected UserModel $userModel;
    protected MidtransService $midtransService;

    public function __construct()
    {
        $this->transModel = new PaymentTransactionModel();
        $this->userModel = new UserModel();
        $this->midtransService = new MidtransService();
    }

    /**
     * Dapatkan daftar paket yang tersedia dan Client Key
     */
    public function packages(): ResponseInterface
    {
        $isEligibleDiscount = false;
        $userId = session()->get('user_id');
        if ($userId) {
            $referralModel = new \App\Models\ReferralModel();
            $isEligibleDiscount = $referralModel->isEligibleForReferralDiscount((int) $userId);
        }

        return $this->response->setJSON([
            'status'               => 'success',
            'client_key'           => $this->midtransService->getClientKey(),
            'packages'             => array_values(MidtransService::getPackages()),
            'is_eligible_discount' => $isEligibleDiscount,
            'discount_percent'     => $isEligibleDiscount ? 10 : 0,
        ]);
    }

    /**
     * Membuat Order dan Snap Token Midtrans untuk checkout
     */
    public function createSnapToken(): ResponseInterface
    {
        $userId = session()->get('user_id');
        if (!$userId) {
            return $this->response->setStatusCode(401)->setJSON([
                'status'  => 'error',
                'message' => 'Silakan login terlebih dahulu untuk melakukan pembelian kuota.',
            ]);
        }

        $user = $this->userModel->find($userId);
        if (!$user) {
            return $this->response->setStatusCode(404)->setJSON([
                'status'  => 'error',
                'message' => 'Pengguna tidak ditemukan.',
            ]);
        }

        $packageId = (string) $this->request->getPost('package_id');
        $package = MidtransService::getPackage($packageId);
        if (!$package) {
            return $this->response->setStatusCode(400)->setJSON([
                'status'  => 'error',
                'message' => 'Paket yang dipilih tidak valid.',
            ]);
        }

        // Cek eligibilitas diskon selamat datang Member-Get-Member (10%)
        $referralModel = new \App\Models\ReferralModel();
        $isEligibleDiscount = $referralModel->isEligibleForReferralDiscount((int) $user['id']);
        $originalAmount = (int) $package['amount'];
        $discountAmount = 0;
        if ($isEligibleDiscount) {
            $discountAmount = (int) round($originalAmount * 0.10);
        }
        $finalAmount = max(1000, $originalAmount - $discountAmount);

        // Periksa apakah pengguna memiliki transaksi pending untuk paket ini dalam 60 menit terakhir
        $existingPending = $this->transModel
            ->where('user_id', (int) $user['id'])
            ->where('package_id', $package['id'])
            ->where('transaction_status', 'pending')
            ->where('created_at >=', date('Y-m-d H:i:s', strtotime('-60 minutes')))
            ->where('snap_token IS NOT NULL', null, false)
            ->orderBy('id', 'DESC')
            ->first();

        if ($existingPending && !empty($existingPending['snap_token'])) {
            return $this->response->setJSON([
                'status'       => 'success',
                'order_id'     => $existingPending['order_id'],
                'snap_token'   => $existingPending['snap_token'],
                'redirect_url' => $existingPending['snap_redirect_url'] ?? '',
                'package'      => $package,
                'resumed'      => true,
                'order_detail' => "Melanjutkan pembayaran pesanan pending {$existingPending['order_id']}",
            ]);
        }

        // Buat Order ID unik dengan entropi tinggi AR Tilik: TILIK-{timestamp}-{random12hex}
        $orderId = 'TILIK-' . date('YmdHis') . '-' . strtoupper(bin2hex(random_bytes(6)));

        // Catat transaksi pending di database
        $this->transModel->insert([
            'user_id'            => (int) $user['id'],
            'order_id'           => $orderId,
            'package_id'         => $package['id'],
            'package_name'       => $package['name'] . ($discountAmount > 0 ? ' (Diskon Referral 10%)' : ''),
            'amount'             => (float) $finalAmount,
            'submissions_added'  => (int) $package['submissions'],
            'subscription_tier'  => $package['tier'],
            'subscription_days'  => (int) $package['days'],
            'payment_type'       => null,
            'transaction_status' => 'pending',
            'fraud_status'       => null,
            'created_at'         => date('Y-m-d H:i:s'),
            'updated_at'         => date('Y-m-d H:i:s'),
        ]);

        $userName        = trim((string) ($user['name'] ?? 'Pelanggan AR Tilik'));
        $userEmail       = trim((string) ($user['email'] ?? ''));
        $userInstitution = trim((string) ($user['institution'] ?? ''));
        $packageName     = (string) ($package['name'] ?? 'Paket Kuota AR Tilik');

        // Format Order Detail lengkap: {nama pengguna} dari {nama_institusi} order paket {nama_paket}
        if (!empty($userInstitution)) {
            $orderDetail = "{$userName} dari {$userInstitution} order paket {$packageName}";
        } else {
            $orderDetail = "{$userName} order paket {$packageName}";
        }

        $itemDetails = [
            [
                'id'            => $package['id'],
                'price'         => $originalAmount,
                'quantity'      => 1,
                'name'          => substr($package['name'], 0, 50),
                'category'      => 'Kuota Submisi AR Tilik',
                'merchant_name' => 'AR Tilik',
            ]
        ];

        if ($discountAmount > 0) {
            $itemDetails[] = [
                'id'            => 'DISC-REFERRAL-10',
                'price'         => -$discountAmount,
                'quantity'      => 1,
                'name'          => 'Diskon Sahabat Tilik 10%',
                'category'      => 'Diskon Promosi',
                'merchant_name' => 'AR Tilik',
            ];
        }

        $params = [
            'transaction_details' => [
                'order_id'     => $orderId,
                'gross_amount' => $finalAmount,
            ],
            'item_details' => $itemDetails,
            'customer_details' => [
                'first_name'       => $userName,
                'email'            => $userEmail,
                'billing_address'  => [
                    'first_name'   => $userName,
                    'email'        => $userEmail,
                    'address'      => substr($orderDetail, 0, 255),
                    'country_code' => 'IDN',
                ],
                'shipping_address' => [
                    'first_name'   => $userName,
                    'email'        => $userEmail,
                    'address'      => substr($orderDetail, 0, 255),
                    'country_code' => 'IDN',
                ],
            ],
            'custom_field1' => substr($orderDetail, 0, 255),
        ];

        try {
            $snapResult = $this->midtransService->createSnapTransaction($params);
            $snapToken = $snapResult['token'] ?? null;
            $redirectUrl = $snapResult['redirect_url'] ?? null;

            $this->transModel->where('order_id', $orderId)->set([
                'snap_token'        => $snapToken,
                'snap_redirect_url' => $redirectUrl,
                'updated_at'        => date('Y-m-d H:i:s'),
            ])->update();

            return $this->response->setJSON([
                'status'       => 'success',
                'order_id'     => $orderId,
                'snap_token'   => $snapToken,
                'redirect_url' => $redirectUrl,
                'package'      => $package,
                'order_detail' => $orderDetail,
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'Gagal membuat Snap Token Midtrans: ' . $e->getMessage());

            $this->transModel->where('order_id', $orderId)->set([
                'transaction_status' => 'cancel',
                'updated_at'         => date('Y-m-d H:i:s'),
            ])->update();

            return $this->response->setStatusCode(500)->setJSON([
                'status'  => 'error',
                'message' => 'Gagal menghubungkan ke gateway pembayaran Midtrans: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Webhook Endpoint Notifikasi Pembayaran Midtrans (Realtime Callback)
     */
    public function webhook(): ResponseInterface
    {
        $rawPayload = file_get_contents('php://input');
        $notif = json_decode($rawPayload, true);

        if (!$notif || empty($notif['order_id'])) {
            log_message('error', 'Midtrans Webhook: Payload kosong atau tidak valid.');
            return $this->response->setStatusCode(400)->setJSON([
                'status'  => 'error',
                'message' => 'Invalid JSON payload',
            ]);
        }

        $orderId           = (string) $notif['order_id'];
        $statusCode        = (string) ($notif['status_code'] ?? '');
        $grossAmount       = (string) ($notif['gross_amount'] ?? '');
        $receivedSignature = (string) ($notif['signature_key'] ?? '');
        $transactionStatus = (string) ($notif['transaction_status'] ?? '');
        $fraudStatus       = (string) ($notif['fraud_status'] ?? '');
        $paymentType       = (string) ($notif['payment_type'] ?? 'unknown');

        // 1. Validasi Keaslian Signature Midtrans (HMAC-SHA512)
        if (!$this->midtransService->verifyWebhookSignature($orderId, $statusCode, $grossAmount, $receivedSignature)) {
            log_message('warning', "Midtrans Webhook: Signature mismatch untuk Order {$orderId}");
            return $this->response->setStatusCode(403)->setJSON([
                'status'  => 'error',
                'message' => 'Signature mismatch',
            ]);
        }

        $transaction = $this->transModel->findByOrderId($orderId);
        if (!$transaction) {
            log_message('warning', "Midtrans Webhook: Order ID {$orderId} tidak terdaftar di sistem.");
            return $this->response->setStatusCode(404)->setJSON([
                'status'  => 'error',
                'message' => 'Order not found',
            ]);
        }

        // 2. Verifikasi Konsistensi Nominal (Gross Amount) terhadap Database
        if (abs((float) $grossAmount - (float) $transaction['amount']) > 0.01) {
            log_message('critical', "Midtrans Webhook: Gross amount mismatch untuk Order {$orderId}. DB: {$transaction['amount']}, Webhook: {$grossAmount}");
            return $this->response->setStatusCode(400)->setJSON([
                'status'  => 'error',
                'message' => 'Gross amount mismatch',
            ]);
        }

        // Idempotensi awal: Jika transaksi di memori sudah selesai, beri respons cepat
        if (in_array($transaction['transaction_status'], ['settlement', 'capture'], true) && !in_array($transactionStatus, ['refund', 'chargeback'], true)) {
            return $this->response->setJSON([
                'status'   => 'already_settled',
                'order_id' => $orderId,
            ]);
        }

        $isSuccess = ($transactionStatus === 'settlement') || ($transactionStatus === 'capture' && $fraudStatus === 'accept');

        if ($isSuccess) {
            $db = \Config\Database::connect();
            $paidAt = date('Y-m-d H:i:s');

            $db->transStart();

            // 3. Transisi status atomik: hanya ubah jika belum berstatus settlement/capture
            $db->query("
                UPDATE payment_transactions
                SET transaction_status = 'settlement',
                    fraud_status = ?,
                    payment_type = ?,
                    midtrans_response = ?,
                    paid_at = ?,
                    updated_at = ?
                WHERE id = ? AND transaction_status NOT IN ('settlement', 'capture')
            ", [$fraudStatus, $paymentType, $rawPayload, $paidAt, $paidAt, $transaction['id']]);

            $affectedRows = $db->affectedRows();

            if ($affectedRows === 1) {
                // Kueri ini memperoleh lock atomik dan berhak mengkreditkan kuota
                $user = $db->table('users')->where('id', $transaction['user_id'])->get()->getRowArray();
                if ($user) {
                    $submissionsToAdd = (int) $transaction['submissions_added'];
                    if ($submissionsToAdd > 0) {
                        $db->query("
                            UPDATE users
                            SET bonus_submissions = bonus_submissions + ?,
                                updated_at = ?
                            WHERE id = ?
                        ", [$submissionsToAdd, $paidAt, $user['id']]);
                    }

                    // Perpanjang masa aktif langganan jika paket langganan
                    if (!empty($transaction['subscription_tier'])) {
                        $days = (int) $transaction['subscription_days'] ?: 30;
                        $currentExpires = !empty($user['subscription_expires_at']) ? strtotime($user['subscription_expires_at']) : 0;
                        $baseTime = ($currentExpires > time()) ? $currentExpires : time();
                        $newExpiresAt = date('Y-m-d H:i:s', $baseTime + ($days * 86400));

                        $db->table('users')->where('id', $user['id'])->update([
                            'subscription_tier'       => $transaction['subscription_tier'],
                            'subscription_expires_at' => $newExpiresAt,
                            'updated_at'              => $paidAt,
                        ]);
                    }
                }

                $db->transComplete();

                if ($db->transStatus() === false) {
                    log_message('critical', "Midtrans Webhook: Database transaction gagal untuk Order {$orderId}");
                    return $this->response->setStatusCode(500)->setJSON([
                        'status'  => 'error',
                        'message' => 'Database transaction failed',
                    ]);
                }

                // Berikan reward Member-Get-Member atas transaksi perdana (+5 kuota cek gratis ke pengundang)
                try {
                    $referralModel = new \App\Models\ReferralModel();
                    $referralModel->awardPurchaseBonus((int) $transaction['id']);
                } catch (\Throwable $re) {
                    log_message('error', 'Gagal memproses bonus referral purchase: ' . $re->getMessage());
                }

                // Kirim notifikasi instan ke Telegram Bot
                try {
                    if ($user) {
                        $telegram = new TelegramNotifier();
                        $formattedAmount = 'Rp ' . number_format($transaction['amount'], 0, ',', '.');
                        $msg = "💳 <b>PEMBAYARAN AR TILIK BERHASIL</b>\n\n"
                             . "• <b>Order ID:</b> <code>{$orderId}</code>\n"
                             . "• <b>Pelanggan:</b> " . htmlspecialchars($user['name']) . " (" . htmlspecialchars($user['email']) . ")\n"
                             . "• <b>Paket:</b> {$transaction['package_name']}\n"
                             . "• <b>Nominal:</b> {$formattedAmount}\n"
                             . "• <b>Metode:</b> {$paymentType}\n"
                             . "• <b>Kredit Diberikan:</b> +{$transaction['submissions_added']} Submisi\n"
                             . "• <b>Waktu:</b> {$paidAt}";
                        $telegram->sendMessage($msg);
                    }
                } catch (\Throwable $te) {
                    log_message('warning', 'Gagal mengirim notif Telegram: ' . $te->getMessage());
                }

                return $this->response->setJSON([
                    'status'   => 'settlement_success',
                    'order_id' => $orderId,
                ]);
            } else {
                // Webhook konkruen lain telah menyelesaikan transaksi ini lebih awal
                $db->transComplete();
                return $this->response->setJSON([
                    'status'   => 'already_settled',
                    'order_id' => $orderId,
                ]);
            }
        } elseif (in_array($transactionStatus, ['refund', 'chargeback'], true)) {
            $db = \Config\Database::connect();
            $db->transStart();

            $curr = $db->table('payment_transactions')->where('id', $transaction['id'])->get()->getRowArray();
            if ($curr && in_array($curr['transaction_status'], ['settlement', 'capture'], true)) {
                // Transaksi sebelumnya sudah settled, maka tarik kembali kuota & tier langganan
                $submissionsToRevoke = (int) $transaction['submissions_added'];
                if ($submissionsToRevoke > 0) {
                    $db->query("
                        UPDATE users
                        SET bonus_submissions = GREATEST(0, CAST(bonus_submissions AS SIGNED) - ?),
                            updated_at = NOW()
                        WHERE id = ?
                    ", [$submissionsToRevoke, $transaction['user_id']]);
                }

                if (!empty($transaction['subscription_tier'])) {
                    $db->table('users')->where('id', $transaction['user_id'])->update([
                        'subscription_tier'       => 'free',
                        'subscription_expires_at' => null,
                        'updated_at'              => date('Y-m-d H:i:s'),
                    ]);
                }
            }

            $db->table('payment_transactions')->where('id', $transaction['id'])->update([
                'transaction_status' => $transactionStatus,
                'midtrans_response'  => $rawPayload,
                'updated_at'         => date('Y-m-d H:i:s'),
            ]);

            $db->transComplete();

            return $this->response->setJSON([
                'status'   => 'transaction_' . $transactionStatus,
                'order_id' => $orderId,
            ]);
        } elseif (in_array($transactionStatus, ['cancel', 'deny', 'expire'], true)) {
            $db = \Config\Database::connect();
            $db->query("
                UPDATE payment_transactions
                SET transaction_status = ?,
                    midtrans_response = ?,
                    updated_at = NOW()
                WHERE id = ? AND transaction_status NOT IN ('settlement', 'capture')
            ", [$transactionStatus, $rawPayload, $transaction['id']]);

            return $this->response->setJSON([
                'status'   => 'transaction_' . $transactionStatus,
                'order_id' => $orderId,
            ]);
        }

        return $this->response->setJSON([
            'status'   => 'pending',
            'order_id' => $orderId,
        ]);
    }

    /**
     * Cek status transaksi oleh frontend pengguna
     */
    public function checkStatus(string $orderId): ResponseInterface
    {
        $transaction = $this->transModel->findByOrderId($orderId);
        if (!$transaction) {
            return $this->response->setStatusCode(404)->setJSON([
                'status'  => 'error',
                'message' => 'Transaksi tidak ditemukan',
            ]);
        }

        $userId = session()->get('user_id');
        $isAdmin = (session()->get('user_role') === 'admin');

        // Pastikan hanya pemilik transaksi atau admin yang berhak memeriksa status pesanan
        if (! $userId || ((int)$transaction['user_id'] !== (int)$userId && ! $isAdmin)) {
            return $this->response->setStatusCode(404)->setJSON([
                'status'  => 'error',
                'message' => 'Transaksi tidak ditemukan atau akses ditolak.',
            ]);
        }

        $user = $this->userModel->find($userId);
        $quotaInfo = $user ? $this->userModel->getQuotaInfo($user) : null;

        return $this->response->setJSON([
            'status'             => 'success',
            'order_id'           => $transaction['order_id'],
            'transaction_status' => $transaction['transaction_status'],
            'is_paid'            => in_array($transaction['transaction_status'], ['settlement', 'capture'], true),
            'package_name'       => $transaction['package_name'],
            'amount'             => (float) $transaction['amount'],
            'paid_at'            => $transaction['paid_at'],
            'quota_info'         => $quotaInfo,
        ]);
    }

    /**
     * Riwayat pembelian kuota pengguna yang sedang login
     */
    public function history(): ResponseInterface
    {
        $userId = session()->get('user_id');
        if (!$userId) {
            return $this->response->setStatusCode(401)->setJSON([
                'status'  => 'error',
                'message' => 'Unauthorized',
            ]);
        }

        $history = $this->transModel->getUserTransactions($userId, 20);

        return $this->response->setJSON([
            'status'  => 'success',
            'history' => $history,
        ]);
    }
}
