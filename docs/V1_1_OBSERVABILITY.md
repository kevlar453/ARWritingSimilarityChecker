# AR Tilik v1.1 — Operational Observability & Latency Tracking (WP-07)

Dokumen ini merangkum arsitektur, hasil pengujian empiris, profil latensi, kepatuhan SLA, dan spesifikasi telemetri terstruktur untuk **Work Package 07 (WP-07: Operational Observability & Latency Tracking)** pada sistem **AR Tilik v1.1**.

---

## 1. Ringkasan Eksekutif & Sasaran WP-07

Tujuan utama dari WP-07 adalah memastikan bahwa penambahan lapisan kesadaran konteks akademik (Citation Context Awareness) dan pemisahan presentasi bukti (Evidence Presentation Layer) pada v1.1 **tidak menimbulkan regresi performa atau beban komputasi signifikan** terhadap pipeline produksi yang telah berjalan.

### Sasaran Kunci:
1. **Target SLA Latensi Parsing Sitasi**: Overhead komputasi ekstraksi konteks dan sitasi harus berada di bawah **150 ms** per naskah (`overhead < 150ms`), bahkan pada dokumen panjang reguler (sampai 3.000–5.000 kata).
2. **Kalkulasi Persentil Valid Secara Statistik**: Menyediakan pengukuran latensi presisi operasional ($p50$, $p90$, $p95$, $p99$) yang dilengkapi guardrail ukuran sampel statistik ($n \ge 30$).
3. **Audit Invarian Non-Suppression**: Memverifikasi secara deterministik secara *real-time* bahwa lapisan konteks tidak pernah mengubah atau menekan metrik *Exact Sequential* dan *TF-IDF Cosine* murni dari engine v1.0.0.
4. **Telemetri Terstruktur (Structured JSON Lines)**: Mencatat setiap event pemrosesan dokumen ke berkas log audit `writable/logs/v1_1_observability.log`.
5. **Dashboard & CLI Observabilitas Terpadu**: Terintegrasi pada `./artilik observability` dan `./artilik metrics`.

---

## 2. Arsitektur Observabilitas & Alur Data

```text
[Dokumen Submisi]
       │
       ▼
[Ekstraksi Teks & Normalisasi] ── (Catat extraction_latency)
       │
       ▼
[Worker Hybrid Engine v1.0.0 (FROZEN)] ── (Catat engine_execution_time)
  ├── Exact Sequential Matcher (5-gram)
  └── TF-IDF Topical Vectorizer
       │
       ▼
[v1.1 Citation & Context Detector] ── (Catat citation_parsing_ms ── Target SLA < 150ms)
       │
       ▼
[v1.1 Evidence Presenter & Grouping] ── (Catat presentation_ms)
  ├── Group 1: Direct Textual Overlap
  ├── Group 2: Lexical / Topic Similarity
  └── Divergence Alert Generator
       │
       ▼
[Non-Suppression & Invariance Verifier] ── (Verifikasi 100% exact & cosine identik)
       │
       ├──► Simpan ke Database: reports.evidence_data (JSON v1.1 enriched)
       └──► Stream Telemetri: writable/logs/v1_1_observability.log (JSON Lines)
```

### Prinsip Keamanan & Non-Interferensi:
- **Zero Engine Modification**: Modul `worker/hybrid.py` dan seluruh threshold baseline v1.0.0 berada dalam status **100% FROZEN** (`git diff worker/hybrid.py` tetap kosong).
- **Failsafe Telemetry**: Lapisan observabilitas dibungkus blok proteksi toleran-kesalahan (*graceful degradation*), memastikan bahwa anomali pencatatan log tidak akan pernah menggagalkan penyimpanan laporan atau penyelesaian job worker.

---

## 3. Benchmark Empiris & Kepatuhan SLA Latensi

Pengujian benchmark mikro-latensi dijalankan menggunakan `benchmark_citation_overhead()` yang menguji dokumen dari skala mikro (34 kata) hingga skala monograf/bab artikel ilmiah (4.998 kata) dengan 10 iterasi per panjang teks.

### Tabel Hasil Benchmark Mikro:

| Panjang Teks (Kata) | Iterasi | Rata-rata (ms) | Min (ms) | Max (ms) | Penggunaan Budget SLA (< 150ms) | Status SLA |
|:--------------------|:-------:|:--------------:|:--------:|:--------:|:-------------------------------:|:----------:|
| **34 kata** (Mikro) | 10 | 0.1549 ms | 0.1322 ms | 0.2811 ms | 0.10% | ✅ **PASS** |
| **238 kata** (Paragraf) | 10 | 1.0478 ms | 0.8790 ms | 1.2825 ms | 0.70% | ✅ **PASS** |
| **493 kata** (1 Halaman) | 10 | 1.7823 ms | 1.5575 ms | 2.1872 ms | 1.19% | ✅ **PASS** |
| **986 kata** (2 Halaman) | 10 | 3.5879 ms | 3.1911 ms | 4.2341 ms | 2.39% | ✅ **PASS** |
| **2.992 kata** (~10 Halaman) | 10 | 12.3594 ms | 11.1618 ms | 14.4252 ms | 8.24% | ✅ **PASS** |
| **4.998 kata** (~18 Halaman) | 10 | 21.5606 ms | 20.4242 ms | 22.3757 ms | 14.37% | ✅ **PASS** |

### Analisis Kepatuhan:
- Pada teks dokumen reguler (500–1.000 kata), overhead deteksi sitasi hanya memerlukan **1.78 ms hingga 3.58 ms**, menggunakan **kurang dari 2.5%** dari batas toleransi SLA (150 ms).
- Pada naskah masif 5.000 kata, rata-rata overhead tetap di angka **21.56 ms**, menyisakan *safety margin* sebesar **85.63%**.
- **Kesimpulan SLA**: Target SLA `< 150 ms` tercapai dengan tingkat kepatuhan **100% (PASS)**.

---

## 4. Distribusi Persentil Latensi Operasional

Berdasarkan populasi gabungan pengujian *targeted evaluation* 80 kasus dan eksekusi submisi *production readiness* ($n = 86$ sampel terverifikasi):

| Metrik Statistik | Latensi Ekstraksi Sitasi (`citation_parsing_ms`) | Latensi Total Pipeline (`request_to_report`) |
|:---|:---:|:---:|
| **Ukuran Sampel ($n$)** | **86 kasus** | **193 kasus** |
| **Rata-rata (Mean)** | **0.2334 ms** | **94.19 ms (0.094 detik)** |
| **Min Latensi** | 0.0110 ms | 17.00 ms |
| **Max Latensi** | 11.1818 ms | 6.116.00 ms |
| **Persentil 50 ($p50$ / Median)** | **0.0997 ms** | **25.00 ms** |
| **Persentil 90 ($p90$)** | 0.1441 ms | 258.00 ms |
| **Persentil 95 ($p95$)** | **0.1860 ms** | **264.00 ms** |
| **Persentil 99 ($p99$)** | 11.1818 ms | 277.00 ms |
| **Status Validitas Statistik** | **VALID ($n \ge 30$)** | **VALID ($n \ge 30$)** |

> [!NOTE]
> Latensi $p95$ untuk deteksi sitasi operasional tercatat pada **0.186 ms**, membuktikan bahwa implementasi pola ekspresi reguler deterministik dan inspeksi *span* bersifat instan dan tidak membebani siklus I/O sistem.

---

## 5. Distribusi Konteks & Status Sitasi Operasional

Dari populasi kasus terarah dan submisi yang telah diproses sistem:

### 1. Distribusi Konteks Sitasi (Citation Context)
- `NONE`: 35 kasus (40.7%)
- `PARENTHETICAL_CITATION`: 28 kasus (32.6%)
- `INLINE_CITATION`: 12 kasus (13.9%)
- `DIRECT_QUOTE`: 7 kasus (8.1%)
- `BLOCK_QUOTE`: 4 kasus (4.7%)
- `FOOTNOTE`: 0 kasus (0.0%)
- `BIBLIOGRAPHY`: 0 kasus (0.0%)
- `UNKNOWN`: 0 kasus (0.0%)

### 2. Distribusi Status Atribusi (Citation Status)
- `NOT_CITED`: 35 kasus (40.7%)
- `CITED`: 40 kasus (46.5%)
- `QUOTE`: 11 kasus (12.8%)
- `PARTIALLY_CITED`: 0 kasus (0.0%)
- `PARAPHRASED_WITH_CITATION`: 0 kasus (0.0%)
- `NOT_APPLICABLE`: 0 kasus (0.0%)
- `UNKNOWN`: 0 kasus (0.0%)

### 3. Divergensi Bukti (Evidence Divergence Tracking)
- **Insiden Divergensi**: 155 laporan ($31.0\%$) menunjukkan kondisi `Exact ≈ 0.0%` dengan `TF-IDF Cosine > 0.0%`.
- **Interpretasi**: Sistem v1.1 berhasil memisahkan kasus-kasus kemiripan leksikal/topikal murni ini dari tuduhan penjiplakan sekuensial verbatim secara transparan melalui *divergence alert*.

---

## 6. Audit Invarian Non-Suppression & Decision Invariance

Sesuai prinsip arsitektur sistem AR Tilik v1.1, kehadiran lapisan konteks sitasi tidak boleh menekan angka kemiripan ataupun mengubah klasifikasi keputusan sistem v1.0.0.

- **Jumlah Sampel Diaudit**: 86 / 86 kasus.
- **Tingkat Verifikasi Non-Suppression**: **100.0% (86/86)**.
- **Tingkat Verifikasi Decision Invariance**: **100.0% (86/86)**.
- **Status Jaminan Invarian**: **TERJAMIN (GUARANTEED)**.

---

## 7. Skema Telemetri Event Terstruktur (JSON Lines)

Setiap event pemrosesan dicatat ke berkas `writable/logs/v1_1_observability.log` dengan skema berikut:

```json
{
  "timestamp": "2026-10-04T19:16:15.127991+00:00",
  "event_type": "SIMILARITY_CHECK_COMPLETED",
  "report_id": 1009,
  "document_id": 2494,
  "engine": "hybrid",
  "predicted_decision": "review",
  "exact_similarity": 0.0,
  "cosine_similarity": 20.14,
  "duration_seconds": 0.253,
  "citation_parsing_ms": 11.1818,
  "sla_passed": true,
  "context_type": "NONE",
  "citation_status": "NOT_CITED",
  "has_divergence": true,
  "non_suppression_verified": true,
  "decision_invariance_verified": true
}
```

---

## 8. CLI Tooling & Integrasi Sistem

Pengguna atau administrator sistem dapat memantau observabilitas v1.1 secara langsung melalui CLI:

```bash
# 1. Menampilkan ringkasan audit observabilitas operasional & quality gates
./artilik observability

# 2. Menjalankan mikro-benchmark latensi lintas ukuran dokumen
./artilik observability --benchmark

# 3. Mengekstrak ringkasan metrik dalam format JSON terstruktur untuk scraping/monitoring
./artilik observability --json

# 4. Menampilkan metrik baseline sistem beserta seksi [7] observabilitas v1.1
./artilik metrics
```

---

## 9. Kesimpulan & Penandatanganan Quality Gates WP-07

Berdasarkan pengujian ekstensif, benchmark mikro hingga 5.000 kata, verifikasi persentil valid secara statistik ($n \ge 30$), serta audit non-suppression 100%:

| Gate ID | Kriteria Kelulusan | Nilai Aktual | Status |
|:---|:---|:---:|:---:|
| **Gate V11-SLA** | Overhead ekstraksi sitasi $< 150.0 \text{ ms}$ | **0.2334 ms (p95: 0.186 ms)** | ✅ **PASS** |
| **Gate V11-NON_SUPPRESSION** | Invarian metrik kemiripan $100\%$ terjaga | **100.0% (86/86)** | ✅ **PASS** |
| **Gate V11-DECISION_INV** | Keputusan engine v1.0.0 $100\%$ invarian | **100.0% (86/86)** | ✅ **PASS** |
| **Gate V11-TELEMETRY** | Format log terstruktur JSON Lines valid & failsafe | **Tervalidasi (100%)** | ✅ **PASS** |
| **Gate V11-REGRESSION** | Zero modification pada `worker/hybrid.py` | **0 diff lines** | ✅ **PASS** |

WP-07 resmi dinyatakan **PASS**. Sistem kini siap melangkah ke tahap akhir: **WP-08: Documentation, Release Gate & Packaging v1.1.0**.
