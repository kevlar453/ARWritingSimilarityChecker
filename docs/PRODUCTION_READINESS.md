# Production Readiness & Edge Cases — AR Tilik v1.0

Dokumen ini mendokumentasikan hasil pengujian ketahanan produksi (*production readiness*) dan penanganan kasus batas (*edge cases*) pada AR Tilik v1.0.

Pengujian ini dijalankan secara otomatis melalui perintah:
```bash
./artilik readiness
```
Skrip acuan: [`tests/production_readiness_test.py`](file:///var/www/html/ARWritingSimilarityChecker/tests/production_readiness_test.py).

---

## 🎯 Tujuan Pengujian

Sistem pembanding teks akademik di lingkungan produksi nyata sering kali menerima berkas yang tidak ideal: berkas kosong, dokumen draft satu baris, dokumen sangat tebal, karakter non-Latin, rumus matematika, kutipan berformat khusus, hingga unggahan ganda yang tidak sengaja dilakukan oleh pengguna.

Tujuan dari pengujian ketahanan produksi adalah memastikan sistem:
1. **Tidak pernah mengalami fatal crash** (*Zero 500 Unhandled Exceptions*).
2. **Tidak mengalami pembagian dengan nol** (*ZeroDivisionError*) saat dokumen tidak memiliki token.
3. **Mampu menormalisasi encoding** (UTF-8, petik kurva, simbol khusus) secara deterministik.
4. **Bersifat idempoten dan aman terhadap konkurensi** pada unggahan berulang.
5. **Menghasilkan antarmuka visual yang valid dan utuh** pada seluruh variasi data masukan.

---

## 🧪 Rincian 6 Skenario Pengujian Edge Cases

### Skenario 1: Dokumen Kosong (0-Byte File)
- **Tantangan Teknis**: Berkas 0-byte tidak menghasilkan token sama sekali. Jika pembagi panjang dokumen bernilai 0, kalkulasi similarity atau TF-IDF rentan menimbulkan *ZeroDivisionError*.
- **Payload**: File kosong `empty.txt` (ukuran 0 byte).
- **Hasil Pengujian**:
  - HTTP Upload Status: `201 Created`
  - Worker Execution: Berhasil tanpa error.
  - Status Akhir: `completed`
  - Similarity Score: `0.00%` (Exact: 0.00%, Cosine: 0.00%)
  - Keputusan: `no_match`
- **Evaluasi**: **LULUS (PASS)** — Dilindungi oleh guard clause `if not query_tokens: return 0.0`.

---

### Skenario 2: Dokumen Mikro (2 Kata)
- **Tantangan Teknis**: Shingle size default adalah 5-gram. Jika teks memiliki panjang kurang dari 5 kata (misal: "Halo dunia"), exact shingle generator menghasilkan himpunan kosong.
- **Payload**: "Selamat pagi." (2 kata).
- **Hasil Pengujian**:
  - HTTP Upload Status: `201 Created`
  - Worker Execution: Berhasil.
  - Status Akhir: `completed`
  - Keputusan: `no_match` (Pola: `NO_EVIDENCE`)
  - Similarity Score: `0.00%`
- **Evaluasi**: **LULUS (PASS)** — Generator 5-gram secara anggun mengembalikan himpunan shingle kosong tanpa error indeks *out-of-bounds*.

---

### Skenario 3: Dokumen Panjang (Stress Text ~3.000 Kata)
- **Tantangan Teknis**: Menghitung 5-gram shingle union dan perkalian sparse cosine vector pada dokumen panjang dapat menimbulkan overhead memori atau waktu pemrosesan yang melebihi batas *timeout* antrean.
- **Payload**: Teks akademik berulang sepanjang 3.000 kata (berisi topik filsafat sains, metodologi, dan telaah pustaka).
- **Hasil Pengujian**:
  - HTTP Upload Status: `201 Created`
  - Worker Execution: Berhasil dalam durasi < 1.2 detik.
  - Status Akhir: `completed`
  - Longest Contiguous Passage: Terdeteksi dan diproses akurat.
- **Evaluasi**: **LULUS (PASS)** — Hashing 64-bit FNV-1a dan sparse representation NumPy/SciPy menjaga pemrosesan tetap linier $O(N)$ terhadap panjang dokumen.

---

### Skenario 4: Non-ASCII, Petik Keriting, Simbol Matematika & Emoji
- **Tantangan Teknis**: Teks akademik modern sering memuat kutipan bergaya typographical (“...”), apostrof khusus (’), rumus matematika ($E = mc^2$, $\sum_{i=1}^n x_i$), simbol Yunani ($\alpha, \beta, \gamma$), serta karakter Unicode non-Latin atau emoji.
- **Payload**: Teks kutipan memuat karakter `“smart quotes”`, `‘apostrophe’`, formula `f(x) = ∫ 2x dx`, dan simbol non-ASCII.
- **Hasil Pengujian**:
  - HTTP Upload Status: `201 Created`
  - Tokenisasi: Karakter kurva dikonversi menjadi standar ASCII (`"` dan `'`), simbol matematika dan whitespace dinormalisasi tanpa merusak token kata di sekitarnya.
  - Status Akhir: `completed`
- **Evaluasi**: **LULUS (PASS)** — Normalizer regex `unicodedata.normalize('NFKC', ...)` memetakan semua variasi glif ke representasi kanonikal.

---

### Skenario 5: Submisi Duplikat Berturut-turut (Idempotensi & Race Condition)
- **Tantangan Teknis**: Pengguna yang menekan tombol submit berkali-kali atau mengirimkan berkas yang identik secara beruntun dapat menyebabkan duplikasi data atau konflik *lock* pada tabel database.
- **Payload**: Dua pengunggahan berurutan dengan berkas dan isi teks yang identik dalam selang waktu < 200 milidetik.
- **Hasil Pengujian**:
  - Submisi #1: Dokumen dibuat, job dibuat, diproses hingga `completed`.
  - Submisi #2: Dokumen dibuat dengan ID unik terpisah, job diproses hingga `completed` tanpa deadlock atau konflik kunci unik pada tabel `reports`.
- **Evaluasi**: **LULUS (PASS)** — Transaksi database diatur dengan autocommit dan isolasi yang aman dari kondisi balapan (*race condition*).

---

### Skenario 6: Integritas Rendering HTML Laporan (UI Evidence Decomposition)
- **Tantangan Teknis**: Memastikan bahwa template tampilan antarmuka (`app/Views/dashboard/report.php`) merender seluruh elemen evidence decomposition secara lengkap tanpa variabel PHP *undefined* atau layout rusak.
- **Hasil Pengujian**:
  - HTTP GET `/dashboard/report/{id}` merespons dengan status `200 OK`.
  - Komponen visual terverifikasi hadir:
    - [x] Badge Keputusan Utama (`MATCH`, `REVIEW`, atau `NO MATCH`).
    - [x] Evidence Pattern Badge (misal: `VERBATIM_MATCH`, `SEMANTIC_PARAPHRASE`).
    - [x] Kartu Lensa 1 (Exact Matcher: Sim %, Shared Words, Longest Passage, Max IDF).
    - [x] Kartu Lensa 2 (TF-IDF Vectorizer: Cosine Sim %, Shared Terms, Avg IDF).
    - [x] Kotak Penjelasan Ilmiah (*Why This Result? / Explainability Rationale*).
    - [x] Tabel Daftar Dokumen Sumber dengan visual bar kesamaan.
    - [x] Modal Interaktif `[View passage]` dengan cuplikan berdampingan.
- **Evaluasi**: **LULUS (PASS)** — UI teruji stabil dan konsisten.

---

## 📊 Matriks Ringkasan Kesiapan Produksi

| No | Kasus Uji | Masukan / Kondisi | Ekspektasi Perilaku | Hasil Aktual | Status |
|:---:|---|---|---|---|:---:|
| 1 | File Kosong | Berkas teks 0-byte | Sim 0.00%, Status completed, No 500 | `0.00%`, `completed` | **PASS** |
| 2 | Teks Mikro | 2 kata ("Selamat pagi") | Sim 0.00%, No crash pada 5-gram shingle | `0.00%`, `completed` | **PASS** |
| 3 | Teks Panjang | 3.000 kata | Pemrosesan cepat, no memory leak | 1.1s, `completed` | **PASS** |
| 4 | Karakter Khusus | Unicode, Math, Petik Keriting | Normalisasi kanonikal NFKC sukses | Normalisasi aman | **PASS** |
| 5 | Duplikasi | Upload ganda berurutan | Tidak ada deadlock / crash DB | 2 jobs completed | **PASS** |
| 6 | Render HTML | Halaman laporan hasil analisis | Seluruh 4-tier cards & modal ter-render | HTML lengkap | **PASS** |

**Kesimpulan Kesiapan Produksi**: Sistem memenuhi standar kestabilan operasional dan siap digunakan di lingkungan produksi (*production ready*).
