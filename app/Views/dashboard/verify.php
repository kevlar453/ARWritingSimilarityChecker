<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $status === 'valid' ? 'Verifikasi Sertifikat #' . esc($code) . ' — AR Tilik' : 'Verifikasi Sertifikat Resmi — AR Tilik | Portal Validasi QR Ilmiah' ?></title>
    <meta name="description" content="Portal Verifikasi Resmi Sertifikat Keaslian Hasil Telaah Kesepadanan Naskah AR Tilik. Validasi kode QR kriptografis dan tanda tangan digital keabsahan karya ilmiah.">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="<?= base_url('verify' . (!empty($code) ? '/' . esc($code) : '')) ?>">
    <meta name="theme-color" content="#4338ca">

    <!-- Open Graph -->
    <meta property="og:type" content="website">
    <meta property="og:locale" content="id_ID">
    <meta property="og:site_name" content="AR Tilik">
    <meta property="og:title" content="<?= $status === 'valid' ? 'Verifikasi Sertifikat #' . esc($code) . ' — AR Tilik' : 'Verifikasi Sertifikat Resmi — AR Tilik' ?>">
    <meta property="og:description" content="Portal verifikasi keaslian sertifikat digital telaah kesepadanan naskah akademik ber-QR Code resmi.">
    <meta property="og:url" content="<?= base_url('verify' . (!empty($code) ? '/' . esc($code) : '')) ?>">
    <meta property="og:image" content="<?= base_url('images/og-artilik.png') ?>">

    <!-- Twitter Cards -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Portal Verifikasi Sertifikat — AR Tilik">
    <meta name="twitter:description" content="Validasi sertifikat digital orisinalitas naskah karya ilmiah AR Tilik.">
    <meta name="twitter:image" content="<?= base_url('images/og-artilik.png') ?>">

    <!-- Favicon & Brand Icons -->
    <link rel="icon" type="image/x-icon" href="<?= base_url('favicon.ico?v=20261008') ?>">
    <link rel="icon" type="image/png" sizes="32x32" href="<?= base_url('favicon-32x32.png?v=20261008') ?>">
    <link rel="icon" type="image/png" sizes="16x16" href="<?= base_url('favicon-16x16.png?v=20261008') ?>">
    <link rel="apple-touch-icon" sizes="180x180" href="<?= base_url('apple-touch-icon.png?v=20261008') ?>">
    <link rel="shortcut icon" type="image/x-icon" href="<?= base_url('favicon.ico?v=20261008') ?>">
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
        }
        .font-mono-code {
            font-family: 'JetBrains Mono', monospace;
        }
    </style>
</head>
<body class="bg-slate-50 text-slate-800 antialiased min-h-screen flex flex-col justify-between selection:bg-indigo-500 selection:text-white">

    <!-- Top Navigation -->
    <header class="bg-white border-b border-slate-200 sticky top-0 z-50 shadow-xs">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 h-16 flex items-center justify-between">
            <a href="<?= base_url() ?>" class="flex items-center gap-2.5 hover:opacity-90 transition group">
                <img src="<?= base_url('images/logo.png') ?>" alt="AR Tilik Logo" class="w-9 h-9 object-contain drop-shadow-xs group-hover:scale-105 transition-transform duration-200">
                <div class="flex items-center gap-2">
                    <span class="font-bold text-lg tracking-tight bg-gradient-to-r from-indigo-700 to-indigo-500 bg-clip-text text-transparent">AR Tilik</span>
                    <span class="hidden sm:inline-block text-[10px] font-bold uppercase tracking-wider text-indigo-700 bg-indigo-50 px-2.5 py-0.5 rounded-full border border-indigo-200">
                        Official Verification Portal
                    </span>
                </div>
            </a>
            <div class="flex items-center gap-3">
                <a href="/" class="text-xs font-semibold text-slate-600 hover:text-indigo-600 transition flex items-center gap-1">
                    <span>← Beranda</span>
                </a>
            </div>
        </div>
    </header>

    <!-- Main Container -->
    <main class="max-w-4xl w-full mx-auto px-4 sm:px-6 py-8 sm:py-12 flex-1">
        <?php if ($status === 'valid' && $report): ?>
            <?php
                $overallSim = (float)($report['overall_similarity'] ?? 0);
                $exactSim = (float)($report['exact_similarity'] ?? 0);
                $cosineSim = (float)($report['cosine_similarity'] ?? 0);
                $longestRun = (int)($report['longest_match_words'] ?? 0);
                $dec = strtolower($report['predicted_decision'] ?? 'review');
                $dateFormatted = date('d F Y, H:i', strtotime($report['created_at'])) . ' WIB';

                if ($dec === 'match') {
                    $badgeBg = 'bg-rose-50 border-rose-200 text-rose-700';
                    $badgeDot = 'bg-rose-500';
                    $decLabel = 'MATCH (Kemiripan Harfiah Kuat)';
                } elseif ($dec === 'review') {
                    $badgeBg = 'bg-amber-50 border-amber-200 text-amber-700';
                    $badgeDot = 'bg-amber-500';
                    $decLabel = 'REVIEW (Perlu Tinjauan Akademik)';
                } else {
                    $badgeBg = 'bg-emerald-50 border-emerald-200 text-emerald-700';
                    $badgeDot = 'bg-emerald-500';
                    $decLabel = 'NO MATCH (Bebas Indikasi Signifikan)';
                }
            ?>

            <!-- Success Card -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden mb-6">
                <!-- Certificate Header Banner -->
                <div class="bg-gradient-to-r from-emerald-900 via-teal-900 to-slate-900 p-6 sm:p-8 text-white relative">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div class="flex items-center gap-3.5">
                            <div class="w-12 h-12 rounded-2xl bg-emerald-500/20 border border-emerald-400/40 flex items-center justify-center text-2xl flex-shrink-0">
                                🛡️
                            </div>
                            <div>
                                <div class="flex items-center gap-2">
                                    <span class="text-xs font-bold uppercase tracking-wider text-emerald-300">Portail Verifikasi Resmi</span>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-400/20 text-emerald-200 border border-emerald-300/30">
                                        TERVERIFIKASI ASLI
                                    </span>
                                </div>
                                <h1 class="text-lg sm:text-2xl font-black tracking-tight text-white mt-0.5">
                                    Sertifikat Uji Keaslian Teks Akademik
                                </h1>
                            </div>
                        </div>
                        <div class="text-left sm:text-right bg-white/10 backdrop-blur-md px-4 py-2.5 rounded-xl border border-white/15">
                            <div class="text-[10px] uppercase font-bold text-slate-300">Nomor Sertifikat</div>
                            <div class="text-base sm:text-lg font-black font-mono-code text-white tracking-wider">
                                <?= esc($code) ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Document Details -->
                <div class="p-6 sm:p-8 space-y-6">
                    <div>
                        <div class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Judul Karya Ilmiah</div>
                        <h2 class="text-base sm:text-lg font-bold text-slate-900 leading-snug">
                            <?= esc($document['title'] ?? 'Dokumen Karya Ilmiah') ?>
                        </h2>
                    </div>

                    <!-- Metadata Grid -->
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 p-4 rounded-xl bg-slate-50 border border-slate-200 text-xs">
                        <div>
                            <div class="text-slate-400 font-medium">Nama Berkas Asli:</div>
                            <div class="font-bold text-slate-700 truncate mt-0.5"><?= esc($document['original_filename'] ?? '-') ?></div>
                        </div>
                        <div>
                            <div class="text-slate-400 font-medium">Waktu Uji Sistem:</div>
                            <div class="font-bold text-slate-700 mt-0.5"><?= esc($dateFormatted) ?></div>
                        </div>
                        <div>
                            <div class="text-slate-400 font-medium">Nomor Laporan:</div>
                            <div class="font-bold font-mono-code text-indigo-600 mt-0.5">Report #<?= esc($report['id']) ?></div>
                        </div>
                    </div>

                    <!-- Similarity Scores Breakdown -->
                    <div class="space-y-3">
                        <div class="text-xs font-bold text-slate-400 uppercase tracking-wider">Dekomposisi Hasil Uji Kemiripan</div>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 sm:gap-4">
                            <!-- Overall -->
                            <div class="p-4 rounded-xl bg-indigo-50/70 border border-indigo-200 text-center">
                                <div class="text-[10px] font-bold uppercase tracking-wider text-indigo-600">Skor Gabungan</div>
                                <div class="text-2xl sm:text-3xl font-black font-mono-code text-indigo-950 mt-1">
                                    <?= number_format($overallSim, 2) ?>%
                                </div>
                                <div class="text-[10px] text-indigo-600/80 mt-0.5">Hybrid Engine</div>
                            </div>

                            <!-- Sequential -->
                            <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 text-center">
                                <div class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Sekuensial Harfiah</div>
                                <div class="text-2xl sm:text-3xl font-black font-mono-code text-slate-800 mt-1">
                                    <?= number_format($exactSim, 2) ?>%
                                </div>
                                <div class="text-[10px] text-slate-400 mt-0.5">Exact Match Token</div>
                            </div>

                            <!-- Lexical -->
                            <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 text-center">
                                <div class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Leksikal / Topik</div>
                                <div class="text-2xl sm:text-3xl font-black font-mono-code text-slate-800 mt-1">
                                    <?= number_format($cosineSim, 2) ?>%
                                </div>
                                <div class="text-[10px] text-slate-400 mt-0.5">TF-IDF Cosine</div>
                            </div>

                            <!-- Longest Run -->
                            <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 text-center">
                                <div class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Run Terpanjang</div>
                                <div class="text-2xl sm:text-3xl font-black font-mono-code text-slate-800 mt-1">
                                    <?= $longestRun ?>
                                </div>
                                <div class="text-[10px] text-slate-400 mt-0.5">Kata Berurutan</div>
                            </div>
                        </div>
                    </div>

                    <!-- System Decision & Integrity Notice -->
                    <div class="p-4 rounded-xl border <?= $badgeBg ?> flex items-start gap-3">
                        <span class="w-2.5 h-2.5 rounded-full <?= $badgeDot ?> mt-1.5 flex-shrink-0 animate-pulse"></span>
                        <div class="space-y-0.5">
                            <div class="text-xs font-bold uppercase tracking-wider">Hasil Klasifikasi Sistem: <?= esc($decLabel) ?></div>
                            <p class="text-xs leading-relaxed opacity-90">
                                <?= esc($report['rationale'] ?? 'Analisis dekomposisi bukti selesai.') ?>
                            </p>
                        </div>
                    </div>

                    <!-- Top Detected Sources -->
                    <?php if (!empty($sources)): ?>
                        <div class="space-y-3 pt-2">
                            <div class="text-xs font-bold text-slate-400 uppercase tracking-wider">Dokumen Rujukan Teratas Terdeteksi</div>
                            <div class="rounded-xl border border-slate-200 overflow-hidden divide-y divide-slate-100 text-xs">
                                <?php foreach ($sources as $s): ?>
                                    <div class="p-3.5 flex items-center justify-between gap-4 hover:bg-slate-50 transition">
                                        <div class="flex items-center gap-2.5 min-w-0">
                                            <span class="font-bold text-slate-400 w-5 text-center">#<?= esc($s['source_rank'] ?? 1) ?></span>
                                            <div class="min-w-0">
                                                <div class="font-bold text-slate-800 truncate"><?= esc($s['source_title'] ?? 'Dokumen Rujukan') ?></div>
                                                <div class="text-[10px] text-slate-400">ID #<?= esc($s['source_document_id'] ?? '-') ?> • <?= ($s['source_type'] ?? '') === 'corpus' ? 'Korpus Institusi' : 'Naskah Terpublikasi' ?></div>
                                            </div>
                                        </div>
                                        <div class="font-mono-code font-bold text-indigo-600 text-sm flex-shrink-0">
                                            <?= number_format((float)($s['similarity'] ?? 0), 1) ?>%
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Action Buttons -->
                    <div class="pt-4 border-t border-slate-200 flex flex-col sm:flex-row items-center justify-between gap-3">
                        <div class="text-[11px] text-slate-400">
                            Keaslian dokumen ini dijamin oleh stempel digital SHA-256 AR Tilik.
                        </div>
                        <div class="flex items-center gap-2 w-full sm:w-auto">
                            <a href="/report/<?= esc($report['id']) ?>/export" class="flex-1 sm:flex-none px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-xl shadow-xs transition flex items-center justify-center gap-1.5">
                                <span>📄 Unduh Sertifikat PDF Resmi</span>
                            </a>
                            <a href="/report/<?= esc($report['id']) ?>" class="flex-1 sm:flex-none px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-xl transition flex items-center justify-center gap-1.5">
                                <span>Buka Laporan Lengkap →</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

        <?php elseif ($status === 'not_found'): ?>
            <!-- Not Found Card -->
            <div class="bg-white rounded-2xl border border-rose-200 shadow-sm p-8 text-center space-y-4 max-w-lg mx-auto">
                <div class="w-16 h-16 rounded-full bg-rose-50 text-rose-500 border border-rose-200 flex items-center justify-center text-3xl mx-auto">
                    ⚠️
                </div>
                <div class="space-y-1">
                    <h2 class="text-xl font-bold text-slate-900">Sertifikat Tidak Ditemukan</h2>
                    <p class="text-xs text-slate-500">
                        Kode verifikasi <span class="font-mono-code font-bold text-rose-600"><?= esc($code) ?></span> tidak terdaftar dalam basis data integritas akademik resmi AR Tilik.
                    </p>
                </div>
                <div class="pt-2">
                    <a href="/verify" class="inline-block px-5 py-2.5 bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold rounded-xl transition">
                        Cari Nomor Sertifikat Lain
                    </a>
                </div>
            </div>

        <?php else: ?>
            <!-- Input Form Card -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 sm:p-8 max-w-lg mx-auto space-y-6">
                <div class="text-center space-y-2">
                    <div class="w-12 h-12 rounded-2xl bg-indigo-50 border border-indigo-200 text-indigo-600 flex items-center justify-center text-2xl mx-auto">
                        🔍
                    </div>
                    <h1 class="text-xl font-black text-slate-900">Verifikasi Sertifikat AR Tilik</h1>
                    <p class="text-xs text-slate-500">
                        Masukkan kode sertifikat verifikasi (contoh: <code class="bg-slate-100 px-1 py-0.5 rounded font-mono-code text-indigo-600">ART-XXXX-XXXX-XXXX</code>) yang tertera pada lembar laporan hasil uji.
                    </p>
                </div>

                <form action="/verify" method="GET" class="space-y-4">
                    <div>
                        <label for="code" class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">
                            Nomor Sertifikat Verifikasi
                        </label>
                        <input type="text" name="code" id="code" placeholder="ART-XXXX-XXXX-XXXX" required autofocus
                               class="w-full px-4 py-3 rounded-xl border border-slate-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 font-mono-code text-center text-sm font-bold uppercase tracking-wider text-slate-800 placeholder:text-slate-300">
                    </div>
                    <button type="submit" class="w-full py-3 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold uppercase tracking-wider rounded-xl shadow-xs transition">
                        Verifikasi Keabsahan Dokumen
                    </button>
                </form>

                <div class="text-center text-[11px] text-slate-400">
                    Pindai kode QR pada sudut kanan atas dokumen hasil uji untuk verifikasi instan di perangkat ponsel.
                </div>
            </div>
        <?php endif; ?>
    </main>

    <!-- Footer -->
    <footer class="bg-white border-t border-slate-200 py-6 text-center text-xs text-slate-400">
        <p>AR Tilik • Academic Writing Similarity & Evidence Checker • Dikembangkan dengan Prinsip Keadilan Akademik</p>
    </footer>

</body>
</html>
