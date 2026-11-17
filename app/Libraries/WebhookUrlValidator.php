<?php

namespace App\Libraries;

/**
 * Validator URL Callback Webhook dengan Proteksi Anti-SSRF Ketat.
 *
 * Aturan keamanan:
 * - Hanya mengizinkan skema HTTPS.
 * - Menolak username/password pada otoritas URL.
 * - Menolak hostname lokal/internal dan bentuk IP numerik terselubung.
 * - Memeriksa hasil resolusi DNS (A/AAAA) dan menolak setiap IP non-publik:
 *   10/8, 172.16/12, 192.168/16, 127/8, 169.254/16 (termasuk 169.254.169.254),
 *   100.64/10, 0.0.0.0/8, ::1, fc00::/7, fe80::/10, multicast, dan reserved.
 */
class WebhookUrlValidator
{
    private const BLOCKED_HOSTNAMES = [
        'localhost',
        'localhost.localdomain',
        'ip6-localhost',
        'ip6-loopback',
        'metadata',
        'metadata.google.internal',
    ];

    private const BLOCKED_SUFFIXES = [
        '.localhost',
        '.local',
        '.internal',
        '.localdomain',
    ];

    /**
     * Memvalidasi URL callback secara sinkron beserta resolusi DNS-nya.
     *
     * @param callable|null $resolver Opsional untuk unit test: fn(string $host): array<string>
     * @return array{valid: bool, error: ?string, resolved_ips: array<string>}
     */
    public static function validate(string $url, ?callable $resolver = null): array
    {
        $trimmed = trim($url);
        if ($trimmed === '') {
            return ['valid' => false, 'error' => 'Callback URL tidak boleh kosong.', 'resolved_ips' => []];
        }

        if (strlen($trimmed) > 1000) {
            return ['valid' => false, 'error' => 'Callback URL melebihi panjang maksimal 1000 karakter.', 'resolved_ips' => []];
        }

        $parts = @parse_url($trimmed);
        if (! is_array($parts) || empty($parts['scheme']) || empty($parts['host'])) {
            return ['valid' => false, 'error' => 'Format callback_url tidak valid.', 'resolved_ips' => []];
        }

        if (strtolower((string) $parts['scheme']) !== 'https') {
            return [
                'valid'        => false,
                'error'        => 'Callback URL ditolak oleh proteksi anti-SSRF: hanya skema HTTPS yang diizinkan.',
                'resolved_ips' => [],
            ];
        }

        if (isset($parts['user']) || isset($parts['pass'])) {
            return [
                'valid'        => false,
                'error'        => 'Callback URL ditolak oleh proteksi anti-SSRF: kredensial pada URL tidak diizinkan.',
                'resolved_ips' => [],
            ];
        }

        if (isset($parts['port']) && ((int) $parts['port'] < 1 || (int) $parts['port'] > 65535)) {
            return ['valid' => false, 'error' => 'Port pada callback_url tidak valid.', 'resolved_ips' => []];
        }

        $host = trim((string) $parts['host']);
        // Hapus kurung siku IPv6 literal jika ada
        if (str_starts_with($host, '[') && str_ends_with($host, ']')) {
            $host = substr($host, 1, -1);
        }
        $host = rtrim($host, '.');
        if ($host === '') {
            return ['valid' => false, 'error' => 'Hostname callback_url kosong.', 'resolved_ips' => []];
        }

        if (function_exists('idn_to_ascii') && ! filter_var($host, FILTER_VALIDATE_IP)) {
            $asciiHost = @idn_to_ascii($host, IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46);
            if ($asciiHost !== false && $asciiHost !== '') {
                $host = $asciiHost;
            }
        }
        $hostLower = strtolower($host);

        if (in_array($hostLower, self::BLOCKED_HOSTNAMES, true)) {
            return [
                'valid'        => false,
                'error'        => 'Callback URL ditolak oleh proteksi anti-SSRF: host lokal/internal dilarang.',
                'resolved_ips' => [],
            ];
        }

        foreach (self::BLOCKED_SUFFIXES as $suffix) {
            if (str_ends_with($hostLower, $suffix)) {
                return [
                    'valid'        => false,
                    'error'        => 'Callback URL ditolak oleh proteksi anti-SSRF: domain lokal/internal dilarang.',
                    'resolved_ips' => [],
                ];
            }
        }

        // Tolak representasi IP terselubung (desimal tunggal, oktal, heksadesimal)
        if (! filter_var($hostLower, FILTER_VALIDATE_IP) && self::looksLikeObfuscatedIp($hostLower)) {
            return [
                'valid'        => false,
                'error'        => 'Callback URL ditolak oleh proteksi anti-SSRF: bentuk IP numerik terselubung tidak diizinkan.',
                'resolved_ips' => [],
            ];
        }

        if (filter_var($hostLower, FILTER_VALIDATE_IP)) {
            if (! self::isPublicIp($hostLower)) {
                return [
                    'valid'        => false,
                    'error'        => 'Callback URL ditolak oleh proteksi anti-SSRF: alamat IP privat/lokal/metadata dilarang.',
                    'resolved_ips' => [],
                ];
            }
            return ['valid' => true, 'error' => null, 'resolved_ips' => [$hostLower]];
        }

        $ips = $resolver !== null ? $resolver($hostLower) : self::resolveHostIps($hostLower);
        $ips = array_values(array_unique(array_filter(array_map('strval', $ips))));

        if (empty($ips)) {
            return [
                'valid'        => false,
                'error'        => 'Callback URL ditolak oleh proteksi anti-SSRF: resolusi DNS host callback gagal.',
                'resolved_ips' => [],
            ];
        }

        foreach ($ips as $ip) {
            if (! self::isPublicIp($ip)) {
                return [
                    'valid'        => false,
                    'error'        => 'Callback URL ditolak oleh proteksi anti-SSRF: DNS host mengarah ke IP privat/lokal/metadata.',
                    'resolved_ips' => [],
                ];
            }
        }

        return ['valid' => true, 'error' => null, 'resolved_ips' => $ips];
    }

    /**
     * Memeriksa apakah IP bersifat publik/global (termasuk membuka IPv4-mapped IPv6).
     */
    public static function isPublicIp(string $ip): bool
    {
        $packed = @inet_pton($ip);
        if ($packed === false) {
            return false;
        }

        // Unwrap ::ffff:w.x.y.z (IPv4-mapped IPv6)
        if (strlen($packed) === 16 && substr($packed, 0, 12) === str_repeat("\x00", 10) . "\xff\xff") {
            $ipv4 = @inet_ntop(substr($packed, 12, 4));
            if ($ipv4 === false) {
                return false;
            }
            $ip = $ipv4;
            $packed = @inet_pton($ip);
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
            return false;
        }

        // Tolak rentang tambahan yang kadang lolos filter standar PHP:
        // 0.0.0.0/8, 100.64.0.0/10 (CGNAT), 192.0.0.0/24, 198.18.0.0/15, 224.0.0.0/4+
        if (strlen($packed) === 4) {
            $long = unpack('N', $packed)[1];
            $blockedCidrsV4 = [
                ['0.0.0.0', 8],
                ['10.0.0.0', 8],
                ['100.64.0.0', 10],
                ['127.0.0.0', 8],
                ['169.254.0.0', 16],
                ['172.16.0.0', 12],
                ['192.0.0.0', 24],
                ['192.0.2.0', 24],
                ['192.168.0.0', 16],
                ['198.18.0.0', 15],
                ['198.51.100.0', 24],
                ['203.0.113.0', 24],
                ['224.0.0.0', 4],
                ['240.0.0.0', 4],
            ];
            foreach ($blockedCidrsV4 as [$net, $maskBits]) {
                $netLong = ip2long($net);
                $mask = $maskBits === 0 ? 0 : (~0 << (32 - $maskBits));
                if (($long & $mask) === ($netLong & $mask)) {
                    return false;
                }
            }
            return true;
        }

        if (strlen($packed) === 16) {
            // Tolak ::/128, ::1/128, fc00::/7, fe80::/10, ff00::/8, 2001:db8::/32
            $b0 = ord($packed[0]);
            $b1 = ord($packed[1]);
            if ($packed === str_repeat("\x00", 16) || $packed === str_repeat("\x00", 15) . "\x01") {
                return false;
            }
            if (($b0 & 0xfe) === 0xfc) { // fc00::/7 (Unique Local)
                return false;
            }
            if ($b0 === 0xfe && ($b1 & 0xc0) === 0x80) { // fe80::/10 (Link-Local)
                return false;
            }
            if ($b0 === 0xff) { // ff00::/8 (Multicast)
                return false;
            }
            if (substr($packed, 0, 4) === "\x20\x01\x0d\xb8") { // 2001:db8::/32 (Documentation)
                return false;
            }
            return true;
        }

        return false;
    }

    /**
     * Resolusi A dan AAAA DNS host menjadi daftar alamat IP.
     *
     * @return array<string>
     */
    private static function resolveHostIps(string $host): array
    {
        $ips = [];

        $records = @dns_get_record($host, DNS_A | DNS_AAAA);
        if (is_array($records)) {
            foreach ($records as $rec) {
                if (! empty($rec['ip'])) {
                    $ips[] = $rec['ip'];
                } elseif (! empty($rec['ipv6'])) {
                    $ips[] = $rec['ipv6'];
                }
            }
        }

        if (empty($ips)) {
            $v4 = @gethostbynamel($host);
            if (is_array($v4)) {
                foreach ($v4 as $addr) {
                    $ips[] = $addr;
                }
            }
        }

        return $ips;
    }

    private static function looksLikeObfuscatedIp(string $host): bool
    {
        if (ctype_digit($host) || str_starts_with($host, '0x')) {
            return true;
        }

        $parts = explode('.', $host);
        if (count($parts) >= 2 && count($parts) <= 4) {
            $allNumericParts = true;
            foreach ($parts as $part) {
                if (! preg_match('/^(?:0x[0-9a-f]+|0[0-7]*|[1-9][0-9]*)$/i', $part)) {
                    $allNumericParts = false;
                    break;
                }
            }
            if ($allNumericParts) {
                return true;
            }
        }

        return false;
    }
}
