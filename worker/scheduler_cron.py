"""
AR Tilik - Scheduled Corpus Automation Runner (Cron Runner)
Mengorkestrasi penambahan korpus berkala: memindai folder dropzone,
menjalankan OAI-PMH harvester repositori kampus, memperbarui indeks shingle/IDF,
dan mengirimkan rekapitulasi ke Telegram bot.
"""

import os
import sys
import argparse
from pathlib import Path
from datetime import datetime

# Ensure project root is prioritized on sys.path and remove script dir to avoid package shadowing
PROJECT_ROOT = Path(__file__).resolve().parents[1]
script_dir = str(Path(__file__).resolve().parent)
if script_dir in sys.path:
    sys.path.remove(script_dir)
if str(PROJECT_ROOT) not in sys.path:
    sys.path.insert(0, str(PROJECT_ROOT))

from worker.database import get_connection, load_env
from worker.corpus_harvester import harvest_dropzone, DEFAULT_DROPZONE_DIR, DEFAULT_ARCHIVE_DIR
from worker.oai_harvester import OAIHarvester
from worker.telegram_notifier import notify_corpus_harvest_completed

load_env()


def get_total_active_corpus_count(connection) -> int:
    """Mengambil jumlah total dokumen korpus yang aktif di database."""
    cursor = connection.cursor()
    cursor.execute("""
        SELECT COUNT(*)
        FROM documents
        WHERE document_type = 'corpus'
          AND status = 'completed'
    """)
    row = cursor.fetchone()
    count = row[0] if row else 0
    cursor.close()
    return count


def run_scheduler_pipeline(
    notify_telegram: bool = True,
    force_notify: bool = False,
    custom_oai_url: str | None = None,
    custom_oai_limit: int | None = None
) -> dict:
    """
    Eksekusi lengkap siklus pemanenan terjadwal.
    """
    start_time = datetime.now()
    print("=" * 60)
    print(f"[{start_time.strftime('%Y-%m-%d %H:%M:%S')}] AR Tilik Scheduled Harvester Runner")
    print("=" * 60)

    # 1. Jalankan pemindaian dropzone lokal
    print("\n[1/3] Memeriksa direktori dropzone...")
    dropzone_result = harvest_dropzone(
        dropzone_dir=DEFAULT_DROPZONE_DIR,
        archive_dir=DEFAULT_ARCHIVE_DIR,
        recursive=True
    )
    indexed_dropzone = dropzone_result.get("indexed", 0)
    print(f"      Terpindai: {dropzone_result.get('total_scanned', 0)}, Diindeks: {indexed_dropzone}, Dilewati: {dropzone_result.get('skipped', 0)}")

    # 2. Jalankan OAI-PMH jika URL dikonfigurasi
    oai_url = custom_oai_url or os.getenv("CORPUS_OAI_URL", "").strip()
    oai_set = os.getenv("CORPUS_OAI_SET", "").strip() or None
    oai_limit = custom_oai_limit or int(os.getenv("CORPUS_OAI_LIMIT", "15"))
    oai_fulltext = os.getenv("CORPUS_OAI_FULLTEXT", "true").lower() in ("true", "1", "yes")

    indexed_oai = 0
    oai_result = {}
    new_titles = []

    for item in dropzone_result.get("processed_items", []):
        if item.get("status") == "success":
            new_titles.append(item.get("title", "Dokumen Dropzone"))

    if oai_url:
        print(f"\n[2/4] Memanen dari repositori OAI-PMH: {oai_url}...")
        try:
            harvester = OAIHarvester(oai_url)
            oai_result = harvester.harvest_records(
                set_spec=oai_set,
                limit=oai_limit,
                download_fulltext=oai_fulltext
            )
            indexed_oai = oai_result.get("indexed_count", 0)
            print(f"      Dipanen: {oai_result.get('total_harvested', 0)}, Diindeks: {indexed_oai}, Dilewati: {oai_result.get('skipped_count', 0)}")

            for rec in oai_result.get("records", []):
                if rec.get("status") == "indexed":
                    new_titles.append(rec.get("title", "Publikasi OAI"))

        except Exception as oai_err:
            print(f"[!] Kendala pemanenan OAI-PMH: {oai_err}")
    else:
        print("\n[2/4] Memeriksa katalog registri repositori perguruan tinggi Indonesia...")
        try:
            from worker.auto_discovery_harvester import harvest_indonesian_registry
            reg_res = harvest_indonesian_registry(max_repos=2, limit_per_repo=3)
            indexed_oai = reg_res.get("indexed_count", 0)
            oai_result = reg_res
            print(f"      Diindeks dari registri: {indexed_oai}")
            for itm in reg_res.get("details", []):
                res_obj = itm.get("result", {})
                for r in res_obj.get("records", []):
                    if r.get("status") == "indexed":
                        new_titles.append(r.get("title", itm.get("name", "Publikasi Repositori RI")))
        except Exception as reg_err:
            print(f"[!] Kendala pemanenan registri repositori: {reg_err}")

    # 3. Jalankan Autonomous Discovery jika dikonfigurasi di .env
    discovery_topics = os.getenv("CORPUS_DISCOVERY_TOPICS", "").strip()
    indexed_discovery = 0
    discovery_result = {}
    if discovery_topics:
        from worker.auto_discovery_harvester import auto_discover_by_query
        print(f"\n[3/4] Menjalankan Autonomous Discovery untuk topik: '{discovery_topics}'...")
        topics_list = [t.strip() for t in discovery_topics.split(",") if t.strip()]
        for topic in topics_list:
            try:
                d_res = auto_discover_by_query(query=topic, limit=5)
                indexed_discovery += d_res.get("indexed_count", 0)
                for itm in d_res.get("items", []):
                    if itm.get("status") == "indexed":
                        new_titles.append(itm.get("title", topic))
            except Exception as d_err:
                print(f"[!] Kendala discovery topik '{topic}': {d_err}")
    else:
        print("\n[3/4] Autonomous Discovery dilewati (CORPUS_DISCOVERY_TOPICS belum dikonfigurasi di .env).")

    # 4. Hitung total korpus aktif di database
    print("\n[4/4] Menghitung status korpus aktif...")
    conn = get_connection()
    try:
        total_corpus = get_total_active_corpus_count(conn)
    finally:
        conn.close()

    total_new = indexed_dropzone + indexed_oai + indexed_discovery
    print(f"      Total naskah korpus aktif saat ini: {total_corpus} dokumen.")

    # 5. Kirim notifikasi Telegram jika ada berkas baru atau dipaksa
    if notify_telegram and (total_new > 0 or force_notify):
        print("\n[*] Mengirimkan laporan panen ke Telegram bot...")
        notif_sent = notify_corpus_harvest_completed(
            new_docs_count=total_new,
            dropzone_count=indexed_dropzone,
            oai_count=indexed_oai + indexed_discovery,
            total_corpus_count=total_corpus,
            details=new_titles
        )
        print(f"    Status pengiriman notifikasi: {'Berhasil' if notif_sent else 'Gagal'}")

    end_time = datetime.now()
    duration = (end_time - start_time).total_seconds()
    print("\n" + "=" * 60)
    print(f"Selesai dalam {duration:.2f} detik. Total naskah baru: +{total_new}")
    print("=" * 60)

    # Catat log terstruktur ke file log
    try:
        log_dir = PROJECT_ROOT / "writable" / "logs"
        log_dir.mkdir(parents=True, exist_ok=True)
        log_file = log_dir / "harvester_cron.log"
        with open(log_file, "a", encoding="utf-8") as f:
            f.write(f"[{start_time.strftime('%Y-%m-%d %H:%M:%S')}] DURATION={duration:.2f}s NEW=+{total_new} (Dropzone={indexed_dropzone}, OAI/Reg={indexed_oai}, Discovery={indexed_discovery}) TOTAL_ACTIVE={total_corpus}\n")
    except Exception:
        pass

    return {
        "dropzone": dropzone_result,
        "oai": oai_result,
        "total_new": total_new,
        "total_active_corpus": total_corpus,
        "duration_seconds": duration
    }


if __name__ == "__main__":
    parser = argparse.ArgumentParser(description="AR Tilik Scheduled Corpus Automation Runner")
    parser.add_argument("--oai-url", default=None, help="URL repositori OAI-PMH temporer")
    parser.add_argument("--oai-limit", type=int, default=None, help="Batas maksimum panen OAI")
    parser.add_argument("--no-notify", action="store_true", help="Jangan kirim notifikasi ke Telegram")
    parser.add_argument("--force-notify", action="store_true", help="Kirim notifikasi Telegram meski tidak ada berkas baru")
    args = parser.parse_args()

    run_scheduler_pipeline(
        notify_telegram=(not args.no_notify),
        force_notify=args.force_notify,
        custom_oai_url=args.oai_url,
        custom_oai_limit=args.oai_limit
    )
