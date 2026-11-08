const assert = require( 'node:assert/strict' );
const fs = require( 'node:fs' );
const path = require( 'node:path' );
const test = require( 'node:test' );
const vm = require( 'node:vm' );

const source = fs.readFileSync(
	path.join( __dirname, '../src/js/code-copy/index.js' ),
	'utf8'
);
const helpers = vm.runInNewContext(
	source
		.replace( /export async function /g, 'async function ' )
		.replace( /export function /g, 'function ' ) +
		'\n({ copyText, getCodeText });'
);

test( 'reads code text and uses the Clipboard API', async () => {
	assert.equal(
		helpers.getCodeText( {
			querySelector: () => ( { textContent: 'const answer = 42;' } ),
		} ),
		'const answer = 42;'
	);

	let copied = '';
	await helpers.copyText(
		'const answer = 42;',
		{
			writeText: async ( value ) => {
				copied = value;
			},
		},
		null
	);
	assert.equal( copied, 'const answer = 42;' );
} );

test( 'falls back to a temporary textarea when the Clipboard API is unavailable', async () => {
	let removed = false;
	let command = '';
	const textarea = {
		style: {},
		setAttribute() {},
		select() {},
		remove() {
			removed = true;
		},
	};
	const doc = {
		body: { appendChild() {} },
		createElement: () => textarea,
		execCommand: ( value ) => {
			command = value;
			return true;
		},
	};

	await helpers.copyText( 'fallback', null, doc );
	assert.equal( command, 'copy' );
	assert.equal( removed, true );
} );
