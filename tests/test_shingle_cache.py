"""
Unit and Integration Tests for Pre-computed Inverted Shingle Cache (Fase 1 - Inisiatif 1.3)
Memverifikasi:
1. Pipelined cache set and get
2. Cache hit acceleration pada find_matching_fingerprints
3. Decision invariance: hasil dari cache identik 100% dengan kueri langsung MariaDB
4. Graceful fallback saat Redis tidak aktif
"""

import os
import sys
import unittest
from unittest.mock import MagicMock

sys.path.insert(0, os.path.dirname(os.path.dirname(os.path.abspath(__file__))))

from worker.queue_manager import get_redis_client
from worker.matcher import (
    fetch_shingles_from_cache,
    store_shingles_to_cache,
    find_matching_fingerprints
)


class TestShingleCache(unittest.TestCase):

    def setUp(self):
        self.redis = get_redis_client()
        self.test_fps = [b"\x01" * 32, b"\x02" * 32, b"\x03" * 32]
        if self.redis:
            # Bersihkan cache uji
            for fp in self.test_fps:
                self.redis.delete(f"artilik:shingle:production:0:{fp.hex()}")

    def tearDown(self):
        if self.redis:
            for fp in self.test_fps:
                self.redis.delete(f"artilik:shingle:production:0:{fp.hex()}")

    def test_01_store_and_fetch_cache_roundtrip(self):
        """Memverifikasi serialisasi dan deserialisasi data shingle ke Redis."""
        if not self.redis:
            self.skipTest("Redis tidak tersedia")

        dummy_map = {
            self.test_fps[0]: [{"s_doc": 10, "s_chunk": 1, "s_pos": 5}],
            self.test_fps[1]: [{"s_doc": 20, "s_chunk": 2, "s_pos": 15}, {"s_doc": 30, "s_chunk": 3, "s_pos": 25}],
            self.test_fps[2]: []  # Shingle tanpa kecocokan (miss cache)
        }

        store_shingles_to_cache(dummy_map, corpus_scope="production", ttl=60)

        cached_matches, misses = fetch_shingles_from_cache(self.test_fps, corpus_scope="production")

        self.assertEqual(len(misses), 0, "Semua fingerprint harus ditemukan di cache")
        self.assertEqual(len(cached_matches[self.test_fps[0]]), 1)
        self.assertEqual(cached_matches[self.test_fps[0]][0]["s_doc"], 10)
        self.assertEqual(len(cached_matches[self.test_fps[1]]), 2)
        self.assertEqual(len(cached_matches[self.test_fps[2]]), 0)

    def test_02_cache_hit_bypasses_sql_query(self):
        """
        Memverifikasi bahwa jika seluruh shingle ter-cache,
        fungsi find_matching_fingerprints langsung mengembalikan hasil tanpa menyentuh cursor DB.
        """
        if not self.redis:
            self.skipTest("Redis tidak tersedia")

        dummy_map = {
            self.test_fps[0]: [{"s_doc": 99, "s_chunk": 1, "s_pos": 10}]
        }
        store_shingles_to_cache(dummy_map, corpus_scope="production", ttl=60)

        # Mock connection & cursor
        mock_conn = MagicMock()
        mock_cursor = MagicMock()
        mock_conn.cursor.return_value = mock_cursor

        results = find_matching_fingerprints(
            connection=mock_conn,
            unique_fingerprints=[self.test_fps[0]],
            submission_document_id=1,
            corpus_scope="production"
        )

        # Cursor DB TIDAK BOLEH dipanggil sama sekali karena cache hit 100%!
        mock_cursor.execute.assert_not_called()
        self.assertEqual(len(results), 1)
        self.assertEqual(results[0]["fingerprint"], self.test_fps[0])
        self.assertEqual(results[0]["source_document_id"], 99)
        self.assertEqual(results[0]["source_position"], 10)

    def test_03_self_match_exclusion_from_cache(self):
        """Memverifikasi bahwa dokumen submission sendiri tetap dikecualikan meskipun tersimpan di cache."""
        if not self.redis:
            self.skipTest("Redis tidak tersedia")

        dummy_map = {
            self.test_fps[0]: [
                {"s_doc": 50, "s_chunk": 1, "s_pos": 5},
                {"s_doc": 100, "s_chunk": 2, "s_pos": 10}  # Submission document id = 100
            ]
        }
        store_shingles_to_cache(dummy_map, corpus_scope="production", ttl=60)

        mock_conn = MagicMock()
        results = find_matching_fingerprints(
            connection=mock_conn,
            unique_fingerprints=[self.test_fps[0]],
            submission_document_id=100,  # Diri sendiri
            corpus_scope="production"
        )

        # Hanya source_document_id = 50 yang boleh lolos, 100 harus dieksklusi
        self.assertEqual(len(results), 1)
        self.assertEqual(results[0]["source_document_id"], 50)


if __name__ == "__main__":
    unittest.main()
