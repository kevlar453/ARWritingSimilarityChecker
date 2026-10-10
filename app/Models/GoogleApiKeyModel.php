<?php

namespace App\Models;

use CodeIgniter\Model;

class GoogleApiKeyModel extends Model
{
    protected $table            = 'google_api_keys';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'key_name',
        'api_key',
        'provider',
        'model_name',
        'status',
        'rate_limited_until',
        'usage_count',
        'error_count',
        'last_error_message',
        'last_used_at',
    ];

    protected bool $allowEmptyInserts = false;

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    /**
     * Ambil seluruh kunci Google API dengan masking untuk tampilan admin
     */
    public function getAllKeys(): array
    {
        $keys = $this->orderBy('id', 'DESC')->findAll();
        $now = date('Y-m-d H:i:s');
        $ptDateToday = '';
        try {
            $ptDateToday = (new \DateTime('now', new \DateTimeZone('America/Los_Angeles')))->format('Y-m-d');
        } catch (\Throwable $e) {
            $ptDateToday = date('Y-m-d');
        }

        foreach ($keys as &$k) {
            // Auto-recovery jika rate_limited_until sudah lewat
            if ($k['status'] === 'rate_limited' && !empty($k['rate_limited_until']) && $k['rate_limited_until'] <= $now) {
                $this->update($k['id'], [
                    'status'             => 'active',
                    'rate_limited_until' => null,
                ]);
                $k['status'] = 'active';
                $k['rate_limited_until'] = null;
            }

            // Auto-recovery jika kunci exhausted sudah melewati Midnight Pacific Time (reset kuota Google)
            if ($k['status'] === 'exhausted' && !empty($k['updated_at'])) {
                try {
                    $keyPtDate = (new \DateTime($k['updated_at']))->setTimezone(new \DateTimeZone('America/Los_Angeles'))->format('Y-m-d');
                    if ($keyPtDate < $ptDateToday) {
                        $this->update($k['id'], [
                            'status'             => 'active',
                            'rate_limited_until' => null,
                            'error_count'        => 0,
                        ]);
                        $k['status'] = 'active';
                        $k['rate_limited_until'] = null;
                        $k['error_count'] = 0;
                    }
                } catch (\Throwable $e) {
                    // Abaikan kesalahan parsing tanggal
                }
            }

            // Masking API Key: AIzaSy...4xK9
            $raw = (string) $k['api_key'];
            if (strlen($raw) > 10) {
                $k['masked_key'] = substr($raw, 0, 7) . '••••••••••••' . substr($raw, -4);
            } else {
                $k['masked_key'] = '••••••••';
            }
        }
        unset($k);

        return $keys;
    }

    /**
     * Mengambil satu API Key yang siap digunakan dengan strategi Pool Cerdas (kesehatan & beban terendah)
     */
    public function getNextRoundRobinKey(): ?array
    {
        $now = date('Y-m-d H:i:s');

        // Kembalikan kunci rate_limited yang sudah melewati masa jeda / cooldown
        $this->builder()
             ->where('status', 'rate_limited')
             ->where('rate_limited_until <=', $now)
             ->update([
                 'status'             => 'active',
                 'rate_limited_until' => null,
             ]);

        // Pilih kunci aktif: error terendah -> pemakaian terendah -> waktu pakai terlama
        return $this->where('status', 'active')
                    ->groupStart()
                        ->where('rate_limited_until IS NULL', null, false)
                        ->orWhere('rate_limited_until <=', $now)
                    ->groupEnd()
                    ->orderBy('error_count', 'ASC')
                    ->orderBy('usage_count', 'ASC')
                    ->orderBy('last_used_at', 'ASC')
                    ->orderBy('id', 'ASC')
                    ->first();
    }

    /**
     * Tandai kunci berhasil digunakan
     */
    public function markUsed(int $id): bool
    {
        return $this->builder()->where('id', $id)->update([
            'usage_count'        => new \CodeIgniter\Database\RawSql('usage_count + 1'),
            'last_used_at'       => date('Y-m-d H:i:s'),
            'rate_limited_until' => null,
            'status'             => 'active',
        ]);
    }

    /**
     * Tandai kunci terkena rate-limit / kuota sesaat (429 Too Many Requests)
     */
    public function markRateLimited(int $id, int $cooldownSeconds = 60, string $errorMessage = ''): bool
    {
        return $this->builder()->where('id', $id)->update([
            'status'             => 'rate_limited',
            'error_count'        => new \CodeIgniter\Database\RawSql('error_count + 1'),
            'rate_limited_until' => date('Y-m-d H:i:s', time() + $cooldownSeconds),
            'last_error_message' => mb_substr($errorMessage, 0, 500),
        ]);
    }

    /**
     * Tandai kunci kuota harian habis total (RESOURCE_EXHAUSTED per day)
     */
    public function markExhausted(int $id, string $errorMessage = ''): bool
    {
        return $this->builder()->where('id', $id)->update([
            'status'             => 'exhausted',
            'error_count'        => new \CodeIgniter\Database\RawSql('error_count + 1'),
            'last_error_message' => mb_substr($errorMessage, 0, 500),
        ]);
    }

    /**
     * Ganti status aktif / nonaktif
     */
    public function toggleStatus(int $id): ?string
    {
        $key = $this->find($id);
        if (! $key) {
            return null;
        }

        $newStatus = ($key['status'] === 'active') ? 'inactive' : 'active';
        $this->update($id, [
            'status'             => $newStatus,
            'rate_limited_until' => null,
        ]);

        return $newStatus;
    }
}
