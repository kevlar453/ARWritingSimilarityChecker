<?php

namespace App\Libraries;

/**
 * Mencatat metrik konversi agregat (tanpa PII) dari ?utm_source=cek-sitasi.
 */
class CitationFunnelTracker
{
    public static function recordArrival(?string $utmSource): void
    {
        $source = strtolower(trim((string) $utmSource));
        if ($source !== 'cek-sitasi') {
            return;
        }

        try {
            if (function_exists('session')) {
                session()->set('utm_source', 'cek-sitasi');
            }
            $db = db_connect();
            if (! $db->tableExists('public_citation_funnel_stats')) {
                return;
            }
            $today = date('Y-m-d');
            $now = date('Y-m-d H:i:s');
            $db->query(
                'INSERT INTO public_citation_funnel_stats (stat_date, utm_source, cta_arrivals, registrations, updated_at)
                 VALUES (?, ?, 1, 0, ?)
                 ON DUPLICATE KEY UPDATE cta_arrivals = cta_arrivals + 1, updated_at = VALUES(updated_at)',
                [$today, 'cek-sitasi', $now]
            );
        } catch (\Throwable $e) {
            log_message('debug', 'CitationFunnelTracker::recordArrival skip: ' . $e->getMessage());
        }
    }

    public static function recordRegistration(?string $explicitUtm = null): void
    {
        $sessionUtm = '';
        if (function_exists('session')) {
            $sessionUtm = (string) (session()->get('utm_source') ?? '');
        }
        $source = strtolower(trim((string) ($explicitUtm ?: $sessionUtm)));
        if ($source !== 'cek-sitasi') {
            return;
        }

        try {
            $db = db_connect();
            if (! $db->tableExists('public_citation_funnel_stats')) {
                return;
            }
            $today = date('Y-m-d');
            $now = date('Y-m-d H:i:s');
            $db->query(
                'INSERT INTO public_citation_funnel_stats (stat_date, utm_source, cta_arrivals, registrations, updated_at)
                 VALUES (?, ?, 0, 1, ?)
                 ON DUPLICATE KEY UPDATE registrations = registrations + 1, updated_at = VALUES(updated_at)',
                [$today, 'cek-sitasi', $now]
            );
        } catch (\Throwable $e) {
            log_message('debug', 'CitationFunnelTracker::recordRegistration skip: ' . $e->getMessage());
        }
    }
}
