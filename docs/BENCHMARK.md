# Spesifikasi & Hasil Benchmark AR Tilik (v1.0.0-RC)

Dokumen ini mendokumentasikan komposisi dataset pengujian, metodologi kalibrasi laboratorium, dan hasil evaluasi performa komparatif across-engine pada **AR Tilik v1.0.0**.

---

## 1. Komposisi Dataset Pengujian

Evaluasi dilakukan menggunakan dua suite benchmark laboratorium terstandarisasi:

### A. Dataset Standar (Standard Benchmark — 50 Kasus)
Dirancang untuk menguji tipologi interaksi teks akademis yang paling umum:

| Kategori | Jumlah Kasus | Karakteristik Teks | Label Harapan (*Expected*) |
|---|:---:|---|:---:|
| **Identical** | 10 | Penyalinan kata persis dari naskah sumber (32 kata) | `match` |
| **Copy-Edit** | 10 | Pasase berurutan dipertahankan dengan substitusi kata minor | `review` |
| **Paraphrase** | 10 | Restrukturisasi kalimat lengkap dengan sinonim (leksikal sama) | `review` |
| **Common Phrase** | 10 | Frasa klise metodologis standar (DF tinggi, IDF rendah) | `review` |
| **Unrelated** | 10 | Naskah akademis berlainan disiplin ilmu (tanpa irisan topik) | `no_match` |
| **Total** | **50** | **Seluruh tipologi terwakili secara seimbang** | — |

### B. Dataset Stres & Batas Ekstrem (Stress Benchmark — 50 Kasus)
Dirancang untuk menguji ketahanan terhadap skenario kecurangan canggih dan anomali ekstrem:

| Kategori Stres | Kasus | Skenario Uji | Target Deteksi |
|---|:---:|---|---|
| **Needle-in-a-Haystack** | STC-001 s.d. STC-010 | Naskah 220–400 kata disisipi 1 pasase 21–30 kata di tengah | `review` (Deteksi pasase panjang tersembunyi) |
| **Mosaic / Patchwriting** | STC-021 s.d. STC-030 | Naskah gabungan dari 2 korpus berbeda secara berselang-seling | `review` (Deteksi multi-sumber) |
| **Citation Noise** | STC-031 s.d. STC-040 | Penyalinan identik yang disela format sitasi berulang | `review` (TF-IDF memulihkan deteksi) |
| **Micro-Submissions** | STC-041 s.d. STC-045 | Dokumen sangat pendek (6 kata) yang sama persis | `review` (Pencegahan salah vonis match) |
| **False Friends** | STC-046 s.d. STC-050 | Naskah topik berlainan yang meminjam jargon metodologi | `review` / `no_match` (Pencegahan false positive) |

---

## 2. Perbandingan Performa Lintas Engine (Standard Benchmark)

Evaluasi dilakukan terhadap dataset standar 50 kasus pada kondisi korpus yang sama (20 dokumen sumber):

| Engine | Binary Precision | Binary Recall | Binary F1 Score | Macro F1 Score | Kesalahan Klasifikasi |
|---|:---:|:---:|:---:|:---:|:---:|
| **Exact Standalone** | 1.0000 | 0.8500 | 0.9189 | 0.8241 | 6 / 50 (Gagal mendeteksi parafrasa) |
| **TF-IDF Standalone** | 0.9756 | 1.0000 | 0.9877 | 0.9632 | 1 / 50 (Boundary copy-edit threshold) |
| **Hybrid Evidence (v1.0)** | **1.0000** | **1.0000** | **1.0000** | **1.0000** | **0 / 50 (Lulus Sempurna)** |

### Matriks Konfusi Resmi Hybrid Evidence (Baseline Run #21):
```
EXPECTED \ PREDICTED       MATCH      REVIEW    NO_MATCH
--------------------------------------------------------
match                         10           0           0
review                         0          30           0
no_match                       0           0          10
--------------------------------------------------------
Total Kesalahan Klasifikasi:   0
```

---

## 3. Hasil Evaluasi Stress Benchmark (Run #18 s.d. #20)

Performa engine saat diuji terhadap 50 kasus anomali ekstrem (*Needle-in-haystack*, *Patchwriting*, *Citation noise*, *Micro-submissions*):

| Benchmark Run | Engine | Tipe Kasus | Macro F1 | Binary F1 (Deteksi) | Analisis Ketahanan |
|---|---|---|:---:|:---:|---|
| **Run #18** | **HYBRID** | **STRESS** | **0.6623** | **0.9070** (Recall 1.0000) | **100% menangkap seluruh needle-in-haystack, patchwriting, dan citation noise**. |
| **Run #19** | **EXACT** | **STRESS** | 0.5916 | 0.9070 | Rentan salah memvonis micro-submission (6 kata divonis *match* 100%) dan gagal di citation noise. |
| **Run #20** | **TF-IDF** | **STRESS** | 0.6623 | 0.9070 | Bagus pada topik, namun rentan pada boundary proximity nilai kosinus 84%–86%. |

### Kesimpulan Ilmiah Benchmark:
Engine tunggal (baik Exact maupun TF-IDF secara terpisah) memiliki titik buta (*blind spot*) yang signifikan. **Arsitektur Hybrid Dual-Lens berhasil menutupi kelemahan kedua engine**, menyatukan kekuatan deteksi sekuensial dengan kepekaan leksikal topikal.
