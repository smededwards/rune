// WordPress dependencies.
import { registerPlugin } from '@wordpress/plugins';

// Internal dependencies.
import AugurControl from './js/augur/control';

registerPlugin( 'rune-augur', { render: AugurControl } );
