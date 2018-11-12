<?php

/**
 * Class Ajax_Test
 */
class Ajax_Test extends WP_Ajax_UnitTestCase {
	public function test_send_order_callback() {
		$_POST['nonce'] = wp_create_nonce( 'kagg-notification-rest' );
		$_POST['text']  = 'test_text';

		try {
			$this->_handleAjax( 'KAGG_NOTIFICATION_send_order' );
			$this->fail( 'Expected exception: WPAjaxDieContinueException' );
		} catch ( WPAjaxDieContinueException $e ) {
			// We expected this, do nothing.
		}
		$response = json_decode( $this->_last_response, true );
		$this->assertTrue( $response['success'] );
		$this->assertEquals( $response['data'], __( 'Your order was processed.', 'kagg-notification' ) );
	}
}
