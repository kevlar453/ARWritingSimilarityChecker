<?php
$currentUser = $currentUser ?? null;
$visitorStats = $visitorStats ?? ['total_unique' => 1, 'today_unique' => 1, 'total_hits' => 1];
?>
<?= view('landing/partials/head', [
    'pageTitle'       => 'Cek Keberadaan Sitasi & Daftar Pustaka Gratis — AR Tilik',
    'pageDescription' => 'Periksa keberadaan daftar pustaka secara gratis tanpa login via Crossref dan OpenAlex. Hasil status transparan (Terverifikasi, Meragukan, Tidak terverifikasi) tanpa menyimpan naskah Anda.',
    'canonicalUrl'    => base_url('cek-sitasi'),
    'pageKeywords'    => 'cek sitasi gratis, cek daftar pustaka, cek sitasi palsu, verifikasi doi crossref openalex, ar tilik',
    'enableAdSense'   => false,
]) ?>

<?= view('landing/partials/navbar', [
    'activeNav'   => 'cek-sitasi',
    'currentUser' => $currentUser,
]) ?>

    <main id="main-content" class="cek-tool-page">
        <nav class="breadcrumb-nav" aria-label="Breadcrumb">
            <ol class="breadcrumb-list">
                <li><a href="<?= base_url() ?>">Beranda</a></li>
                <li aria-hidden="true">/</li>
                <li aria-current="page">Cek Sitasi Gratis</li>
            </ol>
        </nav>
        <div class="hero-box">
            <div class="tool-kicker">Alat Gratis Tanpa Login &bull; Crossref &amp; OpenAlex</div>
            <h1>Cek Keberadaan Sitasi &amp; Daftar Pustaka</h1>
            <p class="hero-sub">
                Tempel daftar pustaka atau DOI Anda untuk memastikan tiap rujukan benar-benar tercatat di pangkalan data ilmiah Crossref dan OpenAlex &mdash; lengkap dengan tautan sumber dan kandidat terdekat bila ada perbedaan tahun atau penulis.
            </p>
        </div>

        <div class="notice-strip">
            <div class="notice-card">
                <span aria-hidden="true">🔒</span>
                <div><strong>Daftar pustaka yang Anda tempel tidak disimpan.</strong> Hasil hanya tampil langsung di layar Anda tanpa tautan publik dan tidak memotong kuota cek naskah akun.</div>
            </div>
            <div class="notice-card">
                <span aria-hidden="true">⚖️</span>
                <div><strong>Pemeriksaan ini hanya mengecek keberadaan sitasi di basis data yang dirujuk, bukan menilai kebenaran isi naskah.</strong> Status tidak ditemukan bukan bukti otomatis fabrikasi &mdash; periksa manual sebelum menyimpulkan.</div>
            </div>
        </div>

        <section class="tool-card" aria-labelledby="citation-form-heading">
            <div class="tool-meta-bar">
                <span id="citation-form-heading" style="font-weight: 700; color: var(--text);">Tempel Daftar Pustaka Anda</span>
                <span id="dailyQuotaBadge" class="quota-pill <?= ((int) ($quota['remaining'] ?? 5) <= 0) ? 'quota-exhausted' : '' ?>">
                    <span>Sisa kuota hari ini: <strong id="quotaRemainingNum"><?= (int) ($quota['remaining'] ?? 5) ?></strong>/<?= (int) ($quota['limit'] ?? 5) ?> pemeriksaan (maks 10 sitasi/cek)</span>
                </span>
            </div>

            <form id="citationCheckForm" method="POST" action="<?= base_url('api/v1/citation-check') ?>" novalidate>
                <div class="field-group">
                    <div class="field-label-row">
                        <label for="citationsInput" class="field-label">Daftar Pustaka atau DOI (satu entri per baris atau satu blok teks) <span aria-hidden="true" style="color:#be123c;">*</span></label>
                        <span id="entryCountPill" class="entry-counter">0 / 10 entri</span>
                    </div>
                    <textarea
                        id="citationsInput"
                        name="citations"
                        class="citation-textarea"
                        required
                        maxlength="15000"
                        data-error="citationsError"
                        aria-invalid="false"
                        aria-describedby="citation-format-hint citation-privacy-note"
                        placeholder="Contoh:&#10;Vaswani, A., Shazeer, N., Parmar, N., Uszkoreit, J., Jones, L., Gomez, A. N., Kaiser, L., &amp; Polosukhin, I. (2017). Attention is all you need. NeurIPS.&#10;LeCun, Y., Bengio, Y., &amp; Hinton, G. (2015). Deep learning. Nature, 521(7553), 436-444. https://doi.org/10.1038/nature14539"
                    ></textarea>
                    <p id="citation-format-hint" class="field-hint">
                        Mendukung gaya APA, IEEE, Harvard, Chicago, maupun daftar DOI langsung (mis. <code>10.1038/nature14539</code>). Maksimal 10 sitasi per cek.
                    </p>
                    <p id="citation-privacy-note" class="field-hint">
                        Daftar pustaka yang Anda tempel tidak disimpan. Log server hanya mencatat jumlah pemeriksaan harian.
                    </p>
                    <div id="citationsError" class="field-error" hidden></div>
                </div>

                <div class="button-row">
                    <button type="submit" id="submitCheckBtn" class="btn btn-primary" <?= ((int) ($quota['remaining'] ?? 5) <= 0) ? 'disabled' : '' ?>>
                        Periksa
                    </button>
                    <button type="button" id="loadSampleBtn" class="btn btn-secondary">
                        Muat Contoh Uji (Campuran)
                    </button>
                    <button type="button" id="clearInputBtn" class="btn btn-secondary">
                        Bersihkan
                    </button>
                </div>
            </form>

            <div id="progressPanel" class="progress-panel" hidden>
                <div class="progress-head">
                    <span id="progressLabel">Menyiapkan pemeriksaan ke Crossref &amp; OpenAlex...</span>
                    <span id="progressCount" style="font-family: 'JetBrains Mono', monospace;">0 / 0</span>
                </div>
                <progress id="checkProgressBar" class="progress-bar-native" value="0" max="10"></progress>
            </div>

            <div id="resultsPanel" class="results-panel" hidden>
                <div class="summary-banner">
                    <h2 id="summaryHeadline" tabindex="-1">Hasil Pemeriksaan Sitasi</h2>
                    <div id="summaryPills" class="summary-pills"></div>
                </div>

                <ol id="resultsList" class="results-list" role="list"></ol>
            </div>

            <div id="ctaPanel" class="cta-card" hidden>
                <div>
                    <h3>Ingin Memeriksa Kesepadanan &amp; Audit Sitasi Naskah Lengkap?</h3>
                    <p>
                        Alat ini hanya memeriksa keberadaan entri daftar pustaka. AR Tilik memeriksa keseluruhan isi naskah terhadap lebih dari satu dekade literatur ilmiah dengan Mesin Tiga Lensa (5-Gram, TF-IDF, Semantik), audit sitasi isi vs pustaka, dan sertifikat ber-QR Code.
                    </p>
                </div>
                <div class="cta-actions">
                    <a href="<?= base_url('/?utm_source=cek-sitasi&utm_medium=cta&utm_campaign=citation_checker&auth=register') ?>" class="btn btn-cta-white">
                        Periksa naskah lengkap dengan AR Tilik &mdash; gratis 3 cek/minggu
                    </a>
                    <button type="button" id="checkAgainBtn" class="btn btn-cta-ghost">
                        Periksa lagi
                    </button>
                </div>
            </div>
        </section>

        <div id="liveAnnouncer" class="visually-hidden" aria-live="polite"></div>
    </main>

    <script>
        (function() {
            const MAX_ENTRIES = 10;
            const ENDPOINT = <?= json_encode(base_url('api/v1/citation-check')) ?>;
            const UNVERIFIED_MANDATORY = 'Tidak ditemukan di Crossref dan OpenAlex. Bisa berarti sitasi fabrikasi, salah ketik, atau sumber tidak terindeks — periksa manual sebelum menyimpulkan.';

            const form = document.getElementById('citationCheckForm');
            const textarea = document.getElementById('citationsInput');
            const errorEl = document.getElementById('citationsError');
            const entryCountPill = document.getElementById('entryCountPill');
            const submitBtn = document.getElementById('submitCheckBtn');
            const loadSampleBtn = document.getElementById('loadSampleBtn');
            const clearInputBtn = document.getElementById('clearInputBtn');
            const progressPanel = document.getElementById('progressPanel');
            const progressLabel = document.getElementById('progressLabel');
            const progressCount = document.getElementById('progressCount');
            const progressBar = document.getElementById('checkProgressBar');
            const resultsPanel = document.getElementById('resultsPanel');
            const summaryHeadline = document.getElementById('summaryHeadline');
            const summaryPills = document.getElementById('summaryPills');
            const resultsList = document.getElementById('resultsList');
            const ctaPanel = document.getElementById('ctaPanel');
            const checkAgainBtn = document.getElementById('checkAgainBtn');
            const quotaRemainingNum = document.getElementById('quotaRemainingNum');
            const dailyQuotaBadge = document.getElementById('dailyQuotaBadge');
            const liveAnnouncer = document.getElementById('liveAnnouncer');

            let isSubmitting = false;

            function setFieldError(message) {
                const ids = (textarea.getAttribute('aria-describedby') || '')
                    .split(/\s+/)
                    .filter((id) => id && id !== 'citationsError');
                if (message) {
                    errorEl.textContent = message;
                    errorEl.hidden = false;
                    textarea.setAttribute('aria-invalid', 'true');
                    textarea.classList.add('user-invalid-fallback');
                    ids.push('citationsError');
                    liveAnnouncer.textContent = message;
                } else {
                    errorEl.textContent = '';
                    errorEl.hidden = true;
                    textarea.setAttribute('aria-invalid', 'false');
                    textarea.classList.remove('user-invalid-fallback');
                }
                if (ids.length) {
                    textarea.setAttribute('aria-describedby', ids.join(' '));
                }
            }

            // Estimasi jumlah entri di sisi klien agar pengguna mendapat pratinjau instan sebelum mengirim
            function estimateEntries(raw) {
                const text = (raw || '').replace(/\r\n?/g, '\n').trim();
                if (!text) return 0;
                const headingRe = /^(?:[0-9.\s]*\b(?:daftar\s+pustaka|references?|bibliography|bibliografi|daftar\s+referensi|kepustakaan|rujukan|works\s+cited)\b)\s*:?\s*$/i;
                const paras = text.split(/\n\s*\n+/);
                let count = 0;
                for (const p of paras) {
                    const lines = p.split('\n').map((s) => s.trim()).filter((s) => s && !headingRe.test(s));
                    let buf = [];
                    for (const ln of lines) {
                        const isNumStart = /^(?:\[\d+\]|\(\d+\)|\d{1,3}[\.\)])\s+\S/.test(ln);
                        const isCont = /^[a-z]/.test(ln) || /^(?:https?:\/\/|www\.|doi:|available\b|retrieved\b|diakses\b|in\s|dalam\s|(?:jurnal|journal|proceedings|prosiding|ieee|acm|springer|elsevier|wiley|vol\.|volume|no\.|pp\.|hal\.|hlm\.|ed\.|penerbit|press)\b|[\d\s\-–,;:\.\(\)]+$)/i.test(ln);
                        const bufText = buf.join(' ');
                        const bufHasDoi = /10\.\d{4,9}\/\S+/i.test(bufText);
                        const bufHasYear = /\b(?:19|20)\d{2}\b/.test(bufText);
                        const bufDoiOnly = /^(?:https?:\/\/(?:dx\.)?doi\.org\/|doi:\s*|10\.\d{4,9}\/)/i.test(bufText) && bufText.split(/\s+/).length <= 2;
                        const isBareDoi = /^10\.\d{4,9}\/\S+$/.test(ln);
                        const isDoiLine = /^(?:https?:\/\/(?:dx\.)?doi\.org\/|doi:\s*|10\.\d{4,9}\/)/i.test(ln);
                        const startsAuthorYear = /^[A-ZÀ-ÖØ-Ý][^\n]{1,65}\((?:(?:19|20)\d{2}[a-z]?|n\.d\.|t\.th\.)\)/.test(ln) || /^[A-ZÀ-ÖØ-Ý][a-zA-Z\-']+\s*,\s*[A-Z]\./.test(ln);

                        let splitHere = false;
                        if (buf.length > 0) {
                            if (isNumStart) splitHere = true;
                            else if (isBareDoi && (bufHasDoi || bufDoiOnly || (bufHasYear && bufText.length >= 25 && /\.$/.test(bufText)))) splitHere = true;
                            else if (isDoiLine) splitHere = bufHasDoi || bufDoiOnly;
                            else if (!isCont && startsAuthorYear && (bufHasYear || bufHasDoi || bufText.length >= 25)) splitHere = true;
                            else if (!isCont && (bufHasYear || bufHasDoi) && /(?:\.|\d|https?:\/\/\S+|10\.\d{4,9}\/\S+)$/.test(bufText) && /\b(?:19|20)\d{2}\b|10\.\d{4,9}\/|["“]/.test(ln)) splitHere = true;
                        }
                        if (splitHere) {
                            if (bufText.length >= 6) count += 1;
                            buf = [ln];
                        } else {
                            buf.push(ln);
                        }
                    }
                    if (buf.join(' ').trim().length >= 6) count += 1;
                }
                return count;
            }

            function updateCounter() {
                const count = estimateEntries(textarea.value);
                entryCountPill.textContent = count > MAX_ENTRIES
                    ? `⚠ ${count} / ${MAX_ENTRIES} entri (melebihi batas)`
                    : `${count} / ${MAX_ENTRIES} entri`;
                entryCountPill.classList.toggle('is-over', count > MAX_ENTRIES);
            }

            textarea.addEventListener('input', () => {
                updateCounter();
                if (textarea.getAttribute('aria-invalid') === 'true') {
                    const c = estimateEntries(textarea.value);
                    if (c > 0 && c <= MAX_ENTRIES) {
                        setFieldError('');
                    }
                }
            });

            textarea.addEventListener('blur', () => {
                if (textarea.value.trim() === '') return;
                const c = estimateEntries(textarea.value);
                if (c > MAX_ENTRIES) {
                    setFieldError(`Maksimal ${MAX_ENTRIES} sitasi per pemeriksaan. Terdeteksi sekitar ${c} entri.`);
                }
            });

            loadSampleBtn.addEventListener('click', () => {
                textarea.value = [
                    'Vaswani, A., Shazeer, N., Parmar, N., Uszkoreit, J., Jones, L., Gomez, A. N., Kaiser, L., & Polosukhin, I. (2017). Attention is all you need. Advances in Neural Information Processing Systems, 30.',
                    'LeCun, Y., Bengio, Y., & Hinton, G. (2015). Deep learning. Nature, 521(7553), 436-444. https://doi.org/10.1038/nature14539',
                    'Devlin, J., Chang, M. W., Lee, K., & Toutanova, K. (2023). BERT: Pre-training of deep bidirectional transformers for language understanding. NAACL-HLT.',
                    'Susanto, R. (2017). Attention is all you need. Journal of Artificial Intelligence.',
                    'Pratama, D., & Kusuma, A. (2022). Efektivitas pembelajaran berbasis digital di sekolah menengah pertama. Jurnal Inovasi Pendidikan Indonesia, 8(3), 201-215.'
                ].join('\n');
                setFieldError('');
                updateCounter();
                textarea.focus();
            });

            clearInputBtn.addEventListener('click', () => {
                textarea.value = '';
                setFieldError('');
                updateCounter();
                resultsPanel.hidden = true;
                ctaPanel.hidden = true;
                progressPanel.hidden = true;
                resultsList.replaceChildren();
                textarea.focus();
            });

            checkAgainBtn.addEventListener('click', () => {
                resultsPanel.hidden = true;
                ctaPanel.hidden = true;
                progressPanel.hidden = true;
                resultsList.replaceChildren();
                textarea.focus();
            });

            function updateQuotaDisplay(quota) {
                if (!quota) return;
                const rem = Number(quota.remaining ?? 0);
                quotaRemainingNum.textContent = String(rem);
                dailyQuotaBadge.classList.toggle('quota-exhausted', rem <= 0);
                if (rem <= 0) {
                    submitBtn.disabled = true;
                }
            }

            function statusMeta(status) {
                switch (status) {
                    case 'verified':
                        return { cls: 'verified', pillCls: 'pill-verified', icon: '✓', label: 'Terverifikasi' };
                    case 'doubtful':
                        return { cls: 'doubtful', pillCls: 'pill-doubtful', icon: '⚠', label: 'Meragukan' };
                    case 'unavailable':
                        return { cls: 'unavailable', pillCls: 'pill-unavailable', icon: '⏱', label: 'Tidak dapat diperiksa saat ini' };
                    default:
                        return { cls: 'unverified', pillCls: 'pill-unverified', icon: '?', label: 'Tidak terverifikasi' };
                }
            }

            function renderResultCard(item) {
                const meta = statusMeta(item.status);
                const li = document.createElement('li');
                li.className = `result-card result-card--${meta.cls}`;
                li.dataset.entryIndex = String(item.index || 0);

                const top = document.createElement('div');
                top.className = 'result-top';

                const idxSpan = document.createElement('span');
                idxSpan.className = 'result-idx';
                idxSpan.textContent = `Sitasi #${item.index || ''}`;
                top.appendChild(idxSpan);

                const badgeGroup = document.createElement('div');
                badgeGroup.className = 'result-badge-group';

                const badge = document.createElement('span');
                badge.className = `summary-pill ${meta.pillCls}`;
                badge.textContent = `${meta.icon} ${item.status_label || meta.label}`;
                badgeGroup.appendChild(badge);

                if (item.source) {
                    const srcChip = document.createElement('span');
                    srcChip.className = 'source-chip';
                    srcChip.textContent = item.source === 'crossref' ? 'Crossref' : (item.source === 'openalex' ? 'OpenAlex' : item.source);
                    badgeGroup.appendChild(srcChip);
                }
                if (item.cached) {
                    const cacheChip = document.createElement('span');
                    cacheChip.className = 'source-chip';
                    cacheChip.textContent = 'Cache';
                    badgeGroup.appendChild(cacheChip);
                }

                top.appendChild(badgeGroup);
                li.appendChild(top);

                const rawP = document.createElement('p');
                rawP.className = 'result-raw';
                rawP.textContent = item.raw_text || '';
                li.appendChild(rawP);

                const detail = document.createElement('div');
                if (item.status === 'verified') {
                    detail.className = 'result-detail';
                    const titleDiv = document.createElement('div');
                    titleDiv.className = 'result-detail-title';
                    titleDiv.textContent = item.matched_title || 'Tercatat pada basis data rujukan';
                    detail.appendChild(titleDiv);

                    const metaParts = [];
                    if (item.matched_authors) metaParts.push(item.matched_authors);
                    if (item.matched_year) metaParts.push(String(item.matched_year));
                    if (metaParts.length) {
                        const metaDiv = document.createElement('div');
                        metaDiv.textContent = metaParts.join(' • ');
                        detail.appendChild(metaDiv);
                    }
                    if (item.source_url) {
                        const a = document.createElement('a');
                        a.className = 'result-link';
                        a.href = item.source_url;
                        a.target = '_blank';
                        a.rel = 'noopener noreferrer';
                        a.textContent = `Buka sumber (${item.doi || item.source_url}) ↗`;
                        detail.appendChild(a);
                    }
                } else if (item.status === 'doubtful') {
                    detail.className = 'result-detail result-detail--warning';
                    const reasonDiv = document.createElement('div');
                    reasonDiv.className = 'result-detail-title';
                    reasonDiv.textContent = item.reason || 'Cocok sebagian — periksa kesesuaian judul, penulis, dan tahun.';
                    detail.appendChild(reasonDiv);

                    if (item.matched_title) {
                        const candDiv = document.createElement('div');
                        const candParts = [`Kandidat terdekat: ${item.matched_title}`];
                        if (item.matched_authors) candParts.push(item.matched_authors);
                        if (item.matched_year) candParts.push(String(item.matched_year));
                        candDiv.textContent = candParts.join(' — ');
                        detail.appendChild(candDiv);
                    }
                    if (item.source_url) {
                        const a = document.createElement('a');
                        a.className = 'result-link';
                        a.href = item.source_url;
                        a.target = '_blank';
                        a.rel = 'noopener noreferrer';
                        a.textContent = `Lihat kandidat sumber (${item.doi || item.source_url}) ↗`;
                        detail.appendChild(a);
                    }
                } else if (item.status === 'unavailable') {
                    detail.className = 'result-detail';
                    detail.textContent = item.reason || 'Layanan Crossref atau OpenAlex sedang lambat saat ini. Silakan ulangi beberapa saat lagi.';
                } else {
                    detail.className = 'result-detail result-detail--danger';
                    detail.textContent = item.reason || UNVERIFIED_MANDATORY;
                }

                li.appendChild(detail);
                return li;
            }

            function insertCardOrdered(item) {
                const newCard = renderResultCard(item);
                const idx = Number(item.index || 0);
                const existing = Array.from(resultsList.children);
                const after = existing.find((node) => Number(node.dataset.entryIndex || 0) > idx);
                if (after) {
                    resultsList.insertBefore(newCard, after);
                } else {
                    resultsList.appendChild(newCard);
                }
            }

            function renderSummary(data) {
                summaryHeadline.textContent = data.summary_text || `${data.verified_count || 0} dari ${data.total_entries || 0} sitasi terverifikasi`;
                summaryPills.replaceChildren();

                const counts = [
                    { n: Number(data.verified_count || 0), cls: 'pill-verified', label: 'Terverifikasi', showZero: true },
                    { n: Number(data.doubtful_count || 0), cls: 'pill-doubtful', label: 'Meragukan', showZero: false },
                    { n: Number(data.unverified_count || 0), cls: 'pill-unverified', label: 'Tidak terverifikasi', showZero: true },
                    { n: Number(data.unavailable_count || 0), cls: 'pill-unavailable', label: 'Tidak dapat diperiksa saat ini', showZero: false },
                ];

                counts.forEach((c) => {
                    if (c.n === 0 && !c.showZero) return;
                    const pill = document.createElement('span');
                    pill.className = `summary-pill ${c.cls}`;
                    pill.textContent = `${c.n} ${c.label}`;
                    summaryPills.appendChild(pill);
                });

                liveAnnouncer.textContent = `Pemeriksaan selesai: ${summaryHeadline.textContent}`;
            }

            form.addEventListener('submit', async (e) => {
                e.preventDefault();
                if (isSubmitting) return;

                const rawText = textarea.value.trim();
                if (!rawText) {
                    setFieldError('Tempel minimal satu sitasi atau DOI terlebih dahulu.');
                    textarea.focus();
                    return;
                }
                const estCount = estimateEntries(rawText);
                if (estCount > MAX_ENTRIES) {
                    setFieldError(`Maksimal ${MAX_ENTRIES} sitasi per pemeriksaan. Anda memasukkan sekitar ${estCount} entri.`);
                    textarea.focus();
                    return;
                }

                setFieldError('');
                isSubmitting = true;
                submitBtn.disabled = true;
                submitBtn.textContent = 'Memeriksa...';

                resultsList.replaceChildren();
                resultsPanel.hidden = false;
                summaryHeadline.textContent = 'Sedang memeriksa sitasi...';
                summaryPills.replaceChildren();
                ctaPanel.hidden = true;

                progressPanel.hidden = false;
                progressBar.value = 0;
                progressBar.max = Math.max(1, estCount);
                progressCount.textContent = `0 / ${Math.max(1, estCount)}`;
                progressLabel.textContent = 'Menghubungkan ke Crossref & OpenAlex...';
                liveAnnouncer.textContent = 'Pemeriksaan sitasi dimulai.';

                try {
                    const res = await fetch(ENDPOINT, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/x-ndjson, application/json',
                        },
                        body: JSON.stringify({ citations: rawText, stream: true }),
                    });

                    const contentType = res.headers.get('Content-Type') || '';
                    if (!res.ok || contentType.includes('application/json')) {
                        const json = await res.json();
                        if (json.quota) updateQuotaDisplay(json.quota);

                        if (!res.ok || json.status === 'error') {
                            progressPanel.hidden = true;
                            resultsPanel.hidden = true;
                            setFieldError(json.message || 'Gagal memeriksa sitasi. Silakan periksa masukan Anda.');
                            textarea.focus();
                            return;
                        }

                        if (json.data) {
                            resultsList.replaceChildren();
                            (json.data.items || []).forEach(insertCardOrdered);
                            renderSummary(json.data);
                            progressPanel.hidden = true;
                            ctaPanel.hidden = false;
                            summaryHeadline.focus();
                        }
                        return;
                    }

                    // Konsumsi NDJSON stream agar progres tampil per sitasi secara nyata
                    const reader = res.body.getReader();
                    const decoder = new TextDecoder('utf-8');
                    let buffer = '';
                    let doneReceived = false;

                    function handleNdjsonLine(line) {
                        const clean = line.trim();
                        if (!clean) return;
                        let msg;
                        try {
                            msg = JSON.parse(clean);
                        } catch (_) {
                            return;
                        }
                        if (msg.event === 'quota' && msg.quota) {
                            updateQuotaDisplay(msg.quota);
                        } else if (msg.event === 'start') {
                            const total = Number(msg.total || 1);
                            progressBar.max = total;
                            progressBar.value = 0;
                            progressCount.textContent = `0 / ${total}`;
                            progressLabel.textContent = `Memeriksa ${total} sitasi...`;
                        } else if (msg.event === 'progress') {
                            const completed = Number(msg.completed || 0);
                            const total = Number(msg.total || 1);
                            progressBar.max = total;
                            progressBar.value = completed;
                            progressCount.textContent = `${completed} / ${total}`;
                            progressLabel.textContent = `Memeriksa sitasi ${completed} dari ${total}...`;
                            if (msg.item) {
                                insertCardOrdered(msg.item);
                            }
                        } else if (msg.event === 'done' && msg.data) {
                            doneReceived = true;
                            if (resultsList.children.length === 0 && Array.isArray(msg.data.items)) {
                                msg.data.items.forEach(insertCardOrdered);
                            }
                            renderSummary(msg.data);
                            progressPanel.hidden = true;
                            ctaPanel.hidden = false;
                            summaryHeadline.focus();
                        } else if (msg.event === 'error') {
                            progressPanel.hidden = true;
                            resultsPanel.hidden = true;
                            setFieldError(msg.message || 'Terjadi gangguan saat memeriksa sitasi.');
                            textarea.focus();
                        }
                    }

                    while (true) {
                        const { value, done } = await reader.read();
                        if (done) break;
                        buffer += decoder.decode(value, { stream: true });
                        const lines = buffer.split('\n');
                        buffer = lines.pop() || '';
                        lines.forEach(handleNdjsonLine);
                    }
                    if (buffer.trim()) {
                        handleNdjsonLine(buffer);
                    }
                    if (!doneReceived) {
                        progressPanel.hidden = true;
                    }
                } catch (err) {
                    progressPanel.hidden = true;
                    resultsPanel.hidden = true;
                    setFieldError('Kendala koneksi jaringan saat memeriksa sitasi. Silakan coba kembali.');
                    textarea.focus();
                } finally {
                    isSubmitting = false;
                    submitBtn.textContent = 'Periksa';
                    if (Number(quotaRemainingNum.textContent || '0') > 0) {
                        submitBtn.disabled = false;
                    }
                }
            });

            updateCounter();
        })();
    </script>

<?= view('landing/partials/footer', [
    'currentUser'  => $currentUser,
    'visitorStats' => $visitorStats ?? ['total_unique' => 1, 'today_unique' => 1, 'total_hits' => 1],
]) ?>

<?= view('landing/partials/modals', [
    'authSecurityToken' => $authSecurityToken ?? '',
]) ?>
