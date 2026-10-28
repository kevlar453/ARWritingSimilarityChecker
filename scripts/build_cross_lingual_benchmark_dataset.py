#!/usr/bin/env python3
"""
AR Tilik - Cross-Lingual Semantic Expansion Dataset Builder (v1.3 - Phase 3)
Membangun dataset benchmark lintas bahasa terarah:
- 10 kasus per pasangan bahasa:
  1. Indonesia <-> Inggris (ID <-> EN) [N=10]
  2. Indonesia <-> Arab (ID <-> AR) [N=10]
  3. Indonesia <-> Mandarin (ID <-> ZH) [N=10]
  4. Indonesia <-> Belanda (ID <-> NL) [N=10]
  5. Indonesia <-> Jerman (ID <-> DE) [N=10]
- 10 kasus kontrol bersih lintas bahasa (Negative Controls - Anti False Positive) [N=10]
Total: 60 kasus benchmark ilmiah terarah.
"""

import json
from pathlib import Path

PROJECT_ROOT = Path(__file__).resolve().parents[1]

CROSS_LINGUAL_CASES = [
    # =========================================================================
    # 1. INDONESIA <-> INGGRIS (ID <-> EN) - 10 KASUS
    # Karakteristik: Domain sains komputasi, kedokteran, energi, dan ekonomi.
    # =========================================================================
    {
        "case_id": "XLN-EN-001",
        "pair": "ID-EN",
        "domain": "Kecerdasan Buatan / NLP",
        "source_text": "Model representasi bahasa berbasis transformer memanfaatkan mekanisme atensi mandiri untuk menangkap dependensi kontekstual jarak jauh dalam korpus teks berskala sangat besar.",
        "submission_text": "Transformer-based language representation models leverage self-attention mechanisms to capture long-range contextual dependencies across large-scale text corpora.",
        "is_plagiarized": True,
        "source_lang": "id",
        "submission_lang": "en",
        "description": "Terjemahan langsung literatur NLP transformer."
    },
    {
        "case_id": "XLN-EN-002",
        "pair": "ID-EN",
        "domain": "Teknologi Energi Bersih",
        "source_text": "Pembangkit listrik tenaga surya fotovoltaik membutuhkan inverter pintar demi menjaga stabilitas tegangan listrik di jaringan transmisi.",
        "submission_text": "Distributed photovoltaic solar plants require smart inverters to maintain grid voltage stability during fluctuating solar irradiance.",
        "is_plagiarized": True,
        "source_lang": "id",
        "submission_lang": "en",
        "description": "Terjemahan parafrastik teknik tenaga surya."
    },
    {
        "case_id": "XLN-EN-003",
        "pair": "ID-EN",
        "domain": "Epidemiologi Klinis",
        "source_text": "Penularan patogen saluran pernapasan melalui aerosol mikro membutuhkan ventilasi udara bertekanan negatif pada ruang isolasi rumah sakit.",
        "submission_text": "Transmission of respiratory pathogens via micro-aerosols necessitates negative-pressure ventilation systems in clinical isolation units.",
        "is_plagiarized": True,
        "source_lang": "id",
        "submission_lang": "en",
        "description": "Terjemahan istilah medis aerosol dan ruang isolasi."
    },
    {
        "case_id": "XLN-EN-004",
        "pair": "ID-EN",
        "domain": "Ekonomi Moneter",
        "source_text": "Bank sentral memperketat likuiditas perbankan melalui operasi pasar terbuka guna menahan laju ekspektasi inflasi yang tidak terkendali.",
        "submission_text": "The central bank tightens commercial banking liquidity via open market operations to anchor runaway inflation expectations.",
        "is_plagiarized": True,
        "source_lang": "id",
        "submission_lang": "en",
        "description": "Konsep moneter likuiditas perbankan dan operasi pasar."
    },
    {
        "case_id": "XLN-EN-005",
        "pair": "ID-EN",
        "domain": "Sains Material / Nanoteknologi",
        "source_text": "Sintesis lembaran grafena oksida dengan metode reduksi kimia menghasilkan konduktivitas listrik yang stabil pada komposit polimer.",
        "submission_text": "Chemical reduction synthesis of graphene oxide nanosheets yields robust electrical conductivity in advanced polymer composites.",
        "is_plagiarized": True,
        "source_lang": "id",
        "submission_lang": "en",
        "description": "Terminologi kimia material reduksi lembaran grafena."
    },
    {
        "case_id": "XLN-EN-006",
        "pair": "ID-EN",
        "domain": "Hukum Internasional",
        "source_text": "Prinsip yurisdiksi universal memungkinkan peradilan domestik mengadili pelaku kejahatan kemanusiaan luar biasa terlepas dari tempat perbuatan berlangsung.",
        "submission_text": "The doctrine of universal jurisdiction empowers domestic courts to prosecute perpetrators of heinous crimes against humanity irrespective of the locus delicti.",
        "is_plagiarized": True,
        "source_lang": "id",
        "submission_lang": "en",
        "description": "Doktrin hukum yurisdiksi universal dan kejahatan kemanusiaan."
    },
    {
        "case_id": "XLN-EN-007",
        "pair": "ID-EN",
        "domain": "Bioteknologi Pertanian",
        "source_text": "Penyuntingan genom tanaman memakai teknologi CRISPR-Cas9 meningkatkan resistensi tanaman pangan terhadap cekaman kekeringan ekstrem.",
        "submission_text": "Crop genome editing utilizing CRISPR-Cas9 technologies significantly improves agricultural crop resilience against extreme drought stresses.",
        "is_plagiarized": True,
        "source_lang": "id",
        "submission_lang": "en",
        "description": "Penyuntingan gen CRISPR pada tanaman pangan."
    },
    {
        "case_id": "XLN-EN-008",
        "pair": "ID-EN",
        "domain": "Arsitektur Komputer",
        "source_text": "Akselerator perangkat keras khusus jaringan saraf tiruan memangkas konsumsi daya secara drastis dibandingkan prosesor serbaguna konvensional.",
        "submission_text": "Domain-specific hardware accelerators for neural network workloads drastically reduce power dissipation compared to traditional general-purpose CPUs.",
        "is_plagiarized": True,
        "source_lang": "id",
        "submission_lang": "en",
        "description": "Perbandingan akselerator AI vs prosesor konvensional."
    },
    {
        "case_id": "XLN-EN-009",
        "pair": "ID-EN",
        "domain": "Oseanografi Fisik",
        "source_text": "Arus lintas Indonesia memindahkan massa air bersuhu hangat dari Samudra Pasifik ke Samudra Hindia sehingga mengatur dinamika iklim regional.",
        "submission_text": "The Indonesian Throughflow transports warm upper-layer water mass from the Pacific to the Indian Ocean, modulating regional climate variability.",
        "is_plagiarized": True,
        "source_lang": "id",
        "submission_lang": "en",
        "description": "Konsep Arlindo (Indonesian Throughflow) dinamika iklim."
    },
    {
        "case_id": "XLN-EN-010",
        "pair": "ID-EN",
        "domain": "Psikologi Kognitif",
        "source_text": "Beban kognitif intrinsik saat memecahkan masalah matematis rumit dibatasi oleh kapasitas penyimpanan memori kerja manusia yang terbatas.",
        "submission_text": "Intrinsic cognitive load during complex mathematical reasoning is strictly bounded by the finite capacity of human working memory.",
        "is_plagiarized": True,
        "source_lang": "id",
        "submission_lang": "en",
        "description": "Teori beban kognitif Sweller dan memori kerja."
    },

    # =========================================================================
    # 2. INDONESIA <-> ARAB (ID <-> AR) - 10 KASUS (Aksara Hijaiyah)
    # Karakteristik: Studi Islam, Fikih, Ushul Fikih, Sejarah Peradaban, dan Filologi.
    # =========================================================================
    {
        "case_id": "XLN-AR-001",
        "pair": "ID-AR",
        "domain": "Hukum Ekonomi Syariah",
        "source_text": "Akad pembiayaan bagi hasil mudharabah menuntut keterbukaan informasi keuangan dari pihak pengelola usaha demi mencegah penipuan.",
        "submission_text": "يتطلب عقد المضاربة للتمويل بالمشاركة شفافية المعلومات المالية من جانب المضارب لمنع الغرر والتدليس.",
        "is_plagiarized": True,
        "source_lang": "id",
        "submission_lang": "ar",
        "description": "Terminologi mudharabah, gharar, dan tadlis dalam ekonomi syariah."
    },
    {
        "case_id": "XLN-AR-002",
        "pair": "ID-AR",
        "domain": "Ushul Fikih",
        "source_text": "Kemaslahatan umum menjadi pertimbangan utama dalam perumusan hukum syariat ketika terjadi benturan antara kepentingan individu dan publik.",
        "submission_text": "تعتبر المصلحة العامة أصلاً معتبراً في استنباط الأحكام الشرعية عند تعارض المصالح الفردية مع المصلحة الجماعية.",
        "is_plagiarized": True,
        "source_lang": "id",
        "submission_lang": "ar",
        "description": "Konsep mashlahah 'ammah dan pertimbangan hukum ushul fikih."
    },
    {
        "case_id": "XLN-AR-003",
        "pair": "ID-AR",
        "domain": "Filologi Naskah Nusantara",
        "source_text": "Kritik teks atas naskah klasik keagamaan bertujuan merekonstruksi silsilah manuskrip dan memulihkan bacaan asli yang mendekati arketipe pengarang.",
        "submission_text": "يهدف التحقيق الفيلولوجي للمخطوطات التراثية إلى تتبع شجرة الأنساب الخطية واستعادة النص الأصلي الأقرب إلى مسودة المؤلف.",
        "is_plagiarized": True,
        "source_lang": "id",
        "submission_lang": "ar",
        "description": "Kritik teks manuskrip kuno dan rekonstruksi arketipe."
    },
    {
        "case_id": "XLN-AR-004",
        "pair": "ID-AR",
        "domain": "Filsafat Islam / Pemikiran",
        "source_text": "Harmoni antara akal rasional dan wahyu keagamaan dibela oleh Ibnu Rusyd melalui metodologi takwil demonstratif dalam risalah pembelaannya.",
        "submission_text": "دافع ابن رشد عن التوافق التام بين العقل والوحي من خلال منهجية التأويل البرهاني في كتابه فصل المقال.",
        "is_plagiarized": True,
        "source_lang": "id",
        "submission_lang": "ar",
        "description": "Harmonisasi akal dan wahyu perspektif Ibnu Rusyd."
    },
    {
        "case_id": "XLN-AR-005",
        "pair": "ID-AR",
        "domain": "Kaidah Fikih Lingkungan",
        "source_text": "Kaidah fikih menegaskan bahwa kemudaratan tidak boleh dihilangkan dengan cara menimbulkan kemudaratan baru yang setara atau lebih parah.",
        "submission_text": "تقضي القاعدة الفقهية الكبرى بأن الضرر لا يزال بضرر مثله أو أشد منه في معالجة القضايا البيئية.",
        "is_plagiarized": True,
        "source_lang": "id",
        "submission_lang": "ar",
        "description": "Kaidah al-dhararu la yuzalu bi mitslih."
    },
    {
        "case_id": "XLN-AR-006",
        "pair": "ID-AR",
        "domain": "Linguistik Bahasa Semit",
        "source_text": "Sistem infleksi vokal dalam tata bahasa morfologis Arab menentukan fungsi sintaksis subjek, objek, dan preposisi dalam susunan kalimat.",
        "submission_text": "يحدد نظام الإعراب بالحركات في النحو العربي الوظيفة التركيبية للفاعل والمفعول والمجرور داخل البنية الكلامية.",
        "is_plagiarized": True,
        "source_lang": "id",
        "submission_lang": "ar",
        "description": "Konsep sintaksis i'rab dan fungsi gramatikal."
    },
    {
        "case_id": "XLN-AR-007",
        "pair": "ID-AR",
        "domain": "Studi Astronomi Falak",
        "source_text": "Perhitungan hisab kontemporer memanfaatkan data orbit bulan dan matahari terkini guna menentukan awal bulan kamariah secara presisi.",
        "submission_text": "تعتمد الحسابات الفلكية المعاصرة على إحداثيات مداري الشمس والقمر لتحديد بدايات الشهور الهجرية بدقة عالية.",
        "is_plagiarized": True,
        "source_lang": "id",
        "submission_lang": "ar",
        "description": "Ilmu falak koordinat hisab awal bulan kamariah."
    },
    {
        "case_id": "XLN-AR-008",
        "pair": "ID-AR",
        "domain": "Hukum Waris / Faraidh",
        "source_text": "Pembagian harta waris menurut hukum Islam didasarkan pada asas keadilan distributif berimbang sesuai kedekatan nasab dan tanggung jawab nafkah.",
        "submission_text": "يقوم توزيع التركات في الفقه الإسلامي على مبدأ العدالة التوزيعية المتوازنة بحسب درجة القرابة وعبء النفقة.",
        "is_plagiarized": True,
        "source_lang": "id",
        "submission_lang": "ar",
        "description": "Asas pembagian waris faraidh dalam fikih."
    },
    {
        "case_id": "XLN-AR-009",
        "pair": "ID-AR",
        "domain": "Etika Kedokteran Islam",
        "source_text": "Penggunaan organ tubuh pengganti hasil transplantasi diperbolehkan dalam kondisi darurat medis asalkan tidak melibatkan komersialisasi organ manusia.",
        "submission_text": "يجوز التداوي بزرع الأعضاء البشرية في الحالات العلاجية الحرجة شريطة عدم تحويل جسد الإنسان إلى سلعة تجارية.",
        "is_plagiarized": True,
        "source_lang": "id",
        "submission_lang": "ar",
        "description": "Fikih kedokteran kontemporer transplantasi organ."
    },
    {
        "case_id": "XLN-AR-010",
        "pair": "ID-AR",
        "domain": "Sejarah Maritim Timur Tengah - Nusantara",
        "source_text": "Jalur pelayaran rempah menghubungkan pelabuhan Yaman dan Oman dengan bandar-bandar niaga di Selat Malaka sejak berabad-abad silam.",
        "submission_text": "ربطت طرق تجارة التوابل البحرية موانئ اليمن وعمان بالمراكز التجارية في مضيق ملقا عبر القرون الماضية.",
        "is_plagiarized": True,
        "source_lang": "id",
        "submission_lang": "ar",
        "description": "Historiografi niaga laut Samudra Hindia - Malaka."
    },

    # =========================================================================
    # 3. INDONESIA <-> MANDARIN (ID <-> ZH) - 10 KASUS (Aksara Hanzi Sederhana)
    # Karakteristik: Ekonomi Asia Timur, Rantai Pasok, Geopolitik, dan Teknologi Manufaktur.
    # =========================================================================
    {
        "case_id": "XLN-ZH-001",
        "pair": "ID-ZH",
        "domain": "Rantai Pasok Industri Global",
        "source_text": "Relokasi rantai pasok industri semikonduktor ke kawasan Asia Tenggara dipicu oleh upaya diversifikasi risiko geopolitik dan gangguan produksi global.",
        "submission_text": "全球半导体产业链向东南亚地区的转移，主要由地缘政治风险分散以及防范生产中断的战略驱动。",
        "is_plagiarized": True,
        "source_lang": "id",
        "submission_lang": "zh",
        "description": "Relokasi industri chip semikonduktor Asia Tenggara."
    },
    {
        "case_id": "XLN-ZH-002",
        "pair": "ID-ZH",
        "domain": "Teknologi Baterai Kendaraan Listrik",
        "source_text": "Hilirisasi bijih nikel laterit menggunakan teknologi pelindian asam bertekanan tinggi merupakan rantai kunci pembuatan katoda baterai litium.",
        "submission_text": "采用高压酸浸技术对红土镍矿进行深加工，是制造电动汽车锂电池正极材料的核心环节。",
        "is_plagiarized": True,
        "source_lang": "id",
        "submission_lang": "zh",
        "description": "Hilirisasi nikel laterit teknologi HPAL katoda baterai."
    },
    {
        "case_id": "XLN-ZH-003",
        "pair": "ID-ZH",
        "domain": "Hubungan Internasional / Perdagangan Bebas",
        "source_text": "Perjanjian kemitraan ekonomi komprehensif regional memfasilitasi penurunan tarif kepabeanan serta harmonisasi aturan asal barang antarnegara anggota.",
        "submission_text": "区域全面经济伙伴关系协定通过大幅削减关税税率并统一原产地规则，促进了成员国之间的多边贸易整合。",
        "is_plagiarized": True,
        "source_lang": "id",
        "submission_lang": "zh",
        "description": "RCEP penurunan tarif dagang dan rules of origin."
    },
    {
        "case_id": "XLN-ZH-004",
        "pair": "ID-ZH",
        "domain": "Ekonomi Digital / E-Commerce",
        "source_text": "Penerapan sistem pembayaran digital berbasis kode respons cepat meningkatkan inklusi keuangan pedagang mikro di perkotaan.",
        "submission_text": "基于快速响应二维码的移动数字支付体系显著提升了城市小型商户的普惠金融参与度。",
        "is_plagiarized": True,
        "source_lang": "id",
        "submission_lang": "zh",
        "description": "QR code mobile payment dan inklusi keuangan UMKM."
    },
    {
        "case_id": "XLN-ZH-005",
        "pair": "ID-ZH",
        "domain": "Teknik Sipil / Kereta Cepat",
        "source_text": "Desain bantalan rel tanpa balas pada jalur kereta cepat dirancang khusus untuk meminimalkan getaran struktur pada kecepatan tiga ratus kilometer per jam.",
        "submission_text": "高速铁路无砟轨道系统的结构设计旨在有效吸收高频振动，确保列车在时速三百公里运行时的平稳性。",
        "is_plagiarized": True,
        "source_lang": "id",
        "submission_lang": "zh",
        "description": "Jalur rel tanpa balas (ballastless track) kereta cepat."
    },
    {
        "case_id": "XLN-ZH-006",
        "pair": "ID-ZH",
        "domain": "Sosiologi Urban / Demografi",
        "source_text": "Fenomena penuaan populasi yang cepat menuntut reformasi skema jaminan pensiun nasional serta penyediaan fasilitas perawatan lansia terpadu.",
        "submission_text": "人口加速老龄化趋势迫切要求各国改革国家养老保险体系，并扩大社区综合照护服务的供给能力。",
        "is_plagiarized": True,
        "source_lang": "id",
        "submission_lang": "zh",
        "description": "Penuaan populasi demografi dan skema pensiun."
    },
    {
        "case_id": "XLN-ZH-007",
        "pair": "ID-ZH",
        "domain": "Teknologi Komunikasi 5G/6G",
        "source_text": "Transmisi gelombang milimeter berfrekuensi tinggi membutuhkan stasiun pemancar mikro berdensitas tinggi karena redaman propagasi yang signifikan.",
        "submission_text": "超高频毫米波信号的空间传播损耗极大，因此必须在城市核心区部署高密度的微基站以实现覆盖。",
        "is_plagiarized": True,
        "source_lang": "id",
        "submission_lang": "zh",
        "description": "Gelombang milimeter telekomunikasi dan micro-cell."
    },
    {
        "case_id": "XLN-ZH-008",
        "pair": "ID-ZH",
        "domain": "Pertanian Presisi",
        "source_text": "Integrasi drone pemantau citra multispektral dengan sensor kelembapan tanah mengoptimalkan efisiensi pemupukan tanaman padi.",
        "submission_text": "结合无人机多光谱遥感影像与农田土壤湿度传感器，能够实现水稻种植中精准施肥与病虫害监控。",
        "is_plagiarized": True,
        "source_lang": "id",
        "submission_lang": "zh",
        "description": "Drone multispektral dan pemupukan presisi sawah."
    },
    {
        "case_id": "XLN-ZH-009",
        "pair": "ID-ZH",
        "domain": "Hukum Maritim Internasional",
        "source_text": "Negara pantai memiliki hak berdaulat untuk mengeksplorasi dan mengelola sumber daya hayati di zona ekonomi eksklusif sesuai hukum laut.",
        "submission_text": "依据联合国海洋法公约，沿海国在其专属经济区内享有勘探、开发及养护自然生物资源的主权权利。",
        "is_plagiarized": True,
        "source_lang": "id",
        "submission_lang": "zh",
        "description": "Hak berdaulat ZEE hukum laut UNCLOS."
    },
    {
        "case_id": "XLN-ZH-010",
        "pair": "ID-ZH",
        "domain": "Kecerdasan Buatan / Visi Komputer",
        "source_text": "Algoritma deteksi objek berbasis transformator visual menggabungkan tokenisasi citra spasial dengan lapisan atensi penuh.",
        "submission_text": "基于视觉Transformer的目标检测算法将图像划分为空间序列片段，并通过全局注意力机制进行特征关联。",
        "is_plagiarized": True,
        "source_lang": "id",
        "submission_lang": "zh",
        "description": "Vision Transformer (ViT) deteksi citra."
    },

    # =========================================================================
    # 4. INDONESIA <-> BELANDA (ID <-> NL) - 10 KASUS (Aksara Latin)
    # Karakteristik: Hukum Perdata (BW), Hukum Pidana (WvS), Sejarah Agraria Kolonial.
    # =========================================================================
    {
        "case_id": "XLN-NL-001",
        "pair": "ID-NL",
        "domain": "Hukum Perikatan Perdata",
        "source_text": "Semua persetujuan yang dibuat secara sah berlaku sebagai undang-undang bagi mereka yang membuatnya dan tidak dapat ditarik kembali secara sepihak.",
        "submission_text": "Alle wettiglijk gemaakte overeenkomsten strekken degenen die dezelve hebben aangegaan tot wet en kunnen niet eenzijdig worden herroepen.",
        "is_plagiarized": True,
        "source_lang": "id",
        "submission_lang": "nl",
        "description": "Pasal 1338 KUHPerdata / Artikel 1374 oud-BW Belanda."
    },
    {
        "case_id": "XLN-NL-002",
        "pair": "ID-NL",
        "domain": "Hukum Perbuatan Melawan Hukum",
        "source_text": "Tiap perbuatan yang melanggar hukum dan membawa kerugian kepada orang lain mewajibkan orang yang menimbulkan kerugian itu mengganti kerugian tersebut.",
        "submission_text": "Elke onrechtmatige daad die aan een ander schade toebrengt, verplicht degene door wiens schuld die schade is ontstaan, om deze te vergoeden.",
        "is_plagiarized": True,
        "source_lang": "id",
        "submission_lang": "nl",
        "description": "Pasal 1365 KUHPerdata (onrechtmatige daad / PMH)."
    },
    {
        "case_id": "XLN-NL-003",
        "pair": "ID-NL",
        "domain": "Sejarah Agraria Hindia Belanda",
        "source_text": "Undang-undang agraria tahun delapan belas tujuh puluh membuka perkebunan skala besar bagi modal swasta Eropa melalui hak sewa tanah jangka panjang.",
        "submission_text": "De Agrarische Wet van achttienhonderdzeventig stelde de exploitatie van grote plantages open voor Europees particulier kapitaal middels erfpachtrechten.",
        "is_plagiarized": True,
        "source_lang": "id",
        "submission_lang": "nl",
        "description": "Agrarische Wet 1870 dan hak erfpacht perkebunan."
    },
    {
        "case_id": "XLN-NL-004",
        "pair": "ID-NL",
        "domain": "Hukum Pidana / Asas Legalitas",
        "source_text": "Tiada suatu perbuatan dapat dipidana kecuali atas kekuatan aturan pidana dalam perundang-undangan yang telah ada sebelum perbuatan dilakukan.",
        "submission_text": "Geen feit is strafbaar dan uit kracht van een daaraan voorafgegane wettelijke strafbepaling.",
        "is_plagiarized": True,
        "source_lang": "id",
        "submission_lang": "nl",
        "description": "Pasal 1 KUHP / Artikel 1 Wetboek van Strafrecht (Nullum delictum)."
    },
    {
        "case_id": "XLN-NL-005",
        "pair": "ID-NL",
        "domain": "Arsitektur Kolonial Tropis",
        "source_text": "Perancang bangunan Hindia Belanda memadukan fasad neoklasik Eropa dengan ventilasi silang atap tinggi untuk beradaptasi dengan iklim lembap nusantara.",
        "submission_text": "Architecten van de Indische stijl combineerden Europese neoklassieke gevels met doordachte kruisventilatie om het vochtige tropische klimaat te doorstaan.",
        "is_plagiarized": True,
        "source_lang": "id",
        "submission_lang": "nl",
        "description": "Indische bouwstijl adaptasi ventilasi tropis."
    },
    {
        "case_id": "XLN-NL-006",
        "pair": "ID-NL",
        "domain": "Tata Kelola Pengairan / Irigasi",
        "source_text": "Sistem bendung polder Belanda mengandalkan tanggul melingkar dan stasiun pompa bertenaga uap guna mengeringkan dataran rendah pesisir.",
        "submission_text": "Het Nederlandse poldersysteem maakte gebruik van ringdijken en stoomgemalen om overstroomde laaggelegen kustgebieden droog te leggen.",
        "is_plagiarized": True,
        "source_lang": "id",
        "submission_lang": "nl",
        "description": "Sistem polder, tanggul ringdijk, dan stoomgemaal."
    },
    {
        "case_id": "XLN-NL-007",
        "pair": "ID-NL",
        "domain": "Hukum Kepailitan",
        "source_text": "Debitur yang mempunyai dua atau lebih kreditur dan tidak membayar lunas sedikitnya satu utang dapat dinyatakan pailit melalui putusan pengadilan.",
        "submission_text": "Een schuldenaar die twee of meer schuldeisers heeft en ten minste één opeisbare schuld onbetaald laat, kan failliet worden verklaard.",
        "is_plagiarized": True,
        "source_lang": "id",
        "submission_lang": "nl",
        "description": "Syarat kepailitan debitur Faillissementswet."
    },
    {
        "case_id": "XLN-NL-008",
        "pair": "ID-NL",
        "domain": "Historiografi Arsip Dagang VOC",
        "source_text": "Catatan harian kastil Batavia mencatat secara rinci volume pengapalan pala, cengkih, dan lada hitam menuju pelabuhan Amsterdam.",
        "submission_text": "De Daghregisters van het Kasteel Batavia bevatten gedetailleerde verzendlijsten van nootmuskaat, kruidnagel en peper bestemd voor de haven van Amsterdam.",
        "is_plagiarized": True,
        "source_lang": "id",
        "submission_lang": "nl",
        "description": "Daghregister Batavia kargo rempah VOC."
    },
    {
        "case_id": "XLN-NL-009",
        "pair": "ID-NL",
        "domain": "Hukum Tata Negara Komparatif",
        "source_text": "Sistem pemerintahan monarki konstitusional parlementer membatasi peran kepala negara pada fungsi representasi simbolis kenegaraan.",
        "submission_text": "Het parlementaire constitutionele monarchiesysteem beperkt de staatsrechtelijke rol van het staatshoofd tot hoofdzakelijk ceremoniële functies.",
        "is_plagiarized": True,
        "source_lang": "id",
        "submission_lang": "nl",
        "description": "Monarki konstitusional parlementer dan peran kepala negara."
    },
    {
        "case_id": "XLN-NL-010",
        "pair": "ID-NL",
        "domain": "Linguistik Serapan",
        "source_text": "Kosakata administratif bahasa Indonesia menyerap banyak istilah hukum dan perkantoran dari perbendaharaan kata bahasa Belanda pada masa pascakolonial.",
        "submission_text": "De administratieve woordenschat van het Indonesisch heeft na de onafhankelijkheid talrijke juridische en bestuurlijke leenwoorden uit het Nederlands behouden.",
        "is_plagiarized": True,
        "source_lang": "id",
        "submission_lang": "nl",
        "description": "Kata serapan hukum administratif Belanda ke Indonesia."
    },

    # =========================================================================
    # 5. INDONESIA <-> JERMAN (ID <-> DE) - 10 KASUS (Aksara Latin)
    # Karakteristik: Filsafat Hermeneutika, Teknik Mesin, Sosiologi Teoretis, Kedokteran.
    # =========================================================================
    {
        "case_id": "XLN-DE-001",
        "pair": "ID-DE",
        "domain": "Filsafat Hermeneutika",
        "source_text": "Pemahaman teks historis senantiasa berlangsung dalam lingkaran hermeneutika di mana bagian hanya dapat dimengerti dari keseluruhan dan sebaliknya.",
        "submission_text": "Das Verstehen historischer Texte vollzieht sich unausweichlich im hermeneutischen Zirkel, wobei das Einzelne nur aus dem Ganzen begriffen werden kann.",
        "is_plagiarized": True,
        "source_lang": "id",
        "submission_lang": "de",
        "description": "Konsep lingkaran hermeneutika Gadamer dan Schleiermacher."
    },
    {
        "case_id": "XLN-DE-002",
        "pair": "ID-DE",
        "domain": "Teknik Manufaktur Otomotif",
        "source_text": "Sistem injeksi bahan bakar bertekanan rel bersama mengoptimalkan proses pembakaran silinder mesin diesel untuk menekan emisi jelaga jelaga.",
        "submission_text": "Die Common-Rail-Hochdruckeinspritzung optimiert den Verbrennungsablauf im Dieselmotor zur nachhaltigen Reduzierung der Rußpartikelemissionen.",
        "is_plagiarized": True,
        "source_lang": "id",
        "submission_lang": "de",
        "description": "Teknik injeksi Common Rail diesel dan emisi jelaga."
    },
    {
        "case_id": "XLN-DE-003",
        "pair": "ID-DE",
        "domain": "Sosiologi Kritis (Frankfurt School)",
        "source_text": "Rasionalitas instrumental dalam masyarakat industri maju mengubah sarana teknis menjadi alat dominasi ekonomi yang membelenggu emansipasi manusia.",
        "submission_text": "Die instrumentelle Vernunft der forgeschrittenen Industriegesellschaft wandelt technische Mittel in Formen ökonomischer Herrschaft um, die menschliche Emanzipation verhindern.",
        "is_plagiarized": True,
        "source_lang": "id",
        "submission_lang": "de",
        "description": "Teori rasionalitas instrumental Horkheimer dan Adorno."
    },
    {
        "case_id": "XLN-DE-004",
        "pair": "ID-DE",
        "domain": "Fisika Kuantum",
        "source_text": "Prinsip ketidakpastian menyatakan bahwa posisi dan momentum suatu partikel subatomik tidak dapat diukur secara simultan dengan ketelitian sempurna.",
        "submission_text": "Die Unschärferelation besagt, dass Ort und Impuls eines subatomaren Teilchens prinzipiell nicht gleichzeitig mit beliebiger Genauigkeit gemessen werden können.",
        "is_plagiarized": True,
        "source_lang": "id",
        "submission_lang": "de",
        "description": "Prinsip ketidakpastian Heisenberg posisi dan momentum."
    },
    {
        "case_id": "XLN-DE-005",
        "pair": "ID-DE",
        "domain": "Psikologi Eksperimental",
        "source_text": "Persepsi bentuk visual Gestalt menegaskan bahwa keseluruhan perseptual memiliki kualitas yang berbeda secara mendasar dari sekadar penjumlahan bagian-bagiannya.",
        "submission_text": "Die Gestaltpsychologie postuliert, dass das wahrgenommene Ganze von grundlegend anderer Qualität ist als die bloße Summe seiner Einzelteile.",
        "is_plagiarized": True,
        "source_lang": "id",
        "submission_lang": "de",
        "description": "Prinsip dasar Gestaltpsychologie."
    },
    {
        "case_id": "XLN-DE-006",
        "pair": "ID-DE",
        "domain": "Hukum Perdata Komparatif (BGB)",
        "source_text": "Prinsip abstraksi dalam hukum benda memisahkan keabsahan perjanjian obligatoir pemindahan hak milik dari tindakan penyerahan kebendaan secara yuridis.",
        "submission_text": "Das zivilrechtliche Abstraktionsprinzip trennt die rechtliche Wirksamkeit des schuldrechtlichen Verpflichtungsgeschäfts strikt vom dinglichen Verfügungsgeschäft.",
        "is_plagiarized": True,
        "source_lang": "id",
        "submission_lang": "de",
        "description": "Abstraktionsprinzip dalam BGB Jerman."
    },
    {
        "case_id": "XLN-DE-007",
        "pair": "ID-DE",
        "domain": "Teknik Otomasi Industri",
        "source_text": "Kontroler logika terprogram dirancang khusus untuk mengeksekusi instruksi kontrol sekuensial pada lini perakitan pabrik berskala masif.",
        "submission_text": "Speicherprogrammierbare Steuerungen sind speziell für die präzise Ablaufsteuerung industrieller Automatisierungsprozesse in Fertigungsstraßen konzipiert.",
        "is_plagiarized": True,
        "source_lang": "id",
        "submission_lang": "de",
        "description": "SPS (Speicherprogrammierbare Steuerung) otomasi pabrik."
    },
    {
        "case_id": "XLN-DE-008",
        "pair": "ID-DE",
        "domain": "Imunologi / Biologi Molekuler",
        "source_text": "Antibodi monoklonal mengenali epitop antigenik spesifik pada permukaan sel kanker untuk memicu respons fagositosis oleh makrofag.",
        "submission_text": "Monoklonale Antikörper binden hochspezifisch an Zielantigene auf der Oberfläche maligner Tumorzellen und induzieren deren Zerstörung durch Makrophagen.",
        "is_plagiarized": True,
        "source_lang": "id",
        "submission_lang": "de",
        "description": "Mekanisme pengikatan antibodi monoklonal pada antigen."
    },
    {
        "case_id": "XLN-DE-009",
        "pair": "ID-DE",
        "domain": "Sejarah Filsafat Modern",
        "source_text": "Imperatif kategoris menuntut setiap agen moral untuk bertindak semata-mata berdasarkan maksim yang sekaligus dapat dikehendaki menjadi hukum universal.",
        "submission_text": "Der kategorische Imperativ fordert, nur nach derjenigen Maxime zu handeln, von der man wollen kann, dass sie ein allgemeines Gesetz werde.",
        "is_plagiarized": True,
        "source_lang": "id",
        "submission_lang": "de",
        "description": "Etika deontologis imperatif kategoris Immanuel Kant."
    },
    {
        "case_id": "XLN-DE-010",
        "pair": "ID-DE",
        "domain": "Teknologi Metalurgi Presisi",
        "source_text": "Perlakuan panas pendinginan cepat dan penempaan ulang menghasilkan struktur mikro martensit berkekuatan tarik tinggi pada paduan baja perkakas.",
        "submission_text": "Das thermische Härten und anschließende Anlassen erzeugt ein hochfestes martensitisches Gefüge in legierten Werkzeugstählen.",
        "is_plagiarized": True,
        "source_lang": "id",
        "submission_lang": "de",
        "description": "Metalurgi perlakuan panas baja martensit."
    },

    # =========================================================================
    # 6. NEGATIVE CONTROLS (KONTROL BERSIH LINTAS BAHASA) - 10 KASUS
    # Karakteristik: Teks bahasa asing murni tentang topik acak yang TIDAK ADA
    # kaitannya dengan teks rujukan Indonesia. Diuji untuk memastikan model
    # tidak menimbulkan False Positive (Alarm Palsu) lintas bahasa.
    # =========================================================================
    {
        "case_id": "XLN-NEG-001",
        "pair": "EN-CLEAN",
        "domain": "Astrofisika Radio",
        "source_text": "Budidaya tanaman kelapa sawit di lahan gambut memerlukan pengaturan tata kelola tata air saluran sekunder untuk mencegah kekeringan gambut.",
        "submission_text": "Fast radio bursts are intense millisecond-duration pulses of coherent radio emission originating from cosmological distances in distant galaxies.",
        "is_plagiarized": False,
        "source_lang": "id",
        "submission_lang": "en",
        "description": "Kontrol Bersih: Sawit gambut Indonesia vs Astrofisika radio Inggris."
    },
    {
        "case_id": "XLN-NEG-002",
        "pair": "EN-CLEAN",
        "domain": "Seni Rupa Abad Pertengahan",
        "source_text": "Evaluasi kinerja keuangan bank perkreditan rakyat diukur berdasarkan rasio kecukupan modal dan pengembalian aset neto tahunan.",
        "submission_text": "Gothic architectural vaults in fourteenth-century cathedrals redistributed lateral roof thrusts through flying buttresses onto external piers.",
        "is_plagiarized": False,
        "source_lang": "id",
        "submission_lang": "en",
        "description": "Kontrol Bersih: Rasio keuangan BPR vs Katedral gotik Inggris."
    },
    {
        "case_id": "XLN-NEG-003",
        "pair": "AR-CLEAN",
        "domain": "Meteorologi Gurun",
        "source_text": "Pemberdayaan kelompok tani jagung hibrida dilakukan melalui pelatihan budidaya organik dan fasilitasi pinjaman modal mikro pedesaan.",
        "submission_text": "تتميز العواصف الرملية في الصحراء الكبرى بانتقال جزيئات الغبار الدقيقة عبر طبقات الغلاف الجوي لمسافات تتجاوز المحيط الأطلسي.",
        "is_plagiarized": False,
        "source_lang": "id",
        "submission_lang": "ar",
        "description": "Kontrol Bersih: Tani jagung Indonesia vs Badai pasir Sahara Arab."
    },
    {
        "case_id": "XLN-NEG-004",
        "pair": "AR-CLEAN",
        "domain": "Geologi Hidrologi",
        "source_text": "Pengembangan kurikulum pendidikan kejuruan harus diselaraskan dengan kebutuhan kompetensi industri manufaktur di kawasan industri baru.",
        "submission_text": "تعتمد خزانات المياه الجوفية العميقة في شبه الجزيرة العربية على طبقات الحجر الرملي الأحفوري غير المتجدد.",
        "is_plagiarized": False,
        "source_lang": "id",
        "submission_lang": "ar",
        "description": "Kontrol Bersih: Vokasi industri vs Akuifer air fosil Arab."
    },
    {
        "case_id": "XLN-NEG-005",
        "pair": "ZH-CLEAN",
        "domain": "Arkeologi Keramik Kuno",
        "source_text": "Manajemen risiko kredit usaha rakyat bertujuan memitigasi potensi gagal bayar nasabah sektor mikro pada masa pemulihan ekonomi.",
        "submission_text": "宋代五大名窑出产的青瓷以其温润如玉的釉面质感和含蓄内敛的审美风格著称于世。",
        "is_plagiarized": False,
        "source_lang": "id",
        "submission_lang": "zh",
        "description": "Kontrol Bersih: Kredit mikro KUR vs Keramik Dinasti Song Mandarin."
    },
    {
        "case_id": "ZH-CLEAN-006",
        "pair": "ZH-CLEAN",
        "domain": "Seismologi Lempeng",
        "source_text": "Strategi pemasaran produk kopi robusta lokal ditingkatkan melalui kemasan ramah lingkungan dan promosi digital di platform media sosial.",
        "submission_text": "青藏高原北缘断裂带的构造活动特征表现为强烈的地壳缩短变形与逆冲断层发育。",
        "is_plagiarized": False,
        "source_lang": "id",
        "submission_lang": "zh",
        "description": "Kontrol Bersih: Pemasaran kopi lokal vs Sesar tektonik Tibet Mandarin."
    },
    {
        "case_id": "XLN-NEG-007",
        "pair": "NL-CLEAN",
        "domain": "Tata Rias Lanskap Belanda",
        "source_text": "Analisis mutu beton bertulang pada struktur jembatan bentang panjang memerlukan pengujian ultrasonik pulsa untuk mendeteksi keretakan mikro.",
        "submission_text": "De traditionele Hollandse tulpenbollenteelt vereist zandige geestgronden met een nauwkeurig gereguleerde grondwaterstand in het vroege voorjaar.",
        "is_plagiarized": False,
        "source_lang": "id",
        "submission_lang": "nl",
        "description": "Kontrol Bersih: Mutu beton jembatan vs Budidaya tulip Belanda."
    },
    {
        "case_id": "XLN-NEG-008",
        "pair": "NL-CLEAN",
        "domain": "Seni Lukis Klasik Belanda",
        "source_text": "Penetapan tarif batas atas tiket transportasi antarkota dilakukan pemerintah untuk menjamin keterjangkauan daya beli masyarakat umum.",
        "submission_text": "Rembrandt van Rijn paste meesterlijke clair-obscur technieken toe in de Nachtwacht om dramatische lichtcontrasten te creëren.",
        "is_plagiarized": False,
        "source_lang": "id",
        "submission_lang": "nl",
        "description": "Kontrol Bersih: Tarif tiket bus vs Teknik lukis Rembrandt Belanda."
    },
    {
        "case_id": "XLN-NEG-009",
        "pair": "DE-CLEAN",
        "domain": "Kehutanan Hutan Hitam",
        "source_text": "Pengaruh penambahan limbah abu terbang batubara pada campuran semen hidrolis meningkatkan ketahanan kimiawi terhadap sulfat.",
        "submission_text": "Die montane Vegetation des Schwarzwaldes wird vorwiegend von Weißtannen und Fichten auf sauren Buntsandsteinböden geprägt.",
        "is_plagiarized": False,
        "source_lang": "id",
        "submission_lang": "de",
        "description": "Kontrol Bersih: Abu terbang semen vs Kehutanan Schwarzwald Jerman."
    },
    {
        "case_id": "XLN-NEG-010",
        "pair": "DE-CLEAN",
        "domain": "Klimatologi Pegunungan Alpen",
        "source_text": "Tata kelola retribusi pasar tradisional diarahkan untuk membiayai pemeliharaan kebersihan dan peremajaan sarana sanitasi pedagang.",
        "submission_text": "Der Föhneffekt in den nördlichen Alpen führt zu plötzlichen Temperaturanstiegen und extrem rascher Luftabtrocknung in den Alpentälern.",
        "is_plagiarized": False,
        "source_lang": "id",
        "submission_lang": "de",
        "description": "Kontrol Bersih: Retribusi pasar vs Angin Föhn Pegunungan Alpen Jerman."
    }
]


def build_and_save():
    output_dir = PROJECT_ROOT / "datasets"
    output_dir.mkdir(parents=True, exist_ok=True)
    target_file = output_dir / "artilik_cross_lingual_benchmark_dataset.json"

    with open(target_file, "w", encoding="utf-8") as f:
        json.dump(CROSS_LINGUAL_CASES, f, ensure_ascii=False, indent=2)

    print(f"[✓] Berhasil membuat dataset lintas bahasa di {target_file}")
    print(f"    Total kasus: {len(CROSS_LINGUAL_CASES)}")
    pairs = {}
    for c in CROSS_LINGUAL_CASES:
        pairs[c["pair"]] = pairs.get(c["pair"], 0) + 1
    for p, cnt in pairs.items():
        print(f"    - Pasangan {p}: {cnt} kasus")


if __name__ == "__main__":
    build_and_save()
