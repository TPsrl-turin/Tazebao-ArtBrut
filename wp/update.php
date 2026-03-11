<?php
//Questo permette a un file nella root di un sito in WP di usare il DB di WordPress.
define('WP_USE_THEMES', false);
if ( ! defined( 'FS_METHOD' ) ) define( 'FS_METHOD', 'direct' );
require('wp-load.php');

ignore_user_abort( true );
set_time_limit( 0 );
ini_set('default_socket_timeout', 10);
ini_set('memory_limit', '-1');

if(!function_exists('save_items')){
	/**
	 * Saves the metadata for an entity on MongoDB.
	 *
	 * @param int     $post_id The ID of the post.
	 * @param WP_Post $post    The post object.
	 * @param bool    $update  Whether this is an update or not.
	 *
	 * @return void
	 */
	function save_items( int $post_id, WP_Post $post, bool $update ): void {
		if ( 'item' !== $post->post_type ) return;
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
		if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) return;
		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) return;

		$item = pods( 'item', $post_id );
		$item->save( 'dir_id', $post_id );

		$taxs = [
			'cultural_context'       => wp_get_post_terms( $post_id, 'cultural_context', ['fields' => 'ids'] ),
			'historical_time_period' => wp_get_post_terms( $post_id, 'historical_time_period', ['fields' => 'ids'] ),
			'item_author'            => wp_get_post_terms( $post_id, 'item_author', ['fields' => 'ids'] ),
			'material'               => wp_get_post_terms( $post_id, 'material', ['fields' => 'ids'] ),
			'place_of_production'    => wp_get_post_terms( $post_id, 'place_of_production', ['fields' => 'ids'] ),
			'public_tag'             => wp_get_post_terms( $post_id, 'public_tag', ['fields' => 'ids'] ),
			'technique'              => wp_get_post_terms( $post_id, 'technique', ['fields' => 'ids'] ),
		];

		$lang = pll_get_post_language( $post_id );
		$list = pll_get_post_translations( $post_id );
		unset($list[$lang]);

		$post_data = [
			'post_title'                               => $post->post_title,
			'post_lang_choice'                         => $lang,
			'post_tr_lang'                             => $list,
			'tax_input'                                => $taxs,
			'pods_meta_iccd_code'                      => $item->field( 'iccd_code' ),
			'pods_meta_inventory_code'                 => $item->field( 'inventory_code' ),
			'pods_meta_alternative_name'               => $item->field( 'alternative_name' ),
			'pods_meta_year_of_origin'                 => $item->field( 'year_of_origin' ),
			'pods_meta_description'                    => $item->field( 'description' ),
			'pods_meta_short_description'              => $item->field( 'short_description' ),
			'pods_meta_detailed_description'           => $item->field( 'detailed_description' ),
			'pods_meta_text_for_screen_reader'         => $item->field( 'text_for_screen_reader' ),
			'pods_meta_main_image'                     => $item->field( 'main_image.ID' ) ?: '',
			'pods_meta_media_gallery'                  => $item->field( 'media_gallery.ID' ) ?: [],
			'pods_meta_attachments'                    => $item->field( 'attachments.ID' ) ?: [],
			'pods_meta_current_location'               => $item->field( 'current_location' ),
			'pods_meta_museum'                         => $item->field( 'museum.ID' ) ?: '',
			//'pods_meta_3d_resources'                   => $item->field( '3d_resources.ID' ) ?: [],
			'pods_meta_3d_files_sources_paths'         => $item->field( '3d_files_sources_paths' ),
			'pods_meta_config_json'                    => $item->field( 'config_json' ),
			'pods_meta_sensitive_content'              => $item->field( 'sensitive_content' ),
			'pods_meta_content_advisory'               => $item->field( 'content_advisory' ),
			'pods_meta_dimensions'                     => $item->field( 'dimensions' ),
			'pods_meta_exhibitions'                    => $item->field( 'exhibitions' ),
			'pods_meta_bibliography'                   => $item->field( 'bibliography' ),
			'pods_meta_historical_time_period_details' => $item->field( 'historical_time_period_details' ),
			'pods_meta_authorship_details'             => $item->field( 'authorship_details' ),
			'pods_meta_place_of_production_details'    => $item->field( 'place_of_production_details' ),
			'pods_meta_manufacturing_details'          => $item->field( 'manufacturing_details' ),
			'pods_meta_parent_item'                    => $item->field( 'parent_item.ID' ) ?: '',
			'pods_meta_related_items'                  => $item->field( 'related_items.ID' ) ?: [],
		];

		ksort($post_data);

		$hash = sha1( json_encode( $post_data ) );
		update_post_meta( $post_id, 'hash', $hash );
		update_post_meta( $post_id, 'o_id', $post_id );
	}
	add_action( 'save_post', 'save_items', 20, 3 );
}

$taxonomies = [
	'cultural_context',
	'historical_time_period',
	'item_author',
	'material',
	'place_of_production',
	'technique',
	'public_tag',
];
foreach ($taxonomies as $taxonomy) {
	echo "\n=== taxonomy [$taxonomy] ===\n\n";
	$local_terms = get_terms( [
		'fields'     => 'ids',
		'taxonomy'   => $taxonomy,
		'hide_empty' => false, // also retrieve terms which are not used yet
		'lang'       => '',
	] );
	foreach ($local_terms as $local_term) {
		add_term_meta( $local_term, 'o_id', $local_term, true );
	}
}

$paged = (int) get_site_option( 'items_progress_paged', 1 );
echo "\n=== Items [$paged] ===\n";
update_all_post( 'item', $paged );
$paged ++;
update_site_option( 'items_progress_paged', $paged );

echo "\n\nFine.\n";


function update_all_post($post_type, $paged){
	$all_posts = get_posts([
		'post_type'           => $post_type,
		'post_status'         => ['publish'], //, 'draft', 'auto-draft', 'pending', 'future', 'private'
		'orderby'             => 'ID',
		'order'               => 'ASC',
		'lang'                => '',
		'ignore_sticky_posts' => 1,
		'posts_per_page'      => 500,
		'paged'               => $paged,
	]);
	$total = count($all_posts);
	foreach ($all_posts as $i => $single_post){
		echo ($i+1) .' / '. $total .' : ['. $single_post->ID .'] - '. $single_post->post_title ."\n";
		$single_post->post_title = $single_post->post_title.'';
		wp_update_post( $single_post );
		flush();
	}
}