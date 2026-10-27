"""
AR Tilik - Semantic Lens (Lapisan Kedua Semantik)
Modul representasi semantik naskah berbasis embedding lokal dan Qdrant Vector Store.

Karakteristik Desain:
- Model: paraphrase-multilingual-MiniLM-L12-v2 (384 dimensi, ONNX Runtime CPU via FastEmbed)
- Zero Data Leakage: 100% inferensi lokal on-premise, tidak ada naskah yang keluar ke API publik
- Filter Teknis: Blok kode [💻 ...], rumus [📐 ...], dan grafik [📊 ...] tidak dihitung sebagai teks narasi
- Skalabilitas: Penyimpanan vektor di Qdrant (Cosine Distance) dengan payload metadata lengkap
"""

import os
import re
import uuid
from typing import List, Dict, Any, Optional

try:
    from fastembed import TextEmbedding
    FASTEMBED_AVAILABLE = True
except ImportError:
    FASTEMBED_AVAILABLE = False

try:
    from qdrant_client import QdrantClient
    from qdrant_client.models import Distance, VectorParams, PointStruct, Filter, FieldCondition, MatchValue
    QDRANT_AVAILABLE = True
except ImportError:
    QDRANT_AVAILABLE = False

# Konfigurasi Default Qdrant & Model
QDRANT_HOST = os.getenv("QDRANT_HOST", "127.0.0.1")
QDRANT_PORT = int(os.getenv("QDRANT_PORT", "6333"))
COLLECTION_NAME = "artilik_corpus_semantic"
MODEL_NAME = "sentence-transformers/paraphrase-multilingual-MiniLM-L12-v2"
VECTOR_DIM = 384


def is_semantic_engine_ready() -> bool:
    """Memeriksa apakah pustaka fastembed, qdrant-client, dan server Qdrant tersedia."""
    if not (FASTEMBED_AVAILABLE and QDRANT_AVAILABLE):
        return False
    try:
        client = QdrantClient(host=QDRANT_HOST, port=QDRANT_PORT, timeout=2)
        client.get_collections()
        return True
    except Exception:
        return False


def clean_chunk_text_for_embedding(text: str) -> str:
    """
    Membersihkan teks sebelum embedding:
    Menghilangkan tag buatan sistem agar tidak mencemari ruang vektor semantik.
    """
    if not text:
        return ""
    # Hapus penanda buatan sistem (rumus, kode, grafik, kaligrafi, catatan kaki)
    cleaned = re.sub(r'\[💻[^\]]*\]', '', text)
    cleaned = re.sub(r'\[📐[^\]]*\]', '', cleaned)
    cleaned = re.sub(r'\[📊[^\]]*\]', '', cleaned)
    cleaned = re.sub(r'\[📜[^\]]*\]', '', cleaned)
    cleaned = re.sub(r'\[Catatan Kaki[^\]]*\]', '', cleaned)
    cleaned = re.sub(r'\s+', ' ', cleaned).strip()
    return cleaned


def chunk_document_semantically(
    pages: List[Dict[str, Any]],
    target_words: int = 200,
    min_words: int = 30
) -> List[Dict[str, Any]]:
    """
    Memecah teks naskah per paragraf atau jendela semantik (~200-250 kata).
    Menghindari pecahan kalimat di tengah jalan dan melampirkan metadata nomor halaman.
    """
    chunks = []
    chunk_index = 0

    for p in pages:
        page_num = p.get("page_number", 1)
        raw_page_text = p.get("text", "")
        if not raw_page_text:
            continue

        # Pecah per paragraf alami berdasarkan baris kosong ganda
        paragraphs = [para.strip() for para in raw_page_text.split("\n\n") if para.strip()]
        
        current_words = []
        current_raw = []

        for para in paragraphs:
            cleaned_para = clean_chunk_text_for_embedding(para)
            words = cleaned_para.split()
            if not words:
                continue

            # Jika penambahan paragraf ini melebihi target_words dan buffer sudah cukup besar
            if len(current_words) + len(words) > target_words and len(current_words) >= min_words:
                chunk_text = " ".join(current_words)
                chunks.append({
                    "chunk_index": chunk_index,
                    "page_number": page_num,
                    "text": chunk_text,
                    "word_count": len(current_words)
                })
                chunk_index += 1
                current_words = list(words)
                current_raw = [para]
            else:
                current_words.extend(words)
                current_raw.append(para)

        # Simpan sisa paragraf di akhir halaman
        if len(current_words) >= min_words:
            chunk_text = " ".join(current_words)
            chunks.append({
                "chunk_index": chunk_index,
                "page_number": page_num,
                "text": chunk_text,
                "word_count": len(current_words)
            })
            chunk_index += 1
        elif current_words and chunks:
            # Jika potongan terakhir terlalu pendek (< min_words), gabungkan ke potongan sebelumnya
            prev = chunks[-1]
            prev["text"] = prev["text"] + " " + " ".join(current_words)
            prev["word_count"] += len(current_words)
        elif current_words and not chunks:
            # Jika dokumen pendek dan belum ada chunk, tetap simpan agar naskah ringkas/benchmark terindeks
            chunk_text = " ".join(current_words)
            chunks.append({
                "chunk_index": chunk_index,
                "page_number": page_num,
                "text": chunk_text,
                "word_count": len(current_words)
            })
            chunk_index += 1

    return chunks


class SemanticLens:
    """
    Pengelola inferensi embedding lokal dan komunikasi ke Qdrant Vector Store.
    Menggunakan pola Singleton untuk model caching agar tidak memuat ulang model berulang kali.
    """
    _instance = None
    _model = None

    def __new__(cls, *args, **kwargs):
        if not cls._instance:
            cls._instance = super(SemanticLens, cls).__new__(cls)
        return cls._instance

    def __init__(self, host: str = QDRANT_HOST, port: int = QDRANT_PORT):
        if not hasattr(self, 'initialized'):
            self.host = host
            self.port = port
            self.client = None
            self.initialized = True
            self._init_qdrant()

    def _init_qdrant(self):
        if not QDRANT_AVAILABLE:
            return
        try:
            self.client = QdrantClient(host=self.host, port=self.port, timeout=5)
            # Pastikan koleksi semantik korpus sudah terbentuk
            collections = [c.name for c in self.client.get_collections().collections]
            if COLLECTION_NAME not in collections:
                self.client.create_collection(
                    collection_name=COLLECTION_NAME,
                    vectors_config=VectorParams(size=VECTOR_DIM, distance=Distance.COSINE),
                )
        except Exception:
            self.client = None

    def get_model(self):
        """Memuat model embedding secara lazy (sekali pakai dalam memori proses)."""
        if self._model is None and FASTEMBED_AVAILABLE:
            self._model = TextEmbedding(MODEL_NAME)
        return self._model

    def embed_texts(self, texts: List[str]) -> List[List[float]]:
        """Menghasilkan representasi vektor untuk daftar teks narasi."""
        model = self.get_model()
        if not model or not texts:
            return []
        embeddings = list(model.embed(texts))
        return [e.tolist() if hasattr(e, 'tolist') else list(e) for e in embeddings]

    def index_corpus_document(self, document_id: int, pages: List[Dict[str, Any]]) -> int:
        """
        Memecah naskah korpus menjadi jendela semantik, menghitung embedding,
        dan menyimpannya ke koleksi Qdrant.
        """
        if not self.client:
            self._init_qdrant()
        if not self.client:
            return 0

        chunks = chunk_document_semantically(pages)
        if not chunks:
            return 0

        # Hapus vektor lama dokumen ini jika sebelumnya pernah diindeks
        self.delete_corpus_document(document_id)

        texts = [c["text"] for c in chunks]
        embeddings = self.embed_texts(texts)
        if not embeddings or len(embeddings) != len(chunks):
            return 0

        points = []
        for i, (chunk, vec) in enumerate(zip(chunks, embeddings)):
            point_id = str(uuid.uuid5(uuid.NAMESPACE_DNS, f"artilik_corpus_{document_id}_{chunk['chunk_index']}"))
            points.append(PointStruct(
                id=point_id,
                vector=vec,
                payload={
                    "document_id": document_id,
                    "chunk_index": chunk["chunk_index"],
                    "page_number": chunk["page_number"],
                    "word_count": chunk["word_count"],
                    "preview": chunk["text"][:300]
                }
            ))

        # Simpan dalam batch ke Qdrant
        batch_size = 100
        for i in range(0, len(points), batch_size):
            self.client.upsert(
                collection_name=COLLECTION_NAME,
                points=points[i:i + batch_size]
            )

        return len(points)

    def delete_corpus_document(self, document_id: int) -> bool:
        """Menghapus seluruh titik vektor dokumen korpus dari Qdrant."""
        if not self.client:
            return False
        try:
            self.client.delete(
                collection_name=COLLECTION_NAME,
                points_selector=Filter(
                    must=[
                        FieldCondition(
                            key="document_id",
                            match=MatchValue(value=document_id)
                        )
                    ]
                )
            )
            return True
        except Exception:
            return False

    def search_similar_chunks(
        self,
        query_text: str,
        top_k: int = 3,
        score_threshold: float = 0.70,
        exclude_doc_id: Optional[int] = None
    ) -> List[Dict[str, Any]]:
        """
        Mencari potongan korpus yang memiliki kemiripan makna tertinggi terhadap potongan teks kueri.
        """
        if not self.client:
            self._init_qdrant()
        if not self.client:
            return []

        cleaned = clean_chunk_text_for_embedding(query_text)
        if len(cleaned.split()) < 15:
            return []

        vecs = self.embed_texts([cleaned])
        if not vecs:
            return []

        query_vec = vecs[0]

        query_filter = None
        if exclude_doc_id is not None:
            query_filter = Filter(
                must_not=[
                    FieldCondition(
                        key="document_id",
                        match=MatchValue(value=exclude_doc_id)
                    )
                ]
            )

        try:
            if hasattr(self.client, "query_points"):
                results = self.client.query_points(
                    collection_name=COLLECTION_NAME,
                    query=query_vec,
                    query_filter=query_filter,
                    limit=top_k,
                    score_threshold=score_threshold
                )
                points = results.points
            elif hasattr(self.client, "search"):
                points = self.client.search(
                    collection_name=COLLECTION_NAME,
                    query_vector=query_vec,
                    query_filter=query_filter,
                    limit=top_k,
                    score_threshold=score_threshold
                )
            else:
                points = []

            hits = []
            for r in points:
                hits.append({
                    "score": round(float(r.score), 4),
                    "document_id": r.payload.get("document_id"),
                    "page_number": r.payload.get("page_number", 1),
                    "chunk_index": r.payload.get("chunk_index", 0),
                    "preview": r.payload.get("preview", "")
                })
            return hits
        except Exception as e:
            print(f"[SEMANTIC SEARCH ERROR] {e}")
            return []
