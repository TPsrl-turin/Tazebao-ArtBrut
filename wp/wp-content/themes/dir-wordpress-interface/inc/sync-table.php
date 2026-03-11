<?php
// File Security Check
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

if(!class_exists('DIRsyncTable')){
	class DIRsyncTable extends WP_List_Table {

		/** Class constructor */
		public function __construct() {

			parent::__construct( [
				'singular' => __( 'Item', 'dir-wordpress-interface' ), //singular name of the listed records
				'plural'   => __( 'Items', 'dir-wordpress-interface' ), //plural name of the listed records
				'ajax'     => false //does this table support ajax?
			] );

		}

		/**
		 * Retrieve items data from API
		 *
		 * @return array
		 */
		public static function fetch_items(): array {

			$languages  = pll_languages_list();
			$url_dir    = get_option('options_url-dir');
			$collection = get_option('options_collection');

			$remote_url = $url_dir.'/wp-json/dir/v1/items/';
			if('' != $collection) $remote_url .= '?v=1&collection='.$collection;

			$items_call = wp_remote_get($remote_url, ['sslverify' => false]);
			$items_body = wp_remote_retrieve_body($items_call);
			$items_remote = json_decode($items_body, true);
			if(!is_array($items_remote)) $items_remote = [];

			$items = new WP_Query( [
				'post_type'      => 'item',
				'post_status'    => 'publish',
				'order'          => 'ASC',
				'orderby'        => 'ID',
				'nopaging'       => true,
				'posts_per_page' => -1,
			] );

			$items_local = [];
			if ( $items->have_posts() ) {
				while ( $items->have_posts() ) {
					$items->the_post();
					$id = get_the_ID();
					$items_local[] = [
						'ID'     => $id,
						'origin' => get_post_meta( $id, 'o_id', true ),
						'name'   => wp_kses_post(get_the_title()),
						'hash'   => get_post_meta( $id, 'hash', true ),
					];
				}
			}
			wp_reset_postdata();

			$items_remote_ID = array_column($items_remote, 'ID');
			$items_local_ID  = array_column($items_local,  'origin');

			$item_full_list = [];
			foreach ($items_remote as $value) {
				if(false != $value['lang'] && !in_array($value['lang'], $languages)) continue;
				$search = array_search($value['ID'], $items_local_ID);

				if(false !== $search){
					$value['origin'] = $value['ID'];
					$value['ID'] = $items_local[$search]['ID'];
					if($items_local[$search]['hash'] != $value['hash']){
						$value['status_value'] = 'tosync';
						$value['status'] = __( 'to sync', 'dir-wordpress-interface' );
					}else{
						$value['status_value'] = 'synced';
						$value['status'] = __( 'synced', 'dir-wordpress-interface' );
					}
				}else{
					$value['origin'] = $value['ID'];
					$value['ID'] .= 'i';
					$value['status_value'] = 'toimport';
					$value['status'] = __( 'to import', 'dir-wordpress-interface' );
				}
				$item_full_list[] = $value;
			}

			$diff = array_diff( $items_local_ID, $items_remote_ID );
			foreach ($diff as $key => $value) {
				$v = $items_local[$key];
				$v['status_value'] = 'todelete';
				$v['status'] = __( 'to delete', 'dir-wordpress-interface' );
				$item_full_list[] = $v;
			}

			return $item_full_list;

		}

		public static function sync_terms(){

			$languages  = pll_languages_list();
			$url_dir    = get_option('options_url-dir');
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
				$remote_url = $url_dir.'wp-json/wp/v2/'.$taxonomy;

				$terms_call = wp_remote_get($remote_url, ['sslverify' => false]);
				$terms_body = wp_remote_retrieve_body($terms_call);
				$terms_remote = json_decode($terms_body, true);
				if(!is_array($terms_remote)) continue;

				foreach ($terms_remote as $term) {
					if(false != $term['polylang']['lang'] && !in_array($term['polylang']['lang'], $languages)) continue;

					$update_term = true;
					$local_term = self::get_local_term_id( $term['id'], $taxonomy );

					if($local_term === 0){
						$new_term = wp_insert_term( wp_kses_post( $term['name'] ), $taxonomy, [
							'slug'        => $term['slug'],
							'parent'      => $term['parent'],
							'description' => $term['description'],
						] );
						if (is_wp_error($new_term)) {
							$local_term = $new_term->get_error_data('term_exists') ?? 0;
						}else {
							$local_term  = $new_term['term_id'];
							$update_term = false;
						}
					}
					if($local_term !== 0){
						if($update_term) {
							wp_update_term( $local_term, $taxonomy, [
								'name'        => wp_kses_post( $term['name'] ),
								'slug'        => $term['slug'],
								'parent'      => $term['parent'],
								'description' => $term['description'],
							] );
						}

						add_term_meta( $local_term, 'o_id', $term['id'], true );
						pll_set_term_language($local_term, $term['polylang']['lang']);
						$translations = [];
						$translations[$term['polylang']['lang']] = $local_term;
						foreach ($term['polylang']['translations'] as $lang => $translation) {
							$local_term_translation = self::get_local_term_id( $translation, $taxonomy );
							if($local_term_translation !== 0){
								$translations[$lang] = $local_term_translation;
							}
						}
						pll_save_term_translations($translations);
						if('item_author' == $taxonomy){
							$pods_term = pods( $taxonomy, $local_term );
							$pods_term->save( 'anonymize_item_author', $term['anonymize_item_author'] ?? 0 );
						}
					}
				}
			}

		}

		public static function get_local_term_id(int $remote_term_id, string $taxonomy): int {
			global $terms_equivalence_cache;
			if(null == $terms_equivalence_cache) $terms_equivalence_cache = [];

			if(isset($terms_equivalence_cache[$remote_term_id])){
				return $terms_equivalence_cache[$remote_term_id];
			}

			$local_term = get_terms( [
				'fields'     => 'ids',
				'taxonomy'   => $taxonomy,
				'hide_empty' => false, // also retrieve terms which are not used yet
				'meta_query' => [
					[
						'key'       => 'o_id',
						'value'     => $remote_term_id,
						'compare'   => 'LIKE',
					]
				],
			] );
			$items_local_ID = 0;
			if(!empty($local_term)){
				$items_local_ID = $local_term[0];
				$terms_equivalence_cache[$remote_term_id] = $items_local_ID;
			}

			return $items_local_ID;
		}

		public static function get_local_post_id(int $remote_post_id, string $cpt): int {
			global $posts_equivalence_cache;
			if(null == $posts_equivalence_cache) $posts_equivalence_cache = [];

			if(isset($posts_equivalence_cache[$remote_post_id])){
				return $posts_equivalence_cache[$remote_post_id];
			}

			$local_post = new WP_Query( [
				'fields'         => 'ids',
				'post_type'      => $cpt,
				'post_status'    => 'publish',
				'meta_query'     => [
					[
						'key'       => 'o_id',
						'value'     => $remote_post_id,
						'compare'   => 'LIKE',
					]
				],
				'posts_per_page' => 1,
			] );

			$post_local_ID = 0;
			if ( $local_post->have_posts() ) {
				while ( $local_post->have_posts() ) {
					$local_post->the_post();
					$post_local_ID = get_the_ID();
					$posts_equivalence_cache[$remote_post_id] = $post_local_ID;
				}
			}
			wp_reset_postdata();

			return $post_local_ID;
		}

		public static function save_items_relationship() {
			global $posts_relationships_list;
			if(null == $posts_relationships_list) return;

			foreach ($posts_relationships_list as $post_id => $relations) {
				$parent_item = [];
				foreach ($relations['parent_item'] as $rel) {
					$local_post_id = self::get_local_post_id( $rel, 'item' );
					if($local_post_id !== 0){
						$parent_item[] = $local_post_id;
					}
				}
				$related_items = [];
				foreach ($relations['related_items'] as $rel) {
					$local_post_id = self::get_local_post_id( $rel, 'item' );
					if($local_post_id !== 0){
						$related_items[] = $local_post_id;
					}
				}
				$item = pods( 'item', $post_id );
				$item->save( 'parent_item',   $parent_item );
				$item->save( 'related_items', $related_items );
			}
		}

		/**
		 * Retrieve customers data from the database
		 *
		 * @param int $per_page
		 * @param int $page_number
		 *
		 * @return mixed
		 */
		public static function get_items( $per_page = 5, $page_number = 1 ) {

			$output = self::fetch_items();

			$current_tab = $_GET['tab'] ?? '';
			if('' != $current_tab){
				$output = array_filter(
					$output,
					fn($i) => $i['status_value'] == $current_tab
				);
			}

			if ( ! empty( $_REQUEST['orderby'] ) ) {
				$sort_order = SORT_ASC;
				if(! empty( $_REQUEST['order'] ) && 'desc' == strtolower($_REQUEST['order'])) $sort_order = SORT_DESC;
				$sort = array_column($output, $_REQUEST['orderby']);
				array_multisort(
					$output,
					$sort_order,
					SORT_REGULAR,
					$sort
				);
			}

			$output = array_slice($output, ( $page_number - 1 ) * $per_page, $per_page);

			return $output;
		}

		/**
		 * Returns the count of records in the database.
		 *
		 * @return null|string
		 */
		public static function record_count() {

			$output = self::fetch_items();

			$current_tab = $_GET['tab'] ?? '';
			if('' != $current_tab){
				$output = array_filter(
					$output,
					fn($i) => $i['status_value'] == $current_tab
				);
			}
			return count( $output );
		}

		/** Text displayed when no customer data is available */
		public function no_items() {
			_e( 'No item avaliable.', 'dir-wordpress-interface' );
		}

		/**
		 * Render a column when no column specific method exist.
		 *
		 * @param array $item
		 * @param string $column_name
		 *
		 * @return mixed
		 */
		public function column_default( $item, $column_name ) {
			switch ( $column_name ) {
				case 'name':
				case 'status':
					return $item[ $column_name ];
				default:
					return print_r( $item, true ); //Show the whole array for troubleshooting purposes
			}
		}

		/**
		 * Render the bulk edit checkbox
		 *
		 * @param array $item
		 *
		 * @return string
		 */
		function column_cb( $item ) {
			return sprintf(
				'<input type="checkbox" name="bulk-item[]" value="%s" />', $item['ID']
			);
		}

		/**
		 * Method for name column
		 *
		 * @param array $item an array of DB data
		 *
		 * @return string
		 */
		function column_name( $item ) {

			$item_nonce = wp_create_nonce( 'item_sync' );

			$title = '<strong>' . $item['name'] . '</strong>';

			$actions = [];

			if('tosync' == $item['status_value']){
				$actions['sync'] = sprintf( '<a href="?page=%s&action=%s&item=%s&_wpnonce=%s">%s</a>', esc_attr( $_REQUEST['page'] ), 'sync', absint( $item['ID'] ), $item_nonce, 'Sync' );
			}
			if('toimport' != $item['status_value']){
				$actions['delete'] = sprintf( '<a href="?page=%s&action=%s&item=%s&_wpnonce=%s">%s</a>', esc_attr( $_REQUEST['page'] ), 'delete', absint( $item['ID'] ), $item_nonce, 'Delete' );
			}else{
				$actions['import'] = sprintf( '<a href="?page=%s&action=%s&item=%s&_wpnonce=%s">%s</a>', esc_attr( $_REQUEST['page'] ), 'sync', $item['ID'], $item_nonce, 'Import' );
			}

			return $title . $this->row_actions( $actions );
		}

		/**
		 *  Associative array of columns
		 *
		 * @return array
		 */
		function get_columns() {
			$columns = [
				'cb'      => '<input type="checkbox" />',
				'name'    => __( 'Name', 'dir-wordpress-interface' ),
				'status'  => __( 'Status', 'dir-wordpress-interface' ),
			];

			return $columns;
		}

		/**
		 * Columns to make sortable.
		 *
		 * @return array
		 */
		public function get_sortable_columns() {
			$sortable_columns = [
				'name'   => [ 'name', true ],
				'status' => [ 'status', false ],
			];

			return $sortable_columns;
		}

		/**
		 * Handles data query and filter, sorting, and pagination.
		 */
		public function prepare_items() {

			$this->_column_headers = $this->get_column_info();

			/** Process bulk action */
			$this->process_bulk_action();

			$per_page     = $this->get_items_per_page( 'items_per_page', 5 ); // quelli delle option
			$current_page = $this->get_pagenum();
			$total_items  = self::record_count();

			$this->set_pagination_args( [
				'total_items' => $total_items, //WE have to calculate the total number of items
				'per_page'    => $per_page //WE have to determine how many items to show on a page
			] );

			$this->items = self::get_items( $per_page, $current_page );
		}

		/**
		 * Returns an associative array containing the bulk action
		 *
		 * @return array
		 */
		public function get_bulk_actions() {
			$actions = [
				'bulk-sync'   => __('Import / Sync', 'dir-wordpress-interface'),
				'bulk-delete' => __('Delete', 'dir-wordpress-interface'),
			];

			return $actions;
		}

		public function process_bulk_action() {

			//Detect when a bulk action is being triggered...
			if ( in_array($this->current_action(), ['sync', 'delete']) ) {

				// In our file that handles the request, verify the nonce.
				$nonce = esc_attr( $_REQUEST['_wpnonce'] );

				if ( ! wp_verify_nonce( $nonce, 'item_sync' ) ) {
					die( 'Go get a life script kiddies' );
				}
				else {
					if('delete' === $this->current_action()){
						self::delete_item( absint( $_GET['item'] ) );
					}else{
						self::sync_item( $_GET['item'] );
					}

					self::save_items_relationship();

					// esc_url_raw() is used to prevent converting ampersand in url to "#038;"
					// add_query_arg() return the current url
					echo '<script>window.location.replace("'. home_url() . esc_url_raw( add_query_arg(['action' => false, 'item' => false, '_wpnonce' => false]) ) .'");</script>';
					exit;
				}

			}

			// If the delete bulk action is triggered
			if ( ( isset( $_POST['action'] ) && in_array( $_POST['action'], ['bulk-sync', 'bulk-delete'] ) )
				 || ( isset( $_POST['action2'] ) && in_array( $_POST['action2'], ['bulk-sync', 'bulk-delete'] ) )
			) {

				$items_ids = esc_sql( $_POST['bulk-item'] );

				// loop over the array of record IDs and delete them
				foreach ( $items_ids as $id ) {
					if('delete' === $this->current_action()){
						self::delete_item( $id );
					}else{
						self::sync_item( $id );
					}
				}

				self::save_items_relationship();

				// esc_url_raw() is used to prevent converting ampersand in url to "#038;"
				// add_query_arg() return the current url
				echo '<script>window.location.replace("'. home_url() . esc_url_raw( add_query_arg(['action' => false, 'item' => false, '_wpnonce' => false]) ) .'");</script>';
				exit;
			}
		}

		/**
		 * Delete a Item record.
		 *
		 * @param int $id item ID
		 */
		public static function delete_item( $id ) {
			wp_delete_post( $id );
		}

		/**
		 * Sync a Item record.
		 *
		 * @param int|string $id item ID
		 */
		public static function sync_item( $id ) {
			global $posts_relationships_list;
			if(null == $posts_relationships_list) $posts_relationships_list = [];

			$fetch = self::fetch_items();
			$items_ID = array_column($fetch, 'ID');
			$mod = 'sync';
			if('i' == substr($id, -1)){
				$search = array_search($id, $items_ID);
				if(false === $search) return;
				$id = absint( substr($id, 0, -1) );
				$mod = 'import';
			}else{
				$search = array_search($id, $items_ID);
				if(false === $search) return;
				$id = absint( $fetch[$search]['origin'] );
			}

			$url_dir    = get_option('options_url-dir');
			$remote_url = $url_dir.'wp-json/taz/v2/item/' . $id;
			$item_call  = wp_remote_get($remote_url, ['sslverify' => false]);
			$item_body  = wp_remote_retrieve_body($item_call);
			$the_item   = json_decode($item_body, true);

			$term_equivalence = function($taxonomy) use ($the_item) {
				$return = [];
				$tax = $the_item[$taxonomy] ?: [];
				foreach ($tax as $remote_term_id) {
					$local_term_id = self::get_local_term_id( $remote_term_id, $taxonomy );
					if($local_term_id !== 0) $return[] = $local_term_id;
				}
				return $return;
			};

			$taxonomies = [
				'cultural_context'          => $term_equivalence('cultural_context'),
				'historical_time_period'    => $term_equivalence('historical_time_period'),
				'item_author'               => $term_equivalence('item_author'),
				'material'                  => $term_equivalence('material'),
				'place_of_production'       => $term_equivalence('place_of_production'),
				'technique'                 => $term_equivalence('technique'),
				'public_tag'                => $term_equivalence('public_tag'),
			];

			$post = [
				'ID'             => $fetch[$search]['ID'],
				'post_type'      => 'item',
				'post_date'      => $the_item['date'],
				'post_title'     => $the_item['title']['rendered'],
				'post_name'      => $the_item['slug'],
				'tax_input'      => $taxonomies,
				'meta_input'     => ['o_id' => $the_item['id'], 'hash' => $fetch[$search]['hash']],
				'comment_status' => 'closed',
				'post_status'    => 'publish',
			];
			if('import' == $mod) unset($post['ID']);
			//tp_debug([$id, $mod, $fetch[$search], $the_item, $post]);
			$post_id = wp_insert_post( $post );

			pll_set_post_language($post_id, $the_item['polylang']['lang']);
			$translations = [];
			$translations[$the_item['polylang']['lang']] = $post_id;
			foreach ($the_item['polylang']['translations'] as $lang => $translation) {
				$local_post_translation = self::get_local_post_id( $translation, 'item' );
				if($local_post_translation !== 0){
					$translations[$lang] = $local_post_translation;
				}
			}
			pll_save_post_translations($translations);

			# preparo le relazioni da salvare dopo
			$parent_item   = $the_item['parent_item'] ?: [];
			$related_items = $the_item['related_items'] ?: [];
			$posts_relationships_list[$post_id] = [
				'parent_item'   => array_column($parent_item, 'ID'),
				'related_items' => array_column($related_items, 'ID'),
			];

			# gestisco i musei
			$museum_list = [];
			$museum = $the_item['museum'] ?: [];
			foreach ($museum as $m) {
				$museum_list[] = self::sync_museum( $m );
			}

			# gestisco gli allegati
			$main_image = self::import_media( $the_item['main_image'] );

			$media_gallery = [];
			if($the_item['media_gallery']){
				foreach ($the_item['media_gallery'] as $media) {
					$media_gallery[] = self::import_media( $media );
				}
				$media_gallery = array_filter($media_gallery);
			}

			$attachments = [];
			if($the_item['attachments']){
				foreach ($the_item['attachments'] as $attachment) {
					$attachments[] = self::import_media( $attachment );
				}
				$attachments = array_filter($attachments);
			}

			/*$ed_resources = [];
			if($the_item['3d_resources']){
				foreach ($the_item['3d_resources'] as $resource) {
					$ed_resources[] = self::import_media( $resource );
				}
				$ed_resources = array_filter($ed_resources);
			}*/

			$item = pods( 'item', $post_id );
			$item->save( 'iccd_code',                       $the_item['iccd_code'] );
			$item->save( 'inventory_code',                  $the_item['inventory_code'] );
			$item->save( 'alternative_name',                $the_item['alternative_name'] );
			$item->save( 'year_of_origin',                  $the_item['year_of_origin'] );
			$item->save( 'description',                     $the_item['description'] );
			$item->save( 'short_description',               $the_item['short_description'] );
			$item->save( 'detailed_description',            $the_item['detailed_description'] );
			$item->save( 'text_for_screen_reader',          $the_item['text_for_screen_reader'] );
			$item->save( 'config_json',                     $the_item['config_json'] );
			$item->save( 'sensitive_content',               $the_item['sensitive_content'] );
			$item->save( 'dimensions',                      $the_item['dimensions'] );
			$item->save( 'bibliography',                    $the_item['bibliography'] );
			$item->save( 'content_advisory',                $the_item['content_advisory'] );
			$item->save( 'current_location',                $the_item['current_location'] );
			$item->save( 'exhibitions',                     $the_item['exhibitions'] );
			$item->save( 'private_notes',                   $the_item['private_notes'] );
			$item->save( '3d_files_sources_paths',          $the_item['3d_files_sources_paths'] );
			$item->save( 'historical_time_period_details',  $the_item['historical_time_period_details'] );
			$item->save( 'authorship_details',              $the_item['authorship_details'] );
			$item->save( 'place_of_production_details',     $the_item['place_of_production_details'] );
			$item->save( 'manufacturing_details',           $the_item['manufacturing_details'] );
			$item->save( 'museum',                          $museum_list );
			$item->save( 'main_image',                      $main_image );
			$item->save( 'media_gallery',                   $media_gallery );
			$item->save( 'attachments',                     $attachments );
			//$item->save( '3d_resources',                    $ed_resources );
		}

		public static function sync_museum( $museum_data ) {
			global $museum_synced_list;
			if(null == $museum_synced_list) $museum_synced_list = [];

			if(isset($museum_synced_list[$museum_data['ID']])){
				return $museum_synced_list[$museum_data['ID']];
			}

			$post = [
				'post_type'      => 'museum',
				'post_date'      => $museum_data['post_date'],
				'post_title'     => $museum_data['post_title'],
				'post_name'      => $museum_data['post_name'],
				'meta_input'     => ['o_id' => $museum_data['ID']],
				'comment_status' => 'closed',
				'post_status'    => 'publish',
			];
			$local_museum = self::get_local_post_id( $museum_data['ID'], 'museum' );
			if(0 !== $local_museum) $post['ID'] = $local_museum;
			$post_id = wp_insert_post( $post );
			$museum_synced_list[$museum_data['ID']] = $post_id;

			$current_language = $museum_data['language']['slug'];
			$remote_translations = unserialize($museum_data['post_translations']['description']);
			unset($remote_translations[$current_language]);
			pll_set_post_language($post_id, $current_language);
			$translations = [];
			$translations[$current_language] = $post_id;
			foreach ($remote_translations as $lang => $translation) {
				$local_post_translation = self::get_local_post_id( $translation, 'museum' );
				if(0 !== $local_post_translation) $translations[$lang] = $local_post_translation;
			}
			pll_save_post_translations($translations);

			$item = pods( 'museum', $post_id );
			$item->save( 'description', $museum_data['description'] );
			$item->save( 'website',     $museum_data['website'] );

			return $museum_synced_list[$museum_data['ID']];
		}

		public static function import_media( $remote_attachment ) {
			if(!$remote_attachment) return $remote_attachment;
			$media = self::get_local_post_id( $remote_attachment['ID'], 'attachment' );
			if( 0 !== $media ) return $media;

			$filename = explode('uploads/', $remote_attachment['guid'])[1] ?? '';
			if('' == $filename) return '';

			// Get the path to the upload directory.
			$upload_dir = wp_upload_dir();

			if ( wp_mkdir_p( $upload_dir['path'] ) ) {
				$file = $upload_dir['path'] . '/' . $filename;
			} else {
				$file = $upload_dir['basedir'] . '/' . $filename;
			}

			$attachment_data = @file_get_contents( $remote_attachment['guid'] );
			file_put_contents( $file, $attachment_data );

			$attachment = [
				'guid'           => $remote_attachment['guid'],
				'post_mime_type' => $remote_attachment['post_mime_type'],
				'post_title'     => $remote_attachment['post_title'],
				'post_content'   => $remote_attachment['post_content'],
				'post_status'    => $remote_attachment['post_status'],
			];

			// Insert the attachment.
			$attach_id = wp_insert_attachment( $attachment, $file );

			// Make sure that this file is included, as wp_generate_attachment_metadata() depends on it.
			require_once( ABSPATH . 'wp-admin/includes/image.php' );

			// Generate attachment metadata and update the attachment
			$attach_data = wp_generate_attachment_metadata( $attach_id, $file );
			wp_update_attachment_metadata( $attach_id, $attach_data );

			update_post_meta( $attach_id, 'o_id', $media_remote['id'] );
			# deve anche altri meta di pods?

			return $attach_id;
		}

	}
}