<?php
/**
 * DIR - WordPress interface functions and definitions
 *
 * @link https://developer.wordpress.org/themes/basics/theme-functions/
 *
 * @package DIR_-_WordPress_interface
 */

if ( ! defined( '_S_VERSION' ) ) {
	// Replace the version number of the theme on each release.
	define( '_S_VERSION', '1.0.0' );
}

/**
 * Sets up theme defaults and registers support for various WordPress features.
 *
 * Note that this function is hooked into the after_setup_theme hook, which
 * runs before the init hook. The init hook is too late for some features, such
 * as indicating support for post thumbnails.
 */
function dir_wordpress_interface_setup() {
	/*
		* Make theme available for translation.
		* Translations can be filed in the /languages/ directory.
		* If you're building a theme based on DIR - WordPress interface, use a find and replace
		* to change 'dir-wordpress-interface' to the name of your theme in all the template files.
		*/
	load_theme_textdomain( 'dir-wordpress-interface', get_template_directory() . '/languages' );

	// Add default posts and comments RSS feed links to head.
	add_theme_support( 'automatic-feed-links' );

	/*
		* Let WordPress manage the document title.
		* By adding theme support, we declare that this theme does not use a
		* hard-coded <title> tag in the document head, and expect WordPress to
		* provide it for us.
		*/
	add_theme_support( 'title-tag' );

	/*
		* Enable support for Post Thumbnails on posts and pages.
		*
		* @link https://developer.wordpress.org/themes/functionality/featured-images-post-thumbnails/
		*/
	add_theme_support( 'post-thumbnails' );

	// This theme uses wp_nav_menu() in one location.
	register_nav_menus(
		array(
			'menu-1' => esc_html__( 'Primary', 'dir-wordpress-interface' ),
		)
	);

	/*
		* Switch default core markup for search form, comment form, and comments
		* to output valid HTML5.
		*/
	add_theme_support(
		'html5',
		array(
			'search-form',
			'comment-form',
			'comment-list',
			'gallery',
			'caption',
			'style',
			'script',
		)
	);

	// Set up the WordPress core custom background feature.
	add_theme_support(
		'custom-background',
		apply_filters(
			'dir_wordpress_interface_custom_background_args',
			array(
				'default-color' => 'ffffff',
				'default-image' => '',
			)
		)
	);

	// Add theme support for selective refresh for widgets.
	add_theme_support( 'customize-selective-refresh-widgets' );

	/**
	 * Add support for core custom logo.
	 *
	 * @link https://codex.wordpress.org/Theme_Logo
	 */
	add_theme_support(
		'custom-logo',
		array(
			'height'      => 250,
			'width'       => 250,
			'flex-width'  => true,
			'flex-height' => true,
		)
	);
}
add_action( 'after_setup_theme', 'dir_wordpress_interface_setup' );

/**
 * Set the content width in pixels, based on the theme's design and stylesheet.
 *
 * Priority 0 to make it available to lower priority callbacks.
 *
 * @global int $content_width
 */
function dir_wordpress_interface_content_width() {
	$GLOBALS['content_width'] = apply_filters( 'dir_wordpress_interface_content_width', 640 );
}
add_action( 'after_setup_theme', 'dir_wordpress_interface_content_width', 0 );



/**
 * Enqueue scripts and styles.
 */
function dir_wordpress_interface_scripts() {
	wp_enqueue_style( 'dir-wordpress-interface-style', get_stylesheet_uri(), array(), _S_VERSION );
	wp_style_add_data( 'dir-wordpress-interface-style', 'rtl', 'replace' );
	wp_enqueue_script( 'dir-wordpress-interface-main', get_template_directory_uri() . '/js/main.js', array(), _S_VERSION, true );
	wp_enqueue_script( 'dir-wordpress-interface-navigation', get_template_directory_uri() . '/js/navigation.js', array(), _S_VERSION, true );
	

	if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
		wp_enqueue_script( 'comment-reply' );
	}
}
add_action( 'wp_enqueue_scripts', 'dir_wordpress_interface_scripts' );

/**
 * Implement the Custom Header feature.
 */
require get_template_directory() . '/inc/custom-header.php';

/**
 * Custom template tags for this theme.
 */
require get_template_directory() . '/inc/template-tags.php';

/**
 * Functions which enhance the theme by hooking into WordPress.
 */
require get_template_directory() . '/inc/template-functions.php';

/**
 * Customizer additions.
 */
require get_template_directory() . '/inc/customizer.php';

/**
 * Sync with DIR
 */
require get_template_directory() . '/inc/sync.php';

/**
 * Load Jetpack compatibility file.
 */
if ( defined( 'JETPACK__VERSION' ) ) {
	require get_template_directory() . '/inc/jetpack.php';
}


function brutart_theme_setup() {

	// 1) Allineamenti wide/full per tutti i blocchi (compreso `core/group`).
	add_theme_support( 'align-wide' );

	// 2) Carica gli stili core dei blocchi (utile anche per il Gruppo).
	add_theme_support( 'wp-block-styles' );

	// 3) Abilita l’uso di uno stylesheet personalizzato nell’editor.
	add_theme_support( 'editor-styles' );
	add_editor_style( 'assets/css/editor-style.css' );

	// 4) Embeds responsivi, facoltativo ma consigliato.
	add_theme_support( 'responsive-embeds' );
}
add_action( 'after_setup_theme', 'brutart_theme_setup' );


/**
 * Google Fonts – front-end + block editor.
 */
function brutart_enqueue_google_fonts() {

	$handle = 'brutart-google-fonts';

	$src    = 'https://fonts.googleapis.com/css2'
		. '?family=Instrument+Serif:ital,wght@0,400;1,400'
		. '&family=Instrument+Sans:ital,wght@0,400..700;1,400..700&display=swap'
		. '&family=IBM+Plex+Mono:wght@400;700'
		. '&family=Material+Symbols+Outlined'
		. '&display=swap';
	/* front-end */
	wp_enqueue_style( $handle, $src, [], null );

	/* editor (iframe) */
	add_editor_style( $src );   // ← lo stesso identico CSS viene linkato nell’editor
}
add_action( 'wp_enqueue_scripts','brutart_enqueue_google_fonts' );
add_action( 'after_setup_theme', 'brutart_enqueue_google_fonts' );


/**
 * Carica shared-style.css in front-end e editor.
 */
function brutart_enqueue_shared_style() {

	$handle   = 'brutart-shared';
	$src      = get_theme_file_uri( 'shared-style.css' );
	$version  = file_exists( get_theme_file_path( 'shared-style.css' ) )
		? filemtime( get_theme_file_path( 'shared-style.css' ) )
		: null;

	/* Front-end */
	wp_enqueue_style( $handle, $src, [], $version );

	/* Editor (iframe) */
	add_editor_style( 'shared-style.css' );
}
add_action( 'wp_enqueue_scripts', 'brutart_enqueue_shared_style' );
add_action( 'after_setup_theme',  'brutart_enqueue_shared_style' );

/**
 * Homepage: sposta l’H1 dal contenuto al logo (SEO-friendly, invisibile).
 */
function brutart_move_home_h1_to_logo() {

	/* Applichiamo solo sul front-page (statico o blog) e lato pubblico */
	if ( ! is_front_page() || is_admin() ) {
		return;
	}

	/* 1) Svuota l’H1 della pagina home -------------------------------- */
	add_filter(
		'the_title',
		function ( $title, $post_id ) {

			/* Solo titolo nel loop principale */
			if ( in_the_loop() && is_main_query() ) {
				return '';
			}
			return $title;
		},
		10,
		2
	);

	/* 2) Aggiunge l’H1 nascosto accanto al logo ----------------------- */
	add_filter(
		'get_custom_logo',
		function ( $html ) {

			$hidden_h1 = sprintf(
				'<h1 class="screen-reader-text">%s</h1>',
				esc_html( get_bloginfo( 'name', 'display' ) )
			);

			/* Logo + H1 nascosto */
			return $html . $hidden_h1;
		}
	);
}
add_action( 'template_redirect', 'brutart_move_home_h1_to_logo' );

function tp_debug($data){
	$breadcrumbs = '';
	if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
		$ip = $_SERVER['HTTP_CLIENT_IP'];
	} elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
		$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
	} else {
		$ip = $_SERVER['REMOTE_ADDR'];
	}
	// if('78.134.87.65' != $ip) return;
	/*$breadcrumbs .= "\tip: ". $ip ."\n";*/
	/*$trackback = debug_backtrace();
	$trackback = json_decode(json_encode($trackback));
	$i = 0;
	foreach($trackback as $item){
		if(false !== strpos($item->file, 'corchia-park')){
			$breadcrumbs .= "\t".$i.': '.str_replace('/web/htdocs/www.corchiapark.it/home/wp-content/plugins', '', $item->file).':'.$item->line;
			if('smz_debug' != $item->function) $breadcrumbs .= ' '.$item->function; //.json_encode($item->args)
			$breadcrumbs .= "\n";
			$i++;
		}
	}*/

	$fp = fopen(get_template_directory().'/log.txt', 'a'); //opens file in append mode
	fwrite($fp, '['. date('d-m-Y H:i:s') .'] '.json_encode($data)."\n".$breadcrumbs."\n\n");
	fclose($fp);
}


/* -------------------------------------------------------------------------
 *  Shortcode: [brutart_register_form]
 * ---------------------------------------------------------------------- */
function brutart_register_form_shortcode() {

	/* Se l’utente è già loggato: nessun form -------------------------- */
	if ( is_user_logged_in() ) {
		return '<p>' . esc_html__( 'You are already registered.', 'dir-wordpress-interface' ) . '</p>';
	}

	/* Output del form -------------------------------------------------- */
	ob_start(); ?>

	<form method="post" class="brutart-register-form">

		<p>
			<label for="brut_username">Username *</label><br>
			<input type="text" name="brut_username" id="brut_username" required>
		</p>

		<p>
			<label for="brut_email">Email *</label><br>
			<input type="email" name="brut_email" id="brut_email" required>
		</p>

		<p>
			<label for="brut_first">First name</label><br>
			<input type="text" name="brut_first" id="brut_first">
		</p>

		<p>
			<label for="brut_last">Last name</label><br>
			<input type="text" name="brut_last" id="brut_last">
		</p>

		<p>
			<label for="brut_pass1">Password *</label><br>
			<input type="password" name="brut_pass1" id="brut_pass1" required>
		</p>

		<p>
			<label for="brut_pass2">Confirm password *</label><br>
			<input type="password" name="brut_pass2" id="brut_pass2" required>
		</p>

		<!-- Honeypot -->
		<p style="display:none;">
			<label>Leave this field empty</label>
			<input type="text" name="brut_hp" tabindex="-1" autocomplete="off">
		</p>

		<?php wp_nonce_field( 'brut_reg_action', 'brut_reg_nonce' ); ?>

		<p><button type="submit" name="brut_submit"><?php esc_html_e( 'Register', 'dir-wordpress-interface' ); ?></button></p>

	</form>

	<?php return ob_get_clean();
}
add_shortcode( 'brutart_register_form', 'brutart_register_form_shortcode' );


/* -------------------------------------------------------------------------
 *  Gestione submit
 * ---------------------------------------------------------------------- */
function brutart_handle_registration() {

	if ( ! isset( $_POST['brut_submit'] ) ) {
		return;
	}

	/* Honeypot */
	if ( ! empty( $_POST['brut_hp'] ) ) {
		return; // bot rilevato
	}

	/* Nonce */
	if ( ! isset( $_POST['brut_reg_nonce'] ) || ! wp_verify_nonce( $_POST['brut_reg_nonce'], 'brut_reg_action' ) ) {
		wp_die( 'Security check failed' );
	}

	$username   = sanitize_user( $_POST['brut_username'] );
	$email      = sanitize_email( $_POST['brut_email'] );
	$pass1      = $_POST['brut_pass1'];
	$pass2      = $_POST['brut_pass2'];
	$first_name = sanitize_text_field( $_POST['brut_first'] );
	$last_name  = sanitize_text_field( $_POST['brut_last'] );

	/* Validazioni di base */
	$errs = new WP_Error;

	if ( username_exists( $username ) ) {
		$errs->add( 'user_exists', 'Username already taken.' );
	}
	if ( ! validate_username( $username ) ) {
		$errs->add( 'user_invalid', 'Invalid username.' );
	}
	if ( email_exists( $email ) ) {
		$errs->add( 'email_exists', 'Email already registered.' );
	}
	if ( ! is_email( $email ) ) {
		$errs->add( 'email_invalid', 'Invalid email.' );
	}
	if ( $pass1 !== $pass2 ) {
		$errs->add( 'pass_mismatch', 'Passwords do not match.' );
	}

	if ( ! empty( $errs->errors ) ) {
		foreach ( $errs->get_error_messages() as $e ) {
			echo '<p class="error">' . esc_html( $e ) . '</p>';
		}
		return;
	}

	/* Crea utente ----------------------------------------------------- */
	$user_id = wp_create_user( $username, $pass1, $email );

	if ( is_wp_error( $user_id ) ) {
		echo '<p class="error">' . esc_html( $user_id->get_error_message() ) . '</p>';
		return;
	}

	/* Attribuisci ruolo & meta --------------------------------------- */
	wp_update_user( [
		'ID'         => $user_id,
		'first_name' => $first_name,
		'last_name'  => $last_name,
		'role'       => 'subscriber',
	] );

	/* Login automatico e redirect ------------------------------------ */
	wp_set_current_user( $user_id );
	wp_set_auth_cookie( $user_id );

	wp_safe_redirect( home_url() );
	exit;
}
add_action( 'init', 'brutart_handle_registration' );

/* -------------------------------------------------------------------------
 *  Shortcode: [show_user_info show="username|first_name|last_name"]
 * ---------------------------------------------------------------------- */
function brutart_show_user_info_shortcode( $atts ) {
	if ( ! is_user_logged_in() ) {
		return '';
	}

	$atts = shortcode_atts( array(
		'show' => 'username',
	), $atts, 'show_user_info' );

	$user = wp_get_current_user();
	$output = '';

	switch ( $atts['show'] ) {
		case 'first_name':
			$output = $user->first_name;
			break;
		case 'last_name':
			$output = $user->last_name;
			break;
		case 'username':
		default:
			$output = $user->user_login;
			break;
	}

	return esc_html( $output );
}
add_shortcode( 'show_user_info', 'brutart_show_user_info_shortcode' );

/* -------------------------------------------------------------------------
 *  Shortcode: [edit_user_details]
 * ---------------------------------------------------------------------- */
function brutart_edit_user_details_shortcode() {
	if ( ! is_user_logged_in() ) {
		return '<p>' . esc_html__( 'You must be logged in to edit your details.', 'dir-wordpress-interface' ) . '</p>';
	}

	$user = wp_get_current_user();
	ob_start();

	// Show success/error messages
	if ( isset( $_GET['updated'] ) && 'profile' === $_GET['updated'] ) {
		echo '<p class="success">' . esc_html__( 'Profile updated successfully.', 'dir-wordpress-interface' ) . '</p>';
	}
	if ( isset( $_GET['error'] ) ) {
		echo '<p class="error">' . esc_html( urldecode( $_GET['error'] ) ) . '</p>';
	}
	?>

	<div class="brutart-edit-profile">
		<form method="post" class="brutart-form">
			<p>
				<label for="brut_edit_email"><?php esc_html_e( 'Email', 'dir-wordpress-interface' ); ?></label><br>
				<input type="email" name="brut_edit_email" id="brut_edit_email" value="<?php echo esc_attr( $user->user_email ); ?>" required>
			</p>
			<p>
				<label for="brut_edit_first"><?php esc_html_e( 'First Name', 'dir-wordpress-interface' ); ?></label><br>
				<input type="text" name="brut_edit_first" id="brut_edit_first" value="<?php echo esc_attr( $user->first_name ); ?>">
			</p>
			<p>
				<label for="brut_edit_last"><?php esc_html_e( 'Last Name', 'dir-wordpress-interface' ); ?></label><br>
				<input type="text" name="brut_edit_last" id="brut_edit_last" value="<?php echo esc_attr( $user->last_name ); ?>">
			</p>
			<?php wp_nonce_field( 'brut_edit_profile_action', 'brut_edit_profile_nonce' ); ?>
			<p><button type="submit" name="brut_edit_profile_submit"><?php esc_html_e( 'Update Profile', 'dir-wordpress-interface' ); ?></button></p>
		</form>
	</div>

	<?php
	return ob_get_clean();
}
add_shortcode( 'edit_user_details', 'brutart_edit_user_details_shortcode' );

/* -------------------------------------------------------------------------
 *  Shortcode: [edit_user_password]
 * ---------------------------------------------------------------------- */
function brutart_edit_user_password_shortcode() {
	if ( ! is_user_logged_in() ) {
		return '<p>' . esc_html__( 'You must be logged in to change your password.', 'dir-wordpress-interface' ) . '</p>';
	}

	ob_start();

	// Show success/error messages
	if ( isset( $_GET['updated'] ) && 'password' === $_GET['updated'] ) {
		echo '<p class="success">' . esc_html__( 'Password updated successfully.', 'dir-wordpress-interface' ) . '</p>';
	}
	if ( isset( $_GET['error'] ) ) {
		echo '<p class="error">' . esc_html( urldecode( $_GET['error'] ) ) . '</p>';
	}
	?>

	<div class="brutart-edit-password">
		<form method="post" class="brutart-form">
			<p>
				<label for="brut_new_pass"><?php esc_html_e( 'New Password', 'dir-wordpress-interface' ); ?></label><br>
				<input type="password" name="brut_new_pass" id="brut_new_pass" required>
			</p>
			<p>
				<label for="brut_confirm_pass"><?php esc_html_e( 'Confirm Password', 'dir-wordpress-interface' ); ?></label><br>
				<input type="password" name="brut_confirm_pass" id="brut_confirm_pass" required>
			</p>
			<?php wp_nonce_field( 'brut_change_pass_action', 'brut_change_pass_nonce' ); ?>
			<p><button type="submit" name="brut_change_pass_submit"><?php esc_html_e( 'Update Password', 'dir-wordpress-interface' ); ?></button></p>
		</form>
	</div>

	<?php
	return ob_get_clean();
}
add_shortcode( 'edit_user_password', 'brutart_edit_user_password_shortcode' );

/* -------------------------------------------------------------------------
 *  Handle User Updates (Profile & Password)
 * ---------------------------------------------------------------------- */
function brutart_handle_user_updates() {
	if ( ! is_user_logged_in() ) {
		return;
	}

	$user_id = get_current_user_id();

	// 1. Handle Profile Update
	if ( isset( $_POST['brut_edit_profile_submit'] ) ) {
		if ( ! isset( $_POST['brut_edit_profile_nonce'] ) || ! wp_verify_nonce( $_POST['brut_edit_profile_nonce'], 'brut_edit_profile_action' ) ) {
			wp_die( 'Security check failed' );
		}

		$email = sanitize_email( $_POST['brut_edit_email'] );
		$first = sanitize_text_field( $_POST['brut_edit_first'] );
		$last  = sanitize_text_field( $_POST['brut_edit_last'] );

		if ( ! is_email( $email ) ) {
			wp_safe_redirect( add_query_arg( 'error', urlencode( __( 'Invalid email address.', 'dir-wordpress-interface' ) ) ) );
			exit;
		}

		if ( email_exists( $email ) && email_exists( $email ) !== $user_id ) {
			wp_safe_redirect( add_query_arg( 'error', urlencode( __( 'Email already in use.', 'dir-wordpress-interface' ) ) ) );
			exit;
		}

		$updated = wp_update_user( array(
			'ID'         => $user_id,
			'user_email' => $email,
			'first_name' => $first,
			'last_name'  => $last,
		) );

		if ( is_wp_error( $updated ) ) {
			wp_safe_redirect( add_query_arg( 'error', urlencode( $updated->get_error_message() ) ) );
			exit;
		}

		wp_safe_redirect( add_query_arg( 'updated', 'profile' ) );
		exit;
	}

	// 2. Handle Password Update
	if ( isset( $_POST['brut_change_pass_submit'] ) ) {
		if ( ! isset( $_POST['brut_change_pass_nonce'] ) || ! wp_verify_nonce( $_POST['brut_change_pass_nonce'], 'brut_change_pass_action' ) ) {
			wp_die( 'Security check failed' );
		}

		$pass1 = $_POST['brut_new_pass'];
		$pass2 = $_POST['brut_confirm_pass'];

		if ( $pass1 !== $pass2 ) {
			wp_safe_redirect( add_query_arg( 'error', urlencode( __( 'Passwords do not match.', 'dir-wordpress-interface' ) ) ) );
			exit;
		}

		if ( strlen( $pass1 ) < 8 ) {
			wp_safe_redirect( add_query_arg( 'error', urlencode( __( 'Password must be at least 8 characters long.', 'dir-wordpress-interface' ) ) ) );
			exit;
		}

		wp_set_password( $pass1, $user_id );
		
		// wp_set_password logs the user out, so we need to re-login
		$user = get_user_by( 'id', $user_id );
		wp_set_current_user( $user_id, $user->user_login );
		wp_set_auth_cookie( $user_id );

		wp_safe_redirect( add_query_arg( 'updated', 'password' ) );
		exit;
	}
}
add_action( 'init', 'brutart_handle_user_updates' );

/* -------------------------------------------------------------------------
 *  Shortcode: [delete_user_account]
 * ---------------------------------------------------------------------- */
function brutart_delete_account_shortcode() {
	if ( ! is_user_logged_in() ) {
		return '';
	}

	ob_start();
	$modal_id = 'delete-account-modal-' . mt_rand();
	?>
	<div class="brutart-delete-account-wrapper">
		<button type="button" class="button delete-account-btn" onclick="document.getElementById('<?php echo esc_js( $modal_id ); ?>').style.display='flex'">
			<?php esc_html_e( 'Delete Account', 'dir-wordpress-interface' ); ?>
		</button>

		<div id="<?php echo esc_attr( $modal_id ); ?>" class="brutart-modal-overlay" style="display:none;">
			<div class="brutart-modal-content">
				<h3><?php esc_html_e( 'Confirm Account Deletion', 'dir-wordpress-interface' ); ?></h3>
				<p><?php esc_html_e( 'Are you sure you want to delete your account? This action cannot be undone.', 'dir-wordpress-interface' ); ?></p>
				
				<form method="post">
					<?php wp_nonce_field( 'brut_delete_account_action', 'brut_delete_account_nonce' ); ?>
					<div class="brutart-modal-actions">
						<button type="button" class="button cancel-btn" onclick="document.getElementById('<?php echo esc_js( $modal_id ); ?>').style.display='none'">
							<?php esc_html_e( 'Cancel', 'dir-wordpress-interface' ); ?>
						</button>
						<button type="submit" name="brut_delete_account_submit" class="button confirm-delete-btn">
							<?php esc_html_e( 'Confirm Delete', 'dir-wordpress-interface' ); ?>
						</button>
					</div>
				</form>
			</div>
		</div>
		
		<style>
			.brutart-modal-overlay {
				position: fixed; top: 0; left: 0; width: 100%; height: 100%;
				background: rgba(0,0,0,0.5);
				display: flex; align-items: center; justify-content: center;
				z-index: 9999;
			}
			.brutart-modal-content {
				background: #fff; padding: 2rem; border-radius: 4px;
				max-width: 500px; width: 90%;
				text-align: center;
				box-shadow: 0 4px 6px rgba(0,0,0,0.1);
			}
			.brutart-modal-actions {
				margin-top: 1.5rem;
				display: flex; gap: 1rem; justify-content: center;
			}
			.confirm-delete-btn {
				background-color: #d63638 !important; color: #fff !important; border-color: #d63638 !important;
			}
		</style>
	</div>
	<?php
	return ob_get_clean();
}
add_shortcode( 'delete_user_account', 'brutart_delete_account_shortcode' );

/* -------------------------------------------------------------------------
 *  Handle Account Deletion
 * ---------------------------------------------------------------------- */
function brutart_handle_account_deletion() {
	if ( ! is_user_logged_in() ) {
		return;
	}

	if ( isset( $_POST['brut_delete_account_submit'] ) ) {
		if ( ! isset( $_POST['brut_delete_account_nonce'] ) || ! wp_verify_nonce( $_POST['brut_delete_account_nonce'], 'brut_delete_account_action' ) ) {
			wp_die( 'Security check failed' );
		}

		require_once( ABSPATH . 'wp-admin/includes/user.php' );
		
		$user_id = get_current_user_id();
		
		// Delete user and reassign posts to nothing (delete content) or admin? 
		// Standard behavior for "delete my account" is usually full deletion.
		// We pass null to delete content.
		if ( wp_delete_user( $user_id ) ) {
			wp_safe_redirect( home_url() );
			exit;
		} else {
			wp_die( __( 'Could not delete account.', 'dir-wordpress-interface' ) );
		}
	}
}
add_action( 'init', 'brutart_handle_account_deletion' );

/* -------------------------------------------------------------------------
 *  Nega accesso a /wp-admin/ ai subscriber
 * ---------------------------------------------------------------------- */
function brutart_block_admin_for_subscribers() {

	if ( is_admin() && ! wp_doing_ajax() ) {
		$user = wp_get_current_user();

		if ( in_array( 'subscriber', (array) $user->roles, true ) ) {
			wp_safe_redirect( home_url() );
			exit;
		}
	}
}
add_action( 'init', 'brutart_block_admin_for_subscribers' );

/* -------------------------------------------------------------------------
 *  Nasconde admin-bar ai subscriber
 * ---------------------------------------------------------------------- */
function brutart_hide_admin_bar() {
	$user = wp_get_current_user();

	if ( in_array( 'subscriber', (array) $user->roles, true ) ) {
		show_admin_bar( false );
	}
}
add_action( 'after_setup_theme', 'brutart_hide_admin_bar' );


/* -----------------------------------------------------------
 *  1. SHORTCODE  –  [item_login_button]
 * --------------------------------------------------------- */
function brutart_login_button_shortcode( $atts, $content = null ) {
	$label = $content ? $content : __( 'Login', 'dir-wordpress-interface' );
	return '<button class="item-login">' . esc_html( $label ) . '</button>';
}
add_shortcode( 'item_login_button', 'brutart_login_button_shortcode' );

/* -----------------------------------------------------------
 *  2. CARICA CSS & JS  ( + nonce per AJAX )
 * --------------------------------------------------------- */
function brutart_enqueue_modal_assets() {

	
	/* JS */
	wp_enqueue_script(
		'brutart-modal',
		get_theme_file_uri( 'js/modal.js' ),
		[ 'jquery' ],
		'1.0',
		true
	);

	wp_localize_script(
		'brutart-modal',
		'brutLogin',
		[
			'ajaxurl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'brut_login_action' ),
			'redirect'=> home_url()      // cambia se vuoi un’altra destinazione
		]
	);
}
add_action( 'wp_enqueue_scripts', 'brutart_enqueue_modal_assets' );

/* ----------------------------------------------------------------------
 *  AJAX login  (guest + logged)     action = brut_login
 * ------------------------------------------------------------------- */
function brutart_ajax_login() {

	/* 1. verifica nonce ------------------------------------------------ */
	if ( empty( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'brut_login_action' ) ) {
		wp_send_json_error( 'Invalid security token', 403 );
	}

	/* 2. credenziali --------------------------------------------------- */
	$creds = [
		'user_login'    => sanitize_user( $_POST['username'] ?? '' ),
		'user_password' => $_POST['password']     ?? '',
		'remember'      => true,
	];

	/* 3. login --------------------------------------------------------- */
	$user = wp_signon( $creds, is_ssl() );

	if ( is_wp_error( $user ) ) {
		wp_send_json_error( $user->get_error_message(), 401 );
	}

	/* 4. success ------------------------------------------------------- */
	wp_send_json_success( [
		'redirect' => home_url()        // cambialo se vuoi
	] );
}
add_action( 'wp_ajax_nopriv_brut_login', 'brutart_ajax_login' );
add_action( 'wp_ajax_brut_login',        'brutart_ajax_login' );

/**
 * Pulsante: conserva solo Accent, Accent Full, Black, Black Full, White, White Full
 * e imposta border-radius:0 con inline-style (coerente al theme.json).
 */
function brutart_replace_button_styles() {

	/* -----------------------------------------------------------------
	 * 1. rimuovi QUALSIASI stile già registrato per core/button
	 * ---------------------------------------------------------------- */
	if ( class_exists( 'WP_Block_Styles_Registry' ) ) {
		$registry = WP_Block_Styles_Registry::get_instance();
		$styles   = $registry->get_registered_styles_for_block( 'core/button' );

		foreach ( $styles as $style ) {
			unregister_block_style( 'core/button', $style->name );
		}
	}

	/* helper comuni -------------------------------------------------- */
	$box      = 'border:1px solid currentColor;padding:1.44rem 1em;font-size:1.6rem;line-height:1.2;border-radius:0;';
	$fullwrap = 'flex-basis:100%;';
	$accentBG = 'background:var(--accent);color:var(--dark-grey);';
	$blackBG  = 'background:var(--dark-grey);color:var(--white);';
	$whiteBG  = 'background:var(--white);color:var(--dark-grey);';

	/* -----------------------------------------------------------------
	 * 2. Accent (default) – replica lo stile di tema per mostrarlo
	 * ---------------------------------------------------------------- */
	register_block_style( 'core/button', [
		'name'  => 'accent-button',
		'label' => __( 'Accent', 'dir-wordpress-interface' ),
		'inline_style' => ".is-style-accent-button .wp-block-button__link{ $accentBG $box }"
	] );

	register_block_style( 'core/button', [
		'name'  => 'accent-button-full',
		'label' => __( 'Accent Full', 'dir-wordpress-interface' ),
		'inline_style' => ".is-style-accent-button-full.wp-block-button{ $fullwrap } .is-style-accent-button-full .wp-block-button__link{ $accentBG $box }"
	] );

	/* -----------------------------------------------------------------
	 * 3. Black
	 * ---------------------------------------------------------------- */
	register_block_style( 'core/button', [
		'name'  => 'black-button',
		'label' => __( 'Black', 'dir-wordpress-interface' ),
		'inline_style' => ".is-style-black-button .wp-block-button__link{ $blackBG $box }"
	] );

	register_block_style( 'core/button', [
		'name'  => 'black-button-full',
		'label' => __( 'Black Full', 'dir-wordpress-interface' ),
		'inline_style' => ".is-style-black-button-full.wp-block-button{ $fullwrap } .is-style-black-button-full .wp-block-button__link{ $blackBG $box }"
	] );

	/* -----------------------------------------------------------------
	 * 4. White
	 * ---------------------------------------------------------------- */
	register_block_style( 'core/button', [
		'name'  => 'white-button',
		'label' => __( 'White', 'dir-wordpress-interface' ),
		'inline_style' => ".is-style-white-button .wp-block-button__link{ $whiteBG $box }"
	] );

	register_block_style( 'core/button', [
		'name'  => 'white-button-full',
		'label' => __( 'White Full', 'dir-wordpress-interface' ),
		'inline_style' => ".is-style-white-button-full.wp-block-button{ $fullwrap } .is-style-white-button-full .wp-block-button__link{ $whiteBG $box }"
	] );
}
add_action( 'init', 'brutart_replace_button_styles', 99 );   // 99 = dopo tutti



/**
 * Restituisce la card “opera d’arte” con micro-data VisualArtwork.
 *
 * @param int   $item_id   ID del post / pod.
 * @param array $overrides Opzionale. Override rapidi:
 *                         - 'image' (URL assoluto)
 *                         - 'description'
 *
 * @return string Markup HTML già sanitizzato.
 */
if ( ! function_exists( 'get_item_card' ) ) {
	function get_item_card( $item_id, $overrides = array() ) {

		global $favorite_items;
		
		$item_id = absint( $item_id );
		if ( ! $item_id ) {
			return '';
		}

		/* ------------------------------------------------------------------
		 * Dati base WordPress
		 * ---------------------------------------------------------------- */
		$title     = get_the_title( $item_id );
		$permalink = get_permalink( $item_id );

		/* ------------------------------------------------------------------
		 * Recupero Pods: main_image (File) e description (Text)
		 * ---------------------------------------------------------------- */
		$image_id     = 0;
		$image_html   = '';
		$description  = '';
		$author_list = get_the_term_list($item_id,'item_author');
		$authors ='';
		if($author_list){
			$authors = __( 'AUTORE: ', 'dir-wordpress-interface' ).strip_tags($author_list);
		}
		
		/* ------------------------------------------------------------------
		 * Campi/Tax → stringhe META per la card
		 * ---------------------------------------------------------------- */

		/* 1. museo (relation, singolo post-type “museum”) */
		$museum_name = '';
		$museum_raw  = function_exists( 'pods_field' )
					  ? pods_field( get_post_type( $item_id ), $item_id, 'museum' )
					  : '';
		if ( is_array( $museum_raw ) && isset( $museum_raw['ID'] ) ) {
			$museum_name = get_the_title( $museum_raw['ID'] );
		} elseif ( is_numeric( $museum_raw ) ) {
			$museum_name = get_the_title( $museum_raw );
		}
		$museum = __( 'MUSEO: ', 'dir-wordpress-interface' ) . esc_html( $museum_name );

		/* 2. iccd_code (campo testo) */
		$iccd_code_val  = function_exists( 'pods_field' )
						? pods_field( get_post_type( $item_id ), $item_id, 'iccd_code' )
						: '';
		$iccd_code      = __( 'CODICE ICCD: ', 'dir-wordpress-interface' ) .
						  esc_html( $iccd_code_val );

		/* 3. inventory_code (campo testo) */
		$inventory_code_val = function_exists( 'pods_field' )
							? pods_field( get_post_type( $item_id ), $item_id, 'inventory_code' )
							: '';
		$inventory_code     = __( 'CODICE MUSEO: ', 'dir-wordpress-interface' ) .
							  esc_html( $inventory_code_val );

		/* 4. historical_time_period (tassonomia) */
		$htp_terms = get_the_terms( $item_id, 'historical_time_period' );
		$htp_list  = ( $htp_terms && ! is_wp_error( $htp_terms ) )
				   ? wp_list_pluck( $htp_terms, 'name' )
				   : [];
		$historical_time_period = __( 'PERIODO: ', 'dir-wordpress-interface' ) .
								   esc_html( implode( ', ', $htp_list ) );

		/* 5. material (tassonomia) */
		$mat_terms = get_the_terms( $item_id, 'material' );
		$mat_list  = ( $mat_terms && ! is_wp_error( $mat_terms ) )
				   ? wp_list_pluck( $mat_terms, 'name' )
				   : [];
		$material = __( 'MATERIALI: ', 'dir-wordpress-interface' ) .
					esc_html( implode( ', ', $mat_list ) );

		/* 6. dimensions (campo testo semplice) */
		$dimension_val = function_exists( 'pods_field' )
					   ? pods_field( get_post_type( $item_id ), $item_id, 'dimensions' )
					   : '';
		$dimension = __( 'DIMENSIONI: ', 'dir-wordpress-interface' ) .
					 esc_html( $dimension_val );

		/* 7. cultural_context (tassonomia) */
		$cc_terms = get_the_terms( $item_id, 'cultural_context' );
		$cc_list  = ( $cc_terms && ! is_wp_error( $cc_terms ) )
				  ? wp_list_pluck( $cc_terms, 'name' )
				  : [];
		$cultural_context = __( 'CONTESTO CULTURALE: ', 'dir-wordpress-interface' ) .
                    esc_html( implode( ', ', $cc_list ) );

		

		if ( function_exists( 'pods_field' ) ) {

			// main_image può essere:
			// - array con chiave 'ID'
			// - array di array [0]['ID']
			// - intero (ID allegato)
			$main_image = pods_field( get_post_type( $item_id ), $item_id, 'main_image' );

			if ( is_array( $main_image ) ) {
				if ( isset( $main_image['ID'] ) ) {
					$image_id = absint( $main_image['ID'] );
				} elseif ( isset( $main_image[0]['ID'] ) ) {
					$image_id = absint( $main_image[0]['ID'] );
				}
			} elseif ( is_numeric( $main_image ) ) {
				$image_id = absint( $main_image );
			}

			// description: semplice testo
			$description = pods_field( get_post_type( $item_id ), $item_id, 'description' );
		}

		/* ------------------------------------------------------------------
		 * Override opzionali
		 * ---------------------------------------------------------------- */
		if ( ! empty( $overrides['description'] ) ) {
			$description = wp_kses_post( $overrides['description'] );
		}

		if ( ! empty( $overrides['image'] ) ) {
			// Se viene passato un URL, lo usiamo subito (bypassando l'ID)
			$image_html = sprintf(
				'<img src="%s" alt="%s" itemprop="image">',
				esc_url( $overrides['image'] ),
				esc_attr( $title )
			);
		}

		/* ------------------------------------------------------------------
		 * Generazione dell’immagine via wp_get_attachment_image()
		 * ---------------------------------------------------------------- */
		if ( ! $image_html && $image_id ) {
			$image_html = wp_get_attachment_image(
				$image_id,
				'large',
				false,
				array(
					'alt'      => $title,
					'itemprop' => 'image',
				)
			);
		}

		/* ------------------------------------------------------------------
		 * Fallback placeholder
		 * ---------------------------------------------------------------- */
		if ( ! $image_html ) {
			$placeholder = get_template_directory_uri() . '/img/placeholder.png';
			$image_html  = sprintf(
				'<img src="%s" alt="%s" itemprop="image">',
				esc_url( $placeholder ),
				esc_attr( $title )
			);
		}

		if ( ! $description ) {
			$description = __( 'Descrizione non disponibile.', 'dir-wordpress-interface' );
		}

		$save_btn_class = 'save-item';
		$user_id = get_current_user_id();
		if(0 != $user_id) {
			if ( ! isset( $favorite_items ) ) {
				$favorite_items = get_user_meta( $user_id, 'favorite', true );
				if ( ! is_array( $favorite_items ) ) {
					// Fallback: check if stored as multiple entries
					$all_favs = get_user_meta( $user_id, 'favorite', false );
					if ( ! empty( $all_favs ) ) {
						$favorite_items = $all_favs;
					} else {
						$favorite_items = array();
					}
				}
			}
			if ( is_array( $favorite_items ) && in_array( $item_id, $favorite_items ) ) {
				$save_btn_class = 'unsave-item';
			}
		}

		/* ------------------------------------------------------------------
		 * Markup finale
		 * ---------------------------------------------------------------- */
		ob_start(); ?>
<article class="brut-item-card item-<?php echo esc_attr( $item_id ); ?>"
         itemscope
         itemtype="https://schema.org/VisualArtwork">

	<figure>
		<a href="<?php echo esc_url( $permalink ); ?>" class="brut-img-link" itemprop="url">
			<?php echo $image_html; /* Immagine con srcset / placeholder */ ?>
		</a>
	</figure>

	<div class="brut-item-card-content">
		<div class="brut-item-card-content-description-section">
			<h3 class="brut-item-card-title" itemprop="name">
				<a href="<?php echo esc_url( $permalink ); ?>" itemprop="url">
					<?php echo esc_html( $title ); ?>
				</a>
			</h3>
			<p class="brut-item-card-meta show-in-row-view" itemprop="description"><?= $iccd_code; ?></p>
			<p class="brut-item-card-meta show-in-row-view" itemprop="description"><?= $inventory_code; ?></p>

			<p class="brut-item-card-description" itemprop="description"><?php echo nl2br( esc_html( $description ) ); ?></p>
		</div>
		<div class="brut-item-card-content-meta-section">
			<p class="brut-item-card-meta" itemprop="description"><?= $authors; ?></p>
			<p class="brut-item-card-meta show-in-row-view" itemprop="description"><?= $historical_time_period; ?></p>
			<p class="brut-item-card-meta show-in-row-view" itemprop="description"><?= $material; ?></p>
			<p class="brut-item-card-meta show-in-row-view" itemprop="description"><?= $dimension; ?></p>
			<p class="brut-item-card-meta show-in-row-view" itemprop="description"><?= $cultural_context; ?></p>
			<p class="brut-item-card-meta" itemprop="description"><?= $museum; ?></p>


		</div>
		<div class="brut-item-card-content-action-section">
			<div class="wp-block-button has-custom-width wp-block-button__width-100">
				<a class="wp-block-button__link wp-element-button"
				   href="<?php echo esc_url( $permalink ); ?>"
				   itemprop="url">
					<?php _e( "Scopri l'opera", 'dir-wordpress-interface' ); ?>
					<span class="material-symbols-outlined">arrow_forward</span>
				</a>
			</div>

			<div class="wp-block-button has-custom-width wp-block-button__width-100 is-style-white-button">
				<div class="wp-block-button__link wp-element-button save_btn <?= $save_btn_class ?>" data-item-id="<?= esc_attr($item_id) ?>">
					<span class="to_save">
						<?php _e( 'Salva nella collezione', 'dir-wordpress-interface' ); ?>
						<span class="material-symbols-outlined">bookmark</span>
					</span>
					<span class="saved">
						<?php _e( 'Rimuovi dalla mia collezione', 'dir-wordpress-interface' ); ?>
						<span class="material-symbols-outlined">bookmark_added</span>
					</span>
				</div>
			</div>
		</div>
				
	</div>
</article>
		<?php
		$html = ob_get_clean();

		/**
		 * Filtro per personalizzare la card dall’esterno.
		 *
		 * @param string $html      Markup finale.
		 * @param int    $item_id   ID dell’item.
		 * @param array  $overrides Override passate alla funzione.
		 */
		return apply_filters( 'item/card/html', $html, $item_id, $overrides );
	}
}


/**
 * Callback dello shortcode.
 *
 * @param array $atts Attributi passati nello shortcode.
 *                    - id (int)  → obbligatorio. ID dell’item / pod.
 *                    - image, description, … → stessi override di get_item_card().
 *
 * @return string Markup HTML della card o messaggio di errore.
 */
function item_card_shortcode_cb( $atts ) {

	$atts = shortcode_atts(
		array(
			'id'          => 0,
			'image'       => '',
			'description' => '',
		),
		$atts,
		'item_card'
	);

	$item_id = absint( $atts['id'] );
	if ( ! $item_id ) {
		return '<p style="color:red;">Shortcode <strong>[item_card]</strong>: attributo <em>id</em> mancante o non valido.</p>';
	}

	// Passiamo eventuali override alla funzione core.
	$overrides = array_filter(
		array(
			'image'       => $atts['image'],
			'description' => $atts['description'],
		)
	);

	return get_item_card( $item_id, $overrides );
}
add_shortcode( 'item_card', 'item_card_shortcode_cb' );

add_filter( 'edit_post_link', '__return_empty_string' );
/**
 * Assets per lʼarchivio “item”
 * – Splide (css + js)
 * – item-archive.js  (dipende da jQuery **e** Splide)
 */
add_action( 'wp_enqueue_scripts', function () {

	/* ------------------------------------------------------------------
	   1) Splide
	------------------------------------------------------------------ */
	wp_register_style(
		'splide-core',
		'https://cdn.jsdelivr.net/npm/@splidejs/splide@4.1.4/dist/css/splide.min.css',
		[],
		'4.1.4'
	);

	wp_register_script(
		'splide',
		'https://cdn.jsdelivr.net/npm/@splidejs/splide@4.1.4/dist/js/splide.min.js',
		[],
		'4.1.4',
		true     // in footer
	);

	/* ------------------------------------------------------------------
	   2) Script principale dellʼarchivio
	------------------------------------------------------------------ */
	wp_register_script(
		'item-archive',
		get_stylesheet_directory_uri() . '/js/item-archive.js',
		[ 'jquery', 'splide' ],   // << dipende da Splide!
		'2.0.7',
		true
	);

	/* enqueue effettivo (CSS + JS) */
	wp_enqueue_style ( 'splide-core' );
	wp_enqueue_script( 'item-archive' );

	/* ------------------------------------------------------------------
	   3) Dati PHP → JS
	------------------------------------------------------------------ */
	wp_localize_script( 'item-archive', 'ItemArchive', [
		'ajax_url' => admin_url( 'admin-ajax.php' ),
		'nonce'    => wp_create_nonce( 'item_archive_nonce' ),
		'texts'    => [
			'loading' => __( 'Caricamento…', 'dir-wordpress-interface' ),
			'empty_collection' => __( 'If you want to see items in this section, you must save them and add them to your collection while browsing the site.', 'dir-wordpress-interface' ),
		],
	] );
} );
add_shortcode( 'item_archive', function ( $atts ) {

	$atts = shortcode_atts( array(
		'my_collection' => false,
		'per_page'      => 24,
		'filter'        => 'auto', // 'auto', 'true', 'false'
	), $atts, 'item_archive' );

	$my_collection = $atts['my_collection'];
	$per_page      = intval( $atts['per_page'] );
	
	// Logic for show_filters
	$show_filters = $atts['filter'];
	if ( $show_filters === 'auto' ) {
		$show_filters = ! $my_collection;
	} else {
		$show_filters = filter_var( $show_filters, FILTER_VALIDATE_BOOLEAN );
	}

	/* opzioni filtri */
	$periods = get_terms( array( 
		'taxonomy'=>'historical_time_period',
		'hide_empty'=>false 
	) );
	
	$places = get_terms( array(
		'taxonomy'=>'place_of_production',
		'hide_empty'=>false 
	) );
	
	$materials = get_terms( array( 
		'taxonomy'=>'material',
		'hide_empty'=>false 
	) );
	
	$techs = get_terms( array(
		'taxonomy'=>'technique',
		'hide_empty'=>false 
	) );

	$museums = get_posts( array(
		'post_type'      => 'museum',
		'posts_per_page' => -1,
		'orderby'        => 'title',
		'order'          => 'ASC',
	) );
	$authors = get_terms( array(
		'taxonomy'   => 'item_author',
		'hide_empty' => false,
	) );
	$tags = get_terms( array(
		'taxonomy'   => 'public_tag',
		'hide_empty' => false,
		'lang'       => pll_current_language(),
	) );

	ob_start(); ?>
<div class="brut-item-archive-wrapper">
	<div class="filter-view-container">
		<div class="view-wrapper">
			<button id="grid-view"   class="is-active" type="button"><?php _e( 'Griglia', 'dir-wordpress-interface' ); ?><span class="material-symbols-outlined">grid_on</span></button>
			<button id="slider-view" type="button"><?php _e( 'Slider',  'dir-wordpress-interface' ); ?><span class="material-symbols-outlined">tile_large</span></button>
			<button id="row-view"    type="button"><?php _e( 'Lista',   'dir-wordpress-interface' ); ?><span class="material-symbols-outlined">list_alt</span></button>
		</div>

		<?php if ( $show_filters ) : ?>
		<div class="filter-wrapper">
			<select id="filter-hp">
			  <option value=""><?php _e( 'Tutti i periodi', 'dir-wordpress-interface' ); ?></option>
			  <?php foreach ( $periods as $t ) : ?>
				<option value="<?= esc_attr( $t->term_id ); ?>"><?= esc_html( $t->name ); ?></option>
			  <?php endforeach; ?>
			</select>

			<select id="filter-pp">
			  <option value=""><?php _e( 'Tutti i luoghi di produzione', 'dir-wordpress-interface' ); ?></option>
			  <?php foreach ( $places as $t ) : ?>
				<option value="<?= esc_attr( $t->term_id ); ?>"><?= esc_html( $t->name ); ?></option>
			  <?php endforeach; ?>
			</select>

			<select id="filter-ma">
			  <option value=""><?php _e( 'Tutti i materiali', 'dir-wordpress-interface' ); ?></option>
			  <?php foreach ( $materials as $t ) : ?>
				<option value="<?= esc_attr( $t->term_id ); ?>"><?= esc_html( $t->name ); ?></option>
			  <?php endforeach; ?>
			</select>

			<select id="filter-tq">
			  <option value=""><?php _e( 'Tutte le tecniche', 'dir-wordpress-interface' ); ?></option>
			  <?php foreach ( $techs as $t ) : ?>
				<option value="<?= esc_attr( $t->term_id ); ?>"><?= esc_html( $t->name ); ?></option>
			  <?php endforeach; ?>
			</select>
			<select id="filter-museum">
				<option value=""><?php _e( 'Tutti i Musei', 'dir-wordpress-interface' ); ?></option>
				<?php foreach ( $museums as $m ) : ?>
					<option value="<?= esc_attr( $m->ID ); ?>"><?= esc_html( $m->post_title ); ?></option>
				<?php endforeach; ?>
			</select>

			<select id="filter-author">
				<option value=""><?php _e( 'Tutti gli Autori', 'dir-wordpress-interface' ); ?></option>
				<?php foreach ( $authors as $t ) : ?>
					<option value="<?= esc_attr( $t->term_id ); ?>"><?= esc_html( $t->name ); ?></option>
				<?php endforeach; ?>
			</select>

			<select id="filter-tag">
				<option value=""><?php _e( 'Tutti i Tag', 'dir-wordpress-interface' ); ?></option>
				<?php foreach ( $tags as $t ) : ?>
					<option value="<?= esc_attr( $t->term_id ); ?>"><?= esc_html( $t->name ); ?></option>
				<?php endforeach; ?>
			</select>

			<input type="search" id="filter-search" placeholder="<?php esc_attr_e( 'Cerca opera…', 'dir-wordpress-interface' ); ?>">
			<input type="search" id="filter-inventory" placeholder="<?php esc_attr_e( 'Codice inventario…', 'dir-wordpress-interface' ); ?>" aria-label="<?php esc_attr_e( 'Cerca per codice inventario', 'dir-wordpress-interface' ); ?>" autocomplete="off" spellcheck="false"/>
		</div>
		<?php endif; ?>
	</div>

	<!-- Griglia (grid / row) -->
	<?php $grid_id = 'item-grid-' . wp_rand(); ?>
	<div id="<?php echo esc_attr( $grid_id ); ?>" 
		     class="item-archive-grid brut-shelf-grid"
		     data-my-collection="<?php echo $my_collection ? '1' : '0'; ?>"
		     data-per-page="<?php echo esc_attr( $per_page ); ?>">
	</div>

	<!-- Paginazione -->
	<div class="brut-item-pagination"></div>

	<!-- Contenitori Splide (nascosti finché necessari) -->
	<div id="splide-main-<?php echo wp_rand(); ?>"  class="splide brut-item-splide-main" style="display:none"><div class="splide__track"><ul class="splide__list"></ul></div></div>
	<div id="splide-thumb-<?php echo wp_rand(); ?>" class="splide is-thumbs brut-item-splide-thumb" style="display:none"><div class="splide__track"><ul class="splide__list"></ul></div></div>
</div>
<?php
	return ob_get_clean();
} );

/*--------------------------------------------------------------
| 3. Helper slide per Splide
--------------------------------------------------------------*/
function item_slide_main( $post_id ) {
	return '<li class="splide__slide">' . get_item_card( $post_id ) . '</li>';
}
function item_slide_thumb( $post_id ) {
	$thumb = get_the_post_thumbnail( $post_id, 'full', array( 'alt' => get_the_title( $post_id ) ) );
	return '<li class="splide__slide">' . $thumb . '</li>';
}

/*--------------------------------------------------------------
| 4. AJAX handler  (filtri + slider support)
--------------------------------------------------------------*/
add_action( 'wp_ajax_tp_filter_items_v2',        'ajax_filter_items' );
add_action( 'wp_ajax_nopriv_tp_filter_items_v2', 'ajax_filter_items' );

function ajax_filter_items() {
	check_ajax_referer( 'item_archive_nonce', 'nonce' );
	$page        	= max( 1, absint( $_POST['page'] ?? 1 ) );
	$per_page       = max( 1, absint( $_POST['per_page'] ?? 24 ) );
	$only_slides 	= ! empty( $_POST['only_slides'] );
	$my_collection  = $_POST['my_collection'];
	$hp 			= absint( $_POST['hp'] ?? 0 );
	$pp 			= absint( $_POST['pp'] ?? 0 );
	$ma 			= absint( $_POST['ma'] ?? 0 );
	$tq 			= absint( $_POST['tq'] ?? 0 );
	$author_id   	= absint( $_POST['author'] ?? 0 );
	$tag_id      	= absint( $_POST['tag']    ?? 0 );
	$museum_id   	= absint( $_POST['museum'] ?? 0 );
	$search_term 	= trim( sanitize_text_field( $_POST['search'] ?? '' ) );

	$user_id = get_current_user_id();
	$favorite_items = array();
	if ( $my_collection ) {
		if ( $user_id ) {
			$favorite_items = get_user_meta( $user_id, 'favorite', false );
			if ( empty( $favorite_items ) ) {
				// Try single array format just in case
				$fav_meta = get_user_meta( $user_id, 'favorite', true );
				if ( is_array( $fav_meta ) ) {
					$favorite_items = $fav_meta;
				}
			}
		}
		
		if ( empty( $favorite_items ) ) {
			wp_send_json_success( [
				'max_pages' => 0,
				'html'      => '<p class="no-results">' . __( 'If you want to see items in this section, you must save them and add them to your collection while browsing the site.', 'dir-wordpress-interface' ) . '</p>',
				'pagination' => '',
				'main'      => '',
				'thumbs'    => '',
			] );
		}
	}

	$args = [
		'post_type'      => 'item',
		'posts_per_page' => $per_page,
		'paged'          => $page,
		'post_status'    => 'publish',
	  	'orderby'        => 'menu_order',
 		'order' 		 => 'ASC'
	];

	if ( $my_collection && ! empty( $favorite_items ) ) {
		$args['post__in'] = array_map( 'intval', $favorite_items );
	}

	/* filtri tassonomie */
	$tax_query = [];
	if ( $hp ) {
	  $tax_query[] = [
		'taxonomy' => 'historical_time_period',
		'field'    => 'term_id',
		'terms'    => [ $hp ],
	  ];
	}
	if ( $pp ) {
	  $tax_query[] = [
		'taxonomy' => 'place_of_production',
		'field'    => 'term_id',
		'terms'    => [ $pp ],
	  ];
	}
	if ( $ma ) {
	  $tax_query[] = [
		'taxonomy' => 'material',
		'field'    => 'term_id',
		'terms'    => [ $ma ],
	  ];
	}
	if ( $tq ) {
	  $tax_query[] = [
		'taxonomy' => 'technique',
		'field'    => 'term_id',
		'terms'    => [ $tq ],
	  ];
	}
	if ( $author_id ) {
		$tax_query[] = [
			'taxonomy' => 'item_author',
			'field'    => 'term_id',
			'terms'    => [ $author_id ],
		];
	}
	if ( $tag_id ) {
		$tax_query[] = [
			'taxonomy' => 'public_tag',
			'field'    => 'term_id',
			'terms'    => [ $tag_id ],
		];
	}
	if ( $tax_query ) {
		$tax_query['relation'] = 'AND';
		$args['tax_query']     = $tax_query;
	}

	/* filtro meta: museo */
	if ( $museum_id ) {
		$args['meta_query'] = [
			[
				'key'     => 'museum',
				'value'   => $museum_id,
				'compare' => '=',
				'type'    => 'NUMERIC',
			],
		];
	}

	if ( $search_term !== '' ) {
		$args['s'] = $search_term;
	}

	$query = new WP_Query( $args );

	/* build output */
	$cards = $main_slides = $thumb_slides = '';
	if ( $query->have_posts() ) {
		while ( $query->have_posts() ) {
			$query->the_post();
			$id           = get_the_ID();
			$cards       .= get_item_card( $id );
			$main_slides .= item_slide_main(  $id );
			$thumb_slides.= item_slide_thumb( $id );
		}
	} else {
		$cards = '<p class="no-results">' . esc_html__( 'Nessuna opera trovata.', 'dir-wordpress-interface' ) . '</p>';
	}
	wp_reset_postdata();

	$pagination = paginate_links( [
		'total'     => $query->max_num_pages,
		'current'   => $page,
		'type'      => 'plain',
		'prev_text' => '«',
		'next_text' => '»',
	] );

	$response = [
		'max_pages' => $query->max_num_pages,
		'main'      => $main_slides,
		'thumbs'    => $thumb_slides,
	];

	if ( ! $only_slides ) {
		$response['html']       = $cards;
		$response['pagination'] = $pagination;
	}

	wp_send_json_success( $response );
}


/* ----------------------------------------------------------------------
 * 1) Card di un singolo “path”
 * -------------------------------------------------------------------- */
/* ----------------------------------------------------------------------
 * Card di un singolo “path”  – versione con featured image
 * -------------------------------------------------------------------- */
function get_path_card( $path_id = 0 ) {

	$path_id = intval( $path_id );
	if ( ! $path_id || get_post_type( $path_id ) !== 'path' ) {
		return '';
	}

	$pod        = pods( 'path', $path_id );
// 	$title      = get_the_title( $path_id );
	$title  	= $pod->field( 'name' );
	$permalink  = get_permalink( $path_id );
	$short_desc = wp_kses_post( $pod->field( 'short_description' ) );

	/* ---------- featured image (srcset automatico) ---------- */
	$thumb_id = get_post_thumbnail_id( $path_id );
	$img_tag  = '';
	if ( $thumb_id ) {
		$img_tag = wp_get_attachment_image(
			$thumb_id,
			'large',            // permette a WP di generare srcset
			false,
			[
				'alt'      => $title,
				'itemprop' => 'contentUrl',
			]
		);
	}

	ob_start(); ?>
<article class="brut-path-card pat-<?php echo esc_attr( $path_id ); ?>">

	<figure>
		<a href="<?php echo esc_url( $permalink ); ?>" class="brut-img-link" itemprop="url">
			<?php echo $img_tag; ?>
		</a>
	</figure>

	<div class="brut-path-card-content">

		<div class="brut-path-card-content-description-section">
			<h3 class="brut-path-card-title" itemprop="name">
				<a href="<?php echo esc_url( $permalink ); ?>" itemprop="url">
					<?php echo esc_html( $title ); ?>
				</a>
			</h3>
			<?php if ( $short_desc ) : ?>
				<p class="brut-path-card-meta" itemprop="description"><?php echo $short_desc; ?></p>
			<?php endif; ?>
		</div>

		<div class="brut-path-card-content-action-section">
			<div class="wp-block-button has-custom-width wp-block-button__width-100">
				<a class="wp-block-button__link wp-element-button"
				   href="<?php echo esc_url( $permalink ); ?>" itemprop="url">
					<?php _e( 'Esplora il percorso', 'dir-wordpress-interface' ); ?>
					<span class="material-symbols-outlined">arrow_forward</span>
				</a>
			</div>
			<?php echo apply_filters( 'path/card/action_section', '', $path_id ); ?>
		</div>

	</div>
</article>
<?php
	$html = ob_get_clean();
	return apply_filters( 'path/card/html', $html, $path_id );
}
/**
 * shortcode per stampare la card di un path
 *
 * uso: [path_card id="123"]
 */
function brut_path_card_shortcode( $atts ) {
    // default e sanificazione
    $atts = shortcode_atts(
        [
            'id' => 0,
        ],
        $atts,
        'path_card'
    );

    $path_id = intval( $atts['id'] );
    if ( ! $path_id ) {
        return ''; // niente id valido
    }

    // chiama la funzione esistente
    return get_path_card( $path_id );
}
add_shortcode( 'path_card', 'brut_path_card_shortcode' );

/* ----------------------------------------------------------------------
 * 2) Shortcode [path_archive]
 * -------------------------------------------------------------------- */
/**
 * Shortcode per visualizzare l'archivio dei CPT Path
 * filtrato per ruoli autore (Admin ed Editor).
 *
 * @return string HTML dell'archivio.
 */
function tp_path_archive_shortcode( $atts ) {

    $atts = shortcode_atts( array(
        'per_page' => 12,
    ), $atts, 'path_archive' );

    /* -- 1. Recupero gli ID degli utenti con permessi speciali -- */
    $privileged_users = get_users( [
        'role__in' => [ 'administrator', 'editor' ],
        'fields'   => 'ID',
    ] );

    // Se non ci sono admin o editor (improbabile, ma gestiamo l'edge case),
    // forziamo un ID inesistente per non mostrare nulla, invece di mostrare tutto.
    if ( empty( $privileged_users ) ) {
        $privileged_users = [ 0 ];
    }

    /* -- 2. Parametri di paginazione e Query -- */
    // Usa 'paged' per archivi standard, 'page' se in static front page
    $paged   = max( 1, get_query_var( 'paged' ) ?: get_query_var( 'page' ) ?: 1 );
    $perpage = intval( $atts['per_page'] );

    $args = [
        'post_type'      => 'path',
        'post_status'    => 'publish',
        'posts_per_page' => $perpage,
        'paged'          => $paged,
        'orderby'        => 'date',
        'order'          => 'DESC',
        'author__in'     => $privileged_users, // <--- Qui avviene la magia
    ];

    $query = new WP_Query( $args );

    ob_start(); 
    ?>

    <div class="path-archive-grid brut-shelf-grid">
    <?php
    if ( $query->have_posts() ) {
        while ( $query->have_posts() ) {
            $query->the_post();
            // Assumo che questa funzione sia definita altrove nel tuo tema/plugin
            if ( function_exists( 'get_path_card' ) ) {
                echo get_path_card( get_the_ID() );
            }
        }
    } else {
        echo '<p class="no-results">' . esc_html__( 'Nessun percorso trovato.', 'dir-wordpress-interface' ) . '</p>';
    }
    ?>
    </div><?php
    /* -- 3. Paginazione -- */
    $total_pages = $query->max_num_pages;
    
    if ( $total_pages > 1 ) {
        // Fix per la base della paginazione
        $base = get_pagenum_link( 1 );
        
        // Logica robusta per mantenere query string esistenti
        $format = '?paged=%#%';
        if ( strpos( $base, '?' ) ) {
            // Se l'URL ha già parametri, aggiungi paged alla fine
            $base = add_query_arg( 'paged', '%#%' );
        } else {
            // Altrimenti usa struttura pretty permalink
            $base = trailingslashit( $base ) . '%_%';
            $format = 'page/%#%/'; // Sovrascriviamo per pretty permalinks standard
        }

        echo '<div class="path-pagination">';
        echo paginate_links( [
            'base'      => $base,
            'format'    => $format,
            'current'   => $paged,
            'total'     => $total_pages,
            'prev_text' => '«',
            'next_text' => '»',
            'type'      => 'list',
        ] );
        echo '</div>';
    }

    /* -- 4. Cleanup -- */
    wp_reset_postdata();

    return ob_get_clean();
}

add_shortcode( 'path_archive', 'tp_path_archive_shortcode' );



/**
 * funzione per gestire le chiamate ajax del tasto "aggiungi ai preferiti"
 */
function ba_ajax_add_favorite() {

	// Define the WordPress "DOING_AJAX" constant.
	if ( ! defined( 'DOING_AJAX' ) ) define( 'DOING_AJAX', true );

	$result = ['success' => 0, 'errors' => [], 'redirect' => ''];

	# dobbiamo controllare che il post esista?
	if(!isset($_POST['postID'])) $result['errors']['global'] = __('Item non disponibile', 'dir-wordpress-interface');

	$user_id = get_current_user_id();
	if(0 == $user_id) {
		$result['errors']['login'] = 'required';
	}

	if(0 == count($result['errors']) && '' == $result['redirect']){
		add_user_meta( $user_id, 'favorite', $_POST['postID'] );
		$result['success'] = 1;
	}

	wp_send_json( $result );
	wp_die();
}
add_action( 'wp_ajax_ba_add_favorite', 'ba_ajax_add_favorite' );
add_action( 'wp_ajax_nopriv_ba_add_favorite', 'ba_ajax_add_favorite' );

/**
 * funzione per gestire le chiamate ajax del tasto "rimuovi dai preferiti"
 */
function ba_ajax_remove_favorite() {

	// Define the WordPress "DOING_AJAX" constant.
	if ( ! defined( 'DOING_AJAX' ) ) define( 'DOING_AJAX', true );

	$result = ['success' => 0, 'errors' => [], 'redirect' => ''];

	# dobbiamo controllare che il post esista?
	if(!isset($_POST['postID'])) $result['errors']['global'] = __('Item non disponibile', 'dir-wordpress-interface');

	$user_id = get_current_user_id();
	if(0 == $user_id) {
		$result['errors']['login'] = 'required';
	}

	if(0 == count($result['errors']) && '' == $result['redirect']){
		delete_user_meta( $user_id, 'favorite', $_POST['postID'] );
		$result['success'] = 1;
	}

	wp_send_json( $result );
	wp_die();
}
add_action( 'wp_ajax_ba_remove_favorite', 'ba_ajax_remove_favorite' );
add_action( 'wp_ajax_nopriv_ba_remove_favorite', 'ba_ajax_remove_favorite' );



// add_action( 'init', 'tp_resave_items_via_pods', 20 );

// function tp_resave_items_via_pods() {
//     if ( ! is_admin() ) {
//         return;
//     }
//     if ( get_option( 'tp_items_resaved_pods' ) ) {
//         return;
//     }

//     $ids = get_posts( array(
//         'post_type'      => 'item',
//         'post_status'    => 'any',
//         'fields'         => 'ids',
//         'nopaging'       => true,
//     ) );

//     foreach ( $ids as $id ) {
//         // istanzia il pod
//         $pod      = pods( 'item', $id );
//         // prendi il valore attuale di main_image in formato ID
//         $image_id = $pod->field( 'main_image', 'id' );

//         if ( $image_id ) {
//             /*
//              * risalva il campo con lo stesso valore
//              * pods lo considererà “modificato” perché lo stiamo
//              * passando nello stesso array da salvare, e quindi
//              * scatterà le istruzioni del field type (attach featured image)
//              */
//             $pod->save( array( 'main_image' => $image_id ) );
//         }
//     }

//     update_option( 'tp_items_resaved_pods', true );
// }
// 
// 














/* ============================================================
 * TAZEBAO – Archivio Item: Ricerca per "Codice inventario" + Filtri + Paginazione (?pg)
 * Versione consolidata (server + client) – NIENTE duplicati.
 * ------------------------------------------------------------
 * Server:
 *  - pre_get_posts: restringe la WP_Query su inventory_code (via Pods → post__in; fallback: postmeta LIKE)
 *  - AJAX handler unico: ajax_filter_items_pg  (usa POST 'pg' e genera paginate_links con ?pg=%#%)
 *
 * Client (inline JS, registrato una sola volta dopo 'item-archive'):
 *  - Aggiunge/riusa input #filter-inventory (ricerca live dal 3° carattere)
 *  - Combina gli altri filtri (select + free search)
 *  - Intercetta paginazione (in capture) con ?pg, previene handler duplicati
 *  - Sincronizza URL (solo 'pg' e 'inventory_code'); gestisce Back/Forward
 *
 * NOTE:
 *  - NON introduce scorrimento automatico in cima alla griglia (lo potrai aggiungere in seguito).
 *  - Lascia intatte le card, Splide e markup esistenti.
 * ============================================================ */


/** ========== SERVER: inventory_code in AJAX filter_items  ========== */
add_action('pre_get_posts', function( WP_Query $q ){
	// Applica SOLO durante l’AJAX dei nostri filtri e SOLO all’archivio 'item'
	if ( ! ( defined('DOING_AJAX') && DOING_AJAX ) ) return;
	if ( empty($_POST['action']) || $_POST['action'] !== 'filter_items' ) return;
	if ( $q->get('post_type') !== 'item' ) return;

	$inv = isset($_POST['inventory_code']) ? sanitize_text_field( wp_unslash($_POST['inventory_code']) ) : '';
	$inv = trim($inv);
	if ( $inv === '' ) return;

	global $wpdb;

	// 1) Tentativo con Pods: query diretta su campo 'inventory_code' della tabella pods_<pod>
	if ( function_exists('pods') ) {
		$where = $wpdb->prepare("LOWER(d.`inventory_code`) LIKE LOWER(%s)", '%'.$inv.'%');
		$pods  = pods('item', [
			'limit'  => 200,
			'where'  => $where,
			'fields' => 'ID',
		]);

		$ids = [];
		if ( $pods && $pods->total() > 0 ) {
			while ( $pods->fetch() ) {
				$pid = (int) $pods->field('ID');
				if ( $pid ) $ids[] = $pid;
			}
		}
		if ( ! empty($ids) ) {
			$q->set('post__in', array_values(array_unique($ids)));
			return; // fermo qui: ho già ristretto l’insieme via post__in
		}
	}

	// 2) Fallback: meta_query LIKE su postmeta('inventory_code')
	$meta_query = (array) $q->get('meta_query');
	if ( ! empty($meta_query) && ! isset($meta_query['relation']) ) {
		$meta_query = ['relation' => 'AND'] + $meta_query;
	}
	$meta_query[] = [
		'key'     => 'inventory_code',
		'value'   => $inv,
		'compare' => 'LIKE',
	];
	$q->set('meta_query', $meta_query);
}, 10, 1);


/** ========== SERVER: handler AJAX UNICO compatibile con 'pg'  ========== */

// Sgancio l’eventuale handler precedente e aggancio il nostro
remove_action( 'wp_ajax_filter_items',        'ajax_filter_items' );
remove_action( 'wp_ajax_nopriv_filter_items', 'ajax_filter_items' );
add_action( 'wp_ajax_filter_items',        'ajax_filter_items_pg' );
add_action( 'wp_ajax_nopriv_filter_items', 'ajax_filter_items_pg' );

function ajax_filter_items_pg() {

	check_ajax_referer( 'item_archive_nonce', 'nonce' );

	// Lettura pagina da 'pg' (fallback su 'page' per retro‐compatibilità)
	$pg          = isset($_POST['pg']) ? absint($_POST['pg']) : ( isset($_POST['page']) ? absint($_POST['page']) : 1 );
	$page        = max(1, $pg);
	$only_slides = ! empty($_POST['only_slides']);

	// Filtri
	$hp        = absint( $_POST['hp']     ?? 0 );
	$pp        = absint( $_POST['pp']     ?? 0 );
	$ma        = absint( $_POST['ma']     ?? 0 );
	$tq        = absint( $_POST['tq']     ?? 0 );
	$author_id = absint( $_POST['author'] ?? 0 );
	$tag_id    = absint( $_POST['tag']    ?? 0 );
	$museum_id = absint( $_POST['museum'] ?? 0 );
	$search    = trim( sanitize_text_field( $_POST['search'] ?? '' ) );
	$inv_code  = trim( sanitize_text_field( wp_unslash( $_POST['inventory_code'] ?? '' ) ) );

	$per_page = 24;

	$args = [
		'post_type'      => 'item',
		'posts_per_page' => $per_page,
		'paged'          => $page,
		'post_status'    => 'publish',
		'orderby'        => 'menu_order',
		'order'          => 'ASC',
	];

	// Tax query (AND su filtri selezionati)
	$tax_query = [];
	if ( $hp ) $tax_query[] = ['taxonomy'=>'historical_time_period','field'=>'term_id','terms'=>[$hp]];
	if ( $pp ) $tax_query[] = ['taxonomy'=>'place_of_production',   'field'=>'term_id','terms'=>[$pp]];
	if ( $ma ) $tax_query[] = ['taxonomy'=>'material',              'field'=>'term_id','terms'=>[$ma]];
	if ( $tq ) $tax_query[] = ['taxonomy'=>'technique',             'field'=>'term_id','terms'=>[$tq]];
	if ( $author_id ) $tax_query[] = ['taxonomy'=>'item_author','field'=>'term_id','terms'=>[$author_id]];
	if ( $tag_id )    $tax_query[] = ['taxonomy'=>'public_tag','field'=>'term_id','terms'=>[$tag_id]];
	if ( $tax_query ) {
		$tax_query['relation'] = 'AND';
		$args['tax_query']     = $tax_query;
	}

	// Meta: museo (relation su postmeta)
	if ( $museum_id ) {
		$args['meta_query'][] = [
			'key'     => 'museum',
			'value'   => $museum_id,
			'compare' => '=',
			'type'    => 'NUMERIC',
		];
	}

	// Ricerca libera
	if ( $search !== '' ) {
		$args['s'] = $search;
	}

	// NB: inventory_code viene gestito a livello di pre_get_posts (sopra) per evitare doppioni

	$query = new WP_Query( $args );

	// Build output (riusa le tue funzioni card/slide esistenti)
	$cards = $main_slides = $thumb_slides = '';
	if ( $query->have_posts() ) {
		while ( $query->have_posts() ) {
			$query->the_post();
			$id            = get_the_ID();
			$cards        .= get_item_card( $id );
			$main_slides  .= item_slide_main(  $id );
			$thumb_slides .= item_slide_thumb( $id );
		}
	} else {
		$cards = '<p class="no-results">' . esc_html__( 'Nessuna opera trovata.', 'dir-wordpress-interface' ) . '</p>';
	}
	wp_reset_postdata();

	// Paginazione con ?pg=%#%  (compromesso: la nascondo se inventory attivo e <= per_page)
	$total_pages = max( 1, (int) $query->max_num_pages );
	$hide_pagination = ( $inv_code !== '' && $query->found_posts <= $per_page );

	if ( $hide_pagination ) {
		$pagination = '';
	} else {
		$base_url = home_url( add_query_arg( [], $_SERVER['REQUEST_URI'] ?? '' ) );
		$base     = add_query_arg( 'pg', '%#%', remove_query_arg( 'pg', $base_url ) );
		$pagination = paginate_links( [
			'base'      => $base,
			'format'    => '',
			'current'   => $page,
			'total'     => $total_pages,
			'prev_text' => '«',
			'next_text' => '»',
			'type'      => 'plain',
		] );
	}

	$response = [
		'max_pages' => $total_pages,
		'found'     => (int) $query->found_posts,
		'per_page'  => (int) $per_page,
		'main'      => $main_slides,
		'thumbs'    => $thumb_slides,
		'html'      => $cards,
		'pagination'=> $pagination, // può essere stringa vuota
	];

	wp_send_json_success( $response );
}


/** ========== CLIENT: inline JS UNICO (gestione inventory + filtri + paginazione ?pg)  ========== */
add_action('wp_enqueue_scripts', function(){

	$js = <<<'JS'
(function(){
  // Evita doppi attach se il file viene incluso due volte
  if (window.__DIR_INV_FILTER_PG__) return;
  window.__DIR_INV_FILTER_PG__ = true;

  // ---------------- utils ----------------
  function ready(fn){ if(document.readyState==='loading'){document.addEventListener('DOMContentLoaded',fn);} else { fn(); } }
  function val(el){ return el ? (el.value||'').trim() : ''; }
  function getURL(){ return new URL(window.location.href); }
  function setParam(url,k,v){ if(v===undefined||v===null||v===''){ url.searchParams.delete(k);} else { url.searchParams.set(k,v); } }
  function debounce(fn,ms){ var t; return function(){ var a=arguments,c=this; clearTimeout(t); t=setTimeout(function(){ fn.apply(c,a); }, ms||200); }; }

  ready(function(){
    var MIN = 3;
    var ajaxUrl = (window.ItemArchive && ItemArchive.ajax_url) ? ItemArchive.ajax_url : (window.ajaxurl || '/wp-admin/admin-ajax.php');

    // Assicura presenza dell'input inventory
    function ensureInventoryInput(){
       return document.getElementById('filter-inventory');
    }

    // Riferimenti dinamici (il DOM può essere rimpiazzato)
    function R(){
      return {
        inv:  document.getElementById('filter-inventory') || ensureInventoryInput(),
        hp:   document.getElementById('filter-hp'),
        pp:   document.getElementById('filter-pp'),
        ma:   document.getElementById('filter-ma'),
        tq:   document.getElementById('filter-tq'),
        mus:  document.getElementById('filter-museum'),
        aut:  document.getElementById('filter-author'),
        tag:  document.getElementById('filter-tag'),
        free: document.getElementById('filter-search'),
        grid: document.getElementById('item-grid'),
        pag:  document.getElementById('item-pagination'),
        ulM:  document.querySelector('#splide-main  .splide__list'),
        ulT:  document.querySelector('#splide-thumb .splide__list')
      };
    }

    // Lettura/scrittura URL (SOLO 'pg' e 'inventory_code')
    function readPg(){ var u=getURL(), p=u.searchParams.get('pg'); return p?parseInt(p,10)||1:1; }
    function readInv(){ var u=getURL(); return (u.searchParams.get('inventory_code')||'').trim(); }
    function syncURL(pg, inv, push){
      var u = getURL();
      setParam(u,'pg', pg && pg>1 ? String(pg) : '');
      setParam(u,'inventory_code', (inv && inv.length>=MIN) ? inv : '');
      var href = u.toString();
      if(href !== window.location.href){
        (push?history.pushState:history.replaceState).call(history, {pg:pg,inventory_code:inv}, '', href);
      }
    }

    // Costruisce il payload unificato per l'AJAX
    function buildFD(pg){
      var r = R();
      var fd = new FormData();
      fd.append('action','filter_items');
      fd.append('nonce', (window.ItemArchive && ItemArchive.nonce) ? ItemArchive.nonce : '');
      fd.append('pg', String(pg||1));

      if(r.hp)  fd.append('hp',     val(r.hp));
      if(r.pp)  fd.append('pp',     val(r.pp));
      if(r.ma)  fd.append('ma',     val(r.ma));
      if(r.tq)  fd.append('tq',     val(r.tq));
      if(r.mus) fd.append('museum', val(r.mus));
      if(r.aut) fd.append('author', val(r.aut));
      if(r.tag) fd.append('tag',    val(r.tag));
      if(r.free)fd.append('search', val(r.free));

      var inv = val(r.inv);
      if(inv.length >= MIN) fd.append('inventory_code', inv);

      return fd;
    }

    // Applica la risposta senza mai "undefined"
    var _applying = false;
    function apply(json){
      if(!json || json.success !== true || !json.data) return;
      _applying = true;
      var r = R(), d = json.data;

      if(r.grid && typeof d.html === 'string')   r.grid.innerHTML = d.html;
      if(r.pag  && typeof d.pagination === 'string') r.pag.innerHTML  = d.pagination;
      if(r.ulM  && typeof d.main === 'string')   r.ulM.innerHTML   = d.main;
      if(r.ulT  && typeof d.thumbs === 'string') r.ulT.innerHTML   = d.thumbs;

      document.dispatchEvent(new CustomEvent('dir:itemsUpdated', { detail: d }));
      setTimeout(function(){ _applying = false; }, 0);
    }

    // Loader centrale
    var load = debounce(function(pg, push){
      var r   = R();
      var inv = val(r.inv);
      if(inv.length < MIN) inv = '';
      syncURL(pg||1, inv, !!push);

      fetch(ajaxUrl, { method:'POST', credentials:'same-origin', body: buildFD(pg||1) })
        .then(function(x){ return x.json(); })
        .then(function(json){ apply(json); })
        .catch(function(e){ console.error('filter_items (pg) error', e); });
    }, 180);

    // === Bind: Inventory (live, dal 3° carattere) → reset pg=1
    document.addEventListener('input', function(e){
      if(e.target && e.target.id==='filter-inventory'){ load(1, true); }
    }, true);

    // === Bind: altri filtri (select) → reset pg=1 (mantiene inventory se valido)
    var SEL = new Set(['filter-hp','filter-pp','filter-ma','filter-tq','filter-museum','filter-author','filter-tag']);
    document.addEventListener('change', function(e){
      var t=e.target;
      if(!t||!t.id||!SEL.has(t.id)) return;
      load(1, true);
      // piccolo richiamo tardivo per “vincere” eventuali refresh di script terzi
      setTimeout(function(){ load(1, false); }, 50);
    }, true);

    // === Bind: ricerca libera (input) → reset pg=1
    document.addEventListener('input', debounce(function(e){
      var t=e.target;
      if(!t || t.id!=='filter-search') return;
      load(1, true);
    },300), true);

    // === Paginazione (?pg): intercetta in CAPTURE e previeni handler duplicati
    document.addEventListener('click', function(e){
      var a = e.target.closest ? e.target.closest('#item-pagination a') : null;
      if(!a) return;
      try{ e.preventDefault(); e.stopImmediatePropagation(); }catch(_){}
      var href = a.getAttribute('href')||'';
      var m = href.match(/[?&]pg=(\d+)/);
      var pg = m && m[1] ? (parseInt(m[1],10)||1) : 1;
      load(pg, true);
      setTimeout(function(){ load(pg, false); }, 50);
    }, true);

    // === Back/Forward: riallinea input e ricarica stato coerente
    window.addEventListener('popstate', function(){
      if(_applying) return;
      var inv = readInv();
      var inp = ensureInventoryInput(); if(inp) inp.value = inv;
      var pg  = readPg();
      load(pg, false);
    });

    // === Init: allinea l’input all’URL (senza fetch)
    (function init(){
      var inv = readInv();
      var inp = ensureInventoryInput(); if(inp) inp.value = inv;
    })();
  });
})();
JS;

	wp_add_inline_script( 'item-archive', $js, 'after' );
}, 100);

add_action('wp_enqueue_scripts', function(){
	$js = <<<'JS'
document.addEventListener('click', function(e) {
  // intercetta click su qualsiasi link dentro la paginazione
  const link = e.target.closest('#item-pagination a');
  if (!link) return;

  // dopo un piccolo delay (per lasciare aggiornare la griglia)
  setTimeout(() => {
    const grid = document.getElementById('item-grid');
    if (grid) {
      const y = grid.getBoundingClientRect().top + window.pageYOffset - 80; // offset header
      window.scrollTo({ top: Math.max(0, y), behavior: 'smooth' });
    }
  }, 300);
}, true);
JS;
	wp_add_inline_script('item-archive', $js, 'after');
}, 120);
