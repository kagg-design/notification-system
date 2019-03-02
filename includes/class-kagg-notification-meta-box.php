<?php
/**
 * KAGG_Notification_Meta_Box class file.
 *
 * @package notification-system
 */

/**
 * Class KAGG_Notification_Meta_Box
 */
class KAGG_Notification_Meta_Box {
	/**
	 * Ajax save action name.
	 */
	const SAVE_ACTION = 'notification_save_data';

	/**
	 * Ajax save action nonce name.
	 */
	const SAVE_NONCE = 'notification_meta_nonce';

	/**
	 * Output the metabox.
	 *
	 * @param WP_Post $post Notification post.
	 */
	public static function output( $post ) {
		wp_nonce_field( self::SAVE_ACTION, self::SAVE_NONCE );

		$notification_id = absint( $post->ID );
		$notification    = new KAGG_Notification( $notification_id );
		$users           = $notification->get_user_list();
		?>

		<div id="notification_options" class="panel-wrap notification_data">

			<div id="general_notification_data" class="panel fq_options_panel">
				<?php

				// Amount.
				KAGG_Input_Fields::text_input(
					array(
						'id'          => 'users',
						'label'       => __( 'Show to users', 'notification-system' ),
						'description' => __( 'List of users to whom to show this notification.', 'notification-system' ),
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
	 * @param int $post_id Notification post id.
	 */
	public static function save( $post_id ) {
		// Check the nonce.
		if (
			empty( $_POST[ self::SAVE_NONCE ] ) ||
			! wp_verify_nonce(
				filter_input( INPUT_POST, self::SAVE_NONCE, FILTER_SANITIZE_STRING ),
				self::SAVE_ACTION
			)
		) {
			return;
		}

		// Proper sanitization is performed in clean().
		// phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		// phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotValidated
		// phpcs:disable WordPress.Security.ValidatedSanitizedInput.MissingUnslash
		$users = KAGG_Input_Fields::clean( $_POST['users'] );
		// phpcs:enable WordPress.Security.ValidatedSanitizedInput.MissingUnslash
		// phpcs:enable WordPress.Security.ValidatedSanitizedInput.InputNotValidated
		// phpcs:enable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		$notification = new KAGG_Notification( $post_id );
		$notification->set_user_list( $users );
		$notification->save();
	}
}
