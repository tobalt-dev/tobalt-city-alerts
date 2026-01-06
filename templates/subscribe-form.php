<?php
/**
 * Subscribe form template.
 *
 * Author: Tobalt — https://tobalt.lt
 *
 * @var array $atts Shortcode attributes
 */

defined( 'ABSPATH' ) || exit;

$categories = get_terms( [
    'taxonomy'   => 'tobalt_alert_category',
    'hide_empty' => false,
] );

$has_categories = ! is_wp_error( $categories ) && ! empty( $categories );
?>
<div class="tobalt-subscribe-form" x-data="tobaltSubscribe()">
    <h3 class="tobalt-subscribe-title"><?php echo esc_html( $atts['title'] ); ?></h3>

    <!-- Messages -->
    <div x-show="message" class="tobalt-form-message" :class="messageType" x-text="message" x-cloak></div>

    <!-- Form -->
    <form @submit.prevent="submit()" x-show="!submitted" class="tobalt-form">
        <div class="tobalt-form-group">
            <label for="tobalt-sub-email"><?php esc_html_e( 'Email Address', 'tobalt-city-alerts' ); ?> *</label>
            <input
                type="email"
                id="tobalt-sub-email"
                x-model="email"
                required
                placeholder="<?php esc_attr_e( 'your@email.com', 'tobalt-city-alerts' ); ?>"
            >
        </div>

        <?php if ( $has_categories ) : ?>
        <div class="tobalt-form-group">
            <label><?php esc_html_e( 'Categories (optional)', 'tobalt-city-alerts' ); ?></label>
            <p class="tobalt-form-hint"><?php esc_html_e( 'Leave empty to receive all categories', 'tobalt-city-alerts' ); ?></p>
            <div class="tobalt-checkbox-group">
                <?php foreach ( $categories as $cat ) : ?>
                <label class="tobalt-checkbox">
                    <input type="checkbox" :value="<?php echo esc_attr( $cat->term_id ); ?>" x-model="selectedCategories">
                    <?php echo esc_html( $cat->name ); ?>
                </label>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <button type="submit" class="tobalt-submit-btn" :disabled="submitting">
            <span x-show="!submitting"><?php esc_html_e( 'Subscribe', 'tobalt-city-alerts' ); ?></span>
            <span x-show="submitting"><?php esc_html_e( 'Subscribing...', 'tobalt-city-alerts' ); ?></span>
        </button>
    </form>

    <!-- Success State -->
    <div x-show="submitted" class="tobalt-success-state" x-cloak>
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="48" height="48">
            <path d="M20 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/>
        </svg>
        <p><?php esc_html_e( 'Please check your email to verify your subscription.', 'tobalt-city-alerts' ); ?></p>
    </div>
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('tobaltSubscribe', () => ({
        email: '',
        selectedCategories: [],
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
                    recaptchaToken = await grecaptcha.execute(tobaltCityAlerts.recaptcha.siteKey, {action: 'subscribe'});
                }

                const res = await fetch(tobaltCityAlerts.apiUrl + 'subscribe', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        email: this.email,
                        categories: this.selectedCategories.map(Number),
                        recaptcha_token: recaptchaToken
                    })
                });

                const data = await res.json();

                if (data.success) {
                    this.submitted = true;
                    this.message = data.message;
                    this.messageType = 'success';
                } else {
                    this.message = data.message || '<?php echo esc_js( __( 'An error occurred.', 'tobalt-city-alerts' ) ); ?>';
                    this.messageType = 'error';
                }
            } catch (e) {
                this.message = '<?php echo esc_js( __( 'Network error. Please try again.', 'tobalt-city-alerts' ) ); ?>';
                this.messageType = 'error';
            }

            this.submitting = false;
        }
    }));
});
</script>

<style>
.tobalt-subscribe-form {
    max-width: 500px;
    margin: 0 auto;
    padding: 25px;
    background: #fff;
    border: 1px solid #e5e5e5;
    border-radius: 8px;
}

.tobalt-subscribe-title {
    margin: 0 0 20px;
    font-size: 18px;
    text-align: center;
}

.tobalt-subscribe-form .tobalt-form-group {
    margin-bottom: 20px;
}

.tobalt-subscribe-form .tobalt-form-group > label {
    display: block;
    margin-bottom: 5px;
    font-weight: 600;
    font-size: 14px;
}

.tobalt-form-hint {
    margin: 0 0 8px;
    font-size: 12px;
    color: #646970;
}

.tobalt-subscribe-form input[type="email"] {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid #8c8f94;
    border-radius: 4px;
    font-size: 14px;
    box-sizing: border-box;
}

.tobalt-subscribe-form input[type="email"]:focus {
    border-color: var(--tobalt-icon-color, #0073aa);
    outline: none;
    box-shadow: 0 0 0 1px var(--tobalt-icon-color, #0073aa);
}

.tobalt-checkbox-group {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
}

.tobalt-checkbox {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    background: #f0f0f1;
    border-radius: 4px;
    font-size: 13px;
    cursor: pointer;
    transition: background 0.2s;
}

.tobalt-checkbox:hover {
    background: #e5e5e5;
}

.tobalt-checkbox input {
    margin: 0;
}

.tobalt-subscribe-form .tobalt-submit-btn {
    width: 100%;
    padding: 12px;
    background: var(--tobalt-icon-color, #0073aa);
    color: #fff;
    border: none;
    border-radius: 4px;
    font-size: 15px;
    font-weight: 600;
    cursor: pointer;
    transition: filter 0.2s;
}

.tobalt-subscribe-form .tobalt-submit-btn:hover {
    filter: brightness(1.1);
}

.tobalt-subscribe-form .tobalt-submit-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.tobalt-subscribe-form .tobalt-form-message {
    padding: 10px 15px;
    border-radius: 4px;
    margin-bottom: 15px;
    font-size: 14px;
}

.tobalt-subscribe-form .tobalt-form-message.success {
    background: #d1e7dd;
    color: #0a3622;
}

.tobalt-subscribe-form .tobalt-form-message.error {
    background: #f8d7da;
    color: #58151c;
}

.tobalt-subscribe-form .tobalt-success-state {
    text-align: center;
    padding: 20px 0;
}

.tobalt-subscribe-form .tobalt-success-state svg {
    color: var(--tobalt-icon-color, #0073aa);
    margin-bottom: 15px;
}

.tobalt-subscribe-form .tobalt-success-state p {
    margin: 0;
    font-size: 15px;
}
</style>
