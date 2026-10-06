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
        'free_submissions_used_this_week',
        'week_reset_at',
        'bonus_submissions',
        'uploaded_corpus_count',
        'last_login_at',
        'created_at',
        'updated_at',
    ];
    protected $useTimestamps = false;

    /**
     * Memeriksa dan memperbarui masa berlaku kuota gratis mingguan (7 hari)
     */
    public function refreshWeeklyQuota(array $user): array
    {
        $now = time();
        $resetTime = strtotime($user['week_reset_at']);

        if ($now >= $resetTime) {
            $newResetAt = date('Y-m-d H:i:s', strtotime('+7 days'));
            $this->update($user['id'], [
                'free_submissions_used_this_week' => 0,
                'week_reset_at'                   => $newResetAt,
                'updated_at'                      => date('Y-m-d H:i:s'),
            ]);
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
            $user = $this->find($user);
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
                'reset_date'              => date('Y-m-d H:i:s', strtotime('+7 days')),
                'days_until_reset'        => 7,
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
     * Mengurangi kuota submisi (prioritas kuota gratis mingguan, lalu bonus korpus)
     */
    public function deductSubmissionQuota(int $userId): bool
    {
        $user = $this->find($userId);
        if (!$user) {
            return false;
        }

        $user = $this->refreshWeeklyQuota($user);
        $freeLimit = 3;
        $freeUsed = (int) $user['free_submissions_used_this_week'];
        $bonus = (int) $user['bonus_submissions'];

        if ($freeUsed < $freeLimit) {
            return $this->update($userId, [
                'free_submissions_used_this_week' => $freeUsed + 1,
                'updated_at'                      => date('Y-m-d H:i:s'),
            ]);
        } elseif ($bonus > 0) {
            return $this->update($userId, [
                'bonus_submissions' => $bonus - 1,
                'updated_at'        => date('Y-m-d H:i:s'),
            ]);
        }

        return false;
    }

    /**
     * Mencatat kontribusi unggahan korpus valid dan memberikan reward jika mencapai kelipatan 3
     */
    public function recordCorpusUpload(int $userId): array
    {
        $user = $this->find($userId);
        if (!$user) {
            return ['bonus_awarded' => false, 'new_bonus_count' => 0];
        }

        $newCorpusCount = (int) $user['uploaded_corpus_count'] + 1;
        $currentBonus = (int) $user['bonus_submissions'];
        $bonusAwarded = false;

        // Setiap 3 korpus valid = +1 submisi gratis tambahan
        if ($newCorpusCount % 3 === 0) {
            $currentBonus += 1;
            $bonusAwarded = true;
        }

        $this->update($userId, [
            'uploaded_corpus_count' => $newCorpusCount,
            'bonus_submissions'     => $currentBonus,
            'updated_at'            => date('Y-m-d H:i:s'),
        ]);

        $updatedUser = $this->find($userId);
        $quotaInfo = $this->getQuotaInfo($updatedUser);

        return [
            'bonus_awarded'   => $bonusAwarded,
            'new_bonus_count' => $currentBonus,
            'total_corpus'    => $newCorpusCount,
            'quota_info'      => $quotaInfo,
        ];
    }
}
