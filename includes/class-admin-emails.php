<?php
/**
 * Approved emails management.
 *
 * Author: Tobalt — https://tobalt.lt
 */

namespace Tobalt\CityAlerts;

defined( 'ABSPATH' ) || exit;

class Admin_Emails {

    private $table_name;

    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'tobalt_approved_emails';

        add_action( 'admin_menu', [ $this, 'add_menu' ] );
        add_action( 'admin_post_tobalt_add_email', [ $this, 'handle_add_email' ] );
        add_action( 'admin_post_tobalt_delete_email', [ $this, 'handle_delete_email' ] );
        add_action( 'admin_post_tobalt_bulk_emails', [ $this, 'handle_bulk_action' ] );
    }

    /**
     * Ensure table exists, create if missing.
     */
    private function ensure_table_exists() {
        global $wpdb;

        $table_exists = $wpdb->get_var( "SHOW TABLES LIKE '{$this->table_name}'" ) === $this->table_name;

        if ( $table_exists ) {
            return true;
        }

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$this->table_name} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            email varchar(255) NOT NULL,
            provider_id bigint(20) unsigned DEFAULT NULL,
            role varchar(50) DEFAULT 'employee',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            created_by bigint(20) unsigned DEFAULT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY email (email),
            KEY provider_id (provider_id)
        ) {$charset_collate};";

        dbDelta( $sql );

        return true;
    }

    /**
     * Add submenu page.
     */
    public function add_menu() {
        add_submenu_page(
            'edit.php?post_type=tobalt_alert',
            __( 'Approved Emails', 'tobalt-city-alerts' ),
            __( 'Approved Emails', 'tobalt-city-alerts' ),
            'manage_options',
            'tobalt-approved-emails',
            [ $this, 'render_page' ]
        );
    }

    /**
     * Get all approved emails.
     */
    public function get_emails( $args = [] ) {
        global $wpdb;

        $defaults = [
            'orderby'  => 'created_at',
            'order'    => 'DESC',
            'per_page' => 20,
            'page'     => 1,
            'search'   => '',
        ];

        $args   = wp_parse_args( $args, $defaults );
        $offset = ( $args['page'] - 1 ) * $args['per_page'];

        $where = '1=1';
        $values = [];

        if ( ! empty( $args['search'] ) ) {
            $where .= ' AND email LIKE %s';
            $values[] = '%' . $wpdb->esc_like( $args['search'] ) . '%';
        }

        $orderby = in_array( $args['orderby'], [ 'email', 'created_at', 'role' ], true ) ? $args['orderby'] : 'created_at';
        $order   = 'ASC' === strtoupper( $args['order'] ) ? 'ASC' : 'DESC';

        $query = "SELECT * FROM {$this->table_name} WHERE {$where} ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d";
        $values[] = $args['per_page'];
        $values[] = $offset;

        return $wpdb->get_results( $wpdb->prepare( $query, $values ) );
    }

    /**
     * Get total count.
     */
    public function get_count( $search = '' ) {
        global $wpdb;

        if ( ! empty( $search ) ) {
            return (int) $wpdb->get_var( $wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->table_name} WHERE email LIKE %s",
                '%' . $wpdb->esc_like( $search ) . '%'
            ) );
        }

        return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$this->table_name}" );
    }

    /**
     * Check if email is approved.
     */
    public function is_approved( $email ) {
        global $wpdb;

        $this->ensure_table_exists();

        return (bool) $wpdb->get_var( $wpdb->prepare(
            "SELECT id FROM {$this->table_name} WHERE email = %s",
            sanitize_email( $email )
        ) );
    }

    /**
     * Add approved email.
     */
    public function add_email( $email, $role = 'employee' ) {
        global $wpdb;

        $email = sanitize_email( $email );

        if ( ! is_email( $email ) ) {
            return new \WP_Error( 'invalid_email', __( 'Invalid email address.', 'tobalt-city-alerts' ) );
        }

        if ( $this->is_approved( $email ) ) {
            return new \WP_Error( 'email_exists', __( 'This email is already approved.', 'tobalt-city-alerts' ) );
        }

        $result = $wpdb->insert(
            $this->table_name,
            [
                'email'      => $email,
                'role'       => sanitize_key( $role ),
                'created_by' => get_current_user_id(),
            ],
            [ '%s', '%s', '%d' ]
        );

        if ( ! $result ) {
            return new \WP_Error( 'db_error', __( 'Failed to add email.', 'tobalt-city-alerts' ) );
        }

        return $wpdb->insert_id;
    }

    /**
     * Delete approved email.
     */
    public function delete_email( $id ) {
        global $wpdb;

        return $wpdb->delete(
            $this->table_name,
            [ 'id' => absint( $id ) ],
            [ '%d' ]
        );
    }

    /**
     * Handle add email form.
     */
    public function handle_add_email() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( __( 'Unauthorized', 'tobalt-city-alerts' ) );
        }

        check_admin_referer( 'tobalt_add_email' );

        $email = sanitize_email( $_POST['email'] ?? '' );
        $role  = sanitize_key( $_POST['role'] ?? 'employee' );

        $result = $this->add_email( $email, $role );

        $redirect = add_query_arg(
            [
                'post_type' => 'tobalt_alert',
                'page'      => 'tobalt-approved-emails',
                'message'   => is_wp_error( $result ) ? 'error' : 'added',
                'error_msg' => is_wp_error( $result ) ? urlencode( $result->get_error_message() ) : '',
            ],
            admin_url( 'edit.php' )
        );

        wp_safe_redirect( $redirect );
        exit;
    }

    /**
     * Handle delete email.
     */
    public function handle_delete_email() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( __( 'Unauthorized', 'tobalt-city-alerts' ) );
        }

        $id = absint( $_GET['id'] ?? 0 );
        check_admin_referer( 'tobalt_delete_email_' . $id );

        $this->delete_email( $id );

        $redirect = add_query_arg(
            [
                'post_type' => 'tobalt_alert',
                'page'      => 'tobalt-approved-emails',
                'message'   => 'deleted',
            ],
            admin_url( 'edit.php' )
        );

        wp_safe_redirect( $redirect );
        exit;
    }

    /**
     * Handle bulk actions.
     */
    public function handle_bulk_action() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( __( 'Unauthorized', 'tobalt-city-alerts' ) );
        }

        check_admin_referer( 'tobalt_bulk_emails' );

        $action = sanitize_key( $_POST['bulk_action'] ?? '' );
        $ids    = array_map( 'absint', $_POST['email_ids'] ?? [] );

        if ( 'delete' === $action && ! empty( $ids ) ) {
            foreach ( $ids as $id ) {
                $this->delete_email( $id );
            }
        }

        $redirect = add_query_arg(
            [
                'post_type' => 'tobalt_alert',
                'page'      => 'tobalt-approved-emails',
                'message'   => 'bulk_deleted',
            ],
            admin_url( 'edit.php' )
        );

        wp_safe_redirect( $redirect );
        exit;
    }

    /**
     * Render admin page.
     */
    public function render_page() {
        $search   = sanitize_text_field( $_GET['s'] ?? '' );
        $page     = max( 1, absint( $_GET['paged'] ?? 1 ) );
        $per_page = 20;

        $emails = $this->get_emails( [
            'search'   => $search,
            'page'     => $page,
            'per_page' => $per_page,
        ] );

        $total = $this->get_count( $search );
        $pages = ceil( $total / $per_page );

        $message = sanitize_key( $_GET['message'] ?? '' );
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline"><?php esc_html_e( 'Approved Emails', 'tobalt-city-alerts' ); ?></h1>

            <?php if ( 'added' === $message ) : ?>
                <div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Email added successfully.', 'tobalt-city-alerts' ); ?></p></div>
            <?php elseif ( 'deleted' === $message ) : ?>
                <div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Email deleted.', 'tobalt-city-alerts' ); ?></p></div>
            <?php elseif ( 'bulk_deleted' === $message ) : ?>
                <div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Selected emails deleted.', 'tobalt-city-alerts' ); ?></p></div>
            <?php elseif ( 'error' === $message ) : ?>
                <div class="notice notice-error is-dismissible"><p><?php echo esc_html( urldecode( $_GET['error_msg'] ?? '' ) ); ?></p></div>
            <?php endif; ?>

            <hr class="wp-header-end">

            <div class="tobalt-emails-wrapper" style="display:flex;gap:30px;margin-top:20px;">
                <!-- Add Email Form -->
                <div style="flex:0 0 300px;">
                    <div class="card">
                        <h2><?php esc_html_e( 'Add New Email', 'tobalt-city-alerts' ); ?></h2>
                        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                            <input type="hidden" name="action" value="tobalt_add_email">
                            <?php wp_nonce_field( 'tobalt_add_email' ); ?>

                            <p>
                                <label for="email"><?php esc_html_e( 'Email Address', 'tobalt-city-alerts' ); ?></label><br>
                                <input type="email" id="email" name="email" required class="regular-text">
                            </p>
                            <p>
                                <label for="role"><?php esc_html_e( 'Role', 'tobalt-city-alerts' ); ?></label><br>
                                <select id="role" name="role">
                                    <option value="employee"><?php esc_html_e( 'Employee', 'tobalt-city-alerts' ); ?></option>
                                    <option value="manager"><?php esc_html_e( 'Manager', 'tobalt-city-alerts' ); ?></option>
                                </select>
                            </p>
                            <?php submit_button( __( 'Add Email', 'tobalt-city-alerts' ), 'primary', 'submit', false ); ?>
                        </form>
                    </div>
                </div>

                <!-- Email List -->
                <div style="flex:1;">
                    <form method="get">
                        <input type="hidden" name="post_type" value="tobalt_alert">
                        <input type="hidden" name="page" value="tobalt-approved-emails">
                        <p class="search-box">
                            <input type="search" name="s" value="<?php echo esc_attr( $search ); ?>" placeholder="<?php esc_attr_e( 'Search emails...', 'tobalt-city-alerts' ); ?>">
                            <?php submit_button( __( 'Search', 'tobalt-city-alerts' ), 'secondary', '', false ); ?>
                        </p>
                    </form>

                    <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                        <input type="hidden" name="action" value="tobalt_bulk_emails">
                        <?php wp_nonce_field( 'tobalt_bulk_emails' ); ?>

                        <div class="tablenav top">
                            <div class="alignleft actions bulkactions">
                                <select name="bulk_action">
                                    <option value=""><?php esc_html_e( 'Bulk Actions', 'tobalt-city-alerts' ); ?></option>
                                    <option value="delete"><?php esc_html_e( 'Delete', 'tobalt-city-alerts' ); ?></option>
                                </select>
                                <?php submit_button( __( 'Apply', 'tobalt-city-alerts' ), 'action', '', false ); ?>
                            </div>
                            <div class="tablenav-pages">
                                <span class="displaying-num"><?php printf( _n( '%s item', '%s items', $total, 'tobalt-city-alerts' ), number_format_i18n( $total ) ); ?></span>
                            </div>
                        </div>

                        <table class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <td class="manage-column column-cb check-column"><input type="checkbox" id="cb-select-all"></td>
                                    <th><?php esc_html_e( 'Email', 'tobalt-city-alerts' ); ?></th>
                                    <th><?php esc_html_e( 'Role', 'tobalt-city-alerts' ); ?></th>
                                    <th><?php esc_html_e( 'Added', 'tobalt-city-alerts' ); ?></th>
                                    <th><?php esc_html_e( 'Actions', 'tobalt-city-alerts' ); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ( empty( $emails ) ) : ?>
                                    <tr><td colspan="5"><?php esc_html_e( 'No approved emails found.', 'tobalt-city-alerts' ); ?></td></tr>
                                <?php else : ?>
                                    <?php foreach ( $emails as $row ) : ?>
                                        <tr>
                                            <th class="check-column"><input type="checkbox" name="email_ids[]" value="<?php echo esc_attr( $row->id ); ?>"></th>
                                            <td><strong><?php echo esc_html( $row->email ); ?></strong></td>
                                            <td><?php echo esc_html( ucfirst( $row->role ) ); ?></td>
                                            <td><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $row->created_at ) ) ); ?></td>
                                            <td>
                                                <a href="<?php echo esc_url( wp_nonce_url(
                                                    add_query_arg( [
                                                        'action' => 'tobalt_delete_email',
                                                        'id'     => $row->id,
                                                    ], admin_url( 'admin-post.php' ) ),
                                                    'tobalt_delete_email_' . $row->id
                                                ) ); ?>" class="delete" onclick="return confirm('<?php esc_attr_e( 'Are you sure?', 'tobalt-city-alerts' ); ?>');">
                                                    <?php esc_html_e( 'Delete', 'tobalt-city-alerts' ); ?>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </form>

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
            </div>
        </div>
        <?php
    }
}
