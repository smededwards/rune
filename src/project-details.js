// WordPress dependencies.
import { registerBlockBindingsSource } from '@wordpress/blocks';
import { TextControl } from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { registerPlugin } from '@wordpress/plugins';

const PluginDocumentSettingPanel =
	window.wp?.editor?.PluginDocumentSettingPanel;

const fields = [
	{
		field: 'role',
		key: 'rune_project_role',
		label: __( 'Role', 'rune' ),
	},
	{
		field: 'year',
		key: 'rune_project_year',
		label: __( 'Year', 'rune' ),
	},
	{
		field: 'technologies',
		key: 'rune_project_technologies',
		label: __( 'Technologies', 'rune' ),
		help: __( 'Separate technologies with commas.', 'rune' ),
	},
];

registerBlockBindingsSource( {
	name: 'rune/project-meta',
	label: __( 'Rune Project Details', 'rune' ),
	usesContext: [ 'postId', 'postType' ],
	getFieldsList: () =>
		fields.map( ( field ) => ( {
			label: field.label,
			args: { field: field.field },
		} ) ),
} );

/**
 * Render the project metadata panel for page editors.
 *
 * @return {Object|null} The settings panel or nothing outside page editing.
 */
function ProjectDetailsPanel() {
	const { meta, postType } = useSelect(
		( select ) => ( {
			meta:
				select( 'core/editor' ).getEditedPostAttribute( 'meta' ) || {},
			postType: select( 'core/editor' ).getCurrentPostType(),
		} ),
		[]
	);
	const { editPost } = useDispatch( 'core/editor' );

	if (
		'page' !== postType ||
		'function' !== typeof PluginDocumentSettingPanel
	) {
		return null;
	}

	return (
		<PluginDocumentSettingPanel
			name="rune-project-details"
			title={ __( 'Project Details', 'rune' ) }
			initialOpen={ true }
		>
			{ fields.map( ( field ) => (
				<TextControl
					key={ field.key }
					label={ field.label }
					help={ field.help }
					value={ meta[ field.key ] || '' }
					onChange={ ( value ) =>
						editPost( {
							meta: { ...meta, [ field.key ]: value },
						} )
					}
				/>
			) ) }
		</PluginDocumentSettingPanel>
	);
}

registerPlugin( 'rune-project-details', { render: ProjectDetailsPanel } );
