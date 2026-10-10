<?php
$r_id = $report['id'];
$d_id = $report['document_id'];
$engine = strtoupper($report['engine'] ?? 'hybrid');
$dec = strtolower($report['predicted_decision'] ?? 'review');
$pattern = $report['evidence_pattern'] ?? 'SEMANTIC_PARAPHRASE';
$agreement = $report['evidence_agreement'] ?? 'COMPLEMENTARY_EVIDENCE';
$sources_agree = !empty($report['sources_agree']);

$overall_sim = (float) ($report['overall_similarity'] ?? 0);
$exact_sim = (float) ($report['exact_similarity'] ?? 0);
$cosine_sim = (float) ($report['cosine_similarity'] ?? 0);
$longest_match = (int) ($report['longest_match_words'] ?? 0);
$shared_terms = (int) ($report['shared_terms_count'] ?? 0);
$avg_idf = (float) ($report['average_shared_term_idf'] ?? 0);
$rationale = $report['rationale'] ?? 'Analisis dekomposisi bukti selesai.';

$doc_title = $document['title'] ?? 'Dokumen Karya Ilmiah';
$filename = $document['original_filename'] ?? "document_{$d_id}.pdf";
$date_str = date('d F Y, H:i', strtotime($report['created_at'])) . ' WIB';

$seed = "ARTILIK:{$r_id}:{$d_id}:" . $report['created_at'] . ":{$overall_sim}:OFFICIAL_CERTIFICATE";
$h = strtoupper(substr(hash('sha256', $seed), 0, 12));
$cert_hash = "ART-" . substr($h, 0, 4) . "-" . substr($h, 4, 4) . "-" . substr($h, 8, 4);

$total_words = $evidenceData['total_words'] ?? 0;
$exec_time = $evidenceData['execution_time_seconds'] ?? 0;

if ($dec === 'match') {
    $dec_label = "MATCH (Kesamaan Harfiah Kuat)";
    $dec_desc = "Terdeteksi pasase identik verbatim melebihi ambang batas kesamaan sekuensial institusi.";
    $dec_color = "#b91c1c";
    $dec_bg = "#fef2f2";
    $dec_border = "#f87171";
} elseif ($dec === 'review') {
    $dec_label = "REVIEW (Perlu Tinjauan Akademik)";
    $dec_desc = "Terdeteksi bukti kontekstual: kutipan, frasa metodologi umum, atau parafrasa leksikal yang memerlukan verifikasi pembimbing/komite penguji.";
    $dec_color = "#b45309";
    $dec_bg = "#fffbeb";
    $dec_border = "#fbbf24";
} else {
    $dec_label = "NO MATCH (Tidak Ada Bukti Tekstual)";
    $dec_desc = "Tidak ditemukan bukti kesamaan teks sekuensial maupun orientasi leksikal yang signifikan.";
    $dec_color = "#047857";
    $dec_bg = "#ecfdf5";
    $dec_border = "#34d399";
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>Laporan Bukti Kemiripan #<?= $r_id ?> — AR Tilik</title>
<!-- Favicon & Brand Icons -->
<link rel="icon" type="image/x-icon" href="<?= base_url('favicon.ico?v=20261008') ?>">
<link rel="icon" type="image/png" sizes="32x32" href="<?= base_url('favicon-32x32.png?v=20261008') ?>">
<link rel="icon" type="image/png" sizes="16x16" href="<?= base_url('favicon-16x16.png?v=20261008') ?>">
<link rel="apple-touch-icon" sizes="180x180" href="<?= base_url('apple-touch-icon.png?v=20261008') ?>">
<link rel="shortcut icon" type="image/x-icon" href="<?= base_url('favicon.ico?v=20261008') ?>">
<style>
    @page {
        size: A4;
        margin: 15mm 15mm 18mm 15mm;
        @top-right {
            content: "AR Tilik v1.3.0 • Sertifikasi #<?= $cert_hash ?>";
            font-size: 7pt;
            font-family: 'Liberation Sans', 'Helvetica Neue', Arial, sans-serif;
            color: #94a3b8;
        }
        @bottom-left {
            content: "Dicetak secara otomatis oleh Sistem AR Tilik • Keabsahan digital terverifikasi";
            font-size: 7pt;
            font-family: 'Liberation Sans', 'Helvetica Neue', Arial, sans-serif;
            color: #94a3b8;
        }
        @bottom-right {
            content: "Halaman " counter(page) " dari " counter(pages);
            font-size: 7pt;
            font-family: 'Liberation Sans', 'Helvetica Neue', Arial, sans-serif;
            color: #94a3b8;
            font-weight: bold;
        }
    }

    body {
        font-family: 'Liberation Sans', 'Helvetica Neue', Arial, sans-serif;
        color: #1e293b;
        background: #ffffff;
        margin: 0;
        padding: 0;
        font-size: 9pt;
        line-height: 1.45;
    }

    .header-table {
        width: 100%;
        border-bottom: 2.5px solid #0f294a;
        padding-bottom: 10px;
        margin-bottom: 14px;
    }
    .brand-title {
        font-size: 16pt;
        font-weight: 800;
        color: #0f294a;
        letter-spacing: -0.5px;
        margin: 0;
    }
    .brand-sub {
        font-size: 8pt;
        color: #64748b;
        margin-top: 2px;
        text-transform: uppercase;
        letter-spacing: 0.8px;
    }
    .cert-box {
        text-align: right;
        font-size: 8pt;
    }
    .cert-code {
        display: inline-block;
        font-family: 'Liberation Mono', monospace;
        font-weight: bold;
        background: #f1f5f9;
        color: #0f294a;
        padding: 4px 8px;
        border-radius: 4px;
        border: 1px solid #cbd5e1;
        letter-spacing: 1px;
    }

    .report-title-box {
        text-align: center;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 6px;
        padding: 10px;
        margin-bottom: 14px;
    }
    .report-title {
        font-size: 12pt;
        font-weight: 800;
        color: #0f294a;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin: 0;
    }
    .report-subtitle {
        font-size: 8pt;
        color: #475569;
        margin-top: 3px;
    }

    .meta-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 14px;
        font-size: 8.5pt;
    }
    .meta-table td {
        padding: 4px 8px;
        vertical-align: top;
        border-bottom: 1px solid #f1f5f9;
    }
    .meta-label {
        width: 130px;
        color: #64748b;
        font-weight: 600;
    }
    .meta-val {
        color: #0f294a;
    }

    .decision-banner {
        border: 1.5px solid <?= $dec_border ?>;
        background: <?= $dec_bg ?>;
        border-radius: 8px;
        padding: 12px 16px;
        margin-bottom: 14px;
        page-break-inside: avoid;
    }
    .decision-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 6px;
    }
    .decision-title {
        font-size: 12pt;
        font-weight: 800;
        color: <?= $dec_color ?>;
        text-transform: uppercase;
    }
    .decision-score {
        font-size: 18pt;
        font-weight: 900;
        color: <?= $dec_color ?>;
    }
    .decision-desc {
        font-size: 8.5pt;
        color: #334155;
        line-height: 1.4;
    }
    .tag-pill {
        display: inline-block;
        font-size: 7.5pt;
        font-weight: bold;
        padding: 2px 7px;
        border-radius: 4px;
        background: #ffffff;
        border: 1px solid #cbd5e1;
        margin-top: 5px;
        margin-right: 4px;
        color: #334155;
    }

    .dual-lens-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 12px;
        margin-bottom: 14px;
        page-break-inside: avoid;
    }
    .lens-card {
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        background: #ffffff;
        padding: 10px 12px;
    }
    .lens-card-header {
        border-bottom: 1.5px solid #0f294a;
        padding-bottom: 4px;
        margin-bottom: 8px;
        display: flex;
        justify-content: space-between;
        align-items: baseline;
    }
    .lens-card-title {
        font-size: 9.5pt;
        font-weight: bold;
        color: #0f294a;
        text-transform: uppercase;
    }
    .lens-card-sub {
        font-size: 7.5pt;
        color: #64748b;
    }
    .metric-row {
        display: flex;
        justify-content: space-between;
        padding: 3px 0;
        border-bottom: 1px dotted #e2e8f0;
        font-size: 8.5pt;
    }
    .metric-lbl { color: #475569; }
    .metric-val { font-weight: bold; color: #0f294a; }

    .rationale-box {
        background: #f8fafc;
        border-left: 3.5px solid #1e3a8a;
        padding: 10px 14px;
        margin-bottom: 14px;
        border-radius: 0 6px 6px 0;
        page-break-inside: avoid;
    }
    .rationale-title {
        font-size: 9pt;
        font-weight: bold;
        color: #0f294a;
        text-transform: uppercase;
        margin-bottom: 4px;
        letter-spacing: 0.5px;
    }
    .rationale-quote {
        font-size: 8.5pt;
        font-style: italic;
        color: #1e293b;
        line-height: 1.45;
        margin-bottom: 6px;
    }
    .fairness-note {
        font-size: 7.5pt;
        color: #475569;
        border-top: 1px solid #e2e8f0;
        padding-top: 5px;
        margin-top: 5px;
        line-height: 1.35;
    }

    .data-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 14px;
        font-size: 8.5pt;
    }
    .data-table th {
        background: #0f294a;
        color: #ffffff;
        padding: 6px 8px;
        font-size: 8pt;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        border: 1px solid #0f294a;
    }
    .data-table td {
        padding: 5px 8px;
        border: 1px solid #e2e8f0;
        vertical-align: middle;
    }
    .data-table tr:nth-child(even) td {
        background: #f8fafc;
    }

    .signature-grid {
        display: grid;
        grid-template-columns: 1fr 1fr 1fr;
        gap: 15px;
        margin-top: 24px;
        page-break-inside: avoid;
    }
    .sig-box {
        text-align: center;
        font-size: 8pt;
        color: #334155;
    }
    .sig-role {
        font-weight: bold;
        margin-bottom: 50px;
        color: #0f294a;
    }
    .sig-line {
        border-top: 1px solid #475569;
        margin: 0 10px;
        padding-top: 4px;
    }

    h3.section-header {
        font-size: 10pt;
        font-weight: bold;
        color: #0f294a;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin: 14px 0 8px 0;
        border-bottom: 1.5px solid #e2e8f0;
        padding-bottom: 4px;
    }
</style>
</head>
<body>

    <?php
        $verifyUrl = "https://tilik.arsetontong.top/verify/" . urlencode($cert_hash);
        $qrApiUrl = "https://api.qrserver.com/v1/create-qr-code/?size=120x120&color=0f-29-4a&data=" . urlencode($verifyUrl);
        $logoPath = FCPATH . 'images/logo.png';
        $logoBase64 = file_exists($logoPath) ? base64_encode(file_get_contents($logoPath)) : '';
        $logoSrc = $logoBase64 ? 'data:image/png;base64,' . $logoBase64 : base_url('images/logo.png');
    ?>
    <!-- Header / Kop -->
    <table class="header-table">
        <tr>
            <td style="vertical-align: middle;">
                <table cellpadding="0" cellspacing="0" style="border: none; margin: 0; padding: 0;">
                    <tr>
                        <td style="vertical-align: middle; padding-right: 12px; width: 44px; border: none;">
                            <img src="<?= $logoSrc ?>" alt="AR Tilik Logo" style="width: 44px; height: 44px; object-fit: contain; display: block;" />
                        </td>
                        <td style="vertical-align: middle; border: none; padding: 0;">
                            <div class="brand-title">AR TILIK</div>
                            <div class="brand-sub">Academic Writing Similarity • Evidence Decomposition Engine</div>
                        </td>
                    </tr>
                </table>
            </td>
            <td class="cert-box" style="vertical-align: middle;">
                <div style="display: flex; align-items: center; justify-content: flex-end; gap: 10px;">
                    <div style="text-align: right;">
                        <div style="color: #64748b; font-size: 7.5pt; margin-bottom: 2px;">NOMOR SERTIFIKAT VERIFIKASI</div>
                        <a href="<?= esc($verifyUrl) ?>" target="_blank" class="cert-code" style="text-decoration: none; color: #0f294a;"><?= esc($cert_hash) ?></a>
                        <div style="font-size: 6.5pt; color: #64748b; margin-top: 2px;">Pindai QR untuk Validasi Keaslian</div>
                    </div>
                    <a href="<?= esc($verifyUrl) ?>" target="_blank">
                        <img src="<?= esc($qrApiUrl) ?>" alt="QR Code" style="width: 52px; height: 52px; border: 1px solid #cbd5e1; border-radius: 4px; padding: 2px; background: #ffffff;" />
                    </a>
                </div>
            </td>
        </tr>
    </table>

    <!-- Title Box -->
    <div class="report-title-box">
        <h1 class="report-title">LEMBAR HASIL UJI KEMIRIPAN TEKS AKADEMIK</h1>
        <div class="report-subtitle">Dokumen Verifikasi Resmi Dekomposisi Bukti Berdasarkan Prinsip Keadilan Akademik</div>
    </div>

    <!-- Metadata Table -->
    <table class="meta-table">
        <tr>
            <td class="meta-label">Judul Naskah</td>
            <td class="meta-val" colspan="3"><strong><?= esc($doc_title) ?></strong></td>
        </tr>
        <tr>
            <td class="meta-label">Nama Berkas</td>
            <td class="meta-val"><?= esc($filename) ?></td>
            <td class="meta-label">ID Laporan / Submisi</td>
            <td class="meta-val">Report #<?= $r_id ?> / Doc #<?= $d_id ?></td>
        </tr>
        <tr>
            <td class="meta-label">Waktu Pemeriksaan</td>
            <td class="meta-val"><?= esc($date_str) ?></td>
            <td class="meta-label">Total Volume</td>
            <td class="meta-val"><?= number_format($total_words) ?> kata</td>
        </tr>
        <tr>
            <td class="meta-label">Arsitektur Mesin</td>
            <td class="meta-val">AR Tilik Dual-Lens Hybrid v1.3</td>
            <td class="meta-label">Cakupan Korpus</td>
            <td class="meta-val"><?= !empty($evidenceData['corpus_docs_count']) ? number_format((int)$evidenceData['corpus_docs_count']) . ' dokumen pembanding' : number_format($exec_time, 2) . ' detik' ?></td>
        </tr>
        <?php if (!empty($document['faculty']) || !empty($document['department'])): ?>
        <tr>
            <td class="meta-label">Fakultas / Prodi</td>
            <td class="meta-val" colspan="3"><?= esc(implode(' / ', array_filter([$document['faculty'] ?? '', $document['department'] ?? '']))) ?></td>
        </tr>
        <?php endif; ?>
    </table>

    <!-- Decision Banner -->
    <div class="decision-banner">
        <div class="decision-header">
            <div>
                <div class="decision-title"><?= esc($dec_label) ?></div>
                <div class="decision-desc"><?= esc($dec_desc) ?></div>
            </div>
            <div class="decision-score"><?= number_format($overall_sim, 2) ?>%</div>
        </div>
        <div>
            <span class="tag-pill">Pola Bukti: <strong><?= esc($pattern) ?></strong></span>
            <span class="tag-pill">Sintesis: <strong><?= esc($agreement) ?></strong></span>
            <span class="tag-pill">Kesepakatan Lensa: <strong><?= $sources_agree ? 'YA (Sepakat)' : 'TIDAK / TUNGGAL' ?></strong></span>
        </div>
    </div>

    <!-- Dual-Lens Quantitative Decomposition -->
    <div class="dual-lens-grid">
        <div class="lens-card">
            <div class="lens-card-header">
                <span class="lens-card-title">Lensa 1 • Exact Matcher</span>
                <span class="lens-card-sub">5-gram Sequential Shingling</span>
            </div>
            <div class="metric-row">
                <span class="metric-lbl">Kesamaan Sekuensial (Exact Sim)</span>
                <span class="metric-val"><?= number_format($exact_sim, 2) ?>%</span>
            </div>
            <div class="metric-row">
                <span class="metric-lbl">Pasase Cocok Terpanjang</span>
                <span class="metric-val"><?= $longest_match ?> kata</span>
            </div>
            <div class="metric-row">
                <span class="metric-lbl">Pola Struktur Sekuensial</span>
                <span class="metric-val"><?= $longest_match >= 50 ? 'Panjang' : ($longest_match >= 10 ? 'Sedang' : 'Mikro/Nol') ?></span>
            </div>
        </div>

        <div class="lens-card">
            <div class="lens-card-header">
                <span class="lens-card-title">Lensa 2 • TF-IDF Vectorizer</span>
                <span class="lens-card-sub">Sparse Cosine Vector Space</span>
            </div>
            <div class="metric-row">
                <span class="metric-lbl">Kemiripan Topikal (Cosine Sim)</span>
                <span class="metric-val"><?= number_format($cosine_sim, 2) ?>%</span>
            </div>
            <div class="metric-row">
                <span class="metric-lbl">Istilah Beririsan (Shared Terms)</span>
                <span class="metric-val"><?= $shared_terms ?> istilah</span>
            </div>
            <div class="metric-row">
                <span class="metric-lbl">Rata-rata Bobot Rujukan (Avg IDF)</span>
                <span class="metric-val"><?= number_format($avg_idf, 4) ?></span>
            </div>
        </div>
    </div>

    <!-- Rationale & Fairness Box -->
    <div class="rationale-box">
        <div class="rationale-title">Catatan Ilmiah & Evaluasi Sistem (Explainability Rationale)</div>
        <div class="rationale-quote">&ldquo;<?= esc($rationale) ?>&rdquo;</div>
        <div class="fairness-note">
            <strong>Prinsip Keadilan Akademik (Academic Fairness Disclaimer):</strong>
            AR Tilik menyajikan bukti tekstual secara matematis dan transparan. Kemiripan leksikal tidak secara otomatis mengindikasikan pelanggaran integritas akademik. Kutipan teks sah, terminologi metodologi terstandarisasi, dan daftar referensi dapat meningkatkan persentase kesamaan. Keputusan substantif mutlak berada di bawah kewenangan Dosen Pembimbing dan Komite Etik Akademik.
        </div>
    </div>

    <!-- Divergence Alert (if exact ~ 0 and cosine > 0) -->
    <?php 
        $has_pdf_div = ($exact_sim <= 0.05 && $cosine_sim > 0.0);
        $direct_pdf_sources = array_filter($sources ?? [], fn($s) => ((float)($s['exact_similarity'] ?? $s['similarity'] ?? 0)) > 0 || ((int)($s['matched_words'] ?? 0)) > 0);
        $lexical_pdf_sources = array_filter($sources ?? [], fn($s) => ((float)($s['cosine_similarity'] ?? 0)) > 0);
    ?>
    <?php if ($has_pdf_div): ?>
        <div style="margin-bottom: 12px; padding: 8px 12px; background-color: #fffbeb; border: 1px solid #fde68a; border-radius: 6px; font-size: 8pt; color: #92400e; page-break-inside: avoid;">
            <strong>Divergensi Bukti Terdeteksi:</strong> Terdapat kemiripan kosakata/topikal (TF-IDF Cosine: <?= number_format($cosine_sim, 2) ?>%), namun <strong>tidak ditemukan pasase sekuensial yang beririsan langsung (Exact: <?= number_format($exact_sim, 2) ?>%)</strong>. Skor kosinus mencerminkan medan makna bersama, bukan penyalinan verbatim.
        </div>
    <?php endif; ?>

    <!-- Table 1: Direct Textual Overlap -->
    <h3 class="section-header">1. Dokumen Rujukan: Direct Textual Overlap (Lensa 1 - Exact Matcher)</h3>
    <table class="data-table" style="margin-bottom: 14px;">
        <thead>
            <tr>
                <th style="width: 35px; text-align: center;">Peringkat</th>
                <th style="text-align: left;">Judul Dokumen Rujukan</th>
                <th style="width: 80px; text-align: right;">Exact Overlap</th>
                <th style="width: 90px; text-align: right;">Kata Cocok</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($direct_pdf_sources)): ?>
                <?php foreach (array_slice($direct_pdf_sources, 0, 5) as $s): ?>
                    <tr>
                        <td style="text-align: center; font-weight: bold; width: 40px;">#<?= $s['source_rank'] ?? 1 ?></td>
                        <td>
                            <strong><?= esc($s['source_title'] ?? $s['title'] ?? ('Rujukan #' . $s['source_document_id'])) ?></strong>
                            <div style="font-size: 7.5pt; color: #64748b;">ID #<?= $s['source_document_id'] ?> • <?= ($s['source_type'] ?? '') === 'corpus' ? 'Korpus Institusi' : 'Naskah Terpublikasi' ?></div>
                        </td>
                        <td style="text-align: right; font-weight: bold; width: 80px; color: #1e3a8a;"><?= number_format($s['exact_similarity'] ?? $s['similarity'] ?? 0, 1) ?>%</td>
                        <td style="text-align: right; width: 90px; color: #475569;"><?= number_format($s['matched_words'] ?? 0) ?> kata</td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="4" style="text-align: center; color: #64748b; font-style: italic; padding: 10px;">
                        Tidak ditemukan dokumen rujukan yang memiliki pasase sekuensial langsung (Exact = 0%).
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- Table 2: Lexical / Topic Similarity -->
    <h3 class="section-header">2. Dokumen Rujukan: Lexical / Topic Similarity (Lensa 2 - TF-IDF Vectorizer)</h3>
    <table class="data-table" style="margin-bottom: 14px;">
        <thead>
            <tr>
                <th style="width: 35px; text-align: center;">Peringkat</th>
                <th style="text-align: left;">Judul Dokumen Rujukan</th>
                <th style="width: 80px; text-align: right;">Cosine Sim</th>
                <th style="width: 90px; text-align: right;">Istilah Beririsan</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($lexical_pdf_sources)): ?>
                <?php foreach (array_slice($lexical_pdf_sources, 0, 5) as $s): ?>
                    <tr>
                        <td style="text-align: center; font-weight: bold; width: 40px;">#<?= $s['source_rank'] ?? 1 ?></td>
                        <td>
                            <strong><?= esc($s['source_title'] ?? $s['title'] ?? ('Rujukan #' . $s['source_document_id'])) ?></strong>
                            <div style="font-size: 7.5pt; color: #64748b;">ID #<?= $s['source_document_id'] ?> • <?= ($s['source_type'] ?? '') === 'corpus' ? 'Korpus Institusi' : 'Naskah Terpublikasi' ?></div>
                        </td>
                        <td style="text-align: right; font-weight: bold; width: 80px; color: #0d9488;"><?= number_format($s['cosine_similarity'] ?? 0, 1) ?>%</td>
                        <td style="text-align: right; width: 90px; color: #475569;"><?= number_format($s['matched_words'] ?? 0) ?> istilah</td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="4" style="text-align: center; color: #64748b; font-style: italic; padding: 10px;">
                        Tidak ada dokumen rujukan yang menunjukkan irisan kemiripan kosinus leksikal.
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- Section 3: Audit Integritas Naskah Akademik & Multibahasa -->
    <?php
        $evAudit = $evidenceData['evasion_detection'] ?? $evidenceData['v1_1_presentation']['v1_2_evolution']['evasion_detection'] ?? [];
        $citAudit = $evidenceData['citation_audit'] ?? $evidenceData['v1_1_presentation']['v1_2_evolution']['citation_audit'] ?? [];
        $clAudit = $evidenceData['cross_lingual'] ?? $evidenceData['v1_1_presentation']['v1_2_evolution']['cross_lingual'] ?? [];

        $evRisk = (float)($evAudit['evasion_risk_score'] ?? 0);
        $evStatus = $evAudit['integrity_status'] ?? 'CLEAN';
        $citHealth = (float)($citAudit['citation_health_score'] ?? 100);
        $citVerdict = $citAudit['health_verdict'] ?? 'GOOD';
        $clScorePdf = (float)($clAudit['cross_lingual_score'] ?? 0);
        $clMatchPdf = !empty($clAudit['is_cross_lingual_match']);
    ?>
    <h3 class="section-header">3. Audit Integritas Naskah, Sitasi &amp; Analisis Lintas Bahasa</h3>
    <table class="data-table" style="margin-bottom: 14px; page-break-inside: avoid;">
        <thead>
            <tr>
                <th style="text-align: left; width: 35%;">Parameter Audit</th>
                <th style="text-align: center; width: 25%;">Skor / Indikator</th>
                <th style="text-align: left; width: 40%;">Status &amp; Keterangan</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><strong>Integritas Karakter &amp; Anti-Evasion</strong><br><span style="font-size: 7.5pt; color: #64748b;">Deteksi Homoglif, Zero-Width, White-Text</span></td>
                <td style="text-align: center; font-weight: bold; color: <?= $evRisk >= 35 ? '#b91c1c' : ($evRisk >= 10 ? '#b45309' : '#047857') ?>; font-size: 10pt;">
                    <?= number_format($evRisk, 1) ?>% Risiko
                </td>
                <td>
                    <strong><?= $evStatus === 'MANIPULATED' ? 'Manipulasi Terencana Terdeteksi' : ($evStatus === 'SUSPICIOUS' ? 'Perlu Tinjauan Karakter' : 'Otentik &amp; Bebas Rekayasa') ?></strong>
                    <div style="font-size: 7.5pt; color: #475569;">Homoglif: <?= (int)($evAudit['homoglyph_count'] ?? 0) ?>, Invisible: <?= (int)($evAudit['zero_width_count'] ?? 0) ?></div>
                </td>
            </tr>
            <tr>
                <td><strong>Konsistensi Sitasi &amp; Daftar Pustaka</strong><br><span style="font-size: 7.5pt; color: #64748b;">Audit Sitasi Gaib (Phantom Citations)</span></td>
                <td style="text-align: center; font-weight: bold; color: <?= $citHealth >= 80 ? '#047857' : ($citHealth >= 50 ? '#b45309' : '#b91c1c') ?>; font-size: 10pt;">
                    <?= number_format($citHealth, 1) ?> / 100
                </td>
                <td>
                    <strong><?= esc($citAudit['verdict_label'] ?? 'Sitasi Selaras') ?></strong>
                    <div style="font-size: 7.5pt; color: #475569;">Sitasi Gaib: <?= (int)($citAudit['phantom_citations_count'] ?? 0) ?>, Pustaka Yatim: <?= (int)($citAudit['orphan_references_count'] ?? 0) ?></div>
                </td>
            </tr>
            <tr>
                <td><strong>Plagiarisme Terjemahan (ID ↔ EN)</strong><br><span style="font-size: 7.5pt; color: #64748b;">Analisis Semantik Proyeksi Konsep Dwibahasa</span></td>
                <td style="text-align: center; font-weight: bold; color: <?= $clScorePdf >= 35 ? '#b91c1c' : ($clScorePdf >= 15 ? '#b45309' : '#047857') ?>; font-size: 10pt;">
                    <?= number_format($clScorePdf, 1) ?>% Keselarasan
                </td>
                <td>
                    <strong><?= $clMatchPdf ? 'Terindikasi Terjemahan Langsung' : ($clScorePdf >= 15 ? 'Parafrasa Lintas Bahasa' : 'Orisinal / Bebas Terjemahan Mesin') ?></strong>
                    <div style="font-size: 7.5pt; color: #475569;">Konsep terpadan: <?= (int)($clAudit['shared_concept_count'] ?? 0) ?> istilah, Pasase: <?= count($clAudit['aligned_passages'] ?? []) ?></div>
                </td>
            </tr>
            <?php
                $aiAuditPdf = $evidenceData['ai_stylometrics'] ?? $evidenceData['v1_1_presentation']['v1_2_evolution']['ai_stylometrics'] ?? [];
                $aiProbPdf = (float)($aiAuditPdf['ai_probability_score'] ?? 0.0);
                $aiLabelPdf = $aiAuditPdf['verdict_label'] ?? 'Gaya Bahasa Alami Manusia (Organik)';
                $aiMetricsPdf = $aiAuditPdf['metrics'] ?? [];
                $aiBurstPdf = (float)($aiMetricsPdf['burstiness'] ?? 0.5);
                $aiConfPdf = $aiAuditPdf['ai_confidence_level'] ?? 'LOW';
                $burstLblPdf = $aiBurstPdf >= 0.4 ? 'Ritme Alami Manusia' : 'Ritme Seragam (Khas AI)';
                $segRatioPdf = (float)($aiMetricsPdf['segmental_monotony_ratio'] ?? 0.0);

                $xgbAuditPdf = $evidenceData['xgboost_risk'] ?? $evidenceData['v1_1_presentation']['v1_2_evolution']['xgboost_risk'] ?? [];
                $xgbScorePdf = (float)($xgbAuditPdf['calibrated_risk_score'] ?? 0.0);
                $xgbLabelPdf = $xgbAuditPdf['verdict_label'] ?? 'Risiko Rendah / Mandiri';
                $xgbLevelPdf = $xgbAuditPdf['risk_level'] ?? 'LOW';

                $geminiAuditPdf = $evidenceData['gemini_forensic'] ?? $evidenceData['v1_1_presentation']['v1_2_evolution']['gemini_forensic'] ?? [];
                $geminiCompleted = !empty($geminiAuditPdf) && ($geminiAuditPdf['status'] ?? '') === 'completed';
                $gAuditData = $geminiAuditPdf['audit'] ?? [];
                if (empty($gAuditData) && !empty($geminiAuditPdf['raw_output'])) {
                    if (preg_match('/"tingkat_indikasi_ai"\s*:\s*"([^"]+)"/i', $geminiAuditPdf['raw_output'], $mLevel)) {
                        $gAuditData['tingkat_indikasi_ai'] = $mLevel[1];
                    }
                    if (preg_match('/"alasan_indikasi_ai"\s*:\s*"([^"]+)"/s', $geminiAuditPdf['raw_output'], $mReason)) {
                        $gAuditData['alasan_indikasi_ai'] = $mReason[1];
                    }
                }
                $gLevelPdf = strtoupper($gAuditData['tingkat_indikasi_ai'] ?? 'RENDAH');
                $gContraPdf = $gAuditData['inkonsistensi_metodologi_dan_logika'] ?? [];
                $gCitPdf = $gAuditData['integritas_sitasi'] ?? [];
                $gQuestionsPdf = $gAuditData['pertanyaan_kritis_dosen'] ?? [];
            ?>
            <?php if (!empty($aiAuditPdf)): ?>
            <tr>
                <td><strong>[Eksperimental] Indikator Gaya Bahasa &amp; Stylometri</strong><br><span style="font-size: 7.5pt; color: #64748b;">Eksperimental — Bukan Vonis AI</span></td>
                <td style="text-align: center; font-weight: bold; color: <?= $aiProbPdf >= 75 ? '#b91c1c' : ($aiProbPdf >= 50 ? '#b45309' : '#047857') ?>; font-size: 10pt;">
                    <?= number_format($aiProbPdf, 1) ?>% Indikator
                </td>
                <td>
                    <strong><?= esc($aiLabelPdf) ?></strong>
                    <div style="font-size: 7.5pt; color: #475569;">
                        Burstiness: <?= number_format($aiBurstPdf, 2) ?> (<?= esc($burstLblPdf) ?>)<?= $segRatioPdf > 0 ? ' • Monotoni Paragraf: ' . number_format($segRatioPdf, 1) . '%' : '' ?> • Keyakinan: <?= esc($aiConfPdf) ?>
                    </div>
                </td>
            </tr>
            <?php endif; ?>
            <?php if (!empty($xgbAuditPdf)): ?>
            <tr>
                <td><strong>Sintesis Risiko Akademik Berbobot</strong><br><span style="font-size: 7.5pt; color: #64748b;">Analisis Multi-Dimensi Determinan Risiko</span></td>
                <td style="text-align: center; font-weight: bold; color: <?= $xgbScorePdf >= 70 ? '#b91c1c' : ($xgbScorePdf >= 35 ? '#b45309' : '#047857') ?>; font-size: 10pt;">
                    <?= number_format($xgbScorePdf, 1) ?>% Indeks Risiko
                </td>
                <td>
                    <strong><?= esc($xgbLabelPdf) ?></strong>
                    <div style="font-size: 7.5pt; color: #475569;">
                        Level: <?= esc($xgbLevelPdf) ?> • Evaluasi komposit berbasis model terkalibrasi
                    </div>
                </td>
            </tr>
            <?php endif; ?>
            <?php if ($geminiCompleted): ?>
            <tr>
                <td><strong>Audit Forensik Cerdas</strong><br><span style="font-size: 7.5pt; color: #64748b;">Konsistensi Semantik Antar-Bab &amp; Integritas Logika</span></td>
                <td style="text-align: center; font-weight: bold; color: <?= $gLevelPdf === 'TINGGI' ? '#b91c1c' : ($gLevelPdf === 'SEDANG' ? '#b45309' : '#047857') ?>; font-size: 10pt;">
                    Indikasi AI: <?= esc($gLevelPdf) ?>
                </td>
                <td>
                    <strong>Verifikasi Semantik Otomatis</strong>
                    <div style="font-size: 7.5pt; color: #475569;">
                        <?= count($gContraPdf) ?> inkonsistensi metodologi, <?= count($gQuestionsPdf) ?> panduan pertanyaan sidang dosen
                    </div>
                </td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- Section 4: Audit Forensik Cerdas & Panduan Ujian Lisan Sidang -->
    <?php if ($geminiCompleted && (!empty($gAuditData['alasan_indikasi_ai']) || !empty($gContraPdf) || !empty($gCitPdf) || !empty($gQuestionsPdf))): ?>
    <div style="margin-top: 14px; margin-bottom: 14px; page-break-inside: avoid;">
        <h3 class="section-header" style="margin-bottom: 8px;">4. Audit Forensik Cerdas &amp; Panduan Ujian Lisan Sidang</h3>
        <?php if (!empty($gAuditData['alasan_indikasi_ai'])): ?>
            <div style="margin-bottom: 8px; padding: 7px 10px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; font-size: 7.5pt; color: #334155; line-height: 1.4;">
                <div style="font-weight: bold; color: <?= $gLevelPdf === 'TINGGI' ? '#b91c1c' : ($gLevelPdf === 'SEDANG' ? '#b45309' : '#047857') ?>; margin-bottom: 2px; font-size: 7pt; text-transform: uppercase;">
                    &bull; Evaluasi Sintetis &amp; Indikasi Perakitan AI (Status: <?= esc($gLevelPdf) ?>):
                </div>
                <div style="font-style: italic;">&ldquo;<?= esc($gAuditData['alasan_indikasi_ai']) ?>&rdquo;</div>
            </div>
        <?php endif; ?>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-bottom: 8px;">
            <div style="background: #ffffff; padding: 7px; border: 1px solid #e2e8f0; border-radius: 6px; font-size: 7.5pt;">
                <div style="font-weight: bold; color: #9a3412; font-size: 7pt; margin-bottom: 4px; text-transform: uppercase;">
                    &bull; Inkonsistensi Metodologi &amp; Logika (<?= count($gContraPdf) ?>)
                </div>
                <ul style="margin: 0; padding-left: 12px; color: #334155; line-height: 1.35;">
                    <?php if (!empty($gContraPdf)): ?>
                        <?php foreach ($gContraPdf as $c): ?>
                            <li style="margin-bottom: 3px;"><?= esc($c) ?></li>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <li style="color: #64748b; font-style: italic;">Tidak ditemukan kontradiksi logika mayor antar-bab.</li>
                    <?php endif; ?>
                </ul>
            </div>
            <div style="background: #ffffff; padding: 7px; border: 1px solid #e2e8f0; border-radius: 6px; font-size: 7.5pt;">
                <div style="font-weight: bold; color: #0369a1; font-size: 7pt; margin-bottom: 4px; text-transform: uppercase;">
                    &bull; Integritas Sitasi &amp; Anomali Rujukan (<?= count($gCitPdf) ?>)
                </div>
                <ul style="margin: 0; padding-left: 12px; color: #334155; line-height: 1.35;">
                    <?php if (!empty($gCitPdf)): ?>
                        <?php foreach ($gCitPdf as $c): ?>
                            <li style="margin-bottom: 3px;"><?= esc($c) ?></li>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <li style="color: #64748b; font-style: italic;">Sitasi rujukan selaras dan tidak ditemukan anomali temporal.</li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>

        <?php if (!empty($gQuestionsPdf)): ?>
            <div style="background: #faf5ff; border: 1px solid #e9d5ff; border-radius: 6px; padding: 7px; margin-bottom: 8px;">
                <div style="font-weight: bold; color: #6b21a8; font-size: 7.5pt; margin-bottom: 5px; display: flex; align-items: center; justify-content: space-between;">
                    <span>Panduan Pertanyaan Kritis Dosen Penguji Sidang</span>
                    <span style="font-size: 6.8pt; font-weight: normal; color: #7e22ce;">Instrumen Uji Penguasaan Orisinalitas</span>
                </div>
                <?php foreach ($gQuestionsPdf as $qIdx => $q): ?>
                    <div style="margin-bottom: 5px; padding: 6px 9px; background: #ffffff; border: 1px solid #e2e8f0; border-left: 3px solid #6366f1; border-radius: 4px; font-size: 7.5pt;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 2px;">
                            <span style="font-weight: bold; color: #312e81; font-size: 7pt;">Pertanyaan #<?= $qIdx + 1 ?> &bull; Fokus: <?= esc($q['fokus'] ?? 'Substansi') ?></span>
                        </div>
                        <div style="font-weight: 600; color: #1e293b; margin-bottom: 2px; line-height: 1.35;">&ldquo;<?= esc($q['pertanyaan'] ?? '') ?>&rdquo;</div>
                        <?php if (!empty($q['alasan'])): ?>
                            <div style="font-size: 6.8pt; color: #64748b; font-style: italic;">Tujuan Pengujian: <?= esc($q['alasan']) ?></div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Matching Passages -->
    <h3 class="section-header">5. Cuplikan Pasase Beririsan (Sampel Kontekstual)</h3>
    <?php if (!empty($matches)): ?>
        <?php foreach (array_slice($matches, 0, 8) as $idx => $m): ?>
            <div class="match-item" style="margin-bottom: 12px; padding: 10px; border: 1px solid #e2e8f0; border-radius: 6px; background-color: #f8fafc; page-break-inside: avoid;">
                <div style="display: flex; justify-content: space-between; font-size: 8.5pt; margin-bottom: 6px; border-bottom: 1px solid #cbd5e1; padding-bottom: 4px;">
                    <span><strong>Pasase #<?= $idx + 1 ?></strong> • Kata <?= $m['submission_start'] ?> &ndash; <?= $m['submission_end'] ?> (<?= $m['word_count'] ?> kata)</span>
                    <span>Rujukan: <strong>Doc #<?= $m['source_document_id'] ?></strong> • Tipe: <strong><?= strtoupper(esc($m['match_type'] ?? 'exact')) ?></strong></span>
                </div>
                <div style="font-size: 8pt; line-height: 1.4; display: grid; grid-template-columns: 1fr 1fr; gap: 8px;">
                    <div style="background: #ffffff; padding: 6px; border: 1px solid #e2e8f0; border-radius: 4px;">
                        <div style="font-weight: bold; color: #1e3a8a; font-size: 7.5pt; margin-bottom: 3px; text-transform: uppercase;">Cuplikan Naskah Submisi</div>
                        <div style="color: #334155; font-style: italic;">&ldquo;<?= esc(mb_substr($m['submission_snippet'] ?? '', 0, 280)) ?><?= mb_strlen($m['submission_snippet'] ?? '') > 280 ? '...' : '' ?>&rdquo;</div>
                    </div>
                    <div style="background: #ffffff; padding: 6px; border: 1px solid #e2e8f0; border-radius: 4px;">
                        <div style="font-weight: bold; color: #475569; font-size: 7.5pt; margin-bottom: 3px; text-transform: uppercase;">Cuplikan Dokumen Rujukan</div>
                        <div style="color: #334155; font-style: italic;">&ldquo;<?= esc(mb_substr($m['source_snippet'] ?? '', 0, 280)) ?><?= mb_strlen($m['source_snippet'] ?? '') > 280 ? '...' : '' ?>&rdquo;</div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div style="text-align: center; color: #64748b; font-style: italic; padding: 16px; border: 1px dashed #cbd5e1; border-radius: 6px;">
            Tidak ditemukan pasase kata berurutan yang melebihi batas deteksi sekuensial.
        </div>
    <?php endif; ?>

    <!-- Signatures Section -->
    <div class="signature-grid">
        <div class="sig-box">
            <div class="sig-role">Penulis / Mahasiswa</div>
            <div class="sig-line">NIM / Tanda Tangan</div>
        </div>
        <div class="sig-box">
            <div class="sig-role">Dosen Pembimbing I</div>
            <div class="sig-line">NIDN / Tanggal</div>
        </div>
        <div class="sig-box">
            <div class="sig-role">Dosen Pembimbing II / Validator</div>
            <div class="sig-line">NIDN / Tanggal</div>
        </div>
    </div>

</body>
</html>
