<?php
/**
 * Subscribers management.
 *
 * Author: Tobalt — https://tobalt.lt
 */

namespace Tobalt\CityAlerts;

defined( 'ABSPATH' ) || exit;

class Subscribers {

    private $table_name;
    private static $hooks_added = false;

    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'tobalt_subscribers';

        // Only add hooks once
        if ( ! self::$hooks_added ) {
            add_action( 'admin_menu', [ $this, 'add_menu' ] );
            add_action( 'admin_post_tobalt_delete_subscriber', [ $this, 'handle_delete' ] );
            add_action( 'admin_post_tobalt_export_subscribers', [ $this, 'handle_export' ] );
            self::$hooks_added = true;
        }
    }

    /**
     * Create subscribers table (called from activator).
     */
    public static function create_table() {
        global $wpdb;

        $table_name      = $wpdb->prefix . 'tobalt_subscribers';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$table_name} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            email VARCHAR(255) NOT NULL,
            categories TEXT DEFAULT NULL,
            providers TEXT DEFAULT NULL,
            verified TINYINT(1) DEFAULT 0,
            verify_token VARCHAR(64) DEFAULT NULL,
            unsubscribe_token VARCHAR(64) NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            verified_at DATETIME DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY email (email),
            KEY verify_token (verify_token),
            KEY unsubscribe_token (unsubscribe_token)
        ) {$charset_collate};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );
    }

    /**
     * Add subscriber.
     */
    public function add( $email, $categories = [] ) {
        global $wpdb;

        $email = sanitize_email( $email );

        if ( ! is_email( $email ) ) {
            return new \WP_Error( 'invalid_email', __( 'Invalid email address.', 'tobalt-city-alerts' ) );
        }

        // Check if already exists
        $existing = $this->get_by_email( $email );

        if ( $existing ) {
            // Update existing subscription
            return $this->update( $existing->id, $categories );
        }

        $verify_token      = bin2hex( random_bytes( 32 ) );
        $unsubscribe_token = bin2hex( random_bytes( 32 ) );

        $result = $wpdb->insert(
            $this->table_name,
            [
                'email'             => $email,
                'categories'        => wp_json_encode( array_map( 'absint', $categories ) ),
                'verified'          => 0,
                'verify_token'      => $verify_token,
                'unsubscribe_token' => $unsubscribe_token,
            ],
            [ '%s', '%s', '%d', '%s', '%s' ]
        );

        if ( ! $result ) {
            return new \WP_Error( 'db_error', __( 'Failed to add subscription.', 'tobalt-city-alerts' ) );
        }

        return [
            'id'           => $wpdb->insert_id,
            'verify_token' => $verify_token,
        ];
    }

    /**
     * Update subscriber preferences.
     */
    public function update( $id, $categories = [] ) {
        global $wpdb;

        $subscriber = $this->get( $id );

        if ( ! $subscriber ) {
            return new \WP_Error( 'not_found', __( 'Subscription not found.', 'tobalt-city-alerts' ) );
        }

        // Generate new verify token if not verified
        $verify_token = $subscriber->verified ? null : bin2hex( random_bytes( 32 ) );

        $data = [
            'categories' => wp_json_encode( array_map( 'absint', $categories ) ),
        ];

        if ( $verify_token ) {
            $data['verify_token'] = $verify_token;
            $data['verified']     = 0;
        }

        $wpdb->update(
            $this->table_name,
            $data,
            [ 'id' => $id ]
        );

        return [
            'id'           => $id,
            'verify_token' => $verify_token,
            'was_verified' => (bool) $subscriber->verified,
        ];
    }

    /**
     * Verify subscription.
     */
    public function verify( $token ) {
        global $wpdb;

        $token = sanitize_text_field( $token );

        $subscriber = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE verify_token = %s",
            $token
        ) );

        if ( ! $subscriber ) {
            return new \WP_Error( 'invalid_token', __( 'Invalid verification link.', 'tobalt-city-alerts' ) );
        }

        if ( $subscriber->verified ) {
            return new \WP_Error( 'already_verified', __( 'Subscription already verified.', 'tobalt-city-alerts' ) );
        }

        $wpdb->update(
            $this->table_name,
            [
                'verified'     => 1,
                'verify_token' => null,
                'verified_at'  => current_time( 'mysql', true ),
            ],
            [ 'id' => $subscriber->id ]
        );

        return true;
    }

    /**
     * Unsubscribe by token.
     */
    public function unsubscribe( $token ) {
        global $wpdb;

        $token = sanitize_text_field( $token );

        $result = $wpdb->delete(
            $this->table_name,
            [ 'unsubscribe_token' => $token ],
            [ '%s' ]
        );

        if ( ! $result ) {
            return new \WP_Error( 'invalid_token', __( 'Invalid unsubscribe link.', 'tobalt-city-alerts' ) );
        }

        return true;
    }

    /**
     * Get subscriber by ID.
     */
    public function get( $id ) {
        global $wpdb;

        return $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE id = %d",
            $id
        ) );
    }

    /**
     * Get subscriber by email.
     */
    public function get_by_email( $email ) {
        global $wpdb;

        return $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE email = %s",
            sanitize_email( $email )
        ) );
    }

    /**
     * Get unsubscribe token for email.
     */
    public function get_unsubscribe_token( $email ) {
        $subscriber = $this->get_by_email( $email );

        if ( ! $subscriber ) {
            return null;
        }

        return $subscriber->unsubscribe_token;
    }

    /**
     * Get subscribers matching category.
     */
    public function get_matching( $category_ids = [] ) {
        global $wpdb;

        // Get all verified subscribers
        $subscribers = $wpdb->get_results(
            "SELECT * FROM {$this->table_name} WHERE verified = 1"
        );

        $matching = [];

        foreach ( $subscribers as $sub ) {
            $sub_categories = json_decode( $sub->categories, true ) ?: [];

            // Empty arrays mean "all"
            $matches_category = empty( $sub_categories ) || empty( $category_ids ) || array_intersect( $sub_categories, $category_ids );

            if ( $matches_category ) {
                $matching[] = $sub;
            }
        }

        return $matching;
    }

    /**
     * Get all subscribers with pagination.
     */
    public function get_all( $args = [] ) {
        global $wpdb;

        $defaults = [
            'per_page' => 20,
            'page'     => 1,
            'verified' => null,
            'search'   => '',
        ];

        $args   = wp_parse_args( $args, $defaults );
        $offset = ( $args['page'] - 1 ) * $args['per_page'];

        $where  = '1=1';
        $values = [];

        if ( null !== $args['verified'] ) {
            $where   .= ' AND verified = %d';
            $values[] = $args['verified'] ? 1 : 0;
        }

        if ( ! empty( $args['search'] ) ) {
            $where   .= ' AND email LIKE %s';
            $values[] = '%' . $wpdb->esc_like( $args['search'] ) . '%';
        }

        $values[] = $args['per_page'];
        $values[] = $offset;

        return $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE {$where} ORDER BY created_at DESC LIMIT %d OFFSET %d",
            $values
        ) );
    }

    /**
     * Get total count.
     */
    public function get_count( $verified = null ) {
        global $wpdb;

        if ( null !== $verified ) {
            return (int) $wpdb->get_var( $wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->table_name} WHERE verified = %d",
                $verified ? 1 : 0
            ) );
        }

        return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$this->table_name}" );
    }

    /**
     * Delete subscriber.
     */
    public function delete( $id ) {
        global $wpdb;

        return $wpdb->delete(
            $this->table_name,
            [ 'id' => absint( $id ) ],
            [ '%d' ]
        );
    }

    /**
     * Add admin menu.
     */
    public function add_menu() {
        add_submenu_page(
            'edit.php?post_type=tobalt_alert',
            __( 'Subscribers', 'tobalt-city-alerts' ),
            __( 'Subscribers', 'tobalt-city-alerts' ),
            'manage_options',
            'tobalt-subscribers',
            [ $this, 'render_page' ]
        );
    }

    /**
     * Render admin page.
     */
    public function render_page() {
        $search   = sanitize_text_field( $_GET['s'] ?? '' );
        $page     = max( 1, absint( $_GET['paged'] ?? 1 ) );
        $per_page = 20;

        $subscribers = $this->get_all( [
            'search'   => $search,
            'page'     => $page,
            'per_page' => $per_page,
        ] );

        $total = $this->get_count();
        $pages = ceil( $total / $per_page );

        $verified_count   = $this->get_count( true );
        $unverified_count = $this->get_count( false );

        $message = sanitize_key( $_GET['message'] ?? '' );
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline"><?php esc_html_e( 'Subscribers', 'tobalt-city-alerts' ); ?></h1>

            <a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=tobalt_export_subscribers' ), 'tobalt_export_subscribers' ) ); ?>" class="page-title-action">
                <?php esc_html_e( 'Export CSV', 'tobalt-city-alerts' ); ?>
            </a>

            <?php if ( 'deleted' === $message ) : ?>
                <div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Subscriber deleted.', 'tobalt-city-alerts' ); ?></p></div>
            <?php endif; ?>

            <hr class="wp-header-end">

            <ul class="subsubsub">
                <li>
                    <?php printf( __( 'Total: %s', 'tobalt-city-alerts' ), '<strong>' . number_format_i18n( $total ) . '</strong>' ); ?> |
                </li>
                <li>
                    <?php printf( __( 'Verified: %s', 'tobalt-city-alerts' ), '<strong>' . number_format_i18n( $verified_count ) . '</strong>' ); ?> |
                </li>
                <li>
                    <?php printf( __( 'Pending: %s', 'tobalt-city-alerts' ), '<strong>' . number_format_i18n( $unverified_count ) . '</strong>' ); ?>
                </li>
            </ul>

            <form method="get" style="margin-top:15px;">
                <input type="hidden" name="post_type" value="tobalt_alert">
                <input type="hidden" name="page" value="tobalt-subscribers">
                <p class="search-box">
                    <input type="search" name="s" value="<?php echo esc_attr( $search ); ?>" placeholder="<?php esc_attr_e( 'Search email...', 'tobalt-city-alerts' ); ?>">
                    <?php submit_button( __( 'Search', 'tobalt-city-alerts' ), 'secondary', '', false ); ?>
                </p>
            </form>

            <table class="wp-list-table widefat fixed striped" style="margin-top:10px;">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Email', 'tobalt-city-alerts' ); ?></th>
                        <th><?php esc_html_e( 'Categories', 'tobalt-city-alerts' ); ?></th>
                        <th><?php esc_html_e( 'Status', 'tobalt-city-alerts' ); ?></th>
                        <th><?php esc_html_e( 'Subscribed', 'tobalt-city-alerts' ); ?></th>
                        <th><?php esc_html_e( 'Actions', 'tobalt-city-alerts' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ( empty( $subscribers ) ) : ?>
                        <tr><td colspan="5"><?php esc_html_e( 'No subscribers found.', 'tobalt-city-alerts' ); ?></td></tr>
                    <?php else : ?>
                        <?php foreach ( $subscribers as $sub ) : ?>
                            <?php
                            $categories = json_decode( $sub->categories, true ) ?: [];

                            $cat_names = [];
                            foreach ( $categories as $cat_id ) {
                                $term = get_term( $cat_id, 'tobalt_alert_category' );
                                if ( $term && ! is_wp_error( $term ) ) {
                                    $cat_names[] = $term->name;
                                }
                            }
                            ?>
                            <tr>
                                <td><strong><?php echo esc_html( $sub->email ); ?></strong></td>
                                <td><?php echo $cat_names ? esc_html( implode( ', ', $cat_names ) ) : '<em>' . esc_html__( 'All', 'tobalt-city-alerts' ) . '</em>'; ?></td>
                                <td>
                                    <?php if ( $sub->verified ) : ?>
                                        <span style="color:#46b450;">● <?php esc_html_e( 'Verified', 'tobalt-city-alerts' ); ?></span>
                                    <?php else : ?>
                                        <span style="color:#f0ad4e;">● <?php esc_html_e( 'Pending', 'tobalt-city-alerts' ); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $sub->created_at ) ) ); ?></td>
                                <td>
                                    <a href="<?php echo esc_url( wp_nonce_url(
                                        admin_url( 'admin-post.php?action=tobalt_delete_subscriber&id=' . $sub->id ),
                                        'tobalt_delete_subscriber_' . $sub->id
                                    ) ); ?>" class="delete" onclick="return confirm('<?php esc_attr_e( 'Delete this subscriber?', 'tobalt-city-alerts' ); ?>');">
                                        <?php esc_html_e( 'Delete', 'tobalt-city-alerts' ); ?>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

            <?php if ( $pages > 1 ) : ?>
                <div class="tablenav bottom">
                    <div class="tablenav-pages">
                        <?php
                        echo paginate_links( [
                            'base'    => add_query_arg( 'paged', '%#%' ),
                            'format'  => '',
                            'current' => $page,
                            'total'   => $pages,
                        ] );
                        ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Handle delete subscriber.
     */
    public function handle_delete() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( __( 'Unauthorized', 'tobalt-city-alerts' ) );
        }

        $id = absint( $_GET['id'] ?? 0 );
        check_admin_referer( 'tobalt_delete_subscriber_' . $id );

        $this->delete( $id );

        wp_safe_redirect( add_query_arg( [
            'post_type' => 'tobalt_alert',
            'page'      => 'tobalt-subscribers',
            'message'   => 'deleted',
        ], admin_url( 'edit.php' ) ) );
        exit;
    }

    /**
     * Handle export subscribers.
     */
    public function handle_export() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( __( 'Unauthorized', 'tobalt-city-alerts' ) );
        }

        check_admin_referer( 'tobalt_export_subscribers' );

        global $wpdb;

        $subscribers = $wpdb->get_results( "SELECT * FROM {$this->table_name} ORDER BY created_at DESC" );

        $filename = 'subscribers-export-' . date( 'Y-m-d' ) . '.csv';

        header( 'Content-Type: text/csv; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename=' . $filename );

        $output = fopen( 'php://output', 'w' );
        fprintf( $output, chr( 0xEF ) . chr( 0xBB ) . chr( 0xBF ) );

        fputcsv( $output, [
            __( 'Email', 'tobalt-city-alerts' ),
            __( 'Categories', 'tobalt-city-alerts' ),
            __( 'Verified', 'tobalt-city-alerts' ),
            __( 'Subscribed', 'tobalt-city-alerts' ),
        ] );

        foreach ( $subscribers as $sub ) {
            $categories = json_decode( $sub->categories, true ) ?: [];

            $cat_names = [];
            foreach ( $categories as $cat_id ) {
                $term = get_term( $cat_id, 'tobalt_alert_category' );
                if ( $term && ! is_wp_error( $term ) ) {
                    $cat_names[] = $term->name;
                }
            }

            fputcsv( $output, [
                $sub->email,
                $cat_names ? implode( ', ', $cat_names ) : __( 'All', 'tobalt-city-alerts' ),
                $sub->verified ? __( 'Yes', 'tobalt-city-alerts' ) : __( 'No', 'tobalt-city-alerts' ),
                $sub->created_at,
            ] );
        }

        fclose( $output );
        exit;
    }
}
