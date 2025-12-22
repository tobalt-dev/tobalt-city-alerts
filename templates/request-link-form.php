<?php
/**
 * Request magic link form template.
 *
 * Author: Tobalt — https://tobalt.lt
 *
 * @var array $atts Shortcode attributes
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="tobalt-request-link-form" x-data="tobaltRequestLink()">
    <!-- Header with icon -->
    <div class="tobalt-form-header">
        <div class="tobalt-form-icon">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="32" height="32">
                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
            </svg>
        </div>
        <h3 class="tobalt-request-title">Pranešimų pateikimo sistema</h3>
        <p class="tobalt-form-subtitle">Skirta įgaliotiems darbuotojams</p>
    </div>

    <!-- Instructions -->
    <div class="tobalt-instructions">
        <div class="tobalt-instruction-item">
            <span class="tobalt-step">1</span>
            <span>Įveskite savo patvirtintą el. pašto adresą</span>
        </div>
        <div class="tobalt-instruction-item">
            <span class="tobalt-step">2</span>
            <span>Gaukite unikalią nuorodą į savo el. paštą</span>
        </div>
        <div class="tobalt-instruction-item">
            <span class="tobalt-step">3</span>
            <span>Pateikite pranešimą gyventojams</span>
        </div>
    </div>

    <!-- Messages -->
    <div x-show="message" class="tobalt-form-message" :class="messageType" x-text="message" x-cloak></div>

    <!-- Form -->
    <form @submit.prevent="submit()" x-show="!submitted" class="tobalt-form">
        <div class="tobalt-form-group">
            <label for="tobalt-email">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="16" height="16">
                    <path d="M20 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/>
                </svg>
                El. pašto adresas
            </label>
            <input
                type="email"
                id="tobalt-email"
                x-model="email"
                required
                placeholder="jusu.pastas@organizacija.lt"
            >
            <span class="tobalt-field-hint">Naudokite tik patvirtintą organizacijos el. paštą</span>
        </div>
        <button type="submit" class="tobalt-submit-btn" :disabled="submitting">
            <span x-show="!submitting">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="18" height="18">
                    <path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/>
                </svg>
                Siųsti prisijungimo nuorodą
            </span>
            <span x-show="submitting">Siunčiama...</span>
        </button>
    </form>

    <!-- Success State -->
    <div x-show="submitted" class="tobalt-success-state" x-cloak>
        <div class="tobalt-success-icon">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="48" height="48">
                <path d="M20 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/>
            </svg>
        </div>
        <h4>Nuoroda išsiųsta!</h4>
        <p>Patikrinkite savo el. paštą ir paspauskite gautą nuorodą, kad galėtumėte pateikti pranešimą.</p>
        <div class="tobalt-note">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="16" height="16">
                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/>
            </svg>
            <span>Nuoroda galioja 1 valandą</span>
        </div>
        <button @click="reset()" class="tobalt-link-btn">Prašyti naujos nuorodos</button>
    </div>

    <!-- Footer note -->
    <div class="tobalt-form-footer" x-show="!submitted">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="14" height="14">
            <path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2z"/>
        </svg>
        <span>Saugus prisijungimas be slaptažodžio</span>
    </div>
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('tobaltRequestLink', () => ({
        email: '',
        submitting: false,
        submitted: false,
        message: '',
        messageType: '',

        async submit() {
            this.submitting = true;
            this.message = '';

            try {
                // Get reCAPTCHA token if enabled
                let recaptchaToken = '';
                if (tobaltCityAlerts.recaptcha?.enabled && typeof grecaptcha !== 'undefined') {
                    recaptchaToken = await grecaptcha.execute(tobaltCityAlerts.recaptcha.siteKey, {action: 'request_link'});
                }

                const res = await fetch(tobaltCityAlerts.apiUrl + 'request-link', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        email: this.email,
                        recaptcha_token: recaptchaToken
                    })
                });

                const data = await res.json();

                if (data.success) {
                    this.submitted = true;
                    this.message = data.message;
                    this.messageType = 'success';
                } else {
                    this.message = data.message || 'Įvyko klaida. Bandykite dar kartą.';
                    this.messageType = 'error';
                }
            } catch (e) {
                this.message = 'Tinklo klaida. Bandykite dar kartą.';
                this.messageType = 'error';
            }

            this.submitting = false;
        },

        reset() {
            this.email = '';
            this.submitted = false;
            this.message = '';
            this.messageType = '';
        }
    }));
});
</script>

<style>
.tobalt-request-link-form {
    max-width: 360px;
    margin: 0 auto;
    padding: 0;
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    overflow: hidden;
}

.tobalt-form-header {
    background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
    color: #fff !important;
    padding: 24px 20px;
    text-align: center;
}

.tobalt-form-icon {
    width: 50px;
    height: 50px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 12px;
}

.tobalt-form-icon svg {
    color: #fff !important;
}

.tobalt-request-title {
    margin: 0 0 4px;
    font-size: 18px;
    font-weight: 600;
    color: #fff !important;
}

.tobalt-form-subtitle {
    margin: 0;
    font-size: 13px;
    opacity: 0.9;
    color: #fff !important;
}

.tobalt-instructions {
    background: #f8fafc;
    padding: 16px 20px;
    border-bottom: 1px solid #e2e8f0;
}

.tobalt-instruction-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 8px 0;
    font-size: 14px;
    color: #475569;
}

.tobalt-step {
    width: 24px;
    height: 24px;
    background: #2563eb;
    color: #fff;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    font-weight: 600;
    flex-shrink: 0;
}

.tobalt-request-link-form .tobalt-form {
    padding: 20px;
}

.tobalt-form-group {
    margin-bottom: 20px;
}

.tobalt-form-group label {
    display: flex;
    align-items: center;
    gap: 6px;
    margin-bottom: 8px;
    font-weight: 600;
    font-size: 14px;
    color: #1e293b;
}

.tobalt-form-group label svg {
    color: #64748b;
}

.tobalt-form-group input {
    width: 100%;
    padding: 12px 14px;
    border: 2px solid #e2e8f0;
    border-radius: 8px;
    font-size: 15px;
    box-sizing: border-box;
    transition: border-color 0.2s, box-shadow 0.2s;
}

.tobalt-form-group input:focus {
    border-color: #2563eb;
    outline: none;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.tobalt-field-hint {
    display: block;
    margin-top: 6px;
    font-size: 12px;
    color: #64748b;
}

.tobalt-request-link-form .tobalt-submit-btn {
    width: 100%;
    padding: 14px;
    background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
    color: #fff;
    border: none;
    border-radius: 8px;
    font-size: 15px;
    font-weight: 600;
    cursor: pointer;
    transition: transform 0.2s, box-shadow 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.tobalt-request-link-form .tobalt-submit-btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
}

.tobalt-request-link-form .tobalt-submit-btn:disabled {
    opacity: 0.7;
    cursor: not-allowed;
    transform: none;
    box-shadow: none;
}

.tobalt-form-message {
    margin: 0 20px 0;
    padding: 10px 12px;
    border-radius: 8px;
    font-size: 13px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.tobalt-form-message.success {
    background: #dcfce7;
    color: #166534;
    border: 1px solid #bbf7d0;
}

.tobalt-form-message.error {
    background: #fef2f2;
    color: #991b1b;
    border: 1px solid #fecaca;
}

.tobalt-success-state {
    text-align: center;
    padding: 30px 20px;
}

.tobalt-success-icon {
    width: 80px;
    height: 80px;
    background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 20px;
}

.tobalt-success-icon svg {
    color: #fff;
}

.tobalt-success-state h4 {
    margin: 0 0 10px;
    font-size: 20px;
    color: #166534;
}

.tobalt-success-state p {
    margin: 0 0 20px;
    font-size: 14px;
    color: #475569;
    line-height: 1.5;
}

.tobalt-note {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 16px;
    background: #fef3c7;
    color: #92400e;
    border-radius: 20px;
    font-size: 13px;
    margin-bottom: 20px;
}

.tobalt-link-btn {
    background: none;
    border: none;
    color: #2563eb;
    cursor: pointer;
    font-size: 14px;
    font-weight: 500;
    text-decoration: none;
}

.tobalt-link-btn:hover {
    text-decoration: underline;
}

.tobalt-form-footer {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 12px 20px;
    background: #f8fafc;
    border-top: 1px solid #e2e8f0;
    font-size: 11px;
    color: #64748b;
}

.tobalt-form-footer svg {
    color: #22c55e;
}
</style>
