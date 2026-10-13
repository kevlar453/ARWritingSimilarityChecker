<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title><?= esc($title ?? 'Benchmark Terbuka Naskah Akademik Bahasa Indonesia — Evaluasi Empiris AR Tilik') ?></title>
    <meta name="description" content="Hasil evaluasi empiris terbuka AR Tilik pada korpus naskah akademik Bahasa Indonesia. Transparansi penuh: akurasi kemiripan tekstual, deteksi konteks sitasi, indikator stylometri, dan publikasi 14 kasus mismatch apa adanya.">
    <meta name="robots" content="index, follow, max-snippet:-1, max-image-preview:large">
    <link rel="canonical" href="<?= base_url('benchmark') ?>">
    <meta name="theme-color" content="#4338ca">

    <!-- Open Graph -->
    <meta property="og:type" content="article">
    <meta property="og:locale" content="id_ID">
    <meta property="og:site_name" content="AR Tilik">
    <meta property="og:title" content="Benchmark Terbuka Naskah Akademik Bahasa Indonesia — AR Tilik">
    <meta property="og:description" content="Publikasi hasil uji empiris, matriks konfusi, dan transparansi 14 kasus titik kegagalan mesin pada korpus akademik Indonesia.">
    <meta property="og:url" content="<?= base_url('benchmark') ?>">
    <meta property="og:image" content="<?= base_url('images/og-artilik.png') ?>">

    <!-- Favicon & Icons -->
    <link rel="icon" type="image/x-icon" href="<?= base_url('favicon.ico?v=20261008') ?>">
    <link rel="icon" type="image/png" sizes="32x32" href="<?= base_url('favicon-32x32.png?v=20261008') ?>">
    <link rel="icon" type="image/png" sizes="16x16" href="<?= base_url('favicon-16x16.png?v=20261008') ?>">
    <link rel="apple-touch-icon" sizes="180x180" href="<?= base_url('apple-touch-icon.png?v=20261008') ?>">

    <!-- Google Fonts -->
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

        /* Fixed Navbar */
        .navbar {
            position: fixed;
            top: 0; left: 0; right: 0;
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
            width: 36px;
            height: 36px;
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
            gap: 20px;
            list-style: none;
        }
        .nav-links a {
            font-size: 14px;
            font-weight: 600;
            color: var(--text-muted);
            transition: color 0.15s ease;
        }
        .nav-links a:hover, .nav-links a.active { color: var(--primary); }
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
        .btn-nav-back:hover { color: var(--dark); background: #e2e8f0; }
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
        .btn-nav-primary:hover { background: var(--primary-dark); transform: translateY(-1px); }

        /* Secondary Sub-Nav */
        .subnav {
            position: fixed;
            top: var(--nav-height, 58px);
            left: 0; right: 0;
            z-index: 999;
            background: #ffffff;
            border-bottom: 1px solid var(--border);
            overflow-x: auto;
            white-space: nowrap;
        }
        .subnav-inner {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 0;
        }
        .subnav-link {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 14px;
            border-radius: 9999px;
            font-size: 13px;
            font-weight: 600;
            color: var(--text-muted);
            transition: all 0.15s ease;
        }
        .subnav-link:hover { color: var(--primary); background: rgba(67, 56, 202, 0.05); }

        /* Hero */
        .hero-wrap {
            padding-top: 135px;
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
            margin-bottom: 18px;
        }
        .breadcrumbs a:hover { color: var(--primary); }
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
        .pill-tag .dot { width: 7px; height: 7px; border-radius: 50%; background: var(--accent-emerald); }

        h1.hero-title {
            font-size: clamp(28px, 3.8vw, 48px);
            font-weight: 800;
            line-height: 1.2;
            letter-spacing: -1px;
            color: var(--dark);
            margin-bottom: 18px;
        }
        h1.hero-title em {
            font-style: normal;
            background: linear-gradient(90deg, #4338ca, #0284c7);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .hero-lead {
            font-size: clamp(15px, 1.3vw, 18px);
            color: #475569;
            line-height: 1.65;
            max-width: 880px;
            margin-bottom: 24px;
        }
        .hero-meta-bar {
            display: flex;
            flex-wrap: wrap;
            gap: 16px;
            align-items: center;
            padding-top: 18px;
            border-top: 1px dashed var(--border);
            font-size: 13px;
            color: var(--text-muted);
        }
        .meta-item { display: inline-flex; align-items: center; gap: 6px; }
        .meta-item strong { color: var(--dark); }

        /* Stats Strip */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 16px;
            margin: -24px 0 48px 0;
            position: relative;
            z-index: 10;
        }
        .stat-card {
            background: #ffffff;
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 20px;
            box-shadow: var(--shadow-sm);
            transition: all 0.2s ease;
        }
        .stat-card:hover { transform: translateY(-2px); box-shadow: var(--shadow-md); border-color: var(--primary-light); }
        .stat-num {
            font-family: var(--mono);
            font-size: 32px;
            font-weight: 800;
            color: var(--primary);
            line-height: 1;
            margin-bottom: 6px;
        }
        .stat-num.emerald { color: var(--accent-emerald); }
        .stat-num.amber { color: var(--accent-amber); }
        .stat-label { font-size: 13px; font-weight: 700; color: var(--dark); margin-bottom: 4px; }
        .stat-desc { font-size: 12px; color: var(--text-muted); line-height: 1.4; }

        /* Content Sections */
        .section-block {
            margin-bottom: 56px;
            background: #ffffff;
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 36px clamp(20px, 4vw, 40px);
            box-shadow: var(--shadow-sm);
        }
        .sec-head {
            margin-bottom: 24px;
            border-bottom: 1px solid var(--border);
            padding-bottom: 16px;
        }
        .sec-tag {
            font-family: var(--mono);
            font-size: 11px;
            font-weight: 700;
            color: var(--primary);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 6px;
        }
        .sec-title {
            font-size: clamp(22px, 2.4vw, 30px);
            font-weight: 800;
            color: var(--dark);
            letter-spacing: -0.5px;
        }
        .sec-desc {
            font-size: 14px;
            color: var(--text-muted);
            margin-top: 6px;
            max-width: 820px;
        }

        /* Callout Box */
        .callout-box {
            padding: 20px 24px;
            border-radius: 14px;
            margin: 20px 0;
            display: flex;
            gap: 16px;
            align-items: flex-start;
        }
        .callout-indigo {
            background: #eef2ff;
            border: 1px solid #c7d2fe;
            color: #312e81;
        }
        .callout-amber {
            background: #fffbeb;
            border: 1px solid #fde68a;
            color: #92400e;
        }
        .callout-emerald {
            background: #ecfdf5;
            border: 1px solid #a7f3d0;
            color: #065f46;
        }
        .callout-rose {
            background: #fff1f2;
            border: 1px solid #fecdd3;
            color: #9f1239;
        }
        .callout-icon { font-size: 24px; line-height: 1; shrink: 0; }
        .callout-text h4 { font-size: 15px; font-weight: 700; margin-bottom: 6px; }
        .callout-text p { font-size: 13px; line-height: 1.6; }

        /* Tables */
        .table-responsive {
            overflow-x: auto;
            margin: 20px 0;
            border: 1px solid var(--border);
            border-radius: 12px;
        }
        table.bench-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
            text-align: left;
            white-space: nowrap;
        }
        table.bench-table th {
            background: #f8fafc;
            padding: 12px 16px;
            font-weight: 700;
            color: var(--dark);
            border-bottom: 1px solid var(--border);
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        table.bench-table td {
            padding: 12px 16px;
            border-bottom: 1px solid #f1f5f9;
            color: #334155;
        }
        table.bench-table tr:last-child td { border-bottom: none; }
        table.bench-table tr:hover td { background: #f8fafc; }
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 3px 8px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 700;
            font-family: var(--mono);
        }
        .badge-pass { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        .badge-fail { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
        .badge-review { background: #fef3c7; color: #92400e; border: 1px solid #fde68a; }
        .badge-code { background: #f1f5f9; color: #475569; border: 1px solid var(--border); }

        /* Code block */
        .code-box {
            background: #0f172a;
            color: #e2e8f0;
            padding: 16px 20px;
            border-radius: 12px;
            font-family: var(--mono);
            font-size: 12.5px;
            line-height: 1.6;
            overflow-x: auto;
            margin: 16px 0;
            position: relative;
        }

        /* Buttons CTA */
        .btn-group {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 24px;
        }
        .btn-primary {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 22px;
            border-radius: 12px;
            background: var(--primary);
            color: #ffffff;
            font-size: 14px;
            font-weight: 700;
            box-shadow: 0 4px 14px rgba(67, 56, 202, 0.25);
            transition: all 0.15s ease;
            cursor: pointer;
            border: none;
        }
        .btn-primary:hover { background: var(--primary-dark); transform: translateY(-1px); }
        .btn-secondary {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 22px;
            border-radius: 12px;
            background: #ffffff;
            color: var(--dark);
            font-size: 14px;
            font-weight: 700;
            border: 1px solid var(--border);
            box-shadow: var(--shadow-sm);
            transition: all 0.15s ease;
            cursor: pointer;
        }
        .btn-secondary:hover { background: #f8fafc; border-color: var(--border-subtle); transform: translateY(-1px); }

        /* Footer */
        .bench-footer {
            border-top: 1px solid var(--border);
            background: #ffffff;
            padding: 40px 0;
            font-size: 13px;
            color: var(--text-muted);
            text-align: center;
        }

        @media (max-width: 768px) {
            .subnav { display: none; }
            .hero-wrap { padding-top: 85px; }
            .stats-grid { margin-top: 16px; }
            .section-block { padding: 24px 16px; }
        }
    </style>
</head>
<body>

    <!-- ─── Primary Header Navbar ─── -->
    <header class="navbar">
        <div class="container nav-inner">
            <a href="<?= base_url('/') ?>" class="brand">
                <img src="<?= base_url('images/logo.png') ?>" alt="AR Tilik Logo" class="brand-logo" onerror="this.src='data:image/svg+xml;utf8,<svg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 40 40\'><rect width=\'40\' height=\'40\' rx=\'8\' fill=\'%234338ca\'/><text x=\'50%52\' y=\'55%52\' dominant-baseline=\'middle\' text-anchor=\'middle\' fill=\'white\' font-family=\'sans-serif\' font-size=\'20\' font-weight=\'bold\'>AR</text></svg>'">
                <span class="brand-title">AR Tilik</span>
                <span class="brand-badge">Riset</span>
            </a>

            <ul class="nav-links">
                <li><a href="<?= base_url('/#keunggulan') ?>">Metodologi</a></li>
                <li><a href="<?= base_url('benchmark') ?>" class="active">Benchmark Lokal</a></li>
                <li><a href="<?= base_url('riset/ar-tilik-vs-turnitin') ?>">vs Turnitin</a></li>
                <li><a href="<?= base_url('riset/ar-tilik-vs-zerogpt') ?>">vs ZeroGPT</a></li>
                <li><a href="<?= base_url('integrations/ojs/pdf') ?>" target="_blank">Integrasi OJS</a></li>
            </ul>

            <div class="nav-actions">
                <a href="<?= base_url('/') ?>" class="btn-nav-back">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
                    <span>Beranda</span>
                </a>
                <a href="<?= base_url('/?auth=register') ?>" class="btn-nav-primary">Coba Sekarang</a>
            </div>
        </div>
    </header>

    <!-- ─── Sticky Subnav Quick Links ─── -->
    <nav class="subnav">
        <div class="container subnav-inner">
            <span style="font-size: 12px; font-weight: 800; color: var(--dark); margin-right: 8px;">NAVIGASI RISET:</span>
            <a href="#ringkasan" class="subnav-link">Ringkasan Eksekutif</a>
            <a href="#dataset" class="subnav-link">Komposisi Dataset</a>
            <a href="#gates" class="subnav-link">Matriks Quality Gates</a>
            <a href="#mismatch" class="subnav-link" style="color: var(--accent-rose); font-weight: 700;">14 Kasus Mismatch</a>
            <a href="#stylometri" class="subnav-link">Stylometri & AI</a>
            <a href="#dualreader" class="subnav-link">Dual-Reader & OJS</a>
            <a href="#onpremise" class="subnav-link">On-Premise & Privasi</a>
            <a href="#unduh" class="subnav-link">Unduh Dataset</a>
        </div>
    </nav>

    <!-- ─── Hero Section ─── -->
    <section class="hero-wrap">
        <div class="container">
            <div class="breadcrumbs">
                <a href="<?= base_url('/') ?>">AR Tilik</a>
                <span>/</span>
                <a href="<?= base_url('benchmark') ?>">Publikasi Riset</a>
                <span>/</span>
                <span style="color: var(--dark);">Benchmark Naskah Akademik Bahasa Indonesia</span>
            </div>

            <div class="pill-tag">
                <span class="dot"></span>
                <span>Open Science & Radical Transparency Benchmark</span>
            </div>

            <h1 class="hero-title">
                Benchmark Terbuka Naskah Akademik Bahasa Indonesia:<br>
                <em>Hasil Uji Empiris &amp; Publikasi Titik Kegagalan AR Tilik</em>
            </h1>

            <p class="hero-lead">
                Turnitin dan ZeroGPT beroperasi sebagai kotak hitam tanpa transparansi alasan putusan untuk Bahasa Indonesia.
                AR Tilik mengambil langkah ilmiah terbuka: kami menguji performa engine pada korpus naskah akademik Indonesia
                dan <strong>mempublikasikan seluruh hasilnya apa adanya</strong> — termasuk 14 kasus anomali di mana sistem kami
                mengalami keterbatasan.
            </p>

            <div class="hero-meta-bar">
                <div class="meta-item">
                    <span>🔬 Versi Evaluasi:</span>
                    <strong>v1.1.0 Production Baseline (Frozen Engine)</strong>
                </div>
                <div class="meta-item">
                    <span>📚 Ukuran Korpus Uji:</span>
                    <strong>80 Targeted Contextual + 500 Baseline Cases</strong>
                </div>
                <div class="meta-item">
                    <span>⚖️ Prinsip:</span>
                    <strong>Zero Suppression &amp; Academic Fairness</strong>
                </div>
                <div class="meta-item">
                    <span>📄 Lisensi Data:</span>
                    <strong>Creative Commons Attribution 4.0 (CC BY 4.0)</strong>
                </div>
            </div>

            <div class="btn-group">
                <a href="<?= base_url('benchmark/dataset/targeted_80') ?>" class="btn-primary">
                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    <span>Unduh Dataset 80 Kasus (JSON)</span>
                </a>
                <a href="#mismatch" class="btn-secondary">
                    <span>Lihat 14 Kasus Mismatch Sistem</span>
                    <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M19 9l-7 7-7-7"/></svg>
                </a>
            </div>
        </div>
    </section>

    <!-- ─── Main Content ─── -->
    <main class="container" style="padding-top: 36px;">

        <!-- Stats Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-num emerald">100.0%</div>
                <div class="stat-label">Akurasi Konteks Akademik</div>
                <div class="stat-desc">80 dari 80 kasus sitasi, kutipan langsung, dan blok kutipan terdeteksi presisi.</div>
            </div>
            <div class="stat-card">
                <div class="stat-num emerald">100.0%</div>
                <div class="stat-label">Atribusi Status Sitasi</div>
                <div class="stat-desc">Pembedaan teks berizin (cited) vs tanpa sitasi (uncited) tanpa kebocoran logika.</div>
            </div>
            <div class="stat-card">
                <div class="stat-num emerald">0.23 ms</div>
                <div class="stat-label">Rata-rata Overhead Parsing</div>
                <div class="stat-desc">SLA P95: 0.186 ms per pasase, menjamin respons cepat pada submisi OJS massal.</div>
            </div>
            <div class="stat-card">
                <div class="stat-num amber">14 Kasus</div>
                <div class="stat-label">Mismatch Terbuka (17.5%)</div>
                <div class="stat-desc">10 batas metodologi boilerplate + 4 boundary threshold. Dipublikasikan secara jujur.</div>
            </div>
        </div>

        <!-- Section 1: Ringkasan Eksekutif & Mengapa Ini Krusial -->
        <section id="ringkasan" class="section-block">
            <div class="sec-head">
                <div class="sec-tag">Bagian 1 — Latar Belakang Ilmiah</div>
                <h2 class="sec-title">Mengapa Komparasi Ini Tidak Pernah Dimiliki Kompetitor?</h2>
                <p class="sec-desc">Problematika mendasar alat komersial global dalam menangani naskah akademik berbahasa Indonesia.</p>
            </div>

            <p style="margin-bottom: 16px;">
                Sebagian besar perguruan tinggi di Indonesia bergantung pada sistem komersial tertutup seperti <strong>Turnitin</strong>
                atau detektor probabilitas seperti <strong>ZeroGPT</strong>. Namun, kedua platform tersebut memiliki kelemahan struktural
                dalam konteks kepenulisan Bahasa Indonesia:
            </p>

            <ul style="margin-left: 20px; margin-bottom: 20px; line-height: 1.8;">
                <li><strong>Turnitin (Closed Black-Box):</strong> Tidak memublikasikan angka toleransi frasa baku metodologis untuk Bahasa Indonesia. Kalimat metodologi umum seperti <em>"Penelitian ini menggunakan pendekatan kuantitatif dengan kuesioner skala Likert..."</em> kerap divonis sebagai kesamaan tinggi tanpa alasan ilmiah terurai.</li>
                <li><strong>ZeroGPT (Tingkat False Positive 80%):</strong> Penelitian independen <strong>Falaq (2026)</strong> pada 80 naskah akademik Bahasa Indonesia menunjukkan ZeroGPT memiliki <strong>false-positive rate hingga 80%</strong> (32 dari 40 naskah karya manusia murni dituduh sebagai buatan AI). Hal ini sejalan dengan temuan Stanford (Liang et al.) mengenai bias detektor terhadap penulis non-native.</li>
                <li><strong>Absennya Data Terbuka:</strong> Tidak ada satu pun penyedia sistem pemeriksaan kemiripan naskah yang berani merilis dataset pengujian lokal berlabel Bahasa Indonesia beserta titik-titik di mana mesin mereka gagal.</li>
            </ul>

            <div class="callout-box callout-indigo">
                <div class="callout-icon">💡</div>
                <div class="callout-text">
                    <h4>Prinsip Keadilan Akademik AR Tilik</h4>
                    <p>
                        AR Tilik memegang teguh prinsip: <em>"Deteksi kesamaan mesin adalah instrumen bukti objektif, bukan vonis otomatis pelanggaran integritas."</em>
                        Oleh karena itu, kami menolak manipulasi metrik (non-suppression invariant) dan membuka seluruh data pengujian agar dewan etik, penelaah jurnal, dan mahasiswa mendapatkan kepastian yang adil.
                    </p>
                </div>
            </div>
        </section>

        <!-- Section 2: Komposisi Dataset Uji -->
        <section id="dataset" class="section-block">
            <div class="sec-head">
                <div class="sec-tag">Bagian 2 — Metodologi & Komposisi Korpus</div>
                <h2 class="sec-title">Struktur 80 Kasus Terarah & 500 Kasus Baseline Mutasi</h2>
                <p class="sec-desc">Pemisahan mutlak antara Source Corpus (target pencarian) dan Evaluation Dataset (data uji tolok ukur) demi mencegah kontaminasi data (data leakage).</p>
            </div>

            <div class="table-responsive">
                <table class="bench-table">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Kelompok Kasus</th>
                            <th>Kode Prefix</th>
                            <th>Jumlah</th>
                            <th>Karakteristik Teks Akademik Indonesia</th>
                            <th>Ekspektasi Konteks</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>1</td>
                            <td><strong>Uncited Direct Overlap</strong></td>
                            <td><span class="badge badge-code">EV11-UNC</span></td>
                            <td>10 Kasus</td>
                            <td>Penyalinan verbatim kalimat ilmiah tanpa tanda kutip maupun sitasi.</td>
                            <td><span class="badge badge-fail">NONE (Not Cited)</span></td>
                        </tr>
                        <tr>
                            <td>2</td>
                            <td><strong>Parenthetical Citation</strong></td>
                            <td><span class="badge badge-code">EV11-PAR</span></td>
                            <td>10 Kasus</td>
                            <td>Teks ilmiah disertai sitasi kurung standar APA/IEEE (misal: <em>Sugiyono, 2020: 45</em>).</td>
                            <td><span class="badge badge-pass">PARENTHETICAL</span></td>
                        </tr>
                        <tr>
                            <td>3</td>
                            <td><strong>Inline / Narrative Citation</strong></td>
                            <td><span class="badge badge-code">EV11-INL</span></td>
                            <td>10 Kasus</td>
                            <td>Sitasi terintegrasi dalam alur kalimat (misal: <em>"Menurut penelitian Sutrisno (2021)..."</em>).</td>
                            <td><span class="badge badge-pass">INLINE</span></td>
                        </tr>
                        <tr>
                            <td>4</td>
                            <td><strong>Direct / Block Quotes</strong></td>
                            <td><span class="badge badge-code">EV11-QUO</span></td>
                            <td>10 Kasus</td>
                            <td>Kutipan harfiah dengan tanda petik ganda atau format blok pasase.</td>
                            <td><span class="badge badge-pass">DIRECT_QUOTE</span></td>
                        </tr>
                        <tr>
                            <td>5</td>
                            <td><strong>Paraphrase + Citation</strong></td>
                            <td><span class="badge badge-code">EV11-PRA</span></td>
                            <td>10 Kasus</td>
                            <td>Restrukturisasi sintaksis aktif-pasif dan substitusi sinonim bersitasi.</td>
                            <td><span class="badge badge-pass">PARENTHETICAL / INLINE</span></td>
                        </tr>
                        <tr>
                            <td>6</td>
                            <td><strong>Methodology Boilerplate</strong></td>
                            <td><span class="badge badge-code">EV11-MET</span></td>
                            <td>10 Kasus</td>
                            <td>Formulaik instrumen baku (misal: uji reliabilitas Cronbach Alpha, purposive sampling).</td>
                            <td><span class="badge badge-review">NONE (Formulaic)</span></td>
                        </tr>
                        <tr>
                            <td>7</td>
                            <td><strong>Lexical Divergence (Zero Exact)</strong></td>
                            <td><span class="badge badge-code">EV11-DIV</span></td>
                            <td>10 Kasus</td>
                            <td>Exact Match 0%, namun TF-IDF Cosine tinggi karena kesamaan domain topik.</td>
                            <td><span class="badge badge-code">NONE</span></td>
                        </tr>
                        <tr>
                            <td>8</td>
                            <td><strong>Unrelated Citation Distractor</strong></td>
                            <td><span class="badge badge-code">EV11-DIS</span></td>
                            <td>10 Kasus</td>
                            <td>Penyalinan verbatim yang disisipi sitasi pengecoh yang tidak relevan dengan topik.</td>
                            <td><span class="badge badge-pass">PARENTHETICAL</span></td>
                        </tr>
                        <tr style="background: #f1f5f9; font-weight: 700;">
                            <td colspan="3">TOTAL KORPUS TERARAH</td>
                            <td>80 Kasus</td>
                            <td colspan="2">Tersimpan dalam format terstruktur di <code>evaluation_dataset_v1_1_targeted_80/cases.json</code></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- Section 3: Matriks Quality Gates v1.1.0 -->
        <section id="gates" class="section-block">
            <div class="sec-head">
                <div class="sec-tag">Bagian 3 — Hasil Pengujian Formal</div>
                <h2 class="sec-title">Matriks Sertifikasi Mutu &amp; Kepatuhan Invariant (Gate V11-B)</h2>
                <p class="sec-desc">Evaluasi terhadap baseline mesin v1.0.0 yang dibekukan secara permanen (0 baris modifikasi pada <code>worker/hybrid.py</code>).</p>
            </div>

            <div class="table-responsive">
                <table class="bench-table">
                    <thead>
                        <tr>
                            <th>Quality Gate</th>
                            <th>Lingkup Pengujian</th>
                            <th>Kriteria Toleransi</th>
                            <th>Hasil Aktual AR Tilik</th>
                            <th>Status Kelulusan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><strong>Gate V11-B1</strong></td>
                            <td>Akurasi Deteksi Konteks Akademik</td>
                            <td>&ge; 95.0%</td>
                            <td><strong>100.0% (80 / 80 kasus)</strong></td>
                            <td><span class="badge badge-pass">✅ PASS</span></td>
                        </tr>
                        <tr>
                            <td><strong>Gate V11-B2</strong></td>
                            <td>Akurasi Status Atribusi Sitasi</td>
                            <td>&ge; 95.0%</td>
                            <td><strong>100.0% (80 / 80 kasus)</strong></td>
                            <td><span class="badge badge-pass">✅ PASS</span></td>
                        </tr>
                        <tr>
                            <td><strong>Gate V11-B3</strong></td>
                            <td>Non-Suppression Invariance (Zero-Suppression)</td>
                            <td>100.0% Identik</td>
                            <td><strong>100.0% (Skor exact &amp; kosinus tidak dimanipulasi)</strong></td>
                            <td><span class="badge badge-pass">✅ PASS</span></td>
                        </tr>
                        <tr>
                            <td><strong>Gate V11-B4</strong></td>
                            <td>Decision Invariance Terhadap Baseline</td>
                            <td>100.0% Identik</td>
                            <td><strong>100.0% (Keputusan v1.0 tidak berubah)</strong></td>
                            <td><span class="badge badge-pass">✅ PASS</span></td>
                        </tr>
                        <tr>
                            <td><strong>Gate V11-B5</strong></td>
                            <td>Confirmed Engine Defects</td>
                            <td>0 Cacat Engine</td>
                            <td><strong>0 Defek Terkonfirmasi</strong></td>
                            <td><span class="badge badge-pass">✅ PASS</span></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <p style="font-size: 13.5px; color: var(--text-muted); margin-top: 14px;">
                <em>Keterangan Gate V11-B3 (Non-Suppression):</em> Berbeda dengan sistem lain yang sering secara sepihak memotong angka kemiripan ketika menemukan sitasi,
                AR Tilik menjamin bahwa angka kemiripan leksikal murni tetap ditampilkan apa adanya, sementara status sitasi disajikan sebagai lapisan interpretatif terpisah bagi penelaah.
            </p>
        </section>

        <!-- Section 4: 14 Kasus Mismatch Apa Adanya -->
        <section id="mismatch" class="section-block" style="border-left: 4px solid var(--accent-rose);">
            <div class="sec-head">
                <div class="sec-tag" style="color: var(--accent-rose);">Bagian 4 — Transparansi Penuh (Radical Transparency)</div>
                <h2 class="sec-title">Publikasi 14 Kasus Mismatch: Di Mana Mesin Mengalami Batas Kemampuan?</h2>
                <p class="sec-desc">Dari 80 kasus terarah, terdapat 66 kasus (82.5%) yang keputusannya identik dengan label benchmark post-hoc, dan 14 kasus (17.5%) yang mengalami mismatch. Berikut rincian ilmiahnya:</p>
            </div>

            <div class="callout-box callout-rose">
                <div class="callout-icon">⚠️</div>
                <div class="callout-text">
                    <h4>Mengapa Kami Mempublikasikan Kegagalan Ini?</h4>
                    <p>
                        Dalam sains dan rekayasa perangkat lunak akademik, sistem yang mengklaim <em>"akurasi 100% tanpa cacat"</em> adalah kebohongan pemasaran.
                        Dengan mempublikasikan 14 anomali ini, dewan juri, reviewer jurnal, dan institusi memahami dengan persis batas matematis mesin kami,
                        serta mengapa intervensi penelaah manusia (*Human-in-the-Loop*) tetap menjadi pilar utama keadilan akademik.
                    </p>
                </div>
            </div>

            <!-- Mismatch 1: Boilerplate -->
            <div style="margin: 24px 0; padding: 20px; border: 1px solid #fed7aa; background: #fffaf0; border-radius: 12px;">
                <h3 style="font-size: 16px; font-weight: 800; color: #9a3412; margin-bottom: 8px;">
                    1. Kelompok 6: Methodology Boilerplate (10 Kasus: EV11-MET-001 s.d. EV11-MET-010)
                </h3>
                <p style="font-size: 13px; color: #7c2d12; line-height: 1.6; margin-bottom: 12px;">
                    <strong>Taksonomi Kegagalan:</strong> <span class="badge badge-code">ENGINE_LIMITATION:NO_CONTEXT_AWARENESS</span><br>
                    <strong>Ekspektasi Benchmark:</strong> <code>REVIEW</code> (Pola: <code>METHODOLOGY_BOILERPLATE</code>)<br>
                    <strong>Keputusan Aktual Engine:</strong> <code>MATCH</code> (Pola: <code>VERBATIM_MATCH</code>)<br>
                    <strong>Metrik Riil:</strong> Exact Similarity 73.68% – 79.17%, Cosine TF-IDF > 85.0%, Longest Match: 14 – 19 kata berurutan.
                </p>
                <div class="code-box">
Contoh Teks Submisi (EV11-MET-001):
"Metode penelitian yang digunakan adalah pendekatan kuantitatif dengan analisis regresi linier berganda
guna menguji pengaruh variabel independen terhadap variabel dependen dengan taraf signifikansi 5%."
                </div>
                <p style="font-size: 13px; color: #431407; line-height: 1.6;">
                    <strong>Akar Masalah:</strong> Mesin pencocok string matematis v1.0.0 menerapkan aturan pasti: jika <code>exact_sim &ge; 70%</code> dan <code>cosine &ge; 85%</code>, sistem wajib menetapkan <code>MATCH</code>. Mesin statistik tidak memiliki pemahaman semantik bahwa rangkaian kata tersebut adalah formulasi metodologi penelitian baku di Indonesia yang wajar digunakan berulang.
                    <br><strong>Solusi di v1.1+:</strong> Skor kesamaan tekstual tidak dimanipulasi, namun lapisan penyajian v1.1 menandai pasase ini dengan label <em>"Terdeteksi Frasa Baku Metodologis"</em> sehingga penelaah manusia dapat memaafkannya secara objektif.
                </p>
            </div>

            <!-- Mismatch 2: Distractor -->
            <div style="margin: 24px 0; padding: 20px; border: 1px solid #fed7aa; background: #fffaf0; border-radius: 12px;">
                <h3 style="font-size: 16px; font-weight: 800; color: #9a3412; margin-bottom: 8px;">
                    2. Kelompok 8: Unrelated Citation Distractor (3 Kasus: EV11-DIS-001, DIS-006, DIS-010)
                </h3>
                <p style="font-size: 13px; color: #7c2d12; line-height: 1.6; margin-bottom: 12px;">
                    <strong>Taksonomi Kegagalan:</strong> <span class="badge badge-code">BOUNDARY_BEHAVIOR:THRESHOLD_TRANSITION</span><br>
                    <strong>Ekspektasi Benchmark:</strong> <code>MATCH</code> (Pola: <code>VERBATIM_MATCH</code>)<br>
                    <strong>Keputusan Aktual Engine:</strong> <code>REVIEW</code> (Pola: <code>COPY_EDIT</code>)<br>
                    <strong>Metrik Riil:</strong> Exact Similarity 70.0% – 74.07%, Cosine TF-IDF: 83.55% – 84.93%.
                </p>
                <p style="font-size: 13px; color: #431407; line-height: 1.6;">
                    <strong>Akar Masalah:</strong> Penambahan kalimat sitasi pengecoh yang panjang mendilusi kesamaan kosinus TF-IDF hingga sedikit berada di bawah ambang batas kesamaan mutlak (85.0%). Karena exact &ge; 70% namun kosinus < 85%, sistem v1.0.0 secara akurat memicu aturan <code>COPY_EDIT</code> (yang menghasilkan <code>REVIEW</code>). Ini adalah perilaku transisi ambang batas (*boundary behavior*) yang sah dari mesin.
                </p>
            </div>

            <!-- Mismatch 3: Extreme Paraphrase -->
            <div style="margin: 24px 0; padding: 20px; border: 1px solid #fed7aa; background: #fffaf0; border-radius: 12px;">
                <h3 style="font-size: 16px; font-weight: 800; color: #9a3412; margin-bottom: 8px;">
                    3. Kelompok 5: Extreme Paraphrase + Citation (1 Kasus: EV11-PRA-009)
                </h3>
                <p style="font-size: 13px; color: #7c2d12; line-height: 1.6; margin-bottom: 12px;">
                    <strong>Taksonomi Kegagalan:</strong> <span class="badge badge-code">BOUNDARY_BEHAVIOR:THRESHOLD_TRANSITION</span><br>
                    <strong>Ekspektasi Benchmark:</strong> <code>REVIEW</code> (Pola: <code>SEMANTIC_PARAPHRASE</code>)<br>
                    <strong>Keputusan Aktual Engine:</strong> <code>NO_MATCH</code> (Pola: <code>NO_EVIDENCE</code>)<br>
                    <strong>Metrik Riil:</strong> Exact Similarity 0.0%, Cosine TF-IDF: 9.72% (Ambang batas peninjauan kosinus v1.0.0 adalah 10.0%).
                </p>
                <p style="font-size: 13px; color: #431407; line-height: 1.6;">
                    <strong>Akar Masalah:</strong> Parafrase tingkat tinggi dengan transposisi kalimat total membuat kesamaan kosinus berada tepat 0.28% di bawah ambang batas deteksi peninjauan (10.0%), sehingga engine deterministik v1.0.0 memutuskan <code>NO_MATCH</code>. Namun, detektor sitasi v1.1 tetap berhasil menandai sitasi naratif <em>"Menurut Anderson (2023)..."</em> dengan span karakter akurat.
                </p>
            </div>
        </section>

        <!-- Section 5: Stylometri & Indikator AI Transparan -->
        <section id="stylometri" class="section-block">
            <div class="sec-head">
                <div class="sec-tag">Bagian 5 — Sinyal Gaya Bahasa</div>
                <h2 class="sec-title">Profil Stylometri Terbuka (Manusia vs AI): Bukan Vonis Tuduhan</h2>
                <p class="sec-desc">Bagaimana AR Tilik mengukur variasi ritme sintaksis bahasa Indonesia tanpa terjebak dalam perangkap false-positive 80% seperti ZeroGPT.</p>
            </div>

            <p style="margin-bottom: 16px;">
                AR Tilik menolak keras pendekatan detektor probabilitas hitam seperti ZeroGPT yang melontarkan vonis <em>"Teks ini 98% buatan AI"</em>.
                Pada modul <a href="file:///var/www/html/ARWritingSimilarityChecker/worker/ai_stylometrics.py"><code>worker/ai_stylometrics.py</code></a>, kami menyediakan 5 indikator statistik terbuka yang dikalibrasi pada korpus empiris naskah akademik Indonesia:
            </p>

            <div class="table-responsive">
                <table class="bench-table">
                    <thead>
                        <tr>
                            <th>Metrik Stylometri</th>
                            <th>Teks Mahasiswa Manusia (50 Skripsi)</th>
                            <th>Draf Mentah LLM (ChatGPT / Gemini)</th>
                            <th>Teks Pemintal / Spinner (QuillBot)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><strong>Sentence Burstiness (CV)</strong></td>
                            <td><span class="badge badge-pass">> 0.40 (Organik &amp; Dinamis)</span></td>
                            <td><span class="badge badge-fail">&lt; 0.25 (Sangat Seragam &amp; Monoton)</span></td>
                            <td>0.30 – 0.42 (Bervariasi semu)</td>
                        </tr>
                        <tr>
                            <td><strong>Segmental Monotony Ratio</strong></td>
                            <td>&lt; 15.0% paragraf berpola seragam</td>
                            <td>> 40.0% paragraf berpola identik</td>
                            <td>15.0% – 30.0%</td>
                        </tr>
                        <tr>
                            <td><strong>Kepadatan Klise Transisi AI</strong></td>
                            <td>&lt; 1.2 frasa per 1.000 kata</td>
                            <td>> 3.5 frasa per 1.000 kata</td>
                            <td>1.5 – 2.8 frasa per 1.000 kata</td>
                        </tr>
                        <tr>
                            <td><strong>Rekayasa Leksikal / Spinner</strong></td>
                            <td>0 anomali sinonim hiperbolis</td>
                            <td>Rendah (Kalimat gramatikal wajar)</td>
                            <td><span class="badge badge-fail">Tinggi (Substitusi kata canggung)</span></td>
                        </tr>
                        <tr style="background: #f8fafc; font-weight: 700;">
                            <td>Skor Indikator Gabungan (P95)</td>
                            <td><strong>25.5 dari 100.0</strong></td>
                            <td><strong>> 75.0 dari 100.0</strong></td>
                            <td><strong>55.0 – 70.0</strong></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="callout-box callout-amber">
                <div class="callout-icon">⚖️</div>
                <div class="callout-text">
                    <h4>Disclaimer Etika &amp; Regulasi Pengujian Sidang</h4>
                    <p>
                        Setiap skor indikator gaya bahasa di AR Tilik selalu disertai pengingat hukum:
                        <em>"Indikator ini bersifat EKSPERIMENTAL dan hanya menunjukkan variasi statistik ritme kalimat, BUKAN bukti atau vonis penggunaan AI.
                        Gaya penulisan teratur dapat mencerminkan kepenulisan manusia yang disiplin. Dilarang mendiskualifikasi penulis tanpa penelaahan kualitatif substantif."</em>
                    </p>
                </div>
            </div>
        </section>

        <!-- Section 6: Dual-Reader & OJS -->
        <section id="dualreader" class="section-block">
            <div class="sec-head">
                <div class="sec-tag">Bagian 6 — Pengalaman Penelaah</div>
                <h2 class="sec-title">Dual-Reader Interaktif &amp; Integrasi OJS 3.x</h2>
                <p class="sec-desc">Alat penelaahan komparatif berdampingan yang dirancang untuk kebutuhan redaktur jurnal ilmiah dan penguji skripsi.</p>
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; margin: 20px 0;">
                <div style="background: #f8fafc; border: 1px solid var(--border); border-radius: 14px; padding: 20px;">
                    <div style="font-size: 28px; margin-bottom: 8px;">📖</div>
                    <h3 style="font-size: 16px; font-weight: 800; color: var(--dark); margin-bottom: 6px;">Dual-Reader Interaktif Layar Belah</h3>
                    <p style="font-size: 13px; color: var(--text-muted); line-height: 1.6;">
                        Tersedia langsung di antarmuka laporan web via modal layar ganda. Menyandingkan naskah submisi dan dokumen pembanding secara sinkron, lengkap dengan penyorotan pasase kata demi kata (*word-level highlight*), preview PDF asli, serta perbandingan naskah dwibahasa (*cross-lingual English-Indonesian*).
                    </p>
                </div>

                <div style="background: #f8fafc; border: 1px solid var(--border); border-radius: 14px; padding: 20px;">
                    <div style="font-size: 28px; margin-bottom: 8px;">🔌</div>
                    <h3 style="font-size: 16px; font-weight: 800; color: var(--dark); margin-bottom: 6px;">Konektor Native PKP OJS 3.x</h3>
                    <p style="font-size: 13px; color: var(--text-muted); line-height: 1.6;">
                        Dukungan REST API v1 (<code>/api/v1/check</code>) dengan skrip konektor mandiri <code>ARTilikOJSConnector.php</code>. Menerima unggahan file PDF/DOCX langsung dari alur editorial OJS dengan pengembalian callback webhook instan tanpa mengganggu antrean jurnal.
                    </p>
                </div>
            </div>
        </section>

        <!-- Section 7: On-Premise & Data Sovereignty -->
        <section id="onpremise" class="section-block">
            <div class="sec-head">
                <div class="sec-tag">Bagian 7 — Kedaulatan Data Kampus</div>
                <h2 class="sec-title">Hapus Mandiri &amp; Kontainer On-Premise Enterprise</h2>
                <p class="sec-desc">Menjamin naskah pra-publikasi civitas akademika tidak pernah diserahkan ke korpus pihak ketiga.</p>
            </div>

            <p style="margin-bottom: 16px;">
                Salah satu alasan institusi perguruan tinggi ragu menggunakan platform luar negeri adalah kekhawatiran naskah skripsi atau artikel penelitian yang belum dipublikasikan akan disimpan secara sepihak di repositori global (*data lock-in*).
            </p>

            <div class="table-responsive">
                <table class="bench-table">
                    <thead>
                        <tr>
                            <th>Fitur Kedaulatan Data</th>
                            <th>AR Tilik</th>
                            <th>Turnitin / iThenticate Global</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><strong>Kebijakan Non-Repository Default</strong></td>
                            <td><span class="badge badge-pass">✓ Ya, Naskah Tidak Diserap Otomatis</span></td>
                            <td>Memaksa submisi masuk ke repositori institusi global</td>
                        </tr>
                        <tr>
                            <td><strong>Hapus Mandiri Fisik File (Purge)</strong></td>
                            <td><span class="badge badge-pass">✓ Terverifikasi sampai Level Disk Server</span></td>
                            <td>Proses birokrasi manual via administrator universitas</td>
                        </tr>
                        <tr>
                            <td><strong>Pembersihan Jejak Sidik Jari (Fingerprints)</strong></td>
                            <td><span class="badge badge-pass">✓ Hard-delete chunks &amp; token biner</span></td>
                            <td>Residu teks tetap tersimpan di kluster cloud</td>
                        </tr>
                        <tr>
                            <td><strong>Dukungan On-Premise / Private Cloud</strong></td>
                            <td><span class="badge badge-pass">✓ Docker Compose (PHP 8.5 + Python 3.14)</span></td>
                            <td>SaaS Cloud Only (Server berlokasi di AS / Eropa)</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="code-box">
# Menjalankan AR Tilik di Pusat Data Internal Kampus (On-Premise Ready)
git clone https://github.com/kevlar453/ARWritingSimilarityChecker.git
cd ARWritingSimilarityChecker
docker compose -f docker-compose.yml up -d
# Sistem langsung beroperasi di http://localhost:8085 dalam lingkungan jaringan tertutup (Air-Gapped)
            </div>
        </section>

        <!-- Section 8: Unduh Dataset & Reproduksi Mandiri -->
        <section id="unduh" class="section-block" style="text-align: center;">
            <div class="sec-tag" style="margin-bottom: 8px;">Bagian 8 — Reproduktifitas & Sains Terbuka</div>
            <h2 class="sec-title" style="margin-bottom: 12px;">Unduh Dataset &amp; Uji Mandiri di Mesin Anda</h2>
            <p class="sec-desc" style="margin: 0 auto 24px auto;">
                Seluruh data uji tersimpan dalam format JSON terbuka lengkap dengan metadata provenans, hash integritas konten SHA-256, dan operator mutasi. Silakan unduh dan uji independen.
            </p>

            <div class="btn-group" style="justify-content: center;">
                <a href="<?= base_url('benchmark/dataset/targeted_80') ?>" class="btn-primary">
                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    <span>Unduh Dataset Terarah 80 Kasus (JSON)</span>
                </a>
                <a href="<?= base_url('benchmark/dataset/v1_500') ?>" class="btn-secondary">
                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    <span>Unduh Dataset Baseline 500 Kasus (JSON)</span>
                </a>
            </div>

            <div class="code-box" style="max-width: 680px; margin: 24px auto 0 auto; text-align: left;">
# Cara Menjalankan Evaluasi Regresi Mandiri via CLI:
./artilik test
./artilik eval run
./artilik eval review
            </div>
        </section>

    </main>

    <!-- ─── Footer ─── -->
    <footer class="bench-footer">
        <div class="container">
            <p style="font-weight: 700; color: var(--dark); margin-bottom: 6px;">
                AR Tilik — Academic Writing Similarity &amp; Evidence Checker
            </p>
            <p style="margin-bottom: 12px;">
                Dedikasi untuk Integritas Akademik, Keadilan Penilaian, dan Kedaulatan Naskah Riset di Indonesia.
            </p>
            <p style="font-size: 12px;">
                &copy; <?= date('Y') ?> AR Tilik. Seluruh dataset benchmark dirilis di bawah lisensi <a href="https://creativecommons.org/licenses/by/4.0/" target="_blank" style="color: var(--primary); text-decoration: underline;">Creative Commons Attribution 4.0 International (CC BY 4.0)</a>.
            </p>
        </div>
    </footer>

</body>
</html>
