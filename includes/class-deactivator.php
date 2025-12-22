<?php
/**
 * Deactivation handler.
 *
 * Author: Tobalt — https://tobalt.lt
 */

namespace Tobalt\CityAlerts;

defined( 'ABSPATH' ) || exit;

class Deactivator {

    /**
     * Run deactivation tasks.
     */
    public static function deactivate() {
        // Clear scheduled cron
        wp_clear_scheduled_hook( 'tobalt_city_alerts_cleanup' );
        wp_clear_scheduled_hook( 'tobalt_city_alerts_scheduled_publish' );

        // Flush rewrite rules
        flush_rewrite_rules();
    }
}
