<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title><?= esc($title ?? 'Perbandingan AR Tilik vs ZeroGPT — Whitepaper Riset Integritas Akademik') ?></title>
    <meta name="description" content="Analisis komparatif berbasis riset independen: Mengapa AR Tilik mengaudit bukti tekstual & rujukan nyata, sementara detektor probabilitas AI seperti ZeroGPT kerap memicu false-positive tinggi pada naskah akademik Indonesia.">
    <meta name="robots" content="index, follow, max-snippet:-1, max-image-preview:large">
    <link rel="canonical" href="<?= base_url('riset/ar-tilik-vs-zerogpt') ?>">
    <meta name="theme-color" content="#4338ca">

    <!-- Open Graph -->
    <meta property="og:type" content="article">
    <meta property="og:locale" content="id_ID">
    <meta property="og:site_name" content="AR Tilik">
    <meta property="og:title" content="Bukan Lomba Menebak AI: Perbandingan AR Tilik vs ZeroGPT">
    <meta property="og:description" content="Analisis berbasis riset independen mengenai audit bukti integritas naskah vs deteksi probabilitas gaya bahasa AI.">
    <meta property="og:url" content="<?= base_url('riset/ar-tilik-vs-zerogpt') ?>">
    <meta property="og:image" content="<?= base_url('images/og-artilik.png') ?>">

    <!-- Favicon & Brand Icons -->
    <link rel="icon" type="image/x-icon" href="<?= base_url('favicon.ico?v=20261008') ?>">
    <link rel="icon" type="image/png" sizes="32x32" href="<?= base_url('favicon-32x32.png?v=20261008') ?>">
    <link rel="icon" type="image/png" sizes="16x16" href="<?= base_url('favicon-16x16.png?v=20261008') ?>">
    <link rel="apple-touch-icon" sizes="180x180" href="<?= base_url('apple-touch-icon.png?v=20261008') ?>">
    <link rel="manifest" href="<?= base_url('site.webmanifest') ?>">

    <!-- Google Fonts: Plus Jakarta Sans & JetBrains Mono (AR Tilik Official Fonts) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary: #4338ca;
            --primary-dark: #312e81;
            --primary-light: #6366f1;
            --secondary: #0ea5e9;
            --accent-emerald: #059669;
            --accent-amber: #d97706;
            --accent-orange: #ea580c;
            --accent-rose: #e11d48;
            --dark: #0f172a;
            --dark-surface: #1e293b;
            --light-bg: #f8fafc;
            --surface: #ffffff;
            --border: #e2e8f0;
            --border-subtle: #cbd5e1;
            --text-main: #0f172a;
            --text-muted: #64748b;
            --sans: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            --mono: 'JetBrains Mono', ui-monospace, SFMono-Regular, monospace;
            --shadow-sm: 0 1px 3px rgba(15, 23, 42, 0.06);
            --shadow-md: 0 4px 20px -2px rgba(15, 23, 42, 0.07);
            --shadow-lg: 0 16px 40px -10px rgba(15, 23, 42, 0.12);
            --nav-height: 58px;
            --subnav-height: 48px;
            --header-offset: calc(var(--nav-height) + var(--subnav-height) + 24px);
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }
        html {
            scroll-behavior: smooth;
            scroll-padding-top: var(--header-offset, 130px);
            overflow-x: hidden;
        }
        body {
            font-family: var(--sans);
            background: var(--light-bg);
            color: var(--text-main);
            line-height: 1.65;
            text-rendering: optimizeLegibility;
            -webkit-font-smoothing: antialiased;
            overflow-x: hidden;
        }
        a { color: inherit; text-decoration: none; }
        a:focus-visible, button:focus-visible { outline: 3px solid var(--primary); outline-offset: 3px; }

        .container {
            max-width: 1180px;
            margin: 0 auto;
            padding: 0 24px;
        }

        /* ─── Fixed Glassmorphism Navbar (AR Tilik Standard) ─── */
        .navbar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            width: 100%;
            z-index: 1000;
            background: rgba(255, 255, 255, 0.94);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--border);
            padding: 10px 0;
            transition: all 0.2s ease;
        }
        .nav-inner {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
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
            border-radius: 8px;
        }
        .brand-title {
            font-size: 19px;
            font-weight: 800;
            color: var(--dark);
            letter-spacing: -0.5px;
        }
        .brand-badge {
            background: rgba(67, 56, 202, 0.1);
            color: var(--primary);
            font-size: 11px;
            font-weight: 700;
            padding: 3px 9px;
            border-radius: 6px;
            border: 1px solid rgba(67, 56, 202, 0.2);
            text-transform: uppercase;
        }
        .nav-links {
            display: flex;
            align-items: center;
            gap: 22px;
            list-style: none;
        }
        .nav-links a {
            font-size: 14px;
            font-weight: 600;
            color: var(--text-muted);
            transition: color 0.15s ease;
        }
        .nav-links a:hover { color: var(--primary); }
        .nav-actions {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .btn-nav-back {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 14px;
            border-radius: 9999px;
            font-size: 13px;
            font-weight: 700;
            color: var(--text-muted);
            background: #f1f5f9;
            border: 1px solid var(--border);
            transition: all 0.15s ease;
        }
        .btn-nav-back:hover {
            color: var(--dark);
            background: #e2e8f0;
        }
        .btn-nav-primary {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            border-radius: 9999px;
            font-size: 13px;
            font-weight: 700;
            color: #ffffff;
            background: var(--primary);
            box-shadow: 0 4px 12px rgba(67, 56, 202, 0.25);
            transition: all 0.15s ease;
        }
        .btn-nav-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-1px);
        }

        /* ─── Breadcrumbs & Masthead Hero ─── */
        .hero-wrap {
            padding-top: 110px;
            padding-bottom: 48px;
            background: radial-gradient(circle at 85% 20%, rgba(67, 56, 202, 0.08) 0%, transparent 50%),
                        radial-gradient(circle at 15% 60%, rgba(14, 165, 233, 0.06) 0%, transparent 40%),
                        #ffffff;
            border-bottom: 1px solid var(--border);
        }
        .breadcrumbs {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 12px;
            font-weight: 600;
            color: var(--text-muted);
            margin-bottom: 20px;
        }
        .breadcrumbs a:hover { color: var(--primary); }
        .mast-grid {
            display: grid;
            grid-template-columns: minmax(0, 1.4fr) minmax(300px, 0.8fr);
            gap: clamp(24px, 5vw, 64px);
            align-items: center;
        }
        .pill-tag {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 5px 14px;
            border-radius: 9999px;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            background: rgba(67, 56, 202, 0.08);
            color: var(--primary);
            border: 1px solid rgba(67, 56, 202, 0.2);
            margin-bottom: 18px;
        }
        .pill-tag .dot { width: 7px; height: 7px; border-radius: 50%; background: var(--primary); }
        h1.hero-title {
            font-size: clamp(32px, 4.2vw, 54px);
            font-weight: 800;
            line-height: 1.15;
            letter-spacing: -1.2px;
            color: var(--dark);
            margin-bottom: 20px;
        }
        h1.hero-title em {
            font-style: normal;
            background: linear-gradient(90deg, #4338ca, #0284c7);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .hero-lead {
            font-size: clamp(16px, 1.4vw, 19px);
            color: #475569;
            line-height: 1.6;
            margin-bottom: 20px;
        }
        .hero-meta {
            display: flex;
            align-items: center;
            gap: 16px;
            font-size: 13px;
            color: var(--text-muted);
            flex-wrap: wrap;
        }

        /* Verdict Card (Dark Navy Accent) */
        .verdict-card {
            background: var(--dark);
            border: 1px solid #1e293b;
            border-radius: 24px;
            padding: clamp(26px, 4vw, 36px);
            color: #f8fafc;
            box-shadow: 0 20px 45px -10px rgba(15, 23, 42, 0.35);
            position: relative;
            overflow: hidden;
        }
        .verdict-card::before {
            content: "“";
            position: absolute;
            top: -20px;
            right: 20px;
            font-size: 120px;
            color: rgba(255, 255, 255, 0.05);
            font-family: Georgia, serif;
            line-height: 1;
            pointer-events: none;
        }
        .verdict-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(245, 158, 11, 0.15);
            color: #fbbf24;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 4px 12px;
            border-radius: 9999px;
            margin-bottom: 14px;
            border: 1px solid rgba(245, 158, 11, 0.3);
        }
        .verdict-card h3 {
            font-size: 18px;
            font-weight: 800;
            color: #ffffff;
            margin-bottom: 10px;
            letter-spacing: -0.3px;
        }
        .verdict-card p {
            font-size: 14px;
            color: #cbd5e1;
            line-height: 1.6;
            margin-bottom: 0;
        }

        /* ─── Sticky Sub-Navigation Bar ─── */
        .subnav {
            position: sticky;
            top: var(--nav-height, 58px);
            z-index: 990;
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(14px);
            -webkit-backdrop-filter: blur(14px);
            border-bottom: 1px solid var(--border);
            padding: 8px 0;
            box-shadow: 0 4px 14px -2px rgba(15, 23, 42, 0.05);
        }
        .subnav-inner {
            display: flex;
            align-items: center;
            gap: 8px;
            overflow-x: auto;
            white-space: nowrap;
            -webkit-overflow-scrolling: touch;
            scrollbar-width: none;
            padding: 2px 0;
        }
        .subnav-inner::-webkit-scrollbar { display: none; }
        .subnav-link {
            font-size: 12.5px;
            font-weight: 700;
            color: var(--text-muted);
            padding: 7px 15px;
            border-radius: 9999px;
            transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
            display: inline-flex;
            align-items: center;
            gap: 6px;
            border: 1px solid transparent;
            text-decoration: none;
            white-space: nowrap;
        }
        .subnav-link:hover {
            color: var(--dark);
            background: #f1f5f9;
            border-color: var(--border);
        }
        .subnav-link.active {
            color: #ffffff;
            background: var(--primary);
            border-color: var(--primary);
            box-shadow: 0 2px 8px rgba(67, 56, 202, 0.28);
        }

        /* ─── Section Shared Styles & Scroll Margins ─── */
        section[id] {
            scroll-margin-top: var(--header-offset, 130px);
        }
        .section-white {
            background: #ffffff;
            border-bottom: 1px solid var(--border);
            padding: clamp(60px, 8vw, 96px) 0;
        }
        .section-slate {
            background: var(--light-bg);
            border-bottom: 1px solid var(--border);
            padding: clamp(60px, 8vw, 96px) 0;
        }
        .section-header {
            max-width: 820px;
            margin-bottom: 40px;
        }
        .section-number {
            font-family: var(--mono);
            font-size: 12px;
            font-weight: 700;
            color: var(--primary);
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 8px;
        }
        .section-title {
            font-size: clamp(24px, 3.2vw, 38px);
            font-weight: 800;
            color: var(--dark);
            line-height: 1.25;
            letter-spacing: -0.8px;
            margin-bottom: 12px;
        }
        .section-desc {
            font-size: 15px;
            color: var(--text-muted);
            line-height: 1.6;
        }

        /* ─── 01 Comparison Matrix Table ─── */
        .comparison-card {
            background: #ffffff;
            border: 1px solid var(--border);
            border-radius: 20px;
            overflow: hidden;
            box-shadow: var(--shadow-sm);
        }
        .comparison-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }
        .comparison-table th, .comparison-table td {
            padding: 18px 22px;
            border-bottom: 1px solid var(--border);
            vertical-align: top;
            text-align: left;
        }
        .comparison-table thead th {
            background: #f8fafc;
            font-size: 12px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--text-muted);
        }
        .comparison-table thead th.th-ar {
            background: rgba(67, 56, 202, 0.06);
            color: var(--primary);
            font-size: 14px;
            border-bottom: 2px solid var(--primary);
        }
        .comparison-table thead th.th-zero {
            background: rgba(234, 88, 12, 0.05);
            color: var(--accent-orange);
            font-size: 14px;
            border-bottom: 2px solid var(--accent-orange);
        }
        .comparison-table tbody tr:last-child td { border-bottom: none; }
        .comparison-table .td-label {
            font-weight: 700;
            color: var(--dark);
            font-size: 13px;
            width: 25%;
            background: #fbfcfe;
        }
        .comparison-table .td-ar {
            background: rgba(67, 56, 202, 0.02);
            color: #1e293b;
            width: 38%;
        }
        .comparison-table .td-zero {
            background: #ffffff;
            color: #475569;
            width: 37%;
        }

        .alert-callout {
            margin-top: 24px;
            padding: 20px 24px;
            border-radius: 16px;
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            display: flex;
            align-items: flex-start;
            gap: 16px;
            font-size: 14px;
            color: #1e3a8a;
            line-height: 1.6;
        }
        .alert-callout strong { color: #1e40af; }

        /* ─── 02 Empirical Studies Split ─── */
        .studies-grid {
            display: grid;
            grid-template-columns: minmax(0, 0.8fr) minmax(0, 1.2fr);
            gap: 28px;
            align-items: stretch;
        }
        .vendor-claim-card {
            background: linear-gradient(135deg, #ea580c 0%, #c2410c 100%);
            border-radius: 20px;
            padding: clamp(28px, 4vw, 40px);
            color: #ffffff;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            box-shadow: 0 16px 36px -8px rgba(234, 88, 12, 0.35);
        }
        .claim-badge {
            display: inline-block;
            background: rgba(255, 255, 255, 0.2);
            padding: 4px 12px;
            border-radius: 9999px;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            margin-bottom: 24px;
            width: fit-content;
        }
        .claim-big {
            font-family: var(--mono);
            font-size: clamp(56px, 8vw, 84px);
            font-weight: 800;
            line-height: 0.9;
            letter-spacing: -2px;
            margin-bottom: 12px;
        }
        .claim-sub {
            font-size: 15px;
            color: #ffedd5;
            line-height: 1.5;
            margin-bottom: 30px;
        }
        .claim-footer {
            font-size: 12px;
            color: rgba(255, 255, 255, 0.8);
            border-top: 1px solid rgba(255, 255, 255, 0.2);
            padding-top: 16px;
        }

        .studies-list {
            display: flex;
            flex-direction: column;
            gap: 14px;
        }
        .study-card {
            background: #ffffff;
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 20px 24px;
            display: grid;
            grid-template-columns: 140px 1fr;
            gap: 20px;
            align-items: center;
            box-shadow: var(--shadow-sm);
            transition: transform 0.15s ease, border-color 0.15s ease;
        }
        .study-card:hover {
            transform: translateY(-2px);
            border-color: var(--primary-light);
            box-shadow: var(--shadow-md);
        }
        .study-metric {
            font-family: var(--mono);
            font-size: 28px;
            font-weight: 800;
            color: var(--primary);
            line-height: 1;
        }
        .study-metric.orange { color: var(--accent-orange); }
        .study-metric.rose { color: var(--accent-rose); }
        .study-title {
            font-size: 15px;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 4px;
        }
        .study-desc {
            font-size: 13px;
            color: var(--text-muted);
            line-height: 1.5;
        }

        /* ─── 03 Engines Comparison ─── */
        .engines-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
            margin-bottom: 24px;
        }
        .engine-card {
            background: #ffffff;
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 32px;
            box-shadow: var(--shadow-sm);
            position: relative;
        }
        .engine-card.ar {
            border-top: 5px solid var(--primary);
        }
        .engine-card.zero {
            border-top: 5px solid var(--accent-orange);
        }
        .engine-tag {
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
        }
        .engine-title {
            font-size: 20px;
            font-weight: 800;
            color: var(--dark);
            margin-bottom: 12px;
            letter-spacing: -0.4px;
        }
        .engine-flow {
            list-style: none;
            margin-top: 20px;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        .engine-flow li {
            display: grid;
            grid-template-columns: 28px 1fr;
            gap: 12px;
            font-size: 13px;
            color: #334155;
            line-height: 1.5;
            padding-bottom: 10px;
            border-bottom: 1px solid #f1f5f9;
        }
        .engine-flow li:last-child { border-bottom: none; }
        .flow-step {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background: #f1f5f9;
            color: var(--text-muted);
            font-family: var(--mono);
            font-size: 11px;
            font-weight: 700;
            display: grid;
            place-items: center;
        }
        .engine-card.ar .flow-step {
            background: rgba(67, 56, 202, 0.1);
            color: var(--primary);
        }
        .engine-card.zero .flow-step {
            background: rgba(234, 88, 12, 0.1);
            color: var(--accent-orange);
        }

        .citation-box {
            background: #ecfdf5;
            border: 1px solid #a7f3d0;
            border-radius: 20px;
            padding: 28px 32px;
            display: grid;
            grid-template-columns: 56px 1fr;
            gap: 24px;
            align-items: center;
        }
        .seal-check {
            width: 56px;
            height: 56px;
            border-radius: 50%;
            background: #10b981;
            color: #ffffff;
            display: grid;
            place-items: center;
            font-size: 28px;
            box-shadow: 0 8px 20px rgba(16, 185, 129, 0.3);
        }

        /* ─── 04 Limits Grid ─── */
        .limits-box {
            background: var(--dark);
            border-radius: 24px;
            padding: clamp(36px, 5vw, 60px);
            color: #ffffff;
        }
        .limits-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 32px;
        }
        .limit-item {
            background: #1e293b;
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 16px;
            padding: 24px;
        }
        .limit-tag {
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            margin-bottom: 12px;
            display: inline-block;
        }
        .limit-tag.zero { color: #fdba74; }
        .limit-tag.ar { color: #93c5fd; }
        .limit-tag.both { color: #86efac; }
        .limit-title {
            font-size: 16px;
            font-weight: 700;
            color: #ffffff;
            margin-bottom: 8px;
        }
        .limit-desc {
            font-size: 13px;
            color: #94a3b8;
            line-height: 1.55;
            margin: 0;
        }

        /* ─── 05 Role Selector ─── */
        .decision-grid {
            display: grid;
            grid-template-columns: minmax(0, 0.65fr) minmax(0, 1.35fr);
            gap: 32px;
            align-items: stretch;
        }
        .role-nav {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .role-btn {
            background: #ffffff;
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 16px 20px;
            text-align: left;
            font-family: var(--sans);
            font-size: 14px;
            font-weight: 700;
            color: var(--dark);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: space-between;
            transition: all 0.15s ease;
        }
        .role-btn:hover {
            border-color: var(--primary-light);
            background: #f8fafc;
        }
        .role-btn[aria-selected="true"] {
            background: var(--primary);
            color: #ffffff;
            border-color: var(--primary);
            box-shadow: 0 4px 14px rgba(67, 56, 202, 0.3);
        }
        .role-advice-card {
            background: #ffffff;
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: clamp(28px, 4vw, 44px);
            box-shadow: var(--shadow-sm);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .advice-kicker {
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--primary);
            margin-bottom: 10px;
        }
        .advice-title {
            font-size: clamp(20px, 2.5vw, 28px);
            font-weight: 800;
            color: var(--dark);
            line-height: 1.25;
            margin-bottom: 14px;
            letter-spacing: -0.5px;
        }
        .advice-body {
            font-size: 15px;
            color: #334155;
            line-height: 1.6;
            margin-bottom: 24px;
        }
        .advice-action {
            background: #f8fafc;
            border-left: 4px solid var(--primary);
            border-radius: 8px;
            padding: 14px 18px;
            font-size: 13px;
            font-weight: 600;
            color: #1e293b;
        }

        /* ─── Call to Action Banner ─── */
        .cta-banner {
            background: linear-gradient(135deg, #090d16 0%, #1e1b4b 60%, #0f172a 100%);
            border-radius: 28px;
            padding: clamp(36px, 5vw, 60px);
            color: #ffffff;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.4);
            margin-top: 30px;
        }
        .cta-banner h2 {
            font-size: clamp(24px, 3.4vw, 40px);
            font-weight: 800;
            letter-spacing: -0.8px;
            margin-bottom: 14px;
        }
        .cta-banner p {
            font-size: 16px;
            color: #cbd5e1;
            max-width: 680px;
            line-height: 1.6;
            margin-bottom: 28px;
        }
        .cta-btn-group {
            display: flex;
            align-items: center;
            gap: 14px;
            flex-wrap: wrap;
            justify-content: center;
        }
        .btn-cta-main {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 14px 28px;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 700;
            color: #0f172a;
            background: linear-gradient(135deg, #f59e0b, #d97706);
            box-shadow: 0 8px 20px rgba(245, 158, 11, 0.35);
            transition: all 0.15s ease;
        }
        .btn-cta-main:hover {
            transform: translateY(-2px);
            filter: brightness(1.05);
        }
        .btn-cta-outline {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 14px 24px;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 700;
            color: #ffffff;
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: all 0.15s ease;
        }
        .btn-cta-outline:hover {
            background: rgba(255, 255, 255, 0.15);
        }

        /* ─── Sources List ─── */
        .source-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            margin-top: 24px;
        }
        .source-item {
            background: #ffffff;
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 16px 20px;
            font-size: 13px;
            color: var(--text-muted);
            line-height: 1.5;
        }
        .source-item strong { color: var(--dark); display: block; margin-bottom: 2px; }
        .source-item a { color: var(--primary); text-decoration: underline; text-underline-offset: 3px; }

        /* ─── Standard AR Tilik Footer ─── */
        .footer {
            background: var(--dark);
            color: #94a3b8;
            padding: 60px 0 30px;
            font-size: 13px;
            border-top: 1px solid rgba(255, 255, 255, 0.08);
        }
        .footer-grid {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1.2fr;
            gap: 40px;
            margin-bottom: 40px;
        }
        .footer-links {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 9px;
        }
        .footer-links a {
            color: #94a3b8;
            text-decoration: none;
            transition: color 0.15s ease;
        }
        .footer-links a:hover { color: #ffffff; }
        .footer-bottom {
            border-top: 1px solid #1e293b;
            padding-top: 24px;
            text-align: center;
            font-size: 12px;
            color: #64748b;
        }

        /* ─── Responsive Media Queries ─── */
        @media (max-width: 900px) {
            .mast-grid, .studies-grid, .engines-grid, .decision-grid, .footer-grid { grid-template-columns: 1fr; }
            .source-grid { grid-template-columns: 1fr; }
            .nav-links { display: none; }
        }

        /* TopNavBar Mobile Standardization & Fluid Layout */
        @media (max-width: 768px) {
            .container { padding: 0 16px; }
            .navbar { padding: 9px 0; }
            .nav-inner { gap: 10px; }
            .brand { gap: 8px; flex-shrink: 0; }
            .brand-logo { width: 32px; height: 32px; }
            .brand-title { font-size: 18px; }
            .brand-badge { display: none !important; }
            .nav-actions { gap: 8px; flex-shrink: 0; }
            .btn-nav-back {
                padding: 6px 11px;
                font-size: 12px;
                white-space: nowrap;
            }
            .btn-nav-primary {
                padding: 6px 13px;
                font-size: 12px;
                white-space: nowrap;
            }
            .btn-nav-prefix { display: none; }
        }

        @media (max-width: 600px) {
            .study-card { grid-template-columns: 1fr; gap: 8px; }
            .citation-box { grid-template-columns: 1fr; text-align: center; }
            .seal-check { margin: 0 auto; }
            .comparison-card { overflow-x: auto; }
            .comparison-table { min-width: 580px; }
        }

        @media (max-width: 480px) {
            .container { padding: 0 14px; }
            .navbar { padding: 8px 0; }
            .nav-inner { gap: 8px; }
            .brand { gap: 7px; }
            .brand-logo { width: 30px; height: 30px; }
            .brand-title { font-size: 16.5px; }
            .nav-actions { gap: 6px; }
            .btn-nav-back {
                padding: 5px 9px;
                font-size: 11.5px;
                gap: 4px;
            }
            .btn-nav-primary {
                padding: 5px 11px;
                font-size: 11.5px;
                gap: 4px;
            }
        }

        @media (max-width: 360px) {
            .container { padding: 0 10px; }
            .brand-title { font-size: 15px; }
            .btn-nav-back .btn-nav-text { display: none; }
            .btn-nav-back { padding: 5px 8px; }
            .btn-nav-primary { padding: 5px 9px; font-size: 11px; }
        }
    </style>
</head>
<body>

    <!-- ─── Fixed Header Navbar ─── -->
    <header class="navbar">
        <div class="container nav-inner">
            <a href="<?= base_url() ?>" class="brand">
                <img src="<?= base_url('images/logo.png') ?>" alt="AR Tilik Logo" class="brand-logo" width="38" height="38">
                <div>
                    <span class="brand-title">AR Tilik</span>
                </div>
                <span class="brand-badge">Whitepaper Riset</span>
            </a>

            <nav>
                <ul class="nav-links">
                    <li><a href="<?= base_url() ?>#teori">Dual-Lens</a></li>
                    <li><a href="<?= base_url() ?>#fitur-unggulan">Inovasi Mesin</a></li>
                    <li><a href="<?= base_url() ?>#integrasi-ojs">Integrasi OJS</a></li>
                    <li><a href="<?= base_url('riset/ar-tilik-vs-turnitin') ?>">AR Tilik vs Turnitin</a></li>
                    <li><a href="<?= base_url('benchmark') ?>">Benchmark Indonesia</a></li>
                    <li><a href="<?= base_url('docs/Brosur_Eksekutif_ARTilik.pdf') ?>" target="_blank" download="Brosur_Eksekutif_ARTilik.pdf">Brosur Kampus</a></li>
                </ul>
            </nav>

            <div class="nav-actions">
                <a href="<?= base_url() ?>" class="btn-nav-back" title="Kembali ke Beranda">
                    <span class="btn-nav-arrow">&larr;</span>
                    <span class="btn-nav-text">Beranda</span>
                </a>
                <a href="<?= base_url('dashboard') ?>" class="btn-nav-primary" title="Buka Dashboard AR Tilik">
                    <span class="btn-nav-prefix">Buka </span><span>Dashboard</span>
                    <span class="btn-nav-arrow">&rarr;</span>
                </a>
            </div>
        </div>
    </header>

    <!-- ─── Hero Masthead ─── -->
    <section class="hero-wrap">
        <div class="container">
            <div class="breadcrumbs">
                <a href="<?= base_url() ?>">Beranda</a>
                <span>&rsaquo;</span>
                <span>Riset &amp; Integritas</span>
                <span>&rsaquo;</span>
                <span style="color: var(--dark);">AR Tilik vs ZeroGPT</span>
            </div>

            <div class="mast-grid">
                <div>
                    <div class="pill-tag">
                        <span class="dot"></span>
                        <span>Analisis Berbasis Riset Independen &bull; Edisi 2026</span>
                    </div>
                    <h1 class="hero-title">
                        Bukan lomba siapa paling pintar <em>menebak AI.</em>
                    </h1>
                    <p class="hero-lead">
                        AR Tilik dan ZeroGPT menjawab dua pertanyaan yang berbeda secara filosofis. ZeroGPT menaksir <strong>“apakah teks ini dibuat AI?”</strong>; sementara AR Tilik memeriksa <strong>“apa bukti empiris yang dapat diverifikasi dari naskah ini?”</strong>
                    </p>
                    <div class="hero-meta">
                        <span>🗓️ Diperbarui: <?= esc($metrics['last_sync_date'] ?? date('d F Y')) ?></span>
                        <span>&bull;</span>
                        <span style="display: inline-flex; align-items: center; gap: 5px; color: #059669; font-weight: 700;">
                            <span style="width: 8px; height: 8px; border-radius: 50%; background: #059669; display: inline-block;"></span>
                            Engine v<?= esc($metrics['engine_version'] ?? '3.4.2') ?> Terverifikasi
                        </span>
                        <span>&bull;</span>
                        <span>📚 <?= number_format($metrics['total_corpus'] ?? 854) ?> Dokumen Korpus</span>
                        <span>&bull;</span>
                        <span>📑 <?= number_format($metrics['total_reports'] ?? 1167) ?> Laporan Audit</span>
                    </div>
                </div>

                <aside class="verdict-card" aria-label="Putusan Ringkas">
                    <span class="verdict-badge">Putusan Ringkas</span>
                    <h3>Audit Bukti vs Dugaan Probabilitas</h3>
                    <p>
                        Untuk skrining naskah jurnal dan karya ilmiah di lingkungan kampus Indonesia, kami lebih mempercayai audit bukti objektif AR Tilik daripada skor probabilitas gaya bahasa ZeroGPT. Namun AR Tilik bukan pengganti detektor AI—melainkan instrumen pembuktian ilmiah berbasis keadilan akademik.
                    </p>
                </aside>
            </div>
        </div>
    </section>

    <!-- ─── Sticky Sub-Navigation ─── -->
    <nav class="subnav" aria-label="Navigasi Analisis">
        <div class="container subnav-inner">
            <a href="#beda" class="subnav-link active">01 — Peta Masalah</a>
            <a href="#bukti" class="subnav-link">02 — Klaim vs Temuan</a>
            <a href="#mesin" class="subnav-link">03 — Cara Kerja Mesin</a>
            <a href="#batas" class="subnav-link">04 — Batas Kepercayaan</a>
            <a href="#pilih" class="subnav-link">05 — Rekomendasi Peran</a>
            <a href="#sumber" class="subnav-link">06 — Sumber Pustaka</a>
        </div>
    </nav>

    <!-- ─── 01 Peta Masalah ─── -->
    <section id="beda" class="section-white">
        <div class="container">
            <div class="section-header">
                <div class="section-number">01 — Peta Masalah</div>
                <h2 class="section-title">Perbandingan yang adil dimulai dari objek yang diukur.</h2>
                <p class="section-desc">
                    Menyebut keduanya sekadar “detektor AI” menyamarkan perbedaan arsitektural terpenting: satu mengklasifikasi gaya penulisan melalui probabilitas tertutup, sementara satu lagi mengaudit bukti kemiripan dan integritas rujukan secara transparan.
                </p>
            </div>

            <div class="comparison-card">
                <table class="comparison-table">
                    <thead>
                        <tr>
                            <th class="td-label">Parameter Telaah</th>
                            <th class="th-ar">AR Tilik (Dual-Lens Engine)</th>
                            <th class="th-zero">ZeroGPT (DeepAnalyse™)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="td-label">Pertanyaan Utama</td>
                            <td class="td-ar">Ada <strong>bukti kemiripan fisik, parafrasa semantik, atau rujukan bermasalah</strong>?</td>
                            <td class="td-zero">Seberapa mungkin teks ini <strong>dihasilkan model AI</strong>?</td>
                        </tr>
                        <tr>
                            <td class="td-label">Bentuk Keluaran</td>
                            <td class="td-ar">Dekomposisi Catur Pilar, skor exact/kosinus, pola temuan, dan audit sitasi phantom.</td>
                            <td class="td-zero">Skor probabilitas tunggal (persentase AI) dan penyorotan kalimat.</td>
                        </tr>
                        <tr>
                            <td class="td-label">Sifat Bukti</td>
                            <td class="td-ar"><strong>Dapat ditelusuri &amp; dibantah:</strong> Cuplikan pasase berpasangan dan verifikasi Crossref/OpenAlex.</td>
                            <td class="td-zero"><strong>Kotak hitam (Black-Box):</strong> Inferensi statistik proprietari; bobot model tidak dibuka.</td>
                        </tr>
                        <tr>
                            <td class="td-label">Bahasa Indonesia</td>
                            <td class="td-ar">Dioptimalkan untuk teks akademik Indonesia dengan dukungan korpus ilmiah lokal terverifikasi.</td>
                            <td class="td-zero">Mengklaim semua bahasa, namun studi independen lokal menemukan akurasi 37,5%.</td>
                        </tr>
                        <tr>
                            <td class="td-label">Sitasi Fabrikasi AI</td>
                            <td class="td-ar"><strong>Ada auditor rujukan aktif:</strong> Memeriksa eksistensi DOI dan pustaka ke pangkalan data global.</td>
                            <td class="td-zero"><strong>Tidak ada:</strong> Tidak memverifikasi apakah daftar pustaka benar-benar ada di dunia nyata.</td>
                        </tr>
                        <tr>
                            <td class="td-label">Posisi Terbaik</td>
                            <td class="td-ar"><strong>Audit integritas naskah &amp; instrumen sidang lisan.</strong></td>
                            <td class="td-zero"><strong>Sinyal awal</strong> yang wajib ditelaah ulang oleh manusia.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="alert-callout">
                <span style="font-size: 22px;">⚖️</span>
                <div>
                    <strong>Penting: Tidak Ada Uji Head-to-Head yang Identik.</strong>
                    Data yang tersedia tidak membolehkan kesimpulan serampangan seperti “AR Tilik X% lebih akurat”. Yang dinilai adalah ketepatan sasaran: bukti empiris yang dapat diverifikasi jauh lebih cocok untuk keputusan akademik berisiko tinggi (skripsi, tesis, akreditasi jurnal) daripada dugaan kepengarangan statistik.
                </div>
            </div>
        </div>
    </section>

    <!-- ─── 02 Klaim vs Temuan ─── -->
    <section id="bukti" class="section-slate">
        <div class="container">
            <div class="section-header">
                <div class="section-number">02 — Klaim vs Temuan Lapangan</div>
                <h2 class="section-title">Angka klaim pecah ketika diuji di dunia nyata.</h2>
                <p class="section-desc">
                    Klaim internal produk komersial bukan otomatis salah, namun belum direplikasi secara independen pada korpus penulisan formal dan penulis non-native berbahasa Indonesia.
                </p>
            </div>

            <div class="studies-grid">
                <article class="vendor-claim-card">
                    <div>
                        <span class="claim-badge">Klaim Resmi Vendor</span>
                        <div class="claim-big">98,4%</div>
                        <p class="claim-sub">
                            Akurasi yang dilaporkan ZeroGPT, dengan klaim tingkat false-positive <strong>&lt; 1%</strong>.
                        </p>
                    </div>
                    <div class="claim-footer">
                        Sumber: <a href="https://www.zerogpt.com/deep-analyse" target="_blank" rel="noopener noreferrer" style="color: #ffffff; text-decoration: underline;">Dokumentasi DeepAnalyse™</a>. Dataset evaluasi tidak dipublikasikan ke publik.
                    </div>
                </article>

                <div class="studies-list">
                    <div class="study-card">
                        <div class="study-metric">59–74%</div>
                        <div>
                            <div class="study-title">Weber-Wulff et al. (2023) — IJEE</div>
                            <p class="study-desc">
                                Menguji 14 detektor AI pada 756 sampel. ZeroGPT mencatat akurasi 59% (binary) dan 74% (binary-inclusive). Kesimpulan: detektor AI belum memadai untuk penetapan sanksi akademik.
                            </p>
                        </div>
                    </div>

                    <div class="study-card">
                        <div class="study-metric orange">46,1%</div>
                        <div>
                            <div class="study-title">Perkins et al. (2024)</div>
                            <p class="study-desc">
                                Melaporkan akurasi baseline ZeroGPT hanya 46,1% pada teks akademik; dan turun menjadi 31,3% pada keluaran teks yang tidak dimanipulasi.
                            </p>
                        </div>
                    </div>

                    <div class="study-card">
                        <div class="study-metric rose">61,22%</div>
                        <div>
                            <div class="study-title">Liang et al. (2023) — Stanford University</div>
                            <p class="study-desc">
                                Membuktikan detektor AI memiliki bias berat terhadap penulis non-native (ESL/EFL). Esai TOEFL karya manusia ditandai sebagai AI dengan false-positive rata-rata 61,22%.
                            </p>
                        </div>
                    </div>

                    <div class="study-card">
                        <div class="study-metric rose">37,5%</div>
                        <div>
                            <div class="study-title">Falaq (2026) — Jurnal Cahaya Edukasi</div>
                            <p class="study-desc">
                                Menguji 80 teks akademik Bahasa Indonesia. ZeroGPT hanya benar pada 30 dari 80 teks; <strong>32 dari 40 karya asli manusia ditandai AI (False Positive Rate 80%)</strong>.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ─── 03 Cara Kerja Mesin ─── -->
    <section id="mesin" class="section-white">
        <div class="container">
            <div class="section-header">
                <div class="section-number">03 — Di Balik Layar</div>
                <h2 class="section-title">Satu membaca pola probabilitas. Satu mengumpulkan jejak bukti.</h2>
                <p class="section-desc">
                    Perbedaan arsitektur menjelaskan mengapa kedua sistem menghasilkan kesimpulan yang sangat berlainan.
                </p>
            </div>

            <div class="engines-grid">
                <article class="engine-card ar">
                    <span class="engine-tag" style="color: var(--primary);">Arsitektur AR Tilik</span>
                    <h3 class="engine-title">Mesin Dual-Lens + Audit Forensik Cerdas</h3>
                    <p style="font-size: 13.5px; color: var(--text-muted);">
                        Mendekomposisi naskah ke dalam pilar matematis terbuka yang setiap pasasenya dapat ditinjau ulang:
                    </p>
                    <ol class="engine-flow">
                        <li>
                            <span class="flow-step">1</span>
                            <span><strong>5-Gram Sequential Shingling:</strong> Mengukur pasase kalimat yang identik kata-demi-kata secara deterministik.</span>
                        </li>
                        <li>
                            <span class="flow-step">2</span>
                            <span><strong>Sparse TF-IDF Cosine:</strong> Menganalisis kedekatan orientasi topik leksikal dan istilah bidang ilmu.</span>
                        </li>
                        <li>
                            <span class="flow-step">3</span>
                            <span><strong>Sintesis XGBoost v3.4:</strong> Menimbang 11 determinan risiko secara non-linear tanpa vonis kaku.</span>
                        </li>
                        <li>
                            <span class="flow-step">4</span>
                            <span><strong>Audit Sitasi Phantom:</strong> Memvalidasi eksistensi rujukan secara eksternal ke OpenAlex &amp; Crossref.</span>
                        </li>
                    </ol>
                </article>

                <article class="engine-card zero">
                    <span class="engine-tag" style="color: var(--accent-orange);">Arsitektur ZeroGPT</span>
                    <h3 class="engine-title">DeepAnalyse™ Proprietari Tertutup</h3>
                    <p style="font-size: 13.5px; color: var(--text-muted);">
                        Situs resmi menjelaskan pipeline bertingkat tanpa membuka bobot atau dataset evaluasi:
                    </p>
                    <ol class="engine-flow">
                        <li>
                            <span class="flow-step">1</span>
                            <span><strong>Macro Analysis:</strong> Membaca karakteristik statistik teks secara menyeluruh (perplexity/gaya penulisan).</span>
                        </li>
                        <li>
                            <span class="flow-step">2</span>
                            <span><strong>Sentence Analysis:</strong> Menandai kalimat yang dianggap memiliki distribusi probabilitas khas model bahasa.</span>
                        </li>
                        <li>
                            <span class="flow-step">3</span>
                            <span><strong>Micro Analysis:</strong> Menghitung sinyal residual untuk membedakan teks murni atau percampuran.</span>
                        </li>
                        <li>
                            <span class="flow-step">4</span>
                            <span><strong>Agregasi Skor:</strong> Mengeluarkan persentase probabilitas tunggal tanpa bukti pasase eksternal.</span>
                        </li>
                    </ol>
                </article>
            </div>

            <!-- Kasus Pembuktian Sitasi Fiktif -->
            <div class="citation-box">
                <div class="seal-check">✓</div>
                <div>
                    <h3 style="font-size: 18px; font-weight: 800; color: #065f46; margin-bottom: 6px;">Studi Kasus Pembuktian Sitasi: Mengapa Detektor AI Gagal?</h3>
                    <p style="font-size: 14px; color: #047857; line-height: 1.6; margin-bottom: 6px;">
                        Dalam audit naskah uji, sitasi fabrikasi <em>“Pratama &amp; Kusuma (2022)”</em> yang dirakit oleh AI generatif dapat ditulis dengan tata bahasa yang sangat rapi sehingga lolos deteksi ZeroGPT. Namun modul <strong>Auditor Sitasi AR Tilik</strong> menandainya sebagai sitasi gaib (phantom citation) karena tidak ditemukan di pangkalan data riset global; sedangkan karya riil seperti <em>Gilster (1997)</em> diverifikasi keabsahannya dengan 823 sitasi global.
                    </p>
                    <small style="font-size: 12px; color: #059669; font-weight: 600;">Kesimpulan: Detektor AI hanya menilai gaya kalimat; integritas ilmiah mensyaratkan pembuktian eksistensi sumber.</small>
                </div>
            </div>
        </div>
    </section>

    <!-- ─── 04 Batas Kepercayaan ─── -->
    <section id="batas" class="section-slate">
        <div class="container">
            <div class="limits-box">
                <div class="section-number" style="color: #93c5fd;">04 — Batas Kepercayaan</div>
                <h2 class="section-title" style="color: #ffffff;">Jangan berikan kuasa lebih besar daripada buktinya.</h2>
                <p class="section-desc" style="color: #94a3b8;">
                    Setiap instrumen komputasi memiliki batasan yang nyata. Perbedaannya terletak pada seberapa fatal konsekuensi kesalahannya bagi mahasiswa dan peneliti.
                </p>

                <div class="limits-grid">
                    <article class="limit-item">
                        <span class="limit-tag zero">ZEROGPT</span>
                        <h4 class="limit-title">Tulisan Formal Tampak “Terlalu Rapi”</h4>
                        <p class="limit-desc">
                            Studi independen membuktikan prosa akademik yang terstruktur dan minim kesalahan ketik kerap disangka buatan AI (FPR 80% pada esai Indonesia).
                        </p>
                    </article>

                    <article class="limit-item">
                        <span class="limit-tag zero">ZEROGPT</span>
                        <h4 class="limit-title">Parafrasa Melemahkan Sinyal</h4>
                        <p class="limit-desc">
                            Dokumentasi ZeroGPT sendiri mengakui parafrasa dapat menurunkan akurasi deteksi secara drastis, sehingga manipulasi teks mudah lolos.
                        </p>
                    </article>

                    <article class="limit-item">
                        <span class="limit-tag ar">AR TILIK</span>
                        <h4 class="limit-title">Audit Berfokus pada Bukti Fisik</h4>
                        <p class="limit-desc">
                            AR Tilik tidak memvonis apakah seorang mahasiswa menggunakan AI atau tidak. Sistem menyajikan pasase beririsan nyata dan daftar ketidaksinkronan sitasi.
                        </p>
                    </article>

                    <article class="limit-item">
                        <span class="limit-tag ar">AR TILIK</span>
                        <h4 class="limit-title">Bukan Alat Vonis Tunggal</h4>
                        <p class="limit-desc">
                            Laporan AR Tilik dirancang sebagai panduan telaah sidang lisan bagi dosen, bukan vonis mutlak otomatis tanpa hak pembelaan mahasiswa.
                        </p>
                    </article>

                    <article class="limit-item">
                        <span class="limit-tag both">KEDUANYA</span>
                        <h4 class="limit-title">Hasil Analisis Bukan Putusan Hukum</h4>
                        <p class="limit-desc">
                            Kemiripan leksikal bukan berarti plagiarisme otomatis; skor AI bukan bukti pelanggaran etik. Keputusan akhir mutlak membutuhkan pertimbangan manusia.
                        </p>
                    </article>

                    <article class="limit-item">
                        <span class="limit-tag both">PRINSIP AKADEMIK</span>
                        <h4 class="limit-title">Klarifikasi Lisan Adalah Kunci</h4>
                        <p class="limit-desc">
                            Mahasiswa yang menulis sendiri naskahnya akan mampu menjelaskan argumen dan metodologinya saat diuji di ruang sidang, terlepas dari skor probabilitas apa pun.
                        </p>
                    </article>
                </div>
            </div>
        </div>
    </section>

    <!-- ─── 05 Rekomendasi Berdasarkan Peran ─── -->
    <section id="pilih" class="section-white">
        <div class="container">
            <div class="section-header">
                <div class="section-number">05 — Rekomendasi Peran &amp; Keputusan</div>
                <h2 class="section-title">Pilih alat berdasarkan tingkat risiko keputusan Anda.</h2>
                <p class="section-desc">
                    Gunakan lensa peran untuk melihat panduan tindakan yang aman secara metodologis dan etis:
                </p>
            </div>

            <div class="decision-grid">
                <div class="role-nav" role="tablist" aria-label="Pilih Peran">
                    <button type="button" class="role-btn" role="tab" aria-selected="true" data-role="editor">
                        <span>📰 Editor Jurnal Ilmiah</span>
                        <span>&rsaquo;</span>
                    </button>
                    <button type="button" class="role-btn" role="tab" aria-selected="false" data-role="dosen">
                        <span>🎓 Dosen Pembimbing &amp; Penguji</span>
                        <span>&rsaquo;</span>
                    </button>
                    <button type="button" class="role-btn" role="tab" aria-selected="false" data-role="penulis">
                        <span>✍️ Mahasiswa &amp; Penulis Naskah</span>
                        <span>&rsaquo;</span>
                    </button>
                    <button type="button" class="role-btn" role="tab" aria-selected="false" data-role="institusi">
                        <span>🏛️ LPPM &amp; Pimpinan Universitas</span>
                        <span>&rsaquo;</span>
                    </button>
                </div>

                <article class="role-advice-card" id="advicePanel" role="tabpanel">
                    <div>
                        <div class="advice-kicker" id="adviceKicker">UNTUK EDITOR JURNAL ILMIAH</div>
                        <h3 class="advice-title" id="adviceTitle">Utamakan bukti konkret yang dapat ditindaklanjuti.</h3>
                        <p class="advice-body" id="adviceBody">
                            AR Tilik selaras dengan standar COPE dan alur editorial OJS: temuan kemiripan fisik, pola parafrasa, dan verifikasi eksistensi rujukan dapat ditunjukkan kepada penulis saat meminta revisi. Jika detektor AI seperti ZeroGPT dipakai, batasi hanya sebagai sinyal pemicu baca ulang—jangan pernah menolak naskah semata-mata karena skor persentase AI.
                        </p>
                    </div>
                    <div class="advice-action" id="adviceAction">
                        Alur Aman: Skrining Bukti &rarr; Telaah Manual Editor &rarr; Permintaan Klarifikasi Sitasi &rarr; Keputusan Substantif.
                    </div>
                </article>
            </div>

            <!-- Call to Action Banner -->
            <div class="cta-banner">
                <div style="font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; color: #fdba74; margin-bottom: 10px;">
                    UJI INTEGRITAS ILMIAH SEKARANG
                </div>
                <h2>Buktikan Sendiri pada Naskah Karya Ilmiah Anda</h2>
                <p>
                    Rasakan pengalaman telaah naskah berbasis bukti tekstual transparan, audit rujukan global Crossref/OpenAlex, dan sertifikasi digital berstempel kriptografis SHA-256.
                </p>
                <div class="cta-btn-group">
                    <a href="<?= base_url('dashboard') ?>" class="btn-cta-main">
                        <span>🚀 Mulai Pemeriksaan di Dashboard</span>
                        <span>&rarr;</span>
                    </a>
                    <a href="<?= base_url('docs/ojs/pdf') ?>" class="btn-cta-outline" target="_blank">
                        <span>📖 Unduh Panduan OJS 3.x</span>
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- ─── 06 Sumber & Status Bukti ─── -->
    <section id="sumber" class="section-slate">
        <div class="container">
            <div class="section-header" style="margin-bottom: 24px;">
                <div class="section-number">06 — Referensi &amp; Landasan Bukti</div>
                <h2 class="section-title" style="font-size: 26px;">Daftar Pustaka &amp; Rujukan Independen</h2>
            </div>

            <div class="source-grid">
                <div class="source-item">
                    <strong>ZeroGPT DeepAnalyse™ Methodology (2026)</strong>
                    Klaim resmi vendor: akurasi 98,4%, false positive &lt;1%, dan cakupan 35+ keluarga model bahasa. Diverifikasi 2026 via <a href="https://www.zerogpt.com/deep-analyse" target="_blank" rel="noopener noreferrer">zerogpt.com</a>.
                </div>
                <div class="source-item">
                    <strong>Falaq, Y. (2026) — Jurnal Cahaya Edukasi</strong>
                    Studi empiris akurasi detektor AI pada 80 teks akademik Bahasa Indonesia (Akurasi 37,5%; False Positive Rate 80%; False Negative Rate 45%). <a href="https://jurnalcahayaedukasi.com/index.php/jce/article/download/316/219/1288" target="_blank" rel="noopener noreferrer">Akses Unduhan Naskah</a>.
                </div>
                <div class="source-item">
                    <strong>Weber-Wulff, D. et al. (2023) — IJEE</strong>
                    <em>Testing of detection tools for AI-generated text.</em> International Journal for Educational Integrity, DOI: 10.1007/s40979-023-00146-z (Pengujian 14 alat terhadap 756 sampel penulisan).
                </div>
                <div class="source-item">
                    <strong>Liang, W. et al. (2023) — Stanford University</strong>
                    <em>GPT detectors are biased against non-native English writers.</em> arXiv:2304.02819. Menemukan tingkat false positive 61,22% pada esai penulis non-native.
                </div>
                <div class="source-item">
                    <strong>Perkins, M. et al. (2024)</strong>
                    Evaluasi komparatif akurasi detektor generatif terhadap teks akademik terapan; akurasi baseline ZeroGPT tercatat 46,1%.
                </div>
                <div class="source-item">
                    <strong>Audit Produk AR Tilik (Engine v<?= esc($metrics['engine_version'] ?? '3.4.2') ?>)</strong>
                    Pengujian empiris sistem Dual-Lens, audit sitasi fiktif (Crossref &amp; OpenAlex), ekstraksi 5-gram sekuensial, dan sintesis risiko akademik terkalibrasi XGBoost. Terverifikasi pada <?= number_format($metrics['total_reports'] ?? 1167) ?> laporan audit selesai per <?= esc($metrics['last_sync_date'] ?? date('d F Y')) ?>.
                </div>
            </div>

            <p style="font-size: 12px; color: var(--text-muted); margin-top: 24px; line-height: 1.6;">
                * Catatan Metodologis: Klaim komersial dan temuan riset akademik independen dipisahkan secara tegas. Tidak ada nilai yang digabung menjadi satu skor pemenang semu karena variasi dataset, ragam bahasa, dan metodologi pengujian yang berbeda.
            </p>
        </div>

        <!-- Crosslink Banner to Turnitin Whitepaper -->
        <div class="container" style="margin-top: 36px;">
            <div style="background: linear-gradient(135deg, #0f766e 0%, #0369a1 100%); border-radius: 20px; padding: clamp(24px, 3.5vw, 36px); color: #ffffff; display: flex; align-items: center; justify-content: space-between; gap: 24px; box-shadow: 0 12px 30px rgba(15, 118, 110, 0.25); flex-wrap: wrap;">
                <div style="max-width: 720px;">
                    <h4 style="font-size: 19px; font-weight: 800; margin-bottom: 6px; color: #ffffff;">Baca Juga Whitepaper: AR Tilik vs Turnitin</h4>
                    <p style="font-size: 14px; color: rgba(255, 255, 255, 0.9); line-height: 1.5; margin: 0;">
                        Analisis objektif untuk pengelola jurnal dan kampus Indonesia: mengapa Turnitin unggul pada skala korpus global, sementara AR Tilik berfokus pada verifikasi sitasi nyata, kedaulatan data lokal, dan integrasi OJS.
                    </p>
                </div>
                <a href="<?= base_url('riset/ar-tilik-vs-turnitin') ?>" style="display: inline-flex; align-items: center; gap: 8px; padding: 12px 22px; background: #ffffff; color: #0f766e; font-size: 13.5px; font-weight: 800; border-radius: 9999px; white-space: nowrap; text-decoration: none; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);">
                    <span>Baca Whitepaper Turnitin</span>
                    <span>&rarr;</span>
                </a>
            </div>
        </div>
    </section>

    <!-- ─── AR Tilik Standard Footer ─── -->
    <footer class="footer">
        <div class="container">
            <div class="footer-grid">
                <div>
                    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 14px;">
                        <img src="<?= base_url('images/logo.png') ?>" alt="AR Tilik Logo" width="34" height="34" style="width: 34px; height: 34px; object-fit: contain; border-radius: 8px;">
                        <span style="font-size: 20px; font-weight: 800; color: #ffffff; letter-spacing: -0.5px;">AR Tilik</span>
                    </div>
                    <p style="line-height: 1.6; max-width: 380px;">
                        Sistem Pemeriksa Kesepadanan Naskah Akademik Berbasis Bukti Transparan. Dikembangkan dengan komitmen tinggi terhadap keadilan akademik dan kedaulatan naskah perguruan tinggi di Indonesia.
                    </p>
                </div>

                <div>
                    <strong style="color: #ffffff; display: block; margin-bottom: 14px;">Fitur &amp; Inovasi</strong>
                    <ul class="footer-links">
                        <li><a href="<?= base_url() ?>#teori">Dual-Lens Engine (5-Gram &amp; Cosine)</a></li>
                        <li><a href="<?= base_url() ?>#fitur-unggulan">Deteksi Lintas Bahasa (EN⇄ID)</a></li>
                        <li><a href="<?= base_url() ?>#fitur-unggulan">Perisai Anti-Manipulasi Karakter</a></li>
                        <li><a href="<?= base_url() ?>#fitur-unggulan">Auditor Sitasi &amp; Daftar Pustaka</a></li>
                        <li><a href="<?= base_url() ?>#fitur-unggulan">Pemanen Neliti &amp; GARUDA Dikti</a></li>
                    </ul>
                </div>

                <div>
                    <strong style="color: #ffffff; display: block; margin-bottom: 14px;">Layanan &amp; Solusi</strong>
                    <ul class="footer-links">
                        <li><a href="<?= base_url() ?>#integrasi-ojs">Integrasi OJS 3.x Jurnal</a></li>
                        <li><a href="<?= base_url() ?>#solusi">Solusi Sivitas Akademika</a></li>
                        <li><a href="<?= base_url('ambassador') ?>">Program Duta Kampus (Afiliasi)</a></li>
                        <li><a href="<?= base_url('verify') ?>">Portal Verifikasi QR</a></li>
                        <li><a href="<?= base_url() ?>#langganan">Paket Tarif &amp; Top-Up</a></li>
                    </ul>
                </div>

                <div>
                    <strong style="color: #ffffff; display: block; margin-bottom: 14px;">Dokumentasi &amp; Legal</strong>
                    <ul class="footer-links">
                        <li><a href="<?= base_url('riset/ar-tilik-vs-turnitin') ?>" style="color: #38bdf8; font-weight: 600;">📊 Whitepaper: AR Tilik vs Turnitin</a></li>
                        <li><a href="<?= base_url('riset/ar-tilik-vs-zerogpt') ?>" style="color: #fdba74; font-weight: 600;">📊 Whitepaper: AR Tilik vs ZeroGPT</a></li>
                        <li><a href="<?= base_url('docs/Brosur_Eksekutif_ARTilik.pdf') ?>" target="_blank" download="Brosur_Eksekutif_ARTilik.pdf">Brosur Eksekutif Kampus (PDF)</a></li>
                        <li><a href="<?= base_url('integrations/ojs/Panduan_Integrasi_OJS_ARTilik.pdf') ?>" target="_blank" download="Panduan_Integrasi_OJS_ARTilik.pdf">Buku Panduan OJS (PDF)</a></li>
                        <li><a href="<?= base_url('privacy') ?>">Kebijakan Privasi</a></li>
                        <li><a href="<?= base_url('terms') ?>">Ketentuan Layanan (ToS)</a></li>
                    </ul>
                </div>
            </div>

            <div class="footer-bottom">
                AR Tilik &copy; <?= date('Y') ?> &bull; Academic Writing Similarity &amp; Evidence Checker &bull; <a href="https://www.arsetontong.top" target="_blank" rel="noopener noreferrer" style="color: #94a3b8; text-decoration: underline; text-underline-offset: 2px;">Powered by ARSetontong</a>
            </div>
        </div>
    </footer>

    <script>
        (function () {
            var recommendations = {
                editor: {
                    kicker: "UNTUK EDITOR JURNAL ILMIAH",
                    title: "Utamakan bukti konkret yang dapat ditindaklanjuti.",
                    body: "AR Tilik selaras dengan standar COPE dan alur editorial OJS: temuan kemiripan fisik, pola parafrasa, dan verifikasi eksistensi rujukan dapat ditunjukkan kepada penulis saat meminta revisi. Jika detektor AI seperti ZeroGPT dipakai, batasi hanya sebagai sinyal pemicu baca ulang—jangan pernah menolak naskah semata-mata karena skor persentase AI.",
                    action: "Alur Aman: Skrining Bukti \u2192 Telaah Manual Editor \u2192 Permintaan Klarifikasi Sitasi \u2192 Keputusan Substantif."
                },
                dosen: {
                    kicker: "UNTUK DOSEN PEMBIMBING & PENGUJI",
                    title: "Jangan jadikan skor AI sebagai vonis disipliner sepihak.",
                    body: "Tingginya angka false-positive pada tulisan akademik formal dan penulis non-native terlalu berisiko untuk sanksi etik. Gunakan instrumen panduan pertanyaan sidang AR Tilik untuk mengonfirmasi penguasaan materi dan kedalaman nalar mahasiswa secara lisan di hadapan dewan penguji.",
                    action: "Alur Aman: Analisis Bukti \u2192 Uji Pemahaman Lewat Pertanyaan Sidang \u2192 Nilai Penguasaan Nalar, Bukan Probabilitas."
                },
                penulis: {
                    kicker: "UNTUK MAHASISWA & PENULIS NASKAH",
                    title: "Perbaiki aspek yang dapat dibuktikan, bukan mengejar skor hijau.",
                    body: "Jangan mengubah tulisan Anda menjadi kaku atau sengaja menambahkan kesalahan ketik demi mengelabui ZeroGPT. Fokuslah pada substansi: pastikan seluruh rujukan benar-benar ada di dunia nyata, lakukan sitasi yang tepat, dan simpan draf tulisan serta catatan proses riset Anda.",
                    action: "Tips Aman: Simpan riwayat draf, periksa setiap DOI di Crossref, dan kuasai argumen setiap bab tulisan Anda."
                },
                institusi: {
                    kicker: "UNTUK LPPM & PIMPINAN UNIVERSITAS",
                    title: "Bangun regulasi kampus yang tahan terhadap salah klasifikasi.",
                    body: "Sebelum mengadopsi perangkat lunak, uji coba pada naskah lokal berbahasa Indonesia. Pisahkan fungsi skrining format dari prosedur penetapan sanksi etik, dan wajibkan adanya hak klarifikasi mahasiswa sebelum keputusan dijatuhkan.",
                    action: "Syarat Kebijakan: Ambang batas terdokumentasi, review manusia independen, hak banding mahasiswa, dan audit berkala."
                }
            };

            var buttons = document.querySelectorAll("[data-role]");
            var kicker = document.getElementById("adviceKicker");
            var title = document.getElementById("adviceTitle");
            var body = document.getElementById("adviceBody");
            var action = document.getElementById("adviceAction");

            buttons.forEach(function (button) {
                button.addEventListener("click", function () {
                    var role = button.getAttribute("data-role");
                    var item = recommendations[role];
                    buttons.forEach(function (b) { b.setAttribute("aria-selected", String(b === button)); });
                    if (item) {
                        kicker.textContent = item.kicker;
                        title.textContent = item.title;
                        body.textContent = item.body;
                        action.textContent = item.action;
                    }
                });
            });

            /* ─── Sub-Navigation & Scroll Offset Handling ─── */
            var subnav = document.querySelector(".subnav");
            var navbar = document.querySelector(".navbar");
            var subnavInner = document.querySelector(".subnav-inner");
            var subnavLinks = document.querySelectorAll(".subnav-link");
            var sectionIds = ["beda", "bukti", "mesin", "batas", "pilih", "sumber"];
            var sections = sectionIds.map(function (id) { return document.getElementById(id); }).filter(Boolean);

            function syncNavHeights() {
                if (navbar) {
                    var navH = navbar.offsetHeight;
                    document.documentElement.style.setProperty("--nav-height", navH + "px");
                }
                if (subnav) {
                    var subH = subnav.offsetHeight;
                    document.documentElement.style.setProperty("--subnav-height", subH + "px");
                }
            }

            syncNavHeights();
            window.addEventListener("resize", syncNavHeights, { passive: true });

            function getHeaderOffset() {
                var navH = navbar ? navbar.offsetHeight : 58;
                var subH = subnav ? subnav.offsetHeight : 48;
                return navH + subH + 20; // 20px extra breathing room
            }

            function setActiveSubnav(targetId) {
                var cleanId = targetId.replace(/^#/, "");
                subnavLinks.forEach(function (link) {
                    var href = link.getAttribute("href") || "";
                    var linkId = href.replace(/^#/, "");
                    var isActive = (linkId === cleanId);
                    link.classList.toggle("active", isActive);
                    if (isActive && subnavInner) {
                        // Scroll ONLY the subnav pill container horizontally, NEVER the main window!
                        var targetLeft = link.offsetLeft - (subnavInner.clientWidth / 2) + (link.clientWidth / 2);
                        subnavInner.scrollTo({ left: targetLeft, behavior: "smooth" });
                    }
                });
            }

            var isProgrammaticScroll = false;
            var scrollEndTimer = null;

            // Smooth Scroll on Subnav Link Click with Exact Dynamic Offset
            subnavLinks.forEach(function (link) {
                link.addEventListener("click", function (e) {
                    var href = this.getAttribute("href");
                    if (href && href.startsWith("#")) {
                        var target = document.querySelector(href);
                        if (target) {
                            e.preventDefault();
                            var offset = getHeaderOffset();
                            var rect = target.getBoundingClientRect();
                            var currentScroll = window.pageYOffset || document.documentElement.scrollTop;
                            var targetTop = rect.top + currentScroll - offset;

                            isProgrammaticScroll = true;
                            setActiveSubnav(href);

                            window.scrollTo({
                                top: targetTop,
                                behavior: "smooth"
                            });

                            if (window.history && window.history.pushState) {
                                window.history.pushState(null, "", href);
                            }

                            clearTimeout(scrollEndTimer);
                            scrollEndTimer = setTimeout(function () {
                                isProgrammaticScroll = false;
                            }, 700);
                        }
                    }
                });
            });

            // Scroll Spy using smooth RAF listener
            function updateActiveSection() {
                if (isProgrammaticScroll) return;
                var offset = getHeaderOffset();
                var currentScroll = window.pageYOffset || document.documentElement.scrollTop;
                var scrollPos = currentScroll + offset + 40;

                // Check if reached bottom of page -> activate last section
                var scrollBottom = currentScroll + window.innerHeight;
                var docHeight = document.documentElement.scrollHeight;
                if (scrollBottom >= docHeight - 80 && sections.length > 0) {
                    setActiveSubnav(sections[sections.length - 1].id);
                    return;
                }

                for (var i = sections.length - 1; i >= 0; i--) {
                    if (sections[i] && scrollPos >= sections[i].offsetTop) {
                        setActiveSubnav(sections[i].id);
                        return;
                    }
                }
            }

            var isTicking = false;
            window.addEventListener("scroll", function () {
                if (!isTicking) {
                    window.requestAnimationFrame(function () {
                        updateActiveSection();
                        isTicking = false;
                    });
                    isTicking = true;
                }
            }, { passive: true });

            // Hash on page load
            if (window.location.hash) {
                setTimeout(function () {
                    var target = document.querySelector(window.location.hash);
                    if (target) {
                        var offset = getHeaderOffset();
                        var rect = target.getBoundingClientRect();
                        var currentScroll = window.pageYOffset || document.documentElement.scrollTop;
                        var targetTop = rect.top + currentScroll - offset;
                        window.scrollTo({ top: targetTop, behavior: "smooth" });
                        setActiveSubnav(window.location.hash);
                    }
                }, 120);
            }
        }());
    </script>
</body>
</html>
