/**
 * Read editable text from a supported text block.
 *
 * @param {Object|null} block Selected block data.
 * @return {{clientId: string, content: string}|null} Text block details.
 */
export function getTextBlock( block ) {
	// shortcut: block-level editing only; add inline range support when required.
	if (
		! block ||
		! [ 'core/paragraph', 'core/heading' ].includes( block.name ) ||
		typeof block.attributes?.content !== 'string' ||
		! block.attributes.content.trim()
	) {
		return null;
	}

	return {
		clientId: block.clientId,
		content: block.attributes.content,
	};
}
