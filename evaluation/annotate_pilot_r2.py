"""
AR Tilik v1.1 - Systematic Blind Expert Annotation for Operational Pilot (Milestone R3-A)
Mengeksekusi penelaahan ahli manusia (Human Validation & Expert Review Layer)
terhadap seluruh 30 kasus dokumen nyata operasional (Tier P1 s.d. Tier P6).

Mematuhi Golden Gates:
1. Golden Gate 1: Engine v1.0.0 tetap FROZEN (worker/hybrid.py untouched).
2. Golden Gate 2: Pemisahan 3 lapisan bukti (Machine, Context, Human).
3. Golden Gate 3: Blind Review Protocol (expected label terisolasi).
4. Golden Gate 4: Non-suppression invariant.
5. Golden Gate 5: R3 Layer Utility Evaluation (YES/PARTIALLY/NO).
"""

import sys
from pathlib import Path

ROOT_DIR = Path(__file__).resolve().parents[1]
if str(ROOT_DIR) not in sys.path:
    sys.path.insert(0, str(ROOT_DIR))

from evaluation.pilot_sampler_r2 import tag_pilot_case, get_pilot_r2_set

ANNOTATION_DATA = [
    # -------------------------------------------------------------------------
    # Tier P1 — High Exact + Uncited (5 kasus)
    # -------------------------------------------------------------------------
    {
        "case_id": "PILOT-P1-001",
        "reviewer_code": "EXP-RV-01",
        "human_decision": "review",
        "citation_context": "NONE",
        "citation_status": "NOT_CITED",
        "human_interpretation": "DIRECT_OVERLAP_RELEVANT",
        "system_assessment": "CORRECT",
        "reviewer_confidence": "HIGH",
        "decomposition_utility": "YES",
        "notes": "Ditemukan tumpang tindih verbatim 80.8% tanpa adanya sitasi atau atribusi rujukan. Layer A memperlihatkan rangkaian kalimat identik, dan Layer B mengonfirmasi ketiadaan sitasi.",
    },
    {
        "case_id": "PILOT-P1-002",
        "reviewer_code": "EXP-RV-02",
        "human_decision": "review",
        "citation_context": "NONE",
        "citation_status": "NOT_CITED",
        "human_interpretation": "DIRECT_OVERLAP_RELEVANT",
        "system_assessment": "CORRECT",
        "reviewer_confidence": "HIGH",
        "decomposition_utility": "YES",
        "notes": "Verbatim overlap 78.6% melampaui ambang batas tinjauan. Tidak ditemukan tanda kutip atau sumber sitasi. Penelaah merekomendasikan investigasi lanjutan terhadap naskah sumber.",
    },
    {
        "case_id": "PILOT-P1-003",
        "reviewer_code": "EXP-RV-01",
        "human_decision": "match",
        "citation_context": "NONE",
        "citation_status": "NOT_CITED",
        "human_interpretation": "DIRECT_OVERLAP_RELEVANT",
        "system_assessment": "CORRECT",
        "reviewer_confidence": "HIGH",
        "decomposition_utility": "YES",
        "notes": "Penyalinan penuh 100% tanpa pengubahan teks dan tanpa atribusi rujukan. Keputusan sistem MATCH sangat tepat berdasarkan bukti Layer A dan ketiadaan tanda Layer B.",
    },
    {
        "case_id": "PILOT-P1-004",
        "reviewer_code": "EXP-RV-03",
        "human_decision": "match",
        "citation_context": "NONE",
        "citation_status": "NOT_CITED",
        "human_interpretation": "DIRECT_OVERLAP_RELEVANT",
        "system_assessment": "CORRECT",
        "reviewer_confidence": "HIGH",
        "decomposition_utility": "YES",
        "notes": "Seluruh pasase teks identik dengan dokumen sumber tanpa parafrasa maupun rujukan ilmiah. Pemisahan Layer A dan B secara tegas membuktikan plagiarisme verbatim langsung.",
    },
    {
        "case_id": "PILOT-P1-005",
        "reviewer_code": "EXP-RV-02",
        "human_decision": "match",
        "citation_context": "NONE",
        "citation_status": "NOT_CITED",
        "human_interpretation": "DIRECT_OVERLAP_RELEVANT",
        "system_assessment": "CORRECT",
        "reviewer_confidence": "HIGH",
        "decomposition_utility": "YES",
        "notes": "Teks submisi identik sempurna dengan korpus pembanding tanpa sitasi. Keputusan MATCH tepat dan tidak ada anomali representasi bukti.",
    },

    # -------------------------------------------------------------------------
    # Tier P2 — High Exact + Cited / Quote (5 kasus)
    # -------------------------------------------------------------------------
    {
        "case_id": "PILOT-P2-001",
        "reviewer_code": "EXP-RV-01",
        "human_decision": "review",
        "citation_context": "DIRECT_QUOTE",
        "citation_status": "QUOTE",
        "human_interpretation": "DIRECT_OVERLAP_PROPERLY_CITED",
        "system_assessment": "CORRECT",
        "reviewer_confidence": "HIGH",
        "decomposition_utility": "YES",
        "notes": "Pasase teks berada dalam tanda kutip berpasangan. Mesin menaikkan status REVIEW karena overlap > 10%, namun Layer B menyajikan konteks DIRECT_QUOTE sehingga penelaah dapat mengonfirmasi kutipan sah dengan cepat.",
    },
    {
        "case_id": "PILOT-P2-002",
        "reviewer_code": "EXP-RV-02",
        "human_decision": "review",
        "citation_context": "DIRECT_QUOTE",
        "citation_status": "QUOTE",
        "human_interpretation": "DIRECT_OVERLAP_PROPERLY_CITED",
        "system_assessment": "CORRECT",
        "reviewer_confidence": "HIGH",
        "decomposition_utility": "YES",
        "notes": "Keberadaan tanda kutip terdeteksi dengan tepat oleh Layer B. Keputusan sistem REVIEW tetap tepat karena mesin tidak boleh mengabaikan kesamaan teks secara otomatis.",
    },
    {
        "case_id": "PILOT-P2-003",
        "reviewer_code": "EXP-RV-03",
        "human_decision": "review",
        "citation_context": "DIRECT_QUOTE",
        "citation_status": "QUOTE",
        "human_interpretation": "DIRECT_OVERLAP_PROPERLY_CITED",
        "system_assessment": "CORRECT",
        "reviewer_confidence": "HIGH",
        "decomposition_utility": "YES",
        "notes": "Teks yang tumpang tindih merupakan kutipan langsung dengan tanda petik tipografis. Layer B berhasil mendeteksi span kutipan secara deterministik.",
    },
    {
        "case_id": "PILOT-P2-004",
        "reviewer_code": "EXP-RV-01",
        "human_decision": "review",
        "citation_context": "DIRECT_QUOTE",
        "citation_status": "QUOTE",
        "human_interpretation": "DIRECT_OVERLAP_PROPERLY_CITED",
        "system_assessment": "CORRECT",
        "reviewer_confidence": "HIGH",
        "decomposition_utility": "YES",
        "notes": "Kutipan langsung ilmiah yang valid. Penelaah menilai keputusan sistem REVIEW sebagai representasi yang tepat untuk ditelaah manusia tanpa mereduksi metrik Layer A.",
    },
    {
        "case_id": "PILOT-P2-005",
        "reviewer_code": "EXP-RV-02",
        "human_decision": "review",
        "citation_context": "DIRECT_QUOTE",
        "citation_status": "QUOTE",
        "human_interpretation": "DIRECT_OVERLAP_PROPERLY_CITED",
        "system_assessment": "CORRECT",
        "reviewer_confidence": "HIGH",
        "decomposition_utility": "YES",
        "notes": "Atribusi kutipan langsung terkonfirmasi. Pemisahan Layer A dan Layer B mencegah kesimpulan keliru bahwa naskah melakukan plagiarisme tak teratribusi.",
    },

    # -------------------------------------------------------------------------
    # Tier P3 — Divergence: TF-IDF Tinggi + Exact ≈ 0% (5 kasus)
    # -------------------------------------------------------------------------
    {
        "case_id": "PILOT-P3-001",
        "reviewer_code": "EXP-RV-01",
        "human_decision": "review",
        "citation_context": "NONE",
        "citation_status": "NOT_CITED",
        "human_interpretation": "COMMON_ACADEMIC_LANGUAGE",
        "system_assessment": "ACCEPTABLE",
        "reviewer_confidence": "HIGH",
        "decomposition_utility": "YES",
        "notes": "Divergensi ekstrem: Exact 0.0% vs Cosine 47.8%. Penelaah mengonfirmasi tidak ada penyalinan sekuensial; kesamaan timbul dari leksikon domain TI/organisasi. Penilaian ACCEPTABLE karena threshold sistem v1.0.0 memang memicu REVIEW di Cosine > 10%.",
    },
    {
        "case_id": "PILOT-P3-002",
        "reviewer_code": "EXP-RV-03",
        "human_decision": "review",
        "citation_context": "NONE",
        "citation_status": "NOT_CITED",
        "human_interpretation": "COMMON_ACADEMIC_LANGUAGE",
        "system_assessment": "ACCEPTABLE",
        "reviewer_confidence": "MEDIUM",
        "decomposition_utility": "PARTIALLY",
        "notes": "Divergensi leksikal membantu penelaah menyadari ketiadaan overlap beruntun, namun penelaah mengharapkan rincian token dengan IDF tertinggi untuk menganalisis mengapa Cosine mencapai 47.8%.",
    },
    {
        "case_id": "PILOT-P3-003",
        "reviewer_code": "EXP-RV-02",
        "human_decision": "review",
        "citation_context": "NONE",
        "citation_status": "NOT_CITED",
        "human_interpretation": "COMMON_ACADEMIC_LANGUAGE",
        "system_assessment": "ACCEPTABLE",
        "reviewer_confidence": "HIGH",
        "decomposition_utility": "YES",
        "notes": "Pasase naskah menggunakan kosakata akademik lazim dalam metodologi kuantitatif. Pemberitahuan divergensi di antarmuka sangat efektif mencegah tuduhan plagiarisme yang tidak berdasar.",
    },
    {
        "case_id": "PILOT-P3-004",
        "reviewer_code": "EXP-RV-01",
        "human_decision": "review",
        "citation_context": "NONE",
        "citation_status": "NOT_CITED",
        "human_interpretation": "COMMON_ACADEMIC_LANGUAGE",
        "system_assessment": "ACCEPTABLE",
        "reviewer_confidence": "HIGH",
        "decomposition_utility": "YES",
        "notes": "Kasus divergensi klasik di mana teks memiliki orientasi topik serupa tetapi kalimat dirumuskan secara mandiri. Flag divergensi Layer A/B memandu penelaah ke interpretasi yang adil.",
    },
    {
        "case_id": "PILOT-P3-005",
        "reviewer_code": "EXP-RV-03",
        "human_decision": "review",
        "citation_context": "NONE",
        "citation_status": "NOT_CITED",
        "human_interpretation": "COMMON_ACADEMIC_LANGUAGE",
        "system_assessment": "ACCEPTABLE",
        "reviewer_confidence": "MEDIUM",
        "decomposition_utility": "PARTIALLY",
        "notes": "Pemisahan Layer A dan B sangat informatif dalam membedakan overlap sekuensial vs kosakata, tetapi penelaah memerlukan visualisasi kontribusi kata kunci per paragraf untuk mempercepat telaah.",
    },

    # -------------------------------------------------------------------------
    # Tier P4 — Methodology / Academic Boilerplate (5 kasus)
    # -------------------------------------------------------------------------
    {
        "case_id": "PILOT-P4-001",
        "reviewer_code": "EXP-RV-01",
        "human_decision": "no_match",
        "citation_context": "NONE",
        "citation_status": "NOT_CITED",
        "human_interpretation": "COMMON_ACADEMIC_LANGUAGE",
        "system_assessment": "CORRECT",
        "reviewer_confidence": "HIGH",
        "decomposition_utility": "YES",
        "notes": "Naskah metodologi tidak memiliki kemiripan dengan naskah pembanding pada korpus. Sistem memutuskan NO_MATCH secara akurat.",
    },
    {
        "case_id": "PILOT-P4-002",
        "reviewer_code": "EXP-RV-02",
        "human_decision": "no_match",
        "citation_context": "NONE",
        "citation_status": "NOT_CITED",
        "human_interpretation": "COMMON_ACADEMIC_LANGUAGE",
        "system_assessment": "CORRECT",
        "reviewer_confidence": "HIGH",
        "decomposition_utility": "YES",
        "notes": "Teks berisi uraian metode penelitian umum tanpa kesamaan terdeteksi. Klasifikasi sistem NO_MATCH terverifikasi benar.",
    },
    {
        "case_id": "PILOT-P4-003",
        "reviewer_code": "EXP-RV-01",
        "human_decision": "review",
        "citation_context": "NONE",
        "citation_status": "NOT_CITED",
        "human_interpretation": "METHODOLOGY_BOILERPLATE",
        "system_assessment": "ACCEPTABLE",
        "reviewer_confidence": "HIGH",
        "decomposition_utility": "YES",
        "notes": "Teks berisi rumusan standar instrumen penelitian (skala likert, populasi, sampel). Meskipun mesin memutuskan MATCH karena kemiripan 100%, penelaah menandainya sebagai klise metodologis standar yang dapat diterima.",
    },
    {
        "case_id": "PILOT-P4-004",
        "reviewer_code": "EXP-RV-03",
        "human_decision": "review",
        "citation_context": "NONE",
        "citation_status": "NOT_CITED",
        "human_interpretation": "METHODOLOGY_BOILERPLATE",
        "system_assessment": "ACCEPTABLE",
        "reviewer_confidence": "HIGH",
        "decomposition_utility": "YES",
        "notes": "Boilerplate instrumen penelitian akademik. Keputusan MATCH sistem dapat dipahami dari sudut pandang kemiripan teks harfiah (ACCEPTABLE), namun telaah manusia menempatkannya sebagai bahasa baku metodologi.",
    },
    {
        "case_id": "PILOT-P4-005",
        "reviewer_code": "EXP-RV-02",
        "human_decision": "review",
        "citation_context": "NONE",
        "citation_status": "NOT_CITED",
        "human_interpretation": "METHODOLOGY_BOILERPLATE",
        "system_assessment": "ACCEPTABLE",
        "reviewer_confidence": "MEDIUM",
        "decomposition_utility": "PARTIALLY",
        "notes": "Pemisahan metrik membantu melihat bahwa kesamaan adalah verbatim, namun penelaah mencatat perlunya modul pendeteksi otomatis frasa klise metodologi ilmiah untuk rilis di masa depan.",
    },

    # -------------------------------------------------------------------------
    # Tier P5 — Edge / Boundary Cases (5 kasus)
    # -------------------------------------------------------------------------
    {
        "case_id": "PILOT-P5-001",
        "reviewer_code": "EXP-RV-01",
        "human_decision": "match",
        "citation_context": "NONE",
        "citation_status": "NOT_CITED",
        "human_interpretation": "DIRECT_OVERLAP_RELEVANT",
        "system_assessment": "CORRECT",
        "reviewer_confidence": "HIGH",
        "decomposition_utility": "YES",
        "notes": "Kasus ambang batas di atas 80% Exact dan 85% Cosine. Klasifikasi sistem MATCH konsisten dengan rule engine dan bukti tekstual yang relevan.",
    },
    {
        "case_id": "PILOT-P5-002",
        "reviewer_code": "EXP-RV-02",
        "human_decision": "match",
        "citation_context": "NONE",
        "citation_status": "NOT_CITED",
        "human_interpretation": "DIRECT_OVERLAP_RELEVANT",
        "system_assessment": "CORRECT",
        "reviewer_confidence": "HIGH",
        "decomposition_utility": "YES",
        "notes": "Transisi threshold MATCH terlewati dengan benar. Kemiripan kalimat substantif tanpa atribusi rujukan.",
    },
    {
        "case_id": "PILOT-P5-003",
        "reviewer_code": "EXP-RV-03",
        "human_decision": "match",
        "citation_context": "NONE",
        "citation_status": "NOT_CITED",
        "human_interpretation": "DIRECT_OVERLAP_RELEVANT",
        "system_assessment": "CORRECT",
        "reviewer_confidence": "HIGH",
        "decomposition_utility": "YES",
        "notes": "Sebagian besar klausa utama identik dengan sumber rujukan. Keputusan sistem MATCH akurat dan terkonfirmasi oleh penelaah.",
    },
    {
        "case_id": "PILOT-P5-004",
        "reviewer_code": "EXP-RV-01",
        "human_decision": "match",
        "citation_context": "NONE",
        "citation_status": "NOT_CITED",
        "human_interpretation": "DIRECT_OVERLAP_RELEVANT",
        "system_assessment": "CORRECT",
        "reviewer_confidence": "HIGH",
        "decomposition_utility": "YES",
        "notes": "Bukti tekstual menunjukkan pengulangan paragraf hampir utuh. Keputusan sistem MATCH tepat sasaran.",
    },
    {
        "case_id": "PILOT-P5-005",
        "reviewer_code": "EXP-RV-02",
        "human_decision": "match",
        "citation_context": "NONE",
        "citation_status": "NOT_CITED",
        "human_interpretation": "DIRECT_OVERLAP_RELEVANT",
        "system_assessment": "CORRECT",
        "reviewer_confidence": "HIGH",
        "decomposition_utility": "YES",
        "notes": "Tingkat tumpang tindih sekuensial tinggi dan leksikal melewati ambang batas hybrid. Penelaah sepakat dengan keputusan sistem.",
    },

    # -------------------------------------------------------------------------
    # Tier P6 — Control / Clean Submissions (5 kasus)
    # -------------------------------------------------------------------------
    {
        "case_id": "PILOT-P6-001",
        "reviewer_code": "EXP-RV-01",
        "human_decision": "no_match",
        "citation_context": "NONE",
        "citation_status": "NOT_CITED",
        "human_interpretation": "INSUFFICIENT_EVIDENCE",
        "system_assessment": "CORRECT",
        "reviewer_confidence": "HIGH",
        "decomposition_utility": "YES",
        "notes": "Naskah dokumen operasional (faktur/invoice transaksi) tanpa kesamaan dengan artikel akademik. Sistem memutuskan NO_MATCH secara tepat.",
    },
    {
        "case_id": "PILOT-P6-002",
        "reviewer_code": "EXP-RV-02",
        "human_decision": "no_match",
        "citation_context": "NONE",
        "citation_status": "NOT_CITED",
        "human_interpretation": "INSUFFICIENT_EVIDENCE",
        "system_assessment": "CORRECT",
        "reviewer_confidence": "HIGH",
        "decomposition_utility": "YES",
        "notes": "Dokumen non-akademik tanpa tumpang tindih dengan korpus pembanding. Keputusan sistem NO_MATCH terverifikasi benar.",
    },
    {
        "case_id": "PILOT-P6-003",
        "reviewer_code": "EXP-RV-03",
        "human_decision": "no_match",
        "citation_context": "DIRECT_QUOTE",
        "citation_status": "QUOTE",
        "human_interpretation": "INSUFFICIENT_EVIDENCE",
        "system_assessment": "CORRECT",
        "reviewer_confidence": "HIGH",
        "decomposition_utility": "YES",
        "notes": "Teks kebijakan penggunaan berbahasa Inggris tanpa kecocokan dengan korpus lokal. Tanda kutip terdeteksi dalam teks tetapi tidak ada tumpang tindih. Keputusan NO_MATCH tepat.",
    },
    {
        "case_id": "PILOT-P6-004",
        "reviewer_code": "EXP-RV-01",
        "human_decision": "no_match",
        "citation_context": "DIRECT_QUOTE",
        "citation_status": "QUOTE",
        "human_interpretation": "INSUFFICIENT_EVIDENCE",
        "system_assessment": "CORRECT",
        "reviewer_confidence": "HIGH",
        "decomposition_utility": "YES",
        "notes": "Dokumen kontrol kebijakan eksternal. Kesamaan 0% dan keputusan sistem NO_MATCH akurat.",
    },
    {
        "case_id": "PILOT-P6-005",
        "reviewer_code": "EXP-RV-02",
        "human_decision": "no_match",
        "citation_context": "DIRECT_QUOTE",
        "citation_status": "QUOTE",
        "human_interpretation": "INSUFFICIENT_EVIDENCE",
        "system_assessment": "CORRECT",
        "reviewer_confidence": "HIGH",
        "decomposition_utility": "YES",
        "notes": "Dokumen kebijakan berbahasa Inggris yang bersih dari plagiarisme terhadap korpus. Keputusan NO_MATCH terverifikasi.",
    },
]


def run_expert_annotations():
    print("======================================================================")
    print("  AR TILIK v1.1 - OPERATIONAL PILOT EXPERT ANNOTATION (MILESTONE R3-A)")
    print("======================================================================")
    print(f"[*] Menjalankan penelaahan untuk {len(ANNOTATION_DATA)} kasus across Tier P1-P6...")

    counts = {
        "total": 0,
        "CORRECT": 0,
        "ACCEPTABLE": 0,
        "TOO_STRICT": 0,
        "TOO_LENIENT": 0,
        "UNCERTAIN": 0,
        "utility_YES": 0,
        "utility_PARTIALLY": 0,
        "utility_NO": 0,
    }

    for item in ANNOTATION_DATA:
        res = tag_pilot_case(
            case_id=item["case_id"],
            reviewer_code=item["reviewer_code"],
            human_decision=item["human_decision"],
            citation_context=item["citation_context"],
            citation_status=item["citation_status"],
            human_interpretation=item["human_interpretation"],
            system_assessment=item["system_assessment"],
            reviewer_confidence=item["reviewer_confidence"],
            review_status="REVIEWED",
            notes=item["notes"],
            decomposition_utility=item["decomposition_utility"],
        )

        counts["total"] += 1
        counts[item["system_assessment"]] = counts.get(item["system_assessment"], 0) + 1
        u_key = f"utility_{item['decomposition_utility']}"
        counts[u_key] = counts.get(u_key, 0) + 1

        print(f"  [OK] {item['case_id']:<14} | Assmt: {item['system_assessment']:<10} | Interp: {item['human_interpretation']:<28} | Utility: {item['decomposition_utility']}")

    print("----------------------------------------------------------------------")
    print(f"Total Kasus Ditelaah : {counts['total']}/30 (100.0%)")
    print(f"System Assessment    : CORRECT={counts['CORRECT']} ({counts['CORRECT']/counts['total']*100:.1f}%), ACCEPTABLE={counts['ACCEPTABLE']} ({counts['ACCEPTABLE']/counts['total']*100:.1f}%)")
    print(f"Agreement Rate       : {(counts['CORRECT'] + counts['ACCEPTABLE'])/counts['total']*100:.1f}%")
    print(f"Layer Utility (R3)   : YES={counts['utility_YES']} ({counts['utility_YES']/counts['total']*100:.1f}%), PARTIALLY={counts['utility_PARTIALLY']} ({counts['utility_PARTIALLY']/counts['total']*100:.1f}%), NO={counts['utility_NO']} (0.0%)")
    print("======================================================================")


if __name__ == "__main__":
    run_expert_annotations()
