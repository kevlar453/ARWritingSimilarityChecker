# AR Tilik Evaluation Dataset v1 & Post-Release Human Annotation System

Dokumen ini mendokumentasikan metodologi, arsitektur, skema data, taksonomi kegagalan, dan protokol evaluasi **Evaluation Dataset v1** untuk **AR Tilik v1.0.0** (*Academic Writing Similarity & Evidence Checker*).

---

## 1. Pemisahan Konseptual: Corpus vs. Evaluation Dataset

Sistem AR Tilik membedakan secara tegas antara dua jenis kumpulan data:

```
                    ┌─────────────────────────┐
                    │      SOURCE CORPUS      │
                    │  (440 Koleksi Dokumen)  │
                    └────────────┬────────────┘
                                 │
                 Menjawab: "Dokumen ini mirip dengan sumber mana?"
                                 │
                                 ▼
                    ┌─────────────────────────┐
                    │     EVALUATION SUITE    │
                    │   (500 Kasus Evaluasi)  │
                    └────────────┬────────────┘
                                 │
                 Menjawab: "Apakah AR Tilik mengevaluasi dengan benar?"
```

1. **Source Corpus** (*Data Rujukan*):
   - Kumpulan dokumen repositori pembanding (artikel jurnal, skripsi, tesis, teks acuan institusi).
   - Berfungsi sebagai target pencarian bukti kemiripan (*similarity search targets*).
2. **Evaluation Dataset** (*Data Uji Tolok Ukur*):
   - Pasangan naskah submisi dan sumber dengan transformasi yang terukur dan diketahui (*ground truth*).
   - Berfungsi menguji ketahanan, stabilitas batas, dan kewajaran keputusan sistem.
   - **TIDAK PERNAH** dimasukkan ke dalam korpus pencarian agar tidak terjadi kontaminasi data (*data leakage*).

---

## 2. Struktur Kasus Evaluasi & Metadata Provenans

Setiap kasus pengujian memiliki metadata standar tanpa informasi pribadi penulis, dilengkapi dengan pelacak provenans dan integritas konten:

```json
{
  "case_id": "EV-MUT-104",
  "case_type": "MUTATION",
  "source_case": "TC-001",
  "generation_method": "MUTATION_RESTRUCTURING",
  "submission_text": "...",
  "source_text": "...",
  "expected_class": "review",
  "expected_pattern": "COPY_EDIT",
  "human_validated": false,
  "system_version": "v1.0.0",
  "mutation_operator": "RESTRUCTURING",
  "mutation_strength": "MEDIUM",
  "generation_seed": 104,
  "content_hash": "99343a3a4e635cb6",
  "failure_class": null,
  "metadata": {
    "mutation_level": 2,
    "parent_category": "identical"
  }
}
```

Bidang provenans kunci:
- `content_hash`: SHA-256 (16-char) dari submisi dan rujukan ternormalisasi untuk menjamin keunikan mutasi 100%.
- `mutation_operator` & `mutation_strength`: Operator transformasi yang diterapkan (`LEXICAL`, `DELETION_INSERTION`, `RESTRUCTURING`, `PARAPHRASE`, `MIXED`).
- `generation_seed`: Seed deterministik untuk reproduktifitas penuh.
- `failure_class`: Klasifikasi kegagalan berstandar Taksonomi Kegagalan formal.

---

## 3. Komposisi 500 Kasus Uji

Evaluation Dataset v1 terdiri atas **500 kasus uji** yang dihasilkan secara terisolasi tanpa mencemari basis data produksi:

| Kategori | Jumlah Kasus | Deskripsi & Tujuan |
|---|---|---|
| **Seed Cases** | 50 | 50 kasus benchmark standar (Identical, Copy-Edit, Paraphrase, Common Phrase, Unrelated) untuk menguji konsistensi fondasi. |
| **Mutation Cases** | 250 | 50 seed $\times$ 5 operator mutasi dengan deduplikasi ketat:<br>• 50 Lexical Edits (Level 1: substitusi sinonim, kapitalisasi, tanda baca)<br>• 50 Deletion/Insertion (Level 1: penyisipan kualifikasi, delesi kata minor)<br>• 50 Restructuring (Level 2: transposisi klausa independen & inversi konjungsi)<br>• 50 Paraphrase (Level 3: aktif-pasif, nominalisasi akademis)<br>• 50 Mixed Transformations (Kombinasi Level 1 + Level 2/3) |
| **Boundary Cases** | 100 | Pengujian batas threshold ($\pm 1$) pada konfigurasi v1.0.0:<br>• Exact 70% threshold (15 kasus)<br>• Exact 10% threshold (15 kasus)<br>• TF-IDF 85% cosine threshold (15 kasus)<br>• TF-IDF 10% cosine threshold (15 kasus)<br>• Longest match 50 kata threshold (15 kasus)<br>• Longest match 10 kata threshold (15 kasus)<br>• Significant IDF 0.50 threshold (10 kasus) |
| **Adversarial Cases** | 100 | Kasus tepi akademis yang mirip namun bukan plagiarisme langsung:<br>• 25 Methodology Boilerplate (*"Data dianalisis menggunakan metode deskriptif..."*)<br>• 25 Citation-Protected Overlap (*"... (Sugiyono, 2019: 145)"*)<br>• 25 Exact Quote Overlap (Kutipan langsung bertanda petik)<br>• 25 Terminology Overlap (Kepadatan jargon disiplin ilmu tanpa pasase identik) |
| **Total Otomatis** | **500** | Tersimpan di `evaluation_dataset_v1/cases.json` |

---

## 4. Taksonomi Kegagalan Formal (Failure Taxonomy)

Setiap anomali hasil evaluasi wajib diklasifikasikan ke dalam 5 taksonomi baku sebelum menyentuh kode engine:

```
                    ┌──────────────────────────────────┐
                    │     FAILURE TAXONOMY SYSTEM      │
                    └────────────────┬─────────────────┘
                                     │
         ┌───────────────────────────┼───────────────────────────┐
         ▼                           ▼                           ▼
[1] GENERATOR_ERROR         [2] EXPECTATION_ERROR       [3] BOUNDARY_BEHAVIOR
 • INVALID_MUTATION          • WRONG_EXPECTED_CLASS      • THRESHOLD_TRANSITION
 • DUPLICATE_MUTATION        • NON_REPRESENTATIVE_CASE
 • INSUFFICIENT_TRANSFORM
                                     │
         ┌───────────────────────────┴───────────────────────────┐
         ▼                                                       ▼
[4] ENGINE_LIMITATION                                   [5] ENGINE_DEFECT
 • NO_CONTEXT_AWARENESS                                  • Confirmed Bug
 • NO_CITATION_AWARENESS                                   (Currently: 0 DEFECT)
 • NO_SEMANTIC_CONTEXT
```

---

## 5. Rekam Diagnosis Kasus Informatif (5 Focus Cases)

Dalam audit post-release v1.0.0, dilakukan analisis mendalam terhadap 5 kasus reprezentatif:

| Kasus | Tipe | Metrik Mesin | Hasil Sistem | Diagnosis Taksonomi | Resolusi & Penjelasan |
|---|---|---|---|---|---|
| **EV-MUT-104** | MUTATION (RESTRUCTURING) | Exact: 67.7%, Longest: 11, Cosine: 80.8% | REVIEW (COPY_EDIT) | Awalnya `GENERATOR_ERROR` (Transposisi dangkal mempertahankan 100% shingle). | **Diperbaiki pada generator**: Menerapkan pemecahan klausa substantif dan deduplikasi hash. Lulus sebagai REVIEW. |
| **EV-MUT-105** | MUTATION (RESTRUCTURING) | Exact: 75.0%, Longest: 11, Cosine: 79.3% | REVIEW (COPY_EDIT) | Awalnya `GENERATOR_ERROR` (Duplikat variasi dengan mut-104). | **Diperbaiki pada generator**: Variasi unik dengan content hash berbeda. Lulus sebagai REVIEW. |
| **EV-ADV-005** | ADVERSARIAL (METHODOLOGY) | Exact: 0.0%, Longest: 0, Cosine: 15.2% | REVIEW (SEMANTIC_PARAPHRASE) | `EXPECTATION_ERROR:NON_REPRESENTATIVE_CASE` | Overlap metodologi standar (*Kolmogorov-Smirnov*). Sistem mendeteksi kemiripan leksikal topikal. Penilai manusia melabeli `METHODOLOGY_BOILERPLATE`. |
| **EV-ADV-026** | ADVERSARIAL (CITATION) | Exact: 28.9%, Longest: 11, Cosine: 30.1% | REVIEW (COPY_EDIT) | `ENGINE_LIMITATION:NO_CITATION_AWARENESS` | Overlap tekstual nyata (11 kata) dengan sitasi `(Sugiyono, 2019: 145)`. Sistem **CORRECT** mendeteksi overlap. Manusia melabeli `PROPERLY_CITED`. **Tidak diubah ke NO_MATCH** (bukti pemisahan bukti vs vonis; fitur backlog v1.1). |
| **EV-ADV-027** | ADVERSARIAL (CITATION) | Exact: 30.6%, Longest: 11, Cosine: 29.9% | REVIEW (COPY_EDIT) | `ENGINE_LIMITATION:NO_CITATION_AWARENESS` | Overlap tekstual nyata menyitir `(Davis, 1989)`. Sistem **CORRECT** mendeteksi overlap. Manusia melabeli `PROPERLY_CITED`. |

---

## 6. Guardrail Terhadap Synthetic F1 & Hasil Kualitas

> [!CAUTION]
> **Larangan Penggabungan Metrik Sintetik**:
> Tingkat kelulusan pengujian sintetik (*synthetic test pass rate*) **TIDAK BOLEH** diklaim sebagai *production accuracy* atau *real-world F1 score*.
> Hasil evaluasi wajib dilaporkan secara terpisah berdasarkan pilar validasi masing-masing.

### Hasil Quality Gates Terkini (v1.0.0 Frozen Engine):
1. **Gate A — Mutation Robustness**:
   - Ambang: Tingkat kelulusan $\ge 95\%$.
   - Hasil Aktual: **250 / 250 (100.0%) — LULUS**.
2. **Gate B — Boundary Determinism**:
   - Ambang: Keputusan deterministik ambang batas $\ge 90\%$.
   - Hasil Aktual: **100 / 100 (100.0%) — LULUS**.
3. **Gate C — Adversarial Safety**:
   - Ambang: **0 Unexpected MATCH** untuk kasus non-plagiarism.
   - Hasil Aktual: **100 / 100 (100.0%, 0 Unexpected MATCH) — LULUS**.
4. **Overall Synthetic Evaluation**:
   - **450 / 450 Pass (100.0%)**.
   - **Confirmed Engine Defects: 0 (ZERO DEFECT)**.

---

## 7. Skema Anotasi Manusia, Konteks Sitasi, & Interpretasi

Tabel basis data `evaluation_labels` mencatat keputusan manusia dengan pemisahan konteks sitasi dan interpretasi etika akademik:

### Pilihan Konteks Sitasi (`citation_context`):
- `NONE`: Teks tidak mengandung indikasi sitasi.
- `INLINE_CITATION`: Sitasi langsung dalam kalimat (*"Menurut Rahman (2020)..."*).
- `PARENTHETICAL_CITATION`: Sitasi dalam kurung (*"... (Sugiyono, 2019: 145)"*).
- `BLOCK_QUOTE`: Blok kutipan terpisah (> 4 baris / indent khusus).
- `DIRECT_QUOTE`: Kutipan langsung dalam tanda petik ganda.
- `FOOTNOTE`: Catatan kaki.
- `BIBLIOGRAPHY`: Daftar pustaka / referensi.
- `UNKNOWN`: Format tidak terdefinisi.

### Pilihan Interpretasi Akademik (`human_interpretation`):
- `DIRECT_OVERLAP_RELEVANT`: Overlap nyata dan relevan untuk ditindaklanjuti.
- `DIRECT_OVERLAP_PROPERLY_CITED`: Overlap nyata namun disitir dengan etika yang benar.
- `COMMON_ACADEMIC_LANGUAGE`: Frasa konvensi ilmiah lazim (*"Berdasarkan hasil penelitian yang telah dilakukan..."*).
- `METHODOLOGY_BOILERPLATE`: Uraian teknis/metodologis standar (*"Uji normalitas menggunakan Kolmogorov-Smirnov..."*).
- `NEEDS_INVESTIGATION`: Memerlukan pemeriksaan naskah asli oleh komite.
- `FALSE_POSITIVE`: Kesamaan artefak statistik tanpa kesamaan substansi.
- `INSUFFICIENT_EVIDENCE`: Bukti tidak cukup untuk memvalidasi klaim kesamaan.

### Pilihan Status Atribusi Sitasi (`citation_status`):
- `NOT_CITED`: Tidak ditemukan atribusi rujukan.
- `CITED`: Rujukan dicantumkan secara lengkap.
- `PARTIALLY_CITED`: Rujukan dicantumkan sebagian atau tidak lengkap.
- `QUOTE`: Pasase dikutip langsung menggunakan tanda petik.
- `PARAPHRASED_WITH_CITATION`: Parafrase dengan penyertaan sitasi sah.
- `NOT_APPLICABLE`: Bagian naskah tidak membutuhkan sitasi.

### Pilihan Penilaian terhadap Sistem (`system_assessment`):
- `CORRECT`: Keputusan dan bukti sistem tepat secara akademik.
- `ACCEPTABLE`: Keputusan sistem masih dapat diterima dalam batas toleransi.
- `TOO_STRICT`: Sistem menilai terlalu ketat (misal menuduh frasa umum).
- `TOO_LENIENT`: Sistem menilai terlalu longgar (lolos overlap signifikan).
- `UNCERTAIN`: Penelaah belum dapat menentukan kepastian.

### Pilihan Keyakinan Penelaah (`reviewer_confidence`):
- `HIGH`: Keyakinan tinggi didukung bukti kuat.
- `MEDIUM`: Keyakinan sedang dengan sedikit ambiguitas kontekstual.
- `LOW`: Keyakinan rendah, membutuhkan second opinion pakar bidang.

---

## 8. Prioritized Human Review Set (Phase HR-1: 30 Kasus Terkurasi)

Untuk menghindari kejenuhan penelaah (*reviewer fatigue*) dan memaksimalkan nilai diagnostik, fase HR-1 memilih **tepat 30 kasus prioritas tinggi** yang dibagi rata ke dalam **6 Tier Prioritas** (5 kasus per tier):

| Tier Prioritas | Target | Fokus Evaluasi & Kasus Kunci |
|---|---|---|
| **Tier 1 — Edge Cases / Former Failures** | 5 | Kasus tepi dari evaluasi awal (`EV-MUT-104`, `EV-MUT-105`, `EV-ADV-005`, `EV-ADV-026`, `EV-ADV-027`). |
| **Tier 2 — High Exact + Citation / Quote** | 5 | Kasus dengan exact overlap tinggi namun berstatus sitasi sah atau kutipan bertanda petik (`EV-ADV-028` s/d `030`, `EV-ADV-051`, `052`). |
| **Tier 3 — High TF-IDF + Exact ≈ 0** | 5 | Kasus dengan kemiripan topik/jargon tinggi tanpa overlap sekuensial (`EV-ADV-076` s/d `080`). |
| **Tier 4 — Boundary Cases** | 5 | Kasus uji tepat di sekitar ambang batas konfigurasi v1.0.0 (`EV-BND-001`, `016`, `031`, `046`, `061`). |
| **Tier 5 — Semantic Paraphrase** | 5 | Kasus benchmark parafrase semantik (`EV-SEED-021` s/d `025`). |
| **Tier 6 — Random Production / Control** | 5 | Kasus kontrol baseline (verbatim clone, copy-edit, frasa umum, disiplin berbeda: `EV-SEED-001`, `011`, `031`, `041`, `005`). |

### 4 Human Review UI Gates (HR-UI Gates 1–4):
1. **Gate 1: Zero Engine Modifications** — Engine v1.0.0, ambang batas, dan Evidence Contract berstatus **FROZEN**. Tidak ada modifikasi kode deteksi.
2. **Gate 2: Read Existing System Evidence Only** — Antarmuka hanya membaca bukti sekuensial dan leksikal yang dihasilkan sistem secara deterministik.
3. **Gate 3: Separate Storage** — Anotasi manusia tersimpan terisolasi di tabel `evaluation_labels` dan `human_reviews.json`, tanpa mencampuri tabel produksi (`documents`, `reports`).
4. **Gate 4: Blind Review** — Label sintetis buatan generator (`expected_class`, `expected_pattern`) disembunyikan sepenuhnya dari penelaah untuk mencegah bias konfirmasi (*confirmation bias*).

---

## 9. Antarmuka Web Telaah Manusia (/evaluation)

Sistem telaah manusia terintegrasi penuh melalui tiga antarmuka web:

1. **Antrean Telaah (`GET /evaluation`)**:
   - Menampilkan tabel 30 kasus prioritas dengan filter status telaah (*Semua, Selesai, Menunggu*), filter pola sistem, dan filter tier prioritas.
   - Dilengkapi kartu statistik total kasus, progres penyelesaian, dan indikator Blind Review.
2. **Detail Telaah Kasus (`GET /evaluation/:case_id`)**:
   - **Dual-Pane Text Excerpt**: Perbandingan berdampingan antara naskah submisi dan naskah rujukan.
   - **Kartu Hasil Sistem**: Keputusan sistem (MATCH/REVIEW/NO_MATCH), pola bukti, skor Exact dan TF-IDF, panjang pasase cocok, bobot IDF, dan pertimbangan sistem.
   - **Formulir Telaah 6 Dimensi**: Pilihan radio button untuk *Human Interpretation*, *Citation Context*, *Citation Status*, *System Assessment*, *Reviewer Confidence*, dan catatan bebas penelaah.
   - Navigasi cepat: Tombol *"Simpan & Kasus Berikutnya"*, *"Sebelumnya"*, dan *"Berikutnya"*.
3. **Ringkasan Hasil Telaah (`GET /evaluation/summary`)**:
   - Visualisasi ringkasan penyelesaian dan tingkat keselarasan (*Human-System Agreement Rate*).
   - Grafik distribusi untuk seluruh 5 dimensi evaluasi.
   - Tabel pemantauan progres per tier (6 tier).
   - Kartu wawasan empiris (*Diagnostic Insights*) sebagai landasan backlog v1.1.
   - Tabel rincian seluruh kasus yang telah selesai ditelaah.

---

## 10. Alur Kerja CLI

```bash
# 1. Menghasilkan 500 kasus evaluasi terisolasi dengan hash deduplikasi
./artilik eval generate

# 2. Menjalankan engine AR Tilik v1.0.0 pada seluruh 500 kasus
./artilik eval run

# 3. Menampilkan antrean 30 kasus prioritas untuk telaah manusia
./artilik eval review

# 4. Memeriksa rincian kasus spesifik (metrik dual-lens, teks, provenans)
./artilik eval review --inspect EV-ADV-026

# 5. Alur telaah interaktif CLI 30 kasus prioritas (Phase HR-1)
./artilik eval human-review [--reviewer RV-001]

# 6. Membubuhkan anotasi telaah manusia spesifik via CLI
./artilik eval review --tag EV-ADV-026 \
  --decision review \
  --context PARENTHETICAL_CITATION \
  --interpretation DIRECT_OVERLAP_PROPERLY_CITED \
  --assessment CORRECT \
  --reviewer RV-001 \
  --notes "Teks mengandung sitasi sah Sugiyono (2019)"
```

