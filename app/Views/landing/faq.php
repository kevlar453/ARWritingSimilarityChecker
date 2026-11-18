<?php
$currentUser = $currentUser ?? null;
$visitorStats = $visitorStats ?? ['total_unique' => 1, 'today_unique' => 1, 'total_hits' => 1];

$faqSchemaNode = [
    '@type' => 'FAQPage',
    '@id' => base_url('faq#faqpage'),
    'mainEntity' => [
        [
            '@type' => 'Question',
            'name' => 'Apa perbedaan mendasar antara AR Tilik dan aplikasi pemeriksa plagiarisme lainnya?',
            'acceptedAnswer' => [
                '@type' => 'Answer',
                'text' => 'AR Tilik berfokus pada Keadilan Bukti (Evidence-Based Fairness) menggunakan arsitektur Tiga Lensa (5-Gram Sintaktis, TF-IDF Leksikal, dan Lensa Semantik Terkalibrasi) serta kesadaran konteks sitasi. AR Tilik mengisolasi daftar pustaka, mengenali blok kutipan bertanda, dan menyediakan penampil perbandingan berdampingan (Dual-Reader) lengkap dengan bukti empiris terbuka.',
            ],
        ],
        [
            '@type' => 'Question',
            'name' => 'Apakah naskah karya ilmiah yang saya unggah akan disimpan ke repositori publik?',
            'acceptedAnswer' => [
                '@type' => 'Answer',
                'text' => 'Sama sekali tidak. AR Tilik menerapkan kebijakan non-repository default untuk melindungi kerahasiaan dan kedaulatan naskah submisi sivitas akademika. Naskah tidak pernah dibagikan atau dijadikan korpus terbuka tanpa izin eksplisit penulis.',
            ],
        ],
        [
            '@type' => 'Question',
            'name' => 'Bagaimana cara mengintegrasikan AR Tilik ke jurnal Open Journal Systems (OJS 3.x)?',
            'acceptedAnswer' => [
                '@type' => 'Answer',
                'text' => 'Pengelola jurnal mengunduh paket plugin resmi AR Tilik untuk OJS 3.x atau pustaka konektor mandiri ARTilikOJSConnector.php dan mengikuti panduan PDF resmi. Pemeriksaan berjalan otomatis saat penulis mengunggah artikel, dan hasilnya terkirim ke meja editor lewat webhook terverifikasi HMAC-SHA256 dan anti-SSRF. Setiap API key dibatasi 60 permintaan/menit.',
            ],
        ],
        [
            '@type' => 'Question',
            'name' => 'Bagaimana AR Tilik mendeteksi kecurangan manipulasi karakter (Font Spoofing & Zero-Width Space)?',
            'acceptedAnswer' => [
                '@type' => 'Answer',
                'text' => 'AR Tilik dilengkapi modul Anti-Cheat Font & Homoglyph Shield yang menormalisasi karakter Cyrillic/Greek menyerupai Latin, menghapus spasi tak kasat mata (Zero-Width Spaces/Joiners), serta mendeteksi teks putih tersembunyi.',
            ],
        ],
        [
            '@type' => 'Question',
            'name' => 'Bagaimana cara menggunakan alat gratis Cek Sitasi Daftar Pustaka?',
            'acceptedAnswer' => [
                '@type' => 'Answer',
                'text' => 'Buka halaman /cek-sitasi tanpa perlu masuk akun, lalu tempel hingga 10 entri daftar pustaka atau DOI. Sistem memeriksa keberadaan tiap rujukan secara nyata ke Crossref dan OpenAlex tanpa menyimpan teks daftar pustaka Anda.',
            ],
        ],
        [
            '@type' => 'Question',
            'name' => 'Bagaimana cara kerja perbandingan revisi (v1 vs v2) saat bimbingan skripsi/tesis?',
            'acceptedAnswer' => [
                '@type' => 'Answer',
                'text' => 'Ketika mengunggah naskah revisi, AR Tilik otomatis memetakan pasase mana yang selesai/hilang, berkurang, tetap sama, meluas, atau baru muncul beserta selisih skor sambil mempertahankan riwayat versi sebelumnya.',
            ],
        ],
        [
            '@type' => 'Question',
            'name' => 'Apakah sivitas akademika (mahasiswa & dosen) dapat menggunakan AR Tilik secara gratis?',
            'acceptedAnswer' => [
                '@type' => 'Answer',
                'text' => 'Ya, 100% gratis untuk penggunaan dasar. Setiap akun memperoleh kuota 3 submisi per minggu yang direset otomatis tiap Senin, serta bonus +1 submisi tiap menyumbang 3 korpus ilmiah terbuka lewat Gotong Royong Korpus atau top-up terjangkau mulai Rp 10.000 via QRIS.',
            ],
        ],
        [
            '@type' => 'Question',
            'name' => 'Bagaimana cara memeriksa keabsahan Sertifikat Digital AR Tilik & opsi On-Premise kampus?',
            'acceptedAnswer' => [
                '@type' => 'Answer',
                'text' => 'Setiap laporan dilengkapi kode hash SHA-256 dan QR Code yang dapat diverifikasi di portal https://tilik.arsetontong.top/verify. Perguruan tinggi juga dapat memilih instalasi On-Premise atau Private Cloud lewat Paket Kampus.',
            ],
        ],
    ],
];
?>
<?= view('landing/partials/head', [
    'pageTitle'        => 'Pertanyaan Umum (FAQ) AR Tilik — Cara Kerja, Privasi, OJS 3.x, Tarif & Verifikasi QR',
    'pageDescription'  => 'Jawaban lengkap seputar perbedaan AR Tilik, privasi non-repositori, integrasi OJS 3.x webhook HMAC, Cek Sitasi Gratis, perbandingan revisi v1 vs v2, kuota gratis, dan verifikasi sertifikat QR.',
    'canonicalUrl'     => base_url('faq'),
    'pageKeywords'     => 'faq ar tilik, cara cek plagiasi ar tilik, non repository plagiarism checker, panduan ojs 3 webhook',
    'enableAdSense'    => true,
    'activeAdSlots'    => [3],
    'extraJsonLdNodes' => [$faqSchemaNode],
]) ?>

<?= view('landing/partials/navbar', [
    'activeNav'   => 'faq',
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
                    <li aria-current="page">Tanya Jawab (FAQ)</li>
                </ol>
            </nav>

            <h1 class="subpage-title">
                Semua Hal yang Perlu Anda Ketahui <span>Tentang AR Tilik</span>
            </h1>
            <p class="subpage-subtitle">
                Penjelasan terbuka mengenai metodologi Tiga Lensa, privasi draf mahasiswa, integrasi OJS 3.x, alat Cek Sitasi Gratis, pilihan paket, hingga verifikasi sertifikat QR.
            </p>
        </div>
    </section>

    <!-- FAQ Section (Native <details> Search-Friendly Accordions + Cross Links) -->
    <section id="faq" class="faq-section">
        <div class="container">
            <div class="section-header" style="margin-bottom: 30px;" data-aos="fade-up" data-aos-duration="700">
                <div class="section-tag" style="color: var(--primary);">Pusat Pengetahuan</div>
                <h2 class="section-title">Pertanyaan yang Paling Sering Diajukan</h2>
                <p class="section-desc">
                    Klik pertanyaan untuk membuka jawaban lengkap beserta tautan ke halaman terkait (atau gunakan <code>Ctrl+F</code> di peramban untuk langsung melompat ke topik yang dicari).
                </p>
            </div>

            <div class="faq-list" data-aos="fade-up" data-aos-delay="100" data-aos-duration="700">
                <!-- FAQ 1 -->
                <details class="faq-card" open>
                    <summary class="faq-summary">
                        <span>Apa perbedaan mendasar antara AR Tilik dan aplikasi pemeriksa plagiarisme atau detektor AI lainnya?</span>
                        <span class="faq-icon" aria-hidden="true">▼</span>
                    </summary>
                    <div class="faq-details-body">
                        <p>
                            AR Tilik berfokus pada <strong>Keadilan Bukti (Evidence-Based Fairness)</strong> menggunakan arsitektur <em>Tiga Lensa</em> (5-Gram Sintaktis, TF-IDF Leksikal, dan Lensa Semantik Terkalibrasi) serta kesadaran konteks sitasi. Berbeda dengan matcher konvensional yang kerap menandai kutipan resmi atau detektor AI probabilitas yang berisiko <em>false positive</em> tinggi pada penulisan akademik formal, AR Tilik mengisolasi daftar pustaka, mengenali blok kutipan bertanda, dan menyajikan perbandingan berdampingan (Dual-Reader) serta benchmark empiris yang dapat diaudit ulang.
                        </p>
                        <div class="faq-related-links">
                            <span style="color: var(--text-muted); font-weight: 600;">Pelajari lanjut:</span>
                            <a href="<?= base_url('fitur') ?>" class="faq-related-link">⚡ Fitur &amp; Tiga Lensa</a>
                            <a href="<?= base_url('benchmark') ?>" class="faq-related-link">🔬 Benchmark Terbuka</a>
                            <a href="<?= base_url('riset/ar-tilik-vs-zerogpt') ?>" class="faq-related-link">📊 Riset vs ZeroGPT</a>
                        </div>
                    </div>
                </details>

                <!-- FAQ 2 -->
                <details class="faq-card">
                    <summary class="faq-summary">
                        <span>Apakah naskah karya ilmiah yang saya unggah akan disimpan ke repositori publik?</span>
                        <span class="faq-icon" aria-hidden="true">▼</span>
                    </summary>
                    <div class="faq-details-body">
                        <p>
                            <strong>Sama sekali tidak.</strong> AR Tilik menerapkan kebijakan <em>non-repository default</em> untuk melindungi kerahasiaan dan kedaulatan naskah submisi sivitas akademika. Naskah hanya diproses untuk menghasilkan laporan telaah kesepadanan dan tidak pernah dibagikan atau dijadikan korpus terbuka tanpa izin eksplisit penulis.
                        </p>
                        <div class="faq-related-links">
                            <span style="color: var(--text-muted); font-weight: 600;">Terkait:</span>
                            <a href="<?= base_url('fitur#tiga-lapis') ?>" class="faq-related-link">⚖️ Prinsip Tiga Lapis</a>
                            <a href="<?= base_url('privacy') ?>" class="faq-related-link">🔒 Kebijakan Privasi</a>
                            <a href="<?= base_url('harga') ?>" class="faq-related-link">🎓 Pilihan Paket Mahasiswa</a>
                        </div>
                    </div>
                </details>

                <!-- FAQ 3 -->
                <details class="faq-card">
                    <summary class="faq-summary">
                        <span>Bagaimana cara mengintegrasikan AR Tilik ke jurnal Open Journal Systems (OJS 3.x)?</span>
                        <span class="faq-icon" aria-hidden="true">▼</span>
                    </summary>
                    <div class="faq-details-body">
                        <p>
                            Integrasi OJS sangat praktis tanpa perlu mengubah <em>core</em> OJS. Pengelola jurnal mengunduh paket plugin resmi AR Tilik untuk OJS 3.x &mdash; atau pustaka konektor mandiri <code>ARTilikOJSConnector.php</code> untuk integrasi manual &mdash; dan mengikuti panduan di Buku Panduan OJS (PDF). Pemeriksaan berjalan otomatis saat penulis mengunggah artikel di OJS, dan hasilnya terkirim kembali ke meja editor lewat webhook terverifikasi HMAC-SHA256 beserta proteksi anti-SSRF: skor, putusan, dan tautan laporan, tanpa <em>polling</em>. Setiap API key dibatasi 60 permintaan/menit.
                        </p>
                        <div class="faq-related-links">
                            <span style="color: var(--text-muted); font-weight: 600;">Panduan &amp; Berkas:</span>
                            <a href="<?= base_url('ojs') ?>" class="faq-related-link">🔌 Halaman Integrasi OJS</a>
                            <a href="<?= base_url('integrations/ojs/artilik-ojs-plugin.zip') ?>" download="artilik-ojs-plugin.zip" class="faq-related-link">⬇️ Unduh Plugin OJS 3 (.zip)</a>
                            <a href="<?= base_url('harga#kampus') ?>" class="faq-related-link">🏛️ Paket Jurnal &amp; Kampus</a>
                        </div>
                    </div>
                </details>

                <!-- FAQ 4 -->
                <details class="faq-card">
                    <summary class="faq-summary">
                        <span>Bagaimana AR Tilik mendeteksi kecurangan manipulasi karakter (Font Spoofing &amp; Zero-Width Space)?</span>
                        <span class="faq-icon" aria-hidden="true">▼</span>
                    </summary>
                    <div class="faq-details-body">
                        <p>
                            AR Tilik dilengkapi modul <em>Anti-Cheat Font &amp; Homoglyph Shield</em> yang secara otomatis menormalisasi karakter Cyrillic/Greek yang mirip dengan Latin, menghapus karakter tak kasat mata (Zero-Width Spaces/Joiners), dan mendeteksi injeksi teks tersembunyi yang lazim digunakan untuk mengelabui deteksi kemiripan tradisional.
                        </p>
                        <div class="faq-related-links">
                            <span style="color: var(--text-muted); font-weight: 600;">Selengkapnya:</span>
                            <a href="<?= base_url('fitur#fitur-unggulan') ?>" class="faq-related-link">🛡️ 6 Inovasi Mesin Forensik</a>
                        </div>
                    </div>
                </details>

                <!-- FAQ 5 -->
                <details class="faq-card">
                    <summary class="faq-summary">
                        <span>Bagaimana cara kerja alat gratis Cek Sitasi Daftar Pustaka di AR Tilik?</span>
                        <span class="faq-icon" aria-hidden="true">▼</span>
                    </summary>
                    <div class="faq-details-body">
                        <p>
                            Buka halaman alat gratis <strong>Cek Sitasi</strong> tanpa perlu login atau mendaftar akun. Tempel maksimal 10 entri daftar pustaka atau DOI, lalu tiap entri diverifikasi secara nyata ke basis data ilmiah <strong>Crossref</strong> dan <strong>OpenAlex</strong> dengan status jujur: <em>Terverifikasi</em> (dengan tautan sumber asli), <em>Meragukan</em> (kandidat terdekat beserta alasan perbedaan tahun/penulis), atau <em>Tidak terverifikasi</em>. Daftar pustaka yang Anda tempel tidak disimpan.
                        </p>
                        <div class="faq-related-links">
                            <span style="color: var(--text-muted); font-weight: 600;">Coba sekarang:</span>
                            <a href="<?= base_url('cek-sitasi') ?>" class="faq-related-link">🔎 Buka Alat Cek Sitasi Gratis</a>
                            <a href="<?= base_url('fitur#fitur-unggulan') ?>" class="faq-related-link">📑 Audit Sitasi Naskah Lengkap</a>
                        </div>
                    </div>
                </details>

                <!-- FAQ 6 -->
                <details class="faq-card">
                    <summary class="faq-summary">
                        <span>Apakah tersedia fitur perbandingan progres revisi (v1 vs v2) bagi mahasiswa yang sedang revisi bab?</span>
                        <span class="faq-icon" aria-hidden="true">▼</span>
                    </summary>
                    <div class="faq-details-body">
                        <p>
                            <strong>Ya.</strong> Ketika Anda mengunggah revisi naskah atau bab skripsi di dashboard, AR Tilik otomatis membandingkannya dengan versi sebelumnya (v1 vs v2): menunjukkan pasase mana yang berhasil diselesaikan/berkurang, tetap sama, meluas, atau baru muncul beserta selisih skor &mdash; sementara laporan versi v1 tetap tersimpan utuh sebagai dokumentasi bimbingan.
                        </p>
                        <div class="faq-related-links">
                            <span style="color: var(--text-muted); font-weight: 600;">Lihat contoh:</span>
                            <a href="<?= base_url('fitur#dual-reader') ?>" class="faq-related-link">🖥️ Dual-Reader &amp; Revisi v1 vs v2</a>
                            <a href="<?= base_url('harga') ?>" class="faq-related-link">💳 Paket Pejuang Skripsi</a>
                        </div>
                    </div>
                </details>

                <!-- FAQ 7 -->
                <details class="faq-card">
                    <summary class="faq-summary">
                        <span>Apakah sivitas akademika dapat memakai AR Tilik secara gratis, dan bagaimana skema Top-Up?</span>
                        <span class="faq-icon" aria-hidden="true">▼</span>
                    </summary>
                    <div class="faq-details-body">
                        <p>
                            <strong>Ya, 100% gratis untuk kuota dasar.</strong> Setiap akun yang mendaftar langsung memperoleh hak <strong>3 submisi per minggu</strong> yang diperbarui otomatis tiap Senin. Anda juga dapat menambah kuota permanen lewat program <em>Gotong Royong Korpus</em> (3 korpus terbuka = +1 cek), melakukan <em>Top-Up Kuota</em> tanpa masa kedaluwarsa mulai Rp 10.000 (3 cek) via QRIS/Virtual Account, atau memilih paket bulanan.
                        </p>
                        <div class="faq-related-links">
                            <span style="color: var(--text-muted); font-weight: 600;">Lihat rincian:</span>
                            <a href="<?= base_url('harga') ?>" class="faq-related-link">💳 Paket &amp; Tarif Lengkap</a>
                            <a href="<?= base_url('tentang#kuota-korpus') ?>" class="faq-related-link">🤝 Gotong Royong Korpus</a>
                            <a href="<?= base_url('tentang#duta-kampus-cta') ?>" class="faq-related-link">🎓 Program Duta Kampus</a>
                        </div>
                    </div>
                </details>

                <!-- FAQ 8 -->
                <details class="faq-card">
                    <summary class="faq-summary">
                        <span>Bagaimana cara memvalidasi keabsahan Sertifikat Digital QR Code &amp; opsi On-Premise kampus?</span>
                        <span class="faq-icon" aria-hidden="true">▼</span>
                    </summary>
                    <div class="faq-details-body">
                        <p>
                            Setiap laporan resmi AR Tilik menyertakan kode hash kriptografis SHA-256 dan QR Code menuju portal verifikasi publik sehingga perpustakaan prodi, penguji sidang, atau editor jurnal dapat memeriksa kesesuaian skor dan keaslian sertifikat secara langsung. Bagi perguruan tinggi dan LPPM yang membutuhkan penyimpanan mandiri di server kampus sendiri (memenuhi prinsip UU PDP), tersedia Paket Institusi dengan opsi pemasangan <em>On-Premise</em> atau <em>Private Cloud</em>.
                        </p>
                        <div class="faq-related-links">
                            <span style="color: var(--text-muted); font-weight: 600;">Akses cepat:</span>
                            <a href="<?= base_url('verify') ?>" class="faq-related-link">🛡️ Portal Verifikasi QR</a>
                            <a href="<?= base_url('harga#kampus') ?>" class="faq-related-link">🏛️ Konsultasi Paket Kampus</a>
                        </div>
                    </div>
                </details>
            </div>
        </div>
    </section>

    <!-- Kotak Iklan PARIWARA 3 (setelah FAQ) -->
    <?= view('landing/partials/ad_slot', ['slotNum' => 3, 'ariaLabel' => 'Iklan Sponsor AR Tilik']) ?>

    <!-- Cross-Link & Support Band -->
    <section class="cross-link-band google-anno-skip">
        <div class="container cross-link-flex">
            <div>
                <h2 style="font-size: 20px; font-weight: 800; color: var(--dark); margin-bottom: 4px;">
                    Masih Ada Pertanyaan atau Kebutuhan Khusus Kampus?
                </h2>
                <p style="font-size: 14px; color: var(--text-muted); margin: 0;">
                    Hubungi tim pengembang via formulir kontak, WhatsApp 0851 3886 2032, atau langsung uji layanan gratis kami.
                </p>
            </div>
            <div style="display: flex; flex-wrap: wrap; gap: 10px;">
                <button type="button" onclick="openContactModal('Pertanyaan Umum')" class="btn btn-primary" data-google-vignette="false">
                    📬 Hubungi Tim Pengembang
                </button>
                <a href="<?= base_url('cek-sitasi') ?>" class="btn btn-outline" style="border-color: #0284c7; color: #0369a1;" data-google-vignette="false">
                    🔎 Cek Sitasi Gratis
                </a>
                <?php if (! $currentUser) : ?>
                    <button type="button" onclick="openAuthModal('register')" class="btn btn-outline" data-google-vignette="false">
                        ✨ Mulai Gratis
                    </button>
                <?php endif; ?>
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
