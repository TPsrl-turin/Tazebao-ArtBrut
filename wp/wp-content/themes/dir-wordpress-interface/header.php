<?php
/**
 * The header for our theme
 *
 * @package DIR_-_WordPress_interface
 */
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="profile" href="https://gmpg.org/xfn/11">
	<?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<div id="page" class="site">
	<a class="skip-link screen-reader-text" href="#primary">
		<?php esc_html_e( 'Skip to content', 'dir-wordpress-interface' ); ?>
	</a>

	<header id="masthead" class="site-header alignfull has-dark-grey-background-color">

		<div class="masthead-inner">
			<div class="site-branding">
				<?php
				the_custom_logo();

				$dir_wordpress_interface_description = get_bloginfo( 'description', 'display' );
				if ( $dir_wordpress_interface_description || is_customize_preview() ) :
					?>
					<p class="site-description">
						<?php echo esc_html( $dir_wordpress_interface_description ); ?>
					</p>
				<?php endif; ?>
			</div><!-- .site-branding -->

			<nav id="site-navigation" class="main-navigation">
				<button class="menu-toggle" aria-controls="primary-menu" aria-expanded="false">
					<?php esc_html_e( 'Menu', 'dir-wordpress-interface' ); ?>
				</button>
				<?php
				wp_nav_menu(
					[
						'theme_location' => 'menu-1',
						'menu_id'        => 'primary-menu',
					]
				);
				?>
			</nav><!-- #site-navigation -->

		</div><!-- .masthead-inner -->

	</header><!-- #masthead -->
