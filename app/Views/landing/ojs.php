<?php
$currentUser = $currentUser ?? null;
$visitorStats = $visitorStats ?? ['total_unique' => 1, 'today_unique' => 1, 'total_hits' => 1];
?>
<?= view('landing/partials/head', [
    'pageTitle'       => 'Integrasi OJS 3.x & Webhook HMAC — AR Tilik untuk Jurnal Ilmiah SINTA',
    'pageDescription' => 'Unduh plugin resmi OJS 3.x AR Tilik (.zip), pustaka ARTilikOJSConnector.php, Buku Panduan OJS PDF, diagram alur webhook HMAC-SHA256, proteksi anti-SSRF, batas laju 60 permintaan/menit, dan FAQ pengelola jurnal.',
    'canonicalUrl'    => base_url('ojs'),
    'pageKeywords'    => 'plugin ojs 3 plagiarism, ojs similarity checker indonesia, webhook ojs ar tilik, sertifikat sinta arjuna, rest api cek plagiasi ojs',
    'enableAdSense'   => false,
]) ?>

<?= view('landing/partials/navbar', [
    'activeNav'   => 'ojs',
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
                    <li aria-current="page">Integrasi OJS 3.x</li>
                </ol>
            </nav>

            <h1 class="subpage-title">
                Integrasi Otomatis <span>Open Journal Systems (OJS 3.x)</span>
            </h1>
            <p class="subpage-subtitle">
                Hubungkan alur submisi penulis dan penelaahan editor jurnal SINTA secara otomatis tanpa mengubah <em>core</em> OJS. Skor kemiripan, putusan, kode verifikasi QR, dan tautan laporan kembali ke meja editor melalui webhook terverifikasi HMAC-SHA256.
            </p>

            <div class="subpage-actions">
                <a href="<?= base_url('integrations/ojs/artilik-ojs-plugin.zip') ?>" download="artilik-ojs-plugin.zip" class="btn btn-primary" style="background: linear-gradient(135deg, #e11d48 0%, #be123c 100%); border-color: #be123c;" data-google-vignette="false">
                    <span>⬇️</span> Unduh Plugin OJS 3 (.zip)
                </a>
                <a href="<?= base_url('integrations/ojs/Panduan_Integrasi_OJS_ARTilik.pdf') ?>" target="_blank" download="Panduan_Integrasi_OJS_ARTilik.pdf" class="btn btn-outline" data-google-vignette="false">
                    <span>📕</span> Buku Panduan OJS (PDF)
                </a>
                <button type="button" onclick="openContactModal('Konsultasi Institusi & Lisensi Kampus')" class="btn btn-outline" data-google-vignette="false">
                    <span>🏛️</span> Konsultasi Paket Kampus
                </button>
            </div>

            <div class="subpage-jump-nav" aria-label="Navigasi Seksi OJS">
                <a href="#unduhan" class="subpage-jump-chip">Unduhan &amp; Contoh Kode PHP</a>
                <a href="#webhook-flow" class="subpage-jump-chip">Diagram Alur Webhook</a>
                <a href="#alur-ojs" class="subpage-jump-chip">4 Tahap Kerja OJS</a>
                <a href="#keamanan-ojs" class="subpage-jump-chip">HMAC, Anti-SSRF &amp; Rate Limit</a>
                <a href="#faq-ojs" class="subpage-jump-chip">FAQ Khusus OJS</a>
            </div>
        </div>
    </section>

    <!-- Section 1: OJS Banner, Downloads & Code Card -->
    <section id="integrasi-ojs" class="section" style="background: #ffffff; border-bottom: 1px solid var(--border);">
        <div class="container">
            <div id="unduhan" class="section-header" data-aos="fade-up" data-aos-duration="700">
                <div class="section-tag" style="color: #e11d48;">Ekosistem Jurnal Ilmiah SINTA</div>
                <h2 class="section-title">Paket Plugin Resmi, Panduan PDF &amp; REST API v1</h2>
                <p class="section-desc">
                    Pilih pemasangan instan lewat paket plugin ZIP resmi OJS 3.x atau integrasi kustom menggunakan pustaka konektor PHP mandiri dan REST API v1.
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
                            Paket Plugin OJS 3.x &amp; Buku Panduan (PDF Resolusi Tinggi &mdash; 8 Halaman)
                        </h3>
                        <p style="font-size: 14px; color: #cbd5e1; line-height: 1.6; margin-bottom: 24px;">
                            Disusun khusus untuk pengelola jurnal, tim TI kampus, dan Dewan Redaksi. Dilengkapi diagram alur kerja <em>end-to-end</em> (Penulis &rarr; OJS 3 &rarr; REST API &rarr; Tiga Lensa &rarr; Webhook &rarr; Sertifikat Digital), panduan instalasi tanpa mengubah <em>core</em> OJS, serta contoh implementasi webhook.
                        </p>

                        <div style="display: flex; gap: 12px; flex-wrap: wrap;">
                            <a href="<?= base_url('integrations/ojs/artilik-ojs-plugin.zip') ?>" download="artilik-ojs-plugin.zip" class="btn" style="background: linear-gradient(135deg, #e11d48 0%, #be123c 100%); color: #ffffff; box-shadow: 0 4px 14px rgba(225, 29, 72, 0.4); border: 1px solid rgba(255,255,255,0.2);" data-google-vignette="false">
                                <span>⬇️</span> Unduh Plugin OJS 3 (.zip)
                            </a>
                            <a href="<?= base_url('integrations/ojs/Panduan_Integrasi_OJS_ARTilik.pdf') ?>" target="_blank" download="Panduan_Integrasi_OJS_ARTilik.pdf" class="btn" style="background: rgba(244, 63, 94, 0.16); color: #fecdd3; border: 1px solid rgba(244, 63, 94, 0.45);">
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
                        <figure class="ojs-code-card" style="margin: 0;">
                            <figcaption id="ojs-code-caption" style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #1e293b; padding-bottom: 8px; margin-bottom: 12px; color: #94a3b8; font-size: 11px;">
                                <span>PHP &bull; artilik_ojs_runner.php</span>
                                <span style="color: #10b981;">● OJS 3.x Ready</span>
                            </figcaption>
                            <pre role="region" aria-labelledby="ojs-code-caption" tabindex="0" style="margin: 0; line-height: 1.5; overflow-x: auto;"><code><span style="color: #f43f5e;">require_once</span> <span style="color: #fde047;">'ARTilikOJSConnector.php'</span>;

<span style="color: #64748b;">// 1. Inisialisasi dengan API Key Jurnal</span>
<span style="color: #38bdf8;">$tilik</span> = <span style="color: #f43f5e;">new</span> <span style="color: #a78bfa;">ARTilikOJSConnector</span>(
    <span style="color: #fde047;">'artilik_ojs_YOUR_API_KEY'</span>
);

<span style="color: #64748b;">// 2. Kirim naskah + URL webhook terverifikasi HMAC</span>
<span style="color: #38bdf8;">$res</span> = <span style="color: #38bdf8;">$tilik</span>-&gt;<span style="color: #60a5fa;">submitManuscript</span>(
    <span style="color: #fde047;">'/path/to/manuscript.pdf'</span>,
    <span style="color: #fde047;">'Judul Naskah Ilmiah'</span>,
    <span style="color: #fde047;">'Dr. Penulis Utama'</span>,
    <span style="color: #fde047;">'submission_4892'</span>
);

<span style="color: #64748b;">// 3. Hasil &amp; sertifikat ber-QR otomatis tiba lewat webhook</span>
<span style="color: #f43f5e;">echo</span> <span style="color: #38bdf8;">$res</span>[<span style="color: #fde047;">'certificate_url'</span>];</code></pre>
                        </figure>
                    </div>
                </div>
            </div>

            <!-- Visual OJS Webhook Workflow Diagram -->
            <div id="webhook-flow" style="margin-top: 32px;">
                <figure class="ojs-workflow-shell" aria-labelledby="ojs-webhook-diagram-title">
                    <div class="ojs-workflow-chrome">
                        <div class="ojs-workflow-chrome-title" style="flex-wrap: wrap;">
                            <span style="display: inline-flex; align-items: center; gap: 6px; flex-shrink: 0;" aria-hidden="true">
                                <span style="display: inline-block; width: 10px; height: 10px; border-radius: 50%; background: #ef4444;"></span>
                                <span style="display: inline-block; width: 10px; height: 10px; border-radius: 50%; background: #f59e0b;"></span>
                                <span style="display: inline-block; width: 10px; height: 10px; border-radius: 50%; background: #10b981;"></span>
                            </span>
                            <span style="font-size: 12px; color: #cbd5e1; font-family: 'JetBrains Mono', monospace; overflow-wrap: anywhere;">OJS 3.x Review Stage &bull; Plugin ZIP &amp; Webhook</span>
                        </div>
                        <span style="font-size: 11px; color: #34d399; font-weight: 700;">● REST API v1 &amp; Webhook Aktif</span>
                    </div>

                    <div class="ojs-workflow-stage">
                        <div class="ojs-workflow-header">
                            <h3 id="ojs-webhook-diagram-title" class="ojs-workflow-heading">Alur Webhook AR Tilik &times; OJS 3.x</h3>
                            <p class="ojs-workflow-sub">Hasil pemeriksaan kembali ke meja editor secara otomatis &mdash; tanpa <em>polling</em></p>
                        </div>

                        <ol class="ojs-flow-steps" role="list">
                            <li class="ojs-flow-item">
                                <div class="ojs-flow-card ojs-flow-card--step1">
                                    <span class="ojs-step-num" aria-hidden="true">1</span>
                                    <svg class="ojs-step-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                        <polyline points="14 2 14 8 20 8"/>
                                        <line x1="16" y1="13" x2="8" y2="13"/>
                                        <line x1="16" y1="17" x2="8" y2="17"/>
                                        <line x1="10" y1="9" x2="8" y2="9"/>
                                    </svg>
                                    <div class="ojs-step-stage">OJS 3.x</div>
                                    <h4 class="ojs-step-title-h">Naskah diunggah</h4>
                                    <p class="ojs-step-body">Penulis mengunggah naskah &mdash; pemeriksaan terpicu otomatis saat <em>Author Submission</em> atau <em>Peer Review</em>.</p>
                                </div>
                                <span class="ojs-step-connector" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                        <line x1="4" y1="12" x2="20" y2="12"/>
                                        <polyline points="14 6 20 12 14 18"/>
                                    </svg>
                                </span>
                            </li>

                            <li class="ojs-flow-item">
                                <div class="ojs-flow-card ojs-flow-card--step2">
                                    <span class="ojs-step-num" aria-hidden="true">2</span>
                                    <svg class="ojs-step-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <circle cx="11" cy="11" r="7"/>
                                        <circle cx="11" cy="11" r="2.5"/>
                                        <line x1="20" y1="20" x2="16" y2="16"/>
                                    </svg>
                                    <div class="ojs-step-stage">AR Tilik</div>
                                    <h4 class="ojs-step-title-h">Diperiksa Tiga Lensa</h4>
                                    <p class="ojs-step-body">5-Gram, TF-IDF, dan Lensa Semantik menghasilkan skor kemiripan, putusan, dan kode verifikasi.</p>
                                </div>
                                <span class="ojs-step-connector" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                        <line x1="4" y1="12" x2="20" y2="12"/>
                                        <polyline points="14 6 20 12 14 18"/>
                                    </svg>
                                </span>
                            </li>

                            <li class="ojs-flow-item">
                                <div class="ojs-flow-card ojs-flow-card--step3">
                                    <span class="ojs-step-num" aria-hidden="true">3</span>
                                    <svg class="ojs-step-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/>
                                    </svg>
                                    <div class="ojs-step-stage">Webhook</div>
                                    <h4 class="ojs-step-title-h">Hasil dikirim otomatis</h4>
                                    <p class="ojs-step-body">AR Tilik mendorong hasil ke OJS begitu analisis selesai &mdash; tanpa perlu <em>polling</em>.</p>
                                    <span class="ojs-step-pill">HMAC-SHA256 &bull; Anti-SSRF</span>
                                </div>
                                <span class="ojs-step-connector" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                        <line x1="4" y1="12" x2="20" y2="12"/>
                                        <polyline points="14 6 20 12 14 18"/>
                                    </svg>
                                </span>
                            </li>

                            <li class="ojs-flow-item">
                                <div class="ojs-flow-card ojs-flow-card--step4">
                                    <span class="ojs-step-num" aria-hidden="true">4</span>
                                    <svg class="ojs-step-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                                        <polyline points="9 12 11 14 15 10"/>
                                    </svg>
                                    <div class="ojs-step-stage">OJS 3.x</div>
                                    <h4 class="ojs-step-title-h">Hasil tiba di meja editor</h4>
                                    <p class="ojs-step-body">Skor, putusan, dan tautan laporan tampil untuk editor serta tercatat sebagai catatan diskusi editorial.</p>
                                </div>
                            </li>
                        </ol>

                        <div class="ojs-security-bar">
                            <span><strong>Keamanan &amp; Kendali Laju:</strong> HMAC-SHA256</span>
                            <span aria-hidden="true">&bull;</span>
                            <span>Anti-SSRF</span>
                            <span aria-hidden="true">&bull;</span>
                            <span>Rate limit <strong>60 permintaan/menit</strong> per API key</span>
                            <span aria-hidden="true">&bull;</span>
                            <span>Plugin ZIP OJS 3.x &amp; REST API v1</span>
                        </div>

                        <figcaption class="ojs-workflow-footer">
                            Plugin resmi OJS 3.x:
                            <a href="<?= base_url('integrations/ojs/artilik-ojs-plugin.zip') ?>" download="artilik-ojs-plugin.zip" data-google-vignette="false">tilik.arsetontong.top/integrations/ojs/artilik-ojs-plugin.zip</a>
                        </figcaption>
                    </div>
                </figure>
            </div>

            <!-- 4 Alur Kerja Integrasi Jurnal -->
            <div id="alur-ojs" class="grid-2" style="margin-top: 40px;">
                <div class="step-card" data-aos="fade-up" data-aos-delay="100" data-aos-duration="700">
                    <div class="step-index">A</div>
                    <div class="card-icon-badge badge-rose">🔌</div>
                    <h3 class="step-title">Plugin Resmi OJS 3.x &amp; Konektor Mandiri</h3>
                    <p class="step-desc">
                        Unduh paket plugin resmi AR Tilik untuk OJS 3.x &mdash; <a href="<?= base_url('integrations/ojs/artilik-ojs-plugin.zip') ?>" download="artilik-ojs-plugin.zip" style="color: #e11d48; font-weight: 700; text-decoration: underline;" data-google-vignette="false">⬇️ Unduh Plugin OJS 3 (.zip)</a> &mdash; dan ikuti Buku Panduan OJS (PDF) untuk pemasangannya. Tanpa mengubah <em>core</em> OJS, tanpa dependensi eksternal yang rumit. Integrasi manual tetap tersedia lewat pustaka <code>ARTilikOJSConnector.php</code> dan REST API v1.
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

                <div class="step-card" data-aos="fade-up" data-aos-delay="250" data-aos-duration="700">
                    <div class="step-index">C</div>
                    <div class="card-icon-badge badge-amber">🔔</div>
                    <h3 class="step-title">Hasil Otomatis Kembali ke OJS (Webhook)</h3>
                    <p class="step-desc">
                        Begitu analisis selesai, skor kemiripan, putusan, kode verifikasi, dan tautan laporan terkirim otomatis ke meja editor OJS &mdash; dan tercatat sebagai catatan diskusi editorial pada naskah yang sedang ditelaah &mdash; tanpa perlu <em>polling</em>.
                    </p>
                </div>

                <div class="step-card" data-aos="fade-up" data-aos-delay="300" data-aos-duration="700">
                    <div class="step-index">D</div>
                    <div class="card-icon-badge badge-emerald">📜</div>
                    <h3 class="step-title">Sertifikat Digital Ber-QR Code</h3>
                    <p class="step-desc">
                        Menerbitkan Sertifikat Keabsahan Orisinalitas ber-QR Code kriptografis (SHA-256) yang dapat dilampirkan langsung pada surat keputusan dewan redaksi (<em>Editorial Decision</em>) untuk mendukung akreditasi SINTA &amp; ARJUNA.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Section 2: Keamanan HMAC, Anti-SSRF, Rate Limit & Editorial Benefits -->
    <section id="keamanan-ojs" class="section" style="background: #f8fafc; border-bottom: 1px solid var(--border);">
        <div class="container">
            <div class="section-header" data-aos="fade-up" data-aos-duration="700">
                <div class="section-tag">Standar Keamanan &amp; Tata Kelola Redaksi</div>
                <h2 class="section-title">Keamanan Webhook HMAC, Anti-SSRF &amp; Kendali Kuota Jurnal</h2>
                <p class="section-desc">
                    Setiap pengiriman hasil antar-server dirancang memenuhi kaidah keamanan produksi untuk tim IT perguruan tinggi dan pengelola rumah jurnal:
                </p>
            </div>

            <div class="grid-3">
                <div class="theory-card">
                    <div class="card-icon-badge badge-indigo">🔐</div>
                    <h3 class="theory-title">Tanda Tangan HMAC-SHA256</h3>
                    <p class="theory-desc">
                        Setiap permintaan webhook dilengkapi header tanda tangan kriptografis <code>HMAC-SHA256</code> beserta stempel waktu menggunakan <em>webhook secret</em> terenkripsi per API key, sehingga plugin OJS Anda hanya menerima hasil sah dari peladen AR Tilik.
                    </p>
                </div>

                <div class="theory-card">
                    <div class="card-icon-badge badge-rose">🛡️</div>
                    <h3 class="theory-title">Perlindungan Anti-SSRF Ketat</h3>
                    <p class="theory-desc">
                        URL callback divalidasi wajib HTTPS publik dengan pemblokiran IP privat RFC1918, loopback, tautan-lokal metadata cloud, dan validasi resolusi DNS ulang saat pengiriman serta pengalihan agar jaringan OJS Anda aman dari serangan SSRF.
                    </p>
                </div>

                <div class="theory-card">
                    <div class="card-icon-badge badge-emerald">⏱️</div>
                    <h3 class="theory-title">Rate Limit 60 Permintaan / Menit</h3>
                    <p class="theory-desc">
                        Setiap API key dibatasi <strong>60 permintaan per menit</strong> secara atomik (dengan header <code>X-RateLimit-*</code> standar). Mencegah skrip berulang menguras kuota jurnal saat lonjakan submisi akhir pekan.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Section 3: FAQ Khusus OJS 3.x -->
    <section id="faq-ojs" class="faq-section">
        <div class="container">
            <div class="section-header" data-aos="fade-up" data-aos-duration="700">
                <div class="section-tag" style="color: #e11d48;">Tanya Jawab Pengelola Jurnal</div>
                <h2 class="section-title">FAQ Integrasi OJS 3.x &amp; REST API</h2>
                <p class="section-desc">
                    Jawaban ringkas untuk Chief Editor, Section Editor, dan Admin OJS kampus.
                </p>
            </div>

            <div class="faq-list">
                <details class="faq-card" open>
                    <summary class="faq-summary">
                        <span>Apakah pemasangan plugin OJS AR Tilik memerlukan perubahan kode core OJS?</span>
                        <span class="faq-icon" aria-hidden="true">▼</span>
                    </summary>
                    <div class="faq-details-body">
                        <p>
                            <strong>Tidak sama sekali.</strong> Paket plugin resmi (<code>artilik-ojs-plugin.zip</code>) dipasang langsung lewat menu <em>Website Settings &rarr; Plugins &rarr; Upload A New Plugin</em> pada OJS 3.x tanpa menyentuh berkas inti OJS maupun memasang ekstensi PHP/Python tambahan di server OJS.
                        </p>
                        <div class="faq-related-links">
                            <a href="<?= base_url('integrations/ojs/artilik-ojs-plugin.zip') ?>" download="artilik-ojs-plugin.zip" class="faq-related-link">⬇️ Unduh Plugin OJS 3 (.zip)</a>
                            <a href="<?= base_url('integrations/ojs/Panduan_Integrasi_OJS_ARTilik.pdf') ?>" target="_blank" class="faq-related-link">📕 Buka Panduan PDF</a>
                        </div>
                    </div>
                </details>

                <details class="faq-card">
                    <summary class="faq-summary">
                        <span>Bagaimana hasil pemeriksaan tampil kembali ke meja editor OJS tanpa polling?</span>
                        <span class="faq-icon" aria-hidden="true">▼</span>
                    </summary>
                    <div class="faq-details-body">
                        <p>
                            Begitu analisis Tiga Lensa selesai di AR Tilik, sistem secara otomatis mengirimkan payload JSON bertanda tangan <code>HMAC-SHA256</code> ke titik callback webhook plugin OJS Anda (dilengkapi mekanisme <em>retry</em> eksponensial jika jaringan sempat sibuk). Plugin langsung memverifikasi tanda tangan lalu mencatat skor kemiripan, putusan, kode verifikasi QR, dan tautan laporan langsung ke catatan diskusi editorial submisi OJS tersebut.
                        </p>
                    </div>
                </details>

                <details class="faq-card">
                    <summary class="faq-summary">
                        <span>Apa perbedaan menggunakan Plugin ZIP resmi dibandingkan pustaka ARTilikOJSConnector.php?</span>
                        <span class="faq-icon" aria-hidden="true">▼</span>
                    </summary>
                    <div class="faq-details-body">
                        <p>
                            <strong>Plugin ZIP resmi</strong> direkomendasikan untuk mayoritas jurnal OJS 3.x karena langsung menghadirkan form pengaturan API key, pemicu otomatis saat unggah/review, dan penerima webhook bawaan di antarmuka OJS. Sementara pustaka tunggal <code>ARTilikOJSConnector.php</code> atau <code>REST API v1</code> cocok bila tim TI kampus ingin mengintegrasikan alur khusus di portal kustom atau sistem repositori non-OJS.
                        </p>
                    </div>
                </details>

                <details class="faq-card">
                    <summary class="faq-summary">
                        <span>Bagaimana pengelolaan kuota jika universitas membawahi banyak jurnal OJS?</span>
                        <span class="faq-icon" aria-hidden="true">▼</span>
                    </summary>
                    <div class="faq-details-body">
                        <p>
                            Pengelola dapat menerbitkan kunci API terpisah untuk setiap jurnal dari portal AR Tilik agar pemakaian dan webhook tiap jurnal terpantau independen, disertai batas aman 60 permintaan/menit per kunci API. Tersedia Paket Kampus &amp; Rumah Jurnal (LPPM) dengan opsi <em>private cloud</em> atau <em>on-premise</em>.
                        </p>
                        <div class="faq-related-links">
                            <a href="<?= base_url('harga#kampus') ?>" class="faq-related-link">🏛️ Lihat Paket Kampus &amp; Jurnal</a>
                        </div>
                    </div>
                </details>
            </div>
        </div>
    </section>

    <!-- Cross-Link Band (/ojs -> /harga paket kampus & -> unduhan plugin ZIP) -->
    <section class="cross-link-band google-anno-skip">
        <div class="container cross-link-flex">
            <div>
                <h2 style="font-size: 20px; font-weight: 800; color: var(--dark); margin-bottom: 4px;">
                    Siap Menghubungkan Rumah Jurnal Anda dengan AR Tilik?
                </h2>
                <p style="font-size: 14px; color: var(--text-muted); margin: 0;">
                    Unduh paket plugin OJS 3.x sekarang atau lihat opsi Paket Kampus &amp; OJS Jurnal (mulai Rp 450.000/bulan).
                </p>
            </div>
            <div style="display: flex; flex-wrap: wrap; gap: 10px;">
                <a href="<?= base_url('integrations/ojs/artilik-ojs-plugin.zip') ?>" download="artilik-ojs-plugin.zip" class="btn btn-primary" style="background: #e11d48; border-color: #be123c;" data-google-vignette="false">
                    ⬇️ Unduh Plugin OJS 3 (.zip)
                </a>
                <a href="<?= base_url('harga#kampus') ?>" class="btn btn-outline" data-google-vignette="false">
                    Lihat Paket Kampus &amp; Jurnal &rarr;
                </a>
                <button type="button" onclick="openContactModal('Konsultasi Institusi & Lisensi Kampus')" class="btn btn-outline" data-google-vignette="false">
                    💬 Konsultasi Tim Teknis
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
