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
        return $this->response->setJSON([
            'status'     => 'success',
            'client_key' => $this->midtransService->getClientKey(),
            'packages'   => array_values(MidtransService::getPackages()),
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

        // Buat Order ID unik dengan prefix resmi AR Tilik: TILIK-{timestamp}-{random}
        $orderId = 'TILIK-' . date('YmdHis') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 5));

        // Catat transaksi pending di database
        $this->transModel->insert([
            'user_id'            => (int) $user['id'],
            'order_id'           => $orderId,
            'package_id'         => $package['id'],
            'package_name'       => $package['name'],
            'amount'             => (float) $package['amount'],
            'submissions_added'  => (int) $package['submissions'],
            'subscription_tier'  => $package['tier'],
            'subscription_days'  => (int) $package['days'],
            'payment_type'       => null,
            'transaction_status' => 'pending',
            'fraud_status'       => null,
            'created_at'         => date('Y-m-d H:i:s'),
            'updated_at'         => date('Y-m-d H:i:s'),
        ]);

        $params = [
            'transaction_details' => [
                'order_id'     => $orderId,
                'gross_amount' => (int) $package['amount'],
            ],
            'item_details' => [
                [
                    'id'       => $package['id'],
                    'price'    => (int) $package['amount'],
                    'quantity' => 1,
                    'name'     => substr($package['name'], 0, 50),
                ]
            ],
            'customer_details' => [
                'first_name' => $user['name'],
                'email'      => $user['email'],
            ],
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

        // Validasi Keaslian Signature Midtrans
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

        // Idempotensi: Jika transaksi sudah sukses, hindari penambahan kuota ganda
        if (in_array($transaction['transaction_status'], ['settlement', 'capture'], true)) {
            return $this->response->setJSON([
                'status'   => 'already_settled',
                'order_id' => $orderId,
            ]);
        }

        $isSuccess = ($transactionStatus === 'settlement') || ($transactionStatus === 'capture' && $fraudStatus === 'accept');

        if ($isSuccess) {
            $paidAt = date('Y-m-d H:i:s');

            // 1. Perbarui status transaksi
            $this->transModel->where('id', $transaction['id'])->set([
                'transaction_status' => 'settlement',
                'fraud_status'       => $fraudStatus,
                'payment_type'       => $paymentType,
                'midtrans_response'  => $rawPayload,
                'paid_at'            => $paidAt,
                'updated_at'         => $paidAt,
            ])->update();

            // 2. Kreditkan kuota dan paket langganan ke pengguna
            $user = $this->userModel->find($transaction['user_id']);
            if ($user) {
                $newBonus = (int) $user['bonus_submissions'] + (int) $transaction['submissions_added'];
                $updateUserData = [
                    'bonus_submissions' => $newBonus,
                    'updated_at'        => $paidAt,
                ];

                // Jika paket memiliki tier langganan bulanan
                if (!empty($transaction['subscription_tier'])) {
                    $days = (int) $transaction['subscription_days'] ?: 30;
                    $currentExpires = !empty($user['subscription_expires_at']) ? strtotime($user['subscription_expires_at']) : 0;
                    $baseTime = ($currentExpires > time()) ? $currentExpires : time();
                    $newExpiresAt = date('Y-m-d H:i:s', $baseTime + ($days * 86400));

                    $updateUserData['subscription_tier'] = $transaction['subscription_tier'];
                    $updateUserData['subscription_expires_at'] = $newExpiresAt;
                }

                $this->userModel->update($user['id'], $updateUserData);

                // 3. Kirim notifikasi instan ke Telegram Bot
                try {
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
                } catch (\Throwable $te) {
                    log_message('warning', 'Gagal mengirim notif Telegram: ' . $te->getMessage());
                }
            }

            return $this->response->setJSON([
                'status'   => 'settlement_success',
                'order_id' => $orderId,
            ]);
        } elseif (in_array($transactionStatus, ['cancel', 'deny', 'expire'], true)) {
            $this->transModel->where('id', $transaction['id'])->set([
                'transaction_status' => $transactionStatus,
                'midtrans_response'  => $rawPayload,
                'updated_at'         => date('Y-m-d H:i:s'),
            ])->update();

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
        $user = $userId ? $this->userModel->find($userId) : null;
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
