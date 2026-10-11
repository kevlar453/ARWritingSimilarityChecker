<?php

namespace App\Models;

use CodeIgniter\Model;

class AmbassadorPayoutModel extends Model
{
    protected $table            = 'ambassador_payouts';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'ambassador_id',
        'user_id',
        'payout_code',
        'amount',
        'payout_channel',
        'payout_account_number',
        'payout_account_name',
        'status',
        'admin_notes',
        'proof_transfer_path',
        'requested_at',
        'processed_at',
        'created_at',
        'updated_at',
    ];

    public const MINIMUM_PAYOUT = 100000.00;

    /**
     * Hitung ringkasan saldo komisi dan kelayakan penarikan (minimum Rp 100.000)
     */
    public function getFinancialSummary(int $ambassadorId, float $totalCommissionEarned): array
    {
        $db = \Config\Database::connect();
        
        // Total yang sudah dicairkan (completed)
        $completedRow = $db->table($this->table)
            ->selectSum('amount', 'total_completed')
            ->where('ambassador_id', $ambassadorId)
            ->where('status', 'completed')
            ->get()
            ->getRowArray();
        $totalCompleted = (float) ($completedRow['total_completed'] ?? 0.0);

        // Total yang sedang diproses atau pending
        $pendingRow = $db->table($this->table)
            ->selectSum('amount', 'total_pending')
            ->where('ambassador_id', $ambassadorId)
            ->whereIn('status', ['pending', 'processing'])
            ->get()
            ->getRowArray();
        $totalPending = (float) ($pendingRow['total_pending'] ?? 0.0);

        // Saldo yang benar-benar siap ditarik
        $availableBalance = max(0.0, $totalCommissionEarned - $totalCompleted - $totalPending);

        return [
            'total_earned'      => $totalCommissionEarned,
            'total_completed'   => $totalCompleted,
            'total_pending'     => $totalPending,
            'total_withdrawn'   => $totalCompleted + $totalPending,
            'available_balance' => $availableBalance,
            'min_payout'        => self::MINIMUM_PAYOUT,
            'can_withdraw'      => ($availableBalance >= self::MINIMUM_PAYOUT),
        ];
    }

    /**
     * Mengambil daftar riwayat penarikan komisi seorang duta
     */
    public function getPayouts(int $ambassadorId, int $limit = 20): array
    {
        return $this->where('ambassador_id', $ambassadorId)
            ->orderBy('id', 'DESC')
            ->findAll($limit);
    }

    /**
     * Mengajukan permintaan penarikan saldo komisi baru
     */
    public function submitPayoutRequest(array $data, float $totalCommissionEarned): array
    {
        $ambassadorId = (int) $data['ambassador_id'];
        $userId       = (int) $data['user_id'];
        $amount       = (float) ($data['amount'] ?? 0);
        $channel      = trim((string) ($data['payout_channel'] ?? ''));
        $accNumber    = trim((string) ($data['payout_account_number'] ?? ''));
        $accName      = trim((string) ($data['payout_account_name'] ?? ''));

        if ($amount < self::MINIMUM_PAYOUT) {
            return [
                'success' => false,
                'message' => 'Minimum penarikan saldo komisi adalah Rp ' . number_format(self::MINIMUM_PAYOUT, 0, ',', '.') . '.',
            ];
        }

        if (empty($channel) || empty($accNumber) || empty($accName)) {
            return [
                'success' => false,
                'message' => 'Mohon lengkapi metode pencairan (Bank/E-Wallet), nomor rekening/HP, dan nama pemilik rekening.',
            ];
        }

        // Cek saldo tersedia
        $financial = $this->getFinancialSummary($ambassadorId, $totalCommissionEarned);
        if ($amount > $financial['available_balance']) {
            return [
                'success' => false,
                'message' => 'Jumlah penarikan (Rp ' . number_format($amount, 0, ',', '.') . ') melebihi saldo komisi yang tersedia (Rp ' . number_format($financial['available_balance'], 0, ',', '.') . ').',
            ];
        }

        // Buat kode pencairan unik, misal: WD-202610-1234
        $now = date('Y-m-d H:i:s');
        $code = 'WD-' . date('Ym') . '-' . strtoupper(bin2hex(random_bytes(3)));

        $insertData = [
            'ambassador_id'         => $ambassadorId,
            'user_id'               => $userId,
            'payout_code'           => $code,
            'amount'                => $amount,
            'payout_channel'        => $channel,
            'payout_account_number' => $accNumber,
            'payout_account_name'   => $accName,
            'status'                => 'pending',
            'requested_at'          => $now,
            'created_at'            => $now,
            'updated_at'            => $now,
        ];

        $payoutId = $this->insert($insertData, true);

        // Perbarui data rekening terakhir di profil duta agar tersimpan
        $profileModel = new AmbassadorProfileModel();
        $profileModel->update($ambassadorId, [
            'payout_channel'        => $channel,
            'payout_account_number' => $accNumber,
            'payout_account_name'   => $accName,
            'updated_at'            => $now,
        ]);

        return [
            'success'           => true,
            'payout_id'         => (int) $payoutId,
            'payout_code'       => $code,
            'amount'            => $amount,
            'channel'           => $channel,
            'acc_number'        => $accNumber,
            'acc_name'          => $accName,
            'remaining_balance' => max(0.0, $financial['available_balance'] - $amount),
            'message'           => 'Permintaan penarikan komisi sebesar Rp ' . number_format($amount, 0, ',', '.') . ' berhasil dikirim! Tim AR Tilik akan memverifikasi dan mentransfer dana ke rekening Anda.',
        ];
    }
}
