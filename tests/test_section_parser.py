"""
Unit tests untuk AR Tilik v1.2 Smart Section Parser.
Memastikan dekomposisi struktur dokumen akademik berjalan deterministik, akurat,
dan mematuhi prinsip keadilan akademik serta isolasi baseline.
"""

import pytest
from worker.section_parser import (
    SectionZone,
    SectionType,
    parse_document_sections,
    filter_text_by_zones,
    get_core_substance_text,
    get_substantive_including_methodology,
)


def test_empty_or_whitespace_text():
    res = parse_document_sections("")
    assert res.total_words == 0
    assert res.total_sections == 0
    assert len(res.sections) == 0

    res_ws = parse_document_sections("   \n\n  \t  ")
    assert res_ws.total_words == 0
    assert res_ws.total_sections == 0


def test_standard_thesis_structure_full_flow():
    sample_thesis = """
    TESIS
    ADORASI EKARISTI DALAM SACRAMENTUM CARITATIS
    Oleh: Emiliana Vivi
    NIM: 24201009
    
    LEMBAR PERSETUJUAN
    Tesis ini telah disetujui oleh dosen pembimbing I dan II.
    
    HALAMAN PERNYATAAN BEBAS PLAGIASI
    Menyatakan dengan sesungguhnya bahwa tesis ini merupakan hasil karya saya sendiri.
    
    ABSTRAK
    Penelitian ini dilatarbelakangi oleh pentingnya memahami makna Adorasi Ekaristi.
    Hasil penelitian menunjukkan bahwa partisipasi dalam Adorasi Ekaristi memiliki hubungan positif.
    
    KATA PENGANTAR
    Puji dan syukur penulis panjatkan ke hadirat Tuhan Yang Maha Esa atas segala kasih-Nya.
    
    DAFTAR ISI
    BAB I PENDAHULUAN ...... 1
    BAB II LANDASAN TEORI ...... 9
    BAB III METODE PENELITIAN ...... 22
    
    BAB I
    PENDAHULUAN
    Gereja Katolik meyakini bahwa Ekaristi adalah puncak dan sumber seluruh kehidupan Kristiani.
    Adorasi Ekaristi dipahami sebagai ruang perjumpaan personal antara umat beriman dengan Kristus.
    
    BAB II
    LANDASAN TEORI
    Ekaristi menempati pusat kehidupan Gereja Katolik. Dalam ensiklik Ecclesia de Eucharistia,
    Paus Yohanes Paulus II menjelaskan bahwa Ekaristi sebagai pusat misteri Gereja.
    
    BAB III
    METODE PENELITIAN
    Penelitian ini menggunakan jenis penelitian lapangan dengan pendekatan mixed methods.
    Instrumen kuesioner diuji menggunakan Corrected Item-Total Correlation SPSS.
    
    BAB IV
    HASIL DAN PEMBAHASAN
    Berdasarkan hasil analisis, variabel partisipasi Adorasi Ekaristi memperoleh nilai rata-rata 33,0.
    Pertumbuhan spiritual berperan sebagai mediator dalam hubungan kedua variabel.
    
    BAB V
    KESIMPULAN DAN SARAN
    Partisipasi Orang Muda Katolik dalam Adorasi Ekaristi memiliki hubungan yang positif dan signifikan.
    
    DAFTAR PUSTAKA
    Benediktus XVI. (2007). Sacramentum Caritatis. Jakarta: KWI.
    Creswell, J. W. (2016). Pendekatan Metode Kualitatif. Yogyakarta: Pustaka Pelajar.
    
    LAMPIRAN A-1
    Kisi-kisi lembar kuesioner partisipasi OMK dalam Adorasi Ekaristi.
    """

    res = parse_document_sections(sample_thesis)

    assert res.total_sections >= 10
    assert res.total_words > 100

    # Verifikasi zona teridentifikasi dengan tepat
    zones_found = {s.section_zone for s in res.sections}
    assert SectionZone.FRONT_MATTER in zones_found
    assert SectionZone.BODY_TEXT in zones_found
    assert SectionZone.METHODOLOGY in zones_found
    assert SectionZone.BACK_MATTER in zones_found

    # Verifikasi tipe seksi spesifik
    types_found = {s.section_type for s in res.sections}
    assert SectionType.PERSETUJUAN in types_found
    assert SectionType.PERNYATAAN_BEBAS_PLAGIASI in types_found
    assert SectionType.ABSTRAK in types_found
    assert SectionType.KATA_PENGANTAR in types_found
    assert SectionType.DAFTAR_ISI in types_found
    assert SectionType.BAB_1_PENDAHULUAN in types_found
    assert SectionType.BAB_2_LANDASAN_TEORI in types_found
    assert SectionType.BAB_3_METODE_PENELITIAN in types_found
    assert SectionType.BAB_4_HASIL_PEMBAHASAN in types_found
    assert SectionType.BAB_5_KESIMPULAN_SARAN in types_found
    assert SectionType.DAFTAR_PUSTAKA in types_found
    assert SectionType.LAMPIRAN in types_found

    # Verifikasi pembagian proporsi
    assert res.core_substance_word_count > 0
    assert res.administrative_word_count > 0
    assert res.core_substance_percentage > 0.0
    assert res.administrative_percentage > 0.0
    assert round(res.core_substance_percentage + res.administrative_percentage + res.zone_percentage_distribution.get("METHODOLOGY", 0), 0) in (99.0, 100.0, 101.0)


def test_arabic_numerals_bab():
    sample_text = """
    BAB 1: PENDAHULUAN
    Latar belakang penelitian ini adalah...
    
    BAB 2: KAJIAN PUSTAKA
    Teori yang mendasari adalah...
    
    BAB 3: METODOLOGI PENELITIAN
    Penelitian ini menggunakan metode kuantitatif.
    
    BAB 4: ANALISIS DAN PEMBAHASAN
    Data menunjukkan korelasi signifikan.
    
    BAB 5: KESIMPULAN
    Kesimpulan dari penelitian ini adalah...
    """

    res = parse_document_sections(sample_text)
    types_found = [s.section_type for s in res.sections]
    assert SectionType.BAB_1_PENDAHULUAN in types_found
    assert SectionType.BAB_2_LANDASAN_TEORI in types_found
    assert SectionType.BAB_3_METODE_PENELITIAN in types_found
    assert SectionType.BAB_4_HASIL_PEMBAHASAN in types_found
    assert SectionType.BAB_5_KESIMPULAN_SARAN in types_found


def test_text_filtering_utilities():
    sample = """
    HALAMAN JUDUL
    Skripsi Keren
    
    KATA PENGANTAR
    Terima kasih kepada semua pembimbing.
    
    BAB I PENDAHULUAN
    Ini adalah isi bab pendahuluan penting.
    
    BAB III METODE PENELITIAN
    Ini adalah bab metodologi formulaik standar.
    
    BAB IV HASIL
    Ini adalah temuan inti penelitian.
    
    DAFTAR PUSTAKA
    Penulis, A. (2020). Buku Pintar.
    """
    res = parse_document_sections(sample)

    # Core substance hanya Bab I dan Bab IV
    core_text = get_core_substance_text(res)
    assert "Ini adalah isi bab pendahuluan penting." in core_text
    assert "Ini adalah temuan inti penelitian." in core_text
    assert "Terima kasih kepada semua pembimbing." not in core_text
    assert "Ini adalah bab metodologi formulaik standar." not in core_text
    assert "Penulis, A. (2020)." not in core_text

    # Substantive including methodology
    subst_text = get_substantive_including_methodology(res)
    assert "Ini adalah isi bab pendahuluan penting." in subst_text
    assert "Ini adalah bab metodologi formulaik standar." in subst_text
    assert "Ini adalah temuan inti penelitian." in subst_text
    assert "Terima kasih kepada semua pembimbing." not in subst_text
    assert "Penulis, A. (2020)." not in subst_text


def test_document_without_headings():
    plain_text = "Sebuah artikel pendek tanpa pembagian bab atau kata pengantar apapun di dalamnya."
    res = parse_document_sections(plain_text)
    assert res.total_sections == 1
    assert res.sections[0].section_zone == SectionZone.BODY_TEXT
    assert res.sections[0].section_type == SectionType.OTHER_BODY
    assert res.core_substance_percentage == 100.0


def test_real_world_emiliana_vivi_structure():
    """
    Uji kesesuaian terhadap struktur faktual Tesis Emiliana Vivi R6:
    Memverifikasi pemisahan tepat antara:
    - Front Matter: Lembar Persetujuan, Keaslian Tulisan, Bebas Plagiasi, Abstrak, Kata Pengantar, Daftar Isi/Tabel/Lampiran
    - Core Body: Bab I, Bab II, Bab IV, Bab V
    - Methodology: Bab III
    - Back Matter: Daftar Pustaka, Lampiran
    """
    vivi_mock_text = """
    ADORASI EKARISTI DALAM SACRAMENTUM CARITATIS
    ANALISIS FENOMENOLOGIS-EMPIRIS PADA PERTUMBUHAN SPIRITUALITAS
    TESIS
    Oleh: EMILIANA VIVI NIM 24201009
    
    LEMBAR PERSETUJUAN
    Disetujui oleh Pembimbing I Dr. Felisitas dan Pembimbing II Dr. Laurentius.
    
    PERNYATAAN KEASLIAN TULISAN
    Saya yang bertanda tangan dibawah ini menyatakan tesis ini hasil karya sendiri.
    
    HALAMAN PERNYATAAN BEBAS PLAGIASI
    Menyatakan dengan sesungguhnya bahwa tesis ini merupakan hasil karya saya sendiri.
    
    ABSTRAK
    Penelitian ini dilatarbelakangi oleh pentingnya memahami makna Adorasi Ekaristi.
    Kata Kunci: Adorasi Ekaristi; Sacramentum Caritatis; pertumbuhan spiritual.
    
    ABSTRACT
    This study was motivated by the importance of understanding the meaning of Eucharistic Adoration.
    Keywords: Eucharistic Adoration; Sacramentum Caritatis.
    
    KATA PENGANTAR
    Puji dan syukur penulis panjatkan ke hadirat Tuhan Yang Maha Esa.
    
    DAFTAR ISI
    BAB I PENDAHULUAN ...... 1
    BAB II LANDASAN TEORI ...... 9
    BAB III METODE PENELITIAN ...... 22
    BAB IV HASIL DAN PEMBAHASAN ...... 34
    BAB V KESIMPULAN DAN SARAN ...... 115
    DAFTAR PUSTAKA ...... 118
    
    DAFTAR TABEL
    Tabel 4.1 Statistik Deskriptif ...... 37
    
    DAFTAR LAMPIRAN
    Lampiran A-1 Kisi-kisi Kuesioner ...... a
    
    BAB I
    PENDAHULUAN
    Gereja Katolik meyakini bahwa Ekaristi adalah puncak dan sumber seluruh kehidupan Kristiani.
    
    BAB II
    LANDASAN TEORI
    Ekaristi menempati pusat kehidupan Gereja Katolik.
    
    BAB III
    METODE PENELITIAN
    Penelitian ini menggunakan jenis penelitian lapangan dengan pendekatan mixed methods.
    
    BAB IV
    HASIL DAN PEMBAHASAN
    Berdasarkan hasil analisis, variabel partisipasi Adorasi Ekaristi memperoleh rata-rata tinggi.
    
    BAB V
    KESIMPULAN DAN SARAN
    Partisipasi OMK dalam Adorasi Ekaristi memiliki hubungan positif signifikan.
    
    DAFTAR PUSTAKA
    Benediktus XVI. (2007). Sacramentum Caritatis. Jakarta: KWI.
    Nouwen, Henri J. M. (2024). Sehati Seperasaan. Yogyakarta: Kanisius.
    
    LAMPIRAN
    LAMPIRAN A-1 Kisi-kisi Kuesioner
    LAMPIRAN B-1 Data Hasil Kuesioner
    """

    res = parse_document_sections(vivi_mock_text)
    assert res.total_sections >= 15

    # Pastikan semua seksi penting terdeteksi
    types = [s.section_type for s in res.sections]
    assert SectionType.PERSETUJUAN in types
    assert SectionType.PERNYATAAN_KEASLIAN in types
    assert SectionType.PERNYATAAN_BEBAS_PLAGIASI in types
    assert SectionType.ABSTRAK in types
    assert SectionType.ABSTRACT in types
    assert SectionType.KATA_PENGANTAR in types
    assert SectionType.DAFTAR_ISI in types
    assert SectionType.DAFTAR_TABEL in types
    assert SectionType.DAFTAR_LAMPIRAN in types
    assert SectionType.BAB_1_PENDAHULUAN in types
    assert SectionType.BAB_2_LANDASAN_TEORI in types
    assert SectionType.BAB_3_METODE_PENELITIAN in types
    assert SectionType.BAB_4_HASIL_PEMBAHASAN in types
    assert SectionType.BAB_5_KESIMPULAN_SARAN in types
    assert SectionType.DAFTAR_PUSTAKA in types
    assert SectionType.LAMPIRAN in types

    # Pastikan Bab III terisolasi di METHODOLOGY, bukan tercampur di BODY_TEXT inti
    bab3_sec = [s for s in res.sections if s.section_type == SectionType.BAB_3_METODE_PENELITIAN][0]
    assert bab3_sec.section_zone == SectionZone.METHODOLOGY

    # Pastikan Core Substance hanya Bab I, II, IV, V
    core_types = [s.section_type for s in res.sections if s.is_core_substance]
    assert SectionType.BAB_1_PENDAHULUAN in core_types
    assert SectionType.BAB_2_LANDASAN_TEORI in core_types
    assert SectionType.BAB_4_HASIL_PEMBAHASAN in core_types
    assert SectionType.BAB_5_KESIMPULAN_SARAN in core_types
    assert SectionType.BAB_3_METODE_PENELITIAN not in core_types


def test_bab_typo_tolerance_and_helena_thesis_pattern():
    """
    Test regresi khusus typo 'BAB 1V' (angka satu + huruf V) seperti pada naskah tesis Helena Nina.
    Memastikan Bab IV terdeteksi dengan benar dan Bab III (Methodology) tidak membengkak menyerap Bab IV.
    """
    mock_thesis = """
    KATA PENGANTAR
    Puji syukur penulis panjatkan kepada Tuhan yang Maha Kuasa.
    
    DAFTAR ISI
    BAB 1 PENDAHULUAN ...... 1
    BAB II LANDASAN TEORI ...... 13
    BAB III METODE PENELITIAN ...... 35
    BAB IV HASIL DAN PEMBAHASAN ...... 48
    BAB V KESIMPULAN DAN SARAN ...... 93
    
    ABSTRAK
    Penelitian ini mengkaji pendampingan liturgi inklusif bagi anak berkebutuhan khusus.
    
    BAB 1
    PENDAHULUAN
    Gereja Katolik mengajarkan bahwa setiap manusia diciptakan menurut citra Allah imago Dei.
    Setiap pribadi memiliki martabat luhur yang tidak dapat dikurangi oleh kondisi apapun.
    """ + " ".join(["kata_pendahuluan"] * 120) + """
    
    BAB II
    LANDASAN TEORI
    Pendampingan pastoral berakar pada pemahaman tugas penggembalaan liturgis.
    """ + " ".join(["kata_teori"] * 150) + """
    
    BAB III
    METODE PENELITIAN
    Penelitian ini menggunakan pendekatan kualitatif dengan studi kasus di Kapel Tirta Ria.
    Data diperoleh melalui observasi, wawancara, dan dokumentasi mendalam.
    """ + " ".join(["kata_metode"] * 100) + """
    
    BAB 1V
    HASIL PENELITIAN
    Berdasarkan observasi dan wawancara, pelaksanaan pendampingan lebih mengedepankan pendekatan personal.
    Pendamping memahami kondisi emosional anak dan mengatur durasi fokus secara adaptif.
    """ + " ".join(["kata_hasil_pembahasan"] * 400) + """
    
    BAB V
    KESIMPULAN DAN SARAN
    Perencanaan dan pelaksanaan pendampingan telah mencerminkan semangat inklusif.
    """ + " ".join(["kata_kesimpulan"] * 80) + """
    
    DAFTAR PUSTAKA
    Engel, J. D. (2020). Pendampingan Pastoral Keindonesiaan. Kurios, 6(1), 47.
    """

    res = parse_document_sections(mock_thesis)

    # Verifikasi bahwa Bab IV (BAB 1V) terdeteksi sebagai BAB_4_HASIL_PEMBAHASAN
    types = [s.section_type for s in res.sections]
    assert SectionType.BAB_4_HASIL_PEMBAHASAN in types
    assert SectionType.BAB_3_METODE_PENELITIAN in types

    # Methodology harus proporsional (tidak menyerap Bab IV)
    meth_pct = res.zone_percentage_distribution.get(SectionZone.METHODOLOGY.value, 0.0)
    assert meth_pct < 25.0, f"Methodology percentage ({meth_pct}%) too high, BAB 1V likely not separated"

    # Verifikasi Bab IV berzona BODY_TEXT
    bab4_sec = [s for s in res.sections if s.section_type == SectionType.BAB_4_HASIL_PEMBAHASAN][0]
    assert bab4_sec.section_zone == SectionZone.BODY_TEXT
    assert bab4_sec.word_count >= 400

    # Verifikasi Daftar Isi tidak menghasilkan seksi palsu Bab 1..5 sebelum Abstrak
    bab_sections_before_abstrak = []
    abstrak_found = False
    for s in res.sections:
        if s.section_type == SectionType.ABSTRAK:
            abstrak_found = True
            break
        if "BAB" in s.section_type.value:
            bab_sections_before_abstrak.append(s)
    assert len(bab_sections_before_abstrak) == 0, f"Found false BAB sections in TOC: {bab_sections_before_abstrak}"


def test_roman_normalization_variations():
    """
    Test toleransi variasi penulisan nomor bab: BAB lV (L kecil), BAB |V (pipa), BAB 4 (angka Arab), BAB II (standar).
    """
    from worker.section_parser import _bab_number
    assert _bab_number("BAB I") == 1
    assert _bab_number("BAB 1") == 1
    assert _bab_number("BAB II") == 2
    assert _bab_number("BAB 2") == 2
    assert _bab_number("BAB III") == 3
    assert _bab_number("BAB 3") == 3
    assert _bab_number("BAB IV") == 4
    assert _bab_number("BAB 4") == 4
    assert _bab_number("BAB 1V") == 4
    assert _bab_number("BAB lV") == 4
    assert _bab_number("BAB |V") == 4
    assert _bab_number("BAB V") == 5
    assert _bab_number("BAB 5") == 5


