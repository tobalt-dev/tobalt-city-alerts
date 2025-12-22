<?php
/**
 * Activity Log for tracking alert actions.
 *
 * Author: Tobalt — https://tobalt.lt
 */

namespace Tobalt\CityAlerts;

defined( 'ABSPATH' ) || exit;

class Activity_Log {

    private $table_name;

    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'tobalt_alert_activity_log';

        // Hook into alert lifecycle events
        add_action( 'tobalt_city_alerts_alert_created', [ $this, 'log_alert_created' ], 10, 2 );
        add_action( 'tobalt_city_alerts_alert_published', [ $this, 'log_alert_published' ] );
        add_action( 'tobalt_city_alerts_alert_expired', [ $this, 'log_alert_expired' ] );
        add_action( 'tobalt_city_alerts_alert_solved', [ $this, 'log_alert_solved' ], 10, 2 );
        add_action( 'tobalt_city_alerts_alert_updated', [ $this, 'log_alert_updated' ], 10, 2 );
    }

    /**
     * Create the activity log table.
     */
    public static function create_table() {
        global $wpdb;
        $table_name      = $wpdb->prefix . 'tobalt_alert_activity_log';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            alert_id bigint(20) unsigned NOT NULL,
            action varchar(50) NOT NULL,
            actor_email varchar(255) DEFAULT NULL,
            actor_type varchar(20) DEFAULT 'user',
            details longtext DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY alert_id (alert_id),
            KEY action (action),
            KEY created_at (created_at)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );
    }

    /**
     * Log an activity.
     *
     * @param int    $alert_id Alert post ID.
     * @param string $action   Action type (created, published, expired, solved, updated).
     * @param string $email    Actor email.
     * @param string $actor_type Actor type (user, system, admin).
     * @param array  $details  Additional details.
     */
    public function log( $alert_id, $action, $email = '', $actor_type = 'user', $details = [] ) {
        global $wpdb;

        $wpdb->insert(
            $this->table_name,
            [
                'alert_id'    => $alert_id,
                'action'      => $action,
                'actor_email' => $email,
                'actor_type'  => $actor_type,
                'details'     => wp_json_encode( $details ),
                'created_at'  => current_time( 'mysql' ),
            ],
            [ '%d', '%s', '%s', '%s', '%s', '%s' ]
        );

        return $wpdb->insert_id;
    }

    /**
     * Log alert creation.
     */
    public function log_alert_created( $alert_id, $email ) {
        $post = get_post( $alert_id );
        $this->log( $alert_id, 'created', $email, 'user', [
            'title'    => $post->post_title,
            'date'     => get_post_meta( $alert_id, '_tobalt_alert_date', true ),
            'end_date' => get_post_meta( $alert_id, '_tobalt_alert_end_date', true ),
            'status'   => $post->post_status,
        ] );
    }

    /**
     * Log alert published.
     */
    public function log_alert_published( $alert_id ) {
        $this->log( $alert_id, 'published', '', 'system', [] );
    }

    /**
     * Log alert expired (auto-archived).
     */
    public function log_alert_expired( $alert_id ) {
        $end_date = get_post_meta( $alert_id, '_tobalt_alert_end_date', true );
        $this->log( $alert_id, 'expired', '', 'system', [
            'end_date'   => $end_date,
            'expired_at' => current_time( 'mysql' ),
        ] );
    }

    /**
     * Log alert marked as solved.
     */
    public function log_alert_solved( $alert_id, $email ) {
        $created_log = $this->get_creation_log( $alert_id );
        $created_at  = $created_log ? $created_log->created_at : null;
        $solved_at   = current_time( 'mysql' );

        $time_to_solve = null;
        if ( $created_at ) {
            $time_to_solve = strtotime( $solved_at ) - strtotime( $created_at );
        }

        $this->log( $alert_id, 'solved', $email, 'user', [
            'solved_at'     => $solved_at,
            'time_to_solve' => $time_to_solve,
        ] );
    }

    /**
     * Log alert updated.
     */
    public function log_alert_updated( $alert_id, $email ) {
        $post = get_post( $alert_id );
        $this->log( $alert_id, 'updated', $email, 'user', [
            'title'    => $post->post_title,
            'end_date' => get_post_meta( $alert_id, '_tobalt_alert_end_date', true ),
        ] );
    }

    /**
     * Get creation log entry for an alert.
     */
    public function get_creation_log( $alert_id ) {
        global $wpdb;

        return $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE alert_id = %d AND action = 'created' ORDER BY id ASC LIMIT 1",
            $alert_id
        ) );
    }

    /**
     * Get all logs for an alert.
     */
    public function get_alert_logs( $alert_id ) {
        global $wpdb;

        return $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE alert_id = %d ORDER BY created_at DESC",
            $alert_id
        ) );
    }

    /**
     * Get logs with filters.
     *
     * @param array $args Filter arguments.
     * @return array
     */
    public function get_logs( $args = [] ) {
        global $wpdb;

        $defaults = [
            'action'     => '',
            'email'      => '',
            'date_from'  => '',
            'date_to'    => '',
            'per_page'   => 50,
            'page'       => 1,
            'orderby'    => 'created_at',
            'order'      => 'DESC',
        ];

        $args   = wp_parse_args( $args, $defaults );
        $where  = [ '1=1' ];
        $values = [];

        if ( ! empty( $args['action'] ) ) {
            $where[]  = 'action = %s';
            $values[] = $args['action'];
        }

        if ( ! empty( $args['email'] ) ) {
            $where[]  = 'actor_email = %s';
            $values[] = $args['email'];
        }

        if ( ! empty( $args['date_from'] ) ) {
            $where[]  = 'created_at >= %s';
            $values[] = $args['date_from'] . ' 00:00:00';
        }

        if ( ! empty( $args['date_to'] ) ) {
            $where[]  = 'created_at <= %s';
            $values[] = $args['date_to'] . ' 23:59:59';
        }

        $where_sql = implode( ' AND ', $where );
        $orderby   = in_array( $args['orderby'], [ 'created_at', 'action', 'alert_id' ], true ) ? $args['orderby'] : 'created_at';
        $order     = 'ASC' === strtoupper( $args['order'] ) ? 'ASC' : 'DESC';
        $offset    = ( $args['page'] - 1 ) * $args['per_page'];

        $sql = "SELECT SQL_CALC_FOUND_ROWS * FROM {$this->table_name} WHERE {$where_sql} ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d";
        $values[] = $args['per_page'];
        $values[] = $offset;

        $results = $wpdb->get_results( $wpdb->prepare( $sql, $values ) );
        $total   = $wpdb->get_var( 'SELECT FOUND_ROWS()' );

        return [
            'logs'       => $results,
            'total'      => (int) $total,
            'pages'      => ceil( $total / $args['per_page'] ),
            'page'       => $args['page'],
            'per_page'   => $args['per_page'],
        ];
    }

    /**
     * Get statistics.
     */
    public function get_stats() {
        global $wpdb;

        $stats = [];

        // Total alerts created
        $stats['total_created'] = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$this->table_name} WHERE action = 'created'"
        );

        // Total solved
        $stats['total_solved'] = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$this->table_name} WHERE action = 'solved'"
        );

        // Total expired (auto-archived)
        $stats['total_expired'] = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$this->table_name} WHERE action = 'expired'"
        );

        // Average time to solve (in seconds)
        $avg_time = $wpdb->get_var(
            "SELECT AVG(JSON_EXTRACT(details, '$.time_to_solve')) FROM {$this->table_name} WHERE action = 'solved' AND JSON_EXTRACT(details, '$.time_to_solve') IS NOT NULL"
        );
        $stats['avg_time_to_solve'] = $avg_time ? (int) $avg_time : null;

        // Top submitters
        $stats['top_submitters'] = $wpdb->get_results(
            "SELECT actor_email, COUNT(*) as count FROM {$this->table_name} WHERE action = 'created' AND actor_email != '' GROUP BY actor_email ORDER BY count DESC LIMIT 10"
        );

        return $stats;
    }

    /**
     * Format time duration.
     */
    public static function format_duration( $seconds ) {
        if ( ! $seconds ) {
            return '—';
        }

        $days    = floor( $seconds / 86400 );
        $hours   = floor( ( $seconds % 86400 ) / 3600 );
        $minutes = floor( ( $seconds % 3600 ) / 60 );

        $parts = [];
        if ( $days > 0 ) {
            $parts[] = sprintf( _n( '%d diena', '%d dienos', $days, 'tobalt-city-alerts' ), $days );
        }
        if ( $hours > 0 ) {
            $parts[] = sprintf( _n( '%d valanda', '%d valandos', $hours, 'tobalt-city-alerts' ), $hours );
        }
        if ( $minutes > 0 && $days === 0 ) {
            $parts[] = sprintf( _n( '%d minutė', '%d minutės', $minutes, 'tobalt-city-alerts' ), $minutes );
        }

        return ! empty( $parts ) ? implode( ' ', $parts ) : __( 'Mažiau nei minutė', 'tobalt-city-alerts' );
    }
}
