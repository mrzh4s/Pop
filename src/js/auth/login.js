const LoginHandler = (() => {
    const config = {
        validation: {
            email: {
                required: true,
                minLength: 5,
                maxLength: 254,
                pattern: /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/,
                messages: {
                    required: "Email diperlukan",
                    minLength: "Email mestilah sekurang-kurangnya 5 aksara",
                    maxLength: "Email tidak boleh melebihi 254 aksara",
                    pattern: "Sila masukkan alamat email yang sah (contoh: nama@domain.com)"
                }
            },
            password: {
                required: true,
                messages: {
                    required: "Kata Laluan Diperlukan"
                }
            }
        },
        redirectDelay: 1500,
        maxLoginAttempts: 5,
        lockoutDuration: 15 * 60 * 1000 // 15 minutes
    };

    let form, submitButton;
    let isValidating = false;

    // ============== SECURITY HELPERS ==============

    /**
     * Rate limiting for login attempts (client-side backup)
     */
    const RateLimiter = {
        getAttempts: () => {
            const attempts = localStorage.getItem('login_attempts');
            const timestamp = localStorage.getItem('login_attempts_timestamp');
            
            if (!attempts || !timestamp) return 0;
            
            // Reset if lockout period has passed
            if (Date.now() - parseInt(timestamp) > config.lockoutDuration) {
                localStorage.removeItem('login_attempts');
                localStorage.removeItem('login_attempts_timestamp');
                return 0;
            }
            
            return parseInt(attempts);
        },

        incrementAttempts: () => {
            const currentAttempts = RateLimiter.getAttempts() + 1;
            localStorage.setItem('login_attempts', currentAttempts.toString());
            localStorage.setItem('login_attempts_timestamp', Date.now().toString());
            return currentAttempts;
        },

        resetAttempts: () => {
            localStorage.removeItem('login_attempts');
            localStorage.removeItem('login_attempts_timestamp');
        },

        isLocked: () => {
            return RateLimiter.getAttempts() >= config.maxLoginAttempts;
        },

        getRemainingLockTime: () => {
            const timestamp = localStorage.getItem('login_attempts_timestamp');
            if (!timestamp) return 0;
            
            const elapsed = Date.now() - parseInt(timestamp);
            const remaining = config.lockoutDuration - elapsed;
            return Math.max(0, remaining);
        }
    };

    /**
     * Input sanitization
     */
    const sanitizeInput = (input) => {
        return input.trim().replace(/[<>&"']/g, '');
    };

    // ============== VALIDATION FUNCTIONS ==============

    const validateForm = () => {
        const emailInput = form.querySelector('[name="email"]');
        const passwordInput = form.querySelector('[name="password"]');
        let isValid = true;

        clearValidationErrors();

        // Validate email
        const emailValue = sanitizeInput(emailInput.value);
        if (!emailValue) {
            showFieldError('email', config.validation.email.messages.required);
            isValid = false;
        } else if (emailValue.length < config.validation.email.minLength) {
            showFieldError('email', config.validation.email.messages.minLength);
            isValid = false;
        } else if (emailValue.length > config.validation.email.maxLength) {
            showFieldError('email', config.validation.email.messages.maxLength);
            isValid = false;
        } else if (!config.validation.email.pattern.test(emailValue)) {
            showFieldError('email', config.validation.email.messages.pattern);
            isValid = false;
        }

        // Validate password
        const passwordValue = passwordInput.value.trim();
        if (!passwordValue) {
            showFieldError('password', config.validation.password.messages.required);
            isValid = false;
        }

        return isValid;
    };

    const showFieldError = (fieldName, message) => {
        const input = form.querySelector(`[name="${fieldName}"]`);
        const formItem = input.closest('.kt-form-item');

        if (formItem) {
            input.setAttribute('aria-invalid', 'true');
            input.classList.add('kt-input-invalid');

            let messageEl = formItem.querySelector('.kt-form-message');
            if (!messageEl) {
                messageEl = document.createElement('div');
                messageEl.className = 'kt-form-message';
                formItem.appendChild(messageEl);
            }

            messageEl.textContent = message;
            messageEl.style.display = 'block';
            messageEl.style.color = '#dc2626';
        }
    };

    const clearValidationErrors = () => {
        form.querySelectorAll('[aria-invalid="true"]').forEach(input => {
            input.removeAttribute('aria-invalid');
            input.classList.remove('kt-input-invalid');
        });

        form.querySelectorAll('.kt-form-message').forEach(messageEl => {
            messageEl.style.display = 'none';
            messageEl.textContent = '';
        });
    };

    // ============== ENHANCED LOGIN HANDLERS ==============

    /**
     * Handle successful login with enhanced session data
     */
    const handleLoginSuccess = (response) => {
        if (!response.data || !response.data.user) {
            console.error('Invalid login response format');
            ApiHelper.showToast('error', 'Invalid server response');
            return;
        }

        const userData = response.data.user;
        const sessionData = response.data.session || {};
        
        // Reset login attempts on success
        RateLimiter.resetAttempts();

        // Enhanced success message with session info
        let welcomeMessage = `Selamat Datang, ${userData.name}!`;

        // Handle redirect
        const redirectUrl = sessionStorage.getItem('redirect_after_login') || response.data.redirect || '/';
        
        // Add device info if available
        if (sessionData.device_info) {
            const deviceInfo = sessionData.device_info;
            console.log('Login successful on:', {
                device: deviceInfo.name,
                platform: deviceInfo.platform,
                browser: deviceInfo.browser,
                trusted: deviceInfo.is_trusted,
                active_sessions: sessionData.active_sessions_count
            });
            
            // Show trust device option if not trusted
            if (!deviceInfo.is_trusted) {
                if (confirm('Adakah anda ingin mempercayai peranti ini untuk log masuk masa hadapan?')) {
                    trustCurrentDevice();
                }


                setTimeout(() => {
                    window.location.href = redirectUrl;
                }, config.redirectDelay);

            }
        }

        ApiHelper.showToast('success', welcomeMessage);
        clearForm();
        
        setTimeout(() => {
            window.location.href = redirectUrl;
        }, config.redirectDelay);
    };

    /**
     * Trust current device
     */
    const trustCurrentDevice = async () => {
        try {
            const response = await api.post('auth/sessions/trust');
            if (response.status === 'Success') {
                ApiHelper.showToast('success', 'Peranti ini telah dipercayai untuk log masuk masa hadapan.');
            }
        } catch (error) {
            console.error('Trust device error:', error);
            ApiHelper.showToast('warning', 'Tidak dapat mempercayai peranti ini sekarang.');
        }
    };

    /**
     * Handle account inactive (email verification required)
     */
    const handleAccountInactive = async (response) => {
        if (!response.data || !response.data.verification_required) {
            handleLoginError("Akaun tidak aktif. Sila hubungi sokongan.");
            return;
        }

        const userData = response.data;
        
        ApiHelper.showToast('warning', 
            `Sila sahkan alamat email anda sebelum log masuk. Kod pengesahan akan dihantar ke ${userData.email}.`
        );

        clearForm();
        
        // Store verification info for verification page
        localStorage.setItem("verification_method", 'email');
        localStorage.setItem("masked_contact", userData.email || '');
        localStorage.setItem("user_id", userData.user_id);
        localStorage.setItem("user_name", userData.user_name || '');

        // Try to send verification code via API
        try {
            const verificationData = {
                user_id: userData.user_id
            };

            const verificationResponse = await api.post('auth/verification/send', verificationData);

            if (verificationResponse.status === 'Success') {
                ApiHelper.showToast('success', "Kod pengesahan telah dihantar ke email anda!");
            } else {
                ApiHelper.showToast('warning', "Sila gunakan halaman pengesahan untuk mendapatkan kod.");
            }
        } catch (error) {
            console.error('Send verification error:', error);
            ApiHelper.showToast('warning', "Sila cuba hantar semula kod pengesahan di halaman seterusnya.");
        }

        // Redirect to verification page
        setTimeout(() => {
            window.location.href = "/auth/verify";
        }, config.redirectDelay);
    };

    /**
     * Handle login error with enhanced error handling
     */
    const handleLoginError = (message, isBlocked = false) => {
        let errorMessage = message;
        
        if (isBlocked) {
            // Server-side blocking takes precedence
            setFormState(false);
            startServerLockoutTimer();
        } else {
            // Client-side rate limiting as backup
            const attempts = RateLimiter.incrementAttempts();
            
            if (attempts >= config.maxLoginAttempts) {
                const remainingTime = Math.ceil(RateLimiter.getRemainingLockTime() / (60 * 1000));
                errorMessage = `Terlalu banyak percubaan gagal. Akaun dikunci untuk ${remainingTime} minit.`;
                
                setFormState(false);
                startLockoutTimer();
            } else {
                const remainingAttempts = config.maxLoginAttempts - attempts;
                if (remainingAttempts <= 2) {
                    errorMessage += ` (${remainingAttempts} percubaan lagi sebelum akaun dikunci)`;
                }
            }
        }
        
        ApiHelper.showToast('error', errorMessage);
    };

    /**
     * Set form enabled/disabled state
     */
    const setFormState = (enabled) => {
        const inputs = form.querySelectorAll('input, button');
        inputs.forEach(input => {
            input.disabled = !enabled;
        });
        
        if (enabled) {
            submitButton.textContent = 'Log Masuk';
        } else {
            submitButton.textContent = 'Akaun Dikunci';
        }
    };

    /**
     * Start client-side lockout timer
     */
    const startLockoutTimer = () => {
        const updateTimer = () => {
            const remainingTime = RateLimiter.getRemainingLockTime();
            
            if (remainingTime <= 0) {
                setFormState(true);
                ApiHelper.showToast('info', 'Akaun telah dibuka semula. Anda boleh cuba log masuk.');
                return;
            }
            
            const minutes = Math.ceil(remainingTime / (60 * 1000));
            submitButton.textContent = `Dikunci (${minutes} minit)`;
            
            setTimeout(updateTimer, 60000);
        };
        
        updateTimer();
    };

    /**
     * Handle server-side lockout (from AuthService)
     */
    const startServerLockoutTimer = () => {
        // For server-side lockout, show generic message
        submitButton.textContent = 'Akaun Dikunci';
        
        // Re-enable after 15 minutes (server lockout duration)
        setTimeout(() => {
            setFormState(true);
            ApiHelper.showToast('info', 'Tempoh penyekat telah tamat. Anda boleh cuba log masuk.');
        }, 15 * 60 * 1000);
    };

    const clearForm = () => {
        form.querySelector('[name="email"]').value = "";
        form.querySelector('[name="password"]').value = "";
        clearValidationErrors();
    };

    // ============== FORM SUBMISSION ==============

    /**
     * Enhanced form submission with AuthService integration
     */
    const submitForm = async () => {
        try {
            // Check if form is locked
            if (RateLimiter.isLocked()) {
                const remainingTime = Math.ceil(RateLimiter.getRemainingLockTime() / (60 * 1000));
                ApiHelper.showToast('warning', `Akaun dikunci untuk ${remainingTime} minit lagi.`);
                return;
            }

            isValidating = true;
            const isValid = validateForm();
            isValidating = false;

            if (!isValid) {
                ApiHelper.showToast('warning', "Sila betulkan maklumat yang diperlukan");
                return;
            }

            // Show loading state
            const originalText = submitButton.textContent;
            submitButton.disabled = true;
            submitButton.textContent = 'Sedang Log Masuk...';

            try {
                // Get sanitized form data
                const formData = {
                    email: sanitizeInput(form.querySelector('[name="email"]').value),
                    password: form.querySelector('[name="password"]').value.trim(),
                    remember: form.querySelector('[name="remember"]')?.checked || false
                };

                // Validate data one more time before sending
                if (!formData.email || !formData.password) {
                    throw new Error('Email dan kata laluan diperlukan');
                }

                // Make login request to our updated LoginController
                const response = await api.post('auth/login', formData).skipErrorHandling();

                // Handle different response types from updated AuthService
                if (response.status === 'Success') {
                    handleLoginSuccess(response);
                } else if (response.status === 'Client Error') {
                    // Check for specific error types
                    if (response.data && response.data.verification_required) {
                        await handleAccountInactive(response);
                    } else if (response.data && response.data.account_status === 'inactive') {
                        handleLoginError("Akaun anda telah dinyahaktifkan. Sila hubungi sokongan.");
                    } else {
                        // Check if it's a blocked response (429 status)
                        const isBlocked = response.message && response.message.includes('percubaan');
                        handleLoginError(response.message || "Nama pengguna atau kata laluan tidak sah.", isBlocked);
                    }
                } else {
                    handleLoginError(response.message || "Ralat tidak diketahui berlaku.");
                }

            } catch (error) {
                console.error('Login failed:', error);

                let errorMessage = "Maaf, Terdapat Ralat di dalam sistem. Sila cuba sekali lagi.";
                let isBlocked = false;

                if (error.response) {
                    const status = error.response.status;
                    const data = error.response.data;

                    switch (status) {
                        case 401:
                            errorMessage = "Nama pengguna atau kata laluan tidak sah.";
                            break;
                        case 403:
                            if (data.data && data.data.verification_required) {
                                await handleAccountInactive(data);
                                return;
                            } else if (data.data && data.data.account_status === 'inactive') {
                                errorMessage = "Akaun anda telah dinyahaktifkan. Sila hubungi sokongan.";
                            } else {
                                errorMessage = data.message || "Akses ditolak.";
                            }
                            break;
                        case 429:
                            errorMessage = data.message || "Terlalu banyak percubaan. Sila cuba lagi kemudian.";
                            isBlocked = true;
                            break;
                        case 500:
                            errorMessage = "Ralat pelayan. Sila cuba lagi kemudian.";
                            break;
                        default:
                            errorMessage = data.message || errorMessage;
                    }
                } else if (error.request) {
                    errorMessage = "Masalah rangkaian. Sila periksa sambungan internet anda.";
                } else {
                    errorMessage = error.message || errorMessage;
                }

                handleLoginError(errorMessage, isBlocked);

            } finally {
                // Reset button state
                submitButton.disabled = false;
                submitButton.textContent = originalText;
            }

        } catch (error) {
            console.error('Unexpected error during form submission:', error);
            ApiHelper.showToast('error', 'Ralat tidak dijangka berlaku.');
            
            // Reset button state
            submitButton.disabled = false;
            submitButton.textContent = 'Log Masuk';
        }
    };

    // ============== EVENT LISTENERS ==============

    const setupRealtimeValidation = () => {
        const inputs = form.querySelectorAll('input[name]');

        inputs.forEach(input => {
            input.addEventListener('focus', () => {
                const formItem = input.closest('.kt-form-item');
                if (formItem) {
                    const messageEl = formItem.querySelector('.kt-form-message');
                    if (messageEl) {
                        messageEl.style.display = 'none';
                    }
                    input.removeAttribute('aria-invalid');
                    input.classList.remove('kt-input-invalid');
                }
            });

            input.addEventListener('blur', () => {
                if (input.value.trim() && !isValidating) {
                    validateSingleField(input.name, sanitizeInput(input.value));
                }
            });

            input.addEventListener('input', () => {
                if (input.hasAttribute('aria-invalid')) {
                    const formItem = input.closest('.kt-form-item');
                    if (formItem) {
                        const messageEl = formItem.querySelector('.kt-form-message');
                        if (messageEl) {
                            messageEl.style.display = 'none';
                        }
                        input.removeAttribute('aria-invalid');
                        input.classList.remove('kt-input-invalid');
                    }
                }
            });
        });
    };

    const validateSingleField = (fieldName, value) => {
        const rules = config.validation[fieldName];
        if (!rules) return true;

        switch (fieldName) {
            case 'email':
                if (value.length < rules.minLength) {
                    showFieldError(fieldName, rules.messages.minLength);
                    return false;
                } else if (value.length > rules.maxLength) {
                    showFieldError(fieldName, rules.messages.maxLength);
                    return false;
                } else if (!rules.pattern.test(value)) {
                    showFieldError(fieldName, rules.messages.pattern);
                    return false;
                }
                break;
        }

        return true;
    };

    const setupEventListeners = () => {
        submitButton.addEventListener("click", (e) => {
            e.preventDefault();
            submitForm();
        });

        form.addEventListener("keypress", (e) => {
            if (e.key === "Enter") {
                e.preventDefault();
                submitForm();
            }
        });

        setupRealtimeValidation();
    };

    // ============== INITIALIZATION ==============

    const initElements = () => {
        form = document.querySelector("#sign_in_form");
        submitButton = document.querySelector("#sign_in_submit");

        if (!form || !submitButton) {
            console.error('Required form elements not found');
            return false;
        }

        const emailInput = form.querySelector('[name="email"]');
        const passwordInput = form.querySelector('[name="password"]');

        if (!emailInput || !passwordInput) {
            console.error('Required form inputs not found');
            return false;
        }

        return true;
    };

    const checkDependencies = () => {
        const dependencies = [
            { name: 'api', obj: window.api },
            { name: 'ApiHelper', obj: window.ApiHelper }
        ];

        const missing = dependencies.filter(dep => typeof dep.obj === 'undefined');

        if (missing.length > 0) {
            console.error('Missing dependencies:', missing.map(dep => dep.name).join(', '));
            return false;
        }

        return true;
    };

    const checkInitialLockState = () => {
        if (RateLimiter.isLocked()) {
            setFormState(false);
            startLockoutTimer();
            
            const remainingTime = Math.ceil(RateLimiter.getRemainingLockTime() / (60 * 1000));
            ApiHelper.showToast('warning', `Akaun dikunci kerana terlalu banyak percubaan gagal. Sila tunggu ${remainingTime} minit.`);
        }
    };

    return {
        init() {
            if (!checkDependencies()) {
                console.error('LoginHandler initialization failed: missing dependencies');
                return;
            }

            if (!initElements()) {
                console.error('LoginHandler initialization failed: required elements not found');
                return;
            }

            setupEventListeners();
            checkInitialLockState();

            console.log('✅ Enhanced Login handler initialized with AuthService integration');
        },

        // Public methods for external use
        validateForm,
        clearForm,
        clearValidationErrors,
        resetLoginAttempts: () => RateLimiter.resetAttempts(),
        trustCurrentDevice
    };
})();

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    LoginHandler.init();
});