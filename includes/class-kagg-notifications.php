<?php
/**
 * KAGG Notifications Main Class.
 *
 * @package kagg-notifications
 */

/**
 * Class KAGG_Notifications
 */
class KAGG_Notifications {
	/**
	 * The single instance of the class.
	 *
	 * @var KAGG_Notifications
	 */
	protected static $instance = null;

	/**
	 * API instance.
	 *
	 * @var KAGG_Notifications_API
	 */
	public $api = null;

	/**
	 * Slug of virtual page with frontend.
	 * By default, works on site.org/notifications
	 *
	 * @var string
	 */
	protected $page_slug = 'notifications';

	/**
	 * KAGG_Notifications constructor.
	 */
	public function __construct() {
		$this->init();
		$this->init_hooks();
	}

	/**
	 * Main KAGG_Notifications Instance.
	 *
	 * Ensures only one instance of KAGG_Notifications is loaded or can be loaded.
	 *
	 * @return KAGG_Notifications - Main instance.
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Init class.
	 */
	protected function init() {
		$this->api = new KAGG_Notifications_API();
	}

	/**
	 * Init various hooks.
	 */
	protected function init_hooks() {
		add_action( 'init', array( $this, 'register_taxonomies' ) );
		add_action( 'init', array( $this, 'add_rewrite_rules' ) );
		add_action( 'init', array( $this, 'register_cpt_notification' ) );

		// Register activation hook to flush rewrite rules.
		register_activation_hook( KAGG_NOTIFICATIONS_FILE, array( $this, 'activate_plugin' ) );

		// Register deactivation hook to flush rewrite rules.
		register_deactivation_hook( KAGG_NOTIFICATIONS_FILE, array( $this, 'deactivate_plugin' ) );

		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ), 20 );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );

		add_action( 'template_redirect', array( $this, 'notifications_page' ) );
		add_filter( 'pre_get_document_title', array( $this, 'notifications_page_document_title' ), 20 );
		add_filter( 'wpseo_breadcrumb_links', array( $this, 'wpseo_breadcrumb_links' ) );
		add_shortcode( 'notifications', array( $this, 'notifications_shortcode' ) );

		add_action( 'wp_ajax_kagg_notification_get_popup_content', array( $this, 'get_popup_content' ) );
		add_action( 'wp_ajax_nopriv_kagg_notification_get_popup_content', array( $this, 'get_popup_content' ) );

		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
		add_action( 'add_meta_boxes', array( $this, 'remove_meta_boxes' ) );
		add_action( 'save_post', array( $this, 'save_meta_boxes' ), 0, 2 );
		add_action( 'update_unread_counts', array( $this, 'update_unread_counts' ) );

		add_action( 'plugins_loaded', array( $this, 'load_plugin_textdomain' ) );
	}

	/**
	 * Plugin activation hook.
	 */
	public function activate_plugin() {
		// Register entities as they do not exist when activation hook is fired.
		// Otherwise flush_rewrite_rules() has nothing to do.
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
		// Otherwise flush_rewrite_rules() has nothing to do.
		remove_rewrite_tag( '%channel%' );

		// This also unregisters taxonomies.
		unregister_post_type( 'notification' );

		flush_rewrite_rules();
	}

	/**
	 * Enqueue scripts.
	 */
	public function enqueue_scripts() {
		// REST Javascript API.
		wp_localize_script(
			'wp-api',
			'WPAPISettings',
			array(
				'root'      => rest_url(),
				'base'      => 'kagg/v1/notifications',
				'pluginURL' => KAGG_NOTIFICATIONS_URL,
				'ajaxURL'   => admin_url( 'admin-ajax.php' ),
				'nonce'     => wp_create_nonce( 'kagg-notification-rest' ),
			)
		);
		wp_enqueue_script( 'wp-api' );

		// Plugin RESTful script.
		wp_enqueue_script(
			'kagg-notifications',
			KAGG_NOTIFICATIONS_URL . '/dist/js/notificationsRESTAPI/app.js',
			array( 'wp-api' ),
			KAGG_NOTIFICATIONS_VERSION,
			true
		);

		wp_enqueue_style(
			'kagg-notifications',
			KAGG_NOTIFICATIONS_URL . '/css/style.css',
			array(),
			KAGG_NOTIFICATIONS_VERSION
		);
	}

	/**
	 * Enqueue admin scripts.
	 */
	public function admin_enqueue_scripts() {
		wp_enqueue_style(
			'kagg-notifications',
			KAGG_NOTIFICATIONS_URL . '/css/admin-style.css',
			array(),
			KAGG_NOTIFICATIONS_VERSION
		);
	}

	/**
	 * Register taxonomies for Notification custom post type.
	 */
	public function register_taxonomies() {
		$args = array(
			'labels'            => array(
				'name'              => __( 'Channels', 'kagg-notifications' ),
				'singular_name'     => __( 'Channel', 'kagg-notifications' ),
				'search_items'      => __( 'Search Channels', 'kagg-notifications' ),
				'all_items'         => __( 'All Channels', 'kagg-notifications' ),
				'parent_item'       => __( 'Parent Channel', 'kagg-notifications' ),
				'parent_item_colon' => __( 'Parent Channel:', 'kagg-notifications' ),
				'edit_item'         => __( 'Edit Channel', 'kagg-notifications' ),
				'update_item'       => __( 'Update Channel', 'kagg-notifications' ),
				'add_new_item'      => __( 'Add New Channel', 'kagg-notifications' ),
				'new_item_name'     => __( 'New Channel', 'kagg-notifications' ),
				'menu_name'         => __( 'Channels', 'kagg-notifications' ),
			),
			'description'       => __( 'Notification Channels', 'kagg-notifications' ),
			'public'            => true,
			'show_ui'           => true,
			'hierarchical'      => false,
			'meta_box_cb'       => null,
			'show_admin_column' => false,
		);
		register_taxonomy( 'channel', array( 'notification' ), $args );
	}

	/**
	 * Add rewrite rules.
	 */
	public function add_rewrite_rules() {
		// Tags.
		add_rewrite_tag( '%channel%', '([^&]+)', 'channel=' );

		// New query vars.
		add_filter( 'query_vars', array( $this, 'add_query_vars' ) );
	}

	/**
	 * Add query vars.
	 *
	 * @param array $vars Query vars.
	 *
	 * @return array
	 */
	public function add_query_vars( $vars ) {
		$vars[] = 'channel';

		return $vars;
	}

	/**
	 * Register Notification custom post type.
	 */
	public function register_cpt_notification() {
		$labels = array(
			'name'               => __( 'Notifications', 'kagg-notifications' ),
			'singular_name'      => __( 'Notification', 'kagg-notifications' ),
			'add_new'            => __( 'Add New', 'kagg-notifications' ),
			'add_new_item'       => __( 'Add New Notification', 'kagg-notifications' ),
			'edit_item'          => __( 'Edit Notification', 'kagg-notifications' ),
			'new_item'           => __( 'New Notification', 'kagg-notifications' ),
			'view_item'          => __( 'View Notification', 'kagg-notifications' ),
			'search_items'       => __( 'Search Notifications', 'kagg-notifications' ),
			'not_found'          => __( 'Not Found', 'kagg-notifications' ),
			'not_found_in_trash' => __( 'Not Found In Trash', 'kagg-notifications' ),
			'parent_item'        => __( 'Parent', 'kagg-notifications' ),
			'parent_item_colon'  => __( 'Parent:', 'kagg-notifications' ),
			'menu_name'          => __( 'Notifications', 'kagg-notifications' ),
		);

		$args = array(
			'labels'                => $labels,
			'hierarchical'          => false,
			'description'           => __( 'Notifications', 'kagg-notifications' ),
			'supports'              => array(
				'title',
				'editor',
				'excerpt',
				'thumbnail',
				'custom-fields',
				'page-attributes',
			),
			'taxonomies'            => array( 'channel' ),
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
			'rewrite'               => array(
				'slug'       => 'notification',
				'with_front' => false,
			),
			'capability_type'       => 'page',
			'show_in_rest'          => false,
			'rest_base'             => 'kagg/v1/notification',
			'rest_controller_class' => 'KAGG_Notifications_API_Controller',
		);

		register_post_type( 'notification', $args );
	}

	/**
	 * Template for the plugin frontend page.
	 */
	public function notifications_page() {
		if ( $this->is_notification_page() ) {
			get_header();
			echo do_shortcode( '[notifications]' );
			get_footer();
			exit;
		}
	}

	/**
	 * Filters the document title before it is generated.
	 *
	 * @param string $title Page title.
	 *
	 * @return string
	 */
	public function notifications_page_document_title( $title ) {
		if ( $this->is_notification_page() ) {
			return esc_html__( 'Notifications', 'kagg-notification' );
		}

		return $title;
	}

	/**
	 * @return bool
	 */
	private function is_notification_page() {
		$uri  = $_SERVER['REQUEST_URI'];
		$path = wp_parse_url( $uri, PHP_URL_PATH );

		if ( '/' . trailingslashit( $this->page_slug ) === trailingslashit( $path ) ) {
			return true;
		}

		return false;
	}

	/**
	 * @param array $crumbs The crumbs array.
	 *
	 * @return array
	 */
	public function wpseo_breadcrumb_links( $crumbs ) {
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
	public function notifications_shortcode() {
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
								<?php esc_html_e( 'Notifications', 'kagg-notifications' ); ?>
							</h1>
						</header><!-- .entry-header -->
						<div id="notifications-header">
								<span>
									<?php esc_html_e( 'Message', 'kagg-notifications' ); ?>
								</span>
							<span>
									<?php
									$this->select_terms(
										'channel',
										__( 'Select channel', 'kagg-notifications' )
									);
									?>
								</span>
						</div>
						<table id="notifications-list">
							<tbody>
							<?php // Here will be the javascript output. ?>
							</tbody>
						</table>
						<?php
						if ( current_user_can( 'read' ) ) {
							?>
							<input
									type='button' id='more-button'
									value='<?php esc_html_e( 'Show more...', 'kagg-notifications' ); ?>'>
							<?php
						}

						if ( current_user_can( 'edit_posts' ) ) {
							?>
							<input
									type='button' id='create-notification-button'
									value='<?php esc_html_e( 'Create Notification', 'kagg-notifications' ); ?>'>
							<?php
						}
						?>
					</article><!-- #notifications-page -->
				</main><!-- #main -->

				<?php // Create modal window. ?>
				<?php
				if ( current_user_can( 'edit_posts' ) ) {
					?>
					<div id="create-modal" class="notifications-modal">
						<div class="notifications-modal-content">
							<span class="close">&times;</span>
							<h3><?php esc_html_e( 'Create new notification', 'kagg-notifications' ); ?></h3>
							<label for="title-text">
								<?php echo esc_html__( 'Title', 'kagg-notifications' ) . ' *'; ?>
							</label>
							<input type="text" id="title-text" required="required">
							<label for="content-text">
								<?php echo esc_html__( 'Content', 'kagg-notifications' ) . ' *'; ?>
							</label>
							<div contenteditable="true" id="content-text"></div>
							<label for="channel-text">
								<?php esc_html_e( 'Channel(s), separated by comma', 'kagg-notifications' ); ?>
							</label>
							<input type="text" id="channel-text">
							<label for="users-text">
								<?php esc_html_e( 'User(s), separated by comma', 'kagg-notifications' ); ?>
							</label>
							<input type="text" id="users-text">
							<input
									type='button' id='create-button'
									value='<?php esc_html_e( 'Create', 'kagg-notifications' ); ?>'>
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
							<h3><?php esc_html_e( 'Update notification', 'kagg-notifications' ); ?></h3>
							<label for="update-title-text">
								<?php echo esc_html__( 'Title', 'kagg-notifications' ) . ' *'; ?>
							</label>
							<input type="text" id="update-title-text" required="required">
							<label for="update-content-text">
								<?php echo esc_html__( 'Content', 'kagg-notifications' ) . ' *'; ?>
							</label>
							<div contenteditable="true" id="update-content-text"></div>
							<label for="update-channel-text">
								<?php esc_html_e( 'Channel(s), separated by comma', 'kagg-notifications' ); ?>
							</label>
							<input type="text" id="update-channel-text">
							<label for="update-users-text">
								<?php esc_html_e( 'User(s), separated by comma', 'kagg-notifications' ); ?>
							</label>
							<input type="text" id="update-users-text">
							<input
									type='button' id='update-button'
									value='<?php esc_html_e( 'Update', 'kagg-notifications' ); ?>'>
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
			wp_send_json_error( __( 'Bad nonce!', 'kagg-notifications' ) );
		}

		wp_send_json_success( $this->notifications_shortcode() );
	}

	/**
	 * Get count of notifications.
	 *
	 * @return int
	 */
	public function get_unread_count() {
		$read_value = KAGG_List_In_Meta::get_prepared_item( wp_get_current_user()->ID );

		$read_meta_query = array(
			'relation' => 'OR',
			array(
				'relation' => 'AND',
				array(
					'key'     => KAGG_Notification::READ_STATUS_META_KEY,
					'value'   => $read_value,
					'compare' => 'NOT LIKE',
				),
				array(
					'key'     => KAGG_Notification::READ_STATUS_META_KEY,
					'compare' => 'EXISTS',
				),
			),
			array(
				'key'     => KAGG_Notification::READ_STATUS_META_KEY,
				'compare' => 'NOT EXISTS',
			),
		);

		$users_meta_query = array(
			'relation' => 'OR',
			array(
				'relation' => 'AND',
				array(
					'key'     => KAGG_Notification::USERS_META_KEY,
					'value'   => KAGG_List_In_Meta::get_prepared_item( wp_get_current_user()->ID ),
					'compare' => 'LIKE',
				),
				array(
					'key'     => KAGG_Notification::USERS_META_KEY,
					'compare' => 'EXISTS',
				),
			),
			array(
				'key'     => KAGG_Notification::USERS_META_KEY,
				'compare' => 'NOT EXISTS',
			),
		);

		if ( current_user_can( 'edit_posts' ) ) {
			// Allow privileged user to see notifications for all users.
			$users_meta_query = array();
		}

		$args = array(
			'post_type'  => 'notification',
			'status'     => 'publish',
			// phpcs:disable WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			'meta_query' => array(
				'relation' => 'AND',
				$read_meta_query,
				$users_meta_query,
			),
			// phpcs:enable WordPress.DB.SlowDBQuery.slow_db_query_meta_query
		);

		$query = new WP_Query( $args );

		return $query->found_posts;
	}

	public function update_unread_counts() {
		$count = $this->get_unread_count();
		?>
		<script type='text/javascript'>
			document.dispatchEvent( new CustomEvent( 'update_unread_counts', { 'detail': <?php echo intval( $count ); ?>} ) );
			console.log( 'done' );
		</script>
		<?php
	}

	/**
	 * Load plugin text domain.
	 */
	public function load_plugin_textdomain() {
		load_plugin_textdomain(
			'kagg-notifications',
			false,
			dirname( plugin_basename( KAGG_NOTIFICATIONS_FILE ) ) . '/languages/'
		);
	}

	/**
	 * Add meta boxes for notifications.
	 */
	public function add_meta_boxes() {
		$metabox = new KAGG_Notification_Meta_Box();
		add_meta_box(
			'notification-data',
			__( 'Notification data', 'kagg-notifications' ),
			array( $metabox, 'output' ),
			'notification',
			'normal',
			'high'
		);
	}

	/**
	 * Remove unnecessary meta boxes for notifications.
	 */
	public function remove_meta_boxes() {
		remove_meta_box( 'slugdiv', 'notification', 'normal' );
		remove_meta_box( 'postcustom', 'notification', 'normal' );
		remove_meta_box( 'postexcerpt', 'notification', 'normal' );
	}

	/**
	 * Check if we're saving, the trigger an action based on the post type.
	 *
	 * @param  int     $post_id Post Id.
	 * @param  WP_Post $post    Post instance.
	 */
	public function save_meta_boxes( $post_id, $post ) {
		// $post_id and $post are required
		if ( empty( $post_id ) || empty( $post ) ) {
			return;
		}

		// Dont' save meta boxes for revisions or autosaves.
		if (
			( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || is_int( wp_is_post_revision( $post ) ) ||
			is_int( wp_is_post_autosave( $post ) )
		) {
			return;
		}

		// Check the nonce.
		if (
			empty( $_POST[ KAGG_Notification_Meta_Box::SAVE_NONCE ] ) ||
			! wp_verify_nonce(
				filter_input( INPUT_POST, KAGG_Notification_Meta_Box::SAVE_NONCE, FILTER_SANITIZE_STRING ),
				KAGG_Notification_Meta_Box::SAVE_ACTION
			)
		) {
			return;
		}

		// Check the post being saved == the $post_id to prevent triggering this call for other save_post events.
		if ( empty( $_POST['post_ID'] ) || ( intval( $_POST['post_ID'] ) !== $post_id ) ) {
			return;
		}

		// Check user has permission to edit.
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		remove_action( 'save_post', array( $this, 'save_meta_boxes' ), 1 );
		$metabox = new KAGG_Notification_Meta_Box();
		$metabox->save( $post_id );
		add_action( 'save_post', array( $this, 'save_meta_boxes' ), 0, 3 );
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

		$args = array(
			'taxonomy'   => $taxonomy,
			'hide_empty' => true,
		);

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
}
