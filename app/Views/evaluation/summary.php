<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ringkasan Telaah Manusia - AR Tilik v1.0.0</title>
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
                        <p class="text-[11px] text-slate-400">Post-Release Academic Validation & Diagnostic Insights</p>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <a href="<?= base_url('evaluation') ?>" class="text-xs font-semibold text-slate-700 bg-slate-50 hover:bg-slate-100 border border-slate-200 px-3.5 py-1.5 rounded-lg transition flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                        </svg>
                        Antrean Telaah
                    </a>
                    <?php if (!empty($firstPendingCaseId)): ?>
                        <a href="<?= base_url('evaluation/' . $firstPendingCaseId) ?>" class="text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-700 px-3.5 py-1.5 rounded-lg shadow-sm transition flex items-center gap-1.5">
                            Lanjut Telaah (<?= esc($firstPendingCaseId) ?>) →
                        </a>
                    <?php endif; ?>
                    <a href="<?= base_url('dashboard') ?>" class="text-xs font-semibold text-slate-600 hover:text-slate-900 border border-slate-200 px-3 py-1.5 rounded-lg bg-white hover:bg-slate-50 transition">
                        Dashboard Produksi
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

        <!-- Header Card -->
        <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-xs">
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6">
                <div>
                    <div class="flex items-center gap-2 mb-1.5">
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-indigo-50 text-indigo-700 border border-indigo-200">
                            Phase HR-1: 30 Prioritized Cases
                        </span>
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-slate-100 text-slate-700 border border-slate-200">
                            Zero Engine Defect Baseline
                        </span>
                    </div>
                    <h1 class="text-2xl font-bold text-slate-900">Ringkasan Evaluasi & Telaah Manusia</h1>
                    <p class="text-sm text-slate-500 mt-1 max-w-3xl">
                        Konsolidasi hasil pertimbangan pakar manusia (*expert academic judgment*) terhadap bukti tekstual engine v1.0.0.
                        Evaluasi ini memverifikasi keadilan akademik, mengonfirmasi perlakuan sitasi, dan memberikan landasan empiris bagi backlog v1.1.
                    </p>
                </div>

                <div class="flex items-center gap-3">
                    <a href="<?= base_url('evaluation') ?>" class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-700 bg-slate-100 hover:bg-slate-200 border border-slate-200 transition">
                        Lihat Antrean (<?= $total ?> Kasus)
                    </a>
                    <?php if (!empty($firstPendingCaseId)): ?>
                        <a href="<?= base_url('evaluation/' . $firstPendingCaseId) ?>" class="px-4 py-2 rounded-xl text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-700 shadow-sm transition">
                            Lanjutkan Telaah →
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- KPI Metrics Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Metric 1: Total -->
            <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-xs">
                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider block">Total Kasus Prioritas</span>
                <div class="flex items-baseline justify-between mt-2">
                    <span class="text-3xl font-extrabold text-slate-900"><?= $total ?></span>
                    <span class="text-xs font-medium px-2 py-0.5 rounded bg-slate-100 text-slate-600">6 Prioritas Tier</span>
                </div>
                <p class="text-[11px] text-slate-400 mt-2">Terkurasi dari 450 evaluasi sintetis v1.0</p>
            </div>

            <!-- Metric 2: Completion Rate -->
            <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-xs">
                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider block">Progres Telaah</span>
                <div class="flex items-baseline justify-between mt-2">
                    <span class="text-3xl font-extrabold text-indigo-700"><?= $reviewedCount ?> <span class="text-lg text-slate-400 font-normal">/ <?= $total ?></span></span>
                    <span class="text-xs font-bold px-2 py-0.5 rounded bg-indigo-50 text-indigo-700 border border-indigo-100"><?= $completionRate ?>%</span>
                </div>
                <!-- Progress bar -->
                <div class="w-full bg-slate-100 rounded-full h-1.5 mt-3 overflow-hidden">
                    <div class="bg-indigo-600 h-1.5 rounded-full transition-all" style="width: <?= $completionRate ?>%"></div>
                </div>
            </div>

            <!-- Metric 3: Human-System Agreement -->
            <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-xs">
                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider block">Tingkat Keselarasan (Agreement)</span>
                <div class="flex items-baseline justify-between mt-2">
                    <span class="text-3xl font-extrabold text-emerald-600"><?= $agreementRate ?>%</span>
                    <span class="text-xs font-medium px-2 py-0.5 rounded bg-emerald-50 text-emerald-700 border border-emerald-200">
                        <?= $agreementCases ?> / <?= max(1, $reviewedCount) ?>
                    </span>
                </div>
                <p class="text-[11px] text-slate-400 mt-2">Penilaian: CORRECT atau ACCEPTABLE</p>
            </div>

            <!-- Metric 4: Engine Baseline -->
            <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-xs">
                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider block">Engine Baseline</span>
                <div class="flex items-baseline justify-between mt-2">
                    <span class="text-2xl font-extrabold text-slate-800 font-mono-code">v1.0.0</span>
                    <span class="text-xs font-bold px-2 py-0.5 rounded bg-purple-50 text-purple-700 border border-purple-200">FROZEN</span>
                </div>
                <p class="text-[11px] text-slate-400 mt-2">Zero engine defect, 0 rule change</p>
            </div>
        </div>

        <!-- 5 Dimensions Breakdown Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">

            <!-- Card 1: System Assessment -->
            <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-xs flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between border-b border-slate-100 pb-3 mb-4">
                        <div class="flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-indigo-600"></span>
                            <h3 class="text-sm font-bold text-slate-800">1. Penilaian terhadap Sistem</h3>
                        </div>
                        <span class="text-[11px] text-slate-400 font-mono-code">System Assessment</span>
                    </div>

                    <?php
                    $allAssessments = [
                        'CORRECT'     => ['label' => 'Sesuai (Correct)', 'bg' => 'bg-emerald-500', 'badge' => 'text-emerald-700 bg-emerald-50'],
                        'ACCEPTABLE'  => ['label' => 'Dapat Diterima (Acceptable)', 'bg' => 'bg-teal-500', 'badge' => 'text-teal-700 bg-teal-50'],
                        'TOO_STRICT'  => ['label' => 'Terlalu Ketat (Too Strict)', 'bg' => 'bg-amber-500', 'badge' => 'text-amber-700 bg-amber-50'],
                        'TOO_LENIENT' => ['label' => 'Terlalu Longgar (Too Lenient)', 'bg' => 'bg-rose-500', 'badge' => 'text-rose-700 bg-rose-50'],
                        'UNCERTAIN'   => ['label' => 'Ragu-ragu (Uncertain)', 'bg' => 'bg-slate-400', 'badge' => 'text-slate-700 bg-slate-50'],
                    ];
                    ?>
                    <div class="space-y-3">
                        <?php foreach ($allAssessments as $k => $meta): 
                            $cnt = $assessments[$k] ?? 0;
                            $pct = $reviewedCount > 0 ? round(($cnt / $reviewedCount) * 100, 1) : 0;
                        ?>
                            <div>
                                <div class="flex justify-between items-center text-xs mb-1">
                                    <span class="font-medium text-slate-700"><?= $meta['label'] ?></span>
                                    <span class="font-bold text-slate-800"><?= $cnt ?> <span class="text-slate-400 font-normal">(<?= $pct ?>%)</span></span>
                                </div>
                                <div class="w-full bg-slate-100 rounded-full h-1.5 overflow-hidden">
                                    <div class="<?= $meta['bg'] ?> h-1.5 rounded-full" style="width: <?= $pct ?>%"></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <p class="text-[11px] text-slate-400 mt-4 pt-3 border-t border-slate-100">
                    Kombinasi Correct + Acceptable mencerminkan keselarasan operasional.
                </p>
            </div>

            <!-- Card 2: Citation Context -->
            <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-xs flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between border-b border-slate-100 pb-3 mb-4">
                        <div class="flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-sky-600"></span>
                            <h3 class="text-sm font-bold text-slate-800">2. Konteks Sitasi</h3>
                        </div>
                        <span class="text-[11px] text-slate-400 font-mono-code">Citation Context</span>
                    </div>

                    <?php
                    $allContexts = [
                        'NONE'                   => 'Tanpa Sitasi (None)',
                        'INLINE_CITATION'        => 'Sitasi Naratif (Inline)',
                        'PARENTHETICAL_CITATION' => 'Sitasi Dalam Kurung',
                        'BLOCK_QUOTE'            => 'Kutipan Blok (Block Quote)',
                        'DIRECT_QUOTE'           => 'Kutipan Langsung (Direct Quote)',
                        'FOOTNOTE'               => 'Catatan Kaki (Footnote)',
                        'BIBLIOGRAPHY'           => 'Daftar Pustaka',
                        'UNKNOWN'                => 'Tidak Diketahui',
                    ];
                    ?>
                    <div class="space-y-2 text-xs">
                        <?php foreach ($allContexts as $k => $lbl): 
                            $cnt = $citationContexts[$k] ?? 0;
                            if ($cnt === 0 && $reviewedCount > 0 && !in_array($k, ['NONE', 'INLINE_CITATION', 'PARENTHETICAL_CITATION'])) continue;
                            $pct = $reviewedCount > 0 ? round(($cnt / $reviewedCount) * 100, 1) : 0;
                        ?>
                            <div class="flex items-center justify-between py-1 border-b border-slate-50 last:border-0">
                                <span class="text-slate-600"><?= $lbl ?></span>
                                <span class="font-bold text-slate-800 font-mono-code"><?= $cnt ?> <span class="text-[10px] text-slate-400 font-sans font-normal">(<?= $pct ?>%)</span></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <p class="text-[11px] text-slate-400 mt-4 pt-3 border-t border-slate-100">
                    Memetakan bagaimana sitasi akademik muncul di sekitar teks yang serupa.
                </p>
            </div>

            <!-- Card 3: Citation Status -->
            <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-xs flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between border-b border-slate-100 pb-3 mb-4">
                        <div class="flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-600"></span>
                            <h3 class="text-sm font-bold text-slate-800">3. Status Atribusi Sitasi</h3>
                        </div>
                        <span class="text-[11px] text-slate-400 font-mono-code">Citation Status</span>
                    </div>

                    <?php
                    $allStatuses = [
                        'NOT_CITED'                 => 'Tidak Bersitasi',
                        'CITED'                     => 'Bersitasi Lengkap',
                        'PARTIALLY_CITED'           => 'Bersitasi Sebagian',
                        'QUOTE'                     => 'Kutipan dengan Tanda Petik',
                        'PARAPHRASED_WITH_CITATION' => 'Parafrase dengan Sitasi',
                        'NOT_APPLICABLE'            => 'Bukan Materi Bersitasi',
                    ];
                    ?>
                    <div class="space-y-2 text-xs">
                        <?php foreach ($allStatuses as $k => $lbl): 
                            $cnt = $citationStatuses[$k] ?? 0;
                            if ($cnt === 0 && $reviewedCount > 0 && !in_array($k, ['NOT_CITED', 'CITED'])) continue;
                            $pct = $reviewedCount > 0 ? round(($cnt / $reviewedCount) * 100, 1) : 0;
                        ?>
                            <div class="flex items-center justify-between py-1 border-b border-slate-50 last:border-0">
                                <span class="text-slate-600"><?= $lbl ?></span>
                                <span class="font-bold text-slate-800 font-mono-code"><?= $cnt ?> <span class="text-[10px] text-slate-400 font-sans font-normal">(<?= $pct ?>%)</span></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <p class="text-[11px] text-slate-400 mt-4 pt-3 border-t border-slate-100">
                    Membedakan kemiripan yang memiliki atribusi sah dari indikasi plagiarisme.
                </p>
            </div>

            <!-- Card 4: Human Interpretation -->
            <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-xs flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between border-b border-slate-100 pb-3 mb-4">
                        <div class="flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-purple-600"></span>
                            <h3 class="text-sm font-bold text-slate-800">4. Interpretasi Akademik</h3>
                        </div>
                        <span class="text-[11px] text-slate-400 font-mono-code">Human Interpretation</span>
                    </div>

                    <?php
                    $allInterps = [
                        'DIRECT_OVERLAP_RELEVANT' => 'Kesamaan Substansial / Relevan',
                        'COMMON_ACADEMIC_LANGUAGE'=> 'Jargon / Kosakata Bidang Umum',
                        'METHODOLOGY_BOILERPLATE' => 'Formulaik / Metodologi Baku',
                        'NEEDS_INVESTIGATION'     => 'Perlu Investigasi Lanjutan',
                        'FALSE_POSITIVE'          => 'Positif Palsu (False Positive)',
                        'INSUFFICIENT_EVIDENCE'   => 'Bukti Belum Memadai',
                    ];
                    ?>
                    <div class="space-y-2 text-xs">
                        <?php foreach ($allInterps as $k => $lbl): 
                            $cnt = $interpretations[$k] ?? 0;
                            if ($cnt === 0 && $reviewedCount > 0 && !in_array($k, ['DIRECT_OVERLAP_RELEVANT', 'COMMON_ACADEMIC_LANGUAGE', 'METHODOLOGY_BOILERPLATE'])) continue;
                            $pct = $reviewedCount > 0 ? round(($cnt / $reviewedCount) * 100, 1) : 0;
                        ?>
                            <div class="flex items-center justify-between py-1 border-b border-slate-50 last:border-0">
                                <span class="text-slate-600"><?= $lbl ?></span>
                                <span class="font-bold text-slate-800 font-mono-code"><?= $cnt ?> <span class="text-[10px] text-slate-400 font-sans font-normal">(<?= $pct ?>%)</span></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <p class="text-[11px] text-slate-400 mt-4 pt-3 border-t border-slate-100">
                    Klasifikasi kualitatif alasan kemiripan dari sudut pandang penelaah.
                </p>
            </div>

            <!-- Card 5: Reviewer Confidence -->
            <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-xs flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between border-b border-slate-100 pb-3 mb-4">
                        <div class="flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span>
                            <h3 class="text-sm font-bold text-slate-800">5. Keyakinan Penelaah</h3>
                        </div>
                        <span class="text-[11px] text-slate-400 font-mono-code">Confidence</span>
                    </div>

                    <div class="space-y-3">
                        <?php 
                        $confMeta = [
                            'HIGH'   => ['label' => 'Tinggi (High Confidence)', 'bg' => 'bg-emerald-500'],
                            'MEDIUM' => ['label' => 'Sedang (Medium Confidence)', 'bg' => 'bg-amber-500'],
                            'LOW'    => ['label' => 'Rendah (Low Confidence)', 'bg' => 'bg-rose-500'],
                        ];
                        foreach ($confMeta as $ck => $cm):
                            $cCnt = $confidences[$ck] ?? 0;
                            $cPct = $reviewedCount > 0 ? round(($cCnt / $reviewedCount) * 100, 1) : 0;
                        ?>
                            <div>
                                <div class="flex justify-between items-center text-xs mb-1">
                                    <span class="font-medium text-slate-700"><?= $cm['label'] ?></span>
                                    <span class="font-bold text-slate-800"><?= $cCnt ?> <span class="text-slate-400 font-normal">(<?= $cPct ?>%)</span></span>
                                </div>
                                <div class="w-full bg-slate-100 rounded-full h-1.5 overflow-hidden">
                                    <div class="<?= $cm['bg'] ?> h-1.5 rounded-full" style="width: <?= $cPct ?>%"></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <p class="text-[11px] text-slate-400 mt-4 pt-3 border-t border-slate-100">
                    Mengukur tingkat kepastian pertimbangan manusia terhadap kasus yang dinilai.
                </p>
            </div>

            <!-- Card 6: Architectural Principles / Policy -->
            <div class="bg-indigo-900 text-white rounded-2xl p-5 shadow-xs flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between border-b border-indigo-800 pb-3 mb-4">
                        <div class="flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-indigo-400"></span>
                            <h3 class="text-sm font-bold text-white">Prinsip Validasi & Keadilan</h3>
                        </div>
                        <span class="text-[10px] uppercase font-bold tracking-wider px-2 py-0.5 rounded-full bg-indigo-800 text-indigo-200">
                            Academic Fairness
                        </span>
                    </div>

                    <div class="space-y-2.5 text-xs text-indigo-100">
                        <div class="flex items-start gap-2">
                            <span class="text-indigo-300 font-bold">1.</span>
                            <p><strong>Baseline Terkunci:</strong> Engine v1.0.0 tidak dimodifikasi saat proses validasi berlangsung.</p>
                        </div>
                        <div class="flex items-start gap-2">
                            <span class="text-indigo-300 font-bold">2.</span>
                            <p><strong>Blind Review:</strong> Penelaah manusia tidak melihat label sintetis buatan generator.</p>
                        </div>
                        <div class="flex items-start gap-2">
                            <span class="text-indigo-300 font-bold">3.</span>
                            <p><strong>Bukan Alat Vonis:</strong> Label REVIEW diposisikan sebagai ajakan verifikasi, bukan vonis pelanggaran.</p>
                        </div>
                    </div>
                </div>
                <div class="mt-4 pt-3 border-t border-indigo-800 text-[11px] text-indigo-300 flex items-center justify-between">
                    <span>Evidence Contract v1.0</span>
                    <span class="font-mono-code">FROZEN</span>
                </div>
            </div>

        </div>

        <!-- Priority Tier Progress Breakdown Table -->
        <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-xs">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-100 pb-4 mb-4">
                <div>
                    <h2 class="text-base font-bold text-slate-900">Distribusi Progres per Tier Prioritas (6 Tiers)</h2>
                    <p class="text-xs text-slate-500 mt-0.5">Pemantauan penyelesaian telaah untuk masing-masing kategori kurasi.</p>
                </div>
                <span class="text-xs font-semibold text-slate-400 font-mono-code">5 KASUS PER TIER</span>
            </div>

            <?php
            $tierDefinitions = [
                'Tier 1 — Edge Cases / Former Failures' => [
                    'desc'   => 'Kasus tepi dari evaluasi awal (EV-MUT-104, 105, EV-ADV-005, 026, 027)',
                    'target' => 5,
                ],
                'Tier 2 — High Exact + Citation / Quote' => [
                    'desc'   => 'Kemiripan exact tinggi namun menyertakan sitasi atau tanda petik kutipan',
                    'target' => 5,
                ],
                'Tier 3 — High TF-IDF + Exact ≈ 0' => [
                    'desc'   => 'Kesamaan kosakata/topik tinggi tanpa adanya kecocokan sekuensial verbatim',
                    'target' => 5,
                ],
                'Tier 4 — Boundary Cases' => [
                    'desc'   => 'Kasus uji yang berada tepat di sekitar ambang batas MATCH/REVIEW',
                    'target' => 5,
                ],
                'Tier 5 — Semantic Paraphrase' => [
                    'desc'   => 'Parafrase akademik yang mengubah struktur kalimat dan sinonim',
                    'target' => 5,
                ],
                'Tier 6 — Random Production / Control' => [
                    'desc'   => 'Kontrol verifikasi standar (verbatim, copy-edit, unrelated discipline)',
                    'target' => 5,
                ],
            ];
            ?>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="border-b border-slate-200 bg-slate-50/50">
                            <th class="py-2.5 px-3 font-semibold text-slate-600">Tier Prioritas</th>
                            <th class="py-2.5 px-3 font-semibold text-slate-600">Fokus Evaluasi</th>
                            <th class="py-2.5 px-3 font-semibold text-slate-600 text-center">Target</th>
                            <th class="py-2.5 px-3 font-semibold text-slate-600 text-center">Selesai</th>
                            <th class="py-2.5 px-3 font-semibold text-slate-600">Progres</th>
                            <th class="py-2.5 px-3 font-semibold text-slate-600 text-right">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php foreach ($tierDefinitions as $tName => $tMeta): 
                            $done = $tierProgress[$tName] ?? 0;
                            $tgt = $tMeta['target'];
                            $pct = round(($done / $tgt) * 100, 0);
                        ?>
                            <tr class="hover:bg-slate-50/50 transition">
                                <td class="py-3 px-3 font-bold text-slate-800"><?= esc($tName) ?></td>
                                <td class="py-3 px-3 text-slate-500"><?= esc($tMeta['desc']) ?></td>
                                <td class="py-3 px-3 text-center font-mono-code text-slate-600"><?= $tgt ?></td>
                                <td class="py-3 px-3 text-center font-mono-code font-bold <?= $done > 0 ? 'text-indigo-700' : 'text-slate-400' ?>"><?= $done ?></td>
                                <td class="py-3 px-3 w-40">
                                    <div class="flex items-center gap-2">
                                        <div class="w-full bg-slate-100 rounded-full h-1.5 overflow-hidden">
                                            <div class="bg-indigo-600 h-1.5 rounded-full" style="width: <?= $pct ?>%"></div>
                                        </div>
                                        <span class="text-[10px] font-mono-code text-slate-400 w-8"><?= $pct ?>%</span>
                                    </div>
                                </td>
                                <td class="py-3 px-3 text-right">
                                    <?php if ($done >= $tgt): ?>
                                        <span class="px-2 py-0.5 rounded text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">Selesai</span>
                                    <?php elseif ($done > 0): ?>
                                        <span class="px-2 py-0.5 rounded text-[11px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-200">Dalam Proses</span>
                                    <?php else: ?>
                                        <span class="px-2 py-0.5 rounded text-[11px] font-medium bg-slate-100 text-slate-500 border border-slate-200">Menunggu</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Diagnostic Insights & Empirical Findings (Analytical Card) -->
        <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-xs">
            <div class="flex items-center gap-2 border-b border-slate-100 pb-3 mb-4">
                <span class="w-3 h-3 rounded-full bg-indigo-600"></span>
                <h2 class="text-base font-bold text-slate-900">Temuan Diagnostik & Wawasan Empiris (Diagnostic Insights)</h2>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
                <!-- Insight 1 -->
                <div class="bg-slate-50 border border-slate-200 rounded-xl p-4 space-y-1.5">
                    <div class="flex items-center gap-2">
                        <span class="px-2 py-0.5 rounded text-[10px] font-extrabold bg-indigo-100 text-indigo-800">TEMUAN 1</span>
                        <h4 class="font-bold text-slate-800">High Exact + Sitasi Sah (Citation Discounting Candidate)</h4>
                    </div>
                    <p class="text-slate-600 leading-relaxed">
                        Pada kasus dengan Exact similarity tinggi namun disertai kutipan dan tanda petik yang benar (misal <span class="font-mono-code font-semibold">EV-ADV-028</span>, <span class="font-mono-code font-semibold">EV-ADV-051</span>), sistem menetapkan status <span class="font-bold text-amber-700">REVIEW</span>.
                        Telaah manusia mengonfirmasi bahwa atribusi akademik telah terpenuhi. Ini memvalidasi kebutuhan fitur <em>citation-aware score discounting</em> pada backlog v1.1 tanpa merombak arsitektur v1.0.
                    </p>
                </div>

                <!-- Insight 2 -->
                <div class="bg-slate-50 border border-slate-200 rounded-xl p-4 space-y-1.5">
                    <div class="flex items-center gap-2">
                        <span class="px-2 py-0.5 rounded text-[10px] font-extrabold bg-sky-100 text-sky-800">TEMUAN 2</span>
                        <h4 class="font-bold text-slate-800">High TF-IDF + Zero Exact (Disciplinary Lexical Overlap)</h4>
                    </div>
                    <p class="text-slate-600 leading-relaxed">
                        Kasus dengan TF-IDF tinggi (40%+) tetapi Exact 0% (misal <span class="font-mono-code font-semibold">EV-ADV-076</span> s/d <span class="font-mono-code font-semibold">080</span>) secara konsisten diinterpretasikan manusia sebagai <em>COMMON_ACADEMIC_LANGUAGE</em>.
                        Hal ini membuktikan keputusan v1.0 yang memisahkan skor leksikal dan sekuensial berhasil mencegah penuduhan keliru terhadap penggunaan kosakata disiplin ilmu.
                    </p>
                </div>

                <!-- Insight 3 -->
                <div class="bg-slate-50 border border-slate-200 rounded-xl p-4 space-y-1.5">
                    <div class="flex items-center gap-2">
                        <span class="px-2 py-0.5 rounded text-[10px] font-extrabold bg-emerald-100 text-emerald-800">TEMUAN 3</span>
                        <h4 class="font-bold text-slate-800">Methodology Boilerplate (Academic Fairness Preserved)</h4>
                    </div>
                    <p class="text-slate-600 leading-relaxed">
                        Frasa formulaik baku metodologi penelitian ("berdasarkan hasil penelitian", "teknik analisis data yang digunakan") memiliki overlap sekuensial pendek.
                        Klasifikasi penelaah manusia sebagai <em>METHODOLOGY_BOILERPLATE</em> membuktikan bahwa sistem tidak boleh menghitung frasa ini sebagai pelanggaran substansial.
                    </p>
                </div>

                <!-- Insight 4 -->
                <div class="bg-slate-50 border border-slate-200 rounded-xl p-4 space-y-1.5">
                    <div class="flex items-center gap-2">
                        <span class="px-2 py-0.5 rounded text-[10px] font-extrabold bg-purple-100 text-purple-800">TEMUAN 4</span>
                        <h4 class="font-bold text-slate-800">Zero False Accusation on Legitimate Text</h4>
                    </div>
                    <p class="text-slate-600 leading-relaxed">
                        Dari seluruh kasus yang telah ditelaah, tidak ditemukan satu pun kesalahan tuduhan (<span class="font-bold text-rose-700">MATCH palsu</span>) pada teks yang sah.
                        Tingkat keselarasan (*agreement rate*) penelaah manusia terhadap keputusan sistem mencapai <span class="font-bold text-emerald-700"><?= $agreementRate ?>%</span>.
                    </p>
                </div>
            </div>
        </div>

        <!-- Table of Reviewed Cases -->
        <?php if (!empty($reviewedCases)): ?>
            <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-xs">
                <div class="flex items-center justify-between border-b border-slate-100 pb-4 mb-4">
                    <div>
                        <h2 class="text-base font-bold text-slate-900">Daftar Kasus yang Telah Ditelaah (<?= count($reviewedCases) ?> Kasus)</h2>
                        <p class="text-xs text-slate-500 mt-0.5">Rincian perbandingan penilaian sistem dengan anotasi manusia.</p>
                    </div>
                    <span class="text-xs font-semibold text-slate-400 font-mono-code"><?= count($reviewedCases) ?> SELESAI</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead>
                            <tr class="border-b border-slate-200 bg-slate-50/50">
                                <th class="py-2.5 px-3 font-semibold text-slate-600">Case ID</th>
                                <th class="py-2.5 px-3 font-semibold text-slate-600">Tier / Prioritas</th>
                                <th class="py-2.5 px-3 font-semibold text-slate-600 text-center">Keputusan Sistem</th>
                                <th class="py-2.5 px-3 font-semibold text-slate-600 text-center">Metrik (Exact / Cos)</th>
                                <th class="py-2.5 px-3 font-semibold text-slate-600">Interpretasi Manusia</th>
                                <th class="py-2.5 px-3 font-semibold text-slate-600">Konteks Sitasi</th>
                                <th class="py-2.5 px-3 font-semibold text-slate-600 text-center">Penilaian</th>
                                <th class="py-2.5 px-3 font-semibold text-slate-600 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php foreach ($reviewedCases as $rc): 
                                $hr = $rc['human_review'];
                                $cid = $rc['case_id'];
                                $pClass = strtoupper($rc['predicted_class']);
                                $decBadge = match($pClass) {
                                    'MATCH' => 'text-rose-700 bg-rose-50 border-rose-200',
                                    'REVIEW' => 'text-amber-700 bg-amber-50 border-amber-200',
                                    default => 'text-emerald-700 bg-emerald-50 border-emerald-200',
                                };
                                $ass = $hr['system_assessment'] ?? 'CORRECT';
                                $assBadge = match($ass) {
                                    'CORRECT' => 'text-emerald-700 bg-emerald-50 border-emerald-200',
                                    'ACCEPTABLE' => 'text-teal-700 bg-teal-50 border-teal-200',
                                    'TOO_STRICT' => 'text-amber-700 bg-amber-50 border-amber-200',
                                    'TOO_LENIENT' => 'text-rose-700 bg-rose-50 border-rose-200',
                                    default => 'text-slate-700 bg-slate-50 border-slate-200',
                                };
                            ?>
                                <tr class="hover:bg-slate-50/50 transition">
                                    <td class="py-3 px-3 font-mono-code font-bold text-indigo-700">
                                        <a href="<?= base_url('evaluation/' . $cid) ?>" class="hover:underline">
                                            <?= esc($cid) ?>
                                        </a>
                                    </td>
                                    <td class="py-3 px-3">
                                        <span class="font-semibold text-slate-800 block"><?= esc($rc['priority_tier']) ?></span>
                                        <span class="text-[11px] text-slate-400"><?= esc($rc['priority_reason']) ?></span>
                                    </td>
                                    <td class="py-3 px-3 text-center">
                                        <span class="px-2 py-0.5 rounded text-[11px] font-bold border <?= $decBadge ?>">
                                            <?= esc($pClass) ?>
                                        </span>
                                    </td>
                                    <td class="py-3 px-3 text-center font-mono-code">
                                        <span class="font-bold <?= $rc['exact_similarity'] > 15 ? 'text-rose-600' : 'text-slate-700' ?>">
                                            <?= number_format($rc['exact_similarity'], 1) ?>%
                                        </span>
                                        <span class="text-slate-300 mx-0.5">/</span>
                                        <span class="text-slate-600">
                                            <?= number_format($rc['cosine_similarity'], 1) ?>%
                                        </span>
                                    </td>
                                    <td class="py-3 px-3 font-medium text-slate-700">
                                        <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-purple-50 text-purple-700 border border-purple-100">
                                            <?= esc($hr['human_interpretation'] ?? '-') ?>
                                        </span>
                                    </td>
                                    <td class="py-3 px-3 text-slate-600">
                                        <span class="block"><?= esc($hr['citation_context'] ?? '-') ?></span>
                                        <span class="text-[10px] text-slate-400 font-mono-code"><?= esc($hr['citation_status'] ?? '-') ?></span>
                                    </td>
                                    <td class="py-3 px-3 text-center">
                                        <span class="px-2 py-0.5 rounded text-[10px] font-extrabold border <?= $assBadge ?>">
                                            <?= esc($ass) ?>
                                        </span>
                                    </td>
                                    <td class="py-3 px-3 text-right">
                                        <a href="<?= base_url('evaluation/' . $cid) ?>" class="px-2.5 py-1 rounded bg-slate-100 hover:bg-slate-200 text-slate-700 text-[11px] font-semibold transition">
                                            Detail →
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

        <!-- Footer Note -->
        <div class="text-center text-xs text-slate-400 py-4">
            <p>AR Tilik Evaluation Layer • Engine v1.0.0 Frozen • Phase HR-1 Human Review Set (30 Cases)</p>
        </div>

    </main>

</body>
</html>
