<?php
/**
 * AR Tilik - Custom Production Error View (500 / System Error)
 * Menggantikan halaman default Whoops/snag dengan tampilan elegan berstandar institusi
 * dan memastikan notifikasi insiden terkirim ke Telegram Administrator.
 */
$incidentId = null;
if (isset($exception) && $exception instanceof \Throwable) {
    try {
        $incidentId = \App\Libraries\TelegramNotifier::notifyException($exception);
    } catch (\Throwable $t) {
        // Failsafe
    }
}
if (!$incidentId) {
    $incidentId = 'INC-' . strtoupper(substr(md5(uniqid((string)mt_rand(), true)), 0, 8));
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Kendala Sistem #<?= esc($incidentId) ?> — AR Tilik</title>
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
            gap: 14px;
        }
        .card-icon {
            width: 44px;
            height: 44px;
            background: rgba(239, 68, 68, 0.2);
            border: 1px solid rgba(239, 68, 68, 0.4);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
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
        }
        .card-body {
            padding: 28px;
        }
        .notice-box {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 22px;
        }
        .notice-header {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #166534;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 6px;
        }
        .notice-desc {
            font-size: 12px;
            color: #15803d;
            line-height: 1.5;
        }
        .incident-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 24px;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        .incident-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 12px;
        }
        .incident-label {
            color: #64748b;
            font-weight: 500;
        }
        .incident-val {
            font-family: 'JetBrains Mono', monospace;
            font-weight: 700;
            color: #0f172a;
            background: #e2e8f0;
            padding: 2px 8px;
            border-radius: 6px;
            font-size: 12px;
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
        .btn-primary {
            background: #4338ca;
            color: #ffffff;
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

    <!-- Header -->
    <header class="header">
        <div class="header-inner">
            <a href="/" class="brand">
                <span class="brand-icon">🏛️</span>
                <span>AR TILIK</span>
            </a>
            <span class="brand-badge">Sistem Pemulihan Mandiri</span>
        </div>
    </header>

    <!-- Main Content -->
    <main class="main-container">
        <div class="card">
            <!-- Card Header -->
            <div class="card-header-bar">
                <div class="card-icon">⚠️</div>
                <div>
                    <h1 class="card-title">Terjadi Kendala pada Server</h1>
                    <div class="card-subtitle">Galat Internal 500 • Pemrosesan Terhenti Sementara</div>
                </div>
            </div>

            <!-- Card Body -->
            <div class="card-body">
                <!-- Telegram Notification Success Badge -->
                <div class="notice-box">
                    <div class="notice-header">
                        <span>📲</span>
                        <span>Detail Galat Telah Terkirim ke Telegram Administrator</span>
                    </div>
                    <div class="notice-desc">
                        Sistem keamanan dan observabilitas AR Tilik telah mendeteksi insiden ini dan secara otomatis mengirimkan rincian galat teknis langsung ke bot Telegram tim pengembang untuk penanganan segera.
                    </div>
                </div>

                <!-- Incident Details -->
                <div class="incident-box">
                    <div class="incident-row">
                        <span class="incident-label">Kode Referensi Insiden:</span>
                        <span class="incident-val">#<?= esc($incidentId) ?></span>
                    </div>
                    <div class="incident-row">
                        <span class="incident-label">Waktu Kejadian:</span>
                        <span style="font-size: 11px; color: #475569;"><?= date('d F Y, H:i:s') ?> WIB</span>
                    </div>
                    <div class="incident-row">
                        <span class="incident-label">Status Notifikasi:</span>
                        <span style="font-size: 11px; color: #166534; font-weight: 600;">✓ Notifikasi Realtime Berhasil</span>
                    </div>
                </div>

                <p style="font-size: 12px; color: #64748b; margin-bottom: 20px; line-height: 1.6;">
                    Anda tidak perlu khawatir data Anda hilang. Silakan muat ulang halaman setelah beberapa saat atau kembali ke menu utama. Jika kendala terus berulang, cantumkan <strong>Kode Referensi Insiden</strong> di atas saat melapor ke bagian akademik.
                </p>

                <!-- Actions -->
                <div class="actions">
                    <a href="/" class="btn btn-primary">
                        <span>← Kembali ke Dashboard Utama</span>
                    </a>
                    <button type="button" onclick="location.reload()" class="btn btn-secondary">
                        <span>🔄 Muat Ulang Halaman</span>
                    </button>
                    <a href="/verify" class="btn btn-secondary">
                        <span>🛡️ Portal Verifikasi</span>
                    </a>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="footer">
        <p>AR Tilik • Academic Writing Similarity & Evidence Checker • Dikembangkan dengan Prinsip Keadilan Akademik</p>
    </footer>

</body>
</html>
