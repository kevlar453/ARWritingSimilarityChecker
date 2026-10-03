<?php

namespace App\Models;

use CodeIgniter\Model;

class ReportSourceModel extends Model
{
    protected $table = 'report_sources';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'report_id',
        'source_document_id',
        'matched_shingles',
        'matched_words',
        'similarity',
        'source_rank',
        'created_at',
    ];
    protected $useTimestamps = false;
}
