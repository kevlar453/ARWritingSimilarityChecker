# Checklist Rilis Produksi — AR Tilik v1.0.0

Dokumen ini berisi daftar periksa komprehensif (*release checklist*) sebagai syarat formal kelayakan rilis **AR Tilik v1.0.0 General Availability (GA)**.

---

## 📋 1. Integritas Kode & Tata Kelola Repositori

- [x] **Git Repository Initialized**: Repositori git resmi aktif di `https://github.com/kevlar453/ARWritingSimilarityChecker`.
- [x] **No Leaked Secrets**: `.env` dikecualikan di `.gitignore`; berkas template `.env.example` bersih dari kredensial riil.
- [x] **Branching & Tagging**: Branch `main` sinkron, tag `v1.0.0-rc1` terpasang, siap untuk tag `v1.0.0`.
- [x] **Clean Working Tree**: Tidak ada file sementara (`.pyc`, `__pycache__`, temporary logs) yang terlacak di git.

---

## 🔒 2. Pembekuan Algoritma & Model Bukti (Freeze v1.0)

- [x] **Aturan Agregasi Bukti Dikunci**: Fungsi `aggregate_evidence()` pada `worker/hybrid.py` berstatus *frozen*.
- [x] **Ambang Batas Dikunci**:
  - `DEFAULT_EXACT_MATCH_SIMILARITY` = `70.0%`
  - `DEFAULT_EXACT_REVIEW_SIMILARITY` = `10.0%`
  - `DEFAULT_TFIDF_MATCH_SIMILARITY` = `85.0%`
  - `DEFAULT_TFIDF_REVIEW_SIMILARITY` = `10.0%`
  - `DEFAULT_EXACT_MATCH_LONGEST` = `50 kata`
  - `DEFAULT_EXACT_REVIEW_LONGEST` = `10 kata`
  - `DEFAULT_SIGNIFICANT_MIN_IDF` = `0.5`
- [x] **Evidence Contract**: Struktur data terstandarisasi (`EvidenceContract`) digunakan konsisten antara worker, database, dan antarmuka web.

---

## 🧪 3. Pengujian Otomatis & Sertifikasi Mutu

- [x] **Automated Unit Tests**: 28 / 28 test lulus hijau (`./artilik test`).
- [x] **Production Readiness**: 6 / 6 skenario ketahanan edge cases lulus (`./artilik readiness`).
  - [x] Penanganan berkas kosong (0-byte) tanpa ZeroDivisionError.
  - [x] Penanganan teks mikro (2 kata) tanpa index error pada 5-gram shingle.
  - [x] Penanganan dokumen panjang (3.000 kata) tanpa timeout atau memory leak.
  - [x] Penanganan karakter non-ASCII, petik typographical, rumus matematika, dan emoji.
  - [x] Idempotensi dan pencegahan konflik transaksi pada unggahan beruntun.
  - [x] Integritas rendering seluruh komponen HTML laporan.
- [x] **E2E Pipeline Regression Test**: 50 / 50 kasus uji benchmark lulus via HTTP API $\to$ Worker $\to$ DB $\to$ HTML (`./artilik regression --cleanup`).
  - [x] Binary F1 Score = `1.0000`
  - [x] Macro F1 Score = `1.0000`
  - [x] Salah Klasifikasi = `0`
  - [x] Pembersihan data uji sementara bekerja otomatis.

---

## 🎨 4. Antarmuka Pengguna & Pengalaman Pengguna (UI/UX)

- [x] **Evidence Decomposition 4-Tier Layout**:
  - Tier 1: Badge Keputusan, Evidence Pattern, dan Source Agreement.
  - Tier 2: Side-by-side Dual-Lens Evidence Cards (Exact vs TF-IDF).
  - Tier 3: Penjelasan Ilmiah Transparan (*Why This Result?*).
  - Tier 4: Daftar Sumber Terdeteksi dan Pasase Terpanjang.
- [x] **Modal Interaktif `[View passage]`**:
  - Tampilan teks berdampingan (submisi vs dokumen rujukan).
  - Metadata pasase (panjang kata, posisi awal-akhir, max IDF).
  - Penutupan modal via tombol close, tombol `Escape`, dan klik backdrop.
- [x] **Penanganan Status Anggun (Graceful States)**:
  - *Loading State*: Kartu pemrosesan dengan animasi spinner dan polling otomatis (2.5 detik).
  - *Error State*: Kartu pesan kesalahan informatif jika job gagal.

---

## 📚 5. Paket Dokumentasi Lengkap (`docs/`)

- [x] `docs/RELEASE_NOTES.md`: Catatan rilis v1.0.0-RC1 dan kapabilitas sistem.
- [x] `docs/ARCHITECTURE.md`: Diagram alur pipeline end-to-end dan arsitektur Dual-Lens.
- [x] `docs/ALGORITHM.md`: Matematika detail Exact Matcher (5-gram shingling, FNV-1a) dan TF-IDF Sparse Cosine.
- [x] `docs/EVIDENCE_MODEL.md`: Spesifikasi 5 pola bukti (*evidence patterns*), rationale ilmiah, dan kontrak bukti.
- [x] `docs/BENCHMARK.md`: Komposisi dataset benchmark 50 kasus uji standar dan 50 kasus uji stres.
- [x] `docs/TESTING.md`: Log audit pengujian, matriks konfusi, dan catatan sertifikasi mutu.
- [x] `docs/PRODUCTION_READINESS.md`: Rincian 6 skenario edge case testing.
- [x] `docs/LIMITATIONS.md`: Batasan sistem, cakupan ilmiah, dan filosofi keadilan akademik.
- [x] `docs/REPRODUCIBILITY.md`: Panduan instalasi dan reproduksi pengujian mandiri.
- [x] `docs/CHECKLIST.md`: Lembar periksa kelayakan rilis GA.
- [x] `docs/PILOT_EVALUATION.md`: Laporan evaluasi pilot naskah tesis master riil 174 halaman.

---

## 🔬 6. Uji Coba Dokumen Riil (Real-World Pilot)

- [x] **Eksekusi Naskah Tesis Riil**: Memproses naskah tesis Magister Teologi Katolik 174 halaman (Emiliana Vivi, 2026).
- [x] **Observasi Performa Pemrosesan**: Durasi ekstraksi PDF 0.297s, pemrosesan worker 6.116s, total turnaround 7.07s untuk 34.400 kata.
- [x] **Evaluasi Kualitatif Bukti**: Terbukti bebas vonis keliru (Exact: 0.03%, Cosine: 18.82%, Pattern: `SEMANTIC_PARAPHRASE`, Keputusan: `REVIEW`). Integritas orisinalitas naskah terlindungi.
