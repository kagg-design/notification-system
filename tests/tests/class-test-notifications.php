<?php

namespace KAGG\Notification_System;

use WP_Hook;
use WP_UnitTestCase;

/**
 * Class Test_Notifications
 */
class Test_Notifications extends WP_UnitTestCase {

	public function test_classes_exist() {
		$this->assertTrue( class_exists( __NAMESPACE__ . '\Notifications' ) );
		$this->assertTrue( class_exists( __NAMESPACE__ . '\Notifications_API' ) );
		$this->assertTrue( class_exists( __NAMESPACE__ . '\Notifications_API_Controller' ) );
	}

	public function test_init_hooks() {
		global $wp_filter;

		do_action( 'rest_api_init' );

		$this->assertEquals(
			10,
			$this->action_priority( 'init', __NAMESPACE__ . '\Notifications', 'register_taxonomies' )
		);
		$this->assertEquals(
			10,
			$this->action_priority( 'init', __NAMESPACE__ . '\Notifications', 'add_rewrite_rules' )
		);
		$this->assertEquals(
			10,
			$this->action_priority( 'init', __NAMESPACE__ . '\Notifications', 'register_cpt_notification' )
		);

		// @todo add more careful check of function name and priority
		$main_file = trim( str_replace( '\\', '/', PLUGIN_MAIN_FILE ), '/' );
		$this->assertArrayHasKey( 'activate_' . $main_file, $wp_filter );
		$this->assertArrayHasKey( 'deactivate_' . $main_file, $wp_filter );

		$this->assertEquals(
			20,
			$this->action_priority( 'wp_enqueue_scripts', __NAMESPACE__ . '\Notifications', 'enqueue_scripts' )
		);

		$this->assertEquals(
			PHP_INT_MAX,
			$this->action_priority( 'init', __NAMESPACE__ . '\Notifications', 'notifications_page' )
		);

		$this->assertEquals(
			10,
			$this->action_priority( 'wp_ajax_kagg_notification_get_popup_content', __NAMESPACE__ . '\Notifications', 'get_popup_content' )
		);
		$this->assertEquals(
			10,
			$this->action_priority( 'wp_ajax_nopriv_kagg_notification_get_popup_content', __NAMESPACE__ . '\Notifications', 'get_popup_content' )
		);

	}

	public function test_register_taxonomies() {
		$this->assertTrue( taxonomy_exists( 'channel' ) );
	}

	public function test_add_rewrite_rules() {
		// @todo check rewrite tags

		do_action( 'rest_api_init' );

		$this->assertEquals(
			10,
			$this->action_priority( 'query_vars', __NAMESPACE__ . '\Notifications', 'add_query_vars' )
		);
	}

	public function test_register_cpt_dealer() {
		$this->assertTrue( post_type_exists( 'notification' ) );
	}

	public function test_enqueue_scripts() {
		// @todo check that wp-api is localized

		$main_class = new Notifications();
		$main_class->enqueue_scripts();

		$this->assertTrue( wp_script_is( 'wp-api' ) );
		$this->assertTrue( wp_script_is( 'notification-system' ) );
		$this->assertTrue( wp_style_is( 'notification-system' ) );
	}

	public function test_add_query_vars() {
		$vars = [ 'channel' ];

		$main_class = new Notifications();

		$this->assertEquals( $vars, $main_class->add_query_vars( [] ) );
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
