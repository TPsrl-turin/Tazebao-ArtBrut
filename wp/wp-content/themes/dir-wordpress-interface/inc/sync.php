<?php
// File Security Check
if ( ! defined( 'ABSPATH' ) ) exit;

if(!class_exists('DIRsync')){
	/**
	 * DIR connection, fetching and sync
	 */
	class DIRsync {
		// class instance
		static $instance;

		static $slug       = 'dir-sync';
		static $capability = 'level_7';
		public $list_table = null;

		public function __construct() {
			add_action( 'admin_menu', [$this, 'admin_create_menu'] );
			add_filter( 'set-screen-option', [$this, 'set_option'], 10, 3 );
		}

		public function admin_create_menu() {
			$hook = add_menu_page (
				__('Sync', 'dir-wordpress-interface'),
				__('Sync', 'dir-wordpress-interface'),
				$this::$capability,
				$this::$slug,
				[$this, 'add_admin_page'],
				'data:image/svg+xml;base64,' . base64_encode('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--!Font Awesome Free 6.7.2 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc.--><path fill="currentColor" d="M142.9 142.9c-17.5 17.5-30.1 38-37.8 59.8c-5.9 16.7-24.2 25.4-40.8 19.5s-25.4-24.2-19.5-40.8C55.6 150.7 73.2 122 97.6 97.6c87.2-87.2 228.3-87.5 315.8-1L455 55c6.9-6.9 17.2-8.9 26.2-5.2s14.8 12.5 14.8 22.2l0 128c0 13.3-10.7 24-24 24l-8.4 0c0 0 0 0 0 0L344 224c-9.7 0-18.5-5.8-22.2-14.8s-1.7-19.3 5.2-26.2l41.1-41.1c-62.6-61.5-163.1-61.2-225.3 1zM16 312c0-13.3 10.7-24 24-24l7.6 0 .7 0L168 288c9.7 0 18.5 5.8 22.2 14.8s1.7 19.3-5.2 26.2l-41.1 41.1c62.6 61.5 163.1 61.2 225.3-1c17.5-17.5 30.1-38 37.8-59.8c5.9-16.7 24.2-25.4 40.8-19.5s25.4 24.2 19.5 40.8c-10.8 30.6-28.4 59.3-52.9 83.8c-87.2 87.2-228.3 87.5-315.8 1L57 457c-6.9 6.9-17.2 8.9-26.2 5.2S16 449.7 16 440l0-119.6 0-.7 0-7.6z"/></svg>'),
				22
			);
			add_action( "load-{$hook}", [$this, 'add_options'] );
		}

		public function add_options() {
			$option = 'per_page';
			$args = [
				'label'   => __('Number of items per page:', 'dir-wordpress-interface'),
				'default' => 20,
				'option'  => 'items_per_page',
			];
			add_screen_option( $option, $args );
			require_once get_template_directory() . '/inc/sync-table.php';
			$this->list_table = new DIRsyncTable();
		}

		public function set_option($status, $option, $value) {
			if ( 'items_per_page' == $option ) return $value;
			return $status;
		}

		public function add_admin_page() {
			if( !current_user_can( $this::$capability )) return ;

			DIRsyncTable::sync_terms();
			$items = DIRsyncTable::fetch_items();
			$tabs = array_column($items, 'status_value');
			$tabs = array_unique($tabs);
			$current_tab = $_GET['tab'] ?? '';
			?>
			<style>
				.sync-tabs-wrapper {
					display: flex;
					align-items: flex-end;
					justify-content: center;
				}
				.sync-tab {
					display: block;
					text-decoration: none;
					color: inherit;
					padding: .5rem 1rem 1rem;
					margin: 0 1rem;
					min-width: 60px;
					text-align: center;
					transition: box-shadow .5s ease-in-out;
				}
				.sync-tab.active {
					box-shadow: inset 0 -3px #3582c4;
					font-weight: 600;
				}
			</style>
			<div class="wrap">
				<h2><?=__('Sync', 'dir-wordpress-interface')?></h2>
				<div id="poststuff">
					<div id="post-body" class="metabox-holder columns-2">
						<nav class="sync-tabs-wrapper tab-count-2" aria-label="Menu secondario">
							<?php
								echo '<a href="?page=dir-sync&tab" class="sync-tab '. ('' == $current_tab ? 'active' : '') .'">'. __('All', 'dir-wordpress-interface') .'</a>';
								foreach ($tabs as $key => $value) {
									echo '<a href="?page=dir-sync&tab='. $items[$key]['status_value'] .'" class="sync-tab '. ($items[$key]['status_value'] == $current_tab ? 'active' : '') .'">'. $items[$key]['status'] .'</a>';
								}
							?>
						</nav>
						<div id="post-body-content">
							<div class="meta-box-sortables ui-sortable">
								<form method="post">
									<?php
									$this->list_table->prepare_items();
									$this->list_table->display(); ?>
								</form>
							</div>
						</div>
					</div>
					<br class="clear">
				</div>
			</div>
			<?php
		}

		/** Singleton instance */
		public static function get_instance() {
			if ( ! isset( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

	}

	$dir_sync = DIRsync::get_instance();
}