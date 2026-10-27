<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panduan &amp; Welcome Kit Duta Kampus &bull; AR Tilik</title>
    
    <!-- Primary SEO Meta Tags -->
    <meta name="description" content="Buku Panduan Resmi &amp; Welcome Kit Duta Kampus AR Tilik: Pedoman edukasi etika integritas karya ilmiah, materi promosi resmi, dan tata cara klaim insentif.">
    <meta name="robots" content="index, follow, max-snippet:-1, max-image-preview:large">
    <link rel="canonical" href="<?= base_url('ambassador/guide') ?>">
    <meta name="theme-color" content="#4338ca">

    <!-- Open Graph / Social Sharing -->
    <meta property="og:type" content="article">
    <meta property="og:locale" content="id_ID">
    <meta property="og:site_name" content="AR Tilik">
    <meta property="og:title" content="Panduan &amp; Welcome Kit Duta Kampus — AR Tilik">
    <meta property="og:description" content="Pedoman resmi perwakilan kampus AR Tilik untuk sosialisasi kesadaran integritas akademik dan program kemitraan mahasiswa.">
    <meta property="og:url" content="<?= base_url('ambassador/guide') ?>">
    <meta property="og:image" content="<?= base_url('images/og-artilik.png') ?>">

    <!-- Twitter Cards -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:site" content="@ARSetontong">
    <meta name="twitter:creator" content="@ARSetontong">
    <meta name="twitter:title" content="Panduan &amp; Welcome Kit Duta Kampus — AR Tilik">
    <meta name="twitter:description" content="Pedoman resmi perwakilan kampus AR Tilik untuk sosialisasi kesadaran integritas akademik dan program kemitraan mahasiswa.">
    <meta name="twitter:image" content="<?= base_url('images/og-artilik.png') ?>">

    <!-- Schema.org JSON-LD Structured Data -->
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@graph": [
        {
          "@type": "WebPage",
          "@id": "<?= base_url('ambassador/guide#webpage') ?>",
          "url": "<?= base_url('ambassador/guide') ?>",
          "name": "Panduan & Welcome Kit Duta Kampus — AR Tilik",
          "description": "Buku Panduan Resmi & Welcome Kit Duta Kampus AR Tilik: Pedoman edukasi etika integritas karya ilmiah, materi promosi resmi, dan tata cara klaim insentif.",
          "inLanguage": "id-ID",
          "publisher": {
            "@type": "Organization",
            "name": "ARSetontong",
            "url": "https://www.arsetontong.top"
          }
        },
        {
          "@type": "BreadcrumbList",
          "@id": "<?= base_url('ambassador/guide#breadcrumb') ?>",
          "itemListElement": [
            {
              "@type": "ListItem",
              "position": 1,
              "name": "Beranda",
              "item": "<?= base_url() ?>"
            },
            {
              "@type": "ListItem",
              "position": 2,
              "name": "Duta Kampus",
              "item": "<?= base_url('ambassador') ?>"
            },
            {
              "@type": "ListItem",
              "position": 3,
              "name": "Panduan & Kit",
              "item": "<?= base_url('ambassador/guide') ?>"
            }
          ]
        }
      ]
    }
    </script>

    <!-- Favicon & Brand Icons -->
    <link rel="icon" type="image/x-icon" href="<?= base_url('favicon.ico?v=20261008') ?>">
    <link rel="icon" type="image/png" sizes="32x32" href="<?= base_url('favicon-32x32.png?v=20261008') ?>">
    <link rel="icon" type="image/png" sizes="16x16" href="<?= base_url('favicon-16x16.png?v=20261008') ?>">
    <link rel="apple-touch-icon" sizes="180x180" href="<?= base_url('apple-touch-icon.png?v=20261008') ?>">
    <link rel="shortcut icon" type="image/x-icon" href="<?= base_url('favicon.ico?v=20261008') ?>">
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .font-mono-code { font-family: 'JetBrains Mono', monospace; }
        .toast-copied {
            transform: translateY(0);
            opacity: 1;
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        }
    </style>
</head>
<body class="bg-slate-950 text-slate-100 min-h-screen flex flex-col antialiased selection:bg-amber-500 selection:text-slate-950">

    <!-- Header Navigasi -->
    <header class="border-b border-white/10 bg-slate-950/80 backdrop-blur-md sticky top-0 z-40">
        <div class="max-w-5xl mx-auto px-3 sm:px-6 min-h-[3.75rem] py-2 sm:py-0 sm:h-16 flex items-center justify-between gap-2">
            <a href="<?= base_url() ?>" class="flex items-center gap-2 group shrink-0">
                <img src="<?= base_url('images/logo.png') ?>" alt="AR Tilik Logo" class="w-8 h-8 sm:w-9 sm:h-9 object-contain drop-shadow-sm group-hover:scale-105 transition-transform duration-200">
                <div>
                    <span class="font-bold text-sm tracking-tight text-white group-hover:text-indigo-300 transition">AR Tilik</span>
                    <span class="text-[10px] text-amber-400 font-mono block leading-none">Welcome Kit Duta Kampus</span>
                </div>
            </a>
            <div class="flex items-center gap-1.5 sm:gap-2.5 flex-wrap justify-end">
                <a href="<?= base_url() ?>" class="inline-flex items-center gap-1 sm:gap-1.5 px-2.5 sm:px-3 py-1.5 rounded-xl text-xs font-semibold text-slate-300 hover:text-white bg-slate-900/90 hover:bg-slate-800 border border-white/15 transition shadow-xs" title="Kembali ke Beranda Utama AR Tilik">
                    <span>🏠</span> <span class="hidden sm:inline">Landing Page</span><span class="sm:hidden">Beranda</span>
                </a>
                <?php if (!empty($profile)): ?>
                    <a href="<?= base_url('ambassador/dashboard') ?>" class="inline-flex items-center gap-1 sm:gap-1.5 px-2.5 sm:px-3 py-1.5 rounded-xl text-xs font-bold text-amber-300 hover:text-white bg-amber-500/15 hover:bg-amber-500/25 border border-amber-500/30 transition shadow-xs">
                        <span>📊</span> <span class="hidden sm:inline">Dashboard </span><span>Duta</span>
                    </a>
                <?php else: ?>
                    <a href="<?= base_url('ambassador') ?>" class="inline-flex items-center gap-1 sm:gap-1.5 px-2.5 sm:px-3 py-1.5 rounded-xl text-xs font-bold text-amber-300 hover:text-white bg-amber-500/15 hover:bg-amber-500/25 border border-amber-500/30 transition shadow-xs">
                        <span>🎓</span> <span class="hidden sm:inline">Daftar </span><span>Duta</span>
                    </a>
                <?php endif; ?>
                <a href="<?= base_url('dashboard') ?>" class="inline-flex items-center gap-1 sm:gap-1.5 px-2.5 sm:px-3 py-1.5 rounded-xl text-xs font-bold text-slate-300 hover:text-white bg-slate-900 hover:bg-slate-800 border border-white/15 transition shadow-xs">
                    <span>&larr;</span> <span class="hidden sm:inline">Dashboard </span><span>Pengguna</span>
                </a>
            </div>
        </div>
    </header>

    <!-- Main Container -->
    <main class="flex-1 py-8 sm:py-12 px-4 sm:px-6">
        <div class="max-w-4xl mx-auto space-y-10">

            <!-- Hero Banner: Sambutan & Ringkasan Peran -->
            <div class="bg-gradient-to-br from-indigo-950 via-slate-900 to-slate-950 border border-amber-500/30 rounded-3xl p-6 sm:p-9 shadow-2xl relative overflow-hidden">
                <div class="absolute -right-10 -bottom-10 w-64 h-64 bg-amber-500/10 rounded-full blur-3xl pointer-events-none"></div>
                <div class="relative z-10 space-y-3">
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-amber-400/15 border border-amber-400/30 text-amber-300 text-xs font-bold uppercase tracking-wider">
                        <span>🎓</span> <span>Paket Sambutan &amp; Panduan Taktis 1 Halaman</span>
                    </div>
                    <h1 class="text-2xl sm:text-4xl font-extrabold text-white tracking-tight leading-tight">
                        Panduan Sukses <span class="bg-gradient-to-r from-amber-400 to-amber-200 bg-clip-text text-transparent">Duta Kampus AR Tilik</span>
                    </h1>
                    <p class="text-xs sm:text-sm text-slate-300 max-w-2xl leading-relaxed">
                        Selamat datang di jajaran perwakilan resmi AR Tilik! Halaman ini adalah panduan lengkap yang merangkum strategi promosi, materi siap pakai (copy-paste), tata cara klaim komisi, dan etika akademik agar Anda langsung menghasilkan dampak nyata.
                    </p>
                </div>

                <!-- Amunisi Referral Duta (Copy Quick Box) -->
                <div class="mt-6 pt-6 border-t border-white/10 grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="bg-black/40 border border-white/10 rounded-2xl p-4 space-y-1.5">
                        <div class="text-[11px] font-semibold text-slate-400">Kode Referral Unik Anda:</div>
                        <div class="flex items-center justify-between gap-2">
                            <span class="text-base font-black text-amber-300 font-mono-code tracking-wider" id="refCodeText"><?= esc($referralCode) ?></span>
                            <button type="button" onclick="copySnippet('<?= esc($referralCode) ?>', this)" class="px-3 py-1 rounded-lg bg-amber-500 hover:bg-amber-400 text-slate-950 text-xs font-bold transition cursor-pointer">
                                Salin Kode
                            </button>
                        </div>
                    </div>
                    <div class="bg-black/40 border border-white/10 rounded-2xl p-4 space-y-1.5">
                        <div class="text-[11px] font-semibold text-slate-400">Tautan Pendaftaran Referral:</div>
                        <div class="flex items-center justify-between gap-2">
                            <span class="text-xs text-indigo-300 font-mono-code truncate max-w-[200px] sm:max-w-[240px]" id="refUrlText"><?= esc($referralUrl) ?></span>
                            <button type="button" onclick="copySnippet('<?= esc($referralUrl) ?>', this)" class="px-3 py-1 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold transition cursor-pointer whitespace-nowrap">
                                Salin Link
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bagian 1: Hak Istimewa & Skema Reward Duta -->
            <div class="space-y-4">
                <div class="flex items-center gap-2">
                    <span class="w-7 h-7 rounded-lg bg-amber-400/20 text-amber-300 flex items-center justify-center font-bold text-sm">1</span>
                    <h2 class="text-lg font-bold text-white tracking-tight">Hak Istimewa &amp; Benefit Duta Kampus</h2>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="bg-slate-900 border border-white/10 rounded-2xl p-4 space-y-2">
                        <div class="text-2xl">💎</div>
                        <h3 class="text-xs font-bold text-white uppercase tracking-wider">Akun Premium 30 Hari</h3>
                        <p class="text-xs text-slate-400 leading-relaxed">
                            Akses penuh tanpa batas ke seluruh engine: Audit Forensik Cerdas, Sitasi Phantom, dan Stylometrics AI.
                        </p>
                    </div>
                    <div class="bg-slate-900 border border-white/10 rounded-2xl p-4 space-y-2">
                        <div class="text-2xl">💰</div>
                        <h3 class="text-xs font-bold text-amber-300 uppercase tracking-wider">Komisi 20% Tiap Transaksi</h3>
                        <p class="text-xs text-slate-400 leading-relaxed">
                            Dapatkan bagi hasil 20% untuk setiap transaksi pembelian paket naskah dari rekan yang mendaftar lewat link Anda.
                        </p>
                    </div>
                    <div class="bg-slate-900 border border-white/10 rounded-2xl p-4 space-y-2">
                        <div class="text-2xl">🎁</div>
                        <h3 class="text-xs font-bold text-emerald-300 uppercase tracking-wider">Bonus +1 Kuota Teman</h3>
                        <p class="text-xs text-slate-400 leading-relaxed">
                            Teman yang Anda ajak otomatis mendapat reward bonus +1 kuota cek naskah gratis saat mendaftar.
                        </p>
                    </div>
                    <div class="bg-slate-900 border border-white/10 rounded-2xl p-4 space-y-2">
                        <div class="text-2xl">📜</div>
                        <h3 class="text-xs font-bold text-indigo-300 uppercase tracking-wider">Sertifikat Pengabdian</h3>
                        <p class="text-xs text-slate-400 leading-relaxed">
                            Sertifikat resmi Duta Integritas Akademik dari AR Tilik yang dapat memperkuat portofolio &amp; CV Anda.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Bagian 2: Alur 3 Langkah Praktis -->
            <div class="bg-slate-900/60 border border-white/10 rounded-3xl p-6 sm:p-7 space-y-4">
                <div class="flex items-center gap-2">
                    <span class="w-7 h-7 rounded-lg bg-indigo-500/20 text-indigo-300 flex items-center justify-center font-bold text-sm">2</span>
                    <h2 class="text-lg font-bold text-white tracking-tight">Alur Kerja 3 Langkah Menghasilkan Komisi</h2>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-xs">
                    <div class="bg-black/30 border border-white/5 rounded-2xl p-4 space-y-2 relative">
                        <span class="text-xs font-bold text-amber-400 font-mono-code block">LANGKAH 1</span>
                        <h3 class="font-bold text-white text-sm">Salin Materi &amp; Link Anda</h3>
                        <p class="text-slate-400 leading-relaxed">
                            Gunakan salah satu template copywriting di bawah yang telah dirancang khusus sesuai audiens kampus Anda.
                        </p>
                    </div>
                    <div class="bg-black/30 border border-white/5 rounded-2xl p-4 space-y-2 relative">
                        <span class="text-xs font-bold text-indigo-400 font-mono-code block">LANGKAH 2</span>
                        <h3 class="font-bold text-white text-sm">Sebarkan ke Kanal Kampus</h3>
                        <p class="text-slate-400 leading-relaxed">
                            Posting di Story WhatsApp, Instagram, grup angkatan, grup pejuang skripsi, atau japri rekan satu bimbingan.
                        </p>
                    </div>
                    <div class="bg-black/30 border border-white/5 rounded-2xl p-4 space-y-2 relative">
                        <span class="text-xs font-bold text-emerald-400 font-mono-code block">LANGKAH 3</span>
                        <h3 class="font-bold text-white text-sm">Tangkapan Layar &amp; Unggah</h3>
                        <p class="text-slate-400 leading-relaxed">
                            Ambil tangkapan layar (screenshot) postingan Anda, unggah di Dashboard Duta, dan pantau komisi Anda bertambah.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Bagian 3: Template Copywriting Siap Pakai (1-Click Copy) -->
            <div class="space-y-4">
                <div class="flex items-center justify-between flex-wrap gap-2">
                    <div class="flex items-center gap-2">
                        <span class="w-7 h-7 rounded-lg bg-emerald-500/20 text-emerald-300 flex items-center justify-center font-bold text-sm">3</span>
                        <h2 class="text-lg font-bold text-white tracking-tight">Template Copywriting Siap Salin (1-Klik Copy)</h2>
                    </div>
                    <span class="text-xs text-slate-400">Pilih template yang paling pas dengan gaya Anda</span>
                </div>

                <div class="space-y-4">
                    <!-- Template 1: Story WhatsApp & Instagram -->
                    <div class="bg-slate-900 border border-white/10 rounded-2xl p-5 space-y-3">
                        <div class="flex items-center justify-between flex-wrap gap-2">
                            <div class="flex items-center gap-2">
                                <span class="px-2 py-0.5 rounded text-[11px] font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                                    📱 Story WhatsApp / Instagram
                                </span>
                                <span class="text-xs text-slate-400">Kasual &bull; Memancing rasa ingin tahu</span>
                            </div>
                            <button type="button" onclick="copyTemplate('templateWaStory', this)" class="px-3.5 py-1.5 rounded-xl bg-amber-500 hover:bg-amber-400 text-slate-950 text-xs font-bold transition flex items-center gap-1.5 cursor-pointer">
                                <span>📋</span> <span>Salin Teks Story</span>
                            </button>
                        </div>
                        <div class="bg-black/50 border border-white/10 rounded-xl p-4 text-xs font-mono-code text-slate-200 leading-relaxed whitespace-pre-line" id="templateWaStory">Buat teman-teman yang lagi ngerjain BAB 3/4, Skripsi, atau Artikel Jurnal 🎓

Jangan sampai kena revisi fatal gara-gara salah kutip atau sitasi tidak sengaja kedeteksi plagiat merah.

Coba cek naskah kalian di AR Tilik (Engine buatan sivitas akademika Indonesia):
✅ Tidak menyimpan naskah ke internet (Non-repository, aman dari bocor)
✅ Ada Audit Forensik penalaran &amp; simulasi pertanyaan dosen penguji
✅ Deteksi kutipan masa depan (phantom citation)

🔗 Cek di sini: <?= esc($referralUrl) ?>

Gunakan kode saya: *<?= esc($referralCode) ?>* buat klaim +1 kuota cek naskah gratis! 🚀</div>
                    </div>

                    <!-- Template 2: Grup Angkatan / Pejuang Skripsi -->
                    <div class="bg-slate-900 border border-white/10 rounded-2xl p-5 space-y-3">
                        <div class="flex items-center justify-between flex-wrap gap-2">
                            <div class="flex items-center gap-2">
                                <span class="px-2 py-0.5 rounded text-[11px] font-bold bg-indigo-500/20 text-indigo-300 border border-indigo-500/30">
                                    👥 Siaran Grup WhatsApp Angkatan / Pejuang Skripsi
                                </span>
                                <span class="text-xs text-slate-400">Edukasi &bull; Rekomendasi sebelum bimbingan</span>
                            </div>
                            <button type="button" onclick="copyTemplate('templateGroupBroadcast', this)" class="px-3.5 py-1.5 rounded-xl bg-amber-500 hover:bg-amber-400 text-slate-950 text-xs font-bold transition flex items-center gap-1.5 cursor-pointer">
                                <span>📋</span> <span>Salin Teks Grup</span>
                            </button>
                        </div>
                        <div class="bg-black/50 border border-white/10 rounded-xl p-4 text-xs font-mono-code text-slate-200 leading-relaxed whitespace-pre-line" id="templateGroupBroadcast">Halo rekan-rekan pejuang skripsi &amp; karya ilmiah 👋

Izin berbagi informasi bermanfaat. Sebelum naskah diserahkan ke dosen pembimbing atau sidang munaqasyah/yudisium, sangat disarankan cek kemiripan teks terlebih dahulu di AR Tilik:
👉 <?= esc($referralUrl) ?>

Kenapa AR Tilik sangat membantu?
1. Privasi Terjaga: Naskah tidak disimpan ke database umum (anti plagiasi sendiri di kemudian hari).
2. Audit Forensik Cerdas: Memberikan kisi-kisi pertanyaan sidang terkait metodologi &amp; sitasi naskah.
3. Keadilan Akademik: Mampu membedakan sitasi resmi vs plagiarisme nyata secara objektif.

Teman-teman yang daftar lewat tautan di atas otomatis dapat tambahan *+1 kuota naskah gratis* dengan kode: *<?= esc($referralCode) ?>*.

Semoga lancar dan cepat wisuda semuanya! ✨🎓</div>
                    </div>

                    <!-- Template 3: Chat Japri Teman / Rekan Bimbingan -->
                    <div class="bg-slate-900 border border-white/10 rounded-2xl p-5 space-y-3">
                        <div class="flex items-center justify-between flex-wrap gap-2">
                            <div class="flex items-center gap-2">
                                <span class="px-2 py-0.5 rounded text-[11px] font-bold bg-purple-500/20 text-purple-300 border border-purple-500/30">
                                    💬 Japri Rekan Seperjuangan / Teman Bimbingan
                                </span>
                                <span class="text-xs text-slate-400">Personal &bull; Saling bantu</span>
                            </div>
                            <button type="button" onclick="copyTemplate('templateJapri', this)" class="px-3.5 py-1.5 rounded-xl bg-amber-500 hover:bg-amber-400 text-slate-950 text-xs font-bold transition flex items-center gap-1.5 cursor-pointer">
                                <span>📋</span> <span>Salin Teks Japri</span>
                            </button>
                        </div>
                        <div class="bg-black/50 border border-white/10 rounded-xl p-4 text-xs font-mono-code text-slate-200 leading-relaxed whitespace-pre-line" id="templateJapri">Halo bro/sis! Gimana progres bab/naskahmu?

Btw kemarin aku coba cek naskah di AR Tilik, hasilnya detail banget sampai ada panduan bedah sitasi dan persiapan sidang skripsi. Naskahnya juga dijamin aman gak bakal bocor ke publik.

Coba daftar lewat link ini deh: <?= esc($referralUrl) ?>
Pake kodeku *<?= esc($referralCode) ?>* biar dapet bonus cek gratis. Siapa tahu ngebantu banget sebelum jadwal bimbinganmu besok! 👍</div>
                    </div>

                    <!-- Template 4: Media Sosial (X / Twitter / LinkedIn / TikTok) -->
                    <div class="bg-slate-900 border border-white/10 rounded-2xl p-5 space-y-3">
                        <div class="flex items-center justify-between flex-wrap gap-2">
                            <div class="flex items-center gap-2">
                                <span class="px-2 py-0.5 rounded text-[11px] font-bold bg-sky-500/20 text-sky-300 border border-sky-500/30">
                                    🌐 X (Twitter) / LinkedIn / Medsos
                                </span>
                                <span class="text-xs text-slate-400">Publik &bull; Profesional</span>
                            </div>
                            <button type="button" onclick="copyTemplate('templateMedsos', this)" class="px-3.5 py-1.5 rounded-xl bg-amber-500 hover:bg-amber-400 text-slate-950 text-xs font-bold transition flex items-center gap-1.5 cursor-pointer">
                                <span>📋</span> <span>Salin Teks Medsos</span>
                            </button>
                        </div>
                        <div class="bg-black/50 border border-white/10 rounded-xl p-4 text-xs font-mono-code text-slate-200 leading-relaxed whitespace-pre-line" id="templateMedsos">Tips buat teman-teman akademisi &amp; mahasiswa akhir: Sebelum submit skripsi atau artikel ke jurnal, pastikan uji similarity naskah secara transparan.

AR Tilik hadir dengan pendekatan evidence-based fairness &amp; audit forensik semantik. Bebas risiko naskah tersimpan ke repositori publik.

Akses &amp; coba gratis di sini: <?= esc($referralUrl) ?> (Gunakan kode: <?= esc($referralCode) ?> untuk bonus kuota).

#Skripsi #PejuangSkripsi #Akademik #MahasiswaAkhir #ARTilik</div>
                    </div>
                </div>
            </div>

            <!-- Bagian 4: Standar Bukti Screenshot Promosi yang Sah -->
            <div class="bg-slate-900 border border-white/10 rounded-3xl p-6 sm:p-7 space-y-4">
                <div class="flex items-center gap-2">
                    <span class="w-7 h-7 rounded-lg bg-amber-500/20 text-amber-300 flex items-center justify-center font-bold text-sm">4</span>
                    <h2 class="text-lg font-bold text-white tracking-tight">Kriteria Bukti Promosi yang Sah (Cepat Disetujui Admin)</h2>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-xs">
                    <div class="bg-black/30 border border-white/5 rounded-2xl p-4 space-y-2">
                        <div class="font-bold text-white flex items-center gap-2">
                            <span>🟢</span> <span>Story WA / IG Story</span>
                        </div>
                        <p class="text-slate-400 leading-relaxed">
                            Screenshot tangkapan layar yang menampilkan teks promosi, link/kode referral Anda, dan jam tayang (setelah tayang minimal 1 jam agar terlihat ada yang melihat).
                        </p>
                    </div>
                    <div class="bg-black/30 border border-white/5 rounded-2xl p-4 space-y-2">
                        <div class="font-bold text-white flex items-center gap-2">
                            <span>🟢</span> <span>Grup WA / Telegram</span>
                        </div>
                        <p class="text-slate-400 leading-relaxed">
                            Screenshot pesan siaran di grup angkatan, himpunan, atau komunitas skripsi. Pastikan nama grup dan isi pesan promosi terbaca jelas.
                        </p>
                    </div>
                    <div class="bg-black/30 border border-white/5 rounded-2xl p-4 space-y-2">
                        <div class="font-bold text-white flex items-center gap-2">
                            <span>🟢</span> <span>Seminar / Poster Offline</span>
                        </div>
                        <p class="text-slate-400 leading-relaxed">
                            Foto mading kampus, poster yang ditempel, atau dokumentasi saat Anda memperkenalkan AR Tilik dalam diskusi kelompok riset/laboratorium.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Bagian 5: Etika Promosi Akademik (Do's & Don'ts) -->
            <div class="bg-slate-900/60 border border-white/10 rounded-3xl p-6 sm:p-7 space-y-4">
                <div class="flex items-center gap-2">
                    <span class="w-7 h-7 rounded-lg bg-rose-500/20 text-rose-300 flex items-center justify-center font-bold text-sm">5</span>
                    <h2 class="text-lg font-bold text-white tracking-tight">Etika Akademik Duta Kampus (Do's &amp; Don'ts)</h2>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                    <div class="bg-emerald-950/20 border border-emerald-500/20 rounded-2xl p-4 space-y-2">
                        <div class="font-bold text-emerald-400 text-sm flex items-center gap-2">
                            <span>✓</span> <span>YANG DISARANKAN (DO's)</span>
                        </div>
                        <ul class="space-y-1.5 text-slate-300 list-disc list-inside leading-relaxed">
                            <li>Fokus mengedukasi integritas akademik &amp; perlindungan naskah (non-repository).</li>
                            <li>Bantu rekan yang bingung membaca skor kemiripan atau daftar pustaka.</li>
                            <li>Gunakan bahasa yang sopan, solutif, dan membangun semangat teman.</li>
                        </ul>
                    </div>
                    <div class="bg-rose-950/20 border border-rose-500/20 rounded-2xl p-4 space-y-2">
                        <div class="font-bold text-rose-400 text-sm flex items-center gap-2">
                            <span>✕</span> <span>YANG DILARANG (DON'Ts)</span>
                        </div>
                        <ul class="space-y-1.5 text-slate-300 list-disc list-inside leading-relaxed">
                            <li>Melakukan spam berulang-ulang di grup umum tanpa izin admin grup.</li>
                            <li>Menjanjikan jaminan kelulusan mutlak tanpa revisi dari dosen pembimbing.</li>
                            <li>Menyalahgunakan akun untuk memeriksa naskah curian atau melanggar hak cipta.</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Bottom CTA Hub -->
            <div class="bg-gradient-to-r from-amber-500/20 via-indigo-500/20 to-purple-500/20 border border-amber-500/40 rounded-3xl p-6 sm:p-8 text-center space-y-4">
                <h3 class="text-lg sm:text-xl font-bold text-white">
                    Siap Beraksi Hari Ini? 🚀
                </h3>
                <p class="text-xs sm:text-sm text-slate-300 max-w-xl mx-auto leading-relaxed">
                    Salin salah satu template di atas, bagikan sekarang juga, dan unggah bukti postingan Anda di Dashboard Duta Kampus!
                </p>
                <div class="flex items-center justify-center gap-3 flex-wrap pt-2">
                    <?php 
                        $quickWaUrl = "https://wa.me/?text=" . urlencode("Halo teman-teman! Buat yang butuh cek kemiripan naskah akurat tanpa bocor ke internet, cobain AR Tilik: " . $referralUrl . " (Pakai kode: *" . $referralCode . "* buat klaim bonus cek gratis!)");
                    ?>
                    <a href="<?= $quickWaUrl ?>" target="_blank" class="px-5 py-3 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold transition flex items-center gap-2 shadow-lg shadow-emerald-600/30">
                        <span>📲</span> <span>Buka WhatsApp &amp; Bagikan Langsung</span>
                    </a>
                    <?php if (!empty($profile)): ?>
                        <a href="<?= base_url('ambassador/dashboard') ?>" class="px-5 py-3 rounded-xl bg-amber-500 hover:bg-amber-400 text-slate-950 text-xs font-bold transition flex items-center gap-2 shadow-lg shadow-amber-500/30">
                            <span>📊</span> <span>Buka Dashboard Duta Kampus</span> &rarr;
                        </a>
                    <?php else: ?>
                        <a href="<?= base_url('ambassador') ?>" class="px-5 py-3 rounded-xl bg-amber-500 hover:bg-amber-400 text-slate-950 text-xs font-bold transition flex items-center gap-2 shadow-lg shadow-amber-500/30">
                            <span>🎓</span> <span>Daftar Menjadi Duta Kampus</span> &rarr;
                        </a>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </main>

    <!-- Footer -->
    <footer class="border-t border-white/10 bg-slate-950 py-6 text-center text-xs text-slate-500">
        &copy; <?= date('Y') ?> AR Tilik &bull; Program Kemitraan Akademik &amp; Duta Kampus Indonesia.
    </footer>

    <!-- Toast Notification Notifikasi Salin -->
    <div id="copyToast" class="fixed bottom-6 right-6 bg-emerald-500 text-slate-950 px-4 py-2.5 rounded-2xl font-bold text-xs shadow-2xl flex items-center gap-2 pointer-events-none opacity-0 translate-y-4 transition-all duration-300 z-50">
        <span>✓</span> <span id="toastMessage">Teks berhasil disalin ke clipboard!</span>
    </div>

    <!-- Script Salin Cepat -->
    <script>
        function showToast(msg) {
            const toast = document.getElementById('copyToast');
            const toastMsg = document.getElementById('toastMessage');
            if (!toast) return;
            toastMsg.innerText = msg || 'Teks berhasil disalin ke clipboard!';
            toast.classList.remove('opacity-0', 'translate-y-4');
            toast.classList.add('opacity-100', 'translate-y-0');
            setTimeout(() => {
                toast.classList.remove('opacity-100', 'translate-y-0');
                toast.classList.add('opacity-0', 'translate-y-4');
            }, 2500);
        }

        function copySnippet(text, btn) {
            if (!navigator.clipboard) {
                const ta = document.createElement('textarea');
                ta.value = text;
                document.body.appendChild(ta);
                ta.select();
                document.execCommand('copy');
                document.body.removeChild(ta);
            } else {
                navigator.clipboard.writeText(text);
            }
            if (btn) {
                const orig = btn.innerText;
                btn.innerText = '✓ Tersalin!';
                setTimeout(() => { btn.innerText = orig; }, 2000);
            }
            showToast('Teks berhasil disalin: ' + text);
        }

        function copyTemplate(elemId, btn) {
            const elem = document.getElementById(elemId);
            if (!elem) return;
            const text = elem.innerText;
            if (!navigator.clipboard) {
                const ta = document.createElement('textarea');
                ta.value = text;
                document.body.appendChild(ta);
                ta.select();
                document.execCommand('copy');
                document.body.removeChild(ta);
            } else {
                navigator.clipboard.writeText(text);
            }
            if (btn) {
                const origHtml = btn.innerHTML;
                btn.innerHTML = '<span>✓</span> <span>Tersalin!</span>';
                setTimeout(() => { btn.innerHTML = origHtml; }, 2000);
            }
            showToast('Template copywriting berhasil disalin!');
        }
    </script>
</body>
</html>
