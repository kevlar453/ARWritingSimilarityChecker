<?php

namespace App\Models;

use CodeIgniter\Model;

class DocumentModel extends Model
{
    protected $table = 'documents';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'user_id',
        'title',
        'author',
        'original_filename',
        'mime_type',
        'file_size',
        'storage_path',
        'status',
        'document_type',
        'corpus_visibility',
        'source_type',
        'faculty',
        'department',
        'indexing_status',
        'benchmark_case_id',
        'benchmark_run_id',
        'page_count',
        'share_token',
        'is_shared',
        'created_at',
        'updated_at',
        'file_purged_at',
    ];
    protected $useTimestamps = false;
}
