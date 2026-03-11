<?php

/**
 * Render Popups in Footer
 */
function wgp_render_popups() {
    $args = array(
        'post_type'        => 'wgp_popup',
        'posts_per_page'   => -1,
        'post_status'      => 'publish',
        'suppress_filters' => false, // Important for WPML/Polylang
    );

    $popups = new WP_Query( $args );

    if ( $popups->have_posts() ) {
        while ( $popups->have_posts() ) {
            $popups->the_post();
            $popup_id = get_the_ID();

            // Check Targeting
            if ( ! wgp_should_show_popup( $popup_id ) ) {
                $debug_rule = get_post_meta( $popup_id, '_wgp_target_rule', true );
                $debug_pages = json_encode( get_post_meta( $popup_id, '_wgp_target_pages', true ) );
                $current_id = get_queried_object_id();
                echo "<!-- WGP: Popup {$popup_id} skipped. Current ID: {$current_id} | Rule: {$debug_rule} | Targets: {$debug_pages} -->";
                continue;
            }
            
            echo "<!-- WGP: Popup {$popup_id} rendered. -->";

            // Get Settings
            $display_mode = get_post_meta( $popup_id, '_wgp_display_mode', true );
            $delay = get_post_meta( $popup_id, '_wgp_delay', true );
            $animation_origin = get_post_meta( $popup_id, '_wgp_animation_origin', true );
            
            // Positioning for Direct Mode
            $pos_style = '';
            if ( $display_mode === 'direct' ) {
                $top = get_post_meta( $popup_id, '_wgp_position_top', true );
                $bottom = get_post_meta( $popup_id, '_wgp_position_bottom', true );
                $left = get_post_meta( $popup_id, '_wgp_position_left', true );
                $right = get_post_meta( $popup_id, '_wgp_position_right', true );

                if ( $top ) $pos_style .= "top: {$top}; ";
                if ( $bottom ) $pos_style .= "bottom: {$bottom}; ";
                if ( $left ) $pos_style .= "left: {$left}; ";
                if ( $right ) $pos_style .= "right: {$right}; ";
            }

            // Render HTML
            ?>
            <div class="wgp-popup-wrapper wgp-mode-<?php echo esc_attr( $display_mode ); ?> wgp-anim-<?php echo esc_attr( $animation_origin ); ?>"
                 id="wgp-popup-<?php echo esc_attr( $popup_id ); ?>"
                 data-delay="<?php echo esc_attr( $delay ); ?>"
                 data-popup-id="<?php echo esc_attr( $popup_id ); ?>"
                 style="display: none; <?php echo ( $display_mode === 'direct' ) ? esc_attr( $pos_style ) : ''; ?>">
                
                <?php if ( $display_mode === 'lightbox' ) : ?>
                    <div class="wgp-popup-overlay"></div>
                <?php endif; ?>

                <div class="wgp-popup-content">
                    <?php the_content(); ?>
                </div>
            </div>
            <?php
        }
        wp_reset_postdata();
    }
}
add_action( 'wp_footer', 'wgp_render_popups' );

/**
 * Check Targeting Logic
 */
function wgp_should_show_popup( $popup_id ) {
    // Don't show in admin
    if ( is_admin() ) return false;

    $target_rule = get_post_meta( $popup_id, '_wgp_target_rule', true );
    $target_pages = get_post_meta( $popup_id, '_wgp_target_pages', true ); // Array of IDs
    
    if ( ! is_array( $target_pages ) ) $target_pages = array();
    // Ensure IDs are integers
    $target_pages = array_map( 'intval', $target_pages );

    $current_id = get_queried_object_id();

    if ( $target_rule === 'all' ) {
        return true;
    } elseif ( $target_rule === 'specific' ) {
        return in_array( $current_id, $target_pages, true );
    } elseif ( $target_rule === 'exclude' ) {
        return ! in_array( $current_id, $target_pages, true );
    }

    return false;
}
