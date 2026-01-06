<?php
/**
 * Alert submission form template (magic link landing page).
 *
 * Author: Tobalt — https://tobalt.lt
 *
 * @var string $email The approved email from token
 * @var string $token The magic link token
 */

defined( 'ABSPATH' ) || exit;

$settings = get_option( 'tobalt_city_alerts_settings', [] );
$labels   = $settings['custom_labels'] ?? [];
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php esc_html_e( 'Submit Alert', 'tobalt-city-alerts' ); ?> - <?php bloginfo( 'name' ); ?></title>
    <?php wp_head(); ?>
    <style>
        /* WCAG 2.1 AA compliant - min contrast 4.5:1 for text, 3:1 for UI */
        :root {
            --color-primary: #0063a0;      /* WCAG AA on white: 5.2:1 */
            --color-primary-dark: #004d7a;
            --color-text: #1a1a1a;         /* WCAG AAA: 16.1:1 */
            --color-text-muted: #555;      /* WCAG AA: 7.5:1 */
            --color-border: #767676;       /* WCAG AA: 4.5:1 */
            --color-border-light: #ccc;
            --color-bg: #f5f5f5;
            --color-success: #1a7f37;      /* WCAG AA: 4.6:1 */
            --color-error: #cf222e;        /* WCAG AA: 5.1:1 */
            --focus-ring: 0 0 0 3px rgba(0,99,160,0.4);
        }

        *, *::before, *::after {
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
            background: var(--color-bg);
            margin: 0;
            padding: 12px;
            font-size: 16px;
            line-height: 1.5;
            color: var(--color-text);
        }

        /* Ensure text visibility on dark backgrounds */
        body.tobalt-dark-bg,
        .tobalt-dark-bg {
            background: #1a1a1a;
        }
        .tobalt-dark-bg .tobalt-submit-container {
            color: var(--color-text);
        }

        .tobalt-submit-container {
            max-width: 540px;
            margin: 16px auto;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.12);
            overflow: hidden;
            color: var(--color-text);
        }
        .tobalt-submit-container .tobalt-tab-content,
        .tobalt-submit-container .tobalt-tabs {
            color: var(--color-text);
        }
        .tobalt-submit-container input,
        .tobalt-submit-container select,
        .tobalt-submit-container textarea,
        .tobalt-submit-container label,
        .tobalt-submit-container p,
        .tobalt-submit-container h4 {
            color: var(--color-text);
        }
        .tobalt-submit-container input,
        .tobalt-submit-container select,
        .tobalt-submit-container textarea {
            background: #fff;
        }

        /* Header - compact */
        .tobalt-submit-header {
            text-align: center;
            padding: 16px 20px;
            background: var(--color-primary);
            color: #fff;
        }
        .tobalt-submit-header h1,
        .tobalt-submit-header p {
            color: #fff;
        }
        .tobalt-submit-header h1 {
            margin: 0 0 2px;
            font-size: 1.125rem;
            font-weight: 600;
        }
        .tobalt-submit-header p {
            margin: 0;
            font-size: 0.875rem;
            opacity: 0.95;
        }

        /* Tabs - WCAG focus visible */
        .tobalt-tabs {
            display: flex;
            border-bottom: 1px solid var(--color-border-light);
            background: #fafafa;
        }
        .tobalt-tab {
            flex: 1;
            padding: 10px 12px;
            text-align: center;
            cursor: pointer;
            font-weight: 500;
            font-size: 0.875rem;
            color: var(--color-text-muted);
            border: none;
            border-bottom: 3px solid transparent;
            background: transparent;
            transition: background 0.15s, border-color 0.15s;
        }
        .tobalt-tab:hover {
            background: #eee;
        }
        .tobalt-tab:focus {
            outline: none;
            box-shadow: inset var(--focus-ring);
        }
        .tobalt-tab:focus-visible {
            box-shadow: inset var(--focus-ring);
        }
        .tobalt-tab.active {
            color: var(--color-primary);
            border-bottom-color: var(--color-primary);
            background: #fff;
        }

        .tobalt-tab-content {
            padding: 16px 20px;
        }

        /* Form - compact spacing */
        .tobalt-form-group {
            margin-bottom: 14px;
        }
        .tobalt-form-group label {
            display: block;
            margin-bottom: 4px;
            font-weight: 600;
            font-size: 0.875rem;
            color: var(--color-text);
        }
        .tobalt-form-group input,
        .tobalt-form-group select,
        .tobalt-form-group textarea {
            width: 100%;
            padding: 8px 10px;
            border: 1px solid var(--color-border);
            border-radius: 4px;
            font-size: 1rem;
            color: var(--color-text);
            background: #fff;
        }
        .tobalt-form-group input:focus,
        .tobalt-form-group select:focus,
        .tobalt-form-group textarea:focus {
            outline: none;
            border-color: var(--color-primary);
            box-shadow: var(--focus-ring);
        }
        .tobalt-form-group textarea {
            min-height: 80px;
            resize: vertical;
        }
        .tobalt-form-row {
            display: flex;
            gap: 12px;
        }
        .tobalt-form-row .tobalt-form-group {
            flex: 1;
            min-width: 0;
        }

        /* Submit button - WCAG compliant */
        .tobalt-submit-btn {
            width: 100%;
            padding: 10px 16px;
            background: var(--color-primary);
            color: #fff;
            border: none;
            border-radius: 4px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.15s;
        }
        .tobalt-submit-btn:hover {
            background: var(--color-primary-dark);
        }
        .tobalt-submit-btn:focus {
            outline: none;
            box-shadow: var(--focus-ring);
        }
        .tobalt-submit-btn:focus-visible {
            box-shadow: var(--focus-ring);
        }
        .tobalt-submit-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        /* Alert messages - WCAG compliant colors */
        .tobalt-alert-message {
            padding: 10px 14px;
            border-radius: 4px;
            margin-bottom: 14px;
            font-size: 0.875rem;
            border-left: 4px solid;
        }
        .tobalt-alert-message.success {
            background: #dff6dd;
            color: #1a5c2c;
            border-color: var(--color-success);
        }
        .tobalt-alert-message.error {
            background: #fce8e8;
            color: #9a1c1c;
            border-color: var(--color-error);
        }

        /* My Alerts List - compact */
        .tobalt-my-alerts-list {
            margin: 0;
            padding: 0;
            list-style: none;
        }
        .tobalt-my-alert-item {
            border: 1px solid var(--color-border-light);
            border-radius: 6px;
            padding: 12px;
            margin-bottom: 10px;
            background: #fff;
        }
        .tobalt-my-alert-item.solved {
            opacity: 0.7;
            background: #f9f9f9;
        }
        .tobalt-my-alert-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 8px;
            margin-bottom: 6px;
        }
        .tobalt-my-alert-title {
            font-weight: 600;
            color: var(--color-text);
            margin: 0;
            font-size: 0.9375rem;
            line-height: 1.3;
        }
        .tobalt-my-alert-status {
            font-size: 0.6875rem;
            padding: 2px 6px;
            border-radius: 3px;
            text-transform: uppercase;
            font-weight: 700;
            white-space: nowrap;
            flex-shrink: 0;
        }
        .tobalt-status-publish {
            background: #dff6dd;
            color: #1a5c2c;
        }
        .tobalt-status-pending {
            background: #fff3cd;
            color: #664d03;
        }
        .tobalt-status-draft {
            background: #e9e9e9;
            color: #444;
        }
        .tobalt-status-solved {
            background: #d4e4fc;
            color: #0a4b94;
        }
        .tobalt-my-alert-meta {
            font-size: 0.8125rem;
            color: var(--color-text-muted);
            margin-bottom: 10px;
            line-height: 1.4;
        }
        .tobalt-my-alert-meta span {
            display: inline-block;
            margin-right: 12px;
        }
        .tobalt-my-alert-actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        /* Small buttons - WCAG touch target 44x44 on mobile */
        .tobalt-btn-small {
            padding: 6px 12px;
            min-height: 36px;
            font-size: 0.8125rem;
            border-radius: 4px;
            cursor: pointer;
            border: 1px solid var(--color-border);
            background: #f6f7f7;
            color: var(--color-text);
            transition: background 0.15s;
        }
        .tobalt-btn-small:hover {
            background: #e5e5e5;
        }
        .tobalt-btn-small:focus {
            outline: none;
            box-shadow: var(--focus-ring);
        }
        .tobalt-btn-small:focus-visible {
            box-shadow: var(--focus-ring);
        }
        .tobalt-btn-small.primary {
            background: var(--color-primary);
            border-color: var(--color-primary);
            color: #fff;
        }
        .tobalt-btn-small.primary:hover {
            background: var(--color-primary-dark);
        }
        .tobalt-btn-small.success {
            background: var(--color-success);
            border-color: var(--color-success);
            color: #fff;
        }
        .tobalt-btn-small.success:hover {
            background: #15662c;
        }
        .tobalt-btn-small:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        /* Modal - WCAG focus trap handled by Alpine */
        .tobalt-modal-backdrop {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.6);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            padding: 16px;
        }
        .tobalt-modal {
            background: #fff;
            border-radius: 8px;
            width: 100%;
            max-width: 360px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.25);
        }
        .tobalt-modal-header {
            padding: 12px 16px;
            border-bottom: 1px solid var(--color-border-light);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .tobalt-modal-header h3 {
            margin: 0;
            font-size: 1rem;
            color: var(--color-text);
        }
        .tobalt-modal-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            line-height: 1;
            cursor: pointer;
            color: var(--color-text-muted);
            padding: 4px 8px;
            margin: -4px -8px;
            border-radius: 4px;
        }
        .tobalt-modal-close:hover {
            background: #eee;
        }
        .tobalt-modal-close:focus {
            outline: none;
            box-shadow: var(--focus-ring);
        }
        .tobalt-modal-body {
            padding: 16px;
        }
        .tobalt-modal-footer {
            padding: 12px 16px;
            border-top: 1px solid var(--color-border-light);
            display: flex;
            justify-content: flex-end;
            gap: 8px;
        }

        .tobalt-empty-state {
            text-align: center;
            padding: 32px 16px;
            color: var(--color-text-muted);
        }
        .tobalt-empty-state svg {
            opacity: 0.4;
            margin-bottom: 12px;
        }
        .tobalt-empty-state p {
            margin: 0;
            font-size: 0.9375rem;
        }

        .tobalt-success-state {
            text-align: center;
            padding: 24px 16px;
        }
        .tobalt-success-state svg {
            margin-bottom: 12px;
        }
        .tobalt-success-state p {
            font-size: 1rem;
            color: var(--color-text);
            margin: 0 0 12px;
        }

        .tobalt-loading {
            text-align: center;
            padding: 24px;
            color: var(--color-text-muted);
        }

        /* Screen reader only */
        .sr-only {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0,0,0,0);
            white-space: nowrap;
            border: 0;
        }

        /* Mobile responsive - 600px breakpoint */
        @media (max-width: 600px) {
            body {
                padding: 8px;
            }
            .tobalt-submit-container {
                margin: 8px auto;
                border-radius: 6px;
            }
            .tobalt-submit-header {
                padding: 14px 16px;
            }
            .tobalt-submit-header h1 {
                font-size: 1.0625rem;
            }
            .tobalt-tab {
                padding: 10px 8px;
                font-size: 0.8125rem;
            }
            .tobalt-tab-content {
                padding: 14px 16px;
            }
            .tobalt-form-row {
                flex-direction: column;
                gap: 0;
            }
            .tobalt-form-group {
                margin-bottom: 12px;
            }
            .tobalt-form-group input,
            .tobalt-form-group select,
            .tobalt-form-group textarea {
                padding: 10px 12px;
                font-size: 16px; /* Prevents iOS zoom */
            }
            .tobalt-btn-small {
                min-height: 44px; /* WCAG touch target */
                padding: 10px 14px;
            }
            .tobalt-submit-btn {
                min-height: 48px;
                padding: 12px 16px;
            }
            .tobalt-my-alert-header {
                flex-direction: column;
                align-items: flex-start;
            }
            .tobalt-my-alert-meta span {
                display: block;
                margin-right: 0;
                margin-bottom: 2px;
            }
            .tobalt-my-alert-actions {
                margin-top: 8px;
            }
            .tobalt-modal {
                max-width: none;
                margin: 0;
                border-radius: 8px;
            }
        }

        /* Reduced motion preference */
        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after {
                transition-duration: 0.01ms !important;
            }
        }

        /* High contrast mode support */
        @media (forced-colors: active) {
            .tobalt-tab.active {
                border-bottom: 3px solid CanvasText;
            }
            .tobalt-submit-btn,
            .tobalt-btn-small.primary,
            .tobalt-btn-small.success {
                border: 2px solid ButtonText;
            }
        }
    </style>
</head>
<body>
    <div class="tobalt-submit-container" x-data="tobaltSubmitForm()">
        <div class="tobalt-submit-header">
            <h1><?php esc_html_e( 'Pranešimų valdymas', 'tobalt-city-alerts' ); ?></h1>
            <p><?php echo esc_html( $email ); ?></p>
        </div>

        <!-- Tabs - WCAG accessible -->
        <div class="tobalt-tabs" role="tablist" aria-label="<?php esc_attr_e( 'Pranešimų valdymas', 'tobalt-city-alerts' ); ?>">
            <button type="button" class="tobalt-tab" role="tab"
                    :class="{ 'active': activeTab === 'new' }"
                    :aria-selected="activeTab === 'new'"
                    aria-controls="tab-new"
                    id="tabBtn-new"
                    @click="activeTab = 'new'"
                    @keydown.right="$refs.tabMy.focus(); activeTab = 'my'; loadMyAlerts()"
                    x-ref="tabNew">
                <?php esc_html_e( 'Naujas pranešimas', 'tobalt-city-alerts' ); ?>
            </button>
            <button type="button" class="tobalt-tab" role="tab"
                    :class="{ 'active': activeTab === 'my' }"
                    :aria-selected="activeTab === 'my'"
                    aria-controls="tab-my"
                    id="tabBtn-my"
                    @click="activeTab = 'my'; loadMyAlerts()"
                    @keydown.left="$refs.tabNew.focus(); activeTab = 'new'"
                    x-ref="tabMy">
                <?php esc_html_e( 'Mano pranešimai', 'tobalt-city-alerts' ); ?>
                <span x-show="myAlerts.length > 0" x-text="'(' + myAlerts.filter(a => !a.solved).length + ')'" aria-hidden="true"></span>
                <span class="sr-only" x-show="myAlerts.length > 0" x-text="myAlerts.filter(a => !a.solved).length + ' aktyvūs'"></span>
            </button>
        </div>

        <!-- New Alert Tab -->
        <div class="tobalt-tab-content" role="tabpanel" id="tab-new" aria-labelledby="tabBtn-new" x-show="activeTab === 'new'">
            <!-- Success/Error Messages -->
            <div x-show="message" class="tobalt-alert-message" :class="messageType" x-text="message"></div>

            <!-- Submission Form -->
            <form @submit.prevent="submit()" x-show="!submitted">
                <input type="hidden" name="token" value="<?php echo esc_attr( $token ); ?>">

                <div class="tobalt-form-group">
                    <label for="title"><?php esc_html_e( 'Pavadinimas', 'tobalt-city-alerts' ); ?> *</label>
                    <input type="text" id="title" x-model="form.title" required maxlength="200">
                </div>

                <div class="tobalt-form-group">
                    <label for="description"><?php esc_html_e( 'Aprašymas', 'tobalt-city-alerts' ); ?> *</label>
                    <textarea id="description" x-model="form.description" required maxlength="2000"></textarea>
                </div>

                <div class="tobalt-form-row">
                    <div class="tobalt-form-group">
                        <label for="date"><?php esc_html_e( 'Pradžios data', 'tobalt-city-alerts' ); ?> *</label>
                        <input type="date" id="date" x-model="form.date" required>
                    </div>
                    <div class="tobalt-form-group">
                        <label for="time"><?php esc_html_e( 'Laikas (neprivaloma)', 'tobalt-city-alerts' ); ?></label>
                        <input type="time" id="time" x-model="form.time">
                    </div>
                </div>

                <div class="tobalt-form-row">
                    <div class="tobalt-form-group">
                        <label for="end_date"><?php esc_html_e( 'Pabaigos data (planuojama)', 'tobalt-city-alerts' ); ?> *</label>
                        <input type="date" id="end_date" x-model="form.end_date" required>
                    </div>
                    <div class="tobalt-form-group">
                        <label for="end_time"><?php esc_html_e( 'Laikas (neprivaloma)', 'tobalt-city-alerts' ); ?></label>
                        <input type="time" id="end_time" x-model="form.end_time">
                    </div>
                </div>

                <div class="tobalt-form-row">
                    <div class="tobalt-form-group">
                        <label for="category"><?php esc_html_e( 'Kategorija', 'tobalt-city-alerts' ); ?></label>
                        <select id="category" x-model="form.category">
                            <option value=""><?php esc_html_e( '— Pasirinkite —', 'tobalt-city-alerts' ); ?></option>
                            <template x-for="cat in categories" :key="cat.id">
                                <option :value="cat.id" x-text="cat.name"></option>
                            </template>
                        </select>
                    </div>
                    <div class="tobalt-form-group">
                        <label for="severity"><?php esc_html_e( 'Svarba', 'tobalt-city-alerts' ); ?></label>
                        <select id="severity" x-model="form.severity">
                            <option value=""><?php esc_html_e( '— Nepasirinkta —', 'tobalt-city-alerts' ); ?></option>
                            <option value="low"><?php esc_html_e( 'Žema', 'tobalt-city-alerts' ); ?></option>
                            <option value="medium"><?php esc_html_e( 'Vidutinė', 'tobalt-city-alerts' ); ?></option>
                            <option value="high"><?php esc_html_e( 'Aukšta', 'tobalt-city-alerts' ); ?></option>
                            <option value="critical"><?php esc_html_e( 'Kritinė', 'tobalt-city-alerts' ); ?></option>
                        </select>
                    </div>
                </div>

                <div class="tobalt-form-group">
                    <label for="location"><?php esc_html_e( 'Vieta (neprivaloma)', 'tobalt-city-alerts' ); ?></label>
                    <input type="text" id="location" x-model="form.location" placeholder="<?php esc_attr_e( 'pvz., Pagrindinė g. 15', 'tobalt-city-alerts' ); ?>">
                </div>

                <button type="submit" class="tobalt-submit-btn" :disabled="submitting">
                    <span x-show="!submitting"><?php echo esc_html( $labels['submit_button'] ?: __( 'Pateikti pranešimą', 'tobalt-city-alerts' ) ); ?></span>
                    <span x-show="submitting"><?php esc_html_e( 'Siunčiama...', 'tobalt-city-alerts' ); ?></span>
                </button>
            </form>

            <!-- Success State -->
            <div x-show="submitted" class="tobalt-success-state">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="#4caf50" width="64" height="64">
                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                </svg>
                <p><?php esc_html_e( 'Ačiū! Jūsų pranešimas sėkmingai pateiktas.', 'tobalt-city-alerts' ); ?></p>
                <button @click="resetForm()" class="tobalt-btn-small primary"><?php esc_html_e( 'Pateikti dar vieną', 'tobalt-city-alerts' ); ?></button>
            </div>
        </div>

        <!-- My Alerts Tab -->
        <div class="tobalt-tab-content" role="tabpanel" id="tab-my" aria-labelledby="tabBtn-my" x-show="activeTab === 'my'">
            <div x-show="loadingMyAlerts" class="tobalt-loading" role="status" aria-live="polite">
                <?php esc_html_e( 'Įkeliama...', 'tobalt-city-alerts' ); ?>
            </div>

            <div x-show="!loadingMyAlerts && myAlerts.length === 0" class="tobalt-empty-state">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="48" height="48">
                    <path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm0 14H6l-2 2V4h16v12z"/>
                </svg>
                <p><?php esc_html_e( 'Jūs dar nepateikėte jokių pranešimų.', 'tobalt-city-alerts' ); ?></p>
            </div>

            <ul x-show="!loadingMyAlerts && myAlerts.length > 0" class="tobalt-my-alerts-list">
                <template x-for="alert in myAlerts" :key="alert.id">
                    <li class="tobalt-my-alert-item" :class="{ 'solved': alert.solved }">
                        <div class="tobalt-my-alert-header">
                            <h4 class="tobalt-my-alert-title" x-text="alert.title"></h4>
                            <span class="tobalt-my-alert-status"
                                  :class="alert.solved ? 'tobalt-status-solved' : 'tobalt-status-' + alert.status"
                                  x-text="alert.solved ? '<?php esc_attr_e( 'Išspręsta', 'tobalt-city-alerts' ); ?>' : getStatusLabel(alert.status)">
                            </span>
                        </div>
                        <div class="tobalt-my-alert-meta">
                            <span x-show="alert.date">
                                <strong><?php esc_html_e( 'Pradžia:', 'tobalt-city-alerts' ); ?></strong>
                                <span x-text="alert.date + (alert.time ? ' ' + alert.time : '')"></span>
                            </span>
                            <span x-show="alert.end_date">
                                <strong><?php esc_html_e( 'Pabaiga:', 'tobalt-city-alerts' ); ?></strong>
                                <span x-text="alert.end_date + (alert.end_time ? ' ' + alert.end_time : '')"></span>
                            </span>
                        </div>
                        <div class="tobalt-my-alert-actions" x-show="!alert.solved">
                            <button class="tobalt-btn-small" @click="openEditModal(alert)" :disabled="updating">
                                <?php esc_html_e( 'Keisti datą', 'tobalt-city-alerts' ); ?>
                            </button>
                            <button class="tobalt-btn-small success" @click="markSolved(alert)" :disabled="updating">
                                <?php esc_html_e( 'Pažymėti kaip išspręstą', 'tobalt-city-alerts' ); ?>
                            </button>
                        </div>
                        <div x-show="alert.solved" class="tobalt-my-alert-meta" style="margin-bottom:0;margin-top:5px;">
                            <span x-show="alert.solved_at">
                                <strong><?php esc_html_e( 'Išspręsta:', 'tobalt-city-alerts' ); ?></strong>
                                <span x-text="alert.solved_at"></span>
                            </span>
                        </div>
                    </li>
                </template>
            </ul>
        </div>

        <!-- Edit Modal -->
        <div x-show="editModal" class="tobalt-modal-backdrop" @click.self="editModal = false" @keydown.escape.window="editModal = false">
            <div class="tobalt-modal" role="dialog" aria-modal="true" aria-labelledby="modal-title">
                <div class="tobalt-modal-header">
                    <h3 id="modal-title"><?php esc_html_e( 'Keisti pabaigos datą', 'tobalt-city-alerts' ); ?></h3>
                    <button type="button" class="tobalt-modal-close" @click="editModal = false" aria-label="<?php esc_attr_e( 'Uždaryti', 'tobalt-city-alerts' ); ?>">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="tobalt-modal-body">
                    <div class="tobalt-form-group">
                        <label><?php esc_html_e( 'Pabaigos data (planuojama)', 'tobalt-city-alerts' ); ?></label>
                        <input type="date" x-model="editForm.end_date">
                    </div>
                    <div class="tobalt-form-group">
                        <label><?php esc_html_e( 'Pabaigos laikas', 'tobalt-city-alerts' ); ?></label>
                        <input type="time" x-model="editForm.end_time">
                    </div>
                </div>
                <div class="tobalt-modal-footer">
                    <button class="tobalt-btn-small" @click="editModal = false"><?php esc_html_e( 'Atšaukti', 'tobalt-city-alerts' ); ?></button>
                    <button class="tobalt-btn-small primary" @click="saveEdit()" :disabled="updating">
                        <?php esc_html_e( 'Išsaugoti', 'tobalt-city-alerts' ); ?>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('tobaltSubmitForm', () => ({
                activeTab: 'new',
                form: {
                    title: '',
                    description: '',
                    date: new Date().toISOString().split('T')[0],
                    time: '',
                    end_date: '',
                    end_time: '',
                    category: '',
                    severity: '',
                    location: ''
                },
                categories: [],
                submitting: false,
                submitted: false,
                message: '',
                messageType: '',

                // My Alerts
                myAlerts: [],
                loadingMyAlerts: false,
                updating: false,

                // Edit Modal
                editModal: false,
                editForm: {
                    id: null,
                    end_date: '',
                    end_time: ''
                },

                async init() {
                    // Load categories
                    try {
                        const res = await fetch('<?php echo esc_url( rest_url( 'tobalt/v1/categories' ) ); ?>');
                        this.categories = await res.json();
                    } catch (e) {
                        console.error('Failed to load categories', e);
                    }
                },

                resetForm() {
                    this.form = {
                        title: '',
                        description: '',
                        date: new Date().toISOString().split('T')[0],
                        time: '',
                        end_date: '',
                        end_time: '',
                        category: '',
                        severity: '',
                        location: ''
                    };
                    this.submitted = false;
                    this.message = '';
                },

                async submit() {
                    this.submitting = true;
                    this.message = '';

                    try {
                        const res = await fetch('<?php echo esc_url( rest_url( 'tobalt/v1/submit-alert' ) ); ?>', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify({
                                token: '<?php echo esc_js( $token ); ?>',
                                ...this.form
                            })
                        });

                        const data = await res.json();

                        if (data.success) {
                            this.submitted = true;
                            this.message = data.message;
                            this.messageType = 'success';
                        } else {
                            this.message = data.message || '<?php echo esc_js( __( 'Įvyko klaida.', 'tobalt-city-alerts' ) ); ?>';
                            this.messageType = 'error';
                        }
                    } catch (e) {
                        this.message = '<?php echo esc_js( __( 'Tinklo klaida. Bandykite dar kartą.', 'tobalt-city-alerts' ) ); ?>';
                        this.messageType = 'error';
                    }

                    this.submitting = false;
                },

                async loadMyAlerts() {
                    if (this.myAlerts.length > 0) return; // Already loaded

                    this.loadingMyAlerts = true;

                    try {
                        const res = await fetch('<?php echo esc_url( rest_url( 'tobalt/v1/my-alerts' ) ); ?>?token=<?php echo esc_js( $token ); ?>');
                        const data = await res.json();

                        if (data.success) {
                            this.myAlerts = data.alerts;
                        }
                    } catch (e) {
                        console.error('Failed to load my alerts', e);
                    }

                    this.loadingMyAlerts = false;
                },

                getStatusLabel(status) {
                    const labels = {
                        'publish': '<?php echo esc_js( __( 'Aktyvus', 'tobalt-city-alerts' ) ); ?>',
                        'pending': '<?php echo esc_js( __( 'Laukia patvirtinimo', 'tobalt-city-alerts' ) ); ?>',
                        'draft': '<?php echo esc_js( __( 'Juodraštis', 'tobalt-city-alerts' ) ); ?>'
                    };
                    return labels[status] || status;
                },

                openEditModal(alert) {
                    this.editForm = {
                        id: alert.id,
                        end_date: alert.end_date || '',
                        end_time: alert.end_time || ''
                    };
                    this.editModal = true;
                },

                async saveEdit() {
                    this.updating = true;

                    try {
                        const res = await fetch('<?php echo esc_url( rest_url( 'tobalt/v1/update-alert' ) ); ?>/' + this.editForm.id, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify({
                                token: '<?php echo esc_js( $token ); ?>',
                                end_date: this.editForm.end_date,
                                end_time: this.editForm.end_time
                            })
                        });

                        const data = await res.json();

                        if (data.success) {
                            // Update local data
                            const alert = this.myAlerts.find(a => a.id === this.editForm.id);
                            if (alert) {
                                alert.end_date = this.editForm.end_date;
                                alert.end_time = this.editForm.end_time;
                            }
                            this.editModal = false;
                        } else {
                            alert(data.message || '<?php echo esc_js( __( 'Įvyko klaida.', 'tobalt-city-alerts' ) ); ?>');
                        }
                    } catch (e) {
                        alert('<?php echo esc_js( __( 'Tinklo klaida. Bandykite dar kartą.', 'tobalt-city-alerts' ) ); ?>');
                    }

                    this.updating = false;
                },

                async markSolved(alertItem) {
                    if (!confirm('<?php echo esc_js( __( 'Ar tikrai norite pažymėti šį pranešimą kaip išspręstą?', 'tobalt-city-alerts' ) ); ?>')) {
                        return;
                    }

                    this.updating = true;

                    try {
                        const res = await fetch('<?php echo esc_url( rest_url( 'tobalt/v1/mark-solved' ) ); ?>/' + alertItem.id, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify({
                                token: '<?php echo esc_js( $token ); ?>'
                            })
                        });

                        const data = await res.json();

                        if (data.success) {
                            alertItem.solved = true;
                            alertItem.solved_at = data.solved_at;
                            alertItem.status = 'draft';
                        } else {
                            alert(data.message || '<?php echo esc_js( __( 'Įvyko klaida.', 'tobalt-city-alerts' ) ); ?>');
                        }
                    } catch (e) {
                        alert('<?php echo esc_js( __( 'Tinklo klaida. Bandykite dar kartą.', 'tobalt-city-alerts' ) ); ?>');
                    }

                    this.updating = false;
                }
            }));
        });
    </script>
    <?php wp_footer(); ?>
</body>
</html>
