<?php

namespace App\Models;

use CodeIgniter\Model;

class TestimonialModel extends Model
{
    protected $table = 'testimonials';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $allowedFields = [
        'user_id',
        'report_id',
        'rating',
        'content',
        'author_name',
        'author_role',
        'institution',
        'is_anonymous',
        'allow_public_display',
        'is_approved',
        'is_featured',
        'ip_address',
        'created_at',
        'updated_at',
    ];

    /**
     * Menyimpan atau memperbarui ulasan/testimoni pengguna
     */
    public function submitReview(array $data): array
    {
        $now = date('Y-m-d H:i:s');
        $reportId = !empty($data['report_id']) ? (int) $data['report_id'] : null;
        $userId = !empty($data['user_id']) ? (int) $data['user_id'] : null;
        $ip = $data['ip_address'] ?? null;

        $rating = max(1, min(5, (int) ($data['rating'] ?? 5)));
        $content = trim((string) ($data['content'] ?? ''));
        $authorName = trim((string) ($data['author_name'] ?? 'Akademisi AR Tilik'));
        $authorRole = !empty($data['author_role']) ? trim((string) $data['author_role']) : 'Mahasiswa';
        $institution = !empty($data['institution']) ? trim((string) $data['institution']) : null;
        $isAnonymous = !empty($data['is_anonymous']) ? 1 : 0;
        $allowPublic = !empty($data['allow_public_display']) ? 1 : 0;

        // Cek apakah ulasan untuk laporan & user ini sudah pernah dikirim
        $existing = null;
        if ($userId && $reportId) {
            $existing = $this->where('report_id', $reportId)->where('user_id', $userId)->first();
        } elseif ($reportId && $ip) {
            $existing = $this->where('report_id', $reportId)->where('ip_address', $ip)->first();
        }

        if ($existing) {
            $updateData = [
                'rating'               => $rating,
                'content'              => $content,
                'author_name'          => $authorName,
                'author_role'          => $authorRole,
                'institution'          => $institution,
                'is_anonymous'         => $isAnonymous,
                'allow_public_display' => $allowPublic,
                'updated_at'           => $now,
            ];
            // Jika user sebelumnya pending/rejected dan kini mengedit ulasan bintang 5, reset status ke pending untuk moderasi
            if ((int) $existing['is_approved'] === -1) {
                $updateData['is_approved'] = 0;
            }

            $this->update($existing['id'], $updateData);
            return [
                'id'     => (int) $existing['id'],
                'action' => 'updated',
                'rating' => $rating,
            ];
        }

        $insertData = [
            'user_id'              => $userId,
            'report_id'            => $reportId,
            'rating'               => $rating,
            'content'              => $content,
            'author_name'          => $authorName,
            'author_role'          => $authorRole,
            'institution'          => $institution,
            'is_anonymous'         => $isAnonymous,
            'allow_public_display' => $allowPublic,
            'is_approved'          => 0, // Default pending review
            'is_featured'          => 0,
            'ip_address'           => $ip,
            'created_at'           => $now,
            'updated_at'           => $now,
        ];

        $insertedId = $this->insert($insertData, true);
        return [
            'id'     => (int) $insertedId,
            'action' => 'created',
            'rating' => $rating,
        ];
    }

    /**
     * Mengambil ulasan yang sudah dibuat pengguna untuk satu laporan
     */
    public function getReviewForReport(?int $reportId, ?int $userId = null, ?string $ipAddress = null): ?array
    {
        if (!$reportId) {
            return null;
        }

        if ($userId) {
            return $this->where('report_id', $reportId)->where('user_id', $userId)->first();
        }
        if ($ipAddress) {
            return $this->where('report_id', $reportId)->where('ip_address', $ipAddress)->first();
        }
        return null;
    }

    /**
     * Mengambil testimoni yang telah disetujui untuk ditampilkan ke publik (Landing Page)
     */
    public function getApprovedForPublic(int $limit = 6, bool $featuredOnly = false): array
    {
        $builder = $this->builder();
        $builder->where('is_approved', 1)
                ->where('allow_public_display', 1)
                ->where('LENGTH(TRIM(content)) >', 15);

        if ($featuredOnly) {
            $builder->where('is_featured', 1);
        }

        $builder->orderBy('is_featured', 'DESC')
                ->orderBy('created_at', 'DESC')
                ->limit($limit);

        $rows = $builder->get()->getResultArray();

        // Samarkan identitas jika pengguna memilih anonim
        foreach ($rows as &$r) {
            if (!empty($r['is_anonymous'])) {
                $r['display_name'] = $this->maskName($r['author_name']);
                $r['display_institution'] = $this->maskInstitution($r['institution']);
            } else {
                $r['display_name'] = $r['author_name'];
                $r['display_institution'] = $r['institution'];
            }
        }
        unset($r);

        return $rows;
    }

    /**
     * Statistik ringkasan ulasan untuk admin dan landing page
     */
    public function getStats(): array
    {
        $db = $this->db;
        $sql = "
            SELECT 
                COUNT(*) as total_reviews,
                ROUND(AVG(rating), 1) as avg_rating,
                SUM(CASE WHEN rating = 5 THEN 1 ELSE 0 END) as count_5_stars,
                SUM(CASE WHEN rating = 4 THEN 1 ELSE 0 END) as count_4_stars,
                SUM(CASE WHEN rating = 3 THEN 1 ELSE 0 END) as count_3_stars,
                SUM(CASE WHEN rating <= 2 THEN 1 ELSE 0 END) as count_low_stars,
                SUM(CASE WHEN is_approved = 1 THEN 1 ELSE 0 END) as count_approved,
                SUM(CASE WHEN is_approved = 0 THEN 1 ELSE 0 END) as count_pending,
                SUM(CASE WHEN is_approved = -1 THEN 1 ELSE 0 END) as count_rejected
            FROM testimonials
        ";
        $row = $db->query($sql)->getRowArray();
        return [
            'total_reviews'    => (int) ($row['total_reviews'] ?? 0),
            'avg_rating'       => (float) ($row['avg_rating'] ?? 5.0),
            'count_5_stars'    => (int) ($row['count_5_stars'] ?? 0),
            'count_4_stars'    => (int) ($row['count_4_stars'] ?? 0),
            'count_3_stars'    => (int) ($row['count_3_stars'] ?? 0),
            'count_low_stars'  => (int) ($row['count_low_stars'] ?? 0),
            'count_approved'   => (int) ($row['count_approved'] ?? 0),
            'count_pending'    => (int) ($row['count_pending'] ?? 0),
            'count_rejected'   => (int) ($row['count_rejected'] ?? 0),
        ];
    }

    /**
     * Menyamarkan nama demi privasi akademik (misal: "Makarius Arseto" -> "M. A***")
     */
    private function maskName(?string $name): string
    {
        $name = trim((string) $name);
        if ($name === '') {
            return 'Akademisi AR Tilik';
        }
        $parts = explode(' ', $name);
        if (count($parts) === 1) {
            return mb_substr($parts[0], 0, 1) . '***';
        }
        return mb_substr($parts[0], 0, 1) . '. ' . mb_substr($parts[1], 0, 1) . '***';
    }

    /**
     * Menyamarkan institusi (misal: "Universitas Gadjah Mada" -> "Perguruan Tinggi Negeri, Yogyakarta")
     */
    private function maskInstitution(?string $inst): string
    {
        $inst = trim((string) $inst);
        if ($inst === '') {
            return 'Institusi Akademik Terakreditasi';
        }
        if (stripos($inst, 'Universitas') !== false || stripos($inst, 'Institut') !== false) {
            return 'Perguruan Tinggi Akademik Terakreditasi';
        }
        return $inst;
    }
}
