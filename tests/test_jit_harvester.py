"""
Unit Test Suite untuk worker/jit_harvester.py
Memverifikasi ekstraksi topik substantif Just-In-Time (JIT) dan penemuan korpus sebidang otomatis.
"""

import pytest
from worker.jit_harvester import extract_substantive_topics


def test_extract_substantive_topics_vocational_smk():
    title = "PENGARUH MOTIVASI DAN MINAT TERHADAP PRESTASI SISWA PADA MATA DIKLAT KESELAMATAN DAN KESEHATAN KERJA DI SMK NEGERI 1 SEDAYU"
    topics = extract_substantive_topics(title)

    assert len(topics) >= 1
    # Harus menangkap domain substantif K3 SMK
    assert any("keselamatan dan kesehatan kerja" in t.lower() or "smk" in t.lower() for t in topics)
    # Tidak boleh ada kata pengisi administratif 'pengaruh', 'mata diklat'
    assert not any(t.startswith("pengaruh") for t in topics)


def test_extract_substantive_topics_theology():
    title = "ANALISIS PENGARUH KEPEMIMPINAN PASTORAL GEREJA TERHADAP PERTUMBUHAN IMAN JEMAAT"
    topics = extract_substantive_topics(title)

    assert len(topics) >= 1
    assert any("pastoral" in t.lower() or "gereja" in t.lower() for t in topics)
    assert not any(t.startswith("analisis") for t in topics)


def test_extract_substantive_topics_law():
    title = "TINJAUAN YURIDIS TERHADAP TINDAK PIDANA KORUPSI DALAM PENGADAAN BARANG DAN JASA"
    topics = extract_substantive_topics(title)

    assert len(topics) >= 1
    assert any("hukum" in t.lower() or "korupsi" in t.lower() or "pidana" in t.lower() for t in topics)


def test_harvest_topic_references_jit_mocked():
    from unittest.mock import patch
    from worker.jit_harvester import harvest_topic_references_jit

    with patch("worker.jit_harvester.search_openalex_works") as mock_search, \
         patch("worker.jit_harvester.ingest_scholarly_work") as mock_ingest:

        mock_search.return_value = [
            {
                "title": "Analisis Penerapan K3 di Bengkel Otomotif SMK",
                "year": 2022,
                "source_name": "Jurnal Pendidikan Vokasi",
                "doi": "https://doi.org/10.1234/jpv.2022",
                "landing_url": "https://example.org/article",
                "pdf_url": None,
            }
        ]
        mock_ingest.return_value = {
            "status": "indexed",
            "document_id": 999,
            "title": "Analisis Penerapan K3 di Bengkel Otomotif SMK"
        }

        res = harvest_topic_references_jit("PENGARUH MOTIVASI TERHADAP K3 DI SMK", max_works=1, connection="mock_conn")
        assert res["status"] == "success"
        assert res["total_discovered"] == 1
        assert res["max_works"] == 1
        assert res["indexed_count"] == 1
        assert isinstance(res["duration_ms"], float)
        assert res["discovered_works"][0]["document_id"] == 999
        assert res["discovered_works"][0]["title"] == "Analisis Penerapan K3 di Bengkel Otomotif SMK"


def test_jit_harvester_default_limit_is_five_and_caps_correctly():
    import inspect
    from pathlib import Path
    from unittest.mock import patch
    from worker.jit_harvester import harvest_topic_references_jit

    sig = inspect.signature(harvest_topic_references_jit)
    assert sig.parameters["max_works"].default == 5

    reporter_src = (Path(__file__).resolve().parents[1] / "worker" / "reporter.py").read_text(encoding="utf-8")
    assert 'os.getenv("JIT_HARVESTER_MAX_WORKS", "5")' in reporter_src
    assert "max_works=3" not in reporter_src

    sample_works = [
        {
            "title": f"Artikel Uji JIT Nomor {i}",
            "year": 2024,
            "source_name": "Jurnal Uji",
            "doi": f"https://doi.org/10.9999/jit.{i}",
            "landing_url": f"https://example.org/work/{i}",
            "pdf_url": None,
        }
        for i in range(1, 8)
    ]

    with patch("worker.jit_harvester.search_openalex_works", return_value=sample_works) as mock_search, \
         patch("worker.jit_harvester.ingest_scholarly_work") as mock_ingest:
        mock_ingest.side_effect = [
            {"status": "indexed", "document_id": 1000 + i, "title": w["title"]}
            for i, w in enumerate(sample_works)
        ]

        res = harvest_topic_references_jit("SISTEM DETEKSI HAMA TANAMAN PADI BERBASIS IOT", connection="mock_conn")
        assert res["status"] == "success"
        assert res["max_works"] == 5
        assert res["total_discovered"] == 5
        assert res["indexed_count"] == 5
        assert mock_search.call_args.kwargs["limit"] == 5
        assert len(res["discovered_works"]) == 5
