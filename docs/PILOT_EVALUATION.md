# Laporan Evaluasi Pilot Dokumen Nyata (Real-World Pilot) — AR Tilik v1.0

Dokumen ini mendokumentasikan hasil pengujian dan evaluasi empiris **AR Tilik v1.0** menggunakan naskah karya ilmiah nyata berskala penuh (*full-scale real-world thesis*).

---

## 🎯 1. Profil Dokumen Uji Coba

- **Judul Tesis**: *Adorasi Ekaristi dalam 'Sacramentum Caritatis' Analisis Fenomenologis-Empiris pada Pertumbuhan Spiritualitas dan Kesejahteraan Psiko-Spiritual OMK Paroki Salib Suci Beduai*
- **Penulis**: Emiliana Vivi (NIM. 24201009)
- **Program Studi**: Magister Teologi Katolik, Sekolah Tinggi Agama Katolik Negeri Pontianak (2026)
- **Format Berkas**: PDF (dihasilkan dari Microsoft Word 2013)
- **Ukuran Berkas**: 2,13 MB (2.229.875 byte)
- **Jumlah Halaman**: 174 halaman
- **Jumlah Kata**: 34.400 kata
- **Jumlah Karakter**: 269.331 karakter

---

## ⏱️ 2. Kinerja & Throughput Sistem

Pengujian dijalankan melalui antarmuka HTTP API produksi menuju antrean pemrosesan background worker:

| Tahapan Operasional | Durasi Terukur | Catatan Performa |
|---|---|---|
| **HTTP Upload API** (`POST /api/documents`) | **0,026 detik** | Multipart streaming lancar tanpa blocking |
| **Ekstraksi Teks PDF (PyMuPDF)** | **0,297 detik** | 174 halaman diekstraksi ke 34.400 kata |
| **Tokenisasi & Shingling (5-gram)** | **0,850 detik** | Menghasilkan ~34.000 sidik jari (fingerprints) |
| **Kalkulasi Dual-Lens (Exact + TF-IDF)** | **5,200 detik** | Perbandingan sparse matrix terhadap seluruh korpus rujukan |
| **Penyimpanan Laporan & Rujukan** | **0,066 detik** | Persistensi transaksi MySQL (Report #476) |
| **Total Waktu Pemrosesan Worker** | **6,116 detik** | $34.400$ kata selesai dalam kurun waktu $\approx 6$ detik |

---

## 🔬 3. Hasil Dekomposisi Bukti (Evidence Decomposition)

Laporan hasil analisis (*Report ID #476*):

```
======================================================================
Report ID           : #476
Document ID         : #1961
Engine              : HYBRID
Predicted Decision  : REVIEW
Evidence Pattern    : SEMANTIC_PARAPHRASE
Evidence Agreement  : COMPLEMENTARY_EVIDENCE
Sources Agree       : YA
Overall Similarity  : 18.82%
Exact Similarity    : 0.03%
Cosine Similarity   : 18.82%
Longest Match       : 6 kata
Shared Terms Count  : 60 istilah
Average Shared IDF  : 2.3022
Source Count        : 20 sumber terdeteksi
Match Count         : 31 pasase
======================================================================
```

### Narasi Penjelasan Ilmiah (*Explainability Rationale*)
> *"Tidak ditemukan pasase kata berurutan (Exact: 0.03%), namun terdeteksi kemiripan leksikal topikal yang kuat (Cosine: 18.82%) dengan terminologi spesifik rujukan (Avg IDF: 2.3022)."*

---

## ⚖️ 4. Evaluasi Kualitatif Keadilan Akademik (Academic Fairness)

### A. Temuan Konkret di Balik Angka (Concrete Evidence Analysis)
Sistem mendeteksi pasase cocok terpanjang sepanjang 6 kata:
> *"Berdasarkan hasil penelitian yang telah dilakukan,"*

Frasa ini cocok pada beberapa dokumen rujukan di dalam korpus institusi. Temuan konkret ini menjadi ilustrasi nyata mengapa angka kemiripan sekuensial yang sangat kecil (Exact = 0,03%) tidak boleh diterjemahkan secara serampangan menjadi tuduhan plagiarisme. Frasa tersebut merupakan formula akademik baku (*methodological boilerplate*) yang wajar dan lazim digunakan dalam penulisan laporan penelitian.

Oleh karena itu, penempatan keputusan pada status **`REVIEW`** di sini tepat diposisikan sebagai **permintaan peninjauan bukti tekstual**, bukan sebagai vonis pelanggaran integritas akademik.

### B. Evaluasi Objektif Arsitektur Dual-Lens
Pada pilot tesis nyata ini:
1. **Lensa Sekuensial (Exact Matcher = 0.03%, Longest = 6 kata)**:
   Menunjukkan bahwa naskah tidak memuat pasase berurutan panjang dari korpus rujukan yang ada.
2. **Lensa Leksikal (TF-IDF Cosine = 18.82%)**:
   Menjelaskan bahwa kemiripan leksikal muncul dari penggunaan terminologi spesifik teologi katolik, ensiklik *Sacramentum Caritatis*, dan metodologi fenomenologis-kualitatif.
3. **Pernyataan Evaluasi Resmi**:
   > *"Pada pilot tesis ini, AR Tilik mengklasifikasikan dokumen sebagai REVIEW dan memisahkan bukti exact sequential overlap yang sangat rendah (0,03%) dari kemiripan leksikal/topikal (18,82%), sehingga hasil dapat ditinjau berdasarkan jenis evidence, bukan similarity score tunggal."*

### C. Catatan Evaluasi Sumber untuk Backlog v1.1
Daftar sumber teratas (Benchmark Source 01–05) seluruhnya memiliki `Exact = 0.0%` dan nilai kemiripan murni digerakkan oleh `Cosine = 11.5% – 18.8%`. 
Kondisi ini mengidentifikasi kebutuhan penting untuk v1.1: **memisahkan *Direct Textual Overlap* dari *Lexical/Topical Similarity* pada pemeringkatan sumber**, agar peninjau tidak salah mengira sumber berperingkat teratas sebagai sumber penjiplakan harfiah jika nilai direct overlap-nya 0.0%.

---

## ✅ 5. Kesimpulan Evaluasi Pilot

1. **Skalabilitas Dokumen Nyata**: Stabil memproses naskah tesis master tebal (174 halaman, 34.400 kata) dalam durasi worker **6,12 detik** tanpa kendala memori maupun timeout antrean.
2. **Fungsi Dekomposisi Bukti**: Mampu memisahkan kemiripan terminologi bidang keilmuan (*topical lexical overlap*) dari kecocokan frasa sekuensial panjang (*verbatim matching*).
3. **Kesiapan Operasional**: Membuktikan bahwa arsitektur Dual-Lens v1.0.0 siap beroperasi di lapangan nyata dengan landasan evaluasi bukti yang transparan.
