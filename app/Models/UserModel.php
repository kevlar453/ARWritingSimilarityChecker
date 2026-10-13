<?php

namespace App\Models;

use CodeIgniter\Model;

class UserModel extends Model
{
    protected $table = 'users';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'name',
        'email',
        'password_hash',
        'institution',
        'role',
        'subscription_tier',
        'subscription_expires_at',
        'status',
        'verification_token',
        'verification_token_expires_at',
        'email_verified_at',
        'reset_password_token',
        'reset_password_expires_at',
        'free_submissions_used_this_week',
        'week_reset_at',
        'bonus_submissions',
        'referred_by_code',
        'uploaded_corpus_count',
        'last_login_at',
        'created_at',
        'updated_at',
    ];
    protected $useTimestamps = false;

    /**
     * Memeriksa dan memperbarui masa berlaku kuota gratis mingguan (reset tiap Senin 00:00 WIB)
     */
    public function refreshWeeklyQuota(array $user): array
    {
        $now = time();
        $resetTime = !empty($user['week_reset_at']) ? strtotime($user['week_reset_at']) : 0;

        if ($now >= $resetTime) {
            $newResetAt = date('Y-m-d 00:00:00', strtotime('next monday'));
            $nowStr = date('Y-m-d H:i:s');

            $this->db->query("
                UPDATE {$this->table}
                SET free_submissions_used_this_week = 0,
                    week_reset_at = ?,
                    updated_at = ?
                WHERE id = ? AND (week_reset_at <= ? OR week_reset_at IS NULL)
            ", [$newResetAt, $nowStr, $user['id'], $nowStr]);

            $user['free_submissions_used_this_week'] = 0;
            $user['week_reset_at'] = $newResetAt;
        }

        return $user;
    }

    /**
     * Mendapatkan rincian statistik kuota dan progres kontribusi korpus
     */
    public function getQuotaInfo($user): array
    {
        if (is_numeric($user)) {
            $user = $this->find((int) $user);
        }

        if (!$user) {
            return [
                'free_limit'              => 3,
                'free_weekly_limit'       => 3,
                'free_used'               => 0,
                'free_remaining'          => 3,
                'bonus_remaining'         => 0,
                'bonus_submissions'       => 0,
                'total_available'         => 3,
                'corpus_count'            => 0,
                'uploaded_corpus_count'   => 0,
                'corpus_to_next_bonus'    => 3,
                'corpus_progress_percent' => 0,
                'reset_date'              => date('Y-m-d 00:00:00', strtotime('next monday')),
                'days_until_reset'        => 7,
                'subscription_tier'       => 'free',
                'subscription_expires_at' => null,
                'subscription_label'      => 'Paket Standar (Gratis)',
                'is_subscribed'           => false,
                'subscription_days_left'  => 0,
            ];
        }

        $user = $this->refreshWeeklyQuota($user);

        $freeLimit = 3;
        $freeUsed = (int) $user['free_submissions_used_this_week'];
        $freeRemaining = max(0, $freeLimit - $freeUsed);
        $bonus = (int) $user['bonus_submissions'];
        $totalAvailable = $freeRemaining + $bonus;

        $corpusCount = (int) $user['uploaded_corpus_count'];
        $currentCycle = $corpusCount % 3;
        $toNext = 3 - $currentCycle;
        $progressPct = round(($currentCycle / 3) * 100);

        $resetTime = strtotime($user['week_reset_at']);
        $daysUntilReset = max(1, (int) ceil(($resetTime - time()) / 86400));

        $subTier = $user['subscription_tier'] ?? 'free';
        $subExpiresAt = $user['subscription_expires_at'] ?? null;
        $isSubscribed = ($subTier !== 'free' && !empty($subExpiresAt) && strtotime($subExpiresAt) > time());
        $subLabel = match ($subTier) {
            'skripsi'   => 'Pejuang Skripsi',
            'dosen_pro' => 'Dosen & Akademisi Pro',
            default     => 'Paket Standar (Gratis)'
        };
        $daysUntilSubExpires = $isSubscribed ? max(1, (int) ceil((strtotime($subExpiresAt) - time()) / 86400)) : 0;

        return [
            'free_limit'              => $freeLimit,
            'free_weekly_limit'       => $freeLimit,
            'free_used'               => $freeUsed,
            'free_remaining'          => $freeRemaining,
            'bonus_remaining'         => $bonus,
            'bonus_submissions'       => $bonus,
            'total_available'         => $totalAvailable,
            'corpus_count'            => $corpusCount,
            'uploaded_corpus_count'   => $corpusCount,
            'corpus_to_next_bonus'    => $toNext,
            'corpus_progress_percent' => $progressPct,
            'reset_date'              => $user['week_reset_at'],
            'days_until_reset'        => $daysUntilReset,
            'subscription_tier'       => $subTier,
            'subscription_expires_at' => $subExpiresAt,
            'subscription_label'      => $subLabel,
            'is_subscribed'           => $isSubscribed,
            'subscription_days_left'  => $daysUntilSubExpires,
        ];
    }

    /**
     * Mengurangi kuota submisi secara atomik di database (mencegah race condition/TOCTOU)
     * Prioritas: kuota gratis mingguan (< 3), lalu bonus kuota.
     */
    public function deductSubmissionQuota(int $userId): bool
    {
        $db = \Config\Database::connect();
        $nowStr = date('Y-m-d H:i:s');
        $nextReset = date('Y-m-d 00:00:00', strtotime('next monday'));

        // 1. Reset otomatis kuota mingguan jika sudah melewati batas waktu (Senin 00:00 WIB)
        $db->query("
            UPDATE {$this->table}
            SET free_submissions_used_this_week = 0,
                week_reset_at = ?,
                updated_at = ?
            WHERE id = ? AND (week_reset_at <= ? OR week_reset_at IS NULL)
        ", [$nextReset, $nowStr, $userId, $nowStr]);

        // 2. Coba potong kuota gratis mingguan secara atomik (hanya jika < 3)
        $db->query("
            UPDATE {$this->table}
            SET free_submissions_used_this_week = free_submissions_used_this_week + 1,
                updated_at = ?
            WHERE id = ? AND free_submissions_used_this_week < 3
        ", [$nowStr, $userId]);

        if ($db->affectedRows() === 1) {
            return true;
        }

        // 3. Jika kuota gratis mingguan habis, coba potong bonus_submissions secara atomik
        $db->query("
            UPDATE {$this->table}
            SET bonus_submissions = bonus_submissions - 1,
                updated_at = ?
            WHERE id = ? AND bonus_submissions > 0
        ", [$nowStr, $userId]);

        if ($db->affectedRows() === 1) {
            return true;
        }

        return false;
    }

    /**
     * Mengembalikan 1 kuota submisi (rollback) jika pemrosesan dokumen/antrean worker gagal
     */
    public function refundSubmissionQuota(int $userId, string $type = 'auto'): bool
    {
        $db = \Config\Database::connect();
        $nowStr = date('Y-m-d H:i:s');

        if ($type === 'bonus') {
            $db->query("
                UPDATE {$this->table}
                SET bonus_submissions = bonus_submissions + 1,
                    updated_at = ?
                WHERE id = ?
            ", [$nowStr, $userId]);
            return $db->affectedRows() > 0;
        }

        if ($type === 'free') {
            $db->query("
                UPDATE {$this->table}
                SET free_submissions_used_this_week = GREATEST(0, CAST(free_submissions_used_this_week AS SIGNED) - 1),
                    updated_at = ?
                WHERE id = ?
            ", [$nowStr, $userId]);
            return $db->affectedRows() > 0;
        }

        // Mode auto: kurangi pemakaian gratis jika > 0, jika tidak tambahkan bonus
        $db->query("
            UPDATE {$this->table}
            SET free_submissions_used_this_week = free_submissions_used_this_week - 1,
                updated_at = ?
            WHERE id = ? AND free_submissions_used_this_week > 0
        ", [$nowStr, $userId]);

        if ($db->affectedRows() === 1) {
            return true;
        }

        $db->query("
            UPDATE {$this->table}
            SET bonus_submissions = bonus_submissions + 1,
                updated_at = ?
            WHERE id = ?
        ", [$nowStr, $userId]);

        return $db->affectedRows() > 0;
    }

    /**
     * Mencatat kontribusi unggahan korpus valid dan memberikan reward jika mencapai kelipatan 3 (aman konkruensi)
     */
    public function recordCorpusUpload(int $userId): array
    {
        $db = \Config\Database::connect();
        $nowStr = date('Y-m-d H:i:s');

        $db->transStart();

        $user = $db->table($this->table)->where('id', $userId)->get()->getRowArray();
        if (! $user) {
            $db->transRollback();
            return ['bonus_awarded' => false, 'new_bonus_count' => 0, 'total_corpus' => 0, 'quota_info' => null];
        }

        $newCorpusCount = (int) $user['uploaded_corpus_count'] + 1;
        $currentBonus = (int) $user['bonus_submissions'];
        $bonusAwarded = false;

        // Setiap 3 korpus valid = +1 submisi gratis tambahan
        if ($newCorpusCount % 3 === 0) {
            $currentBonus += 1;
            $bonusAwarded = true;
        }

        $db->table($this->table)->where('id', $userId)->update([
            'uploaded_corpus_count' => $newCorpusCount,
            'bonus_submissions'     => $currentBonus,
            'updated_at'            => $nowStr,
        ]);

        $db->transComplete();

        $updatedUser = $this->find($userId);
        $quotaInfo = $this->getQuotaInfo($updatedUser);

        return [
            'bonus_awarded'   => $bonusAwarded,
            'new_bonus_count' => $currentBonus,
            'total_corpus'    => $newCorpusCount,
            'quota_info'      => $quotaInfo,
        ];
    }

    /**
     * Mereset seluruh kuota gratis mingguan pengguna ke 0 dan memajukan jadwal ke Senin berikutnya
     * Digunakan oleh CLI command quota:reset-weekly dan administrasi sistem
     */
    public function resetAllWeeklyQuotas(bool $force = false): int
    {
        $nextReset = date('Y-m-d 00:00:00', strtotime('next monday'));
        $nowStr = date('Y-m-d H:i:s');

        if ($force) {
            $this->db->query("
                UPDATE {$this->table}
                SET free_submissions_used_this_week = 0,
                    week_reset_at = ?,
                    updated_at = ?
            ", [$nextReset, $nowStr]);
        } else {
            $this->db->query("
                UPDATE {$this->table}
                SET free_submissions_used_this_week = 0,
                    week_reset_at = ?,
                    updated_at = ?
                WHERE week_reset_at <= ? 
                   OR week_reset_at IS NULL
            ", [$nextReset, $nowStr, $nowStr]);
        }

        return $this->db->affectedRows();
    }
}

