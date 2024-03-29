<?php
/**
 * Notification class file.
 *
 * @package notification-system
 */

namespace KAGG\NotificationSystem;

/**
 * Class Notification
 */
class Notification {

	/**
	 * Read status meta key.
	 *
	 * @var string
	 */
	const READ_STATUS_META_KEY = '_read';

	/**
	 * Users meta key.
	 *
	 * @var string
	 */
	const USERS_META_KEY = '_users';

	/**
	 * ID of this notification.
	 *
	 * @var int
	 */
	public $id = 0;

	/**
	 * Instance of List In Meta class.
	 *
	 * @var ListInMeta
	 */
	protected $list_in_meta;

	/**
	 * Notification constructor.
	 *
	 * @param int $id Notification post ID.
	 */
	public function __construct( $id ) {
		$this->id           = absint( $id );
		$this->list_in_meta = new ListInMeta();
	}

	/**
	 * Get read status of the notification for current user.
	 *
	 * @return bool
	 */
	public function get_read_status(): bool {
		return $this->list_in_meta->is_in_list( $this->id, self::READ_STATUS_META_KEY, wp_get_current_user()->ID );
	}

	/**
	 * Set read status of the notification for the current user.
	 *
	 * @param bool $read_status Read status.
	 */
	public function set_read_status( $read_status ) {
		$user_id = wp_get_current_user()->ID;
		if ( $read_status ) {
			$this->list_in_meta->add( $this->id, self::READ_STATUS_META_KEY, $user_id );
		} else {
			$this->list_in_meta->remove( $this->id, self::READ_STATUS_META_KEY, $user_id );
		}
	}

	/**
	 * Get an array of user ids to whom to show notifications.
	 *
	 * @return array
	 */
	public function get_users(): array {
		return $this->list_in_meta->get_array( $this->id, self::USERS_META_KEY );
	}

	/**
	 * Set array of user ids to whom to show notifications.
	 *
	 * @param array $users User ids to save.
	 */
	public function set_users( $users ) {
		$this->list_in_meta->set_array( $this->id, self::USERS_META_KEY, $users );
	}

	/**
	 * Get a list of users as comma-separated string.
	 *
	 * @return string
	 */
	public function get_user_list(): string {
		$users = $this->get_users();
		foreach ( $users as $key => $user ) {
			$user_data = get_userdata( $user );
			if ( $user_data ) {
				$users[ $key ] = $user_data->user_login;
			}
		}

		return implode( ', ', $users );
	}

	/**
	 * Set the list of users defined by the comma-separated string.
	 *
	 * @param string $users User list as comma-separated string.
	 */
	public function set_user_list( $users ) {
		$users         = preg_replace( '/\s+/', '', $users );
		$users_arr     = explode( ',', $users );
		$users_to_save = [];

		foreach ( $users_arr as $user ) {
			$wp_user = get_user_by( 'login', $user );
			if ( $wp_user ) {
				$users_to_save[] = $wp_user->ID;
			}
		}

		$this->set_users( $users_to_save );
	}

	/**
	 * Save notification to database.
	 */
	public function save() {
		clean_post_cache( $this->id );
	}
}
