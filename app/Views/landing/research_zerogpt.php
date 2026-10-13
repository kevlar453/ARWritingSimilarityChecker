<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover" />
  <meta name="color-scheme" content="light dark" />
  <meta name="theme-color" content="#f2efe8" media="(prefers-color-scheme: light)" />
  <meta name="theme-color" content="#11171a" media="(prefers-color-scheme: dark)" />
  <meta name="description" content="Perbandingan mendalam AR Tilik vs ZeroGPT: Analisis berbasis riset independen mengenai perbedaan audit bukti integritas naskah vs deteksi probabilitas AI." />
  <link rel="icon" href="<?= base_url('images/logo.png') ?>" type="image/png" />
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@400;500;600;700&family=Newsreader:ital,opsz,wght@0,6..72,400;0,6..72,500;0,6..72,600;0,6..72,700;1,6..72,400&display=swap" rel="stylesheet" />
  <title>Perbandingan AR Tilik vs ZeroGPT — Analisis Berbasis Riset &amp; Whitepaper Integritas</title>
  <style>
    :root {
      color-scheme: light dark;
      --bg: #f2efe8;
      --surface: #fffdf8;
      --surface-2: #e8e4dc;
      --ink: #142027;
      --muted: #506067;
      --line: #c7c2b8;
      --navy: #173e52;
      --navy-soft: #d5e3e8;
      --orange: #e7562e;
      --orange-soft: #f5d8cd;
      --green: #2e6a56;
      --green-soft: #d7e7df;
      --yellow: #bd7b18;
      --shadow: 0 20px 60px rgba(20,32,39,.08);
      --sans: "IBM Plex Sans", ui-sans-serif, sans-serif;
      --serif: "Newsreader", Georgia, serif;
    }
    @media (prefers-color-scheme: dark) {
      :root {
        --bg: #11171a;
        --surface: #172126;
        --surface-2: #202c31;
        --ink: #f2eee5;
        --muted: #b5c0c3;
        --line: #3b494e;
        --navy: #8bc1d5;
        --navy-soft: #203d48;
        --orange: #ff7b55;
        --orange-soft: #4a2a23;
        --green: #83c9ab;
        --green-soft: #203d33;
        --yellow: #efb658;
        --shadow: 0 24px 70px rgba(0,0,0,.25);
      }
    }
    * { box-sizing: border-box; }
    html { scroll-behavior: smooth; }
    body {
      margin: 0;
      background: var(--bg);
      color: var(--ink);
      font-family: var(--sans);
      line-height: 1.6;
      text-rendering: optimizeLegibility;
    }
    a { color: inherit; }
    a:focus-visible, button:focus-visible { outline: 3px solid var(--orange); outline-offset: 3px; }
    .wrap { width: min(1160px, calc(100% - 40px)); margin: 0 auto; }

    /* Top Platform Header Bar */
    .top-bar {
      background: var(--surface-2);
      border-bottom: 1px solid var(--line);
      font-size: .84rem;
      padding: 10px 0;
    }
    .top-bar-inner {
      display: flex;
      justify-content: space-between;
      align-items: center;
      flex-wrap: wrap;
      gap: 12px;
    }
    .top-brand {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      font-weight: 700;
      text-decoration: none;
      letter-spacing: -.02em;
    }
    .top-brand img { width: 22px; height: 22px; border-radius: 4px; }
    .top-actions {
      display: flex;
      align-items: center;
      gap: 16px;
    }
    .top-link {
      text-decoration: none;
      color: var(--muted);
      font-weight: 600;
      transition: color .15s ease;
    }
    .top-link:hover { color: var(--orange); }
    .btn-pill {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 5px 14px;
      border-radius: 9999px;
      background: var(--navy);
      color: #ffffff !important;
      text-decoration: none;
      font-weight: 600;
      font-size: .8rem;
      transition: opacity .15s ease, transform .15s ease;
    }
    .btn-pill:hover { opacity: .9; transform: translateY(-1px); }

    .mast {
      display: grid;
      grid-template-columns: minmax(0, 1.35fr) minmax(280px, .65fr);
      gap: clamp(32px, 7vw, 96px);
      padding: clamp(48px, 8vw, 100px) 0 54px;
      align-items: end;
      border-bottom: 1px solid var(--line);
    }
    .stamp {
      display: inline-flex;
      gap: 8px;
      align-items: center;
      color: var(--muted);
      font-size: .83rem;
      font-weight: 600;
      letter-spacing: .04em;
      margin-bottom: 20px;
    }
    .stamp::before { content: ""; width: 25px; height: 3px; background: var(--orange); }
    h1, h2, h3, p { margin-top: 0; }
    h1 {
      font-family: var(--serif);
      font-size: clamp(3.15rem, 7.4vw, 6.9rem);
      line-height: .91;
      letter-spacing: -.052em;
      max-width: 880px;
      margin-bottom: 30px;
    }
    h1 em { color: var(--orange); font-style: normal; }
    .lede {
      max-width: 760px;
      font-family: var(--serif);
      font-size: clamp(1.35rem, 2.4vw, 2rem);
      line-height: 1.25;
      margin: 0;
    }
    .verdict-card {
      background: var(--navy);
      color: #f6f2e8;
      padding: 28px;
      box-shadow: var(--shadow);
    }
    @media (prefers-color-scheme: dark) { .verdict-card { color: #102128; } }
    .verdict-card strong { display: block; font-size: 1.05rem; margin-bottom: 12px; }
    .verdict-card p { margin: 0; line-height: 1.5; }
    .verdict-mark {
      display: block;
      font-family: var(--serif);
      font-size: 4.5rem;
      line-height: .7;
      color: var(--orange);
      margin-bottom: 22px;
    }
    .quick-nav {
      position: sticky;
      top: 0;
      z-index: 20;
      background: color-mix(in srgb, var(--bg) 92%, transparent);
      backdrop-filter: blur(10px);
      border-bottom: 1px solid var(--line);
    }
    .quick-nav .wrap { display: flex; gap: 8px 24px; flex-wrap: wrap; padding: 12px 0; }
    .quick-nav a { text-decoration: none; color: var(--muted); font-size: .88rem; font-weight: 600; }
    .quick-nav a:hover { color: var(--orange); }
    section { padding: clamp(64px, 9vw, 112px) 0; }
    .section-head { display: grid; grid-template-columns: minmax(160px, .42fr) minmax(0, 1fr); gap: 30px; align-items: start; margin-bottom: 42px; }
    .num { color: var(--orange); font-weight: 600; font-variant-numeric: tabular-nums; }
    h2 {
      font-family: var(--serif);
      font-size: clamp(2.35rem, 5vw, 4.6rem);
      line-height: 1;
      letter-spacing: -.035em;
      margin-bottom: 16px;
    }
    .section-intro { color: var(--muted); max-width: 760px; margin-bottom: 0; font-size: 1.08rem; }
    .comparison {
      display: grid;
      grid-template-columns: .85fr 1fr 1fr;
      border-top: 1px solid var(--line);
    }
    .comparison > div { padding: 21px 18px; border-bottom: 1px solid var(--line); }
    .comparison .label { color: var(--muted); font-size: .85rem; font-weight: 600; }
    .comparison .colhead { font-family: var(--serif); font-size: 1.6rem; padding-top: 27px; }
    .comparison .ar { background: var(--navy-soft); }
    .comparison .zero { background: var(--orange-soft); }
    .comparison p { margin: 0; }
    .comparison strong { color: var(--ink); }
    .callout {
      margin-top: 34px;
      display: grid;
      grid-template-columns: auto 1fr;
      gap: 18px;
      padding: 24px;
      background: var(--surface);
      box-shadow: var(--shadow);
      max-width: 900px;
    }
    .callout b { color: var(--orange); font-size: 1.7rem; line-height: 1; }
    .callout p { margin: 0; }
    .evidence-band { background: var(--surface); }
    .claim-split { display: grid; grid-template-columns: .74fr 1.26fr; gap: 24px; align-items: start; }
    .vendor-claim {
      min-height: 420px;
      padding: clamp(28px, 5vw, 52px);
      background: var(--orange);
      color: #fff9f2;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
    }
    .vendor-claim .big { font-family: var(--serif); font-size: clamp(5rem, 10vw, 8.5rem); line-height: .75; letter-spacing: -.05em; font-variant-numeric: tabular-nums; }
    .vendor-claim small { font-size: .87rem; max-width: 310px; line-height: 1.5; }
    .studies { padding: 8px 0 0; }
    .study {
      display: grid;
      grid-template-columns: 128px 1fr;
      gap: 22px;
      padding: 20px 0;
      border-bottom: 1px solid var(--line);
      align-items: baseline;
    }
    .study:first-child { border-top: 1px solid var(--line); }
    .study .metric { font-family: var(--serif); font-size: 2.35rem; line-height: 1; color: var(--navy); font-variant-numeric: tabular-nums; }
    .study p { margin: 0; }
    .study small { display: block; color: var(--muted); margin-top: 6px; }
    .source-link { text-decoration-color: var(--orange); text-decoration-thickness: 2px; text-underline-offset: 3px; }
    .source-link:hover { color: var(--orange); }
    .caution { margin-top: 28px; padding: 18px 22px; border: 1px solid var(--yellow); color: var(--ink); }
    .caution strong { color: var(--yellow); }
    .forensics-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; }
    .engine {
      padding: 28px;
      background: var(--surface);
      border-top: 5px solid var(--navy);
    }
    .engine.orange { border-color: var(--orange); }
    .engine h3 { font-family: var(--serif); font-size: 2rem; line-height: 1.08; margin-bottom: 14px; }
    .flow { list-style: none; margin: 28px 0 0; padding: 0; counter-reset: flow; }
    .flow li { counter-increment: flow; display: grid; grid-template-columns: 34px 1fr; gap: 12px; padding: 12px 0; border-top: 1px solid var(--line); }
    .flow li::before { content: counter(flow, decimal-leading-zero); color: var(--orange); font-weight: 600; font-size: .82rem; }
    .audit-note { color: var(--muted); font-size: .9rem; margin-top: 20px; }
    .citation-case {
      margin-top: 24px;
      background: var(--green-soft);
      padding: 28px;
      display: grid;
      grid-template-columns: auto 1fr;
      gap: 20px;
      align-items: start;
    }
    .seal { width: 54px; height: 54px; border: 2px solid var(--green); border-radius: 50%; display: grid; place-items: center; color: var(--green); font-weight: 600; }
    .citation-case h3 { margin-bottom: 8px; }
    .citation-case p { margin-bottom: 8px; }
    .citation-case small { color: var(--muted); }
    .limits { background: var(--navy); color: #f6f2e8; }
    @media (prefers-color-scheme: dark) { .limits { color: #102128; } }
    .limits .num, .limits h2 { color: inherit; }
    .limits .section-intro { color: color-mix(in srgb, currentColor 72%, transparent); }
    .risk-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1px; background: color-mix(in srgb, currentColor 25%, transparent); border: 1px solid color-mix(in srgb, currentColor 25%, transparent); }
    .risk { background: var(--navy); padding: 28px; min-height: 235px; }
    .risk .tag { color: var(--orange); font-weight: 600; font-size: .82rem; display: block; margin-bottom: 18px; }
    .risk h3 { font-family: var(--serif); font-size: 1.65rem; line-height: 1.1; }
    .risk p { margin: 0; opacity: .84; }
    .decision-tool { display: grid; grid-template-columns: .55fr 1.45fr; gap: clamp(24px, 7vw, 80px); align-items: start; }
    .role-buttons { display: grid; gap: 8px; }
    .role-buttons button {
      appearance: none;
      border: 1px solid var(--line);
      background: transparent;
      color: var(--ink);
      font: 600 .92rem var(--sans);
      padding: 14px 16px;
      text-align: left;
      cursor: pointer;
    }
    .role-buttons button:hover { border-color: var(--orange); }
    .role-buttons button[aria-selected="true"] { background: var(--ink); color: var(--bg); border-color: var(--ink); }
    .advice { min-height: 310px; background: var(--surface); padding: clamp(28px, 5vw, 54px); box-shadow: var(--shadow); }
    .advice .mini { color: var(--orange); font-weight: 600; font-size: .84rem; }
    .advice h3 { font-family: var(--serif); font-size: clamp(2rem, 4vw, 3.5rem); line-height: 1.02; margin: 10px 0 18px; }
    .advice p { max-width: 690px; }
    .advice .action { margin-top: 24px; padding-top: 18px; border-top: 1px solid var(--line); font-weight: 600; }
    .final-word { background: var(--orange-soft); }
    .final-grid { display: grid; grid-template-columns: minmax(0, 1.25fr) minmax(260px, .75fr); gap: 52px; align-items: start; }
    .final-grid blockquote { margin: 0; font: 500 clamp(2rem, 4.8vw, 4.5rem)/1.02 var(--serif); letter-spacing: -.035em; }
    .final-grid aside { border-top: 4px solid var(--orange); padding-top: 20px; }
    .final-grid aside p:last-child { margin-bottom: 0; }
    
    /* Dedicated Actions Band */
    .cta-band {
      background: var(--surface);
      border-top: 1px solid var(--line);
      border-bottom: 1px solid var(--line);
      padding: 56px 0;
      text-align: center;
    }
    .cta-box {
      max-width: 720px;
      margin: 0 auto;
    }
    .cta-box h3 {
      font-family: var(--serif);
      font-size: clamp(1.8rem, 3.5vw, 2.8rem);
      line-height: 1.1;
      margin-bottom: 12px;
    }
    .cta-box p {
      color: var(--muted);
      font-size: 1.05rem;
      margin-bottom: 24px;
    }
    .cta-buttons {
      display: flex;
      justify-content: center;
      gap: 14px;
      flex-wrap: wrap;
    }
    .btn-main {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 12px 24px;
      background: var(--orange);
      color: #ffffff !important;
      text-decoration: none;
      font-weight: 600;
      font-size: .95rem;
      border-radius: 6px;
      box-shadow: 0 4px 14px rgba(231,86,46,.25);
      transition: transform .15s ease, opacity .15s ease;
    }
    .btn-main:hover { transform: translateY(-2px); opacity: .95; }
    .btn-secondary {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 12px 24px;
      background: transparent;
      border: 1px solid var(--line);
      color: var(--ink) !important;
      text-decoration: none;
      font-weight: 600;
      font-size: .95rem;
      border-radius: 6px;
      transition: border-color .15s ease;
    }
    .btn-secondary:hover { border-color: var(--ink); }

    .sources { padding-bottom: 48px; }
    .sources h2 { font-size: 2.4rem; }
    .source-list { display: grid; grid-template-columns: repeat(2, 1fr); gap: 0 34px; padding: 0; margin: 0; list-style: none; }
    .source-list li { padding: 16px 0; border-top: 1px solid var(--line); font-size: .9rem; color: var(--muted); }
    .source-list a { color: var(--ink); font-weight: 600; }
    .method-note { margin-top: 30px; color: var(--muted); font-size: .85rem; max-width: 840px; }

    @media (max-width: 820px) {
      .mast, .section-head, .claim-split, .forensics-grid, .decision-tool, .final-grid { grid-template-columns: 1fr; }
      .mast { align-items: start; }
      .verdict-card { max-width: 540px; }
      .section-head { gap: 10px; }
      .risk-grid { grid-template-columns: 1fr; }
      .source-list { grid-template-columns: 1fr; }
      .comparison { grid-template-columns: .7fr 1fr 1fr; font-size: .92rem; }
      .comparison > div { padding: 16px 10px; }
      .comparison .colhead { font-size: 1.3rem; }
      .vendor-claim { min-height: 340px; }
    }
    @media (max-width: 560px) {
      .wrap { width: min(100% - 28px, 1160px); }
      h1 { font-size: clamp(3rem, 17vw, 4.6rem); }
      .quick-nav .wrap { flex-wrap: nowrap; overflow: hidden; gap: 15px; }
      .quick-nav a:nth-child(4), .quick-nav a:nth-child(5) { display: none; }
      .comparison { display: block; border: 0; }
      .comparison .blank { display: none; }
      .comparison .colhead { position: sticky; top: 47px; z-index: 2; padding: 13px 14px; }
      .comparison .label { padding: 25px 0 5px; border: 0; }
      .comparison .ar, .comparison .zero { padding: 14px; border: 0; margin-bottom: 7px; }
      .comparison .ar::before, .comparison .zero::before { display: block; font-size: .7rem; font-weight: 600; margin-bottom: 4px; }
      .comparison .ar::before { content: "AR TILIK"; color: var(--navy); }
      .comparison .zero::before { content: "ZEROGPT"; color: var(--orange); }
      .comparison .colhead { display: none; }
      .study { grid-template-columns: 92px 1fr; gap: 14px; }
      .study .metric { font-size: 1.9rem; }
      .citation-case { grid-template-columns: 1fr; }
      .callout { grid-template-columns: 1fr; }
    }
    @media (prefers-reduced-motion: reduce) { html { scroll-behavior: auto; } }
  </style>
</head>
<body>

  <!-- Top Bar Navigating back to AR Tilik Main Platform -->
  <aside class="top-bar" aria-label="Navigasi Platform">
    <div class="wrap top-bar-inner">
      <a href="<?= base_url() ?>" class="top-brand">
        <img src="<?= base_url('images/logo.png') ?>" alt="AR Tilik Logo" width="22" height="22" />
        <span>AR Tilik</span>
        <span style="font-weight: normal; color: var(--muted);">&bull; Whitepaper Riset</span>
      </a>
      <div class="top-actions">
        <a href="<?= base_url() ?>" class="top-link">&larr; Kembali ke Beranda</a>
        <a href="<?= base_url('dashboard') ?>" class="btn-pill">Buka Aplikasi &rarr;</a>
      </div>
    </div>
  </aside>

  <header class="wrap mast">
    <div>
      <div class="stamp">Analisis berbasis riset &bull; Edisi 2026</div>
      <h1>Bukan lomba siapa paling pintar <em>menebak AI.</em></h1>
      <p class="lede">AR Tilik dan ZeroGPT menjawab dua pertanyaan berbeda. ZeroGPT menaksir “apakah teks ini dibuat AI?”; AR Tilik memeriksa “apa yang dapat diverifikasi dari naskah ini?”</p>
    </div>
    <aside class="verdict-card" aria-label="Putusan singkat">
      <span class="verdict-mark">&ldquo;</span>
      <strong>Putusan singkat</strong>
      <p>Untuk skrining naskah jurnal Indonesia, saya lebih percaya audit bukti AR Tilik daripada skor probabilitas AI ZeroGPT. Namun AR Tilik bukan pengganti detektor AI—dan belum punya benchmark komparatif skala besar.</p>
    </aside>
  </header>

  <nav class="quick-nav" aria-label="Navigasi analisis">
    <div class="wrap">
      <a href="#beda">Perbedaan dasar</a>
      <a href="#bukti">Bukti angka</a>
      <a href="#mesin">Cara kerja</a>
      <a href="#batas">Batasan</a>
      <a href="#pilih">Rekomendasi</a>
      <a href="#aksi">Tindakan</a>
    </div>
  </nav>

  <main>
    <section id="beda" class="wrap">
      <div class="section-head">
        <div class="num">01 — Peta masalah</div>
        <div>
          <h2>Perbandingan yang adil dimulai dari objek yang diukur.</h2>
          <p class="section-intro">Menyebut keduanya “detektor AI” menyamarkan perbedaan terpenting: satu mengklasifikasi gaya bahasa, satu mengaudit kemiripan dan rujukan.</p>
        </div>
      </div>

      <div class="comparison" role="table" aria-label="Matriks perbandingan AR Tilik dan ZeroGPT">
        <div class="blank"></div><div class="colhead ar">AR Tilik</div><div class="colhead zero">ZeroGPT</div>
        <div class="label">Pertanyaan utama</div>
        <div class="ar"><p>Ada <strong>bukti kemiripan, parafrasa semantik, atau rujukan bermasalah</strong>?</p></div>
        <div class="zero"><p>Seberapa mungkin teks ini <strong>dihasilkan AI</strong>?</p></div>
        <div class="label">Jenis keluaran</div>
        <div class="ar"><p>Empat pilar metrik, vonis risiko, pola temuan, dan audit rujukan.</p></div>
        <div class="zero"><p>Skor/kelas AI, penyorotan tingkat kalimat, termasuk sinyal campuran.</p></div>
        <div class="label">Sifat bukti</div>
        <div class="ar"><p>Dapat ditelusuri ke kemiripan teks dan pangkalan data rujukan.</p></div>
        <div class="zero"><p>Inferensi statistik proprietari; bobot dan dataset evaluasi tidak dibuka.</p></div>
        <div class="label">Bahasa Indonesia</div>
        <div class="ar"><p>Fokus pada naskah akademik Indonesia; didukung korpus ilmiah lokal.</p></div>
        <div class="zero"><p>Diklaim mendukung semua bahasa, tetapi satu studi Indonesia menemukan akurasi 37,5%.</p></div>
        <div class="label">Sitasi fabrikasi AI</div>
        <div class="ar"><p>Ada verifikasi eksternal melalui OpenAlex dan Crossref; lolos uji terkontrol.</p></div>
        <div class="zero"><p>Tidak ditemukan bukti fitur verifikasi keaslian rujukan.</p></div>
        <div class="label">Posisi terbaik</div>
        <div class="ar"><p><strong>Audit integritas naskah.</strong></p></div>
        <div class="zero"><p><strong>Sinyal awal</strong> yang wajib ditinjau manusia.</p></div>
      </div>

      <div class="callout">
        <b>&ne;</b>
        <p><strong>Tidak ada uji head-to-head yang sama.</strong> Data yang tersedia tidak membolehkan kesimpulan “AR Tilik X% lebih akurat”. Yang bisa dinilai adalah ketepatan sasaran: bukti yang dapat diverifikasi lebih cocok untuk keputusan akademik berisiko tinggi daripada dugaan kepengarangan.</p>
      </div>
    </section>

    <section id="bukti" class="evidence-band">
      <div class="wrap">
        <div class="section-head">
          <div class="num">02 — Klaim vs temuan</div>
          <div>
            <h2>Angka ZeroGPT pecah ketika dibawa ke dunia nyata.</h2>
            <p class="section-intro">Klaim internal bukan otomatis salah, tetapi ia belum direplikasi secara independen—dan hasil pada tulisan formal atau penulis non-native jauh lebih buruk.</p>
          </div>
        </div>

        <div class="claim-split">
          <article class="vendor-claim">
            <div>
              <div class="big">98,4%</div>
              <p>akurasi yang dilaporkan ZeroGPT, dengan klaim false positive <strong>&lt;1%</strong>.</p>
            </div>
            <small>Sumber: <a class="source-link" href="https://www.zerogpt.com/deep-analyse" target="_blank" rel="noopener noreferrer">DeepAnalyse&trade; resmi</a>. Dataset evaluasi dan rincian benchmark tidak dipublikasikan.</small>
          </article>

          <div class="studies" aria-label="Ringkasan temuan independen">
            <div class="study">
              <div class="metric">59–74%</div>
              <p><strong>Weber-Wulff et al. (2023)</strong> memberi tiga hasil tergantung metode penilaian: 59% binary, 74% binary-inclusive, dan 67% semi-binary.<small>14 alat, 756 pengujian; DOI 10.1007/s40979-023-00146-z.</small></p>
            </div>
            <div class="study">
              <div class="metric">46,1%</div>
              <p><strong>Perkins et al. (2024)</strong> melaporkan akurasi baseline ZeroGPT 46,1%; 31,3% pada keluaran yang tidak dimanipulasi.<small>Angka dilaporkan dari Table 7 melalui catatan lapangan riset.</small></p>
            </div>
            <div class="study">
              <div class="metric">61,22%</div>
              <p><strong>Liang et al. (2023) - Stanford University</strong> menemukan rata-rata false-positive pada esai TOEFL penulis non-native untuk tujuh detektor, termasuk ZeroGPT; sekitar 5% pada esai penutur asli.<small>Angka 61,22% adalah rerata lintas tujuh detektor, bukan skor ZeroGPT sendirian.</small></p>
            </div>
            <div class="study">
              <div class="metric">37,5%</div>
              <p><strong>Falaq (2026)</strong> menguji 80 teks akademik Bahasa Indonesia. ZeroGPT benar pada 30 dari 80 teks; 32 dari 40 tulisan manusia ditandai AI.<small><a class="source-link" href="https://jurnalcahayaedukasi.com/index.php/jce/article/download/316/219/1288" target="_blank" rel="noopener noreferrer">Baca studi Jurnal Cahaya Edukasi</a> &bull; FPR 80%; FNR 45%.</small></p>
            </div>
          </div>
        </div>

        <div class="caution"><strong>Jangan campur metrik.</strong> Akurasi, false-positive rate, dan false-negative rate mengukur hal berbeda. Rentang 46–74% di atas berasal dari studi dan metode yang berbeda—bukan satu meta-analisis tunggal.</div>
      </div>
    </section>

    <section id="mesin" class="wrap">
      <div class="section-head">
        <div class="num">03 — Di balik keluaran</div>
        <div>
          <h2>Satu membaca pola. Satu mengumpulkan jejak.</h2>
          <p class="section-intro">Perbedaan arsitektur menjelaskan mengapa kedua alat gagal dengan cara yang berbeda.</p>
        </div>
      </div>

      <div class="forensics-grid">
        <article class="engine">
          <h3>AR Tilik: mesin hibrida + audit forensik</h3>
          <p>Pengujian langsung Report #1167 memperlihatkan dekomposisi empat pilar dan jejak yang bisa diperiksa.</p>
          <ol class="flow">
            <li><span>5-gram sekuensial mengukur kecocokan frasa identik.</span></li>
            <li><span>TF-IDF kosinus mengukur kedekatan topik dan istilah leksikal.</span></li>
            <li><span>Pasase terpanjang dan istilah beririsan memberi konteks pembuktian.</span></li>
            <li><span>XGBoost v3.4 menyintesis risiko; Audit Forensik Cerdas mengevaluasi konsistensi logika antar-bab secara terstruktur.</span></li>
            <li><span>OpenAlex dan Crossref memeriksa eksistensi dan integritas rujukan secara eksternal.</span></li>
          </ol>
          <p class="audit-note">Pada naskah uji empiris: exact 0.29%; cosine 24.34%; pasase terpanjang 6 kata; vonis MEMENUHI SYARAT BERSYARAT. Korpus rujukan diverifikasi secara terpadu tanpa kebocoran berkas naskah.</p>
        </article>

        <article class="engine orange">
          <h3>ZeroGPT: DeepAnalyse&trade; yang proprietari</h3>
          <p>Situs resmi menjelaskan pipeline bertingkat, tetapi tidak membuka bobot model atau dataset evaluasinya.</p>
          <ol class="flow">
            <li><span>Macro analysis membaca karakteristik teks secara luas.</span></li>
            <li><span>Sentence analysis menyorot kalimat yang dianggap berpola AI.</span></li>
            <li><span>Micro analysis mencari pola probabilitas halus yang dipelajari.</span></li>
            <li><span>Komponen digabung menjadi skor persentase probabilitas AI.</span></li>
          </ol>
          <p class="audit-note">ZeroGPT mengklaim pelatihan pada teks internet, data pendidikan, dan data sintetis; cakupan 35+ keluarga LLM. Penilaian berpusat pada sifat gaya penulisan, bukan pembuktian keberadaan sumber rujukan. <a class="source-link" href="https://www.zerogpt.com/deep-analyse" target="_blank" rel="noopener noreferrer">Metodologi resmi ZeroGPT</a>.</p>
        </article>
      </div>

      <article class="citation-case">
        <div class="seal">&check;</div>
        <div>
          <h3>Uji sitasi yang membedakan keduanya</h3>
          <p>Dalam audit langsung, rujukan fabrikasi “Pratama &amp; Kusuma (2022)” awalnya lolos pemeriksaan internal. Setelah AR Tilik menambahkan verifikasi eksternal pangkalan data global, rujukan itu ditandai fiktif (sitasi gaib); sedangkan rujukan autentik seperti Gilster (1997) diverifikasi sahih dengan 823 sitasi global.</p>
          <small>Makna praktis: kemiripan internal tidak membuktikan sebuah rujukan benar-benar ada di dunia nyata. Detektor AI murni tidak memeriksa validitas sitasi.</small>
        </div>
      </article>
    </section>

    <section id="batas" class="limits">
      <div class="wrap">
        <div class="section-head">
          <div class="num">04 — Batas kepercayaan</div>
          <div>
            <h2>Jangan berikan kuasa lebih besar daripada buktinya.</h2>
            <p class="section-intro">Kedua alat punya batas nyata. Perbedaannya adalah konsekuensi kesalahannya.</p>
          </div>
        </div>
        <div class="risk-grid">
          <article class="risk">
            <span class="tag">ZEROGPT</span>
            <h3>Tulisan formal bisa tampak “terlalu rapi”.</h3>
            <p>Studi Indonesia menunjukkan bias berat pada prosa akademik manusia: 80% false positive dalam sampel 40 esai autentik.</p>
          </article>
          <article class="risk">
            <span class="tag">ZEROGPT</span>
            <h3>Parafrasa melemahkan sinyal.</h3>
            <p>FAQ ZeroGPT sendiri mengakui paraphrasing dapat mengurangi sebagian sinyal. Bukti sistematis untuk teks campuran manusia+AI juga belum ditemukan.</p>
          </article>
          <article class="risk">
            <span class="tag">AR TILIK</span>
            <h3>Audit produk berfokus pada bukti tekstual.</h3>
            <p>Belum ada benchmark akurasi populasi raksasa. Menghindari vonis biner mutlak demi menjaga asas keadilan akademik (academic fairness).</p>
          </article>
          <article class="risk">
            <span class="tag">AR TILIK</span>
            <h3>Bukan identifikasi kepengarangan.</h3>
            <p>AR Tilik tidak mengklaim tahu isi pikiran penulis. Ia menyajikan gejala yang dapat diverifikasi: frasa identik, sitasi gaib, dan pola metodologi.</p>
          </article>
          <article class="risk">
            <span class="tag">KEDUANYA</span>
            <h3>Temuan bukan vonis.</h3>
            <p>Kemiripan bukan plagiarisme otomatis; skor AI bukan bukti pelanggaran. Keputusan substantif mutlak memerlukan peninjauan manusia.</p>
          </article>
          <article class="risk">
            <span class="tag">PRINSIP AKADEMIK</span>
            <h3>Klarifikasi lisan adalah kunci.</h3>
            <p>Instrumen pertanyaan sidang lisan AR Tilik dirancang untuk mengonfirmasi pemahaman mahasiswa, bukan menghukum dari angka semata.</p>
          </article>
        </div>
      </div>
    </section>

    <section id="pilih" class="wrap">
      <div class="section-head">
        <div class="num">05 — Keputusan</div>
        <div>
          <h2>Pilih alat dari risiko keputusanmu.</h2>
          <p class="section-intro">Gunakan lensa peran untuk melihat rekomendasi yang lebih spesifik.</p>
        </div>
      </div>
      <div class="decision-tool">
        <div class="role-buttons" role="tablist" aria-label="Pilih peran">
          <button type="button" role="tab" aria-selected="true" aria-controls="advice" data-role="editor">Editor jurnal</button>
          <button type="button" role="tab" aria-selected="false" aria-controls="advice" data-role="dosen">Dosen</button>
          <button type="button" role="tab" aria-selected="false" aria-controls="advice" data-role="penulis">Mahasiswa / penulis</button>
          <button type="button" role="tab" aria-selected="false" aria-controls="advice" data-role="institusi">Institusi</button>
        </div>
        <article class="advice" id="advice" role="tabpanel" aria-live="polite">
          <div class="mini" id="advice-kicker">UNTUK EDITOR JURNAL</div>
          <h3 id="advice-title">Utamakan bukti yang bisa ditindaklanjuti.</h3>
          <p id="advice-body">AR Tilik lebih selaras dengan alur editorial: temuan kemiripan, pola parafrasa, dan verifikasi rujukan dapat diperiksa kembali. Jika ZeroGPT dipakai, batasi sebagai pemicu baca ulang—bukan alasan penolakan.</p>
          <p class="action" id="advice-action">Alur aman: skrining &rarr; telaah manusia &rarr; minta klarifikasi penulis &rarr; dokumentasikan alasan.</p>
        </article>
      </div>
    </section>

    <section class="final-word">
      <div class="wrap final-grid">
        <blockquote>“Dalam integritas akademik, alat terbaik bukan yang paling yakin—melainkan yang paling mudah dibantah, diperiksa, dan diperbaiki.”</blockquote>
        <aside>
          <h3>Pendapat akhir</h3>
          <p><strong>ZeroGPT unggul sebagai pengalaman deteksi AI yang cepat, luas, dan mudah dicoba.</strong> Free tier-nya menerima 15.000 karakter per pemindaian, mengklaim semua bahasa, dan menyorot kalimat.</p>
          <p><strong>AR Tilik lebih masuk akal untuk tata kelola karya ilmiah &amp; jurnal Indonesia</strong> karena ia memindahkan fokus dari dugaan “dibuat AI” ke isu yang dapat diverifikasi: kemiripan sekuensial, pola parafrasa, dan keaslian rujukan.</p>
          <p>Rekomendasi kami: jangan jadikan deteksi AI sebagai vonis mutlak. Posisikan sistem sebagai <strong>audit integritas yang berpegang pada bukti objektif</strong>.</p>
        </aside>
      </div>
    </section>

    <!-- Call to Action Band -->
    <section id="aksi" class="cta-band">
      <div class="wrap cta-box">
        <h3>Buktikan Sendiri pada Naskah Anda</h3>
        <p>Rasakan bedanya telaah berbasis bukti tekstual objektif, audit rujukan global, dan sertifikasi digital terverifikasi.</p>
        <div class="cta-buttons">
          <a href="<?= base_url('dashboard') ?>" class="btn-main">
            <span>Uji Coba AR Tilik Sekarang</span>
            <span>&rarr;</span>
          </a>
          <a href="<?= base_url('docs/ojs/pdf') ?>" class="btn-secondary">
            <span>Panduan Integrasi OJS 3 Kampus</span>
          </a>
        </div>
      </div>
    </section>

    <section class="wrap sources" aria-labelledby="sources-title">
      <h2 id="sources-title">Sumber &amp; status bukti</h2>
      <ul class="source-list">
        <li><a href="https://www.zerogpt.com/deep-analyse" target="_blank" rel="noopener noreferrer">ZeroGPT DeepAnalyse&trade;</a><br />Metodologi, klaim 98,4% akurasi, &lt;1% false positive, 35+ keluarga LLM. Diverifikasi 2026.</li>
        <li><a href="https://www.zerogpt.com/faq" target="_blank" rel="noopener noreferrer">FAQ resmi ZeroGPT</a><br />Mendukung semua bahasa, panjang optimal 150–200+ kata, dan batasan parafrasa.</li>
        <li><a href="https://www.zerogpt.com/plagiarism-checker" target="_blank" rel="noopener noreferrer">ZeroGPT Plagiarism Checker</a><br />Produk kemiripan terpisah; klaim miliaran sumber, 190+ bahasa, dan laporan PDF.</li>
        <li><a href="https://jurnalcahayaedukasi.com/index.php/jce/article/download/316/219/1288" target="_blank" rel="noopener noreferrer">Falaq (2026), Jurnal Cahaya Edukasi</a><br />80 teks akademik Indonesia; akurasi 37,5%; FPR 80%; FNR 45%.</li>
        <li><strong>Audit Produk AR Tilik, Report #1159 / #1167</strong><br />Pengujian empiris sistem Dual-Lens, audit sitasi phantom, dan sintesis risiko XGBoost.</li>
        <li><strong>Weber-Wulff et al. (2023)</strong><br />International Journal for Educational Integrity; DOI 10.1007/s40979-023-00146-z; 14 alat dan 756 pengujian.</li>
      </ul>
      <p class="method-note">Catatan metodologi: angka komersial dan klaim produk dibedakan dari temuan akademik independen. Tidak ada nilai yang digabung menjadi satu “skor pemenang” karena dataset, bahasa, dan definisi hasil berbeda.</p>
    </section>
  </main>

  <script>
    (function () {
      var recommendations = {
        editor: {
          kicker: "UNTUK EDITOR JURNAL",
          title: "Utamakan bukti yang bisa ditindaklanjuti.",
          body: "AR Tilik lebih selaras dengan alur editorial: temuan kemiripan, pola parafrasa, dan verifikasi rujukan dapat diperiksa kembali. Jika ZeroGPT dipakai, batasi sebagai pemicu baca ulang—bukan alasan penolakan.",
          action: "Alur aman: skrining \u2192 telaah manusia \u2192 minta klarifikasi penulis \u2192 dokumentasikan alasan."
        },
        dosen: {
          kicker: "UNTUK DOSEN",
          title: "Jangan jadikan skor AI sebagai bukti disipliner.",
          body: "False positive pada tulisan formal dan non-native terlalu tinggi untuk keputusan berisiko besar. Gunakan percakapan tentang proses, sumber, draf, dan kemampuan mahasiswa menjelaskan argumennya.",
          action: "Alur aman: lihat sinyal \u2192 periksa proses penulisan \u2192 beri kesempatan menjelaskan \u2192 nilai bukti, bukan probabilitas."
        },
        penulis: {
          kicker: "UNTUK MAHASISWA / PENULIS",
          title: "Perbaiki yang dapat dibuktikan, bukan mengejar skor hijau.",
          body: "Jangan mengubah tulisan semata-mata agar lolos ZeroGPT. Prioritaskan sumber yang benar-benar ada, kutipan yang tepat, parafrasa yang bertanggung jawab, dan catatan proses penulisan.",
          action: "Simpan draf dan daftar sumber; cek setiap DOI, judul, penulis, dan tahun sebelum menyerahkan."
        },
        institusi: {
          kicker: "UNTUK INSTITUSI",
          title: "Bangun kebijakan yang tahan terhadap salah klasifikasi.",
          body: "Sebelum adopsi, uji kedua alat pada korpus lokal berlabel yang mewakili disiplin dan ragam Bahasa Indonesia. Pisahkan fungsi skrining dari keputusan sanksi, dan audit false positive per kelompok penulis.",
          action: "Syarat minimal: benchmark lokal, ambang yang terdokumentasi, review manusia, hak banding, dan log alasan keputusan."
        }
      };
      var buttons = document.querySelectorAll("[data-role]");
      var kicker = document.getElementById("advice-kicker");
      var title = document.getElementById("advice-title");
      var body = document.getElementById("advice-body");
      var action = document.getElementById("advice-action");
      buttons.forEach(function (button) {
        button.addEventListener("click", function () {
          var item = recommendations[button.getAttribute("data-role")];
          buttons.forEach(function (b) { b.setAttribute("aria-selected", String(b === button)); });
          kicker.textContent = item.kicker;
          title.textContent = item.title;
          body.textContent = item.body;
          action.textContent = item.action;
        });
      });
    }());
  </script>
</body>
</html>
