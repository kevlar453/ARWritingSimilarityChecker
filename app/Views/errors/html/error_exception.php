<?php
/**
 * AR Tilik - Custom Exception Inspector View (Whoops Replacement)
 * Menggantikan halaman default Whoops dengan antarmuka elegan, modern, dan aman,
 * serta secara otomatis mengirimkan notifikasi rincian galat ke Telegram Bot.
 */
use CodeIgniter\CodeIgniter;

$incidentId = 'INC-' . strtoupper(substr(md5($file . $line . $exception->getMessage() . time()), 0, 8));
try {
    $incidentId = \App\Libraries\TelegramNotifier::notifyException($exception);
} catch (\Throwable $t) {
    // Failsafe
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title><?= esc($title) ?> (#<?= esc($incidentId) ?>) — AR Tilik Inspector</title>
    <!-- Favicon & Brand Icons -->
    <link rel="icon" type="image/x-icon" href="/favicon.ico?v=20261008">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png?v=20261008">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png?v=20261008">
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png?v=20261008">
    <link rel="shortcut icon" type="image/x-icon" href="/favicon.ico?v=20261008">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: #f8fafc;
            color: #1e293b;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            line-height: 1.5;
        }
        .header {
            background: #ffffff;
            border-bottom: 1px solid #e2e8f0;
            padding: 14px 24px;
        }
        .header-inner {
            max-width: 1000px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .brand {
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            color: #0f172a;
            font-weight: 800;
            font-size: 16px;
        }
        .brand-icon {
            width: 36px;
            height: 36px;
            background: linear-gradient(135deg, #1e1b4b, #3730a3);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            color: #ffffff;
        }
        .brand-badge {
            font-size: 11px;
            font-family: 'JetBrains Mono', monospace;
            font-weight: 700;
            background: #eff6ff;
            color: #1d4ed8;
            padding: 3px 10px;
            border-radius: 20px;
            border: 1px solid #bfdbfe;
        }
        .main-container {
            max-width: 1000px;
            width: 100%;
            margin: 30px auto;
            padding: 0 20px;
            flex: 1;
        }
        .card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 18px;
            overflow: hidden;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            margin-bottom: 24px;
        }
        .card-header-bar {
            background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 100%);
            padding: 24px 28px;
            color: #ffffff;
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
        }
        .title-group {
            display: flex;
            align-items: center;
            gap: 14px;
        }
        .card-icon {
            width: 46px;
            height: 46px;
            background: rgba(239, 68, 68, 0.2);
            border: 1px solid rgba(239, 68, 68, 0.4);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            flex-shrink: 0;
        }
        .card-title {
            font-size: 18px;
            font-weight: 800;
            letter-spacing: -0.3px;
        }
        .card-subtitle {
            font-size: 12px;
            color: #94a3b8;
            margin-top: 2px;
            font-family: 'JetBrains Mono', monospace;
        }
        .incident-badge-box {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(8px);
            border: 1px solid rgba(255, 255, 255, 0.15);
            padding: 8px 14px;
            border-radius: 12px;
            text-align: right;
        }
        .incident-badge-title {
            font-size: 9px;
            text-transform: uppercase;
            font-weight: 700;
            color: #94a3b8;
        }
        .incident-badge-code {
            font-family: 'JetBrains Mono', monospace;
            font-weight: 800;
            color: #38bdf8;
            font-size: 14px;
            letter-spacing: 0.5px;
        }
        .card-body {
            padding: 28px;
        }
        .telegram-alert-banner {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            border-radius: 12px;
            padding: 14px 18px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 22px;
            font-size: 12px;
            color: #166534;
        }
        .error-message-box {
            background: #fff1f2;
            border: 1px solid #fecdd3;
            border-radius: 12px;
            padding: 18px 20px;
            margin-bottom: 24px;
        }
        .error-message-title {
            font-size: 11px;
            text-transform: uppercase;
            font-weight: 800;
            color: #9f1239;
            letter-spacing: 0.5px;
            margin-bottom: 6px;
        }
        .error-message-text {
            font-size: 15px;
            font-weight: 700;
            color: #881337;
            line-height: 1.4;
            word-break: break-word;
        }
        .error-meta-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 12px;
            margin-bottom: 24px;
            font-size: 12px;
        }
        .meta-item {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 12px 14px;
        }
        .meta-item-label {
            color: #64748b;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
        }
        .meta-item-value {
            font-family: 'JetBrains Mono', monospace;
            font-weight: 600;
            color: #0f172a;
            word-break: break-all;
        }
        .actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            align-items: center;
            margin-bottom: 26px;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 10px 18px;
            border-radius: 10px;
            font-size: 12px;
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.2s ease;
            border: none;
        }
        .btn-primary { background: #4338ca; color: #ffffff; }
        .btn-primary:hover { background: #3730a3; }
        .btn-secondary { background: #f1f5f9; color: #334155; border: 1px solid #cbd5e1; }
        .btn-secondary:hover { background: #e2e8f0; }

        /* Accordion for Technical Details */
        details {
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            overflow: hidden;
            background: #ffffff;
            margin-bottom: 14px;
        }
        summary {
            padding: 14px 18px;
            font-size: 12px;
            font-weight: 700;
            color: #1e293b;
            background: #f8fafc;
            cursor: pointer;
            user-select: none;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        summary:hover { background: #f1f5f9; }
        .details-body {
            padding: 16px 18px;
            font-size: 11px;
            background: #ffffff;
            border-top: 1px solid #e2e8f0;
        }
        pre.code-block {
            background: #0f172a;
            color: #e2e8f0;
            padding: 14px 16px;
            border-radius: 8px;
            font-family: 'JetBrains Mono', monospace;
            font-size: 11px;
            overflow-x: auto;
            line-height: 1.5;
        }
        .trace-list {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 8px;
            font-family: 'JetBrains Mono', monospace;
            font-size: 11px;
        }
        .trace-item {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 8px 12px;
            display: flex;
            flex-direction: column;
            gap: 2px;
        }
        .trace-func {
            font-weight: 700;
            color: #4338ca;
        }
        .trace-file {
            color: #64748b;
            font-size: 10px;
        }
        .footer {
            border-top: 1px solid #e2e8f0;
            background: #ffffff;
            padding: 16px;
            text-align: center;
            font-size: 11px;
            color: #94a3b8;
        }
    </style>
</head>
<body>

    <!-- Top Bar -->
    <header class="header">
        <div class="header-inner">
            <a href="/" class="brand">
                <img src="/images/logo.png" alt="AR Tilik Logo" style="width: 28px; height: 28px; object-fit: contain;">
                <span>AR Tilik</span>
            </a>
            <span class="brand-badge">Developer &amp; Observability Mode</span>
        </div>
    </header>

    <!-- Main Container -->
    <main class="main-container">
        <div class="card">
            <!-- Header Bar -->
            <div class="card-header-bar">
                <div class="title-group">
                    <div class="card-icon">🛠️</div>
                    <div>
                        <h1 class="card-title"><?= esc($title) ?></h1>
                        <div class="card-subtitle"><?= esc(clean_path($file)) ?> : line <?= esc($line) ?></div>
                    </div>
                </div>
                <div class="incident-badge-box">
                    <div class="incident-badge-title">Incident Reference</div>
                    <div class="incident-badge-code">#<?= esc($incidentId) ?></div>
                </div>
            </div>

            <!-- Body -->
            <div class="card-body">
                <!-- Telegram Notification Success -->
                <div class="telegram-alert-banner">
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <span style="font-size: 16px;">📲</span>
                        <span><strong>Notifikasi Galat Terkirim:</strong> Rincian insiden teknis ini telah dikirimkan secara realtime ke Telegram Bot Administrator.</span>
                    </div>
                    <span style="font-weight: 700; font-family: 'JetBrains Mono', monospace; font-size: 10px; background: #bbf7d0; padding: 2px 8px; border-radius: 6px;">
                        DELIVERED
                    </span>
                </div>

                <!-- Exception Message -->
                <div class="error-message-box">
                    <div class="error-message-title">Pesan Eksepsi:</div>
                    <div class="error-message-text">
                        <?= nl2br(esc($exception->getMessage() ?: 'Tidak ada pesan galat tertulis.')) ?>
                    </div>
                </div>

                <!-- Meta Details -->
                <div class="error-meta-grid">
                    <div class="meta-item">
                        <div class="meta-item-label">HTTP Request</div>
                        <div class="meta-item-value"><?= esc($_SERVER['REQUEST_METHOD'] ?? 'GET') ?> <?= esc(mb_substr($_SERVER['REQUEST_URI'] ?? '/', 0, 40)) ?></div>
                    </div>
                    <div class="meta-item">
                        <div class="meta-item-label">Client IP</div>
                        <div class="meta-item-value"><?= esc($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1') ?></div>
                    </div>
                    <div class="meta-item">
                        <div class="meta-item-label">Waktu Eksekusi</div>
                        <div class="meta-item-value"><?= date('d M Y, H:i:s') ?> WIB</div>
                    </div>
                    <div class="meta-item">
                        <div class="meta-item-label">Versi Engine</div>
                        <div class="meta-item-value">PHP <?= esc(PHP_VERSION) ?> • CI <?= esc(CodeIgniter::CI_VERSION) ?></div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="actions">
                    <a href="/" class="btn btn-primary">
                        <span>← Kembali ke Dashboard</span>
                    </a>
                    <button type="button" onclick="location.reload()" class="btn btn-secondary">
                        <span>🔄 Muat Ulang</span>
                    </button>
                    <a href="/verify" class="btn btn-secondary">
                        <span>🛡️ Portal Verifikasi</span>
                    </a>
                </div>

                <!-- Collapsible Source Code Preview -->
                <?php if (is_file($file)): ?>
                <details open>
                    <summary>
                        <span>📄 Sumber Berkas &amp; Baris Eksepsi</span>
                        <span style="font-size: 10px; color: #64748b;">(Klik untuk melipat)</span>
                    </summary>
                    <div class="details-body">
                        <pre class="code-block"><?php
                            $lines = @file($file);
                            if ($lines !== false) {
                                $startLine = max(1, $line - 5);
                                $endLine = min(count($lines), $line + 5);
                                for ($i = $startLine; $i <= $endLine; $i++) {
                                    $isTarget = ($i === $line);
                                    $lineNum = str_pad((string)$i, 4, ' ', STR_PAD_LEFT);
                                    $lineContent = rtrim($lines[$i - 1]);
                                    $prefix = $isTarget ? ' >> ' : '    ';
                                    echo htmlspecialchars($prefix . $lineNum . ' | ' . $lineContent) . "\n";
                                }
                            } else {
                                echo "Tidak dapat membaca berkas.";
                            }
                        ?></pre>
                    </div>
                </details>
                <?php endif; ?>

                <!-- Collapsible Stack Trace -->
                <details>
                    <summary>
                        <span>🥞 Stack Trace Lengkap (<?= count($trace) ?> Frames)</span>
                        <span style="font-size: 10px; color: #64748b;">(Klik untuk membuka)</span>
                    </summary>
                    <div class="details-body">
                        <ul class="trace-list">
                            <?php foreach ($trace as $index => $t): ?>
                                <li class="trace-item">
                                    <div class="trace-func">
                                        #<?= $index ?> <?= esc(($t['class'] ?? '') . ($t['type'] ?? '') . ($t['function'] ?? '')) ?>(...)
                                    </div>
                                    <div class="trace-file">
                                        <?= esc(isset($t['file']) ? clean_path($t['file']) : '[internal function]') ?><?= isset($t['line']) ? ' : line ' . esc($t['line']) : '' ?>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </details>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="footer">
        <p>AR Tilik • Academic Writing Similarity &amp; Evidence Checker • Dikembangkan dengan Prinsip Keadilan Akademik</p>
    </footer>

</body>
</html>
