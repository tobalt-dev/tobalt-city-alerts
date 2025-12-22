<?php
/**
 * Uninstall handler - only removes data if explicitly enabled in settings.
 *
 * Author: Tobalt — https://tobalt.lt
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

// Check if user wants to delete data on uninstall
$settings = get_option( 'tobalt_city_alerts_settings', [] );
$delete_data = ! empty( $settings['delete_data_on_uninstall'] );

if ( ! $delete_data ) {
    // Preserve all data - only clear transients
    global $wpdb;
    $wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_tobalt_city_alerts%'" );
    $wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_tobalt_city_alerts%'" );
    flush_rewrite_rules();
    return;
}

// Full cleanup only if delete_data_on_uninstall is enabled
global $wpdb;

// Delete custom tables
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}tobalt_approved_emails" );
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}tobalt_magic_tokens" );
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}tobalt_subscribers" );
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}tobalt_activity_log" );

// Delete all alerts (CPT)
$alerts = get_posts( [
    'post_type'      => 'tobalt_alert',
    'posts_per_page' => -1,
    'post_status'    => 'any',
    'fields'         => 'ids',
] );

foreach ( $alerts as $alert_id ) {
    wp_delete_post( $alert_id, true );
}

// Delete options
delete_option( 'tobalt_city_alerts_settings' );
delete_option( 'tobalt_city_alerts_db_version' );

// Clear transients
$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_tobalt_city_alerts%'" );
$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_tobalt_city_alerts%'" );

// Flush rewrite rules
flush_rewrite_rules();
