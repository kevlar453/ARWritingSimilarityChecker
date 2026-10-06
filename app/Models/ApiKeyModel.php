<?php

namespace App\Models;

use CodeIgniter\Model;

class ApiKeyModel extends Model
{
    protected $table            = 'api_keys';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'user_id',
        'key_name',
        'key_prefix',
        'api_key',
        'system_type',
        'rate_limit_per_minute',
        'quota_limit',
        'quota_used',
        'is_active',
        'last_used_at',
        'expires_at',
        'created_at',
        'updated_at',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    /**
     * Membuat API Key baru dengan format artilik_live_[hex]
     */
    public function generateKey(int $userId, string $keyName, string $systemType = 'ojs', int $quotaLimit = 1000, int $rateLimit = 60): array
    {
        $prefix = 'artilik_' . ($systemType === 'ojs' ? 'ojs_' : 'live_');
        $randomHex = bin2hex(random_bytes(24));
        $fullKey = $prefix . $randomHex;
        $keyPrefix = substr($fullKey, 0, 14) . '...';

        $data = [
            'user_id'               => $userId,
            'key_name'              => $keyName,
            'key_prefix'            => $keyPrefix,
            'api_key'               => hash('sha256', $fullKey), // simpan hash SHA-256 untuk keamanan
            'system_type'           => $systemType,
            'rate_limit_per_minute' => $rateLimit,
            'quota_limit'           => $quotaLimit,
            'quota_used'            => 0,
            'is_active'             => 1,
            'created_at'            => date('Y-m-d H:i:s'),
            'updated_at'            => date('Y-m-d H:i:s'),
        ];

        $id = $this->insert($data);

        return [
            'id'       => $id,
            'key_name' => $keyName,
            'raw_key'  => $fullKey, // Hanya ditampilkan 1x saat generate
            'prefix'   => $keyPrefix,
            'quota'    => $quotaLimit,
        ];
    }

    /**
     * Memvalidasi API Key dari request header atau query
     */
    public function validateKey(string $rawKey): ?array
    {
        $rawKey = trim($rawKey);
        if (empty($rawKey)) {
            return null;
        }

        // Cek kecocokan hash SHA-256 atau string langsung (backward compatible)
        $hashed = hash('sha256', $rawKey);
        $key = $this->where('api_key', $hashed)
                    ->orWhere('api_key', $rawKey)
                    ->first();

        if (! $key) {
            return null;
        }

        if (! (int) $key['is_active']) {
            return null;
        }

        if (! empty($key['expires_at']) && strtotime($key['expires_at']) < time()) {
            return null;
        }

        if ((int) $key['quota_used'] >= (int) $key['quota_limit']) {
            return array_merge($key, ['quota_exceeded' => true]);
        }

        return $key;
    }

    /**
     * Mencatat pemakaian kuota dan update last_used_at
     */
    public function recordUsage(int $keyId): void
    {
        $this->db->table($this->table)
            ->where('id', $keyId)
            ->set('quota_used', 'quota_used + 1', false)
            ->set('last_used_at', date('Y-m-d H:i:s'))
            ->set('updated_at', date('Y-m-d H:i:s'))
            ->update();
    }

    /**
     * Mengambil daftar API Key milik pengguna
     */
    public function getUserKeys(int $userId): array
    {
        return $this->where('user_id', $userId)
                    ->orderBy('id', 'DESC')
                    ->findAll();
    }

    /**
     * Mencabut / menonaktifkan API Key
     */
    public function revokeKey(int $keyId, ?int $userId = null): bool
    {
        $builder = $this->where('id', $keyId);
        if ($userId !== null) {
            $builder->where('user_id', $userId);
        }
        return $this->update($keyId, [
            'is_active'  => 0,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }
}
