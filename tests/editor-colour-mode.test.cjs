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

/**
 * Create a fake DOM and observer set for canvas controller checks.
 *
 * @return {Object} Document, node factories and recorded observers.
 */
function harness() {
	const observers = [];
	// Provide a controllable MutationObserver stub for tests.
	class Observer {
		/**
		 * Store the callback used to report DOM changes.
		 *
		 * @param {(records: Object[], observer: Object) => void} callback Callback to invoke when a mutation occurs.
		 * @return {void}
		 */
		constructor( callback ) {
			this.callback = callback;
			observers.push( this );
		}
		/**
		 * Mark the observation interface used by the controller.
		 *
		 * @return {void}
		 */
		observe() {}
		/**
		 * Record that the observer has been stopped.
		 *
		 * @return {void}
		 */
		disconnect() {
			this.disconnected = true;
		}
	}
	/**
	 * Create a fake document with a controllable editor canvas.
	 *
	 * @return {Object} Minimal document stub.
	 */
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
	/**
	 * Add a fake editor wrapper to a document.
	 *
	 * @param {Object}      doc      Document stub to own the wrapper.
	 * @param {string|null} previous Previously saved canvas mode.
	 * @return {Object} Editor wrapper stub.
	 */
	function wrapper( doc, previous = null ) {
		const attrs = new Map();
		if ( previous !== null ) {
			attrs.set( 'data-rune-canvas-mode', previous );
		}
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
	/**
	 * Add a fake editor iframe to a document.
	 *
	 * @param {Object} doc Document to expose through the frame.
	 * @return {Object} Frame stub with controllable load listeners.
	 */
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

/**
 * Read the current canvas mode from a test wrapper.
 *
 * @param {Object} node Editor wrapper stub.
 * @return {string|null} Current preview mode.
 */
const mode = ( node ) => node.getAttribute( 'data-rune-canvas-mode' );

/**
 * Flush the microtask scheduled by a mutation observer callback.
 *
 * @return {Promise<void>} Promise resolved after the scheduled refresh.
 */
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
