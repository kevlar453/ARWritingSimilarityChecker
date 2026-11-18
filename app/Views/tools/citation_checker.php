<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cek Keberadaan Sitasi &amp; Daftar Pustaka Gratis — AR Tilik</title>
    <meta name="description" content="Periksa keberadaan daftar pustaka secara gratis tanpa login via Crossref dan OpenAlex. Hasil status transparan (Terverifikasi, Meragukan, Tidak terverifikasi) tanpa menyimpan naskah Anda.">
    <link rel="canonical" href="<?= base_url('cek-sitasi') ?>">
    <meta name="theme-color" content="#4338ca">
    <link rel="icon" type="image/png" sizes="32x32" href="<?= base_url('images/logo.png') ?>">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary: #4338ca;
            --primary-dark: #312e81;
            --primary-light: #6366f1;
            --surface: #ffffff;
            --bg: #f8fafc;
            --border: #e2e8f0;
            --border-strong: #94a3b8;
            --text: #0f172a;
            --text-muted: #475569;
            --emerald-bg: #ecfdf5;
            --emerald-bd: #a7f3d0;
            --emerald-fg: #065f46;
            --amber-bg: #fffbeb;
            --amber-bd: #fde68a;
            --amber-fg: #92400e;
            --rose-bg: #fff1f2;
            --rose-bd: #fecdd3;
            --rose-fg: #9f1239;
            --slate-bg: #f1f5f9;
            --slate-bd: #cbd5e1;
            --slate-fg: #334155;
        }

        * { box-sizing: border-box; }
        [hidden] { display: none !important; }

        body {
            margin: 0;
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: var(--bg);
            color: var(--text);
            line-height: 1.6;
        }

        .visually-hidden:where(:not(:focus-within, :active)) {
            position: absolute !important;
            clip-path: inset(50%) !important;
            overflow: hidden !important;
            width: 1px !important;
            height: 1px !important;
            margin: -1px !important;
            padding: 0 !important;
            border: 0 !important;
            white-space: nowrap !important;
        }

        :where(a:any-link, button, textarea, input):focus-visible {
            outline: 3px solid #4338ca;
            outline-offset: 2px;
        }

        /* Header */
        .site-header {
            background: rgba(255, 255, 255, 0.95);
            border-bottom: 1px solid var(--border);
            position: sticky;
            top: 0;
            z-index: 50;
            backdrop-filter: blur(10px);
        }

        .header-inner {
            max-width: 1140px;
            margin: 0 auto;
            padding: 10px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }

        @media (prefers-reduced-motion: no-preference) {
            @view-transition {
                navigation: auto;
            }
        }

        .brand-link {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            color: var(--text);
            font-weight: 800;
            font-size: 1.125rem;
            letter-spacing: -0.02em;
            min-block-size: 44px;
            flex-shrink: 0;
        }

        .brand-link img {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            object-fit: contain;
        }

        .cek-desktop-nav {
            display: flex;
            align-items: center;
        }

        .cek-nav-list {
            display: flex;
            align-items: center;
            gap: 4px;
            list-style: none;
            margin: 0;
            padding: 0;
        }

        .cek-nav-list a {
            display: inline-flex;
            align-items: center;
            padding: 7px 10px;
            border-radius: 8px;
            font-size: 0.84rem;
            font-weight: 600;
            color: var(--text-muted);
            text-decoration: none;
            white-space: nowrap;
            transition: color 0.15s ease, background 0.15s ease;
        }

        .cek-nav-list a:hover {
            color: var(--primary);
            background: #eef2ff;
        }

        .cek-nav-list a[aria-current="page"] {
            color: var(--primary);
            background: #eef2ff;
            font-weight: 700;
            box-shadow: inset 0 -2px 0 var(--primary);
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-shrink: 0;
        }

        .cek-menu-toggle {
            display: none;
            width: 40px;
            height: 40px;
            border-radius: 10px;
            border: 1px solid var(--border);
            background: var(--surface);
            color: var(--text);
            font-size: 1.25rem;
            line-height: 1;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }

        .cek-mobile-panel {
            background: var(--surface);
            border-top: 1px solid var(--border);
            border-bottom: 1px solid var(--border);
            padding: 12px 20px 16px;
        }

        .cek-mobile-panel ul {
            list-style: none;
            margin: 0;
            padding: 0;
            display: grid;
            gap: 6px;
        }

        .cek-mobile-panel a {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10px 12px;
            border-radius: 10px;
            color: var(--text);
            text-decoration: none;
            font-weight: 600;
            font-size: 0.92rem;
        }

        .cek-mobile-panel a[aria-current="page"] {
            background: #eef2ff;
            color: var(--primary);
            font-weight: 700;
        }

        @media (max-width: 880px) {
            .cek-desktop-nav { display: none; }
            .cek-menu-toggle { display: inline-flex; }
        }

        .nav-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 14px;
            min-block-size: 44px;
            border-radius: 10px;
            font-size: 0.875rem;
            font-weight: 600;
            text-decoration: none;
            color: var(--text-muted);
            border: 1px solid var(--border);
            background: var(--surface);
            transition: border-color 0.15s ease, color 0.15s ease;
        }

        .nav-pill:hover {
            border-color: var(--primary-light);
            color: var(--primary);
        }

        .nav-pill-primary {
            background: var(--primary);
            border-color: var(--primary);
            color: #ffffff;
        }

        .nav-pill-primary:hover {
            background: var(--primary-dark);
            border-color: var(--primary-dark);
            color: #ffffff;
        }

        /* Main container */
        .page-main {
            max-width: 960px;
            margin: 0 auto;
            padding: 32px 20px 64px;
        }

        .hero-box {
            margin-bottom: 24px;
        }

        .tool-kicker {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 12px;
            border-radius: 999px;
            background: #eef2ff;
            border: 1px solid #c7d2fe;
            color: var(--primary);
            font-size: 0.78rem;
            font-weight: 700;
            margin-bottom: 10px;
        }

        h1 {
            margin: 0 0 8px;
            font-size: clamp(1.55rem, 2.5vw + 0.85rem, 2.25rem);
            line-height: 1.2;
            letter-spacing: -0.025em;
            color: var(--text);
        }

        .hero-sub {
            margin: 0;
            color: var(--text-muted);
            font-size: 1rem;
            max-width: 72ch;
        }

        /* Disclaimer & Privacy Notices */
        .notice-strip {
            display: grid;
            grid-template-columns: minmax(0, 1fr);
            gap: 10px;
            margin-bottom: 22px;
        }

        @media (min-width: 720px) {
            .notice-strip {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        .notice-card {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            padding: 12px 14px;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 12px;
            font-size: 0.86rem;
            color: var(--text-muted);
        }

        .notice-card strong {
            color: var(--text);
        }

        /* Checker Card */
        .tool-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 18px;
            padding: 22px;
            box-shadow: 0 10px 30px -12px rgba(15, 23, 42, 0.08);
        }

        .tool-meta-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 8px 14px;
            margin-bottom: 16px;
            padding-bottom: 14px;
            border-bottom: 1px solid var(--border);
            font-size: 0.85rem;
            color: var(--text-muted);
        }

        .quota-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 11px;
            border-radius: 999px;
            background: #f1f5f9;
            border: 1px solid var(--slate-bd);
            color: var(--text);
            font-weight: 700;
            font-size: 0.8rem;
        }

        .quota-pill.quota-exhausted {
            background: var(--rose-bg);
            border-color: var(--rose-bd);
            color: var(--rose-fg);
        }

        .field-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
            margin-bottom: 16px;
        }

        .field-label-row {
            display: flex;
            align-items: baseline;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 8px;
        }

        .field-label {
            font-weight: 700;
            font-size: 0.98rem;
            color: var(--text);
        }

        .entry-counter {
            font-size: 0.8rem;
            font-family: 'JetBrains Mono', monospace;
            padding: 2px 9px;
            border-radius: 999px;
            background: var(--slate-bg);
            color: var(--slate-fg);
            font-weight: 700;
        }

        .entry-counter.is-over {
            background: var(--rose-bg);
            color: var(--rose-fg);
            border: 1px solid var(--rose-bd);
        }

        .citation-textarea {
            width: 100%;
            min-height: 210px;
            padding: 14px;
            border: 1.5px solid var(--border-strong);
            border-radius: 12px;
            font: inherit;
            font-size: max(1rem, 1em);
            line-height: 1.6;
            color: var(--text);
            background: #ffffff;
            resize: vertical;
            transition: border-color 0.15s ease, box-shadow 0.15s ease;
        }

        .citation-textarea:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(67, 56, 202, 0.14);
        }

        .citation-textarea:is(:user-invalid, .user-invalid-fallback, [aria-invalid="true"]) {
            border-color: #be123c;
            background: #fffafb;
        }

        .field-hint {
            font-size: 0.84rem;
            color: var(--text-muted);
            margin: 0;
        }

        .field-error {
            padding: 10px 12px;
            border-radius: 10px;
            background: var(--rose-bg);
            border: 1px solid var(--rose-bd);
            color: var(--rose-fg);
            font-size: 0.88rem;
            font-weight: 600;
        }

        .button-row {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            min-block-size: 48px;
            padding: 11px 20px;
            border-radius: 12px;
            font: inherit;
            font-size: 0.95rem;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
            border: 1px solid transparent;
            transition: background 0.15s ease, border-color 0.15s ease, transform 0.1s ease;
        }

        .btn:disabled {
            opacity: 0.65;
            cursor: not-allowed;
        }

        .btn-primary {
            background: var(--primary);
            color: #ffffff;
        }

        .btn-primary:hover:not(:disabled) {
            background: var(--primary-dark);
        }

        .btn-secondary {
            background: var(--surface);
            border-color: var(--border-strong);
            color: var(--text);
        }

        .btn-secondary:hover:not(:disabled) {
            background: #f1f5f9;
        }

        /* Progress Bar */
        .progress-panel {
            margin-top: 22px;
            padding: 16px;
            border-radius: 14px;
            background: #eef2ff;
            border: 1px solid #c7d2fe;
        }

        .progress-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            margin-bottom: 8px;
            font-size: 0.9rem;
            font-weight: 700;
            color: var(--primary-dark);
        }

        .progress-bar-native {
            width: 100%;
            height: 10px;
            border-radius: 999px;
            accent-color: var(--primary);
        }

        /* Results */
        .results-panel {
            margin-top: 26px;
        }

        .summary-banner {
            padding: 18px 20px;
            border-radius: 14px;
            background: #f8fafc;
            border: 1px solid var(--border);
            margin-bottom: 16px;
        }

        .summary-banner h2 {
            margin: 0 0 10px;
            font-size: 1.25rem;
            color: var(--text);
        }

        .summary-pills {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .summary-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 11px;
            border-radius: 999px;
            font-size: 0.82rem;
            font-weight: 700;
            border: 1px solid transparent;
        }

        .pill-verified { background: var(--emerald-bg); border-color: var(--emerald-bd); color: var(--emerald-fg); }
        .pill-doubtful { background: var(--amber-bg); border-color: var(--amber-bd); color: var(--amber-fg); }
        .pill-unverified { background: var(--rose-bg); border-color: var(--rose-bd); color: var(--rose-fg); }
        .pill-unavailable { background: var(--slate-bg); border-color: var(--slate-bd); color: var(--slate-fg); }

        .results-list {
            list-style: none;
            margin: 0;
            padding: 0;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .result-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 16px 18px;
            border-left-width: 5px;
        }

        .result-card--verified { border-left-color: #10b981; }
        .result-card--doubtful { border-left-color: #f59e0b; }
        .result-card--unverified { border-left-color: #e11d48; }
        .result-card--unavailable { border-left-color: #64748b; }

        .result-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 8px;
        }

        .result-idx {
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.78rem;
            font-weight: 700;
            color: var(--text-muted);
        }

        .result-badge-group {
            display: flex;
            align-items: center;
            gap: 6px;
            flex-wrap: wrap;
        }

        .source-chip {
            font-size: 0.74rem;
            font-family: 'JetBrains Mono', monospace;
            padding: 2px 8px;
            border-radius: 999px;
            background: var(--slate-bg);
            color: var(--slate-fg);
            font-weight: 600;
        }

        .result-raw {
            margin: 0 0 10px;
            font-size: 0.94rem;
            color: var(--text);
            font-weight: 600;
            overflow-wrap: anywhere;
        }

        .result-detail {
            padding: 10px 12px;
            border-radius: 10px;
            background: #f8fafc;
            border: 1px solid var(--border);
            font-size: 0.86rem;
            color: var(--text-muted);
            overflow-wrap: anywhere;
        }

        .result-detail--warning {
            background: var(--amber-bg);
            border-color: var(--amber-bd);
            color: var(--amber-fg);
        }

        .result-detail--danger {
            background: var(--rose-bg);
            border-color: var(--rose-bd);
            color: var(--rose-fg);
        }

        .result-detail-title {
            font-weight: 700;
            color: var(--text);
            margin-bottom: 3px;
        }

        .result-link {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            margin-top: 6px;
            color: var(--primary);
            font-weight: 700;
            text-decoration: underline;
            text-underline-offset: 2px;
            word-break: break-all;
        }

        /* CTA Conversion Card */
        .cta-card {
            margin-top: 24px;
            padding: 24px 22px;
            border-radius: 16px;
            background: linear-gradient(135deg, #1e1b4b 0%, #312e81 60%, #4338ca 100%);
            color: #ffffff;
            display: flex;
            flex-direction: column;
            gap: 14px;
        }

        .cta-card h3 {
            margin: 0;
            font-size: 1.2rem;
            line-height: 1.3;
        }

        .cta-card p {
            margin: 0;
            color: #e0e7ff;
            font-size: 0.94rem;
            max-width: 68ch;
        }

        .cta-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            align-items: center;
        }

        .btn-cta-white {
            background: #ffffff;
            color: #1e1b4b;
        }

        .btn-cta-white:hover {
            background: #eef2ff;
        }

        .btn-cta-ghost {
            background: rgba(255, 255, 255, 0.14);
            color: #ffffff;
            border-color: rgba(255, 255, 255, 0.35);
        }

        .btn-cta-ghost:hover {
            background: rgba(255, 255, 255, 0.22);
        }

        .site-footer {
            max-width: 960px;
            margin: 0 auto;
            padding: 16px 20px 36px;
            border-top: 1px solid var(--border);
            font-size: 0.84rem;
            color: var(--text-muted);
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            gap: 10px;
        }

        .site-footer a {
            color: var(--primary);
            font-weight: 600;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <header class="site-header">
        <div class="header-inner">
            <a href="<?= base_url('/') ?>" class="brand-link">
                <img src="<?= base_url('images/logo.png') ?>" alt="AR Tilik" width="32" height="32">
                <span>AR Tilik</span>
            </a>

            <nav class="cek-desktop-nav" aria-label="Navigasi Utama">
                <ul class="cek-nav-list">
                    <li><a href="<?= base_url('/') ?>">Beranda</a></li>
                    <li><a href="<?= base_url('fitur') ?>">Fitur</a></li>
                    <li><a href="<?= base_url('ojs') ?>">OJS</a></li>
                    <li><a href="<?= base_url('harga') ?>">Harga</a></li>
                    <li><a href="<?= base_url('cek-sitasi') ?>" aria-current="page">Cek Sitasi Gratis</a></li>
                    <li><a href="<?= base_url('tentang') ?>">Tentang</a></li>
                    <li><a href="<?= base_url('faq') ?>">FAQ</a></li>
                </ul>
            </nav>

            <div class="header-actions">
                <?php if (! empty($currentUser)) : ?>
                    <a href="<?= base_url('dashboard') ?>" class="nav-pill nav-pill-primary">Dashboard</a>
                <?php else : ?>
                    <a href="<?= base_url('/?utm_source=cek-sitasi&utm_medium=nav&utm_campaign=citation_checker&auth=register') ?>" class="nav-pill nav-pill-primary">Mulai Gratis</a>
                <?php endif; ?>
                <button type="button" id="cekMenuToggle" class="cek-menu-toggle" aria-expanded="false" aria-controls="cekMobilePanel" aria-label="Buka Menu Navigasi">
                    &#9776;
                </button>
            </div>
        </div>

        <nav id="cekMobilePanel" class="cek-mobile-panel" aria-label="Menu Seluler" hidden>
            <ul>
                <li><a href="<?= base_url('/') ?>">Beranda <span aria-hidden="true">&rsaquo;</span></a></li>
                <li><a href="<?= base_url('fitur') ?>">Fitur <span aria-hidden="true">&rsaquo;</span></a></li>
                <li><a href="<?= base_url('ojs') ?>">OJS <span aria-hidden="true">&rsaquo;</span></a></li>
                <li><a href="<?= base_url('harga') ?>">Harga <span aria-hidden="true">&rsaquo;</span></a></li>
                <li><a href="<?= base_url('cek-sitasi') ?>" aria-current="page">Cek Sitasi Gratis <span aria-hidden="true">&rsaquo;</span></a></li>
                <li><a href="<?= base_url('tentang') ?>">Tentang <span aria-hidden="true">&rsaquo;</span></a></li>
                <li><a href="<?= base_url('faq') ?>">FAQ <span aria-hidden="true">&rsaquo;</span></a></li>
            </ul>
        </nav>
    </header>

    <main id="main-content" class="page-main">
        <div class="hero-box">
            <div class="tool-kicker">Alat Gratis Tanpa Login &bull; Crossref &amp; OpenAlex</div>
            <h1>Cek Keberadaan Sitasi &amp; Daftar Pustaka</h1>
            <p class="hero-sub">
                Tempel daftar pustaka atau DOI Anda untuk memastikan tiap rujukan benar-benar tercatat di pangkalan data ilmiah Crossref dan OpenAlex &mdash; lengkap dengan tautan sumber dan kandidat terdekat bila ada perbedaan tahun atau penulis.
            </p>
        </div>

        <div class="notice-strip">
            <div class="notice-card">
                <span aria-hidden="true">🔒</span>
                <div><strong>Daftar pustaka yang Anda tempel tidak disimpan.</strong> Hasil hanya tampil langsung di layar Anda tanpa tautan publik dan tidak memotong kuota cek naskah akun.</div>
            </div>
            <div class="notice-card">
                <span aria-hidden="true">⚖️</span>
                <div><strong>Pemeriksaan ini hanya mengecek keberadaan sitasi di basis data yang dirujuk, bukan menilai kebenaran isi naskah.</strong> Status tidak ditemukan bukan bukti otomatis fabrikasi &mdash; periksa manual sebelum menyimpulkan.</div>
            </div>
        </div>

        <section class="tool-card" aria-labelledby="citation-form-heading">
            <div class="tool-meta-bar">
                <span id="citation-form-heading" style="font-weight: 700; color: var(--text);">Tempel Daftar Pustaka Anda</span>
                <span id="dailyQuotaBadge" class="quota-pill <?= ((int) ($quota['remaining'] ?? 5) <= 0) ? 'quota-exhausted' : '' ?>">
                    <span>Sisa kuota hari ini: <strong id="quotaRemainingNum"><?= (int) ($quota['remaining'] ?? 5) ?></strong>/<?= (int) ($quota['limit'] ?? 5) ?> pemeriksaan (maks 10 sitasi/cek)</span>
                </span>
            </div>

            <form id="citationCheckForm" method="POST" action="<?= base_url('api/v1/citation-check') ?>" novalidate>
                <div class="field-group">
                    <div class="field-label-row">
                        <label for="citationsInput" class="field-label">Daftar Pustaka atau DOI (satu entri per baris atau satu blok teks) <span aria-hidden="true" style="color:#be123c;">*</span></label>
                        <span id="entryCountPill" class="entry-counter">0 / 10 entri</span>
                    </div>
                    <textarea
                        id="citationsInput"
                        name="citations"
                        class="citation-textarea"
                        required
                        maxlength="15000"
                        data-error="citationsError"
                        aria-invalid="false"
                        aria-describedby="citation-format-hint citation-privacy-note"
                        placeholder="Contoh:&#10;Vaswani, A., Shazeer, N., Parmar, N., Uszkoreit, J., Jones, L., Gomez, A. N., Kaiser, L., &amp; Polosukhin, I. (2017). Attention is all you need. NeurIPS.&#10;LeCun, Y., Bengio, Y., &amp; Hinton, G. (2015). Deep learning. Nature, 521(7553), 436-444. https://doi.org/10.1038/nature14539"
                    ></textarea>
                    <p id="citation-format-hint" class="field-hint">
                        Mendukung gaya APA, IEEE, Harvard, Chicago, maupun daftar DOI langsung (mis. <code>10.1038/nature14539</code>). Maksimal 10 sitasi per cek.
                    </p>
                    <p id="citation-privacy-note" class="field-hint">
                        Daftar pustaka yang Anda tempel tidak disimpan. Log server hanya mencatat jumlah pemeriksaan harian.
                    </p>
                    <div id="citationsError" class="field-error" hidden></div>
                </div>

                <div class="button-row">
                    <button type="submit" id="submitCheckBtn" class="btn btn-primary" <?= ((int) ($quota['remaining'] ?? 5) <= 0) ? 'disabled' : '' ?>>
                        Periksa
                    </button>
                    <button type="button" id="loadSampleBtn" class="btn btn-secondary">
                        Muat Contoh Uji (Campuran)
                    </button>
                    <button type="button" id="clearInputBtn" class="btn btn-secondary">
                        Bersihkan
                    </button>
                </div>
            </form>

            <div id="progressPanel" class="progress-panel" hidden>
                <div class="progress-head">
                    <span id="progressLabel">Menyiapkan pemeriksaan ke Crossref &amp; OpenAlex...</span>
                    <span id="progressCount" style="font-family: 'JetBrains Mono', monospace;">0 / 0</span>
                </div>
                <progress id="checkProgressBar" class="progress-bar-native" value="0" max="10"></progress>
            </div>

            <div id="resultsPanel" class="results-panel" hidden>
                <div class="summary-banner">
                    <h2 id="summaryHeadline" tabindex="-1">Hasil Pemeriksaan Sitasi</h2>
                    <div id="summaryPills" class="summary-pills"></div>
                </div>

                <ol id="resultsList" class="results-list" role="list"></ol>
            </div>

            <div id="ctaPanel" class="cta-card" hidden>
                <div>
                    <h3>Ingin Memeriksa Kesepadanan &amp; Audit Sitasi Naskah Lengkap?</h3>
                    <p>
                        Alat ini hanya memeriksa keberadaan entri daftar pustaka. AR Tilik memeriksa keseluruhan isi naskah terhadap lebih dari satu dekade literatur ilmiah dengan Mesin Tiga Lensa (5-Gram, TF-IDF, Semantik), audit sitasi isi vs pustaka, dan sertifikat ber-QR Code.
                    </p>
                </div>
                <div class="cta-actions">
                    <a href="<?= base_url('/?utm_source=cek-sitasi&utm_medium=cta&utm_campaign=citation_checker&auth=register') ?>" class="btn btn-cta-white">
                        Periksa naskah lengkap dengan AR Tilik &mdash; gratis 3 cek/minggu
                    </a>
                    <button type="button" id="checkAgainBtn" class="btn btn-cta-ghost">
                        Periksa lagi
                    </button>
                </div>
            </div>
        </section>

        <div id="liveAnnouncer" class="visually-hidden" aria-live="polite"></div>
    </main>

    <footer class="site-footer">
        <span>AR Tilik &copy; <?= date('Y') ?> &bull; Pemeriksaan sitasi tidak menyimpan teks Anda.</span>
        <span>
            <a href="<?= base_url('/') ?>">Beranda</a> &bull;
            <a href="<?= base_url('fitur') ?>">Fitur</a> &bull;
            <a href="<?= base_url('ojs') ?>">OJS</a> &bull;
            <a href="<?= base_url('harga') ?>">Harga</a> &bull;
            <a href="<?= base_url('faq') ?>">FAQ</a> &bull;
            <a href="<?= base_url('benchmark') ?>">Benchmark</a> &bull;
            <a href="<?= base_url('verify') ?>">Verifikasi QR</a> &bull;
            <a href="<?= base_url('privacy') ?>">Privasi</a> &bull;
            <a href="https://wa.me/6285138862032" target="_blank" rel="noopener noreferrer">WA 0851 3886 2032</a> &bull;
            <a href="mailto:tilik@arsetontong.top">tilik@arsetontong.top</a>
        </span>
    </footer>

    <script>
        (function() {
            const MAX_ENTRIES = 10;
            const ENDPOINT = <?= json_encode(base_url('api/v1/citation-check')) ?>;
            const UNVERIFIED_MANDATORY = 'Tidak ditemukan di Crossref dan OpenAlex. Bisa berarti sitasi fabrikasi, salah ketik, atau sumber tidak terindeks — periksa manual sebelum menyimpulkan.';

            const form = document.getElementById('citationCheckForm');
            const textarea = document.getElementById('citationsInput');
            const errorEl = document.getElementById('citationsError');
            const entryCountPill = document.getElementById('entryCountPill');
            const submitBtn = document.getElementById('submitCheckBtn');
            const loadSampleBtn = document.getElementById('loadSampleBtn');
            const clearInputBtn = document.getElementById('clearInputBtn');
            const progressPanel = document.getElementById('progressPanel');
            const progressLabel = document.getElementById('progressLabel');
            const progressCount = document.getElementById('progressCount');
            const progressBar = document.getElementById('checkProgressBar');
            const resultsPanel = document.getElementById('resultsPanel');
            const summaryHeadline = document.getElementById('summaryHeadline');
            const summaryPills = document.getElementById('summaryPills');
            const resultsList = document.getElementById('resultsList');
            const ctaPanel = document.getElementById('ctaPanel');
            const checkAgainBtn = document.getElementById('checkAgainBtn');
            const quotaRemainingNum = document.getElementById('quotaRemainingNum');
            const dailyQuotaBadge = document.getElementById('dailyQuotaBadge');
            const liveAnnouncer = document.getElementById('liveAnnouncer');

            let isSubmitting = false;

            function setFieldError(message) {
                const ids = (textarea.getAttribute('aria-describedby') || '')
                    .split(/\s+/)
                    .filter((id) => id && id !== 'citationsError');
                if (message) {
                    errorEl.textContent = message;
                    errorEl.hidden = false;
                    textarea.setAttribute('aria-invalid', 'true');
                    textarea.classList.add('user-invalid-fallback');
                    ids.push('citationsError');
                    liveAnnouncer.textContent = message;
                } else {
                    errorEl.textContent = '';
                    errorEl.hidden = true;
                    textarea.setAttribute('aria-invalid', 'false');
                    textarea.classList.remove('user-invalid-fallback');
                }
                if (ids.length) {
                    textarea.setAttribute('aria-describedby', ids.join(' '));
                }
            }

            // Estimasi jumlah entri di sisi klien agar pengguna mendapat pratinjau instan sebelum mengirim
            function estimateEntries(raw) {
                const text = (raw || '').replace(/\r\n?/g, '\n').trim();
                if (!text) return 0;
                const headingRe = /^(?:[0-9.\s]*\b(?:daftar\s+pustaka|references?|bibliography|bibliografi|daftar\s+referensi|kepustakaan|rujukan|works\s+cited)\b)\s*:?\s*$/i;
                const paras = text.split(/\n\s*\n+/);
                let count = 0;
                for (const p of paras) {
                    const lines = p.split('\n').map((s) => s.trim()).filter((s) => s && !headingRe.test(s));
                    let buf = [];
                    for (const ln of lines) {
                        const isNumStart = /^(?:\[\d+\]|\(\d+\)|\d{1,3}[\.\)])\s+\S/.test(ln);
                        const isCont = /^[a-z]/.test(ln) || /^(?:https?:\/\/|www\.|doi:|available\b|retrieved\b|diakses\b|in\s|dalam\s|(?:jurnal|journal|proceedings|prosiding|ieee|acm|springer|elsevier|wiley|vol\.|volume|no\.|pp\.|hal\.|hlm\.|ed\.|penerbit|press)\b|[\d\s\-–,;:\.\(\)]+$)/i.test(ln);
                        const bufText = buf.join(' ');
                        const bufHasDoi = /10\.\d{4,9}\/\S+/i.test(bufText);
                        const bufHasYear = /\b(?:19|20)\d{2}\b/.test(bufText);
                        const bufDoiOnly = /^(?:https?:\/\/(?:dx\.)?doi\.org\/|doi:\s*|10\.\d{4,9}\/)/i.test(bufText) && bufText.split(/\s+/).length <= 2;
                        const isBareDoi = /^10\.\d{4,9}\/\S+$/.test(ln);
                        const isDoiLine = /^(?:https?:\/\/(?:dx\.)?doi\.org\/|doi:\s*|10\.\d{4,9}\/)/i.test(ln);
                        const startsAuthorYear = /^[A-ZÀ-ÖØ-Ý][^\n]{1,65}\((?:(?:19|20)\d{2}[a-z]?|n\.d\.|t\.th\.)\)/.test(ln) || /^[A-ZÀ-ÖØ-Ý][a-zA-Z\-']+\s*,\s*[A-Z]\./.test(ln);

                        let splitHere = false;
                        if (buf.length > 0) {
                            if (isNumStart) splitHere = true;
                            else if (isBareDoi && (bufHasDoi || bufDoiOnly || (bufHasYear && bufText.length >= 25 && /\.$/.test(bufText)))) splitHere = true;
                            else if (isDoiLine) splitHere = bufHasDoi || bufDoiOnly;
                            else if (!isCont && startsAuthorYear && (bufHasYear || bufHasDoi || bufText.length >= 25)) splitHere = true;
                            else if (!isCont && (bufHasYear || bufHasDoi) && /(?:\.|\d|https?:\/\/\S+|10\.\d{4,9}\/\S+)$/.test(bufText) && /\b(?:19|20)\d{2}\b|10\.\d{4,9}\/|["“]/.test(ln)) splitHere = true;
                        }
                        if (splitHere) {
                            if (bufText.length >= 6) count += 1;
                            buf = [ln];
                        } else {
                            buf.push(ln);
                        }
                    }
                    if (buf.join(' ').trim().length >= 6) count += 1;
                }
                return count;
            }

            function updateCounter() {
                const count = estimateEntries(textarea.value);
                entryCountPill.textContent = count > MAX_ENTRIES
                    ? `⚠ ${count} / ${MAX_ENTRIES} entri (melebihi batas)`
                    : `${count} / ${MAX_ENTRIES} entri`;
                entryCountPill.classList.toggle('is-over', count > MAX_ENTRIES);
            }

            textarea.addEventListener('input', () => {
                updateCounter();
                if (textarea.getAttribute('aria-invalid') === 'true') {
                    const c = estimateEntries(textarea.value);
                    if (c > 0 && c <= MAX_ENTRIES) {
                        setFieldError('');
                    }
                }
            });

            textarea.addEventListener('blur', () => {
                if (textarea.value.trim() === '') return;
                const c = estimateEntries(textarea.value);
                if (c > MAX_ENTRIES) {
                    setFieldError(`Maksimal ${MAX_ENTRIES} sitasi per pemeriksaan. Terdeteksi sekitar ${c} entri.`);
                }
            });

            loadSampleBtn.addEventListener('click', () => {
                textarea.value = [
                    'Vaswani, A., Shazeer, N., Parmar, N., Uszkoreit, J., Jones, L., Gomez, A. N., Kaiser, L., & Polosukhin, I. (2017). Attention is all you need. Advances in Neural Information Processing Systems, 30.',
                    'LeCun, Y., Bengio, Y., & Hinton, G. (2015). Deep learning. Nature, 521(7553), 436-444. https://doi.org/10.1038/nature14539',
                    'Devlin, J., Chang, M. W., Lee, K., & Toutanova, K. (2023). BERT: Pre-training of deep bidirectional transformers for language understanding. NAACL-HLT.',
                    'Susanto, R. (2017). Attention is all you need. Journal of Artificial Intelligence.',
                    'Pratama, D., & Kusuma, A. (2022). Efektivitas pembelajaran berbasis digital di sekolah menengah pertama. Jurnal Inovasi Pendidikan Indonesia, 8(3), 201-215.'
                ].join('\n');
                setFieldError('');
                updateCounter();
                textarea.focus();
            });

            clearInputBtn.addEventListener('click', () => {
                textarea.value = '';
                setFieldError('');
                updateCounter();
                resultsPanel.hidden = true;
                ctaPanel.hidden = true;
                progressPanel.hidden = true;
                resultsList.replaceChildren();
                textarea.focus();
            });

            checkAgainBtn.addEventListener('click', () => {
                resultsPanel.hidden = true;
                ctaPanel.hidden = true;
                progressPanel.hidden = true;
                resultsList.replaceChildren();
                textarea.focus();
            });

            function updateQuotaDisplay(quota) {
                if (!quota) return;
                const rem = Number(quota.remaining ?? 0);
                quotaRemainingNum.textContent = String(rem);
                dailyQuotaBadge.classList.toggle('quota-exhausted', rem <= 0);
                if (rem <= 0) {
                    submitBtn.disabled = true;
                }
            }

            function statusMeta(status) {
                switch (status) {
                    case 'verified':
                        return { cls: 'verified', pillCls: 'pill-verified', icon: '✓', label: 'Terverifikasi' };
                    case 'doubtful':
                        return { cls: 'doubtful', pillCls: 'pill-doubtful', icon: '⚠', label: 'Meragukan' };
                    case 'unavailable':
                        return { cls: 'unavailable', pillCls: 'pill-unavailable', icon: '⏱', label: 'Tidak dapat diperiksa saat ini' };
                    default:
                        return { cls: 'unverified', pillCls: 'pill-unverified', icon: '?', label: 'Tidak terverifikasi' };
                }
            }

            function renderResultCard(item) {
                const meta = statusMeta(item.status);
                const li = document.createElement('li');
                li.className = `result-card result-card--${meta.cls}`;
                li.dataset.entryIndex = String(item.index || 0);

                const top = document.createElement('div');
                top.className = 'result-top';

                const idxSpan = document.createElement('span');
                idxSpan.className = 'result-idx';
                idxSpan.textContent = `Sitasi #${item.index || ''}`;
                top.appendChild(idxSpan);

                const badgeGroup = document.createElement('div');
                badgeGroup.className = 'result-badge-group';

                const badge = document.createElement('span');
                badge.className = `summary-pill ${meta.pillCls}`;
                badge.textContent = `${meta.icon} ${item.status_label || meta.label}`;
                badgeGroup.appendChild(badge);

                if (item.source) {
                    const srcChip = document.createElement('span');
                    srcChip.className = 'source-chip';
                    srcChip.textContent = item.source === 'crossref' ? 'Crossref' : (item.source === 'openalex' ? 'OpenAlex' : item.source);
                    badgeGroup.appendChild(srcChip);
                }
                if (item.cached) {
                    const cacheChip = document.createElement('span');
                    cacheChip.className = 'source-chip';
                    cacheChip.textContent = 'Cache';
                    badgeGroup.appendChild(cacheChip);
                }

                top.appendChild(badgeGroup);
                li.appendChild(top);

                const rawP = document.createElement('p');
                rawP.className = 'result-raw';
                rawP.textContent = item.raw_text || '';
                li.appendChild(rawP);

                const detail = document.createElement('div');
                if (item.status === 'verified') {
                    detail.className = 'result-detail';
                    const titleDiv = document.createElement('div');
                    titleDiv.className = 'result-detail-title';
                    titleDiv.textContent = item.matched_title || 'Tercatat pada basis data rujukan';
                    detail.appendChild(titleDiv);

                    const metaParts = [];
                    if (item.matched_authors) metaParts.push(item.matched_authors);
                    if (item.matched_year) metaParts.push(String(item.matched_year));
                    if (metaParts.length) {
                        const metaDiv = document.createElement('div');
                        metaDiv.textContent = metaParts.join(' • ');
                        detail.appendChild(metaDiv);
                    }
                    if (item.source_url) {
                        const a = document.createElement('a');
                        a.className = 'result-link';
                        a.href = item.source_url;
                        a.target = '_blank';
                        a.rel = 'noopener noreferrer';
                        a.textContent = `Buka sumber (${item.doi || item.source_url}) ↗`;
                        detail.appendChild(a);
                    }
                } else if (item.status === 'doubtful') {
                    detail.className = 'result-detail result-detail--warning';
                    const reasonDiv = document.createElement('div');
                    reasonDiv.className = 'result-detail-title';
                    reasonDiv.textContent = item.reason || 'Cocok sebagian — periksa kesesuaian judul, penulis, dan tahun.';
                    detail.appendChild(reasonDiv);

                    if (item.matched_title) {
                        const candDiv = document.createElement('div');
                        const candParts = [`Kandidat terdekat: ${item.matched_title}`];
                        if (item.matched_authors) candParts.push(item.matched_authors);
                        if (item.matched_year) candParts.push(String(item.matched_year));
                        candDiv.textContent = candParts.join(' — ');
                        detail.appendChild(candDiv);
                    }
                    if (item.source_url) {
                        const a = document.createElement('a');
                        a.className = 'result-link';
                        a.href = item.source_url;
                        a.target = '_blank';
                        a.rel = 'noopener noreferrer';
                        a.textContent = `Lihat kandidat sumber (${item.doi || item.source_url}) ↗`;
                        detail.appendChild(a);
                    }
                } else if (item.status === 'unavailable') {
                    detail.className = 'result-detail';
                    detail.textContent = item.reason || 'Layanan Crossref atau OpenAlex sedang lambat saat ini. Silakan ulangi beberapa saat lagi.';
                } else {
                    detail.className = 'result-detail result-detail--danger';
                    detail.textContent = item.reason || UNVERIFIED_MANDATORY;
                }

                li.appendChild(detail);
                return li;
            }

            function insertCardOrdered(item) {
                const newCard = renderResultCard(item);
                const idx = Number(item.index || 0);
                const existing = Array.from(resultsList.children);
                const after = existing.find((node) => Number(node.dataset.entryIndex || 0) > idx);
                if (after) {
                    resultsList.insertBefore(newCard, after);
                } else {
                    resultsList.appendChild(newCard);
                }
            }

            function renderSummary(data) {
                summaryHeadline.textContent = data.summary_text || `${data.verified_count || 0} dari ${data.total_entries || 0} sitasi terverifikasi`;
                summaryPills.replaceChildren();

                const counts = [
                    { n: Number(data.verified_count || 0), cls: 'pill-verified', label: 'Terverifikasi', showZero: true },
                    { n: Number(data.doubtful_count || 0), cls: 'pill-doubtful', label: 'Meragukan', showZero: false },
                    { n: Number(data.unverified_count || 0), cls: 'pill-unverified', label: 'Tidak terverifikasi', showZero: true },
                    { n: Number(data.unavailable_count || 0), cls: 'pill-unavailable', label: 'Tidak dapat diperiksa saat ini', showZero: false },
                ];

                counts.forEach((c) => {
                    if (c.n === 0 && !c.showZero) return;
                    const pill = document.createElement('span');
                    pill.className = `summary-pill ${c.cls}`;
                    pill.textContent = `${c.n} ${c.label}`;
                    summaryPills.appendChild(pill);
                });

                liveAnnouncer.textContent = `Pemeriksaan selesai: ${summaryHeadline.textContent}`;
            }

            form.addEventListener('submit', async (e) => {
                e.preventDefault();
                if (isSubmitting) return;

                const rawText = textarea.value.trim();
                if (!rawText) {
                    setFieldError('Tempel minimal satu sitasi atau DOI terlebih dahulu.');
                    textarea.focus();
                    return;
                }
                const estCount = estimateEntries(rawText);
                if (estCount > MAX_ENTRIES) {
                    setFieldError(`Maksimal ${MAX_ENTRIES} sitasi per pemeriksaan. Anda memasukkan sekitar ${estCount} entri.`);
                    textarea.focus();
                    return;
                }

                setFieldError('');
                isSubmitting = true;
                submitBtn.disabled = true;
                submitBtn.textContent = 'Memeriksa...';

                resultsList.replaceChildren();
                resultsPanel.hidden = false;
                summaryHeadline.textContent = 'Sedang memeriksa sitasi...';
                summaryPills.replaceChildren();
                ctaPanel.hidden = true;

                progressPanel.hidden = false;
                progressBar.value = 0;
                progressBar.max = Math.max(1, estCount);
                progressCount.textContent = `0 / ${Math.max(1, estCount)}`;
                progressLabel.textContent = 'Menghubungkan ke Crossref & OpenAlex...';
                liveAnnouncer.textContent = 'Pemeriksaan sitasi dimulai.';

                try {
                    const res = await fetch(ENDPOINT, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/x-ndjson, application/json',
                        },
                        body: JSON.stringify({ citations: rawText, stream: true }),
                    });

                    const contentType = res.headers.get('Content-Type') || '';
                    if (!res.ok || contentType.includes('application/json')) {
                        const json = await res.json();
                        if (json.quota) updateQuotaDisplay(json.quota);

                        if (!res.ok || json.status === 'error') {
                            progressPanel.hidden = true;
                            resultsPanel.hidden = true;
                            setFieldError(json.message || 'Gagal memeriksa sitasi. Silakan periksa masukan Anda.');
                            textarea.focus();
                            return;
                        }

                        if (json.data) {
                            resultsList.replaceChildren();
                            (json.data.items || []).forEach(insertCardOrdered);
                            renderSummary(json.data);
                            progressPanel.hidden = true;
                            ctaPanel.hidden = false;
                            summaryHeadline.focus();
                        }
                        return;
                    }

                    // Konsumsi NDJSON stream agar progres tampil per sitasi secara nyata
                    const reader = res.body.getReader();
                    const decoder = new TextDecoder('utf-8');
                    let buffer = '';
                    let doneReceived = false;

                    function handleNdjsonLine(line) {
                        const clean = line.trim();
                        if (!clean) return;
                        let msg;
                        try {
                            msg = JSON.parse(clean);
                        } catch (_) {
                            return;
                        }
                        if (msg.event === 'quota' && msg.quota) {
                            updateQuotaDisplay(msg.quota);
                        } else if (msg.event === 'start') {
                            const total = Number(msg.total || 1);
                            progressBar.max = total;
                            progressBar.value = 0;
                            progressCount.textContent = `0 / ${total}`;
                            progressLabel.textContent = `Memeriksa ${total} sitasi...`;
                        } else if (msg.event === 'progress') {
                            const completed = Number(msg.completed || 0);
                            const total = Number(msg.total || 1);
                            progressBar.max = total;
                            progressBar.value = completed;
                            progressCount.textContent = `${completed} / ${total}`;
                            progressLabel.textContent = `Memeriksa sitasi ${completed} dari ${total}...`;
                            if (msg.item) {
                                insertCardOrdered(msg.item);
                            }
                        } else if (msg.event === 'done' && msg.data) {
                            doneReceived = true;
                            if (resultsList.children.length === 0 && Array.isArray(msg.data.items)) {
                                msg.data.items.forEach(insertCardOrdered);
                            }
                            renderSummary(msg.data);
                            progressPanel.hidden = true;
                            ctaPanel.hidden = false;
                            summaryHeadline.focus();
                        } else if (msg.event === 'error') {
                            progressPanel.hidden = true;
                            resultsPanel.hidden = true;
                            setFieldError(msg.message || 'Terjadi gangguan saat memeriksa sitasi.');
                            textarea.focus();
                        }
                    }

                    while (true) {
                        const { value, done } = await reader.read();
                        if (done) break;
                        buffer += decoder.decode(value, { stream: true });
                        const lines = buffer.split('\n');
                        buffer = lines.pop() || '';
                        lines.forEach(handleNdjsonLine);
                    }
                    if (buffer.trim()) {
                        handleNdjsonLine(buffer);
                    }
                    if (!doneReceived) {
                        progressPanel.hidden = true;
                    }
                } catch (err) {
                    progressPanel.hidden = true;
                    resultsPanel.hidden = true;
                    setFieldError('Kendala koneksi jaringan saat memeriksa sitasi. Silakan coba kembali.');
                    textarea.focus();
                } finally {
                    isSubmitting = false;
                    submitBtn.textContent = 'Periksa';
                    if (Number(quotaRemainingNum.textContent || '0') > 0) {
                        submitBtn.disabled = false;
                    }
                }
            });

            const cekMenuBtn = document.getElementById('cekMenuToggle');
            const cekMobilePanel = document.getElementById('cekMobilePanel');
            if (cekMenuBtn && cekMobilePanel) {
                cekMenuBtn.addEventListener('click', () => {
                    const opening = cekMobilePanel.hidden;
                    cekMobilePanel.hidden = !opening;
                    cekMenuBtn.setAttribute('aria-expanded', opening ? 'true' : 'false');
                });
                document.addEventListener('keydown', (ev) => {
                    if (ev.key === 'Escape' && !cekMobilePanel.hidden) {
                        cekMobilePanel.hidden = true;
                        cekMenuBtn.setAttribute('aria-expanded', 'false');
                        cekMenuBtn.focus();
                    }
                });
            }

            updateCounter();
        })();
    </script>
</body>
</html>
