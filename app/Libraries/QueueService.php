<?php

namespace App\Libraries;

/**
 * AR Tilik - High-Throughput Queue Service (Fase 1 - v1.4)
 * Mendukung pengiriman tugas asinkron langsung ke Redis Streams/Lists
 * dengan fallback mulus ke tabel jobs MariaDB.
 */
class QueueService
{
    protected static ?string $redisHost = null;
    protected static ?int $redisPort = null;

    /**
     * Mengirim tugas (job) ke antrean MariaDB dan Redis secara sinkron.
     *
     * @param string $type Jenis pekerjaan (similarity_check, index_corpus, dll)
     * @param array $payload Data muatan pekerjaan
     * @param string|null $priority 'fast' | 'heavy' | null
     * @return int ID pekerjaan di basis data MariaDB
     */
    public static function dispatch(string $type, array $payload, ?string $priority = null): int
    {
        $db = db_connect('default');
        $now = date('Y-m-d H:i:s');

        // 1. Catat ke MariaDB jobs table untuk audit trail & failover
        $db->table('jobs')->insert([
            'type'         => $type,
            'payload'      => json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            'status'       => 'pending',
            'available_at' => $now,
            'created_at'   => $now,
            'updated_at'   => $now,
        ]);

        $jobId = (int) $db->insertID();

        // 2. Kirim notifikasi event ke Redis jika tersedia
        self::pushToRedis($jobId, $type, $payload, $priority);

        return $jobId;
    }

    /**
     * Mendorong notifikasi tugas ke Redis melalui socket TCP ringan tanpa dependensi ekstensi.
     */
    protected static function pushToRedis(int $jobId, string $type, array $payload, ?string $priority = null): bool
    {
        $host = self::$redisHost ?? env('REDIS_HOST', '127.0.0.1');
        $port = self::$redisPort ?? (int) env('REDIS_PORT', 6379);

        // Tentukan antrean target: fast vs heavy
        $engine = $payload['engine'] ?? 'hybrid';
        $targetQueue = $priority;
        if (! $targetQueue) {
            if (in_array($type, ['index_corpus', 'reindex', 'notify'], true) || in_array($engine, ['exact', 'idf'], true)) {
                $targetQueue = 'artilik:queue:fast';
            } else {
                $targetQueue = 'artilik:queue:heavy';
            }
        } elseif (! str_starts_with($targetQueue, 'artilik:queue:')) {
            $targetQueue = 'artilik:queue:' . $targetQueue;
        }

        $packet = json_encode([
            'id'          => $jobId,
            'type'        => $type,
            'payload'     => $payload,
            'enqueued_at' => date('c'),
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        try {
            $socket = @stream_socket_client("tcp://{$host}:{$port}", $errno, $errstr, 0.2);
            if (! $socket) {
                return false;
            }

            stream_set_timeout($socket, 0, 200000); // 200ms timeout
            $cmd = "*3\r\n$5\r\nLPUSH\r\n$" . strlen($targetQueue) . "\r\n{$targetQueue}\r\n$" . strlen($packet) . "\r\n{$packet}\r\n";
            fwrite($socket, $cmd);
            $res = fgets($socket);
            fclose($socket);

            return $res && str_starts_with($res, ':');
        } catch (\Throwable $e) {
            return false;
        }
    }
}
