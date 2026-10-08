<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal Administrator - AR Tilik Academic Writing Similarity Checker</title>
    <!-- Favicon & Brand Icons -->
    <link rel="icon" type="image/x-icon" href="<?= base_url('favicon.ico?v=20261008') ?>">
    <link rel="icon" type="image/png" sizes="32x32" href="<?= base_url('favicon-32x32.png?v=20261008') ?>">
    <link rel="icon" type="image/png" sizes="16x16" href="<?= base_url('favicon-16x16.png?v=20261008') ?>">
    <link rel="apple-touch-icon" sizes="180x180" href="<?= base_url('apple-touch-icon.png?v=20261008') ?>">
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
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
    </style>
</head>
<body class="bg-slate-900 text-slate-100 antialiased min-h-screen flex flex-col">

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
                    <div class="px-3 pb-1.5 text-[10px] font-bold uppercase tracking-wider text-slate-500">
                        Menu Utama
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
                    <a href="<?= base_url('admin?tab=users') ?>" class="group flex items-center justify-between px-3 py-2.5 rounded-xl text-xs font-medium transition-all duration-150 <?= $activeTab === 'users' ? 'bg-indigo-600/15 text-indigo-300 font-semibold border border-indigo-500/30 shadow-xs' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/60 border border-transparent' ?>">
                        <div class="flex items-center gap-2.5">
                            <svg class="w-4 h-4 <?= $activeTab === 'users' ? 'text-indigo-400' : 'text-slate-400 group-hover:text-slate-200' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                            <span>Pengguna & Kuota</span>
                        </div>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold <?= $activeTab === 'users' ? 'bg-indigo-500/20 text-indigo-300' : 'bg-slate-800 text-slate-300' ?>">
                            <?= (int) $metrics['total_users'] ?>
                        </span>
                    </a>
                    <a href="<?= base_url('admin?tab=documents') ?>" class="group flex items-center justify-between px-3 py-2.5 rounded-xl text-xs font-medium transition-all duration-150 <?= $activeTab === 'documents' ? 'bg-indigo-600/15 text-indigo-300 font-semibold border border-indigo-500/30 shadow-xs' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/60 border border-transparent' ?>">
                        <div class="flex items-center gap-2.5">
                            <svg class="w-4 h-4 <?= $activeTab === 'documents' ? 'text-indigo-400' : 'text-slate-400 group-hover:text-slate-200' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                            <span>Dokumen & Antrean</span>
                        </div>
                        <?php if ($metrics['queue_uploaded'] + $metrics['queue_processing'] > 0): ?>
                            <span class="px-2 py-0.5 rounded-full text-[10px] bg-amber-500/20 text-amber-300 border border-amber-500/30 font-semibold animate-pulse">
                                <?= $metrics['queue_uploaded'] + $metrics['queue_processing'] ?> proses
                            </span>
                        <?php endif; ?>
                    </a>
                    <a href="<?= base_url('admin?tab=corpus') ?>" class="group flex items-center justify-between px-3 py-2.5 rounded-xl text-xs font-medium transition-all duration-150 <?= $activeTab === 'corpus' ? 'bg-indigo-600/15 text-indigo-300 font-semibold border border-indigo-500/30 shadow-xs' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/60 border border-transparent' ?>">
                        <div class="flex items-center gap-2.5">
                            <svg class="w-4 h-4 <?= $activeTab === 'corpus' ? 'text-indigo-400' : 'text-slate-400 group-hover:text-slate-200' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
                            <span>Korpus Repositori</span>
                        </div>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold <?= $activeTab === 'corpus' ? 'bg-indigo-500/20 text-indigo-300' : 'bg-slate-800 text-slate-300' ?>">
                            <?= (int) $metrics['total_corpus'] ?>
                        </span>
                    </a>
                </div>

                <!-- Group 3: Analisis & Integrasi -->
                <div class="space-y-1">
                    <div class="px-3 pb-1.5 text-[10px] font-bold uppercase tracking-wider text-slate-500">
                        Analisis & Ekosistem
                    </div>
                    <a href="<?= base_url('admin?tab=analytics') ?>" class="group flex items-center justify-between px-3 py-2.5 rounded-xl text-xs font-medium transition-all duration-150 <?= $activeTab === 'analytics' ? 'bg-indigo-600/15 text-indigo-300 font-semibold border border-indigo-500/30 shadow-xs' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/60 border border-transparent' ?>">
                        <div class="flex items-center gap-2.5">
                            <svg class="w-4 h-4 <?= $activeTab === 'analytics' ? 'text-indigo-400' : 'text-slate-400 group-hover:text-slate-200' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                            <span>Analitik Institusi</span>
                        </div>
                        <span class="px-2 py-0.5 rounded-full text-[10px] bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 font-semibold">
                            <?= (int) ($integrityAnalytics['total_audited'] ?? 0) ?>
                        </span>
                    </a>
                    <a href="<?= base_url('admin?tab=api_keys') ?>" class="group flex items-center justify-between px-3 py-2.5 rounded-xl text-xs font-medium transition-all duration-150 <?= $activeTab === 'api_keys' ? 'bg-indigo-600/15 text-indigo-300 font-semibold border border-indigo-500/30 shadow-xs' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/60 border border-transparent' ?>">
                        <div class="flex items-center gap-2.5">
                            <svg class="w-4 h-4 <?= $activeTab === 'api_keys' ? 'text-indigo-400' : 'text-slate-400 group-hover:text-slate-200' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path></svg>
                            <span>Integrasi API & OJS</span>
                        </div>
                        <span class="px-2 py-0.5 rounded-full text-[10px] bg-indigo-500/20 text-indigo-300 border border-indigo-500/30 font-semibold">
                            <?= count($apiKeysList ?? []) ?>
                        </span>
                    </a>
                    <a href="<?= base_url('admin?tab=payments') ?>" class="group flex items-center justify-between px-3 py-2.5 rounded-xl text-xs font-medium transition-all duration-150 <?= $activeTab === 'payments' ? 'bg-indigo-600/15 text-indigo-300 font-semibold border border-indigo-500/30 shadow-xs' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/60 border border-transparent' ?>">
                        <div class="flex items-center gap-2.5">
                            <svg class="w-4 h-4 <?= $activeTab === 'payments' ? 'text-indigo-400' : 'text-slate-400 group-hover:text-slate-200' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>
                            <span>Transaksi & Pembayaran</span>
                        </div>
                        <?php if (!empty($metrics['payment_stats']['settled_count'])): ?>
                            <span class="px-2 py-0.5 rounded-full text-[10px] bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 font-semibold">
                                <?= (int) $metrics['payment_stats']['settled_count'] ?> lunas
                            </span>
                        <?php endif; ?>
                    </a>
                </div>

                <!-- Group 4: Sistem & Keamanan -->
                <div class="space-y-1">
                    <div class="px-3 pb-1.5 text-[10px] font-bold uppercase tracking-wider text-slate-500">
                        Sistem & Keamanan
                    </div>
                    <a href="<?= base_url('admin?tab=security') ?>" class="group flex items-center justify-between px-3 py-2.5 rounded-xl text-xs font-medium transition-all duration-150 <?= $activeTab === 'security' ? 'bg-indigo-600/15 text-indigo-300 font-semibold border border-indigo-500/30 shadow-xs' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/60 border border-transparent' ?>">
                        <div class="flex items-center gap-2.5">
                            <svg class="w-4 h-4 <?= $activeTab === 'security' ? 'text-indigo-400' : 'text-slate-400 group-hover:text-slate-200' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                            <span>Keamanan & Limit</span>
                        </div>
                        <?php if ($metrics['active_locks_count'] > 0): ?>
                            <span class="px-2 py-0.5 rounded-full text-[10px] bg-rose-500/20 text-rose-300 border border-rose-500/30 font-semibold">
                                <?= $metrics['active_locks_count'] ?> lock
                            </span>
                        <?php endif; ?>
                    </a>
                    <a href="<?= base_url('admin?tab=visitors') ?>" class="group flex items-center justify-between px-3 py-2.5 rounded-xl text-xs font-medium transition-all duration-150 <?= $activeTab === 'visitors' ? 'bg-indigo-600/15 text-indigo-300 font-semibold border border-indigo-500/30 shadow-xs' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/60 border border-transparent' ?>">
                        <div class="flex items-center gap-2.5">
                            <svg class="w-4 h-4 <?= $activeTab === 'visitors' ? 'text-indigo-400' : 'text-slate-400 group-hover:text-slate-200' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                            <span>Pengunjung</span>
                        </div>
                    </a>
                    <a href="<?= base_url('admin?tab=system') ?>" class="group flex items-center justify-between px-3 py-2.5 rounded-xl text-xs font-medium transition-all duration-150 <?= $activeTab === 'system' ? 'bg-indigo-600/15 text-indigo-300 font-semibold border border-indigo-500/30 shadow-xs' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/60 border border-transparent' ?>">
                        <div class="flex items-center gap-2.5">
                            <svg class="w-4 h-4 <?= $activeTab === 'system' ? 'text-indigo-400' : 'text-slate-400 group-hover:text-slate-200' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                            <span>Sistem Server</span>
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
                            <span class="text-xs text-purple-400 font-medium">Karya Terindeks</span>
                        </div>
                        <div class="mt-2 text-xs text-slate-400">
                            <span>Basis pembanding TF-IDF & Exact Shingle</span>
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
                        <div class="overflow-x-auto">
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
                                    <?php $recDocs = array_slice($documentsList, 0, 7); ?>
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
                        <div class="overflow-x-auto">
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
                                    <?php $recUsers = array_slice($usersList, 0, 7); ?>
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
                                                        <span class="px-2 py-0.5 rounded-full text-[10px] bg-amber-500/10 text-amber-400 border border-amber-500/20 font-semibold">Pending</span>
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

                <!-- Table Pengguna -->
                <div class="bg-slate-800/40 border border-slate-700/60 rounded-2xl overflow-hidden shadow-sm">
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
                                                    <?php if ($u['status'] === 'active'): ?>
                                                        <span class="px-2 py-0.5 rounded text-[10px] bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 font-semibold">Aktif</span>
                                                    <?php elseif ($u['status'] === 'pending_verification'): ?>
                                                        <span class="px-2 py-0.5 rounded text-[10px] bg-amber-500/10 text-amber-400 border border-amber-500/20 font-semibold">Pending Email</span>
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
                                            <td class="py-3 px-3 text-slate-400 text-[11px]">
                                                <?= date('d M Y', strtotime($u['created_at'])) ?>
                                            </td>
                                            <td class="py-3 px-4 text-right">
                                                <div class="flex items-center justify-end gap-1.5 flex-wrap">
                                                    <!-- Verifikasi Manual jika pending -->
                                                    <?php if ($u['status'] === 'pending_verification'): ?>
                                                        <button onclick="handleUserAction(<?= (int) $u['id'] ?>, 'verify_email')" class="px-2 py-1 rounded bg-emerald-600/20 hover:bg-emerald-600/30 text-emerald-300 border border-emerald-500/30 text-[10px] font-semibold transition-colors" title="Verifikasi email manual tanpa menunggu klik tautan">
                                                            ✓ Verifikasi
                                                        </button>
                                                    <?php endif; ?>

                                                    <!-- Reset Mingguan -->
                                                    <button onclick="handleUserAction(<?= (int) $u['id'] ?>, 'reset_weekly_quota')" class="px-2 py-1 rounded bg-slate-800 hover:bg-slate-700 text-slate-300 border border-slate-700 text-[10px] transition-colors" title="Reset kuota terpakai mingguan ke 0">
                                                        ↺ Reset Kuota
                                                    </button>

                                                    <!-- Tambah Bonus Kuota -->
                                                    <button onclick="promptBonusQuota(<?= (int) $u['id'] ?>)" class="px-2 py-1 rounded bg-indigo-600/20 hover:bg-indigo-600/30 text-indigo-300 border border-indigo-500/30 text-[10px] font-semibold transition-colors" title="Tambah bonus kuota submisi">
                                                        + Kuota
                                                    </button>

                                                    <!-- Toggle Role -->
                                                    <button onclick="handleUserAction(<?= (int) $u['id'] ?>, 'set_role', {role: '<?= $u['role'] === 'admin' ? 'user' : 'admin' ?>'})" class="px-2 py-1 rounded bg-purple-600/20 hover:bg-purple-600/30 text-purple-300 border border-purple-500/30 text-[10px] transition-colors" title="Ganti peran antara Admin dan Pengguna">
                                                        <?= $u['role'] === 'admin' ? 'Jadikan User' : 'Jadikan Admin' ?>
                                                    </button>

                                                    <!-- Toggle Status -->
                                                    <button onclick="handleUserAction(<?= (int) $u['id'] ?>, 'set_status', {status: '<?= $u['status'] === 'active' ? 'inactive' : 'active' ?>'})" class="px-2 py-1 rounded <?= $u['status'] === 'active' ? 'bg-rose-600/20 hover:bg-rose-600/30 text-rose-300 border-rose-500/30' : 'bg-emerald-600/20 hover:bg-emerald-600/30 text-emerald-300 border-emerald-500/30' ?> border text-[10px] transition-colors">
                                                        <?= $u['status'] === 'active' ? 'Suspend' : 'Aktifkan' ?>
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
            <div class="space-y-6">
                <div>
                    <h2 class="text-lg font-bold text-white">Monitoring Antrean & Audit Dokumen</h2>
                    <p class="text-xs text-slate-400">Seluruh dokumen yang diuji kemiripan dan status eksekusi worker analisis</p>
                </div>

                <div class="bg-slate-800/40 border border-slate-700/60 rounded-2xl overflow-hidden shadow-sm">
                    <div class="overflow-x-auto">
                        <table class="w-full text-xs text-left">
                            <thead class="bg-slate-900/80 text-slate-400 uppercase tracking-wider text-[10px] border-b border-slate-700/60">
                                <tr>
                                    <th class="py-3 px-4">Judul & File</th>
                                    <th class="py-3 px-3">Pengunggah</th>
                                    <th class="py-3 px-3">Tipe</th>
                                    <th class="py-3 px-3">Status Worker</th>
                                    <th class="py-3 px-3 text-center">Skor Kemiripan</th>
                                    <th class="py-3 px-3">Waktu Masuk</th>
                                    <th class="py-3 px-4 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-800/80">
                                <?php if (empty($documentsList)): ?>
                                    <tr><td colspan="7" class="py-6 text-center text-slate-500">Tidak ada dokumen ditemukan.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($documentsList as $d): ?>
                                        <tr class="hover:bg-slate-800/50 transition-colors">
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
                                                <?php if (isset($d['report']['overall_score'])): ?>
                                                    <div class="font-bold <?= $d['report']['overall_score'] >= 25 ? 'text-rose-400' : 'text-emerald-400' ?>">
                                                        <?= number_format($d['report']['overall_score'], 1) ?>%
                                                    </div>
                                                    <div class="text-[10px] text-slate-500">
                                                        V: <?= number_format($d['report']['verbatim_score'] ?? 0, 1) ?>% | P: <?= number_format($d['report']['paraphrase_score'] ?? 0, 1) ?>%
                                                    </div>
                                                <?php else: ?>
                                                    <span class="text-slate-500">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="py-3 px-3 text-slate-400 text-[11px]">
                                                <?= date('d M Y, H:i', strtotime($d['created_at'])) ?>
                                            </td>
                                            <td class="py-3 px-4 text-right">
                                                <div class="flex items-center justify-end gap-1.5">
                                                    <?php if (isset($d['report']['id'])): ?>
                                                        <a href="<?= base_url('report/' . $d['report']['id']) ?>" target="_blank" class="px-2 py-1 rounded bg-indigo-600/20 hover:bg-indigo-600/30 text-indigo-300 border border-indigo-500/30 text-[10px] font-semibold transition-colors">
                                                            Laporan
                                                        </a>
                                                    <?php endif; ?>
                                                    <button onclick="handleDocReprocess(<?= (int) $d['id'] ?>)" class="px-2 py-1 rounded bg-sky-600/20 hover:bg-sky-600/30 text-sky-300 border border-sky-500/30 text-[10px] transition-colors" title="Jalankan ulang analisis dokumen ini">
                                                        ↺ Re-process
                                                    </button>
                                                    <button onclick="handleDocDelete(<?= (int) $d['id'] ?>)" class="px-2 py-1 rounded bg-rose-600/20 hover:bg-rose-600/30 text-rose-300 border border-rose-500/30 text-[10px] transition-colors" title="Hapus dokumen dari sistem">
                                                        ✕
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
        <!-- TAB 4: KORPUS REPOSITORI                   -->
        <!-- ========================================== -->
        <?php elseif ($activeTab === 'corpus'): ?>
            <div class="space-y-6">
                <div>
                    <h2 class="text-lg font-bold text-white">Manajemen Korpus & Repositori Ilmiah</h2>
                    <p class="text-xs text-slate-400">Basis data naskah pembanding untuk algoritma pencocokan kemiripan</p>
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

                <div class="bg-slate-800/40 border border-slate-700/60 rounded-2xl overflow-hidden shadow-sm">
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
                                        <tr class="hover:bg-slate-800/50 transition-colors">
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
                                            <td class="py-3 px-3 text-slate-400 text-[11px]">
                                                <?= number_format($c['file_size'] / 1024, 1) ?> KB
                                            </td>
                                            <td class="py-3 px-3 text-slate-400 text-[11px]">
                                                <?= date('d M Y', strtotime($c['created_at'])) ?>
                                            </td>
                                            <td class="py-3 px-4 text-right">
                                                <button onclick="handleDocDelete(<?= (int) $c['id'] ?>)" class="px-2 py-1 rounded bg-rose-600/20 hover:bg-rose-600/30 text-rose-300 border border-rose-500/30 text-[10px] transition-colors" title="Hapus korpus">
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
                        <button onclick="handleClearAllLocks()" class="px-3 py-1.5 rounded-xl bg-rose-600/20 hover:bg-rose-600/30 text-rose-300 border border-rose-500/30 text-xs font-semibold flex items-center gap-1.5 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                            Reset Semua Penguncian
                        </button>
                    <?php endif; ?>
                </div>

                <div class="bg-slate-800/40 border border-slate-700/60 rounded-2xl overflow-hidden shadow-sm">
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
                                            <td class="py-3 px-3 text-slate-400 text-[11px]">
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
                                            <td class="py-3 px-4 text-right">
                                                <button onclick="handleUnblock(<?= (int) $log['id'] ?>)" class="px-2.5 py-1 rounded bg-emerald-600/20 hover:bg-emerald-600/30 text-emerald-300 border border-emerald-500/30 text-[10px] font-semibold transition-colors" title="Buka blokir IP/identitas ini">
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
                <div>
                    <h2 class="text-lg font-bold text-white">Analitik Pengunjung Unik Landing Page</h2>
                    <p class="text-xs text-slate-400">Data analitik anonim pelacak pengunjung landing page 30 hari terakhir</p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="bg-slate-800/40 border border-slate-700/60 rounded-2xl p-4">
                        <span class="text-xs text-slate-400">Total Pengunjung Unik</span>
                        <div class="text-2xl font-bold text-white mt-1"><?= number_format($metrics['visitor_stats']['total_unique'] ?? 0) ?></div>
                    </div>
                    <div class="bg-slate-800/40 border border-slate-700/60 rounded-2xl p-4">
                        <span class="text-xs text-slate-400">Total Tayangan Halaman</span>
                        <div class="text-2xl font-bold text-sky-400 mt-1"><?= number_format($metrics['visitor_stats']['total_hits'] ?? 0) ?></div>
                    </div>
                    <div class="bg-slate-800/40 border border-slate-700/60 rounded-2xl p-4">
                        <span class="text-xs text-slate-400">Pengunjung Unik Hari Ini</span>
                        <div class="text-2xl font-bold text-emerald-400 mt-1"><?= number_format($metrics['visitor_stats']['today_unique'] ?? 0) ?></div>
                    </div>
                    <div class="bg-slate-800/40 border border-slate-700/60 rounded-2xl p-4">
                        <span class="text-xs text-slate-400">Tayangan Laman Hari Ini</span>
                        <div class="text-2xl font-bold text-purple-400 mt-1"><?= number_format($metrics['visitor_stats']['today_hits'] ?? 0) ?></div>
                    </div>
                </div>

                <div class="bg-slate-800/40 border border-slate-700/60 rounded-2xl overflow-hidden shadow-sm">
                    <div class="overflow-x-auto">
                        <table class="w-full text-xs text-left">
                            <thead class="bg-slate-900/80 text-slate-400 uppercase tracking-wider text-[10px] border-b border-slate-700/60">
                                <tr>
                                    <th class="py-3 px-4">Tanggal Kunjungan</th>
                                    <th class="py-3 px-4 text-center">Pengunjung Unik</th>
                                    <th class="py-3 px-4 text-center">Total Tayangan (Hits)</th>
                                    <th class="py-3 px-4 text-right">Rata-rata Hits/Pengunjung</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-800/80">
                                <?php if (empty($dailyVisitors)): ?>
                                    <tr><td colspan="4" class="py-6 text-center text-slate-500">Belum ada catatan pengunjung.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($dailyVisitors as $v): ?>
                                        <tr class="hover:bg-slate-800/50 transition-colors">
                                            <td class="py-3 px-4 font-semibold text-slate-200">
                                                <?= date('d F Y', strtotime($v['visit_date'])) ?>
                                            </td>
                                            <td class="py-3 px-4 text-center font-bold text-emerald-400">
                                                <?= number_format($v['unique_visitors']) ?>
                                            </td>
                                            <td class="py-3 px-4 text-center font-bold text-sky-400">
                                                <?= number_format($v['total_hits']) ?>
                                            </td>
                                            <td class="py-3 px-4 text-right text-slate-300 font-mono-code">
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
                    <div class="overflow-x-auto">
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
                                                <div class="text-[10px] text-slate-400"><?= esc($pay['user_email'] ?? '-') ?></div>
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
                                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-500/20 text-amber-300 border border-amber-500/30">PENDING</span>
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
                        <a href="<?= base_url('admin/analytics/export-csv') ?>" class="px-3.5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-semibold shadow transition-colors flex items-center gap-2">
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
                            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Total Naskah Diaudit</span>
                            <div class="p-2 rounded-xl bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            </div>
                        </div>
                        <div class="mt-3 flex items-baseline gap-2">
                            <span class="text-3xl font-extrabold text-white tracking-tight"><?= number_format($integrityAnalytics['total_audited']) ?></span>
                            <span class="text-xs text-emerald-400 font-medium">Sertifikat Resmi</span>
                        </div>
                        <div class="mt-2 text-[11px] text-slate-400">
                            Tersebar di skripsi, tesis & artikel jurnal
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
                            Integrasi REST API Publik & Plugin OJS (Open Journal Systems)
                            <span class="px-2 py-0.5 rounded text-[10px] bg-sky-500/20 text-sky-300 border border-sky-500/30">OJS 3.x Ready</span>
                        </h2>
                        <p class="text-xs text-slate-400">Hubungkan jurnal ilmiah kampus ke AR Tilik untuk verifikasi otomatis artikel dan penerbitan sertifikat digital</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <a href="<?= base_url('integrations/ojs/connector') ?>" download="ARTilikOJSConnector.php" class="px-3 py-1.5 rounded-lg bg-sky-600 hover:bg-sky-500 text-white text-xs font-semibold shadow transition-colors flex items-center gap-1.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                            Unduh Konektor OJS (PHP)
                        </a>
                        <a href="<?= base_url('integrations/ojs/Panduan_Integrasi_OJS_ARTilik.pdf') ?>" target="_blank" download="Panduan_Integrasi_OJS_ARTilik.pdf" class="px-3 py-1.5 rounded-lg bg-gradient-to-r from-rose-600 to-rose-700 hover:from-rose-500 hover:to-rose-600 text-white text-xs font-semibold shadow transition-all flex items-center gap-1.5 border border-rose-500/40">
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                            Dokumentasi OJS (PDF)
                        </a>
                        <a href="<?= base_url('integrations/ojs/README.md') ?>" target="_blank" class="px-2.5 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-400 hover:text-slate-200 border border-slate-700 text-xs font-medium transition-colors" title="Buka Dokumentasi Markdown Online">
                            Markdown
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
                    <div class="overflow-x-auto">
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
                <div class="bg-slate-800/40 border border-slate-700/60 rounded-2xl p-5 space-y-3">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-300 border-b border-slate-700/60 pb-2">Contoh Request cURL Integrasi OJS</h3>
                    <pre class="bg-slate-950 p-3 rounded-xl border border-slate-800 text-[11px] font-mono-code text-sky-300 overflow-x-auto">curl -X POST https://tilik.arsetontong.top/api/v1/check \
  -H "X-API-Key: artilik_ojs_YOUR_KEY" \
  -F "document=@/path/to/manuscript.pdf" \
  -F "title=Judul Naskah Ilmiah" \
  -F "author=Nama Penulis" \
  -F "ojs_submission_id=1240"</pre>
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
            if (!confirm('Apakah Anda yakin ingin menghapus dokumen ini beserta semua riwayat kemiripannya? Tindakan ini tidak dapat dibatalkan.')) return;
            try {
                const res = await postJson('<?= base_url('admin/documents/delete') ?>', { document_id: docId });
                alert(res.message);
                window.location.reload();
            } catch (err) {
                alert('Terjadi kesalahan koneksi.');
            }
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
    </script>
</body>
</html>
