// WordPress dependencies.
import { __ } from '@wordpress/i18n';

/**
 * Calculate completed years between a start date and today.
 *
 * @param {string} startDate ISO date value.
 * @return {number} Completed years.
 */
export function getCompletedYears( startDate ) {
	const start = new Date( `${ startDate }T00:00:00` );
	const today = new Date();

	if ( Number.isNaN( start.getTime() ) || start > today ) {
		return 0;
	}

	let years = today.getFullYear() - start.getFullYear();
	const anniversary = new Date(
		today.getFullYear(),
		start.getMonth(),
		start.getDate()
	);

	if ( today < anniversary ) {
		years -= 1;
	}

	return Math.max( 0, years );
}

/**
 * Format the preview value for the selected display style.
 *
 * @param {number} years Completed years.
 * @param {string} style Display style identifier.
 * @return {string} Formatted preview value.
 */
export function formatPreview( years, style ) {
	if ( 'plus' === style ) {
		return `${ years }+`;
	}

	const label = 1 === years ? __( 'year', 'rune' ) : __( 'years', 'rune' );

	return 'over' === style
		? `${ __( 'over', 'rune' ) } ${ years } ${ label }`
		: `${ years } ${ label }`;
}
