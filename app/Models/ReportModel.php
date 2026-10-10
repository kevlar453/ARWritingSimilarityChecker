<?php

namespace App\Models;

use CodeIgniter\Model;

class ReportModel extends Model
{
    protected $table = 'reports';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'document_id',
        'engine',
        'predicted_decision',
        'evidence_pattern',
        'evidence_agreement',
        'sources_agree',
        'rationale',
        'exact_similarity',
        'cosine_similarity',
        'shared_terms_count',
        'average_shared_term_idf',
        'evidence_data',
        'overall_similarity',
        'source_count',
        'match_count',
        'longest_match_words',
        'verification_code',
        'status',
        'error_message',
        'created_at',
        'completed_at',
        'updated_at',
    ];
    protected $useTimestamps = false;
}
