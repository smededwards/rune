// WordPress dependencies.
import { registerBlockType } from '@wordpress/blocks';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { PanelBody, TextControl, TextareaControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

// Internal dependencies.
import metadata from './block.json';

/**
 * Render the contact form block settings and editor preview.
 *
 * @param {Object}                       props               Block component properties.
 * @param {Object}                       props.attributes    Saved form labels, placeholders and message.
 * @param {(attributes: Object) => void} props.setAttributes Updates saved block attributes.
 * @return {Object} Editor settings and form preview.
 */
function Edit( { attributes, setAttributes } ) {
	const fields = [ 'name', 'email', 'message' ];
	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Form content', 'rune' ) }>
					{ fields.map( ( field ) => (
						<div key={ field }>
							<TextControl
								label={ `${ metadata.attributes[ field + 'Label' ].default } ${ __( 'label', 'rune' ) }` }
								value={ attributes[ field + 'Label' ] }
								onChange={ ( value ) =>
									setAttributes( {
										[ field + 'Label' ]: value,
									} )
								}
							/>
							<TextControl
								label={ `${ metadata.attributes[ field + 'Label' ].default } ${ __( 'placeholder', 'rune' ) }` }
								value={ attributes[ field + 'Placeholder' ] }
								onChange={ ( value ) =>
									setAttributes( {
										[ field + 'Placeholder' ]: value,
									} )
								}
							/>
						</div>
					) ) }
					<TextControl
						label={ __( 'Button text', 'rune' ) }
						value={ attributes.buttonLabel }
						onChange={ ( buttonLabel ) =>
							setAttributes( { buttonLabel } )
						}
					/>
					<TextareaControl
						label={ __( 'Success message', 'rune' ) }
						value={ attributes.successMessage }
						onChange={ ( successMessage ) =>
							setAttributes( { successMessage } )
						}
					/>
				</PanelBody>
			</InspectorControls>
			<div { ...useBlockProps( { className: 'contact-form' } ) }>
				<p>
					{ __(
						'Contact form preview — edit its text in the block settings.',
						'rune'
					) }
				</p>
				{ fields.map( ( field ) => (
					<div className="contact-form__field" key={ field }>
						<TextControl
							label={ attributes[ field + 'Label' ] }
							placeholder={ attributes[ field + 'Placeholder' ] }
							value=""
							disabled
						/>
					</div>
				) ) }
				<button className="contact-form__submit" type="button" disabled>
					{ attributes.buttonLabel }
				</button>
			</div>
		</>
	);
}

registerBlockType( metadata.name, { edit: Edit, save: () => null } );
