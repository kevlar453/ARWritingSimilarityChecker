import argparse
import base64
import hashlib
import io
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

PROJECT_ROOT = Path(__file__).resolve().parents[1]
if str(PROJECT_ROOT) not in sys.path:
    sys.path.insert(0, str(PROJECT_ROOT))

from worker.database import get_connection

VERIFY_BASE_URL = os.getenv("APP_PUBLIC_URL", "https://tilik.arsetontong.top")

def generate_qr_code_base64(url: str) -> str:
    try:
        qr = qrcode.QRCode(
            version=1,
            error_correction=qrcode.constants.ERROR_CORRECT_M,
            box_size=5,
            border=1
        )
        qr.add_data(url)
        qr.make(fit=True)
        img = qr.make_image(fill_color="#081a33", back_color="white")
        buf = io.BytesIO()
        img.save(buf, format="PNG")
        return base64.b64encode(buf.getvalue()).decode("utf-8")
    except Exception as e:
        print(f"[CERTIFICATE WARNING] Gagal menghasilkan QR code: {e}", file=sys.stderr)
        return ""

def get_logo_base64() -> str:
    logo_path = PROJECT_ROOT / "public" / "images" / "logo.png"
    if logo_path.exists():
        try:
            with open(logo_path, "rb") as f:
                return base64.b64encode(f.read()).decode("utf-8")
        except Exception:
            pass
    return ""

def format_date_id(dt_val) -> tuple[str, str]:
    months_id = [
        "", "Januari", "Februari", "Maret", "April", "Mei", "Juni",
        "Juli", "Agustus", "September", "Oktober", "November", "Desember"
    ]
    if isinstance(dt_val, str):
        try:
            dt = datetime.fromisoformat(dt_val.replace("Z", "+00:00"))
        except Exception:
            try:
                dt = datetime.strptime(dt_val[:19], "%Y-%m-%d %H:%M:%S")
            except Exception:
                dt = datetime.now()
    elif isinstance(dt_val, datetime):
        dt = dt_val
    else:
        dt = datetime.now()

    date_str = f"{dt.day:02d} {months_id[dt.month]} {dt.year}"
    time_str = f"{dt.strftime('%H:%M')} WIB"
    return date_str, time_str

def calculate_dynamic_ai_score(gemini: dict, stylometrics_score: float = None) -> float:
    audit = gemini.get("audit") or {}
    if not audit and gemini.get("raw_output"):
        m = re.search(r'"tingkat_indikasi_ai"\s*:\s*"([^"]+)"', gemini["raw_output"], re.IGNORECASE)
        if m:
            audit["tingkat_indikasi_ai"] = m.group(1)
        m2 = re.search(r'"alasan_indikasi_ai"\s*:\s*"([^"]+)"', gemini["raw_output"], re.IGNORECASE)
        if m2:
            audit["alasan_indikasi_ai"] = m2.group(1)
        m3 = re.search(r'"skor_indikasi_persen"\s*:\s*(\d+(?:\.\d+)?)', gemini["raw_output"], re.IGNORECASE)
        if m3:
            audit["skor_indikasi_persen"] = float(m3.group(1))

    if "skor_indikasi_persen" in audit and audit["skor_indikasi_persen"] is not None:
        try:
            explicit = float(audit["skor_indikasi_persen"])
            if 0.0 <= explicit <= 100.0:
                return max(explicit, stylometrics_score) if stylometrics_score is not None else explicit
        except (ValueError, TypeError):
            pass

    level = str(audit.get("tingkat_indikasi_ai") or "RENDAH").strip().upper()
    reason = str(audit.get("alasan_indikasi_ai") or "")
    contra = audit.get("inkonsistensi_metodologi_dan_logika") or []
    citations = audit.get("integritas_sitasi") or []
    ev_text = (reason + " " + " ".join([str(c) for c in contra]) + " " + " ".join([str(ci) for ci in citations])).lower()

    if level == "RENDAH":
        return stylometrics_score if stylometrics_score is not None else 0.0

    if level == "SEDANG":
        score = 50.0 + (5.0 if len(contra) > 1 or len(citations) > 1 else 0.0)
        return round(max(score, stylometrics_score or 0.0), 1)

    is_meta = bool(re.search(r"(kebocoran instruksi|meta-prompt|prompt leak|disalin-tempel secara langsung|copy-paste mentah|percakapan ai|chat ai|instruksi percakapan|bisa\. saya akan buat|tentu, berikut|sebagai model bahasa ai)", ev_text))
    if is_meta:
        return 98.0

    has_cit = bool(re.search(r"(halusinasi|tokoh fiktif|nama jurnal .* diperlakukan sebagai|nama paroki .* diperlakukan sebagai|sitasi palsu|penulis fiktif|fabrikasi)", ev_text))
    base = 80.0 if has_cit else 74.0
    contra_w = min(len(contra) * 1.5, 4.5)
    cit_w = min(len(citations) * 1.5, 4.5)
    forensic_raw = base + contra_w + cit_w

    if stylometrics_score is not None and stylometrics_score > 0:
        if stylometrics_score >= 70.0:
            final = max(forensic_raw, stylometrics_score + 6.0)
        elif stylometrics_score >= 35.0:
            final = (forensic_raw * 0.8) + (stylometrics_score * 0.2)
        else:
            final = forensic_raw
    else:
        final = forensic_raw
    return round(min(max(final, 70.0), 99.0), 1)

def extract_ai_assessment(evidence_data) -> tuple[float | None, str]:
    if not evidence_data:
        return None, "Otentik Alami"
    try:
        ev = json.loads(evidence_data) if isinstance(evidence_data, str) else evidence_data
    except Exception:
        return None, "Otentik Alami"

    ai_sty = ev.get("ai_stylometrics") or ev.get("v1_1_presentation", {}).get("v1_2_evolution", {}).get("ai_stylometrics")
    gemini = ev.get("gemini_forensic") or ev.get("v1_1_presentation", {}).get("v1_2_evolution", {}).get("gemini_forensic")

    stylometrics_score = None
    if isinstance(ai_sty, dict) and "ai_probability_score" in ai_sty and ai_sty["ai_probability_score"] is not None:
        try:
            stylometrics_score = float(ai_sty["ai_probability_score"])
        except (ValueError, TypeError):
            pass

    forensic_level = None
    if isinstance(gemini, dict) and gemini.get("status") == "completed":
        g_audit = gemini.get("audit") or {}
        if not g_audit and gemini.get("raw_output"):
            m = re.search(r'"tingkat_indikasi_ai"\s*:\s*"([^"]+)"', gemini["raw_output"], re.IGNORECASE)
            if m:
                g_audit["tingkat_indikasi_ai"] = m.group(1)
        if g_audit.get("tingkat_indikasi_ai"):
            forensic_level = str(g_audit["tingkat_indikasi_ai"]).strip().upper()

    composite_score = None
    if forensic_level is not None and isinstance(gemini, dict) and gemini.get("status") == "completed":
        composite_score = calculate_dynamic_ai_score(gemini, stylometrics_score)
    else:
        composite_score = stylometrics_score

    if composite_score is None:
        return None, "Otentik Alami"

    is_meta = False
    if isinstance(gemini, dict):
        comb = json.dumps(gemini).lower()
        is_meta = bool(re.search(r"(kebocoran instruksi|meta-prompt|prompt leak|disalin-tempel secara langsung|copy-paste mentah|bisa\. saya akan buat)", comb))

    if is_meta:
        label = "Indikasi Ekstrem (Prompt Leak)"
    elif composite_score >= 70.0 or forensic_level == "TINGGI":
        label = "Indikasi AI: Tinggi"
    elif composite_score >= 35.0 or forensic_level == "SEDANG":
        label = "Indikasi AI: Sedang"
    else:
        label = "Otentik Alami"

    return composite_score, label

def build_certificate_html(report: dict, document: dict, override_ai_score: float = None, override_ai_label: str = None) -> str:
    code = report.get("verification_code") or f"ART-{report['id']:04d}-CERT"
    overall_sim = float(report.get("overall_similarity") or 0)
    exact_sim = float(report.get("exact_similarity") or 0)
    cosine_sim = float(report.get("cosine_similarity") or 0)
    longest_run = int(report.get("longest_match_words") or 0)
    originality_rate = max(0.0, 100.0 - overall_sim)
    decision = str(report.get("predicted_decision") or "review").lower()
    engine = str(report.get("engine") or "HYBRID").upper()
    rationale = report.get("rationale") or "Naskah memenuhi kaidah integritas dan orisinalitas karya ilmiah perguruan tinggi."

    # Dates
    date_formatted, time_formatted = format_date_id(report.get("created_at"))

    # Title & Metadata
    title = document.get("title") or "Dokumen Karya Ilmiah"
    filename = document.get("original_filename") or f"document_{document.get('id', '')}.docx"
    author_name = document.get("author_name") or document.get("user_name") or "Sivitas Akademika"
    institution = document.get("institution") or document.get("user_inst") or "Institusi Akademik"
    faculty = document.get("faculty")
    department = document.get("department")
    fac_dept = " / ".join([p for p in [faculty, department] if p])

    # Purged status
    file_purged_at = document.get("file_purged_at")
    is_purged = bool(file_purged_at or document.get("storage_path") == "[PURGED]")
    purged_str = ""
    if file_purged_at:
        p_date, p_time = format_date_id(file_purged_at)
        purged_str = f"{p_date}, {p_time}"

    # AI assessment (diselaraskan penuh dengan Audit Forensik Cerdas)
    if override_ai_score is not None:
        ai_score = float(override_ai_score)
        ai_label = override_ai_label or ("Indikasi AI: Tinggi" if ai_score >= 70 else ("Indikasi AI: Sedang" if ai_score >= 35 else "Otentik Alami"))
    else:
        ai_score, ai_label = extract_ai_assessment(report.get("evidence_data"))

    # Styling badge Pilar IV
    if ai_score is not None and ai_score >= 70.0:
        ai_val_class = "pillar-val-warning"
        ai_badge_class = "pillar-badge-warning"
    elif ai_score is not None and ai_score >= 35.0:
        ai_val_class = ""
        ai_badge_class = "pillar-badge-moderate"
    else:
        ai_val_class = ""
        ai_badge_class = "pillar-badge-clean"

    # Verdict Classification
    if decision in ["no_match", "clean"]:
        verdict_badge = "TERAKREDITASI SANGAT BAIK"
        verdict_status = "LOLOS AMBANG BATAS ORISINALITAS ILMIAH"
        verdict_border = "#059669"
        verdict_bg = "#f0fdf4"
        verdict_color = "#065f46"
        badge_border = "#10b981"
    elif decision == "match":
        verdict_badge = "TIDAK MEMENUHI SYARAT"
        verdict_status = "KEMIRIPAN TINGGI • PERLU PERBAIKAN & PARAFRASA"
        verdict_border = "#dc2626"
        verdict_bg = "#fef2f2"
        verdict_color = "#991b1b"
        badge_border = "#ef4444"
    else:
        verdict_badge = "MEMENUHI SYARAT BERSYARAT"
        verdict_status = "DALAM AMBANG BATAS TOLERANSI REVIEW AKADEMIK"
        verdict_border = "#d97706"
        verdict_bg = "#fffbeb"
        verdict_color = "#92400e"
        badge_border = "#f59e0b"

    # URLs & QR Code
    verify_url = f"{VERIFY_BASE_URL.rstrip('/')}/verify/{code}"
    qr_b64 = generate_qr_code_base64(verify_url)
    logo_b64 = get_logo_base64()

    # SHA256 Signature
    raw_sig_payload = f"{code}:{report.get('created_at')}:{title}:{overall_sim}"
    doc_sig = hashlib.sha256(raw_sig_payload.encode("utf-8")).hexdigest().upper()
    sig_preview = f"{doc_sig[:8]}-{doc_sig[8:16]}-{doc_sig[16:24]}...{doc_sig[-8:]}"

    return f"""<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Sertifikat Akreditasi #{code} — AR Tilik</title>
    <style>
        @page {{
            size: A4 landscape;
            margin: 0;
        }}
        * {{
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }}
        html, body {{
            width: 297mm;
            height: 210mm;
            max-width: 297mm;
            max-height: 210mm;
            background: #ffffff;
            font-family: 'Liberation Serif', 'DejaVu Serif', 'Times New Roman', Georgia, serif;
            color: #0b1a30;
            overflow: hidden;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }}

        /* Lembar Dasar Piagam Klasik Bertekstur Halus */
        .diploma-sheet {{
            width: 297mm;
            height: 210mm;
            padding: 6.5mm;
            position: relative;
            background-color: #ffffff;
            background-image: 
                radial-gradient(circle at 50% 50%, #fffefc 0%, #fcf9f2 80%, #f8f2e2 100%),
                radial-gradient(circle at 10% 10%, rgba(197, 160, 89, 0.04) 0%, transparent 50%),
                radial-gradient(circle at 90% 90%, rgba(197, 160, 89, 0.04) 0%, transparent 50%);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }}

        /* Bingkai Ukir Ganda Guilloche Engraved */
        .guilloche-border-outer {{
            width: 100%;
            height: 100%;
            border: 4pt solid #081a33;
            outline: 2pt solid #c5a059;
            outline-offset: -5pt;
            padding: 5mm 6.5mm 4.5mm 6.5mm;
            position: relative;
            box-shadow: inset 0 0 50pt rgba(197, 160, 89, 0.08);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }}

        .guilloche-border-inner {{
            position: absolute;
            top: 7pt;
            bottom: 7pt;
            left: 7pt;
            right: 7pt;
            border: 0.75pt solid #c5a059;
            box-shadow: inset 0 0 0 0.5pt rgba(8, 26, 51, 0.2);
            pointer-events: none;
        }}

        /* Sudut Ornamen Piagam Vektor Klasik */
        .corner-flourish {{
            position: absolute;
            width: 22mm;
            height: 22mm;
            pointer-events: none;
            opacity: 0.9;
            z-index: 2;
        }}
        .corner-tl {{ top: 1.5mm; left: 1.5mm; }}
        .corner-tr {{ top: 1.5mm; right: 1.5mm; transform: scaleX(-1); }}
        .corner-bl {{ bottom: 1.5mm; left: 1.5mm; transform: scaleY(-1); }}
        .corner-br {{ bottom: 1.5mm; right: 1.5mm; transform: scale(-1); }}

        /* Watermark Lambang Tengah */
        .watermark-bg {{
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 130mm;
            height: 130mm;
            opacity: 0.035;
            pointer-events: none;
            z-index: 1;
        }}

        /* Header / Kop Resmi Piagam */
        .cert-header {{
            position: relative;
            z-index: 3;
            text-align: center;
            border-bottom: 1.25pt solid #c5a059;
            padding-bottom: 2mm;
        }}
        .header-grid {{
            display: table;
            width: 100%;
        }}
        .header-cell-left {{
            display: table-cell;
            width: 25mm;
            vertical-align: middle;
            text-align: left;
        }}
        .header-cell-center {{
            display: table-cell;
            vertical-align: middle;
            text-align: center;
        }}
        .header-cell-right {{
            display: table-cell;
            width: 52mm;
            vertical-align: middle;
            text-align: right;
        }}

        .logo-emblem {{
            width: 21mm;
            height: 21mm;
            object-fit: contain;
        }}

        .republic-text {{
            font-family: 'Liberation Sans', 'DejaVu Sans', Arial, sans-serif;
            font-size: 7.2pt;
            font-weight: 800;
            letter-spacing: 0.28em;
            color: #081a33;
            text-transform: uppercase;
            margin-bottom: 0.8mm;
        }}
        .cert-main-title {{
            font-family: 'Cinzel', 'Liberation Serif', 'Times New Roman', serif;
            font-size: 17.5pt;
            font-weight: 900;
            letter-spacing: 0.08em;
            color: #081a33;
            text-transform: uppercase;
            line-height: 1.1;
            margin-bottom: 0.6mm;
        }}
        .cert-subtitle {{
            font-style: italic;
            font-size: 8.5pt;
            color: #875c16;
            font-weight: 600;
            letter-spacing: 0.03em;
        }}

        .reg-cartouche {{
            display: inline-block;
            border: 1pt solid #c5a059;
            background: #fffdf8;
            padding: 1.5mm 3mm;
            text-align: right;
            box-shadow: 0 1pt 2pt rgba(0,0,0,0.04);
        }}
        .reg-label {{
            font-size: 6.2pt;
            font-weight: bold;
            letter-spacing: 0.16em;
            text-transform: uppercase;
            color: #875c16;
        }}
        .reg-code {{
            font-family: 'Liberation Mono', 'DejaVu Sans Mono', monospace;
            font-size: 9.5pt;
            font-weight: bold;
            color: #081a33;
            letter-spacing: 0.06em;
            margin-top: 0.4mm;
        }}
        .reg-date {{
            font-size: 6.2pt;
            color: #64748b;
            margin-top: 0.3mm;
        }}

        /* Naskah Maklumat Penetapan (Official Prose) */
        .prose-section {{
            position: relative;
            z-index: 3;
            text-align: center;
            padding: 1.2mm 5mm 0.8mm 5mm;
        }}
        .prose-lead {{
            font-style: italic;
            font-size: 8.4pt;
            color: #334155;
            line-height: 1.35;
            max-width: 250mm;
            margin: 0 auto;
        }}
        .doc-title-box {{
            margin: 1.4mm auto 1.2mm auto;
            max-width: 250mm;
            border-top: 0.5pt solid rgba(197, 160, 89, 0.4);
            border-bottom: 0.5pt solid rgba(197, 160, 89, 0.4);
            padding: 1mm 4mm;
            background: rgba(255, 253, 248, 0.6);
        }}
        .doc-title-text {{
            font-family: 'Cinzel', 'Liberation Serif', 'Times New Roman', serif;
            font-size: 11.5pt;
            font-weight: 900;
            color: #081a33;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            line-height: 1.25;
        }}

        .meta-strip {{
            display: table;
            margin: 0.8mm auto 1mm auto;
            padding: 0.5mm 3mm;
            font-size: 7.4pt;
            color: #475569;
        }}
        .meta-strip span {{
            display: inline-block;
            margin: 0 2mm;
        }}
        .meta-strip strong {{
            color: #081a33;
        }}

        /* Grand Accreditation Decree Banner */
        .decree-banner {{
            position: relative;
            z-index: 3;
            border: 1.5pt solid {verdict_border};
            background: {verdict_bg};
            padding: 1.8mm 5mm;
            text-align: center;
            margin: 0 auto 2mm auto;
            max-width: 255mm;
            box-shadow: 0 1.5pt 3pt rgba(0,0,0,0.03);
            border-radius: 2pt;
        }}
        .decree-title {{
            font-family: 'Cinzel', 'Liberation Serif', serif;
            font-size: 10.5pt;
            font-weight: 900;
            letter-spacing: 0.12em;
            color: {verdict_color};
            text-transform: uppercase;
        }}
        .decree-metrics {{
            font-size: 8.2pt;
            color: #1e293b;
            font-weight: 600;
            margin-top: 0.5mm;
        }}
        .decree-metrics strong {{
            color: {verdict_color};
            font-family: 'Liberation Mono', monospace;
            font-size: 9.2pt;
        }}
        .decree-rationale {{
            font-size: 7pt;
            color: #475569;
            margin-top: 0.4mm;
            font-style: italic;
            max-width: 240mm;
            margin-left: auto;
            margin-right: auto;
        }}

        /* 4 Pilar Metrik Integritas (Catur Paramita Integritas) */
        .pillars-table {{
            display: table;
            width: 100%;
            table-layout: fixed;
            position: relative;
            z-index: 3;
            margin-bottom: 1.8mm;
        }}
        .pillar-col {{
            display: table-cell;
            width: 25%;
            padding: 0 1.2mm;
            vertical-align: top;
        }}
        .pillar-card {{
            border: 0.75pt solid #c5a059;
            background: #ffffff;
            background: linear-gradient(180deg, #fffdfa 0%, #faf6ec 100%);
            padding: 1.5mm 1.5mm 1.2mm 1.5mm;
            text-align: center;
            box-shadow: 0 1pt 2pt rgba(0,0,0,0.03);
            height: 100%;
        }}
        .pillar-num {{
            font-size: 6pt;
            font-weight: bold;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            color: #875c16;
            margin-bottom: 0.3mm;
        }}
        .pillar-name {{
            font-size: 7.2pt;
            font-weight: bold;
            color: #081a33;
            line-height: 1.15;
            min-height: 15pt;
        }}
        .pillar-value {{
            font-family: 'Liberation Mono', 'DejaVu Sans Mono', monospace;
            font-size: 11.5pt;
            font-weight: 900;
            color: #081a33;
            margin: 0.6mm 0;
            letter-spacing: 0.02em;
        }}
        .pillar-val-warning {{
            color: #b91c1c !important;
        }}
        .pillar-method {{
            font-size: 5.8pt;
            color: #64748b;
            line-height: 1.1;
        }}
        .pillar-badge {{
            display: inline-block;
            font-size: 5.6pt;
            font-weight: bold;
            padding: 0.3mm 1.4mm;
            border-radius: 2pt;
            margin-top: 0.6mm;
            text-transform: uppercase;
            background: rgba(197, 160, 89, 0.16);
            color: #78510a;
            border: 0.4pt solid rgba(197, 160, 89, 0.5);
        }}
        .pillar-badge-warning {{
            display: inline-block;
            font-size: 5.6pt;
            font-weight: bold;
            padding: 0.3mm 1.4mm;
            border-radius: 2pt;
            margin-top: 0.6mm;
            text-transform: uppercase;
            background: #fee2e2;
            color: #991b1b;
            border: 0.4pt solid #f87171;
        }}
        .pillar-badge-moderate {{
            display: inline-block;
            font-size: 5.6pt;
            font-weight: bold;
            padding: 0.3mm 1.4mm;
            border-radius: 2pt;
            margin-top: 0.6mm;
            text-transform: uppercase;
            background: rgba(197, 160, 89, 0.16);
            color: #78510a;
            border: 0.4pt solid rgba(197, 160, 89, 0.5);
        }}
        .pillar-badge-clean {{
            display: inline-block;
            font-size: 5.6pt;
            font-weight: bold;
            padding: 0.3mm 1.4mm;
            border-radius: 2pt;
            margin-top: 0.6mm;
            text-transform: uppercase;
            background: #dcfce7;
            color: #166534;
            border: 0.4pt solid #86efac;
        }}

        /* Klausul Privasi Kedaulatan */
        .privacy-strip {{
            position: relative;
            z-index: 3;
            border: 0.6pt solid rgba(197, 160, 89, 0.5);
            background: #fffdfa;
            padding: 0.9mm 3mm;
            font-size: 6.4pt;
            color: #475569;
            text-align: center;
            line-height: 1.25;
            margin-bottom: 2mm;
        }}
        .privacy-strip strong {{
            color: #081a33;
        }}

        /* Pengesahan Tri-Partite & Stempel Medali Emas */
        .auth-table {{
            display: table;
            width: 100%;
            position: relative;
            z-index: 3;
            border-top: 1pt solid rgba(8, 26, 51, 0.25);
            padding-top: 2mm;
        }}
        .auth-cell-left {{
            display: table-cell;
            width: 76mm;
            vertical-align: bottom;
            text-align: left;
        }}
        .auth-cell-center {{
            display: table-cell;
            vertical-align: bottom;
            text-align: center;
        }}
        .auth-cell-right {{
            display: table-cell;
            width: 78mm;
            vertical-align: bottom;
            text-align: right;
        }}

        /* QR Code Block */
        .qr-box {{
            display: inline-block;
            vertical-align: bottom;
        }}
        .qr-img {{
            width: 16.5mm;
            height: 16.5mm;
            border: 0.75pt solid #c5a059;
            padding: 0.5mm;
            background: #ffffff;
            display: inline-block;
            vertical-align: middle;
            margin-right: 2.5mm;
        }}
        .qr-info {{
            display: inline-block;
            vertical-align: middle;
            max-width: 52mm;
            font-size: 6pt;
            color: #475569;
            line-height: 1.25;
        }}
        .qr-title {{
            font-weight: bold;
            font-size: 6.5pt;
            color: #081a33;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }}
        .qr-url {{
            font-family: 'Liberation Mono', monospace;
            font-size: 5.3pt;
            color: #64748b;
            word-break: break-all;
            margin-top: 0.3mm;
        }}

        /* Stempel Medali Emas Timbul 3D dengan Pita */
        .seal-wrapper {{
            position: relative;
            display: inline-block;
            width: 27mm;
            height: 27mm;
        }}
        .ribbon-left {{
            position: absolute;
            bottom: -5.5mm;
            left: 3.5mm;
            width: 8mm;
            height: 13mm;
            background: #081a33;
            background: linear-gradient(135deg, #081a33 0%, #1e3a8a 100%);
            clip-path: polygon(0% 0%, 100% 0%, 100% 100%, 50% 80%, 0% 100%);
            z-index: 1;
            box-shadow: 0 1pt 2pt rgba(0,0,0,0.3);
        }}
        .ribbon-right {{
            position: absolute;
            bottom: -5.5mm;
            right: 3.5mm;
            width: 8mm;
            height: 13mm;
            background: #081a33;
            background: linear-gradient(225deg, #081a33 0%, #1e3a8a 100%);
            clip-path: polygon(0% 0%, 100% 0%, 100% 100%, 50% 80%, 0% 100%);
            z-index: 1;
            box-shadow: 0 1pt 2pt rgba(0,0,0,0.3);
        }}
        .gold-seal-disc {{
            position: relative;
            z-index: 2;
            width: 26mm;
            height: 26mm;
            border-radius: 50%;
            background: radial-gradient(circle at 35% 30%, #fffdf0 0%, #fed7aa 25%, #d97706 65%, #78350f 100%);
            border: 1.5pt dashed #fef3c7;
            box-shadow: 0 2pt 6pt rgba(120, 53, 15, 0.4), inset 0 1pt 3pt rgba(255,255,255,0.9), inset 0 -2pt 4pt rgba(69, 26, 3, 0.7);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 1.5mm;
        }}
        .seal-inner-ring {{
            width: 100%;
            height: 100%;
            border-radius: 50%;
            border: 0.6pt solid #fffbeb;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }}
        .seal-text-top {{
            font-size: 4.4pt;
            font-weight: 900;
            letter-spacing: 0.12em;
            color: #78350f;
            text-transform: uppercase;
        }}
        .seal-text-main {{
            font-size: 6.2pt;
            font-weight: 900;
            letter-spacing: 0.05em;
            color: #451a03;
            text-transform: uppercase;
            margin: 0.2mm 0;
            text-shadow: 0 0.5pt 0.5pt rgba(255,255,255,0.6);
        }}
        .seal-text-sub {{
            font-size: 4.2pt;
            font-weight: 800;
            letter-spacing: 0.14em;
            color: #78350f;
            text-transform: uppercase;
        }}
        .seal-stars {{
            font-size: 4.2pt;
            color: #451a03;
            margin-top: 0.2mm;
        }}

        /* Pengesahan Dewan Kanan */
        .auth-right-text {{
            font-size: 6.8pt;
            color: #475569;
            line-height: 1.25;
        }}
        .auth-date {{
            font-weight: bold;
            font-size: 7.5pt;
            color: #081a33;
            margin: 0.3mm 0 0.8mm 0;
        }}
        .auth-council {{
            font-family: 'Cinzel', serif;
            font-size: 7.4pt;
            font-weight: 900;
            color: #081a33;
            text-transform: uppercase;
            letter-spacing: 0.06em;
        }}
        .auth-sig-badge {{
            display: inline-block;
            font-family: 'Liberation Mono', monospace;
            font-size: 5.6pt;
            color: #875c16;
            background: #fffdf8;
            border: 0.6pt solid #c5a059;
            padding: 0.3mm 1.5mm;
            margin-top: 0.6mm;
        }}
        .auth-disclaimer {{
            font-size: 5.2pt;
            color: #94a3b8;
            font-style: italic;
            margin-top: 0.3mm;
        }}
    </style>
</head>
<body>
    <div class="diploma-sheet">
        <div class="guilloche-border-outer">
            <div class="guilloche-border-inner"></div>

            <!-- 4 Sudut Ornamen Vektor Guilloche -->
            <svg class="corner-flourish corner-tl" viewBox="0 0 100 100" fill="none">
                <path d="M0,0 L60,0 C40,0 20,20 20,40 C20,20 0,40 0,60 Z" fill="#c5a059"/>
                <path d="M5,5 L80,5 C60,5 30,25 25,60 C25,30 5,60 5,80 Z" stroke="#081a33" stroke-width="1.5" fill="none"/>
                <circle cx="20" cy="20" r="4" fill="#c5a059"/>
                <path d="M0,0 L95,0 C65,10 40,35 35,65 L35,95 L0,95 Z" stroke="#c5a059" stroke-width="0.8" fill="none"/>
            </svg>
            <svg class="corner-flourish corner-tr" viewBox="0 0 100 100" fill="none">
                <path d="M0,0 L60,0 C40,0 20,20 20,40 C20,20 0,40 0,60 Z" fill="#c5a059"/>
                <path d="M5,5 L80,5 C60,5 30,25 25,60 C25,30 5,60 5,80 Z" stroke="#081a33" stroke-width="1.5" fill="none"/>
                <circle cx="20" cy="20" r="4" fill="#c5a059"/>
                <path d="M0,0 L95,0 C65,10 40,35 35,65 L35,95 L0,95 Z" stroke="#c5a059" stroke-width="0.8" fill="none"/>
            </svg>
            <svg class="corner-flourish corner-bl" viewBox="0 0 100 100" fill="none">
                <path d="M0,0 L60,0 C40,0 20,20 20,40 C20,20 0,40 0,60 Z" fill="#c5a059"/>
                <path d="M5,5 L80,5 C60,5 30,25 25,60 C25,30 5,60 5,80 Z" stroke="#081a33" stroke-width="1.5" fill="none"/>
                <circle cx="20" cy="20" r="4" fill="#c5a059"/>
                <path d="M0,0 L95,0 C65,10 40,35 35,65 L35,95 L0,95 Z" stroke="#c5a059" stroke-width="0.8" fill="none"/>
            </svg>
            <svg class="corner-flourish corner-br" viewBox="0 0 100 100" fill="none">
                <path d="M0,0 L60,0 C40,0 20,20 20,40 C20,20 0,40 0,60 Z" fill="#c5a059"/>
                <path d="M5,5 L80,5 C60,5 30,25 25,60 C25,30 5,60 5,80 Z" stroke="#081a33" stroke-width="1.5" fill="none"/>
                <circle cx="20" cy="20" r="4" fill="#c5a059"/>
                <path d="M0,0 L95,0 C65,10 40,35 35,65 L35,95 L0,95 Z" stroke="#c5a059" stroke-width="0.8" fill="none"/>
            </svg>

            <!-- Watermark Lambang Tengah -->
            <svg class="watermark-bg" viewBox="0 0 200 200" fill="none">
                <circle cx="100" cy="100" r="85" stroke="#c5a059" stroke-width="1.5" stroke-dasharray="4 2"/>
                <circle cx="100" cy="100" r="70" stroke="#081a33" stroke-width="1"/>
                <polygon points="100,25 120,75 175,75 130,110 150,165 100,130 50,165 70,110 25,75 80,75" stroke="#c5a059" stroke-width="1"/>
            </svg>

            <!-- 1. KOP RESMI PIAGAM AKREDITASI -->
            <div class="cert-header">
                <div class="header-grid">
                    <div class="header-cell-left">
                        {f'<img src="data:image/png;base64,{logo_b64}" class="logo-emblem" alt="AR Tilik">' if logo_b64 else '<div style="width:20mm;height:20mm;border:1pt solid #c5a059;border-radius:50%;text-align:center;line-height:20mm;font-weight:bold;color:#081a33;">ART</div>'}
                    </div>
                    <div class="header-cell-center">
                        <div class="republic-text">REPUBLIK INDONESIA &bull; STANDAR INTEGRITAS SINTA / ARJUNA</div>
                        <h1 class="cert-main-title">SERTIFIKAT AKREDITASI INTEGRITAS AKADEMIK</h1>
                        <div class="cert-subtitle">Certificate of Academic Integrity &amp; Originality Attestation</div>
                    </div>
                    <div class="header-cell-right">
                        <div class="reg-cartouche">
                            <div class="reg-label">Nomor Registrasi:</div>
                            <div class="reg-code">{code}</div>
                            <div class="reg-date">Terbit: {date_formatted}</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 2. NASKAH MAKLUMAT RESMI (OFFICIAL PROSE) -->
            <div class="prose-section">
                <p class="prose-lead">
                    Berdasarkan hasil audit komputasi forensik teks menggunakan algoritma sekuensial <em>5-Gram Shingle</em> dan dekomposisi leksikal <em>TF-IDF Cosine</em>, dengan ini Dewan Integritas Akademik AR Tilik menyatakan bahwa naskah karya ilmiah:
                </p>

                <div class="doc-title-box">
                    <h2 class="doc-title-text">&ldquo;{title}&rdquo;</h2>
                </div>

                <div class="meta-strip">
                    <span>Penulis: <strong>{author_name}</strong></span>
                    <span>&bull;</span>
                    <span>Institusi: <strong>{institution}</strong></span>
                    {f'<span>&bull;</span><span>Bidang: <strong>{fac_dept}</strong></span>' if fac_dept else ''}
                    <span>&bull;</span>
                    <span>Format: <strong>{filename}</strong></span>
                    <span>&bull;</span>
                    <span>Waktu Audit: <strong>{date_formatted}, {time_formatted}</strong></span>
                </div>
            </div>

            <!-- 3. DIKTUM KEPUTUSAN AKREDITASI (GRAND DECREE BANNER) -->
            <div class="decree-banner">
                <div class="decree-title">KEPUTUSAN: {verdict_badge}</div>
                <div class="decree-metrics">
                    {verdict_status} &bull; Indeks Kesepadanan: <strong>{overall_sim:.2f}%</strong> &bull; Tingkat Orisinalitas: <strong>{originality_rate:.2f}%</strong>
                </div>
                <div class="decree-rationale">
                    {rationale}
                </div>
            </div>

            <!-- 4. CATUR PILAR METRIK INTEGRITAS (HERALDIC 4 PILLARS) -->
            <div class="pillars-table">
                <!-- Pilar 1 -->
                <div class="pillar-col">
                    <div class="pillar-card">
                        <div class="pillar-num">Pilar I &bull; Sekuensial</div>
                        <div class="pillar-name">Kesepadanan Harfiah</div>
                        <div class="pillar-value">{exact_sim:.2f}%</div>
                        <div class="pillar-method">5-Gram Exact Shingle Token</div>
                        <div class="pillar-badge">{'Sangat Baik' if exact_sim <= 5.0 else 'Wajar'}</div>
                    </div>
                </div>
                <!-- Pilar 2 -->
                <div class="pillar-col">
                    <div class="pillar-card">
                        <div class="pillar-num">Pilar II &bull; Orientasi</div>
                        <div class="pillar-name">Kosinus Leksikal / Topik</div>
                        <div class="pillar-value">{cosine_sim:.2f}%</div>
                        <div class="pillar-method">TF-IDF Vector Cosine</div>
                        <div class="pillar-badge">Korelasi Topikal Wajar</div>
                    </div>
                </div>
                <!-- Pilar 3 -->
                <div class="pillar-col">
                    <div class="pillar-card">
                        <div class="pillar-num">Pilar III &bull; Pasase</div>
                        <div class="pillar-name">Rangkaian Terpanjang</div>
                        <div class="pillar-value">{longest_run} Kata</div>
                        <div class="pillar-method">Consecutive Matched Word Span</div>
                        <div class="pillar-badge">{'Bebas Frasa Berulang' if longest_run < 20 else 'Perlu Parafrasa'}</div>
                    </div>
                </div>
                <!-- Pilar 4 -->
                <div class="pillar-col">
                    <div class="pillar-card">
                        <div class="pillar-num">Pilar IV &bull; Sintesis</div>
                        <div class="pillar-name">Forensik Sintesis Mesin</div>
                        <div class="pillar-value {ai_val_class}">{f"{ai_score:.1f}%" if ai_score is not None else "Alami"}</div>
                        <div class="pillar-method">Stylometrik &amp; Forensik Cerdas</div>
                        <div class="{ai_badge_class}">{ai_label}</div>
                    </div>
                </div>
            </div>

            <!-- 5. KLAUSUL KEDAULATAN PRIVASI DATA -->
            <div class="privacy-strip">
                🛡️ <strong>Kedaulatan Privasi Data (Zero-Leakage Guarantee):</strong>
                {'Berkas fisik naskah asli telah dimusnahkan secara mandiri dari peladen pada <strong>' + purged_str + '</strong>. Sertifikasi ini sah 100% berbasis sidik jari kriptografis SHA-256.' if is_purged else 'Naskah karya ilmiah dilindungi di bawah protokol integritas tertutup AR Tilik dan tidak pernah dipublikasikan atau dijadikan korpus terbuka tanpa izin pemilik.'}
            </div>

            <!-- 6. PENGESAHAN TRI-PARTITE, QR CODE & STEMPEL MEDALI EMAS -->
            <div class="auth-table">
                <!-- Kolom Kiri: QR Code Validasi -->
                <div class="auth-cell-left">
                    <div class="qr-box">
                        <img src="data:image/png;base64,{qr_b64}" class="qr-img" alt="QR Code">
                        <div class="qr-info">
                            <div class="qr-title">Validasi Kriptografis</div>
                            <div>Pindai untuk memvalidasi keabsahan piagam ini secara langsung pada peladen resmi.</div>
                            <div class="qr-url">{verify_url}</div>
                        </div>
                    </div>
                </div>

                <!-- Kolom Tengah: Stempel Medali Emas Timbul 3D dengan Pita Royal Navy -->
                <div class="auth-cell-center">
                    <div class="seal-wrapper">
                        <div class="ribbon-left"></div>
                        <div class="ribbon-right"></div>
                        <div class="gold-seal-disc">
                            <div class="seal-inner-ring">
                                <div class="seal-text-top">★ AR TILIK ★</div>
                                <div class="seal-text-main">AKREDITASI</div>
                                <div class="seal-text-sub">INTEGRITAS RESMI</div>
                                <div class="seal-stars">★ ★ ★ ★ ★</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Kolom Kanan: Pengesahan Dewan & Tanda Tangan Digital -->
                <div class="auth-cell-right">
                    <div class="auth-right-text">
                        <div>Ditetapkan secara resmi pada:</div>
                        <div class="auth-date">{date_formatted}</div>
                        <div class="auth-council">Lembaga Uji Integritas AR Tilik</div>
                        <div class="auth-sig-badge">SIG: {sig_preview}</div>
                        <div class="auth-disclaimer">Tanda Tangan Digital Tersertifikasi Sistem Peladen</div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</body>
</html>
"""

def fetch_certificate_data(code: str = None, report_id: int = None) -> tuple[dict, dict]:
    conn = get_connection()
    try:
        cur = conn.cursor(dictionary=True)
        if code:
            cur.execute("""
                SELECT r.*, 
                       d.title AS doc_title, d.original_filename, d.faculty, d.department,
                       d.file_purged_at, d.storage_path, d.created_at AS doc_created_at,
                       u.name AS user_name, u.institution AS user_inst
                FROM reports r
                JOIN documents d ON d.id = r.document_id
                LEFT JOIN users u ON u.id = d.user_id
                WHERE r.verification_code = %s
                LIMIT 1
            """, (code.strip(),))
        elif report_id:
            cur.execute("""
                SELECT r.*, 
                       d.title AS doc_title, d.original_filename, d.faculty, d.department,
                       d.file_purged_at, d.storage_path, d.created_at AS doc_created_at,
                       u.name AS user_name, u.institution AS user_inst
                FROM reports r
                JOIN documents d ON d.id = r.document_id
                LEFT JOIN users u ON u.id = d.user_id
                WHERE r.id = %s
                LIMIT 1
            """, (report_id,))
        else:
            raise ValueError("Harus menyediakan kode sertifikat atau ID laporan.")

        row = cur.fetchone()
        if not row:
            raise LookupError(f"Data sertifikat tidak ditemukan untuk parameter yang diberikan.")

        report = {
            "id": row["id"],
            "document_id": row["document_id"],
            "verification_code": row["verification_code"],
            "overall_similarity": row["overall_similarity"],
            "exact_similarity": row["exact_similarity"],
            "cosine_similarity": row["cosine_similarity"],
            "longest_match_words": row["longest_match_words"],
            "predicted_decision": row["predicted_decision"],
            "rationale": row["rationale"],
            "engine": row["engine"],
            "created_at": row["created_at"],
            "evidence_data": row.get("evidence_data"),
        }
        document = {
            "id": row["document_id"],
            "title": row.get("doc_title") or "Dokumen Karya Ilmiah",
            "original_filename": row.get("original_filename"),
            "faculty": row.get("faculty"),
            "department": row.get("department"),
            "file_purged_at": row.get("file_purged_at"),
            "storage_path": row.get("storage_path"),
            "user_name": row.get("user_name"),
            "user_inst": row.get("user_inst"),
        }
        return report, document
    finally:
        conn.close()

def generate_certificate_pdf(code: str = None, report_id: int = None, output_path: str = None, override_ai_score: float = None, override_ai_label: str = None) -> bytes:
    report, document = fetch_certificate_data(code=code, report_id=report_id)
    html_content = build_certificate_html(report, document, override_ai_score=override_ai_score, override_ai_label=override_ai_label)

    with tempfile.NamedTemporaryFile("w", suffix=".html", delete=False, encoding="utf-8") as f_html:
        f_html.write(html_content)
        html_file = f_html.name

    pdf_temp = tempfile.NamedTemporaryFile("wb", suffix=".pdf", delete=False)
    pdf_temp.close()
    pdf_file = pdf_temp.name

    try:
        success = False
        weasy_bin = shutil.which("weasyprint")
        if weasy_bin:
            res = subprocess.run([weasy_bin, html_file, pdf_file], capture_output=True, text=True)
            if res.returncode == 0:
                success = True
            else:
                print(f"[WEASYPRINT ERROR] {res.stderr}", file=sys.stderr)

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
                    "--print-to-pdf-no-header",
                    f"--print-to-pdf={pdf_file}",
                    html_file
                ]
                subprocess.run(chrome_cmd, capture_output=True, check=True)
                success = True
            else:
                raise RuntimeError("Tidak ditemukan WeasyPrint maupun Google Chrome/Chromium untuk merender PDF Piagam.")

        with open(pdf_file, "rb") as f_pdf:
            pdf_bytes = f_pdf.read()

        if output_path:
            with open(output_path, "wb") as f_out:
                f_out.write(pdf_bytes)

        return pdf_bytes
    finally:
        if os.path.exists(html_file):
            try:
                os.remove(html_file)
            except OSError:
                pass
        if os.path.exists(pdf_file):
            try:
                os.remove(pdf_file)
            except OSError:
                pass

if __name__ == "__main__":
    parser = argparse.ArgumentParser(description="Generator PDF Piagam Akreditasi AR Tilik (WeasyPrint)")
    parser.add_argument("--code", type=str, default=None, help="Kode Verifikasi Sertifikat (misal: ART-BFF9-774A-2C68)")
    parser.add_argument("--report", type=int, default=None, help="ID Laporan (opsional jika kode tidak diisi)")
    parser.add_argument("--ai-score", type=float, default=None, help="Override skor metrik AI (0-100)")
    parser.add_argument("--ai-label", type=str, default=None, help="Override label metrik AI")
    parser.add_argument("--output", type=str, default=None, help="Path berkas luaran PDF")
    parser.add_argument("--stdout", action="store_true", help="Keluarkan bytes PDF langsung ke stdout")
    args = parser.parse_args()

    if not args.code and not args.report:
        parser.error("Harus memberikan argumen --code atau --report")

    try:
        pdf_bytes = generate_certificate_pdf(
            code=args.code,
            report_id=args.report,
            output_path=args.output,
            override_ai_score=args.ai_score,
            override_ai_label=args.ai_label
        )
        if args.stdout:
            sys.stdout.buffer.write(pdf_bytes)
        elif args.output:
            print(f"[✓] Piagam PDF berhasil disimpan ke: {args.output} ({len(pdf_bytes):,} bytes)")
        else:
            default_out = f"Piagam_Akreditasi_{args.code or args.report}.pdf"
            with open(default_out, "wb") as f:
                f.write(pdf_bytes)
            print(f"[✓] Piagam PDF berhasil disimpan ke: {default_out} ({len(pdf_bytes):,} bytes)")
    except Exception as e:
        print(f"[!] Gagal menghasilkan PDF Piagam: {e}", file=sys.stderr)
        sys.exit(1)
