<?php
/**
 * Class KAGG_List_In_Meta
 */
class KAGG_List_In_Meta {
	const DELIMITER = '|';

	/**
	 * Looks for item in post meta, containing comma-separated list.
	 *
	 * @param int $post_id Post ID.
	 * @param string $meta_key Meta name.
	 * @param string $value Item to find in the list.
	 *
	 * @return bool
	 */
	public function is_listed_in_meta( $post_id, $meta_key, $value ) {
		$meta_arr = $this->get_arr_from_list_in_meta( $post_id, $meta_key );
		$result   = array_search( (string) $value, $meta_arr, true );
		if ( false !== $result ) {
			return true;
		}

		return false;
	}

	/**
	 * @param int $post_id Post ID.
	 * @param string $meta_key Meta name.
	 * @param string $value Item to add to the list.
	 */
	public function add_to_list_in_meta( $post_id, $meta_key, $value ) {
		$meta_arr   = $this->get_arr_from_list_in_meta( $post_id, $meta_key );
		$meta_arr[] = $value;
		$this->update_list_in_meta( $post_id, $meta_key, $meta_arr );
	}

	/**
	 * @param int $post_id Post ID.
	 * @param string $meta_key Meta name.
	 * @param string $value Item to remove from the list.
	 */
	public function remove_from_list_in_meta( $post_id, $meta_key, $value ) {
		$meta_arr = $this->get_arr_from_list_in_meta( $post_id, $meta_key );
		$meta_arr = array_diff( $meta_arr, array( $value ) );
		$this->update_list_in_meta( $post_id, $meta_key, $meta_arr );
	}

	/**
	 * Get array from the list stored in meta.
	 *
	 * @param int $post_id Post ID.
	 * @param string $meta_key Meta name.
	 *
	 * @return array
	 */
	protected function get_arr_from_list_in_meta( $post_id, $meta_key ) {
		return array_filter( explode( self::DELIMITER, get_post_meta( $post_id, $meta_key, true ) ) );
	}

	/**
	 * @param int $post_id Post ID.
	 * @param string $meta_key Meta name.
	 * @param array $meta_arr Array of values.
	 */
	protected function update_list_in_meta( $post_id, $meta_key, $meta_arr ) {
		$meta_value = implode( self::DELIMITER, array_unique( array_filter( $meta_arr ) ) );
		$meta_value = self::DELIMITER . $meta_value . self::DELIMITER;
		update_post_meta( $post_id, $meta_key, $meta_value );
	}
}
