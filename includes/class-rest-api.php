<?php
/**
 * REST API endpoints.
 *
 * Author: Tobalt — https://tobalt.lt
 */

namespace Tobalt\CityAlerts;

defined( 'ABSPATH' ) || exit;

class Rest_API {

    private $namespace = 'tobalt/v1';

    public function __construct() {
        add_action( 'rest_api_init', [ $this, 'register_routes' ] );
    }

    /**
     * Register REST routes.
     */
    public function register_routes() {
        // Get alerts (public)
        register_rest_route( $this->namespace, '/alerts', [
            'methods'             => 'GET',
            'callback'            => [ $this, 'get_alerts' ],
            'permission_callback' => '__return_true',
            'args'                => [
                'date'     => [
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'from'     => [
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'to'       => [
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'category' => [
                    'type'              => 'integer',
                    'sanitize_callback' => 'absint',
                ],
            ],
        ] );

        // Request magic link (public)
        register_rest_route( $this->namespace, '/request-link', [
            'methods'             => 'POST',
            'callback'            => [ $this, 'request_magic_link' ],
            'permission_callback' => '__return_true',
            'args'                => [
                'email' => [
                    'required'          => true,
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_email',
                    'validate_callback' => function( $value ) {
                        return is_email( $value );
                    },
                ],
            ],
        ] );

        // Verify token (public)
        register_rest_route( $this->namespace, '/verify-token/(?P<token>[a-f0-9]{64})', [
            'methods'             => 'GET',
            'callback'            => [ $this, 'verify_token' ],
            'permission_callback' => '__return_true',
            'args'                => [
                'token' => [
                    'required' => true,
                    'type'     => 'string',
                ],
            ],
        ] );

        // Submit alert (requires valid token)
        register_rest_route( $this->namespace, '/submit-alert', [
            'methods'             => 'POST',
            'callback'            => [ $this, 'submit_alert' ],
            'permission_callback' => [ $this, 'check_token_permission' ],
            'args'                => [
                'token'       => [
                    'required'          => true,
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'title'       => [
                    'required'          => true,
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'description' => [
                    'required'          => true,
                    'type'              => 'string',
                    'sanitize_callback' => 'wp_kses_post',
                ],
                'date'        => [
                    'required'          => true,
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                    'validate_callback' => function( $value ) {
                        return preg_match( '/^\d{4}-\d{2}-\d{2}$/', $value );
                    },
                ],
                'time'        => [
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'category'    => [
                    'type'              => 'integer',
                    'sanitize_callback' => 'absint',
                ],
                'severity'    => [
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_key',
                ],
                'location'    => [
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
            ],
        ] );

        // Get categories (public)
        register_rest_route( $this->namespace, '/categories', [
            'methods'             => 'GET',
            'callback'            => [ $this, 'get_categories' ],
            'permission_callback' => '__return_true',
        ] );

        // Subscribe (public)
        register_rest_route( $this->namespace, '/subscribe', [
            'methods'             => 'POST',
            'callback'            => [ $this, 'subscribe' ],
            'permission_callback' => '__return_true',
            'args'                => [
                'email' => [
                    'required'          => true,
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_email',
                    'validate_callback' => function( $value ) {
                        return is_email( $value );
                    },
                ],
                'categories' => [
                    'type'    => 'array',
                    'default' => [],
                ],
            ],
        ] );

        // Verify subscription (public)
        register_rest_route( $this->namespace, '/verify-subscription/(?P<token>[a-f0-9]{64})', [
            'methods'             => 'GET',
            'callback'            => [ $this, 'verify_subscription' ],
            'permission_callback' => '__return_true',
        ] );

        // Unsubscribe (public)
        register_rest_route( $this->namespace, '/unsubscribe/(?P<token>[a-f0-9]{64})', [
            'methods'             => 'GET',
            'callback'            => [ $this, 'unsubscribe' ],
            'permission_callback' => '__return_true',
        ] );

        // Get my alerts (requires valid token)
        register_rest_route( $this->namespace, '/my-alerts', [
            'methods'             => 'GET',
            'callback'            => [ $this, 'get_my_alerts' ],
            'permission_callback' => [ $this, 'check_token_permission' ],
            'args'                => [
                'token' => [
                    'required' => true,
                    'type'     => 'string',
                ],
            ],
        ] );

        // Update alert (requires valid token)
        register_rest_route( $this->namespace, '/update-alert/(?P<id>\d+)', [
            'methods'             => 'POST',
            'callback'            => [ $this, 'update_alert' ],
            'permission_callback' => [ $this, 'check_token_permission' ],
            'args'                => [
                'token'    => [
                    'required' => true,
                    'type'     => 'string',
                ],
                'end_date' => [
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'end_time' => [
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
            ],
        ] );

        // Mark alert as solved (requires valid token)
        register_rest_route( $this->namespace, '/mark-solved/(?P<id>\d+)', [
            'methods'             => 'POST',
            'callback'            => [ $this, 'mark_alert_solved' ],
            'permission_callback' => [ $this, 'check_token_permission' ],
            'args'                => [
                'token' => [
                    'required' => true,
                    'type'     => 'string',
                ],
            ],
        ] );
    }

    /**
     * Get alerts.
     */
    public function get_alerts( $request ) {
        $settings   = get_option( 'tobalt_city_alerts_settings', [] );
        $date_range = (int) ( $settings['date_range'] ?? 7 );

        $date     = $request->get_param( 'date' );
        $from     = $request->get_param( 'from' );
        $to       = $request->get_param( 'to' );
        $category = $request->get_param( 'category' );

        // Default: today to X days ahead
        if ( ! $from && ! $to && ! $date ) {
            $from = current_time( 'Y-m-d' );
            $to   = date( 'Y-m-d', strtotime( "+{$date_range} days" ) );
        } elseif ( $date ) {
            $from = $date;
            $to   = $date;
        }

        // Use selected date for filtering (not today)
        $filter_date = $from;

        $args = [
            'post_type'      => 'tobalt_alert',
            'post_status'    => 'publish',
            'posts_per_page' => 100,
            'meta_query'     => [
                'relation' => 'AND',
                // Alert must have started by the selected date
                [
                    'key'     => '_tobalt_alert_date',
                    'value'   => $to,
                    'compare' => '<=',
                    'type'    => 'DATE',
                ],
                // Either: has end_date >= selected date, OR no end_date and start_date matches selected date
                [
                    'relation' => 'OR',
                    // Has end_date that is on or after the selected date
                    [
                        'key'     => '_tobalt_alert_end_date',
                        'value'   => $filter_date,
                        'compare' => '>=',
                        'type'    => 'DATE',
                    ],
                    // No end_date: only show on the exact start date
                    [
                        'relation' => 'AND',
                        [
                            'relation' => 'OR',
                            [
                                'key'     => '_tobalt_alert_end_date',
                                'compare' => 'NOT EXISTS',
                            ],
                            [
                                'key'     => '_tobalt_alert_end_date',
                                'value'   => '',
                                'compare' => '=',
                            ],
                        ],
                        [
                            'key'     => '_tobalt_alert_date',
                            'value'   => $filter_date,
                            'compare' => '=',
                            'type'    => 'DATE',
                        ],
                    ],
                ],
            ],
            'orderby'        => [
                'meta_value' => 'ASC',
            ],
            'meta_key'       => '_tobalt_alert_date',
        ];

        // Filter by category
        if ( $category ) {
            $args['tax_query'][] = [
                'taxonomy' => 'tobalt_alert_category',
                'field'    => 'term_id',
                'terms'    => $category,
            ];
        }

        $query  = new \WP_Query( $args );
        $alerts = [];

        foreach ( $query->posts as $post ) {
            $alerts[] = $this->format_alert( $post );
        }

        // Sort: pinned first, then by date
        usort( $alerts, function( $a, $b ) {
            if ( $a['pinned'] && ! $b['pinned'] ) return -1;
            if ( ! $a['pinned'] && $b['pinned'] ) return 1;
            return strcmp( $a['date'], $b['date'] );
        } );

        return rest_ensure_response( [
            'alerts' => $alerts,
            'total'  => count( $alerts ),
            'from'   => $from,
            'to'     => $to,
        ] );
    }

    /**
     * Format alert for API response.
     */
    private function format_alert( $post ) {
        $categories = wp_get_post_terms( $post->ID, 'tobalt_alert_category', [ 'fields' => 'all' ] );

        return [
            'id'          => $post->ID,
            'title'       => $post->post_title,
            'description' => wp_kses_post( $post->post_content ),
            'date'        => get_post_meta( $post->ID, '_tobalt_alert_date', true ),
            'time'        => get_post_meta( $post->ID, '_tobalt_alert_time', true ),
            'end_date'    => get_post_meta( $post->ID, '_tobalt_alert_end_date', true ),
            'end_time'    => get_post_meta( $post->ID, '_tobalt_alert_end_time', true ),
            'severity'    => get_post_meta( $post->ID, '_tobalt_alert_severity', true ),
            'location'    => get_post_meta( $post->ID, '_tobalt_alert_location', true ),
            'pinned'      => (bool) get_post_meta( $post->ID, '_tobalt_alert_pinned', true ),
            'categories'  => array_map( function( $term ) {
                return [
                    'id'   => $term->term_id,
                    'name' => $term->name,
                    'slug' => $term->slug,
                ];
            }, $categories ),
        ];
    }

    /**
     * Request magic link.
     */
    public function request_magic_link( $request ) {
        // Verify reCAPTCHA
        $recaptcha_token = $request->get_param( 'recaptcha_token' );
        $recaptcha_result = Recaptcha::verify( $recaptcha_token, 'request_link' );

        if ( is_wp_error( $recaptcha_result ) ) {
            return new \WP_REST_Response( [
                'success' => false,
                'message' => $recaptcha_result->get_error_message(),
            ], 400 );
        }

        $email = $request->get_param( 'email' );

        $magic_link = new Magic_Link();
        $result     = $magic_link->generate_token( $email );

        if ( is_wp_error( $result ) ) {
            return new \WP_REST_Response( [
                'success' => false,
                'message' => $result->get_error_message(),
            ], 400 );
        }

        // Send email
        $sent = $magic_link->send_magic_link( $email, $result['token'] );

        if ( ! $sent ) {
            return new \WP_REST_Response( [
                'success' => false,
                'message' => __( 'Failed to send email. Please try again.', 'tobalt-city-alerts' ),
            ], 500 );
        }

        return rest_ensure_response( [
            'success' => true,
            'message' => __( 'Magic link sent! Check your email.', 'tobalt-city-alerts' ),
        ] );
    }

    /**
     * Verify token.
     */
    public function verify_token( $request ) {
        $token = $request->get_param( 'token' );

        $magic_link = new Magic_Link();
        $result     = $magic_link->verify_token( $token );

        if ( is_wp_error( $result ) ) {
            return new \WP_REST_Response( [
                'valid'   => false,
                'message' => $result->get_error_message(),
            ], 400 );
        }

        return rest_ensure_response( [
            'valid'      => true,
            'email'      => $result['email'],
            'expires_at' => $result['expires_at'],
        ] );
    }

    /**
     * Check token permission for submissions.
     */
    public function check_token_permission( $request ) {
        $token = $request->get_param( 'token' );

        if ( empty( $token ) ) {
            return false;
        }

        $magic_link = new Magic_Link();
        $result     = $magic_link->verify_token( $token );

        return ! is_wp_error( $result );
    }

    /**
     * Submit alert.
     */
    public function submit_alert( $request ) {
        $token       = $request->get_param( 'token' );
        $title       = $request->get_param( 'title' );
        $description = $request->get_param( 'description' );
        $date        = $request->get_param( 'date' );
        $time        = $request->get_param( 'time' );
        $end_date    = $request->get_param( 'end_date' );
        $end_time    = $request->get_param( 'end_time' );
        $category    = $request->get_param( 'category' );
        $severity    = $request->get_param( 'severity' );
        $location    = $request->get_param( 'location' );

        // Verify token and get email
        $magic_link  = new Magic_Link();
        $token_data  = $magic_link->verify_token( $token );

        if ( is_wp_error( $token_data ) ) {
            return new \WP_REST_Response( [
                'success' => false,
                'message' => $token_data->get_error_message(),
            ], 400 );
        }

        $email = $token_data['email'];

        // Determine post status based on settings
        $settings    = get_option( 'tobalt_city_alerts_settings', [] );
        $post_status = ! empty( $settings['require_approval'] ) ? 'pending' : 'publish';

        // Create alert
        $post_id = wp_insert_post( [
            'post_type'    => 'tobalt_alert',
            'post_title'   => $title,
            'post_content' => $description,
            'post_status'  => $post_status,
        ] );

        if ( is_wp_error( $post_id ) ) {
            return new \WP_REST_Response( [
                'success' => false,
                'message' => __( 'Failed to create alert.', 'tobalt-city-alerts' ),
            ], 500 );
        }

        // Save meta
        update_post_meta( $post_id, '_tobalt_alert_date', $date );
        update_post_meta( $post_id, '_tobalt_alert_time', $time ?: '' );
        update_post_meta( $post_id, '_tobalt_alert_end_date', $end_date ?: '' );
        update_post_meta( $post_id, '_tobalt_alert_end_time', $end_time ?: '' );
        update_post_meta( $post_id, '_tobalt_alert_severity', $severity ?: '' );
        update_post_meta( $post_id, '_tobalt_alert_location', $location ?: '' );
        update_post_meta( $post_id, '_tobalt_alert_submitted_by', $email );

        // Assign category
        if ( $category ) {
            wp_set_post_terms( $post_id, [ $category ], 'tobalt_alert_category' );
        }

        // Mark token as used
        $magic_link->mark_token_used( $token );

        // Fire activity log event
        do_action( 'tobalt_city_alerts_alert_created', $post_id, $email );

        return rest_ensure_response( [
            'success'  => true,
            'alert_id' => $post_id,
            'status'   => $post_status,
            'message'  => 'pending' === $post_status
                ? __( 'Alert submitted! It will be published after admin review.', 'tobalt-city-alerts' )
                : __( 'Alert published successfully!', 'tobalt-city-alerts' ),
        ] );
    }

    /**
     * Get alerts submitted by the current user (by email from token).
     */
    public function get_my_alerts( $request ) {
        $token      = $request->get_param( 'token' );
        $magic_link = new Magic_Link();
        $token_data = $magic_link->get_token_data( $token );

        if ( ! $token_data || empty( $token_data['email'] ) ) {
            return new \WP_REST_Response( [
                'success' => false,
                'message' => __( 'Invalid token.', 'tobalt-city-alerts' ),
            ], 400 );
        }

        $email = $token_data['email'];

        $args = [
            'post_type'      => 'tobalt_alert',
            'post_status'    => [ 'publish', 'pending', 'draft' ],
            'posts_per_page' => 50,
            'meta_query'     => [
                [
                    'key'   => '_tobalt_alert_submitted_by',
                    'value' => $email,
                ],
            ],
            'orderby'        => 'date',
            'order'          => 'DESC',
        ];

        $query  = new \WP_Query( $args );
        $alerts = [];

        foreach ( $query->posts as $post ) {
            $alerts[] = [
                'id'          => $post->ID,
                'title'       => $post->post_title,
                'description' => wp_kses_post( $post->post_content ),
                'date'        => get_post_meta( $post->ID, '_tobalt_alert_date', true ),
                'time'        => get_post_meta( $post->ID, '_tobalt_alert_time', true ),
                'end_date'    => get_post_meta( $post->ID, '_tobalt_alert_end_date', true ),
                'end_time'    => get_post_meta( $post->ID, '_tobalt_alert_end_time', true ),
                'severity'    => get_post_meta( $post->ID, '_tobalt_alert_severity', true ),
                'location'    => get_post_meta( $post->ID, '_tobalt_alert_location', true ),
                'status'      => $post->post_status,
                'solved'      => (bool) get_post_meta( $post->ID, '_tobalt_alert_solved', true ),
                'solved_at'   => get_post_meta( $post->ID, '_tobalt_alert_solved_at', true ),
            ];
        }

        return rest_ensure_response( [
            'success' => true,
            'alerts'  => $alerts,
            'email'   => $email,
        ] );
    }

    /**
     * Update an alert (only end_date for now).
     */
    public function update_alert( $request ) {
        $alert_id   = (int) $request->get_param( 'id' );
        $token      = $request->get_param( 'token' );
        $end_date   = $request->get_param( 'end_date' );
        $end_time   = $request->get_param( 'end_time' );

        $magic_link = new Magic_Link();
        $token_data = $magic_link->get_token_data( $token );

        if ( ! $token_data || empty( $token_data['email'] ) ) {
            return new \WP_REST_Response( [
                'success' => false,
                'message' => __( 'Invalid token.', 'tobalt-city-alerts' ),
            ], 400 );
        }

        $email = $token_data['email'];

        // Check ownership
        $submitted_by = get_post_meta( $alert_id, '_tobalt_alert_submitted_by', true );
        if ( $submitted_by !== $email ) {
            return new \WP_REST_Response( [
                'success' => false,
                'message' => __( 'You can only edit your own alerts.', 'tobalt-city-alerts' ),
            ], 403 );
        }

        // Update end_date if provided
        if ( $end_date ) {
            update_post_meta( $alert_id, '_tobalt_alert_end_date', $end_date );
        }
        if ( $end_time !== null ) {
            update_post_meta( $alert_id, '_tobalt_alert_end_time', $end_time );
        }

        // Fire activity log event
        do_action( 'tobalt_city_alerts_alert_updated', $alert_id, $email );

        return rest_ensure_response( [
            'success' => true,
            'message' => __( 'Alert updated successfully.', 'tobalt-city-alerts' ),
        ] );
    }

    /**
     * Mark an alert as solved.
     */
    public function mark_alert_solved( $request ) {
        $alert_id   = (int) $request->get_param( 'id' );
        $token      = $request->get_param( 'token' );

        $magic_link = new Magic_Link();
        $token_data = $magic_link->get_token_data( $token );

        if ( ! $token_data || empty( $token_data['email'] ) ) {
            return new \WP_REST_Response( [
                'success' => false,
                'message' => __( 'Invalid token.', 'tobalt-city-alerts' ),
            ], 400 );
        }

        $email = $token_data['email'];

        // Check ownership
        $submitted_by = get_post_meta( $alert_id, '_tobalt_alert_submitted_by', true );
        if ( $submitted_by !== $email ) {
            return new \WP_REST_Response( [
                'success' => false,
                'message' => __( 'You can only mark your own alerts as solved.', 'tobalt-city-alerts' ),
            ], 403 );
        }

        // Mark as solved
        $solved_at = current_time( 'mysql' );
        update_post_meta( $alert_id, '_tobalt_alert_solved', true );
        update_post_meta( $alert_id, '_tobalt_alert_solved_at', $solved_at );

        // Change status to draft (remove from public view)
        wp_update_post( [
            'ID'          => $alert_id,
            'post_status' => 'draft',
        ] );

        // Fire activity log event
        do_action( 'tobalt_city_alerts_alert_solved', $alert_id, $email );

        return rest_ensure_response( [
            'success'   => true,
            'message'   => __( 'Alert marked as solved.', 'tobalt-city-alerts' ),
            'solved_at' => $solved_at,
        ] );
    }

    /**
     * Get categories.
     */
    public function get_categories() {
        $terms = get_terms( [
            'taxonomy'   => 'tobalt_alert_category',
            'hide_empty' => false,
        ] );

        if ( is_wp_error( $terms ) ) {
            return rest_ensure_response( [] );
        }

        return rest_ensure_response( array_map( function( $term ) {
            return [
                'id'    => $term->term_id,
                'name'  => $term->name,
                'slug'  => $term->slug,
                'count' => $term->count,
            ];
        }, $terms ) );
    }

    /**
     * Subscribe to alerts.
     */
    public function subscribe( $request ) {
        // Verify reCAPTCHA
        $recaptcha_token = $request->get_param( 'recaptcha_token' );
        $recaptcha_result = Recaptcha::verify( $recaptcha_token, 'subscribe' );

        if ( is_wp_error( $recaptcha_result ) ) {
            return new \WP_REST_Response( [
                'success' => false,
                'message' => $recaptcha_result->get_error_message(),
            ], 400 );
        }

        $email      = $request->get_param( 'email' );
        $categories = $request->get_param( 'categories' ) ?: [];

        $subscribers = new Subscribers();
        $result      = $subscribers->add( $email, $categories );

        if ( is_wp_error( $result ) ) {
            return new \WP_REST_Response( [
                'success' => false,
                'message' => $result->get_error_message(),
            ], 400 );
        }

        // Send verification email
        if ( ! empty( $result['verify_token'] ) ) {
            $this->send_verification_email( $email, $result['verify_token'] );
        }

        return rest_ensure_response( [
            'success' => true,
            'message' => __( 'Please check your email to verify your subscription.', 'tobalt-city-alerts' ),
        ] );
    }

    /**
     * Verify subscription.
     */
    public function verify_subscription( $request ) {
        $token = $request->get_param( 'token' );

        $subscribers = new Subscribers();
        $result      = $subscribers->verify( $token );

        if ( is_wp_error( $result ) ) {
            return new \WP_REST_Response( [
                'success' => false,
                'message' => $result->get_error_message(),
            ], 400 );
        }

        return rest_ensure_response( [
            'success' => true,
            'message' => __( 'Your subscription has been verified!', 'tobalt-city-alerts' ),
        ] );
    }

    /**
     * Unsubscribe.
     */
    public function unsubscribe( $request ) {
        $token = $request->get_param( 'token' );

        $subscribers = new Subscribers();
        $result      = $subscribers->unsubscribe( $token );

        if ( is_wp_error( $result ) ) {
            return new \WP_REST_Response( [
                'success' => false,
                'message' => $result->get_error_message(),
            ], 400 );
        }

        return rest_ensure_response( [
            'success' => true,
            'message' => __( 'You have been unsubscribed.', 'tobalt-city-alerts' ),
        ] );
    }

    /**
     * Send verification email.
     */
    private function send_verification_email( $email, $token ) {
        $settings   = get_option( 'tobalt_city_alerts_settings', [] );
        $from_name  = $settings['email_from_name'] ?? 'CityAlerts';
        $from_email = $settings['email_from_address'] ?: get_option( 'admin_email' );

        $verify_url = add_query_arg( [
            'tobalt_verify_sub' => 1,
            'token'             => $token,
        ], home_url( '/' ) );

        $subject = sprintf(
            /* translators: %s: site name */
            __( '[%s] Verify your subscription', 'tobalt-city-alerts' ),
            get_bloginfo( 'name' )
        );

        ob_start();
        include TOBALT_CITY_ALERTS_PATH . 'templates/email-verify-subscription.php';
        $message = ob_get_clean();

        $headers = [
            'Content-Type: text/html; charset=UTF-8',
            sprintf( 'From: %s <%s>', $from_name, $from_email ),
        ];

        return wp_mail( $email, $subject, $message, $headers );
    }
}
