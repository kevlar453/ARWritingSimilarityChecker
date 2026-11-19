<?php
$currentUser = $currentUser ?? null;
$visitorStats = $visitorStats ?? ['total_unique' => 1, 'today_unique' => 1, 'total_hits' => 1];
?>
    <!-- Footer -->
    <footer class="footer google-anno-skip">
        <div class="container">
            <div class="footer-grid">
                <div>
                    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 14px;">
                        <img src="<?= base_url('images/logo.png') ?>" alt="AR Tilik Logo" width="34" height="34" style="width: 34px; height: 34px; object-fit: contain; border-radius: 8px;">
                        <span style="font-size: 20px; font-weight: 800; color: #ffffff; letter-spacing: -0.5px;">AR Tilik</span>
                    </div>
                    <p style="line-height: 1.65; max-width: 380px; margin-bottom: 14px;">
                        Sistem Pemeriksa Kesepadanan Naskah Akademik Berbasis Bukti Transparan. Dikembangkan dengan dedikasi untuk keadilan dan integritas ilmiah perguruan tinggi di Indonesia.
                    </p>
                    <div style="font-size: 13px; color: #cbd5e1; line-height: 1.8;">
                        <div>🌐 <a href="https://tilik.arsetontong.top" style="color: #93c5fd; font-weight: 600;">tilik.arsetontong.top</a></div>
                        <div>💬 WhatsApp: <a href="https://wa.me/6285138862032" target="_blank" rel="noopener noreferrer" style="color: #86efac; font-weight: 600;">0851 3886 2032</a></div>
                        <div>📧 Email: <a href="mailto:tilik@arsetontong.top" style="color: #cbd5e1; font-weight: 600;">tilik@arsetontong.top</a></div>
                    </div>
                </div>

                <div>
                    <strong style="color: #ffffff; display: block; margin-bottom: 14px;">Halaman Utama</strong>
                    <ul class="footer-links">
                        <li><a href="<?= base_url() ?>">Beranda</a></li>
                        <li><a href="<?= base_url('fitur') ?>">Fitur &amp; Mesin Tiga Lensa</a></li>
                        <li><a href="<?= base_url('ojs') ?>">Integrasi OJS 3.x Jurnal</a></li>
                        <li><a href="<?= base_url('harga') ?>">Harga &amp; Pilihan Paket</a></li>
                        <li><a href="<?= base_url('cek-sitasi') ?>" style="color: #7dd3fc; font-weight: 600;">🔎 Cek Sitasi Gratis (Tanpa Login)</a></li>
                        <li><a href="<?= base_url('tentang') ?>">Tentang &amp; Testimoni</a></li>
                        <li><a href="<?= base_url('faq') ?>">Tanya Jawab (FAQ)</a></li>
                    </ul>
                </div>

                <div>
                    <strong style="color: #ffffff; display: block; margin-bottom: 14px;">Riset &amp; Ekosistem</strong>
                    <ul class="footer-links">
                        <li><a href="<?= base_url('benchmark') ?>" style="color: #c4b5fd; font-weight: 600;">🔬 Benchmark Terbuka Indonesia</a></li>
                        <li><a href="<?= base_url('riset/ar-tilik-vs-turnitin') ?>" style="color: #38bdf8; font-weight: 600;">🏛️ Whitepaper: AR Tilik vs Turnitin</a></li>
                        <li><a href="<?= base_url('riset/ar-tilik-vs-zerogpt') ?>" style="color: #fdba74; font-weight: 600;">📊 Whitepaper: AR Tilik vs ZeroGPT</a></li>
                        <li><a href="<?= base_url('verify') ?>">Portal Verifikasi Sertifikat QR</a></li>
                        <li><a href="<?= base_url('ambassador') ?>">Program Duta Kampus (Afiliasi)</a></li>
                        <li><a href="<?= base_url('tentang#kuota-korpus') ?>">Gotong Royong Korpus</a></li>
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
                        <li><a href="<?= base_url('integrations/ojs/artilik-ojs-plugin.zip') ?>" download="artilik-ojs-plugin.zip">Plugin Resmi OJS 3.x (.zip)</a></li>
                        <li><a href="<?= base_url('integrations/ojs/Panduan_Integrasi_OJS_ARTilik.pdf') ?>" target="_blank" download="Panduan_Integrasi_OJS_ARTilik.pdf">Buku Panduan OJS (PDF)</a></li>
                        <li><a href="<?= base_url('docs/Brosur_Eksekutif_ARTilik.pdf') ?>" target="_blank" download="Brosur_Eksekutif_ARTilik.pdf">Brosur Eksekutif Institusi (PDF)</a></li>
                        <li><a href="<?= base_url('integrations/ojs/connector') ?>" download="ARTilikOJSConnector.php">Konektor PHP OJS 3.x</a></li>
                        <li><a href="<?= base_url('integrations/ojs/README.md') ?>" target="_blank">Spesifikasi REST API v1</a></li>
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
                AR Tilik &copy; <?= date('Y') ?> &bull; Academic Writing Similarity &amp; Evidence Checker &bull; Kontak WhatsApp: <a href="https://wa.me/6285138862032" target="_blank" rel="noopener noreferrer" style="color: #cbd5e1; font-weight: 600;">0851 3886 2032</a> &bull; Email: <a href="mailto:tilik@arsetontong.top" style="color: #cbd5e1; font-weight: 600;">tilik@arsetontong.top</a> &bull; <a href="https://www.arsetontong.top" target="_blank" rel="noopener noreferrer" style="color: #94a3b8; text-decoration: underline; text-underline-offset: 2px;">Powered by ARSetontong</a>
            </div>
        </div>
    </footer>
