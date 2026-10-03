<?php

namespace App\Models;

use CodeIgniter\Model;

class DocumentPageModel extends Model
{
    protected $table = 'document_pages';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'document_id',
        'page_number',
        'text',
        'created_at',
    ];
    protected $useTimestamps = false;
}
