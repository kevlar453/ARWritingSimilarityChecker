<!DOCTYPE html>
<html lang="id" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal Duta Kampus &bull; AR Tilik</title>
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
        <div class="max-w-6xl mx-auto px-4 sm:px-6 h-16 flex items-center justify-between">
            <a href="<?= base_url() ?>" class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-white font-bold text-base shadow-lg">
                    AR
                </div>
                <div>
                    <span class="font-bold text-sm tracking-tight text-white">AR Tilik</span>
                    <span class="text-[10px] text-amber-400 font-mono block leading-none">Dashboard Duta Kampus</span>
                </div>
            </a>
            <div class="flex items-center gap-2.5 flex-wrap">
                <a href="<?= base_url('ambassador/guide') ?>" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold text-amber-300 hover:text-white bg-amber-500/20 hover:bg-amber-500/30 border border-amber-500/30 transition shadow-xs">
                    <span>📖</span> <span>Panduan &amp; Template Promosi</span>
                </a>
                <a href="<?= base_url('dashboard') ?>" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold text-slate-200 hover:text-white bg-slate-900 hover:bg-slate-800 border border-white/15 transition shadow-xs">
                    <span>&larr;</span> <span>Kembali ke Dashboard</span>
                </a>
                <span class="text-xs px-2.5 py-1 rounded-full bg-indigo-500/20 text-indigo-300 border border-indigo-500/30 font-bold font-mono">
                    <?= esc($profile['referral_code']) ?>
                </span>
            </div>
        </div>
    </header>

    <main class="flex-1 py-8 sm:py-12 px-4 sm:px-6">
        <div class="max-w-5xl mx-auto space-y-8">

            <!-- Banner Sambutan Duta -->
            <div class="bg-gradient-to-r from-indigo-900/60 via-purple-900/40 to-slate-900 border border-indigo-500/30 rounded-3xl p-6 sm:p-8 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-6 shadow-xl">
                <div class="space-y-2">
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="text-xs font-bold uppercase tracking-wider px-2.5 py-0.5 rounded-full bg-amber-400 text-slate-950 shadow-xs">
                            <?= $profile['status'] === 'probation' ? 'Masa Percobaan (30 Hari)' : 'Duta Kampus Resmi' ?>
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

                <!-- Metrik 3: Estimasi Komisi Bulanan -->
                <div class="bg-slate-900 border border-white/10 rounded-2xl p-5 space-y-1">
                    <div class="text-[11px] uppercase font-bold text-slate-400 tracking-wider">Estimasi Komisi (20%)</div>
                    <div class="text-3xl font-black text-amber-400">Rp <?= number_format($profile['estimated_commission'] ?? 0, 0, ',', '.') ?></div>
                    <div class="text-[11px] text-slate-500">Siap cair di akhir bulan berjalan</div>
                </div>
            </div>

            <!-- Progress Probation 1 Bulan -->
            <?php if ($profile['status'] === 'probation'): ?>
                <div class="bg-slate-900 border border-amber-500/30 rounded-3xl p-6 space-y-4">
                    <div class="flex items-center justify-between flex-wrap gap-2">
                        <h3 class="text-sm font-bold text-white flex items-center gap-2">
                            <span>🎯</span> <span>Target Kelulusan Masa Percobaan (Bulan 1)</span>
                        </h3>
                        <span class="text-xs font-mono text-amber-300">
                            Cukup penuhi salah satu target
                        </span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                        <!-- Jalur 1: Bukti Promosi -->
                        <div class="bg-black/30 p-4 rounded-xl border border-white/5 space-y-2">
                            <div class="flex items-center justify-between">
                                <span class="font-bold text-slate-200">Jalur A: 8 Bukti Promosi</span>
                                <span class="font-mono text-amber-400 font-bold"><?= (int)($profile['promos_this_month'] ?? 0) ?> / 8</span>
                            </div>
                            <?php $promoPercent = min(100, ((int)($profile['promos_this_month'] ?? 0) / 8) * 100); ?>
                            <div class="w-full bg-slate-800 h-2.5 rounded-full overflow-hidden">
                                <div class="bg-amber-400 h-full rounded-full transition-all duration-500" style="width: <?= $promoPercent ?>%"></div>
                            </div>
                            <p class="text-[11px] text-slate-400">Unggah tangkapan layar Story WA, IG, atau posting grup di bawah.</p>
                        </div>

                        <!-- Jalur 2: Transaksi Referral -->
                        <div class="bg-black/30 p-4 rounded-xl border border-white/5 space-y-2">
                            <div class="flex items-center justify-between">
                                <span class="font-bold text-slate-200">Jalur B: 3 Transaksi Berbayar</span>
                                <span class="font-mono text-indigo-400 font-bold"><?= (int)($profile['total_transactions'] ?? 0) ?> / 3</span>
                            </div>
                            <?php $txPercent = min(100, ((int)($profile['total_transactions'] ?? 0) / 3) * 100); ?>
                            <div class="w-full bg-slate-800 h-2.5 rounded-full overflow-hidden">
                                <div class="bg-indigo-500 h-full rounded-full transition-all duration-500" style="width: <?= $txPercent ?>%"></div>
                            </div>
                            <p class="text-[11px] text-slate-400">Pembelian paket apapun oleh teman yang Anda undang.</p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

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
    </script>
</body>
</html>
