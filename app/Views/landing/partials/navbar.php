<?php
$activeNav = (string) ($activeNav ?? 'beranda');
$currentUser = $currentUser ?? null;
$useHrefAuth = ! empty($useHrefAuth);

$primaryNavItems = [
    ['id' => 'beranda',    'label' => 'Beranda',           'icon' => '🏠', 'href' => base_url(),            'extraClass' => ''],
    ['id' => 'fitur',      'label' => 'Fitur',             'icon' => '⚡', 'href' => base_url('fitur'),     'extraClass' => ''],
    ['id' => 'ojs',        'label' => 'OJS',               'icon' => '🔌', 'href' => base_url('ojs'),       'extraClass' => ''],
    ['id' => 'harga',      'label' => 'Harga',             'icon' => '💳', 'href' => base_url('harga'),     'extraClass' => ''],
    ['id' => 'cek-sitasi', 'label' => 'Cek Sitasi Gratis', 'icon' => '🔎', 'href' => base_url('cek-sitasi'),'extraClass' => 'nav-link-cek'],
    ['id' => 'tentang',    'label' => 'Tentang',           'icon' => '🏛️', 'href' => base_url('tentang'),   'extraClass' => ''],
    ['id' => 'faq',        'label' => 'FAQ',               'icon' => '❓', 'href' => base_url('faq'),       'extraClass' => ''],
];

$registerHref = ! empty($registerCtaHref) ? (string) $registerCtaHref : base_url('/?auth=register');
$loginHref = base_url('/?auth=login');
?>
    <!-- Header Navigation -->
    <header class="navbar google-anno-skip">
        <div class="container nav-inner">
            <a href="<?= base_url() ?>" class="brand" data-google-vignette="false">
                <img src="<?= base_url('images/logo.png') ?>" alt="AR Tilik Logo" class="brand-logo" width="35" height="35">
                <div class="brand-text">
                    <span class="brand-title">AR Tilik</span>
                    <span class="brand-badge">v1.3.0</span>
                </div>
            </a>

            <nav aria-label="Navigasi Utama">
                <ul class="nav-links">
                    <?php foreach ($primaryNavItems as $item) :
                        $isActive = ($activeNav === $item['id']);
                    ?>
                        <li>
                            <a href="<?= $item['href'] ?>"
                               class="<?= trim($item['extraClass']) ?>"
                               <?= $isActive ? 'aria-current="page"' : '' ?>
                               data-google-vignette="false">
                                <?= esc($item['label']) ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </nav>

            <div class="nav-actions">
                <?php if ($currentUser) : ?>
                    <a href="<?= base_url('dashboard') ?>" class="btn btn-primary btn-sm btn-nav-primary-cta" data-google-vignette="false">
                        <span class="btn-cta-icon" aria-hidden="true">📊</span> Dashboard
                    </a>
                    <a href="<?= base_url('auth/logout') ?>" class="btn btn-secondary btn-sm btn-desktop-only" title="Keluar" data-google-vignette="false">
                        Keluar
                    </a>
                <?php elseif ($useHrefAuth) : ?>
                    <a href="<?= $loginHref ?>" class="btn btn-outline btn-sm btn-desktop-only" data-google-vignette="false">
                        Masuk
                    </a>
                    <a href="<?= $registerHref ?>" class="btn btn-primary btn-sm btn-nav-primary-cta" data-google-vignette="false">
                        Mulai Gratis
                    </a>
                <?php else : ?>
                    <button type="button" onclick="openAuthModal('login')" class="btn btn-outline btn-sm btn-desktop-only" data-google-vignette="false">
                        Masuk
                    </button>
                    <button type="button" onclick="openAuthModal('register')" class="btn btn-primary btn-sm btn-nav-primary-cta" data-google-vignette="false">
                        Mulai Gratis
                    </button>
                <?php endif; ?>

                <!-- Mobile Hamburger Toggle Button -->
                <button type="button" id="hamburgerBtn" class="hamburger-btn" onclick="toggleMobileNav()" aria-label="Buka Menu Navigasi" aria-expanded="false" aria-controls="mobileNavDrawer">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>
            </div>
        </div>
    </header>

    <!-- Mobile Navigation Drawer Overlay -->
    <div id="mobileNavOverlay" class="mobile-nav-overlay" onclick="closeMobileNav()"></div>

    <!-- Mobile Navigation Drawer (susunan utama identik dengan navigasi desktop) -->
    <div id="mobileNavDrawer" class="mobile-nav-drawer google-anno-skip" role="dialog" aria-modal="true" aria-label="Menu Navigasi Seluler" aria-hidden="true" inert>
        <div class="mobile-drawer-header">
            <a href="<?= base_url() ?>" class="brand" onclick="closeMobileNav()" data-google-vignette="false">
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
                        <a href="<?= base_url('dashboard') ?>" class="btn btn-primary btn-sm" style="flex: 1; justify-content: center;" onclick="closeMobileNav()" data-google-vignette="false">
                            <span>📊</span> Dashboard
                        </a>
                        <a href="<?= base_url('auth/logout') ?>" class="btn btn-secondary btn-sm" style="padding: 6px 12px;" data-google-vignette="false">
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
                        <?php if ($useHrefAuth) : ?>
                            <a href="<?= $registerHref ?>" class="btn btn-primary" style="width: 100%; justify-content: center; padding: 11px 14px; font-size: 13.5px;" onclick="closeMobileNav()" data-google-vignette="false">
                                Mulai Gratis
                            </a>
                            <a href="<?= $loginHref ?>" class="btn btn-outline" style="width: 100%; justify-content: center; padding: 10px 14px; font-size: 13px;" onclick="closeMobileNav()" data-google-vignette="false">
                                Masuk ke Akun
                            </a>
                        <?php else : ?>
                            <button type="button" onclick="closeMobileNav(); openAuthModal('register');" class="btn btn-primary" style="width: 100%; justify-content: center; padding: 11px 14px; font-size: 13.5px;" data-google-vignette="false">
                                Mulai Gratis
                            </button>
                            <button type="button" onclick="closeMobileNav(); openAuthModal('login');" class="btn btn-outline" style="width: 100%; justify-content: center; padding: 10px 14px; font-size: 13px;" data-google-vignette="false">
                                Masuk ke Akun
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Mobile Navigation Links (Urutan Utama Sama dengan Desktop) -->
        <div class="mobile-drawer-body">
            <div class="mobile-nav-group-title">Navigasi Utama</div>
            <nav class="mobile-nav-menu" aria-label="Menu Utama Seluler">
                <?php foreach ($primaryNavItems as $item) :
                    $isActive = ($activeNav === $item['id']);
                ?>
                    <a href="<?= $item['href'] ?>"
                       class="mobile-nav-link <?= $item['id'] === 'cek-sitasi' ? 'highlight' : '' ?>"
                       <?= $isActive ? 'aria-current="page"' : '' ?>
                       onclick="closeMobileNav()"
                       data-google-vignette="false">
                        <span class="nav-icon" aria-hidden="true"><?= $item['icon'] ?></span>
                        <span><?= esc($item['label']) ?></span>
                        <span class="nav-arrow" aria-hidden="true">›</span>
                    </a>
                <?php endforeach; ?>
            </nav>

            <div class="mobile-nav-group-title" style="margin-top: 20px;">Ekosistem &amp; Riset</div>
            <nav class="mobile-nav-menu" aria-label="Tautan Ekosistem">
                <a href="<?= base_url('benchmark') ?>" class="mobile-nav-link" onclick="closeMobileNav()" data-google-vignette="false">
                    <span class="nav-icon" aria-hidden="true">🔬</span>
                    <span>Benchmark Terbuka Indonesia</span>
                    <span class="nav-arrow" aria-hidden="true">›</span>
                </a>
                <a href="<?= base_url('verify') ?>" class="mobile-nav-link" onclick="closeMobileNav()" data-google-vignette="false">
                    <span class="nav-icon" aria-hidden="true">🛡️</span>
                    <span>Verifikasi Sertifikat QR</span>
                    <span class="nav-arrow" aria-hidden="true">›</span>
                </a>
                <a href="<?= base_url('ambassador') ?>" class="mobile-nav-link" onclick="closeMobileNav()" style="background: rgba(245, 158, 11, 0.08); border-left: 3px solid #f59e0b;" data-google-vignette="false">
                    <span class="nav-icon" aria-hidden="true">🎓</span>
                    <span>Program Duta Kampus</span>
                    <span class="nav-badge" style="background: rgba(245, 158, 11, 0.2); color: #d97706; font-weight: 800;">Komisi 20%</span>
                </a>
                <a href="<?= base_url('riset/ar-tilik-vs-turnitin') ?>" class="mobile-nav-link" onclick="closeMobileNav()" data-google-vignette="false">
                    <span class="nav-icon" aria-hidden="true">⚖️</span>
                    <span>Whitepaper: AR Tilik vs Turnitin</span>
                    <span class="nav-arrow" aria-hidden="true">›</span>
                </a>
                <a href="<?= base_url('riset/ar-tilik-vs-zerogpt') ?>" class="mobile-nav-link" onclick="closeMobileNav()" data-google-vignette="false">
                    <span class="nav-icon" aria-hidden="true">📊</span>
                    <span>Whitepaper: AR Tilik vs ZeroGPT</span>
                    <span class="nav-arrow" aria-hidden="true">›</span>
                </a>
                <?php if ($useHrefAuth) : ?>
                    <a href="https://wa.me/6285138862032" target="_blank" rel="noopener noreferrer" class="mobile-nav-link" onclick="closeMobileNav()" data-google-vignette="false">
                        <span class="nav-icon" aria-hidden="true">💬</span>
                        <span>Hubungi via WhatsApp</span>
                        <span class="nav-arrow" aria-hidden="true">›</span>
                    </a>
                <?php else : ?>
                    <button type="button" onclick="closeMobileNav(); openContactModal('Pertanyaan Umum');" class="mobile-nav-link" style="width: 100%; border: none; background: transparent; text-align: left; cursor: pointer; font: inherit;" data-google-vignette="false">
                        <span class="nav-icon" aria-hidden="true">💬</span>
                        <span>Hubungi Tim Pengembang</span>
                        <span class="nav-arrow" aria-hidden="true">›</span>
                    </button>
                <?php endif; ?>
            </nav>
        </div>

        <!-- Mobile Drawer Footer -->
        <div class="mobile-drawer-footer">
            <div class="mobile-footer-links">
                <a href="<?= base_url('privacy') ?>" <?= $useHrefAuth ? '' : 'onclick="closeMobileNav(); openLegalModal(\'privacy\', event)"' ?> data-google-vignette="false">Privasi</a>
                <span>&bull;</span>
                <a href="<?= base_url('terms') ?>" <?= $useHrefAuth ? '' : 'onclick="closeMobileNav(); openLegalModal(\'terms\', event)"' ?> data-google-vignette="false">Ketentuan</a>
                <span>&bull;</span>
                <a href="<?= base_url('sitemap.xml') ?>" target="_blank" data-google-vignette="false">Sitemap</a>
            </div>
            <div class="mobile-footer-brand">
                AR Tilik &copy; <?= date('Y') ?> &bull; <a href="https://www.arsetontong.top" target="_blank" rel="noopener noreferrer">Powered by ARSetontong</a>
            </div>
        </div>
    </div>
