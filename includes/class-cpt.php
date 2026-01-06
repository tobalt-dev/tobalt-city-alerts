<?php
/**
 * Custom Post Type registration.
 *
 * Author: Tobalt — https://tobalt.lt
 */

namespace Tobalt\CityAlerts;

defined( 'ABSPATH' ) || exit;

class CPT {

    public function __construct() {
        add_action( 'init', [ $this, 'register_post_type' ] );
        add_action( 'init', [ $this, 'register_taxonomies' ] );
    }

    /**
     * Register the alert CPT.
     */
    public function register_post_type() {
        $labels = [
            'name'                  => _x( 'Alerts', 'Post type general name', 'tobalt-city-alerts' ),
            'singular_name'         => _x( 'Alert', 'Post type singular name', 'tobalt-city-alerts' ),
            'menu_name'             => _x( 'City Alerts', 'Admin Menu text', 'tobalt-city-alerts' ),
            'add_new'               => __( 'Add New', 'tobalt-city-alerts' ),
            'add_new_item'          => __( 'Add New Alert', 'tobalt-city-alerts' ),
            'edit_item'             => __( 'Edit Alert', 'tobalt-city-alerts' ),
            'new_item'              => __( 'New Alert', 'tobalt-city-alerts' ),
            'view_item'             => __( 'View Alert', 'tobalt-city-alerts' ),
            'search_items'          => __( 'Search Alerts', 'tobalt-city-alerts' ),
            'not_found'             => __( 'No alerts found', 'tobalt-city-alerts' ),
            'not_found_in_trash'    => __( 'No alerts found in Trash', 'tobalt-city-alerts' ),
            'all_items'             => __( 'All Alerts', 'tobalt-city-alerts' ),
            'filter_items_list'     => __( 'Filter alerts list', 'tobalt-city-alerts' ),
            'items_list_navigation' => __( 'Alerts list navigation', 'tobalt-city-alerts' ),
            'items_list'            => __( 'Alerts list', 'tobalt-city-alerts' ),
        ];

        $args = [
            'labels'              => $labels,
            'public'              => false,
            'publicly_queryable'  => false,
            'show_ui'             => true,
            'show_in_menu'        => true,
            'query_var'           => false,
            'rewrite'             => false,
            'capability_type'     => 'post',
            'has_archive'         => false,
            'hierarchical'        => false,
            'menu_position'       => 25,
            'menu_icon'           => 'dashicons-megaphone',
            'supports'            => [ 'title', 'editor' ],
            'show_in_rest'        => false, // Custom REST endpoints used instead
        ];

        register_post_type( 'tobalt_alert', $args );
    }

    /**
     * Register taxonomies (v2+).
     */
    public function register_taxonomies() {
        // Alert Category (v2)
        $cat_labels = [
            'name'              => _x( 'Categories', 'taxonomy general name', 'tobalt-city-alerts' ),
            'singular_name'     => _x( 'Category', 'taxonomy singular name', 'tobalt-city-alerts' ),
            'search_items'      => __( 'Search Categories', 'tobalt-city-alerts' ),
            'all_items'         => __( 'All Categories', 'tobalt-city-alerts' ),
            'parent_item'       => __( 'Parent Category', 'tobalt-city-alerts' ),
            'parent_item_colon' => __( 'Parent Category:', 'tobalt-city-alerts' ),
            'edit_item'         => __( 'Edit Category', 'tobalt-city-alerts' ),
            'update_item'       => __( 'Update Category', 'tobalt-city-alerts' ),
            'add_new_item'      => __( 'Add New Category', 'tobalt-city-alerts' ),
            'new_item_name'     => __( 'New Category Name', 'tobalt-city-alerts' ),
            'menu_name'         => __( 'Categories', 'tobalt-city-alerts' ),
        ];

        register_taxonomy( 'tobalt_alert_category', 'tobalt_alert', [
            'labels'            => $cat_labels,
            'hierarchical'      => true,
            'public'            => false,
            'show_ui'           => true,
            'show_admin_column' => true,
            'show_in_rest'      => false,
            'rewrite'           => false,
        ] );
    }
}
