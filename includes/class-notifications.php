<?php
/**
 * Notifications: Send emails to subscribers when alerts are published.
 *
 * Author: Tobalt — https://tobalt.lt
 */

namespace Tobalt\CityAlerts;

defined( 'ABSPATH' ) || exit;

class Notifications {

    private $subscribers;
    private $batch_size = 50;

    public function __construct() {
        $this->subscribers = new Subscribers();

        // Hook into alert publish
        add_action( 'transition_post_status', [ $this, 'on_status_change' ], 10, 3 );

        // Batch sending via cron
        add_action( 'tobalt_send_notification_batch', [ $this, 'send_batch' ], 10, 2 );
    }

    /**
     * Triggered when alert status changes.
     */
    public function on_status_change( $new_status, $old_status, $post ) {
        // Only for our CPT
        if ( 'tobalt_alert' !== $post->post_type ) {
            return;
        }

        // Only when transitioning to publish
        if ( 'publish' !== $new_status || 'publish' === $old_status ) {
            return;
        }

        // Check if notifications already sent for this alert
        if ( get_post_meta( $post->ID, '_tobalt_notifications_sent', true ) ) {
            return;
        }

        $this->queue_notifications( $post->ID );
    }

    /**
     * Queue notifications for an alert.
     */
    private function queue_notifications( $alert_id ) {
        $alert = get_post( $alert_id );

        if ( ! $alert || 'publish' !== $alert->post_status ) {
            return;
        }

        // Get category IDs
        $categories = wp_get_post_terms( $alert_id, 'tobalt_alert_category', [ 'fields' => 'ids' ] );
        $category_ids = is_wp_error( $categories ) ? [] : $categories;

        // Get matching subscribers
        $subscribers = $this->subscribers->get_matching( $category_ids );

        if ( empty( $subscribers ) ) {
            // Mark as sent (no subscribers)
            update_post_meta( $alert_id, '_tobalt_notifications_sent', current_time( 'mysql' ) );
            update_post_meta( $alert_id, '_tobalt_notifications_count', 0 );
            return;
        }

        // Extract emails
        $emails = wp_list_pluck( $subscribers, 'email' );

        // Schedule batch sending
        $batches = array_chunk( $emails, $this->batch_size );

        foreach ( $batches as $index => $batch ) {
            $delay = $index * 30; // 30 seconds between batches
            wp_schedule_single_event(
                time() + $delay,
                'tobalt_send_notification_batch',
                [ $alert_id, $batch ]
            );
        }

        // Mark as queued
        update_post_meta( $alert_id, '_tobalt_notifications_sent', current_time( 'mysql' ) );
        update_post_meta( $alert_id, '_tobalt_notifications_count', count( $emails ) );
    }

    /**
     * Send a batch of notification emails.
     */
    public function send_batch( $alert_id, $emails ) {
        $alert = get_post( $alert_id );

        if ( ! $alert ) {
            return;
        }

        foreach ( $emails as $email ) {
            $this->send_notification( $alert, $email );
        }
    }

    /**
     * Send notification email to a single subscriber.
     */
    private function send_notification( $alert, $email ) {
        // Generate unsubscribe token
        $unsubscribe_token = $this->subscribers->get_unsubscribe_token( $email );

        if ( ! $unsubscribe_token ) {
            return;
        }

        $unsubscribe_url = add_query_arg( [
            'tobalt_unsubscribe' => '1',
            'token'              => $unsubscribe_token,
        ], home_url( '/' ) );

        // Get alert data
        $severity    = get_post_meta( $alert->ID, '_tobalt_severity', true ) ?: 'medium';
        $starts_at   = get_post_meta( $alert->ID, '_tobalt_starts_at', true );
        $ends_at     = get_post_meta( $alert->ID, '_tobalt_ends_at', true );
        $categories  = wp_get_post_terms( $alert->ID, 'tobalt_alert_category', [ 'fields' => 'names' ] );

        $category_names = is_wp_error( $categories ) ? [] : $categories;

        // Build email
        $site_name = get_bloginfo( 'name' );
        $subject   = sprintf(
            /* translators: 1: Site name, 2: Alert title */
            __( '[%1$s] New Alert: %2$s', 'tobalt-city-alerts' ),
            $site_name,
            $alert->post_title
        );

        // Render email template
        ob_start();
        include TOBALT_CITY_ALERTS_PATH . 'templates/email-notification.php';
        $message = ob_get_clean();

        // Send
        $headers = [
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . $site_name . ' <' . get_option( 'admin_email' ) . '>',
        ];

        wp_mail( $email, $subject, $message, $headers );
    }

    /**
     * Manually trigger notifications for an alert (admin action).
     */
    public function resend_notifications( $alert_id ) {
        // Clear previous flag
        delete_post_meta( $alert_id, '_tobalt_notifications_sent' );
        delete_post_meta( $alert_id, '_tobalt_notifications_count' );

        // Re-queue
        $this->queue_notifications( $alert_id );
    }
}
