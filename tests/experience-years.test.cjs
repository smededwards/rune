const assert = require( 'node:assert/strict' );
const fs = require( 'node:fs' );
const path = require( 'node:path' );
const test = require( 'node:test' );
const vm = require( 'node:vm' );

const source = fs.readFileSync(
	path.join( __dirname, '../blocks/experience-years/years.js' ),
	'utf8'
);
const helpers = vm.runInNewContext(
	source
		.replace( "import { __ } from '@wordpress/i18n';", '' )
		.replace( /export function /g, 'function ' ) +
		'\n({ getCompletedYears, formatPreview });',
	{
		__( value ) {
			return value;
		},
	}
);

test( 'calculates completed years and handles future dates', () => {
	const originalDate = global.Date;
	global.Date = class extends originalDate {
		constructor( value ) {
			super( value ?? '2026-11-04T00:00:00' );
		}
	};

	assert.equal( helpers.getCompletedYears( '2013-01-15' ), 13 );
	assert.equal( helpers.getCompletedYears( '2030-01-15' ), 0 );
	global.Date = originalDate;
} );
