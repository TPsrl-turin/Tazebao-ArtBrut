<?php
/*
Plugin Name: TP Slider Orizzontale
Description: Blocco Gutenberg dinamico per slider immagini selezionate via Media Library
Version: 1.0
Author: TP Design s.r.l.
*/

if ( ! defined( 'ABSPATH' ) ) exit;

function tp_slider_oriz_register_assets() {
    // Splide CSS & JS + AutoScroll Extension
    wp_register_style( 'splide-core', 'https://cdn.jsdelivr.net/npm/@splidejs/splide@4.1.4/dist/css/splide.min.css', [], '4.1.4' );
    wp_register_script( 'splide-js', 'https://cdn.jsdelivr.net/npm/@splidejs/splide@4.1.4/dist/js/splide.min.js', [], '4.1.4', true );
    wp_register_script( 'splide-autoscroll', 'https://cdn.jsdelivr.net/npm/@splidejs/splide-extension-auto-scroll@0.4.1/dist/js/splide-extension-auto-scroll.min.js', [], '0.4.1', true );

    wp_register_script( 'tp-slider-oriz-frontend', plugins_url( 'block/frontend.js', __FILE__ ), [ 'splide-js', 'splide-autoscroll' ], filemtime( plugin_dir_path( __FILE__ ) . 'block/frontend.js' ), true );
    wp_register_style( 'tp-slider-oriz-style', plugins_url( 'block/frontend.css', __FILE__ ), [], filemtime( plugin_dir_path( __FILE__ ) . 'block/frontend.css' ) );
}
add_action( 'wp_enqueue_scripts', 'tp_slider_oriz_register_assets' );

function tp_slider_oriz_register_block() {
    wp_register_script(
        'tp-slider-oriz-editor',
        plugins_url( 'block/editor.js', __FILE__ ),
        array( 'wp-blocks', 'wp-element', 'wp-editor', 'wp-components', 'wp-i18n' ),
        filemtime( plugin_dir_path( __FILE__ ) . 'block/editor.js' )
    );

    register_block_type( 'tp/slider-oriz', array(
        'editor_script'   => 'tp-slider-oriz-editor',
        'render_callback' => 'tp_slider_oriz_render_callback',
        'attributes'      => array(
            'images' => array(
                'type'    => 'array',
                'default' => array(),
                'items'   => array(
                    'type' => 'object',
                ),
            ),
        ),
    ) );
}
add_action( 'init', 'tp_slider_oriz_register_block' );

function tp_slider_oriz_render_callback( $attributes ) {
    if ( empty( $attributes['images'] ) ) return '';

    wp_enqueue_style( 'splide-core' );
    wp_enqueue_script( 'splide-js' );
    wp_enqueue_script( 'splide-autoscroll' );
    wp_enqueue_script( 'tp-slider-oriz-frontend' );
    wp_enqueue_style( 'tp-slider-oriz-style' );

    ob_start();
    echo '<div class="splide tp-slider-oriz is-auto" aria-label="Slider">';
    echo '<div class="splide__track"><ul class="splide__list">';

    foreach ( $attributes['images'] as $img ) {
        $url      = esc_url( $img['url'] );
        $alt      = get_post_meta( $img['id'], '_wp_attachment_image_alt', true ) ?? '';
        $item_id  = get_post_meta( $img['id'], 'item_link', true );
        $item_url = $item_id ? esc_url( get_permalink( $item_id ) ) : null;

        echo "<li class='splide__slide'>";
        if ( $item_url ) {
            echo "<a href='{$item_url}'>";
        }
        echo "<figure>";
        echo "<img src='{$url}' alt='{$alt}' />";
        echo "</figure>";
        if ( $item_url ) {
            echo "</a>";
        }
        echo "</li>";
    }

    echo '</ul></div></div>';
    return ob_get_clean();
}

//Gestione link nel media popup
add_filter( 'attachment_fields_to_edit', function ( $form_fields, $post ) {
    $item_id = get_post_meta( $post->ID, 'item_link', true );

    $form_fields['item_link'] = [
        'label' => 'Item link',
        'input' => 'html',
        'html'  => wp_dropdown_pages([
            'post_type'        => 'item',
            'name'             => 'attachments[' . $post->ID . '][item_link]',
            'echo'             => 0,
            'show_option_none' => '— Nessuno —',
            'selected'         => $item_id,
            'class'            => 'tp-media-item-select', // <-- classe CSS aggiunta
        ]),
        'helps' => 'Select the item linked to this image',
    ];

    return $form_fields;
}, 10, 2 );

//salvataggio valore
add_filter( 'attachment_fields_to_save', function ( $post, $attachment ) {
    if ( isset( $attachment['item_link'] ) ) {
        update_post_meta( $post['ID'], 'item_link', intval( $attachment['item_link'] ) );
    }
    return $post;
}, 10, 2 );

//Stile label
add_action('admin_head', function () {
    echo '<style>.tp-media-item-select { max-width: 100%; width: 100%; }</style>';
});
