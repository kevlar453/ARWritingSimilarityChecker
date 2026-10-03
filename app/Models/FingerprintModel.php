<?php

namespace App\Models;

use CodeIgniter\Model;

class FingerprintModel extends Model
{
    protected $table = 'fingerprints';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'document_id',
        'chunk_id',
        'fingerprint',
        'position',
        'created_at',
    ];
    protected $useTimestamps = false;
}
