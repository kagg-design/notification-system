<?php

/**
 * Class KAGG_Notification_Meta_Box
 */
class KAGG_Notification_Meta_Box {
	const SAVE_ACTION = 'notification_save_data';

	const SAVE_NONCE = 'notification_meta_nonce';

	/**
	 * Notices.
	 *
	 * @var array Notices.
	 */
	public static $notices = array();

	/**
	 * FQ_Meta_Box_Coupon_Data constructor.
	 */
	public function __construct() {
		add_action( 'admin_notices', array( $this, 'output_notices' ) );
		add_action( 'shutdown', array( $this, 'save_notices' ) );
	}

	/**
	 * Show admin notice.
	 *
	 * @param string $message Message to show.
	 * @param string $class Message class: notice notice-success notice-error notice-warning notice-info is-dismissible.
	 */
	protected function admin_notice( $message, $class ) {
		?>
		<div class="<?php echo esc_attr( $class ); ?>">
			<p>
				<span style="display: block; margin: 0.5em 0.5em 0 0; clear: both;">
				<?php echo wp_kses_post( $message ); ?>
				</span>
			</p>
		</div>
		<?php
	}

	/**
	 * Add admin notice
	 *
	 * @param string $message Message to add.
	 * @param string $class Message class: notice notice-success notice-error notice-warning notice-info is-dismissible.
	 */
	public static function add_notice( $message, $class ) {
		self::$notices[] = array(
			'message' => $message,
			'class'   => $class,
		);
	}

	/**
	 * Save notices to an option.
	 */
	public function save_notices() {
		update_option( 'quote_meta_box_notices', self::$notices );
	}

	/**
	 * Output all notices.
	 */
	public function output_notices() {
		$notices = array_filter( (array) get_option( 'quote_meta_box_notices' ) );

		if ( ! empty( $notices ) ) {
			foreach ( $notices as $notice ) {
				$this->admin_notice( $notice['message'], $notice['class'] );
			}
			delete_option( 'quote_meta_box_notices' );
		}
	}

	/**
	 * Output the metabox.
	 *
	 * @param WP_Post $post
	 */
	public static function output( $post ) {
		wp_nonce_field( self::SAVE_ACTION, self::SAVE_NONCE );

		$notification_id = absint( $post->ID );
		$coupon    = new FQ_Coupon( $notification_id );

		?>

		<div id="coupon_options" class="panel-wrap coupon_data">

			<div id="general_coupon_data" class="panel fq_options_panel">
				<?php

				// Type.
				fq_wp_select(
					array(
						'id'      => 'discount_type',
						'label'   => __( 'Discount type', 'quote' ),
						'options' => fq_get_coupon_types(),
						'value'   => $coupon->get_discount_type(),
					)
				);

				// Amount.
				fq_wp_text_input(
					array(
						'id'                => 'coupon_amount',
						'label'             => __( 'Coupon amount', 'quote' ),
						'placeholder'       => 0,
						'description'       => __( 'Value of the coupon.', 'quote' ),
						'desc_tip'          => true,
						'type'              => 'number',
						'value'             => $coupon->get_amount(),
						'custom_attributes' => array(
							'step' => 1,
							'min'  => 0,
						),
					)
				);

				// Expiry date.
				$expiry_date = $coupon->get_date_expires() ? $coupon->get_date_expires()->date( 'Y-m-d' ) : '';
				fq_wp_text_input(
					array(
						'id'                => 'expiry_date',
						'value'             => esc_attr( $expiry_date ),
						'label'             => __( 'Coupon expiry date', 'quote' ),
						'placeholder'       => 'YYYY-MM-DD',
						'description'       => __( 'Coupon is valid before this date only.', 'quote' ),
						'desc_tip'          => true,
						'class'             => 'date-picker',
						'custom_attributes' => array(
							'pattern' => '[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])',
						),
					)
				);

				?>
			</div>
			<div id="usage_restriction_coupon_data" class="panel fq_options_panel">
				<div class="options_group">
					<?php

					// Minimum spend.
					fq_wp_text_input(
						array(
							'id'                => 'minimum_amount',
							'label'             => __( 'Minimum spend', 'quote' ),
							'placeholder'       => __( 'No minimum', 'quote' ),
							'description'       => __( 'This field allows you to set the minimum spend (subtotal) allowed to use the coupon.', 'quote' ),
							'desc_tip'          => true,
							'type'              => 'number',
							'value'             => $coupon->get_minimum_amount(),
							'custom_attributes' => array(
								'step' => 1,
								'min'  => 0,
							),
						)
					);

					// Maximum spend.
					fq_wp_text_input(
						array(
							'id'                => 'maximum_amount',
							'label'             => __( 'Maximum spend', 'quote' ),
							'placeholder'       => __( 'No maximum', 'quote' ),
							'description'       => __( 'This field allows you to set the maximum spend (subtotal) allowed when using the coupon.', 'quote' ),
							'desc_tip'          => true,
							'type'              => 'number',
							'value'             => $coupon->get_maximum_amount(),
							'custom_attributes' => array(
								'step' => 1,
								'min'  => 0,
							),
						)
					);

					?>
				</div>
			</div>
		</div>
		<div id="usage_limit_coupon_data" class="panel fq_options_panel">
			<div class="options_group">
				<?php
				// Usage limit per coupons.
				fq_wp_text_input(
					array(
						'id'                => 'usage_limit',
						'label'             => __( 'Usage limit per coupon', 'quote' ),
						'placeholder'       => esc_attr__( 'Unlimited usage', 'quote' ),
						'description'       => __( 'How many times this coupon can be used before it is void.', 'quote' ),
						'type'              => 'number',
						'desc_tip'          => true,
						'class'             => 'short',
						'custom_attributes' => array(
							'step' => 1,
							'min'  => 0,
						),
						'value'             => $coupon->get_usage_limit() ? $coupon->get_usage_limit() : '',
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
	 * @param WP_Post $post
	 */
	public static function save( $post_id, $post ) {
		// Check for dupe coupons.
		$coupon_code  = fq_format_coupon_code( $post->post_title );
		$id_from_code = fq_coupon_code_exists( $coupon_code, $post_id );

		if ( $id_from_code ) {
			self::add_notice(
				__( 'Coupon code already exists - customers will use the latest coupon with this code.', 'quote' ),
				'notice notice-error is-dismissible'
			);
		}

		// Check the nonce
		if ( empty( $_POST[ self::SAVE_NONCE ] ) || ! wp_verify_nonce( $_POST[ self::SAVE_NONCE ], self::SAVE_ACTION ) ) {
			return;
		}

		$coupon = new FQ_Coupon( $post_id );
		$coupon->set_props(
			array(
				'code'                 => $post->post_title,
				'discount_type'        => fq_clean( $_POST['discount_type'] ),
				'amount'               => fq_format_decimal( $_POST['coupon_amount'] ),
				'date_expires'         => fq_clean( $_POST['expiry_date'] ),
				'usage_limit'          => absint( $_POST['usage_limit'] ),
				'usage_limit_per_user' => absint( $_POST['usage_limit_per_user'] ),
				'minimum_amount'       => fq_format_decimal( $_POST['minimum_amount'] ),
				'maximum_amount'       => fq_format_decimal( $_POST['maximum_amount'] ),
			)
		);
		$coupon->save();
	}
}
