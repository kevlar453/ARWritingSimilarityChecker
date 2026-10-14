#!/usr/bin/env python3
"""
Generator PDF Dokumentasi & Panduan Integrasi OJS 3.x - AR Tilik
Membuat berkas PDF resmi, profesional, kaya visual, dan siap unduh
untuk pengelola jurnal ilmiah (Editor in Chief, Reviewer, dan Administrator OJS).
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
OUTPUT_PDF_PATH = PUBLIC_DIR / "integrations" / "ojs" / "Panduan_Integrasi_OJS_ARTilik.pdf"

# Pastikan direktori tujuan tersedia
OUTPUT_PDF_PATH.parent.mkdir(parents=True, exist_ok=True)

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
    """Menyusun seluruh struktur HTML dokumen PDF multi-halaman."""
    logo_b64 = load_image_base64(PUBLIC_DIR / "images" / "logo.png")
    admin_screen_b64 = load_image_base64(PUBLIC_DIR / "images" / "ojs_integration_admin.png")
    dual_lens_concept_b64 = load_image_base64(PUBLIC_DIR / "images" / "dual_lens_concept.jpg")
    dual_reader_mockup_b64 = load_image_base64(PUBLIC_DIR / "images" / "dual_reader_mockup.jpg")
    qr_demo_b64 = generate_qr_base64("https://tilik.arsetontong.top/verify/ART-OJS3-8821-DEMO")

    template = """<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Panduan Integrasi OJS 3.x - AR Tilik Dual-Lens</title>
<style>
/* ====================================================== */
/* PRINT PAGE SETUP (A4 PORTRAIT)                        */
/* ====================================================== */
@page {
    size: A4 portrait;
    margin: 16mm 14mm 16mm 14mm;
    @top-left {
        content: "AR Tilik | Panduan Integrasi OJS 3.x";
        font-family: 'Liberation Sans', 'DejaVu Sans', Arial, sans-serif;
        font-size: 7.5pt;
        font-weight: bold;
        color: #0284c7;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    @top-right {
        content: "Academic Writing Similarity & Evidence Checker";
        font-family: 'Liberation Sans', 'DejaVu Sans', Arial, sans-serif;
        font-size: 7.5pt;
        color: #64748b;
    }
    @bottom-left {
        content: "https://tilik.arsetontong.top • Dokumentasi Resmi v1.3.0 Production (Edisi Pengelola Jurnal)";
        font-family: 'Liberation Sans', 'DejaVu Sans', Arial, sans-serif;
        font-size: 7.5pt;
        color: #94a3b8;
    }
    @bottom-right {
        content: "Halaman " counter(page) " dari " counter(pages);
        font-family: 'Liberation Sans', 'DejaVu Sans', Arial, sans-serif;
        font-size: 7.5pt;
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
/* GLOBAL TYPOGRAPHY & RESET                              */
/* ====================================================== */
* {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
}

body {
    font-family: 'Liberation Sans', 'DejaVu Sans', Arial, sans-serif;
    color: #1e293b;
    font-size: 8.6pt;
    line-height: 1.42;
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
/* COVER PAGE STYLES                                      */
/* ====================================================== */
.cover-container {
    width: 210mm;
    height: 297mm;
    background: linear-gradient(145deg, #070f1e 0%, #0b1e36 45%, #0f294a 100%);
    color: #ffffff;
    padding: 22mm 20mm;
    position: relative;
    overflow: hidden;
}

.cover-decor-circle1 {
    position: absolute;
    width: 320px;
    height: 320px;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(14, 165, 233, 0.15) 0%, rgba(14, 165, 233, 0) 70%);
    top: -60px;
    right: -60px;
}

.cover-decor-circle2 {
    position: absolute;
    width: 400px;
    height: 400px;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(56, 189, 248, 0.1) 0%, rgba(56, 189, 248, 0) 70%);
    bottom: -80px;
    left: -100px;
}

.cover-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    border-bottom: 1px solid rgba(255, 255, 255, 0.12);
    padding-bottom: 14px;
    margin-bottom: 24px;
}

.cover-logo-box {
    display: flex;
    align-items: center;
    gap: 12px;
}

.cover-logo-img {
    width: 42px;
    height: 42px;
    border-radius: 10px;
    background: #ffffff;
    padding: 3px;
}

.cover-brand-title {
    font-size: 15pt;
    font-weight: 800;
    letter-spacing: -0.3px;
    color: #ffffff;
}

.cover-brand-sub {
    font-size: 7.5pt;
    color: #38bdf8;
    font-weight: 600;
    letter-spacing: 0.5px;
    text-transform: uppercase;
}

.cover-badge-top {
    display: inline-block;
    background: rgba(14, 165, 233, 0.2);
    border: 1px solid rgba(14, 165, 233, 0.4);
    color: #7dd3fc;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 7pt;
    font-weight: 700;
    letter-spacing: 0.5px;
    text-transform: uppercase;
}

.cover-title-group {
    margin-top: 25px;
    margin-bottom: 20px;
}

.cover-doc-type {
    font-size: 8.5pt;
    color: #38bdf8;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 1.5px;
    margin-bottom: 10px;
}

.cover-main-title {
    font-size: 25pt;
    font-weight: 900;
    line-height: 1.15;
    letter-spacing: -0.5px;
    color: #ffffff;
    margin-bottom: 12px;
}

.cover-main-title span {
    color: #38bdf8;
}

.cover-subtitle {
    font-size: 10.5pt;
    line-height: 1.45;
    color: #cbd5e1;
    font-weight: 400;
    max-width: 90%;
}

.cover-highlights-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
    margin-top: 20px;
    margin-bottom: 24px;
}

.cover-highlight-card {
    background: rgba(15, 23, 42, 0.7);
    border: 1px solid rgba(56, 189, 248, 0.2);
    border-radius: 10px;
    padding: 12px 14px;
}

.cover-highlight-title {
    font-size: 8.5pt;
    font-weight: 700;
    color: #38bdf8;
    margin-bottom: 4px;
    display: flex;
    align-items: center;
    gap: 6px;
}

.cover-highlight-desc {
    font-size: 7.5pt;
    color: #94a3b8;
    line-height: 1.35;
}

.cover-banner-visual {
    border-radius: 12px;
    border: 1px solid rgba(56, 189, 248, 0.3);
    overflow: hidden;
    margin-bottom: 22px;
    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.5);
    background: #091322;
}

.cover-banner-visual img {
    width: 100%;
    height: 135px;
    object-fit: cover;
    display: block;
}

.cover-meta-footer {
    position: absolute;
    bottom: 20mm;
    left: 20mm;
    right: 20mm;
    border-top: 1px solid rgba(255, 255, 255, 0.12);
    padding-top: 14px;
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
}

.cover-meta-item {
    font-size: 7pt;
    color: #64748b;
    line-height: 1.4;
}

.cover-meta-item strong {
    color: #cbd5e1;
    font-weight: 600;
}

/* ====================================================== */
/* SECTION HEADINGS & BADGES                              */
/* ====================================================== */
.section-header {
    border-bottom: 2px solid #0284c7;
    padding-bottom: 5px;
    margin-bottom: 12px;
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
}

.section-title {
    font-size: 12.5pt;
    font-weight: 800;
    color: #0f172a;
    letter-spacing: -0.3px;
}

.section-number {
    color: #0284c7;
    font-weight: 900;
    margin-right: 4px;
}

.section-subtitle {
    font-size: 7.2pt;
    color: #64748b;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

h3.sub-heading {
    font-size: 8.8pt;
    font-weight: 700;
    color: #0f294a;
    margin-top: 10px;
    margin-bottom: 5px;
}

p.body-text {
    font-size: 8.3pt;
    color: #334155;
    margin-bottom: 7px;
    line-height: 1.42;
}

/* ====================================================== */
/* CALLOUT ALERTS & HIGHLIGHT BOXES                       */
/* ====================================================== */
.callout {
    border-radius: 7px;
    padding: 8px 11px;
    margin: 8px 0;
    font-size: 7.6pt;
    line-height: 1.38;
    border-left: 4px solid;
}

.callout-info {
    background: #f0f9ff;
    border-color: #0284c7;
    color: #0369a1;
}

.callout-success {
    background: #ecfdf5;
    border-color: #10b981;
    color: #047857;
}

.callout-warning {
    background: #fffbeb;
    border-color: #f59e0b;
    color: #b45309;
}

.callout-danger {
    background: #fef2f2;
    border-color: #ef4444;
    color: #b91c1c;
}

.callout-dark {
    background: #0f172a;
    border-color: #38bdf8;
    color: #e2e8f0;
}

.callout-title {
    font-weight: 700;
    font-size: 8pt;
    margin-bottom: 2px;
    display: flex;
    align-items: center;
    gap: 5px;
}

/* ====================================================== */
/* FEATURE PROMOTION CARDS                                */
/* ====================================================== */
.promo-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 8px;
    margin: 8px 0;
}

.promo-card {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 7px;
    padding: 8px 9px;
    border-top: 3px solid #0284c7;
}

.promo-card-title {
    font-size: 8pt;
    font-weight: 700;
    color: #0f294a;
    margin-bottom: 3px;
    display: flex;
    align-items: center;
    gap: 5px;
}

.promo-card-desc {
    font-size: 7.2pt;
    color: #475569;
    line-height: 1.32;
}

/* ====================================================== */
/* COMPARISON TABLE                                       */
/* ====================================================== */
table.data-table {
    width: 100%;
    border-collapse: collapse;
    margin: 5px 0;
    font-size: 7.05pt;
}

table.data-table th {
    background: #0f294a;
    color: #ffffff;
    font-weight: 700;
    padding: 4px 6px;
    text-align: left;
    border: 1px solid #1e3a8a;
}

table.data-table td {
    padding: 3.5px 6px;
    border: 1px solid #e2e8f0;
    color: #334155;
    vertical-align: middle;
}

table.data-table tr:nth-child(even) {
    background: #f8fafc;
}

table.data-table tr.highlight-row {
    background: #f0fdf4;
    font-weight: 600;
}

.tag-badge {
    display: inline-block;
    padding: 1.5px 5px;
    border-radius: 4px;
    font-size: 6.3pt;
    font-weight: 700;
    text-transform: uppercase;
}

.tag-green { background: #dcfce7; color: #15803d; }
.tag-blue { background: #e0f2fe; color: #0369a1; }
.tag-amber { background: #fef3c7; color: #b45309; }
.tag-red { background: #fee2e2; color: #b91c1c; }

/* ====================================================== */
/* CODE BLOCKS & SYNTAX                                   */
/* ====================================================== */
.code-block {
    background: #091322;
    border: 1px solid #1e293b;
    border-radius: 6px;
    padding: 6px 9px;
    font-family: 'Liberation Mono', 'DejaVu Sans Mono', Courier, monospace;
    font-size: 6.5pt;
    line-height: 1.32;
    color: #e2e8f0;
    margin: 6px 0;
    white-space: pre-wrap;
    word-break: break-all;
    overflow: hidden;
}

.code-header {
    background: #0f1e36;
    border-bottom: 1px solid #1e293b;
    padding: 3px 8px;
    font-size: 6.3pt;
    font-weight: 700;
    color: #38bdf8;
    display: flex;
    justify-content: space-between;
    margin: -6px -9px 5px -9px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    white-space: normal;
}

.code-keyword { color: #f43f5e; font-weight: bold; }
.code-func { color: #38bdf8; }
.code-str { color: #34d399; }
.code-comment { color: #64748b; font-style: italic; }
.code-var { color: #fbbf24; }

/* ====================================================== */
/* STEP BY STEP GUIDES                                    */
/* ====================================================== */
.step-container {
    display: flex;
    gap: 9px;
    margin-bottom: 9px;
}

.step-badge {
    width: 22px;
    height: 22px;
    border-radius: 50%;
    background: #0284c7;
    color: #ffffff;
    font-weight: 800;
    font-size: 7.8pt;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    margin-top: 1px;
}

.step-content {
    flex: 1;
}

.step-title {
    font-size: 8.2pt;
    font-weight: 700;
    color: #0f294a;
    margin-bottom: 2px;
}

.step-desc {
    font-size: 7.3pt;
    color: #475569;
    line-height: 1.32;
}

/* ====================================================== */
/* DIAGRAMS & VISUAL CONTAINERS                           */
/* ====================================================== */
.diagram-box {
    background: #f8fafc;
    border: 1px solid #cbd5e1;
    border-radius: 7px;
    padding: 7px;
    margin: 7px 0;
    text-align: center;
}

.diagram-caption {
    font-size: 6.6pt;
    font-weight: 700;
    color: #475569;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-top: 4px;
}

.image-frame {
    border-radius: 6px;
    border: 1px solid #cbd5e1;
    overflow: hidden;
    margin: 6px 0;
    box-shadow: 0 3px 5px -1px rgba(0, 0, 0, 0.05);
}

.image-frame img {
    width: 100%;
    display: block;
}

/* ====================================================== */
/* CERTIFICATE PREVIEW CARD                               */
/* ====================================================== */
.cert-card {
    background: #ffffff;
    border: 2px solid #0284c7;
    border-radius: 9px;
    padding: 10px 12px;
    margin: 8px 0;
    box-shadow: 0 4px 10px rgba(2, 132, 199, 0.08);
    position: relative;
}

.cert-card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid #e2e8f0;
    padding-bottom: 6px;
    margin-bottom: 6px;
}

.cert-stamp {
    background: #ecfdf5;
    border: 1px solid #10b981;
    color: #047857;
    padding: 2.5px 7px;
    border-radius: 4px;
    font-size: 6.5pt;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
</style>
</head>
<body>

<!-- ============================================================== -->
<!-- HALAMAN 1: COVER PAGE / SAMPUL DOKUMEN RESMI                   -->
<!-- ============================================================== -->
<div class="cover-container page-break">
    <div class="cover-decor-circle1"></div>
    <div class="cover-decor-circle2"></div>

    <div class="cover-header">
        <div class="cover-logo-box">
            <img class="cover-logo-img" src="__LOGO_B64__" alt="AR Tilik Logo" />
            <div>
                <div class="cover-brand-title">AR Tilik</div>
                <div class="cover-brand-sub">Academic Writing Similarity &amp; Evidence Checker</div>
            </div>
        </div>
        <div class="cover-badge-top">Dokumentasi Resmi v1.3.0 Production • OJS 3.x Ready</div>
    </div>

    <div class="cover-title-group">
        <div class="cover-doc-type">Buku Panduan & Dokumentasi Integrasi Teknis</div>
        <h1 class="cover-main-title">
            Otomasi Penelaahan Naskah Jurnal Ilmiah dengan <span>PKP OJS 3.x</span>
        </h1>
        <p class="cover-subtitle">
            Integrasi REST API Publik, Konektor PHP Cerdas, Evaluasi Mesin Dual-Lens, Audit Forensik Cerdas AI, Panduan Telaah Redaksi, dan Penerbitan Sertifikat Keabsahan Digital Berbasis QR Code untuk Pengelola Jurnal Kampus.
        </p>
    </div>

    <div class="cover-banner-visual">
        <img src="__DUAL_LENS_CONCEPT_B64__" alt="Dual Lens Concept Visualization" />
    </div>

    <div class="cover-highlights-grid">
        <div class="cover-highlight-card">
            <div class="cover-highlight-title">
                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                100% Kedaulatan & Kerahasiaan Data
            </div>
            <div class="cover-highlight-desc">
                Naskah submisi tidak dibocorkan ke basis data komersial publik. Privasi hak cipta penulis terlindungi hingga resmi terbit.
            </div>
        </div>
        <div class="cover-highlight-card">
            <div class="cover-highlight-title">
                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                Mesin Dual-Lens &amp; Forensik AI
            </div>
            <div class="cover-highlight-desc">
                Kombinasi Lensa 1 (5-Gram exact match), Lensa 2 (TF-IDF Cosine), dan penalaran semantik AI untuk deteksi fabrikasi rujukan sitasi.
            </div>
        </div>
        <div class="cover-highlight-card">
            <div class="cover-highlight-title">
                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                Sertifikat QR &amp; Panduan Telaah
            </div>
            <div class="cover-highlight-desc">
                Bukti keabsahan naskah digital ber-hash SHA-256 berkode QR serta 3 set pertanyaan telaah kritis otomatis siap pakai bagi Mitra Bestari.
            </div>
        </div>
        <div class="cover-highlight-card">
            <div class="cover-highlight-title">
                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                Hemat Anggaran Kampus Hingga 70%
            </div>
            <div class="cover-highlight-desc">
                Model lisensi terjangkau karya anak bangsa tanpa kontrak valas (USD) yang memberatkan anggaran jurnal prodi dan fakultas.
            </div>
        </div>
    </div>

    <div class="cover-meta-footer">
        <div class="cover-meta-item">
            <strong>Target Pembaca:</strong><br>
            Dewan Editor Jurnal, Editor-in-Chief, Reviewer,<br>
            Pengelola Jurnal SINTA, & Tim Sysadmin Kampus
        </div>
        <div class="cover-meta-item" style="text-align: center;">
            <strong>Kompatibilitas:</strong><br>
            PKP Open Journal Systems (OJS) 3.2+ / 3.3 / 3.4<br>
            PHP 8.0, 8.1, 8.2, 8.3 & cURL
        </div>
        <div class="cover-meta-item" style="text-align: right;">
            <strong>Penerbit & Portal Resmi:</strong><br>
            Tim AR Tilik Academic Integrity System<br>
            <span style="color: #38bdf8;">https://tilik.arsetontong.top</span>
        </div>
    </div>
</div>

<!-- ============================================================== -->
<!-- HALAMAN 2: MENGAPA AR TILIK? (PROMOSI & KEUNGGULAN JURNAL)      -->
<!-- ============================================================== -->
<div class="page-break">
    <div class="section-header">
        <div>
            <span class="section-number">01</span>
            <span class="section-title">Mengapa Memilih AR Tilik untuk Jurnal Ilmiah?</span>
        </div>
        <div class="section-subtitle">Promosi & Nilai Tambah Eksklusif</div>
    </div>

    <p class="body-text">
        Pengelolaan jurnal ilmiah di era digital menuntut kecepatan peninjauan (<em>editorial turnaround time</em>) sekaligus ketelitian tinggi dalam memverifikasi orisinalitas naskah dan mencegah pelanggaran integritas akademik. Selama bertahun-tahun, pengelola jurnal di perguruan tinggi Indonesia menghadapi dilema: <strong>biaya langganan platform asing yang sangat mahal berbasis valas (USD)</strong>, minimnya dukungan repositori karya ilmiah lokal Indonesia, serta risiko kerahasiaan naskah submisi penulis yang belum dipublikasikan (<em>pre-published manuscripts</em>).
    </p>

    <div class="promo-grid">
        <div class="promo-card">
            <div class="promo-card-title">
                <span class="tag-badge tag-blue">Kedaulatan Data</span>
                Naskah Pra-Publikasi Aman 100%
            </div>
            <div class="promo-card-desc">
                AR Tilik menjamin naskah berstatus draft tidak diindeks ke repositori global terbuka yang dapat membocorkan ide riset sebelum terbit resmi. Hak cipta penulis sepenuhnya dihormati.
            </div>
        </div>
        <div class="promo-card">
            <div class="promo-card-title">
                <span class="tag-badge tag-green">Akurasi &amp; Forensik</span>
                Dual-Lens &amp; Forensik AI
            </div>
            <div class="promo-card-desc">
                Tidak lagi terkecoh sekadar skor tunggal. Memisahkan salin-tempel literal (Lensa 1) dengan parafrase (Lensa 2), ditambah penalaran AI untuk mendeteksi fabrikasi sitasi fiktif.
            </div>
        </div>
        <div class="promo-card">
            <div class="promo-card-title">
                <span class="tag-badge tag-amber">Akreditasi SINTA</span>
                Sertifikat &amp; Pertanyaan Telaah
            </div>
            <div class="promo-card-desc">
                Setiap naskah memperoleh lembar sertifikasi ber-QR code dan SHA-256 untuk akreditasi ARJUNA/SINTA, serta 3 pertanyaan telaah terarah untuk reviewer OJS.
            </div>
        </div>
        <div class="promo-card">
            <div class="promo-card-title">
                <span class="tag-badge tag-red">Efisiensi Biaya</span>
                Penghematan Hingga 70% Anggaran
            </div>
            <div class="promo-card-desc">
                Didesain khusus untuk ekosistem akademis Indonesia dengan biaya transparan berbasis kuota wajar, tanpa sistem lisensi per kepala mahasiswa yang memberatkan kas fakultas.
            </div>
        </div>
    </div>

    <h3 class="sub-heading">Matriks Komparasi Head-to-Head: AR Tilik vs Platform Lain</h3>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 28%;">Parameter Komparasi</th>
                <th style="width: 24%; background: #0284c7;">AR Tilik (Dual-Lens v1.3)</th>
                <th style="width: 24%;">Vendor Asing Komersial</th>
                <th style="width: 24%;">Detektor Daring Gratisan</th>
            </tr>
        </thead>
        <tbody>
            <tr class="highlight-row">
                <td><strong>Kedaulatan Data &amp; Privasi</strong></td>
                <td><span class="tag-badge tag-green">Sangat Terjamin (Private)</span></td>
                <td><span class="tag-badge tag-amber">Tersimpan di Cloud Asing</span></td>
                <td><span class="tag-badge tag-red">Risiko Naskah Dijual / Bocor</span></td>
            </tr>
            <tr>
                <td><strong>Deteksi Parafrase Kalimat</strong></td>
                <td><span class="tag-badge tag-green">Ya (Lensa 2 TF-IDF Cosine)</span></td>
                <td><span class="tag-badge tag-green">Ya (Model Proprietary)</span></td>
                <td><span class="tag-badge tag-red">Tidak (Hanya String Match)</span></td>
            </tr>
            <tr>
                <td><strong>Audit Forensik Cerdas AI &amp; Sitasi</strong></td>
                <td><span class="tag-badge tag-green">Ya (Verifikasi Fabrikasi Sitasi)</span></td>
                <td><span class="tag-badge tag-amber">Terbatas / Fitur Add-on USD</span></td>
                <td><span class="tag-badge tag-red">Tidak Ada</span></td>
            </tr>
            <tr>
                <td><strong>Panduan Pertanyaan Telaah Redaksi</strong></td>
                <td><span class="tag-badge tag-green">Ya (3 Set Pertanyaan Otomatis)</span></td>
                <td><span class="tag-badge tag-red">Tidak Ada</span></td>
                <td><span class="tag-badge tag-red">Tidak Ada</span></td>
            </tr>
            <tr>
                <td><strong>Sertifikat Digital Ber-QR Code</strong></td>
                <td><span class="tag-badge tag-green">Ya, Verifikasi Publik Instan</span></td>
                <td><span class="tag-badge tag-amber">Hanya Teks PDF Standar</span></td>
                <td><span class="tag-badge tag-red">Tidak Ada</span></td>
            </tr>
            <tr>
                <td><strong>Konektivitas OJS 3.x</strong></td>
                <td><span class="tag-badge tag-green">Native REST API &amp; PHP Connector</span></td>
                <td><span class="tag-badge tag-amber">Biaya Lisensi API Tambahan</span></td>
                <td><span class="tag-badge tag-red">Manual Unggah Satu-Satu</span></td>
            </tr>
            <tr>
                <td><strong>Indeks Repositori Lokal Indonesia</strong></td>
                <td><span class="tag-badge tag-green">Terhubung (Garuda, Neliti, EPrints)</span></td>
                <td><span class="tag-badge tag-amber">Terbatas pada Penerbit Besar</span></td>
                <td><span class="tag-badge tag-red">Sangat Terbatas</span></td>
            </tr>
            <tr>
                <td><strong>Skema Biaya &amp; Lisensi</strong></td>
                <td><span class="tag-badge tag-green">Hemat, Rupiah, Kuota Fleksibel</span></td>
                <td><span class="tag-badge tag-red">Mahal (Mata Uang USD)</span></td>
                <td><span class="tag-badge tag-amber">Gratis Terbatas / Iklan</span></td>
            </tr>
        </tbody>
    </table>

    <div class="callout callout-info avoid-break">
        <div class="callout-title">
            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            Pernyataan Manfaat untuk Pengelola Jurnal
        </div>
        Dengan menghubungkan OJS ke AR Tilik, dewan redaksi tidak perlu lagi mengunduh berkas satu per satu, mengunggah ke aplikasi terpisah, lalu membagikan tangkapan layar kepada reviewer. Melalui pustaka konektor PHP mandiri dan REST API v1, proses pemeriksaan naskah dapat diintegrasikan ke dalam alur kerja redaksi, dan hasilnya langsung dapat dilampirkan ke ruang diskusi peninjauan (<em>Review Discussions</em>).
    </div>
</div>

<!-- ============================================================== -->
<!-- HALAMAN 3: ARSITEKTUR & ALUR KERJA INTEGRASI OJS               -->
<!-- ============================================================== -->
<div class="page-break">
    <div class="section-header">
        <div>
            <span class="section-number">02</span>
            <span class="section-title">Arsitektur & Alur Kerja Integrasi OJS 3.x</span>
        </div>
        <div class="section-subtitle">Siklus Hidup Naskah (Workflow Lifecycle)</div>
    </div>

    <p class="body-text">
        Integrasi antara OJS 3 dan AR Tilik menggunakan arsitektur RESTful berbasis asinkron (<em>non-blocking asynchronous queue</em>). Naskah yang diunggah ke OJS dikirimkan secara instan melalui konektor PHP ke gateway AR Tilik tanpa memperlambat interaksi pengguna pada portal jurnal.
    </p>

    <!-- DIAGRAM VEKTOR SVG ALUR INTEGRASI -->
    <div class="diagram-box avoid-break">
        <svg viewBox="0 0 680 230" width="100%" height="195" style="font-family: 'Liberation Sans', sans-serif;">
            <!-- Background boxes -->
            <rect x="10" y="10" width="150" height="210" rx="8" fill="#f0f9ff" stroke="#bae6fd" stroke-width="1.5" />
            <text x="85" y="28" fill="#0369a1" font-size="9.5" font-weight="bold" text-anchor="middle">LINGKUNGAN OJS 3</text>
            
            <rect x="260" y="10" width="160" height="210" rx="8" fill="#f8fafc" stroke="#cbd5e1" stroke-width="1.5" />
            <text x="340" y="28" fill="#0f294a" font-size="9.5" font-weight="bold" text-anchor="middle">GATEWAY REST API</text>

            <rect x="520" y="10" width="150" height="210" rx="8" fill="#ecfdf5" stroke="#a7f3d0" stroke-width="1.5" />
            <text x="595" y="28" fill="#047857" font-size="9.5" font-weight="bold" text-anchor="middle">MESIN DUAL-LENS</text>

            <!-- Step 1: Author -->
            <rect x="25" y="40" width="120" height="40" rx="6" fill="#ffffff" stroke="#0284c7" stroke-width="1.2" />
            <text x="85" y="56" fill="#0f172a" font-size="8.2" font-weight="bold" text-anchor="middle">1. Author Submisi</text>
            <text x="85" y="70" fill="#64748b" font-size="7.2" text-anchor="middle">Unggah PDF/DOCX</text>

            <!-- Step 2: Connector Hook -->
            <rect x="25" y="98" width="120" height="42" rx="6" fill="#0284c7" stroke="#0369a1" stroke-width="1.2" />
            <text x="85" y="114" fill="#ffffff" font-size="7.8" font-weight="bold" text-anchor="middle">2. OJS Connector</text>
            <text x="85" y="128" fill="#e0f2fe" font-size="6.8" text-anchor="middle">ARTilikOJSConnector.php</text>

            <!-- Step 6: Reviewer / Editor -->
            <rect x="25" y="158" width="120" height="42" rx="6" fill="#ffffff" stroke="#10b981" stroke-width="1.2" />
            <text x="85" y="174" fill="#047857" font-size="7.8" font-weight="bold" text-anchor="middle">6. Editor & Reviewer</text>
            <text x="85" y="188" fill="#64748b" font-size="6.8" text-anchor="middle">Terima Laporan & PDF</text>

            <!-- Arrow 1 -> 2 -->
            <line x1="85" y1="80" x2="85" y2="98" stroke="#0284c7" stroke-width="1.5" />

            <!-- Arrow 2 -> 3 (HTTP POST /api/v1/check) -->
            <line x1="145" y1="118" x2="275" y2="118" stroke="#0284c7" stroke-width="2" />
            <polygon points="275,114 283,118 275,122" fill="#0284c7" />
            <text x="210" y="110" fill="#0284c7" font-size="7.2" font-weight="bold" text-anchor="middle">POST /api/v1/check</text>
            <text x="210" y="130" fill="#64748b" font-size="6.2" text-anchor="middle">Header X-API-Key</text>

            <!-- Step 3: API Auth & Quota -->
            <rect x="275" y="48" width="130" height="46" rx="6" fill="#ffffff" stroke="#64748b" stroke-width="1.2" />
            <text x="340" y="65" fill="#0f172a" font-size="7.8" font-weight="bold" text-anchor="middle">3. Autentikasi Kunci</text>
            <text x="340" y="78" fill="#64748b" font-size="6.8" text-anchor="middle">Validasi & Catat Kuota</text>
            <text x="340" y="88" fill="#059669" font-size="6.3" font-weight="bold" text-anchor="middle">Return 202 Accepted</text>

            <!-- Step 4: Worker Job Queue -->
            <rect x="275" y="125" width="130" height="50" rx="6" fill="#ffffff" stroke="#64748b" stroke-width="1.2" />
            <text x="340" y="142" fill="#0f172a" font-size="7.8" font-weight="bold" text-anchor="middle">4. Antrean Komputasi</text>
            <text x="340" y="155" fill="#64748b" font-size="6.8" text-anchor="middle">Async Worker Multi-thread</text>
            <text x="340" y="167" fill="#2563eb" font-size="6.3" text-anchor="middle">Polling / Webhook</text>

            <!-- Arrow 3 -> 4 -->
            <line x1="340" y1="94" x2="340" y2="125" stroke="#64748b" stroke-width="1.5" />

            <!-- Arrow 4 -> 5 (Worker Processing) -->
            <line x1="405" y1="150" x2="535" y2="150" stroke="#059669" stroke-width="2" />
            <polygon points="535,146 543,150 535,154" fill="#059669" />
            <text x="470" y="142" fill="#059669" font-size="7.2" font-weight="bold" text-anchor="middle">Dual-Lens Engine</text>

            <!-- Step 5: Dual-Lens & Forensik Cerdas -->
            <rect x="535" y="44" width="120" height="66" rx="6" fill="#ffffff" stroke="#10b981" stroke-width="1.2" />
            <text x="595" y="59" fill="#047857" font-size="7.8" font-weight="bold" text-anchor="middle">5. Komputasi Bukti</text>
            <text x="595" y="72" fill="#15803d" font-size="6.5" text-anchor="middle">• Dual-Lens (Exact &amp; Cosine)</text>
            <text x="595" y="83" fill="#0284c7" font-size="6.5" text-anchor="middle">• Audit Forensik Cerdas AI</text>
            <text x="595" y="94" fill="#7c3aed" font-size="6.5" text-anchor="middle">• Panduan Telaah Redaksi</text>

            <rect x="535" y="118" width="120" height="52" rx="6" fill="#ffffff" stroke="#10b981" stroke-width="1.2" />
            <text x="595" y="134" fill="#047857" font-size="7.8" font-weight="bold" text-anchor="middle">Sertifikat Digital</text>
            <text x="595" y="147" fill="#64748b" font-size="6.8" text-anchor="middle">Hash SHA-256 &amp; QR Code</text>
            <text x="595" y="159" fill="#64748b" font-size="6.3" text-anchor="middle">Laporan PDF Bersertifikasi</text>

            <!-- Arrow back to OJS (Completed Result) -->
            <path d="M 535 160 L 480 195 L 145 180" fill="none" stroke="#10b981" stroke-width="1.5" stroke-dasharray="4" />
            <polygon points="145,176 137,180 145,184" fill="#10b981" />
            <text x="340" y="202" fill="#047857" font-size="6.8" font-weight="bold" text-anchor="middle">Laporan, Hasil Forensik &amp; Tautan PDF Terkirim ke OJS</text>
        </svg>
        <div class="diagram-caption">Gambar 1: Alur Kerja End-to-End Integrasi PKP OJS 3.x dengan AR Tilik</div>
    </div>

    <h3 class="sub-heading">Titik Pemicu Integrasi (Trigger Points) dalam Redaksi OJS</h3>
    <div class="promo-grid">
        <div class="promo-card">
            <div class="promo-card-title">
                <span class="tag-badge tag-blue">Titik 1</span>
                Desk Evaluation &amp; Cek Sitasi
            </div>
            <div class="promo-card-desc">
                Saat naskah masuk, konektor memicu komputasi Dual-Lens dan verifikasi referensi AI. Editor langsung mengetahui skor kemiripan dan deteksi dini keabsahan rujukan.
            </div>
        </div>
        <div class="promo-card">
            <div class="promo-card-title">
                <span class="tag-badge tag-green">Titik 2</span>
                Diskusi Reviewer &amp; Soal Telaah
            </div>
            <div class="promo-card-desc">
                Tautan laporan interaktif dilampirkan otomatis beserta 3 set pertanyaan telaah kritis. Mitra Bestari dapat langsung menyalinnya ke catatan ulasan naskah di OJS.
            </div>
        </div>
        <div class="promo-card">
            <div class="promo-card-title">
                <span class="tag-badge tag-amber">Titik 3</span>
                Penerbitan Galley &amp; Sertifikat
            </div>
            <div class="promo-card-desc">
                Sebelum artikel terbit, kode verifikasi digital (<code>ART-XXXX-XXXX-XXXX</code>) dan QR verifikasi resmi disematkan pada artikel sebagai bukti standar mutu publikasi SINTA.
            </div>
        </div>
        <div class="promo-card">
            <div class="promo-card-title">
                <span class="tag-badge tag-red">Keamanan</span>
                Kolaborasi Aman &amp; Rate Limit
            </div>
            <div class="promo-card-desc">
                Didukung tautan ulasan aman (Secure Share Link) tanpa mewajibkan reviewer eksternal mendaftar akun lokal, serta perlindungan rate limit 60 req/menit.
            </div>
        </div>
    </div>
</div>

<!-- ============================================================== -->
<!-- HALAMAN 4: KONSEP & MEKANISME DUAL-LENS                        -->
<!-- ============================================================== -->
<div class="page-break">
    <div class="section-header">
        <div>
            <span class="section-number">03</span>
            <span class="section-title">Konsep Mesin Analisis Dual-Lens</span>
        </div>
        <div class="section-subtitle">Akurasi Tanpa Vonis Keliru</div>
    </div>

    <p class="body-text">
        Kelemahan terbesar alat penelaah kemiripan konvensional adalah memberikan satu skor tunggal tanpa membedakan <strong>frasa metodologi umum yang wajar</strong> dengan <strong>salin-tempel literal sekuensial</strong> atau <strong>parafrase otomatis berstruktur sinonim</strong>. AR Tilik memecahkan tantangan ini dengan membedah teks melalui dua lensa komputasi independen dan lapisan audit semantik:
    </p>

    <div class="promo-grid">
        <div class="promo-card" style="border-top-color: #dc2626;">
            <div class="promo-card-title" style="color: #dc2626;">
                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                Lensa 1: Exact 5-Gram Shingling
            </div>
            <div class="promo-card-desc">
                • <strong>Mekanisme:</strong> Memecah teks menjadi kelompok 5 kata berurutan secara sekuensial dan membandingkan hash blok teks identik.<br>
                • <strong>Fungsi Utama:</strong> Mendeteksi kutipan langsung tanpa tanda petik, salin-tempel kata-demi-kata (<em>verbatim match</em>), dan mengidentifikasi blok terpanjang.
            </div>
        </div>
        <div class="promo-card" style="border-top-color: #0284c7;">
            <div class="promo-card-title" style="color: #0284c7;">
                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                Lensa 2: TF-IDF Cosine Vector Space
            </div>
            <div class="promo-card-desc">
                • <strong>Mekanisme:</strong> Membangun vektor multi-dimensi berdasarkan frekuensi kata dan bobot kelangkaan istilah ilmiah (IDF) di seluruh korpus.<br>
                • <strong>Fungsi Utama:</strong> Menemukan parafrase struktural, perubahan kalimat aktif-pasif, manipulasi sinonim kata, dan kesamaan topik mendalam.
            </div>
        </div>
    </div>

    <!-- GAMBAR PENJELAS TAMPILAN DUAL-READER INTERAKTIF -->
    <div class="image-frame avoid-break" style="margin: 6px 0;">
        <img src="__DUAL_READER_MOCKUP_B64__" style="height: 90px; object-fit: cover;" alt="Antarmuka Penelaahan Naskah Dual-Lens AR Tilik" />
    </div>
    <div class="diagram-caption" style="text-align: center; margin-bottom: 6px;">
        Gambar 2: Antarmuka Interaktif Dual-Reader AR Tilik untuk Evaluasi Bukti Kemiripan
    </div>

    <h3 class="sub-heading">Matriks Kuadran Keputusan Redaksi Jurnal</h3>
    <table class="data-table avoid-break">
        <thead>
            <tr>
                <th style="width: 25%;">Kuadran Evaluasi</th>
                <th style="width: 18%;">Lensa 1 (Exact)</th>
                <th style="width: 18%;">Lensa 2 (Semantic)</th>
                <th style="width: 39%;">Rekomendasi Tindakan Redaksi OJS</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><strong>1. Naskah Orisinal</strong></td>
                <td><span class="tag-badge tag-green">&lt; 5%</span></td>
                <td><span class="tag-badge tag-green">&lt; 15%</span></td>
                <td><strong>Lolos Cek Orisinalitas.</strong> Segera kirimkan ke Reviewer untuk penilaian substansi.</td>
            </tr>
            <tr>
                <td><strong>2. Parafrase Dominan</strong></td>
                <td><span class="tag-badge tag-green">&lt; 5%</span></td>
                <td><span class="tag-badge tag-amber">&gt; 25%</span></td>
                <td><strong>Tinjauan Konteks.</strong> Pastikan sitasi sumber telah dicantumkan dengan benar.</td>
            </tr>
            <tr>
                <td><strong>3. Kutipan Literal Tinggi</strong></td>
                <td><span class="tag-badge tag-amber">&gt; 15%</span></td>
                <td><span class="tag-badge tag-green">&lt; 20%</span></td>
                <td><strong>Revisi Minor / Sitasi.</strong> Minta penulis menyisipkan tanda petik / parafrase ulang.</td>
            </tr>
            <tr style="background: #fef2f2;">
                <td><strong>4. Replikasi Pasase Masif</strong></td>
                <td><span class="tag-badge tag-red">&gt; 20%</span></td>
                <td><span class="tag-badge tag-red">&gt; 35%</span></td>
                <td><strong>Desk Reject / Klarifikasi.</strong> Kembalikan ke penulis dengan bukti PDF resmi.</td>
            </tr>
        </tbody>
    </table>

    <h3 class="sub-heading" style="margin-top: 8px;">Standar Ambang Batas (Threshold) Rekomendasi Akreditasi SINTA</h3>
    <div class="promo-grid avoid-break">
        <div class="promo-card">
            <div class="promo-card-title">Jurnal SINTA 1 &amp; 2 / Scopus</div>
            <div class="promo-card-desc">
                • Maksimal Overall Similarity: <strong>&le; 15% - 20%</strong> | Per Sumber: <strong>&le; 3%</strong><br>
                • Didukung validasi bebas fabrikasi sitasi semantik AI &amp; sertifikat ber-QR resmi.
            </div>
        </div>
        <div class="promo-card">
            <div class="promo-card-title">Jurnal SINTA 3 &amp; 4</div>
            <div class="promo-card-desc">
                • Maksimal Overall Similarity: <strong>&le; 20% - 25%</strong> | Per Sumber: <strong>&le; 5%</strong><br>
                • Isolasi teks substantif (section-aware) mengecualikan cover dan daftar pustaka.
            </div>
        </div>
    </div>
</div>

<!-- ============================================================== -->
<!-- HALAMAN 5: PANDUAN LANGKAH INSTALASI & KONFIGURASI             -->
<!-- ============================================================== -->
<div class="page-break">
    <div class="section-header">
        <div>
            <span class="section-number">04</span>
            <span class="section-title">Panduan Langkah Demi Langkah Instalasi</span>
        </div>
        <div class="section-subtitle">Konfigurasi di Server OJS & AR Tilik</div>
    </div>

    <p class="body-text">
        Proses pemasangan konektor OJS dirancang sangat sederhana dan dapat diselesaikan dalam <strong>kurang dari 10 menit</strong> oleh pengelola jurnal atau staf IT kampus tanpa perlu melakukan kompilasi perangkat lunak tambahan.
    </p>

    <div class="step-container">
        <div class="step-badge">1</div>
        <div class="step-content">
            <div class="step-title">Langkah 1: Membuat API Key di Portal Admin AR Tilik</div>
            <div class="step-desc">
                Masuk ke Portal Admin AR Tilik (<code style="color: #0284c7;">https://tilik.arsetontong.top/admin</code>), buka tab <strong>Integrasi API & OJS</strong>, isi formulir nama jurnal Anda, pilih tipe target <strong>Open Journal Systems (OJS 3.x)</strong>, atur batas kuota bulanan, lalu klik tombol <strong>Generate API Key</strong>. Salin kunci rahasia yang dihasilkan (awalan: <code style="color: #0284c7;">artilik_ojs_...</code>).
            </div>
        </div>
    </div>

    <!-- TANGKAPAN LAYAR PANEL ADMIN AR TILIK -->
    <div class="image-frame avoid-break">
        <img src="__ADMIN_SCREEN_B64__" alt="Tampilan Panel Admin Integrasi API OJS" />
    </div>
    <div class="diagram-caption" style="text-align: center; margin-bottom: 8px;">
        Gambar 3: Antarmuka Manajemen API Key Jurnal OJS pada Portal Admin AR Tilik
    </div>

    <div class="step-container">
        <div class="step-badge">2</div>
        <div class="step-content">
            <div class="step-title">Langkah 2: Mengunduh Konektor PHP &amp; Skrip Runner</div>
            <div class="step-desc">
                Unduh berkas <code style="color: #0284c7;">ARTilikOJSConnector.php</code> dan skrip runner mandiri <code style="color: #0284c7;">artilik_ojs_runner.php</code> dari portal AR Tilik. Salin berkas tersebut ke direktori server OJS Anda (contoh: di dalam folder plugin atau folder kustom integrasi).
            </div>
            <div class="code-block" style="margin-top: 4px;">
<div class="code-header">Terminal / SSH Server OJS</div>
<span class="code-comment"># Salin konektor dan skrip runner ke folder library OJS Anda</span>
<span class="code-keyword">cp</span> ARTilikOJSConnector.php artilik_ojs_runner.php /var/www/ojs/plugins/generic/artilik/
<span class="code-keyword">chmod</span> 644 /var/www/ojs/plugins/generic/artilik/*.php</div>
        </div>
    </div>

    <div class="step-container">
        <div class="step-badge">3</div>
        <div class="step-content">
            <div class="step-title">Langkah 3: Menentukan Metode Integrasi pada OJS 3</div>
            <div class="step-desc">
                Anda dapat memilih salah satu dari 3 cara penerapan sesuai arsitektur jurnal Anda:
                <br>• <strong>Opsi A (Rekomendasi):</strong> Dipanggil via event hook OJS saat berkas diunggah (<code style="color: #0284c7;">SubmissionFile::upload</code>).
                <br>• <strong>Opsi B (Cron Job Otomatis):</strong> Menjalankan skrip CLI berkala untuk memproses naskah baru setiap 15 menit.
                <br>• <strong>Opsi C (Tombol Aksi Manual Editor):</strong> Menambahkan tombol "Periksa Kemiripan AR Tilik" pada halaman Editorial Activity.
            </div>
        </div>
    </div>
</div>

<!-- ============================================================== -->
<!-- HALAMAN 6: CONTOH KODE IMPLEMENTASI PHP SIAP PAKAI             -->
<!-- ============================================================== -->
<div class="page-break">
    <div class="section-header">
        <div>
            <span class="section-number">05</span>
            <span class="section-title">Contoh Implementasi Kode PHP Siap Pakai</span>
        </div>
        <div class="section-subtitle">Skrip Otomasi Lengkap & Handal</div>
    </div>

    <p class="body-text">
        Berikut adalah contoh skrip PHP mandiri yang dapat langsung dijalankan atau diadaptasikan ke dalam modul OJS 3. Skrip menangani pengiriman naskah, polling otomatis hingga selesai, pembacaan skor Dual-Lens, dan pengunduhan sertifikat PDF resmi.
    </p>

    <div class="code-block avoid-break" style="font-size: 6.0pt; line-height: 1.25;">
<div class="code-header">artilik_ojs_runner.php (Contoh Implementasi Lengkap v1.3.0)</div>
<span class="code-keyword">&lt;?php</span>
<span class="code-keyword">require_once</span> <span class="code-str">__DIR__ . '/ARTilikOJSConnector.php'</span>;

<span class="code-comment">// Inisialisasi Konektor dengan API Key dari Portal AR Tilik</span>
<span class="code-var">$connector</span> = <span class="code-keyword">new</span> <span class="code-func">ARTilikOJSConnector</span>(<span class="code-str">'artilik_ojs_YOUR_KEY'</span>, <span class="code-str">'https://tilik.arsetontong.top/api/v1'</span>);

<span class="code-keyword">try</span> {
    <span class="code-comment">// 1. Kirim naskah artikel yang diunggah oleh Penulis di OJS</span>
    <span class="code-var">$filePath</span> = <span class="code-str">'/var/www/ojs/files/journals/1/articles/124/submission/124-article.pdf'</span>;
    <span class="code-var">$res</span> = <span class="code-var">$connector</span>-&gt;<span class="code-func">submitManuscript</span>(<span class="code-var">$filePath</span>, <span class="code-str">'Analisis Algoritma Dual-Lens'</span>, <span class="code-str">'Dr. Budi Santoso'</span>, <span class="code-str">'ojs_124'</span>);
    <span class="code-var">$docId</span>    = <span class="code-var">$res</span>[<span class="code-str">'data'</span>][<span class="code-str">'document_id'</span>];
    <span class="code-var">$reportId</span> = <span class="code-var">$res</span>[<span class="code-str">'data'</span>][<span class="code-str">'report_id'</span>];
    <span class="code-func">echo</span> <span class="code-str">"[✓] Naskah diterima! Doc ID: #{$docId} | Report ID: #{$reportId}\n"</span>;

    <span class="code-comment">// 2. Menunggu komputasi selesai (Polling otomatis setiap 3 detik)</span>
    <span class="code-var">$result</span>    = <span class="code-var">$connector</span>-&gt;<span class="code-func">waitForCompletion</span>(<span class="code-var">$docId</span>, 60, 3);
    <span class="code-var">$sim</span>       = <span class="code-var">$result</span>[<span class="code-str">'data'</span>][<span class="code-str">'overall_similarity'</span>];
    <span class="code-var">$decision</span>  = <span class="code-var">$result</span>[<span class="code-str">'data'</span>][<span class="code-str">'predicted_decision'</span>];
    <span class="code-var">$webReport</span> = <span class="code-var">$result</span>[<span class="code-str">'data'</span>][<span class="code-str">'web_report_url'</span>];
    <span class="code-var">$pdfExport</span> = <span class="code-var">$result</span>[<span class="code-str">'data'</span>][<span class="code-str">'pdf_export_url'</span>];
    <span class="code-func">echo</span> <span class="code-str">"Hasil: Kemiripan {$sim}% | Putusan: {$decision}\nLaporan Web: {$webReport}\n"</span>;

    <span class="code-comment">// 3. Ambil Audit Forensik Cerdas AI &amp; Pertanyaan Telaah Reviewer (v1.3.0)</span>
    <span class="code-var">$audit</span> = <span class="code-var">$connector</span>-&gt;<span class="code-func">getForensicAudit</span>(<span class="code-var">$reportId</span>);
    <span class="code-keyword">if</span> (<span class="code-var">$audit</span>) {
        <span class="code-func">echo</span> <span class="code-str">"Audit AI: "</span> . <span class="code-var">$audit</span>[<span class="code-str">'verdict'</span>] . <span class="code-str">" | Model: "</span> . <span class="code-var">$audit</span>[<span class="code-str">'model'</span>] . <span class="code-str">"\n"</span>;
        <span class="code-var">$questions</span> = <span class="code-var">$connector</span>-&gt;<span class="code-func">getEditorialQuestions</span>(<span class="code-var">$reportId</span>);
        <span class="code-comment">// Sisipkan $questions ke formulir Review Discussions OJS untuk Mitra Bestari</span>
    }

    <span class="code-comment">// 4. Unduh Sertifikat PDF Resmi untuk Disimpan di Arsip Editorial OJS</span>
    <span class="code-var">$pdfBytes</span> = <span class="code-func">file_get_contents</span>(<span class="code-var">$pdfExport</span>);
    <span class="code-keyword">if</span> (<span class="code-var">$pdfBytes</span>) {
        <span class="code-func">file_put_contents</span>(<span class="code-str">"/var/www/ojs/files/journals/1/articles/124/review/sertifikat_{$docId}.pdf"</span>, <span class="code-var">$pdfBytes</span>);
        <span class="code-func">echo</span> <span class="code-str">"[✓] Sertifikat PDF resmi telah diarsipkan di OJS!\n"</span>;
    }
} <span class="code-keyword">catch</span> (<span class="code-func">Exception</span> <span class="code-var">$e</span>) {
    <span class="code-func">echo</span> <span class="code-str">"[ERROR] Terjadi kendala: "</span> . <span class="code-var">$e</span>-&gt;<span class="code-func">getMessage</span>() . <span class="code-str">"\n"</span>;
}</div>

    <h3 class="sub-heading">Ringkasan Metode Helper Kelas ARTilikOJSConnector</h3>
    <table class="data-table avoid-break">
        <thead>
            <tr>
                <th style="width: 30%;">Metode PHP</th>
                <th style="width: 22%;">Parameter Utama</th>
                <th style="width: 48%;">Keterangan &amp; Fungsi</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><code style="color: #0284c7;">submitManuscript()</code></td>
                <td><code>$file, $title, $author, $id</code></td>
                <td>Mengunggah berkas naskah PDF/DOCX ke antrean mesin AR Tilik. Mengembalikan Document ID dan Report ID.</td>
            </tr>
            <tr>
                <td><code style="color: #0284c7;">checkStatus()</code></td>
                <td><code>$documentId</code></td>
                <td>Mengecek status terkini (pending &rarr; processing &rarr; completed) beserta skor kemiripan awal.</td>
            </tr>
            <tr>
                <td><code style="color: #0284c7;">getDetailedReport()</code></td>
                <td><code>$reportId</code></td>
                <td>Mengambil struktur JSON rincian sumber rujukan teratas, dekomposisi Dual-Lens, dan audit forensik.</td>
            </tr>
            <tr>
                <td><code style="color: #0284c7;">getForensicAudit()</code></td>
                <td><code>$reportId</code></td>
                <td>Mengambil hasil evaluasi semantik Google Gemini AI, verifikasi fabrikasi sitasi, dan rekomendasi audit.</td>
            </tr>
            <tr>
                <td><code style="color: #0284c7;">getEditorialQuestions()</code></td>
                <td><code>$reportId</code></td>
                <td>Mengambil 3 set pertanyaan telaah kritis terarah untuk form ulasan Mitra Bestari / Reviewer OJS.</td>
            </tr>
            <tr>
                <td><code style="color: #0284c7;">waitForCompletion()</code></td>
                <td><code>$docId, $maxWait, $pollInterval</code></td>
                <td>Fungsi polling otomatis praktis yang menunggu komputasi selesai tanpa memblokir server OJS.</td>
            </tr>
        </tbody>
    </table>
</div>

<!-- ============================================================== -->
<!-- HALAMAN 7: SPESIFIKASI LENGKAP REST API PUBLIK V1              -->
<!-- ============================================================== -->
<div class="page-break">
    <div class="section-header">
        <div>
            <span class="section-number">06</span>
            <span class="section-title">Spesifikasi Lengkap REST API Publik v1</span>
        </div>
        <div class="section-subtitle">Katalog Endpoint & Dokumentasi Teknis</div>
    </div>

    <p class="body-text">
        Jika Anda membangun plugin kustom dengan bahasa pemrograman selain PHP (misal Python, Node.js, atau Go), Anda dapat langsung berinteraksi dengan gateway REST API publik AR Tilik menggunakan spesifikasi standar berikut:
    </p>

    <div class="callout callout-dark avoid-break" style="padding: 5px 10px; margin-bottom: 7px;">
        <strong>Header Otentikasi Wajib:</strong> <code>X-API-Key: artilik_ojs_YOUR_SECRET_KEY</code> &nbsp;|&nbsp; <code>Accept: application/json</code>
    </div>

    <h3 class="sub-heading">Daftar Endpoint REST API v1</h3>
    <table class="data-table avoid-break">
        <thead>
            <tr>
                <th style="width: 12%;">Method</th>
                <th style="width: 28%;">Endpoint</th>
                <th style="width: 30%;">Parameter Kunci</th>
                <th style="width: 30%;">Respon Sukses</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><span class="tag-badge tag-blue">POST</span></td>
                <td><code>/api/v1/check</code></td>
                <td><code>document</code> (File), <code>title</code>, <code>author</code>, <code>ojs_submission_id</code></td>
                <td><code>202 Accepted</code> (ID Dokumen & URL status)</td>
            </tr>
            <tr>
                <td><span class="tag-badge tag-green">GET</span></td>
                <td><code>/api/v1/status/{id}</code></td>
                <td><code>id</code> = Document ID</td>
                <td><code>200 OK</code> (Status progres & skor kemiripan)</td>
            </tr>
            <tr>
                <td><span class="tag-badge tag-green">GET</span></td>
                <td><code>/api/v1/report/{id}</code></td>
                <td><code>id</code> = Report ID</td>
                <td><code>200 OK</code> (Rincian Dual-Lens & daftar sumber)</td>
            </tr>
            <tr>
                <td><span class="tag-badge tag-green">GET</span></td>
                <td><code>/api/v1/quota</code></td>
                <td>Tanpa parameter (baca kredensial)</td>
                <td><code>200 OK</code> (Sisa kuota & limit per menit)</td>
            </tr>
        </tbody>
    </table>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin: 6px 0;">
        <div class="code-block avoid-break" style="font-size: 6.1pt; line-height: 1.28; margin: 0;">
<div class="code-header">Request cURL Mandiri</div>
curl -X POST https://tilik.arsetontong.top/api/v1/check \\
  -H "X-API-Key: artilik_ojs_prod_a98f43c21d" \\
  -F "document=@/path/to/manuscript.pdf" \\
  -F "title=Analisis Komputasi Dual-Lens" \\
  -F "author=Dr. Budi Santoso" \\
  -F "ojs_submission_id=124"</div>

        <div class="code-block avoid-break" style="font-size: 6.1pt; line-height: 1.28; margin: 0;">
<div class="code-header">Respon JSON (HTTP 202 Accepted)</div>
{
  <span class="code-str">"status"</span>: <span class="code-str">"accepted"</span>,
  <span class="code-str">"message"</span>: <span class="code-str">"Naskah berhasil diterima."</span>,
  <span class="code-str">"data"</span>: {
    <span class="code-str">"document_id"</span>: <span class="code-keyword">3182</span>,
    <span class="code-str">"report_id"</span>: <span class="code-keyword">894</span>,
    <span class="code-str">"status_url"</span>: <span class="code-str">"https://.../status/3182"</span>,
    <span class="code-str">"estimated_wait_seconds"</span>: <span class="code-keyword">10</span>
  }
}</div>
    </div>

    <h3 class="sub-heading">Kode Status HTTP & Penanganan Kendala (Troubleshooting)</h3>
    <table class="data-table avoid-break">
        <thead>
            <tr>
                <th style="width: 15%;">Status Code</th>
                <th style="width: 30%;">Arti & Pesan</th>
                <th style="width: 55%;">Solusi & Tindakan Perbaikan</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><span class="tag-badge tag-green">200 / 202</span></td>
                <td>Success / Accepted</td>
                <td>Permintaan valid dan berhasil diproses oleh antrean AR Tilik.</td>
            </tr>
            <tr>
                <td><span class="tag-badge tag-red">401</span></td>
                <td>Unauthorized</td>
                <td>API Key tidak valid, salah ketik, atau belum disertakan pada header <code>X-API-Key</code>.</td>
            </tr>
            <tr>
                <td><span class="tag-badge tag-amber">429</span></td>
                <td>Quota Exceeded / Rate Limit</td>
                <td>Batas kuota bulanan habis, atau request melebihi 60 req/menit. Ajukan penambahan kuota ke Admin.</td>
            </tr>
            <tr>
                <td><span class="tag-badge tag-red">400</span></td>
                <td>Bad Request</td>
                <td>Berkas kosong, format tidak didukung (gunakan PDF, DOCX, TXT), atau berkas melebihi batas 25 MB.</td>
            </tr>
        </tbody>
    </table>
</div>

<!-- ============================================================== -->
<!-- HALAMAN 8: SERTIFIKAT DIGITAL, KEMITRAAN & DUKUNGAN TEKNIS     -->
<!-- ============================================================== -->
<div>
    <div class="section-header">
        <div>
            <span class="section-number">07</span>
            <span class="section-title">Sertifikat Digital & Dukungan Institusi</span>
        </div>
        <div class="section-subtitle">Bukti Keabsahan & Layanan Kampus</div>
    </div>

    <p class="body-text">
        Salah satu fitur paling berharga dari integrasi AR Tilik untuk pengelola jurnal adalah <strong>Sertifikat Keabsahan Digital Resmi</strong>. Berbeda dengan hasil pengecekan biasa, sertifikat ini dilengkapi tanda tangan kriptografis dan kode QR verifikasi publik yang tidak dapat dipalsukan.
    </p>

    <!-- MOCKUP SERTIFIKAT DIGITAL AR TILIK -->
    <div class="cert-card avoid-break">
        <div class="cert-card-header">
            <div style="display: flex; align-items: center; gap: 8px;">
                <img src="__LOGO_B64__" style="width: 26px; height: 26px; border-radius: 6px;" alt="Logo" />
                <div>
                    <div style="font-size: 8.2pt; font-weight: 800; color: #0f294a;">AR TILIK OFFICIAL CERTIFICATE OF ORIGINALITY</div>
                    <div style="font-size: 6.3pt; color: #64748b;">Sistem Verifikasi Integritas Akademik & Orisinalitas Ilmiah</div>
                </div>
            </div>
            <div class="cert-stamp">✓ VERIFIED AUTHENTIC</div>
        </div>

        <div style="display: flex; gap: 14px; align-items: center;">
            <div style="flex: 1;">
                <table style="width: 100%; font-size: 7.1pt; line-height: 1.38; border-collapse: collapse;">
                    <tr>
                        <td style="color: #64748b; width: 32%; padding: 2px 0;">Judul Naskah:</td>
                        <td style="font-weight: 700; color: #0f172a; padding: 2px 0;">Analisis Algoritma Dual-Lens pada Pengecekan Orisinalitas</td>
                    </tr>
                    <tr>
                        <td style="color: #64748b; padding: 2px 0;">Penulis:</td>
                        <td style="font-weight: 600; color: #1e293b; padding: 2px 0;">Dr. Budi Santoso, M.Kom. (Jurnal Informatika SINTA 2)</td>
                    </tr>
                    <tr>
                        <td style="color: #64748b; padding: 2px 0;">Nomor Sertifikat:</td>
                        <td style="font-family: monospace; font-weight: bold; color: #0284c7; padding: 2px 0;">ART-OJS3-8821-DEMO</td>
                    </tr>
                    <tr>
                        <td style="color: #64748b; padding: 2px 0;">Skor Kemiripan:</td>
                        <td style="padding: 2px 0;">
                            <span class="tag-badge tag-green" style="font-size: 6.8pt;">Total: 11.4% (LOLOS AMBANG BATAS)</span>
                            <span style="font-size: 6.3pt; color: #64748b; margin-left: 4px;">Exact: 2.1% | Cosine: 9.3%</span>
                        </td>
                    </tr>
                    <tr>
                        <td style="color: #64748b; padding: 2px 0;">Status Audit:</td>
                        <td style="color: #059669; font-weight: 700; padding: 2px 0;">Direkomendasikan untuk Terbit (Risiko Integritas Sangat Rendah - Sesuai Standar SINTA)</td>
                    </tr>
                </table>
            </div>
            <div style="text-align: center; border-left: 1px solid #e2e8f0; padding-left: 12px;">
                <img src="__QR_DEMO_B64__" style="width: 64px; height: 64px; display: block; margin: 0 auto 3px auto;" alt="QR Code" />
                <div style="font-size: 5.8pt; color: #64748b; font-weight: 600;">Scan untuk Verifikasi</div>
            </div>
        </div>
    </div>

    <h3 class="sub-heading">Promosi Paket Enterprise Kampus, OJS Jurnal & LPPM</h3>
    <div class="promo-grid avoid-break">
        <div class="promo-card" style="border-top-color: #059669;">
            <div class="promo-card-title" style="color: #059669;">
                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                Instalasi Mandiri (On-Premise / Private Cloud)
            </div>
            <div class="promo-card-desc">
                Bagi universitas dengan regulasi perlindungan data ketat, AR Tilik dapat dipasang langsung pada server data center kampus (bare-metal atau private cloud). Seluruh naskah tugas akhir dan jurnal tidak pernah meninggalkan server institusi Anda.
            </div>
        </div>
        <div class="promo-card" style="border-top-color: #2563eb;">
            <div class="promo-card-title" style="color: #2563eb;">
                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                Sinkronisasi Repositori Kampus (OAI-PMH)
            </div>
            <div class="promo-card-desc">
                Hubungkan koleksi repositori skripsi, tesis, dan disertasi kampus (EPrints, DSpace, atau SLIMS) secara otomatis. Mesin AR Tilik akan memanen dan mengindeks seluruh karya lokal sebagai basis pembanding eksklusif prodi Anda.
            </div>
        </div>
        <div class="promo-card" style="border-top-color: #7c3aed;">
            <div class="promo-card-title" style="color: #7c3aed;">
                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
                Pelatihan & Workshop Integritas Akademik
            </div>
            <div class="promo-card-desc">
                Kami menyediakan program pendampingan teknis dan workshop integritas penulisan ilmiah bersertifikat untuk para pengelola jurnal, dosen pembimbing, dan mahasiswa pascasarjana di lingkungan kampus Anda.
            </div>
        </div>
        <div class="promo-card" style="border-top-color: #0284c7;">
            <div class="promo-card-title" style="color: #0284c7;">
                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                Dukungan Teknis Dedicated (SLA 99.9%)
            </div>
            <div class="promo-card-desc">
                Akses langsung ke tim teknis AR Tilik melalui saluran WhatsApp khusus dan bantuan konfigurasi instalasi OJS secara gratis tanpa biaya konsultasi tambahan.
            </div>
        </div>
    </div>

    <!-- CALL TO ACTION & KONTAK -->
    <div class="callout callout-dark avoid-break" style="margin-top: 12px; padding: 11px 13px; text-align: center;">
        <div style="font-size: 9.5pt; font-weight: 800; color: #38bdf8; margin-bottom: 3px;">
            Siap Mengautomasi Jurnal Ilmiah Kampus Anda dengan AR Tilik?
        </div>
        <div style="font-size: 7.5pt; color: #cbd5e1; max-width: 90%; margin: 0 auto 9px auto;">
            Hubungi tim kemitraan kami hari ini untuk mendapatkan sesi demo langsung, pembuatan kunci integrasi percobaan, atau uji kelayakan pada server OJS kampus Anda.
        </div>
        <div style="display: flex; justify-content: center; gap: 20px; font-size: 7.3pt; font-weight: 600;">
            <span style="color: #ffffff;">🌐 Portal: <a href="https://tilik.arsetontong.top" style="color: #38bdf8; text-decoration: none;">https://tilik.arsetontong.top</a></span>
            <span style="color: #ffffff;">✉️ Email: <a href="mailto:support@tilik.arsetontong.top" style="color: #38bdf8; text-decoration: none;">support@tilik.arsetontong.top</a></span>
            <span style="color: #ffffff;">💬 Bantuan: Tim Engineering AR Tilik Indonesia</span>
        </div>
    </div>
</div>

</body>
</html>"""

    html = template.replace("__LOGO_B64__", logo_b64)
    html = html.replace("__ADMIN_SCREEN_B64__", admin_screen_b64)
    html = html.replace("__DUAL_LENS_CONCEPT_B64__", dual_lens_concept_b64)
    html = html.replace("__DUAL_READER_MOCKUP_B64__", dual_reader_mockup_b64)
    html = html.replace("__QR_DEMO_B64__", qr_demo_b64)
    return html

def generate_pdf():
    """Mengkompilasi berkas HTML ke PDF menggunakan WeasyPrint."""
    print("[*] Menyusun konten HTML Dokumentasi OJS...")
    html_content = build_html()

    with tempfile.NamedTemporaryFile("w", suffix=".html", delete=False, encoding="utf-8") as f_html:
        f_html.write(html_content)
        temp_html_path = f_html.name

    try:
        print("[*] Merender berkas PDF menggunakan WeasyPrint...")
        weasy_bin = shutil.which("weasyprint") or "/usr/bin/weasyprint"
        
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
        print(f"[✓] Berkas PDF Dokumentasi OJS berhasil dibuat!")
        print(f"    Lokasi: {OUTPUT_PDF_PATH}")
        print(f"    Ukuran: {file_size:,} byte ({file_size / (1024*1024):.2f} MB)")

    finally:
        if os.path.exists(temp_html_path):
            os.remove(temp_html_path)

if __name__ == "__main__":
    generate_pdf()
