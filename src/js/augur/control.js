// WordPress dependencies.
import {
	Button,
	Fill,
	Modal,
	Notice,
	Spinner,
	TextareaControl,
} from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

// Internal dependencies.
import { toolbarSlots } from '../editor-slots';
import { getTextBlock } from './selection';

/**
 * Request a clarity revision from the Rune REST endpoint.
 *
 * @param {string} text   Selected block text.
 * @param {number} postId Current post ID.
 * @return {Promise<{text: string}>} Provider response.
 */
async function improveClarity( text, postId ) {
	const apiFetch = window.wp?.apiFetch;
	if ( 'function' !== typeof apiFetch ) {
		throw new Error( __( 'Augur is unavailable in this editor.', 'rune' ) );
	}

	return apiFetch( {
		path: 'rune/v1/augur/improve-clarity',
		method: 'POST',
		data: { text, postId },
	} );
}

/**
 * Render the Augur clarity action and review modal.
 *
 * @return {Object[]} Toolbar fills for supported editor contexts.
 */
export default function AugurControl() {
	const block = useSelect(
		( select ) => select( 'core/block-editor' ).getSelectedBlock(),
		[]
	);
	const postId = useSelect(
		( select ) => select( 'core/editor' )?.getCurrentPostId?.() ?? 0,
		[]
	);
	const { updateBlockAttributes } = useDispatch( 'core/block-editor' );
	const textBlock = getTextBlock( block );
	const [ isOpen, setIsOpen ] = useState( false );
	const [ isBusy, setIsBusy ] = useState( false );
	const [ result, setResult ] = useState( '' );
	const [ error, setError ] = useState( '' );
	const [ clientId, setClientId ] = useState( '' );

	const requestRevision = async () => {
		if ( ! textBlock || ! postId ) {
			return;
		}

		setClientId( textBlock.clientId );
		setResult( '' );
		setError( '' );
		setIsOpen( true );
		setIsBusy( true );

		try {
			const response = await improveClarity( textBlock.content, postId );
			setResult( response.text );
		} catch ( requestError ) {
			setError(
				requestError?.message ||
					__( 'Augur could not improve this text.', 'rune' )
			);
		} finally {
			setIsBusy( false );
		}
	};

	const applyRevision = () => {
		if ( result && clientId ) {
			updateBlockAttributes( clientId, { content: result } );
		}
		setIsOpen( false );
	};

	return (
		<>
			{ toolbarSlots.map( ( name ) => (
				<Fill key={ name } name={ name }>
					<Button
						className="rune-augur-control"
						icon="edit"
						size="compact"
						label={ __( 'Augur: Improve Clarity', 'rune' ) }
						disabled={ ! textBlock || ! postId || isBusy }
						onClick={ requestRevision }
					/>
				</Fill>
			) ) }
			{ isOpen && (
				<Modal
					title={ __( 'Augur: Improve Clarity', 'rune' ) }
					onRequestClose={ () => setIsOpen( false ) }
				>
					{ isBusy && <Spinner /> }
					{ error && (
						<Notice status="error" isDismissible={ false }>
							{ error }
						</Notice>
					) }
					{ result && (
						<>
							<TextareaControl
								label={ __( 'Suggested revision', 'rune' ) }
								value={ result }
								onChange={ setResult }
							/>
							<Button variant="primary" onClick={ applyRevision }>
								{ __( 'Apply Revision', 'rune' ) }
							</Button>
						</>
					) }
				</Modal>
			) }
		</>
	);
}
