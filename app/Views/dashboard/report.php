<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Bukti Similarity #<?= $report['id'] ?> - AR Tilik</title>
    <!-- Favicon & Brand Icons -->
    <link rel="icon" type="image/x-icon" href="<?= base_url('favicon.ico?v=20261006') ?>">
    <link rel="icon" type="image/png" sizes="32x32" href="<?= base_url('favicon-32x32.png?v=20261006') ?>">
    <link rel="icon" type="image/png" sizes="16x16" href="<?= base_url('favicon-16x16.png?v=20261006') ?>">
    <link rel="apple-touch-icon" sizes="180x180" href="<?= base_url('apple-touch-icon.png?v=20261006') ?>">
    <link rel="shortcut icon" type="image/x-icon" href="<?= base_url('favicon.ico?v=20261006') ?>">
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
    <!-- Top Loading Progress Bar -->
    <div id="topProgressBar" class="fixed top-0 left-0 h-1 bg-gradient-to-r from-indigo-500 via-purple-500 to-indigo-600 z-50 w-0 transition-all duration-300 ease-out pointer-events-none"></div>

    <!-- Page Transition Spinner Overlay -->
    <div id="pageTransitionOverlay" class="fixed inset-0 bg-slate-900/25 backdrop-blur-[2px] z-50 flex items-center justify-center opacity-0 pointer-events-none transition-opacity duration-200">
        <div class="bg-white rounded-2xl px-6 py-4 shadow-2xl border border-slate-100 flex items-center gap-3.5">
            <svg class="animate-spin h-5 w-5 text-indigo-600" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span class="text-sm font-semibold text-slate-700">Memuat halaman...</span>
        </div>
    </div>

    <!-- Navbar -->
    <nav class="bg-white/95 backdrop-blur-md border-b border-slate-200 sticky top-0 z-40 shadow-xs">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-wrap sm:flex-nowrap justify-between min-h-[4rem] py-2.5 sm:py-3.5 items-center gap-2.5 sm:gap-4">
                <div class="flex items-center gap-2.5 sm:gap-3.5 min-w-0">
                    <a href="<?= base_url('dashboard') ?>" class="flex-shrink-0 group">
                        <img src="<?= base_url('images/logo.png') ?>" alt="AR Tilik Logo" class="w-10 h-10 sm:w-11 sm:h-11 object-contain drop-shadow-sm group-hover:scale-105 transition-transform duration-200">
                    </a>
                    <div class="min-w-0">
                        <div class="flex items-center gap-1.5 sm:gap-2 flex-wrap">
                            <a href="<?= base_url('dashboard') ?>" class="text-lg sm:text-xl font-bold bg-gradient-to-r from-indigo-700 to-indigo-500 bg-clip-text text-transparent hover:opacity-90">AR Tilik</a>
                            <span class="text-[10px] sm:text-xs px-2 py-0.5 rounded-full bg-indigo-50 text-indigo-700 font-semibold border border-indigo-100 whitespace-nowrap">Laporan #<?= $report['id'] ?></span>
                            <span class="text-[9px] sm:text-[10px] uppercase font-bold tracking-wider px-2 py-0.5 rounded-full bg-slate-100 text-slate-600 border border-slate-200 whitespace-nowrap">
                                Engine: <?= strtoupper(esc($report['engine'] ?? 'hybrid')) ?>
                            </span>
                        </div>
                        <p class="hidden sm:block text-[11px] text-slate-400 truncate">Academic Writing Similarity & Evidence Checker</p>
                    </div>
                </div>
                <div class="flex items-center gap-2 flex-shrink-0">
                    <?php if (($report['status'] ?? '') === 'completed'): ?>
                        <?php if (($document['document_type'] ?? '') === 'corpus'): ?>
                            <span class="text-xs font-semibold text-emerald-700 bg-emerald-50 border border-emerald-200 flex items-center gap-1.5 px-3 py-1.5 rounded-lg shadow-xs">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                <span>Korpus Institusi</span>
                            </span>
                        <?php else: ?>
                            <button id="btnEnrollCorpus" onclick="enrollDocumentAsCorpus(<?= $document['id'] ?>)" class="text-xs font-semibold text-emerald-700 bg-emerald-50 hover:bg-emerald-100 active:bg-emerald-200 border border-emerald-300 flex items-center gap-1.5 px-3 py-1.5 rounded-lg shadow-xs transition cursor-pointer">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                </svg>
                                <span>Daftarkan ke Korpus</span>
                            </button>
                        <?php endif; ?>
                        <button onclick="openDualReaderModal()" class="text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 flex items-center gap-1.5 px-3 sm:px-3.5 py-1.5 rounded-lg shadow-xs transition cursor-pointer">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"/>
                            </svg>
                            <span>Layar Ganda</span>
                        </button>
                        <a href="<?= base_url('report/' . $report['id'] . '/export') ?>" target="_blank" class="text-xs font-semibold text-slate-700 bg-white hover:bg-slate-50 border border-slate-200 flex items-center gap-1.5 px-3 sm:px-3.5 py-1.5 rounded-lg shadow-xs transition">
                            <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            <span>Unduh PDF</span>
                        </a>
                    <?php endif; ?>
                    <a href="<?= base_url('dashboard') ?>" class="text-xs font-semibold text-slate-600 hover:text-indigo-600 flex items-center gap-1 px-3 py-1.5 rounded-lg border border-slate-200 bg-slate-50 hover:bg-white transition">
                        <span>← Dashboard</span>
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Container -->
    <main class="max-w-5xl mx-auto px-4 sm:px-6 py-8 space-y-6">

        <!-- Document Metadata Header -->
        <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <div class="flex items-center gap-2 mb-2">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-indigo-700 bg-indigo-50 px-2.5 py-1 rounded-md border border-indigo-100">
                        Academic Evidence Report
                    </span>
                    <span class="text-xs text-slate-400">•</span>
                    <span class="text-xs text-slate-500">ID Submisi #<?= $document['id'] ?? $report['document_id'] ?></span>
                </div>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">
                    <?= esc($document['title'] ?? 'Dokumen Karya Ilmiah') ?>
                </h1>
                <p class="text-xs sm:text-sm text-slate-500 mt-1 flex flex-wrap items-center gap-3">
                    <span>File: <span class="font-mono-code text-slate-700 font-semibold"><?= esc($document['original_filename'] ?? '-') ?></span></span>
                    <span>•</span>
                    <span>Diperiksa: <strong class="text-slate-700"><?= date('d M Y, H:i', strtotime($report['created_at'])) ?></strong></span>
                    <?php if (!empty($evidenceData['total_words'])): ?>
                        <span>•</span>
                        <span>Total: <strong class="text-slate-700"><?= number_format($evidenceData['total_words']) ?> kata</strong></span>
                    <?php endif; ?>
                </p>
            </div>

            <!-- Overall Similarity Metric Badge -->
            <div class="flex sm:flex-col items-center sm:items-end justify-between border-t md:border-t-0 pt-4 md:pt-0 border-slate-100 gap-2">
                <div class="text-left sm:text-right">
                    <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Overall Similarity</span>
                    <div class="text-3xl sm:text-4xl font-extrabold <?= $report['overall_similarity'] >= 70 ? 'text-rose-600' : ($report['overall_similarity'] >= 10 ? 'text-amber-600' : 'text-emerald-600') ?>">
                        <?= number_format($report['overall_similarity'], 2) ?>%
                    </div>
                    <span class="text-[11px] text-slate-400">Skor Terpadu</span>
                </div>
                <?php if (($report['status'] ?? '') === 'completed'): ?>
                    <a href="<?= base_url('report/' . $report['id'] . '/export') ?>" target="_blank" class="inline-flex items-center gap-1.5 text-xs font-semibold text-indigo-700 bg-indigo-50 hover:bg-indigo-100 border border-indigo-200 px-3 py-1.5 rounded-lg transition shadow-xs mt-1">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        Cetak / Unduh PDF
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <?php if (($report['status'] ?? '') === 'processing' || ($report['status'] ?? '') === 'pending'): ?>
            <!-- Loading / Processing State -->
            <div class="bg-white border border-indigo-100 rounded-2xl p-10 text-center space-y-4 shadow-sm">
                <div class="inline-flex items-center justify-center w-14 h-14 rounded-full bg-indigo-50 text-indigo-600 animate-spin text-2xl">
                    ⏳
                </div>
                <h2 class="text-xl font-bold text-slate-800">Pemeriksaan Sedang Berlangsung</h2>
                <p class="text-sm text-slate-500 max-w-md mx-auto">
                    Dokumen Anda sedang diproses oleh background worker (analisis exact shingle & kosinus leksikal TF-IDF). Halaman akan otomatis diperbarui begitu proses selesai.
                </p>
                <div class="text-xs text-slate-400 font-mono-code">Status: <?= esc($report['status']) ?> (ID Laporan #<?= $report['id'] ?>)</div>
                <script>
                    setTimeout(() => { location.reload(); }, 2500);
                </script>
            </div>
        <?php elseif (($report['status'] ?? '') === 'failed'): ?>
            <!-- Error State -->
            <div class="bg-white border border-rose-200 rounded-2xl p-10 text-center space-y-4 shadow-sm">
                <div class="inline-flex items-center justify-center w-14 h-14 rounded-full bg-rose-50 text-rose-600 text-2xl">
                    ⚠️
                </div>
                <h2 class="text-xl font-bold text-slate-800">Pemeriksaan Mengalami Kendala</h2>
                <p class="text-sm text-rose-600 max-w-md mx-auto">
                    <?= esc($report['error_message'] ?? 'Terjadi kesalahan sistem saat memproses dokumen.') ?>
                </p>
                <a href="<?= base_url('dashboard') ?>" class="inline-block px-5 py-2.5 bg-slate-800 text-white rounded-lg text-xs font-semibold hover:bg-slate-900 transition">
                    ← Kembali ke Dashboard
                </a>
            </div>
        <?php else: ?>
        <?php
            $decision = $report['predicted_decision'] ?? 'no_match';
            $pattern = $report['evidence_pattern'] ?? 'NO_EVIDENCE';

            $patternDescriptions = [
                'VERBATIM_MATCH'          => 'Penyalinan kata persis secara mutlak tanpa parafrasa',
                'COPY_EDIT'               => 'Pasase substansial dipertahankan dengan subtitusi/modifikasi parsial kata',
                'COMMON_ACADEMIC_PHRASE'  => 'Kemiripan leksikal didominasi oleh frasa baku/klise akademis standar',
                'SEMANTIC_PARAPHRASE'     => 'Parafrasa topikal leksikal kuat tanpa pasase sekuensial panjang',
                'PARTIAL_DIRECT_MATCH'    => 'Pasase sekuensial signifikan terdeteksi pada fragmen teks tertentu',
                'SEQUENTIAL_EXACT'        => 'Pasase berurutan terdeteksi secara exact 5-gram',
                'TOPICAL_COSINE'          => 'Kemiripan orientasi kosakata leksikal TF-IDF',
                'NO_EVIDENCE'             => 'Tidak ditemukan bukti kemiripan sekuensial maupun leksikal yang signifikan'
            ];
            $patternDesc = $patternDescriptions[$pattern] ?? $pattern;

            // Variabel Evolusi v1.2
            $v11Pres = $evidenceData['v1_1_presentation'] ?? [];
            $v12Evol = $v11Pres['v1_2_evolution'] ?? [];
            $secSeg = $v12Evol['section_segmentation'] ?? null;
            $termDrill = $v12Evol['term_drilldown'] ?? null;
            $bpDetect = $v12Evol['boilerplate_detection'] ?? null;
        ?>

        <!-- ═════════════════════════════════════════════════════════════════════ -->
        <!-- FITUR 1: LIVE EXCLUSION FILTERS & CLIENT-SIDE DYNAMIC RECALCULATION   -->
        <!-- ═════════════════════════════════════════════════════════════════════ -->
        <div class="bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 rounded-2xl p-5 sm:p-6 text-white shadow-md border border-indigo-900/50 space-y-4">
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                <div class="space-y-1">
                    <div class="flex items-center gap-2">
                        <span class="text-base sm:text-lg">🎛️</span>
                        <h2 class="text-sm sm:text-base font-extrabold uppercase tracking-wide font-mono-code text-white">
                            Live Exclusion Filters &amp; Skor Murni
                        </h2>
                        <span class="text-[10px] uppercase font-bold px-2 py-0.5 rounded bg-indigo-500/30 text-indigo-200 border border-indigo-400/40 font-mono-code">
                            Fitur 1 (Live Recalc)
                        </span>
                    </div>
                    <p class="text-xs text-slate-300">
                        Aktifkan saklar di bawah ini untuk mengecualikan segmen tertentu secara instan di browser (<10ms) tanpa membebani server:
                    </p>
                </div>

                <!-- Live Score Badges -->
                <div class="flex items-center gap-3 bg-white/10 backdrop-blur-md px-4 py-2.5 rounded-xl border border-white/15 self-start lg:self-auto">
                    <div class="text-right">
                        <div class="text-[10px] uppercase font-bold text-slate-300">Skor Asli</div>
                        <div class="text-sm font-bold font-mono-code text-slate-300" id="rawScoreDisplay"><?= number_format($report['overall_similarity'], 2) ?>%</div>
                    </div>
                    <div class="text-slate-400 text-lg">➔</div>
                    <div>
                        <div class="text-[10px] uppercase font-bold text-indigo-200 flex items-center gap-1.5">
                            <span>Skor Kemiripan Murni</span>
                            <span id="filterActiveBadge" class="hidden w-2 h-2 rounded-full bg-emerald-400 animate-ping"></span>
                        </div>
                        <div class="text-xl sm:text-2xl font-black font-mono-code text-emerald-300 transition-all duration-300" id="liveAdjustedScoreDisplay">
                            <?= number_format($report['overall_similarity'], 2) ?>%
                        </div>
                    </div>
                </div>
            </div>

            <!-- Toggles Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 pt-3 border-t border-white/10 text-xs">
                <!-- Toggle 1: Quotes -->
                <label class="flex items-center justify-between p-3 rounded-xl bg-white/5 hover:bg-white/10 border border-white/10 cursor-pointer transition select-none group">
                    <div class="flex items-center gap-2.5 pr-2">
                        <span class="text-base">💬</span>
                        <div>
                            <div class="font-bold text-white group-hover:text-indigo-200 transition">Kecualikan Kutipan</div>
                            <div class="text-[10px] text-slate-400 leading-tight">Abaikan teks dalam tanda petik ("...")</div>
                        </div>
                    </div>
                    <input type="checkbox" id="toggleExcludeQuotes" onchange="recalculateLiveScore()" class="w-4 h-4 rounded text-indigo-500 focus:ring-indigo-400 bg-slate-800 border-slate-600 cursor-pointer">
                </label>

                <!-- Toggle 2: Boilerplate -->
                <label class="flex items-center justify-between p-3 rounded-xl bg-white/5 hover:bg-white/10 border border-white/10 cursor-pointer transition select-none group">
                    <div class="flex items-center gap-2.5 pr-2">
                        <span class="text-base">📐</span>
                        <div>
                            <div class="font-bold text-white group-hover:text-indigo-200 transition">Kecualikan Frasa Baku</div>
                            <div class="text-[10px] text-slate-400 leading-tight">Prosedur metode &amp; transisi ilmiah baku</div>
                        </div>
                    </div>
                    <input type="checkbox" id="toggleExcludeBoilerplate" onchange="recalculateLiveScore()" class="w-4 h-4 rounded text-indigo-500 focus:ring-indigo-400 bg-slate-800 border-slate-600 cursor-pointer">
                </label>

                <!-- Toggle 3: Front Matter -->
                <label class="flex items-center justify-between p-3 rounded-xl bg-white/5 hover:bg-white/10 border border-white/10 cursor-pointer transition select-none group">
                    <div class="flex items-center gap-2.5 pr-2">
                        <span class="text-base">📋</span>
                        <div>
                            <div class="font-bold text-white group-hover:text-indigo-200 transition">Kecualikan Formalitas Depan</div>
                            <div class="text-[10px] text-slate-400 leading-tight">Cover, pengesahan &amp; pernyataan</div>
                        </div>
                    </div>
                    <input type="checkbox" id="toggleExcludeFrontMatter" onchange="recalculateLiveScore()" class="w-4 h-4 rounded text-indigo-500 focus:ring-indigo-400 bg-slate-800 border-slate-600 cursor-pointer">
                </label>

                <!-- Toggle 4: Bibliography -->
                <label class="flex items-center justify-between p-3 rounded-xl bg-white/5 hover:bg-white/10 border border-white/10 cursor-pointer transition select-none group">
                    <div class="flex items-center gap-2.5 pr-2">
                        <span class="text-base">📚</span>
                        <div>
                            <div class="font-bold text-white group-hover:text-indigo-200 transition">Kecualikan Bibliografi</div>
                            <div class="text-[10px] text-slate-400 leading-tight">Daftar pustaka &amp; lampiran belakang</div>
                        </div>
                    </div>
                    <input type="checkbox" id="toggleExcludeBibliography" onchange="recalculateLiveScore()" class="w-4 h-4 rounded text-indigo-500 focus:ring-indigo-400 bg-slate-800 border-slate-600 cursor-pointer">
                </label>
            </div>

            <!-- Filter Status Bar -->
            <div id="filterStatusNotice" class="text-[11px] text-slate-300 font-mono-code flex items-center justify-between bg-black/20 px-3.5 py-1.5 rounded-lg border border-white/5">
                <span id="filterExcludedCountText">Semua pasase aktif (0 kata dikecualikan).</span>
                <button type="button" onclick="resetLiveFilters()" class="text-indigo-300 hover:text-white underline text-[10px] cursor-pointer">Reset Semua Filter</button>
            </div>
        </div>

        <!-- ═════════════════════════════════════════════════════════════════════ -->
        <!-- CORE EVIDENCE DECOMPOSITION CARD                                     -->
        <!-- ═════════════════════════════════════════════════════════════════════ -->
        <div class="bg-white border-2 border-slate-300 rounded-2xl overflow-hidden shadow-sm">
            <!-- Header -->
            <div class="bg-slate-900 text-white px-6 py-4 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                <div class="flex items-center gap-2.5">
                    <span class="text-xl">🧬</span>
                    <div>
                        <h2 class="text-base sm:text-lg font-bold tracking-wide uppercase font-mono-code">
                            HYBRID SIMILARITY RESULT
                        </h2>
                        <p class="text-[11px] text-slate-400">Evidence Decomposition Architecture (Exact + TF-IDF)</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-xs font-mono-code px-2.5 py-1 rounded bg-slate-800 text-slate-300 border border-slate-700">
                        v1.0 Frozen
                    </span>
                </div>
            </div>

            <!-- Tier 1: Synthesis Decision, Pattern, Agreement -->
            <div class="p-6 border-b border-slate-200 bg-slate-50/60">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <!-- Decision -->
                    <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-2xs">
                        <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-1">Decision</div>
                        <div class="flex items-center gap-2" data-decision="<?= esc($decision) ?>">
                            <?php if ($decision === 'match'): ?>
                                <span class="text-lg font-black text-rose-700">MATCH</span>
                                <span class="text-xs px-2 py-0.5 rounded-full bg-rose-100 text-rose-800 font-bold border border-rose-200">Identik</span>
                            <?php elseif ($decision === 'review'): ?>
                                <span class="text-lg font-black text-amber-700">REVIEW</span>
                                <span class="text-xs px-2 py-0.5 rounded-full bg-amber-100 text-amber-800 font-bold border border-amber-200">Perlu Ditelaah</span>
                            <?php else: ?>
                                <span class="text-lg font-black text-emerald-700">NO MATCH</span>
                                <span class="text-xs px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800 font-bold border border-emerald-200">Bebas Indikasi</span>
                            <?php endif; ?>
                        </div>
                        <div class="text-[11px] text-slate-500 mt-1">Klasifikasi objektif sistem</div>
                    </div>

                    <!-- Pattern -->
                    <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-2xs">
                        <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-1">Evidence Pattern</div>
                        <div class="font-bold text-sm text-slate-900 font-mono-code truncate" title="<?= esc($pattern) ?>">
                            <?= esc($pattern) ?>
                        </div>
                        <div class="text-[11px] text-slate-500 mt-1 truncate" title="<?= esc($patternDesc) ?>">
                            <?= esc($patternDesc) ?>
                        </div>
                    </div>

                    <!-- Agreement -->
                    <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-2xs">
                        <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-1">Evidence Agreement</div>
                        <div class="font-bold text-sm text-indigo-700 font-mono-code">
                            <?= esc($report['evidence_agreement'] ?? 'ANALYZED') ?>
                        </div>
                        <div class="text-[11px] text-slate-500 mt-1 flex items-center gap-1">
                            <?php if (!empty($report['sources_agree'])): ?>
                                <span class="text-emerald-600 font-semibold">✓ Kedua Lensa Sepakat</span>
                            <?php else: ?>
                                <span class="text-slate-500">Sumber Rujukan Berbeda</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tier 2: Exact Evidence & TF-IDF Evidence Side-by-Side -->
            <div class="grid grid-cols-1 md:grid-cols-2 divide-y md:divide-y-0 md:divide-x divide-slate-200 border-b border-slate-200">
                <!-- Left: EXACT EVIDENCE -->
                <div class="p-6 space-y-4 bg-white">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                        <div class="flex items-center gap-2">
                            <span class="w-6 h-6 rounded-md bg-indigo-600 text-white flex items-center justify-center font-bold text-xs">E</span>
                            <h3 class="font-bold text-sm uppercase tracking-wider text-slate-900 font-mono-code">EXACT EVIDENCE</h3>
                        </div>
                        <span class="text-[11px] text-slate-400 font-medium">5-gram Sequential Matcher</span>
                    </div>

                    <div class="space-y-3 font-mono-code text-xs">
                        <div class="flex justify-between items-center py-1.5 border-b border-slate-100">
                            <span class="text-slate-500">Similarity</span>
                            <span class="font-bold text-slate-900 text-sm"><?= number_format($report['exact_similarity'] ?? $report['overall_similarity'], 2) ?>%</span>
                        </div>
                        <div class="flex justify-between items-center py-1.5 border-b border-slate-100">
                            <span class="text-slate-500">Matched words</span>
                            <span class="font-bold text-slate-900"><?= number_format($evidenceData['exact_evidence']['matched_terms'] ?? ($report['match_count'] * 5)) ?> kata</span>
                        </div>
                        <div class="flex justify-between items-center py-1.5 border-b border-slate-100">
                            <span class="text-slate-500">Longest passage</span>
                            <span class="font-bold text-indigo-700"><?= number_format($report['longest_match_words']) ?> words</span>
                        </div>
                        <div class="flex justify-between items-center py-1.5 border-b border-slate-100">
                            <span class="text-slate-500">Max IDF</span>
                            <span class="font-bold text-slate-900"><?= number_format($evidenceData['exact_evidence']['max_idf'] ?? 0, 4) ?></span>
                        </div>
                    </div>

                    <p class="text-[11px] text-slate-500 italic pt-1">
                        * Mengukur bukti penyalinan pasase sekuensial mutlak dan panjang frasa kontigu.
                    </p>
                </div>

                <!-- Right: TF-IDF EVIDENCE -->
                <div class="p-6 space-y-4 bg-white">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                        <div class="flex items-center gap-2">
                            <span class="w-6 h-6 rounded-md bg-emerald-600 text-white flex items-center justify-center font-bold text-xs">T</span>
                            <h3 class="font-bold text-sm uppercase tracking-wider text-slate-900 font-mono-code">TF-IDF EVIDENCE</h3>
                        </div>
                        <span class="text-[11px] text-slate-400 font-medium">Sparse Cosine Vectorizer</span>
                    </div>

                    <div class="space-y-3 font-mono-code text-xs">
                        <div class="flex justify-between items-center py-1.5 border-b border-slate-100">
                            <span class="text-slate-500">Cosine similarity</span>
                            <span class="font-bold text-slate-900 text-sm"><?= number_format($report['cosine_similarity'] ?? 0, 2) ?>%</span>
                        </div>
                        <div class="flex justify-between items-center py-1.5 border-b border-slate-100">
                            <span class="text-slate-500">Shared terms</span>
                            <span class="font-bold text-slate-900"><?= number_format($report['shared_terms_count'] ?? 0) ?> distinct terms</span>
                        </div>
                        <div class="flex justify-between items-center py-1.5 border-b border-slate-100">
                            <span class="text-slate-500">Average IDF</span>
                            <span class="font-bold text-emerald-700"><?= number_format($report['average_shared_term_idf'] ?? 0, 4) ?></span>
                        </div>
                        <div class="flex justify-between items-center py-1.5 border-b border-slate-100">
                            <span class="text-slate-500">Detected sources</span>
                            <span class="font-bold text-slate-900"><?= number_format($evidenceData['tfidf_evidence']['source_count'] ?? count($sources)) ?> source(s)</span>
                        </div>
                    </div>

                    <!-- Shared Vocabulary Preview -->
                    <?php 
                        $topTerms = $evidenceData['tfidf_evidence']['top_terms'] ?? ($evidenceData['top_shared_terms'] ?? []);
                    ?>
                    <?php if (!empty($topTerms)): ?>
                        <div class="pt-1">
                            <span class="text-[11px] text-slate-400 font-medium block mb-1">Top Shared Vocabulary:</span>
                            <div class="flex flex-wrap gap-1">
                                <?php foreach (array_slice($topTerms, 0, 8) as $t): ?>
                                    <span class="px-2 py-0.5 rounded bg-slate-100 text-slate-700 font-mono-code text-[10px] border border-slate-200">
                                        <?= esc($t) ?>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <p class="text-[11px] text-slate-500 italic pt-1">
                            * Mengukur orientasi keselarasan leksikal topikal dan kebal terhadap sisipan format sitasi.
                        </p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Tier 3: WHY THIS RESULT? (Hybrid Rationale) -->
            <div class="p-6 bg-slate-50 border-b border-slate-200">
                <div class="flex items-center gap-2 mb-2">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-600 font-mono-code flex items-center gap-1.5">
                        <span>💬</span> WHY THIS RESULT?
                    </span>
                    <span class="text-[11px] text-slate-400">• Transparent Explainability Rationale</span>
                </div>
                <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-2xs">
                    <p class="text-sm font-medium text-slate-800 leading-relaxed italic">
                        "<?= esc($report['rationale'] ?? 'Analisis bukti telah selesai dijalankan.') ?>"
                    </p>
                    <p class="text-[11px] text-slate-400 mt-2">
                        Prinsip Keadilan: Penilaian ini diturunkan langsung dari bukti sekuensial dan bobot signifikansi term, bukan asumsi sepihak.
                    </p>
                </div>
            </div>

            <!-- Tier 4: SOURCE EVIDENCE (Grouped by Evidence Type: Direct Overlap vs Lexical Similarity) -->
            <?php
                $exactSimVal = (float) ($report['exact_similarity'] ?? $report['overall_similarity'] ?? 0);
                $cosineSimVal = (float) ($report['cosine_similarity'] ?? 0);
                $hasDivergence = ($exactSimVal <= 0.05 && $cosineSimVal > 0.0);

                // Kumpulkan ID sumber yang benar-benar memiliki pasase sekuensial di daftar matches
                $sourceIdsWithMatches = [];
                foreach ($matches as $m) {
                    if (!empty($m['source_document_id'])) {
                        $sourceIdsWithMatches[(int)$m['source_document_id']] = true;
                    }
                }

                // Group 1: Hanya sumber yang terbukti memiliki irisan pasase sekuensial riil
                $directOverlapSources = array_filter($sources ?? [], function($s) use ($sourceIdsWithMatches) {
                    $sId = (int) ($s['source_document_id'] ?? 0);
                    $ex = (float) ($s['exact_similarity'] ?? 0);
                    $mShingles = (int) ($s['matched_shingles'] ?? 0);
                    return ($ex > 0.05 && $mShingles > 0) || isset($sourceIdsWithMatches[$sId]);
                });

                // Group 2: Sumber yang memiliki kemiripan kosinus leksikal / topikal TF-IDF
                $lexicalSources = array_filter($sources ?? [], function($s) {
                    $cos = (float) ($s['cosine_similarity'] ?? 0);
                    return $cos > 0.0;
                });
            ?>
            <div class="p-6 bg-white space-y-6">
                <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-900 font-mono-code">
                            SOURCE EVIDENCE (DECOMPOSED)
                        </span>
                        <span class="text-xs text-slate-400">(<?= count($sources) ?> sumber rujukan)</span>
                    </div>
                </div>

                <?php if ($hasDivergence): ?>
                    <!-- Divergence State Callout -->
                    <div class="p-4 rounded-xl bg-amber-50/90 border border-amber-200 text-amber-900 text-xs sm:text-sm space-y-1.5 shadow-2xs">
                        <div class="flex items-center gap-2 font-bold text-amber-950">
                            <span class="text-base">⚠️</span>
                            <span>Divergensi Bukti Terdeteksi (Lexical Similarity Without Direct Textual Overlap)</span>
                        </div>
                        <p class="text-amber-800 leading-relaxed text-xs">
                            Dokumen rujukan teratas menunjukkan kemiripan kosakata/topik (TF-IDF Cosine: <strong><?= number_format($cosineSimVal, 2) ?>%</strong>), namun <strong>tidak ditemukan pasase sekuensial yang beririsan langsung (Exact: <?= number_format($exactSimVal, 2) ?>%)</strong>. Skor kosinus mencerminkan irisan terminologi dan medan makna bersama, bukan bukti penjiplakan sekuensial langsung.
                        </p>
                    </div>
                <?php endif; ?>

                <!-- Group 1: DIRECT TEXTUAL OVERLAP (Exact Sequential Matcher) -->
                <div class="space-y-3">
                    <div class="flex items-center justify-between border-b border-indigo-100 pb-1.5">
                        <div class="flex items-center gap-2">
                            <span class="w-5 h-5 rounded bg-indigo-600 text-white flex items-center justify-center font-bold text-xs">1</span>
                            <h4 class="font-bold text-xs uppercase tracking-wider text-indigo-950 font-mono-code">
                                DIRECT TEXTUAL OVERLAP (BUKTI SEKUENSIAL LANGSUNG)
                            </h4>
                        </div>
                        <span class="text-[11px] font-mono-code font-semibold px-2 py-0.5 rounded <?= empty($directOverlapSources) ? 'bg-slate-100 text-slate-400' : 'bg-indigo-50 text-indigo-700' ?>">
                            <?= count($directOverlapSources) ?> sumber
                        </span>
                    </div>

                    <?php if (empty($directOverlapSources)): ?>
                        <div class="p-4 rounded-lg bg-slate-50 border border-dashed border-slate-200 text-center text-xs text-slate-400">
                            🍃 Tidak ditemukan dokumen rujukan yang memiliki pasase sekuensial langsung (Exact = 0%).
                        </div>
                    <?php else: ?>
                        <div class="divide-y divide-slate-100 max-h-[300px] overflow-y-auto pr-1">
                            <?php foreach ($directOverlapSources as $src): ?>
                                <div class="py-3 first:pt-0 last:pb-0 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                    <div class="space-y-1">
                                        <div class="flex items-center gap-2">
                                            <span class="text-xs font-bold font-mono-code px-2 py-0.5 rounded bg-indigo-50 text-indigo-700">
                                                #<?= $src['source_rank'] ?>
                                            </span>
                                            <h5 class="font-bold text-sm text-slate-900">
                                                <?= esc($src['source_title']) ?>
                                            </h5>
                                            <span class="text-[11px] uppercase font-semibold px-2 py-0.5 rounded bg-slate-100 text-slate-600">
                                                <?= esc($src['source_type'] ?? 'dokumen') ?>
                                            </span>
                                            <?php if (!empty($src['is_jit_discovered']) || in_array((int)$src['source_document_id'], $jitDiscoveredIds ?? [], true)): ?>
                                                <span class="text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded bg-emerald-100 text-emerald-800 border border-emerald-300 flex items-center gap-1 shadow-2xs">
                                                    <span>🎯</span> JIT Peer Discovery
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="text-xs text-slate-500 font-mono-code flex flex-wrap items-center gap-3">
                                            <span>Exact Overlap: <strong class="text-indigo-700 font-bold"><?= number_format($src['exact_similarity'] ?? $src['similarity'], 1) ?>%</strong></span>
                                            <span>•</span>
                                            <span><?= number_format($src['matched_words']) ?> matched words</span>
                                        </div>
                                    </div>

                                    <div>
                                        <button onclick="openPassageModal(<?= $src['source_document_id'] ?>, '<?= esc($src['source_title']) ?>')" class="w-full sm:w-auto px-4 py-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 font-bold text-xs rounded-lg border border-indigo-200 transition shadow-2xs flex items-center justify-center gap-1.5">
                                            <span>🔍</span> [View passage]
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Group 2: LEXICAL / TOPIC SIMILARITY (TF-IDF Vector Space Matcher) -->
                <div class="space-y-3 pt-2">
                    <div class="flex items-center justify-between border-b border-teal-100 pb-1.5">
                        <div class="flex items-center gap-2">
                            <span class="w-5 h-5 rounded bg-teal-600 text-white flex items-center justify-center font-bold text-xs">2</span>
                            <h4 class="font-bold text-xs uppercase tracking-wider text-teal-950 font-mono-code">
                                LEXICAL / TOPIC SIMILARITY (KEMIRIPAN TOPIKAL & KOSAKATA)
                            </h4>
                        </div>
                        <span class="text-[11px] font-mono-code font-semibold px-2 py-0.5 rounded <?= empty($lexicalSources) ? 'bg-slate-100 text-slate-400' : 'bg-teal-50 text-teal-700' ?>">
                            <?= count($lexicalSources) ?> sumber
                        </span>
                    </div>

                    <?php if (empty($lexicalSources)): ?>
                        <div class="p-4 rounded-lg bg-slate-50 border border-dashed border-slate-200 text-center text-xs text-slate-400">
                            🍃 Tidak ditemukan dokumen rujukan dengan irisan kosinus leksikal signifikan.
                        </div>
                    <?php else: ?>
                        <div class="divide-y divide-slate-100 max-h-[300px] overflow-y-auto pr-1">
                            <?php foreach ($lexicalSources as $src): ?>
                                <div class="py-3 first:pt-0 last:pb-0 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                    <div class="space-y-1">
                                        <div class="flex items-center gap-2">
                                            <span class="text-xs font-bold font-mono-code px-2 py-0.5 rounded bg-teal-50 text-teal-700">
                                                #<?= $src['source_rank'] ?>
                                            </span>
                                            <h5 class="font-bold text-sm text-slate-900">
                                                <?= esc($src['source_title']) ?>
                                            </h5>
                                            <span class="text-[11px] uppercase font-semibold px-2 py-0.5 rounded bg-slate-100 text-slate-600">
                                                <?= esc($src['source_type'] ?? 'dokumen') ?>
                                            </span>
                                            <?php if (!empty($src['is_jit_discovered']) || in_array((int)$src['source_document_id'], $jitDiscoveredIds ?? [], true)): ?>
                                                <span class="text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded bg-emerald-100 text-emerald-800 border border-emerald-300 flex items-center gap-1 shadow-2xs">
                                                    <span>🎯</span> JIT Peer Discovery
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="text-xs text-slate-500 font-mono-code flex flex-wrap items-center gap-3">
                                            <span>Cosine Similarity: <strong class="text-teal-700 font-bold"><?= number_format($src['cosine_similarity'] ?? 0, 1) ?>%</strong></span>
                                            <span>•</span>
                                            <span><?= number_format($src['matched_words']) ?> shared terms</span>
                                        </div>
                                    </div>

                                    <div class="flex items-center gap-1.5 self-start sm:self-auto">
                                        <?php if (!empty($sourceIdsWithMatches[(int)$src['source_document_id']])): ?>
                                            <button onclick="openPassageModal(<?= $src['source_document_id'] ?>, '<?= esc($src['source_title']) ?>')" class="w-full sm:w-auto px-3.5 py-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 font-bold text-xs rounded-lg border border-indigo-200 transition shadow-2xs flex items-center justify-center gap-1 cursor-pointer">
                                                <span>🔍</span> [View passage]
                                            </button>
                                        <?php endif; ?>
                                        <button onclick="openDualReaderModal(<?= $src['source_document_id'] ?>)" class="w-full sm:w-auto px-3.5 py-1.5 bg-teal-50 hover:bg-teal-100 text-teal-800 font-bold text-xs rounded-lg border border-teal-200 transition shadow-2xs flex items-center justify-center gap-1.5 cursor-pointer" title="Buka kedua dokumen berdampingan di Layar Ganda">
                                            <span>📖</span> Layar Ganda
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- ═════════════════════════════════════════════════════════════════════ -->
        <!-- FITUR 2: INDIKATOR TEKS BUATAN AI & PROFIL STYLOMETRI                 -->
        <!-- ═════════════════════════════════════════════════════════════════════ -->
        <?php if (!empty($aiStylometrics)): ?>
        <?php
            $aiScore = (float)($aiStylometrics['ai_probability_score'] ?? 0);
            $aiConf = $aiStylometrics['ai_confidence_level'] ?? 'LOW';
            $aiLabel = $aiStylometrics['verdict_label'] ?? 'Gaya Bahasa Alami Manusia';
            $aiMetrics = $aiStylometrics['metrics'] ?? [];
            $burstiness = (float)($aiMetrics['burstiness'] ?? 0.5);
            $transDensity = (float)($aiMetrics['transition_density'] ?? 0);
            $avgSentLen = (float)($aiMetrics['average_sentence_length'] ?? 0);
            $anomalies = $aiStylometrics['anomalies'] ?? [];

            $aiScoreBadgeClass = match($aiConf) {
                'HIGH' => 'bg-rose-50 text-rose-700 border-rose-200',
                'MODERATE' => 'bg-amber-50 text-amber-700 border-amber-200',
                default => 'bg-emerald-50 text-emerald-700 border-emerald-200'
            };
            $aiScoreColor = match($aiConf) {
                'HIGH' => 'text-rose-600',
                'MODERATE' => 'text-amber-600',
                default => 'text-emerald-600'
            };
        ?>
        <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-xs space-y-5">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 pb-3">
                <div class="flex items-center gap-2.5">
                    <span class="text-2xl">🤖</span>
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="font-bold text-base text-slate-900">Indikator Teks Buatan AI &amp; Profil Stylometri</h3>
                            <span class="text-[10px] font-bold font-mono-code uppercase px-2 py-0.5 rounded bg-purple-50 text-purple-700 border border-purple-100">Fitur 2</span>
                        </div>
                        <p class="text-xs text-slate-400">Analisis variabilitas ritme sintaksis (Burstiness), kepadatan frasa transisi generik, &amp; anomali pemintal teks (Spinner/QuillBot)</p>
                    </div>
                </div>
                <div class="flex items-center gap-2 self-start sm:self-auto">
                    <span class="text-xs font-mono-code font-bold px-3 py-1 rounded-full border shadow-2xs <?= $aiScoreBadgeClass ?>">
                        Probabilitas AI: <strong class="<?= $aiScoreColor ?>"><?= number_format($aiScore, 1) ?>%</strong> (<?= $aiConf ?>)
                    </span>
                </div>
            </div>

            <!-- Stylometric Gauges & Metric Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3.5">
                <!-- 1. Burstiness (Variabilitas Panjang Kalimat) -->
                <div class="p-3.5 rounded-xl border border-slate-200 bg-slate-50/70 space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500">1. Sentence Burstiness</span>
                        <span class="text-xs font-mono-code font-bold <?= $burstiness < 0.28 ? 'text-rose-600' : 'text-emerald-600' ?>">
                            <?= number_format($burstiness, 2) ?>
                        </span>
                    </div>
                    <div class="w-full bg-slate-200 h-2 rounded-full overflow-hidden">
                        <div style="width: <?= min(100, max(5, $burstiness * 100)) ?>%" class="h-full <?= $burstiness < 0.28 ? 'bg-rose-500' : ($burstiness < 0.40 ? 'bg-amber-500' : 'bg-emerald-500') ?>"></div>
                    </div>
                    <div class="flex justify-between text-[10px] text-slate-400 font-mono-code">
                        <span>AI (&lt;0.28)</span>
                        <span>Manusia (&gt;0.45)</span>
                    </div>
                    <p class="text-[11px] text-slate-500 leading-tight">
                        <?= $burstiness < 0.28 ? 'Panjang kalimat sangat seragam (khas LLM).' : 'Ritme bervariasi secara organik (khas manusia).' ?>
                    </p>
                </div>

                <!-- 2. Kepadatan Transisi AI -->
                <div class="p-3.5 rounded-xl border border-slate-200 bg-slate-50/70 space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500">2. Kepadatan Frasa AI</span>
                        <span class="text-xs font-mono-code font-bold <?= $transDensity >= 2.5 ? 'text-rose-600' : 'text-slate-700' ?>">
                            <?= number_format($transDensity, 1) ?> / 1k kata
                        </span>
                    </div>
                    <div class="w-full bg-slate-200 h-2 rounded-full overflow-hidden">
                        <div style="width: <?= min(100, $transDensity * 20) ?>%" class="h-full <?= $transDensity >= 2.5 ? 'bg-rose-500' : 'bg-indigo-500' ?>"></div>
                    </div>
                    <div class="flex justify-between text-[10px] text-slate-400 font-mono-code">
                        <span>Wajar (&lt;2.0)</span>
                        <span>Tinggi (&gt;3.5)</span>
                    </div>
                    <p class="text-[11px] text-slate-500 leading-tight">
                        Frekuensi frasa klise LLM (misal: "dalam era digital", "perlu digarisbawahi").
                    </p>
                </div>

                <!-- 3. Rata-rata Panjang Kalimat -->
                <div class="p-3.5 rounded-xl border border-slate-200 bg-slate-50/70 space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500">3. Rerata Kalimat</span>
                        <span class="text-xs font-mono-code font-bold text-slate-800">
                            <?= number_format($avgSentLen, 1) ?> kata
                        </span>
                    </div>
                    <div class="text-sm font-extrabold text-slate-800">
                        <?= number_format($aiMetrics['total_sentences_analyzed'] ?? 0) ?> kalimat dianalisis
                    </div>
                    <p class="text-[11px] text-slate-500 leading-tight">
                        Teks LLM cenderung menghasilkan rata-rata 18–28 kata per kalimat secara konsisten.
                    </p>
                </div>

                <!-- 4. Anomali Parafrase Spinner -->
                <div class="p-3.5 rounded-xl border <?= empty($anomalies) ? 'border-emerald-200 bg-emerald-50/30' : 'border-amber-200 bg-amber-50/40' ?> space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-bold uppercase tracking-wider <?= empty($anomalies) ? 'text-emerald-700' : 'text-amber-800' ?>">4. Rekayasa Leksikal</span>
                        <span class="text-xs font-mono-code font-bold <?= empty($anomalies) ? 'text-emerald-700' : 'text-amber-700' ?>">
                            <?= count($anomalies) ?> anomali
                        </span>
                    </div>
                    <div class="text-sm font-extrabold <?= empty($anomalies) ? 'text-emerald-900' : 'text-amber-900' ?>">
                        <?= empty($anomalies) ? 'Bersih (Bebas Spinner)' : 'Terdeteksi Pola Canggung' ?>
                    </div>
                    <p class="text-[11px] <?= empty($anomalies) ? 'text-emerald-700/80' : 'text-amber-800/80' ?> leading-tight">
                        Pemeriksaan substitusi sinonim hiperbolis tak wajar (QuillBot/Spinner).
                    </p>
                </div>
            </div>

            <!-- Stylometric Narrative Box & Ethical Fairness Disclaimer -->
            <div class="p-4 rounded-xl bg-purple-50/60 border border-purple-200 text-xs sm:text-sm text-purple-950 space-y-2">
                <div class="flex items-center justify-between font-bold text-xs font-mono-code">
                    <span class="flex items-center gap-1.5">
                        <span>💬</span> KESIMPULAN STYLOMETRI: <span class="<?= $aiScoreColor ?>"><?= esc($aiLabel) ?></span>
                    </span>
                </div>
                <p class="text-slate-700 text-xs sm:text-sm leading-relaxed italic bg-white p-3 rounded-lg border border-purple-100 shadow-2xs">
                    "<?= esc($aiStylometrics['summary_rationale'] ?? '') ?>"
                </p>
                <div class="flex items-start gap-2 pt-1 text-[11px] text-purple-800/80">
                    <span class="text-sm">⚖️</span>
                    <span><strong>Prinsip Keadilan Akademik:</strong> Indikator ini merupakan panduan profil probabilitas statistik, bukan vonis mutlak. Penguji dan pembimbing disarankan melakukan klarifikasi dialogis lisan secara arif mengenai pemahaman konsep karya tulis mahasiswa.</span>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- ═════════════════════════════════════════════════════════════════════ -->
        <!-- FITUR 5: KEMIRIPAN LINTAS BAHASA (CROSS-LINGUAL SIMILARITY ID <-> EN) -->
        <!-- ═════════════════════════════════════════════════════════════════════ -->
        <?php if (!empty($crossLingual) && !empty($crossLingual['is_cross_lingual_match'])): ?>
        <div class="bg-white border-2 border-indigo-200 rounded-2xl p-6 shadow-xs space-y-5">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-indigo-100 pb-3">
                <div class="flex items-center gap-2.5">
                    <span class="text-2xl">🌐</span>
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="font-bold text-base text-slate-900">Deteksi Kemiripan Lintas Bahasa (Cross-Lingual ID ↔ EN)</h3>
                            <span class="text-[10px] font-bold font-mono-code uppercase px-2 py-0.5 rounded bg-indigo-50 text-indigo-700 border border-indigo-200">Fitur 5</span>
                        </div>
                        <p class="text-xs text-slate-400">Deteksi naskah hasil terjemahan langsung (direct translation) dari literatur berbahasa Inggris tanpa pencantuman sitasi sah</p>
                    </div>
                </div>
                <div class="flex items-center gap-2 self-start sm:self-auto">
                    <span class="text-xs font-mono-code font-bold px-3 py-1 rounded-full bg-indigo-50 text-indigo-800 border border-indigo-300 shadow-2xs">
                        Pasangan: <strong><?= esc($crossLingual['detected_pair'] ?? 'EN -> ID') ?></strong> • Skor: <strong><?= number_format($crossLingual['cross_lingual_score'] ?? 0, 1) ?>%</strong>
                    </span>
                </div>
            </div>

            <!-- Metrics Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3.5">
                <div class="p-3.5 rounded-xl border border-indigo-100 bg-indigo-50/40 space-y-1">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-indigo-700">Skor Keselarasan Konsep</span>
                    <div class="text-2xl font-extrabold text-indigo-900"><?= number_format($crossLingual['cross_lingual_score'] ?? 0, 1) ?>%</div>
                    <p class="text-[11px] text-indigo-800/70">Tingkat kesamaan semantik terstruktur lintas bahasa.</p>
                </div>
                <div class="p-3.5 rounded-xl border border-slate-200 bg-slate-50/70 space-y-1">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-slate-600">Konsep Bersama (Concepts)</span>
                    <div class="text-2xl font-extrabold text-slate-800"><?= (int)($crossLingual['shared_concept_count'] ?? 0) ?> istilah</div>
                    <p class="text-[11px] text-slate-500">Terminologi akademik kunci yang dipetakan secara kanonikal.</p>
                </div>
                <div class="p-3.5 rounded-xl border border-slate-200 bg-slate-50/70 space-y-1">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-slate-600">Pasangan Relasi Gagasan</span>
                    <div class="text-2xl font-extrabold text-slate-800"><?= (int)($crossLingual['shared_pairs_count'] ?? 0) ?> relasi</div>
                    <p class="text-[11px] text-slate-500">Struktur ko-okurensi gagasan dalam jendela kalimat.</p>
                </div>
            </div>

            <!-- Matched Academic Concepts Pills -->
            <?php if (!empty($crossLingual['matched_concepts'])): ?>
                <div class="space-y-1.5">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-500 font-mono-code block">Konsep Kunci yang Selaras Lintas Bahasa:</span>
                    <div class="flex flex-wrap gap-1.5">
                        <?php foreach ($crossLingual['matched_concepts'] as $mc): ?>
                            <span class="px-2.5 py-1 rounded-lg bg-indigo-50 text-indigo-800 font-mono-code text-xs font-semibold border border-indigo-200 flex items-center gap-1 shadow-2xs">
                                <span>🎯</span> <?= esc($mc) ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Rationale Box -->
            <div class="p-4 rounded-xl bg-slate-50 border border-indigo-200 text-xs sm:text-sm text-slate-700 leading-relaxed space-y-1.5">
                <div class="font-bold text-indigo-950 flex items-center gap-1.5 text-xs uppercase tracking-wider font-mono-code">
                    <span>💡</span> Analisis Semantik Terjemahan:
                </div>
                <p class="text-slate-800 leading-relaxed italic bg-white p-3 rounded-lg border border-slate-200 shadow-2xs">
                    "<?= esc($crossLingual['rationale'] ?? '') ?>"
                </p>
                <p class="text-[11px] text-slate-500 pt-0.5">
                    Prinsip Keadilan: Mengidentifikasi penulisan yang menyalin alur pemikiran dari literatur asing tanpa penyebutan rujukan primer.
                </p>
            </div>
        </div>
        <?php endif; ?>

        <!-- ═════════════════════════════════════════════════════════════════════ -->
        <!-- SINTESIS RISIKO AKADEMIK BERBOBOT ML (XGBOOST CLASSIFIER v3.4)       -->
        <!-- ═════════════════════════════════════════════════════════════════════ -->
        <?php if (!empty($xgboostRisk)): ?>
        <?php
            $xgbRiskScore = (float)($xgboostRisk['calibrated_risk_score'] ?? 0.0);
            $xgbRiskLevel = $xgboostRisk['risk_level'] ?? 'LOW';
            $xgbLabel = $xgboostRisk['verdict_label'] ?? 'Aman (Bebas Indikasi Signifikan)';
            $xgbRationale = $xgboostRisk['summary_rationale'] ?? '';
            $xgbProbs = $xgboostRisk['probabilities'] ?? [];
            $pOrig = round((float)($xgbProbs['p_original'] ?? 0.0) * 100, 1);
            $pRev = round((float)($xgbProbs['p_needs_review'] ?? 0.0) * 100, 1);
            $pRisk = round((float)($xgbProbs['p_high_risk'] ?? 0.0) * 100, 1);
            $xgbDrivers = $xgboostRisk['explainable_drivers'] ?? [];

            $xgbBadgeClass = match($xgbRiskLevel) {
                'HIGH' => 'bg-rose-50 text-rose-700 border-rose-300',
                'MODERATE' => 'bg-amber-50 text-amber-700 border-amber-300',
                default => 'bg-emerald-50 text-emerald-700 border-emerald-300'
            };
            $xgbMeterColor = match($xgbRiskLevel) {
                'HIGH' => 'bg-rose-600',
                'MODERATE' => 'bg-amber-500',
                default => 'bg-emerald-600'
            };
        ?>
        <div class="bg-white border-2 border-slate-300 rounded-2xl p-6 shadow-xs space-y-5">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-200 pb-3">
                <div class="flex items-center gap-2.5">
                    <span class="text-2xl">🤖</span>
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="font-bold text-base text-slate-900">Sintesis Risiko Akademik Berbobot XGBoost</h3>
                            <span class="text-[10px] font-bold font-mono-code uppercase px-2 py-0.5 rounded bg-purple-50 text-purple-700 border border-purple-200">
                                XGBoost v3.4 • 11 Dimensi Tabular
                            </span>
                        </div>
                        <p class="text-xs text-slate-400">Penyintesis probabilistik terkalibrasi yang memadukan bukti sekuensial, leksikal, stylometri, dan alih bahasa secara non-linear</p>
                    </div>
                </div>
                <div class="flex items-center gap-2 self-start sm:self-auto">
                    <span class="text-xs font-mono-code font-bold px-3 py-1 rounded-full border shadow-2xs <?= $xgbBadgeClass ?>">
                        Level Risiko: <strong><?= esc($xgbRiskLevel) ?></strong> (<?= number_format($xgbRiskScore, 1) ?>%)
                    </span>
                </div>
            </div>

            <!-- Probabilities & Score Grid -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-3.5">
                <!-- Calibrated Risk Score -->
                <div class="p-3.5 rounded-xl border border-slate-200 bg-slate-50 space-y-1.5">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Skor Risiko Terkalibrasi</span>
                    <div class="text-2xl font-extrabold text-slate-900 font-mono-code"><?= number_format($xgbRiskScore, 1) ?>%</div>
                    <div class="w-full bg-slate-200 rounded-full h-1.5 overflow-hidden">
                        <div class="h-1.5 rounded-full <?= $xgbMeterColor ?>" style="width: <?= min(100, max(2, $xgbRiskScore)) ?>%"></div>
                    </div>
                    <p class="text-[10px] text-slate-400">Sintesis agregat ensemble ML</p>
                </div>

                <!-- Probability 1: P(Original) -->
                <div class="p-3.5 rounded-xl border border-emerald-100 bg-emerald-50/40 space-y-1">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-emerald-700">P (Orisinal Mandiri)</span>
                        <span class="text-xs">🌱</span>
                    </div>
                    <div class="text-2xl font-extrabold text-emerald-800 font-mono-code"><?= number_format($pOrig, 1) ?>%</div>
                    <p class="text-[10px] text-emerald-700/80">Peluang kepenulisan mandiri murni</p>
                </div>

                <!-- Probability 2: P(Review) -->
                <div class="p-3.5 rounded-xl border border-amber-100 bg-amber-50/40 space-y-1">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-amber-700">P (Perlu Telaah Dosen)</span>
                        <span class="text-xs">🔍</span>
                    </div>
                    <div class="text-2xl font-extrabold text-amber-800 font-mono-code"><?= number_format($pRev, 1) ?>%</div>
                    <p class="text-[10px] text-amber-700/80">Peluang nuansa teks perlu klarifikasi</p>
                </div>

                <!-- Probability 3: P(High Risk) -->
                <div class="p-3.5 rounded-xl border border-rose-100 bg-rose-50/40 space-y-1">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-rose-700">P (Risiko Tinggi / Verbatim)</span>
                        <span class="text-xs">⚠️</span>
                    </div>
                    <div class="text-2xl font-extrabold text-rose-800 font-mono-code"><?= number_format($pRisk, 1) ?>%</div>
                    <p class="text-[10px] text-rose-700/80">Peluang kesamaan tekstual substansial</p>
                </div>
            </div>

            <!-- Explainable AI (Risk Drivers) -->
            <div class="space-y-2">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500 font-mono-code flex items-center gap-1.5">
                    <span>🧭</span> Faktor Pendorong Keputusan (Explainable AI Drivers):
                </span>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                    <?php foreach ($xgbDrivers as $driver): ?>
                        <div class="p-2.5 rounded-lg bg-slate-50 border border-slate-200 text-xs text-slate-700 flex items-start gap-2 shadow-2xs">
                            <span class="text-indigo-600 mt-0.5">✦</span>
                            <span><?= esc($driver) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Summary & Academic Fairness Notice -->
            <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 text-xs sm:text-sm text-slate-700 leading-relaxed space-y-2">
                <div class="font-bold text-slate-900 flex items-center gap-1.5 text-xs uppercase tracking-wider font-mono-code">
                    <span>💡</span> Rekomendasi Sintesis XGBoost:
                </div>
                <p class="text-slate-800 leading-relaxed italic bg-white p-3 rounded-lg border border-slate-200 shadow-2xs">
                    "<?= esc($xgbRationale) ?>"
                </p>
                <div class="flex items-start gap-2 pt-1 text-[11px] text-slate-500">
                    <span class="text-sm">⚖️</span>
                    <span><strong>Prinsip Keadilan Akademik:</strong> Integrasi XGBoost berfungsi sebagai penimbang bukti probabilistik untuk membantu pembimbing dan penguji memilah formulasi baku atau kutipan sah dari penyalinan substantif, tanpa vonis otomatis yang merugikan integritas mahasiswa.</span>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- ═════════════════════════════════════════════════════════════════════ -->
        <!-- JUST-IN-TIME (JIT) PEER REFERENCE DISCOVERY                           -->
        <!-- ═════════════════════════════════════════════════════════════════════ -->
        <?php if (!empty($jitHarvesting) && !empty($jitHarvesting['discovered_works'])): ?>
        <div class="bg-white border-2 border-emerald-200 rounded-2xl p-6 shadow-xs space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-emerald-100 pb-3">
                <div class="flex items-center gap-2.5">
                    <span class="text-2xl">🎯</span>
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="font-bold text-base text-slate-900">Just-In-Time (JIT) Peer Reference Discovery</h3>
                            <span class="text-[10px] font-bold font-mono-code uppercase px-2 py-0.5 rounded bg-emerald-50 text-emerald-800 border border-emerald-300">
                                On-Demand Harvesting
                            </span>
                        </div>
                        <p class="text-xs text-slate-500">
                            AR Tilik secara otomatis mendeteksi domain disipliner naskah dan mengindeks karya sebidang dari basis data terbuka sebagai pembanding riil
                        </p>
                    </div>
                </div>
                <div class="flex items-center gap-2 self-start sm:self-auto">
                    <span class="text-xs font-mono-code font-bold px-3 py-1 rounded-full bg-emerald-50 text-emerald-800 border border-emerald-300 shadow-2xs">
                        <?= count($jitHarvesting['discovered_works']) ?> Karya Sebidang Ditemukan
                    </span>
                </div>
            </div>

            <!-- Substantive Topic Queries Executed -->
            <?php if (!empty($jitHarvesting['queries_executed'])): ?>
                <div class="space-y-1.5">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-500 font-mono-code block">
                        Kata Kunci &amp; Domain Disipliner yang Diekstrak:
                    </span>
                    <div class="flex flex-wrap gap-1.5">
                        <?php foreach ($jitHarvesting['queries_executed'] as $q): ?>
                            <span class="px-2.5 py-1 rounded-lg bg-emerald-50 text-emerald-800 font-mono-code text-xs font-semibold border border-emerald-200 flex items-center gap-1 shadow-2xs">
                                <span>🏷️</span> <?= esc($q) ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Discovered Peer Works List -->
            <div class="space-y-2 pt-1">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500 font-mono-code block">
                    Karya Ilmiah Sebidang yang Diindeks ke Korpus Pembanding:
                </span>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <?php foreach ($jitHarvesting['discovered_works'] as $dw): ?>
                        <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200 space-y-1 shadow-2xs">
                            <div class="flex items-center justify-between gap-2">
                                <span class="text-[10px] font-bold font-mono-code px-2 py-0.5 rounded bg-emerald-100 text-emerald-800">
                                    Tahun <?= esc($dw['year'] ?? '-') ?>
                                </span>
                                <?php if (!empty($dw['url'])): ?>
                                    <a href="<?= esc($dw['url']) ?>" target="_blank" rel="noopener noreferrer" class="text-[11px] text-indigo-600 hover:text-indigo-800 hover:underline flex items-center gap-1">
                                        <span>🔗 Buka Sumber</span>
                                    </a>
                                <?php endif; ?>
                            </div>
                            <h5 class="font-bold text-xs text-slate-900 leading-snug">
                                <?= esc($dw['title']) ?>
                            </h5>
                            <p class="text-[11px] text-slate-500 italic">
                                <?= esc($dw['source_name'] ?? 'Basis Data Akademik Terbuka') ?>
                            </p>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- ═════════════════════════════════════════════════════════════════════ -->
        <!-- MODUL 1: DEKOMPOSISI STRUKTUR & ZONASI NASKAH (SMART SECTION PARSER)  -->
        <!-- ═════════════════════════════════════════════════════════════════════ -->
        <?php if (!empty($secSeg)): ?>
        <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-xs space-y-5">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 pb-3">
                <div class="flex items-center gap-2.5">
                    <span class="text-2xl">📑</span>
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="font-bold text-base text-slate-900">Dekomposisi Struktur & Zonasi Naskah</h3>
                            <span class="text-[10px] font-bold font-mono-code uppercase px-2 py-0.5 rounded bg-indigo-50 text-indigo-700 border border-indigo-100">Modul 1</span>
                        </div>
                        <p class="text-xs text-slate-400">Pembedahan fungsional naskah untuk mengisolasi formalitas administratif dari substansi pemikiran ilmiah</p>
                    </div>
                </div>
                <div class="flex items-center gap-2 self-start sm:self-auto">
                    <span class="text-xs font-mono-code font-bold px-3 py-1 rounded-full bg-indigo-50 text-indigo-700 border border-indigo-200 shadow-2xs">
                        <?= number_format($secSeg['core_substance_percentage'] ?? 0, 1) ?>% Substansi Pokok
                    </span>
                </div>
            </div>

            <!-- Multi-Zone Progress Bar -->
            <div class="space-y-2">
                <div class="flex justify-between text-xs text-slate-500 font-medium">
                    <span>Distribusi Proporsi Naskah</span>
                    <span>Total Volume: <strong class="text-slate-700 font-mono-code"><?= number_format($secSeg['total_words'] ?? 0) ?> kata</strong></span>
                </div>
                <?php
                    $pctFront = (float)($secSeg['zone_percentage_distribution']['FRONT_MATTER'] ?? 0);
                    $pctBody = (float)($secSeg['zone_percentage_distribution']['BODY_TEXT'] ?? 0);
                    $pctMethod = (float)($secSeg['zone_percentage_distribution']['METHODOLOGY'] ?? 0);
                    $pctBack = (float)($secSeg['zone_percentage_distribution']['BACK_MATTER'] ?? 0);
                ?>
                <div class="h-3.5 w-full bg-slate-100 rounded-full overflow-hidden flex shadow-inner">
                    <?php if ($pctFront > 0): ?>
                        <div style="width: <?= $pctFront ?>%" class="bg-slate-400 hover:opacity-90 transition-opacity" title="Front Matter: <?= $pctFront ?>%"></div>
                    <?php endif; ?>
                    <?php if ($pctBody > 0): ?>
                        <div style="width: <?= $pctBody ?>%" class="bg-indigo-600 hover:opacity-90 transition-opacity" title="Body Text (Substansi): <?= $pctBody ?>%"></div>
                    <?php endif; ?>
                    <?php if ($pctMethod > 0): ?>
                        <div style="width: <?= $pctMethod ?>%" class="bg-amber-500 hover:opacity-90 transition-opacity" title="Methodology: <?= $pctMethod ?>%"></div>
                    <?php endif; ?>
                    <?php if ($pctBack > 0): ?>
                        <div style="width: <?= $pctBack ?>%" class="bg-teal-500 hover:opacity-90 transition-opacity" title="Back Matter: <?= $pctBack ?>%"></div>
                    <?php endif; ?>
                </div>
                <div class="flex flex-wrap items-center gap-4 text-[11px] pt-1 text-slate-500">
                    <span class="inline-flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-slate-400"></span> Front Matter (<?= number_format($pctFront, 1) ?>%)</span>
                    <span class="inline-flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-indigo-600"></span> Body Text (<?= number_format($pctBody, 1) ?>%)</span>
                    <span class="inline-flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span> Methodology (<?= number_format($pctMethod, 1) ?>%)</span>
                    <span class="inline-flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-teal-500"></span> Back Matter (<?= number_format($pctBack, 1) ?>%)</span>
                </div>
            </div>

            <!-- 4 Functional Zones Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                <!-- Zone 1: Front Matter -->
                <div class="p-3.5 rounded-xl border border-slate-200 bg-slate-50/70 space-y-1.5">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500">1. Front Matter</span>
                        <span class="text-xs font-mono-code font-bold text-slate-700"><?= number_format($pctFront, 1) ?>%</span>
                    </div>
                    <div class="text-sm font-extrabold text-slate-800"><?= number_format($secSeg['zone_word_distribution']['FRONT_MATTER'] ?? 0) ?> kata</div>
                    <p class="text-[11px] text-slate-500 leading-tight">Formalitas: Cover, Lembar Pengesahan, Abstrak, & Daftar Isi.</p>
                </div>

                <!-- Zone 2: Body Text (Substance) -->
                <div class="p-3.5 rounded-xl border border-indigo-200 bg-indigo-50/40 space-y-1.5">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-indigo-700">2. Body Text (Substansi)</span>
                        <span class="text-xs font-mono-code font-bold text-indigo-700"><?= number_format($pctBody, 1) ?>%</span>
                    </div>
                    <div class="text-sm font-extrabold text-indigo-900"><?= number_format($secSeg['zone_word_distribution']['BODY_TEXT'] ?? 0) ?> kata</div>
                    <p class="text-[11px] text-indigo-900/70 leading-tight">Inti Orisinalitas: Bab I, Bab II, Bab IV (Hasil), & Bab V (Saran).</p>
                </div>

                <!-- Zone 3: Methodology -->
                <div class="p-3.5 rounded-xl border border-amber-200 bg-amber-50/40 space-y-1.5">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-amber-700">3. Methodology</span>
                        <span class="text-xs font-mono-code font-bold text-amber-700"><?= number_format($pctMethod, 1) ?>%</span>
                    </div>
                    <div class="text-sm font-extrabold text-amber-900"><?= number_format($secSeg['zone_word_distribution']['METHODOLOGY'] ?? 0) ?> kata</div>
                    <p class="text-[11px] text-amber-900/70 leading-tight">Prosedur Ilmiah: Bab III (Desain, Populasi/Sampel, & Instrumen).</p>
                </div>

                <!-- Zone 4: Back Matter -->
                <div class="p-3.5 rounded-xl border border-teal-200 bg-teal-50/40 space-y-1.5">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-teal-700">4. Back Matter</span>
                        <span class="text-xs font-mono-code font-bold text-teal-700"><?= number_format($pctBack, 1) ?>%</span>
                    </div>
                    <div class="text-sm font-extrabold text-teal-900"><?= number_format($secSeg['zone_word_distribution']['BACK_MATTER'] ?? 0) ?> kata</div>
                    <p class="text-[11px] text-teal-900/70 leading-tight">Penunjang: Daftar Pustaka (Bibliografi) & Lampiran Data.</p>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- ═════════════════════════════════════════════════════════════════════ -->
        <!-- MODUL 2: TERM-LEVEL IDF DRILL-DOWN (EXPLAINABLE TF-IDF)               -->
        <!-- ═════════════════════════════════════════════════════════════════════ -->
        <?php if (!empty($termDrill) && !empty($termDrill['top_contributing_terms'])): ?>
        <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-xs space-y-5">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 pb-3">
                <div class="flex items-center gap-2.5">
                    <span class="text-2xl">🔬</span>
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="font-bold text-base text-slate-900">Transparansi Bobot Kosakata (Term-Level IDF Drill-Down)</h3>
                            <span class="text-[10px] font-bold font-mono-code uppercase px-2 py-0.5 rounded bg-emerald-50 text-emerald-700 border border-emerald-100">Modul 2</span>
                        </div>
                        <p class="text-xs text-slate-400">Dekomposisi kontribusi dot-product setiap istilah bersama terhadap skor kemiripan kosinus (Explainable TF-IDF)</p>
                    </div>
                </div>
                <span class="text-xs font-mono-code font-bold px-3 py-1 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200 shadow-2xs self-start sm:self-auto">
                    <?= count($termDrill['top_contributing_terms']) ?> Istilah Teratas
                </span>
            </div>

            <!-- Narrative Explanation Box -->
            <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 text-xs sm:text-sm text-slate-700 leading-relaxed space-y-2">
                <div class="font-bold text-slate-900 flex items-center gap-1.5 text-xs uppercase tracking-wider font-mono-code">
                    <span>💡</span> Narasi Transparansi Sistem:
                </div>
                <p class="text-slate-700 text-xs sm:text-sm leading-relaxed italic bg-white p-3 rounded-lg border border-slate-200 shadow-2xs">
                    "<?= esc($termDrill['explanation_narrative'] ?? '') ?>"
                </p>
                <div class="flex flex-wrap items-center gap-3 pt-1 text-xs font-mono-code text-slate-500">
                    <span>Kata Khas Disipliner: <strong class="text-purple-700 font-bold"><?= (int)($termDrill['disciplinary_terms_count'] ?? 0) ?></strong></span>
                    <span>•</span>
                    <span>Istilah Bidang Kajian: <strong class="text-blue-700 font-bold"><?= (int)($termDrill['topical_terms_count'] ?? 0) ?></strong></span>
                    <span>•</span>
                    <span>Kosakata Umum: <strong class="text-slate-700 font-bold"><?= (int)($termDrill['common_terms_count'] ?? 0) ?></strong></span>
                    <span>•</span>
                    <span>Kontribusi Bobot Khas: <strong class="text-purple-700 font-bold"><?= number_format($termDrill['disciplinary_contribution_percentage'] ?? 0, 1) ?>%</strong></span>
                </div>
            </div>

            <!-- Table of Top Contributing Terms -->
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border border-slate-200 rounded-lg overflow-hidden min-w-[620px]">
                    <thead class="bg-slate-100 text-slate-700 font-bold border-b border-slate-200 uppercase font-mono-code text-[11px]">
                        <tr>
                            <th class="py-2.5 px-3">Istilah (Term)</th>
                            <th class="py-2.5 px-3 text-center">Frekuensi (Submisi / Rujukan)</th>
                            <th class="py-2.5 px-3 text-right">Bobot IDF</th>
                            <th class="py-2.5 px-3 text-right">Kontribusi Kosinus</th>
                            <th class="py-2.5 px-3 text-center">Tingkat Signifikansi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 font-mono-code">
                        <?php foreach ($termDrill['top_contributing_terms'] as $tc): ?>
                            <?php
                                $tier = $tc['significance_tier'] ?? 'COMMON_VOCABULARY';
                                $tierBadge = match($tier) {
                                    'DISCIPLINARY_KEYWORD' => 'bg-purple-50 text-purple-700 border-purple-200',
                                    'TOPICAL_TERM' => 'bg-blue-50 text-blue-700 border-blue-200',
                                    default => 'bg-slate-100 text-slate-600 border-slate-200'
                                };
                                $tierLabel = match($tier) {
                                    'DISCIPLINARY_KEYWORD' => 'Khas Topik (Disipliner)',
                                    'TOPICAL_TERM' => 'Bidang Kajian',
                                    default => 'Kosakata Umum'
                                };
                            ?>
                            <tr class="hover:bg-slate-50/80 transition-colors">
                                <td class="py-2.5 px-3 font-bold text-slate-900"><?= esc($tc['term']) ?></td>
                                <td class="py-2.5 px-3 text-center text-slate-600"><?= (int)($tc['sub_count'] ?? 0) ?>x / <?= (int)($tc['doc_count'] ?? 0) ?>x</td>
                                <td class="py-2.5 px-3 text-right text-slate-700"><?= number_format($tc['idf'] ?? 0, 3) ?></td>
                                <td class="py-2.5 px-3 text-right font-bold text-emerald-700"><?= number_format($tc['contribution_pct'] ?? 0, 1) ?>%</td>
                                <td class="py-2.5 px-3 text-center">
                                    <span class="inline-block text-[10px] font-semibold px-2 py-0.5 rounded border <?= $tierBadge ?>">
                                        <?= $tierLabel ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <!-- Attention & Significance Profile Banner -->
        <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-xs">
            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-3">Profil Signifikansi & Atensi Akademik</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="flex items-center gap-3 p-3.5 rounded-xl bg-amber-50/70 border border-amber-200">
                    <div class="w-10 h-10 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center font-bold text-lg shadow-2xs">
                        <?= $highAttentionCount ?>
                    </div>
                    <div>
                        <div class="text-sm font-bold text-amber-900">Pasase Berperhatian Tinggi (High-Attention)</div>
                        <div class="text-xs text-amber-700">Frasa unik/spesifik yang jarang muncul di korpus lain (perlu dicek sitasinya).</div>
                    </div>
                </div>
                <div class="flex items-center gap-3 p-3.5 rounded-xl bg-blue-50/70 border border-blue-200">
                    <div class="w-10 h-10 rounded-xl bg-blue-100 text-blue-700 flex items-center justify-center font-bold text-lg shadow-2xs">
                        <?= $commonPassagesCount ?>
                    </div>
                    <div>
                        <div class="text-sm font-bold text-blue-900">Frasa Akademik Umum (Common Phrases)</div>
                        <div class="text-xs text-blue-700">Pola baku ilmiah (misal: "Penelitian ini bertujuan...") yang wajar tersebar di banyak karya tulis.</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Academic Fairness Principle Notice -->
        <div class="p-4 rounded-xl bg-blue-50 border border-blue-200 flex gap-3 items-start text-sm text-blue-900 shadow-2xs">
            <span class="text-xl">ℹ️</span>
            <div>
                <strong>Prinsip Keadilan Akademik AR Tilik:</strong>
                <p class="mt-0.5 text-blue-800 text-xs sm:text-sm leading-relaxed">
                    Sistem AR Tilik menyajikan <em>bukti tekstual berimbang</em>, bukan memberikan sanksi sepihak. <strong>Similarity tinggi &ne; otomatis pelanggaran integritas akademik</strong>. Penelaah wajib meninjau konteks tanda kutip langsung, atribusi nama pengarang, catatan kaki, serta daftar pustaka.
                </p>
            </div>
        </div>

        <!-- Detailed Matched Passages List -->
        <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-xs space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <div>
                    <h3 class="font-bold text-base text-slate-900">Rincian Pasase yang Cocok (Matched Passages Detail)</h3>
                    <p class="text-xs text-slate-400 mt-0.5">Daftar seluruh fragmen kata berurutan yang ditemukan pada submisi.</p>
                </div>
                <span class="text-xs font-mono-code font-bold px-2.5 py-1 rounded bg-slate-100 text-slate-600">
                    <?= count($matches) ?> pasase
                </span>
            </div>

            <?php if (empty($matches)): ?>
                <div class="text-center py-10 text-slate-400">
                    <span class="text-3xl block mb-2">🍃</span>
                    <p class="text-sm font-medium">Tidak ditemukan pasase kata yang identik secara sekuensial.</p>
                    <p class="text-xs mt-1 text-slate-400">Kemiripan hanya bersifat orientasi leksikal/parafrasa luas.</p>
                </div>
            <?php else: ?>
                <div class="space-y-4 max-h-[640px] overflow-y-auto pr-1">
                    <?php foreach ($matches as $idx => $m): ?>
                        <?php
                            $subSnip = $m['submission_snippet'] ?? '';
                            $isQuoteMatch = (str_contains($subSnip, '"') || str_contains($subSnip, '“') || str_contains($subSnip, '”') || ($m['citation_status'] ?? '') === 'QUOTE');
                            $isBpMatch = !empty($m['is_boilerplate']);
                            $isBibMatch = (stripos($subSnip, 'daftar pustaka') !== false || stripos($subSnip, 'references') !== false || stripos($subSnip, 'bibliografi') !== false);
                            $isFrontMatch = (
                                (preg_match('/(?i)\b(?:lembar\s+(?:pengesahan|persetujuan)|pernyataan\s+(?:keaslian|bebas\s+plagiasi)|kata\s+pengantar|daftar\s+(?:isi|tabel|gambar))\b/', $subSnip) === 1) ||
                                (!empty($m['boilerplate_category']) && in_array($m['boilerplate_category'], ['ADMINISTRATIVE_FORMULA', 'LEGAL_CITATION'], true))
                            );
                        ?>
                        <div id="passage-card-<?= $idx ?>" data-match-idx="<?= $idx ?>" data-word-count="<?= (int)$m['word_count'] ?>" data-is-boilerplate="<?= $isBpMatch ? '1' : '0' ?>" data-is-quote="<?= $isQuoteMatch ? '1' : '0' ?>" data-is-biblio="<?= $isBibMatch ? '1' : '0' ?>" data-is-front="<?= $isFrontMatch ? '1' : '0' ?>" class="passage-card border border-slate-200 rounded-xl p-4 bg-slate-50/50 hover:bg-white hover:border-indigo-300 transition-all space-y-3">
                            <div class="flex flex-wrap items-center justify-between gap-2 pb-2 border-b border-slate-200/60">
                                <div class="flex items-center gap-2">
                                    <span class="text-xs font-bold px-2 py-0.5 rounded bg-slate-200 text-slate-700 font-mono-code">#<?= $idx + 1 ?></span>
                                    <span class="text-xs font-bold text-slate-800 truncate max-w-sm">Sumber: <?= esc($m['source_title']) ?></span>
                                    <span id="passage-excluded-badge-<?= $idx ?>" class="hidden text-[10px] font-bold px-2 py-0.5 rounded bg-amber-100 text-amber-800 border border-amber-300 font-mono-code">
                                        Dikecualikan Filter
                                    </span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <button type="button" onclick="openDualReaderModal(<?= (int)$m['source_document_id'] ?>, <?= $idx ?>)" class="text-[11px] font-semibold text-indigo-700 hover:text-indigo-900 bg-indigo-50 hover:bg-indigo-100 px-2.5 py-0.5 rounded border border-indigo-200 flex items-center gap-1 transition cursor-pointer">
                                        <span>📖</span> Layar Ganda
                                    </button>
                                    <?php 
                                        $com = $m['commonness'] ?? 'low';
                                        $comClass = match($com) {
                                            'very_high' => 'bg-amber-100 text-amber-800 border-amber-200',
                                            'high'      => 'bg-amber-50 text-amber-700 border-amber-100',
                                            'medium'    => 'bg-blue-50 text-blue-700 border-blue-100',
                                            default     => 'bg-emerald-50 text-emerald-700 border-emerald-100'
                                        };
                                        $comLabel = match($com) {
                                            'very_high' => 'Frasa Sangat Umum (Klise)',
                                            'high'      => 'Frasa Umum',
                                            'medium'    => 'Frasa Sedang',
                                            default     => 'Frasa Khas (Spesifik)'
                                        };
                                    ?>
                                    <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full border <?= $comClass ?>">
                                        <?= $comLabel ?>
                                    </span>
                                    <span class="text-xs font-mono-code font-bold text-slate-700"><?= $m['word_count'] ?> kata</span>
                                </div>
                            </div>

                            <!-- Snippet Preview -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-xs">
                                <div class="p-3 bg-white rounded-lg border border-slate-200 space-y-1">
                                    <span class="font-bold text-indigo-700 uppercase tracking-wider text-[10px] block">Teks Submisi:</span>
                                    <p class="text-slate-800 leading-relaxed font-mono-code text-[11px] bg-indigo-50/30 p-2 rounded border border-indigo-100">
                                        "<?= esc($m['submission_snippet'] ?? '-') ?>"
                                    </p>
                                    <span class="text-[10px] text-slate-400 block pt-0.5">
                                        Hal. <?= $m['submission_page'] ?? 1 ?> (Kata ke-<?= $m['submission_start'] ?> s/d <?= $m['submission_end'] ?>)
                                    </span>
                                </div>

                                <div class="p-3 bg-white rounded-lg border border-slate-200 space-y-1">
                                    <span class="font-bold text-emerald-700 uppercase tracking-wider text-[10px] block">Teks Sumber:</span>
                                    <p class="text-slate-800 leading-relaxed font-mono-code text-[11px] bg-emerald-50/30 p-2 rounded border border-emerald-100">
                                        "<?= esc($m['source_snippet'] ?? '-') ?>"
                                    </p>
                                    <span class="text-[10px] text-slate-400 block pt-0.5">
                                        Hal. <?= $m['source_page'] ?? 1 ?> (Kata ke-<?= $m['source_start'] ?> s/d <?= $m['source_end'] ?>)
                                    </span>
                                </div>
                            </div>

                            <?php if (!empty($m['is_boilerplate'])): ?>
                                <?php
                                    $bpCat = $m['boilerplate_category'] ?? 'METHODOLOGY_PROCEDURE';
                                    $bpCatLabel = match($bpCat) {
                                        'METHODOLOGY_PROCEDURE' => '📐 Prosedur Metodologi Baku',
                                        'STATISTICAL_ANALYSIS'  => '📊 Analisis Statistik Standar',
                                        'ACADEMIC_TRANSITION'   => '🔗 Frasa Transisi Ilmiah',
                                        'ADMINISTRATIVE_FORMULA'=> '🏛️ Formulasi Administratif Dokumen',
                                        default                 => '📐 Formulasi Ilmiah Baku'
                                    };
                                ?>
                                <div class="p-3 rounded-lg bg-indigo-50/70 border border-indigo-200/80 text-xs space-y-1">
                                    <div class="flex flex-wrap items-center justify-between gap-1.5">
                                        <span class="font-bold text-indigo-900 text-[11px] uppercase tracking-wider flex items-center gap-1.5 font-mono-code">
                                            <span><?= $bpCatLabel ?></span>
                                        </span>
                                        <?php if (!empty($m['boilerplate_phrase'])): ?>
                                            <span class="font-mono-code text-[10px] bg-white text-indigo-700 px-2 py-0.5 rounded border border-indigo-200 font-semibold">
                                                Frasa: "<?= esc($m['boilerplate_phrase']) ?>"
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <p class="text-slate-600 text-[11px] leading-relaxed">
                                        <?= esc($m['boilerplate_rationale'] ?? 'Formulasi kalimat standar keilmuan.') ?>
                                        <span class="text-slate-400 block pt-0.5 italic">&bull; Catatan Keadilan: Konvensi penulisan ilmiah yang lazim, bukan indikasi pelanggaran orisinalitas substantif.</span>
                                    </p>
                                </div>
                            <?php endif; ?>

                            <div class="flex justify-between items-center text-[11px] text-slate-400 pt-1 border-t border-slate-100">
                                <span>Rata-rata IDF: <strong class="text-slate-700 font-mono-code"><?= number_format($m['average_idf'] ?? 0, 3) ?></strong></span>
                                <span>Max IDF: <strong class="text-slate-700 font-mono-code"><?= number_format($m['max_idf'] ?? 0, 3) ?></strong></span>
                                <span>Skor Signifikansi: <strong class="text-indigo-600 font-mono-code"><?= number_format($m['significance'] ?? 0, 2) ?></strong></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

    </main>

    <!-- Modal [View Passage] Dialog -->
    <div id="passageModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs z-50 hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-2xl w-full shadow-2xl border border-slate-200 overflow-hidden flex flex-col max-h-[90vh]">
            <div class="p-5 border-b border-slate-200 flex justify-between items-center bg-slate-50">
                <div class="flex items-center gap-2">
                    <span class="text-lg">🔍</span>
                    <div>
                        <h3 class="font-bold text-slate-900 text-sm" id="modalSourceTitle">Sumber Pasase</h3>
                        <p class="text-[11px] text-slate-400">Perbandingan Teks Submisi vs Dokumen Rujukan</p>
                    </div>
                </div>
                <button onclick="closePassageModal()" class="text-slate-400 hover:text-slate-700 text-lg font-bold p-1">
                    ✕
                </button>
            </div>
            <div class="p-6 overflow-y-auto space-y-4" id="modalPassagesContent">
                <!-- Injected via JavaScript -->
            </div>
            <div class="p-4 border-t border-slate-200 bg-slate-50 flex justify-end">
                <button onclick="closePassageModal()" class="px-4 py-2 bg-slate-800 hover:bg-slate-900 text-white font-semibold text-xs rounded-lg transition">
                    Tutup
                </button>
            </div>
        </div>
    </div>

    <!-- Passages Data for JavaScript Modal -->
    <script>
        const allMatches = <?= json_encode($matches, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;

        function openPassageModal(sourceId, sourceTitle) {
            document.getElementById('modalSourceTitle').innerText = sourceTitle;
            const content = document.getElementById('modalPassagesContent');
            content.innerHTML = '';

            const sourceMatches = allMatches.filter(m => parseInt(m.source_document_id) === parseInt(sourceId));

            if (sourceMatches.length === 0) {
                content.innerHTML = `
                    <div class="text-center py-6 px-4 space-y-3">
                        <div class="w-12 h-12 rounded-full bg-teal-50 border border-teal-200 text-teal-600 flex items-center justify-center mx-auto text-xl shadow-2xs">
                            📚
                        </div>
                        <div class="space-y-1">
                            <h4 class="font-bold text-sm text-slate-900">Kemiripan Topikal / Kosakata Bidang Ilmu (Lexical Match)</h4>
                            <p class="text-xs text-slate-500 max-w-md mx-auto leading-relaxed">
                                Dokumen ini adalah pembanding topikal (TF-IDF Cosine), <strong>bukan salinan sekuensial kata-demi-kata (Verbatim: 0%)</strong>. Kesamaan berasal dari irisan terminologi ilmiah dan konsep bahasan yang sebidang.
                            </p>
                        </div>
                        <div class="pt-3">
                            <button type="button" onclick="closePassageModal(); openDualReaderModal(${sourceId});" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs rounded-xl shadow-xs transition inline-flex items-center gap-1.5 cursor-pointer">
                                <span>📖</span> Buka Penelaahan di Layar Ganda (Dual Reader)
                            </button>
                        </div>
                    </div>
                `;
            } else {
                sourceMatches.forEach((m, idx) => {
                    let bpBanner = '';
                    if (m.is_boilerplate) {
                        const bpCat = (m.boilerplate_category || 'FORMULA').replace(/_/g, ' ');
                        bpBanner = `
                            <div class="mt-2 p-2.5 rounded-lg bg-indigo-50 border border-indigo-200 text-xs space-y-0.5">
                                <div class="font-bold text-indigo-900 text-[11px] uppercase tracking-wider flex items-center gap-1.5 font-mono-code">
                                    <span>📐</span> Formulasi Ilmiah Baku (${bpCat})
                                </div>
                                <div class="text-slate-600 text-[11px] leading-relaxed">
                                    ${m.boilerplate_rationale || 'Konvensi kalimat penulisan ilmiah standar.'}
                                    <span class="text-slate-400 block pt-0.5 italic">&bull; Catatan Keadilan: Konvensi penulisan ilmiah standar, bukan indikasi pelanggaran orisinalitas substantif.</span>
                                </div>
                            </div>
                        `;
                    }

                    const block = document.createElement('div');
                    block.className = 'border border-slate-200 rounded-xl p-4 bg-slate-50 space-y-2';
                    block.innerHTML = `
                        <div class="flex justify-between items-center text-xs pb-1 border-b border-slate-200 font-mono-code">
                            <span class="font-bold text-indigo-700">Pasase #${idx + 1} (${m.word_count} kata)</span>
                            <span class="text-slate-500">Signifikansi: ${parseFloat(m.significance || 0).toFixed(2)}</span>
                        </div>
                        <div class="space-y-1 pt-1">
                            <div class="text-[11px] font-bold text-slate-600">Submisi (Kata ke-${m.submission_start} s/d ${m.submission_end}):</div>
                            <div class="p-2.5 bg-white rounded border border-indigo-200 text-xs font-mono-code text-slate-800 leading-relaxed">
                                "${m.submission_snippet || '-'}"
                            </div>
                        </div>
                        <div class="space-y-1 pt-1">
                            <div class="text-[11px] font-bold text-slate-600">Sumber (Kata ke-${m.source_start} s/d ${m.source_end}):</div>
                            <div class="p-2.5 bg-white rounded border border-emerald-200 text-xs font-mono-code text-slate-800 leading-relaxed">
                                "${m.source_snippet || '-'}"
                            </div>
                        </div>
                        ${bpBanner}
                    `;
                    content.appendChild(block);
                });
            }

            document.getElementById('passageModal').classList.remove('hidden');
        }

        function closePassageModal() {
            document.getElementById('passageModal').classList.add('hidden');
        }

        // ═════════════════════════════════════════════════════════════════════
        // FITUR 1: INTERACTIVE SIDE-BY-SIDE DUAL READER LOGIC
        // ═════════════════════════════════════════════════════════════════════
        const submissionFullText = <?= json_encode($submissionText ?? '', JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
        const sourceTextsMap = <?= json_encode($sourceTexts ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
        const reportSources = <?= json_encode($sources ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
        
        let currentDualMode = 'exact'; // 'exact' (pasase sekuensial) atau 'lexical' (kosakata topikal)
        let currentDualMatches = [];
        let currentDualMatchIndex = 0;
        let currentDualLexicalItems = [];
        let isSyncScrolling = false;

        const INDO_STOP_WORDS = new Set([
            'dan', 'yang', 'di', 'ke', 'dari', 'pada', 'untuk', 'ini', 'itu', 'dengan', 'atau',
            'oleh', 'dalam', 'adalah', 'sebagai', 'akan', 'dapat', 'juga', 'ada', 'tidak',
            'bisa', 'karena', 'hal', 'tersebut', 'bahwa', 'secara', 'ia', 'mereka', 'kita',
            'kami', 'saya', 'dia', 'namun', 'serta', 'bagi', 'antara', 'sudah', 'telah',
            'tentang', 'atas', 'hanya', 'lebih', 'sangat', 'maka', 'bila', 'jika', 'bukan',
            'seperti', 'terhadap', 'menjadi', 'saat', 'ketika', 'para', 'lain', 'lagi',
            '1', '2', '3', '4', '5', '6', '7', '8', '9', '0', 'no', 'hal', 'halaman', 'bab', 'vol',
            'tahun', 'nomor', 'link', 'data', 'tabel', 'prodi', 'pendidikan', 'program', 'studi'
        ]);

        function extractTopSharedTerms(textA, textB, topK = 15) {
            if (!textA || !textB) return [];
            const re = /\b[a-zA-Z]{4,}\b/gu;
            const tokensA = textA.toLowerCase().match(re) || [];
            const tokensB = textB.toLowerCase().match(re) || [];

            const countA = {};
            for (const w of tokensA) {
                if (!INDO_STOP_WORDS.has(w)) countA[w] = (countA[w] || 0) + 1;
            }
            const countB = {};
            for (const w of tokensB) {
                if (!INDO_STOP_WORDS.has(w)) countB[w] = (countB[w] || 0) + 1;
            }

            const shared = [];
            for (const w in countA) {
                if (countB[w]) {
                    shared.push({
                        word: w,
                        score: countA[w] * countB[w],
                        countA: countA[w],
                        countB: countB[w]
                    });
                }
            }
            shared.sort((a, b) => b.score - a.score);
            return shared.slice(0, topK);
        }

        function escapeHtml(text) {
            const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
            return String(text).replace(/[&<>"']/g, m => map[m]);
        }

        function buildHighlightedSequentialHtml(rawText, matchRanges, markClass, idPrefix) {
            if (!rawText) return '<p class="text-slate-400 italic py-8 text-center">Teks tidak tersedia.</p>';
            const tokenRegex = /\b[a-zA-Z0-9]+(?:-[a-zA-Z0-9]+)*\b/gu;
            const tokens = [];
            let match;
            while ((match = tokenRegex.exec(rawText)) !== null) {
                tokens.push({
                    start: match.index,
                    end: match.index + match[0].length,
                    text: match[0]
                });
            }

            if (tokens.length === 0 || matchRanges.length === 0) {
                return `<div class="leading-relaxed whitespace-pre-wrap">${escapeHtml(rawText)}</div>`;
            }

            const charIntervals = [];
            matchRanges.forEach(mr => {
                const wStart = mr.tokenStart;
                const wEnd = mr.tokenEnd;
                if (wStart < tokens.length) {
                    const startChar = tokens[wStart].start;
                    const safeEndToken = Math.min(tokens.length - 1, wEnd);
                    const endChar = tokens[safeEndToken].end;
                    charIntervals.push({
                        startChar,
                        endChar,
                        matchIdx: mr.matchIdx,
                        tooltip: mr.tooltip || `Pasase #${mr.matchIdx + 1}`
                    });
                }
            });

            charIntervals.sort((a, b) => a.startChar - b.startChar);
            const merged = [];
            charIntervals.forEach(curr => {
                if (merged.length === 0) {
                    merged.push({ ...curr });
                } else {
                    const prev = merged[merged.length - 1];
                    if (curr.startChar <= prev.endChar) {
                        prev.endChar = Math.max(prev.endChar, curr.endChar);
                    } else {
                        merged.push({ ...curr });
                    }
                }
            });

            let resultHtml = '';
            let lastIdx = 0;
            merged.forEach(interval => {
                resultHtml += escapeHtml(rawText.substring(lastIdx, interval.startChar));
                const slice = rawText.substring(interval.startChar, interval.endChar);
                resultHtml += `<mark id="${idPrefix}-${interval.matchIdx}" onclick="jumpToDualMatch(${interval.matchIdx})" class="${markClass} cursor-pointer px-1.5 py-0.5 rounded transition-all shadow-2xs font-semibold" title="${escapeHtml(interval.tooltip)}">${escapeHtml(slice)}</mark>`;
                lastIdx = interval.endChar;
            });
            resultHtml += escapeHtml(rawText.substring(lastIdx));
            return `<div class="leading-relaxed whitespace-pre-wrap">${resultHtml}</div>`;
        }

        function buildHighlightedLexicalHtml(rawText, sharedTermsSet, markClass, idPrefix) {
            if (!rawText) return { html: '<p class="text-slate-400 italic py-8 text-center">Teks tidak tersedia.</p>', count: 0 };
            if (!sharedTermsSet || sharedTermsSet.size === 0) {
                return { html: `<div class="leading-relaxed whitespace-pre-wrap">${escapeHtml(rawText)}</div>`, count: 0 };
            }

            const tokenRegex = /\b[a-zA-Z0-9]+(?:-[a-zA-Z0-9]+)*\b/gu;
            let resultHtml = '';
            let lastIdx = 0;
            let match;
            let termOccurrenceIdx = 0;

            while ((match = tokenRegex.exec(rawText)) !== null) {
                const wordLower = match[0].toLowerCase();
                if (sharedTermsSet.has(wordLower)) {
                    resultHtml += escapeHtml(rawText.substring(lastIdx, match.index));
                    resultHtml += `<mark id="${idPrefix}-${termOccurrenceIdx}" data-term="${wordLower}" onclick="jumpToSpecificTerm('${wordLower}')" class="${markClass} cursor-pointer px-1 py-0.5 rounded font-medium border-b transition-all shadow-2xs" title="Kosakata bersama: ${escapeHtml(wordLower)} (${escapeHtml(match[0])})">${escapeHtml(match[0])}</mark>`;
                    lastIdx = match.index + match[0].length;
                    termOccurrenceIdx++;
                }
            }
            resultHtml += escapeHtml(rawText.substring(lastIdx));
            return {
                html: `<div class="leading-relaxed whitespace-pre-wrap">${resultHtml}</div>`,
                count: termOccurrenceIdx
            };
        }

        function openDualReaderModal(preferredSourceId = null, focusMatchIdx = null) {
            const modal = document.getElementById('dualReaderModal');
            const select = document.getElementById('dualSourceSelect');
            select.innerHTML = '';

            if (!reportSources || reportSources.length === 0) {
                alert('Tidak ada dokumen rujukan yang terdeteksi untuk perbandingan layar ganda.');
                return;
            }

            const sourceIdsWithDirectMatches = new Set(allMatches.map(m => parseInt(m.source_document_id)));

            reportSources.forEach((s, idx) => {
                const sId = parseInt(s.source_document_id);
                const hasDirect = sourceIdsWithDirectMatches.has(sId);
                const opt = document.createElement('option');
                opt.value = sId;
                const matchCount = allMatches.filter(m => parseInt(m.source_document_id) === sId).length;
                const badgeLabel = hasDirect ? `[🔍 ${matchCount} Pasase Sekuensial]` : `[📚 Kesepadanan Leksikal]`;
                opt.text = `#${idx + 1} - ${s.source_title} (${parseFloat(s.similarity || s.cosine_similarity || 0).toFixed(1)}%) ${badgeLabel}`;
                if (preferredSourceId && parseInt(preferredSourceId) === sId) {
                    opt.selected = true;
                }
                select.appendChild(opt);
            });

            if (!preferredSourceId && reportSources.length > 0) {
                const firstWithDirect = reportSources.find(s => sourceIdsWithDirectMatches.has(parseInt(s.source_document_id)));
                select.value = firstWithDirect ? firstWithDirect.source_document_id : reportSources[0].source_document_id;
            }

            modal.classList.remove('hidden');
            document.body.classList.add('overflow-hidden');

            renderDualReaderContent();

            if (focusMatchIdx !== null) {
                setTimeout(() => {
                    const matchItemIdx = currentDualMatches.findIndex(m => parseInt(m.match_original_idx) === parseInt(focusMatchIdx));
                    if (matchItemIdx !== -1) {
                        jumpToDualMatch(matchItemIdx);
                    }
                }, 150);
            }
        }

        function closeDualReaderModal() {
            const modal = document.getElementById('dualReaderModal');
            modal.classList.add('hidden');
            document.body.classList.remove('overflow-hidden');
        }

        function switchDualReaderSource(sourceId) {
            renderDualReaderContent();
        }

        function renderDualReaderContent() {
            const select = document.getElementById('dualSourceSelect');
            const selectedSrcId = parseInt(select.value);
            const selectedSourceObj = reportSources.find(s => parseInt(s.source_document_id) === selectedSrcId) || reportSources[0];

            document.getElementById('dualSrcHeaderTitle').innerText = selectedSourceObj ? selectedSourceObj.source_title : 'Dokumen Rujukan';

            // Filter pasase sekuensial langsung
            currentDualMatches = allMatches
                .map((m, originalIdx) => ({ ...m, match_original_idx: originalIdx }))
                .filter(m => parseInt(m.source_document_id) === selectedSrcId);

            currentDualMatchIndex = 0;

            const bannerEl = document.getElementById('dualReaderModeBanner');
            const legendEl = document.getElementById('dualReaderLegend');
            const subPane = document.getElementById('dualSubPane');
            const srcPane = document.getElementById('dualSrcPane');
            const srcFullText = sourceTextsMap[selectedSrcId] || '';

            const subWords = submissionFullText ? submissionFullText.split(/\s+/) : [];
            const srcWords = srcFullText ? srcFullText.split(/\s+/) : [];

            if (currentDualMatches.length > 0) {
                // ═════════════════════════════════════════════════════════════
                // MODE 1: BUKTI SEKUENSIAL LANGSUNG (EXACT OVERLAP)
                // ═════════════════════════════════════════════════════════════
                currentDualMode = 'exact';
                updateDualMatchNavCounter();

                const simVal = parseFloat(selectedSourceObj ? (selectedSourceObj.exact_similarity || selectedSourceObj.similarity || 0) : 0).toFixed(1);

                if (bannerEl) {
                    bannerEl.className = 'px-5 py-2.5 bg-indigo-50/90 border-b border-indigo-200 flex flex-wrap items-center justify-between gap-3 text-xs';
                    bannerEl.innerHTML = `
                        <div class="flex items-center gap-2">
                            <span class="text-lg">🔍</span>
                            <div>
                                <div class="font-bold text-indigo-950 font-mono-code uppercase flex items-center gap-2">
                                    <span>Mode Bukti Sekuensial Langsung (Exact Overlap: ${simVal}%)</span>
                                    <span class="text-[10px] px-2 py-0.5 rounded bg-indigo-200 text-indigo-900 font-bold">${currentDualMatches.length} Pasase Cocok</span>
                                </div>
                                <p class="text-[11px] text-indigo-800 leading-snug pt-0.5">
                                    Ditemukan pasase kata-demi-kata berurutan. Klik pasase tersorot atau gunakan tombol navigasi (◄ ►) untuk menyinkronkan posisi kedua dokumen.
                                </p>
                            </div>
                        </div>
                    `;
                }

                if (legendEl) {
                    legendEl.innerHTML = `
                        <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-amber-200 border border-amber-400 inline-block"></span> Teks Submisi Cocok</span>
                        <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-indigo-200 border border-indigo-400 inline-block"></span> Teks Sumber Cocok</span>
                        <span class="hidden sm:inline text-slate-400">• Klik pasase tersorot untuk menyinkronkan posisi kedua dokumen</span>
                    `;
                }

                document.getElementById('dualSubInfoText').innerText = `${subWords.length} kata • ${currentDualMatches.length} pasase cocok`;
                document.getElementById('dualSrcInfoText').innerText = `${srcWords.length} kata • ${currentDualMatches.length} pasase cocok`;

                // Highlight Submisi
                const subRanges = currentDualMatches.map((m, idx) => ({
                    tokenStart: parseInt(m.submission_start),
                    tokenEnd: parseInt(m.submission_end),
                    matchIdx: idx,
                    tooltip: `Pasase #${idx + 1} (${m.word_count} kata)`
                }));
                subPane.innerHTML = buildHighlightedSequentialHtml(
                    submissionFullText,
                    subRanges,
                    'bg-amber-200/95 hover:bg-amber-300 text-amber-950 border-b-2 border-amber-500',
                    'dual-sub-m'
                );

                // Highlight Sumber
                const srcRanges = currentDualMatches.map((m, idx) => ({
                    tokenStart: parseInt(m.source_start),
                    tokenEnd: parseInt(m.source_end),
                    matchIdx: idx,
                    tooltip: `Pasase Rujukan #${idx + 1} (${m.word_count} kata)`
                }));
                srcPane.innerHTML = buildHighlightedSequentialHtml(
                    srcFullText,
                    srcRanges,
                    'bg-indigo-200/95 hover:bg-indigo-300 text-indigo-950 border-b-2 border-indigo-600',
                    'dual-src-m'
                );

            } else {
                // ═════════════════════════════════════════════════════════════
                // MODE 2: KESEPADANAN LEKSIKAL & TOPIKAL (TF-IDF SIMILARITY)
                // ═════════════════════════════════════════════════════════════
                currentDualMode = 'lexical';

                const simVal = parseFloat(selectedSourceObj ? (selectedSourceObj.cosine_similarity || selectedSourceObj.similarity || 0) : 0).toFixed(1);
                const matchedWords = selectedSourceObj ? parseInt(selectedSourceObj.matched_words || 0) : 0;

                // Hitung kosakata substantif bersama teratas
                const topSharedTerms = extractTopSharedTerms(submissionFullText, srcFullText, 12);
                const sharedTermsSet = new Set(topSharedTerms.map(t => t.word));

                // Buat chips kosakata interaktif
                let chipsHtml = '';
                topSharedTerms.forEach(item => {
                    chipsHtml += `
                        <button type="button" onclick="jumpToSpecificTerm('${item.word}')" class="px-2 py-0.5 rounded-full bg-teal-100 hover:bg-teal-200 text-teal-900 border border-teal-300 text-[10px] font-bold font-mono-code transition cursor-pointer" title="Submisi: ${item.countA}x | Sumber: ${item.countB}x">
                            ${escapeHtml(item.word)} <span class="text-teal-600 text-[9px]">(${item.countA}x/${item.countB}x)</span>
                        </button>
                    `;
                });

                if (bannerEl) {
                    bannerEl.className = 'px-5 py-2.5 bg-teal-50/90 border-b border-teal-200 flex flex-col md:flex-row md:items-center justify-between gap-2.5 text-xs';
                    bannerEl.innerHTML = `
                        <div class="flex items-center gap-2.5">
                            <span class="text-lg">📚</span>
                            <div>
                                <div class="font-bold text-teal-950 font-mono-code uppercase flex items-center gap-2">
                                    <span>Mode Kesepadanan Topikal & Kosakata (TF-IDF: ${simVal}%)</span>
                                    <span class="text-[10px] px-2 py-0.5 rounded bg-teal-200 text-teal-900 font-bold">Verbatim: 0%</span>
                                </div>
                                <p class="text-[11px] text-teal-800 leading-snug pt-0.5">
                                    Kedua naskah berbagi orientasi bidang ilmu dan kosakata tematik (${matchedWords} kata bersama), bukan salinan sekuensial kata-demi-kata. Kosakata tematik bersama ditandai dengan warna toska di bawah.
                                </p>
                            </div>
                        </div>
                        <div class="flex items-center gap-1.5 flex-wrap pt-1 md:pt-0">
                            <span class="text-[10px] font-bold text-teal-900 uppercase font-mono-code">Kata Kunci:</span>
                            ${chipsHtml || '<span class="text-slate-400 italic text-[11px]">Tidak ada kata kunci dominan</span>'}
                        </div>
                    `;
                }

                if (legendEl) {
                    legendEl.innerHTML = `
                        <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-teal-200 border border-teal-400 inline-block"></span> Kosakata Tematik Bersama (Topikal)</span>
                        <span class="hidden sm:inline text-slate-400">• Klik kata kunci di atas atau kata tersorot untuk menelusuri persebaran konsep</span>
                    `;
                }

                // Highlight Submisi
                const subRes = buildHighlightedLexicalHtml(
                    submissionFullText,
                    sharedTermsSet,
                    'bg-teal-200/90 hover:bg-teal-300 text-teal-950 border-b-2 border-teal-500',
                    'dual-sub-term'
                );
                subPane.innerHTML = subRes.html;

                // Highlight Sumber
                const srcRes = buildHighlightedLexicalHtml(
                    srcFullText,
                    sharedTermsSet,
                    'bg-teal-200/90 hover:bg-teal-300 text-teal-950 border-b-2 border-teal-500',
                    'dual-src-term'
                );
                srcPane.innerHTML = srcRes.html;

                currentDualLexicalItems = [];
                const maxTermCount = Math.max(subRes.count, srcRes.count);
                for (let i = 0; i < maxTermCount; i++) {
                    currentDualLexicalItems.push(i);
                }

                updateDualMatchNavCounter();

                document.getElementById('dualSubInfoText').innerText = `${subWords.length} kata • ${topSharedTerms.length} kata kunci (${subRes.count} kemunculan)`;
                document.getElementById('dualSrcInfoText').innerText = `${srcWords.length} kata • ${topSharedTerms.length} kata kunci (${srcRes.count} kemunculan)`;
            }

            // Setup Sinkronisasi Gulir (Sync-Scroll)
            setupDualSyncScroll();
        }

        function updateDualMatchNavCounter() {
            const counter = document.getElementById('dualMatchNavCounter');
            if (currentDualMode === 'exact') {
                if (currentDualMatches.length === 0) {
                    counter.innerText = '0 / 0';
                } else {
                    counter.innerText = `${currentDualMatchIndex + 1} / ${currentDualMatches.length}`;
                }
            } else {
                if (currentDualLexicalItems.length === 0) {
                    counter.innerText = '0 / 0';
                } else {
                    counter.innerText = `${currentDualMatchIndex + 1} / ${currentDualLexicalItems.length}`;
                }
            }
        }

        function navigateDualMatch(direction) {
            const total = (currentDualMode === 'exact') ? currentDualMatches.length : currentDualLexicalItems.length;
            if (total === 0) return;
            currentDualMatchIndex = (currentDualMatchIndex + direction + total) % total;
            jumpToDualMatch(currentDualMatchIndex);
        }

        function jumpToDualMatch(matchIndex) {
            currentDualMatchIndex = matchIndex;
            updateDualMatchNavCounter();

            // Lepas highlight aktif sebelumnya
            document.querySelectorAll('.dual-active-focus').forEach(el => {
                el.classList.remove('dual-active-focus', 'ring-4', 'ring-indigo-500', 'ring-teal-500', 'ring-offset-2', 'z-20');
            });

            const ringColor = (currentDualMode === 'exact') ? 'ring-indigo-500' : 'ring-teal-500';
            const subId = (currentDualMode === 'exact') ? `dual-sub-m-${matchIndex}` : `dual-sub-term-${matchIndex}`;
            const srcId = (currentDualMode === 'exact') ? `dual-src-m-${matchIndex}` : `dual-src-term-${matchIndex}`;

            const subTarget = document.getElementById(subId);
            const srcTarget = document.getElementById(srcId);

            if (subTarget) {
                subTarget.classList.add('dual-active-focus', 'ring-4', ringColor, 'ring-offset-2', 'z-20');
                subTarget.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }

            if (srcTarget) {
                srcTarget.classList.add('dual-active-focus', 'ring-4', ringColor, 'ring-offset-2', 'z-20');
                srcTarget.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }

        function jumpToSpecificTerm(termWord) {
            const termLower = termWord.toLowerCase();
            document.querySelectorAll('.dual-active-focus').forEach(el => {
                el.classList.remove('dual-active-focus', 'ring-4', 'ring-indigo-500', 'ring-teal-500', 'ring-offset-2', 'z-20');
            });

            const subTarget = document.querySelector(`#dualSubPane mark[data-term="${termLower}"]`);
            const srcTarget = document.querySelector(`#dualSrcPane mark[data-term="${termLower}"]`);

            if (subTarget) {
                subTarget.classList.add('dual-active-focus', 'ring-4', 'ring-teal-500', 'ring-offset-2', 'z-20');
                subTarget.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }

            if (srcTarget) {
                srcTarget.classList.add('dual-active-focus', 'ring-4', 'ring-teal-500', 'ring-offset-2', 'z-20');
                srcTarget.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }

        // ═════════════════════════════════════════════════════════════════════
        // FITUR 1: LIVE EXCLUSION FILTERS & DYNAMIC SCORE RECALCULATION
        // ═════════════════════════════════════════════════════════════════════
        const rawOverallScore = <?= (float)$report['overall_similarity'] ?>;

        function recalculateLiveScore() {
            const excludeQuotes = document.getElementById('toggleExcludeQuotes').checked;
            const excludeBoilerplate = document.getElementById('toggleExcludeBoilerplate').checked;
            const excludeFrontMatter = document.getElementById('toggleExcludeFrontMatter').checked;
            const excludeBiblio = document.getElementById('toggleExcludeBibliography').checked;

            const cards = document.querySelectorAll('.passage-card');
            let totalMatchedWords = 0;
            let excludedWords = 0;
            let excludedCount = 0;

            cards.forEach(card => {
                const wCount = parseInt(card.getAttribute('data-word-count') || 0);
                const isBp = card.getAttribute('data-is-boilerplate') === '1';
                const isQuote = card.getAttribute('data-is-quote') === '1';
                const isFront = card.getAttribute('data-is-front') === '1';
                const isBib = card.getAttribute('data-is-biblio') === '1';
                const idx = card.getAttribute('data-match-idx');
                const badge = document.getElementById(`passage-excluded-badge-${idx}`);

                totalMatchedWords += wCount;

                let shouldExclude = false;
                let reasons = [];

                if (excludeQuotes && isQuote) {
                    shouldExclude = true;
                    reasons.push('Kutipan');
                }
                if (excludeBoilerplate && isBp) {
                    shouldExclude = true;
                    reasons.push('Frasa Baku');
                }
                if (excludeFrontMatter && isFront) {
                    shouldExclude = true;
                    reasons.push('Formalitas Depan');
                }
                if (excludeBiblio && isBib) {
                    shouldExclude = true;
                    reasons.push('Bibliografi');
                }

                if (shouldExclude) {
                    excludedWords += wCount;
                    excludedCount++;
                    card.classList.add('opacity-40', 'bg-slate-100', 'border-dashed');
                    card.classList.remove('hover:bg-white', 'hover:border-indigo-300');
                    if (badge) {
                        badge.classList.remove('hidden');
                        badge.innerText = `Dikecualikan Filter (${reasons.join(', ')})`;
                    }
                } else {
                    card.classList.remove('opacity-40', 'bg-slate-100', 'border-dashed');
                    card.classList.add('hover:bg-white', 'hover:border-indigo-300');
                    if (badge) badge.classList.add('hidden');
                }
            });

            // Hitung skor kemiripan murni (Adjusted Score) secara proporsional
            let adjustedScore = rawOverallScore;
            if (totalMatchedWords > 0) {
                const activeWords = Math.max(0, totalMatchedWords - excludedWords);
                adjustedScore = (activeWords / totalMatchedWords) * rawOverallScore;
            } else if (excludedWords > 0) {
                adjustedScore = 0.0;
            }
            adjustedScore = Math.max(0, Math.min(rawOverallScore, adjustedScore));

            const displayEl = document.getElementById('liveAdjustedScoreDisplay');
            const activeBadge = document.getElementById('filterActiveBadge');
            const statusNotice = document.getElementById('filterExcludedCountText');

            displayEl.innerText = adjustedScore.toFixed(2) + '%';

            // Animasi warna badge skor
            displayEl.className = 'text-xl sm:text-2xl font-black font-mono-code transition-all duration-300 ' + 
                (adjustedScore >= 70 ? 'text-rose-400' : (adjustedScore >= 10 ? 'text-amber-300' : 'text-emerald-300'));

            if (excludeQuotes || excludeBoilerplate || excludeFrontMatter || excludeBiblio) {
                activeBadge.classList.remove('hidden');
                statusNotice.innerHTML = `Filter aktif: <strong class="text-indigo-200">${excludedCount} pasase (${excludedWords} kata)</strong> dikecualikan dari perhitungan murni.`;
            } else {
                activeBadge.classList.add('hidden');
                statusNotice.innerText = 'Semua pasase aktif (0 kata dikecualikan).';
            }
        }

        function resetLiveFilters() {
            document.getElementById('toggleExcludeQuotes').checked = false;
            document.getElementById('toggleExcludeBoilerplate').checked = false;
            document.getElementById('toggleExcludeFrontMatter').checked = false;
            document.getElementById('toggleExcludeBibliography').checked = false;
            recalculateLiveScore();
        }

        // Page Transition Handling
        function startPageTransition() {
            const bar = document.getElementById('topProgressBar');
            const overlay = document.getElementById('pageTransitionOverlay');
            if (bar) bar.style.width = '70%';
            if (overlay) {
                overlay.classList.remove('pointer-events-none', 'opacity-0');
                overlay.classList.add('opacity-100');
            }
        }

        document.addEventListener('click', function(e) {
            const link = e.target.closest('a');
            if (link && link.href && !link.target && !link.href.startsWith('javascript:') && !link.href.includes('#')) {
                const currentOrigin = window.location.origin;
                if (link.href.startsWith(currentOrigin)) {
                    startPageTransition();
                }
            }
        });

        // Enroll as Institutional Corpus
        async function enrollDocumentAsCorpus(docId) {
            if (!confirm('Apakah Anda yakin ingin mendaftarkan naskah ini ke dalam Korpus Institusi permanen? Naskah ini akan menjadi basis pembanding bagi pemeriksaan selanjutnya.')) {
                return;
            }

            const btn = document.getElementById('btnEnrollCorpus');
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = `
                    <svg class="animate-spin h-3.5 w-3.5 text-emerald-700" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span>Mendaftarkan...</span>
                `;
            }

            try {
                const res = await fetch(`<?= base_url('api/documents') ?>/${docId}/enroll`, {
                    method: 'POST',
                    headers: { 'Accept': 'application/json' }
                });
                const data = await res.json();
                if (res.ok && data.status === 'success') {
                    if (btn) {
                        btn.outerHTML = `
                            <span class="text-xs font-semibold text-emerald-700 bg-emerald-50 border border-emerald-200 flex items-center gap-1.5 px-3 py-1.5 rounded-lg shadow-xs">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                <span>Korpus Institusi</span>
                            </span>
                        `;
                    }
                    alert(data.message || 'Naskah berhasil didaftarkan sebagai Korpus Institusi!');
                } else if (data.status === 'already_enrolled') {
                    alert(data.message);
                } else {
                    alert('Gagal: ' + (data.message || 'Terjadi kesalahan sistem.'));
                    if (btn) btn.disabled = false;
                }
            } catch (err) {
                alert('Kendala koneksi ke server: ' + err.message);
                if (btn) btn.disabled = false;
            }
        }
    </script>

    <!-- ═════════════════════════════════════════════════════════════════════ -->
    <!-- INTERACTIVE SIDE-BY-SIDE DUAL READER MODAL (FITUR 1)                  -->
    <!-- ═════════════════════════════════════════════════════════════════════ -->
    <div id="dualReaderModal" class="fixed inset-0 bg-slate-950/85 backdrop-blur-md z-50 hidden flex flex-col p-2 sm:p-4 transition-opacity duration-200">
        <div class="bg-white rounded-2xl w-full h-full shadow-2xl border border-slate-200 flex flex-col overflow-hidden">
            <!-- Modal Header -->
            <div class="px-5 py-3.5 border-b border-slate-200 bg-slate-900 text-white flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <span class="text-xl">📖</span>
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="font-bold text-sm sm:text-base text-white tracking-wide">
                                Interactive Side-by-Side Dual Reader
                            </h3>
                            <span class="text-[10px] uppercase font-bold font-mono-code px-2 py-0.5 rounded bg-indigo-500/30 text-indigo-200 border border-indigo-400/30">
                                Sync-Scroll Active
                            </span>
                        </div>
                        <p class="text-[11px] text-slate-400">Komparasi naskah mahasiswa vs dokumen rujukan secara sinkron berdampingan</p>
                    </div>
                </div>

                <!-- Controls in Header -->
                <div class="flex flex-wrap items-center gap-3 text-xs">
                    <!-- Source Selector -->
                    <div class="flex items-center gap-1.5 bg-slate-800 px-2.5 py-1.5 rounded-lg border border-slate-700">
                        <label for="dualSourceSelect" class="text-slate-400 font-medium text-[11px]">Rujukan:</label>
                        <select id="dualSourceSelect" onchange="switchDualReaderSource(this.value)" class="bg-slate-900 text-indigo-200 font-bold text-xs rounded border border-slate-700 py-0.5 px-2 focus:ring-1 focus:ring-indigo-400 focus:outline-none">
                            <!-- Injected by JavaScript -->
                        </select>
                    </div>

                    <!-- Sync-Scroll Switch -->
                    <label class="flex items-center gap-1.5 bg-slate-800 px-2.5 py-1.5 rounded-lg border border-slate-700 cursor-pointer select-none">
                        <input type="checkbox" id="dualSyncToggle" checked class="w-3.5 h-3.5 rounded text-indigo-500 focus:ring-indigo-400 bg-slate-900 border-slate-700 cursor-pointer">
                        <span class="text-slate-300 text-[11px]">Sync-Scroll</span>
                    </label>

                    <!-- Match Navigator -->
                    <div class="flex items-center gap-1 bg-slate-800 px-2 py-1 rounded-lg border border-slate-700 font-mono-code text-[11px]">
                        <button type="button" onclick="navigateDualMatch(-1)" class="px-2 py-0.5 hover:bg-slate-700 rounded text-slate-300 hover:text-white transition font-bold cursor-pointer" title="Pasase Sebelumnya">◄</button>
                        <span id="dualMatchNavCounter" class="px-2 text-indigo-300 font-semibold">0 / 0</span>
                        <button type="button" onclick="navigateDualMatch(1)" class="px-2 py-0.5 hover:bg-slate-700 rounded text-slate-300 hover:text-white transition font-bold cursor-pointer" title="Pasase Berikutnya">►</button>
                    </div>

                    <!-- Close Button -->
                    <button type="button" onclick="closeDualReaderModal()" class="w-8 h-8 rounded-lg bg-slate-800 hover:bg-rose-900/60 text-slate-400 hover:text-rose-300 border border-slate-700 flex items-center justify-center transition font-bold text-base cursor-pointer">
                        ✕
                    </button>
                </div>
            </div>

            <!-- Mode Banner (Sequential Overlap vs Topical Lexical Overlap) -->
            <div id="dualReaderModeBanner" class="hidden"></div>

            <!-- Modal Body: 2 Columns Split Screen -->
            <div class="flex-1 grid grid-cols-1 md:grid-cols-2 divide-y md:divide-y-0 md:divide-x divide-slate-200 min-h-0 bg-slate-100">
                <!-- Left Column: Naskah Mahasiswa (Submisi) -->
                <div class="flex flex-col min-h-0 bg-white">
                    <div class="px-5 py-2.5 bg-indigo-50/70 border-b border-indigo-100 flex items-center justify-between text-xs">
                        <div class="flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span>
                            <span class="font-bold text-indigo-950 uppercase tracking-wider font-mono-code">Naskah Mahasiswa (Submisi)</span>
                        </div>
                        <span class="text-[11px] text-slate-500 font-mono-code" id="dualSubInfoText"></span>
                    </div>
                    <div id="dualSubPane" class="flex-1 overflow-y-auto p-6 text-sm text-slate-800 leading-relaxed font-serif select-text scroll-smooth space-y-3">
                        <!-- Full text with highlight marks -->
                    </div>
                </div>

                <!-- Right Column: Dokumen Rujukan (Sumber) -->
                <div class="flex flex-col min-h-0 bg-white">
                    <div class="px-5 py-2.5 bg-slate-50 border-b border-slate-200 flex items-center justify-between text-xs">
                        <div class="flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-indigo-600"></span>
                            <span class="font-bold text-slate-900 uppercase tracking-wider font-mono-code" id="dualSrcHeaderTitle">Dokumen Rujukan</span>
                        </div>
                        <span class="text-[11px] text-slate-500 font-mono-code" id="dualSrcInfoText"></span>
                    </div>
                    <div id="dualSrcPane" class="flex-1 overflow-y-auto p-6 text-sm text-slate-800 leading-relaxed font-serif select-text scroll-smooth space-y-3">
                        <!-- Full text with highlight marks -->
                    </div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="px-5 py-2.5 border-t border-slate-200 bg-slate-50 flex items-center justify-between text-xs text-slate-500">
                <div class="flex items-center gap-3" id="dualReaderLegend">
                    <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-amber-200 border border-amber-400 inline-block"></span> Teks Submisi Cocok</span>
                    <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-indigo-200 border border-indigo-400 inline-block"></span> Teks Sumber Cocok</span>
                    <span class="hidden sm:inline text-slate-400">• Klik pada teks tersorot untuk menyinkronkan posisi kedua dokumen</span>
                </div>
                <button type="button" onclick="closeDualReaderModal()" class="px-4 py-1.5 bg-slate-800 hover:bg-slate-900 text-white font-semibold text-xs rounded-lg transition cursor-pointer">
                    Tutup Layar Ganda
                </button>
            </div>
        </div>
    </div>
</body>
</html>
