"""
AR Tilik - Telegram Notification Integration
Modul notifikasi terintegrasi untuk mengirimkan pembaruan status sistem,
laporan hasil pemeriksaan kemiripan karya ilmiah, dan alert operasional.
"""

import os
import urllib.request
import urllib.parse
import json
from typing import Optional, Dict, Any
from pathlib import Path

# Token default sesuai konfigurasi institusi
DEFAULT_BOT_TOKEN = "688091201:AAFQ0TiJfDZg2wSsuPvIPJtNa_5NaQetfm4"
DEFAULT_CHAT_ID = "674868958"
DEFAULT_BASE_URL = "https://tilik.arsetontong.top"


def get_telegram_credentials() -> tuple[str, str]:
    """
    Mengambil BOT TOKEN dan CHAT ID dari environment variable atau file .env.
    """
    token = os.getenv("TELEGRAM_BOT_TOKEN")
    chat_id = os.getenv("TELEGRAM_CHAT_ID")

    if not token or not chat_id:
        # Coba parse .env jika belum di-export ke OS env
        env_path = Path(__file__).resolve().parents[1] / ".env"
        if env_path.exists():
            with open(env_path, "r", encoding="utf-8") as f:
                for line in f:
                    line = line.strip()
                    if line.startswith("TELEGRAM_BOT_TOKEN"):
                        parts = line.split("=", 1)
                        if len(parts) == 2 and not token:
                            token = parts[1].strip().strip("'\"")
                    elif line.startswith("TELEGRAM_CHAT_ID"):
                        parts = line.split("=", 1)
                        if len(parts) == 2 and not chat_id:
                            chat_id = parts[1].strip().strip("'\"")

    return token or DEFAULT_BOT_TOKEN, chat_id or DEFAULT_CHAT_ID


def send_telegram_message(
    text: str,
    chat_id: Optional[str] = None,
    parse_mode: str = "HTML",
    disable_web_page_preview: bool = True
) -> bool:
    """
    Mengirim pesan langsung ke Telegram.
    """
    token, default_cid = get_telegram_credentials()
    target_chat_id = chat_id or default_cid

    if not token or not target_chat_id:
        print("[TELEGRAM] Warning: Token atau Chat ID belum dikonfigurasi.")
        return False

    url = f"https://api.telegram.org/bot{token}/sendMessage"
    payload = {
        "chat_id": target_chat_id,
        "text": text,
        "parse_mode": parse_mode,
        "disable_web_page_preview": disable_web_page_preview,
    }

    try:
        data = urllib.parse.urlencode(payload).encode("utf-8")
        req = urllib.request.Request(
            url,
            data=data,
            headers={"User-Agent": "ARTilik-NotificationEngine/1.2"}
        )
        with urllib.request.urlopen(req, timeout=10) as resp:
            body = resp.read().decode("utf-8")
            res_json = json.loads(body)
            return bool(res_json.get("ok"))
    except Exception as err:
        print(f"[TELEGRAM ERROR] Gagal mengirim pesan: {err}")
        return False


def notify_similarity_check_completed(
    report_id: int,
    document_title: str,
    decision: str,
    exact_similarity: float,
    cosine_similarity: float,
    longest_match_words: int,
    base_url: str = DEFAULT_BASE_URL
) -> bool:
    """
    Mengirimkan ringkasan bukti pemeriksaan kemiripan karya ilmiah ke Telegram.
    """
    decision_icons = {
        "match": "🔴 <b>MATCH (Kesamaan Signifikan)</b>",
        "review": "🟡 <b>REVIEW (Perlu Telaah Akademik)</b>",
        "no_match": "🟢 <b>NO MATCH (Orisinalitas Terjaga)</b>",
    }
    decision_text = decision_icons.get(decision.lower(), f"⚪ <b>{decision.upper()}</b>")
    report_url = f"{base_url.rstrip('/')}/report/{report_id}"

    msg = (
        f"📋 <b>AR Tilik - Laporan Pemeriksaan #{report_id}</b>\n\n"
        f"📄 <b>Dokumen:</b> {document_title}\n"
        f"⚖️ <b>Keputusan:</b> {decision_text}\n"
        f"🔍 <b>Exact Overlap:</b> <code>{exact_similarity:.2f}%</code> ({longest_match_words} kata berurutan)\n"
        f"📊 <b>TF-IDF Cosine:</b> <code>{cosine_similarity:.2f}%</code>\n\n"
        f"🔗 <b>Lihat Laporan:</b> <a href=\"{report_url}\">{report_url}</a>\n"
        f"<i>Sistem Penelaah Kemiripan Ilmiah Berbasis Keadilan Akademik</i>"
    )
    return send_telegram_message(msg)


def notify_deployment_success(
    server_url: str = DEFAULT_BASE_URL,
    version: str = "v1.3.0 (Dual-Lens + Section Parser + Audit Forensik Cerdas)"
) -> bool:
    """
    Notifikasi resmi penyelesaian instalasi/deployment ke production server.
    """
    msg = (
        "🚀 <b>AR Tilik Berhasil Diimplementasikan ke Production Server!</b>\n\n"
        f"🌐 <b>URL:</b> <a href=\"{server_url}\">{server_url}</a>\n"
        f"📦 <b>Versi:</b> <code>{version}</code>\n"
        f"🗄️ <b>Database:</b> <code>db_artilik</code> (MariaDB Production)\n"
        "⚡ <b>Engine & Invariant:</b> Dual-Lens (5-Gram + TF-IDF) Frozen Baseline\n"
        "✨ <b>Modul Tambahan v1.3:</b>\n"
        "  • <i>Smart Section Parser & Boilerplate Detector</i>\n"
        "  • <i>Audit Forensik Cerdas & Sintesis Risiko Akademik</i>\n"
        "  • <i>Dynamic Multi-Key API Failover Engine</i>\n\n"
        f"<i>Layanan AR Tilik siap melayani pemeriksaan karya ilmiah civitas akademika.</i>"
    )
    return send_telegram_message(msg)


def notify_corpus_harvest_completed(
    new_docs_count: int,
    dropzone_count: int = 0,
    oai_count: int = 0,
    total_corpus_count: int = 0,
    details: Optional[list[str]] = None,
    base_url: str = DEFAULT_BASE_URL
) -> bool:
    """
    Mengirimkan ringkasan laporan hasil panen otomatis korpus ke Telegram.
    """
    detail_lines = ""
    if details:
        top_items = details[:5]
        detail_lines = "\n\n<b>Naskah Terbaru yang Diindeks:</b>\n" + "\n".join([f"• {item}" for item in top_items])
        if len(details) > 5:
            detail_lines += f"\n• <i>...dan {len(details) - 5} naskah lainnya.</i>"

    msg = (
        "🌾 <b>AR Tilik - Laporan Panen Korpus Otomatis</b>\n\n"
        f"✅ <b>Naskah Baru Diindeks:</b> <code>+{new_docs_count} dokumen</code>\n"
        f"📁 <b>Sumber Dropzone:</b> {dropzone_count} berkas\n"
        f"🌐 <b>Sumber OAI-PMH:</b> {oai_count} publikasi\n"
        f"📚 <b>Total Korpus Aktif:</b> <b>{total_corpus_count} dokumen</b>\n"
        f"⚡ <b>Indeks Shingle & IDF:</b> <i>Telah diperbarui otomatis</i>"
        f"{detail_lines}\n\n"
        f"🔗 <b>Akses Sistem:</b> <a href=\"{base_url}\">{base_url}</a>\n"
        f"<i>Automated Corpus Harvester & Repository Sync</i>"
    )
    return send_telegram_message(msg)


if __name__ == "__main__":
    import sys
    if len(sys.argv) > 1:
        text_to_send = " ".join(sys.argv[1:])
        ok = send_telegram_message(text_to_send)
        print(f"Status pengiriman: {'BERHASIL' if ok else 'GAGAL'}")
    else:
        ok = notify_deployment_success()
        print(f"Status notifikasi deployment: {'BERHASIL' if ok else 'GAGAL'}")
