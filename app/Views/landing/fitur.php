<?php
$currentUser = $currentUser ?? null;
$visitorStats = $visitorStats ?? ['total_unique' => 1, 'today_unique' => 1, 'total_hits' => 1];
?>
<?= view('landing/partials/head', [
    'pageTitle'       => 'Fitur & Arsitektur Tiga Lensa AR Tilik — Three-Layer Separation & Dual-Reader',
    'pageDescription' => 'Penjelasan lengkap Arsitektur Tiga Lensa (5-Gram, TF-IDF, Semantik MiniLM-L12 Qdrant), Prinsip Pemisahan Tiga Lapis, Whitepaper Komparatif, 6 Inovasi Mesin Forensik, serta Dual-Reader & Revisi v1 vs v2.',
    'canonicalUrl'    => base_url('fitur'),
    'enableAdSense'   => true,
    'activeAdSlots'   => [1, 2],
]) ?>

<?= view('landing/partials/navbar', [
    'activeNav'   => 'fitur',
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
                    <li aria-current="page">Fitur &amp; Arsitektur Mesin</li>
                </ol>
            </nav>

            <h1 class="subpage-title">
                Arsitektur <span>Tiga Lensa, Tiga Lapis Bukti</span> &amp; Audit Forensik
            </h1>
            <p class="subpage-subtitle">
                Dirancang bagi mahasiswa, dosen pembimbing, dan penguji yang menginginkan pembuktian kesepadanan yang transparan, terkalibrasi secara empiris, dan mudah ditelusuri kalimat demi kalimat.
            </p>

            <div class="subpage-actions">
                <?php if ($currentUser) : ?>
                    <a href="<?= base_url('dashboard') ?>" class="btn btn-primary" data-google-vignette="false">
                        🚀 Buka Dashboard Pemeriksaan
                    </a>
                <?php else : ?>
                    <button type="button" onclick="openAuthModal('register')" class="btn btn-primary" data-google-vignette="false">
                        ✨ Coba Cek Gratis (3x/Minggu)
                    </button>
                <?php endif; ?>
                <a href="<?= base_url('benchmark') ?>" class="btn btn-outline" data-google-vignette="false">
                    🔬 Lihat Benchmark Terbuka
                </a>
                <a href="<?= base_url('cek-sitasi') ?>" class="btn btn-outline" style="border-color: #0284c7; color: #0369a1;" data-google-vignette="false">
                    🔎 Cek Sitasi Gratis
                </a>
            </div>

            <div class="subpage-jump-nav" aria-label="Navigasi Seksi Fitur">
                <a href="#teori" class="subpage-jump-chip">Tiga Lensa &amp; Tiga Lapis</a>
                <a href="#riset-komparasi" class="subpage-jump-chip">Whitepaper &amp; Riset</a>
                <a href="#fitur-unggulan" class="subpage-jump-chip">6 Inovasi Mesin</a>
                <a href="#cara-kerja" class="subpage-jump-chip">Alur Kerja 4 Langkah</a>
                <a href="#dual-reader" class="subpage-jump-chip">Dual-Reader &amp; Revisi v1 vs v2</a>
            </div>
        </div>
    </section>

    <!-- Section 1: Detail Tiga Lensa & Tiga Lapis -->
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
                    <h3 class="theory-title">Arsitektur Tiga Lensa</h3>
                    <p class="theory-desc">
                        Membedakan secara matematis antara <strong>Exact Matcher</strong> (5-gram shingle), <strong>Sparse TF-IDF Leksikal</strong>, dan <strong>Lensa Semantik Terkalibrasi</strong> (Pilar Inti 3) untuk memisahkan karya orisinal dari parafrase dan alih bahasa.
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
                        Model komputasi probabilistik terkalibrasi yang mensintesis 13 dimensi bukti keabsahan &mdash; termasuk skor kemiripan makna sebagai penggerak terbesar, estilometri panjang kalimat (<em>burstiness</em>), frasa baku metodologi, dan konsistensi naskah secara non-linear tanpa vonis kaku.
                    </p>
                </div>
            </div>

            <!-- Spotlight Card: Visualisasi Tiga Lensa Engine -->
            <div class="spotlight-card" data-aos="fade-up" data-aos-duration="800">
                <div class="spotlight-img-wrap" data-aos="fade-right" data-aos-delay="100" data-aos-duration="800">
                    <picture>
                        <source srcset="<?= base_url('images/three_lens_concept.webp') ?>" type="image/webp">
                        <img src="<?= base_url('images/three_lens_concept.jpg') ?>" alt="Konsep Arsitektur Tiga Lensa AR Tilik" loading="lazy" width="800" height="450">
                    </picture>
                    <div class="spotlight-caption">
                        <span>LENSA 1: Sequential 5-Gram</span>
                        <span>LENSA 2: Lexical TF-IDF</span>
                        <span>LENSA 3: Semantik Qdrant</span>
                    </div>
                </div>

                <div data-aos="fade-left" data-aos-delay="150" data-aos-duration="800">
                    <div style="font-size: 11px; text-transform: uppercase; font-weight: 800; color: var(--primary); letter-spacing: 1px; margin-bottom: 6px;">Arsitektur Komparasi Presisi</div>
                    <h3 style="font-size: 24px; font-weight: 800; color: var(--dark); margin-bottom: 12px; letter-spacing: -0.5px;">Arsitektur Tiga Lensa</h3>
                    <p style="font-size: 14px; color: var(--text-muted); line-height: 1.6; margin-bottom: 16px;">
                        AR Tilik menolak pendekatan monolitik. Kami memakai tiga lensa matematis yang dipadukan secara transparan: Lensa 1 Exact Matcher (5-gram shingle) untuk tumpang tindih kata berurutan; Lensa 2 TF-IDF Leksikal untuk orientasi kosakata dan topik; dan Lensa 3 Semantik Terkalibrasi (Pilar Inti 3) untuk menangkap parafrase dan terjemahan yang lolos dari kedua lensa leksikal.
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

                        <div class="lens-item">
                            <div class="lens-item-badge badge-purple">🧠</div>
                            <div>
                                <strong style="font-size: 13px; color: var(--dark);">Lensa Semantik (MiniLM-L12, 384 dimensi, Qdrant)</strong>
                                <p style="font-size: 12px; color: var(--text-muted); margin-top: 2px;">
                                    Mengukur kesepadanan makna antar pasase, termasuk parafrase berat dan alih bahasa, dengan ambang baku 0,75 dan mode penyelidikan 0,70.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 3-Layer Separation Explanation Box -->
            <div id="tiga-lapis" class="layer-box" data-aos="fade-up" data-aos-duration="800">
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
                        <strong style="color: #059669;">Academic Context (Konteks Sitasi &amp; Kutipan)</strong>
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

    <!-- Section 1B: Riset Komparatif & Seri Whitepaper -->
    <section id="riset-komparasi" class="section" style="background: #ffffff; border-top: 1px solid var(--border);">
        <div class="container">
            <div class="section-header" data-aos="fade-up" data-aos-duration="700">
                <div class="section-tag" style="background: #fff7ed; color: #ea580c; border: 1px solid #fed7aa;">Whitepaper &amp; Riset Komparatif</div>
                <h2 class="section-title">Bukan Lomba Menebak AI: Mengapa Kami Mengaudit Bukti Nyata?</h2>
                <p class="section-desc">
                    Detektor AI probabilitas seperti ZeroGPT menaksir <em>&ldquo;apakah teks ini dibuat AI?&rdquo;</em>, sementara AR Tilik memeriksa <em>&ldquo;apa bukti empiris yang dapat diverifikasi dari naskah ini?&rdquo;</em>. Pendekatan berbasis bukti menghindarkan tuduhan sepihak pada tulisan formal dan penulis non-native:
                </p>
            </div>

            <div class="grid-3" style="margin-bottom: 36px;">
                <div class="theory-card" style="border-top: 4px solid #ea580c;" data-aos="fade-up" data-aos-delay="100" data-aos-duration="700">
                    <div class="card-icon-badge" style="background: #fff7ed; color: #ea580c;">⚖️</div>
                    <h3 class="theory-title">Objek Ukur Berbeda</h3>
                    <p class="theory-desc">
                        Detektor konvensional bekerja lewat inferensi statistik gaya bahasa tertutup. AR Tilik menyajikan <strong>4 jenis bukti yang dapat dilacak</strong>: frasa sekuensial, orientasi leksikal, pasase beririsan, dan validasi keaslian rujukan.
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
                        AI generatif kerap memfabrikasi daftar pustaka halusinasi. AR Tilik mengaudit langsung ke pangkalan data <strong>Crossref &amp; OpenAlex</strong> untuk membuktikan apakah rujukan benar-benar terbit di dunia nyata &mdash; fitur yang tidak dimiliki detektor teks murni.
                    </p>
                </div>
            </div>

            <!-- Panel Kutipan Whitepaper -->
            <div style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); border-radius: 20px; padding: clamp(24px, 4vw, 40px); color: #ffffff; display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 28px; align-items: center; box-shadow: 0 16px 40px -10px rgba(15, 23, 42, 0.2);" data-aos="fade-up" data-aos-duration="800">
                <div>
                    <div style="display: inline-flex; align-items: center; gap: 8px; font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; color: #fdba74; margin-bottom: 8px;">
                        <span>📖</span> Seri Whitepaper Riset Akademik 2026
                    </div>
                    <h3 style="font-size: clamp(19px, 2.2vw, 26px); font-weight: 800; line-height: 1.3; margin-bottom: 12px; color: #ffffff;">
                        &ldquo;Dalam integritas akademik, alat terbaik bukan yang paling yakin&mdash;melainkan yang paling mudah dibantah, diperiksa, dan diperbaiki.&rdquo;
                    </h3>
                    <p style="font-size: 14px; color: #94a3b8; line-height: 1.6; margin: 0;">
                        Kami memublikasikan hasil riset dan benchmark empiris terbuka: benchmark korpus akademik Bahasa Indonesia (termasuk 14 kasus mismatch), telaah rujukan nyata vs Turnitin, serta analisis pembuktian empiris vs deteksi AI ZeroGPT.
                    </p>
                </div>
                <div style="display: flex; flex-direction: column; gap: 10px; align-items: stretch; justify-content: center;">
                    <a href="<?= base_url('benchmark') ?>" class="btn btn-primary" style="background: #4338ca; border-color: #4338ca; padding: 13px 20px; font-size: 13.5px; font-weight: 700; text-align: center; border-radius: 10px; box-shadow: 0 6px 16px rgba(67, 56, 202, 0.35); text-decoration: none;" data-google-vignette="false">
                        <span>🔬 Benchmark Terbuka Naskah Indonesia</span>
                        <span style="margin-left: 6px;">&rarr;</span>
                    </a>
                    <a href="<?= base_url('riset/ar-tilik-vs-turnitin') ?>" class="btn btn-primary" style="background: #0f766e; border-color: #0f766e; padding: 13px 20px; font-size: 13.5px; font-weight: 700; text-align: center; border-radius: 10px; box-shadow: 0 6px 16px rgba(15, 118, 110, 0.35); text-decoration: none;" data-google-vignette="false">
                        <span>🏛️ Whitepaper: AR Tilik vs Turnitin</span>
                        <span style="margin-left: 6px;">&rarr;</span>
                    </a>
                    <a href="<?= base_url('riset/ar-tilik-vs-zerogpt') ?>" class="btn btn-primary" style="background: #ea580c; border-color: #ea580c; padding: 13px 20px; font-size: 13.5px; font-weight: 700; text-align: center; border-radius: 10px; box-shadow: 0 6px 16px rgba(234, 88, 12, 0.35); text-decoration: none;" data-google-vignette="false">
                        <span>📊 Whitepaper: AR Tilik vs ZeroGPT</span>
                        <span style="margin-left: 6px;">&rarr;</span>
                    </a>
                    <a href="<?= base_url('dashboard') ?>" class="btn" style="background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.18); color: #ffffff; padding: 11px 18px; font-size: 13px; font-weight: 600; text-align: center; border-radius: 10px; text-decoration: none;" data-google-vignette="false">
                        Uji Coba Audit Naskah Mandiri
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Kotak Iklan PARIWARA 1 (setelah panel kutipan whitepaper) -->
    <?= view('landing/partials/ad_slot', ['slotNum' => 1, 'ariaLabel' => 'Iklan Sponsor AR Tilik']) ?>

    <!-- Section 2: 6 Inovasi Mesin -->
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
                    <div style="font-size: 11px; font-weight: 800; color: #e11d48; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px;">5 Pasangan Bahasa Tervalidasi</div>
                    <h3 class="theory-title">Deteksi Plagiarisme Lintas Bahasa (5 Pasangan Tervalidasi)</h3>
                    <p class="theory-desc">
                        Membongkar modus plagiarisme terjemahan tanpa atribusi dari lima pasangan bahasa yang tervalidasi empiris (Inggris, Mandarin, Jerman, Belanda, Arab ⇄ Indonesia; masing-masing 10 kasus uji dengan interval kepercayaan Wilson 95%). Model multibahasa kami memetakan naskah asing ke rujukan Indonesia dan Inggris di ruang vektor bersama, sehingga kesepadanan makna dan alur argumen tetap terdeteksi sekalipun naskah telah dialihbahasakan. Catatan jujur: lencana pasangan bahasa menandakan kemampuan deteksi terjemahan yang terbukti, bukan kepemilikan perpustakaan teks pasif berbahasa Mandarin, Jerman, atau Belanda.
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
                        Menganalisis koherensi sitasi (APA/IEEE/Harvard) secara otomatis. Mendeteksi <strong>Ghost Citations</strong> (nama penulis dikutip di badan teks tapi raib dari referensi) dan <strong>Orphan References</strong> (tercantum di bibliografi namun tidak pernah dirujuk dalam tubuh artikel), sekaligus verifikasi nyata ke Crossref &amp; OpenAlex.
                    </p>
                    <a href="<?= base_url('cek-sitasi') ?>" style="display: inline-flex; align-items: center; gap: 6px; margin-top: 12px; font-size: 13px; font-weight: 700; color: #059669; text-decoration: underline;">
                        Coba Cek Sitasi Gratis (Tanpa Login) &rarr;
                    </a>
                </div>

                <!-- Fitur 6: OAI-PMH & Academic Repository Harvester -->
                <div class="theory-card" data-aos="fade-up" data-aos-delay="350" data-aos-duration="700">
                    <div class="card-icon-badge badge-cyan">🦅</div>
                    <div style="font-size: 11px; font-weight: 800; color: #0284c7; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px;">Korpus Nasional &amp; Provenance</div>
                    <h3 class="theory-title">Pemanen Literatur &amp; Repositori Skripsi Kampus (OAI-PMH 2.0)</h3>
                    <p class="theory-desc">
                        Memanen literatur terbuka lewat protokol OAI-PMH 2.0 &mdash; artikel jurnal dari GARUDA Kemdiktisaintek dan skripsi/tesis dari repositori kampus &mdash; serta menemukan hingga 5 karya sebidang lewat OpenAlex pada setiap pemeriksaan. Setiap rujukan repositori menampilkan nama institusi dan tautan ke sumber aslinya, sehingga asal setiap kecocokan dapat dilacak.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Section 3: Cara Penggunaan & Dual-Reader -->
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
                        Kirimkan naskah dalam format PDF, DOCX, TXT, atau LaTeX (.tex) (maks. 64 MB). PDF hasil pindaian dibaca dengan OCR, dan sistem secara otomatis mengekstrak teks, menormalisasi tanda baca, dan menghitung shingle sekuensial.
                    </p>
                </div>

                <div class="step-card" data-aos="fade-up" data-aos-delay="300" data-aos-duration="700">
                    <div class="step-index">03</div>
                    <div class="card-icon-badge badge-emerald">⚡</div>
                    <h3 class="step-title">Pemrosesan Asinkron</h3>
                    <p class="step-desc">
                        Worker background membandingkan naskah terhadap korpus institusi dan jurnal terbuka secara asinkron &mdash; Anda tidak perlu menunggu di depan layar. Laporan lengkap biasanya terbit dalam beberapa menit.
                    </p>
                </div>

                <div class="step-card" data-aos="fade-up" data-aos-delay="400" data-aos-duration="700">
                    <div class="step-index">04</div>
                    <div class="card-icon-badge badge-amber">🛡️</div>
                    <h3 class="step-title">Telaah Interaktif &amp; Sertifikat QR</h3>
                    <p class="step-desc">
                        Buka perbandingan di <strong>Layar Ganda (Dual-Reader)</strong> dengan penanda warna, bandingkan progres revisi v1 vs v2, gunakan filter dinamis pengabaian kutipan, dan unduh sertifikat resmi ber-QR Code.
                    </p>
                </div>
            </div>

            <!-- Showcase: Dual-Reader Interface Mockup -->
            <div id="dual-reader" class="dual-reader-showcase">
                <div class="dual-reader-header">
                    <span class="dual-reader-tag">Antarmuka Unggulan Penelaah</span>
                    <h3 class="dual-reader-title">Dual-Reader: Komparasi Berdampingan &amp; Pelacak Revisi v1 vs v2</h3>
                    <p class="dual-reader-desc">
                        Tidak perlu lagi mencetak lembaran kertas atau berpindah tab secara melelahkan. Periksa kalimat sumber dan naskah mahasiswa secara berdampingan lengkap dengan tanda kutip, sitasi, perbandingan antarversi revisi, serta verifikasi sertifikat digital.
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
                        <h4 class="feature-dark-title">Sinkronisasi Gulir &amp; Perbandingan Revisi (v1 vs v2)</h4>
                        <p class="feature-dark-desc">
                            Mengklik pasase mirip langsung menyoroti rujukan pembanding di panel kanan. Pada unggah revisi, sistem langsung memetakan pasase mana yang selesai/berkurang, tetap sama, meluas, atau baru muncul lengkap dengan selisih skor sementara laporan versi sebelumnya tetap tersimpan utuh.
                        </p>
                    </div>

                    <div class="feature-card-dark" data-aos="fade-up" data-aos-delay="200" data-aos-duration="600">
                        <div class="feature-dark-icon">🔍</div>
                        <h4 class="feature-dark-title">Filter Sitasi &amp; Pustaka Dinamis</h4>
                        <p class="feature-dark-desc">
                            Sakelar interaktif untuk mengecualikan kutipan bertanda petik, bibliografi, frasa baku, dan formalitas depan &mdash; persentase di layar langsung dihitung ulang, dan angka yang sama persis tercetak di PDF resmi beserta Catatan Transparansi Pengecualian.
                        </p>
                    </div>

                    <div class="feature-card-dark" data-aos="fade-up" data-aos-delay="300" data-aos-duration="600">
                        <div class="feature-dark-icon">🛡️</div>
                        <h4 class="feature-dark-title">Verifikasi QR Code Kriptografis</h4>
                        <p class="feature-dark-desc">
                            Setiap naskah memiliki hash SHA-256 unik dan QR code yang dapat dipindai oleh dewan sidang skripsi atau editor jurnal di portal publik untuk memeriksa keabsahan laporan.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Kotak Iklan PARIWARA 2 (setelah Dual-Reader) -->
    <?= view('landing/partials/ad_slot', ['slotNum' => 2, 'ariaLabel' => 'Iklan Sponsor AR Tilik']) ?>

    <!-- Cross-Link Band (/fitur -> /harga dan -> /benchmark) -->
    <section class="cross-link-band google-anno-skip">
        <div class="container cross-link-flex">
            <div>
                <h2 style="font-size: 20px; font-weight: 800; color: var(--dark); margin-bottom: 4px;">
                    Yakin dengan Metodologinya? Mulai Periksa atau Bandingkan Paket
                </h2>
                <p style="font-size: 14px; color: var(--text-muted); margin: 0;">
                    Setiap akun baru otomatis mendapat 3 cek naskah gratis tiap minggu, atau lihat hasil evaluasi empiris terbuka kami.
                </p>
            </div>
            <div style="display: flex; flex-wrap: wrap; gap: 10px;">
                <?php if ($currentUser) : ?>
                    <a href="<?= base_url('dashboard') ?>" class="btn btn-primary" data-google-vignette="false">Buka Dashboard</a>
                <?php else : ?>
                    <button type="button" onclick="openAuthModal('register')" class="btn btn-primary" data-google-vignette="false">✨ Coba Cek Gratis</button>
                <?php endif; ?>
                <a href="<?= base_url('harga') ?>" class="btn btn-outline" data-google-vignette="false">Lihat Paket &amp; Harga &rarr;</a>
                <a href="<?= base_url('benchmark') ?>" class="btn btn-outline" data-google-vignette="false">🔬 Lihat Benchmark</a>
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
