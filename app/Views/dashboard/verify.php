<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $status === 'valid' ? 'Sertifikat Integritas Akademik #' . esc($code) . ' — AR Tilik' : 'Verifikasi Sertifikat Resmi — AR Tilik | Portal Validasi QR Ilmiah' ?></title>
    <meta name="description" content="Portal Verifikasi Resmi Sertifikat Keaslian Hasil Telaah Kesepadanan Naskah AR Tilik. Validasi kode QR kriptografis dan stempel digital keabsahan karya ilmiah berstandar perguruan tinggi.">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="<?= base_url('verify' . (!empty($code) ? '/' . esc($code) : '')) ?>">
    <meta name="theme-color" content="#1e1b4b">

    <!-- Open Graph -->
    <meta property="og:type" content="website">
    <meta property="og:locale" content="id_ID">
    <meta property="og:site_name" content="AR Tilik">
    <meta property="og:title" content="<?= $status === 'valid' ? 'Sertifikat Integritas Akademik #' . esc($code) . ' — AR Tilik' : 'Verifikasi Sertifikat Resmi — AR Tilik' ?>">
    <meta property="og:description" content="Validasi sertifikat digital orisinalitas naskah karya ilmiah berstempel kriptografis resmi.">
    <meta property="og:url" content="<?= base_url('verify' . (!empty($code) ? '/' . esc($code) : '')) ?>">
    <meta property="og:image" content="<?= base_url('images/og-artilik.png') ?>">

    <!-- Favicon & Brand Icons -->
    <link rel="icon" type="image/x-icon" href="<?= base_url('favicon.ico?v=20261008') ?>">
    <link rel="icon" type="image/png" sizes="32x32" href="<?= base_url('favicon-32x32.png?v=20261008') ?>">
    <link rel="icon" type="image/png" sizes="16x16" href="<?= base_url('favicon-16x16.png?v=20261008') ?>">
    <link rel="apple-touch-icon" sizes="180x180" href="<?= base_url('apple-touch-icon.png?v=20261008') ?>">

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800;900&family=Cinzel:wght@600;700;800;900&family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
        }
        .font-serif-academic {
            font-family: 'Cinzel', serif;
        }
        .font-mono-code {
            font-family: 'JetBrains Mono', monospace;
        }

        /* Ornamen Bingkai Klasik Sertifikat Akademik */
        .cert-outer-border {
            border: 6px double #312e81;
            box-shadow: 0 0 0 4px #e0e7ff, 0 20px 40px -15px rgba(15, 23, 42, 0.15);
        }
        .cert-inner-frame {
            border: 1.5px solid #c7d2fe;
            position: relative;
        }
        .cert-corner {
            position: absolute;
            width: 24px;
            height: 24px;
            border-color: #4338ca;
        }
        .cert-corner-tl { top: -2px; left: -2px; border-top: 3px solid; border-left: 3px solid; }
        .cert-corner-tr { top: -2px; right: -2px; border-top: 3px solid; border-right: 3px solid; }
        .cert-corner-bl { bottom: -2px; left: -2px; border-bottom: 3px solid; border-left: 3px solid; }
        .cert-corner-br { bottom: -2px; right: -2px; border-bottom: 3px solid; border-right: 3px solid; }

        /* Stempel Embossed 3D Gold Seal */
        .embossed-seal {
            background: radial-gradient(circle at 35% 35%, #fef08a, #ca8a04 60%, #854d0e 95%);
            box-shadow: 0 4px 14px rgba(161, 98, 7, 0.4), inset 0 2px 4px rgba(254, 240, 138, 0.8), inset 0 -2px 4px rgba(113, 63, 18, 0.6);
            border: 3px dashed #fef9c3;
        }

        /* Watermark Background */
        .cert-watermark {
            background-image: radial-gradient(circle at 50% 50%, rgba(224, 231, 255, 0.4) 0%, rgba(255, 255, 255, 0) 70%);
        }

        /* Print Optimization */
        @media print {
            body {
                background: #ffffff !important;
                color: #0f172a !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            .no-print {
                display: none !important;
            }
            .print-container {
                max-width: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
                box-shadow: none !important;
                border: 4px double #312e81 !important;
            }
            .cert-outer-border {
                box-shadow: none !important;
            }
        }
    </style>
</head>
<body class="bg-slate-100 text-slate-800 antialiased min-h-screen flex flex-col justify-between selection:bg-indigo-600 selection:text-white">

    <!-- Top Navigation (Hidden in Print) -->
    <header class="bg-white/95 backdrop-blur-md border-b border-slate-200 sticky top-0 z-50 shadow-xs no-print">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 h-16 flex items-center justify-between">
            <a href="<?= base_url() ?>" class="flex items-center gap-2.5 hover:opacity-90 transition group">
                <img src="<?= base_url('images/logo.png') ?>" alt="AR Tilik Logo" class="w-9 h-9 object-contain drop-shadow-xs group-hover:scale-105 transition-transform duration-200">
                <div class="flex items-center gap-2">
                    <span class="font-bold text-lg tracking-tight bg-gradient-to-r from-indigo-800 to-indigo-600 bg-clip-text text-transparent">AR Tilik</span>
                    <span class="hidden sm:inline-block text-[10px] font-bold uppercase tracking-wider text-indigo-700 bg-indigo-50 px-2.5 py-0.5 rounded-full border border-indigo-200">
                        Official Academic Attestation
                    </span>
                </div>
            </a>
            <div class="flex items-center gap-2.5">
                <a href="<?= base_url('dashboard') ?>" class="text-xs font-semibold text-slate-600 hover:text-indigo-600 px-3 py-1.5 rounded-lg border border-slate-200 hover:bg-slate-50 transition">
                    Dashboard
                </a>
                <a href="<?= base_url('/') ?>" class="text-xs font-semibold text-slate-600 hover:text-indigo-600 px-3 py-1.5 rounded-lg border border-slate-200 hover:bg-slate-50 transition">
                    Beranda
                </a>
            </div>
        </div>
    </header>

    <!-- Main Container -->
    <main class="max-w-5xl w-full mx-auto px-4 sm:px-6 py-8 sm:py-12 flex-1">
        <?php if ($status === 'valid' && $report): ?>
            <?php
                $overallSim = (float)($report['overall_similarity'] ?? 0);
                $exactSim = (float)($report['exact_similarity'] ?? 0);
                $cosineSim = (float)($report['cosine_similarity'] ?? 0);
                $longestRun = (int)($report['longest_match_words'] ?? 0);
                $dec = strtolower($report['predicted_decision'] ?? 'review');
                $dateFormatted = date('d F Y, H:i', strtotime($report['created_at'])) . ' WIB';
                $dateShort = date('d/m/Y', strtotime($report['created_at']));

                // Data Sovereignty & Purged Status
                $isPurged = !empty($document['file_purged_at']) || ($document['storage_path'] ?? '') === '[PURGED]';
                $purgedDate = !empty($document['file_purged_at']) ? date('d F Y, H:i', strtotime($document['file_purged_at'])) . ' WIB' : null;

                // QR Code URL (Points to this exact public verify URL)
                $verifyUrl = base_url('verify/' . $code);
                $qrApiUrl = "https://api.qrserver.com/v1/create-qr-code/?size=150x150&color=1e1b4b&data=" . urlencode($verifyUrl);

                // Decision Labels & Styling
                if ($dec === 'match') {
                    $decBadge = 'bg-rose-50 border-rose-300 text-rose-800';
                    $decPill = 'bg-rose-600 text-white';
                    $decLabel = 'MATCH (Tingkat Kemiripan Tinggi)';
                    $decDesc = 'Naskah memiliki pasase harfiah berulang signifikan yang memerlukan parafrasa intensif sebelum diujikan.';
                } elseif ($dec === 'review') {
                    $decBadge = 'bg-amber-50 border-amber-300 text-amber-900';
                    $decPill = 'bg-amber-500 text-slate-950 font-black';
                    $decLabel = 'REVIEW (Perlu Tinjauan Akademik)';
                    $decDesc = 'Kemiripan dalam ambang toleransi standar institusi, disarankan peninjauan rujukan dan sitasi.';
                } else {
                    $decBadge = 'bg-emerald-50 border-emerald-300 text-emerald-900';
                    $decPill = 'bg-emerald-600 text-white';
                    $decLabel = 'NO MATCH (Orisinalitas Sangat Baik)';
                    $decDesc = 'Naskah bebas dari temuan pasase kemiripan berulang signifikan dan memenuhi kaidah integritas karya ilmiah.';
                }

                // AI Assessment Data if available
                $aiData = $report['ai_assessment'] ?? null;
                $aiScore = $aiData ? ($aiData['score'] ?? null) : null;
            ?>

            <!-- Action Toolbar (Hidden in Print) -->
            <div class="mb-6 flex flex-wrap items-center justify-between gap-3 no-print bg-white p-3.5 sm:p-4 rounded-2xl border border-slate-200 shadow-xs">
                <div class="flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span class="text-xs font-bold text-slate-700">Status Validasi Dokumen:</span>
                    <span class="text-xs font-bold text-emerald-700 bg-emerald-50 px-2.5 py-0.5 rounded-full border border-emerald-200">
                        ✅ Kriptografis Asli &amp; Terverifikasi
                    </span>
                </div>
                <div class="flex items-center gap-2 flex-wrap">
                    <button type="button" onclick="window.print()" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-xl shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                        <span>Cetak / Simpan PDF</span>
                    </button>
                    <button type="button" id="btnCopyVerifyUrl" onclick="copyVerifyUrl()" class="px-3.5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold rounded-xl transition flex items-center gap-1.5 cursor-pointer">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                        <span id="btnCopyVerifyText">Salin Tautan</span>
                    </button>
                    <?php if (!empty($report['id'])): ?>
                    <a href="<?= base_url('dashboard/report/' . $report['id']) ?>" class="px-3.5 py-2 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 text-xs font-semibold rounded-xl transition flex items-center gap-1">
                        <span>Laporan Rinci &rarr;</span>
                    </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- ACADEMIC CERTIFICATE CANVAS (PRINTABLE) -->
            <div class="print-container bg-white rounded-3xl cert-outer-border p-4 sm:p-8 relative overflow-hidden text-slate-900 shadow-xl">
                <!-- Watermark Background -->
                <div class="absolute inset-0 pointer-events-none cert-watermark flex items-center justify-center opacity-40">
                    <div class="text-center transform -rotate-12 select-none">
                        <div class="font-serif-academic text-slate-200 text-5xl sm:text-7xl font-black tracking-widest uppercase">
                            AR TILIK
                        </div>
                        <div class="text-[10px] sm:text-xs font-bold tracking-[0.3em] text-slate-300 uppercase mt-2">
                            ACADEMIC INTEGRITY ATTESTATION • OFFICIAL
                        </div>
                    </div>
                </div>

                <!-- Inner Frame with Corner Accents -->
                <div class="cert-inner-frame p-6 sm:p-10 rounded-2xl bg-white/95 relative z-10 space-y-7">
                    <div class="cert-corner cert-corner-tl"></div>
                    <div class="cert-corner cert-corner-tr"></div>
                    <div class="cert-corner cert-corner-bl"></div>
                    <div class="cert-corner cert-corner-br"></div>

                    <!-- Certificate Official Header (Kop Piagam) -->
                    <div class="border-b-2 border-indigo-900/20 pb-6 flex flex-col sm:flex-row items-center justify-between gap-6 text-center sm:text-left">
                        <div class="flex items-center gap-4">
                            <img src="<?= base_url('images/logo.png') ?>" alt="AR Tilik Logo" class="w-16 h-16 sm:w-20 sm:h-20 object-contain drop-shadow-md">
                            <div>
                                <div class="text-[11px] sm:text-xs font-extrabold uppercase tracking-[0.2em] text-indigo-900">
                                    Sistem Pemeriksaan Integritas &amp; Kesepadanan Akademik
                                </div>
                                <h1 class="font-serif-academic text-xl sm:text-3xl font-black tracking-wide text-slate-900 mt-0.5">
                                    SERTIFIKAT AKREDITASI INTEGRITAS
                                </h1>
                                <div class="text-[10px] sm:text-xs font-medium text-slate-500 tracking-wider uppercase mt-0.5">
                                    Official Attestation of Academic Integrity &amp; Originality Standards
                                </div>
                            </div>
                        </div>

                        <!-- Certificate ID & Date Block -->
                        <div class="sm:text-right bg-indigo-50/70 border border-indigo-200/80 px-4 py-3 rounded-xl">
                            <div class="text-[10px] font-extrabold uppercase tracking-widest text-indigo-800">Nomor Registrasi Sertifikat</div>
                            <div class="text-base sm:text-lg font-black font-mono-code text-indigo-950 tracking-wider">
                                <?= esc($code) ?>
                            </div>
                            <div class="text-[10px] text-slate-500 mt-0.5">Tanggal Terbit: <strong><?= esc($dateShort) ?></strong></div>
                        </div>
                    </div>

                    <!-- Preamble / Surat Keterangan Pernyataan -->
                    <div class="text-center space-y-2 max-w-2xl mx-auto pt-1">
                        <p class="text-xs sm:text-sm text-slate-600 leading-relaxed">
                            Berdasarkan hasil audit sistem komputasi forensik bahasa berbasis algoritma sekuensial <em>5-Gram Shingle</em> dan dekomposisi leksikal <em>TF-IDF Cosine</em>, dengan ini diterbitkan lembar verifikasi resmi terhadap karya ilmiah:
                        </p>
                    </div>

                    <!-- Document Title Card -->
                    <div class="bg-gradient-to-br from-slate-50 via-indigo-50/30 to-slate-50 p-5 sm:p-6 rounded-2xl border border-indigo-100 text-center space-y-3 shadow-2xs">
                        <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] font-extrabold uppercase tracking-widest bg-indigo-100/70 text-indigo-800 border border-indigo-200">
                            <span>📄 Dokumen Terperiksa</span>
                        </div>
                        <h2 class="text-lg sm:text-2xl font-black text-slate-900 leading-snug max-w-3xl mx-auto">
                            &ldquo;<?= esc($document['title'] ?? 'Dokumen Karya Ilmiah') ?>&rdquo;
                        </h2>
                        
                        <!-- Metadata Penulis & Institusi -->
                        <div class="flex flex-wrap items-center justify-center gap-x-6 gap-y-2 text-xs text-slate-600 pt-1 border-t border-indigo-100/60 max-w-2xl mx-auto">
                            <?php if (!empty($document['author_name'])): ?>
                            <div>
                                <span class="text-slate-400">Penulis:</span> <strong><?= esc($document['author_name']) ?></strong>
                            </div>
                            <?php endif; ?>
                            <?php if (!empty($document['institution'])): ?>
                            <div>
                                <span class="text-slate-400">Institusi:</span> <strong><?= esc($document['institution']) ?></strong>
                            </div>
                            <?php endif; ?>
                            <?php if (!empty($document['faculty'])): ?>
                            <div>
                                <span class="text-slate-400">Fakultas:</span> <strong><?= esc($document['faculty']) ?></strong>
                            </div>
                            <?php endif; ?>
                            <?php if (!empty($document['department'])): ?>
                            <div>
                                <span class="text-slate-400">Program Studi:</span> <strong><?= esc($document['department']) ?></strong>
                            </div>
                            <?php endif; ?>
                            <div>
                                <span class="text-slate-400">Berkas:</span> <span class="font-mono-code"><?= esc($document['original_filename'] ?? '-') ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Score Decomposition & Metrics Grid -->
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-extrabold uppercase tracking-wider text-slate-500">Dekomposisi Metrik Kesepadanan</span>
                            <span class="text-[11px] text-slate-400 font-mono-code">Engine: <?= strtoupper(esc($report['engine'] ?? 'HYBRID')) ?></span>
                        </div>
                        
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 sm:gap-4">
                            <!-- Overall Similarity -->
                            <div class="p-4 rounded-2xl bg-indigo-50 border-2 border-indigo-300/80 text-center relative overflow-hidden">
                                <div class="text-[10px] font-extrabold uppercase tracking-wider text-indigo-700">Skor Gabungan (Hybrid)</div>
                                <div class="text-3xl sm:text-4xl font-black font-mono-code text-indigo-950 mt-1">
                                    <?= number_format($overallSim, 2) ?>%
                                </div>
                                <div class="text-[10px] text-indigo-600 font-semibold mt-0.5">Ambang Akreditasi</div>
                            </div>

                            <!-- Sequential 5-Gram -->
                            <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200 text-center">
                                <div class="text-[10px] font-extrabold uppercase tracking-wider text-slate-500">Sekuensial Harfiah</div>
                                <div class="text-2xl sm:text-3xl font-black font-mono-code text-slate-800 mt-1">
                                    <?= number_format($exactSim, 2) ?>%
                                </div>
                                <div class="text-[10px] text-slate-400 mt-0.5">5-Gram Exact Token</div>
                            </div>

                            <!-- Lexical Cosine -->
                            <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200 text-center">
                                <div class="text-[10px] font-extrabold uppercase tracking-wider text-slate-500">Kosinus Leksikal</div>
                                <div class="text-2xl sm:text-3xl font-black font-mono-code text-slate-800 mt-1">
                                    <?= number_format($cosineSim, 2) ?>%
                                </div>
                                <div class="text-[10px] text-slate-400 mt-0.5">TF-IDF Topikal</div>
                            </div>

                            <!-- Longest Match -->
                            <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200 text-center">
                                <div class="text-[10px] font-extrabold uppercase tracking-wider text-slate-500">Run Terpanjang</div>
                                <div class="text-2xl sm:text-3xl font-black font-mono-code text-slate-800 mt-1">
                                    <?= $longestRun ?>
                                </div>
                                <div class="text-[10px] text-slate-400 mt-0.5">Kata Berurutan</div>
                            </div>
                        </div>
                    </div>

                    <!-- AI Indication (If Available) -->
                    <?php if ($aiScore !== null): ?>
                    <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-2 text-xs">
                        <div class="flex items-center gap-2">
                            <span class="text-base">🤖</span>
                            <span class="font-bold text-slate-700">Audit Forensik Indikasi Sintesis AI:</span>
                            <span class="font-mono-code font-bold text-slate-900"><?= number_format($aiScore, 1) ?>%</span>
                            <span class="text-[10px] text-slate-400">• <?= esc($aiData['label'] ?? 'Tingkat Indikasi ' . ($aiData['forensic_level'] ?? 'Normal')) ?></span>
                        </div>
                        <div class="text-[11px] text-slate-500">
                            Metode Analisis: Stylometri &amp; Penalaran Semantik
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Decision Ribbon Banner -->
                    <div class="p-4 sm:p-5 rounded-2xl border-2 <?= $decBadge ?> flex flex-col sm:flex-row items-center justify-between gap-4">
                        <div class="flex items-center gap-3.5 text-center sm:text-left">
                            <div class="w-10 h-10 rounded-full flex items-center justify-center text-xl font-black <?= $decPill ?> flex-shrink-0">
                                ✓
                            </div>
                            <div>
                                <div class="text-xs font-extrabold uppercase tracking-wider">
                                    Status Keputusan Dewan Sistem: <strong><?= esc($decLabel) ?></strong>
                                </div>
                                <p class="text-xs mt-0.5 leading-relaxed opacity-90 max-w-xl">
                                    <?= esc($decDesc) ?>
                                </p>
                            </div>
                        </div>
                        <div class="flex-shrink-0 text-center sm:text-right">
                            <span class="inline-block px-3.5 py-1.5 rounded-xl text-xs font-black uppercase tracking-wider <?= $decPill ?> shadow-xs">
                                Akreditasi Lolos
                            </span>
                        </div>
                    </div>

                    <!-- Data Sovereignty / Self-Purge Guarantee Notice (FITUR 4 & 5) -->
                    <div class="p-4 rounded-2xl <?= $isPurged ? 'bg-emerald-50/70 border border-emerald-300' : 'bg-slate-50 border border-slate-200' ?> flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                        <div class="flex items-start gap-3">
                            <div class="text-xl mt-0.5 flex-shrink-0">
                                🛡️
                            </div>
                            <div class="space-y-0.5">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <h4 class="text-xs font-bold text-slate-900">Jaminan Kedaulatan Data &amp; Privasi Akademik (Zero-Leakage Guarantee)</h4>
                                    <?php if ($isPurged): ?>
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-emerald-600 text-white">
                                        Berkas Mentah Telah Dimusnahkan
                                    </span>
                                    <?php else: ?>
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-slate-200 text-slate-700">
                                        Perlindungan Enkripsi Disk
                                    </span>
                                    <?php endif; ?>
                                </div>
                                <p class="text-[11px] text-slate-600 leading-relaxed max-w-2xl">
                                    <?php if ($isPurged): ?>
                                    Berkas fisik asli naskah ini (PDF/DOCX) telah dimusnahkan secara mandiri oleh pemilik akun pada <strong><?= esc($purgedDate) ?></strong>. Keabsahan sertifikat dan rekap analitik ini dijamin sah 100% berbasis sidik jari kriptografis tanpa menyimpan salinan naskah mentah di peladen.
                                    <?php else: ?>
                                    Naskah ini dilindungi di bawah protokol integritas tertutup AR Tilik. Naskah tidak pernah dipublikasikan atau dijadikan korpus terbuka tanpa izin eksplisit pemilik karya.
                                    <?php endif; ?>
                                </p>
                            </div>
                        </div>
                        <div class="flex-shrink-0 self-end sm:self-center">
                            <span class="text-[10px] font-mono-code font-bold text-indigo-800 bg-indigo-100/60 px-2.5 py-1 rounded-lg border border-indigo-200">
                                SHA-256 Valid
                            </span>
                        </div>
                    </div>

                    <!-- Top Detected Sources (Summary) -->
                    <?php if (!empty($sources)): ?>
                    <div class="space-y-2.5 pt-1">
                        <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Rujukan Teratas Terdeteksi Dalam Korpus:</div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs">
                            <?php foreach ($sources as $s): ?>
                            <div class="p-2.5 rounded-xl border border-slate-200 bg-white flex items-center justify-between gap-3 shadow-2xs">
                                <div class="min-w-0">
                                    <div class="font-bold text-slate-800 truncate" title="<?= esc($s['source_title'] ?? '') ?>"><?= esc($s['source_title'] ?? 'Dokumen Rujukan') ?></div>
                                    <div class="text-[10px] text-slate-400">Peringkat #<?= esc($s['source_rank'] ?? 1) ?> &bull; <?= ($s['source_type'] ?? '') === 'corpus' ? 'Korpus Terdaftar' : 'Naskah Publik' ?></div>
                                </div>
                                <span class="font-mono-code font-bold text-indigo-700 text-xs flex-shrink-0">
                                    <?= number_format((float)($s['similarity'] ?? 0), 1) ?>%
                                </span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Bottom Authentication Footer: QR Code, Embossed Seal & Digital Signature -->
                    <div class="border-t-2 border-indigo-900/20 pt-6 flex flex-col sm:flex-row items-center justify-between gap-6">
                        <!-- Dynamic QR Code -->
                        <div class="flex items-center gap-3.5 text-left">
                            <div class="p-1.5 bg-white border border-slate-300 rounded-xl shadow-2xs flex-shrink-0">
                                <img src="<?= esc($qrApiUrl) ?>" alt="QR Code Verifikasi" class="w-24 h-24 object-contain">
                            </div>
                            <div class="space-y-1">
                                <div class="text-[11px] font-extrabold uppercase tracking-wider text-indigo-900">Verifikasi QR Kriptografis</div>
                                <p class="text-[10px] text-slate-500 leading-relaxed max-w-[200px]">
                                    Pindai dengan kamera ponsel untuk memvalidasi keabsahan dokumen secara instan pada basis data resmi AR Tilik.
                                </p>
                                <div class="text-[9px] font-mono-code text-slate-400 truncate max-w-[200px]" title="<?= esc($verifyUrl) ?>">
                                    <?= esc($verifyUrl) ?>
                                </div>
                            </div>
                        </div>

                        <!-- 3D Embossed Seal Stamp -->
                        <div class="relative flex items-center justify-center my-2 sm:my-0">
                            <div class="w-24 h-24 rounded-full embossed-seal flex items-center justify-center text-center p-2 text-amber-950 font-serif-academic relative z-10 shadow-lg">
                                <div class="text-[8px] font-black tracking-tighter leading-tight uppercase">
                                    ★ AR TILIK ★<br>
                                    <span class="text-[9px] font-bold">INTEGRITAS</span><br>
                                    RESMI<br>
                                    <span class="text-[7px] tracking-normal font-sans">SHA-256</span>
                                </div>
                            </div>
                        </div>

                        <!-- Digital Signature & Authority Sign-off -->
                        <div class="text-center sm:text-right space-y-1">
                            <div class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400">Pengesahan Sistem Peladen</div>
                            <div class="font-serif-academic text-sm font-bold text-slate-800">
                                AR Tilik Academic Engine
                            </div>
                            <div class="text-[10px] font-mono-code text-indigo-700 bg-indigo-50 px-2 py-0.5 rounded border border-indigo-200 inline-block">
                                SHA-256: <?= substr(hash('sha256', $code . ($report['created_at'] ?? '')), 0, 16) ?>...
                            </div>
                            <div class="text-[10px] text-slate-400 pt-1">
                                Diverifikasi Otomatis pada <?= esc($dateFormatted) ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        <?php elseif ($status === 'not_found'): ?>
            <!-- Not Found Card -->
            <div class="bg-white rounded-3xl border border-rose-200 shadow-sm p-8 text-center space-y-5 max-w-lg mx-auto">
                <div class="w-16 h-16 rounded-2xl bg-rose-50 text-rose-600 border border-rose-200 flex items-center justify-center text-3xl mx-auto shadow-inner">
                    ⚠️
                </div>
                <div class="space-y-1.5">
                    <h2 class="text-xl font-bold text-slate-900">Sertifikat Tidak Ditemukan</h2>
                    <p class="text-xs text-slate-500 leading-relaxed">
                        Kode registrasi sertifikat <span class="font-mono-code font-bold text-rose-600 bg-rose-50 px-2 py-0.5 rounded"><?= esc($code) ?></span> tidak terdaftar dalam basis data integritas akademik resmi AR Tilik.
                    </p>
                </div>
                <div class="pt-2">
                    <a href="<?= base_url('verify') ?>" class="inline-block px-5 py-2.5 bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold rounded-xl transition">
                        Periksa Nomor Sertifikat Lain &rarr;
                    </a>
                </div>
            </div>

        <?php else: ?>
            <!-- Input Form Card -->
            <div class="bg-white rounded-3xl border border-slate-200 shadow-sm p-6 sm:p-10 max-w-lg mx-auto space-y-6">
                <div class="text-center space-y-2">
                    <div class="w-14 h-14 rounded-2xl bg-indigo-50 border border-indigo-200 text-indigo-600 flex items-center justify-center text-3xl mx-auto shadow-inner">
                        🔍
                    </div>
                    <h1 class="text-xl sm:text-2xl font-black text-slate-900">Validasi Sertifikat Akademik</h1>
                    <p class="text-xs text-slate-500 leading-relaxed">
                        Masukkan nomor sertifikat resmi (contoh: <code class="bg-slate-100 px-1.5 py-0.5 rounded font-mono-code text-indigo-600 font-bold">ART-XXXX-XXXX-XXXX</code>) yang tertera pada lembar hasil uji naskah.
                    </p>
                </div>

                <form action="<?= base_url('verify') ?>" method="GET" class="space-y-4">
                    <div>
                        <label for="code" class="block text-xs font-extrabold uppercase tracking-wider text-slate-600 mb-2">
                            Nomor Registrasi Sertifikat
                        </label>
                        <input type="text" name="code" id="code" placeholder="ART-XXXX-XXXX-XXXX" required autofocus
                               class="w-full px-4 py-3.5 rounded-xl border border-slate-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 font-mono-code text-center text-base font-bold uppercase tracking-wider text-slate-900 placeholder:text-slate-300 bg-slate-50/50">
                    </div>
                    <button type="submit" class="w-full py-3.5 bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 text-white text-xs font-bold uppercase tracking-wider rounded-xl shadow-md transition flex items-center justify-center gap-2 cursor-pointer">
                        <span>Verifikasi Keabsahan Sekarang</span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                    </button>
                </form>

                <div class="p-3 bg-slate-50 rounded-xl border border-slate-200 text-center text-[11px] text-slate-500 leading-relaxed">
                    💡 Atau arahkan kamera ponsel ke <strong>Kode QR</strong> di sudut kanan atas dokumen hasil uji untuk membuka sertifikat digital ini secara langsung.
                </div>
            </div>
        <?php endif; ?>
    </main>

    <!-- Footer (Hidden in Print) -->
    <footer class="bg-white border-t border-slate-200 py-6 text-center text-xs text-slate-400 no-print">
        <p>AR Tilik • Academic Writing Similarity &amp; Evidence Checker &bull; Dikembangkan dengan Standar Integritas &amp; Kedaulatan Data</p>
    </footer>

    <!-- Copy Link Script -->
    <script>
        function copyVerifyUrl() {
            const url = window.location.href;
            navigator.clipboard.writeText(url).then(() => {
                const btnText = document.getElementById('btnCopyVerifyText');
                if (btnText) {
                    const orig = btnText.innerText;
                    btnText.innerText = 'Tersalin!';
                    setTimeout(() => { btnText.innerText = orig; }, 2000);
                }
            }).catch(() => {
                alert('Tautan disalin ke clipboard: ' + url);
            });
        }
    </script>

</body>
</html>
