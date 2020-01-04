<?php

namespace KAGG\Notification_System;

use WP_Error;
use WP_REST_Request;
use WP_UnitTestCase;

/**
 * Class Test_Notifications_API_Controller
 */
class Test_Notifications_API_Controller extends WP_UnitTestCase {
	public function test_register_routes() {
		do_action( 'rest_api_init' );

		$routes = $GLOBALS['wp_rest_server']->get_routes();

		$this->assertArrayHasKey( '/kagg/v1', $routes );
		$this->assertArrayHasKey( '/kagg/v1/notifications', $routes );
		$this->assertArrayHasKey( '/kagg/v1/notifications/(?P<id>[\d]+)', $routes );
	}

	public function test_get_collection_params() {
		$controller = new Notifications_API_Controller();
		$params     = $controller->get_collection_params();

		$this->assertArrayHasKey( 'slug', $params );
		$this->assertArrayHasKey( 'status', $params );
		$this->assertArrayHasKey( 'channel', $params );
	}

	public function test_get_endpoint_args_for_item_schema() {
		$controller = new Notifications_API_Controller();
		$args       = $controller->get_endpoint_args_for_item_schema();

		$this->assertEquals( [], $args );
	}

	public function test_get_item() {
		$controller = new Notifications_API_Controller();
		$item       = $controller->get_item( new WP_REST_Request( 'GET', 'kagg/v1/notifications' ) );

		$this->assertEquals( new WP_Error( 'KAGG_Notification_rest_invalid_id', 'Invalid ID.', [ 'status' => 404 ] ), $item );

		$postarr = [
			'post_type'   => 'notification',
			'post_status' => 'publish',
			'post_title'  => 'test post',
		];
		$post_id = wp_insert_post( $postarr );

		$rest_request = new WP_REST_Request( 'GET', '/kagg/v1/notifications' );
		$rest_request->set_url_params( [
			'id' => $post_id,
		] );
		$item = $controller->get_item( $rest_request );

		$this->assertEquals( $post_id, $item->data['id'] );
	}

	public function test_create_item() {
		$controller   = new Notifications_API_Controller();
		$rest_request = new WP_REST_Request( 'POST', '/kagg/v1/notifications' );
		$rest_request->set_url_params( [
			'id' => 1,
		] );
		$item = $controller->create_item( $rest_request );

		$this->assertEquals( new WP_Error( 'KAGG_Notification_rest_exists', 'Cannot create existing post.', [ 'status' => 400 ] ), $item );

		$rest_request = new WP_REST_Request( 'POST', '/kagg/v1/notifications' );
		$rest_request->set_url_params( [
			'title' => 'test post',
		] );
		$item = $controller->create_item( $rest_request );

		$this->assertEquals( 'test post', $item->data['title'] );
	}

	public function test_update_item() {
		$postarr = [
			'post_type'   => 'notification',
			'post_status' => 'publish',
			'post_title'  => 'test post',
		];
		$post_id = wp_insert_post( $postarr );

		$rest_request = new WP_REST_Request( 'UPDATE', '/kagg/v1/notifications' );
		$rest_request->set_url_params( [
			'id'    => $post_id,
			'title' => 'updated test post',
		] );

		$controller = new Notifications_API_Controller();
		$item       = $controller->update_item( $rest_request );

		$this->assertEquals( 'updated test post', $item->data['title'] );
	}

	public function test_delete_item() {
		$postarr = [
			'post_type'   => 'notification',
			'post_status' => 'publish',
			'post_title'  => 'test post',
		];
		$post_id = wp_insert_post( $postarr );

		$rest_request = new WP_REST_Request( 'DELETE', '/kagg/v1/notifications' );
		$rest_request->set_url_params( [
			'id' => $post_id,
		] );

		$controller = new Notifications_API_Controller();
		$controller->delete_item( $rest_request );

		$post = get_post( $post_id );
		$this->assertEmpty( $post );
	}

	public function test_get_items() {
		$postarr = [
			'post_type'   => 'notification',
			'post_status' => 'publish',
			'post_title'  => 'test post 1',
		];
		wp_insert_post( $postarr );

		$postarr = [
			'post_type'   => 'notification',
			'post_status' => 'publish',
			'post_title'  => 'test post 2',
		];
		wp_insert_post( $postarr );

		$rest_request = new WP_REST_Request( 'GET', '/kagg/v1/notifications' );
		$rest_request->set_default_params( [ 'status' => 'any', 'orderby'=> 'id', 'order' => 'ASC' ] );
		$rest_request->set_url_params( [ 'per_page' => '3' ] );
		$rest_request->set_body( '' );

		$controller = new Notifications_API_Controller();
		$items      = $controller->get_items( $rest_request );

		$this->assertEquals( 'test post 1', $items->data[0]['title'] );
		$this->assertEquals( 'test post 2', $items->data[1]['title'] );
	}

	public function test_get_items_permissions_check() {
		$rest_request = new WP_REST_Request( 'GET', '/kagg/v1/notifications' );
		$controller   = new Notifications_API_Controller();

		$this->assertEquals(
			new WP_Error( 'KAGG_Notification_rest_cannot_create', 'Sorry, you cannot view resources.', [ 'status' => rest_authorization_required_code() ] ),
			$controller->get_items_permissions_check( $rest_request )
		);
	}

	public function test_get_item_permissions_check() {
		$rest_request = new WP_REST_Request( 'GET', '/kagg/v1/notifications' );
		$controller   = new Notifications_API_Controller();

		$this->assertEquals(
			new WP_Error( 'KAGG_Notification_rest_cannot_create', 'Sorry, you cannot view resources.', [ 'status' => rest_authorization_required_code() ] ),
			$controller->get_item_permissions_check( $rest_request )
		);
	}

	public function test_create_item_permissions_check() {
		$rest_request = new WP_REST_Request( 'GET', '/kagg/v1/notifications' );
		$controller   = new Notifications_API_Controller();

		$this->assertEquals(
			new WP_Error( 'KAGG_Notification_rest_cannot_create', 'Sorry, you cannot create resources.', [ 'status' => rest_authorization_required_code() ] ),
			$controller->create_item_permissions_check( $rest_request )
		);
	}

	public function test_update_item_permissions_check() {
		$rest_request = new WP_REST_Request( 'GET', '/kagg/v1/notifications' );
		$controller   = new Notifications_API_Controller();

		$this->assertEquals(
			new WP_Error( 'KAGG_Notification_rest_cannot_update', 'Sorry, you cannot update resources.', [ 'status' => rest_authorization_required_code() ] ),
			$controller->update_item_permissions_check( $rest_request )
		);
	}

	public function test_delete_item_permissions_check() {
		$rest_request = new WP_REST_Request( 'GET', '/kagg/v1/notifications' );
		$controller   = new Notifications_API_Controller();

		$this->assertEquals(
			new WP_Error( 'KAGG_Notification_rest_cannot_delete', 'Sorry, you cannot delete resources.', [ 'status' => rest_authorization_required_code() ] ),
			$controller->delete_item_permissions_check( $rest_request )
		);
	}
}
