<?php
/**
 * Scheduled publishing and expiration handler.
 *
 * Author: Tobalt — https://tobalt.lt
 */

namespace Tobalt\CityAlerts;

defined( 'ABSPATH' ) || exit;

class Scheduler {

    public function __construct() {
        add_action( 'tobalt_city_alerts_scheduled_publish', [ $this, 'process_scheduled_alerts' ] );
        add_action( 'tobalt_city_alerts_cleanup', [ $this, 'cleanup_expired_alerts' ] );
    }

    /**
     * Process alerts scheduled for publishing.
     *
     * Checks for alerts with:
     * - _tobalt_alert_scheduled_date in the past
     * - post_status = 'future' or 'draft'
     *
     * Publishes them automatically.
     */
    public function process_scheduled_alerts() {
        $now = current_time( 'Y-m-d H:i:s' );

        $args = [
            'post_type'      => 'tobalt_alert',
            'post_status'    => [ 'future', 'draft' ],
            'posts_per_page' => 50,
            'meta_query'     => [
                'relation' => 'AND',
                [
                    'key'     => '_tobalt_alert_scheduled_publish',
                    'value'   => $now,
                    'compare' => '<=',
                    'type'    => 'DATETIME',
                ],
                [
                    'key'     => '_tobalt_alert_scheduled_publish',
                    'value'   => '',
                    'compare' => '!=',
                ],
            ],
        ];

        $query = new \WP_Query( $args );

        foreach ( $query->posts as $post ) {
            wp_update_post( [
                'ID'          => $post->ID,
                'post_status' => 'publish',
            ] );

            // Clear the scheduled date after publishing
            delete_post_meta( $post->ID, '_tobalt_alert_scheduled_publish' );

            do_action( 'tobalt_city_alerts_alert_published', $post->ID );
        }
    }

    /**
     * Auto-expire alerts based on end date.
     *
     * Alerts with _tobalt_alert_end_date in the past are moved to 'draft' status.
     */
    public function cleanup_expired_alerts() {
        $today = current_time( 'Y-m-d' );

        $args = [
            'post_type'      => 'tobalt_alert',
            'post_status'    => 'publish',
            'posts_per_page' => 100,
            'meta_query'     => [
                'relation' => 'AND',
                [
                    'key'     => '_tobalt_alert_end_date',
                    'value'   => $today,
                    'compare' => '<',
                    'type'    => 'DATE',
                ],
                [
                    'key'     => '_tobalt_alert_end_date',
                    'value'   => '',
                    'compare' => '!=',
                ],
            ],
        ];

        $query = new \WP_Query( $args );

        foreach ( $query->posts as $post ) {
            wp_update_post( [
                'ID'          => $post->ID,
                'post_status' => 'draft',
            ] );

            // Mark as auto-expired
            update_post_meta( $post->ID, '_tobalt_alert_auto_expired', current_time( 'mysql' ) );

            do_action( 'tobalt_city_alerts_alert_expired', $post->ID );
        }
    }
}
