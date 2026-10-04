# AR Tilik Evaluation Dataset v1 & Post-Release Human Annotation System

Dokumen ini mendokumentasikan metodologi, arsitektur, skema data, dan protokol pengujian **Evaluation Dataset v1** untuk **AR Tilik v1.0.0** (*Academic Writing Similarity & Evidence Checker*).

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

## 2. Struktur Kasus Evaluasi (Evaluation Case Schema)

Setiap kasus pengujian memiliki metadata standar tanpa informasi pribadi penulis:

```json
{
  "case_id": "EV-MUT-011",
  "case_type": "MUTATION",
  "source_case": "TC-011",
  "generation_method": "LEXICAL_EDIT",
  "submission_text": "...",
  "source_text": "...",
  "expected_class": "review",
  "expected_pattern": "COPY_EDIT",
  "human_validated": false,
  "system_version": "v1.0.0",
  "metadata": {
    "mutation_level": 1,
    "parent_category": "copy_edit"
  }
}
```

Untuk kasus dunia nyata yang belum berlabel:
```json
{
  "case_id": "EV-RW-001",
  "case_type": "REAL_WORLD",
  "source_case": "Report #476",
  "generation_method": "REAL_DOCUMENT",
  "expected_class": null,
  "human_validated": true,
  "system_version": "v1.0.0"
}
```

---

## 3. Komposisi 500 Kasus Uji

Evaluation Dataset v1 terdiri atas **500 kasus uji** yang dihasilkan secara terisolasi tanpa mencemari basis data produksi:

| Kategori | Jumlah Kasus | Deskripsi & Tujuan |
|---|---|---|
| **Seed Cases** | 50 | 50 kasus benchmark standar (Identical, Copy-Edit, Paraphrase, Common Phrase, Unrelated) untuk menguji konsistensi fondasi. |
| **Mutation Cases** | 250 | 50 seed $\times$ 5 operator mutasi: <br>• 50 Lexical Edits (Level 1: substitusi sinonim, kapitalisasi, tanda baca)<br>• 50 Deletion/Insertion (Level 1: penyisipan kualifikasi, delesi kata minor)<br>• 50 Restructuring (Level 2: penataan ulang kalimat, pemecahan klausul)<br>• 50 Paraphrase (Level 3: aktif-pasif, nominalisasi akademis)<br>• 50 Mixed Transformations (Kombinasi Level 1 + Level 2/3) |
| **Boundary Cases** | 100 | Pengujian batas threshold ($\pm 1$) pada konfigurasi v1.0.0:<br>• Exact 70% threshold (15 kasus)<br>• Exact 10% threshold (15 kasus)<br>• TF-IDF 85% cosine threshold (15 kasus)<br>• TF-IDF 10% cosine threshold (15 kasus)<br>• Longest match 50 kata threshold (15 kasus)<br>• Longest match 10 kata threshold (15 kasus)<br>• Significant IDF 0.50 threshold (10 kasus) |
| **Adversarial Cases** | 100 | Kasus tepi akademis yang mirip namun bukan plagiarisme langsung:<br>• 25 Methodology Boilerplate (*"Data dianalisis menggunakan metode deskriptif..."*)<br>• 25 Citation-Protected Overlap (*"... (Sugiyono, 2019: 145)"*)<br>• 25 Exact Quote Overlap (Kutipan langsung bertanda petik)<br>• 25 Terminology Overlap (Kepadatan jargon disiplin ilmu tanpa pasase identik) |
| **Total Otomatis** | **500** | Tersimpan di `evaluation_dataset_v1/cases.json` |

---

## 4. Guardrail Terhadap Synthetic F1

> [!CAUTION]
> **Larangan Penggabungan Metrik Sintetik**:
> Tingkat kelulusan pengujian sintetik (*synthetic test pass rate*) **TIDAK BOLEH** diklaim sebagai *production accuracy* atau *real-world F1 score*.
> Hasil evaluasi wajib dilaporkan secara terpisah berdasarkan pilar validasi masing-masing.

### Ambang Batas Kualitas (Quality Gates):
1. **Gate A — Mutation Robustness**:
   - Tingkat kelulusan $\ge 95\%$ dari perilaku yang diharapkan (*expected behavior*).
   - Hasil Aktual v1.0.0: **246 / 250 (98.4%) — LULUS**.
2. **Gate B — Boundary Determinism**:
   - Sistem wajib menghasilkan keputusan deterministik dan wajar pada ambang batas ($\ge 90\%$).
   - Hasil Aktual v1.0.0: **95 / 100 (95.0%) — LULUS**.
3. **Gate C — Adversarial Safety**:
   - **0 Unexpected MATCH** untuk kasus yang sengaja dirancang sebagai kutipan sah, metodologi umum, atau terminologi keilmuan.
   - Hasil Aktual v1.0.0: **0 Unexpected MATCH (97 Pass, 3 Fail, 0 MATCH) — LULUS**.
4. **Gate D — Real-World Agreement**:
   - Evaluasi kesepakatan peninjau manusia terhadap naskah dunia nyata (dimulai dari percontohan tesis Report #476).

---

## 5. Antrean Peninjauan Manusia (Prioritized Human Review Queue)

Alih-alih memeriksa seluruh 500 kasus, sistem secara cerdas menyeleksi **30–50 kasus paling informatif** berdasarkan hierarki 8 prioritas:

1. **Unexpected MATCH**: Kasus yang divonis MATCH padahal dirancang sebagai non-overlap.
2. **Unexpected NO_MATCH**: Kasus yang divonis NO_MATCH padahal memiliki pasase atau parafrase penting.
3. **Citation & Quote Overlap**: Pasase bersitasi resmi untuk menguji pemisahan bukti versus vonis.
4. **High TF-IDF + Exact ≈ 0%**: Kemiripan leksikal tinggi tanpa ada pasase identik (kasus parafrase murni atau jargon).
5. **Low Exact (< 10%) + High TF-IDF (> 40%)**: Kasus pembuktian dekomposisi bukti.
6. **Evidence Lens Divergence**: Kasus divergensi signifikansi (Exact tinggi namun IDF rendah pada frasa pasaran).
7. **Threshold Boundary Proximity**: Kasus tepat di sekitar batas 70%, 85%, atau 10%.
8. **Representative Random Sample**: Sampel acak terdistribusi.

### Format Pelabelan Manusia:
Reviewer akademis dapat membubuhkan tag bukti:
- `[ ] Relevant`: Pasase kemiripan substansial yang relevan untuk ditindaklanjuti.
- `[ ] Common phrase`: Frasa formula akademik standar (bukan plagiarisme).
- `[ ] Properly cited`: Teks disitir dengan etika akademik yang benar.
- `[ ] Needs investigation`: Memerlukan penelusuran lebih lanjut oleh komite etik.
- `[ ] False positive`: Bukan kesamaan yang bermakna.

Serta memberikan penilaian terhadap akurasi sistem (*system assessment*):
`CORRECT`, `ACCEPTABLE`, `TOO_STRICT`, `TOO_LENIENT`, atau `UNCERTAIN`.

---

## 6. Skema Basis Data Terisolasi

Data evaluasi pasca-rilis dikelola dalam 3 tabel mandiri tanpa menyentuh tabel produksi:

1. `post_release_evaluations`:
   Menyimpan snapshot metrik dual-lens v1.0.0 (Exact Sim, Cosine, Longest, IDF, Decision, Pattern).
2. `evaluation_evidence`:
   Menyimpan metrik bukti individual per lensa (`EXACT` vs `TFIDF`).
3. `evaluation_labels`:
   Menyimpan label peninjauan manusia, kode reviewer tanpa PII (misal: `RV-001`), dan catatan kualitatif.

---

## 7. Alur Kerja CLI

```bash
# 1. Menghasilkan 500 kasus evaluasi terisolasi
./artilik eval generate

# 2. Menjalankan engine AR Tilik v1.0.0 pada seluruh 500 kasus
./artilik eval run

# 3. Menampilkan antrean 35 kasus paling informatif untuk telaah manusia
./artilik eval review

# 4. Memeriksa satu kasus tertentu secara mendalam
./artilik eval review --inspect EV-ADV-026

# 5. Membubuhkan anotasi telaah manusia via CLI
./artilik eval review --tag EV-ADV-026 --decision review --label "Properly cited" --reviewer RV-001 --notes "Kutipan bersitasi resmi"
```
