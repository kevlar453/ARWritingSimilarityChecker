<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Telaah Kasus <?= esc($item['case_id']) ?> [<?= $currentIndex ?> / <?= $totalCases ?>] - AR Tilik</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .font-mono-code { font-family: 'JetBrains Mono', monospace; }
        .radio-card:has(input[type="radio"]:checked) {
            background-color: rgb(238 242 255) !important;
            border-color: rgb(79 70 229) !important;
            box-shadow: 0 0 0 1px rgb(79 70 229) !important;
        }
    </style>
</head>
<body class="bg-slate-100 text-slate-800 antialiased min-h-screen">

    <!-- Top Navigation -->
    <nav class="bg-white border-b border-slate-200 sticky top-0 z-30 shadow-xs">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 py-3 flex justify-between items-center">
            <div class="flex items-center gap-3">
                <a href="<?= base_url('evaluation') ?>" class="text-xs font-semibold text-slate-500 hover:text-indigo-600 flex items-center gap-1 transition">
                    ← Kembali ke Antrean
                </a>
                <span class="text-slate-300">|</span>
                <span class="text-xs font-bold text-slate-700">HUMAN REVIEW</span>
                <span class="text-xs px-2 py-0.5 rounded font-mono-code bg-indigo-50 text-indigo-700 font-bold border border-indigo-100">
                    <?= esc($item['case_id']) ?>
                </span>
                <span class="text-xs text-slate-400 font-medium">Kasus ke-<?= $currentIndex ?> dari <?= $totalCases ?></span>
            </div>
            <div class="flex items-center gap-2 text-xs">
                <?php if ($prevCase): ?>
                    <a href="<?= base_url('evaluation/' . $prevCase) ?>" class="px-2.5 py-1 rounded border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 transition">
                        ← Sebelumnya
                    </a>
                <?php endif; ?>
                <?php if ($nextCase): ?>
                    <a href="<?= base_url('evaluation/' . $nextCase) ?>" class="px-2.5 py-1 rounded border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 transition">
                        Berikutnya →
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <!-- Main Container -->
    <main class="max-w-5xl mx-auto px-4 sm:px-6 py-6 space-y-6">

        <!-- Flash Messages -->
        <?php if (session()->getFlashdata('error')): ?>
            <div class="bg-rose-50 border border-rose-200 text-rose-800 px-4 py-3 rounded-xl text-xs flex items-center gap-2">
                <svg class="w-4 h-4 text-rose-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span><?= esc(session()->getFlashdata('error')) ?></span>
            </div>
        <?php endif; ?>
        <?php if (session()->getFlashdata('success')): ?>
            <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-xl text-xs flex items-center gap-2">
                <svg class="w-4 h-4 text-emerald-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                <span><?= esc(session()->getFlashdata('success')) ?></span>
            </div>
        <?php endif; ?>

        <!-- Case Overview Header -->
        <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-xs">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2 mb-1">
                        <span class="px-2.5 py-0.5 rounded text-xs font-bold bg-indigo-100 text-indigo-800">
                            <?= esc($item['priority_tier']) ?>
                        </span>
                        <?php if ($item['review_status'] === 'REVIEWED' || $item['review_status'] === 'VALIDATED'): ?>
                            <span class="px-2 py-0.5 rounded text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                                Sudah Ditelaah
                            </span>
                        <?php else: ?>
                            <span class="px-2 py-0.5 rounded text-xs font-bold bg-amber-100 text-amber-800 border border-amber-200">
                                Menunggu Telaah
                            </span>
                        <?php endif; ?>
                    </div>
                    <h1 class="text-xl font-bold text-slate-900"><?= esc($item['case_id']) ?>: <?= esc($item['priority_reason']) ?></h1>
                </div>

                <div class="text-right sm:border-l sm:border-slate-200 sm:pl-6">
                    <span class="text-xs text-slate-400 block font-medium">Progres Telaah</span>
                    <span class="text-2xl font-extrabold text-indigo-700"><?= $currentIndex ?> <span class="text-base text-slate-400 font-normal">/ <?= $totalCases ?></span></span>
                </div>
            </div>
        </div>

        <!-- 1. SYSTEM RESULT CARD -->
        <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-xs">
            <div class="flex items-center justify-between mb-4 border-b border-slate-100 pb-3">
                <div class="flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-indigo-600"></span>
                    <h2 class="text-sm font-bold uppercase tracking-wider text-slate-700">Hasil Analisis Engine v1.0.0 (System Result)</h2>
                </div>
                <span class="text-[11px] font-semibold text-slate-400 font-mono-code">FROZEN ENGINE OUTPUT</span>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                <div class="bg-slate-50 border border-slate-200 rounded-xl p-3 text-center">
                    <span class="text-[11px] text-slate-500 font-medium block">Keputusan Sistem</span>
                    <?php
                    $pClass = strtoupper($item['predicted_class']);
                    $badgeCls = match($pClass) {
                        'MATCH' => 'text-rose-700 bg-rose-50 border-rose-200',
                        'REVIEW' => 'text-amber-700 bg-amber-50 border-amber-200',
                        default => 'text-emerald-700 bg-emerald-50 border-emerald-200',
                    };
                    ?>
                    <span class="inline-block mt-1 px-3 py-0.5 rounded-full text-xs font-extrabold border <?= $badgeCls ?>">
                        <?= $pClass ?>
                    </span>
                </div>

                <div class="bg-slate-50 border border-slate-200 rounded-xl p-3 text-center">
                    <span class="text-[11px] text-slate-500 font-medium block">Pola Bukti (Pattern)</span>
                    <span class="font-mono-code text-xs font-bold text-slate-800 block mt-1">
                        <?= esc($item['predicted_pattern']) ?>
                    </span>
                </div>

                <div class="bg-slate-50 border border-slate-200 rounded-xl p-3 text-center">
                    <span class="text-[11px] text-slate-500 font-medium block">Exact Sequential</span>
                    <span class="font-mono-code text-lg font-extrabold text-slate-800 block mt-0.5">
                        <?= number_format($item['exact_similarity'], 2) ?>%
                    </span>
                    <span class="text-[10px] text-slate-400">Pasase: <?= $item['longest_match_words'] ?> kata</span>
                </div>

                <div class="bg-slate-50 border border-slate-200 rounded-xl p-3 text-center">
                    <span class="text-[11px] text-slate-500 font-medium block">TF-IDF Cosine</span>
                    <span class="font-mono-code text-lg font-extrabold text-slate-800 block mt-0.5">
                        <?= number_format($item['cosine_similarity'], 2) ?>%
                    </span>
                    <span class="text-[10px] text-slate-400">Terms: <?= $item['matched_terms'] ?? 0 ?>, Avg IDF: <?= number_format($item['avg_idf'] ?? 2.5, 2) ?></span>
                </div>
            </div>

            <!-- Explainability Rationale -->
            <div class="mt-4 p-3.5 bg-indigo-50/50 border border-indigo-100 rounded-xl text-xs text-indigo-900 leading-relaxed">
                <span class="font-bold text-indigo-800 block mb-0.5">Keterangan Rasionalitas Bukti:</span>
                <?= esc($item['rationale']) ?>
            </div>
        </div>

        <!-- 2. EVIDENCE COMPARISON DUAL-PANE -->
        <div class="space-y-3">
            <div class="bg-amber-50/80 border border-amber-200 text-amber-900 px-4 py-2.5 rounded-xl text-xs flex flex-col sm:flex-row sm:items-center justify-between gap-2 shadow-xs">
                <div class="flex items-center gap-2">
                    <span class="px-1.5 py-0.5 rounded bg-amber-200 text-amber-950 font-bold border border-amber-300 text-[10px] tracking-wider">HIGHLIGHT</span>
                    <span>Pasase berurutan yang bersesuaian (matching phrase sequence ≥ 3 kata) disorot otomatis dengan latar kuning untuk memudahkan komparasi visual.</span>
                </div>
                <span class="text-[11px] font-semibold text-amber-800 font-mono-code flex-shrink-0">EXACT PASASE: <?= $item['longest_match_words'] ?> KATA</span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Submission Excerpt -->
                <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-xs flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-3 pb-2 border-b border-slate-100">
                            <div class="flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-700">Teks Submisi (Submission Text)</h3>
                            </div>
                            <span class="text-[10px] text-slate-400 font-mono-code"><?= str_word_count(strip_tags($item['submission_text'])) ?> KATA</span>
                        </div>
                        <div class="text-xs text-slate-800 leading-relaxed bg-slate-50 p-4 rounded-xl border border-slate-200 font-mono-code whitespace-pre-wrap max-h-72 overflow-y-auto">
<?= $highlightedSubmission ?>
                        </div>
                    </div>
                </div>

                <!-- Source Excerpt -->
                <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-xs flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-3 pb-2 border-b border-slate-100">
                            <div class="flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-700">Teks Sumber Rujukan (Source Text)</h3>
                            </div>
                            <span class="text-[10px] text-slate-400 font-mono-code"><?= str_word_count(strip_tags($item['source_text'])) ?> KATA</span>
                        </div>
                        <div class="text-xs text-slate-800 leading-relaxed bg-slate-50 p-4 rounded-xl border border-slate-200 font-mono-code whitespace-pre-wrap max-h-72 overflow-y-auto">
<?= $highlightedSource ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 3. HUMAN REVIEW FORM -->
        <form method="POST" action="<?= base_url('evaluation/' . $item['case_id'] . '/save') ?>" id="reviewForm" class="bg-white border border-slate-200 rounded-2xl p-6 shadow-xs space-y-6">
            <?= csrf_field() ?>

            <div class="border-b border-slate-100 pb-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div>
                    <div class="flex items-center gap-2">
                        <h2 class="text-base font-bold text-slate-900">Form Telaah & Validasi Manusia (Review Form)</h2>
                        <span class="text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded bg-amber-50 text-amber-800 border border-amber-200">
                            Post-Release Evaluation Layer
                        </span>
                    </div>
                    <p class="text-xs text-slate-500 mt-0.5">Human judgment untuk evaluasi pasca-rilis; bukan perubahan terhadap keputusan engine.</p>
                    <p class="text-[11px] text-amber-700 mt-1.5 bg-amber-50/60 p-2 rounded-lg border border-amber-200/60 flex items-center gap-1.5 leading-normal">
                        <svg class="w-4 h-4 text-amber-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span><strong>Catatan Metodologis:</strong> Penilaian Anda (misal <em>CORRECT / ACCEPTABLE</em>) merupakan evaluasi terhadap ketepatan kerja sistem, bukan pengganti (<em>replacement</em>) atas keputusan klasifikasi engine v1.0.0.</span>
                    </p>
                </div>
                <div class="flex items-center gap-2 text-xs flex-shrink-0 self-start sm:self-center">
                    <label class="font-medium text-slate-600">Kode Reviewer:</label>
                    <input type="text" name="reviewer_code" value="<?= esc($existing['reviewer_code'] ?? 'RV-001') ?>" class="border border-slate-200 rounded-lg px-2.5 py-1 text-xs font-mono-code w-24 text-center bg-slate-50">
                </div>
            </div>

            <?php
            // Pre-selected values ONLY if existing record exists in database
            $curInterp = $existing['human_interpretation'] ?? null;
            $curCtx    = $existing['citation_context'] ?? null;
            $curStat   = $existing['citation_status'] ?? null;
            $curAssess = $existing['system_assessment'] ?? null;
            $curConf   = $existing['reviewer_confidence'] ?? null;
            $curNotes  = $existing['notes'] ?? '';
            ?>

            <!-- A. Human Interpretation (Evidence Relevance) -->
            <div>
                <div class="flex items-center justify-between mb-2">
                    <label class="text-xs font-bold uppercase tracking-wider text-slate-700">
                        A. Interpretasi Manusia Terhadap Relevansi Bukti (Human Interpretation):
                    </label>
                    <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-rose-50 text-rose-700 border border-rose-200 uppercase tracking-wider">* wajib pilih</span>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2.5">
                    <?php
                    $interpOpts = [
                        'DIRECT_OVERLAP_RELEVANT' => ['label' => 'Direct Overlap Relevant', 'desc' => 'Kesamaan nyata & relevan untuk ditindaklanjuti'],
                        'COMMON_ACADEMIC_LANGUAGE' => ['label' => 'Common Academic Language', 'desc' => 'Frasa ilmiah konvensional lazim'],
                        'METHODOLOGY_BOILERPLATE' => ['label' => 'Methodology Boilerplate', 'desc' => 'Formula metodologis/teknis standar'],
                        'NEEDS_INVESTIGATION' => ['label' => 'Needs Investigation', 'desc' => 'Memerlukan pemeriksaan naskah asli lebih lanjut'],
                        'FALSE_POSITIVE' => ['label' => 'False Positive', 'desc' => 'Bukan kesamaan substantif yang bermakna'],
                        'INSUFFICIENT_EVIDENCE' => ['label' => 'Insufficient Evidence', 'desc' => 'Bukti tekstual terlalu minim/lemah'],
                    ];
                    foreach ($interpOpts as $val => $info):
                    ?>
                        <label class="radio-card relative flex items-start p-3 rounded-xl border border-slate-200 hover:border-indigo-400 cursor-pointer transition bg-slate-50/50">
                            <input type="radio" name="human_interpretation" value="<?= $val ?>" <?= $curInterp === $val ? 'checked' : '' ?> required class="mt-0.5 text-indigo-600 focus:ring-indigo-500">
                            <div class="ml-2.5">
                                <span class="block text-xs font-bold text-slate-800"><?= $info['label'] ?></span>
                                <span class="block text-[11px] text-slate-500 leading-tight mt-0.5"><?= $info['desc'] ?></span>
                            </div>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- B. Citation Context & C. Citation Status -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-4 border-t border-slate-100">
                <!-- B. Citation Context -->
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <label class="text-xs font-bold uppercase tracking-wider text-slate-700">
                            B. Konteks Sitasi Bukti (Citation Context):
                        </label>
                        <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-rose-50 text-rose-700 border border-rose-200 uppercase tracking-wider">* wajib pilih</span>
                    </div>
                    <div class="space-y-1.5 bg-slate-50 p-3.5 rounded-xl border border-slate-200 text-xs">
                        <?php
                        $ctxOpts = [
                            'NONE' => 'None (Tidak ada tanda sitasi)',
                            'INLINE_CITATION' => 'Inline Citation (Menurut Sugiyono...)',
                            'PARENTHETICAL_CITATION' => 'Parenthetical Citation ((Davis, 1989))',
                            'BLOCK_QUOTE' => 'Block Quote (Kutipan blok terpisah)',
                            'DIRECT_QUOTE' => 'Direct Quote (Kutipan langsung bertanda petik)',
                            'FOOTNOTE' => 'Footnote (Catatan kaki)',
                            'BIBLIOGRAPHY' => 'Bibliography (Daftar pustaka)',
                            'UNKNOWN' => 'Unknown / Tidak terdefinisi',
                        ];
                        foreach ($ctxOpts as $val => $txt):
                        ?>
                            <label class="flex items-center gap-2 cursor-pointer p-1 rounded hover:bg-slate-100">
                                <input type="radio" name="citation_context" value="<?= $val ?>" <?= $curCtx === $val ? 'checked' : '' ?> required class="text-indigo-600 focus:ring-indigo-500">
                                <span class="text-slate-800 text-xs"><?= $txt ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- C. Citation Status -->
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <label class="text-xs font-bold uppercase tracking-wider text-slate-700">
                            C. Status Akademik Bukti (Citation Status):
                        </label>
                        <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-rose-50 text-rose-700 border border-rose-200 uppercase tracking-wider">* wajib pilih</span>
                    </div>
                    <div class="space-y-1.5 bg-slate-50 p-3.5 rounded-xl border border-slate-200 text-xs">
                        <?php
                        $statOpts = [
                            'NOT_CITED' => 'Not Cited (Tidak disitir)',
                            'CITED' => 'Cited (Disitir dengan sumber)',
                            'PARTIALLY_CITED' => 'Partially Cited (Disitir sebagian)',
                            'QUOTE' => 'Quote (Kutipan formal bertanda petik)',
                            'PARAPHRASED_WITH_CITATION' => 'Paraphrased with Citation (Parafrase beratribusi)',
                            'NOT_APPLICABLE' => 'Not Applicable (Tidak relevan disitir)',
                            'UNKNOWN' => 'Unknown',
                        ];
                        foreach ($statOpts as $val => $txt):
                        ?>
                            <label class="flex items-center gap-2 cursor-pointer p-1 rounded hover:bg-slate-100">
                                <input type="radio" name="citation_status" value="<?= $val ?>" <?= $curStat === $val ? 'checked' : '' ?> required class="text-indigo-600 focus:ring-indigo-500">
                                <span class="text-slate-800 text-xs"><?= $txt ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- D. System Assessment & E. Confidence -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-4 border-t border-slate-100">
                <!-- D. System Assessment -->
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <label class="text-xs font-bold uppercase tracking-wider text-slate-700">
                            D. Penilaian Terhadap Sistem (System Assessment):
                        </label>
                        <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-rose-50 text-rose-700 border border-rose-200 uppercase tracking-wider">* wajib pilih</span>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                        <?php
                        $assessOpts = [
                            'CORRECT' => ['label' => 'Correct', 'desc' => 'Keputusan & evidensi tepat'],
                            'ACCEPTABLE' => ['label' => 'Acceptable', 'desc' => 'Dapat diterima secara akademik'],
                            'TOO_STRICT' => ['label' => 'Too Strict', 'desc' => 'Sistem terlalu keras / ketat'],
                            'TOO_LENIENT' => ['label' => 'Too Lenient', 'desc' => 'Sistem terlalu longgar'],
                            'UNCERTAIN' => ['label' => 'Uncertain', 'desc' => 'Ragu / tidak dapat disimpulkan'],
                        ];
                        foreach ($assessOpts as $val => $info):
                        ?>
                            <label class="radio-card flex items-start p-2.5 rounded-lg border border-slate-200 hover:border-indigo-400 cursor-pointer transition bg-slate-50/50">
                                <input type="radio" name="system_assessment" value="<?= $val ?>" <?= $curAssess === $val ? 'checked' : '' ?> required class="mt-0.5 text-indigo-600 focus:ring-indigo-500">
                                <div class="ml-2">
                                    <span class="block text-xs font-bold text-slate-800"><?= $info['label'] ?></span>
                                    <span class="block text-[10px] text-slate-500 leading-tight"><?= $info['desc'] ?></span>
                                </div>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- E. Confidence -->
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <label class="text-xs font-bold uppercase tracking-wider text-slate-700">
                            E. Tingkat Keyakinan Reviewer (Confidence):
                        </label>
                        <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-rose-50 text-rose-700 border border-rose-200 uppercase tracking-wider">* wajib pilih</span>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5">
                        <?php
                        $confOpts = [
                            'HIGH'   => ['label' => 'High', 'desc' => 'Sangat Yakin'],
                            'MEDIUM' => ['label' => 'Medium', 'desc' => 'Cukup Yakin'],
                            'LOW'    => ['label' => 'Low', 'desc' => 'Kurang Yakin'],
                        ];
                        foreach ($confOpts as $val => $info):
                        ?>
                            <label class="radio-card flex items-center gap-2.5 p-3 rounded-xl border border-slate-200 hover:border-indigo-400 cursor-pointer transition bg-slate-50/50">
                                <input type="radio" name="reviewer_confidence" value="<?= $val ?>" <?= $curConf === $val ? 'checked' : '' ?> required class="text-indigo-600 focus:ring-indigo-500 w-4 h-4">
                                <div>
                                    <span class="block text-xs font-bold text-slate-800"><?= $info['label'] ?></span>
                                    <span class="block text-[10px] text-slate-500 leading-tight"><?= $info['desc'] ?></span>
                                </div>
                            </label>
                        <?php endforeach; ?>
                    </div>

                    <!-- F. Notes -->
                    <div class="mt-4">
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                            F. Catatan Reviewer (Reviewer Notes):
                        </label>
                        <textarea name="notes" rows="2" placeholder="Tuliskan catatan akademik spesifik terkait pasase ini..." class="w-full text-xs p-2.5 rounded-lg border border-slate-200 focus:ring-1 focus:ring-indigo-500 font-mono-code"><?= esc($curNotes) ?></textarea>
                    </div>
                </div>
            </div>

            <!-- Submit Button Bar -->
            <div class="pt-4 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <a href="<?= base_url('evaluation') ?>" class="text-xs font-semibold text-slate-500 hover:text-slate-800 transition">
                        ← Batal & Antrean
                    </a>
                    <button type="button" id="resetFormBtn" class="text-xs text-slate-400 hover:text-rose-600 transition underline">
                        Kosongkan Pilihan
                    </button>
                </div>

                <div class="flex items-center gap-3">
                    <div id="validationHint" class="text-xs font-medium text-amber-700"></div>

                    <?php if ($nextCase): ?>
                        <a href="<?= base_url('evaluation/' . $nextCase) ?>" class="text-xs font-semibold text-slate-600 hover:text-slate-900 border border-slate-200 px-4 py-2 rounded-xl bg-white hover:bg-slate-50 transition">
                            Lewati (Skip)
                        </a>
                    <?php endif; ?>

                    <button type="submit" id="saveSubmitBtn" class="text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-700 disabled:bg-slate-300 disabled:cursor-not-allowed disabled:hover:bg-slate-300 px-6 py-2.5 rounded-xl shadow-sm transition flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Simpan & Kasus Berikutnya →
                    </button>
                </div>
            </div>
        </form>

    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var groups = ['human_interpretation', 'citation_context', 'citation_status', 'system_assessment', 'reviewer_confidence'];

            function updateRadioCards() {
                document.querySelectorAll('.radio-card').forEach(function (card) {
                    var input = card.querySelector('input[type="radio"]');
                    if (input && input.checked) {
                        card.classList.add('bg-indigo-50', 'border-indigo-600', 'ring-1', 'ring-indigo-500');
                        card.classList.remove('bg-slate-50/50', 'border-slate-200');
                    } else if (input) {
                        card.classList.remove('bg-indigo-50', 'border-indigo-600', 'ring-1', 'ring-indigo-500');
                        card.classList.add('bg-slate-50/50', 'border-slate-200');
                    }
                });
            }

            function validateForm() {
                var missing = 0;
                groups.forEach(function (name) {
                    var checked = document.querySelector('input[name="' + name + '"]:checked');
                    if (!checked) missing++;
                });

                var submitBtn = document.getElementById('saveSubmitBtn');
                var hint = document.getElementById('validationHint');

                if (missing === 0) {
                    submitBtn.removeAttribute('disabled');
                    hint.innerHTML = '<span class="text-emerald-700 flex items-center gap-1"><svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> Siap disimpan (5/5 lengkap)</span>';
                } else {
                    submitBtn.setAttribute('disabled', 'disabled');
                    hint.innerHTML = '<span class="text-amber-700 flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span> Pilih 5 pertimbangan wajib (' + missing + ' tersisa)</span>';
                }
            }

            document.querySelectorAll('input[type="radio"]').forEach(function (radio) {
                radio.addEventListener('change', function () {
                    updateRadioCards();
                    validateForm();
                });
            });

            var resetBtn = document.getElementById('resetFormBtn');
            if (resetBtn) {
                resetBtn.addEventListener('click', function () {
                    document.querySelectorAll('input[type="radio"]').forEach(function (r) {
                        r.checked = false;
                    });
                    updateRadioCards();
                    validateForm();
                });
            }

            updateRadioCards();
            validateForm();
        });
    </script>
</body>
</html>
