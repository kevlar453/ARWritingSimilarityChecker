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
        $this->botToken = env('TELEGRAM_BOT_TOKEN', '688091201:AAFQ0TiJfDZg2wSsuPvIPJtNa_5NaQetfm4');
        $this->chatId = env('TELEGRAM_CHAT_ID', '674868958');
    }

    /**
     * Mengirimkan pesan teks format HTML ke Telegram.
     */
    public function sendMessage(string $message): bool
    {
        if (empty($this->botToken) || empty($this->chatId)) {
            log_message('warning', 'TelegramNotifier: Token atau Chat ID tidak disetel.');
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
}
