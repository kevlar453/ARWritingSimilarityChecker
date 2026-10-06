<?php
/**
 * AR Tilik - Custom 400 Bad Request View
 * Desain modern bertema AR Tilik dengan instruksi pemulihan yang jelas.
 */
$homeUrl = function_exists('base_url') ? base_url('/') : '/';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>400 Permintaan Tidak Valid — AR Tilik</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500;600;700&display=swap" rel="stylesheet">
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
            max-width: 900px;
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
            box-shadow: 0 2px 4px rgba(30, 27, 75, 0.2);
        }
        .brand-badge {
            font-size: 10px;
            text-transform: uppercase;
            font-weight: 700;
            background: #f1f5f9;
            color: #475569;
            padding: 3px 8px;
            border-radius: 20px;
            border: 1px solid #cbd5e1;
        }
        .main-container {
            max-width: 680px;
            width: 100%;
            margin: 40px auto;
            padding: 0 20px;
            flex: 1;
        }
        .card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 18px;
            overflow: hidden;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -2px rgba(0, 0, 0, 0.05);
        }
        .card-header-bar {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            padding: 24px 28px;
            color: #ffffff;
            display: flex;
            align-items: center;
            gap: 16px;
        }
        .card-icon {
            width: 48px;
            height: 48px;
            background: rgba(245, 158, 11, 0.2);
            border: 1px solid rgba(251, 191, 36, 0.4);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            flex-shrink: 0;
        }
        .card-title {
            font-size: 20px;
            font-weight: 800;
            letter-spacing: -0.3px;
        }
        .card-subtitle {
            font-size: 13px;
            color: #94a3b8;
            margin-top: 2px;
        }
        .card-body {
            padding: 28px;
        }
        .info-desc {
            font-size: 14px;
            color: #475569;
            line-height: 1.6;
            margin-bottom: 20px;
        }
        .hint-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 24px;
        }
        .hint-title {
            font-size: 12px;
            font-weight: 700;
            color: #334155;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .hint-list {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 6px;
            font-size: 13px;
            color: #64748b;
        }
        .hint-list li {
            position: relative;
            padding-left: 18px;
        }
        .hint-list li::before {
            content: "•";
            position: absolute;
            left: 4px;
            color: #f59e0b;
            font-weight: bold;
        }
        .debug-box {
            background: #fffbeb;
            border: 1px solid #fef3c7;
            border-radius: 10px;
            padding: 12px 16px;
            margin-bottom: 20px;
            font-family: 'JetBrains Mono', monospace;
            font-size: 12px;
            color: #92400e;
            word-break: break-all;
        }
        .actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            align-items: center;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 10px 18px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.2s ease;
            border: none;
        }
        .btn-primary {
            background: #4338ca;
            color: #ffffff;
            box-shadow: 0 2px 4px rgba(67, 56, 202, 0.2);
        }
        .btn-primary:hover {
            background: #3730a3;
        }
        .btn-secondary {
            background: #f1f5f9;
            color: #334155;
            border: 1px solid #cbd5e1;
        }
        .btn-secondary:hover {
            background: #e2e8f0;
            color: #0f172a;
        }
        .footer {
            background: #ffffff;
            border-top: 1px solid #e2e8f0;
            padding: 16px 24px;
            font-size: 12px;
            color: #64748b;
            text-align: center;
        }
        @media (max-width: 640px) {
            .card-header-bar {
                flex-direction: column;
                align-items: flex-start;
            }
            .actions {
                flex-direction: column;
                width: 100%;
            }
            .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>

    <header class="header">
        <div class="header-inner">
            <a href="<?= esc($homeUrl) ?>" class="brand">
                <div class="brand-icon">🔬</div>
                <div>
                    <div>AR TILIK</div>
                </div>
            </a>
            <span class="brand-badge">Pemeriksa Kesepadanan Teks</span>
        </div>
    </header>

    <main class="main-container">
        <div class="card">
            <div class="card-header-bar">
                <div class="card-icon">⚠️</div>
                <div>
                    <h1 class="card-title">400 — Permintaan Tidak Valid</h1>
                    <p class="card-subtitle">Peladen tidak dapat memproses format permintaan yang dikirimkan.</p>
                </div>
            </div>

            <div class="card-body">
                <?php if (ENVIRONMENT !== 'production' && !empty($message)) : ?>
                    <div class="debug-box">
                        <strong>Keterangan Pengembang:</strong><br>
                        <?= nl2br(esc($message)) ?>
                    </div>
                <?php endif; ?>

                <p class="info-desc">
                    Permintaan yang dikirim oleh peramban web Anda mengandung sintaksis atau parameter yang tidak memenuhi format standar AR Tilik.
                </p>

                <div class="hint-box">
                    <div class="hint-title">Kemungkinan Penyebab:</div>
                    <ul class="hint-list">
                        <li>Parameter atau data input formulir tidak lengkap atau memiliki format data yang salah.</li>
                        <li>Berkas dokumen yang diunggah rusak, kosong, atau memiliki ekstensi yang tidak didukung.</li>
                        <li>Token sesi keamanan (CSRF Token) telah kedaluwarsa. Silakan muat ulang (refresh) halaman formulir dan coba ulangi.</li>
                    </ul>
                </div>

                <div class="actions">
                    <a href="javascript:history.back()" class="btn btn-primary">
                        <span>←</span> Kembali ke Halaman Sebelumnya
                    </a>
                    <a href="<?= esc($homeUrl) ?>" class="btn btn-secondary">
                        <span>📊</span> Buka Dashboard
                    </a>
                </div>
            </div>
        </div>
    </main>

    <footer class="footer">
        AR Tilik &copy; <?= date('Y') ?> &bull; Sistem Deteksi Kesepadanan Naskah Akademik Mandiri &bull; Universitas Sanata Dharma
    </footer>

</body>
</html>
