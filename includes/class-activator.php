<?php
/**
 * Activation handler.
 *
 * Author: Tobalt — https://tobalt.lt
 */

namespace Tobalt\CityAlerts;

defined( 'ABSPATH' ) || exit;

class Activator {

    /**
     * Run activation tasks.
     */
    public static function activate() {
        self::create_tables();
        Subscribers::create_table();
        Activity_Log::create_table();
        self::set_default_options();
        self::schedule_cron();

        // Flush rewrite rules after CPT registration
        add_action( 'init', function() {
            flush_rewrite_rules();
        }, 999 );
    }

    /**
     * Create custom database tables.
     */
    private static function create_tables() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        $sql = [];

        // Approved emails table (dbDelta requires 2 spaces after PRIMARY KEY)
        $table_emails = $wpdb->prefix . 'tobalt_approved_emails';
        $sql[] = "CREATE TABLE {$table_emails} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            email varchar(255) NOT NULL,
            provider_id bigint(20) unsigned DEFAULT NULL,
            role varchar(50) DEFAULT 'employee',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            created_by bigint(20) unsigned DEFAULT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY email (email),
            KEY provider_id (provider_id)
        ) {$charset_collate};";

        // Magic tokens table (email index limited to 191 chars for utf8mb4 compatibility)
        $table_tokens = $wpdb->prefix . 'tobalt_magic_tokens';
        $sql[] = "CREATE TABLE {$table_tokens} (
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

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        foreach ( $sql as $query ) {
            dbDelta( $query );
        }

        update_option( 'tobalt_city_alerts_db_version', '1.0.0' );
    }

    /**
     * Set default plugin options.
     */
    private static function set_default_options() {
        $defaults = [
            'icon_position'     => 'right',
            'icon_color'        => '#0073aa',
            'panel_width'       => '400',
            'injection_mode'    => 'auto',
            'date_range'        => 7,
            'require_approval'  => true,
            'email_from_name'   => 'CityAlerts',
            'email_from_address'=> '',
            'token_expiry'      => 60, // minutes
            'rate_limit'        => 3,  // requests per hour
            'custom_labels'     => [
                'panel_title'   => '',
                'no_alerts'     => '',
                'submit_button' => '',
            ],
        ];

        if ( ! get_option( 'tobalt_city_alerts_settings' ) ) {
            add_option( 'tobalt_city_alerts_settings', $defaults );
        }
    }

    /**
     * Schedule cron jobs.
     */
    private static function schedule_cron() {
        if ( ! wp_next_scheduled( 'tobalt_city_alerts_cleanup' ) ) {
            wp_schedule_event( time(), 'hourly', 'tobalt_city_alerts_cleanup' );
        }

        // Scheduled publishing check (every 5 minutes)
        if ( ! wp_next_scheduled( 'tobalt_city_alerts_scheduled_publish' ) ) {
            wp_schedule_event( time(), 'five_minutes', 'tobalt_city_alerts_scheduled_publish' );
        }
    }
}
