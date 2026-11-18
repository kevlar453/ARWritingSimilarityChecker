<?php
$currentUser = $currentUser ?? null;
$visitorStats = $visitorStats ?? ['total_unique' => 1, 'today_unique' => 1, 'total_hits' => 1];
$approvedTestimonials = $approvedTestimonials ?? [];
$testimonialStats = $testimonialStats ?? ['avg_rating' => 5.0, 'total_reviews' => 0];

$extraNodes = [];
if (! empty($approvedTestimonials) && (int) ($testimonialStats['total_reviews'] ?? count($approvedTestimonials)) > 0) {
    $reviewItems = [];
    foreach (array_slice($approvedTestimonials, 0, 5) as $sRev) {
        $reviewItems[] = [
            '@type' => 'Review',
            'author' => [
                '@type' => 'Person',
                'name' => (string) ($sRev['display_name'] ?? $sRev['author_name'] ?? 'Sivitas Akademika'),
            ],
            'reviewRating' => [
                '@type' => 'Rating',
                'ratingValue' => (string) (int) $sRev['rating'],
                'bestRating' => '5',
                'worstRating' => '1',
            ],
            'reviewBody' => preg_replace('/\s+/', ' ', trim((string) ($sRev['content'] ?? ''))),
        ];
    }
    $extraNodes[] = [
        '@type' => 'WebApplication',
        '@id' => base_url('#software'),
        'name' => 'AR Tilik',
        'url' => base_url(),
        'aggregateRating' => [
            '@type' => 'AggregateRating',
            'ratingValue' => number_format($testimonialStats['avg_rating'] ?? 5.0, 1),
            'ratingCount' => (string) max(1, (int) ($testimonialStats['total_reviews'] ?? count($approvedTestimonials))),
            'reviewCount' => (string) max(1, (int) ($testimonialStats['total_reviews'] ?? count($approvedTestimonials))),
            'bestRating' => '5',
            'worstRating' => '1',
        ],
        'review' => $reviewItems,
    ];
}
?>
<?= view('landing/partials/head', [
    'pageTitle'        => 'Tentang AR Tilik — Filosofi Akademik, Gotong Royong Korpus, Testimoni & Duta Kampus',
    'pageDescription'  => 'Mengenal misi keadilan akademik AR Tilik, prinsip non-repositori & UU PDP, insentif Gotong Royong Korpus (+1 cek per 3 korpus valid), testimoni terverifikasi sivitas, dan program kemitraan Duta Kampus.',
    'canonicalUrl'     => base_url('tentang'),
    'pageKeywords'     => 'tentang ar tilik, gotong royong korpus akademik, testimoni ar tilik, duta kampus ar tilik, kedaulatan data kampus',
    'enableAdSense'    => false,
    'extraJsonLdNodes' => $extraNodes,
]) ?>

<?= view('landing/partials/navbar', [
    'activeNav'   => 'tentang',
    'currentUser' => $currentUser,
]) ?>

    <main id="main-content">

    <!-- Subpage Hero -->
    <section class="subpage-hero google-anno-skip">
        <div class="container">
            <nav class="breadcrumb-nav" aria-label="Breadcrumb">
                <ol class="breadcrumb-list">
                    <li><a href="<?= base_url() ?>">Beranda</a></li>
                    <li aria-hidden="true">/</li>
                    <li aria-current="page">Tentang AR Tilik</li>
                </ol>
            </nav>

            <h1 class="subpage-title">
                Keadilan Bukti Ilmiah, <span>Kedaulatan Data</span> &amp; Gotong Royong Kampus
            </h1>
            <p class="subpage-subtitle">
                AR Tilik dikembangkan oleh ARSetontong untuk membantu mahasiswa, dosen pembimbing, dan pengelola jurnal di Indonesia mengaudit keabsahan naskah secara objektif tanpa menghakimi secara sepihak.
            </p>

            <div class="subpage-jump-nav" aria-label="Navigasi Seksi Tentang">
                <a href="#filosofi" class="subpage-jump-chip">Filosofi &amp; Kedaulatan Data</a>
                <a href="#kuota-korpus" class="subpage-jump-chip">Gotong Royong Korpus</a>
                <a href="#testimoni" class="subpage-jump-chip">Testimoni Akademisi</a>
                <a href="#duta-kampus-cta" class="subpage-jump-chip">Duta Kampus (20%)</a>
            </div>
        </div>
    </section>

    <!-- Section 1: Filosofi & Komitmen Akademik -->
    <section id="filosofi" class="section" style="background: #ffffff; border-bottom: 1px solid var(--border);">
        <div class="container">
            <div class="section-header" data-aos="fade-up" data-aos-duration="700">
                <div class="section-tag">Komitmen Keadilan Akademik</div>
                <h2 class="section-title">Prinsip Dasar Pembangunan AR Tilik</h2>
                <p class="section-desc">
                    Kami percaya pengawasan etika ilmiah hanya sah bila metodenya terbuka, bisa diperiksa kembali oleh manusia, dan menjaga hak penulis atas draf karyanya.
                </p>
            </div>

            <div class="grid-3">
                <div class="theory-card">
                    <div class="card-icon-badge badge-indigo">⚖️</div>
                    <h3 class="theory-title">Bukti Terukur, Bukan Tuduhan</h3>
                    <p class="theory-desc">
                        Skor kemiripan hanyalah indikator awal. AR Tilik memisahkan teks mentah, konteks kutipan, dan penilaian penelaah agar istilah metodologi baku tidak disamaratakan sebagai plagiarisme.
                    </p>
                </div>

                <div class="theory-card">
                    <div class="card-icon-badge badge-emerald">🔒</div>
                    <h3 class="theory-title">Privasi &amp; Non-Repositori Baku</h3>
                    <p class="theory-desc">
                        Draf skripsi dan penelitian sivitas tidak pernah otomatis dimasukkan ke korpus publik. Untuk kebutuhan perguruan tinggi yang tunduk pada regulasi UU PDP, tersedia opsi <em>on-premise</em> di pusat data kampus.
                    </p>
                </div>

                <div class="theory-card">
                    <div class="card-icon-badge badge-cyan">🔬</div>
                    <h3 class="theory-title">Evaluasi Empiris Terbuka</h3>
                    <p class="theory-desc">
                        Kami memublikasikan dataset benchmark akademik Bahasa Indonesia lengkap dengan 14 kasus mismatch yang bisa diunduh dan diuji ulang secara independen oleh peneliti dan kampus.
                    </p>
                </div>
            </div>

            <div class="section-cta-center">
                <a href="<?= base_url('fitur') ?>" class="btn btn-outline" data-google-vignette="false">
                    Pelajari Arsitektur Teknis &rarr;
                </a>
                <a href="<?= base_url('benchmark') ?>" class="btn btn-outline" data-google-vignette="false">
                    🔬 Unduh Dataset &amp; Laporan Benchmark
                </a>
                <a href="<?= base_url('harga#kampus') ?>" class="btn btn-outline" data-google-vignette="false">
                    🏛️ Lihat Paket Kampus &amp; On-Premise
                </a>
            </div>
        </div>
    </section>

    <!-- Section 2: Gotong Royong Korpus -->
    <section id="kuota-korpus" class="section">
        <div class="container">
            <div class="incentive-banner" data-aos="fade-up" data-aos-duration="800">
                <div class="incentive-grid">
                    <div>
                        <span style="background: rgba(56, 189, 248, 0.2); color: #38bdf8; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 700; text-transform: uppercase;">Inisiatif Gotong Royong Akademik</span>
                        <h2 class="incentive-title" style="margin-top: 14px;">Perlu Lebih dari 3 Submisi Tiap Minggu?</h2>
                        <p class="incentive-desc">
                            AR Tilik percaya bahwa repositori institusi yang kuat dibangun bersama. Jika kuota 3 submisi gratis mingguan Anda telah terpakai, Anda dapat menambah kuota secara cuma-cuma dengan <strong>menyumbangkan 3 berkas korpus karya ilmiah valid</strong> (jurnal/skripsi referensi terbuka non-duplikat).
                        </p>
                        <div style="display: flex; gap: 12px; flex-wrap: wrap;">
                            <?php if ($currentUser) : ?>
                                <a href="<?= base_url('dashboard') ?>" class="btn" style="background: #ffffff; color: var(--primary);">
                                    Buka Dashboard &amp; Kontribusi Korpus
                                </a>
                            <?php else : ?>
                                <button type="button" onclick="openAuthModal('register')" class="btn" style="background: #ffffff; color: var(--primary);">
                                    Daftar &amp; Mulai Kontribusi
                                </button>
                            <?php endif; ?>
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

    <!-- Section 3: Testimoni Lengkap & Bukti Nyata Akademisi -->
    <section id="testimoni" class="section" style="background: #f8fafc; border-top: 1px solid var(--border); position: relative;">
        <div class="container">
            <div class="section-header" data-aos="fade-up" data-aos-duration="700">
                <div class="section-tag">Bukti Nyata &amp; Kredibilitas Akademik</div>
                <h2 class="section-title">Dipercaya Oleh Mahasiswa, Dosen, &amp; Peneliti</h2>
                <p class="section-desc">
                    Ulasan otentik dari civitas akademika yang telah menggunakan analisis kemiripan dan audit forensik AR Tilik untuk kelulusan sidang skripsi, tesis, dan publikasi jurnal bereputasi.
                </p>
            </div>

            <?php if (! empty($approvedTestimonials)) : ?>
                <!-- Trust Bar / KPI -->
                <div class="testi-trust-bar-wrap" data-aos="fade-up" data-aos-delay="100">
                    <div class="testi-trust-bar">
                        <div class="testi-trust-stars" aria-hidden="true">★★★★★</div>
                        <div class="testi-trust-score">
                            <?= number_format($testimonialStats['avg_rating'] ?? 5.0, 1) ?> / 5.0
                        </div>
                        <div class="testi-trust-desc">
                            (Skor Kepuasan dari <?= (int) ($testimonialStats['total_reviews'] ?? count($approvedTestimonials)) ?>+ Ulasan Terverifikasi)
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
                    <button type="button" id="testiPrevBtnDesktop" class="testi-nav-arrow testi-prev-arrow" onclick="scrollTesti(-1)" aria-label="Testimoni Sebelumnya" title="Sebelumnya">
                        <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/></svg>
                    </button>
                    <button type="button" id="testiNextBtnDesktop" class="testi-nav-arrow testi-next-arrow" onclick="scrollTesti(1)" aria-label="Testimoni Berikutnya" title="Berikutnya">
                        <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
                    </button>

                    <div id="testimonialsTrack" class="testi-carousel-track">
                        <?php foreach ($approvedTestimonials as $tItem) :
                            $roleLower = strtolower($tItem['author_role'] ?? '');
                            if (str_contains($roleLower, 'jurnal') || str_contains($roleLower, 'editor') || str_contains($roleLower, 'reviewer') || str_contains($roleLower, 'ojs')) {
                                $roleCategory = 'jurnal';
                            } elseif (str_contains($roleLower, 'dosen') || str_contains($roleLower, 'peneliti') || str_contains($roleLower, 'pengajar')) {
                                $roleCategory = 'dosen';
                            } else {
                                $roleCategory = 'mahasiswa';
                            }
                        ?>
                            <div class="testi-card" data-category="<?= $roleCategory ?>">
                                <?php if (! empty($tItem['is_featured'])) : ?>
                                    <div class="testi-featured-badge">⭐ Unggulan</div>
                                <?php endif; ?>

                                <div style="flex-grow: 1;">
                                    <div style="color: #f59e0b; font-size: 15px; margin-bottom: 14px;" aria-label="Rating <?= (int) $tItem['rating'] ?> dari 5">
                                        <?php for ($st = 1; $st <= 5; $st++) : ?>
                                            <span><?= $st <= (int) $tItem['rating'] ? '★' : '☆' ?></span>
                                        <?php endfor; ?>
                                    </div>

                                    <p class="testi-card-quote">
                                        "<?= esc($tItem['content']) ?>"
                                    </p>
                                </div>

                                <div class="testi-author-row">
                                    <div class="testi-author-left">
                                        <div class="testi-avatar">
                                            <?= esc(mb_substr($tItem['display_name'] ?? 'A', 0, 1)) ?>
                                        </div>
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
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div id="testiEmptyState" style="display: none; background: #ffffff; border: 1px dashed var(--border); border-radius: 16px; padding: 36px 20px; text-align: center; color: var(--text-muted); font-size: 13px;">
                        <span>🔍</span> Belum ada ulasan untuk kategori ini.
                    </div>

                    <div id="testiControlsBar" class="testi-controls-bar">
                        <div class="testi-controls-inner">
                            <button type="button" id="testiPrevBtnMobile" class="testi-mobile-nav-btn" onclick="scrollTesti(-1)" aria-label="Sebelumnya" title="Sebelumnya">
                                <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/></svg>
                            </button>
                            <div id="testiDots" class="testi-dots-container"></div>
                            <button type="button" id="testiNextBtnMobile" class="testi-mobile-nav-btn" onclick="scrollTesti(1)" aria-label="Berikutnya" title="Berikutnya">
                                <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
                            </button>
                        </div>
                        <div id="testiCounter" class="testi-counter-badge"></div>
                    </div>
                </div>
            <?php else : ?>
                <div style="background: #ffffff; border: 1px dashed var(--border); border-radius: 16px; padding: 32px; text-align: center; color: var(--text-muted); font-size: 14px;">
                    Jadilah yang pertama membagikan pengalaman penggunaan AR Tilik melalui menu testimoni di dashboard akun Anda.
                </div>
            <?php endif; ?>

            <!-- Callout CTA Tulis Ulasan -->
            <div class="testi-cta-box" data-aos="fade-up">
                <div style="display: flex; align-items: center; gap: 14px;">
                    <span style="font-size: 26px;" aria-hidden="true">💬</span>
                    <div>
                        <div style="font-weight: 800; font-size: 13.5px; color: var(--text-dark);">Telah Menggunakan AR Tilik untuk Naskah Anda?</div>
                        <div style="font-size: 12px; color: var(--text-muted);">Bagikan masukan dan rating kepuasan di dashboard naskah untuk mendukung integritas akademik Indonesia.</div>
                    </div>
                </div>
                <a href="<?= base_url('dashboard') ?>" class="btn" style="background: #e0e7ff; color: #3730a3; font-weight: 800; font-size: 12px; padding: 9px 18px; border-radius: 10px; text-decoration: none; display: inline-flex; align-items: center; gap: 6px;">
                    <span>✍️</span> <span>Beri Ulasan di Dashboard</span> &rarr;
                </a>
            </div>
        </div>
    </section>

    <!-- Section 4: Peluang Bergabung Duta Kampus AR Tilik -->
    <section id="duta-kampus-cta" class="section" style="background: linear-gradient(135deg, #090d16 0%, #1e1b4b 60%, #0f172a 100%); padding: 70px 0; border-top: 1px solid rgba(255,255,255,0.08); border-bottom: 1px solid rgba(255,255,255,0.08); color: #ffffff; position: relative; overflow: hidden;">
        <div class="container" style="position: relative; z-index: 2;">
            <div style="background: rgba(255, 255, 255, 0.04); border: 1px solid rgba(245, 158, 11, 0.35); border-radius: 28px; padding: 44px 36px; display: flex; flex-wrap: wrap; gap: 32px; align-items: center; justify-content: space-between; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5); backdrop-filter: blur(12px);" data-aos="fade-up" data-aos-duration="800">
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
                    <a href="<?= base_url('ambassador') ?>" class="btn" style="background: linear-gradient(135deg, #f59e0b, #d97706); color: #090d16; font-weight: 800; font-size: 15px; padding: 16px 28px; border-radius: 16px; width: 100%; display: inline-flex; align-items: center; justify-content: center; gap: 8px; text-decoration: none; box-shadow: 0 10px 25px rgba(245, 158, 11, 0.35);">
                        <span>🚀</span> <span>Daftar Jadi Duta</span> &rarr;
                    </a>
                    <div style="font-size: 11px; color: #94a3b8; margin-top: 10px;">
                        Seleksi berkas &amp; aktivasi kode dalam 1&times;24 jam kerja
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Cross-Link Band (/tentang -> /harga paket kampus & -> pendaftaran Duta Kampus) -->
    <section id="kontak-tentang" class="cross-link-band google-anno-skip">
        <div class="container cross-link-flex">
            <div>
                <h2 style="font-size: 20px; font-weight: 800; color: var(--dark); margin-bottom: 4px;">
                    Ingin Kerja Sama Institusi atau Bergabung Menjadi Duta Kampus?
                </h2>
                <p style="font-size: 14px; color: var(--text-muted); margin: 0;">
                    Hubungi kami via WhatsApp <strong>0851 3886 2032</strong> atau email <strong>tilik@arsetontong.top</strong>.
                </p>
            </div>
            <div style="display: flex; flex-wrap: wrap; gap: 10px;">
                <a href="<?= base_url('harga#kampus') ?>" class="btn btn-primary" data-google-vignette="false">
                    Lihat Paket Kampus &rarr;
                </a>
                <a href="<?= base_url('ambassador') ?>" class="btn btn-outline" style="border-color: #f59e0b; color: #b45309;" data-google-vignette="false">
                    🎓 Pendaftaran Duta Kampus
                </a>
                <button type="button" onclick="openContactModal('Pertanyaan Umum')" class="btn btn-outline" data-google-vignette="false">
                    📬 Kirim Pesan
                </button>
            </div>
        </div>
    </section>

    </main>

<?= view('landing/partials/footer', [
    'currentUser'  => $currentUser,
    'visitorStats' => $visitorStats,
]) ?>

<?= view('landing/partials/modals', [
    'authSecurityToken' => $authSecurityToken ?? '',
]) ?>
