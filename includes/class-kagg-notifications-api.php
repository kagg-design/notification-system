<?php
/**
 * Class KAGG_Notifications_API
 */
class KAGG_Notifications_API {

	/**
	 * KAGG_Notifications_API constructor.
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

		// Init REST API routes.
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
	}

	/**
	 * Register REST API routes.
	 */
	public function register_rest_routes() {
		// Register settings to the REST API.

		$controllers = array(
			'KAGG_Notifications_API_Controller',
		);

		foreach ( $controllers as $controller ) {
			$this->$controller = new $controller();
			$this->$controller->register_routes();
		}
	}
}
