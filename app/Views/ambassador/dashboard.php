<!DOCTYPE html>
<html lang="id" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal Duta Kampus &bull; AR Tilik</title>
    <!-- Favicon & Brand Icons -->
    <link rel="icon" type="image/x-icon" href="<?= base_url('favicon.ico?v=20261008') ?>">
    <link rel="icon" type="image/png" sizes="32x32" href="<?= base_url('favicon-32x32.png?v=20261008') ?>">
    <link rel="icon" type="image/png" sizes="16x16" href="<?= base_url('favicon-16x16.png?v=20261008') ?>">
    <link rel="apple-touch-icon" sizes="180x180" href="<?= base_url('apple-touch-icon.png?v=20261008') ?>">
    <link rel="shortcut icon" type="image/x-icon" href="<?= base_url('favicon.ico?v=20261008') ?>">
    <!-- Tailwind CSS v3 via CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', 'sans-serif'],
                        mono: ['"JetBrains Mono"', 'monospace'],
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-slate-950 text-slate-100 font-sans antialiased min-h-screen flex flex-col selection:bg-indigo-500 selection:text-white">

    <!-- Header / Navbar -->
    <header class="border-b border-white/10 bg-slate-950/80 backdrop-blur-md sticky top-0 z-50">
        <div class="max-w-6xl mx-auto px-3 sm:px-6 min-h-[3.75rem] py-2 sm:py-0 sm:h-16 flex items-center justify-between gap-2">
            <a href="<?= base_url() ?>" class="flex items-center gap-2 group shrink-0">
                <img src="<?= base_url('images/logo.png') ?>" alt="AR Tilik Logo" class="w-8 h-8 sm:w-9 sm:h-9 object-contain drop-shadow-sm group-hover:scale-105 transition-transform duration-200">
                <div>
                    <span class="font-bold text-sm tracking-tight text-white group-hover:text-indigo-300 transition">AR Tilik</span>
                    <span class="text-[10px] text-amber-400 font-mono block leading-none">Dashboard Duta Kampus</span>
                </div>
            </a>
            <div class="flex items-center gap-1.5 sm:gap-2.5 flex-wrap justify-end">
                <a href="<?= base_url() ?>" class="inline-flex items-center gap-1 sm:gap-1.5 px-2.5 sm:px-3 py-1.5 rounded-xl text-xs font-semibold text-slate-300 hover:text-white bg-slate-900/90 hover:bg-slate-800 border border-white/15 transition shadow-xs" title="Kembali ke Beranda Utama AR Tilik">
                    <span>🏠</span> <span class="hidden sm:inline">Landing Page</span><span class="sm:hidden">Beranda</span>
                </a>
                <a href="<?= base_url('ambassador/guide') ?>" class="inline-flex items-center gap-1 sm:gap-1.5 px-2.5 sm:px-3 py-1.5 rounded-xl text-xs font-bold text-amber-300 hover:text-white bg-amber-500/20 hover:bg-amber-500/30 border border-amber-500/30 transition shadow-xs">
                    <span>📖</span> <span class="hidden sm:inline">Panduan </span><span>Promosi</span>
                </a>
                <?php if (!empty($profile['is_graduated'])): ?>
                    <a href="<?= base_url('ambassador/certificate/' . $profile['certificate_code']) ?>" class="inline-flex items-center gap-1 sm:gap-1.5 px-2.5 sm:px-3 py-1.5 rounded-xl text-xs font-bold text-slate-950 bg-amber-400 hover:bg-amber-300 border border-amber-400 transition shadow-xs">
                        <span>🎓</span> <span class="hidden sm:inline">Piagam </span><span>Duta</span>
                    </a>
                <?php endif; ?>
                <a href="<?= base_url('dashboard') ?>" class="inline-flex items-center gap-1 sm:gap-1.5 px-2.5 sm:px-3 py-1.5 rounded-xl text-xs font-bold text-slate-200 hover:text-white bg-slate-900 hover:bg-slate-800 border border-white/15 transition shadow-xs">
                    <span>&larr;</span> <span class="hidden sm:inline">Dashboard </span><span>Pengguna</span>
                </a>
                <span class="text-xs px-2.5 py-1 rounded-full bg-indigo-500/20 text-indigo-300 border border-indigo-500/30 font-bold font-mono">
                    <?= esc($profile['referral_code']) ?>
                </span>
            </div>
        </div>
    </header>

    <main class="flex-1 py-8 sm:py-12 px-4 sm:px-6">
        <div class="max-w-5xl mx-auto space-y-8">

            <?php if (!empty($profile['is_graduated'])): ?>
                <!-- Kartu Prestasi Kelulusan & Piagam Resmi -->
                <div class="bg-gradient-to-r from-amber-950/70 via-slate-900 to-indigo-950/50 border-2 border-amber-400/50 rounded-3xl p-6 sm:p-8 shadow-2xl relative overflow-hidden">
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 relative z-10">
                        <div class="space-y-3">
                            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-bold bg-amber-500/20 text-amber-300 border border-amber-500/40">
                                <span>🎓</span>
                                <span>PIAGAM PENGHARGAAN RESMI DUTA AKADEMIK</span>
                            </div>
                            <h2 class="text-xl sm:text-2xl font-black text-white tracking-tight">
                                Selamat! Anda Telah Resmi Lulus Masa Orientasi Duta Kampus
                            </h2>
                            <p class="text-xs sm:text-sm text-slate-300 max-w-2xl leading-relaxed">
                                Piagam digital resmi ber-QR Code validasi, nomor SK konsorsium, dan tanda tangan Dewan Pembina telah diterbitkan. Anda dapat mengunduh berkas PDF formal untuk portofolio CV atau menambahkan lisensi ini ke profil LinkedIn.
                            </p>
                            <div class="flex items-center gap-3 pt-1 text-xs font-mono text-amber-400/90 flex-wrap">
                                <span>No. SK: <strong><?= esc($profile['certificate_code']) ?></strong></span>
                                <span>&bull;</span>
                                <span>Predikat: <strong>Sangat Memuaskan</strong></span>
                                <span>&bull;</span>
                                <span>Status: <strong class="text-emerald-400">Aktif &amp; Terverifikasi</strong></span>
                            </div>
                        </div>

                        <div class="flex flex-col sm:flex-row md:flex-col gap-2.5 shrink-0 min-w-[210px]">
                            <a href="<?= base_url('ambassador/certificate/' . $profile['certificate_code'] . '/pdf') ?>" class="px-5 py-3 rounded-xl bg-gradient-to-r from-amber-400 to-amber-500 hover:from-amber-300 hover:to-amber-400 text-slate-950 font-black text-xs flex items-center justify-center gap-2 shadow-lg shadow-amber-500/20 hover:scale-[1.02] active:scale-95 transition">
                                <span>📥</span> <span>Unduh Piagam (PDF)</span>
                            </a>
                            <a href="<?= base_url('ambassador/certificate/' . $profile['certificate_code']) ?>" target="_blank" class="px-4 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 font-bold text-xs flex items-center justify-center gap-2 border border-white/10 transition">
                                <span>🌐</span> <span>Verifikasi Publik</span> &rarr;
                            </a>
                            <?php
                                $certUrl = base_url('ambassador/certificate/' . $profile['certificate_code']);
                                $lYear = date('Y', strtotime($profile['graduated_at'] ?? 'now'));
                                $lMonth = date('n', strtotime($profile['graduated_at'] ?? 'now'));
                                $linkedInUrl = "https://www.linkedin.com/profile/add?startTask=CERTIFICATION_NAME"
                                    . "&name=" . urlencode("Collegiate Academic Ambassador & Research Integrity Advocate")
                                    . "&organizationName=" . urlencode("AR Tilik")
                                    . "&issueYear=" . $lYear
                                    . "&issueMonth=" . $lMonth
                                    . "&certUrl=" . urlencode($certUrl)
                                    . "&certId=" . urlencode($profile['certificate_code']);
                            ?>
                            <a href="<?= $linkedInUrl ?>" target="_blank" class="px-4 py-2.5 rounded-xl bg-[#0077b5] hover:bg-[#006396] text-white font-bold text-xs flex items-center justify-center gap-2 shadow-sm transition">
                                <svg class="w-3.5 h-3.5 fill-current" viewBox="0 0 24 24"><path d="M19 0h-14c-2.761 0-5 2.239-5 5v14c0 2.761 2.239 5 5 5h14c2.762 0 5-2.239 5-5v-14c0-2.761-2.238-5-5-5zm-11 19h-3v-11h3v11zm-1.5-12.268c-.966 0-1.75-.79-1.75-1.764s.784-1.764 1.75-1.764 1.75.79 1.75 1.764-.783 1.764-1.75 1.764zm13.5 12.268h-3v-5.604c0-3.368-4-3.113-4 0v5.604h-3v-11h3v1.765c1.396-2.586 7-2.777 7 2.476v6.759z"/></svg>
                                <span>Tambah ke LinkedIn</span>
                            </a>
                        </div>
                    </div>
                    <div class="absolute -right-12 -bottom-12 w-64 h-64 bg-amber-400/10 rounded-full blur-3xl pointer-events-none"></div>
                </div>
            <?php endif; ?>

            <!-- Banner Sambutan Duta -->
            <div class="bg-gradient-to-r from-indigo-900/60 via-purple-900/40 to-slate-900 border border-indigo-500/30 rounded-3xl p-6 sm:p-8 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-6 shadow-xl">
                <div class="space-y-2">
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="text-xs font-bold uppercase tracking-wider px-2.5 py-0.5 rounded-full <?= !empty($profile['is_graduated']) ? 'bg-amber-400 text-slate-950' : ($profile['status'] === 'probation' ? 'bg-indigo-400 text-slate-950' : 'bg-emerald-400 text-slate-950') ?> shadow-xs">
                            <?= !empty($profile['is_graduated']) ? '🎓 Duta Purna Kehormatan' : ($profile['status'] === 'probation' ? 'Masa Percobaan (30 Hari)' : 'Duta Kampus Resmi') ?>
                        </span>
                        <span class="text-xs text-slate-400 font-mono">
                            Periode s/d <?= date('d M Y', strtotime($profile['probation_end'] ?? '+30 days')) ?>
                        </span>
                    </div>
                    <h1 class="text-xl sm:text-3xl font-extrabold text-white tracking-tight">
                        Portal Afiliasi &amp; Kinerja Duta Kampus
                    </h1>
                    <p class="text-xs sm:text-sm text-slate-300 max-w-2xl leading-relaxed">
                        Bagikan kode referral Anda ke grup angkatan atau rekan mahasiswa. Bantu mereka lolos cek naskah akademik sekaligus raih komisi bulanan 20%.
                    </p>
                </div>
                <div class="bg-black/30 border border-white/10 rounded-2xl p-4 text-center flex-shrink-0 min-w-[160px]">
                    <div class="text-[10px] uppercase font-bold text-slate-400 tracking-wider">Komisi Anda</div>
                    <div class="text-2xl font-black text-amber-400 mt-0.5">20%</div>
                    <div class="text-[10px] text-slate-400 mt-0.5">Dicairkan Bulanan</div>
                </div>
            </div>

            <!-- Kartu Alat Promosi & Link Referral -->
            <div class="bg-slate-900 border border-white/10 rounded-3xl p-6 sm:p-7 shadow-xl space-y-4">
                <h2 class="text-sm sm:text-base font-bold text-white flex items-center gap-2">
                    <span>🔗</span> <span>Tautan &amp; Kode Referral Unik Anda</span>
                </h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="space-y-1.5">
                        <label class="block text-xs font-semibold text-slate-400">Tautan Referral (Klik Otomatis Menempelkan Kode):</label>
                        <div class="flex items-center gap-2 bg-black/40 border border-white/15 rounded-xl p-2.5">
                            <input type="text" id="refUrlInput" readonly value="<?= esc($profile['referral_url']) ?>" class="bg-transparent text-xs text-indigo-300 font-mono w-full focus:outline-none">
                            <button type="button" onclick="copyRefUrl()" class="px-3 py-1.5 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold whitespace-nowrap transition cursor-pointer">
                                Salin Link
                            </button>
                        </div>
                    </div>

                    <div class="space-y-1.5">
                        <label class="block text-xs font-semibold text-slate-400">Kode Kupon / Referral:</label>
                        <div class="flex items-center gap-2 bg-black/40 border border-white/15 rounded-xl p-2.5">
                            <input type="text" id="refCodeInput" readonly value="<?= esc($profile['referral_code']) ?>" class="bg-transparent text-xs text-amber-300 font-mono font-bold w-full focus:outline-none">
                            <button type="button" onclick="copyRefCode()" class="px-3 py-1.5 rounded-lg bg-amber-500 hover:bg-amber-400 text-slate-950 text-xs font-bold whitespace-nowrap transition cursor-pointer">
                                Salin Kode
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Tombol Berbagi Cepat -->
                <div class="pt-2 flex items-center gap-3 flex-wrap">
                    <?php 
                        $waShareText = "Halo teman-teman! Buat yang lagi ngerjain skripsi, tesis, atau artikel jurnal dan butuh cek kemiripan naskah akurat (termasuk deteksi sitasi masa depan & stylometrics AI), bisa coba AR Tilik:\n"
                            . $profile['referral_url'] . "\n\n"
                            . "Gunakan kode saya: *" . $profile['referral_code'] . "* untuk bonus kuota gratis!";
                        $waShareUrl = "https://wa.me/?text=" . urlencode($waShareText);
                    ?>
                    <a href="<?= $waShareUrl ?>" target="_blank" class="px-4 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs flex items-center gap-2 shadow-md shadow-emerald-600/20 transition">
                        <span>📲</span> <span>Bagikan Cepat ke WhatsApp</span>
                    </a>
                    <a href="<?= base_url('ambassador/guide') ?>" class="px-4 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs flex items-center gap-2 shadow-md shadow-indigo-600/20 transition">
                        <span>📖</span> <span>Template Copywriting Siap Salin</span> &rarr;
                    </a>
                    <span class="text-[11px] text-slate-400">
                        Teman yang mendaftar melalui link Anda otomatis mendapat <strong>+1 bonus cek naskah gratis</strong>.
                    </span>
                </div>
            </div>

            <!-- Target Masa Percobaan (Tahap 5) & Metrik Kinerja -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <!-- Metrik 1: Teman Bergabung -->
                <div class="bg-slate-900 border border-white/10 rounded-2xl p-5 space-y-1">
                    <div class="text-[11px] uppercase font-bold text-slate-400 tracking-wider">Mahasiswa Bergabung</div>
                    <div class="text-3xl font-black text-white"><?= number_format($profile['total_registered'] ?? 0) ?></div>
                    <div class="text-[11px] text-slate-500">Mendaftar via tautan/kode Anda</div>
                </div>

                <!-- Metrik 2: Transaksi Pembelian -->
                <div class="bg-slate-900 border border-white/10 rounded-2xl p-5 space-y-1">
                    <div class="text-[11px] uppercase font-bold text-slate-400 tracking-wider">Transaksi Naskah Berbayar</div>
                    <div class="text-3xl font-black text-indigo-400"><?= number_format($profile['total_transactions'] ?? 0) ?></div>
                    <div class="text-[11px] text-slate-500">Target kelulusan: minimal 3 transaksi</div>
                </div>

                <!-- Metrik 3: Saldo Komisi Siap Tarik -->
                <div class="bg-slate-900 border border-white/10 rounded-2xl p-5 space-y-1">
                    <div class="text-[11px] uppercase font-bold text-slate-400 tracking-wider">Saldo Komisi Siap Tarik</div>
                    <div class="text-3xl font-black text-amber-400">Rp <?= number_format($financial['available_balance'] ?? 0, 0, ',', '.') ?></div>
                    <div class="text-[11px] text-slate-500">Min. penarikan: Rp 100.000</div>
                </div>
            </div>

            <!-- Progress Probation 1 Bulan & Kelulusan Piagam -->
            <?php if (empty($profile['is_graduated'])): ?>
                <div class="bg-slate-900 border <?= !empty($profile['is_eligible_graduation']) ? 'border-emerald-500/40' : 'border-amber-500/30' ?> rounded-3xl p-6 space-y-4">
                    <div class="flex items-center justify-between flex-wrap gap-2">
                        <div class="flex items-center gap-2">
                            <span class="text-lg">🎯</span>
                            <h3 class="text-sm font-bold text-white">
                                Target Kelulusan Masa Percobaan &amp; Penerbitan Piagam
                            </h3>
                        </div>
                        <?php if (!empty($profile['is_eligible_graduation'])): ?>
                            <span class="px-3 py-1 rounded-full text-xs font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-500/40">
                                ✓ Target Tercapai (Menunggu Terbit Piagam)
                            </span>
                        <?php else: ?>
                            <span class="text-xs font-mono text-amber-300">
                                Cukup penuhi salah satu target di bawah
                            </span>
                        <?php endif; ?>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                        <!-- Jalur 1: Bukti Promosi Disetujui -->
                        <?php 
                            $approvedCount = (int)($profile['approved_promos_total'] ?? 0);
                            $uploadedCount = (int)($profile['promos_this_month'] ?? 0);
                            $promoPercent = min(100, ($approvedCount / 8) * 100);
                        ?>
                        <div class="bg-black/30 p-4 rounded-xl border border-white/5 space-y-2">
                            <div class="flex items-center justify-between">
                                <span class="font-bold text-slate-200">Jalur A: 8 Bukti Promosi Disetujui</span>
                                <span class="font-mono text-amber-400 font-bold"><?= $approvedCount ?> / 8</span>
                            </div>
                            <div class="w-full bg-slate-800 h-2.5 rounded-full overflow-hidden">
                                <div class="bg-amber-400 h-full rounded-full transition-all duration-500" style="width: <?= $promoPercent ?>%"></div>
                            </div>
                            <p class="text-[11px] text-slate-400">
                                Total diunggah: <?= $uploadedCount ?> bukti. Unggah bukti Story WA/IG/Grup angkatan di formulir bawah.
                            </p>
                        </div>

                        <!-- Jalur 2: Transaksi Referral -->
                        <?php 
                            $txCount = (int)($profile['total_transactions'] ?? 0);
                            $txPercent = min(100, ($txCount / 3) * 100);
                        ?>
                        <div class="bg-black/30 p-4 rounded-xl border border-white/5 space-y-2">
                            <div class="flex items-center justify-between">
                                <span class="font-bold text-slate-200">Jalur B: 3 Transaksi Berbayar</span>
                                <span class="font-mono text-indigo-400 font-bold"><?= $txCount ?> / 3</span>
                            </div>
                            <div class="w-full bg-slate-800 h-2.5 rounded-full overflow-hidden">
                                <div class="bg-indigo-500 h-full rounded-full transition-all duration-500" style="width: <?= $txPercent ?>%"></div>
                            </div>
                            <p class="text-[11px] text-slate-400">Pembelian paket naskah oleh teman yang Anda undang.</p>
                        </div>
                    </div>

                    <div class="bg-amber-500/10 border border-amber-500/20 rounded-xl p-3 flex items-center gap-3 text-xs text-amber-200/90">
                        <span class="text-base">🏆</span>
                        <span>
                            <strong>Reward Kelulusan:</strong> Menyelesaikan masa percobaan akan membuka <strong>Piagam Penghargaan Duta Akademik (PDF ber-QR Code)</strong> dan memperpanjang akun Premium 1 tahun penuh.
                        </span>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Dompet Komisi & Penarikan Saldo (Payout Minimum Rp 100.000) -->
            <div id="payout-section" class="bg-gradient-to-br from-slate-900 via-slate-900/90 to-amber-950/20 border border-amber-500/30 rounded-3xl p-6 sm:p-7 space-y-6 shadow-xl">
                <div class="flex items-start sm:items-center justify-between flex-wrap gap-4 border-b border-white/10 pb-4">
                    <div>
                        <div class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase bg-amber-500/20 text-amber-300 border border-amber-500/30 mb-1.5">
                            <span>💸</span> <span>Bagi Hasil Kemitraan 20%</span>
                        </div>
                        <h2 class="text-base sm:text-lg font-bold text-white flex items-center gap-2">
                            <span>Dompet Komisi &amp; Pencairan Saldo (Payout)</span>
                        </h2>
                        <p class="text-xs text-slate-400 mt-0.5">
                            Tarik saldo komisi langsung ke rekening Bank atau E-Wallet Anda (Minimum Rp <?= number_format($financial['min_payout'] ?? 100000, 0, ',', '.') ?> tanpa potongan biaya admin).
                        </p>
                    </div>

                    <div>
                        <?php if (!empty($financial['can_withdraw'])): ?>
                            <button type="button" onclick="openPayoutModal()" class="px-5 py-2.5 rounded-xl bg-gradient-to-r from-amber-500 to-amber-400 hover:from-amber-400 hover:to-amber-300 text-slate-950 font-black text-xs shadow-lg shadow-amber-500/20 hover:scale-[1.02] active:scale-95 transition flex items-center gap-2 cursor-pointer">
                                <span>💸</span>
                                <span>Tarik Saldo Komisi &rarr;</span>
                            </button>
                        <?php else: ?>
                            <div class="text-right">
                                <button type="button" disabled class="px-4 py-2.5 rounded-xl bg-slate-800 text-slate-500 font-bold text-xs border border-white/5 cursor-not-allowed flex items-center gap-2">
                                    <span>🔒</span>
                                    <span>Tarik Saldo (Min. Rp 100.000)</span>
                                </button>
                                <span class="text-[10px] text-amber-400/80 block mt-1">
                                    Saldo kurang Rp <?= number_format(max(0, ($financial['min_payout'] ?? 100000) - ($financial['available_balance'] ?? 0)), 0, ',', '.') ?> lagi
                                </span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- 3 Kotak Rincian Saldo -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-xs">
                    <!-- 1. Saldo Tersedia -->
                    <div class="bg-black/40 border border-amber-500/30 rounded-2xl p-4 space-y-1 relative overflow-hidden">
                        <div class="text-[11px] uppercase font-bold text-amber-300/80">Saldo Siap Ditarik</div>
                        <div class="text-2xl font-black text-amber-300">
                            Rp <?= number_format($financial['available_balance'] ?? 0, 0, ',', '.') ?>
                        </div>
                        <div class="text-[10px] text-slate-400 flex items-center justify-between pt-1">
                            <span>Bisa dicairkan kapan saja</span>
                            <?php if (!empty($financial['can_withdraw'])): ?>
                                <span class="text-emerald-400 font-bold">✓ Memenuhi Syarat</span>
                            <?php else: ?>
                                <span class="text-slate-500">Min. Rp 100.000</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- 2. Total Komisi Diperoleh -->
                    <div class="bg-black/30 border border-white/5 rounded-2xl p-4 space-y-1">
                        <div class="text-[11px] uppercase font-bold text-slate-400">Total Komisi Akumulasi</div>
                        <div class="text-2xl font-black text-white">
                            Rp <?= number_format($financial['total_earned'] ?? 0, 0, ',', '.') ?>
                        </div>
                        <div class="text-[10px] text-slate-500">
                            Hasil bagi 20% dari <?= (int)($profile['total_transactions'] ?? 0) ?> transaksi teman
                        </div>
                    </div>

                    <!-- 3. Total Dicairkan / Diproses -->
                    <div class="bg-black/30 border border-white/5 rounded-2xl p-4 space-y-1">
                        <div class="text-[11px] uppercase font-bold text-slate-400">Telah Ditarik / Diproses</div>
                        <div class="text-2xl font-black text-indigo-300">
                            Rp <?= number_format($financial['total_withdrawn'] ?? 0, 0, ',', '.') ?>
                        </div>
                        <div class="text-[10px] text-slate-500">
                            Total penarikan yang telah diajukan
                        </div>
                    </div>
                </div>

                <!-- Riwayat Penarikan Saldo -->
                <div class="pt-2 space-y-3">
                    <div class="flex items-center justify-between flex-wrap gap-2">
                        <h3 class="text-xs font-bold uppercase tracking-wider text-slate-300 flex items-center gap-1.5">
                            <span>📋</span> <span>Riwayat Penarikan Saldo (<?= count($payouts ?? []) ?>)</span>
                        </h3>
                        <span class="text-[11px] text-slate-500">Pencairan diproses 1-2 hari kerja</span>
                    </div>

                    <?php if (empty($payouts)): ?>
                        <div class="p-6 text-center text-xs text-slate-500 bg-black/20 rounded-xl border border-white/5">
                            Belum ada riwayat penarikan saldo. Komisi akan otomatis bertambah setiap ada transaksi dari kode referral Anda.
                        </div>
                    <?php else: ?>
                        <div class="overflow-x-auto rounded-xl border border-white/10">
                            <table class="w-full text-left text-xs border-collapse">
                                <thead>
                                    <tr class="bg-black/40 text-slate-400 border-b border-white/10 text-[11px]">
                                        <th class="p-3 font-semibold">Kode Payout</th>
                                        <th class="p-3 font-semibold">Tanggal</th>
                                        <th class="p-3 font-semibold">Nominal</th>
                                        <th class="p-3 font-semibold">Tujuan Rekening</th>
                                        <th class="p-3 font-semibold">Status</th>
                                        <th class="p-3 font-semibold text-right">Keterangan</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-white/5 bg-black/20">
                                    <?php foreach ($payouts as $po): ?>
                                        <tr class="hover:bg-white/[0.02] transition">
                                            <td class="p-3 font-mono font-bold text-amber-300"><?= esc($po['payout_code']) ?></td>
                                            <td class="p-3 text-slate-400"><?= date('d M Y, H:i', strtotime($po['requested_at'])) ?></td>
                                            <td class="p-3 font-bold text-white">Rp <?= number_format($po['amount'], 0, ',', '.') ?></td>
                                            <td class="p-3 text-slate-300">
                                                <span class="font-bold uppercase text-slate-200"><?= esc($po['payout_channel']) ?></span> - 
                                                <span class="font-mono text-slate-400"><?= esc($po['payout_account_number']) ?></span>
                                                <div class="text-[10px] text-slate-500">a.n <?= esc($po['payout_account_name']) ?></div>
                                            </td>
                                            <td class="p-3">
                                                <?php if ($po['status'] === 'completed'): ?>
                                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                                                        ✓ SELESAI / DITRANSFER
                                                    </span>
                                                <?php elseif ($po['status'] === 'processing'): ?>
                                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-indigo-500/20 text-indigo-300 border border-indigo-500/30">
                                                        🔄 SEDANG DIPROSES
                                                    </span>
                                                <?php elseif ($po['status'] === 'rejected'): ?>
                                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-rose-500/20 text-rose-300 border border-rose-500/30">
                                                        ✕ DITOLAK
                                                    </span>
                                                <?php else: ?>
                                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-500/20 text-amber-300 border border-amber-500/30">
                                                        ⏳ MENUNGGU TRANSFER
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="p-3 text-right text-slate-400 text-[11px]">
                                                <?php if (!empty($po['proof_transfer_path'])): ?>
                                                    <a href="<?= base_url('admin/ambassador/payouts/proof/' . $po['id']) ?>" target="_blank" class="text-indigo-300 hover:text-white underline font-semibold">
                                                        Lihat Bukti Transfer
                                                    </a>
                                                <?php elseif (!empty($po['admin_notes'])): ?>
                                                    <span><?= esc($po['admin_notes']) ?></span>
                                                <?php else: ?>
                                                    <span class="text-slate-600">-</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Formulir & Riwayat Unggah Bukti Promosi (Tahap 5 & 6) -->
            <div class="bg-slate-900 border border-white/10 rounded-3xl p-6 sm:p-7 space-y-6">
                <div class="border-b border-white/10 pb-4">
                    <h2 class="text-base sm:text-lg font-bold text-white flex items-center gap-2">
                        <span>📸</span> <span>Unggah Bukti Aktivitas Promosi (Rekap Mandiri)</span>
                    </h2>
                    <p class="text-xs text-slate-400 mt-0.5">
                        Unggah tangkapan layar Story WA, Story IG, atau share grup angkatan agar tercatat otomatis dalam evaluasi bulanan.
                    </p>
                </div>

                <form id="proofForm" onsubmit="submitPromotionProof(event)" class="space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1">Saluran Promosi:</label>
                            <select name="proof_type" class="w-full text-xs bg-slate-950 border border-white/15 rounded-xl px-3 py-2.5 text-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                <option value="story_wa" selected>WhatsApp Story</option>
                                <option value="grup_wa">Grup WhatsApp (Kelas / Angkatan)</option>
                                <option value="story_ig">Instagram Story / Post</option>
                                <option value="seminar">Presentasi / Seminar Kampus</option>
                                <option value="poster">Poster / Media Cetak Mading</option>
                                <option value="lainnya">Lainnya</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1">Judul / Kegiatan Promosi:</label>
                            <input type="text" name="title" placeholder="Misal: Share ke Grup Skripsi TI 2021" required class="w-full text-xs bg-black/40 border border-white/15 rounded-xl px-3 py-2.5 text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1">Screenshot Bukti:</label>
                            <input type="file" name="proof_file" accept="image/*,application/pdf" required class="w-full text-xs bg-black/40 border border-white/15 rounded-xl p-2 text-slate-300 file:mr-2 file:py-1 file:px-2.5 file:rounded-lg file:border-0 file:text-[11px] file:bg-indigo-600 file:text-white cursor-pointer">
                        </div>
                    </div>

                    <div class="flex items-center justify-end">
                        <button type="submit" id="btnSubmitProof" class="px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 active:scale-95 text-white font-bold text-xs shadow-md transition flex items-center gap-2 cursor-pointer">
                            <span>📤</span>
                            <span id="btnSubmitProofText">Simpan Bukti Promosi</span>
                        </button>
                    </div>
                </form>

                <!-- Riwayat Bukti Promosi -->
                <div class="pt-4 border-t border-white/10 space-y-3">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400">
                        Riwayat Bukti Promosi Terunggah (<?= count($proofs ?? []) ?>)
                    </h3>

                    <?php if (empty($proofs)): ?>
                        <div class="p-6 text-center text-xs text-slate-500 bg-black/20 rounded-xl border border-white/5">
                            Belum ada bukti promosi yang diunggah. Mulai bagikan tautan referral Anda sekarang!
                        </div>
                    <?php else: ?>
                        <div class="space-y-2">
                            <?php foreach ($proofs as $p): ?>
                                <div class="bg-black/30 border border-white/5 rounded-xl p-3.5 flex items-center justify-between flex-wrap gap-2 text-xs">
                                    <div class="flex items-center gap-3">
                                        <span class="text-lg">📸</span>
                                        <div>
                                            <div class="font-bold text-white"><?= esc($p['title']) ?></div>
                                            <div class="text-[11px] text-slate-400">
                                                <span><?= strtoupper(str_replace('_', ' ', $p['proof_type'])) ?></span>
                                                <span>&bull;</span>
                                                <span><?= date('d M Y, H:i', strtotime($p['created_at'])) ?> WIB</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <?php if (!empty($p['file_path'])): ?>
                                            <span class="text-[11px] text-indigo-300 bg-indigo-500/20 px-2 py-0.5 rounded border border-indigo-500/30">
                                                Tersimpan Aman
                                            </span>
                                        <?php endif; ?>
                                        <?php if (in_array($p['status'], ['approved', 'verified'], true)): ?>
                                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                                                ✓ DISETUJUI
                                            </span>
                                        <?php elseif ($p['status'] === 'rejected'): ?>
                                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-rose-500/20 text-rose-300 border border-rose-500/30">
                                                ✕ DITOLAK
                                            </span>
                                        <?php else: ?>
                                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-500/20 text-amber-300 border border-amber-500/30">
                                                ⏳ PENDING
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </main>

    <!-- Footer -->
    <footer class="border-t border-white/10 py-6 text-center text-xs text-slate-500">
        &copy; <?= date('Y') ?> AR Tilik &bull; Portal Duta Kampus.
    </footer>

    <script>
        function copyRefUrl() {
            const input = document.getElementById('refUrlInput');
            navigator.clipboard.writeText(input.value);
            alert('✓ Tautan referral berhasil disalin ke clipboard!');
        }

        function copyRefCode() {
            const input = document.getElementById('refCodeInput');
            navigator.clipboard.writeText(input.value);
            alert('✓ Kode kupon referral berhasil disalin!');
        }

        async function submitPromotionProof(e) {
            e.preventDefault();
            const btn = document.getElementById('btnSubmitProof');
            const btnText = document.getElementById('btnSubmitProofText');
            const form = document.getElementById('proofForm');
            const formData = new FormData(form);

            btn.disabled = true;
            btnText.innerText = 'Mengunggah...';

            try {
                const res = await fetch('<?= base_url('ambassador/proof') ?>', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                const data = await res.json();

                if (res.ok && data.success) {
                    alert('✓ ' + data.message);
                    window.location.reload();
                } else {
                    alert('⚠️ ' + (data.message || 'Gagal mengunggah bukti promosi.'));
                }
            } catch (err) {
                console.error('Error proof upload:', err);
                alert('Terjadi kesalahan jaringan.');
            } finally {
                btn.disabled = false;
                btnText.innerText = 'Simpan Bukti Promosi';
            }
        }

        function openPayoutModal() {
            document.getElementById('payoutModal').classList.remove('hidden');
        }

        function closePayoutModal() {
            document.getElementById('payoutModal').classList.add('hidden');
        }

        function setMaxPayout(maxAmount) {
            document.getElementById('payoutAmountInput').value = Math.floor(maxAmount);
        }

        async function submitPayoutRequest(e) {
            e.preventDefault();
            const btn = document.getElementById('btnSubmitPayout');
            const btnText = document.getElementById('btnSubmitPayoutText');
            const form = document.getElementById('payoutForm');
            const formData = new FormData(form);

            const amount = parseFloat(formData.get('amount') || 0);
            if (amount < 100000) {
                alert('⚠️ Batas minimum penarikan saldo adalah Rp 100.000');
                return;
            }

            btn.disabled = true;
            btnText.innerText = 'Memproses...';

            try {
                const res = await fetch('<?= base_url('ambassador/payout') ?>', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                const data = await res.json();

                if (res.ok && data.success) {
                    alert('✓ ' + data.message);
                    closePayoutModal();
                    window.location.reload();
                } else {
                    alert('⚠️ ' + (data.message || 'Gagal mengajukan penarikan saldo.'));
                }
            } catch (err) {
                console.error('Error payout request:', err);
                alert('Terjadi kesalahan jaringan saat mengirimkan permintaan penarikan.');
            } finally {
                btn.disabled = false;
                btnText.innerText = 'Kirim Permintaan Pencairan';
            }
        }
    </script>

    <!-- Modal Penarikan Saldo Komisi (Minimum Rp 100.000) -->
    <div id="payoutModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm hidden">
        <div class="bg-slate-900 border border-amber-500/40 rounded-3xl p-6 sm:p-7 max-w-lg w-full space-y-5 shadow-2xl relative">
            <div class="flex items-center justify-between border-b border-white/10 pb-3">
                <div class="flex items-center gap-2">
                    <span class="text-xl">💸</span>
                    <div>
                        <h3 class="text-sm sm:text-base font-bold text-white">Tarik Saldo Komisi Duta</h3>
                        <p class="text-[11px] text-slate-400">Minimum penarikan: Rp 100.000 &bull; Tanpa potongan admin</p>
                    </div>
                </div>
                <button type="button" onclick="closePayoutModal()" class="text-slate-400 hover:text-white text-lg font-bold p-1 cursor-pointer">&times;</button>
            </div>

            <!-- Info Saldo Tersedia -->
            <div class="bg-amber-500/10 border border-amber-500/30 rounded-2xl p-3.5 flex items-center justify-between text-xs">
                <div>
                    <span class="text-slate-400 block text-[11px]">Saldo Komisi Tersedia:</span>
                    <strong class="text-amber-300 text-base font-mono">Rp <?= number_format($financial['available_balance'] ?? 0, 0, ',', '.') ?></strong>
                </div>
                <button type="button" onclick="setMaxPayout(<?= (float)($financial['available_balance'] ?? 0) ?>)" class="px-2.5 py-1 rounded-lg bg-amber-500/20 hover:bg-amber-500/30 text-amber-300 font-bold text-[11px] border border-amber-500/30 transition cursor-pointer">
                    Tarik Semua Saldo
                </button>
            </div>

            <form id="payoutForm" onsubmit="submitPayoutRequest(event)" class="space-y-4 text-xs">
                <div>
                    <label class="block font-semibold text-slate-300 mb-1">Jumlah Penarikan (Rp):</label>
                    <div class="relative">
                        <span class="absolute left-3.5 top-2.5 text-slate-400 font-bold text-xs">Rp</span>
                        <input type="number" id="payoutAmountInput" name="amount" min="100000" max="<?= (float)($financial['available_balance'] ?? 0) ?>" step="1000" placeholder="100000" required class="w-full bg-slate-950 border border-white/15 rounded-xl pl-10 pr-3.5 py-2.5 text-white font-mono font-bold focus:outline-none focus:ring-2 focus:ring-amber-500">
                    </div>
                    <span class="text-[10px] text-slate-500 mt-1 block">Batas minimum Rp 100.000. Masukkan angka bulat (contoh: 150000).</span>
                </div>

                <div>
                    <label class="block font-semibold text-slate-300 mb-1">Pilihan Saluran / Bank Tujuan:</label>
                    <select name="payout_channel" required class="w-full bg-slate-950 border border-white/15 rounded-xl px-3.5 py-2.5 text-white focus:outline-none focus:ring-2 focus:ring-amber-500">
                        <optgroup label="Bank Transfer">
                            <option value="BCA" <?= ($profile['payout_channel'] ?? '') === 'BCA' ? 'selected' : '' ?>>Bank Central Asia (BCA)</option>
                            <option value="Mandiri" <?= ($profile['payout_channel'] ?? '') === 'Mandiri' ? 'selected' : '' ?>>Bank Mandiri</option>
                            <option value="BRI" <?= ($profile['payout_channel'] ?? '') === 'BRI' ? 'selected' : '' ?>>Bank Rakyat Indonesia (BRI)</option>
                            <option value="BNI" <?= ($profile['payout_channel'] ?? '') === 'BNI' ? 'selected' : '' ?>>Bank Negara Indonesia (BNI)</option>
                            <option value="BSI" <?= ($profile['payout_channel'] ?? '') === 'BSI' ? 'selected' : '' ?>>Bank Syariah Indonesia (BSI)</option>
                            <option value="CIMB" <?= ($profile['payout_channel'] ?? '') === 'CIMB' ? 'selected' : '' ?>>CIMB Niaga</option>
                        </optgroup>
                        <optgroup label="E-Wallet">
                            <option value="DANA" <?= ($profile['payout_channel'] ?? '') === 'DANA' ? 'selected' : '' ?>>DANA (No. HP)</option>
                            <option value="GoPay" <?= ($profile['payout_channel'] ?? '') === 'GoPay' ? 'selected' : '' ?>>GoPay (No. HP)</option>
                            <option value="OVO" <?= ($profile['payout_channel'] ?? '') === 'OVO' ? 'selected' : '' ?>>OVO (No. HP)</option>
                            <option value="ShopeePay" <?= ($profile['payout_channel'] ?? '') === 'ShopeePay' ? 'selected' : '' ?>>ShopeePay (No. HP)</option>
                        </optgroup>
                    </select>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block font-semibold text-slate-300 mb-1">Nomor Rekening / HP:</label>
                        <input type="text" name="payout_account_number" value="<?= esc($profile['payout_account_number'] ?? '') ?>" placeholder="Misal: 1234567890 / 0812xxx" required class="w-full bg-slate-950 border border-white/15 rounded-xl px-3.5 py-2.5 text-white font-mono focus:outline-none focus:ring-2 focus:ring-amber-500">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-300 mb-1">Nama Pemilik Rekening:</label>
                        <input type="text" name="payout_account_name" value="<?= esc($profile['payout_account_name'] ?? '') ?>" placeholder="Sesuai buku tabungan / akun" required class="w-full bg-slate-950 border border-white/15 rounded-xl px-3.5 py-2.5 text-white focus:outline-none focus:ring-2 focus:ring-amber-500">
                    </div>
                </div>

                <div class="pt-2 flex items-center justify-end gap-3">
                    <button type="button" onclick="closePayoutModal()" class="px-4 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 font-semibold cursor-pointer">
                        Batal
                    </button>
                    <button type="submit" id="btnSubmitPayout" class="px-5 py-2.5 rounded-xl bg-amber-500 hover:bg-amber-400 text-slate-950 font-black shadow-lg shadow-amber-500/25 active:scale-95 transition flex items-center gap-2 cursor-pointer">
                        <span>💸</span>
                        <span id="btnSubmitPayoutText">Kirim Permintaan Pencairan</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
