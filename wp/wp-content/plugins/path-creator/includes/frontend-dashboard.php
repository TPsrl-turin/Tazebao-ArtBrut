<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Shortcode: [tp_user_dashboard]
 */
function tp_user_dashboard_shortcode() {
    if ( ! is_user_logged_in() ) {
        return '<p>' . __( 'You must be logged in to view your dashboard.', 'tp' ) . '</p>';
    }

    ob_start();

    // Handle Actions (Edit/Delete)
    $action = isset( $_GET['tp_action'] ) ? sanitize_key( $_GET['tp_action'] ) : '';
    $path_id = isset( $_GET['path_id'] ) ? intval( $_GET['path_id'] ) : 0;

    echo '<div id="tp-path-form-section" class="tp-dashboard-wrapper">';
    if ( $action === 'edit' && $path_id ) {
        tp_render_path_form( $path_id );
    } elseif ( $action === 'new' ) {
        tp_render_path_form();
    } else {
        tp_render_dashboard_list();
    }
    echo '</div>';

    return ob_get_clean();
}
add_shortcode( 'tp_user_dashboard', 'tp_user_dashboard_shortcode' );

/**
 * Render Dashboard List
 */
function tp_render_dashboard_list() {
    $current_user_id = get_current_user_id();
    $max_paths = 4; // Default limit
    // Allow overriding via filter
    $max_paths = apply_filters( 'tp_max_user_paths', $max_paths, $current_user_id );

    $args = array(
        'post_type'      => 'path',
        'author'         => $current_user_id,
        'posts_per_page' => -1, // Get all to count
        'post_status'    => 'publish',
    );
    $query = new WP_Query( $args );
    $path_count = $query->found_posts;

    echo '<div class="tp-dashboard" data-path-count="' . esc_attr( $path_count ) . '" data-max-paths="' . esc_attr( $max_paths ) . '">';
    
    // Hidden creation URL for JS to use
    $create_url = esc_url( add_query_arg( 'tp_action', 'new') ) . '#tp-path-form-section';
    echo '<input type="hidden" id="tp-create-path-url" value="' . $create_url . '">';

    if ( $query->have_posts() ) {
        // Add filter to inject buttons into the card
        add_filter( 'path/card/action_section', 'tp_add_dashboard_actions', 10, 2 );

        echo '<div class="tp-dashboard-slider splide">';
        echo '<div class="splide__track">';
        echo '<ul class="splide__list">';
        
        while ( $query->have_posts() ) {
            $query->the_post();
            echo '<li class="splide__slide">';
            if ( function_exists( 'get_path_card' ) ) {
                echo get_path_card( get_the_ID() );
            } else {
                echo '<div class="brut-path-card"><strong>' . get_the_title() . '</strong></div>';
            }
            echo '</li>';
        }
        
        echo '</ul>';
        echo '</div>'; // .splide__track
        echo '</div>'; // .splide

        // Remove filter after use
        remove_filter( 'path/card/action_section', 'tp_add_dashboard_actions', 10 );

        wp_reset_postdata();
    } else {
        echo '<p>' . __( 'You have not created any paths yet.', 'tp' ) . '</p>';
    }
    
    // Limit Reached Modal
    ?>
    <div id="tp-limit-modal" class="tp-modal" style="display:none;">
        <div class="tp-modal-content">
            <span class="tp-close-modal tp-close-limit">&times;</span>
            <h3><?php _e( 'Limit Reached', 'tp' ); ?></h3>
            <p><?php printf( __( 'You have reached the maximum limit of %d paths. Please delete an existing path to create a new one.', 'tp' ), $max_paths ); ?></p>
        </div>
    </div>
    <?php

    echo '</div>';
}

/**
 * Filter to add Edit/Delete buttons to the path card in dashboard
 */
function tp_add_dashboard_actions( $html, $path_id ) {
    $edit_url = add_query_arg( array( 'tp_action' => 'edit', 'path_id' => $path_id ) ) . '#tp-path-form-section';
    $delete_url = wp_nonce_url( add_query_arg( array( 'tp_action' => 'delete', 'path_id' => $path_id ) ), 'tp_delete_path_' . $path_id );

    ob_start(); ?>
    <div class="wp-block-button has-custom-width wp-block-button__width-100 is-style-white-button">
        <a class="wp-block-button__link wp-element-button" href="<?php echo esc_url( $edit_url ); ?>">
            <?php _e( 'Edit', 'tp' ); ?>
            <span class="material-symbols-outlined">edit</span>
        </a>
    </div>
    <div class="wp-block-button has-custom-width wp-block-button__width-100 is-style-white-button">
        <a class="wp-block-button__link wp-element-button tp-text-danger" 
           href="<?php echo esc_url( $delete_url ); ?>" 
           onclick="return confirm('<?php _e( 'Are you sure?', 'tp' ); ?>');">
            <?php _e( 'Delete', 'tp' ); ?>
            <span class="material-symbols-outlined">delete</span>
        </a>
    </div>
    <?php
    return $html . ob_get_clean();
}

/**
 * Render Path Form
 */
function tp_render_path_form( $path_id = 0 ) {
    $is_edit = $path_id > 0;
    $title = '';
    $short_desc = '';
    $desc = '';
    $bg_color = '#ffffff';
    $text_color = '#000000';
    $steps = array();

    if ( $is_edit ) {
        // Verify ownership
        $post = get_post( $path_id );
        if ( $post->post_author != get_current_user_id() ) {
            echo '<p>' . __( 'You do not have permission to edit this path.', 'tp' ) . '</p>';
            return;
        }
        $title = $post->post_title;

        if ( function_exists( 'pods' ) ) {
            $pod = pods( 'path', $path_id );
            if ( $pod && $pod->exists() ) {
                $short_desc = $pod->field( 'short_description' );
                $desc = $pod->field( 'description' );
                $bg_color = $pod->field( 'background_color' ) ?: '#ffffff';
                $text_color = $pod->field( 'text_color' ) ?: '#000000';
                $steps_raw = $pod->field( 'steps' );
                
                if ( is_array( $steps_raw ) ) {
                    foreach ( $steps_raw as $s ) {
                        if ( is_array( $s ) && isset( $s['ID'] ) ) {
                            $steps[] = intval( $s['ID'] );
                        } elseif ( is_numeric( $s ) ) {
                            $steps[] = intval( $s );
                        }
                    }
                }
            }
        } else {
            $short_desc = get_post_meta( $path_id, 'short_description', true );
            $desc = get_post_meta( $path_id, 'description', true );
            $bg_color = get_post_meta( $path_id, 'background_color', true ) ?: '#ffffff';
            $text_color = get_post_meta( $path_id, 'text_color', true ) ?: '#000000';
            $steps = get_post_meta( $path_id, 'steps', true );
            if ( ! is_array( $steps ) ) $steps = array();
        }
    }

    ?>
    <div class="tp-path-form-wrapper">
        <h2><?php echo $is_edit ? __( 'Edit Path', 'tp' ) : __( 'Create New Path', 'tp' ); ?></h2>
        
        <form method="post" id="tp-path-form">
            <?php wp_nonce_field( 'tp_save_path', 'tp_path_nonce' ); ?>
            <input type="hidden" name="tp_action" value="save_path">
            <?php if ( $is_edit ) : ?>
                <input type="hidden" name="path_id" value="<?php echo esc_attr( $path_id ); ?>">
            <?php endif; ?>

            <!-- Path Fields -->
            <div class="tp-form-group">
                <label for="path_title"><?php _e( 'Title', 'tp' ); ?></label>
                <input type="text" name="path_title" id="path_title" value="<?php echo esc_attr( $title ); ?>" required>
            </div>

            <div class="tp-form-group">
                <label for="short_description"><?php _e( 'Short Description', 'tp' ); ?></label>
                <input type="text" name="short_description" id="short_description" value="<?php echo esc_attr( $short_desc ); ?>">
            </div>

            <div class="tp-form-group">
                <label for="description"><?php _e( 'Description', 'tp' ); ?></label>
                <textarea name="description" id="description" rows="4"><?php echo esc_textarea( $desc ); ?></textarea>
            </div>

            <div class="tp-form-row">
                <div class="tp-form-group">
                    <label for="background_color"><?php _e( 'Background Color', 'tp' ); ?></label>
                    <input type="color" name="background_color" id="background_color" value="<?php echo esc_attr( $bg_color ); ?>">
                </div>
                <div class="tp-form-group">
                    <label for="text_color"><?php _e( 'Text Color', 'tp' ); ?></label>
                    <input type="color" name="text_color" id="text_color" value="<?php echo esc_attr( $text_color ); ?>">
                </div>
            </div>

            <!-- Steps Area -->
            <div class="tp-steps-area">
                <h3><?php _e( 'Steps', 'tp' ); ?></h3>
                <div id="tp-steps-list">
                    <?php
                    // Render existing steps
                    if ( ! empty( $steps ) ) {
                        foreach ( $steps as $step_id ) {
                            $step = get_post( $step_id );
                            if ( $step ) {
                                echo '<div class="tp-step-item" data-id="' . esc_attr( $step_id ) . '">';
                                echo '<span class="tp-step-title">' . esc_html( $step->post_title ) . '</span>';
                                echo '<input type="hidden" name="steps[]" value="' . esc_attr( $step_id ) . '">';
                                echo '<div class="tp-step-actions">';
                                echo '<button type="button" class="tp-edit-step button-link">' . __( 'Edit', 'tp' ) . '<span class="material-symbols-outlined">edit</span></button>';
                                echo '<button type="button" class="tp-remove-step button-link" style="margin-top:1rem">' . __( 'Delete  ', 'tp' ) . '<span class="material-symbols-outlined">delete</span></button>';
                                echo '</div>';
                                echo '</div>';
                            }
                        }
                    }
                    ?>
                </div>
                <button type="button" id="tp-add-step-btn" style="margin-block:1rem 2rem" class="button"><?php _e( 'Add New Step', 'tp' ); ?><span class="material-symbols-outlined">
add
</span></button>
            </div>

            <div class="tp-form-actions">
                <div class="wp-block-button is-style-black-button">
                <button type="submit" class="button wp-block-button__link" style="width:100%"><?php _e( 'Save Path', 'tp' ); ?><span class="material-symbols-outlined">
save
</span></button>
                </div>
                 <div class="wp-block-button is-style-white-button">
                <a href="<?php echo esc_url( remove_query_arg( array( 'tp_action', 'path_id' ) ) ); ?>" class="button wp-block-button__link "><?php _e( 'Cancel', 'tp' ); ?><span class="material-symbols-outlined">
undo
</span></a>
            </div>
        </form>

        <!-- Step Modal (Hidden) -->
        <?php tp_render_step_modal(); ?>
    </div>
    <?php
}

/**
 * Render Step Modal
 */
function tp_render_step_modal() {
    ?>
    <div id="tp-step-modal" class="tp-modal" style="display:none;">
        <div class="tp-modal-content">
            <span class="tp-close-modal">&times;</span>
            <h3 id="tp-modal-title"><?php _e( 'Add New Step', 'tp' ); ?></h3>
            <form id="tp-step-form">
                <input type="hidden" name="step_id" id="step_id" value="0">
                <div class="tp-form-group">
                    <label for="step_title"><?php _e( 'Step Title', 'tp' ); ?></label>
                    <input type="text" name="step_title" id="step_title" required>
                </div>
                
                <!-- Content 1 & 2 (Simple Textareas for now, WYSIWYG requires wp_editor via AJAX which is complex) -->
                <div class="tp-form-group">
                    <label for="content_1"><?php _e( 'Content 1', 'tp' ); ?></label>
                    <textarea name="content_1" id="content_1" rows="3"></textarea>
                </div>
                <div class="tp-form-group">
                    <label for="content_2"><?php _e( 'Content 2', 'tp' ); ?></label>
                    <textarea name="content_2" id="content_2" rows="3"></textarea>
                </div>

                <!-- Items Selection -->
                <div class="tp-form-group">
                    <label><?php _e( 'Select Items', 'tp' ); ?></label>
                    <div class="tp-items-filter">
                        <label><input type="radio" name="items_filter" value="all" checked> <?php _e( 'All Items', 'tp' ); ?></label>
                        <label><input type="radio" name="items_filter" value="favorites"> <?php _e( 'Favorites', 'tp' ); ?></label>
                    </div>
                    <select name="step_items[]" id="step_items" multiple style="width: 100%;">
                        <!-- Populated via AJAX -->
                    </select>
                </div>

                <div class="tp-form-row">
                    <div class="tp-form-group">
                        <label for="step_bg_color"><?php _e( 'Background Color', 'tp' ); ?></label>
                        <input type="color" name="step_bg_color" id="step_bg_color" value="#ffffff">
                    </div>
                    <div class="tp-form-group">
                        <label for="step_text_color"><?php _e( 'Text Color', 'tp' ); ?></label>
                        <input type="color" name="step_text_color" id="step_text_color" value="#000000">
                    </div>
                </div>

                <div class="tp-form-actions">
                    <button type="submit" id="tp-step-save-btn" class="button tp-btn-primary"><?php _e( 'Create Step', 'tp' ); ?></button>
                </div>
            </form>
        </div>
    </div>
    <?php
}

/**
 * Handle Path Form Submission
 */
function tp_handle_form_submission() {
    if ( isset( $_POST['tp_action'] ) && $_POST['tp_action'] === 'save_path' ) {
        if ( ! isset( $_POST['tp_path_nonce'] ) || ! wp_verify_nonce( $_POST['tp_path_nonce'], 'tp_save_path' ) ) {
            wp_die( 'Security check failed' );
        }

        $path_id = isset( $_POST['path_id'] ) ? intval( $_POST['path_id'] ) : 0;
        $title = sanitize_text_field( $_POST['path_title'] );
        $short_desc = sanitize_text_field( $_POST['short_description'] );
        $desc = sanitize_textarea_field( $_POST['description'] );
        $bg_color = sanitize_hex_color( $_POST['background_color'] );
        $text_color = sanitize_hex_color( $_POST['text_color'] );
        $steps = isset( $_POST['steps'] ) ? array_map( 'intval', $_POST['steps'] ) : array();

        $data = array(
            'post_title'        => $title,
            'post_type'         => 'path',
            'post_status'       => 'publish',
            'post_author'       => get_current_user_id(),
            'short_description' => $short_desc,
            'description'       => $desc,
            'background_color'  => $bg_color,
            'text_color'        => $text_color,
            'steps'             => $steps,
        );

        if ( $path_id ) {
            $data['ID'] = $path_id;
        }

        if ( function_exists( 'pods' ) ) {
            $pod = pods( 'path', $path_id ?: null );
            $path_id = $pod->save( $data );
        } else {
            if ( $path_id ) {
                wp_update_post( array( 'ID' => $path_id, 'post_title' => $title ) );
            } else {
                $path_id = wp_insert_post( array(
                    'post_title'   => $title,
                    'post_type'    => 'path',
                    'post_status'  => 'publish',
                    'post_author'  => get_current_user_id(),
                ) );
            }

            if ( $path_id ) {
                update_post_meta( $path_id, 'short_description', $short_desc );
                update_post_meta( $path_id, 'description', $desc );
                update_post_meta( $path_id, 'background_color', $bg_color );
                update_post_meta( $path_id, 'text_color', $text_color );
                update_post_meta( $path_id, 'steps', $steps );
            }
        }

        // Redirect to dashboard
        wp_redirect( remove_query_arg( array( 'tp_action', 'path_id' ) ) );
        exit;
    }
    
    // Handle Delete
    if ( isset( $_GET['tp_action'] ) && $_GET['tp_action'] === 'delete' && isset( $_GET['path_id'] ) ) {
        $path_id = intval( $_GET['path_id'] );
        check_admin_referer( 'tp_delete_path_' . $path_id );
        
        $post = get_post( $path_id );
        if ( $post && $post->post_author == get_current_user_id() ) {
            wp_delete_post( $path_id, true ); // This triggers before_delete_post hook
        }
        
        wp_redirect( remove_query_arg( array( 'tp_action', 'path_id', '_wpnonce' ) ) );
        exit;
    }
}
add_action( 'init', 'tp_handle_form_submission' );

/**
 * Render Signup Modal in Footer
 */
function tp_render_signup_modal() {
    if ( is_user_logged_in() ) return;
    ?>
    <div id="tp-signup-modal" class="tp-modal">
        <div class="tp-modal-content">
            <span id="tp-signup-close" class="tp-close-modal">&times;</span>
            <h3><?php _e( 'Join Our Community', 'tp' ); ?></h3>
            <p><?php _e( 'Sign up to save items and create your own personalized museum paths.', 'tp' ); ?></p>
            <a href="<?php echo esc_url( wp_registration_url() ); ?>" class="button tp-btn-primary"><?php _e( 'Register Now', 'tp' ); ?></a>
            <p style="margin-top:10px;"><small><?php _e( 'Already have an account?', 'tp' ); ?> <a href="<?php echo esc_url( wp_login_url() ); ?>"><?php _e( 'Log In', 'tp' ); ?></a></small></p>
        </div>
    </div>
    <?php
}
add_action( 'wp_footer', 'tp_render_signup_modal' );
