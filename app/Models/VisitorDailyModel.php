<?php

namespace App\Models;

use CodeIgniter\Model;

class VisitorDailyModel extends Model
{
    protected $table = 'visitor_daily';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = false;
    protected $returnType = 'array';
    protected $allowedFields = [
        'visit_date',
        'unique_visitors',
        'total_visitors',
        'total_hits',
        'hits',
        'unique_ips',
        'created_at',
        'updated_at',
    ];

    /**
     * Mengambil rekap harian pengunjung
     */
    public function getDailySummary(int $limit = 30): array
    {
        return $this->orderBy('visit_date', 'DESC')
                    ->limit($limit)
                    ->findAll();
    }
}
