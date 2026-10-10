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
}
