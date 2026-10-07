<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($title ?? 'Kebijakan Privasi — AR Tilik') ?></title>
    <!-- Favicon & Brand Icons -->
    <link rel="icon" type="image/x-icon" href="<?= base_url('favicon.ico?v=20261006') ?>">
    <link rel="icon" type="image/png" sizes="32x32" href="<?= base_url('favicon-32x32.png?v=20261006') ?>">
    <link rel="icon" type="image/png" sizes="16x16" href="<?= base_url('favicon-16x16.png?v=20261006') ?>">
    <link rel="apple-touch-icon" sizes="180x180" href="<?= base_url('apple-touch-icon.png?v=20261006') ?>">
    <link rel="manifest" href="<?= base_url('site.webmanifest') ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #4338ca;
            --primary-dark: #312e81;
            --primary-light: #6366f1;
            --secondary: #0ea5e9;
            --dark: #0f172a;
            --light-bg: #f8fafc;
            --surface: #ffffff;
            --border: #e2e8f0;
            --text-main: #1e293b;
            --text-muted: #64748b;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: var(--light-bg);
            color: var(--text-main);
            line-height: 1.6;
        }

        .navbar {
            background: #ffffff;
            border-bottom: 1px solid var(--border);
            padding: 16px 0;
            position: sticky;
            top: 0;
            z-index: 50;
        }

        .container {
            max-width: 960px;
            margin: 0 auto;
            padding: 0 24px;
        }

        .nav-inner {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
        }

        .brand-logo {
            width: 38px;
            height: 38px;
            object-fit: contain;
        }

        .brand-title {
            font-size: 20px;
            font-weight: 800;
            color: var(--dark);
        }

        .btn-back {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 700;
            text-decoration: none;
            color: var(--primary);
            background: rgba(67, 56, 202, 0.08);
            border: 1px solid rgba(67, 56, 202, 0.2);
            transition: all 0.2s ease;
        }

        .btn-back:hover {
            background: var(--primary);
            color: #ffffff;
        }

        .legal-wrapper {
            padding: 50px 0 80px;
        }

        .legal-document {
            background: #ffffff;
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 48px;
            box-shadow: 0 4px 20px -2px rgba(0, 0, 0, 0.04);
        }

        .legal-header {
            margin-bottom: 30px;
            border-bottom: 1px solid var(--border);
            padding-bottom: 24px;
        }

        .legal-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            background: rgba(67, 56, 202, 0.1);
            color: var(--primary);
            font-size: 12px;
            font-weight: 700;
            margin-bottom: 12px;
            text-transform: uppercase;
        }

        .legal-title {
            font-size: 28px;
            font-weight: 800;
            color: var(--dark);
            line-height: 1.25;
            letter-spacing: -0.5px;
            margin-bottom: 8px;
        }

        .legal-subtitle {
            font-size: 13px;
            color: var(--text-muted);
        }

        .legal-callout {
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            border-left: 4px solid #3b82f6;
            border-radius: 12px;
            padding: 18px 22px;
            margin-bottom: 36px;
            font-size: 14px;
            color: #1e3a8a;
            line-height: 1.6;
        }

        .legal-section {
            margin-bottom: 34px;
        }

        .legal-section h2 {
            font-size: 19px;
            font-weight: 800;
            color: var(--dark);
            margin-bottom: 12px;
            letter-spacing: -0.3px;
        }

        .legal-section p {
            font-size: 14px;
            color: #334155;
            line-height: 1.7;
            margin-bottom: 12px;
        }

        .legal-section ul {
            padding-left: 20px;
            margin-bottom: 16px;
        }

        .legal-section li {
            font-size: 14px;
            color: #334155;
            line-height: 1.7;
            margin-bottom: 8px;
        }

        .legal-contact-card {
            background: #f8fafc;
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 18px 22px;
            font-size: 13px;
            color: #475569;
            line-height: 1.6;
            margin-top: 14px;
        }

        .legal-contact-card a {
            color: var(--primary);
            font-weight: 700;
            text-decoration: underline;
        }

        .footer {
            background: #0f172a;
            color: #94a3b8;
            padding: 30px 0;
            text-align: center;
            font-size: 13px;
            border-top: 1px solid #1e293b;
        }

        @media (max-width: 768px) {
            .legal-document { padding: 24px; }
            .legal-title { font-size: 22px; }
        }
    </style>
</head>
<body>
    <header class="navbar">
        <div class="container nav-inner">
            <a href="<?= base_url() ?>" class="brand">
                <img src="<?= base_url('images/logo.png') ?>" alt="AR Tilik" class="brand-logo">
                <span class="brand-title">AR Tilik</span>
            </a>
            <a href="<?= base_url() ?>" class="btn-back">
                &larr; Kembali ke Beranda
            </a>
        </div>
    </header>

    <main class="legal-wrapper">
        <div class="container">
            <?= view('legal/privacy_content') ?>
        </div>
    </main>

    <footer class="footer">
        <div class="container">
            AR Tilik &copy; <?= date('Y') ?> &bull; Sistem Penelaah Kesepadanan Naskah Akademik Mandiri &bull; <a href="https://www.arsetontong.top" target="_blank" rel="noopener noreferrer" style="color: inherit; text-decoration: underline;">Powered by ARSetontong</a>
        </div>
    </footer>
</body>
</html>
