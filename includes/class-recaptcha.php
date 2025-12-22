<?php
/**
 * reCAPTCHA v3 integration.
 *
 * Author: Tobalt — https://tobalt.lt
 */

namespace Tobalt\CityAlerts;

defined( 'ABSPATH' ) || exit;

class Recaptcha {

    private static $settings = null;

    /**
     * Get settings.
     */
    private static function get_settings() {
        if ( null === self::$settings ) {
            self::$settings = get_option( 'tobalt_city_alerts_settings', [] );
        }
        return self::$settings;
    }

    /**
     * Check if reCAPTCHA is enabled and configured.
     */
    public static function is_enabled() {
        $settings = self::get_settings();
        return ! empty( $settings['recaptcha_enabled'] )
            && ! empty( $settings['recaptcha_site_key'] )
            && ! empty( $settings['recaptcha_secret_key'] );
    }

    /**
     * Get site key.
     */
    public static function get_site_key() {
        $settings = self::get_settings();
        return $settings['recaptcha_site_key'] ?? '';
    }

    /**
     * Get secret key.
     */
    public static function get_secret_key() {
        $settings = self::get_settings();
        return $settings['recaptcha_secret_key'] ?? '';
    }

    /**
     * Verify reCAPTCHA token.
     *
     * @param string $token    The reCAPTCHA token from frontend.
     * @param string $action   Expected action name.
     * @param float  $min_score Minimum score (0.0 - 1.0), default 0.5.
     *
     * @return true|\WP_Error True on success, WP_Error on failure.
     */
    public static function verify( $token, $action = '', $min_score = 0.5 ) {
        if ( ! self::is_enabled() ) {
            return true; // reCAPTCHA disabled, allow request
        }

        if ( empty( $token ) ) {
            return new \WP_Error(
                'recaptcha_missing',
                __( 'reCAPTCHA verification failed. Please try again.', 'tobalt-city-alerts' )
            );
        }

        $response = wp_remote_post( 'https://www.google.com/recaptcha/api/siteverify', [
            'timeout' => 10,
            'body'    => [
                'secret'   => self::get_secret_key(),
                'response' => $token,
                'remoteip' => self::get_client_ip(),
            ],
        ] );

        if ( is_wp_error( $response ) ) {
            // Log error but don't block user if Google is unreachable
            error_log( 'Tobalt City Alerts reCAPTCHA error: ' . $response->get_error_message() );
            return true;
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( empty( $body['success'] ) ) {
            $error_codes = $body['error-codes'] ?? [];
            error_log( 'Tobalt City Alerts reCAPTCHA failed: ' . implode( ', ', $error_codes ) );
            return new \WP_Error(
                'recaptcha_failed',
                __( 'reCAPTCHA verification failed. Please try again.', 'tobalt-city-alerts' )
            );
        }

        // Check action if specified
        if ( $action && isset( $body['action'] ) && $body['action'] !== $action ) {
            return new \WP_Error(
                'recaptcha_action_mismatch',
                __( 'reCAPTCHA verification failed. Please try again.', 'tobalt-city-alerts' )
            );
        }

        // Check score
        $score = $body['score'] ?? 0;
        if ( $score < $min_score ) {
            return new \WP_Error(
                'recaptcha_low_score',
                __( 'reCAPTCHA verification failed. Please try again.', 'tobalt-city-alerts' )
            );
        }

        return true;
    }

    /**
     * Get client IP address.
     */
    private static function get_client_ip() {
        $ip = '';

        if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
            $ip = sanitize_text_field( wp_unslash( $_SERVER['HTTP_CLIENT_IP'] ) );
        } elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
            $ip = sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) );
            // Take first IP if multiple
            if ( strpos( $ip, ',' ) !== false ) {
                $ip = trim( explode( ',', $ip )[0] );
            }
        } elseif ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
            $ip = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
        }

        return filter_var( $ip, FILTER_VALIDATE_IP ) ?: '';
    }

    /**
     * Render reCAPTCHA script tag for frontend.
     */
    public static function render_script() {
        if ( ! self::is_enabled() ) {
            return;
        }

        $site_key = self::get_site_key();
        ?>
        <script src="https://www.google.com/recaptcha/api.js?render=<?php echo esc_attr( $site_key ); ?>"></script>
        <?php
    }

    /**
     * Get JavaScript code to execute reCAPTCHA.
     *
     * @param string $action Action name for this form.
     * @return string JavaScript code.
     */
    public static function get_execute_js( $action ) {
        if ( ! self::is_enabled() ) {
            return 'Promise.resolve("")';
        }

        $site_key = self::get_site_key();
        return "grecaptcha.execute('" . esc_js( $site_key ) . "', {action: '" . esc_js( $action ) . "'})";
    }
}
