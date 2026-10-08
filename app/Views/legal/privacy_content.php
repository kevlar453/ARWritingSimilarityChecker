<div class="legal-document">
    <div class="legal-header">
        <div class="legal-badge">Dokumen Resmi &bull; Perlindungan Sivitas Akademika</div>
        <h1 class="legal-title">Kebijakan Privasi &amp; Kedaulatan Data Akademik</h1>
        <p class="legal-subtitle">
            Terakhir diperbarui: 07 Oktober 2026 &bull; Berlaku untuk seluruh pengguna, penulis, peneliti, dan institusi mitra AR Tilik.
        </p>
    </div>

    <div class="legal-callout">
        <strong>Prinsip Utama Kedaulatan Data:</strong> AR Tilik dibangun atas asas keadilan dan etika akademik. Naskah karya ilmiah (skripsi, tesis, disertasi, manuskrip jurnal) yang Anda unggah <strong>TIDAK PERNAH</strong> dijual, disewakan, dialihkan, maupun dikomersialkan kepada pihak ketiga mana pun. Naskah tetap menjadi hak milik intelektual penuh Anda dan institusi asal Anda.
    </div>

    <section class="legal-section">
        <h2>1. Kedaulatan Hak Cipta &amp; Kepemilikan Naskah</h2>
        <p>
            AR Tilik memandang naskah karya ilmiah sebagai produk intelektual yang berharga dan dilindungi oleh undang-undang hak cipta. Ketika Anda mengunggah naskah (dalam format PDF, DOCX, maupun TXT) ke dalam sistem:
        </p>
        <ul>
            <li><strong>Tidak Ada Pengalihan Hak Cipta:</strong> Mengunggah dokumen ke AR Tilik tidak mengalihkan atau mengurangi kepemilikan hak cipta Anda atas naskah tersebut.</li>
            <li><strong>Bukan Komersialisasi Pihak Ketiga:</strong> Berbeda dengan kekhawatiran umum terhadap sistem komersial multinasional, AR Tilik tidak pernah menjual korpus teks Anda kepada korporasi teknologi asing atau pihak ketiga mana pun.</li>
            <li><strong>Kedaulatan Institusi Kampus:</strong> Untuk paket institusi/kampus, basis data korpus dikelola secara tertutup (*private repository*) dan berada di bawah yurisdiksi perguruan tinggi bersangkutan.</li>
        </ul>
    </section>

    <section class="legal-section">
        <h2>2. Perlindungan dari Jebakan "Self-Plagiarism" (Naskah Pra-Sidang)</h2>
        <p>
            Salah satu kekhawatiran terbesar mahasiswa dan peneliti saat melakukan uji mandiri adalah naskah draf mereka tersimpan di database publik, sehingga saat diuji oleh dewan sidang atau diunggah ke repositori resmi kampus, naskah tersebut terdeteksi sebagai plagiarisme atas karya sendiri (*self-plagiarism trap*).
        </p>
        <p>
            AR Tilik menerapkan kebijakan proteksi tegas:
        </p>
        <ul>
            <li>Dokumen yang diunggah untuk pemeriksaan mandiri <strong>TIDAK otomatis didaftarkan ke korpus pembanding umum</strong>.</li>
            <li>Naskah submisi Anda hanya diproses di memori kalkulasi untuk menghitung kesepadanan terhadap korpus yang telah ada, dan tidak dijadikan materi pembanding bagi pengguna luar lainnya.</li>
        </ul>
    </section>

    <section class="legal-section">
        <h2>3. Ruang Lingkup &amp; Batasan Pemrosesan Data Teks</h2>
        <p>
            Pemrosesan teks yang dilakukan oleh peladen AR Tilik dibatasi secara ketat pada kebutuhan komputasi integritas akademik:
        </p>
        <ul>
            <li><strong>Ekstraksi &amp; Normalisasi Teks:</strong> Mengekstrak teks dari berkas naskah, membersihkan artefak biner, dan membentuk token *n-gram* sekuensial serta representasi leksikal (*sparse TF-IDF*).</li>
            <li><strong>Penyelarasan Bilingual (Cross-Lingual):</strong> Menyelaraskan teks secara semantik untuk mendeteksi indikasi naskah hasil alih-bahasa (Inggris &ndash; Indonesia) tanpa pengutipan yang sah.</li>
            <li><strong>Pemeriksaan Anomali Tipografi (Anti-Cheat):</strong> Menganalisis ada tidaknya manipulasi font, *Cyrillic homoglyphs*, maupun karakter nol-lebar (*zero-width space*) yang sengaja disisipkan.</li>
            <li><strong>Penerbitan Hash SHA-256 &amp; Sertifikat Digital:</strong> Menghasilkan sidik jari kriptografis naskah guna menerbitkan Sertifikat Keabsahan digital ber-QR Code pada portal verifikasi publik resmi.</li>
        </ul>
    </section>

    <section class="legal-section">
        <h2>4. Pemrosesan Kecerdasan Buatan (AI) &amp; Audit Forensik Semantik yang Bertanggung Jawab</h2>
        <p>
            Untuk memberikan perlindungan mutu ilmiah terhadap manipulasi teks canggih, perakitan draf sintetis (*AI-stitching*), dan sitasi halusinasi, AR Tilik mengintegrasikan modul <em>Audit Forensik Cerdas</em> dan <em>Sintesis Risiko Akademik Berbobot</em>:
        </p>
        <ul>
            <li><strong>Inferensi Nir-Penyimpanan (Stateless Inference):</strong> Seluruh pemrosesan teks oleh modul komputasi cerdas bersifat sementara (*transient / stateless*). Teks naskah yang dikirim untuk evaluasi semantik dan validasi sitasi diproses secara terisolasi dan <strong>TIDAK PERNAH disimpan permanen, tidak diindeks ke pangkalan data umum, dan tidak digunakan untuk melatih (*training/fine-tuning*) model kecerdasan buatan komersial pihak ketiga mana pun</strong>.</li>
            <li><strong>Kerahasiaan &amp; Enkripsi Tingkat Lanjut:</strong> Transmisi data ke simpul inferensi diamankan dengan protokol enkripsi modern (TLS v1.3) berstandar industri, menjamin kerahasiaan naskah penelitian pra-publikasi.</li>
            <li><strong>Kedaulatan Keputusan Akademik:</strong> Sistem kecerdasan cerdas dalam AR Tilik tidak memvonis integritas moral seseorang. Penilaian yang dihasilkan berfungsi sebagai instrumen forensik objektif, pemetaan inkonsistensi metodologi, dan panduan dialog kritis bagi dosen pembimbing dan dewan penguji sidang.</li>
        </ul>
    </section>

    <section class="legal-section">
        <h2>5. Partisipasi Korpus Gotong Royong (Crowdsourcing)</h2>
        <p>
            Pengguna yang memanfaatkan program insentif gotong royong korpus (menyumbangkan 3 karya ilmiah valid untuk memperoleh kuota submisi tambahan) tunduk pada ketentuan:
        </p>
        <ul>
            <li>Naskah yang didonasikan ke korpus institusi <strong>hanya boleh berupa karya ilmiah yang berstatus Akses Terbuka (*Open Access*)</strong>, telah terbit secara resmi di repositori perguruan tinggi, jurnal bereputasi, atau prosiding konferensi yang dapat diakses publik.</li>
            <li>Dilarang mendonasikan naskah draf rahasia, karya milik pihak lain yang bersifat tertutup, atau dokumen yang terikat perjanjian kerahasiaan (*Non-Disclosure Agreement*).</li>
        </ul>
    </section>

    <section class="legal-section">
        <h2>6. Perlindungan Data Akun Pribadi (UU PDP No. 27 Tahun 2022)</h2>
        <p>
            Kami mengumpulkan dan mengelola data pribadi pengguna (nama lengkap, alamat email institusi, nama perguruan tinggi, serta log aktivitas) sesuai dengan amanat <strong>Undang-Undang Perlindungan Data Pribadi (UU No. 27/2022)</strong>:
        </p>
        <ul>
            <li>Data identitas Anda tidak akan pernah dipindahtangankan atau disalahgunakan untuk keperluan pemasaran pihak ketiga yang tidak relevan.</li>
            <li>Kata sandi akun dienkripsi menggunakan algoritma *hashing* standar industri (Bcrypt/Argon2).</li>
            <li>Sistem dilengkapi pemantau keamanan berlapis untuk menangkal upaya peretasan, pencurian data kredensial, dan serangan siber terdistribusi.</li>
        </ul>
    </section>

    <section class="legal-section">
        <h2>7. Hak Pengguna &amp; Penghapusan Data (Right to Erasure)</h2>
        <p>
            Sebagai sivitas akademika, Anda memiliki kendali penuh atas data Anda:
        </p>
        <ul>
            <li><strong>Hak Akses &amp; Tinjau:</strong> Anda berhak meninjau seluruh riwayat dokumen, laporan kesepadanan, dan sertifikat yang diterbitkan atas nama akun Anda.</li>
            <li><strong>Hak Penghapusan Naskah:</strong> Anda dapat menghapus dokumen yang pernah Anda periksa dari dasbor akun Anda. Begitu dihapus, berkas fisik naskah akan dieliminasi dari media penyimpanan server.</li>
        </ul>
    </section>

    <section class="legal-section">
        <h2>8. Kontak Pengembang &amp; Tim Kepatuhan Privasi</h2>
        <p>
            Jika Anda, dewan penjaminan mutu kampus, atau pengelola jurnal memiliki pertanyaan, permintaan khusus terkait kedaulatan data, atau permohonan audit perjanjian kerahasiaan data institusi, silakan hubungi tim kami:
        </p>
        <div class="legal-contact-card">
            <strong>Tim Pengembang &amp; Kepatuhan Data AR Tilik</strong><br>
            Surat Elektronik: <a href="mailto:admin@tilik.arsetontong.top">admin@tilik.arsetontong.top</a><br>
            Pengembang &amp; Infrastruktur: <a href="https://www.arsetontong.top" target="_blank" rel="noopener noreferrer">Powered by ARSetontong</a> &bull; Indonesia
        </div>
    </section>
</div>
