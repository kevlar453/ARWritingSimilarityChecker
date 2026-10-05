<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Operational Quality Dashboard - AR Tilik v1.1.0</title>
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
    <nav class="bg-white/95 backdrop-blur-md border-b border-slate-200 sticky top-0 z-40 shadow-xs">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-wrap sm:flex-nowrap justify-between min-h-[4rem] py-2.5 sm:py-3.5 items-center gap-2.5 sm:gap-4">
                <div class="flex items-center gap-3">
                    <a href="<?= base_url('dashboard') ?>" class="flex-shrink-0">
                        <img src="<?= base_url('images/logo.png') ?>" alt="AR Tilik Logo" class="w-11 h-11 object-contain drop-shadow-sm hover:scale-105 transition-transform duration-200">
                    </a>
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="text-xl font-bold bg-gradient-to-r from-indigo-700 to-indigo-500 bg-clip-text text-transparent">AR Tilik</span>
                            <span class="text-xs px-2.5 py-0.5 rounded-full bg-emerald-50 text-emerald-700 font-semibold border border-emerald-200">Quality Dashboard</span>
                            <span class="text-[10px] uppercase font-bold tracking-wider px-2 py-0.5 rounded-full bg-slate-100 text-slate-600 border border-slate-200">
                                v1.1.0 (GA) | Baseline: v1.0.0 FROZEN
                            </span>
                        </div>
                        <p class="text-[11px] text-slate-400">Post-Release Multi-Dimensional Quality & Operational Observability</p>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <a href="<?= base_url('evaluation') ?>" class="text-xs font-semibold text-slate-600 hover:text-slate-900 border border-slate-200 px-3 py-1.5 rounded-lg bg-white hover:bg-slate-50 transition flex items-center gap-1.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                        Antrean Telaah
                    </a>
                    <a href="<?= base_url('evaluation/summary') ?>" class="text-xs font-semibold text-slate-600 hover:text-slate-900 border border-slate-200 px-3 py-1.5 rounded-lg bg-white hover:bg-slate-50 transition flex items-center gap-1.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                        Ringkasan Evaluasi
                    </a>
                    <a href="<?= base_url('dashboard') ?>" class="text-xs font-semibold text-indigo-700 bg-indigo-50 hover:bg-indigo-100 border border-indigo-200 px-3 py-1.5 rounded-lg transition">
                        Dashboard Produksi →
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Container -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

        <!-- Header Card -->
        <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-xs">
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6">
                <div>
                    <div class="flex items-center gap-2 mb-1">
                        <span class="px-2.5 py-0.5 rounded text-xs font-bold bg-indigo-100 text-indigo-800">
                            Fase Operasional R (Phase R)
                        </span>
                        <span class="px-2.5 py-0.5 rounded text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                            Telemetri Nyata (Live Database)
                        </span>
                    </div>
                    <h1 class="text-2xl font-bold text-slate-900">Dasbor Kualitas Operasional (Operational Quality Dashboard)</h1>
                    <p class="text-sm text-slate-500 mt-1 max-w-3xl">
                        Memantau metrik kualitas sistem multi-dimensi pasca-rilis v1.1.0 secara objektif:
                        distribusi bukti tekstual, kepatuhan lapisan konteks sitasi, reliabilitas telaah ahli manusia, serta SLA operasional sistem.
                    </p>
                </div>
                <div class="bg-slate-50 border border-slate-200 rounded-xl p-4 text-xs space-y-1.5 min-w-[260px]">
                    <div class="flex justify-between">
                        <span class="text-slate-500">Versi Rilis:</span>
                        <span class="font-bold font-mono-code text-indigo-700"><?= esc($report['system_version'] ?? 'v1.1.0') ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-500">Engine Baseline:</span>
                        <span class="font-bold font-mono-code text-emerald-700"><?= esc($report['baseline_engine'] ?? 'v1.0.0 (FROZEN)') ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-500">Waktu Audit:</span>
                        <span class="font-mono-code text-slate-600"><?= esc(substr($report['generated_at'] ?? date('c'), 0, 19)) ?>Z</span>
                    </div>
                </div>
            </div>
        </div>

        <?php
        $ev = $report['evidence_metrics'] ?? [];
        $ctx = $report['context_metrics'] ?? [];
        $hum = $report['human_validation_metrics'] ?? [];
        $op = $report['operational_health'] ?? [];
        ?>

        <!-- 4-Quadrant Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

            <!-- QUADRANT 1: EVIDENCE METRICS -->
            <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-xs space-y-5">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-700 flex items-center justify-center font-bold text-sm border border-indigo-100">
                            1
                        </div>
                        <div>
                            <h2 class="text-base font-bold text-slate-900">EVIDENCE METRICS</h2>
                            <p class="text-[11px] text-slate-400">Analisis Distribusi Bukti Fisik & Dekomposisi Bukti</p>
                        </div>
                    </div>
                    <span class="px-2 py-0.5 rounded text-[11px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-100">
                        Lapisan A
                    </span>
                </div>

                <!-- Highlight Cards -->
                <div class="grid grid-cols-2 gap-3">
                    <div class="bg-slate-50 border border-slate-200 rounded-xl p-3">
                        <span class="text-xs text-slate-500 block font-medium">Pasase Terpanjang (Kata)</span>
                        <div class="mt-1 flex items-baseline gap-2">
                            <span class="text-xl font-extrabold text-slate-800"><?= $ev['longest_exact_match_words']['max'] ?? 0 ?></span>
                            <span class="text-xs text-slate-400">rata-rata <?= $ev['longest_exact_match_words']['avg'] ?? 0 ?> kata</span>
                        </div>
                    </div>
                    <div class="bg-amber-50 border border-amber-200 rounded-xl p-3">
                        <span class="text-xs text-amber-800 block font-medium">Insiden Divergensi Bukti</span>
                        <div class="mt-1 flex items-baseline gap-2">
                            <span class="text-xl font-extrabold text-amber-900"><?= $ev['divergence']['count'] ?? 0 ?></span>
                            <span class="text-xs text-amber-700 font-semibold">(<?= $ev['divergence']['rate_percent'] ?? 0 ?>%) [Exact≈0 & Cos>0]</span>
                        </div>
                    </div>
                </div>

                <!-- Exact Distribution -->
                <div>
                    <h3 class="text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Distribusi Exact Similarity (Penyalinan Sekuensial)</h3>
                    <div class="space-y-2">
                        <?php foreach ($ev['exact_similarity_distribution'] ?? [] as $k => $v): ?>
                            <div>
                                <div class="flex justify-between text-xs mb-1">
                                    <span class="text-slate-600 font-mono-code"><?= esc($k) ?></span>
                                    <span class="font-bold text-slate-800"><?= $v['count'] ?> lap (<?= $v['percent'] ?>%)</span>
                                </div>
                                <div class="w-full bg-slate-100 rounded-full h-2 overflow-hidden">
                                    <div class="bg-indigo-600 h-2 rounded-full" style="width: <?= min(100, $v['percent']) ?>%"></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- TF-IDF Distribution -->
                <div>
                    <h3 class="text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Distribusi TF-IDF Cosine Similarity (Kemiripan Topikal)</h3>
                    <div class="space-y-2">
                        <?php foreach ($ev['tfidf_similarity_distribution'] ?? [] as $k => $v): ?>
                            <div>
                                <div class="flex justify-between text-xs mb-1">
                                    <span class="text-slate-600 font-mono-code"><?= esc($k) ?></span>
                                    <span class="font-bold text-slate-800"><?= $v['count'] ?> lap (<?= $v['percent'] ?>%)</span>
                                </div>
                                <div class="w-full bg-slate-100 rounded-full h-2 overflow-hidden">
                                    <div class="bg-emerald-600 h-2 rounded-full" style="width: <?= min(100, $v['percent']) ?>%"></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Evidence Decomposition Summary -->
                <div class="bg-slate-50 border border-slate-200 rounded-xl p-3 flex justify-between items-center text-xs">
                    <div>
                        <span class="font-semibold text-slate-700 block">Direct Textual Overlap</span>
                        <span class="text-slate-400"><?= $ev['evidence_type_presence']['direct_textual_overlap']['count'] ?? 0 ?> laporan (<?= $ev['evidence_type_presence']['direct_textual_overlap']['percent'] ?? 0 ?>%)</span>
                    </div>
                    <div class="text-right">
                        <span class="font-semibold text-slate-700 block">Lexical Topic Similarity</span>
                        <span class="text-slate-400"><?= $ev['evidence_type_presence']['lexical_topic_similarity']['count'] ?? 0 ?> laporan (<?= $ev['evidence_type_presence']['lexical_topic_similarity']['percent'] ?? 0 ?>%)</span>
                    </div>
                </div>
            </div>

            <!-- QUADRANT 2: CONTEXT & CITATION METRICS -->
            <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-xs space-y-5">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-700 flex items-center justify-center font-bold text-sm border border-emerald-100">
                            2
                        </div>
                        <div>
                            <h2 class="text-base font-bold text-slate-900">CITATION & CONTEXT METRICS</h2>
                            <p class="text-[11px] text-slate-400">Lapisan Konteks Akademik & Non-Suppression Invariant</p>
                        </div>
                    </div>
                    <span class="px-2 py-0.5 rounded text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-100">
                        Lapisan B
                    </span>
                </div>

                <!-- Highlight Cards -->
                <div class="grid grid-cols-2 gap-3">
                    <div class="bg-emerald-50 border border-emerald-200 rounded-xl p-3">
                        <span class="text-xs text-emerald-800 block font-medium">Tingkat Konteks UNKNOWN</span>
                        <div class="mt-1 flex items-baseline gap-2">
                            <span class="text-xl font-extrabold text-emerald-900"><?= $ctx['unknown_rate_percent'] ?? 0 ?>%</span>
                            <span class="text-xs text-emerald-700 font-semibold">Toleransi 0 Asumsi</span>
                        </div>
                    </div>
                    <div class="bg-slate-50 border border-slate-200 rounded-xl p-3">
                        <span class="text-xs text-slate-500 block font-medium">Kegagalan Deteksi Parsing</span>
                        <div class="mt-1 flex items-baseline gap-2">
                            <span class="text-xl font-extrabold text-slate-800"><?= $ctx['context_detection_failure_rate_percent'] ?? 0 ?>%</span>
                            <span class="text-xs text-slate-400 font-medium">Deterministic Guard</span>
                        </div>
                    </div>
                </div>

                <!-- Context Distribution -->
                <div>
                    <h3 class="text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Distribusi Konteks Sitasi Terdeteksi</h3>
                    <div class="grid grid-cols-2 gap-2 text-xs">
                        <?php foreach ($ctx['context_distribution'] ?? [] as $k => $v): ?>
                            <?php if ($v['count'] > 0): ?>
                                <div class="p-2.5 rounded-lg border border-slate-200 bg-slate-50/50 flex justify-between items-center">
                                    <span class="font-mono-code text-slate-700 text-[11px]"><?= esc($k) ?></span>
                                    <span class="font-bold text-slate-900"><?= $v['count'] ?> <span class="text-slate-400 font-normal">(<?= $v['percent'] ?>%)</span></span>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Attribution Status -->
                <div>
                    <h3 class="text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Distribusi Status Atribusi Sitasi</h3>
                    <div class="grid grid-cols-2 gap-2 text-xs">
                        <?php foreach ($ctx['status_distribution'] ?? [] as $k => $v): ?>
                            <?php if ($v['count'] > 0): ?>
                                <div class="p-2.5 rounded-lg border border-slate-200 bg-slate-50/50 flex justify-between items-center">
                                    <span class="font-mono-code text-slate-700 text-[11px]"><?= esc($k) ?></span>
                                    <span class="font-bold text-slate-900"><?= $v['count'] ?> <span class="text-slate-400 font-normal">(<?= $v['percent'] ?>%)</span></span>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="bg-emerald-50/60 border border-emerald-200 rounded-xl p-3 text-xs text-emerald-900 flex items-start gap-2">
                    <svg class="w-4 h-4 text-emerald-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span><strong>Jaminan Non-Suppression:</strong> Deteksi kutipan dan sitasi memperkaya kartu telaah tanpa menurunkan skor Exact atau mengubah ambang batas v1.0.0.</span>
                </div>
            </div>

            <!-- QUADRANT 3: HUMAN VALIDATION METRICS -->
            <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-xs space-y-5">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-lg bg-purple-50 text-purple-700 flex items-center justify-center font-bold text-sm border border-purple-100">
                            3
                        </div>
                        <div>
                            <h2 class="text-base font-bold text-slate-900">HUMAN VALIDATION METRICS</h2>
                            <p class="text-[11px] text-slate-400">Ground Truth Penelaah Ahli & Kesepakatan Akademik</p>
                        </div>
                    </div>
                    <span class="px-2 py-0.5 rounded text-[11px] font-bold bg-purple-50 text-purple-700 border border-purple-100">
                        Lapisan C
                    </span>
                </div>

                <!-- Highlight Cards -->
                <div class="grid grid-cols-2 gap-3">
                    <div class="bg-purple-50 border border-purple-200 rounded-xl p-3">
                        <span class="text-xs text-purple-800 block font-medium">Reviewer Agreement Rate</span>
                        <div class="mt-1 flex items-baseline gap-2">
                            <span class="text-xl font-extrabold text-purple-900"><?= $hum['reviewer_agreement_rate_percent'] ?? 0 ?>%</span>
                            <span class="text-xs text-purple-700 font-semibold">(CORRECT + ACCEPTABLE)</span>
                        </div>
                    </div>
                    <div class="bg-slate-50 border border-slate-200 rounded-xl p-3">
                        <span class="text-xs text-slate-500 block font-medium">Total Kasus Ditelaah</span>
                        <div class="mt-1 flex items-baseline gap-2">
                            <span class="text-xl font-extrabold text-slate-800"><?= $hum['total_cases_reviewed'] ?? 0 ?></span>
                            <span class="text-xs text-slate-400 font-medium">label di database</span>
                        </div>
                    </div>
                </div>

                <!-- Assessment Distribution -->
                <div>
                    <h3 class="text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Distribusi Penilaian Sistem oleh Ahli</h3>
                    <div class="grid grid-cols-3 gap-2 text-xs">
                        <?php foreach ($hum['system_assessment_distribution'] ?? [] as $k => $v): ?>
                            <div class="p-2.5 rounded-lg border border-slate-200 bg-slate-50/50 text-center">
                                <span class="font-mono-code font-bold text-slate-800 text-[11px] block"><?= esc($k) ?></span>
                                <span class="text-base font-extrabold text-indigo-700"><?= $v['count'] ?></span>
                                <span class="text-[10px] text-slate-400 block">(<?= $v['percent'] ?>%)</span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Provenance & Suite Breakdown -->
                <div>
                    <h3 class="text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Distribusi Provenance & Suite Evaluasi</h3>
                    <div class="space-y-1.5 text-xs">
                        <?php foreach ($hum['suite_provenance_breakdown'] ?? [] as $sName => $sData): ?>
                            <div class="p-2 rounded-lg border border-slate-200 bg-slate-50/70 flex justify-between items-center">
                                <div>
                                    <span class="font-bold text-slate-800 font-mono-code"><?= esc($sName) ?></span>
                                    <span class="text-[10px] text-slate-400 block">[<?= esc($sData['provenance']) ?>]</span>
                                </div>
                                <div class="text-right">
                                    <span class="font-bold text-indigo-700"><?= $sData['label_count'] ?> label</span>
                                    <span class="text-[10px] text-emerald-700 block font-semibold">Agreement: <?= $sData['reviewer_agreement_rate_percent'] ?>%</span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Layer Utility (Milestone R3) -->
                <div>
                    <h3 class="text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Kegunaan Pemisahan Bukti (Layer Utility — Milestone R3)</h3>
                    <div class="grid grid-cols-3 gap-2 text-xs text-center">
                        <?php
                        $uData = $hum['layer_utility_metrics'] ?? [];
                        foreach (['YES' => 'bg-emerald-50 text-emerald-800 border-emerald-200', 'PARTIALLY' => 'bg-amber-50 text-amber-800 border-amber-200', 'NO' => 'bg-rose-50 text-rose-800 border-rose-200'] as $uKey => $uClass):
                            $cnt = $uData[$uKey]['count'] ?? 0;
                            $pct = $uData[$uKey]['percent'] ?? 0;
                        ?>
                            <div class="p-2 rounded-lg border <?= $uClass ?>">
                                <span class="font-bold block text-[11px]"><?= $uKey ?></span>
                                <span class="text-base font-extrabold"><?= $cnt ?></span>
                                <span class="text-[10px] block opacity-80">(<?= $pct ?>%)</span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Academic Interpretation Taxonomy -->
                <div>
                    <h3 class="text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Taksonomi Interpretasi Akademik</h3>
                    <div class="space-y-1.5 text-xs">
                        <?php foreach ($hum['academic_interpretation_distribution'] ?? [] as $k => $v): ?>
                            <?php if ($v['count'] > 0): ?>
                                <div class="flex justify-between items-center py-1 border-b border-slate-100 last:border-0">
                                    <span class="font-mono-code text-slate-600 text-[11px]"><?= esc($k) ?></span>
                                    <span class="font-bold text-slate-800"><?= $v['count'] ?> <span class="text-slate-400 font-normal">(<?= $v['percent'] ?>%)</span></span>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="bg-purple-50/50 border border-purple-200 rounded-xl p-3 text-xs text-purple-900 flex items-start gap-2">
                    <svg class="w-4 h-4 text-purple-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span><strong>Prinsip Blind Review:</strong> Penelaah manusia tidak melihat label sintetis expected dalam antrean untuk menjamin integritas ilmiah evaluasi.</span>
                </div>
            </div>

            <!-- QUADRANT 4: OPERATIONAL HEALTH & PIPELINE -->
            <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-xs space-y-5">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-lg bg-teal-50 text-teal-700 flex items-center justify-center font-bold text-sm border border-teal-100">
                            4
                        </div>
                        <div>
                            <h2 class="text-base font-bold text-slate-900">OPERATIONAL HEALTH & PIPELINE</h2>
                            <p class="text-[11px] text-slate-400">Kesehatan Antrean, Latensi & SLA Overhead Sitasi</p>
                        </div>
                    </div>
                    <span class="px-2 py-0.5 rounded text-[11px] font-bold bg-teal-50 text-teal-700 border border-teal-100">
                        Reliabilitas
                    </span>
                </div>

                <!-- Highlight Cards -->
                <div class="grid grid-cols-2 gap-3">
                    <div class="bg-teal-50 border border-teal-200 rounded-xl p-3">
                        <span class="text-xs text-teal-800 block font-medium">Tingkat Keberhasilan Laporan</span>
                        <div class="mt-1 flex items-baseline gap-2">
                            <span class="text-xl font-extrabold text-teal-900"><?= $op['reports']['success_rate_percent'] ?? 100 ?>%</span>
                            <span class="text-xs text-teal-700 font-semibold">(<?= $op['reports']['completed'] ?? 0 ?> sukses, <?= $op['reports']['failed'] ?? 0 ?> gagal)</span>
                        </div>
                    </div>
                    <div class="bg-slate-50 border border-slate-200 rounded-xl p-3">
                        <span class="text-xs text-slate-500 block font-medium">Status Worker Daemon</span>
                        <div class="mt-1 flex items-baseline gap-2">
                            <span class="text-base font-extrabold text-emerald-700"><?= esc($op['worker_status'] ?? 'IDLE') ?></span>
                        </div>
                    </div>
                </div>

                <!-- Latency SLA Compliance -->
                <div class="bg-slate-50 border border-slate-200 rounded-xl p-4 space-y-3 text-xs">
                    <div class="flex justify-between items-center">
                        <span class="font-bold text-slate-700">Target SLA Overhead Sitasi:</span>
                        <span class="px-2.5 py-0.5 rounded text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                            &lt; 150 ms [<?= esc($op['sla_overhead_status'] ?? 'PASS') ?>]
                        </span>
                    </div>

                    <?php $cp = $op['citation_parsing_latency'] ?? []; ?>
                    <div class="grid grid-cols-3 gap-2 text-center bg-white p-2.5 rounded-lg border border-slate-200">
                        <div>
                            <span class="text-slate-400 block text-[10px]">Sampel (n)</span>
                            <span class="font-bold font-mono-code text-slate-800"><?= $cp['count'] ?? 0 ?></span>
                        </div>
                        <div>
                            <span class="text-slate-400 block text-[10px]">Rata-rata</span>
                            <span class="font-bold font-mono-code text-slate-800"><?= number_format($cp['avg_ms'] ?? 0, 4) ?> ms</span>
                        </div>
                        <div>
                            <span class="text-slate-400 block text-[10px]">p95</span>
                            <span class="font-bold font-mono-code text-indigo-700"><?= number_format($cp['p95_ms'] ?? 0, 4) ?> ms</span>
                        </div>
                    </div>

                    <?php $tp = $op['pipeline_latency'] ?? []; ?>
                    <?php if (($tp['count'] ?? 0) > 0): ?>
                        <div class="flex justify-between items-center text-slate-500 pt-1">
                            <span>Latensi Pipeline Total:</span>
                            <span class="font-mono-code font-bold text-slate-800">Avg: <?= number_format($tp['avg_ms'] ?? 0, 2) ?> ms | p95: <?= number_format($tp['p95_ms'] ?? 0, 2) ?> ms (n=<?= $tp['count'] ?>)</span>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Queue Backlog -->
                <div>
                    <h3 class="text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Status Antrean Jobs</h3>
                    <div class="grid grid-cols-4 gap-2 text-xs text-center">
                        <div class="p-2 rounded-lg bg-amber-50 border border-amber-200">
                            <span class="text-amber-800 text-[10px] block">Pending</span>
                            <span class="font-extrabold text-amber-900 text-sm"><?= $op['queue_backlog']['pending'] ?? 0 ?></span>
                        </div>
                        <div class="p-2 rounded-lg bg-indigo-50 border border-indigo-200">
                            <span class="text-indigo-800 text-[10px] block">Processing</span>
                            <span class="font-extrabold text-indigo-900 text-sm"><?= $op['queue_backlog']['processing'] ?? 0 ?></span>
                        </div>
                        <div class="p-2 rounded-lg bg-emerald-50 border border-emerald-200">
                            <span class="text-emerald-800 text-[10px] block">Completed</span>
                            <span class="font-extrabold text-emerald-900 text-sm"><?= $op['queue_backlog']['completed'] ?? 0 ?></span>
                        </div>
                        <div class="p-2 rounded-lg bg-rose-50 border border-rose-200">
                            <span class="text-rose-800 text-[10px] block">Failed</span>
                            <span class="font-extrabold text-rose-900 text-sm"><?= $op['queue_backlog']['failed'] ?? 0 ?></span>
                        </div>
                    </div>
                </div>
            </div>

        </div>

    </main>

</body>
</html>
