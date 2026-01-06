<?php
/**
 * Admin Activity Log page.
 *
 * Author: Tobalt — https://tobalt.lt
 */

namespace Tobalt\CityAlerts;

defined( 'ABSPATH' ) || exit;

class Admin_Activity_Log {

    public function __construct() {
        add_action( 'admin_menu', [ $this, 'add_menu' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
    }

    /**
     * Add submenu page.
     */
    public function add_menu() {
        add_submenu_page(
            'edit.php?post_type=tobalt_alert',
            __( 'Activity Log', 'tobalt-city-alerts' ),
            __( 'Activity Log', 'tobalt-city-alerts' ),
            'manage_options',
            'tobalt-activity-log',
            [ $this, 'render_page' ]
        );
    }

    /**
     * Enqueue scripts.
     */
    public function enqueue_scripts( $hook ) {
        if ( 'tobalt_alert_page_tobalt-activity-log' !== $hook ) {
            return;
        }

        wp_enqueue_style(
            'tobalt-admin-activity-log',
            TOBALT_CITY_ALERTS_URL . 'assets/css/admin-activity-log.css',
            [],
            TOBALT_CITY_ALERTS_VERSION
        );
    }

    /**
     * Render page.
     */
    public function render_page() {
        $activity_log = new Activity_Log();

        // Get filters (verify nonce if filter submitted)
        $action    = '';
        $email     = '';
        $date_from = '';
        $date_to   = '';

        if ( isset( $_GET['filter_action'] ) || isset( $_GET['filter_email'] ) || isset( $_GET['date_from'] ) || isset( $_GET['date_to'] ) ) {
            if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'tobalt_activity_log_filter' ) ) {
                // Invalid nonce - ignore filters
                add_settings_error( 'tobalt_activity_log', 'invalid_nonce', __( 'Security check failed. Please try again.', 'tobalt-city-alerts' ), 'error' );
            } else {
                $action    = isset( $_GET['filter_action'] ) ? sanitize_text_field( $_GET['filter_action'] ) : '';
                $email     = isset( $_GET['filter_email'] ) ? sanitize_email( $_GET['filter_email'] ) : '';
                $date_from = isset( $_GET['date_from'] ) ? sanitize_text_field( $_GET['date_from'] ) : '';
                $date_to   = isset( $_GET['date_to'] ) ? sanitize_text_field( $_GET['date_to'] ) : '';
            }
        }

        $page = isset( $_GET['paged'] ) ? max( 1, (int) $_GET['paged'] ) : 1;

        // Get logs
        $result = $activity_log->get_logs( [
            'action'    => $action,
            'email'     => $email,
            'date_from' => $date_from,
            'date_to'   => $date_to,
            'page'      => $page,
            'per_page'  => 30,
        ] );

        // Get stats
        $stats = $activity_log->get_stats();

        // Action labels
        $action_labels = [
            'created'   => __( 'Sukurta', 'tobalt-city-alerts' ),
            'published' => __( 'Publikuota', 'tobalt-city-alerts' ),
            'updated'   => __( 'Atnaujinta', 'tobalt-city-alerts' ),
            'solved'    => __( 'Išspręsta', 'tobalt-city-alerts' ),
            'expired'   => __( 'Pasibaigė', 'tobalt-city-alerts' ),
        ];

        ?>
        <div class="wrap tobalt-activity-log">
            <h1><?php esc_html_e( 'Activity Log', 'tobalt-city-alerts' ); ?></h1>

            <!-- Stats Cards -->
            <div class="tobalt-stats-cards">
                <div class="tobalt-stat-card">
                    <span class="tobalt-stat-value"><?php echo esc_html( $stats['total_created'] ); ?></span>
                    <span class="tobalt-stat-label"><?php esc_html_e( 'Sukurta pranešimų', 'tobalt-city-alerts' ); ?></span>
                </div>
                <div class="tobalt-stat-card tobalt-stat-success">
                    <span class="tobalt-stat-value"><?php echo esc_html( $stats['total_solved'] ); ?></span>
                    <span class="tobalt-stat-label"><?php esc_html_e( 'Išspręsta', 'tobalt-city-alerts' ); ?></span>
                </div>
                <div class="tobalt-stat-card tobalt-stat-warning">
                    <span class="tobalt-stat-value"><?php echo esc_html( $stats['total_expired'] ); ?></span>
                    <span class="tobalt-stat-label"><?php esc_html_e( 'Pasibaigė automatiškai', 'tobalt-city-alerts' ); ?></span>
                </div>
                <div class="tobalt-stat-card tobalt-stat-info">
                    <span class="tobalt-stat-value"><?php echo esc_html( Activity_Log::format_duration( $stats['avg_time_to_solve'] ) ); ?></span>
                    <span class="tobalt-stat-label"><?php esc_html_e( 'Vid. sprendimo laikas', 'tobalt-city-alerts' ); ?></span>
                </div>
            </div>

            <!-- Filters -->
            <form method="get" class="tobalt-log-filters">
                <input type="hidden" name="post_type" value="tobalt_alert">
                <input type="hidden" name="page" value="tobalt-activity-log">
                <?php wp_nonce_field( 'tobalt_activity_log_filter', '_wpnonce', false ); ?>

                <select name="filter_action">
                    <option value=""><?php esc_html_e( 'Visi veiksmai', 'tobalt-city-alerts' ); ?></option>
                    <?php foreach ( $action_labels as $key => $label ) : ?>
                        <option value="<?php echo esc_attr( $key ); ?>" <?php selected( $action, $key ); ?>><?php echo esc_html( $label ); ?></option>
                    <?php endforeach; ?>
                </select>

                <input type="email" name="filter_email" value="<?php echo esc_attr( $email ); ?>" placeholder="<?php esc_attr_e( 'El. paštas', 'tobalt-city-alerts' ); ?>">

                <input type="date" name="date_from" value="<?php echo esc_attr( $date_from ); ?>" placeholder="<?php esc_attr_e( 'Nuo', 'tobalt-city-alerts' ); ?>">
                <input type="date" name="date_to" value="<?php echo esc_attr( $date_to ); ?>" placeholder="<?php esc_attr_e( 'Iki', 'tobalt-city-alerts' ); ?>">

                <button type="submit" class="button"><?php esc_html_e( 'Filtruoti', 'tobalt-city-alerts' ); ?></button>
                <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=tobalt_alert&page=tobalt-activity-log' ) ); ?>" class="button"><?php esc_html_e( 'Išvalyti', 'tobalt-city-alerts' ); ?></a>
            </form>

            <!-- Log Table -->
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th style="width:160px;"><?php esc_html_e( 'Data', 'tobalt-city-alerts' ); ?></th>
                        <th style="width:100px;"><?php esc_html_e( 'Veiksmas', 'tobalt-city-alerts' ); ?></th>
                        <th><?php esc_html_e( 'Pranešimas', 'tobalt-city-alerts' ); ?></th>
                        <th style="width:200px;"><?php esc_html_e( 'Atlikėjas', 'tobalt-city-alerts' ); ?></th>
                        <th style="width:180px;"><?php esc_html_e( 'Detalės', 'tobalt-city-alerts' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ( empty( $result['logs'] ) ) : ?>
                        <tr>
                            <td colspan="5" style="text-align:center;"><?php esc_html_e( 'Įrašų nerasta.', 'tobalt-city-alerts' ); ?></td>
                        </tr>
                    <?php else : ?>
                        <?php foreach ( $result['logs'] as $log ) : ?>
                            <?php
                            $post    = get_post( $log->alert_id );
                            $details = json_decode( $log->details, true ) ?: [];
                            ?>
                            <tr>
                                <td>
                                    <?php echo esc_html( date_i18n( 'Y-m-d H:i', strtotime( $log->created_at ) ) ); ?>
                                </td>
                                <td>
                                    <span class="tobalt-action-badge tobalt-action-<?php echo esc_attr( $log->action ); ?>">
                                        <?php echo esc_html( $action_labels[ $log->action ] ?? $log->action ); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ( $post ) : ?>
                                        <a href="<?php echo esc_url( get_edit_post_link( $post->ID ) ); ?>">
                                            <?php echo esc_html( $post->post_title ); ?>
                                        </a>
                                        <span class="tobalt-alert-id">#<?php echo esc_html( $post->ID ); ?></span>
                                    <?php else : ?>
                                        <span class="tobalt-deleted"><?php esc_html_e( 'Ištrintas', 'tobalt-city-alerts' ); ?></span>
                                        <span class="tobalt-alert-id">#<?php echo esc_html( $log->alert_id ); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ( $log->actor_email ) : ?>
                                        <?php echo esc_html( $log->actor_email ); ?>
                                    <?php elseif ( 'system' === $log->actor_type ) : ?>
                                        <em><?php esc_html_e( 'Sistema', 'tobalt-city-alerts' ); ?></em>
                                    <?php else : ?>
                                        —
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ( 'solved' === $log->action && ! empty( $details['time_to_solve'] ) ) : ?>
                                        <span class="tobalt-time-to-solve">
                                            <?php echo esc_html( Activity_Log::format_duration( $details['time_to_solve'] ) ); ?>
                                        </span>
                                    <?php elseif ( ! empty( $details['end_date'] ) ) : ?>
                                        <?php esc_html_e( 'Pabaiga:', 'tobalt-city-alerts' ); ?> <?php echo esc_html( $details['end_date'] ); ?>
                                    <?php else : ?>
                                        —
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

            <!-- Pagination -->
            <?php if ( $result['pages'] > 1 ) : ?>
                <div class="tablenav bottom">
                    <div class="tablenav-pages">
                        <span class="displaying-num">
                            <?php printf( esc_html__( '%s įrašų', 'tobalt-city-alerts' ), number_format_i18n( $result['total'] ) ); ?>
                        </span>
                        <span class="pagination-links">
                            <?php
                            $base_url = admin_url( 'edit.php?post_type=tobalt_alert&page=tobalt-activity-log' );
                            if ( $action ) $base_url = add_query_arg( 'filter_action', $action, $base_url );
                            if ( $email ) $base_url = add_query_arg( 'filter_email', $email, $base_url );
                            if ( $date_from ) $base_url = add_query_arg( 'date_from', $date_from, $base_url );
                            if ( $date_to ) $base_url = add_query_arg( 'date_to', $date_to, $base_url );

                            if ( $page > 1 ) :
                                ?>
                                <a class="prev-page button" href="<?php echo esc_url( add_query_arg( 'paged', $page - 1, $base_url ) ); ?>">‹</a>
                            <?php endif; ?>

                            <span class="paging-input">
                                <?php echo esc_html( $page ); ?> / <?php echo esc_html( $result['pages'] ); ?>
                            </span>

                            <?php if ( $page < $result['pages'] ) : ?>
                                <a class="next-page button" href="<?php echo esc_url( add_query_arg( 'paged', $page + 1, $base_url ) ); ?>">›</a>
                            <?php endif; ?>
                        </span>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Top Submitters -->
            <?php if ( ! empty( $stats['top_submitters'] ) ) : ?>
                <h2 style="margin-top:30px;"><?php esc_html_e( 'Aktyviausi pateikėjai', 'tobalt-city-alerts' ); ?></h2>
                <table class="wp-list-table widefat fixed striped" style="max-width:400px;">
                    <thead>
                        <tr>
                            <th><?php esc_html_e( 'El. paštas', 'tobalt-city-alerts' ); ?></th>
                            <th style="width:80px;text-align:center;"><?php esc_html_e( 'Kiekis', 'tobalt-city-alerts' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $stats['top_submitters'] as $submitter ) : ?>
                            <tr>
                                <td><?php echo esc_html( $submitter->actor_email ); ?></td>
                                <td style="text-align:center;"><?php echo esc_html( $submitter->count ); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        <?php
    }
}
