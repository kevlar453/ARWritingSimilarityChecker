# Batasan Sistem & Prinsip Keadilan Akademik — AR Tilik v1.0

Dokumen ini mendefinisikan batasan teknis (*system boundaries*), batasan ilmiah (*scientific scope*), serta filosofi keadilan akademik (*academic fairness*) yang mendasari perancangan **AR Tilik v1.0**.

---

## ⚖️ 1. Filosofi Keadilan Akademik (Bukan Alat Vonis Plagiarisme)

### Kesamaan Tekstual $\neq$ Plagiarisme
AR Tilik dirancang sebagai **alat bantu identifikasi bukti tekstual dan signifikansi rujukan** (*textual evidence & reference significance checker*), **BUKAN mesin penentu atau pemvonis plagiarisme otomatis**.

Dalam karya ilmiah yang sah dan berintegritas tinggi, kesamaan tekstual lazim dan wajar ditemukan pada:
- **Kutipan langsung**: Teks kitab suci, pasal perundang-undangan, ensiklik, dokumen historis, atau kutipan narasumber wawancara yang dikutip secara sah dengan tanda petik.
- **Frasa metodologi umum**: Istilah terstandarisasi seperti *"teknik pengumpulan data menggunakan purposive sampling"*, *"analisis data dilakukan dengan reduksi data, penyajian data, dan penarikan kesimpulan"*.
- **Daftar Pustaka / Bibliografi**: Format sitasi standar (APA, Chicago, Turabian) yang secara otomatis akan memiliki kesamaan dengan karya lain yang mengutip sumber yang sama.
- **Halaman Formalitas Institusional**: Lembar persetujuan pembimbing, lembar pengesahan, surat pernyataan keaslian, kata pengantar, dan daftar isi.

### Peran Penilai Manusia
Sistem ini menyediakan **dekomposisi bukti transparan** (*evidence decomposition*) untuk membantu dosen pembimbing, penguji, dan komite etik akademik. **Keputusan etis akhir sepenuhnya berada di tangan akademisi manusia**, bukan pada persentase angka tunggal yang dihasilkan mesin.

---

## 🔍 2. Batasan Teknis Arsitektur Dual-Lens v1.0

### A. Lensa Sekuensial (Exact Matcher)
- **Ukuran Shingle (5-Gram)**: Lensa exact bekerja dengan membandingkan 5 kata berturut-turut. Jika teks mengalami parafrasa berat dengan penyisipan kata sinonim di setiap 2–3 kata, lensa exact tidak akan mendeteksi pasase tersebut sebagai *exact match* (melainkan diserahkan kepada lensa leksikal).
- **Sensitivitas Urutan Kata**: Perubahan urutan kata (*word permutation*) akan memutus rantai shingle sekuensial.

### B. Lensa Leksikal (TF-IDF Vectorizer)
- **Representasi Bag-of-Words**: TF-IDF mengukur frekuensi dan signifikansi kata tanpa memperhatikan sintaksis mendalam atau tata bahasa (*grammar*).
- **Ketiadaan Pemahaman Konteks Mendalam (Deep Semantics)**: Lensa TF-IDF tidak memahami antonim, negasi konteksual (*"ini benar"* vs *"ini tidak benar"*), atau makna tersirat (*figurative language*).
- **Bahasa Asing / Cross-Lingual**: Deteksi kemiripan lintas bahasa (misalnya menerjemahkan teks Latin/Inggris ke bahasa Indonesia) tidak dideteksi pada v1.0 dan memerlukan modul *multilingual embedding* pada rilis mendatang.

### C. Dependensi terhadap Korpus Rujukan
- **Cakupan Terbatas pada Basis Data Lokal**: AR Tilik hanya dapat membandingkan dokumen submisi terhadap dokumen yang telah tersimpan di dalam basis data (tabel `documents`).
- **Tidak Melakukan Web Crawling**: Sistem ini tidak mengakses seluruh internet terbuka atau jurnal berbayar di luar korpus yang diunggah ke institusi.

### D. Pemisahan Bagian Formal Dokumen (Daftar Pustaka & Lembar Pengesahan)
- Pada v1.0, seluruh isi berkas teks/PDF diekstraksi dan dibandingkan secara menyeluruh.
- Pemisahan otomatis (*automatic section parsing*) untuk mengabaikan Daftar Pustaka secara selektif dijadwalkan untuk rilis v1.1 / Fase 3. Saat ini pengguna disarankan meninjau rincian pasase pada modal `[View passage]`.

---

## 📈 3. Batasan Klaim Ilmiah & Benchmark

### Interpretasi Metrik Benchmark
- **Klaim Terukur**: Capaian **Macro F1 = 1.0000** dan **Binary F1 = 1.0000** diraih pada dataset benchmark terstandarisasi (50 kasus uji laboratorium yang mencakup variasi identik, *copy-edit*, parafrasa, frasa umum, dan teks berbeda).
- **Bukan Jaminan Generalisasi Mutlak**: Hasil benchmark laboratorium membuktikan konsistensi aturan *evidence synthesis* dan kestabilan algoritma, **bukan klaim universal** bahwa sistem dapat membedakan semua jenis manipulasi teks di dunia nyata tanpa supervisi manusia.
- Setiap pengujian dokumen nyata (*real-world document*) wajib dievaluasi secara kualitatif berdasarkan kegunaan pasase bukti dan keadilan akademik bagi mahasiswa/penulis.
