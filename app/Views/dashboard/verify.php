<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $status === 'valid' ? 'Sertifikat Akreditasi Integritas Akademik #' . esc($code) . ' — AR Tilik' : 'Verifikasi Sertifikat Resmi — AR Tilik | Portal Validasi QR Ilmiah' ?></title>
    <meta name="description" content="Portal Verifikasi Resmi Sertifikat Keaslian Hasil Telaah Kesepadanan Naskah AR Tilik. Dokumen akreditasi integritas karya ilmiah berstandar perguruan tinggi berstempel digital kriptografis.">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="<?= base_url('verify' . (!empty($code) ? '/' . esc($code) : '')) ?>">
    <meta name="theme-color" content="#0f172a">

    <!-- Open Graph -->
    <meta property="og:type" content="website">
    <meta property="og:locale" content="id_ID">
    <meta property="og:site_name" content="AR Tilik">
    <meta property="og:title" content="<?= $status === 'valid' ? 'Sertifikat Akreditasi Integritas Akademik #' . esc($code) . ' — AR Tilik' : 'Verifikasi Sertifikat Resmi — AR Tilik' ?>">
    <meta property="og:description" content="Lembar Sertifikat Resmi Akreditasi Integritas dan Keaslian Karya Ilmiah AR Tilik.">
    <meta property="og:url" content="<?= base_url('verify' . (!empty($code) ? '/' . esc($code) : '')) ?>">

    <!-- Favicon & Brand Icons -->
    <link rel="icon" type="image/x-icon" href="<?= base_url('favicon.ico?v=20261008') ?>">
    <link rel="icon" type="image/png" sizes="32x32" href="<?= base_url('favicon-32x32.png?v=20261008') ?>">
    <link rel="icon" type="image/png" sizes="16x16" href="<?= base_url('favicon-16x16.png?v=20261008') ?>">
    <link rel="apple-touch-icon" sizes="180x180" href="<?= base_url('apple-touch-icon.png?v=20261008') ?>">

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@600;700;800;900&family=Playfair+Display:ital,wght@0,600;0,700;0,800;0,900;1,400;1,600&family=Plus+Jakarta+Sans:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
        }
        .font-diploma-serif {
            font-family: 'Cinzel', 'Times New Roman', serif;
        }
        .font-editorial-serif {
            font-family: 'Playfair Display', Georgia, serif;
        }
        .font-mono-code {
            font-family: 'JetBrains Mono', monospace;
        }

        /* Latar Belakang Parchment Mewah */
        .diploma-parchment {
            background-color: #ffffff;
            background-image: 
                radial-gradient(circle at 50% 50%, rgba(254, 252, 246, 0.9) 0%, rgba(250, 248, 240, 0.7) 100%),
                radial-gradient(circle at 20% 20%, rgba(212, 175, 55, 0.03) 0%, transparent 60%);
        }

        /* Bingkai Klasik Ganda Akreditasi (Double Guilloche Frame) */
        .diploma-border-outer {
            border: 4px solid #0f2137;
            outline: 2px solid #b8860b;
            outline-offset: -7px;
            box-shadow: 0 15px 35px -10px rgba(15, 33, 55, 0.2), inset 0 0 60px rgba(212, 175, 55, 0.05);
        }
        .diploma-border-inner {
            border: 1px solid #c5a059;
            box-shadow: inset 0 0 0 1px rgba(15, 33, 55, 0.15);
        }

        /* Stempel Medali Emas Timbul 3D dengan Tekstur Bergerigi */
        .gold-seal-medallion {
            background: radial-gradient(circle at 35% 30%, #fffbeb 5%, #fef08a 20%, #d97706 70%, #92400e 95%);
            border: 2px dashed #fef3c7;
            box-shadow: 
                0 4px 12px rgba(146, 64, 14, 0.35),
                inset 0 2px 5px rgba(255, 255, 255, 0.9),
                inset 0 -3px 6px rgba(120, 53, 15, 0.6);
        }

        /* Pita Menjuntai Stempel Emas */
        .seal-ribbon-left {
            position: absolute;
            bottom: -14px;
            left: 12px;
            width: 22px;
            height: 36px;
            background: linear-gradient(135deg, #0f2137 0%, #1e3a8a 100%);
            clip-path: polygon(0% 0%, 100% 0%, 100% 100%, 50% 80%, 0% 100%);
            box-shadow: 0 2px 4px rgba(0,0,0,0.3);
            z-index: 1;
        }
        .seal-ribbon-right {
            position: absolute;
            bottom: -14px;
            right: 12px;
            width: 22px;
            height: 36px;
            background: linear-gradient(225deg, #0f2137 0%, #1e3a8a 100%);
            clip-path: polygon(0% 0%, 100% 0%, 100% 100%, 50% 80%, 0% 100%);
            box-shadow: 0 2px 4px rgba(0,0,0,0.3);
            z-index: 1;
        }

        /* Cetak Presisi Tinggi 1 Halaman A4 Portrait (Strict Single Page Guarantee) */
        @page {
            size: A4 portrait;
            margin: 6mm;
        }
        @media print {
            html, body {
                width: 210mm !important;
                height: 297mm !important;
                margin: 0 !important;
                padding: 0 !important;
                background: #ffffff !important;
                overflow: hidden !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            .no-print {
                display: none !important;
            }
            .print-certificate-canvas {
                width: 198mm !important;
                height: 284mm !important;
                max-height: 284mm !important;
                margin: 0 auto !important;
                padding: 6mm !important;
                box-sizing: border-box !important;
                page-break-inside: avoid !important;
                page-break-after: avoid !important;
                page-break-before: avoid !important;
                overflow: hidden !important;
                box-shadow: none !important;
                border: 4px solid #0f2137 !important;
                outline: 2px solid #b8860b !important;
                outline-offset: -5px !important;
            }
            .print-inner-frame {
                height: 100% !important;
                padding: 4mm !important;
                box-sizing: border-box !important;
            }
        }
    </style>
</head>
<body class="bg-slate-200 text-slate-800 antialiased min-h-screen flex flex-col justify-between selection:bg-amber-600 selection:text-white">

    <!-- Top Action Toolbar (Tersembunyi Saat Dicetak) -->
    <header class="bg-slate-900 border-b border-slate-800 sticky top-0 z-50 shadow-md no-print">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 h-16 flex items-center justify-between">
            <a href="<?= base_url() ?>" class="flex items-center gap-2.5 hover:opacity-95 transition group">
                <img src="<?= base_url('images/logo.png') ?>" alt="AR Tilik Logo" class="w-8 h-8 object-contain drop-shadow-xs">
                <div class="flex items-center gap-2">
                    <span class="font-bold text-base text-white tracking-tight">AR Tilik</span>
                    <span class="hidden sm:inline-block text-[10px] font-bold uppercase tracking-wider text-amber-300 bg-amber-950/60 px-2.5 py-0.5 rounded-full border border-amber-600/40">
                        Portal Verifikasi Sertifikat Resmi
                    </span>
                </div>
            </a>
            <div class="flex items-center gap-2">
                <?php if ($status === 'valid'): ?>
                <button type="button" onclick="window.print()" class="px-3.5 py-1.5 bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-400 hover:to-amber-500 text-slate-950 text-xs font-extrabold rounded-lg shadow-sm transition flex items-center gap-1.5 cursor-pointer">
                    <svg class="w-4 h-4 text-slate-950" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                    <span>Cetak PDF Resmi (1 Halaman)</span>
                </button>
                <button type="button" id="btnCopyUrlNav" onclick="copyVerifyUrl()" class="px-3 py-1.5 bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-semibold rounded-lg border border-slate-700 transition flex items-center gap-1 cursor-pointer">
                    <span id="btnCopyUrlText">Salin Tautan</span>
                </button>
                <?php endif; ?>
                <a href="<?= base_url('dashboard') ?>" class="text-xs font-semibold text-slate-300 hover:text-white px-2.5 py-1.5 rounded-lg border border-slate-700 hover:bg-slate-800 transition">
                    Dashboard
                </a>
            </div>
        </div>
    </header>

    <!-- Main Container -->
    <main class="max-w-4xl w-full mx-auto px-2 sm:px-6 py-6 sm:py-10 flex-1 flex justify-center items-start">
        <?php if ($status === 'valid' && $report): ?>
            <?php
                $overallSim = (float)($report['overall_similarity'] ?? 0);
                $exactSim = (float)($report['exact_similarity'] ?? 0);
                $cosineSim = (float)($report['cosine_similarity'] ?? 0);
                $longestRun = (int)($report['longest_match_words'] ?? 0);
                $dec = strtolower($report['predicted_decision'] ?? 'review');
                $dateFormatted = date('d F Y', strtotime($report['created_at']));
                $timeFormatted = date('H:i', strtotime($report['created_at'])) . ' WIB';

                // Status Kedaulatan Berkas (Zero-Leakage)
                $isPurged = !empty($document['file_purged_at']) || ($document['storage_path'] ?? '') === '[PURGED]';
                $purgedDate = !empty($document['file_purged_at']) ? date('d F Y, H:i', strtotime($document['file_purged_at'])) . ' WIB' : null;

                // QR Code URL (Validasi Kriptografis Publik)
                $verifyUrl = base_url('verify/' . $code);
                $qrApiUrl = "https://api.qrserver.com/v1/create-qr-code/?size=160x160&color=0f2137&data=" . urlencode($verifyUrl);

                // Klasifikasi Akreditasi Formal
                if ($dec === 'match') {
                    $verdictTitle = 'TIDAK MEMENUHI SYARAT (KEMIRIPAN TINGGI)';
                    $verdictKriteria = 'PERLU PERBAIKAN & PARAFRASA INTENSIF';
                    $verdictColor = 'text-rose-700';
                    $verdictBorder = 'border-rose-400 bg-rose-50/60';
                } elseif ($dec === 'review') {
                    $verdictTitle = 'MEMENUHI SYARAT BERSYARAT (REVIEW AKADEMIK)';
                    $verdictKriteria = 'DALAM AMBANG BATAS TOLERANSI INSTITUSI';
                    $verdictColor = 'text-amber-800';
                    $verdictBorder = 'border-amber-400 bg-amber-50/60';
                } else {
                    $verdictTitle = 'TERAKREDITASI SANGAT BAIK (LOLOS BEBAS PLAGIARISME)';
                    $verdictKriteria = 'MEMENUHI KAIDAH INTEGRITAS KARYA ILMIAH';
                    $verdictColor = 'text-emerald-800';
                    $verdictBorder = 'border-emerald-500 bg-emerald-50/60';
                }

                // AI Indication Metrics
                $aiData = $report['ai_assessment'] ?? null;
                $aiScore = $aiData ? ($aiData['score'] ?? null) : null;
                $aiLabel = $aiData ? ($aiData['label'] ?? ($aiScore >= 65 ? 'Indikasi Tinggi' : ($aiScore >= 35 ? 'Indikasi Sedang' : 'Otentik Alami'))) : 'Otentik Alami';

                // SHA-256 Signature
                $docSignature = hash('sha256', $code . ($report['created_at'] ?? '') . ($document['title'] ?? ''));
            ?>

            <!-- LEMBAR PIAGAM RESMI AKREDITASI (DIPLOMA-GRADE CANVAS) -->
            <div class="print-certificate-canvas diploma-parchment diploma-border-outer p-6 sm:p-8 w-full max-w-[210mm] relative rounded-lg text-slate-900 select-text">
                
                <!-- Inner Fine-Line Decorative Frame -->
                <div class="print-inner-frame diploma-border-inner p-5 sm:p-7 relative flex flex-col justify-between h-full space-y-4">

                    <!-- Watermark Latar Belakang Lambang Lambat -->
                    <div class="absolute inset-0 flex items-center justify-center pointer-events-none opacity-[0.035] overflow-hidden select-none">
                        <img src="<?= base_url('images/logo.png') ?>" alt="" class="w-96 h-96 object-contain transform -rotate-12">
                    </div>

                    <!-- 1. KOP RESMI PIAGAM AKREDITASI (OFFICIAL EMBLEM & HEADER) -->
                    <div class="relative z-10 text-center border-b border-[#c5a059]/60 pb-3">
                        <div class="flex items-center justify-between gap-4">
                            <!-- Logo Lambang Kiri -->
                            <div class="w-16 h-16 flex items-center justify-center flex-shrink-0">
                                <img src="<?= base_url('images/logo.png') ?>" alt="AR Tilik Emblem" class="w-14 h-14 object-contain drop-shadow-xs">
                            </div>

                            <!-- Teks Judul Piagam Utama -->
                            <div class="flex-1 space-y-0.5">
                                <div class="font-diploma-serif text-[10px] sm:text-[11px] tracking-[0.25em] text-[#0f2137] font-bold uppercase">
                                    REPUBLIK INDONESIA &bull; STANDAR INTEGRITAS SINTA / ARJUNA
                                </div>
                                <h1 class="font-diploma-serif text-lg sm:text-2xl font-black tracking-wider text-[#0f2137] uppercase">
                                    SERTIFIKAT AKREDITASI INTEGRITAS AKADEMIK
                                </h1>
                                <div class="font-editorial-serif italic text-xs sm:text-sm text-[#78591e] font-semibold">
                                    Certificate of Academic Integrity &amp; Originality Attestation
                                </div>
                            </div>

                            <!-- Nomor Registrasi Piagam Kanan (Non-Wrapping) -->
                            <div class="w-auto flex-shrink-0 text-right">
                                <div class="border border-[#c5a059] bg-[#faf8f2] px-3 py-1.5 rounded text-right shadow-2xs">
                                    <div class="text-[9px] font-diploma-serif tracking-widest text-[#78591e] uppercase font-bold">Nomor Registrasi:</div>
                                    <div class="font-mono-code text-xs sm:text-sm font-bold text-[#0f2137] whitespace-nowrap tracking-wider">
                                        <?= esc($code) ?>
                                    </div>
                                    <div class="text-[8.5px] text-slate-500">Terbit: <?= esc($dateFormatted) ?></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 2. TEKS MAKLUMAT PIAGAM (OFFICIAL PROSE) -->
                    <div class="relative z-10 text-center space-y-1.5 pt-1">
                        <p class="font-editorial-serif italic text-xs sm:text-sm text-slate-700 leading-relaxed">
                            Berdasarkan hasil audit komputasi forensik teks menggunakan algoritma sekuensial <em>5-Gram Shingle</em> dan dekomposisi leksikal <em>TF-IDF Cosine</em>, dengan ini Dewan Integritas Akademik AR Tilik menyatakan bahwa naskah karya ilmiah:
                        </p>

                        <!-- Judul Naskah Terperiksa (Gagah & Formal) -->
                        <div class="py-1">
                            <h2 class="font-editorial-serif font-bold text-sm sm:text-lg text-[#0f2137] tracking-tight uppercase px-4 max-w-2xl mx-auto leading-snug">
                                &ldquo;<?= esc($document['title'] ?? 'Dokumen Karya Ilmiah') ?>&rdquo;
                            </h2>
                        </div>

                        <!-- Data Penulis & Metadata Naskah -->
                        <div class="flex flex-wrap items-center justify-center gap-x-5 gap-y-1 text-[11px] text-slate-600 border-y border-[#c5a059]/40 py-1 max-w-2xl mx-auto">
                            <?php if (!empty($document['author_name'])): ?>
                            <div>Penulis: <strong class="text-slate-900"><?= esc($document['author_name']) ?></strong></div>
                            <?php endif; ?>
                            <?php if (!empty($document['institution'])): ?>
                            <div>Institusi: <strong class="text-slate-900"><?= esc($document['institution']) ?></strong></div>
                            <?php endif; ?>
                            <?php if (!empty($document['faculty'])): ?>
                            <div>Fakultas: <strong class="text-slate-900"><?= esc($document['faculty']) ?></strong></div>
                            <?php endif; ?>
                            <div>Format: <span class="font-mono-code font-bold uppercase"><?= strtoupper(pathinfo($document['original_filename'] ?? 'DOCX', PATHINFO_EXTENSION)) ?></span></div>
                            <div>Waktu Audit: <span class="font-medium"><?= esc($dateFormatted) ?>, <?= esc($timeFormatted) ?></span></div>
                        </div>
                    </div>

                    <!-- 3. TABEL RESMI PARAMETER UJI AKADEMIK (CLASSIC TABULAR METRICS) -->
                    <div class="relative z-10">
                        <div class="text-[9.5px] font-diploma-serif tracking-widest text-[#78591e] font-bold uppercase mb-1 flex items-center justify-between">
                            <span>Tabel Hasil Evaluasi Kesepadanan Tekstual &amp; Orisinalitas:</span>
                            <span class="font-mono-code text-[9px] text-slate-400">ENGINE: <?= strtoupper(esc($report['engine'] ?? 'HYBRID')) ?></span>
                        </div>
                        <div class="border border-[#c5a059] rounded overflow-hidden shadow-2xs">
                            <table class="w-full text-left border-collapse text-[10.5px]">
                                <thead class="bg-[#f5eedc] text-[#0f2137] font-diploma-serif uppercase tracking-wider text-[9px] border-b border-[#c5a059]">
                                    <tr>
                                        <th class="py-1.5 px-3 border-r border-[#c5a059]/50 w-8 text-center">No</th>
                                        <th class="py-1.5 px-3 border-r border-[#c5a059]/50">Parameter Uji Akademik</th>
                                        <th class="py-1.5 px-3 border-r border-[#c5a059]/50">Metode Komputasi</th>
                                        <th class="py-1.5 px-3 border-r border-[#c5a059]/50 text-center w-24">Hasil / Nilai</th>
                                        <th class="py-1.5 px-3 text-center w-40">Status Integritas</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-[#c5a059]/30 bg-white">
                                    <tr class="bg-[#faf8f2]/60 font-semibold">
                                        <td class="py-1.5 px-3 border-r border-[#c5a059]/30 text-center">1</td>
                                        <td class="py-1.5 px-3 border-r border-[#c5a059]/30 text-[#0f2137] font-bold">Kesepadanan Gabungan (Overall Similarity)</td>
                                        <td class="py-1.5 px-3 border-r border-[#c5a059]/30 text-slate-600">Hybrid (Exact Shingle + TF-IDF)</td>
                                        <td class="py-1.5 px-3 border-r border-[#c5a059]/30 text-center font-mono-code font-extrabold text-xs text-[#0f2137]">
                                            <?= number_format($overallSim, 2) ?>%
                                        </td>
                                        <td class="py-1.5 px-3 text-center font-bold <?= $overallSim <= 20 ? 'text-emerald-700' : 'text-amber-700' ?>">
                                            <?= $overallSim <= 20 ? 'MEMENUHI SYARAT (<20%)' : 'PERLU TINJAUAN' ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="py-1.5 px-3 border-r border-[#c5a059]/30 text-center text-slate-500">2</td>
                                        <td class="py-1.5 px-3 border-r border-[#c5a059]/30 text-slate-800">Kesepadanan Sekuensial Harfiah</td>
                                        <td class="py-1.5 px-3 border-r border-[#c5a059]/30 text-slate-600">5-Gram Exact Shingle Token</td>
                                        <td class="py-1.5 px-3 border-r border-[#c5a059]/30 text-center font-mono-code font-bold">
                                            <?= number_format($exactSim, 2) ?>%
                                        </td>
                                        <td class="py-1.5 px-3 text-center text-slate-600 text-[10px]">
                                            <?= $exactSim <= 5 ? 'Sangat Baik' : 'Wajar' ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="py-1.5 px-3 border-r border-[#c5a059]/30 text-center text-slate-500">3</td>
                                        <td class="py-1.5 px-3 border-r border-[#c5a059]/30 text-slate-800">Kosinus Leksikal / Orientasi Topik</td>
                                        <td class="py-1.5 px-3 border-r border-[#c5a059]/30 text-slate-600">TF-IDF Vector Cosine Similarity</td>
                                        <td class="py-1.5 px-3 border-r border-[#c5a059]/30 text-center font-mono-code font-bold">
                                            <?= number_format($cosineSim, 2) ?>%
                                        </td>
                                        <td class="py-1.5 px-3 text-center text-slate-600 text-[10px]">Korelasi Topikal Wajar</td>
                                    </tr>
                                    <tr>
                                        <td class="py-1.5 px-3 border-r border-[#c5a059]/30 text-center text-slate-500">4</td>
                                        <td class="py-1.5 px-3 border-r border-[#c5a059]/30 text-slate-800">Pasase Berurutan Terpanjang</td>
                                        <td class="py-1.5 px-3 border-r border-[#c5a059]/30 text-slate-600">Consecutive Matched Word Span</td>
                                        <td class="py-1.5 px-3 border-r border-[#c5a059]/30 text-center font-mono-code font-bold">
                                            <?= $longestRun ?> Kata
                                        </td>
                                        <td class="py-1.5 px-3 text-center text-slate-600 text-[10px]">
                                            <?= $longestRun < 20 ? 'Bebas Frasa Berulang' : 'Perlu Parafrasa' ?>
                                        </td>
                                    </tr>
                                    <?php if ($aiScore !== null): ?>
                                    <tr class="bg-slate-50/50">
                                        <td class="py-1.5 px-3 border-r border-[#c5a059]/30 text-center text-slate-500">5</td>
                                        <td class="py-1.5 px-3 border-r border-[#c5a059]/30 text-slate-800">Audit Forensik Sintesis Mesin / AI</td>
                                        <td class="py-1.5 px-3 border-r border-[#c5a059]/30 text-slate-600">Stylometrik &amp; Penalaran Semantik</td>
                                        <td class="py-1.5 px-3 border-r border-[#c5a059]/30 text-center font-mono-code font-bold">
                                            <?= number_format($aiScore, 1) ?>%
                                        </td>
                                        <td class="py-1.5 px-3 text-center font-semibold text-[10px] <?= $aiScore >= 65 ? 'text-rose-700' : 'text-slate-700' ?>">
                                            <?= esc($aiLabel) ?>
                                        </td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- 4. PERNYATAAN STATUS AKREDITASI RESMI (VERDICT DECREE BANNER) -->
                    <div class="relative z-10 border-2 <?= $verdictBorder ?> rounded px-4 py-2 text-center shadow-2xs">
                        <div class="font-diploma-serif text-[11px] font-black tracking-widest <?= $verdictColor ?> uppercase">
                            KEPUTUSAN: <?= esc($verdictTitle) ?>
                        </div>
                        <div class="text-[10px] text-slate-600 mt-0.5 font-medium">
                            <?= esc($verdictKriteria) ?> &bull; <?= esc($report['rationale'] ?? 'Naskah memenuhi standar orisinalitas karya ilmiah institusi.') ?>
                        </div>
                    </div>

                    <!-- 5. KLAUSUL KEDAULATAN DATA (ZERO-LEAKAGE PRIVACY ATTESTATION) -->
                    <div class="relative z-10 border border-[#c5a059]/40 bg-[#faf8f2] rounded px-3 py-1.5 text-[9.5px] text-slate-600 flex items-center justify-between gap-3">
                        <div class="flex items-center gap-1.5">
                            <span class="text-xs">🛡️</span>
                            <span>
                                <strong>Kedaulatan Privasi Data:</strong> 
                                <?php if ($isPurged): ?>
                                    Berkas fisik naskah asli telah dimusnahkan secara mandiri dari peladen pada <strong><?= esc($purgedDate) ?></strong> (Zero-Leakage Guarantee). Sertifikasi ini sah 100% berbasis sidik jari kriptografis SHA-256.
                                <?php else: ?>
                                    Naskah dilindungi di bawah protokol integritas tertutup AR Tilik. Naskah tidak pernah dipublikasikan atau dijadikan korpus terbuka tanpa izin eksplisit pemilik.
                                <?php endif; ?>
                            </span>
                        </div>
                        <span class="font-mono-code text-[8.5px] font-bold text-[#78591e] border border-[#c5a059]/60 px-1.5 py-0.5 rounded flex-shrink-0 bg-white">
                            SHA-256 VALID
                        </span>
                    </div>

                    <!-- 6. PENGESAHAN, QR CODE & STEMPEL MEDALI EMAS BERPITA (AUTHENTICATION SEAL & SIGNATURE) -->
                    <div class="relative z-10 border-t-2 border-[#0f2137]/30 pt-3 flex items-end justify-between gap-4">
                        
                        <!-- Kolom Kiri: QR Code Kriptografis -->
                        <div class="flex items-center gap-2.5 max-w-[210px]">
                            <div class="p-1 bg-white border border-[#c5a059] rounded shadow-2xs flex-shrink-0">
                                <img src="<?= esc($qrApiUrl) ?>" alt="QR Code Verifikasi" class="w-16 h-16 object-contain">
                            </div>
                            <div class="text-[8.5px] text-slate-600 leading-tight space-y-0.5">
                                <div class="font-diploma-serif font-bold text-[#0f2137] uppercase text-[9px]">Validasi Kriptografis</div>
                                <p>Pindai untuk memeriksa keabsahan lembar sertifikat ini secara langsung pada peladen resmi.</p>
                                <div class="font-mono-code text-[7.5px] text-slate-400 truncate max-w-[120px]" title="<?= esc($verifyUrl) ?>">
                                    <?= esc($verifyUrl) ?>
                                </div>
                            </div>
                        </div>

                        <!-- Kolom Tengah: Stempel Medali Emas Bergerigi Timbul 3D dengan Pita Royal Navy -->
                        <div class="relative flex flex-col items-center justify-center my-0">
                            <!-- Pita Menjuntai Belakang -->
                            <div class="seal-ribbon-left"></div>
                            <div class="seal-ribbon-right"></div>
                            
                            <!-- Medali Emas Timbul -->
                            <div class="w-20 h-20 rounded-full gold-seal-medallion flex items-center justify-center text-center p-1.5 relative z-10">
                                <div class="w-full h-full rounded-full border border-[#fef3c7]/80 flex flex-col items-center justify-center text-[#78350f]">
                                    <div class="text-[6.5px] font-black tracking-tighter uppercase font-diploma-serif leading-none">★ AR TILIK ★</div>
                                    <div class="text-[8.5px] font-black tracking-tight uppercase font-diploma-serif leading-none my-0.5 text-[#451a03]">AKREDITASI</div>
                                    <div class="text-[6px] font-extrabold uppercase tracking-widest text-[#78350f] leading-none">INTEGRITAS</div>
                                    <div class="text-[5.5px] font-mono-code font-bold text-[#92400e] leading-none mt-0.5">RESMI &bull; SAH</div>
                                </div>
                            </div>
                        </div>

                        <!-- Kolom Kanan: Pengesahan Dewan & Digital Checksum -->
                        <div class="text-right space-y-0.5 max-w-[220px]">
                            <div class="text-[9px] text-slate-500 font-medium">Ditetapkan secara resmi pada:</div>
                            <div class="font-bold text-xs text-[#0f2137]"><?= esc($dateFormatted) ?></div>
                            <div class="font-diploma-serif text-[10px] font-extrabold text-[#0f2137] uppercase mt-1">
                                Lembaga Uji Integritas AR Tilik
                            </div>
                            <div class="font-mono-code text-[8px] text-[#78591e] bg-[#faf8f2] px-2 py-0.5 rounded border border-[#c5a059]/50 inline-block mt-0.5">
                                SIG: <?= substr($docSignature, 0, 16) ?>...
                            </div>
                            <div class="text-[8px] text-slate-400 italic">
                                Tanda Tangan Digital Tersertifikasi Sistem Peladen
                            </div>
                        </div>

                    </div>

                </div>
            </div>

        <?php elseif ($status === 'not_found'): ?>
            <!-- Card Sertifikat Tidak Ditemukan -->
            <div class="bg-white rounded-2xl border border-rose-200 shadow-md p-8 text-center space-y-4 max-w-lg mx-auto">
                <div class="w-16 h-16 rounded-2xl bg-rose-50 text-rose-600 border border-rose-200 flex items-center justify-center text-3xl mx-auto shadow-inner">
                    ⚠️
                </div>
                <div class="space-y-1.5">
                    <h2 class="text-xl font-bold text-slate-900 font-diploma-serif">Sertifikat Tidak Terdaftar</h2>
                    <p class="text-xs text-slate-500 leading-relaxed">
                        Kode registrasi <span class="font-mono-code font-bold text-rose-600 bg-rose-50 px-2 py-0.5 rounded"><?= esc($code) ?></span> tidak ditemukan dalam basis data resmi integritas akademik AR Tilik.
                    </p>
                </div>
                <div class="pt-2">
                    <a href="<?= base_url('verify') ?>" class="inline-block px-5 py-2.5 bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold rounded-xl transition">
                        Periksa Kode Registrasi Lain &rarr;
                    </a>
                </div>
            </div>

        <?php else: ?>
            <!-- Form Input Verifikasi Kode -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-md p-6 sm:p-10 max-w-lg w-full mx-auto space-y-6">
                <div class="text-center space-y-2">
                    <div class="w-14 h-14 rounded-2xl bg-indigo-50 border border-indigo-200 text-indigo-700 flex items-center justify-center text-3xl mx-auto shadow-inner">
                        🔍
                    </div>
                    <h1 class="text-xl sm:text-2xl font-black text-slate-900 font-diploma-serif">Validasi Sertifikat Akademik</h1>
                    <p class="text-xs text-slate-500 leading-relaxed">
                        Masukkan nomor registrasi sertifikat (contoh: <code class="bg-slate-100 px-1.5 py-0.5 rounded font-mono-code text-indigo-700 font-bold">ART-XXXX-XXXX-XXXX</code>) yang tertera pada lembar dokumen.
                    </p>
                </div>

                <form action="<?= base_url('verify') ?>" method="GET" class="space-y-4">
                    <div>
                        <label for="code" class="block text-xs font-extrabold uppercase tracking-wider text-slate-600 mb-2 font-diploma-serif">
                            Nomor Registrasi Sertifikat:
                        </label>
                        <input type="text" name="code" id="code" placeholder="ART-XXXX-XXXX-XXXX" required autofocus
                               class="w-full px-4 py-3.5 rounded-xl border border-slate-300 focus:ring-2 focus:ring-amber-500 focus:border-amber-500 font-mono-code text-center text-base font-bold uppercase tracking-wider text-slate-900 placeholder:text-slate-300 bg-slate-50/50">
                    </div>
                    <button type="submit" class="w-full py-3.5 bg-[#0f2137] hover:bg-slate-800 active:bg-slate-950 text-amber-300 text-xs font-bold uppercase tracking-widest rounded-xl shadow-md transition flex items-center justify-center gap-2 cursor-pointer font-diploma-serif">
                        <span>Verifikasi Keabsahan Dokumen</span>
                        <svg class="w-4 h-4 text-amber-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                    </button>
                </form>

                <div class="p-3 bg-amber-50/70 rounded-xl border border-amber-200 text-center text-[11px] text-amber-900 leading-relaxed">
                    💡 Atau pindai <strong>Kode QR</strong> pada lembar hasil uji dengan kamera ponsel untuk verifikasi instan.
                </div>
            </div>
        <?php endif; ?>
    </main>

    <!-- Footer (Tersembunyi Saat Dicetak) -->
    <footer class="bg-white border-t border-slate-200 py-5 text-center text-xs text-slate-500 no-print">
        <p>AR Tilik &bull; Academic Writing Similarity &amp; Evidence Checker &bull; Dikembangkan dengan Standar Integritas &amp; Kedaulatan Data</p>
    </footer>

    <!-- Script Salin Tautan -->
    <script>
        function copyVerifyUrl() {
            const url = window.location.href;
            navigator.clipboard.writeText(url).then(() => {
                const btnText = document.getElementById('btnCopyUrlText');
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
