<?php
/**
 * Notifications System Main Class.
 *
 * @package notification-system
 */

namespace KAGG\Notification_System;

use WP_Post;
use WP_Query;

/**
 * Class Notifications
 */
class Notifications {

	/**
	 * Slug of virtual page with frontend.
	 * By default, works on site.org/notifications
	 */
	const PAGE_SLUG = 'notifications';

	/**
	 * Hash in link which opens popup with frontend.
	 * By default, works on site.org/any-url#notifications*
	 */
	const POPUP_HASH = 'notifications';

	/**
	 * Such a menu title will be replaced by icon and unread count.
	 */
	const EMPTY_MENU = '-';

	/**
	 * API instance.
	 *
	 * @var Notifications_API
	 */
	public $api;

	/**
	 * Notifications constructor.
	 */
	public function __construct() {
		$this->init();
		$this->init_hooks();
	}

	/**
	 * Init class.
	 */
	protected function init() {
		$this->api = new Notifications_API();
	}

	/**
	 * Init various hooks.
	 */
	protected function init_hooks() {
		add_action( 'init', [ $this, 'register_taxonomies' ] );
		add_action( 'init', [ $this, 'add_rewrite_rules' ] );
		add_action( 'init', [ $this, 'register_cpt_notification' ] );

		// Register activation hook to flush rewrite rules.
		register_activation_hook( KAGG_NOTIFICATIONS_FILE, [ $this, 'activate_plugin' ] );

		// Register deactivation hook to flush rewrite rules.
		register_deactivation_hook( KAGG_NOTIFICATIONS_FILE, [ $this, 'deactivate_plugin' ] );

		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_scripts' ], 20 );
		add_action( 'admin_enqueue_scripts', [ $this, 'admin_enqueue_scripts' ] );

		add_action( 'init', [ $this, 'notifications_page' ], PHP_INT_MAX );
		add_filter( 'pre_get_document_title', [ $this, 'notifications_page_document_title' ], 20 );
		add_filter( 'wpseo_breadcrumb_links', [ $this, 'wpseo_breadcrumb_links' ] );
		add_shortcode( 'notifications', [ $this, 'notifications_shortcode' ] );

		add_action( 'wp_ajax_kagg_notification_get_popup_content', [ $this, 'get_popup_content' ] );
		add_action( 'wp_ajax_nopriv_kagg_notification_get_popup_content', [ $this, 'get_popup_content' ] );

		add_action( 'wp_ajax_kagg_notification_make_all_as_read', [ $this, 'make_all_as_read' ] );
		add_action( 'wp_ajax_nopriv_kagg_notification_make_all_as_read', [ $this, 'make_all_as_read' ] );

		add_action( 'add_meta_boxes', [ $this, 'add_meta_boxes' ] );
		add_action( 'add_meta_boxes', [ $this, 'remove_meta_boxes' ] );
		add_action( 'save_post', [ $this, 'save_meta_boxes' ], 0, 2 );
		add_action( 'update_unread_counts', [ $this, 'update_unread_counts' ] );
		add_filter( 'wp_nav_menu_objects', [ $this, 'update_nav_menu_items' ], 10 );

		add_action( 'plugins_loaded', [ $this, 'load_plugin_textdomain' ] );
	}

	/**
	 * Plugin activation hook.
	 */
	public function activate_plugin() {
		// Register entities as they do not exist when activation hook is fired.
		// Otherwise, flush_rewrite_rules() has nothing to do.
		$this->register_taxonomies();
		$this->add_rewrite_rules();
		$this->register_cpt_notification();

		flush_rewrite_rules();
	}

	/**
	 * Plugin deactivation hook.
	 */
	public function deactivate_plugin() {
		// Unregister entities here as they do already exist when deactivation hook is fired.
		// Otherwise, flush_rewrite_rules() has nothing to do.
		remove_rewrite_tag( '%channel%' );

		// This also unregisters taxonomies.
		unregister_post_type( 'notification' );

		flush_rewrite_rules();
	}

	/**
	 * Enqueue scripts.
	 */
	public function enqueue_scripts() {
		$min = $this->min_suffix();

		// REST Javascript API.
		wp_localize_script(
			'wp-api',
			'WPAPISettings',
			[
				'root'      => rest_url(),
				'base'      => 'kagg/v1/notifications',
				'pluginURL' => KAGG_NOTIFICATIONS_URL,
				'ajaxURL'   => admin_url( 'admin-ajax.php' ),
				'nonce'     => wp_create_nonce( 'kagg-notification-rest' ),
			]
		);
		wp_enqueue_script( 'wp-api' );

		// Plugin REST script.
		wp_enqueue_script(
			'notification-system',
			KAGG_NOTIFICATIONS_URL . '/assets/js/apps/notificationsRESTAPI.js',
			[ 'wp-api' ],
			KAGG_NOTIFICATIONS_VERSION,
			true
		);

		wp_enqueue_style(
			'notification-system',
			KAGG_NOTIFICATIONS_URL . "/assets/css/style$min.css",
			[],
			KAGG_NOTIFICATIONS_VERSION
		);
	}

	/**
	 * Enqueue admin scripts.
	 */
	public function admin_enqueue_scripts() {
		$min = $this->min_suffix();

		wp_enqueue_style(
			'notification-system',
			KAGG_NOTIFICATIONS_URL . "/assets/css/admin-style$min.css",
			[],
			KAGG_NOTIFICATIONS_VERSION
		);
	}

	/**
	 * Register taxonomies for a Notification custom post type.
	 */
	public function register_taxonomies() {
		$args = [
			'labels'            => [
				'name'              => __( 'Channels', 'notification-system' ),
				'singular_name'     => __( 'Channel', 'notification-system' ),
				'search_items'      => __( 'Search Channels', 'notification-system' ),
				'all_items'         => __( 'All Channels', 'notification-system' ),
				'parent_item'       => __( 'Parent Channel', 'notification-system' ),
				'parent_item_colon' => __( 'Parent Channel:', 'notification-system' ),
				'edit_item'         => __( 'Edit Channel', 'notification-system' ),
				'update_item'       => __( 'Update Channel', 'notification-system' ),
				'add_new_item'      => __( 'Add New Channel', 'notification-system' ),
				'new_item_name'     => __( 'New Channel', 'notification-system' ),
				'menu_name'         => __( 'Channels', 'notification-system' ),
			],
			'description'       => __( 'Notification Channels', 'notification-system' ),
			'public'            => false,
			'show_ui'           => true,
			'hierarchical'      => false,
			'meta_box_cb'       => null,
			'show_admin_column' => false,
		];
		register_taxonomy( 'channel', [ 'notification' ], $args );
	}

	/**
	 * Add rewrite rules.
	 */
	public function add_rewrite_rules() {
		// Tags.
		add_rewrite_tag( '%channel%', '([^&]+)', 'channel=' );

		// New query vars.
		add_filter( 'query_vars', [ $this, 'add_query_vars' ] );
	}

	/**
	 * Add query vars.
	 *
	 * @param array|mixed $vars Query vars.
	 *
	 * @return array
	 */
	public function add_query_vars( $vars ): array {
		$vars   = (array) $vars;
		$vars[] = 'channel';

		return $vars;
	}

	/**
	 * Register Notification custom post type.
	 *
	 * @noinspection HtmlDeprecatedAttribute HtmlDeprecatedAttribute.
	 */
	public function register_cpt_notification() {
		$labels = [
			'name'               => __( 'Notifications', 'notification-system' ),
			'singular_name'      => __( 'Notification', 'notification-system' ),
			'add_new'            => __( 'Add New', 'notification-system' ),
			'add_new_item'       => __( 'Add New Notification', 'notification-system' ),
			'edit_item'          => __( 'Edit Notification', 'notification-system' ),
			'new_item'           => __( 'New Notification', 'notification-system' ),
			'view_item'          => __( 'View Notification', 'notification-system' ),
			'search_items'       => __( 'Search Notifications', 'notification-system' ),
			'not_found'          => __( 'Not Found', 'notification-system' ),
			'not_found_in_trash' => __( 'Not Found In Trash', 'notification-system' ),
			'parent_item'        => __( 'Parent', 'notification-system' ),
			'parent_item_colon'  => __( 'Parent:', 'notification-system' ),
			'menu_name'          => __( 'Notifications', 'notification-system' ),
		];

		$args = [
			'labels'                => $labels,
			'hierarchical'          => false,
			'description'           => __( 'Notifications', 'notification-system' ),
			'supports'              => [
				'title',
				'editor',
			],
			'taxonomies'            => [ 'channel' ],
			'public'                => true,
			'show_ui'               => true,
			'show_in_menu'          => true,
			// phpcs:disable WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
			'menu_icon'             => 'data:image/svg+xml;base64,' . base64_encode( '<svg height="20" viewBox="0 85.5 1024 855" version="1.1" xmlns="http://www.w3.org/2000/svg"><path d="M490.666667 938.666667c46.933333 0 85.333333-38.4 85.333333-85.333334h-170.666667c0 46.933333 38.4 85.333333 85.333334 85.333334z m277.333333-256V448c0-130.986667-90.88-240.64-213.333333-269.653333V149.333333c0-35.413333-28.586667-64-64-64s-64 28.586667-64 64v29.013334C304.213333 207.36 213.333333 317.013333 213.333333 448v234.666667l-85.333333 85.333333v42.666667h725.333333v-42.666667l-85.333333-85.333333z" fill="black" /> </svg>' ),
			// phpcs:enable WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
			'show_in_nav_menus'     => true,
			'publicly_queryable'    => true,
			'exclude_from_search'   => false,
			'has_archive'           => false,
			'query_var'             => true,
			'can_export'            => true,
			'rewrite'               => [
				'slug'       => 'notification',
				'with_front' => false,
			],
			'capability_type'       => 'post',
			'show_in_rest'          => false,
			'rest_base'             => 'kagg/v1/notification',
			'rest_controller_class' => Notifications_API_Controller::class,
		];

		register_post_type( 'notification', $args );
	}

	/**
	 * Template for the plugin frontend page.
	 */
	public function notifications_page() {
		if ( ! $this->is_notification_page() ) {
			return;
		}

		if ( wp_is_block_theme() ) {
			add_filter( 'deprecated_file_trigger_error', '__return_false' );
		}

		get_header();
		echo do_shortcode( '[notifications]' );
		get_footer();

		exit;
	}

	/**
	 * Filters the document title before it is generated.
	 *
	 * @param string|mixed $title Page title.
	 *
	 * @return string
	 */
	public function notifications_page_document_title( $title ): string {
		$title = (string) $title;

		if ( $this->is_notification_page() ) {
			return esc_html__( 'Notifications', 'kagg-notification' );
		}

		return $title;
	}

	/**
	 * Check if it is a notification page.
	 *
	 * @return bool
	 */
	private function is_notification_page(): bool {
		if ( isset( $_SERVER['REQUEST_URI'] ) ) {
			$uri = filter_var( wp_unslash( $_SERVER['REQUEST_URI'] ), FILTER_SANITIZE_STRING );
		} else {
			return false;
		}

		$path = wp_parse_url( $uri, PHP_URL_PATH );

		return '/' . trailingslashit( self::PAGE_SLUG ) === trailingslashit( $path );
	}

	/**
	 * Filter Yoast SEO breadcrumbs and set proper page name.
	 *
	 * @param array|mixed $crumbs The crumbs array.
	 *
	 * @return array
	 */
	public function wpseo_breadcrumb_links( $crumbs ): array {
		$crumbs = (array) $crumbs;

		if ( $this->is_notification_page() ) {
			$crumbs[1]['text'] = esc_html__( 'Notifications', 'kagg-notification' );

			return $crumbs;
		}

		return $crumbs;
	}

	/**
	 * Shortcode to show notifications.
	 *
	 * @return string
	 */
	public function notifications_shortcode(): string {
		ob_start();

		if ( current_user_can( 'edit_posts' ) ) {
			$edit_class = 'edit';
		} else {
			$edit_class = '';
		}

		?>
		<div class="wrap">
			<div id="primary" class="content-area notifications-content <?php echo esc_attr( $edit_class ); ?>">
				<main id="main" class="site-main" role="main">
					<article id="notifications-page" <?php post_class(); ?>>
						<header class="entry-header">
							<h1>
								<?php esc_html_e( 'Notifications', 'notification-system' ); ?>
							</h1>
						</header><!-- .entry-header -->
						<div id="notifications-header">
								<span>
									<?php esc_html_e( 'Message', 'notification-system' ); ?>
								</span>
							<span>
									<?php
									$this->select_terms(
										'channel',
										__( 'Select channel', 'notification-system' )
									);
									?>
								</span>
						</div>
						<table id="notifications-list">
							<tbody>
							<?php // Here will be the javascript output. ?>
							</tbody>
						</table>
						<div class="buttons-block">
							<?php
							if ( current_user_can( 'read' ) ) {
								?>
								<input
									type='button' id='more-button'
									value='<?php esc_html_e( 'More...', 'notification-system' ); ?>'>
								<?php
							}
							?>
							<input
								type='button' id='read-button'
								value='<?php esc_html_e( 'Mark all as read', 'notification-system' ); ?>'>
							<?php
							if ( current_user_can( 'edit_posts' ) ) {
								?>
								<input
									type='button' id='create-notification-button'
									value='<?php esc_html_e( 'Create', 'notification-system' ); ?>'>
								<?php
							}
							?>
							<input
								type="hidden" id="current-user" name="current-user"
								value="<?php echo esc_attr( get_current_user_id() ); ?>">
						</div>
					</article><!-- #notifications-page -->
				</main><!-- #main -->

				<?php // Create a modal window. ?>
				<?php
				if ( current_user_can( 'edit_posts' ) ) {
					?>
					<div id="create-modal" class="notifications-modal">
						<div class="notifications-modal-content">
							<span class="close">&times;</span>
							<h3><?php esc_html_e( 'Create new notification', 'notification-system' ); ?></h3>
							<label for="title-text">
								<?php echo esc_html__( 'Title', 'notification-system' ) . ' *'; ?>
							</label>
							<input type="text" id="title-text" required="required">
							<label for="content-text">
								<?php echo esc_html__( 'Content', 'notification-system' ) . ' *'; ?>
							</label>
							<div contenteditable="true" id="content-text"></div>
							<label for="channel-text">
								<?php esc_html_e( 'Channel(s), separated by comma', 'notification-system' ); ?>
							</label>
							<input type="text" id="channel-text">
							<label for="users-text">
								<?php esc_html_e( 'User(s), separated by comma', 'notification-system' ); ?>
							</label>
							<input type="text" id="users-text">
							<input
								type='button' id='create-button'
								value='<?php esc_html_e( 'Create', 'notification-system' ); ?>'>
						</div>
					</div>
					<?php
				}
				?>

				<?php // Update modal window. ?>
				<?php
				if ( current_user_can( 'edit_posts' ) ) {
					?>
					<div id="update-modal" class="notifications-modal">
						<div class="notifications-modal-content">
							<span class="close">&times;</span>
							<h3><?php esc_html_e( 'Update notification', 'notification-system' ); ?></h3>
							<label for="update-title-text">
								<?php echo esc_html__( 'Title', 'notification-system' ) . ' *'; ?>
							</label>
							<input type="text" id="update-title-text" required="required">
							<label for="update-content-text">
								<?php echo esc_html__( 'Content', 'notification-system' ) . ' *'; ?>
							</label>
							<div contenteditable="true" id="update-content-text"></div>
							<label for="update-channel-text">
								<?php esc_html_e( 'Channel(s), separated by comma', 'notification-system' ); ?>
							</label>
							<input type="text" id="update-channel-text">
							<label for="update-users-text">
								<?php esc_html_e( 'User(s), separated by comma', 'notification-system' ); ?>
							</label>
							<input type="text" id="update-users-text">
							<input
								type='button' id='update-button'
								value='<?php esc_html_e( 'Update', 'notification-system' ); ?>'>
						</div>
					</div>
					<?php
				}
				?>
			</div><!-- #primary -->
		</div><!-- .wrap -->
		<?php

		return ob_get_clean();
	}

	/**
	 * AJAX callback function to get popup content.
	 */
	public function get_popup_content() {
		if ( ! wp_verify_nonce(
			filter_input( INPUT_POST, 'nonce', FILTER_SANITIZE_STRING ),
			'kagg-notification-rest'
		)
		) {
			wp_send_json_error( __( 'Bad nonce!', 'notification-system' ) );
		}

		wp_send_json_success( $this->notifications_shortcode() );
	}

	/**
	 * Make all as read notification.
	 */
	public function make_all_as_read() {
		if ( ! wp_verify_nonce(
			filter_input( INPUT_POST, 'nonce', FILTER_SANITIZE_STRING ),
			'kagg-notification-rest'
		)
		) {
			wp_send_json_error( __( 'Bad nonce!', 'notification-system' ) );
		}

		$user_id = filter_input( INPUT_POST, 'current_user', FILTER_SANITIZE_STRING );

		if ( empty( $user_id ) ) {
			wp_send_json_error( __( 'Current user ID is empty!', 'notification-system' ) );
		}
		$read_value = List_In_Meta::get_prepared_item( wp_get_current_user()->ID );

		$user_meta_query = [
			'relation' => 'AND',
			[
				'key'     => Notification::USERS_META_KEY,
				'value'   => List_In_Meta::get_prepared_item( $user_id ),
				'compare' => 'LIKE',
			],
			[
				'key'     => Notification::READ_STATUS_META_KEY,
				'compare' => 'NOT EXISTS',
			],
		];

		$admin_meta_query = [
			'relation' => 'OR',
			[
				'relation' => 'AND',
				[
					'key'     => Notification::READ_STATUS_META_KEY,
					'value'   => $read_value,
					'compare' => 'NOT LIKE',
				],
				[
					'key'     => Notification::READ_STATUS_META_KEY,
					'compare' => 'EXISTS',
				],
			],
			[
				'key'     => Notification::READ_STATUS_META_KEY,
				'compare' => 'NOT EXISTS',
			],
		];

		$args = [
			'post_type'      => 'notification',
			'posts_per_page' => - 1,
			'status'         => 'publish',
		];

		// phpcs:disable WordPress.DB.SlowDBQuery.slow_db_query_meta_query
		if ( user_can( $user_id, 'manage_options' ) ) {
			$args['meta_query'] = $admin_meta_query;
		} else {
			$args['meta_query'] = $user_meta_query;
		}
		// phpcs:enable WordPress.DB.SlowDBQuery.slow_db_query_meta_query

		$query = new WP_Query( $args );

		if ( 0 === $query->post_count ) {
			wp_send_json_success( __( 'No notification found.', 'notification-system' ) );
		}

		$notification = new Notification( 0 );

		foreach ( $query->posts as $post ) {
			$notification->id = $post->ID;
			$notification->set_read_status( true );
		}

		wp_send_json_success( 'done' );
	}

	/**
	 * Get count of notifications.
	 *
	 * @return int
	 */
	public function get_unread_count(): int {
		$read_value = List_In_Meta::get_prepared_item( wp_get_current_user()->ID );

		$read_meta_query = [
			'relation' => 'OR',
			[
				'relation' => 'AND',
				[
					'key'     => Notification::READ_STATUS_META_KEY,
					'value'   => $read_value,
					'compare' => 'NOT LIKE',
				],
				[
					'key'     => Notification::READ_STATUS_META_KEY,
					'compare' => 'EXISTS',
				],
			],
			[
				'key'     => Notification::READ_STATUS_META_KEY,
				'compare' => 'NOT EXISTS',
			],
		];

		$users_meta_query = [
			'relation' => 'OR',
			[
				'relation' => 'AND',
				[
					'key'     => Notification::USERS_META_KEY,
					'value'   => List_In_Meta::get_prepared_item( wp_get_current_user()->ID ),
					'compare' => 'LIKE',
				],
				[
					'key'     => Notification::USERS_META_KEY,
					'compare' => 'EXISTS',
				],
			],
			[
				'key'     => Notification::USERS_META_KEY,
				'compare' => 'NOT EXISTS',
			],
		];

		if ( current_user_can( 'edit_posts' ) ) {
			// Allow privileged user to see notifications for all users.
			$users_meta_query = [];
		}

		$args = [
			'post_type'  => 'notification',
			'status'     => 'publish',
			// phpcs:disable WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			'meta_query' => [
				'relation' => 'AND',
				$read_meta_query,
				$users_meta_query,
			],
			// phpcs:enable WordPress.DB.SlowDBQuery.slow_db_query_meta_query
		];

		return ( new WP_Query( $args ) )->found_posts;
	}

	/**
	 * Update unread counts.
	 */
	public function update_unread_counts() {
		$count = $this->get_unread_count();
		?>
		<script type='text/javascript'>
			document.dispatchEvent(
				new CustomEvent(
					'update_unread_counts',
					{ 'detail': <?php echo $count; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>},
				),
			);
		</script>
		<?php
	}

	/**
	 * Load plugin text domain.
	 */
	public function load_plugin_textdomain() {
		load_plugin_textdomain(
			'notification-system',
			false,
			dirname( plugin_basename( KAGG_NOTIFICATIONS_FILE ) ) . '/languages/'
		);
	}

	/**
	 * Add meta-boxes for notifications.
	 */
	public function add_meta_boxes() {
		$metabox = new Notification_Meta_Box();
		add_meta_box(
			'notification-data',
			__( 'Notification data', 'notification-system' ),
			[ $metabox, 'output' ],
			'notification',
			'normal',
			'high'
		);
	}

	/**
	 * Remove unnecessary meta-boxes for notifications.
	 */
	public function remove_meta_boxes() {
		remove_meta_box( 'slugdiv', 'notification', 'normal' );
		remove_meta_box( 'postcustom', 'notification', 'normal' );
		remove_meta_box( 'postexcerpt', 'notification', 'normal' );
	}

	/**
	 * Check if we're saving, the trigger an action based on the post type.
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post instance.
	 */
	public function save_meta_boxes( $post_id, $post ) {
		// $post_id and $post are required
		if ( empty( $post_id ) || null === $post ) {
			return;
		}

		// Don't save meta-boxes for revision or autosave.
		if (
			( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || is_int( wp_is_post_revision( $post ) ) ||
			is_int( wp_is_post_autosave( $post ) )
		) {
			return;
		}

		// Check the nonce.
		if (
			empty( $_POST[ Notification_Meta_Box::SAVE_NONCE ] ) ||
			! wp_verify_nonce(
				filter_input( INPUT_POST, Notification_Meta_Box::SAVE_NONCE, FILTER_SANITIZE_STRING ),
				Notification_Meta_Box::SAVE_ACTION
			)
		) {
			return;
		}

		// Check the post being saved == the $post_id to prevent triggering this call for other save_post events.
		if ( empty( $_POST['post_ID'] ) || ( (int) $_POST['post_ID'] !== $post_id ) ) {
			return;
		}

		// Check user has permission to edit.
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		remove_action( 'save_post', [ $this, 'save_meta_boxes' ], 1 );
		$metabox = new Notification_Meta_Box();
		$metabox::save( $post_id );
		add_action( 'save_post', [ $this, 'save_meta_boxes' ], 0, 3 );
	}

	/**
	 * Update Notifications item(s) in the nav menu.
	 * Add svg icon and unread count to them.
	 *
	 * @param array|mixed $sorted_menu_items The menu items, sorted by each menu item's menu order.
	 *
	 * @return array
	 */
	public function update_nav_menu_items( $sorted_menu_items ): array {
		$sorted_menu_items = (array) $sorted_menu_items;

		if ( ! is_user_logged_in() ) {
			return $sorted_menu_items;
		}

		$hash         = '#' . self::POPUP_HASH;
		$count        = $this->get_unread_count();
		$count_str    = $count > 9 ? '9+' : (string) $count;
		$display_span = 0 === $count ? 'none' : 'inline-block';

		foreach ( $sorted_menu_items as $item ) {
			if ( ! isset( $item->url ) || false === mb_strpos( $item->url, $hash ) ) {
				continue;
			}

			$this->update_nav_menu_item( $item, $display_span, $count_str );
		}

		return $sorted_menu_items;
	}

	/**
	 * Output select element for selection of a taxonomy.
	 *
	 * @param string $taxonomy      Taxonomy slug.
	 * @param string $select_header Taxonomy name.
	 */
	protected function select_terms( $taxonomy, $select_header ) {
		if ( ! $taxonomy ) {
			return;
		}

		$args = [
			'taxonomy'   => $taxonomy,
			'hide_empty' => true,
		];

		$terms = get_terms( $args );
		?>
		<select name="<?php echo esc_attr( $taxonomy ); ?>" title="">
			<option value="">
				<?php
				echo esc_html( ' -- ' . $select_header . ' -- ' );
				?>
			</option>
			<?php
			foreach ( $terms as $term ) {
				?>
				<option value="<?php echo esc_attr( $term->slug ); ?>">
					<?php echo esc_html( $term->name ); ?>
				</option>
				<?php
			}
			?>
		</select>
		<?php
	}

	/**
	 * Update nav menu item.
	 *
	 * @param object $item Menu item.
	 * @param string $display_span Whether to display span.
	 * @param string $count_str Counter.
	 *
	 * @return void
	 */
	private function update_nav_menu_item( $item, $display_span, $count_str ) {
		$item->title = self::EMPTY_MENU === trim( $item->title ) ? '' : $item->title;
		$count_span  = '<span class="unread-notifications-count" style="display: ' . $display_span . '">' . $count_str . '</span>';
		$svg         = '<svg class="icon" height="20" viewBox="0 85.5 1024 855" xmlns="http://www.w3.org/2000/svg"><path d="M490.666667 938.666667c46.933333 0 85.333333-38.4 85.333333-85.333334h-170.666667c0 46.933333 38.4 85.333333 85.333334 85.333334z m277.333333-256V448c0-130.986667-90.88-240.64-213.333333-269.653333V149.333333c0-35.413333-28.586667-64-64-64s-64 28.586667-64 64v29.013334C304.213333 207.36 213.333333 317.013333 213.333333 448v234.666667l-85.333333 85.333333v42.666667h725.333333v-42.666667l-85.333333-85.333333z" fill="black" /> </svg>';

		$item->title .= '<span class="menu-item-notifications">' . $svg . $count_span . '</span>';
	}

	/**
	 * Get min suffix.
	 *
	 * @return string
	 */
	private function min_suffix(): string {
		return defined( 'SCRIPT_DEBUG' ) && constant( 'SCRIPT_DEBUG' ) ? '' : '.min';
	}
}
