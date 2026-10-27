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
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:alt" content="Benchmark Terbuka AR Tilik">

    <!-- Twitter / X Cards -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:site" content="@ARSetontong">
    <meta name="twitter:creator" content="@ARSetontong">
    <meta name="twitter:title" content="Benchmark Terbuka Naskah Akademik Indonesia — AR Tilik">
    <meta name="twitter:description" content="Publikasi hasil uji empiris terbuka, matriks akurasi, dan transparansi 14 kasus titik kegagalan pada korpus akademik Indonesia.">
    <meta name="twitter:image" content="<?= base_url('images/og-artilik.png') ?>">

    <!-- Schema.org JSON-LD Structured Data -->
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@graph": [
        {
          "@type": "TechArticle",
          "@id": "<?= base_url('benchmark#article') ?>",
          "headline": "Benchmark Terbuka Naskah Akademik Bahasa Indonesia — Evaluasi Empiris AR Tilik",
          "alternativeHeadline": "Publikasi Hasil Evaluasi Empiris, Matriks Konfusi, dan 14 Titik Kegagalan Mesin pada Korpus Naskah Akademik Indonesia",
          "description": "Hasil evaluasi empiris terbuka AR Tilik pada korpus naskah akademik Bahasa Indonesia. Transparansi penuh: akurasi kemiripan tekstual, deteksi konteks sitasi, indikator stylometri, dan publikasi 14 kasus mismatch apa adanya.",
          "inLanguage": "id-ID",
          "mainEntityOfPage": "<?= base_url('benchmark') ?>",
          "url": "<?= base_url('benchmark') ?>",
          "image": "<?= base_url('images/og-artilik.png') ?>",
          "datePublished": "2026-10-08T08:00:00+07:00",
          "dateModified": "<?= date('c') ?>",
          "author": {
            "@type": "Organization",
            "name": "Laboratorium Evaluasi AI AR Tilik",
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
            "Benchmark Plagiarisme Indonesia",
            "Evaluasi Empiris Similarity Checker",
            "Akurasi Deteksi Plagiasi",
            "Stylometri Forensik Teks",
            "AR Tilik"
          ]
        },
        {
          "@type": "BreadcrumbList",
          "@id": "<?= base_url('benchmark#breadcrumb') ?>",
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
              "name": "Benchmark Terbuka",
              "item": "<?= base_url('benchmark') ?>"
            }
          ]
        }
      ]
    }
    </script>

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
            display: inline-flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            white-space: nowrap;
            flex-shrink: 0;
        }
        .brand-logo {
            width: 36px;
            height: 36px;
            object-fit: contain;
            border-radius: 8px;
            flex-shrink: 0;
        }
        .brand-title {
            font-size: 19px;
            font-weight: 800;
            color: var(--dark);
            letter-spacing: -0.5px;
            white-space: nowrap;
            line-height: 1.2;
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
            white-space: nowrap;
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
            white-space: nowrap;
        }
        .nav-links a:hover, .nav-links a.active { color: var(--primary); }
        .nav-actions {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-shrink: 0;
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
            white-space: nowrap;
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
            white-space: nowrap;
        }
        .btn-nav-primary:hover { background: var(--primary-dark); transform: translateY(-1px); }

        /* Hamburger Toggle Button */
        .hamburger-btn {
            display: none;
            width: 38px;
            height: 38px;
            border-radius: 10px;
            background: #f8fafc;
            border: 1px solid var(--border);
            cursor: pointer;
            padding: 9px 8px;
            flex-direction: column;
            justify-content: space-between;
            align-items: center;
            transition: all 0.2s ease;
            flex-shrink: 0;
        }
        .hamburger-btn:hover { background: #f1f5f9; border-color: #cbd5e1; }
        .hamburger-btn span {
            display: block;
            width: 20px;
            height: 2px;
            background: var(--dark);
            border-radius: 2px;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            transform-origin: center;
        }
        .hamburger-btn.active span:nth-child(1) {
            transform: translateY(6.5px) rotate(45deg);
            background: var(--primary);
        }
        .hamburger-btn.active span:nth-child(2) {
            opacity: 0;
            transform: scaleX(0);
        }
        .hamburger-btn.active span:nth-child(3) {
            transform: translateY(-6.5px) rotate(-45deg);
            background: var(--primary);
        }

        /* Mobile Nav Drawer & Overlay */
        .mobile-nav-overlay {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(4px);
            -webkit-backdrop-filter: blur(4px);
            z-index: 1040;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.3s ease, visibility 0.3s ease;
        }
        .mobile-nav-overlay.active {
            opacity: 1;
            visibility: visible;
        }
        .mobile-nav-drawer {
            position: fixed;
            top: 0;
            right: 0;
            bottom: 0;
            width: min(88vw, 360px);
            background: #ffffff;
            z-index: 1050;
            box-shadow: -8px 0 32px rgba(15, 23, 42, 0.2);
            transform: translateX(100%);
            visibility: hidden;
            transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1), visibility 0.3s ease;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        .mobile-nav-drawer.active {
            transform: translateX(0);
            visibility: visible;
        }
        .mobile-drawer-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 14px 18px;
            border-bottom: 1px solid var(--border);
            background: #f8fafc;
        }
        .mobile-drawer-close {
            width: 34px;
            height: 34px;
            border-radius: 8px;
            border: 1px solid var(--border);
            background: #ffffff;
            color: var(--text-muted);
            font-size: 22px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.15s ease;
            line-height: 1;
        }
        .mobile-drawer-close:hover {
            background: #fee2e2;
            color: #ef4444;
            border-color: #fca5a5;
        }
        .mobile-drawer-body {
            flex: 1;
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
            padding: 18px;
        }
        .mobile-nav-group-title {
            font-size: 11px;
            font-weight: 800;
            color: var(--text-muted);
            letter-spacing: 0.6px;
            text-transform: uppercase;
            margin-bottom: 10px;
        }
        .mobile-nav-list {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 6px;
        }
        .mobile-nav-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 12px;
            border-radius: 10px;
            background: #f8fafc;
            border: 1px solid transparent;
            transition: all 0.15s ease;
        }
        .mobile-nav-item:hover {
            background: #f1f5f9;
            border-color: var(--border);
        }
        .mobile-nav-item.active {
            background: #eef2ff;
            border-color: #c7d2fe;
        }
        .mobile-nav-item.active strong {
            color: var(--primary);
        }
        .mobile-nav-item .item-icon {
            font-size: 18px;
            flex-shrink: 0;
        }
        .mobile-nav-item .item-text {
            display: flex;
            flex-direction: column;
        }
        .mobile-nav-item .item-text strong {
            font-size: 13.5px;
            font-weight: 700;
            color: var(--dark);
            line-height: 1.3;
        }
        .mobile-nav-item .item-text small {
            font-size: 11.5px;
            color: var(--text-muted);
            line-height: 1.25;
            margin-top: 2px;
        }
        .mobile-jump-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
        }
        .mobile-jump-chip {
            display: inline-flex;
            align-items: center;
            padding: 6px 11px;
            border-radius: 9999px;
            font-size: 12px;
            font-weight: 600;
            background: #f1f5f9;
            color: var(--text-muted);
            border: 1px solid var(--border);
            transition: all 0.15s ease;
        }
        .mobile-jump-chip:hover {
            background: #e2e8f0;
            color: var(--dark);
        }
        .mobile-jump-chip.mismatch-chip {
            background: #fff1f2;
            border-color: #fecdd3;
            color: var(--accent-rose);
            font-weight: 700;
        }

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

        @media (max-width: 992px) {
            .nav-links { display: none; }
            .hamburger-btn { display: flex; }
            .btn-desktop-only { display: none !important; }
            .navbar { padding: 8px 0; }
            .nav-inner { gap: 10px; }
            .brand { gap: 8px; }
            .brand-logo { width: 32px; height: 32px; }
            .brand-title { font-size: 17px; }
            .btn-nav-back { padding: 6px 12px; font-size: 12px; }
        }

        @media (max-width: 768px) {
            .subnav { display: none; }
            html { scroll-padding-top: 75px; }
            .hero-wrap { padding-top: 75px; }
            .stats-grid { margin-top: 16px; }
            .section-block { padding: 24px 16px; }
        }

        @media (max-width: 480px) {
            .container { padding: 0 14px; }
            .navbar { padding: 7px 0; }
            .nav-inner { gap: 8px; }
            .brand { gap: 6px; }
            .brand-logo { width: 28px; height: 28px; border-radius: 6px; }
            .brand-title { font-size: 15.5px; }
            .brand-badge { font-size: 10px; padding: 2px 6px; }
            .btn-nav-back { padding: 5px 9px; font-size: 11px; gap: 4px; }
            .hamburger-btn { width: 34px; height: 34px; padding: 8px 7px; border-radius: 8px; }
            .hamburger-btn span { width: 17px; height: 2px; }
            .hamburger-btn.active span:nth-child(1) { transform: translateY(5px) rotate(45deg); }
            .hamburger-btn.active span:nth-child(3) { transform: translateY(-5px) rotate(-45deg); }
        }

        @media (max-width: 360px) {
            .brand-badge { display: none; }
            .brand-title { font-size: 14.5px; }
            .btn-nav-back .btn-nav-text { display: none; }
            .btn-nav-back { padding: 5px 8px; }
        }
    </style>
</head>
<body>

    <!-- ─── Primary Header Navbar ─── -->
    <header class="navbar">
        <div class="container nav-inner">
            <a href="<?= base_url('/') ?>" class="brand">
                <img src="<?= base_url('images/logo.png') ?>" alt="AR Tilik Logo" class="brand-logo" onerror="this.src='data:image/svg+xml;utf8,<svg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 40 40\'><rect width=\'40\' height=\'40\' rx=\'8\' fill=\'%234338ca\'/><text x=\'50%25\' y=\'55%25\' dominant-baseline=\'middle\' text-anchor=\'middle\' fill=\'white\' font-family=\'sans-serif\' font-size=\'20\' font-weight=\'bold\'>AR</text></svg>'">
                <span class="brand-title">AR Tilik</span>
                <span class="brand-badge">Riset</span>
            </a>

            <!-- Desktop Nav Links -->
            <ul class="nav-links">
                <li><a href="<?= base_url('/#keunggulan') ?>">Metodologi</a></li>
                <li><a href="<?= base_url('benchmark') ?>" class="active">Benchmark Lokal</a></li>
                <li><a href="<?= base_url('riset/ar-tilik-vs-turnitin') ?>">vs Turnitin</a></li>
                <li><a href="<?= base_url('riset/ar-tilik-vs-zerogpt') ?>">vs ZeroGPT</a></li>
                <li><a href="<?= base_url('integrations/ojs/pdf') ?>" target="_blank">Integrasi OJS</a></li>
            </ul>

            <div class="nav-actions">
                <a href="<?= base_url('/') ?>" class="btn-nav-back" title="Kembali ke Beranda">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
                    <span class="btn-nav-text">Beranda</span>
                </a>
                <a href="<?= base_url('/?auth=register') ?>" class="btn-nav-primary btn-desktop-only">Coba Sekarang</a>

                <!-- Mobile Hamburger Toggle Button -->
                <button type="button" id="hamburgerBtn" class="hamburger-btn" onclick="toggleMobileNav()" aria-label="Buka Menu Navigasi" aria-expanded="false">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>
            </div>
        </div>
    </header>

    <!-- Mobile Nav Overlay & Drawer -->
    <div id="mobileNavOverlay" class="mobile-nav-overlay" onclick="closeMobileNav()"></div>
    <div id="mobileNavDrawer" class="mobile-nav-drawer" role="dialog" aria-modal="true" aria-label="Menu Navigasi Mobile">
        <div class="mobile-drawer-header">
            <div class="brand" style="gap: 8px;">
                <img src="<?= base_url('images/logo.png') ?>" alt="AR Tilik Logo" class="brand-logo" style="width: 28px; height: 28px;">
                <span class="brand-title" style="font-size: 16px;">AR Tilik</span>
                <span class="brand-badge" style="font-size: 10px; padding: 2px 6px;">Riset</span>
            </div>
            <button type="button" class="mobile-drawer-close" onclick="closeMobileNav()" aria-label="Tutup Menu">&times;</button>
        </div>

        <div class="mobile-drawer-body">
            <div class="mobile-nav-group-title">Navigasi Utama &amp; Publikasi</div>
            <ul class="mobile-nav-list">
                <li>
                    <a href="<?= base_url('benchmark') ?>" class="mobile-nav-item active" onclick="closeMobileNav()">
                        <span class="item-icon">🔬</span>
                        <div class="item-text">
                            <strong>Benchmark Naskah Indonesia</strong>
                            <small>80 Kasus Terarah + 14 Mismatch Terbuka</small>
                        </div>
                    </a>
                </li>
                <li>
                    <a href="<?= base_url('riset/ar-tilik-vs-turnitin') ?>" class="mobile-nav-item" onclick="closeMobileNav()">
                        <span class="item-icon">⚖️</span>
                        <div class="item-text">
                            <strong>AR Tilik vs Turnitin</strong>
                            <small>Komparasi Kedaulatan &amp; Hapus Mandiri</small>
                        </div>
                    </a>
                </li>
                <li>
                    <a href="<?= base_url('riset/ar-tilik-vs-zerogpt') ?>" class="mobile-nav-item" onclick="closeMobileNav()">
                        <span class="item-icon">🛡️</span>
                        <div class="item-text">
                            <strong>AR Tilik vs ZeroGPT</strong>
                            <small>Riset Independen False-Positive 80%</small>
                        </div>
                    </a>
                </li>
                <li>
                    <a href="<?= base_url('/#keunggulan') ?>" class="mobile-nav-item" onclick="closeMobileNav()">
                        <span class="item-icon">📐</span>
                        <div class="item-text">
                            <strong>Metodologi &amp; 4 Pilar</strong>
                            <small>N-Gram, Cosine, Pasase, XGBoost</small>
                        </div>
                    </a>
                </li>
                <li>
                    <a href="<?= base_url('integrations/ojs/pdf') ?>" target="_blank" class="mobile-nav-item" onclick="closeMobileNav()">
                        <span class="item-icon">🔌</span>
                        <div class="item-text">
                            <strong>Konektor Integrasi OJS</strong>
                            <small>Panduan Teknis Workflow Redaksi Jurnal</small>
                        </div>
                    </a>
                </li>
            </ul>

            <div class="mobile-nav-group-title" style="margin-top: 20px;">Lompat ke Bagian Benchmark</div>
            <div class="mobile-jump-grid">
                <a href="#ringkasan" class="mobile-jump-chip" onclick="closeMobileNav()">Latar Belakang</a>
                <a href="#dataset" class="mobile-jump-chip" onclick="closeMobileNav()">Komposisi Dataset</a>
                <a href="#gates" class="mobile-jump-chip" onclick="closeMobileNav()">Quality Gates</a>
                <a href="#mismatch" class="mobile-jump-chip mismatch-chip" onclick="closeMobileNav()">14 Mismatch</a>
                <a href="#stylometri" class="mobile-jump-chip" onclick="closeMobileNav()">Stylometri &amp; AI</a>
                <a href="#dualreader" class="mobile-jump-chip" onclick="closeMobileNav()">Dual-Reader</a>
                <a href="#onpremise" class="mobile-jump-chip" onclick="closeMobileNav()">On-Premise &amp; Privasi</a>
                <a href="#unduh" class="mobile-jump-chip" onclick="closeMobileNav()">Unduh Dataset</a>
            </div>

            <div style="margin-top: 24px; padding-top: 20px; border-top: 1px solid var(--border); display: flex; flex-direction: column; gap: 10px;">
                <a href="<?= base_url('/?auth=register') ?>" class="btn-primary" style="justify-content: center; padding: 12px; font-size: 13.5px;" onclick="closeMobileNav()">
                    <span>Coba AR Tilik Sekarang</span>
                </a>
                <a href="<?= base_url('/') ?>" class="btn-secondary" style="justify-content: center; padding: 10px; font-size: 13px;" onclick="closeMobileNav()">
                    <span>Kembali ke Halaman Beranda</span>
                </a>
            </div>
        </div>
    </div>

    <!-- ─── Sticky Subnav Quick Links ─── -->
    <nav class="subnav">
        <div class="container subnav-inner">
            <span style="font-size: 12px; font-weight: 800; color: var(--dark); margin-right: 8px;">NAVIGASI RISET:</span>
            <a href="#ringkasan" class="subnav-link">Ringkasan Eksekutif</a>
            <a href="#dataset" class="subnav-link">Komposisi Dataset</a>
            <a href="#gates" class="subnav-link">Matriks Quality Gates</a>
            <a href="#mismatch" class="subnav-link" style="color: var(--accent-rose); font-weight: 700;">14 Kasus Mismatch</a>
            <a href="#semantik" class="subnav-link" style="color: #4f46e5; font-weight: 700;">Lensa Semantik (MiniLM-L12)</a>
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
                <a href="<?= base_url('datasets/artilik_indonesia_targeted_contextual_80_cases.json') ?>" download="artilik_indonesia_targeted_contextual_80_cases.json" target="_blank" class="btn-primary">
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
                <div class="stat-label">Deteksi Konteks Sitasi (Layer B)</div>
                <div class="stat-desc">80 dari 80 kasus sitasi, kutipan langsung, dan blok kutipan teratribusi akurat (Gate V11-B1/B2).</div>
            </div>
            <div class="stat-card">
                <div class="stat-num emerald">100.0%</div>
                <div class="stat-label">Atribusi Izin Akademik</div>
                <div class="stat-desc">Pembedaan teks berizin (cited/quoted) vs tanpa sitasi tanpa kebocoran logika filter.</div>
            </div>
            <div class="stat-card">
                <div class="stat-num emerald">0.23 ms</div>
                <div class="stat-label">Rata-rata Overhead Parsing</div>
                <div class="stat-desc">SLA P95: 0.186 ms per pasase, menjamin respons cepat pada submisi OJS massal.</div>
            </div>
            <div class="stat-card">
                <div class="stat-num amber">14 Kasus</div>
                <div class="stat-label">Mismatch Terbuka (17.5%)</div>
                <div class="stat-desc">Keputusan Layer A (v1.0 string vs ground truth): 10 metodologi boilerplate + 4 boundary threshold.</div>
            </div>
        </div>

        <!-- Clarification Banner between Layer B (Context Attribution) vs Layer A (Decision Invariance) -->
        <div class="callout-box callout-indigo" style="margin-bottom: 32px; border-left: 4px solid var(--primary); background: #f8faff; padding: 18px 22px; border-radius: 8px;">
            <div class="callout-icon" style="font-size: 24px; margin-right: 14px;">ℹ️</div>
            <div class="callout-text">
                <h4 style="margin: 0 0 6px 0; font-size: 15px; color: var(--dark); font-weight: 700;">Catatan Kejujuran Metrik: Memahami Skor 100% vs 14 Mismatch (17,5%)</h4>
                <p style="margin: 0; font-size: 13.5px; line-height: 1.65; color: var(--text);">
                    Agar tidak membingungkan pembaca sekilas, kedua metrik di atas menguji dua lapisan yang berbeda:<br>
                    &bull; <strong>100.0% (80/80 Kasus)</strong> adalah <em>Skor Atribusi Konteks Sitasi (Layer B Engine)</em>: mesin AR Tilik 100% berhasil mengenali tanda kurung sitasi, nama penulis, tahun rilis, dan tanda kutip ganda pada naskah Indonesia tanpa ada yang terlewat (Gate V11-B1 &amp; B2).<br>
                    &bull; <strong>14 Kasus Mismatch (17.5% / 66 dari 80 lolos kesepakatan keputusan)</strong> adalah <em>Hasil Uji Keputusan Klasifikasi Baseline (Layer A String Matcher v1.0)</em>: mesin lama mencatat 10 kesamaan leksikal pada frasa metodologi baku (seperti <em>&quot;populasi dan sampel penelitian...&quot;</em>) serta 4 variasi parafrasa yang secara kuratorial diizinkan oleh validator manusia (Gate V11-F). Kami menolak menutup-nutupi mismatch ini demi integritas ilmiah.
                </p>
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

        <!-- Section 4B: Kalibrasi Empiris Lensa Semantik (MiniLM-L12) -->
        <section id="semantik" class="section-block" style="border-left: 4px solid #4f46e5;">
            <div class="sec-head">
                <div class="sec-tag" style="color: #4f46e5;">Evaluasi Lapisan Kedua — Mesin Vektor Semantik</div>
                <h2 class="sec-title">Kalibrasi Empiris Lensa Semantik: Menghalau Parafrase Tanpa Merusak Skor Leksikal</h2>
                <p class="sec-desc">
                    Evaluasi empiris komprehensif pada 80 kasus terarah menggunakan model multibahasa on-premise <code>paraphrase-multilingual-MiniLM-L12-v2</code> (118M parameter, 384 dimensi) dan basis data vektor Qdrant.
                </p>
            </div>

            <!-- Filosofi Desain -->
            <div class="callout-box" style="background: #f5f3ff; border: 1px solid #ddd6fe; border-left: 4px solid #6366f1;">
                <div class="callout-icon">💡</div>
                <div class="callout-text">
                    <h4 style="margin: 0 0 6px 0; font-size: 15px; color: #312e81; font-weight: 700;">Prinsip Desain: Semantik Sebagai Lapisan Kedua (Re-Ranker Zona Abu-Abu), Bukan Pengganti</h4>
                    <p style="margin: 0; font-size: 13.5px; line-height: 1.65; color: #4338ca;">
                        Mesin leksikal (shingling 5-gram + Jaccard) tetap menjadi tulang punggung utama AR Tilik yang deterministik dan dapat diverifikasi kata demi kata. Penanaman vektor semantik bekerja secara eksklusif sebagai <strong>lensa pemeriksa ulang pada zona abu-abu</strong> (skor leksikal moderat 5%–25% atau naskah dengan anomali parafrase). Ini menghemat komputasi, menjamin waktu respons sub-detik pada CPU server, dan menjaga privasi naskah 100% lokal on-premise tanpa ketergantungan API pihak ketiga.
                    </p>
                </div>
            </div>

            <!-- Tabel 1: Kalibrasi Tiga Ambang Semantik -->
            <h3 style="font-size: 17px; font-weight: 800; color: var(--dark); margin: 28px 0 12px 0;">1. Kalibrasi Tiga Ambang Semantik &amp; Garansi Anti-Salah-Tuduh (0.0% False Positive)</h3>
            <p style="font-size: 14px; color: var(--text); line-height: 1.6; margin-bottom: 14px;">
                Kami mengevaluasi performa klasifikasi pada tiga batas ambang kemiripan kosinus (0.70, 0.75, dan 0.80) terhadap 60 variasi parafrase serta 20 dokumen kontrol bersih dari berbagai disiplin ilmu (Kedokteran, Hukum, Geodesi, Ekonomi, Teknik Kimia, Sosiologi, dll.):
            </p>

            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Ambang Kosinus</th>
                            <th>Karakter Ambang</th>
                            <th style="text-align: center;">Presisi</th>
                            <th style="text-align: center;">Recall</th>
                            <th style="text-align: center;">F1-Score</th>
                            <th style="text-align: center; color: var(--accent-emerald);">FP Dokumen Bersih</th>
                            <th style="text-align: center;">Akurasi Total</th>
                            <th>Status Kebijakan Mesin</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><strong>0.70</strong> (70.0%)</td>
                            <td>Agresif / Sensitif</td>
                            <td style="text-align: center; font-weight: 700;">100.0%</td>
                            <td style="text-align: center; font-weight: 700;">73.3%</td>
                            <td style="text-align: center; font-mono-code;">0.8462</td>
                            <td style="text-align: center; font-weight: 800; color: var(--accent-emerald);">0.0% (0/20)</td>
                            <td style="text-align: center;">80.0%</td>
                            <td><span style="font-size: 11px; padding: 2px 8px; border-radius: 4px; background: #e0e7ff; color: #3730a3; font-weight: 700;">Mode Penyelidikan Luas</span></td>
                        </tr>
                        <tr style="background: #f8fafc; border: 2px solid #6366f1;">
                            <td><strong style="color: #4f46e5;">0.75</strong> (75.0%)</td>
                            <td><strong style="color: #4f46e5;">Seimbang (Rekomendasi)</strong></td>
                            <td style="text-align: center; font-weight: 800; color: #4f46e5;">100.0%</td>
                            <td style="text-align: center; font-weight: 800; color: #4f46e5;">61.7%</td>
                            <td style="text-align: center; font-mono-code; font-weight: 700;">0.7629</td>
                            <td style="text-align: center; font-weight: 800; color: var(--accent-emerald);">0.0% (0/20)</td>
                            <td style="text-align: center; font-weight: 700;">71.2%</td>
                            <td><span style="font-size: 11px; padding: 2px 8px; border-radius: 4px; background: #dcfce7; color: #166534; font-weight: 800;">Ambang Resmi Operasional</span></td>
                        </tr>
                        <tr>
                            <td><strong>0.80</strong> (80.0%)</td>
                            <td>Ketat / Konservatif</td>
                            <td style="text-align: center; font-weight: 700;">100.0%</td>
                            <td style="text-align: center; font-weight: 700;">53.3%</td>
                            <td style="text-align: center; font-mono-code;">0.6957</td>
                            <td style="text-align: center; font-weight: 800; color: var(--accent-emerald);">0.0% (0/20)</td>
                            <td style="text-align: center;">65.0%</td>
                            <td><span style="font-size: 11px; padding: 2px 8px; border-radius: 4px; background: #f1f5f9; color: #475569; font-weight: 700;">Ambang Sangat Ketat</span></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Tabel 2: Perbandingan Daya Tangkap Leksikal vs Semantik -->
            <h3 style="font-size: 17px; font-weight: 800; color: var(--dark); margin: 28px 0 12px 0;">2. Perbandingan Daya Tangkap Leksikal 5-Gram vs Lensa Semantik (MiniLM-L12)</h3>
            <p style="font-size: 14px; color: var(--text); line-height: 1.6; margin-bottom: 14px;">
                Tabel di bawah memperlihatkan lompatan kemampuan deteksi ketika naskah dimutasi dengan berbagai tingkat parafrase. Mesin leksikal konvensional (v1.0) runtuh seketika saat sinonim dan susunan kalimat diubah, sementara Lensa Semantik tetap mengenali proposisi gagasan:
            </p>

            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Tingkat Pengujian Parafrase</th>
                            <th>Karakteristik Teks Submisi</th>
                            <th style="text-align: center;">Rata-rata Leksikal (v1.0)</th>
                            <th style="text-align: center;">Rata-rata Semantik (MiniLM-L12)</th>
                            <th style="text-align: center;">Tingkat Deteksi (@ 0.75)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><strong>Parafrase Ringan (Light)</strong> (15 Kasus)</td>
                            <td>Substitusi sinonim kata kunci &amp; konversi aktif-pasif</td>
                            <td style="text-align: center; color: var(--accent-rose); font-weight: 700;">0.2%</td>
                            <td style="text-align: center; color: #4f46e5; font-weight: 800;">88.9%</td>
                            <td style="text-align: center; font-weight: 800; color: var(--accent-emerald);">93.3% (14/15)</td>
                        </tr>
                        <tr>
                            <td><strong>Parafrase Sedang (Moderate)</strong> (15 Kasus)</td>
                            <td>Restrukturisasi klausa, pemadatan proposisi &amp; variasi sintaksis</td>
                            <td style="text-align: center; color: var(--accent-rose); font-weight: 700;">0.0%</td>
                            <td style="text-align: center; color: #4f46e5; font-weight: 800;">76.0%</td>
                            <td style="text-align: center; font-weight: 800; color: #0284c7;">53.3% (8/15)</td>
                        </tr>
                        <tr>
                            <td><strong>Parafrase Agresif (Deep)</strong> (15 Kasus)</td>
                            <td>Perombakan total kosakata, gaya metaforis / sintesis AI</td>
                            <td style="text-align: center; color: var(--accent-rose); font-weight: 700;">0.0%</td>
                            <td style="text-align: center; color: #4f46e5; font-weight: 800;">67.2%</td>
                            <td style="text-align: center; font-weight: 700; color: var(--accent-amber);">6.7% (1/15)*</td>
                        </tr>
                        <tr style="background: #f0fdf4;">
                            <td><strong>Kontrol Dokumen Bersih</strong> (20 Kasus)</td>
                            <td>Naskah orisinal independen antar bidang ilmu</td>
                            <td style="text-align: center; font-weight: 700;">0.0%</td>
                            <td style="text-align: center; color: var(--accent-emerald); font-weight: 800;">30.7%</td>
                            <td style="text-align: center; font-weight: 800; color: var(--accent-emerald);">0.0% Alarm Palsu</td>
                        </tr>
                        <tr>
                            <td><strong>Uji Lintas Bahasa (Cross-Lingual)</strong> (15 Kasus)</td>
                            <td>Indonesia vs Inggris, Arab, Mandarin, Belanda, Jerman</td>
                            <td style="text-align: center; color: var(--accent-rose); font-weight: 700;">0.0%</td>
                            <td style="text-align: center; color: #4f46e5; font-weight: 800;">85.3%</td>
                            <td style="text-align: center; font-weight: 800; color: var(--accent-emerald);">93.3% (14/15)</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <p style="font-size: 12px; color: var(--muted); margin-top: 6px; font-style: italic;">
                *Catatan ilmiah: Pada ambang 0.70, daya tangkap parafrase agresif meningkat menjadi 33.3%. Namun demi menjaga zero false positive pada dokumen bersih, AR Tilik memilih ambang seimbang 0.75 secara default.
            </p>

            <!-- Tabel 3: Validasi Lintas Bahasa per Pasangan Bahasa -->
            <h3 style="font-size: 17px; font-weight: 800; color: var(--dark); margin: 28px 0 12px 0;">3. Validasi Lintas Bahasa (Zero-Shot Cross-Lingual) Tanpa Mesin Penerjemah</h3>
            <p style="font-size: 14px; color: var(--text); line-height: 1.6; margin-bottom: 14px;">
                Karena model representasi MiniLM-L12 bersifat multibahasa, teks berbahasa Indonesia dan teks bahasa asing dipetakan ke dalam satu ruang vektor bersama. Kami memvalidasi setiap bahasa secara terpisah tanpa klaim sepihak:
            </p>

            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Pasangan Bahasa</th>
                            <th>Representasi Kasus Uji</th>
                            <th style="text-align: center;">Rata-rata Kosinus</th>
                            <th style="text-align: center;">Kemiripan Makna</th>
                            <th style="text-align: center;">Lolos Ambang 0.75</th>
                            <th>Status Validasi AR Tilik</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><strong>Indonesia ↔ Mandarin (ZH)</strong></td>
                            <td>NLP Transformer, Nutrisi Klinis, Inverter Surya</td>
                            <td style="text-align: center; font-mono-code; font-weight: 700;">0.9090</td>
                            <td style="text-align: center; font-weight: 800; color: #166534;">90.9%</td>
                            <td style="text-align: center; font-weight: 800; color: var(--accent-emerald);">3 / 3 (100%)</td>
                            <td><span style="font-size: 11px; padding: 2px 8px; border-radius: 4px; background: #dcfce7; color: #166534; font-weight: 800;">Tervalidasi Kuat</span></td>
                        </tr>
                        <tr>
                            <td><strong>Indonesia ↔ Arab (AR)</strong></td>
                            <td>Arsitektur NLP, Epidemiologi Diabetes, Pembangkit Fotovoltaik</td>
                            <td style="text-align: center; font-mono-code; font-weight: 700;">0.8830</td>
                            <td style="text-align: center; font-weight: 800; color: #166534;">88.3%</td>
                            <td style="text-align: center; font-weight: 800; color: var(--accent-emerald);">3 / 3 (100%)</td>
                            <td><span style="font-size: 11px; padding: 2px 8px; border-radius: 4px; background: #dcfce7; color: #166534; font-weight: 800;">Tervalidasi Kuat</span></td>
                        </tr>
                        <tr>
                            <td><strong>Indonesia ↔ Jerman (DE)</strong></td>
                            <td>Komposit Geopolimer, Kebijakan Suku Bunga, Model Bahasa</td>
                            <td style="text-align: center; font-mono-code; font-weight: 700;">0.8661</td>
                            <td style="text-align: center; font-weight: 800; color: #166534;">86.6%</td>
                            <td style="text-align: center; font-weight: 800; color: var(--accent-emerald);">3 / 3 (100%)</td>
                            <td><span style="font-size: 11px; padding: 2px 8px; border-radius: 4px; background: #dcfce7; color: #166534; font-weight: 800;">Tervalidasi Kuat</span></td>
                        </tr>
                        <tr>
                            <td><strong>Indonesia ↔ Belanda (NL)</strong></td>
                            <td>Peradilan Konstitusi, Inflasi Komoditas, Ekologi Laut Tropis</td>
                            <td style="text-align: center; font-mono-code; font-weight: 700;">0.8050</td>
                            <td style="text-align: center; font-weight: 800; color: #166534;">80.5%</td>
                            <td style="text-align: center; font-weight: 800; color: var(--accent-emerald);">3 / 3 (100%)</td>
                            <td><span style="font-size: 11px; padding: 2px 8px; border-radius: 4px; background: #dcfce7; color: #166534; font-weight: 800;">Tervalidasi Kuat</span></td>
                        </tr>
                        <tr>
                            <td><strong>Indonesia ↔ Inggris (EN)</strong></td>
                            <td>Attention Mechanism, Smart Inverter, Moneter Makro</td>
                            <td style="text-align: center; font-mono-code; font-weight: 700;">0.8009</td>
                            <td style="text-align: center; font-weight: 800; color: #166534;">80.1%</td>
                            <td style="text-align: center; font-weight: 800; color: #0284c7;">2 / 3 (66.7%)*</td>
                            <td><span style="font-size: 11px; padding: 2px 8px; border-radius: 4px; background: #e0f2fe; color: #0369a1; font-weight: 800;">Tervalidasi Operasional</span></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <p style="font-size: 12px; color: var(--muted); margin-top: 6px; font-style: italic;">
                *Catatan: Pada kasus SEM-XLN-EN3 (suku bunga moneter), skor kosinus tercatat 0.6211 karena pemadatan frasa yang terlampau ringkas; kasus ini terdeteksi pada ambang 0.60.
            </p>

            <!-- 4. Transparansi Kasus Batas / Kegagalan Semantik Terbuka -->
            <h3 style="font-size: 17px; font-weight: 800; color: var(--dark); margin: 28px 0 12px 0;">4. Transparansi Titik Kegagalan Mesin: Di Mana Lensa Semantik Mengalami Batas?</h3>
            <p style="font-size: 14px; color: var(--text); line-height: 1.6; margin-bottom: 14px;">
                Sesuai prinsip transparansi radikal AR Tilik, kami tidak menyembunyikan keterbatasan algoritma. Pada ambang operasional 0.75, terdapat 23 kasus parafrase (terutama kelompok agresif) yang lolos di bawah ambang deteksi karena perubahan perumpamaan dan terminologi naratif populer:
            </p>

            <div class="callout-box" style="background: #fffbeb; border: 1px solid #fef3c7; border-left: 4px solid #f59e0b; margin-bottom: 16px;">
                <div class="callout-icon">🔍</div>
                <div class="callout-text">
                    <h4 style="margin: 0 0 6px 0; font-size: 14.5px; color: #78350f; font-weight: 700;">Temuan Ilmiah: Mengapa Model MiniLM-L12 Meloloskan Parafrase Agresif?</h4>
                    <p style="margin: 0; font-size: 13px; line-height: 1.6; color: #92400e;">
                        Ketika sebuah naskah teknis diubah menjadi narasi populer atau metafora awam (misalnya istilah kedokteran <em>"terapi sel CAR-T merekayasa limfosit T..."</em> diubah menjadi <em>"pasukan imun tubuh dilatih ulang di tabung reaksi dibekali radar..."</em> [SEM-AGR-008]), jarak kosinus vektor turun drastis ke angka <strong>0.5120</strong>. Menurunkan ambang ke 0.50 akan meningkatkan recall, namun mengorbankan kepastian hukum karena dapat menimbulkan alarm palsu pada teks berbeda yang kebetulan menggunakan kiasan serupa. AR Tilik dengan tegas menolak kompromi tersebut demi melindungi mahasiswa dan akademisi dari salah tuduh.
                    </p>
                </div>
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
                            <td><strong>Kebijakan Penyerapan Repositori Default</strong></td>
                            <td><span class="badge badge-pass">✓ Ya, Naskah Tidak Diserap Otomatis</span></td>
                            <td>Submisi standar masuk ke repositori institusi/global untuk pencocokan silang</td>
                        </tr>
                        <tr>
                            <td><strong>Mekanisme Penghapusan Repositori</strong></td>
                            <td><span class="badge badge-pass">✓ Hapus Mandiri Sekali Klik (Self-Service)</span><br><small style="color:var(--muted);">Naskah, berkas sementara, dan sidik jari dihapus seketika hingga level disk server tanpa tiket bantuan.</small></td>
                            <td><strong>Prosedur Berjenjang Manual</strong><br><small style="color:var(--muted);">Berdasarkan dokumentasi resmi Turnitin, penghapusan permanen dari repositori tidak dapat dilakukan mandiri; memerlukan persetujuan berjenjang: Instruktur &rarr; Administrator Institusi &rarr; Tiket Dukungan Turnitin.</small></td>
                        </tr>
                        <tr>
                            <td><strong>Kedaulatan &amp; Pembersihan Jejak Fisik</strong></td>
                            <td><span class="badge badge-pass">✓ Hard-delete Disk &amp; Chunks DB</span><br><small style="color:var(--muted);">Terverifikasi end-to-end sampai level berkas fisik dan token biner di server storage lokal.</small></td>
                            <td><strong>Kebijakan Retensi Akun Institusi</strong><br><small style="color:var(--muted);">Tergantung konfigurasi lisensi institusi; file umumnya diindeks dalam database arsip internal.</small></td>
                        </tr>
                        <tr>
                            <td><strong>Dukungan On-Premise / Private Cloud</strong></td>
                            <td><span class="badge badge-pass">✓ Docker Compose (PHP 8.5 + Python 3.14)</span></td>
                            <td>SaaS Cloud Only (Penyimpanan berada pada infrastruktur server global)</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="callout-box callout-indigo" style="margin-top: 20px; border-left: 4px solid var(--primary); background: #f8faff; padding: 18px 22px; border-radius: 8px;">
                <div class="callout-icon" style="font-size: 22px; margin-right: 14px;">🔒</div>
                <div class="callout-text">
                    <h4 style="margin: 0 0 6px 0; font-size: 14.5px; color: var(--dark); font-weight: 700;">Akses Repositori &amp; Paket Kontainer On-Premise</h4>
                    <p style="margin: 0 0 8px 0; font-size: 13.5px; line-height: 1.6; color: var(--text);">
                        Repositori kode sumber saat ini berstatus privat. <strong>Akses repositori diberikan sesuai permintaan</strong> bagi institusi perguruan tinggi, pengelola jurnal, dan peneliti untuk kebutuhan verifikasi integritas, audit kedaulatan data, atau implementasi on-premise (air-gapped).
                    </p>
                    <p style="margin: 0; font-size: 13px; color: var(--muted);">
                        Permintaan akses kolaborator dapat diajukan melalui email: <a href="mailto:admin@tilik.arsetontong.top?subject=Permintaan%20Akses%20Repositori%20On-Premise%20AR%20Tilik" style="color: var(--primary); font-weight: 600; text-decoration: underline;">admin@tilik.arsetontong.top</a>
                    </p>
                </div>
            </div>

            <div class="code-box" style="margin-top: 16px;">
# Menjalankan AR Tilik di Pusat Data Internal Kampus (Akses Repositori Diberikan Sesuai Permintaan)
# 1. Ajukan akses kolaborator via email ke admin@tilik.arsetontong.top
# 2. Setelah izin akses diberikan:
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

            <div class="btn-group" style="justify-content: center; flex-wrap: wrap; gap: 12px;">
                <a href="<?= base_url('datasets/artilik_semantic_paraphrase_benchmark.json') ?>" download="artilik_semantic_paraphrase_80_cases.json" target="_blank" class="btn-primary" style="background: #4f46e5;">
                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    <span>Unduh Dataset Parafrase Semantik 80 Kasus (JSON)</span>
                </a>
                <a href="<?= base_url('datasets/artilik_semantic_calibration_report.json') ?>" download="artilik_semantic_calibration_report.json" target="_blank" class="btn-secondary" style="border-color: #4f46e5; color: #4f46e5;">
                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    <span>Unduh Laporan Kalibrasi Semantik (JSON)</span>
                </a>
                <a href="<?= base_url('datasets/artilik_indonesia_targeted_contextual_80_cases.json') ?>" download="artilik_indonesia_targeted_contextual_80_cases.json" target="_blank" class="btn-secondary">
                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    <span>Unduh Dataset Terarah 80 Kasus (JSON)</span>
                </a>
                <a href="<?= base_url('datasets/artilik_indonesia_academic_benchmark_500_cases.json') ?>" download="artilik_indonesia_academic_benchmark_500_cases.json" target="_blank" class="btn-secondary">
                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    <span>Unduh Dataset Baseline 500 Kasus (JSON)</span>
                </a>
            </div>

            <div style="margin-top: 14px; font-size: 13px; color: var(--muted); line-height: 1.8;">
                Tautan alternatif rute controller / API:<br>
                <a href="<?= base_url('benchmark/dataset/semantic_80') ?>" target="_blank" style="color: var(--primary); text-decoration: underline; margin: 0 6px;">/benchmark/dataset/semantic_80</a> |
                <a href="<?= base_url('benchmark/dataset/semantic_report') ?>" target="_blank" style="color: var(--primary); text-decoration: underline; margin: 0 6px;">/benchmark/dataset/semantic_report</a> |
                <a href="<?= base_url('benchmark/dataset/targeted_80') ?>" target="_blank" style="color: var(--primary); text-decoration: underline; margin: 0 6px;">/benchmark/dataset/targeted_80</a> |
                <a href="<?= base_url('benchmark/dataset/v1_500') ?>" target="_blank" style="color: var(--primary); text-decoration: underline; margin: 0 6px;">/benchmark/dataset/v1_500</a>
            </div>

            <div class="code-box" style="max-width: 680px; margin: 24px auto 0 auto; text-align: left;">
# Cara Menjalankan Evaluasi Regresi Mandiri via CLI:
# (Berkas dataset JSON dapat langsung diuji di atas; CLI tool './artilik' dapat diakses setelah repositori diberikan sesuai permintaan)
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

    <script>
        function toggleMobileNav() {
            var drawer = document.getElementById('mobileNavDrawer');
            var overlay = document.getElementById('mobileNavOverlay');
            var btn = document.getElementById('hamburgerBtn');
            if (!drawer || !overlay || !btn) return;
            var isOpen = drawer.classList.contains('active');
            if (isOpen) {
                closeMobileNav();
            } else {
                drawer.classList.add('active');
                overlay.classList.add('active');
                btn.classList.add('active');
                btn.setAttribute('aria-expanded', 'true');
                document.body.style.overflow = 'hidden';
            }
        }

        function closeMobileNav() {
            var drawer = document.getElementById('mobileNavDrawer');
            var overlay = document.getElementById('mobileNavOverlay');
            var btn = document.getElementById('hamburgerBtn');
            if (drawer) drawer.classList.remove('active');
            if (overlay) overlay.classList.remove('active');
            if (btn) {
                btn.classList.remove('active');
                btn.setAttribute('aria-expanded', 'false');
            }
            document.body.style.overflow = '';
        }

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') closeMobileNav();
        });
    </script>
</body>
</html>
