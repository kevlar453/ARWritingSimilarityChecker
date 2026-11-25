<?php
$authSecurityToken = $authSecurityToken ?? '';
$flashNotice = session()->getFlashdata('auth_notice') ?: session()->getFlashdata('error');
$flashSuccess = session()->getFlashdata('message');
$jsVersion = @filemtime(FCPATH . 'js/artilik-landing.js') ?: '20261119';
?>
    <!-- Auth Modal (Registration & Login) -->
    <div id="authModal" class="modal-overlay google-anno-skip" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
        <div class="modal-card">
            <div class="modal-header">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <img src="<?= base_url('images/logo.png') ?>" alt="AR Tilik Logo" width="26" height="26" style="width: 26px; height: 26px; object-fit: contain; border-radius: 6px;">
                    <div class="modal-title" id="modalTitle">Masuk ke AR Tilik</div>
                </div>
                <button type="button" class="modal-close" onclick="closeAuthModal()" aria-label="Tutup dialog akun">&times;</button>
            </div>

            <div class="modal-body">
                <div class="tab-switch">
                    <button type="button" id="tabLogin" class="tab-btn active" onclick="switchTab('login')">Masuk</button>
                    <button type="button" id="tabRegister" class="tab-btn" onclick="switchTab('register')">Daftar Akun Gratis</button>
                </div>

                <div id="authAlert" class="alert-box" role="status" aria-live="polite"></div>

                <!-- Google OAuth Quick Sign-In / Sign-Up -->
                <div id="googleAuthSection" class="google-auth-section">
                    <button type="button" id="btnGoogleAuth" class="btn-google-auth" onclick="startGoogleAuth()">
                        <svg width="18" height="18" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                            <path fill="#4285F4" d="M23.745 12.27c0-.7-.06-1.4-.19-2.07H12v4.51h6.6c-.29 1.52-1.14 2.82-2.4 3.68v3.05h3.88c2.27-2.09 3.665-5.17 3.665-9.17Z"/>
                            <path fill="#34A853" d="M12 24c3.3 0 6.08-1.09 8.11-2.96l-3.88-3.05c-1.09.73-2.48 1.16-4.23 1.16-3.25 0-6.01-2.19-7-5.15H.98v3.14C3 21.18 7.2 24 12 24Z"/>
                            <path fill="#FBBC05" d="M5 14c-.25-.73-.39-1.52-.39-2.32 0-.8.14-1.59.39-2.32V6.22H.98A11.96 11.96 0 0 0 0 11.68c0 1.93.46 3.76 1.28 5.46L5 14Z"/>
                            <path fill="#EA4335" d="M12 4.75c1.79 0 3.41.62 4.68 1.82l3.51-3.51C18.07 1.14 15.3 0 12 0 7.2 0 3 2.82.98 6.86L5 10c.99-2.96 3.75-5.25 7-5.25Z"/>
                        </svg>
                        <span id="googleAuthBtnLabel">Masuk dengan Google</span>
                    </button>
                    <div class="auth-divider" aria-hidden="true"><span>atau dengan email</span></div>
                </div>

                <!-- Login Form -->
                <form id="formLogin" onsubmit="handleLogin(event)">
                    <div style="display:none; visibility:hidden; opacity:0; position:absolute; left:-9999px;" aria-hidden="true">
                        <input type="text" name="website_hp" tabindex="-1" autocomplete="off">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="loginEmail">Alamat Email</label>
                        <input type="email" id="loginEmail" name="email" autocomplete="email" inputmode="email" class="form-control" placeholder="nama@kampus.ac.id" required>
                    </div>

                    <div class="form-group">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                            <label class="form-label" for="loginPassword" style="margin-bottom: 0;">Kata Sandi</label>
                            <button type="button" onclick="switchTab('forgot')" style="background: none; border: none; padding: 0; font-family: inherit; font-size: 11px; font-weight: 700; color: var(--primary); text-decoration: none; cursor: pointer;">
                                Lupa Kata Sandi?
                            </button>
                        </div>
                        <input type="password" id="loginPassword" name="password" autocomplete="current-password" class="form-control" placeholder="••••••••" required>
                    </div>

                    <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 8px;">
                        Masuk ke Akun
                    </button>
                </form>

                <!-- Forgot Password Form -->
                <form id="formForgot" onsubmit="handleForgotPassword(event)" style="display: none;">
                    <input type="hidden" name="security_token" id="forgotSecurityToken" value="<?= esc($authSecurityToken) ?>">
                    <div style="display:none; visibility:hidden; opacity:0; position:absolute; left:-9999px;" aria-hidden="true">
                        <input type="text" name="website_hp" tabindex="-1" autocomplete="off">
                        <input type="text" name="fax_number" tabindex="-1" autocomplete="off">
                    </div>

                    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 12px 14px; margin-bottom: 16px;">
                        <p style="font-size: 12px; color: var(--text-muted); line-height: 1.5; margin: 0;">
                            🔐 Masukkan alamat email akun Anda. Kami akan mengirimkan tautan aman untuk mengatur ulang kata sandi.
                        </p>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="forgotEmail">Alamat Email Terdaftar</label>
                        <input type="email" id="forgotEmail" name="email" autocomplete="email" inputmode="email" class="form-control" placeholder="nama@kampus.ac.id" required>
                    </div>

                    <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 8px;">
                        Kirim Tautan Atur Ulang
                    </button>

                    <div style="text-align: center; margin-top: 14px;">
                        <button type="button" onclick="switchTab('login')" style="background: none; border: none; padding: 0; font-family: inherit; font-size: 12px; font-weight: 700; color: var(--primary); text-decoration: underline; cursor: pointer;">
                            ← Kembali ke Halaman Masuk
                        </button>
                    </div>
                </form>

                <!-- Register Form -->
                <form id="formRegister" onsubmit="handleRegister(event)" style="display: none;">
                    <input type="hidden" name="security_token" id="regSecurityToken" value="<?= esc($authSecurityToken) ?>">
                    <input type="hidden" name="referral_code" id="regReferralCode" value="<?= esc(session()->get('referred_by_code') ?? ($_COOKIE['referred_by_code'] ?? '')) ?>">
                    <div style="display:none; visibility:hidden; opacity:0; position:absolute; left:-9999px;" aria-hidden="true">
                        <input type="text" name="fax_number" tabindex="-1" autocomplete="off">
                    </div>

                    <div id="referralBonusBadge" style="<?= empty(session()->get('referred_by_code')) && empty($_COOKIE['referred_by_code']) ? 'display:none;' : '' ?> background: #ecfdf5; border: 1px solid #a7f3d0; border-radius: 12px; padding: 10px 14px; margin-bottom: 16px; font-size: 11.5px; color: #065f46;">
                        <span style="font-weight: 700;">🎁 Bonus Sahabat &amp; Duta:</span> Kode <span id="displayRefCode" style="font-family: monospace; font-weight: 800; color: #047857;"><?= esc(session()->get('referred_by_code') ?? ($_COOKIE['referred_by_code'] ?? '')) ?></span> terpasang. Anda mendapatkan <strong>+1 bonus cek naskah gratis</strong> &amp; <strong>diskon perdana 10%</strong>!
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="regName">Nama Lengkap</label>
                        <input type="text" id="regName" name="name" autocomplete="name" class="form-control" placeholder="Nama Anda" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="regEmail">Alamat Email Valid</label>
                        <input type="email" id="regEmail" name="email" autocomplete="email" inputmode="email" class="form-control" placeholder="nama@kampus.ac.id" required>
                        <small style="font-size: 11px; color: var(--text-muted); margin-top: 4px; display: block; line-height: 1.4;">
                            ✉️ Tautan aktivasi akun akan dikirimkan ke email ini. Periksa folder <strong>Kotak Masuk (Inbox)</strong> maupun <strong>SPAM</strong>.
                        </small>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="regInstitution">Institusi / Universitas</label>
                        <input type="text" id="regInstitution" name="institution" autocomplete="organization" class="form-control" placeholder="Contoh: Universitas Indonesia / UGM">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="regPassword">Kata Sandi</label>
                        <input type="password" id="regPassword" name="password" autocomplete="new-password" class="form-control" placeholder="Minimal 6 karakter" minlength="6" required>
                    </div>

                    <div style="background: rgba(245, 158, 11, 0.08); border: 1px solid rgba(245, 158, 11, 0.28); border-radius: 10px; padding: 10px 12px; margin-top: 12px; margin-bottom: 14px; font-size: 11.5px; color: #b45309; line-height: 1.5;">
                        <div style="display: flex; align-items: flex-start; gap: 8px;">
                            <span style="font-size: 15px; line-height: 1.1;">📬</span>
                            <div>
                                <strong style="color: #92400e;">Penting Terkait Email Verifikasi:</strong><br>
                                Tautan aktivasi dikirim otomatis ke email Anda sesaat setelah mendaftar. Apabila tidak ditemukan pada folder <strong>Kotak Masuk (Inbox)</strong>, mohon periksa juga folder <strong>SPAM / Junk</strong> atau tab <em>Promosi</em> akun email Anda.
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 4px;">
                        Daftar Gratis (3 Submisi/Minggu)
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Contact & Inquiry Modal -->
    <div id="contactModal" class="modal-overlay google-anno-skip" role="dialog" aria-modal="true" aria-labelledby="contactModalTitle">
        <div class="modal-card modal-card-md">
            <div class="modal-header">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <div style="width: 32px; height: 32px; border-radius: 8px; background: rgba(79, 70, 229, 0.1); color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: 16px;" aria-hidden="true">
                        📬
                    </div>
                    <div>
                        <div class="modal-title" id="contactModalTitle" style="font-size: 17px; font-weight: 800; color: var(--dark);">Hubungi Tim Pengembang</div>
                        <div style="font-size: 12px; color: var(--text-muted);">Pesan langsung diteruskan ke tim pengembang AR Tilik</div>
                    </div>
                </div>
                <button type="button" class="modal-close" onclick="closeContactModal()" aria-label="Tutup dialog kontak">&times;</button>
            </div>

            <div class="modal-body">
                <div id="contactAlert" class="alert-box" role="status" aria-live="polite"></div>

                <form id="formContact" onsubmit="handleContactSubmit(event)">
                    <input type="hidden" name="security_token" id="contactSecurityToken" value="<?= esc($authSecurityToken) ?>">

                    <div style="display:none; visibility:hidden; opacity:0; position:absolute; left:-9999px;" aria-hidden="true">
                        <input type="text" name="website_hp" tabindex="-1" autocomplete="off">
                        <input type="text" name="fax_number" tabindex="-1" autocomplete="off">
                    </div>

                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 12px;">
                        <div class="form-group" style="margin-bottom: 12px;">
                            <label class="form-label" for="contactName">Nama Lengkap *</label>
                            <input type="text" id="contactName" name="name" autocomplete="name" class="form-control" placeholder="Prof. / Dr. / Bpk / Ibu" required>
                        </div>
                        <div class="form-group" style="margin-bottom: 12px;">
                            <label class="form-label" for="contactEmail">Email Aktif *</label>
                            <input type="email" id="contactEmail" name="email" autocomplete="email" inputmode="email" class="form-control" placeholder="nama@kampus.ac.id" required oninput="handleEmailInputDebounced(this.value)">
                            <div id="emailVerifyFeedback" style="font-size: 11px; margin-top: 5px; min-height: 18px; color: var(--text-muted); line-height: 1.4; transition: all 0.2s;">
                                <span>✉️ Masukkan email aktif (domain institusi atau email resmi Anda)</span>
                            </div>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 12px;">
                        <div class="form-group" style="margin-bottom: 12px;">
                            <label class="form-label" for="contactInstitution">Institusi / Kampus</label>
                            <input type="text" id="contactInstitution" name="institution" autocomplete="organization" class="form-control" placeholder="Contoh: Univ. Indonesia / UGM">
                        </div>
                        <div class="form-group" style="margin-bottom: 12px;">
                            <label class="form-label" for="contactSubject">Subjek Kebutuhan *</label>
                            <select id="contactSubject" name="subject" class="form-control" required style="padding: 10px 14px; height: 42px;">
                                <option value="Pertanyaan Umum">Pertanyaan Umum</option>
                                <option value="Konsultasi Institusi & Lisensi Kampus">Konsultasi Institusi &amp; Lisensi Kampus</option>
                                <option value="Integrasi Plugin OJS 3.x">Integrasi Plugin OJS 3.x</option>
                                <option value="Kemitraan Korpus & Repositori">Kemitraan Korpus &amp; Repositori</option>
                                <option value="Kebijakan Privasi & Hak Cipta">Kebijakan Privasi &amp; Hak Cipta</option>
                                <option value="Laporan Kendala Teknis">Laporan Kendala Teknis</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group" style="margin-bottom: 12px;">
                        <label class="form-label" for="contactMessage">Pesan / Pertanyaan Detail *</label>
                        <textarea id="contactMessage" name="message" class="form-control" rows="4" placeholder="Jelaskan kebutuhan, jumlah jurnal/mahasiswa, atau kendala teknis Anda..." required style="resize: vertical; min-height: 85px;"></textarea>
                    </div>

                    <div class="form-group" style="margin-bottom: 16px; background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 12px; padding: 12px 16px;">
                        <label class="form-label" for="mathAnswerInput" style="font-size: 12px; color: #475569; display: flex; align-items: center; justify-content: space-between; margin-bottom: 6px;">
                            <span>🛡️ Uji Anti-Spam: Hitung <strong id="mathProblemLabel" style="color: var(--primary); font-size: 13px;">...</strong></span>
                            <button type="button" onclick="generateMathCaptcha()" style="background:none; border:none; color:var(--primary); font-size:11px; font-weight:700; cursor:pointer; text-decoration:underline;">Ganti Soal</button>
                        </label>
                        <div style="display: flex; gap: 10px; align-items: center;">
                            <input type="number" id="mathAnswerInput" name="captcha_ans" class="form-control" placeholder="Jawaban angka" required style="max-width: 140px; font-weight: 700; text-align: center;">
                            <input type="hidden" id="mathChallengeToken" name="captcha_token">
                            <span style="font-size: 11px; color: var(--text-muted); line-height: 1.4;">Diperlukan untuk memvalidasi bahwa pengirim adalah akademisi sungguhan.</span>
                        </div>
                    </div>

                    <button type="submit" id="btnSubmitContact" class="btn btn-primary" style="width: 100%;">
                        Kirim Pesan ke Pengembang 🚀
                    </button>
                    <div style="font-size: 11px; color: var(--text-muted); text-align: center; margin-top: 10px; line-height: 1.6;">
                        📧 Email: <strong>tilik@arsetontong.top</strong> &bull; 💬 WhatsApp Resmi: <a href="https://wa.me/6285138862032" target="_blank" rel="noopener noreferrer" style="color: var(--primary); font-weight: 700; text-decoration: underline;">0851 3886 2032</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Legal Document Modal (Privacy Policy & Terms) -->
    <div id="legalModal" class="modal-overlay google-anno-skip" role="dialog" aria-modal="true" aria-labelledby="legalModalTitle">
        <div class="modal-card modal-card-lg">
            <div class="modal-header" style="border-bottom: 1px solid var(--border); padding: 18px 24px;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <img src="<?= base_url('images/logo.png') ?>" alt="AR Tilik Logo" width="24" height="24" style="width: 24px; height: 24px; object-fit: contain;">
                    <div class="modal-title" id="legalModalTitle" style="font-size: 17px; font-weight: 800; color: var(--dark);">Dokumen Legal</div>
                </div>
                <div style="display: flex; align-items: center; gap: 12px;">
                    <a id="legalFullPageLink" href="<?= base_url('privacy') ?>" target="_blank" style="font-size: 12px; color: var(--primary); font-weight: 700; text-decoration: underline;">
                        Buka Halaman Penuh ↗
                    </a>
                    <button type="button" class="modal-close" onclick="closeLegalModal()" aria-label="Tutup dokumen legal">&times;</button>
                </div>
            </div>
            <div id="legalModalBody" class="modal-body-scroll">
                <div style="text-align: center; padding: 40px; color: var(--text-muted);">
                    <div class="spinner" style="margin: 0 auto 14px;"></div>
                    <span>Memuat dokumen legal...</span>
                </div>
            </div>
        </div>
    </div>

    <script>
        window.ARTILIK_LANDING = {
            baseUrl: <?= json_encode(base_url()) ?>,
            securityTokenUrl: <?= json_encode(base_url('auth/security-token')) ?>,
            loginUrl: <?= json_encode(base_url('auth/login')) ?>,
            registerUrl: <?= json_encode(base_url('auth/register')) ?>,
            googleAuthUrl: <?= json_encode(base_url('auth/google')) ?>,
            forgotPasswordUrl: <?= json_encode(base_url('auth/forgot-password')) ?>,
            resendVerificationUrl: <?= json_encode(base_url('auth/resend-verification')) ?>,
            dashboardUrl: <?= json_encode(base_url('dashboard')) ?>,
            contactChallengeUrl: <?= json_encode(base_url('contact/challenge')) ?>,
            contactValidateEmailUrl: <?= json_encode(base_url('contact/validate-email')) ?>,
            contactSendUrl: <?= json_encode(base_url('contact/send')) ?>,
            privacyUrl: <?= json_encode(base_url('privacy')) ?>,
            termsUrl: <?= json_encode(base_url('terms')) ?>,
            flashNotice: <?= json_encode($flashNotice ?: null) ?>,
            flashSuccess: <?= json_encode($flashSuccess ?: null) ?>
        };
    </script>
    <script defer src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script defer src="<?= base_url('js/artilik-landing.js?v=' . $jsVersion) ?>"></script>
</body>
</html>
