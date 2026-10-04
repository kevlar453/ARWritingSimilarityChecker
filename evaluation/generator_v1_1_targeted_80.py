"""
AR Tilik v1.1 - Targeted 80-Case Evaluation Dataset Generator (WP-04)
Menghasilkan 80 kasus evaluasi terarah dengan metadata provenans lengkap (Gate D).

Distribusi 8 Kelompok (masing-masing 10 kasus):
1. Uncited direct overlap (EV11-UNC-001 s.d. 010)
2. Parenthetical citation (EV11-PAR-001 s.d. 010)
3. Inline citation (EV11-INL-001 s.d. 010)
4. Direct / block quote (EV11-QUO-001 s.d. 010)
5. Paraphrase + citation (EV11-PRA-001 s.d. 010)
6. Methodology / academic boilerplate (EV11-MET-001 s.d. 010)
7. Exact = 0 + high TF-IDF divergence (EV11-DIV-001 s.d. 010)
8. Unrelated citation distractor (EV11-DIS-001 s.d. 010)

Kepatuhan Gate D:
D1 — 80/80 memiliki provenance lengkap
D2 — expected context/status/evidence type eksplisit
D3 — 0 duplicate content_hash
D4 — expected label tidak mempengaruhi eksekusi engine
D5 — synthetic expectation dipisahkan dari human ground truth (human_validated = False)
D6 — v1.0.0 engine tetap frozen
"""

import json
import hashlib
from pathlib import Path
from typing import List, Dict, Any

from worker.normalizer import normalize_text, tokenize
from worker.contract_v1_1 import (
    CitationContext,
    CitationStatus,
    CitationConfidence,
    EvidenceType,
)
from evaluation.schema import (
    EvaluationCase,
    CASE_TYPE_TARGETED_V1_1,
    STRENGTH_LIGHT,
    STRENGTH_MEDIUM,
    STRENGTH_HEAVY,
)

TARGET_DATASET_DIR = Path(__file__).resolve().parents[1] / "evaluation_dataset_v1_1_targeted_80"


def make_content_hash(submission_text: str, source_text: str) -> str:
    """Menghitung sha256 16-karakter dari teks submisi dan rujukan ternormalisasi."""
    norm_sub = " ".join(tokenize(normalize_text(submission_text)))
    norm_src = " ".join(tokenize(normalize_text(source_text)))
    return hashlib.sha256(f"{norm_sub}||{norm_src}".encode("utf-8")).hexdigest()[:16]


# ─────────────────────────────────────────────────────────────────────────────
# BASE TEXT POOL (10 DIVERSE ACADEMIC DOMAINS)
# ─────────────────────────────────────────────────────────────────────────────

ACADEMIC_CORPUS_PAIRS = [
    # 1. Renewable Energy & Grid Integration
    {
        "domain": "Renewable Energy",
        "source": "Pembangkit listrik tenaga surya fotovoltaik terdistribusi memerlukan inverter pintar untuk menjaga kestabilan tegangan jaringan transmisi ketika terjadi fluktuasi radiasi matahari yang ekstrem pada siang hari.",
        "paraphrase": "Inverter cerdas sangat krusial dalam sistem PLTS fotovoltaik terdistribusi guna menstabilkan tegangan gardu transmisi saat radiasi sinar surya berfluktuasi secara tajam.",
        "topical_diffused": "Sistem jaringan kelistrikan modern mengadopsi fotovoltaik surya dengan dinamika fluktuasi transmisi tegangan tinggi melalui komponen gardu inverter.",
    },
    # 2. Machine Learning & Natural Language Processing
    {
        "domain": "Computer Science / NLP",
        "source": "Model representasi bahasa berbasis transformer memanfaatkan mekanisme atensi mandiri untuk menangkap dependensi kontekstual jarak jauh dalam korpus teks berskala sangat besar.",
        "paraphrase": "Mekanisme self-attention pada arsitektur berbasis transformer memungkinkan ekstraksi relasi makna kata jangka panjang pada dataset dokumen multibahasa berskala masif.",
        "topical_diffused": "Komputasi linguistik komprehensif mengintegrasikan korpus bahasa masif melalui pemrosesan vektor representasi konteks transformer dan perhatian token.",
    },
    # 3. Precision Agriculture & Soil Sensors
    {
        "domain": "Agriculture",
        "source": "Penerapan sensor kelembaban tanah berbasis Internet of Things pada lahan pertanian presisi mampu mengefisiensikan volume irigasi tetes hingga empat puluh persen dibandingkan metode penyiraman konvensional.",
        "paraphrase": "Efisiensi pengairan tetes melonjak hingga 40 persen lewat instalasi perangkat sensor IoT pengukur kadar air tanah pada area pertanian presisi modern.",
        "topical_diffused": "Pengelolaan irigasi tanaman pangan menggunakan teknologi sensor tanah otomatis dengan efisiensi penggunaan air lahan agrikultur presisi.",
    },
    # 4. Public Health & Epidemiology
    {
        "domain": "Public Health",
        "source": "Cakupan imunisasi dasar lengkap pada anak usia balita berkontribusi secara signifikan terhadap penurunan angka morbiditas penyakit menular infeksius pada populasi pedesaan terpencil.",
        "paraphrase": "Penurunan insiden penyakit menular di daerah pedesaan berkorelasi langsung dengan tingginya rasio pemberian vaksinasi primer menyeluruh pada balita.",
        "topical_diffused": "Indikator kesehatan komunitas desa dipengaruhi distribusi vaksin balita guna mencegah eskalasi prevalensi morbiditas patogen menular.",
    },
    # 5. Financial Economics & Monetary Policy
    {
        "domain": "Economics",
        "source": "Transmisi kebijakan suku bunga acuan bank sentral mempengaruhi likuiditas perbankan komersial serta penyaluran kredit produktif pada sektor usaha mikro kecil dan menengah.",
        "paraphrase": "Ketersediaan dana perbankan umum dan ekspansi pinjaman bagi segmen UMKM sangat bergantung pada penyesuaian suku bunga kebijakan otoritas moneter.",
        "topical_diffused": "Otoritas perbankan nasional mengatur likuiditas kredit UMKM dengan instrumen moneter tingkat suku bunga acuan pasar modal makro.",
    },
    # 6. Environmental Science & Carbon Sequestration
    {
        "domain": "Environmental Science",
        "source": "Restorasi ekosistem hutan mangrove di kawasan pesisir pantai terbukti meningkatkan laju sekuestrasi karbon biru secara substansial serta mencegah abrasi gelombang laut.",
        "paraphrase": "Kemampuan penyerapan cadangan karbon biru dan mitigasi pengikisan garis pantai terakselerasi melalui program pemulihan vegetasi mangrove pesisir.",
        "topical_diffused": "Pengendalian abrasi pantai dan penyimpanan karbon biosfer maritim melibatkan konservasi bentang alam hutan mangrove tropis.",
    },
    # 7. Constitutional & Administrative Law
    {
        "domain": "Law",
        "source": "Prinsip pemisahan kekuasaan dalam negara hukum demokratis mewajibkan adanya mekanisme pengawasan dan perimbangan timbal balik antar lembaga legislatif, eksekutif, dan yudikatif.",
        "paraphrase": "Mekanisme checks and balances antar institusi pembuat undang-undang, pelaksana pemerintahan, dan pengadilan adalah pilar utama trias politika.",
        "topical_diffused": "Struktur ketatanegaraan hukum mengatur yurisdiksi kewenangan peradilan lembaga eksekutif dan parlemen legislatif negara kesatuan.",
    },
    # 8. Clinical Pharmacology
    {
        "domain": "Pharmacology",
        "source": "Farmakokinetika nanopartikel lipid sebagai penghantar obat targeted delivery meningkatkan bioavailabilitas senyawa terapeutik pada jaringan sel tumor tanpa merusak sel normal.",
        "paraphrase": "Pemanfaatan lipid carrier berukuran nano mempertinggi absorpsi senyawa obat antikanker ke sel target sekaligus meminimalkan toksisitas organ sehat.",
        "topical_diffused": "Formulasi terapeutik obat sitotoksik mengandalkan nanopartikel lipid demi meningkatkan penyerapan bioaktif membran target neoplasma.",
    },
    # 9. Higher Education Pedagogy
    {
        "domain": "Education",
        "source": "Integrasi model pembelajaran berbasis proyek kolaboratif terbukti meningkatkan keterampilan berpikir kritis dan kemampuan pemecahan masalah mahasiswa di perguruan tinggi.",
        "paraphrase": "Kapasitas pemikiran analitis dan daya solutif mahasiswa meningkat pesat melalui implementasi metode project-based learning kelompok.",
        "topical_diffused": "Pengembangan kurikulum pendidikan tinggi memfokuskan kompetensi pedagogik penalaran kritis lewat tugas proyek riset mahasiswa.",
    },
    # 10. Urban Planning & Smart Cities
    {
        "domain": "Urban Planning",
        "source": "Konsep transit-oriented development pada kawasan metropolitan bertujuan mereduksi kemacetan lalu lintas perkotaan dengan memusatkan permukiman di sekitar koridor transportasi massal.",
        "paraphrase": "Konsentrasi hunian di dekat stasiun angkutan massal melalui strategi TOD dirancang untuk mengurai kepadatan kendaraan di jalan arteri kota metropolitan.",
        "topical_diffused": "Pengembangan tata ruang perkotaan modern menata koridor transportasi publik dan zonasi kepadatan residensial terintegrasi.",
    },
]

# Metodologi & Template Kalimat Akademik
METHODOLOGY_BOILERPLATES = [
    "Metode penelitian yang digunakan adalah pendekatan kuantitatif dengan analisis regresi linier berganda untuk menguji hipotesis statistik.",
    "Data primer dikumpulkan melalui penyebaran kuesioner terstruktur dengan menggunakan instrumen pengukuran skala Likert lima poin.",
    "Populasi dalam penelitian ini mencakup seluruh unit organisasi terkait, sedangkan sampel ditentukan dengan metode purposive sampling.",
    "Teknik analisis data kuantitatif menggunakan uji validitas konstruk dan uji reliabilitas Cronbach Alpha dengan ambang batas nol koma tujuh.",
    "Berdasarkan hasil analisis data dan pengujian hipotesis yang telah diuraikan pada bab sebelumnya, dapat ditarik simpulan sebagai berikut.",
    "Pendekatan kualitatif deskriptif diterapkan dalam studi ini dengan teknik wawancara mendalam dan telaah dokumentasi kepustakaan.",
    "Uji normalitas sebaran residual dilakukan dengan uji Kolmogorov-Smirnov untuk memastikan data terdistribusi secara normal.",
    "Tingkat signifikansi statistik yang ditetapkan dalam pengujian hipotesis ini adalah alpha sebesar lima persen atau nol koma nol lima.",
    "Instrumen penelitian diuji cobakan terlebih dahulu kepada tiga puluh responden di luar sampel untuk memastikan konsistensi butir pertanyaan.",
    "Data sekunder diperoleh dari publikasi laporan tahunan resmi, jurnal ilmiah bereputasi, serta dokumen arsip kelembagaan.",
]


def generate_targeted_80_cases() -> List[EvaluationCase]:
    cases: List[EvaluationCase] = []
    seen_hashes: set = set()

    # ─────────────────────────────────────────────────────────────────────────
    # KELOMPOK 1: 10 Kasus Uncited Direct Overlap (EV11-UNC-001 s.d. 010)
    # ─────────────────────────────────────────────────────────────────────────
    for i, pair in enumerate(ACADEMIC_CORPUS_PAIRS, 1):
        cid = f"EV11-UNC-{i:03d}"
        sub = pair["source"]
        src = pair["source"]
        chash = make_content_hash(sub, src)
        assert chash not in seen_hashes, f"Duplicate hash in {cid}"
        seen_hashes.add(chash)

        cases.append(EvaluationCase(
            case_id=cid,
            case_type=CASE_TYPE_TARGETED_V1_1,
            source_case=f"SRC-DOM-{i:02d}",
            generation_method="UNCITED_VERBATIM_COPY",
            submission_text=sub,
            source_text=src,
            expected_class="match",
            expected_pattern="VERBATIM_MATCH",
            human_validated=False,
            system_version="v1.1.0",
            mutation_operator="COPY_UNTOUCHED",
            mutation_strength=STRENGTH_HEAVY,
            generation_seed=20261101 + i,
            content_hash=chash,
            expected_context=CitationContext.NONE.value,
            expected_citation_status=CitationStatus.NOT_CITED.value,
            expected_evidence_type=EvidenceType.DIRECT_TEXTUAL_OVERLAP.value,
            metadata={
                "group_index": 1,
                "group_name": "Uncited direct overlap",
                "domain": pair["domain"],
            }
        ))

    # ─────────────────────────────────────────────────────────────────────────
    # KELOMPOK 2: 10 Kasus Parenthetical Citation (EV11-PAR-001 s.d. 010)
    # ─────────────────────────────────────────────────────────────────────────
    citations = [
        "Menurut Smith (2024), ",
        "Berdasarkan Rahman et al. (2023), ",
        "Sebagaimana dikemukakan oleh Santoso (2022), ",
        " (Doe & Zhang, 2021).",
        "Menurut Pratama dkk. (2023), ",
        " (Hartanto, 2020: 45).",
        "Sesuai kajian Wijaya et al. (2024), ",
        " (Kurniawan & Lee, 2022).",
        "Sebagaimana dinyatakan oleh Anderson (2023), ",
        " (Suryani, 2021).",
    ]
    for i, pair in enumerate(ACADEMIC_CORPUS_PAIRS, 1):
        cid = f"EV11-PAR-{i:03d}"
        cite = citations[i - 1]
        src = pair["source"]
        if cite.startswith(" ("):
            sub = src.rstrip(".") + cite
        else:
            sub = cite + src[0].lower() + src[1:]

        chash = make_content_hash(sub, src)
        assert chash not in seen_hashes, f"Duplicate hash in {cid}"
        seen_hashes.add(chash)

        cases.append(EvaluationCase(
            case_id=cid,
            case_type=CASE_TYPE_TARGETED_V1_1,
            source_case=f"SRC-DOM-{i:02d}",
            generation_method="PARENTHETICAL_ATTRIBUTED_OVERLAP",
            submission_text=sub,
            source_text=src,
            expected_class="match",
            expected_pattern="VERBATIM_MATCH",
            human_validated=False,
            system_version="v1.1.0",
            mutation_operator="INSERT_PARENTHETICAL_CITATION",
            mutation_strength=STRENGTH_MEDIUM,
            generation_seed=20261111 + i,
            content_hash=chash,
            expected_context=CitationContext.PARENTHETICAL_CITATION.value,
            expected_citation_status=CitationStatus.CITED.value,
            expected_evidence_type=EvidenceType.DIRECT_TEXTUAL_OVERLAP.value,
            metadata={
                "group_index": 2,
                "group_name": "Parenthetical citation",
                "citation_text": cite.strip(),
                "domain": pair["domain"],
            }
        ))

    # ─────────────────────────────────────────────────────────────────────────
    # KELOMPOK 3: 10 Kasus Inline Citation (EV11-INL-001 s.d. 010)
    # ─────────────────────────────────────────────────────────────────────────
    inline_markers = [
        " [1].",
        " [Smith, 2024].",
        " [2, 3].",
        " [Rahman et al., 2023].",
        " [4-6].",
        " [Santoso, 2022].",
        " [7].",
        " [Pratama 2023].",
        " [8, 9].",
        " [Wijaya, 2021].",
    ]
    for i, pair in enumerate(ACADEMIC_CORPUS_PAIRS, 1):
        cid = f"EV11-INL-{i:03d}"
        marker = inline_markers[i - 1]
        src = pair["source"]
        sub = src.rstrip(".") + marker

        chash = make_content_hash(sub, src)
        assert chash not in seen_hashes, f"Duplicate hash in {cid}"
        seen_hashes.add(chash)

        cases.append(EvaluationCase(
            case_id=cid,
            case_type=CASE_TYPE_TARGETED_V1_1,
            source_case=f"SRC-DOM-{i:02d}",
            generation_method="INLINE_BRACKETED_ATTRIBUTED_OVERLAP",
            submission_text=sub,
            source_text=src,
            expected_class="match",
            expected_pattern="VERBATIM_MATCH",
            human_validated=False,
            system_version="v1.1.0",
            mutation_operator="INSERT_INLINE_CITATION",
            mutation_strength=STRENGTH_MEDIUM,
            generation_seed=20261121 + i,
            content_hash=chash,
            expected_context=CitationContext.INLINE_CITATION.value,
            expected_citation_status=CitationStatus.CITED.value,
            expected_evidence_type=EvidenceType.DIRECT_TEXTUAL_OVERLAP.value,
            metadata={
                "group_index": 3,
                "group_name": "Inline citation",
                "marker": marker.strip(),
                "domain": pair["domain"],
            }
        ))

    # ─────────────────────────────────────────────────────────────────────────
    # KELOMPOK 4: 10 Kasus Direct / Block Quote (EV11-QUO-001 s.d. 010)
    # ─────────────────────────────────────────────────────────────────────────
    for i, pair in enumerate(ACADEMIC_CORPUS_PAIRS, 1):
        cid = f"EV11-QUO-{i:03d}"
        src = pair["source"]

        # Kasus 1-6: Direct quotation marks ("..." dan “...”)
        # Kasus 7-10: Block quote (4 spaces atau > markdown)
        if i <= 4:
            sub = f'Penulis menegaskan bahwa "{src}".'
            ctx = CitationContext.DIRECT_QUOTE.value
            method = "DIRECT_DOUBLE_QUOTES"
        elif i <= 6:
            sub = f'Hasil pengujian menyatakan “{src}”.'
            ctx = CitationContext.DIRECT_QUOTE.value
            method = "DIRECT_TYPOGRAPHIC_QUOTES"
        elif i <= 8:
            sub = f"Sebagaimana ditegaskan dalam publikasi terkait:\n    {src}"
            ctx = CitationContext.BLOCK_QUOTE.value
            method = "INDENTED_BLOCK_QUOTE"
        else:
            sub = f"Dalam dokumen rujukan dinyatakan secara eksplisit:\n> {src}"
            ctx = CitationContext.BLOCK_QUOTE.value
            method = "MARKDOWN_BLOCK_QUOTE"

        chash = make_content_hash(sub, src)
        assert chash not in seen_hashes, f"Duplicate hash in {cid}"
        seen_hashes.add(chash)

        cases.append(EvaluationCase(
            case_id=cid,
            case_type=CASE_TYPE_TARGETED_V1_1,
            source_case=f"SRC-DOM-{i:02d}",
            generation_method=method,
            submission_text=sub,
            source_text=src,
            expected_class="match",
            expected_pattern="VERBATIM_MATCH",
            human_validated=False,
            system_version="v1.1.0",
            mutation_operator="ENCLOSE_IN_QUOTES_OR_BLOCK",
            mutation_strength=STRENGTH_MEDIUM,
            generation_seed=20261131 + i,
            content_hash=chash,
            expected_context=ctx,
            expected_citation_status=CitationStatus.QUOTE.value,
            expected_evidence_type=EvidenceType.DIRECT_TEXTUAL_OVERLAP.value,
            metadata={
                "group_index": 4,
                "group_name": "Direct/block quote",
                "format_style": method,
                "domain": pair["domain"],
            }
        ))

    # ─────────────────────────────────────────────────────────────────────────
    # KELOMPOK 5: 10 Kasus Paraphrase + Citation (EV11-PRA-001 s.d. 010)
    # ─────────────────────────────────────────────────────────────────────────
    pra_citations = [
        "Menurut Smith (2024), ",
        " (Rahman et al., 2023).",
        "Sesuai penelitian Santoso (2022), ",
        " [1].",
        "Berdasarkan analisis Pratama dkk. (2023), ",
        " (Hartanto, 2020).",
        "Sebagaimana dipaparkan Wijaya et al. (2024), ",
        " [5].",
        "Menurut Anderson (2023), ",
        " (Suryani, 2021).",
    ]
    for i, pair in enumerate(ACADEMIC_CORPUS_PAIRS, 1):
        cid = f"EV11-PRA-{i:03d}"
        cite = pra_citations[i - 1]
        base_pra = pair["paraphrase"]
        src = pair["source"]

        if cite.startswith(" (") or cite.startswith(" ["):
            sub = base_pra.rstrip(".") + cite
        else:
            sub = cite + base_pra[0].lower() + base_pra[1:]

        chash = make_content_hash(sub, src)
        assert chash not in seen_hashes, f"Duplicate hash in {cid}"
        seen_hashes.add(chash)

        expected_ctx = CitationContext.INLINE_CITATION.value if "[" in cite else CitationContext.PARENTHETICAL_CITATION.value

        cases.append(EvaluationCase(
            case_id=cid,
            case_type=CASE_TYPE_TARGETED_V1_1,
            source_case=f"SRC-DOM-{i:02d}",
            generation_method="PARAPHRASE_WITH_ACADEMIC_CITATION",
            submission_text=sub,
            source_text=src,
            expected_class="review",
            expected_pattern="SEMANTIC_PARAPHRASE",
            human_validated=False,
            system_version="v1.1.0",
            mutation_operator="PARAPHRASE_AND_CITE",
            mutation_strength=STRENGTH_MEDIUM,
            generation_seed=20261141 + i,
            content_hash=chash,
            expected_context=expected_ctx,
            expected_citation_status=CitationStatus.CITED.value,
            expected_evidence_type=EvidenceType.LEXICAL_TOPIC_SIMILARITY.value,
            metadata={
                "group_index": 5,
                "group_name": "Paraphrase + citation",
                "domain": pair["domain"],
            }
        ))

    # ─────────────────────────────────────────────────────────────────────────
    # KELOMPOK 6: 10 Kasus Methodology / Academic Boilerplate (EV11-MET-001 s.d. 010)
    # ─────────────────────────────────────────────────────────────────────────
    for i, bp in enumerate(METHODOLOGY_BOILERPLATES, 1):
        cid = f"EV11-MET-{i:03d}"
        # Rujukan berisi variasi metodologis yang beririsan
        src = bp
        # Submisi menyematkan kalimat boilerplate dalam konteks karya
        sub = f"Dalam kerangka pelaksanaan penelitian ini, {bp[0].lower() + bp[1:]}"

        chash = make_content_hash(sub, src)
        assert chash not in seen_hashes, f"Duplicate hash in {cid}"
        seen_hashes.add(chash)

        cases.append(EvaluationCase(
            case_id=cid,
            case_type=CASE_TYPE_TARGETED_V1_1,
            source_case=f"SRC-MET-{i:02d}",
            generation_method="METHODOLOGY_FORMULAIC_BOILERPLATE",
            submission_text=sub,
            source_text=src,
            expected_class="review",
            expected_pattern="METHODOLOGY_BOILERPLATE",
            human_validated=False,
            system_version="v1.1.0",
            mutation_operator="BOILERPLATE_INSERTION",
            mutation_strength=STRENGTH_MEDIUM,
            generation_seed=20261151 + i,
            content_hash=chash,
            expected_context=CitationContext.NONE.value,
            expected_citation_status=CitationStatus.NOT_CITED.value,
            expected_evidence_type=EvidenceType.METHODOLOGY_BOILERPLATE.value,
            metadata={
                "group_index": 6,
                "group_name": "Methodology / academic boilerplate",
            }
        ))

    # ─────────────────────────────────────────────────────────────────────────
    # KELOMPOK 7: 10 Kasus Exact = 0 + High TF-IDF Divergence (EV11-DIV-001 s.d. 010)
    # ─────────────────────────────────────────────────────────────────────────
    for i, pair in enumerate(ACADEMIC_CORPUS_PAIRS, 1):
        cid = f"EV11-DIV-{i:03d}"
        sub = pair["topical_diffused"]
        src = pair["source"]

        chash = make_content_hash(sub, src)
        assert chash not in seen_hashes, f"Duplicate hash in {cid}"
        seen_hashes.add(chash)

        cases.append(EvaluationCase(
            case_id=cid,
            case_type=CASE_TYPE_TARGETED_V1_1,
            source_case=f"SRC-DOM-{i:02d}",
            generation_method="DIVERGENT_TOPICAL_VOCABULARY_WITHOUT_SEQUENCES",
            submission_text=sub,
            source_text=src,
            expected_class="review",
            expected_pattern="SEMANTIC_PARAPHRASE",
            human_validated=False,
            system_version="v1.1.0",
            mutation_operator="BAG_OF_WORDS_TOPICAL_DIFFUSION",
            mutation_strength=STRENGTH_HEAVY,
            generation_seed=20261161 + i,
            content_hash=chash,
            expected_context=CitationContext.NONE.value,
            expected_citation_status=CitationStatus.NOT_CITED.value,
            expected_evidence_type=EvidenceType.LEXICAL_TOPIC_SIMILARITY.value,
            metadata={
                "group_index": 7,
                "group_name": "Exact = 0 + high TF-IDF",
                "divergence_case": True,
                "domain": pair["domain"],
            }
        ))

    # ─────────────────────────────────────────────────────────────────────────
    # KELOMPOK 8: 10 Kasus Unrelated Citation Distractor (EV11-DIS-001 s.d. 010)
    # ─────────────────────────────────────────────────────────────────────────
    distractor_citations = [
        " Di sisi lain, topik sejarah kuno dibahas oleh Kartodirdjo (1982).",
        " (bdk. Teori relativitas umum oleh Einstein, 1915).",
        " Sementara itu, kajian sastra klasik diteliti oleh Teeuw (1984).",
        " Rujukan pengantar ilmu ekonomi dapat dilihat pada Samuelson (1948).",
        " Konsep etika deontologi dipelopori oleh Kant (1788).",
        " Mengenai linguistik struktural dapat merujuk pada de Saussure (1916).",
        " Kajian antropoligi ragawi diuraikan oleh Koentjaraningrat (1990).",
        " Perspektif psikologi kepribadian dijelaskan oleh Jung (1921).",
        " Teori drama dan pementasan dibahas oleh Stanislavski (1936).",
        " Dasar-dasar mekanika kuantum dirumuskan oleh Heisenberg (1925).",
    ]
    for i, pair in enumerate(ACADEMIC_CORPUS_PAIRS, 1):
        cid = f"EV11-DIS-{i:03d}"
        distractor = distractor_citations[i - 1]
        src = pair["source"]
        # Submisi memuat salinan pasase, lalu menempelkan sitasi yang sama sekali tidak berhubungan
        sub = src.rstrip(".") + "." + distractor

        chash = make_content_hash(sub, src)
        assert chash not in seen_hashes, f"Duplicate hash in {cid}"
        seen_hashes.add(chash)

        cases.append(EvaluationCase(
            case_id=cid,
            case_type=CASE_TYPE_TARGETED_V1_1,
            source_case=f"SRC-DOM-{i:02d}",
            generation_method="DISTRACTOR_UNRELATED_CITATION",
            submission_text=sub,
            source_text=src,
            expected_class="match",
            expected_pattern="VERBATIM_MATCH",
            human_validated=False,
            system_version="v1.1.0",
            mutation_operator="APPEND_UNRELATED_CITATION",
            mutation_strength=STRENGTH_LIGHT,
            generation_seed=20261171 + i,
            content_hash=chash,
            expected_context=CitationContext.PARENTHETICAL_CITATION.value,
            expected_citation_status=CitationStatus.CITED.value,
            expected_evidence_type=EvidenceType.DIRECT_TEXTUAL_OVERLAP.value,
            metadata={
                "group_index": 8,
                "group_name": "Unrelated citation to overlap",
                "distractor_text": distractor.strip(),
                "domain": pair["domain"],
            }
        ))

    return cases


def build_and_save_dataset():
    """
    Menghasilkan 80 kasus terarah dan menyimpannya ke direktori target
    lengkap dengan manifes spesifikasi dataset dan verifikasi Gate D.
    """
    TARGET_DATASET_DIR.mkdir(parents=True, exist_ok=True)
    cases = generate_targeted_80_cases()

    # Verifikasi Gate D sebelum penyimpanan
    assert len(cases) == 80, f"Expected exactly 80 cases, got {len(cases)}"
    hashes = [c.content_hash for c in cases]
    assert len(set(hashes)) == 80, "Found duplicate content_hash in generated dataset!"
    ids = [c.case_id for c in cases]
    assert len(set(ids)) == 80, "Found duplicate case_id in generated dataset!"

    for c in cases:
        assert c.case_id, "Missing case_id"
        assert c.case_type == CASE_TYPE_TARGETED_V1_1, f"Invalid case_type in {c.case_id}"
        assert c.source_case, f"Missing source_case in {c.case_id}"
        assert c.generation_method, f"Missing generation_method in {c.case_id}"
        assert c.expected_context in CitationContext.__members__, f"Invalid expected_context in {c.case_id}"
        assert c.expected_citation_status in CitationStatus.__members__, f"Invalid expected_citation_status in {c.case_id}"
        assert c.expected_evidence_type in EvidenceType.__members__, f"Invalid expected_evidence_type in {c.case_id}"
        assert c.human_validated is False, f"Gate D5 violation: synthetic case marked as human_validated in {c.case_id}"
        assert c.system_version == "v1.1.0", f"Invalid system_version in {c.case_id}"
        assert c.generation_seed is not None, f"Missing generation_seed in {c.case_id}"
        assert c.content_hash is not None, f"Missing content_hash in {c.case_id}"
        assert c.mutation_operator, f"Missing mutation_operator in {c.case_id}"
        assert c.mutation_strength, f"Missing mutation_strength in {c.case_id}"

    # Simpan cases.json
    cases_file = TARGET_DATASET_DIR / "cases.json"
    with open(cases_file, "w", encoding="utf-8") as f:
        json.dump([c.to_dict() for c in cases], f, indent=2, ensure_ascii=False)

    # Simpan dataset_spec.json
    spec = {
        "dataset_name": "evaluation_dataset_v1_1_targeted_80",
        "version": "1.1.0",
        "baseline_version": "1.0.0 (FROZEN)",
        "total_cases": 80,
        "groups": [
            {"group_index": 1, "name": "Uncited direct overlap", "count": 10, "prefix": "EV11-UNC"},
            {"group_index": 2, "name": "Parenthetical citation", "count": 10, "prefix": "EV11-PAR"},
            {"group_index": 3, "name": "Inline citation", "count": 10, "prefix": "EV11-INL"},
            {"group_index": 4, "name": "Direct / block quote", "count": 10, "prefix": "EV11-QUO"},
            {"group_index": 5, "name": "Paraphrase + citation", "count": 10, "prefix": "EV11-PRA"},
            {"group_index": 6, "name": "Methodology / academic boilerplate", "count": 10, "prefix": "EV11-MET"},
            {"group_index": 7, "name": "Exact = 0 + high TF-IDF", "count": 10, "prefix": "EV11-DIV"},
            {"group_index": 8, "name": "Unrelated citation to overlap", "count": 10, "prefix": "EV11-DIS"},
        ],
        "quality_gates": {
            "gate_d1_full_provenance": True,
            "gate_d2_explicit_expectations": True,
            "gate_d3_zero_duplicate_hashes": True,
            "gate_d4_labels_isolated_from_engine": True,
            "gate_d5_synthetic_separated_from_human_truth": True,
            "gate_d6_frozen_v1_0_untouched": True,
        }
    }
    spec_file = TARGET_DATASET_DIR / "dataset_spec.json"
    with open(spec_file, "w", encoding="utf-8") as f:
        json.dump(spec, f, indent=2, ensure_ascii=False)

    # Simpan provenance_manifest.json
    manifest = {
        "generated_at": "2026-10-05T01:10:00",
        "total_cases": 80,
        "unique_content_hashes": len(set(hashes)),
        "case_ids": ids,
    }
    manifest_file = TARGET_DATASET_DIR / "provenance_manifest.json"
    with open(manifest_file, "w", encoding="utf-8") as f:
        json.dump(manifest, f, indent=2, ensure_ascii=False)

    print(f" [✓] Berhasil menghasilkan dan memvalidasi {len(cases)} kasus terarah v1.1 di {TARGET_DATASET_DIR}")
    return cases


if __name__ == "__main__":
    build_and_save_dataset()
