<?php
/**
 * Class to check requirements of the plugin.
 *
 * @package notification-system
 */

namespace KAGG\NotificationSystem;

/**
 * Class Requirements
 */
class Requirements {

	/**
	 * Check if requirements are met.
	 *
	 * @return bool
	 */
	public function are_requirements_met(): bool {
		return $this->is_php_version_required();
	}

	/**
	 * Check the PHP version.
	 *
	 * @return bool
	 */
	private function is_php_version_required(): bool {
		if ( version_compare( KAGG_NOTIFICATIONS_MINIMUM_PHP_REQUIRED_VERSION, PHP_VERSION, '>' ) ) {
			add_action( 'admin_notices', [ $this, 'php_requirement_message' ] );

			return false;
		}

		return true;
	}

	/**
	 * Show notice with php requirement.
	 */
	public function php_requirement_message() {
		load_plugin_textdomain(
			'notification-system',
			false,
			dirname( plugin_basename( KAGG_NOTIFICATIONS_FILE ) ) . '/languages/'
		);

		$message = sprintf(
		/* translators: 1: Current PHP version number, 2: Notification System version, 3: Minimum required PHP version number */
			__( 'Your server is running PHP version %1$s but Notification System %2$s requires at least %3$s.', 'notification-system' ),
			PHP_VERSION,
			KAGG_NOTIFICATIONS_VERSION,
			KAGG_NOTIFICATIONS_MINIMUM_PHP_REQUIRED_VERSION
		);

		?>
		<div class="message error">
			<p>
				<?php echo esc_html( $message ); ?>
			</p>
		</div>
		<?php
	}
}
