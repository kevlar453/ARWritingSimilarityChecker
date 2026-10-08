<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Atur Ulang Kata Sandi — AR Tilik</title>
    <meta name="description" content="Atur ulang kata sandi akun AR Tilik Anda dengan aman.">
    <meta name="robots" content="noindex, nofollow">
    <link rel="canonical" href="<?= base_url('auth/reset-password') ?>">
    <meta name="theme-color" content="#4338ca">

    <!-- Favicon & Brand Icons -->
    <link rel="icon" type="image/x-icon" href="<?= base_url('favicon.ico?v=20261006') ?>">
    <link rel="icon" type="image/png" sizes="32x32" href="<?= base_url('favicon-32x32.png?v=20261006') ?>">
    <link rel="icon" type="image/png" sizes="16x16" href="<?= base_url('favicon-16x16.png?v=20261006') ?>">
    <link rel="apple-touch-icon" sizes="180x180" href="<?= base_url('apple-touch-icon.png?v=20261006') ?>">

    <!-- Tailwind CSS & Fonts -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
        }
        .font-mono-code {
            font-family: 'JetBrains Mono', monospace;
        }
    </style>
</head>
<body class="bg-slate-50 text-slate-800 antialiased min-h-screen flex flex-col justify-between selection:bg-indigo-500 selection:text-white">

    <!-- Header Navigation -->
    <header class="bg-white border-b border-slate-200 sticky top-0 z-50 shadow-xs">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 h-16 flex items-center justify-between">
            <a href="<?= base_url() ?>" class="flex items-center gap-2.5 hover:opacity-90 transition group">
                <img src="<?= base_url('images/logo.png') ?>" alt="AR Tilik Logo" class="w-9 h-9 object-contain drop-shadow-xs group-hover:scale-105 transition-transform duration-200">
                <div class="flex items-center gap-2">
                    <span class="font-bold text-lg tracking-tight bg-gradient-to-r from-indigo-700 to-indigo-500 bg-clip-text text-transparent">AR Tilik</span>
                    <span class="hidden sm:inline-block text-[10px] font-bold uppercase tracking-wider text-indigo-700 bg-indigo-50 px-2.5 py-0.5 rounded-full border border-indigo-200">
                        Keamanan Akun
                    </span>
                </div>
            </a>
            <div class="flex items-center gap-3">
                <a href="<?= base_url() ?>" class="text-xs font-semibold text-slate-600 hover:text-indigo-600 transition flex items-center gap-1">
                    <span>← Kembali ke Beranda</span>
                </a>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="max-w-md w-full mx-auto px-4 py-10 sm:py-16 flex-1 flex flex-col justify-center">
        <div class="bg-white rounded-2xl border border-slate-200 shadow-xl overflow-hidden p-6 sm:p-8">
            <div class="text-center mb-6">
                <div class="w-14 h-14 bg-indigo-50 text-indigo-600 rounded-2xl flex items-center justify-center mx-auto mb-4 border border-indigo-100 shadow-xs">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                </div>
                <h1 class="text-xl sm:text-2xl font-extrabold text-slate-900 tracking-tight">Atur Ulang Kata Sandi</h1>
                <p class="text-xs sm:text-sm text-slate-500 mt-1.5">
                    Buat kata sandi baru untuk akun <strong class="text-slate-700"><?= esc($userEmail) ?></strong>
                </p>
            </div>

            <!-- Alert Notification -->
            <div id="resetAlert" class="hidden rounded-xl p-3.5 mb-5 text-xs sm:text-sm border transition-all"></div>

            <form id="formResetPassword" onsubmit="handleResetPasswordSubmit(event)" class="space-y-4">
                <input type="hidden" name="token" value="<?= esc($token) ?>">
                <input type="hidden" name="security_token" value="<?= esc($securityToken ?? '') ?>">
                
                <!-- Anti-bot honeypot -->
                <div style="display:none; visibility:hidden; opacity:0; position:absolute; left:-9999px;" aria-hidden="true">
                    <input type="text" name="website_hp" tabindex="-1" autocomplete="off">
                    <input type="text" name="fax_number" tabindex="-1" autocomplete="off">
                </div>

                <div>
                    <label for="newPassword" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                        Kata Sandi Baru
                    </label>
                    <div class="relative">
                        <input type="password" id="newPassword" name="password" required minlength="6"
                               placeholder="Minimal 6 karakter"
                               class="w-full px-3.5 py-2.5 text-sm bg-slate-50 border border-slate-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition pr-10">
                        <button type="button" onclick="togglePasswordVisibility('newPassword', this)" 
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 focus:outline-none">
                            <svg class="w-5 h-5 eye-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                        </button>
                    </div>
                </div>

                <div>
                    <label for="confirmPassword" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                        Konfirmasi Kata Sandi Baru
                    </label>
                    <div class="relative">
                        <input type="password" id="confirmPassword" name="password_confirm" required minlength="6"
                               placeholder="Ulangi kata sandi baru"
                               class="w-full px-3.5 py-2.5 text-sm bg-slate-50 border border-slate-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition pr-10">
                        <button type="button" onclick="togglePasswordVisibility('confirmPassword', this)" 
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 focus:outline-none">
                            <svg class="w-5 h-5 eye-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                        </button>
                    </div>
                    <div id="passwordMismatchError" class="hidden text-xs text-rose-600 mt-1 font-medium">
                        ⚠️ Konfirmasi kata sandi belum cocok dengan kata sandi baru.
                    </div>
                </div>

                <div class="pt-2">
                    <button type="submit" id="btnSubmitReset"
                            class="w-full py-3 px-4 bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 text-white font-bold text-sm rounded-xl shadow-md shadow-indigo-200 hover:shadow-indigo-300 transition duration-150 flex items-center justify-center gap-2">
                        <span>Simpan Kata Sandi Baru</span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                    </button>
                </div>
            </form>

            <div class="mt-6 pt-5 border-t border-slate-100 text-center">
                <a href="<?= base_url('/?auth=login') ?>" class="text-xs font-bold text-indigo-600 hover:text-indigo-800 transition">
                    ← Kembali ke Halaman Masuk
                </a>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-white border-t border-slate-200 py-6 text-center text-xs text-slate-500">
        <div class="max-w-4xl mx-auto px-4">
            <p>&copy; 2026 AR Tilik — Sistem Penelaah Kesepadanan Naskah Akademik & Integritas Bukti.</p>
        </div>
    </footer>

    <!-- Interactive Script -->
    <script>
        function togglePasswordVisibility(inputId, btn) {
            const input = document.getElementById(inputId);
            if (!input) return;
            const isPassword = input.type === 'password';
            input.type = isPassword ? 'text' : 'password';
            btn.classList.toggle('text-indigo-600', isPassword);
            btn.classList.toggle('text-slate-400', !isPassword);
        }

        const newPassInput = document.getElementById('newPassword');
        const confirmPassInput = document.getElementById('confirmPassword');
        const mismatchError = document.getElementById('passwordMismatchError');

        function validateMatch() {
            if (confirmPassInput.value && newPassInput.value !== confirmPassInput.value) {
                mismatchError.classList.remove('hidden');
                return false;
            } else {
                mismatchError.classList.add('hidden');
                return true;
            }
        }

        newPassInput.addEventListener('input', validateMatch);
        confirmPassInput.addEventListener('input', validateMatch);

        function showResetAlert(message, isSuccess = false) {
            const alertBox = document.getElementById('resetAlert');
            alertBox.innerHTML = message;
            alertBox.className = 'rounded-xl p-3.5 mb-5 text-xs sm:text-sm border transition-all ' + 
                (isSuccess 
                    ? 'bg-emerald-50 border-emerald-200 text-emerald-800' 
                    : 'bg-rose-50 border-rose-200 text-rose-800');
            alertBox.classList.remove('hidden');
        }

        async function handleResetPasswordSubmit(e) {
            e.preventDefault();
            if (!validateMatch()) return;

            const form = e.target;
            const submitBtn = document.getElementById('btnSubmitReset');
            const originalBtnContent = submitBtn.innerHTML;

            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span>Menyimpan Kata Sandi...</span>';

            const formData = new FormData(form);

            try {
                const res = await fetch('<?= base_url('auth/reset-password') ?>', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json'
                    },
                    body: formData
                });

                const data = await res.json();

                if (!res.ok || data.status === 'error') {
                    showResetAlert('⚠️ ' + (data.message || 'Gagal mengatur ulang kata sandi.'), false);
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalBtnContent;
                } else {
                    showResetAlert('✅ <strong>Berhasil!</strong> Kata sandi Anda telah diperbarui. Mengarahkan ke halaman masuk...', true);
                    form.reset();
                    setTimeout(() => {
                        window.location.href = data.redirect || '<?= base_url('/?auth=login&reset=success') ?>';
                    }, 1500);
                }
            } catch (err) {
                showResetAlert('⚠️ Kendala jaringan saat menghubungi peladen. Silakan coba kembali.', false);
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnContent;
            }
        }
    </script>
</body>
</html>
