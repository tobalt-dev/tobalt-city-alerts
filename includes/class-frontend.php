<?php
/**
 * Frontend functionality: panel injection, shortcode.
 *
 * Author: Tobalt — https://tobalt.lt
 */

namespace Tobalt\CityAlerts;

defined( 'ABSPATH' ) || exit;

class Frontend {

    private $settings;

    public function __construct() {
        $this->settings = get_option( 'tobalt_city_alerts_settings', [] );

        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_assets' ] );
        add_action( 'wp_footer', [ $this, 'maybe_inject_panel' ] );
        add_shortcode( 'tobalt_city_alerts', [ $this, 'render_shortcode' ] );
        add_shortcode( 'tobalt_request_link', [ $this, 'render_request_link_shortcode' ] );
        add_shortcode( 'tobalt_submission_form', [ $this, 'render_request_link_shortcode' ] ); // Alias
        add_shortcode( 'tobalt_subscribe', [ $this, 'render_subscribe_shortcode' ] );

        // Handle submission page
        add_action( 'template_redirect', [ $this, 'handle_submission_page' ] );
        add_action( 'template_redirect', [ $this, 'handle_subscription_verify' ] );
        add_action( 'template_redirect', [ $this, 'handle_unsubscribe' ] );
    }

    /**
     * Enqueue frontend assets.
     */
    public function enqueue_assets() {
        if ( is_admin() ) {
            return;
        }

        // Frontend JS (must load before Alpine)
        wp_enqueue_script(
            'tobalt-city-alerts',
            TOBALT_CITY_ALERTS_URL . 'assets/js/frontend.js',
            [],
            TOBALT_CITY_ALERTS_VERSION,
            true
        );

        // Alpine.js (bundled) - loads after frontend.js
        wp_enqueue_script(
            'alpine',
            TOBALT_CITY_ALERTS_URL . 'assets/js/alpine.min.js',
            [ 'tobalt-city-alerts' ],
            '3.14.3',
            true
        );

        // Frontend CSS
        wp_enqueue_style(
            'tobalt-city-alerts',
            TOBALT_CITY_ALERTS_URL . 'assets/css/frontend.css',
            [],
            TOBALT_CITY_ALERTS_VERSION
        );

        // Localize script
        $labels = $this->settings['custom_labels'] ?? [];

        wp_localize_script( 'tobalt-city-alerts', 'tobaltCityAlerts', [
            'apiUrl'     => rest_url( 'tobalt/v1/' ),
            'nonce'      => wp_create_nonce( 'wp_rest' ),
            'settings'   => [
                'iconPosition' => $this->settings['icon_position'] ?? 'right',
                'iconColor'    => $this->settings['icon_color'] ?? '#0073aa',
                'panelWidth'   => (int) ( $this->settings['panel_width'] ?? 400 ),
                'dateRange'    => (int) ( $this->settings['date_range'] ?? 7 ),
            ],
            'labels'     => [
                'panelTitle'   => $labels['panel_title'] ?: __( 'Avarijos ir planiniai darbai', 'tobalt-city-alerts' ),
                'noAlerts'     => $labels['no_alerts'] ?: __( 'Šią dieną pranešimų nėra', 'tobalt-city-alerts' ),
                'submitButton' => $labels['submit_button'] ?: __( 'Pateikti pranešimą', 'tobalt-city-alerts' ),
                'loading'      => __( 'Kraunama...', 'tobalt-city-alerts' ),
                'today'        => __( 'Šiandien', 'tobalt-city-alerts' ),
                'tomorrow'     => __( 'Rytoj', 'tobalt-city-alerts' ),
                'pinned'       => __( 'Prisegtas', 'tobalt-city-alerts' ),
            ],
            'severityColors' => [
                'low'      => '#4caf50',
                'medium'   => '#ff9800',
                'high'     => '#f44336',
                'critical' => '#9c27b0',
            ],
            'recaptcha' => [
                'enabled' => Recaptcha::is_enabled(),
                'siteKey' => Recaptcha::get_site_key(),
            ],
        ] );

        // CSS custom properties
        $custom_css = sprintf(
            ':root { --tobalt-icon-color: %s; --tobalt-panel-width: %dpx; }',
            esc_attr( $this->settings['icon_color'] ?? '#0073aa' ),
            (int) ( $this->settings['panel_width'] ?? 400 )
        );
        wp_add_inline_style( 'tobalt-city-alerts', $custom_css );

        // reCAPTCHA
        if ( Recaptcha::is_enabled() ) {
            wp_enqueue_script(
                'google-recaptcha',
                'https://www.google.com/recaptcha/api.js?render=' . esc_attr( Recaptcha::get_site_key() ),
                [],
                null,
                true
            );
        }
    }

    /**
     * Maybe inject panel (auto or menu mode).
     */
    public function maybe_inject_panel() {
        $mode = $this->settings['injection_mode'] ?? 'auto';

        if ( is_admin() ) {
            return;
        }

        // Auto mode: show floating panel
        if ( 'auto' === $mode ) {
            $this->render_panel();
            return;
        }

        // Menu mode: inject panel without floating button (menu has the trigger)
        if ( 'menu' === $mode ) {
            $this->render_panel( true );
            return;
        }
    }

    /**
     * Render panel HTML.
     *
     * @param bool $hide_button Hide the floating button (for menu mode).
     */
    private function render_panel( $hide_button = false ) {
        include TOBALT_CITY_ALERTS_PATH . 'templates/panel.php';
    }

    /**
     * Shortcode handler.
     */
    public function render_shortcode( $atts ) {
        $atts = shortcode_atts( [
            'inline' => false,
        ], $atts, 'tobalt_city_alerts' );

        ob_start();

        if ( $atts['inline'] ) {
            // Inline version (no floating button)
            include TOBALT_CITY_ALERTS_PATH . 'templates/panel-inline.php';
        } else {
            $this->render_panel();
        }

        return ob_get_clean();
    }

    /**
     * Handle submission page (magic link landing).
     */
    public function handle_submission_page() {
        if ( empty( $_GET['tobalt_submit'] ) ) {
            return;
        }

        $token = sanitize_text_field( $_GET['token'] ?? '' );

        if ( empty( $token ) ) {
            wp_die( __( 'Invalid submission link.', 'tobalt-city-alerts' ) );
        }

        // Verify token
        $magic_link = new Magic_Link();
        $result     = $magic_link->verify_token( $token );

        if ( is_wp_error( $result ) ) {
            wp_die( $result->get_error_message() );
        }

        // Render submission form
        $email = $result['email'];
        include TOBALT_CITY_ALERTS_PATH . 'templates/submission-form.php';
        exit;
    }

    /**
     * Render request magic link shortcode.
     */
    public function render_request_link_shortcode( $atts ) {
        $atts = shortcode_atts( [
            'title' => __( 'Request Submission Link', 'tobalt-city-alerts' ),
        ], $atts, 'tobalt_request_link' );

        ob_start();
        include TOBALT_CITY_ALERTS_PATH . 'templates/request-link-form.php';
        return ob_get_clean();
    }

    /**
     * Render subscribe shortcode.
     */
    public function render_subscribe_shortcode( $atts ) {
        $atts = shortcode_atts( [
            'title' => __( 'Subscribe to Alerts', 'tobalt-city-alerts' ),
        ], $atts, 'tobalt_subscribe' );

        ob_start();
        include TOBALT_CITY_ALERTS_PATH . 'templates/subscribe-form.php';
        return ob_get_clean();
    }

    /**
     * Handle subscription verification.
     */
    public function handle_subscription_verify() {
        if ( empty( $_GET['tobalt_verify_sub'] ) ) {
            return;
        }

        $token = sanitize_text_field( $_GET['token'] ?? '' );

        if ( empty( $token ) ) {
            wp_die( __( 'Invalid verification link.', 'tobalt-city-alerts' ) );
        }

        $subscribers = new Subscribers();
        $result      = $subscribers->verify( $token );

        if ( is_wp_error( $result ) ) {
            wp_die( $result->get_error_message() );
        }

        // Show success page
        include TOBALT_CITY_ALERTS_PATH . 'templates/subscription-verified.php';
        exit;
    }

    /**
     * Handle unsubscribe.
     */
    public function handle_unsubscribe() {
        if ( empty( $_GET['tobalt_unsubscribe'] ) ) {
            return;
        }

        $token = sanitize_text_field( $_GET['token'] ?? '' );

        if ( empty( $token ) ) {
            wp_die( __( 'Invalid unsubscribe link.', 'tobalt-city-alerts' ) );
        }

        $subscribers = new Subscribers();
        $result      = $subscribers->unsubscribe( $token );

        if ( is_wp_error( $result ) ) {
            wp_die( $result->get_error_message() );
        }

        // Show success page
        include TOBALT_CITY_ALERTS_PATH . 'templates/unsubscribed.php';
        exit;
    }
}
