<?php

namespace App\Models;

use CodeIgniter\Model;

class VisitorModel extends Model
{
    protected $table = 'landing_visitors';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $allowedFields = [
        'visitor_hash',
        'ip_address',
        'user_agent',
        'visit_date',
        'hits',
        'created_at',
        'updated_at',
    ];

    /**
     * Catat kunjungan unik pengunjung ke landing page
     */
    public function recordVisit(string $ip, ?string $userAgent = null): array
    {
        $db = $this->db;
        $ip = trim($ip);
        if ($ip === '') {
            $ip = '127.0.0.1';
        }

        // Hashing anonim untuk privasi data akademik (tidak menyimpan raw identity secara terbuka)
        $salt = env('VISITOR_SALT', 'artilik-visitor-counter-salt-2026');
        $visitorHash = hash('sha256', $ip . '|' . $salt);
        $today = date('Y-m-d');
        $now = date('Y-m-d H:i:s');
        $uaTrimmed = substr((string)$userAgent, 0, 500);

        try {
            // Atomic UPSERT: hitungan bertambah jika IP yang sama berkunjung kembali di hari yang sama
            $sql = "INSERT INTO landing_visitors (visitor_hash, ip_address, user_agent, visit_date, hits, created_at, updated_at)
                    VALUES (?, ?, ?, ?, 1, ?, ?)
                    ON DUPLICATE KEY UPDATE hits = hits + 1, updated_at = VALUES(updated_at)";
            $db->query($sql, [$visitorHash, $ip, $uaTrimmed, $today, $now, $now]);
        } catch (\Throwable $e) {
            log_message('error', 'VisitorModel recordVisit error: ' . $e->getMessage());
        }

        return $this->getStats();
    }

    /**
     * Mengambil statistik ringkasan pengunjung unik
     */
    public function getStats(): array
    {
        $db = $this->db;
        $today = date('Y-m-d');

        try {
            $rowTotal = $db->query("SELECT COUNT(DISTINCT visitor_hash) AS total_unique, COALESCE(SUM(hits), 0) AS total_hits FROM landing_visitors")->getRowArray();
            $rowToday = $db->query("SELECT COUNT(*) AS today_unique, COALESCE(SUM(hits), 0) AS today_hits FROM landing_visitors WHERE visit_date = ?", [$today])->getRowArray();

            $totalUnique = (int) ($rowTotal['total_unique'] ?? 0);
            $totalHits   = (int) ($rowTotal['total_hits'] ?? 0);
            $todayUnique = (int) ($rowToday['today_unique'] ?? 0);
            $todayHits   = (int) ($rowToday['today_hits'] ?? 0);
        } catch (\Throwable $e) {
            log_message('error', 'VisitorModel getStats error: ' . $e->getMessage());
            $totalUnique = 1;
            $totalHits   = 1;
            $todayUnique = 1;
            $todayHits   = 1;
        }

        return [
            'total_unique' => $totalUnique,
            'today_unique' => $todayUnique,
            'total_hits'   => $totalHits,
            'today_hits'   => $todayHits,
        ];
    }
}
