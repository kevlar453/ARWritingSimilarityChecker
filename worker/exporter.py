import argparse
import hashlib
import json
import os
import re
import shutil
import subprocess
import sys
import tempfile
from datetime import datetime
from pathlib import Path

import qrcode
import base64
import io
import secrets
from html import escape as html_escape

# Ensure root directory is on PYTHONPATH and prevent script dir from shadowing worker package
PROJECT_ROOT = Path(__file__).resolve().parents[1]
script_dir = str(Path(__file__).resolve().parent)
if script_dir in sys.path:
    sys.path.remove(script_dir)
if str(PROJECT_ROOT) not in sys.path:
    sys.path.insert(0, str(PROJECT_ROOT))

from worker.database import get_connection

VERIFY_BASE_URL = os.getenv("APP_PUBLIC_URL", "https://tilik.arsetontong.top")

def generate_qr_code_base64(url: str) -> str:
    try:
        qr = qrcode.QRCode(
            version=1,
            error_correction=qrcode.constants.ERROR_CORRECT_M,
            box_size=4,
            border=1
        )
        qr.add_data(url)
        qr.make(fit=True)
        img = qr.make_image(fill_color="#0f294a", back_color="white")
        buf = io.BytesIO()
        img.save(buf, format="PNG")
        return base64.b64encode(buf.getvalue()).decode("utf-8")
    except Exception as e:
        print(f"[EXPORTER WARNING] Gagal menghasilkan QR code: {e}")
        return ""

def generate_verification_code() -> str:
    rnd = secrets.token_hex(6).upper()
    return f"ART-{rnd[:4]}-{rnd[4:8]}-{rnd[8:12]}"

def generate_verification_hash(report_id: int, doc_id: int, created_at: str, sim: float) -> str:
    seed = f"ARTILIK:{report_id}:{doc_id}:{created_at}:{float(sim):.2f}:OFFICIAL_CERTIFICATE"
    h = hashlib.sha256(seed.encode("utf-8")).hexdigest().upper()[:12]
    return f"ART-{h[:4]}-{h[4:8]}-{h[8:12]}"

def format_rentang_html(tiers: list[dict]) -> str:
    """
    Menghasilkan HTML indikator rentang nilai dengan pewarnaan semantik
    (Hijau: Rendah/Prima, Oranye/Kuning: Menengah, Merah: Tinggi/Kritis)
    dan penyorotan (badge) pada rentang aktif yang sesuai dengan skor saat ini.
    """
    pills = []
    for t in tiers:
        lbl = t["label"]
        color = t["color"]
        bg = t.get("bg", "#f8fafc")
        border = t.get("border", "#cbd5e1")
        is_active = t.get("active", False)
        if is_active:
            pills.append(
                f'<span style="display: inline-block; white-space: nowrap; color: {color}; '
                f'background: {bg}; border: 1px solid {border}; border-radius: 3px; '
                f'padding: 0.5px 3.5px; font-weight: bold;">{lbl}</span>'
            )
        else:
            pills.append(
                f'<span style="display: inline-block; white-space: nowrap; color: {color}; opacity: 0.82;">{lbl}</span>'
            )
    sep = '<span style="color: #94a3b8; margin: 0 1px;">&bull;</span>'
    return (
        f'<div style="font-size: 6.5pt; font-weight: normal; margin-top: 3px; line-height: 1.45;">'
        f'<span style="color: #64748b;">Rentang:</span> ' + f" {sep} ".join(pills) + "</div>"
    )

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
    faculty = document.get("faculty")
    department = document.get("department")
    fac_dept_parts = [str(p).strip() for p in [faculty, department] if p and str(p).strip()]
    fac_dept_str = " / ".join(fac_dept_parts)
    fac_dept_row = f"""
        <tr>
            <td class="meta-label">Fakultas / Prodi</td>
            <td class="meta-val" colspan="3">{fac_dept_str}</td>
        </tr>
    """ if fac_dept_str else ""
    
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

    cert_hash = report.get("verification_code") or generate_verification_hash(r_id, d_id, raw_created, overall_sim)
    
    # Evidence data JSON
    evidence_data = {}
    if report.get("evidence_data"):
        try:
            evidence_data = json.loads(report["evidence_data"]) if isinstance(report["evidence_data"], str) else report["evidence_data"]
        except Exception:
            evidence_data = {}

    total_words = evidence_data.get("total_words", 0)
    exec_time = evidence_data.get("execution_time_seconds", 0)
    corpus_docs_count = int(evidence_data.get("corpus_docs_count") or evidence_data.get("substantive_summary", {}).get("corpus_docs_count") or 0)
    if corpus_docs_count == 0:
        try:
            from worker.database import get_connection
            from worker.matcher import get_corpus_document_count
            _conn = get_connection()
            corpus_docs_count = get_corpus_document_count(_conn)
            _conn.close()
        except Exception:
            pass
    sub_summary = evidence_data.get("substantive_summary") or {}
    sub_sim_val = sub_summary.get("substantive_similarity")
    sub_longest_val = sub_summary.get("substantive_longest_words")

    # Ekstraksi modul evolusi v1.2
    v11_pres = evidence_data.get("v1_1_presentation", {})
    v12_evol = v11_pres.get("v1_2_evolution", {}) if isinstance(v11_pres, dict) else {}
    sec_seg = v12_evol.get("section_segmentation")
    term_drill = v12_evol.get("term_drilldown")

    # Auto-heal section_segmentation jika kosong, anomali zonasi (BODY_TEXT == 0 pada teks > 100 kata), atau word count tidak konsisten
    cnt_dist_check = sec_seg.get("zone_word_distribution", {}) if isinstance(sec_seg, dict) else {}
    pct_dist_check = sec_seg.get("zone_percentage_distribution", {}) if isinstance(sec_seg, dict) else {}
    p_body_check = pct_dist_check.get("BODY_TEXT", 0.0)
    w_sum_check = sum(cnt_dist_check.values()) if cnt_dist_check else 0
    sec_words_check = sec_seg.get("total_words", 0) if isinstance(sec_seg, dict) else 0

    if not sec_seg or (total_words > 100 and p_body_check == 0.0) or (total_words > 0 and (sec_words_check != total_words or w_sum_check != total_words)):
        try:
            from worker.section_parser import heal_report_section_segmentation
            healed = heal_report_section_segmentation(r_id)
            if healed and healed.get("success"):
                if healed.get("total_words"):
                    total_words = healed["total_words"]
                try:
                    from worker.database import get_connection
                    _conn = get_connection()
                    with _conn.cursor(dictionary=True) as _cur:
                        _cur.execute("SELECT evidence_data FROM reports WHERE id = %s", (r_id,))
                        _refreshed_row = _cur.fetchone()
                        if _refreshed_row and _refreshed_row.get("evidence_data"):
                            evidence_data = json.loads(_refreshed_row["evidence_data"]) if isinstance(_refreshed_row["evidence_data"], str) else _refreshed_row["evidence_data"]
                            total_words = evidence_data.get("total_words", total_words)
                            v11_pres = evidence_data.get("v1_1_presentation", {})
                            v12_evol = v11_pres.get("v1_2_evolution", {}) if isinstance(v11_pres, dict) else {}
                            sec_seg = v12_evol.get("section_segmentation")
                    _conn.close()
                except Exception:
                    pass
        except Exception:
            pass

    section_seg_html = ""
    if sec_seg:
        pct_dist = sec_seg.get("zone_percentage_distribution", {})
        cnt_dist = sec_seg.get("zone_word_distribution", {})
        core_pct = sec_seg.get("core_substance_percentage", 0.0)
        p_front = pct_dist.get("FRONT_MATTER", 0.0)
        p_body = pct_dist.get("BODY_TEXT", 0.0)
        p_meth = pct_dist.get("METHODOLOGY", 0.0)
        p_back = pct_dist.get("BACK_MATTER", 0.0)
        w_front = cnt_dist.get("FRONT_MATTER", 0)
        w_body = cnt_dist.get("BODY_TEXT", 0)
        w_meth = cnt_dist.get("METHODOLOGY", 0)
        w_back = cnt_dist.get("BACK_MATTER", 0)

        section_seg_html = f"""
        <h3 class="section-header">Dekomposisi Struktur & Zonasi Naskah (Smart Section Segmentation)</h3>
        <div style="background: #ffffff; border: 1px solid #cbd5e1; border-radius: 6px; padding: 10px 12px; margin-bottom: 14px; page-break-inside: avoid;">
            <div style="display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 8px; font-size: 8.5pt;">
                <span><strong>Distribusi Fungsional Dokumen:</strong> Total {total_words:,} kata</span>
                <span style="font-weight: bold; color: #1e3a8a; background: #eef2ff; padding: 2px 8px; border-radius: 4px; border: 1px solid #c7d2fe;">
                    {core_pct:.1f}% Substansi Pokok
                </span>
            </div>
            <table style="width: 100%; border-collapse: collapse; font-size: 8pt; text-align: center;">
                <tr style="background: #f8fafc; font-weight: bold; color: #0f294a;">
                    <td style="padding: 6px; border: 1px solid #e2e8f0; width: 25%;">1. Front Matter</td>
                    <td style="padding: 6px; border: 1px solid #c7d2fe; background: #eef2ff; width: 25%;">2. Body Text (Substansi)</td>
                    <td style="padding: 6px; border: 1px solid #fef3c7; background: #fffbeb; width: 25%;">3. Methodology</td>
                    <td style="padding: 6px; border: 1px solid #ccfbf1; background: #f0fdfa; width: 25%;">4. Back Matter</td>
                </tr>
                <tr>
                    <td style="padding: 5px; border: 1px solid #e2e8f0;">{p_front:.1f}% ({w_front:,} kata)</td>
                    <td style="padding: 5px; border: 1px solid #c7d2fe; font-weight: bold; color: #1e3a8a;">{p_body:.1f}% ({w_body:,} kata)</td>
                    <td style="padding: 5px; border: 1px solid #fef3c7; color: #92400e;">{p_meth:.1f}% ({w_meth:,} kata)</td>
                    <td style="padding: 5px; border: 1px solid #ccfbf1; color: #0f766e;">{p_back:.1f}% ({w_back:,} kata)</td>
                </tr>
            </table>
            <div style="font-size: 7.5pt; color: #64748b; margin-top: 6px; line-height: 1.35;">
                • Pemisahan zonasi memastikan kemiripan format lembar pengesahan/kata pengantar (Front Matter) dan daftar pustaka (Back Matter) tidak mendistorsi evaluasi keaslian naskah.
            </div>
        </div>
        """

    term_drill_html = ""
    if term_drill and term_drill.get("top_contributing_terms"):
        top_src = sources[0] if sources else None
        top_src_id = top_src.get("source_document_id") if top_src else None
        header_title = f"Transparansi Bobot Kosakata (Dekomposisi Rujukan Utama: ID #{top_src_id})" if top_src_id else "Transparansi Bobot Kosakata (Term-Level IDF Decomposition)"
        
        ref_note_html = ""
        if top_src_id:
            td_cos = float(term_drill.get("cosine_similarity") or 0.0)
            ref_note_html = f"""
            <div style="font-size: 7.5pt; color: #334155; margin-bottom: 8px; line-height: 1.35; background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 4px; padding: 6px 10px;">
                <strong>Konteks Rujukan Teratas:</strong> Dekomposisi istilah di bawah dihitung khusus terhadap <strong>Dokumen Rujukan Utama (Peringkat #1: ID #{top_src_id})</strong>. Berbeda dengan skor kemiripan topikal global korpus pada Lembar 1 (<strong>{cosine_sim:.2f}%</strong>), nilai kemiripan leksikal di bawah (<strong>{td_cos:.2f}%</strong>) menguraikan kontribusi kata kunci non-stopword spesifik terhadap dokumen rujukan utama ini.
            </div>
            """

        t_rows = ""
        for tc in term_drill["top_contributing_terms"][:6]:
            tier = tc.get("significance_tier", "COMMON_VOCABULARY")
            tier_label = "Khas Disipliner" if tier == "DISCIPLINARY_KEYWORD" else ("Bidang Kajian" if tier == "TOPICAL_TERM" else "Kosakata Umum")
            c_pct = float(tc.get('contribution_percentage') or tc.get('contribution_pct') or 0.0)
            t_rows += f"""
            <tr>
                <td style="padding: 4px 8px; border: 1px solid #e2e8f0; font-weight: bold;">{tc.get('term')}</td>
                <td style="padding: 4px 8px; border: 1px solid #e2e8f0; text-align: center;">{tc.get('sub_count', 0)}x / {tc.get('doc_count', 0)}x</td>
                <td style="padding: 4px 8px; border: 1px solid #e2e8f0; text-align: right;">{float(tc.get('idf', 0)):.3f}</td>
                <td style="padding: 4px 8px; border: 1px solid #e2e8f0; text-align: right; font-weight: bold; color: #047857;">{c_pct:.1f}%</td>
                <td style="padding: 4px 8px; border: 1px solid #e2e8f0; text-align: center; font-size: 7.5pt;">{tier_label}</td>
            </tr>
            """
        narrative = term_drill.get("explanation_narrative", "")
        term_drill_html = f"""
        <h3 class="section-header">{header_title}</h3>
        {ref_note_html}
        <div style="background: #ffffff; border: 1px solid #cbd5e1; border-radius: 6px; padding: 10px 12px; margin-bottom: 14px; page-break-inside: avoid;">
            <div style="font-size: 8pt; font-style: italic; color: #334155; margin-bottom: 8px; background: #f8fafc; padding: 6px 10px; border-left: 3px solid #0f294a; border-radius: 0 4px 4px 0;">
                &ldquo;{narrative}&rdquo;
            </div>
            <table style="width: 100%; border-collapse: collapse; font-size: 8pt;">
                <thead>
                    <tr style="background: #f1f5f9; font-weight: bold; color: #0f294a;">
                        <th style="padding: 5px 8px; border: 1px solid #cbd5e1; text-align: left;">Istilah Kunci</th>
                        <th style="padding: 5px 8px; border: 1px solid #cbd5e1; text-align: center;">Frekuensi</th>
                        <th style="padding: 5px 8px; border: 1px solid #cbd5e1; text-align: right;">Bobot IDF</th>
                        <th style="padding: 5px 8px; border: 1px solid #cbd5e1; text-align: right;">Kontribusi</th>
                        <th style="padding: 5px 8px; border: 1px solid #cbd5e1; text-align: center;">Signifikansi</th>
                    </tr>
                </thead>
                <tbody>
                    {t_rows}
                </tbody>
            </table>
        </div>
        """

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

    # Sources rows (Tampilkan Top 6 agar pas di Halaman 2 dan tidak terpotong ke halaman berikutnya)
    sources_html = ""
    display_sources = sources[:6]
    for s in display_sources:
        s_rank = s.get("source_rank", 1)
        s_title = s.get("source_title") or s.get("title") or f"Rujukan #{s.get('source_document_id')}"
        s_sim = float(s.get("similarity") or 0)
        s_exact = float(s.get("exact_similarity") or 0)
        s_cosine = float(s.get("cosine_similarity") or 0)
        s_type = "Korpus Institusi" if s.get("source_type") == "corpus" else "Naskah Terpublikasi"

        sources_html += f"""
        <tr>
            <td style="text-align: center; font-weight: bold; width: 40px;">#{s_rank}</td>
            <td style="padding: 4px 8px;">
                <strong style="color: #0f294a;">{s_title}</strong>
                <div style="font-size: 7.5pt; color: #64748b; margin-top: 1px;">ID #{s.get('source_document_id')} • {s_type}</div>
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

    sources_count_note = ""
    if len(sources) > len(display_sources):
        sources_count_note = f"""
        <div style="font-size: 7.5pt; color: #64748b; margin-top: 4px; text-align: right; font-style: italic;">
            * Menampilkan {len(display_sources)} dokumen rujukan berbobot tertinggi dari total {len(sources)} dokumen pembanding beririsan di korpus.
        </div>
        """

    sources_section_html = f"""
    <h3 class="section-header">Dokumen Rujukan Teratas Terdeteksi</h3>
    <table class="data-table" style="margin-bottom: 6px;">
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
    {sources_count_note}
    """

    # Ekstraksi Audit Integritas Naskah, Sitasi & Lintas Bahasa
    ev_audit = evidence_data.get("evasion_detection") or v12_evol.get("evasion_detection") or {}
    cit_audit = evidence_data.get("citation_audit") or v12_evol.get("citation_audit") or {}
    cl_audit = evidence_data.get("cross_lingual") or v12_evol.get("cross_lingual") or {}
    ai_audit = evidence_data.get("ai_stylometrics") or v12_evol.get("ai_stylometrics") or {}
    xgb_audit = evidence_data.get("xgboost_risk") or v12_evol.get("xgboost_risk") or {}

    ev_risk = float(ev_audit.get("evasion_risk_score") or 0.0)
    ev_status = ev_audit.get("integrity_status") or "CLEAN"
    cit_health = float(cit_audit.get("citation_health_score") or 100.0)
    cit_label = cit_audit.get("verdict_label") or "Sitasi Selaras"
    cl_score = float(cl_audit.get("cross_lingual_score") or 0.0)
    cl_match = bool(cl_audit.get("is_cross_lingual_match"))

    ev_color = "#b91c1c" if ev_risk >= 35 else ("#b45309" if ev_risk >= 10 else "#047857")
    ev_lbl = "Manipulasi Terencana" if ev_status == "MANIPULATED" else ("Perlu Tinjauan Karakter" if ev_status == "SUSPICIOUS" else "Otentik & Bebas Rekayasa")
    cit_color = "#047857" if cit_health >= 80 else ("#b45309" if cit_health >= 50 else "#b91c1c")
    cl_color = "#b91c1c" if cl_score >= 35 else ("#b45309" if cl_score >= 15 else "#047857")
    cl_lbl = "Terindikasi Terjemahan Langsung" if cl_match else ("Parafrasa Lintas Bahasa" if cl_score >= 15 else "Orisinal / Bebas Terjemahan Mesin")

    ext_ver = cit_audit.get("external_verification") or {}
    if not ext_ver and cit_audit.get("has_bibliography_section"):
        try:
            from worker.citation_auditor import extract_bibliography_entries, split_body_and_bibliography
            from worker.external_citation_verifier import verify_bibliography_list
            from worker.database import get_connection
            _conn = get_connection()
            _cur = _conn.cursor(dictionary=True)
            _cur.execute("SELECT text FROM document_chunks WHERE document_id = %s ORDER BY chunk_index ASC", (document.get("id"),))
            _chunks = _cur.fetchall()
            _cur.close()
            _conn.close()
            if _chunks:
                _full_t = " ".join(ch["text"] for ch in _chunks if ch.get("text"))
                _, _b_t, _has_b = split_body_and_bibliography(_full_t)
                if _has_b and _b_t:
                    _entries = extract_bibliography_entries(_b_t)
                    if _entries:
                        ext_ver = verify_bibliography_list(_entries)
        except Exception:
            pass

    ext_ver_row = ""
    ext_ver_details_html = ""
    if ext_ver and ext_ver.get("enabled"):
        v_tot = ext_ver.get("total_checked", 0)
        v_ok = ext_ver.get("verified_count", 0)
        v_fake = ext_ver.get("suspected_fake_count", 0)
        v_unv = ext_ver.get("unverified_count", 0)
        v_rate = float(ext_ver.get("veracity_rate") or 0.0)
        has_fake = bool(ext_ver.get("has_suspected_fakes"))

        if has_fake:
            v_color = "#b91c1c"
            v_label = f"Terindikasi Sitasi Fiktif ({v_fake} entri)"
            v_desc = f"Ditemukan {v_fake} rujukan di Daftar Pustaka yang tidak terdaftar di indeks publikasi global (>250 juta paper OpenAlex & Crossref), mengindikasikan fabrikasi AI."
        elif v_ok > 0:
            v_color = "#047857"
            v_label = "Rujukan Terverifikasi Sahih"
            v_desc = f"Seluruh {v_ok} rujukan yang diperiksa terdaftar sah pada pangkalan data indeks publikasi global."
        else:
            v_color = "#64748b"
            v_label = "Perlu Penelusuran Mandiri"
            v_desc = "Rujukan belum dapat diverifikasi secara otomatis (buku teks/sumber lokal)."

        ext_ver_counts_html = f"""
            <div style="font-size: 6.5pt; font-weight: normal; margin-top: 3px; line-height: 1.45;">
                <span style="display: inline-block; white-space: nowrap; color: #047857; {'background: #ecfdf5; border: 1px solid #a7f3d0; border-radius: 3px; padding: 0.5px 3.5px; font-weight: bold;' if (v_ok > 0 and not has_fake) else ''}">{v_ok} Sahih</span>
                <span style="color: #94a3b8; margin: 0 1px;">&bull;</span>
                <span style="display: inline-block; white-space: nowrap; color: {'#b91c1c' if v_fake > 0 else '#64748b'}; {'background: #fef2f2; border: 1px solid #fecaca; border-radius: 3px; padding: 0.5px 3.5px; font-weight: bold;' if v_fake > 0 else ''}">{v_fake} Fiktif AI</span>
                <span style="color: #94a3b8; margin: 0 1px;">&bull;</span>
                <span style="display: inline-block; white-space: nowrap; color: #64748b;">{v_unv} Non-Indeks</span>
            </div>
        """
        ext_ver_row = f"""
            <tr>
                <td><strong>Verifikasi Rujukan Eksternal (Deteksi Sitasi Fiktif)</strong><br><span style="font-size: 7.5pt; color: #64748b;">Audit Ontologis via OpenAlex &amp; Crossref Global API</span></td>
                <td style="text-align: center; font-weight: bold; color: {v_color}; font-size: 10pt; line-height: 1.25;">
                    {v_rate:.1f}% Sahih
                    {ext_ver_counts_html}
                </td>
                <td>
                    <strong style="color: {v_color};">{v_label}</strong>
                    <div style="font-size: 7.5pt; color: #475569;">{v_desc}</div>
                </td>
            </tr>
        """

        if ext_ver.get("items"):
            fakes = [it for it in ext_ver["items"] if it.get("status") == "suspected_fake"]
            verified = [it for it in ext_ver["items"] if it.get("status") == "verified"]
            detail_lines = []
            for it in fakes:
                t_sh = html_escape(it.get("extracted_title") or "")
                detail_lines.append(f"<li style='color: #b91c1c; margin-bottom: 2px;'><strong>[Terindikasi Fiktif / Halusinasi AI]:</strong> &ldquo;{t_sh}&rdquo; &mdash; Tidak ditemukan di pangkalan data global (>250 juta rekaman).</li>")
            for it in verified[:2]:
                t_sh = html_escape(it.get("extracted_title") or "")
                c_cnt = it.get("cited_by_count", 0)
                detail_lines.append(f"<li style='color: #047857; margin-bottom: 2px;'><strong>[Terverifikasi Sahih]:</strong> &ldquo;{t_sh}&rdquo; ({c_cnt} sitasi global).</li>")
            if detail_lines:
                ext_ver_details_html = f"""
                <div style="margin-top: 6px; padding: 6px 10px; background: {'#fff1f2' if fakes else '#f0fdf4'}; border: 1px solid {'#fecdd3' if fakes else '#bbf7d0'}; border-radius: 6px; font-size: 7.5pt; line-height: 1.35; page-break-inside: avoid;">
                    <div style="font-weight: bold; color: {'#9f1239' if fakes else '#166534'}; margin-bottom: 3px;">
                        Audit Keberadaan Rujukan Ilmiah (OpenAlex / Crossref Global Index):
                    </div>
                    <ul style="margin: 0; padding-left: 14px;">
                        {''.join(detail_lines)}
                    </ul>
                </div>
                """

    ai_row = ""
    if ai_audit:
        ai_prob = float(ai_audit.get("ai_probability_score") or 0.0)
        ai_lbl = ai_audit.get("verdict_label") or "Gaya Bahasa Alami Manusia (Organik)"
        ai_metrics = ai_audit.get("metrics") or {}
        burstiness = float(ai_metrics.get("burstiness") or 0.5)
        ai_conf = ai_audit.get("ai_confidence_level") or "LOW"
        ai_color = "#b91c1c" if ai_prob >= 75 else ("#b45309" if ai_prob >= 50 else "#047857")
        burst_lbl = "Ritme Alami Manusia" if burstiness >= 0.35 else "Ritme Seragam"
        seg_ratio = float(ai_metrics.get("segmental_monotony_ratio") or 0.0)
        seg_str = f" • Monotoni Paragraf: {seg_ratio:.1f}%" if seg_ratio > 0 else ""
        ai_rentang = format_rentang_html([
            {"label": "[0–49%] Rendah", "color": "#047857", "bg": "#ecfdf5", "border": "#a7f3d0", "active": ai_prob < 50.0},
            {"label": "[50–74%] Menengah", "color": "#b45309", "bg": "#fffbeb", "border": "#fde68a", "active": 50.0 <= ai_prob < 75.0},
            {"label": "[≥75%] Tinggi", "color": "#b91c1c", "bg": "#fef2f2", "border": "#fecaca", "active": ai_prob >= 75.0},
        ])
        ai_row = f"""
            <tr>
                <td><strong>[Eksperimental] Indikator Gaya Bahasa &amp; Stylometri</strong><br><span style="font-size: 7.5pt; color: #64748b;">Eksperimental — Bukan Vonis AI</span></td>
                <td style="text-align: center; font-weight: bold; color: {ai_color}; font-size: 10pt; line-height: 1.25;">
                    {ai_prob:.1f}% Indikator
                    {ai_rentang}
                </td>
                <td>
                    <strong>{ai_lbl}</strong>
                    <div style="font-size: 7.5pt; color: #475569;">Burstiness: {burstiness:.2f} ({burst_lbl}){seg_str} • Keyakinan: {ai_conf}</div>
                </td>
            </tr>
        """

    xgb_row = ""
    if xgb_audit:
        xgb_score = float(xgb_audit.get("calibrated_risk_score") or 0.0)
        xgb_lbl = xgb_audit.get("verdict_label") or "Risiko Rendah / Mandiri"
        xgb_level = xgb_audit.get("risk_level") or "LOW"
        xgb_color = "#b91c1c" if xgb_score >= 70 else ("#b45309" if xgb_score >= 35 else "#047857")
        xgb_rentang = format_rentang_html([
            {"label": "[0–34%] Rendah", "color": "#047857", "bg": "#ecfdf5", "border": "#a7f3d0", "active": xgb_score < 35.0},
            {"label": "[35–69%] Menengah", "color": "#b45309", "bg": "#fffbeb", "border": "#fde68a", "active": 35.0 <= xgb_score < 70.0},
            {"label": "[70–100%] Tinggi", "color": "#b91c1c", "bg": "#fef2f2", "border": "#fecaca", "active": xgb_score >= 70.0},
        ])
        xgb_row = f"""
            <tr>
                <td><strong>Sintesis Risiko Akademik Berbobot</strong><br><span style="font-size: 7.5pt; color: #64748b;">Analisis Multi-Dimensi Determinan Risiko</span></td>
                <td style="text-align: center; font-weight: bold; color: {xgb_color}; font-size: 10pt; line-height: 1.25;">
                    {xgb_score:.1f}% Indeks Risiko
                    {xgb_rentang}
                </td>
                <td>
                    <strong>{xgb_lbl}</strong>
                    <div style="font-size: 7.5pt; color: #475569;">Level: {xgb_level} • Evaluasi komposit berbasis model terkalibrasi</div>
                </td>
            </tr>
        """

    forensic_audit = evidence_data.get("gemini_forensic") or v12_evol.get("gemini_forensic") or {}
    forensic_completed = bool(forensic_audit and forensic_audit.get("status") == "completed")
    g_data = forensic_audit.get("audit") or {}
    if not g_data and forensic_audit.get("raw_output"):
        m_lvl = re.search(r'"tingkat_indikasi_ai"\s*:\s*"([^"]+)"', forensic_audit["raw_output"], re.I)
        m_rsn = re.search(r'"alasan_indikasi_ai"\s*:\s*"([^"]+)"', forensic_audit["raw_output"])
        if m_lvl:
            g_data["tingkat_indikasi_ai"] = m_lvl.group(1)
        if m_rsn:
            g_data["alasan_indikasi_ai"] = m_rsn.group(1)

    g_level = str(g_data.get("tingkat_indikasi_ai") or "RENDAH").upper()
    g_color = "#b91c1c" if g_level == "TINGGI" else ("#b45309" if g_level == "SEDANG" else "#047857")
    g_contra = g_data.get("inkonsistensi_metodologi_dan_logika") or []
    g_cit = g_data.get("integritas_sitasi") or []
    g_questions = g_data.get("pertanyaan_kritis_dosen") or []

    # Penilaian Komposit AI & Stylometri (diselaraskan penuh dengan Sertifikat & Daftar Karya Ilmiah)
    from worker.generate_certificate_pdf import extract_ai_assessment
    ai_composite_score, ai_composite_label = extract_ai_assessment(evidence_data)
    if ai_composite_score is None:
        ai_composite_score = 0.0
    ai_composite_color = "#b91c1c" if (ai_composite_score >= 70.0 or g_level == "TINGGI") else ("#b45309" if (ai_composite_score >= 35.0 or g_level == "SEDANG") else "#047857")

    forensic_row = ""
    if forensic_completed:
        forensic_rentang = format_rentang_html([
            {"label": "[0–34%] Rendah", "color": "#047857", "bg": "#ecfdf5", "border": "#a7f3d0", "active": ai_composite_score < 35.0},
            {"label": "[35–69%] Menengah", "color": "#b45309", "bg": "#fffbeb", "border": "#fde68a", "active": 35.0 <= ai_composite_score < 70.0},
            {"label": "[70–100%] Tinggi", "color": "#b91c1c", "bg": "#fef2f2", "border": "#fecaca", "active": ai_composite_score >= 70.0},
        ])
        forensic_row = f"""
            <tr>
                <td><strong>Audit Forensik Cerdas</strong><br><span style="font-size: 7.5pt; color: #64748b;">Konsistensi Semantik Antar-Bab &amp; Integritas Logika</span></td>
                <td style="text-align: center; font-weight: bold; color: {ai_composite_color}; font-size: 10pt; line-height: 1.25;">
                    {ai_composite_score:.1f}% Indikasi AI
                    {forensic_rentang}
                </td>
                <td>
                    <strong>{ai_composite_label} (Status: {g_level})</strong>
                    <div style="font-size: 7.5pt; color: #475569;">
                        {len(g_contra)} inkonsistensi metodologi, {len(g_questions)} panduan pertanyaan sidang dosen
                    </div>
                </td>
            </tr>
        """

    ev_rentang = format_rentang_html([
        {"label": "[0–9%] Rendah", "color": "#047857", "bg": "#ecfdf5", "border": "#a7f3d0", "active": ev_risk < 10.0},
        {"label": "[10–34%] Menengah", "color": "#b45309", "bg": "#fffbeb", "border": "#fde68a", "active": 10.0 <= ev_risk < 35.0},
        {"label": "[≥35%] Tinggi", "color": "#b91c1c", "bg": "#fef2f2", "border": "#fecaca", "active": ev_risk >= 35.0},
    ])
    cit_rentang = format_rentang_html([
        {"label": "[80–100] Prima", "color": "#047857", "bg": "#ecfdf5", "border": "#a7f3d0", "active": cit_health >= 80.0},
        {"label": "[50–79] Menengah", "color": "#b45309", "bg": "#fffbeb", "border": "#fde68a", "active": 50.0 <= cit_health < 80.0},
        {"label": "[<50] Kritis", "color": "#b91c1c", "bg": "#fef2f2", "border": "#fecaca", "active": cit_health < 50.0},
    ])
    cl_rentang = format_rentang_html([
        {"label": "[0–14%] Rendah", "color": "#047857", "bg": "#ecfdf5", "border": "#a7f3d0", "active": cl_score < 15.0},
        {"label": "[15–34%] Menengah", "color": "#b45309", "bg": "#fffbeb", "border": "#fde68a", "active": 15.0 <= cl_score < 35.0},
        {"label": "[≥35%] Tinggi", "color": "#b91c1c", "bg": "#fef2f2", "border": "#fecaca", "active": cl_score >= 35.0},
    ])

    audit_html = f"""
    <h3 class="section-header">Audit Integritas Naskah, Sitasi &amp; Analisis Lintas Bahasa</h3>
    <div style="font-size: 7pt; color: #64748b; margin-top: -3px; margin-bottom: 6px;">
        * Panduan rentang nilai standar: <span style="display: inline-block; background: #ecfdf5; color: #047857; border: 1px solid #a7f3d0; border-radius: 3px; padding: 0.5px 4px; font-weight: bold;">[0–34%] Rendah (Aman)</span> &bull; <span style="display: inline-block; background: #fffbeb; color: #b45309; border: 1px solid #fde68a; border-radius: 3px; padding: 0.5px 4px; font-weight: bold;">[35–69%] Menengah (Telaah)</span> &bull; <span style="display: inline-block; background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; border-radius: 3px; padding: 0.5px 4px; font-weight: bold;">[70–100%] Tinggi (Atensi)</span>. Rincian skala spesifik tercantum di bawah masing-masing indikator.
    </div>
    <table class="data-table" style="margin-bottom: 14px; page-break-inside: avoid;">
        <thead>
            <tr>
                <th style="text-align: left; width: 33%;">Parameter Audit</th>
                <th style="text-align: center; width: 30%;">Skor / Indikator</th>
                <th style="text-align: left; width: 37%;">Status &amp; Keterangan</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><strong>Integritas Karakter &amp; Anti-Evasion</strong><br><span style="font-size: 7.5pt; color: #64748b;">Deteksi Homoglif, Zero-Width, White-Text</span></td>
                <td style="text-align: center; font-weight: bold; color: {ev_color}; font-size: 10pt; line-height: 1.25;">
                    {ev_risk:.1f}% Risiko
                    {ev_rentang}
                </td>
                <td>
                    <strong>{ev_lbl}</strong>
                    <div style="font-size: 7.5pt; color: #475569;">Homoglif: {int(ev_audit.get('homoglyph_count') or 0)}, Invisible: {int(ev_audit.get('zero_width_count') or 0)}</div>
                </td>
            </tr>
            <tr>
                <td><strong>Konsistensi Sitasi Internal</strong><br><span style="font-size: 7.5pt; color: #64748b;">Kesesuaian Teks vs Daftar Pustaka (Audit Internal)</span></td>
                <td style="text-align: center; font-weight: bold; color: {cit_color}; font-size: 10pt; line-height: 1.25;">
                    {cit_health:.1f} / 100
                    {cit_rentang}
                </td>
                <td>
                    <strong>{cit_label}</strong>
                    <div style="font-size: 7.5pt; color: #475569;">Sitasi Tanpa Pustaka: {int(cit_audit.get('phantom_citations_count') or 0)}, Pustaka Tak Dikutip: {int(cit_audit.get('orphan_references_count') or 0)}</div>
                </td>
            </tr>
            {ext_ver_row}
            <tr>
                <td><strong>Plagiarisme Terjemahan (ID ↔ EN)</strong><br><span style="font-size: 7.5pt; color: #64748b;">Analisis Semantik Proyeksi Konsep Dwibahasa</span></td>
                <td style="text-align: center; font-weight: bold; color: {cl_color}; font-size: 10pt; line-height: 1.25;">
                    {cl_score:.1f}% Keselarasan
                    {cl_rentang}
                </td>
                <td>
                    <strong>{cl_lbl}</strong>
                    <div style="font-size: 7.5pt; color: #475569;">Konsep terpadan: {int(cl_audit.get('shared_concept_count') or 0)} istilah, Pasase: {len(cl_audit.get('aligned_passages') or [])}</div>
                </td>
            </tr>
            {ai_row}
            {xgb_row}
            {forensic_row}
        </tbody>
    </table>
    {ext_ver_details_html}
    """

    forensic_findings_html = ""
    questions_section_html = ""
    if forensic_completed and (g_data.get("alasan_indikasi_ai") or g_contra or g_cit or g_questions):
        g_reason_txt = g_data.get("alasan_indikasi_ai") or ""
        reason_box = ""
        if g_reason_txt:
            reason_box = f"""
            <div style="margin-bottom: 8px; padding: 7px 10px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; font-size: 7.5pt; color: #334155; line-height: 1.4;">
                <div style="font-weight: bold; color: {g_color}; margin-bottom: 2px; font-size: 7pt; text-transform: uppercase;">
                    Evaluasi Sintetis &amp; Indikasi Perakitan AI (Status: {g_level}):
                </div>
                <div style="font-style: italic;">&ldquo;{html_escape(g_reason_txt)}&rdquo;</div>
            </div>
            """

        filtered_contra = [c.strip() for c in g_contra if c and c.strip()]
        filtered_cit = [c.strip() for c in g_cit if c and c.strip()]

        contra_items = "".join([f"<li style='margin-bottom: 3px;'>{html_escape(c)}</li>" for c in filtered_contra]) or "<li style='color: #64748b; font-style: italic;'>Tidak ditemukan kontradiksi logika mayor antar-bab.</li>"
        cit_items = "".join([f"<li style='margin-bottom: 3px;'>{html_escape(c)}</li>" for c in filtered_cit]) or "<li style='color: #64748b; font-style: italic;'>Sitasi rujukan selaras dan tidak ditemukan anomali temporal.</li>"

        findings_grid = f"""
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-bottom: 8px;">
            <div style="background: #ffffff; padding: 7px; border: 1px solid #e2e8f0; border-radius: 6px; font-size: 7.5pt;">
                <div style="font-weight: bold; color: #9a3412; font-size: 7pt; margin-bottom: 4px; text-transform: uppercase;">
                    Inkonsistensi Metodologi &amp; Logika ({len(filtered_contra)})
                </div>
                <ul style="margin: 0; padding-left: 12px; color: #334155; line-height: 1.35;">
                    {contra_items}
                </ul>
            </div>
            <div style="background: #ffffff; padding: 7px; border: 1px solid #e2e8f0; border-radius: 6px; font-size: 7.5pt;">
                <div style="font-weight: bold; color: #0369a1; font-size: 7pt; margin-bottom: 4px; text-transform: uppercase;">
                    Integritas Sitasi &amp; Anomali Rujukan ({len(filtered_cit)})
                </div>
                <ul style="margin: 0; padding-left: 12px; color: #334155; line-height: 1.35;">
                    {cit_items}
                </ul>
            </div>
        </div>
        """

        forensic_findings_html = f"""
        <div style="margin-top: 10px; margin-bottom: 8px; page-break-inside: avoid;">
            <h3 class="section-header" style="margin-bottom: 6px;">Audit Forensik Cerdas (Evaluasi Semantik &amp; Logika Metodologi)</h3>
            {reason_box}
            {findings_grid}
        </div>
        """

        if g_questions:
            questions_boxes = ""
            for q_idx, q in enumerate(g_questions, 1):
                q_fokus = html_escape(q.get("fokus") or "Substansi")
                q_urgensi = html_escape(q.get("tingkat_urgensi") or "")
                q_text = html_escape(q.get("pertanyaan") or "")
                q_alasan = html_escape(q.get("alasan") or "")
                q_strategi = html_escape(q.get("strategi_jawaban") or "")

                urgensi_badge = f'<span style="font-size: 6.5pt; font-weight: bold; padding: 1px 4px; border-radius: 3px; background: #fee2e2; color: #991b1b;">{q_urgensi}</span>' if q_urgensi else ''
                strategi_html = f'<div style="font-size: 6.8pt; color: #065f46; margin-top: 3px; background: #f0fdf4; padding: 3px 6px; border-radius: 3px; border-left: 2px solid #10b981;"><strong>Strategi Jawaban Mahasiswa:</strong> {q_strategi}</div>' if q_strategi else ''

                questions_boxes += f"""
                <div style="margin-bottom: 5px; padding: 5px 8px; background: #ffffff; border: 1px solid #e2e8f0; border-left: 3px solid #6366f1; border-radius: 4px; font-size: 7.5pt;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 2px;">
                        <span style="font-weight: bold; color: #312e81; font-size: 7pt;">Pertanyaan #{q_idx} • Fokus: {q_fokus}</span>
                        {urgensi_badge}
                    </div>
                    <div style="font-weight: 600; color: #1e293b; margin-bottom: 2px; line-height: 1.35;">&ldquo;{q_text}&rdquo;</div>
                    <div style="font-size: 6.8pt; color: #64748b; font-style: italic;">Tujuan Pengujian: {q_alasan}</div>
                    {strategi_html}
                </div>
                """

            questions_section_html = f"""
            <div style="margin-top: 6px; margin-bottom: 10px; page-break-inside: avoid;">
                <h3 class="section-header" style="margin-bottom: 6px;">Panduan Ujian Lisan Sidang Dosen &amp; Instrumen Pengujian Orisinalitas</h3>
                <div style="background: #faf5ff; border: 1px solid #e9d5ff; border-radius: 6px; padding: 7px; margin-bottom: 6px;">
                    <div style="font-weight: bold; color: #6b21a8; font-size: 7.5pt; margin-bottom: 5px; display: flex; align-items: center; justify-content: space-between;">
                        <span>Panduan Pertanyaan Kritis Ujian Sidang Skripsi / Tesis</span>
                        <span style="font-size: 6.8pt; font-weight: normal; color: #7e22ce;">{len(g_questions)} Pertanyaan Penguji</span>
                    </div>
                    {questions_boxes}
                </div>
            </div>
            """

    # Matches rows (Tampilkan 2 pasase teratas agar muat bersama tanda tangan di Halaman 4)
    matches_html = ""
    for idx, m in enumerate(matches[:2], 1):
        w_cnt = m.get("word_count", 0)
        m_type = (m.get("match_type") or "exact").upper()
        s_sub = m.get("submission_snippet", "")
        s_src = m.get("source_snippet", "")
        src_id = m.get("source_document_id", "-")
        sub_span = f"Kata {m.get('submission_start', 0)} &ndash; {m.get('submission_end', 0)}"

        bp_html = ""
        if m.get("is_boilerplate"):
            bp_cat = m.get("boilerplate_category", "FORMULA")
            bp_phr = m.get("boilerplate_phrase", "")
            bp_rat = m.get("boilerplate_rationale", "")
            bp_html = f"""
            <div style="margin-top: 4px; padding: 3px 6px; background: #eef2ff; border: 1px solid #c7d2fe; border-radius: 4px; font-size: 7pt; color: #3730a3;">
                <strong>Formulasi Ilmiah Baku ({bp_cat}):</strong> &ldquo;{bp_phr}&rdquo; &mdash; {bp_rat}
            </div>
            """

        matches_html += f"""
        <div class="match-item" style="margin-bottom: 8px; padding: 6px 9px; border: 1px solid #e2e8f0; border-radius: 6px; background-color: #f8fafc; page-break-inside: avoid;">
            <div style="display: flex; justify-content: space-between; font-size: 8pt; margin-bottom: 4px; border-bottom: 1px solid #cbd5e1; padding-bottom: 3px;">
                <span><strong>Pasase #{idx}</strong> • {sub_span} ({w_cnt} kata)</span>
                <span>Rujukan: <strong>Doc #{src_id}</strong> • Tipe: <strong>{m_type}</strong></span>
            </div>
            <div style="font-size: 7.5pt; line-height: 1.35; display: grid; grid-template-columns: 1fr 1fr; gap: 8px;">
                <div style="background: #ffffff; padding: 5px; border: 1px solid #e2e8f0; border-radius: 4px;">
                    <div style="font-weight: bold; color: #1e3a8a; font-size: 7pt; margin-bottom: 2px; text-transform: uppercase;">Cuplikan Naskah Submisi</div>
                    <div style="color: #334155; font-style: italic;">&ldquo;{s_sub[:240] + ('...' if len(s_sub) > 240 else '')}&rdquo;</div>
                </div>
                <div style="background: #ffffff; padding: 5px; border: 1px solid #e2e8f0; border-radius: 4px;">
                    <div style="font-weight: bold; color: #475569; font-size: 7pt; margin-bottom: 2px; text-transform: uppercase;">Cuplikan Dokumen Rujukan</div>
                    <div style="color: #334155; font-style: italic;">&ldquo;{s_src[:240] + ('...' if len(s_src) > 240 else '')}&rdquo;</div>
                </div>
            </div>
            {bp_html}
        </div>
        """

    if not matches:
        matches_html = """
        <div style="text-align: center; color: #64748b; font-style: italic; padding: 12px; border: 1px dashed #cbd5e1; border-radius: 6px; font-size: 7.5pt;">
            Tidak ditemukan pasase kata berurutan yang melebihi batas deteksi sekuensial.
        </div>
        """

    matches_section_html = f"""
    <div style="margin-top: 8px; margin-bottom: 8px; page-break-inside: avoid;">
        <h3 class="section-header" style="margin-bottom: 6px;">Cuplikan Pasase Beririsan (Sampel Kontekstual)</h3>
        {matches_html}
    </div>
    """

    verify_url = f"{VERIFY_BASE_URL}/verify/{cert_hash}"
    logo_path = Path(__file__).resolve().parents[1] / "public" / "images" / "logo.png"
    logo_b64 = ""
    if logo_path.exists():
        try:
            with open(logo_path, "rb") as f:
                logo_b64 = base64.b64encode(f.read()).decode("utf-8")
        except Exception as e:
            print(f"[EXPORTER WARNING] Gagal memuat logo: {e}")
    logo_img_tag = f'<img src="data:image/png;base64,{logo_b64}" alt="AR Tilik Logo" style="width: 44px; height: 44px; object-fit: contain; display: block;" />' if logo_b64 else ''

    qr_code_base64 = generate_qr_code_base64(verify_url)
    qr_img_tag = f'<img src="data:image/png;base64,{qr_code_base64}" alt="QR Verifikasi" style="width: 52px; height: 52px; border: 1px solid #cbd5e1; border-radius: 4px; padding: 2px; background: #ffffff;" />' if qr_code_base64 else ''

    page_break_after_sources = '<div class="page-break"></div>'
    page_break_after_forensic = '<div class="page-break"></div>' if forensic_completed else ''

    html = f"""<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>Laporan Bukti Kemiripan #{r_id} — AR Tilik</title>
<style>
    @page {{
        size: A4;
        margin: 13mm 14mm 15mm 14mm;
        @top-right {{
            content: "AR Tilik v1.3.0 • Sertifikasi #{cert_hash}";
            font-size: 7pt;
            font-family: 'Liberation Sans', 'Helvetica Neue', Arial, sans-serif;
            color: #94a3b8;
        }}
        @bottom-left {{
            content: "Dicetak secara otomatis oleh Sistem AR Tilik • Keabsahan digital terverifikasi";
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

    .page-break {{
        page-break-after: always;
        break-after: page;
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
        padding: 5px 7px;
        font-size: 7.5pt;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        border: 1px solid #0f294a;
    }}
    .data-table td {{
        padding: 4px 7px;
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
        margin-top: 14px;
        page-break-inside: avoid;
    }}
    .sig-box {{
        text-align: center;
        font-size: 8pt;
        color: #334155;
    }}
    .sig-role {{
        font-weight: bold;
        margin-bottom: 35px;
        color: #0f294a;
    }}
    .sig-line {{
        border-top: 1px solid #475569;
        margin: 0 10px;
        padding-top: 4px;
    }}

    h3.section-header {{
        font-size: 9pt;
        font-weight: bold;
        color: #0f294a;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin: 10px 0 6px 0;
        border-bottom: 1.5px solid #e2e8f0;
        padding-bottom: 3px;
    }}
</style>
</head>
<body>

    <!-- Header / Kop -->
    <table class="header-table">
        <tr>
            <td style="vertical-align: middle;">
                <table cellpadding="0" cellspacing="0" style="border: none; margin: 0; padding: 0;">
                    <tr>
                        {f'<td style="vertical-align: middle; padding-right: 12px; width: 44px; border: none;">{logo_img_tag}</td>' if logo_img_tag else ''}
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
                        <div class="cert-code">{cert_hash}</div>
                        <div style="font-size: 6.5pt; color: #64748b; margin-top: 2px;">Pindai QR untuk Validasi Keaslian</div>
                    </div>
                    {qr_img_tag}
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
            <td class="meta-val">AR Tilik Dual-Lens Hybrid v1.3</td>
            <td class="meta-label">Cakupan Korpus</td>
            <td class="meta-val">{f"{corpus_docs_count:,} dokumen pembanding" if corpus_docs_count > 0 else f"{exec_time:.2f} detik (Lengkap)"}</td>
        </tr>
        {fac_dept_row}
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
                <span class="lens-card-title">Lensa 1 • Exact Matcher</span>
                <span class="lens-card-sub">5-gram Sequential Shingling</span>
            </div>
            <div class="metric-row">
                <span class="metric-lbl">Kesamaan Sekuensial (Exact Sim)</span>
                <span class="metric-val">{exact_sim:.2f}%</span>
            </div>
            {f'''<div class="metric-row">
                <span class="metric-lbl">Substansi Pokok (Non-Boilerplate)</span>
                <span class="metric-val" style="color: #047857;">{float(sub_sim_val):.2f}% (max {sub_longest_val} kata)</span>
            </div>''' if sub_sim_val is not None else ''}
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
                <span class="lens-card-title">Lensa 2 • TF-IDF Vectorizer</span>
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

    {section_seg_html}

    <div class="page-break"></div>

    <!-- Lembar 2: Transparansi Kosakata & Dokumen Rujukan Utama -->
    {term_drill_html}
    {sources_section_html}

    {page_break_after_sources}

    <!-- Lembar 3: Audit Integritas & Forensik Cerdas -->
    {audit_html}
    {forensic_findings_html}

    {page_break_after_forensic}

    <!-- Lembar 4: Panduan Ujian Sidang, Sampel Pasase & Pengesahan -->
    {questions_section_html}
    {matches_section_html}

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
        # 1b. Update verification_code di database jika belum terisi
        cert_hash = report.get("verification_code")
        if not cert_hash:
            raw_created = str(report["created_at"]) if report.get("created_at") else str(datetime.now())
            overall_sim = float(report.get("overall_similarity") or 0)
            cert_hash = generate_verification_hash(report_id, doc_id, raw_created, overall_sim)
            report["verification_code"] = cert_hash
            try:
                cursor.execute("UPDATE reports SET verification_code = %s WHERE id = %s AND (verification_code IS NULL OR verification_code = '')", (cert_hash, report_id))
                conn.commit()
            except Exception:
                pass

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
                SELECT text, normalized_text, start_word_position, end_word_position
                FROM document_chunks
                WHERE document_id = %s AND start_word_position <= %s
                ORDER BY start_word_position DESC LIMIT 1
            """, (document_id, start_word))
            chunk = cursor.fetchone()
            if not chunk or not chunk.get("text"):
                return "Teks tidak tersedia."

            raw_text = chunk["text"]
            offset = max(0, start_word - int(chunk.get("start_word_position") or 0))
            count = max(1, word_count)

            # 1. Ekstraksi token presisi dari teks asli dengan regex untuk mempertahankan tanda baca & kapitalisasi
            raw_tokens = list(re.finditer(r'\b[a-zA-Z0-9]+(?:-[a-zA-Z0-9]+)*\b', raw_text))
            if raw_tokens and offset < len(raw_tokens):
                end_idx = min(len(raw_tokens) - 1, offset + count - 1)
                start_byte = raw_tokens[offset].start()
                end_byte = raw_tokens[end_idx].end()
                snippet = " ".join(raw_text[start_byte:end_byte].split())
                if snippet:
                    return snippet

            # 2. Fallback deterministik ke normalized_text
            norm_text = chunk.get("normalized_text")
            if norm_text:
                words = norm_text.split()
                slice_words = words[offset:offset + count]
                if slice_words:
                    return " ".join(slice_words)

            # 3. Fallback pemecahan spasi sederhana
            words = raw_text.split()
            return " ".join(words[offset:offset + count]) if words else "Teks tidak tersedia."

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
        # 1. Coba WeasyPrint jika terpasang
        success = False
        weasy_bin = shutil.which("weasyprint")
        if weasy_bin:
            res = subprocess.run([weasy_bin, html_file, pdf_file], capture_output=True, text=True)
            if res.returncode == 0:
                success = True

        # 2. Fallback ke Google Chrome / Chromium headless
        if not success:
            chrome_bin = (
                shutil.which("google-chrome-stable") or
                shutil.which("google-chrome") or
                shutil.which("chromium-browser") or
                shutil.which("chromium")
            )
            if chrome_bin:
                chrome_cmd = [
                    chrome_bin,
                    "--headless",
                    "--disable-gpu",
                    "--no-sandbox",
                    f"--print-to-pdf={pdf_file}",
                    html_file
                ]
                subprocess.run(chrome_cmd, capture_output=True, check=True)
                success = True
            else:
                raise RuntimeError("Tidak ditemukan WeasyPrint maupun Google Chrome/Chromium untuk merender PDF.")

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
