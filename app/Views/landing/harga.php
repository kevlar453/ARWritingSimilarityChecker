<?php
$currentUser = $currentUser ?? null;
$visitorStats = $visitorStats ?? ['total_unique' => 1, 'today_unique' => 1, 'total_hits' => 1];
?>
<?= view('landing/partials/head', [
    'pageTitle'       => 'Harga, Paket & Top-Up Kuota AR Tilik — Gratis 3x/Minggu, Pejuang Skripsi & Kampus',
    'pageDescription' => 'Pilihan tarif transparan AR Tilik: Akun Gratis Civitas Akademika (3 cek/minggu), Top-Up tanpa kedaluwarsa mulai Rp 10.000, Paket Pejuang Skripsi Rp 25.000/bln, Dosen Pro Rp 75.000/bln, dan Paket Kampus/OJS.',
    'canonicalUrl'    => base_url('harga'),
    'pageKeywords'    => 'harga cek plagiarisme ar tilik, paket pejuang skripsi, top up cek plagiasi murah, paket kampus ojs jurnal',
    'enableAdSense'   => false,
]) ?>

<?= view('landing/partials/navbar', [
    'activeNav'   => 'harga',
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
                    <li aria-current="page">Harga &amp; Paket</li>
                </ol>
            </nav>

            <h1 class="subpage-title">
                Tarif Transparan: <span>Gratis Tiap Minggu</span> hingga Lisensi Kampus
            </h1>
            <p class="subpage-subtitle">
                Gunakan kuota gratis mingguan untuk bimbingan rutin, beli top-up tanpa masa kedaluwarsa saat menjelang tenggat sidang, atau berlangganan paket kapasitas besar &amp; rumah jurnal OJS.
            </p>

            <div class="subpage-jump-nav" aria-label="Navigasi Seksi Harga">
                <a href="#langganan" class="subpage-jump-chip">4 Pilihan Paket</a>
                <a href="#kampus" class="subpage-jump-chip">Paket Kampus &amp; OJS</a>
                <a href="#pembayaran" class="subpage-jump-chip">QRIS &amp; Virtual Account</a>
                <a href="#solusi" class="subpage-jump-chip">Solusi Sesuai Peran</a>
            </div>
        </div>
    </section>

    <!-- Section 1: 4 Kartu Harga Lengkap -->
    <section id="langganan" class="section google-anno-skip" style="background: #ffffff; border-bottom: 1px solid var(--border); position: relative;">
        <span id="pricing" style="position: absolute; top: -70px; left: 0;"></span>
        <div class="container">
            <div class="section-header" data-aos="fade-up" data-aos-duration="700">
                <div class="section-tag">Tarif Transparan &amp; Pilihan Akses</div>
                <h2 class="section-title">Pilihan Paket &amp; Top-Up Kuota AR Tilik</h2>
                <p class="section-desc">
                    Mulai dari penggunaan mandiri gratis, top-up fleksibel tanpa batas waktu, paket bulanan pejuang skripsi, hingga integrasi institusi kampus:
                </p>
            </div>

            <div class="grid-4">
                <!-- Plan 1: Free Academic -->
                <div class="pricing-card google-anno-skip" data-aos="fade-up" data-aos-delay="100" data-aos-duration="700">
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
                            <li>Perbandingan revisi v1 vs v2 antar draf bab</li>
                            <li>Filter cerdas sitasi pustaka (eksklusi referensi)</li>
                            <li>Sertifikat resmi keaslian naskah ber-QR Code</li>
                        </ul>
                    </div>

                    <?php if ($currentUser) : ?>
                        <a href="<?= base_url('dashboard') ?>" class="btn btn-outline" data-google-vignette="false">
                            Gunakan Kuota Saya
                        </a>
                    <?php else : ?>
                        <button type="button" onclick="openAuthModal('register')" class="btn btn-outline" data-google-vignette="false">
                            Daftar Akun Gratis
                        </button>
                    <?php endif; ?>
                </div>

                <!-- Plan 2: Top-Up Fleksibel -->
                <div class="pricing-card google-anno-skip" data-aos="fade-up" data-aos-delay="150" data-aos-duration="700">
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
                            <li>Analisis Tiga Lensa: n-gram, TF-IDF Cosine &amp; kemiripan makna</li>
                            <li><strong>Audit Forensik Cerdas</strong> &amp; instrumen ujian sidang aktif</li>
                            <li>Semua fitur deteksi kecerdasan buatan &amp; stylometri aktif</li>
                            <li>Ekspor PDF laporan resmi lengkap ber-QR</li>
                            <li>Aktivasi instan via QRIS &amp; Virtual Account</li>
                        </ul>
                    </div>

                    <?php if ($currentUser) : ?>
                        <a href="<?= base_url('dashboard') ?>" class="btn btn-primary" data-google-vignette="false">
                            ⚡ Top-Up di Dashboard
                        </a>
                    <?php else : ?>
                        <button type="button" onclick="openAuthModal('login')" class="btn btn-primary" data-google-vignette="false">
                            Masuk &amp; Beli Kuota
                        </button>
                    <?php endif; ?>
                </div>

                <!-- Plan 3: Paket Pejuang Skripsi (Featured) -->
                <div class="pricing-card popular google-anno-skip" data-aos="fade-up" data-aos-delay="200" data-aos-duration="700">
                    <div class="pricing-badge popular">⭐ Paling Diminati</div>
                    <div>
                        <span class="plan-pill plan-pill-popular">Paket Skripsi</span>
                        <div class="plan-name">Pejuang Skripsi</div>
                        <div class="plan-price">
                            <span class="currency">Rp</span><span class="amount">25.000</span>
                            <span class="plan-period">/ bulan</span>
                        </div>
                        <p class="plan-desc">
                            Hanya <strong>Rp 1.250 / dokumen</strong>. Solusi hemat untuk persiapan sidang &amp; revisi bab skripsi/tesis.
                        </p>

                        <ul class="plan-features">
                            <li><strong>20 submisi dokumen</strong> selama masa aktif</li>
                            <li>Analisis Tiga Lensa: n-gram, TF-IDF Cosine &amp; kemiripan makna</li>
                            <li><strong>Audit Forensik Cerdas</strong> &amp; 3 panduan pertanyaan sidang dosen</li>
                            <li>Deteksi fabrikasi sitasi gaib &amp; inkonsistensi metodologi</li>
                            <li>Ekspor PDF laporan resmi lengkap tanpa batas</li>
                            <li>Verifikasi keaslian naskah ber-QR Code</li>
                            <li>Masa aktif paket 30 hari penuh</li>
                            <li>Aktivasi instan via QRIS &amp; Virtual Account</li>
                        </ul>
                    </div>

                    <?php if ($currentUser) : ?>
                        <a href="<?= base_url('dashboard') ?>" class="btn" style="background: #f59e0b; border: 1px solid #d97706; color: #ffffff; box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3);" data-google-vignette="false">
                            Langganan di Dashboard &rarr;
                        </a>
                    <?php else : ?>
                        <button type="button" onclick="openAuthModal('register')" class="btn" style="background: #f59e0b; border: 1px solid #d97706; color: #ffffff; box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3);" data-google-vignette="false">
                            Daftar &amp; Langganan &rarr;
                        </button>
                    <?php endif; ?>
                </div>

                <!-- Plan 4: Dosen & Peneliti Pro -->
                <div class="pricing-card google-anno-skip" data-aos="fade-up" data-aos-delay="250" data-aos-duration="700">
                    <div>
                        <span class="plan-pill plan-pill-pro">Kapasitas Besar</span>
                        <div class="plan-name">Dosen &amp; Peneliti Pro</div>
                        <div class="plan-price">
                            <span class="currency">Rp</span><span class="amount">75.000</span>
                            <span class="plan-period">/ bulan</span>
                        </div>
                        <p class="plan-desc">
                            Hanya <strong>Rp 1.000 / dokumen</strong>. Dirancang untuk dosen pembimbing dan peneliti aktif.
                        </p>

                        <ul class="plan-features">
                            <li><strong>75 submisi dokumen</strong> selama masa aktif</li>
                            <li>Dukungan format naskah lengkap (PDF, DOCX, TXT, .tex)</li>
                            <li><strong>Audit Forensik Cerdas</strong> mendalam &amp; instrumen dewan penguji</li>
                            <li>Sintesis risiko akademik berbobot &amp; integritas sitasi</li>
                            <li>Dukungan berkas besar hingga <strong>64 MB</strong></li>
                            <li>Pemeriksaan draf jurnal &amp; tugas kelas</li>
                            <li>Masa aktif paket 30 hari penuh</li>
                            <li>Aktivasi instan via QRIS &amp; Virtual Account</li>
                        </ul>
                    </div>

                    <?php if ($currentUser) : ?>
                        <a href="<?= base_url('dashboard') ?>" class="btn btn-outline" data-google-vignette="false">
                            Pilih Paket Dosen
                        </a>
                    <?php else : ?>
                        <button type="button" onclick="openAuthModal('register')" class="btn btn-outline" data-google-vignette="false">
                            Daftar &amp; Langganan
                        </button>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Institutional & Enterprise Banner (#kampus) -->
            <div id="kampus" style="margin-top: 36px; background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 100%); border-radius: 20px; padding: 32px 36px; color: #ffffff; display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 24px; border: 1px solid rgba(99, 102, 241, 0.25); box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.3);">
                <div style="max-width: 780px;">
                    <div style="display: inline-flex; align-items: center; gap: 8px; padding: 4px 12px; border-radius: 20px; background: rgba(99, 102, 241, 0.2); border: 1px solid rgba(99, 102, 241, 0.4); font-size: 11px; font-weight: 800; color: #a5b4fc; text-transform: uppercase; margin-bottom: 12px;">
                        🏛️ Solusi Kampus &amp; Lembaga Riset
                    </div>
                    <h3 style="font-size: 22px; font-weight: 800; margin-bottom: 8px; color: #ffffff;">Paket Kampus, OJS Jurnal &amp; LPPM (Enterprise)</h3>
                    <p style="font-size: 14px; color: #cbd5e1; line-height: 1.6; margin: 0;">
                        Solusi institusi untuk penguatan integritas karya ilmiah perguruan tinggi dan dewan editor jurnal. Mendukung integrasi OJS 3.x berbasis Plugin ZIP, Konektor PHP &amp; REST API v1 + Webhook HMAC, opsi instalasi mandiri (<em>on-premise</em> / <em>private cloud</em>) dengan isolasi korpus institusi, kesiapan repositori OAI-PMH 2.0, serta pendampingan teknis khusus.
                    </p>
                </div>
                <div style="flex-shrink: 0; width: 100%; max-width: 260px; text-align: center;">
                    <button type="button" onclick="openContactModal('Konsultasi Institusi & Lisensi Kampus')" class="btn" style="background: #ffffff; color: #0f172a; font-weight: 800; width: 100%; border: none; padding: 14px 20px; display: inline-block; text-decoration: none; border-radius: 14px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); cursor: pointer;" data-google-vignette="false">
                        Konsultasi Institusi &rarr;
                    </button>
                    <div style="font-size: 11px; color: #94a3b8; margin-top: 8px;">Mulai Rp 450.000 / bln untuk pengelolaan jurnal</div>
                    <a href="<?= base_url('ojs') ?>" style="display: inline-block; margin-top: 8px; font-size: 12px; color: #fda4af; font-weight: 700; text-decoration: underline;">
                        Lihat Spesifikasi OJS 3.x &rarr;
                    </a>
                </div>
            </div>

            <!-- Payment Methods Supported Bar -->
            <div id="pembayaran" style="margin-top: 24px; padding: 18px 24px; background: #f8fafc; border: 1px solid var(--border); border-radius: 16px; display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 14px; font-size: 13px; color: var(--text-muted);" data-aos="fade-up" data-aos-delay="350">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <span style="font-size: 18px;" aria-hidden="true">💳</span>
                    <span><strong>Metode Pembayaran Resmi (Midtrans Gateway):</strong> QRIS (GoPay, OVO, Dana, ShopeePay, LinkAja) &amp; Virtual Account (BCA, BRI, BNI, Mandiri)</span>
                </div>
                <div style="font-size: 12px; color: #64748b;">
                    🔒 Terenkripsi SSL 256-bit &bull; Kuota langsung bertambah otomatis dalam hitungan detik
                </div>
            </div>

            <!-- Callout Duta Kampus & Gotong Royong -->
            <div style="margin-top: 16px; display: grid; grid-template-columns: repeat(auto-fit, minmax(290px, 1fr)); gap: 14px;">
                <div style="padding: 16px 22px; background: linear-gradient(90deg, rgba(245, 158, 11, 0.08) 0%, rgba(99, 102, 241, 0.08) 100%); border: 1px dashed rgba(245, 158, 11, 0.4); border-radius: 16px; display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 12px;">
                    <div style="font-size: 13px; color: #1e293b;">
                        <strong>🎓 Program Duta Kampus:</strong> Akses naskah gratis 30 hari &amp; komisi 20%!
                    </div>
                    <a href="<?= base_url('ambassador') ?>" class="btn" style="background: #f59e0b; color: #0f172a; font-weight: 800; font-size: 12.5px; padding: 8px 16px; border-radius: 10px; text-decoration: none;">
                        Daftar Duta Kampus &rarr;
                    </a>
                </div>

                <div style="padding: 16px 22px; background: #f0f9ff; border: 1px dashed rgba(14, 165, 233, 0.4); border-radius: 16px; display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 12px;">
                    <div style="font-size: 13px; color: #1e293b;">
                        <strong>🤝 Butuh Kuota Gratis Ekstra?</strong> Sumbang 3 korpus valid = +1 submisi!
                    </div>
                    <a href="<?= base_url('tentang#kuota-korpus') ?>" class="btn btn-outline btn-sm" style="border-color: #0284c7; color: #0369a1;">
                        Gotong Royong Korpus &rarr;
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Section 2: Solusi Sesuai Peran Akademik (#solusi) -->
    <section id="solusi" class="section" style="background: #f8fafc;">
        <div class="container">
            <div class="section-header" data-aos="fade-up" data-aos-duration="700">
                <div class="section-tag">Pilih Sesuai Peran Anda</div>
                <h2 class="section-title">Solusi Tepat Guna untuk Seluruh Sivitas Akademika</h2>
                <p class="section-desc">
                    Setiap paket dirancang menjawab kebutuhan spesifik mahasiswa akhir, pengelola jurnal OJS, dan pimpinan perguruan tinggi:
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
                            <li><span class="check-icon">✓</span> <span><strong>Layar Ganda (Dual-Reader):</strong> Telusuri kalimat yang perlu diparafrasa berdampingan.</span></li>
                            <li><span class="check-icon">✓</span> <span><strong>Perbandingan Revisi (v1 vs v2):</strong> Unggah revisi bab dan lihat pasase mana yang selesai, berkurang, tetap sama, meluas, atau baru muncul &mdash; lengkap dengan selisih skor.</span></li>
                            <li><span class="check-icon">✓</span> <span><strong>Tarif Ringan Mahasiswa:</strong> Gratis 3x/minggu atau top-up fleksibel mulai Rp 10.000 (3 dokumen).</span></li>
                        </ul>
                    </div>

                    <?php if ($currentUser) : ?>
                        <a href="<?= base_url('dashboard') ?>" class="btn btn-outline" style="width: 100%; justify-content: center; font-weight: 700;">
                            Buka Dashboard Mahasiswa
                        </a>
                    <?php else : ?>
                        <button type="button" onclick="openAuthModal('register')" class="btn btn-outline" style="width: 100%; justify-content: center; font-weight: 700;">
                            Daftar Akun Mahasiswa
                        </button>
                    <?php endif; ?>
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
                            Pemeriksaan kemiripan naskah terarah untuk mendukung kepatuhan etika publikasi pada persiapan akreditasi ARJUNA &amp; indeksasi SINTA.
                        </p>

                        <ul class="persona-list">
                            <li><span class="check-icon" style="color: #e11d48;">✓</span> <span><strong>Plugin Resmi ZIP &amp; REST API OJS 3.x:</strong> Pasang praktis tanpa ubah <em>core</em> OJS.</span></li>
                            <li><span class="check-icon" style="color: #e11d48;">✓</span> <span><strong>Webhook Otomatis HMAC-SHA256:</strong> Hasil langsung tiba di meja editor tanpa <em>polling</em>.</span></li>
                            <li><span class="check-icon" style="color: #e11d48;">✓</span> <span><strong>Deteksi Lintas Bahasa:</strong> Membongkar artikel terjemahan asing tanpa sitasi.</span></li>
                            <li><span class="check-icon" style="color: #e11d48;">✓</span> <span><strong>Sertifikat Digital QR &amp; Rate Limit:</strong> 60 req/menit per API key agar kuota aman.</span></li>
                        </ul>
                    </div>

                    <a href="<?= base_url('ojs') ?>" class="btn btn-primary" style="width: 100%; justify-content: center; font-weight: 700; background: linear-gradient(135deg, #e11d48 0%, #be123c 100%);">
                        Pelajari Integrasi OJS &rarr;
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
                            <li><span class="check-icon">✓</span> <span><strong>Kedaulatan Data Mandiri:</strong> Naskah sivitas tetap di server kampus (mendukung prinsip UU PDP).</span></li>
                            <li><span class="check-icon">✓</span> <span><strong>Opsi On-Premise / Private Cloud:</strong> Dipasang di pusat data kampus sendiri.</span></li>
                            <li><span class="check-icon">✓</span> <span><strong>Kesiapan Protokol OAI-PMH:</strong> Dapat dikonfigurasikan dengan repositori kampus.</span></li>
                            <li><span class="check-icon">✓</span> <span><strong>Dasbor Integritas Kampus:</strong> Analitik tren kemiripan dan risiko antar fakultas.</span></li>
                        </ul>
                    </div>

                    <div style="display: flex; flex-direction: column; gap: 8px;">
                        <button type="button" onclick="openContactModal('Konsultasi Institusi & Lisensi Kampus')" class="btn btn-outline" style="width: 100%; justify-content: center; font-weight: 700;">
                            Konsultasi Paket Kampus
                        </button>
                        <a href="<?= base_url('docs/Brosur_Eksekutif_ARTilik.pdf') ?>" target="_blank" download="Brosur_Eksekutif_ARTilik.pdf" class="btn btn-sm" style="width: 100%; justify-content: center; font-weight: 700; background: rgba(16, 185, 129, 0.1); color: #059669; border: 1px solid rgba(16, 185, 129, 0.3);">
                            <span>📄</span> Unduh Brosur Eksekutif (PDF)
                        </a>
                    </div>
                </div>
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
