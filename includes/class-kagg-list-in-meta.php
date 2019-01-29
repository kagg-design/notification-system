<?php
/**
 * List in Meta.
 *
 * @package kagg-notifications
 */

/**
 * Class KAGG_List_In_Meta
 */
class KAGG_List_In_Meta {

	/**
	 * Delimiter in the list.
	 */
	const DELIMITER = '|';

	/**
	 * Looks for item in post meta, containing comma-separated list.
	 *
	 * @param int    $post_id  Post ID.
	 * @param string $meta_key Meta name.
	 * @param string $value    Item to find in the list.
	 *
	 * @return bool
	 */
	public function is_in_list( $post_id, $meta_key, $value ) {
		$meta_arr = $this->get_array( $post_id, $meta_key );
		$result   = array_search( (string) $value, $meta_arr, true );
		if ( false !== $result ) {
			return true;
		}

		return false;
	}

	/**
	 * Add item to list.
	 *
	 * @param int    $post_id  Post ID.
	 * @param string $meta_key Meta name.
	 * @param string $value    Item to add to the list.
	 */
	public function add( $post_id, $meta_key, $value ) {
		$meta_arr   = $this->get_array( $post_id, $meta_key );
		$meta_arr[] = $value;
		$this->update( $post_id, $meta_key, $meta_arr );
	}

	/**
	 * Remove item from list.
	 *
	 * @param int    $post_id  Post ID.
	 * @param string $meta_key Meta name.
	 * @param string $value    Item to remove from the list.
	 */
	public function remove( $post_id, $meta_key, $value ) {
		$meta_arr = $this->get_array( $post_id, $meta_key );
		$meta_arr = array_diff( $meta_arr, array( $value ) );
		$this->update( $post_id, $meta_key, $meta_arr );
	}

	/**
	 * Get array from the list stored in meta.
	 *
	 * @param int    $post_id  Post ID.
	 * @param string $meta_key Meta name.
	 *
	 * @return array
	 */
	public function get_array( $post_id, $meta_key ) {
		return array_filter( explode( self::DELIMITER, get_post_meta( $post_id, $meta_key, true ) ) );
	}

	/**
	 * Set array to store as list in meta.
	 *
	 * @param int    $post_id  Post ID.
	 * @param string $meta_key Meta name.
	 * @param array  $meta_arr Array of values.
	 */
	public function set_array( $post_id, $meta_key, $meta_arr ) {
		$this->update( $post_id, $meta_key, $meta_arr );
	}


	/**
	 * Get item prepared to search it the list.
	 *
	 * @param string $item Item.
	 *
	 * @return string
	 */
	public static function get_prepared_item( $item ) {
		return self::DELIMITER . $item . self::DELIMITER;
	}

	/**
	 * Update post meta.
	 *
	 * @param int    $post_id  Post ID.
	 * @param string $meta_key Meta name.
	 * @param array  $meta_arr Array of values.
	 */
	protected function update( $post_id, $meta_key, $meta_arr ) {
		$meta_arr = array_unique( array_filter( $meta_arr ) );
		if ( ! $meta_arr ) {
			delete_post_meta( $post_id, $meta_key );

			return;
		}
		$meta_value = implode( self::DELIMITER, $meta_arr );
		$meta_value = self::DELIMITER . $meta_value . self::DELIMITER;
		update_post_meta( $post_id, $meta_key, $meta_value );
	}
}
