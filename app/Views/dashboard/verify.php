<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $status === 'valid' ? 'Sertifikat Akreditasi Integritas Akademik #' . esc($code) . ' — AR Tilik' : 'Verifikasi Sertifikat Resmi — AR Tilik | Portal Validasi QR Ilmiah' ?></title>
    <meta name="description" content="Portal Verifikasi Resmi Sertifikat Keaslian Hasil Telaah Kesepadanan Naskah AR Tilik. Dokumen akreditasi integritas karya ilmiah berstandar perguruan tinggi berstempel digital kriptografis.">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="<?= base_url('verify' . (!empty($code) ? '/' . esc($code) : '')) ?>">
    <meta name="theme-color" content="#081a33">

    <!-- Open Graph -->
    <meta property="og:type" content="website">
    <meta property="og:locale" content="id_ID">
    <meta property="og:site_name" content="AR Tilik">
    <meta property="og:title" content="<?= $status === 'valid' ? 'Sertifikat Akreditasi Integritas Akademik #' . esc($code) . ' — AR Tilik' : 'Verifikasi Sertifikat Resmi — AR Tilik' ?>">
    <meta property="og:description" content="Lembar Piagam Resmi Akreditasi Integritas dan Keaslian Karya Ilmiah AR Tilik.">
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
                radial-gradient(circle at 50% 50%, #fffefc 0%, #fcf9f2 80%, #f8f2e2 100%),
                radial-gradient(circle at 10% 10%, rgba(197, 160, 89, 0.04) 0%, transparent 50%),
                radial-gradient(circle at 90% 90%, rgba(197, 160, 89, 0.04) 0%, transparent 50%);
        }

        /* Bingkai Klasik Ganda Akreditasi (Double Guilloche Frame) */
        .diploma-border-outer {
            border: 4.5px solid #081a33;
            outline: 2px solid #c5a059;
            outline-offset: -6px;
            box-shadow: 0 20px 40px -15px rgba(8, 26, 51, 0.25), inset 0 0 70px rgba(197, 160, 89, 0.08);
        }
        @media (max-width: 640px) {
            .diploma-border-outer {
                border-width: 3.5px;
                outline-width: 1.5px;
                outline-offset: -5px;
                box-shadow: 0 10px 25px -10px rgba(8, 26, 51, 0.25);
            }
        }
        .diploma-border-inner {
            border: 1px solid #c5a059;
            box-shadow: inset 0 0 0 0.5px rgba(8, 26, 51, 0.15);
        }

        /* Stempel Medali Emas Timbul 3D dengan Tekstur Bergerigi */
        .gold-seal-medallion {
            background: radial-gradient(circle at 35% 30%, #fffdf0 0%, #fed7aa 25%, #d97706 65%, #78350f 100%);
            border: 2px dashed #fef3c7;
            box-shadow: 
                0 4px 14px rgba(120, 53, 15, 0.4),
                inset 0 2px 5px rgba(255, 255, 255, 0.9),
                inset 0 -3px 6px rgba(69, 26, 3, 0.7);
        }

        /* Pita Menjuntai Stempel Emas */
        .seal-ribbon-left {
            position: absolute;
            bottom: -16px;
            left: 14px;
            width: 24px;
            height: 40px;
            background: linear-gradient(135deg, #081a33 0%, #1e3a8a 100%);
            clip-path: polygon(0% 0%, 100% 0%, 100% 100%, 50% 80%, 0% 100%);
            box-shadow: 0 2px 5px rgba(0,0,0,0.3);
            z-index: 1;
        }
        .seal-ribbon-right {
            position: absolute;
            bottom: -16px;
            right: 14px;
            width: 24px;
            height: 40px;
            background: linear-gradient(225deg, #081a33 0%, #1e3a8a 100%);
            clip-path: polygon(0% 0%, 100% 0%, 100% 100%, 50% 80%, 0% 100%);
            box-shadow: 0 2px 5px rgba(0,0,0,0.3);
            z-index: 1;
        }

        /* Cetak Presisi Tinggi 1 Halaman A4 Landscape */
        @page {
            size: A4 landscape;
            margin: 0;
        }
        @media print {
            html, body {
                width: 297mm !important;
                height: 210mm !important;
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
                width: 297mm !important;
                height: 210mm !important;
                max-width: 297mm !important;
                max-height: 210mm !important;
                margin: 0 !important;
                padding: 6.5mm !important;
                box-sizing: border-box !important;
                page-break-inside: avoid !important;
                page-break-after: avoid !important;
                page-break-before: avoid !important;
                overflow: hidden !important;
                box-shadow: none !important;
                border: 4.5px solid #081a33 !important;
                outline: 2px solid #c5a059 !important;
                outline-offset: -6px !important;
            }
            .print-inner-frame {
                height: 100% !important;
                padding: 5mm 6.5mm !important;
                box-sizing: border-box !important;
            }
            .print-desktop-kop {
                display: flex !important;
            }
            .print-mobile-kop {
                display: none !important;
            }
            .print-desktop-footer {
                display: flex !important;
                flex-direction: row !important;
                align-items: flex-end !important;
            }
            .print-desktop-footer .order-1 {
                order: 2 !important;
                margin-bottom: 0 !important;
            }
            .print-desktop-footer .order-2 {
                order: 1 !important;
            }
            .print-desktop-footer .order-3 {
                order: 3 !important;
                text-align: right !important;
            }
            .print-pillars-grid {
                grid-template-columns: repeat(4, minmax(0, 1fr)) !important;
            }
        }
    </style>
</head>
<body class="bg-slate-900 text-slate-800 antialiased min-h-screen flex flex-col justify-between selection:bg-amber-600 selection:text-white">

    <!-- Top Action Toolbar (Tersembunyi Saat Dicetak) -->
    <header class="bg-slate-950/90 backdrop-blur-md border-b border-slate-800 sticky top-0 z-50 shadow-md no-print">
        <div class="max-w-7xl mx-auto px-3 sm:px-6 h-14 sm:h-16 flex items-center justify-between gap-2">
            <a href="<?= base_url() ?>" class="flex items-center gap-2 sm:gap-3 hover:opacity-95 transition group shrink-0">
                <img src="<?= base_url('images/logo.png') ?>" alt="AR Tilik Logo" class="w-7 h-7 sm:w-8 sm:h-8 object-contain drop-shadow-xs">
                <div class="flex items-center gap-2">
                    <span class="font-bold text-sm sm:text-base text-white tracking-tight">AR Tilik</span>
                    <span class="hidden md:inline-block text-[10px] font-bold uppercase tracking-wider text-amber-300 bg-amber-950/60 px-2.5 py-0.5 rounded-full border border-amber-600/40">
                        Portal Verifikasi Piagam Resmi
                    </span>
                </div>
            </a>
            <div class="flex items-center gap-1.5 sm:gap-2.5 shrink-0">
                <?php if ($status === 'valid'): ?>
                <a href="<?= base_url('verify/' . esc($code) . '/pdf') ?>" download class="px-2.5 sm:px-3.5 py-1.5 bg-gradient-to-r from-amber-400 via-amber-500 to-amber-600 hover:from-amber-300 hover:to-amber-500 text-slate-950 text-xs font-black rounded-lg shadow-sm hover:shadow transition flex items-center gap-1 sm:gap-1.5 cursor-pointer border border-amber-300 whitespace-nowrap">
                    <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4 text-slate-950 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    <span class="hidden md:inline">Unduh Piagam PDF Resmi (A4)</span>
                    <span class="hidden sm:inline md:hidden">Unduh PDF A4</span>
                    <span class="sm:hidden font-bold">Unduh PDF</span>
                </a>
                <button type="button" onclick="window.print()" class="hidden md:flex px-3 py-1.5 bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-semibold rounded-lg border border-slate-700 transition items-center gap-1.5 cursor-pointer">
                    <svg class="w-4 h-4 text-slate-300 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                    <span>Cetak</span>
                </button>
                <button type="button" id="btnCopyUrlNav" onclick="copyVerifyUrl()" class="px-2 sm:px-2.5 py-1.5 bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-semibold rounded-lg border border-slate-700 transition flex items-center gap-1 cursor-pointer whitespace-nowrap">
                    <svg class="w-3.5 h-3.5 text-slate-300 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                    <span class="hidden sm:inline" id="btnCopyUrlText">Salin Tautan</span>
                    <span class="sm:hidden" id="btnCopyUrlTextMobile">Salin</span>
                </button>
                <?php endif; ?>
                <a href="<?= base_url('dashboard') ?>" class="text-xs font-semibold text-slate-300 hover:text-white px-2 sm:px-2.5 py-1.5 rounded-lg border border-slate-700 hover:bg-slate-800 transition whitespace-nowrap shrink-0">
                    Dashboard
                </a>
            </div>
        </div>
    </header>

    <!-- Main Container -->
    <main class="max-w-7xl w-full mx-auto px-2 sm:px-6 py-6 sm:py-10 flex-1 flex justify-center items-start">
        <?php if ($status === 'valid' && $report): ?>
            <?php
                $overallSim = (float)($report['overall_similarity'] ?? 0);
                $exactSim = (float)($report['exact_similarity'] ?? 0);
                $cosineSim = (float)($report['cosine_similarity'] ?? 0);
                $longestRun = (int)($report['longest_match_words'] ?? 0);
                $originalityRate = max(0.0, 100.0 - $overallSim);
                $dec = strtolower($report['predicted_decision'] ?? 'review');
                $dateFormatted = date('d F Y', strtotime($report['created_at']));
                $timeFormatted = date('H:i', strtotime($report['created_at'])) . ' WIB';

                // Status Kedaulatan Berkas (Zero-Leakage)
                $isPurged = !empty($document['file_purged_at']) || ($document['storage_path'] ?? '') === '[PURGED]';
                $purgedDate = !empty($document['file_purged_at']) ? date('d F Y, H:i', strtotime($document['file_purged_at'])) . ' WIB' : null;

                // QR Code URL (Validasi Kriptografis Publik)
                $verifyUrl = base_url('verify/' . $code);
                $qrApiUrl = "https://api.qrserver.com/v1/create-qr-code/?size=180x180&color=081a33&data=" . urlencode($verifyUrl);

                // Klasifikasi Akreditasi Formal
                if ($dec === 'match') {
                    $verdictTitle = 'TIDAK MEMENUHI SYARAT';
                    $verdictKriteria = 'KEMIRIPAN TINGGI • PERLU PERBAIKAN & PARAFRASA';
                    $verdictColor = 'text-rose-800';
                    $verdictBorder = 'border-rose-400 bg-rose-50/80';
                } elseif ($dec === 'review') {
                    $verdictTitle = 'MEMENUHI SYARAT BERSYARAT';
                    $verdictKriteria = 'DALAM AMBANG BATAS TOLERANSI REVIEW AKADEMIK';
                    $verdictColor = 'text-amber-800';
                    $verdictBorder = 'border-amber-400 bg-amber-50/80';
                } else {
                    $verdictTitle = 'TERAKREDITASI SANGAT BAIK';
                    $verdictKriteria = 'LOLOS AMBANG BATAS ORISINALITAS ILMIAH';
                    $verdictColor = 'text-emerald-800';
                    $verdictBorder = 'border-emerald-500 bg-emerald-50/80';
                }

                // AI Indication Metrics
                $aiData = $report['ai_assessment'] ?? null;
                $aiScore = $aiData ? ($aiData['score'] ?? null) : null;
                $aiLabel = $aiData ? ($aiData['label'] ?? null) : null;
                if (!$aiLabel) {
                    $aiLabel = ($aiScore !== null && $aiScore >= 70) ? 'Indikasi AI: Tinggi' : (($aiScore !== null && $aiScore >= 35) ? 'Indikasi AI: Sedang' : 'Otentik Alami');
                }

                // SHA-256 Signature
                $docSignature = hash('sha256', $code . ($report['created_at'] ?? '') . ($document['title'] ?? '') . $overallSim);
                $sigPreview = substr($docSignature, 0, 8) . '-' . substr($docSignature, 8, 8) . '-' . substr($docSignature, 16, 8) . '...' . substr($docSignature, -8);
            ?>

            <!-- LEMBAR PIAGAM RESMI AKREDITASI (DIPLOMA-GRADE CANVAS LANDSCAPE A4) -->
            <div class="print-certificate-canvas diploma-parchment diploma-border-outer p-3.5 sm:p-7 w-full max-w-[1140px] relative rounded-lg text-slate-900 select-text shadow-2xl overflow-hidden">
                
                <!-- Inner Fine-Line Decorative Frame -->
                <div class="print-inner-frame diploma-border-inner p-3 sm:p-6 relative flex flex-col justify-between h-full space-y-3 sm:space-y-3.5">

                    <!-- 4 Sudut Ornamen Vektor Guilloche -->
                    <svg class="absolute top-1 left-1 w-6 h-6 sm:w-14 sm:h-14 pointer-events-none opacity-60 sm:opacity-85 z-10" viewBox="0 0 100 100" fill="none">
                        <path d="M0,0 L60,0 C40,0 20,20 20,40 C20,20 0,40 0,60 Z" fill="#c5a059"/>
                        <path d="M5,5 L80,5 C60,5 30,25 25,60 C25,30 5,60 5,80 Z" stroke="#081a33" stroke-width="1.5" fill="none"/>
                        <circle cx="20" cy="20" r="4" fill="#c5a059"/>
                    </svg>
                    <svg class="absolute top-1 right-1 w-6 h-6 sm:w-14 sm:h-14 pointer-events-none opacity-60 sm:opacity-85 z-10 scale-x-[-1]" viewBox="0 0 100 100" fill="none">
                        <path d="M0,0 L60,0 C40,0 20,20 20,40 C20,20 0,40 0,60 Z" fill="#c5a059"/>
                        <path d="M5,5 L80,5 C60,5 30,25 25,60 C25,30 5,60 5,80 Z" stroke="#081a33" stroke-width="1.5" fill="none"/>
                        <circle cx="20" cy="20" r="4" fill="#c5a059"/>
                    </svg>
                    <svg class="absolute bottom-1 left-1 w-6 h-6 sm:w-14 sm:h-14 pointer-events-none opacity-60 sm:opacity-85 z-10 scale-y-[-1]" viewBox="0 0 100 100" fill="none">
                        <path d="M0,0 L60,0 C40,0 20,20 20,40 C20,20 0,40 0,60 Z" fill="#c5a059"/>
                        <path d="M5,5 L80,5 C60,5 30,25 25,60 C25,30 5,60 5,80 Z" stroke="#081a33" stroke-width="1.5" fill="none"/>
                        <circle cx="20" cy="20" r="4" fill="#c5a059"/>
                    </svg>
                    <svg class="absolute bottom-1 right-1 w-6 h-6 sm:w-14 sm:h-14 pointer-events-none opacity-60 sm:opacity-85 z-10 scale-[-1]" viewBox="0 0 100 100" fill="none">
                        <path d="M0,0 L60,0 C40,0 20,20 20,40 C20,20 0,40 0,60 Z" fill="#c5a059"/>
                        <path d="M5,5 L80,5 C60,5 30,25 25,60 C25,30 5,60 5,80 Z" stroke="#081a33" stroke-width="1.5" fill="none"/>
                        <circle cx="20" cy="20" r="4" fill="#c5a059"/>
                    </svg>

                    <!-- Watermark Latar Belakang Lambang -->
                    <div class="absolute inset-0 flex items-center justify-center pointer-events-none opacity-[0.035] overflow-hidden select-none">
                        <img src="<?= base_url('images/logo.png') ?>" alt="" class="w-80 h-80 object-contain">
                    </div>

                    <!-- 1. KOP RESMI PIAGAM AKREDITASI -->
                    <div class="relative z-20 text-center border-b border-[#c5a059] pb-3">
                        <!-- Desktop Layout (md and up): 3 Columns -->
                        <div class="hidden md:flex print-desktop-kop items-center justify-between gap-4">
                            <!-- Logo Lambang Kiri -->
                            <div class="w-16 h-16 flex items-center justify-center shrink-0">
                                <img src="<?= base_url('images/logo.png') ?>" alt="AR Tilik Emblem" class="w-14 h-14 object-contain drop-shadow-xs">
                            </div>

                            <!-- Teks Judul Piagam Utama -->
                            <div class="flex-1 space-y-0.5 text-center">
                                <div class="font-sans text-[11px] tracking-[0.28em] text-[#081a33] font-extrabold uppercase">
                                    REPUBLIK INDONESIA &bull; STANDAR INTEGRITAS SINTA / ARJUNA
                                </div>
                                <h1 class="font-diploma-serif text-2xl font-black tracking-wider text-[#081a33] uppercase">
                                    SERTIFIKAT AKREDITASI INTEGRITAS AKADEMIK
                                </h1>
                                <div class="font-editorial-serif italic text-sm text-[#875c16] font-semibold">
                                    Certificate of Academic Integrity &amp; Originality Attestation
                                </div>
                            </div>

                            <!-- Nomor Registrasi Piagam Kanan -->
                            <div class="w-auto shrink-0 text-right">
                                <div class="border border-[#c5a059] bg-[#fffdf8] px-3.5 py-1.5 rounded text-right shadow-2xs">
                                    <div class="text-[9px] font-sans tracking-widest text-[#875c16] uppercase font-bold">Nomor Registrasi:</div>
                                    <div class="font-mono-code text-sm font-bold text-[#081a33] whitespace-nowrap tracking-wider">
                                        <?= esc($code) ?>
                                    </div>
                                    <div class="text-[8.5px] text-slate-500">Terbit: <?= esc($dateFormatted) ?></div>
                                </div>
                            </div>
                        </div>

                        <!-- Mobile Layout (< md): Clean Vertical Stack -->
                        <div class="md:hidden print-mobile-kop flex flex-col items-center gap-2">
                            <img src="<?= base_url('images/logo.png') ?>" alt="AR Tilik Emblem" class="w-12 h-12 object-contain drop-shadow-xs">
                            <div class="font-sans text-[8.5px] sm:text-[9.5px] tracking-[0.2em] text-[#081a33] font-extrabold uppercase leading-tight">
                                REPUBLIK INDONESIA &bull; STANDAR INTEGRITAS SINTA / ARJUNA
                            </div>
                            <h1 class="font-diploma-serif text-base sm:text-lg font-black tracking-wide text-[#081a33] uppercase leading-snug px-1">
                                SERTIFIKAT AKREDITASI INTEGRITAS AKADEMIK
                            </h1>
                            <div class="font-editorial-serif italic text-xs text-[#875c16] font-semibold">
                                Certificate of Academic Integrity &amp; Originality Attestation
                            </div>
                            <div class="mt-1 border border-[#c5a059] bg-[#fffdf8] px-3 py-1.5 rounded text-center shadow-2xs w-full max-w-[280px]">
                                <div class="text-[8px] font-sans tracking-widest text-[#875c16] uppercase font-bold">Nomor Registrasi</div>
                                <div class="font-mono-code text-xs font-bold text-[#081a33] tracking-wide break-all">
                                    <?= esc($code) ?>
                                </div>
                                <div class="text-[8px] text-slate-500">Terbit: <?= esc($dateFormatted) ?></div>
                            </div>
                        </div>
                    </div>

                    <!-- 2. TEKS MAKLUMAT PIAGAM (OFFICIAL PROSE) -->
                    <div class="relative z-20 text-center space-y-1 pt-0.5">
                        <p class="font-editorial-serif italic text-xs sm:text-sm text-slate-700 leading-relaxed max-w-4xl mx-auto px-1">
                            Berdasarkan hasil audit komputasi forensik teks menggunakan algoritma sekuensial <em>5-Gram Shingle</em> dan dekomposisi leksikal <em>TF-IDF Cosine</em>, dengan ini Dewan Integritas Akademik AR Tilik menyatakan bahwa naskah karya ilmiah:
                        </p>

                        <!-- Judul Naskah Terperiksa (Formal & Elegan) -->
                        <div class="py-1.5 border-y border-[#c5a059]/40 bg-[#fffdf8]/60 max-w-4xl mx-auto px-2 sm:px-4 my-1">
                            <h2 class="font-diploma-serif font-black text-xs sm:text-base md:text-lg text-[#081a33] tracking-tight uppercase leading-snug break-words">
                                &ldquo;<?= esc($document['title'] ?? 'Dokumen Karya Ilmiah') ?>&rdquo;
                            </h2>
                        </div>

                        <!-- Data Penulis & Metadata Naskah -->
                        <div class="flex flex-wrap items-center justify-center gap-x-3 sm:gap-x-5 gap-y-1 text-[10px] sm:text-[11px] text-slate-600 max-w-4xl mx-auto px-1">
                            <?php if (!empty($document['author'])): ?>
                                <div>Penulis: <strong class="text-[#081a33]"><?= esc($document['author']) ?></strong></div>
                                <?php if (!empty($document['uploader_name']) && $document['uploader_name'] !== $document['author']): ?>
                                    <div>Pengunggah: <span class="font-medium text-slate-700"><?= esc($document['uploader_name']) ?></span></div>
                                <?php endif; ?>
                            <?php else: ?>
                                <div>Pengunggah: <strong class="text-[#081a33]"><?= esc($document['uploader_name'] ?? $document['author_name'] ?? 'Pengguna Terdaftar') ?></strong></div>
                            <?php endif; ?>
                            <?php if (!empty($document['institution'])): ?>
                            <div>Institusi: <strong class="text-[#081a33]"><?= esc($document['institution']) ?></strong></div>
                            <?php endif; ?>
                            <?php if (!empty($document['faculty'])): ?>
                            <div>Fakultas / Prodi: <strong class="text-[#081a33]"><?= esc($document['faculty']) ?><?= !empty($document['department']) ? ' / ' . esc($document['department']) : '' ?></strong></div>
                            <?php endif; ?>
                            <div>Format: <span class="font-mono-code font-bold uppercase"><?= strtoupper(pathinfo($document['original_filename'] ?? 'DOCX', PATHINFO_EXTENSION)) ?></span></div>
                            <div>Waktu Audit: <span class="font-medium"><?= esc($dateFormatted) ?>, <?= esc($timeFormatted) ?></span></div>
                        </div>
                    </div>

                    <!-- 3. DIKTUM KEPUTUSAN AKREDITASI RESMI (GRAND DECREE BANNER) -->
                    <div class="relative z-20 border-2 <?= $verdictBorder ?> rounded px-3 sm:px-4 py-2 text-center shadow-2xs max-w-4xl mx-auto w-full">
                        <div class="font-diploma-serif text-xs sm:text-sm font-black tracking-widest <?= $verdictColor ?> uppercase leading-snug">
                            KEPUTUSAN: <?= esc($verdictTitle) ?>
                        </div>
                        <div class="text-[10px] sm:text-[11px] text-slate-700 mt-1 font-medium flex flex-wrap items-center justify-center gap-x-2 gap-y-0.5">
                            <span><?= esc($verdictKriteria) ?></span>
                            <span class="hidden sm:inline">&bull;</span>
                            <span>Indeks Kesepadanan: <strong class="font-mono-code text-xs font-black <?= $verdictColor ?>"><?= number_format($overallSim, 2) ?>%</strong></span>
                            <span class="hidden sm:inline">&bull;</span>
                            <span>Tingkat Orisinalitas: <strong class="font-mono-code text-xs font-black text-emerald-800"><?= number_format($originalityRate, 2) ?>%</strong></span>
                        </div>
                        <div class="text-[9px] sm:text-[9.5px] text-slate-500 italic mt-0.5">
                            <?= esc($report['rationale'] ?? 'Naskah memenuhi kriteria integritas karya ilmiah perguruan tinggi.') ?>
                        </div>
                    </div>

                    <!-- 4. CATUR PILAR METRIK INTEGRITAS (HERALDIC 4 PILLARS) -->
                    <div class="relative z-20 grid grid-cols-2 md:grid-cols-4 print-pillars-grid gap-2 sm:gap-2.5 max-w-4xl mx-auto w-full">
                        <!-- Pilar I -->
                        <div class="border border-[#c5a059] bg-gradient-to-b from-[#fffdfa] to-[#faf6ec] rounded p-1.5 sm:p-2 text-center shadow-2xs">
                            <div class="text-[8px] sm:text-[9px] font-bold tracking-widest text-[#875c16] uppercase">Pilar I &bull; Sekuensial</div>
                            <div class="text-[9.5px] sm:text-[10.5px] font-bold text-[#081a33] mt-0.5 leading-tight">Kesepadanan Harfiah</div>
                            <div class="font-mono-code text-xs sm:text-sm font-black text-[#081a33] my-0.5"><?= number_format($exactSim, 2) ?>%</div>
                            <div class="text-[8px] sm:text-[8.5px] text-slate-500">5-Gram Exact Shingle</div>
                            <div class="inline-block text-[7.5px] sm:text-[8px] font-bold uppercase tracking-wider px-1.5 py-0.5 rounded bg-amber-100/60 text-[#78510a] border border-[#c5a059]/40 mt-1 max-w-full truncate">
                                <?= $exactSim <= 5.0 ? 'Sangat Baik' : 'Wajar' ?>
                            </div>
                        </div>

                        <!-- Pilar II -->
                        <div class="border border-[#c5a059] bg-gradient-to-b from-[#fffdfa] to-[#faf6ec] rounded p-1.5 sm:p-2 text-center shadow-2xs">
                            <div class="text-[8px] sm:text-[9px] font-bold tracking-widest text-[#875c16] uppercase">Pilar II &bull; Orientasi</div>
                            <div class="text-[9.5px] sm:text-[10.5px] font-bold text-[#081a33] mt-0.5 leading-tight">Kosinus Leksikal / Topik</div>
                            <div class="font-mono-code text-xs sm:text-sm font-black text-[#081a33] my-0.5"><?= number_format($cosineSim, 2) ?>%</div>
                            <div class="text-[8px] sm:text-[8.5px] text-slate-500">TF-IDF Vector Cosine</div>
                            <div class="inline-block text-[7.5px] sm:text-[8px] font-bold uppercase tracking-wider px-1.5 py-0.5 rounded bg-amber-100/60 text-[#78510a] border border-[#c5a059]/40 mt-1 max-w-full truncate">
                                Korelasi Wajar
                            </div>
                        </div>

                        <!-- Pilar III -->
                        <div class="border border-[#c5a059] bg-gradient-to-b from-[#fffdfa] to-[#faf6ec] rounded p-1.5 sm:p-2 text-center shadow-2xs">
                            <div class="text-[8px] sm:text-[9px] font-bold tracking-widest text-[#875c16] uppercase">Pilar III &bull; Pasase</div>
                            <div class="text-[9.5px] sm:text-[10.5px] font-bold text-[#081a33] mt-0.5 leading-tight">Rangkaian Terpanjang</div>
                            <div class="font-mono-code text-xs sm:text-sm font-black text-[#081a33] my-0.5"><?= $longestRun ?> Kata</div>
                            <div class="text-[8px] sm:text-[8.5px] text-slate-500">Word Span</div>
                            <div class="inline-block text-[7.5px] sm:text-[8px] font-bold uppercase tracking-wider px-1.5 py-0.5 rounded bg-amber-100/60 text-[#78510a] border border-[#c5a059]/40 mt-1 max-w-full truncate">
                                <?= $longestRun < 20 ? 'Bebas Frasa Berulang' : 'Perlu Parafrasa' ?>
                            </div>
                        </div>

                        <!-- Pilar IV -->
                        <div class="border border-[#c5a059] bg-gradient-to-b from-[#fffdfa] to-[#faf6ec] rounded p-1.5 sm:p-2 text-center shadow-2xs">
                            <div class="text-[8px] sm:text-[9px] font-bold tracking-widest text-[#875c16] uppercase">Pilar IV &bull; Sintesis</div>
                            <div class="text-[9.5px] sm:text-[10.5px] font-bold text-[#081a33] mt-0.5 leading-tight">Forensik Sintesis Mesin</div>
                            <div class="font-mono-code text-xs sm:text-sm font-black <?= ($aiScore !== null && $aiScore >= 70) ? 'text-rose-700' : (($aiScore !== null && $aiScore >= 35) ? 'text-amber-700' : 'text-[#081a33]') ?> my-0.5"><?= $aiScore !== null ? number_format($aiScore, 1) . '%' : 'Alami' ?></div>
                            <div class="text-[8px] sm:text-[8.5px] text-slate-500">Forensik Cerdas</div>
                            <div class="inline-block text-[7.5px] sm:text-[8px] font-bold uppercase tracking-wider px-1.5 py-0.5 rounded <?= ($aiScore !== null && $aiScore >= 70) ? 'bg-rose-100/90 text-rose-800 border border-rose-300' : (($aiScore !== null && $aiScore >= 35) ? 'bg-amber-100/60 text-[#78510a] border border-[#c5a059]/40' : 'bg-emerald-100/60 text-emerald-800 border border-emerald-300') ?> mt-1 max-w-full truncate">
                                <?= esc($aiLabel) ?>
                            </div>
                        </div>
                    </div>

                    <!-- 5. KLAUSUL KEDAULATAN PRIVASI DATA -->
                    <div class="relative z-20 border border-[#c5a059]/50 bg-[#fffdfa] rounded px-2.5 sm:px-3 py-1.5 text-[8.5px] sm:text-[9.5px] text-slate-600 text-center max-w-4xl mx-auto w-full leading-relaxed">
                        🛡️ <strong>Kedaulatan Privasi Data (Zero-Leakage Guarantee):</strong>
                        <?php if ($isPurged): ?>
                            Berkas fisik naskah asli telah dimusnahkan secara mandiri dari peladen pada <strong><?= esc($purgedDate) ?></strong> (Zero-Leakage Guarantee). Sertifikasi ini sah 100% berbasis sidik jari kriptografis SHA-256.
                        <?php else: ?>
                            Naskah dilindungi di bawah protokol integritas tertutup AR Tilik. Naskah tidak pernah dipublikasikan atau dijadikan korpus terbuka tanpa izin eksplisit pemilik.
                        <?php endif; ?>
                    </div>

                    <!-- 6. PENGESAHAN, QR CODE & STEMPEL MEDALI EMAS BERPITA -->
                    <div class="relative z-20 border-t-2 border-[#081a33]/25 pt-4 pb-2 flex flex-col md:flex-row print-desktop-footer items-center md:items-end justify-between gap-5 md:gap-4 max-w-4xl mx-auto w-full">
                        
                        <!-- Kolom Kiri: QR Code Kriptografis -->
                        <div class="flex items-center gap-2.5 max-w-[260px] order-2 md:order-1 text-left">
                            <div class="p-1 bg-white border border-[#c5a059] rounded shadow-2xs shrink-0">
                                <img src="<?= esc($qrApiUrl) ?>" alt="QR Code Verifikasi" class="w-14 h-14 sm:w-16 sm:h-16 object-contain">
                            </div>
                            <div class="text-[9px] text-slate-600 leading-tight space-y-0.5">
                                <div class="font-diploma-serif font-bold text-[#081a33] uppercase text-[9.5px]">Validasi Kriptografis</div>
                                <p class="text-[8px] sm:text-[9px]">Pindai untuk memvalidasi keabsahan piagam ini secara langsung pada peladen resmi.</p>
                                <div class="font-mono-code text-[7.5px] text-slate-400 truncate max-w-[140px]" title="<?= esc($verifyUrl) ?>">
                                    <?= esc($verifyUrl) ?>
                                </div>
                            </div>
                        </div>

                        <!-- Kolom Tengah: Stempel Medali Emas Bergerigi Timbul 3D dengan Pita Royal Navy -->
                        <div class="relative flex flex-col items-center justify-center my-0 order-1 md:order-2 shrink-0 mb-4 md:mb-0">
                            <!-- Pita Menjuntai Belakang -->
                            <div class="seal-ribbon-left"></div>
                            <div class="seal-ribbon-right"></div>
                            
                            <!-- Medali Emas Timbul -->
                            <div class="w-20 h-20 rounded-full gold-seal-medallion flex items-center justify-center text-center p-1.5 relative z-10">
                                <div class="w-full h-full rounded-full border border-[#fef3c7]/80 flex flex-col items-center justify-center text-[#78350f]">
                                    <div class="text-[6.5px] font-black tracking-tighter uppercase font-diploma-serif leading-none">★ AR TILIK ★</div>
                                    <div class="text-[8.5px] font-black tracking-tight uppercase font-diploma-serif leading-none my-0.5 text-[#451a03]">AKREDITASI</div>
                                    <div class="text-[6px] font-extrabold uppercase tracking-widest text-[#78350f] leading-none">INTEGRITAS</div>
                                    <div class="text-[5.5px] font-mono-code font-bold text-[#92400e] leading-none mt-0.5">★ ★ ★ ★ ★</div>
                                </div>
                            </div>
                        </div>

                        <!-- Kolom Kanan: Pengesahan Dewan & Digital Checksum -->
                        <div class="text-center md:text-right space-y-0.5 max-w-[260px] order-3">
                            <div class="text-[8.5px] sm:text-[9px] text-slate-500 font-medium">Ditetapkan secara resmi pada:</div>
                            <div class="font-bold text-xs text-[#081a33]"><?= esc($dateFormatted) ?></div>
                            <div class="font-diploma-serif text-[9.5px] sm:text-[10px] font-extrabold text-[#081a33] uppercase mt-0.5 sm:mt-1">
                                Lembaga Uji Integritas AR Tilik
                            </div>
                            <div class="font-mono-code text-[7.5px] sm:text-[8px] text-[#875c16] bg-[#fffdf8] px-2 py-0.5 rounded border border-[#c5a059]/50 inline-block mt-0.5">
                                SIG: <?= esc($sigPreview) ?>
                            </div>
                            <div class="text-[7.5px] sm:text-[8px] text-slate-400 italic">
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
                        Nomor registrasi <span class="font-mono-code font-bold text-rose-600 bg-rose-50 px-2 py-0.5 rounded"><?= esc($code) ?></span> tidak ditemukan dalam pangkalan data resmi integritas akademik AR Tilik.
                    </p>
                </div>
                <div class="pt-2">
                    <a href="<?= base_url('verify') ?>" class="inline-block px-5 py-2.5 bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold rounded-xl transition">
                        Periksa Nomor Registrasi Lain &rarr;
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
                        Masukkan nomor registrasi sertifikat (contoh: <code class="bg-slate-100 px-1.5 py-0.5 rounded font-mono-code text-indigo-700 font-bold">ART-BFF9-774A-2C68</code>) yang tertera pada lembar dokumen atau piagam akreditasi.
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
                    <button type="submit" class="w-full py-3.5 bg-[#081a33] hover:bg-slate-800 active:bg-slate-950 text-amber-300 text-xs font-bold uppercase tracking-widest rounded-xl shadow-md transition flex items-center justify-center gap-2 cursor-pointer font-diploma-serif">
                        <span>Verifikasi Keabsahan Dokumen</span>
                        <svg class="w-4 h-4 text-amber-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                    </button>
                </form>

                <div class="p-3 bg-amber-50/70 rounded-xl border border-amber-200 text-center text-[11px] text-amber-900 leading-relaxed">
                    💡 Atau pindai <strong>Kode QR</strong> pada lembar piagam atau laporan hasil uji dengan kamera ponsel untuk verifikasi seketika.
                </div>
            </div>
        <?php endif; ?>
    </main>

    <!-- Footer (Tersembunyi Saat Dicetak) -->
    <footer class="bg-slate-950 border-t border-slate-800 py-5 text-center text-xs text-slate-400 no-print">
        <p>AR Tilik &bull; Academic Writing Similarity &amp; Evidence Checker &bull; Dikembangkan dengan Standar Integritas &amp; Kedaulatan Data</p>
    </footer>

    <!-- Script Salin Tautan -->
    <script>
        function copyVerifyUrl() {
            const url = window.location.href;
            navigator.clipboard.writeText(url).then(() => {
                const btnText = document.getElementById('btnCopyUrlText');
                const btnTextMobile = document.getElementById('btnCopyUrlTextMobile');
                if (btnText) {
                    const orig = btnText.innerText;
                    btnText.innerText = 'Tersalin!';
                    setTimeout(() => { btnText.innerText = orig; }, 2000);
                }
                if (btnTextMobile) {
                    const origM = btnTextMobile.innerText;
                    btnTextMobile.innerText = 'Tersalin!';
                    setTimeout(() => { btnTextMobile.innerText = origM; }, 2000);
                }
            }).catch(() => {
                alert('Tautan disalin ke clipboard: ' + url);
            });
        }
    </script>

</body>
</html>
