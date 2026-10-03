import argparse
import hashlib
import json
import os
import subprocess
import sys
import tempfile
from datetime import datetime
from pathlib import Path

# Ensure root directory is on PYTHONPATH
sys.path.append(str(Path(__file__).resolve().parents[1]))

from worker.database import get_connection

def generate_verification_hash(report_id: int, doc_id: int, created_at: str, sim: float) -> str:
    seed = f"ARTILIK:{report_id}:{doc_id}:{created_at}:{sim:.2f}:OFFICIAL_CERTIFICATE"
    h = hashlib.sha256(seed.encode("utf-8")).hexdigest().upper()
    return f"ART-{h[:4]}-{h[4:8]}-{h[8:12]}"

def build_report_html(report: dict, document: dict, sources: list[dict], matches: list[dict]) -> str:
    r_id = report["id"]
    d_id = report["document_id"]
    engine = (report["engine"] or "hybrid").upper()
    dec = (report["predicted_decision"] or "review").lower()
    pattern = report["evidence_pattern"] or "SEMANTIC_PARAPHRASE"
    agreement = report["evidence_agreement"] or "COMPLEMENTARY_EVIDENCE"
    sources_agree = bool(report["sources_agree"])
    
    overall_sim = float(report["overall_similarity"] or 0)
    exact_sim = float(report["exact_similarity"] or 0)
    cosine_sim = float(report["cosine_similarity"] or 0)
    longest_match = report["longest_match_words"] or 0
    shared_terms = report["shared_terms_count"] or 0
    avg_idf = float(report["average_shared_term_idf"] or 0)
    rationale = report["rationale"] or "Analisis dekomposisi bukti selesai."

    doc_title = document.get("title") or "Dokumen Karya Ilmiah"
    filename = document.get("original_filename") or f"document_{d_id}.pdf"
    
    # Parse created_at
    created_at_dt = report.get("created_at")
    if isinstance(created_at_dt, datetime):
        date_str = created_at_dt.strftime("%d %B %Y, %H:%M WIB")
        raw_created = str(created_at_dt)
    elif created_at_dt:
        date_str = str(created_at_dt)
        raw_created = str(created_at_dt)
    else:
        date_str = datetime.now().strftime("%d %B %Y, %H:%M WIB")
        raw_created = date_str

    cert_hash = generate_verification_hash(r_id, d_id, raw_created, overall_sim)
    
    # Evidence data JSON
    evidence_data = {}
    if report.get("evidence_data"):
        try:
            evidence_data = json.loads(report["evidence_data"]) if isinstance(report["evidence_data"], str) else report["evidence_data"]
        except Exception:
            evidence_data = {}

    total_words = evidence_data.get("total_words", 0)
    exec_time = evidence_data.get("execution_time_seconds", 0)

    # Decision styling
    if dec == "match":
        dec_label = "MATCH (Kesamaan Harfiah Kuat)"
        dec_desc = "Terdeteksi pasase identik verbatim melebihi ambang batas kesamaan sekuensial institusi."
        dec_color = "#b91c1c"
        dec_bg = "#fef2f2"
        dec_border = "#f87171"
    elif dec == "review":
        dec_label = "REVIEW (Perlu Tinjauan Akademik)"
        dec_desc = "Terdeteksi bukti kontekstual: kutipan, frasa metodologi umum, atau parafrasa leksikal yang memerlukan verifikasi pembimbing/komite penguji."
        dec_color = "#b45309"
        dec_bg = "#fffbeb"
        dec_border = "#fbbf24"
    else:
        dec_label = "NO MATCH (Tidak Ada Bukti Tekstual)"
        dec_desc = "Tidak ditemukan bukti kesamaan teks sekuensial maupun orientasi leksikal yang signifikan."
        dec_color = "#047857"
        dec_bg = "#ecfdf5"
        dec_border = "#34d399"

    # Pattern description
    pattern_desc_map = {
        "VERBATIM_MATCH": "Pasase teks kata-demi-kata identik berurutan terdeteksi.",
        "COPY_EDIT": "Teks memiliki struktur kalimat sangat dekat dengan sedikit variasi kata sinonim.",
        "SEMANTIC_PARAPHRASE": "Parafrasa topikal leksikal kuat tanpa pasase sekuensial panjang (orisinalitas susunan kata terjaga).",
        "COMMON_ACADEMIC_PHRASE": "Kesamaan bersumber dari frasa baku metodologi atau terminologi formal keilmuan.",
        "PARTIAL_DIRECT_MATCH": "Sebagian pasase cocok secara langsung pada frasa spesifik tertentu.",
        "NO_EVIDENCE": "Tidak ditemukan bukti tekstual yang beririsan dengan korpus rujukan."
    }
    pattern_desc = pattern_desc_map.get(pattern, "Pola bukti teridentifikasi secara objektif.")

    # Sources rows
    sources_html = ""
    for s in sources[:10]:
        s_rank = s.get("source_rank", 1)
        s_title = s.get("source_title") or s.get("title") or f"Rujukan #{s.get('source_document_id')}"
        s_sim = float(s.get("similarity") or 0)
        s_exact = float(s.get("exact_similarity") or 0)
        s_cosine = float(s.get("cosine_similarity") or 0)
        s_type = "Korpus Institusi" if s.get("source_type") == "corpus" else "Naskah Terpublikasi"

        sources_html += f"""
        <tr>
            <td style="text-align: center; font-weight: bold; width: 40px;">#{s_rank}</td>
            <td>
                <strong>{s_title}</strong>
                <div style="font-size: 8pt; color: #64748b;">ID #{s.get('source_document_id')} &bull; {s_type}</div>
            </td>
            <td style="text-align: right; font-weight: bold; width: 75px; color: #1e3a8a;">{s_sim:.1f}%</td>
            <td style="text-align: right; width: 65px; color: #475569;">{s_exact:.1f}%</td>
            <td style="text-align: right; width: 65px; color: #475569;">{s_cosine:.1f}%</td>
        </tr>
        """

    if not sources:
        sources_html = """
        <tr>
            <td colspan="5" style="text-align: center; color: #64748b; font-style: italic; padding: 16px;">
                Tidak ada dokumen rujukan yang menunjukkan kesamaan signifikan.
            </td>
        </tr>
        """

    # Matches rows
    matches_html = ""
    for idx, m in enumerate(matches[:8], 1):
        w_cnt = m.get("word_count", 0)
        m_type = (m.get("match_type") or "exact").upper()
        s_sub = m.get("submission_snippet", "")
        s_src = m.get("source_snippet", "")
        src_id = m.get("source_document_id", "-")
        sub_span = f"Kata {m.get('submission_start', 0)} &ndash; {m.get('submission_end', 0)}"

        matches_html += f"""
        <div class="match-item" style="margin-bottom: 12px; padding: 10px; border: 1px solid #e2e8f0; border-radius: 6px; background-color: #f8fafc; page-break-inside: avoid;">
            <div style="display: flex; justify-content: space-between; font-size: 8.5pt; margin-bottom: 6px; border-bottom: 1px solid #cbd5e1; padding-bottom: 4px;">
                <span><strong>Pasase #{idx}</strong> &bull; {sub_span} ({w_cnt} kata)</span>
                <span>Rujukan: <strong>Doc #{src_id}</strong> &bull; Tipe: <strong>{m_type}</strong></span>
            </div>
            <div style="font-size: 8pt; line-height: 1.4; display: grid; grid-template-columns: 1fr 1fr; gap: 8px;">
                <div style="background: #ffffff; padding: 6px; border: 1px solid #e2e8f0; border-radius: 4px;">
                    <div style="font-weight: bold; color: #1e3a8a; font-size: 7.5pt; margin-bottom: 3px; text-transform: uppercase;">Cuplikan Naskah Submisi</div>
                    <div style="color: #334155; font-style: italic;">&ldquo;{s_sub[:280] + ('...' if len(s_sub) > 280 else '')}&rdquo;</div>
                </div>
                <div style="background: #ffffff; padding: 6px; border: 1px solid #e2e8f0; border-radius: 4px;">
                    <div style="font-weight: bold; color: #475569; font-size: 7.5pt; margin-bottom: 3px; text-transform: uppercase;">Cuplikan Dokumen Rujukan</div>
                    <div style="color: #334155; font-style: italic;">&ldquo;{s_src[:280] + ('...' if len(s_src) > 280 else '')}&rdquo;</div>
                </div>
            </div>
        </div>
        """

    if not matches:
        matches_html = """
        <div style="text-align: center; color: #64748b; font-style: italic; padding: 16px; border: 1px dashed #cbd5e1; border-radius: 6px;">
            Tidak ditemukan pasase kata berurutan yang melebihi batas deteksi sekuensial.
        </div>
        """

    html = f"""<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>Laporan Bukti Kemiripan #{r_id} — AR Tilik</title>
<style>
    @page {{
        size: A4;
        margin: 15mm 15mm 18mm 15mm;
        @top-right {{
            content: "AR Tilik v1.0.0 &bull; Sertifikasi #{cert_hash}";
            font-size: 7pt;
            font-family: 'Liberation Sans', 'Helvetica Neue', Arial, sans-serif;
            color: #94a3b8;
        }}
        @bottom-left {{
            content: "Dicetak secara otomatis oleh Sistem AR Tilik &bull; Keabsahan digital terverifikasi";
            font-size: 7pt;
            font-family: 'Liberation Sans', 'Helvetica Neue', Arial, sans-serif;
            color: #94a3b8;
        }}
        @bottom-right {{
            content: "Halaman " counter(page) " dari " counter(pages);
            font-size: 7pt;
            font-family: 'Liberation Sans', 'Helvetica Neue', Arial, sans-serif;
            color: #94a3b8;
            font-weight: bold;
        }}
    }}

    body {{
        font-family: 'Liberation Sans', 'Helvetica Neue', Arial, sans-serif;
        color: #1e293b;
        background: #ffffff;
        margin: 0;
        padding: 0;
        font-size: 9pt;
        line-height: 1.45;
    }}

    /* Header & Kop Surat */
    .header-table {{
        width: 100%;
        border-bottom: 2.5px solid #0f294a;
        padding-bottom: 10px;
        margin-bottom: 14px;
    }}
    .brand-title {{
        font-size: 16pt;
        font-weight: 800;
        color: #0f294a;
        letter-spacing: -0.5px;
        margin: 0;
    }}
    .brand-sub {{
        font-size: 8pt;
        color: #64748b;
        margin-top: 2px;
        text-transform: uppercase;
        letter-spacing: 0.8px;
    }}
    .cert-box {{
        text-align: right;
        font-size: 8pt;
    }}
    .cert-code {{
        display: inline-block;
        font-family: 'Liberation Mono', monospace;
        font-weight: bold;
        background: #f1f5f9;
        color: #0f294a;
        padding: 4px 8px;
        border-radius: 4px;
        border: 1px solid #cbd5e1;
        letter-spacing: 1px;
    }}

    .report-title-box {{
        text-align: center;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 6px;
        padding: 10px;
        margin-bottom: 14px;
    }}
    .report-title {{
        font-size: 12pt;
        font-weight: 800;
        color: #0f294a;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin: 0;
    }}
    .report-subtitle {{
        font-size: 8pt;
        color: #475569;
        margin-top: 3px;
    }}

    /* Metadata Table */
    .meta-table {{
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 14px;
        font-size: 8.5pt;
    }}
    .meta-table td {{
        padding: 4px 8px;
        vertical-align: top;
        border-bottom: 1px solid #f1f5f9;
    }}
    .meta-label {{
        width: 130px;
        color: #64748b;
        font-weight: 600;
    }}
    .meta-val {{
        color: #0f294a;
    }}

    /* Decision Banner */
    .decision-banner {{
        border: 1.5px solid {dec_border};
        background: {dec_bg};
        border-radius: 8px;
        padding: 12px 16px;
        margin-bottom: 14px;
        page-break-inside: avoid;
    }}
    .decision-header {{
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 6px;
    }}
    .decision-title {{
        font-size: 12pt;
        font-weight: 800;
        color: {dec_color};
        text-transform: uppercase;
    }}
    .decision-score {{
        font-size: 18pt;
        font-weight: 900;
        color: {dec_color};
    }}
    .decision-desc {{
        font-size: 8.5pt;
        color: #334155;
        line-height: 1.4;
    }}
    .tag-pill {{
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
    }}

    /* Dual-Lens Cards */
    .dual-lens-grid {{
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 12px;
        margin-bottom: 14px;
        page-break-inside: avoid;
    }}
    .lens-card {{
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        background: #ffffff;
        padding: 10px 12px;
    }}
    .lens-card-header {{
        border-bottom: 1.5px solid #0f294a;
        padding-bottom: 4px;
        margin-bottom: 8px;
        display: flex;
        justify-content: space-between;
        align-items: baseline;
    }}
    .lens-card-title {{
        font-size: 9.5pt;
        font-weight: bold;
        color: #0f294a;
        text-transform: uppercase;
    }}
    .lens-card-sub {{
        font-size: 7.5pt;
        color: #64748b;
    }}
    .metric-row {{
        display: flex;
        justify-content: space-between;
        padding: 3px 0;
        border-bottom: 1px dotted #e2e8f0;
        font-size: 8.5pt;
    }}
    .metric-lbl {{ color: #475569; }}
    .metric-val {{ font-weight: bold; color: #0f294a; }}

    /* Rationale & Academic Fairness Box */
    .rationale-box {{
        background: #f8fafc;
        border-left: 3.5px solid #1e3a8a;
        padding: 10px 14px;
        margin-bottom: 14px;
        border-radius: 0 6px 6px 0;
        page-break-inside: avoid;
    }}
    .rationale-title {{
        font-size: 9pt;
        font-weight: bold;
        color: #0f294a;
        text-transform: uppercase;
        margin-bottom: 4px;
        letter-spacing: 0.5px;
    }}
    .rationale-quote {{
        font-size: 8.5pt;
        font-style: italic;
        color: #1e293b;
        line-height: 1.45;
        margin-bottom: 6px;
    }}
    .fairness-note {{
        font-size: 7.5pt;
        color: #475569;
        border-top: 1px solid #e2e8f0;
        padding-top: 5px;
        margin-top: 5px;
        line-height: 1.35;
    }}

    /* Data Tables */
    .data-table {{
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 14px;
        font-size: 8.5pt;
    }}
    .data-table th {{
        background: #0f294a;
        color: #ffffff;
        padding: 6px 8px;
        font-size: 8pt;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        border: 1px solid #0f294a;
    }}
    .data-table td {{
        padding: 5px 8px;
        border: 1px solid #e2e8f0;
        vertical-align: middle;
    }}
    .data-table tr:nth-child(even) td {{
        background: #f8fafc;
    }}

    /* Signature Section */
    .signature-grid {{
        display: grid;
        grid-template-columns: 1fr 1fr 1fr;
        gap: 15px;
        margin-top: 24px;
        page-break-inside: avoid;
    }}
    .sig-box {{
        text-align: center;
        font-size: 8pt;
        color: #334155;
    }}
    .sig-role {{
        font-weight: bold;
        margin-bottom: 50px;
        color: #0f294a;
    }}
    .sig-line {{
        border-top: 1px solid #475569;
        margin: 0 10px;
        padding-top: 4px;
    }}

    h3.section-header {{
        font-size: 10pt;
        font-weight: bold;
        color: #0f294a;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin: 14px 0 8px 0;
        border-bottom: 1.5px solid #e2e8f0;
        padding-bottom: 4px;
    }}
</style>
</head>
<body>

    <!-- Header / Kop -->
    <table class="header-table">
        <tr>
            <td style="vertical-align: middle;">
                <div class="brand-title">AR TILIK</div>
                <div class="brand-sub">Academic Writing Similarity &bull; Evidence Decomposition Engine</div>
            </td>
            <td class="cert-box" style="vertical-align: middle;">
                <div style="color: #64748b; font-size: 7.5pt; margin-bottom: 2px;">NOMOR SERTIFIKAT VERIFIKASI</div>
                <div class="cert-code">{cert_hash}</div>
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
            <td class="meta-val" colspan="3"><strong>{doc_title}</strong></td>
        </tr>
        <tr>
            <td class="meta-label">Nama Berkas</td>
            <td class="meta-val">{filename}</td>
            <td class="meta-label">ID Laporan / Submisi</td>
            <td class="meta-val">Report #{r_id} / Doc #{d_id}</td>
        </tr>
        <tr>
            <td class="meta-label">Waktu Pemeriksaan</td>
            <td class="meta-val">{date_str}</td>
            <td class="meta-label">Total Volume</td>
            <td class="meta-val">{total_words:,} kata</td>
        </tr>
        <tr>
            <td class="meta-label">Arsitektur Mesin</td>
            <td class="meta-val">AR Tilik Dual-Lens Hybrid v1.0.0 (Frozen)</td>
            <td class="meta-label">Durasi Eksekusi</td>
            <td class="meta-val">{exec_time:.2f} detik</td>
        </tr>
    </table>

    <!-- Decision Banner -->
    <div class="decision-banner">
        <div class="decision-header">
            <div>
                <div class="decision-title">{dec_label}</div>
                <div class="decision-desc">{dec_desc}</div>
            </div>
            <div class="decision-score">{overall_sim:.2f}%</div>
        </div>
        <div>
            <span class="tag-pill">Pola Bukti: <strong>{pattern}</strong></span>
            <span class="tag-pill">Sintesis: <strong>{agreement}</strong></span>
            <span class="tag-pill">Kesepakatan Lensa: <strong>{'YA (Sepakat)' if sources_agree else 'TIDAK / TUNGGAL'}</strong></span>
        </div>
    </div>

    <!-- Dual-Lens Quantitative Decomposition -->
    <div class="dual-lens-grid">
        <div class="lens-card">
            <div class="lens-card-header">
                <span class="lens-card-title">Lensa 1 &bull; Exact Matcher</span>
                <span class="lens-card-sub">5-gram Sequential Shingling</span>
            </div>
            <div class="metric-row">
                <span class="metric-lbl">Kesamaan Sekuensial (Exact Sim)</span>
                <span class="metric-val">{exact_sim:.2f}%</span>
            </div>
            <div class="metric-row">
                <span class="metric-lbl">Pasase Cocok Terpanjang</span>
                <span class="metric-val">{longest_match} kata</span>
            </div>
            <div class="metric-row">
                <span class="metric-lbl">Pola Struktur Sekuensial</span>
                <span class="metric-val">{'Panjang' if longest_match >= 50 else ('Sedang' if longest_match >= 10 else 'Mikro/Nol')}</span>
            </div>
        </div>

        <div class="lens-card">
            <div class="lens-card-header">
                <span class="lens-card-title">Lensa 2 &bull; TF-IDF Vectorizer</span>
                <span class="lens-card-sub">Sparse Cosine Vector Space</span>
            </div>
            <div class="metric-row">
                <span class="metric-lbl">Kemiripan Topikal (Cosine Sim)</span>
                <span class="metric-val">{cosine_sim:.2f}%</span>
            </div>
            <div class="metric-row">
                <span class="metric-lbl">Istilah Beririsan (Shared Terms)</span>
                <span class="metric-val">{shared_terms} istilah</span>
            </div>
            <div class="metric-row">
                <span class="metric-lbl">Rata-rata Bobot Rujukan (Avg IDF)</span>
                <span class="metric-val">{avg_idf:.4f}</span>
            </div>
        </div>
    </div>

    <!-- Rationale & Fairness Box -->
    <div class="rationale-box">
        <div class="rationale-title">Catatan Ilmiah & Evaluasi Sistem (Explainability Rationale)</div>
        <div class="rationale-quote">&ldquo;{rationale}&rdquo;</div>
        <div class="fairness-note">
            <strong>Prinsip Keadilan Akademik (Academic Fairness Disclaimer):</strong>
            AR Tilik menyajikan bukti tekstual secara matematis dan transparan. Kemiripan leksikal tidak secara otomatis mengindikasikan pelanggaran integritas akademik. Kutipan teks sah, terminologi metodologi terstandarisasi, dan daftar referensi dapat meningkatkan persentase kesamaan. Keputusan substantif mutlak berada di bawah kewenangan Dosen Pembimbing dan Komite Etik Akademik.
        </div>
    </div>

    <!-- Sources Table -->
    <h3 class="section-header">Dokumen Rujukan Teratas Terdeteksi</h3>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 35px; text-align: center;">Peringkat</th>
                <th style="text-align: left;">Judul Dokumen Rujukan</th>
                <th style="width: 75px; text-align: right;">Skor Gabungan</th>
                <th style="width: 65px; text-align: right;">Exact</th>
                <th style="width: 65px; text-align: right;">Kosinus</th>
            </tr>
        </thead>
        <tbody>
            {sources_html}
        </tbody>
    </table>

    <!-- Matching Passages -->
    <h3 class="section-header">Cuplikan Pasase Beririsan (Sampel Kontekstual)</h3>
    {matches_html}

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
"""
    return html

def export_report_pdf(report_id: int, output_path: str | None = None) -> bytes:
    conn = get_connection()
    try:
        cursor = conn.cursor(dictionary=True)
        # 1. Fetch Report
        cursor.execute("SELECT * FROM reports WHERE id = %s", (report_id,))
        report = cursor.fetchone()
        if not report:
            raise ValueError(f"Laporan ID #{report_id} tidak ditemukan.")

        doc_id = report["document_id"]
        # 2. Fetch Document
        cursor.execute("SELECT * FROM documents WHERE id = %s", (doc_id,))
        document = cursor.fetchone() or {}

        # 3. Fetch Sources
        cursor.execute("""
            SELECT rs.*, d.title AS source_title, d.original_filename AS source_filename, d.source_type
            FROM report_sources rs
            LEFT JOIN documents d ON d.id = rs.source_document_id
            WHERE rs.report_id = %s
            ORDER BY rs.source_rank ASC
        """, (report_id,))
        sources = cursor.fetchall()

        # 4. Fetch Matches
        cursor.execute("""
            SELECT rm.*, d.title AS source_title, d.original_filename AS source_filename
            FROM report_matches rm
            LEFT JOIN documents d ON d.id = rm.source_document_id
            WHERE rm.report_id = %s
            ORDER BY rm.word_count DESC, rm.submission_start ASC
            LIMIT 10
        """, (report_id,))
        matches = cursor.fetchall()

        # 5. Extract snippets using precise start_word_position
        def fetch_snippet(document_id: int, start_word: int, word_count: int) -> str:
            cursor.execute("""
                SELECT text, start_word_position, end_word_position
                FROM document_chunks
                WHERE document_id = %s AND start_word_position <= %s
                ORDER BY start_word_position DESC LIMIT 1
            """, (document_id, start_word))
            chunk = cursor.fetchone()
            if not chunk or not chunk["text"]:
                return "Teks tidak tersedia."
            words = chunk["text"].split()
            offset = max(0, start_word - chunk["start_word_position"])
            count = max(1, word_count)
            snippet = " ".join(words[offset:offset + count])
            return snippet if snippet else "Teks tidak tersedia."

        for m in matches:
            m["submission_snippet"] = fetch_snippet(doc_id, int(m["submission_start"]), int(m["word_count"]))
            m["source_snippet"] = fetch_snippet(int(m["source_document_id"]), int(m["source_start"]), int(m["word_count"]))

    finally:
        conn.close()

    html_content = build_report_html(report, document, sources, matches)

    # Compile PDF using WeasyPrint
    with tempfile.NamedTemporaryFile("w", suffix=".html", delete=False, encoding="utf-8") as f_html:
        f_html.write(html_content)
        html_file = f_html.name

    pdf_temp = tempfile.NamedTemporaryFile("wb", suffix=".pdf", delete=False)
    pdf_temp.close()
    pdf_file = pdf_temp.name

    try:
        # Try WeasyPrint first
        cmd = ["weasyprint", html_file, pdf_file]
        res = subprocess.run(cmd, capture_output=True, text=True)
        if res.returncode != 0:
            # Fallback to Google Chrome headless
            chrome_cmd = [
                "google-chrome",
                "--headless",
                "--disable-gpu",
                "--no-sandbox",
                f"--print-to-pdf={pdf_file}",
                html_file
            ]
            subprocess.run(chrome_cmd, capture_output=True, check=True)

        with open(pdf_file, "rb") as f_pdf:
            pdf_bytes = f_pdf.read()

        if output_path:
            with open(output_path, "wb") as f_out:
                f_out.write(pdf_bytes)

        return pdf_bytes
    finally:
        if os.path.exists(html_file):
            os.remove(html_file)
        if os.path.exists(pdf_file):
            os.remove(pdf_file)

if __name__ == "__main__":
    parser = argparse.ArgumentParser(description="AR Tilik PDF Report Exporter")
    parser.add_argument("--report", type=int, required=True, help="ID Laporan yang akan diekspor")
    parser.add_argument("--output", type=str, default=None, help="Lokasi berkas luaran PDF")
    parser.add_argument("--stdout", action="store_true", help="Keluarkan byte PDF langsung ke stdout")
    args = parser.parse_args()

    try:
        pdf_data = export_report_pdf(args.report, args.output)
        if args.stdout:
            sys.stdout.buffer.write(pdf_data)
        elif args.output:
            print(f"[✓] Berkas PDF laporan #{args.report} berhasil disimpan ke: {args.output} ({len(pdf_data):,} byte)")
        else:
            default_out = f"Laporan_AR_Tilik_{args.report}.pdf"
            with open(default_out, "wb") as f:
                f.write(pdf_data)
            print(f"[✓] Berkas PDF laporan #{args.report} berhasil disimpan ke: {default_out} ({len(pdf_data):,} byte)")
    except Exception as e:
        print(f"[!] Gagal mengekspor PDF: {e}", file=sys.stderr)
        sys.exit(1)
