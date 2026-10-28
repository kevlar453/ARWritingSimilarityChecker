#!/usr/bin/env python3
"""
AR Tilik - Audit Sensus Distribusi Bahasa Vektor Qdrant
Memindai 100% titik (points) pada koleksi artilik_corpus_semantic untuk
mengklasifikasikan bahasa payload cuplikan naskah korpus secara transparan.
"""

import sys
import time
from collections import Counter
import re
from pathlib import Path

PROJECT_ROOT = Path(__file__).resolve().parents[1]
if str(PROJECT_ROOT) not in sys.path:
    sys.path.insert(0, str(PROJECT_ROOT))

from qdrant_client import QdrantClient

def main():
    t0 = time.time()
    client = QdrantClient(host="localhost", port=6333)
    coll = client.get_collection("artilik_corpus_semantic")
    total_vectors = coll.points_count

    print("=" * 72)
    print(" AR TILIK - AUDIT SENSUS BAHASA KOLEKSI VEKTOR QDRANT (PRODUKSI)")
    print("=" * 72)
    print(f"Host Qdrant       : http://localhost:6333")
    print(f"Nama Koleksi      : artilik_corpus_semantic")
    print(f"Status Koleksi    : {coll.status}")
    print(f"Total Titik/Point : {total_vectors:,} vektor")
    print(f"Dimensi Vektor    : 384 dimensi (Cosine Metric, ONNX MiniLM-L12)")
    print("-" * 72)

    langs = Counter()
    samples = {
        "Indonesia (ID)": [],
        "Inggris (EN)": [],
        "Belanda (NL)": [],
        "Arab (AR)": [],
        "Jerman (DE)": [],
        "Mandarin (ZH)": []
    }

    offset = None
    total_scanned = 0

    while True:
        records, next_offset = client.scroll(
            collection_name="artilik_corpus_semantic",
            limit=1000,
            offset=offset,
            with_payload=["preview", "document_id", "chunk_index"],
            with_vectors=False
        )
        if not records:
            break
        total_scanned += len(records)
        for p in records:
            txt = (p.payload or {}).get("preview", "")
            doc_id = (p.payload or {}).get("document_id", 0)
            
            if re.search(r"[\u4e00-\u9fff]", txt):
                tag = "Mandarin (ZH)"
            elif re.search(r"[\u0600-\u06ff]", txt):
                tag = "Arab (AR)"
            elif any(w in txt.lower() for w in [" the ", " and ", " of ", " research ", " this paper ", " university "]):
                tag = "Inggris (EN)"
            elif any(w in txt.lower() for w in [" der ", " die ", " das ", " und ", " nicht "]):
                tag = "Jerman (DE)"
            elif any(w in txt.lower() for w in [" het ", " van ", " een ", " artikel ", " wetboek "]):
                tag = "Belanda (NL)"
            else:
                tag = "Indonesia (ID)"
                
            langs[tag] += 1
            if len(samples[tag]) < 2:
                clean_prev = " ".join(txt.split())[:75]
                samples[tag].append(f"Doc #{doc_id} -> \"{clean_prev}...\"")
                
        if not next_offset:
            break
        offset = next_offset

    dur = time.time() - t0
    print(f"Total Vektor Dipindai: {total_scanned:,} dari {total_vectors:,} ({dur:.2f} detik)")
    print("-" * 72)
    print(f"{'BAHASA':<18} | {'JUMLAH VEKTOR':<14} | {'PROPORSI':<10}")
    print("-" * 72)
    for lang, count in langs.most_common():
        pct = (count / total_scanned) * 100
        print(f"{lang:<18} | {count:>10,} pkt | {pct:>7.2f} %")
    print("=" * 72)
    print("CONTOH CUPLIKAN PREVIEW PAYLOAD PER BAHASA:")
    for lang, smps in samples.items():
        print(f"[{lang}] ({langs[lang]:,} vektor):")
        for s in smps:
            print(f"   * {s}")
    print("=" * 72)

if __name__ == "__main__":
    main()
