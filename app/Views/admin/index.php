<!DOCTYPE html>
<html lang="id" class="bg-slate-900">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal Administrator - AR Tilik Academic Writing Similarity Checker</title>
    <meta name="robots" content="noindex, nofollow">
    <!-- Favicon & Brand Icons -->
    <link rel="icon" type="image/x-icon" href="<?= base_url('favicon.ico?v=20261008') ?>">
    <link rel="icon" type="image/png" sizes="32x32" href="<?= base_url('favicon-32x32.png?v=20261008') ?>">
    <link rel="icon" type="image/png" sizes="16x16" href="<?= base_url('favicon-16x16.png?v=20261008') ?>">
    <link rel="apple-touch-icon" sizes="180x180" href="<?= base_url('apple-touch-icon.png?v=20261008') ?>">
    <link rel="shortcut icon" type="image/x-icon" href="<?= base_url('favicon.ico?v=20261008') ?>">
    <!-- Anti-Flash Critical CSS Fallback for Canvas & Theme -->
    <style>
        html {
            background-color: #0f172a !important; /* Tailwind slate-900: eliminates white flash on page load */
            color-scheme: dark;
        }
        body {
            background-color: #0f172a !important;
            font-family: 'Plus Jakarta Sans', sans-serif;
        }
        .font-mono-code { font-family: 'JetBrains Mono', monospace; }
        /* Sleek sidebar scroll styling */
        .admin-sidebar-scroll {
            scrollbar-width: thin;
            scrollbar-color: rgba(99, 102, 241, 0.25) transparent;
        }
        .admin-sidebar-scroll::-webkit-scrollbar {
            width: 4px;
        }
        .admin-sidebar-scroll::-webkit-scrollbar-track {
            background: transparent;
        }
        .admin-sidebar-scroll::-webkit-scrollbar-thumb {
            background: rgba(99, 102, 241, 0.25);
            border-radius: 9999px;
        }
        .admin-sidebar-scroll::-webkit-scrollbar-thumb:hover {
            background: rgba(99, 102, 241, 0.6);
        }
        #topProgressBar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(to right, #6366f1, #38bdf8, #34d399);
            z-index: 99999;
            pointer-events: none;
            box-shadow: 0 0 10px rgba(99, 102, 241, 0.7);
            transition: width 0.3s cubic-bezier(0.4, 0, 0.2, 1), opacity 0.3s ease;
        }
        #pageTransitionOverlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            width: 100vw;
            height: 100vh;
            background-color: rgba(2, 6, 23, 0.88);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            z-index: 99998;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            opacity: 1;
            transition: opacity 0.3s ease-in-out;
        }
        @keyframes spinPreloader {
            to { transform: rotate(360deg); }
        }
        .preloader-spinner {
            animation: spinPreloader 0.8s linear infinite;
        }
    </style>
    <!-- Tailwind CSS CDN & Chart.js CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
</head>
<body class="bg-slate-900 text-slate-100 antialiased min-h-screen flex flex-col">

    <!-- Top Loading Progress Bar (NProgress-style) -->
    <div id="topProgressBar" style="width: 45%;"></div>

    <!-- Page Transition Spinner Overlay (Initially rendered visible for zero-flash page transition & preloading) -->
    <div id="pageTransitionOverlay">
        <div class="bg-slate-900 border border-slate-700/80 rounded-2xl px-6 py-4 shadow-2xl flex items-center gap-3.5 text-slate-100 max-w-sm w-full sm:w-auto" style="background-color: #0f172a; border: 1px solid rgba(51, 65, 85, 0.8); border-radius: 1rem; padding: 1rem 1.5rem; display: flex; align-items: center; gap: 0.875rem;">
            <svg class="preloader-spinner" style="width: 1.5rem; height: 1.5rem; color: #818cf8; flex-shrink: 0;" fill="none" viewBox="0 0 24 24">
                <circle opacity="0.25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path opacity="0.75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <div style="display: flex; flex-direction: column; min-width: 0;">
                <span class="text-sm font-semibold text-slate-200 truncate" id="pageTransitionTitle" style="font-size: 0.875rem; font-weight: 600; color: #e2e8f0;">Memuat Portal Admin...</span>
                <span class="text-[11px] text-slate-400 truncate" id="pageTransitionSubtitle" style="font-size: 0.6875rem; color: #94a3b8;">Menyiapkan data &amp; antarmuka</span>
            </div>
        </div>
    </div>
    <script>
        (function() {
            var b = document.getElementById('topProgressBar');
            if (b) b.style.width = '70%';
            // Failsafe auto-dismiss overlay to guarantee the admin portal is never stuck behind the loader
            setTimeout(function() {
                var o = document.getElementById('pageTransitionOverlay');
                if (o && o.style.display !== 'none') {
                    o.style.opacity = '0';
                    o.style.pointerEvents = 'none';
                    setTimeout(function() { o.style.display = 'none'; }, 250);
                }
                if (b) b.style.opacity = '0';
            }, 400);
        })();
    </script>

    <!-- Top Admin Header -->
    <header class="bg-slate-950/95 border-b border-slate-800/80 sticky top-0 z-40 backdrop-blur-md">
        <div class="w-full px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16 gap-4">
                <!-- Mobile Toggle & Brand & Admin Badge -->
                <div class="flex items-center gap-3">
                    <button type="button" onclick="toggleAdminSidebar()" class="lg:hidden p-2 -ml-2 text-slate-400 hover:text-white rounded-xl hover:bg-slate-800/80 transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500" aria-label="Toggle Sidebar">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                    </button>
                    <a href="<?= base_url('admin') ?>" class="flex items-center gap-2.5 group">
                        <img src="<?= base_url('images/logo.png') ?>" alt="AR Tilik" class="w-8 h-8 object-contain drop-shadow group-hover:scale-105 transition-transform">
                        <span class="text-lg font-bold bg-gradient-to-r from-indigo-400 via-sky-300 to-emerald-400 bg-clip-text text-transparent">
                            AR Tilik
                        </span>
                    </a>
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 hidden sm:inline-flex items-center gap-1.5">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                        Portal Admin
                    </span>
                </div>

                <!-- Search Bar -->
                <form action="<?= base_url('admin') ?>" method="get" class="hidden md:flex items-center flex-1 max-w-sm lg:max-w-md mx-4">
                    <input type="hidden" name="tab" value="<?= esc($activeTab) ?>">
                    <div class="relative w-full">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                        </div>
                        <input type="text" name="q" value="<?= esc($searchQuery) ?>" placeholder="Cari pengguna, email, atau judul dokumen..." class="w-full pl-9 pr-4 py-1.5 bg-slate-900 border border-slate-700/80 rounded-xl text-sm text-slate-200 placeholder-slate-500 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-colors">
                    </div>
                </form>

                <!-- Admin Action Links -->
                <div class="flex items-center gap-3">
                    <a href="<?= base_url('dashboard') ?>" class="hidden sm:inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-slate-300 hover:text-white bg-slate-800/80 hover:bg-slate-800 border border-slate-700/60 rounded-lg transition-colors">
                        <svg class="w-3.5 h-3.5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                        Dashboard Pengguna
                    </a>
                    <div class="flex items-center gap-2 pl-2 border-l border-slate-800">
                        <div class="w-8 h-8 rounded-full bg-gradient-to-tr from-indigo-600 to-emerald-500 flex items-center justify-center text-xs font-bold text-white shadow-sm">
                            <?= strtoupper(substr($currentAdmin['name'] ?? 'A', 0, 1)) ?>
                        </div>
                        <div class="hidden lg:block text-left">
                            <div class="text-xs font-semibold text-slate-200 truncate max-w-[120px]"><?= esc($currentAdmin['name'] ?? 'Administrator') ?></div>
                            <div class="text-[10px] text-emerald-400">Super Admin</div>
                        </div>
                        <a href="<?= base_url('auth/logout') ?>" title="Keluar Sesi" class="p-1.5 text-slate-400 hover:text-rose-400 rounded-lg hover:bg-slate-800 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Admin Body Wrapper (Sidebar + Main Content) -->
    <div class="flex-1 flex min-h-[calc(100vh-4rem)] relative">

        <!-- Mobile Sidebar Backdrop Overlay -->
        <div id="adminSidebarOverlay" class="fixed inset-0 top-16 bg-slate-950/80 backdrop-blur-xs z-30 hidden lg:hidden transition-opacity duration-300" onclick="toggleAdminSidebar()"></div>

        <!-- Admin Sidebar Navigation -->
        <aside id="adminSidebar" class="fixed lg:sticky top-16 inset-y-16 left-0 z-40 w-64 lg:w-72 bg-slate-950/95 lg:bg-slate-950/75 border-r border-slate-800/80 backdrop-blur-md flex flex-col justify-between flex-shrink-0 transition-transform duration-300 -translate-x-full lg:translate-x-0 h-[calc(100vh-4rem)] overflow-y-auto admin-sidebar-scroll">
            <div class="p-4 space-y-6">
                <!-- Mobile Sidebar Close Header -->
                <div class="flex items-center justify-between pb-3 border-b border-slate-800/80 lg:hidden">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Navigasi Admin</span>
                    <button type="button" onclick="toggleAdminSidebar()" class="p-1.5 text-slate-400 hover:text-white rounded-lg hover:bg-slate-800" aria-label="Tutup Sidebar">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>

                <!-- Group 1: Menu Utama -->
                <div class="space-y-1">
                    <div class="flex items-center justify-between px-3 pb-1.5 text-[10px] font-bold uppercase tracking-wider text-slate-500">
                        <span>Menu Utama</span>
                        <div class="flex items-center gap-1.5 font-normal normal-case font-mono text-[9px] text-slate-400" title="Auto-reload metrik sidebar setiap 12 detik">
                            <span class="relative flex h-1.5 w-1.5">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-1.5 w-1.5 bg-emerald-500"></span>
                            </span>
                            <span id="sidebar-last-sync" class="text-slate-400">Live</span>
                            <button type="button" onclick="refreshSidebarBadgesRealtime(true)" id="btn-manual-sync-sidebar" class="hover:text-slate-200 transition p-0.5 rounded cursor-pointer" title="Perbarui angka sidebar sekarang">
                                <svg class="w-3 h-3 text-slate-400 hover:text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                            </button>
                        </div>
                    </div>
                    <a href="<?= base_url('admin?tab=overview') ?>" class="group flex items-center justify-between px-3 py-2.5 rounded-xl text-xs font-medium transition-all duration-150 <?= $activeTab === 'overview' ? 'bg-indigo-600/15 text-indigo-300 font-semibold border border-indigo-500/30 shadow-xs' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/60 border border-transparent' ?>">
                        <div class="flex items-center gap-2.5">
                            <svg class="w-4 h-4 <?= $activeTab === 'overview' ? 'text-indigo-400' : 'text-slate-400 group-hover:text-slate-200' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg>
                            <span>Ringkasan</span>
                        </div>
                    </a>
                </div>

                <!-- Group 2: Pengguna & Dokumen -->
                <div class="space-y-1">
                    <div class="px-3 pb-1.5 text-[10px] font-bold uppercase tracking-wider text-slate-500">
                        Konten & Pengguna
                    </div>
                    <a href="<?= base_url('admin?tab=users') ?>" class="group flex items-center justify-between gap-2 px-3 py-2.5 rounded-xl text-xs font-medium transition-all duration-150 <?= $activeTab === 'users' ? 'bg-indigo-600/15 text-indigo-300 font-semibold border border-indigo-500/30 shadow-xs' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/60 border border-transparent' ?>">
                        <div class="flex items-center gap-2.5 min-w-0">
                            <svg class="w-4 h-4 flex-shrink-0 <?= $activeTab === 'users' ? 'text-indigo-400' : 'text-slate-400 group-hover:text-slate-200' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                            <span class="truncate">Pengguna & Kuota</span>
                        </div>
                    </a>
                    <a href="<?= base_url('admin?tab=documents') ?>" class="group flex items-center justify-between gap-2 px-3 py-2.5 rounded-xl text-xs font-medium transition-all duration-150 <?= $activeTab === 'documents' ? 'bg-indigo-600/15 text-indigo-300 font-semibold border border-indigo-500/30 shadow-xs' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/60 border border-transparent' ?>">
                        <div class="flex items-center gap-2.5 min-w-0">
                            <svg class="w-4 h-4 flex-shrink-0 <?= $activeTab === 'documents' ? 'text-indigo-400' : 'text-slate-400 group-hover:text-slate-200' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                            <span class="truncate">Dokumen & Antrean</span>
                        </div>
                        <?php $totalQueue = (int)($metrics['queue_uploaded'] ?? 0) + (int)($metrics['queue_processing'] ?? 0); ?>
                        <span id="sidebar-badge-documents" title="<?= $totalQueue ?> dokumen dalam antrean proses" class="flex-shrink-0 min-w-5 h-5 px-1.5 inline-flex items-center justify-center rounded-full text-[10px] bg-amber-500/20 text-amber-300 border border-amber-500/30 font-bold animate-pulse transition-all duration-300 <?= $totalQueue > 0 ? '' : 'hidden' ?>">
                            <?= $totalQueue > 0 ? $totalQueue : '' ?>
                        </span>
                    </a>
                    <a href="<?= base_url('admin?tab=corpus') ?>" class="group flex items-center justify-between gap-2 px-3 py-2.5 rounded-xl text-xs font-medium transition-all duration-150 <?= $activeTab === 'corpus' ? 'bg-indigo-600/15 text-indigo-300 font-semibold border border-indigo-500/30 shadow-xs' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/60 border border-transparent' ?>">
                        <div class="flex items-center gap-2.5 min-w-0">
                            <svg class="w-4 h-4 flex-shrink-0 <?= $activeTab === 'corpus' ? 'text-indigo-400' : 'text-slate-400 group-hover:text-slate-200' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
                            <span class="truncate">Korpus Repositori</span>
                        </div>
                    </a>
                </div>

                <!-- Group 3: Analisis & Integrasi -->
                <div class="space-y-1">
                    <div class="px-3 pb-1.5 text-[10px] font-bold uppercase tracking-wider text-slate-500">
                        Analisis & Ekosistem
                    </div>
                    <a href="<?= base_url('admin?tab=analytics') ?>" class="group flex items-center justify-between gap-2 px-3 py-2.5 rounded-xl text-xs font-medium transition-all duration-150 <?= $activeTab === 'analytics' ? 'bg-indigo-600/15 text-indigo-300 font-semibold border border-indigo-500/30 shadow-xs' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/60 border border-transparent' ?>">
                        <div class="flex items-center gap-2.5 min-w-0">
                            <svg class="w-4 h-4 flex-shrink-0 <?= $activeTab === 'analytics' ? 'text-indigo-400' : 'text-slate-400 group-hover:text-slate-200' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                            <span class="truncate">Analitik Institusi</span>
                        </div>
                    </a>
                    <a href="<?= base_url('admin?tab=api_keys') ?>" class="group flex items-center justify-between gap-2 px-3 py-2.5 rounded-xl text-xs font-medium transition-all duration-150 <?= $activeTab === 'api_keys' ? 'bg-indigo-600/15 text-indigo-300 font-semibold border border-indigo-500/30 shadow-xs' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/60 border border-transparent' ?>">
                        <div class="flex items-center gap-2.5 min-w-0">
                            <svg class="w-4 h-4 flex-shrink-0 <?= $activeTab === 'api_keys' ? 'text-indigo-400' : 'text-slate-400 group-hover:text-slate-200' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path></svg>
                            <span class="truncate">Integrasi API & OJS</span>
                        </div>
                    </a>
                    <?php
                    $gActive = (int) ($activeGoogleKeysCount ?? 0);
                    $gTroubled = count(array_filter($googleKeysList ?? [], fn($k) => ($k['status'] ?? '') !== 'active'));
                    $gBadgeClass = 'hidden';
                    $gBadgeText = '';
                    $gBadgeTitle = '';
                    if ($gActive === 0) {
                        $gBadgeClass = 'bg-rose-500/20 text-rose-300 border border-rose-500/30 animate-pulse';
                        $gBadgeText = '0';
                        $gBadgeTitle = '0 kunci Google AI aktif (Darurat)';
                    } elseif ($gTroubled > 0) {
                        $gBadgeClass = 'bg-amber-500/20 text-amber-300 border border-amber-500/30';
                        $gBadgeText = (string) $gTroubled;
                        $gBadgeTitle = $gTroubled . ' kunci Google AI berstatus limit/bermasalah';
                    }
                    ?>
                    <a href="<?= base_url('admin?tab=google_keys') ?>" class="group flex items-center justify-between gap-2 px-3 py-2.5 rounded-xl text-xs font-medium transition-all duration-150 <?= $activeTab === 'google_keys' ? 'bg-indigo-600/15 text-indigo-300 font-semibold border border-indigo-500/30 shadow-xs' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/60 border border-transparent' ?>">
                        <div class="flex items-center gap-2.5 min-w-0">
                            <svg class="w-4 h-4 flex-shrink-0 <?= $activeTab === 'google_keys' ? 'text-indigo-400' : 'text-slate-400 group-hover:text-slate-200' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                            <span class="truncate">AI Gateway (Groq &amp; Gemini)</span>
                        </div>
                        <span id="sidebar-badge-google_keys" title="<?= esc($gBadgeTitle) ?>" class="flex-shrink-0 min-w-5 h-5 px-1.5 inline-flex items-center justify-center rounded-full text-[10px] font-bold transition-all duration-300 <?= $gBadgeClass ?>">
                            <?= $gBadgeText ?>
                        </span>
                    </a>
                    <?php $pendingPayCount = (int) ($metrics['payment_stats']['pending_count'] ?? 0); ?>
                    <a href="<?= base_url('admin?tab=payments') ?>" class="group flex items-center justify-between gap-2 px-3 py-2.5 rounded-xl text-xs font-medium transition-all duration-150 <?= $activeTab === 'payments' ? 'bg-indigo-600/15 text-indigo-300 font-semibold border border-indigo-500/30 shadow-xs' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/60 border border-transparent' ?>">
                        <div class="flex items-center gap-2.5 min-w-0">
                            <svg class="w-4 h-4 flex-shrink-0 <?= $activeTab === 'payments' ? 'text-indigo-400' : 'text-slate-400 group-hover:text-slate-200' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>
                            <span class="truncate">Transaksi & Pembayaran</span>
                        </div>
                        <span id="sidebar-badge-payments" title="<?= $pendingPayCount ?> transaksi pending menunggu konfirmasi" class="flex-shrink-0 min-w-5 h-5 px-1.5 inline-flex items-center justify-center rounded-full text-[10px] bg-amber-500/20 text-amber-300 border border-amber-500/30 font-bold transition-all duration-300 <?= $pendingPayCount > 0 ? '' : 'hidden' ?>">
                            <?= $pendingPayCount > 0 ? $pendingPayCount : '' ?>
                        </span>
                    </a>
                    <?php $pendingReviews = (int) ($testimonialStats['count_pending'] ?? 0); ?>
                    <a href="<?= base_url('admin?tab=feedback') ?>" class="group flex items-center justify-between gap-2 px-3 py-2.5 rounded-xl text-xs font-medium transition-all duration-150 <?= $activeTab === 'feedback' ? 'bg-indigo-600/15 text-indigo-300 font-semibold border border-indigo-500/30 shadow-xs' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/60 border border-transparent' ?>">
                        <div class="flex items-center gap-2.5 min-w-0">
                            <svg class="w-4 h-4 flex-shrink-0 <?= $activeTab === 'feedback' ? 'text-indigo-400' : 'text-slate-400 group-hover:text-slate-200' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>
                            <span class="truncate">Feedback &amp; Testimoni</span>
                        </div>
                        <span id="sidebar-badge-feedback" title="<?= $pendingReviews ?> ulasan baru menunggu moderasi" class="flex-shrink-0 min-w-5 h-5 px-1.5 inline-flex items-center justify-center rounded-full text-[10px] bg-amber-500/20 text-amber-300 border border-amber-500/30 font-bold transition-all duration-300 <?= $pendingReviews > 0 ? '' : 'hidden' ?>">
                            <?= $pendingReviews > 0 ? $pendingReviews : '' ?>
                        </span>
                    </a>
                    <?php $ambPending = (int) ($ambStats['pending_review'] ?? 0); ?>
                    <a href="<?= base_url('admin?tab=ambassador') ?>" class="group flex items-center justify-between gap-2 px-3 py-2.5 rounded-xl text-xs font-medium transition-all duration-150 <?= $activeTab === 'ambassador' ? 'bg-indigo-600/15 text-indigo-300 font-semibold border border-indigo-500/30 shadow-xs' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/60 border border-transparent' ?>">
                        <div class="flex items-center gap-2.5 min-w-0">
                            <span class="text-sm flex-shrink-0">🎓</span>
                            <span class="truncate">Duta Kampus (Afiliasi)</span>
                        </div>
                        <span id="sidebar-badge-ambassador" title="<?= $ambPending ?> pendaftaran duta baru menunggu review" class="flex-shrink-0 min-w-5 h-5 px-1.5 inline-flex items-center justify-center rounded-full text-[10px] bg-amber-500/20 text-amber-300 border border-amber-500/30 font-bold transition-all duration-300 <?= $ambPending > 0 ? '' : 'hidden' ?>">
                            <?= $ambPending > 0 ? $ambPending : '' ?>
                        </span>
                    </a>
                </div>

                <!-- Group 4: Sistem & Keamanan -->
                <div class="space-y-1">
                    <div class="px-3 pb-1.5 text-[10px] font-bold uppercase tracking-wider text-slate-500">
                        Sistem & Keamanan
                    </div>
                    <a href="<?= base_url('admin?tab=security') ?>" class="group flex items-center justify-between gap-2 px-3 py-2.5 rounded-xl text-xs font-medium transition-all duration-150 <?= $activeTab === 'security' ? 'bg-indigo-600/15 text-indigo-300 font-semibold border border-indigo-500/30 shadow-xs' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/60 border border-transparent' ?>">
                        <div class="flex items-center gap-2.5 min-w-0">
                            <svg class="w-4 h-4 flex-shrink-0 <?= $activeTab === 'security' ? 'text-indigo-400' : 'text-slate-400 group-hover:text-slate-200' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                            <span class="truncate">Keamanan & Limit</span>
                        </div>
                        <?php $locksCount = (int) ($metrics['active_locks_count'] ?? 0); ?>
                        <span id="sidebar-badge-security" title="<?= $locksCount ?> akun / IP terkunci" class="flex-shrink-0 min-w-5 h-5 px-1.5 inline-flex items-center justify-center rounded-full text-[10px] bg-rose-500/20 text-rose-300 border border-rose-500/30 font-bold transition-all duration-300 <?= $locksCount > 0 ? '' : 'hidden' ?>">
                            <?= $locksCount > 0 ? $locksCount : '' ?>
                        </span>
                    </a>
                    <a href="<?= base_url('admin?tab=visitors') ?>" class="group flex items-center justify-between gap-2 px-3 py-2.5 rounded-xl text-xs font-medium transition-all duration-150 <?= $activeTab === 'visitors' ? 'bg-indigo-600/15 text-indigo-300 font-semibold border border-indigo-500/30 shadow-xs' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/60 border border-transparent' ?>">
                        <div class="flex items-center gap-2.5 min-w-0">
                            <svg class="w-4 h-4 flex-shrink-0 <?= $activeTab === 'visitors' ? 'text-indigo-400' : 'text-slate-400 group-hover:text-slate-200' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                            <span class="truncate">Pengunjung</span>
                        </div>
                    </a>
                    <a href="<?= base_url('admin?tab=system') ?>" class="group flex items-center justify-between gap-2 px-3 py-2.5 rounded-xl text-xs font-medium transition-all duration-150 <?= $activeTab === 'system' ? 'bg-indigo-600/15 text-indigo-300 font-semibold border border-indigo-500/30 shadow-xs' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/60 border border-transparent' ?>">
                        <div class="flex items-center gap-2.5 min-w-0">
                            <svg class="w-4 h-4 flex-shrink-0 <?= $activeTab === 'system' ? 'text-indigo-400' : 'text-slate-400 group-hover:text-slate-200' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                            <span class="truncate">Sistem Server</span>
                        </div>
                    </a>
                </div>
            </div>

            <!-- Sidebar Footer Status Box -->
            <div class="p-4 border-t border-slate-800/80 bg-slate-950/40">
                <div class="p-3 rounded-xl bg-slate-900/80 border border-slate-800 space-y-1.5">
                    <div class="flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                        <span class="text-[11px] font-semibold text-slate-200">Hybrid Engine v2.4.0</span>
                    </div>
                    <p class="text-[10px] text-slate-400 leading-relaxed">
                        Dual-lens similarity & frozen k-gram repository aktif.
                    </p>
                </div>
            </div>
        </aside>

        <!-- Main Content Area -->
        <main class="flex-1 min-w-0 w-full px-4 sm:px-6 lg:px-8 py-6 overflow-y-auto">

        <!-- Flash Notice / Toast -->
        <?php if (session()->getFlashdata('message')): ?>
            <div class="mb-5 p-4 rounded-xl bg-emerald-950/60 border border-emerald-500/30 text-emerald-200 text-sm flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    <span><?= session()->getFlashdata('message') ?></span>
                </div>
            </div>
        <?php endif; ?>
        <?php if (session()->getFlashdata('error')): ?>
            <div class="mb-5 p-4 rounded-xl bg-rose-950/60 border border-rose-500/30 text-rose-200 text-sm flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <span><?= session()->getFlashdata('error') ?></span>
                </div>
            </div>
        <?php endif; ?>

        <!-- ========================================== -->
        <!-- TAB 1: OVERVIEW METRICS                    -->
        <!-- ========================================== -->
        <?php if ($activeTab === 'overview'): ?>
            <div class="space-y-6">
                <!-- Alerting AI Degradation (Jika Kunci Google Mengalami 503 Massal) -->
                <?php if (!empty($googleAiDegraded) || (!empty($keys503Count) && $keys503Count >= 3)): ?>
                    <div class="bg-amber-950/40 border border-amber-500/40 rounded-2xl p-4 shadow-md flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                        <div class="flex items-start gap-3">
                            <div class="p-2 rounded-xl bg-amber-500/20 text-amber-400 border border-amber-500/30 shrink-0 mt-0.5">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                            </div>
                            <div>
                                <h3 class="text-sm font-bold text-amber-200 flex items-center gap-2">
                                    Peringatan Kesiapan AI: Upstream Google 503 Terdeteksi
                                    <span class="px-2 py-0.5 rounded-full text-[10px] bg-amber-500/20 text-amber-300 border border-amber-500/30 font-semibold font-mono-code"><?= $keys503Count ?> Kunci High-Demand</span>
                                </h3>
                                <p class="text-xs text-slate-300 mt-0.5">
                                    Tercatat <?= $keys503Count ?> dari <?= $activeGoogleKeysCount ?? 8 ?> kunci Google API aktif mendeteksi status High Demand (HTTP 503). Mekanisme round-robin pool otomatis melakukan failover, namun disarankan untuk memeriksa atau menambah kunci cadangan.
                                </p>
                            </div>
                        </div>
                        <a href="<?= base_url('admin?tab=google_keys') ?>" class="px-3.5 py-1.5 rounded-xl bg-amber-600 hover:bg-amber-500 text-white text-xs font-semibold shrink-0 transition-colors shadow-sm">
                            Kelola Pool Kunci AI &rarr;
                        </a>
                    </div>
                <?php endif; ?>

                <!-- KPI Metric Cards Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <!-- Total Users -->
                    <div class="bg-slate-800/60 border border-slate-700/60 rounded-2xl p-5 shadow-sm hover:border-slate-600/80 transition-colors">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Total Pengguna</span>
                            <div class="p-2 rounded-xl bg-indigo-500/10 text-indigo-400 border border-indigo-500/20">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                            </div>
                        </div>
                        <div class="mt-3 flex items-baseline gap-2">
                            <span class="text-3xl font-extrabold text-white tracking-tight"><?= number_format($metrics['total_users']) ?></span>
                            <span class="text-xs text-emerald-400 font-medium"><?= number_format($metrics['active_users']) ?> aktif</span>
                        </div>
                        <div class="mt-2 text-xs text-slate-400 flex items-center gap-2">
                            <span><?= $metrics['pending_users'] ?> menunggu verifikasi email</span>
                        </div>
                    </div>

                    <!-- Submissions Week / Total -->
                    <div class="bg-slate-800/60 border border-slate-700/60 rounded-2xl p-5 shadow-sm hover:border-slate-600/80 transition-colors">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Submisi Dokumen</span>
                            <div class="p-2 rounded-xl bg-sky-500/10 text-sky-400 border border-sky-500/20">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                            </div>
                        </div>
                        <div class="mt-3 flex items-baseline gap-2">
                            <span class="text-3xl font-extrabold text-white tracking-tight"><?= number_format($metrics['total_submissions']) ?></span>
                            <span class="text-xs text-sky-400 font-medium">+<?= $metrics['submissions_this_week'] ?> minggu ini</span>
                        </div>
                        <div class="mt-2 text-xs text-slate-400">
                            <span>Total naskah ilmiah yang diuji plagiarisme</span>
                        </div>
                    </div>

                    <!-- Corpus Database -->
                    <div class="bg-slate-800/60 border border-slate-700/60 rounded-2xl p-5 shadow-sm hover:border-slate-600/80 transition-colors">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Korpus Pembanding</span>
                            <div class="p-2 rounded-xl bg-purple-500/10 text-purple-400 border border-purple-500/20">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
                            </div>
                        </div>
                        <div class="mt-3 flex items-baseline gap-2">
                            <span class="text-3xl font-extrabold text-white tracking-tight"><?= number_format($metrics['total_corpus']) ?></span>
                            <span class="text-xs text-purple-400 font-medium">Total Naskah</span>
                        </div>
                        <div class="mt-2 text-xs text-slate-400 flex items-center justify-between gap-1">
                            <span class="text-emerald-400 font-medium"><?= number_format($metrics['corpus_completed'] ?? $metrics['total_corpus']) ?> aktif terindeks</span>
                            <?php if (!empty($metrics['corpus_failed'])): ?>
                                <span class="text-rose-400 font-medium" title="Gagal ekstraksi/unduh harvester">• <?= $metrics['corpus_failed'] ?> gagal panen</span>
                            <?php endif; ?>
                        </div>
                        <div class="mt-1 text-[11px] text-slate-500">
                            Basis pembanding TF-IDF &amp; Exact Shingle
                        </div>
                    </div>

                    <!-- Security & Rate Limits -->
                    <div class="bg-slate-800/60 border border-slate-700/60 rounded-2xl p-5 shadow-sm hover:border-slate-600/80 transition-colors">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Status Keamanan</span>
                            <div class="p-2 rounded-xl <?= $metrics['active_locks_count'] > 0 ? 'bg-rose-500/10 text-rose-400 border border-rose-500/20 animate-pulse' : 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' ?>">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                            </div>
                        </div>
                        <div class="mt-3 flex items-baseline gap-2">
                            <span class="text-3xl font-extrabold text-white tracking-tight"><?= number_format($metrics['active_locks_count']) ?></span>
                            <span class="text-xs <?= $metrics['active_locks_count'] > 0 ? 'text-rose-400' : 'text-emerald-400' ?> font-medium">IP Terkunci</span>
                        </div>
                        <div class="mt-2 text-xs text-slate-400">
                            <span><?= number_format($metrics['total_security_events']) ?> event keamanan terekam</span>
                        </div>
                    </div>
                </div>

                <!-- Worker Queue Status Banner -->
                <div class="bg-slate-950/80 border border-slate-800 rounded-2xl p-5 shadow-sm flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-indigo-500/10 text-indigo-400 border border-indigo-500/20 flex items-center justify-center flex-shrink-0">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-white">Status Antrean Pemrosesan Naskah (Worker Queue)</h3>
                            <p class="text-xs text-slate-400">Worker Python Hybrid menganalisis kemiripan verbatim & parafrasa di latar belakang</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3 flex-wrap">
                        <span class="px-3 py-1.5 rounded-xl bg-slate-800 text-xs font-semibold text-slate-300 border border-slate-700/60">
                            ⏳ Uploaded: <b class="text-amber-400 ml-1"><?= $metrics['queue_uploaded'] ?></b>
                        </span>
                        <span class="px-3 py-1.5 rounded-xl bg-slate-800 text-xs font-semibold text-slate-300 border border-slate-700/60">
                            ⚙️ Processing: <b class="text-sky-400 ml-1"><?= $metrics['queue_processing'] ?></b>
                        </span>
                        <span class="px-3 py-1.5 rounded-xl bg-slate-800 text-xs font-semibold text-slate-300 border border-slate-700/60">
                            ✅ Completed: <b class="text-emerald-400 ml-1"><?= $metrics['queue_completed'] ?></b>
                        </span>
                        <?php if ($metrics['queue_failed'] > 0): ?>
                            <span class="px-3 py-1.5 rounded-xl bg-rose-950/60 text-xs font-semibold text-rose-300 border border-rose-500/30">
                                ⚠️ Failed: <b class="text-rose-400 ml-1"><?= $metrics['queue_failed'] ?></b>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Two Column Lists: Recent Submissions & Recent Users -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Recent Submissions -->
                    <div class="bg-slate-800/40 border border-slate-700/60 rounded-2xl p-5">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-sm font-bold text-white flex items-center gap-2">
                                <svg class="w-4 h-4 text-sky-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                Submisi Naskah Terbaru
                            </h3>
                            <a href="<?= base_url('admin?tab=documents') ?>" class="text-xs text-indigo-400 hover:text-indigo-300 font-medium">Lihat Semua →</a>
                        </div>
                        <?php $recDocs = array_slice($documentsList, 0, 7); ?>
                        <!-- Mobile List: Naskah Terbaru (Layar Ponsel) -->
                        <div class="space-y-2.5 md:hidden">
                            <?php if (empty($recDocs)): ?>
                                <div class="py-4 text-center text-slate-500 text-xs">Belum ada submisi naskah.</div>
                            <?php else: ?>
                                <?php foreach ($recDocs as $d): ?>
                                    <div class="bg-slate-900/60 border border-slate-800 rounded-xl p-3 space-y-2">
                                        <div class="flex items-start justify-between gap-2">
                                            <div class="min-w-0 flex-1">
                                                <div class="font-medium text-slate-200 text-xs truncate"><?= esc($d['title']) ?></div>
                                                <div class="text-[10px] text-slate-400 mt-0.5 truncate"><?= esc($d['user_name'] ?? 'Tamu') ?></div>
                                            </div>
                                            <?php if ($d['status'] === 'completed'): ?>
                                                <span class="px-2 py-0.5 rounded-full text-[10px] bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 font-semibold shrink-0">Selesai</span>
                                            <?php elseif ($d['status'] === 'processing'): ?>
                                                <span class="px-2 py-0.5 rounded-full text-[10px] bg-sky-500/10 text-sky-400 border border-sky-500/20 font-semibold shrink-0 animate-pulse">Proses</span>
                                            <?php elseif ($d['status'] === 'failed'): ?>
                                                <span class="px-2 py-0.5 rounded-full text-[10px] bg-rose-500/10 text-rose-400 border border-rose-500/20 font-semibold shrink-0">Gagal</span>
                                            <?php else: ?>
                                                <span class="px-2 py-0.5 rounded-full text-[10px] bg-slate-700/60 text-slate-300 font-semibold shrink-0">Antre</span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="flex items-center justify-between text-[11px] pt-1.5 border-t border-slate-800/80">
                                            <span class="text-slate-400 text-[10px]">Skor Kemiripan:</span>
                                            <span class="font-bold font-mono-code <?= isset($d['report']['overall_score']) && $d['report']['overall_score'] >= 25 ? 'text-amber-400' : 'text-emerald-400' ?>">
                                                <?= isset($d['report']['overall_score']) ? number_format($d['report']['overall_score'], 1) . '%' : '-' ?>
                                            </span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>

                        <!-- Desktop Table View: Terlihat di Layar md ke atas -->
                        <div class="hidden md:block overflow-x-auto">
                            <table class="w-full text-xs text-left">
                                <thead class="text-slate-400 uppercase tracking-wider border-b border-slate-700/60 text-[10px]">
                                    <tr>
                                        <th class="py-2.5 px-3">Judul Naskah</th>
                                        <th class="py-2.5 px-3">Pengunggah</th>
                                        <th class="py-2.5 px-3">Status</th>
                                        <th class="py-2.5 px-3">Kemiripan</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-800/80">
                                    <?php if (empty($recDocs)): ?>
                                        <tr><td colspan="4" class="py-4 text-center text-slate-500">Belum ada submisi naskah.</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($recDocs as $d): ?>
                                            <tr class="hover:bg-slate-800/40 transition-colors">
                                                <td class="py-2.5 px-3 font-medium text-slate-200 truncate max-w-[180px]">
                                                    <?= esc($d['title']) ?>
                                                </td>
                                                <td class="py-2.5 px-3 text-slate-400 truncate max-w-[120px]">
                                                    <?= esc($d['user_name'] ?? 'Tamu') ?>
                                                </td>
                                                <td class="py-2.5 px-3">
                                                    <?php if ($d['status'] === 'completed'): ?>
                                                        <span class="px-2 py-0.5 rounded-full text-[10px] bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 font-semibold">Selesai</span>
                                                    <?php elseif ($d['status'] === 'processing'): ?>
                                                        <span class="px-2 py-0.5 rounded-full text-[10px] bg-sky-500/10 text-sky-400 border border-sky-500/20 font-semibold animate-pulse">Proses</span>
                                                    <?php elseif ($d['status'] === 'failed'): ?>
                                                        <span class="px-2 py-0.5 rounded-full text-[10px] bg-rose-500/10 text-rose-400 border border-rose-500/20 font-semibold">Gagal</span>
                                                    <?php else: ?>
                                                        <span class="px-2 py-0.5 rounded-full text-[10px] bg-slate-700/60 text-slate-300 font-semibold">Antre</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="py-2.5 px-3 font-semibold <?= isset($d['report']['overall_score']) && $d['report']['overall_score'] >= 25 ? 'text-amber-400' : 'text-emerald-400' ?>">
                                                    <?= isset($d['report']['overall_score']) ? number_format($d['report']['overall_score'], 1) . '%' : '-' ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Recent Users -->
                    <div class="bg-slate-800/40 border border-slate-700/60 rounded-2xl p-5">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-sm font-bold text-white flex items-center gap-2">
                                <svg class="w-4 h-4 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                                Pendaftaran Pengguna Terbaru
                            </h3>
                            <a href="<?= base_url('admin?tab=users') ?>" class="text-xs text-indigo-400 hover:text-indigo-300 font-medium">Kelola Semua →</a>
                        </div>
                        <?php $recUsers = array_slice($usersList, 0, 7); ?>
                        <!-- Mobile List: Pengguna Terbaru (Layar Ponsel) -->
                        <div class="space-y-2.5 md:hidden">
                            <?php if (empty($recUsers)): ?>
                                <div class="py-4 text-center text-slate-500 text-xs">Belum ada pengguna terdaftar.</div>
                            <?php else: ?>
                                <?php foreach ($recUsers as $u): ?>
                                    <div class="bg-slate-900/60 border border-slate-800 rounded-xl p-3 space-y-2">
                                        <div class="flex items-start justify-between gap-2">
                                            <div class="min-w-0 flex-1">
                                                <div class="font-semibold text-slate-200 text-xs truncate"><?= esc($u['name']) ?></div>
                                                <div class="text-[10px] text-slate-400 mt-0.5 truncate"><?= esc($u['email']) ?></div>
                                            </div>
                                            <span class="px-2 py-0.5 rounded-full text-[10px] <?= $u['role'] === 'admin' ? 'bg-purple-500/20 text-purple-300 border border-purple-500/30 font-bold' : 'bg-slate-800 text-slate-400' ?> shrink-0">
                                                <?= strtoupper($u['role']) ?>
                                            </span>
                                        </div>
                                        <div class="flex items-center justify-between text-[11px] pt-1.5 border-t border-slate-800/80">
                                            <span class="text-slate-400 text-[10px] truncate max-w-[150px]"><?= esc($u['institution'] ?: '-') ?></span>
                                            <div>
                                                <?php if ($u['status'] === 'active'): ?>
                                                    <span class="px-2 py-0.5 rounded-full text-[10px] bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 font-semibold">Aktif</span>
                                                <?php elseif ($u['status'] === 'pending_verification'): ?>
                                                    <span class="px-2 py-0.5 rounded-full text-[10px] bg-amber-500/10 text-amber-400 border border-amber-500/20 font-semibold" title="Email verifikasi terkirim: <?= (int)($u['verification_emails_sent'] ?? 1) ?>/3. Diberikan masa tenggang 48 jam sebelum pembersihan otomatis.">Pending (<?= (int)($u['verification_emails_sent'] ?? 1) ?>/3)</span>
                                                <?php else: ?>
                                                    <span class="px-2 py-0.5 rounded-full text-[10px] bg-rose-500/10 text-rose-400 border border-rose-500/20 font-semibold">Nonaktif</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>

                        <!-- Desktop Table View: Terlihat di Layar md ke atas -->
                        <div class="hidden md:block overflow-x-auto">
                            <table class="w-full text-xs text-left">
                                <thead class="text-slate-400 uppercase tracking-wider border-b border-slate-700/60 text-[10px]">
                                    <tr>
                                        <th class="py-2.5 px-3">Nama & Email</th>
                                        <th class="py-2.5 px-3">Institusi</th>
                                        <th class="py-2.5 px-3">Status</th>
                                        <th class="py-2.5 px-3">Peran</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-800/80">
                                    <?php if (empty($recUsers)): ?>
                                        <tr><td colspan="4" class="py-4 text-center text-slate-500">Belum ada pengguna terdaftar.</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($recUsers as $u): ?>
                                            <tr class="hover:bg-slate-800/40 transition-colors">
                                                <td class="py-2.5 px-3 font-medium text-slate-200">
                                                    <div class="font-semibold truncate max-w-[150px]"><?= esc($u['name']) ?></div>
                                                    <div class="text-[10px] text-slate-400 truncate max-w-[150px]"><?= esc($u['email']) ?></div>
                                                </td>
                                                <td class="py-2.5 px-3 text-slate-400 truncate max-w-[120px]">
                                                    <?= esc($u['institution'] ?: '-') ?>
                                                </td>
                                                <td class="py-2.5 px-3">
                                                    <?php if ($u['status'] === 'active'): ?>
                                                        <span class="px-2 py-0.5 rounded-full text-[10px] bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 font-semibold">Aktif</span>
                                                    <?php elseif ($u['status'] === 'pending_verification'): ?>
                                                        <span class="px-2 py-0.5 rounded-full text-[10px] bg-amber-500/10 text-amber-400 border border-amber-500/20 font-semibold" title="Email verifikasi terkirim: <?= (int)($u['verification_emails_sent'] ?? 1) ?>/3">Pending (<?= (int)($u['verification_emails_sent'] ?? 1) ?>/3)</span>
                                                    <?php else: ?>
                                                        <span class="px-2 py-0.5 rounded-full text-[10px] bg-rose-500/10 text-rose-400 border border-rose-500/20 font-semibold">Nonaktif</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="py-2.5 px-3">
                                                    <span class="px-2 py-0.5 rounded-full text-[10px] <?= $u['role'] === 'admin' ? 'bg-purple-500/20 text-purple-300 border border-purple-500/30 font-bold' : 'bg-slate-800 text-slate-400' ?>">
                                                        <?= strtoupper($u['role']) ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

        <!-- ========================================== -->
        <!-- TAB 2: PENGGUNA & KUOTA                    -->
        <!-- ========================================== -->
        <?php elseif ($activeTab === 'users'): ?>
            <div class="space-y-6">
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                    <div>
                        <h2 class="text-lg font-bold text-white">Manajemen Pengguna & Kontrol Kuota</h2>
                        <p class="text-xs text-slate-400">Atur peran pengguna, verifikasi email darurat, dan penyesuaian kuota submisi</p>
                    </div>
                </div>

                <!-- Mobile List: Tampilan Kartu Responsif Pengguna (Layar Ponsel) -->
                <div class="space-y-3.5 md:hidden">
                    <?php if (empty($usersList)): ?>
                        <div class="bg-slate-800/40 border border-slate-700/60 rounded-2xl p-6 text-center text-xs text-slate-500">
                            Tidak ada pengguna yang cocok dengan kriteria pencarian.
                        </div>
                    <?php else: ?>
                        <?php foreach ($usersList as $u): ?>
                            <div class="bg-slate-800/60 border border-slate-700/70 rounded-2xl p-4 shadow-sm space-y-3">
                                <!-- Baris Identitas & Status -->
                                <div class="flex items-start justify-between gap-3">
                                    <div class="flex items-center gap-3 min-w-0">
                                        <div class="w-10 h-10 rounded-xl bg-gradient-to-tr <?= $u['role'] === 'admin' ? 'from-purple-600 to-indigo-600' : 'from-indigo-600 to-sky-600' ?> flex items-center justify-center text-sm font-bold text-white shadow-xs shrink-0">
                                            <?= strtoupper(substr($u['name'] ?: 'U', 0, 1)) ?>
                                        </div>
                                        <div class="min-w-0">
                                            <div class="font-bold text-slate-100 text-sm truncate"><?= esc($u['name']) ?></div>
                                            <div class="text-slate-400 text-xs font-mono-code truncate"><?= esc($u['email']) ?></div>
                                            <div class="text-[11px] text-slate-400 mt-0.5 flex items-center gap-1">
                                                <span>🏛️</span>
                                                <span class="truncate"><?= esc($u['institution'] ?: 'Tanpa Institusi') ?></span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex flex-col items-end gap-1 shrink-0">
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold <?= $u['role'] === 'admin' ? 'bg-purple-500/20 text-purple-300 border border-purple-500/30' : 'bg-slate-800 text-slate-300 border border-slate-700/60' ?>">
                                            <?= strtoupper($u['role']) ?>
                                        </span>
                                        <?php if (($u['subscription_tier'] ?? 'free') === 'trial_kampus'): ?>
                                            <span class="px-2 py-0.5 rounded text-[10px] bg-gradient-to-r from-emerald-500/20 to-teal-500/20 text-emerald-300 border border-emerald-500/40 font-bold" title="Uji Coba Kampus s.d. <?= esc($u['subscription_expires_at'] ?? '-') ?>">🎓 TRIAL 14H</span>
                                        <?php elseif (($u['subscription_tier'] ?? 'free') === 'skripsi'): ?>
                                            <span class="px-2 py-0.5 rounded text-[10px] bg-amber-500/20 text-amber-300 border border-amber-500/30 font-semibold">SKRIPSI</span>
                                        <?php elseif (($u['subscription_tier'] ?? 'free') === 'dosen_pro'): ?>
                                            <span class="px-2 py-0.5 rounded text-[10px] bg-indigo-500/20 text-indigo-300 border border-indigo-500/30 font-semibold">DOSEN PRO</span>
                                        <?php endif; ?>
                                        <?php if ($u['status'] === 'active'): ?>
                                            <span class="px-2 py-0.5 rounded text-[10px] bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 font-semibold">Aktif</span>
                                        <?php elseif ($u['status'] === 'pending_verification'): ?>
                                            <span class="px-2 py-0.5 rounded text-[10px] bg-amber-500/10 text-amber-400 border border-amber-500/20 font-semibold" title="Email verifikasi terkirim: <?= (int)($u['verification_emails_sent'] ?? 1) ?>/3. Maks 3x pengingat + masa tenggang 48 jam sebelum akun nonaktif dibersihkan.">Pending Email (<?= (int)($u['verification_emails_sent'] ?? 1) ?>/3)</span>
                                        <?php else: ?>
                                            <span class="px-2 py-0.5 rounded text-[10px] bg-rose-500/10 text-rose-400 border border-rose-500/20 font-semibold">Nonaktif</span>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <!-- Ringkasan Metrik Pengguna (Grid 2x2) -->
                                <div class="grid grid-cols-2 gap-2 bg-slate-900/60 border border-slate-800/80 rounded-xl p-2.5 text-xs">
                                    <div>
                                        <div class="text-[10px] uppercase tracking-wider text-slate-500">Kuota Gratis</div>
                                        <div class="font-semibold text-slate-200 mt-0.5">
                                            <?= (int) $u['free_submissions_used_this_week'] ?> <span class="text-slate-500 text-[10px]">/ 3 mg ini</span>
                                        </div>
                                    </div>
                                    <div>
                                        <div class="text-[10px] uppercase tracking-wider text-slate-500">Bonus Kuota</div>
                                        <div class="font-bold text-indigo-400 mt-0.5">
                                            +<?= (int) $u['bonus_submissions'] ?>
                                        </div>
                                    </div>
                                    <div>
                                        <div class="text-[10px] uppercase tracking-wider text-slate-500">Korpus Unggah</div>
                                        <div class="font-medium text-slate-300 mt-0.5">
                                            <?= (int) $u['uploaded_corpus_count'] ?> file
                                        </div>
                                    </div>
                                    <div>
                                        <div class="text-[10px] uppercase tracking-wider text-slate-500">Bergabung</div>
                                        <div class="text-slate-400 mt-0.5 text-[11px]">
                                            <?= date('d M Y', strtotime($u['created_at'])) ?>
                                        </div>
                                    </div>
                                </div>

                                <!-- Penataan Tombol Aksi Kontrol (Grid 2 Kolom Mobile) -->
                                <div class="pt-2 border-t border-slate-700/60">
                                    <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-2">Aksi Kontrol</div>
                                    <div class="grid grid-cols-2 gap-2">
                                        <!-- Tombol Aktivasi Program Uji Coba Resmi Kampus & Jurnal -->
                                        <button onclick="activateTrialKampus(<?= (int) $u['id'] ?>, '<?= esc($u['name'], 'js') ?>')" class="col-span-2 py-2 px-3 rounded-xl bg-gradient-to-r from-emerald-600/20 to-teal-600/20 hover:from-emerald-600/30 hover:to-teal-600/30 text-emerald-300 border border-emerald-500/30 text-xs font-semibold flex items-center justify-center gap-1.5 transition-colors active:scale-98">
                                            <span>🎓</span> <?= ($u['subscription_tier'] ?? '') === 'trial_kampus' ? 'Perpanjang Trial Kampus (+14H & +50)' : 'Aktifkan Trial Jurnal & Kampus (+50 Kuota)' ?>
                                        </button>

                                        <?php if ($u['status'] === 'pending_verification'): ?>
                                            <button onclick="handleUserAction(<?= (int) $u['id'] ?>, 'verify_email')" class="col-span-2 py-2 px-3 rounded-xl bg-emerald-600/20 hover:bg-emerald-600/30 text-emerald-300 border border-emerald-500/30 text-xs font-semibold flex items-center justify-center gap-1.5 transition-colors active:scale-98">
                                                <span>✓</span> Verifikasi Email Manual
                                            </button>
                                        <?php endif; ?>

                                        <button onclick="handleUserAction(<?= (int) $u['id'] ?>, 'reset_weekly_quota')" class="py-2 px-3 rounded-xl bg-slate-900 hover:bg-slate-800 text-slate-200 border border-slate-700 text-xs font-medium flex items-center justify-center gap-1.5 transition-colors active:scale-98">
                                            <span>↺</span> Reset Kuota
                                        </button>

                                        <button onclick="promptBonusQuota(<?= (int) $u['id'] ?>)" class="py-2 px-3 rounded-xl bg-indigo-600/20 hover:bg-indigo-600/30 text-indigo-300 border border-indigo-500/30 text-xs font-semibold flex items-center justify-center gap-1.5 transition-colors active:scale-98">
                                            <span>+</span> Tambah Kuota
                                        </button>

                                        <button onclick="confirmRoleChange(<?= (int) $u['id'] ?>, '<?= esc($u['name'], 'js') ?>', '<?= $u['role'] === 'admin' ? 'user' : 'admin' ?>')" class="py-2 px-3 rounded-xl bg-purple-600/20 hover:bg-purple-600/30 text-purple-300 border border-purple-500/30 text-xs font-medium flex items-center justify-center gap-1.5 transition-colors active:scale-98">
                                            <span>👑</span> <?= $u['role'] === 'admin' ? 'Jadikan User' : 'Jadikan Admin' ?>
                                        </button>

                                        <button onclick="handleUserAction(<?= (int) $u['id'] ?>, 'set_status', {status: '<?= $u['status'] === 'active' ? 'inactive' : 'active' ?>'})" class="py-2 px-3 rounded-xl <?= $u['status'] === 'active' ? 'bg-rose-600/20 hover:bg-rose-600/30 text-rose-300 border-rose-500/30' : 'bg-emerald-600/20 hover:bg-emerald-600/30 text-emerald-300 border-emerald-500/30' ?> border text-xs font-medium flex items-center justify-center gap-1.5 transition-colors active:scale-98">
                                            <span><?= $u['status'] === 'active' ? '🚫' : '✅' ?></span> <?= $u['status'] === 'active' ? 'Suspend' : 'Aktifkan' ?>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Desktop Table View: Terlihat di Layar md ke atas -->
                <div class="hidden md:block bg-slate-800/40 border border-slate-700/60 rounded-2xl overflow-hidden shadow-sm">
                    <div class="overflow-x-auto">
                        <table class="w-full text-xs text-left">
                            <thead class="bg-slate-900/80 text-slate-400 uppercase tracking-wider text-[10px] border-b border-slate-700/60">
                                <tr>
                                    <th class="py-3 px-4">Pengguna</th>
                                    <th class="py-3 px-3">Institusi</th>
                                    <th class="py-3 px-3">Peran & Status</th>
                                    <th class="py-3 px-3 text-center">Kuota Gratis</th>
                                    <th class="py-3 px-3 text-center">Bonus Kuota</th>
                                    <th class="py-3 px-3 text-center">Korpus</th>
                                    <th class="py-3 px-3">Bergabung</th>
                                    <th class="py-3 px-4 text-right">Aksi Kontrol</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-800/80">
                                <?php if (empty($usersList)): ?>
                                    <tr><td colspan="8" class="py-6 text-center text-slate-500">Tidak ada pengguna yang cocok dengan kriteria pencarian.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($usersList as $u): ?>
                                        <tr class="hover:bg-slate-800/50 transition-colors">
                                            <td class="py-3 px-4">
                                                <div class="font-bold text-slate-200"><?= esc($u['name']) ?></div>
                                                <div class="text-slate-400 text-[11px] font-mono-code"><?= esc($u['email']) ?></div>
                                            </td>
                                            <td class="py-3 px-3 text-slate-300">
                                                <?= esc($u['institution'] ?: '-') ?>
                                            </td>
                                            <td class="py-3 px-3">
                                                <div class="flex items-center gap-1.5 flex-wrap">
                                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold <?= $u['role'] === 'admin' ? 'bg-purple-500/20 text-purple-300 border border-purple-500/30' : 'bg-slate-800 text-slate-400 border border-slate-700/60' ?>">
                                                        <?= strtoupper($u['role']) ?>
                                                    </span>
                                                    <?php if (($u['subscription_tier'] ?? 'free') === 'trial_kampus'): ?>
                                                        <span class="px-2 py-0.5 rounded text-[10px] bg-gradient-to-r from-emerald-500/20 to-teal-500/20 text-emerald-300 border border-emerald-500/40 font-bold" title="Uji Coba Kampus s.d. <?= esc($u['subscription_expires_at'] ?? '-') ?>">🎓 TRIAL 14H</span>
                                                    <?php elseif (($u['subscription_tier'] ?? 'free') === 'skripsi'): ?>
                                                        <span class="px-2 py-0.5 rounded text-[10px] bg-amber-500/20 text-amber-300 border border-amber-500/30 font-semibold">SKRIPSI</span>
                                                    <?php elseif (($u['subscription_tier'] ?? 'free') === 'dosen_pro'): ?>
                                                        <span class="px-2 py-0.5 rounded text-[10px] bg-indigo-500/20 text-indigo-300 border border-indigo-500/30 font-semibold">DOSEN PRO</span>
                                                    <?php endif; ?>
                                                    <?php if ($u['status'] === 'active'): ?>
                                                        <span class="px-2 py-0.5 rounded text-[10px] bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 font-semibold">Aktif</span>
                                                    <?php elseif ($u['status'] === 'pending_verification'): ?>
                                                        <span class="px-2 py-0.5 rounded text-[10px] bg-amber-500/10 text-amber-400 border border-amber-500/20 font-semibold" title="Email verifikasi terkirim: <?= (int)($u['verification_emails_sent'] ?? 1) ?>/3. Maks 3x pengingat + masa tenggang 48 jam sebelum akun nonaktif dibersihkan.">Pending Email (<?= (int)($u['verification_emails_sent'] ?? 1) ?>/3)</span>
                                                    <?php else: ?>
                                                        <span class="px-2 py-0.5 rounded text-[10px] bg-rose-500/10 text-rose-400 border border-rose-500/20 font-semibold">Nonaktif</span>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td class="py-3 px-3 text-center">
                                                <span class="font-bold text-slate-200"><?= (int) $u['free_submissions_used_this_week'] ?></span>
                                                <span class="text-slate-500 text-[10px]">/ 3 minggu ini</span>
                                            </td>
                                            <td class="py-3 px-3 text-center font-bold text-indigo-400">
                                                +<?= (int) $u['bonus_submissions'] ?>
                                            </td>
                                            <td class="py-3 px-3 text-center text-slate-300 font-semibold">
                                                <?= (int) $u['uploaded_corpus_count'] ?> file
                                            </td>
                                            <td class="py-3 px-3 text-slate-400 text-[11px] whitespace-nowrap">
                                                <?= date('d M Y', strtotime($u['created_at'])) ?>
                                            </td>
                                            <td class="py-3 px-4 text-right whitespace-nowrap">
                                                <div class="inline-flex items-center rounded-xl bg-slate-900/90 border border-slate-700/70 p-0.5 shadow-xs divide-x divide-slate-800">
                                                    <!-- Aktivasi Uji Coba Resmi Kampus & Jurnal -->
                                                    <button type="button" onclick="activateTrialKampus(<?= (int) $u['id'] ?>, '<?= esc($u['name'], 'js') ?>')" class="p-1.5 text-emerald-400 hover:text-emerald-200 hover:bg-emerald-500/20 rounded-lg transition-colors cursor-pointer" title="Aktifkan Uji Coba Resmi Jurnal & Kampus (14 Hari, +50 Kuota & Kunci API OJS)" aria-label="Aktifkan Trial Kampus">
                                                        <span class="text-xs">🎓</span>
                                                    </button>

                                                    <!-- Verifikasi Manual jika pending -->
                                                    <?php if ($u['status'] === 'pending_verification'): ?>
                                                        <button type="button" onclick="handleUserAction(<?= (int) $u['id'] ?>, 'verify_email')" class="p-1.5 text-emerald-400 hover:text-emerald-200 hover:bg-emerald-500/20 rounded-lg transition-colors cursor-pointer" title="Verifikasi email manual tanpa menunggu konfirmasi" aria-label="Verifikasi Email Manual">
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                                        </button>
                                                    <?php endif; ?>

                                                    <!-- Reset Mingguan -->
                                                    <button type="button" onclick="handleUserAction(<?= (int) $u['id'] ?>, 'reset_weekly_quota')" class="p-1.5 text-sky-400 hover:text-sky-200 hover:bg-sky-500/20 rounded-lg transition-colors cursor-pointer" title="Reset kuota terpakai mingguan ke 0" aria-label="Reset Kuota Mingguan">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                                                    </button>

                                                    <!-- Tambah Bonus Kuota -->
                                                    <button type="button" onclick="promptBonusQuota(<?= (int) $u['id'] ?>)" class="p-1.5 text-indigo-400 hover:text-indigo-200 hover:bg-indigo-500/20 rounded-lg transition-colors cursor-pointer" title="Tambah bonus kuota submisi dokumen" aria-label="Tambah Bonus Kuota">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                                    </button>

                                                    <!-- Toggle Role -->
                                                    <button type="button" onclick="confirmRoleChange(<?= (int) $u['id'] ?>, '<?= esc($u['name'], 'js') ?>', '<?= $u['role'] === 'admin' ? 'user' : 'admin' ?>')" class="p-1.5 <?= $u['role'] === 'admin' ? 'text-amber-400 hover:text-amber-200 hover:bg-amber-500/20' : 'text-purple-400 hover:text-purple-200 hover:bg-purple-500/20' ?> rounded-lg transition-colors cursor-pointer" title="<?= $u['role'] === 'admin' ? 'Cabut hak Admin (Jadikan Pengguna Biasa)' : 'Beri hak Admin (Jadikan Administrator)' ?>" aria-label="<?= $u['role'] === 'admin' ? 'Jadikan User' : 'Jadikan Admin' ?>">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                                                    </button>

                                                    <!-- Toggle Status -->
                                                    <button type="button" onclick="handleUserAction(<?= (int) $u['id'] ?>, 'set_status', {status: '<?= $u['status'] === 'active' ? 'inactive' : 'active' ?>'})" class="p-1.5 <?= $u['status'] === 'active' ? 'text-rose-400 hover:text-rose-200 hover:bg-rose-500/20' : 'text-emerald-400 hover:text-emerald-200 hover:bg-emerald-500/20' ?> rounded-lg transition-colors cursor-pointer" title="<?= $u['status'] === 'active' ? 'Bekukan / Suspend Akun Pengguna' : 'Aktifkan Kembali Akun Pengguna' ?>" aria-label="<?= $u['status'] === 'active' ? 'Suspend Akun' : 'Aktifkan Akun' ?>">
                                                        <?php if ($u['status'] === 'active'): ?>
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path></svg>
                                                        <?php else: ?>
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                                        <?php endif; ?>
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

        <!-- ========================================== -->
        <!-- TAB 3: DOKUMEN & ANTREAN                   -->
        <!-- ========================================== -->
        <?php elseif ($activeTab === 'documents'): ?>
            <?php
            // Helper URL builder untuk filter & paginasi dokumen
            $docFilterUrl = function(array $changes = []) use ($docFilter) {
                $merged = array_merge([
                    'tab'        => 'documents',
                    'doc_q'      => $docFilter['q'] ?? '',
                    'doc_type'   => $docFilter['type'] ?? 'all',
                    'doc_status' => $docFilter['status'] ?? 'all',
                    'doc_score'  => $docFilter['score'] ?? 'all',
                    'doc_date'   => $docFilter['date'] ?? 'all',
                    'doc_sort'   => $docFilter['sort'] ?? 'newest',
                    'doc_limit'  => $docFilter['limit'] ?? 50,
                    'doc_page'   => 1,
                ], $changes);

                $clean = ['tab' => 'documents'];
                if (!empty($merged['doc_q'])) $clean['doc_q'] = $merged['doc_q'];
                if (!empty($merged['doc_type']) && $merged['doc_type'] !== 'all') $clean['doc_type'] = $merged['doc_type'];
                if (!empty($merged['doc_status']) && $merged['doc_status'] !== 'all') $clean['doc_status'] = $merged['doc_status'];
                if (!empty($merged['doc_score']) && $merged['doc_score'] !== 'all') $clean['doc_score'] = $merged['doc_score'];
                if (!empty($merged['doc_date']) && $merged['doc_date'] !== 'all') $clean['doc_date'] = $merged['doc_date'];
                if (!empty($merged['doc_sort']) && $merged['doc_sort'] !== 'newest') $clean['doc_sort'] = $merged['doc_sort'];
                if (!empty($merged['doc_limit']) && (int)$merged['doc_limit'] !== 50) $clean['doc_limit'] = (int)$merged['doc_limit'];
                if (!empty($merged['doc_page']) && (int)$merged['doc_page'] > 1) $clean['doc_page'] = (int)$merged['doc_page'];

                return base_url('admin?' . http_build_query($clean));
            };
            ?>
            <div class="space-y-6">
                <!-- Judul Tab & Ringkasan Metrik Singkat -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div>
                        <div class="flex items-center gap-2.5">
                            <h2 class="text-lg font-bold text-white">Monitoring Antrean &amp; Audit Dokumen</h2>
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-mono font-semibold bg-indigo-500/20 text-indigo-300 border border-indigo-500/30">
                                <?= number_format($docFilter['total_filtered']) ?> Naskah
                            </span>
                        </div>
                        <p class="text-xs text-slate-400 mt-0.5">Seluruh dokumen yang diuji kemiripan dan status eksekusi worker analisis</p>
                    </div>

                    <?php if ($docFilter['is_active']): ?>
                        <div class="flex items-center gap-2">
                            <span class="text-xs text-slate-400 font-medium">
                                <?= $docFilter['active_count'] ?> Filter Diterapkan
                            </span>
                            <a href="<?= base_url('admin?tab=documents') ?>" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-semibold text-rose-300 hover:text-rose-200 bg-rose-500/10 hover:bg-rose-500/20 border border-rose-500/30 transition-colors">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                                <span>Reset Filter</span>
                            </a>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Panel Penyaringan dan Pencarian Dokumen (Filter & Search Panel) -->
                <div class="bg-slate-800/50 border border-slate-700/70 rounded-2xl p-4 sm:p-5 shadow-sm space-y-4">
                    <!-- Baris Header Panel & Status Hasil -->
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-slate-700/60">
                        <div class="flex items-center gap-2.5">
                            <div class="p-2 rounded-xl bg-indigo-500/20 text-indigo-400 border border-indigo-500/30 shrink-0">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                            </div>
                            <div>
                                <h3 class="text-sm font-bold text-white leading-tight">Panel Penyaringan &amp; Pencarian</h3>
                                <p class="text-[11px] text-slate-400">Temukan dokumen berdasarkan kata kunci, tipe naskah, status worker, skor kemiripan, dan waktu</p>
                            </div>
                        </div>

                        <!-- Status Ringkasan Hasil -->
                        <div class="flex items-center gap-2 text-xs text-slate-300">
                            <span class="px-2.5 py-1 rounded-lg bg-slate-900/90 border border-slate-700/80 font-mono-code text-[11px]">
                                Menampilkan <strong class="text-indigo-300"><?= number_format($docFilter['start_item']) ?>-<?= number_format($docFilter['end_item']) ?></strong> dari <strong class="text-white"><?= number_format($docFilter['total_filtered']) ?></strong> data
                            </span>
                        </div>
                    </div>

                    <!-- Shortcut Tombol Saring Cepat (Quick Filter Pills) -->
                    <div class="flex items-center gap-1.5 overflow-x-auto pb-1 admin-sidebar-scroll text-xs">
                        <!-- 1. Semua -->
                        <?php 
                        $isAllActive = ($docFilter['type'] === 'all' && $docFilter['status'] === 'all' && $docFilter['score'] === 'all' && empty($docFilter['q']) && $docFilter['date'] === 'all');
                        ?>
                        <a href="<?= $docFilterUrl(['doc_type' => 'all', 'doc_status' => 'all', 'doc_score' => 'all', 'doc_date' => 'all', 'doc_q' => '']) ?>" 
                           class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl font-medium transition-all whitespace-nowrap shrink-0 <?= $isAllActive ? 'bg-indigo-600 text-white shadow-xs' : 'bg-slate-900/70 hover:bg-slate-900 text-slate-300 border border-slate-700/80' ?>">
                            <span>Semua Naskah</span>
                            <span class="px-1.5 py-0.2 rounded-md text-[10px] <?= $isAllActive ? 'bg-white/20 text-white' : 'bg-slate-800 text-slate-400' ?>"><?= number_format($metrics['total_submissions'] + $metrics['total_corpus']) ?></span>
                        </a>

                        <!-- 2. Submisi -->
                        <?php $isSubActive = ($docFilter['type'] === 'submission' && $docFilter['status'] === 'all' && $docFilter['score'] === 'all'); ?>
                        <a href="<?= $docFilterUrl(['doc_type' => 'submission', 'doc_status' => 'all', 'doc_score' => 'all']) ?>" 
                           class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl font-medium transition-all whitespace-nowrap shrink-0 <?= $isSubActive ? 'bg-sky-600 text-white shadow-xs' : 'bg-slate-900/70 hover:bg-slate-900 text-slate-300 border border-slate-700/80' ?>">
                            <span>Naskah Submisi</span>
                            <span class="px-1.5 py-0.2 rounded-md text-[10px] <?= $isSubActive ? 'bg-white/20 text-white' : 'bg-sky-500/20 text-sky-300' ?>"><?= number_format($metrics['total_submissions']) ?></span>
                        </a>

                        <!-- 3. Korpus -->
                        <?php $isCorpusActive = ($docFilter['type'] === 'corpus' && $docFilter['status'] === 'all' && $docFilter['score'] === 'all'); ?>
                        <a href="<?= $docFilterUrl(['doc_type' => 'corpus', 'doc_status' => 'all', 'doc_score' => 'all']) ?>" 
                           class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl font-medium transition-all whitespace-nowrap shrink-0 <?= $isCorpusActive ? 'bg-purple-600 text-white shadow-xs' : 'bg-slate-900/70 hover:bg-slate-900 text-slate-300 border border-slate-700/80' ?>">
                            <span>Korpus Acuan</span>
                            <span class="px-1.5 py-0.2 rounded-md text-[10px] <?= $isCorpusActive ? 'bg-white/20 text-white' : 'bg-purple-500/20 text-purple-300' ?>"><?= number_format($metrics['total_corpus']) ?></span>
                        </a>

                        <!-- 4. Antre / Proses -->
                        <a href="<?= $docFilterUrl(['doc_status' => 'uploaded']) ?>" 
                           class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl font-medium transition-all whitespace-nowrap shrink-0 <?= ($docFilter['status'] === 'uploaded') ? 'bg-amber-600 text-white shadow-xs' : 'bg-slate-900/70 hover:bg-slate-900 text-slate-300 border border-slate-700/80' ?>">
                            <span class="w-1.5 h-1.5 rounded-full bg-amber-400 animate-pulse"></span>
                            <span>Antre</span>
                            <span class="px-1.5 py-0.2 rounded-md text-[10px] bg-amber-500/20 text-amber-300"><?= number_format($metrics['queue_uploaded']) ?></span>
                        </a>

                        <a href="<?= $docFilterUrl(['doc_status' => 'processing']) ?>" 
                           class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl font-medium transition-all whitespace-nowrap shrink-0 <?= ($docFilter['status'] === 'processing') ? 'bg-sky-600 text-white shadow-xs' : 'bg-slate-900/70 hover:bg-slate-900 text-slate-300 border border-slate-700/80' ?>">
                            <span class="w-1.5 h-1.5 rounded-full bg-sky-400 animate-ping"></span>
                            <span>Sedang Proses</span>
                            <span class="px-1.5 py-0.2 rounded-md text-[10px] bg-sky-500/20 text-sky-300"><?= number_format($metrics['queue_processing']) ?></span>
                        </a>

                        <!-- 5. Selesai -->
                        <?php $isCompletedActive = ($docFilter['status'] === 'completed'); ?>
                        <a href="<?= $docFilterUrl(['doc_status' => 'completed']) ?>" 
                           class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl font-medium transition-all whitespace-nowrap shrink-0 <?= $isCompletedActive ? 'bg-emerald-600 text-white shadow-xs' : 'bg-slate-900/70 hover:bg-slate-900 text-slate-300 border border-slate-700/80' ?>">
                            <span>Selesai</span>
                            <span class="px-1.5 py-0.2 rounded-md text-[10px] <?= $isCompletedActive ? 'bg-white/20 text-white' : 'bg-emerald-500/20 text-emerald-300' ?>"><?= number_format($metrics['queue_completed']) ?></span>
                        </a>

                        <!-- 6. Skor Tinggi (>= 25%) -->
                        <?php $isHighActive = ($docFilter['score'] === 'high'); ?>
                        <a href="<?= $docFilterUrl(['doc_score' => 'high']) ?>" 
                           class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl font-medium transition-all whitespace-nowrap shrink-0 <?= $isHighActive ? 'bg-rose-600 text-white shadow-xs' : 'bg-slate-900/70 hover:bg-slate-900 text-slate-300 border border-slate-700/80' ?>">
                            <span>Kemiripan Tinggi (≥25%)</span>
                            <span class="px-1.5 py-0.2 rounded-md text-[10px] bg-rose-500/20 text-rose-300">Waspada</span>
                        </a>

                        <!-- 7. Gagal -->
                        <?php if (!empty($metrics['queue_failed'])): ?>
                            <?php $isFailedActive = ($docFilter['status'] === 'failed'); ?>
                            <a href="<?= $docFilterUrl(['doc_status' => 'failed']) ?>" 
                               class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl font-medium transition-all whitespace-nowrap shrink-0 <?= $isFailedActive ? 'bg-rose-600 text-white shadow-xs' : 'bg-slate-900/70 hover:bg-slate-900 text-slate-300 border border-slate-700/80' ?>">
                                <span>Gagal</span>
                                <span class="px-1.5 py-0.2 rounded-md text-[10px] bg-rose-500/20 text-rose-300"><?= number_format($metrics['queue_failed']) ?></span>
                            </a>
                        <?php endif; ?>
                    </div>

                    <!-- Formulir Penyaringan Lengkap (Form Search & Dropdown Filters) -->
                    <form action="<?= base_url('admin') ?>" method="get" id="docFilterPanelForm" class="space-y-3.5">
                        <input type="hidden" name="tab" value="documents">

                        <!-- Baris Input Pencarian & Tombol Aksi -->
                        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2.5">
                            <div class="relative flex-1">
                                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                                </div>
                                <input type="text" 
                                       id="docSearchKeywordInput"
                                       name="doc_q" 
                                       value="<?= esc($docFilter['q'] ?? '') ?>" 
                                       placeholder="Cari judul naskah, nama berkas, nama pengunggah, email, fakultas, prodi..." 
                                       class="w-full pl-10 pr-9 py-2.5 bg-slate-900/90 border border-slate-700/80 rounded-xl text-xs sm:text-sm text-slate-100 placeholder-slate-500 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-colors">
                                <?php if (!empty($docFilter['q'])): ?>
                                    <a href="<?= $docFilterUrl(['doc_q' => '']) ?>" 
                                       class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-500 hover:text-slate-300 transition-colors" 
                                       title="Hapus kata kunci pencarian">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                    </a>
                                <?php endif; ?>
                            </div>

                            <div class="flex items-center gap-2 shrink-0">
                                <button type="submit" class="flex-1 sm:flex-initial inline-flex items-center justify-center gap-1.5 px-4 py-2.5 bg-indigo-600 hover:bg-indigo-500 active:bg-indigo-700 text-white rounded-xl text-xs font-semibold shadow-sm transition-all active:scale-95 cursor-pointer">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                                    <span>Terapkan Filter</span>
                                </button>
                                <?php if ($docFilter['is_active']): ?>
                                    <a href="<?= base_url('admin?tab=documents') ?>" class="flex-1 sm:flex-initial inline-flex items-center justify-center gap-1.5 px-3.5 py-2.5 bg-rose-500/10 hover:bg-rose-500/20 text-rose-300 border border-rose-500/30 rounded-xl text-xs font-semibold transition-all cursor-pointer" title="Kembalikan semua filter ke kondisi awal">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                                        <span>Reset</span>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Baris Grid Filter Dropdown 6 Kolom -->
                        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-2.5 pt-1">
                            <!-- 1. Tipe Dokumen -->
                            <div>
                                <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">Tipe Naskah</label>
                                <select name="doc_type" onchange="this.form.submit()" class="w-full bg-slate-900/90 border border-slate-700/80 rounded-lg px-2.5 py-1.5 text-xs text-slate-200 focus:outline-none focus:border-indigo-500 cursor-pointer">
                                    <option value="all" <?= ($docFilter['type'] === 'all') ? 'selected' : '' ?>>Semua Tipe</option>
                                    <option value="submission" <?= ($docFilter['type'] === 'submission') ? 'selected' : '' ?>>Submisi (Uji)</option>
                                    <option value="corpus" <?= ($docFilter['type'] === 'corpus') ? 'selected' : '' ?>>Korpus Acuan</option>
                                </select>
                            </div>

                            <!-- 2. Status Worker -->
                            <div>
                                <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">Status Worker</label>
                                <select name="doc_status" onchange="this.form.submit()" class="w-full bg-slate-900/90 border border-slate-700/80 rounded-lg px-2.5 py-1.5 text-xs text-slate-200 focus:outline-none focus:border-indigo-500 cursor-pointer">
                                    <option value="all" <?= ($docFilter['status'] === 'all') ? 'selected' : '' ?>>Semua Status</option>
                                    <option value="uploaded" <?= ($docFilter['status'] === 'uploaded') ? 'selected' : '' ?>>Antre (Uploaded)</option>
                                    <option value="processing" <?= ($docFilter['status'] === 'processing') ? 'selected' : '' ?>>Sedang Proses</option>
                                    <option value="completed" <?= ($docFilter['status'] === 'completed') ? 'selected' : '' ?>>Selesai</option>
                                    <option value="failed" <?= ($docFilter['status'] === 'failed') ? 'selected' : '' ?>>Gagal Eksekusi</option>
                                </select>
                            </div>

                            <!-- 3. Skor Kemiripan -->
                            <div>
                                <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">Skor Kemiripan</label>
                                <select name="doc_score" onchange="this.form.submit()" class="w-full bg-slate-900/90 border border-slate-700/80 rounded-lg px-2.5 py-1.5 text-xs text-slate-200 focus:outline-none focus:border-indigo-500 cursor-pointer">
                                    <option value="all" <?= ($docFilter['score'] === 'all') ? 'selected' : '' ?>>Semua Skor</option>
                                    <option value="clean" <?= ($docFilter['score'] === 'clean') ? 'selected' : '' ?>>Bersih (&lt; 15%)</option>
                                    <option value="moderate" <?= ($docFilter['score'] === 'moderate') ? 'selected' : '' ?>>Sedang (15 - 24.9%)</option>
                                    <option value="high" <?= ($docFilter['score'] === 'high') ? 'selected' : '' ?>>Tinggi (≥ 25%)</option>
                                    <option value="corpus_ref" <?= ($docFilter['score'] === 'corpus_ref') ? 'selected' : '' ?>>Ref. Korpus</option>
                                </select>
                            </div>

                            <!-- 4. Periode Waktu -->
                            <div>
                                <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">Periode Waktu</label>
                                <select name="doc_date" onchange="this.form.submit()" class="w-full bg-slate-900/90 border border-slate-700/80 rounded-lg px-2.5 py-1.5 text-xs text-slate-200 focus:outline-none focus:border-indigo-500 cursor-pointer">
                                    <option value="all" <?= ($docFilter['date'] === 'all') ? 'selected' : '' ?>>Semua Waktu</option>
                                    <option value="today" <?= ($docFilter['date'] === 'today') ? 'selected' : '' ?>>Hari Ini</option>
                                    <option value="yesterday" <?= ($docFilter['date'] === 'yesterday') ? 'selected' : '' ?>>Kemarin</option>
                                    <option value="7days" <?= ($docFilter['date'] === '7days') ? 'selected' : '' ?>>7 Hari Terakhir</option>
                                    <option value="30days" <?= ($docFilter['date'] === '30days') ? 'selected' : '' ?>>30 Hari Terakhir</option>
                                    <option value="this_month" <?= ($docFilter['date'] === 'this_month') ? 'selected' : '' ?>>Bulan Ini</option>
                                </select>
                            </div>

                            <!-- 5. Urutan Data -->
                            <div>
                                <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">Urutan Data</label>
                                <select name="doc_sort" onchange="this.form.submit()" class="w-full bg-slate-900/90 border border-slate-700/80 rounded-lg px-2.5 py-1.5 text-xs text-slate-200 focus:outline-none focus:border-indigo-500 cursor-pointer">
                                    <option value="newest" <?= ($docFilter['sort'] === 'newest') ? 'selected' : '' ?>>Terbaru (Waktu ↓)</option>
                                    <option value="oldest" <?= ($docFilter['sort'] === 'oldest') ? 'selected' : '' ?>>Terlama (Waktu ↑)</option>
                                    <option value="score_desc" <?= ($docFilter['sort'] === 'score_desc') ? 'selected' : '' ?>>Skor Tertinggi ↓</option>
                                    <option value="score_asc" <?= ($docFilter['sort'] === 'score_asc') ? 'selected' : '' ?>>Skor Terendah ↑</option>
                                    <option value="title_asc" <?= ($docFilter['sort'] === 'title_asc') ? 'selected' : '' ?>>Judul (A - Z)</option>
                                    <option value="title_desc" <?= ($docFilter['sort'] === 'title_desc') ? 'selected' : '' ?>>Judul (Z - A)</option>
                                </select>
                            </div>

                            <!-- 6. Baris per Halaman -->
                            <div>
                                <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">Per Halaman</label>
                                <select name="doc_limit" onchange="this.form.submit()" class="w-full bg-slate-900/90 border border-slate-700/80 rounded-lg px-2.5 py-1.5 text-xs text-slate-200 focus:outline-none focus:border-indigo-500 cursor-pointer">
                                    <option value="25" <?= ($docFilter['limit'] == 25) ? 'selected' : '' ?>>25 Baris</option>
                                    <option value="50" <?= ($docFilter['limit'] == 50) ? 'selected' : '' ?>>50 Baris</option>
                                    <option value="100" <?= ($docFilter['limit'] == 100) ? 'selected' : '' ?>>100 Baris</option>
                                    <option value="200" <?= ($docFilter['limit'] == 200) ? 'selected' : '' ?>>200 Baris</option>
                                </select>
                            </div>
                        </div>
                    </form>

                    <!-- Baris Tag/Chip Filter Aktif -->
                    <?php if ($docFilter['is_active']): ?>
                        <div class="pt-3 border-t border-slate-700/60 flex items-center gap-1.5 flex-wrap text-xs">
                            <span class="text-[11px] text-slate-400 font-semibold mr-1">Filter Aktif:</span>

                            <?php if (!empty($docFilter['q'])): ?>
                                <a href="<?= $docFilterUrl(['doc_q' => '']) ?>" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-indigo-500/15 text-indigo-300 border border-indigo-500/30 hover:bg-indigo-500/25 transition-colors text-[11px]" title="Klik untuk menghapus filter ini">
                                    <span>Pencarian: "<strong><?= esc($docFilter['q']) ?></strong>"</span>
                                    <span class="text-indigo-400 font-bold hover:text-white">✕</span>
                                </a>
                            <?php endif; ?>

                            <?php if ($docFilter['type'] !== 'all'): ?>
                                <a href="<?= $docFilterUrl(['doc_type' => 'all']) ?>" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-sky-500/15 text-sky-300 border border-sky-500/30 hover:bg-sky-500/25 transition-colors text-[11px]" title="Klik untuk menghapus filter ini">
                                    <span>Tipe: <strong><?= $docFilter['type'] === 'submission' ? 'Submisi' : 'Korpus' ?></strong></span>
                                    <span class="text-sky-400 font-bold hover:text-white">✕</span>
                                </a>
                            <?php endif; ?>

                            <?php if ($docFilter['status'] !== 'all'): ?>
                                <?php
                                $statusNames = [
                                    'uploaded'   => 'Antre (Uploaded)',
                                    'processing' => 'Sedang Diproses',
                                    'completed'  => 'Selesai',
                                    'failed'     => 'Gagal',
                                ];
                                ?>
                                <a href="<?= $docFilterUrl(['doc_status' => 'all']) ?>" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-emerald-500/15 text-emerald-300 border border-emerald-500/30 hover:bg-emerald-500/25 transition-colors text-[11px]" title="Klik untuk menghapus filter ini">
                                    <span>Status: <strong><?= $statusNames[$docFilter['status']] ?? esc($docFilter['status']) ?></strong></span>
                                    <span class="text-emerald-400 font-bold hover:text-white">✕</span>
                                </a>
                            <?php endif; ?>

                            <?php if ($docFilter['score'] !== 'all'): ?>
                                <?php
                                $scoreNames = [
                                    'clean'      => 'Bersih (< 15%)',
                                    'moderate'   => 'Sedang (15 - 24.9%)',
                                    'high'       => 'Tinggi (≥ 25%)',
                                    'corpus_ref' => 'Ref. Korpus',
                                ];
                                ?>
                                <a href="<?= $docFilterUrl(['doc_score' => 'all']) ?>" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-amber-500/15 text-amber-300 border border-amber-500/30 hover:bg-amber-500/25 transition-colors text-[11px]" title="Klik untuk menghapus filter ini">
                                    <span>Skor: <strong><?= $scoreNames[$docFilter['score']] ?? esc($docFilter['score']) ?></strong></span>
                                    <span class="text-amber-400 font-bold hover:text-white">✕</span>
                                </a>
                            <?php endif; ?>

                            <?php if ($docFilter['date'] !== 'all'): ?>
                                <?php
                                $dateNames = [
                                    'today'      => 'Hari Ini',
                                    'yesterday'  => 'Kemarin',
                                    '7days'      => '7 Hari Terakhir',
                                    '30days'     => '30 Hari Terakhir',
                                    'this_month' => 'Bulan Ini',
                                ];
                                ?>
                                <a href="<?= $docFilterUrl(['doc_date' => 'all']) ?>" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-purple-500/15 text-purple-300 border border-purple-500/30 hover:bg-purple-500/25 transition-colors text-[11px]" title="Klik untuk menghapus filter ini">
                                    <span>Periode: <strong><?= $dateNames[$docFilter['date']] ?? esc($docFilter['date']) ?></strong></span>
                                    <span class="text-purple-400 font-bold hover:text-white">✕</span>
                                </a>
                            <?php endif; ?>

                            <?php if ($docFilter['sort'] !== 'newest'): ?>
                                <?php
                                $sortNames = [
                                    'oldest'     => 'Terlama',
                                    'score_desc' => 'Skor Tertinggi',
                                    'score_asc'  => 'Skor Terendah',
                                    'title_asc'  => 'Judul (A-Z)',
                                    'title_desc' => 'Judul (Z-A)',
                                ];
                                ?>
                                <a href="<?= $docFilterUrl(['doc_sort' => 'newest']) ?>" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-slate-700/60 text-slate-300 border border-slate-600/60 hover:bg-slate-700 transition-colors text-[11px]" title="Klik untuk mengembalikan urutan ke Terbaru">
                                    <span>Urutan: <strong><?= $sortNames[$docFilter['sort']] ?? esc($docFilter['sort']) ?></strong></span>
                                    <span class="text-slate-400 font-bold hover:text-white">✕</span>
                                </a>
                            <?php endif; ?>

                            <a href="<?= base_url('admin?tab=documents') ?>" class="text-[11px] text-rose-400 hover:text-rose-300 underline font-semibold ml-auto transition-colors">
                                Hapus Semua Filter
                            </a>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Mobile List: Tampilan Kartu Responsif & Ringkas Dokumen (Layar Ponsel) -->
                <div class="space-y-2.5 md:hidden">
                    <?php if (empty($documentsList)): ?>
                        <div class="bg-slate-800/40 border border-slate-700/60 rounded-2xl p-8 text-center space-y-3">
                            <div class="w-12 h-12 rounded-2xl bg-slate-900 border border-slate-700 flex items-center justify-center text-xl mx-auto text-slate-400">
                                🔍
                            </div>
                            <div>
                                <h4 class="text-sm font-semibold text-slate-200">Tidak ada dokumen ditemukan</h4>
                                <p class="text-xs text-slate-400 mt-1">Tidak ada dokumen yang cocok dengan kriteria pencarian atau penyaringan saat ini.</p>
                            </div>
                            <?php if ($docFilter['is_active']): ?>
                                <div>
                                    <a href="<?= base_url('admin?tab=documents') ?>" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-semibold text-rose-300 bg-rose-500/10 hover:bg-rose-500/20 border border-rose-500/30 transition-colors">
                                        ↺ Hapus Semua Filter
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <?php foreach ($documentsList as $d): ?>
                            <div id="doc-mobile-<?= $d['id'] ?>" class="bg-slate-800/60 hover:bg-slate-800/80 border border-slate-700/70 rounded-xl p-3 shadow-xs space-y-2.5 transition-colors">
                                <!-- Baris 1: Status Badges di Kiri, Button-Group Aksi di Kanan -->
                                <div class="flex items-center justify-between gap-2">
                                    <div class="flex items-center gap-1.5 flex-wrap min-w-0">
                                        <span class="px-1.5 py-0.5 rounded text-[9px] font-bold uppercase tracking-wider <?= $d['document_type'] === 'corpus' ? 'bg-purple-500/20 text-purple-300 border border-purple-500/30' : 'bg-sky-500/20 text-sky-300 border border-sky-500/30' ?>">
                                            <?= strtoupper($d['document_type']) ?>
                                        </span>
                                        <?php if ($d['status'] === 'completed'): ?>
                                            <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-full text-[9px] bg-emerald-500/15 text-emerald-300 border border-emerald-500/30 font-semibold">
                                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span> Selesai
                                            </span>
                                        <?php elseif ($d['status'] === 'processing'): ?>
                                            <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-full text-[9px] bg-sky-500/15 text-sky-300 border border-sky-500/30 font-semibold animate-pulse">
                                                <span class="w-1.5 h-1.5 rounded-full bg-sky-400 animate-ping"></span> Proses
                                            </span>
                                        <?php elseif ($d['status'] === 'failed'): ?>
                                            <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-full text-[9px] bg-rose-500/15 text-rose-300 border border-rose-500/30 font-semibold">
                                                <span class="w-1.5 h-1.5 rounded-full bg-rose-400"></span> Gagal
                                            </span>
                                        <?php else: ?>
                                            <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-full text-[9px] bg-amber-500/15 text-amber-300 border border-amber-500/30 font-semibold">
                                                <span class="w-1.5 h-1.5 rounded-full bg-amber-400"></span> Antre
                                            </span>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Button-Group Aksi Berbasis Ikon di Ponsel -->
                                    <div class="inline-flex items-center rounded-lg bg-slate-900/90 border border-slate-700/80 p-0.5 shadow-xs divide-x divide-slate-800 shrink-0">
                                        <?php if (isset($d['report']['id'])): ?>
                                            <a href="<?= base_url('report/' . $d['report']['id']) ?>" target="_blank" class="p-1.5 text-indigo-400 hover:text-indigo-200 hover:bg-indigo-500/20 rounded-md transition-colors cursor-pointer" title="Lihat Laporan Hasil Audit" aria-label="Lihat Laporan">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                            </a>
                                        <?php endif; ?>
                                        <button type="button" onclick="handleDocReprocess(<?= (int) $d['id'] ?>)" class="p-1.5 text-sky-400 hover:text-sky-200 hover:bg-sky-500/20 rounded-md transition-colors cursor-pointer" title="Proses Ulang Dokumen (Re-process)" aria-label="Proses Ulang">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                                        </button>
                                        <button type="button" onclick="confirmDeleteDocument(<?= (int) $d['id'] ?>, '<?= esc($d['title'], 'js') ?>', 'doc-mobile-<?= $d['id'] ?>')" class="p-1.5 text-rose-400 hover:text-rose-200 hover:bg-rose-500/20 rounded-md transition-colors cursor-pointer" title="Musnahkan / Hapus Dokumen" aria-label="Hapus Dokumen">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                        </button>
                                    </div>
                                </div>

                                <!-- Baris 2: Judul Naskah & Nama Berkas -->
                                <div class="space-y-0.5">
                                    <div class="font-bold text-slate-100 text-xs sm:text-sm leading-snug line-clamp-2" title="<?= esc($d['title']) ?>">
                                        <?= esc($d['title']) ?>
                                    </div>
                                    <div class="text-[10px] text-slate-400 font-mono-code truncate">
                                        <?= esc($d['original_filename']) ?>
                                    </div>
                                </div>

                                <!-- Tag Fakultas & Prodi (jika ada) -->
                                <?php if (!empty($d['faculty']) || !empty($d['department'])): ?>
                                    <div class="flex items-center gap-1.5 flex-wrap">
                                        <?php if (!empty($d['faculty'])): ?>
                                            <span class="inline-flex items-center text-[9px] font-medium px-1.5 py-0.5 rounded bg-slate-900/90 text-slate-300 border border-slate-700/80 truncate max-w-[140px]" title="<?= esc($d['faculty']) ?>">🏛️ <?= esc($d['faculty']) ?></span>
                                        <?php endif; ?>
                                        <?php if (!empty($d['department'])): ?>
                                            <span class="inline-flex items-center text-[9px] font-medium px-1.5 py-0.5 rounded bg-indigo-950/60 text-indigo-300 border border-indigo-700/50 truncate max-w-[140px]" title="<?= esc($d['department']) ?>">🎓 <?= esc($d['department']) ?></span>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>

                                <!-- Baris 3: Info Pengunggah & Waktu di Kiri, Skor Kemiripan di Kanan -->
                                <div class="pt-2 border-t border-slate-700/50 flex items-center justify-between text-[11px] gap-2">
                                    <div class="min-w-0 flex-1">
                                        <div class="text-slate-300 font-medium truncate text-xs"><?= esc($d['user_name'] ?? 'Tamu / Anonim') ?></div>
                                        <div class="text-[10px] text-slate-500 font-mono"><?= date('d M Y, H:i', strtotime($d['created_at'])) ?></div>
                                    </div>
                                    <div class="text-right shrink-0">
                                        <?php if (isset($d['report']['overall_similarity'])): ?>
                                            <div class="font-bold font-mono text-xs leading-tight <?= $d['report']['overall_similarity'] >= 25 ? 'text-rose-400' : 'text-emerald-400' ?>">
                                                <?= number_format($d['report']['overall_similarity'], 1) ?>%
                                            </div>
                                            <div class="text-[9px] text-slate-500 font-mono">
                                                Eksak: <?= number_format($d['report']['exact_similarity'] ?? 0, 1) ?>%
                                            </div>
                                        <?php elseif (($d['document_type'] ?? '') === 'corpus'): ?>
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-purple-500/15 text-purple-300 border border-purple-500/30" title="Naskah acuan korpus pembanding">Ref. Korpus</span>
                                        <?php else: ?>
                                            <span class="text-slate-500 text-xs font-mono">-</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Desktop Table View: Terlihat di Layar md ke atas -->
                <div class="hidden md:block bg-slate-800/40 border border-slate-700/60 rounded-2xl overflow-hidden shadow-sm">
                    <div class="overflow-x-auto">
                        <table class="w-full text-xs text-left">
                            <thead class="bg-slate-900/80 text-slate-400 uppercase tracking-wider text-[10px] border-b border-slate-700/60">
                                <tr>
                                    <th class="py-3 px-4">
                                        <a href="<?= $docFilterUrl(['doc_sort' => ($docFilter['sort'] === 'title_asc' ? 'title_desc' : 'title_asc')]) ?>" class="hover:text-white inline-flex items-center gap-1 transition-colors" title="Klik untuk mengurutkan berdasarkan Judul Dokumen">
                                            <span>Judul &amp; File</span>
                                            <span class="text-[9px] text-indigo-400"><?= $docFilter['sort'] === 'title_asc' ? '▲' : ($docFilter['sort'] === 'title_desc' ? '▼' : '↕') ?></span>
                                        </a>
                                    </th>
                                    <th class="py-3 px-3">Pengunggah</th>
                                    <th class="py-3 px-3">Tipe</th>
                                    <th class="py-3 px-3">Status Worker</th>
                                    <th class="py-3 px-3 text-center">
                                        <a href="<?= $docFilterUrl(['doc_sort' => ($docFilter['sort'] === 'score_desc' ? 'score_asc' : 'score_desc')]) ?>" class="hover:text-white inline-flex items-center justify-center gap-1 transition-colors" title="Klik untuk mengurutkan berdasarkan Skor Kemiripan">
                                            <span>Skor Kemiripan</span>
                                            <span class="text-[9px] text-indigo-400"><?= $docFilter['sort'] === 'score_desc' ? '▼' : ($docFilter['sort'] === 'score_asc' ? '▲' : '↕') ?></span>
                                        </a>
                                    </th>
                                    <th class="py-3 px-3">
                                        <a href="<?= $docFilterUrl(['doc_sort' => ($docFilter['sort'] === 'newest' ? 'oldest' : 'newest')]) ?>" class="hover:text-white inline-flex items-center gap-1 transition-colors" title="Klik untuk mengurutkan berdasarkan Waktu Masuk">
                                            <span>Waktu Masuk</span>
                                            <span class="text-[9px] text-indigo-400"><?= $docFilter['sort'] === 'newest' ? '▼' : ($docFilter['sort'] === 'oldest' ? '▲' : '↕') ?></span>
                                        </a>
                                    </th>
                                    <th class="py-3 px-4 text-right w-28">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-800/80">
                                <?php if (empty($documentsList)): ?>
                                    <tr>
                                        <td colspan="7" class="py-10 text-center">
                                            <div class="space-y-2">
                                                <div class="text-2xl text-slate-500">🔍</div>
                                                <div class="text-slate-300 font-medium text-xs">Tidak ada dokumen yang cocok dengan kriteria penyaringan.</div>
                                                <?php if ($docFilter['is_active']): ?>
                                                    <div class="pt-1">
                                                        <a href="<?= base_url('admin?tab=documents') ?>" class="inline-flex items-center gap-1 text-[11px] text-indigo-400 hover:text-indigo-300 underline font-semibold">
                                                            Hapus Semua Filter &amp; Tampilkan Seluruh Data
                                                        </a>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($documentsList as $d): ?>
                                        <tr id="doc-row-<?= $d['id'] ?>" class="hover:bg-slate-800/50 transition-colors">
                                            <td class="py-3 px-4">
                                                <div class="font-bold text-slate-200"><?= esc($d['title']) ?></div>
                                                <div class="text-[11px] text-slate-400 font-mono-code"><?= esc($d['original_filename']) ?></div>
                                                <?php if (!empty($d['faculty']) || !empty($d['department'])): ?>
                                                    <div class="mt-1 flex items-center gap-1.5 flex-wrap">
                                                        <?php if (!empty($d['faculty'])): ?>
                                                            <span class="inline-flex items-center text-[10px] font-medium px-1.5 py-0.5 rounded bg-slate-800 text-slate-300 border border-slate-700" title="Fakultas">🏛️ <?= esc($d['faculty']) ?></span>
                                                        <?php endif; ?>
                                                        <?php if (!empty($d['department'])): ?>
                                                            <span class="inline-flex items-center text-[10px] font-medium px-1.5 py-0.5 rounded bg-indigo-900/40 text-indigo-300 border border-indigo-700/50" title="Program Studi">🎓 <?= esc($d['department']) ?></span>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td class="py-3 px-3 text-slate-300">
                                                <div class="font-semibold"><?= esc($d['user_name'] ?? 'Tamu / Anonim') ?></div>
                                                <div class="text-[10px] text-slate-500"><?= esc($d['user_email'] ?? '-') ?></div>
                                            </td>
                                            <td class="py-3 px-3">
                                                <span class="px-2 py-0.5 rounded text-[10px] font-semibold <?= $d['document_type'] === 'corpus' ? 'bg-purple-500/20 text-purple-300 border border-purple-500/30' : 'bg-sky-500/20 text-sky-300 border border-sky-500/30' ?>">
                                                    <?= strtoupper($d['document_type']) ?>
                                                </span>
                                            </td>
                                            <td class="py-3 px-3">
                                                <?php if ($d['status'] === 'completed'): ?>
                                                    <span class="px-2 py-0.5 rounded-full text-[10px] bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 font-semibold">Completed</span>
                                                <?php elseif ($d['status'] === 'processing'): ?>
                                                    <span class="px-2 py-0.5 rounded-full text-[10px] bg-sky-500/10 text-sky-400 border border-sky-500/20 font-semibold animate-pulse">Processing</span>
                                                <?php elseif ($d['status'] === 'failed'): ?>
                                                    <span class="px-2 py-0.5 rounded-full text-[10px] bg-rose-500/10 text-rose-400 border border-rose-500/20 font-semibold">Failed</span>
                                                <?php else: ?>
                                                    <span class="px-2 py-0.5 rounded-full text-[10px] bg-amber-500/10 text-amber-400 border border-amber-500/20 font-semibold">Uploaded</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="py-3 px-3 text-center">
                                                <?php if (isset($d['report']['overall_similarity'])): ?>
                                                    <div class="font-bold <?= $d['report']['overall_similarity'] >= 25 ? 'text-rose-400' : 'text-emerald-400' ?>">
                                                        <?= number_format($d['report']['overall_similarity'], 1) ?>%
                                                    </div>
                                                    <div class="text-[10px] text-slate-500">
                                                        Eksak: <?= number_format($d['report']['exact_similarity'] ?? 0, 1) ?>% | Kosinus: <?= number_format($d['report']['cosine_similarity'] ?? 0, 1) ?>%
                                                    </div>
                                                <?php elseif (($d['document_type'] ?? '') === 'corpus'): ?>
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold bg-purple-500/15 text-purple-300 border border-purple-500/30" title="Dokumen ini adalah data acuan/pembanding dalam repositori, bukan karya yang diuji">Ref. Korpus</span>
                                                <?php else: ?>
                                                    <span class="text-slate-500">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="py-3 px-3 text-slate-400 text-[11px] whitespace-nowrap">
                                                <?= date('d M Y, H:i', strtotime($d['created_at'])) ?>
                                            </td>
                                            <td class="py-3 px-4 text-right whitespace-nowrap">
                                                <div class="inline-flex items-center rounded-xl bg-slate-900/90 border border-slate-700/70 p-0.5 shadow-xs divide-x divide-slate-800">
                                                    <?php if (isset($d['report']['id'])): ?>
                                                        <a href="<?= base_url('report/' . $d['report']['id']) ?>" target="_blank" class="p-1.5 text-indigo-400 hover:text-indigo-200 hover:bg-indigo-500/20 rounded-lg transition-colors cursor-pointer" title="Lihat Laporan Hasil Audit" aria-label="Lihat Laporan">
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                                        </a>
                                                    <?php endif; ?>
                                                    <button type="button" onclick="handleDocReprocess(<?= (int) $d['id'] ?>)" class="p-1.5 text-sky-400 hover:text-sky-200 hover:bg-sky-500/20 rounded-lg transition-colors cursor-pointer" title="Jalankan ulang analisis dokumen ini (Re-process)" aria-label="Jalankan Ulang">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                                                    </button>
                                                    <button type="button" onclick="confirmDeleteDocument(<?= (int) $d['id'] ?>, '<?= esc($d['title'], 'js') ?>', 'doc-row-<?= $d['id'] ?>')" class="p-1.5 text-rose-400 hover:text-rose-200 hover:bg-rose-500/20 rounded-lg transition-colors cursor-pointer" title="Musnahkan / Hapus dokumen dari sistem" aria-label="Hapus Dokumen">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
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

                <!-- Baris Paginasi & Kontrol Halaman Bawah -->
                <div class="flex flex-col sm:flex-row items-center justify-between gap-3 bg-slate-800/40 border border-slate-700/60 rounded-2xl p-4 shadow-sm">
                    <div class="text-xs text-slate-400">
                        Menampilkan <strong class="text-slate-200"><?= number_format($docFilter['start_item']) ?></strong> - <strong class="text-slate-200"><?= number_format($docFilter['end_item']) ?></strong> dari <strong class="text-white"><?= number_format($docFilter['total_filtered']) ?></strong> dokumen
                        <?php if ($docFilter['is_active']): ?>
                            <span class="text-indigo-400 font-medium">(Hasil Difilter)</span>
                        <?php endif; ?>
                    </div>

                    <?php if ($docFilter['total_pages'] > 1): ?>
                        <div class="flex items-center gap-1.5 flex-wrap justify-center">
                            <!-- Tombol Halaman Pertama -->
                            <?php if ($docFilter['page'] > 1): ?>
                                <a href="<?= $docFilterUrl(['doc_page' => 1]) ?>" class="p-1.5 rounded-lg bg-slate-900 border border-slate-700 text-slate-300 hover:text-white hover:bg-slate-800 transition-colors" title="Halaman Pertama">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"/></svg>
                                </a>
                                <a href="<?= $docFilterUrl(['doc_page' => $docFilter['page'] - 1]) ?>" class="px-2.5 py-1.5 rounded-lg bg-slate-900 border border-slate-700 text-xs text-slate-300 hover:text-white hover:bg-slate-800 transition-colors">
                                    Sebelumnya
                                </a>
                            <?php else: ?>
                                <span class="px-2.5 py-1.5 rounded-lg bg-slate-900/40 border border-slate-800/80 text-xs text-slate-600 cursor-not-allowed">
                                    Sebelumnya
                                </span>
                            <?php endif; ?>

                            <!-- Halaman Berurutan -->
                            <?php
                            $startPage = max(1, $docFilter['page'] - 2);
                            $endPage   = min($docFilter['total_pages'], $startPage + 4);
                            if ($endPage - $startPage < 4) {
                                $startPage = max(1, $endPage - 4);
                            }
                            ?>
                            <?php for ($p = $startPage; $p <= $endPage; $p++): ?>
                                <?php if ($p === $docFilter['page']): ?>
                                    <span class="px-3 py-1 rounded-lg bg-indigo-600 text-white font-bold text-xs shadow-xs">
                                        <?= $p ?>
                                    </span>
                                <?php else: ?>
                                    <a href="<?= $docFilterUrl(['doc_page' => $p]) ?>" class="px-3 py-1 rounded-lg bg-slate-900 border border-slate-700 text-xs text-slate-300 hover:text-white hover:bg-slate-800 transition-colors">
                                        <?= $p ?>
                                    </a>
                                <?php endif; ?>
                            <?php endfor; ?>

                            <!-- Tombol Halaman Berikutnya -->
                            <?php if ($docFilter['page'] < $docFilter['total_pages']): ?>
                                <a href="<?= $docFilterUrl(['doc_page' => $docFilter['page'] + 1]) ?>" class="px-2.5 py-1.5 rounded-lg bg-slate-900 border border-slate-700 text-xs text-slate-300 hover:text-white hover:bg-slate-800 transition-colors">
                                    Berikutnya
                                </a>
                                <a href="<?= $docFilterUrl(['doc_page' => $docFilter['total_pages']]) ?>" class="p-1.5 rounded-lg bg-slate-900 border border-slate-700 text-slate-300 hover:text-white hover:bg-slate-800 transition-colors" title="Halaman Terakhir">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7"/></svg>
                                </a>
                            <?php else: ?>
                                <span class="px-2.5 py-1.5 rounded-lg bg-slate-900/40 border border-slate-800/80 text-xs text-slate-600 cursor-not-allowed">
                                    Berikutnya
                                </span>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        <!-- ========================================== -->
        <!-- TAB 4: KORPUS REPOSITORI                   -->
        <!-- ========================================== -->
        <?php elseif ($activeTab === 'corpus'): ?>
            <div class="space-y-6">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div>
                        <div class="flex items-center gap-2">
                            <h2 class="text-lg font-bold text-white">Manajemen Korpus & Repositori Ilmiah</h2>
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-mono font-semibold bg-purple-500/20 text-purple-300 border border-purple-500/30">
                                <?= number_format($metrics['total_corpus']) ?> Naskah Total
                            </span>
                        </div>
                        <p class="text-xs text-slate-400 mt-0.5">
                            Basis data naskah pembanding algoritma pencocokan (<?= number_format($metrics['corpus_completed'] ?? $metrics['total_corpus']) ?> aktif terindeks<?php if (!empty($metrics['corpus_failed'])): ?>, <span class="text-rose-400"><?= $metrics['corpus_failed'] ?> gagal panen</span><?php endif; ?>).
                            <span class="text-slate-500">Catatan: Laporan audit menyimpan snapshot jumlah korpus saat pengujian dijalankan (mis. 864 dokumen pada naskah lampau).</span>
                        </p>
                    </div>
                </div>

                <!-- Transparansi Metrik Korpus: Live Repositori vs Snapshot Laporan Audit -->
                <div class="bg-indigo-950/40 border border-indigo-500/40 rounded-2xl p-4.5 shadow-sm">
                    <div class="flex items-start gap-3.5">
                        <div class="p-2.5 rounded-xl bg-indigo-500/20 text-indigo-400 border border-indigo-500/30 shrink-0 mt-0.5">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <div class="space-y-1.5">
                            <div class="flex items-center gap-2 flex-wrap">
                                <h3 class="text-xs font-bold text-indigo-200">
                                    Transparansi Metrik: Basis Live Repositori (<?= number_format($metrics['total_corpus']) ?> Naskah) vs Snapshot Laporan Audit
                                </h3>
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 font-mono">Prinsip Integritas Audit</span>
                            </div>
                            <p class="text-[11px] text-slate-300 leading-relaxed">
                                <strong>Mengapa angka korpus berbeda?</strong> Sidebar dan tab ini menampilkan <strong><?= number_format($metrics['total_corpus']) ?> naskah</strong> (<?= number_format($metrics['corpus_completed'] ?? $metrics['total_corpus']) ?> aktif terindeks<?php if (!empty($metrics['corpus_failed'])): ?>, <?= $metrics['corpus_failed'] ?> gagal ekstrak<?php endif; ?>) yang merupakan ukuran repositori <em>saat ini (live)</em>. Sementara itu, setiap dokumen laporan hasil uji (misal: Report #1170 yang mencatat <strong>864 Dokumen</strong>) mengunci <em>snapshot permanen</em> jumlah korpus tepat saat pengujian naskah tersebut dijalankan.
                            </p>
                            <p class="text-[11px] text-slate-400 leading-relaxed">
                                Selisih <strong><?= max(0, (int)$metrics['total_corpus'] - 864) ?> dokumen</strong> adalah karya ilmiah baru hasil pemanenan berkala nasional (Neliti &amp; GARUDA Dikti) yang masuk secara dinamis <em>setelah</em> pengujian tersebut selesai. Penguncian snapshot ini wajib dijaga secara forensik agar hasil evaluasi ilmiah terdahulu tidak berubah-ubah secara retroaktif saat repositori bertambah.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Auto-Harvester Berkala: Neliti & GARUDA Dikti (Opsi C) -->
                <div class="bg-gradient-to-r from-slate-900/90 via-slate-800/80 to-slate-900/90 border border-indigo-500/30 rounded-2xl p-5 shadow-lg space-y-4">
                    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 border-b border-slate-700/60 pb-3">
                        <div class="flex items-center gap-2.5">
                            <div class="p-2 rounded-xl bg-indigo-500/20 text-indigo-400 border border-indigo-500/30">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                            </div>
                            <div>
                                <h3 class="text-sm font-bold text-white flex items-center gap-2">
                                    Auto-Harvester Berkala Nasional (Neliti & GARUDA Dikti)
                                    <span class="px-2 py-0.5 rounded-full text-[10px] bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 font-semibold">Aktif & Siap Pemanenan</span>
                                </h3>
                                <p class="text-[11px] text-slate-400">Pemanenan berkala karya ilmiah & jurnal akreditasi nasional tanpa unggahan manual untuk memperluas korpus institusi</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <button onclick="handleHarvestScheduled()" id="btn-harvest-sched" class="px-3 py-1.5 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold shadow transition-colors flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                                Jalankan Seluruh Jadwal
                            </button>
                        </div>
                    </div>

                    <!-- Pemanen Grid: Neliti & GARUDA -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Card 1: Neliti -->
                        <div class="bg-slate-950/60 border border-slate-800 rounded-xl p-4 space-y-3">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <span class="text-base">🏛️</span>
                                    <div>
                                        <div class="text-xs font-bold text-white">Portal Riset Neliti</div>
                                        <div class="text-[10px] text-slate-400">Protokol OAI-PMH 2.0 (neliti.com/oai)</div>
                                    </div>
                                </div>
                                <span class="px-2 py-0.5 rounded text-[10px] bg-sky-500/10 text-sky-300 border border-sky-500/20">OAI-DC 2.0</span>
                            </div>
                            <div class="flex gap-2">
                                <input type="text" id="neliti-query" placeholder="Filter topik (mis: pendidikan, sains)..." class="flex-1 bg-slate-900 border border-slate-700/80 rounded-lg px-2.5 py-1.5 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500">
                                <select id="neliti-limit" class="bg-slate-900 border border-slate-700/80 rounded-lg px-2 py-1.5 text-xs text-white focus:outline-none focus:border-indigo-500">
                                    <option value="5">5 naskah</option>
                                    <option value="10" selected>10 naskah</option>
                                    <option value="20">20 naskah</option>
                                </select>
                            </div>
                            <button onclick="handleHarvestNeliti()" id="btn-harvest-neliti" class="w-full py-1.5 rounded-lg bg-sky-600 hover:bg-sky-500 text-white text-xs font-medium transition-colors flex items-center justify-center gap-1.5">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10"></path></svg>
                                Mulai Pemanenan Neliti
                            </button>
                        </div>

                        <!-- Card 2: GARUDA Dikti -->
                        <div class="bg-slate-950/60 border border-slate-800 rounded-xl p-4 space-y-3">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <span class="text-base">🦅</span>
                                    <div>
                                        <div class="text-xs font-bold text-white">GARUDA Dikti (Garba Rujukan Digital)</div>
                                        <div class="text-[10px] text-slate-400">Kemdiktisaintek Jurnal SINTA Terakreditasi</div>
                                    </div>
                                </div>
                                <span class="px-2 py-0.5 rounded text-[10px] bg-emerald-500/10 text-emerald-300 border border-emerald-500/20">SINTA Index</span>
                            </div>
                            <div class="flex gap-2">
                                <input type="text" id="garuda-query" placeholder="Kata kunci topik (mis: kecerdasan buatan, ekonomi)..." value="kecerdasan buatan" class="flex-1 bg-slate-900 border border-slate-700/80 rounded-lg px-2.5 py-1.5 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500">
                                <select id="garuda-limit" class="bg-slate-900 border border-slate-700/80 rounded-lg px-2 py-1.5 text-xs text-white focus:outline-none focus:border-indigo-500">
                                    <option value="5">5 naskah</option>
                                    <option value="10" selected>10 naskah</option>
                                    <option value="20">20 naskah</option>
                                </select>
                            </div>
                            <button onclick="handleHarvestGaruda()" id="btn-harvest-garuda" class="w-full py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-medium transition-colors flex items-center justify-center gap-1.5">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10"></path></svg>
                                Mulai Pemanenan GARUDA
                            </button>
                        </div>
                    </div>

                    <!-- Log Status Box (Hidden by default, shown during harvest) -->
                    <div id="harvest-console" class="hidden bg-slate-950 border border-slate-800 rounded-xl p-3 text-[11px] font-mono-code text-slate-300 space-y-1.5 max-h-36 overflow-y-auto">
                        <div class="flex items-center justify-between text-indigo-400 font-bold border-b border-slate-800 pb-1">
                            <span>Console Pemanenan Aktif:</span>
                            <span id="harvest-spinner" class="animate-pulse text-amber-400">Sedang memproses...</span>
                        </div>
                        <pre id="harvest-output" class="whitespace-pre-wrap text-slate-300 text-[10px]"></pre>
                    </div>

                    <!-- Riwayat Pemanenan Terakhir -->
                    <?php if (! empty($harvestLogs)): ?>
                        <div class="border-t border-slate-800/80 pt-3">
                            <div class="text-[11px] font-bold text-slate-300 mb-2 flex items-center justify-between">
                                <span>Riwayat Pemanenan Korpus Terakhir</span>
                                <span class="text-[10px] text-slate-400">Total <?= count($harvestLogs) ?> log rekaman</span>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="w-full text-[11px] text-left">
                                    <thead class="text-slate-400 text-[10px] uppercase border-b border-slate-800">
                                        <tr>
                                            <th class="py-1.5 px-2">Waktu</th>
                                            <th class="py-1.5 px-2">Sumber Portal</th>
                                            <th class="py-1.5 px-2">Topik / Query</th>
                                            <th class="py-1.5 px-2 text-center">Ditemukan</th>
                                            <th class="py-1.5 px-2 text-center">Terindeks</th>
                                            <th class="py-1.5 px-2 text-center">Duplikat</th>
                                            <th class="py-1.5 px-2">Status</th>
                                            <th class="py-1.5 px-2 text-right">Durasi</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-800/60">
                                        <?php foreach (array_slice($harvestLogs, 0, 5) as $hl): ?>
                                            <tr class="hover:bg-slate-800/30">
                                                <td class="py-1.5 px-2 text-slate-400 font-mono-code"><?= date('d M H:i', strtotime($hl['started_at'])) ?></td>
                                                <td class="py-1.5 px-2 font-medium text-slate-200"><?= esc($hl['source_name']) ?></td>
                                                <td class="py-1.5 px-2 text-slate-400"><?= esc($hl['query_or_set'] ?: '-') ?></td>
                                                <td class="py-1.5 px-2 text-center text-slate-300 font-mono-code"><?= (int) $hl['items_found'] ?></td>
                                                <td class="py-1.5 px-2 text-center font-bold text-emerald-400 font-mono-code">+<?= (int) $hl['items_indexed'] ?></td>
                                                <td class="py-1.5 px-2 text-center text-slate-500 font-mono-code"><?= (int) $hl['items_skipped'] ?></td>
                                                <td class="py-1.5 px-2">
                                                    <span class="px-1.5 py-0.5 rounded text-[9px] font-semibold <?= $hl['status'] === 'completed' ? 'bg-emerald-500/20 text-emerald-300' : ($hl['status'] === 'running' ? 'bg-indigo-500/20 text-indigo-300 animate-pulse' : 'bg-amber-500/20 text-amber-300') ?>">
                                                        <?= strtoupper(esc($hl['status'])) ?>
                                                    </span>
                                                </td>
                                                <td class="py-1.5 px-2 text-right text-slate-400 font-mono-code"><?= number_format((float) ($hl['duration_seconds'] ?? 0), 1) ?>s</td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Mobile List: Tampilan Kartu Responsif Korpus (Layar Ponsel) -->
                <div class="space-y-3.5 md:hidden">
                    <?php if (empty($corpusList)): ?>
                        <div class="bg-slate-800/40 border border-slate-700/60 rounded-2xl p-6 text-center text-xs text-slate-500">
                            Belum ada korpus terdaftar.
                        </div>
                    <?php else: ?>
                        <?php foreach ($corpusList as $c): ?>
                            <div id="corpus-mobile-<?= $c['id'] ?>" class="bg-slate-800/60 border border-slate-700/70 rounded-2xl p-4 shadow-sm space-y-3">
                                <!-- Header Judul Korpus -->
                                <div class="space-y-1">
                                    <div class="flex items-start justify-between gap-2">
                                        <div class="font-bold text-slate-100 text-sm leading-snug"><?= esc($c['title']) ?></div>
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold shrink-0 <?= $c['indexing_status'] === 'completed' ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-amber-500/10 text-amber-400 border border-amber-500/20' ?>">
                                            <?= esc($c['indexing_status']) ?>
                                        </span>
                                    </div>
                                    <div class="text-[11px] text-slate-400 font-mono-code truncate"><?= esc($c['original_filename']) ?></div>
                                </div>

                                <!-- Detail Sumber, Ukuran, Tanggal (2 Kolom) -->
                                <div class="grid grid-cols-2 gap-2 bg-slate-900/60 border border-slate-800/80 rounded-xl p-2.5 text-xs">
                                    <div>
                                        <div class="text-[10px] text-slate-500 uppercase tracking-wider">Sumber & Tipe</div>
                                        <span class="inline-block mt-0.5 px-2 py-0.5 rounded text-[10px] bg-slate-800 text-slate-300 border border-slate-700">
                                            <?= esc($c['source_type'] ?: 'other') ?>
                                        </span>
                                    </div>
                                    <div>
                                        <div class="text-[10px] text-slate-500 uppercase tracking-wider">Ukuran & Tanggal</div>
                                        <div class="text-slate-200 font-medium mt-0.5 text-[11px]">
                                            <?= number_format($c['file_size'] / 1024, 1) ?> KB • <?= date('d M Y', strtotime($c['created_at'])) ?>
                                        </div>
                                    </div>
                                </div>

                                <!-- Pengaturan Visibilitas & Aksi Hapus -->
                                <div class="pt-2 border-t border-slate-700/60 flex items-center justify-between gap-2">
                                    <div class="flex-1 flex items-center gap-1.5 min-w-0">
                                        <span class="text-[11px] text-slate-400 shrink-0">Visibilitas:</span>
                                        <select onchange="handleCorpusVisibility(<?= (int) $c['id'] ?>, this.value)" class="flex-1 bg-slate-900 border border-slate-700 rounded-lg px-2 py-1.5 text-xs text-slate-200 focus:outline-none focus:border-indigo-500 truncate">
                                            <option value="corpus" <?= $c['corpus_visibility'] === 'corpus' ? 'selected' : '' ?>>Publik Korpus</option>
                                            <option value="institution" <?= $c['corpus_visibility'] === 'institution' ? 'selected' : '' ?>>Institusi</option>
                                            <option value="private" <?= $c['corpus_visibility'] === 'private' ? 'selected' : '' ?>>Privat</option>
                                        </select>
                                    </div>
                                    <button onclick="confirmDeleteDocument(<?= (int) $c['id'] ?>, '<?= esc($c['title'], 'js') ?>', 'corpus-mobile-<?= $c['id'] ?>')" class="py-1.5 px-3 rounded-lg bg-rose-600/20 hover:bg-rose-600/30 text-rose-300 border border-rose-500/30 text-xs font-medium transition-colors shrink-0 cursor-pointer" title="Hapus Korpus">
                                        ✕ Hapus
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Desktop Table View: Terlihat di Layar md ke atas -->
                <div class="hidden md:block bg-slate-800/40 border border-slate-700/60 rounded-2xl overflow-hidden shadow-sm">
                    <div class="overflow-x-auto">
                        <table class="w-full text-xs text-left">
                            <thead class="bg-slate-900/80 text-slate-400 uppercase tracking-wider text-[10px] border-b border-slate-700/60">
                                <tr>
                                    <th class="py-3 px-4">Judul Korpus</th>
                                    <th class="py-3 px-3">Sumber & Tipe</th>
                                    <th class="py-3 px-3">Visibilitas</th>
                                    <th class="py-3 px-3">Status Indeks</th>
                                    <th class="py-3 px-3">Ukuran File</th>
                                    <th class="py-3 px-3">Tanggal Unggah</th>
                                    <th class="py-3 px-4 text-right">Kontrol</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-800/80">
                                <?php if (empty($corpusList)): ?>
                                    <tr><td colspan="7" class="py-6 text-center text-slate-500">Belum ada korpus terdaftar.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($corpusList as $c): ?>
                                        <tr id="corpus-row-<?= $c['id'] ?>" class="hover:bg-slate-800/50 transition-colors">
                                            <td class="py-3 px-4">
                                                <div class="font-bold text-slate-200"><?= esc($c['title']) ?></div>
                                                <div class="text-[11px] text-slate-400 font-mono-code"><?= esc($c['original_filename']) ?></div>
                                            </td>
                                            <td class="py-3 px-3 text-slate-300">
                                                <span class="px-2 py-0.5 rounded text-[10px] bg-slate-800 text-slate-300 border border-slate-700/60">
                                                    <?= esc($c['source_type'] ?: 'other') ?>
                                                </span>
                                            </td>
                                            <td class="py-3 px-3">
                                                <select onchange="handleCorpusVisibility(<?= (int) $c['id'] ?>, this.value)" class="bg-slate-900 border border-slate-700 rounded px-2 py-1 text-[11px] text-slate-200 focus:outline-none focus:border-indigo-500">
                                                    <option value="corpus" <?= $c['corpus_visibility'] === 'corpus' ? 'selected' : '' ?>>Publik Korpus</option>
                                                    <option value="institution" <?= $c['corpus_visibility'] === 'institution' ? 'selected' : '' ?>>Institusi</option>
                                                    <option value="private" <?= $c['corpus_visibility'] === 'private' ? 'selected' : '' ?>>Privat</option>
                                                </select>
                                            </td>
                                            <td class="py-3 px-3">
                                                <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold <?= $c['indexing_status'] === 'completed' ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-amber-500/10 text-amber-400 border border-amber-500/20' ?>">
                                                    <?= esc($c['indexing_status']) ?>
                                                </span>
                                            </td>
                                            <td class="py-3 px-3 text-slate-400 text-[11px] whitespace-nowrap">
                                                <?= number_format($c['file_size'] / 1024, 1) ?> KB
                                            </td>
                                            <td class="py-3 px-3 text-slate-400 text-[11px] whitespace-nowrap">
                                                <?= date('d M Y', strtotime($c['created_at'])) ?>
                                            </td>
                                            <td class="py-3 px-4 text-right whitespace-nowrap">
                                                <button onclick="confirmDeleteDocument(<?= (int) $c['id'] ?>, '<?= esc($c['title'], 'js') ?>', 'corpus-row-<?= $c['id'] ?>')" class="px-2.5 py-1 rounded-lg bg-rose-600/20 hover:bg-rose-600/30 text-rose-300 border border-rose-500/30 text-[11px] transition-colors cursor-pointer" title="Hapus korpus">
                                                    Hapus
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        <!-- ========================================== -->
        <!-- TAB 5: KEAMANAN & RATE LIMIT               -->
        <!-- ========================================== -->
        <?php elseif ($activeTab === 'security'): ?>
            <div class="space-y-6">
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                    <div>
                        <h2 class="text-lg font-bold text-white">Log Keamanan, Anti-Spam & Rate Limits</h2>
                        <p class="text-xs text-slate-400">Pantau upaya serangan brute-force login, spammer honeypot, dan buka blokir (Unblock)</p>
                    </div>
                    <?php if (!empty($securityLogs)): ?>
                        <button onclick="confirmClearAllLocks()" class="px-3 py-1.5 rounded-xl bg-rose-600/20 hover:bg-rose-600/30 text-rose-300 border border-rose-500/30 text-xs font-semibold flex items-center gap-1.5 transition-colors cursor-pointer">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                            Reset Semua Penguncian
                        </button>
                    <?php endif; ?>
                </div>

                <!-- Mobile List: Tampilan Kartu Responsif Keamanan (Layar Ponsel) -->
                <div class="space-y-3 md:hidden">
                    <?php if (empty($securityLogs)): ?>
                        <div class="bg-slate-800/40 border border-slate-700/60 rounded-2xl p-6 text-center text-xs text-slate-500">
                            Belum ada catatan pelanggaran keamanan atau pembatasan frekuensi.
                        </div>
                    <?php else: ?>
                        <?php foreach ($securityLogs as $log): ?>
                            <?php $isLocked = !empty($log['locked_until']) && strtotime($log['locked_until']) > time(); ?>
                            <div class="bg-slate-800/60 border border-slate-700/70 rounded-2xl p-4 shadow-sm space-y-3 <?= $isLocked ? 'bg-rose-950/20 border-rose-500/40' : '' ?>">
                                <div class="flex items-start justify-between gap-2">
                                    <div>
                                        <div class="font-mono-code font-bold text-slate-100 text-sm"><?= esc($log['ip_address']) ?></div>
                                        <div class="text-[11px] font-mono-code text-slate-400 mt-0.5 truncate"><?= esc($log['identifier']) ?></div>
                                    </div>
                                    <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-slate-800 text-slate-300 border border-slate-700">
                                        <?= esc($log['action_type']) ?>
                                    </span>
                                </div>

                                <div class="grid grid-cols-2 gap-2 bg-slate-900/60 border border-slate-800/80 rounded-xl p-2.5 text-xs">
                                    <div>
                                        <div class="text-[10px] text-slate-500 uppercase tracking-wider">Percobaan Gagal</div>
                                        <div class="font-bold text-amber-400 mt-0.5"><?= (int) $log['attempts'] ?>x</div>
                                    </div>
                                    <div>
                                        <div class="text-[10px] text-slate-500 uppercase tracking-wider">Status Kunci</div>
                                        <div class="mt-0.5">
                                            <?php if ($isLocked): ?>
                                                <span class="px-2 py-0.5 rounded-full text-[10px] bg-rose-500/20 text-rose-300 border border-rose-500/30 font-bold inline-flex items-center gap-1 animate-pulse">
                                                    🔒 s/d <?= date('H:i:s', strtotime($log['locked_until'])) ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="px-2 py-0.5 rounded-full text-[10px] bg-slate-800 text-slate-400">Normal</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="col-span-2 pt-1 border-t border-slate-800/80 text-[11px] text-slate-400">
                                        Waktu Terakhir: <?= date('d M Y, H:i:s', strtotime($log['last_attempt_at'])) ?>
                                    </div>
                                </div>

                                <div class="pt-1">
                                    <button onclick="handleUnblock(<?= (int) $log['id'] ?>)" class="w-full py-2 px-3 rounded-xl bg-emerald-600/20 hover:bg-emerald-600/30 text-emerald-300 border border-emerald-500/30 text-xs font-semibold flex items-center justify-center gap-1.5 transition-colors">
                                        🔓 Buka Kunci Blokir
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Desktop Table View: Terlihat di Layar md ke atas -->
                <div class="hidden md:block bg-slate-800/40 border border-slate-700/60 rounded-2xl overflow-hidden shadow-sm">
                    <div class="overflow-x-auto">
                        <table class="w-full text-xs text-left">
                            <thead class="bg-slate-900/80 text-slate-400 uppercase tracking-wider text-[10px] border-b border-slate-700/60">
                                <tr>
                                    <th class="py-3 px-4">Alamat IP</th>
                                    <th class="py-3 px-3">Tipe Aksi</th>
                                    <th class="py-3 px-3">Identitas Target</th>
                                    <th class="py-3 px-3 text-center">Percobaan Gagal</th>
                                    <th class="py-3 px-3">Waktu Terakhir</th>
                                    <th class="py-3 px-3">Status Kunci</th>
                                    <th class="py-3 px-4 text-right">Aksi Buka Blokir</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-800/80">
                                <?php if (empty($securityLogs)): ?>
                                    <tr><td colspan="7" class="py-6 text-center text-slate-500">Belum ada catatan pelanggaran keamanan atau pembatasan frekuensi.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($securityLogs as $log): ?>
                                        <?php 
                                             $isLocked = !empty($log['locked_until']) && strtotime($log['locked_until']) > time();
                                        ?>
                                        <tr class="hover:bg-slate-800/50 transition-colors <?= $isLocked ? 'bg-rose-950/20' : '' ?>">
                                            <td class="py-3 px-4 font-mono-code text-slate-200">
                                                <?= esc($log['ip_address']) ?>
                                            </td>
                                            <td class="py-3 px-3">
                                                <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-slate-800 text-slate-300 border border-slate-700/60">
                                                    <?= esc($log['action_type']) ?>
                                                </span>
                                            </td>
                                            <td class="py-3 px-3 font-mono-code text-slate-300">
                                                <?= esc($log['identifier']) ?>
                                            </td>
                                            <td class="py-3 px-3 text-center font-bold text-amber-400">
                                                <?= (int) $log['attempts'] ?>x
                                            </td>
                                            <td class="py-3 px-3 text-slate-400 text-[11px] whitespace-nowrap">
                                                <?= date('d M Y, H:i:s', strtotime($log['last_attempt_at'])) ?>
                                            </td>
                                            <td class="py-3 px-3">
                                                <?php if ($isLocked): ?>
                                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] bg-rose-500/20 text-rose-300 border border-rose-500/30 font-bold flex items-center gap-1 w-max animate-pulse">
                                                        🔒 Terkunci s/d <?= date('H:i:s', strtotime($log['locked_until'])) ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="px-2 py-0.5 rounded-full text-[10px] bg-slate-800 text-slate-400">Normal</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="py-3 px-4 text-right whitespace-nowrap">
                                                <button onclick="handleUnblock(<?= (int) $log['id'] ?>)" class="px-2.5 py-1 rounded-lg bg-emerald-600/20 hover:bg-emerald-600/30 text-emerald-300 border border-emerald-500/30 text-[11px] font-semibold transition-colors" title="Buka blokir IP/identitas ini">
                                                    Buka Kunci
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        <!-- ========================================== -->
        <!-- TAB 6: ANALITIK PENGUNJUNG                 -->
        <!-- ========================================== -->
        <?php elseif ($activeTab === 'visitors'): ?>
            <div class="space-y-6">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-bold text-white">Analitik Pengunjung Unik Landing Page</h2>
                        <p class="text-xs text-slate-400">Data analitik anonim pelacak pengunjung landing page 30 hari terakhir</p>
                    </div>
                    <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-xl bg-slate-800/60 border border-slate-700/60 text-xs text-slate-300 w-fit">
                        <span class="relative flex h-2 w-2">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                        </span>
                        <span class="font-medium text-slate-200">Sinkronisasi Real-Time</span>
                        <span class="text-slate-500">•</span>
                        <span class="text-[11px] text-slate-400 font-mono" id="visitor-last-sync">Memuat...</span>
                        <button type="button" onclick="refreshVisitorStatsRealtime(true)" id="btn-manual-sync-visitors" class="ml-1 p-1 hover:text-white text-slate-400 hover:bg-slate-700/50 rounded transition cursor-pointer" title="Perbarui data sekarang">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                        </button>
                    </div>
                </div>

                <!-- Status Filter Integritas Trafik & Bot Protection -->
                <div class="bg-slate-900/80 border border-slate-700/70 rounded-2xl p-3.5 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 text-xs">
                    <div class="flex items-center gap-2.5">
                        <span class="p-1.5 rounded-lg bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 text-sm">🛡️</span>
                        <div>
                            <span class="font-semibold text-slate-200">Filter Integritas Lalu Lintas &amp; Proteksi Bot Aktif</span>
                            <p class="text-[11px] text-slate-400 mt-0.5">
                                Log anomali pra-instalasi &amp; stress test (05 Okt) telah dikarantina/dibersihkan dari basis analitik produksi. Seluruh metrik mencerminkan lalu lintas riil pengunjung terverifikasi dengan proteksi Cloudflare Turnstile &amp; rate-limiting.
                            </p>
                        </div>
                    </div>
                    <span class="px-2.5 py-1 rounded-full text-[10px] font-semibold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 whitespace-nowrap shrink-0">Data Bersih Terverifikasi</span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="bg-slate-800/40 border border-slate-700/60 rounded-2xl p-4">
                        <span class="text-xs text-slate-400">Total Pengunjung Unik</span>
                        <div id="metric-total-unique" class="text-2xl font-bold text-white mt-1 transition-all duration-300"><?= number_format($metrics['visitor_stats']['total_unique'] ?? 0) ?></div>
                    </div>
                    <div class="bg-slate-800/40 border border-slate-700/60 rounded-2xl p-4">
                        <span class="text-xs text-slate-400">Total Tayangan Halaman</span>
                        <div id="metric-total-hits" class="text-2xl font-bold text-sky-400 mt-1 transition-all duration-300"><?= number_format($metrics['visitor_stats']['total_hits'] ?? 0) ?></div>
                    </div>
                    <div class="bg-slate-800/40 border border-slate-700/60 rounded-2xl p-4">
                        <span class="text-xs text-slate-400">Pengunjung Unik Hari Ini</span>
                        <div id="metric-today-unique" class="text-2xl font-bold text-emerald-400 mt-1 transition-all duration-300"><?= number_format($metrics['visitor_stats']['today_unique'] ?? 0) ?></div>
                    </div>
                    <div class="bg-slate-800/40 border border-slate-700/60 rounded-2xl p-4">
                        <span class="text-xs text-slate-400">Tayangan Laman Hari Ini</span>
                        <div id="metric-today-hits" class="text-2xl font-bold text-purple-400 mt-1 transition-all duration-300"><?= number_format($metrics['visitor_stats']['today_hits'] ?? 0) ?></div>
                    </div>
                </div>

                <!-- KARTU GRAFIK ANALITIK VISUAL (Real-time Live Sync) -->
                <div class="bg-slate-800/40 border border-slate-700/60 rounded-2xl p-4 sm:p-5 shadow-sm space-y-4">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div>
                            <h3 class="text-sm font-bold text-white flex items-center gap-2">
                                <span>📈</span> Tren Kunjungan Harian Landing Page
                            </h3>
                            <p class="text-[11px] text-slate-400">Visualisasi dinamika Pengunjung Unik dan Total Tayangan (Hits)</p>
                        </div>
                        <div class="flex items-center gap-2 flex-wrap">
                            <!-- Custom Legend Badges -->
                            <div class="inline-flex items-center gap-3 px-3 py-1 bg-slate-900/60 border border-slate-700/70 rounded-xl text-xs">
                                <span class="inline-flex items-center gap-1.5 text-emerald-400 font-semibold text-[11px]">
                                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-400"></span> Pengunjung Unik
                                </span>
                                <span class="inline-flex items-center gap-1.5 text-sky-400 font-semibold text-[11px]">
                                    <span class="w-2.5 h-2.5 rounded-full bg-sky-400"></span> Total Tayangan
                                </span>
                            </div>
                            <!-- Time Range Selector -->
                            <div class="inline-flex p-0.5 rounded-xl bg-slate-900/80 border border-slate-700/70 text-xs font-medium">
                                <button type="button" onclick="setChartRange(7, this)" class="chart-range-btn px-2.5 py-1 rounded-lg text-[11px] text-slate-300 hover:text-white transition cursor-pointer">7 Hari</button>
                                <button type="button" onclick="setChartRange(14, this)" class="chart-range-btn px-2.5 py-1 rounded-lg text-[11px] text-slate-300 hover:text-white transition cursor-pointer">14 Hari</button>
                                <button type="button" onclick="setChartRange(30, this)" class="chart-range-btn active-range-btn px-2.5 py-1 rounded-lg text-[11px] bg-indigo-600 text-white font-semibold shadow-xs transition cursor-pointer">30 Hari</button>
                            </div>
                            <!-- Tombol Toggle Tabel Data -->
                            <button type="button" onclick="toggleVisitorTable()" id="btn-toggle-visitor-table" class="px-2.5 py-1 rounded-xl bg-slate-800/80 hover:bg-slate-700 border border-slate-700/70 text-slate-300 hover:text-white text-[11px] font-medium transition flex items-center gap-1.5 cursor-pointer" title="Tampilkan rincian tabel tabular">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                <span id="label-toggle-table">Buka Tabel Data</span>
                            </button>
                        </div>
                    </div>

                    <!-- Canvas Grafik -->
                    <div class="relative w-full" style="height: 310px;">
                        <canvas id="visitorAnalyticsChart"></canvas>
                    </div>
                </div>

                <!-- TABEL DATA RINCIAN (COLLAPSIBLE / OPSIONAL) -->
                <div id="visitorTableWrapper" class="hidden bg-slate-800/40 border border-slate-700/60 rounded-2xl overflow-hidden shadow-sm transition-all duration-300">
                    <div class="px-4 py-3 bg-slate-900/60 border-b border-slate-700/60 flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-300 flex items-center gap-2">
                            <span>📋</span> Rincian Kunjungan Harian (Tabular)
                        </span>
                        <span class="text-[11px] text-slate-400">Sinkronisasi otomatis aktif</span>
                    </div>

                    <!-- Mobile List: Tampilan Kartu Responsif Pengunjung Harian (Layar Ponsel) -->
                    <div id="mobile-visitor-list" class="p-4 space-y-3 md:hidden">
                        <?php if (empty($dailyVisitors)): ?>
                            <div class="bg-slate-800/40 border border-slate-700/60 rounded-2xl p-6 text-center text-xs text-slate-500">
                                Belum ada catatan pengunjung.
                            </div>
                        <?php else: ?>
                            <?php foreach ($dailyVisitors as $v): ?>
                                <div class="bg-slate-800/60 border border-slate-700/70 rounded-2xl p-3.5 shadow-sm space-y-2" data-mobile-date="<?= $v['visit_date'] ?>">
                                    <div class="flex items-center justify-between">
                                        <span class="font-bold text-slate-200 text-xs"><?= date('d F Y', strtotime($v['visit_date'])) ?></span>
                                        <span class="text-[11px] text-slate-400 font-mono-code" data-cell="avg">Rata-rata: <?= $v['unique_visitors'] > 0 ? number_format($v['total_hits'] / $v['unique_visitors'], 2) : '1.00' ?> hits</span>
                                    </div>
                                    <div class="grid grid-cols-2 gap-2 text-xs bg-slate-900/60 border border-slate-800/80 rounded-xl p-2.5">
                                        <div>
                                            <div class="text-[10px] text-slate-500 uppercase tracking-wider">Pengunjung Unik</div>
                                            <div class="font-bold text-emerald-400 text-sm mt-0.5" data-cell="unique"><?= number_format($v['unique_visitors']) ?></div>
                                        </div>
                                        <div>
                                            <div class="text-[10px] text-slate-500 uppercase tracking-wider">Total Tayangan</div>
                                            <div class="font-bold text-sky-400 text-sm mt-0.5" data-cell="hits"><?= number_format($v['total_hits']) ?></div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <!-- Desktop Table View: Terlihat di Layar md ke atas -->
                    <div class="hidden md:block overflow-x-auto">
                        <table class="w-full text-xs text-left">
                            <thead class="bg-slate-900/80 text-slate-400 uppercase tracking-wider text-[10px] border-b border-slate-700/60">
                                <tr>
                                    <th class="py-3 px-4">Tanggal Kunjungan</th>
                                    <th class="py-3 px-4 text-center">Pengunjung Unik</th>
                                    <th class="py-3 px-4 text-center">Total Tayangan (Hits)</th>
                                    <th class="py-3 px-4 text-right">Rata-rata Hits/Pengunjung</th>
                                </tr>
                            </thead>
                            <tbody id="desktop-visitor-tbody" class="divide-y divide-slate-800/80">
                                <?php if (empty($dailyVisitors)): ?>
                                    <tr><td colspan="4" class="py-6 text-center text-slate-500">Belum ada catatan pengunjung.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($dailyVisitors as $v): ?>
                                        <tr class="hover:bg-slate-800/50 transition-colors" data-visit-date="<?= $v['visit_date'] ?>">
                                            <td class="py-3 px-4 font-semibold text-slate-200">
                                                <?= date('d F Y', strtotime($v['visit_date'])) ?>
                                            </td>
                                            <td class="py-3 px-4 text-center font-bold text-emerald-400" data-cell="unique">
                                                <?= number_format($v['unique_visitors']) ?>
                                            </td>
                                            <td class="py-3 px-4 text-center font-bold text-sky-400" data-cell="hits">
                                                <?= number_format($v['total_hits']) ?>
                                            </td>
                                            <td class="py-3 px-4 text-right text-slate-300 font-mono-code" data-cell="avg">
                                                <?= $v['unique_visitors'] > 0 ? number_format($v['total_hits'] / $v['unique_visitors'], 2) : '1.00' ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        <!-- ========================================== -->
        <!-- TAB 6.5: TRANSAKSI & PEMBAYARAN MIDTRANS   -->
        <!-- ========================================== -->
        <?php elseif ($activeTab === 'payments'): ?>
            <div class="space-y-6">
                <div>
                    <h2 class="text-lg font-bold text-white">Transaksi & Pendapatan Midtrans</h2>
                    <p class="text-xs text-slate-400">Monitoring pembayaran real-time via QRIS, GoPay, ShopeePay, dan Virtual Account</p>
                </div>

                <!-- Metric Cards -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="bg-slate-800/40 border border-slate-700/60 rounded-2xl p-4 flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-emerald-500/20 text-emerald-400 flex items-center justify-center font-bold text-lg">💰</div>
                        <div>
                            <div class="text-[11px] text-slate-400">Total Pendapatan (Gross)</div>
                            <div class="text-xl font-extrabold text-white">Rp <?= number_format($metrics['payment_stats']['total_revenue'] ?? 0, 0, ',', '.') ?></div>
                        </div>
                    </div>
                    <div class="bg-slate-800/40 border border-slate-700/60 rounded-2xl p-4 flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-indigo-500/20 text-indigo-400 flex items-center justify-center font-bold text-lg">✅</div>
                        <div>
                            <div class="text-[11px] text-slate-400">Transaksi Lunas (Settlement)</div>
                            <div class="text-xl font-extrabold text-emerald-400"><?= number_format($metrics['payment_stats']['settled_count'] ?? 0) ?></div>
                        </div>
                    </div>
                    <div class="bg-slate-800/40 border border-slate-700/60 rounded-2xl p-4 flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-amber-500/20 text-amber-400 flex items-center justify-center font-bold text-lg">⏳</div>
                        <div>
                            <div class="text-[11px] text-slate-400">Menunggu Pembayaran</div>
                            <div class="text-xl font-extrabold text-amber-400"><?= number_format($metrics['payment_stats']['pending_count'] ?? 0) ?></div>
                        </div>
                    </div>
                    <div class="bg-slate-800/40 border border-slate-700/60 rounded-2xl p-4 flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-sky-500/20 text-sky-400 flex items-center justify-center font-bold text-lg">📊</div>
                        <div>
                            <div class="text-[11px] text-slate-400">Total Transaksi</div>
                            <div class="text-xl font-extrabold text-slate-200"><?= number_format($metrics['payment_stats']['total_transactions'] ?? 0) ?></div>
                        </div>
                    </div>
                </div>

                <!-- Payments Table -->
                <div class="bg-slate-800/40 border border-slate-700/60 rounded-2xl overflow-hidden shadow-xs">
                    <div class="px-5 py-3 border-b border-slate-700/60 flex items-center justify-between">
                        <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400">Log Transaksi Terkini</h3>
                        <span class="text-[11px] text-slate-500">Maks. 50 transaksi terakhir</span>
                    </div>
                    <!-- Mobile List: Tampilan Kartu Responsif Transaksi Midtrans (Layar Ponsel) -->
                    <div class="p-4 space-y-3 md:hidden">
                        <?php if (empty($paymentsList)): ?>
                            <div class="bg-slate-800/40 border border-slate-700/60 rounded-2xl p-6 text-center text-xs text-slate-500">
                                Belum ada transaksi pembayaran yang tercatat.
                            </div>
                        <?php else: ?>
                            <?php foreach ($paymentsList as $pay): ?>
                                <div class="bg-slate-800/60 border border-slate-700/70 rounded-2xl p-4 shadow-sm space-y-3">
                                    <div class="flex items-start justify-between gap-2">
                                        <div class="min-w-0 flex-1">
                                            <div class="font-mono-code text-xs font-bold text-indigo-300 truncate"><?= esc($pay['order_id']) ?></div>
                                            <div class="text-sm font-semibold text-slate-100 mt-0.5 truncate"><?= esc($pay['user_name'] ?? 'User #' . $pay['user_id']) ?></div>
                                            <div class="text-[10px] text-slate-400 truncate"><?= esc($pay['user_email'] ?? '-') ?><?= !empty($pay['user_institution']) ? ' • <span class="text-indigo-300 font-medium">' . esc($pay['user_institution']) . '</span>' : '' ?></div>
                                        </div>
                                        <div>
                                            <?php if (in_array($pay['transaction_status'], ['settlement', 'capture'])): ?>
                                                <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-500/20 text-emerald-400 border border-emerald-500/30">LUNAS</span>
                                            <?php elseif ($pay['transaction_status'] === 'pending'): ?>
                                                <div class="flex flex-col items-end gap-1">
                                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-amber-500/20 text-amber-300 border border-amber-500/30">PENDING</span>
                                                    <a href="<?= base_url('payment/status/' . esc($pay['order_id'])) ?>" target="_blank" class="text-[10px] text-amber-400 hover:text-amber-300 underline font-medium">Cek Status &rarr;</a>
                                                </div>
                                            <?php else: ?>
                                                <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-rose-500/20 text-rose-300 border border-rose-500/30"><?= strtoupper(esc($pay['transaction_status'])) ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-2 gap-2 text-xs bg-slate-900/60 border border-slate-800/80 rounded-xl p-2.5">
                                        <div>
                                            <div class="text-[10px] text-slate-500 uppercase tracking-wider">Paket Kuota</div>
                                            <div class="text-slate-200 font-medium text-xs mt-0.5"><?= esc($pay['package_name']) ?></div>
                                            <div class="text-[10px] text-emerald-400 font-semibold">+<?= (int) $pay['submissions_added'] ?> submisi</div>
                                        </div>
                                        <div>
                                            <div class="text-[10px] text-slate-500 uppercase tracking-wider">Nominal Bayar</div>
                                            <div class="font-mono-code font-extrabold text-sm text-slate-100 mt-0.5">
                                                Rp <?= number_format($pay['amount'], 0, ',', '.') ?>
                                            </div>
                                            <div class="text-[10px] text-slate-400 uppercase font-semibold"><?= esc($pay['payment_type'] ?? 'Midtrans') ?></div>
                                        </div>
                                        <div class="col-span-2 pt-1 border-t border-slate-800/80 text-[11px] text-slate-400 flex items-center justify-between">
                                            <span>Waktu:</span>
                                            <span class="text-slate-300 font-mono-code"><?= $pay['paid_at'] ? date('d M Y, H:i', strtotime($pay['paid_at'])) : date('d M Y, H:i', strtotime($pay['created_at'])) ?></span>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <!-- Desktop Table View: Terlihat di Layar md ke atas -->
                    <div class="hidden md:block overflow-x-auto">
                        <table class="w-full text-left border-collapse text-xs">
                            <thead>
                                <tr class="bg-slate-900/50 border-b border-slate-700/60 text-slate-400 font-semibold">
                                    <th class="p-3">Order ID</th>
                                    <th class="p-3">Pelanggan</th>
                                    <th class="p-3">Paket</th>
                                    <th class="p-3">Nominal</th>
                                    <th class="p-3">Metode</th>
                                    <th class="p-3">Status</th>
                                    <th class="p-3">Waktu Bayar / Buat</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-700/40">
                                <?php if (empty($paymentsList)): ?>
                                    <tr>
                                        <td colspan="7" class="p-6 text-center text-slate-500">Belum ada transaksi pembayaran yang tercatat.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($paymentsList as $pay): ?>
                                        <tr class="hover:bg-slate-800/30 transition-colors">
                                            <td class="p-3 font-mono-code text-indigo-300 font-semibold"><?= esc($pay['order_id']) ?></td>
                                            <td class="p-3">
                                                <div class="text-slate-200 font-medium"><?= esc($pay['user_name'] ?? 'User #' . $pay['user_id']) ?></div>
                                                <div class="text-[10px] text-slate-400"><?= esc($pay['user_email'] ?? '-') ?><?= !empty($pay['user_institution']) ? ' • <span class="text-indigo-300 font-medium">' . esc($pay['user_institution']) . '</span>' : '' ?></div>
                                            </td>
                                            <td class="p-3">
                                                <div class="text-slate-200 font-medium"><?= esc($pay['package_name']) ?></div>
                                                <div class="text-[10px] text-emerald-400">+<?= (int) $pay['submissions_added'] ?> submisi</div>
                                            </td>
                                            <td class="p-3 font-mono-code font-bold text-slate-100">
                                                Rp <?= number_format($pay['amount'], 0, ',', '.') ?>
                                            </td>
                                            <td class="p-3 text-slate-300 uppercase text-[10px] font-semibold">
                                                <?= esc($pay['payment_type'] ?? 'Midtrans') ?>
                                            </td>
                                            <td class="p-3">
                                                <?php if (in_array($pay['transaction_status'], ['settlement', 'capture'])): ?>
                                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-500/20 text-emerald-400 border border-emerald-500/30">LUNAS</span>
                                                <?php elseif ($pay['transaction_status'] === 'pending'): ?>
                                                    <div class="flex items-center gap-1.5 flex-wrap">
                                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-500/20 text-amber-300 border border-amber-500/30">PENDING</span>
                                                        <a href="<?= base_url('payment/status/' . esc($pay['order_id'])) ?>" target="_blank" class="text-[10px] text-amber-400 hover:text-amber-300 underline font-medium" title="Periksa status transaksi ke Midtrans">Cek Status</a>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-rose-500/20 text-rose-300 border border-rose-500/30"><?= strtoupper(esc($pay['transaction_status'])) ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="p-3 text-[11px] text-slate-400">
                                                <?= $pay['paid_at'] ? date('d M Y, H:i', strtotime($pay['paid_at'])) : date('d M Y, H:i', strtotime($pay['created_at'])) ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        <!-- ========================================== -->
        <!-- TAB: ANALITIK INTEGRITAS INSTITUSI (OPSI D) -->
        <!-- ========================================== -->
        <?php elseif ($activeTab === 'analytics'): ?>
            <div class="space-y-6">
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                    <div>
                        <h2 class="text-lg font-bold text-white flex items-center gap-2">
                            Dashboard Analitik & Integritas Akademik Institusi
                            <span class="px-2 py-0.5 rounded text-[10px] bg-indigo-500/20 text-indigo-300 border border-indigo-500/30">BAN-PT & LAM Ready</span>
                        </h2>
                        <p class="text-xs text-slate-400">Audit makro orisinalitas naskah akademik, kepatuhan sitasi, deteksi manipulasi & pelaporan mutu universitas</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <a href="<?= base_url('admin/analytics/export-csv') ?>" data-no-loader="true" download="audit_integritas_artilik.csv" class="px-3.5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-semibold shadow transition-colors flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                            Ekspor Rekapitulasi Integritas (.CSV)
                        </a>
                    </div>
                </div>

                <!-- KPI Cards Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <!-- Rata-rata Kemiripan -->
                    <div class="bg-slate-800/60 border border-slate-700/60 rounded-2xl p-5 shadow-sm">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Rata-Rata Kemiripan</span>
                            <div class="p-2 rounded-xl bg-indigo-500/10 text-indigo-400 border border-indigo-500/20">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                            </div>
                        </div>
                        <div class="mt-3 flex items-baseline gap-2">
                            <span class="text-3xl font-extrabold text-white tracking-tight"><?= number_format($integrityAnalytics['avg_similarity'], 1) ?>%</span>
                            <span class="text-xs text-slate-400 font-medium">Batas Aman Kampus: &lt; 20%</span>
                        </div>
                        <div class="mt-2 text-[11px] text-emerald-400 flex items-center gap-1">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                            <span>Standar Keadilan Dual-Lens Terpenuhi</span>
                        </div>
                    </div>

                    <!-- Total Naskah Terverifikasi -->
                    <div class="bg-slate-800/60 border border-slate-700/60 rounded-2xl p-5 shadow-sm">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Laporan Selesai Diaudit</span>
                            <div class="p-2 rounded-xl bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            </div>
                        </div>
                        <div class="mt-3 flex items-baseline gap-2">
                            <span class="text-3xl font-extrabold text-white tracking-tight"><?= number_format($integrityAnalytics['total_audited']) ?></span>
                            <span class="text-xs text-emerald-400 font-medium">Laporan Tuntas</span>
                        </div>
                        <div class="mt-2 text-[11px] text-slate-400">
                            Dari <strong class="text-slate-300 font-mono-code"><?= number_format($metrics['total_submissions']) ?></strong> total submisi naskah (skripsi, tesis &amp; artikel)
                        </div>
                    </div>

                    <!-- Manipulasi & Evasif -->
                    <div class="bg-slate-800/60 border border-slate-700/60 rounded-2xl p-5 shadow-sm">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Indikasi Evasif / Manipulasi</span>
                            <div class="p-2 rounded-xl bg-rose-500/10 text-rose-400 border border-rose-500/20">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                            </div>
                        </div>
                        <div class="mt-3 flex items-baseline gap-2">
                            <span class="text-3xl font-extrabold text-white tracking-tight"><?= number_format($integrityAnalytics['evasion_count']) ?></span>
                            <span class="text-xs text-rose-400 font-medium">Naskah</span>
                        </div>
                        <div class="mt-2 text-[11px] text-slate-400">
                            Deteksi Homoglif Cyrillic, Font Camouflage, Zero-Width
                        </div>
                    </div>

                    <!-- Sitasi Gaib & Lintas Bahasa -->
                    <div class="bg-slate-800/60 border border-slate-700/60 rounded-2xl p-5 shadow-sm">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Sitasi Gaib & Lintas Bahasa</span>
                            <div class="p-2 rounded-xl bg-amber-500/10 text-amber-400 border border-amber-500/20">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129"></path></svg>
                            </div>
                        </div>
                        <div class="mt-3 flex items-baseline gap-2">
                            <span class="text-3xl font-extrabold text-white tracking-tight"><?= number_format($integrityAnalytics['phantom_count'] + $integrityAnalytics['cross_lingual_count']) ?></span>
                            <span class="text-xs text-amber-400 font-medium">Insiden</span>
                        </div>
                        <div class="mt-2 text-[11px] text-slate-400">
                            <?= $integrityAnalytics['phantom_count'] ?> Sitasi Gaib, <?= $integrityAnalytics['cross_lingual_count'] ?> Mesin Terjemahan
                        </div>
                    </div>
                </div>

                <!-- Distribusi Klasifikasi & Tren -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Card 1: Distribusi Kategori Integritas -->
                    <div class="bg-slate-800/40 border border-slate-700/60 rounded-2xl p-5 space-y-4">
                        <div class="flex items-center justify-between border-b border-slate-700/60 pb-3">
                            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-300">Distribusi Orisinalitas Naskah</h3>
                            <span class="text-[11px] text-slate-400"><?= (int) $integrityAnalytics['total_audited'] ?> Sampel Terverifikasi</span>
                        </div>
                        <?php 
                            $tot = max(1, (int) $integrityAnalytics['total_audited']);
                            $pClean = round(($integrityAnalytics['clean_count'] / $tot) * 100, 1);
                            $pMod = round(($integrityAnalytics['moderate_count'] / $tot) * 100, 1);
                            $pSub = round(($integrityAnalytics['substantial_count'] / $tot) * 100, 1);
                            $pCrit = round(($integrityAnalytics['critical_count'] / $tot) * 100, 1);
                        ?>
                        <div class="space-y-3.5">
                            <div>
                                <div class="flex justify-between text-xs mb-1">
                                    <span class="text-emerald-300 font-medium">Sangat Bersih (&lt; 15%) &mdash; Orisinal</span>
                                    <span class="font-bold text-white"><?= $integrityAnalytics['clean_count'] ?> (<?= $pClean ?>%)</span>
                                </div>
                                <div class="w-full bg-slate-900 rounded-full h-2.5 overflow-hidden">
                                    <div class="bg-emerald-500 h-2.5 rounded-full" style="width: <?= $pClean ?>%"></div>
                                </div>
                            </div>

                            <div>
                                <div class="flex justify-between text-xs mb-1">
                                    <span class="text-sky-300 font-medium">Moderat (15% &ndash; 25%) &mdash; Perlu Telaah Rujukan</span>
                                    <span class="font-bold text-white"><?= $integrityAnalytics['moderate_count'] ?> (<?= $pMod ?>%)</span>
                                </div>
                                <div class="w-full bg-slate-900 rounded-full h-2.5 overflow-hidden">
                                    <div class="bg-sky-500 h-2.5 rounded-full" style="width: <?= $pMod ?>%"></div>
                                </div>
                            </div>

                            <div>
                                <div class="flex justify-between text-xs mb-1">
                                    <span class="text-amber-300 font-medium">Kemiripan Substansial (25% &ndash; 40%) &mdash; Revisi Mayor</span>
                                    <span class="font-bold text-white"><?= $integrityAnalytics['substantial_count'] ?> (<?= $pSub ?>%)</span>
                                </div>
                                <div class="w-full bg-slate-900 rounded-full h-2.5 overflow-hidden">
                                    <div class="bg-amber-500 h-2.5 rounded-full" style="width: <?= $pSub ?>%"></div>
                                </div>
                            </div>

                            <div>
                                <div class="flex justify-between text-xs mb-1">
                                    <span class="text-rose-300 font-medium">Kritis (&gt; 40%) &mdash; Indikasi Pelanggaran Berat</span>
                                    <span class="font-bold text-white"><?= $integrityAnalytics['critical_count'] ?> (<?= $pCrit ?>%)</span>
                                </div>
                                <div class="w-full bg-slate-900 rounded-full h-2.5 overflow-hidden">
                                    <div class="bg-rose-500 h-2.5 rounded-full" style="width: <?= $pCrit ?>%"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Card 2: Tren Bulanan -->
                    <div class="bg-slate-800/40 border border-slate-700/60 rounded-2xl p-5 space-y-4">
                        <div class="flex items-center justify-between border-b border-slate-700/60 pb-3">
                            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-300">Tren Kemiripan Naskah (6 Bulan Terakhir)</h3>
                            <span class="text-[11px] text-slate-400">Periode Evaluasi Terkini</span>
                        </div>
                        <?php if (empty($integrityAnalytics['monthly_trends'])): ?>
                            <div class="py-8 text-center text-slate-500 text-xs">Belum cukup data tren historis.</div>
                        <?php else: ?>
                            <div class="space-y-3">
                                <?php foreach ($integrityAnalytics['monthly_trends'] as $mt): ?>
                                    <?php $sim = round((float) $mt['avg_sim'], 1); ?>
                                    <div class="flex items-center justify-between text-xs">
                                        <span class="text-slate-300 font-mono-code w-20"><?= esc($mt['period']) ?></span>
                                        <div class="flex-1 mx-3 bg-slate-900 rounded-full h-2 overflow-hidden">
                                            <div class="h-2 rounded-full <?= $sim < 15 ? 'bg-emerald-500' : ($sim < 25 ? 'bg-sky-500' : 'bg-amber-500') ?>" style="width: <?= min(100, $sim * 2.5) ?>%"></div>
                                        </div>
                                        <span class="text-slate-400 font-mono-code text-[11px] w-24 text-right">
                                            <?= (int) $mt['total_submissions'] ?> naskah &bull; <strong class="text-white"><?= $sim ?>%</strong>
                                        </span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Sumber Korpus Teratas yang Menjadi Rujukan Kemiripan -->
                <div class="bg-slate-800/40 border border-slate-700/60 rounded-2xl overflow-hidden shadow-sm">
                    <div class="px-5 py-3 border-b border-slate-700/60 flex items-center justify-between bg-slate-900/60">
                        <h3 class="text-xs font-bold uppercase tracking-wider text-slate-300">Sumber Rujukan Teratas yang Sering Beririsan</h3>
                        <span class="text-[11px] text-slate-400">Analisis Klaster Korpus Kampus</span>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-xs text-left">
                            <thead class="bg-slate-900/80 text-slate-400 uppercase tracking-wider text-[10px] border-b border-slate-700/60">
                                <tr>
                                    <th class="py-2.5 px-4">Judul Dokumen Korpus</th>
                                    <th class="py-2.5 px-3">Tipe Sumber</th>
                                    <th class="py-2.5 px-3 text-center">Frekuensi Terdeteksi</th>
                                    <th class="py-2.5 px-3 text-right">Rata-rata Kemiripan</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-800/80">
                                <?php if (empty($integrityAnalytics['top_sources'])): ?>
                                    <tr><td colspan="4" class="py-4 text-center text-slate-500">Belum ada data irisan naskah yang terakumulasi.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($integrityAnalytics['top_sources'] as $ts): ?>
                                        <tr class="hover:bg-slate-800/40">
                                            <td class="py-2.5 px-4 font-medium text-slate-200">
                                                <?= esc($ts['title'] ?? ('Rujukan #' . $ts['source_document_id'])) ?>
                                            </td>
                                            <td class="py-2.5 px-3 text-slate-400">
                                                <span class="px-2 py-0.5 rounded text-[10px] bg-slate-800 border border-slate-700/60">
                                                    <?= esc($ts['source_type'] ?: 'corpus') ?>
                                                </span>
                                            </td>
                                            <td class="py-2.5 px-3 text-center text-indigo-400 font-bold font-mono-code">
                                                <?= (int) $ts['match_occurrences'] ?> kali
                                            </td>
                                            <td class="py-2.5 px-3 text-right text-slate-300 font-mono-code">
                                                <?= number_format((float) ($ts['avg_similarity'] ?? 0), 1) ?>%
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        <!-- ========================================== -->
        <!-- TAB: INTEGRASI API & PLUGIN OJS (OPSI B)   -->
        <!-- ========================================== -->
        <?php elseif ($activeTab === 'api_keys'): ?>
            <div class="space-y-6">
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                    <div>
                        <h2 class="text-lg font-bold text-white flex items-center gap-2">
                            Integrasi REST API Publik &amp; SDK Konektor OJS (Open Journal Systems)
                            <span class="px-2 py-0.5 rounded text-[10px] bg-sky-500/20 text-sky-300 border border-sky-500/30">OJS 3.x Ready</span>
                        </h2>
                        <p class="text-xs text-slate-400">Pustaka PHP (SDK), skrip runner CLI, dan API publik untuk menghubungkan alur penelaahan naskah jurnal ilmiah kampus ke AR Tilik</p>
                    </div>
                    <div class="flex items-center gap-2 flex-wrap">
                        <a href="<?= base_url('integrations/ojs/connector') ?>" download="ARTilikOJSConnector.php" class="px-2.5 py-1.5 rounded-lg bg-sky-600 hover:bg-sky-500 text-white text-xs font-semibold shadow transition-colors flex items-center gap-1.5" title="Pustaka PHP Client SDK">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                            PHP SDK
                        </a>
                        <a href="<?= base_url('integrations/ojs/runner') ?>" download="artilik_ojs_runner.php" class="px-2.5 py-1.5 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold shadow transition-colors flex items-center gap-1.5" title="Skrip Runner Mandiri CLI">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            CLI Runner
                        </a>
                        <a href="<?= base_url('integrations/ojs/plugin') ?>" download="artilik-ojs-plugin.zip" class="px-2.5 py-1.5 rounded-lg bg-purple-600 hover:bg-purple-500 text-white text-xs font-semibold shadow transition-colors flex items-center gap-1.5" title="Paket Plugin Generic PKP OJS (ZIP)">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path></svg>
                            PKP Plugin (ZIP)
                        </a>
                        <a href="<?= base_url('integrations/ojs/Panduan_Integrasi_OJS_ARTilik.pdf') ?>" target="_blank" download="Panduan_Integrasi_OJS_ARTilik.pdf" class="px-2.5 py-1.5 rounded-lg bg-gradient-to-r from-rose-600 to-rose-700 hover:from-rose-500 hover:to-rose-600 text-white text-xs font-semibold shadow transition-all flex items-center gap-1.5 border border-rose-500/40">
                            <svg class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                            Panduan PDF
                        </a>
                        <a href="<?= base_url('integrations/ojs/README.md') ?>" target="_blank" class="px-2 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-400 hover:text-slate-200 border border-slate-700 text-xs font-medium transition-colors" title="Buka Dokumentasi Markdown Online">
                            Docs
                        </a>
                    </div>
                </div>

                <!-- Banner Panduan Resmi OJS PDF -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 p-3.5 rounded-2xl bg-gradient-to-r from-rose-950/40 via-slate-900/80 to-rose-950/40 border border-rose-500/30 shadow-lg">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-rose-500/20 border border-rose-500/40 flex items-center justify-center text-lg flex-shrink-0">
                            📕
                        </div>
                        <div>
                            <h4 class="text-xs font-bold text-white flex items-center gap-1.5">
                                Buku Panduan Integrasi OJS 3.x (Format PDF Resolusi Tinggi - 8 Halaman)
                                <span class="px-1.5 py-0.5 rounded text-[9px] bg-rose-500/20 text-rose-300 font-semibold border border-rose-500/30">Siap Unduh</span>
                            </h4>
                            <p class="text-[11px] text-slate-300">Dilengkapi diagram alur kerja end-to-end, konsep mesin Dual-Lens, tangkapan layar panduan langkah demi langkah, dan contoh kode PHP siap pakai.</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 flex-shrink-0">
                        <a href="<?= base_url('integrations/ojs/Panduan_Integrasi_OJS_ARTilik.pdf') ?>" target="_blank" download="Panduan_Integrasi_OJS_ARTilik.pdf" class="px-3.5 py-2 rounded-xl bg-gradient-to-r from-rose-600 to-rose-700 hover:from-rose-500 hover:to-rose-600 text-white text-xs font-bold shadow-md hover:shadow-rose-600/30 transition-all flex items-center gap-2">
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                            Unduh Panduan PDF (1.9 MB)
                        </a>
                    </div>
                </div>

                <!-- Form Buat API Key Baru -->
                <div class="bg-gradient-to-r from-slate-900/90 via-slate-800/80 to-slate-900/90 border border-sky-500/30 rounded-2xl p-5 shadow-lg space-y-4">
                    <h3 class="text-sm font-bold text-white flex items-center gap-2">
                        <svg class="w-4 h-4 text-sky-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                        Buat API Key Baru untuk Jurnal / Sistem Eksternal
                    </h3>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div>
                            <label class="block text-[11px] font-medium text-slate-400 mb-1">Nama Jurnal / Aplikasi</label>
                            <input type="text" id="api-key-name" placeholder="mis: Jurnal Informatika SINTA 2" class="w-full bg-slate-900 border border-slate-700/80 rounded-lg px-3 py-2 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-sky-500">
                        </div>
                        <div>
                            <label class="block text-[11px] font-medium text-slate-400 mb-1">Tipe Sistem Target</label>
                            <select id="api-system-type" class="w-full bg-slate-900 border border-slate-700/80 rounded-lg px-3 py-2 text-xs text-white focus:outline-none focus:border-sky-500">
                                <option value="ojs" selected>Open Journal Systems (OJS 3.x)</option>
                                <option value="web">Sistem Akademik Kampus (Web / SIAKAD)</option>
                                <option value="integration">Repositori Institusi (EPrints / DSpace)</option>
                                <option value="cli">Automasi Skrip CLI / Python</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[11px] font-medium text-slate-400 mb-1">Batas Kuota Dokumen / Bulan</label>
                            <input type="number" id="api-quota-limit" value="1000" min="50" max="10000" class="w-full bg-slate-900 border border-slate-700/80 rounded-lg px-3 py-2 text-xs text-white focus:outline-none focus:border-sky-500">
                        </div>
                    </div>
                    <div class="flex justify-end">
                        <button onclick="handleGenerateApiKey()" id="btn-generate-key" class="px-4 py-2 rounded-xl bg-sky-600 hover:bg-sky-500 text-white text-xs font-semibold shadow transition-colors flex items-center gap-1.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path></svg>
                            Generate API Key
                        </button>
                    </div>

                    <!-- Modal / Alert API Key Baru -->
                    <div id="key-result-box" class="hidden bg-slate-950 border border-emerald-500/40 rounded-xl p-4 space-y-2">
                        <div class="text-xs font-bold text-emerald-400 flex items-center gap-1.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                            API Key Berhasil Dibuat! Simpan kunci ini sekarang:
                        </div>
                        <div class="flex items-center gap-2 bg-slate-900 p-2.5 rounded-lg border border-slate-800">
                            <code id="new-key-value" class="text-xs font-mono-code text-sky-300 flex-1 break-all"></code>
                            <button onclick="navigator.clipboard.writeText(document.getElementById('new-key-value').innerText); alert('API Key disalin ke clipboard!');" class="px-3 py-1 rounded bg-sky-600 hover:bg-sky-500 text-white text-[11px] font-semibold whitespace-nowrap">
                                Salin
                            </button>
                        </div>
                        <p class="text-[10px] text-slate-400">Kunci rahasia ini di-hash di basis data dan tidak akan ditampilkan secara utuh kembali demi keamanan.</p>
                    </div>
                </div>

                <!-- Tabel API Key Aktif -->
                <div class="bg-slate-800/40 border border-slate-700/60 rounded-2xl overflow-hidden shadow-sm">
                    <div class="px-5 py-3 border-b border-slate-700/60 flex items-center justify-between bg-slate-900/60">
                        <h3 class="text-xs font-bold uppercase tracking-wider text-slate-300">Daftar Kunci Integrasi Terdaftar</h3>
                        <span class="text-[11px] text-slate-400">Total <?= count($apiKeysList ?? []) ?> kunci aktif</span>
                    </div>

                    <!-- Mobile List: Tampilan Kartu Responsif API Keys (Layar Ponsel) -->
                    <div class="p-4 space-y-3.5 md:hidden">
                        <?php if (empty($apiKeysList)): ?>
                            <div class="bg-slate-800/40 border border-slate-700/60 rounded-2xl p-6 text-center text-xs text-slate-500">
                                Belum ada API Key yang dibuat.
                            </div>
                        <?php else: ?>
                            <?php foreach ($apiKeysList as $ak): ?>
                                <div class="bg-slate-800/60 border border-slate-700/70 rounded-2xl p-4 shadow-sm space-y-3">
                                    <div class="flex items-start justify-between gap-2">
                                        <div class="min-w-0 flex-1">
                                            <div class="font-bold text-white text-sm truncate"><?= esc($ak['key_name']) ?></div>
                                            <div class="text-[10px] text-slate-400 mt-0.5 truncate"><?= esc($ak['user_name'] ?? $ak['user_email'] ?? 'Admin') ?></div>
                                        </div>
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold <?= $ak['is_active'] ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-rose-500/10 text-rose-400 border border-rose-500/20' ?> shrink-0">
                                            <?= $ak['is_active'] ? 'Aktif' : 'Dicabut' ?>
                                        </span>
                                    </div>

                                    <div class="bg-slate-900/80 border border-slate-800 rounded-xl p-2.5 flex items-center justify-between text-xs">
                                        <span class="text-slate-400 text-[11px]">Prefix Identifikasi:</span>
                                        <span class="font-mono-code text-sky-400 text-[11px] font-semibold bg-slate-950 px-2 py-0.5 rounded border border-slate-800">
                                            <?= esc($ak['key_prefix']) ?>
                                        </span>
                                    </div>

                                    <div class="grid grid-cols-2 gap-2 text-xs bg-slate-900/40 border border-slate-800/60 rounded-xl p-2.5">
                                        <div>
                                            <div class="text-[10px] text-slate-500 uppercase tracking-wider">Sistem Target</div>
                                            <div class="mt-0.5">
                                                <span class="px-2 py-0.5 rounded text-[10px] bg-slate-800 text-slate-300 border border-slate-700 font-semibold inline-block">
                                                    <?= strtoupper(esc($ak['system_type'])) ?>
                                                </span>
                                            </div>
                                        </div>
                                        <div>
                                            <div class="text-[10px] text-slate-500 uppercase tracking-wider">Kuota Terpakai</div>
                                            <div class="mt-0.5 font-mono-code font-bold text-slate-200">
                                                <?= (int) $ak['quota_used'] ?> <span class="text-slate-500 font-normal">/ <?= (int) $ak['quota_limit'] ?></span>
                                            </div>
                                        </div>
                                        <div class="col-span-2 pt-1 border-t border-slate-800/60 text-[11px] text-slate-400 flex justify-between">
                                            <span>Penggunaan Terakhir:</span>
                                            <span class="font-mono-code text-slate-300"><?= $ak['last_used_at'] ? date('d M Y, H:i', strtotime($ak['last_used_at'])) : 'Belum pernah' ?></span>
                                        </div>
                                    </div>

                                    <?php if ($ak['is_active']): ?>
                                        <div class="pt-1">
                                            <button onclick="handleRevokeApiKey(<?= (int) $ak['id'] ?>)" class="w-full py-2 px-3 rounded-xl bg-rose-600/20 hover:bg-rose-600/30 text-rose-300 border border-rose-500/30 text-xs font-semibold flex items-center justify-center gap-1.5 transition-colors">
                                                <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path></svg>
                                                Cabut Kunci Integrasi
                                            </button>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <!-- Desktop Table View: Terlihat di Layar md ke atas -->
                    <div class="hidden md:block overflow-x-auto">
                        <table class="w-full text-xs text-left">
                            <thead class="bg-slate-900/80 text-slate-400 uppercase tracking-wider text-[10px] border-b border-slate-700/60">
                                <tr>
                                    <th class="py-2.5 px-4">Nama Kunci</th>
                                    <th class="py-2.5 px-3">Prefix Identifikasi</th>
                                    <th class="py-2.5 px-3">Sistem</th>
                                    <th class="py-2.5 px-3 text-center">Kuota Terpakai</th>
                                    <th class="py-2.5 px-3">Penggunaan Terakhir</th>
                                    <th class="py-2.5 px-3">Status</th>
                                    <th class="py-2.5 px-4 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-800/80">
                                <?php if (empty($apiKeysList)): ?>
                                    <tr><td colspan="7" class="py-6 text-center text-slate-500">Belum ada API Key yang dibuat.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($apiKeysList as $ak): ?>
                                        <tr class="hover:bg-slate-800/40 transition-colors">
                                            <td class="py-2.5 px-4">
                                                <div class="font-bold text-slate-200"><?= esc($ak['key_name']) ?></div>
                                                <div class="text-[10px] text-slate-400"><?= esc($ak['user_name'] ?? $ak['user_email'] ?? 'Admin') ?></div>
                                            </td>
                                            <td class="py-2.5 px-3 font-mono-code text-sky-400 text-[11px]">
                                                <?= esc($ak['key_prefix']) ?>
                                            </td>
                                            <td class="py-2.5 px-3">
                                                <span class="px-2 py-0.5 rounded text-[10px] bg-slate-800 text-slate-300 border border-slate-700">
                                                    <?= strtoupper(esc($ak['system_type'])) ?>
                                                </span>
                                            </td>
                                            <td class="py-2.5 px-3 text-center font-mono-code text-slate-200">
                                                <strong><?= (int) $ak['quota_used'] ?></strong> / <?= (int) $ak['quota_limit'] ?>
                                            </td>
                                            <td class="py-2.5 px-3 text-slate-400 text-[11px] font-mono-code">
                                                <?= $ak['last_used_at'] ? date('d M H:i', strtotime($ak['last_used_at'])) : 'Belum pernah' ?>
                                            </td>
                                            <td class="py-2.5 px-3">
                                                <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold <?= $ak['is_active'] ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-rose-500/10 text-rose-400 border border-rose-500/20' ?>">
                                                    <?= $ak['is_active'] ? 'Aktif' : 'Dicabut' ?>
                                                </span>
                                            </td>
                                            <td class="py-2.5 px-4 text-right">
                                                <?php if ($ak['is_active']): ?>
                                                    <button onclick="handleRevokeApiKey(<?= (int) $ak['id'] ?>)" class="px-2 py-1 rounded bg-rose-600/20 hover:bg-rose-600/30 text-rose-300 border border-rose-500/30 text-[10px] transition-colors">
                                                        Cabut Kunci
                                                    </button>
                                                <?php else: ?>
                                                    <span class="text-slate-500 text-[10px] italic">Nonaktif</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Card Spesifikasi REST API & Quick Start -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                    <div class="bg-slate-800/40 border border-slate-700/60 rounded-2xl p-5 space-y-3">
                        <div class="flex items-center justify-between border-b border-slate-700/60 pb-2">
                            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-300">Contoh Request cURL REST API v1</h3>
                            <span class="text-[10px] text-sky-400 font-mono-code">POST /api/v1/check</span>
                        </div>
                        <pre class="bg-slate-950 p-3 rounded-xl border border-slate-800 text-[11px] font-mono-code text-sky-300 overflow-x-auto leading-relaxed">curl -X POST https://tilik.arsetontong.top/api/v1/check \
  -H "X-API-Key: artilik_ojs_YOUR_KEY" \
  -F "document=@/path/to/manuscript.pdf" \
  -F "title=Judul Naskah Ilmiah" \
  -F "author=Nama Penulis" \
  -F "ojs_submission_id=1240"</pre>
                        <p class="text-[11px] text-slate-400 leading-normal">
                            Endpoint menerima berkas PDF/DOCX maksimal 30 MB. Autentikasi menggunakan header <code>X-API-Key</code> atau <code>Authorization: Bearer &lt;key&gt;</code>.
                        </p>
                    </div>

                    <div class="bg-slate-800/40 border border-slate-700/60 rounded-2xl p-5 space-y-3">
                        <div class="flex items-center justify-between border-b border-slate-700/60 pb-2">
                            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-300">Uji End-to-End Termurah (Tanpa OJS)</h3>
                            <span class="px-2 py-0.5 rounded text-[10px] bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 font-semibold">Mode Polling</span>
                        </div>
                        <pre class="bg-slate-950 p-3 rounded-xl border border-slate-800 text-[11px] font-mono-code text-emerald-300 overflow-x-auto leading-relaxed">export ARTILIK_API_KEY="artilik_ojs_YOUR_KEY"
php artilik_ojs_runner.php /path/to/sample.pdf "Judul Artikel" "Dr. Penulis"</pre>
                        <p class="text-[11px] text-slate-400 leading-normal">
                            <strong>Localhost Friendly:</strong> Skrip runner menggunakan <em>outbound client polling</em> (<code>waitForCompletion</code>), sehingga <strong>100% tidak memerlukan Public IP, Port Forwarding, atau Tunnel</strong>. Sangat ideal untuk pengujian lokal di laptop atau container Docker PKP.
                        </p>
                    </div>
                </div>
            </div>

        <!-- ========================================== -->
        <!-- TAB 6B: GOOGLE AI / GEMINI KEYS POOL       -->
        <!-- ========================================== -->
        <?php elseif ($activeTab === 'google_keys'): ?>
            <div class="space-y-6">
                <!-- Alerting AI Degradation (Jika Kunci Google Mengalami 503 Massal) -->
                <?php if (!empty($googleAiDegraded) || (!empty($keys503Count) && $keys503Count >= 3)): ?>
                    <div class="bg-amber-950/40 border border-amber-500/40 rounded-2xl p-4 shadow-md flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                        <div class="flex items-start gap-3">
                            <div class="p-2 rounded-xl bg-amber-500/20 text-amber-400 border border-amber-500/30 shrink-0 mt-0.5">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                            </div>
                            <div>
                                <h3 class="text-sm font-bold text-amber-200 flex items-center gap-2">
                                    Peringatan Operasional: Upstream Google 503 High Demand
                                    <span class="px-2 py-0.5 rounded-full text-[10px] bg-amber-500/20 text-amber-300 border border-amber-500/30 font-semibold font-mono-code"><?= $keys503Count ?> Kunci Terdampak</span>
                                </h3>
                                <p class="text-xs text-slate-300 mt-0.5">
                                    Tercatat <?= $keys503Count ?> kunci aktif menerima pesan kesalahan <code>503: This model is currently experiencing high demand</code> dari Google AI Studio. Antrean worker tetap memproses kemiripan heuristik TF-IDF &amp; Levenshtein secara independen, dan mencoba kunci lain secara rotasi round-robin. Disarankan menambah kunci cadangan dari project Google Cloud berbeda.
                                </p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Header -->
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                    <div>
                        <h2 class="text-lg font-bold text-white flex items-center gap-2">
                            Kumpulan Kunci AI Gateway (Groq L1 &amp; Gemini L2)
                            <span class="px-2 py-0.5 rounded text-[10px] bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">Free Tier Ready</span>
                        </h2>
                        <p class="text-xs text-slate-400">Rotasi otomatis multi-lapis: Groq Cloud L1 (Utama, latensi ultra-cepat) auto-failover ke Google Gemini L2 saat kuota habis atau rate-limited</p>
                    </div>
                </div>

                <!-- KPI Metric Cards Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <!-- Total Keys -->
                    <div class="bg-slate-800/60 border border-slate-700/60 rounded-2xl p-5 shadow-sm">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Total Kunci Terdaftar</span>
                            <div class="p-2 rounded-xl bg-indigo-500/10 text-indigo-400 border border-indigo-500/20">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path></svg>
                            </div>
                        </div>
                        <div class="mt-3 flex items-baseline gap-2">
                            <span class="text-3xl font-extrabold text-white tracking-tight"><?= count($googleKeysList ?? []) ?></span>
                            <span class="text-xs text-slate-400 font-medium">akun pool</span>
                        </div>
                        <div class="mt-2 text-xs text-slate-400">
                            <span>Mendukung Groq Cloud &amp; Google AI Studio</span>
                        </div>
                    </div>

                    <!-- Active Keys -->
                    <div class="bg-slate-800/60 border border-slate-700/60 rounded-2xl p-5 shadow-sm">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Kunci Aktif (Siaga)</span>
                            <div class="p-2 rounded-xl bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            </div>
                        </div>
                        <div class="mt-3 flex items-baseline gap-2">
                            <span class="text-3xl font-extrabold text-emerald-400 tracking-tight"><?= (int) ($activeGoogleKeysCount ?? 0) ?></span>
                            <span class="text-xs text-emerald-300 font-medium">siap rotasi</span>
                        </div>
                        <div class="mt-2 text-xs text-slate-400">
                            <span>Tersedia untuk rotasi round-robin worker</span>
                        </div>
                    </div>

                    <!-- Cooldown / Rate-Limited Keys -->
                    <?php 
                        $rateLimitedCount = count(array_filter($googleKeysList ?? [], fn($k) => $k['status'] === 'rate_limited'));
                    ?>
                    <div class="bg-slate-800/60 border border-slate-700/60 rounded-2xl p-5 shadow-sm">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Status Cooldown (429)</span>
                            <div class="p-2 rounded-xl <?= $rateLimitedCount > 0 ? 'bg-amber-500/10 text-amber-400 border border-amber-500/20' : 'bg-slate-700/20 text-slate-400 border border-slate-700/30' ?>">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            </div>
                        </div>
                        <div class="mt-3 flex items-baseline gap-2">
                            <span class="text-3xl font-extrabold <?= $rateLimitedCount > 0 ? 'text-amber-400' : 'text-slate-300' ?> tracking-tight"><?= $rateLimitedCount ?></span>
                            <span class="text-xs text-amber-300 font-medium">sedang jeda</span>
                        </div>
                        <div class="mt-2 text-xs text-slate-400">
                            <span>Pulih otomatis setelah waktu jeda selesai</span>
                        </div>
                    </div>

                    <!-- Total Usage Calls -->
                    <?php 
                        $totalCalls = array_sum(array_column($googleKeysList ?? [], 'usage_count'));
                    ?>
                    <div class="bg-slate-800/60 border border-slate-700/60 rounded-2xl p-5 shadow-sm">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Total Audit Sukses</span>
                            <div class="p-2 rounded-xl bg-sky-500/10 text-sky-400 border border-sky-500/20">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                            </div>
                        </div>
                        <div class="mt-3 flex items-baseline gap-2">
                            <span class="text-3xl font-extrabold text-sky-400 tracking-tight"><?= number_format($totalCalls) ?></span>
                            <span class="text-xs text-sky-300 font-medium">panggilan</span>
                        </div>
                        <div class="mt-2 text-xs text-slate-400">
                            <span>Kumulatif audit semantik berhasil</span>
                        </div>
                    </div>
                </div>

                <!-- Form Tambah Kunci AI Baru (Groq L1 & Google Gemini L2) -->
                <div class="bg-gradient-to-r from-slate-900/90 via-slate-800/80 to-slate-900/90 border border-emerald-500/30 rounded-2xl p-5 shadow-lg space-y-4">
                    <h3 class="text-sm font-bold text-white flex items-center gap-2">
                        <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                        Daftarkan Kunci API Baru ke Kumpulan Rotasi AI Gateway (Groq &amp; Gemini)
                    </h3>
                    <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">
                        <div>
                            <label class="block text-[11px] font-medium text-slate-400 mb-1">Penyedia AI (Provider)</label>
                            <select id="google-provider" onchange="handleProviderChange()" class="w-full bg-slate-900 border border-indigo-500/50 rounded-lg px-3 py-2 text-xs text-white focus:outline-none focus:border-indigo-400 font-medium">
                                <option value="groq" selected>⚡ Groq Cloud (Lapis 1 Utama)</option>
                                <option value="gemini">✨ Google Gemini (Lapis 2 Fallback)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[11px] font-medium text-slate-400 mb-1">Label Akun / Nama Kunci</label>
                            <input type="text" id="google-key-name" placeholder="mis: Groq Primer (Akun 1)" class="w-full bg-slate-900 border border-slate-700/80 rounded-lg px-3 py-2 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-emerald-500">
                        </div>
                        <div>
                            <label class="block text-[11px] font-medium text-slate-400 mb-1">API Key</label>
                            <input type="password" id="google-api-key" placeholder="gsk_... atau AIzaSy..." oninput="handleKeyInputAutoDetect()" class="w-full bg-slate-900 border border-slate-700/80 rounded-lg px-3 py-2 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-emerald-500 font-mono-code">
                        </div>
                        <div>
                            <label class="block text-[11px] font-medium text-slate-400 mb-1">Pilihan Model AI Default</label>
                            <select id="google-model-name" class="w-full bg-slate-900 border border-indigo-500/50 rounded-lg px-3 py-2 text-xs text-white focus:outline-none focus:border-indigo-400 font-medium">
                                <option value="qwen/qwen3.8-27b" selected>Qwen 3.8 27B (Groq - Rekomendasi Utama L1)</option>
                                <option value="openai/gpt-oss-120b">OpenAI GPT-OSS 120B (Groq - Deep Reasoning)</option>
                                <option value="openai/gpt-oss-20b">OpenAI GPT-OSS 20B (Groq - Fast Reasoning)</option>
                                <option value="allam-2-7b">ALLaM 2 7B (Groq)</option>
                            </select>
                        </div>
                    </div>
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pt-3 border-t border-slate-800/80">
                        <label class="flex items-center gap-2.5 cursor-pointer select-none">
                            <input type="checkbox" id="google-send-notify" checked class="w-4 h-4 rounded text-indigo-600 bg-slate-900 border-slate-700 focus:ring-indigo-500">
                            <div>
                                <span class="text-xs font-semibold text-slate-200">Kirim Analisis Hasil Audit AI Otomatis</span>
                                <span class="block text-[10px] text-slate-400">Terima ringkasan notifikasi duplikasi &amp; forensik semantik AI via Email/Telegram</span>
                            </div>
                        </label>
                        <button onclick="handleCreateGoogleKey()" id="btn-add-google-key" class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-semibold shadow transition-colors flex items-center gap-1.5 cursor-pointer self-end sm:self-auto">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                            Tambahkan ke Pool Rotasi
                        </button>
                    </div>
                </div>

                <!-- Table Daftar Google API Keys -->
                <div class="bg-slate-800/40 border border-slate-700/60 rounded-2xl p-5 space-y-4">
                    <div class="flex items-center justify-between">
                        <h3 class="text-sm font-bold text-white flex items-center gap-2">
                            <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                            Daftar Kunci AI Terdaftar (Groq L1 &amp; Gemini L2)
                        </h3>
                        <span class="text-xs text-slate-400">Total: <strong class="text-slate-200"><?= count($googleKeysList ?? []) ?></strong> kunci</span>
                    </div>

                    <?php 
                        $modelDisplayNames = [
                            'qwen/qwen3.8-27b'         => 'Qwen 3.8 27B (Groq - Rekomendasi Utama L1)',
                            'openai/gpt-oss-120b'      => 'OpenAI GPT-OSS 120B (Groq - Deep Reasoning)',
                            'openai/gpt-oss-20b'       => 'OpenAI GPT-OSS 20B (Groq - Fast Reasoning)',
                            'allam-2-7b'               => 'ALLaM 2 7B (Groq)',
                            'llama-3.3-70b-versatile'  => 'Llama 3.3 70B (Legacy Groq)',
                            'llama-3.1-8b-instant'     => 'Llama 3.1 8B (Legacy Groq)',
                            'mixtral-8x7b-32768'       => 'Mixtral 8x7B (Groq)',
                            'gemma2-9b-it'             => 'Gemma 2 9B (Groq)',
                            'qwen-qwq-32b'             => 'Qwen QWQ 32B (Groq)',
                            'gemini-flash-lite-latest' => 'Gemini Flash Lite (Bebas Antrean - Utama L2)',
                            'gemini-3.5-flash-lite'   => 'Gemini 3.5 Flash Lite (Cepat & Stabil)',
                            'gemini-3.5-flash'         => 'Gemini 3.5 Flash (Standar)',
                            'gemini-3.6-flash'         => 'Gemini 3.6 Flash (Terbaru 2026)',
                            'gemini-3.0-flash'         => 'Gemini 3.0 Flash (Cepat & Efisien)',
                            'gemini-1.5-flash'         => 'Gemini 1.5 Flash (Legacy Stabil)',
                            'gemini-1.5-pro'           => 'Gemini 1.5 Pro (Legacy Pro)',
                        ];
                    ?>

                    <!-- Mobile List: Tampilan Kartu Responsif Google AI Keys (Layar Ponsel) -->
                    <div class="space-y-3.5 md:hidden">
                        <?php if (empty($googleKeysList)): ?>
                            <div class="bg-slate-800/40 border border-slate-700/60 rounded-2xl p-6 text-center text-xs text-slate-500">
                                Belum ada Kunci API terdaftar. Silakan tambahkan Kunci Groq atau Gemini melalui formulir di atas.
                            </div>
                        <?php else: ?>
                            <?php foreach ($googleKeysList as $k): ?>
                                <div class="bg-slate-800/60 border border-slate-700/70 rounded-2xl p-4 shadow-sm space-y-3">
                                    <!-- Top Row: Name, Status Pill & Model -->
                                    <div class="flex items-start justify-between gap-2">
                                        <div class="min-w-0 flex-1">
                                            <div class="flex items-center gap-1.5">
                                                <div class="font-bold text-white text-sm truncate"><?= esc($k['key_name']) ?></div>
                                                <?php if (($k['provider'] ?? 'gemini') === 'groq'): ?>
                                                    <span class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-purple-500/20 text-purple-300 border border-purple-500/30 shrink-0">Groq L1</span>
                                                <?php else: ?>
                                                    <span class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-blue-500/20 text-blue-300 border border-blue-500/30 shrink-0">Gemini L2</span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="text-[11px] text-indigo-300 font-mono-code mt-0.5 truncate">
                                                <?= esc($modelDisplayNames[$k['model_name']] ?? $k['model_name']) ?>
                                            </div>
                                        </div>
                                        <!-- Status Badge -->
                                        <?php if ($k['status'] === 'active'): ?>
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 flex items-center gap-1 shrink-0">
                                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                                                Aktif
                                            </span>
                                        <?php elseif ($k['status'] === 'rate_limited'): ?>
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-amber-500/20 text-amber-300 border border-amber-500/30 flex items-center gap-1 shrink-0" title="<?= esc($k['last_error_message'] ?? 'Rate limit') ?>">
                                                <span class="w-1.5 h-1.5 rounded-full bg-amber-400 animate-pulse"></span>
                                                Jeda
                                            </span>
                                        <?php elseif ($k['status'] === 'exhausted'): ?>
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-rose-500/20 text-rose-300 border border-rose-500/30 flex items-center gap-1 shrink-0">
                                                <span class="w-1.5 h-1.5 rounded-full bg-rose-400"></span>
                                                Habis
                                            </span>
                                        <?php else: ?>
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-slate-700/40 text-slate-400 border border-slate-700/60 shrink-0">
                                                Nonaktif
                                            </span>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Masked Key & Error (if any) -->
                                    <div class="bg-slate-900/80 border border-slate-800 rounded-xl p-2.5 space-y-1.5">
                                        <div class="flex items-center justify-between text-[11px]">
                                            <span class="text-slate-400">Masked Key:</span>
                                            <span class="font-mono-code text-slate-200 bg-slate-950 px-2 py-0.5 rounded border border-slate-800/80">
                                                <?= esc($k['masked_key'] ?? (substr($k['api_key'] ?? '', 0, 8) . '...' . substr($k['api_key'] ?? '', -4))) ?>
                                            </span>
                                        </div>
                                        <?php if (!empty($k['last_error_message'])): ?>
                                            <div class="text-[10px] text-rose-400/90 pt-1 border-t border-slate-800/60 truncate" title="<?= esc($k['last_error_message']) ?>">
                                                ⚠️ <?= esc($k['last_error_message']) ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <!-- 2-Column Stats Grid -->
                                    <div class="grid grid-cols-2 gap-2 text-xs bg-slate-900/40 border border-slate-800/60 rounded-xl p-2.5">
                                        <div>
                                            <div class="text-[10px] text-slate-500 uppercase tracking-wider">Panggilan Audit</div>
                                            <div class="mt-0.5 font-mono-code font-bold text-slate-200">
                                                <span class="text-emerald-400"><?= (int) $k['usage_count'] ?></span> sukses
                                                <?php if ((int)$k['error_count'] > 0): ?>
                                                    <span class="text-rose-400">/ <?= (int) $k['error_count'] ?> err</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <div>
                                            <div class="text-[10px] text-slate-500 uppercase tracking-wider">Terakhir Digunakan</div>
                                            <div class="mt-0.5 text-slate-300 text-[11px] truncate">
                                                <?= !empty($k['last_used_at']) ? esc(date('d M, H:i', strtotime($k['last_used_at']))) : 'Belum pernah' ?>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Action Button Grid (3 Touch-Friendly Buttons) -->
                                    <div class="grid grid-cols-3 gap-2 pt-1">
                                        <button onclick="handleTestGoogleKey(<?= $k['id'] ?>, this)" class="py-2 px-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700/80 text-xs font-semibold flex items-center justify-center gap-1 transition-colors">
                                            <svg class="w-3.5 h-3.5 text-emerald-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                                            <span>Uji</span>
                                        </button>
                                        <button onclick="handleToggleGoogleKey(<?= $k['id'] ?>)" class="py-2 px-2 rounded-xl <?= $k['status'] === 'active' ? 'bg-amber-900/30 hover:bg-amber-900/50 text-amber-300 border border-amber-600/40' : 'bg-emerald-900/30 hover:bg-emerald-900/50 text-emerald-300 border border-emerald-600/40' ?> text-xs font-semibold flex items-center justify-center transition-colors">
                                            <?= $k['status'] === 'active' ? 'Nonaktif' : 'Aktifkan' ?>
                                        </button>
                                        <button onclick="handleDeleteGoogleKey(<?= $k['id'] ?>, '<?= esc($k['key_name']) ?>')" class="py-2 px-2 rounded-xl bg-rose-950/40 hover:bg-rose-900/60 text-rose-300 border border-rose-600/40 text-xs font-semibold flex items-center justify-center gap-1 transition-colors">
                                            <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                            <span>Hapus</span>
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <!-- Desktop Table View: Terlihat di Layar md ke atas -->
                    <div class="hidden md:block overflow-x-auto">
                        <table class="w-full text-xs text-left">
                            <thead class="text-slate-400 uppercase tracking-wider border-b border-slate-700/60 text-[10px]">
                                <tr>
                                    <th class="py-2.5 px-3">Label &amp; Model</th>
                                    <th class="py-2.5 px-3">Kunci (Masked)</th>
                                    <th class="py-2.5 px-3">Status</th>
                                    <th class="py-2.5 px-3">Panggilan &amp; Error</th>
                                    <th class="py-2.5 px-3">Terakhir Digunakan</th>
                                    <th class="py-2.5 px-3 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-800/80">
                                <?php if (empty($googleKeysList)): ?>
                                    <tr>
                                        <td colspan="6" class="py-8 text-center text-slate-500">
                                            Belum ada Kunci API terdaftar. Silakan tambahkan Kunci Groq (Lapis 1) atau Gemini (Lapis 2) melalui formulir di atas.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($googleKeysList as $k): ?>
                                        <tr class="hover:bg-slate-800/40 transition-colors">
                                            <td class="py-3 px-3">
                                                <div class="flex items-center gap-1.5">
                                                    <span class="font-semibold text-white"><?= esc($k['key_name']) ?></span>
                                                    <?php if (($k['provider'] ?? 'gemini') === 'groq'): ?>
                                                        <span class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-purple-500/20 text-purple-300 border border-purple-500/30">Groq L1</span>
                                                    <?php else: ?>
                                                        <span class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-blue-500/20 text-blue-300 border border-blue-500/30">Gemini L2</span>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="text-[10px] text-indigo-300 font-mono-code"><?= esc($modelDisplayNames[$k['model_name']] ?? $k['model_name']) ?></div>
                                            </td>
                                            <td class="py-3 px-3">
                                                <span class="font-mono-code text-[11px] text-slate-300 bg-slate-900 px-2 py-1 rounded border border-slate-800">
                                                    <?= esc($k['masked_key'] ?? (substr($k['api_key'] ?? '', 0, 8) . '...' . substr($k['api_key'] ?? '', -4))) ?>
                                                </span>
                                            </td>
                                            <td class="py-3 px-3">
                                                <?php if ($k['status'] === 'active'): ?>
                                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 flex items-center gap-1 w-max">
                                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                                                        Aktif Siaga
                                                    </span>
                                                <?php elseif ($k['status'] === 'rate_limited'): ?>
                                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-amber-500/20 text-amber-300 border border-amber-500/30 flex items-center gap-1 w-max" title="<?= esc($k['last_error_message'] ?? 'Rate limit') ?>">
                                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-400 animate-pulse"></span>
                                                        Dijeda s.d. <?= esc(substr($k['rate_limited_until'] ?? '', 11, 8)) ?>
                                                    </span>
                                                <?php elseif ($k['status'] === 'exhausted'): ?>
                                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-rose-500/20 text-rose-300 border border-rose-500/30 flex items-center gap-1 w-max">
                                                        <span class="w-1.5 h-1.5 rounded-full bg-rose-400"></span>
                                                        Habis Kuota
                                                    </span>
                                                <?php else: ?>
                                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-slate-700/40 text-slate-400 border border-slate-700/60 flex items-center gap-1 w-max">
                                                        Nonaktif
                                                    </span>
                                                <?php endif; ?>
                                                <?php if (!empty($k['last_error_message'])): ?>
                                                    <div class="text-[10px] text-rose-400/80 truncate max-w-[200px] mt-0.5" title="<?= esc($k['last_error_message']) ?>">
                                                        <?= esc($k['last_error_message']) ?>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td class="py-3 px-3 font-mono-code text-[11px]">
                                                <span class="text-emerald-400 font-bold"><?= (int) $k['usage_count'] ?></span> sukses
                                                <?php if ((int)$k['error_count'] > 0): ?>
                                                    / <span class="text-rose-400"><?= (int) $k['error_count'] ?></span> err
                                                <?php endif; ?>
                                            </td>
                                            <td class="py-3 px-3 text-slate-400 text-[11px]">
                                                <?= !empty($k['last_used_at']) ? esc(date('d M H:i', strtotime($k['last_used_at']))) : '-' ?>
                                            </td>
                                            <td class="py-3 px-3 text-right whitespace-nowrap min-w-[190px]">
                                                <div class="flex items-center justify-end gap-1.5">
                                                    <button onclick="handleTestGoogleKey(<?= $k['id'] ?>, this)" class="px-2 py-1 rounded bg-slate-700 hover:bg-slate-600 text-[11px] text-slate-200 transition-colors flex items-center gap-1 cursor-pointer" title="Kirim ping prompt uji koneksi ke Google API">
                                                        <svg class="w-3 h-3 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                                                        Uji
                                                    </button>
                                                    <button onclick="handleToggleGoogleKey(<?= $k['id'] ?>)" class="px-2 py-1 rounded <?= $k['status'] === 'active' ? 'bg-amber-900/40 hover:bg-amber-900/60 text-amber-300 border border-amber-600/40' : 'bg-emerald-900/40 hover:bg-emerald-900/60 text-emerald-300 border border-emerald-600/40' ?> text-[11px] transition-colors cursor-pointer">
                                                        <?= $k['status'] === 'active' ? 'Nonaktifkan' : 'Aktifkan' ?>
                                                    </button>
                                                    <button onclick="handleDeleteGoogleKey(<?= $k['id'] ?>, '<?= esc($k['key_name']) ?>')" class="px-2 py-1 rounded bg-rose-950/40 hover:bg-rose-900/60 text-rose-300 border border-rose-600/40 text-[11px] transition-colors cursor-pointer" title="Hapus Kunci">
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

                <!-- Petunjuk & Kebijakan Free Tier Google AI Studio -->
                <div class="bg-slate-900/80 border border-slate-800 rounded-2xl p-5 space-y-3">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-emerald-400 flex items-center gap-1.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        Pedoman Kapasitas &amp; Cara Kerja Rotasi Round-Robin
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3 text-xs text-slate-300">
                        <div class="p-3 rounded-xl bg-slate-950/60 border border-slate-800/80 space-y-1">
                            <strong class="text-white block">1. Batas Kuota Free Tier</strong>
                            <p class="text-slate-400 text-[11px] leading-relaxed">
                                Setiap Google API Key Free Tier menyediakan 15 RPM (permintaan per menit) dan 1.500 RPD (permintaan per hari). Layanan ini sepenuhnya gratis tanpa perlu kartu kredit.
                            </p>
                        </div>
                        <div class="p-3 rounded-xl bg-slate-950/60 border border-slate-800/80 space-y-1">
                            <strong class="text-white block">2. Rotasi Otomatis (Round-Robin)</strong>
                            <p class="text-slate-400 text-[11px] leading-relaxed">
                                Worker AR Tilik memanggil kunci dengan urutan waktu penggunaan terlama. Setiap kunci menerima beban secara merata sehingga tidak ada kunci yang cepat habis kuotanya.
                            </p>
                        </div>
                        <div class="p-3 rounded-xl bg-slate-950/60 border border-slate-800/80 space-y-1">
                            <strong class="text-white block">3. Failover &amp; Cooldown 60 Detik</strong>
                            <p class="text-slate-400 text-[11px] leading-relaxed">
                                Jika satu kunci mengalami limit (HTTP 429), kunci tersebut langsung dijeda selama 60 detik dan worker segera melanjutkan analisis menggunakan kunci berikutnya tanpa interupsi.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

        <!-- ========================================== -->
        <!-- TAB: FEEDBACK & TESTIMONI (FASE 1)         -->
        <!-- ========================================== -->
        <?php elseif ($activeTab === 'feedback'): ?>
            <div class="space-y-6">
                <!-- Header -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-bold text-white flex items-center gap-2">
                            <span>🌟 Evaluasi Kinerja Fitur &amp; Moderasi Testimoni</span>
                            <span class="text-[10px] uppercase font-bold tracking-wider px-2 py-0.5 rounded-full bg-amber-500/20 text-amber-300 border border-amber-500/30 font-mono-code">Fase 1 Growth</span>
                        </h2>
                        <p class="text-xs text-slate-400 mt-0.5">
                            Menganalisis penilaian performa fitur di mata pengguna (Voting Score), mengevaluasi catatan kualitatif, serta memoderasi testimoni publik untuk Landing Page.
                        </p>
                    </div>
                </div>

                <!-- 4 KPI Metrics Cards -->
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 sm:gap-4">
                    <div class="bg-slate-800/40 border border-slate-700/60 rounded-2xl p-4 space-y-1">
                        <div class="text-[11px] font-semibold text-slate-400">Rata-rata Rating</div>
                        <div class="text-2xl font-black text-amber-400 font-mono-code flex items-center gap-1">
                            <span><?= number_format($testimonialStats['avg_rating'] ?? 5.0, 1) ?></span>
                            <span class="text-base text-amber-400">★</span>
                        </div>
                        <div class="text-[10px] text-slate-400">Kepuasan pemeriksaan naskah</div>
                    </div>
                    <div class="bg-slate-800/40 border border-slate-700/60 rounded-2xl p-4 space-y-1">
                        <div class="text-[11px] font-semibold text-slate-400">Total Ulasan Masuk</div>
                        <div class="text-2xl font-black text-white font-mono-code">
                            <?= (int) ($testimonialStats['total_reviews'] ?? 0) ?>
                        </div>
                        <div class="text-[10px] text-slate-400">Seluruh ulasan terkirim</div>
                    </div>
                    <div class="bg-slate-800/40 border border-slate-700/60 rounded-2xl p-4 space-y-1">
                        <div class="text-[11px] font-semibold text-slate-400">Tayang di Publik</div>
                        <div class="text-2xl font-black text-emerald-400 font-mono-code">
                            <?= (int) ($testimonialStats['count_approved'] ?? 0) ?>
                        </div>
                        <div class="text-[10px] text-emerald-400/80">Disetujui untuk Landing Page</div>
                    </div>
                    <div class="bg-slate-800/40 border border-slate-700/60 rounded-2xl p-4 space-y-1">
                        <div class="text-[11px] font-semibold text-slate-400">Menunggu Moderasi</div>
                        <div class="text-2xl font-black <?= ($testimonialStats['count_pending'] ?? 0) > 0 ? 'text-amber-400' : 'text-slate-400' ?> font-mono-code">
                            <?= (int) ($testimonialStats['count_pending'] ?? 0) ?>
                        </div>
                        <div class="text-[10px] text-slate-400">Perlu ditinjau admin</div>
                    </div>
                </div>

                <!-- Bagian A: Kinerja Fitur (Feature Voting Score) -->
                <div class="bg-slate-800/40 border border-slate-700/60 rounded-2xl p-5 space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-700/60 pb-3">
                        <div>
                            <h3 class="text-sm font-bold text-white flex items-center gap-2">
                                <span>🗳️ Skor Voting &amp; Kepuasan Fitur (Micro-Feedback)</span>
                            </h3>
                            <p class="text-xs text-slate-400 mt-0.5">Respon pengguna per fitur: memantau fitur mana yang paling membantu vs yang perlu ditingkatkan</p>
                        </div>
                    </div>

                    <?php if (empty($featureStats)): ?>
                        <div class="p-6 text-center text-xs text-slate-500 bg-slate-900/40 rounded-xl border border-slate-800">
                            Belum ada rekaman voting fitur dari pengguna.
                        </div>
                    <?php else: ?>
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                            <?php foreach ($featureStats as $fs): ?>
                                <?php 
                                    $fNameLabel = match($fs['feature_name']) {
                                        'audit_forensik'     => '✨ Audit Forensik Cerdas',
                                        'pertanyaan_sidang'  => '🎯 Pertanyaan Sidang Lisan',
                                        'cross_lingual'      => '🌐 Kemiripan Lintas Bahasa',
                                        'dual_lens'          => '🧬 Dual-Lens Matcher',
                                        'pdf_export'         => '📄 Cetak / Ekspor PDF',
                                        default              => ucwords(str_replace('_', ' ', $fs['feature_name']))
                                    };
                                    $satRate = (float)($fs['satisfaction_rate'] ?? 100.0);
                                ?>
                                <div class="bg-slate-900/60 border border-slate-800 rounded-xl p-3.5 space-y-2.5">
                                    <div class="flex items-center justify-between">
                                        <span class="text-xs font-bold text-slate-200"><?= esc($fNameLabel) ?></span>
                                        <span class="text-[11px] font-mono-code font-bold px-2 py-0.5 rounded <?= $satRate >= 80 ? 'bg-emerald-500/20 text-emerald-300' : ($satRate >= 60 ? 'bg-amber-500/20 text-amber-300' : 'bg-rose-500/20 text-rose-300') ?>">
                                            <?= number_format($satRate, 1) ?>% Puas
                                        </span>
                                    </div>
                                    <!-- Progress Bar -->
                                    <div class="w-full bg-slate-800 h-2 rounded-full overflow-hidden flex">
                                        <div class="bg-emerald-500 h-full" style="width: <?= $satRate ?>%"></div>
                                        <div class="bg-amber-500 h-full" style="width: <?= 100 - $satRate ?>%"></div>
                                    </div>
                                    <div class="flex justify-between items-center text-[10px] text-slate-400">
                                        <span>Total: <strong><?= (int)$fs['total_votes'] ?> suara</strong></span>
                                        <span class="space-x-2">
                                            <span class="text-emerald-400">👍 <?= (int)$fs['upvotes'] ?></span>
                                            <span class="text-amber-400">👎 <?= (int)$fs['downvotes'] ?></span>
                                        </span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Catatan Kualitatif Pengguna Terbaru -->
                    <?php if (!empty($recentFeedback)): ?>
                        <div class="mt-4 pt-3 border-t border-slate-700/60 space-y-2">
                            <h4 class="text-xs font-bold uppercase tracking-wider text-slate-400">Catatan &amp; Masukan Kualitatif Pengguna</h4>
                            <div class="space-y-2 max-h-60 overflow-y-auto pr-1">
                                <?php foreach ($recentFeedback as $fb): ?>
                                    <div class="p-3 bg-slate-950/60 border border-slate-800 rounded-xl text-xs space-y-1">
                                        <div class="flex items-center justify-between text-[11px]">
                                            <span class="font-bold text-indigo-300">
                                                <?= (int)$fb['vote_value'] === 1 ? '👍' : '👎' ?> <?= esc($fb['feature_name']) ?>
                                                <span class="text-slate-400 font-normal">&bull; <?= esc($fb['user_name'] ?? 'Tamu') ?> (<?= esc($fb['institution'] ?? 'Institusi') ?>)</span>
                                            </span>
                                            <span class="text-slate-500"><?= date('d M Y, H:i', strtotime($fb['created_at'])) ?></span>
                                        </div>
                                        <p class="text-slate-300 italic">"<?= esc($fb['feedback_text']) ?>"</p>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Bagian B: Moderasi Testimoni Akademik -->
                <div class="bg-slate-800/40 border border-slate-700/60 rounded-2xl p-5 space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-700/60 pb-3 flex-wrap gap-2">
                        <div>
                            <h3 class="text-sm font-bold text-white flex items-center gap-2">
                                <span>💬 Moderasi Testimoni &amp; Bukti Nyata Publik</span>
                                <span class="text-[10px] font-mono-code px-2 py-0.5 rounded bg-slate-700 text-slate-300">
                                    <?= count($testimonialsList ?? []) ?> total
                                </span>
                            </h3>
                            <p class="text-xs text-slate-400 mt-0.5">Pilih testimoni yang memenuhi etika akademik untuk ditampilkan pada Landing Page</p>
                        </div>
                    </div>

                    <?php if (empty($testimonialsList)): ?>
                        <div class="p-8 text-center text-xs text-slate-500 bg-slate-900/40 rounded-xl border border-slate-800">
                            Belum ada testimoni yang dikirimkan oleh pengguna.
                        </div>
                    <?php else: ?>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-xs text-slate-300">
                                <thead class="text-[10px] font-bold uppercase tracking-wider text-slate-400 bg-slate-900/80 border-b border-slate-700/60">
                                    <tr>
                                        <th class="py-3 px-3">Penulis &amp; Status</th>
                                        <th class="py-3 px-3">Rating</th>
                                        <th class="py-3 px-3">Isi Testimoni / Ulasan</th>
                                        <th class="py-3 px-3">Status Moderasi</th>
                                        <th class="py-3 px-3 text-center">Unggulan</th>
                                        <th class="py-3 px-3 text-right">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-800">
                                    <?php foreach ($testimonialsList as $t): ?>
                                        <tr class="hover:bg-slate-800/30 transition" id="testi-row-<?= (int)$t['id'] ?>">
                                            <td class="py-3 px-3 align-top">
                                                <div class="font-bold text-white"><?= esc($t['author_name']) ?></div>
                                                <div class="text-[11px] text-indigo-300"><?= esc($t['author_role'] ?? 'Mahasiswa') ?></div>
                                                <?php if (!empty($t['institution'])): ?>
                                                    <div class="text-[10px] text-slate-400"><?= esc($t['institution']) ?></div>
                                                <?php endif; ?>
                                                <?php if (!empty($t['is_anonymous'])): ?>
                                                    <span class="inline-block mt-1 text-[9px] px-1.5 py-0.5 rounded bg-slate-700 text-amber-300 font-mono-code">Anonim Publik</span>
                                                <?php endif; ?>
                                                <?php if (!empty($t['has_purchased'])): ?>
                                                    <span class="inline-block mt-1 text-[9px] px-1.5 py-0.5 rounded bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 font-semibold" title="Pengguna telah pernah membeli paket pemeriksaan">🎓 Eligible Duta Kampus</span>
                                                <?php else: ?>
                                                    <span class="inline-block mt-1 text-[9px] px-1.5 py-0.5 rounded bg-slate-800 text-slate-500" title="Akun belum memiliki riwayat pembelian paket">Non-Buyer</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="py-3 px-3 align-top whitespace-nowrap">
                                                <div class="text-amber-400 font-bold">
                                                    <?php for ($s = 1; $s <= 5; $s++): ?>
                                                        <span><?= $s <= (int)$t['rating'] ? '★' : '☆' ?></span>
                                                    <?php endfor; ?>
                                                </div>
                                                <div class="text-[10px] text-slate-400 font-mono-code"><?= (int)$t['rating'] ?>.0 / 5.0</div>
                                            </td>
                                            <td class="py-3 px-3 align-top max-w-xs sm:max-w-md">
                                                <p class="text-slate-200 leading-relaxed italic bg-slate-950/40 p-2.5 rounded-lg border border-slate-800/80">
                                                    "<?= esc($t['content']) ?>"
                                                </p>
                                                <div class="text-[10px] text-slate-500 mt-1 flex items-center gap-2">
                                                    <span>Naskah: <?= esc($t['document_title'] ?? 'Laporan #' . $t['report_id']) ?></span>
                                                    <span>&bull;</span>
                                                    <span><?= date('d M Y, H:i', strtotime($t['created_at'])) ?></span>
                                                </div>
                                            </td>
                                            <td class="py-3 px-3 align-top whitespace-nowrap">
                                                <span id="testi-status-badge-<?= (int)$t['id'] ?>">
                                                    <?php if ((int)$t['is_approved'] === 1): ?>
                                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                                                            ✓ Disetujui
                                                        </span>
                                                    <?php elseif ((int)$t['is_approved'] === -1): ?>
                                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold bg-rose-500/20 text-rose-300 border border-rose-500/30">
                                                            ✕ Ditolak
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold bg-amber-500/20 text-amber-300 border border-amber-500/30">
                                                            ⏳ Menunggu
                                                        </span>
                                                    <?php endif; ?>
                                                </span>
                                            </td>
                                            <td class="py-3 px-3 align-top text-center whitespace-nowrap">
                                                <button type="button" onclick="adminToggleFeatured(<?= (int)$t['id'] ?>)" id="testi-feat-btn-<?= (int)$t['id'] ?>" class="p-1.5 rounded-lg border text-sm transition cursor-pointer <?= !empty($t['is_featured']) ? 'bg-amber-500/20 border-amber-500/40 text-amber-300' : 'bg-slate-800 border-slate-700 text-slate-500 hover:text-slate-300' ?>" title="Jadikan unggulan di Landing Page">
                                                    ⭐
                                                </button>
                                            </td>
                                            <td class="py-3 px-3 align-top text-right whitespace-nowrap space-x-1">
                                                <button type="button" onclick="adminApproveTestimonial(<?= (int)$t['id'] ?>)" class="px-2.5 py-1 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-[11px] transition cursor-pointer shadow-xs" title="Setujui dan tayangkan ke publik">
                                                    Setujui
                                                </button>
                                                <button type="button" onclick="adminRejectTestimonial(<?= (int)$t['id'] ?>)" class="px-2 py-1 rounded-lg bg-slate-700 hover:bg-slate-600 text-slate-300 text-[11px] transition cursor-pointer" title="Tolak">
                                                    Tolak
                                                </button>
                                                <button type="button" onclick="confirmDeleteTestimonial(<?= (int)$t['id'] ?>, '<?= esc($t['author_name'], 'js') ?>')" class="px-2 py-1 rounded-lg bg-rose-900/60 hover:bg-rose-800 text-rose-200 text-[11px] transition cursor-pointer" title="Hapus permanen">
                                                    Hapus
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        <!-- ========================================== -->
        <!-- TAB: DUTA KAMPUS & SELEKSI ASINKRON        -->
        <!-- ========================================== -->
        <?php elseif ($activeTab === 'ambassador'): ?>
            <div class="space-y-6">
                <!-- Header -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-700/60 pb-4">
                    <div>
                        <h2 class="text-lg font-bold text-white flex items-center gap-2">
                            <span>🎓 Program Duta Kampus &amp; Seleksi Asinkron</span>
                            <span class="text-[10px] uppercase font-bold tracking-wider px-2 py-0.5 rounded-full bg-indigo-500/30 text-indigo-200 border border-indigo-400/30 font-mono-code">
                                Fase 2 Kemitraan
                            </span>
                        </h2>
                        <p class="text-xs text-slate-400 mt-0.5">
                            Kurasi mingguan pendaftar perwakilan kampus, verifikasi identitas anti-fraud, dan pemantauan kinerja afiliasi.
                        </p>
                    </div>
                    <div class="flex items-center gap-2">
                        <a href="<?= base_url('ambassador') ?>" target="_blank" class="px-3 py-1.5 rounded-lg bg-indigo-600/20 hover:bg-indigo-600/30 text-indigo-300 border border-indigo-500/30 text-xs font-semibold transition flex items-center gap-1.5">
                            <span>🔗</span> <span>Lihat Formulir Publik</span>
                        </a>
                    </div>
                </div>

                <!-- 5 Metrik Ringkasan Seleksi -->
                <div class="grid grid-cols-2 sm:grid-cols-5 gap-3">
                    <div class="bg-slate-800/40 border border-slate-700/60 rounded-xl p-3.5 space-y-1">
                        <div class="text-[10px] uppercase font-bold text-slate-400 tracking-wider">Total Pendaftar</div>
                        <div class="text-xl font-bold font-mono-code text-white"><?= (int)($ambStats['total_applications'] ?? 0) ?></div>
                    </div>
                    <div class="bg-slate-800/40 border border-amber-500/30 rounded-xl p-3.5 space-y-1">
                        <div class="text-[10px] uppercase font-bold text-amber-300 tracking-wider">Menunggu Seleksi</div>
                        <div class="text-xl font-bold font-mono-code text-amber-400"><?= (int)($ambStats['pending_review'] ?? 0) ?></div>
                    </div>
                    <div class="bg-slate-800/40 border border-sky-500/30 rounded-xl p-3.5 space-y-1">
                        <div class="text-[10px] uppercase font-bold text-sky-300 tracking-wider">Menunggu KTM</div>
                        <div class="text-xl font-bold font-mono-code text-sky-400"><?= (int)($ambStats['accepted_wait_ktm'] ?? 0) ?></div>
                    </div>
                    <div class="bg-slate-800/40 border border-purple-500/30 rounded-xl p-3.5 space-y-1">
                        <div class="text-[10px] uppercase font-bold text-purple-300 tracking-wider">KTM Siap Aktivasi</div>
                        <div class="text-xl font-bold font-mono-code text-purple-400"><?= (int)($ambStats['verified_ready_act'] ?? 0) ?></div>
                    </div>
                    <div class="bg-slate-800/40 border border-emerald-500/30 rounded-xl p-3.5 space-y-1">
                        <div class="text-[10px] uppercase font-bold text-emerald-300 tracking-wider">Duta Aktif</div>
                        <div class="text-xl font-bold font-mono-code text-emerald-400"><?= (int)($ambStats['active_ambassadors'] ?? 0) ?></div>
                    </div>
                </div>

                <!-- Bagian 1: Berkas Pendaftaran & Seleksi Asinkron (Tahap 1, 2, 3) -->
                <div class="bg-slate-800/40 border border-slate-700/60 rounded-2xl p-5 space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-700/60 pb-3 flex-wrap gap-2">
                        <div>
                            <h3 class="text-sm font-bold text-white flex items-center gap-2">
                                <span>📋 Berkas Pendaftaran &amp; Kurasi Seleksi</span>
                                <span class="text-[10px] font-mono-code px-2 py-0.5 rounded bg-slate-700 text-slate-300">
                                    <?= count($ambApplications ?? []) ?> pendaftar
                                </span>
                            </h3>
                            <p class="text-xs text-slate-400 mt-0.5">Tinjau narasi jaringan kampus &amp; kelayakan pembelian paket untuk aktivasi Duta Kampus</p>
                        </div>
                    </div>

                    <?php if (empty($ambApplications)): ?>
                        <div class="p-8 text-center text-xs text-slate-500 bg-slate-900/40 rounded-xl border border-slate-800">
                            Belum ada pendaftaran Duta Kampus yang masuk.
                        </div>
                    <?php else: ?>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-xs">
                                <thead>
                                    <tr class="text-slate-400 border-b border-slate-700/60 text-[11px] uppercase tracking-wider">
                                        <th class="py-3 px-3">Pendaftar &amp; Kontak</th>
                                        <th class="py-3 px-3">Kampus &amp; Semester</th>
                                        <th class="py-3 px-3">Jaringan Kampus</th>
                                        <th class="py-3 px-3 text-center">Kelayakan Paket</th>
                                        <th class="py-3 px-3 text-center">Status</th>
                                        <th class="py-3 px-3 text-right">Aksi Seleksi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-800">
                                    <?php foreach ($ambApplications as $app): ?>
                                        <tr class="hover:bg-slate-800/30 transition" id="amb-row-<?= (int)$app['id'] ?>">
                                            <td class="py-3 px-3 align-top">
                                                <div class="font-bold text-white"><?= esc($app['full_name']) ?></div>
                                                <div class="text-[11px] text-indigo-300"><?= esc($app['email']) ?></div>
                                                <div class="text-[10px] text-slate-400 font-mono-code mt-0.5">WA: <?= esc($app['whatsapp_number']) ?></div>
                                                <div class="text-[9px] text-slate-500"><?= date('d M Y, H:i', strtotime($app['created_at'])) ?></div>
                                            </td>
                                            <td class="py-3 px-3 align-top">
                                                <div class="font-bold text-slate-200"><?= esc($app['institution']) ?></div>
                                                <div class="text-[11px] text-slate-400"><?= esc($app['major']) ?></div>
                                                <div class="text-[10px] text-amber-300/90 mt-0.5"><?= esc($app['semester']) ?></div>
                                            </td>
                                            <td class="py-3 px-3 align-top max-w-xs sm:max-w-sm">
                                                <p class="text-slate-300 leading-relaxed italic bg-slate-950/40 p-2.5 rounded-lg border border-slate-800/80 line-clamp-3 hover:line-clamp-none transition-all">
                                                    "<?= esc($app['campus_network']) ?>"
                                                </p>
                                                <?php if (!empty($app['id_card_path'])): ?>
                                                    <div class="mt-1.5 flex items-center gap-1.5 text-[10px] text-emerald-400 font-semibold">
                                                        <span>📎</span>
                                                        <span>KTM Terunggah</span>
                                                        <span class="text-slate-500">&bull;</span>
                                                        <span class="text-slate-400"><?= date('d M Y', strtotime($app['id_card_uploaded_at'] ?? 'now')) ?></span>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td class="py-3 px-3 align-top text-center whitespace-nowrap">
                                                <?php if (!empty($app['has_purchased'])): ?>
                                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                                                        <span>✓</span> <span>Pembeli Terverifikasi</span>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-medium bg-slate-800 text-slate-400">
                                                        <span>Non-Buyer</span>
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="py-3 px-3 align-top text-center whitespace-nowrap">
                                                <span id="amb-status-badge-<?= (int)$app['id'] ?>">
                                                    <?php if ($app['status'] === 'active'): ?>
                                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                                                            ✓ Aktif
                                                        </span>
                                                    <?php elseif ($app['status'] === 'verified'): ?>
                                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold bg-purple-500/20 text-purple-300 border border-purple-500/30">
                                                            🛡️ KTM Terverifikasi
                                                        </span>
                                                    <?php elseif ($app['status'] === 'accepted'): ?>
                                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold bg-sky-500/20 text-sky-300 border border-sky-500/30">
                                                            ⏳ Menunggu KTM
                                                        </span>
                                                    <?php elseif ($app['status'] === 'rejected'): ?>
                                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold bg-rose-500/20 text-rose-300 border border-rose-500/30">
                                                            ✕ Ditolak
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold bg-amber-500/20 text-amber-300 border border-amber-500/30">
                                                            ⏳ Menunggu Seleksi
                                                        </span>
                                                    <?php endif; ?>
                                                </span>
                                            </td>
                                            <td class="py-3 px-3 align-top text-right whitespace-nowrap space-x-1">
                                                <?php if ($app['status'] === 'pending'): ?>
                                                    <button type="button" onclick="adminAcceptAmbassador(<?= (int)$app['id'] ?>)" class="px-2.5 py-1 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-[11px] transition shadow-xs cursor-pointer" title="Loloskan seleksi & buka pesan WhatsApp untuk minta KTM">
                                                        ✓ Terima (Kirim WA)
                                                    </button>
                                                    <button type="button" onclick="adminRejectAmbassador(<?= (int)$app['id'] ?>)" class="px-2 py-1 rounded-lg bg-slate-800 hover:bg-rose-900 text-slate-300 hover:text-white text-[11px] transition cursor-pointer" title="Tolak pendaftaran">
                                                        ✕ Tolak
                                                    </button>
                                                <?php elseif (in_array($app['status'], ['accepted', 'verified'])): ?>
                                                    <button type="button" onclick="adminActivateAmbassador(<?= (int)$app['id'] ?>, '<?= esc($app['full_name']) ?>', '<?= esc($app['institution']) ?>')" class="px-2.5 py-1 rounded-lg bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-slate-950 font-bold text-[11px] transition shadow-sm cursor-pointer" title="Terbitkan kode referral & kirim paket sambutan WA">
                                                        🚀 Aktivasi Kode &amp; WA
                                                    </button>
                                                <?php else: ?>
                                                    <span class="text-[10px] text-slate-500 font-mono-code">Selesai</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Bagian 2: Daftar Duta Kampus Aktif & Pemantauan Performa (Tahap 4, 5, 6) -->
                <div class="bg-slate-800/40 border border-slate-700/60 rounded-2xl p-5 space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-700/60 pb-3 flex-wrap gap-2">
                        <div>
                            <h3 class="text-sm font-bold text-white flex items-center gap-2">
                                <span>🌟 Duta Kampus Aktif &amp; Pemantauan Afiliasi</span>
                                <span class="text-[10px] font-mono-code px-2 py-0.5 rounded bg-slate-700 text-slate-300">
                                    <?= count($ambProfiles ?? []) ?> aktif
                                </span>
                            </h3>
                            <p class="text-xs text-slate-400 mt-0.5">Pantau kode referral, masa percobaan (probation), dan rekap pencairan komisi bulanan</p>
                        </div>
                    </div>

                    <?php if (empty($ambProfiles)): ?>
                        <div class="p-8 text-center text-xs text-slate-500 bg-slate-900/40 rounded-xl border border-slate-800">
                            Belum ada Duta Kampus yang diaktivasi.
                        </div>
                    <?php else: ?>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-xs">
                                <thead>
                                    <tr class="text-slate-400 border-b border-slate-700/60 text-[11px] uppercase tracking-wider">
                                        <th class="py-3 px-3">Duta &amp; Kampus</th>
                                        <th class="py-3 px-3">Kode Referral</th>
                                        <th class="py-3 px-3">Status &amp; Periode</th>
                                        <th class="py-3 px-3 text-center">Komisi</th>
                                        <th class="py-3 px-3 text-right">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-800">
                                    <?php foreach ($ambProfiles as $prof): ?>
                                        <tr class="hover:bg-slate-800/30 transition">
                                            <td class="py-3 px-3 align-top">
                                                <div class="font-bold text-white"><?= esc($prof['user_name'] ?? 'Akademisi') ?></div>
                                                <div class="text-[11px] text-slate-400"><?= esc($prof['user_institution'] ?? '-') ?></div>
                                                <div class="text-[10px] text-indigo-300 font-mono-code"><?= esc($prof['user_email'] ?? '-') ?></div>
                                            </td>
                                            <td class="py-3 px-3 align-top whitespace-nowrap">
                                                <span class="font-mono-code font-bold text-amber-300 bg-amber-500/10 px-2 py-1 rounded-md border border-amber-500/20">
                                                    <?= esc($prof['referral_code']) ?>
                                                </span>
                                            </td>
                                            <td class="py-3 px-3 align-top whitespace-nowrap">
                                                <div>
                                                    <?php if ($prof['status'] === 'graduated' || !empty($prof['certificate_code'])): ?>
                                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-400/20 text-amber-300 border border-amber-400/40">
                                                            <span>🎓</span> <span>LULUS / PURNA</span>
                                                        </span>
                                                        <div class="text-[9px] text-amber-300/80 font-mono-code mt-0.5"><?= esc($prof['certificate_code']) ?></div>
                                                    <?php elseif ($prof['status'] === 'probation'): ?>
                                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-indigo-500/20 text-indigo-300 border border-indigo-500/30">
                                                            <span>⏳</span> <span>PROBATION</span>
                                                        </span>
                                                        <div class="text-[10px] text-slate-400 mt-1">
                                                            s/d <?= date('d M Y', strtotime($prof['probation_end'] ?? '+30 days')) ?>
                                                        </div>
                                                    <?php else: ?>
                                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                                                            <span>✓</span> <span><?= strtoupper(esc($prof['status'])) ?></span>
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td class="py-3 px-3 align-top text-center whitespace-nowrap">
                                                <span class="font-mono-code font-bold text-emerald-400">
                                                    <?= number_format((float)$prof['commission_rate_percent'], 0) ?>%
                                                </span>
                                            </td>
                                            <td class="py-3 px-3 align-top text-right whitespace-nowrap space-x-1">
                                                <?php if ($prof['status'] === 'graduated' || !empty($prof['certificate_code'])): ?>
                                                    <a href="<?= base_url('ambassador/certificate/' . $prof['certificate_code'] . '/preview') ?>" target="_blank" class="px-2.5 py-1 rounded-lg bg-amber-500 hover:bg-amber-400 text-slate-950 text-[11px] font-bold transition shadow-xs inline-flex items-center gap-1" title="Buka Piagam Penghargaan PDF">
                                                        <span>📜</span> <span>Piagam</span>
                                                    </a>
                                                    <a href="<?= base_url('ambassador/certificate/' . $prof['certificate_code']) ?>" target="_blank" class="px-2 py-1 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 text-[11px] font-semibold transition inline-flex items-center gap-1" title="Lihat Halaman Verifikasi Publik">
                                                        <span>🌐</span>
                                                    </a>
                                                <?php else: ?>
                                                    <button type="button" onclick="adminGraduateAmbassador(<?= (int)$prof['id'] ?>)" class="px-2.5 py-1 rounded-lg bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-400 hover:to-amber-500 text-slate-950 text-[11px] font-bold transition shadow-xs inline-flex items-center gap-1 cursor-pointer" title="Luluskan Duta & Terbitkan Piagam Penghargaan Resmi">
                                                        <span>🎓</span> <span>Luluskan</span>
                                                    </button>
                                                <?php endif; ?>
                                                <a href="<?= base_url('ref/' . $prof['referral_code']) ?>" target="_blank" class="px-2 py-1 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 text-[11px] font-semibold transition inline-flex items-center gap-1" title="Uji tautan referral">
                                                    <span>🔗</span>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Bagian 3: Verifikasi Bukti Aktivitas Promosi Bulanan (Tahap 5 & 6) -->
                <div class="bg-slate-800/40 border border-slate-700/60 rounded-2xl p-5 space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-700/60 pb-3 flex-wrap gap-2">
                        <div>
                            <h3 class="text-sm font-bold text-white flex items-center gap-2">
                                <span>📸 Verifikasi Bukti Promosi &amp; Rekap Mandiri Duta</span>
                                <span class="text-[10px] font-mono-code px-2 py-0.5 rounded bg-slate-700 text-slate-300">
                                    <?= count($ambProofs ?? []) ?> total bukti
                                </span>
                            </h3>
                            <p class="text-xs text-slate-400 mt-0.5">Validasi tangkapan layar promosi (Story WA/IG, grup angkatan) untuk syarat kelulusan probation &amp; evaluasi bulanan</p>
                        </div>
                    </div>

                    <?php if (empty($ambProofs)): ?>
                        <div class="p-8 text-center text-xs text-slate-500 bg-slate-900/40 rounded-xl border border-slate-800">
                            Belum ada bukti promosi yang diunggah oleh Duta Kampus.
                        </div>
                    <?php else: ?>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-xs">
                                <thead>
                                    <tr class="text-slate-400 border-b border-slate-700/60 text-[11px] uppercase tracking-wider">
                                        <th class="py-3 px-3">Duta &amp; Kampus</th>
                                        <th class="py-3 px-3">Kegiatan &amp; Saluran</th>
                                        <th class="py-3 px-3">Waktu Unggah</th>
                                        <th class="py-3 px-3 text-center">Berkas / Link Bukti</th>
                                        <th class="py-3 px-3 text-center">Status</th>
                                        <th class="py-3 px-3 text-right">Aksi Verifikasi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-800">
                                    <?php foreach ($ambProofs as $pf): ?>
                                        <tr class="hover:bg-slate-800/30 transition" id="proof-row-<?= (int)$pf['id'] ?>">
                                            <td class="py-3 px-3 align-top">
                                                <div class="font-bold text-white"><?= esc($pf['ambassador_name'] ?? 'Duta') ?></div>
                                                <div class="text-[11px] text-slate-400"><?= esc($pf['ambassador_institution'] ?? '-') ?></div>
                                                <div class="text-[10px] text-amber-300 font-mono-code mt-0.5"><?= esc($pf['referral_code'] ?? '-') ?></div>
                                            </td>
                                            <td class="py-3 px-3 align-top">
                                                <div class="font-semibold text-slate-200"><?= esc($pf['title']) ?></div>
                                                <div class="text-[11px] text-indigo-300 font-medium">
                                                    <?= strtoupper(str_replace('_', ' ', $pf['proof_type'])) ?>
                                                </div>
                                                <?php if (!empty($pf['description'])): ?>
                                                    <div class="text-[11px] text-slate-400 mt-1 italic"><?= esc($pf['description']) ?></div>
                                                <?php endif; ?>
                                            </td>
                                            <td class="py-3 px-3 align-top whitespace-nowrap text-slate-400">
                                                <div><?= date('d M Y', strtotime($pf['created_at'])) ?></div>
                                                <div class="text-[10px] text-slate-500"><?= date('H:i', strtotime($pf['created_at'])) ?> WIB</div>
                                            </td>
                                            <td class="py-3 px-3 align-top text-center whitespace-nowrap">
                                                <?php if (!empty($pf['file_path'])): ?>
                                                    <a href="<?= base_url('admin/ambassador/proof/file/' . $pf['id']) ?>" target="_blank" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-indigo-500/20 hover:bg-indigo-500/30 text-indigo-300 border border-indigo-500/30 text-[11px] font-bold transition">
                                                        <span>🖼️</span> <span>Lihat Screenshot</span>
                                                    </a>
                                                <?php endif; ?>
                                                <?php if (!empty($pf['url_link'])): ?>
                                                    <a href="<?= esc($pf['url_link']) ?>" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 border border-slate-700 text-[11px] font-medium transition ml-1">
                                                        <span>🔗</span> <span>Tautan</span>
                                                    </a>
                                                <?php endif; ?>
                                                <?php if (empty($pf['file_path']) && empty($pf['url_link'])): ?>
                                                    <span class="text-slate-500 text-[11px]">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="py-3 px-3 align-top text-center whitespace-nowrap" id="proof-status-badge-<?= (int)$pf['id'] ?>">
                                                <?php if (in_array($pf['status'], ['approved', 'verified'], true)): ?>
                                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                                                        <span>✓</span> <span>Disetujui</span>
                                                    </span>
                                                <?php elseif ($pf['status'] === 'rejected'): ?>
                                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-500/20 text-rose-300 border border-rose-500/30">
                                                        <span>✕</span> <span>Ditolak</span>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-500/20 text-amber-300 border border-amber-500/30">
                                                        <span>⏳</span> <span>Menunggu Review</span>
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="py-3 px-3 align-top text-right whitespace-nowrap space-x-1" id="proof-actions-<?= (int)$pf['id'] ?>">
                                                <?php if (!in_array($pf['status'], ['approved', 'verified'], true)): ?>
                                                    <button type="button" onclick="adminApproveProof(<?= (int)$pf['id'] ?>)" class="px-2.5 py-1 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-[11px] transition shadow-xs cursor-pointer" title="Verifikasi & setujui bukti promosi ini">
                                                        ✓ Setujui
                                                    </button>
                                                <?php endif; ?>
                                                <?php if ($pf['status'] !== 'rejected'): ?>
                                                    <button type="button" onclick="adminRejectProof(<?= (int)$pf['id'] ?>)" class="px-2.5 py-1 rounded-lg bg-slate-800 hover:bg-rose-900 text-slate-300 hover:text-white text-[11px] transition cursor-pointer" title="Tolak bukti promosi">
                                                        ✕ Tolak
                                                    </button>
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

        <!-- ========================================== -->
        <!-- TAB 7: SISTEM SERVER                       -->
        <!-- ========================================== -->
        <?php elseif ($activeTab === 'system'): ?>
            <div class="space-y-6">
                <div>
                    <h2 class="text-lg font-bold text-white">Status Lingkungan Server & Aplikasi</h2>
                    <p class="text-xs text-slate-400">Konfigurasi runtime backend AR Tilik</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="bg-slate-800/40 border border-slate-700/60 rounded-2xl p-5 space-y-3">
                        <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 border-b border-slate-700/60 pb-2">PHP & Web Server</h3>
                        <div class="flex justify-between text-xs py-1"><span class="text-slate-400">Versi PHP:</span><span class="font-mono-code text-slate-200"><?= esc($systemInfo['php_version']) ?></span></div>
                        <div class="flex justify-between text-xs py-1"><span class="text-slate-400">Web Server:</span><span class="font-mono-code text-slate-200"><?= esc($systemInfo['server_software']) ?></span></div>
                        <div class="flex justify-between text-xs py-1"><span class="text-slate-400">Batas Memori (memory_limit):</span><span class="font-mono-code text-slate-200"><?= esc($systemInfo['memory_limit']) ?></span></div>
                        <div class="flex justify-between text-xs py-1"><span class="text-slate-400">Ukuran Unggah Maks (upload_max_filesize):</span><span class="font-mono-code text-slate-200"><?= esc($systemInfo['upload_max_filesize']) ?></span></div>
                        <div class="flex justify-between text-xs py-1"><span class="text-slate-400">Post Maks (post_max_size):</span><span class="font-mono-code text-slate-200"><?= esc($systemInfo['post_max_size']) ?></span></div>
                    </div>

                    <div class="bg-slate-800/40 border border-slate-700/60 rounded-2xl p-5 space-y-3">
                        <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 border-b border-slate-700/60 pb-2">Basis Data & Waktu</h3>
                        <div class="flex justify-between text-xs py-1"><span class="text-slate-400">Platform Basis Data:</span><span class="font-mono-code text-emerald-400"><?= esc($systemInfo['db_driver']) ?></span></div>
                        <div class="flex justify-between text-xs py-1"><span class="text-slate-400">Waktu Peladen:</span><span class="font-mono-code text-slate-200"><?= esc($systemInfo['server_time']) ?></span></div>
                        <div class="flex justify-between text-xs py-1"><span class="text-slate-400">Worker Detection Engine:</span><span class="font-mono-code text-indigo-400">Python 3 (Frozen Hybrid 5-Gram)</span></div>
                        <div class="flex justify-between text-xs py-1"><span class="text-slate-400">Protokol SMTP:</span><span class="font-mono-code text-sky-400">Postfix TLS Port 587 (mail.arsetontong.top)</span></div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        </main>
    </div>

    <!-- Interactive JavaScript Actions -->
    <script>
        // ==========================================
        // Page Transition & Progress Bar Handling
        // ==========================================
        let progressInterval = null;

        function startPageTransition(title = 'Memuat portal admin...', subtitle = 'Menyiapkan data & antarmuka') {
            const bar = document.getElementById('topProgressBar');
            const overlay = document.getElementById('pageTransitionOverlay');
            const titleEl = document.getElementById('pageTransitionTitle');
            const subtitleEl = document.getElementById('pageTransitionSubtitle');

            if (titleEl && title) titleEl.textContent = title;
            if (subtitleEl && subtitle) subtitleEl.textContent = subtitle;

            if (bar) {
                bar.style.opacity = '1';
                bar.style.width = '35%';
                clearInterval(progressInterval);
                let currentWidth = 35;
                progressInterval = setInterval(() => {
                    if (currentWidth < 90) {
                        currentWidth += Math.random() * 12;
                        if (currentWidth > 90) currentWidth = 90;
                        bar.style.width = currentWidth + '%';
                    }
                }, 120);
            }

            if (overlay) {
                overlay.style.display = 'flex';
                void overlay.offsetWidth;
                overlay.style.opacity = '1';
                overlay.style.pointerEvents = 'auto';
            }
        }

        function endPageTransition() {
            clearInterval(progressInterval);
            const bar = document.getElementById('topProgressBar');
            const overlay = document.getElementById('pageTransitionOverlay');

            if (bar) {
                bar.style.width = '100%';
                setTimeout(() => {
                    bar.style.opacity = '0';
                    setTimeout(() => {
                        bar.style.width = '0%';
                        bar.style.opacity = '1';
                    }, 300);
                }, 200);
            }

            if (overlay) {
                overlay.style.opacity = '0';
                overlay.style.pointerEvents = 'none';
                setTimeout(() => {
                    overlay.style.display = 'none';
                }, 280);
            }
        }

        // Intercept standard navigation link clicks
        document.addEventListener('click', function(e) {
            const link = e.target.closest('a');
            if (!link || !link.href) return;
            
            // Ignore new window, javascript:, hashes, download, or explicitly opted-out
            if (link.target && link.target !== '_self') return;
            if (link.hasAttribute('download')) return;
            if (link.href.startsWith('javascript:')) return;
            if (link.getAttribute('href') && link.getAttribute('href').startsWith('#')) return;
            if (link.dataset.noLoader !== undefined) return;

            // Only intercept same-origin navigation
            try {
                const url = new URL(link.href, window.location.origin);
                if (url.origin === window.location.origin) {
                    // If clicking exact same page + same search + hash anchor, do not trigger
                    if (url.pathname === window.location.pathname && url.search === window.location.search && url.hash) {
                        return;
                    }

                    // Determine context-aware loading message
                    let title = 'Memuat portal admin...';
                    let subtitle = 'Menyiapkan data & antarmuka';
                    
                    const tabParam = url.searchParams.get('tab');
                    if (tabParam) {
                        const tabTitles = {
                            'overview': 'Memuat ringkasan...',
                            'users': 'Memuat data pengguna & kuota...',
                            'documents': 'Memuat antrean dokumen...',
                            'corpus': 'Memuat korpus repositori...',
                            'analytics': 'Menyiapkan analitik institusi...',
                            'api_keys': 'Memuat integrasi API & OJS...',
                            'google_keys': 'Memuat pool Google AI keys...',
                            'payments': 'Memuat transaksi & pembayaran...',
                            'security': 'Memuat status keamanan...',
                            'visitors': 'Memuat analitik pengunjung...',
                            'system': 'Memeriksa status server...'
                        };
                        if (tabTitles[tabParam]) {
                            title = tabTitles[tabParam];
                        }
                    } else if (url.pathname.includes('/dashboard')) {
                        title = 'Menuju Dashboard Pengguna...';
                        subtitle = 'Beralih ke tampilan pengguna';
                    } else if (url.pathname.includes('/auth/logout')) {
                        title = 'Mengakhiri Sesi...';
                        subtitle = 'Keluar dari Portal Admin';
                    }

                    startPageTransition(title, subtitle);
                }
            } catch (err) {
                // If invalid URL, ignore
            }
        });

        // Intercept form submissions (e.g., search bar)
        document.addEventListener('submit', function(e) {
            const form = e.target;
            if (form && !form.target && form.dataset.noLoader === undefined) {
                startPageTransition('Mencari data...', 'Menyaring hasil penelusuran');
            }
        });

        // Handle page show & BFCache restoration
        window.addEventListener('pageshow', function(event) {
            endPageTransition();
        });

        // Trigger on unload / reload
        window.addEventListener('beforeunload', function() {
            startPageTransition('Memuat portal admin...', 'Menyiapkan pembaruan halaman');
        });

        // Auto-dismiss preloader once DOM/window is ready
        const MIN_LOADER_DELAY = 350;
        if (document.readyState === 'complete') {
            setTimeout(endPageTransition, MIN_LOADER_DELAY);
        } else {
            window.addEventListener('load', function() {
                setTimeout(endPageTransition, MIN_LOADER_DELAY);
            });
            document.addEventListener('DOMContentLoaded', function() {
                setTimeout(endPageTransition, MIN_LOADER_DELAY);
            });
            // Safety timeout: maximum 1.5s so content is never blocked
            setTimeout(endPageTransition, 1500);
        }

        function toggleAdminSidebar() {
            const sidebar = document.getElementById('adminSidebar');
            const overlay = document.getElementById('adminSidebarOverlay');
            if (!sidebar) return;

            const isHidden = sidebar.classList.contains('-translate-x-full');
            if (isHidden) {
                sidebar.classList.remove('-translate-x-full');
                sidebar.classList.add('translate-x-0');
                if (overlay) overlay.classList.remove('hidden');
                document.body.classList.add('overflow-hidden', 'lg:overflow-auto');
            } else {
                sidebar.classList.add('-translate-x-full');
                sidebar.classList.remove('translate-x-0');
                if (overlay) overlay.classList.add('hidden');
                document.body.classList.remove('overflow-hidden', 'lg:overflow-auto');
            }
        }

        async function postJson(url, data) {
            const formData = new FormData();
            for (const key in data) {
                formData.append(key, data[key]);
            }
            const res = await fetch(url, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            return await res.json();
        }

        async function activateTrialKampus(userId, userName) {
            const confirmMsg = `Aktifkan Program Uji Coba Resmi Jurnal & Kampus untuk "${userName}"?\n\n` +
                               `• Masa Berlaku: 14 Hari Penuh\n` +
                               `• Bonus Kuota Submisi: +50 Naskah Lengkap\n` +
                               `• Akses Seluruh Fitur Enterprise (Catur Pilar, Forensik Gemini AI, Sertifikat SINTA/ARJUNA, Pemusnahan Nol Jejak)\n` +
                               `• Otomatis terbitkan Kunci API Konektor REST OJS 3.x (Batas 50 Submisi)\n\n` +
                               `Lanjutkan aktivasi sekarang?`;
            if (!confirm(confirmMsg)) return;

            try {
                const res = await postJson('<?= base_url('admin/users/update') ?>', {
                    user_id: userId,
                    action: 'activate_trial_kampus'
                });
                if (res.status === 'success') {
                    if (res.extra && res.extra.ojs_key) {
                        prompt(
                            `🎉 BERHASIL DIAKTIFKAN!\n\nSalin API Key Konektor OJS berikut untuk diberikan kepada Pengelola Jurnal/Editor OJS:\n(Catatan: Kunci lengkap hanya dapat dilihat pada saat ini)`,
                            res.extra.ojs_key
                        );
                    } else {
                        alert(res.message);
                    }
                    window.location.reload();
                } else {
                    alert('Gagal: ' + (res.message || 'Terjadi kesalahan sistem.'));
                }
            } catch (err) {
                alert('Terjadi kesalahan koneksi.');
            }
        }

        async function handleUserAction(userId, action, extra = {}) {
            if (action === 'set_status' && !confirm('Apakah Anda yakin ingin mengubah status pengguna ini?')) return;
            if (action === 'set_role' && !confirm('Apakah Anda yakin ingin mengubah peran pengguna ini?')) return;
            
            try {
                const payload = { user_id: userId, action: action, ...extra };
                const res = await postJson('<?= base_url('admin/users/update') ?>', payload);
                if (res.status === 'success') {
                    alert(res.message);
                    window.location.reload();
                } else {
                    alert('Gagal: ' + res.message);
                }
            } catch (err) {
                alert('Terjadi kesalahan koneksi.');
            }
        }

        function promptBonusQuota(userId) {
            const amount = prompt('Masukkan jumlah bonus kuota tambahan (misal: 3, 5, dsb):', '3');
            if (amount !== null && !isNaN(parseInt(amount))) {
                handleUserAction(userId, 'adjust_quota', { bonus_submissions: parseInt(amount) });
            }
        }

        async function handleDocReprocess(docId) {
            if (!confirm('Proses ulang analisis dokumen ini dengan worker?')) return;
            try {
                const res = await postJson('<?= base_url('admin/documents/reprocess') ?>', { document_id: docId });
                alert(res.message);
                window.location.reload();
            } catch (err) {
                alert('Terjadi kesalahan koneksi.');
            }
        }

        async function handleDocDelete(docId) {
            confirmDeleteDocument(docId, 'Dokumen #' + docId, 'doc-row-' + docId);
        }

        async function handleCorpusVisibility(docId, visibility) {
            try {
                const res = await postJson('<?= base_url('admin/corpus/visibility') ?>', { document_id: docId, visibility: visibility });
                if (res.status !== 'success') {
                    alert('Gagal: ' + res.message);
                }
            } catch (err) {
                alert('Terjadi kesalahan koneksi.');
            }
        }

        async function handleUnblock(logId) {
            if (!confirm('Buka penguncian alamat IP/identitas ini?')) return;
            try {
                const res = await postJson('<?= base_url('admin/security/unblock') ?>', { id: logId });
                alert(res.message);
                window.location.reload();
            } catch (err) {
                alert('Terjadi kesalahan koneksi.');
            }
        }

        async function handleClearAllLocks() {
            if (!confirm('Hapus dan buka seluruh catatan penguncian keamanan saat ini?')) return;
            try {
                const res = await postJson('<?= base_url('admin/security/clear-all') ?>', {});
                alert(res.message);
                window.location.reload();
            } catch (err) {
                alert('Terjadi kesalahan koneksi.');
            }
        }

        // Auto-Harvester Handlers (Opsi C)
        function showHarvestConsole(text) {
            const consoleBox = document.getElementById('harvest-console');
            const output = document.getElementById('harvest-output');
            if (consoleBox && output) {
                consoleBox.classList.remove('hidden');
                output.innerText = text;
            }
        }

        async function handleHarvestNeliti() {
            const query = document.getElementById('neliti-query').value.trim();
            const limit = document.getElementById('neliti-limit').value;
            const btn = document.getElementById('btn-harvest-neliti');
            if (btn) btn.disabled = true;

            showHarvestConsole('[*] Menghubungi repositori Neliti (OAI-PMH 2.0)...\n[*] Memanen metadata artikel ilmiah open-access...');

            try {
                const res = await postJson('<?= base_url('admin/corpus/harvest-neliti') ?>', { query: query, limit: limit });
                showHarvestConsole(res.output || res.message);
                alert(res.message);
                window.location.reload();
            } catch (err) {
                showHarvestConsole('[!] Terjadi kesalahan koneksi atau timeout pemanenan.');
                alert('Gagal mengeksekusi pemanenan Neliti.');
            } finally {
                if (btn) btn.disabled = false;
            }
        }

        async function handleHarvestGaruda() {
            const query = document.getElementById('garuda-query').value.trim();
            const limit = document.getElementById('garuda-limit').value;
            const btn = document.getElementById('btn-harvest-garuda');
            if (btn) btn.disabled = true;

            showHarvestConsole('[*] Menghubungi portal GARUDA Dikti Kemdiktisaintek...\n[*] Memindai jurnal SINTA terakreditasi dan mengunduh berkas PDF...');

            try {
                const res = await postJson('<?= base_url('admin/corpus/harvest-garuda') ?>', { query: query, limit: limit });
                showHarvestConsole(res.output || res.message);
                alert(res.message);
                window.location.reload();
            } catch (err) {
                showHarvestConsole('[!] Terjadi kesalahan koneksi atau timeout pemanenan.');
                alert('Gagal mengeksekusi pemanenan GARUDA.');
            } finally {
                if (btn) btn.disabled = false;
            }
        }

        async function handleHarvestScheduled() {
            if (!confirm('Jalankan seluruh antrean pemanenan terjadwal dari basis data sekarang?')) return;
            const btn = document.getElementById('btn-harvest-sched');
            if (btn) btn.disabled = true;

            showHarvestConsole('[*] Menjalankan pemanenan terjadwal (Neliti & GARUDA Dikti)...');

            try {
                const res = await postJson('<?= base_url('admin/corpus/harvest-scheduled') ?>', {});
                showHarvestConsole(res.output || res.message);
                alert(res.message);
                window.location.reload();
            } catch (err) {
                showHarvestConsole('[!] Terjadi kendala saat menjalankan pemanenan terjadwal.');
                alert('Gagal menjalankan jadwal pemanenan.');
            } finally {
                if (btn) btn.disabled = false;
            }
        }

        // API Key Handlers (Opsi B)
        async function handleGenerateApiKey() {
            const keyName = document.getElementById('api-key-name').value.trim();
            const systemType = document.getElementById('api-system-type').value;
            const quotaLimit = document.getElementById('api-quota-limit').value;
            const btn = document.getElementById('btn-generate-key');

            if (!keyName) {
                alert('Silakan isi nama jurnal atau aplikasi target.');
                return;
            }

            if (btn) btn.disabled = true;

            try {
                const res = await postJson('<?= base_url('api-keys/generate') ?>', {
                    key_name: keyName,
                    system_type: systemType,
                    quota_limit: quotaLimit
                });

                if (res.status === 'success') {
                    const resultBox = document.getElementById('key-result-box');
                    const keyVal = document.getElementById('new-key-value');
                    if (resultBox && keyVal) {
                        keyVal.innerText = res.data.raw_key;
                        resultBox.classList.remove('hidden');
                    }
                    alert('API Key baru berhasil dibuat! Salin kunci sebelum menutup halaman.');
                } else {
                    alert('Gagal: ' + res.message);
                }
            } catch (err) {
                alert('Terjadi kesalahan koneksi.');
            } finally {
                if (btn) btn.disabled = false;
            }
        }

        async function handleRevokeApiKey(keyId) {
            if (!confirm('Apakah Anda yakin ingin mencabut (menonaktifkan) API Key ini? Sistem eksternal tidak akan dapat mengakses API kembali.')) return;

            try {
                const res = await postJson('<?= base_url('api-keys/revoke') ?>/' + keyId, {});
                if (res.status === 'success') {
                    alert(res.message);
                    window.location.reload();
                } else {
                    alert('Gagal: ' + res.message);
                }
            } catch (err) {
                alert('Terjadi kesalahan koneksi.');
            }
        }

        // Provider & Model Dynamic Switcher (Groq L1 vs Gemini L2)
        const AI_MODELS = {
            groq: [
                { value: 'qwen/qwen3.8-27b', text: 'Qwen 3.8 27B (Groq - Rekomendasi Utama L1)' },
                { value: 'openai/gpt-oss-120b', text: 'OpenAI GPT-OSS 120B (Groq - Deep Reasoning)' },
                { value: 'openai/gpt-oss-20b', text: 'OpenAI GPT-OSS 20B (Groq - Fast Reasoning)' },
                { value: 'allam-2-7b', text: 'ALLaM 2 7B (Groq)' }
            ],
            gemini: [
                { value: 'gemini-flash-lite-latest', text: 'Gemini Flash Lite (Bebas Antrean - Rekomendasi Utama L2)' },
                { value: 'gemini-3.5-flash-lite', text: 'Gemini 3.5 Flash Lite (Cepat & Responsif)' },
                { value: 'gemini-3.5-flash', text: 'Gemini 3.5 Flash (Standar)' },
                { value: 'gemini-3.6-flash', text: 'Gemini 3.6 Flash (Generasi Mutakhir 2026)' },
                { value: 'gemini-3.0-flash', text: 'Gemini 3.0 Flash (Cepat & Efisien)' },
                { value: 'gemini-1.5-flash', text: 'Gemini 1.5 Flash (Legacy)' },
                { value: 'gemini-1.5-pro', text: 'Gemini 1.5 Pro (Legacy Pro)' }
            ]
        };

        function handleProviderChange() {
            const providerSel = document.getElementById('google-provider');
            const modelSel = document.getElementById('google-model-name');
            const nameInput = document.getElementById('google-key-name');
            const keyInput = document.getElementById('google-api-key');
            if (!providerSel || !modelSel) return;

            const provider = providerSel.value;
            const models = AI_MODELS[provider] || AI_MODELS.groq;

            // Simpan model yang sedang dipilih jika masih valid di provider baru
            const currentVal = modelSel.value;
            modelSel.innerHTML = '';
            models.forEach(m => {
                const opt = document.createElement('option');
                opt.value = m.value;
                opt.textContent = m.text;
                if (m.value === currentVal) opt.selected = true;
                modelSel.appendChild(opt);
            });

            if (nameInput && !nameInput.value) {
                nameInput.placeholder = (provider === 'groq') ? 'mis: Groq Primer (Akun 1)' : 'mis: Gemini Cadangan (Akun 2)';
            }
            if (keyInput) {
                keyInput.placeholder = (provider === 'groq') ? 'gsk_...' : 'AIzaSy...';
            }
        }

        function handleKeyInputAutoDetect() {
            const keyInput = document.getElementById('google-api-key');
            const providerSel = document.getElementById('google-provider');
            if (!keyInput || !providerSel) return;

            const val = keyInput.value.trim();
            if (val.startsWith('gsk_') && providerSel.value !== 'groq') {
                providerSel.value = 'groq';
                handleProviderChange();
            } else if (val.startsWith('AIza') && providerSel.value !== 'gemini') {
                providerSel.value = 'gemini';
                handleProviderChange();
            }
        }

        // Google Gemini & Groq Cloud API Key Handlers (Pool Round-Robin)
        async function handleCreateGoogleKey() {
            const provider = document.getElementById('google-provider')?.value || 'groq';
            const keyName = document.getElementById('google-key-name').value.trim();
            const apiKey = document.getElementById('google-api-key').value.trim();
            const modelName = document.getElementById('google-model-name').value;
            const btn = document.getElementById('btn-add-google-key');

            if (!keyName || !apiKey) {
                alert('Silakan lengkapi label nama dan API Key.');
                return;
            }

            if (btn) btn.disabled = true;

            try {
                const res = await postJson('<?= base_url('admin/google-keys/create') ?>', {
                    provider: provider,
                    key_name: keyName,
                    api_key: apiKey,
                    model_name: modelName
                });

                if (res.status === 'success') {
                    alert(res.message);
                    window.location.reload();
                } else {
                    alert('Gagal: ' + res.message);
                }
            } catch (err) {
                alert('Terjadi kesalahan koneksi saat menambahkan kunci.');
            } finally {
                if (btn) btn.disabled = false;
            }
        }

        async function handleToggleGoogleKey(keyId) {
            try {
                const res = await postJson('<?= base_url('admin/google-keys/toggle') ?>/' + keyId, {});
                if (res.status === 'success') {
                    window.location.reload();
                } else {
                    alert('Gagal: ' + res.message);
                }
            } catch (err) {
                alert('Terjadi kesalahan koneksi.');
            }
        }

        async function handleDeleteGoogleKey(keyId, keyName) {
            if (!confirm('Apakah Anda yakin ingin menghapus kunci "' + keyName + '" dari pool rotasi?')) return;

            try {
                const res = await postJson('<?= base_url('admin/google-keys/delete') ?>/' + keyId, {});
                if (res.status === 'success') {
                    alert(res.message);
                    window.location.reload();
                } else {
                    alert('Gagal: ' + res.message);
                }
            } catch (err) {
                alert('Terjadi kesalahan koneksi.');
            }
        }

        async function handleTestGoogleKey(keyId, btnElement) {
            if (btnElement) {
                btnElement.disabled = true;
                btnElement.innerHTML = 'Menguji...';
            }

            try {
                const res = await postJson('<?= base_url('admin/google-keys/test') ?>/' + keyId, {});
                if (res.status === 'success') {
                    alert('✅ ' + (res.message || 'Uji Koneksi Sukses!') + (res.reply ? '\nRespon Uji: "' + res.reply + '"' : ''));
                    window.location.reload();
                } else {
                    alert('❌ Uji Koneksi Gagal:\n' + (res.message || 'Tidak dapat terhubung ke penyedia AI'));
                }
            } catch (err) {
                alert('Terjadi kesalahan koneksi saat pengujian API.');
            } finally {
                if (btnElement) {
                    btnElement.disabled = false;
                    btnElement.innerHTML = 'Uji';
                }
            }
        }

        // ── KELOLA MODERASI TESTIMONI AKADEMIK ──
        async function adminApproveTestimonial(id) {
            try {
                const res = await postJson('<?= base_url('admin/testimonials/approve') ?>/' + id, {});
                if (res.status === 'success') {
                    const badge = document.getElementById('testi-status-badge-' + id);
                    if (badge) {
                        badge.innerHTML = '<span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">✓ Disetujui</span>';
                    }
                } else {
                    alert('Gagal: ' + res.message);
                }
            } catch (err) {
                alert('Terjadi kesalahan koneksi.');
            }
        }

        async function adminRejectTestimonial(id) {
            try {
                const res = await postJson('<?= base_url('admin/testimonials/reject') ?>/' + id, {});
                if (res.status === 'success') {
                    const badge = document.getElementById('testi-status-badge-' + id);
                    if (badge) {
                        badge.innerHTML = '<span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold bg-rose-500/20 text-rose-300 border border-rose-500/30">✕ Ditolak</span>';
                    }
                } else {
                    alert('Gagal: ' + res.message);
                }
            } catch (err) {
                alert('Terjadi kesalahan koneksi.');
            }
        }

        async function adminToggleFeatured(id) {
            try {
                const res = await postJson('<?= base_url('admin/testimonials/feature') ?>/' + id, {});
                if (res.status === 'success') {
                    const btn = document.getElementById('testi-feat-btn-' + id);
                    if (btn) {
                        if (res.is_featured) {
                            btn.className = 'p-1.5 rounded-lg border text-sm transition cursor-pointer bg-amber-500/20 border-amber-500/40 text-amber-300';
                            const badge = document.getElementById('testi-status-badge-' + id);
                            if (badge) {
                                badge.innerHTML = '<span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">✓ Disetujui</span>';
                            }
                        } else {
                            btn.className = 'p-1.5 rounded-lg border text-sm transition cursor-pointer bg-slate-800 border-slate-700 text-slate-500 hover:text-slate-300';
                        }
                    }
                } else {
                    alert('Gagal: ' + res.message);
                }
            } catch (err) {
                alert('Terjadi kesalahan koneksi.');
            }
        }

        async function adminDeleteTestimonial(id) {
            confirmDeleteTestimonial(id);
        }

        async function adminAcceptAmbassador(id) {
            if (!confirm('Loloskan pendaftar ini ke Tahap 2 & kirim tautan verifikasi identitas (KTM)?')) return;
            try {
                const res = await postJson('<?= base_url('admin/ambassador/accept') ?>/' + id, {});
                if (res.success) {
                    alert('✓ ' + res.message);
                    const badge = document.getElementById('amb-status-badge-' + id);
                    if (badge) {
                        badge.innerHTML = '<span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold bg-sky-500/20 text-sky-300 border border-sky-500/30">⏳ Menunggu KTM</span>';
                    }
                    if (res.wa_link) {
                        window.open(res.wa_link, '_blank');
                    }
                } else {
                    alert('Gagal: ' + (res.message || 'Terjadi kesalahan.'));
                }
            } catch (err) {
                alert('Terjadi kesalahan koneksi.');
            }
        }

        async function adminRejectAmbassador(id) {
            const reason = prompt('Alasan penolakan (opsional untuk catatan admin):', 'Kuota perwakilan kampus telah terpenuhi');
            if (reason === null) return;
            try {
                const res = await postJson('<?= base_url('admin/ambassador/reject') ?>/' + id, { admin_notes: reason });
                if (res.success) {
                    const badge = document.getElementById('amb-status-badge-' + id);
                    if (badge) {
                        badge.innerHTML = '<span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold bg-rose-500/20 text-rose-300 border border-rose-500/30">✕ Ditolak</span>';
                    }
                    if (res.wa_link && confirm('Buka WhatsApp untuk mengirim pesan pemberitahuan sopan ke pendaftar?')) {
                        window.open(res.wa_link, '_blank');
                    }
                } else {
                    alert('Gagal: ' + (res.message || 'Terjadi kesalahan.'));
                }
            } catch (err) {
                alert('Terjadi kesalahan koneksi.');
            }
        }

        async function adminActivateAmbassador(id, name, institution) {
            const defaultCode = 'TILIK-' + institution.replace(/[^a-zA-Z]/g, '').substring(0, 4).toUpperCase() + '-' + name.split(' ')[0].toUpperCase();
            const code = prompt('Masukkan / Sesuaikan Kode Referral Unik untuk Duta Kampus ini:', defaultCode);
            if (!code) return;

            try {
                const res = await postJson('<?= base_url('admin/ambassador/activate') ?>/' + id, { referral_code: code });
                if (res.success) {
                    alert('🎉 Duta Kampus berhasil diaktivasi!\nKode Referral: ' + res.referral_code + '\nPaket Premium 30 hari telah aktif.');
                    const badge = document.getElementById('amb-status-badge-' + id);
                    if (badge) {
                        badge.innerHTML = '<span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">✓ Aktif</span>';
                    }
                    if (res.wa_link) {
                        window.open(res.wa_link, '_blank');
                    }
                    setTimeout(() => window.location.reload(), 1500);
                } else {
                    alert('Gagal aktivasi: ' + (res.message || 'Periksa kembali data.'));
                }
            } catch (err) {
                alert('Terjadi kesalahan koneksi.');
            }
        }

        async function adminApproveProof(id) {
            if (!confirm('Apakah Anda ingin memverifikasi dan menyetujui bukti promosi ini?')) return;
            try {
                const res = await postJson('<?= base_url('admin/ambassador/proof/approve') ?>/' + id, {});
                if (res.success) {
                    alert('✓ ' + res.message);
                    const badge = document.getElementById('proof-status-badge-' + id);
                    if (badge) {
                        badge.innerHTML = '<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30"><span>✓</span> <span>Disetujui</span></span>';
                    }
                    const actions = document.getElementById('proof-actions-' + id);
                    if (actions) {
                        actions.innerHTML = '<button type="button" onclick="adminRejectProof(' + id + ')" class="px-2.5 py-1 rounded-lg bg-slate-800 hover:bg-rose-900 text-slate-300 hover:text-white text-[11px] transition cursor-pointer" title="Tolak bukti promosi">✕ Tolak</button>';
                    }
                } else {
                    alert('Gagal: ' + (res.message || 'Terjadi kesalahan.'));
                }
            } catch (err) {
                alert('Terjadi kesalahan koneksi.');
            }
        }

        async function adminRejectProof(id) {
            const reason = prompt('Alasan penolakan bukti (opsional untuk catatan):', 'Tangkapan layar tidak jelas atau bukan materi AR Tilik');
            if (reason === null) return;
            try {
                const res = await postJson('<?= base_url('admin/ambassador/proof/reject') ?>/' + id, { admin_notes: reason });
                if (res.success) {
                    alert('✓ ' + res.message);
                    const badge = document.getElementById('proof-status-badge-' + id);
                    if (badge) {
                        badge.innerHTML = '<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-500/20 text-rose-300 border border-rose-500/30"><span>✕</span> <span>Ditolak</span></span>';
                    }
                    const actions = document.getElementById('proof-actions-' + id);
                    if (actions) {
                        actions.innerHTML = '<button type="button" onclick="adminApproveProof(' + id + ')" class="px-2.5 py-1 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-[11px] transition shadow-xs cursor-pointer" title="Verifikasi & setujui bukti promosi ini">✓ Setujui</button>';
                    }
                } else {
                    alert('Gagal: ' + (res.message || 'Terjadi kesalahan.'));
                }
            } catch (err) {
                alert('Terjadi kesalahan koneksi.');
            }
        }

        async function adminGraduateAmbassador(id) {
            if (!confirm('Apakah Anda yakin ingin meluluskan Duta Kampus ini dan menerbitkan Piagam Penghargaan Digital Resmi (PDF ber-QR Code)?')) return;
            try {
                const res = await postJson('<?= base_url('admin/ambassador/graduate') ?>/' + id, {});
                if (res.success) {
                    alert('✓ ' + res.message);
                    window.location.reload();
                } else {
                    alert('Gagal: ' + (res.message || 'Terjadi kesalahan saat meluluskan duta.'));
                }
            } catch (err) {
                alert('Terjadi kesalahan koneksi.');
            }
        }

        // ========================================================
        // SINKRONISASI REAL-TIME VISITOR & GRAFIK VISUAL (ZERO FLICKER)
        // ========================================================
        const initialVisitorDaily = <?= json_encode(array_map(function($row) {
            $u = (int) $row['unique_visitors'];
            $h = (int) $row['total_hits'];
            $a = $u > 0 ? round($h / $u, 2) : 1.00;
            return [
                'visit_date'      => $row['visit_date'],
                'full_date'       => date('d F Y', strtotime($row['visit_date'])),
                'unique_visitors' => $u,
                'total_hits'      => $h,
                'average_hits'    => number_format($a, 2),
            ];
        }, array_reverse($dailyVisitors ?? []))) ?>;

        let visitorChart = null;
        let rawDailyData = initialVisitorDaily || [];
        let currentRangeDays = 30;
        let visitorPollInterval = null;
        let isFetchingVisitor = false;

        function formatChartDate(dateStr) {
            if (!dateStr) return '';
            const d = new Date(dateStr);
            const months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
            return `${d.getDate()} ${months[d.getMonth()]}`;
        }

        function initVisitorChart() {
            const canvas = document.getElementById('visitorAnalyticsChart');
            if (!canvas) return;

            const sliced = rawDailyData.slice(-currentRangeDays);
            const labels = sliced.map(d => formatChartDate(d.visit_date));
            const uniqueData = sliced.map(d => Number(d.unique_visitors));
            const hitsData = sliced.map(d => Number(d.total_hits));

            const ctx = canvas.getContext('2d');

            // Soft gradients
            const gradientHits = ctx.createLinearGradient(0, 0, 0, 280);
            gradientHits.addColorStop(0, 'rgba(56, 189, 248, 0.28)');
            gradientHits.addColorStop(1, 'rgba(56, 189, 248, 0.00)');

            const gradientUnique = ctx.createLinearGradient(0, 0, 0, 280);
            gradientUnique.addColorStop(0, 'rgba(16, 185, 129, 0.30)');
            gradientUnique.addColorStop(1, 'rgba(16, 185, 129, 0.00)');

            visitorChart = new Chart(canvas, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'Total Tayangan (Hits)',
                            data: hitsData,
                            borderColor: '#38bdf8',
                            backgroundColor: gradientHits,
                            borderWidth: 2.5,
                            fill: true,
                            tension: 0.35,
                            pointBackgroundColor: '#0f172a',
                            pointBorderColor: '#38bdf8',
                            pointBorderWidth: 2,
                            pointRadius: 4,
                            pointHoverRadius: 6,
                            pointHoverBorderWidth: 3,
                            pointHoverBackgroundColor: '#38bdf8',
                            order: 2
                        },
                        {
                            label: 'Pengunjung Unik',
                            data: uniqueData,
                            borderColor: '#10b981',
                            backgroundColor: gradientUnique,
                            borderWidth: 2.5,
                            fill: true,
                            tension: 0.35,
                            pointBackgroundColor: '#0f172a',
                            pointBorderColor: '#10b981',
                            pointBorderWidth: 2,
                            pointRadius: 4,
                            pointHoverRadius: 6,
                            pointHoverBorderWidth: 3,
                            pointHoverBackgroundColor: '#10b981',
                            order: 1
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        mode: 'index',
                        intersect: false,
                    },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: 'rgba(15, 23, 42, 0.95)',
                            titleColor: '#ffffff',
                            titleFont: { size: 12, weight: '700', family: 'Plus Jakarta Sans' },
                            bodyColor: '#cbd5e1',
                            bodyFont: { size: 11, family: 'Plus Jakarta Sans' },
                            borderColor: 'rgba(51, 65, 85, 0.8)',
                            borderWidth: 1,
                            padding: 12,
                            cornerRadius: 12,
                            boxPadding: 6,
                            callbacks: {
                                title: function(items) {
                                    if (!items.length) return '';
                                    const idx = items[0].dataIndex;
                                    const d = sliced[idx];
                                    return d ? d.full_date || d.visit_date : items[0].label;
                                },
                                label: function(item) {
                                    return ` ${item.dataset.label}: ${item.raw.toLocaleString('id-ID')}`;
                                },
                                afterBody: function(items) {
                                    if (!items.length) return '';
                                    const idx = items[0].dataIndex;
                                    const d = sliced[idx];
                                    if (d && Number(d.unique_visitors) > 0) {
                                        const avg = (Number(d.total_hits) / Number(d.unique_visitors)).toFixed(2);
                                        return ` Rasio: ${avg} hits / pengunjung`;
                                    }
                                    return '';
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: {
                                color: 'rgba(255, 255, 255, 0.04)',
                                drawBorder: false
                            },
                            ticks: {
                                color: '#64748b',
                                font: { size: 10, family: 'Plus Jakarta Sans' },
                                maxRotation: 0,
                                autoSkip: true,
                                maxTicksLimit: 10
                            }
                        },
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(255, 255, 255, 0.05)',
                                drawBorder: false
                            },
                            ticks: {
                                color: '#64748b',
                                font: { size: 10, family: 'JetBrains Mono' },
                                precision: 0
                            }
                        }
                    }
                }
            });
        }

        function updateChartWithData(newDailyData) {
            if (Array.isArray(newDailyData) && newDailyData.length > 0) {
                rawDailyData = newDailyData;
            }
            if (!visitorChart) {
                initVisitorChart();
                return;
            }
            const sliced = rawDailyData.slice(-currentRangeDays);
            visitorChart.data.labels = sliced.map(d => formatChartDate(d.visit_date));
            visitorChart.data.datasets[0].data = sliced.map(d => Number(d.total_hits));
            visitorChart.data.datasets[1].data = sliced.map(d => Number(d.unique_visitors));
            visitorChart.update('none');
        }

        function setChartRange(days, btn) {
            currentRangeDays = days;
            document.querySelectorAll('.chart-range-btn').forEach(b => {
                b.classList.remove('bg-indigo-600', 'text-white', 'font-semibold', 'shadow-xs');
                b.classList.add('text-slate-300');
            });
            btn.classList.add('bg-indigo-600', 'text-white', 'font-semibold', 'shadow-xs');
            btn.classList.remove('text-slate-300');

            updateChartWithData(rawDailyData);
        }

        function toggleVisitorTable() {
            const wrap = document.getElementById('visitorTableWrapper');
            const label = document.getElementById('label-toggle-table');
            if (!wrap) return;

            if (wrap.classList.contains('hidden')) {
                wrap.classList.remove('hidden');
                if (label) label.textContent = 'Tutup Tabel Data';
            } else {
                wrap.classList.add('hidden');
                if (label) label.textContent = 'Buka Tabel Data';
            }
        }

        function updateNumberWithHighlight(el, newFormattedVal) {
            if (!el) return;
            const currentVal = el.textContent.trim();
            if (currentVal !== newFormattedVal) {
                el.textContent = newFormattedVal;
                el.classList.add('scale-105', 'text-emerald-300');
                setTimeout(() => {
                    el.classList.remove('scale-105', 'text-emerald-300');
                }, 800);
            }
        }

        async function refreshVisitorStatsRealtime(manual = false) {
            if (isFetchingVisitor) return;
            isFetchingVisitor = true;

            const syncEl = document.getElementById('visitor-last-sync');
            const syncBtn = document.getElementById('btn-manual-sync-visitors');
            if (manual && syncBtn) {
                syncBtn.classList.add('animate-spin');
            }

            try {
                const res = await fetch('<?= base_url('admin/analytics/visitors-realtime') ?>', {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                if (!res.ok) throw new Error('HTTP ' + res.status);
                const data = await res.json();

                if (data && data.status === 'success' && data.stats) {
                    const stats = data.stats;
                    // Update 4 Kartu Metrik
                    updateNumberWithHighlight(document.getElementById('metric-total-unique'), Number(stats.total_unique).toLocaleString('en-US'));
                    updateNumberWithHighlight(document.getElementById('metric-total-hits'), Number(stats.total_hits).toLocaleString('en-US'));
                    updateNumberWithHighlight(document.getElementById('metric-today-unique'), Number(stats.today_unique).toLocaleString('en-US'));
                    updateNumberWithHighlight(document.getElementById('metric-today-hits'), Number(stats.today_hits).toLocaleString('en-US'));

                    // Update Grafik Visual Real-time
                    if (Array.isArray(data.daily) && data.daily.length > 0) {
                        updateChartWithData(data.daily);
                    }

                    // Update Baris Tabel Hari Ini (Desktop & Mobile)
                    if (data.today && data.today.visit_date) {
                        const todayDate = data.today.visit_date;
                        const trToday = document.querySelector(`tr[data-visit-date="${todayDate}"]`);
                        if (trToday) {
                            const tdUnique = trToday.querySelector('[data-cell="unique"]');
                            const tdHits = trToday.querySelector('[data-cell="hits"]');
                            const tdAvg = trToday.querySelector('[data-cell="avg"]');
                            if (tdUnique) updateNumberWithHighlight(tdUnique, Number(data.today.unique_visitors).toLocaleString('en-US'));
                            if (tdHits) updateNumberWithHighlight(tdHits, Number(data.today.total_hits).toLocaleString('en-US'));
                            if (tdAvg) tdAvg.textContent = data.today.average_hits;
                        }

                        const mobToday = document.querySelector(`div[data-mobile-date="${todayDate}"]`);
                        if (mobToday) {
                            const mUnique = mobToday.querySelector('[data-cell="unique"]');
                            const mHits = mobToday.querySelector('[data-cell="hits"]');
                            const mAvg = mobToday.querySelector('[data-cell="avg"]');
                            if (mUnique) updateNumberWithHighlight(mUnique, Number(data.today.unique_visitors).toLocaleString('en-US'));
                            if (mHits) updateNumberWithHighlight(mHits, Number(data.today.total_hits).toLocaleString('en-US'));
                            if (mAvg) mAvg.textContent = 'Rata-rata: ' + data.today.average_hits + ' hits';
                        }
                    }

                    if (syncEl) {
                        syncEl.textContent = 'Sinkron: ' + (data.synced_at || new Date().toLocaleTimeString('id-ID'));
                    }
                }
            } catch (err) {
                console.warn('Realtime visitor poll skipped:', err.message);
                if (syncEl && manual) {
                    syncEl.textContent = 'Gagal sinkron';
                }
            } finally {
                isFetchingVisitor = false;
                if (syncBtn) syncBtn.classList.remove('animate-spin');
            }
        }

        function initVisitorRealtimePolling() {
            const metricEl = document.getElementById('metric-total-unique');
            if (!metricEl) return;

            // Inisialisasi Grafik
            initVisitorChart();

            const syncEl = document.getElementById('visitor-last-sync');
            if (syncEl) {
                const now = new Date();
                const hh = now.getHours().toString().padStart(2, '0');
                const mm = now.getMinutes().toString().padStart(2, '0');
                const ss = now.getSeconds().toString().padStart(2, '0');
                syncEl.textContent = 'Sinkron: ' + hh + ':' + mm + ':' + ss;
            }

            // Interval polling otomatis setiap 15 detik
            if (visitorPollInterval) clearInterval(visitorPollInterval);
            visitorPollInterval = setInterval(() => {
                if (!document.hidden) {
                    refreshVisitorStatsRealtime();
                }
            }, 15000);

            // Re-fetch saat tab browser kembali aktif
            document.addEventListener('visibilitychange', () => {
                if (!document.hidden) {
                    refreshVisitorStatsRealtime();
                }
            });
        }

        // ==========================================
        // REALTIME POLLING SIDEBAR BADGES (Auto-Reload Nilai Tanpa Refresh Halaman)
        // ==========================================
        let isFetchingSidebarBadges = false;
        let sidebarBadgesPollInterval = null;

        function updateBadgeWithHighlight(el, newFormattedVal, show = true) {
            if (!el) return;
            if (!show) {
                el.classList.add('hidden');
                return;
            }
            el.classList.remove('hidden');
            const currentVal = el.textContent.trim();
            const targetVal = String(newFormattedVal).trim();
            if (currentVal !== targetVal) {
                el.textContent = targetVal;
                el.classList.add('scale-110', 'ring-2', 'ring-emerald-500/50', 'transition-transform');
                setTimeout(() => {
                    el.classList.remove('scale-110', 'ring-2', 'ring-emerald-500/50', 'transition-transform');
                }, 900);
            }
        }

        async function refreshSidebarBadgesRealtime(manual = false) {
            if (isFetchingSidebarBadges) return;
            isFetchingSidebarBadges = true;

            const syncEl = document.getElementById('sidebar-last-sync');
            const syncBtn = document.getElementById('btn-manual-sync-sidebar');
            if (manual && syncBtn) {
                syncBtn.classList.add('animate-spin');
            }

            try {
                const res = await fetch('<?= base_url('admin/sidebar-badges-realtime') ?>', {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                if (!res.ok) throw new Error('HTTP ' + res.status);
                const data = await res.json();

                if (data && data.status === 'success' && data.badges) {
                    const b = data.badges;

                    // 1. Dokumen & Antrean (Hanya muncul jika ada antrean proses)
                    const queueCount = Number(b.queue || 0);
                    const elDoc = document.getElementById('sidebar-badge-documents');
                    if (elDoc) {
                        elDoc.setAttribute('title', queueCount + ' dokumen dalam antrean proses');
                        updateBadgeWithHighlight(elDoc, queueCount, queueCount > 0);
                    }

                    // 2. Google AI Keys (Pool) - Hanya tampil jika ada kendala / 0 aktif
                    const gActive = Number(b.google_keys_active || 0);
                    const gTroubled = Number(b.google_keys_troubled || 0);
                    const elGKeys = document.getElementById('sidebar-badge-google_keys');
                    if (elGKeys) {
                        if (gActive === 0) {
                            elGKeys.className = 'flex-shrink-0 min-w-5 h-5 px-1.5 inline-flex items-center justify-center rounded-full text-[10px] font-bold transition-all duration-300 bg-rose-500/20 text-rose-300 border border-rose-500/30 animate-pulse';
                            elGKeys.setAttribute('title', '0 kunci Google AI aktif (Darurat)');
                            updateBadgeWithHighlight(elGKeys, '0', true);
                        } else if (gTroubled > 0) {
                            elGKeys.className = 'flex-shrink-0 min-w-5 h-5 px-1.5 inline-flex items-center justify-center rounded-full text-[10px] font-bold transition-all duration-300 bg-amber-500/20 text-amber-300 border border-amber-500/30';
                            elGKeys.setAttribute('title', gTroubled + ' kunci Google AI berstatus limit/bermasalah');
                            updateBadgeWithHighlight(elGKeys, gTroubled, true);
                        } else {
                            elGKeys.classList.add('hidden');
                        }
                    }

                    // 3. Transaksi & Pembayaran - Hanya tampil jika ada pembayaran pending
                    const pendingPay = Number(b.payments_pending || 0);
                    const elPay = document.getElementById('sidebar-badge-payments');
                    if (elPay) {
                        elPay.setAttribute('title', pendingPay + ' transaksi pending menunggu konfirmasi');
                        updateBadgeWithHighlight(elPay, pendingPay, pendingPay > 0);
                    }

                    // 4. Feedback & Testimoni - Hanya tampil jika ada ulasan menunggu moderasi
                    const pendingTestims = Number(b.testimonials_pending || 0);
                    const elFeed = document.getElementById('sidebar-badge-feedback');
                    if (elFeed) {
                        elFeed.setAttribute('title', pendingTestims + ' ulasan baru menunggu moderasi');
                        updateBadgeWithHighlight(elFeed, pendingTestims, pendingTestims > 0);
                    }

                    // 5. Duta Kampus (Afiliasi) - Hanya tampil jika ada pendaftar menunggu review
                    const ambPending = Number(b.ambassador_pending || 0);
                    const elAmb = document.getElementById('sidebar-badge-ambassador');
                    if (elAmb) {
                        elAmb.setAttribute('title', ambPending + ' pendaftar baru menunggu review');
                        updateBadgeWithHighlight(elAmb, ambPending, ambPending > 0);
                    }

                    // 6. Keamanan & Limit - Hanya tampil jika ada lock akun/IP
                    const secLocks = Number(b.security_locks || 0);
                    const elSec = document.getElementById('sidebar-badge-security');
                    if (elSec) {
                        elSec.setAttribute('title', secLocks + ' akun/IP terkunci');
                        updateBadgeWithHighlight(elSec, secLocks, secLocks > 0);
                    }

                    // Update timestamp indicator
                    if (syncEl && data.synced_at) {
                        syncEl.textContent = data.synced_at;
                    }
                }
            } catch (err) {
                console.warn('[SidebarRealtime] Polling error:', err);
            } finally {
                isFetchingSidebarBadges = false;
                if (syncBtn) {
                    setTimeout(() => syncBtn.classList.remove('animate-spin'), 300);
                }
            }
        }

        function initSidebarRealtimePolling() {
            // Polling otomatis setiap 12 detik
            if (sidebarBadgesPollInterval) clearInterval(sidebarBadgesPollInterval);
            sidebarBadgesPollInterval = setInterval(() => {
                if (!document.hidden) {
                    refreshSidebarBadgesRealtime();
                }
            }, 12000);

            // Re-fetch saat tab browser kembali aktif
            document.addEventListener('visibilitychange', () => {
                if (!document.hidden) {
                    refreshSidebarBadgesRealtime();
                }
            });
        }

        // ==========================================
        // Explicit Action Confirmation Modal Engine
        // ==========================================
        let adminActionCallback = null;

        function showAdminToast(message, isError = false) {
            const toast = document.getElementById('adminToast');
            const toastMsg = document.getElementById('adminToastMsg');
            const toastIcon = document.getElementById('adminToastIcon');
            if (!toast) return;

            toastMsg.textContent = message;
            toastIcon.textContent = isError ? '❌' : '✅';
            toast.className = 'fixed bottom-5 right-5 z-50 transform translate-y-0 opacity-100 transition-all duration-300 flex items-center gap-2.5 px-4 py-3 rounded-xl shadow-xl text-xs font-semibold ' + 
                (isError ? 'bg-rose-950 border border-rose-500/50 text-rose-200' : 'bg-slate-900 border border-emerald-500/50 text-emerald-200');

            setTimeout(() => {
                toast.className = 'fixed bottom-5 right-5 z-50 transform translate-y-20 opacity-0 pointer-events-none transition-all duration-300 flex items-center gap-2.5 px-4 py-3 rounded-xl shadow-xl text-xs font-semibold bg-slate-900 border border-slate-700 text-white';
            }, 3500);
        }

        function openAdminActionModal({ title, subtitle, message, icon = '⚠️', type = 'danger', confirmText = 'Lanjutkan', onConfirm }) {
            const modal = document.getElementById('adminActionModal');
            const titleEl = document.getElementById('modalTitle');
            const subEl = document.getElementById('modalSubtitle');
            const msgEl = document.getElementById('modalMessage');
            const iconEl = document.getElementById('modalIcon');
            const iconContainer = document.getElementById('modalIconContainer');
            const confirmBtn = document.getElementById('modalConfirmBtn');

            if (!modal) return;

            titleEl.textContent = title;
            subEl.textContent = subtitle || 'Tindakan ini membutuhkan verifikasi eksplisit';
            msgEl.innerHTML = message;
            iconEl.textContent = icon;

            // Styling based on type
            if (type === 'danger') {
                iconContainer.className = 'w-11 h-11 rounded-xl flex items-center justify-center shrink-0 bg-rose-500/20 text-rose-400 border border-rose-500/30';
                confirmBtn.className = 'px-4 py-2 rounded-xl bg-rose-600 hover:bg-rose-500 text-white text-xs font-bold shadow-md transition-all cursor-pointer';
            } else if (type === 'purple') {
                iconContainer.className = 'w-11 h-11 rounded-xl flex items-center justify-center shrink-0 bg-purple-500/20 text-purple-400 border border-purple-500/30';
                confirmBtn.className = 'px-4 py-2 rounded-xl bg-purple-600 hover:bg-purple-500 text-white text-xs font-bold shadow-md transition-all cursor-pointer';
            } else if (type === 'warning') {
                iconContainer.className = 'w-11 h-11 rounded-xl flex items-center justify-center shrink-0 bg-amber-500/20 text-amber-400 border border-amber-500/30';
                confirmBtn.className = 'px-4 py-2 rounded-xl bg-amber-600 hover:bg-amber-500 text-white text-xs font-bold shadow-md transition-all cursor-pointer';
            } else {
                iconContainer.className = 'w-11 h-11 rounded-xl flex items-center justify-center shrink-0 bg-indigo-500/20 text-indigo-400 border border-indigo-500/30';
                confirmBtn.className = 'px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold shadow-md transition-all cursor-pointer';
            }
            confirmBtn.textContent = confirmText;

            adminActionCallback = onConfirm;
            confirmBtn.onclick = async () => {
                confirmBtn.disabled = true;
                const origText = confirmBtn.textContent;
                confirmBtn.textContent = 'Memproses...';
                try {
                    if (adminActionCallback) await adminActionCallback();
                } catch (e) {
                    console.error(e);
                    showAdminToast('Terjadi kesalahan saat mengeksekusi aksi.', true);
                } finally {
                    confirmBtn.disabled = false;
                    confirmBtn.textContent = origText;
                    closeAdminActionModal();
                }
            };

            modal.classList.remove('hidden');
        }

        function closeAdminActionModal() {
            const modal = document.getElementById('adminActionModal');
            if (modal) modal.classList.add('hidden');
            adminActionCallback = null;
        }

        function confirmDeleteDocument(docId, title, rowId) {
            openAdminActionModal({
                title: 'Musnahkan Dokumen?',
                subtitle: 'Aksi destruktif permanen sistem',
                icon: '🗑️',
                type: 'danger',
                confirmText: 'Ya, Musnahkan Dokumen',
                message: 'Apakah Anda yakin ingin memusnahkan dokumen <strong class="text-rose-300">"' + (title || '#' + docId) + '"</strong> beserta seluruh riwayat analisis kemiripan dan sitasinya? Berkas dan rekaman audit akan dihapus permanen.',
                onConfirm: async () => {
                    const res = await postJson('<?= base_url('admin/documents/delete') ?>', { document_id: docId });
                    if (res.status === 'success') {
                        showAdminToast(res.message || 'Dokumen berhasil dihapus.');
                        if (rowId) {
                            const el = document.getElementById(rowId);
                            if (el) {
                                el.style.transition = 'all 0.3s ease';
                                el.style.opacity = '0';
                                el.style.transform = 'scale(0.95)';
                                setTimeout(() => el.remove(), 300);
                            }
                        } else {
                            setTimeout(() => window.location.reload(), 600);
                        }
                    } else {
                        showAdminToast(res.message || 'Gagal menghapus dokumen.', true);
                    }
                }
            });
        }

        function confirmRoleChange(userId, userName, targetRole) {
            const isEscalation = (targetRole === 'admin');
            openAdminActionModal({
                title: isEscalation ? 'Eskalasi Hak Akses Admin' : 'Cabut Hak Akses Administrator',
                subtitle: isEscalation ? 'Pemberian wewenang level tertinggi' : 'Penurunan peran ke pengguna biasa',
                icon: isEscalation ? '👑' : '👤',
                type: isEscalation ? 'purple' : 'warning',
                confirmText: isEscalation ? 'Ya, Jadikan Administrator' : 'Ya, Jadikan User Biasa',
                message: isEscalation 
                    ? 'Pengguna <strong class="text-purple-300">"' + userName + '"</strong> akan mendapatkan hak akses penuh ke seluruh menu Admin (manajemen korpus, kuota, kunci Google AI, transaksi, dan data keamanan). Pastikan pengguna ini tepercaya.' 
                    : 'Pengguna <strong class="text-amber-300">"' + userName + '"</strong> akan dicabut hak akses administrasinya dan kembali menjadi pengguna biasa.',
                onConfirm: async () => {
                    const res = await postJson('<?= base_url('admin/users/update') ?>', {
                        user_id: userId,
                        action: 'set_role',
                        role: targetRole
                    });
                    if (res.status === 'success') {
                        showAdminToast(res.message || 'Peran pengguna berhasil diperbarui.');
                        setTimeout(() => window.location.reload(), 700);
                    } else {
                        showAdminToast(res.message || 'Gagal mengubah peran.', true);
                    }
                }
            });
        }

        function confirmClearAllLocks() {
            openAdminActionModal({
                title: 'Reset Semua Penguncian Keamanan?',
                subtitle: 'Aksi massal log keamanan & rate limit',
                icon: '🔓',
                type: 'danger',
                confirmText: 'Ya, Buka Semua Kuncian',
                message: 'Seluruh catatan pemblokiran IP, proteksi brute-force login, dan pembatasan frekuensi (rate limit) akan direset bersih. Seluruh IP yang sebelumnya dicekal akan dapat mengakses kembali.',
                onConfirm: async () => {
                    const res = await postJson('<?= base_url('admin/security/clear-all') ?>', {});
                    if (res.status === 'success') {
                        showAdminToast(res.message || 'Semua penguncian keamanan berhasil direset.');
                        setTimeout(() => window.location.reload(), 700);
                    } else {
                        showAdminToast(res.message || 'Gagal mereset penguncian.', true);
                    }
                }
            });
        }

        function confirmDeleteTestimonial(id, authorName) {
            openAdminActionModal({
                title: 'Hapus Testimoni Permanen?',
                subtitle: 'Penghapusan ulasan pengguna',
                icon: '💬',
                type: 'danger',
                confirmText: 'Ya, Hapus Testimoni',
                message: 'Ulasan dari <strong class="text-rose-300">"' + (authorName || 'Pengguna #' + id) + '"</strong> akan dihapus permanen dari basis data dan tidak akan ditampilkan lagi di halaman publik.',
                onConfirm: async () => {
                    const res = await postJson('<?= base_url('admin/testimonials/delete') ?>/' + id, {});
                    if (res.status === 'success') {
                        showAdminToast(res.message || 'Testimoni berhasil dihapus.');
                        const row = document.getElementById('testi-row-' + id);
                        if (row) {
                            row.style.transition = 'all 0.3s ease';
                            row.style.opacity = '0';
                            setTimeout(() => row.remove(), 300);
                        }
                    } else {
                        showAdminToast(res.message || 'Gagal menghapus testimoni.', true);
                    }
                }
            });
        }

        document.addEventListener('DOMContentLoaded', () => {
            initVisitorRealtimePolling();
            initSidebarRealtimePolling();
        });
    </script>

    <!-- ======================================================== -->
    <!-- MODAL KONFIRMASI EKSPLISIT AKSI DESTRUKTIF & ESKALASI   -->
    <!-- ======================================================== -->
    <div id="adminActionModal" class="fixed inset-0 z-50 bg-slate-950/80 backdrop-blur-xs flex items-center justify-center p-4 hidden">
        <div class="bg-slate-900 border border-slate-700/80 rounded-2xl max-w-md w-full shadow-2xl overflow-hidden animate-in fade-in zoom-in-95 duration-150">
            <div class="p-6 space-y-4">
                <div class="flex items-center gap-3">
                    <div id="modalIconContainer" class="w-11 h-11 rounded-xl flex items-center justify-center shrink-0 bg-rose-500/20 text-rose-400 border border-rose-500/30">
                        <span id="modalIcon" class="text-xl">🗑️</span>
                    </div>
                    <div>
                        <h3 id="modalTitle" class="text-base font-bold text-white leading-tight">Konfirmasi Tindakan</h3>
                        <p id="modalSubtitle" class="text-xs text-slate-400 mt-0.5">Tindakan ini membutuhkan verifikasi eksplisit</p>
                    </div>
                </div>
                <div id="modalMessage" class="text-xs text-slate-300 bg-slate-950/70 p-3.5 rounded-xl border border-slate-800 leading-relaxed font-sans">
                    <!-- Dynamic message -->
                </div>
                <div class="flex items-center justify-end gap-2.5 pt-2">
                    <button type="button" onclick="closeAdminActionModal()" class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-semibold transition-colors cursor-pointer">
                        Batal
                    </button>
                    <button type="button" id="modalConfirmBtn" class="px-4 py-2 rounded-xl text-white text-xs font-bold shadow-md transition-all cursor-pointer">
                        Lanjutkan
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Admin Toast Notification -->
    <div id="adminToast" class="fixed bottom-5 right-5 z-50 transform translate-y-20 opacity-0 pointer-events-none transition-all duration-300 flex items-center gap-2.5 px-4 py-3 rounded-xl shadow-xl text-xs font-semibold bg-slate-900 border border-slate-700 text-white">
        <span id="adminToastIcon">✅</span>
        <span id="adminToastMsg">Berhasil</span>
    </div>
</body>
</html>
