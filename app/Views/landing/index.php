<?php
$currentUser = $currentUser ?? null;
$totalCorpus = (int) ($totalCorpus ?? 0);
$totalReports = (int) ($totalReports ?? 0);
$visitorStats = $visitorStats ?? ['total_unique' => 1, 'today_unique' => 1, 'total_hits' => 1];
$approvedTestimonials = $approvedTestimonials ?? [];
$testimonialStats = $testimonialStats ?? ['avg_rating' => 5.0, 'total_reviews' => 0];

// Siapkan 1-2 testimoni unggulan teratas untuk beranda ringkas
$featuredTestimonials = array_values(array_filter(
    $approvedTestimonials,
    static fn ($item) => ! empty($item['is_featured'])
));
$homeTestimonials = count($featuredTestimonials) >= 2
    ? array_slice($featuredTestimonials, 0, 2)
    : array_slice($approvedTestimonials, 0, 2);

$softwareAppNode = [
    '@type' => 'WebApplication',
    '@id' => base_url('#software'),
    'name' => 'AR Tilik',
    'applicationCategory' => 'EducationalApplication',
    'operatingSystem' => 'All modern web browsers',
    'url' => base_url(),
    'offers' => [
        '@type' => 'Offer',
        'price' => '0',
        'priceCurrency' => 'IDR',
        'availability' => 'https://schema.org/InStock',
        'name' => 'Kuota Sivitas Akademika Gratis (3 Submisi/Minggu)',
    ],
    'featureList' => [
        'Tiga Lensa: 5-Gram, TF-IDF & Semantik Engine',
        'Citation Context Awareness (APA, IEEE, Harvard)',
        'Open Journal Systems (OJS 3.x) Automatic REST Integration & Webhook',
        'Anti-Cheat Homoglyph & Font Spoofing Shield',
        'Interactive Dual-Reader Side-by-Side Comparison',
        'Cryptographic QR Code Verification Certificate',
    ],
    'author' => ['@id' => base_url('#organization')],
];
if (! empty($approvedTestimonials) && (int) ($testimonialStats['total_reviews'] ?? count($approvedTestimonials)) > 0) {
    $softwareAppNode['aggregateRating'] = [
        '@type' => 'AggregateRating',
        'ratingValue' => number_format($testimonialStats['avg_rating'] ?? 5.0, 1),
        'ratingCount' => (string) max(1, (int) ($testimonialStats['total_reviews'] ?? count($approvedTestimonials))),
        'reviewCount' => (string) max(1, (int) ($testimonialStats['total_reviews'] ?? count($approvedTestimonials))),
        'bestRating' => '5',
        'worstRating' => '1',
    ];
}
?>
<?= view('landing/partials/head', [
    'pageTitle'          => 'AR Tilik — Cek Plagiarisme & Penelaah Kesepadanan Naskah Akademik Mandiri',
    'pageDescription'    => 'AR Tilik adalah instrumen penelaah kesepadanan naskah akademik & cek plagiarisme mandiri berbasis bukti ilmiah terbuka, Tiga Lensa 5-Gram/TF-IDF/Semantik, integrasi OJS 3.x, serta alat gratis cek sitasi Crossref & OpenAlex.',
    'canonicalUrl'       => base_url(),
    'enableAdSense'      => false,
    'preloadHeroImage'   => true,
    'legacyHashRedirect' => true,
    'extraJsonLdNodes'   => [$softwareAppNode],
]) ?>

<?= view('landing/partials/navbar', [
    'activeNav'   => 'beranda',
    'currentUser' => $currentUser,
]) ?>

    <main id="main-content">

    <!-- 1. Hero Section -->
    <section class="hero google-anno-skip">
        <div class="container">
            <div class="hero-grid">
                <div>
                    <div class="hero-badge">
                        <span>🏛️</span> Standar Keadilan Akademik &bull; OJS 3.x Ready &bull; Lintas Bahasa 5 Pasangan (EN, ZH, DE, NL, AR ⇄ ID) &bull; Anti-Cheat Shield
                    </div>
                    <h1 class="hero-title">
                        Cek Plagiarisme &amp; Penelaah Kesepadanan Naskah Berbasis <span>Bukti Ilmiah Terbuka</span>
                    </h1>
                    <p class="hero-subtitle">
                        AR Tilik menghadirkan dekomposisi bukti kemiripan transparan, audit keaslian sitasi, perbandingan revisi bab, dan integrasi otomatis jurnal OJS 3.x &mdash; tanpa mengunci karya mahasiswa ke repositori publik.
                    </p>

                    <div class="hero-buttons">
                        <?php if ($currentUser) : ?>
                            <a href="<?= base_url('dashboard') ?>" class="btn btn-primary" data-google-vignette="false">
                                <span>🚀</span> Buka Dashboard Pemeriksaan
                            </a>
                        <?php else : ?>
                            <button type="button" onclick="openAuthModal('register')" class="btn btn-primary" data-google-vignette="false">
                                <span>✨</span> Mulai Gratis (3 Cek/Minggu)
                            </button>
                        <?php endif; ?>
                        <a href="<?= base_url('cek-sitasi') ?>" class="btn btn-outline" style="border-color: #0284c7; color: #0369a1; background: #f0f9ff;" data-google-vignette="false">
                            <span>🔎</span> Cek Sitasi Gratis
                        </a>
                        <a href="<?= base_url('fitur') ?>" class="btn btn-outline" data-google-vignette="false">
                            <span>⚡</span> Lihat Fitur &amp; Tiga Lensa
                        </a>
                    </div>

                    <!-- Micro Feature Badges -->
                    <div class="hero-pills-wrap">
                        <div class="hero-pill-badge"><span>⚡</span> Tiga Lensa: 5-Gram, TF-IDF &amp; Semantik</div>
                        <div class="hero-pill-badge"><span>📑</span> Audit Sitasi &amp; Revisi v1 vs v2</div>
                        <div class="hero-pill-badge"><span>🛡️</span> Anti-Cheat Font &amp; Homoglyph</div>
                        <div class="hero-pill-badge"><span>🔌</span> Plugin Resmi OJS 3.x + Webhook</div>
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
                                <span>🔬</span> Mesin Tiga Lensa Aktif
                            </span>
                            <span class="hero-visual-status">
                                Exact, Leksikal &amp; Semantik
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 2. Statistik Singkat -->
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

    <!-- 3. Tiga Lensa — Versi Ringkas (3 Kartu) -->
    <section class="section">
        <div class="container">
            <div class="section-header" data-aos="fade-up" data-aos-duration="700">
                <div class="section-tag">Mengapa AR Tilik Berbeda</div>
                <h2 class="section-title">Bukti Terukur, Bukan Vonis Satu Angka</h2>
                <p class="section-desc">
                    Alih-alih memvonis naskah lewat persentase mentah atau tebakan detektor AI hitam-putih, AR Tilik membedah bukti keabsahan lewat tiga pilar utama:
                </p>
            </div>

            <div class="grid-3">
                <div class="theory-card" data-aos="fade-up" data-aos-delay="100" data-aos-duration="700">
                    <div class="card-icon-badge badge-indigo">🔍</div>
                    <h3 class="theory-title">Arsitektur Tiga Lensa</h3>
                    <p class="theory-desc">
                        Menggabungkan <strong>Exact 5-Gram</strong> (salin-tempel), <strong>TF-IDF Leksikal</strong> (kesamaan istilah), dan <strong>Lensa Semantik MiniLM-L12</strong> (parafrase &amp; terjemahan 5 pasangan bahasa).
                    </p>
                </div>

                <div class="theory-card" data-aos="fade-up" data-aos-delay="180" data-aos-duration="700">
                    <div class="card-icon-badge badge-emerald">📚</div>
                    <h3 class="theory-title">Sadar Konteks Sitasi &amp; Revisi</h3>
                    <p class="theory-desc">
                        Mengenali kutipan bertanda petik dan daftar pustaka APA/IEEE, mengecek keberadaan referensi via <strong>Crossref &amp; OpenAlex</strong>, serta membandingkan kemajuan revisi <strong>v1 vs v2</strong>.
                    </p>
                </div>

                <div class="theory-card" data-aos="fade-up" data-aos-delay="260" data-aos-duration="700">
                    <div class="card-icon-badge badge-amber">⚖️</div>
                    <h3 class="theory-title">Prinsip Tiga Lapis &amp; Non-Repositori</h3>
                    <p class="theory-desc">
                        Naskah pra-sidang Anda tetap privat (tidak disimpan ke korpus umum), disajikan di <strong>Layar Ganda (Dual-Reader)</strong> dan sertifikat ber-QR SHA-256 yang transparan.
                    </p>
                </div>
            </div>

            <div class="section-cta-center">
                <a href="<?= base_url('fitur') ?>" class="btn btn-primary" data-google-vignette="false">
                    Pelajari Arsitektur Tiga Lensa &amp; 6 Inovasi Mesin &rarr;
                </a>
                <a href="<?= base_url('benchmark') ?>" class="btn btn-outline" data-google-vignette="false">
                    🔬 Lihat Benchmark Terbuka
                </a>
            </div>
        </div>
    </section>

    <!-- 4. Cara Kerja — 3 Langkah -->
    <section class="section" style="background: #ffffff; border-top: 1px solid var(--border); border-bottom: 1px solid var(--border);">
        <div class="container">
            <div class="section-header" data-aos="fade-up" data-aos-duration="700">
                <div class="section-tag">Alur Cepat 3 Langkah</div>
                <h2 class="section-title">Dari Unggah Draf hingga Sertifikat Ber-QR</h2>
                <p class="section-desc">
                    Selesaikan pemeriksaan kemiripan dan audit daftar pustaka secara mandiri tanpa antre dan tanpa biaya langganan wajib:
                </p>
            </div>

            <div class="grid-3">
                <div class="step-card" data-aos="fade-up" data-aos-delay="100" data-aos-duration="700">
                    <div class="step-index">01</div>
                    <div class="card-icon-badge badge-indigo">📤</div>
                    <h3 class="step-title">Daftar Gratis &amp; Unggah Naskah</h3>
                    <p class="step-desc">
                        Setiap akun langsung mendapat <strong>3 cek gratis per minggu</strong>. Kirim PDF, DOCX, TXT, atau LaTeX (`.tex`) hingga 64 MB tanpa khawatir masuk repositori umum.
                    </p>
                </div>

                <div class="step-card" data-aos="fade-up" data-aos-delay="200" data-aos-duration="700">
                    <div class="step-index">02</div>
                    <div class="card-icon-badge badge-cyan">⚡</div>
                    <h3 class="step-title">Pemrosesan Tiga Lensa</h3>
                    <p class="step-desc">
                        Mesin bekerja asinkron memeriksa korpus kampus, GARUDA Kemdiktisaintek, OpenAlex, pelacak font manipulatif, serta kesesuaian kutipan dan daftar pustaka.
                    </p>
                </div>

                <div class="step-card" data-aos="fade-up" data-aos-delay="300" data-aos-duration="700">
                    <div class="step-index">03</div>
                    <div class="card-icon-badge badge-amber">🛡️</div>
                    <h3 class="step-title">Telaah Dual-Reader &amp; Sertifikat</h3>
                    <p class="step-desc">
                        Telusuri sumber pembanding berdampingan, nyalakan filter kutipan yang sama persis dengan ekspor PDF, lalu verifikasi laporan lewat kode QR SHA-256.
                    </p>
                </div>
            </div>

            <div class="section-cta-center">
                <a href="<?= base_url('fitur#dual-reader') ?>" class="btn btn-outline" data-google-vignette="false">
                    Lihat Pratinjau Layar Ganda (Dual-Reader) &rarr;
                </a>
                <a href="<?= base_url('cek-sitasi') ?>" class="btn btn-outline" style="border-color: #0284c7; color: #0369a1;" data-google-vignette="false">
                    🔎 Uji Cepat Daftar Pustaka Tanpa Login
                </a>
            </div>
        </div>
    </section>

    <!-- 5. Teaser OJS — 1 Kartu + Tombol ke /ojs -->
    <section class="section">
        <div class="container">
            <div class="home-ojs-teaser-card" data-aos="zoom-in" data-aos-duration="750">
                <div>
                    <div style="display: inline-flex; align-items: center; gap: 8px; padding: 4px 12px; background: rgba(244, 63, 94, 0.22); border: 1px solid rgba(244, 63, 94, 0.45); border-radius: 9999px; font-size: 11px; font-weight: 800; color: #fecdd3; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 14px;">
                        <span>🔌</span> Ekosistem Jurnal Ilmiah SINTA &amp; ARJUNA
                    </div>
                    <h2 style="font-size: clamp(23px, 2.8vw, 31px); font-weight: 800; color: #ffffff; line-height: 1.25; margin-bottom: 12px;">
                        Otomasi Pemeriksaan Naskah di OJS 3.x Tanpa Mengubah Core
                    </h2>
                    <p style="font-size: 14.5px; color: #cbd5e1; line-height: 1.65; margin-bottom: 22px;">
                        Pasang paket plugin resmi AR Tilik di OJS 3.x: setiap submisi penulis diperiksa otomatis di latar belakang, lalu skor, putusan, kode verifikasi, dan tautan laporan langsung terkirim ke meja editor melalui webhook terverifikasi HMAC-SHA256 &mdash; tanpa polling.
                    </p>
                    <div style="display: flex; flex-wrap: wrap; gap: 12px;">
                        <a href="<?= base_url('ojs') ?>" class="btn" style="background: linear-gradient(135deg, #e11d48 0%, #be123c 100%); color: #ffffff; box-shadow: 0 6px 18px rgba(225, 29, 72, 0.4);" data-google-vignette="false">
                            Pelajari Integrasi OJS &rarr;
                        </a>
                        <a href="<?= base_url('integrations/ojs/artilik-ojs-plugin.zip') ?>" download="artilik-ojs-plugin.zip" class="btn" style="background: rgba(255, 255, 255, 0.12); color: #ffffff; border: 1px solid rgba(255, 255, 255, 0.28);" data-google-vignette="false">
                            <span>⬇️</span> Unduh Plugin OJS 3 (.zip)
                        </a>
                    </div>
                </div>

                <div class="home-ojs-metrics">
                    <div class="home-ojs-metric-box">
                        <div style="font-size: 11px; color: #94a3b8; font-weight: 700; text-transform: uppercase;">Paket Resmi</div>
                        <div style="font-size: 18px; font-weight: 800; color: #fecdd3; margin: 4px 0;">Plugin ZIP</div>
                        <div style="font-size: 12px; color: #cbd5e1;">Siap pasang di OJS 3.x + Panduan PDF 8 halaman</div>
                    </div>
                    <div class="home-ojs-metric-box">
                        <div style="font-size: 11px; color: #94a3b8; font-weight: 700; text-transform: uppercase;">Pengiriman Hasil</div>
                        <div style="font-size: 18px; font-weight: 800; color: #34d399; margin: 4px 0;">Webhook HMAC</div>
                        <div style="font-size: 12px; color: #cbd5e1;">Signed SHA-256 &amp; anti-SSRF langsung ke diskusi OJS</div>
                    </div>
                    <div class="home-ojs-metric-box">
                        <div style="font-size: 11px; color: #94a3b8; font-weight: 700; text-transform: uppercase;">Kendali Beban</div>
                        <div style="font-size: 18px; font-weight: 800; color: #38bdf8; margin: 4px 0;">60 Req / Menit</div>
                        <div style="font-size: 12px; color: #cbd5e1;">Perlindungan per API key agar kuota jurnal aman</div>
                    </div>
                    <div class="home-ojs-metric-box">
                        <div style="font-size: 11px; color: #94a3b8; font-weight: 700; text-transform: uppercase;">Bukti Editorial</div>
                        <div style="font-size: 18px; font-weight: 800; color: #fbbf24; margin: 4px 0;">Sertifikat QR</div>
                        <div style="font-size: 12px; color: #cbd5e1;">Tautan verifikasi publik pendukung akreditasi SINTA</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- 6. Harga Ringkas -->
    <section class="section" style="background: #ffffff; border-top: 1px solid var(--border); border-bottom: 1px solid var(--border);">
        <div class="container">
            <div class="section-header" data-aos="fade-up" data-aos-duration="700">
                <div class="section-tag">Tarif Adil &amp; Transparan</div>
                <h2 class="section-title">Mulai Gratis, Tambah Sesuai Kebutuhan</h2>
                <p class="section-desc">
                    Ringkasan pilihan akses untuk mahasiswa, dosen pembimbing, pengelola jurnal OJS, hingga server institusi kampus:
                </p>
            </div>

            <div class="home-pricing-grid">
                <div class="home-pricing-card">
                    <div>
                        <span class="plan-pill plan-pill-free">Gratis</span>
                        <div class="plan-name">Civitas Akademika</div>
                        <div class="plan-price"><span class="amount">Rp 0</span><span class="plan-period">/ 3 cek per minggu</span></div>
                        <p style="font-size: 13px; color: var(--text-muted); line-height: 1.55;">
                            Reset otomatis tiap Senin, bonus Gotong Royong (+1 cek per 3 korpus valid), Dual-Reader &amp; Sertifikat QR.
                        </p>
                    </div>
                    <?php if ($currentUser) : ?>
                        <a href="<?= base_url('dashboard') ?>" class="btn btn-outline btn-sm" style="width: 100%;" data-google-vignette="false">Gunakan Kuota</a>
                    <?php else : ?>
                        <button type="button" onclick="openAuthModal('register')" class="btn btn-outline btn-sm" style="width: 100%;" data-google-vignette="false">Mulai Gratis</button>
                    <?php endif; ?>
                </div>

                <div class="home-pricing-card">
                    <div>
                        <span class="plan-pill plan-pill-payg">Pay-As-You-Go</span>
                        <div class="plan-name">Top-Up Kuota</div>
                        <div class="plan-price"><span class="currency">Rp</span><span class="amount">10.000</span><span class="plan-period">/ 3 cek</span></div>
                        <p style="font-size: 13px; color: var(--text-muted); line-height: 1.55;">
                            Kuota aktif selamanya tanpa masa kedaluwarsa. Opsi hemat <strong>Rp 25.000 / 10 cek</strong> via QRIS &amp; VA.
                        </p>
                    </div>
                    <a href="<?= base_url('harga') ?>" class="btn btn-outline btn-sm" style="width: 100%;" data-google-vignette="false">Detail Top-Up</a>
                </div>

                <div class="home-pricing-card highlight">
                    <div>
                        <span class="plan-pill plan-pill-popular">⭐ Paling Diminati</span>
                        <div class="plan-name">Pejuang Skripsi</div>
                        <div class="plan-price"><span class="currency">Rp</span><span class="amount">25.000</span><span class="plan-period">/ bulan</span></div>
                        <p style="font-size: 13px; color: var(--text-muted); line-height: 1.55;">
                            <strong>20 submisi/bulan</strong> (Rp 1.250/naskah), Audit Forensik Cerdas, perbandingan revisi v1 vs v2 &amp; pertanyaan sidang.
                        </p>
                    </div>
                    <a href="<?= base_url('harga') ?>" class="btn btn-primary btn-sm" style="width: 100%; background: #d97706; border-color: #b45309;" data-google-vignette="false">Lihat Paket Skripsi</a>
                </div>

                <div class="home-pricing-card">
                    <div>
                        <span class="plan-pill plan-pill-pro">Dosen &amp; Kampus</span>
                        <div class="plan-name">Dosen Pro &amp; Institusi</div>
                        <div class="plan-price"><span class="currency">Rp</span><span class="amount">75.000</span><span class="plan-period">/ bln (75 cek)</span></div>
                        <p style="font-size: 13px; color: var(--text-muted); line-height: 1.55;">
                            Juga tersedia <strong>Paket Jurnal OJS &amp; Kampus</strong> mulai Rp 450.000/bln hingga opsi instalasi On-Premise.
                        </p>
                    </div>
                    <a href="<?= base_url('harga#kampus') ?>" class="btn btn-outline btn-sm" style="width: 100%;" data-google-vignette="false">Lihat Paket Kampus</a>
                </div>
            </div>

            <div class="section-cta-center">
                <a href="<?= base_url('harga') ?>" class="btn btn-primary" data-google-vignette="false">
                    Bandingkan Semua Paket, Solusi Peran &amp; Metode QRIS &rarr;
                </a>
            </div>
        </div>
    </section>

    <?php if (! empty($homeTestimonials)) : ?>
    <!-- 7. 1-2 Testimoni Unggulan -->
    <section class="section" style="background: #f8fafc;">
        <div class="container">
            <div class="section-header" data-aos="fade-up" data-aos-duration="700">
                <div class="section-tag">Suara Sivitas Akademika</div>
                <h2 class="section-title">Dipercaya Mahasiswa, Dosen &amp; Editor Jurnal</h2>
                <p class="section-desc">
                    Penilaian terverifikasi (<?= number_format($testimonialStats['avg_rating'] ?? 5.0, 1) ?>/5.0 dari <?= (int) ($testimonialStats['total_reviews'] ?? count($approvedTestimonials)) ?>+ ulasan) atas kejelasan bukti telaah AR Tilik:
                </p>
            </div>

            <div class="home-testi-preview">
                <?php foreach ($homeTestimonials as $tItem) : ?>
                    <article class="home-testi-card">
                        <div>
                            <div style="color: #f59e0b; font-size: 15px; margin-bottom: 10px;" aria-label="Rating <?= (int) $tItem['rating'] ?> dari 5">
                                <?php for ($st = 1; $st <= 5; $st++) : ?>
                                    <span><?= $st <= (int) $tItem['rating'] ? '★' : '☆' ?></span>
                                <?php endfor; ?>
                            </div>
                            <p style="font-size: 14.5px; color: var(--text-main); line-height: 1.65; margin: 0;">
                                "<?= esc($tItem['content']) ?>"
                            </p>
                        </div>
                        <div class="testi-author-row" style="margin-top: 0;">
                            <div class="testi-author-left">
                                <div class="testi-avatar"><?= esc(mb_substr($tItem['display_name'] ?? 'A', 0, 1)) ?></div>
                                <div class="testi-author-meta">
                                    <div class="testi-author-name-row">
                                        <span class="testi-author-name"><?= esc($tItem['display_name']) ?></span>
                                        <span class="testi-verified-badge">✓ Terverifikasi</span>
                                    </div>
                                    <div class="testi-author-role"><?= esc($tItem['author_role'] ?? 'Mahasiswa') ?></div>
                                    <?php if (! empty($tItem['display_institution'])) : ?>
                                        <div class="testi-author-inst"><?= esc($tItem['display_institution']) ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>

            <div class="section-cta-center">
                <a href="<?= base_url('tentang#testimoni') ?>" class="btn btn-outline" data-google-vignette="false">
                    Lihat Semua Testimoni, Filosofi &amp; Gotong Royong Korpus &rarr;
                </a>
                <a href="<?= base_url('tentang#duta-kampus-cta') ?>" class="btn btn-outline" style="border-color: #f59e0b; color: #b45309; background: #fffbeb;" data-google-vignette="false">
                    🎓 Gabung Program Duta Kampus
                </a>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- 8. CTA Akhir -->
    <section class="final-cta-section google-anno-skip">
        <div class="container">
            <div class="final-cta-inner">
                <div style="display: inline-flex; align-items: center; gap: 8px; padding: 5px 14px; background: rgba(255, 255, 255, 0.12); border: 1px solid rgba(255, 255, 255, 0.22); border-radius: 9999px; font-size: 11.5px; font-weight: 700; color: #e0e7ff; text-transform: uppercase; letter-spacing: 0.6px; margin-bottom: 16px;">
                    Bebas Khawatir Self-Plagiarism &bull; Verifikasi QR SHA-256
                </div>
                <h2 style="font-size: clamp(26px, 3.2vw, 36px); font-weight: 800; line-height: 1.25; margin-bottom: 14px; color: #ffffff;">
                    Siap Menelaah Naskah Ilmiah Anda dengan Bukti Terbuka?
                </h2>
                <p style="font-size: 15.5px; color: #cbd5e1; line-height: 1.65; margin-bottom: 28px;">
                    Gunakan 3 submisi gratis tiap minggu untuk memeriksa skripsi, tesis, atau artikel jurnal sebelum disidangkan &mdash; atau uji daftar pustaka seketika lewat Cek Sitasi Gratis.
                </p>
                <div style="display: flex; flex-wrap: wrap; gap: 12px; justify-content: center;">
                    <?php if ($currentUser) : ?>
                        <a href="<?= base_url('dashboard') ?>" class="btn" style="background: #ffffff; color: #1e1b4b; font-weight: 800; padding: 13px 24px;" data-google-vignette="false">
                            🚀 Buka Dashboard Saya
                        </a>
                    <?php else : ?>
                        <button type="button" onclick="openAuthModal('register')" class="btn" style="background: #ffffff; color: #1e1b4b; font-weight: 800; padding: 13px 24px;" data-google-vignette="false">
                            ✨ Mulai Gratis (3 Cek/Minggu)
                        </button>
                    <?php endif; ?>
                    <a href="<?= base_url('cek-sitasi') ?>" class="btn" style="background: rgba(14, 165, 233, 0.22); color: #e0f2fe; border: 1px solid rgba(56, 189, 248, 0.45); padding: 13px 22px;" data-google-vignette="false">
                        🔎 Cek Sitasi Gratis
                    </a>
                    <a href="<?= base_url('faq') ?>" class="btn" style="background: transparent; color: #e2e8f0; border: 1px solid rgba(255, 255, 255, 0.28); padding: 13px 20px;" data-google-vignette="false">
                        ❓ Pertanyaan Umum (FAQ)
                    </a>
                </div>
            </div>
        </div>
    </section>

    </main>

    <!-- 9. Footer Bersama -->
<?= view('landing/partials/footer', [
    'currentUser'  => $currentUser,
    'visitorStats' => $visitorStats,
]) ?>

<?= view('landing/partials/modals', [
    'authSecurityToken' => $authSecurityToken ?? '',
]) ?>
