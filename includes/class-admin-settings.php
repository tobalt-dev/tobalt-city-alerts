<?php
/**
 * Admin settings page.
 *
 * Author: Tobalt — https://tobalt.lt
 */

namespace Tobalt\CityAlerts;

defined( 'ABSPATH' ) || exit;

class Admin_Settings {

    private $option_name = 'tobalt_city_alerts_settings';

    public function __construct() {
        add_action( 'admin_menu', [ $this, 'add_menu' ] );
        add_action( 'admin_init', [ $this, 'register_settings' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
    }

    /**
     * Add settings submenu.
     */
    public function add_menu() {
        add_submenu_page(
            'edit.php?post_type=tobalt_alert',
            __( 'Nustatymai', 'tobalt-city-alerts' ),
            __( 'Nustatymai', 'tobalt-city-alerts' ),
            'manage_options',
            'tobalt-city-alerts-settings',
            [ $this, 'render_page' ]
        );
    }

    /**
     * Register settings.
     */
    public function register_settings() {
        register_setting( 'tobalt_city_alerts', $this->option_name, [
            'sanitize_callback' => [ $this, 'sanitize_settings' ],
        ] );

        // Display Section
        add_settings_section(
            'tobalt_display',
            __( 'Rodymo nustatymai', 'tobalt-city-alerts' ),
            null,
            'tobalt-city-alerts-settings'
        );

        add_settings_field(
            'icon_position',
            __( 'Ikonos pozicija', 'tobalt-city-alerts' ),
            [ $this, 'render_select' ],
            'tobalt-city-alerts-settings',
            'tobalt_display',
            [
                'name'    => 'icon_position',
                'options' => [
                    'left'  => __( 'Kairėje', 'tobalt-city-alerts' ),
                    'right' => __( 'Dešinėje', 'tobalt-city-alerts' ),
                ],
            ]
        );

        add_settings_field(
            'icon_color',
            __( 'Ikonos spalva', 'tobalt-city-alerts' ),
            [ $this, 'render_color' ],
            'tobalt-city-alerts-settings',
            'tobalt_display',
            [ 'name' => 'icon_color' ]
        );

        add_settings_field(
            'panel_width',
            __( 'Skydelio plotis (px)', 'tobalt-city-alerts' ),
            [ $this, 'render_number' ],
            'tobalt-city-alerts-settings',
            'tobalt_display',
            [ 'name' => 'panel_width', 'min' => 280, 'max' => 600 ]
        );

        add_settings_field(
            'injection_mode',
            __( 'Rodymo būdas', 'tobalt-city-alerts' ),
            [ $this, 'render_display_mode' ],
            'tobalt-city-alerts-settings',
            'tobalt_display'
        );

        add_settings_field(
            'date_range',
            __( 'Datų intervalas (dienomis)', 'tobalt-city-alerts' ),
            [ $this, 'render_number' ],
            'tobalt-city-alerts-settings',
            'tobalt_display',
            [ 'name' => 'date_range', 'min' => 1, 'max' => 30 ]
        );

        add_settings_field(
            'show_bell_label',
            __( 'Trumpojo kodo tekstas', 'tobalt-city-alerts' ),
            [ $this, 'render_checkbox' ],
            'tobalt-city-alerts-settings',
            'tobalt_display',
            [
                'name'        => 'show_bell_label',
                'description' => __( 'Rodyti tekstą šalia varpelio trumpajame kode [tobalt_city_alerts] vietoj užuominos (tooltip).', 'tobalt-city-alerts' ),
            ]
        );

        // Workflow Section
        add_settings_section(
            'tobalt_workflow',
            __( 'Darbo eigos nustatymai', 'tobalt-city-alerts' ),
            null,
            'tobalt-city-alerts-settings'
        );

        add_settings_field(
            'require_approval',
            __( 'Reikalauti patvirtinimo', 'tobalt-city-alerts' ),
            [ $this, 'render_checkbox' ],
            'tobalt-city-alerts-settings',
            'tobalt_workflow',
            [
                'name'        => 'require_approval',
                'description' => __( 'Darbuotojų pateikti pranešimai reikalauja administratoriaus patvirtinimo prieš publikavimą.', 'tobalt-city-alerts' ),
            ]
        );

        add_settings_field(
            'token_expiry',
            __( 'Nuorodos galiojimas (minutėmis)', 'tobalt-city-alerts' ),
            [ $this, 'render_number' ],
            'tobalt-city-alerts-settings',
            'tobalt_workflow',
            [ 'name' => 'token_expiry', 'min' => 5, 'max' => 1440 ]
        );

        add_settings_field(
            'rate_limit',
            __( 'Užklausų limitas (per valandą)', 'tobalt-city-alerts' ),
            [ $this, 'render_number' ],
            'tobalt-city-alerts-settings',
            'tobalt_workflow',
            [ 'name' => 'rate_limit', 'min' => 1, 'max' => 10 ]
        );

        // Email Section
        add_settings_section(
            'tobalt_email',
            __( 'El. pašto nustatymai', 'tobalt-city-alerts' ),
            null,
            'tobalt-city-alerts-settings'
        );

        add_settings_field(
            'email_from_name',
            __( 'Siuntėjo vardas', 'tobalt-city-alerts' ),
            [ $this, 'render_text' ],
            'tobalt-city-alerts-settings',
            'tobalt_email',
            [ 'name' => 'email_from_name' ]
        );

        add_settings_field(
            'email_from_address',
            __( 'Siuntėjo adresas', 'tobalt-city-alerts' ),
            [ $this, 'render_email' ],
            'tobalt-city-alerts-settings',
            'tobalt_email',
            [ 'name' => 'email_from_address' ]
        );

        // Labels Section
        add_settings_section(
            'tobalt_labels',
            __( 'Tekstų nustatymai', 'tobalt-city-alerts' ),
            null,
            'tobalt-city-alerts-settings'
        );

        add_settings_field(
            'label_panel_title',
            __( 'Skydelio pavadinimas', 'tobalt-city-alerts' ),
            [ $this, 'render_label' ],
            'tobalt-city-alerts-settings',
            'tobalt_labels',
            [ 'name' => 'panel_title' ]
        );

        add_settings_field(
            'label_no_alerts',
            __( 'Pranešimas kai nėra įvykių', 'tobalt-city-alerts' ),
            [ $this, 'render_label' ],
            'tobalt-city-alerts-settings',
            'tobalt_labels',
            [ 'name' => 'no_alerts' ]
        );

        add_settings_field(
            'label_submit_button',
            __( 'Pateikimo mygtuko tekstas', 'tobalt-city-alerts' ),
            [ $this, 'render_label' ],
            'tobalt-city-alerts-settings',
            'tobalt_labels',
            [ 'name' => 'submit_button' ]
        );

        // reCAPTCHA Section
        add_settings_section(
            'tobalt_recaptcha',
            __( 'reCAPTCHA apsauga', 'tobalt-city-alerts' ),
            null,
            'tobalt-city-alerts-settings'
        );

        add_settings_field(
            'recaptcha_enabled',
            __( 'Įjungti reCAPTCHA', 'tobalt-city-alerts' ),
            [ $this, 'render_checkbox' ],
            'tobalt-city-alerts-settings',
            'tobalt_recaptcha',
            [
                'name'        => 'recaptcha_enabled',
                'description' => __( 'Apsaugoti formas su Google reCAPTCHA v3.', 'tobalt-city-alerts' ),
            ]
        );

        add_settings_field(
            'recaptcha_site_key',
            __( 'Svetainės raktas (Site Key)', 'tobalt-city-alerts' ),
            [ $this, 'render_text' ],
            'tobalt-city-alerts-settings',
            'tobalt_recaptcha',
            [ 'name' => 'recaptcha_site_key' ]
        );

        add_settings_field(
            'recaptcha_secret_key',
            __( 'Slaptasis raktas (Secret Key)', 'tobalt-city-alerts' ),
            [ $this, 'render_password' ],
            'tobalt-city-alerts-settings',
            'tobalt_recaptcha',
            [ 'name' => 'recaptcha_secret_key' ]
        );

        // Danger Zone Section
        add_settings_section(
            'tobalt_danger',
            __( 'Pavojinga zona', 'tobalt-city-alerts' ),
            null,
            'tobalt-city-alerts-settings'
        );

        add_settings_field(
            'delete_data_on_uninstall',
            __( 'Ištrinti duomenis pašalinant', 'tobalt-city-alerts' ),
            [ $this, 'render_checkbox' ],
            'tobalt-city-alerts-settings',
            'tobalt_danger',
            [
                'name'        => 'delete_data_on_uninstall',
                'description' => __( 'Ištrinti visus pranešimus, el. paštus ir nustatymus kai įskiepis pašalinamas. Jei neįjungta - duomenys išsaugomi.', 'tobalt-city-alerts' ),
            ]
        );
    }

    /**
     * Generate label with tooltip icon.
     */
    private function label_with_tooltip( $label, $tooltip ) {
        // Don't show tooltip if translation key is not translated
        if ( strpos( $tooltip, 'tooltip_' ) === 0 ) {
            return $label;
        }
        return $label . ' <span class="tobalt-tooltip" title="' . esc_attr( $tooltip ) . '">?</span>';
    }

    /**
     * Get settings.
     */
    public function get_settings() {
        return get_option( $this->option_name, [] );
    }

    /**
     * Get single setting.
     */
    public function get_setting( $key, $default = '' ) {
        $settings = $this->get_settings();
        return $settings[ $key ] ?? $default;
    }

    /**
     * Sanitize settings.
     */
    public function sanitize_settings( $input ) {
        $sanitized = [];

        $sanitized['icon_position']     = in_array( $input['icon_position'] ?? '', [ 'left', 'right' ], true ) ? $input['icon_position'] : 'right';
        $sanitized['icon_color']        = sanitize_hex_color( $input['icon_color'] ?? '#0073aa' ) ?: '#0073aa';
        $sanitized['panel_width']       = absint( $input['panel_width'] ?? 400 );
        $sanitized['injection_mode']    = in_array( $input['injection_mode'] ?? '', [ 'auto', 'shortcode' ], true ) ? $input['injection_mode'] : 'auto';
        $sanitized['date_range']        = min( 30, max( 1, absint( $input['date_range'] ?? 7 ) ) );
        $sanitized['show_bell_label']   = ! empty( $input['show_bell_label'] );
        $sanitized['require_approval']  = ! empty( $input['require_approval'] );
        $sanitized['token_expiry']      = min( 1440, max( 5, absint( $input['token_expiry'] ?? 60 ) ) );
        $sanitized['rate_limit']        = min( 10, max( 1, absint( $input['rate_limit'] ?? 3 ) ) );
        $sanitized['email_from_name']   = sanitize_text_field( $input['email_from_name'] ?? 'CityAlerts' );
        $sanitized['email_from_address']= sanitize_email( $input['email_from_address'] ?? '' );

        $sanitized['custom_labels'] = [
            'panel_title'   => sanitize_text_field( $input['custom_labels']['panel_title'] ?? '' ),
            'no_alerts'     => sanitize_text_field( $input['custom_labels']['no_alerts'] ?? '' ),
            'submit_button' => sanitize_text_field( $input['custom_labels']['submit_button'] ?? '' ),
        ];

        // reCAPTCHA
        $sanitized['recaptcha_enabled']    = ! empty( $input['recaptcha_enabled'] );
        $sanitized['recaptcha_site_key']   = sanitize_text_field( $input['recaptcha_site_key'] ?? '' );
        $sanitized['recaptcha_secret_key'] = sanitize_text_field( $input['recaptcha_secret_key'] ?? '' );

        // Danger zone
        $sanitized['delete_data_on_uninstall'] = ! empty( $input['delete_data_on_uninstall'] );

        return $sanitized;
    }

    /**
     * Render settings page.
     */
    public function render_page() {
        $demo_message = '';
        $demo_type    = '';

        // Handle demo content actions
        if ( isset( $_POST['tobalt_create_demo'] ) && check_admin_referer( 'tobalt_demo_content' ) ) {
            $this->create_demo_content();
            $demo_message = __( 'Demo turinys sukurtas sėkmingai!', 'tobalt-city-alerts' );
            $demo_type    = 'success';
        }

        if ( isset( $_POST['tobalt_delete_demo'] ) && check_admin_referer( 'tobalt_demo_content' ) ) {
            $this->delete_demo_content();
            $demo_message = __( 'Demo turinys ištrintas sėkmingai!', 'tobalt-city-alerts' );
            $demo_type    = 'success';
        }

        $demo_count = $this->get_demo_content_count();
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Pranešimų sistemos nustatymai', 'tobalt-city-alerts' ); ?></h1>

            <form method="post" action="options.php">
                <?php
                settings_fields( 'tobalt_city_alerts' );
                do_settings_sections( 'tobalt-city-alerts-settings' );
                submit_button( __( 'Išsaugoti nustatymus', 'tobalt-city-alerts' ) );
                ?>
            </form>

            <hr>

            <h2><?php esc_html_e( 'Trumpieji kodai (Shortcodes)', 'tobalt-city-alerts' ); ?></h2>
            <table class="widefat" style="max-width: 600px;">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Trumpasis kodas', 'tobalt-city-alerts' ); ?></th>
                        <th><?php esc_html_e( 'Aprašymas', 'tobalt-city-alerts' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><code>[tobalt_city_alerts]</code></td>
                        <td><?php esc_html_e( 'Išskleidžiamas pranešimų skydelis su varpeliu', 'tobalt-city-alerts' ); ?></td>
                    </tr>
                    <tr>
                        <td><code>[tobalt_city_alerts inline="true"]</code></td>
                        <td><?php esc_html_e( 'Įterptas pranešimų rodinys (kalendorius, navigacija, įvykių sąrašas)', 'tobalt-city-alerts' ); ?></td>
                    </tr>
                    <tr>
                        <td><code>[tobalt_subscribe]</code></td>
                        <td><?php esc_html_e( 'El. pašto prenumeratos forma', 'tobalt-city-alerts' ); ?></td>
                    </tr>
                    <tr>
                        <td><code>[tobalt_request_link]</code></td>
                        <td><?php esc_html_e( 'Prisijungimo nuorodos užklausos forma (pranešimų pateikimui)', 'tobalt-city-alerts' ); ?></td>
                    </tr>
                </tbody>
            </table>

            <hr>

            <h2><?php esc_html_e( 'Demo turinys', 'tobalt-city-alerts' ); ?></h2>

            <?php if ( $demo_message ) : ?>
                <div class="notice notice-<?php echo esc_attr( $demo_type ); ?> is-dismissible">
                    <p><?php echo esc_html( $demo_message ); ?></p>
                </div>
            <?php endif; ?>

            <p class="description">
                <?php esc_html_e( 'Sukurkite demo pranešimus, kad išbandytumėte įskiepio funkcionalumą. Demo turinį galima lengvai pašalinti, kai jo nebereikia.', 'tobalt-city-alerts' ); ?>
            </p>

            <?php if ( $demo_count > 0 ) : ?>
                <p>
                    <strong><?php printf( esc_html__( 'Dabartinių demo pranešimų: %d', 'tobalt-city-alerts' ), $demo_count ); ?></strong>
                </p>
            <?php endif; ?>

            <form method="post" style="display: inline-flex; gap: 10px; margin-top: 10px;">
                <?php wp_nonce_field( 'tobalt_demo_content' ); ?>

                <button type="submit" name="tobalt_create_demo" class="button button-secondary">
                    <?php esc_html_e( 'Sukurti demo turinį', 'tobalt-city-alerts' ); ?>
                </button>

                <?php if ( $demo_count > 0 ) : ?>
                    <button type="submit" name="tobalt_delete_demo" class="button button-secondary" style="color: #b32d2e;" onclick="return confirm('<?php esc_attr_e( 'Ištrinti visą demo turinį?', 'tobalt-city-alerts' ); ?>');">
                        <?php esc_html_e( 'Ištrinti demo turinį', 'tobalt-city-alerts' ); ?>
                    </button>
                <?php endif; ?>
            </form>
        </div>
        <?php
    }

    /**
     * Create demo content.
     */
    private function create_demo_content() {
        // Create demo categories (Lithuanian)
        $categories = [
            'Vykdomi darbai'   => 'Šiuo metu vykdomi darbai',
            'Planuojami darbai' => 'Ateityje planuojami darbai',
        ];

        $cat_ids = [];
        foreach ( $categories as $name => $desc ) {
            $term = term_exists( $name, 'tobalt_alert_category' );
            if ( ! $term ) {
                $term = wp_insert_term( $name, 'tobalt_alert_category', [ 'description' => $desc ] );
            }
            if ( ! is_wp_error( $term ) ) {
                $cat_ids[ $name ] = is_array( $term ) ? $term['term_id'] : $term;
            }
        }

        // Demo alerts data (Lithuanian)
        $demo_alerts = [
            [
                'title'       => 'Vandentiekio vamzdžio remontas',
                'content'     => 'Šiuo metu vykdomi vandentiekio vamzdžio remonto darbai. Vandens tiekimas laikinai nutrauktas. Remonto brigados dirba vietoje.',
                'severity'    => 'high',
                'category'    => 'Vykdomi darbai',
                'location'    => 'Vilniaus g. 1-50',
                'starts_at'   => date( 'Y-m-d H:i', strtotime( '-2 hours' ) ),
                'ends_at'     => date( 'Y-m-d H:i', strtotime( '+4 hours' ) ),
                'pinned'      => true,
            ],
            [
                'title'       => 'Kelio dangos atnaujinimas',
                'content'     => 'Vykdomi kelio dangos atnaujinimo darbai. Eismas ribojamas. Prašome naudoti alternatyvius maršrutus.',
                'severity'    => 'medium',
                'category'    => 'Vykdomi darbai',
                'location'    => 'Laisvės alėja',
                'starts_at'   => date( 'Y-m-d' ) . ' 07:00',
                'ends_at'     => date( 'Y-m-d', strtotime( '+2 days' ) ) . ' 18:00',
            ],
            [
                'title'       => 'Planuojamas elektros tiekimo nutraukimas',
                'content'     => 'Planuojami elektros tinklų priežiūros darbai. Elektros tiekimas bus nutrauktas nuo 9:00 iki 15:00.',
                'severity'    => 'medium',
                'category'    => 'Planuojami darbai',
                'location'    => 'Centrinis rajonas',
                'starts_at'   => date( 'Y-m-d', strtotime( '+3 days' ) ) . ' 09:00',
                'ends_at'     => date( 'Y-m-d', strtotime( '+3 days' ) ) . ' 15:00',
            ],
            [
                'title'       => 'Šaligatvio remontas',
                'content'     => 'Planuojamas šaligatvio remonto darbai. Pėsčiųjų eismas bus nukreiptas kitoje gatvės pusėje.',
                'severity'    => 'low',
                'category'    => 'Planuojami darbai',
                'location'    => 'Gedimino g. 10-30',
                'starts_at'   => date( 'Y-m-d', strtotime( '+5 days' ) ) . ' 08:00',
                'ends_at'     => date( 'Y-m-d', strtotime( '+7 days' ) ) . ' 17:00',
            ],
        ];

        foreach ( $demo_alerts as $alert ) {
            $post_id = wp_insert_post( [
                'post_type'    => 'tobalt_alert',
                'post_title'   => $alert['title'],
                'post_content' => $alert['content'],
                'post_status'  => 'publish',
                'meta_input'   => [
                    '_tobalt_demo_content' => '1',
                ],
            ] );

            if ( ! is_wp_error( $post_id ) ) {
                // Parse starts_at into date and time
                $starts_at = $alert['starts_at'] ?? '';
                if ( $starts_at ) {
                    $date_parts = explode( ' ', $starts_at );
                    update_post_meta( $post_id, '_tobalt_alert_date', $date_parts[0] ?? '' );
                    update_post_meta( $post_id, '_tobalt_alert_time', $date_parts[1] ?? '' );
                }

                // Parse ends_at into end_date
                $ends_at = $alert['ends_at'] ?? '';
                if ( $ends_at ) {
                    $end_parts = explode( ' ', $ends_at );
                    update_post_meta( $post_id, '_tobalt_alert_end_date', $end_parts[0] ?? '' );
                }

                update_post_meta( $post_id, '_tobalt_alert_severity', $alert['severity'] );
                update_post_meta( $post_id, '_tobalt_alert_location', $alert['location'] ?? '' );

                if ( ! empty( $alert['pinned'] ) ) {
                    update_post_meta( $post_id, '_tobalt_alert_pinned', '1' );
                }

                // Assign category
                if ( ! empty( $alert['category'] ) && isset( $cat_ids[ $alert['category'] ] ) ) {
                    wp_set_object_terms( $post_id, [ (int) $cat_ids[ $alert['category'] ] ], 'tobalt_alert_category' );
                }
            }
        }
    }

    /**
     * Delete demo content.
     */
    private function delete_demo_content() {
        $demo_posts = get_posts( [
            'post_type'      => 'tobalt_alert',
            'posts_per_page' => -1,
            'post_status'    => 'any',
            'meta_key'       => '_tobalt_demo_content',
            'meta_value'     => '1',
            'fields'         => 'ids',
        ] );

        foreach ( $demo_posts as $post_id ) {
            wp_delete_post( $post_id, true );
        }
    }

    /**
     * Get demo content count.
     */
    private function get_demo_content_count() {
        $demo_posts = get_posts( [
            'post_type'      => 'tobalt_alert',
            'posts_per_page' => -1,
            'post_status'    => 'any',
            'meta_key'       => '_tobalt_demo_content',
            'meta_value'     => '1',
            'fields'         => 'ids',
        ] );

        return count( $demo_posts );
    }

    /**
     * Render text field.
     */
    public function render_text( $args ) {
        $settings = $this->get_settings();
        $value    = $settings[ $args['name'] ] ?? '';
        ?>
        <input type="text" name="<?php echo esc_attr( $this->option_name . '[' . $args['name'] . ']' ); ?>" value="<?php echo esc_attr( $value ); ?>" class="regular-text">
        <?php if ( ! empty( $args['description'] ) ) : ?>
            <p class="description"><?php echo esc_html( $args['description'] ); ?></p>
        <?php endif;
    }

    /**
     * Render email field.
     */
    public function render_email( $args ) {
        $settings = $this->get_settings();
        $value    = $settings[ $args['name'] ] ?? '';
        ?>
        <input type="email" name="<?php echo esc_attr( $this->option_name . '[' . $args['name'] . ']' ); ?>" value="<?php echo esc_attr( $value ); ?>" class="regular-text">
        <?php if ( ! empty( $args['description'] ) ) : ?>
            <p class="description"><?php echo esc_html( $args['description'] ); ?></p>
        <?php endif;
    }

    /**
     * Render number field.
     */
    public function render_number( $args ) {
        $settings = $this->get_settings();
        $value    = $settings[ $args['name'] ] ?? '';
        ?>
        <input type="number" name="<?php echo esc_attr( $this->option_name . '[' . $args['name'] . ']' ); ?>" value="<?php echo esc_attr( $value ); ?>" min="<?php echo esc_attr( $args['min'] ?? 0 ); ?>" max="<?php echo esc_attr( $args['max'] ?? 9999 ); ?>" class="small-text">
        <?php
    }

    /**
     * Render select field.
     */
    public function render_select( $args ) {
        $settings = $this->get_settings();
        $value    = $settings[ $args['name'] ] ?? '';
        ?>
        <select name="<?php echo esc_attr( $this->option_name . '[' . $args['name'] . ']' ); ?>">
            <?php foreach ( $args['options'] as $key => $label ) : ?>
                <option value="<?php echo esc_attr( $key ); ?>" <?php selected( $value, $key ); ?>><?php echo esc_html( $label ); ?></option>
            <?php endforeach; ?>
        </select>
        <?php if ( ! empty( $args['description'] ) ) : ?>
            <p class="description"><?php echo esc_html( $args['description'] ); ?></p>
        <?php endif;
    }

    /**
     * Render color picker.
     */
    public function render_color( $args ) {
        $settings = $this->get_settings();
        $value    = $settings[ $args['name'] ] ?? '#0073aa';
        ?>
        <input type="color" name="<?php echo esc_attr( $this->option_name . '[' . $args['name'] . ']' ); ?>" value="<?php echo esc_attr( $value ); ?>">
        <?php
    }

    /**
     * Render password field.
     */
    public function render_password( $args ) {
        $settings = $this->get_settings();
        $value    = $settings[ $args['name'] ] ?? '';
        ?>
        <input type="password" name="<?php echo esc_attr( $this->option_name . '[' . $args['name'] . ']' ); ?>" value="<?php echo esc_attr( $value ); ?>" class="regular-text" autocomplete="off">
        <?php if ( ! empty( $args['description'] ) ) : ?>
            <p class="description"><?php echo esc_html( $args['description'] ); ?></p>
        <?php endif;
    }

    /**
     * Render checkbox.
     */
    public function render_checkbox( $args ) {
        $settings = $this->get_settings();
        $value    = $settings[ $args['name'] ] ?? false;
        ?>
        <label>
            <input type="checkbox" name="<?php echo esc_attr( $this->option_name . '[' . $args['name'] . ']' ); ?>" value="1" <?php checked( $value ); ?>>
            <?php if ( ! empty( $args['description'] ) ) : ?>
                <?php echo esc_html( $args['description'] ); ?>
            <?php endif; ?>
        </label>
        <?php
    }

    /**
     * Render label field.
     */
    public function render_label( $args ) {
        $settings = $this->get_settings();
        $labels   = $settings['custom_labels'] ?? [];
        $value    = $labels[ $args['name'] ] ?? '';
        ?>
        <input type="text" name="<?php echo esc_attr( $this->option_name . '[custom_labels][' . $args['name'] . ']' ); ?>" value="<?php echo esc_attr( $value ); ?>" class="regular-text">
        <?php
    }

    /**
     * Render display mode selector.
     */
    public function render_display_mode() {
        $settings = $this->get_settings();
        $mode     = $settings['injection_mode'] ?? 'auto';
        ?>
        <fieldset>
            <label>
                <input type="radio" name="<?php echo esc_attr( $this->option_name . '[injection_mode]' ); ?>" value="auto" <?php checked( $mode, 'auto' ); ?>>
                <?php esc_html_e( 'Automatinis (plaukiojantis varpelis visuose puslapiuose)', 'tobalt-city-alerts' ); ?>
            </label><br>
            <label>
                <input type="radio" name="<?php echo esc_attr( $this->option_name . '[injection_mode]' ); ?>" value="shortcode" <?php checked( $mode, 'shortcode' ); ?>>
                <?php esc_html_e( 'Tik trumpuoju kodu (varpelis rodomas ten, kur įdėtas trumpasis kodas)', 'tobalt-city-alerts' ); ?>
            </label>
        </fieldset>
        <?php
    }

    /**
     * Enqueue admin assets.
     */
    public function enqueue_assets( $hook ) {
        if ( 'tobalt_alert_page_tobalt-city-alerts-settings' !== $hook ) {
            return;
        }

        wp_enqueue_style(
            'tobalt-city-alerts-admin',
            TOBALT_CITY_ALERTS_URL . 'assets/css/admin.css',
            [],
            TOBALT_CITY_ALERTS_VERSION
        );
    }
}
