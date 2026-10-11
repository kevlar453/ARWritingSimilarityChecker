<?php

namespace App\Models;

use CodeIgniter\Model;

class FeatureVoteModel extends Model
{
    protected $table = 'feature_votes';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $allowedFields = [
        'user_id',
        'report_id',
        'feature_name',
        'vote_value',
        'feedback_text',
        'ip_address',
        'created_at',
        'updated_at',
    ];

    /**
     * Mencatat atau memperbarui voting fitur (Upvote +1 / Downvote -1)
     */
    public function recordVote(
        ?int $reportId,
        string $featureName,
        int $voteValue,
        ?int $userId = null,
        ?string $feedbackText = null,
        ?string $ipAddress = null
    ): array {
        $now = date('Y-m-d H:i:s');
        $voteValue = ($voteValue >= 1) ? 1 : -1;
        $feedbackText = !empty($feedbackText) ? trim($feedbackText) : null;

        $existing = null;
        if ($userId && $reportId) {
            $existing = $this->where('report_id', $reportId)
                             ->where('feature_name', $featureName)
                             ->where('user_id', $userId)
                             ->first();
        } elseif ($reportId && $ipAddress) {
            $existing = $this->where('report_id', $reportId)
                             ->where('feature_name', $featureName)
                             ->where('ip_address', $ipAddress)
                             ->first();
        }

        if ($existing) {
            $updateData = [
                'vote_value' => $voteValue,
                'updated_at' => $now,
            ];
            if ($feedbackText !== null) {
                $updateData['feedback_text'] = $feedbackText;
            }
            if ($userId && empty($existing['user_id'])) {
                $updateData['user_id'] = $userId;
            }

            $this->update($existing['id'], $updateData);
            return [
                'id'         => (int) $existing['id'],
                'action'     => 'updated',
                'vote_value' => $voteValue,
            ];
        }

        $insertData = [
            'user_id'       => $userId,
            'report_id'     => $reportId,
            'feature_name'  => $featureName,
            'vote_value'    => $voteValue,
            'feedback_text' => $feedbackText,
            'ip_address'    => $ipAddress,
            'created_at'    => $now,
            'updated_at'    => $now,
        ];

        $insertedId = $this->insert($insertData, true);
        return [
            'id'         => (int) $insertedId,
            'action'     => 'created',
            'vote_value' => $voteValue,
        ];
    }

    /**
     * Mengambil seluruh voting yang dilakukan pengguna untuk satu laporan tertentu
     * Return array: ['audit_forensik' => 1, 'pertanyaan_sidang' => 1, ...]
     */
    public function getReportVotes(?int $reportId, ?int $userId = null, ?string $ipAddress = null): array
    {
        if (!$reportId) {
            return [];
        }

        $builder = $this->builder();
        $builder->where('report_id', $reportId);

        if ($userId) {
            $builder->where('user_id', $userId);
        } elseif ($ipAddress) {
            $builder->where('ip_address', $ipAddress);
        } else {
            return [];
        }

        $results = $builder->get()->getResultArray();
        $map = [];
        foreach ($results as $row) {
            $map[$row['feature_name']] = [
                'vote_value'    => (int) $row['vote_value'],
                'feedback_text' => $row['feedback_text'] ?? '',
            ];
        }
        return $map;
    }

    /**
     * Mengambil statistik voting per fitur untuk dasbor analitik admin
     */
    public function getFeatureStats(): array
    {
        $db = $this->db;
        $sql = "
            SELECT 
                feature_name,
                COUNT(*) as total_votes,
                SUM(CASE WHEN vote_value = 1 THEN 1 ELSE 0 END) as upvotes,
                SUM(CASE WHEN vote_value = -1 THEN 1 ELSE 0 END) as downvotes,
                ROUND((SUM(CASE WHEN vote_value = 1 THEN 1 ELSE 0 END) / COUNT(*)) * 100, 1) as satisfaction_rate
            FROM feature_votes
            GROUP BY feature_name
            ORDER BY total_votes DESC
        ";
        return $db->query($sql)->getResultArray();
    }

    /**
     * Mengambil masukan kualitatif terbaru dari pengguna
     */
    public function getRecentFeedback(int $limit = 15): array
    {
        $db = $this->db;
        $sql = "
            SELECT 
                fv.*,
                u.name as user_name,
                u.institution,
                d.title as document_title
            FROM feature_votes fv
            LEFT JOIN users u ON u.id = fv.user_id
            LEFT JOIN reports r ON r.id = fv.report_id
            LEFT JOIN documents d ON d.id = r.document_id
            WHERE fv.feedback_text IS NOT NULL AND TRIM(fv.feedback_text) != ''
            ORDER BY fv.created_at DESC
            LIMIT ?
        ";
        return $db->query($sql, [$limit])->getResultArray();
    }
}
