const assert = require( 'node:assert/strict' );
const fs = require( 'node:fs' );
const path = require( 'node:path' );
const test = require( 'node:test' );
const vm = require( 'node:vm' );

const source = fs.readFileSync(
	path.join( __dirname, '../src/js/editor-colour-mode/canvas.js' ),
	'utf8'
);
const createController = vm.runInNewContext(
	source.replace( 'export function', 'function' ) +
		'\ncreateCanvasController;'
);

function harness() {
	const observers = [];
	class Observer {
		constructor( callback ) {
			this.callback = callback;
			observers.push( this );
		}
		observe() {}
		disconnect() {
			this.disconnected = true;
		}
	}
	function document() {
		return {
			documentElement: {},
			defaultView: { MutationObserver: Observer },
			wrappers: [],
			frames: [],
			querySelectorAll( selector ) {
				return selector === '.editor-styles-wrapper'
					? this.wrappers
					: this.frames;
			},
		};
	}
	function wrapper( doc, previous = null ) {
		const attrs = new Map();
		if ( previous !== null ) attrs.set( 'data-rune-canvas-mode', previous );
		const node = {
			ownerDocument: doc,
			isConnected: true,
			getAttribute: ( key ) => attrs.get( key ) ?? null,
			setAttribute: ( key, value ) => attrs.set( key, value ),
			removeAttribute: ( key ) => attrs.delete( key ),
		};
		doc.wrappers.push( node );
		return node;
	}
	function frame( doc ) {
		return {
			contentDocument: doc,
			isConnected: true,
			listeners: new Map(),
			addEventListener( name, callback ) {
				this.listeners.set( name, callback );
			},
			removeEventListener( name ) {
				this.listeners.delete( name );
			},
		};
	}
	const root = document();
	return { root, document, wrapper, frame, observers };
}
const mode = ( node ) => node.getAttribute( 'data-rune-canvas-mode' );
const flush = () => new Promise( ( resolve ) => setImmediate( resolve ) );

test( 'changes the canvas immediately without colouring the admin document', () => {
	const { root, wrapper } = harness();
	const canvas = wrapper( root );
	const controller = createController( root );
	controller.setMode( 'dark' );
	assert.equal( mode( canvas ), 'dark' );
	assert.deepEqual( root.documentElement, {} );
	controller.setMode( 'light' );
	assert.equal( mode( canvas ), 'light' );
	controller.dispose();
	assert.equal( mode( canvas ), null );
} );

test( 'applies the current mode to an iframe that mounts after initialisation', async () => {
	const h = harness();
	const controller = createController( h.root );
	controller.setMode( 'dark' );
	const doc = h.document();
	const canvas = h.wrapper( doc );
	h.root.frames.push( h.frame( doc ) );
	h.observers[ 0 ].callback();
	await flush();
	assert.equal( mode( canvas ), 'dark' );
	controller.dispose();
} );

test( 'handles iframe navigation and disconnects observers for the old document', async () => {
	const h = harness();
	const oldDoc = h.document();
	const frame = h.frame( oldDoc );
	h.root.frames.push( frame );
	const controller = createController( h.root );
	controller.setMode( 'dark' );
	const nextDoc = h.document();
	const canvas = h.wrapper( nextDoc );
	frame.contentDocument = nextDoc;
	frame.listeners.get( 'load' )();
	await flush();
	assert.equal( mode( canvas ), 'dark' );
	assert.equal( h.observers[ 1 ].disconnected, true );
	controller.dispose();
} );

test( 'handles an inaccessible frame without preventing other canvases updating', () => {
	const h = harness();
	const frame = h.frame( null );
	Object.defineProperty( frame, 'contentDocument', {
		get() {
			throw new Error( 'Cross origin' );
		},
	} );
	h.root.frames.push( frame );
	const canvas = h.wrapper( h.root );
	const controller = createController( h.root );
	controller.setMode( 'dark' );
	assert.equal( mode( canvas ), 'dark' );
	controller.dispose();
} );

test( 'detects a canvas mounted inside an existing iframe', async () => {
	const h = harness();
	const doc = h.document();
	h.root.frames.push( h.frame( doc ) );
	const controller = createController( h.root );
	controller.setMode( 'dark' );
	const canvas = h.wrapper( doc );
	h.observers[ 1 ].callback();
	await flush();
	assert.equal( mode( canvas ), 'dark' );
	controller.dispose();
} );

test( 'cleans up listeners and queued work and restores a prior preview attribute', async () => {
	const h = harness();
	const canvas = h.wrapper( h.root, 'light' );
	const frame = h.frame( h.document() );
	h.root.frames.push( frame );
	const controller = createController( h.root );
	controller.setMode( 'dark' );
	h.observers[ 0 ].callback();
	controller.dispose();
	await flush();
	assert.equal( mode( canvas ), 'light' );
	assert.equal( frame.listeners.size, 0 );
	assert.equal(
		h.observers.every( ( observer ) => observer.disconnected ),
		true
	);
} );
