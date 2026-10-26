"""
AR Tilik - Real-Time Email Notifier
Mengirimkan notifikasi email otomatis ke pengguna saat laporan hasil pemeriksaan similarity selesai dianalisis.
"""

import os
import smtplib
import ssl
from email.message import EmailMessage
from typing import Optional

from worker.database import get_connection


def notify_user_report_completed(
    document_id: int,
    report_id: int,
    document_title: str,
    decision: str,
    overall_similarity: float,
    exact_similarity: float,
    cosine_similarity: float,
    citation_health_score: Optional[float] = None,
    ai_probability_score: Optional[float] = None,
    evasion_risk_score: Optional[float] = None,
    cross_lingual_score: Optional[float] = None,
) -> bool:
    """
    Mengirimkan notifikasi email kepada pemilik dokumen saat laporan selesai diproses.
    """
    conn = None
    try:
        conn = get_connection()
        cursor = conn.cursor(dictionary=True)
        cursor.execute("""
            SELECT u.id AS user_id, u.email, u.name AS full_name, 
                   u.email_notifications_enabled, u.unsubscribe_token
            FROM documents d
            JOIN users u ON d.user_id = u.id
            WHERE d.id = %s
        """, (document_id,))
        user = cursor.fetchone()
        cursor.close()

        if not user or not user.get("email"):
            return False

        # Periksa preferensi berhenti berlangganan (unsubscribe)
        if user.get("email_notifications_enabled") == 0:
            print(f"  [EMAIL NOTIFIER] Pengguna #{user.get('user_id')} ({user.get('email')}) menonaktifkan notifikasi surel laporan. Pengiriman dilewati.")
            return False

        recipient_email = user["email"].strip()
        recipient_name = user.get("full_name") or "Pengguna AR Tilik"
        unsub_token = user.get("unsubscribe_token") or ""
        if not unsub_token:
            import hashlib, time
            unsub_token = hashlib.sha256(f"{user['user_id']}|{recipient_email}|{time.time()}".encode()).hexdigest()
            try:
                cur_tok = conn.cursor()
                cur_tok.execute("UPDATE users SET unsubscribe_token = %s WHERE id = %s", (unsub_token, user["user_id"]))
                conn.commit()
                cur_tok.close()
            except Exception:
                pass

        # Tentukan label keputusan dan warna badge
        dec_lower = decision.lower()
        if dec_lower == "match":
            badge_text = "MATCH (Kesamaan Harfiah Kuat)"
            badge_bg = "#fef2f2"
            badge_color = "#b91c1c"
            badge_border = "#f87171"
        elif dec_lower == "review":
            badge_text = "REVIEW (Perlu Tinjauan Akademik)"
            badge_bg = "#fffbeb"
            badge_color = "#b45309"
            badge_border = "#fbbf24"
        else:
            badge_text = "NO MATCH (Bebas Indikasi Kesamaan)"
            badge_bg = "#ecfdf5"
            badge_color = "#047857"
            badge_border = "#34d399"

        # Bangun metrik opsional
        audit_row = ""
        if citation_health_score is not None:
            c_color = "#047857" if citation_health_score >= 80 else ("#b45309" if citation_health_score >= 50 else "#b91c1c")
            audit_row = f"""
            <tr>
                <td style="padding: 10px 14px; border-bottom: 1px solid #f1f5f9; color: #64748b; font-size: 13px;">Kesehatan Sitasi & Pustaka</td>
                <td style="padding: 10px 14px; border-bottom: 1px solid #f1f5f9; font-weight: 700; color: {c_color}; font-size: 13px;">
                    {citation_health_score:.1f}%
                </td>
            </tr>
            """

        ai_row = ""
        if ai_probability_score is not None:
            ai_color = "#b91c1c" if ai_probability_score >= 65 else ("#b45309" if ai_probability_score >= 38 else "#047857")
            ai_label = "Pola AI Terdeteksi" if ai_probability_score >= 65 else ("Kemungkinan Parafrase AI" if ai_probability_score >= 38 else "Tulisan Alami Manusia")
            ai_row = f"""
            <tr>
                <td style="padding: 10px 14px; border-bottom: 1px solid #f1f5f9; color: #64748b; font-size: 13px;">Indikator Teks Buatan AI</td>
                <td style="padding: 10px 14px; border-bottom: 1px solid #f1f5f9; font-weight: 700; color: {ai_color}; font-size: 13px;">
                    {ai_probability_score:.1f}% ({ai_label})
                </td>
            </tr>
            """

        evasion_row = ""
        if evasion_risk_score is not None:
            ev_color = "#b91c1c" if evasion_risk_score >= 35 else ("#b45309" if evasion_risk_score >= 10 else "#047857")
            ev_label = "Manipulasi Terencana" if evasion_risk_score >= 35 else ("Anomali Karakter" if evasion_risk_score >= 10 else "Otentik / Bersih")
            evasion_row = f"""
            <tr>
                <td style="padding: 10px 14px; border-bottom: 1px solid #f1f5f9; color: #64748b; font-size: 13px;">Integritas &amp; Anti-Evasion</td>
                <td style="padding: 10px 14px; border-bottom: 1px solid #f1f5f9; font-weight: 700; color: {ev_color}; font-size: 13px;">
                    {evasion_risk_score:.1f}% ({ev_label})
                </td>
            </tr>
            """

        cross_lingual_row = ""
        if cross_lingual_score is not None and cross_lingual_score >= 15.0:
            cl_color = "#b91c1c" if cross_lingual_score >= 35 else "#b45309"
            cl_label = "Terindikasi Terjemahan Langsung" if cross_lingual_score >= 35 else "Parafrasa Lintas Bahasa"
            cross_lingual_row = f"""
            <tr>
                <td style="padding: 10px 14px; border-bottom: 1px solid #f1f5f9; color: #64748b; font-size: 13px;">Plagiarisme Terjemahan (EN ↔ ID)</td>
                <td style="padding: 10px 14px; border-bottom: 1px solid #f1f5f9; font-weight: 700; color: {cl_color}; font-size: 13px;">
                    {cross_lingual_score:.1f}% ({cl_label})
                </td>
            </tr>
            """

        report_url = f"https://tilik.arsetontong.top/reports/{report_id}"
        unsub_url = f"https://tilik.arsetontong.top/email/unsubscribe?token={unsub_token}&type=report"

        # Template Email HTML
        html_content = f"""<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Hasil Pemeriksaan Karya Ilmiah</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f8fafc; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; color: #1e293b;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color: #f8fafc; padding: 30px 15px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" style="max-width: 600px; background-color: #ffffff; border-radius: 16px; border: 1px solid #e2e8f0; box-shadow: 0 4px 12px rgba(15, 23, 42, 0.05); overflow: hidden;" cellspacing="0" cellpadding="0">
                    <!-- Header -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #4338ca 0%, #312e81 100%); padding: 26px 30px; text-align: left;">
                            <div style="font-size: 22px; font-weight: 800; color: #ffffff; letter-spacing: -0.5px;">
                                ⚖️ AR Tilik
                            </div>
                            <div style="font-size: 12px; color: #c7d2fe; margin-top: 4px;">
                                Sistem Pemeriksa Kesamaan Naskah &amp; Integritas Akademik
                            </div>
                        </td>
                    </tr>

                    <!-- Body -->
                    <tr>
                        <td style="padding: 30px;">
                            <h2 style="font-size: 18px; font-weight: 800; color: #0f172a; margin-top: 0; margin-bottom: 8px;">
                                Pemeriksaan Naskah Selesai
                            </h2>
                            <p style="font-size: 14px; color: #475569; line-height: 1.6; margin-top: 0; margin-bottom: 20px;">
                                Halo <strong>{recipient_name}</strong>, naskah karya ilmiah Anda telah selesai dianalisis oleh antrean pemrosesan worker AR Tilik. Berikut adalah rangkuman hasil pemeriksaannya:
                            </p>

                            <!-- Summary Card -->
                            <div style="background-color: #f8fafc; border-radius: 12px; border: 1px solid #e2e8f0; padding: 18px; margin-bottom: 24px;">
                                <div style="font-size: 15px; font-weight: 700; color: #0f172a; margin-bottom: 12px;">
                                    📄 {document_title}
                                </div>

                                <table width="100%" cellspacing="0" cellpadding="0" style="border-collapse: collapse;">
                                    <tr>
                                        <td style="padding: 10px 14px; border-bottom: 1px solid #f1f5f9; color: #64748b; font-size: 13px;">Skor Kesamaan Menyeluruh</td>
                                        <td style="padding: 10px 14px; border-bottom: 1px solid #f1f5f9; font-weight: 800; font-size: 16px; color: {badge_color};">
                                            {overall_similarity:.2f}%
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 10px 14px; border-bottom: 1px solid #f1f5f9; color: #64748b; font-size: 13px;">Keputusan Analisis</td>
                                        <td style="padding: 10px 14px; border-bottom: 1px solid #f1f5f9; font-size: 12px;">
                                            <span style="display: inline-block; background-color: {badge_bg}; color: {badge_color}; border: 1px solid {badge_border}; font-weight: 700; padding: 3px 8px; border-radius: 6px;">
                                                {badge_text}
                                            </span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 10px 14px; border-bottom: 1px solid #f1f5f9; color: #64748b; font-size: 13px;">Exact / Verbatim Match</td>
                                        <td style="padding: 10px 14px; border-bottom: 1px solid #f1f5f9; font-weight: 600; color: #334155; font-size: 13px;">
                                            {exact_similarity:.2f}%
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 10px 14px; border-bottom: 1px solid #f1f5f9; color: #64748b; font-size: 13px;">Cosine / Topical Sim</td>
                                        <td style="padding: 10px 14px; border-bottom: 1px solid #f1f5f9; font-weight: 600; color: #334155; font-size: 13px;">
                                            {cosine_similarity:.2f}%
                                        </td>
                                    </tr>
                                    {audit_row}
                                    {ai_row}
                                    {evasion_row}
                                    {cross_lingual_row}
                                </table>
                            </div>

                            <!-- CTA Button -->
                            <div style="text-align: center; margin-bottom: 24px;">
                                <a href="{report_url}" style="display: inline-block; background-color: #4338ca; color: #ffffff; font-weight: 700; font-size: 14px; text-decoration: none; padding: 13px 28px; border-radius: 10px; box-shadow: 0 4px 10px rgba(67, 56, 202, 0.25);">
                                    Buka Laporan Lengkap di Layar Ganda →
                                </a>
                            </div>

                            <p style="font-size: 12px; color: #94a3b8; line-height: 1.5; margin: 0; text-align: center;">
                                Anda juga dapat mengunduh sertifikat resmi ber-QR Code langsung dari tautan laporan di atas.
                            </p>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #f1f5f9; padding: 18px 30px; text-align: center; font-size: 11px; color: #64748b; border-top: 1px solid #e2e8f0; line-height: 1.6;">
                            AR Tilik • Platform Pemeriksa Kesamaan Naskah &amp; Integritas Akademik<br>
                            Surel ini dikirim otomatis oleh sistem. Jangan membalas surel ini secara langsung.<br>
                            <span style="display: inline-block; margin-top: 6px;">
                                Tidak ingin menerima notifikasi naskah? <a href="{unsub_url}" style="color: #4f46e5; text-decoration: underline;">Berhenti berlangganan di sini</a>.
                            </span>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
        """

        msg = EmailMessage()
        msg["Subject"] = f"[AR Tilik] Laporan Selesai: {document_title} ({overall_similarity:.1f}%)"
        msg["From"] = "AR Tilik <tilik@arsetontong.top>"
        msg["To"] = recipient_email
        msg["List-Unsubscribe"] = f"<{unsub_url}>"
        msg["List-Unsubscribe-Post"] = "List-Unsubscribe=One-Click"
        msg.set_content(
            f"Halo {recipient_name},\n\n"
            f"Pemeriksaan naskah '{document_title}' telah selesai dengan skor kemiripan {overall_similarity:.2f}%.\n\n"
            f"Buka laporan lengkap di: {report_url}\n\n"
            f"Berhenti berlangganan notifikasi surel laporan: {unsub_url}\n\n"
            f"Salam,\nAR Tilik"
        )
        msg.add_alternative(html_content, subtype="html")

        # Kirim melalui SMTP Postfix lokal di 127.0.0.1:25
        ctx = ssl.create_default_context()
        ctx.check_hostname = False
        ctx.verify_mode = ssl.CERT_NONE

        with smtplib.SMTP("127.0.0.1", 25, timeout=10) as server:
            server.starttls(context=ctx)
            server.send_message(msg)

        print(f"  [EMAIL NOTIFIER] Notifikasi laporan #{report_id} berhasil dikirim ke {recipient_email}")
        return True

    except Exception as e:
        print(f"  [EMAIL NOTIFIER ERROR] Gagal mengirim notifikasi email untuk doc #{document_id}: {e}")
        return False
    finally:
        if conn:
            conn.close()
