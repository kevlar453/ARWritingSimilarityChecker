#!/usr/bin/env python3
"""
Generator PDF Piagam Resmi Duta Kampus AR Tilik
Menghasilkan dokumen Piagam Penghargaan Duta Akademik & Integritas Ilmiah (A4 Landscape)
lengkap dengan ornamen guilloche klasik, stempel emas timbul, tanda tangan digital,
dan QR Code verifikasi publik kriptografis.
"""

import argparse
import base64
import hashlib
import io
import json
import os
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
        print(f"[AMBASSADOR CERTIFICATE WARNING] Gagal menghasilkan QR code: {e}", file=sys.stderr)
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


def format_date_id(dt_val) -> str:
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

    return f"{dt.day:02d} {months_id[dt.month]} {dt.year}"


def fetch_ambassador_data(code: str = None, profile_id: int = None, user_id: int = None) -> dict:
    conn = get_connection()
    try:
        try:
            cursor = conn.cursor(dictionary=True)
        except TypeError:
            cursor = conn.cursor()

        query = """
            SELECT 
                ap.id as profile_id,
                ap.user_id,
                ap.application_id,
                ap.referral_code,
                ap.status,
                ap.certificate_code,
                ap.certificate_hash,
                ap.graduated_at,
                ap.probation_start,
                ap.probation_end,
                ap.created_at as profile_created_at,
                aa.full_name as app_full_name,
                aa.institution as app_institution,
                aa.major as app_major,
                aa.semester as app_semester,
                u.name as user_name,
                u.email as user_email,
                u.institution as user_institution
            FROM ambassador_profiles ap
            LEFT JOIN ambassador_applications aa ON aa.id = ap.application_id
            LEFT JOIN users u ON u.id = ap.user_id
            WHERE 1=1
        """
        params = []
        if code:
            query += " AND (ap.certificate_code = %s OR ap.referral_code = %s)"
            params.extend([code, code])
        elif profile_id:
            query += " AND ap.id = %s"
            params.append(profile_id)
        elif user_id:
            query += " AND ap.user_id = %s"
            params.append(user_id)
        else:
            raise ValueError("Harus menyediakan code, profile_id, atau user_id.")

        query += " LIMIT 1"
        cursor.execute(query, tuple(params))
        row = cursor.fetchone()
        if not row:
            raise ValueError(f"Profil Duta Kampus tidak ditemukan (Parameter: code={code}, profile_id={profile_id}, user_id={user_id})")

        # Jika tuple (fallback), mapping kan ke dict
        if isinstance(row, (tuple, list)):
            col_names = [d[0] for d in cursor.description]
            row = dict(zip(col_names, row))

        # Hitung total bukti yang disetujui
        cursor.execute(
            "SELECT count(*) as total FROM ambassador_activity_proofs WHERE ambassador_id = %s AND status = 'approved'",
            (row["profile_id"],)
        )
        proof_row = cursor.fetchone()
        if isinstance(proof_row, (tuple, list)):
            row["approved_proofs_count"] = proof_row[0] if proof_row else 0
        elif isinstance(proof_row, dict):
            row["approved_proofs_count"] = proof_row.get("total", 0)
        else:
            row["approved_proofs_count"] = 0

        return row
    finally:
        conn.close()


def build_ambassador_certificate_html(amb: dict) -> str:
    logo_b64 = get_logo_base64()
    logo_src = f"data:image/png;base64,{logo_b64}" if logo_b64 else ""

    cert_code = amb.get("certificate_code")
    if not cert_code:
        # Jika belum ada kode, gunakan default format
        ref_clean = amb.get("referral_code", "AMB").replace("TILIK-", "")
        cert_code = f"ART-AMB-{ref_clean}"

    grad_date = amb.get("graduated_at") or amb.get("profile_created_at") or datetime.now()
    grad_date_str = format_date_id(grad_date)

    now_dt = datetime.now()
    sk_number = f"SK-DUTA/ARTILIK/{now_dt.year}/{now_dt.month:02d}/{cert_code}"

    # Verifikasi URL publik
    verify_url = f"{VERIFY_BASE_URL.rstrip('/')}/ambassador/certificate/{cert_code}"
    qr_b64 = generate_qr_code_base64(verify_url)
    qr_src = f"data:image/png;base64,{qr_b64}" if qr_b64 else ""

    # Nama duta & Afiliasi
    full_name = amb.get("app_full_name") or amb.get("user_name") or "Duta Akademik"
    institution = amb.get("app_institution") or amb.get("user_institution") or "Perguruan Tinggi"
    major = amb.get("app_major") or "Ilmu Terapan & Akademik"
    ref_code = amb.get("referral_code", "-")

    # Hash integritas
    cert_hash = amb.get("certificate_hash")
    if not cert_hash:
        raw_hash_data = f"ARTILIK_AMBASSADOR|{amb.get('profile_id')}|{cert_code}|{grad_date}"
        cert_hash = hashlib.sha256(raw_hash_data.encode("utf-8")).hexdigest()

    return f"""<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Piagam Penghargaan Duta Kampus - {full_name}</title>
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

        /* Lembar Dasar Piagam Tekstur Ivory Klasik */
        .diploma-sheet {{
            width: 297mm;
            height: 210mm;
            padding: 7mm;
            position: relative;
            background-color: #ffffff;
            background-image: 
                radial-gradient(circle at 50% 50%, #fffefc 0%, #fcf9f2 80%, #f7f1df 100%),
                radial-gradient(circle at 10% 10%, rgba(197, 160, 89, 0.05) 0%, transparent 50%),
                radial-gradient(circle at 90% 90%, rgba(197, 160, 89, 0.05) 0%, transparent 50%);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }}

        /* Bingkai Ukir Ganda Guilloche Diplomatik */
        .guilloche-border-outer {{
            width: 100%;
            height: 100%;
            border: 4pt solid #081a33;
            outline: 2pt solid #c5a059;
            outline-offset: -5pt;
            padding: 5mm 7mm 4mm 7mm;
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
            pointer-events: none;
        }}

        /* Sudut Ornamen Klasik Emas (Corner Rosettes) */
        .corner-rosette {{
            position: absolute;
            width: 32pt;
            height: 32pt;
            z-index: 10;
        }}
        .corner-tl {{ top: 1pt; left: 1pt; border-top: 3pt solid #c5a059; border-left: 3pt solid #c5a059; }}
        .corner-tr {{ top: 1pt; right: 1pt; border-top: 3pt solid #c5a059; border-right: 3pt solid #c5a059; }}
        .corner-bl {{ bottom: 1pt; left: 1pt; border-bottom: 3pt solid #c5a059; border-left: 3pt solid #c5a059; }}
        .corner-br {{ bottom: 1pt; right: 1pt; border-bottom: 3pt solid #c5a059; border-right: 3pt solid #c5a059; }}

        /* Watermark Transparan di Pusat Piagam */
        .watermark-emblem {{
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 115mm;
            opacity: 0.038;
            z-index: 1;
            pointer-events: none;
        }}

        /* Header Piagam */
        .cert-header {{
            position: relative;
            z-index: 2;
            text-align: center;
            border-bottom: 1.5pt solid #c5a059;
            padding-bottom: 3.5mm;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 5mm;
        }}
        .header-logo {{
            width: 18mm;
            height: 18mm;
            object-fit: contain;
        }}
        .header-text-block {{
            flex: 1;
            text-align: center;
        }}
        .org-republic {{
            font-size: 8.5pt;
            font-weight: 700;
            letter-spacing: 2.8pt;
            color: #081a33;
            text-transform: uppercase;
            font-family: 'Liberation Sans', 'DejaVu Sans', Arial, sans-serif;
        }}
        .org-council {{
            font-size: 11pt;
            font-weight: 900;
            letter-spacing: 1.2pt;
            color: #081a33;
            text-transform: uppercase;
            margin-top: 0.8mm;
        }}
        .org-sub {{
            font-size: 7pt;
            font-weight: 600;
            letter-spacing: 1.8pt;
            color: #8c6d2d;
            text-transform: uppercase;
            font-family: 'Liberation Sans', 'DejaVu Sans', Arial, sans-serif;
            margin-top: 0.6mm;
        }}
        .header-meta {{
            text-align: right;
            font-family: 'Courier New', Courier, monospace;
            font-size: 7pt;
            color: #475569;
            line-height: 1.35;
        }}
        .sk-badge {{
            display: inline-block;
            background: #081a33;
            color: #ffffff;
            font-weight: bold;
            padding: 1.5pt 5pt;
            border-radius: 2pt;
            letter-spacing: 0.5pt;
            margin-top: 1mm;
        }}

        /* Bagian Konten Utama Piagam */
        .cert-body {{
            position: relative;
            z-index: 2;
            text-align: center;
            padding: 2.5mm 6mm;
            display: flex;
            flex-direction: column;
            justify-content: center;
            gap: 2.2mm;
        }}
        .title-prelude {{
            font-size: 8pt;
            letter-spacing: 3pt;
            text-transform: uppercase;
            color: #8c6d2d;
            font-family: 'Liberation Sans', 'DejaVu Sans', Arial, sans-serif;
            font-weight: 700;
        }}
        .cert-main-title {{
            font-size: 21pt;
            font-weight: 900;
            letter-spacing: 2.5pt;
            color: #081a33;
            text-transform: uppercase;
            line-height: 1.1;
            text-shadow: 0 1px 1px rgba(0,0,0,0.05);
        }}
        .cert-sub-title {{
            font-size: 9.5pt;
            font-style: italic;
            color: #8c6d2d;
            letter-spacing: 1pt;
            font-weight: 500;
            margin-top: -1mm;
        }}

        .awarded-to-label {{
            font-size: 8.5pt;
            font-style: italic;
            color: #334155;
            margin-top: 1mm;
        }}
        .recipient-name {{
            font-size: 22pt;
            font-weight: 900;
            letter-spacing: 1pt;
            color: #081a33;
            text-transform: uppercase;
            border-bottom: 1.2pt solid #c5a059;
            display: inline-block;
            padding: 0 8mm 1mm 8mm;
            margin: 0 auto;
            max-width: 90%;
        }}
        .recipient-affiliation {{
            font-size: 11pt;
            font-weight: 700;
            color: #1e293b;
            letter-spacing: 0.5pt;
            font-family: 'Liberation Sans', 'DejaVu Sans', Arial, sans-serif;
            margin-top: 0.5mm;
        }}
        .ref-pill {{
            display: inline-block;
            background: rgba(197, 160, 89, 0.15);
            border: 0.75pt solid #c5a059;
            color: #081a33;
            font-family: 'Courier New', Courier, monospace;
            font-size: 8pt;
            font-weight: bold;
            padding: 1pt 6pt;
            border-radius: 3pt;
            margin-top: 0.5mm;
        }}

        .narrative-box {{
            max-width: 86%;
            margin: 0 auto;
            font-size: 8.5pt;
            line-height: 1.45;
            color: #1e293b;
            text-align: center;
        }}
        .honor-badge {{
            display: inline-block;
            background: linear-gradient(135deg, #081a33 0%, #1e293b 100%);
            color: #f6e05e;
            font-family: 'Liberation Sans', 'DejaVu Sans', Arial, sans-serif;
            font-size: 8pt;
            font-weight: 800;
            letter-spacing: 1pt;
            text-transform: uppercase;
            padding: 2.5pt 10pt;
            border-radius: 30pt;
            border: 1pt solid #c5a059;
            box-shadow: 0 2pt 4pt rgba(0,0,0,0.12);
            margin-top: 0.5mm;
        }}

        /* Bagian Bawah: QR, Segel Emas, Tanda Tangan */
        .cert-footer {{
            position: relative;
            z-index: 2;
            border-top: 1pt solid #cbd5e1;
            padding-top: 2.5mm;
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 4mm;
        }}

        /* Kolom Kiri: QR Code & Validasi Publik */
        .footer-qr-box {{
            width: 60mm;
            display: flex;
            align-items: center;
            gap: 2.5mm;
        }}
        .qr-img {{
            width: 17mm;
            height: 17mm;
            border: 1pt solid #c5a059;
            padding: 1pt;
            background: #ffffff;
        }}
        .qr-caption {{
            font-size: 6pt;
            color: #475569;
            line-height: 1.25;
            font-family: 'Liberation Sans', 'DejaVu Sans', Arial, sans-serif;
        }}
        .qr-caption strong {{
            color: #081a33;
            display: block;
            font-size: 6.5pt;
            margin-bottom: 0.5mm;
        }}

        /* Kolom Tengah: Segel Emas Timbul Resmi */
        .footer-seal-box {{
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
        }}
        .gold-seal {{
            width: 25mm;
            height: 25mm;
            border-radius: 50%;
            background: radial-gradient(circle, #f9f0d3 0%, #d8b26e 65%, #a88134 100%);
            border: 2pt dashed #ffffff;
            box-shadow: 0 0 0 2pt #c5a059, 0 3pt 8pt rgba(140, 109, 45, 0.35);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 2mm;
            position: relative;
        }}
        .seal-stars {{
            font-size: 5.5pt;
            color: #081a33;
            letter-spacing: 1.5pt;
        }}
        .seal-title {{
            font-size: 5.5pt;
            font-weight: 900;
            color: #081a33;
            text-transform: uppercase;
            font-family: 'Liberation Sans', 'DejaVu Sans', Arial, sans-serif;
            letter-spacing: 0.4pt;
            line-height: 1.15;
            margin: 0.8mm 0;
        }}
        .seal-ribbon {{
            font-size: 5pt;
            font-weight: 800;
            color: #ffffff;
            background: #081a33;
            padding: 0.8pt 4pt;
            border-radius: 2pt;
            letter-spacing: 0.5pt;
        }}

        /* Kolom Kanan: Tanda Tangan Pejabat */
        .footer-sig-box {{
            width: 75mm;
            text-align: right;
            font-family: 'Liberation Sans', 'DejaVu Sans', Arial, sans-serif;
        }}
        .sig-city-date {{
            font-size: 7.5pt;
            color: #334155;
            margin-bottom: 1.5mm;
        }}
        .sig-title {{
            font-size: 7.5pt;
            font-weight: bold;
            color: #081a33;
            text-transform: uppercase;
            margin-bottom: 7mm;
        }}
        .sig-name {{
            font-size: 9pt;
            font-weight: 800;
            color: #081a33;
            text-decoration: underline;
        }}
        .sig-role {{
            font-size: 7pt;
            color: #475569;
            margin-top: 0.5mm;
        }}

        /* Baris Pengaman Paling Bawah (Security Footer) */
        .security-strip {{
            position: relative;
            z-index: 2;
            font-family: 'Courier New', Courier, monospace;
            font-size: 5.5pt;
            color: #64748b;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-top: 0.5pt solid #e2e8f0;
            padding-top: 1mm;
            margin-top: 1mm;
        }}
    </style>
</head>
<body>
    <div class="diploma-sheet">
        <div class="guilloche-border-outer">
            <div class="guilloche-border-inner"></div>
            <div class="corner-rosette corner-tl"></div>
            <div class="corner-rosette corner-tr"></div>
            <div class="corner-rosette corner-bl"></div>
            <div class="corner-rosette corner-br"></div>

            {f'<img src="{logo_src}" class="watermark-emblem" alt="Emblem">' if logo_src else ''}

            <!-- 1. Header Piagam Formal -->
            <div class="cert-header">
                {f'<img src="{logo_src}" class="header-logo" alt="AR Tilik">' if logo_src else '<div style="width:18mm;"></div>'}
                <div class="header-text-block">
                    <div class="org-republic">REPUBLIK INDONESIA</div>
                    <div class="org-council">LEMBAGA PENGEMBANGAN INTEGRITAS AKADEMIK &amp; SAINS</div>
                    <div class="org-sub">AR TILIK ACADEMIC AMBASSADORSHIP CONSORTIUM &bull; DEWAN KEHORMATAN KEMITRAAN ILMIAH</div>
                </div>
                <div class="header-meta">
                    <div>TEREGISTRASI RESMI</div>
                    <div>STATUS: <strong>TERVERIFIKASI</strong></div>
                    <div class="sk-badge">{sk_number}</div>
                </div>
            </div>

            <!-- 2. Isi Piagam Penghargaan -->
            <div class="cert-body">
                <div class="title-prelude">PIAGAM PENGHARGAAN DAN KEHORMATAN AKADEMIK</div>
                <div class="cert-main-title">DUTA AKADEMIK &amp; INTEGRITAS ILMIAH</div>
                <div class="cert-sub-title">Certificate of Academic Ambassadorship and Scientific Research Integrity</div>

                <div class="awarded-to-label">Diberikan dengan penuh kehormatan dan apresiasi tertinggi kepada:</div>
                <div class="recipient-name">{full_name}</div>

                <div class="recipient-affiliation">
                    {major} &bull; {institution}
                </div>
                <div>
                    <span class="ref-pill">KODE KEHORMATAN DUTA: {ref_code}</span>
                </div>

                <div class="narrative-box">
                    Atas komitmen, integritas, dan dedikasi luar biasa dalam memelopori penegakan kejujuran karya ilmiah, pencegahan plagiarisme akademik, serta advokasi pemanfaatan teknologi kecerdasan buatan secara etis dan bertanggung jawab di lingkungan sivitas akademika perguruan tinggi.
                </div>

                <div>
                    <span class="honor-badge">&#9733; TELAH MENYELESAIKAN MASA ADVOKASI DENGAN PREDIKAT SANGAT MEMUASKAN &#9733;</span>
                </div>
            </div>

            <!-- 3. Footer: QR Code, Segel Emas, Tanda Tangan Pejabat -->
            <div class="cert-footer">
                <!-- QR Code Validasi -->
                <div class="footer-qr-box">
                    {f'<img src="{qr_src}" class="qr-img" alt="QR Code">' if qr_src else ''}
                    <div class="qr-caption">
                        <strong>VERIFIKASI DIGITAL</strong>
                        Pindai kode QR untuk memverifikasi keabsahan piagam ini secara publik pada portal integritas resmi AR Tilik.
                    </div>
                </div>

                <!-- Segel Emas Timbul -->
                <div class="footer-seal-box">
                    <div class="gold-seal">
                        <div class="seal-stars">&#9733; &#9733; &#9733;</div>
                        <div class="seal-title">OFFICIAL COLLEGIATE AMBASSADOR</div>
                        <div class="seal-ribbon">AR TILIK 2026</div>
                    </div>
                </div>

                <!-- Tanda Tangan -->
                <div class="footer-sig-box">
                    <div class="sig-city-date">Ditetapkan di Jakarta, {grad_date_str}</div>
                    <div class="sig-title">DEWAN PEMBINA INTEGRITAS AKADEMIK</div>
                    <div class="sig-name">Prof. Dr. Ir. M. Makarius, M.Sc.</div>
                    <div class="sig-role">Direktur Kemitraan Kampus &amp; Integritas Ilmiah</div>
                </div>
            </div>

            <!-- 4. Security Fingerprint Strip -->
            <div class="security-strip">
                <span>DIGITAL SECURITY SHA-256: {cert_hash[:32]}...{cert_hash[-16:]}</span>
                <span>VERIFIKASI PUBLIK: {verify_url}</span>
                <span>ORIGIN: INDONESIAN SCIENTIFIC REPOSITORY NETWORK</span>
            </div>
        </div>
    </div>
</body>
</html>
"""


def generate_ambassador_certificate_pdf(
    code: str = None,
    profile_id: int = None,
    user_id: int = None,
    output_path: str = None
) -> bytes:
    amb = fetch_ambassador_data(code=code, profile_id=profile_id, user_id=user_id)
    html_content = build_ambassador_certificate_html(amb)

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
                raise RuntimeError("Tidak ditemukan WeasyPrint maupun Google Chrome/Chromium untuk merender PDF Piagam Duta.")

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
    parser = argparse.ArgumentParser(description="Generator PDF Piagam Penghargaan Duta Kampus AR Tilik")
    parser.add_argument("--code", type=str, default=None, help="Kode Sertifikat Duta / Kode Referral")
    parser.add_argument("--profile-id", type=int, default=None, help="ID Profil Duta Kampus")
    parser.add_argument("--user-id", type=int, default=None, help="ID Pengguna Duta Kampus")
    parser.add_argument("--output", type=str, default=None, help="Path berkas luaran PDF")
    parser.add_argument("--stdout", action="store_true", help="Keluarkan bytes PDF langsung ke stdout")
    args = parser.parse_args()

    if not args.code and not args.profile_id and not args.user_id:
        parser.error("Harus memberikan salah satu argumen: --code, --profile-id, atau --user-id")

    try:
        pdf_bytes = generate_ambassador_certificate_pdf(
            code=args.code,
            profile_id=args.profile_id,
            user_id=args.user_id,
            output_path=args.output
        )
        if args.stdout:
            sys.stdout.buffer.write(pdf_bytes)
        elif args.output:
            print(f"[✓] Piagam Duta Kampus PDF berhasil disimpan ke: {args.output} ({len(pdf_bytes):,} bytes)")
        else:
            default_out = f"Piagam_Duta_ARTilik_{args.code or args.profile_id or args.user_id}.pdf"
            with open(default_out, "wb") as f:
                f.write(pdf_bytes)
            print(f"[✓] Piagam Duta Kampus PDF berhasil disimpan ke: {default_out} ({len(pdf_bytes):,} bytes)")
    except Exception as e:
        print(f"[!] Gagal menghasilkan PDF Piagam Duta Kampus: {e}", file=sys.stderr)
        sys.exit(1)
