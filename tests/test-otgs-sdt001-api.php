<?php

/**
 * Class KAGG_Notification_API_Test
 */
class KAGG_Notification_API_Test extends WP_UnitTestCase {
	public function test_rest_api_init() {
		$this->assertEquals(
			10,
			$this->action_priority( 'rest_api_init', 'KAGG_Notification_API', 'register_rest_routes' )
		);
	}

	public function test_register_rest_routes() {
		$api = new KAGG_Notification_API();
		$api->register_rest_routes();

		$routes = $GLOBALS['wp_rest_server']->get_routes();

		$this->assertArrayHasKey( '/otgs/SDT001/v1', $routes );
		$this->assertArrayHasKey( '/otgs/SDT001/v1/dealers', $routes );
		$this->assertArrayHasKey( '/otgs/SDT001/v1/dealers/(?P<id>[\d]+)', $routes );
	}

	/**
	 * Get priority of action or filter.
	 *
	 * @param string $action_name Action or filter name.
	 * @param string $class_name Class name enqueueing the action.
	 * @param string $function_name Function name enqueueing the action.
	 *
	 * @return int|null|string
	 */
	protected function action_priority( $action_name, $class_name, $function_name ) {
		global $wp_filter;

		/** @var $hooks WP_Hook */
		$hooks = $wp_filter[ $action_name ];

		$callbacks = $hooks->callbacks;

		foreach ( $callbacks as $priority => $actions ) {
			foreach ( $actions as $action ) {
				$function = $action['function'];
				if (
					is_array( $function ) &&
					( get_class( $function[0] ) === $class_name ) &&
					( $function[1] === $function_name )
				) {
					return $priority;
				}
			}
		}

		return null;
	}
}

