<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AR Tilik - Academic Writing Similarity Checker</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
</head>
<body class="bg-slate-50 text-slate-800 antialiased min-h-screen">
    <!-- Navbar -->
    <nav class="bg-white border-b border-slate-200 sticky top-0 z-30">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16 items-center">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-indigo-600 text-white flex items-center justify-center font-bold text-xl shadow-md shadow-indigo-100">
                        AR
                    </div>
                    <div>
                        <span class="text-xl font-bold bg-gradient-to-r from-indigo-700 to-indigo-500 bg-clip-text text-transparent">AR Tilik</span>
                        <span class="text-xs ml-2 px-2 py-0.5 rounded-full bg-indigo-50 text-indigo-700 font-medium border border-indigo-100">v1.0.0 Frozen (RC)</span>
                        <p class="text-xs text-slate-400">Academic Writing Similarity & Evidence Checker</p>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        Python Worker Ready
                    </span>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Container -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Hero Header -->
        <div class="mb-8 bg-gradient-to-br from-indigo-900 via-indigo-800 to-slate-900 rounded-2xl p-6 sm:p-8 text-white shadow-lg">
            <div class="max-w-3xl">
                <h1 class="text-2xl sm:text-3xl font-bold tracking-tight mb-2">Pemeriksaan Kemiripan Tulisan Ilmiah</h1>
                <p class="text-indigo-200 text-sm sm:text-base leading-relaxed mb-6">
                    Menghadirkan deteksi kemiripan leksikal berbasis 5-gram shingle dan pembobotan signifikansi IDF. 
                    Membantu akademisi meninjau bukti teks secara objektif tanpa praduga menghakimi.
                </p>
                <div class="grid grid-cols-3 gap-4 border-t border-indigo-700/50 pt-5">
                    <div>
                        <div class="text-2xl font-bold text-white"><?= number_format($totalSubmissions) ?></div>
                        <div class="text-xs text-indigo-300">Submisi Diperiksa</div>
                    </div>
                    <div>
                        <div class="text-2xl font-bold text-white"><?= number_format($totalCorpus) ?></div>
                        <div class="text-xs text-indigo-300">Dokumen Korpus</div>
                    </div>
                    <div>
                        <div class="text-2xl font-bold text-white"><?= number_format($totalReports) ?></div>
                        <div class="text-xs text-indigo-300">Laporan Dihasilkan</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Navigation Tabs -->
        <div class="flex border-b border-slate-200 mb-6 gap-6">
            <button onclick="switchTab('tab-submission')" id="btn-tab-submission" class="tab-btn pb-3 font-semibold text-sm border-b-2 border-indigo-600 text-indigo-600 flex items-center gap-2">
                📄 Submisi & Pemeriksaan
            </button>
            <button onclick="switchTab('tab-corpus')" id="btn-tab-corpus" class="tab-btn pb-3 font-medium text-sm text-slate-500 hover:text-slate-800 border-b-2 border-transparent flex items-center gap-2">
                📚 Manajemen Korpus Rujukan
            </button>
            <button onclick="switchTab('tab-benchmark')" id="btn-tab-benchmark" class="tab-btn pb-3 font-medium text-sm text-slate-500 hover:text-slate-800 border-b-2 border-transparent flex items-center gap-2">
                🧪 Benchmark Lab
            </button>
        </div>

        <!-- TAB 1: Submisi & Pemeriksaan -->
        <div id="tab-submission" class="tab-content space-y-6">
            <!-- Upload Box -->
            <div class="bg-white rounded-xl border border-slate-200 p-6 shadow-sm">
                <h2 class="text-lg font-bold text-slate-800 mb-2">Unggah Karya Ilmiah</h2>
                <p class="text-sm text-slate-500 mb-4">Pilih file naskah skripsi, jurnal, atau makalah (PDF, DOCX, atau TXT) untuk diperiksa kemiripannya dengan korpus institusi.</p>
                
                <form id="uploadSubmissionForm" class="space-y-4" enctype="multipart/form-data">
                    <div class="border-2 border-dashed border-slate-300 hover:border-indigo-400 transition-colors rounded-xl p-6 text-center cursor-pointer bg-slate-50/50" onclick="document.getElementById('submissionFileInput').click()">
                        <input type="file" id="submissionFileInput" name="document" accept=".pdf,.docx,.txt" class="hidden" onchange="handleFileSelect(this, 'submissionFileLabel')">
                        <div class="w-12 h-12 mx-auto mb-3 rounded-full bg-indigo-50 text-indigo-600 flex items-center justify-center text-2xl">
                            📤
                        </div>
                        <p id="submissionFileLabel" class="text-sm font-medium text-slate-700">Klik untuk memilih file dokumen (PDF, DOCX, TXT)</p>
                        <p class="text-xs text-slate-400 mt-1">Ukuran maksimal file: 25 MB</p>
                    </div>

                    <div class="space-y-3 pt-2">
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider">Metode Mesin Pemeriksaan (Engine Mode):</label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 text-xs">
                            <label class="flex items-center gap-2 p-3 rounded-lg border border-indigo-200 bg-indigo-50/40 cursor-pointer hover:bg-indigo-50 transition">
                                <input type="radio" name="engine" value="hybrid" checked class="text-indigo-600 focus:ring-indigo-500">
                                <div>
                                    <div class="font-bold text-indigo-900">Hybrid (Rekomendasi)</div>
                                    <div class="text-[11px] text-indigo-700">Pasase Exact + TF-IDF Topikal</div>
                                </div>
                            </label>
                            <label class="flex items-center gap-2 p-3 rounded-lg border border-slate-200 bg-white cursor-pointer hover:bg-slate-50 transition">
                                <input type="radio" name="engine" value="tfidf" class="text-indigo-600 focus:ring-indigo-500">
                                <div>
                                    <div class="font-bold text-slate-800">TF-IDF Topikal</div>
                                    <div class="text-[11px] text-slate-500">Kosinus Leksikal & Bobot DF</div>
                                </div>
                            </label>
                            <label class="flex items-center gap-2 p-3 rounded-lg border border-slate-200 bg-white cursor-pointer hover:bg-slate-50 transition">
                                <input type="radio" name="engine" value="idf" class="text-indigo-600 focus:ring-indigo-500">
                                <div>
                                    <div class="font-bold text-slate-800">Exact + IDF</div>
                                    <div class="text-[11px] text-slate-500">5-Gram dengan Atensi Frasa</div>
                                </div>
                            </label>
                            <label class="flex items-center gap-2 p-3 rounded-lg border border-slate-200 bg-white cursor-pointer hover:bg-slate-50 transition">
                                <input type="radio" name="engine" value="exact" class="text-indigo-600 focus:ring-indigo-500">
                                <div>
                                    <div class="font-bold text-slate-800">Exact 5-Gram</div>
                                    <div class="text-[11px] text-slate-500">Baseline Kesamaan Sekuensial</div>
                                </div>
                            </label>
                        </div>
                    </div>

                    <div class="flex justify-end pt-2">
                        <button type="submit" id="btnUploadSubmit" class="w-full sm:w-auto px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-lg shadow-sm transition flex items-center justify-center gap-2">
                            <span>🚀</span> Unggah & Jadwalkan Pemeriksaan
                        </button>
                    </div>
                </form>
                <div id="uploadAlert" class="mt-4 hidden p-4 rounded-lg text-sm"></div>
            </div>

            <!-- Submission List -->
            <div class="bg-white rounded-xl border border-slate-200 overflow-hidden shadow-sm">
                <div class="p-5 border-b border-slate-100 flex justify-between items-center">
                    <div>
                        <h3 class="font-bold text-slate-800">Daftar Karya Ilmiah yang Diajukan</h3>
                        <p class="text-xs text-slate-400 mt-0.5">Memantau status pemeriksaan, engine yang digunakan, dan keputusan bukti akademis.</p>
                    </div>
                    <button onclick="location.reload()" class="text-xs font-semibold text-indigo-600 hover:underline">Refresh</button>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-sm">
                        <thead class="bg-slate-50 text-slate-500 uppercase text-xs border-b border-slate-200">
                            <tr>
                                <th class="p-4">Dokumen</th>
                                <th class="p-4">Format</th>
                                <th class="p-4">Engine & Keputusan</th>
                                <th class="p-4">Similarity</th>
                                <th class="p-4">Tanggal Unggah</th>
                                <th class="p-4 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php if (empty($submissions)): ?>
                                <tr>
                                    <td colspan="6" class="p-8 text-center text-slate-400">Belum ada karya ilmiah yang diunggah.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($submissions as $sub): 
                                    $rep = $sub['latest_report'] ?? null;
                                ?>
                                    <tr class="hover:bg-slate-50/50">
                                        <td class="p-4 font-medium text-slate-800">
                                            <?= esc($sub['title']) ?>
                                            <div class="text-xs text-slate-400"><?= esc($sub['original_filename']) ?></div>
                                        </td>
                                        <td class="p-4">
                                            <span class="px-2 py-0.5 rounded text-xs bg-slate-100 text-slate-600 font-mono"><?= strtoupper(pathinfo($sub['original_filename'], PATHINFO_EXTENSION)) ?></span>
                                        </td>
                                        <td class="p-4">
                                            <?php if ($rep): ?>
                                                <div class="flex flex-col gap-1 items-start">
                                                    <span class="text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded bg-slate-100 text-slate-600 border border-slate-200">
                                                        <?= strtoupper(esc($rep['engine'] ?? 'hybrid')) ?>
                                                    </span>
                                                    <?php 
                                                        $dec = $rep['predicted_decision'] ?? 'no_match';
                                                        $status = $rep['status'] ?? 'pending';
                                                    ?>
                                                    <?php if ($status === 'completed'): ?>
                                                        <?php if ($dec === 'match'): ?>
                                                            <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-rose-50 text-rose-700 border border-rose-200">
                                                                MATCH (Kemiripan Kuat)
                                                            </span>
                                                        <?php elseif ($dec === 'review'): ?>
                                                            <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-amber-50 text-amber-800 border border-amber-200">
                                                                REVIEW (Perlu Ditelaah)
                                                            </span>
                                                        <?php else: ?>
                                                            <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                                NO MATCH (Aman)
                                                            </span>
                                                        <?php endif; ?>
                                                    <?php elseif ($status === 'processing'): ?>
                                                        <span class="text-[10px] font-medium px-2 py-0.5 rounded-full bg-blue-50 text-blue-700 border border-blue-200 animate-pulse">
                                                            Memproses...
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="text-[10px] font-medium px-2 py-0.5 rounded-full bg-slate-100 text-slate-500">
                                                            Antrean (Pending)
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                            <?php else: ?>
                                                <span class="text-xs text-slate-400">Belum diperiksa</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="p-4">
                                            <?php if ($rep && $rep['status'] === 'completed'): ?>
                                                <div class="font-extrabold text-sm <?= $rep['overall_similarity'] >= 70 ? 'text-rose-600' : ($rep['overall_similarity'] >= 10 ? 'text-amber-600' : 'text-slate-700') ?>">
                                                    <?= number_format($rep['overall_similarity'], 2) ?>%
                                                </div>
                                                <div class="text-[10px] text-slate-400">
                                                    <?= (int) ($rep['longest_match_words'] ?? 0) ?> kata berurutan
                                                </div>
                                            <?php else: ?>
                                                <span class="text-xs text-slate-400">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="p-4 text-slate-500 text-xs"><?= date('d M Y, H:i', strtotime($sub['created_at'])) ?></td>
                                        <td class="p-4 text-right space-x-2">
                                            <?php if ($rep && $rep['status'] === 'completed'): ?>
                                                <a href="<?= base_url('dashboard/report/' . $rep['id']) ?>" class="px-3 py-1 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded text-xs shadow-sm transition">
                                                    Buka Laporan
                                                </a>
                                                <button onclick="triggerCheck(<?= $sub['id'] ?>)" class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 font-medium rounded text-xs transition" title="Ulangi pemeriksaan">
                                                    ↻ Ulangi
                                                </button>
                                            <?php else: ?>
                                                <button onclick="triggerCheck(<?= $sub['id'] ?>)" class="px-3 py-1 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 font-medium rounded text-xs transition">
                                                    Cek Similarity
                                                </button>
                                            <?php endif; ?>
                                            <a href="<?= base_url('api/documents/' . $sub['id']) ?>" target="_blank" class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 text-slate-600 font-medium rounded text-xs transition" title="Lihat JSON">
                                                JSON
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- TAB 2: Manajemen Korpus -->
        <div id="tab-corpus" class="tab-content space-y-6 hidden">
            <!-- Upload Corpus Box -->
            <div class="bg-white rounded-xl border border-slate-200 p-6 shadow-sm">
                <h2 class="text-lg font-bold text-slate-800 mb-2">Tambah Dokumen Korpus Pembanding</h2>
                <p class="text-sm text-slate-500 mb-4">Dokumen yang diunggah ke sini akan menjadi basis rujukan similarity untuk memeriksa submisi mahasiswa.</p>
                
                <form id="uploadCorpusForm" class="space-y-4" enctype="multipart/form-data">
                    <div class="border-2 border-dashed border-slate-300 hover:border-emerald-400 transition-colors rounded-xl p-6 text-center cursor-pointer bg-slate-50/50" onclick="document.getElementById('corpusFileInput').click()">
                        <input type="file" id="corpusFileInput" name="document" accept=".pdf,.docx,.txt" class="hidden" onchange="handleFileSelect(this, 'corpusFileLabel')">
                        <div class="w-12 h-12 mx-auto mb-3 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center text-2xl">
                            📚
                        </div>
                        <p id="corpusFileLabel" class="text-sm font-medium text-slate-700">Pilih file naskah rujukan (Skripsi/Tesis/Jurnal)</p>
                        <p class="text-xs text-slate-400 mt-1">PDF, DOCX, TXT hingga 25 MB</p>
                    </div>

                    <div class="flex flex-col sm:flex-row items-center justify-between gap-4 pt-2">
                        <div class="flex items-center gap-4 text-sm text-slate-600">
                            <label class="font-medium text-xs text-slate-500">Tipe Dokumen:</label>
                            <select name="source_type" class="text-xs border border-slate-300 rounded px-2.5 py-1.5 focus:ring-indigo-500">
                                <option value="thesis">Skripsi / Tesis</option>
                                <option value="journal">Jurnal Ilmiah</option>
                                <option value="book">Buku Referensi</option>
                                <option value="repository">Repositori Institusi</option>
                                <option value="other">Lainnya</option>
                            </select>
                        </div>
                        <button type="submit" id="btnUploadCorpus" class="w-full sm:w-auto px-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold rounded-lg shadow-sm transition">
                            Unggah & Indeks Korpus
                        </button>
                    </div>
                </form>
                <div id="corpusAlert" class="mt-4 hidden p-4 rounded-lg text-sm"></div>
            </div>

            <!-- Corpus List -->
            <div class="bg-white rounded-xl border border-slate-200 overflow-hidden shadow-sm">
                <div class="p-5 border-b border-slate-100 flex justify-between items-center">
                    <h3 class="font-bold text-slate-800">Daftar Dokumen Korpus Terdaftar</h3>
                    <span class="text-xs text-slate-400">Total: <?= count($corpusDocs) ?> dokumen</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-sm">
                        <thead class="bg-slate-50 text-slate-500 uppercase text-xs border-b border-slate-200">
                            <tr>
                                <th class="p-4">Nama Dokumen</th>
                                <th class="p-4">Tipe Sumber</th>
                                <th class="p-4">Status Indeks</th>
                                <th class="p-4">Tanggal Tambah</th>
                                <th class="p-4 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php if (empty($corpusDocs)): ?>
                                <tr>
                                    <td colspan="5" class="p-8 text-center text-slate-400">Belum ada korpus terdaftar. Unggah dokumen rujukan untuk memulai.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($corpusDocs as $doc): ?>
                                    <tr class="hover:bg-slate-50/50">
                                        <td class="p-4 font-medium text-slate-800">
                                            <?= esc($doc['title']) ?>
                                            <div class="text-xs text-slate-400"><?= esc($doc['original_filename']) ?></div>
                                        </td>
                                        <td class="p-4">
                                            <span class="px-2 py-0.5 rounded text-xs bg-indigo-50 text-indigo-700 capitalize font-medium"><?= esc($doc['source_type'] ?? 'other') ?></span>
                                        </td>
                                        <td class="p-4">
                                            <?php if ($doc['indexing_status'] === 'completed'): ?>
                                                <span class="px-2 py-1 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">Terindeks ✓</span>
                                            <?php elseif ($doc['indexing_status'] === 'pending'): ?>
                                                <span class="px-2 py-1 rounded-full text-xs font-medium bg-amber-50 text-amber-700 border border-amber-200">Antrean Indeks</span>
                                            <?php else: ?>
                                                <span class="px-2 py-1 rounded-full text-xs font-medium bg-slate-100 text-slate-600"><?= esc($doc['indexing_status']) ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="p-4 text-slate-500"><?= date('d M Y', strtotime($doc['created_at'])) ?></td>
                                        <td class="p-4 text-right space-x-2">
                                            <button onclick="reindexCorpus(<?= $doc['id'] ?>)" class="px-3 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 font-medium rounded text-xs transition">
                                                Reindex
                                            </button>
                                            <button onclick="deleteCorpus(<?= $doc['id'] ?>)" class="px-3 py-1 bg-rose-50 hover:bg-rose-100 text-rose-700 font-medium rounded text-xs transition">
                                                Hapus
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- TAB 3: Benchmark Lab -->
        <div id="tab-benchmark" class="tab-content space-y-6 hidden">
            <div class="bg-white rounded-xl border border-slate-200 p-6 shadow-sm">
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-4">
                    <div>
                        <h2 class="text-lg font-bold text-slate-800">Laboratorium Evaluasi & Benchmark</h2>
                        <p class="text-sm text-slate-500">Mengevaluasi akurasi mesin deteksi pada 50 kasus uji baku (Identical, Copy-Edit, Paraphrase, Common-Phrase, Unrelated).</p>
                    </div>
                </div>

                <div class="overflow-x-auto mt-4">
                    <table class="w-full text-left border-collapse text-sm">
                        <thead class="bg-slate-50 text-slate-500 uppercase text-xs border-b border-slate-200">
                            <tr>
                                <th class="p-4">Run ID</th>
                                <th class="p-4">Versi Engine</th>
                                <th class="p-4">Total Kasus</th>
                                <th class="p-4">Binary Precision</th>
                                <th class="p-4">Binary Recall</th>
                                <th class="p-4">Binary F1</th>
                                <th class="p-4">Macro F1</th>
                                <th class="p-4">Waktu Eksekusi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php if (empty($benchmarkRuns)): ?>
                                <tr>
                                    <td colspan="8" class="p-8 text-center text-slate-400">Belum ada riwayat benchmark yang dijalankan.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($benchmarkRuns as $run): ?>
                                    <tr class="hover:bg-slate-50/50">
                                        <td class="p-4 font-mono font-semibold text-indigo-600">#<?= $run['id'] ?></td>
                                        <td class="p-4">
                                            <span class="px-2.5 py-1 rounded text-xs font-semibold uppercase <?= $run['engine_version'] === 'exact' ? 'bg-blue-50 text-blue-700 border border-blue-200' : 'bg-purple-50 text-purple-700 border border-purple-200' ?>">
                                                <?= esc($run['engine_version']) ?>
                                            </span>
                                        </td>
                                        <td class="p-4"><?= $run['total_cases'] ?></td>
                                        <td class="p-4 font-mono"><?= number_format($run['precision_score'] * 100, 2) ?>%</td>
                                        <td class="p-4 font-mono"><?= number_format($run['recall_score'] * 100, 2) ?>%</td>
                                        <td class="p-4 font-mono font-bold text-emerald-600"><?= number_format($run['f1_score'] * 100, 2) ?>%</td>
                                        <td class="p-4 font-mono font-bold text-indigo-600"><?= number_format($run['macro_f1'] * 100, 2) ?>%</td>
                                        <td class="p-4 text-slate-400 text-xs"><?= date('d M Y, H:i', strtotime($run['created_at'])) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <!-- JavaScript Handlers -->
    <script>
        function switchTab(tabId) {
            document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.classList.remove('border-indigo-600', 'text-indigo-600', 'font-semibold');
                btn.classList.add('border-transparent', 'text-slate-500', 'font-medium');
            });
            document.getElementById(tabId).classList.remove('hidden');
            const activeBtn = document.getElementById('btn-' + tabId);
            activeBtn.classList.add('border-indigo-600', 'text-indigo-600', 'font-semibold');
            activeBtn.classList.remove('border-transparent', 'text-slate-500');
        }

        function handleFileSelect(input, labelId) {
            if (input.files && input.files[0]) {
                document.getElementById(labelId).innerText = "File terpilih: " + input.files[0].name;
            }
        }

        // Handle upload submission
        document.getElementById('uploadSubmissionForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const btn = document.getElementById('btnUploadSubmit');
            const alertBox = document.getElementById('uploadAlert');
            const formData = new FormData(this);

            if (!document.getElementById('submissionFileInput').files[0]) {
                alert('Silakan pilih file terlebih dahulu.');
                return;
            }

            btn.disabled = true;
            btn.innerText = 'Mengunggah file...';
            alertBox.classList.add('hidden');

            try {
                const res = await fetch('<?= base_url('api/documents') ?>', {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();

                if (res.ok) {
                    alertBox.className = 'mt-4 p-4 rounded-lg text-sm bg-emerald-50 text-emerald-800 border border-emerald-200 block';
                    alertBox.innerHTML = `<strong>Berhasil:</strong> Dokumen #${data.document_id} berhasil diunggah. Menjadwalkan pemeriksaan...`;

                    // Otomatis jadwalkan check
                    const engineMode = document.querySelector('input[name="engine"]:checked').value;
                    const checkFormData = new FormData();
                    checkFormData.append('engine', engineMode);

                    const checkRes = await fetch(`<?= base_url('api/documents') ?>/${data.document_id}/check`, {
                        method: 'POST',
                        body: checkFormData
                    });
                    const checkData = await checkRes.json();

                    if (checkRes.ok) {
                        alertBox.innerHTML = `<strong>Berhasil!</strong> Dokumen #${data.document_id} masuk antrean (Laporan #${checkData.report_id}). Memuat ulang halaman...`;
                        setTimeout(() => location.reload(), 1500);
                    }
                } else {
                    throw new Error(data.message || 'Gagal mengunggah dokumen.');
                }
            } catch (err) {
                alertBox.className = 'mt-4 p-4 rounded-lg text-sm bg-rose-50 text-rose-800 border border-rose-200 block';
                alertBox.innerText = 'Error: ' + err.message;
            } finally {
                btn.disabled = false;
                btn.innerText = 'Unggah & Jadwalkan Pemeriksaan';
            }
        });

        // Handle upload corpus
        document.getElementById('uploadCorpusForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const btn = document.getElementById('btnUploadCorpus');
            const alertBox = document.getElementById('corpusAlert');
            const formData = new FormData(this);

            if (!document.getElementById('corpusFileInput').files[0]) {
                alert('Silakan pilih file korpus terlebih dahulu.');
                return;
            }

            btn.disabled = true;
            btn.innerText = 'Mengunggah & Mengindeks...';
            alertBox.classList.add('hidden');

            try {
                const res = await fetch('<?= base_url('admin/corpus') ?>', {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();

                if (res.ok) {
                    alertBox.className = 'mt-4 p-4 rounded-lg text-sm bg-emerald-50 text-emerald-800 border border-emerald-200 block';
                    alertBox.innerHTML = `<strong>Sukses:</strong> Dokumen korpus #${data.document_id} berhasil ditambahkan dan masuk antrean indeks. Memuat ulang...`;
                    setTimeout(() => location.reload(), 1500);
                } else {
                    throw new Error(data.message || 'Gagal menambah korpus.');
                }
            } catch (err) {
                alertBox.className = 'mt-4 p-4 rounded-lg text-sm bg-rose-50 text-rose-800 border border-rose-200 block';
                alertBox.innerText = 'Error: ' + err.message;
            } finally {
                btn.disabled = false;
                btn.innerText = 'Unggah & Indeks Korpus';
            }
        });

        async function triggerCheck(docId) {
            const engineRadio = document.querySelector('input[name="engine"]:checked');
            const engineMode = engineRadio ? engineRadio.value : 'hybrid';
            if (!confirm(`Jalankan pemeriksaan similarity (${engineMode.toUpperCase()}) untuk dokumen #${docId}?`)) return;
            try {
                const formData = new FormData();
                formData.append('engine', engineMode);
                const res = await fetch(`<?= base_url('api/documents') ?>/${docId}/check`, {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();
                if (res.ok) {
                    alert(`Pemeriksaan (${engineMode.toUpperCase()}) berhasil dijadwalkan! Laporan ID: #${data.report_id}. Jalankan worker jika belum aktif.`);
                    location.reload();
                } else {
                    alert('Gagal: ' + data.message);
                }
            } catch (err) {
                alert('Error: ' + err.message);
            }
        }

        async function reindexCorpus(id) {
            if (!confirm(`Jalankan reindex untuk korpus #${id}?`)) return;
            try {
                const res = await fetch(`<?= base_url('admin/corpus') ?>/${id}/reindex`, { method: 'POST' });
                const data = await res.json();
                alert(data.message);
                location.reload();
            } catch (err) {
                alert('Error: ' + err.message);
            }
        }

        async function deleteCorpus(id) {
            if (!confirm(`Hapus dokumen korpus #${id}?`)) return;
            try {
                const res = await fetch(`<?= base_url('admin/corpus') ?>/${id}`, { method: 'DELETE' });
                const data = await res.json();
                alert(data.message);
                location.reload();
            } catch (err) {
                alert('Error: ' + err.message);
            }
        }
    </script>
</body>
</html>
