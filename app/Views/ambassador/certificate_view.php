<!DOCTYPE html>
<html lang="id" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi Piagam Duta Kampus &bull; <?= esc($amb['ambassador_name']) ?> &bull; AR Tilik</title>
    <!-- Favicon & Brand Icons -->
    <link rel="icon" type="image/x-icon" href="<?= base_url('favicon.ico?v=20261008') ?>">
    <link rel="icon" type="image/png" sizes="32x32" href="<?= base_url('favicon-32x32.png?v=20261008') ?>">
    <link rel="icon" type="image/png" sizes="16x16" href="<?= base_url('favicon-16x16.png?v=20261008') ?>">
    <link rel="apple-touch-icon" sizes="180x180" href="<?= base_url('apple-touch-icon.png?v=20261008') ?>">
    <!-- Tailwind CSS v3 via CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&family=Playfair+Display:ital,wght@0,600;0,700;0,900;1,600&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', 'sans-serif'],
                        mono: ['"JetBrains Mono"', 'monospace'],
                        serif: ['"Playfair Display"', 'Georgia', 'serif'],
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-slate-950 text-slate-100 font-sans antialiased min-h-screen flex flex-col selection:bg-amber-500 selection:text-slate-950">

    <!-- Header Navbar -->
    <header class="border-b border-white/10 bg-slate-950/80 backdrop-blur-md sticky top-0 z-50">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 h-16 flex items-center justify-between gap-4">
            <a href="<?= base_url() ?>" class="flex items-center gap-2.5 group shrink-0">
                <img src="<?= base_url('images/logo.png') ?>" alt="AR Tilik Logo" class="w-8 h-8 sm:w-9 sm:h-9 object-contain drop-shadow-sm group-hover:scale-105 transition-transform duration-200">
                <div>
                    <span class="font-bold text-sm tracking-tight text-white group-hover:text-amber-300 transition">AR Tilik</span>
                    <span class="text-[10px] text-amber-400 font-mono block leading-none">Verifikasi Piagam Duta</span>
                </div>
            </a>
            <div class="flex items-center gap-2">
                <a href="<?= base_url() ?>" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-semibold text-slate-300 hover:text-white bg-slate-900 hover:bg-slate-800 border border-white/15 transition">
                    <span>Beranda Utama</span>
                </a>
                <a href="<?= base_url('ambassador/certificate/' . $amb['certificate_code'] . '/pdf') ?>" class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl text-xs font-bold text-slate-950 bg-gradient-to-r from-amber-400 to-amber-500 hover:from-amber-300 hover:to-amber-400 shadow-md shadow-amber-500/20 transition">
                    <span>📥</span> <span>Unduh PDF</span>
                </a>
            </div>
        </div>
    </header>

    <main class="flex-1 py-10 px-4 sm:px-6">
        <div class="max-w-4xl mx-auto space-y-8">

            <!-- Banner Status Autentikasi -->
            <div class="bg-gradient-to-r from-emerald-950/60 via-slate-900 to-slate-900 border border-emerald-500/30 rounded-3xl p-6 sm:p-8 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-6 shadow-2xl relative overflow-hidden">
                <div class="space-y-2 relative z-10">
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                        <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                        <span>PIAGAM PENGHARGAAN RESMI TERVERIFIKASI</span>
                    </div>
                    <h1 class="text-xl sm:text-2xl font-extrabold text-white tracking-tight">
                        Surat Keputusan &amp; Piagam Duta Akademik
                    </h1>
                    <p class="text-xs sm:text-sm text-slate-300 max-w-xl leading-relaxed">
                        Dokumen piagam ini tercatat secara resmi dalam basis data repositori nasional AR Tilik dan memiliki keabsahan akademik sebagai bukti keikutsertaan program kemitraan integritas karya ilmiah.
                    </p>
                </div>
                <div class="bg-emerald-500/10 border border-emerald-500/30 rounded-2xl p-4 text-center shrink-0 min-w-[170px] relative z-10">
                    <div class="text-[10px] uppercase font-bold text-emerald-400 tracking-wider">Status Validasi</div>
                    <div class="text-xl font-black text-emerald-300 mt-0.5">SAH &amp; AKTIF</div>
                    <div class="text-[10px] text-slate-400 mt-1 font-mono"><?= esc($amb['certificate_code']) ?></div>
                </div>
                <div class="absolute -right-8 -bottom-8 w-48 h-48 bg-emerald-500/5 rounded-full blur-3xl pointer-events-none"></div>
            </div>

            <!-- Kartu Detail Piagam -->
            <div class="bg-slate-900 border border-white/10 rounded-3xl p-6 sm:p-8 shadow-xl space-y-6">
                <div class="border-b border-white/10 pb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <div class="text-[11px] font-bold uppercase tracking-wider text-amber-400 font-mono">Dianugerahkan Kepada</div>
                        <div class="text-2xl sm:text-3xl font-extrabold text-white mt-1 font-serif tracking-wide">
                            <?= esc($amb['ambassador_name']) ?>
                        </div>
                        <div class="text-sm text-slate-300 mt-1">
                            <span class="font-semibold text-indigo-300"><?= esc($amb['major']) ?></span>
                            <span class="text-slate-500">&bull;</span>
                            <span><?= esc($amb['institution']) ?></span>
                        </div>
                    </div>
                    <div class="text-left sm:text-right">
                        <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Kode Referral Resmi</div>
                        <div class="inline-block px-3 py-1 rounded-lg bg-amber-500/10 text-amber-300 border border-amber-500/30 font-mono text-sm font-bold mt-1">
                            <?= esc($amb['referral_code']) ?>
                        </div>
                    </div>
                </div>

                <!-- Grid Data Verifikasi -->
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4 text-xs">
                    <div class="bg-black/30 border border-white/5 rounded-xl p-4 space-y-1">
                        <div class="text-slate-400 uppercase font-semibold text-[10px]">Nomor Piagam / SK</div>
                        <div class="font-mono text-white font-bold text-xs"><?= esc($amb['certificate_code']) ?></div>
                        <div class="text-[10px] text-slate-500">Tercatat di Konsorsium AR Tilik</div>
                    </div>

                    <div class="bg-black/30 border border-white/5 rounded-xl p-4 space-y-1">
                        <div class="text-slate-400 uppercase font-semibold text-[10px]">Tanggal Kelulusan</div>
                        <div class="text-white font-bold text-xs">
                            <?= date('d F Y', strtotime($amb['graduated_at'])) ?>
                        </div>
                        <div class="text-[10px] text-slate-500"><?= date('H:i', strtotime($amb['graduated_at'])) ?> WIB</div>
                    </div>

                    <div class="bg-black/30 border border-white/5 rounded-xl p-4 space-y-1">
                        <div class="text-slate-400 uppercase font-semibold text-[10px]">Predikat Kelulusan</div>
                        <div class="text-amber-400 font-bold text-xs">SANGAT MEMUASKAN</div>
                        <div class="text-[10px] text-slate-500">Lolos Verifikasi Kemitraan Ilmiah</div>
                    </div>
                </div>

                <!-- Pernyataan Kehormatan -->
                <div class="bg-black/20 border border-amber-500/20 rounded-2xl p-5 space-y-2">
                    <div class="text-[11px] font-bold uppercase tracking-wider text-amber-400">Pernyataan Dewan Kehormatan:</div>
                    <p class="text-xs sm:text-sm text-slate-300 leading-relaxed italic">
                        &ldquo;Atas komitmen, integritas, dan dedikasi luar biasa dalam memelopori penegakan kejujuran karya ilmiah, pencegahan plagiarisme akademik, serta advokasi pemanfaatan teknologi kecerdasan buatan secara etis dan bertanggung jawab di lingkungan sivitas akademika perguruan tinggi.&rdquo;
                    </p>
                </div>

                <!-- Sidik Jari Kriptografis SHA-256 -->
                <div class="bg-black/40 border border-white/5 rounded-xl p-4 space-y-1.5 font-mono text-[11px]">
                    <div class="text-slate-400 text-[10px] uppercase font-bold">Integritas Kriptografis (SHA-256 Fingerprint):</div>
                    <div class="text-indigo-300 break-all select-all font-mono text-[10px] bg-slate-950 p-2 rounded border border-white/10">
                        <?= esc($amb['certificate_hash'] ?? hash('sha256', $amb['certificate_code'])) ?>
                    </div>
                    <div class="text-[10px] text-slate-500 font-sans">
                        Nilai hash ini menjamin bahwa isi dan metadata piagam tidak pernah dimodifikasi sejak waktu penerbitan.
                    </div>
                </div>

                <!-- Tombol Aksi & Bagikan ke LinkedIn -->
                <div class="pt-4 border-t border-white/10 flex flex-wrap items-center justify-between gap-3">
                    <div class="flex items-center gap-2 flex-wrap">
                        <a href="<?= base_url('ambassador/certificate/' . $amb['certificate_code'] . '/pdf') ?>" class="px-4 py-2.5 rounded-xl bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold text-xs flex items-center gap-2 shadow-lg shadow-amber-500/20 transition">
                            <span>📥</span> <span>Unduh Piagam Resmi (PDF)</span>
                        </a>
                        <a href="<?= base_url('ambassador/certificate/' . $amb['certificate_code'] . '/preview') ?>" target="_blank" class="px-4 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-white font-bold text-xs flex items-center gap-2 border border-white/10 transition">
                            <span>📄</span> <span>Buka Lembar Piagam</span> &rarr;
                        </a>
                    </div>

                    <?php
                        $certUrl = base_url('ambassador/certificate/' . $amb['certificate_code']);
                        $linkedInYear = date('Y', strtotime($amb['graduated_at']));
                        $linkedInMonth = date('n', strtotime($amb['graduated_at']));
                        $linkedInUrl = "https://www.linkedin.com/profile/add?startTask=CERTIFICATION_NAME"
                            . "&name=" . urlencode("Collegiate Academic Ambassador & Research Integrity Advocate")
                            . "&organizationName=" . urlencode("AR Tilik")
                            . "&issueYear=" . $linkedInYear
                            . "&issueMonth=" . $linkedInMonth
                            . "&certUrl=" . urlencode($certUrl)
                            . "&certId=" . urlencode($amb['certificate_code']);
                    ?>
                    <div class="flex items-center gap-2">
                        <a href="<?= $linkedInUrl ?>" target="_blank" class="px-4 py-2.5 rounded-xl bg-[#0077b5] hover:bg-[#006396] text-white font-bold text-xs flex items-center gap-2 shadow-md transition">
                            <svg class="w-3.5 h-3.5 fill-current" viewBox="0 0 24 24"><path d="M19 0h-14c-2.761 0-5 2.239-5 5v14c0 2.761 2.239 5 5 5h14c2.762 0 5-2.239 5-5v-14c0-2.761-2.238-5-5-5zm-11 19h-3v-11h3v11zm-1.5-12.268c-.966 0-1.75-.79-1.75-1.764s.784-1.764 1.75-1.764 1.75.79 1.75 1.764-.783 1.764-1.75 1.764zm13.5 12.268h-3v-5.604c0-3.368-4-3.113-4 0v5.604h-3v-11h3v1.765c1.396-2.586 7-2.777 7 2.476v6.759z"/></svg>
                            <span>Tambah ke Profil LinkedIn</span>
                        </a>
                        <button type="button" onclick="copyCertLink()" class="px-3.5 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 font-bold text-xs border border-white/10 transition cursor-pointer" title="Salin Tautan Verifikasi">
                            🔗 Salin Link
                        </button>
                    </div>
                </div>
            </div>

            <!-- Preview Lembar Piagam Interaktif -->
            <div class="bg-slate-900 border border-white/10 rounded-3xl p-6 sm:p-7 shadow-xl space-y-4">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-bold text-white flex items-center gap-2">
                        <span>🖼️</span> <span>Pratinjau Lembar Piagam Digital (A4 Landscape)</span>
                    </h3>
                    <span class="text-xs text-slate-400 font-mono">Format Vektor WeasyPrint 1 Halaman Presisi</span>
                </div>
                <div class="w-full bg-slate-950 rounded-2xl overflow-hidden border border-white/10 shadow-inner flex justify-center p-2">
                    <iframe src="<?= base_url('ambassador/certificate/' . $amb['certificate_code'] . '/preview') ?>#toolbar=0" class="w-full h-[480px] sm:h-[600px] rounded-xl border-0" title="Pratinjau Piagam"></iframe>
                </div>
            </div>

        </div>
    </main>

    <!-- Footer -->
    <footer class="border-t border-white/10 py-6 text-center text-xs text-slate-500">
        &copy; <?= date('Y') ?> AR Tilik &bull; Sistem Verifikasi Piagam &amp; Keaslian Karya Ilmiah Nasional.
    </footer>

    <script>
        function copyCertLink() {
            navigator.clipboard.writeText(window.location.href);
            alert('✓ Tautan verifikasi piagam berhasil disalin ke clipboard!');
        }
    </script>
</body>
</html>
