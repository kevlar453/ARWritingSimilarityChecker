#!/usr/bin/env python3
"""
Unit test suite untuk Lensa Semantik (MiniLM-L12) & Re-Ranker Zona Abu-Abu AR Tilik.
"""

import os
import sys
import unittest
from pathlib import Path

_parent_dir = str(Path(__file__).resolve().parents[1])
if _parent_dir not in sys.path:
    sys.path.insert(0, _parent_dir)

from worker.semantic_lens import (
    clean_chunk_text_for_embedding,
    chunk_document_semantically,
    is_semantic_engine_ready,
    SemanticLens,
)
from worker.semantic_verifier import SemanticVerifier


class TestSemanticLens(unittest.TestCase):

    def test_clean_chunk_text_for_embedding(self):
        sample = "Pembangkit listrik [📐 Rumus Matematika: E=mc^2] menggunakan [💻 Blok Kode: print('ok')] inverter pintar."
        cleaned = clean_chunk_text_for_embedding(sample)
        self.assertNotIn("[📐", cleaned)
        self.assertNotIn("[💻", cleaned)
        self.assertIn("Pembangkit listrik", cleaned)
        self.assertIn("inverter pintar.", cleaned)

    def test_chunk_document_semantically(self):
        pages = [
            {
                "page_number": 1,
                "text": "Model representasi bahasa berbasis transformer memanfaatkan mekanisme atensi mandiri untuk menangkap dependensi kontekstual jarak jauh dalam korpus teks berskala sangat besar.\n\nPenerapan pola makan rendah gula terbukti secara klinis mampu menurunkan prevalensi diabetes melitus tipe 2 pada lansia."
            }
        ]
        chunks = chunk_document_semantically(pages, target_words=100, min_words=10)
        self.assertTrue(len(chunks) >= 1)
        self.assertEqual(chunks[0]["page_number"], 1)
        self.assertTrue(chunks[0]["word_count"] >= 10)

    def test_should_evaluate_gray_zone_logic(self):
        verifier = SemanticVerifier()

        # 1. Kasus kemiripan rendah normal (< 5%) -> Bukan zona abu-abu
        should_eval, reason = verifier.should_evaluate_gray_zone(lexical_similarity=2.5, evasion_data={"is_manipulated": False})
        self.assertFalse(should_eval)
        self.assertEqual(reason, "outside_gray_zone")

        # 2. Kasus kemiripan leksikal moderat (5% - 25%) -> Zona abu-abu
        should_eval, reason = verifier.should_evaluate_gray_zone(lexical_similarity=12.0)
        self.assertTrue(should_eval)
        self.assertEqual(reason, "gray_zone_lexical_range")

        # 3. Kasus kemiripan leksikal tinggi (>= 25%) -> Di luar zona abu-abu
        should_eval, reason = verifier.should_evaluate_gray_zone(lexical_similarity=30.0)
        self.assertFalse(should_eval)
        self.assertEqual(reason, "outside_gray_zone")

        # 4. Kasus evasif manipulatif aktif -> Selalu dievaluasi
        should_eval, reason = verifier.should_evaluate_gray_zone(lexical_similarity=2.0, evasion_data={"is_manipulated": True})
        self.assertTrue(should_eval)
        self.assertEqual(reason, "evasion_anomaly_detected")


if __name__ == "__main__":
    unittest.main()
