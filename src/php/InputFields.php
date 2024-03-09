<?php
/**
 * InputFields class file.
 *
 * @package notification-system
 */

namespace KAGG\NotificationSystem;

/**
 * Class InputFields
 */
class InputFields {

	/**
	 * Output a text input box.
	 *
	 * @param array $field Text input field.
	 */
	public static function text_input( $field ) {
		global $post;

		$field = wp_parse_args(
			$field,
			[
				'placeholder'   => '',
				'class'         => 'short',
				'style'         => '',
				'wrapper_class' => '',
				'value'         => get_post_meta( $post->ID, $field['id'], true ),
				'name'          => $field['id'],
				'type'          => 'text',
				'desc_tip'      => false,
			]
		);

		// Custom attribute handling.
		$custom_attributes = self::esc_custom_attributes( $field );

		$help_tip    = ! empty( $field['description'] ) && false !== $field['desc_tip'] ? $field['description'] : '';
		$description = ! empty( $field['description'] ) && false === $field['desc_tip'] ? $field['description'] : '';

		echo '<p class="form-field ' . esc_attr( $field['id'] ) . '_field ' . esc_attr( $field['wrapper_class'] ) . '">
		<label for="' . esc_attr( $field['id'] ) . '">' . wp_kses_post( $field['label'] ) . '</label>';

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		self::show_help_tip( $help_tip );

		echo '<input type="' . esc_attr( $field['type'] ) . '" class="' . esc_attr( $field['class'] ) . '"';
		echo ' style="' . esc_attr( $field['style'] ) . '" name="' . esc_attr( $field['name'] ) . '"';
		echo ' id="' . esc_attr( $field['id'] ) . '" value="' . esc_attr( $field['value'] ) . '"';
		echo ' placeholder="' . esc_attr( $field['placeholder'] ) . '"';
		// Output $custom_attributes without esc_attr(), as they are already well-formed.
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo ' ' . implode( ' ', $custom_attributes ) . ' /> ';

		self::show_description( $description );

		echo '</p>';
	}

	/**
	 * Return a help tip.
	 *
	 * @param string $tip        Help tip text.
	 * @param bool   $allow_html Allow sanitized HTML if true or escape.
	 *
	 * @return string
	 * @noinspection PhpSameParameterValueInspection PhpSameParameterValueInspection.
	 */
	private static function get_help_tip( $tip, $allow_html = false ): string {
		if ( ! $tip ) {
			return '';
		}

		if ( $allow_html ) {
			$tip = self::sanitize_tooltip( $tip );
		} else {
			$tip = esc_attr( $tip );
		}

		return '<span class="fq-help-tip" data-tip="' . $tip . '"></span>';
	}

	/**
	 * Display a help tip.
	 *
	 * @param string $tip        Help tip text.
	 * @param bool   $allow_html Allow sanitized HTML if true or escape.
	 *
	 * @return void
	 * @noinspection PhpSameParameterValueInspection PhpSameParameterValueInspection.
	 */
	private static function show_help_tip( $tip, $allow_html = false ) {
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo self::get_help_tip( $tip, $allow_html );
	}

	/**
	 * Get description html.
	 *
	 * @param string $description Description.
	 *
	 * @return string
	 */
	private static function get_description_html( $description ): string {
		if ( ! $description ) {
			return '';
		}

		return '<span class="description">' . wp_kses_post( $description ) . '</span>';
	}

	/**
	 * Show description.
	 *
	 * @param string $description Description.
	 *
	 * @return void
	 */
	private static function show_description( $description ) {
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo self::get_description_html( $description );
	}

	/**
	 * Sanitize a string destined to be a tooltip.
	 *
	 * Tooltips are encoded with htmlspecialchars() to prevent XSS.
	 * Should not be used in conjunction with esc_attr().
	 *
	 * @param string $tip Data to sanitize.
	 *
	 * @return string
	 */
	private static function sanitize_tooltip( $tip ): string {
		return htmlspecialchars(
			wp_kses(
				html_entity_decode( $tip ),
				[
					'br'     => [],
					'em'     => [],
					'strong' => [],
					'small'  => [],
					'span'   => [],
					'ul'     => [],
					'li'     => [],
					'ol'     => [],
					'p'      => [],
				]
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
			[
				'class'             => 'select short',
				'style'             => '',
				'wrapper_class'     => '',
				'value'             => get_post_meta( $the_post_id, $field['id'], true ),
				'name'              => $field['id'],
				'desc_tip'          => false,
				'custom_attributes' => [],
			]
		);

		$wrapper_attributes = [
			'class' => $field['wrapper_class'] . " form-field {$field['id']}_field",
		];

		$label_attributes = [
			'for' => $field['id'],
		];

		$field_attributes          = (array) $field['custom_attributes'];
		$field_attributes['style'] = $field['style'];
		$field_attributes['id']    = $field['id'];
		$field_attributes['name']  = $field['name'];
		$field_attributes['class'] = $field['class'];

		$help_tip    = ! empty( $field['description'] ) && false !== $field['desc_tip'] ? $field['description'] : '';
		$description = ! empty( $field['description'] ) && false === $field['desc_tip'] ? $field['description'] : '';
		// Output $custom_attributes without esc_attr(), as they are already well-formed.
		// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
		?>
		<p <?php echo self::implode_html_attributes( $wrapper_attributes ); ?>>
			<label <?php echo self::implode_html_attributes( $label_attributes ); ?>>
				<?php echo wp_kses_post( $field['label'] ); ?>
			</label>
			<?php self::show_help_tip( $help_tip ); ?>
			<select <?php echo self::implode_html_attributes( $field_attributes ); ?> title="">
				<?php
				foreach ( $field['options'] as $key => $value ) {
					echo '<option value="' . esc_attr( $key ) . '" ' . selected( $key, $field['value'] ) . '>';
					echo esc_html( $value ) . '</option>';
				}
				?>
			</select>
			<?php self::show_description( $description ); ?>
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
	private static function implode_html_attributes( $raw_attributes ): string {
		$attributes = [];
		foreach ( $raw_attributes as $name => $value ) {
			$attributes[] = esc_attr( $name ) . '="' . esc_attr( $value ) . '"';
		}

		return implode( ' ', $attributes );
	}

	/**
	 * Escape custom attributes.
	 *
	 * @param array $field Field.
	 *
	 * @return array
	 */
	private static function esc_custom_attributes( $field ): array {
		$custom_attributes = [];

		if ( ! isset( $field['custom_attributes'] ) || ! is_array( $field['custom_attributes'] ) ) {
			return $custom_attributes;
		}

		foreach ( $field['custom_attributes'] as $attribute => $value ) {
			$custom_attributes[] = esc_attr( $attribute ) . '="' . esc_attr( $value ) . '"';
		}

		return $custom_attributes;
	}

	/**
	 * Return the html selected attribute if stringified $value is found in an array of stringified $options
	 * or if stringified $value is the same as scalar stringified $options.
	 *
	 * @param string|int       $value   Value to find within options.
	 * @param string|int|array $options Options to go through when looking for value.
	 *
	 * @return string
	 */
	public function selected( $value, $options ): string {
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
	 * @param string|array $str Data to sanitize.
	 *
	 * @return string|array
	 */
	public static function clean( $str ) {
		if ( is_array( $str ) ) {
			return array_map( 'self::clean', $str );
		}

		return is_scalar( $str ) ? sanitize_text_field( $str ) : $str;
	}
}
