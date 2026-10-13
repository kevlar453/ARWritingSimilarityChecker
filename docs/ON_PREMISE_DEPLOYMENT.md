# Panduan Penerapan Mandiri Institusi (On-Premise Enterprise Deployment)
**AR Tilik — Academic Writing Similarity & Evidence Checker**  
**Versi Sistem:** v1.3.0 Enterprise Edition  
**Prinsip Arsitektur:** Data Sovereignty, Air-Gapped Readiness, Non-Repository Default, & Self-Purge Guarantee

---

## 🏛️ 1. Latar Belakang & Kedaulatan Naskah (Data Sovereignty)

Banyak perguruan tinggi dan pengelola jurnal ilmiah di Indonesia menghadapi risiko hukum dan privasi ketika menyerahkan draf skripsi, tesis, disertasi, atau naskah pra-publikasi ke penyedia cloud multinasional:
1. **Risiko Data Lock-In:** Naskah yang diunggah ke repositori global milik pihak ketiga tidak dapat ditarik kembali secara mandiri tanpa proses birokrasi manual.
2. **Kerahasiaan Hak Paten & Kebaruan Riset:** Naskah riset bernilai paten rentan terekspos sebelum publikasi resmi.
3. **Kepatuhan Regulasi:** UU Perlindungan Data Pribadi (UU PDP No. 27/2022) dan regulasi privasi data institusi mewajibkan data penelitian strategis disimpan di dalam yurisdiksi server lokal kampus.

**AR Tilik On-Premise Edition** dirancang agar seluruh data, naskah fisik, sidik jari hash (shingle/fingerprints), serta basis data audit **100% berada di bawah kendali penuh departemen teknologi informasi kampus Anda**.

---

## ⚡ 2. Prasyarat Sistem

| Komponen | Spesifikasi Minimum | Rekomendasi Enterprise |
|:---|:---|:---|
| **Sistem Operasi** | Linux (Debian 12/13, Ubuntu 22.04/24.04, RHEL 9) | Debian 13 (Trixie) / Ubuntu 24.04 LTS |
| **Runtime Web** | **PHP 8.5+** (FPM/Apache) | **PHP 8.5.11+** dengan OPcache & FPM |
| **Runtime Forensik** | **Python 3.14+** | **Python 3.14.7+** Virtualenv |
| **Basis Data** | MariaDB 10.11+ / MySQL 8.0+ | MariaDB 10.11 LTS (InnoDB Buffer Pool 4 GB+) |
| **Cache & Queue** | Redis 7.x | Redis 7.x Alpine |
| **CPU & Memori** | 4 Core CPU, 8 GB RAM | 8 Core CPU, 16–32 GB RAM (NVMe Storage) |
| **Kontainerisasi** | Docker Engine 24+ & Docker Compose v2+ | Docker Swarm atau Kubernetes Ready |

---

## 🚀 3. Panduan Instalasi Cepat via Docker Compose (15 Menit)

### Langkah 1: Kloning Repositori ke Server Kampus
```bash
git clone https://github.com/kevlar453/ARWritingSimilarityChecker.git /opt/artilik
cd /opt/artilik
```

### Langkah 2: Konfigurasi File Lingkungan
```bash
cp .env.docker.example .env
nano .env
# Sesuaikan kredensial basis data dan APP_URL lokal kampus (misal: https://tilik.kampus.ac.id/)
```

### Langkah 3: Bangun & Jalankan Kontainer
```bash
docker compose up -d --build
```

Setelah perintah di atas selesai:
- Antarmuka web AR Tilik beroperasi di: `http://<IP-SERVER>:8085` atau melalui reverse proxy domain kampus.
- Database MariaDB diinisialisasi otomatis dari `schema.sql`.
- Background worker daemon (`./artilik worker`) berjalan otomatis di bawah pengawasan `supervisord`.

### Langkah 4: Verifikasi Kesiapan Operasional
```bash
docker compose exec app ./artilik readiness
```

---

## 🛡️ 4. Pembuktian Fitur Kedaulatan & Hapus Mandiri (Self-Purge)

AR Tilik membedakan dua tingkatan perlindungan kedaulatan naskah yang dapat diverifikasi oleh admin IT kampus:

### A. Non-Repository Default
Naskah yang diserahkan oleh mahasiswa untuk penelaahan kesepadanan **hanya diproses di lingkungan memori kerja terisolasi** untuk menghasilkan laporan bukti, dan **tidak pernah diserap menjadi korpus pembanding terbuka**, kecuali penulis/dosen secara eksplisit memilih opsi *“Kontribusikan ke Korpus Tertutup Kampus”*.

### B. Hapus Mandiri Terverifikasi Level Disk (Disk Purge)
Pemilik dokumen atau admin prodi dapat memusnahkan berkas fisik naskah kapan saja melalui tombol **"Musnahkan Berkas Fisik"** di dashboard. Sistem mengeksekusi operasi tingkat rendah:
1. Pemanggilan fungsi `unlink()` pada path fisik berkas PDF/DOCX di sistem file disk server.
2. Pengosongan teks mentah pada tabel `document_chunks` dan `document_pages` di basis data.
3. Penghapusan biner seluruh sidik jari digital (*fingerprints* dan *shingles*) naskah.
4. Nilai `storage_path` diubah menjadi `[PURGED]` dengan timestamp `file_purged_at`.
5. Hanya hash sertifikat digital SHA-256 yang dipertahankan untuk kebutuhan verifikasi akreditasi ijazah di masa depan.

---

## 🔌 5. Integrasi dengan Ekosistem Kampus (OJS & SSO)

### A. Integrasi PKP OJS 3.x
Gunakan modul konektor yang tersedia di:
- Skrip: `public/integrations/ojs/ARTilikOJSConnector.php`
- Dokumen Panduan: `public/integrations/ojs/Panduan_Integrasi_OJS_ARTilik.pdf`
- Endpoint REST API: `POST /api/v1/check`

### B. Autentikasi & SSO Kampus (LDAP / SAML / OAuth2)
AR Tilik mendukung otorisasi berbasis API Key terisolasi per fakultas/program studi dan siap dihubungkan ke SSO Sentral Universitas.

---

## 📞 Dukungan Teknis & Layanan Institusi
Untuk asistensi penerapan kluster server skala besar atau asistensi migrasi data dari sistem lama:
- Email Tim Rekayasa: `support@tilik.arsetontong.top`
- Portal Resmi: `https://tilik.arsetontong.top/`
