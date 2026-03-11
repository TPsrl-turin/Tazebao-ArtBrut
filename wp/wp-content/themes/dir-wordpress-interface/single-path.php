<?php
/**
 * Template: Single “Path” – fullPage.js
 * Tutto autoconcluso: enqueue di CSS/JS, slide Path+Steps+Footer,
 * bottone fullscreen in basso a destra.
 */

/* ------------------------------------------------------------------
 * ENQUEUE FullPage (solo in questo template)
 * ---------------------------------------------------------------- */
wp_enqueue_style(
	'fullpage',
	'https://cdnjs.cloudflare.com/ajax/libs/fullPage.js/4.0.20/fullpage.min.css',
	array(),
	'4.0.20'
);

wp_enqueue_script(
	'fullpage-extensions',
	'https://cdnjs.cloudflare.com/ajax/libs/fullPage.js/4.0.20/fullpage.extensions.min.js',
	array(),
	'4.0.20',
	true
);

get_header();
echo do_blocks('<!-- wp:block {"ref":3059} /-->');
/* ---------- 1. Helper immagine ---------- */
if ( ! function_exists( 'path_get_media_url' ) ) {
	function path_get_media_url( $v ) {
		if ( is_array( $v ) ) {
			$first = reset( $v );
			if ( isset( $first['url'] ) || isset( $first['guid'] ) ) {
				return $first['url'] ?? $first['guid'];
			}
			if ( isset( $v['url'] ) || isset( $v['guid'] ) ) {
				return $v['url'] ?? $v['guid'];
			}
		}
		if ( is_numeric( $v ) ) {
			return wp_get_attachment_url( (int) $v ) ?: '';
		}
		return is_string( $v ) ? $v : '';
	}
}

/* ---------- 2. Meta Path ---------- */
$pid        = get_the_ID();
$bg_color   = pods_field( 'path', $pid, 'background_color' );
$bg_img     = path_get_media_url( pods_field( 'path', $pid, 'background_image' ) );
$text_color = pods_field( 'path', $pid, 'text_color' );
$custom_css = pods_field( 'path', $pid, 'custom_style' );

/* ---------- 3. Steps IDs ---------- */
$raw_steps = pods_field( 'path', $pid, 'steps' );
$step_ids  = array();
if ( is_array( $raw_steps ) ) {
	foreach ( $raw_steps as $s ) {
		if ( is_numeric( $s ) )               $step_ids[] = (int)$s;
		elseif ( isset( $s['ID'] ) )          $step_ids[] = (int)$s['ID'];
		elseif ( is_object( $s ) && isset( $s->ID ) ) $step_ids[] = (int)$s->ID;
	}
} elseif ( is_string( $raw_steps ) ) {
	$step_ids = array_map( 'intval', array_filter( explode( ',', $raw_steps ) ) );
}

/* Anchors fullPage */
$anchors = array_merge( array( 'intro' ),
	array_map( fn($i)=>'step-'.($i+1), array_keys( $step_ids ) ),
	array( 'footer' ) );

/* ---------- 4. Style builder ---------- */
function path_style( $img, $bg, $txt ){
	$s = array();
	if ( $img )        	$s[] = 'background:url(' . esc_url_raw( $img ) . ') center/cover no-repeat';
	if ( $bg )     		$s[] = 'background-color:' . esc_attr( $bg );
	if ( $bg && $img ) 	$s[] = 'background-blend-mode: multiply';
	if ( $txt )        	$s[] = 'color:' . esc_attr( $txt );
	return $s ? ' style="' . esc_attr( implode( ';', $s ) ) . '"' : '';
}
?>

<div id="fullpage">

	<!-- Slide intro Path -->
	<section class="section path-hero"<?php echo path_style( $bg_img, $bg_color, $text_color ); ?> data-anchor="intro">
		<div class="fp-content fp-intro">
			<div class="small-column-container">
				<h1><?php the_title(); ?></h1>

				<?php
				$desc    = pods_field( 'path', $pid, 'description' );
				$curator = pods_field( 'path', $pid, 'curator' );

				if ( $desc )   echo '<div class="path-description">' . wpautop( wp_kses_post( $desc ) ) . '</div>';
				if ( $curator) echo '<p class="path-curator">' . __( 'A cura di:', 'dir-wordpress-interface' ) . ' ' . esc_html( $curator ) . '</p>';
				?>
			</div>
		</div>
	</section>

	<?php foreach ( $step_ids as $idx => $sid ) :

		$s_bg_color   = pods_field( 'step', $sid, 'background_color' );
		$s_bg_img     = path_get_media_url( pods_field( 'step', $sid, 'background_image' ) );
		$s_text_color = pods_field( 'step', $sid, 'text_color' );
		$s_css        = pods_field( 'step', $sid, 'css' );

		$final_img   = $s_bg_img;
		$final_bg    = $s_bg_color;
		$final_txt   = $s_text_color ?: $text_color;
		$anchor      = 'step-' . ( $idx + 1 );
		?>
		<style id="step-<?php echo esc_attr( $sid ); ?>-css">
			<?php 
				if ( $final_txt ) echo '.step-'. esc_attr( $sid ).' .brut-item-card-content{ color: ' . $final_txt . '!important; border-color:' . $final_txt . '};';
				if ( $s_css ) echo '.step-'. esc_attr( $sid ).'{' . wp_kses_post( $s_css ) .'}'; 
			?>
		</style>
		<section class="section step-<?php echo esc_attr( $sid ); ?>"<?php echo path_style( $final_img, $final_bg, $final_txt ); ?> data-anchor="<?php echo esc_attr( $anchor ); ?>">
			<div class="fp-content fp-step">
				<div class="small-column-container">
					<h2><?php echo esc_html( get_the_title( $sid ) ); ?></h2>
					<?php
						$c1 = pods_field( 'step', $sid, 'content_1' );
						if ( $c1 ) echo '<div class="step-content-1">' . wpautop( wp_kses_post( $c1 ) ) . '</div>';

						/* Items */
						$raw_items = pods_field( 'step', $sid, 'items' );
						$item_ids  = array();
						if ( is_array( $raw_items ) ) {
							foreach ( $raw_items as $it ) {
								$item_ids[] = is_numeric( $it ) ? (int)$it : ( isset( $it['ID'] ) ? (int)$it['ID'] : ( isset( $it->ID ) ? (int)$it->ID : 0 ) );
							}
						} elseif ( is_string( $raw_items ) ) {
							$item_ids = array_map( 'intval', array_filter( explode( ',', $raw_items ) ) );
						}
						if ( $item_ids ) {
							echo '<div class="step-items">';
							
							if(1 == count($item_ids)){
								
								echo get_item_card( $iids[0] );
								
							}
							else{
								?>
								<div class="wp-block-group alignfull">
									<div class="wp-block-group alignwide">
										<div id="slider-<?= $idx ?>" class="splide">
								 			 <div class="splide__track">
											<ul class="splide__list">
												<?php 
													foreach ( $item_ids as $iid ) {
														echo '<li class="splide__slide">';
														echo get_item_card( $iid );
														echo '</li>';
													}
												?>
											</ul>
										</div>
									</div>
								  </div>
								</div>
			
								<?php
							}
							
							echo '</div>';
							
						}

						$c2 = pods_field( 'step', $sid, 'content_2' );
						if ( $c2 ) echo '<div class="step-content-2">' . wpautop( wp_kses_post( $c2 ) ) . '</div>';
					?>
				</div>
				
			</div>
		</section>
	<?php endforeach; ?>
	<section class="section fp-auto-height">
		<div class="fp-content fp-step">
			<footer id="colophon" class="site-footer">
				<?=  do_blocks( '<!-- wp:block {"ref":3084} /-->' ); ?>	
			</footer><!-- #colophon -->
		</div>
	</section>
		
</div><!-- /#fullpage -->

<?php wp_footer(); ?>

<?php if ( $custom_css ) : ?>
	<style id="path-custom-css"><?php echo wp_kses_post( $custom_css ); ?></style>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded',function(){
	new fullpage('#fullpage',{
		licenseKey:'gplv3-license',
		anchors:<?php echo wp_json_encode( $anchors ); ?>,
		navigation:true,
		scrollOverflow:true,
		scrollOverflowReset:true
	});
});
</script>

<!-- Bottone fullscreen -->

<button id="fp-full-toggle" class="fp-full-btn" aria-pressed="false">
	<span class="material-symbols-outlined">open_in_full</span>
</button>


<script>
(function(){
	const btn=document.getElementById('fp-full-toggle');
	const icon=btn.querySelector('.material-symbols-outlined');
	const KEY='fpFull';
	if(sessionStorage.getItem(KEY)==='1') toggle(true);
	btn.addEventListener('click',()=>toggle());
	function toggle(force){
	  const on = (force!==undefined) ? force : !document.body.classList.contains('fp-full');
	  document.body.classList.toggle('fp-full', on);
	  btn.setAttribute('aria-pressed', on);
	  icon.textContent = on ? 'close_fullscreen' : 'open_in_full';
	  sessionStorage.setItem(KEY, on ? '1' : '0');
	}
})();
	
	var elms = document.getElementsByClassName( 'splide' );
	for ( var i = 0; i < elms.length; i++ ) {
	  new Splide( elms[ i ], {
		  type: "slide",
		  perPage: 1,
		  pagination: false,
		  arrows: true,
	  } ).mount();
	}
</script>
</div><!-- #page -->



</body>
</html>
