<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Bukti Similarity #<?= $report['id'] ?> - AR Tilik</title>
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
    <!-- Navbar -->
    <nav class="bg-white border-b border-slate-200 sticky top-0 z-30 shadow-xs">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16 items-center">
                <div class="flex items-center gap-3">
                    <a href="<?= base_url('dashboard') ?>" class="w-10 h-10 rounded-xl bg-indigo-600 text-white flex items-center justify-center font-bold text-xl shadow-md shadow-indigo-100 hover:bg-indigo-700 transition">
                        AR
                    </a>
                    <div>
                        <div class="flex items-center gap-2">
                            <a href="<?= base_url('dashboard') ?>" class="text-xl font-bold bg-gradient-to-r from-indigo-700 to-indigo-500 bg-clip-text text-transparent hover:opacity-90">AR Tilik</a>
                            <span class="text-xs px-2 py-0.5 rounded-full bg-indigo-50 text-indigo-700 font-semibold border border-indigo-100">Laporan #<?= $report['id'] ?></span>
                            <span class="text-[10px] uppercase font-bold tracking-wider px-2 py-0.5 rounded-full bg-slate-100 text-slate-600 border border-slate-200">
                                Engine: <?= strtoupper(esc($report['engine'] ?? 'hybrid')) ?>
                            </span>
                        </div>
                        <p class="text-[11px] text-slate-400">Academic Writing Similarity & Evidence Checker</p>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <a href="<?= base_url('dashboard') ?>" class="text-xs font-semibold text-slate-600 hover:text-indigo-600 flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-slate-200 bg-slate-50 hover:bg-white transition">
                        ← Kembali ke Dashboard
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
            <div class="flex sm:flex-col items-center sm:items-end justify-between border-t md:border-t-0 pt-4 md:pt-0 border-slate-100">
                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Overall Similarity</span>
                <div class="text-3xl sm:text-4xl font-extrabold <?= $report['overall_similarity'] >= 70 ? 'text-rose-600' : ($report['overall_similarity'] >= 10 ? 'text-amber-600' : 'text-emerald-600') ?>">
                    <?= number_format($report['overall_similarity'], 2) ?>%
                </div>
                <span class="text-[11px] text-slate-400">Skor Terpadu</span>
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
        ?>

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

            <!-- Tier 4: SOURCE EVIDENCE & [View Passage] -->
            <div class="p-6 bg-white space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-900 font-mono-code">
                            SOURCE EVIDENCE
                        </span>
                        <span class="text-xs text-slate-400">(<?= count($sources) ?> sumber terdeteksi)</span>
                    </div>
                </div>

                <?php if (empty($sources)): ?>
                    <p class="text-xs text-slate-400 py-4 text-center">Tidak ditemukan dokumen korpus rujukan yang beririsan.</p>
                <?php else: ?>
                    <div class="divide-y divide-slate-100 max-h-[480px] overflow-y-auto pr-1">
                        <?php foreach ($sources as $idx => $src): ?>
                            <div class="py-3.5 first:pt-0 last:pb-0 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                <div class="space-y-1">
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs font-bold font-mono-code px-2 py-0.5 rounded bg-slate-100 text-slate-700">
                                            #<?= $src['source_rank'] ?>
                                        </span>
                                        <h4 class="font-bold text-sm text-slate-900">
                                            <?= esc($src['source_title']) ?>
                                        </h4>
                                        <span class="text-[11px] uppercase font-semibold px-2 py-0.5 rounded bg-slate-100 text-slate-600">
                                            <?= esc($src['source_type'] ?? 'dokumen') ?>
                                        </span>
                                    </div>
                                    <div class="text-xs text-slate-500 font-mono-code flex flex-wrap items-center gap-3">
                                        <span><?= number_format($src['matched_words']) ?> matching words</span>
                                        <span>•</span>
                                        <span>Exact: <strong><?= number_format($src['exact_similarity'] ?? $src['similarity'], 1) ?>%</strong></span>
                                        <span>•</span>
                                        <span>Cosine: <strong><?= number_format($src['cosine_similarity'] ?? 0, 1) ?>%</strong></span>
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
        </div>

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
                        <div class="border border-slate-200 rounded-xl p-4 bg-slate-50/50 hover:bg-white hover:border-indigo-300 transition-all space-y-3">
                            <div class="flex flex-wrap items-center justify-between gap-2 pb-2 border-b border-slate-200/60">
                                <div class="flex items-center gap-2">
                                    <span class="text-xs font-bold px-2 py-0.5 rounded bg-slate-200 text-slate-700 font-mono-code">#<?= $idx + 1 ?></span>
                                    <span class="text-xs font-bold text-slate-800 truncate max-w-sm">Sumber: <?= esc($m['source_title']) ?></span>
                                </div>
                                <div class="flex items-center gap-2">
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
                content.innerHTML = '<p class="text-xs text-slate-400 text-center py-6">Tidak ada pasase teks sekuensial untuk dokumen ini (hanya kemiripan leksikal kosinus global).</p>';
            } else {
                sourceMatches.forEach((m, idx) => {
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
                    `;
                    content.appendChild(block);
                });
            }

            document.getElementById('passageModal').classList.remove('hidden');
        }

        function closePassageModal() {
            document.getElementById('passageModal').classList.add('hidden');
        }

        // Close modal on backdrop click or Escape key
        document.getElementById('passageModal').addEventListener('click', function(e) {
            if (e.target === this) closePassageModal();
        });
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') closePassageModal();
        });
    </script>
</body>
</html>
