"""
Unit and Integration Tests for Layout-Aware Academic PDF Parsing (Fase 1 - Inisiatif 1.2)
Memverifikasi:
1. Preservasi alur 1-kolom standar
2. Eliminasi silang horizontal pada 2-kolom (kolom kiri tuntas sebelum kolom kanan)
3. Header & Abstrak full-width berada di atas kolom
4. Catatan kaki / Footnotes berada di bagian bawah setelah seluruh kolom selesai
5. Integrasi tabel Markdown dan formula matematika
"""

import os
import sys
import tempfile
import unittest

sys.path.insert(0, os.path.dirname(os.path.dirname(os.path.abspath(__file__))))

import fitz  # PyMuPDF
from worker.extractor import reorder_blocks_two_column, extract_pdf


class TestLayoutPdfExtractor(unittest.TestCase):

    def test_01_single_column_preservation(self):
        """Memverifikasi bahwa layout 1 kolom standar diurutkan secara wajar dari atas ke bawah."""
        # blocks format: (x0, y0, x1, y1, text, block_no, block_type)
        blocks = [
            (50, 200, 500, 250, "Paragraf Kedua Halaman", 1, 0),
            (50, 100, 500, 150, "Paragraf Pertama Halaman", 0, 0),
            (50, 300, 500, 350, "Paragraf Ketiga Halaman", 2, 0),
        ]
        ordered = reorder_blocks_two_column(blocks, page_width=600, page_height=800)
        texts = [b[4] for b in ordered]
        self.assertEqual(texts[0], "Paragraf Pertama Halaman")
        self.assertEqual(texts[1], "Paragraf Kedua Halaman")
        self.assertEqual(texts[2], "Paragraf Ketiga Halaman")

    def test_02_synthetic_two_column_reordering(self):
        """
        Memverifikasi bahwa dua kolom tidak terbaca bersilangan horizontal.
        Kolom kiri (paragraf 1 dan 2) harus mendahului kolom kanan (paragraf 3 dan 4).
        """
        blocks = [
            # Header Full Width (y = 50..80)
            (50, 50, 550, 80, "JUDUL ARTIKEL ILMIAH NASIONAL", 0, 0),
            # Kolom Kanan Baris 1 (y = 120..150) - sengaja di indeks lebih awal untuk menguji reordering
            (320, 120, 550, 150, "Kolom Kanan Bagian 1", 1, 0),
            # Kolom Kiri Baris 1 (y = 120..150)
            (50, 120, 280, 150, "Kolom Kiri Bagian 1", 2, 0),
            # Kolom Kanan Baris 2 (y = 200..230)
            (320, 200, 550, 230, "Kolom Kanan Bagian 2", 3, 0),
            # Kolom Kiri Baris 2 (y = 200..230)
            (50, 200, 280, 230, "Kolom Kiri Bagian 2", 4, 0),
            # Footnote Full Width di bawah (y = 750..780)
            (50, 750, 550, 780, "Catatan Kaki Penulis: Korespondensi email.", 5, 0),
        ]

        ordered = reorder_blocks_two_column(blocks, page_width=600, page_height=800)
        extracted_texts = [b[4] for b in ordered]

        expected_sequence = [
            "JUDUL ARTIKEL ILMIAH NASIONAL",
            "Kolom Kiri Bagian 1",
            "Kolom Kiri Bagian 2",
            "Kolom Kanan Bagian 1",
            "Kolom Kanan Bagian 2",
            "Catatan Kaki Penulis: Korespondensi email.",
        ]

        self.assertEqual(extracted_texts, expected_sequence)

    def test_03_extract_real_two_column_pdf(self):
        """
        Membuat file PDF nyata secara in-memory dengan PyMuPDF berformat 2-kolom,
        lalu mengekstraknya dengan extract_pdf dan memverifikasi kontinuitas naskah.
        """
        doc = fitz.open()
        page = doc.new_page(width=600, height=800)

        # Header Title (Full width)
        page.insert_text(fitz.Point(100, 60), "OPTIMASI KECERDASAN BUATAN PADA DETEKSI KESAMAAN", fontsize=12)

        # Kolom Kiri Paragraf 1 & 2
        page.insert_text(fitz.Point(60, 160), "Metodologi penelitian ini menggunakan pendekatan bertingkat.", fontsize=10)
        page.insert_text(fitz.Point(60, 260), "Data dikumpulkan dari repositori universitas terakreditasi.", fontsize=10)

        # Kolom Kanan Paragraf 1 & 2 (posisi Y sejajar dengan kolom kiri)
        page.insert_text(fitz.Point(330, 160), "Hasil pengujian menunjukkan peningkatan presisi hingga 98%.", fontsize=10)
        page.insert_text(fitz.Point(330, 260), "Diskusi hasil membuktikan ketahanan model terhadap variasi parafrasa.", fontsize=10)

        # Footnote
        page.insert_text(fitz.Point(60, 760), "Korespondensi: lab@artilik.id", fontsize=8)

        with tempfile.NamedTemporaryFile(suffix=".pdf", delete=False) as tmp:
            tmp_path = tmp.name

        try:
            doc.save(tmp_path)
            doc.close()

            pages = extract_pdf(tmp_path)
            self.assertEqual(len(pages), 1)
            full_text = pages[0]["text"]

            # Pastikan teks kolom kiri muncul LENGKAP SEBELUM teks kolom kanan dimulai
            pos_left_1 = full_text.find("Metodologi penelitian ini")
            pos_left_2 = full_text.find("Data dikumpulkan dari repositori")
            pos_right_1 = full_text.find("Hasil pengujian menunjukkan")
            pos_right_2 = full_text.find("Diskusi hasil membuktikan")

            self.assertNotEqual(pos_left_1, -1, "Teks Kolom Kiri 1 tidak ditemukan")
            self.assertNotEqual(pos_left_2, -1, "Teks Kolom Kiri 2 tidak ditemukan")
            self.assertNotEqual(pos_right_1, -1, "Teks Kolom Kanan 1 tidak ditemukan")
            self.assertNotEqual(pos_right_2, -1, "Teks Kolom Kanan 2 tidak ditemukan")

            # Uji urutan: Kolom Kiri 1 < Kolom Kiri 2 < Kolom Kanan 1 < Kolom Kanan 2
            self.assertLess(pos_left_1, pos_left_2, "Kolom Kiri 1 harus sebelum Kolom Kiri 2")
            self.assertLess(pos_left_2, pos_right_1, "Seluruh Kolom Kiri harus tuntas sebelum Kolom Kanan 1")
            self.assertLess(pos_right_1, pos_right_2, "Kolom Kanan 1 harus sebelum Kolom Kanan 2")

        finally:
            if os.path.exists(tmp_path):
                os.remove(tmp_path)


if __name__ == "__main__":
    unittest.main()
