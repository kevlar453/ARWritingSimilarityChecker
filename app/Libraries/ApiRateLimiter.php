<?php

namespace App\Libraries;

use CodeIgniter\HTTP\ResponseInterface;

/**
 * Pembatas Laju Permintaan API Key berbasis Redis (Fixed-Window 60 Detik).
 *
 * Kebijakan keandalan:
 * - Fail-Open saat Redis mati/tidak terjangkau: permintaan tetap dilayani agar
 *   ketersediaan layanan terjaga, sambil mencatat kesalahan ke log (level error).
 * - Sepenuhnya terpisah dari penghitung kuota total (quota_used / recordUsage).
 */
class ApiRateLimiter
{
    public const DEFAULT_WINDOW_SECONDS = 60;
    public const DEFAULT_LIMIT_PER_MIN  = 60;

    /**
     * Memeriksa dan menaikkan penghitung permintaan untuk api_key_id pada jendela 60 detik.
     *
     * @param callable|null $counterFn Opsional untuk unit test: fn(string $key, int $windowSec): array{0:int, 1:int}
     * @return array{allowed: bool, limit: int, current: int, remaining: int, retry_after: int, fail_open: bool}
     */
    public static function checkLimit(
        int $apiKeyId,
        int $limitPerMinute = self::DEFAULT_LIMIT_PER_MIN,
        string $keyPrefix = '',
        int $windowSeconds = self::DEFAULT_WINDOW_SECONDS,
        ?callable $counterFn = null,
        ?callable $loggerFn = null
    ): array {
        $limit = $limitPerMinute > 0 ? $limitPerMinute : self::DEFAULT_LIMIT_PER_MIN;
        $window = $windowSeconds > 0 ? $windowSeconds : self::DEFAULT_WINDOW_SECONDS;
        $redisKey = "artilik:ratelimit:apikey:{$apiKeyId}";

        try {
            if ($counterFn !== null) {
                [$current, $ttl] = $counterFn($redisKey, $window);
            } else {
                [$current, $ttl] = self::incrementInRedis($redisKey, $window);
            }

            $current = (int) $current;
            $retryAfter = max(1, min($window, (int) ($ttl > 0 ? $ttl : $window)));
            $allowed = $current <= $limit;

            return [
                'allowed'     => $allowed,
                'limit'       => $limit,
                'current'     => $current,
                'remaining'   => $allowed ? max(0, $limit - $current) : 0,
                'retry_after' => $retryAfter,
                'fail_open'   => false,
            ];
        } catch (\Throwable $e) {
            $safePrefix = $keyPrefix !== '' ? $keyPrefix : '-';
            $msg = sprintf(
                '[RATE_LIMIT] Redis tidak tersedia (fail-open aktif) api_key_id=%d prefix=%s: %s',
                $apiKeyId,
                $safePrefix,
                $e->getMessage()
            );

            if ($loggerFn !== null) {
                $loggerFn($msg);
            } elseif (function_exists('log_message')) {
                log_message('error', $msg);
            } else {
                error_log($msg);
            }

            return [
                'allowed'     => true,
                'limit'       => $limit,
                'current'     => 0,
                'remaining'   => $limit,
                'retry_after' => $window,
                'fail_open'   => true,
            ];
        }
    }

    /**
     * Menambahkan header standar X-RateLimit-* ke respons HTTP (jika Redis aktif).
     */
    public static function applyHeaders(ResponseInterface $response, array $rateInfo): ResponseInterface
    {
        if (! empty($rateInfo['fail_open'])) {
            return $response->setHeader('X-RateLimit-Policy', 'fail-open');
        }

        $retryAfter = max(1, (int) ($rateInfo['retry_after'] ?? self::DEFAULT_WINDOW_SECONDS));
        $response
            ->setHeader('X-RateLimit-Limit', (string) ($rateInfo['limit'] ?? self::DEFAULT_LIMIT_PER_MIN))
            ->setHeader('X-RateLimit-Remaining', (string) ($rateInfo['remaining'] ?? 0))
            ->setHeader('X-RateLimit-Reset', (string) (time() + $retryAfter));

        if (empty($rateInfo['allowed'])) {
            $response->setHeader('Retry-After', (string) $retryAfter);
        }

        return $response;
    }

    /**
     * Membangun respons HTTP 429 Too Many Requests beserta header Retry-After.
     */
    public static function buildRateLimitExceededResponse(ResponseInterface $response, array $rateInfo): ResponseInterface
    {
        $retryAfter = max(1, (int) ($rateInfo['retry_after'] ?? self::DEFAULT_WINDOW_SECONDS));
        self::applyHeaders($response, $rateInfo);

        return $response->setStatusCode(429)->setJSON([
            'status'      => 'error',
            'code'        => 429,
            'message'     => "Batas laju permintaan ({$rateInfo['limit']} permintaan/menit) terlampaui. Silakan ulangi setelah {$retryAfter} detik.",
            'retry_after' => $retryAfter,
        ]);
    }

    /**
     * Mengeksekusi INCR + EXPIRE atomik pada Redis melalui ext-redis atau socket RESP ringan.
     *
     * @return array{0: int, 1: int} [current_count, ttl_seconds]
     */
    private static function incrementInRedis(string $redisKey, int $windowSeconds): array
    {
        $host = self::readEnv('REDIS_HOST', '127.0.0.1') ?: '127.0.0.1';
        $port = (int) (self::readEnv('REDIS_PORT', '6379') ?: 6379);
        $password = self::readEnv('REDIS_PASSWORD', null);
        $dbIndex = (int) (self::readEnv('REDIS_DB', '0') ?: 0);

        if (class_exists('\Redis')) {
            $redis = new \Redis();
            $connected = @$redis->connect($host, $port, 0.35);
            if (! $connected) {
                throw new \RuntimeException("Gagal terhubung ke Redis {$host}:{$port}");
            }

            if (defined('\Redis::OPT_READ_TIMEOUT')) {
                $redis->setOption(\Redis::OPT_READ_TIMEOUT, 0.5);
            }

            if ($password !== null && $password !== '') {
                if (! @$redis->auth($password)) {
                    $redis->close();
                    throw new \RuntimeException('Autentikasi Redis gagal.');
                }
            }

            if ($dbIndex > 0) {
                if (! @$redis->select($dbIndex)) {
                    $redis->close();
                    throw new \RuntimeException("Gagal memilih database Redis #{$dbIndex}.");
                }
            }

            $lua = <<<'LUA'
local current = redis.call('INCR', KEYS[1])
if current == 1 or redis.call('TTL', KEYS[1]) < 0 then
    redis.call('EXPIRE', KEYS[1], tonumber(ARGV[1]))
end
local ttl = redis.call('TTL', KEYS[1])
return {current, ttl}
LUA;

            $result = @$redis->eval($lua, [$redisKey, (string) $windowSeconds], 1);
            $lastErr = method_exists($redis, 'getLastError') ? $redis->getLastError() : null;
            @$redis->close();

            if (! is_array($result) || count($result) < 2) {
                throw new \RuntimeException('Evaluasi Lua rate limiter Redis gagal' . ($lastErr ? ": {$lastErr}" : '.'));
            }

            return [(int) $result[0], (int) $result[1]];
        }

        // Fallback TCP RESP bila ext-redis tidak aktif
        $socket = @stream_socket_client("tcp://{$host}:{$port}", $errno, $errstr, 0.35);
        if (! $socket) {
            throw new \RuntimeException("Koneksi socket Redis {$host}:{$port} gagal ({$errno}: {$errstr})");
        }
        stream_set_timeout($socket, 0, 500000);

        try {
            if ($password !== null && $password !== '') {
                self::respCommand($socket, ['AUTH', $password]);
            }
            if ($dbIndex > 0) {
                self::respCommand($socket, ['SELECT', (string) $dbIndex]);
            }
            $current = (int) self::respCommand($socket, ['INCR', $redisKey]);
            $ttl = (int) self::respCommand($socket, ['TTL', $redisKey]);
            if ($current === 1 || $ttl < 0) {
                self::respCommand($socket, ['EXPIRE', $redisKey, (string) $windowSeconds]);
                $ttl = $windowSeconds;
            }
            return [$current, $ttl];
        } finally {
            @fclose($socket);
        }
    }

    /**
     * Mengirim perintah RESP tunggal melalui stream socket.
     *
     * @param resource $socket
     * @param list<string> $args
     */
    private static function respCommand($socket, array $args): string
    {
        $payload = '*' . count($args) . "\r\n";
        foreach ($args as $arg) {
            $payload .= '$' . strlen($arg) . "\r\n" . $arg . "\r\n";
        }
        if (@fwrite($socket, $payload) === false) {
            throw new \RuntimeException('Gagal menulis perintah ke socket Redis.');
        }

        $line = @fgets($socket);
        if ($line === false || $line === '') {
            throw new \RuntimeException('Tidak ada respons dari socket Redis.');
        }

        $prefix = $line[0];
        $body = rtrim(substr($line, 1), "\r\n");
        if ($prefix === '-') {
            throw new \RuntimeException("Redis error: {$body}");
        }
        if ($prefix === '+' || $prefix === ':') {
            return $body;
        }
        throw new \RuntimeException('Respons RESP Redis tidak terduga.');
    }

    private static function readEnv(string $name, ?string $default = null): ?string
    {
        $val = getenv($name);
        if ($val !== false && $val !== '') {
            return (string) $val;
        }
        if (isset($_ENV[$name]) && $_ENV[$name] !== '') {
            return (string) $_ENV[$name];
        }
        if (isset($_SERVER[$name]) && $_SERVER[$name] !== '') {
            return (string) $_SERVER[$name];
        }
        if (function_exists('env')) {
            $ciVal = env($name);
            if ($ciVal !== null && $ciVal !== '') {
                return (string) $ciVal;
            }
        }
        return $default;
    }
}
