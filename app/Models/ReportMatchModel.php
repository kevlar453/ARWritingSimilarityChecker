<?php

namespace App\Models;

use CodeIgniter\Model;

class ReportMatchModel extends Model
{
    protected $table = 'report_matches';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'report_id',
        'source_document_id',
        'document_page_id',
        'source_page_id',
        'submission_start',
        'submission_end',
        'source_start',
        'source_end',
        'word_count',
        'match_type',
        'similarity',
        'average_idf',
        'max_idf',
        'significance',
        'commonness',
        'matched_text',
        'created_at',
    ];
    protected $useTimestamps = false;
}
