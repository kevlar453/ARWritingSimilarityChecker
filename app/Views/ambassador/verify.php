<!DOCTYPE html>
<html lang="id" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi Identitas Duta Kampus &bull; AR Tilik</title>
    <!-- Tailwind CSS v3 via CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
</head>
<body class="bg-slate-950 text-slate-100 font-sans antialiased min-h-screen flex flex-col items-center justify-center p-4 sm:p-6 selection:bg-indigo-500 selection:text-white">

    <div class="max-w-md w-full space-y-6">
        <!-- Logo -->
        <div class="text-center space-y-2">
            <a href="<?= base_url() ?>" class="inline-flex items-center gap-2">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-white font-bold text-lg shadow-lg">
                    AR
                </div>
                <span class="font-bold text-base tracking-tight text-white">AR Tilik</span>
            </a>
            <div class="inline-flex items-center gap-1.5 px-3 py-0.5 rounded-full text-xs font-semibold bg-amber-500/15 text-amber-300 border border-amber-500/30">
                <span>🛡️</span> <span>Tahap 3: Verifikasi Identitas Anti-Fraud</span>
            </div>
            <h1 class="text-xl font-bold text-white">Unggah Tanda Pengenal Kampus</h1>
            <p class="text-xs text-slate-400 leading-relaxed">
                Halo kak <strong><?= esc($app['full_name']) ?></strong> (<?= esc($app['institution']) ?>). Selamat atas kelulusan seleksi tahap awal!
            </p>
        </div>

        <!-- Kartu Form -->
        <div class="bg-slate-900 border border-white/10 rounded-3xl p-6 sm:p-7 shadow-2xl space-y-5">
            <?php if ($app['status'] === 'verified' || !empty($app['id_card_path'])): ?>
                <div class="text-center py-6 space-y-3">
                    <div class="w-14 h-14 rounded-2xl bg-emerald-500/20 text-emerald-400 flex items-center justify-center text-3xl mx-auto border border-emerald-500/30">
                        ✓
                    </div>
                    <h3 class="text-base font-bold text-white">Identitas Anda Telah Terunggah</h3>
                    <p class="text-xs text-slate-300 leading-relaxed">
                        Foto identitas telah tersimpan aman di server kami. Tim AR Tilik sedang menyiapkan kode referral unik dan akun premium Anda. Anda akan menerima paket sambutan via WhatsApp segera!
                    </p>
                    <a href="<?= base_url('ambassador') ?>" class="inline-block mt-2 text-xs text-indigo-300 underline font-semibold">
                        &larr; Kembali ke Halaman Duta Kampus
                    </a>
                </div>
            <?php else: ?>
                <div class="bg-indigo-500/10 border border-indigo-500/20 rounded-xl p-3 text-xs text-slate-300 leading-relaxed flex items-start gap-2.5">
                    <span class="text-base">ℹ️</span>
                    <div>
                        <strong>Kebijakan Integritas:</strong> 1 Mahasiswa/Dosen = 1 Akun Duta. Berkas ini hanya disimpan untuk mencegah kepemilikan akun ganda dan tidak akan dipublikasikan.
                    </div>
                </div>

                <form id="verifyForm" onsubmit="submitVerificationFile(event, '<?= esc($app['verification_token']) ?>')" class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-300 mb-1.5">Pilih Jenis Identitas:</label>
                        <select name="id_card_type" class="w-full text-xs bg-slate-950 border border-white/15 rounded-xl px-3.5 py-2.5 text-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            <option value="ktm" selected>Kartu Tanda Mahasiswa (KTM)</option>
                            <option value="ktp_dosen">KTP / NIDN (Dosen / Tenaga Pendidik)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-300 mb-1.5">Foto KTM / Tanda Pengenal:</label>
                        <input type="file" name="id_card_file" accept="image/*,application/pdf" required class="w-full text-xs bg-black/40 border border-white/15 rounded-xl p-3 text-slate-300 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-indigo-600 file:text-white hover:file:bg-indigo-500 cursor-pointer">
                        <span class="text-[10px] text-slate-500 mt-1 block">Format: JPG, PNG, WEBP, atau PDF (Maksimal 5 MB). Pastikan nama dan kampus terbaca.</span>
                    </div>

                    <button type="submit" id="btnSubmitVerify" class="w-full py-3 rounded-xl bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 active:scale-95 text-slate-950 font-bold text-xs shadow-lg shadow-amber-500/20 transition flex items-center justify-center gap-2 cursor-pointer">
                        <span>📤</span>
                        <span id="btnSubmitVerifyText">Unggah &amp; Konfirmasi Identitas</span>
                    </button>
                </form>
            <?php endif; ?>
        </div>

        <div class="text-center text-xs text-slate-500">
            &copy; <?= date('Y') ?> AR Tilik &bull; Verifikasi Identitas Duta Kampus
        </div>
    </div>

    <script>
        async function submitVerificationFile(e, token) {
            e.preventDefault();
            const btn = document.getElementById('btnSubmitVerify');
            const btnText = document.getElementById('btnSubmitVerifyText');
            const form = document.getElementById('verifyForm');
            const formData = new FormData(form);

            btn.disabled = true;
            btnText.innerText = 'Mengunggah Berkas...';

            try {
                const res = await fetch('<?= base_url('ambassador/verify/') ?>' + token, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                const data = await res.json();

                if (res.ok && data.success) {
                    alert('✓ ' + data.message);
                    window.location.reload();
                } else {
                    alert('⚠️ ' + (data.message || 'Gagal mengunggah berkas identitas.'));
                }
            } catch (err) {
                console.error('Error verification upload:', err);
                alert('Terjadi kesalahan jaringan saat mengunggah.');
            } finally {
                btn.disabled = false;
                btnText.innerText = 'Unggah & Konfirmasi Identitas';
            }
        }
    </script>
</body>
</html>
