# Spesifikasi Algoritma & Komputasi AR Tilik (v1.0.0-RC)

Dokumen ini menjelaskan fondasi matematis, struktur data, dan tahapan komputasi algoritmik yang diterapkan pada AR Tilik.

---

## 1. Lensa 1: Sequential Exact Matcher

Mesin Exact Matcher (`worker/matcher.py`) bertugas mendeteksi salinan kata berturut-turut (*verbatim sequential passage*) dan mengukur panjang pasase terpanjang.

### A. Pembentukan Shingle 5-Gram
Diberikan urutan token kata hasil normalisasi:
$$W = (w_1, w_2, w_3, \dots, w_M)$$
Untuk ukuran shingle $k = 5$, shingle ke-$i$ didefinisikan sebagai 5 kata berurutan:
$$s_i = (w_i, w_{i+1}, w_{i+2}, w_{i+3}, w_{i+4}), \quad 1 \le i \le M - k + 1$$

### B. Hashing FNV-1a 32-bit
Setiap shingle $s_i$ dikonversi menjadi representasi integer 32-bit menggunakan algoritma Fowler–Noll–Vo (FNV-1a) untuk efisiensi penyimpanan dan pencarian:
$$H_0 = \text{0x811C9DC5}$$
$$H_j = (H_{j-1} \oplus b_j) \times \text{0x01000193} \pmod{2^{32}}$$
di mana $b_j$ adalah byte ke-$j$ dari representasi string shingle yang dipisahkan spasi tunggal.

### C. Algoritma Union Coverage (Tanpa Double Counting)
Untuk mencegah penggelembungan persentase ketika satu kalimat submisi cocok dengan lebih dari satu dokumen korpus, sistem menghitung cakupan gabungan (*union coverage*):

1. Setiap segmen kecocokan shingle $s_i$ dipetakan ke rentang indeks kata submisi $[i, i + k - 1]$.
2. Seluruh rentang diurutkan: $R = \text{sort}([(start_1, end_1), (start_2, end_2), \dots])$.
3. Penggabungan rentang yang tumpang tindih (*overlapping*) atau berdampingan (*adjacent*):
   $$\text{Jika } start_{next} \le end_{last} + 1 \implies end_{last} \leftarrow \max(end_{last}, end_{next})$$
4. Jumlah kata unik yang cocok dihitung sebagai:
   $$W_{matched} = \sum_{(start, end) \in R_{merged}} (end - start + 1)$$
5. Persentase kemiripan exact:
   $$\text{Similarity}_{exact} = \min\left(100.0, \frac{W_{matched}}{M} \times 100\%\right)$$

### D. Algoritma Pasase Terpanjang (*Longest Contiguous Match*)
Segmen yang berurutan pada dokumen submisi sekaligus pada dokumen sumber yang sama digabungkan:
$$L_{contiguous} = end_{seg} - start_{seg} + 1$$
$$Longest = \max_{seg \in Segments} (L_{contiguous})$$

---

## 2. Lensa 2: Sparse TF-IDF Cosine Vectorizer

Mesin TF-IDF (`worker/tfidf.py`) bertugas mendeteksi orientasi leksikal topikal yang kebal terhadap intervensi susunan kata, pengubahan sinonim, dan sisipan sitasi (*citation noise*).

### A. Frekuensi Istilah (Term Frequency - TF)
Untuk dokumen $D$ dengan panjang $M$ kata, frekuensi istilah $t$ dihitung sebagai frekuensi tereduksi:
$$\text{TF}(t, D) = \frac{f(t, D)}{\sum_{t' \in D} f(t', D)}$$

### B. Frekuensi Dokumen Terbalik (Inverse Document Frequency - IDF)
Dihitung dari korpus $C$ yang berisi $N$ dokumen:
$$\text{DF}(t) = \sum_{D_i \in C} \mathbb{I}(t \in D_i)$$
Menggunakan formula penghalusan (*smooth IDF*) standar Information Retrieval:
$$\text{IDF}(t) = \ln\left(1 + \frac{N}{\text{DF}(t)}\right) + 1.0$$
Istilah yang tidak terdapat di korpus (kandidat kosakata unik) diberi bobot default:
$$\text{IDF}_{default} = \ln(1 + N) + 1.0$$

### C. Pembentukan Vektor TF-IDF
Bobot untuk setiap istilah $t$ dalam dokumen:
$$v(t) = \text{TF}(t, D) \times \text{IDF}(t)$$

### D. Kemiripan Kosinus (*Cosine Similarity*)
Kemiripan antara vektor submisi $\vec{u}$ dan vektor korpus $\vec{v}$ dihitung dalam ruang dimensi jarang (*sparse dictionary space*):
$$\text{Cosine}(\vec{u}, \vec{v}) = \frac{\sum_{t \in \vec{u} \cap \vec{v}} u_t \cdot v_t}{\sqrt{\sum_{t} u_t^2} \cdot \sqrt{\sum_{t} v_t^2}}$$
Skor kemiripan dinormalisasi ke persentase:
$$\text{Similarity}_{cosine} = \text{Cosine}(\vec{u}, \vec{v}) \times 100\%$$

---

## 3. Algoritma Sintesis Bukti (Hybrid Aggregation)

Diberikan kontrak bukti terstandardisasi dari kedua lensa:
- Lensa 1: $Sim_{exact}, Longest, MaxIDF_{exact}$
- Lensa 2: $Sim_{tfidf}, SharedTerms, AvgIDF_{shared}$

Aturan keputusan berjenjang dievaluasi secara berurutan:

```python
# 1. Kasus Identik / Verbatim
if (Sim_exact >= 70.0 or Longest >= 50) and Sim_tfidf >= 85.0:
    return "VERBATIM_MATCH", "match", "STRONG_AGREEMENT"

# 2. Kasus Frasa Akademik Umum (Pencegahan False Positive)
if (Sim_exact >= 10.0 and MaxIDF_exact <= 1.0) or (Sim_tfidf >= 10.0 and AvgIDF_shared < 0.5):
    return "COMMON_ACADEMIC_PHRASE", "review", "SIGNIFICANCE_DIVERGENCE"

# 3. Kasus Copy-Edit (Pasase Berurutan dengan Intervensi Kosakata)
if (Sim_exact >= 70.0 or Longest >= 10) and (10.0 <= Sim_tfidf < 85.0):
    return "COPY_EDIT", "review", "PARTIAL_AGREEMENT"

# 4. Kasus Kemiripan Sekuensial Terlokalisasi
if (Sim_exact >= 10.0 or Longest >= 10) and MaxIDF_exact > 1.0:
    return "PARTIAL_DIRECT_MATCH", "review", "LOCALIZED_EXACT_EVIDENCE"

# 5. Kasus Parafrasa Semantik Topikal
if Sim_exact < 10.0 and Sim_tfidf >= 10.0 and AvgIDF_shared >= 0.5:
    return "SEMANTIC_PARAPHRASE", "review", "COMPLEMENTARY_EVIDENCE"

# 6. Bebas Indikasi Kemiripan
return "NO_EVIDENCE", "no_match", "NO_EVIDENCE"
```

---

## 4. Analisis Kompleksitas Komputasi

1. **Waktu Komputasi Shingling & Exact Match**:
   - Pembentukan shingle: $\mathcal{O}(M)$
   - Pencarian indeks terbalik (*Inverted Index Lookup*): $\mathcal{O}(M \times \bar{k}_{matches})$
   - Penggabungan rentang (*Interval Merging*): $\mathcal{O}(K \log K)$ di mana $K$ adalah jumlah segmen shingle cocok.
2. **Waktu Komputasi TF-IDF**:
   - Tokenisasi & TF: $\mathcal{O}(M)$
   - Dot product vektor jarang: $\mathcal{O}(|V_{\text{shared}}|)$
3. **Ruang Memori**:
   - Penyimpanan vektor jarang berbasis Python dictionary: $\mathcal{O}(|V_{unique}|)$ yang sangat hemat memori dibandingkan representasi matriks padat (*dense matrix*).
