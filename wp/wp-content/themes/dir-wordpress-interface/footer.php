<?php
/**
 * The template for displaying the footer
 *
 * Contains the closing of the #content div and all content after.
 *
 * @link https://developer.wordpress.org/themes/basics/template-files/#template-partials
 *
 * @package DIR_-_WordPress_interface
 */

?>

	<footer id="colophon" class="site-footer">
		<?= pll_current_language() == 'it' ? do_blocks( '<!-- wp:block {"ref":3084} /-->' ) : do_blocks('<!-- wp:block {"ref":5873} /-->'); ?>
		<?= do_blocks('<!-- wp:block {"ref":3059} /-->'); ?>	
	</footer><!-- #colophon -->
</div><!-- #page -->

<?php wp_footer(); ?>

</body>
</html>
