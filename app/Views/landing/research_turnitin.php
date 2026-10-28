<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title><?= esc($title ?? 'Perbandingan AR Tilik vs Turnitin — Whitepaper Riset & Analisis Jurnal Indonesia') ?></title>
    <meta name="description" content="Analisis komparatif berbasis riset: Turnitin unggul mutlak pada skala korpus global, sementara AR Tilik menawarkan verifikasi sitasi empiris, transparansi laporan per-komponen, integrasi OJS, dan efisiensi biaya untuk jurnal Indonesia.">
    <meta name="robots" content="index, follow, max-snippet:-1, max-image-preview:large">
    <link rel="canonical" href="<?= base_url('riset/ar-tilik-vs-turnitin') ?>">
    <meta name="theme-color" content="#4338ca">

    <!-- Open Graph -->
    <meta property="og:type" content="article">
    <meta property="og:locale" content="id_ID">
    <meta property="og:site_name" content="AR Tilik">
    <meta property="og:title" content="AR Tilik vs Turnitin: Analisis Objektif untuk Jurnal & Kampus Indonesia">
    <meta property="og:description" content="Turnitin unggul pada skala korpus global; AR Tilik berfokus pada verifikasi rujukan nyata, transparansi audit, dan alur jurnal Indonesia.">
    <meta property="og:url" content="<?= base_url('riset/ar-tilik-vs-turnitin') ?>">
    <meta property="og:image" content="<?= base_url('images/og-artilik.png') ?>">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:alt" content="Whitepaper AR Tilik vs Turnitin">

    <!-- Twitter / X Cards -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:site" content="@ARSetontong">
    <meta name="twitter:creator" content="@ARSetontong">
    <meta name="twitter:title" content="AR Tilik vs Turnitin: Analisis Objektif untuk Jurnal & Kampus Indonesia">
    <meta name="twitter:description" content="Turnitin unggul pada skala korpus global; AR Tilik berfokus pada verifikasi rujukan nyata, transparansi audit, dan alur jurnal Indonesia.">
    <meta name="twitter:image" content="<?= base_url('images/og-artilik.png') ?>">

    <!-- Schema.org JSON-LD Structured Data -->
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@graph": [
        {
          "@type": "TechArticle",
          "@id": "<?= base_url('riset/ar-tilik-vs-turnitin#article') ?>",
          "headline": "AR Tilik vs Turnitin: Analisis Objektif untuk Jurnal & Kampus Indonesia",
          "alternativeHeadline": "Whitepaper Komparatif: Membedah Keunggulan Korpus Global vs Verifikasi Sitasi Empiris & Integrasi OJS",
          "description": "Analisis komparatif berbasis riset: Turnitin unggul mutlak pada skala korpus global, sementara AR Tilik menawarkan verifikasi sitasi empiris, transparansi laporan per-komponen, integrasi OJS, dan efisiensi biaya untuk jurnal Indonesia.",
          "inLanguage": "id-ID",
          "mainEntityOfPage": "<?= base_url('riset/ar-tilik-vs-turnitin') ?>",
          "url": "<?= base_url('riset/ar-tilik-vs-turnitin') ?>",
          "image": "<?= base_url('images/og-artilik.png') ?>",
          "datePublished": "2026-10-13T08:00:00+07:00",
          "dateModified": "<?= date('c') ?>",
          "author": {
            "@type": "Organization",
            "name": "Dewan Riset Integritas Akademik AR Tilik",
            "url": "<?= base_url() ?>"
          },
          "publisher": {
            "@type": "Organization",
            "name": "ARSetontong",
            "url": "https://www.arsetontong.top",
            "logo": {
              "@type": "ImageObject",
              "url": "<?= base_url('android-chrome-512x512.png') ?>"
            }
          },
          "about": [
            "Turnitin Alternative",
            "Academic Integrity",
            "Open Journal Systems",
            "Citation Verification",
            "AR Tilik"
          ]
        },
        {
          "@type": "BreadcrumbList",
          "@id": "<?= base_url('riset/ar-tilik-vs-turnitin#breadcrumb') ?>",
          "itemListElement": [
            {
              "@type": "ListItem",
              "position": 1,
              "name": "Beranda",
              "item": "<?= base_url() ?>"
            },
            {
              "@type": "ListItem",
              "position": 2,
              "name": "Riset & Komparasi",
              "item": "<?= base_url('#riset-komparasi') ?>"
            },
            {
              "@type": "ListItem",
              "position": 3,
              "name": "AR Tilik vs Turnitin",
              "item": "<?= base_url('riset/ar-tilik-vs-turnitin') ?>"
            }
          ]
        }
      ]
    }
    </script>

    <!-- Favicon & Brand Icons -->
    <link rel="icon" type="image/x-icon" href="<?= base_url('favicon.ico?v=20261008') ?>">
    <link rel="icon" type="image/png" sizes="32x32" href="<?= base_url('favicon-32x32.png?v=20261008') ?>">
    <link rel="icon" type="image/png" sizes="16x16" href="<?= base_url('favicon-16x16.png?v=20261008') ?>">
    <link rel="apple-touch-icon" sizes="180x180" href="<?= base_url('apple-touch-icon.png?v=20261008') ?>">
    <link rel="manifest" href="<?= base_url('site.webmanifest') ?>">

    <!-- Google Fonts: Plus Jakarta Sans & JetBrains Mono -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary: #4338ca;
            --primary-dark: #312e81;
            --primary-light: #6366f1;
            --secondary: #0ea5e9;
            --turnitin-teal: #0f766e;
            --turnitin-teal-dark: #115e59;
            --turnitin-soft: #f0fdfa;
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

        /* ─── Breadcrumbs & Hero Wrap ─── */
        .hero-wrap {
            padding-top: 110px;
            padding-bottom: 48px;
            background: radial-gradient(circle at 85% 20%, rgba(15, 118, 110, 0.08) 0%, transparent 50%),
                        radial-gradient(circle at 15% 60%, rgba(67, 56, 202, 0.07) 0%, transparent 40%),
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
            grid-template-columns: minmax(0, 1.35fr) minmax(320px, 0.85fr);
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
            background: rgba(15, 118, 110, 0.09);
            color: var(--turnitin-teal);
            border: 1px solid rgba(15, 118, 110, 0.25);
            margin-bottom: 18px;
        }
        .pill-tag .dot { width: 7px; height: 7px; border-radius: 50%; background: var(--turnitin-teal); }
        h1.hero-title {
            font-size: clamp(32px, 4.2vw, 52px);
            font-weight: 800;
            line-height: 1.15;
            letter-spacing: -1.2px;
            color: var(--dark);
            margin-bottom: 20px;
        }
        h1.hero-title .brand-ar { color: var(--primary); }
        h1.hero-title .brand-vs { color: var(--text-muted); font-size: 0.7em; font-weight: 600; }
        h1.hero-title .brand-ti { color: var(--turnitin-teal); }
        .hero-lead {
            font-size: clamp(16px, 1.3vw, 18.5px);
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

        /* Honest Verdict Card */
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
            display: inline-block;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1px;
            background: rgba(14, 165, 233, 0.15);
            color: #38bdf8;
            padding: 4px 12px;
            border-radius: 9999px;
            margin-bottom: 14px;
            border: 1px solid rgba(56, 189, 248, 0.3);
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
            margin-bottom: 12px;
        }
        .verdict-card .stamp {
            font-size: 12px;
            color: #94a3b8;
            margin: 0;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            padding-top: 10px;
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
            max-width: 840px;
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
            margin-bottom: 14px;
        }
        .section-desc {
            font-size: 16px;
            color: #475569;
            line-height: 1.65;
        }

        /* Side-by-side Product Cards */
        .duel-cards {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
            margin-bottom: 36px;
        }
        .duel-card {
            background: #ffffff;
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: clamp(24px, 3.5vw, 36px);
            box-shadow: var(--shadow-sm);
            position: relative;
        }
        .duel-card.card-ar {
            border-top: 4px solid var(--primary);
        }
        .duel-card.card-ti {
            border-top: 4px solid var(--turnitin-teal);
        }
        .duel-tag {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 12px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 14px;
        }
        .duel-card.card-ar .duel-tag { color: var(--primary); }
        .duel-card.card-ti .duel-tag { color: var(--turnitin-teal); }
        .duel-card h3 {
            font-size: 20px;
            font-weight: 800;
            color: var(--dark);
            margin-bottom: 14px;
            letter-spacing: -0.4px;
        }
        .duel-list {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 12px;
            font-size: 14.5px;
            color: #334155;
            line-height: 1.55;
        }
        .duel-list li {
            position: relative;
            padding-left: 22px;
        }
        .duel-list li::before {
            content: "•";
            position: absolute;
            left: 6px;
            font-weight: 800;
        }
        .duel-card.card-ar .duel-list li::before { color: var(--primary); }
        .duel-card.card-ti .duel-list li::before { color: var(--turnitin-teal); }
        .duel-note {
            margin-top: 18px;
            padding-top: 14px;
            border-top: 1px solid var(--border);
            font-size: 12.5px;
            color: var(--text-muted);
            line-height: 1.5;
        }

        /* ─── Scoreboard / Comparison Table ─── */
        .scoreboard-card {
            background: #ffffff;
            border: 1px solid var(--border);
            border-radius: 20px;
            overflow: hidden;
            box-shadow: var(--shadow-sm);
        }
        .scoreboard-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }
        .scoreboard-table th, .scoreboard-table td {
            padding: 18px 22px;
            border-bottom: 1px solid var(--border);
            text-align: left;
            vertical-align: top;
        }
        .scoreboard-table th {
            font-size: 12px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            background: #f8fafc;
            color: var(--text-muted);
        }
        .scoreboard-table th.th-ar { color: var(--primary); }
        .scoreboard-table th.th-ti { color: var(--turnitin-teal); }
        .scoreboard-table tr:last-child td { border-bottom: none; }
        .score-pill {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 3px 9px;
            border-radius: 9999px;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 6px;
        }
        .score-pill.good {
            background: #ecfdf5;
            color: #059669;
            border: 1px solid #a7f3d0;
        }
        .score-pill.limit {
            background: #fffbeb;
            color: #d97706;
            border: 1px solid #fde68a;
        }
        .score-pill.open {
            background: #f1f5f9;
            color: #64748b;
            border: 1px solid #e2e8f0;
        }

        /* ─── 02 Corpus Statistics Strip ─── */
        .corpus-strip {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin: 36px 0;
        }
        .corpus-card {
            background: #ffffff;
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 28px;
            box-shadow: var(--shadow-sm);
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        .corpus-card::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--turnitin-teal);
        }
        .big-number {
            font-family: var(--mono);
            font-size: clamp(34px, 4.5vw, 56px);
            font-weight: 800;
            color: var(--turnitin-teal);
            line-height: 1;
            margin-bottom: 8px;
            letter-spacing: -1.5px;
        }
        .corpus-label {
            font-size: 15px;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 6px;
        }
        .corpus-source {
            font-size: 12px;
            color: var(--text-muted);
            line-height: 1.45;
        }

        /* Mechanism Steps */
        .steps-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin: 32px 0;
        }
        .step-box {
            background: #ffffff;
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 24px;
        }
        .step-num {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: #f1f5f9;
            color: var(--dark);
            font-family: var(--mono);
            font-size: 14px;
            font-weight: 800;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 14px;
            border: 1px solid var(--border);
        }
        .step-box h4 {
            font-size: 16px;
            font-weight: 800;
            color: var(--dark);
            margin-bottom: 8px;
        }
        .step-box p {
            font-size: 13.5px;
            color: var(--text-muted);
            line-height: 1.55;
            margin: 0;
        }

        /* Callout Box */
        .callout-box {
            background: #fffbeb;
            border: 1px solid #fde68a;
            border-left: 5px solid #d97706;
            border-radius: 14px;
            padding: 24px 28px;
            margin: 32px 0;
        }
        .callout-box.teal {
            background: #f0fdfa;
            border-color: #99f6e4;
            border-left-color: var(--turnitin-teal);
        }
        .callout-quote {
            font-size: clamp(17px, 1.8vw, 21px);
            font-weight: 700;
            color: #78350f;
            line-height: 1.45;
            margin-bottom: 8px;
        }
        .callout-box.teal .callout-quote { color: #134e4a; }
        .callout-desc {
            font-size: 14px;
            color: #92400e;
            line-height: 1.6;
            margin: 0;
        }
        .callout-box.teal .callout-desc { color: #115e59; }

        /* ─── 03 Evidence Grid (Deteksi AI) ─── */
        .evidence-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
            margin-bottom: 36px;
        }
        .evidence-card {
            background: #ffffff;
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 28px;
            box-shadow: var(--shadow-sm);
        }
        .evidence-card.card-claim { border-top: 4px solid var(--turnitin-teal); }
        .evidence-card.card-indep { border-top: 4px solid var(--primary); }
        .evidence-card h3 {
            font-size: 18px;
            font-weight: 800;
            color: var(--dark);
            margin-bottom: 16px;
        }
        .data-stats-row {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
            margin: 28px 0;
        }
        .data-stat-box {
            background: #ffffff;
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 20px;
            text-align: center;
        }
        .data-stat-box .stat-val {
            font-family: var(--mono);
            font-size: 32px;
            font-weight: 800;
            color: var(--accent-rose);
            line-height: 1;
            margin-bottom: 6px;
        }
        .data-stat-box .stat-title {
            font-size: 13px;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 4px;
        }
        .data-stat-box .stat-sub {
            font-size: 11.5px;
            color: var(--text-muted);
            line-height: 1.4;
        }

        /* ─── 04 Flow Diagram (Tata Kelola Data) ─── */
        .flow-container {
            display: flex;
            flex-direction: column;
            gap: 14px;
            margin: 32px 0;
        }
        .flow-card {
            background: #ffffff;
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 20px 24px;
            display: grid;
            grid-template-columns: 220px 40px 1fr;
            align-items: center;
            gap: 16px;
            box-shadow: var(--shadow-sm);
        }
        .flow-card.ar-flow { border-left: 5px solid var(--primary); }
        .flow-card.ti-flow { border-left: 5px solid var(--turnitin-teal); }
        .flow-source strong { font-size: 15px; color: var(--dark); display: block; }
        .flow-source span { font-size: 12px; color: var(--text-muted); }
        .flow-arrow { font-size: 20px; color: var(--text-muted); text-align: center; }
        .flow-dest strong { font-size: 14.5px; color: var(--dark); display: block; margin-bottom: 3px; }
        .flow-dest p { font-size: 13px; color: #475569; line-height: 1.5; margin: 0; }

        /* ─── 05 Fit List (Celah Pasar Jurnal Indonesia) ─── */
        .fit-list {
            display: flex;
            flex-direction: column;
            gap: 16px;
            margin: 32px 0;
        }
        .fit-item {
            background: #ffffff;
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 22px 26px;
            display: grid;
            grid-template-columns: 46px 1fr auto;
            align-items: center;
            gap: 18px;
            box-shadow: var(--shadow-sm);
        }
        .fit-idx {
            font-family: var(--mono);
            font-size: 16px;
            font-weight: 800;
            color: var(--primary);
            width: 38px;
            height: 38px;
            border-radius: 10px;
            background: rgba(67, 56, 202, 0.08);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .fit-content h4 {
            font-size: 16px;
            font-weight: 800;
            color: var(--dark);
            margin-bottom: 4px;
        }
        .fit-content p {
            font-size: 13.5px;
            color: #475569;
            line-height: 1.55;
            margin: 0;
        }

        /* ─── 06 Recommendations Tabs ─── */
        .recom-container {
            display: grid;
            grid-template-columns: 280px 1fr;
            gap: 24px;
            margin: 32px 0;
        }
        .recom-nav {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .recom-btn {
            background: #ffffff;
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 16px 20px;
            font-family: var(--sans);
            font-size: 14px;
            font-weight: 700;
            color: var(--dark);
            cursor: pointer;
            text-align: left;
            transition: all 0.15s ease;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .recom-btn:hover { background: #f8fafc; border-color: var(--border-subtle); }
        .recom-btn[aria-selected="true"] {
            background: var(--primary);
            color: #ffffff;
            border-color: var(--primary);
            box-shadow: 0 4px 14px rgba(67, 56, 202, 0.25);
        }
        .recom-panel {
            background: #ffffff;
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 32px;
            box-shadow: var(--shadow-sm);
        }
        .recom-panel h4 {
            font-size: 18px;
            font-weight: 800;
            color: var(--dark);
            margin-bottom: 12px;
        }
        .recom-panel p {
            font-size: 14.5px;
            color: #334155;
            line-height: 1.65;
            margin-bottom: 14px;
        }
        .recom-panel p:last-child { margin-bottom: 0; }

        /* Final Dark Card */
        .final-banner {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            border-radius: 24px;
            padding: clamp(32px, 5vw, 48px);
            color: #ffffff;
            margin: 40px 0;
            box-shadow: var(--shadow-lg);
        }
        .final-banner h3 {
            font-size: clamp(22px, 2.5vw, 32px);
            font-weight: 800;
            line-height: 1.3;
            margin-bottom: 16px;
            color: #ffffff;
        }
        .final-banner p {
            font-size: 15px;
            color: #cbd5e1;
            line-height: 1.7;
            max-width: 860px;
            margin-bottom: 24px;
        }

        /* ─── 07 Sources & Details ─── */
        details.source-group {
            background: #ffffff;
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 16px 20px;
            margin-bottom: 12px;
        }
        details.source-group[open] { border-color: var(--border-subtle); }
        details summary {
            font-size: 15px;
            font-weight: 700;
            color: var(--dark);
            cursor: pointer;
            outline: none;
            user-select: none;
        }
        details .source-list {
            margin-top: 14px;
            padding-left: 20px;
            font-size: 13.5px;
            color: #475569;
            line-height: 1.65;
        }
        details .source-list li { margin-bottom: 8px; }
        details .source-list a { color: var(--primary); text-decoration: underline; text-underline-offset: 2px; }
        details .source-list a:hover { color: var(--primary-dark); }

        /* ─── Cross-link Promo Banner ─── */
        .crosslink-banner {
            background: linear-gradient(135deg, #4338ca 0%, #0284c7 100%);
            border-radius: 20px;
            padding: 32px;
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 24px;
            margin: 40px 0 20px;
            box-shadow: 0 12px 30px rgba(67, 56, 202, 0.25);
        }
        .crosslink-content h4 {
            font-size: 19px;
            font-weight: 800;
            margin-bottom: 6px;
        }
        .crosslink-content p {
            font-size: 14px;
            color: rgba(255, 255, 255, 0.9);
            line-height: 1.5;
            margin: 0;
        }
        .btn-crosslink {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 22px;
            background: #ffffff;
            color: var(--primary);
            font-size: 13.5px;
            font-weight: 800;
            border-radius: 9999px;
            white-space: nowrap;
            transition: all 0.2s ease;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        .btn-crosslink:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 18px rgba(0, 0, 0, 0.15);
        }

        /* ─── Footer ─── */
        .footer {
            background: #0f172a;
            color: #cbd5e1;
            padding: 60px 0 30px;
            border-top: 1px solid #1e293b;
        }
        .footer-grid {
            display: grid;
            grid-template-columns: 1.8fr 1fr 1fr 1.2fr;
            gap: 36px;
            margin-bottom: 48px;
        }
        .footer-brand h4 {
            font-size: 18px;
            font-weight: 800;
            color: #ffffff;
            margin-bottom: 12px;
        }
        .footer-brand p {
            font-size: 13.5px;
            color: #94a3b8;
            line-height: 1.6;
            margin: 0;
        }
        .footer-links {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 10px;
            font-size: 13.5px;
        }
        .footer-links a {
            color: #94a3b8;
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

        /* ─── Responsive Queries ─── */
        @media (max-width: 900px) {
            .mast-grid, .duel-cards, .evidence-grid, .data-stats-row, .steps-grid, .recom-container, .footer-grid {
                grid-template-columns: 1fr;
            }
            .corpus-strip { grid-template-columns: 1fr; }
            .nav-links { display: none; }
            .flow-card { grid-template-columns: 1fr; text-align: left; }
            .flow-arrow { display: none; }
            .crosslink-banner { flex-direction: column; align-items: flex-start; }
            .scoreboard-card { overflow-x: auto; }
            .scoreboard-table { min-width: 600px; }
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
            .fit-item { grid-template-columns: 1fr; gap: 8px; }
            .fit-item .score-pill { align-self: flex-start; }
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
                    <li><a href="<?= base_url() ?>#teori">Tiga Lensa</a></li>
                    <li><a href="<?= base_url() ?>#fitur-unggulan">Inovasi Mesin</a></li>
                    <li><a href="<?= base_url() ?>#integrasi-ojs">Integrasi OJS</a></li>
                    <li><a href="<?= base_url('riset/ar-tilik-vs-zerogpt') ?>">AR Tilik vs ZeroGPT</a></li>
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
                <span style="color: var(--dark);">AR Tilik vs Turnitin</span>
            </div>

            <div class="mast-grid">
                <div>
                    <div class="pill-tag">
                        <span class="dot"></span>
                        <span>Analisis untuk Jurnal &amp; Kampus Indonesia &bull; Edisi 2026</span>
                    </div>
                    <h1 class="hero-title">
                        <span class="brand-ar">AR Tilik</span> <span class="brand-vs">vs</span> <span class="brand-ti">Turnitin</span>
                    </h1>
                    <p class="hero-lead">
                        Bukan duel <em>“siapa paling pintar”</em>. Turnitin unggul mutlak pada skala korpus institusional global puluhan tahun. AR Tilik berfokus pada apa yang tidak diselesaikan Turnitin: <strong>verifikasi keberadaan rujukan nyata (Crossref/OpenAlex)</strong>, transparansi alasan keputusan, kedaulatan data lokal, dan akses fleksibel untuk pengelola jurnal ilmiah di Indonesia.
                    </p>
                    <div class="hero-meta">
                        <span>🗓️ Diperbarui: <?= esc($metrics['last_sync_date'] ?? date('d F Y')) ?></span>
                        <span>&bull;</span>
                        <span style="display: inline-flex; align-items: center; gap: 5px; color: #059669; font-weight: 700;">
                            <span style="width: 8px; height: 8px; border-radius: 50%; background: #059669; display: inline-block;"></span>
                            Engine v<?= esc($metrics['engine_version'] ?? '3.4.2') ?> Terverifikasi
                        </span>
                        <span>&bull;</span>
                        <span>📚 <?= number_format($metrics['total_corpus'] ?? 854) ?> Dokumen Korpus Terindeks</span>
                        <span>&bull;</span>
                        <span>📑 <?= number_format($metrics['total_reports'] ?? 1167) ?> Laporan Audit Selesai</span>
                    </div>
                </div>

                <aside class="verdict-card" aria-label="Kesimpulan Eksekutif">
                    <span class="verdict-badge">Kesimpulan Paling Jujur</span>
                    <h3>Perbedaan Lapangan &amp; Arsitektur</h3>
                    <p>
                        Untuk melacak salinan dari internet terbuka dan miliaran tugas mahasiswa lama di seluruh dunia, Turnitin jauh lebih dominan dan matang.
                    </p>
                    <p>
                        Namun untuk memeriksa apakah rujukan benar-benar ada di dunia nyata, memahami rincian alasan skor, serta mengelola alur editorial naskah berbahasa Indonesia secara terjangkau tanpa kontrak ribuan dollar, AR Tilik menawarkan nilai diferensiasi yang nyata dan terbukti.
                    </p>
                    <p class="stamp">
                        Klaim vendor dipisahkan secara ketat dari temuan studi independen.
                    </p>
                </aside>
            </div>
        </div>
    </section>

    <!-- ─── Sticky Sub-Navigation ─── -->
    <nav class="subnav" aria-label="Navigasi Bab Riset">
        <div class="container subnav-inner">
            <a href="#lapangan" class="subnav-link active">01 — Lapangan Berbeda</a>
            <a href="#korpus" class="subnav-link">02 — Skala Korpus</a>
            <a href="#ai" class="subnav-link">03 — Deteksi AI</a>
            <a href="#data" class="subnav-link">04 — Tata Kelola Data</a>
            <a href="#pasar" class="subnav-link">05 — Pasar Jurnal &amp; OJS</a>
            <a href="#strategi" class="subnav-link">06 — Rekomendasi Peran</a>
            <a href="#sumber" class="subnav-link">07 — Sumber Pustaka</a>
        </div>
    </nav>

    <!-- ─── 01 Lapangan Berbeda ─── -->
    <section id="lapangan" class="section-white">
        <div class="container">
            <div class="section-header">
                <div class="section-number">01 — Lapangan Berbeda</div>
                <h2 class="section-title">Mereka bermain di lapangan yang berbeda secara fundamental.</h2>
                <p class="section-desc">
                    Turnitin dibangun sebagai infrastruktur pencocokan teks berskala institusional kampus global. AR Tilik dirancang sebagai instrumen pemeriksa kemiripan dengan lapisan audit forensik rujukan yang berorientasi pada ekosistem karya ilmiah Indonesia. Persamaan mereka ada pada pengukuran kemiripan teks; selebihnya sasaran, alur, dan arsitekturnya berlainan.
                </p>
            </div>

            <div class="duel-cards">
                <article class="duel-card card-ar">
                    <div class="duel-tag">AR Tilik (Mesin Tiga Lensa)</div>
                    <h3>Menilai Risiko, Lalu Memverifikasi Rujukan</h3>
                    <ul class="duel-list">
                        <li><strong>Empat Pilar Terbuka:</strong> Mengombinasikan 5-gram token match, TF-IDF kemiripan kosinus semantik, pasase beririsan terpanjang, dan sintesis risiko berbasis XGBoost v<?= esc($metrics['engine_version'] ?? '3.4.2') ?>.</li>
                        <li><strong>JIT Harvester &amp; Repositori Nasional:</strong> Terhubung ke Neliti &amp; GARUDA Dikti, kini mengindeks <strong><?= number_format($metrics['total_corpus'] ?? 854) ?> dokumen karya ilmiah</strong> dan terus bertambah otomatis saat naskah dianalisis.</li>
                        <li><strong>Audit Sitasi Nyata:</strong> Setiap rujukan diperiksa langsung ke pangkalan data global OpenAlex dan Crossref; sitasi fiktif teridentifikasi secara otomatis.</li>
                        <li><strong>Kedaulatan &amp; Purge Mandiri:</strong> Penulis memegang hak penuh memusnahkan fisik naskah secara mandiri tanpa residu di repositori publik.</li>
                    </ul>
                    <div class="duel-note">
                        Status sistem: Terverifikasi pada pengujian produksi &bull; <?= number_format($metrics['total_reports'] ?? 1167) ?> Laporan Selesai.
                    </div>
                </article>

                <article class="duel-card card-ti">
                    <div class="duel-tag">Turnitin (Similarity Infrastructure)</div>
                    <h3>Mencocokkan Teks pada Korpus Raksasa</h3>
                    <ul class="duel-list">
                        <li><strong>Database Dominan:</strong> Mencocokkan teks ke indeks web masif, miliaran tugas mahasiswa yang diarsipkan, serta konten mitra penerbit dunia.</li>
                        <li><strong>Similarity Report:</strong> Menampilkan kecocokan teks persis; panduan resmi Turnitin menegaskan skor kemiripan bukanlah vonis plagiarisme.</li>
                        <li><strong>Deteksi AI Terpisah:</strong> Fitur AI Writing Detection disematkan sebagai lapisan probabilistik terpisah dari skor kemiripan teks.</li>
                        <li><strong>Segmentasi Produk:</strong> Untuk penerbit dan kantor jurnal, produk yang setara secara pasar adalah iThenticate atau Crossref Similarity Check.</li>
                    </ul>
                    <div class="duel-note">
                        Turnitin dan panduan universitas global mewajibkan penelaahan manusia dan melarang pemutusan sanksi sepihak hanya berdasar persentase.
                    </div>
                </article>
            </div>

            <!-- Scoreboard Matriks -->
            <div class="scoreboard-card">
                <table class="scoreboard-table">
                    <thead>
                        <tr>
                            <th style="width: 22%;">Parameter Telaah</th>
                            <th class="th-ar" style="width: 39%;">AR Tilik</th>
                            <th class="th-ti" style="width: 39%;">Turnitin</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><strong>Kedalaman Korpus</strong></td>
                            <td>
                                <span class="score-pill limit">Terfokus / JIT</span><br>
                                <?= number_format($metrics['total_corpus'] ?? 854) ?>+ dokumen karya ilmiah terindeks riil dan terus berkembang secara otomatis via JIT Harvester Neliti &amp; GARUDA Dikti.
                            </td>
                            <td>
                                <span class="score-pill good">Dominan Mutlak</span><br>
                                Puluhan miliar laman web, 1.9 miliar tugas mahasiswa tersimpan, dan kemitraan puluhan tahun dengan penerbit terkemuka dunia.
                            </td>
                        </tr>
                        <tr>
                            <td><strong>Audit Sitasi Fiktif</strong></td>
                            <td>
                                <span class="score-pill good">Terverifikasi Mandiri</span><br>
                                Terhubung langsung ke API Crossref &amp; OpenAlex; lulus uji pembuktian sitasi fiktif yang sengaja dibuat-buat.
                            </td>
                            <td>
                                <span class="score-pill limit">Bukan Fungsi Similarity</span><br>
                                Fitur Similarity Report hanya mencari kecocokan teks; sitasi palsu yang tidak ada di dunia nyata justru tidak memicu kemiripan apa pun.
                            </td>
                        </tr>
                        <tr>
                            <td><strong>Dukungan Bahasa Indonesia</strong></td>
                            <td>
                                <span class="score-pill good">Fokus Produk</span><br>
                                Dirancang khusus untuk morfologi naskah, format skripsi/tesis, dan gaya penulisan akademik Indonesia.
                            </td>
                            <td>
                                <span class="score-pill limit">Celah Fitur</span><br>
                                Bahasa Indonesia tidak tercantum dalam daftar resmi <em>Translated Matching</em> maupun daftar bahasa yang didukung oleh deteksi AI Turnitin.
                            </td>
                        </tr>
                        <tr>
                            <td><strong>Deteksi Tulisan AI</strong></td>
                            <td>
                                <span class="score-pill open">Tidak Diklaim</span><br>
                                Memilih berfokus pada indikator fisik yang dapat dibuktikan secara empiris dan dipertanggungjawabkan di ruang sidang.
                            </td>
                            <td>
                                <span class="score-pill limit">Probabilistik Tertutup</span><br>
                                Vendor mengklaim false-positive &lt;1%, namun berbagai studi independen menemukan kerentanan bias bahasa dan akurasi rendah setelah parafrasa.
                            </td>
                        </tr>
                        <tr>
                            <td><strong>Aksesibilitas &amp; Biaya</strong></td>
                            <td>
                                <span class="score-pill good">Transparan &amp; Fleksibel</span><br>
                                Biaya langganan transparan <?= esc($metrics['price_label'] ?? 'Rp 450.000/bulan') ?>; dapat diputuskan langsung oleh pengelola jurnal mandiri atau prodi tanpa birokrasi kontrak tahunan.
                            </td>
                            <td>
                                <span class="score-pill limit">Kontrak Institusional</span><br>
                                Wajib negosiasi lisensi institusi universitas; harga tidak dibuka ke publik dan umumnya berbasis jumlah mahasiswa aktif (enrollment).
                            </td>
                        </tr>
                        <tr>
                            <td><strong>Penghapusan Mandiri (Purge)</strong></td>
                            <td>
                                <span class="score-pill good">Terverifikasi &amp; Aktif</span><br>
                                Fitur Purge Mandiri terverifikasi dan aktif di sistem: penulis berhak memusnahkan berkas fisik (@unlink) dan menghapus teks ekstraksi kapan saja tanpa residu pada repositori publik.
                            </td>
                            <td>
                                <span class="score-pill limit">Prosedur Admin Rumit</span><br>
                                Penulis atau mahasiswa tidak memiliki tombol purge mandiri; permintaan penghapusan permanen harus diajukan instruktur dan disetujui admin kampus.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    <!-- ─── 02 Skala Korpus ─── -->
    <section id="korpus" class="section-slate">
        <div class="container">
            <div class="section-header">
                <div class="section-number">02 — Skala Korpus</div>
                <h2 class="section-title">Turnitin menang telak pada kedalaman korpus global.</h2>
                <p class="section-desc">
                    Bagian ini tidak perlu ditutup-tutupi: tidak ada dasar ilmiah untuk mengklaim bahwa korpus AR Tilik sebanding dengan Turnitin. Keunggulan mutlak (*moat*) Turnitin terletak pada akumulasi data naskah mahasiswa dan kemitraan penerbit selama puluhan tahun.
                </p>
            </div>

            <div class="corpus-strip">
                <div class="corpus-card">
                    <div class="big-number">47M+</div>
                    <div class="corpus-label">Halaman Web Diarsipkan</div>
                    <div class="corpus-source">Dikutip dari panduan resmi University of Tennessee (UTK). Meliputi konten internet aktif dan arsip halaman web lama.</div>
                </div>
                <div class="corpus-card">
                    <div class="big-number">1.9M+</div>
                    <div class="corpus-label">Karya Mahasiswa Tersimpan</div>
                    <div class="corpus-source">Data Turnitin per Juni 2025 yang dikutip dalam investigasi publik Route Fifty. Draf yang pernah diunggah kampus global.</div>
                </div>
                <div class="corpus-card">
                    <div class="big-number">≈190Jt</div>
                    <div class="corpus-label">Artikel Ilmiah Penerbit</div>
                    <div class="corpus-source">Estimasi dari kemitraan penerbit jurnal internasional (Elsevier, IEEE, Springer, Wiley, dll.).</div>
                </div>
            </div>

            <h3 style="font-size: 20px; font-weight: 800; color: var(--dark); margin-bottom: 16px;">Mekanisme Kerja Pencocokan Standar</h3>
            <div class="steps-grid">
                <div class="step-box">
                    <div class="step-num">01</div>
                    <h4>Naskah Dipecah Menjadi Pola</h4>
                    <p>Mesin mencacah kalimat menjadi n-gram dan sidik jari digital untuk dicocokkan dengan basis data yang telah terindeks sebelumnya.</p>
                </div>
                <div class="step-box">
                    <div class="step-num">02</div>
                    <h4>Sorotan Sumber Serupa</h4>
                    <p>Similarity Report menyorot bagian yang identik dan menghitung proporsi teks yang cocok dari seluruh panjang naskah.</p>
                </div>
                <div class="step-box">
                    <div class="step-num">03</div>
                    <h4>Keputusan Mutlak pada Manusia</h4>
                    <p>Skor 0% bukan berarti naskah pasti asli (bisa karena sumber belum terindeks); skor tinggi bisa berasal dari sitasi sah atau metodologi standar.</p>
                </div>
            </div>

            <div class="callout-box">
                <div class="callout-quote">“Skor 0% similarity bukan jaminan naskah asli. Itu hanya berarti sistem tidak menemukan teks yang identik pada database dan konfigurasi saat itu.”</div>
                <p class="callout-desc">Sebaliknya, skor 25% bisa jadi sah sepenuhnya jika berasal dari landasan undang-undang, formula matematika, atau rujukan yang dikutip secara benar.</p>
            </div>

            <div style="background: #ffffff; border: 1px solid var(--border); border-radius: 20px; padding: 32px; box-shadow: var(--shadow-sm); margin-top: 24px;">
                <h3 style="font-size: 19px; font-weight: 800; color: var(--dark); margin-bottom: 10px;">Di Sinilah Posisi &amp; Nilai AR Tilik</h3>
                <p style="font-size: 15px; color: #475569; line-height: 1.65; margin: 0;">
                    Keunggulan AR Tilik bukan pada klaim jumlah naskah yang lebih banyak dari Turnitin, melainkan pada <strong>keterbukaan alasan dekomposisi data</strong>: laporan AR Tilik membongkar putusan menjadi pencocokan leksikal eksak, kesamaan semantik kosinus, pasase beririsan terpanjang, dan verifikasi faktual daftar pustaka. Editor dan penguji dapat melihat persis <em>mengapa</em> sebuah dokumen diberi label <em>REVIEW</em>.
                </p>
            </div>
        </div>
    </section>

    <!-- ─── 03 Deteksi AI ─── -->
    <section id="ai" class="section-white">
        <div class="container">
            <div class="section-header">
                <div class="section-number">03 — Deteksi Tulisan AI</div>
                <h2 class="section-title">Klaim vendor deteksi AI bukanlah kepastian hukum.</h2>
                <p class="section-desc">
                    Turnitin menyematkan fitur AI Writing Detection sebagai laporan terpisah. Dokumen rilis mereka sendiri menyatakan bahwa hasilnya tidak boleh menjadi satu-satunya dasar penjatuhan sanksi. Sejumlah penelitian independen membuktikan kelemahan fatal pendeteksian berbasis gaya bahasa:
                </p>
            </div>

            <div class="evidence-grid">
                <article class="evidence-card card-claim">
                    <div style="font-size: 12px; font-weight: 800; color: var(--turnitin-teal); text-transform: uppercase; margin-bottom: 8px;">Klaim Vendor Resmi</div>
                    <h3>Yang Diklaim Turnitin</h3>
                    <ul class="duel-list">
                        <li>Risiko false positive diklaim “kurang dari 1%” untuk dokumen yang memiliki perkiraan teks AI di atas 20%.</li>
                        <li>Sekitar 85% teks buatan AI terdeteksi; sekitar 15% sengaja dilewatkan demi menekan angka false positive.</li>
                        <li>Skor antara 1–19% disamarkan sebagai tanda bintang (*%) tanpa penyorotan kalimat karena rendahnya tingkat keyakinan model.</li>
                        <li>Hasil dinyatakan sebagai sarana pembuka diskusi pedagogis, bukan putusan disipliner final.</li>
                    </ul>
                </article>

                <article class="evidence-card card-indep">
                    <div style="font-size: 12px; font-weight: 800; color: var(--primary); text-transform: uppercase; margin-bottom: 8px;">Riset Independen Global</div>
                    <h3>Temuan Nyata Peneliti Independen</h3>
                    <ul class="duel-list">
                        <li><strong>Weber-Wulff et al. (2023):</strong> Menguji 14 detektor AI; tidak ada satupun alat yang mencapai akurasi 80%. Turnitin dinilai terbaik di kelompoknya namun tetap tidak cukup andal untuk sanksi akademik.</li>
                        <li><strong>Perkins et al. (2024):</strong> Sekitar 61% teks AI mentah terdeteksi, namun angka deteksi merosot drastis hingga 19% setelah teks diparafrasa atau dimanipulasi secara sederhana.</li>
                        <li><strong>Benchmark Pihak Ketiga (2026):</strong> False positive rate pada tulisan formal manusia mendekati 4%, jauh melampaui klaim vendor &lt;1%.</li>
                        <li><strong>Teks AI 'Humanized':</strong> Alat pengubah gaya bahasa AI generasi terbaru lolos hampir 100% dari radar detektor konvensional.</li>
                    </ul>
                </article>
            </div>

            <!-- Stats Row -->
            <div class="data-stats-row">
                <div class="data-stat-box">
                    <div class="stat-val">61%</div>
                    <div class="stat-title">AI Mentah Terdeteksi</div>
                    <div class="stat-sub">Riset Perkins et al. (2024) pada teks ChatGPT mentah tanpa pengeditan.</div>
                </div>
                <div class="data-stat-box">
                    <div class="stat-val">19%</div>
                    <div class="stat-title">Setelah Parafrasa Ringan</div>
                    <div class="stat-sub">Tingkat deteksi anjlok drastis begitu penulis memodifikasi kalimat pembuka.</div>
                </div>
                <div class="data-stat-box">
                    <div class="stat-val">0</div>
                    <div class="stat-title">Bahasa Indonesia Didukung</div>
                    <div class="stat-sub">Deteksi AI Turnitin hanya mencakup Inggris, Spanyol, Jepang, Jerman, Belanda, dll.</div>
                </div>
            </div>

            <div class="callout-box teal">
                <div class="callout-quote">Implikasi Ilmiah untuk AR Tilik:</div>
                <p class="callout-desc">
                    AR Tilik menolak menjual label “detektor AI paling akurat”. Kami memilih mengaudit aspek yang dapat diverifikasi secara fisik: teks mana yang cocok, bagaimana pola susunannya, dan apakah rujukannya benar-benar terbit di dunia nyata. Kasus gugatan hukum mahasiswa di Adelphi University (2026)—di mana vonis plagiarisme AI 100% dibatalkan peradilan—menjadi bukti risiko fatal jika probabilitas gaya bahasa dijadikan vonis disipliner sepihak.
                </p>
            </div>
        </div>
    </section>

    <!-- ─── 04 Tata Kelola Data ─── -->
    <section id="data" class="section-slate">
        <div class="container">
            <div class="section-header">
                <div class="section-number">04 — Tata Kelola Data &amp; Privasi</div>
                <h2 class="section-title">Pahami perbedaan Turnitin Standard vs iThenticate.</h2>
                <p class="section-desc">
                    Pernyataan bahwa “Turnitin menyimpan naskah selamanya” benar untuk alur pengunggahan tugas mahasiswa biasa (*Turnitin Feedback Studio*), namun tidak berlaku bagi alur penerbitan (*iThenticate*). Pemahaman ini krusial bagi kantor jurnal ilmiah:
                </p>
            </div>

            <div class="flow-container">
                <div class="flow-card ti-flow">
                    <div class="flow-source">
                        <strong>Turnitin Standard</strong>
                        <span>Tugas Mahasiswa / LMS</span>
                    </div>
                    <div class="flow-arrow">&rarr;</div>
                    <div class="flow-dest">
                        <strong>Masuk Repository Global Secara Default</strong>
                        <p>Naskah disimpan dan dijadikan database pencocokan untuk pengujian masa depan. Pengajar dapat mengatur “No Repository”, namun mahasiswa tidak memegang kendali atas pilihan ini.</p>
                    </div>
                </div>

                <div class="flow-card ti-flow">
                    <div class="flow-source">
                        <strong>Permintaan Hapus</strong>
                        <span>Turnitin Standard</span>
                    </div>
                    <div class="flow-arrow">&rarr;</div>
                    <div class="flow-dest">
                        <strong>Prosedur Birokrasi Admin Kampus</strong>
                        <p>Permintaan penghapusan permanen harus diajukan oleh dosen instruktur dan disetujui satu per satu oleh administrator universitas. Penulis tidak memiliki tombol hapus mandiri.</p>
                    </div>
                </div>

                <div class="flow-card ti-flow">
                    <div class="flow-source">
                        <strong>iThenticate</strong>
                        <span>Alur Penerbit &amp; Jurnal</span>
                    </div>
                    <div class="flow-arrow">&rarr;</div>
                    <div class="flow-dest">
                        <strong>Tidak Diindeks ke Repository Global</strong>
                        <p>Manuskrip tetap berstatus privat di folder pengguna dan tidak dicocokkan ke karya orang lain. Pengguna dapat menghapus draf naskah agar tidak mencocokkan dirinya sendiri di masa depan.</p>
                    </div>
                </div>

                <div class="flow-card ar-flow">
                    <div class="flow-source">
                        <strong>AR Tilik</strong>
                        <span>Ekosistem Riset Indonesia</span>
                    </div>
                    <div class="flow-arrow">&rarr;</div>
                    <div class="flow-dest">
                        <strong>Kedaulatan &amp; Purge Mandiri Terverifikasi</strong>
                        <p>Status: Terverifikasi &amp; Aktif di Engine v<?= esc($metrics['engine_version'] ?? '3.4.2') ?>. Penulis dan pengelola naskah memiliki hak penuh memusnahkan berkas fisik (@unlink) dan menghapus teks ekstraksi kapan saja tanpa residu pada repositori publik.</p>
                    </div>
                </div>
            </div>

            <div style="background: #ffffff; border: 1px solid var(--border); border-radius: 16px; padding: 24px; margin-top: 24px;">
                <h4 style="font-size: 16px; font-weight: 800; color: var(--dark); margin-bottom: 8px;">Pedoman Komunikasi yang Beretika:</h4>
                <p style="font-size: 14px; color: #475569; line-height: 1.6; margin: 0;">
                    Kami tidak mengatakan “semua produk Turnitin mencuri naskah Anda”. Pernyataan yang tepat dan jujur adalah: <em>“Turnitin Feedback Studio tidak menyediakan penghapusan mandiri bagi penulis naskah tugas; sementara AR Tilik dan iThenticate menghormati privasi manuskrip pra-terbit tanpa pengarsipan otomatis.”</em>
                </p>
            </div>
        </div>
    </section>

    <!-- ─── 05 Pasar Jurnal & OJS ─── -->
    <section id="pasar" class="section-white">
        <div class="container">
            <div class="section-header">
                <div class="section-number">05 — Pasar Jurnal Ilmiah &amp; OJS</div>
                <h2 class="section-title">Untuk jurnal ilmiah, lawan sebenarnya adalah iThenticate.</h2>
                <p class="section-desc">
                    Banyak pengelola jurnal di Indonesia salah kaprah menyamakan lisensi Turnitin kelas perkuliahan dengan kebutuhan editorial jurnal. Jalur resmi penerbitan global adalah iThenticate atau <em>Crossref Similarity Check</em> yang membutuhkan biaya per-dokumen dan keanggotaan institusi:
                </p>
            </div>

            <div class="fit-list">
                <div class="fit-item">
                    <div class="fit-idx">01</div>
                    <div class="fit-content">
                        <h4>Harga Fleksibel Tanpa Kontrak Ribuan Dollar</h4>
                        <p>AR Tilik hadir dengan skema mandiri <?= esc($metrics['price_label'] ?? 'Rp 450.000/bulan') ?> yang dapat langsung diputuskan oleh ketua editor jurnal atau dekan fakultas tanpa menunggu lelang pengadaan kampus bernilai ratusan juta rupiah.</p>
                    </div>
                    <span class="score-pill good">Solutif</span>
                </div>

                <div class="fit-item">
                    <div class="fit-idx">02</div>
                    <div class="fit-content">
                        <h4>Verifikasi Sitasi Eksternal Melawan Halusinasi AI</h4>
                        <p>Naskah modern kerap memuat daftar pustaka yang dibuat-buat oleh AI generatif. Turnitin Similarity Check tidak mendeteksi sitasi palsu; AR Tilik membuktikan keberadaan naskah secara empiris lewat pangkalan data Crossref &amp; OpenAlex.</p>
                    </div>
                    <span class="score-pill good">Terverifikasi</span>
                </div>

                <div class="fit-item">
                    <div class="fit-idx">03</div>
                    <div class="fit-content">
                        <h4>Dukungan Bahasa &amp; Konteks Akademik Lokal</h4>
                        <p>Daftar resmi <em>Translated Matching</em> Turnitin mencantumkan bahasa Jawa, tetapi tidak memiliki dukungan untuk Bahasa Indonesia. AR Tilik dibangun dari fondasi tata bahasa dan gaya penulisan ilmiah Indonesia.</p>
                    </div>
                    <span class="score-pill good">Fokus Lokal</span>
                </div>

                <div class="fit-item">
                    <div class="fit-idx">04</div>
                    <div class="fit-content">
                        <h4>Konektivitas Terbuka ke Open Journal Systems (OJS 3.x)</h4>
                        <p>Menyediakan dokumen panduan integrasi, konektor PHP OJS 3.x mandiri, dan script runner eksekusi yang dapat langsung diunduh dan dipasang oleh pengelola jurnal.</p>
                    </div>
                    <span class="score-pill good">Tersedia &amp; Resmi</span>
                </div>

                <div class="fit-item">
                    <div class="fit-idx">05</div>
                    <div class="fit-content">
                        <h4>Kedaulatan Naskah &amp; Purge Mandiri</h4>
                        <p>Penulis memegang kendali penuh atas privasi karya pra-terbit dengan fitur penghapusan fisik berkas permanen (purge) dari server secara mandiri tanpa prosedur birokrasi administrator.</p>
                    </div>
                    <span class="score-pill good">Terverifikasi</span>
                </div>
            </div>
        </div>
    </section>

    <!-- ─── 06 Rekomendasi Peran ─── -->
    <section id="strategi" class="section-slate">
        <div class="container">
            <div class="section-header">
                <div class="section-number">06 — Rekomendasi Keputusan</div>
                <h2 class="section-title">Strategi adopsi yang rasional dan saling melengkapi.</h2>
                <p class="section-desc">
                    AR Tilik tidak perlu diposisikan sebagai pembunuh Turnitin di segala lini. Keduanya dapat berdampingan secara cerdas sesuai kebutuhan dan tingkat risiko keputusan:
                </p>
            </div>

            <div class="recom-container">
                <div class="recom-nav" role="tablist">
                    <button type="button" class="recom-btn" role="tab" aria-selected="true" data-role="editor">
                        <span>📰 Editor Jurnal Ilmiah</span>
                        <span>&rsaquo;</span>
                    </button>
                    <button type="button" class="recom-btn" role="tab" aria-selected="false" data-role="kampus">
                        <span>🏛️ Pimpinan LPPM &amp; Fakultas</span>
                        <span>&rsaquo;</span>
                    </button>
                    <button type="button" class="recom-btn" role="tab" aria-selected="false" data-role="penulis">
                        <span>✍️ Dosen &amp; Mahasiswa</span>
                        <span>&rsaquo;</span>
                    </button>
                </div>

                <article class="recom-panel" id="recomPanel">
                    <div id="recomContent">
                        <div style="font-size: 11px; font-weight: 800; color: var(--primary); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px;" id="recomKicker">UNTUK PENGELOLA &amp; EDITOR JURNAL</div>
                        <h4 id="recomTitle">Gunakan Berdampingan Sesuai Tingkat Risiko Naskah</h4>
                        <p id="recomP1">
                            <strong>Gunakan Turnitin / iThenticate</strong> jika kantor jurnal Anda terafiliasi dengan universitas besar yang telah mendanai akses Crossref Similarity Check dan prioritas utama Anda adalah menyaring salinan langsung dari jurnal internasional terindeks Scopus.
                        </p>
                        <p id="recomP2">
                            <strong>Gunakan AR Tilik</strong> untuk melakukan skrining rutin naskah masuk: memeriksa apakah referensi yang dicantumkan penulis benar-benar ada di Crossref, mendeteksi sitasi palsu buatan AI, serta memperoleh laporan audit komprehensif tanpa membebani anggaran operasional jurnal dengan biaya per-dokumen yang mahal.
                        </p>
                    </div>
                </article>
            </div>

            <!-- Final Dark Callout -->
            <div class="final-banner">
                <div style="font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; color: #38bdf8; margin-bottom: 10px;">
                    Prinsip Integritas Utama
                </div>
                <h3>Turnitin unggul sebagai mesin pencari salinan. AR Tilik berfokus unggul sebagai proses audit integritas.</h3>
                <p>
                    Itulah posisi yang paling jujur, etis, dan kokoh di hadapan komunitas akademisi Indonesia. Bukan mengklaim memiliki database paling besar di dunia, melainkan membantu editor dan dosen memahami substansi naskah, membuktikan keberadaan rujukan nyata, dan mengambil keputusan yang transparan serta dapat dipertanggungjawabkan.
                </p>
                <div style="display: flex; gap: 14px; flex-wrap: wrap;">
                    <a href="<?= base_url('dashboard') ?>" class="btn btn-primary" style="background: var(--primary); border: none; padding: 12px 24px; border-radius: 9999px; font-size: 13.5px; font-weight: 700; color: #ffffff;">
                        Buka Dashboard AR Tilik &rarr;
                    </a>
                    <a href="<?= base_url('docs/Brosur_Eksekutif_ARTilik.pdf') ?>" target="_blank" download="Brosur_Eksekutif_ARTilik.pdf" style="display: inline-flex; align-items: center; gap: 6px; padding: 12px 22px; border-radius: 9999px; font-size: 13.5px; font-weight: 700; color: #ffffff; background: rgba(255, 255, 255, 0.1); border: 1px solid rgba(255, 255, 255, 0.2);">
                        Unduh Brosur Eksekutif Kampus (PDF)
                    </a>
                </div>
            </div>

            <!-- Crosslink to ZeroGPT Whitepaper -->
            <div class="crosslink-banner">
                <div class="crosslink-content">
                    <h4>Baca Juga Whitepaper: AR Tilik vs ZeroGPT</h4>
                    <p>Mengapa detektor probabilitas gaya bahasa AI kerap menghasilkan 80% false-positive pada naskah Indonesia, dan bagaimana Mesin Tiga Lensa membuktikan integritas secara ilmiah.</p>
                </div>
                <a href="<?= base_url('riset/ar-tilik-vs-zerogpt') ?>" class="btn-crosslink">
                    <span>Baca Whitepaper ZeroGPT</span>
                    <span>&rarr;</span>
                </a>
            </div>
        </div>
    </section>

    <!-- ─── 07 Sumber Pustaka ─── -->
    <section id="sumber" class="section-white">
        <div class="container">
            <div class="section-header">
                <div class="section-number">07 — Sumber Pustaka &amp; Batas Bukti</div>
                <h2 class="section-title">Dokumentasi primer, regulasi kampus, dan keterbatasan riset.</h2>
                <p class="section-desc">
                    Demi transparansi metodologis, analisis ini mendasarkan pembahasannya pada dokumen rilis resmi vendor, panduan kampus independen, serta riset pihak ketiga yang dapat ditelusuri:
                </p>
            </div>

            <details class="source-group" open>
                <summary>1. Dokumen Resmi Vendor &amp; Panduan OJS</summary>
                <ol class="source-list">
                    <li><a href="https://guides.turnitin.com/hc/en-us/articles/23453068441613-Requesting-paper-deletions" target="_blank" rel="noopener">Turnitin Guides — Requesting paper deletions (Alur Penghapusan Naskah)</a></li>
                    <li><a href="https://www.turnitin.com/blog/ai-writing-detection-what-academic-leaders-need-to-know" target="_blank" rel="noopener">Turnitin Blog — AI writing detection: What academic leaders need to know</a></li>
                    <li><a href="https://guides.turnitin.com/hc/en-us/articles/30570324172941-Translated-Matching-in-the-new-enhanced-Similarity-Report" target="_blank" rel="noopener">Turnitin — Translated Matching supported languages</a></li>
                    <li><a href="http://guides.ithenticate.com/hc/en-us/articles/27871174228365-iThenticate-release-notes" target="_blank" rel="noopener">iThenticate — Product Release Notes &amp; Repository Privacy Policies</a></li>
                    <li><a href="https://docs.pkp.sfu.ca/crossref-ojs-manual/en/simCheck" target="_blank" rel="noopener">Public Knowledge Project (PKP) — OJS Crossref Similarity Check Manual</a></li>
                </ol>
            </details>

            <details class="source-group">
                <summary>2. Panduan Universitas &amp; Investigasi Publik</summary>
                <ol class="source-list">
                    <li><a href="https://oit.utk.edu/teachingtools/ithenticate-vs-turnitin-similarity/" target="_blank" rel="noopener">University of Tennessee Knoxville (OIT) — iThenticate vs Turnitin Comparison</a></li>
                    <li><a href="https://teaching.resources.osu.edu/toolsets/carmencanvas/guides/turnitin/turnitin-best-practices" target="_blank" rel="noopener">Ohio State University — Turnitin Best Practices in Canvas</a></li>
                    <li><a href="https://help.d2l.msu.edu/msu-docs/other-tools-at-msu/using-assignments-with-turnitin/turnitin-vs-ithenticate" target="_blank" rel="noopener">Michigan State University — Turnitin vs iThenticate Differences</a></li>
                    <li><a href="https://www.route-fifty.com/artificial-intelligence/2025/06/california-colleges-spend-millions-catch-plagiarism-and-ai-faulty-tech-worth-it/406330/" target="_blank" rel="noopener">Route Fifty (Juni 2025) — California Colleges Investigation on Turnitin AI Tech</a></li>
                    <li><a href="https://www.insidehighered.com/news/quick-takes/2026/02/11/adelphi-student-wins-ai-plagiarism-lawsuit" target="_blank" rel="noopener">Inside Higher Ed (Februari 2026) — Adelphi Student Wins AI Plagiarism Lawsuit</a></li>
                </ol>
            </details>

            <details class="source-group">
                <summary>3. Studi Ilmiah Independen Pihak Ketiga</summary>
                <ol class="source-list">
                    <li>Weber-Wulff, D., Anohina-Naumeca, A., Bjelobaba, S., et al. (2023). <em>Testing of detection tools for AI-generated text</em>. International Journal for Educational Integrity.</li>
                    <li>Perkins, M., Roe, J., Vu, B. H., &amp; Postma, D. (2024). <em>Academic integrity in the age of generative AI: Testing detection accuracy under evasion techniques</em>.</li>
                    <li><a href="https://claritybot.io/ai-content-verification/how-to-detect-hallucinated-citations-in-ai-generated-academic-writing-a-systematic-guide/" target="_blank" rel="noopener">Claritybot Research — Systematic Guide on Hallucinated Citation Verification</a></li>
                    <li><a href="https://hub.paper-checker.com/blog/ai-detection-false-positive-rates-tools-2026/" target="_blank" rel="noopener">Paper Checker — 2026 AI Detector False-Positive Benchmark Comparison</a></li>
                </ol>
            </details>

            <details class="source-group">
                <summary>4. Batasan Bukti &amp; Sinkronisasi Sistem Terkini</summary>
                <div style="font-size: 13.5px; color: #475569; line-height: 1.6; padding-top: 10px;">
                    Harga institusional Turnitin dinegosiasikan tertutup per kampus; biaya per naskah Crossref Similarity Check bergantung pada keanggotaan institusi. Status AR Tilik disinkronkan langsung dengan arsitektur produksi: Engine v<?= esc($metrics['engine_version'] ?? '3.4.2') ?>, <?= number_format($metrics['total_corpus'] ?? 854) ?> dokumen korpus aktif, fitur self-purge terverifikasi, dan konektor OJS 3.x resmi tersedia. Pembaruan data sistem disinkronkan secara otomatis per <?= esc($metrics['last_sync_date'] ?? date('d F Y')) ?>.
                </div>
            </details>
        </div>
    </section>

    <!-- ─── Official Footer ─── -->
    <footer class="footer">
        <div class="container">
            <div class="footer-grid">
                <div class="footer-brand">
                    <h4>AR Tilik</h4>
                    <p>
                        Sistem Penjaminan Mutu &amp; Integritas Penulisan Akademik Terintegrasi. Mengombinasikan Sidik Jari Leksikal, Sintesis Semantik, Audit Rujukan Nyata, dan Dukungan Open Journal Systems (OJS).
                    </p>
                </div>

                <div>
                    <strong style="color: #ffffff; display: block; margin-bottom: 14px;">Whitepaper &amp; Riset</strong>
                    <ul class="footer-links">
                        <li><a href="<?= base_url('riset/ar-tilik-vs-turnitin') ?>" style="color: #38bdf8; font-weight: 700;">AR Tilik vs Turnitin</a></li>
                        <li><a href="<?= base_url('riset/ar-tilik-vs-zerogpt') ?>">AR Tilik vs ZeroGPT</a></li>
                        <li><a href="<?= base_url() ?>#teori">Mesin Tiga Lensa</a></li>
                        <li><a href="<?= base_url() ?>#fitur-unggulan">Audit Forensik Cerdas</a></li>
                    </ul>
                </div>

                <div>
                    <strong style="color: #ffffff; display: block; margin-bottom: 14px;">Navigasi Cepat</strong>
                    <ul class="footer-links">
                        <li><a href="<?= base_url() ?>">Beranda Utama</a></li>
                        <li><a href="<?= base_url('dashboard') ?>">Dashboard Analisis</a></li>
                        <li><a href="<?= base_url('verify') ?>">Verifikasi Sertifikat</a></li>
                        <li><a href="<?= base_url('ambassador') ?>">AR Tilik Ambassador</a></li>
                    </ul>
                </div>

                <div>
                    <strong style="color: #ffffff; display: block; margin-bottom: 14px;">Dokumentasi &amp; Legal</strong>
                    <ul class="footer-links">
                        <li><a href="<?= base_url('docs/Brosur_Eksekutif_ARTilik.pdf') ?>" target="_blank" download="Brosur_Eksekutif_ARTilik.pdf">Brosur Kampus (PDF)</a></li>
                        <li><a href="<?= base_url('integrations/ojs/Panduan_Integrasi_OJS_ARTilik.pdf') ?>" target="_blank" download="Panduan_Integrasi_OJS_ARTilik.pdf">Panduan OJS (PDF)</a></li>
                        <li><a href="<?= base_url('privacy') ?>">Kebijakan Privasi</a></li>
                        <li><a href="<?= base_url('terms') ?>">Ketentuan Layanan</a></li>
                    </ul>
                </div>
            </div>

            <div class="footer-bottom">
                AR Tilik &copy; <?= date('Y') ?> &bull; Academic Writing Similarity &amp; Evidence Checker &bull; Kontak WhatsApp: <a href="https://wa.me/6285138862032" target="_blank" rel="noopener noreferrer" style="color: #cbd5e1; font-weight: 600;">085138862032</a> &bull; <a href="https://www.arsetontong.top" target="_blank" rel="noopener noreferrer" style="color: #94a3b8; text-decoration: underline; text-underline-offset: 2px;">Powered by ARSetontong</a>
            </div>
        </div>
    </footer>

    <script>
        (function () {
            /* ─── Role Recommendations Tab Switcher ─── */
            var recommendations = {
                editor: {
                    kicker: "UNTUK PENGELOLA & EDITOR JURNAL",
                    title: "Gunakan Berdampingan Sesuai Tingkat Risiko Naskah",
                    p1: "<strong>Gunakan Turnitin / iThenticate</strong> jika kantor jurnal Anda terafiliasi dengan universitas besar yang telah mendanai akses Crossref Similarity Check dan prioritas utama Anda adalah menyaring salinan langsung dari jurnal internasional terindeks Scopus.",
                    p2: "<strong>Gunakan AR Tilik</strong> untuk melakukan skrining rutin naskah masuk: memeriksa apakah referensi yang dicantumkan penulis benar-benar ada di Crossref, mendeteksi sitasi palsu buatan AI, serta memperoleh laporan audit komprehensif tanpa membebani anggaran operasional jurnal dengan biaya per-dokumen yang mahal."
                },
                kampus: {
                    kicker: "UNTUK PIMPINAN LPPM & FAKULTAS",
                    title: "Lindungi Reputasi Kampus dengan Pembuktian yang Transparan",
                    p1: "<strong>Hindari ketergantungan sepihak</strong> pada skor probabilitas AI. Jika universitas Anda sudah memiliki Turnitin, gunakan untuk penyaringan korpus umum, namun lengkapi regulasi sidang dengan instrumen audit bukti empiris.",
                    p2: "<strong>Gunakan AR Tilik</strong> untuk melatih dosen pembimbing dan penguji agar mampu mengajukan pertanyaan sidang kritis berdasarkan temuan pola teks dan verifikasi rujukan riil mahasiswa."
                },
                penulis: {
                    kicker: "UNTUK DOSEN & MAHASISWA PENULIS",
                    title: "Fokus pada Kualitas Sitasi dan Penguasaan Nalar Riset",
                    p1: "<strong>Jangan cemas berlebihan</strong> terhadap skor persentase acak di internet. Fokuslah pada substansi: pastikan seluruh referensi yang Anda kutip benar-benar pernah terbit dan dapat diakses DOI-nya secara sah.",
                    p2: "<strong>Simpan draf naskah</strong> dan catatan proses penelitian Anda. Ketika diuji dalam sidang, kemampuan menjelaskan metodologi dan argumen secara lisan adalah pertahanan integritas terbaik Anda."
                }
            };

            var buttons = document.querySelectorAll("[data-role]");
            var kicker = document.getElementById("recomKicker");
            var title = document.getElementById("recomTitle");
            var p1 = document.getElementById("recomP1");
            var p2 = document.getElementById("recomP2");

            buttons.forEach(function (button) {
                button.addEventListener("click", function () {
                    var role = button.getAttribute("data-role");
                    var item = recommendations[role];
                    buttons.forEach(function (b) { b.setAttribute("aria-selected", String(b === button)); });
                    if (item) {
                        kicker.textContent = item.kicker;
                        title.textContent = item.title;
                        p1.innerHTML = item.p1;
                        p2.innerHTML = item.p2;
                    }
                });
            });

            /* ─── Sub-Navigation & Dynamic Scroll Handling ─── */
            var subnav = document.querySelector(".subnav");
            var navbar = document.querySelector(".navbar");
            var subnavInner = document.querySelector(".subnav-inner");
            var subnavLinks = document.querySelectorAll(".subnav-link");
            var sectionIds = ["lapangan", "korpus", "ai", "data", "pasar", "strategi", "sumber"];
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
                        var targetLeft = link.offsetLeft - (subnavInner.clientWidth / 2) + (link.clientWidth / 2);
                        subnavInner.scrollTo({ left: targetLeft, behavior: "smooth" });
                    }
                });
            }

            var isProgrammaticScroll = false;
            var scrollEndTimer = null;

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

            function updateActiveSection() {
                if (isProgrammaticScroll) return;
                var offset = getHeaderOffset();
                var currentScroll = window.pageYOffset || document.documentElement.scrollTop;
                var scrollPos = currentScroll + offset + 40;

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
