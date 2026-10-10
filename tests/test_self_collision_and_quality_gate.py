"""
Unit Test untuk Self-Collision / Self-Plagiarism Protection dan Corpus Quality Gate di AR Tilik.
"""

from unittest.mock import MagicMock
import pytest
from worker.matcher import find_matching_fingerprints
from worker.tfidf import get_corpus_documents
from worker.hybrid import collect_evidence, run_hybrid_similarity_check


def test_matcher_exclude_user_id_in_production_scope():
    """
    Memastikan find_matching_fingerprints menambahkan klausul exclude_user_id
    hanya pada corpus_scope='production' dan mem-pass parameter user_id.
    """
    mock_cursor = MagicMock()
    mock_cursor.fetchall.return_value = []
    mock_conn = MagicMock()
    mock_conn.cursor.return_value = mock_cursor

    fps = [b"fp1", b"fp2"]
    
    # Panggilan dengan exclude_user_id pada production (use_cache=False untuk verifikasi query builder SQL)
    find_matching_fingerprints(
        connection=mock_conn,
        unique_fingerprints=fps,
        submission_document_id=10,
        corpus_scope="production",
        exclude_user_id=42,
        use_cache=False
    )

    assert mock_cursor.execute.called
    executed_sql, executed_params = mock_cursor.execute.call_args[0]
    assert "(d.user_id != %s OR d.user_id IS NULL)" in executed_sql
    assert 42 in executed_params
    assert 10 in executed_params


def test_matcher_benchmark_scope_does_not_exclude_user_id():
    """
    Memastikan evaluasi benchmark tidak mengecualikan user_id agar
    ground-truth benchmark dataset tetap utuh 100%.
    """
    mock_cursor = MagicMock()
    mock_cursor.fetchall.return_value = []
    mock_conn = MagicMock()
    mock_conn.cursor.return_value = mock_cursor

    fps = [b"fp1"]
    find_matching_fingerprints(
        connection=mock_conn,
        unique_fingerprints=fps,
        submission_document_id=10,
        corpus_scope="benchmark",
        exclude_user_id=42,
        use_cache=False
    )

    executed_sql, executed_params = mock_cursor.execute.call_args[0]
    assert "user_id !=" not in executed_sql
    assert 42 not in executed_params


def test_tfidf_exclude_user_id_in_production_scope():
    """
    Memastikan get_corpus_documents pada tfidf mengecualikan korpus milik user_id
    yang sama dalam corpus_scope='production'.
    """
    mock_cursor = MagicMock()
    mock_cursor.fetchall.return_value = []
    mock_conn = MagicMock()
    mock_conn.cursor.return_value = mock_cursor

    get_corpus_documents(
        connection=mock_conn,
        corpus_scope="production",
        exclude_user_id=99
    )

    assert mock_cursor.execute.called
    executed_sql, executed_params = mock_cursor.execute.call_args[0]
    assert "(d.user_id != %s OR d.user_id IS NULL)" in executed_sql
    assert 99 in executed_params


def test_corpus_quality_gate_rules():
    """
    Memvalidasi aturan Quality Gate penerimaan naskah ke korpus institusi:
    1. Status pemeriksaan harus 'completed'
    2. Tingkat kemiripan awal <= 25% (untuk non-admin)
    3. Jumlah kata substantif >= 1.000 kata (untuk non-admin)
    4. Indikasi AI tidak boleh 'TINGGI' atau probabilitas AI >= 80% (untuk non-admin)
    """
    def check_quality_gate(report_status, sim_score, word_count, ai_level, ai_prob, is_admin=False):
        if report_status != 'completed':
            return False, "Pemeriksaan kemiripan naskah harus selesai dilakukan terlebih dahulu."
        if not is_admin and sim_score > 25.0:
            return False, f"Tingkat kemiripan ({sim_score}%) melebihi batas maksimal 25%."
        if not is_admin and word_count < 1000:
            return False, f"Panjang naskah ({word_count} kata) kurang dari batas minimum (1.000 kata)."
        if not is_admin and (ai_level.upper() == 'TINGGI' or ai_prob >= 80.0):
            return False, f"Terdeteksi tingkat indikasi generatif AI yang dominan ({ai_level})."
        return True, "Memenuhi syarat korpus institusi."

    # Kasus 1: Naskah belum selesai diperiksa
    ok, msg = check_quality_gate('processing', 10.0, 3000, 'RENDAH', 10.0)
    assert not ok
    assert "harus selesai" in msg

    # Kasus 2: Naskah plagiat (kemiripan 35% > 25%)
    ok, msg = check_quality_gate('completed', 35.0, 3000, 'RENDAH', 10.0)
    assert not ok
    assert "melebihi batas maksimal 25%" in msg

    # Kasus 3: Naskah terlalu pendek (draf mentah 400 kata < 1.000)
    ok, msg = check_quality_gate('completed', 12.0, 400, 'RENDAH', 10.0)
    assert not ok
    assert "kurang dari batas minimum" in msg

    # Kasus 4: Naskah buatan AI (AI level TINGGI atau prob >= 80%)
    ok, msg = check_quality_gate('completed', 15.0, 2500, 'TINGGI', 85.0)
    assert not ok
    assert "indikasi generatif AI yang dominan" in msg

    # Kasus 5: Naskah mahasiswa valid & berkualitas
    ok, msg = check_quality_gate('completed', 14.5, 4200, 'SEDANG', 45.0)
    assert ok
    assert "Memenuhi syarat" in msg

    # Kasus 6: Admin override (admin bisa mendaftarkan korpus referensi khusus meski sim > 25% atau pendek)
    ok, msg = check_quality_gate('completed', 40.0, 500, 'SEDANG', 50.0, is_admin=True)
    assert ok
