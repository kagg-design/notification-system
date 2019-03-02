<?php
/**
 * KAGG_Input_Fields class file.
 *
 * @package notification-system
 */

/**
 * Class KAGG_Input_Fields
 */
class KAGG_Input_Fields {

	/**
	 * Output a text input box.
	 *
	 * @param array $field Text input field.
	 */
	public static function text_input( $field ) {
		global $post;

		$the_post_id            = $post->ID;
		$field['placeholder']   = isset( $field['placeholder'] ) ? $field['placeholder'] : '';
		$field['class']         = isset( $field['class'] ) ? $field['class'] : 'short';
		$field['style']         = isset( $field['style'] ) ? $field['style'] : '';
		$field['wrapper_class'] = isset( $field['wrapper_class'] ) ? $field['wrapper_class'] : '';
		$field['value']         = isset( $field['value'] ) ?
			$field['value'] :
			get_post_meta( $the_post_id, $field['id'], true );
		$field['name']          = isset( $field['name'] ) ? $field['name'] : $field['id'];
		$field['type']          = isset( $field['type'] ) ? $field['type'] : 'text';
		$field['desc_tip']      = isset( $field['desc_tip'] ) ? $field['desc_tip'] : false;

		// Custom attribute handling.
		$custom_attributes = array();

		if ( ! empty( $field['custom_attributes'] ) && is_array( $field['custom_attributes'] ) ) {
			foreach ( $field['custom_attributes'] as $attribute => $value ) {
				$custom_attributes[] = esc_attr( $attribute ) . '="' . esc_attr( $value ) . '"';
			}
		}

		echo '<p class="form-field ' . esc_attr( $field['id'] ) . '_field ' . esc_attr( $field['wrapper_class'] ) . '">
		<label for="' . esc_attr( $field['id'] ) . '">' . wp_kses_post( $field['label'] ) . '</label>';

		if ( ! empty( $field['description'] ) && false !== $field['desc_tip'] ) {
			// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
			echo self::help_tip( $field['description'] );
			// phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped
		}

		// Output $custom_attributes without esc_attr(), as they are already well formed.
		// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '<input type="' . esc_attr( $field['type'] ) . '" class="' . esc_attr( $field['class'] ) . '"';
		echo ' style="' . esc_attr( $field['style'] ) . '" name="' . esc_attr( $field['name'] ) . '"';
		echo ' id="' . esc_attr( $field['id'] ) . '" value="' . esc_attr( $field['value'] ) . '"';
		echo ' placeholder="' . esc_attr( $field['placeholder'] ) . '"';
		echo ' ' . implode( ' ', $custom_attributes ) . ' /> ';
		// phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped

		if ( ! empty( $field['description'] ) && false === $field['desc_tip'] ) {
			echo '<span class="description">' . wp_kses_post( $field['description'] ) . '</span>';
		}

		echo '</p>';
	}

	/**
	 * Display a help tip.
	 *
	 * @param  string $tip        Help tip text.
	 * @param  bool   $allow_html Allow sanitized HTML if true or escape.
	 *
	 * @return string
	 */
	private static function help_tip( $tip, $allow_html = false ) {
		if ( $allow_html ) {
			$tip = self::sanitize_tooltip( $tip );
		} else {
			$tip = esc_attr( $tip );
		}

		return '<span class="fq-help-tip" data-tip="' . $tip . '"></span>';
	}

	/**
	 * Sanitize a string destined to be a tooltip.
	 *
	 * Tooltips are encoded with htmlspecialchars to prevent XSS. Should not be used in conjunction with esc_attr().
	 *
	 * @param  string $var Data to sanitize.
	 *
	 * @return string
	 */
	private static function sanitize_tooltip( $var ) {
		return htmlspecialchars(
			wp_kses(
				html_entity_decode( $var ),
				array(
					'br'     => array(),
					'em'     => array(),
					'strong' => array(),
					'small'  => array(),
					'span'   => array(),
					'ul'     => array(),
					'li'     => array(),
					'ol'     => array(),
					'p'      => array(),
				)
			)
		);
	}

	/**
	 * Output a select input box.
	 *
	 * @param array $field Data about the field to render.
	 */
	public static function select( $field ) {
		global $post;

		$the_post_id = $post->ID;
		$field       = wp_parse_args(
			$field,
			array(
				'class'             => 'select short',
				'style'             => '',
				'wrapper_class'     => '',
				'value'             => get_post_meta( $the_post_id, $field['id'], true ),
				'name'              => $field['id'],
				'desc_tip'          => false,
				'custom_attributes' => array(),
			)
		);

		$wrapper_attributes = array(
			'class' => $field['wrapper_class'] . " form-field {$field['id']}_field",
		);

		$label_attributes = array(
			'for' => $field['id'],
		);

		$field_attributes          = (array) $field['custom_attributes'];
		$field_attributes['style'] = $field['style'];
		$field_attributes['id']    = $field['id'];
		$field_attributes['name']  = $field['name'];
		$field_attributes['class'] = $field['class'];

		$tooltip     = ! empty( $field['description'] ) && false !== $field['desc_tip'] ? $field['description'] : '';
		$description = ! empty( $field['description'] ) && false === $field['desc_tip'] ? $field['description'] : '';
		?>
		<p
			<?php
			// Output $custom_attributes without esc_attr(), as they are already well formed.
			// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
			echo ' ' . self::implode_html_attributes( $wrapper_attributes );
			// phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped
			?>
		>
			<label
				<?php
				// Output $custom_attributes without esc_attr(), as they are already well formed.
				// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
				echo ' ' . self::implode_html_attributes( $label_attributes );
				// phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped
				?>
			>
				<?php echo wp_kses_post( $field['label'] ); ?>
			</label>
			<?php if ( $tooltip ) : ?>
				<?php
				// Output $custom_attributes without esc_attr(), as they are already well formed.
				// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
				echo self::help_tip( $tooltip );
				// phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped
				?>
			<?php endif; ?>
			<select
				<?php
				// Output $custom_attributes without esc_attr(), as they are already well formed.
				// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
				echo ' ' . self::implode_html_attributes( $field_attributes );
				// phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped
				?>
					title="">
				<?php
				foreach ( $field['options'] as $key => $value ) {
					echo '<option value="' . esc_attr( $key ) . '" ' . selected( $key, $field['value'] ) . '>';
					echo esc_html( $value ) . '</option>';
				}
				?>
			</select>
			<?php if ( $description ) : ?>
				<span class="description"><?php echo wp_kses_post( $description ); ?></span>
			<?php endif; ?>
		</p>
		<?php
	}

	/**
	 * Implode and escape HTML attributes for output.
	 *
	 * @param array $raw_attributes Attribute name value pairs.
	 *
	 * @return string
	 */
	private static function implode_html_attributes( $raw_attributes ) {
		$attributes = array();
		foreach ( $raw_attributes as $name => $value ) {
			$attributes[] = esc_attr( $name ) . '="' . esc_attr( $value ) . '"';
		}

		return implode( ' ', $attributes );
	}

	/**
	 * Return the html selected attribute if stringified $value is found in array of stringified $options
	 * or if stringified $value is the same as scalar stringified $options.
	 *
	 * @param string|int       $value   Value to find within options.
	 * @param string|int|array $options Options to go through when looking for value.
	 *
	 * @return string
	 */
	public function selected( $value, $options ) {
		if ( is_array( $options ) ) {
			$options = array_map( 'strval', $options );

			return selected( in_array( (string) $value, $options, true ), true, false );
		}

		return selected( $value, $options, false );
	}

	/**
	 * Clean variables using sanitize_text_field. Arrays are cleaned recursively.
	 * Non-scalar values are ignored.
	 *
	 * @param string|array $var Data to sanitize.
	 *
	 * @return string|array
	 */
	public static function clean( $var ) {
		if ( is_array( $var ) ) {
			return array_map( 'self::clean', $var );
		} else {
			return is_scalar( $var ) ? sanitize_text_field( $var ) : $var;
		}
	}
}
