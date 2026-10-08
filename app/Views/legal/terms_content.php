<div class="legal-document">
    <div class="legal-header">
        <div class="legal-badge">Dokumen Resmi &bull; Etika &amp; Integritas Ilmiah</div>
        <h1 class="legal-title">Ketentuan Layanan (Terms of Service)</h1>
        <p class="legal-subtitle">
            Terakhir diperbarui: 07 Oktober 2026 &bull; Pedoman penggunaan layanan untuk mahasiswa, dosen, peneliti, dan pengelola jurnal ilmiah.
        </p>
    </div>

    <div class="legal-callout">
        <strong>Pernyataan Asas Akademik:</strong> AR Tilik adalah instrumen pengukuran kesepadanan tekstual objektif (*textual similarity instrument*), <strong>bukan pembuat vonis plagiarisme</strong>. Persentase kemiripan yang dilaporkan mesin merupakan titik tolak telaah kritis manusia, bukan alat diskualifikasi otomatis tanpa pertimbangan konteks ilmiah.
    </div>

    <section class="legal-section">
        <h2>1. Asas Filosofis: Kesepadanan Tekstual vs. Plagiarisme</h2>
        <p>
            Dengan menggunakan layanan AR Tilik, setiap pengguna (mahasiswa, dosen, peneliti, editor jurnal, maupun institusi mitra) memahami dan menyetujui batasan filosofis berikut:
        </p>
        <ul>
            <li><strong>Kesepadanan Tekstual (Textual Similarity):</strong> Menghitung derajat tumpang tindih urutan kata (5-gram) dan kesamaan terminologi leksikal (TF-IDF Cosine) antara naskah submisi dengan korpus rujukan secara matematis.</li>
            <li><strong>Plagiarisme (Plagiarism):</strong> Pelanggaran etika berat yang menyangkut niat pengabaian atribusi, penjiplakan ide substansial, atau klaim kepengarangan secara tidak jujur.</li>
            <li><strong>Kedaulatan Keputusan Manusia:</strong> Angka persentase AR Tilik tidak boleh dijadikan dasar tunggal penolakan naskah atau penjatuhan sanksi akademik. Keputusan final keaslian dan kelayakan karya ilmiah mutlak berada pada wewenang <strong>Dosen Pembimbing, Dewan Penguji Sidang, Mitra Bestari (*Peer Reviewer*), dan Dewan Redaksi Jurnal</strong>.</li>
        </ul>
    </section>

    <section class="legal-section">
        <h2>2. Standar Kejujuran Akademik &amp; Larangan Manipulasi Teks (Anti-Cheat)</h2>
        <p>
            AR Tilik dibangun untuk mempromosikan budaya riset yang bermartabat. Pengguna dilarang keras melakukan manipulasi dokumen yang bertujuan mengecoh atau merusak algoritma deteksi, termasuk namun tidak terbatas pada:
        </p>
        <ul>
            <li><strong>Substitusi Karakter Asing (Homoglyphs):</strong> Mengganti huruf Latin dengan alfabet Cyrillic, Yunani, atau karakter khusus yang tampak serupa secara visual.</li>
            <li><strong>Penyisipan Karakter Tak Kasat Mata:</strong> Menyelipkan spasi nol-lebar (*zero-width space*), *soft hyphen*, atau pemenggal kata tersembunyi di antara suku kata.</li>
            <li><strong>Manipulasi Tipografi &amp; Teks Putih:</strong> Menggunakan font berwarna putih, transparan, atau berukuran mikro (*hidden text*) untuk memanipulasi rasio kata unik.</li>
            <li><strong>Manipulasi Struktur Berkas Biner:</strong> Mengubah tata letak PDF menjadi gambar (*rasterization*) untuk menghalangi ekstraksi teks normal.</li>
        </ul>
        <p>
            Sistem AR Tilik dilengkapi modul <em>Anti-Cheat &amp; Evasion Detection Engine</em>. Naskah yang terbukti mengandung manipulasi di atas akan diberi tanda peringatan risiko tinggi (*evasion risk flag*) pada Laporan Pemeriksaan dan Sertifikat Digital.
        </p>
    </section>

    <section class="legal-section">
        <h2>3. Pemanfaatan Teknologi Kecerdasan Buatan (AI) &amp; Audit Forensik Semantik</h2>
        <p>
            Dalam rangka menjaga keaslian dan kemurnian nalar ilmiah, AR Tilik menerapkan pemrosesan komputasi cerdas (termasuk <em>Audit Forensik Cerdas</em> dan <em>Sintesis Risiko Akademik Berbobot</em>):
        </p>
        <ul>
            <li><strong>Persetujuan Evaluasi Semantik:</strong> Pengguna memahami dan menyetujui bahwa naskah yang diajukan akan dianalisis secara semantik untuk menguji konsistensi metodologis, keselarasan logika antar-bab, dan integritas sitasi (mendeteksi rujukan fiktif atau sitasi hasil halusinasi otomatis).</li>
            <li><strong>Tanggung Jawab Penulis atas Naskah:</strong> Pengguna (mahasiswa atau penulis) bertanggung jawab penuh terhadap setiap klaim, teori, data, dan referensi yang dicantumkan dalam karyanya. Dalih ketidaktahuan atas kutipan fiktif atau inkonsistensi yang dihasilkan oleh bantuan generator teks otomatis tidak membebaskan penulis dari kewajiban etik akademik.</li>
            <li><strong>Sifat Indikator &amp; Ujian Lisan:</strong> Hasil Audit Forensik Cerdas bukan merupakan vonis hukum mutlak, melainkan instrumen pembuktian ilmiah objektif yang menyajikan panduan pertanyaan ujian lisan (*oral examination guidance*). Penulis wajib mampu membuktikan pemahaman orisinal atas isi naskahnya di hadapan dosen pembimbing atau dewan penguji sidang.</li>
        </ul>
    </section>

    <section class="legal-section">
        <h2>4. Penggunaan REST API Publik &amp; Integrasi OJS 3.x</h2>
        <p>
            Bagi pengelola jurnal ilmiah atau institusi yang memanfaatkan layanan API publik v1 dan konektor PHP OJS 3.x (`ARTilikOJSConnector`):
        </p>
        <ul>
            <li><strong>Kerahasiaan Kunci API:</strong> Pengelola jurnal bertanggung jawab penuh menjaga kerahasiaan *API Key*. Dilarang menyematkan kunci API pada kode sisi klien yang dapat dibaca publik.</li>
            <li><strong>Kepatuhan Kuota Pemakaian:</strong> Panggilan API dibatasi sesuai dengan kuota bulanan yang dialokasikan pada portal admin. Sistem berhak menolak permintaan yang melebihi batas (*HTTP 429 Too Many Requests*).</li>
            <li><strong>Larangan Penyalahgunaan Lalu Lintas (No DoS):</strong> Dilarang melakukan panggilan berulang secara tidak wajar (*flooding/brute-force*) yang dapat mengganggu stabilitas peladen bagi pengelola jurnal lainnya.</li>
        </ul>
    </section>

    <section class="legal-section">
        <h2>5. Sertifikat Digital Keabsahan &amp; Verifikasi Publik</h2>
        <p>
            Setiap dokumen yang selesai dianalisis berhak memperoleh Sertifikat Digital ber-QR Code kriptografis (SHA-256):
        </p>
        <ul>
            <li>Sertifikat mencerminkan status naskah pada tanggal dan jam pemeriksaan dilakukan terhadap basis data korpus yang tersedia pada saat itu.</li>
            <li>Tautan verifikasi resmi hanya dapat diakses melalui domain induk terpercaya: <code>https://tilik.arsetontong.top/verify/{token}</code>.</li>
            <li>Segala bentuk modifikasi, manipulasi grafis, pemalsuan nomor sertifikat, atau penggantian QR Code merupakan pelanggaran pidana dan etika akademik berat yang dapat dilaporkan ke pihak berwajib serta dewan etik perguruan tinggi terkait.</li>
        </ul>
    </section>

    <section class="legal-section">
        <h2>6. Batasan Tanggung Jawab (Limitation of Liability)</h2>
        <p>
            AR Tilik berupaya secara maksimal menghadirkan algoritma presisi tinggi, pustaka berdaya komputasi modern, dan ketersediaan peladen yang stabil. Namun demikian:
        </p>
        <ul>
            <li>Layanan ini disediakan sebagaimana adanya (*as-is*). Kami tidak menjamin bahwa seluruh artikel yang beredar di seluruh dunia telah masuk dalam korpus pembanding pada detik pemeriksaan berlangsung.</li>
            <li>AR Tilik tidak bertanggung jawab atas kerugian moril, sanksi administratif akademik, atau kerugian material yang timbul dari interpretasi sepihak atas angka persentase kemiripan tanpa telaah mendalam konteks kutipan oleh pihak penelaah naskah.</li>
        </ul>
    </section>

    <section class="legal-section">
        <h2>7. Hukum yang Berlaku &amp; Asas Integritas Nasional</h2>
        <p>
            Ketentuan Layanan ini tunduk dan ditafsirkan berdasarkan hukum Negara Kesatuan Republik Indonesia, standar etika publikasi ilmiah nasional (ARJUNA / Kemdiktisaintek), serta prinsip kebebasan mimbar akademik.
        </p>
    </section>

    <section class="legal-section">
        <h2>8. Kontak Dukungan &amp; Layanan Bantuan</h2>
        <p>
            Untuk pertanyaan terkait implementasi Ketentuan Layanan, kemitraan lisensi kampus, atau aduan etika akademik:
        </p>
        <div class="legal-contact-card">
            <strong>Sekretariat Etika &amp; Dukungan AR Tilik</strong><br>
            Email: <a href="mailto:admin@tilik.arsetontong.top">admin@tilik.arsetontong.top</a><br>
            Website Resmi: <a href="https://tilik.arsetontong.top">https://tilik.arsetontong.top</a>
        </div>
    </section>
</div>
