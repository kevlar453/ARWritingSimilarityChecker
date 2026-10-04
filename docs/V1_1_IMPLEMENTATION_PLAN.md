# AR TILIK v1.1 — Implementation Plan

**Baseline:** AR TILIK v1.0.0 (FROZEN)  
**Dokumen Induk:** `V1_1_SPECIFICATION.md`  
**Status:** Approved / Active Sprint Plan  
**Fokus Utama:** Menambahkan lapisan interpretasi konteks sitasi (*Citation / Context Awareness*) dan pemisahan penyajian bukti (*Evidence-Type Source Grouping*) tanpa mengubah baseline deteksi atau aturan keputusan v1.0.0.

---

## 1. Posisi Arsitektur & Prinsip Utama

Dokumen ini menjadi jembatan operasional dari spesifikasi ke implementasi kode:

```text
V1_0_0_POST_RELEASE_EVALUATION.md (Baseline Dokumen & Bukti Operasional)
                    ↓
POST_RELEASE_FINDINGS_V1_1_BACKLOG.md (Temuan & Analisis Kebutuhan v1.1)
                    ↓
V1_1_SPECIFICATION.md (Spesifikasi Teknis & Non-Goals v1.1)
                    ↓
V1_1_IMPLEMENTATION_PLAN.md (Rencana Kerja Terstruktur WP-01 s.d. WP-08)
                    ↓
                 CODING
```

### Prinsip Inti
1. **v1.0.0 Baseline Is Frozen:** File `worker/hybrid.py`, threshold keputusan (`70%`, `10%`, `85%`, dll.), serta aturan klasifikasi v1.0.0 tidak boleh dimodifikasi.
2. **Context Enriches, Never Suppresses Evidence:** Kehadiran sitasi, kutipan langsung, atau tanda baca tidak boleh menekan, menghapus, atau menurunkan metrik kemiripan tekstual (`Exact` % atau `TF-IDF` %).
3. **Pemisahan Lapisan Bukti dan Keputusan:**
   - **Textual Evidence:** Metrik Exact sequential & TF-IDF cosine.
   - **Academic Context:** Atribusi sitasi, kutipan langsung, atau formulasi metodologis.
   - **System Decision:** MATCH / REVIEW / NO_MATCH tetap deterministik dan independen. Kehadiran sitasi tidak otomatis mengubah `REVIEW` menjadi `NO_MATCH`.
4. **Deterministic & Auditable:** Setiap ekstraksi konteks harus menyertakan span teks yang diobservasi (`context_span`) dan alasan eksplisit (`rationale`).
5. **Safe Fallback:** Bila parser tidak dapat memastikan konteks dengan keyakinan yang memadai, sistem harus menghasilkan `UNKNOWN`, bukan menebak status sitasi positif.

---

## 2. Struktur Workstream (WP-01 s.d. WP-08)

Rencana kerja v1.1 dipecah menjadi 8 work packages dengan prioritas dan dependensi yang ketat:

| Work Package | Fokus Utama | Prioritas | Dependensi | Deliverable Utama |
|---|---|---|---|---|
| **WP-01** | **Schema & Evidence Contract v1.1** | **P0** | V1_1_SPEC | `worker/contract_v1_1.py`, unit test fixtures |
| **WP-02** | **Citation / Context Detector** | **P0** | WP-01 | `worker/citation_detector.py`, parser regex/lexical |
| **WP-03** | **Evidence-Type Presentation & Grouping** | **P0** | WP-01, WP-02 | Dual-bucket visual grouping (Direct vs Lexical) |
| **WP-04** | **Targeted 80-Case Evaluation Dataset** | **P0** | WP-01, WP-02 | 80 ground-truth contextual test cases |
| **WP-05** | **Regression & Evaluation Runner** | **P1** | WP-03, WP-04 | Runner isolasi v1.1 vs v1.0.0 baseline |
| **WP-06** | **Expanded Human Validation Layer** | **P1** | WP-05 | Sampling & evaluasi reviewer manusia baru |
| **WP-07** | **Operational Observability & Latency** | **P2** | WP-03 | Metric logging (span parsing latency) |
| **WP-08** | **Documentation & Release Gate** | **P0** | WP-01 s.d. WP-07 | Release audit, verification record v1.1.0 |

---

## 3. Rincian Work Package & Acceptance Criteria

### WP-01: Schema & Evidence Contract v1.1 (P0) — *Current Sprint*
- **Tujuan:** Mendefinisikan kontrak data v1.1 yang memperluas kontrak bukti v1.0.0 dengan metadata konteks sitasi tanpa merusak kompatibilitas ke belakang (*backward compatibility*).
- **Komponen:**
  - Enums: `EvidenceType`, `CitationContext`, `CitationStatus`, `CitationConfidence`.
  - Data Class / TypedDict: `EvidenceContractV11`.
  - Serialisasi deterministik (`.to_dict()`).
  - Factory konversi dari v1.0.0 (`from_v1_0_contract()`).
  - Minimal 8 canonical test fixtures (Uncited, Parenthetical, Inline, Direct Quote, Block Quote, Footnote, Bibliography, Ambiguous/Unknown).
- **Acceptance Gate WP-01:**
  - Schema v1.1 terdokumentasi dan diimplementasikan di `worker/contract_v1_1.py`.
  - Baseline `worker/hybrid.py` tidak diubah sama sekali (`git diff worker/hybrid.py` kosong).
  - Serialisasi teruji deterministik dan bebas inkonsistensi.
  - Test suite baru `tests/test_evidence_contract_v1_1.py` PASS 100%.
  - Seluruh test regression existing `./artilik test` tetap PASS.

---

### WP-02: Citation / Context Detector (P0)
- **Tujuan:** Mengembangkan modul pendeteksi konteks yang dapat mengekstrak pola sitasi di sekitar pasase bukti secara deterministik.
- **Pola Deteksi Awal:**
  - *Parenthetical:* Pola nama penulis dan tahun, e.g. `(Smith, 2024)`, `(Rahman et al., 2023)`.
  - *Inline marker:* Format angka kurung siku atau numerik, e.g. `[1]`, `[Smith 2020]`.
  - *Direct quotation:* Tanda petik ganda/tunggal standar dan tipografis, e.g. `"..."`, `“...”`.
  - *Block quotation:* Pola indentasi/paragraf terisolasi bila tersedia.
  - *Footnote marker:* Superscript atau indeks numerik berurutan.
  - *Bibliography:* Bagian referensi/daftar pustaka di akhir dokumen.
- **Batasan:** Detector hanya membaca konteks di sekitar span bukti (*evidence-adjacent window*). Sitasi acak di halaman lain tidak boleh dihubungkan ke bukti yang tidak relevan.

---

### WP-03: Evidence-Type Presentation & Source Grouping (P0)
- **Tujuan:** Memisahkan penyajian sumber pada antarmuka web dan laporan PDF:
  ```text
  SOURCE EVIDENCE
  ├── 1. Direct Textual Overlap (Didukung oleh Exact Sequential Match)
  │      Tampilkan: skor Exact %, rentang pasase terpanjang, teks sumber
  │      Syarat: Hanya tampil jika Exact > 0%
  └── 2. Lexical / Topic Similarity (Didukung oleh TF-IDF Cosine Match)
         Tampilkan: skor Cosine %, kata kunci bersama, average IDF
  ```
- **Divergence Notice:** Jika Exact = 0% dan TF-IDF > 0%, tampilkan penegasan formal bahwa dokumen rujukan memiliki kesamaan topik/leksikal tanpa pasase teks berurutan.

---

### WP-04: Targeted 80-Case Evaluation Dataset (P0)
- **Tujuan:** Menyusun dataset terarah khusus untuk menguji akurasi deteksi sitasi dan pengelompokan bukti sebelum peluncuran v1.1.
- **Distribusi Kasus:**
  - 10 kasus *Uncited Direct Overlap*
  - 10 kasus *Parenthetical Citation*
  - 10 kasus *Inline Citation*
  - 10 kasus *Direct / Block Quote*
  - 10 kasus *Paraphrase with Citation*
  - 10 kasus *Methodology Boilerplate*
  - 10 kasus *High TF-IDF with Exact = 0%*
  - 10 kasus *Citation Unrelated to Overlap (Distractor)*
- **Total:** 80 kasus ground-truth beranotasi formal.

---

### WP-05: Regression & Evaluation Runner (P1)
- **Tujuan:** Menjalankan perbandingan ganda (*dual-run harness*) antara engine baseline v1.0.0 dan v1.1 untuk memverifikasi bahwa tidak ada degradasi klasifikasi pada dataset evaluasi yang ada (50 benchmark cases, 450 synthetic cases, 50 E2E cases).

---

### WP-06: Expanded Human Validation Layer (P1)
- **Tujuan:** Memperluas telaah manusia pada batch kasus baru pasca-implementasi WP-02 dan WP-03 untuk menguji apakah pemisahan Direct Textual Overlap vs Lexical Similarity dan metadata sitasi mempermudah telaah akademik.

---

### WP-07: Operational Observability & Latency Tracking (P2)
- **Tujuan:** Memastikan overhead parsing sitasi tidak menambah latensi secara signifikan (target overhead < 150ms per submission pada naskah reguler).

---

### WP-08: Documentation & Release Gate (P0)
- **Tujuan:** Finalisasi release audit, changelog v1.1.0, serta publikasi dokumentasi teknis.
- **Kriteria Kelulusan Akhir (Release Gate v1.1):**
  - Gate V11-A (Regression): 100% test v1.0.0 tetap passing.
  - Gate V11-B (Context Detection): Dataset 80 kasus terarah mencapai akurasi deteksi context $\ge 95\%$.
  - Gate V11-C (Evidence Traceability): Semua temuan konteks memiliki `context_span` dan `rationale`.
  - Gate V11-D (Zero Evidence Suppression): Metrik kemiripan tekstual murni tidak berubah karena keberadaan sitasi.
  - Gate V11-E (Interpretation Safety): Penegasan bahwa kemiripan bukanlah vonis otomatis pelanggaran akademik.

---

## 4. Rencana Kerja Sprint 1 (Fokus: WP-01)

Urutan eksekusi WP-01:
1. **Spesifikasi Modul Kontrak:** Buat `worker/contract_v1_1.py`.
2. **Definisi Enum Terkunci:**
   - `EvidenceType`: `DIRECT_TEXTUAL_OVERLAP`, `LEXICAL_TOPIC_SIMILARITY`, `COMMON_ACADEMIC_LANGUAGE`, `METHODOLOGY_BOILERPLATE`, `UNKNOWN`.
   - `CitationContext`: `NONE`, `INLINE_CITATION`, `PARENTHETICAL_CITATION`, `BLOCK_QUOTE`, `DIRECT_QUOTE`, `FOOTNOTE`, `BIBLIOGRAPHY`, `UNKNOWN`.
   - `CitationStatus`: `NOT_CITED`, `CITED`, `PARTIALLY_CITED`, `QUOTE`, `PARAPHRASED_WITH_CITATION`, `NOT_APPLICABLE`, `UNKNOWN`.
   - `CitationConfidence`: `HIGH`, `MEDIUM`, `LOW`, `UNKNOWN`.
3. **Implementasi Kelas Kontrak:**
   - `EvidenceContractV11`: Data class terstruktur dengan validasi tipe data.
   - Serialisasi `.to_dict()` deterministik dengan fallback `UNKNOWN`.
   - Factory `.from_v1_0()` yang menerima kamus `EvidenceContract` v1.0.0.
4. **Pembuatan 8 Unit Test Fixtures:**
   - File: `tests/test_evidence_contract_v1_1.py`.
   - Pengujian 8 skenario sitasi kanonikal beserta verifikasi backward-compatibility dan immutability metrik evidence.
5. **Verifikasi Gate WP-01:**
   - Eksekusi pytest untuk modul kontrak baru.
   - Eksekusi suite `./artilik test` (36 + test baru).
   - Verifikasi keutuhan `worker/hybrid.py`.
