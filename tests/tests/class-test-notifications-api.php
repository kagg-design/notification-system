<?php

namespace KAGG\Notification_System;

use WP_Hook;
use WP_UnitTestCase;

/**
 * Class Test_Notifications_API
 */
class Test_Notifications_API extends WP_UnitTestCase {
	public function test_rest_api_init() {
		$this->assertEquals(
			10,
			$this->action_priority( 'rest_api_init', __NAMESPACE__ . '\Notifications_API', 'register_rest_routes' )
		);
	}

	public function test_register_rest_routes() {
		do_action( 'rest_api_init' );

		$routes = $GLOBALS['wp_rest_server']->get_routes();

		$this->assertArrayHasKey( '/kagg/v1', $routes );
		$this->assertArrayHasKey( '/kagg/v1/notifications', $routes );
		$this->assertArrayHasKey( '/kagg/v1/notifications/(?P<id>[\d]+)', $routes );
	}

	/**
	 * Get priority of action or filter.
	 *
	 * @param string $action_name   Action or filter name.
	 * @param string $class_name    Class name enqueueing the action.
	 * @param string $function_name Function name enqueueing the action.
	 *
	 * @return int|null|string
	 */
	protected function action_priority( $action_name, $class_name, $function_name ) {
		global $wp_filter;

		/** @var WP_Hook $hooks */
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
