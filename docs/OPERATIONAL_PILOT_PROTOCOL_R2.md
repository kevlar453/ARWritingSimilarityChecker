# AR Tilik v1.1.0 — Protokol Operasional Pilot & Pembuktian Lapangan (Phase R)

Dokumen ini menetapkan panduan metodologis, tata kelola evaluasi lapangan, pemisahan 3 lapisan bukti, instrumen sampling berbasis risiko, dan matriks KPI operasional pasca-rilis **AR Tilik v1.1.0**.

---

## 1. Status Siklus Hidup Sistem & Roadmap Pasca-Rilis

```text
                    AR TILIK v1.1.0
                         │
                  WP-01 ── WP-08 (SELESAI)
                         │
                  [RELEASE CLOSED]
                         │
              ┌──────────┴──────────┐
              │                     │
       OPERASIONAL PILOT       EVIDENCE LOG
       (Dokumen Nyata)        (Real-Time Telemetry)
              │                     │
              └──────────┬──────────┘
                         │
                 HUMAN VALIDATION
                  BERKALA / BLIND
                         │
                         ▼
               POST-RELEASE DATASET
               (100–120 Kasus Nyata)
                         │
                         ▼
                  v1.2 / v2.0 GATE
                  (Jika Ada Bukti Sistematis)
```

### Milestone Operasional (Phase R):
- [x] **Milestone R1 — Release Closure (SELESAI)**: Pembekuan v1.1.0, audit kelulusan Gate V11-A s.d. V11-J, publikasi *Release Notes* dan pemutakhiran repositori.
- [ ] **Milestone R2 — Operational Pilot (TAHAP SAAT INI)**: Penelaahan awal pada 20–30 dokumen submisi akademik nyata menggunakan sampling berbasis risiko.
- [ ] **Milestone R3 — First Production Evidence Review**: Perluasan populasi menjadi 50–100 kasus nyata untuk melacak pola sistematis.
- [ ] **Milestone R4 — Operational Baseline**: Penetapan baseline metrik lapangan (konteks, kesepakatan manusia, latensi, rate divergensi, rate UNKNOWN).
- [ ] **Milestone R5 — v1.2 Decision Gate**: Evaluasi bukti empiris apakah sistem tetap dipertahankan pada v1.1.x atau memerlukan peningkatan ke v1.2.

---

## 2. Kebijakan Pembekuan & 9 Larangan Tegas (Explicit Negatives)

Mulai fase ini, setiap keputusan teknis wajib didasarkan pada **bukti empiris operasional nyata**, bukan intuisi atau keinginan menaikkan skor uji laboratorium.

### 🚫 Daftar 9 Larangan Mutlak:
1. ❌ **Dilarang tuning threshold v1.0.0**: Seluruh ambang batas (*70.0% Exact, 10.0% Exact Review, 85.0% Cosine, 10.0% Cosine Review, 5-gram shingle*) berstatus **PERMANENTLY FROZEN**.
2. ❌ **Dilarang mengubah `worker/hybrid.py`** hanya untuk mengakomodasi kasus atau dokumen tertentu.
3. ❌ **Dilarang mengejar 100% kesesuaian keputusan pada synthetic targeted 80** melalui modifikasi engine. Mismatch sintetis mencerminkan batasan pemahaman semantik yang wajar, bukan defek engine.
4. ❌ **Dilarang menyebut 450/450 synthetic benchmark sebagai *"akurasi 100%"***. Angka tersebut adalah *synthetic test pass rate*.
5. ❌ **Dilarang menyebut 30/30 human review sebagai *"akurasi produksi universal"***. Angka tersebut adalah tingkat kesepakatan penelaah (*reviewer agreement rate*) pada sampel terprioritas.
6. ❌ **Dilarang memperlakukan detektor sitasi sebagai legitimasi otomatis bahwa kutipan *"sah"***. Detektor hanya mengenali pola teks, bukan vonis hak cipta atau etika sitasi.
7. ❌ **Dilarang mengubah keputusan `REVIEW` menjadi `NO_MATCH` secara otomatis** semata-mata karena ditemukannya sitasi.
8. ❌ **Dilarang menggabungkan label sintetis dengan ground truth penelaah manusia** menjadi satu angka rata-rata.
9. ❌ **Dilarang membuka kembali baseline v1.0.0**. Versi v1.0.0 adalah *reference implementation* yang tidak boleh diutak-atik.

---

## 3. Arsitektur 3 Lapisan Bukti (Three-Layer Separation)

Setiap laporan penelaahan dokumen produksi wajib mempertahankan isolasi ketat antara ketiga lapisan berikut:

```text
┌─────────────────────────────────────────────────────────────────────────────┐
│ LAPISAN A: MACHINE EVIDENCE (Fisik / Tekstual)                             │
│ - Putusan Engine Baseline: MATCH / REVIEW / NO_MATCH                        │
│ - Pola Bukti: VERBATIM, COPY_EDIT, SEMANTIC_PARAPHRASE, dsb.               │
│ - Skor Metrik Murni: Exact Similarity (%), TF-IDF Cosine (%)                │
│ - Pasase: Longest Match (kata), Matched Terms, Source Rank                  │
└──────────────────────────────────────┬──────────────────────────────────────┘
                                       │
┌──────────────────────────────────────▼──────────────────────────────────────┐
│ LAPISAN B: ACADEMIC CONTEXT (Metadata / Non-Suppressive)                    │
│ - Citation Context: INLINE, PARENTHETICAL, DIRECT_QUOTE, BLOCK_QUOTE, dll. │
│ - Citation Status: NOT_CITED, CITED, QUOTE, dll.                            │
│ - Evidence Type: Direct Textual Overlap vs Lexical / Topic Similarity       │
│ - Context Span & Auditable Rationale (penjelasan kemunculan pola)           │
│ - Divergence Notice (jika Exact ≈ 0% dan Cosine > 0%)                       │
└──────────────────────────────────────┬──────────────────────────────────────┘
                                       │
┌──────────────────────────────────────▼──────────────────────────────────────┐
│ LAPISAN C: HUMAN INTERPRETATION (Penilaian Substantif Dewan Penelaah)      │
│ - System Assessment: CORRECT / ACCEPTABLE / TOO_STRICT / TOO_LENIENT / UNCERTAIN
│ - Academic Interpretation:                                                  │
│   * DIRECT_OVERLAP_RELEVANT                                                 │
│   * COMMON_ACADEMIC_LANGUAGE                                                │
│   * METHODOLOGY_BOILERPLATE                                                 │
│   * NEEDS_INVESTIGATION                                                     │
│   * FALSE_POSITIVE                                                          │
│   * INSUFFICIENT_EVIDENCE                                                   │
│ - Reviewer Confidence & Catatan Kualitatif Ahli                            │
└─────────────────────────────────────────────────────────────────────────────┘
```

---

## 4. Metode Sampling Berbasis Risiko (Risk-Based Sampling)

Penelaahan manusia tidak dibebankan pada seluruh dokumen secara acak, melainkan difokuskan melalui sampling terarah pada area berisiko tinggi (*high-risk zones*).

### Matriks Target Sampling (Populasi Total Target Awal: 120 Kasus):

| Tier Risiko | Kategori Kasus | Definisi Risiko | Target Awal | Target Pilot (R2) |
|:---:|:---|:---|:---:|:---:|
| **P1** | **REVIEW / MATCH dengan Exact Tinggi + Not Cited** | Indikasi penyalinan verbatim tanpa atribusi yang jelas. | 20 | **5** |
| **P2** | **REVIEW dengan Exact Tinggi + Cited / Quote** | Pembuktian apakah deteksi tumpang tindih berizin dapat ditelaah dengan adil. | 20 | **5** |
| **P3** | **Divergensi: TF-IDF Tinggi + Exact ≈ 0%** | Kemiripan orientasi leksikal/topikal tanpa pasase kata langsung. | 20 | **5** |
| **P4** | **Methodology / Academic Boilerplate** | Penggunaan formulasi baku instrumen penelitian, rumus statistik, atau prosedur klise. | 20 | **5** |
| **P5** | **Edge / Boundary Cases** | Dokumen dengan skor kemiripan berada di sekitar batas threshold (9.5%–10.5% atau 84.0%–85.5%). | 20 | **5** |
| **P6** | **Control / Clean Submissions (`NO_MATCH`)** | Memverifikasi tidak adanya alarm palsu (*false alarms*) pada naskah orisinal. | 20 | **5** |
| **Total** | | | **120 Kasus** | **30 Kasus** |

---

## 5. Protokol Blind Human Review

Untuk menjaga objektivitas penilaian dan mencegah *confirmation bias*:
1. **Blind Presentation**: Penelaah hanya melihat teks submisi, teks rujukan, metrik bukti (Lapisan A), dan metadata konteks (Lapisan B). Label ekspektasi pengembang **disembunyikan secara absolut**.
2. **Kemandirian Putusan**: Penelaah menilai apakah sistem berperilaku adil dan masuk akal terhadap naskah akademik dengan opsi:
   - `CORRECT`: Sistem mengidentifikasi bukti dan pola secara tepat.
   - `ACCEPTABLE`: Sistem memberikan klasifikasi yang dapat dijustifikasi secara akademik, meskipun terdapat nuansa penafsiran kontekstual tambahan.
   - `TOO_STRICT`: Sistem menilai terlalu keras terhadap kemiripan yang semestinya wajar/berizin.
   - `TOO_LENIENT`: Sistem melewatkan tumpang tindih signifikan yang seharusnya ditandai.
   - `UNCERTAIN`: Bukti tidak cukup untuk menentukan sikap ilmiah.

---

## 6. Matriks KPI Multi-Dimensi Operasional

Menolak penggunaan angka tunggal (*single aggregate score* / F1 tunggal), AR Tilik mengevaluasi performa melalui 5 dimensi KPI independen:

```text
┌─────────────────────────────────────────────────────────────────────────────┐
│ 1. DETECTION QUALITY                                                        │
│    - Apakah pasase sekuensial dan kemiripan leksikal terdeteksi tanpa slip?│
│ 2. EVIDENCE DECOMPOSITION                                                   │
│    - Apakah Direct Overlap dan Lexical Similarity terpisah secara visual?   │
│ 3. CONTEXT DETECTION FIDELITY                                               │
│    - Apakah sitasi, tanda petik, dan format rujukan dikenali secara presisi?│
│    - Berapa tingkat kemunculan UNKNOWN context?                             │
│ 4. HUMAN USEFULNESS & AGREEMENT                                             │
│    - Berapa proporsi penilaian CORRECT + ACCEPTABLE oleh penelaah pakar?    │
│    - Apakah penelaah merasa terbantu oleh narasi 'WHY THIS RESULT'?         │
│ 5. ACADEMIC SAFETY & FAIRNESS                                               │
│    - Apakah sistem berhasil menghindari tuduhan palsu pelanggaran akademik? │
│    - Apakah metrik kemiripan murni tetap 100% terjaga (non-suppression)?   │
└─────────────────────────────────────────────────────────────────────────────┘
```

---

## 7. Kriteria Gerbang Keputusan v1.2 (Milestone R5)

Keputusan pengembangan sistem masa depan hanya diambil setelah data operasional dari 100–120 kasus nyata terkumpul lengkap:

```text
                    ANALISIS DATA OPERASIONAL NYATA
                                  │
          ┌───────────────────────┴───────────────────────┐
          │                                               │
Tidak Ditemukan Masalah Sistematis             Ditemukan Pola Masalah Sistematis
          │                                               │
          ▼                                               ▼
     TETAP v1.1.x                                  KLASIFIKASI AKAR MASALAH
(Status Quo Produksi)                                     │
                                        ┌─────────────────┼─────────────────┐
                                        ▼                 ▼                 ▼
                                    Konteks/UI       Observabilitas      Algoritma Engine
                                     (v1.1.x)           (v1.1.x)             (v1.2)
```

- **Jalur 1: Pertahankan v1.1.x (Status Quo)** jika tingkat kesepakatan penelaah $\ge 95\%$ dan tidak ditemukan anomali perilaku sistemik.
- **Jalur 2: Pembaruan Minor v1.1.x (Dokumentasi/UI/Konteks)** jika penelaah membutuhkan penyempurnaan regex sitasi, perbaikan kartu UI, atau penjelasan narasi.
- **Jalur 3: Pengembangan v1.2** HANYA diizinkan jika ditemukan bukti empiris bahwa algoritma inti `worker/hybrid.py` memiliki cacat fundamental yang tidak dapat diselesaikan melalui lapisan presentasi dan konteks.
