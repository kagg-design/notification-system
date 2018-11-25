<?php

/**
 * Class KAGG_Notification_Meta_Box
 */
class KAGG_Notification_Meta_Box {
	const SAVE_ACTION = 'notification_save_data';

	const SAVE_NONCE = 'notification_meta_nonce';

	const NOTICES_OPTION = 'kagg_notification_box_notices';

	/**
	 * Output the metabox.
	 *
	 * @param WP_Post $post
	 */
	public static function output( $post ) {
		wp_nonce_field( self::SAVE_ACTION, self::SAVE_NONCE );

		$notification_id = absint( $post->ID );
		$notification    = new KAGG_Notification( $notification_id );
		$users           = $notification->get_user_list();
		?>

		<div id="coupon_options" class="panel-wrap coupon_data">

			<div id="general_coupon_data" class="panel fq_options_panel">
				<?php

				// Amount.
				KAGG_Input_Fields::text_input(
					array(
						'id'          => 'users',
						'label'       => __( 'Show to users', 'kagg-notifications' ),
						'description' => __( 'List of users to whom to show this notification.', 'kagg-notifications' ),
						'desc_tip'    => true,
						'type'        => 'text',
						'value'       => $users,
					)
				);

				?>
			</div>
		</div>
		<div class="clear"></div>
		<?php
	}

	/**
	 * Save meta box data.
	 *
	 * @param int $post_id
	 */
	public static function save( $post_id ) {
		// Check the nonce.
		if ( empty( $_POST[ self::SAVE_NONCE ] ) || ! wp_verify_nonce( $_POST[ self::SAVE_NONCE ], self::SAVE_ACTION ) ) {
			return;
		}

		$users = KAGG_Input_Fields::clean( $_POST['users'] );

		$notification = new KAGG_Notification( $post_id );
		$notification->set_user_list( $users );
		$notification->save();
	}
}
