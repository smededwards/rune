<?php
/**
 * Dynamic experience years block.
 *
 * @package Rune
 */

namespace Rune\Blocks;

/**
 * Renders a date-based experience value inline with surrounding content.
 */
final class ExperienceYears {

	private const DEFAULT_START_DATE = '2013-01-15';

	/**
	 * Render the experience summary with the saved block settings.
	 *
	 * @param array $attributes Saved block attributes.
	 * @return string Rendered block markup.
	 */
	public function render( array $attributes ): string {
		$start_date = $this->parse_date( (string) ( $attributes['startDate'] ?? self::DEFAULT_START_DATE ) );
		if ( ! $start_date ) {
			$start_date = $this->parse_date( self::DEFAULT_START_DATE );
		}
		$today = current_datetime();
		$years = $start_date > $today ? 0 : $start_date->diff( $today )->y;
		$style = (string) ( $attributes['displayStyle'] ?? 'years' );
		$style = in_array( $style, array( 'years', 'plus', 'over' ), true ) ? $style : 'years';
		$value = $this->format_value( $years, $style );

		return sprintf(
			'<span %1$s><strong>%2$s</strong></span>',
			get_block_wrapper_attributes( array( 'class' => 'experience-years' ) ),
			esc_html( $value )
		);
	}

	/**
	 * Parse a saved ISO date in the WordPress timezone.
	 *
	 * @param string $date Saved date value.
	 * @return \DateTimeImmutable|null Parsed date or null when invalid.
	 */
	private function parse_date( string $date ): ?\DateTimeImmutable {
		$parsed = \DateTimeImmutable::createFromFormat( '!Y-m-d', $date, wp_timezone() );
		$errors = \DateTimeImmutable::getLastErrors();

		if ( false === $parsed || ( false !== $errors && ( $errors['warning_count'] || $errors['error_count'] ) ) ) {
			return null;
		}

		return $parsed;
	}

	/**
	 * Format the selected display style.
	 *
	 * @param int    $years Completed years.
	 * @param string $style Display style identifier.
	 * @return string Formatted experience value.
	 */
	private function format_value( int $years, string $style ): string {
		$year_label = _n( 'year', 'years', $years, 'rune' );

		if ( 'plus' === $style ) {
			return sprintf( '%d+', $years );
		}

		if ( 'over' === $style ) {
			// translators: 1: completed years, 2: singular or plural year label.
			return sprintf( __( 'over %1$d %2$s', 'rune' ), $years, $year_label );
		}

		return sprintf( '%d %s', $years, $year_label );
	}
}
