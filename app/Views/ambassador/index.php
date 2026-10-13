<!DOCTYPE html>
<html lang="id" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Duta Kampus AR Tilik &bull; Program Kemitraan Akademik &amp; Afiliasi</title>
    
    <!-- Primary SEO Meta Tags -->
    <meta name="description" content="Program Duta Kampus AR Tilik: Raih akses naskah gratis 30 hari, piagam kepemimpinan integritas akademik, dan komisi kemitraan 20% di universitas Anda.">
    <meta name="keywords" content="duta kampus, student ambassador, afiliasi akademik, integritas akademik, program duta mahasiswa, ar tilik">
    <meta name="robots" content="index, follow, max-snippet:-1, max-image-preview:large">
    <link rel="canonical" href="<?= base_url('ambassador') ?>">
    <meta name="theme-color" content="#0f172a">

    <!-- Open Graph / Social Sharing -->
    <meta property="og:type" content="website">
    <meta property="og:locale" content="id_ID">
    <meta property="og:site_name" content="AR Tilik">
    <meta property="og:title" content="Duta Kampus AR Tilik — Kemitraan Akademik &amp; Afiliasi">
    <meta property="og:description" content="Program Duta Kampus AR Tilik: Akses naskah gratis 30 hari, piagam kepemimpinan resmi, dan komisi kemitraan 20% bagi mahasiswa &amp; dosen.">
    <meta property="og:url" content="<?= base_url('ambassador') ?>">
    <meta property="og:image" content="<?= base_url('images/og-artilik.png') ?>">

    <!-- Twitter Cards -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Duta Kampus AR Tilik — Kemitraan Akademik &amp; Afiliasi">
    <meta name="twitter:description" content="Program Duta Kampus AR Tilik: Akses naskah gratis 30 hari, piagam kepemimpinan resmi, dan komisi kemitraan 20%.">
    <meta name="twitter:image" content="<?= base_url('images/og-artilik.png') ?>">

    <!-- Favicon & Brand Icons -->
    <link rel="icon" type="image/x-icon" href="<?= base_url('favicon.ico?v=20261008') ?>">
    <link rel="icon" type="image/png" sizes="32x32" href="<?= base_url('favicon-32x32.png?v=20261008') ?>">
    <link rel="icon" type="image/png" sizes="16x16" href="<?= base_url('favicon-16x16.png?v=20261008') ?>">
    <link rel="apple-touch-icon" sizes="180x180" href="<?= base_url('apple-touch-icon.png?v=20261008') ?>">
    <link rel="shortcut icon" type="image/x-icon" href="<?= base_url('favicon.ico?v=20261008') ?>">
    <!-- Tailwind CSS v3 via CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', 'sans-serif'],
                        mono: ['"JetBrains Mono"', 'monospace'],
                    },
                    colors: {
                        brand: {
                            50: '#eef2ff',
                            100: '#e0e7ff',
                            500: '#6366f1',
                            600: '#4f46e5',
                            700: '#4338ca',
                            900: '#312e81',
                            950: '#1e1b4b',
                        }
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-slate-950 text-slate-100 font-sans antialiased min-h-screen flex flex-col selection:bg-indigo-500 selection:text-white">

    <!-- Navbar Minimalis -->
    <header class="border-b border-white/10 bg-slate-950/80 backdrop-blur-md sticky top-0 z-50">
        <div class="max-w-6xl mx-auto px-3 sm:px-6 min-h-[3.75rem] py-2 sm:py-0 sm:h-16 flex items-center justify-between gap-2">
            <a href="<?= base_url() ?>" class="flex items-center gap-2 group shrink-0">
                <img src="<?= base_url('images/logo.png') ?>" alt="AR Tilik Logo" class="w-8 h-8 sm:w-9 sm:h-9 object-contain drop-shadow-sm group-hover:scale-105 transition-transform duration-200">
                <div>
                    <span class="font-bold text-sm tracking-tight text-white group-hover:text-indigo-300 transition">AR Tilik</span>
                    <span class="text-[10px] text-amber-400 font-mono block leading-none">Duta Kampus Hub</span>
                </div>
            </a>
            <div class="flex items-center gap-1.5 sm:gap-2.5 flex-wrap justify-end">
                <a href="<?= base_url() ?>" class="inline-flex items-center gap-1 sm:gap-1.5 px-2.5 sm:px-3 py-1.5 rounded-xl text-xs font-semibold text-slate-300 hover:text-white bg-slate-900/90 hover:bg-slate-800 border border-white/15 transition shadow-xs" title="Kembali ke Beranda Utama AR Tilik">
                    <span>🏠</span> <span class="hidden sm:inline">Landing Page</span><span class="sm:hidden">Beranda</span>
                </a>
                <?php if (!empty($currentUser)): ?>
                    <a href="<?= base_url('dashboard') ?>" class="inline-flex items-center gap-1 sm:gap-1.5 px-2.5 sm:px-3 py-1.5 rounded-xl text-xs font-bold text-slate-200 hover:text-white bg-slate-900 hover:bg-slate-800 border border-white/15 transition shadow-xs">
                        <span>&larr;</span> <span class="hidden sm:inline">Kembali ke </span><span>Dashboard</span>
                    </a>
                    <?php if (!empty($activeProfile)): ?>
                        <a href="<?= base_url('ambassador/dashboard') ?>" class="text-xs px-2.5 sm:px-3 py-1.5 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white font-bold transition flex items-center gap-1 sm:gap-1.5 shadow-xs">
                            <span>🎓</span> <span class="hidden sm:inline">Portal </span><span>Duta</span>
                        </a>
                    <?php endif; ?>
                <?php else: ?>
                    <a href="<?= base_url('/?auth=login') ?>" class="text-xs text-slate-300 hover:text-white font-semibold transition px-2 py-1">
                        Masuk
                    </a>
                    <a href="<?= base_url('/#langganan') ?>" class="text-xs px-2.5 sm:px-3 py-1.5 rounded-lg bg-amber-500/20 text-amber-300 border border-amber-500/30 font-semibold hover:bg-amber-500/30 transition">
                        <span class="hidden sm:inline">Paket </span>Pemeriksaan
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <main class="flex-1 py-10 sm:py-16 px-4 sm:px-6">
        <div class="max-w-4xl mx-auto space-y-12">

            <!-- Hero Section -->
            <div class="text-center space-y-4">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-semibold bg-amber-500/15 text-amber-300 border border-amber-500/30 shadow-xs">
                    <span>✨</span> <span>Program Afiliasi &amp; Kemitraan Kampus Resmi</span>
                </div>
                <h1 class="text-3xl sm:text-5xl font-black text-white tracking-tight leading-tight">
                    Jadilah <span class="bg-gradient-to-r from-amber-400 via-amber-300 to-indigo-400 bg-clip-text text-transparent">Duta Kampus AR Tilik</span>
                </h1>
                <p class="text-sm sm:text-base text-slate-300 max-w-2xl mx-auto leading-relaxed">
                    Bantu mahasiswa di kampus Anda menjaga integritas skripsi, tesis, dan naskah publikasi ilmiah. Dapatkan akun premium gratis, sertifikat apresiasi resmi, dan bagi hasil komisi afiliasi 20%.
                </p>
            </div>

            <!-- Manfaat Utama -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="bg-slate-900/60 border border-white/10 rounded-2xl p-5 space-y-2">
                    <div class="w-10 h-10 rounded-xl bg-indigo-500/20 text-indigo-400 flex items-center justify-center text-xl">
                        💎
                    </div>
                    <h3 class="text-sm font-bold text-white">Akun Premium Gratis</h3>
                    <p class="text-xs text-slate-400 leading-relaxed">
                        Akses gratis seluruh engine pemeriksaan naskah (Audit Forensik Cerdas, Sitasi Phantom, Stylometrics AI) selama masa aktif Duta Kampus.
                    </p>
                </div>
                <div class="bg-slate-900/60 border border-white/10 rounded-2xl p-5 space-y-2">
                    <div class="w-10 h-10 rounded-xl bg-emerald-500/20 text-emerald-400 flex items-center justify-center text-xl">
                        💰
                    </div>
                    <h3 class="text-sm font-bold text-white">Komisi 20% Bulanan</h3>
                    <p class="text-xs text-slate-400 leading-relaxed">
                        Bagi hasil langsung 20% dari setiap pembelian paket pemeriksaan melalui kode referral Anda. Dicairkan rutin tiap akhir bulan ke rekening/e-wallet.
                    </p>
                </div>
                <div class="bg-slate-900/60 border border-white/10 rounded-2xl p-5 space-y-2">
                    <div class="w-10 h-10 rounded-xl bg-amber-500/20 text-amber-400 flex items-center justify-center text-xl">
                        📜
                    </div>
                    <h3 class="text-sm font-bold text-white">Sertifikat Resmi Founder</h3>
                    <p class="text-xs text-slate-400 leading-relaxed">
                        Sertifikat Duta Akademik resmi dan rekomendasi profesional LinkedIn untuk memperkuat portofolio karier Anda setelah lolos masa probation.
                    </p>
                </div>
            </div>

            <!-- Alur 6 Tahap Transparan -->
            <div class="bg-gradient-to-b from-slate-900/80 to-indigo-950/30 border border-white/10 rounded-3xl p-6 sm:p-8 space-y-6">
                <div class="flex items-center justify-between flex-wrap gap-2 border-b border-white/10 pb-4">
                    <div>
                        <h2 class="text-base sm:text-lg font-bold text-white flex items-center gap-2">
                            <span>🚀</span> <span>Alur Seleksi &amp; Perjalanan Duta Kampus</span>
                        </h2>
                        <p class="text-xs text-slate-400 mt-0.5">Sederhana, asinkron tanpa wawancara, dan fokus pada kinerja nyata.</p>
                    </div>
                    <span class="text-[11px] font-mono px-2.5 py-1 rounded-full bg-indigo-500/20 text-indigo-300 border border-indigo-500/30">
                        6 Tahapan Jelas
                    </span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
                    <div class="p-4 rounded-xl bg-black/20 border border-white/5 space-y-1.5">
                        <div class="font-bold text-amber-300 flex items-center gap-1.5">
                            <span>1.</span> <span>Pendaftaran Ringan</span>
                        </div>
                        <p class="text-slate-300 leading-relaxed">
                            Cukup isi formulir data dasar &amp; ceritakan jaringan kampus Anda di bawah ini (KTM belum diminta pada tahap ini).
                        </p>
                    </div>
                    <div class="p-4 rounded-xl bg-black/20 border border-white/5 space-y-1.5">
                        <div class="font-bold text-amber-300 flex items-center gap-1.5">
                            <span>2.</span> <span>Seleksi Asinkron</span>
                        </div>
                        <p class="text-slate-300 leading-relaxed">
                            Tim meninjau pengajuan dalam batch mingguan tanpa interview. Pemberitahuan dikirimkan via WhatsApp.
                        </p>
                    </div>
                    <div class="p-4 rounded-xl bg-black/20 border border-white/5 space-y-1.5">
                        <div class="font-bold text-amber-300 flex items-center gap-1.5">
                            <span>3.</span> <span>Verifikasi Identitas</span>
                        </div>
                        <p class="text-slate-300 leading-relaxed">
                            Pendaftar yang diterima mengunggah foto KTM/tanda pengenal (anti-fraud: 1 mahasiswa = 1 kode unik).
                        </p>
                    </div>
                    <div class="p-4 rounded-xl bg-black/20 border border-white/5 space-y-1.5">
                        <div class="font-bold text-amber-300 flex items-center gap-1.5">
                            <span>4.</span> <span>Aktivasi &amp; Paket Sambutan</span>
                        </div>
                        <p class="text-slate-300 leading-relaxed">
                            Kode referral unik terbit, akun premium otomatis aktif, dan menerima panduan 1 halaman via WhatsApp.
                        </p>
                    </div>
                    <div class="p-4 rounded-xl bg-black/20 border border-white/5 space-y-1.5">
                        <div class="font-bold text-amber-300 flex items-center gap-1.5">
                            <span>5.</span> <span>Masa Percobaan 1 Bulan</span>
                        </div>
                        <p class="text-slate-300 leading-relaxed">
                            Bulan pertama: cukup kumpulkan 8 bukti promosi ATAU capai 3 transaksi referral agar lolos menjadi duta penuh.
                        </p>
                    </div>
                    <div class="p-4 rounded-xl bg-black/20 border border-white/5 space-y-1.5">
                        <div class="font-bold text-amber-300 flex items-center gap-1.5">
                            <span>6.</span> <span>Evaluasi &amp; Gajian Bulanan</span>
                        </div>
                        <p class="text-slate-300 leading-relaxed">
                            Unggah rekap di dashboard, komisi 20% dicairkan sebulan sekali ke rekening/e-wallet Anda.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Banner Status Pengguna Saat Ini (Jika Sedang Login) -->
            <?php if (!empty($currentUser)): ?>
                <?php if (!empty($activeProfile)): ?>
                    <div class="bg-gradient-to-r from-emerald-500/20 via-teal-500/20 to-indigo-500/20 border border-emerald-500/40 rounded-2xl p-5 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                        <div class="space-y-1">
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-bold uppercase tracking-wider px-2 py-0.5 rounded bg-emerald-500/30 text-emerald-200 border border-emerald-400/30">
                                    Status: Duta Kampus Aktif
                                </span>
                                <span class="font-mono text-xs font-bold text-amber-300"><?= esc($activeProfile['referral_code']) ?></span>
                            </div>
                            <p class="text-xs text-slate-300">
                                Anda telah resmi terdaftar sebagai Duta Kampus. Silakan akses Dashboard Duta untuk memantau klik, komisi, dan mengunggah bukti promosi.
                            </p>
                        </div>
                        <a href="<?= base_url('ambassador/dashboard') ?>" class="px-5 py-2.5 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-bold text-xs shadow-lg shadow-emerald-500/20 transition whitespace-nowrap">
                            Buka Dashboard Duta &rarr;
                        </a>
                    </div>
                <?php elseif (!empty($existingApp)): ?>
                    <div class="bg-indigo-950/60 border border-indigo-500/30 rounded-2xl p-5 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                        <div class="space-y-1">
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-bold uppercase tracking-wider px-2 py-0.5 rounded bg-amber-500/20 text-amber-300 border border-amber-400/30">
                                    Status Pengajuan: <?= strtoupper(esc($existingApp['status'])) ?>
                                </span>
                                <span class="text-xs text-slate-400">&bull; Diajukan <?= date('d M Y', strtotime($existingApp['created_at'])) ?></span>
                            </div>
                            <p class="text-xs text-slate-300">
                                <?php if ($existingApp['status'] === 'accepted'): ?>
                                    Selamat! Pengajuan Anda telah diterima tahap awal. Silakan lakukan verifikasi foto identitas (KTM).
                                <?php elseif ($existingApp['status'] === 'verified'): ?>
                                    Foto KTM Anda telah terverifikasi! Menunggu aktivasi akhir &amp; penerbitan kode referral dari tim.
                                <?php else: ?>
                                    Pengajuan Anda tersimpan dan sedang menunggu jadwal peninjauan batch mingguan oleh tim kami.
                                <?php endif; ?>
                            </p>
                        </div>
                        <?php if ($existingApp['status'] === 'accepted' && !empty($existingApp['verification_token'])): ?>
                            <a href="<?= base_url('ambassador/verify/' . $existingApp['verification_token']) ?>" class="px-4 py-2 rounded-xl bg-amber-400 hover:bg-amber-300 text-slate-950 font-bold text-xs shadow-md transition whitespace-nowrap">
                                Unggah KTM Sekarang &rarr;
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <!-- Formulir Pendaftaran Tahap 1 -->
            <div class="bg-slate-900 border border-white/10 rounded-3xl p-6 sm:p-8 shadow-2xl relative overflow-hidden" id="formDutaSection">
                <div class="space-y-6">
                    <div class="border-b border-white/10 pb-5">
                        <h2 class="text-lg sm:text-xl font-bold text-white flex items-center gap-2">
                            <span>📝</span> <span>Formulir Pendaftaran Duta Kampus (Tahap 1)</span>
                        </h2>
                        <p class="text-xs text-slate-400 mt-1">
                            Isi formulir sederhana berikut. Bebas ribet, data langsung masuk ke basis data seleksi AR Tilik.
                        </p>
                    </div>

                    <!-- Notifikasi Status Pembelian Paket -->
                    <?php if (!empty($currentUser)): ?>
                        <?php if ($hasPurchased): ?>
                            <div class="bg-emerald-500/10 border border-emerald-500/30 rounded-2xl p-4 flex items-start gap-3">
                                <span class="text-lg text-emerald-400">✓</span>
                                <div class="text-xs text-slate-300">
                                    <strong class="text-emerald-300">Syarat Pembelian Terpenuhi:</strong> Akun Anda terverifikasi telah pernah melakukan pembelian paket di AR Tilik. Anda memenuhi syarat kualifikasi penuh untuk seleksi Duta Kampus.
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="bg-amber-500/10 border border-amber-500/30 rounded-2xl p-4 flex items-start gap-3">
                                <span class="text-lg text-amber-400">💡</span>
                                <div class="text-xs text-slate-300">
                                    <strong class="text-amber-300">Catatan Kualifikasi:</strong> Duta Kampus wajib pernah melakukan pembelian paket pemeriksaan apapun di AR Tilik agar memahami alur kerja dan kualitas naskah secara nyata. Anda tetap dapat mengisi formulir sekarang, namun aktivasi kode referral akan menunggu riwayat pembelian paket pertama Anda. 
                                    <a href="<?= base_url('/#langganan') ?>" target="_blank" class="text-amber-300 font-bold underline hover:text-white ml-1">Lihat Pilihan Paket &rarr;</a>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="bg-indigo-500/10 border border-indigo-500/20 rounded-2xl p-4 flex items-start gap-3">
                            <span class="text-lg text-indigo-400">ℹ️</span>
                            <div class="text-xs text-slate-300">
                                Sudah memiliki akun AR Tilik? <a href="<?= base_url('/?auth=login&redirect=' . urlencode(base_url('ambassador'))) ?>" class="text-indigo-300 font-bold underline hover:text-white">Masuk terlebih dahulu</a> agar formulir terisi otomatis dari profil Anda.
                            </div>
                        </div>
                    <?php endif; ?>

                    <form id="ambassadorForm" onsubmit="submitAmbassadorApplication(event)" class="space-y-5">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-300 mb-1">Nama Lengkap &amp; Gelar:</label>
                                <input type="text" name="full_name" value="<?= esc($currentUser['name'] ?? '') ?>" placeholder="Misal: Andi Pratama" required class="w-full text-xs bg-black/40 border border-white/15 rounded-xl px-3.5 py-2.5 text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-300 mb-1">Email Akun AR Tilik:</label>
                                <input type="email" name="email" value="<?= esc($currentUser['email'] ?? '') ?>" placeholder="nama@kampus.ac.id / email pendaftaran" required class="w-full text-xs bg-black/40 border border-white/15 rounded-xl px-3.5 py-2.5 text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-300 mb-1">Nomor WhatsApp Aktif:</label>
                                <input type="text" name="whatsapp_number" placeholder="Misal: 081234567890" required class="w-full text-xs bg-black/40 border border-white/15 rounded-xl px-3.5 py-2.5 text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                <span class="text-[10px] text-slate-500">Pemberitahuan seleksi dikirim via WhatsApp</span>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-300 mb-1">Perguruan Tinggi / Kampus:</label>
                                <input type="text" name="institution" value="<?= esc($currentUser['institution'] ?? '') ?>" placeholder="Misal: Universitas Indonesia" required class="w-full text-xs bg-black/40 border border-white/15 rounded-xl px-3.5 py-2.5 text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-300 mb-1">Jurusan / Program Studi:</label>
                                <input type="text" name="major" placeholder="Misal: Ilmu Komputer / Manajemen" required class="w-full text-xs bg-black/40 border border-white/15 rounded-xl px-3.5 py-2.5 text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-300 mb-1">Semester / Tingkat Saat Ini:</label>
                            <select name="semester" required class="w-full text-xs bg-slate-950 border border-white/15 rounded-xl px-3.5 py-2.5 text-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                <option value="Semester 5 - 6 (Persiapan Judul)">Semester 5 - 6 (Persiapan Judul / Magang)</option>
                                <option value="Semester 7 - 8 (Penyusunan Skripsi)" selected>Semester 7 - 8 (Penyusunan Skripsi / Tugas Akhir)</option>
                                <option value="Semester 9+ (Finalisasi Sidang)">Semester 9+ (Finalisasi Sidang / Revisi)</option>
                                <option value="Pascasarjana (Magister / Doktoral)">Pascasarjana (Magister / Doktoral)</option>
                                <option value="Dosen / Asisten Laboratorium">Dosen / Asisten Laboratorium / Peneliti</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-300 mb-1">
                                Ceritakan Jaringan Kampus Anda:
                            </label>
                            <textarea name="campus_network" rows="3" required placeholder="Contoh: Saya aktif di BEM Fakultas, pengurus Himpunan Mahasiswa Jurusan, admin grup WhatsApp angkatan 2021 (150 mahasiswa), dan sering berdiskusi dengan mahasiswa tingkat akhir yang butuh cek naskah turnitin/tilik..." class="w-full text-xs bg-black/40 border border-white/15 rounded-xl p-3.5 text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 leading-relaxed"></textarea>
                            <p class="text-[10px] text-slate-500 mt-1">
                                Ceritakan organisasi, kepanitiaan, atau grup komunitas akademik yang Anda ikuti. Bagian ini menjadi kriteria utama seleksi asinkron tim kami.
                            </p>
                        </div>

                        <div class="pt-2 flex items-center justify-between flex-wrap gap-4 border-t border-white/10">
                            <div class="text-[11px] text-slate-400">
                                🔒 Data Anda dijaga kerahasiaannya dan hanya digunakan untuk kurasi program Duta Kampus AR Tilik.
                            </div>
                            <button type="submit" id="btnSubmitDuta" class="px-6 py-3 rounded-xl bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 active:scale-95 text-slate-950 font-bold text-xs shadow-lg shadow-amber-500/20 transition flex items-center gap-2 cursor-pointer">
                                <span>🚀</span>
                                <span id="btnSubmitDutaText">Kirim Pendaftaran Duta Kampus</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </main>

    <!-- Footer Minimalis -->
    <footer class="border-t border-white/10 py-6 text-center text-xs text-slate-500">
        &copy; <?= date('Y') ?> AR Tilik &bull; Kedaulatan Integritas Akademik Indonesia.
    </footer>

    <script>
        async function submitAmbassadorApplication(e) {
            e.preventDefault();
            const btn = document.getElementById('btnSubmitDuta');
            const btnText = document.getElementById('btnSubmitDutaText');
            const form = document.getElementById('ambassadorForm');
            const formData = new FormData(form);

            btn.disabled = true;
            btnText.innerText = 'Mengirimkan Pendaftaran...';

            try {
                const res = await fetch('<?= base_url('ambassador/apply') ?>', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                const data = await res.json();

                if (res.ok && data.success) {
                    alert('🎉 ' + data.message);
                    window.location.reload();
                } else {
                    alert('⚠️ ' + (data.message || 'Gagal mengirimkan pendaftaran. Silakan periksa kembali formulir Anda.'));
                }
            } catch (err) {
                console.error('Error submitting application:', err);
                alert('Terjadi kesalahan jaringan. Silakan coba kembali.');
            } finally {
                btn.disabled = false;
                btnText.innerText = 'Kirim Pendaftaran Duta Kampus';
            }
        }
    </script>
</body>
</html>
