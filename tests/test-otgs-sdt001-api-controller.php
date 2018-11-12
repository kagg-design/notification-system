<?php
// This can be used in tested code to define if tests are running.
/*
if ( defined( '__PHPUNIT_PHAR__' ) ) {
	return $something;
}
*/

/**
 * Class KAGG_Notification_API_Controller_Test
 */
class KAGG_Notification_API_Controller_Test extends WP_UnitTestCase {
	public function test_register_routes() {
		$controller = new KAGG_Notification_API_Controller();
		$controller->register_routes();

		$routes = $GLOBALS['wp_rest_server']->get_routes();

		$this->assertArrayHasKey( '/otgs/SDT001/v1', $routes );
		$this->assertArrayHasKey( '/otgs/SDT001/v1/dealers', $routes );
		$this->assertArrayHasKey( '/otgs/SDT001/v1/dealers/(?P<id>[\d]+)', $routes );
	}

	public function test_get_collection_params() {
		$controller = new KAGG_Notification_API_Controller();
		$params     = $controller->get_collection_params();

		$this->assertArrayHasKey( 'slug', $params );
		$this->assertArrayHasKey( 'status', $params );
		$this->assertArrayHasKey( 'location', $params );
		$this->assertArrayHasKey( 'dealer_type', $params );
		$this->assertArrayHasKey( 'brand', $params );
	}

	public function test_get_endpoint_args_for_item_schema() {
		$controller = new KAGG_Notification_API_Controller();
		$args       = $controller->get_endpoint_args_for_item_schema();

		$this->assertEquals( array(), $args );
	}

	public function test_get_item() {
		$controller = new KAGG_Notification_API_Controller();
		$item       = $controller->get_item( new WP_REST_Request( 'GET', 'otgs/SDT001/v1/dealers' ) );

		$this->assertEquals( new WP_Error( 'KAGG_NOTIFICATION_rest_invalid_id', __( 'Invalid ID.', 'kagg-notification' ), array( 'status' => 404 ) ), $item );

		$postarr = array(
			'post_type'   => 'dealer',
			'post_status' => 'publish',
			'post_title'  => 'test post',
		);
		$post_id = wp_insert_post( $postarr );

		$rest_request = new WP_REST_Request( 'GET', '/otgs/SDT001/v1/dealers' );
		$rest_request->set_url_params( array(
			'id' => $post_id,
		) );
		$item = $controller->get_item( $rest_request );

		$this->assertEquals( $post_id, $item->data['id'] );
	}

	public function test_create_item() {
		$controller   = new KAGG_Notification_API_Controller();
		$rest_request = new WP_REST_Request( 'POST', '/otgs/SDT001/v1/dealers' );
		$rest_request->set_url_params( array(
			'id' => 1,
		) );
		$item = $controller->create_item( $rest_request );

		$this->assertEquals( new WP_Error( 'KAGG_NOTIFICATION_rest_exists', __( 'Cannot create existing post.', 'kagg-notification' ), array( 'status' => 400 ) ), $item );

		$rest_request = new WP_REST_Request( 'POST', '/otgs/SDT001/v1/dealers' );
		$rest_request->set_url_params( array(
			'title' => 'test post',
		) );
		$item = $controller->create_item( $rest_request );

		$this->assertEquals( 'test post', $item->data['name'] );
	}

	public function test_update_item() {
		$postarr = array(
			'post_type'   => 'dealer',
			'post_status' => 'publish',
			'post_title'  => 'test post',
		);
		$post_id = wp_insert_post( $postarr );

		$rest_request = new WP_REST_Request( 'UPDATE', '/otgs/SDT001/v1/dealers' );
		$rest_request->set_url_params( array(
			'id'    => $post_id,
			'title' => 'updated test post',
		) );

		$controller = new KAGG_Notification_API_Controller();
		$item       = $controller->update_item( $rest_request );

		$this->assertEquals( 'updated test post', $item->data['name'] );
	}

	public function test_delete_item() {
		$postarr = array(
			'post_type'   => 'dealer',
			'post_status' => 'publish',
			'post_title'  => 'test post',
		);
		$post_id = wp_insert_post( $postarr );

		$rest_request = new WP_REST_Request( 'DELETE', '/otgs/SDT001/v1/dealers' );
		$rest_request->set_url_params( array(
			'id' => $post_id,
		) );

		$controller = new KAGG_Notification_API_Controller();
		$controller->delete_item( $rest_request );

		$post = get_post( $post_id );
		$this->assertEmpty( $post );
	}

	public function test_get_items() {
		$postarr = array(
			'post_type'   => 'dealer',
			'post_status' => 'publish',
			'post_title'  => 'test post 1',
		);
		wp_insert_post( $postarr );

		$postarr = array(
			'post_type'   => 'dealer',
			'post_status' => 'publish',
			'post_title'  => 'test post 2',
		);
		wp_insert_post( $postarr );

		$rest_request = new WP_REST_Request( 'GET', '/kagg/v1/notifications' );
		$rest_request->set_default_params( array(
			'status' => 'any',
		) );
		$rest_request->set_url_params( array(
			'per_page' => '3',
		) );
		$rest_request->set_body( '' );

		$controller = new KAGG_Notification_API_Controller();
		$items      = $controller->get_items( $rest_request );

		$this->assertEquals( 'test post 2', $items->data[0]['name'] );
		$this->assertEquals( 'test post 1', $items->data[1]['name'] );
	}

	// prepare_object_for_response() is tested via get_items(), get_item(), etc.

	public function test_get_items_permissions_check() {
		$rest_request = new WP_REST_Request( 'GET', '/kagg/v1/notifications' );
		$controller   = new KAGG_Notification_API_Controller();

		$this->assertEquals(
			new WP_Error( 'KAGG_NOTIFICATION_rest_cannot_create', __( 'Sorry, you cannot view resources.', 'kagg-notification' ), array( 'status' => rest_authorization_required_code() ) ),
			$controller->get_items_permissions_check( $rest_request )
		);
	}

	public function test_get_item_permissions_check() {
		$rest_request = new WP_REST_Request( 'GET', '/kagg/v1/notifications' );
		$controller   = new KAGG_Notification_API_Controller();

		$this->assertEquals(
			new WP_Error( 'KAGG_NOTIFICATION_rest_cannot_create', __( 'Sorry, you cannot view resources.', 'kagg-notification' ), array( 'status' => rest_authorization_required_code() ) ),
			$controller->get_item_permissions_check( $rest_request )
		);
	}

	public function test_create_item_permissions_check() {
		$rest_request = new WP_REST_Request( 'GET', '/kagg/v1/notifications' );
		$controller   = new KAGG_Notification_API_Controller();

		$this->assertEquals(
			new WP_Error( 'KAGG_NOTIFICATION_rest_cannot_create', __( 'Sorry, you cannot create resources.', 'kagg-notification' ), array( 'status' => rest_authorization_required_code() ) ),
			$controller->create_item_permissions_check( $rest_request )
		);
	}

	public function test_update_item_permissions_check() {
		$rest_request = new WP_REST_Request( 'GET', '/kagg/v1/notifications' );
		$controller   = new KAGG_Notification_API_Controller();

		$this->assertEquals(
			new WP_Error( 'KAGG_NOTIFICATION_rest_cannot_update', __( 'Sorry, you cannot update resources.', 'kagg-notification' ), array( 'status' => rest_authorization_required_code() ) ),
			$controller->update_item_permissions_check( $rest_request )
		);
	}

	public function test_delete_item_permissions_check() {
		$rest_request = new WP_REST_Request( 'GET', '/kagg/v1/notifications' );
		$controller   = new KAGG_Notification_API_Controller();

		$this->assertEquals(
			new WP_Error( 'KAGG_NOTIFICATION_rest_cannot_delete', __( 'Sorry, you cannot delete resources.', 'kagg-notification' ), array( 'status' => rest_authorization_required_code() ) ),
			$controller->delete_item_permissions_check( $rest_request )
		);
	}
}
