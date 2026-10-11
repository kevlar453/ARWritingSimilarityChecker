<?php

namespace App\Models;

use CodeIgniter\Model;

class AmbassadorProofModel extends Model
{
    protected $table            = 'ambassador_activity_proofs';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'ambassador_id',
        'month_period',
        'proof_type',
        'title',
        'description',
        'file_path',
        'url_link',
        'status',
        'created_at',
    ];

    /**
     * Menyimpan unggahan bukti aktivitas promosi bulanan (Tahap 5 & 6)
     */
    public function submitProof(array $data): int
    {
        $record = [
            'ambassador_id' => (int) $data['ambassador_id'],
            'month_period'  => $data['month_period'] ?? date('Y-m'),
            'proof_type'    => $data['proof_type'] ?? 'story_wa',
            'title'         => trim($data['title'] ?? 'Promosi Kampus'),
            'description'   => !empty($data['description']) ? trim($data['description']) : null,
            'file_path'     => $data['file_path'] ?? null,
            'url_link'      => $data['url_link'] ?? null,
            'status'        => 'pending',
            'created_at'    => date('Y-m-d H:i:s'),
        ];

        return $this->insert($record);
    }

    /**
     * Ambil daftar bukti promosi duta pada periode tertentu
     */
    public function getProofs(int $ambassadorId, ?string $monthPeriod = null): array
    {
        $builder = $this->where('ambassador_id', $ambassadorId);
        if ($monthPeriod) {
            $builder->where('month_period', $monthPeriod);
        }
        return $builder->orderBy('id', 'DESC')->findAll();
    }
}
