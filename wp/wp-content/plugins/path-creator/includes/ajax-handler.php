<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handle AJAX Step Creation
 */
function tp_create_step_ajax() {
    check_ajax_referer( 'tp_ajax_nonce', 'nonce' );
    if ( ! is_user_logged_in() ) {
        wp_send_json_error( 'Not logged in' );
    }

    $step_id = isset( $_POST['step_id'] ) ? intval( $_POST['step_id'] ) : 0;
    $title = sanitize_text_field( $_POST['step_title'] );
    $content_1 = wp_kses_post( $_POST['content_1'] );
    $content_2 = wp_kses_post( $_POST['content_2'] );
    $bg_color = sanitize_hex_color( $_POST['step_bg_color'] );
    $text_color = sanitize_hex_color( $_POST['step_text_color'] );
    $items = isset( $_POST['step_items'] ) ? array_map( 'intval', $_POST['step_items'] ) : array();

    $data = array(
        'post_title'       => $title,
        'post_type'        => 'step',
        'post_status'      => 'publish',
        'post_author'      => get_current_user_id(),
        'content_1'        => $content_1,
        'content_2'        => $content_2,
        'background_color' => $bg_color,
        'text_color'       => $text_color,
        'items'            => $items,
    );

    if ( $step_id ) {
        $data['ID'] = $step_id;
    }

    if ( function_exists( 'pods' ) ) {
        $pod = pods( 'step', $step_id ?: null );
        $step_id = $pod->save( $data );
    } else {
        if ( $step_id ) {
            wp_update_post( array( 'ID' => $step_id, 'post_title' => $title ) );
        } else {
            $step_id = wp_insert_post( array(
                'post_title'   => $title,
                'post_type'    => 'step',
                'post_status'  => 'publish',
                'post_author'  => get_current_user_id(),
            ) );
        }

        if ( $step_id ) {
            update_post_meta( $step_id, 'content_1', $content_1 );
            update_post_meta( $step_id, 'content_2', $content_2 );
            update_post_meta( $step_id, 'background_color', $bg_color );
            update_post_meta( $step_id, 'text_color', $text_color );
            update_post_meta( $step_id, 'items', $items );
        }
    }

    if ( $step_id ) {
        wp_send_json_success( array(
            'id' => $step_id,
            'title' => $title
        ) );
    } else {
        wp_send_json_error( 'Failed to save step' );
    }
}
add_action( 'wp_ajax_tp_create_step', 'tp_create_step_ajax' );

/**
 * Handle AJAX Step Fetching
 */
function tp_get_step_ajax() {
    check_ajax_referer( 'tp_ajax_nonce', 'nonce' );
    if ( ! is_user_logged_in() ) {
        wp_send_json_error( 'Not logged in' );
    }

    $step_id = isset( $_GET['step_id'] ) ? intval( $_GET['step_id'] ) : 0;
    if ( ! $step_id ) {
        wp_send_json_error( 'Invalid Step ID' );
    }

    $post = get_post( $step_id );
    if ( ! $post || $post->post_type !== 'step' ) {
        wp_send_json_error( 'Step not found' );
    }

    $data = array(
        'id'               => $step_id,
        'title'            => $post->post_title,
        'content_1'        => '',
        'content_2'        => '',
        'background_color' => '#ffffff',
        'text_color'       => '#000000',
        'items'            => array(),
    );

    if ( function_exists( 'pods' ) ) {
        $pod = pods( 'step', $step_id );
        if ( $pod && $pod->exists() ) {
            $data['content_1'] = $pod->field( 'content_1' );
            $data['content_2'] = $pod->field( 'content_2' );
            $data['background_color'] = $pod->field( 'background_color' ) ?: '#ffffff';
            $data['text_color'] = $pod->field( 'text_color' ) ?: '#000000';
            $items_raw = $pod->field( 'items' );
            if ( is_array( $items_raw ) ) {
                foreach ( $items_raw as $item ) {
                    if ( is_array( $item ) && isset( $item['ID'] ) ) {
                        $data['items'][] = array(
                            'id' => $item['ID'],
                            'text' => get_the_title( $item['ID'] )
                        );
                    } elseif ( is_numeric( $item ) ) {
                        $data['items'][] = array(
                            'id' => $item,
                            'text' => get_the_title( $item )
                        );
                    }
                }
            }
        }
    } else {
        $data['content_1'] = get_post_meta( $step_id, 'content_1', true );
        $data['content_2'] = get_post_meta( $step_id, 'content_2', true );
        $data['background_color'] = get_post_meta( $step_id, 'background_color', true ) ?: '#ffffff';
        $data['text_color'] = get_post_meta( $step_id, 'text_color', true ) ?: '#000000';
        $items = get_post_meta( $step_id, 'items', true );
        if ( is_array( $items ) ) {
            foreach ( $items as $id ) {
                $data['items'][] = array(
                    'id' => $id,
                    'text' => get_the_title( $id )
                );
            }
        }
    }

    wp_send_json_success( $data );
}
add_action( 'wp_ajax_tp_get_step', 'tp_get_step_ajax' );

/**
 * Handle AJAX Items Fetching
 */
function tp_get_items_ajax() {
    // check_ajax_referer( 'tp_ajax_nonce', 'nonce' ); // Optional for read-only but good practice

    $filter = isset( $_GET['filter'] ) ? sanitize_key( $_GET['filter'] ) : 'all';
    $search = isset( $_GET['term'] ) ? sanitize_text_field( $_GET['term'] ) : '';

    $args = array(
        'post_type'      => 'item', // Updated to 'item' CPT
        'posts_per_page' => 20,
        's'              => $search,
        'post_status'    => 'publish',
    );

    if ( $filter === 'favorites' ) {
        if ( is_user_logged_in() ) {
            $user_id = get_current_user_id();
            $saved_items = get_user_meta( $user_id, 'favorite', false );
            
            if ( ! empty( $saved_items ) ) {
                $args['post__in'] = array_map( 'intval', $saved_items );
            } else {
                // User has no favorites, return no results
                $args['post__in'] = array( 0 );
            }
        } else {
            // Not logged in, return no results
            $args['post__in'] = array( 0 );
        }
    }

    $query = new WP_Query( $args );
    $results = array();

    if ( $query->have_posts() ) {
        while ( $query->have_posts() ) {
            $query->the_post();
            $results[] = array(
                'id'    => get_the_ID(),
                'text'  => get_the_title(),
                'image' => get_the_post_thumbnail_url( get_the_ID(), array( 80, 80 ) ) ?: '',
            );
        }
    }

    wp_send_json( array( 'results' => $results ) );
}
add_action( 'wp_ajax_tp_get_items', 'tp_get_items_ajax' );
add_action( 'wp_ajax_nopriv_tp_get_items', 'tp_get_items_ajax' );

/**
 * Handle Toggle Favorite AJAX
 */
function tp_toggle_favorite_ajax() {
    check_ajax_referer( 'tp_ajax_nonce', 'nonce' );

    if ( ! is_user_logged_in() ) {
        wp_send_json_error( 'Not logged in' );
    }

    $item_id = isset( $_POST['item_id'] ) ? intval( $_POST['item_id'] ) : 0;
    if ( ! $item_id ) {
        wp_send_json_error( 'Invalid Item ID' );
    }

    $user_id = get_current_user_id();
    $item_id = intval( $item_id );
    
    // Check if already favorited (stored as multiple entries)
    $all_favs = get_user_meta( $user_id, 'favorite', false );
    $is_favorited = in_array( $item_id, array_map( 'intval', $all_favs ), true );

    $action = 'added';
    if ( $is_favorited ) {
        // Remove all instances (to be safe)
        delete_user_meta( $user_id, 'favorite', $item_id );
        $action = 'removed';
    } else {
        // Add
        add_user_meta( $user_id, 'favorite', $item_id );
        $action = 'added';
    }

    wp_send_json_success( array(
        'action' => $action,
        'count' => count( get_user_meta( $user_id, 'favorite', false ) )
    ) );
}
add_action( 'wp_ajax_tp_toggle_favorite', 'tp_toggle_favorite_ajax' );