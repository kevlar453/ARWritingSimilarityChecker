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
        'verification_emails_sent',
        'last_verification_email_sent_at',
        'email_verified_at',
        'unsubscribe_token',
        'email_notifications_enabled',
        'email_weekly_summary',
        'last_weekly_summary_sent_at',
        'last_quota_reminder_sent_at',
        'reset_password_token',
        'reset_password_expires_at',
        'free_submissions_used_this_week',
        'week_reset_at',
        'bonus_submissions',
        'referral_code',
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
                    last_quota_reminder_sent_at = NULL,
                    updated_at = ?
                WHERE id = ? AND (week_reset_at <= ? OR week_reset_at IS NULL)
            ", [$newResetAt, $nowStr, $user['id'], $nowStr]);

            $user['free_submissions_used_this_week'] = 0;
            $user['week_reset_at'] = $newResetAt;
            $user['last_quota_reminder_sent_at'] = null;
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
            'skripsi'      => 'Pejuang Skripsi',
            'dosen_pro'    => 'Dosen & Akademisi Pro',
            'trial_kampus' => 'Program Uji Coba Resmi Jurnal & Kampus',
            default        => 'Paket Standar (Gratis)'
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
                last_quota_reminder_sent_at = NULL,
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
            $this->checkAndTriggerQuotaExhaustedReminder($userId);
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
            $this->checkAndTriggerQuotaExhaustedReminder($userId);
            return true;
        }

        return false;
    }

    /**
     * Memeriksa apakah kuota pengguna baru saja habis (0 submisi tersisa) dan memicu
     * pengiriman surel pengingat secara asinkron (non-blocking) di latar belakang.
     */
    protected function checkAndTriggerQuotaExhaustedReminder(int $userId): void
    {
        try {
            $row = $this->db->table($this->table)
                ->select('id, status, email_verified_at, email_weekly_summary, subscription_tier, subscription_expires_at, free_submissions_used_this_week, bonus_submissions, last_quota_reminder_sent_at')
                ->where('id', $userId)
                ->get()
                ->getRowArray();

            if (! $row) {
                return;
            }

            if (($row['status'] ?? '') !== 'active' || empty($row['email_verified_at']) || empty($row['email_weekly_summary'])) {
                return;
            }

            // Lewati jika pengguna berlangganan paket tanpa batas (Dosen Pro aktif)
            $subTier = $row['subscription_tier'] ?? 'free';
            $subExp  = $row['subscription_expires_at'] ?? null;
            if ($subTier === 'dosen_pro' && !empty($subExp) && strtotime($subExp) > time()) {
                return;
            }

            $freeUsed = (int) ($row['free_submissions_used_this_week'] ?? 0);
            $bonus    = (int) ($row['bonus_submissions'] ?? 0);

            if ($freeUsed >= 3 && $bonus === 0 && empty($row['last_quota_reminder_sent_at'])) {
                $sparkPath = ROOTPATH . 'spark';
                if (is_file($sparkPath) && function_exists('exec')) {
                    $cmd = 'php ' . escapeshellarg($sparkPath) . ' email:send-quota-reminder --user_id=' . (int) $userId . ' > /dev/null 2>&1 &';
                    @exec($cmd);
                }
            }
        } catch (\Throwable $e) {
            log_message('error', 'Gagal memicu pengingat kuota habis untuk user #' . $userId . ': ' . $e->getMessage());
        }
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
                    last_quota_reminder_sent_at = NULL,
                    updated_at = ?
                WHERE id = ?
            ", [$nowStr, $userId]);
            return $db->affectedRows() > 0;
        }

        if ($type === 'free') {
            $db->query("
                UPDATE {$this->table}
                SET free_submissions_used_this_week = GREATEST(0, CAST(free_submissions_used_this_week AS SIGNED) - 1),
                    last_quota_reminder_sent_at = NULL,
                    updated_at = ?
                WHERE id = ?
            ", [$nowStr, $userId]);
            return $db->affectedRows() > 0;
        }

        // Mode auto: kurangi pemakaian gratis jika > 0, jika tidak tambahkan bonus
        $db->query("
            UPDATE {$this->table}
            SET free_submissions_used_this_week = free_submissions_used_this_week - 1,
                last_quota_reminder_sent_at = NULL,
                updated_at = ?
            WHERE id = ? AND free_submissions_used_this_week > 0
        ", [$nowStr, $userId]);

        if ($db->affectedRows() === 1) {
            return true;
        }

        $db->query("
            UPDATE {$this->table}
            SET bonus_submissions = bonus_submissions + 1,
                last_quota_reminder_sent_at = NULL,
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

        $updatePayload = [
            'uploaded_corpus_count' => $newCorpusCount,
            'bonus_submissions'     => $currentBonus,
            'updated_at'            => $nowStr,
        ];
        if ($bonusAwarded) {
            $updatePayload['last_quota_reminder_sent_at'] = null;
        }

        $db->table($this->table)->where('id', $userId)->update($updatePayload);

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
                    last_quota_reminder_sent_at = NULL,
                    updated_at = ?
            ", [$nextReset, $nowStr]);
        } else {
            $this->db->query("
                UPDATE {$this->table}
                SET free_submissions_used_this_week = 0,
                    week_reset_at = ?,
                    last_quota_reminder_sent_at = NULL,
                    updated_at = ?
                WHERE week_reset_at <= ? 
                   OR week_reset_at IS NULL
            ", [$nextReset, $nowStr, $nowStr]);
        }

        return $this->db->affectedRows();
    }

    /**
     * Menghasilkan kode referral unik untuk pengguna (Format: REF-XXXX-YYYY)
     */
    public function generateUniqueReferralCode(int $userId, ?string $name = null): string
    {
        $cleanPart = sprintf('%04X', ($userId * 31 + 1047) % 65536);
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $suffix = '';
        for ($i = 0; $i < 4; $i++) {
            $suffix .= $chars[random_int(0, strlen($chars) - 1)];
        }
        $candidate = "REF-{$cleanPart}-{$suffix}";

        while ($this->where('referral_code', $candidate)->countAllResults() > 0) {
            $suffix = '';
            for ($i = 0; $i < 4; $i++) {
                $suffix .= $chars[random_int(0, strlen($chars) - 1)];
            }
            $candidate = "REF-{$cleanPart}-{$suffix}";
        }

        return $candidate;
    }

    /**
     * Memastikan pengguna memiliki kode referral aktif di database
     */
    public function ensureReferralCode(int $userId): string
    {
        $user = $this->find($userId);
        if (!$user) {
            return '';
        }

        if (!empty($user['referral_code'])) {
            return $user['referral_code'];
        }

        $newCode = $this->generateUniqueReferralCode($userId, $user['name'] ?? null);
        $this->update($userId, [
            'referral_code' => $newCode,
            'updated_at'    => date('Y-m-d H:i:s'),
        ]);

        return $newCode;
    }

    /**
     * Mengambil pengguna berdasarkan kode referral
     */
    public function getByReferralCode(string $code): ?array
    {
        $code = strtoupper(trim($code));
        if (empty($code)) {
            return null;
        }

        return $this->where('referral_code', $code)->first();
    }

    /**
     * Memusnahkan akun pendaftar yang tidak memverifikasi email setelah batas pengingat tercapai
     * Membersihkan seluruh data terkait secara aman tanpa meninggalkan data yatim (orphaned records).
     */
    public function purgeUnverifiedUser(int $userId): bool
    {
        $db = \Config\Database::connect();
        $db->transStart();

        $user = $this->find($userId);
        if (!$user || $user['status'] !== 'pending_verification') {
            $db->transRollback();
            return false;
        }

        // 1. Hapus relasi otorisasi & keamanan
        $db->table('auth_rate_limits')->where('identifier', (string) $userId)->delete();
        $db->table('auth_rate_limits')->where('identifier', $user['email'])->delete();
        $db->table('api_keys')->where('user_id', $userId)->delete();
        $db->table('document_shares')->where('shared_by_user_id', $userId)->orWhere('shared_with_user_id', $userId)->delete();
        $db->table('payment_transactions')->where('user_id', $userId)->delete();
        $db->table('ambassador_applications')->where('user_id', $userId)->delete();
        $db->table('ambassador_profiles')->where('user_id', $userId)->delete();
        $db->table('testimonials')->where('user_id', $userId)->delete();
        $db->table('feature_votes')->where('user_id', $userId)->delete();

        // 2. Hapus berkas fisik dan dokumen jika ada
        $docs = $db->table('documents')->where('user_id', $userId)->get()->getResultArray();
        foreach ($docs as $d) {
            if (!empty($d['storage_path']) && file_exists($d['storage_path']) && $d['storage_path'] !== '[PURGED]') {
                @unlink($d['storage_path']);
            }
            $db->table('reports')->where('document_id', $d['id'])->delete();
            $db->table('document_chunks')->where('document_id', $d['id'])->delete();
            $db->table('document_pages')->where('document_id', $d['id'])->delete();
        }
        $db->table('documents')->where('user_id', $userId)->delete();

        // 3. Hapus entitas pengguna
        $db->table($this->table)->where('id', $userId)->where('status', 'pending_verification')->delete();

        $db->transComplete();

        return $db->transStatus();
    }

    /**
     * Mendapatkan atau membuat token unsubscribe unik untuk pengguna
     */
    public function getOrCreateUnsubscribeToken(int $userId): string
    {
        $user = $this->find($userId);
        if (!$user) {
            return '';
        }

        if (!empty($user['unsubscribe_token'])) {
            return $user['unsubscribe_token'];
        }

        $token = hash('sha256', $userId . '|' . $user['email'] . '|' . microtime(true) . '|' . bin2hex(random_bytes(16)));
        $this->update($userId, [
            'unsubscribe_token' => $token,
            'updated_at'        => date('Y-m-d H:i:s'),
        ]);

        return $token;
    }

    /**
     * Mencari pengguna berdasarkan token unsubscribe
     */
    public function findByUnsubscribeToken(string $token): ?array
    {
        $token = trim($token);
        if ($token === '') {
            return null;
        }

        return $this->where('unsubscribe_token', $token)->first();
    }

    /**
     * Memperbarui preferensi pengiriman email pengguna
     */
    public function updateEmailPreferences(int $userId, bool $reportNotification, bool $weeklySummary): bool
    {
        return (bool) $this->update($userId, [
            'email_notifications_enabled' => $reportNotification ? 1 : 0,
            'email_weekly_summary'        => $weeklySummary ? 1 : 0,
            'updated_at'                  => date('Y-m-d H:i:s'),
        ]);
    }
}

