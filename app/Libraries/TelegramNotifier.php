<?php

namespace App\Libraries;

/**
 * TelegramNotifier
 * Layanan pengiriman notifikasi instan ke Telegram Bot institusi.
 */
class TelegramNotifier
{
    protected string $botToken;
    protected string $chatId;

    public function __construct()
    {
        $token = function_exists('env') ? env('TELEGRAM_BOT_TOKEN') : getenv('TELEGRAM_BOT_TOKEN');
        $chat = function_exists('env') ? env('TELEGRAM_CHAT_ID') : getenv('TELEGRAM_CHAT_ID');

        $this->botToken = $token ?: '688091201:AAFQ0TiJfDZg2wSsuPvIPJtNa_5NaQetfm4';
        $this->chatId = $chat ?: '674868958';
    }

    /**
     * Mengirimkan pesan teks format HTML ke Telegram.
     */
    public function sendMessage(string $message): bool
    {
        if (empty($this->botToken) || empty($this->chatId)) {
            if (function_exists('log_message')) {
                log_message('warning', 'TelegramNotifier: Token atau Chat ID tidak disetel.');
            }
            return false;
        }

        $url = "https://api.telegram.org/bot{$this->botToken}/sendMessage";
        $data = [
            'chat_id'                  => $this->chatId,
            'text'                     => $message,
            'parse_mode'               => 'HTML',
            'disable_web_page_preview' => true,
        ];

        $options = [
            'http' => [
                'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                'method'  => 'POST',
                'content' => http_build_query($data),
                'timeout' => 5,
            ],
        ];

        try {
            $context = stream_context_create($options);
            $result = @file_get_contents($url, false, $context);
            if ($result === false) {
                return false;
            }
            $json = json_decode($result, true);
            return !empty($json['ok']);
        } catch (\Throwable $e) {
            log_message('error', 'TelegramNotifier Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Mengirimkan detail pengecualian/galat teknis secara terstruktur ke Telegram.
     * Dilengkapi mekanisme anti-spam lock (maks 1 alert identik per 60 detik)
     * dan mengembalikan Incident ID unik (misal: INC-8F3A2B1C).
     */
    public static function notifyException(\Throwable $exception, array $context = []): string
    {
        $hash = md5(get_class($exception) . $exception->getMessage() . $exception->getFile() . $exception->getLine());
        $incidentId = 'INC-' . strtoupper(substr($hash, 0, 8));

        // Tentukan direktori writable secara aman (baik saat di dalam framework CI maupun standalone)
        $writableDir = defined('WRITEPATH') ? WRITEPATH : (defined('APPPATH') ? dirname(APPPATH) . '/writable/' : sys_get_temp_dir() . '/');
        $cacheDir = rtrim($writableDir, '/\\') . '/cache/';
        $lockFile = $cacheDir . 'tg_err_' . $hash . '.lock';
        $now = time();
        if (file_exists($lockFile) && ($now - filemtime($lockFile)) < 60) {
            return $incidentId;
        }

        // Tulis lock file
        if (!is_dir($cacheDir)) {
            @mkdir($cacheDir, 0775, true);
        }
        @touch($lockFile);

        $className = get_class($exception);
        $message = $exception->getMessage() ?: '(Tanpa pesan galat)';
        $file = function_exists('clean_path') ? clean_path($exception->getFile()) : $exception->getFile();
        $line = $exception->getLine();
        $env = defined('ENVIRONMENT') ? ENVIRONMENT : 'unknown';

        $isCli = (PHP_SAPI === 'cli') || (function_exists('is_cli') && is_cli());
        $method = $_SERVER['REQUEST_METHOD'] ?? ($isCli ? 'CLI' : 'GET');
        $uri = $_SERVER['REQUEST_URI'] ?? ($isCli ? implode(' ', $_SERVER['argv'] ?? []) : '/');
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $timeStr = date('d M Y, H:i:s') . ' WIB';

        // Format 4 frame stack trace teratas
        $traceFrames = [];
        $rawTrace = $exception->getTrace();
        $maxFrames = min(4, count($rawTrace));
        for ($i = 0; $i < $maxFrames; $i++) {
            $t = $rawTrace[$i];
            $tFile = isset($t['file']) ? basename($t['file']) : '[internal]';
            $tLine = $t['line'] ?? '-';
            $tFunc = ($t['class'] ?? '') . ($t['type'] ?? '') . ($t['function'] ?? '');
            $traceFrames[] = "#{$i} {$tFile}({$tLine}): {$tFunc}()";
        }
        $traceText = !empty($traceFrames) ? implode("\n", $traceFrames) : '#0 [internal function]';

        $safeMessage = htmlspecialchars(mb_substr($message, 0, 400), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $safeFile = htmlspecialchars((string)$file, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $safeUri = htmlspecialchars(mb_substr((string)$uri, 0, 150), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        $text = "🚨 <b>[AR TILIK] SYSTEM EXCEPTION ALERT</b>\n";
        $text .= "<b>ID Insiden:</b> <code>#{$incidentId}</code>\n";
        $text .= "<b>Lingkungan:</b> <code>{$env}</code>\n";
        $text .= "<b>Permintaan:</b> <code>[{$method}] {$safeUri}</code>\n";
        $text .= "<b>IP Klien:</b> <code>{$ip}</code>\n";
        $text .= "<b>Waktu:</b> {$timeStr}\n\n";
        $text .= "<b>Tipe Galat:</b> <code>{$className}</code>\n";
        $text .= "<b>Pesan:</b>\n<code>{$safeMessage}</code>\n\n";
        $text .= "<b>Lokasi:</b>\n<code>{$safeFile}:{$line}</code>\n\n";
        $text .= "<b>Cuplikan Stack Trace:</b>\n<pre>{$traceText}</pre>";

        $instance = new self();
        $instance->sendMessage($text);

        return $incidentId;
    }
}
