"""
AR Tilik - Semantic Verifier (Pemeriksa Ulang Zona Abu-Abu)
Mengimplementasikan verifikasi semantik sebagai lapisan kedua (second lens), bukan pengganti.

Logika Utama:
- Berjalan selektif pada dokumen yang masuk 'zona abu-abu':
  a. Skor leksikal antara 5.0% s.d. 25.0% (di mana parafrase sinonim bertingkat sering lolos).
  b. Dokumen dengan manipulasi evasif terdeteksi (evasion flag aktif).
  c. Naskah dengan dugaan parafrase sintaksis tinggi.
- Potongan submisi dicocokkan dengan korpus rujukan di Qdrant.
- Hasil disimpan secara transparan di evidence_data['semantic_lens'] tanpa mencemari skor leksikal utama.
"""

from typing import List, Dict, Any, Optional
from worker.semantic_lens import SemanticLens, chunk_document_semantically, is_semantic_engine_ready


class SemanticVerifier:
    def __init__(self):
        self.lens = SemanticLens() if is_semantic_engine_ready() else None

    def should_evaluate_gray_zone(
        self,
        lexical_similarity: float,
        evasion_data: Optional[Dict[str, Any]] = None,
        force_check: bool = False
    ) -> tuple[bool, str]:
        """
        Menentukan apakah naskah memenuhi kriteria untuk pemeriksaan ulang semantik.
        """
        if force_check:
            return True, "manual_force_check"

        # 1. Zona Abu-Abu Leksikal: Kemiripan leksikal moderat di mana parafrase sering terselubung
        if 5.0 <= lexical_similarity < 25.0:
            return True, "gray_zone_lexical_range"

        # 2. Indikasi Evasif Aktif: Pelaku manipulasi teks/font sering memparafrasekan naskah
        if evasion_data and evasion_data.get("is_manipulated", False):
            return True, "evasion_anomaly_detected"

        # Jika skor sangat tinggi (>= 25%), bukti leksikal sudah sangat kuat dan tidak ambigu
        # Jika skor sangat bersih (< 5%) dan tanpa anomali, lewati komputasi untuk efisiensi
        return False, "outside_gray_zone"

    def verify_submission(
        self,
        submission_pages: List[Dict[str, Any]],
        lexical_similarity: float,
        evasion_data: Optional[Dict[str, Any]] = None,
        exclude_doc_id: Optional[int] = None,
        similarity_threshold: float = 0.75,
        force_check: bool = False
    ) -> Dict[str, Any]:
        """
        Melakukan evaluasi semantik terhadap naskah jika memenuhi kriteria zona abu-abu.
        """
        should_run, reason = self.should_evaluate_gray_zone(
            lexical_similarity=lexical_similarity,
            evasion_data=evasion_data,
            force_check=force_check
        )

        default_result = {
            "is_evaluated": False,
            "evaluation_reason": reason,
            "semantic_score": 0.0,
            "matched_chunks_count": 0,
            "top_matches": [],
            "model": "paraphrase-multilingual-MiniLM-L12-v2",
            "threshold_used": similarity_threshold
        }

        if not should_run or not self.lens:
            return default_result

        # Pecah naskah submisi menjadi jendela semantik
        chunks = chunk_document_semantically(submission_pages)
        if not chunks:
            return default_result

        matched_chunks = []
        scores = []

        for ch in chunks:
            raw_text = ch["text"]
            hits = self.lens.search_similar_chunks(
                query_text=raw_text,
                top_k=2,
                score_threshold=similarity_threshold,
                exclude_doc_id=exclude_doc_id
            )

            if hits:
                best_hit = hits[0]
                matched_chunks.append({
                    "submission_page": ch["page_number"],
                    "submission_chunk_index": ch["chunk_index"],
                    "submission_preview": raw_text[:250],
                    "matched_document_id": best_hit["document_id"],
                    "matched_page": best_hit["page_number"],
                    "matched_preview": best_hit["preview"],
                    "cosine_similarity": best_hit["score"]
                })
                scores.append(best_hit["score"])

        # Kalkulasi skor semantik agregat
        semantic_score = 0.0
        if scores:
            # Rata-rata kemiripan dari potongan-potongan yang melampaui ambang batas
            avg_score = sum(scores) / len(scores)
            # Proporsi potongan yang cocok terhadap total potongan naskah
            coverage = min(1.0, len(scores) / max(1, len(chunks)))
            semantic_score = round(float(avg_score * 100.0 * coverage), 2)

        return {
            "is_evaluated": True,
            "evaluation_reason": reason,
            "semantic_score": semantic_score,
            "matched_chunks_count": len(matched_chunks),
            "top_matches": matched_chunks[:10],
            "total_chunks_audited": len(chunks),
            "model": "paraphrase-multilingual-MiniLM-L12-v2",
            "threshold_used": similarity_threshold
        }
