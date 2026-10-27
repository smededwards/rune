import { registerPlugin } from '@wordpress/plugins';
import ColourModeControl from './js/editor-colour-mode/control';
import './scss/editor-colour-mode.scss';

registerPlugin( 'rune-editor-colour-mode', { render: ColourModeControl } );
