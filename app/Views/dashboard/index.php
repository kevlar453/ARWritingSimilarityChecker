<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AR Tilik - Academic Writing Similarity Checker</title>
    <!-- Favicon & Brand Icons -->
    <link rel="icon" type="image/x-icon" href="<?= base_url('favicon.ico?v=20261006') ?>">
    <link rel="icon" type="image/png" sizes="32x32" href="<?= base_url('favicon-32x32.png?v=20261006') ?>">
    <link rel="icon" type="image/png" sizes="16x16" href="<?= base_url('favicon-16x16.png?v=20261006') ?>">
    <link rel="apple-touch-icon" sizes="180x180" href="<?= base_url('apple-touch-icon.png?v=20261006') ?>">
    <link rel="shortcut icon" type="image/x-icon" href="<?= base_url('favicon.ico?v=20261006') ?>">
    <link rel="manifest" href="<?= base_url('site.webmanifest') ?>">
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .font-mono-code { font-family: 'JetBrains Mono', monospace; }
        /* Hide scrollbars for chrome, safari and opera */
        .no-scrollbar::-webkit-scrollbar { display: none; }
        /* Hide scrollbars for IE, Edge and Firefox */
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
    </style>
</head>
<body class="bg-slate-50 text-slate-800 antialiased min-h-screen flex flex-col">

    <!-- Top Loading Progress Bar -->
    <div id="topProgressBar" class="fixed top-0 left-0 h-1 bg-gradient-to-r from-indigo-500 via-purple-500 to-indigo-600 z-50 w-0 transition-all duration-300 ease-out pointer-events-none"></div>

    <!-- Page Transition Spinner Overlay -->
    <div id="pageTransitionOverlay" class="fixed inset-0 bg-slate-900/25 backdrop-blur-[2px] z-50 flex items-center justify-center opacity-0 pointer-events-none transition-opacity duration-200">
        <div class="bg-white rounded-2xl px-6 py-4 shadow-2xl border border-slate-100 flex items-center gap-3.5 animate-in fade-in zoom-in-95 duration-150">
            <svg class="animate-spin h-5 w-5 text-indigo-600" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span class="text-sm font-semibold text-slate-700">Memuat halaman...</span>
        </div>
    </div>

    <!-- Interactive Processing / Loading Modal -->
    <div id="processingModal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs z-50 flex items-center justify-center hidden p-4">
        <div class="bg-white rounded-2xl p-6 sm:p-8 max-w-md w-full shadow-2xl border border-slate-100 text-center space-y-4 animate-in fade-in zoom-in-95 duration-200">
            <div class="w-16 h-16 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center mx-auto shadow-inner relative">
                <svg class="animate-spin h-8 w-8 text-indigo-600" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
            </div>
            <div>
                <h3 id="processingTitle" class="text-lg font-bold text-slate-800">Sedang Memproses Naskah...</h3>
                <p id="processingSubtitle" class="text-xs text-slate-500 mt-1.5 leading-relaxed">Sistem sedang mengekstraksi teks 5-gram shingle & vektor TF-IDF</p>
            </div>
            <!-- Progress Bar -->
            <div class="w-full bg-slate-100 rounded-full h-2 overflow-hidden">
                <div id="processingProgressBar" class="bg-indigo-600 h-2 rounded-full w-2/3 animate-pulse"></div>
            </div>
            <p class="text-[11px] text-slate-400">Mohon menunggu, proses analisis bukti kemiripan sedang berlangsung...</p>
        </div>
    </div>

    <!-- Responsive Navbar -->
    <nav class="bg-white/95 backdrop-blur-md border-b border-slate-200/80 sticky top-0 z-40 shadow-xs">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between min-h-[4rem] py-2.5 sm:py-3.5 gap-3">
                <!-- Brand & Logo -->
                <div class="flex items-center gap-2.5 sm:gap-3.5 min-w-0">
                    <a href="<?= base_url('dashboard') ?>" class="flex-shrink-0 group">
                        <img src="<?= base_url('images/logo.png') ?>" alt="AR Tilik Logo" class="w-10 h-10 sm:w-11 sm:h-11 object-contain drop-shadow-sm group-hover:scale-105 transition-transform duration-200">
                    </a>
                    <div class="min-w-0">
                        <div class="flex items-center gap-1.5 sm:gap-2 flex-wrap">
                            <a href="<?= base_url('dashboard') ?>" class="text-lg sm:text-xl font-bold bg-gradient-to-r from-indigo-700 to-indigo-500 bg-clip-text text-transparent truncate">
                                AR Tilik
                            </a>
                            <span class="text-[10px] sm:text-xs px-2 py-0.5 rounded-full bg-indigo-50 text-indigo-700 font-semibold border border-indigo-100 flex-shrink-0">
                                v1.2.0 Production
                            </span>
                        </div>
                        <p class="hidden sm:block text-[11px] text-slate-400 font-medium truncate">
                            Academic Writing Similarity & Evidence Checker
                        </p>
                    </div>
                </div>

                <!-- Worker Status Badge & Actions -->
                <div class="flex items-center gap-2 flex-shrink-0">
                    <a href="<?= base_url('/') ?>" class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-700 hover:text-indigo-600 px-3 py-1.5 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 transition shadow-2xs">
                        🏠 <span class="hidden sm:inline">Beranda &amp; Teori</span>
                    </a>
                    <a href="<?= base_url('evaluation/quality') ?>" class="hidden md:inline-flex items-center gap-1.5 text-xs font-semibold text-slate-600 hover:text-indigo-600 px-3 py-1.5 rounded-lg border border-slate-200 bg-slate-50 hover:bg-white transition">
                        📊 Observabilitas
                    </a>
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 sm:px-3 sm:py-1 rounded-full text-[11px] sm:text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200 shadow-xs">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        <span class="hidden sm:inline">Worker Ready</span>
                    </span>

                    <?php if (!empty($currentUser)): ?>
                    <div class="flex items-center gap-2 pl-2 border-l border-slate-200">
                        <?php if (($currentUser['role'] ?? '') === 'admin'): ?>
                        <a href="<?= base_url('admin') ?>" class="inline-flex items-center gap-1 text-xs font-bold text-emerald-700 hover:text-emerald-800 bg-emerald-50 hover:bg-emerald-100 px-2.5 py-1.5 rounded-lg border border-emerald-300 transition shadow-2xs" title="Buka Portal Administrator">
                            👑 <span class="hidden sm:inline">Portal Admin</span>
                        </a>
                        <?php endif; ?>
                        <div class="hidden sm:flex flex-col text-right">
                            <span class="text-xs font-bold text-slate-800 leading-tight"><?= esc($currentUser['name']) ?></span>
                            <span class="text-[10px] text-slate-500 leading-tight"><?= esc($currentUser['institution'] ?? 'Pengguna Terdaftar') ?></span>
                        </div>
                        <a href="<?= base_url('auth/logout') ?>" class="inline-flex items-center gap-1 text-xs font-semibold text-rose-600 hover:text-rose-700 bg-rose-50 hover:bg-rose-100 px-2.5 py-1.5 rounded-lg border border-rose-200 transition" title="Keluar Akun">
                            🚪 <span class="hidden sm:inline">Keluar</span>
                        </a>
                    </div>
                    <?php else: ?>
                    <div class="flex items-center gap-2 pl-2 border-l border-slate-200">
                        <a href="<?= base_url('/#auth-section') ?>" class="inline-flex items-center gap-1.5 text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-700 px-3 py-1.5 rounded-lg transition shadow-xs">
                            🔑 Masuk / Daftar
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Container -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8 flex-1 w-full">
        <!-- Hero Header -->
        <div class="mb-6 sm:mb-8 bg-gradient-to-br from-indigo-900 via-indigo-800 to-slate-900 rounded-2xl p-5 sm:p-8 text-white shadow-lg relative overflow-hidden">
            <div class="max-w-3xl relative z-10">
                <h1 class="text-xl sm:text-3xl font-extrabold tracking-tight mb-2">
                    Pemeriksaan Kemiripan Tulisan Ilmiah
                </h1>
                <p class="text-indigo-200 text-xs sm:text-sm leading-relaxed mb-5 sm:mb-6">
                    Menghadirkan deteksi bukti tekstual sekuensial 5-gram shingle, orientasi leksikal TF-IDF Cosine, 
                    dan kesadaran konteks akademik. Membantu akademisi meninjau naskah secara transparan tanpa tuduhan sepihak.
                </p>
                <div class="grid grid-cols-3 gap-2 sm:gap-4 border-t border-indigo-700/50 pt-4 sm:pt-5">
                    <div class="p-2 sm:p-0">
                        <div class="text-xl sm:text-3xl font-extrabold text-white"><?= number_format($totalSubmissions) ?></div>
                        <div class="text-[10px] sm:text-xs text-indigo-300 font-medium">Submisi Diperiksa</div>
                    </div>
                    <div class="p-2 sm:p-0 border-x border-indigo-700/30 sm:border-0 px-2 sm:px-0">
                        <div class="text-xl sm:text-3xl font-extrabold text-white"><?= number_format($totalCorpus) ?></div>
                        <div class="text-[10px] sm:text-xs text-indigo-300 font-medium">Dokumen Korpus</div>
                    </div>
                    <div class="p-2 sm:p-0">
                        <div class="text-xl sm:text-3xl font-extrabold text-white"><?= number_format($totalReports) ?></div>
                        <div class="text-[10px] sm:text-xs text-indigo-300 font-medium">Laporan Dihasilkan</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Email Verification / Flash Message Banner -->
        <?php if (session()->getFlashdata('message') || (isset($_GET['verified']) && $_GET['verified'] === 'success')): ?>
        <div class="mb-6 p-4 bg-emerald-50 border border-emerald-200 rounded-2xl flex items-center justify-between gap-3 text-emerald-800 text-xs sm:text-sm font-medium shadow-xs">
            <div class="flex items-center gap-2.5">
                <span class="text-xl">🎉</span>
                <span><?= esc(session()->getFlashdata('message')) ?: 'Alamat email Anda berhasil diverifikasi! Selamat datang di dashboard AR Tilik.' ?></span>
            </div>
            <button type="button" onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-800 text-lg font-bold leading-none p-1">&times;</button>
        </div>
        <?php endif; ?>

        <!-- Quota & Crowdsourced Corpus Banner -->
        <?php if (!empty($currentUser) && !empty($quotaInfo)): ?>
        <div class="mb-6 bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 rounded-2xl p-5 text-white shadow-md border border-indigo-800/40 relative overflow-hidden">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-5 relative z-10">
                <div class="space-y-1.5">
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="px-2 py-0.5 rounded text-[11px] font-bold bg-indigo-500/30 text-indigo-300 border border-indigo-500/40 uppercase tracking-wider">
                            Akun: <?= esc($currentUser['name']) ?>
                        </span>
                        <?php if (!empty($quotaInfo['is_subscribed'])): ?>
                        <span class="px-2 py-0.5 rounded text-[11px] font-bold bg-amber-500/25 text-amber-300 border border-amber-500/40 flex items-center gap-1 shadow-xs">
                            ⭐ <?= esc($quotaInfo['subscription_label']) ?> (Sisa <?= (int) ($quotaInfo['subscription_days_left'] ?? 0) ?> hari)
                        </span>
                        <?php endif; ?>
                        <span class="text-xs text-slate-400">• <?= esc($currentUser['email']) ?></span>
                    </div>
                    <h2 class="text-lg font-bold text-white flex items-center gap-2 flex-wrap">
                        <span>🎯 Status Kuota Submisi:</span>
                        <span class="text-emerald-400 font-mono-code text-xl font-extrabold"><?= $quotaInfo['total_available'] ?? 3 ?> Submisi Tersedia</span>
                    </h2>
                    <p class="text-xs text-slate-300 leading-relaxed max-w-2xl">
                        Kuota gratis: <strong class="text-white"><?= $quotaInfo['free_remaining'] ?? 3 ?>/<?= $quotaInfo['free_weekly_limit'] ?? $quotaInfo['free_limit'] ?? 3 ?> submisi</strong> minggu ini (reset otomatis dalam <?= $quotaInfo['days_until_reset'] ?? 7 ?> hari). 
                        Bonus & Top-Up: <strong class="text-amber-300">+<?= $quotaInfo['bonus_submissions'] ?? $quotaInfo['bonus_remaining'] ?? 0 ?> submisi</strong>. 
                        Tiap sumbangan <strong class="text-sky-300">3 naskah korpus valid</strong> memberikan reward <strong class="text-emerald-300">+1 submisi gratis</strong>!
                    </p>
                </div>
                
                <div class="flex flex-wrap sm:flex-nowrap items-center gap-2.5 flex-shrink-0">
                    <button type="button" onclick="openPaymentModal()" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl font-bold text-xs bg-gradient-to-r from-amber-500 to-orange-500 hover:from-amber-400 hover:to-orange-400 text-white shadow-md transition transform hover:-translate-y-0.5 cursor-pointer">
                        <span>⚡ Beli Kuota / Top-Up</span>
                    </button>
                    <button type="button" onclick="openContributeCorpusModal()" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-3.5 py-2.5 rounded-xl font-bold text-xs bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white shadow-sm transition transform hover:-translate-y-0.5 cursor-pointer">
                        <span>➕ Sumbang Korpus (+1)</span>
                    </button>
                </div>
            </div>

            <!-- Progress to next bonus -->
            <div class="mt-4 pt-3 border-t border-white/10 flex flex-col sm:flex-row sm:items-center justify-between text-xs text-slate-300 gap-2">
                <div class="flex items-center gap-2">
                    <span>Progres Reward Korpus:</span>
                    <div class="w-32 bg-slate-800 rounded-full h-2 overflow-hidden border border-slate-700">
                        <div class="bg-gradient-to-r from-emerald-500 to-teal-400 h-2 rounded-full" style="width: <?= min(100, ((($quotaInfo['uploaded_corpus_count'] ?? $quotaInfo['corpus_count'] ?? 0) % 3) / 3) * 100) ?>%"></div>
                    </div>
                    <span class="font-mono-code text-white font-bold"><?= (($quotaInfo['uploaded_corpus_count'] ?? $quotaInfo['corpus_count'] ?? 0) % 3) ?>/3 Dokumen</span>
                </div>
                <div class="text-[11px] text-slate-400">
                    <?php if (($quotaInfo['corpus_to_next_bonus'] ?? 3) === 3): ?>
                        Sumbang 3 korpus valid untuk mendapatkan bonus submisi berikutnya.
                    <?php else: ?>
                        Tinggal <strong class="text-amber-300"><?= $quotaInfo['corpus_to_next_bonus'] ?? 3 ?> korpus lagi</strong> untuk klaim reward +1 submisi!
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php else: ?>
        <div class="mb-6 bg-gradient-to-r from-indigo-900 to-indigo-950 rounded-2xl p-4 sm:p-5 text-white shadow-md border border-indigo-700/50 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="space-y-1">
                <div class="flex items-center gap-2">
                    <span class="px-2 py-0.5 rounded text-[11px] font-bold bg-amber-400/20 text-amber-300 border border-amber-400/30">
                        Akses Tamu / Pengunjung
                    </span>
                    <span class="text-xs text-indigo-200">Dapatkan Kuota Gratis Mingguan</span>
                </div>
                <p class="text-xs text-indigo-100 leading-relaxed">
                    Daftarkan akun gratis untuk memperoleh kuota <strong>3 submisi per minggu</strong> dan dapatkan kuota tambahan tanpa batas dengan menyumbangkan korpus rujukan karya ilmiah!
                </p>
            </div>
            <div class="flex items-center gap-2 flex-shrink-0">
                <a href="<?= base_url('/#auth-section') ?>" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl font-bold text-xs bg-indigo-500 hover:bg-indigo-400 text-white shadow-sm transition">
                    ✨ Daftar Gratis Sekarang
                </a>
                <a href="<?= base_url('/#pricing') ?>" class="inline-flex items-center gap-1 px-3 py-2 rounded-xl font-medium text-xs bg-indigo-800/60 hover:bg-indigo-800 text-indigo-200 border border-indigo-700 transition">
                    Paket Langganan
                </a>
            </div>
        </div>
        <?php endif; ?>

        <!-- Navigation Tabs (Horizontally Scrollable on Mobile) -->
        <div class="flex border-b border-slate-200 mb-6 gap-2 sm:gap-6 overflow-x-auto no-scrollbar scroll-smooth">
            <button onclick="switchTab('tab-submission')" id="btn-tab-submission" class="tab-btn pb-3 font-semibold text-xs sm:text-sm border-b-2 border-indigo-600 text-indigo-600 flex items-center gap-1.5 sm:gap-2 flex-shrink-0 whitespace-nowrap transition-colors">
                📄 Submisi & Pemeriksaan
            </button>
            <button onclick="switchTab('tab-corpus')" id="btn-tab-corpus" class="tab-btn pb-3 font-medium text-xs sm:text-sm text-slate-500 hover:text-slate-800 border-b-2 border-transparent flex items-center gap-1.5 sm:gap-2 flex-shrink-0 whitespace-nowrap transition-colors">
                📚 Manajemen Korpus Rujukan
            </button>
            <button onclick="switchTab('tab-benchmark')" id="btn-tab-benchmark" class="tab-btn pb-3 font-medium text-xs sm:text-sm text-slate-500 hover:text-slate-800 border-b-2 border-transparent flex items-center gap-1.5 sm:gap-2 flex-shrink-0 whitespace-nowrap transition-colors">
                🧪 Benchmark Lab
            </button>
        </div>

        <!-- TAB 1: Submisi & Pemeriksaan -->
        <div id="tab-submission" class="tab-content space-y-6">
            <!-- Upload Box -->
            <div class="bg-white rounded-xl border border-slate-200 p-5 sm:p-6 shadow-xs">
                <h2 class="text-base sm:text-lg font-bold text-slate-800 mb-1.5">Unggah Karya Ilmiah</h2>
                <p class="text-xs sm:text-sm text-slate-500 mb-4">Pilih file naskah skripsi, jurnal, tesis, atau makalah (PDF, DOCX, TXT) untuk diuji terhadap korpus rujukan institusi.</p>
                
                <form id="uploadSubmissionForm" class="space-y-4" enctype="multipart/form-data">
                    <div id="submissionDropZone" class="border-2 border-dashed border-slate-300 hover:border-indigo-400 transition-colors rounded-xl p-5 sm:p-6 text-center cursor-pointer bg-slate-50/50" onclick="document.getElementById('submissionFileInput').click()">
                        <input type="file" id="submissionFileInput" name="document" multiple accept=".pdf,.docx,.txt" class="hidden" onchange="handleSubmissionFilesSelect(this)">
                        <div class="w-12 h-12 mx-auto mb-2 sm:mb-3 rounded-full bg-indigo-50 text-indigo-600 flex items-center justify-center text-2xl">
                            📤
                        </div>
                        <p id="submissionFileLabel" class="text-xs sm:text-sm font-semibold text-slate-700">Klik atau geser (drag &amp; drop) berkas naskah di sini</p>
                        <p class="text-[11px] text-slate-400 mt-1">Mendukung Batch Upload sekaligus (PDF, DOCX, TXT) • Maksimal 25 MB per berkas</p>
                    </div>

                    <!-- Selected Files Preview Container (Batch List) -->
                    <div id="submissionFilesPreviewContainer" class="hidden bg-slate-50/80 border border-slate-200 rounded-xl p-3.5 space-y-2.5">
                        <div class="flex items-center justify-between text-xs font-bold text-slate-700 border-b border-slate-200/80 pb-2">
                            <div class="flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-indigo-600 animate-pulse"></span>
                                <span id="submissionFilesCountText">0 berkas terpilih</span>
                            </div>
                            <button type="button" onclick="clearSubmissionFiles()" class="text-rose-600 hover:text-rose-700 font-semibold text-[11px] hover:underline cursor-pointer flex items-center gap-1">
                                <span>✕</span> <span>Hapus Semua</span>
                            </button>
                        </div>
                        <div id="submissionFilesList" class="space-y-1.5 max-h-48 overflow-y-auto pr-1">
                            <!-- Populated dynamically via JS -->
                        </div>
                    </div>

                    <div class="space-y-2.5 pt-1">
                        <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider">Metode Mesin Pemeriksaan (Engine Mode):</label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-2.5 sm:gap-3 text-xs">
                            <label class="flex items-center gap-2.5 p-3 rounded-xl border border-indigo-200 bg-indigo-50/40 cursor-pointer hover:bg-indigo-50 transition shadow-2xs">
                                <input type="radio" name="engine" value="hybrid" checked class="text-indigo-600 focus:ring-indigo-500">
                                <div>
                                    <div class="font-bold text-indigo-900">Hybrid (Rekomendasi)</div>
                                    <div class="text-[11px] text-indigo-700">Pasase Exact + TF-IDF Topikal</div>
                                </div>
                            </label>
                            <label class="flex items-center gap-2.5 p-3 rounded-xl border border-slate-200 bg-white cursor-pointer hover:bg-slate-50 transition shadow-2xs">
                                <input type="radio" name="engine" value="tfidf" class="text-indigo-600 focus:ring-indigo-500">
                                <div>
                                    <div class="font-bold text-slate-800">TF-IDF Topikal</div>
                                    <div class="text-[11px] text-slate-500">Kosinus Leksikal & Bobot DF</div>
                                </div>
                            </label>
                            <label class="flex items-center gap-2.5 p-3 rounded-xl border border-slate-200 bg-white cursor-pointer hover:bg-slate-50 transition shadow-2xs">
                                <input type="radio" name="engine" value="idf" class="text-indigo-600 focus:ring-indigo-500">
                                <div>
                                    <div class="font-bold text-slate-800">Exact + IDF</div>
                                    <div class="text-[11px] text-slate-500">5-Gram dengan Atensi Frasa</div>
                                </div>
                            </label>
                            <label class="flex items-center gap-2.5 p-3 rounded-xl border border-slate-200 bg-white cursor-pointer hover:bg-slate-50 transition shadow-2xs">
                                <input type="radio" name="engine" value="exact" class="text-indigo-600 focus:ring-indigo-500">
                                <div>
                                    <div class="font-bold text-slate-800">Exact Match</div>
                                    <div class="text-[11px] text-slate-500">Verbatim Shingle 5-Gram</div>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Opsi Pemeriksaan Lanjutan & Korpus Otomatis -->
                    <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200 text-xs space-y-2">
                        <div class="font-bold text-slate-700 flex items-center gap-1.5">
                            <span>⚙️</span> <span>Opsi Analisis Cerdas &amp; Segmentasi Otomatis</span>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-slate-600">
                            <label class="flex items-center gap-2 cursor-pointer select-none">
                                <input type="checkbox" id="optAutoExcludeFormalities" checked class="w-4 h-4 rounded text-indigo-600 focus:ring-indigo-500 bg-white border-slate-300">
                                <span class="text-[11px] sm:text-xs">Eksklusi otomatis Halaman Formalitas &amp; Daftar Pustaka</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer select-none">
                                <input type="checkbox" id="optJITHarvest" checked class="w-4 h-4 rounded text-indigo-600 focus:ring-indigo-500 bg-white border-slate-300">
                                <span class="text-[11px] sm:text-xs">Pencarian Otomatis Korpus Terbuka Sebidang (JIT Harvester)</span>
                            </label>
                        </div>
                    </div>

                    <div class="flex justify-end pt-2">
                        <button type="submit" id="btnUploadSubmit" class="w-full sm:w-auto px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 text-white font-semibold rounded-xl text-xs sm:text-sm shadow-sm transition flex items-center justify-center gap-2 cursor-pointer">
                            <span id="btnUploadSubmitText">Unggah &amp; Periksa Similarity</span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                        </button>
                    </div>
                </form>

                <div id="uploadAlert" class="mt-4 p-4 rounded-xl text-xs sm:text-sm hidden"></div>
            </div>

            <!-- Submissions Table Card -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
                <div class="p-4 sm:p-5 border-b border-slate-200 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2 sm:gap-4">
                    <div>
                        <h2 class="text-base sm:text-lg font-bold text-slate-800">Daftar Karya Ilmiah yang Diajukan</h2>
                        <p class="text-xs text-slate-500">Memantau status pemeriksaan, engine yang digunakan, dan dekomposisi bukti akademis.</p>
                    </div>
                    <div class="flex items-center gap-2 self-end sm:self-auto">
                        <span class="text-[11px] text-slate-400 hidden sm:inline">Geser tabel jika terpotong →</span>
                        <a href="<?= base_url('dashboard') ?>" class="text-xs font-semibold text-indigo-600 hover:text-indigo-800 bg-indigo-50 px-3 py-1.5 rounded-lg border border-indigo-100 transition flex items-center gap-1">
                            ↻ Refresh
                        </a>
                    </div>
                </div>

                <!-- Table Container with Horizontal Scroll -->
                <div id="submissionsTableContainer" class="overflow-x-auto">
                    <table class="min-w-[760px] sm:min-w-full w-full text-left border-collapse text-sm">
                        <thead class="bg-slate-50 text-slate-500 uppercase text-[11px] font-bold border-b border-slate-200">
                            <tr>
                                <th class="p-3.5 sm:p-4 min-w-[200px]">Dokumen</th>
                                <th class="p-3.5 sm:p-4 text-center w-20">Format</th>
                                <th class="p-3.5 sm:p-4 min-w-[170px]">Engine & Keputusan</th>
                                <th class="p-3.5 sm:p-4 min-w-[130px]">Similarity</th>
                                <th class="p-3.5 sm:p-4 min-w-[130px] whitespace-nowrap">Tanggal Unggah</th>
                                <th class="p-3.5 sm:p-4 text-right min-w-[190px] whitespace-nowrap">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php if (empty($submissions)): ?>
                                <tr>
                                    <td colspan="6" class="p-8 text-center text-slate-400 text-xs sm:text-sm">Belum ada karya ilmiah yang diunggah.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($submissions as $sub): 
                                    $rep = $sub['latest_report'] ?? null;
                                    $isProcessing = $rep && in_array($rep['status'] ?? '', ['processing', 'pending'], true);
                                ?>
                                    <tr class="submission-row <?= $isProcessing ? 'submission-processing' : '' ?> hover:bg-slate-50/70 transition-colors">
                                        <td class="p-3.5 sm:p-4 font-medium text-slate-800">
                                            <div class="font-semibold text-slate-900 line-clamp-1" title="<?= esc($sub['title']) ?>"><?= esc($sub['title']) ?></div>
                                            <div class="text-[11px] text-slate-400 truncate max-w-[220px]" title="<?= esc($sub['original_filename']) ?>"><?= esc($sub['original_filename']) ?></div>
                                        </td>
                                        <td class="p-3.5 sm:p-4 text-center">
                                            <span class="px-2 py-0.5 rounded text-[10px] bg-slate-100 text-slate-600 font-mono font-semibold"><?= strtoupper(pathinfo($sub['original_filename'], PATHINFO_EXTENSION)) ?></span>
                                        </td>
                                        <td class="p-3.5 sm:p-4">
                                            <?php if ($rep): ?>
                                                <div class="flex flex-col gap-1 items-start">
                                                    <span class="text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded bg-slate-100 text-slate-600 border border-slate-200">
                                                        <?= strtoupper(esc($rep['engine'] ?? 'hybrid')) ?>
                                                    </span>
                                                    <?php 
                                                        $dec = $rep['predicted_decision'] ?? 'no_match';
                                                        $status = $rep['status'] ?? 'pending';
                                                    ?>
                                                    <?php if ($status === 'completed'): ?>
                                                        <?php if ($dec === 'match'): ?>
                                                            <span class="inline-flex items-center gap-1 text-[10px] font-bold px-2.5 py-0.5 rounded-full bg-rose-50 text-rose-700 border border-rose-200 whitespace-nowrap">
                                                                MATCH (Kemiripan Kuat)
                                                            </span>
                                                        <?php elseif ($dec === 'review'): ?>
                                                            <span class="inline-flex items-center gap-1 text-[10px] font-bold px-2.5 py-0.5 rounded-full bg-amber-50 text-amber-800 border border-amber-200 whitespace-nowrap">
                                                                REVIEW (Perlu Ditelaah)
                                                            </span>
                                                        <?php else: ?>
                                                            <span class="inline-flex items-center gap-1 text-[10px] font-bold px-2.5 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200 whitespace-nowrap">
                                                                NO MATCH (Aman)
                                                            </span>
                                                        <?php endif; ?>
                                                    <?php elseif ($status === 'processing'): ?>
                                                        <span class="inline-flex items-center gap-1 text-[10px] font-semibold px-2.5 py-0.5 rounded-full bg-blue-50 text-blue-700 border border-blue-200 animate-pulse whitespace-nowrap">
                                                            <span class="w-1.5 h-1.5 rounded-full bg-blue-600 animate-ping"></span>
                                                            Memproses...
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="inline-flex items-center text-[10px] font-medium px-2 py-0.5 rounded-full bg-slate-100 text-slate-500 whitespace-nowrap">
                                                            Antrean (Pending)
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                            <?php else: ?>
                                                <span class="text-xs text-slate-400">Belum diperiksa</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="p-3.5 sm:p-4">
                                            <?php if ($rep && $rep['status'] === 'completed'): ?>
                                                <div class="font-extrabold text-sm <?= $rep['overall_similarity'] >= 70 ? 'text-rose-600' : ($rep['overall_similarity'] >= 10 ? 'text-amber-600' : 'text-slate-700') ?>">
                                                    <?= number_format($rep['overall_similarity'], 2) ?>%
                                                </div>
                                                <div class="text-[10px] text-slate-400 whitespace-nowrap">
                                                    <?= (int) ($rep['longest_match_words'] ?? 0) ?> kata berurutan
                                                </div>
                                            <?php else: ?>
                                                <span class="text-xs text-slate-400">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="p-3.5 sm:p-4 text-slate-500 text-xs whitespace-nowrap">
                                            <?= date('d M Y, H:i', strtotime($sub['created_at'])) ?>
                                        </td>
                                        <td class="p-3.5 sm:p-4 text-right">
                                            <div class="inline-flex items-center justify-end gap-1.5 whitespace-nowrap">
                                                <?php if ($rep && $rep['status'] === 'completed'): ?>
                                                    <a href="<?= base_url('dashboard/report/' . $rep['id']) ?>" class="px-3 py-1 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-lg text-xs shadow-xs transition flex items-center gap-1">
                                                        Buka Laporan
                                                    </a>
                                                    <button onclick="triggerCheck(<?= $sub['id'] ?>)" class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 font-medium rounded-lg text-xs transition" title="Ulangi pemeriksaan">
                                                        ↻ Ulangi
                                                    </button>
                                                <?php else: ?>
                                                    <button onclick="triggerCheck(<?= $sub['id'] ?>)" class="px-3 py-1 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 font-semibold rounded-lg text-xs transition">
                                                        Cek Similarity
                                                    </button>
                                                <?php endif; ?>
                                                <a href="<?= base_url('api/documents/' . $sub['id']) ?>" target="_blank" class="px-2 py-1 bg-slate-100 hover:bg-slate-200 text-slate-600 font-mono rounded-lg text-xs transition" title="Lihat JSON">
                                                    JSON
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- TAB 2: Manajemen Korpus -->
        <div id="tab-corpus" class="tab-content space-y-6 hidden">
            <!-- Automated Harvesting & Dropzone Engine Banner -->
            <div class="bg-gradient-to-r from-emerald-900 via-teal-900 to-slate-900 rounded-xl p-5 text-white shadow-sm flex flex-col md:flex-row items-start md:items-center justify-between gap-4 border border-emerald-800/60">
                <div class="space-y-1">
                    <div class="flex items-center gap-2">
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">Otomasi Terjadwal</span>
                        <h3 class="text-sm sm:text-base font-bold text-white">Automated Dropzone & OAI-PMH Harvester</h3>
                    </div>
                    <p class="text-xs text-slate-300 max-w-2xl leading-relaxed">
                        Anda tidak perlu mengunggah satu per satu. Letakkan puluhan berkas PDF atau file ZIP langsung ke direktori <code class="bg-slate-800 text-emerald-400 px-1.5 py-0.5 rounded text-[11px]">storage/corpus_dropzone/</code>, atau hubungkan repositori kampus (EPrints/DSpace/OJS) untuk dipanen otomatis terjadwal.
                    </p>
                </div>
                <div class="flex items-center gap-2 flex-shrink-0 w-full md:w-auto">
                    <button id="btnSyncDropzone" onclick="syncDropzone()" class="w-full md:w-auto px-4 py-2 bg-emerald-600 hover:bg-emerald-500 active:bg-emerald-700 text-white font-semibold rounded-lg text-xs shadow transition flex items-center justify-center gap-1.5 cursor-pointer">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        <span>Pindai Dropzone Sekarang</span>
                    </button>
                </div>
            </div>

            <!-- Autonomous Scholarly Discovery Box -->
            <div class="bg-white rounded-xl border border-indigo-200 p-5 sm:p-6 shadow-xs relative overflow-hidden">
                <div class="flex items-center gap-2 mb-1.5">
                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-indigo-100 text-indigo-700">Fitur Baru</span>
                    <h2 class="text-base sm:text-lg font-bold text-slate-800">Cari Sumber Korpus Otomatis (Autonomous Scholarly Discovery)</h2>
                </div>
                <p class="text-xs sm:text-sm text-slate-500 mb-4">
                    AR Tilik dapat secara mandiri mencari, mengunduh, dan mengindeks karya ilmiah terbuka (Open Access) dari jaringan basis data akademik global (OpenAlex) serta katalog repositori perguruan tinggi di Indonesia.
                </p>

                <form id="autoDiscoverForm" onsubmit="handleAutoDiscover(event)" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                        <div class="md:col-span-2">
                            <label class="block text-[11px] font-bold text-slate-600 uppercase tracking-wider mb-1">Topik / Kata Kunci Karya Ilmiah:</label>
                            <input type="text" id="discoverQuery" name="query" placeholder="Contoh: Pendidikan Agama Kristen, Teologi Pastoral, Kurikulum Merdeka..." class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 bg-white">
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-slate-600 uppercase tracking-wider mb-1">Jumlah Dokumen:</label>
                            <select id="discoverLimit" name="limit" class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 bg-white">
                                <option value="5" selected>5 Dokumen Teratas</option>
                                <option value="10">10 Dokumen Teratas</option>
                                <option value="15">15 Dokumen Teratas</option>
                            </select>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center justify-between gap-3 pt-1">
                        <div class="flex items-center gap-4 text-xs text-slate-600 flex-wrap">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" name="discoverMode" value="query" checked onchange="toggleDiscoverMode(this.value)" class="text-indigo-600 focus:ring-indigo-500">
                                <span class="font-medium">Pencarian Topik Terbuka (OpenAlex / Jurnal Kampus)</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" name="discoverMode" value="registry" onchange="toggleDiscoverMode(this.value)" class="text-indigo-600 focus:ring-indigo-500">
                                <span class="font-medium">Katalog Repositori Kampus Indonesia (OAI-PMH)</span>
                            </label>
                        </div>
                        <button type="submit" id="btnAutoDiscover" class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 text-white font-semibold rounded-xl text-xs sm:text-sm shadow-sm transition flex items-center gap-2 cursor-pointer">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                            <span>Cari & Indeks Otomatis</span>
                        </button>
                    </div>
                </form>
                <div id="discoverResultBox" class="mt-4 p-4 rounded-xl text-xs sm:text-sm hidden bg-slate-50 border border-slate-200"></div>
            </div>

            <!-- Upload Corpus Box -->
            <div class="bg-white rounded-xl border border-slate-200 p-5 sm:p-6 shadow-xs">
                <h2 class="text-base sm:text-lg font-bold text-slate-800 mb-1.5">Tambah Dokumen Korpus Pembanding</h2>
                <p class="text-xs sm:text-sm text-slate-500 mb-4">Dokumen yang diunggah ke sini akan menjadi basis rujukan similarity untuk memeriksa karya ilmiah mahasiswa.</p>
                
                <form id="uploadCorpusForm" class="space-y-4" enctype="multipart/form-data">
                    <div class="border-2 border-dashed border-slate-300 hover:border-emerald-400 transition-colors rounded-xl p-5 sm:p-6 text-center cursor-pointer bg-slate-50/50" onclick="document.getElementById('corpusFileInput').click()">
                        <input type="file" id="corpusFileInput" name="document" accept=".pdf,.docx,.txt" class="hidden" onchange="handleFileSelect(this, 'corpusFileLabel')">
                        <div class="w-12 h-12 mx-auto mb-2 sm:mb-3 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center text-2xl">
                            📚
                        </div>
                        <p id="corpusFileLabel" class="text-xs sm:text-sm font-semibold text-slate-700">Pilih file naskah rujukan (Skripsi/Tesis/Jurnal)</p>
                        <p class="text-[11px] text-slate-400 mt-1">PDF, DOCX, TXT hingga 25 MB</p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1">Judul Dokumen (Opsional):</label>
                            <input type="text" name="title" placeholder="Contoh: Jurnal Teologi Pastoral 2026" class="w-full text-xs sm:text-sm px-3.5 py-2 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1">Kategori Sumber:</label>
                            <select name="source_type" class="w-full text-xs sm:text-sm px-3.5 py-2 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-emerald-500 bg-white">
                                <option value="thesis">Tesis / Disertasi</option>
                                <option value="journal">Jurnal Ilmiah</option>
                                <option value="book">Buku / Monograf</option>
                                <option value="student">Karya Mahasiswa</option>
                                <option value="repository">Repositori Terbuka</option>
                                <option value="other" selected>Lainnya</option>
                            </select>
                        </div>
                    </div>

                    <div class="flex justify-end pt-2">
                        <button type="submit" id="btnUploadCorpus" class="w-full sm:w-auto px-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 text-white font-semibold rounded-xl text-xs sm:text-sm shadow-sm transition flex items-center justify-center gap-2">
                            <span>Unggah & Indeks Korpus</span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                        </button>
                    </div>
                </form>

                <div id="corpusAlert" class="mt-4 p-4 rounded-xl text-xs sm:text-sm hidden"></div>
            </div>

            <!-- Corpus Table Card -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
                <div class="p-4 sm:p-5 border-b border-slate-200 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2 sm:gap-4">
                    <div>
                        <h2 class="text-base sm:text-lg font-bold text-slate-800">Daftar Dokumen Korpus Terdaftar</h2>
                        <p class="text-xs text-slate-500">Total: <?= count($corpusDocs) ?> dokumen rujukan aktif.</p>
                    </div>
                    <span class="text-[11px] text-slate-400 hidden sm:inline">Geser tabel jika terpotong →</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-[650px] sm:min-w-full w-full text-left border-collapse text-sm">
                        <thead class="bg-slate-50 text-slate-500 uppercase text-[11px] font-bold border-b border-slate-200">
                            <tr>
                                <th class="p-3.5 sm:p-4 min-w-[200px]">Dokumen</th>
                                <th class="p-3.5 sm:p-4 min-w-[120px]">Kategori Sumber</th>
                                <th class="p-3.5 sm:p-4 min-w-[130px]">Status Indeks</th>
                                <th class="p-3.5 sm:p-4 min-w-[120px] whitespace-nowrap">Tanggal Tambah</th>
                                <th class="p-3.5 sm:p-4 text-right min-w-[150px] whitespace-nowrap">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php if (empty($corpusDocs)): ?>
                                <tr>
                                    <td colspan="5" class="p-8 text-center text-slate-400 text-xs sm:text-sm">Belum ada korpus terdaftar. Unggah dokumen rujukan untuk memulai.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($corpusDocs as $doc): ?>
                                    <tr class="hover:bg-slate-50/70 transition-colors">
                                        <td class="p-3.5 sm:p-4 font-medium text-slate-800">
                                            <div class="font-semibold text-slate-900 line-clamp-1" title="<?= esc($doc['title']) ?>"><?= esc($doc['title']) ?></div>
                                            <div class="text-[11px] text-slate-400 truncate max-w-[220px]" title="<?= esc($doc['original_filename']) ?>"><?= esc($doc['original_filename']) ?></div>
                                        </td>
                                        <td class="p-3.5 sm:p-4">
                                            <span class="px-2 py-0.5 rounded text-[11px] bg-indigo-50 text-indigo-700 capitalize font-medium"><?= esc($doc['source_type'] ?? 'other') ?></span>
                                        </td>
                                        <td class="p-3.5 sm:p-4">
                                            <?php if ($doc['indexing_status'] === 'completed'): ?>
                                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200 whitespace-nowrap">Terindeks ✓</span>
                                            <?php elseif ($doc['indexing_status'] === 'pending'): ?>
                                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-amber-50 text-amber-700 border border-amber-200 whitespace-nowrap">Antrean Indeks</span>
                                            <?php else: ?>
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium bg-slate-100 text-slate-600 whitespace-nowrap"><?= esc($doc['indexing_status']) ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="p-3.5 sm:p-4 text-slate-500 text-xs whitespace-nowrap"><?= date('d M Y', strtotime($doc['created_at'])) ?></td>
                                        <td class="p-3.5 sm:p-4 text-right">
                                            <div class="inline-flex items-center justify-end gap-1.5 whitespace-nowrap">
                                                <button onclick="reindexCorpus(<?= $doc['id'] ?>)" class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold rounded-lg text-xs transition">
                                                    Reindex
                                                </button>
                                                <button onclick="deleteCorpus(<?= $doc['id'] ?>)" class="px-2.5 py-1 bg-rose-50 hover:bg-rose-100 text-rose-700 font-semibold rounded-lg text-xs transition">
                                                    Hapus
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- TAB 3: Benchmark Lab -->
        <div id="tab-benchmark" class="tab-content space-y-6 hidden">
            <div class="bg-white rounded-xl border border-slate-200 p-5 sm:p-6 shadow-xs">
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2 sm:gap-4 mb-4">
                    <div>
                        <div class="flex items-center gap-2">
                            <h2 class="text-base sm:text-lg font-bold text-slate-800">Laboratorium Evaluasi &amp; Benchmark</h2>
                            <span class="text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded bg-indigo-50 text-indigo-700 border border-indigo-200">
                                Internal Regression Test Suite
                            </span>
                        </div>
                        <p class="text-xs sm:text-sm text-slate-500 mt-1">Pengujian regresi terkontrol pada dataset baku laboratorium (50 kasus uji: Identical, Copy-Edit, Paraphrase, Common-Phrase, Unrelated) untuk memvalidasi presisi dan daya tangkap algoritma secara terkalibrasi.</p>
                    </div>
                    <span class="text-[11px] text-slate-400 hidden sm:inline">Geser tabel jika terpotong →</span>
                </div>

                <!-- Metodologi & Transparansi Ilmiah Notice -->
                <div class="p-3.5 sm:p-4 rounded-xl bg-slate-50 border border-slate-200 text-xs text-slate-600 space-y-1.5 my-3">
                    <div class="font-bold text-slate-800 flex items-center gap-1.5">
                        <span>ℹ️</span> <span>Catatan Metodologi &amp; Integritas Ilmiah</span>
                    </div>
                    <p class="text-slate-500 leading-relaxed text-[11px] sm:text-xs">
                        Skor F1 100% pada tabel di bawah ini merefleksikan hasil pengujian internal pada 50 kasus uji regresi sintetis terkontrol. Pada skripsi dan artikel ilmiah dunia nyata, AR Tilik menyajikan dekomposisi bukti multi-dimensi (kesamaan sekuensial verbatim, leksikal TF-IDF, analisis alih bahasa, serta klasifikasi risiko XGBoost) agar dosen dan komite akademik dapat menelaah konteks orisinalitas secara berkeadilan.
                    </p>
                </div>

                <div class="overflow-x-auto mt-4 rounded-xl border border-slate-200">
                    <table class="min-w-[700px] sm:min-w-full w-full text-left border-collapse text-sm">
                        <thead class="bg-slate-50 text-slate-500 uppercase text-[11px] font-bold border-b border-slate-200">
                            <tr>
                                <th class="p-3.5 sm:p-4">Run ID</th>
                                <th class="p-3.5 sm:p-4">Versi Engine</th>
                                <th class="p-3.5 sm:p-4">Total Kasus</th>
                                <th class="p-3.5 sm:p-4">Binary Precision</th>
                                <th class="p-3.5 sm:p-4">Binary Recall</th>
                                <th class="p-3.5 sm:p-4">Binary F1</th>
                                <th class="p-3.5 sm:p-4">Macro F1</th>
                                <th class="p-3.5 sm:p-4 whitespace-nowrap">Waktu Eksekusi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php if (empty($benchmarkRuns)): ?>
                                <tr>
                                    <td colspan="8" class="p-8 text-center text-slate-400 text-xs sm:text-sm">Belum ada riwayat benchmark yang dijalankan.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($benchmarkRuns as $run): ?>
                                    <tr class="hover:bg-slate-50/70 transition-colors">
                                        <td class="p-3.5 sm:p-4 font-mono font-semibold text-indigo-600">#<?= $run['id'] ?></td>
                                        <td class="p-3.5 sm:p-4">
                                            <span class="px-2.5 py-0.5 rounded text-[11px] font-semibold uppercase <?= $run['engine_version'] === 'exact' ? 'bg-blue-50 text-blue-700 border border-blue-200' : 'bg-purple-50 text-purple-700 border border-purple-200' ?>">
                                                <?= esc($run['engine_version']) ?>
                                            </span>
                                        </td>
                                        <td class="p-3.5 sm:p-4"><?= $run['total_cases'] ?></td>
                                        <td class="p-3.5 sm:p-4 font-mono"><?= number_format($run['precision_score'] * 100, 2) ?>%</td>
                                        <td class="p-3.5 sm:p-4 font-mono"><?= number_format($run['recall_score'] * 100, 2) ?>%</td>
                                        <td class="p-3.5 sm:p-4 font-mono font-bold text-emerald-600"><?= number_format($run['f1_score'] * 100, 2) ?>%</td>
                                        <td class="p-3.5 sm:p-4 font-mono font-bold text-indigo-600"><?= number_format($run['macro_f1'] * 100, 2) ?>%</td>
                                        <td class="p-3.5 sm:p-4 text-slate-400 text-xs whitespace-nowrap"><?= date('d M Y, H:i', strtotime($run['created_at'])) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-white border-t border-slate-200 mt-auto py-5 text-center text-xs text-slate-400">
        <p>AR Tilik • Academic Writing Similarity & Evidence Checker • Dikembangkan dengan Prinsip Keadilan Akademik</p>
    </footer>

    <!-- JavaScript Handlers -->
    <script>
        // Page Transition Handling
        function startPageTransition() {
            const bar = document.getElementById('topProgressBar');
            const overlay = document.getElementById('pageTransitionOverlay');
            if (bar) {
                bar.style.width = '70%';
            }
            if (overlay) {
                overlay.classList.remove('pointer-events-none', 'opacity-0');
                overlay.classList.add('opacity-100');
            }
        }

        function endPageTransition() {
            const bar = document.getElementById('topProgressBar');
            const overlay = document.getElementById('pageTransitionOverlay');
            if (bar) {
                bar.style.width = '100%';
                setTimeout(() => { bar.style.width = '0'; }, 300);
            }
            if (overlay) {
                overlay.classList.remove('opacity-100');
                overlay.classList.add('opacity-0', 'pointer-events-none');
            }
        }

        // Intercept standard navigation link clicks
        document.addEventListener('click', function(e) {
            const link = e.target.closest('a');
            if (link && link.href && !link.target && !link.href.startsWith('javascript:') && !link.href.includes('#')) {
                const currentOrigin = window.location.origin;
                if (link.href.startsWith(currentOrigin)) {
                    startPageTransition();
                }
            }
        });

        // Processing Modal Functions
        function showProcessingModal(title, subtitle, progressPercent = null) {
            const modal = document.getElementById('processingModal');
            const tEl = document.getElementById('processingTitle');
            const sEl = document.getElementById('processingSubtitle');
            const bar = document.getElementById('processingProgressBar');
            if (title && tEl) tEl.innerText = title;
            if (subtitle && sEl) sEl.innerText = subtitle;
            if (bar) {
                if (progressPercent !== null) {
                    bar.classList.remove('animate-pulse');
                    bar.style.width = Math.min(100, Math.max(0, progressPercent)) + '%';
                } else {
                    bar.classList.add('animate-pulse');
                    bar.style.width = '66%';
                }
            }
            if (modal) modal.classList.remove('hidden');
        }

        function hideProcessingModal() {
            const modal = document.getElementById('processingModal');
            if (modal) modal.classList.add('hidden');
        }

        function switchTab(tabId) {
            document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.classList.remove('border-indigo-600', 'text-indigo-600', 'font-semibold');
                btn.classList.add('border-transparent', 'text-slate-500', 'font-medium');
            });
            const target = document.getElementById(tabId);
            if (target) target.classList.remove('hidden');
            const activeBtn = document.getElementById('btn-' + tabId);
            if (activeBtn) {
                activeBtn.classList.add('border-indigo-600', 'text-indigo-600', 'font-semibold');
                activeBtn.classList.remove('border-transparent', 'text-slate-500');
            }
        }

        function handleFileSelect(input, labelId) {
            if (input.files && input.files[0]) {
                document.getElementById(labelId).innerText = "File terpilih: " + input.files[0].name;
            }
        }

        // ==========================================
        // BATCH UPLOAD & MULTI-FILE LOGIC
        // ==========================================
        let selectedSubmissionFiles = [];

        function formatFileSize(bytes) {
            if (!bytes || bytes === 0) return '0 B';
            const k = 1024;
            const sizes = ['B', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
        }

        function getFileIcon(filename) {
            const ext = filename.split('.').pop().toLowerCase();
            if (ext === 'pdf') return '📕';
            if (ext === 'docx') return '📘';
            return '📄';
        }

        function handleSubmissionFilesSelect(input) {
            if (input.files && input.files.length > 0) {
                addSubmissionFiles(input.files);
            }
        }

        function addSubmissionFiles(newFiles) {
            const allowedExts = ['pdf', 'docx', 'txt'];
            const maxSizeBytes = 25 * 1024 * 1024; // 25 MB

            Array.from(newFiles).forEach(file => {
                const ext = file.name.split('.').pop().toLowerCase();
                if (!allowedExts.includes(ext)) {
                    alert(`Format berkas "${file.name}" tidak didukung. Hanya PDF, DOCX, dan TXT.`);
                    return;
                }
                if (file.size > maxSizeBytes) {
                    alert(`Berkas "${file.name}" melebihi batas maksimal 25 MB.`);
                    return;
                }
                // Cegah duplikasi file berdasarkan nama & ukuran
                const exists = selectedSubmissionFiles.some(f => f.name === file.name && f.size === file.size);
                if (!exists) {
                    selectedSubmissionFiles.push(file);
                }
            });

            updateSubmissionFilesUI();
        }

        function removeSubmissionFile(index) {
            if (index >= 0 && index < selectedSubmissionFiles.length) {
                selectedSubmissionFiles.splice(index, 1);
                updateSubmissionFilesUI();
            }
        }

        function clearSubmissionFiles() {
            selectedSubmissionFiles = [];
            const fileInput = document.getElementById('submissionFileInput');
            if (fileInput) fileInput.value = '';
            updateSubmissionFilesUI();
        }

        function updateSubmissionFilesUI() {
            const container = document.getElementById('submissionFilesPreviewContainer');
            const countText = document.getElementById('submissionFilesCountText');
            const listEl = document.getElementById('submissionFilesList');
            const labelEl = document.getElementById('submissionFileLabel');
            const btnText = document.getElementById('btnUploadSubmitText');

            if (!container || !listEl) return;

            if (selectedSubmissionFiles.length === 0) {
                container.classList.add('hidden');
                if (labelEl) labelEl.innerText = "Klik atau geser (drag & drop) berkas naskah di sini";
                if (btnText) btnText.innerText = "Unggah & Periksa Similarity";
                listEl.innerHTML = '';
                return;
            }

            container.classList.remove('hidden');
            const totalBytes = selectedSubmissionFiles.reduce((acc, f) => acc + f.size, 0);
            if (countText) {
                countText.innerText = `${selectedSubmissionFiles.length} berkas naskah terpilih (Total: ${formatFileSize(totalBytes)})`;
            }
            if (labelEl) {
                labelEl.innerText = `+ Tambah berkas lainnya (${selectedSubmissionFiles.length} berkas telah dipilih)`;
            }
            if (btnText) {
                btnText.innerText = selectedSubmissionFiles.length > 1
                    ? `Unggah & Periksa Similarity (${selectedSubmissionFiles.length} Berkas)`
                    : `Unggah & Periksa Similarity`;
            }

            listEl.innerHTML = selectedSubmissionFiles.map((file, idx) => `
                <div class="flex items-center justify-between p-2 sm:p-2.5 bg-white rounded-lg border border-slate-200 text-xs shadow-2xs hover:border-indigo-300 transition">
                    <div class="flex items-center gap-2.5 min-w-0 pr-2">
                        <span class="text-base flex-shrink-0">${getFileIcon(file.name)}</span>
                        <div class="min-w-0">
                            <div class="font-bold text-slate-800 truncate text-[12px]">${escapeHtml(file.name)}</div>
                            <div class="text-[10px] text-slate-400">${formatFileSize(file.size)}</div>
                        </div>
                    </div>
                    <button type="button" onclick="removeSubmissionFile(${idx})" class="p-1 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded transition cursor-pointer flex-shrink-0" title="Keluarkan berkas ini">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
            `).join('');
        }

        function escapeHtml(str) {
            return str.replace(/[&<>'"]/g, 
                tag => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#39;', '"': '&quot;' }[tag] || tag)
            );
        }

        // Drag & Drop handler for submission drop zone
        const subDropZone = document.getElementById('submissionDropZone');
        if (subDropZone) {
            ['dragenter', 'dragover'].forEach(eventName => {
                subDropZone.addEventListener(eventName, (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    subDropZone.classList.add('border-indigo-500', 'bg-indigo-50/60');
                });
            });
            ['dragleave', 'drop'].forEach(eventName => {
                subDropZone.addEventListener(eventName, (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    subDropZone.classList.remove('border-indigo-500', 'bg-indigo-50/60');
                });
            });
            subDropZone.addEventListener('drop', (e) => {
                if (e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files.length > 0) {
                    addSubmissionFiles(e.dataTransfer.files);
                }
            });
        }

        // Handle upload submission (Batch & Single)
        document.getElementById('uploadSubmissionForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const btn = document.getElementById('btnUploadSubmit');
            const alertBox = document.getElementById('uploadAlert');

            // Jika belum ada file di array, periksa input file langsung
            const fileInput = document.getElementById('submissionFileInput');
            if (selectedSubmissionFiles.length === 0 && fileInput && fileInput.files && fileInput.files.length > 0) {
                addSubmissionFiles(fileInput.files);
            }

            if (selectedSubmissionFiles.length === 0) {
                alert('Silakan pilih setidaknya satu file naskah dokumen terlebih dahulu.');
                return;
            }

            btn.disabled = true;
            alertBox.classList.add('hidden');

            const total = selectedSubmissionFiles.length;
            const engineMode = document.querySelector('input[name="engine"]:checked')?.value || 'hybrid';
            let successCount = 0;
            let failedFiles = [];

            for (let i = 0; i < total; i++) {
                const file = selectedSubmissionFiles[i];
                const currentPct = Math.round((i / total) * 100);
                
                showProcessingModal(
                    `Mengunggah Naskah (${i + 1} dari ${total})`,
                    `Mengunggah "${file.name}" ke antrean analisis...`,
                    currentPct
                );

                const formData = new FormData();
                formData.append('document', file);
                
                const optFormalities = document.getElementById('optAutoExcludeFormalities');
                if (optFormalities && optFormalities.checked) {
                    formData.append('auto_exclude_formalities', '1');
                }
                const optJIT = document.getElementById('optJITHarvest');
                if (optJIT && optJIT.checked) {
                    formData.append('jit_harvest', '1');
                }

                try {
                    const res = await fetch('<?= base_url('api/documents') ?>', {
                        method: 'POST',
                        body: formData
                    });
                    const data = await res.json();

                    if (res.ok && data.document_id) {
                        showProcessingModal(
                            `Menjadwalkan Analisis (${i + 1} dari ${total})`,
                            `Mendaftarkan "${file.name}" ke antrean worker (${engineMode.toUpperCase()})...`,
                            Math.round(((i + 0.5) / total) * 100)
                        );

                        const checkFormData = new FormData();
                        checkFormData.append('engine', engineMode);

                        const checkRes = await fetch(`<?= base_url('api/documents') ?>/${data.document_id}/check`, {
                            method: 'POST',
                            body: checkFormData
                        });

                        if (checkRes.ok) {
                            successCount++;
                        } else {
                            failedFiles.push({ name: file.name, error: 'Gagal menjadwalkan pemeriksaan worker' });
                        }
                    } else if (res.status === 403 && data.status === 'quota_exceeded') {
                        // Kuota habis!
                        hideProcessingModal();
                        let quotaMsg = data.message;
                        if (successCount > 0) {
                            quotaMsg = `${successCount} berkas pertama berhasil dijadwalkan. Berkas "${file.name}" dan berkas berikutnya dihentikan karena: ${data.message}`;
                        }
                        alertBox.className = 'mt-4 p-4 rounded-xl text-xs sm:text-sm bg-amber-50 text-amber-900 border border-amber-300 block space-y-2.5';
                        alertBox.innerHTML = `
                            <div class="font-bold flex items-center gap-1.5 text-amber-950">
                                <span>⚠️</span> <span>Batas Kuota Telah Habis</span>
                            </div>
                            <p class="text-xs leading-relaxed text-amber-900">${quotaMsg}</p>
                            <div class="pt-1 flex flex-wrap items-center gap-2">
                                <button type="button" onclick="openContributeCorpusModal()" class="px-3.5 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow-xs transition">
                                    ➕ Sumbang Korpus (+1 Submisi)
                                </button>
                                <a href="<?= base_url('/#pricing') ?>" class="px-3.5 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-900 text-white font-medium text-xs shadow-xs transition">
                                    💎 Rencana Paket Berlangganan
                                </a>
                            </div>
                        `;
                        if (successCount > 0) {
                            fetchSubmissionsTableSilently();
                        }
                        btn.disabled = false;
                        return;
                    } else {
                        failedFiles.push({ name: file.name, error: data.message || 'Gagal mengunggah berkas' });
                    }
                } catch (err) {
                    failedFiles.push({ name: file.name, error: err.message || 'Terjadi kesalahan koneksi' });
                }
            }

            if (successCount > 0) {
                showProcessingModal(
                    'Pemeriksaan Berhasil Dijadwalkan!',
                    `Sebanyak ${successCount} dari ${total} berkas telah masuk antrean worker. Memuat ulang dashboard...`,
                    100
                );
                setTimeout(() => location.reload(), 1400);
            } else {
                hideProcessingModal();
                btn.disabled = false;
                alertBox.className = 'mt-4 p-4 rounded-xl text-xs sm:text-sm bg-rose-50 text-rose-800 border border-rose-200 block';
                const errList = failedFiles.map(f => `• ${f.name}: ${f.error}`).join('<br>');
                alertBox.innerHTML = `<strong>Gagal mengunggah berkas:</strong><br>${errList}`;
            }
        });

        // Handle upload corpus
        document.getElementById('uploadCorpusForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const btn = document.getElementById('btnUploadCorpus');
            const alertBox = document.getElementById('corpusAlert');
            const formData = new FormData(this);

            if (!document.getElementById('corpusFileInput').files[0]) {
                alert('Silakan pilih file korpus terlebih dahulu.');
                return;
            }

            btn.disabled = true;
            alertBox.classList.add('hidden');
            showProcessingModal('Mengunggah & Mengindeks Korpus...', 'Mengekstrak teks halaman dan menyusun indeks sidik jari dokumen.');

            try {
                const res = await fetch('<?= base_url('admin/corpus') ?>', {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();

                if (res.ok) {
                    showProcessingModal('Korpus Berhasil Didaftarkan!', 'Memuat ulang daftar korpus institusi...');
                    setTimeout(() => location.reload(), 1200);
                } else {
                    throw new Error(data.message || 'Gagal menambah korpus.');
                }
            } catch (err) {
                hideProcessingModal();
                alertBox.className = 'mt-4 p-4 rounded-xl text-xs sm:text-sm bg-rose-50 text-rose-800 border border-rose-200 block';
                alertBox.innerText = 'Error: ' + err.message;
            } finally {
                btn.disabled = false;
            }
        });

        async function triggerCheck(docId) {
            const engineRadio = document.querySelector('input[name="engine"]:checked');
            const engineMode = engineRadio ? engineRadio.value : 'hybrid';
            if (!confirm(`Jalankan pemeriksaan similarity (${engineMode.toUpperCase()}) untuk dokumen #${docId}?`)) return;
            
            showProcessingModal('Menjadwalkan Pemeriksaan...', `Mengirimkan tugas analisis (${engineMode.toUpperCase()}) ke background worker.`);
            try {
                const formData = new FormData();
                formData.append('engine', engineMode);
                const res = await fetch(`<?= base_url('api/documents') ?>/${docId}/check`, {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();
                if (res.ok) {
                    showProcessingModal('Tugas Diterima!', `Laporan #${data.report_id} sedang diproses. Memuat ulang halaman...`);
                    setTimeout(() => location.reload(), 1200);
                } else {
                    hideProcessingModal();
                    alert('Gagal: ' + data.message);
                }
            } catch (err) {
                hideProcessingModal();
                alert('Error: ' + err.message);
            }
        }

        async function reindexCorpus(id) {
            if (!confirm(`Jalankan reindex untuk korpus #${id}?`)) return;
            showProcessingModal('Mengindeks Ulang Korpus...', 'Menghitung ulang token, shingle, dan bobot DF dokumen.');
            try {
                const res = await fetch(`<?= base_url('admin/corpus') ?>/${id}/reindex`, { method: 'POST' });
                const data = await res.json();
                showProcessingModal('Reindex Selesai!', 'Memuat ulang daftar korpus...');
                setTimeout(() => location.reload(), 1000);
            } catch (err) {
                hideProcessingModal();
                alert('Error: ' + err.message);
            }
        }

        async function deleteCorpus(id) {
            if (!confirm(`Hapus dokumen korpus #${id}?`)) return;
            showProcessingModal('Menghapus Dokumen...', 'Menghapus data dokumen dan jejak indeks terkait.');
            try {
                const res = await fetch(`<?= base_url('admin/corpus') ?>/${id}`, { method: 'DELETE' });
                const data = await res.json();
                showProcessingModal('Dokumen Berhasil Dihapus', 'Memuat ulang...');
                setTimeout(() => location.reload(), 1000);
            } catch (err) {
                hideProcessingModal();
                alert('Error: ' + err.message);
            }
        }

        async function syncDropzone() {
            const btn = document.getElementById('btnSyncDropzone');
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = `
                    <svg class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span>Memindai Dropzone...</span>
                `;
            }

            try {
                const res = await fetch('<?= base_url('admin/corpus/sync-dropzone') ?>', {
                    method: 'POST',
                    headers: { 'Accept': 'application/json' }
                });
                const data = await res.json();
                if (data.status === 'success') {
                    alert('Pemindaian Dropzone Berhasil!\n\n' + (data.output || data.message));
                    location.reload();
                } else {
                    alert('Gagal memindai dropzone: ' + (data.message || 'Error'));
                    if (btn) {
                        btn.disabled = false;
                        btn.innerHTML = '<span>Pindai Dropzone Sekarang</span>';
                    }
                }
            } catch (err) {
                alert('Kendala koneksi ke server: ' + err.message);
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = '<span>Pindai Dropzone Sekarang</span>';
                }
            }
        }

        function toggleDiscoverMode(mode) {
            const queryInput = document.getElementById('discoverQuery');
            if (queryInput) {
                if (mode === 'registry') {
                    queryInput.disabled = true;
                    queryInput.placeholder = 'Mode Katalog Repositori: Otomatis menjelajahi repositori perguruan tinggi Indonesia...';
                } else {
                    queryInput.disabled = false;
                    queryInput.placeholder = 'Contoh: Pendidikan Agama Kristen, Teologi Pastoral, Kurikulum Merdeka...';
                }
            }
        }

        async function handleAutoDiscover(e) {
            e.preventDefault();
            const btn = document.getElementById('btnAutoDiscover');
            const box = document.getElementById('discoverResultBox');
            const query = document.getElementById('discoverQuery').value.trim();
            const limit = document.getElementById('discoverLimit').value;
            const mode = document.querySelector('input[name="discoverMode"]:checked')?.value || 'query';

            if (mode === 'query' && !query) {
                alert('Silakan masukkan topik atau kata kunci pencarian karya ilmiah.');
                return;
            }

            if (btn) {
                btn.disabled = true;
                btn.innerHTML = `
                    <svg class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span>Mencari & Mengindeks...</span>
                `;
            }

            if (box) {
                box.classList.remove('hidden');
                box.innerHTML = `
                    <div class="flex items-center gap-2.5 text-indigo-700 py-1">
                        <svg class="animate-spin h-4 w-4 text-indigo-600 flex-shrink-0" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span class="text-xs font-medium text-slate-700">Menghubungi jaringan repositori ilmiah terbuka dan mengunduh berkas PDF... Mohon tunggu sebentar.</span>
                    </div>
                `;
            }

            try {
                const formData = new FormData();
                formData.append('mode', mode);
                formData.append('query', query);
                formData.append('limit', limit);

                const res = await fetch('<?= base_url('admin/corpus/auto-discover') ?>', {
                    method: 'POST',
                    headers: { 'Accept': 'application/json' },
                    body: formData
                });
                const data = await res.json();

                if (res.ok && data.status === 'success') {
                    box.innerHTML = `
                        <div class="p-3 bg-emerald-50 text-emerald-800 rounded-lg border border-emerald-200">
                            <div class="font-bold flex items-center gap-1.5 mb-1 text-xs sm:text-sm">
                                <span>✓ Pencarian & Pengindeksan Berhasil!</span>
                            </div>
                            <pre class="text-[11px] sm:text-xs text-slate-700 whitespace-pre-wrap font-mono mt-2 bg-white p-3 rounded-lg border border-emerald-100 max-h-60 overflow-y-auto">${data.output || data.message}</pre>
                            <div class="mt-3 flex justify-end">
                                <button onclick="location.reload()" class="px-3.5 py-1.5 bg-emerald-700 text-white font-semibold rounded-lg text-xs shadow-xs hover:bg-emerald-800 cursor-pointer transition">
                                    Segarkan Tabel Korpus
                                </button>
                            </div>
                        </div>
                    `;
                } else {
                    box.innerHTML = `
                        <div class="p-3 bg-rose-50 text-rose-800 rounded-lg border border-rose-200">
                            <div class="font-bold text-xs sm:text-sm">Gagal Melakukan Pencarian:</div>
                            <p class="mt-1 text-xs">${data.message || 'Terjadi kesalahan sistem.'}</p>
                            ${data.output ? `<pre class="text-[11px] text-slate-700 whitespace-pre-wrap font-mono mt-2 bg-white p-2.5 rounded border border-rose-100 max-h-40 overflow-y-auto">${data.output}</pre>` : ''}
                        </div>
                    `;
                }
            } catch (err) {
                if (box) {
                    box.innerHTML = `<div class="p-3 bg-rose-50 text-rose-800 rounded-lg text-xs">Kendala koneksi ke server: ${err.message}</div>`;
                }
            } finally {
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = `
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <span>Cari & Indeks Otomatis</span>
                    `;
                }
            }
        }

        // Silent Polling: Hanya memperbarui tabel submisi tanpa me-reload seluruh halaman
        let submissionPollingTimer = null;

        function checkAndPollSubmissions() {
            const processingRows = document.querySelectorAll('#submissionsTableContainer .submission-processing');
            if (processingRows.length > 0) {
                if (!submissionPollingTimer) {
                    submissionPollingTimer = setInterval(fetchSubmissionsTableSilently, 3000);
                }
            } else {
                if (submissionPollingTimer) {
                    clearInterval(submissionPollingTimer);
                    submissionPollingTimer = null;
                }
            }
        }

        async function fetchSubmissionsTableSilently() {
            try {
                const res = await fetch('<?= base_url('dashboard') ?>', {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                if (!res.ok) return;
                const html = await res.text();
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                const newContainer = doc.getElementById('submissionsTableContainer');
                const currentContainer = document.getElementById('submissionsTableContainer');
                if (newContainer && currentContainer) {
                    currentContainer.innerHTML = newContainer.innerHTML;
                    checkAndPollSubmissions();
                }
            } catch (err) {
                console.warn('[AR Tilik] Polling tabel submisi tertunda:', err);
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            checkAndPollSubmissions();
        });

        // Modal Sumbang Korpus (+3 Korpus = +1 Submisi)
        function openContributeCorpusModal() {
            <?php if (empty($currentUser)): ?>
            alert('Silakan daftar atau masuk terlebih dahulu untuk menyumbang korpus rujukan dan memperoleh bonus kuota.');
            window.location.href = '<?= base_url('/#auth-section') ?>';
            return;
            <?php endif; ?>
            const modal = document.getElementById('contributeCorpusModal');
            if (modal) modal.classList.remove('hidden');
        }

        function closeContributeCorpusModal() {
            const modal = document.getElementById('contributeCorpusModal');
            if (modal) modal.classList.add('hidden');
            const alertBox = document.getElementById('contributeAlert');
            if (alertBox) alertBox.classList.add('hidden');
        }

        async function handleContributeCorpusSubmit(e) {
            if (e) e.preventDefault();
            const form = document.getElementById('formContributeCorpus');
            if (!form) return;
            const btn = document.getElementById('btnSubmitContribute');
            const alertBox = document.getElementById('contributeAlert');
            const fileInput = document.getElementById('contributeFileInput');

            if (!fileInput.files || !fileInput.files[0]) {
                alert('Silakan pilih berkas korpus ilmiah terlebih dahulu.');
                return;
            }

            btn.disabled = true;
            btn.innerHTML = `
                <svg class="animate-spin h-4 w-4 text-white inline-block mr-1" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span>Memvalidasi & Mengindeks...</span>
            `;
            alertBox.className = 'hidden';

            const formData = new FormData(form);

            try {
                const res = await fetch('<?= base_url('api/corpus/contribute') ?>', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                const data = await res.json();

                if (res.status === 201) {
                    alertBox.className = 'p-3 rounded-xl text-xs bg-emerald-50 text-emerald-800 border border-emerald-200 block';
                    alertBox.innerHTML = '<strong>Sukses!</strong> ' + data.message;
                    setTimeout(() => {
                        window.location.href = '<?= base_url('dashboard') ?>';
                    }, 1200);
                } else if (res.status === 409) {
                    alertBox.className = 'p-3 rounded-xl text-xs bg-amber-50 text-amber-900 border border-amber-300 block';
                    alertBox.innerHTML = '<strong>Duplikat Terdeteksi:</strong> ' + data.message;
                } else {
                    alertBox.className = 'p-3 rounded-xl text-xs bg-rose-50 text-rose-800 border border-rose-200 block';
                    alertBox.innerHTML = '<strong>Gagal:</strong> ' + (data.message || 'Terjadi kesalahan saat memproses korpus.');
                }
            } catch (err) {
                alertBox.className = 'p-3 rounded-xl text-xs bg-rose-50 text-rose-800 border border-rose-200 block';
                alertBox.innerHTML = '<strong>Kendala Sistem:</strong> ' + err.message;
            } finally {
                btn.disabled = false;
                btn.innerHTML = '<span>📤 Unggah & Tambah Kuota</span>';
            }
        }
    </script>

    <!-- Contribute Corpus Modal (+3 Korpus = +1 Submisi) -->
    <div id="contributeCorpusModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs z-50 flex items-center justify-center hidden p-4">
        <div class="bg-white rounded-2xl max-w-lg w-full shadow-2xl border border-slate-100 overflow-hidden animate-in fade-in zoom-in-95 duration-150">
            <div class="px-6 py-4 bg-slate-50 border-b border-slate-100 flex items-center justify-between">
                <div>
                    <h3 class="text-base font-bold text-slate-800">Sumbang Korpus Rujukan Ilmiah</h3>
                    <p class="text-[11px] text-slate-500">Mekanisme Gotong-Royong: +3 Korpus Valid = +1 Submisi Gratis</p>
                </div>
                <button type="button" onclick="closeContributeCorpusModal()" class="text-slate-400 hover:text-slate-600 text-2xl font-bold p-1 leading-none">&times;</button>
            </div>
            
            <form id="formContributeCorpus" action="<?= base_url('api/corpus/contribute') ?>" method="POST" class="p-6 space-y-4" enctype="multipart/form-data" onsubmit="handleContributeCorpusSubmit(event)">
                <div class="p-3 bg-emerald-50 rounded-xl border border-emerald-200 text-xs text-emerald-800 leading-relaxed">
                    💡 <strong>Aturan Validasi:</strong> Naskah harus berupa karya ilmiah (jurnal, skripsi, tesis, makalah) dalam format PDF, DOCX, atau TXT. Berkas duplikat yang sudah ada di database akan ditolak secara otomatis dan tidak menambah kuota.
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Judul Karya Ilmiah / Artikel:</label>
                    <input type="text" name="title" id="contributeTitleInput" placeholder="Contoh: Pengaruh Algoritma Terdistribusi pada Sistem Edukasi" class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                    <p class="text-[10px] text-slate-400 mt-1">Kosongkan jika ingin menggunakan judul otomatis dari nama berkas.</p>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Jenis Sumber Rujukan:</label>
                    <select name="source_type" class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-emerald-500 bg-white">
                        <option value="journal">Artikel Jurnal Ilmiah</option>
                        <option value="thesis">Skripsi / Tesis / Disertasi</option>
                        <option value="conference">Prosiding Konferensi</option>
                        <option value="book">Buku / Monograf / Bab Buku</option>
                        <option value="report">Laporan Penelitian Institusi</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Pilih Berkas Naskah (PDF, DOCX, TXT):</label>
                    <input type="file" name="corpus_document" id="contributeFileInput" accept=".pdf,.docx,.txt" required class="w-full text-xs text-slate-600 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100 cursor-pointer">
                    <p class="text-[10px] text-slate-400 mt-1">Maksimal 25 MB per berkas. Sistem memverifikasi konten tanpa duplikasi.</p>
                </div>

                <div id="contributeAlert" class="hidden p-3 rounded-xl text-xs"></div>

                <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                    <button type="button" onclick="closeContributeCorpusModal()" class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-100 transition">Batal</button>
                    <button type="submit" id="btnSubmitContribute" class="px-5 py-2 rounded-xl text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 transition shadow-xs flex items-center gap-1.5">
                        <span>📤 Unggah & Tambah Kuota</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Midtrans Snap Script -->
    <script type="text/javascript" src="https://app.midtrans.com/snap/snap.js" data-client-key="<?= esc(env('midtrans.clientKey') ?: 'Mid-client-YcdL-k_f7VC97khK') ?>"></script>

    <!-- Midtrans Payment & Top-Up Modal -->
    <div id="paymentModal" class="fixed inset-0 bg-slate-900/70 backdrop-blur-xs z-50 flex items-center justify-center hidden p-4 overflow-y-auto">
        <div class="bg-white rounded-2xl max-w-4xl w-full shadow-2xl border border-slate-100 overflow-hidden my-8 animate-in fade-in zoom-in-95 duration-150">
            <!-- Modal Header -->
            <div class="px-6 py-5 bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 border-b border-indigo-900/50 flex items-center justify-between text-white">
                <div class="space-y-1">
                    <div class="flex items-center gap-2">
                        <span class="text-xl">💳</span>
                        <h3 class="text-lg font-bold">Top-Up Kuota & Paket Langganan</h3>
                    </div>
                    <p class="text-xs text-indigo-200">Pembayaran instan & otomatis via Midtrans: QRIS, GoPay, ShopeePay, dan Virtual Account Bank</p>
                </div>
                <button type="button" onclick="closePaymentModal()" class="text-slate-400 hover:text-white text-2xl font-bold p-1 leading-none transition">&times;</button>
            </div>

            <!-- Modal Body -->
            <div class="p-6 space-y-6">
                <!-- Info Alert -->
                <div class="p-3 bg-indigo-50 border border-indigo-200 rounded-xl flex items-center justify-between gap-3 text-indigo-900 text-xs">
                    <div class="flex items-center gap-2">
                        <span class="text-base">⚡</span>
                        <span>Kuota Anda saat ini: <strong class="text-indigo-950 font-bold"><?= $quotaInfo['total_available'] ?? 3 ?> Submisi</strong>. Pembayaran sukses akan langsung mengkreditkan kuota secara instan ke akun Anda tanpa perlu konfirmasi manual.</span>
                    </div>
                </div>

                <div id="paymentAlert" class="hidden p-3 rounded-xl text-xs"></div>

                <!-- Packages Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <!-- Paket 1: Top-up 3 Dokumen -->
                    <div class="border border-slate-200 hover:border-emerald-500 rounded-2xl p-4 bg-white hover:shadow-md transition flex flex-col justify-between relative group">
                        <div class="space-y-3">
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-700 border border-emerald-200 inline-block">
                                Paling Terjangkau
                            </span>
                            <div>
                                <h4 class="font-extrabold text-sm text-slate-800">Top-Up 3 Dokumen</h4>
                                <div class="mt-1 flex items-baseline gap-1">
                                    <span class="text-xl font-black text-slate-900">Rp 10.000</span>
                                </div>
                                <p class="text-[11px] text-slate-500 mt-1">Rp 3.333 / submisi</p>
                            </div>
                            <ul class="text-[11px] text-slate-600 space-y-1.5 pt-2 border-t border-slate-100">
                                <li class="flex items-center gap-1.5"><span class="text-emerald-500 font-bold">✓</span> +3 submisi dokumen</li>
                                <li class="flex items-center gap-1.5"><span class="text-emerald-500 font-bold">✓</span> Aktif selamanya (tanpa batas)</li>
                                <li class="flex items-center gap-1.5"><span class="text-emerald-500 font-bold">✓</span> Ekspor PDF & tautan publik</li>
                            </ul>
                        </div>
                        <button type="button" onclick="checkoutPackage('topup_3', this)" class="mt-4 w-full py-2 px-3 rounded-xl font-bold text-xs bg-slate-900 hover:bg-emerald-600 text-white transition shadow-xs cursor-pointer flex items-center justify-center gap-1">
                            <span>Pilih Paket</span>
                        </button>
                    </div>

                    <!-- Paket 2: Top-up 10 Dokumen -->
                    <div class="border-2 border-amber-400 rounded-2xl p-4 bg-gradient-to-b from-amber-50/50 to-white hover:shadow-md transition flex flex-col justify-between relative group">
                        <div class="space-y-3">
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-500 text-white shadow-xs inline-block">
                                ⭐ Paling Populer
                            </span>
                            <div>
                                <h4 class="font-extrabold text-sm text-slate-800">Top-Up 10 Dokumen</h4>
                                <div class="mt-1 flex items-baseline gap-1">
                                    <span class="text-xl font-black text-slate-900">Rp 25.000</span>
                                    <span class="text-[10px] text-emerald-600 font-bold">Hemat 16%</span>
                                </div>
                                <p class="text-[11px] text-slate-500 mt-1">Rp 2.500 / submisi</p>
                            </div>
                            <ul class="text-[11px] text-slate-600 space-y-1.5 pt-2 border-t border-amber-100">
                                <li class="flex items-center gap-1.5"><span class="text-emerald-500 font-bold">✓</span> +10 submisi dokumen</li>
                                <li class="flex items-center gap-1.5"><span class="text-emerald-500 font-bold">✓</span> Aktif selamanya (tanpa batas)</li>
                                <li class="flex items-center gap-1.5"><span class="text-emerald-500 font-bold">✓</span> Prioritas pemrosesan dokumen</li>
                            </ul>
                        </div>
                        <button type="button" onclick="checkoutPackage('topup_10', this)" class="mt-4 w-full py-2 px-3 rounded-xl font-bold text-xs bg-amber-500 hover:bg-amber-600 text-white transition shadow-xs cursor-pointer flex items-center justify-center gap-1">
                            <span>Pilih Paket</span>
                        </button>
                    </div>

                    <!-- Paket 3: Pejuang Skripsi -->
                    <div class="border border-slate-200 hover:border-cyan-500 rounded-2xl p-4 bg-white hover:shadow-md transition flex flex-col justify-between relative group">
                        <div class="space-y-3">
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-cyan-100 text-cyan-800 border border-cyan-200 inline-block">
                                Rekomendasi Mahasiswa
                            </span>
                            <div>
                                <h4 class="font-extrabold text-sm text-slate-800">Paket Pejuang Skripsi</h4>
                                <div class="mt-1 flex items-baseline gap-1">
                                    <span class="text-xl font-black text-slate-900">Rp 25.000</span>
                                    <span class="text-xs text-slate-500">/ bln</span>
                                </div>
                                <p class="text-[11px] text-cyan-600 font-medium mt-1">Rp 1.250 / submisi</p>
                            </div>
                            <ul class="text-[11px] text-slate-600 space-y-1.5 pt-2 border-t border-slate-100">
                                <li class="flex items-center gap-1.5"><span class="text-emerald-500 font-bold">✓</span> 20 submisi dokumen / bulan</li>
                                <li class="flex items-center gap-1.5"><span class="text-emerald-500 font-bold">✓</span> Prioritas antrean cepat</li>
                                <li class="flex items-center gap-1.5"><span class="text-emerald-500 font-bold">✓</span> Masa aktif paket 30 hari</li>
                            </ul>
                        </div>
                        <button type="button" onclick="checkoutPackage('subs_skripsi', this)" class="mt-4 w-full py-2 px-3 rounded-xl font-bold text-xs bg-slate-900 hover:bg-cyan-600 text-white transition shadow-xs cursor-pointer flex items-center justify-center gap-1">
                            <span>Langganan 30 Hari</span>
                        </button>
                    </div>

                    <!-- Paket 4: Dosen & Peneliti Pro -->
                    <div class="border border-slate-200 hover:border-purple-500 rounded-2xl p-4 bg-white hover:shadow-md transition flex flex-col justify-between relative group">
                        <div class="space-y-3">
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-purple-100 text-purple-800 border border-purple-200 inline-block">
                                Kapasitas Besar
                            </span>
                            <div>
                                <h4 class="font-extrabold text-sm text-slate-800">Dosen & Akademisi Pro</h4>
                                <div class="mt-1 flex items-baseline gap-1">
                                    <span class="text-xl font-black text-slate-900">Rp 75.000</span>
                                    <span class="text-xs text-slate-500">/ bln</span>
                                </div>
                                <p class="text-[11px] text-purple-600 font-medium mt-1">Rp 1.000 / submisi</p>
                            </div>
                            <ul class="text-[11px] text-slate-600 space-y-1.5 pt-2 border-t border-slate-100">
                                <li class="flex items-center gap-1.5"><span class="text-emerald-500 font-bold">✓</span> 75 submisi dokumen / bulan</li>
                                <li class="flex items-center gap-1.5"><span class="text-emerald-500 font-bold">✓</span> Prioritas pemrosesan tertinggi</li>
                                <li class="flex items-center gap-1.5"><span class="text-emerald-500 font-bold">✓</span> Dukungan berkas hingga 30 MB</li>
                            </ul>
                        </div>
                        <button type="button" onclick="checkoutPackage('subs_dosen', this)" class="mt-4 w-full py-2 px-3 rounded-xl font-bold text-xs bg-slate-900 hover:bg-purple-600 text-white transition shadow-xs cursor-pointer flex items-center justify-center gap-1">
                            <span>Langganan 30 Hari</span>
                        </button>
                    </div>
                </div>

                <!-- Payment Methods Supported -->
                <div class="pt-4 border-t border-slate-100 flex flex-wrap items-center justify-between text-xs text-slate-400 gap-3">
                    <div class="flex items-center gap-2">
                        <span>Metode Pembayaran Resmi:</span>
                        <span class="font-semibold text-slate-600">QRIS (GoPay, OVO, Dana, LinkAja, ShopeePay), BCA, BRI, BNI, Mandiri VA</span>
                    </div>
                    <div class="text-[11px] text-slate-400">
                        Keamanan dienkripsi 256-bit SSL via Midtrans Gateway
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Payment & Snap Logic -->
    <script>
        function openPaymentModal() {
            document.getElementById('paymentModal').classList.remove('hidden');
        }

        function closePaymentModal() {
            document.getElementById('paymentModal').classList.add('hidden');
        }

        async function checkoutPackage(packageId, btnElement) {
            const originalText = btnElement.innerHTML;
            btnElement.disabled = true;
            btnElement.innerHTML = '<span class="inline-block animate-spin mr-1">⌛</span> Menyiapkan...';

            const alertBox = document.getElementById('paymentAlert');
            alertBox.className = 'hidden';

            try {
                const formData = new FormData();
                formData.append('package_id', packageId);

                const response = await fetch('<?= base_url('payment/create-snap') ?>', {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    body: formData
                });

                const data = await response.json();

                if (!response.ok || data.status !== 'success' || !data.snap_token) {
                    throw new Error(data.message || 'Gagal memproses token pembayaran Midtrans.');
                }

                // Tutup modal pemilihan paket
                closePaymentModal();

                // Buka Snap Popup Midtrans
                if (window.snap) {
                    window.snap.pay(data.snap_token, {
                        onSuccess: function(result) {
                            alert('🎉 Pembayaran Berhasil! Kuota akun Anda telah diperbarui.');
                            window.location.reload();
                        },
                        onPending: function(result) {
                            alert('⏳ Pembayaran sedang menunggu. Silakan selesaikan pembayaran sesuai instruksi QRIS/Virtual Account.');
                            window.location.reload();
                        },
                        onError: function(result) {
                            alert('❌ Terjadi kendala pembayaran: ' + (result.status_message || 'Gagal'));
                        },
                        onClose: function() {
                            // User menutup popup sebelum bayar
                        }
                    });
                } else {
                    // Fallback jika popup JS terblokir
                    window.location.href = data.redirect_url;
                }
            } catch (err) {
                alertBox.className = 'p-3 rounded-xl text-xs bg-rose-50 text-rose-800 border border-rose-200 block';
                alertBox.innerHTML = '<strong>Kendala:</strong> ' + err.message;
            } finally {
                btnElement.disabled = false;
                btnElement.innerHTML = originalText;
            }
        }
    </script>
</body>
</html>

