<?php
/**
 * Plugin Name: WP Gutenberg Popups
 * Description: Create popups using Gutenberg patterns with advanced display options.
 * Version: 1.0.0
 * Author: Antigravity
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'WGP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WGP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Include required files
require_once WGP_PLUGIN_DIR . 'includes/admin.php';
require_once WGP_PLUGIN_DIR . 'includes/frontend.php';

/**
 * Enqueue scripts and styles
 */
function wgp_enqueue_scripts() {
    // Admin scripts
    if ( is_admin() ) {
        wp_enqueue_style( 'wgp-select2-css', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css' );
        wp_enqueue_script( 'wgp-select2-js', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', array( 'jquery' ), '4.1.0', true );
        wp_enqueue_script( 'wgp-admin-js', WGP_PLUGIN_URL . 'assets/js/admin.js', array( 'jquery', 'wgp-select2-js' ), '1.0.0', true );
        wp_enqueue_style( 'wgp-admin-css', WGP_PLUGIN_URL . 'assets/css/admin.css' );
    } else {
        // Frontend scripts
        wp_enqueue_style( 'wgp-frontend-css', WGP_PLUGIN_URL . 'assets/css/style.css' );
        wp_enqueue_script( 'wgp-frontend-js', WGP_PLUGIN_URL . 'assets/js/frontend.js', array( 'jquery' ), '1.0.0', true );
    }
}
add_action( 'admin_enqueue_scripts', 'wgp_enqueue_scripts' );
add_action( 'wp_enqueue_scripts', 'wgp_enqueue_scripts' );

/**
 * Register Custom Post Type
 */
function wgp_register_cpt() {
    $labels = array(
        'name'                  => _x( 'Popups', 'Post Type General Name', 'wgp' ),
        'singular_name'         => _x( 'Popup', 'Post Type Singular Name', 'wgp' ),
        'menu_name'             => __( 'Popups', 'wgp' ),
        'name_admin_bar'        => __( 'Popup', 'wgp' ),
        'archives'              => __( 'Popup Archives', 'wgp' ),
        'attributes'            => __( 'Popup Attributes', 'wgp' ),
        'parent_item_colon'     => __( 'Parent Popup:', 'wgp' ),
        'all_items'             => __( 'All Popups', 'wgp' ),
        'add_new_item'          => __( 'Add New Popup', 'wgp' ),
        'add_new'               => __( 'Add New', 'wgp' ),
        'new_item'              => __( 'New Popup', 'wgp' ),
        'edit_item'             => __( 'Edit Popup', 'wgp' ),
        'update_item'           => __( 'Update Popup', 'wgp' ),
        'view_item'             => __( 'View Popup', 'wgp' ),
        'view_items'            => __( 'View Popups', 'wgp' ),
        'search_items'          => __( 'Search Popup', 'wgp' ),
        'not_found'             => __( 'Not found', 'wgp' ),
        'not_found_in_trash'    => __( 'Not found in Trash', 'wgp' ),
        'featured_image'        => __( 'Featured Image', 'wgp' ),
        'set_featured_image'    => __( 'Set featured image', 'wgp' ),
        'remove_featured_image' => __( 'Remove featured image', 'wgp' ),
        'use_featured_image'    => __( 'Use as featured image', 'wgp' ),
        'insert_into_item'      => __( 'Insert into popup', 'wgp' ),
        'uploaded_to_this_item' => __( 'Uploaded to this popup', 'wgp' ),
        'items_list'            => __( 'Popups list', 'wgp' ),
        'items_list_navigation' => __( 'Popups list navigation', 'wgp' ),
        'filter_items_list'     => __( 'Filter popups list', 'wgp' ),
    );
    $args = array(
        'label'                 => __( 'Popup', 'wgp' ),
        'description'           => __( 'Popup Custom Post Type', 'wgp' ),
        'labels'                => $labels,
        'supports'              => array( 'title', 'editor', 'revisions', 'custom-fields' ), // Added custom-fields support
        'hierarchical'          => false,
        'public'                => true, // Changed to true for WPML detection
        'show_ui'               => true,
        'show_in_menu'          => true,
        'menu_position'         => 5,
        'menu_icon'             => 'dashicons-megaphone',
        'show_in_admin_bar'     => true,
        'show_in_nav_menus'     => false,
        'can_export'            => true,
        'has_archive'           => false,
        'exclude_from_search'   => true,
        'publicly_queryable'    => true, // Changed to true to allow previewing
        'capability_type'       => 'page',
        'show_in_rest'          => true,
    );
    register_post_type( 'wgp_popup', $args );
}
add_action( 'init', 'wgp_register_cpt' );
