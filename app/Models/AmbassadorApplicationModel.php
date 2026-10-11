<?php

namespace App\Models;

use CodeIgniter\Model;

class AmbassadorApplicationModel extends Model
{
    protected $table            = 'ambassador_applications';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'user_id',
        'full_name',
        'email',
        'whatsapp_number',
        'institution',
        'major',
        'semester',
        'campus_network',
        'has_purchased',
        'status',
        'verification_token',
        'id_card_path',
        'id_card_type',
        'id_card_uploaded_at',
        'reviewed_by',
        'reviewed_at',
        'admin_notes',
        'ip_address',
        'created_at',
        'updated_at',
    ];

    /**
     * Mendaftarkan formulir Duta Kampus (Tahap 1)
     */
    public function submitApplication(array $data): array
    {
        $db = \Config\Database::connect();
        $email = trim(strtolower($data['email'] ?? ''));
        $userId = !empty($data['user_id']) ? (int) $data['user_id'] : null;

        // Cari user berdasarkan email jika user_id belum ada
        if (!$userId && !empty($email)) {
            $userRow = $db->table('users')->where('email', $email)->get()->getRowArray();
            if ($userRow) {
                $userId = (int) $userRow['id'];
            }
        }

        // Cek riwayat pembelian paket pengguna (Syarat Mutlak Duta Kampus)
        $hasPurchased = false;
        if ($userId) {
            $paidCount = $db->table('payment_transactions')
                ->where('user_id', $userId)
                ->whereIn('transaction_status', ['settlement', 'capture'])
                ->countAllResults();

            if ($paidCount > 0) {
                $hasPurchased = true;
            } else {
                // Cek subscription_tier
                $user = $db->table('users')->where('id', $userId)->get()->getRowArray();
                if ($user && !empty($user['subscription_tier']) && $user['subscription_tier'] !== 'free') {
                    $hasPurchased = true;
                }
            }
        }

        // Cek apakah pendaftar dengan email ini sudah pernah mendaftar
        $existing = $this->where('email', $email)->orderBy('id', 'DESC')->first();
        if ($existing && in_array($existing['status'], ['pending', 'reviewing', 'accepted', 'verified', 'active'])) {
            return [
                'success' => false,
                'message' => 'Email ini sudah terdaftar dalam seleksi Duta Kampus dengan status saat ini: ' . strtoupper($existing['status']) . '.',
                'application_id' => $existing['id'],
                'status' => $existing['status'],
                'verification_token' => $existing['verification_token'],
            ];
        }

        $token = bin2hex(random_bytes(24));

        $record = [
            'user_id'            => $userId,
            'full_name'          => trim($data['full_name'] ?? ''),
            'email'              => $email,
            'whatsapp_number'    => preg_replace('/[^0-9+]/', '', trim($data['whatsapp_number'] ?? '')),
            'institution'        => trim($data['institution'] ?? ''),
            'major'              => trim($data['major'] ?? ''),
            'semester'           => trim($data['semester'] ?? ''),
            'campus_network'     => trim($data['campus_network'] ?? ''),
            'has_purchased'      => $hasPurchased ? 1 : 0,
            'status'             => 'pending',
            'verification_token' => $token,
            'ip_address'         => $data['ip_address'] ?? null,
            'created_at'         => date('Y-m-d H:i:s'),
            'updated_at'         => date('Y-m-d H:i:s'),
        ];

        $appId = $this->insert($record);

        return [
            'success'            => true,
            'application_id'     => $appId,
            'has_purchased'      => $hasPurchased,
            'verification_token' => $token,
            'message'            => 'Pendaftaran berhasil dikirim! Tim AR Tilik akan meninjau data Anda dalam batch mingguan.',
        ];
    }

    /**
     * Cari berkas berdasarkan token verifikasi KTM (Tahap 3)
     */
    public function findByToken(string $token): ?array
    {
        return $this->where('verification_token', $token)->first();
    }

    /**
     * Simpan unggahan KTM/KTP (Tahap 3)
     */
    public function saveIdCard(int $id, string $filePath, string $idType = 'ktm'): bool
    {
        return $this->update($id, [
            'id_card_path'        => $filePath,
            'id_card_type'        => $idType,
            'id_card_uploaded_at' => date('Y-m-d H:i:s'),
            'status'              => 'verified',
            'updated_at'          => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Ambil ringkasan statistik Duta Kampus untuk Admin
     */
    public function getAdminStats(): array
    {
        $db = \Config\Database::connect();
        $totalApps = $this->countAllResults();
        $pending = $this->where('status', 'pending')->countAllResults();
        $accepted = $this->where('status', 'accepted')->countAllResults();
        $verified = $this->where('status', 'verified')->countAllResults();
        $active = $db->table('ambassador_profiles')->where('status', 'active')->countAllResults();
        $probation = $db->table('ambassador_profiles')->where('status', 'probation')->countAllResults();

        return [
            'total_applications' => $totalApps,
            'pending_review'     => $pending,
            'accepted_wait_ktm'  => $accepted,
            'verified_ready_act' => $verified,
            'active_ambassadors' => $active,
            'probation_count'    => $probation,
        ];
    }
}
