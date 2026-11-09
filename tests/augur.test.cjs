const assert = require( 'node:assert/strict' );
const fs = require( 'node:fs' );
const path = require( 'node:path' );
const test = require( 'node:test' );
const vm = require( 'node:vm' );

const source = fs.readFileSync(
	path.join( __dirname, '../src/js/augur/selection.js' ),
	'utf8'
);
const getTextBlock = vm.runInNewContext(
	source.replace( 'export function', 'function' ) + '\ngetTextBlock;'
);

test( 'accepts paragraph and heading blocks only', () => {
	const textBlock = getTextBlock( {
		clientId: 'paragraph-1',
		name: 'core/paragraph',
		attributes: { content: 'A paragraph.' },
	} );
	assert.equal( textBlock.clientId, 'paragraph-1' );
	assert.equal( textBlock.content, 'A paragraph.' );
	assert.equal(
		getTextBlock( {
			clientId: 'image-1',
			name: 'core/image',
			attributes: { content: 'Not editable text.' },
		} ),
		null
	);
} );
