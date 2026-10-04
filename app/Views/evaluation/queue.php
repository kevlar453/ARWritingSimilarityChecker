<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Human Review Queue - AR Tilik v1.0.0</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .font-mono-code { font-family: 'JetBrains Mono', monospace; }
    </style>
</head>
<body class="bg-slate-100 text-slate-800 antialiased min-h-screen">

    <!-- Top Navigation -->
    <nav class="bg-white border-b border-slate-200 sticky top-0 z-30 shadow-xs">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16 items-center">
                <div class="flex items-center gap-3">
                    <a href="<?= base_url('dashboard') ?>" class="w-10 h-10 rounded-xl bg-indigo-600 text-white flex items-center justify-center font-bold text-xl shadow-md shadow-indigo-100 hover:bg-indigo-700 transition">
                        AR
                    </a>
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="text-xl font-bold bg-gradient-to-r from-indigo-700 to-indigo-500 bg-clip-text text-transparent">AR Tilik</span>
                            <span class="text-xs px-2.5 py-0.5 rounded-full bg-amber-50 text-amber-700 font-semibold border border-amber-200">Human Review Module</span>
                            <span class="text-[10px] uppercase font-bold tracking-wider px-2 py-0.5 rounded-full bg-slate-100 text-slate-600 border border-slate-200">
                                Engine: v1.0.0 FROZEN
                            </span>
                        </div>
                        <p class="text-[11px] text-slate-400">Post-Release Academic Evidence & Human Validation Layer</p>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <a href="<?= base_url('evaluation/summary') ?>" class="text-xs font-semibold text-indigo-700 bg-indigo-50 hover:bg-indigo-100 border border-indigo-200 px-3.5 py-1.5 rounded-lg transition flex items-center gap-1.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                        Ringkasan Evaluasi
                    </a>
                    <a href="<?= base_url('dashboard') ?>" class="text-xs font-semibold text-slate-600 hover:text-slate-900 border border-slate-200 px-3 py-1.5 rounded-lg bg-white hover:bg-slate-50 transition">
                        Dashboard Produksi →
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Container -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

        <!-- Flash Message -->
        <?php if (session()->getFlashdata('success')): ?>
            <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-xl text-sm flex items-center gap-2">
                <svg class="w-5 h-5 text-emerald-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                <?= esc(session()->getFlashdata('success')) ?>
            </div>
        <?php endif; ?>

        <!-- Header Card & Overview -->
        <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-xs">
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900">Antrean Telaah Manusia (Prioritized Human Review)</h1>
                    <p class="text-sm text-slate-500 mt-1 max-w-3xl">
                        Mengevaluasi kesesuaian akademik bukti tekstual yang dihasilkan oleh engine v1.0.0 pada 30 kasus prioritas terkurasi:
                        kasus tepi (*edge cases*), pasase bersitasi, jargon keilmuan, batas threshold, parafrase semantik, dan kontrol dasar.
                    </p>
                    <div class="flex items-center gap-2 mt-3 text-xs text-slate-500">
                        <span class="inline-flex items-center gap-1 font-semibold text-indigo-700 bg-indigo-50 px-2 py-0.5 rounded-md border border-indigo-100">
                            HR-UI Gate 4: Blind Review
                        </span>
                        <span>• Label sintetik expected disembunyikan untuk menjaga objektivitas telaah manusia.</span>
                    </div>
                </div>

                <!-- Stats Cards -->
                <div class="grid grid-cols-3 gap-3 w-full lg:w-auto">
                    <div class="bg-slate-50 border border-slate-200 rounded-xl p-3.5 text-center min-w-[100px]">
                        <span class="text-xs text-slate-500 font-medium block">Total Kasus</span>
                        <span class="text-2xl font-extrabold text-slate-800"><?= $total ?></span>
                    </div>
                    <div class="bg-emerald-50 border border-emerald-200 rounded-xl p-3.5 text-center min-w-[100px]">
                        <span class="text-xs text-emerald-700 font-medium block">Selesai</span>
                        <span class="text-2xl font-extrabold text-emerald-700"><?= $reviewed ?></span>
                    </div>
                    <div class="bg-amber-50 border border-amber-200 rounded-xl p-3.5 text-center min-w-[100px]">
                        <span class="text-xs text-amber-700 font-medium block">Menunggu</span>
                        <span class="text-2xl font-extrabold text-amber-700"><?= $pending ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter Bar -->
        <div class="bg-white border border-slate-200 rounded-xl p-4 shadow-xs flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center gap-2">
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Status:</span>
                <div class="inline-flex rounded-lg border border-slate-200 p-0.5 bg-slate-50 text-xs">
                    <a href="?status=all&pattern=<?= esc($patternFilter) ?>&priority=<?= esc($priorityFilter) ?>" class="px-3 py-1 rounded-md font-medium transition <?= $statusFilter === 'all' ? 'bg-white text-indigo-700 shadow-xs' : 'text-slate-600 hover:text-slate-900' ?>">Semua (<?= $total ?>)</a>
                    <a href="?status=pending&pattern=<?= esc($patternFilter) ?>&priority=<?= esc($priorityFilter) ?>" class="px-3 py-1 rounded-md font-medium transition <?= $statusFilter === 'pending' ? 'bg-white text-amber-700 shadow-xs' : 'text-slate-600 hover:text-slate-900' ?>">Menunggu (<?= $pending ?>)</a>
                    <a href="?status=reviewed&pattern=<?= esc($patternFilter) ?>&priority=<?= esc($priorityFilter) ?>" class="px-3 py-1 rounded-md font-medium transition <?= $statusFilter === 'reviewed' ? 'bg-white text-emerald-700 shadow-xs' : 'text-slate-600 hover:text-slate-900' ?>">Selesai (<?= $reviewed ?>)</a>
                </div>
            </div>

            <form method="GET" action="<?= base_url('evaluation') ?>" class="flex flex-wrap items-center gap-3">
                <input type="hidden" name="status" value="<?= esc($statusFilter) ?>">

                <div class="flex items-center gap-1.5 text-xs">
                    <label class="text-slate-500 font-medium">Pola:</label>
                    <select name="pattern" onchange="this.form.submit()" class="border border-slate-200 rounded-lg px-2.5 py-1 text-slate-700 bg-white text-xs focus:ring-1 focus:ring-indigo-500">
                        <option value="all">Semua Pola</option>
                        <?php foreach ($patterns as $p): ?>
                            <option value="<?= esc($p) ?>" <?= $patternFilter === $p ? 'selected' : '' ?>><?= esc($p) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="flex items-center gap-1.5 text-xs">
                    <label class="text-slate-500 font-medium">Tier Prioritas:</label>
                    <select name="priority" onchange="this.form.submit()" class="border border-slate-200 rounded-lg px-2.5 py-1 text-slate-700 bg-white text-xs focus:ring-1 focus:ring-indigo-500">
                        <option value="all">Semua Tier</option>
                        <?php foreach ($priorities as $pr): ?>
                            <option value="<?= esc($pr) ?>" <?= $priorityFilter === $pr ? 'selected' : '' ?>><?= esc($pr) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <?php if ($patternFilter !== 'all' || $priorityFilter !== 'all' || $statusFilter !== 'all'): ?>
                    <a href="<?= base_url('evaluation') ?>" class="text-xs text-rose-600 hover:underline">Reset Filter</a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Cases Table -->
        <div class="bg-white border border-slate-200 rounded-2xl shadow-xs overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-slate-50 border-b border-slate-200 text-xs font-semibold text-slate-600 uppercase tracking-wider">
                        <tr>
                            <th class="py-3.5 px-4 w-12 text-center">#</th>
                            <th class="py-3.5 px-4">Kasus Evaluasi</th>
                            <th class="py-3.5 px-4">Tier & Alasan Prioritas</th>
                            <th class="py-3.5 px-4">Pola Bukti Mesin</th>
                            <th class="py-3.5 px-4 text-center">Exact %</th>
                            <th class="py-3.5 px-4 text-center">TF-IDF %</th>
                            <th class="py-3.5 px-4 text-center">Keputusan</th>
                            <th class="py-3.5 px-4 text-center">Status</th>
                            <th class="py-3.5 px-4 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php if (empty($cases)): ?>
                            <tr>
                                <td colspan="9" class="py-12 text-center text-slate-400">
                                    Tidak ada kasus yang cocok dengan kriteria filter saat ini.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($cases as $c): ?>
                                <tr class="hover:bg-slate-50/70 transition">
                                    <td class="py-3.5 px-4 text-center text-xs font-bold text-slate-400">
                                        <?= $c['queue_rank'] ?>
                                    </td>
                                    <td class="py-3.5 px-4">
                                        <div class="font-mono-code font-bold text-indigo-700 text-sm">
                                            <?= esc($c['case_id']) ?>
                                        </div>
                                    </td>
                                    <td class="py-3.5 px-4">
                                        <span class="inline-block px-2 py-0.5 rounded text-[11px] font-semibold bg-slate-100 text-slate-700 border border-slate-200 mb-0.5">
                                            <?= esc($c['priority_tier']) ?>
                                        </span>
                                        <p class="text-xs text-slate-500 line-clamp-1"><?= esc($c['priority_reason']) ?></p>
                                    </td>
                                    <td class="py-3.5 px-4">
                                        <span class="text-xs font-mono-code bg-slate-50 px-2 py-0.5 rounded border border-slate-200">
                                            <?= esc($c['predicted_pattern']) ?>
                                        </span>
                                    </td>
                                    <td class="py-3.5 px-4 text-center font-mono-code text-xs <?= $c['exact_similarity'] > 0 ? 'font-bold text-slate-900' : 'text-slate-400' ?>">
                                        <?= number_format($c['exact_similarity'], 1) ?>%
                                    </td>
                                    <td class="py-3.5 px-4 text-center font-mono-code text-xs <?= $c['cosine_similarity'] > 0 ? 'font-bold text-slate-900' : 'text-slate-400' ?>">
                                        <?= number_format($c['cosine_similarity'], 1) ?>%
                                    </td>
                                    <td class="py-3.5 px-4 text-center">
                                        <?php
                                        $pClass = strtoupper($c['predicted_class']);
                                        $badgeColor = match($pClass) {
                                            'MATCH' => 'bg-rose-50 text-rose-700 border-rose-200',
                                            'REVIEW' => 'bg-amber-50 text-amber-700 border-amber-200',
                                            default => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                        };
                                        ?>
                                        <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold border <?= $badgeColor ?>">
                                            <?= $pClass ?>
                                        </span>
                                    </td>
                                    <td class="py-3.5 px-4 text-center">
                                        <?php if ($c['review_status'] === 'REVIEWED' || $c['review_status'] === 'VALIDATED'): ?>
                                            <span class="inline-flex items-center gap-1 text-[11px] font-semibold text-emerald-700 bg-emerald-50 px-2.5 py-0.5 rounded-full border border-emerald-200">
                                                <svg class="w-3 h-3 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                                Selesai
                                            </span>
                                        <?php else: ?>
                                            <span class="inline-flex items-center text-[11px] font-semibold text-amber-700 bg-amber-50 px-2.5 py-0.5 rounded-full border border-amber-200">
                                                Menunggu
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-3.5 px-4 text-right">
                                        <a href="<?= base_url('evaluation/' . $c['case_id']) ?>" class="inline-flex items-center gap-1 text-xs font-semibold px-3 py-1.5 rounded-lg bg-indigo-50 text-indigo-700 hover:bg-indigo-600 hover:text-white border border-indigo-200 transition">
                                            <?= $c['review_status'] === 'REVIEWED' ? 'Edit Telaah' : 'Buka Telaah' ?> →
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </main>

</body>
</html>
