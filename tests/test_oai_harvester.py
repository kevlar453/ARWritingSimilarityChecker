import pytest
import xml.etree.ElementTree as ET
from pathlib import Path
import tempfile

from worker.oai_harvester import (
    OAIHarvester,
    clean_xml_tag
)

SAMPLE_OAI_XML = """<?xml version="1.0" encoding="UTF-8"?>
<OAI-PMH xmlns="http://www.openarchives.org/OAI/2.0/"
         xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
    <responseDate>2026-10-05T20:00:00Z</responseDate>
    <request verb="ListRecords" metadataPrefix="oai_dc">https://repo.sttarastamar.ac.id/cgi/oai2</request>
    <ListRecords>
        <record>
            <header>
                <identifier>oai:repo.sttarastamar.ac.id:101</identifier>
                <datestamp>2026-05-10T12:00:00Z</datestamp>
                <setSpec>thesis</setSpec>
            </header>
            <metadata>
                <oai_dc:dc xmlns:oai_dc="http://www.openarchives.org/OAI/2.0/oai_dc/"
                           xmlns:dc="http://purl.org/dc/elements/1.1/">
                    <dc:title>Penerapan Etika Pelayanan Terhadap Pertumbuhan Jemaat Lokal</dc:title>
                    <dc:creator>Sutrisno, Markus</dc:creator>
                    <dc:creator>Wulandari, Hana</dc:creator>
                    <dc:description>Penelitian ini bertujuan untuk meneliti relasi antara etika kepemimpinan rohani dan dinamika pertumbuhan jemaat pada gereja kontemporer. Menggunakan pendekatan kualitatif dan studi kasus pada lima gereja di wilayah urban.</dc:description>
                    <dc:date>2025</dc:date>
                    <dc:type>Thesis</dc:type>
                    <dc:subject>Teologi Praktika; Kepemimpinan; Pertumbuhan Gereja</dc:subject>
                    <dc:identifier>https://repo.sttarastamar.ac.id/101/</dc:identifier>
                    <dc:identifier>https://repo.sttarastamar.ac.id/101/1/Markus_Sutrisno_Tesis.pdf</dc:identifier>
                </oai_dc:dc>
            </metadata>
        </record>
        <record>
            <header status="deleted">
                <identifier>oai:repo.sttarastamar.ac.id:102</identifier>
                <datestamp>2026-06-01T10:00:00Z</datestamp>
            </header>
        </record>
        <record>
            <header>
                <identifier>oai:repo.sttarastamar.ac.id:103</identifier>
                <datestamp>2026-07-15T09:00:00Z</datestamp>
                <setSpec>journal</setSpec>
            </header>
            <metadata>
                <oai_dc:dc xmlns:oai_dc="http://www.openarchives.org/OAI/2.0/oai_dc/"
                           xmlns:dc="http://purl.org/dc/elements/1.1/">
                    <dc:title>Analisis Teks Naratif Injil Sinoptik Melalui Pendekatan Retorika Semitik</dc:title>
                    <dc:creator>Yohanes Kurniadi</dc:creator>
                    <dc:description>Artikel ini mengkaji struktur paralelisme dan kiasmus dalam teks Injil Markus dan Matius.</dc:description>
                    <dc:date>2026</dc:date>
                    <dc:type>Article</dc:type>
                    <dc:subject>Eksegesis Biblika; Perjanjian Baru</dc:subject>
                    <dc:identifier>https://journal.sttarastamar.ac.id/index.php/jurnal/article/view/55</dc:identifier>
                </oai_dc:dc>
            </metadata>
        </record>
        <resumptionToken cursor="0" completeListSize="150">token_page_2_xyz</resumptionToken>
    </ListRecords>
</OAI-PMH>
"""


def test_clean_xml_tag():
    assert clean_xml_tag("{http://www.openarchives.org/OAI/2.0/}record") == "record"
    assert clean_xml_tag("{http://purl.org/dc/elements/1.1/}title") == "title"
    assert clean_xml_tag("plain_tag") == "plain_tag"


def test_parse_oai_xml_records():
    root = ET.fromstring(SAMPLE_OAI_XML)
    harvester = OAIHarvester("https://repo.sttarastamar.ac.id/cgi/oai2")
    records = harvester._parse_records_from_xml(root)

    # Record 102 harus dilewati karena status="deleted"
    assert len(records) == 2

    # Verifikasi record pertama (thesis)
    r1 = records[0]
    assert r1["oai_identifier"] == "oai:repo.sttarastamar.ac.id:101"
    assert r1["title"] == "Penerapan Etika Pelayanan Terhadap Pertumbuhan Jemaat Lokal"
    assert "Sutrisno, Markus" in r1["creators"]
    assert "Wulandari, Hana" in r1["creators"]
    assert "Pertumbuhan Gereja" in " ".join(r1["subjects"])
    assert r1["date"] == "2025"
    assert any(".pdf" in ident for ident in r1["identifiers"])

    # Verifikasi record kedua (article / journal)
    r2 = records[1]
    assert r2["oai_identifier"] == "oai:repo.sttarastamar.ac.id:103"
    assert "Injil Sinoptik" in r2["title"]
    assert r2["creators"] == ["Yohanes Kurniadi"]


def test_resumption_token_parsing():
    root = ET.fromstring(SAMPLE_OAI_XML)
    token = None
    for elem in root.iter():
        if clean_xml_tag(elem.tag) == "resumptionToken":
            token = elem.text.strip() if elem.text else None
            break
    assert token == "token_page_2_xyz"


def test_oai_open_access_filter_and_ssrf_guard():
    import urllib.error
    from worker.oai_harvester import _validate_external_http_url, is_record_open_access, _sanitize_xml_bytes

    with pytest.raises(ValueError):
        _validate_external_http_url("http://127.0.0.1/oai2")
    with pytest.raises(ValueError):
        _validate_external_http_url("http://169.254.169.254/latest/meta-data/")
    with pytest.raises(ValueError):
        _validate_external_http_url("file:///etc/passwd")

    assert is_record_open_access({"rights": ["Open Access License CC-BY"], "description": "Abstrak penuh..."})[0] is True
    assert is_record_open_access({"rights": ["Restricted Access - Campus only"], "description": ""})[0] is False
    assert is_record_open_access({"rights": [], "description": "Embargo until 2030 internal use only"})[0] is False

    dirty_xml = b"<OAI-PMH>\x00\x07<title>Judul Bersih</title></OAI-PMH>"
    cleaned = _sanitize_xml_bytes(dirty_xml)
    assert "\x00" not in cleaned and "\x07" not in cleaned
    assert "<title>Judul Bersih</title>" in cleaned


def test_oai_pilot_harvest_stores_provenance_dedup_and_thesis_type(tmp_path):
    from unittest.mock import patch
    from worker.database import get_connection
    from worker.matcher import run_similarity_check

    pilot_xml = b"""<?xml version="1.0" encoding="UTF-8"?>
<OAI-PMH xmlns="http://www.openarchives.org/OAI/2.0/">
  <ListRecords>
    <record>
      <header>
        <identifier>oai:pilot.undip.ac.id:test-9001</identifier>
        <setSpec>74797065733D746865736973</setSpec>
      </header>
      <metadata>
        <oai_dc:dc xmlns:oai_dc="http://www.openarchives.org/OAI/2.0/oai_dc/"
                   xmlns:dc="http://purl.org/dc/elements/1.1/">
          <dc:title>Rancang Bangun Sistem Monitoring Kualitas Air Tambak Udang Berbasis Sensor IoT Dan Mikrokontroler</dc:title>
          <dc:creator>Pratama, Dimas</dc:creator>
          <dc:description>Penelitian skripsi ini merancang sistem pemantauan suhu derajat keasaman dan kekeruhan air tambak udang vaname secara waktu nyata memakai sensor terkalibrasi modul transmisi nirkabel serta penyimpanan basis data berbasis web untuk membantu pembudidaya mengambil keputusan pengelolaan air secara tepat.</dc:description>
          <dc:date>2025</dc:date>
          <dc:type>Thesis</dc:type>
          <dc:rights>Open Access</dc:rights>
          <dc:identifier>https://eprints.undip.ac.id/9001/</dc:identifier>
        </oai_dc:dc>
      </metadata>
    </record>
    <record>
      <header>
        <identifier>oai:pilot.undip.ac.id:test-9002</identifier>
        <setSpec>74797065733D746865736973</setSpec>
      </header>
      <metadata>
        <oai_dc:dc xmlns:oai_dc="http://www.openarchives.org/OAI/2.0/oai_dc/"
                   xmlns:dc="http://purl.org/dc/elements/1.1/">
          <dc:title>Skripsi Tertutup Tanpa Abstrak Memadai</dc:title>
          <dc:description>Abstrak singkat.</dc:description>
          <dc:rights>Closed Access</dc:rights>
        </oai_dc:dc>
      </metadata>
    </record>
  </ListRecords>
</OAI-PMH>"""

    harvester = OAIHarvester(
        "https://eprints.undip.ac.id/cgi/oai2",
        corpus_dir=tmp_path,
        repository_name="UNDIP-IR Pilot",
    )

    conn = get_connection()
    created_ids = []
    try:
        with patch("worker.oai_harvester.fetch_url_content", return_value=(pilot_xml, "text/xml")), \
             patch("worker.oai_harvester.rebuild_fingerprint_stats"):
            summary1 = harvester.harvest_records(
                set_spec="74797065733D746865736973",
                limit=5,
                download_fulltext=False,
                open_access_only=True,
                force_source_type="thesis",
                request_delay=0.0,
                harvest_batch_id="pilot_test_batch",
            )
            assert summary1["total_harvested"] == 2
            assert summary1["indexed_count"] == 1
            assert summary1["filtered_closed_count"] == 1
            doc_id = int(summary1["records"][0]["document_id"])
            created_ids.append(doc_id)

            # Verifikasi provenance lengkap tersimpan di tabel documents
            cur = conn.cursor(dictionary=True)
            cur.execute("SELECT * FROM documents WHERE id = %s", (doc_id,))
            doc_row = cur.fetchone()
            assert doc_row["source_type"] == "thesis"
            assert doc_row["oai_identifier"] == "oai:pilot.undip.ac.id:test-9001"
            assert doc_row["source_repository"] == "UNDIP-IR Pilot"
            assert doc_row["source_url"] == "https://eprints.undip.ac.id/9001/"
            assert doc_row["harvest_batch_id"] == "pilot_test_batch"
            cur.close()

            # Panen kedua untuk ID OAI yang sama harus terdeteksi dedup -> skipped
            summary2 = harvester.harvest_records(
                set_spec="74797065733D746865736973",
                limit=5,
                download_fulltext=False,
                open_access_only=True,
                force_source_type="thesis",
                request_delay=0.0,
            )
            assert summary2["indexed_count"] == 0
            assert summary2["skipped_count"] == 2
    finally:
        c2 = conn.cursor()
        try:
            for did in created_ids:
                c2.execute("DELETE FROM fingerprint_documents WHERE document_id = %s", (did,))
                c2.execute("DELETE FROM document_chunks WHERE document_id = %s", (did,))
                c2.execute("DELETE FROM document_pages WHERE document_id = %s", (did,))
                c2.execute("DELETE FROM documents WHERE id = %s", (did,))
            conn.commit()
        finally:
            c2.close()
            conn.close()
