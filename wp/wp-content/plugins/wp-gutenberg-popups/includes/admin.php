<?php

/**
 * Add Meta Boxes
 */
function wgp_add_meta_boxes() {
    add_meta_box(
        'wgp_popup_settings',
        __( 'Popup Settings', 'wgp' ),
        'wgp_render_meta_box',
        'wgp_popup',
        'normal',
        'high'
    );
}
add_action( 'add_meta_boxes', 'wgp_add_meta_boxes' );

/**
 * Render Meta Box
 */
function wgp_render_meta_box( $post ) {
    wp_nonce_field( 'wgp_save_meta_box_data', 'wgp_meta_box_nonce' );

    $display_mode = get_post_meta( $post->ID, '_wgp_display_mode', true );
    $position_top = get_post_meta( $post->ID, '_wgp_position_top', true );
    $position_bottom = get_post_meta( $post->ID, '_wgp_position_bottom', true );
    $position_left = get_post_meta( $post->ID, '_wgp_position_left', true );
    $position_right = get_post_meta( $post->ID, '_wgp_position_right', true );
    $animation_origin = get_post_meta( $post->ID, '_wgp_animation_origin', true );
    $delay = get_post_meta( $post->ID, '_wgp_delay', true );
    $target_rule = get_post_meta( $post->ID, '_wgp_target_rule', true );
    $target_pages = get_post_meta( $post->ID, '_wgp_target_pages', true ); // Array of IDs

    if ( empty( $display_mode ) ) $display_mode = 'lightbox';
    if ( empty( $target_rule ) ) $target_rule = 'all';
    if ( empty( $target_pages ) ) $target_pages = array();

    ?>
    <div class="wgp-meta-box">
        <!-- Display Mode -->
        <p>
            <label for="wgp_display_mode"><strong><?php _e( 'Display Mode', 'wgp' ); ?></strong></label><br>
            <select name="wgp_display_mode" id="wgp_display_mode">
                <option value="lightbox" <?php selected( $display_mode, 'lightbox' ); ?>><?php _e( 'Lightbox / Shadowbox (Centered)', 'wgp' ); ?></option>
                <option value="direct" <?php selected( $display_mode, 'direct' ); ?>><?php _e( 'Direct Positioning', 'wgp' ); ?></option>
            </select>
        </p>

        <!-- Direct Positioning Fields -->
        <div id="wgp-direct-settings" style="<?php echo ( $display_mode === 'direct' ) ? '' : 'display:none;'; ?>">
            <p><strong><?php _e( 'Positioning (Value + Unit, e.g., 20px, 2rem)', 'wgp' ); ?></strong></p>
            <div class="wgp-flex-row">
                <p>
                    <label for="wgp_position_top"><?php _e( 'Top', 'wgp' ); ?></label>
                    <input type="text" name="wgp_position_top" id="wgp_position_top" value="<?php echo esc_attr( $position_top ); ?>">
                </p>
                <p>
                    <label for="wgp_position_bottom"><?php _e( 'Bottom', 'wgp' ); ?></label>
                    <input type="text" name="wgp_position_bottom" id="wgp_position_bottom" value="<?php echo esc_attr( $position_bottom ); ?>">
                </p>
                <p>
                    <label for="wgp_position_left"><?php _e( 'Left', 'wgp' ); ?></label>
                    <input type="text" name="wgp_position_left" id="wgp_position_left" value="<?php echo esc_attr( $position_left ); ?>">
                </p>
                <p>
                    <label for="wgp_position_right"><?php _e( 'Right', 'wgp' ); ?></label>
                    <input type="text" name="wgp_position_right" id="wgp_position_right" value="<?php echo esc_attr( $position_right ); ?>">
                </p>
            </div>
            
            <p>
                <label for="wgp_animation_origin"><strong><?php _e( 'Entrance Animation From', 'wgp' ); ?></strong></label><br>
                <select name="wgp_animation_origin" id="wgp_animation_origin">
                    <option value="top" <?php selected( $animation_origin, 'top' ); ?>><?php _e( 'Top', 'wgp' ); ?></option>
                    <option value="bottom" <?php selected( $animation_origin, 'bottom' ); ?>><?php _e( 'Bottom', 'wgp' ); ?></option>
                    <option value="left" <?php selected( $animation_origin, 'left' ); ?>><?php _e( 'Left', 'wgp' ); ?></option>
                    <option value="right" <?php selected( $animation_origin, 'right' ); ?>><?php _e( 'Right', 'wgp' ); ?></option>
                </select>
            </p>
        </div>

        <hr>

        <!-- Delay -->
        <p>
            <label for="wgp_delay"><strong><?php _e( 'Delay (Seconds)', 'wgp' ); ?></strong></label><br>
            <input type="number" step="0.1" min="0" name="wgp_delay" id="wgp_delay" value="<?php echo esc_attr( $delay ); ?>">
        </p>

        <hr>

        <!-- Page Targeting -->
        <p>
            <label for="wgp_target_rule"><strong><?php _e( 'Show On', 'wgp' ); ?></strong></label><br>
            <select name="wgp_target_rule" id="wgp_target_rule">
                <option value="all" <?php selected( $target_rule, 'all' ); ?>><?php _e( 'All Pages', 'wgp' ); ?></option>
                <option value="specific" <?php selected( $target_rule, 'specific' ); ?>><?php _e( 'Specific Pages', 'wgp' ); ?></option>
                <option value="exclude" <?php selected( $target_rule, 'exclude' ); ?>><?php _e( 'All Pages Except...', 'wgp' ); ?></option>
            </select>
        </p>

        <div id="wgp-page-selection" style="<?php echo ( $target_rule !== 'all' ) ? '' : 'display:none;'; ?>">
            <p>
                <label for="wgp_target_pages"><strong><?php _e( 'Select Pages', 'wgp' ); ?></strong></label><br>
                <select name="wgp_target_pages[]" id="wgp_target_pages" multiple="multiple" style="width: 100%;">
                    <?php
                    if ( ! empty( $target_pages ) ) {
                        foreach ( $target_pages as $page_id ) {
                            echo '<option value="' . esc_attr( $page_id ) . '" selected="selected">' . get_the_title( $page_id ) . '</option>';
                        }
                    }
                    ?>
                </select>
            </p>
        </div>

    </div>
    <?php
}

/**
 * Save Meta Box Data
 */
function wgp_save_meta_box_data( $post_id ) {
    if ( ! isset( $_POST['wgp_meta_box_nonce'] ) ) return;
    if ( ! wp_verify_nonce( $_POST['wgp_meta_box_nonce'], 'wgp_save_meta_box_data' ) ) return;
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
    if ( ! current_user_can( 'edit_post', $post_id ) ) return;

    // Save fields
    $fields = array(
        '_wgp_display_mode',
        '_wgp_position_top',
        '_wgp_position_bottom',
        '_wgp_position_left',
        '_wgp_position_right',
        '_wgp_animation_origin',
        '_wgp_delay',
        '_wgp_target_rule',
        // '_wgp_target_pages' removed from here to handle separately
    );

    foreach ( $fields as $field ) {
        $key = substr( $field, 1 ); // Remove leading underscore: _wgp_target_rule -> wgp_target_rule
        if ( isset( $_POST[ $key ] ) ) {
            update_post_meta( $post_id, $field, sanitize_text_field( $_POST[ $key ] ) );
        } else {
            delete_post_meta( $post_id, $field );
        }
    }

    // Save Target Pages (Array)
    if ( isset( $_POST['wgp_target_pages'] ) ) {
        $pages = array_map( 'intval', $_POST['wgp_target_pages'] );
        update_post_meta( $post_id, '_wgp_target_pages', $pages );
    } else {
        delete_post_meta( $post_id, '_wgp_target_pages' );
    }
}
add_action( 'save_post', 'wgp_save_meta_box_data' );

/**
 * AJAX Handler for Select2 Page Search
 */
function wgp_ajax_search_pages() {
    $term = isset( $_GET['term'] ) ? sanitize_text_field( $_GET['term'] ) : '';
    
    $args = array(
        'post_type'      => array( 'page', 'post' ), // Search pages and posts
        'post_status'    => 'publish',
        's'              => $term,
        'posts_per_page' => 20,
    );

    $query = new WP_Query( $args );
    $results = array();

    if ( $query->have_posts() ) {
        while ( $query->have_posts() ) {
            $query->the_post();
            $results[] = array(
                'id'   => get_the_ID(),
                'text' => get_the_title(),
            );
        }
    }

    wp_send_json( array( 'results' => $results ) );
}
add_action( 'wp_ajax_wgp_search_pages', 'wgp_ajax_search_pages' );
