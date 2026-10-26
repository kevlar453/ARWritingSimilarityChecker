<?php

namespace App\Models;

use CodeIgniter\Model;

class PaymentTransactionModel extends Model
{
    protected $table            = 'payment_transactions';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'user_id',
        'order_id',
        'package_id',
        'package_name',
        'amount',
        'submissions_added',
        'subscription_tier',
        'subscription_days',
        'payment_type',
        'transaction_status',
        'fraud_status',
        'snap_token',
        'snap_redirect_url',
        'midtrans_response',
        'paid_at',
        'created_at',
        'updated_at',
    ];

    protected $useTimestamps = false;

    /**
     * Cari transaksi berdasarkan order_id
     */
    public function findByOrderId(string $orderId): ?array
    {
        return $this->where('order_id', $orderId)->first();
    }

    /**
     * Ambil riwayat transaksi untuk pengguna tertentu
     */
    public function getUserTransactions(int $userId, int $limit = 20): array
    {
        return $this->where('user_id', $userId)
            ->orderBy('id', 'DESC')
            ->findAll($limit);
    }

    /**
     * Ambil semua transaksi untuk Admin Portal
     */
    public function getAllTransactions(int $limit = 50, int $offset = 0): array
    {
        return $this->select('payment_transactions.*, users.name as user_name, users.email as user_email, users.institution as user_institution')
            ->join('users', 'users.id = payment_transactions.user_id', 'left')
            ->orderBy('payment_transactions.id', 'DESC')
            ->findAll($limit, $offset);
    }

    /**
     * Statistik ringkasan pendapatan & pembayaran untuk Admin Portal
     */
    public function getPaymentStats(): array
    {
        $db = \Config\Database::connect();
        $builder = $db->table($this->table);

        $totalRevenue = (float) ($builder->selectSum('amount')
            ->whereIn('transaction_status', ['settlement', 'capture'])
            ->get()->getRow()->amount ?? 0);

        $settledCount = $db->table($this->table)
            ->whereIn('transaction_status', ['settlement', 'capture'])
            ->countAllResults();

        $pendingCount = $db->table($this->table)
            ->where('transaction_status', 'pending')
            ->countAllResults();

        $totalTransactions = $db->table($this->table)->countAllResults();

        return [
            'total_revenue'      => $totalRevenue,
            'settled_count'      => $settledCount,
            'pending_count'      => $pendingCount,
            'total_transactions' => $totalTransactions,
        ];
    }

    /**
     * Membatalkan transaksi pending yang telah melampaui batas waktu kedaluwarsa secara otomatis
     *
     * @param int $olderThanHours Batas usia pending dalam jam (default: 24 jam)
     * @return array Ringkasan hasil pemrosesan: ['total_checked', 'expired_count', 'settled_count', 'details']
     */
    public function autoExpirePendingTransactions(int $olderThanHours = 24): array
    {
        $cutoffTime = date('Y-m-d H:i:s', strtotime("-{$olderThanHours} hours"));
        $staleOrders = $this->where('transaction_status', 'pending')
                            ->where('created_at <=', $cutoffTime)
                            ->findAll();

        $totalChecked = count($staleOrders);
        $expiredCount = 0;
        $settledCount = 0;
        $details      = [];

        if ($totalChecked === 0) {
            return [
                'total_checked' => 0,
                'expired_count' => 0,
                'settled_count' => 0,
                'details'       => [],
            ];
        }

        $midtransService = new \App\Libraries\MidtransService();
        $db = \Config\Database::connect();

        foreach ($staleOrders as $order) {
            $orderId = $order['order_id'];
            $midtransStatus = null;

            try {
                $midtransStatus = $midtransService->getTransactionStatus($orderId);
            } catch (\Throwable $e) {
                log_message('error', "Gagal menghubungi Midtrans untuk Order {$orderId}: " . $e->getMessage());
            }

            if ($midtransStatus && !empty($midtransStatus['transaction_status'])) {
                $mStatus = (string) $midtransStatus['transaction_status'];
                $fraud = (string) ($midtransStatus['fraud_status'] ?? '');

                if ($mStatus === 'settlement' || ($mStatus === 'capture' && $fraud === 'accept')) {
                    // Pengguna telah membayar di Midtrans, selesaikan transaksi secara resmi
                    $this->settleTransaction((int) $order['id'], $midtransStatus);
                    $settledCount++;
                    $details[] = [
                        'order_id' => $orderId,
                        'status'   => 'settlement',
                        'message'  => 'Telah dibayar di Midtrans, kuota berhasil ditambahkan.',
                    ];
                    continue;
                }

                if (in_array($mStatus, ['expire', 'cancel', 'deny'], true)) {
                    $this->update($order['id'], [
                        'transaction_status' => $mStatus,
                        'midtrans_response'  => json_encode($midtransStatus),
                        'updated_at'         => date('Y-m-d H:i:s'),
                    ]);
                    $expiredCount++;
                    $details[] = [
                        'order_id' => $orderId,
                        'status'   => $mStatus,
                        'message'  => "Status diselaraskan dengan Midtrans ({$mStatus}).",
                    ];
                    continue;
                }

                if ($mStatus === 'pending') {
                    // Coba panggil expire API ke Midtrans
                    try {
                        $midtransService->expireTransaction($orderId);
                    } catch (\Throwable $e) {
                        // Lanjutkan jika API error
                    }

                    $this->update($order['id'], [
                        'transaction_status' => 'expire',
                        'midtrans_response'  => json_encode($midtransStatus),
                        'updated_at'         => date('Y-m-d H:i:s'),
                    ]);
                    $expiredCount++;
                    $details[] = [
                        'order_id' => $orderId,
                        'status'   => 'expire',
                        'message'  => "Kedaluwarsa otomatis setelah {$olderThanHours} jam.",
                    ];
                    continue;
                }
            }

            // Jika Midtrans 404 (tidak pernah memilih metode bayar di Snap) atau null:
            // Karena usia order > $olderThanHours jam, tandai transaksi sebagai kedaluwarsa (expire)
            $this->update($order['id'], [
                'transaction_status' => 'expire',
                'midtrans_response'  => json_encode([
                    'note'          => "Dibatalkan/kedaluwarsa otomatis oleh sistem karena melewati batas {$olderThanHours} jam tanpa pembayaran.",
                    'auto_expired_at' => date('Y-m-d H:i:s'),
                ]),
                'updated_at'         => date('Y-m-d H:i:s'),
            ]);
            $expiredCount++;
            $details[] = [
                'order_id' => $orderId,
                'status'   => 'expire',
                'message'  => "Kedaluwarsa otomatis (Snap tidak diselesaikan dalam {$olderThanHours} jam).",
            ];
        }

        return [
            'total_checked' => $totalChecked,
            'expired_count' => $expiredCount,
            'settled_count' => $settledCount,
            'details'       => $details,
        ];
    }

    /**
     * Membatalkan transaksi pending tertentu secara manual atau melalui permintaan pengguna/admin
     */
    public function cancelPendingTransaction(string $orderId, string $cancelledBy = 'admin'): array
    {
        $order = $this->findByOrderId($orderId);
        if (!$order) {
            return [
                'success' => false,
                'message' => 'Transaksi tidak ditemukan.',
            ];
        }

        if ($order['transaction_status'] !== 'pending') {
            return [
                'success' => false,
                'message' => "Transaksi tidak dapat dibatalkan karena berstatus '{$order['transaction_status']}'.",
            ];
        }

        $midtransService = new \App\Libraries\MidtransService();

        // Cek status ke Midtrans terlebih dahulu untuk memastikan belum terbayar
        try {
            $midtransStatus = $midtransService->getTransactionStatus($orderId);
            if ($midtransStatus && !empty($midtransStatus['transaction_status'])) {
                $mStatus = (string) $midtransStatus['transaction_status'];
                $fraud = (string) ($midtransStatus['fraud_status'] ?? '');
                if ($mStatus === 'settlement' || ($mStatus === 'capture' && $fraud === 'accept')) {
                    $this->settleTransaction((int) $order['id'], $midtransStatus);
                    return [
                        'success' => false,
                        'message' => 'Transaksi telah lunas di Midtrans dan tidak dapat dibatalkan. Kuota telah dikreditkan.',
                    ];
                }
            }
            // Kirim pembatalan ke Midtrans
            $midtransService->cancelTransaction($orderId);
        } catch (\Throwable $e) {
            log_message('warning', "Gagal membatalkan transaksi {$orderId} di Midtrans: " . $e->getMessage());
        }

        $this->update($order['id'], [
            'transaction_status' => 'cancel',
            'midtrans_response'  => json_encode([
                'cancelled_by' => $cancelledBy,
                'cancelled_at' => date('Y-m-d H:i:s'),
            ]),
            'updated_at'         => date('Y-m-d H:i:s'),
        ]);

        return [
            'success' => true,
            'message' => "Transaksi {$orderId} berhasil dibatalkan.",
        ];
    }

    /**
     * Menyinkronkan status satu transaksi secara real-time dengan API Midtrans
     */
    public function syncTransactionStatus(string $orderId): array
    {
        $order = $this->findByOrderId($orderId);
        if (!$order) {
            return [
                'success' => false,
                'message' => 'Transaksi tidak ditemukan.',
            ];
        }

        $midtransService = new \App\Libraries\MidtransService();
        $midtransStatus = null;

        try {
            $midtransStatus = $midtransService->getTransactionStatus($orderId);
        } catch (\Throwable $e) {
            log_message('error', "Gagal sync Midtrans untuk Order {$orderId}: " . $e->getMessage());
        }

        if ($midtransStatus && !empty($midtransStatus['transaction_status'])) {
            $mStatus = (string) $midtransStatus['transaction_status'];
            $fraud = (string) ($midtransStatus['fraud_status'] ?? '');

            if ($mStatus === 'settlement' || ($mStatus === 'capture' && $fraud === 'accept')) {
                $this->settleTransaction((int) $order['id'], $midtransStatus);
                $updatedOrder = $this->find($order['id']);
                return [
                    'success'            => true,
                    'transaction_status' => 'settlement',
                    'message'            => 'Pembayaran telah lunas di Midtrans. Kuota telah dikreditkan.',
                    'transaction'        => $updatedOrder,
                ];
            }

            if (in_array($mStatus, ['expire', 'cancel', 'deny'], true)) {
                $this->update($order['id'], [
                    'transaction_status' => $mStatus,
                    'midtrans_response'  => json_encode($midtransStatus),
                    'updated_at'         => date('Y-m-d H:i:s'),
                ]);
                $updatedOrder = $this->find($order['id']);
                return [
                    'success'            => true,
                    'transaction_status' => $mStatus,
                    'message'            => "Status transaksi diperbarui menjadi '{$mStatus}'.",
                    'transaction'        => $updatedOrder,
                ];
            }
        }

        // Jika di Midtrans tidak ditemukan / 404, dan usia order sudah lebih dari 24 jam:
        $createdAt = strtotime($order['created_at']);
        if ((time() - $createdAt) > 86400 && $order['transaction_status'] === 'pending') {
            $this->update($order['id'], [
                'transaction_status' => 'expire',
                'midtrans_response'  => json_encode([
                    'note'          => 'Kedaluwarsa otomatis karena melebihi 24 jam tanpa pembayaran di Midtrans.',
                    'auto_expired_at' => date('Y-m-d H:i:s'),
                ]),
                'updated_at'         => date('Y-m-d H:i:s'),
            ]);
            $updatedOrder = $this->find($order['id']);
            return [
                'success'            => true,
                'transaction_status' => 'expire',
                'message'            => 'Transaksi telah kedaluwarsa karena tidak diselesaikan dalam 24 jam.',
                'transaction'        => $updatedOrder,
            ];
        }

        return [
            'success'            => true,
            'transaction_status' => $order['transaction_status'],
            'message'            => "Status transaksi saat ini: {$order['transaction_status']}.",
            'transaction'        => $order,
        ];
    }

    /**
     * Selesaikan transaksi (settlement atomik): berikan kuota, tier langganan, bonus referral & notifikasi Telegram
     */
    public function settleTransaction(int $transactionId, ?array $midtransData = null, ?string $rawPayload = null): bool
    {
        $transaction = $this->find($transactionId);
        if (!$transaction || in_array($transaction['transaction_status'], ['settlement', 'capture'], true)) {
            return false;
        }

        $db = \Config\Database::connect();
        $paidAt = date('Y-m-d H:i:s');
        $fraudStatus = (string) ($midtransData['fraud_status'] ?? '');
        $paymentType = (string) ($midtransData['payment_type'] ?? 'midtrans');
        $payloadStr = $rawPayload ?? ($midtransData ? json_encode($midtransData) : null);

        $db->transStart();

        $db->query("
            UPDATE payment_transactions
            SET transaction_status = 'settlement',
                fraud_status = ?,
                payment_type = ?,
                midtrans_response = ?,
                paid_at = ?,
                updated_at = ?
            WHERE id = ? AND transaction_status NOT IN ('settlement', 'capture')
        ", [$fraudStatus, $paymentType, $payloadStr, $paidAt, $paidAt, $transaction['id']]);

        $affected = $db->affectedRows();
        if ($affected !== 1) {
            $db->transRollback();
            return false;
        }

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
            return false;
        }

        // Bonus Referral Member-Get-Member
        try {
            $referralModel = new \App\Models\ReferralModel();
            $referralModel->awardPurchaseBonus((int) $transaction['id']);
        } catch (\Throwable $re) {
            log_message('error', 'Gagal memproses bonus referral purchase: ' . $re->getMessage());
        }

        // Notifikasi Telegram
        try {
            if ($user) {
                $telegram = new \App\Libraries\TelegramNotifier();
                $formattedAmount = 'Rp ' . number_format($transaction['amount'], 0, ',', '.');
                $msg = "💳 <b>PEMBAYARAN AR TILIK BERHASIL</b>\n\n"
                     . "• <b>Order ID:</b> <code>{$transaction['order_id']}</code>\n"
                     . "• <b>Pelanggan:</b> " . htmlspecialchars($user['name']) . " (" . htmlspecialchars($user['email']) . ")\n"
                     . "• <b>Paket:</b> {$transaction['package_name']}\n"
                     . "• <b>Nominal:</b> {$formattedAmount}\n"
                     . "• <b>Metode:</b> {$paymentType}\n"
                     . "• <b>Kredit Diberikan:</b> +{$transaction['submissions_added']} Submisi\n"
                     . "• <b>Waktu:</b> {$paidAt}";
                $telegram->sendMessage($msg);
            }
        } catch (\Throwable $te) {
            log_message('warning', 'Gagal notif Telegram: ' . $te->getMessage());
        }

        return true;
    }
}
