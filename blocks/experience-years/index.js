// WordPress dependencies.
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { registerBlockType } from '@wordpress/blocks';
import { PanelBody, SelectControl, TextControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

// Internal dependencies.
import metadata from './block.json';
import { formatPreview, getCompletedYears } from './years';

/**
 * Render the experience years block settings and preview.
 *
 * @param {Object}                       props               Block component properties.
 * @param {Object}                       props.attributes    Saved date and display style.
 * @param {(attributes: Object) => void} props.setAttributes Updates saved block attributes.
 * @return {Object} Editor settings and block preview.
 */
function Edit( { attributes, setAttributes } ) {
	const years = getCompletedYears( attributes.startDate );

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Experience Settings', 'rune' ) }>
					<TextControl
						label={ __( 'Start Date', 'rune' ) }
						type="date"
						value={ attributes.startDate }
						onChange={ ( startDate ) =>
							setAttributes( { startDate } )
						}
					/>
					<SelectControl
						label={ __( 'Display Style', 'rune' ) }
						value={ attributes.displayStyle }
						options={ [
							{ label: __( 'Years', 'rune' ), value: 'years' },
							{
								label: __( 'Years Plus', 'rune' ),
								value: 'plus',
							},
							{
								label: __( 'Over Years', 'rune' ),
								value: 'over',
							},
						] }
						onChange={ ( displayStyle ) =>
							setAttributes( { displayStyle } )
						}
					/>
				</PanelBody>
			</InspectorControls>
			<span { ...useBlockProps( { className: 'experience-years' } ) }>
				<strong>
					{ formatPreview( years, attributes.displayStyle ) }
				</strong>
			</span>
		</>
	);
}

registerBlockType( metadata.name, { edit: Edit, save: () => null } );
