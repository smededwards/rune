/**
 * Apply a preview attribute to native editor canvases, including replaced iframes.
 * No block attributes, saved content or surrounding admin colours are changed.
 *
 * @param {Document} rootDocument The editor interface document.
 * @return {Object} Mode updater and cleanup callback.
 */
export function createCanvasController( rootDocument ) {
	const attribute = 'data-rune-canvas-mode';
	const documents = new Map();
	const frames = new Map();
	const wrappers = new Map();
	const Observer = rootDocument.defaultView.MutationObserver;
	let mode = 'light';
	let disposed = false;
	let scheduled = false;

	/**
	 * Apply the active mode to canvas wrappers in a document.
	 *
	 * @param {Document} doc Editor document to update.
	 * @return {void}
	 */
	function apply( doc ) {
		doc.querySelectorAll( '.editor-styles-wrapper' ).forEach(
			( wrapper ) => {
				if ( ! wrappers.has( wrapper ) ) {
					wrappers.set( wrapper, wrapper.getAttribute( attribute ) );
				}
				if ( wrapper.getAttribute( attribute ) !== mode ) {
					wrapper.setAttribute( attribute, mode );
				}
			}
		);
	}

	/**
	 * Observe a document for editor canvas changes.
	 *
	 * @param {Document} doc Editor document to observe.
	 * @return {void}
	 */
	function observe( doc ) {
		if ( ! doc?.documentElement || documents.has( doc ) ) {
			return;
		}
		const observer = new Observer( schedule );
		observer.observe( doc.documentElement, {
			childList: true,
			subtree: true,
		} );
		documents.set( doc, observer );
	}

	/**
	 * Reconcile active editor documents, frames and canvas wrappers.
	 *
	 * @return {void}
	 */
	function refresh() {
		if ( disposed ) {
			return;
		}
		scheduled = false;
		observe( rootDocument );
		const activeDocuments = new Set( [ rootDocument ] );
		rootDocument
			.querySelectorAll( 'iframe[name="editor-canvas"]' )
			.forEach( ( frame ) => {
				if ( ! frames.has( frame ) ) {
					frame.addEventListener( 'load', schedule );
					frames.set( frame, schedule );
				}
				try {
					if ( frame.contentDocument?.documentElement ) {
						observe( frame.contentDocument );
						activeDocuments.add( frame.contentDocument );
					}
				} catch {
					// Never inspect an inaccessible or cross-origin frame.
				}
			} );
		for ( const [ doc, observer ] of documents ) {
			if ( ! activeDocuments.has( doc ) ) {
				observer.disconnect();
				documents.delete( doc );
			} else {
				apply( doc );
			}
		}
		for ( const [ frame, listener ] of frames ) {
			if ( ! frame.isConnected ) {
				frame.removeEventListener( 'load', listener );
				frames.delete( frame );
			}
		}
		for ( const wrapper of wrappers.keys() ) {
			if (
				! wrapper.isConnected ||
				! activeDocuments.has( wrapper.ownerDocument )
			) {
				wrappers.delete( wrapper );
			}
		}
	}

	/**
	 * Schedule a single refresh after DOM mutations settle.
	 *
	 * @return {void}
	 */
	function schedule() {
		if ( ! scheduled && ! disposed ) {
			scheduled = true;
			Promise.resolve().then( refresh );
		}
	}

	refresh();
	return {
		/**
		 * Apply a colour mode to every active editor canvas.
		 *
		 * @param {string} nextMode Requested mode; only `dark` selects dark mode.
		 * @return {void}
		 */
		setMode( nextMode ) {
			mode = nextMode === 'dark' ? 'dark' : 'light';
			refresh();
		},
		/**
		 * Disconnect observers and restore canvas attributes.
		 *
		 * @return {void}
		 */
		dispose() {
			disposed = true;
			for ( const observer of documents.values() ) {
				observer.disconnect();
			}
			for ( const [ frame, listener ] of frames ) {
				frame.removeEventListener( 'load', listener );
			}
			for ( const [ wrapper, previous ] of wrappers ) {
				if ( previous === null ) {
					wrapper.removeAttribute( attribute );
				} else {
					wrapper.setAttribute( attribute, previous );
				}
			}
			documents.clear();
			frames.clear();
			wrappers.clear();
		},
	};
}
