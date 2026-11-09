/**
 * Return the text stored in a native Code block.
 *
 * @param {Element} root Code block wrapper.
 * @return {string} Code text.
 */
export function getCodeText( root ) {
	return root.querySelector( 'code' )?.textContent ?? '';
}

/**
 * Copy text with the Clipboard API and a small legacy fallback.
 *
 * @param {string}      text      Text to copy.
 * @param {Object|null} clipboard Clipboard API implementation.
 * @param {Document}    doc       Document used by the fallback.
 * @return {Promise<void>} Resolves after the text is copied.
 */
export async function copyText(
	text,
	clipboard = globalThis.navigator?.clipboard,
	doc = globalThis.document
) {
	if ( clipboard?.writeText ) {
		await clipboard.writeText( text );
		return;
	}

	if ( ! doc?.body || ! doc.createElement || ! doc.execCommand ) {
		throw new Error( 'Clipboard access is unavailable.' );
	}

	const textarea = doc.createElement( 'textarea' );
	textarea.value = text;
	textarea.setAttribute( 'readonly', '' );
	textarea.style.position = 'fixed';
	textarea.style.opacity = '0';
	doc.body.appendChild( textarea );

	try {
		textarea.select();
		if ( ! doc.execCommand( 'copy' ) ) {
			throw new Error( 'Copy command failed.' );
		}
	} finally {
		textarea.remove();
	}
}

/**
 * Handle copy requests for all Code blocks on a page.
 *
 * @param {Document} doc Document containing the Code blocks.
 * @return {void}
 */
export function enhanceCodeBlocks( doc = globalThis.document ) {
	if ( ! doc ) {
		return;
	}

	doc.addEventListener( 'click', async ( event ) => {
		const button = event.target.closest( '[data-rune-code-copy]' );
		if ( ! button ) {
			return;
		}

		const wrapper = button.closest( '.rune-code-copy' );
		const status = wrapper?.querySelector( '.rune-code-copy__status' );
		if ( ! wrapper || ! status ) {
			return;
		}

		try {
			await copyText( getCodeText( wrapper ) );
			button.textContent = button.dataset.copiedLabel;
			status.textContent = button.dataset.copiedLabel;
			globalThis.setTimeout( () => {
				button.textContent = button.dataset.copyLabel;
			}, 2000 );
		} catch {
			status.textContent = button.dataset.errorLabel;
		}
	} );
}
