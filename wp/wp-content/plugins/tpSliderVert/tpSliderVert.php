<?php
/*
Plugin Name: TP Slider Verticale
Description: Blocco Gutenberg per slider verticale a tre colonne.
Version: 2.0
Author: TP Design s.r.l.
*/

if ( ! defined( 'ABSPATH' ) ) exit;

function tp_slider_vert_register_block() {
    // CSS + JS frontend slider
    wp_register_style(
        'tp-slider-vert-style',
        plugins_url('assets/slider.css', __FILE__),
        [],
        '1.0'
    );

    wp_register_script(
        'tp-slider-vert-script',
        plugins_url('assets/slider.js', __FILE__),
        [],
        '1.0',
        true
    );

    wp_register_script(
        'tp-slider-vert-block-editor',
        plugins_url('index.js', __FILE__),
        ['wp-blocks', 'wp-element', 'wp-editor', 'wp-components', 'wp-i18n', 'wp-block-editor'],
        filemtime( plugin_dir_path(__FILE__) . 'index.js' ),
        true
    );

    register_block_type('tpdesign/tp-slider-vert', [
        'editor_script'   => 'tp-slider-vert-block-editor',
        'render_callback' => 'tp_slider_vert_render_callback',
        'attributes'      => [
            'firstSlider'   => [ 'type' => 'array',  'default' => [] ],
            'secondSlider'  => [ 'type' => 'array',  'default' => [] ],
            'thirdSlider'   => [ 'type' => 'array',  'default' => [] ],
            'speed1'        => [ 'type' => 'number', 'default' => 40 ],
            'speed2'        => [ 'type' => 'number', 'default' => 45 ],
            'speed3'        => [ 'type' => 'number', 'default' => 50 ],
            'mobileHeight'  => [ 'type' => 'number', 'default' => 100 ],
        ]
    ]);
}
add_action('init', 'tp_slider_vert_register_block');

require_once plugin_dir_path(__FILE__) . 'render.php';
