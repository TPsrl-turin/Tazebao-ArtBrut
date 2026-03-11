<?php
/**
 * Plugin Name: Path & Steps Manager
 * Description: Allows users to create and manage Paths and nested Steps via frontend.
 * Version: 1.0.0
 * Author: TP Design
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'TP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'TP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Include required files
require_once TP_PLUGIN_DIR . 'includes/cpt-registry.php';
require_once TP_PLUGIN_DIR . 'includes/frontend-dashboard.php';
require_once TP_PLUGIN_DIR . 'includes/ajax-handler.php';

/**
 * Enqueue scripts and styles
 */
function tp_enqueue_scripts() {
    // Enqueue only on pages with the shortcode or specific pages if needed
    // For now, we'll enqueue globally or check for shortcode presence in a more advanced way later.
    // A simple check is to enqueue if we are not in admin.
    if ( ! is_admin() ) {
        // Register Splide in case it's not registered by the theme yet
        if ( ! wp_script_is( 'splide', 'registered' ) ) {
            wp_register_script( 'splide', 'https://cdn.jsdelivr.net/npm/@splidejs/splide@4.1.4/dist/js/splide.min.js', array(), '4.1.4', true );
            wp_register_style( 'splide-core', 'https://cdn.jsdelivr.net/npm/@splidejs/splide@4.1.4/dist/css/splide.min.css', array(), '4.1.4' );
        }

        wp_enqueue_style( 'tp-frontend-css', TP_PLUGIN_URL . 'assets/css/tp-frontend.css' );
        wp_enqueue_script( 'tp-frontend-js', TP_PLUGIN_URL . 'assets/js/tp-frontend.js', array( 'jquery', 'splide' ), '1.0.0', true );

        // Localize script for AJAX
        wp_localize_script( 'tp-frontend-js', 'tp_ajax_obj', array(
            'ajax_url'      => admin_url( 'admin-ajax.php' ),
            'nonce'         => wp_create_nonce( 'tp_ajax_nonce' ),
            'is_logged_in'  => is_user_logged_in()
        ));
        
        // Enqueue Select2 for items selection (optional, but recommended for UX)
        wp_enqueue_style( 'tp-select2-css', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css' );
        wp_enqueue_script( 'tp-select2-js', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', array( 'jquery' ), '4.1.0', true );
    }
}
add_action( 'wp_enqueue_scripts', 'tp_enqueue_scripts' );


