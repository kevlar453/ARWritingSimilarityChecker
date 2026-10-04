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

---

## 8. Alur Kerja CLI

```bash
# 1. Menghasilkan 500 kasus evaluasi terisolasi dengan hash deduplikasi
./artilik eval generate

# 2. Menjalankan engine AR Tilik v1.0.0 pada seluruh 500 kasus
./artilik eval run

# 3. Menampilkan antrean kasus paling informatif untuk telaah manusia
./artilik eval review

# 4. Memeriksa rincian kasus spesifik (metrik dual-lens, teks, provenans)
./artilik eval review --inspect EV-ADV-026

# 5. Membubuhkan anotasi telaah manusia via CLI lengkap dengan konteks & interpretasi
./artilik eval review --tag EV-ADV-026 \
  --decision review \
  --label "Properly cited" \
  --context PARENTHETICAL_CITATION \
  --interpretation DIRECT_OVERLAP_PROPERLY_CITED \
  --assessment CORRECT \
  --reviewer RV-001 \
  --notes "Teks mengandung sitasi sah Sugiyono (2019)"

# 6. Mode telaah interaktif satu per satu
./artilik eval review --interactive
```
