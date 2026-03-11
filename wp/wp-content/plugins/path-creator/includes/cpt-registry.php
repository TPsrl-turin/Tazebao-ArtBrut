<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * CPT Registration is handled externally (e.g., Pods).
 * This file now only handles data integrity logic.
 */

/**
 * Cascading Delete Logic
 * When a Path is deleted, delete its associated Steps.
 */
function tp_handle_path_deletion( $post_id ) {
    if ( get_post_type( $post_id ) !== 'path' ) {
        return;
    }

    // Get associated steps
    // Assuming 'steps' is stored as a meta field containing an array of IDs
    // If using Pods, it might be stored differently, but get_post_meta usually retrieves it if it's a relationship field.
    // We need to be careful: Pods relationships might be stored in a separate table or as serialized array.
    // For this implementation, we assume standard meta array or Pods compatible meta.
    
    $steps = get_post_meta( $post_id, 'steps', true ); // Adjust based on how we save it. 
    // If we save as array of IDs: [1, 2, 3]
    
    if ( ! empty( $steps ) && is_array( $steps ) ) {
        foreach ( $steps as $step_id ) {
            // Check if it's actually a step post
            if ( get_post_type( $step_id ) === 'step' ) {
                wp_delete_post( $step_id, true ); // Force delete
            }
        }
    }
}
add_action( 'before_delete_post', 'tp_handle_path_deletion' );
