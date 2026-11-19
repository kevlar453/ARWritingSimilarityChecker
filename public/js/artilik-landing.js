/**
 * AR Tilik Shared Public Landing & Multi-Page Interactive Script
 */
(function () {
    const cfg = window.ARTILIK_LANDING || {};
    let lastModalTrigger = null;
    let lastDrawerTrigger = null;
    let emailDebounceTimer = null;
    let isEmailVerifiedActive = null;
    const legalCache = {};

    function setBackgroundInert(isInert) {
        const main = document.getElementById('main-content');
        const footer = document.querySelector('footer.footer');
        [main, footer].forEach((el) => {
            if (!el) return;
            if (isInert) el.setAttribute('inert', '');
            else el.removeAttribute('inert');
        });
    }

    function restoreFocus(triggerEl) {
        if (triggerEl && typeof triggerEl.focus === 'function' && document.contains(triggerEl)) {
            triggerEl.focus();
        }
    }

    /* --- Auth Modal --- */
    window.openAuthModal = async function (mode) {
        const modal = document.getElementById('authModal');
        if (!modal) return;
        lastModalTrigger = document.activeElement;
        modal.style.display = 'flex';
        setBackgroundInert(true);
        window.switchTab(mode);

        const firstInput = mode === 'register'
            ? document.getElementById('regName')
            : (mode === 'forgot' ? document.getElementById('forgotEmail') : document.getElementById('loginEmail'));
        if (firstInput) {
            requestAnimationFrame(() => firstInput.focus());
        }

        const tokenInputs = [
            document.getElementById('regSecurityToken'),
            document.getElementById('forgotSecurityToken')
        ];
        try {
            let tokenVal = null;
            for (const input of tokenInputs) {
                if (input && (!input.value || input.value === '') && cfg.securityTokenUrl) {
                    if (!tokenVal) {
                        const res = await fetch(cfg.securityTokenUrl);
                        const data = await res.json();
                        tokenVal = data.token;
                    }
                    if (tokenVal) input.value = tokenVal;
                }
            }
        } catch (e) {}
    };

    window.closeAuthModal = function () {
        const modal = document.getElementById('authModal');
        if (!modal || modal.style.display === 'none' || modal.style.display === '') return;
        modal.style.display = 'none';
        setBackgroundInert(false);
        hideAlert();
        restoreFocus(lastModalTrigger);
        lastModalTrigger = null;
    };

    window.switchTab = function (mode) {
        const tabSwitch = document.querySelector('.tab-switch');
        const tabLogin = document.getElementById('tabLogin');
        const tabRegister = document.getElementById('tabRegister');
        const formLogin = document.getElementById('formLogin');
        const formRegister = document.getElementById('formRegister');
        const formForgot = document.getElementById('formForgot');
        const modalTitle = document.getElementById('modalTitle');

        hideAlert();

        if (mode === 'register') {
            if (tabSwitch) tabSwitch.style.display = 'flex';
            if (tabRegister) tabRegister.classList.add('active');
            if (tabLogin) tabLogin.classList.remove('active');
            if (formRegister) formRegister.style.display = 'block';
            if (formLogin) formLogin.style.display = 'none';
            if (formForgot) formForgot.style.display = 'none';
            if (modalTitle) modalTitle.textContent = 'Daftar Akun AR Tilik Gratis';
        } else if (mode === 'forgot') {
            if (tabSwitch) tabSwitch.style.display = 'none';
            if (formLogin) formLogin.style.display = 'none';
            if (formRegister) formRegister.style.display = 'none';
            if (formForgot) {
                formForgot.style.display = 'block';
                const loginEmailVal = document.getElementById('loginEmail')?.value;
                const forgotEmailInput = document.getElementById('forgotEmail');
                if (loginEmailVal && forgotEmailInput && !forgotEmailInput.value) {
                    forgotEmailInput.value = loginEmailVal;
                }
            }
            if (modalTitle) modalTitle.textContent = 'Atur Ulang Kata Sandi';
        } else {
            if (tabSwitch) tabSwitch.style.display = 'flex';
            if (tabLogin) tabLogin.classList.add('active');
            if (tabRegister) tabRegister.classList.remove('active');
            if (formLogin) formLogin.style.display = 'block';
            if (formRegister) formRegister.style.display = 'none';
            if (formForgot) formForgot.style.display = 'none';
            if (modalTitle) modalTitle.textContent = 'Masuk ke AR Tilik';
        }
    };

    function showAlert(msg, isError) {
        if (typeof isError === 'undefined') isError = true;
        const alertBox = document.getElementById('authAlert');
        if (!alertBox) return;
        alertBox.innerHTML = msg;
        alertBox.className = 'alert-box ' + (isError ? 'alert-error' : 'alert-success');
        alertBox.style.display = 'block';
    }
    window.showAlert = showAlert;

    function hideAlert() {
        const alertBox = document.getElementById('authAlert');
        if (alertBox) alertBox.style.display = 'none';
    }
    window.hideAlert = hideAlert;

    window.handleLogin = async function (e) {
        e.preventDefault();
        hideAlert();

        const form = e.target;
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalBtnText = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span>Memverifikasi...</span>';

        const formData = new FormData(form);
        const loginEmailVal = formData.get('email');

        try {
            const res = await fetch(cfg.loginUrl || '/auth/login', {
                method: 'POST',
                headers: { 'Accept': 'application/json' },
                body: formData
            });
            const data = await res.json();

            if (!res.ok || data.status === 'error') {
                if (data.needs_verification) {
                    const safeEmail = String(data.email || loginEmailVal || '').replace(/'/g, "\\'");
                    showAlert('⚠️ ' + data.message +
                        '<br><button type="button" onclick="resendVerification(\'' + safeEmail + '\')" style="background:none; border:none; padding:0; cursor:pointer; font-family:inherit; color:var(--primary); font-weight:700; text-decoration:underline; display:inline-block; margin-top:8px;">✉️ Kirim Ulang Tautan Aktivasi Sekarang</button>', true);
                } else {
                    showAlert(data.message || 'Gagal masuk akun. Periksa email & kata sandi.', true);
                }
            } else {
                showAlert(data.message || 'Berhasil masuk! Mengarahkan...', false);
                setTimeout(() => {
                    const urlParams = new URLSearchParams(window.location.search);
                    const redirectUrl = urlParams.get('redirect') || data.redirect || cfg.dashboardUrl || '/dashboard';
                    window.location.href = redirectUrl;
                }, 600);
            }
        } catch (err) {
            showAlert('Kendala koneksi peladen. Silakan coba kembali.', true);
        } finally {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalBtnText;
        }
    };

    window.handleRegister = async function (e) {
        e.preventDefault();
        hideAlert();

        const form = e.target;
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalBtnText = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span>Mengirim Email Aktivasi...</span>';

        const formData = new FormData(form);
        const userEmail = String(formData.get('email') || '');
        const safeEmail = userEmail.replace(/'/g, "\\'");

        try {
            const res = await fetch(cfg.registerUrl || '/auth/register', {
                method: 'POST',
                headers: { 'Accept': 'application/json' },
                body: formData
            });
            const data = await res.json();

            if (!res.ok || data.status === 'error') {
                if (data.needs_verification) {
                    showAlert('⚠️ ' + (data.message || 'Email sudah terdaftar namun belum diverifikasi.') +
                        '<br><button type="button" onclick="resendVerification(\'' + safeEmail + '\')" style="background:none; border:none; padding:0; cursor:pointer; font-family:inherit; color:var(--primary); font-weight:700; text-decoration:underline; display:inline-block; margin-top:6px;">Kirim Ulang Email Aktivasi</button>', true);
                } else {
                    showAlert(data.message || 'Gagal mendaftar. Silakan lengkapi formulir.', true);
                }
            } else {
                form.reset();
                showAlert('🎉 <strong>Pendaftaran Berhasil!</strong><br>' +
                    'Kami telah mengirimkan tautan verifikasi ke <strong>' + userEmail + '</strong>.<br>' +
                    '<div style="background:#fffbeb; border:1px solid #fde68a; border-radius:8px; padding:10px 12px; margin-top:10px; font-size:12px; color:#92400e; text-align:left; line-height:1.5;">' +
                    '📬 <strong>Perhatian:</strong> Apabila email belum muncul di <strong>Kotak Masuk (Inbox)</strong> dalam beberapa menit, mohon periksa juga folder <strong>SPAM / Junk</strong> atau tab <em>Promosi</em>. Tandai sebagai <strong>"Bukan Spam"</strong> agar pengiriman pemberitahuan selanjutnya lancar.' +
                    '</div>' +
                    '<div style="margin-top:12px;"><button type="button" onclick="resendVerification(\'' + safeEmail + '\')" style="background:none; border:none; padding:0; cursor:pointer; font-family:inherit; font-size:12px; color:var(--primary); font-weight:700; text-decoration:underline;">Belum menerima email? Kirim Ulang</button></div>', false);
            }
        } catch (err) {
            showAlert('Kendala koneksi peladen. Silakan coba kembali.', true);
        } finally {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalBtnText;
        }
    };

    window.resendVerification = async function (email) {
        if (!email) {
            email = prompt('Masukkan alamat email yang Anda daftarkan:');
        }
        if (!email) return;

        showAlert('⏳ Sedang mengirimkan ulang tautan verifikasi...', false);
        try {
            const res = await fetch(cfg.resendVerificationUrl || '/auth/resend-verification', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ email: email })
            });
            const data = await res.json();
            if (data.status === 'success') {
                showAlert('✉️ ' + data.message, false);
            } else {
                showAlert(data.message || 'Gagal mengirim ulang email verifikasi.', true);
            }
        } catch (err) {
            showAlert('Kendala koneksi peladen. Silakan coba kembali.', true);
        }
    };

    window.handleForgotPassword = async function (e) {
        e.preventDefault();
        hideAlert();

        const form = e.target;
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalBtnText = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span>Mengirim Tautan...</span>';

        const formData = new FormData(form);

        try {
            const res = await fetch(cfg.forgotPasswordUrl || '/auth/forgot-password', {
                method: 'POST',
                headers: { 'Accept': 'application/json' },
                body: formData
            });
            const data = await res.json();

            if (!res.ok || data.status === 'error') {
                showAlert(data.message || 'Gagal mengajukan atur ulang kata sandi.', true);
            } else {
                form.reset();
                showAlert('✉️ <strong>Permintaan Terkirim!</strong><br>' +
                    (data.message || 'Tautan atur ulang kata sandi telah dikirimkan ke email Anda.') +
                    '<div style="margin-top:10px;"><button type="button" onclick="switchTab(\'login\')" style="background:none; border:none; padding:0; cursor:pointer; font-family:inherit; font-size:12px; color:var(--primary); font-weight:700; text-decoration:underline;">← Kembali ke Halaman Masuk</button></div>', false);
            }
        } catch (err) {
            showAlert('Kendala koneksi peladen. Silakan coba kembali.', true);
        } finally {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalBtnText;
        }
    };

    /* --- Contact Modal --- */
    window.openContactModal = async function (subject) {
        if (typeof subject === 'undefined') subject = 'Pertanyaan Umum';
        const modal = document.getElementById('contactModal');
        if (!modal) return;
        lastModalTrigger = document.activeElement;
        modal.style.display = 'flex';
        setBackgroundInert(true);
        hideContactAlert();

        const subjectSelect = document.getElementById('contactSubject');
        if (subjectSelect) {
            for (let i = 0; i < subjectSelect.options.length; i++) {
                if (subjectSelect.options[i].value === subject || subjectSelect.options[i].text.includes(subject)) {
                    subjectSelect.selectedIndex = i;
                    break;
                }
            }
        }
        const nameInput = document.getElementById('contactName');
        if (nameInput) requestAnimationFrame(() => nameInput.focus());

        await generateMathCaptcha();
    };

    window.closeContactModal = function () {
        const modal = document.getElementById('contactModal');
        if (!modal || modal.style.display === 'none' || modal.style.display === '') return;
        modal.style.display = 'none';
        setBackgroundInert(false);
        hideContactAlert();
        restoreFocus(lastModalTrigger);
        lastModalTrigger = null;
    };

    async function generateMathCaptcha() {
        const label = document.getElementById('mathProblemLabel');
        const tokenInput = document.getElementById('mathChallengeToken');
        const ansInput = document.getElementById('mathAnswerInput');
        const secTokenInput = document.getElementById('contactSecurityToken');

        if (label) label.textContent = 'Memuat soal...';
        if (ansInput) ansInput.value = '';

        try {
            const res = await fetch(cfg.contactChallengeUrl || '/contact/challenge');
            const data = await res.json();
            if (data.status === 'success' && data.challenge) {
                if (label) label.textContent = data.challenge.question + ' = ?';
                if (tokenInput) tokenInput.value = data.challenge.token;
                if (data.time_token && secTokenInput) {
                    secTokenInput.value = data.time_token;
                }
            } else if (label) {
                label.textContent = '3 + 4 = ?';
            }
        } catch (e) {
            if (label) label.textContent = '3 + 4 = ?';
        }
    }
    window.generateMathCaptcha = generateMathCaptcha;

    function showContactAlert(msg, isError) {
        if (typeof isError === 'undefined') isError = true;
        const alertBox = document.getElementById('contactAlert');
        if (!alertBox) return;
        alertBox.innerHTML = msg;
        alertBox.className = 'alert-box ' + (isError ? 'alert-error' : 'alert-success');
        alertBox.style.display = 'block';
    }

    function hideContactAlert() {
        const alertBox = document.getElementById('contactAlert');
        if (alertBox) alertBox.style.display = 'none';
    }

    window.handleEmailInputDebounced = function (val) {
        clearTimeout(emailDebounceTimer);
        const feedbackEl = document.getElementById('emailVerifyFeedback');
        const emailInput = document.getElementById('contactEmail');
        if (!feedbackEl || !emailInput) return;

        val = (val || '').trim();

        if (val === '') {
            feedbackEl.innerHTML = '<span style="color:var(--text-muted);">✉️ Masukkan email aktif (domain institusi atau email resmi Anda)</span>';
            emailInput.style.borderColor = '';
            isEmailVerifiedActive = null;
            return;
        }

        if (!val.includes('@') || !val.includes('.') || val.length < 5) {
            feedbackEl.innerHTML = '<span style="color:#64748b;">⏳ Melengkapi format email...</span>';
            emailInput.style.borderColor = '';
            isEmailVerifiedActive = null;
            return;
        }

        feedbackEl.innerHTML = '<span style="color:var(--primary); font-weight:600;">⏳ Memeriksa keaktifan server email di internet...</span>';

        emailDebounceTimer = setTimeout(async () => {
            try {
                const validateBase = cfg.contactValidateEmailUrl || '/contact/validate-email';
                const res = await fetch(validateBase + '?email=' + encodeURIComponent(val));
                const data = await res.json();

                if (data.status === 'success' && data.valid) {
                    isEmailVerifiedActive = true;
                    emailInput.style.borderColor = '#10b981';
                    let html = '<span style="color:#059669; font-weight:600;">✓ Server email aktif & domain valid (' + (data.domain || '') + ')</span>';
                    if (data.suggestion) {
                        html += '<span style="color:#d97706; margin-top:2px;">💡 Apakah maksud Anda: <button type="button" onclick="applyEmailSuggestion(\'' + data.suggestion + '\')" style="background:none; border:none; padding:0; cursor:pointer; font-family:inherit; color:#d97706; font-weight:700; text-decoration:underline;">' + data.suggestion + '</button>?</span>';
                    }
                    feedbackEl.innerHTML = html;
                } else {
                    isEmailVerifiedActive = false;
                    emailInput.style.borderColor = '#ef4444';
                    let html = '<span style="color:#dc2626; font-weight:600;">⚠️ ' + (data.reason || 'Email terindikasi tidak aktif atau fiktif.') + '</span>';
                    if (data.suggestion) {
                        html += '<span style="color:#d97706; margin-top:2px;">💡 Apakah maksud Anda: <button type="button" onclick="applyEmailSuggestion(\'' + data.suggestion + '\')" style="background:none; border:none; padding:0; cursor:pointer; font-family:inherit; color:#d97706; font-weight:700; text-decoration:underline;">' + data.suggestion + '</button>?</span>';
                    }
                    feedbackEl.innerHTML = html;
                }
            } catch (e) {
                feedbackEl.innerHTML = '<span style="color:var(--text-muted);">Format email diterima.</span>';
            }
        }, 350);
    };

    window.applyEmailSuggestion = function (correctedEmail) {
        const emailInput = document.getElementById('contactEmail');
        if (emailInput) {
            emailInput.value = correctedEmail;
            window.handleEmailInputDebounced(correctedEmail);
        }
    };

    window.handleContactSubmit = async function (e) {
        e.preventDefault();
        hideContactAlert();

        if (isEmailVerifiedActive === false) {
            showContactAlert('⚠️ Alamat email yang Anda masukkan tidak valid atau tidak memiliki server email aktif. Harap gunakan email aktif yang dapat menerima balasan.', true);
            const emailInput = document.getElementById('contactEmail');
            if (emailInput) emailInput.focus();
            return;
        }

        const form = e.target;
        const submitBtn = document.getElementById('btnSubmitContact');
        const originalBtnText = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span>Mengirim Pesan...</span>';

        const formData = new FormData(form);

        try {
            const res = await fetch(cfg.contactSendUrl || '/contact/send', {
                method: 'POST',
                headers: { 'Accept': 'application/json' },
                body: formData
            });
            const data = await res.json();

            if (!res.ok || data.status === 'error') {
                showContactAlert(data.message || 'Gagal mengirimkan pesan. Silakan periksa kembali formulir Anda.', true);
                generateMathCaptcha();
            } else {
                form.reset();
                showContactAlert('🎉 <strong>Pesan Berhasil Terkirim!</strong><br>' +
                    (data.message || 'Terima kasih, tim pengembang akan merespons pesan Anda via email dalam 1x24 jam kerja.'), false);
                setTimeout(() => {
                    generateMathCaptcha();
                }, 500);
            }
        } catch (err) {
            showContactAlert('Kendala koneksi peladen saat mengirim pesan. Silakan coba kembali.', true);
        } finally {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalBtnText;
        }
    };

    /* --- Legal Modal --- */
    window.openLegalModal = async function (type, e) {
        if (e) e.preventDefault();

        const modal = document.getElementById('legalModal');
        if (!modal) return;
        lastModalTrigger = document.activeElement;
        const titleEl = document.getElementById('legalModalTitle');
        const bodyEl = document.getElementById('legalModalBody');
        const fullLink = document.getElementById('legalFullPageLink');

        const isPrivacy = (type === 'privacy');
        const endpoint = isPrivacy ? (cfg.privacyUrl || '/privacy') : (cfg.termsUrl || '/terms');
        const pageTitle = isPrivacy ? 'Kebijakan Privasi & Kedaulatan Data' : 'Ketentuan Layanan (Terms of Service)';

        if (titleEl) titleEl.textContent = pageTitle;
        if (fullLink) fullLink.href = endpoint;
        modal.style.display = 'flex';
        setBackgroundInert(true);

        const closeBtn = modal.querySelector('.modal-close');
        if (closeBtn) requestAnimationFrame(() => closeBtn.focus());

        if (legalCache[type]) {
            if (bodyEl) bodyEl.innerHTML = legalCache[type];
            return;
        }

        if (bodyEl) {
            bodyEl.innerHTML = '<div style="text-align: center; padding: 50px 20px; color: var(--text-muted);">' +
                '<div style="font-size: 24px; margin-bottom: 8px;">⏳</div>' +
                '<div>Memuat dokumen ' + pageTitle + '...</div>' +
                '</div>';
        }

        try {
            const res = await fetch(endpoint, { headers: { 'Accept': 'application/json' } });
            const data = await res.json();
            if (data.status === 'success' && data.html) {
                legalCache[type] = data.html;
                if (bodyEl) bodyEl.innerHTML = data.html;
            } else if (bodyEl) {
                bodyEl.innerHTML = '<div class="alert-box alert-error" style="display:block;">Gagal memuat dokumen. Silakan buka melalui <a href="' + endpoint + '" target="_blank" style="color:var(--primary); font-weight:700;">Halaman Penuh</a>.</div>';
            }
        } catch (err) {
            if (bodyEl) {
                bodyEl.innerHTML = '<div class="alert-box alert-error" style="display:block;">Kendala koneksi jaringan saat memuat dokumen. Silakan buka melalui <a href="' + endpoint + '" target="_blank" style="color:var(--primary); font-weight:700;">Halaman Penuh</a>.</div>';
            }
        }
    };

    window.closeLegalModal = function () {
        const modal = document.getElementById('legalModal');
        if (!modal || modal.style.display === 'none' || modal.style.display === '') return;
        modal.style.display = 'none';
        setBackgroundInert(false);
        restoreFocus(lastModalTrigger);
        lastModalTrigger = null;
    };

    /* --- Legacy toggleFaq fallback (only if button accordion is still present) --- */
    window.toggleFaq = function (btn) {
        const card = btn.closest('.faq-card');
        if (!card) return;
        const isActive = card.classList.contains('active');
        document.querySelectorAll('.faq-card').forEach(function (c) {
            c.classList.remove('active');
            const otherBtn = c.querySelector('.faq-btn');
            if (otherBtn) otherBtn.setAttribute('aria-expanded', 'false');
        });
        if (!isActive) {
            card.classList.add('active');
            btn.setAttribute('aria-expanded', 'true');
        }
    };

    /* --- Scroll to Top & Fixed Navbar --- */
    window.scrollToTop = function () {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    };

    window.addEventListener('scroll', function () {
        const scrollY = window.pageYOffset || document.documentElement.scrollTop;
        const scrollBtn = document.getElementById('scrollTopBtn');
        const navbar = document.querySelector('.navbar');

        if (scrollBtn) {
            scrollBtn.classList.toggle('visible', scrollY > 280);
        }
        if (navbar) {
            navbar.classList.toggle('scrolled', scrollY > 15);
        }
    }, { passive: true });

    /* --- Dropdown Controls --- */
    window.toggleNavDropdown = function (event) {
        event.stopPropagation();
        const dropdown = event.currentTarget.closest('.nav-item-dropdown');
        if (!dropdown) return;
        const isOpen = dropdown.classList.contains('active');
        window.closeAllNavDropdowns();
        if (!isOpen) {
            dropdown.classList.add('active');
            event.currentTarget.setAttribute('aria-expanded', 'true');
        }
    };

    window.closeAllNavDropdowns = function () {
        document.querySelectorAll('.nav-item-dropdown').forEach(function (d) {
            d.classList.remove('active');
            const btn = d.querySelector('.nav-dropdown-toggle');
            if (btn) btn.setAttribute('aria-expanded', 'false');
        });
    };

    document.addEventListener('click', function (e) {
        if (!e.target.closest('.nav-item-dropdown')) {
            window.closeAllNavDropdowns();
        }
    });

    /* --- Mobile Drawer Controls --- */
    window.toggleMobileNav = function () {
        const drawer = document.getElementById('mobileNavDrawer');
        if (!drawer) return;
        if (drawer.classList.contains('active')) {
            window.closeMobileNav();
        } else {
            window.openMobileNav();
        }
    };

    window.openMobileNav = function () {
        const drawer = document.getElementById('mobileNavDrawer');
        const overlay = document.getElementById('mobileNavOverlay');
        const btn = document.getElementById('hamburgerBtn');
        if (!drawer || !overlay) return;

        lastDrawerTrigger = document.activeElement;
        drawer.classList.add('active');
        overlay.classList.add('active');
        drawer.setAttribute('aria-hidden', 'false');
        drawer.removeAttribute('inert');
        setBackgroundInert(true);
        if (btn) {
            btn.classList.add('active');
            btn.setAttribute('aria-expanded', 'true');
        }
        document.body.style.overflow = 'hidden';
        const closeBtn = drawer.querySelector('.mobile-drawer-close');
        if (closeBtn) requestAnimationFrame(() => closeBtn.focus());
    };

    window.closeMobileNav = function () {
        const drawer = document.getElementById('mobileNavDrawer');
        const overlay = document.getElementById('mobileNavOverlay');
        const btn = document.getElementById('hamburgerBtn');
        if (!drawer || !overlay || !drawer.classList.contains('active')) return;

        drawer.classList.remove('active');
        overlay.classList.remove('active');
        drawer.setAttribute('aria-hidden', 'true');
        drawer.setAttribute('inert', '');
        setBackgroundInert(false);
        if (btn) {
            btn.classList.remove('active');
            btn.setAttribute('aria-expanded', 'false');
        }
        document.body.style.overflow = '';
        restoreFocus(lastDrawerTrigger || btn);
        lastDrawerTrigger = null;
    };

    /* --- Modals light-dismiss & Escape --- */
    window.addEventListener('click', function (event) {
        const authModal = document.getElementById('authModal');
        const contactModal = document.getElementById('contactModal');
        const legalModal = document.getElementById('legalModal');

        if (event.target === authModal) window.closeAuthModal();
        if (event.target === contactModal) window.closeContactModal();
        if (event.target === legalModal) window.closeLegalModal();
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            window.closeAuthModal();
            window.closeContactModal();
            window.closeLegalModal();
            window.closeMobileNav();
            window.closeAllNavDropdowns();
        }
    });

    /* --- Testimonials Filter & Carousel (only initialized on pages with #testimonialsTrack) --- */
    function initTestimonials() {
        const track = document.getElementById('testimonialsTrack');
        if (!track) return;

        function getCardsPerPage() {
            const w = window.innerWidth;
            if (w < 768) return 1;
            if (w < 1024) return 2;
            return 3;
        }

        function getVisibleCards() {
            return Array.from(document.querySelectorAll('.testi-card')).filter(c => c.style.display !== 'none');
        }

        function updateActiveDotAndArrows() {
            const visibleCards = getVisibleCards();
            if (visibleCards.length === 0) return;

            const cardsPerPage = getCardsPerPage();
            const maxStep = Math.max(0, visibleCards.length - cardsPerPage);
            let currentStep = 0;
            const currentScroll = track.scrollLeft;
            let minDiff = Infinity;

            visibleCards.slice(0, maxStep + 1).forEach((card, idx) => {
                const offset = card.offsetLeft - track.offsetLeft;
                const diff = Math.abs(currentScroll - offset);
                if (diff < minDiff) {
                    minDiff = diff;
                    currentStep = idx;
                }
            });

            document.querySelectorAll('.testi-dot').forEach((dot, idx) => {
                dot.classList.toggle('active', idx === currentStep);
            });

            const counterEl = document.getElementById('testiCounter');
            if (counterEl) counterEl.textContent = `${currentStep + 1} dari ${maxStep + 1}`;

            const prevDesktop = document.getElementById('testiPrevBtnDesktop');
            const nextDesktop = document.getElementById('testiNextBtnDesktop');
            if (prevDesktop) prevDesktop.disabled = (currentStep <= 0);
            if (nextDesktop) nextDesktop.disabled = (currentStep >= maxStep);

            const prevMobile = document.getElementById('testiPrevBtnMobile');
            const nextMobile = document.getElementById('testiNextBtnMobile');
            if (prevMobile) prevMobile.disabled = (currentStep <= 0);
            if (nextMobile) nextMobile.disabled = (currentStep >= maxStep);
        }

        function scrollTestiToStep(stepIndex) {
            const visibleCards = getVisibleCards();
            if (!visibleCards[stepIndex]) return;
            const targetOffset = visibleCards[stepIndex].offsetLeft - track.offsetLeft;
            track.scrollTo({ left: targetOffset, behavior: 'smooth' });
        }

        window.scrollTesti = function (direction) {
            const visibleCards = getVisibleCards();
            if (visibleCards.length === 0) return;
            const cardsPerPage = getCardsPerPage();
            const maxStep = Math.max(0, visibleCards.length - cardsPerPage);
            if (maxStep === 0) return;

            let currentStep = 0;
            const currentScroll = track.scrollLeft;
            let minDiff = Infinity;

            visibleCards.slice(0, maxStep + 1).forEach((card, idx) => {
                const offset = card.offsetLeft - track.offsetLeft;
                const diff = Math.abs(currentScroll - offset);
                if (diff < minDiff) {
                    minDiff = diff;
                    currentStep = idx;
                }
            });

            const nextStep = Math.min(maxStep, Math.max(0, currentStep + direction));
            scrollTestiToStep(nextStep);
        };

        function updateTestiCarousel() {
            const visibleCards = getVisibleCards();
            const count = visibleCards.length;
            const emptyState = document.getElementById('testiEmptyState');
            const prevDesktop = document.getElementById('testiPrevBtnDesktop');
            const nextDesktop = document.getElementById('testiNextBtnDesktop');
            const controlsBar = document.getElementById('testiControlsBar');
            const dotsContainer = document.getElementById('testiDots');

            if (count === 0) {
                if (emptyState) emptyState.style.display = 'block';
                track.style.display = 'none';
                if (prevDesktop) prevDesktop.style.display = 'none';
                if (nextDesktop) nextDesktop.style.display = 'none';
                if (controlsBar) controlsBar.style.display = 'none';
                return;
            }

            if (emptyState) emptyState.style.display = 'none';
            track.style.display = 'flex';

            const cardsPerPage = getCardsPerPage();
            const needsCarousel = count > cardsPerPage;

            if (!needsCarousel) {
                track.classList.add('no-carousel');
                if (prevDesktop) prevDesktop.style.display = 'none';
                if (nextDesktop) nextDesktop.style.display = 'none';
                if (controlsBar) controlsBar.style.display = 'none';
                track.scrollLeft = 0;
                return;
            }

            track.classList.remove('no-carousel');
            if (prevDesktop) prevDesktop.style.display = 'flex';
            if (nextDesktop) nextDesktop.style.display = 'flex';
            if (controlsBar) controlsBar.style.display = 'flex';

            const maxStep = Math.max(0, count - cardsPerPage);
            if (dotsContainer) {
                dotsContainer.innerHTML = '';
                for (let i = 0; i <= maxStep; i++) {
                    const dot = document.createElement('button');
                    dot.type = 'button';
                    dot.className = 'testi-dot' + (i === 0 ? ' active' : '');
                    dot.setAttribute('aria-label', `Testimoni slide ${i + 1}`);
                    dot.onclick = () => scrollTestiToStep(i);
                    dotsContainer.appendChild(dot);
                }
            }
            updateActiveDotAndArrows();
        }

        window.filterTestimonials = function (cat, btn) {
            document.querySelectorAll('.testi-filter-btn').forEach(b => {
                b.classList.remove('active-testi-filter');
                b.style.background = '';
                b.style.color = '';
                b.style.borderColor = '';
            });
            if (btn) {
                btn.classList.add('active-testi-filter');
                if (typeof btn.scrollIntoView === 'function') {
                    btn.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
                }
            }

            document.querySelectorAll('.testi-card').forEach(card => {
                card.style.display = (cat === 'all' || card.dataset.category === cat) ? 'flex' : 'none';
            });
            track.scrollLeft = 0;
            updateTestiCarousel();
        };

        let scrollAnim = null;
        track.addEventListener('scroll', () => {
            if (scrollAnim) cancelAnimationFrame(scrollAnim);
            scrollAnim = requestAnimationFrame(updateActiveDotAndArrows);
        }, { passive: true });
        window.addEventListener('resize', updateTestiCarousel);
        updateTestiCarousel();
    }

    document.addEventListener('DOMContentLoaded', function () {
        initTestimonials();

        const urlParams = new URLSearchParams(window.location.search);
        const authParam = urlParams.get('auth');
        const verifiedParam = urlParams.get('verified');
        const resetParam = urlParams.get('reset');
        const reasonParam = urlParams.get('reason');

        if (verifiedParam === 'error') {
            window.openAuthModal('login');
            let errText = 'Tautan verifikasi tidak valid atau telah kedaluwarsa.';
            if (reasonParam === 'expired') {
                const unvEmail = String(urlParams.get('email') || '').replace(/'/g, "\\'");
                errText = '⏱️ Tautan verifikasi telah kedaluwarsa (berlaku 24 jam).<br>' +
                    '<button type="button" onclick="resendVerification(\'' + unvEmail + '\')" style="background:none; border:none; padding:0; cursor:pointer; font-family:inherit; color:var(--primary); font-weight:700; text-decoration:underline; display:inline-block; margin-top:6px;">Klik di sini untuk mengirimkan tautan aktivasi baru.</button>';
            }
            showAlert(errText, true);
        } else if (verifiedParam === 'success') {
            window.openAuthModal('login');
            showAlert('🎉 Alamat email Anda telah berhasil diverifikasi! Silakan masuk ke akun Anda.', false);
        } else if (resetParam === 'success') {
            window.openAuthModal('login');
            showAlert('🎉 Kata sandi Anda berhasil diperbarui! Silakan masuk menggunakan kata sandi baru Anda.', false);
        } else if (resetParam === 'error') {
            window.openAuthModal('login');
            let errText = 'Tautan atur ulang kata sandi tidak valid atau telah kedaluwarsa.';
            if (reasonParam === 'expired') {
                errText = '⏱️ Tautan atur ulang kata sandi telah kedaluwarsa (berlaku 1 jam).<br>' +
                    '<button type="button" onclick="switchTab(\'forgot\')" style="background:none; border:none; padding:0; cursor:pointer; font-family:inherit; color:var(--primary); font-weight:700; text-decoration:underline; display:inline-block; margin-top:6px;">Klik di sini untuk mengajukan permintaan baru.</button>';
            }
            showAlert(errText, true);
        } else if (authParam === 'login' || authParam === 'register' || authParam === 'forgot') {
            window.openAuthModal(authParam);
            if (cfg.flashNotice) {
                showAlert(cfg.flashNotice, true);
            } else if (cfg.flashSuccess) {
                showAlert(cfg.flashSuccess, false);
            }
            const paramEmail = urlParams.get('email');
            if (paramEmail) {
                const regEmailInput = document.querySelector('#formRegister input[name="email"]');
                const logEmailInput = document.querySelector('#formLogin input[name="email"]');
                if (regEmailInput) regEmailInput.value = paramEmail;
                if (logEmailInput) logEmailInput.value = paramEmail;
            }
            const paramRef = urlParams.get('ref');
            if (paramRef) {
                const regRefInput = document.getElementById('regReferralCode');
                const badge = document.getElementById('referralBonusBadge');
                const codeSpan = document.getElementById('displayRefCode');
                if (regRefInput) regRefInput.value = paramRef.toUpperCase();
                if (codeSpan) codeSpan.textContent = paramRef.toUpperCase();
                if (badge) badge.style.display = 'block';
            }
        } else if (window.location.hash === '#auth-section') {
            window.openAuthModal('login');
        }

        function revealVisibleAos() {
            const viewBottom = window.innerHeight || document.documentElement.clientHeight;
            const atBottom = (window.scrollY + viewBottom) >= (document.documentElement.scrollHeight - 40);
            document.querySelectorAll('[data-aos]:not(.aos-animate)').forEach((el) => {
                const rect = el.getBoundingClientRect();
                if (atBottom || rect.top <= viewBottom - 16) {
                    el.classList.add('aos-animate');
                }
            });
        }

        // Bidirectional AOS scroll tracker + fallback visibilitas viewport
        let lastScrollTop = window.pageYOffset || document.documentElement.scrollTop;
        document.body.classList.add('scrolling-down');

        window.addEventListener('scroll', function () {
            const currentScrollTop = window.pageYOffset || document.documentElement.scrollTop;
            if (Math.abs(currentScrollTop - lastScrollTop) > 6) {
                if (currentScrollTop > lastScrollTop) {
                    document.body.classList.add('scrolling-down');
                    document.body.classList.remove('scrolling-up');
                } else if (currentScrollTop < lastScrollTop) {
                    document.body.classList.add('scrolling-up');
                    document.body.classList.remove('scrolling-down');
                }
                lastScrollTop = currentScrollTop <= 0 ? 0 : currentScrollTop;
            }
            revealVisibleAos();
        }, { passive: true });

        function refreshAosOffsets() {
            if (typeof AOS !== 'undefined' && typeof AOS.refreshHard === 'function') {
                AOS.refreshHard();
            } else if (typeof AOS !== 'undefined' && typeof AOS.refresh === 'function') {
                AOS.refresh();
            }
            revealVisibleAos();
        }

        let aosInitialized = false;
        function initAOS() {
            if (typeof AOS !== 'undefined' && !aosInitialized) {
                aosInitialized = true;
                AOS.init({
                    duration: 550,
                    easing: 'ease-out-cubic',
                    once: true,
                    mirror: false,
                    offset: 40,
                    disable: () => window.matchMedia('(prefers-reduced-motion: reduce)').matches
                });
                refreshAosOffsets();
            }
        }
        if (typeof AOS !== 'undefined') {
            initAOS();
        }
        window.addEventListener('load', () => {
            initAOS();
            refreshAosOffsets();
        });
        if (document.fonts && document.fonts.ready) {
            document.fonts.ready.then(refreshAosOffsets).catch(() => {});
        }
        if ('ResizeObserver' in window && document.body) {
            let resizeRaf = 0;
            const bodyObserver = new ResizeObserver(() => {
                if (resizeRaf) cancelAnimationFrame(resizeRaf);
                resizeRaf = requestAnimationFrame(() => {
                    refreshAosOffsets();
                });
            });
            bodyObserver.observe(document.body);
        }
    });
})();
