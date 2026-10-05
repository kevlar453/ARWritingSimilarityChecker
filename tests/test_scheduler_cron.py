import pytest
from worker.scheduler_cron import run_scheduler_pipeline, get_total_active_corpus_count
from worker.database import get_connection


def test_get_total_active_corpus_count():
    conn = get_connection()
    try:
        count = get_total_active_corpus_count(conn)
        assert isinstance(count, int)
        assert count >= 0
    finally:
        conn.close()


def test_scheduler_pipeline_dry_run():
    # Jalankan pipeline dengan no-notify agar tidak spamming Telegram saat automated test
    res = run_scheduler_pipeline(
        notify_telegram=False,
        force_notify=False
    )
    assert "dropzone" in res
    assert "total_new" in res
    assert "total_active_corpus" in res
    assert isinstance(res["duration_seconds"], float)
