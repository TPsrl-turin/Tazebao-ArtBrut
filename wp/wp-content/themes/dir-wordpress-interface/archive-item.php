<?php
/**
 * Template: archive-item.php
 * Mostra la griglia delle “opere” (Custom Post Type: item).
 */

get_header();

/*--------------------------------------------------------------
| Query: 12 post per pagina
--------------------------------------------------------------*/
$paged = get_query_var( 'paged' ) ? get_query_var( 'paged' ) : 1;

$item_query = new WP_Query( array(
	'post_type'      => 'item',
	'posts_per_page' => 12,
	'paged'          => $paged,
) );
?>
<main id="primary" class="site-main">

<?php if ( $item_query->have_posts() ) : ?>

	<div class="brut-shelf-grid">
		<?php
		while ( $item_query->have_posts() ) :
			$item_query->the_post();

			// Output della singola card
			echo get_item_card( get_the_ID() );

		endwhile;
		?>
	</div><!-- /.brut-shelf-grid -->

	<?php
	/*----------------------------------------------------------
	| Paginazione
	----------------------------------------------------------*/
	echo paginate_links( array(
		'total'     => $item_query->max_num_pages,
		'current'   => $paged,
		'mid_size'  => 2,
		'prev_text' => __( '« Precedente', 'dir-wordpress-interface' ),
		'next_text' => __( 'Successivo »', 'dir-wordpress-interface' ),
	) );
	?>

<?php
else :
	echo '<p>' . __( 'Nessuna opera trovata.', 'dir-wordpress-interface' ) . '</p>';
endif;

wp_reset_postdata();
?>
</main>

<?php get_footer(); ?>