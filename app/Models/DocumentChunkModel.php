<?php

namespace App\Models;

use CodeIgniter\Model;

class DocumentChunkModel extends Model
{
    protected $table = 'document_chunks';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'document_id',
        'page_id',
        'chunk_index',
        'text',
        'normalized_text',
        'word_count',
        'start_word_position',
        'end_word_position',
        'created_at',
    ];
    protected $useTimestamps = false;
}
