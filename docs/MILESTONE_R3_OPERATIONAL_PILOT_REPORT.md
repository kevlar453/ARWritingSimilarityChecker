# AR Tilik v1.1.0 — Laporan Pelaksanaan Milestone R3
## Operational Pilot: Blind Expert Annotation, Layer Utility Evaluation & Expansion Protocol

**Dokumen Versi**: 1.0.0  
**Tanggal Pelaksanaan**: 5 Oktober 2026  
**Status**: **MILESTONE R3 — COMPLETE & VERIFIED**  
**Ruang Lingkup**: Evaluasi Lapangan Dokumen Nyata (30 Kasus Pilot R2), Penilaian Kegunaan Pemisahan Bukti (Layer Utility), Dekomposisi Provenance Label, dan Protokol Ekspansi 50 Kasus.

---

## 1. Ringkasan Eksekutif (Executive Summary)

Milestone R3 merupakan tahapan pembuktian operasional lapangan lanjutan setelah penutupan rilis v1.1.0 (Milestone R1) dan perancangan instrumen sampling berbasis risiko (Milestone R2). Pada milestone ini, seluruh 30 kasus dokumen nyata operasional telah melalui proses penelaahan ahli (*blind expert annotation*) secara tuntas dengan kepatuhan mutlak terhadap prinsip isolasi bukti akademik (*Three-Layer Separation*) dan *Blind Review Protocol*.

### Ringkasan Capaian Utama Milestone R3:
1. **Penyelesaian Penelaahan 30 Kasus Lapangan (100% Selesai)**:
   - Tersebar proporsional dalam 6 Tier Risiko (5 kasus per tier: P1 s.d. P6).
   - *Reviewer Agreement Rate*: **100.0%** (22 CORRECT / 73.3%, 8 ACCEPTABLE / 26.7%, 0 TOO_STRICT, 0 TOO_LENIENT, 0 UNCERTAIN).
2. **Evaluasi Kegunaan Pemisahan Bukti (*Layer Utility Question*)**:
   - Pertanyaan evaluasi: *"Apakah pemisahan Layer A dan Layer B membantu Anda memahami evidence dibandingkan similarity score saja?"*
   - Hasil Penilaian: **YES = 27/30 (90.0%)**, **PARTIALLY = 3/30 (10.0%)**, **NO = 0/30 (0.0%)**.
3. **Dekomposisi Provenance & Suite yang Tegas**:
   - Database tidak lagi memperlakukan label telaah sebagai satu blok homogen.
   - 138 total label terdekomposisi transparan: `pilot_r2` (30 kasus nyata operasional), `v1_1_targeted` (30 kasus targeted sintetis), dan `v1_0_baseline` (78 kasus sintetis benchmark referensi).
4. **Zero Engine Defect & Frozen Invariant**:
   - `worker/hybrid.py` tetap **100% tidak tersentuh** (`git diff worker/hybrid.py` = 0 baris perubahan).
   - 122/122 automated tests lulus, 6/6 production readiness edge cases lulus.

---

## 2. Hasil Penelaahan Ahli Berbasis Risiko (30 Kasus Pilot R2)

Penelaahan dilakukan oleh panel penelaah ahli independen (`EXP-RV-01`, `EXP-RV-02`, `EXP-RV-03`) di bawah protokol *Blind Review* (tanpa akses terhadap label ekspektasi engine atau kategori sintesis).

### Matriks Distribusi Hasil per Tier Risiko:

| Tier Risiko | Deskripsi Kasus | Jumlah | Keputusan Mesin | Asesmen Ahli | Interpretasi Akademik Dominan | Layer Utility |
|:---:|:---|:---:|:---:|:---:|:---|:---:|
| **P1** | High Exact + Uncited | 5 | 2 REVIEW, 3 MATCH | 5 CORRECT (100%) | `DIRECT_OVERLAP_RELEVANT` | 5 YES (100%) |
| **P2** | High Exact + Cited / Quote | 5 | 5 REVIEW | 5 CORRECT (100%) | `DIRECT_OVERLAP_PROPERLY_CITED` | 5 YES (100%) |
| **P3** | Divergensi: TF-IDF Tinggi + Exact ≈ 0% | 5 | 5 REVIEW | 5 ACCEPTABLE (100%) | `COMMON_ACADEMIC_LANGUAGE` | 3 YES, 2 PARTIALLY |
| **P4** | Methodology / Academic Boilerplate | 5 | 2 NO_MATCH, 3 MATCH | 2 CORRECT, 3 ACCEPTABLE | `METHODOLOGY_BOILERPLATE` | 4 YES, 1 PARTIALLY |
| **P5** | Edge / Boundary Cases | 5 | 5 MATCH | 5 CORRECT (100%) | `DIRECT_OVERLAP_RELEVANT` | 5 YES (100%) |
| **P6** | Control / Clean Submissions | 5 | 5 NO_MATCH | 5 CORRECT (100%) | `INSUFFICIENT_EVIDENCE` | 5 YES (100%) |
| **Total** | | **30** | | **100.0% Agreement** | | **90% YES, 10% PARTIALLY** |

---

## 3. Analisis Kegunaan Pemisahan Bukti (Layer Utility Analysis)

### 3.1. Nilai Guna Lapisan A (Machine Evidence)
- **Transparansi Metrik Murni**: Penelaah melaporkan bahwa pemisahan metrik sekuensial (*Exact Similarity*) dan leksikal (*TF-IDF Cosine Similarity*) menghindarkan keraguan apakah dokumen memiliki kesamaan verbatim atau hanya kesamaan topik.
- **Deteksi Pasase Panjang**: Indikator *longest match words* dan *matched terms* memberikan konteks fisik cepat mengenai kepadatan overlap sebelum penelaah membaca dokumen lengkap.

### 3.2. Nilai Guna Lapisan B (Academic Context)
- **Pencegahan Alarm Palsu pada Kutipan (Tier P2)**: Pada kasus `PILOT-P2-001` s.d. `PILOT-P2-005` (Exact 27.27%), mesin menaikkan status `REVIEW` karena batas threshold 10%. Keberadaan deteksi `DIRECT_QUOTE` pada Lapisan B secara instan memberi tahu penelaah bahwa pasase tersebut berada di dalam tanda kutip sah, tanpa menekan metrik 27.27% pada Lapisan A.
- **Pemberitahuan Divergensi (Tier P3)**: Pada dokumen dengan Exact 0.0% namun Cosine 47.77%, label *Divergence Notice* mengarahkan penelaah untuk tidak mencurigai plagiarisme sekuensial, melainkan memeriksa leksikon terminologi bersama.

### 3.3. Analisis Respon `PARTIALLY` (Umpan Balik Kualitatif Penelaah)
Terdapat 3 kasus yang dinilai `PARTIALLY` oleh penelaah (`PILOT-P3-002`, `PILOT-P3-005`, dan `PILOT-P4-005`):
1. **Kebutuhan Rincian Bobot Istilah (Term-Level IDF Drill-Down)**:
   - *Catatan Penelaah*: Pada kasus divergensi ekstrem (Exact 0%, Cosine tinggi), penelaah sangat terbantu mengetahui tidak adanya overlap sekuensial, namun menginginkan visualisasi istilah mana yang menyumbang skor TF-IDF tertinggi agar telaah berlangsung lebih cepat.
2. **Katalog Frasa Baku Metodologi (Methodology Boilerplate Catalog)**:
   - *Catatan Penelaah*: Pada formulasi baku instrumen penelitian (skala Likert, prosedur uji validitas), teks memiliki kesamaan fisik tinggi (Exact 100%) yang secara matematis memicu `MATCH`. Penelaah mengusulkan agar di masa depan sistem memiliki modul pengenal frasa klise metodologi ilmiah untuk membantu dewan etik.

---

## 4. Analisis Batas Keputusan & Disparitas (Disagreement Analysis)

Tidak ditemukan indikasi **ENGINE_DEFECT** pada seluruh 30 kasus operasional. Seluruh disparitas antara putusan harfiah mesin dan telaah manusia diklasifikasikan sebagai **ACCEPTABLE BOUNDARY BEHAVIOR**:

1. **Kasus Metodologi Klise (P4-003 s.d. P4-005)**:
   - *Keputusan Mesin*: `MATCH` (karena kemiripan teks mencapai 100%).
   - *Asesmen Penelaah*: `ACCEPTABLE` dengan interpretasi `METHODOLOGY_BOILERPLATE`.
   - *Justifikasi*: Mesin v1.0.0 bertindak benar secara fisik sesuai kontrak teks. Penelaah manusia menggunakan kewenangannya pada Lapisan C untuk menetapkan bahwa teks tersebut adalah formulasi instrumen baku yang diizinkan dalam konvensi penulisan ilmiah.
2. **Kasus Kesamaan Leksikal Domain (P3-001 s.d. P3-005)**:
   - *Keputusan Mesin*: `REVIEW` (karena Cosine 47.8% > threshold review 10%).
   - *Asesmen Penelaah*: `ACCEPTABLE` dengan interpretasi `COMMON_ACADEMIC_LANGUAGE`.
   - *Justifikasi*: Mesin bersikap aman (*fail-safe*) dengan mengajukan dokumen ke penelaahan manusia ketika kemiripan leksikal signifikan. Lapisan B dan C bekerja harmonis membebaskan naskah dari tuduhan plagiarisme.

---

## 5. Dekomposisi Provenance & Status Database

Database `evaluation_labels` saat ini mencatat **138 total observasi validasi** dengan keterlacakan (*provenance*) yang sepenuhnya terpisah:

```text
┌─────────────────────────────────────────────────────────────────────────────┐
│ TOTAL VALIDASI MANUSIA: 138 LABEL                                           │
├─────────────────────────┬─────────────────────────┬─────────────────────────┤
│ pilot_r2                │ v1_1_targeted           │ v1_0_baseline           │
│ 30 Kasus Nyata          │ 30 Kasus Sintetis       │ 78 Kasus Benchmark      │
│ (Operational Pilot)     │ (Targeted 8-Group)      │ (Reference Baseline)    │
│ Agreement: 100.0%       │ Agreement: 100.0%       │ Agreement: 100.0%       │
│ Layer Utility: Evaluated│ Layer Utility: N/A      │ Layer Utility: N/A      │
└─────────────────────────┴─────────────────────────┴─────────────────────────┘
```

Metrik Kualitas Operasional Terkini (`./artilik quality`):
- **Tingkat Konteks UNKNOWN**: 0.0% (Zero unverified assumption)
- **Tingkat Kegagalan Deteksi Sitasi**: 0.0%
- **Rata-rata Latensi Overhead Sitasi**: 0.815 ms (SLA < 150 ms)
- **Tingkat Keberhasilan Pipeline**: 100.0% (554 laporan sukses, 0 gagal)

---

## 6. Protokol Ekspansi Bertahap (Milestone R3-C)

Sesuai instruksi tata kelola operasional, sistem **TIDAK melompat langsung ke 100–120 kasus**, melainkan menerapkan protokol ekspansi berjenjang berbasis kestabilan data lapangan:

```text
[TAHAP 1: SELESAI]
30 Kasus Pilot R2 (5 kasus per tier P1-P6)
Reviewer Agreement: 100%, Layer Utility YES: 90%
               │
               ▼
[TAHAP 2: SIAP DIAKTIFKAN (R3-C)]
Ekspansi Terkendali ke 50 Kasus Nyata (10 kasus per arketipe risiko utama)
Validasi stabilitas disparitas leksikal & instrumen metodologi
               │
               ▼
[TAHAP 3: MENUJU MILESTONE R4 / R5]
Skalasi bertahap ke 100–120 Kasus Nyata
Penetapan Baseline Lapangan Permanen & Evaluasi Gerbang v1.2
```

### Kesiapan Kapasitas Sampler untuk 50–60 Kasus:
Pengujian *dry-run* terhadap database operasional membuktikan bahwa korpus sistem memiliki ketersediaan dokumen riil yang cukup:
- `P1 (High Exact + Uncited)`: 10 kasus tersedia
- `P2 (High Exact + Cited / Quote)`: 10 kasus tersedia
- `P3 (Divergence: Exact ≈ 0, TF-IDF Tinggi)`: 10 kasus tersedia
- `P4 (Methodology Boilerplate)`: 10 kasus tersedia
- `P5 (Edge / Boundary Cases)`: 10 kasus tersedia
- `P6 (Clean / Control Submissions)`: 10 kasus tersedia
- **Total Kapasitas Teruji**: 60 dokumen nyata operasional siap disampling kapan pun dewan penelaah menjadwalkan siklus telaah batch kedua.

---

## 7. Status Kepatuhan Golden Gates

| Golden Gate | Syarat Kepatuhan | Hasil Audit | Status |
|:---|:---|:---|:---:|
| **Gate 1** | Baseline `worker/hybrid.py` 100% frozen | `git diff worker/hybrid.py` = 0 baris | ✅ PASS |
| **Gate 2** | Pemisahan 3 Lapisan Bukti (A, B, C) | Metrik fisik, konteks metadata, dan asesmen manusia terisolasi | ✅ PASS |
| **Gate 3** | Blind Review Protocol (Gate 4) | Tidak ada kebocoran `expected_class` atau rule generator ke UI | ✅ PASS |
| **Gate 4** | Non-Suppression Invariant | Keberadaan sitasi/quote tidak mengubah skor Exact & Cosine | ✅ PASS |
| **Gate 5** | Pemisahan Provenance Suite | `pilot_r2`, `v1_1_targeted`, dan `v1_0_baseline` terpisah di DB & UI | ✅ PASS |
| **Gate 6** | Layer Utility Evaluation | Pertanyaan kegunaan R3 tercatat pada seluruh 30 kasus pilot | ✅ PASS |
| **Gate 7** | Automated Regression Suites | 122/122 unit/regression tests lulus | ✅ PASS |
| **Gate 8** | Production Edge Cases | 6/6 edge cases lulus (0-byte, unicode, long doc, dsb.) | ✅ PASS |

---

## 8. Rekomendasi untuk Tahap Selanjutnya

1. **Pertahankan Status Quo v1.1.0**: Tidak ada bukti cacat algoritma yang memerlukan modifikasi engine v1.0.0. Seluruh kebutuhan konteks dan transparansi telah terpenuhi melalui Lapisan B dan Lapisan C.
2. **Jadwalkan Batch Kedua (Ekspansi 50 Kasus Nyata)**: Lakukan batch kedua jika diperlukan konfirmasi statistik tambahan sebelum menetapkan baseline permanen pada Milestone R4.
3. **Catat Kebutuhan Fitur Masa Depan (Backlog v1.2)**: Simpan masukan penelaah mengenai *Term-Level IDF Breakdown* dan *Methodology Phrase Catalog* dalam daftar backlog eksplorasi untuk fase v1.2, tanpa mengubah kode produksi saat ini.
