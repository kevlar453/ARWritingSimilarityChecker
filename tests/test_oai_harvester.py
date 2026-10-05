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
