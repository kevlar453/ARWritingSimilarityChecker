<?php
$adsenseClient = trim((string) env('ADSENSE_CLIENT', 'ca-pub-9248253613564140'));
$adsenseAutoAds = filter_var((string) env('ADSENSE_AUTO_ADS', 'false'), FILTER_VALIDATE_BOOLEAN);
$enableAdSense = ! empty($enableAdSense);
$activeAdSlots = $activeAdSlots ?? [];
$hasPageAdSlot = false;
foreach ($activeAdSlots as $sNum) {
    $rawSlotVal = (string) env('ADSENSE_SLOT_' . (int) $sNum, match ((int) $sNum) {
        1 => '4774338502',
        2 => '1341525808',
        3 => '7905712963',
        default => '',
    });
    if (preg_replace('/\D+/', '', $rawSlotVal) !== '') {
        $hasPageAdSlot = true;
        break;
    }
}
$loadAdSenseScript = $enableAdSense && $adsenseClient !== '' && ($hasPageAdSlot || $adsenseAutoAds);

$pageTitle = (string) ($pageTitle ?? 'AR Tilik — Cek Plagiarisme & Penelaah Kesepadanan Naskah Akademik Mandiri');
$pageDescription = (string) ($pageDescription ?? 'AR Tilik adalah instrumen penelaah kesepadanan naskah akademik & cek plagiarisme mandiri berbasis bukti ilmiah.');
$canonicalUrl = (string) ($canonicalUrl ?? base_url());
$pageKeywords = (string) ($pageKeywords ?? 'cek plagiasi, cek plagiarisme, similarity checker indonesia, ar tilik, integritas akademik, ojs 3 similarity plugin');
$ogTitle = (string) ($ogTitle ?? $pageTitle);
$ogDescription = (string) ($ogDescription ?? $pageDescription);
$preloadHeroImage = ! empty($preloadHeroImage);
$legacyHashRedirect = ! empty($legacyHashRedirect);
$cssVersion = @filemtime(FCPATH . 'css/artilik-landing.css') ?: '20261119';

$graphNodes = [
    [
        '@type' => 'WebSite',
        '@id' => base_url('#website'),
        'url' => base_url(),
        'name' => 'AR Tilik',
        'alternateName' => ['ARTilik', 'AR Tilik Similarity Checker'],
        'description' => 'Sistem Penelaah Kesepadanan Naskah Akademik & Cek Plagiarisme Mandiri Berbasis Bukti Ilmiah Terbuka',
        'inLanguage' => 'id-ID',
        'publisher' => ['@id' => base_url('#organization')],
    ],
    [
        '@type' => 'Organization',
        '@id' => base_url('#organization'),
        'name' => 'ARSetontong',
        'url' => 'https://www.arsetontong.top',
        'logo' => [
            '@type' => 'ImageObject',
            'url' => base_url('android-chrome-512x512.png'),
            'width' => 512,
            'height' => 512,
        ],
        'sameAs' => ['https://www.arsetontong.top'],
    ],
    [
        '@type' => 'WebPage',
        '@id' => $canonicalUrl . '#webpage',
        'url' => $canonicalUrl,
        'name' => $pageTitle,
        'description' => $pageDescription,
        'inLanguage' => 'id-ID',
        'isPartOf' => ['@id' => base_url('#website')],
    ],
];
if (! empty($extraJsonLdNodes) && is_array($extraJsonLdNodes)) {
    foreach ($extraJsonLdNodes as $node) {
        $graphNodes[] = $node;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php if ($adsenseClient !== '') : ?>
    <meta name="google-adsense-account" content="<?= esc($adsenseClient, 'attr') ?>">
    <?php endif; ?>
    <title><?= esc($pageTitle) ?></title>

    <!-- Primary SEO Meta Tags -->
    <meta name="description" content="<?= esc($pageDescription, 'attr') ?>">
    <meta name="keywords" content="<?= esc($pageKeywords, 'attr') ?>">
    <meta name="author" content="ARSetontong">
    <meta name="publisher" content="ARSetontong">
    <meta name="robots" content="index, follow, max-snippet:-1, max-image-preview:large, max-video-preview:-1">
    <link rel="canonical" href="<?= esc($canonicalUrl, 'attr') ?>">
    <meta name="theme-color" content="#4338ca">
    <meta name="language" content="Indonesian">
    <meta name="geo.region" content="ID">
    <meta name="geo.placename" content="Indonesia">

    <!-- Open Graph / Social -->
    <meta property="og:type" content="website">
    <meta property="og:locale" content="id_ID">
    <meta property="og:site_name" content="AR Tilik">
    <meta property="og:title" content="<?= esc($ogTitle, 'attr') ?>">
    <meta property="og:description" content="<?= esc($ogDescription, 'attr') ?>">
    <meta property="og:url" content="<?= esc($canonicalUrl, 'attr') ?>">
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
    <meta name="twitter:title" content="<?= esc($ogTitle, 'attr') ?>">
    <meta name="twitter:description" content="<?= esc($ogDescription, 'attr') ?>">
    <meta name="twitter:image" content="<?= base_url('images/og-artilik.png') ?>">

    <!-- Schema.org JSON-LD Structured Data -->
    <script type="application/ld+json">
    <?= json_encode(['@context' => 'https://schema.org', '@graph' => $graphNodes], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) ?>
    </script>

    <?php if ($legacyHashRedirect) : ?>
    <!-- Pengalihan otomatis tautan jangkar lama (/#...) ke halaman khusus baru -->
    <script>
        (function() {
            var h = (window.location.hash || '').toLowerCase();
            if (!h || h === '#' || h === '#main-content' || h === '#auth-section') return;
            var targets = {
                '#faq': <?= json_encode(base_url('faq')) ?>,
                '#integrasi-ojs': <?= json_encode(base_url('ojs')) ?>,
                '#langganan': <?= json_encode(base_url('harga')) ?>,
                '#pricing': <?= json_encode(base_url('harga')) ?>,
                '#solusi': <?= json_encode(base_url('harga#solusi')) ?>,
                '#teori': <?= json_encode(base_url('fitur#teori')) ?>,
                '#riset-komparasi': <?= json_encode(base_url('fitur#riset-komparasi')) ?>,
                '#fitur-unggulan': <?= json_encode(base_url('fitur#fitur-unggulan')) ?>,
                '#cara-kerja': <?= json_encode(base_url('fitur#cara-kerja')) ?>,
                '#dual-reader': <?= json_encode(base_url('fitur#dual-reader')) ?>,
                '#kuota-korpus': <?= json_encode(base_url('tentang#kuota-korpus')) ?>,
                '#testimoni': <?= json_encode(base_url('tentang#testimoni')) ?>,
                '#duta-kampus-cta': <?= json_encode(base_url('tentang#duta-kampus-cta')) ?>
            };
            if (targets[h]) {
                window.location.replace(targets[h]);
            }
        })();
    </script>
    <?php endif; ?>

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

    <?php if ($preloadHeroImage) : ?>
    <!-- Preload Hero LCP Candidate Image -->
    <link rel="preload" as="image" href="<?= base_url('images/hero_illustration.webp') ?>" type="image/webp" fetchpriority="high">
    <?php endif; ?>

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

    <?php if ($loadAdSenseScript) : ?>
    <!-- Google AdSense: Dimuat satu kali pada halaman konten (/fitur & /faq), tersembunyi bila slot kosong -->
    <script>
        (function() {
            var adsLoaded = false;
            var autoAdsEnabled = <?= $adsenseAutoAds ? 'true' : 'false' ?>;
            var adClient = <?= json_encode($adsenseClient) ?>;

            function collapseSlot(wrapper) {
                if (!wrapper) return;
                wrapper.hidden = true;
                wrapper.style.display = 'none';
            }

            function markSlotFilled(wrapper) {
                if (!wrapper) return;
                wrapper.classList.add('is-filled');
                wrapper.hidden = false;
                wrapper.style.display = '';
            }

            function syncSlotStatus(el) {
                var wrapper = el.closest('[data-ad-slot-wrapper]');
                if (!wrapper) return;
                var status = el.getAttribute('data-ad-status');
                var initSt = el.getAttribute('data-adsbygoogle-status');
                var rect = el.getBoundingClientRect();
                var hasVisibleBox = Math.max(el.offsetHeight || 0, Math.round(rect.height || 0)) >= 24;

                if (status === 'unfilled') {
                    collapseSlot(wrapper);
                } else if (status === 'filled' || (hasVisibleBox && (status === 'unfill-optimized' || initSt === 'done'))) {
                    markSlotFilled(wrapper);
                }
            }

            function initAdSense() {
                if (adsLoaded) return;
                adsLoaded = true;
                ['scroll', 'mousemove', 'touchstart', 'click', 'keydown'].forEach(function(evt) {
                    window.removeEventListener(evt, initAdSense, { passive: true });
                });

                var slotEls = document.querySelectorAll('.artilik-ad-slot-body .adsbygoogle');
                slotEls.forEach(function(el) {
                    syncSlotStatus(el);
                    if ('MutationObserver' in window) {
                        var obs = new MutationObserver(function() { syncSlotStatus(el); });
                        obs.observe(el, {
                            attributes: true,
                            attributeFilter: ['data-ad-status', 'data-adsbygoogle-status', 'style'],
                            childList: true
                        });
                    }
                    if ('ResizeObserver' in window) {
                        var rObs = new ResizeObserver(function() { syncSlotStatus(el); });
                        rObs.observe(el);
                    }
                });

                var adScript = document.createElement('script');
                adScript.src = autoAdsEnabled
                    ? ('https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=' + encodeURIComponent(adClient))
                    : 'https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js';
                adScript.async = true;
                adScript.crossOrigin = 'anonymous';
                adScript.onload = function() {
                    try {
                        slotEls.forEach(function(el) {
                            if (!el.getAttribute('data-adsbygoogle-status')) {
                                (window.adsbygoogle = window.adsbygoogle || []).push({});
                            }
                        });
                        setTimeout(function() {
                            slotEls.forEach(function(el) {
                                var st = el.getAttribute('data-ad-status');
                                var initSt = el.getAttribute('data-adsbygoogle-status');
                                var rect = el.getBoundingClientRect();
                                var hasVisibleBox = Math.max(el.offsetHeight || 0, Math.round(rect.height || 0)) >= 24;
                                if (hasVisibleBox && st !== 'unfilled') {
                                    markSlotFilled(el.closest('[data-ad-slot-wrapper]'));
                                } else if (st === 'unfilled' || !initSt) {
                                    collapseSlot(el.closest('[data-ad-slot-wrapper]'));
                                }
                            });
                        }, 10000);
                    } catch (e) {
                        slotEls.forEach(function(el) {
                            collapseSlot(el.closest('[data-ad-slot-wrapper]'));
                        });
                    }
                };
                adScript.onerror = function() {
                    slotEls.forEach(function(el) {
                        collapseSlot(el.closest('[data-ad-slot-wrapper]'));
                    });
                };
                document.head.appendChild(adScript);
            }

            ['scroll', 'mousemove', 'touchstart', 'click', 'keydown'].forEach(function(evt) {
                window.addEventListener(evt, initAdSense, { once: true, passive: true });
            });
            if ('requestIdleCallback' in window) {
                requestIdleCallback(function() { setTimeout(initAdSense, 4000); });
            } else {
                setTimeout(initAdSense, 4000);
            }
        })();
    </script>
    <?php endif; ?>

    <link rel="stylesheet" href="<?= base_url('css/artilik-landing.css?v=' . $cssVersion) ?>">
</head>
<body>
<a href="#main-content" class="skip-link">Loncat ke konten utama</a>
