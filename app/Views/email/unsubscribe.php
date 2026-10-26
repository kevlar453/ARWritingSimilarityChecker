<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Preferensi Surel — AR Tilik</title>
    <link rel="icon" type="image/png" href="<?= base_url('images/logo.png') ?>">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
</head>
<body class="bg-slate-50 text-slate-800 min-h-screen flex flex-col justify-between antialiased">
    <!-- Navbar Minimalis -->
    <header class="bg-white border-b border-slate-200">
        <div class="max-w-4xl mx-auto px-4 py-4 flex items-center justify-between">
            <a href="<?= base_url('/') ?>" class="flex items-center gap-2.5 text-indigo-700 font-extrabold text-lg tracking-tight">
                <span class="text-xl">⚖️</span>
                <span>AR Tilik</span>
            </a>
            <a href="<?= base_url('/dashboard') ?>" class="text-xs font-semibold text-slate-600 hover:text-indigo-600 transition">
                Masuk ke Dasbor →
            </a>
        </div>
    </header>

    <!-- Konten Utama -->
    <main class="flex-1 max-w-lg w-full mx-auto px-4 py-10">
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <!-- Header Card -->
            <div class="bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 p-6 text-white text-center">
                <div class="w-12 h-12 bg-white/10 rounded-full flex items-center justify-center mx-auto mb-3 text-2xl backdrop-blur-sm border border-white/20">
                    ✉️
                </div>
                <h1 class="text-xl font-bold tracking-tight">Preferensi Komunikasi Surel</h1>
                <p class="text-xs text-slate-300 mt-1">Kelola jenis surel yang ingin Anda terima dari platform AR Tilik</p>
            </div>

            <div class="p-6 sm:p-8">
                <?php if (!empty($message)): ?>
                <div class="mb-6 p-4 rounded-xl bg-emerald-50 border border-emerald-200 flex items-start gap-3">
                    <span class="text-emerald-600 text-lg">✅</span>
                    <div>
                        <div class="text-xs font-bold text-emerald-900">Perubahan Berhasil Disimpan</div>
                        <p class="text-xs text-emerald-700 mt-0.5"><?= esc($message) ?></p>
                    </div>
                </div>
                <?php endif; ?>

                <?php if (!empty($error)): ?>
                <div class="mb-6 p-4 rounded-xl bg-rose-50 border border-rose-200 flex items-start gap-3">
                    <span class="text-rose-600 text-lg">⚠️</span>
                    <div>
                        <div class="text-xs font-bold text-rose-900">Kendala Tautan</div>
                        <p class="text-xs text-rose-700 mt-0.5"><?= esc($error) ?></p>
                    </div>
                </div>
                <div class="text-center">
                    <a href="<?= base_url('/') ?>" class="inline-block px-5 py-2.5 bg-slate-900 text-white rounded-xl text-xs font-semibold hover:bg-slate-800 transition">
                        Kembali ke Halaman Utama
                    </a>
                </div>
                <?php elseif ($user): ?>

                <!-- Profil Singkat -->
                <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200 mb-6 flex items-center justify-between">
                    <div>
                        <div class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Akun Terdaftar</div>
                        <div class="text-sm font-bold text-slate-900"><?= esc($user['name']) ?></div>
                        <div class="text-xs text-slate-500"><?= esc($user['email']) ?></div>
                    </div>
                    <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-200">
                        <?= esc($user['subscription_tier'] === 'dosen_pro' ? 'Dosen Pro' : ($user['subscription_tier'] === 'skripsi' ? 'Paket Skripsi' : 'Akun Standar')) ?>
                    </span>
                </div>

                <!-- Formulir Preferensi -->
                <form method="POST" action="<?= base_url('email/unsubscribe') ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="token" value="<?= esc($token) ?>">
                    <input type="hidden" name="type" value="<?= esc($type) ?>">

                    <div class="space-y-4 mb-6">
                        <!-- Opsi 1: Ringkasan Mingguan -->
                        <label class="flex items-start gap-3 p-3.5 rounded-xl border border-slate-200 hover:border-indigo-300 hover:bg-indigo-50/20 transition cursor-pointer">
                            <input type="checkbox" name="email_weekly_summary" value="1" <?= (!empty($user['email_weekly_summary'])) ? 'checked' : '' ?> class="mt-1 h-4 w-4 text-indigo-600 rounded border-slate-300 focus:ring-indigo-500">
                            <div>
                                <span class="text-xs font-bold text-slate-900 block">📊 Ringkasan Penggunaan Mingguan</span>
                                <span class="text-[11px] text-slate-500 leading-relaxed block mt-0.5">
                                    Rekap berkala setiap awal pekan mengenai jumlah dokumen yang diperiksa, rerata skor kemiripan, kesehatan sitasi, status kuota, dan trik akademik bermanfaat.
                                </span>
                            </div>
                        </label>

                        <!-- Opsi 2: Notifikasi Laporan Selesai -->
                        <label class="flex items-start gap-3 p-3.5 rounded-xl border border-slate-200 hover:border-indigo-300 hover:bg-indigo-50/20 transition cursor-pointer">
                            <input type="checkbox" name="email_notifications_enabled" value="1" <?= (!empty($user['email_notifications_enabled'])) ? 'checked' : '' ?> class="mt-1 h-4 w-4 text-indigo-600 rounded border-slate-300 focus:ring-indigo-500">
                            <div>
                                <span class="text-xs font-bold text-slate-900 block">🔔 Notifikasi Hasil Pemeriksaan Naskah</span>
                                <span class="text-[11px] text-slate-500 leading-relaxed block mt-0.5">
                                    Pemberitahuan instan saat dokumen karya ilmiah Anda selesai diproses oleh mesin pembanding, lengkap dengan ringkasan skor dan tautan laporan.
                                </span>
                            </div>
                        </label>
                    </div>

                    <!-- Informasi Surel Transaksional -->
                    <div class="p-3 rounded-lg bg-amber-50/80 border border-amber-200/80 text-[11px] text-amber-800 mb-6 leading-relaxed flex items-start gap-2">
                        <span class="text-sm">🛡️</span>
                        <div>
                            <strong>Catatan Keamanan:</strong> Surel esensial terkait keamanan akun seperti <em>Verifikasi Akun Baru</em> dan <em>Atur Ulang Kata Sandi</em> akan tetap dikirimkan saat Anda memintanya.
                        </div>
                    </div>

                    <!-- Tombol Aksi -->
                    <div class="flex flex-col sm:flex-row gap-3">
                        <button type="submit" name="action" value="save" class="flex-1 px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold transition shadow-xs">
                            Simpan Pengaturan
                        </button>
                        <button type="submit" name="action" value="unsubscribe_all" class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-semibold transition" onclick="return confirm('Apakah Anda yakin ingin berhenti dari seluruh surel berkala & notifikasi naskah?');">
                            Berhenti dari Semua
                        </button>
                    </div>
                </form>

                <?php else: ?>
                <div class="text-center py-6">
                    <p class="text-xs text-slate-600 mb-4">Untuk mengelola preferensi surel, silakan klik tautan khusus berhenti berlangganan yang tercantum di bagian bawah surel AR Tilik yang Anda terima.</p>
                    <a href="<?= base_url('/dashboard') ?>" class="inline-block px-5 py-2.5 bg-indigo-600 text-white rounded-xl text-xs font-bold hover:bg-indigo-700 transition">
                        Buka Dasbor Saya
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="py-6 text-center text-xs text-slate-400">
        <p>&copy; <?= date('Y') ?> AR Tilik — Platform Pemeriksa Kesepadanan Naskah Akademik &amp; Keadilan Bukti.</p>
    </footer>
</body>
</html>
