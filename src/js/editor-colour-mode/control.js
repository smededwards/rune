import { Button, Fill } from '@wordpress/components';
import { useMediaQuery } from '@wordpress/compose';
import { useDispatch, useSelect } from '@wordpress/data';
import { useEffect, useRef } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { store as preferencesStore } from '@wordpress/preferences';
import { createCanvasController } from './canvas';

// WordPress 7.1 shares the core scope across post/site editors; widgets has its own.
// Older editor scopes are retained here as a small, isolated SlotFill adapter.
const toolbarSlots = [
	'PinnedItems/core',
	'PinnedItems/core/edit-post',
	'PinnedItems/core/edit-site',
	'PinnedItems/core/edit-widgets',
];

export default function ColourModeControl() {
	const storedMode = useSelect(
		( select ) =>
			select( preferencesStore ).get( 'rune', 'canvasColourMode' ),
		[]
	);
	const { set } = useDispatch( preferencesStore );
	const systemDark = useMediaQuery( '(prefers-color-scheme: dark)' );
	const dark =
		storedMode === 'dark' ||
		( storedMode !== 'light' && storedMode !== 'dark' && systemDark );
	const controller = useRef( null );

	useEffect( () => {
		controller.current = createCanvasController( document );
		return () => {
			controller.current.dispose();
			controller.current = null;
		};
	}, [] );

	useEffect( () => {
		controller.current?.setMode( dark ? 'dark' : 'light' );
	}, [ dark ] );

	return toolbarSlots.map( ( name ) => (
		<Fill key={ name } name={ name }>
			<Button
				className="rune-canvas-toggle"
				icon="lightbulb"
				size="compact"
				label={ __( 'Dark editor canvas', 'rune' ) }
				aria-pressed={ Boolean( dark ) }
				isPressed={ Boolean( dark ) }
				onClick={ () =>
					set( 'rune', 'canvasColourMode', dark ? 'light' : 'dark' )
				}
			/>
		</Fill>
	) );
}
