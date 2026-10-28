#!/usr/bin/env python3
"""
AR Tilik - Index Latin Reference Sources to Corpus & Qdrant
Mendaftarkan 10 naskah rujukan (terjemahan Indonesia segar) ke tabel documents,
document_pages, dan document_chunks serta mengindeks titik vektornya ke Qdrant.
Submisi Latin TIDAK diindeks (hanya sebagai query submisi uji).
"""

import os
import sys
import json
from pathlib import Path

PROJECT_ROOT = Path(__file__).resolve().parents[1]
if str(PROJECT_ROOT) not in sys.path:
    sys.path.insert(0, str(PROJECT_ROOT))

from worker.database import get_connection
from worker.semantic_lens import SemanticLens, is_semantic_engine_ready

LATIN_CASES = [
    {
        "case_id": "XLN-LA-001",
        "pair": "LA-ID",
        "domain": "Kitab Suci / Teologi Penciptaan",
        "source_text": "Pada awal mula, Sang Pencipta menjadikan langit beserta bumi seisinya.",
        "submission_text": "In principio creavit Deus caelum et terram.",
        "is_plagiarized": True,
        "source_lang": "id",
        "submission_lang": "la",
        "description": "Terjemahan segar Vulgata Kejadian 1:1."
    },
    {
        "case_id": "XLN-LA-002",
        "pair": "LA-ID",
        "domain": "Kitab Suci / Kristologi Sabda",
        "source_text": "Pada permulaan telah ada Sang Sabda, dan Sabda itu senantiasa bersama Allah, dan Sabda itu sungguhlah Allah.",
        "submission_text": "In principio erat Verbum, et Verbum erat apud Deum, et Deus erat Verbum.",
        "is_plagiarized": True,
        "source_lang": "id",
        "submission_lang": "la",
        "description": "Terjemahan segar Vulgata Yohanes 1:1."
    },
    {
        "case_id": "XLN-LA-003",
        "pair": "LA-ID",
        "domain": "Kitab Suci / Teologi Pastoral",
        "source_text": "Tuhan yang menuntunku bagai seorang gembala, sehingga aku tidak akan berkekurangan.",
        "submission_text": "Dominus regit me, et nihil mihi deerit.",
        "is_plagiarized": True,
        "source_lang": "id",
        "submission_lang": "la",
        "description": "Terjemahan segar Vulgata Mazmur 23:1."
    },
    {
        "case_id": "XLN-LA-004",
        "pair": "LA-ID",
        "domain": "Kitab Suci / Spiritualitas Injil",
        "source_text": "Berbahagialah mereka yang rendah hati dan bersahaja dalam roh, karena milik merekalah kerajaan surga.",
        "submission_text": "Beati pauperes spiritu, quoniam ipsorum est regnum caelorum.",
        "is_plagiarized": True,
        "source_lang": "id",
        "submission_lang": "la",
        "description": "Terjemahan segar Vulgata Matius 5:3."
    },
    {
        "case_id": "XLN-LA-005",
        "pair": "LA-ID",
        "domain": "Kitab Suci / Kebajikan Ilahi",
        "source_text": "Maka kini tetaplah ada ketiga perkara ini: iman, pengharapan, dan kasih; namun yang paling utama di antaranya adalah kasih.",
        "submission_text": "Nunc autem manent fides, spes, caritas, tria haec; maior autem his est caritas.",
        "is_plagiarized": True,
        "source_lang": "id",
        "submission_lang": "la",
        "description": "Terjemahan segar Vulgata 1 Korintus 13:13."
    },
    {
        "case_id": "XLN-LA-006",
        "pair": "LA-ID",
        "domain": "Liturgi / Doa Kristen Purba",
        "source_text": "Bapa kami yang bertakhta di surga, dimuliakanlah nama-Mu yang kudus. Datanglah kerajaan-Mu, terwujudlah kehendak-Mu di atas bumi seperti halnya di dalam surga.",
        "submission_text": "Pater noster, qui es in caelis, sanctificetur nomen tuum. Adveniat regnum tuum; fiat voluntas tua, sicut in caelo et in terra.",
        "is_plagiarized": True,
        "source_lang": "id",
        "submission_lang": "la",
        "description": "Terjemahan segar formula doa Pater Noster."
    },
    {
        "case_id": "XLN-LA-007",
        "pair": "LA-ID",
        "domain": "Devosi / Mariologi Tradisional",
        "source_text": "Salam kepadamu Maria, yang penuh kelimpahan rahmat, Tuhan menyertaimu. Terberkatilah engkau di antara kaum perempuan, dan terberkatilah buah rahimmu, Yesus.",
        "submission_text": "Ave Maria, gratia plena, Dominus tecum. Benedicta tu in mulieribus, et benedictus fructus ventris tui, Iesus.",
        "is_plagiarized": True,
        "source_lang": "id",
        "submission_lang": "la",
        "description": "Terjemahan segar doa devosional Ave Maria."
    },
    {
        "case_id": "XLN-LA-008",
        "pair": "LA-ID",
        "domain": "Pengakuan Iman / Eklesiologi",
        "source_text": "Aku beriman kepada satu Allah, Bapa yang mahakuasa, pencipta semesta langit dan bumi, beserta segala ciptaan yang tampak maupun yang tak tampak.",
        "submission_text": "Credo in unum Deum, Patrem omnipotentem, factorem caeli et terrae, visibilium omnium et invisibilium.",
        "is_plagiarized": True,
        "source_lang": "id",
        "submission_lang": "la",
        "description": "Terjemahan segar Simbolon Nicea-Konstantinopel."
    },
    {
        "case_id": "XLN-LA-009",
        "pair": "LA-ID",
        "domain": "Doktrin Skolastik / Etika Moral",
        "source_text": "Dari sanalah tampak jelas bahwa hukum kodrat alami tiada lain merupakan keikutsertaan hukum abadi pada makhluk yang berakal budi.",
        "submission_text": "Unde patet quod lex naturalis nihil aliud est quam participatio legis aeternae in rationali creatura.",
        "is_plagiarized": True,
        "source_lang": "id",
        "submission_lang": "la",
        "description": "Terjemahan segar Thomas Aquinas (Summa Theologiae I-II, q. 91, a. 2)."
    },
    {
        "case_id": "XLN-LA-010",
        "pair": "LA-ID",
        "domain": "Liturgi / Himne Kemuliaan",
        "source_text": "Kemuliaan bagi Allah di tempat yang mahatinggi, dan damai sejahtera di bumi bagi sekalian insan yang berkehendak baik.",
        "submission_text": "Gloria in excelsis Deo, et in terra pax hominibus bonae voluntatis.",
        "is_plagiarized": True,
        "source_lang": "id",
        "submission_lang": "la",
        "description": "Terjemahan segar Doksologi Raya Gloria in Excelsis Deo."
    },
    # 2 Kasus Kontrol Negatif
    {
        "case_id": "XLN-LA-NEG-001",
        "pair": "LA-CLEAN",
        "domain": "Retorika Romawi Klasik (Non-Teologis)",
        "source_text": "Pada awal mula, Sang Pencipta menjadikan langit beserta bumi seisinya.",
        "submission_text": "Quo usque tandem abutere, Catilina, patientia nostra? Quam diu etiam furor iste tuus nos eludet?",
        "is_plagiarized": False,
        "source_lang": "id",
        "submission_lang": "la",
        "description": "Kontrol Negatif: Pidato Cicero In Catilinam vs Teologi Penciptaan."
    },
    {
        "case_id": "XLN-LA-NEG-002",
        "pair": "LA-CLEAN",
        "domain": "Formula Liturgis Doksologi Singkat",
        "source_text": "Bapa kami yang bertakhta di surga, dimuliakanlah nama-Mu yang kudus. Datanglah kerajaan-Mu, terwujudlah kehendak-Mu di atas bumi seperti halnya di dalam surga.",
        "submission_text": "Gloria Patri, et Filio, et Spiritui Sancto, sicut erat in principio, et nunc, et semper, et in saecula saeculorum. Amen.",
        "is_plagiarized": False,
        "source_lang": "id",
        "submission_lang": "la",
        "description": "Kontrol Negatif: Formula boilerplate Gloria Patri vs Doa Bapa Kami."
    }
]


def index_sources():
    print("=" * 68)
    print("   AR Tilik - Indeks Sumber Rujukan Bahasa Latin (STAKat / Teologi)   ")
    print("=" * 68)

    output_dir = PROJECT_ROOT / "datasets"
    output_dir.mkdir(parents=True, exist_ok=True)
    dataset_file = output_dir / "artilik_latin_benchmark_dataset.json"

    with open(dataset_file, "w", encoding="utf-8") as f:
        json.dump(LATIN_CASES, f, ensure_ascii=False, indent=2)
    print(f"[✓] Berhasil menyimpan dataset 12 kasus Latin di {dataset_file}")

    if not is_semantic_engine_ready():
        print("[X] Mesin semantik / Qdrant tidak siap!")
        return

    lens = SemanticLens()
    conn = get_connection()
    cursor = conn.cursor(dictionary=True)

    inserted_count = 0
    indexed_vectors = 0

    try:
        # Hanya indeks 10 kasus positif (source_text bahasa Indonesia)
        for c in LATIN_CASES:
            if not c.get("is_plagiarized"):
                continue

            case_id = c["case_id"]
            domain = c["domain"]
            src_text = c["source_text"].strip()
            title = f"Rujukan Benchmark Latin [{case_id}] - ({domain})"

            cursor.execute("SELECT id FROM documents WHERE title = %s AND document_type = 'corpus'", (title,))
            existing = cursor.fetchone()

            if existing:
                doc_id = existing["id"]
            else:
                cursor.execute(
                    """
                    INSERT INTO documents (
                        user_id, title, author, original_filename, mime_type, file_size,
                        storage_path, status, document_type, corpus_visibility, source_type,
                        indexing_status, created_at, updated_at
                    ) VALUES (
                        NULL, %s, 'AR Tilik Latin Theology Benchmark', %s, 'text/plain', %s,
                        '', 'completed', 'corpus', 'corpus', 'other',
                        'completed', NOW(), NOW()
                    )
                    """,
                    (title, f"{case_id.lower()}_source.txt", len(src_text.encode('utf-8')))
                )
                doc_id = cursor.lastrowid
                conn.commit()

                cursor.execute(
                    """
                    INSERT INTO document_pages (document_id, page_number, text, created_at)
                    VALUES (%s, 1, %s, NOW())
                    """,
                    (doc_id, src_text)
                )

                words = src_text.split()
                cursor.execute(
                    """
                    INSERT INTO document_chunks (
                        document_id, page_id, chunk_index, text, normalized_text,
                        word_count, start_word_position, end_word_position, created_at
                    ) VALUES (
                        %s, 0, 0, %s, %s, %s, 0, %s, NOW()
                    )
                    """,
                    (doc_id, src_text, src_text.lower(), len(words), max(0, len(words) - 1))
                )
                conn.commit()
                inserted_count += 1

            pages = [{"page_number": 1, "text": src_text}]
            count = lens.index_corpus_document(doc_id, pages)
            indexed_vectors += count
            print(f"[✓] {case_id}: Doc #{doc_id} terindeks ke Qdrant ({count} vektor)")

        print("=" * 68)
        print(f"[✓] Selesai: {inserted_count} dokumen rujukan baru, {indexed_vectors} vektor terindeks ke Qdrant.")
        print("=" * 68)
    finally:
        cursor.close()
        conn.close()


if __name__ == "__main__":
    index_sources()
