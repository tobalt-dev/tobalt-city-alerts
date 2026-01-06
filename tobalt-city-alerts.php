<?php
/**
 * Plugin Name: Tobalt City Alerts
 * Plugin URI: https://tobalt.lt
 * Description: Pranešimų sistema savivaldybėms ir organizacijoms. Darbuotojai pateikia pranešimus per magic link, gyventojai mato juos išskleidžiamame skydelyje.
 * Version: 1.3.10
 * Author: Tobalt — https://tobalt.lt
 * Author URI: https://tobalt.lt
 * License: GPL v2 or later
 * Text Domain: tobalt-city-alerts
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 */

namespace Tobalt\CityAlerts;

defined( 'ABSPATH' ) || exit;

define( 'TOBALT_CITY_ALERTS_VERSION', '1.3.10' );
define( 'TOBALT_CITY_ALERTS_FILE', __FILE__ );
define( 'TOBALT_CITY_ALERTS_PATH', plugin_dir_path( __FILE__ ) );
define( 'TOBALT_CITY_ALERTS_URL', plugin_dir_url( __FILE__ ) );
define( 'TOBALT_CITY_ALERTS_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Autoloader for plugin classes.
 */
spl_autoload_register( function ( $class ) {
    $prefix = 'Tobalt\\CityAlerts\\';
    $len    = strlen( $prefix );

    if ( strncmp( $prefix, $class, $len ) !== 0 ) {
        return;
    }

    $relative_class = substr( $class, $len );
    $file           = TOBALT_CITY_ALERTS_PATH . 'includes/class-' . strtolower( str_replace( '_', '-', $relative_class ) ) . '.php';

    if ( file_exists( $file ) ) {
        require $file;
    }
} );

/**
 * Run activation tasks.
 */
function tobalt_city_alerts_activate() {
    require_once TOBALT_CITY_ALERTS_PATH . 'includes/class-activator.php';
    Activator::activate();
}
register_activation_hook( __FILE__, __NAMESPACE__ . '\\tobalt_city_alerts_activate' );

/**
 * Run deactivation tasks.
 */
function tobalt_city_alerts_deactivate() {
    require_once TOBALT_CITY_ALERTS_PATH . 'includes/class-deactivator.php';
    Deactivator::deactivate();
}
register_deactivation_hook( __FILE__, __NAMESPACE__ . '\\tobalt_city_alerts_deactivate' );

/**
 * Add custom cron schedules.
 */
function tobalt_city_alerts_cron_schedules( $schedules ) {
    $schedules['five_minutes'] = [
        'interval' => 300,
        'display'  => __( 'Every 5 Minutes', 'tobalt-city-alerts' ),
    ];
    return $schedules;
}
add_filter( 'cron_schedules', __NAMESPACE__ . '\\tobalt_city_alerts_cron_schedules' );

/**
 * Ensure database tables exist (fallback for failed activation).
 */
function tobalt_city_alerts_maybe_create_tables() {
    global $wpdb;

    $db_version = get_option( 'tobalt_city_alerts_db_version' );
    if ( $db_version === '1.0.0' ) {
        return; // Tables already created
    }

    // Check if tables exist
    $tokens_table = $wpdb->prefix . 'tobalt_magic_tokens';
    $emails_table = $wpdb->prefix . 'tobalt_approved_emails';

    $tokens_exists = $wpdb->get_var( "SHOW TABLES LIKE '{$tokens_table}'" ) === $tokens_table;
    $emails_exists = $wpdb->get_var( "SHOW TABLES LIKE '{$emails_table}'" ) === $emails_table;

    if ( $tokens_exists && $emails_exists ) {
        update_option( 'tobalt_city_alerts_db_version', '1.0.0' );
        return;
    }

    // Create tables
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    $charset_collate = $wpdb->get_charset_collate();

    if ( ! $emails_exists ) {
        // Try dbDelta first
        dbDelta( "CREATE TABLE {$emails_table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            email varchar(255) NOT NULL,
            provider_id bigint(20) unsigned DEFAULT NULL,
            role varchar(50) DEFAULT 'employee',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            created_by bigint(20) unsigned DEFAULT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY email (email),
            KEY provider_id (provider_id)
        ) {$charset_collate};" );

        // Fallback: direct query
        if ( $wpdb->get_var( "SHOW TABLES LIKE '{$emails_table}'" ) !== $emails_table ) {
            $wpdb->query( "CREATE TABLE IF NOT EXISTS `{$emails_table}` (
                `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                `email` varchar(255) NOT NULL,
                `provider_id` bigint(20) unsigned DEFAULT NULL,
                `role` varchar(50) DEFAULT 'employee',
                `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
                `created_by` bigint(20) unsigned DEFAULT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `email` (`email`),
                KEY `provider_id` (`provider_id`)
            ) ENGINE=InnoDB {$charset_collate}" );
        }
    }

    if ( ! $tokens_exists ) {
        // Try dbDelta first (email index limited to 191 chars for utf8mb4 compatibility)
        dbDelta( "CREATE TABLE {$tokens_table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            email varchar(255) NOT NULL,
            token varchar(64) NOT NULL,
            expires_at datetime NOT NULL,
            used_at datetime DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY token (token),
            KEY email_expires (email(191),expires_at)
        ) {$charset_collate};" );

        // Fallback: direct query
        if ( $wpdb->get_var( "SHOW TABLES LIKE '{$tokens_table}'" ) !== $tokens_table ) {
            $wpdb->query( "CREATE TABLE IF NOT EXISTS `{$tokens_table}` (
                `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                `email` varchar(255) NOT NULL,
                `token` varchar(64) NOT NULL,
                `expires_at` datetime NOT NULL,
                `used_at` datetime DEFAULT NULL,
                `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `token` (`token`),
                KEY `email_expires` (`email`(191),`expires_at`)
            ) ENGINE=InnoDB {$charset_collate}" );
        }
    }

    update_option( 'tobalt_city_alerts_db_version', '1.0.0' );
}

/**
 * Initialize plugin.
 */
function tobalt_city_alerts_init() {
    load_plugin_textdomain( 'tobalt-city-alerts', false, dirname( TOBALT_CITY_ALERTS_BASENAME ) . '/languages' );

    // Ensure tables exist (fallback if activation hook didn't run)
    tobalt_city_alerts_maybe_create_tables();

    // Core components
    new CPT();
    new Meta_Boxes();
    new Admin_Settings();
    new Admin_Emails();
    new Magic_Link();
    new Rest_API();
    new Frontend();
    new Scheduler();
    new CSV_Export();
    new Subscribers();
    new Notifications();
    new Activity_Log();
    new Admin_Activity_Log();
    new Nav_Menu();
}
add_action( 'plugins_loaded', __NAMESPACE__ . '\\tobalt_city_alerts_init' );
