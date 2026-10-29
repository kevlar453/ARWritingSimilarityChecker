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

    public const DEFAULT_ACTIVITY_CHAT_ID = '-1002426917612';

    public function __construct(?string $chatId = null)
    {
        $token = function_exists('env') ? env('TELEGRAM_BOT_TOKEN') : getenv('TELEGRAM_BOT_TOKEN');
        $chat = function_exists('env') ? env('TELEGRAM_CHAT_ID') : getenv('TELEGRAM_CHAT_ID');

        $this->botToken = $token ?: '688091201:AAFQ0TiJfDZg2wSsuPvIPJtNa_5NaQetfm4';
        $this->chatId = $chatId ?: ($chat ?: '674868958');
    }

    /**
     * Mengambil target Chat ID untuk aktivitas Duta Kampus & Feedback.
     */
    public static function getActivityChatId(): string
    {
        $chat = function_exists('env') ? env('TELEGRAM_ACTIVITY_CHAT_ID') : getenv('TELEGRAM_ACTIVITY_CHAT_ID');
        return $chat ?: self::DEFAULT_ACTIVITY_CHAT_ID;
    }

    /**
     * Mengirimkan pesan teks format HTML ke Telegram.
     */
    public function sendMessage(string $message, ?string $chatId = null): bool
    {
        $targetChatId = $chatId ?: $this->chatId;
        if (empty($this->botToken) || empty($targetChatId)) {
            if (function_exists('log_message')) {
                log_message('warning', 'TelegramNotifier: Token atau Chat ID tidak disetel.');
            }
            return false;
        }

        $url = "https://api.telegram.org/bot{$this->botToken}/sendMessage";
        $data = [
            'chat_id'                  => $targetChatId,
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
            if (function_exists('log_message')) {
                log_message('error', 'TelegramNotifier Error: ' . $e->getMessage());
            }
            return false;
        }
    }

    /**
     * Helper untuk membersihkan nomor telepon menjadi format internasional (misal 628xxx)
     */
    protected static function cleanWaNumber(string $phone): string
    {
        $clean = preg_replace('/[^0-9]/', '', $phone);
        if (str_starts_with($clean, '0')) {
            $clean = '62' . substr($clean, 1);
        }
        return $clean;
    }

    /**
     * Mengirimkan notifikasi Pendaftaran Duta Kampus Baru (Tahap 1)
     */
    public static function notifyAmbassadorApplication(array $data, ?string $chatId = null): bool
    {
        $targetChat = $chatId ?: self::getActivityChatId();
        $notifier = new self($targetChat);

        $name = htmlspecialchars($data['full_name'] ?? 'Pendaftar', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $institution = htmlspecialchars($data['institution'] ?? '-', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $major = htmlspecialchars($data['major'] ?? '-', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $semester = htmlspecialchars((string)($data['semester'] ?? '-'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $email = htmlspecialchars($data['email'] ?? '-', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $wa = htmlspecialchars($data['whatsapp_number'] ?? '-', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $cleanWa = self::cleanWaNumber((string)($data['whatsapp_number'] ?? ''));
        $network = htmlspecialchars(mb_substr($data['campus_network'] ?? '-', 0, 300), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $hasPurchased = !empty($data['has_purchased']) ? '✅ Memenuhi Syarat (Pernah Beli Paket)' : '⚠️ Belum Pernah Beli Paket';
        $timeStr = date('d M Y, H:i') . ' WIB';
        $adminUrl = function_exists('base_url') ? base_url('admin') : 'https://tilik.arsetontong.top/admin';

        $msg = "🎓 <b>[AR TILIK] PENDAFTARAN DUTA KAMPUS BARU</b>\n\n";
        $msg .= "👤 <b>Nama:</b> {$name}\n";
        $msg .= "🏛️ <b>Kampus:</b> {$institution}\n";
        $msg .= "📚 <b>Prodi / Semester:</b> {$major} (Semester {$semester})\n";
        $msg .= "💬 <b>WhatsApp:</b> <a href=\"https://wa.me/{$cleanWa}\">{$wa}</a>\n";
        $msg .= "✉️ <b>Email:</b> {$email}\n";
        $msg .= "🎯 <b>Status Kualifikasi:</b> {$hasPurchased}\n\n";
        $msg .= "🌐 <b>Jaringan & Basis Relasi:</b>\n<i>\"{$network}\"</i>\n\n";
        $msg .= "🕒 <b>Waktu:</b> {$timeStr}\n\n";
        $msg .= "👉 <a href=\"{$adminUrl}\">Buka Portal Admin untuk Review & Loloskan Seleksi</a>";

        return $notifier->sendMessage($msg, $targetChat);
    }

    /**
     * Mengirimkan notifikasi Pengunggahan Berkas Identitas / KTM (Tahap 2)
     */
    public static function notifyAmbassadorKtmUploaded(array $data, ?string $chatId = null): bool
    {
        $targetChat = $chatId ?: self::getActivityChatId();
        $notifier = new self($targetChat);

        $name = htmlspecialchars($data['full_name'] ?? 'Calon Duta', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $institution = htmlspecialchars($data['institution'] ?? '-', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $wa = htmlspecialchars($data['whatsapp_number'] ?? '-', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $cleanWa = self::cleanWaNumber((string)($data['whatsapp_number'] ?? ''));
        $email = htmlspecialchars($data['email'] ?? '-', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $idType = ($data['id_card_type'] ?? '') === 'ktp_dosen' ? 'KTP / ID Card Dosen' : 'KTM (Kartu Tanda Mahasiswa)';
        $timeStr = date('d M Y, H:i') . ' WIB';
        $adminUrl = function_exists('base_url') ? base_url('admin') : 'https://tilik.arsetontong.top/admin';

        $msg = "🪪 <b>[AR TILIK] UNGGAHAN BERKAS IDENTITAS / KTM</b>\n\n";
        $msg .= "👤 <b>Nama Calon Duta:</b> {$name}\n";
        $msg .= "🏛️ <b>Kampus:</b> {$institution}\n";
        $msg .= "📋 <b>Jenis Berkas:</b> {$idType}\n";
        $msg .= "💬 <b>WhatsApp:</b> <a href=\"https://wa.me/{$cleanWa}\">{$wa}</a>\n";
        $msg .= "✉️ <b>Email:</b> {$email}\n\n";
        $msg .= "🕒 <b>Waktu:</b> {$timeStr}\n\n";
        $msg .= "👉 <a href=\"{$adminUrl}\">Buka Portal Admin untuk Verifikasi KTM & Aktivasi Kode Referral</a>";

        return $notifier->sendMessage($msg, $targetChat);
    }

    /**
     * Mengirimkan notifikasi Bukti Promosi Aktivitas Bulanan Masuk (Tahap 5 & 6)
     */
    public static function notifyAmbassadorProofSubmitted(array $data, ?string $chatId = null): bool
    {
        $targetChat = $chatId ?: self::getActivityChatId();
        $notifier = new self($targetChat);

        $name = htmlspecialchars($data['ambassador_name'] ?? 'Duta Kampus', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $institution = htmlspecialchars($data['institution'] ?? '-', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $code = htmlspecialchars($data['referral_code'] ?? '-', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $proofType = strtoupper(str_replace('_', ' ', (string)($data['proof_type'] ?? 'Story WA')));
        $title = htmlspecialchars($data['title'] ?? 'Promosi Kampus', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $desc = htmlspecialchars(mb_substr($data['description'] ?? '-', 0, 300), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $hasFile = !empty($data['has_file']) ? '✅ Ada (Tersimpan Aman)' : '❌ Tidak Ada';
        $urlLink = !empty($data['url_link']) ? htmlspecialchars($data['url_link'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') : '-';
        $timeStr = date('d M Y, H:i') . ' WIB';
        $adminUrl = function_exists('base_url') ? base_url('admin') : 'https://tilik.arsetontong.top/admin';

        $msg = "📸 <b>[AR TILIK] BUKTI PROMOSI DUTA KAMPUS MASUK</b>\n\n";
        $msg .= "👤 <b>Duta Kampus:</b> {$name} (<code>{$code}</code>)\n";
        $msg .= "🏛️ <b>Kampus:</b> {$institution}\n";
        $msg .= "📢 <b>Kanal / Jenis:</b> {$proofType}\n";
        $msg .= "📌 <b>Judul Bukti:</b> {$title}\n";
        $msg .= "🖼️ <b>Screenshot Fisik:</b> {$hasFile}\n";
        $msg .= "🔗 <b>Tautan Publik:</b> {$urlLink}\n\n";
        if ($desc !== '-') {
            $msg .= "📝 <b>Catatan Promosi:</b>\n<i>\"{$desc}\"</i>\n\n";
        }
        $msg .= "🕒 <b>Waktu:</b> {$timeStr}\n\n";
        $msg .= "👉 <a href=\"{$adminUrl}\">Buka Portal Admin untuk Tinjau & Setujui Bukti Promosi</a>";

        return $notifier->sendMessage($msg, $targetChat);
    }

    /**
     * Mengirimkan notifikasi Pengajuan Penarikan Saldo Komisi Duta Kampus (Payout Request)
     */
    public static function notifyAmbassadorPayoutRequested(array $data, ?string $chatId = null): bool
    {
        $targetChat = $chatId ?: self::getActivityChatId();
        $notifier = new self($targetChat);

        $code = htmlspecialchars($data['payout_code'] ?? 'WD-XXXX', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $name = htmlspecialchars($data['ambassador_name'] ?? 'Duta Kampus', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $refCode = htmlspecialchars($data['referral_code'] ?? '-', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $institution = htmlspecialchars($data['institution'] ?? '-', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $amount = number_format((float)($data['amount'] ?? 0), 0, ',', '.');
        $channel = htmlspecialchars(strtoupper((string)($data['payout_channel'] ?? 'BANK')), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $accNumber = htmlspecialchars((string)($data['payout_account_number'] ?? '-'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $accName = htmlspecialchars((string)($data['payout_account_name'] ?? '-'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $remaining = number_format((float)($data['remaining_balance'] ?? 0), 0, ',', '.');
        $timeStr = date('d M Y, H:i') . ' WIB';
        $adminUrl = function_exists('base_url') ? base_url('admin') : 'https://tilik.arsetontong.top/admin';

        $msg = "💸 <b>[AR TILIK] PENGAJUAN PENARIKAN KOMISI DUTA KAMPUS</b>\n\n";
        $msg .= "🔖 <b>Kode Penarikan:</b> <code>{$code}</code>\n";
        $msg .= "👤 <b>Duta Kampus:</b> {$name} (<code>{$refCode}</code>)\n";
        $msg .= "🏛️ <b>Kampus:</b> {$institution}\n\n";
        $msg .= "💰 <b>Jumlah Penarikan:</b> <b>Rp {$amount}</b>\n";
        $msg .= "🏦 <b>Metode / Bank:</b> {$channel}\n";
        $msg .= "🔢 <b>No. Rekening / E-Wallet:</b> <code>{$accNumber}</code>\n";
        $msg .= "📝 <b>Atas Nama:</b> <b>{$accName}</b>\n";
        $msg .= "💳 <b>Sisa Saldo Komisi:</b> Rp {$remaining}\n\n";
        $msg .= "🕒 <b>Waktu Pengajuan:</b> {$timeStr}\n\n";
        $msg .= "👉 <a href=\"{$adminUrl}\">Buka Portal Admin untuk Proses Transfer Dana</a>";

        return $notifier->sendMessage($msg, $targetChat);
    }

    /**
     * Mengirimkan notifikasi Testimoni & Evaluasi Akademik Pengguna
     */
    public static function notifyTestimonialSubmitted(array $data, ?string $chatId = null): bool
    {
        $targetChat = $chatId ?: self::getActivityChatId();
        $notifier = new self($targetChat);

        $rating = max(1, min(5, (int)($data['rating'] ?? 5)));
        $stars = str_repeat('⭐', $rating) . " ({$rating}.0 / 5.0)";
        $author = htmlspecialchars($data['author_name'] ?? 'Akademisi AR Tilik', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $role = htmlspecialchars($data['author_role'] ?? 'Mahasiswa', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $institution = htmlspecialchars($data['institution'] ?: '-', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $reportCode = htmlspecialchars((string)($data['report_code'] ?? '-'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $content = htmlspecialchars(mb_substr($data['content'] ?? '', 0, 500), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $allowPublic = !empty($data['allow_public_display']) ? '✅ Boleh Tayang Publik' : '🔒 Khusus Internal';
        $isAnon = !empty($data['is_anonymous']) ? '🎭 Identitas Disamarkan' : '👤 Nama Terbuka';
        $timeStr = date('d M Y, H:i') . ' WIB';
        $adminUrl = function_exists('base_url') ? base_url('admin') : 'https://tilik.arsetontong.top/admin';

        $msg = "🌟 <b>[AR TILIK] TESTIMONI & EVALUASI AKADEMIK BARU</b>\n\n";
        $msg .= "⭐ <b>Penilaian:</b> {$stars}\n";
        $msg .= "👤 <b>Penulis:</b> {$author} ({$role})\n";
        $msg .= "🏛️ <b>Institusi:</b> {$institution}\n";
        $msg .= "📄 <b>Verifikasi Naskah:</b> <code>#{$reportCode}</code>\n";
        $msg .= "🌐 <b>Status Tayang:</b> {$allowPublic} • {$isAnon}\n\n";
        $msg .= "💬 <b>Isi Testimoni:</b>\n<i>\"{$content}\"</i>\n\n";
        $msg .= "🕒 <b>Waktu:</b> {$timeStr}\n\n";
        $msg .= "👉 <a href=\"{$adminUrl}\">Buka Portal Admin untuk Moderasi & Setujui Testimoni</a>";

        return $notifier->sendMessage($msg, $targetChat);
    }

    /**
     * Mengirimkan notifikasi Feedback Fitur (Vote & Komentar)
     */
    public static function notifyFeatureFeedback(array $data, ?string $chatId = null): bool
    {
        $targetChat = $chatId ?: self::getActivityChatId();
        $notifier = new self($targetChat);

        $feature = htmlspecialchars(strtoupper(str_replace('_', ' ', (string)($data['feature_name'] ?? 'Fitur'))), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $voteVal = (int)($data['vote_value'] ?? 1);
        $voteIcon = ($voteVal === 1) ? '👍 <b>Sangat Membantu (+1)</b>' : '👎 <b>Perlu Peningkatan (-1)</b>';
        $reportId = (int)($data['report_id'] ?? 0);
        $feedbackText = !empty($data['feedback_text']) ? htmlspecialchars(mb_substr((string)$data['feedback_text'], 0, 400), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') : null;
        $userId = !empty($data['user_id']) ? "User #{$data['user_id']}" : 'Tamu / Anonim';
        $timeStr = date('d M Y, H:i') . ' WIB';

        $msg = "🗳️ <b>[AR TILIK] MASUKAN FITUR (USER FEEDBACK)</b>\n\n";
        $msg .= "⚡ <b>Fitur:</b> {$feature}\n";
        $msg .= "📊 <b>Penilaian:</b> {$voteIcon}\n";
        if ($reportId > 0) {
            $msg .= "📄 <b>Laporan Naskah:</b> <code>#{$reportId}</code>\n";
        }
        $msg .= "👤 <b>Pengguna:</b> {$userId}\n\n";
        if (!empty($feedbackText)) {
            $msg .= "📝 <b>Catatan Masukan:</b>\n<i>\"{$feedbackText}\"</i>\n\n";
        }
        $msg .= "🕒 <b>Waktu:</b> {$timeStr}";

        return $notifier->sendMessage($msg, $targetChat);
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

        // Abaikan notifikasi untuk 404 / PageNotFoundException agar tidak memicu false alarm
        if ($exception instanceof \CodeIgniter\Exceptions\PageNotFoundException) {
            return $incidentId;
        }

        $message = $exception->getMessage() ?: '(Tanpa pesan galat)';
        $isCli = (PHP_SAPI === 'cli') || (function_exists('is_cli') && is_cli());

        // Abaikan galat Broken pipe (EPIPE / errno=32) di CLI (misal output di-pipe ke head, less, grep)
        if ($isCli && (stripos($message, 'Broken pipe') !== false || stripos($message, 'errno=32') !== false)) {
            return $incidentId;
        }

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
