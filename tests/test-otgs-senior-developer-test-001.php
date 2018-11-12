<?php

/**
 * Class OTGS_Senior_Developer_Test_001
 */
class OTGS_Senior_Developer_Test_001 extends WP_UnitTestCase {

	public function test_classes_exist() {
		$this->assertTrue( class_exists( 'KAGG_NOTIFICATION' ) );
		$this->assertTrue( class_exists( 'KAGG_Notification_API' ) );
		$this->assertTrue( class_exists( 'KAGG_Notification_API_Controller' ) );
	}

	public function test_init_hooks() {
		global $wp_filter;

		$this->assertEquals(
			10,
			$this->action_priority( 'init', 'KAGG_NOTIFICATION', 'register_taxonomies' )
		);
		$this->assertEquals(
			10,
			$this->action_priority( 'init', 'KAGG_NOTIFICATION', 'add_rewrite_rules' )
		);
		$this->assertEquals(
			10,
			$this->action_priority( 'init', 'KAGG_NOTIFICATION', 'register_cpt_dealer' )
		);

		// @todo add more careful check of function name and priority
		$this->assertArrayHasKey( 'activate_var/www/otgs/wp-content/plugins/otgs-senior-developer-test-001/otgs-senior-developer-test-001.php', $wp_filter );
		$this->assertArrayHasKey( 'deactivate_var/www/otgs/wp-content/plugins/otgs-senior-developer-test-001/otgs-senior-developer-test-001.php', $GLOBALS['wp_filter'] );

		$this->assertEquals(
			20,
			$this->action_priority( 'wp_enqueue_scripts', 'KAGG_NOTIFICATION', 'enqueue_scripts' )
		);

		$this->assertEquals(
			10,
			$this->action_priority( 'init', 'KAGG_NOTIFICATION', 'dealers_page' )
		);

		$this->assertEquals(
			10,
			$this->action_priority( 'wp_ajax_KAGG_NOTIFICATION_send_order', 'KAGG_NOTIFICATION', 'send_order_callback' )
		);
		$this->assertEquals(
			10,
			$this->action_priority( 'wp_ajax_nopriv_KAGG_NOTIFICATION_send_order', 'KAGG_NOTIFICATION', 'send_order_callback' )
		);

	}

	public function test_register_taxonomies() {
		$this->assertTrue( taxonomy_exists( 'location' ) );
		$this->assertTrue( taxonomy_exists( 'dealer_type' ) );
		$this->assertTrue( taxonomy_exists( 'brand' ) );
	}

	public function test_add_rewrite_rules() {
		// @todo check rewrite tags

		$this->assertEquals(
			10,
			$this->action_priority( 'query_vars', 'KAGG_NOTIFICATION', 'add_query_vars' )
		);
	}

	public function test_register_cpt_dealer() {
		$this->assertTrue( post_type_exists( 'dealer' ) );
	}

	public function test_enqueue_scripts() {
		// @todo check that wp-api is localized

		$main_class = new KAGG_NOTIFICATION();
		$main_class->enqueue_scripts();

		$this->assertTrue( wp_script_is( 'wp-api' ) );
		$this->assertTrue( wp_script_is( 'kagg-notification' ) );
		$this->assertTrue( wp_style_is( 'kagg-notification' ) );
	}

	public function test_add_query_vars() {
		$vars = array( 'location', 'dealer_type', 'brand' );

		$main_class = new KAGG_NOTIFICATION();

		$this->assertEquals( $vars, $main_class->add_query_vars( array() ) );
	}

	/*
	public function test_dealers_page() {
		// @todo this doesn't work, of course
		// $this->go_to( '/dealers' );
		// $this->assertQueryTrue( 'is_page' );
	}
	*/

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
