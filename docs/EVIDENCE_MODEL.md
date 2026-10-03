# Model Dekomposisi Bukti AR Tilik (v1.0.0-RC)

Dokumen ini mendefinisikan kontrak data bukti (*Evidence Contract*), taksonomi pola bukti (*Evidence Patterns*), indikator kesepakatan sumber (*Source Agreement*), dan panduan penelaahan akademik (*Reviewer Guidelines*).

---

## 1. Kontrak Bukti Terstandarisasi (`EvidenceContract`)

Setiap mesin analitik dalam AR Tilik menghasilkan objek bukti yang dinormalisasi ke dalam tipe data baku `EvidenceContract` (`worker/hybrid.py`):

```python
class EvidenceContract(TypedDict):
    engine: str                  # 'exact' | 'tfidf'
    similarity: float            # 0.0 - 100.0%
    matched_terms: int           # exact: jumlah kata cocok; tfidf: distinct shared terms
    longest_match: Optional[int] # exact: kata sekuensial terpanjang; tfidf: None
    source_count: int            # jumlah dokumen sumber terdeteksi
    match_count: int             # jumlah segmen / pasase
    avg_idf: Optional[float]     # rata-rata signifikansi IDF istilah beririsan
    max_idf: Optional[float]     # signifikansi IDF maksimum istilah beririsan
    top_sources: list[dict]      # daftar 5 sumber teratas yang paling relevan
```

Dengan kontrak ini, mesin pendeteksi baru di masa depan dapat ditambahkan tanpa merusak lapisan antarmuka atau aturan agregasi.

---

## 2. Taksonomi Pola Bukti (Evidence Patterns)

AR Tilik mengidentifikasi 5 pola bukti tekstual yang memiliki arti berbeda dalam konteks karya ilmiah:

```
┌────────────────────────────────────────────────────────────────────────┐
│                        KARYA ILMIAH SUBMISI                            │
└───────────────────────────────────┬────────────────────────────────────┘
                                    │
       ┌────────────────────────────┼────────────────────────────┐
       ▼                            ▼                            ▼
┌──────────────┐             ┌──────────────┐             ┌──────────────┐
│   VERBATIM   │             │  COPY-EDIT   │             │    COMMON    │
│    MATCH     │             │  (PATCHWORK) │             │    PHRASE    │
│  Exact ≥70%  │             │ Longest ≥10w │             │ Max IDF ≤1.0 │
│  Cosine ≥85% │             │ Cosine <85%  │             │ Klise Ilmiah │
└──────┬───────┘             └──────┬───────┘             └──────┬───────┘
       │                            │                            │
       ▼                            ▼                            ▼
 [ MATCH 🔴 ]                 [ REVIEW 🟡 ]                [ REVIEW 🟢/🟡 ]
Teks identik mutlak         Modifikasi pasase            Frasa standar metodologi
Periksa atribusi            Periksa sitasi & parafrasa   Bebas sanksi akademik
```

### A. `VERBATIM_MATCH` (Penyalinan Persis Tanpa Parafrasa)
- **Karakteristik**: Kemiripan sekuensial sangat tinggi ($\ge 70.0\%$ atau pasase terpanjang $\ge 50$ kata) **DAN** kemiripan orientasi leksikal kosinus sangat tinggi ($\ge 85.0\%$).
- **Keputusan**: **`MATCH`** (Merah).
- **Implikasi Akademik**: Penelaah wajib memeriksa apakah teks yang bersangkutan berada di dalam tanda kutip langsung (*direct quotation*) disertai nama pengarang dan halaman sumber rujukan yang sah.

### B. `COPY_EDIT` (Pasase Dipertahankan dengan Modifikasi Kata)
- **Karakteristik**: Terdapat pasase kata berurutan yang substansial ($\ge 10$ kata berturut-turut), namun orientasi kosinus menunjukkan adanya perubahan kata atau sisipan istilah lain ($10.0\% \le \text{Cosine} < 85.0\%$).
- **Keputusan**: **`REVIEW`** (Kuning).
- **Implikasi Akademik**: Tipikal penulisan tambal-sulam (*patchwriting*) di mana struktur kalimat sumber dijiplak namun beberapa kata disubstitusi. Penelaah perlu memeriksa apakah penulis sekadar menyalin struktur tanpa melakukan sintesis pemikiran mandiri.

### C. `COMMON_ACADEMIC_PHRASE` (Frasa Baku / Klise Metodologi Ilmiah)
- **Karakteristik**: Kemiripan sekuensial atau leksikal terdeteksi, namun seluruh istilah yang cocok memiliki bobot signifikansi rendah ($\text{Max IDF} \le 1.0$ atau $\text{Avg IDF} < 0.5$).
- **Keputusan**: **`REVIEW`** (Kuning/Informatif).
- **Implikasi Akademik**: Merupakan kalimat standar karya tulis (misal: *"Penelitian ini bertujuan untuk mengetahui pengaruh..."* atau *"Teknik pengumpulan data menggunakan kuesioner..."*). **Penulis TIDAK boleh dikenai sanksi atau tuduhan plagiasi atas penggunaan frasa baku keilmuan ini**.

### D. `SEMANTIC_PARAPHRASE` (Parafrasa Leksikal Topikal)
- **Karakteristik**: Tidak ditemukan pasase kata berurutan yang identik ($\text{Exact} < 10.0\%$), namun terdapat kemiripan kosakata yang kuat dengan bobot terminologi spesifik ($\text{Cosine} \ge 10.0\%$ dan $\text{Avg IDF} \ge 0.5$).
- **Keputusan**: **`REVIEW`** (Kuning).
- **Implikasi Akademik**: Penulis berhasil melakukan parafrasa kalimat dengan bahasanya sendiri, namun membahas ide dan rujukan konsep yang erat kaitannya dengan dokumen korpus. Penelaah cukup memastikan bahwa sitasi ke sumber ide dicantumkan secara layak.

### E. `PARTIAL_DIRECT_MATCH` (Kemiripan Pasase Terlokalisasi)
- **Karakteristik**: Kemiripan sekuensial terdeteksi pada fragmen teks tertentu dengan rujukan istilah spesifik ($\text{Max IDF} > 1.0$), namun secara global teks submisi bersifat orisinal.
- **Keputusan**: **`REVIEW`** (Kuning).
- **Implikasi Akademik**: Sering terjadi pada definisi istilah atau kutipan teori yang belum diberi tanda petik secara sempurna.

### F. `NO_EVIDENCE` (Bebas Indikasi Kemiripan)
- **Karakteristik**: Baik kemiripan sekuensial maupun orientasi leksikal berada di bawah ambang batas telaah minimum ($< 10.0\%$).
- **Keputusan**: **`NO_MATCH`** (Hijau).
- **Implikasi Akademik**: Tidak ada bukti tekstual yang menunjukkan irisan signifikan dengan korpus yang tersedia saat ini.

---

## 3. Indikator Kesepakatan Lensa (Agreement Model)

Sistem membandingkan diagnosis dari Lensa Exact dan Lensa TF-IDF untuk menentukan tingkat konvergensi bukti:

| Tipe Kesepakatan | Kondisi Lensa | Makna Analisis |
|---|---|---|
| **`STRONG_AGREEMENT`** | Exact tinggi & TF-IDF tinggi | Bukti tekstual dan orientasi leksikal mutlak selaras |
| **`PARTIAL_AGREEMENT`** | Exact tinggi & TF-IDF sedang | Teks berurutan sama, tetapi susunan kata global dimodifikasi |
| **`SIGNIFICANCE_DIVERGENCE`** | Ada kesamaan, tetapi IDF sangat rendah | Kemiripan hanya bersifat frasa pasaran / klise ilmiah |
| **`COMPLEMENTARY_EVIDENCE`** | Exact rendah, tetapi TF-IDF berbobot tinggi | Deteksi parafrasa leksikal yang tidak tertangkap oleh exact 5-gram |
| **`LOCALIZED_EXACT_EVIDENCE`**| Exact terlokalisasi dengan terminologi unik | Kutipan fragmen spesifik di tengah naskah orisinal |
| **`NO_EVIDENCE`** | Kedua lensa tidak menemukan irisan | Naskah bersih dari bukti irisan korpus |

---

## 4. Penjelasan Rasional Transparan (Explainability Rationale)

Sistem secara dinamis menyusun satu paragraf penjelasan objektif yang disajikan pada kartu *"WHY THIS RESULT?"*. Contoh nyata yang dihasilkan oleh sistem:

- **Kasus Verbatim**:
  > *"Dokumen menunjukkan keselarasan kesamaan tekstual mutlak (Exact: 100.00%, Pasase Terpanjang: 31 kata) dan orientasi kosakata penuh (Cosine: 87.29%)."*
- **Kasus Copy-Edit**:
  > *"Ditemukan pasase berurutan yang substansial (26 kata identik), namun orientasi kosakata global menunjukkan adanya intervensi/modifikasi teks (Cosine: 81.22%)."*
- **Kasus Common Phrase**:
  > *"Kemiripan terdeteksi pada tingkat frasa (Exact: 52.38%, Cosine: 35.86%), namun analisis signifikansi menunjukkan irisan didominasi oleh kosakata standar/frasa klise akademis (Max IDF: 0.6931)."*
- **Kasus Semantic Paraphrase**:
  > *"Tidak ditemukan pasase kata berurutan (Exact: 0.00%), namun terdeteksi kemiripan leksikal topikal yang kuat (Cosine: 41.34%) dengan terminologi spesifik rujukan (Avg IDF: 2.6978)."*
- **Kasus Bebas Indikasi**:
  > *"Tidak ditemukan bukti kesamaan pasase sekuensial maupun irisan kosakata yang melewati ambang batas telaah (Exact: 0.00%, Cosine: 5.73%)."*
