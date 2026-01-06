<?php
/**
 * Magic link authentication system.
 *
 * Author: Tobalt — https://tobalt.lt
 */

namespace Tobalt\CityAlerts;

defined( 'ABSPATH' ) || exit;

class Magic_Link {

    private $tokens_table;

    public function __construct() {
        global $wpdb;
        $this->tokens_table = $wpdb->prefix . 'tobalt_magic_tokens';

        add_action( 'tobalt_city_alerts_cleanup', [ $this, 'cleanup_expired_tokens' ] );
    }

    /**
     * Ensure database table exists, create if missing.
     */
    private function ensure_table_exists() {
        global $wpdb;

        // Check if table exists first
        $table_exists = $wpdb->get_var( "SHOW TABLES LIKE '{$this->tokens_table}'" ) === $this->tokens_table;

        if ( $table_exists ) {
            return true;
        }

        // Use dbDelta for better compatibility with shared hosting
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $charset_collate = $wpdb->get_charset_collate();

        // dbDelta requires specific formatting (email index limited to 191 chars for utf8mb4)
        $sql = "CREATE TABLE {$this->tokens_table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            email varchar(255) NOT NULL,
            token varchar(64) NOT NULL,
            expires_at datetime NOT NULL,
            used_at datetime DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY token (token),
            KEY email_expires (email(191),expires_at)
        ) {$charset_collate};";

        dbDelta( $sql );

        return true;
    }

    /**
     * Get settings helper.
     */
    private function get_setting( $key, $default = '' ) {
        $settings = get_option( 'tobalt_city_alerts_settings', [] );
        return $settings[ $key ] ?? $default;
    }

    /**
     * Generate a magic link token.
     */
    public function generate_token( $email ) {
        global $wpdb;

        // Ensure table exists
        $this->ensure_table_exists();

        $email = sanitize_email( $email );

        // Check if email is approved
        $admin_emails = new Admin_Emails();
        if ( ! $admin_emails->is_approved( $email ) ) {
            return new \WP_Error( 'not_approved', __( 'This email is not approved for submissions.', 'tobalt-city-alerts' ) );
        }

        // Rate limiting
        if ( ! $this->check_rate_limit( $email ) ) {
            return new \WP_Error( 'rate_limited', __( 'Too many requests. Please try again later.', 'tobalt-city-alerts' ) );
        }

        // Generate token
        $token      = bin2hex( random_bytes( 32 ) );
        $expiry_min = (int) $this->get_setting( 'token_expiry', 60 );
        $expires_at = gmdate( 'Y-m-d H:i:s', time() + ( $expiry_min * 60 ) );

        // Store token
        $result = $wpdb->insert(
            $this->tokens_table,
            [
                'email'      => $email,
                'token'      => $token,
                'expires_at' => $expires_at,
            ],
            [ '%s', '%s', '%s' ]
        );

        if ( ! $result ) {
            // Log DB error for debugging, but don't expose to users
            if ( ! empty( $wpdb->last_error ) ) {
                error_log( 'Tobalt City Alerts - Magic Link DB Error: ' . $wpdb->last_error );
            }
            return new \WP_Error( 'db_error', __( 'Failed to generate token. Please try again.', 'tobalt-city-alerts' ) );
        }

        return [
            'token'      => $token,
            'expires_at' => $expires_at,
        ];
    }

    /**
     * Verify a magic link token.
     */
    public function verify_token( $token ) {
        global $wpdb;

        // Ensure table exists
        $this->ensure_table_exists();

        $token = sanitize_text_field( $token );

        if ( empty( $token ) || strlen( $token ) !== 64 ) {
            return new \WP_Error( 'invalid_token', __( 'Invalid token format.', 'tobalt-city-alerts' ) );
        }

        $row = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$this->tokens_table} WHERE token = %s",
            $token
        ) );

        if ( ! $row ) {
            return new \WP_Error( 'token_not_found', __( 'Token not found.', 'tobalt-city-alerts' ) );
        }

        if ( strtotime( $row->expires_at ) < time() ) {
            return new \WP_Error( 'token_expired', __( 'This link has expired. Please request a new one.', 'tobalt-city-alerts' ) );
        }

        if ( ! empty( $row->used_at ) ) {
            return new \WP_Error( 'token_used', __( 'This link has already been used.', 'tobalt-city-alerts' ) );
        }

        return [
            'email'      => $row->email,
            'expires_at' => $row->expires_at,
        ];
    }

    /**
     * Mark token as used.
     */
    public function mark_token_used( $token ) {
        global $wpdb;

        return $wpdb->update(
            $this->tokens_table,
            [ 'used_at' => current_time( 'mysql', true ) ],
            [ 'token' => sanitize_text_field( $token ) ],
            [ '%s' ],
            [ '%s' ]
        );
    }

    /**
     * Get token data without marking as used (for read-only operations).
     */
    public function get_token_data( $token ) {
        global $wpdb;

        $token = sanitize_text_field( $token );

        if ( empty( $token ) || strlen( $token ) !== 64 ) {
            return null;
        }

        $row = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$this->tokens_table} WHERE token = %s",
            $token
        ) );

        if ( ! $row ) {
            return null;
        }

        // Check if expired
        if ( strtotime( $row->expires_at ) < time() ) {
            return null;
        }

        return [
            'email'      => $row->email,
            'expires_at' => $row->expires_at,
            'used_at'    => $row->used_at,
        ];
    }

    /**
     * Check rate limit for email.
     */
    private function check_rate_limit( $email ) {
        global $wpdb;

        $rate_limit = (int) $this->get_setting( 'rate_limit', 3 );
        $hour_ago   = gmdate( 'Y-m-d H:i:s', time() - HOUR_IN_SECONDS );

        $count = (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->tokens_table} WHERE email = %s AND created_at > %s",
            $email,
            $hour_ago
        ) );

        return $count < $rate_limit;
    }

    /**
     * Send magic link email.
     */
    public function send_magic_link( $email, $token ) {
        $settings   = get_option( 'tobalt_city_alerts_settings', [] );
        $from_name  = $settings['email_from_name'] ?? 'CityAlerts';
        $from_email = $settings['email_from_address'] ?: get_option( 'admin_email' );

        $submit_url = add_query_arg( [
            'tobalt_submit' => 1,
            'token'         => $token,
        ], home_url( '/' ) );

        $subject = sprintf(
            /* translators: %s: site name */
            __( '[%s] Your Alert Submission Link', 'tobalt-city-alerts' ),
            get_bloginfo( 'name' )
        );

        $expiry_min = (int) $this->get_setting( 'token_expiry', 60 );

        ob_start();
        include TOBALT_CITY_ALERTS_PATH . 'templates/email-magic-link.php';
        $message = ob_get_clean();

        $headers = [
            'Content-Type: text/html; charset=UTF-8',
            sprintf( 'From: %s <%s>', $from_name, $from_email ),
        ];

        return wp_mail( $email, $subject, $message, $headers );
    }

    /**
     * Cleanup expired tokens (cron job).
     */
    public function cleanup_expired_tokens() {
        global $wpdb;

        // Delete tokens expired more than 24 hours ago
        $cutoff = gmdate( 'Y-m-d H:i:s', time() - DAY_IN_SECONDS );

        $wpdb->query( $wpdb->prepare(
            "DELETE FROM {$this->tokens_table} WHERE expires_at < %s",
            $cutoff
        ) );
    }

    /**
     * Get submission URL for a token.
     */
    public function get_submission_url( $token ) {
        return add_query_arg( [
            'tobalt_submit' => 1,
            'token'         => $token,
        ], home_url( '/' ) );
    }
}
