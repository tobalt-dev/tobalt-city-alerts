<?php
/**
 * CSV Export functionality.
 *
 * Author: Tobalt — https://tobalt.lt
 */

namespace Tobalt\CityAlerts;

defined( 'ABSPATH' ) || exit;

class CSV_Export {

    public function __construct() {
        add_action( 'admin_menu', [ $this, 'add_menu' ] );
        add_action( 'admin_post_tobalt_export_alerts', [ $this, 'handle_export' ] );
    }

    /**
     * Add export submenu.
     */
    public function add_menu() {
        add_submenu_page(
            'edit.php?post_type=tobalt_alert',
            __( 'Export Alerts', 'tobalt-city-alerts' ),
            __( 'Export', 'tobalt-city-alerts' ),
            'manage_options',
            'tobalt-export-alerts',
            [ $this, 'render_page' ]
        );
    }

    /**
     * Render export page.
     */
    public function render_page() {
        $categories = get_terms( [
            'taxonomy'   => 'tobalt_alert_category',
            'hide_empty' => false,
        ] );
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Export Alerts', 'tobalt-city-alerts' ); ?></h1>

            <div class="card" style="max-width:500px;padding:20px;">
                <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                    <input type="hidden" name="action" value="tobalt_export_alerts">
                    <?php wp_nonce_field( 'tobalt_export_alerts' ); ?>

                    <h3><?php esc_html_e( 'Export Options', 'tobalt-city-alerts' ); ?></h3>

                    <p>
                        <label for="date_from"><strong><?php esc_html_e( 'From Date', 'tobalt-city-alerts' ); ?></strong></label><br>
                        <input type="date" id="date_from" name="date_from" value="<?php echo esc_attr( date( 'Y-m-01' ) ); ?>">
                    </p>

                    <p>
                        <label for="date_to"><strong><?php esc_html_e( 'To Date', 'tobalt-city-alerts' ); ?></strong></label><br>
                        <input type="date" id="date_to" name="date_to" value="<?php echo esc_attr( date( 'Y-m-d' ) ); ?>">
                    </p>

                    <p>
                        <label for="status"><strong><?php esc_html_e( 'Status', 'tobalt-city-alerts' ); ?></strong></label><br>
                        <select id="status" name="status">
                            <option value=""><?php esc_html_e( 'All', 'tobalt-city-alerts' ); ?></option>
                            <option value="publish"><?php esc_html_e( 'Published', 'tobalt-city-alerts' ); ?></option>
                            <option value="pending"><?php esc_html_e( 'Pending', 'tobalt-city-alerts' ); ?></option>
                            <option value="draft"><?php esc_html_e( 'Draft', 'tobalt-city-alerts' ); ?></option>
                        </select>
                    </p>

                    <?php if ( ! is_wp_error( $categories ) && ! empty( $categories ) ) : ?>
                    <p>
                        <label for="category"><strong><?php esc_html_e( 'Category', 'tobalt-city-alerts' ); ?></strong></label><br>
                        <select id="category" name="category">
                            <option value=""><?php esc_html_e( 'All', 'tobalt-city-alerts' ); ?></option>
                            <?php foreach ( $categories as $cat ) : ?>
                                <option value="<?php echo esc_attr( $cat->term_id ); ?>"><?php echo esc_html( $cat->name ); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </p>
                    <?php endif; ?>

                    <?php submit_button( __( 'Export CSV', 'tobalt-city-alerts' ), 'primary', 'submit', true ); ?>
                </form>
            </div>
        </div>
        <?php
    }

    /**
     * Handle export request.
     */
    public function handle_export() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( __( 'Unauthorized', 'tobalt-city-alerts' ) );
        }

        check_admin_referer( 'tobalt_export_alerts' );

        $date_from = sanitize_text_field( $_POST['date_from'] ?? '' );
        $date_to   = sanitize_text_field( $_POST['date_to'] ?? '' );
        $status    = sanitize_key( $_POST['status'] ?? '' );
        $category  = absint( $_POST['category'] ?? 0 );

        // Build query
        $args = [
            'post_type'      => 'tobalt_alert',
            'posts_per_page' => -1,
            'post_status'    => $status ?: [ 'publish', 'pending', 'draft' ],
            'orderby'        => 'meta_value',
            'meta_key'       => '_tobalt_alert_date',
            'order'          => 'ASC',
        ];

        // Date range filter
        if ( $date_from && $date_to ) {
            $args['meta_query'] = [
                'relation' => 'AND',
                [
                    'key'     => '_tobalt_alert_date',
                    'value'   => $date_from,
                    'compare' => '>=',
                    'type'    => 'DATE',
                ],
                [
                    'key'     => '_tobalt_alert_date',
                    'value'   => $date_to,
                    'compare' => '<=',
                    'type'    => 'DATE',
                ],
            ];
        }

        // Category filter
        if ( $category ) {
            $args['tax_query'][] = [
                'taxonomy' => 'tobalt_alert_category',
                'field'    => 'term_id',
                'terms'    => $category,
            ];
        }

        $query = new \WP_Query( $args );

        // Generate CSV
        $filename = 'city-alerts-export-' . date( 'Y-m-d' ) . '.csv';

        header( 'Content-Type: text/csv; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename=' . $filename );
        header( 'Pragma: no-cache' );
        header( 'Expires: 0' );

        $output = fopen( 'php://output', 'w' );

        // BOM for Excel UTF-8 compatibility
        fprintf( $output, chr( 0xEF ) . chr( 0xBB ) . chr( 0xBF ) );

        // Header row
        fputcsv( $output, [
            __( 'ID', 'tobalt-city-alerts' ),
            __( 'Title', 'tobalt-city-alerts' ),
            __( 'Description', 'tobalt-city-alerts' ),
            __( 'Alert Date', 'tobalt-city-alerts' ),
            __( 'Time', 'tobalt-city-alerts' ),
            __( 'End Date', 'tobalt-city-alerts' ),
            __( 'Severity', 'tobalt-city-alerts' ),
            __( 'Location', 'tobalt-city-alerts' ),
            __( 'Category', 'tobalt-city-alerts' ),
            __( 'Pinned', 'tobalt-city-alerts' ),
            __( 'Status', 'tobalt-city-alerts' ),
            __( 'Submitted By', 'tobalt-city-alerts' ),
            __( 'Created', 'tobalt-city-alerts' ),
        ] );

        // Data rows
        foreach ( $query->posts as $post ) {
            $categories = wp_get_post_terms( $post->ID, 'tobalt_alert_category', [ 'fields' => 'names' ] );

            fputcsv( $output, [
                $post->ID,
                $post->post_title,
                wp_strip_all_tags( $post->post_content ),
                get_post_meta( $post->ID, '_tobalt_alert_date', true ),
                get_post_meta( $post->ID, '_tobalt_alert_time', true ),
                get_post_meta( $post->ID, '_tobalt_alert_end_date', true ),
                get_post_meta( $post->ID, '_tobalt_alert_severity', true ),
                get_post_meta( $post->ID, '_tobalt_alert_location', true ),
                is_array( $categories ) ? implode( ', ', $categories ) : '',
                get_post_meta( $post->ID, '_tobalt_alert_pinned', true ) ? __( 'Yes', 'tobalt-city-alerts' ) : __( 'No', 'tobalt-city-alerts' ),
                $post->post_status,
                get_post_meta( $post->ID, '_tobalt_alert_submitted_by', true ),
                $post->post_date,
            ] );
        }

        fclose( $output );
        exit;
    }
}
