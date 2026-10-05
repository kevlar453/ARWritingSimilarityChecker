# Panduan Otomasi Panen Korpus Terjadwal (Automated & Scheduled Corpus Harvester) - AR Tilik v1.2

Dokumen ini menjelaskan arsitektur, panduan konfigurasi, dan pengoperasian mesin pemanenan korpus otomatis pada sistem penelaah kemiripan ilmiah **AR Tilik**.

---

## 1. Latar Belakang & Filosofi

Sebelumnya, pustakawan atau administrator sistem perlu mengunggah berkas karya ilmiah rujukan (skripsi, tesis, artikel jurnal) satu per satu secara manual melalui web form. Hal ini memakan waktu dan berpotensi menjadi hambatan bagi perpustakaan yang memiliki ratusan hingga ribuan naskah koleksi digital.

Mesin **Automated & Scheduled Corpus Harvester** menyediakan solusi komprehensif melalui 4 pilar:
1. **Bulk Ingest & Directory Dropzone**: Memproses sekumpulan berkas (PDF, DOCX, TXT) maupun berkas terkompresi ZIP secara rekursif langsung dari direktori server.
2. **OAI-PMH Harvester (Protokol Repositori Standar)**: Memanen naskah dan metadata secara terstandar dari repositori kampus (EPrints, DSpace, Open Journal Systems / OJS).
3. **Self-Growing Institutional Repository**: Fitur satu-klik untuk mempromosikan naskah tugas akhir mahasiswa yang telah lolos verifikasi menjadi bagian permanen korpus pembanding institusi.
4. **Autonomous Scholarly Discovery**: Menjelajahi basis data literatur akademik terbuka dunia (OpenAlex, CrossRef, DOAJ) dan katalog repositori perguruan tinggi Indonesia untuk mencari, mengunduh, dan mengindeks karya ilmiah secara mandiri.
5. **Scheduled Automation & Telegram Alert**: Penjadwalan eksekusi otomatis (cron job / systemd timer) saat server senggang dengan pelaporan hasil ke Telegram Bot institusi.

---

## 2. Struktur Direktori Dropzone & Arsip

Di lingkungan server produksi maupun lokal, AR Tilik menyediakan direktori khusus:

```bash
storage/
├── corpus_dropzone/    # Folder penempatan berkas baru yang siap dipanen
│   ├── skripsi_2024.zip
│   ├── Tesis_Markus_Sutrisno.pdf
│   └── Jurnal_Teologi_Vol5.docx
└── corpus_archive/     # Folder arsip berkas yang telah selesai diindeks
```

### Format Berkas yang Didukung
- **PDF** (`.pdf`): Ekstraksi teks berbasis PyMuPDF (fitz) dengan pembersihan margin header/footer.
- **DOCX** (`.docx`): Ekstraksi paragraf naskah Microsoft Word.
- **TXT** (`.txt`): Berkas teks polos UTF-8.
- **ZIP** (`.zip`): Paket arsip terkompresi. Sistem akan mengekstrak isinya secara aman (*Zip Slip Protected*) dan mengindeks seluruh naskah di dalamnya.

---

## 3. Panduan Penggunaan CLI (`./artilik`)

AR Tilik menyediakan antarmuka baris perintah terpadu:

### A. Memproses Folder Dropzone
```bash
./artilik corpus:dropzone
```
- Memindai seluruh berkas di `storage/corpus_dropzone/`.
- Mengekstrak teks, membuat potongan shingle 5-gram, menyimpannya ke `fingerprints` dan `fingerprint_documents`.
- Memperbarui statistik bobot DF/IDF korpus (`fingerprint_stats`).
- Memindahkan berkas yang selesai diproses ke `storage/corpus_archive/`.

### B. Mengimpor Berkas atau Folder Kustom
```bash
# Impor satu folder skripsi fakultas
./artilik corpus:import /path/ke/koleksi/skripsi/

# Impor satu berkas ZIP tertentu
./artilik corpus:import /home/pustaka/kumpulan_jurnal.zip
```

### C. Memanen dari Repositori Kampus (OAI-PMH)
```bash
# Periksa konektivitas dan nama repositori kampus
./artilik corpus:harvest --url https://repository.kampus.ac.id/cgi/oai2 --identify

# Tampilkan daftar set/koleksi yang tersedia
./artilik corpus:harvest --url https://repository.kampus.ac.id/cgi/oai2 --list-sets

# Panen 20 naskah dari koleksi skripsi/tesis (beserta unduhan PDF full-text)
./artilik corpus:harvest --url https://repository.kampus.ac.id/cgi/oai2 --set thesis --limit 20

# Panen hanya metadata dan ringkasan abstrak tanpa mengunduh berkas PDF
./artilik corpus:harvest --url https://repository.kampus.ac.id/cgi/oai2 --limit 50 --no-fulltext
```

### D. Promosi Naskah Mahasiswa Menjadi Korpus (Self-Growing)
```bash
./artilik corpus:enroll <DOCUMENT_ID>
```
*Contoh:* `./artilik corpus:enroll 1103`
Dokumen langsung bertransformasi dari `submission` menjadi `corpus` institusi, dan shingle-nya langsung diaktifkan sebagai pembanding untuk pemeriksaan masa depan.

### E. Pencarian & Penjelajahan Mandiri (Autonomous Scholarly Discovery)
```bash
# 1. Cari karya ilmiah terbuka berdasarkan topik/kata kunci via OpenAlex
./artilik corpus:discover --query "Pendidikan Agama Kristen" --limit 15

# 2. Panen otomatis dari katalog repositori perguruan tinggi Indonesia
./artilik corpus:discover --registry --limit 10

# 3. Cari karya asli dari judul-judul rujukan dalam naskah mahasiswa
./artilik corpus:discover --document 1103 --limit 5
```

---

## 4. Penjadwalan Otomatis (Cron & Systemd Timer)

### Menjalankan Siklus Pemanenan Terjadwal
Perintah berikut mengorkestrasi pemindaian dropzone, pemanenan OAI-PMH, pembaruan indeks, dan pengiriman notifikasi Telegram:
```bash
./artilik corpus:cron
```

### Konfigurasi `.env`
Tambahkan variabel berikut pada file `.env` di server:
```ini
# Endpoint OAI-PMH repositori kampus target (opsional)
CORPUS_OAI_URL = https://repository.kampus.ac.id/cgi/oai2
CORPUS_OAI_SET = thesis
CORPUS_OAI_LIMIT = 20
CORPUS_OAI_FULLTEXT = true

# Telegram Notifikasi
TELEGRAM_BOT_TOKEN = 688091201:AAFQ0TiJfDZg2wSsuPvIPJtNa_5NaQetfm4
TELEGRAM_CHAT_ID = 674868958
```

### Konfigurasi Crontab (Harian Pukul 02:00 WIB)
Jalankan `crontab -e` pada server, lalu tambahkan baris berikut:
```cron
# Eksekusi panen korpus otomatis AR Tilik setiap hari pukul 02:00 dini hari
0 2 * * * cd /var/www/html/tilik.arsetontong.top && ./artilik corpus:cron >> /var/log/artilik_harvester.log 2>&1
```

---

## 5. Alur Fitur Melalui Web Interface

1. **Dashboard Korpus**:
   - Tab **"Manajemen Korpus Rujukan"** kini dilengkapi tombol **"Pindai Dropzone Sekarang"**.
   - Operator dapat menaruh berkas di folder dropzone lalu menekan tombol tersebut untuk memproses langsung tanpa membuka terminal.
2. **Halaman Laporan Pemeriksaan**:
   - Tombol **"Daftarkan ke Korpus"** tersedia di bilah aksi atas laporan.
   - Apabila naskah mahasiswa dinyatakan orisinal dan lulus sidang, penguji/operator dapat menekan tombol tersebut dengan satu klik untuk memperkaya basis korpus institusi.
