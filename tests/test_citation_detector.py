"""
Tests for Citation & Context Detector (WP-02)
Validates deterministic context detection across the 8 canonical fixture types,
accurate span offsets, auditable rationales, and non-suppression invariants.
"""

import pytest
from worker.contract_v1_1 import (
    CitationContext,
    CitationStatus,
    CitationConfidence,
    EvidenceType,
    EvidenceContractV11,
)
from worker.citation_detector import (
    detect_citation_context,
    enrich_contract_with_context,
    CitationDetectionResult,
)


def test_fixture_1_none_uncited():
    text = "Algoritma hybrid menggabungkan pencocokan eksak dan kemiripan kosinus tf-idf secara paralel."
    res = detect_citation_context(text)

    assert res.context_type == CitationContext.NONE.value
    assert res.citation_status == CitationStatus.NOT_CITED.value
    assert res.confidence == CitationConfidence.HIGH.value
    assert res.matched_span is None
    assert res.span_start is None
    assert res.span_end is None
    assert "No adjacent citation marker" in res.rationale


def test_fixture_2_parenthetical_citation_narrative_and_brackets():
    # Narrative form: "Menurut Smith (2024)..."
    text1 = "Menurut Smith (2024), dekomposisi bukti harus memisahkan overlap langsung dari kesamaan leksikal."
    res1 = detect_citation_context(text1)

    assert res1.context_type == CitationContext.PARENTHETICAL_CITATION.value
    assert res1.citation_status == CitationStatus.CITED.value
    assert res1.confidence == CitationConfidence.HIGH.value
    assert res1.matched_span == "Menurut Smith (2024)"
    assert res1.span_start == 0
    assert res1.span_end == 20
    assert text1[res1.span_start:res1.span_end] == res1.matched_span
    assert "Detected author-year citation pattern" in res1.rationale

    # Standard parenthetical: "(Smith, 2024)"
    text2 = "Dekomposisi bukti memisahkan overlap langsung dari kemiripan leksikal (Smith, 2024)."
    res2 = detect_citation_context(text2)

    assert res2.context_type == CitationContext.PARENTHETICAL_CITATION.value
    assert res2.citation_status == CitationStatus.CITED.value
    assert res2.matched_span == "(Smith, 2024)"
    assert text2[res2.span_start:res2.span_end] == res2.matched_span

    # Indonesian author with 'dkk.': "Santoso dkk. (2023)"
    text3 = "Berdasarkan Santoso dkk. (2023), evaluasi teks harus adil."
    res3 = detect_citation_context(text3)
    assert res3.context_type == CitationContext.PARENTHETICAL_CITATION.value
    assert "Santoso dkk. (2023)" in res3.matched_span


def test_fixture_3_inline_citation():
    # Inline bracketed author: "[Smith, 2024]"
    text1 = "Evaluasi ketahanan sistem kemiripan teks menggunakan kasus sintetis terarah [Smith, 2024]."
    res1 = detect_citation_context(text1)

    assert res1.context_type == CitationContext.INLINE_CITATION.value
    assert res1.citation_status == CitationStatus.CITED.value
    assert res1.confidence == CitationConfidence.HIGH.value
    assert res1.matched_span == "[Smith, 2024]"
    assert text1[res1.span_start:res1.span_end] == res1.matched_span
    assert "bracketed inline reference marker" in res1.rationale

    # Detached bracketed numeric: " [1]"
    text2 = "Evaluasi ketahanan teks menggunakan model hybrid [1]."
    res2 = detect_citation_context(text2)
    assert res2.context_type == CitationContext.INLINE_CITATION.value
    assert res2.matched_span == "[1]"


def test_fixture_4_direct_quote():
    # ASCII double quote
    text1 = 'Penulis menegaskan bahwa "bukti kemiripan leksikal tidak boleh dianggap sebagai vonis otomatis plagiarisme".'
    res1 = detect_citation_context(text1)

    assert res1.context_type == CitationContext.DIRECT_QUOTE.value
    assert res1.citation_status == CitationStatus.QUOTE.value
    assert res1.confidence == CitationConfidence.HIGH.value
    assert res1.matched_span == '"bukti kemiripan leksikal tidak boleh dianggap sebagai vonis otomatis plagiarisme"'
    assert text1[res1.span_start:res1.span_end] == res1.matched_span
    assert "Detected passage enclosed within typographic or direct quotation marks" in res1.rationale

    # Typographic curly quote
    text2 = 'Peneliti menyatakan “kesamaan leksikal adalah indikasi awal”.'
    res2 = detect_citation_context(text2)
    assert res2.context_type == CitationContext.DIRECT_QUOTE.value
    assert res2.matched_span == '“kesamaan leksikal adalah indikasi awal”'


def test_fixture_5_block_quote():
    # 4 spaces indentation
    text1 = "    Setiap sistem peninjauan integritas akademik harus menempatkan telaah manusia sebagai penentu akhir substantif, bukan menyerahkan vonis kepada metrik numerik semata."
    res1 = detect_citation_context(text1)

    assert res1.context_type == CitationContext.BLOCK_QUOTE.value
    assert res1.citation_status == CitationStatus.QUOTE.value
    assert res1.confidence == CitationConfidence.HIGH.value
    assert res1.matched_span.startswith("    Setiap sistem")
    assert "indented block quotation" in res1.rationale

    # Markdown blockquote prefix
    text2 = "> Integritas akademik menuntut atribusi sumber yang jujur dan transparan."
    res2 = detect_citation_context(text2)
    assert res2.context_type == CitationContext.BLOCK_QUOTE.value
    assert res2.citation_status == CitationStatus.QUOTE.value


def test_fixture_6_footnote():
    # Attached bracketed footnote: "dokumen[1]"
    text1 = "Metrik IDF dihitung menggunakan logaritma basis 10 dari total korpus dibagi frekuensi dokumen[1]."
    res1 = detect_citation_context(text1)

    assert res1.context_type == CitationContext.FOOTNOTE.value
    assert res1.citation_status == CitationStatus.CITED.value
    assert res1.confidence == CitationConfidence.HIGH.value
    assert res1.matched_span == "[1]"
    assert text1[res1.span_start:res1.span_end] == res1.matched_span
    assert "footnote reference marker" in res1.rationale

    # Superscript footnote numeral: "dokumen¹"
    text2 = "Metrik dihitung dari korpus referensi¹."
    res2 = detect_citation_context(text2)
    assert res2.context_type == CitationContext.FOOTNOTE.value
    assert res2.matched_span == "¹"


def test_fixture_7_bibliography():
    text1 = "Smith, J., & Rahman, A. (2024). Dual-Lens Evidence Decomposition in Academic Writing. Journal of Academic Ethics, 15(2), 112-128."
    res1 = detect_citation_context(text1)

    assert res1.context_type == CitationContext.BIBLIOGRAPHY.value
    assert res1.citation_status == CitationStatus.NOT_APPLICABLE.value
    assert res1.confidence == CitationConfidence.HIGH.value
    assert "formal reference list or bibliography" in res1.rationale

    # Bibliography header
    text2 = "DAFTAR PUSTAKA"
    res2 = detect_citation_context(text2)
    assert res2.context_type == CitationContext.BIBLIOGRAPHY.value
    assert res2.citation_status == CitationStatus.NOT_APPLICABLE.value


def test_fixture_8_ambiguous_unknown():
    text = "Fragmentasi teks tidak lengkap ... xx (??) tanpa konteks sitasi teridentifikasi."
    res = detect_citation_context(text)

    assert res.context_type == CitationContext.UNKNOWN.value
    assert res.citation_status == CitationStatus.UNKNOWN.value
    assert res.confidence == CitationConfidence.UNKNOWN.value
    assert "Unable to determine academic attribution context reliably" in res.rationale
    assert "xx (??" in res.matched_span


def test_multi_cue_direct_quote_with_parenthetical():
    """
    Kutipan langsung yang disertai sitasi parenthetical di sebelahnya.
    Primary context = DIRECT_QUOTE, secondary captures parenthetical citation.
    """
    text = 'Penulis menegaskan bahwa "bukti leksikal harus dipisahkan dari pasase" (Smith, 2024).'
    res = detect_citation_context(text)

    assert res.context_type == CitationContext.DIRECT_QUOTE.value
    assert res.citation_status == CitationStatus.QUOTE.value
    assert len(res.secondary_matches) > 0
    assert res.secondary_matches[0]["context_type"] == CitationContext.PARENTHETICAL_CITATION.value
    assert "(Smith, 2024)" in res.rationale


def test_empty_and_whitespace_input():
    for empty_val in (None, "", "   \n\t  "):
        res = detect_citation_context(empty_val)
        assert res.context_type == CitationContext.NONE.value
        assert res.citation_status == CitationStatus.NOT_CITED.value
        assert res.matched_span is None


def test_enrich_contract_non_suppression_invariant():
    """
    Verifikasi bahwa mengaplikasikan hasil deteksi ke EvidenceContractV11
    tidak merusak atau menurunkan metrik kemiripan numerik (Gate V11-D).
    """
    contract = EvidenceContractV11(
        engine="exact",
        similarity=85.25,
        matched_terms=45,
        longest_match=22,
        source_count=1,
        match_count=2,
        avg_idf=2.15,
        max_idf=2.85,
        evidence_type=EvidenceType.DIRECT_TEXTUAL_OVERLAP.value,
    )

    sample_text = "Menurut Smith (2024), model hybrid menggabungkan exact dan tfidf."
    enriched = enrich_contract_with_context(contract, sample_text)

    # Invariant: Kemiripan numerik tetap persis sama
    assert enriched.similarity == 85.25
    assert enriched.matched_terms == 45
    assert enriched.longest_match == 22
    assert enriched.source_count == 1

    # Metadata konteks terisi dengan benar
    assert enriched.citation_context == CitationContext.PARENTHETICAL_CITATION.value
    assert enriched.citation_status == CitationStatus.CITED.value
    assert enriched.citation_confidence == CitationConfidence.HIGH.value
    assert enriched.context_span == "Menurut Smith (2024)"
    assert enriched.rationale is not None


def test_citation_auditor_bibliography_english_and_single_name():
    from worker.citation_auditor import audit_citations_and_bibliography

    sample_doc = """
    Penelitian ini mendalami konstruksi identitas menurut pandangan sosial (Ritzer & Smart, 2012).
    Selain itu, integrasi sosial suku juga dipelajari oleh (Fatmawati, 2007) dan (Halawa, 2022).
    Pemikiran tentang subsuku juga diungkapkan oleh (John Bamba, 2008).

    BIBLIOGRAPHY

    Fatmawati. (2007). Integrasi Sosial Masyarakat Multi-etnis di kecamatan Sungai Ambawang.
    Halawa, arius arifman. (2022). Syncretism on Catholic Symbols in The Invulnerability Rituals.
    John Bamba (Ed.). (2008). Keberagaman subsuku dan bahasa Dayak di Kalimantan Barat.
    Ritzer, G., & Smart, B. (2012). Handbook Teori Sosial. Nusa Indah.
    """

    res = audit_citations_and_bibliography(sample_doc)
    assert res.has_bibliography_section is True
    assert res.total_in_text_citations == 4
    assert res.total_bibliography_entries == 4
    assert res.matched_citations_count == 4
    assert res.phantom_citations_count == 0
    assert res.citation_health_score == 100.0


def test_citation_auditor_footnotes_not_leaking_into_bibliography():
    from worker.citation_auditor import audit_citations_and_bibliography

    sample_doc = """
    Penelitian ini mendalami pendidikan asrama (Yuswanto, 2021).
    Dalam tradisi lokal, ritual pengobatan sering menggunakan doa tradisional.

    BIBLIOGRAPHY

    Yuswanto, F. (2021). Revitalisasi Pengelolaan Asrama Dalam Mengembangkan Pendidikan Katolik Bagi Suku Dayak di Wilayah Perbatasan Kabupaten Sanggau. VOCAT: JURNAL PENDIDIKAN KATOLIK, 1(1), 30–36.

    [Catatan Kaki / Footnotes]
    Dayak Mentuka adalah: Sub suku Dayak di Kalimantan Barat umumnya menempati wilayah Kabupaten Sekadau.
    Dalam penulisan berikutnya, anggota suku Dayak Mentuka yang akan berpindah agama ke agama Islam akan disebut sebagai konversan. Sinan adalah istilah khas dalam suku Dayak Mentuka.
    """

    res = audit_citations_and_bibliography(sample_doc)
    assert res.has_bibliography_section is True
    assert res.total_in_text_citations == 1
    assert res.total_bibliography_entries == 1
    assert res.matched_citations_count == 1
    assert res.orphan_references_count == 0
    assert len(res.orphan_references) == 0
    assert res.matched_items[0]["matched_entry_text"].strip().endswith("30–36.")


def test_citation_auditor_compound_multi_year_and_narrative_citations():
    from worker.citation_auditor import audit_citations_and_bibliography

    sample_doc = """
    Beberapa riset terdahulu menunjukkan pentingnya dinamika sosial (Bansal et al., 2024; Masenya, 2021).
    Selain itu, fenomena integrasi juga ditinjau oleh (Howitt, 2002; Laurie et al., 2003).
    Kajian penting lainnya diungkapkan dalam (Maarif, 2012, 2023).
    Dalam analisis kualitatif, Miles, Huberman, and Saldaña (2014) mengusulkan kondensasi data.
    Pendekatan evaluatif juga didasarkan pada Patton's (2015) serta Radcliffe-Brown (1940, 1965).

    REFERENCES

    Bansal, S., & Masenya, T. (2024). Social dynamics in indigenous communities.
    Howitt, R. (2002). Rethinking resource management.
    Laurie, N., Andolina, R., & Radcliffe, S. (2003). Indigenous professionalization.
    Maarif, S. (2012). Dimensions of religious conversion.
    Maarif, S. (2023). Customary governance in Kalimantan.
    Masenya, T. M. (2021). Indigenous knowledge preservation.
    Miles, M. B., Huberman, A. M., & Saldaña, J. (2014). Qualitative data analysis: A methods sourcebook.
    Patton, M. Q. (2015). Qualitative research & evaluation methods.
    Radcliffe-Brown, A. R. (1940). On social structure.
    Radcliffe-Brown, A. R. (1965). Structure and function in primitive society.
    """

    res = audit_citations_and_bibliography(sample_doc, verify_external=False)
    assert res.has_bibliography_section is True
    assert res.total_bibliography_entries == 10
    # Citations in text:
    # Bansal (2024), Masenya (2021), Howitt (2002), Laurie (2003), Maarif (2012), Maarif (2023),
    # Saldana/Miles (2014), Patton (2015), Radcliffe-Brown (1940), Radcliffe-Brown (1965)
    assert res.total_in_text_citations == 10
    assert res.matched_citations_count == 10
    assert res.phantom_citations_count == 0
    assert res.orphan_references_count == 0
    assert res.citation_health_score == 100.0


