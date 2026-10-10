#!/usr/bin/env python3
"""
Generator PDF Brosur & Prospektus Eksekutif Institusi - AR Tilik
Membuat media promosi resmi, elegan, bernilai konversi tinggi, dan siap unduh
untuk Pimpinan Perguruan Tinggi, Ketua LPPM, Dekan, Pengelola Jurnal OJS, dan Sysadmin IT Kampus.
"""

import os
import sys
import base64
import shutil
import tempfile
import subprocess
from pathlib import Path

# Setup paths
PROJECT_ROOT = Path(__file__).resolve().parents[1]
PUBLIC_DIR = PROJECT_ROOT / "public"
OUTPUT_DIR = PUBLIC_DIR / "docs"
OUTPUT_PDF_PATH = OUTPUT_DIR / "Brosur_Eksekutif_ARTilik.pdf"

# Pastikan direktori tujuan tersedia
OUTPUT_DIR.mkdir(parents=True, exist_ok=True)

def load_image_base64(file_path: Path) -> str:
    """Membaca berkas gambar dan mengubahnya menjadi base64 string."""
    if not file_path.exists():
        return ""
    ext = file_path.suffix.lower().replace(".", "")
    mime = "image/png" if ext == "png" else "image/jpeg"
    with open(file_path, "rb") as f:
        data = base64.b64encode(f.read()).decode("utf-8")
    return f"data:{mime};base64,{data}"

def generate_qr_base64(text: str) -> str:
    """Menghasilkan QR code dalam bentuk PNG base64."""
    try:
        import qrcode
        import io
        qr = qrcode.QRCode(
            version=1,
            error_correction=qrcode.constants.ERROR_CORRECT_M,
            box_size=5,
            border=1
        )
        qr.add_data(text)
        qr.make(fit=True)
        img = qr.make_image(fill_color="#0f294a", back_color="white")
        buf = io.BytesIO()
        img.save(buf, format="PNG")
        b64 = base64.b64encode(buf.getvalue()).decode("utf-8")
        return f"data:image/png;base64,{b64}"
    except Exception as e:
        print(f"[WARNING] Gagal menghasilkan QR code: {e}")
        return ""

def build_html() -> str:
    """Menyusun seluruh struktur HTML dokumen PDF multi-halaman (4 Halaman Eksklusif)."""
    logo_b64 = load_image_base64(PUBLIC_DIR / "images" / "logo.png")
    hero_b64 = load_image_base64(PUBLIC_DIR / "images" / "hero_illustration.jpg")
    dual_lens_concept_b64 = load_image_base64(PUBLIC_DIR / "images" / "dual_lens_concept.jpg")
    dual_reader_mockup_b64 = load_image_base64(PUBLIC_DIR / "images" / "dual_reader_mockup.jpg")
    qr_portal_b64 = generate_qr_base64("https://tilik.arsetontong.top/#solusi")
    qr_demo_b64 = generate_qr_base64("https://tilik.arsetontong.top/verify/ART-OJS3-8821-DEMO")
    qr_wa_b64 = generate_qr_base64("https://wa.me/6282353201673?text=Halo%20Pak%20Makarius,%20saya%20ingin%20mengajukan%20Trial%20Gratis%2014%20Hari%20AR%20Tilik")

    template = """<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Prospektus Eksekutif & Media Promosi Institusi - AR Tilik</title>
<style>
/* ====================================================== */
/* PRINT PAGE SETUP (A4 PORTRAIT)                        */
/* ====================================================== */
@page {
    size: A4 portrait;
    margin: 14mm 13mm 14mm 13mm;
    @top-left {
        content: "AR Tilik | Prospektus Eksekutif Institusi";
        font-family: 'Liberation Sans', 'DejaVu Sans', Arial, sans-serif;
        font-size: 7.2pt;
        font-weight: bold;
        color: #4338ca;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    @top-right {
        content: "Academic Writing Similarity & Evidence Checker";
        font-family: 'Liberation Sans', 'DejaVu Sans', Arial, sans-serif;
        font-size: 7.2pt;
        color: #64748b;
    }
    @bottom-left {
        content: "https://tilik.arsetontong.top • Prospektus Resmi Institusi v1.3.0 Production";
        font-family: 'Liberation Sans', 'DejaVu Sans', Arial, sans-serif;
        font-size: 7.2pt;
        color: #94a3b8;
    }
    @bottom-right {
        content: "Halaman " counter(page) " dari " counter(pages);
        font-family: 'Liberation Sans', 'DejaVu Sans', Arial, sans-serif;
        font-size: 7.2pt;
        font-weight: 700;
        color: #0f172a;
    }
}

@page:first {
    margin: 0;
    @top-left { content: none; }
    @top-right { content: none; }
    @bottom-left { content: none; }
    @bottom-right { content: none; }
}

/* ====================================================== */
/* GLOBAL RESET & TYPOGRAPHY                              */
/* ====================================================== */
* {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
}

body {
    font-family: 'Liberation Sans', 'DejaVu Sans', Arial, sans-serif;
    color: #1e293b;
    font-size: 8.4pt;
    line-height: 1.4;
    background: #ffffff;
}

.page-break {
    page-break-after: always;
}

.avoid-break {
    break-inside: avoid;
    page-break-inside: avoid;
}

/* ====================================================== */
/* COVER PAGE STYLES (HALAMAN 1)                          */
/* ====================================================== */
.cover-container {
    width: 210mm;
    height: 297mm;
    background: linear-gradient(145deg, #060d1f 0%, #0d1e3a 45%, #132b50 100%);
    color: #ffffff;
    padding: 18mm 18mm 16mm 18mm;
    position: relative;
    overflow: hidden;
}

.cover-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 7mm;
    border-bottom: 1px solid rgba(255, 255, 255, 0.12);
    padding-bottom: 4mm;
}

.cover-brand {
    display: flex;
    align-items: center;
    gap: 12px;
}

.cover-logo {
    width: 44px;
    height: 44px;
    object-fit: contain;
    background: #ffffff;
    border-radius: 10px;
    padding: 3px;
    box-shadow: 0 4px 14px rgba(0,0,0,0.3);
}

.cover-title-group h1 {
    font-size: 19pt;
    font-weight: 800;
    letter-spacing: -0.3px;
    color: #ffffff;
    line-height: 1.15;
}

.cover-title-group p {
    font-size: 8pt;
    font-weight: 600;
    color: #38bdf8;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.cover-badge {
    background: rgba(99, 102, 241, 0.22);
    border: 1px solid rgba(129, 140, 248, 0.5);
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 7.2pt;
    font-weight: 700;
    color: #c7d2fe;
    text-transform: uppercase;
    letter-spacing: 0.6px;
}

.cover-kicker {
    font-size: 8.5pt;
    font-weight: 800;
    color: #f43f5e;
    text-transform: uppercase;
    letter-spacing: 1.2px;
    margin-bottom: 2mm;
}

.cover-hero-title {
    font-size: 20.5pt;
    font-weight: 800;
    line-height: 1.2;
    color: #ffffff;
    margin-bottom: 3mm;
}

.cover-hero-title span {
    color: #38bdf8;
}

.cover-hero-subtitle {
    font-size: 9.3pt;
    color: #cbd5e1;
    line-height: 1.45;
    margin-bottom: 5.5mm;
    max-width: 95%;
}

.cover-hero-image-box {
    width: 100%;
    height: 48mm;
    border-radius: 9px;
    overflow: hidden;
    margin-bottom: 5.5mm;
    border: 1px solid rgba(255, 255, 255, 0.18);
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.45);
    background: #020617;
}

.cover-hero-image-box img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}

.cover-stats-row {
    display: flex;
    justify-content: space-between;
    gap: 8px;
    margin-bottom: 5.5mm;
}

.cover-stat-card {
    flex: 1;
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(255, 255, 255, 0.12);
    border-radius: 8px;
    padding: 7px 9px;
    text-align: center;
}

.cover-stat-val {
    font-size: 13pt;
    font-weight: 800;
    color: #38bdf8;
    line-height: 1.1;
}

.cover-stat-val.gold { color: #facc15; }
.cover-stat-val.rose { color: #fb7185; }
.cover-stat-val.emerald { color: #34d399; }

.cover-stat-lbl {
    font-size: 6.8pt;
    color: #94a3b8;
    font-weight: 600;
    margin-top: 2px;
}

.cover-pillars-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 8px;
    margin-bottom: 5.5mm;
}

.cover-pillar-card {
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 7px;
    padding: 7px 10px;
}

.cover-pillar-title {
    font-size: 8.3pt;
    font-weight: 700;
    color: #ffffff;
    margin-bottom: 2px;
    display: flex;
    align-items: center;
    gap: 5px;
}

.cover-pillar-desc {
    font-size: 7.2pt;
    color: #94a3b8;
    line-height: 1.35;
}

.cover-footer-meta {
    border-top: 1px solid rgba(255, 255, 255, 0.12);
    padding-top: 3.5mm;
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 7.2pt;
    color: #64748b;
}

.cover-footer-meta strong {
    color: #cbd5e1;
}

/* ====================================================== */
/* INSIDE PAGES SHARED STYLES                             */
/* ====================================================== */
.section-tag {
    display: inline-block;
    font-size: 6.8pt;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.8px;
    color: #4338ca;
    background: #eef2ff;
    border: 1px solid #c7d2fe;
    padding: 2.5px 8px;
    border-radius: 4px;
    margin-bottom: 3.5px;
}

.section-tag.rose {
    color: #be123c;
    background: #ffe4e6;
    border-color: #fecdd3;
}

.section-tag.emerald {
    color: #047857;
    background: #d1fae5;
    border-color: #a7f3d0;
}

.section-title {
    font-size: 13.5pt;
    font-weight: 800;
    color: #0f172a;
    letter-spacing: -0.2px;
    line-height: 1.25;
    margin-bottom: 3.5px;
}

.section-desc {
    font-size: 8.2pt;
    color: #475569;
    line-height: 1.45;
    margin-bottom: 4mm;
}

/* Card Grids */
.grid-2 {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 8px;
    margin-bottom: 4mm;
}

.grid-3 {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr;
    gap: 7px;
    margin-bottom: 4mm;
}

.info-card {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    padding: 8px 10px;
}

.info-card.highlight {
    background: #f0fdf4;
    border-color: #bbf7d0;
}

.info-card.alert {
    background: #fff1f2;
    border-color: #fecdd3;
}

.info-card.primary {
    background: #f5f3ff;
    border-color: #ddd6fe;
}

.card-title {
    font-size: 8.5pt;
    font-weight: 700;
    color: #0f172a;
    margin-bottom: 3px;
    display: flex;
    align-items: center;
    gap: 5px;
}

.card-text {
    font-size: 7.6pt;
    color: #475569;
    line-height: 1.4;
}

/* Callout Box */
.callout-box {
    background: #eff6ff;
    border-left: 3.5px solid #2563eb;
    border-radius: 0 6px 6px 0;
    padding: 7px 10px;
    margin-bottom: 4mm;
    font-size: 7.8pt;
    color: #1e3a8a;
    line-height: 1.45;
}

.callout-box strong {
    color: #1d4ed8;
}

/* Comparison Table */
.comp-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 4mm;
    font-size: 7.2pt;
    border: 1px solid #cbd5e1;
}

.comp-table th {
    background: #0f172a;
    color: #ffffff;
    padding: 5px 6px;
    text-align: left;
    font-weight: 700;
    font-size: 7.1pt;
}

.comp-table td {
    padding: 4px 6px;
    border-bottom: 1px solid #e2e8f0;
    line-height: 1.35;
    vertical-align: top;
}

.comp-table tr:nth-child(even) td {
    background: #f8fafc;
}

.badge-win {
    font-weight: 700;
    color: #047857;
    background: #d1fae5;
    padding: 1.5px 5px;
    border-radius: 3px;
    display: inline-block;
}

.badge-lose {
    color: #b91c1c;
    background: #fee2e2;
    padding: 1.5px 5px;
    border-radius: 3px;
    display: inline-block;
}

.badge-neutral {
    color: #475569;
    background: #e2e8f0;
    padding: 1.5px 5px;
    border-radius: 3px;
    display: inline-block;
}

/* Package Cards */
.package-card {
    border: 1.5px solid #e2e8f0;
    border-radius: 7px;
    padding: 9px;
    background: #ffffff;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
}

.package-card.featured {
    border-color: #6366f1;
    background: linear-gradient(180deg, #faf5ff 0%, #ffffff 100%);
    box-shadow: 0 4px 12px rgba(99, 102, 241, 0.12);
}

.package-header {
    border-bottom: 1px solid #e2e8f0;
    padding-bottom: 6px;
    margin-bottom: 6px;
}

.package-tag {
    font-size: 6.5pt;
    font-weight: 800;
    text-transform: uppercase;
    color: #4338ca;
    margin-bottom: 2px;
}

.package-name {
    font-size: 9.5pt;
    font-weight: 800;
    color: #0f172a;
}

.package-list {
    list-style: none;
    font-size: 7.2pt;
    color: #475569;
    margin-bottom: 8px;
}

.package-list li {
    margin-bottom: 3.5px;
    display: flex;
    align-items: flex-start;
    gap: 5px;
    line-height: 1.35;
}

.package-list li span.check {
    color: #10b981;
    font-weight: bold;
    flex-shrink: 0;
}

.package-list li .li-txt {
    flex: 1;
}

/* Trial 14 Hari Box */
.trial-box {
    background: linear-gradient(135deg, #fff1f2 0%, #fdf2f8 100%);
    border: 1.5px solid #fbcfe8;
    border-radius: 8px;
    padding: 8px 12px;
    margin-top: 3.5mm;
    margin-bottom: 3.5mm;
}

.trial-badge-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 3px;
}

.trial-pill-highlight {
    font-size: 6.8pt;
    font-weight: 800;
    color: #be123c;
    background: #ffe4e6;
    border: 1px solid #fecdd3;
    padding: 2px 8px;
    border-radius: 12px;
}

.trial-title {
    font-size: 11pt;
    font-weight: 800;
    color: #9d174d;
    margin-bottom: 3px;
}

.trial-desc {
    font-size: 7.7pt;
    color: #334155;
    line-height: 1.42;
    margin-bottom: 5px;
}

.trial-features-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 3.5px 10px;
    font-size: 7.1pt;
    color: #1e293b;
    background: rgba(255, 255, 255, 0.75);
    border: 1px solid rgba(244, 63, 94, 0.2);
    border-radius: 5px;
    padding: 5px 8px;
}

.trial-feat-item {
    display: flex;
    align-items: center;
    gap: 5px;
}

.trial-feat-item .feat-check {
    color: #e11d48;
    font-weight: bold;
    flex-shrink: 0;
}

/* CTA Box */
.cta-box {
    background-color: #0b1528;
    background: #0b1528;
    border: 1.5px solid #2563eb;
    border-radius: 8px;
    padding: 10px 14px;
    color: #ffffff;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
}

.cta-content {
    flex: 1;
}

.cta-kicker {
    font-size: 6.8pt;
    font-weight: 800;
    color: #38bdf8;
    text-transform: uppercase;
    letter-spacing: 0.8px;
    margin-bottom: 2px;
}

.cta-title {
    font-size: 11.5pt;
    font-weight: 800;
    color: #ffffff;
    margin-bottom: 6px;
}

.contact-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 5px 12px;
}

.contact-item {
    display: flex;
    align-items: flex-start;
    gap: 6px;
    font-size: 7.3pt;
    color: #cbd5e1;
}

.contact-icon {
    font-size: 9.5pt;
    line-height: 1.1;
    flex-shrink: 0;
}

.contact-label {
    color: #94a3b8;
    font-weight: 600;
    margin-right: 2px;
}

.contact-val {
    color: #ffffff;
    font-weight: 700;
}

.cta-qr-box {
    background: #ffffff;
    border-radius: 6px;
    padding: 5px;
    text-align: center;
    flex-shrink: 0;
    box-shadow: 0 4px 12px rgba(0,0,0,0.3);
}

.cta-qr-box img {
    width: 60px;
    height: 60px;
    display: block;
}

.cta-qr-box span {
    font-size: 5.8pt;
    font-weight: 700;
    color: #0f172a;
    display: block;
    margin-top: 2px;
}
</style>
</head>
<body>

<!-- ==================================================== -->
<!-- HALAMAN 1: COVER EKSEKUTIF & VALUE PROPOSITION      -->
<!-- ==================================================== -->
<div class="cover-container page-break">
    <div class="cover-header">
        <div class="cover-brand">
            <img src="__LOGO_B64__" alt="AR Tilik Logo" class="cover-logo">
            <div class="cover-title-group">
                <h1>AR Tilik</h1>
                <p>Academic Writing Similarity &amp; Evidence Checker</p>
            </div>
        </div>
        <div class="cover-badge">
            Prospektus Resmi Institusi • v1.3.0 Production
        </div>
    </div>

    <div class="cover-kicker">PROSPEKTUS KEMITRAAN UNIVERSITAS, LPPM, FAKULTAS &amp; JURNAL ILMIAH</div>
    <h2 class="cover-hero-title">
        Kedaulatan Integritas Akademik &amp;<br>
        <span>Efisiensi Anggaran Kampus Hingga 70%</span>
    </h2>
    <p class="cover-hero-subtitle">
        Platform pemeriksaan kesepadanan naskah ilmiah berbasis Dual-Lens, audit forensik semantik bertenaga Google Gemini AI, dan verifikasi sertifikat digital berstempel SHA-256 yang menjaga 100% kerahasiaan hak cipta karya ilmiah sivitas akademika Indonesia.
    </p>

    <div class="cover-hero-image-box">
        <img src="__HERO_B64__" alt="AR Tilik Platform Hero Illustration">
    </div>

    <!-- Quick Stats -->
    <div class="cover-stats-row">
        <div class="cover-stat-card">
            <div class="cover-stat-val gold">70%+</div>
            <div class="cover-stat-lbl">Efisiensi Kas Kampus (Bebas Valas USD)</div>
        </div>
        <div class="cover-stat-card">
            <div class="cover-stat-val emerald">100%</div>
            <div class="cover-stat-lbl">Kedaulatan Data &amp; Anti-Kebocoran Naskah</div>
        </div>
        <div class="cover-stat-card">
            <div class="cover-stat-val">&lt; 15 dtk</div>
            <div class="cover-stat-lbl">Komputasi Dual-Lens Kilat &amp; Akurat</div>
        </div>
        <div class="cover-stat-card">
            <div class="cover-stat-val rose">SINTA Ready</div>
            <div class="cover-stat-lbl">Bukti Dukung Akreditasi ARJUNA &amp; BAN-PT</div>
        </div>
    </div>

    <!-- 4 Pilar Strategis -->
    <div class="cover-pillars-grid">
        <div class="cover-pillar-card">
            <div class="cover-pillar-title">🛡️ 100% Kedaulatan &amp; Kerahasiaan Data</div>
            <div class="cover-pillar-desc">Naskah submisi tidak diumpankan ke repositori komersial global. Opsi <em>On-Premise / Private Cloud</em> menjamin data riset sivitas tetap di pusat data kampus.</div>
        </div>
        <div class="cover-pillar-card">
            <div class="cover-pillar-title">💰 Lisensi Rupiah Transparan Tanpa Monopoli</div>
            <div class="cover-pillar-desc">Hapus beban tagihan valas asing (USD) dan sistem lisensi per-kepala mahasiswa. Kampus membayar sesuai kuota naskah riil dengan biaya terukur.</div>
        </div>
        <div class="cover-pillar-card">
            <div class="cover-pillar-title">🔍 Mesin Analisis Dual-Lens &amp; Isolasi Substantif</div>
            <div class="cover-pillar-desc">Memisahkan salin-tempel literal (Lensa 1) dengan parafrase semantik (Lensa 2). Bagian metodologi baku dan daftar pustaka diisolasi agar sitasi wajar tidak dipenalti.</div>
        </div>
        <div class="cover-pillar-card">
            <div class="cover-pillar-title">🤖 Google Gemini AI Forensic Audit &amp; Tanya Uji</div>
            <div class="cover-pillar-desc">Penalaran semantik cerdas membongkar manipulasi teks &amp; rujukan fiktif, serta otomatis menghasilkan 3 set pertanyaan kritis untuk Dewan Penguji sidang &amp; Reviewer.</div>
        </div>
    </div>

    <div class="cover-footer-meta">
        <div>Sasaran: <strong>Rektorat, LPPM, Dekanat, Dewan Pengelola Jurnal, dan Perpustakaan Perguruan Tinggi</strong></div>
        <div>Portal Resmi: <strong>https://tilik.arsetontong.top</strong></div>
    </div>
</div>

<!-- ==================================================== -->
<!-- HALAMAN 2: ARSITEKTUR TEKNOLOGI & FORENSIK AI       -->
<!-- ==================================================== -->
<div class="page-break" style="padding-top: 2mm;">
    <div class="section-tag rose">Tantangan Ekosistem Akademik</div>
    <h2 class="section-title">Mengapa Mesin Konvensional Merugikan Kampus?</h2>
    <p class="section-desc">
        Selama lebih dari dua dekade, perguruan tinggi di Indonesia terjebak dalam dilema platform asing konvensional: biaya lisensi berbasis valas (USD) yang melambung tinggi setiap tahun, naskah submisi lokal yang terserap ke repositori vendor asing tanpa jaminan kerahasiaan, serta vonis keliru akibat penilaian berbasis skor persentase tunggal yang dangkal.
    </p>

    <div class="grid-3">
        <div class="info-card alert">
            <div class="card-title">⚠️ Jebakan Skor Tunggal</div>
            <div class="card-text">
                Alat pendeteksi lama mencampuradukkan sitasi sah, kutipan doa, nama institusi, dan kalimat metodologi umum menjadi satu angka kumulatif, menimbulkan salah vonis yang merugikan mahasiswa dan peneliti jujur.
            </div>
        </div>
        <div class="info-card alert">
            <div class="card-title">💵 Beban Lisensi Valas (USD)</div>
            <div class="card-text">
                Platform komersial asing memberlakukan sistem lisensi per-kepala mahasiswa berbayar USD. Kampus terpaksa mengalokasikan ratusan juta hingga miliaran rupiah setiap tahun hanya untuk langganan alat bantu.
            </div>
        </div>
        <div class="info-card alert">
            <div class="card-title">🔓 Kebocoran Hak Cipta Naskah</div>
            <div class="card-text">
                Naskah pra-publikasi yang diunggah otomatis tersimpan di repositori terbuka penyedia asing. Gagasan riset dan draf naskah yang belum resmi terbit berisiko terpublikasi secara prematur.
            </div>
        </div>
    </div>

    <div class="section-tag">Arsitektur Komputasi Unggulan</div>
    <h2 class="section-title">Solusi AR Tilik: Dekomposisi Dual-Lens &amp; Isolasi Substantif</h2>
    <p class="section-desc">
        AR Tilik membedah setiap naskah secara objektif melalui dua sudut pandang matematis yang independen, memastikan keadilan akademik tanpa vonis serampangan:
    </p>

    <div class="grid-2">
        <div class="info-card primary">
            <div class="card-title">🔬 Lensa 1: Exact 5-Gram Shingling (Literal)</div>
            <div class="card-text">
                Memecah teks menjadi klaster 5-kata sekuensial dan mencocokkan hash blok teks identik. Berfungsi mendeteksi kutipan langsung tanpa tanda petik, salin-tempel kata-demi-kata (*verbatim copying*), dan mengidentifikasi pasase duplikasi terpanjang.
            </div>
        </div>
        <div class="info-card primary">
            <div class="card-title">🧠 Lensa 2: TF-IDF Cosine Vector (Semantik)</div>
            <div class="card-text">
                Membangun ruang vektor multi-dimensi berdasarkan frekuensi dan bobot kelangkaan istilah ilmiah (IDF). Berfungsi mendeteksi parafrase terstruktur, manipulasi sinonim kata, dan kesepadanan topik gagasan mendalam.
            </div>
        </div>
    </div>

    <!-- Visual Concept -->
    <div style="width: 100%; border: 1px solid #cbd5e1; border-radius: 6px; overflow: hidden; margin-bottom: 4mm;">
        <img src="__DUAL_LENS_CONCEPT_B64__" alt="Konsep Dual-Lens AR Tilik" style="width: 100%; height: 38mm; object-fit: cover; display: block;">
    </div>

    <div class="section-tag emerald">Kecerdasan Artifisial Generatif</div>
    <h2 class="section-title">Google Gemini AI Forensic Audit &amp; Panduan Tanya Uji Kritis</h2>
    <p class="section-desc">
        AR Tilik melangkah jauh melampaui pencocokan teks biasa dengan menghadirkan penalaran forensik semantik bertenaga AI untuk membantu dewan editor jurnal dan penguji sidang:
    </p>

    <div class="grid-2">
        <div class="info-card highlight">
            <div class="card-title">🛡️ Audit Forensik Semantik AI</div>
            <div class="card-text">
                Mendeteksi anomali gaya selingkung (*stylometry*), indikasi fabrikasi data, serta kutipan sitasi fiktif/halusinatif yang sering lolos dari pengawasan manusia. Memberikan ringkasan audit komprehensif berstatus netral.
            </div>
        </div>
        <div class="info-card highlight">
            <div class="card-title">🎯 3 Set Pertanyaan Tanya Uji (Oral Defense)</div>
            <div class="card-text">
                Otomatis memproduksi daftar pertanyaan kritis berbasis temuan pasase naskah yang spesifik. Sangat berharga bagi Dosen Penguji Skripsi/Tesis dan Mitra Bestari Jurnal untuk menguji penguasaan substansi penulis secara elegan.
            </div>
        </div>
    </div>
</div>

<!-- ==================================================== -->
<!-- HALAMAN 3: KOMPARASI HEAD-TO-HEAD & INTEGRASI       -->
<!-- ==================================================== -->
<div class="page-break" style="padding-top: 2mm;">
    <div class="section-tag">Analisis Komparatif</div>
    <h2 class="section-title">Matriks Komparasi Head-to-Head: AR Tilik vs Platform Lain</h2>
    <p class="section-desc">
        Perbandingan transparan membuktikan keunggulan teknologi, kepatuhan etika, dan efisiensi biaya AR Tilik dibandingkan alternatif yang ada di pasar:
    </p>

    <table class="comp-table">
        <thead>
            <tr>
                <th style="width: 25%;">Parameter Evaluasi</th>
                <th style="width: 28%;">AR Tilik (Enterprise &amp; OJS)</th>
                <th style="width: 27%;">Vendor Asing (Turnitin / iThenticate)</th>
                <th style="width: 20%;">Detektor Daring Gratisan</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><strong>Kedaulatan &amp; Privasi Data</strong></td>
                <td><span class="badge-win">100% Privat / Opsi On-Premise</span><br>Naskah draft tidak dibocorkan ke luar.</td>
                <td><span class="badge-lose">Tersimpan di Cloud Asing</span><br>Naskah tersimpan di basis data vendor.</td>
                <td><span class="badge-lose">Risiko Naskah Dijual</span><br>Hak cipta rentan disalahgunakan.</td>
            </tr>
            <tr>
                <td><strong>Skema Biaya &amp; Model Lisensi</strong></td>
                <td><span class="badge-win">Rupiah Transparan, Hemat 70%</span><br>Kuota fleksibel, tanpa biaya per-kepala mhs.</td>
                <td><span class="badge-lose">Valas (USD) Sangat Mahal</span><br>Kontrak multi-tahunan, tarif per-mahasiswa.</td>
                <td><span class="badge-neutral">Gratis Terbatas / Iklan</span><br>Fitur dipangkas, limit kata sangat kecil.</td>
            </tr>
            <tr>
                <td><strong>Dekomposisi Bukti Kemiripan</strong></td>
                <td><span class="badge-win">Dual-Lens (5-Gram &amp; TF-IDF Cosine)</span><br>Memisahkan salin-tempel vs parafrase.</td>
                <td><span class="badge-neutral">Hanya Skor Tunggal Kumulatif</span><br>Tidak membedakan jenis kemiripan.</td>
                <td><span class="badge-lose">Pencocokan String Dasar</span><br>Sangat mudah dikelabui manipulasi kata.</td>
            </tr>
            <tr>
                <td><strong>Isolasi Substantif (Section-Aware)</strong></td>
                <td><span class="badge-win">Ya, Pemisahan Bab Otomatis</span><br>Metodologi &amp; referensi diisolasi adil.</td>
                <td><span class="badge-neutral">Hanya Filter Teks Standar</span><br>Memerlukan penyesuaian manual lambat.</td>
                <td><span class="badge-lose">Tidak Ada</span><br>Seluruh teks diperlakukan seragam.</td>
            </tr>
            <tr>
                <td><strong>Forensik Cerdas Google Gemini AI</strong></td>
                <td><span class="badge-win">Ya, Forensik Semantik + Tanya Uji</span><br>Analisis gaya teks &amp; panduan penguji.</td>
                <td><span class="badge-lose">Tidak Tersedia</span><br>Hanya fitur detektor AI generik terpisah.</td>
                <td><span class="badge-lose">Tidak Ada</span></td>
            </tr>
            <tr>
                <td><strong>Sertifikat Digital Anti-Pemalsuan</strong></td>
                <td><span class="badge-win">Ya, QR Code Publik + SHA-256</span><br>Diverifikasi instan oleh Asesor SINTA.</td>
                <td><span class="badge-neutral">Hanya Lembar PDF Standar</span><br>Rentan dipalsukan / dimanipulasi teksnya.</td>
                <td><span class="badge-lose">Tidak Ada Sertifikat</span></td>
            </tr>
            <tr>
                <td><strong>Konektivitas OJS 3.x &amp; Repositori</strong></td>
                <td><span class="badge-win">Native REST API, PHP, &amp; OAI-PMH</span><br>Plug-and-play ke OJS 3, EPrints, &amp; DSpace.</td>
                <td><span class="badge-neutral">Integrasi API Kompleks</span><br>Memerlukan biaya lisensi API tambahan.</td>
                <td><span class="badge-lose">Manual Satu-Satu</span><br>Tidak mendukung otomasi redaksi.</td>
            </tr>
            <tr>
                <td><strong>Cakupan Repositori Lokal Indonesia</strong></td>
                <td><span class="badge-win">Terhubung Garuda, Neliti, EPrints</span><br>Kaya korpus rujukan ilmiah nasional.</td>
                <td><span class="badge-neutral">Terbatas Jurnal Komersial</span><br>Repositori kampus lokal minim terindeks.</td>
                <td><span class="badge-lose">Hanya Hasil Web Publik</span></td>
            </tr>
        </tbody>
    </table>

    <div class="section-tag emerald">Antarmuka Interaktif &amp; Bukti Keabsahan</div>
    <h2 class="section-title">Dual-Reader Interaktif &amp; Sertifikat Digital Berkode QR</h2>
    <p class="section-desc">
        Memudahkan sivitas memeriksa bukti secara berdampingan dan membuktikan keabsahan naskah secara transparan kepada pihak ketiga:
    </p>

    <!-- Visual Mockup -->
    <div style="width: 100%; border: 1px solid #cbd5e1; border-radius: 6px; overflow: hidden; margin-bottom: 3.5mm;">
        <img src="__DUAL_READER_MOCKUP_B64__" alt="Antarmuka Dual-Reader AR Tilik" style="width: 100%; height: 36mm; object-fit: cover; display: block;">
    </div>

    <div class="callout-box">
        <strong>Sertifikat Keabsahan Digital Resmi:</strong> Setiap dokumen yang diperiksa memperoleh lembar sertifikasi digital berstempel kriptografis SHA-256 dan kode QR publik. Asesor SINTA, ARJUNA, BAN-PT, maupun panitia yudisium dapat memindai kode QR untuk memvalidasi keaslian laporan secara instan tanpa perlu akun masuk.
    </div>
</div>

<!-- ==================================================== -->
<!-- HALAMAN 4: PAKET LAYANAN INSTITUSI & CALL TO ACTION -->
<!-- ==================================================== -->
<div class="avoid-break" style="padding-top: 2mm;">
    <div class="section-tag emerald">Model Lisensi &amp; Kemitraan</div>
    <h2 class="section-title">Pilihan Paket Kemitraan Kampus yang Fleksibel</h2>
    <p class="section-desc">
        Disesuaikan dengan skala kebutuhan institusi, mulai dari pengelolaan jurnal ilmiah tingkat prodi hingga integrasi menyeluruh di tingkat rektorat universitas:
    </p>

    <div class="grid-3">
        <!-- Paket 1: OJS Jurnal -->
        <div class="package-card">
            <div class="package-header">
                <div class="package-tag">Jurnal Ilmiah OJS</div>
                <div class="package-name">Paket Redaksi Jurnal</div>
            </div>
            <ul class="package-list">
                <li><span class="check">✓</span> <span class="li-txt">Kuota fleksibel untuk 1–10 Jurnal OJS</span></li>
                <li><span class="check">✓</span> <span class="li-txt">Pustaka Konektor PHP &amp; Skrip Runner CLI</span></li>
                <li><span class="check">✓</span> <span class="li-txt">REST API Key Jurnal dengan limit terlindungi</span></li>
                <li><span class="check">✓</span> <span class="li-txt">Sertifikat Digital Ber-QR Code per artikel</span></li>
                <li><span class="check">✓</span> <span class="li-txt">Standar Bukti Akreditasi ARJUNA / SINTA</span></li>
            </ul>
            <div style="font-size: 7.2pt; color: #64748b; font-weight: 600; text-align: center; border-top: 1px dashed #e2e8f0; padding-top: 5px;">
                Mulai Rp 450.000 / Bulan
            </div>
        </div>

        <!-- Paket 2: Fakultas & Pascasarjana (Featured) -->
        <div class="package-card featured">
            <div class="package-header">
                <div class="package-tag" style="color: #4f46e5;">Fakultas &amp; Pascasarjana</div>
                <div class="package-name">Paket Sivitas Fakultas</div>
            </div>
            <ul class="package-list">
                <li><span class="check">✓</span> <span class="li-txt">Seluruh fitur Paket Redaksi Jurnal</span></li>
                <li><span class="check">✓</span> <span class="li-txt">Dasbor Verifikasi Skripsi, Tesis &amp; Disertasi</span></li>
                <li><span class="check">✓</span> <span class="li-txt"><strong>AI Oral Defense Questions</strong> untuk Dosen Penguji</span></li>
                <li><span class="check">✓</span> <span class="li-txt">Isolasi Substantif Bab Pembahasan Ilmiah</span></li>
                <li><span class="check">✓</span> <span class="li-txt">Multi-user akun Dosen Pembimbing &amp; Admin Prodi</span></li>
            </ul>
            <div style="font-size: 7.2pt; color: #4338ca; font-weight: 700; text-align: center; border-top: 1px dashed #c7d2fe; padding-top: 5px;">
                Solusi Paling Populer Fakultas
            </div>
        </div>

        <!-- Paket 3: Enterprise Kampus -->
        <div class="package-card">
            <div class="package-header">
                <div class="package-tag">Universitas &amp; LPPM</div>
                <div class="package-name">Paket Enterprise Kampus</div>
            </div>
            <ul class="package-list">
                <li><span class="check">✓</span> <span class="li-txt"><strong>Instalasi Mandiri (On-Premise / Private Cloud)</strong></span></li>
                <li><span class="check">✓</span> <span class="li-txt">Panen Otomatis Repositori Kampus (OAI-PMH)</span></li>
                <li><span class="check">✓</span> <span class="li-txt">Integrasi Single Sign-On (SSO / LDAP / Google)</span></li>
                <li><span class="check">✓</span> <span class="li-txt">Dasbor Analitik Tren Integritas Universitas</span></li>
                <li><span class="check">✓</span> <span class="li-txt">Dedicated Technical Support &amp; SLA 99.9%</span></li>
            </ul>
            <div style="font-size: 7.2pt; color: #64748b; font-weight: 600; text-align: center; border-top: 1px dashed #e2e8f0; padding-top: 5px;">
                Lisensi Kampus Penuh / Multi-Tahun
            </div>
        </div>
    </div>

    <!-- PROGRAM UJI COBA 14 HARI (TRIAL) -->
    <div class="trial-box">
        <div class="trial-badge-row">
            <span class="section-tag rose" style="margin-bottom: 0;">PROGRAM UJI COBA RESMI JURNAL &amp; KAMPUS</span>
            <span class="trial-pill-highlight">🎁 50 Naskah Riil &bull; 14 Hari Penuh</span>
        </div>
        <h3 class="trial-title">Coba Gratis 14 Hari &mdash; Tanpa Komitmen</h3>
        <p class="trial-desc">
            Uji AR Tilik dengan naskah riil jurnal Anda: <strong>50 naskah</strong>, semua fitur aktif (<strong>Dual-Lens</strong>, <strong>forensik Gemini AI</strong>, <strong>sertifikat QR</strong>, <strong>konektor OJS</strong>). Tanpa kartu kredit, tanpa sales yang menekan. Cukup kirim email &mdash; akun trial aktif dalam <strong>1x24 jam</strong>.
        </p>

        <div class="trial-features-row">
            <div class="trial-feat-item">
                <span class="feat-check">✓</span>
                <span><strong>50 Naskah Riil</strong> (Kapasitas Uji Penuh)</span>
            </div>
            <div class="trial-feat-item">
                <span class="feat-check">✓</span>
                <span><strong>Dual-Lens &amp; Gemini AI</strong> Aktif</span>
            </div>
            <div class="trial-feat-item">
                <span class="feat-check">✓</span>
                <span><strong>Konektor OJS 3.x</strong> &amp; Sertifikat QR</span>
            </div>
            <div class="trial-feat-item">
                <span class="feat-check">✓</span>
                <span><strong>Tanpa Kartu Kredit</strong> &amp; Bebas Tekanan</span>
            </div>
        </div>
    </div>

    <!-- SALURAN RESMI KONTAK KEMITRAAN & PIC -->
    <div class="cta-box">
        <div class="cta-content">
            <div class="cta-kicker">KONSULTASI TEKNIS &amp; AKTIVASI TRIAL</div>
            <h3 class="cta-title">Hubungi Tim Kemitraan</h3>

            <div class="contact-grid">
                <div class="contact-item">
                    <span class="contact-icon">📱</span>
                    <div>
                        <span class="contact-label">WhatsApp:</span>
                        <strong class="contact-val">082353201673</strong>
                    </div>
                </div>
                <div class="contact-item">
                    <span class="contact-icon">✉️</span>
                    <div>
                        <span class="contact-label">Email:</span>
                        <strong class="contact-val">support@tilik.arsetontong.top</strong>
                    </div>
                </div>
                <div class="contact-item">
                    <span class="contact-icon">🌐</span>
                    <div>
                        <span class="contact-label">Portal:</span>
                        <strong class="contact-val">tilik.arsetontong.top</strong>
                    </div>
                </div>
                <div class="contact-item">
                    <span class="contact-icon">👤</span>
                    <div>
                        <span class="contact-label">PIC:</span>
                        <strong class="contact-val">Makarius Yuwono</strong>
                        <span style="font-size: 6.6pt; color: #94a3b8; display: block; margin-top: 1px;">(respon &lt; 1x24 jam hari kerja)</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="cta-qr-box">
            <img src="__QR_WA_B64__" alt="QR WhatsApp Tim Kemitraan AR Tilik">
            <span>Pindai WhatsApp</span>
        </div>
    </div>

    <div style="margin-top: 4mm; border-top: 1px solid #e2e8f0; padding-top: 2.5mm; display: flex; justify-content: space-between; align-items: center; font-size: 6.8pt; color: #94a3b8;">
        <div>Diterbitkan oleh: <strong>ARSetontong &bull; Indonesia</strong></div>
        <div>Dokumentasi Resmi &bull; Hak Cipta &copy; 2026 AR Tilik. Seluruh hak cipta dilindungi undang-undang.</div>
    </div>
</div>

</body>
</html>
"""
    html = template.replace("__LOGO_B64__", logo_b64)
    html = html.replace("__HERO_B64__", hero_b64)
    html = html.replace("__DUAL_LENS_CONCEPT_B64__", dual_lens_concept_b64)
    html = html.replace("__DUAL_READER_MOCKUP_B64__", dual_reader_mockup_b64)
    html = html.replace("__QR_PORTAL_B64__", qr_portal_b64)
    html = html.replace("__QR_DEMO_B64__", qr_demo_b64)
    html = html.replace("__QR_WA_B64__", qr_wa_b64)
    return html

def main():
    print("=" * 65)
    print("🚀 MEMULAI GENERASI PDF BROSUR & PROSPEKTUS EKSEKUTIF AR TILIK")
    print("=" * 65)

    weasy_bin = shutil.which("weasyprint") or "/usr/bin/weasyprint"
    if not os.path.exists(weasy_bin):
        print(f"[ERROR] WeasyPrint binary tidak ditemukan di sistem: {weasy_bin}")
        sys.exit(1)

    print("📄 Menyusun template HTML & CSS Prospektus Eksekutif...")
    html_content = build_html()

    print(f"🎨 Mengompilasi PDF menggunakan WeasyPrint CLI ({weasy_bin})...")
    with tempfile.NamedTemporaryFile(suffix=".html", mode="w", encoding="utf-8", delete=False) as f:
        f.write(html_content)
        temp_html_path = f.name

    try:
        cmd = [
            weasy_bin,
            temp_html_path,
            str(OUTPUT_PDF_PATH)
        ]
        res = subprocess.run(cmd, capture_output=True, text=True)
        if res.returncode != 0:
            print(f"[!] WeasyPrint stderr: {res.stderr}", file=sys.stderr)
            raise RuntimeError(f"Gagal menghasilkan PDF: {res.stderr}")

        file_size = OUTPUT_PDF_PATH.stat().st_size
        print(f"✅ PDF berhasil dikompilasi ke: {OUTPUT_PDF_PATH}")
        print(f"📦 Ukuran Berkas: {file_size:,} bytes ({file_size / (1024*1024):.2f} MB)")
    finally:
        if os.path.exists(temp_html_path):
            os.remove(temp_html_path)

    # Validasi jumlah halaman dengan pdfinfo jika tersedia
    try:
        res = subprocess.run(["pdfinfo", str(OUTPUT_PDF_PATH)], capture_output=True, text=True, check=True)
        for line in res.stdout.splitlines():
            if "Pages:" in line:
                print(f"📑 Total Halaman Dokumen: {line.split()[-1]} (Target: Tepat 4 Halaman)")
    except Exception:
        pass

    print("=" * 65)
    print("🎉 SELESAI: Berkas media promosi siap diunduh dan dipublikasikan!")
    print("=" * 65)

if __name__ == "__main__":
    main()
