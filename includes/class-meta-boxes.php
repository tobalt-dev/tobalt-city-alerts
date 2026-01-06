<?php
/**
 * Meta boxes for Alert CPT.
 *
 * Author: Tobalt — https://tobalt.lt
 */

namespace Tobalt\CityAlerts;

defined( 'ABSPATH' ) || exit;

class Meta_Boxes {

    public function __construct() {
        add_action( 'add_meta_boxes', [ $this, 'add_meta_boxes' ] );
        add_action( 'save_post_tobalt_alert', [ $this, 'save_meta' ], 10, 2 );
        add_filter( 'manage_tobalt_alert_posts_columns', [ $this, 'add_columns' ] );
        add_action( 'manage_tobalt_alert_posts_custom_column', [ $this, 'render_columns' ], 10, 2 );
        add_filter( 'manage_edit-tobalt_alert_sortable_columns', [ $this, 'sortable_columns' ] );
        add_action( 'pre_get_posts', [ $this, 'sort_by_alert_date' ] );
    }

    /**
     * Add meta boxes.
     */
    public function add_meta_boxes() {
        add_meta_box(
            'tobalt_alert_details',
            __( 'Pranešimo informacija', 'tobalt-city-alerts' ),
            [ $this, 'render_details_box' ],
            'tobalt_alert',
            'side',
            'high'
        );

        add_meta_box(
            'tobalt_alert_info',
            __( 'Pateikimo informacija', 'tobalt-city-alerts' ),
            [ $this, 'render_info_box' ],
            'tobalt_alert',
            'side',
            'default'
        );
    }

    /**
     * Render alert details meta box.
     */
    public function render_details_box( $post ) {
        wp_nonce_field( 'tobalt_alert_meta', 'tobalt_alert_nonce' );

        $date     = get_post_meta( $post->ID, '_tobalt_alert_date', true );
        $time     = get_post_meta( $post->ID, '_tobalt_alert_time', true );
        $end_date = get_post_meta( $post->ID, '_tobalt_alert_end_date', true );
        $severity = get_post_meta( $post->ID, '_tobalt_alert_severity', true );
        $location = get_post_meta( $post->ID, '_tobalt_alert_location', true );
        $pinned   = get_post_meta( $post->ID, '_tobalt_alert_pinned', true );

        if ( empty( $date ) ) {
            $date = current_time( 'Y-m-d' );
        }
        ?>
        <p>
            <label for="tobalt_alert_date"><strong><?php esc_html_e( 'Pradžios data', 'tobalt-city-alerts' ); ?></strong></label><br>
            <input type="date" id="tobalt_alert_date" name="tobalt_alert_date" value="<?php echo esc_attr( $date ); ?>" class="widefat" required>
        </p>
        <p>
            <label for="tobalt_alert_time"><strong><?php esc_html_e( 'Laikas (neprivaloma)', 'tobalt-city-alerts' ); ?></strong></label><br>
            <input type="time" id="tobalt_alert_time" name="tobalt_alert_time" value="<?php echo esc_attr( $time ); ?>" class="widefat">
        </p>
        <p>
            <label for="tobalt_alert_end_date"><strong><?php esc_html_e( 'Pabaigos data (neprivaloma)', 'tobalt-city-alerts' ); ?></strong></label><br>
            <input type="date" id="tobalt_alert_end_date" name="tobalt_alert_end_date" value="<?php echo esc_attr( $end_date ); ?>" class="widefat">
            <span class="description"><?php esc_html_e( 'Pranešimas automatiškai pasibaigia po šios datos', 'tobalt-city-alerts' ); ?></span>
        </p>
        <?php
        $scheduled_publish = get_post_meta( $post->ID, '_tobalt_alert_scheduled_publish', true );
        ?>
        <p>
            <label for="tobalt_alert_scheduled_publish"><strong><?php esc_html_e( 'Suplanuotas publikavimas', 'tobalt-city-alerts' ); ?></strong></label><br>
            <input type="datetime-local" id="tobalt_alert_scheduled_publish" name="tobalt_alert_scheduled_publish" value="<?php echo esc_attr( $scheduled_publish ? date( 'Y-m-d\TH:i', strtotime( $scheduled_publish ) ) : '' ); ?>" class="widefat">
            <span class="description"><?php esc_html_e( 'Palikite tuščią, jei norite publikuoti iš karto', 'tobalt-city-alerts' ); ?></span>
        </p>
        <p>
            <label for="tobalt_alert_severity"><strong><?php esc_html_e( 'Svarba', 'tobalt-city-alerts' ); ?></strong></label><br>
            <select id="tobalt_alert_severity" name="tobalt_alert_severity" class="widefat">
                <option value=""><?php esc_html_e( '— Nenurodyta —', 'tobalt-city-alerts' ); ?></option>
                <option value="low" <?php selected( $severity, 'low' ); ?>><?php esc_html_e( 'Žema', 'tobalt-city-alerts' ); ?></option>
                <option value="medium" <?php selected( $severity, 'medium' ); ?>><?php esc_html_e( 'Vidutinė', 'tobalt-city-alerts' ); ?></option>
                <option value="high" <?php selected( $severity, 'high' ); ?>><?php esc_html_e( 'Aukšta', 'tobalt-city-alerts' ); ?></option>
                <option value="critical" <?php selected( $severity, 'critical' ); ?>><?php esc_html_e( 'Kritinė', 'tobalt-city-alerts' ); ?></option>
            </select>
        </p>
        <p>
            <label for="tobalt_alert_location"><strong><?php esc_html_e( 'Vieta', 'tobalt-city-alerts' ); ?></strong></label><br>
            <input type="text" id="tobalt_alert_location" name="tobalt_alert_location" value="<?php echo esc_attr( $location ); ?>" class="widefat" placeholder="<?php esc_attr_e( 'pvz., Vilniaus g. 15', 'tobalt-city-alerts' ); ?>">
        </p>
        <p>
            <label>
                <input type="checkbox" name="tobalt_alert_pinned" value="1" <?php checked( $pinned, '1' ); ?>>
                <strong><?php esc_html_e( 'Prisegti šį pranešimą', 'tobalt-city-alerts' ); ?></strong>
            </label><br>
            <span class="description"><?php esc_html_e( 'Prisegti pranešimai rodomi viršuje', 'tobalt-city-alerts' ); ?></span>
        </p>
        <?php
    }

    /**
     * Render submission info meta box.
     */
    public function render_info_box( $post ) {
        $submitted_by = get_post_meta( $post->ID, '_tobalt_alert_submitted_by', true );

        if ( $submitted_by ) {
            echo '<p><strong>' . esc_html__( 'Pateikė:', 'tobalt-city-alerts' ) . '</strong><br>';
            echo esc_html( $submitted_by ) . '</p>';
        } else {
            echo '<p class="description">' . esc_html__( 'Sukurta administratoriaus', 'tobalt-city-alerts' ) . '</p>';
        }
    }

    /**
     * Save meta data.
     */
    public function save_meta( $post_id, $post ) {
        if ( ! isset( $_POST['tobalt_alert_nonce'] ) || ! wp_verify_nonce( $_POST['tobalt_alert_nonce'], 'tobalt_alert_meta' ) ) {
            return;
        }

        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        // Date (required)
        if ( isset( $_POST['tobalt_alert_date'] ) ) {
            $date = sanitize_text_field( $_POST['tobalt_alert_date'] );
            if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
                update_post_meta( $post_id, '_tobalt_alert_date', $date );
            }
        }

        // Time
        if ( isset( $_POST['tobalt_alert_time'] ) ) {
            $time = sanitize_text_field( $_POST['tobalt_alert_time'] );
            if ( empty( $time ) || preg_match( '/^\d{2}:\d{2}$/', $time ) ) {
                update_post_meta( $post_id, '_tobalt_alert_time', $time );
            }
        }

        // End date
        if ( isset( $_POST['tobalt_alert_end_date'] ) ) {
            $end_date = sanitize_text_field( $_POST['tobalt_alert_end_date'] );
            if ( empty( $end_date ) || preg_match( '/^\d{4}-\d{2}-\d{2}$/', $end_date ) ) {
                update_post_meta( $post_id, '_tobalt_alert_end_date', $end_date );
            }
        }

        // Scheduled publish
        if ( isset( $_POST['tobalt_alert_scheduled_publish'] ) ) {
            $scheduled = sanitize_text_field( $_POST['tobalt_alert_scheduled_publish'] );
            if ( empty( $scheduled ) ) {
                delete_post_meta( $post_id, '_tobalt_alert_scheduled_publish' );
            } else {
                // Convert datetime-local format to MySQL datetime
                $datetime = date( 'Y-m-d H:i:s', strtotime( $scheduled ) );
                update_post_meta( $post_id, '_tobalt_alert_scheduled_publish', $datetime );

                // If scheduled in future and status is publish, set to draft
                if ( strtotime( $datetime ) > time() && 'publish' === $post->post_status ) {
                    remove_action( 'save_post_tobalt_alert', [ $this, 'save_meta' ], 10 );
                    wp_update_post( [
                        'ID'          => $post_id,
                        'post_status' => 'draft',
                    ] );
                    add_action( 'save_post_tobalt_alert', [ $this, 'save_meta' ], 10, 2 );
                }
            }
        }

        // Severity
        $severity = isset( $_POST['tobalt_alert_severity'] ) ? sanitize_key( $_POST['tobalt_alert_severity'] ) : '';
        if ( in_array( $severity, [ '', 'low', 'medium', 'high', 'critical' ], true ) ) {
            update_post_meta( $post_id, '_tobalt_alert_severity', $severity );
        }

        // Location
        if ( isset( $_POST['tobalt_alert_location'] ) ) {
            update_post_meta( $post_id, '_tobalt_alert_location', sanitize_text_field( $_POST['tobalt_alert_location'] ) );
        }

        // Pinned
        $pinned = isset( $_POST['tobalt_alert_pinned'] ) ? '1' : '';
        update_post_meta( $post_id, '_tobalt_alert_pinned', $pinned );
    }

    /**
     * Add custom columns to alerts list.
     */
    public function add_columns( $columns ) {
        $new_columns = [];

        foreach ( $columns as $key => $value ) {
            $new_columns[ $key ] = $value;

            if ( 'title' === $key ) {
                $new_columns['alert_date'] = __( 'Pradžios data', 'tobalt-city-alerts' );
                $new_columns['severity']   = __( 'Svarba', 'tobalt-city-alerts' );
                $new_columns['pinned']     = __( 'Prisegtas', 'tobalt-city-alerts' );
            }
        }

        return $new_columns;
    }

    /**
     * Render custom columns.
     */
    public function render_columns( $column, $post_id ) {
        switch ( $column ) {
            case 'alert_date':
                $date = get_post_meta( $post_id, '_tobalt_alert_date', true );
                $time = get_post_meta( $post_id, '_tobalt_alert_time', true );
                echo $date ? esc_html( $date . ( $time ? ' ' . $time : '' ) ) : '—';
                break;

            case 'severity':
                $severity = get_post_meta( $post_id, '_tobalt_alert_severity', true );
                if ( $severity ) {
                    $colors = [
                        'low'      => '#4caf50',
                        'medium'   => '#ff9800',
                        'high'     => '#f44336',
                        'critical' => '#9c27b0',
                    ];
                    $labels = [
                        'low'      => __( 'Žema', 'tobalt-city-alerts' ),
                        'medium'   => __( 'Vidutinė', 'tobalt-city-alerts' ),
                        'high'     => __( 'Aukšta', 'tobalt-city-alerts' ),
                        'critical' => __( 'Kritinė', 'tobalt-city-alerts' ),
                    ];
                    $color = $colors[ $severity ] ?? '#999';
                    $label = $labels[ $severity ] ?? ucfirst( $severity );
                    echo '<span style="display:inline-block;background:' . esc_attr( $color ) . ';color:#fff;padding:2px 8px;border-radius:3px;font-size:11px;">' . esc_html( $label ) . '</span>';
                } else {
                    echo '—';
                }
                break;

            case 'pinned':
                $pinned = get_post_meta( $post_id, '_tobalt_alert_pinned', true );
                echo $pinned ? '<span class="dashicons dashicons-admin-post" title="' . esc_attr__( 'Prisegtas', 'tobalt-city-alerts' ) . '"></span>' : '—';
                break;
        }
    }

    /**
     * Make columns sortable.
     */
    public function sortable_columns( $columns ) {
        $columns['alert_date'] = 'alert_date';
        return $columns;
    }

    /**
     * Sort by alert date.
     */
    public function sort_by_alert_date( $query ) {
        if ( ! is_admin() || ! $query->is_main_query() ) {
            return;
        }

        if ( 'tobalt_alert' !== $query->get( 'post_type' ) ) {
            return;
        }

        $orderby = $query->get( 'orderby' );

        if ( 'alert_date' === $orderby ) {
            $query->set( 'meta_key', '_tobalt_alert_date' );
            $query->set( 'orderby', 'meta_value' );
        }
    }
}
