<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AR Tilik — Cek Plagiarisme &amp; Penelaah Kesepadanan Naskah Akademik Mandiri</title>
    
    <!-- Primary SEO Meta Tags -->
    <meta name="description" content="AR Tilik adalah instrumen penelaah kesepadanan naskah akademik &amp; cek plagiarisme mandiri berbasis bukti ilmiah, arsitektur Dual-Lens 5-Gram &amp; TF-IDF, integrasi OJS 3.x, audit sitasi APA/IEEE, serta transparansi keadilan akademik.">
    <meta name="keywords" content="cek plagiasi, cek plagiarisme, similarity checker indonesia, uji kesepadanan naskah, plagiarism checker ojs, anti plagiasi karya ilmiah, turnitin alternatif, cek plagiasi gratis, ojs 3 similarity plugin, ar tilik, arsetontong, integritas akademik">
    <meta name="author" content="ARSetontong">
    <meta name="publisher" content="ARSetontong">
    <meta name="robots" content="index, follow, max-snippet:-1, max-image-preview:large, max-video-preview:-1">
    <link rel="canonical" href="<?= base_url() ?>">
    <meta name="theme-color" content="#4338ca">
    <meta name="language" content="Indonesian">
    <meta name="geo.region" content="ID">
    <meta name="geo.placename" content="Indonesia">

    <!-- Open Graph / Facebook / LinkedIn / WhatsApp -->
    <meta property="og:type" content="website">
    <meta property="og:locale" content="id_ID">
    <meta property="og:site_name" content="AR Tilik">
    <meta property="og:title" content="AR Tilik — Cek Plagiarisme &amp; Penelaah Kesepadanan Naskah Akademik">
    <meta property="og:description" content="Platform penelaah kesepadanan karya ilmiah mandiri berbasis bukti transparan, arsitektur Dual-Lens 5-Gram &amp; TF-IDF, integrasi otomatis OJS 3.x, dan perlindungan privasi naskah.">
    <meta property="og:url" content="<?= base_url() ?>">
    <meta property="og:image" content="<?= base_url('images/og-artilik.png') ?>">
    <meta property="og:image:secure_url" content="<?= base_url('images/og-artilik.png') ?>">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:type" content="image/png">
    <meta property="og:image:alt" content="AR Tilik Academic Writing Similarity Checker">

    <!-- Twitter / X Cards -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:site" content="@ARSetontong">
    <meta name="twitter:creator" content="@ARSetontong">
    <meta name="twitter:title" content="AR Tilik — Cek Plagiarisme &amp; Penelaah Kesepadanan Naskah">
    <meta name="twitter:description" content="Platform penelaah kesepadanan karya ilmiah mandiri berbasis bukti ilmiah, integrasi OJS 3.x, dan transparansi keadilan sitasi.">
    <meta name="twitter:image" content="<?= base_url('images/og-artilik.png') ?>">

    <!-- Schema.org JSON-LD Structured Data -->
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@graph": [
        {
          "@type": "WebSite",
          "@id": "<?= base_url('#website') ?>",
          "url": "<?= base_url() ?>",
          "name": "AR Tilik",
          "alternateName": ["ARTilik", "AR Tilik Similarity Checker"],
          "description": "Sistem Penelaah Kesepadanan Naskah Akademik & Cek Plagiarisme Mandiri Berbasis Bukti Ilmiah Terbuka",
          "inLanguage": "id-ID",
          "publisher": {
            "@id": "<?= base_url('#organization') ?>"
          }
        },
        {
          "@type": "Organization",
          "@id": "<?= base_url('#organization') ?>",
          "name": "ARSetontong",
          "url": "https://www.arsetontong.top",
          "logo": {
            "@type": "ImageObject",
            "url": "<?= base_url('android-chrome-512x512.png') ?>",
            "width": 512,
            "height": 512
          },
          "sameAs": [
            "https://www.arsetontong.top"
          ]
        },
        {
          "@type": "WebApplication",
          "@id": "<?= base_url('#software') ?>",
          "name": "AR Tilik",
          "applicationCategory": "EducationalApplication",
          "operatingSystem": "All modern web browsers",
          "url": "<?= base_url() ?>",
          "offers": {
            "@type": "Offer",
            "price": "0",
            "priceCurrency": "IDR",
            "availability": "https://schema.org/InStock",
            "name": "Kuota Sivitas Akademika Gratis (3 Submisi/Minggu)"
          },
          "featureList": [
            "Dual-Lens 5-Gram & TF-IDF Cosine Similarity Engine",
            "Citation Context Awareness (APA, IEEE, Harvard)",
            "Open Journal Systems (OJS 3.x) Automatic REST Integration",
            "Anti-Cheat Homoglyph & Font Spoofing Shield",
            "Interactive Dual-Reader Side-by-Side Comparison",
            "Cryptographic QR Code Verification Certificate"
          ],
          "author": {
            "@id": "<?= base_url('#organization') ?>"
          },
          "aggregateRating": {
            "@type": "AggregateRating",
            "ratingValue": "<?= number_format($testimonialStats['avg_rating'] ?? 5.0, 1) ?>",
            "ratingCount": "<?= max(1, (int)($testimonialStats['total_reviews'] ?? count($approvedTestimonials))) ?>",
            "reviewCount": "<?= max(1, (int)($testimonialStats['total_reviews'] ?? count($approvedTestimonials))) ?>",
            "bestRating": "5",
            "worstRating": "1"
          }<?php if (!empty($approvedTestimonials)): ?>,
          "review": [
            <?php foreach (array_slice($approvedTestimonials, 0, 5) as $sIdx => $sRev): ?>
            {
              "@type": "Review",
              "author": {
                "@type": "Person",
                "name": "<?= esc($sRev['display_name'] ?? $sRev['author_name']) ?>"
              },
              "reviewRating": {
                "@type": "Rating",
                "ratingValue": "<?= (int)$sRev['rating'] ?>",
                "bestRating": "5",
                "worstRating": "1"
              },
              "reviewBody": "<?= esc(str_replace(['"', "\r", "\n"], ["'", ' ', ' '], $sRev['content'])) ?>"
            }<?= $sIdx < min(4, count($approvedTestimonials) - 1) ? ',' : '' ?>
            <?php endforeach; ?>
          ]
          <?php endif; ?>
        },
        {
          "@type": "FAQPage",
          "@id": "<?= base_url('#faq') ?>",
          "mainEntity": [
            {
              "@type": "Question",
              "name": "Apa perbedaan mendasar antara AR Tilik dan aplikasi pemeriksa plagiarisme lainnya?",
              "acceptedAnswer": {
                "@type": "Answer",
                "text": "AR Tilik berfokus pada Keadilan Bukti (Evidence-Based Fairness) menggunakan arsitektur Dual-Lens (5-Gram Sintaktis & TF-IDF Semantik) serta kesadaran konteks sitasi. Berbeda dengan matcher konvensional yang kerap menandai kutipan resmi, rumus, dan frasa umum sebagai plagiarisme, AR Tilik mengisolasi daftar pustaka, mengenali blok kutipan bertanda, dan menyediakan sintesis risiko terkalibrasi melalui Sintesis Risiko Akademik Berbobot serta penampil perbandingan berdampingan (Dual-Reader)."
              }
            },
            {
              "@type": "Question",
              "name": "Apakah naskah karya ilmiah yang saya unggah akan disimpan ke repositori publik?",
              "acceptedAnswer": {
                "@type": "Answer",
                "text": "Sama sekali tidak. AR Tilik menerapkan kebijakan non-repository default untuk melindungi kerahasiaan dan kedaulatan naskah submisi sivitas akademika. Naskah hanya diproses di lingkungan memori terisolasi untuk menghasilkan laporan telaah kesepadanan dan tidak pernah dibagikan atau dijadikan korpus terbuka tanpa izin eksplisit penulis."
              }
            },
            {
              "@type": "Question",
              "name": "Bagaimana cara mengintegrasikan AR Tilik ke jurnal Open Journal Systems (OJS 3.x)?",
              "acceptedAnswer": {
                "@type": "Answer",
                "text": "Integrasi OJS sangat praktis tanpa perlu mengubah core OJS. Pengelola jurnal cukup mengunduh berkas konektor mandiri ARTilikOJSConnector.php dan mengikuti panduan di Buku Panduan OJS (PDF). Konektor ini memanfaatkan REST API v1 AR Tilik untuk melakukan pemeriksaan submisi naskah secara otomatis saat penulis mengunggah artikel di OJS."
              }
            },
            {
              "@type": "Question",
              "name": "Bagaimana AR Tilik mendeteksi kecurangan manipulasi karakter (Font Spoofing & Zero-Width Space)?",
              "acceptedAnswer": {
                "@type": "Answer",
                "text": "AR Tilik dilengkapi modul Anti-Cheat Font & Homoglyph Shield yang secara otomatis menormalisasi karakter Cyrillic/Greek yang mirip dengan Latin, menghapus karakter tak kasat mata (Zero-Width Spaces/Joiners), dan mendeteksi injeksi teks tersembunyi yang lazim digunakan untuk mengelabui deteksi similarity tradisional."
              }
            },
            {
              "@type": "Question",
              "name": "Apakah sivitas akademika (mahasiswa & dosen) dapat menggunakan AR Tilik secara gratis?",
              "acceptedAnswer": {
                "@type": "Answer",
                "text": "Ya, 100% gratis. Setiap akun yang mendaftar langsung memperoleh kuota 3 submisi per minggu yang diperbarui secara otomatis. Selain itu, sivitas akademika dapat menambah kuota permanen secara gratis melalui program Gotong Royong Korpus dengan menyumbangkan repositori skripsi/tesis berlisensi terbuka (Open Access), atau melakukan top-up terjangkau mulai Rp 5.000 via QRIS."
              }
            },
            {
              "@type": "Question",
              "name": "Bagaimana cara memeriksa keabsahan dan keaslian Sertifikat Digital AR Tilik?",
              "acceptedAnswer": {
                "@type": "Answer",
                "text": "Setiap laporan hasil telaah AR Tilik dilengkapi kode hash kriptografis unik dan tautan QR Code resmi yang mengarah ke portal verifikasi publik (https://tilik.arsetontong.top/verify/{hash}). Pihak perpustakaan prodi, dewan penguji skripsi, atau editor jurnal dapat memindai QR tersebut kapan saja untuk memvalidasi skor kesepadanan, tanggal terbit, dan keaslian sertifikat tanpa risiko pemalsuan PDF."
              }
            }
          ]
        }
      ]
    }
    </script>
    <!-- Favicon & Brand Icons -->
    <link rel="icon" type="image/x-icon" href="<?= base_url('favicon.ico?v=20261012') ?>">
    <link rel="icon" type="image/png" sizes="16x16" href="<?= base_url('favicon-16x16.png?v=20261012') ?>">
    <link rel="icon" type="image/png" sizes="32x32" href="<?= base_url('favicon-32x32.png?v=20261012') ?>">
    <link rel="icon" type="image/png" sizes="48x48" href="<?= base_url('favicon-48x48.png?v=20261012') ?>">
    <link rel="icon" type="image/png" sizes="96x96" href="<?= base_url('favicon-96x96.png?v=20261012') ?>">
    <link rel="icon" type="image/png" sizes="192x192" href="<?= base_url('android-chrome-192x192.png?v=20261012') ?>">
    <link rel="apple-touch-icon" sizes="180x180" href="<?= base_url('apple-touch-icon.png?v=20261012') ?>">
    <link rel="shortcut icon" type="image/x-icon" href="<?= base_url('favicon.ico?v=20261012') ?>">
    <link rel="manifest" href="<?= base_url('site.webmanifest?v=20261012') ?>">
    <!-- Preload Hero LCP Candidate Image -->
    <link rel="preload" as="image" href="<?= base_url('images/hero_illustration.webp') ?>" type="image/webp" fetchpriority="high">

    <!-- Fonts & Performance Preconnects -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preload" as="style" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;600;700&display=swap">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;600;700&display=swap" rel="stylesheet" media="print" onload="this.media='all'">
    <noscript>
        <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;600;700&display=swap" rel="stylesheet">
    </noscript>

    <!-- Animate On Scroll (AOS) Non-blocking -->
    <link rel="preload" as="style" href="https://unpkg.com/aos@2.3.1/dist/aos.css">
    <link rel="stylesheet" href="https://unpkg.com/aos@2.3.1/dist/aos.css" media="print" onload="this.media='all'">
    <noscript>
        <link rel="stylesheet" href="https://unpkg.com/aos@2.3.1/dist/aos.css">
    </noscript>

    <!-- Google AdSense (Lazy load upon user interaction or idle callback to prevent blocking LCP / TBT) -->
    <script>
        (function() {
            var adsLoaded = false;
            function initAdSense() {
                if (adsLoaded) return;
                adsLoaded = true;
                ['scroll', 'mousemove', 'touchstart', 'click', 'keydown'].forEach(function(evt) {
                    window.removeEventListener(evt, initAdSense, { passive: true });
                });
                var adScript = document.createElement('script');
                adScript.src = 'https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-9248253613564140';
                adScript.async = true;
                adScript.crossOrigin = 'anonymous';
                document.head.appendChild(adScript);
            }
            ['scroll', 'mousemove', 'touchstart', 'click', 'keydown'].forEach(function(evt) {
                window.addEventListener(evt, initAdSense, { once: true, passive: true });
            });
            if ('requestIdleCallback' in window) {
                requestIdleCallback(function() { setTimeout(initAdSense, 4500); });
            } else {
                setTimeout(initAdSense, 4500);
            }
        })();
    </script>

    <style>
        :root {
            --primary: #4338ca;
            --primary-dark: #312e81;
            --primary-light: #6366f1;
            --secondary: #0ea5e9;
            --dark: #0f172a;
            --dark-card: #1e293b;
            --light-bg: #f8fafc;
            --surface: #ffffff;
            --border: #e2e8f0;
            --text-main: #1e293b;
            --text-muted: #64748b;
            --emerald: #10b981;
            --amber: #f59e0b;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        html, body {
            overflow-x: hidden;
            max-width: 100vw;
        }

        html {
            scroll-behavior: smooth;
            scroll-padding-top: 80px;
        }

        body {
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: var(--light-bg);
            color: var(--text-main);
            line-height: 1.6;
            padding-top: 72px;
        }

        /* Container */
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 24px;
        }

        /* Navbar (Fixed Glassmorphism) */
        .navbar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            width: 100%;
            z-index: 990;
            background: rgba(255, 255, 255, 0.92);
            backdrop-filter: blur(14px);
            -webkit-backdrop-filter: blur(14px);
            border-bottom: 1px solid var(--border);
            padding: 9px 0;
            transition: padding 0.25s ease, background 0.25s ease, box-shadow 0.25s ease;
        }

        .navbar.scrolled {
            padding: 7px 0;
            background: rgba(255, 255, 255, 0.98);
            box-shadow: 0 4px 18px -2px rgba(15, 23, 42, 0.07);
            border-bottom-color: rgba(226, 232, 240, 0.85);
        }

        .navbar .container {
            max-width: 1240px;
        }

        /* Floating Go to Top Button */
        .btn-scroll-top {
            position: fixed;
            bottom: 28px;
            right: 28px;
            width: 46px;
            height: 46px;
            border-radius: 14px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: #ffffff;
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 8px 24px -4px rgba(67, 56, 202, 0.45), 0 2px 6px rgba(0, 0, 0, 0.08);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            z-index: 900;
            opacity: 0;
            visibility: hidden;
            transform: translateY(14px) scale(0.9);
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .btn-scroll-top.visible {
            opacity: 1;
            visibility: visible;
            transform: translateY(0) scale(1);
        }

        .btn-scroll-top:hover {
            transform: translateY(-3px) scale(1.06);
            box-shadow: 0 12px 28px -4px rgba(67, 56, 202, 0.6), 0 4px 10px rgba(0, 0, 0, 0.12);
            color: #ffffff;
        }

        .btn-scroll-top:active {
            transform: translateY(0) scale(0.98);
        }

        .btn-scroll-top svg {
            transition: transform 0.2s ease;
        }

        .btn-scroll-top:hover svg {
            transform: translateY(-2px);
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
            transition: transform 0.2s ease;
        }

        .brand:hover {
            transform: scale(1.02);
        }

        .brand-logo {
            width: 35px;
            height: 35px;
            object-fit: contain;
            border-radius: 9px;
            filter: drop-shadow(0 2px 4px rgba(67, 56, 202, 0.16));
        }

        .brand-text {
            display: flex;
            align-items: center;
            gap: 7px;
        }

        .brand-title {
            font-size: 18.5px;
            font-weight: 800;
            letter-spacing: -0.4px;
            background: linear-gradient(135deg, #4338ca 0%, #6366f1 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .brand-badge {
            font-size: 9.5px;
            font-weight: 700;
            padding: 1.5px 6px;
            border-radius: 10px;
            background: #eef2ff;
            color: #4338ca;
            border: 1px solid #c7d2fe;
            text-transform: uppercase;
            letter-spacing: 0.2px;
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 3px;
            list-style: none;
            margin: 0;
            padding: 0;
        }

        .nav-links > li > a {
            text-decoration: none;
            color: var(--text-muted);
            font-size: 13px;
            font-weight: 600;
            padding: 5.5px 9px;
            border-radius: 8px;
            transition: all 0.2s ease;
            white-space: nowrap;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .nav-links > li > a:hover {
            color: var(--primary);
            background: rgba(67, 56, 202, 0.06);
        }

        /* Nav Dropdown */
        .nav-item-dropdown {
            position: relative;
        }

        .nav-dropdown-toggle {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            background: transparent;
            border: none;
            color: var(--text-muted);
            font-size: 13px;
            font-weight: 600;
            font-family: inherit;
            cursor: pointer;
            padding: 5.5px 9px;
            border-radius: 8px;
            transition: all 0.2s ease;
            white-space: nowrap;
        }

        .nav-dropdown-toggle:hover,
        .nav-item-dropdown:hover .nav-dropdown-toggle,
        .nav-item-dropdown.active .nav-dropdown-toggle {
            color: var(--primary);
            background: rgba(67, 56, 202, 0.06);
        }

        .dropdown-chevron {
            transition: transform 0.25s cubic-bezier(0.16, 1, 0.3, 1);
            color: var(--text-muted);
        }

        .nav-item-dropdown:hover .dropdown-chevron,
        .nav-item-dropdown.active .dropdown-chevron {
            transform: rotate(180deg);
            color: var(--primary);
        }

        .nav-dropdown-menu {
            position: absolute;
            top: calc(100% + 8px);
            left: 0;
            width: 320px;
            background: #ffffff;
            border-radius: 14px;
            border: 1px solid rgba(226, 232, 240, 0.95);
            box-shadow: 0 16px 36px -4px rgba(15, 23, 42, 0.12), 0 4px 12px -2px rgba(15, 23, 42, 0.06);
            padding: 8px;
            opacity: 0;
            visibility: hidden;
            transform: translateY(8px);
            transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
            z-index: 120;
            pointer-events: none;
        }

        .nav-dropdown-menu::before {
            content: '';
            position: absolute;
            top: -12px;
            left: 0;
            right: 0;
            height: 12px;
        }

        .nav-item-dropdown:hover .nav-dropdown-menu,
        .nav-item-dropdown:focus-within .nav-dropdown-menu,
        .nav-item-dropdown.active .nav-dropdown-menu {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
            pointer-events: auto;
        }

        .dropdown-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 12px;
            border-radius: 10px;
            text-decoration: none;
            color: var(--text-main);
            transition: background 0.15s ease, transform 0.15s ease;
        }

        .dropdown-item:hover {
            background: #f8fafc;
            transform: translateX(3px);
        }

        .dropdown-icon {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 17px;
            flex-shrink: 0;
        }

        .dropdown-content {
            display: flex;
            flex-direction: column;
            text-align: left;
        }

        .dropdown-title {
            font-size: 13.5px;
            font-weight: 700;
            color: var(--text-main);
            line-height: 1.3;
        }

        .dropdown-item:hover .dropdown-title {
            color: var(--primary);
        }

        .dropdown-sub {
            font-size: 11.5px;
            color: var(--text-muted);
            line-height: 1.35;
            margin-top: 2px;
        }

        /* QR Verify Pill */
        .nav-link-verify {
            border: 1px solid rgba(67, 56, 202, 0.18) !important;
            background: rgba(67, 56, 202, 0.04) !important;
            color: var(--primary) !important;
            font-weight: 700 !important;
            padding: 5.5px 9px !important;
            font-size: 12.5px !important;
        }

        .nav-link-verify:hover {
            background: rgba(67, 56, 202, 0.1) !important;
            border-color: rgba(67, 56, 202, 0.35) !important;
        }

        /* Duta Kampus Nav Pill */
        .nav-link-ambassador {
            border: 1px solid rgba(245, 158, 11, 0.28) !important;
            background: rgba(245, 158, 11, 0.08) !important;
            color: #b45309 !important;
            font-weight: 700 !important;
            display: inline-flex !important;
            align-items: center !important;
            gap: 5px !important;
            padding: 5.5px 9px !important;
            font-size: 12.5px !important;
        }

        .nav-link-ambassador:hover {
            background: rgba(245, 158, 11, 0.16) !important;
            border-color: rgba(245, 158, 11, 0.5) !important;
            color: #92400e !important;
            box-shadow: 0 2px 8px rgba(245, 158, 11, 0.15) !important;
        }

        .nav-badge-ambassador {
            font-size: 9.5px;
            font-weight: 800;
            line-height: 1;
            padding: 1.5px 5px;
            border-radius: 9999px;
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            color: #ffffff;
            letter-spacing: 0.2px;
            box-shadow: 0 1px 3px rgba(217, 119, 6, 0.25);
            display: inline-flex;
            align-items: center;
        }

        .nav-actions {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-shrink: 0;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 9px 18px;
            border-radius: 12px;
            font-size: 13.5px;
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.2s ease;
            border: 1px solid transparent;
        }

        .btn-sm {
            padding: 6.5px 13px;
            font-size: 12.8px;
            border-radius: 10px;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: #ffffff;
            box-shadow: 0 4px 12px rgba(67, 56, 202, 0.25);
        }

        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(67, 56, 202, 0.35);
        }

        .btn-outline {
            border-color: var(--border);
            color: var(--text-main);
            background: #ffffff;
        }

        .btn-outline:hover {
            border-color: var(--primary);
            color: var(--primary);
            background: #f8fafc;
        }

        .btn-secondary {
            background: #f1f5f9;
            color: var(--text-main);
            border-color: #cbd5e1;
        }

        /* Hero Section */
        .hero {
            padding: 76px 0 52px;
            position: relative;
            background: radial-gradient(circle at 50% 10%, rgba(99, 102, 241, 0.08) 0%, transparent 60%);
        }

        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 16px;
            background: rgba(67, 56, 202, 0.08);
            border: 1px solid rgba(67, 56, 202, 0.2);
            border-radius: 30px;
            font-size: 13px;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 24px;
        }

        .hero-title {
            font-size: 48px;
            font-weight: 800;
            line-height: 1.15;
            letter-spacing: -1.5px;
            color: var(--dark);
            margin-bottom: 20px;
            max-width: 900px;
        }

        .hero-title span {
            background: linear-gradient(135deg, #4338ca 0%, #0284c7 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .hero-subtitle {
            font-size: 18px;
            color: var(--text-muted);
            max-width: 760px;
            margin-bottom: 36px;
            line-height: 1.6;
        }

        .hero-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 16px;
            align-items: center;
            margin-bottom: 24px;
        }

        .stats-bar {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 20px;
            background: #ffffff;
            border: 1px solid var(--border);
            border-radius: 18px;
            padding: 24px 32px;
            box-shadow: 0 4px 20px -2px rgba(0, 0, 0, 0.04);
        }

        .stat-item {
            text-align: left;
        }

        .stat-val {
            font-size: 28px;
            font-weight: 800;
            color: var(--dark);
            font-family: 'JetBrains Mono', monospace;
        }

        .stat-lbl {
            font-size: 13px;
            color: var(--text-muted);
            font-weight: 500;
            margin-top: 4px;
        }

        /* Section Styling */
        .section {
            padding: 90px 0;
            content-visibility: auto;
            contain-intrinsic-size: 1px 700px;
        }

        .faq-section, .footer {
            content-visibility: auto;
            contain-intrinsic-size: 1px 600px;
        }

        .section-header {
            text-align: center;
            max-width: 740px;
            margin: 0 auto 60px;
        }

        .section-tag {
            font-size: 12px;
            text-transform: uppercase;
            font-weight: 800;
            letter-spacing: 1px;
            color: var(--primary);
            margin-bottom: 12px;
        }

        .section-title {
            font-size: 34px;
            font-weight: 800;
            color: var(--dark);
            letter-spacing: -0.8px;
            margin-bottom: 16px;
        }

        .section-desc {
            font-size: 16px;
            color: var(--text-muted);
            line-height: 1.6;
        }

        /* Theory Cards */
        .grid-4 {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
        }

        @media (max-width: 1120px) {
            .grid-4 {
                grid-template-columns: repeat(2, 1fr);
                gap: 20px;
            }
        }

        .grid-3 {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 28px;
        }

        .grid-2 {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 32px;
        }

        .theory-card {
            background: #ffffff;
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 32px;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .theory-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 24px -4px rgba(0, 0, 0, 0.06);
            border-color: rgba(99, 102, 241, 0.4);
        }

        .card-icon-badge {
            width: 52px;
            height: 52px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            margin-bottom: 22px;
        }

        .badge-indigo { background: rgba(99, 102, 241, 0.12); color: #4338ca; }
        .badge-emerald { background: rgba(16, 185, 129, 0.12); color: #059669; }
        .badge-amber { background: rgba(245, 158, 11, 0.12); color: #d97706; }
        .badge-cyan { background: rgba(14, 165, 233, 0.12); color: #0284c7; }

        .theory-title {
            font-size: 20px;
            font-weight: 800;
            color: var(--dark);
            margin-bottom: 12px;
            letter-spacing: -0.3px;
        }

        .theory-desc {
            font-size: 14px;
            color: var(--text-muted);
            line-height: 1.6;
        }

        /* Interactive Layers Diagram */
        .layer-box {
            background: #ffffff;
            border: 1px solid var(--border);
            border-radius: 22px;
            padding: 36px;
            box-shadow: 0 4px 20px -2px rgba(0, 0, 0, 0.04);
        }

        .layer-item {
            display: flex;
            gap: 20px;
            padding: 20px;
            border-radius: 14px;
            margin-bottom: 14px;
            border: 1px solid var(--border);
            background: #f8fafc;
        }

        .layer-item.layer-a { border-left: 6px solid #4338ca; }
        .layer-item.layer-b { border-left: 6px solid #059669; }
        .layer-item.layer-c { border-left: 6px solid #d97706; }

        .layer-num {
            font-size: 14px;
            font-weight: 800;
            font-family: 'JetBrains Mono', monospace;
            color: var(--dark);
            padding: 4px 10px;
            background: #ffffff;
            border: 1px solid var(--border);
            border-radius: 8px;
            align-self: flex-start;
        }

        /* How to use step cards */
        .step-card {
            background: #ffffff;
            border: 1px solid var(--border);
            border-radius: 18px;
            padding: 28px;
            position: relative;
        }

        .step-index {
            font-size: 32px;
            font-weight: 800;
            color: rgba(67, 56, 202, 0.15);
            font-family: 'JetBrains Mono', monospace;
            position: absolute;
            top: 20px;
            right: 24px;
        }

        .step-title {
            font-size: 18px;
            font-weight: 800;
            color: var(--dark);
            margin-bottom: 10px;
        }

        .step-desc {
            font-size: 14px;
            color: var(--text-muted);
            line-height: 1.6;
        }

        /* Incentive & Crowdsourcing Banner */
        .incentive-banner {
            background: linear-gradient(135deg, #1e1b4b 0%, #312e81 100%);
            border-radius: 24px;
            padding: 48px;
            color: #ffffff;
            position: relative;
            overflow: hidden;
        }

        .incentive-grid {
            display: grid;
            grid-template-columns: 1.2fr 0.8fr;
            gap: 40px;
            align-items: center;
        }

        .incentive-title {
            font-size: 32px;
            font-weight: 800;
            letter-spacing: -0.5px;
            margin-bottom: 16px;
        }

        .incentive-desc {
            font-size: 15px;
            color: #cbd5e1;
            line-height: 1.7;
            margin-bottom: 24px;
        }

        .formula-box {
            background: rgba(255, 255, 255, 0.08);
            border: 1px dashed rgba(255, 255, 255, 0.25);
            border-radius: 16px;
            padding: 24px;
            text-align: center;
        }

        .formula-text {
            font-size: 20px;
            font-weight: 800;
            font-family: 'JetBrains Mono', monospace;
            color: #38bdf8;
            margin-bottom: 8px;
        }

        /* Pricing Cards */
        #langganan .container {
            max-width: 1240px;
        }

        .pricing-card {
            background: #ffffff;
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 26px 20px;
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            transition: all 0.25s ease;
            box-shadow: 0 4px 12px rgba(15, 23, 42, 0.03);
        }

        .pricing-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 16px 32px -6px rgba(15, 23, 42, 0.08);
        }

        .pricing-card.featured {
            border: 2px solid var(--primary);
            box-shadow: 0 10px 28px -4px rgba(67, 56, 202, 0.15);
        }

        .pricing-card.popular {
            border: 2px solid #f59e0b;
            box-shadow: 0 12px 30px -4px rgba(245, 158, 11, 0.18);
            background: linear-gradient(180deg, #fffdf8 0%, #ffffff 100%);
        }

        .pricing-card.popular:hover {
            transform: translateY(-4px);
            box-shadow: 0 18px 36px -6px rgba(245, 158, 11, 0.25);
        }

        .pricing-badge {
            position: absolute;
            top: -12px;
            left: 50%;
            transform: translateX(-50%);
            background: var(--primary);
            color: #fff;
            padding: 4px 14px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            white-space: nowrap;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        .pricing-badge.popular {
            background: #f59e0b;
        }

        .plan-pill {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 3px 10px;
            border-radius: 9999px;
            font-size: 10.5px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 12px;
        }

        .plan-pill-free {
            background: #ecfdf5;
            color: #059669;
            border: 1px solid #a7f3d0;
        }

        .plan-pill-payg {
            background: #f0f9ff;
            color: #0284c7;
            border: 1px solid #bae6fd;
        }

        .plan-pill-popular {
            background: #fffbeb;
            color: #d97706;
            border: 1px solid #fde68a;
        }

        .plan-pill-pro {
            background: #f5f3ff;
            color: #7c3aed;
            border: 1px solid #ddd6fe;
        }

        .plan-name {
            font-size: 19px;
            font-weight: 800;
            color: var(--dark);
            margin-bottom: 8px;
            line-height: 1.25;
            letter-spacing: -0.3px;
        }

        .plan-price {
            font-size: 26px;
            font-weight: 800;
            color: var(--dark);
            font-family: inherit;
            margin-bottom: 10px;
            display: flex;
            align-items: baseline;
            flex-wrap: wrap;
            gap: 4px;
            line-height: 1.15;
        }

        .plan-price .currency {
            font-size: 16px;
            font-weight: 700;
            color: var(--dark);
        }

        .plan-price .amount {
            font-size: 26px;
            font-weight: 800;
            letter-spacing: -0.5px;
        }

        .plan-period {
            font-size: 12.5px;
            color: var(--text-muted);
            font-weight: 600;
            letter-spacing: normal;
        }

        .plan-desc {
            font-size: 12.5px;
            color: var(--text-muted);
            line-height: 1.5;
            min-height: 52px;
            margin-bottom: 16px;
        }

        .plan-features {
            list-style: none;
            padding: 16px 0 0 0;
            margin: 0 0 24px 0;
            display: flex;
            flex-direction: column;
            gap: 11px;
            font-size: 12.5px;
            line-height: 1.45;
            color: var(--text-main);
            border-top: 1px dashed var(--border);
        }

        .plan-features li {
            position: relative;
            padding-left: 20px;
            display: block;
            text-align: left;
            word-break: normal;
        }

        .plan-features li::before {
            content: "✓";
            position: absolute;
            left: 0;
            top: 0;
            color: #10b981;
            font-weight: 800;
            font-size: 13px;
            line-height: 1.45;
        }

        .plan-features li strong {
            color: var(--dark);
            font-weight: 700;
        }

        .pricing-card .btn {
            width: 100%;
            padding: 11px 16px;
            font-size: 13.5px;
            font-weight: 700;
            border-radius: 12px;
            text-align: center;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }

        /* Modal Dialog */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(15, 23, 42, 0.65);
            backdrop-filter: blur(6px);
            z-index: 1000;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .modal-card {
            background: #ffffff;
            border-radius: 20px;
            max-width: 440px;
            width: 100%;
            overflow: hidden;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
            animation: modalFadeIn 0.25s ease-out;
        }

        @keyframes modalFadeIn {
            from { opacity: 0; transform: translateY(12px) scale(0.98); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }

        .modal-header {
            padding: 20px 24px;
            background: #f8fafc;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .modal-title {
            font-size: 17px;
            font-weight: 800;
            color: var(--dark);
        }

        .modal-close {
            background: none;
            border: none;
            font-size: 20px;
            color: var(--text-muted);
            cursor: pointer;
            padding: 4px;
        }

        .modal-body {
            padding: 24px;
        }

        .modal-card-md {
            background: #ffffff;
            border-radius: 20px;
            max-width: 540px;
            width: 100%;
            overflow: hidden;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            animation: modalFadeIn 0.25s ease-out;
            max-height: 90vh;
            display: flex;
            flex-direction: column;
        }

        .modal-card-lg {
            background: #ffffff;
            border-radius: 20px;
            max-width: 860px;
            width: 100%;
            overflow: hidden;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            animation: modalFadeIn 0.25s ease-out;
            max-height: 88vh;
            display: flex;
            flex-direction: column;
        }

        .modal-body-scroll {
            overflow-y: auto;
            padding: 28px 32px;
            flex: 1;
        }

        /* Legal Document Styling inside Modal */
        .legal-document { font-size: 14px; color: #334155; line-height: 1.7; }
        .legal-header { margin-bottom: 24px; border-bottom: 1px solid var(--border); padding-bottom: 18px; }
        .legal-badge { display: inline-block; padding: 4px 12px; border-radius: 20px; background: rgba(67, 56, 202, 0.1); color: var(--primary); font-size: 11px; font-weight: 700; margin-bottom: 10px; text-transform: uppercase; }
        .legal-title { font-size: 24px; font-weight: 800; color: var(--dark); line-height: 1.3; letter-spacing: -0.5px; margin-bottom: 6px; }
        .legal-subtitle { font-size: 12px; color: var(--text-muted); }
        .legal-callout { background: #eff6ff; border: 1px solid #bfdbfe; border-left: 4px solid #3b82f6; border-radius: 12px; padding: 16px 20px; margin-bottom: 28px; font-size: 13px; color: #1e3a8a; line-height: 1.6; }
        .legal-section { margin-bottom: 28px; }
        .legal-section h2 { font-size: 17px; font-weight: 800; color: var(--dark); margin-bottom: 10px; letter-spacing: -0.2px; }
        .legal-section p { font-size: 13.5px; color: #334155; line-height: 1.65; margin-bottom: 10px; }
        .legal-section ul { padding-left: 20px; margin-bottom: 12px; }
        .legal-section li { font-size: 13.5px; color: #334155; line-height: 1.65; margin-bottom: 6px; }
        .legal-contact-card { background: #f8fafc; border: 1px solid var(--border); border-radius: 12px; padding: 16px 20px; font-size: 13px; color: #475569; line-height: 1.6; margin-top: 12px; }
        .legal-contact-card a { color: var(--primary); font-weight: 700; text-decoration: underline; }


        .tab-switch {
            display: flex;
            background: #f1f5f9;
            border-radius: 10px;
            padding: 4px;
            margin-bottom: 20px;
        }

        .tab-btn {
            flex: 1;
            padding: 8px;
            border: none;
            background: transparent;
            font-size: 13px;
            font-weight: 700;
            color: var(--text-muted);
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .tab-btn.active {
            background: #ffffff;
            color: var(--primary);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .form-group {
            margin-bottom: 16px;
        }

        .form-label {
            display: block;
            font-size: 13px;
            font-weight: 700;
            color: var(--text-main);
            margin-bottom: 6px;
        }

        .form-control {
            width: 100%;
            padding: 10px 14px;
            border: 1px solid var(--border);
            border-radius: 10px;
            font-size: 14px;
            font-family: inherit;
            transition: border 0.2s;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(67, 56, 202, 0.1);
        }

        .alert-box {
            padding: 12px;
            border-radius: 10px;
            font-size: 13px;
            margin-bottom: 16px;
            display: none;
        }

        .alert-error {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #991b1b;
        }

        .alert-success {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            color: #166534;
        }

        /* Footer */
        .footer {
            background: var(--dark);
            color: #94a3b8;
            padding: 60px 0 30px;
            font-size: 13px;
        }

        .footer-grid {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr;
            gap: 40px;
            margin-bottom: 50px;
        }

        .footer-brand {
            color: #ffffff;
            font-size: 18px;
            font-weight: 800;
            margin-bottom: 12px;
        }

        .footer-links {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .footer-links a,
        .footer-links button {
            color: #94a3b8;
            text-decoration: none;
            transition: color 0.2s;
            background: none;
            border: none;
            padding: 0;
            font: inherit;
            cursor: pointer;
            text-align: left;
        }

        .footer-links a:hover,
        .footer-links button:hover {
            color: #ffffff;
        }

        .footer-bottom {
            border-top: 1px solid #334155;
            padding-top: 24px;
            text-align: center;
            font-size: 12px;
            color: #64748b;
        }

        .footer-counter-wrap {
            text-align: center;
            margin-bottom: 24px;
        }

        .visitor-counter-badge {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 30px;
            padding: 8px 22px;
            font-size: 13px;
            color: #cbd5e1;
            flex-wrap: wrap;
            justify-content: center;
        }

        .visitor-counter-badge strong {
            color: #38bdf8;
            font-family: 'JetBrains Mono', monospace;
            font-weight: 800;
        }

        .counter-sep {
            color: #64748b;
        }

        .live-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #10b981;
            display: inline-block;
            box-shadow: 0 0 8px #10b981;
            animation: livePulse 2s infinite;
        }

        @keyframes livePulse {
            0% { transform: scale(0.95); opacity: 0.8; }
            50% { transform: scale(1.2); opacity: 1; }
            100% { transform: scale(0.95); opacity: 0.8; }
        }

        /* Hero Visual & Layout */
        .hero-grid {
            display: grid;
            grid-template-columns: 1.15fr 0.85fr;
            gap: 48px;
            align-items: center;
            margin-bottom: 50px;
        }

        .hero-visual-card {
            position: relative;
            border-radius: 22px;
            overflow: hidden;
            box-shadow: 0 25px 50px -12px rgba(67, 56, 202, 0.28);
            border: 1px solid rgba(99, 102, 241, 0.25);
            background: #0f172a;
            transition: all 0.4s ease;
        }

        .hero-visual-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 30px 60px -12px rgba(67, 56, 202, 0.38);
        }

        .hero-visual-card img {
            width: 100%;
            height: auto;
            display: block;
            object-fit: cover;
            aspect-ratio: 16 / 9;
        }

        .hero-visual-overlay {
            position: absolute;
            bottom: 14px;
            left: 14px;
            right: 14px;
            background: rgba(15, 23, 42, 0.85);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 12px;
            padding: 10px 14px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            color: #ffffff;
            font-size: 12px;
        }

        .hero-visual-tag {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-weight: 700;
            color: #38bdf8;
        }

        .hero-visual-status {
            font-size: 11px;
            color: #10b981;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .hero-visual-status::before {
            content: "";
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: #10b981;
            display: inline-block;
            box-shadow: 0 0 8px #10b981;
        }

        /* Dual-Lens Architecture Spotlight */
        .spotlight-card {
            background: #ffffff;
            border: 1px solid var(--border);
            border-radius: 24px;
            padding: 36px;
            box-shadow: 0 8px 30px -4px rgba(0, 0, 0, 0.05);
            margin: 40px 0 30px;
            display: grid;
            grid-template-columns: 1fr 1.05fr;
            gap: 36px;
            align-items: center;
        }

        .spotlight-img-wrap {
            border-radius: 18px;
            overflow: hidden;
            border: 1px solid var(--border);
            box-shadow: 0 12px 30px -6px rgba(0, 0, 0, 0.1);
            background: #0f172a;
            position: relative;
        }

        .spotlight-img-wrap img {
            width: 100%;
            height: auto;
            display: block;
            aspect-ratio: 16 / 9;
            object-fit: cover;
            transition: transform 0.5s ease;
        }

        .spotlight-img-wrap:hover img {
            transform: scale(1.025);
        }

        .spotlight-caption {
            position: absolute;
            bottom: 12px;
            left: 12px;
            right: 12px;
            background: rgba(15, 23, 42, 0.82);
            backdrop-filter: blur(8px);
            border-radius: 10px;
            padding: 8px 12px;
            font-size: 11px;
            color: #e2e8f0;
            font-family: 'JetBrains Mono', monospace;
            display: flex;
            justify-content: space-between;
        }

        .lens-list {
            display: flex;
            flex-direction: column;
            gap: 16px;
            margin-top: 18px;
        }

        .lens-item {
            display: flex;
            gap: 14px;
            align-items: flex-start;
            padding: 12px 14px;
            border-radius: 12px;
            background: #f8fafc;
            border: 1px solid var(--border);
            transition: all 0.2s ease;
        }

        .lens-item:hover {
            background: #ffffff;
            border-color: rgba(99, 102, 241, 0.3);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.04);
        }

        .lens-item-badge {
            width: 34px;
            height: 34px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 15px;
            flex-shrink: 0;
        }

        /* Dual-Reader Mockup Showcase */
        .dual-reader-showcase {
            background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 100%);
            border-radius: 28px;
            padding: 44px;
            color: #ffffff;
            margin-top: 50px;
            box-shadow: 0 25px 50px -12px rgba(15, 23, 42, 0.35);
            border: 1px solid rgba(255, 255, 255, 0.12);
        }

        .dual-reader-header {
            text-align: center;
            max-width: 700px;
            margin: 0 auto 36px;
        }

        .dual-reader-tag {
            display: inline-block;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 1.2px;
            font-weight: 800;
            color: #38bdf8;
            background: rgba(56, 189, 248, 0.15);
            padding: 4px 12px;
            border-radius: 20px;
            margin-bottom: 12px;
        }

        .dual-reader-title {
            font-size: 30px;
            font-weight: 800;
            letter-spacing: -0.6px;
            margin-bottom: 10px;
            color: #ffffff;
        }

        .dual-reader-desc {
            font-size: 15px;
            color: #94a3b8;
            line-height: 1.6;
        }

        .browser-mockup {
            background: #1e293b;
            border-radius: 18px;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.15);
            box-shadow: 0 25px 45px -10px rgba(0, 0, 0, 0.5);
        }

        .browser-bar {
            background: #0f172a;
            padding: 12px 18px;
            display: flex;
            align-items: center;
            gap: 14px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        }

        .browser-dots {
            display: flex;
            gap: 7px;
        }

        .browser-dot {
            width: 11px;
            height: 11px;
            border-radius: 50%;
        }
        .dot-red { background: #ef4444; }
        .dot-yellow { background: #f59e0b; }
        .dot-green { background: #10b981; }

        .browser-url {
            background: rgba(255, 255, 255, 0.06);
            border-radius: 8px;
            padding: 5px 14px;
            font-size: 12px;
            font-family: 'JetBrains Mono', monospace;
            color: #cbd5e1;
            flex: 1;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .browser-mockup img {
            width: 100%;
            height: auto;
            display: block;
            aspect-ratio: 16 / 9;
            object-fit: cover;
        }

        .dual-reader-features {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-top: 36px;
        }

        .feature-card-dark {
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 16px;
            padding: 22px;
            transition: all 0.2s ease;
        }

        .feature-card-dark:hover {
            background: rgba(255, 255, 255, 0.07);
            border-color: rgba(56, 189, 248, 0.3);
            transform: translateY(-2px);
        }

        .feature-dark-icon {
            font-size: 24px;
            margin-bottom: 12px;
        }

        .feature-dark-title {
            font-size: 16px;
            font-weight: 700;
            color: #ffffff;
            margin-bottom: 6px;
        }

        .feature-dark-desc {
            font-size: 13px;
            color: #94a3b8;
            line-height: 1.6;
        }

        /* Dynamic Bidirectional AOS Transitions (Scroll-Down vs Scroll-Up) */
        body.scrolling-down [data-aos="fade-up"]:not(.aos-animate) {
            transform: translate3d(0, 50px, 0);
        }
        body.scrolling-down [data-aos="fade-down"]:not(.aos-animate) {
            transform: translate3d(0, -50px, 0);
        }

        body.scrolling-up [data-aos="fade-up"]:not(.aos-animate) {
            transform: translate3d(0, -50px, 0);
        }
        body.scrolling-up [data-aos="fade-down"]:not(.aos-animate) {
            transform: translate3d(0, 50px, 0);
        }

        body.scrolling-up [data-aos="fade-left"]:not(.aos-animate) {
            transform: translate3d(-50px, 0, 0);
        }
        body.scrolling-up [data-aos="fade-right"]:not(.aos-animate) {
            transform: translate3d(50px, 0, 0);
        }

        /* Instant reaction on reverse scroll (eliminate delay on upward reading) */
        body.scrolling-up [data-aos] {
            transition-delay: 0s !important;
        }

        /* Modern Additions: Badges & Section Components */
        .badge-rose { background: rgba(244, 63, 94, 0.12); color: #e11d48; }
        .badge-purple { background: rgba(168, 85, 247, 0.12); color: #9333ea; }

        /* Hero Pills Micro-badges */
        .hero-pills-wrap {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 0;
            margin-bottom: 36px;
        }

        .hero-pill-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 5px 12px;
            background: #ffffff;
            border: 1px solid var(--border);
            border-radius: 9999px;
            font-size: 12px;
            font-weight: 600;
            color: var(--dark);
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
            transition: all 0.2s ease;
        }

        .hero-pill-badge:hover {
            border-color: var(--primary-light);
            color: var(--primary);
            transform: translateY(-1px);
        }

        /* OJS Banner & Showcase */
        .ojs-banner-card {
            background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 55%, #1e293b 100%);
            border: 1px solid rgba(244, 63, 94, 0.35);
            border-radius: 24px;
            padding: 42px;
            color: #ffffff;
            box-shadow: 0 20px 45px -15px rgba(244, 63, 94, 0.2);
            position: relative;
            overflow: hidden;
            margin-bottom: 40px;
        }

        .ojs-banner-card::before {
            content: '';
            position: absolute;
            top: -40%;
            right: -15%;
            width: 500px;
            height: 500px;
            background: radial-gradient(circle, rgba(244, 63, 94, 0.16) 0%, transparent 70%);
            pointer-events: none;
        }

        .ojs-grid {
            display: grid;
            grid-template-columns: 1.2fr 0.8fr;
            gap: 36px;
            align-items: center;
            position: relative;
            z-index: 2;
        }

        .ojs-code-card {
            background: #020617;
            border: 1px solid rgba(51, 65, 85, 0.8);
            border-radius: 16px;
            padding: 22px;
            font-family: 'JetBrains Mono', monospace;
            font-size: 12px;
            color: #38bdf8;
            overflow-x: auto;
            box-shadow: 0 12px 32px rgba(0, 0, 0, 0.6);
        }

        /* Persona Solution Cards */
        .persona-card {
            background: #ffffff;
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 32px;
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .persona-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 14px 30px -4px rgba(67, 56, 202, 0.12);
            border-color: rgba(99, 102, 241, 0.45);
        }

        .persona-list {
            list-style: none;
            padding: 0;
            margin: 20px 0 28px;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .persona-list li {
            font-size: 13px;
            color: var(--text-muted);
            display: flex;
            align-items: flex-start;
            gap: 10px;
            line-height: 1.5;
        }

        .persona-list li span.check-icon {
            color: #10b981;
            font-weight: 800;
            font-size: 14px;
            flex-shrink: 0;
            margin-top: 1px;
        }

        /* FAQ Accordion Section */
        .faq-section {
            background: #ffffff;
            border-bottom: 1px solid var(--border);
            padding: 84px 0;
        }

        .faq-list {
            max-width: 860px;
            margin: 40px auto 0 auto;
            display: flex;
            flex-direction: column;
            gap: 14px;
        }

        .faq-card {
            background: #f8fafc;
            border: 1px solid var(--border);
            border-radius: 14px;
            overflow: hidden;
            transition: all 0.25s ease;
        }

        .faq-card:hover {
            border-color: #cbd5e1;
            background: #ffffff;
            box-shadow: 0 4px 14px rgba(15, 23, 42, 0.04);
        }

        .faq-card.active {
            border-color: var(--primary-light);
            background: #ffffff;
            box-shadow: 0 10px 25px -5px rgba(67, 56, 202, 0.12);
        }

        .faq-btn {
            width: 100%;
            padding: 20px 24px;
            text-align: left;
            background: none;
            border: none;
            outline: none;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            font-family: inherit;
            font-size: 16px;
            font-weight: 700;
            color: var(--dark);
            line-height: 1.45;
        }

        .faq-btn:hover {
            color: var(--primary);
        }

        .faq-icon {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: #e2e8f0;
            color: var(--dark);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 13px;
            font-weight: 800;
            flex-shrink: 0;
            transition: all 0.3s ease;
        }

        .faq-card.active .faq-icon {
            background: var(--primary);
            color: #ffffff;
            transform: rotate(180deg);
        }

        .faq-content {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.35s ease, padding 0.35s ease;
            padding: 0 24px;
            color: var(--text-muted);
            font-size: 14.5px;
            line-height: 1.7;
        }

        .faq-card.active .faq-content {
            padding: 0 24px 22px 24px;
            max-height: 600px;
        }

        .faq-content p {
            margin-bottom: 8px;
        }

        .faq-content p:last-child {
            margin-bottom: 0;
        }

        /* ========================================================
           Mobile Navigation & Drawer Components
           ======================================================== */
        .hamburger-btn {
            display: none;
            width: 40px;
            height: 40px;
            border-radius: 10px;
            background: #f8fafc;
            border: 1px solid var(--border);
            align-items: center;
            justify-content: center;
            flex-direction: column;
            gap: 5px;
            cursor: pointer;
            padding: 0;
            transition: all 0.2s ease;
            color: var(--dark);
            flex-shrink: 0;
        }

        .hamburger-btn:hover {
            background: #f1f5f9;
            border-color: #cbd5e1;
        }

        .hamburger-btn span {
            display: block;
            width: 20px;
            height: 2px;
            background: var(--dark);
            border-radius: 2px;
            transition: all 0.28s cubic-bezier(0.4, 0, 0.2, 1);
            transform-origin: center;
        }

        .hamburger-btn.active span:nth-child(1) {
            transform: translateY(7px) rotate(45deg);
            background: var(--primary);
        }

        .hamburger-btn.active span:nth-child(2) {
            opacity: 0;
            transform: scaleX(0);
        }

        .hamburger-btn.active span:nth-child(3) {
            transform: translateY(-7px) rotate(-45deg);
            background: var(--primary);
        }

        /* Mobile Nav Overlay & Drawer */
        .mobile-nav-overlay {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.65);
            backdrop-filter: blur(5px);
            -webkit-backdrop-filter: blur(5px);
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
            box-shadow: -8px 0 32px rgba(15, 23, 42, 0.25);
            transform: translateX(100%);
            visibility: hidden;
            transition: transform 0.32s cubic-bezier(0.16, 1, 0.3, 1), visibility 0.32s ease;
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
            padding: 16px 20px;
            border-bottom: 1px solid var(--border);
            background: #f8fafc;
        }

        .mobile-drawer-close {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            border: 1px solid var(--border);
            background: #ffffff;
            color: var(--text-muted);
            font-size: 22px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s ease;
            line-height: 1;
        }

        .mobile-drawer-close:hover {
            background: #fee2e2;
            color: #ef4444;
            border-color: #fca5a5;
        }

        .mobile-drawer-user {
            padding: 16px 20px;
            border-bottom: 1px solid var(--border);
            background: #ffffff;
        }

        .mobile-user-card {
            background: #f8fafc;
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 14px;
        }

        .mobile-guest-card {
            background: linear-gradient(135deg, #f8fafc 0%, #eef2ff 100%);
            border: 1px solid #c7d2fe;
            border-radius: 14px;
            padding: 14px;
        }

        .mobile-drawer-body {
            flex: 1;
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
            padding: 18px 20px;
        }

        .mobile-nav-group-title {
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: var(--text-muted);
            margin-bottom: 10px;
        }

        .mobile-nav-menu {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .mobile-nav-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 11px 14px;
            border-radius: 12px;
            text-decoration: none;
            color: var(--dark);
            font-size: 14px;
            font-weight: 600;
            transition: all 0.2s ease;
        }

        .mobile-nav-link:hover, .mobile-nav-link:active {
            background: #f1f5f9;
            color: var(--primary);
        }

        .mobile-nav-link.highlight {
            background: rgba(99, 102, 241, 0.08);
            color: var(--primary);
            border: 1px solid rgba(99, 102, 241, 0.2);
        }

        .mobile-nav-link .nav-icon {
            font-size: 17px;
            width: 24px;
            text-align: center;
            flex-shrink: 0;
        }

        .mobile-nav-link .nav-arrow {
            margin-left: auto;
            color: #94a3b8;
            font-size: 18px;
            font-weight: 700;
        }

        .mobile-nav-link .nav-badge {
            margin-left: auto;
            font-size: 10px;
            font-weight: 700;
            padding: 2px 7px;
            border-radius: 10px;
            background: #e2e8f0;
            color: #475569;
        }

        .mobile-drawer-footer {
            padding: 14px 20px;
            border-top: 1px solid var(--border);
            background: #f8fafc;
            text-align: center;
        }

        .mobile-footer-links {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            font-size: 12px;
            color: var(--text-muted);
            margin-bottom: 6px;
        }

        .mobile-footer-links a {
            color: var(--text-muted);
            text-decoration: none;
        }

        .mobile-footer-links a:hover {
            color: var(--primary);
            text-decoration: underline;
        }

        .mobile-footer-brand {
            font-size: 11px;
            color: #94a3b8;
        }

        .mobile-footer-brand a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
        }

        @media (min-width: 993px) and (max-width: 1260px) {
            .nav-links { gap: 2px; }
            .nav-links > li > a, .nav-dropdown-toggle { font-size: 12.5px; padding: 6px 7px; }
            .brand-title { font-size: 18px; }
            .brand-badge { display: none; }
        }

        /* ========================================================
           Responsive Media Queries (Mobile First Tuning)
           ======================================================== */
        @media (max-width: 992px) {
            body { padding-top: 62px; }
            .nav-links { display: none; }
            .hamburger-btn { display: flex; }
            .btn-desktop-only { display: none !important; }
            .container { padding: 0 18px; }

            /* Topbar */
            .navbar { padding: 10px 0; }
            .nav-actions { gap: 8px; }

            /* Go to Top Mobile */
            .btn-scroll-top {
                bottom: 20px;
                right: 20px;
                width: 42px;
                height: 42px;
                border-radius: 12px;
            }

            /* Hero */
            .hero { padding: 44px 0 36px; }
            .hero-grid { grid-template-columns: 1fr; gap: 28px; margin-bottom: 28px; }
            .hero-title { font-size: 32px; letter-spacing: -0.5px; line-height: 1.22; }
            .hero-subtitle { font-size: 15px; margin-bottom: 24px; line-height: 1.6; }
            .hero-buttons { flex-direction: column; width: 100%; gap: 10px; margin-bottom: 24px; }
            .hero-buttons .btn { width: 100%; justify-content: center; padding: 13px 18px; font-size: 14px; }
            
            /* Anti-overlap for Hero Pills */
            .hero-pills-wrap { margin-top: 0 !important; margin-bottom: 24px !important; gap: 8px; justify-content: flex-start; }
            .hero-pill-badge { font-size: 11px; padding: 4px 10px; }
            .hero-badge { font-size: 11.5px; padding: 6px 12px; line-height: 1.45; max-width: 100%; display: inline-block; word-break: break-word; }

            /* Stats Bar */
            .stats-bar { grid-template-columns: repeat(2, 1fr); gap: 12px; padding: 18px 16px; border-radius: 14px; }
            .stat-val { font-size: 22px; }
            .stat-lbl { font-size: 11.5px; }
            .stats-bar .stat-item:nth-child(5) { grid-column: span 2; text-align: center; }

            /* General Sections */
            .section { padding: 64px 0; }
            .section-header { margin-bottom: 36px; }
            .section-title { font-size: 26px; letter-spacing: -0.5px; }
            .section-desc { font-size: 14.5px; }
            .grid-4, .grid-3, .grid-2 { grid-template-columns: 1fr; gap: 16px; }

            /* Theory & Spotlight */
            .theory-card { padding: 22px 18px; border-radius: 16px; }
            .spotlight-card { grid-template-columns: 1fr; gap: 20px; padding: 20px 16px; border-radius: 16px; }
            .layer-box { padding: 20px 16px; border-radius: 16px; }
            .layer-item { flex-direction: column; gap: 8px; padding: 14px; border-radius: 12px; }

            /* Step Cards Anti-overlap */
            .step-card { padding: 22px 18px; border-radius: 16px; }
            .step-title { padding-right: 48px; font-size: 17px; }
            .step-index { font-size: 26px; top: 16px; right: 18px; }

            /* Dual-Reader Showcase */
            .dual-reader-showcase { padding: 20px 14px; border-radius: 16px; }
            .dual-reader-title { font-size: 20px; }
            .dual-reader-features { grid-template-columns: 1fr; gap: 12px; margin-top: 24px; }
            .browser-url { font-size: 10.5px; padding: 4px 8px; max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }

            /* OJS Section */
            .ojs-grid { grid-template-columns: 1fr; gap: 24px; }
            .ojs-banner-card { padding: 22px 16px; border-radius: 16px; }
            .ojs-banner-card .btn { width: 100%; justify-content: center; }
            .ojs-code-card { padding: 14px; font-size: 11px; }

            /* Persona & Incentives */
            .persona-card { padding: 22px 18px; border-radius: 16px; }
            .incentive-grid { grid-template-columns: 1fr; gap: 24px; }
            .incentive-banner { padding: 24px 18px; border-radius: 18px; }
            .incentive-title { font-size: 24px; }

            /* Pricing */
            #langganan .pricing-grid { gap: 28px; }
            .pricing-card { padding: 24px 18px; border-radius: 18px; }
            .pricing-card.popular { margin-top: 10px; }
            .pricing-badge { font-size: 10.5px; padding: 3px 12px; }

            /* Footer */
            .footer { padding: 50px 0 24px; }
            .footer-grid { grid-template-columns: 1fr; gap: 28px; }
            .footer-counter-wrap { margin-bottom: 20px; }
            .visitor-counter-badge { font-size: 11.5px; padding: 8px 14px; gap: 6px 8px; border-radius: 20px; }

            /* Modals Mobile Friendly */
            .modal-overlay { padding: 12px; }
            .modal-card, .modal-card-md, .modal-card-lg { max-height: 94vh; width: 100%; border-radius: 16px; }
            .modal-header { padding: 14px 16px; }
            .modal-body { padding: 16px; max-height: calc(94vh - 65px); overflow-y: auto; -webkit-overflow-scrolling: touch; }
        }

        @media (max-width: 480px) {
            .container { padding: 0 14px; }
            .navbar { padding: 10px 0; }
            .brand-logo { width: 32px; height: 32px; }
            .brand-title { font-size: 18px; }
            .brand-badge { display: none; }
            .hero-title { font-size: 25px; line-height: 1.25; }
            .hero-subtitle { font-size: 14px; }
            .section-title { font-size: 22px; }
            .hero-buttons .btn { font-size: 13.5px; padding: 11px 14px; }
            .stat-val { font-size: 20px; }
            .nav-actions .btn-outline { display: none !important; }
            .browser-url { max-width: 150px; }
        }

        /* ========================================================== */
        /* TESTIMONIALS CAROUSEL & CARDS (RESPONSIVE DESKTOP & MOBILE) */
        /* ========================================================== */
        .testi-trust-bar-wrap {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 20px;
            flex-wrap: wrap;
            margin-bottom: 26px;
        }

        .testi-trust-bar {
            background: #ffffff;
            border: 1px solid var(--border);
            border-radius: 100px;
            padding: 8px 24px;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            box-shadow: var(--shadow-sm);
        }

        .testi-trust-stars {
            color: #f59e0b;
            font-size: 16px;
            line-height: 1;
        }

        .testi-trust-score {
            font-size: 13px;
            font-weight: 700;
            color: var(--text-dark);
            line-height: 1;
        }

        .testi-trust-desc {
            font-size: 12px;
            color: var(--text-muted);
            line-height: 1;
        }

        /* Filter Kategori Testimoni Interaktif */
        .testi-filter-wrapper {
            margin-bottom: 30px;
            width: 100%;
        }

        .testi-filter-container {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .testi-filter-btn {
            padding: 7px 18px;
            border-radius: 9999px;
            font-size: 12px;
            font-weight: 700;
            border: 1px solid var(--border);
            background: #ffffff;
            color: var(--text-dark);
            cursor: pointer;
            transition: all 0.2s ease;
            white-space: nowrap;
        }

        .testi-filter-btn.active-testi-filter {
            border-color: var(--primary) !important;
            background: var(--primary) !important;
            color: #ffffff !important;
        }

        .testi-carousel-wrapper {
            position: relative;
            width: 100%;
        }

        .testi-carousel-track {
            display: flex;
            gap: 24px;
            overflow-x: auto;
            scroll-snap-type: x mandatory;
            scroll-behavior: smooth;
            -webkit-overflow-scrolling: touch;
            scrollbar-width: none;
            -ms-overflow-style: none;
            padding: 16px 4px 20px 4px;
            box-sizing: border-box;
            transition: all 0.3s ease;
        }

        .testi-carousel-track::-webkit-scrollbar {
            display: none;
        }

        /* Mode No-Carousel (Saat kartu <= batas kolom, misal <= 3 di desktop) */
        @media (min-width: 1024px) {
            .testi-carousel-track.no-carousel {
                overflow-x: visible;
                justify-content: center;
                scroll-snap-type: none;
            }
        }
        @media (min-width: 768px) and (max-width: 1023px) {
            .testi-carousel-track.no-carousel {
                overflow-x: visible;
                justify-content: center;
                scroll-snap-type: none;
            }
        }

        /* Base Card Styling */
        .testi-card {
            scroll-snap-align: start;
            flex: 0 0 100%;
            width: 100%;
            min-width: 100%;
            max-width: 100%;
            box-sizing: border-box;
            background: #ffffff;
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 26px 22px;
            box-shadow: var(--shadow-sm);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            transition: transform 0.25s ease, box-shadow 0.25s ease, border-color 0.25s ease;
            position: relative;
        }

        .testi-card:hover {
            box-shadow: 0 12px 28px -6px rgba(0, 0, 0, 0.08);
            border-color: #cbd5e1;
        }

        .testi-featured-badge {
            position: absolute;
            top: -10px;
            right: 20px;
            background: #fef3c7;
            color: #92400e;
            border: 1px solid #fde68a;
            border-radius: 20px;
            padding: 2px 10px;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.04);
            letter-spacing: 0.02em;
        }

        .testi-card-quote {
            font-size: 13.5px;
            line-height: 1.65;
            color: var(--text-dark);
            margin-bottom: 20px;
            font-style: italic;
        }

        /* Author Meta Row */
        .testi-author-row {
            border-top: 1px solid #f1f5f9;
            padding-top: 14px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-top: auto;
        }

        .testi-author-left {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            min-width: 0;
            width: 100%;
        }

        .testi-avatar {
            width: 40px;
            height: 40px;
            border-radius: 12px;
            background: #e0e7ff;
            color: #4338ca;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 14px;
            flex-shrink: 0;
        }

        .testi-author-meta {
            min-width: 0;
            flex: 1;
        }

        .testi-author-name-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            margin-bottom: 3px;
        }

        .testi-author-name {
            font-weight: 700;
            font-size: 13px;
            color: var(--text-dark);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .testi-verified-badge {
            background: #ecfdf5;
            color: #047857;
            border: 1px solid #a7f3d0;
            border-radius: 12px;
            padding: 2px 7px;
            font-size: 10px;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 3px;
            white-space: nowrap;
            flex-shrink: 0;
        }

        .testi-author-role {
            font-size: 11.5px;
            color: var(--primary);
            font-weight: 600;
            line-height: 1.35;
            word-break: break-word;
        }

        .testi-author-inst {
            font-size: 11px;
            color: var(--text-muted);
            line-height: 1.35;
            word-break: break-word;
        }

        /* Callout CTA Box */
        .testi-cta-box {
            margin-top: 36px;
            background: #ffffff;
            border: 1px dashed var(--border);
            border-radius: 20px;
            padding: 20px 26px;
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
        }

        /* Tablet (min-width: 768px and max-width: 1023px): 2 cards per view */
        @media (min-width: 768px) and (max-width: 1023px) {
            .testi-card {
                flex: 0 0 calc((100% - 24px) / 2);
                width: calc((100% - 24px) / 2);
                min-width: calc((100% - 24px) / 2);
                max-width: calc((100% - 24px) / 2);
            }
        }

        /* Desktop (min-width: 1024px): 3 cards per view */
        @media (min-width: 1024px) {
            .testi-card {
                flex: 0 0 calc((100% - 48px) / 3);
                width: calc((100% - 48px) / 3);
                min-width: calc((100% - 48px) / 3);
                max-width: calc((100% - 48px) / 3);
                padding: 28px;
            }
        }

        /* Floating Nav Arrows for Desktop/Tablet */
        .testi-nav-arrow {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background: #ffffff;
            border: 1px solid var(--border);
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.08);
            color: var(--text-dark, #1e293b);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            z-index: 10;
            transition: all 0.2s ease;
            outline: none;
        }

        .testi-nav-arrow:hover:not(:disabled) {
            background: var(--primary, #4338ca);
            color: #ffffff;
            border-color: var(--primary, #4338ca);
            box-shadow: 0 6px 20px rgba(67, 56, 202, 0.25);
            transform: translateY(-50%) scale(1.06);
        }

        .testi-nav-arrow:disabled {
            opacity: 0.3;
            cursor: not-allowed;
            pointer-events: none;
        }

        .testi-prev-arrow {
            left: -22px;
        }

        .testi-next-arrow {
            right: -22px;
        }

        /* Controls Bar (Dots & Mobile Nav) */
        .testi-controls-bar {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-top: 14px;
            margin-bottom: 8px;
        }

        .testi-controls-inner {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
        }

        .testi-dots-container {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .testi-dot {
            width: 8px;
            height: 8px;
            border-radius: 9999px;
            background: #cbd5e1;
            cursor: pointer;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            border: none;
            padding: 0;
        }

        .testi-dot:hover {
            background: #94a3b8;
        }

        .testi-dot.active {
            width: 26px;
            background: var(--primary, #4338ca);
            box-shadow: 0 2px 8px rgba(67, 56, 202, 0.35);
        }

        .testi-mobile-nav-btn {
            display: none;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: #ffffff;
            border: 1px solid var(--border);
            color: var(--text-dark, #1e293b);
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s ease;
            box-shadow: var(--shadow-sm);
        }

        .testi-mobile-nav-btn:hover:not(:disabled) {
            background: var(--primary, #4338ca);
            color: #ffffff;
            border-color: var(--primary, #4338ca);
        }

        .testi-mobile-nav-btn:disabled {
            opacity: 0.3;
            cursor: not-allowed;
            pointer-events: none;
        }

        .testi-counter-badge {
            font-size: 11px;
            font-weight: 700;
            color: var(--text-muted, #64748b);
            background: #f1f5f9;
            padding: 3px 10px;
            border-radius: 100px;
            letter-spacing: 0.04em;
        }

        /* Responsive Mobile Specific (max-width: 767px) */
        @media (max-width: 767px) {
            #testimoni {
                padding-top: 48px;
                padding-bottom: 60px;
            }

            .testi-trust-bar-wrap {
                margin-bottom: 16px;
            }

            .testi-trust-bar {
                padding: 6px 14px;
                gap: 8px;
            }

            .testi-trust-stars {
                font-size: 13px;
            }

            .testi-trust-score {
                font-size: 12px;
            }

            .testi-trust-desc {
                font-size: 11px;
            }

            /* Single horizontal scrollable row for filter buttons on mobile */
            .testi-filter-wrapper {
                margin: 0 -18px 16px -18px;
                padding: 0;
            }

            .testi-filter-container {
                justify-content: flex-start;
                flex-wrap: nowrap;
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
                scrollbar-width: none;
                gap: 8px;
                padding: 4px 18px 8px 18px;
            }

            .testi-filter-container::-webkit-scrollbar {
                display: none;
            }

            .testi-filter-btn {
                flex-shrink: 0;
                padding: 6px 14px !important;
                font-size: 11.5px !important;
            }

            .testi-nav-arrow {
                display: none !important;
            }

            .testi-mobile-nav-btn {
                display: flex;
                width: 38px;
                height: 38px;
            }

            .testi-carousel-track {
                padding-left: 2px;
                padding-right: 2px;
                padding-top: 12px;
                padding-bottom: 12px;
                gap: 16px;
            }

            .testi-card {
                padding: 22px 18px 18px;
            }

            .testi-card-quote {
                font-size: 13px;
                line-height: 1.6;
                margin-bottom: 16px;
            }

            .testi-controls-bar {
                margin-top: 14px;
                margin-bottom: 24px;
                gap: 8px;
            }

            .testi-cta-box {
                margin-top: 24px;
                padding: 18px 16px;
                flex-direction: column;
                align-items: flex-start;
                gap: 14px;
            }

            .testi-cta-box .btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>

    <!-- Header Navigation -->
    <header class="navbar">
        <div class="container nav-inner">
            <a href="<?= base_url() ?>" class="brand">
                <img src="<?= base_url('images/logo.png') ?>" alt="AR Tilik Logo" class="brand-logo" width="40" height="40">
                <div class="brand-text">
                    <span class="brand-title">AR Tilik</span>
                    <span class="brand-badge">v1.3.0</span>
                </div>
            </a>

            <ul class="nav-links">
                <!-- Dropdown: Fitur & Solusi -->
                <li class="nav-item-dropdown">
                    <button type="button" class="nav-dropdown-toggle" onclick="toggleNavDropdown(event)" aria-expanded="false" aria-haspopup="true">
                        <span>Fitur &amp; Solusi</span>
                        <svg class="dropdown-chevron" width="11" height="11" viewBox="0 0 12 12" fill="none">
                            <path d="M2.5 4.5L6 8L9.5 4.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </button>
                    <div class="nav-dropdown-menu">
                        <a href="#fitur-unggulan" class="dropdown-item" onclick="closeAllNavDropdowns()">
                            <div class="dropdown-icon" style="background: #eef2ff; color: #4338ca;">⚡</div>
                            <div class="dropdown-content">
                                <span class="dropdown-title">Inovasi Mesin</span>
                                <span class="dropdown-sub">Dual-Lens 5-Gram &amp; Anti-Cheat Shield</span>
                            </div>
                        </a>
                        <a href="#integrasi-ojs" class="dropdown-item" onclick="closeAllNavDropdowns()">
                            <div class="dropdown-icon" style="background: #f0fdf4; color: #16a34a;">🔌</div>
                            <div class="dropdown-content">
                                <span class="dropdown-title">Integrasi OJS 3.x</span>
                                <span class="dropdown-sub">Konektor otomatisasi naskah jurnal OJS</span>
                            </div>
                        </a>
                        <a href="#solusi" class="dropdown-item" onclick="closeAllNavDropdowns()">
                            <div class="dropdown-icon" style="background: #eff6ff; color: #2563eb;">🏛️</div>
                            <div class="dropdown-content">
                                <span class="dropdown-title">Solusi Institusi</span>
                                <span class="dropdown-sub">Sivitas akademika, LPPM &amp; dewan penguji</span>
                            </div>
                        </a>
                        <a href="#cara-kerja" class="dropdown-item" onclick="closeAllNavDropdowns()">
                            <div class="dropdown-icon" style="background: #e0f2fe; color: #0284c7;">🔄</div>
                            <div class="dropdown-content">
                                <span class="dropdown-title">Cara Kerja</span>
                                <span class="dropdown-sub">Alur 4 tahap telaah kesepadanan naskah</span>
                            </div>
                        </a>
                        <a href="#teori" class="dropdown-item" onclick="closeAllNavDropdowns()">
                            <div class="dropdown-icon" style="background: #fdf4ff; color: #9333ea;">⚖️</div>
                            <div class="dropdown-content">
                                <span class="dropdown-title">Filosofi &amp; Teori Bukti</span>
                                <span class="dropdown-sub">Keadilan akademik &amp; kedaulatan naskah</span>
                            </div>
                        </a>
                        <a href="<?= base_url('riset/ar-tilik-vs-turnitin') ?>" class="dropdown-item" onclick="closeAllNavDropdowns()">
                            <div class="dropdown-icon" style="background: #f0fdfa; color: #0f766e;">🏛️</div>
                            <div class="dropdown-content">
                                <span class="dropdown-title">Riset: AR Tilik vs Turnitin</span>
                                <span class="dropdown-sub">Analisis untuk jurnal &amp; kampus Indonesia</span>
                            </div>
                        </a>
                        <a href="<?= base_url('riset/ar-tilik-vs-zerogpt') ?>" class="dropdown-item" onclick="closeAllNavDropdowns()">
                            <div class="dropdown-icon" style="background: #fff7ed; color: #ea580c;">📊</div>
                            <div class="dropdown-content">
                                <span class="dropdown-title">Riset: AR Tilik vs ZeroGPT</span>
                                <span class="dropdown-sub">Whitepaper audit bukti vs deteksi AI</span>
                            </div>
                        </a>
                        <a href="<?= base_url('benchmark') ?>" class="dropdown-item" onclick="closeAllNavDropdowns()">
                            <div class="dropdown-icon" style="background: #eef2ff; color: #4338ca;">🔬</div>
                            <div class="dropdown-content">
                                <span class="dropdown-title">Benchmark Terbuka Indonesia</span>
                                <span class="dropdown-sub">Hasil uji empiris &amp; transparansi 14 mismatch</span>
                            </div>
                        </a>
                        <a href="<?= base_url('docs/Brosur_Eksekutif_ARTilik.pdf') ?>" target="_blank" download="Brosur_Eksekutif_ARTilik.pdf" class="dropdown-item" onclick="closeAllNavDropdowns()">
                            <div class="dropdown-icon" style="background: #ecfdf5; color: #059669;">📄</div>
                            <div class="dropdown-content">
                                <span class="dropdown-title">Brosur Eksekutif Kampus</span>
                                <span class="dropdown-sub">Prospektus resmi institusi (PDF 2.7 MB)</span>
                            </div>
                        </a>
                    </div>
                </li>

                <?php if (!empty($approvedTestimonials)): ?>
                    <li><a href="#testimoni">Testimoni</a></li>
                <?php endif; ?>
                <li><a href="#langganan">Tarif</a></li>
                <li><a href="#faq">FAQ</a></li>
                <li>
                    <a href="<?= base_url('ambassador') ?>" class="nav-link-ambassador" title="Program Kemitraan &amp; Afiliasi Duta Kampus (Komisi 20%)">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="color: #d97706; flex-shrink: 0;">
                            <path d="M22 10v6M2 10l10-5 10 5-10 5z"/>
                            <path d="M6 12v5c3 3 9 3 12 0v-5"/>
                        </svg>
                        <span>Duta Kampus</span>
                        <span class="nav-badge-ambassador">20%</span>
                    </a>
                </li>
                <li>
                    <a href="<?= base_url('verify') ?>" class="nav-link-verify" title="Verifikasi Keaslian Sertifikat &amp; Laporan">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                        </svg>
                        <span>Verifikasi QR</span>
                    </a>
                </li>
            </ul>

            <div class="nav-actions">
                <?php if ($currentUser) : ?>
                    <a href="<?= base_url('dashboard') ?>" class="btn btn-primary btn-sm">
                        <span>📊</span> Dashboard
                    </a>
                    <a href="<?= base_url('auth/logout') ?>" class="btn btn-secondary btn-sm btn-desktop-only" title="Keluar">
                        Keluar
                    </a>
                <?php else : ?>
                    <button type="button" onclick="openAuthModal('login')" class="btn btn-outline btn-sm btn-desktop-only">
                        Masuk
                    </button>
                    <button type="button" onclick="openAuthModal('register')" class="btn btn-primary btn-sm">
                        Daftar Gratis
                    </button>
                <?php endif; ?>

                <!-- Mobile Hamburger Toggle Button -->
                <button type="button" id="hamburgerBtn" class="hamburger-btn" onclick="toggleMobileNav()" aria-label="Buka Menu Navigasi" aria-expanded="false">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>
            </div>
        </div>
    </header>

    <!-- Mobile Navigation Drawer Overlay -->
    <div id="mobileNavOverlay" class="mobile-nav-overlay" onclick="closeMobileNav()"></div>

    <!-- Mobile Navigation Drawer -->
    <div id="mobileNavDrawer" class="mobile-nav-drawer" aria-hidden="true" inert>
        <div class="mobile-drawer-header">
            <a href="<?= base_url() ?>" class="brand" onclick="closeMobileNav()">
                <img src="<?= base_url('images/logo.png') ?>" alt="AR Tilik Logo" width="30" height="30" style="width: 30px; height: 30px; object-fit: contain; border-radius: 8px;">
                <div class="brand-text">
                    <span class="brand-title" style="font-size: 18px;">AR Tilik</span>
                    <span class="brand-badge">v1.3.0</span>
                </div>
            </a>
            <button type="button" class="mobile-drawer-close" onclick="closeMobileNav()" aria-label="Tutup Menu">
                &times;
            </button>
        </div>

        <!-- Mobile User Status Card -->
        <div class="mobile-drawer-user">
            <?php if ($currentUser) : ?>
                <div class="mobile-user-card">
                    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 12px;">
                        <div style="width: 38px; height: 38px; border-radius: 50%; background: #e0e7ff; color: #4338ca; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 16px; flex-shrink: 0;">
                            <?= strtoupper(substr($currentUser['name'] ?? 'U', 0, 1)) ?>
                        </div>
                        <div style="overflow: hidden;">
                            <div style="font-weight: 800; font-size: 14px; color: var(--dark); white-space: nowrap; text-overflow: ellipsis; overflow: hidden;"><?= esc($currentUser['name'] ?? 'Akun Pengguna') ?></div>
                            <div style="font-size: 11px; color: var(--text-muted); white-space: nowrap; text-overflow: ellipsis; overflow: hidden;"><?= esc($currentUser['email'] ?? '') ?></div>
                        </div>
                    </div>
                    <div style="display: flex; gap: 8px;">
                        <a href="<?= base_url('dashboard') ?>" class="btn btn-primary btn-sm" style="flex: 1; justify-content: center;" onclick="closeMobileNav()">
                            <span>📊</span> Buka Dashboard
                        </a>
                        <a href="<?= base_url('auth/logout') ?>" class="btn btn-secondary btn-sm" style="padding: 6px 12px;">
                            Keluar
                        </a>
                    </div>
                </div>
            <?php else : ?>
                <div class="mobile-guest-card">
                    <div style="font-size: 12px; color: var(--text-muted); margin-bottom: 10px; line-height: 1.4;">
                        ✨ Hak Akses Sivitas: <strong>3 Submisi Gratis Tiap Minggu</strong>
                    </div>
                    <div style="display: flex; flex-direction: column; gap: 8px;">
                        <button type="button" onclick="closeMobileNav(); openAuthModal('register');" class="btn btn-primary" style="width: 100%; justify-content: center; padding: 11px 14px; font-size: 13.5px;">
                            Daftar Akun Gratis
                        </button>
                        <button type="button" onclick="closeMobileNav(); openAuthModal('login');" class="btn btn-outline" style="width: 100%; justify-content: center; padding: 10px 14px; font-size: 13px;">
                            Masuk ke Akun
                        </button>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Mobile Navigation Links -->
        <div class="mobile-drawer-body">
            <div class="mobile-nav-group-title">Eksplorasi Layanan</div>
            <nav class="mobile-nav-menu">
                <a href="#teori" class="mobile-nav-link" onclick="closeMobileNav()">
                    <span class="nav-icon">🔍</span>
                    <span>Filosofi &amp; Teori Bukti</span>
                    <span class="nav-arrow">›</span>
                </a>
                <a href="#fitur-unggulan" class="mobile-nav-link" onclick="closeMobileNav()">
                    <span class="nav-icon">⚡</span>
                    <span>Inovasi Mesin Cerdas</span>
                    <span class="nav-arrow">›</span>
                </a>
                <a href="#cara-kerja" class="mobile-nav-link" onclick="closeMobileNav()">
                    <span class="nav-icon">🔄</span>
                    <span>Cara Kerja 4 Langkah</span>
                    <span class="nav-arrow">›</span>
                </a>
                <a href="#integrasi-ojs" class="mobile-nav-link" onclick="closeMobileNav()">
                    <span class="nav-icon">🔌</span>
                    <span>Integrasi OJS 3.x Jurnal</span>
                    <span class="nav-arrow">›</span>
                </a>
                <a href="#solusi" class="mobile-nav-link" onclick="closeMobileNav()">
                    <span class="nav-icon">👥</span>
                    <span>Solusi Sivitas &amp; Kampus</span>
                    <span class="nav-arrow">›</span>
                </a>
                <?php if (!empty($approvedTestimonials)): ?>
                <a href="#testimoni" class="mobile-nav-link" onclick="closeMobileNav()">
                    <span class="nav-icon">🌟</span>
                    <span>Testimoni Akademisi</span>
                    <span class="nav-arrow">›</span>
                </a>
                <?php endif; ?>
                <a href="#langganan" class="mobile-nav-link" onclick="closeMobileNav()">
                    <span class="nav-icon">💳</span>
                    <span>Paket Tarif &amp; Top-Up</span>
                    <span class="nav-arrow">›</span>
                </a>
                <a href="#faq" class="mobile-nav-link" onclick="closeMobileNav()">
                    <span class="nav-icon">❓</span>
                    <span>Tanya Jawab (FAQ)</span>
                    <span class="nav-arrow">›</span>
                </a>
                <a href="<?= base_url('verify') ?>" class="mobile-nav-link highlight" onclick="closeMobileNav()">
                    <span class="nav-icon">🛡️</span>
                    <span>Portal Verifikasi Sertifikat QR</span>
                    <span class="nav-arrow">›</span>
                </a>
                <a href="<?= base_url('ambassador') ?>" class="mobile-nav-link" onclick="closeMobileNav()" style="background: rgba(245, 158, 11, 0.08); border-left: 3px solid #f59e0b;">
                    <span class="nav-icon">🎓</span>
                    <span>Program Duta Kampus (Afiliasi)</span>
                    <span class="nav-badge" style="background: rgba(245, 158, 11, 0.2); color: #d97706; font-weight: 800;">Komisi 20%</span>
                </a>
            </nav>

            <div class="mobile-nav-group-title" style="margin-top: 20px;">Unduhan &amp; Bantuan</div>
            <nav class="mobile-nav-menu">
                <a href="<?= base_url('riset/ar-tilik-vs-turnitin') ?>" class="mobile-nav-link" onclick="closeMobileNav()">
                    <span class="nav-icon">🏛️</span>
                    <span>Whitepaper: AR Tilik vs Turnitin</span>
                    <span class="nav-badge" style="background: rgba(15, 118, 110, 0.15); color: #0f766e; font-weight: 700;">Riset</span>
                </a>
                <a href="<?= base_url('riset/ar-tilik-vs-zerogpt') ?>" class="mobile-nav-link" onclick="closeMobileNav()">
                    <span class="nav-icon">📊</span>
                    <span>Whitepaper: AR Tilik vs ZeroGPT</span>
                    <span class="nav-badge" style="background: rgba(234, 88, 12, 0.15); color: #ea580c; font-weight: 700;">Riset</span>
                </a>
                <a href="<?= base_url('docs/Brosur_Eksekutif_ARTilik.pdf') ?>" target="_blank" download="Brosur_Eksekutif_ARTilik.pdf" class="mobile-nav-link" onclick="closeMobileNav()">
                    <span class="nav-icon">📄</span>
                    <span>Brosur Eksekutif Kampus (PDF)</span>
                    <span class="nav-badge" style="background: rgba(16, 185, 129, 0.2); color: #10b981;">2.7 MB</span>
                </a>
                <a href="<?= base_url('integrations/ojs/Panduan_Integrasi_OJS_ARTilik.pdf') ?>" target="_blank" download="Panduan_Integrasi_OJS_ARTilik.pdf" class="mobile-nav-link" onclick="closeMobileNav()">
                    <span class="nav-icon">📕</span>
                    <span>Buku Panduan OJS (PDF)</span>
                    <span class="nav-badge">2.0 MB</span>
                </a>
                <a href="<?= base_url('integrations/ojs/connector') ?>" download="ARTilikOJSConnector.php" class="mobile-nav-link" onclick="closeMobileNav()">
                    <span class="nav-icon">💻</span>
                    <span>Konektor PHP OJS 3.x</span>
                    <span class="nav-badge">PHP</span>
                </a>
                <button type="button" onclick="closeMobileNav(); openContactModal('Pertanyaan Umum');" class="mobile-nav-link" style="width: 100%; border: none; background: transparent; text-align: left; cursor: pointer; font: inherit;">
                    <span class="nav-icon">💬</span>
                    <span>Hubungi Tim Pengembang</span>
                    <span class="nav-arrow">›</span>
                </button>
            </nav>
        </div>

        <!-- Mobile Drawer Footer -->
        <div class="mobile-drawer-footer">
            <div class="mobile-footer-links">
                <a href="<?= base_url('privacy') ?>" onclick="closeMobileNav(); openLegalModal('privacy', event)">Privasi</a>
                <span>&bull;</span>
                <a href="<?= base_url('terms') ?>" onclick="closeMobileNav(); openLegalModal('terms', event)">Ketentuan</a>
                <span>&bull;</span>
                <a href="<?= base_url('sitemap.xml') ?>" target="_blank">Sitemap</a>
            </div>
            <div class="mobile-footer-brand">
                AR Tilik &copy; <?= date('Y') ?> &bull; <a href="https://www.arsetontong.top" target="_blank" rel="noopener noreferrer">Powered by ARSetontong</a>
            </div>
        </div>
    </div>

    <!-- Main Content Landmark -->
    <main id="main-content">

    <!-- Hero Section -->
    <section class="hero">
        <div class="container">
            <div class="hero-grid">
                <div>
                    <div class="hero-badge">
                        <span>🏛️</span> Standar Keadilan Akademik &bull; OJS 3.x Ready &bull; Lintas Bahasa EN⇄ID &bull; Anti-Cheat Shield
                    </div>
                    <h1 class="hero-title">
                        Cek Plagiarisme &amp; Penelaah Kesepadanan Naskah Berbasis <span>Bukti Ilmiah Terbuka</span>
                    </h1>
                    <p class="hero-subtitle">
                        AR Tilik hadir bukan untuk memvonis karya, melainkan menyediakan dekomposisi bukti kemiripan transparan, kesadaran konteks sitasi, audit kepatuhan daftar pustaka, integrasi otomatis OJS 3.x, serta sintesis risiko akademik terkalibrasi untuk sivitas perguruan tinggi.
                    </p>

                    <div class="hero-buttons">
                        <?php if ($currentUser) : ?>
                            <a href="<?= base_url('dashboard') ?>" class="btn btn-primary">
                                <span>🚀</span> Buka Dashboard Pemeriksaan
                            </a>
                        <?php else : ?>
                            <button type="button" onclick="openAuthModal('register')" class="btn btn-primary">
                                <span>✨</span> Mulai Gratis (3 Submisi/Minggu)
                            </button>
                        <?php endif; ?>
                        <a href="#integrasi-ojs" class="btn btn-outline" style="border-color: #f43f5e; color: #be123c; background: #fff1f2;">
                            <span>📕</span> Panduan OJS 3.x (PDF)
                        </a>
                        <a href="#fitur-unggulan" class="btn btn-outline">
                            <span>⚡</span> Inovasi Mesin
                        </a>
                    </div>

                    <!-- Micro Feature Badges -->
                    <div class="hero-pills-wrap">
                        <div class="hero-pill-badge"><span>⚡</span> Dual-Lens 5-Gram & TF-IDF</div>
                        <div class="hero-pill-badge"><span>🌐</span> Cross-Lingual EN⇄ID</div>
                        <div class="hero-pill-badge"><span>🛡️</span> Anti-Cheat Font/Homoglyph</div>
                        <div class="hero-pill-badge"><span>📑</span> Auditor Sitasi APA/IEEE</div>
                        <div class="hero-pill-badge"><span>🦅</span> Pemanen Neliti & GARUDA Dikti</div>
                    </div>
                </div>

                <div>
                    <div class="hero-visual-card">
                        <picture>
                            <source srcset="<?= base_url('images/hero_illustration.webp') ?>" type="image/webp">
                            <img src="<?= base_url('images/hero_illustration.jpg') ?>" alt="Analisis Naskah Akademik AR Tilik" width="800" height="450" fetchpriority="high" decoding="async">
                        </picture>
                        <div class="hero-visual-overlay">
                            <span class="hero-visual-tag">
                                <span>🔬</span> Dual-Lens Spectrum Analysis
                            </span>
                            <span class="hero-visual-status">
                                Engine Active
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Stats Bar -->
            <div class="stats-bar">
                <div class="stat-item">
                    <div class="stat-val"><?= number_format($totalCorpus) ?>+</div>
                    <div class="stat-lbl">Korpus Terindeks</div>
                </div>
                <div class="stat-item">
                    <div class="stat-val"><?= number_format($totalReports) ?></div>
                    <div class="stat-lbl">Laporan Terbit</div>
                </div>
                <div class="stat-item">
                    <div class="stat-val"><?= number_format($visitorStats['total_unique'] ?? 1) ?></div>
                    <div class="stat-lbl">Pengunjung Unik</div>
                </div>
                <div class="stat-item">
                    <div class="stat-val">Hybrid</div>
                    <div class="stat-lbl">Exact &amp; IDF Engine</div>
                </div>
                <div class="stat-item">
                    <div class="stat-val">&lt; 150ms</div>
                    <div class="stat-lbl">SLA Parsing Sitasi</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Section 1: Teori & Filosofi Akademik -->
    <section id="teori" class="section">
        <div class="container">
            <div class="section-header" data-aos="fade-up" data-aos-duration="700">
                <div class="section-tag">Filosofi Ilmiah</div>
                <h2 class="section-title">Mengapa Similarity Bukan Berarti Plagiarisme?</h2>
                <p class="section-desc">
                    Banyak sistem komersial hanya menyodorkan satu angka persentase global yang sering disalahartikan sebagai vonis plagiarisme. AR Tilik dibangun dengan komitmen teguh terhadap asas keadilan akademik:
                </p>
            </div>

            <div class="grid-3" style="margin-bottom: 40px;">
                <div class="theory-card" data-aos="fade-up" data-aos-delay="100" data-aos-duration="700">
                    <div class="card-icon-badge badge-indigo">🔍</div>
                    <h3 class="theory-title">Dual-Lens Architecture</h3>
                    <p class="theory-desc">
                        Membedakan secara matematis antara <strong>Exact Matcher</strong> (5-gram shingle) untuk mendeteksi tumpang tindih kata berurutan, dan <strong>Sparse TF-IDF Cosine</strong> untuk menganalisis orientasi kosakata dan topik bersama.
                    </p>
                </div>

                <div class="theory-card" data-aos="fade-up" data-aos-delay="200" data-aos-duration="700">
                    <div class="card-icon-badge badge-emerald">📚</div>
                    <h3 class="theory-title">Citation Context Awareness</h3>
                    <p class="theory-desc">
                        Mengenali tanda petik langsung, blok kutipan, dan gaya sitasi standar (APA/IEEE/Harvard). Kutipan yang diatribusikan dengan benar tidak ditekan skor fisiknya, namun diberi label konteks pelindung agar tidak disalahpahami.
                    </p>
                </div>

                <div class="theory-card" data-aos="fade-up" data-aos-delay="300" data-aos-duration="700">
                    <div class="card-icon-badge badge-amber">⚖️</div>
                    <h3 class="theory-title">Sintesis Risiko Akademik Berbobot</h3>
                    <p class="theory-desc">
                        Model komputasi probabilistik terkalibrasi yang mensintesis 11 dimensi bukti keabsahan—termasuk estilometri panjang kalimat (burstiness), frasa baku metodologi, dan konsistensi naskah secara non-linear tanpa vonis kaku.
                    </p>
                </div>
            </div>

            <!-- Spotlight Card: Visualisasi Dual-Lens Engine -->
            <div class="spotlight-card" data-aos="fade-up" data-aos-duration="800">
                <div class="spotlight-img-wrap" data-aos="fade-right" data-aos-delay="100" data-aos-duration="800">
                    <picture>
                        <source srcset="<?= base_url('images/dual_lens_concept.webp') ?>" type="image/webp">
                        <img src="<?= base_url('images/dual_lens_concept.jpg') ?>" alt="Konsep Arsitektur Dual-Lens AR Tilik" loading="lazy" width="800" height="450">
                    </picture>
                    <div class="spotlight-caption">
                        <span>LENSA 1: Sequential 5-Gram</span>
                        <span>LENSA 2: Lexical TF-IDF</span>
                    </div>
                </div>

                <div data-aos="fade-left" data-aos-delay="150" data-aos-duration="800">
                    <div style="font-size: 11px; text-transform: uppercase; font-weight: 800; color: var(--primary); letter-spacing: 1px; margin-bottom: 6px;">Arsitektur Komparasi Presisi</div>
                    <h3 style="font-size: 24px; font-weight: 800; color: var(--dark); margin-bottom: 12px; letter-spacing: -0.5px;">Konvergensi Dua Lensa Bukti Ilmiah</h3>
                    <p style="font-size: 14px; color: var(--text-muted); line-height: 1.6; margin-bottom: 16px;">
                        Algoritma AR Tilik menolak pendekatan monolitik. Kami menggunakan dua lensa matematis independen yang dikombinasikan secara transparan untuk memisahkan karya orisinal dari modifikasi dangkal:
                    </p>

                    <div class="lens-list">
                        <div class="lens-item">
                            <div class="lens-item-badge badge-indigo">🎯</div>
                            <div>
                                <strong style="font-size: 13px; color: var(--dark);">Lensa Sequential (5-Gram Jaccard Shingle)</strong>
                                <p style="font-size: 12px; color: var(--text-muted); margin-top: 2px;">
                                    Menangkap frasa identik berturutan, pasase salin-tempel langsung, dan kontinuitas struktur kalimat secara deterministik.
                                </p>
                            </div>
                        </div>

                        <div class="lens-item">
                            <div class="lens-item-badge badge-cyan">📊</div>
                            <div>
                                <strong style="font-size: 13px; color: var(--dark);">Lensa Leksikal (Sparse TF-IDF Cosine Vector)</strong>
                                <p style="font-size: 12px; color: var(--text-muted); margin-top: 2px;">
                                    Mengukur kedekatan medan makna, distribusi leksikon tematik, dan konsistensi terminologi disiplin ilmu yang diteliti.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 3-Layer Separation Explanation Box -->
            <div class="layer-box" data-aos="fade-up" data-aos-duration="800">
                <h3 style="font-size: 22px; font-weight: 800; margin-bottom: 8px;">Prinsip Pemisahan Tiga Lapis (Three-Layer Separation)</h3>
                <p style="font-size: 14px; color: var(--text-muted); margin-bottom: 24px;">
                    Setiap hasil pemeriksaan naskah di AR Tilik didekomposisi ke dalam tiga domain yang terpisah tegas:
                </p>

                <div class="layer-item layer-a" data-aos="fade-up" data-aos-delay="100" data-aos-duration="600">
                    <div class="layer-num">LAPISAN A</div>
                    <div>
                        <strong style="color: #4338ca;">Machine Textual Evidence (Bukti Fisik Mesin)</strong>
                        <p style="font-size: 13px; color: var(--text-muted); margin-top: 4px;">
                            Mengukur tumpang tindih huruf dan kata secara objektif tanpa manipulasi threshold (Exact similarity %, pasase terpanjang, dan kemiripan kosinus leksikal).
                        </p>
                    </div>
                </div>

                <div class="layer-item layer-b" data-aos="fade-up" data-aos-delay="200" data-aos-duration="600">
                    <div class="layer-num">LAPISAN B</div>
                    <div>
                        <strong style="color: #059669;">Academic Context (Konteks Sitasi & Kutipan)</strong>
                        <p style="font-size: 13px; color: var(--text-muted); margin-top: 4px;">
                            Menjelaskan status kepatuhan ilmiah pasase: apakah berada di dalam tanda petik, disertai sitasi nama penulis dan tahun, atau bagian dari daftar pustaka.
                        </p>
                    </div>
                </div>

                <div class="layer-item layer-c" data-aos="fade-up" data-aos-delay="300" data-aos-duration="600">
                    <div class="layer-num">LAPISAN C</div>
                    <div>
                        <strong style="color: #d97706;">Human Academic Judgment (Keputusan Penelaah/Dosen)</strong>
                        <p style="font-size: 13px; color: var(--text-muted); margin-top: 4px;">
                            Kewenangan akhir tetap berada di tangan dosen pembimbing dan dewan etik akademik untuk menilai orisinalitas ide dan substansi naskah.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Section 1B: Riset Komparatif AR Tilik vs Detektor AI (ZeroGPT) -->
    <section id="riset-komparasi" class="section" style="background: #ffffff; border-top: 1px solid var(--border);">
        <div class="container">
            <div class="section-header" data-aos="fade-up" data-aos-duration="700">
                <div class="section-tag" style="background: #fff7ed; color: #ea580c; border: 1px solid #fed7aa;">Whitepaper &amp; Riset Komparatif</div>
                <h2 class="section-title">Bukan Lomba Menebak AI: Mengapa Kami Mengaudit Bukti Nyata?</h2>
                <p class="section-desc">
                    Detektor AI probabilitas seperti ZeroGPT menaksir <em>“apakah teks ini dibuat AI?”</em>, sementara AR Tilik memeriksa <em>“apa bukti empiris yang dapat diverifikasi dari naskah ini?”</em>. Pendekatan berbasis bukti menghindarkan tuduhan sepihak pada tulisan formal dan penulis non-native:
                </p>
            </div>

            <div class="grid-3" style="margin-bottom: 36px;">
                <div class="theory-card" style="border-top: 4px solid #ea580c;" data-aos="fade-up" data-aos-delay="100" data-aos-duration="700">
                    <div class="card-icon-badge" style="background: #fff7ed; color: #ea580c;">⚖️</div>
                    <h3 class="theory-title">Objek Ukur Berbeda</h3>
                    <p class="theory-desc">
                        Detektor konvensional bekerja lewat inferensi statistik gaya bahasa tertutup. AR Tilik menyajikan <strong>4 pilar bukti yang dapat dilacak</strong>: frasa sekuensial, orientasi leksikal, pasase beririsan, dan validasi keaslian rujukan.
                    </p>
                </div>

                <div class="theory-card" style="border-top: 4px solid #0284c7;" data-aos="fade-up" data-aos-delay="200" data-aos-duration="700">
                    <div class="card-icon-badge" style="background: #e0f2fe; color: #0284c7;">📊</div>
                    <h3 class="theory-title">Fakta False Positive</h3>
                    <p class="theory-desc">
                        Studi Falaq (2026) pada 80 naskah akademik Indonesia menemukan ZeroGPT memiliki <strong>false-positive rate 80%</strong> (32 dari 40 karya manusia ditandai AI). Studi Stanford (Liang et al.) juga mencatat 61.2% bias pada esai TOEFL.
                    </p>
                </div>

                <div class="theory-card" style="border-top: 4px solid #059669;" data-aos="fade-up" data-aos-delay="300" data-aos-duration="700">
                    <div class="card-icon-badge" style="background: #ecfdf5; color: #059669;">🔬</div>
                    <h3 class="theory-title">Deteksi Sitasi Fiktif</h3>
                    <p class="theory-desc">
                        AI generatif kerap memfabrikasi daftar pustaka halusinasi. AR Tilik mengaudit langsung ke pangkalan data <strong>Crossref &amp; OpenAlex</strong> untuk membuktikan apakah rujukan benar-benar terbit di dunia nyata—fitur yang tidak dimiliki detektor teks murni.
                    </p>
                </div>
            </div>

            <!-- Comparison Banner / Call to Action Box -->
            <div style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); border-radius: 20px; padding: clamp(24px, 4vw, 40px); color: #ffffff; display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 28px; align-items: center; box-shadow: 0 16px 40px -10px rgba(15, 23, 42, 0.2);" data-aos="fade-up" data-aos-duration="800">
                <div>
                    <div style="display: inline-flex; align-items: center; gap: 8px; font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; color: #fdba74; margin-bottom: 8px;">
                        <span>📖</span> Seri Whitepaper Riset Akademik 2026
                    </div>
                    <h3 style="font-size: clamp(19px, 2.2vw, 26px); font-weight: 800; line-height: 1.3; margin-bottom: 12px; color: #ffffff;">
                        “Dalam integritas akademik, alat terbaik bukan yang paling yakin—melainkan yang paling mudah dibantah, diperiksa, dan diperbaiki.”
                    </h3>
                    <p style="font-size: 14px; color: #94a3b8; line-height: 1.6; margin: 0;">
                        Kami memublikasikan hasil riset dan benchmark empiris terbuka: benchmark korpus akademik Bahasa Indonesia (termasuk 14 kasus mismatch), telaah rujukan nyata vs Turnitin, serta analisis pembuktian empiris vs deteksi AI ZeroGPT.
                    </p>
                </div>
                <div style="display: flex; flex-direction: column; gap: 10px; align-items: stretch; justify-content: center;">
                    <a href="<?= base_url('benchmark') ?>" class="btn btn-primary" style="background: #4338ca; border-color: #4338ca; padding: 13px 20px; font-size: 13.5px; font-weight: 700; text-align: center; border-radius: 10px; box-shadow: 0 6px 16px rgba(67, 56, 202, 0.35); text-decoration: none;">
                        <span>🔬 Benchmark Terbuka Naskah Indonesia</span>
                        <span style="margin-left: 6px;">&rarr;</span>
                    </a>
                    <a href="<?= base_url('riset/ar-tilik-vs-turnitin') ?>" class="btn btn-primary" style="background: #0f766e; border-color: #0f766e; padding: 13px 20px; font-size: 13.5px; font-weight: 700; text-align: center; border-radius: 10px; box-shadow: 0 6px 16px rgba(15, 118, 110, 0.35); text-decoration: none;">
                        <span>🏛️ Whitepaper: AR Tilik vs Turnitin</span>
                        <span style="margin-left: 6px;">&rarr;</span>
                    </a>
                    <a href="<?= base_url('riset/ar-tilik-vs-zerogpt') ?>" class="btn btn-primary" style="background: #ea580c; border-color: #ea580c; padding: 13px 20px; font-size: 13.5px; font-weight: 700; text-align: center; border-radius: 10px; box-shadow: 0 6px 16px rgba(234, 88, 12, 0.35); text-decoration: none;">
                        <span>📊 Whitepaper: AR Tilik vs ZeroGPT</span>
                        <span style="margin-left: 6px;">&rarr;</span>
                    </a>
                    <a href="<?= base_url('dashboard') ?>" class="btn" style="background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.18); color: #ffffff; padding: 11px 18px; font-size: 13px; font-weight: 600; text-align: center; border-radius: 10px; text-decoration: none;">
                        Uji Coba Audit Naskah Mandiri
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Section 2: Inovasi Mesin Pendeteksi Generasi Baru -->
    <section id="fitur-unggulan" class="section" style="background: #f8fafc; border-top: 1px solid var(--border);">
        <div class="container">
            <div class="section-header" data-aos="fade-up" data-aos-duration="700">
                <div class="section-tag">Integritas Ilmiah Mutakhir</div>
                <h2 class="section-title">Lebih Cerdas dari Matcher Teks Biasa</h2>
                <p class="section-desc">
                    Pelanggaran etika akademik modern telah berevolusi melampaui salin-tempel biasa. AR Tilik dipersenjatai 6 pilar komputasi cerdas untuk membongkar perakitan draf otomatis, inkonsistensi metodologi, alih-bahasa tersembunyi, manipulasi tipografi, sitasi fiktif, serta terhubung langsung ke pangkalan data riset nasional:
                </p>
            </div>

            <div class="grid-3">
                <!-- Fitur 1: Audit Forensik Cerdas -->
                <div class="theory-card" data-aos="fade-up" data-aos-delay="100" data-aos-duration="700">
                    <div class="card-icon-badge badge-indigo">✨</div>
                    <div style="font-size: 11px; font-weight: 800; color: #4f46e5; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px;">Forensik Semantik</div>
                    <h3 class="theory-title">Audit Forensik Cerdas &amp; Panduan Ujian Lisan</h3>
                    <p class="theory-desc">
                        Membongkar perakitan draf sintetis otomatis (<em>AI-stitching</em>), kontradiksi logika metodologi antar-bab, dan sitasi halusinasi masa depan. Menghasilkan panduan pertanyaan kritis lisan bagi dosen penguji untuk memverifikasi orisinalitas pemahaman mahasiswa dalam sidang.
                    </p>
                </div>

                <!-- Fitur 2: Sintesis Risiko Akademik Berbobot -->
                <div class="theory-card" data-aos="fade-up" data-aos-delay="150" data-aos-duration="700">
                    <div class="card-icon-badge badge-amber">⚖️</div>
                    <div style="font-size: 11px; font-weight: 800; color: #d97706; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px;">Sintesis Probabilistik</div>
                    <h3 class="theory-title">Sintesis Risiko Akademik Berbobot</h3>
                    <p class="theory-desc">
                        Penyintesis probabilistik terkalibrasi yang memadukan residu leksikal, estilometri variasi kalimat, dan konsistensi naskah secara non-linear. Membantu dosen membedakan formulasi baku ilmiah dari indikasi penjiplakan substantif tanpa vonis kaku.
                    </p>
                </div>

                <!-- Fitur 3: Cross-Lingual -->
                <div class="theory-card" data-aos="fade-up" data-aos-delay="200" data-aos-duration="700">
                    <div class="card-icon-badge badge-rose">🌐</div>
                    <div style="font-size: 11px; font-weight: 800; color: #e11d48; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px;">Penyelarasan Bilingual</div>
                    <h3 class="theory-title">Deteksi Plagiarisme Lintas Bahasa (EN ⇄ ID)</h3>
                    <p class="theory-desc">
                        Membongkar modus plagiarisme terjemahan langsung dari literatur asing (bahasa Inggris) ke bahasa Indonesia tanpa atribusi. Model representasi multilingual kami mendeteksi kesepadanan semantik dan susunan alur argumen sekalipun naskah telah dialihbahasakan.
                    </p>
                </div>

                <!-- Fitur 4: Anti-Cheat Evasion -->
                <div class="theory-card" data-aos="fade-up" data-aos-delay="250" data-aos-duration="700">
                    <div class="card-icon-badge badge-amber">🛡️</div>
                    <div style="font-size: 11px; font-weight: 800; color: #d97706; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px;">Perisai Manipulasi</div>
                    <h3 class="theory-title">Perisai Anti-Kecurangan Karakter &amp; Font</h3>
                    <p class="theory-desc">
                        Melacak trik manipulasi naskah yang kerap lolos dari sistem komersial: alfabet Cyrillic/Greek homoglyph yang menyerupai Latin, spasi nol-lebar (<em>zero-width space</em>), pemenggalan kata mikro, dan teks putih transparan yang sengaja disisipkan untuk mengecoh persentase mesin.
                    </p>
                </div>

                <!-- Fitur 5: Citation Auditor -->
                <div class="theory-card" data-aos="fade-up" data-aos-delay="300" data-aos-duration="700">
                    <div class="card-icon-badge badge-emerald">📑</div>
                    <div style="font-size: 11px; font-weight: 800; color: #059669; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px;">Audit Referensi</div>
                    <h3 class="theory-title">Auditor Konsistensi Sitasi &amp; Daftar Pustaka</h3>
                    <p class="theory-desc">
                        Menganalisis koherensi sitasi (APA/IEEE/Harvard) secara otomatis. Mendeteksi <strong>Ghost Citations</strong> (nama penulis dikutip di badan teks tapi raib dari referensi) dan <strong>Orphan References</strong> (tercantum di bibliografi namun tidak pernah dirujuk dalam tubuh artikel).
                    </p>
                </div>

                <!-- Fitur 6: National Harvester -->
                <div class="theory-card" data-aos="fade-up" data-aos-delay="350" data-aos-duration="700">
                    <div class="card-icon-badge badge-cyan">🦅</div>
                    <div style="font-size: 11px; font-weight: 800; color: #0284c7; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px;">Korpus Nasional</div>
                    <h3 class="theory-title">Pemanenan Neliti &amp; GARUDA Dikti (OAI-PMH)</h3>
                    <p class="theory-desc">
                        Terhubung ke protokol OAI-PMH 2.0 untuk pemanenan berkala artikel jurnal nasional terakreditasi SINTA 1–6 dari pangkalan data GARUDA Kemdiktisaintek dan portal riset Neliti. Memastikan basis data pembanding terus bertumbuh tanpa bergantung pada unggahan manual.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Section 3: Cara Penggunaan -->
    <section id="cara-kerja" class="section" style="background: #ffffff; border-top: 1px solid var(--border); border-bottom: 1px solid var(--border);">
        <div class="container">
            <div class="section-header" data-aos="fade-up" data-aos-duration="700">
                <div class="section-tag">Alur Kerja Praktis</div>
                <h2 class="section-title">Bagaimana Cara Menggunakan AR Tilik?</h2>
                <p class="section-desc">
                    Dirancang dengan antarmuka yang intuitif dan cepat, Anda dapat menyelesaikan pemeriksaan naskah karya ilmiah hanya dalam 4 langkah mudah:
                </p>
            </div>

            <div class="grid-2">
                <div class="step-card" data-aos="fade-up" data-aos-delay="100" data-aos-duration="700">
                    <div class="step-index">01</div>
                    <div class="card-icon-badge badge-indigo">📝</div>
                    <h3 class="step-title">Daftarkan Akun Valid</h3>
                    <p class="step-desc">
                        Buat akun gratis menggunakan alamat email Anda. Setiap akun terdaftar langsung memperoleh hak periksa <strong>3 submisi gratis tiap minggu</strong>.
                    </p>
                </div>

                <div class="step-card" data-aos="fade-up" data-aos-delay="200" data-aos-duration="700">
                    <div class="step-index">02</div>
                    <div class="card-icon-badge badge-cyan">📤</div>
                    <h3 class="step-title">Unggah Dokumen Karya Ilmiah</h3>
                    <p class="step-desc">
                        Kirimkan naskah dalam format PDF, DOCX, atau TXT (maks. 25 MB). Sistem secara otomatis mengekstrak teks, menormalisasi tanda baca, dan menghitung shingle sekuensial.
                    </p>
                </div>

                <div class="step-card" data-aos="fade-up" data-aos-delay="300" data-aos-duration="700">
                    <div class="step-index">03</div>
                    <div class="card-icon-badge badge-emerald">⚡</div>
                    <h3 class="step-title">Pemrosesan Asinkron Kilat</h3>
                    <p class="step-desc">
                        Worker background membandingkan naskah terhadap ratusan korpus institusi dan jurnal terbuka. Dalam rentang waktu kurang dari 1 detik, laporan lengkap terbit.
                    </p>
                </div>

                <div class="step-card" data-aos="fade-up" data-aos-delay="400" data-aos-duration="700">
                    <div class="step-index">04</div>
                    <div class="card-icon-badge badge-amber">🛡️</div>
                    <h3 class="step-title">Telaah Interaktif & Sertifikat QR</h3>
                    <p class="step-desc">
                        Buka perbandingan di <strong>Layar Ganda (Dual-Reader)</strong> dengan penanda warna, gunakan filter dinamis pengabaian kutipan, dan unduh sertifikat resmi ber-QR Code.
                    </p>
                </div>
            </div>

            <!-- Showcase: Dual-Reader Interface Mockup -->
            <div class="dual-reader-showcase" data-aos="zoom-in" data-aos-duration="850">
                <div class="dual-reader-header">
                    <span class="dual-reader-tag">Antarmuka Unggulan Penelaah</span>
                    <h3 class="dual-reader-title">Dual-Reader: Komparasi Berdampingan Interaktif</h3>
                    <p class="dual-reader-desc">
                        Tidak perlu lagi mencetak lembaran kertas atau berpindah tab secara melelahkan. Periksa kalimat sumber dan naskah mahasiswa secara berdampingan lengkap dengan tanda kutip, sitasi, dan verifikasi sertifikat digital.
                    </p>
                </div>

                <div class="browser-mockup">
                    <div class="browser-bar">
                        <div class="browser-dots">
                            <span class="browser-dot dot-red"></span>
                            <span class="browser-dot dot-yellow"></span>
                            <span class="browser-dot dot-green"></span>
                        </div>
                        <div class="browser-url">
                            <span>🔒</span> https://tilik.arsetontong.top/report/compare/SUB-704B1494
                        </div>
                    </div>
                    <picture>
                        <source srcset="<?= base_url('images/dual_reader_mockup.webp') ?>" type="image/webp">
                        <img src="<?= base_url('images/dual_reader_mockup.jpg') ?>" alt="Antarmuka Dual-Reader AR Tilik" loading="lazy" width="800" height="450">
                    </picture>
                </div>

                <div class="dual-reader-features">
                    <div class="feature-card-dark" data-aos="fade-up" data-aos-delay="100" data-aos-duration="600">
                        <div class="feature-dark-icon">↔️</div>
                        <h4 class="feature-dark-title">Sinkronisasi Gulir Otomatis</h4>
                        <p class="feature-dark-desc">
                            Mengklik pasase mirip di naskah mahasiswa seketika mengarahkan dan menyoroti pasase rujukan yang bersesuaian di korpus pembanding.
                        </p>
                    </div>

                    <div class="feature-card-dark" data-aos="fade-up" data-aos-delay="200" data-aos-duration="600">
                        <div class="feature-dark-icon">🔍</div>
                        <h4 class="feature-dark-title">Filter Sitasi & Pustaka Dinamis</h4>
                        <p class="feature-dark-desc">
                            Sakelar interaktif untuk mengecualikan kalimat dalam tanda petik, sitasi sah, maupun daftar referensi langsung menghitung ulang persentase di layar.
                        </p>
                    </div>

                    <div class="feature-card-dark" data-aos="fade-up" data-aos-delay="300" data-aos-duration="600">
                        <div class="feature-dark-icon">🛡️</div>
                        <h4 class="feature-dark-title">Verifikasi QR Code Kriptografis</h4>
                        <p class="feature-dark-desc">
                            Setiap naskah memiliki hash SHA-256 unik dan QR code yang dapat dipindai oleh dewan sidang skripsi untuk memeriksa keabsahan laporan.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Section 4: Ekosistem Integrasi OJS 3.x & REST API -->
    <section id="integrasi-ojs" class="section" style="background: #ffffff; border-bottom: 1px solid var(--border);">
        <div class="container">
            <div class="section-header" data-aos="fade-up" data-aos-duration="700">
                <div class="section-tag" style="color: #e11d48;">Ekosistem Jurnal Ilmiah SINTA</div>
                <h2 class="section-title">Integrasi Otomatis Open Journal Systems (OJS 3.x)</h2>
                <p class="section-desc">
                    Tingkatkan efisiensi dan reputasi jurnal ilmiah kampus Anda. Hubungkan OJS 3.x ke AR Tilik untuk verifikasi naskah masuk secara otomatis dan penerbitan Sertifikat Keabsahan digital ber-QR Code bagi Dewan Editor.
                </p>
            </div>

            <!-- OJS Banner Card -->
            <div class="ojs-banner-card" data-aos="zoom-in" data-aos-duration="800">
                <div class="ojs-grid">
                    <div>
                        <div style="display: inline-flex; align-items: center; gap: 8px; padding: 4px 12px; background: rgba(244, 63, 94, 0.2); border: 1px solid rgba(244, 63, 94, 0.4); border-radius: 20px; font-size: 11px; font-weight: 700; color: #fecdd3; text-transform: uppercase; margin-bottom: 16px;">
                            <span>📕</span> Buku Panduan Resmi &amp; Konektor PHP Siap Pakai
                        </div>
                        <h3 style="font-size: 26px; font-weight: 800; color: #ffffff; line-height: 1.3; margin-bottom: 14px;">
                            Buku Panduan Integrasi OJS 3.x (Format PDF Resolusi Tinggi - 8 Halaman)
                        </h3>
                        <p style="font-size: 14px; color: #cbd5e1; line-height: 1.6; margin-bottom: 24px;">
                            Disusun khusus untuk pengelola jurnal, IT kampus, dan Dewan Redaksi. Dilengkapi diagram alur kerja <em>end-to-end</em> (Penulis &rarr; OJS 3 &rarr; REST API &rarr; Dual-Lens &rarr; Sertifikat Digital), konsep komputasi Dual-Lens, Audit Forensik Cerdas AI, Panduan Telaah Redaksi, panduan instalasi, dan contoh kode PHP konektor.
                        </p>

                        <div style="display: flex; gap: 12px; flex-wrap: wrap;">
                            <a href="<?= base_url('integrations/ojs/Panduan_Integrasi_OJS_ARTilik.pdf') ?>" target="_blank" download="Panduan_Integrasi_OJS_ARTilik.pdf" class="btn" style="background: linear-gradient(135deg, #e11d48 0%, #be123c 100%); color: #ffffff; box-shadow: 0 4px 14px rgba(225, 29, 72, 0.4); border: 1px solid rgba(255,255,255,0.2);">
                                <span>📥</span> Unduh Panduan PDF (2.0 MB)
                            </a>
                            <a href="<?= base_url('docs/Brosur_Eksekutif_ARTilik.pdf') ?>" target="_blank" download="Brosur_Eksekutif_ARTilik.pdf" class="btn" style="background: rgba(99, 102, 241, 0.2); color: #c7d2fe; border: 1px solid rgba(129, 140, 248, 0.45);">
                                <span>📄</span> Unduh Brosur Eksekutif (2.7 MB)
                            </a>
                            <a href="<?= base_url('integrations/ojs/connector') ?>" download="ARTilikOJSConnector.php" class="btn" style="background: rgba(255, 255, 255, 0.1); color: #ffffff; border: 1px solid rgba(255,255,255,0.25);">
                                <span>💻</span> Unduh Konektor PHP
                            </a>
                            <a href="<?= base_url('integrations/ojs/runner') ?>" download="artilik_ojs_runner.php" class="btn" style="background: rgba(255, 255, 255, 0.08); color: #38bdf8; border: 1px solid rgba(56, 189, 248, 0.3);">
                                <span>⚡</span> Unduh Runner PHP
                            </a>
                            <a href="<?= base_url('integrations/ojs/README.md') ?>" target="_blank" class="btn" style="background: transparent; color: #94a3b8; border: 1px solid #334155;">
                                <span>📖</span> Dokumentasi Online
                            </a>
                        </div>
                    </div>

                    <!-- Code Box Preview -->
                    <div>
                        <div class="ojs-code-card">
                            <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #1e293b; padding-bottom: 8px; margin-bottom: 12px; color: #94a3b8; font-size: 11px;">
                                <span>PHP &bull; artilik_ojs_runner.php</span>
                                <span style="color: #10b981;">● OJS 3.x Ready</span>
                            </div>
                            <pre style="margin: 0; line-height: 1.5;"><span style="color: #f43f5e;">require_once</span> <span style="color: #fde047;">'ARTilikOJSConnector.php'</span>;

<span style="color: #64748b;">// 1. Inisialisasi dengan API Key Jurnal</span>
<span style="color: #38bdf8;">$tilik</span> = <span style="color: #f43f5e;">new</span> <span style="color: #a78bfa;">ARTilikOJSConnector</span>(
    <span style="color: #fde047;">'artilik_ojs_YOUR_API_KEY'</span>
);

<span style="color: #64748b;">// 2. Kirim naskah submisi penulis</span>
<span style="color: #38bdf8;">$res</span> = <span style="color: #38bdf8;">$tilik</span>-&gt;<span style="color: #60a5fa;">submitManuscript</span>(
    <span style="color: #fde047;">'/path/to/manuscript.pdf'</span>,
    <span style="color: #fde047;">'Judul Naskah Ilmiah'</span>,
    <span style="color: #fde047;">'Dr. Penulis Utama'</span>,
    <span style="color: #fde047;">'submission_4892'</span>
);

<span style="color: #64748b;">// 3. Ambil sertifikat digital ber-QR</span>
<span style="color: #f43f5e;">echo</span> <span style="color: #38bdf8;">$res</span>[<span style="color: #fde047;">'certificate_url'</span>];</pre>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Visual OJS Dashboard Interface Preview -->
            <div data-aos="fade-up" data-aos-duration="700" style="margin-top: 32px;">
                <div style="background: #0f172a; border: 1px solid rgba(244, 63, 94, 0.25); border-radius: 16px; padding: 14px; box-shadow: 0 16px 36px -8px rgba(15, 23, 42, 0.25);">
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px; padding: 0 6px;">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <span style="display: inline-block; width: 10px; height: 10px; border-radius: 50%; background: #ef4444;"></span>
                            <span style="display: inline-block; width: 10px; height: 10px; border-radius: 50%; background: #f59e0b;"></span>
                            <span style="display: inline-block; width: 10px; height: 10px; border-radius: 50%; background: #10b981;"></span>
                            <span style="font-size: 12px; color: #94a3b8; font-family: 'JetBrains Mono', monospace; margin-left: 8px;">OJS 3.x Editorial Review Stage &bull; AR Tilik Live Integration</span>
                        </div>
                        <span style="font-size: 11px; color: #f43f5e; font-weight: 700;">● Terhubung REST API v1</span>
                    </div>
                    <img src="<?= base_url('images/ojs_integration_admin.png') ?>" alt="Integrasi AR Tilik pada Alur Kerja Editor OJS 3.x" width="1024" height="479" style="width: 100%; height: auto; border-radius: 10px; display: block;" loading="lazy">
                </div>
            </div>

            <!-- 3 Alur Kerja Integrasi Jurnal -->
            <div class="grid-3" style="margin-top: 40px;">
                <div class="step-card" data-aos="fade-up" data-aos-delay="100" data-aos-duration="700">
                    <div class="step-index">A</div>
                    <div class="card-icon-badge badge-rose">🔌</div>
                    <h3 class="step-title">1 Berkas Tanpa Konfigurasi Rumit</h3>
                    <p class="step-desc">
                        Cukup letakkan berkas pustaka <code>ARTilikOJSConnector.php</code> pada folder instalasi OJS Anda. Tidak memerlukan instalasi ekstensi kernel peladen atau dependensi eksternal yang rumit.
                    </p>
                </div>

                <div class="step-card" data-aos="fade-up" data-aos-delay="200" data-aos-duration="700">
                    <div class="step-index">B</div>
                    <div class="card-icon-badge badge-indigo">⚡</div>
                    <h3 class="step-title">Pemeriksaan Submisi Otomatis</h3>
                    <p class="step-desc">
                        Dipicu saat penulis mengunggah naskah (<em>Author Submission</em>) atau saat naskah dialihkan ke tahap penelaahan mitra bestari (<em>Peer Review Stage</em>) secara otomatis di latar belakang.
                    </p>
                </div>

                <div class="step-card" data-aos="fade-up" data-aos-delay="300" data-aos-duration="700">
                    <div class="step-index">C</div>
                    <div class="card-icon-badge badge-emerald">📜</div>
                    <h3 class="step-title">Sertifikat Digital Ber-QR Code</h3>
                    <p class="step-desc">
                        Menerbitkan Sertifikat Keabsahan Orisinalitas ber-QR Code kriptografis (SHA-256) yang dapat dilampirkan langsung pada surat keputusan dewan redaksi (<em>Editorial Decision</em>).
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Section 5: Solusi Tepat Guna Berdasarkan Kebutuhan (Persona) -->
    <section id="solusi" class="section" style="background: #f8fafc; border-bottom: 1px solid var(--border);">
        <div class="container">
            <div class="section-header" data-aos="fade-up" data-aos-duration="700">
                <div class="section-tag">Segmentasi Pemangku Kepentingan</div>
                <h2 class="section-title">Solusi Tepat Guna untuk Seluruh Sivitas Akademika</h2>
                <p class="section-desc">
                    AR Tilik dirancang untuk memberikan nilai nyata bagi mahasiswa, pengelola jurnal ilmiah, hingga pimpinan universitas:
                </p>
            </div>

            <div class="grid-3">
                <!-- Card 1: Mahasiswa & Peneliti -->
                <div class="persona-card" data-aos="fade-up" data-aos-delay="100" data-aos-duration="700">
                    <div>
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                            <div class="card-icon-badge badge-indigo" style="margin-bottom: 0;">🎓</div>
                            <span style="font-size: 11px; font-weight: 800; background: rgba(99, 102, 241, 0.1); color: var(--primary); padding: 4px 10px; border-radius: 20px;">Individu</span>
                        </div>
                        <h3 class="theory-title">Mahasiswa &amp; Peneliti Mandiri</h3>
                        <p class="theory-desc">
                            Cek mandiri sebelum maju sidang skripsi/tesis tanpa rasa was-was naskah tersimpan ke repositori publik.
                        </p>

                        <ul class="persona-list">
                            <li><span class="check-icon">✓</span> <span><strong>Bebas Risiko Self-Plagiarism:</strong> Naskah pra-sidang Anda tidak disimpan ke korpus publik.</span></li>
                            <li><span class="check-icon">✓</span> <span><strong>Audit Sitasi Otomatis:</strong> Pastikan tidak ada kutipan fiktif sebelum naskah dicetak.</span></li>
                            <li><span class="check-icon">✓</span> <span><strong>Lensa Ganda (Dual-Reader):</strong> Telusuri kalimat yang perlu diparafrasa berdampingan.</span></li>
                            <li><span class="check-icon">✓</span> <span><strong>Tarif Ringan Mahasiswa:</strong> Gratis 3x/minggu atau top-up fleksibel Rp 3.500/dokumen.</span></li>
                        </ul>
                    </div>

                    <button type="button" onclick="openAuthModal('register')" class="btn btn-outline" style="width: 100%; justify-content: center; font-weight: 700;">
                        Daftar Akun Mahasiswa
                    </button>
                </div>

                <!-- Card 2: Pengelola Jurnal OJS -->
                <div class="persona-card" style="border-color: rgba(244, 63, 94, 0.4); box-shadow: 0 10px 30px -10px rgba(244, 63, 94, 0.15);" data-aos="fade-up" data-aos-delay="200" data-aos-duration="700">
                    <div>
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                            <div class="card-icon-badge badge-rose" style="margin-bottom: 0;">🏛️</div>
                            <span style="font-size: 11px; font-weight: 800; background: rgba(244, 63, 94, 0.1); color: #e11d48; padding: 4px 10px; border-radius: 20px;">Jurnal Ilmiah</span>
                        </div>
                        <h3 class="theory-title">Pengelola Jurnal (OJS 3.x)</h3>
                        <p class="theory-desc">
                            Otomatisasi telaah kemiripan artikel submisi untuk menjaga reputasi dan kepatuhan akreditasi ARJUNA &amp; SINTA.
                        </p>

                        <ul class="persona-list">
                            <li><span class="check-icon" style="color: #e11d48;">✓</span> <span><strong>Konektor OJS 3.x Plug &amp; Play:</strong> Cukup pasang 1 berkas pustaka PHP.</span></li>
                            <li><span class="check-icon" style="color: #e11d48;">✓</span> <span><strong>Deteksi Lintas Bahasa:</strong> Membongkar artikel terjemahan asing tanpa sitasi.</span></li>
                            <li><span class="check-icon" style="color: #e11d48;">✓</span> <span><strong>Sertifikat Digital Ber-QR Code:</strong> Bukti verifikasi resmi untuk dewan editor.</span></li>
                            <li><span class="check-icon" style="color: #e11d48;">✓</span> <span><strong>Manajemen Kuota API Jurnal:</strong> Kontrol batas pemakaian per bulan secara presisi.</span></li>
                        </ul>
                    </div>

                    <a href="#integrasi-ojs" class="btn btn-primary" style="width: 100%; justify-content: center; font-weight: 700; background: linear-gradient(135deg, #e11d48 0%, #be123c 100%);">
                        Pelajari Integrasi OJS
                    </a>
                </div>

                <!-- Card 3: Pimpinan Perguruan Tinggi -->
                <div class="persona-card" data-aos="fade-up" data-aos-delay="300" data-aos-duration="700">
                    <div>
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                            <div class="card-icon-badge badge-emerald" style="margin-bottom: 0;">🏢</div>
                            <span style="font-size: 11px; font-weight: 800; background: rgba(16, 185, 129, 0.1); color: #059669; padding: 4px 10px; border-radius: 20px;">Institusi Kampus</span>
                        </div>
                        <h3 class="theory-title">Perguruan Tinggi &amp; Perpustakaan</h3>
                        <p class="theory-desc">
                            Kedaulatan data naskah akademik sivitas kampus dengan opsi server mandiri (<em>On-Premise</em>) dan dasbor analitik.
                        </p>

                        <ul class="persona-list">
                            <li><span class="check-icon">✓</span> <span><strong>Kedaulatan Data Penuh:</strong> Naskah sivitas tidak dijual/diserahkan ke pihak luar.</span></li>
                            <li><span class="check-icon">✓</span> <span><strong>Opsi On-Premise / Private Cloud:</strong> Dipasang di pusat data kampus sendiri.</span></li>
                            <li><span class="check-icon">✓</span> <span><strong>Sinkronisasi OAI-PMH:</strong> Hubungkan repositori EPrints / DSpace institusi.</span></li>
                            <li><span class="check-icon">✓</span> <span><strong>Dasbor Integritas Kampus:</strong> Analitik tren kemiripan dan risiko antar fakultas.</span></li>
                        </ul>
                    </div>

                    <div style="display: flex; flex-direction: column; gap: 8px;">
                        <a href="#langganan" class="btn btn-outline" style="width: 100%; justify-content: center; font-weight: 700;">
                            Konsultasi Paket Kampus
                        </a>
                        <a href="<?= base_url('docs/Brosur_Eksekutif_ARTilik.pdf') ?>" target="_blank" download="Brosur_Eksekutif_ARTilik.pdf" class="btn btn-sm" style="width: 100%; justify-content: center; font-weight: 700; background: rgba(16, 185, 129, 0.1); color: #059669; border: 1px solid rgba(16, 185, 129, 0.3);">
                            <span>📄</span> Unduh Brosur Eksekutif (PDF)
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Section 6: Gotong Royong Korpus (Crowdsourcing Incentive) -->
    <section id="kuota-korpus" class="section">
        <div class="container">
            <div class="incentive-banner" data-aos="fade-up" data-aos-duration="800">
                <div class="incentive-grid">
                    <div>
                        <span style="background: rgba(56, 189, 248, 0.2); color: #38bdf8; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 700; text-transform: uppercase;">Inisiatif Gotong Royong Akademik</span>
                        <h2 class="incentive-title" style="margin-top: 14px;">Perlu Lebih dari 3 Submisi Tiap Minggu?</h2>
                        <p class="incentive-desc">
                            AR Tilik percaya bahwa repositori institusi yang kuat dibangun bersama. Jika kuota 3 submisi gratis mingguan Anda telah terpakai, Anda dapat menambah kuota secara cuma-cuma dengan <strong>menyumbangkan 3 berkas korpus karya ilmiah valid</strong> (jurnal/skripsi referensi non-duplikat).
                        </p>
                        <div style="display: flex; gap: 12px; flex-wrap: wrap;">
                            <button type="button" onclick="openAuthModal('register')" class="btn" style="background: #ffffff; color: var(--primary);">
                                Daftar & Mulai Kontribusi
                            </button>
                            <a href="<?= base_url('verify') ?>" class="btn" style="background: rgba(255, 255, 255, 0.15); color: #ffffff; border: 1px solid rgba(255,255,255,0.3);">
                                Portal Verifikasi Publik
                            </a>
                        </div>
                    </div>

                    <div class="formula-box">
                        <div style="font-size: 13px; color: #94a3b8; margin-bottom: 6px;">FORMULA INSENTIF KORPUS:</div>
                        <div class="formula-text">3 Korpus Valid = +1 Submisi</div>
                        <p style="font-size: 13px; color: #cbd5e1; line-height: 1.5; margin-top: 10px;">
                            Setiap naskah yang Anda sumbangkan melewati verifikasi anti-duplikasi otomatis dan langsung memperkaya khazanah pembanding perguruan tinggi kita.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <?php if (!empty($approvedTestimonials)): ?>
    <!-- Section: Testimoni & Bukti Nyata Akademisi (Fase 1 Social Proof) -->
    <section id="testimoni" class="section" style="background: #f8fafc; border-top: 1px solid var(--border); position: relative;">
        <div class="container">
            <div class="section-header" data-aos="fade-up" data-aos-duration="700">
                <div class="section-tag">Bukti Nyata &amp; Kredibilitas Akademik</div>
                <h2 class="section-title">Dipercaya Oleh Mahasiswa, Dosen, &amp; Peneliti</h2>
                <p class="section-desc">
                    Ulasan otentik dari civitas akademika yang telah menggunakan analisis kemiripan dan audit forensik AR Tilik untuk kelulusan sidang skripsi, tesis, dan publikasi jurnal bereputasi.
                </p>
            </div>

            <!-- Trust Bar / KPI -->
            <div class="testi-trust-bar-wrap" data-aos="fade-up" data-aos-delay="100">
                <div class="testi-trust-bar">
                    <div class="testi-trust-stars">★★★★★</div>
                    <div class="testi-trust-score">
                        <?= number_format($testimonialStats['avg_rating'] ?? 5.0, 1) ?> / 5.0
                    </div>
                    <div class="testi-trust-desc">
                        (Skor Kepuasan dari <?= (int)($testimonialStats['total_reviews'] ?? count($approvedTestimonials)) ?>+ Ulasan Terverifikasi)
                    </div>
                </div>
            </div>

            <!-- Filter Kategori Testimoni Interaktif -->
            <div class="testi-filter-wrapper" data-aos="fade-up" data-aos-delay="150">
                <div class="testi-filter-container">
                    <button type="button" onclick="filterTestimonials('all', this)" class="testi-filter-btn active-testi-filter">
                        Semua Civitas (<?= count($approvedTestimonials) ?>)
                    </button>
                    <button type="button" onclick="filterTestimonials('mahasiswa', this)" class="testi-filter-btn">
                        🎓 Mahasiswa (Skripsi &amp; Tesis)
                    </button>
                    <button type="button" onclick="filterTestimonials('dosen', this)" class="testi-filter-btn">
                        👨‍🏫 Dosen &amp; Peneliti
                    </button>
                    <button type="button" onclick="filterTestimonials('jurnal', this)" class="testi-filter-btn">
                        📑 Pengelola Jurnal (OJS)
                    </button>
                </div>
            </div>

            <!-- Testimonials Carousel Container -->
            <div class="testi-carousel-wrapper" data-aos="fade-up" data-aos-delay="200">
                <!-- Floating Nav Arrows (Desktop & Tablet) -->
                <button type="button" id="testiPrevBtnDesktop" class="testi-nav-arrow testi-prev-arrow" onclick="scrollTesti(-1)" aria-label="Testimoni Sebelumnya" title="Sebelumnya">
                    <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/></svg>
                </button>
                <button type="button" id="testiNextBtnDesktop" class="testi-nav-arrow testi-next-arrow" onclick="scrollTesti(1)" aria-label="Testimoni Berikutnya" title="Berikutnya">
                    <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
                </button>

                <!-- Scrollable Carousel Track -->
                <div id="testimonialsTrack" class="testi-carousel-track">
                    <?php foreach ($approvedTestimonials as $tItem): 
                        $roleLower = strtolower($tItem['author_role'] ?? '');
                        if (str_contains($roleLower, 'jurnal') || str_contains($roleLower, 'editor') || str_contains($roleLower, 'reviewer') || str_contains($roleLower, 'ojs')) {
                            $roleCategory = 'jurnal';
                        } elseif (str_contains($roleLower, 'dosen') || str_contains($roleLower, 'peneliti') || str_contains($roleLower, 'pengajar')) {
                            $roleCategory = 'dosen';
                        } else {
                            $roleCategory = 'mahasiswa';
                        }
                    ?>
                        <div class="testi-card" data-category="<?= $roleCategory ?>" itemscope itemtype="https://schema.org/Review">
                            <!-- Hidden Schema Reference to Product -->
                            <div itemprop="itemReviewed" itemscope itemtype="https://schema.org/WebApplication" style="display: none;">
                                <meta itemprop="name" content="AR Tilik">
                                <meta itemprop="url" content="<?= base_url() ?>">
                            </div>

                            <?php if (!empty($tItem['is_featured'])): ?>
                                <div class="testi-featured-badge">
                                    ⭐ Unggulan
                                </div>
                            <?php endif; ?>
                            
                            <div style="flex-grow: 1;">
                                <!-- Rating Stars (Schema.org Rating) -->
                                <div itemprop="reviewRating" itemscope itemtype="https://schema.org/Rating" style="color: #f59e0b; font-size: 15px; margin-bottom: 14px;">
                                    <meta itemprop="ratingValue" content="<?= (int)$tItem['rating'] ?>">
                                    <meta itemprop="bestRating" content="5">
                                    <meta itemprop="worstRating" content="1">
                                    <?php for ($st = 1; $st <= 5; $st++): ?>
                                        <span><?= $st <= (int)$tItem['rating'] ? '★' : '☆' ?></span>
                                    <?php endfor; ?>
                                </div>

                                <!-- Testimonial Quote (Schema.org reviewBody) -->
                                <p itemprop="reviewBody" class="testi-card-quote">
                                    "<?= esc($tItem['content']) ?>"
                                </p>
                            </div>

                            <!-- Author Meta (Schema.org Person) -->
                            <div class="testi-author-row" itemprop="author" itemscope itemtype="https://schema.org/Person">
                                <div class="testi-author-left">
                                    <div class="testi-avatar">
                                        <?= mb_substr($tItem['display_name'] ?? 'A', 0, 1) ?>
                                    </div>
                                    <div class="testi-author-meta">
                                        <div class="testi-author-name-row">
                                            <span class="testi-author-name" itemprop="name">
                                                <?= esc($tItem['display_name']) ?>
                                            </span>
                                            <span class="testi-verified-badge">
                                                ✓ Terverifikasi
                                            </span>
                                        </div>
                                        <div class="testi-author-role">
                                            <?= esc($tItem['author_role'] ?? 'Mahasiswa') ?>
                                        </div>
                                        <?php if (!empty($tItem['display_institution'])): ?>
                                            <div class="testi-author-inst">
                                                <?= esc($tItem['display_institution']) ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Empty State jika kategori tidak memiliki ulasan -->
                <div id="testiEmptyState" style="display: none; background: #ffffff; border: 1px dashed var(--border); border-radius: 16px; padding: 36px 20px; text-align: center; color: var(--text-muted); font-size: 13px;">
                    <span>🔍</span> Belum ada ulasan untuk kategori ini.
                </div>

                <!-- Controls Bar (Dots & Mobile Buttons) -->
                <div id="testiControlsBar" class="testi-controls-bar">
                    <div class="testi-controls-inner">
                        <button type="button" id="testiPrevBtnMobile" class="testi-mobile-nav-btn" onclick="scrollTesti(-1)" aria-label="Sebelumnya" title="Sebelumnya">
                            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/></svg>
                        </button>
                        <div id="testiDots" class="testi-dots-container"></div>
                        <button type="button" id="testiNextBtnMobile" class="testi-mobile-nav-btn" onclick="scrollTesti(1)" aria-label="Berikutnya" title="Berikutnya">
                            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
                        </button>
                    </div>
                    <div id="testiCounter" class="testi-counter-badge"></div>
                </div>
            </div>

            <!-- Callout CTA Tulis Ulasan -->
            <div class="testi-cta-box" data-aos="fade-up">
                <div style="display: flex; align-items: center; gap: 14px;">
                    <span style="font-size: 26px;">💬</span>
                    <div>
                        <div style="font-weight: 800; font-size: 13.5px; color: var(--text-dark);">Telah Menggunakan AR Tilik untuk Naskah Anda?</div>
                        <div style="font-size: 12px; color: var(--text-muted);">Bagikan masukan dan rating kepuasan di dashboard naskah untuk mendukung integritas akademik Indonesia.</div>
                    </div>
                </div>
                <a href="<?= base_url('dashboard') ?>" class="btn" style="background: #e0e7ff; color: #3730a3; font-weight: 800; font-size: 12px; padding: 9px 18px; border-radius: 10px; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; transition: all 0.2s ease;">
                    <span>✍️</span> <span>Beri Ulasan di Dashboard</span> &rarr;
                </a>
            </div>
        </div>
    </section>

    <!-- JavaScript Filter & Carousel Testimoni Interaktif -->
    <script>
        let currentTestiFilter = 'all';

        function getCardsPerPage() {
            const w = window.innerWidth;
            if (w < 768) return 1;
            if (w < 1024) return 2;
            return 3;
        }

        function getVisibleCards() {
            return Array.from(document.querySelectorAll('.testi-card')).filter(c => c.style.display !== 'none');
        }

        function updateTestiCarousel() {
            const track = document.getElementById('testimonialsTrack');
            if (!track) return;

            const visibleCards = getVisibleCards();
            const count = visibleCards.length;
            const emptyState = document.getElementById('testiEmptyState');
            const prevDesktop = document.getElementById('testiPrevBtnDesktop');
            const nextDesktop = document.getElementById('testiNextBtnDesktop');
            const controlsBar = document.getElementById('testiControlsBar');
            const dotsContainer = document.getElementById('testiDots');
            const counterEl = document.getElementById('testiCounter');

            if (count === 0) {
                if (emptyState) emptyState.style.display = 'block';
                track.style.display = 'none';
                if (prevDesktop) prevDesktop.style.display = 'none';
                if (nextDesktop) nextDesktop.style.display = 'none';
                if (controlsBar) controlsBar.style.display = 'none';
                return;
            }

            if (emptyState) emptyState.style.display = 'none';
            track.style.display = 'flex';

            const cardsPerPage = getCardsPerPage();
            const needsCarousel = count > cardsPerPage;

            if (!needsCarousel) {
                track.classList.add('no-carousel');
                if (prevDesktop) prevDesktop.style.display = 'none';
                if (nextDesktop) nextDesktop.style.display = 'none';
                if (controlsBar) controlsBar.style.display = 'none';
                track.scrollLeft = 0;
                return;
            }

            // Mode carousel aktif
            track.classList.remove('no-carousel');
            if (prevDesktop) prevDesktop.style.display = 'flex';
            if (nextDesktop) nextDesktop.style.display = 'flex';
            if (controlsBar) controlsBar.style.display = 'flex';

            const maxStep = Math.max(0, count - cardsPerPage);

            // Render Pagination Dots
            if (dotsContainer) {
                dotsContainer.innerHTML = '';
                for (let i = 0; i <= maxStep; i++) {
                    const dot = document.createElement('button');
                    dot.type = 'button';
                    dot.className = 'testi-dot' + (i === 0 ? ' active' : '');
                    dot.setAttribute('aria-label', `Testimoni slide ${i + 1}`);
                    dot.onclick = () => scrollTestiToStep(i);
                    dotsContainer.appendChild(dot);
                }
            }

            updateActiveDotAndArrows();
        }

        function scrollTestiToStep(stepIndex) {
            const track = document.getElementById('testimonialsTrack');
            const visibleCards = getVisibleCards();
            if (!track || !visibleCards[stepIndex]) return;

            const targetOffset = visibleCards[stepIndex].offsetLeft - track.offsetLeft;
            track.scrollTo({ left: targetOffset, behavior: 'smooth' });
        }

        function scrollTesti(direction) {
            const track = document.getElementById('testimonialsTrack');
            const visibleCards = getVisibleCards();
            if (!track || visibleCards.length === 0) return;

            const cardsPerPage = getCardsPerPage();
            const maxStep = Math.max(0, visibleCards.length - cardsPerPage);
            if (maxStep === 0) return;

            let currentStep = 0;
            const currentScroll = track.scrollLeft;
            let minDiff = Infinity;

            visibleCards.slice(0, maxStep + 1).forEach((card, idx) => {
                const offset = card.offsetLeft - track.offsetLeft;
                const diff = Math.abs(currentScroll - offset);
                if (diff < minDiff) {
                    minDiff = diff;
                    currentStep = idx;
                }
            });

            const nextStep = Math.min(maxStep, Math.max(0, currentStep + direction));
            scrollTestiToStep(nextStep);
        }

        function updateActiveDotAndArrows() {
            const track = document.getElementById('testimonialsTrack');
            const visibleCards = getVisibleCards();
            if (!track || visibleCards.length === 0) return;

            const cardsPerPage = getCardsPerPage();
            const maxStep = Math.max(0, visibleCards.length - cardsPerPage);

            let currentStep = 0;
            const currentScroll = track.scrollLeft;
            let minDiff = Infinity;

            visibleCards.slice(0, maxStep + 1).forEach((card, idx) => {
                const offset = card.offsetLeft - track.offsetLeft;
                const diff = Math.abs(currentScroll - offset);
                if (diff < minDiff) {
                    minDiff = diff;
                    currentStep = idx;
                }
            });

            // Dots highlight
            const dots = document.querySelectorAll('.testi-dot');
            dots.forEach((dot, idx) => {
                if (idx === currentStep) {
                    dot.classList.add('active');
                } else {
                    dot.classList.remove('active');
                }
            });

            // Counter (Mobile)
            const counterEl = document.getElementById('testiCounter');
            if (counterEl) {
                counterEl.textContent = `${currentStep + 1} dari ${maxStep + 1}`;
            }

            // Desktop Arrows disabled state
            const prevDesktop = document.getElementById('testiPrevBtnDesktop');
            const nextDesktop = document.getElementById('testiNextBtnDesktop');
            if (prevDesktop) prevDesktop.disabled = (currentStep <= 0);
            if (nextDesktop) nextDesktop.disabled = (currentStep >= maxStep);

            // Mobile Nav Buttons disabled state
            const prevMobile = document.getElementById('testiPrevBtnMobile');
            const nextMobile = document.getElementById('testiNextBtnMobile');
            if (prevMobile) prevMobile.disabled = (currentStep <= 0);
            if (nextMobile) nextMobile.disabled = (currentStep >= maxStep);
        }

        function filterTestimonials(cat, btn) {
            currentTestiFilter = cat;
            document.querySelectorAll('.testi-filter-btn').forEach(b => {
                b.classList.remove('active-testi-filter');
                b.style.background = '';
                b.style.color = '';
                b.style.borderColor = '';
            });
            if (btn) {
                btn.classList.add('active-testi-filter');
                btn.style.background = '';
                btn.style.color = '';
                btn.style.borderColor = '';

                if (typeof btn.scrollIntoView === 'function') {
                    btn.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
                }
            }

            const cards = document.querySelectorAll('.testi-card');
            cards.forEach(card => {
                if (cat === 'all' || card.dataset.category === cat) {
                    card.style.display = 'flex';
                } else {
                    card.style.display = 'none';
                }
            });

            const track = document.getElementById('testimonialsTrack');
            if (track) {
                track.scrollLeft = 0;
            }

            updateTestiCarousel();
        }

        document.addEventListener('DOMContentLoaded', () => {
            const track = document.getElementById('testimonialsTrack');
            if (track) {
                let scrollAnim = null;
                track.addEventListener('scroll', () => {
                    if (scrollAnim) cancelAnimationFrame(scrollAnim);
                    scrollAnim = requestAnimationFrame(updateActiveDotAndArrows);
                }, { passive: true });
            }

            window.addEventListener('resize', () => {
                updateTestiCarousel();
            });

            updateTestiCarousel();
        });
    </script>
    <?php endif; ?>

    <!-- Section 4: Paket Berlangganan & Top-Up Kuota (Midtrans Aligned) -->
    <section id="langganan" class="section" style="background: #ffffff; border-top: 1px solid var(--border); position: relative;">
        <!-- Anchor link alias for #pricing -->
        <span id="pricing" style="position: absolute; top: -70px; left: 0;"></span>
        <div class="container">
            <div class="section-header" data-aos="fade-up" data-aos-duration="700">
                <div class="section-tag">Tarif Transparan & Pilihan Akses</div>
                <h2 class="section-title">Pilihan Paket & Top-Up Kuota AR Tilik</h2>
                <p class="section-desc">
                    Mulai dari penggunaan mandiri gratis, top-up fleksibel tanpa batas waktu, paket bulanan pejuang skripsi, hingga integrasi institusi kampus:
                </p>
            </div>

            <div class="grid-4">
                <!-- Plan 1: Free Academic -->
                <div class="pricing-card" data-aos="fade-up" data-aos-delay="100" data-aos-duration="700">
                    <div>
                        <span class="plan-pill plan-pill-free">Tier Gratis</span>
                        <div class="plan-name">Civitas Akademika</div>
                        <div class="plan-price">
                            <span class="amount">Gratis</span>
                            <span class="plan-period">/ selamanya</span>
                        </div>
                        <p class="plan-desc">
                            Cocok untuk mahasiswa, dosen, dan peneliti mandiri yang memeriksa tugas atau draf artikel secara berkala.
                        </p>

                        <ul class="plan-features">
                            <li><strong>3 submisi gratis</strong> per minggu (reset tiap Senin)</li>
                            <li><strong>Bonus gotong royong</strong> (+1 cek tiap 3 korpus valid)</li>
                            <li>Akses Dual-Reader (layar komparasi berdampingan)</li>
                            <li>Filter cerdas sitasi pustaka (eksklusi referensi)</li>
                            <li>Sertifikat resmi keaslian naskah ber-QR Code</li>
                            <li>Deteksi sitasi format APA, IEEE, dan Harvard</li>
                        </ul>
                    </div>

                    <?php if ($currentUser) : ?>
                        <a href="<?= base_url('dashboard') ?>" class="btn btn-outline">
                            Gunakan Kuota Saya
                        </a>
                    <?php else : ?>
                        <button type="button" onclick="openAuthModal('register')" class="btn btn-outline">
                            Daftar Akun Gratis
                        </button>
                    <?php endif; ?>
                </div>

                <!-- Plan 2: Top-Up Fleksibel (Pay-As-You-Go) -->
                <div class="pricing-card" data-aos="fade-up" data-aos-delay="150" data-aos-duration="700">
                    <div>
                        <span class="plan-pill plan-pill-payg">Top-Up Fleksibel</span>
                        <div class="plan-name">Top-Up Kuota</div>
                        <div class="plan-price">
                            <span class="currency">Rp</span><span class="amount">10.000</span>
                            <span class="plan-period">/ 3 cek</span>
                        </div>
                        <p class="plan-desc">
                            Beli kuota saat dibutuhkan tanpa komitmen bulanan. Tersedia opsi hemat <strong>Rp 25.000 / 10 cek</strong>.
                        </p>

                        <ul class="plan-features">
                            <li><strong>Aktif selamanya</strong> (kuota tanpa masa kedaluwarsa)</li>
                            <li>Pilihan paket isi ulang 3 cek atau 10 cek</li>
                            <li>Prioritas pemrosesan antrean dokumen cepat</li>
                            <li><strong>Audit Forensik Cerdas</strong> & instrumen ujian sidang aktif</li>
                            <li>Semua fitur deteksi kecerdasan buatan & stylometri aktif</li>
                            <li>Ekspor PDF laporan resmi lengkap ber-QR</li>
                            <li>Aktivasi instan via QRIS & Virtual Account</li>
                        </ul>
                    </div>

                    <?php if ($currentUser) : ?>
                        <a href="<?= base_url('dashboard') ?>" class="btn btn-primary">
                            ⚡ Top-Up di Dashboard
                        </a>
                    <?php else : ?>
                        <button type="button" onclick="openAuthModal('login')" class="btn btn-primary">
                            Masuk & Beli Kuota
                        </button>
                    <?php endif; ?>
                </div>

                <!-- Plan 3: Paket Pejuang Skripsi (Featured) -->
                <div class="pricing-card popular" data-aos="fade-up" data-aos-delay="200" data-aos-duration="700">
                    <div class="pricing-badge popular">⭐ Paling Diminati</div>
                    <div>
                        <span class="plan-pill plan-pill-popular">Paket Skripsi</span>
                        <div class="plan-name">Pejuang Skripsi</div>
                        <div class="plan-price">
                            <span class="currency">Rp</span><span class="amount">25.000</span>
                            <span class="plan-period">/ bulan</span>
                        </div>
                        <p class="plan-desc">
                            Hanya <strong>Rp 1.250 / dokumen</strong>. Solusi hemat untuk persiapan sidang & revisi bab skripsi/tesis.
                        </p>

                        <ul class="plan-features">
                            <li><strong>20 submisi dokumen</strong> selama masa aktif</li>
                            <li>Prioritas antrean pemrosesan cepat</li>
                            <li><strong>Audit Forensik Cerdas</strong> & 4 panduan pertanyaan sidang dosen</li>
                            <li>Deteksi fabrikasi sitasi gaib & inkonsistensi metodologi</li>
                            <li>Ekspor PDF laporan resmi lengkap tanpa batas</li>
                            <li>Verifikasi keaslian naskah ber-QR Code</li>
                            <li>Masa aktif paket 30 hari penuh</li>
                            <li>Aktivasi instan via QRIS & Virtual Account</li>
                        </ul>
                    </div>

                    <?php if ($currentUser) : ?>
                        <a href="<?= base_url('dashboard') ?>" class="btn" style="background: #f59e0b; border: 1px solid #d97706; color: #ffffff; box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3);">
                            Langganan di Dashboard →
                        </a>
                    <?php else : ?>
                        <button type="button" onclick="openAuthModal('register')" class="btn" style="background: #f59e0b; border: 1px solid #d97706; color: #ffffff; box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3);">
                            Daftar & Langganan →
                        </button>
                    <?php endif; ?>
                </div>

                <!-- Plan 4: Dosen & Peneliti Pro -->
                <div class="pricing-card" data-aos="fade-up" data-aos-delay="250" data-aos-duration="700">
                    <div>
                        <span class="plan-pill plan-pill-pro">Kapasitas Besar</span>
                        <div class="plan-name">Dosen & Peneliti Pro</div>
                        <div class="plan-price">
                            <span class="currency">Rp</span><span class="amount">75.000</span>
                            <span class="plan-period">/ bulan</span>
                        </div>
                        <p class="plan-desc">
                            Hanya <strong>Rp 1.000 / dokumen</strong>. Dirancang untuk dosen pembimbing dan peneliti aktif.
                        </p>

                        <ul class="plan-features">
                            <li><strong>75 submisi dokumen</strong> selama masa aktif</li>
                            <li>Prioritas antrean tertinggi sistem</li>
                            <li><strong>Audit Forensik Cerdas</strong> mendalam & instrumen dewan penguji</li>
                            <li>Sintesis risiko akademik berbobot & integritas sitasi</li>
                            <li>Dukungan berkas besar hingga <strong>30 MB</strong></li>
                            <li>Pemeriksaan draf jurnal & tugas kelas</li>
                            <li>Masa aktif paket 30 hari penuh</li>
                            <li>Aktivasi instan via QRIS & Virtual Account</li>
                        </ul>
                    </div>

                    <?php if ($currentUser) : ?>
                        <a href="<?= base_url('dashboard') ?>" class="btn btn-outline">
                            Pilih Paket Dosen
                        </a>
                    <?php else : ?>
                        <button type="button" onclick="openAuthModal('register')" class="btn btn-outline">
                            Daftar & Langganan
                        </button>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Institutional & Enterprise Banner -->
            <div data-aos="fade-up" data-aos-delay="300" data-aos-duration="700" style="margin-top: 36px; background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 100%); border-radius: 20px; padding: 32px 36px; color: #ffffff; display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 24px; border: 1px solid rgba(99, 102, 241, 0.25); box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.3);">
                <div style="max-width: 780px;">
                    <div style="display: inline-flex; align-items: center; gap: 8px; padding: 4px 12px; border-radius: 20px; background: rgba(99, 102, 241, 0.2); border: 1px solid rgba(99, 102, 241, 0.4); font-size: 11px; font-weight: 800; color: #a5b4fc; text-transform: uppercase; margin-bottom: 12px;">
                        🏛️ Solusi Kampus & Lembaga Riset
                    </div>
                    <h3 style="font-size: 22px; font-weight: 800; margin-bottom: 8px; color: #ffffff;">Paket Kampus, OJS Jurnal & LPPM (Enterprise)</h3>
                    <p style="font-size: 14px; color: #cbd5e1; line-height: 1.6; margin: 0;">
                        Butuh integrasi dengan repositori skripsi internal prodi, plugin OJS 3 untuk penerbitan jurnal, atau plugin Moodle LMS kampus? Kami menyediakan instalasi mandiri (on-premise / private cloud), korpus tertutup institusi, serta sinkronisasi OAI-PMH otomatis dengan SLA dukungan teknis dedicated.
                    </p>
                </div>
                <div style="flex-shrink: 0; width: 100%; max-width: 260px; text-align: center;">
                    <button type="button" onclick="openContactModal('Konsultasi Institusi & Lisensi Kampus')" class="btn" style="background: #ffffff; color: #0f172a; font-weight: 800; width: 100%; border: none; padding: 14px 20px; display: inline-block; text-decoration: none; border-radius: 14px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); cursor: pointer;">
                        Konsultasi Institusi →
                    </button>
                    <div style="font-size: 11px; color: #94a3b8; margin-top: 8px;">Mulai Rp 250.000 / bln untuk pengelolaan jurnal</div>
                </div>
            </div>

            <!-- Payment Methods Supported Bar -->
            <div style="margin-top: 24px; padding: 18px 24px; background: #f8fafc; border: 1px solid var(--border); border-radius: 16px; display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 14px; font-size: 13px; color: var(--text-muted);" data-aos="fade-up" data-aos-delay="350">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <span style="font-size: 18px;">💳</span>
                    <span><strong>Metode Pembayaran Resmi (Midtrans Gateway):</strong> QRIS (GoPay, OVO, Dana, ShopeePay, LinkAja) & Virtual Account (BCA, BRI, BNI, Mandiri)</span>
                </div>
                <div style="font-size: 12px; color: #64748b;">
                    🔒 Terenkripsi SSL 256-bit • Kuota langsung bertambah otomatis dalam hitungan detik
                </div>
            </div>

            <!-- Callout Duta Kampus di Area Tarif -->
            <div style="margin-top: 16px; padding: 16px 24px; background: linear-gradient(90deg, rgba(245, 158, 11, 0.08) 0%, rgba(99, 102, 241, 0.08) 100%); border: 1px dashed rgba(245, 158, 11, 0.4); border-radius: 16px; display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 14px;" data-aos="fade-up">
                <div style="display: flex; align-items: center; gap: 12px; font-size: 13px; color: #1e293b;">
                    <span style="font-size: 22px;">🎓</span>
                    <span><strong>Peluang Mahasiswa &amp; Dosen:</strong> Ingin akses naskah gratis 30 hari &amp; penghasilan bagi hasil 20% di kampus Anda?</span>
                </div>
                <a href="<?= base_url('ambassador') ?>" class="btn" style="background: #f59e0b; color: #0f172a; font-weight: 800; font-size: 12.5px; padding: 9px 18px; border-radius: 10px; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 4px 10px rgba(245, 158, 11, 0.25);">
                    <span>Daftar Duta Kampus</span> &rarr;
                </a>
            </div>
        </div>
    </section>

    <!-- FAQ Section (Schema.org Aligned) -->
    <section id="faq" class="faq-section">
        <div class="container">
            <div class="section-header" data-aos="fade-up" data-aos-duration="700">
                <div class="section-tag" style="color: var(--primary);">Pertanyaan Umum (FAQ)</div>
                <h2 class="section-title">Semua Hal yang Perlu Anda Ketahui Tentang AR Tilik</h2>
                <p class="section-desc">
                    Penjelasan terbuka mengenai cara kerja mesin penelaah kesepadanan naskah, privasi dokumen karya ilmiah, integrasi OJS 3.x, serta sertifikasi keabsahan.
                </p>
            </div>

            <div class="faq-list" data-aos="fade-up" data-aos-delay="100" data-aos-duration="700">
                <!-- FAQ 1 -->
                <div class="faq-card">
                    <button type="button" class="faq-btn" onclick="toggleFaq(this)" aria-expanded="false">
                        <span>Apa perbedaan mendasar antara AR Tilik dan aplikasi pemeriksa plagiarisme lainnya?</span>
                        <span class="faq-icon">▼</span>
                    </button>
                    <div class="faq-content">
                        <p>
                            AR Tilik berfokus pada <strong>Keadilan Bukti (Evidence-Based Fairness)</strong> menggunakan arsitektur <em>Dual-Lens</em> (5-Gram Sintaktis &amp; TF-IDF Semantik) serta kesadaran konteks sitasi. Berbeda dengan matcher konvensional yang kerap menandai kutipan resmi, rumus, dan frasa umum sebagai plagiarisme, AR Tilik mengisolasi daftar pustaka, mengenali blok kutipan bertanda, dan menyediakan sintesis risiko terkalibrasi melalui Sintesis Risiko Akademik Berbobot serta penampil perbandingan berdampingan (Dual-Reader).
                        </p>
                    </div>
                </div>

                <!-- FAQ 2 -->
                <div class="faq-card">
                    <button type="button" class="faq-btn" onclick="toggleFaq(this)" aria-expanded="false">
                        <span>Apakah naskah karya ilmiah yang saya unggah akan disimpan ke repositori publik?</span>
                        <span class="faq-icon">▼</span>
                    </button>
                    <div class="faq-content">
                        <p>
                            <strong>Sama sekali tidak.</strong> AR Tilik menerapkan kebijakan <em>non-repository default</em> untuk melindungi kerahasiaan dan kedaulatan naskah submisi sivitas akademika. Naskah hanya diproses di lingkungan memori terisolasi untuk menghasilkan laporan telaah kesepadanan dan tidak pernah dibagikan atau dijadikan korpus terbuka tanpa izin eksplisit penulis.
                        </p>
                    </div>
                </div>

                <!-- FAQ 3 -->
                <div class="faq-card">
                    <button type="button" class="faq-btn" onclick="toggleFaq(this)" aria-expanded="false">
                        <span>Bagaimana cara mengintegrasikan AR Tilik ke jurnal Open Journal Systems (OJS 3.x)?</span>
                        <span class="faq-icon">▼</span>
                    </button>
                    <div class="faq-content">
                        <p>
                            Integrasi OJS sangat praktis tanpa perlu mengubah core OJS. Pengelola jurnal cukup mengunduh berkas konektor mandiri <code>ARTilikOJSConnector.php</code> dan mengikuti panduan di Buku Panduan OJS (PDF). Konektor ini memanfaatkan REST API v1 AR Tilik untuk melakukan pemeriksaan submisi naskah secara otomatis saat penulis mengunggah artikel di OJS, serta langsung menyematkan skor kemiripan dan tautan sertifikat QR ke meja editor.
                        </p>
                    </div>
                </div>

                <!-- FAQ 4 -->
                <div class="faq-card">
                    <button type="button" class="faq-btn" onclick="toggleFaq(this)" aria-expanded="false">
                        <span>Bagaimana AR Tilik mendeteksi kecurangan manipulasi karakter (Font Spoofing &amp; Zero-Width Space)?</span>
                        <span class="faq-icon">▼</span>
                    </button>
                    <div class="faq-content">
                        <p>
                            AR Tilik dilengkapi modul <em>Anti-Cheat Font &amp; Homoglyph Shield</em> yang secara otomatis menormalisasi karakter Cyrillic/Greek yang mirip dengan Latin, menghapus karakter tak kasat mata (Zero-Width Spaces/Joiners), dan mendeteksi injeksi teks tersembunyi yang lazim digunakan untuk mengelabui deteksi similarity tradisional.
                        </p>
                    </div>
                </div>

                <!-- FAQ 5 -->
                <div class="faq-card">
                    <button type="button" class="faq-btn" onclick="toggleFaq(this)" aria-expanded="false">
                        <span>Apakah sivitas akademika (mahasiswa &amp; dosen) dapat menggunakan AR Tilik secara gratis?</span>
                        <span class="faq-icon">▼</span>
                    </button>
                    <div class="faq-content">
                        <p>
                            <strong>Ya, 100% gratis.</strong> Setiap akun yang mendaftar langsung memperoleh kuota 3 submisi per minggu yang diperbarui secara otomatis. Selain itu, sivitas akademika dapat menambah kuota permanen secara gratis melalui program <em>Gotong Royong Korpus</em> dengan menyumbangkan repositori skripsi/tesis berlisensi terbuka (Open Access), atau melakukan top-up terjangkau mulai Rp 5.000 via QRIS.
                        </p>
                    </div>
                </div>

                <!-- FAQ 6 -->
                <div class="faq-card">
                    <button type="button" class="faq-btn" onclick="toggleFaq(this)" aria-expanded="false">
                        <span>Bagaimana cara memeriksa keabsahan dan keaslian Sertifikat Digital AR Tilik?</span>
                        <span class="faq-icon">▼</span>
                    </button>
                    <div class="faq-content">
                        <p>
                            Setiap laporan hasil telaah AR Tilik dilengkapi kode hash kriptografis unik dan tautan QR Code resmi yang mengarah ke portal verifikasi publik (<code>https://tilik.arsetontong.top/verify/{hash}</code>). Pihak perpustakaan prodi, dewan penguji skripsi, atau editor jurnal dapat memindai QR tersebut kapan saja untuk memvalidasi skor kesepadanan, tanggal terbit, dan keaslian sertifikat tanpa risiko pemalsuan PDF.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Section Khusus: Peluang Bergabung Duta Kampus AR Tilik -->
    <section id="duta-kampus-cta" class="section" style="background: linear-gradient(135deg, #090d16 0%, #1e1b4b 60%, #0f172a 100%); padding: 70px 0; border-top: 1px solid rgba(255,255,255,0.08); border-bottom: 1px solid rgba(255,255,255,0.08); color: #ffffff; position: relative; overflow: hidden;">
        <div class="container" style="position: relative; z-index: 2;">
            <div style="background: rgba(255, 255, 255, 0.04); border: 1px solid rgba(245, 158, 11, 0.35); border-radius: 28px; padding: 44px 36px; display: flex; flex-direction: column; lg:flex-row; gap: 32px; align-items: center; justify-content: space-between; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5); backdrop-filter: blur(12px);" data-aos="fade-up" data-aos-duration="800">
                <div style="max-width: 650px;">
                    <div style="display: inline-flex; align-items: center; gap: 8px; background: rgba(245, 158, 11, 0.15); border: 1px solid rgba(245, 158, 11, 0.3); padding: 5px 14px; border-radius: 9999px; font-size: 11px; font-weight: 800; color: #f59e0b; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 16px;">
                        <span>🎓</span> <span>Program Afiliasi &amp; Kemitraan Kampus</span>
                    </div>
                    <h2 style="font-size: 28px; font-weight: 800; line-height: 1.3; color: #ffffff; margin-bottom: 14px; letter-spacing: -0.5px;">
                        Jadilah <span style="background: linear-gradient(90deg, #f59e0b, #fbbf24); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">Duta Kampus AR Tilik</span> di Universitas Anda
                    </h2>
                    <p style="font-size: 14px; color: #cbd5e1; line-height: 1.6; margin-bottom: 20px;">
                        Bantu rekan mahasiswa, peneliti, dan dosen di kampus Anda mengakses uji kemiripan naskah yang adil dan transparan. Dapatkan <strong>Akun Premium 30 Hari Gratis</strong>, <strong>Bagi Hasil 20%</strong> untuk setiap transaksi dari kode referral Anda, serta <strong>Sertifikat Pengabdian Resmi</strong>.
                    </p>
                    <div style="display: flex; flex-wrap: wrap; gap: 16px; font-size: 12px; color: #94a3b8;">
                        <span style="display: flex; align-items: center; gap: 6px;"><strong style="color: #10b981;">✓</strong> Tanpa Modal &amp; Biaya Pendaftaran</span>
                        <span style="display: flex; align-items: center; gap: 6px;"><strong style="color: #10b981;">✓</strong> Dilengkapi Welcome Kit &amp; Copywriting Siap Pakai</span>
                        <span style="display: flex; align-items: center; gap: 6px;"><strong style="color: #10b981;">✓</strong> Pencairan Komisi Tiap Akhir Bulan</span>
                    </div>
                </div>

                <div style="flex-shrink: 0; text-align: center; width: 100%; max-width: 280px;">
                    <a href="<?= base_url('ambassador') ?>" class="btn" style="background: linear-gradient(135deg, #f59e0b, #d97706); color: #090d16; font-weight: 800; font-size: 15px; padding: 16px 28px; border-radius: 16px; width: 100%; display: inline-flex; align-items: center; justify-content: center; gap: 8px; text-decoration: none; box-shadow: 0 10px 25px rgba(245, 158, 11, 0.35); transition: transform 0.2s ease;">
                        <span>🚀</span> <span>Daftar Jadi Duta</span> &rarr;
                    </a>
                    <div style="font-size: 11px; color: #64748b; margin-top: 10px;">
                        Seleksi berkas &amp; aktivasi kode dalam 1×24 jam kerja
                    </div>
                </div>
            </div>
        </div>
    </section>
    </main>

    <!-- Footer -->
    <footer class="footer" data-aos="fade-in" data-aos-duration="800">
        <div class="container">
            <div class="footer-grid">
                <div>
                    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 14px;">
                        <img src="<?= base_url('images/logo.png') ?>" alt="AR Tilik Logo" width="34" height="34" style="width: 34px; height: 34px; object-fit: contain; border-radius: 8px;">
                        <span style="font-size: 20px; font-weight: 800; color: #ffffff; letter-spacing: -0.5px;">AR Tilik</span>
                    </div>
                    <p style="line-height: 1.6; max-width: 380px;">
                        Sistem Pemeriksa Kesepadanan Naskah Akademik Berbasis Bukti Transparan. Dikembangkan dengan dedikasi untuk keadilan dan integritas ilmiah perguruan tinggi di Indonesia.
                    </p>
                </div>

                <div>
                    <strong style="color: #ffffff; display: block; margin-bottom: 14px;">Fitur &amp; Inovasi</strong>
                    <ul class="footer-links">
                        <li><a href="#teori">Dual-Lens Engine (5-Gram &amp; Cosine)</a></li>
                        <li><a href="#fitur-unggulan">Deteksi Lintas Bahasa (EN⇄ID)</a></li>
                        <li><a href="#fitur-unggulan">Perisai Anti-Manipulasi Karakter</a></li>
                        <li><a href="#fitur-unggulan">Auditor Sitasi &amp; Daftar Pustaka</a></li>
                        <li><a href="#fitur-unggulan">Pemanen Neliti &amp; GARUDA Dikti</a></li>
                        <li><a href="#cara-kerja">Layar Ganda (Dual-Reader)</a></li>
                    </ul>
                </div>

                <div>
                    <strong style="color: #ffffff; display: block; margin-bottom: 14px;">Layanan &amp; Solusi</strong>
                    <ul class="footer-links">
                        <li><a href="#integrasi-ojs">Integrasi OJS 3.x Jurnal</a></li>
                        <li><a href="#solusi">Solusi Sivitas Akademika</a></li>
                        <li><a href="<?= base_url('ambassador') ?>">Program Duta Kampus (Afiliasi)</a></li>
                        <li><a href="<?= base_url('verify') ?>">Portal Verifikasi QR</a></li>
                        <li><a href="#kuota-korpus">Gotong Royong Korpus</a></li>
                        <li><a href="#langganan">Paket Tarif &amp; Top-Up</a></li>
                        <?php if ($currentUser) : ?>
                            <li><a href="<?= base_url('dashboard') ?>">Dashboard Pemeriksaan</a></li>
                        <?php else : ?>
                            <li><button type="button" onclick="openAuthModal('login')">Masuk Dashboard</button></li>
                        <?php endif; ?>
                    </ul>
                </div>

                <div>
                    <strong style="color: #ffffff; display: block; margin-bottom: 14px;">Dokumentasi &amp; Legal</strong>
                    <ul class="footer-links">
                        <li><a href="<?= base_url('riset/ar-tilik-vs-turnitin') ?>" style="color: #38bdf8; font-weight: 600;">🏛️ Whitepaper: AR Tilik vs Turnitin</a></li>
                        <li><a href="<?= base_url('riset/ar-tilik-vs-zerogpt') ?>" style="color: #fdba74; font-weight: 600;">📊 Whitepaper: AR Tilik vs ZeroGPT</a></li>
                        <li><a href="<?= base_url('docs/Brosur_Eksekutif_ARTilik.pdf') ?>" target="_blank" download="Brosur_Eksekutif_ARTilik.pdf">Brosur Eksekutif Institusi (PDF)</a></li>
                        <li><a href="<?= base_url('integrations/ojs/Panduan_Integrasi_OJS_ARTilik.pdf') ?>" target="_blank" download="Panduan_Integrasi_OJS_ARTilik.pdf">Buku Panduan OJS (PDF)</a></li>
                        <li><a href="<?= base_url('integrations/ojs/connector') ?>" download="ARTilikOJSConnector.php">Konektor PHP OJS 3.x</a></li>
                        <li><a href="<?= base_url('integrations/ojs/README.md') ?>" target="_blank">Spesifikasi REST API v1</a></li>
                        <li><a href="#faq">Tanya Jawab (FAQ)</a></li>
                        <li><a href="<?= base_url('sitemap.xml') ?>" target="_blank">Peta Situs (Sitemap XML)</a></li>
                        <li><a href="<?= base_url('privacy') ?>" onclick="openLegalModal('privacy', event)">Kebijakan Privasi</a></li>
                        <li><a href="<?= base_url('terms') ?>" onclick="openLegalModal('terms', event)">Ketentuan Layanan (ToS)</a></li>
                        <li><button type="button" onclick="openContactModal('Pertanyaan Umum')">Kontak Pengembang</button></li>
                    </ul>
                </div>
            </div>

            <div class="footer-counter-wrap">
                <div class="visitor-counter-badge">
                    <span class="live-dot"></span>
                    <span>Pengunjung Unik: <strong><?= number_format($visitorStats['total_unique'] ?? 1) ?></strong></span>
                    <span class="counter-sep">&bull;</span>
                    <span>Hari Ini: <strong><?= number_format($visitorStats['today_unique'] ?? 1) ?></strong></span>
                    <span class="counter-sep">&bull;</span>
                    <span>Total Kunjungan: <strong><?= number_format($visitorStats['total_hits'] ?? 1) ?></strong></span>
                </div>
            </div>

            <div class="footer-bottom">
                AR Tilik &copy; <?= date('Y') ?> &bull; Academic Writing Similarity & Evidence Checker &bull; <a href="https://www.arsetontong.top" target="_blank" rel="noopener noreferrer" style="color: #94a3b8; text-decoration: underline; text-underline-offset: 2px;">Powered by ARSetontong</a>
            </div>
        </div>
    </footer>

    <!-- Floating Go to Top Button -->
    <button type="button" id="scrollTopBtn" class="btn-scroll-top" onclick="scrollToTop()" aria-label="Kembali ke atas" title="Kembali ke atas">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="18 15 12 9 6 15"></polyline>
        </svg>
    </button>

    <!-- Auth Modal (Registration & Login) -->
    <div id="authModal" class="modal-overlay">
        <div class="modal-card">
            <div class="modal-header">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <img src="<?= base_url('images/logo.png') ?>" alt="AR Tilik Logo" width="26" height="26" style="width: 26px; height: 26px; object-fit: contain; border-radius: 6px;">
                    <div class="modal-title" id="modalTitle">Masuk ke AR Tilik</div>
                </div>
                <button type="button" class="modal-close" onclick="closeAuthModal()">&times;</button>
            </div>

            <div class="modal-body">
                <div class="tab-switch">
                    <button type="button" id="tabLogin" class="tab-btn active" onclick="switchTab('login')">Masuk</button>
                    <button type="button" id="tabRegister" class="tab-btn" onclick="switchTab('register')">Daftar Akun Gratis</button>
                </div>

                <div id="authAlert" class="alert-box"></div>

                <!-- Login Form -->
                <form id="formLogin" onsubmit="handleLogin(event)">
                    <!-- Invisible Honeypot Trap for Bots -->
                    <div style="display:none; visibility:hidden; opacity:0; position:absolute; left:-9999px;" aria-hidden="true">
                        <input type="text" name="website_hp" tabindex="-1" autocomplete="off">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="loginEmail">Alamat Email</label>
                        <input type="email" id="loginEmail" name="email" class="form-control" placeholder="nama@kampus.ac.id" required>
                    </div>

                    <div class="form-group">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                            <label class="form-label" for="loginPassword" style="margin-bottom: 0;">Kata Sandi</label>
                            <button type="button" onclick="switchTab('forgot')" style="background: none; border: none; padding: 0; font-family: inherit; font-size: 11px; font-weight: 700; color: var(--primary); text-decoration: none; cursor: pointer;">
                                Lupa Kata Sandi?
                            </button>
                        </div>
                        <input type="password" id="loginPassword" name="password" class="form-control" placeholder="••••••••" required>
                    </div>

                    <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 8px;">
                        Masuk ke Akun
                    </button>
                </form>

                <!-- Forgot Password Form -->
                <form id="formForgot" onsubmit="handleForgotPassword(event)" style="display: none;">
                    <!-- Anti-Spam Time Token & Honeypot Trap -->
                    <input type="hidden" name="security_token" id="forgotSecurityToken" value="<?= esc($authSecurityToken ?? '') ?>">
                    <div style="display:none; visibility:hidden; opacity:0; position:absolute; left:-9999px;" aria-hidden="true">
                        <input type="text" name="website_hp" tabindex="-1" autocomplete="off">
                        <input type="text" name="fax_number" tabindex="-1" autocomplete="off">
                    </div>

                    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 12px 14px; margin-bottom: 16px;">
                        <p style="font-size: 12px; color: var(--text-muted); line-height: 1.5; margin: 0;">
                            🔐 Masukkan alamat email akun Anda. Kami akan mengirimkan tautan aman untuk mengatur ulang kata sandi.
                        </p>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="forgotEmail">Alamat Email Terdaftar</label>
                        <input type="email" id="forgotEmail" name="email" class="form-control" placeholder="nama@kampus.ac.id" required>
                    </div>

                    <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 8px;">
                        Kirim Tautan Atur Ulang
                    </button>

                    <div style="text-align: center; margin-top: 14px;">
                        <button type="button" onclick="switchTab('login')" style="background: none; border: none; padding: 0; font-family: inherit; font-size: 12px; font-weight: 700; color: var(--primary); text-decoration: underline; cursor: pointer;">
                            ← Kembali ke Halaman Masuk
                        </button>
                    </div>
                </form>

                <!-- Register Form -->
                <form id="formRegister" onsubmit="handleRegister(event)" style="display: none;">
                    <!-- Anti-Spam Time Token & Honeypot Trap -->
                    <input type="hidden" name="security_token" id="regSecurityToken" value="<?= esc($authSecurityToken ?? '') ?>">
                    <input type="hidden" name="referral_code" id="regReferralCode" value="<?= esc(session()->get('referred_by_code') ?? ($_COOKIE['referred_by_code'] ?? '')) ?>">
                    <div style="display:none; visibility:hidden; opacity:0; position:absolute; left:-9999px;" aria-hidden="true">
                        <input type="text" name="fax_number" tabindex="-1" autocomplete="off">
                    </div>

                    <!-- Referral Bonus Badge -->
                    <div id="referralBonusBadge" style="<?= empty(session()->get('referred_by_code')) && empty($_COOKIE['referred_by_code']) ? 'display:none;' : '' ?> background: #ecfdf5; border: 1px solid #a7f3d0; border-radius: 12px; padding: 10px 14px; margin-bottom: 16px; font-size: 11.5px; color: #065f46;">
                        <span style="font-weight: 700;">🎁 Bonus Sahabat & Duta:</span> Kode <span id="displayRefCode" style="font-family: monospace; font-weight: 800; color: #047857;"><?= esc(session()->get('referred_by_code') ?? ($_COOKIE['referred_by_code'] ?? '')) ?></span> terpasang. Anda mendapatkan <strong>+1 bonus cek naskah gratis</strong> & <strong>diskon perdana 10%</strong>!
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="regName">Nama Lengkap</label>
                        <input type="text" id="regName" name="name" class="form-control" placeholder="Nama Anda" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="regEmail">Alamat Email Valid</label>
                        <input type="email" id="regEmail" name="email" class="form-control" placeholder="nama@kampus.ac.id" required>
                        <small style="font-size: 11px; color: var(--text-muted); margin-top: 4px; display: block; line-height: 1.4;">
                            ✉️ Tautan aktivasi akun akan dikirimkan ke email ini. Periksa folder <strong>Kotak Masuk (Inbox)</strong> maupun <strong>SPAM</strong>.
                        </small>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="regInstitution">Institusi / Universitas</label>
                        <input type="text" id="regInstitution" name="institution" class="form-control" placeholder="Contoh: Universitas Indonesia / UGM">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="regPassword">Kata Sandi</label>
                        <input type="password" id="regPassword" name="password" class="form-control" placeholder="Minimal 6 karakter" minlength="6" required>
                    </div>

                    <!-- Pemberitahuan Khusus: Email Verifikasi & Folder SPAM -->
                    <div style="background: rgba(245, 158, 11, 0.08); border: 1px solid rgba(245, 158, 11, 0.28); border-radius: 10px; padding: 10px 12px; margin-top: 12px; margin-bottom: 14px; font-size: 11.5px; color: #b45309; line-height: 1.5;">
                        <div style="display: flex; align-items: flex-start; gap: 8px;">
                            <span style="font-size: 15px; line-height: 1.1;">📬</span>
                            <div>
                                <strong style="color: #92400e;">Penting Terkait Email Verifikasi:</strong><br>
                                Tautan aktivasi dikirim otomatis ke email Anda sesaat setelah mendaftar. Apabila tidak ditemukan pada folder <strong>Kotak Masuk (Inbox)</strong>, mohon periksa juga folder <strong>SPAM / Junk</strong> atau tab <em>Promosi</em> akun email Anda.
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 4px;">
                        Daftar Gratis (3 Submisi/Minggu)
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Contact & Inquiry Modal -->
    <div id="contactModal" class="modal-overlay">
        <div class="modal-card modal-card-md">
            <div class="modal-header">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <div style="width: 32px; height: 32px; border-radius: 8px; background: rgba(79, 70, 229, 0.1); color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: 16px;">
                        📬
                    </div>
                    <div>
                        <div class="modal-title" style="font-size: 17px; font-weight: 800; color: var(--dark);">Hubungi Tim Pengembang</div>
                        <div style="font-size: 12px; color: var(--text-muted);">Pesan langsung diteruskan ke tim pengembang AR Tilik</div>
                    </div>
                </div>
                <button type="button" class="modal-close" onclick="closeContactModal()">&times;</button>
            </div>

            <div class="modal-body">
                <div id="contactAlert" class="alert-box"></div>

                <form id="formContact" onsubmit="handleContactSubmit(event)">
                    <!-- Anti-Spam Security Token -->
                    <input type="hidden" name="security_token" id="contactSecurityToken" value="<?= esc($authSecurityToken ?? '') ?>">
                    
                    <!-- Hidden Honeypot Traps (Invisibly trapped for spambots) -->
                    <div style="display:none; visibility:hidden; opacity:0; position:absolute; left:-9999px;" aria-hidden="true">
                        <input type="text" name="website_hp" tabindex="-1" autocomplete="off">
                        <input type="text" name="fax_number" tabindex="-1" autocomplete="off">
                    </div>

                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 12px;">
                        <div class="form-group" style="margin-bottom: 12px;">
                            <label class="form-label" for="contactName">Nama Lengkap *</label>
                            <input type="text" id="contactName" name="name" class="form-control" placeholder="Prof. / Dr. / Bpk / Ibu" required>
                        </div>
                        <div class="form-group" style="margin-bottom: 12px;">
                            <label class="form-label" for="contactEmail">Email Aktif *</label>
                            <input type="email" id="contactEmail" name="email" class="form-control" placeholder="nama@kampus.ac.id" required oninput="handleEmailInputDebounced(this.value)">
                            <div id="emailVerifyFeedback" style="font-size: 11px; margin-top: 5px; min-height: 18px; color: var(--text-muted); line-height: 1.4; transition: all 0.2s;">
                                <span>✉️ Masukkan email aktif (domain institusi atau email resmi Anda)</span>
                            </div>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 12px;">
                        <div class="form-group" style="margin-bottom: 12px;">
                            <label class="form-label" for="contactInstitution">Institusi / Kampus</label>
                            <input type="text" id="contactInstitution" name="institution" class="form-control" placeholder="Contoh: Univ. Indonesia / UGM">
                        </div>
                        <div class="form-group" style="margin-bottom: 12px;">
                            <label class="form-label" for="contactSubject">Subjek Kebutuhan *</label>
                            <select id="contactSubject" name="subject" class="form-control" required style="padding: 10px 14px; height: 42px;">
                                <option value="Pertanyaan Umum">Pertanyaan Umum</option>
                                <option value="Konsultasi Institusi & Lisensi Kampus">Konsultasi Institusi & Lisensi Kampus</option>
                                <option value="Integrasi Plugin OJS 3.x">Integrasi Plugin OJS 3.x</option>
                                <option value="Kemitraan Korpus & Repositori">Kemitraan Korpus & Repositori</option>
                                <option value="Kebijakan Privasi & Hak Cipta">Kebijakan Privasi & Hak Cipta</option>
                                <option value="Laporan Kendala Teknis">Laporan Kendala Teknis</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group" style="margin-bottom: 12px;">
                        <label class="form-label" for="contactMessage">Pesan / Pertanyaan Detail *</label>
                        <textarea id="contactMessage" name="message" class="form-control" rows="4" placeholder="Jelaskan kebutuhan, jumlah jurnal/mahasiswa, atau kendala teknis Anda..." required style="resize: vertical; min-height: 85px;"></textarea>
                    </div>

                    <!-- Math Captcha Challenge to defeat Automated AI / Spambots -->
                    <div class="form-group" style="margin-bottom: 16px; background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 12px; padding: 12px 16px;">
                        <label class="form-label" style="font-size: 12px; color: #475569; display: flex; align-items: center; justify-content: space-between; margin-bottom: 6px;">
                            <span>🛡️ Uji Anti-Spam: Hitung <strong id="mathProblemLabel" style="color: var(--primary); font-size: 13px;">...</strong></span>
                            <button type="button" onclick="generateMathCaptcha()" style="background:none; border:none; color:var(--primary); font-size:11px; font-weight:700; cursor:pointer; text-decoration:underline;">Ganti Soal</button>
                        </label>
                        <div style="display: flex; gap: 10px; align-items: center;">
                            <input type="number" id="mathAnswerInput" name="captcha_ans" class="form-control" placeholder="Jawaban angka" required style="max-width: 140px; font-weight: 700; text-align: center;">
                            <input type="hidden" id="mathChallengeToken" name="captcha_token">
                            <span style="font-size: 11px; color: var(--text-muted); line-height: 1.4;">Diperlukan untuk memvalidasi bahwa pengirim adalah akademisi sungguhan.</span>
                        </div>
                    </div>

                    <button type="submit" id="btnSubmitContact" class="btn btn-primary" style="width: 100%;">
                        Kirim Pesan ke Pengembang 🚀
                    </button>
                    <div style="font-size: 11px; color: var(--text-muted); text-align: center; margin-top: 10px;">
                        📧 Pesan Anda langsung diteruskan ke <strong>admin@tilik.arsetontong.top</strong>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Legal Document Modal (Privacy Policy & Terms) -->
    <div id="legalModal" class="modal-overlay">
        <div class="modal-card modal-card-lg">
            <div class="modal-header" style="border-bottom: 1px solid var(--border); padding: 18px 24px;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <img src="<?= base_url('images/logo.png') ?>" alt="AR Tilik Logo" width="24" height="24" style="width: 24px; height: 24px; object-fit: contain;">
                    <div class="modal-title" id="legalModalTitle" style="font-size: 17px; font-weight: 800; color: var(--dark);">Dokumen Legal</div>
                </div>
                <div style="display: flex; align-items: center; gap: 12px;">
                    <a id="legalFullPageLink" href="<?= base_url('privacy') ?>" target="_blank" style="font-size: 12px; color: var(--primary); font-weight: 700; text-decoration: underline;">
                        Buka Halaman Penuh ↗
                    </a>
                    <button type="button" class="modal-close" onclick="closeLegalModal()">&times;</button>
                </div>
            </div>
            <div id="legalModalBody" class="modal-body-scroll">
                <div style="text-align: center; padding: 40px; color: var(--text-muted);">
                    <div class="spinner" style="margin: 0 auto 14px;"></div>
                    <span>Memuat dokumen legal...</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script>
        async function openAuthModal(mode) {
            const modal = document.getElementById('authModal');
            modal.style.display = 'flex';
            switchTab(mode);

            const tokenInputs = [document.getElementById('regSecurityToken'), document.getElementById('forgotSecurityToken')];
            try {
                let tokenVal = null;
                for (const input of tokenInputs) {
                    if (input && (!input.value || input.value === '')) {
                        if (!tokenVal) {
                            const res = await fetch('<?= base_url('auth/security-token') ?>');
                            const data = await res.json();
                            tokenVal = data.token;
                        }
                        if (tokenVal) input.value = tokenVal;
                    }
                }
            } catch (e) {}
        }

        function closeAuthModal() {
            const modal = document.getElementById('authModal');
            modal.style.display = 'none';
            hideAlert();
        }

        window.onclick = function(event) {
            const authModal = document.getElementById('authModal');
            const contactModal = document.getElementById('contactModal');
            const legalModal = document.getElementById('legalModal');

            if (event.target === authModal) closeAuthModal();
            if (event.target === contactModal) closeContactModal();
            if (event.target === legalModal) closeLegalModal();
        };

        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeAuthModal();
                closeContactModal();
                closeLegalModal();
            }
        });

        function switchTab(mode) {
            const tabSwitch = document.querySelector('.tab-switch');
            const tabLogin = document.getElementById('tabLogin');
            const tabRegister = document.getElementById('tabRegister');
            const formLogin = document.getElementById('formLogin');
            const formRegister = document.getElementById('formRegister');
            const formForgot = document.getElementById('formForgot');
            const modalTitle = document.getElementById('modalTitle');

            hideAlert();

            if (mode === 'register') {
                if (tabSwitch) tabSwitch.style.display = 'flex';
                tabRegister.classList.add('active');
                tabLogin.classList.remove('active');
                formRegister.style.display = 'block';
                formLogin.style.display = 'none';
                if (formForgot) formForgot.style.display = 'none';
                modalTitle.textContent = 'Daftar Akun AR Tilik Gratis';
            } else if (mode === 'forgot') {
                if (tabSwitch) tabSwitch.style.display = 'none';
                formLogin.style.display = 'none';
                formRegister.style.display = 'none';
                if (formForgot) {
                    formForgot.style.display = 'block';
                    const loginEmailVal = document.getElementById('loginEmail')?.value;
                    const forgotEmailInput = document.getElementById('forgotEmail');
                    if (loginEmailVal && forgotEmailInput && !forgotEmailInput.value) {
                        forgotEmailInput.value = loginEmailVal;
                    }
                }
                modalTitle.textContent = 'Atur Ulang Kata Sandi';
            } else {
                if (tabSwitch) tabSwitch.style.display = 'flex';
                tabLogin.classList.add('active');
                tabRegister.classList.remove('active');
                formLogin.style.display = 'block';
                formRegister.style.display = 'none';
                if (formForgot) formForgot.style.display = 'none';
                modalTitle.textContent = 'Masuk ke AR Tilik';
            }
        }

        function showAlert(msg, isError = true) {
            const alertBox = document.getElementById('authAlert');
            alertBox.innerHTML = msg;
            alertBox.className = 'alert-box ' + (isError ? 'alert-error' : 'alert-success');
            alertBox.style.display = 'block';
        }

        function hideAlert() {
            const alertBox = document.getElementById('authAlert');
            alertBox.style.display = 'none';
        }

        async function handleLogin(e) {
            e.preventDefault();
            hideAlert();

            const form = e.target;
            const submitBtn = form.querySelector('button[type="submit"]');
            const originalBtnText = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span>Memverifikasi...</span>';

            const formData = new FormData(form);
            const loginEmailVal = formData.get('email');

            try {
                const res = await fetch('<?= base_url('auth/login') ?>', {
                    method: 'POST',
                    headers: { 'Accept': 'application/json' },
                    body: formData
                });
                const data = await res.json();

                if (!res.ok || data.status === 'error') {
                    if (data.needs_verification) {
                        showAlert('⚠️ ' + data.message + 
                            '<br><button type="button" onclick="resendVerification(\'' + (data.email || loginEmailVal) + '\')" style="background:none; border:none; padding:0; cursor:pointer; font-family:inherit; color:var(--primary); font-weight:700; text-decoration:underline; display:inline-block; margin-top:8px;">✉️ Kirim Ulang Tautan Aktivasi Sekarang</button>', true);
                    } else {
                        showAlert(data.message || 'Gagal masuk akun. Periksa email & kata sandi.', true);
                    }
                } else {
                    showAlert(data.message || 'Berhasil masuk! Mengarahkan...', false);
                    setTimeout(() => {
                        const urlParams = new URLSearchParams(window.location.search);
                        const redirectUrl = urlParams.get('redirect') || data.redirect || '<?= base_url('dashboard') ?>';
                        window.location.href = redirectUrl;
                    }, 600);
                }
            } catch (err) {
                showAlert('Kendala koneksi peladen. Silakan coba kembali.', true);
            } finally {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnText;
            }
        }

        async function handleRegister(e) {
            e.preventDefault();
            hideAlert();

            const form = e.target;
            const submitBtn = form.querySelector('button[type="submit"]');
            const originalBtnText = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span>Mengirim Email Aktivasi...</span>';

            const formData = new FormData(form);
            const userEmail = formData.get('email');

            try {
                const res = await fetch('<?= base_url('auth/register') ?>', {
                    method: 'POST',
                    headers: { 'Accept': 'application/json' },
                    body: formData
                });
                const data = await res.json();

                if (!res.ok || data.status === 'error') {
                    if (data.needs_verification) {
                        showAlert('⚠️ ' + (data.message || 'Email sudah terdaftar namun belum diverifikasi.') + 
                            '<br><button type="button" onclick="resendVerification(\'' + userEmail + '\')" style="background:none; border:none; padding:0; cursor:pointer; font-family:inherit; color:var(--primary); font-weight:700; text-decoration:underline; display:inline-block; margin-top:6px;">Kirim Ulang Email Aktivasi</button>', true);
                    } else {
                        showAlert(data.message || 'Gagal mendaftar. Silakan lengkapi formulir.', true);
                    }
                } else {
                    form.reset();
                    showAlert('🎉 <strong>Pendaftaran Berhasil!</strong><br>' + 
                        'Kami telah mengirimkan tautan verifikasi ke <strong>' + userEmail + '</strong>.<br>' +
                        '<div style="background:#fffbeb; border:1px solid #fde68a; border-radius:8px; padding:10px 12px; margin-top:10px; font-size:12px; color:#92400e; text-align:left; line-height:1.5;">' +
                        '📬 <strong>Perhatian:</strong> Apabila email belum muncul di <strong>Kotak Masuk (Inbox)</strong> dalam beberapa menit, mohon periksa juga folder <strong>SPAM / Junk</strong> atau tab <em>Promosi</em>. Tandai sebagai <strong>"Bukan Spam"</strong> agar pengiriman pemberitahuan selanjutnya lancar.' +
                        '</div>' +
                        '<div style="margin-top:12px;"><button type="button" onclick="resendVerification(\'' + userEmail + '\')" style="background:none; border:none; padding:0; cursor:pointer; font-family:inherit; font-size:12px; color:var(--primary); font-weight:700; text-decoration:underline;">Belum menerima email? Kirim Ulang</button></div>', false);
                }
            } catch (err) {
                showAlert('Kendala koneksi peladen. Silakan coba kembali.', true);
            } finally {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnText;
            }
        }

        async function resendVerification(email) {
            if (!email) {
                email = prompt('Masukkan alamat email yang Anda daftarkan:');
            }
            if (!email) return;

            showAlert('⏳ Sedang mengirimkan ulang tautan verifikasi...', false);
            try {
                const res = await fetch('<?= base_url('auth/resend-verification') ?>', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ email: email })
                });
                const data = await res.json();
                if (data.status === 'success') {
                    showAlert('✉️ ' + data.message, false);
                } else {
                    showAlert(data.message || 'Gagal mengirim ulang email verifikasi.', true);
                }
            } catch (err) {
                showAlert('Kendala koneksi peladen. Silakan coba kembali.', true);
            }
        }

        async function handleForgotPassword(e) {
            e.preventDefault();
            hideAlert();

            const form = e.target;
            const submitBtn = form.querySelector('button[type="submit"]');
            const originalBtnText = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span>Mengirim Tautan...</span>';

            const formData = new FormData(form);
            const userEmail = formData.get('email');

            try {
                const res = await fetch('<?= base_url('auth/forgot-password') ?>', {
                    method: 'POST',
                    headers: { 'Accept': 'application/json' },
                    body: formData
                });
                const data = await res.json();

                if (!res.ok || data.status === 'error') {
                    showAlert(data.message || 'Gagal mengajukan atur ulang kata sandi.', true);
                } else {
                    form.reset();
                    showAlert('✉️ <strong>Permintaan Terkirim!</strong><br>' + 
                        (data.message || 'Tautan atur ulang kata sandi telah dikirimkan ke email Anda.') + 
                        '<div style="margin-top:10px;"><button type="button" onclick="switchTab(\'login\')" style="background:none; border:none; padding:0; cursor:pointer; font-family:inherit; font-size:12px; color:var(--primary); font-weight:700; text-decoration:underline;">← Kembali ke Halaman Masuk</button></div>', false);
                }
            } catch (err) {
                showAlert('Kendala koneksi peladen. Silakan coba kembali.', true);
            } finally {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnText;
            }
        }

        /* --- Contact Form & Anti-Spam Functions --- */
        async function openContactModal(subject = 'Pertanyaan Umum') {
            const modal = document.getElementById('contactModal');
            modal.style.display = 'flex';
            hideContactAlert();

            const subjectSelect = document.getElementById('contactSubject');
            if (subjectSelect) {
                for (let i = 0; i < subjectSelect.options.length; i++) {
                    if (subjectSelect.options[i].value === subject || subjectSelect.options[i].text.includes(subject)) {
                        subjectSelect.selectedIndex = i;
                        break;
                    }
                }
            }

            await generateMathCaptcha();
        }

        function closeContactModal() {
            const modal = document.getElementById('contactModal');
            modal.style.display = 'none';
            hideContactAlert();
        }

        async function generateMathCaptcha() {
            const label = document.getElementById('mathProblemLabel');
            const tokenInput = document.getElementById('mathChallengeToken');
            const ansInput = document.getElementById('mathAnswerInput');
            const secTokenInput = document.getElementById('contactSecurityToken');

            if (label) label.textContent = 'Memuat soal...';
            if (ansInput) ansInput.value = '';

            try {
                const res = await fetch('<?= base_url('contact/challenge') ?>');
                const data = await res.json();
                if (data.status === 'success' && data.challenge) {
                    if (label) label.textContent = data.challenge.question + ' = ?';
                    if (tokenInput) tokenInput.value = data.challenge.token;
                    if (data.time_token && secTokenInput) {
                        secTokenInput.value = data.time_token;
                    }
                } else {
                    if (label) label.textContent = '3 + 4 = ?';
                }
            } catch (e) {
                if (label) label.textContent = '3 + 4 = ?';
            }
        }

        function showContactAlert(msg, isError = true) {
            const alertBox = document.getElementById('contactAlert');
            if (!alertBox) return;
            alertBox.innerHTML = msg;
            alertBox.className = 'alert-box ' + (isError ? 'alert-error' : 'alert-success');
            alertBox.style.display = 'block';
        }

        function hideContactAlert() {
            const alertBox = document.getElementById('contactAlert');
            if (alertBox) alertBox.style.display = 'none';
        }

        let emailDebounceTimer = null;
        let isEmailVerifiedActive = null;

        function handleEmailInputDebounced(val) {
            clearTimeout(emailDebounceTimer);
            const feedbackEl = document.getElementById('emailVerifyFeedback');
            const emailInput = document.getElementById('contactEmail');
            if (!feedbackEl || !emailInput) return;

            val = (val || '').trim();

            if (val === '') {
                feedbackEl.innerHTML = '<span style="color:var(--text-muted);">✉️ Masukkan email aktif (domain institusi atau email resmi Anda)</span>';
                emailInput.style.borderColor = '';
                isEmailVerifiedActive = null;
                return;
            }

            if (!val.includes('@') || !val.includes('.') || val.length < 5) {
                feedbackEl.innerHTML = '<span style="color:#64748b;">⏳ Melengkapi format email...</span>';
                emailInput.style.borderColor = '';
                isEmailVerifiedActive = null;
                return;
            }

            feedbackEl.innerHTML = '<span style="color:var(--primary); font-weight:600;">⏳ Memeriksa keaktifan server email di internet...</span>';

            emailDebounceTimer = setTimeout(async () => {
                try {
                    const res = await fetch('<?= base_url('contact/validate-email') ?>?email=' + encodeURIComponent(val));
                    const data = await res.json();

                    if (data.status === 'success' && data.valid) {
                        isEmailVerifiedActive = true;
                        emailInput.style.borderColor = '#10b981';
                        let html = '<span style="color:#059669; font-weight:600;">✓ Server email aktif & domain valid (' + (data.domain || '') + ')</span>';
                        if (data.suggestion) {
                            html += '<span style="color:#d97706; margin-top:2px;">💡 Apakah maksud Anda: <button type="button" onclick="applyEmailSuggestion(\'' + data.suggestion + '\')" style="background:none; border:none; padding:0; cursor:pointer; font-family:inherit; color:#d97706; font-weight:700; text-decoration:underline;">' + data.suggestion + '</button>?</span>';
                        }
                        feedbackEl.innerHTML = html;
                    } else {
                        isEmailVerifiedActive = false;
                        emailInput.style.borderColor = '#ef4444';
                        let html = '<span style="color:#dc2626; font-weight:600;">⚠️ ' + (data.reason || 'Email terindikasi tidak aktif atau fiktif.') + '</span>';
                        if (data.suggestion) {
                            html += '<span style="color:#d97706; margin-top:2px;">💡 Apakah maksud Anda: <button type="button" onclick="applyEmailSuggestion(\'' + data.suggestion + '\')" style="background:none; border:none; padding:0; cursor:pointer; font-family:inherit; color:#d97706; font-weight:700; text-decoration:underline;">' + data.suggestion + '</button>?</span>';
                        }
                        feedbackEl.innerHTML = html;
                    }
                } catch (e) {
                    feedbackEl.innerHTML = '<span style="color:var(--text-muted);">Format email diterima.</span>';
                }
            }, 350);
        }

        function applyEmailSuggestion(correctedEmail) {
            const emailInput = document.getElementById('contactEmail');
            if (emailInput) {
                emailInput.value = correctedEmail;
                handleEmailInputDebounced(correctedEmail);
            }
        }

        async function handleContactSubmit(e) {
            e.preventDefault();
            hideContactAlert();

            if (isEmailVerifiedActive === false) {
                showContactAlert('⚠️ Alamat email yang Anda masukkan tidak valid atau tidak memiliki server email aktif. Harap gunakan email aktif yang dapat menerima balasan.', true);
                const emailInput = document.getElementById('contactEmail');
                if (emailInput) emailInput.focus();
                return;
            }

            const form = e.target;
            const submitBtn = document.getElementById('btnSubmitContact');
            const originalBtnText = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span>Mengirim Pesan...</span>';

            const formData = new FormData(form);

            try {
                const res = await fetch('<?= base_url('contact/send') ?>', {
                    method: 'POST',
                    headers: { 'Accept': 'application/json' },
                    body: formData
                });
                const data = await res.json();

                if (!res.ok || data.status === 'error') {
                    showContactAlert(data.message || 'Gagal mengirimkan pesan. Silakan periksa kembali formulir Anda.', true);
                    generateMathCaptcha();
                } else {
                    form.reset();
                    showContactAlert('🎉 <strong>Pesan Berhasil Terkirim!</strong><br>' + 
                        (data.message || 'Terima kasih, tim pengembang akan merespons pesan Anda via email dalam 1x24 jam kerja.'), false);
                    setTimeout(() => {
                        generateMathCaptcha();
                    }, 500);
                }
            } catch (err) {
                showContactAlert('Kendala koneksi peladen saat mengirim pesan. Silakan coba kembali.', true);
            } finally {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnText;
            }
        }

        /* --- Legal Document Modal (Privacy Policy & Terms) --- */
        const legalCache = {};

        async function openLegalModal(type, e) {
            if (e) {
                e.preventDefault();
            }

            const modal = document.getElementById('legalModal');
            const titleEl = document.getElementById('legalModalTitle');
            const bodyEl = document.getElementById('legalModalBody');
            const fullLink = document.getElementById('legalFullPageLink');

            const isPrivacy = (type === 'privacy');
            const endpoint = isPrivacy ? '<?= base_url('privacy') ?>' : '<?= base_url('terms') ?>';
            const pageTitle = isPrivacy ? 'Kebijakan Privasi & Kedaulatan Data' : 'Ketentuan Layanan (Terms of Service)';

            if (titleEl) titleEl.textContent = pageTitle;
            if (fullLink) fullLink.href = endpoint;
            modal.style.display = 'flex';

            if (legalCache[type]) {
                if (bodyEl) bodyEl.innerHTML = legalCache[type];
                return;
            }

            if (bodyEl) {
                bodyEl.innerHTML = '<div style="text-align: center; padding: 50px 20px; color: var(--text-muted);">' +
                    '<div style="font-size: 24px; margin-bottom: 8px;">⏳</div>' +
                    '<div>Memuat dokumen ' + pageTitle + '...</div>' +
                    '</div>';
            }

            try {
                const res = await fetch(endpoint, {
                    headers: { 'Accept': 'application/json' }
                });
                const data = await res.json();
                if (data.status === 'success' && data.html) {
                    legalCache[type] = data.html;
                    if (bodyEl) bodyEl.innerHTML = data.html;
                } else {
                    if (bodyEl) bodyEl.innerHTML = '<div class="alert-box alert-error" style="display:block;">Gagal memuat dokumen. Silakan buka melalui <a href="' + endpoint + '" target="_blank" style="color:var(--primary); font-weight:700;">Halaman Penuh</a>.</div>';
                }
            } catch (err) {
                if (bodyEl) bodyEl.innerHTML = '<div class="alert-box alert-error" style="display:block;">Kendala koneksi jaringan saat memuat dokumen. Silakan buka melalui <a href="' + endpoint + '" target="_blank" style="color:var(--primary); font-weight:700;">Halaman Penuh</a>.</div>';
            }
        }

        function closeLegalModal() {
            const modal = document.getElementById('legalModal');
            modal.style.display = 'none';
        }
    </script>

    <!-- AOS (Animate On Scroll) Library & Initialization -->
    <script defer src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        function toggleFaq(btn) {
            const card = btn.closest('.faq-card');
            const isActive = card.classList.contains('active');
            
            // Close other active FAQs for accordion behavior
            document.querySelectorAll('.faq-card').forEach(function(c) {
                c.classList.remove('active');
                const otherBtn = c.querySelector('.faq-btn');
                if (otherBtn) otherBtn.setAttribute('aria-expanded', 'false');
            });
            
            if (!isActive) {
                card.classList.add('active');
                btn.setAttribute('aria-expanded', 'true');
            }
        }

        /* --- Scroll to Top & Fixed Navbar Controls --- */
        function scrollToTop() {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        }

        window.addEventListener('scroll', function() {
            const scrollY = window.pageYOffset || document.documentElement.scrollTop;
            const scrollBtn = document.getElementById('scrollTopBtn');
            const navbar = document.querySelector('.navbar');

            // Toggle Go to Top visibility (> 280px)
            if (scrollBtn) {
                if (scrollY > 280) {
                    scrollBtn.classList.add('visible');
                } else {
                    scrollBtn.classList.remove('visible');
                }
            }

            // Compact shadow on fixed navbar when scrolled
            if (navbar) {
                if (scrollY > 15) {
                    navbar.classList.add('scrolled');
                } else {
                    navbar.classList.remove('scrolled');
                }
            }
        }, { passive: true });

        /* --- Desktop Navigation Dropdown Controls --- */
        function toggleNavDropdown(event) {
            event.stopPropagation();
            const dropdown = event.currentTarget.closest('.nav-item-dropdown');
            if (!dropdown) return;
            const isOpen = dropdown.classList.contains('active');
            closeAllNavDropdowns();
            if (!isOpen) {
                dropdown.classList.add('active');
                event.currentTarget.setAttribute('aria-expanded', 'true');
            }
        }

        function closeAllNavDropdowns() {
            document.querySelectorAll('.nav-item-dropdown').forEach(function(d) {
                d.classList.remove('active');
                const btn = d.querySelector('.nav-dropdown-toggle');
                if (btn) btn.setAttribute('aria-expanded', 'false');
            });
        }

        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.nav-item-dropdown')) {
                closeAllNavDropdowns();
            }
        });

        /* --- Mobile Navigation Drawer Controls --- */
        function toggleMobileNav() {
            const drawer = document.getElementById('mobileNavDrawer');
            if (!drawer) return;
            const isOpen = drawer.classList.contains('active');
            if (isOpen) {
                closeMobileNav();
            } else {
                openMobileNav();
            }
        }

        function openMobileNav() {
            const drawer = document.getElementById('mobileNavDrawer');
            const overlay = document.getElementById('mobileNavOverlay');
            const btn = document.getElementById('hamburgerBtn');
            if (!drawer || !overlay) return;

            drawer.classList.add('active');
            overlay.classList.add('active');
            drawer.setAttribute('aria-hidden', 'false');
            drawer.removeAttribute('inert');
            if (btn) {
                btn.classList.add('active');
                btn.setAttribute('aria-expanded', 'true');
            }
            document.body.style.overflow = 'hidden';
        }

        function closeMobileNav() {
            const drawer = document.getElementById('mobileNavDrawer');
            const overlay = document.getElementById('mobileNavOverlay');
            const btn = document.getElementById('hamburgerBtn');
            if (!drawer || !overlay) return;

            drawer.classList.remove('active');
            overlay.classList.remove('active');
            drawer.setAttribute('aria-hidden', 'true');
            drawer.setAttribute('inert', '');
            if (btn) {
                btn.classList.remove('active');
                btn.setAttribute('aria-expanded', 'false');
            }
            document.body.style.overflow = '';
        }

        // Close mobile drawer and dropdowns on Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeMobileNav();
                closeAllNavDropdowns();
            }
        });

        document.addEventListener('DOMContentLoaded', function() {
            // Auto open modal when redirected from protected route
            const urlParams = new URLSearchParams(window.location.search);
            const authParam = urlParams.get('auth');
            const verifiedParam = urlParams.get('verified');
            const resetParam = urlParams.get('reset');
            const reasonParam = urlParams.get('reason');

            if (verifiedParam === 'error') {
                openAuthModal('login');
                let errText = 'Tautan verifikasi tidak valid atau telah kedaluwarsa.';
                if (reasonParam === 'expired') {
                    const unvEmail = urlParams.get('email') || '';
                    errText = '⏱️ Tautan verifikasi telah kedaluwarsa (berlaku 24 jam).<br>' +
                        '<button type="button" onclick="resendVerification(\'' + unvEmail + '\')" style="background:none; border:none; padding:0; cursor:pointer; font-family:inherit; color:var(--primary); font-weight:700; text-decoration:underline; display:inline-block; margin-top:6px;">Klik di sini untuk mengirimkan tautan aktivasi baru.</button>';
                }
                showAlert(errText, true);
            } else if (verifiedParam === 'success') {
                openAuthModal('login');
                showAlert('🎉 Alamat email Anda telah berhasil diverifikasi! Silakan masuk ke akun Anda.', false);
            } else if (resetParam === 'success') {
                openAuthModal('login');
                showAlert('🎉 Kata sandi Anda berhasil diperbarui! Silakan masuk menggunakan kata sandi baru Anda.', false);
            } else if (resetParam === 'error') {
                openAuthModal('login');
                let errText = 'Tautan atur ulang kata sandi tidak valid atau telah kedaluwarsa.';
                if (reasonParam === 'expired') {
                    errText = '⏱️ Tautan atur ulang kata sandi telah kedaluwarsa (berlaku 1 jam).<br>' +
                        '<button type="button" onclick="switchTab(\'forgot\')" style="background:none; border:none; padding:0; cursor:pointer; font-family:inherit; color:var(--primary); font-weight:700; text-decoration:underline; display:inline-block; margin-top:6px;">Klik di sini untuk mengajukan permintaan baru.</button>';
                }
                showAlert(errText, true);
            } else if (authParam === 'login' || authParam === 'register' || authParam === 'forgot') {
                openAuthModal(authParam);
                <?php $flashNotice = session()->getFlashdata('auth_notice') ?: session()->getFlashdata('error'); ?>
                <?php $flashSuccess = session()->getFlashdata('message'); ?>
                <?php if (!empty($flashNotice)) : ?>
                    showAlert(<?= json_encode($flashNotice) ?>, true);
                <?php elseif (!empty($flashSuccess)) : ?>
                    showAlert(<?= json_encode($flashSuccess) ?>, false);
                <?php endif; ?>
                const paramEmail = urlParams.get('email');
                if (paramEmail) {
                    const regEmailInput = document.querySelector('#formRegister input[name="email"]');
                    const logEmailInput = document.querySelector('#formLogin input[name="email"]');
                    if (regEmailInput) regEmailInput.value = paramEmail;
                    if (logEmailInput) logEmailInput.value = paramEmail;
                }

                const paramRef = urlParams.get('ref');
                if (paramRef) {
                    const regRefInput = document.getElementById('regReferralCode');
                    const badge = document.getElementById('referralBonusBadge');
                    const codeSpan = document.getElementById('displayRefCode');
                    if (regRefInput) regRefInput.value = paramRef.toUpperCase();
                    if (codeSpan) codeSpan.textContent = paramRef.toUpperCase();
                    if (badge) badge.style.display = 'block';
                }
            }

            // Directional scroll tracker for bidirectional AOS (fade-up when scrolling down, fade-down when scrolling up)
            let lastScrollTop = window.pageYOffset || document.documentElement.scrollTop;
            document.body.classList.add('scrolling-down');

            window.addEventListener('scroll', function() {
                const currentScrollTop = window.pageYOffset || document.documentElement.scrollTop;
                if (Math.abs(currentScrollTop - lastScrollTop) > 6) {
                    if (currentScrollTop > lastScrollTop) {
                        document.body.classList.add('scrolling-down');
                        document.body.classList.remove('scrolling-up');
                    } else if (currentScrollTop < lastScrollTop) {
                        document.body.classList.add('scrolling-up');
                        document.body.classList.remove('scrolling-down');
                    }
                    lastScrollTop = currentScrollTop <= 0 ? 0 : currentScrollTop;
                }
            }, { passive: true });

            function initAOS() {
                if (typeof AOS !== 'undefined') {
                    AOS.init({
                        duration: 750,
                        easing: 'ease-out-cubic',
                        once: false,
                        mirror: true,
                        offset: 70
                    });
                }
            }
            if (typeof AOS !== 'undefined') {
                initAOS();
            } else {
                window.addEventListener('load', initAOS);
            }
        });
    </script>
</body>
</html>
