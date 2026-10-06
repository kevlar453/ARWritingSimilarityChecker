<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal Administrator - AR Tilik Academic Writing Similarity Checker</title>
    <!-- Favicon & Brand Icons -->
    <link rel="icon" type="image/x-icon" href="<?= base_url('favicon.ico?v=20261006') ?>">
    <link rel="icon" type="image/png" sizes="32x32" href="<?= base_url('favicon-32x32.png?v=20261006') ?>">
    <link rel="icon" type="image/png" sizes="16x16" href="<?= base_url('favicon-16x16.png?v=20261006') ?>">
    <link rel="apple-touch-icon" sizes="180x180" href="<?= base_url('apple-touch-icon.png?v=20261006') ?>">
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .font-mono-code { font-family: 'JetBrains Mono', monospace; }
        /* Sleek horizontal tab scroll styling */
        .admin-tab-scroll {
            scrollbar-width: thin;
            scrollbar-color: rgba(99, 102, 241, 0.4) transparent;
        }
        .admin-tab-scroll::-webkit-scrollbar {
            height: 3px;
        }
        .admin-tab-scroll::-webkit-scrollbar-track {
            background: transparent;
        }
        .admin-tab-scroll::-webkit-scrollbar-thumb {
            background: rgba(99, 102, 241, 0.4);
            border-radius: 9999px;
        }
        .admin-tab-scroll::-webkit-scrollbar-thumb:hover {
            background: rgba(99, 102, 241, 0.8);
        }
    </style>
</head>
<body class="bg-slate-900 text-slate-100 antialiased min-h-screen flex flex-col">

    <!-- Top Admin Header -->
    <header class="bg-slate-950/90 border-b border-slate-800 sticky top-0 z-40 backdrop-blur-md">
        <div class="max-w-[1600px] w-full mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16 gap-4">
                <!-- Brand & Admin Badge -->
                <div class="flex items-center gap-3">
                    <a href="<?= base_url('admin') ?>" class="flex items-center gap-2.5 group">
                        <img src="<?= base_url('images/logo.png') ?>" alt="AR Tilik" class="w-9 h-9 object-contain drop-shadow group-hover:scale-105 transition-transform">
                        <span class="text-lg font-bold bg-gradient-to-r from-indigo-400 via-sky-300 to-emerald-400 bg-clip-text text-transparent">
                            AR Tilik
                        </span>
                    </a>
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 flex items-center gap-1.5">
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

            <!-- Tab Navigation Bar -->
            <div class="flex items-center gap-1.5 overflow-x-auto admin-tab-scroll py-2 border-t border-slate-800/80 text-xs font-medium scroll-smooth">
                <a href="<?= base_url('admin?tab=overview') ?>" class="flex-shrink-0 px-3 py-1.5 rounded-lg flex items-center gap-2 whitespace-nowrap transition-colors <?= $activeTab === 'overview' ? 'bg-indigo-600/20 text-indigo-300 font-semibold border border-indigo-500/30' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/50' ?>">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg>
                    Ringkasan
                </a>
                <a href="<?= base_url('admin?tab=users') ?>" class="flex-shrink-0 px-3 py-1.5 rounded-lg flex items-center gap-2 whitespace-nowrap transition-colors <?= $activeTab === 'users' ? 'bg-indigo-600/20 text-indigo-300 font-semibold border border-indigo-500/30' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/50' ?>">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                    Pengguna & Kuota
                    <span class="px-1.5 py-0.2 rounded-full text-[10px] bg-slate-800 text-slate-300 font-semibold"><?= (int) $metrics['total_users'] ?></span>
                </a>
                <a href="<?= base_url('admin?tab=documents') ?>" class="flex-shrink-0 px-3 py-1.5 rounded-lg flex items-center gap-2 whitespace-nowrap transition-colors <?= $activeTab === 'documents' ? 'bg-indigo-600/20 text-indigo-300 font-semibold border border-indigo-500/30' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/50' ?>">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    Dokumen & Antrean
                    <?php if ($metrics['queue_uploaded'] + $metrics['queue_processing'] > 0): ?>
                        <span class="px-1.5 py-0.2 rounded-full text-[10px] bg-amber-500/20 text-amber-300 border border-amber-500/30 font-semibold animate-pulse"><?= $metrics['queue_uploaded'] + $metrics['queue_processing'] ?> proses</span>
                    <?php endif; ?>
                </a>
                <a href="<?= base_url('admin?tab=corpus') ?>" class="flex-shrink-0 px-3 py-1.5 rounded-lg flex items-center gap-2 whitespace-nowrap transition-colors <?= $activeTab === 'corpus' ? 'bg-indigo-600/20 text-indigo-300 font-semibold border border-indigo-500/30' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/50' ?>">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
                    Korpus Repositori
                    <span class="px-1.5 py-0.2 rounded-full text-[10px] bg-slate-800 text-slate-300 font-semibold"><?= (int) $metrics['total_corpus'] ?></span>
                </a>
                <a href="<?= base_url('admin?tab=security') ?>" class="flex-shrink-0 px-3 py-1.5 rounded-lg flex items-center gap-2 whitespace-nowrap transition-colors <?= $activeTab === 'security' ? 'bg-indigo-600/20 text-indigo-300 font-semibold border border-indigo-500/30' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/50' ?>">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                    Keamanan & Limit
                    <?php if ($metrics['active_locks_count'] > 0): ?>
                        <span class="px-1.5 py-0.2 rounded-full text-[10px] bg-rose-500/20 text-rose-300 border border-rose-500/30 font-semibold"><?= $metrics['active_locks_count'] ?> terkunci</span>
                    <?php endif; ?>
                </a>
                <a href="<?= base_url('admin?tab=visitors') ?>" class="flex-shrink-0 px-3 py-1.5 rounded-lg flex items-center gap-2 whitespace-nowrap transition-colors <?= $activeTab === 'visitors' ? 'bg-indigo-600/20 text-indigo-300 font-semibold border border-indigo-500/30' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/50' ?>">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                    Pengunjung
                </a>
                <a href="<?= base_url('admin?tab=system') ?>" class="flex-shrink-0 px-3 py-1.5 rounded-lg flex items-center gap-2 whitespace-nowrap transition-colors <?= $activeTab === 'system' ? 'bg-indigo-600/20 text-indigo-300 font-semibold border border-indigo-500/30' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/50' ?>">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                    Sistem Server
                </a>
            </div>
        </div>
    </header>

    <!-- Main Content Area -->
    <main class="flex-1 max-w-[1600px] w-full mx-auto px-4 sm:px-6 lg:px-8 py-6">

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

    <!-- Interactive JavaScript Actions -->
    <script>
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
    </script>
</body>
</html>
