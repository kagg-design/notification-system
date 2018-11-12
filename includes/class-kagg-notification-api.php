<?php
/**
 * KAGG Notification plugin API.
 */

/**
 * Class KAGG_Notification_API
 */
class KAGG_Notification_API {

	/**
	 * KAGG_Notification_API constructor.
	 */
	public function __construct() {
		// Init REST API.
		$this->rest_api_init();
	}

	/**
	 * Init REST API.
	 */
	private function rest_api_init() {
		// REST API was included starting WordPress 4.4.
		if ( ! class_exists( 'WP_REST_Server' ) ) {
			return;
		}

		$this->rest_api_includes();

		// Init REST API routes.
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
	}

	/**
	 * Include REST API classes.
	 */
	private function rest_api_includes() {
		include_once KAGG_NOTIFICATION_PATH . '/includes/class-kagg-notification-api-controller.php';
	}

	/**
	 * Register REST API routes.
	 */
	public function register_rest_routes() {
		// Register settings to the REST API.

		$controllers = array(
			'KAGG_Notification_API_Controller',
		);

		foreach ( $controllers as $controller ) {
			$this->$controller = new $controller();
			$this->$controller->register_routes();
		}
	}
}
