// WordPress dependencies.
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { registerBlockType } from '@wordpress/blocks';
import {
	Button,
	Notice,
	PanelBody,
	Spinner,
	TextControl,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useCallback, useEffect, useRef, useState } from '@wordpress/element';

// Internal dependencies.
import metadata from './block.json';
import './style.scss';

const githubIcon = (
	<svg
		viewBox="0 0 24 24"
		xmlns="http://www.w3.org/2000/svg"
		aria-hidden="true"
	>
		<path d="M12 .297c-6.63 0-12 5.373-12 12 0 5.303 3.438 9.8 8.205 11.385.6.113.82-.258.82-.577 0-.285-.01-1.04-.015-2.04-3.338.724-4.042-1.61-4.042-1.61-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.084-.729.084-.729 1.205.084 1.84 1.237 1.84 1.237 1.07 1.835 2.809 1.305 3.495.998.108-.776.418-1.305.762-1.605-2.665-.3-5.466-1.332-5.466-5.93 0-1.31.465-2.38 1.235-3.22-.135-.303-.54-1.523.105-3.176 0 0 1.005-.322 3.3 1.23.96-.267 1.98-.4 3-.405 1.02.005 2.04.138 3 .405 2.28-1.552 3.285-1.23 3.285-1.23.645 1.653.24 2.873.12 3.176.765.84 1.23 1.91 1.23 3.22 0 4.61-2.805 5.625-5.475 5.92.42.36.81 1.096.81 2.21 0 1.595-.015 2.88-.015 3.27 0 .315.21.69.825.57C20.565 22.092 24 17.592 24 12.297c0-6.627-5.373-12-12-12" />
	</svg>
);

/**
 * Render the GitHub repository block editor controls and snapshot preview.
 *
 * @param {Object}                       props               Block component properties.
 * @param {Object}                       props.attributes    Saved repository snapshot.
 * @param {(attributes: Object) => void} props.setAttributes Updates saved block attributes.
 * @return {Object} Editor controls and repository preview.
 */
function Edit( { attributes, setAttributes } ) {
	const [ isLoading, setIsLoading ] = useState( false );
	const [ error, setError ] = useState( '' );
	const firstUrl = useRef( attributes.repositoryUrl );

	const fetchRepository = useCallback(
		async ( url ) => {
			const trimmedUrl = url.trim();
			if ( ! trimmedUrl ) {
				setError( '' );
				return;
			}
			setIsLoading( true );
			setError( '' );
			try {
				const apiFetch = window.wp?.apiFetch;
				if ( 'function' !== typeof apiFetch ) {
					throw new Error(
						__( 'The WordPress API is unavailable.', 'rune' )
					);
				}
				const repository = await apiFetch( {
					path: '/rune/v1/github-repository',
					method: 'POST',
					data: { url: trimmedUrl },
				} );
				firstUrl.current = repository.url;
				setAttributes( {
					repositoryUrl: repository.url,
					repositoryDataUrl: repository.url,
					repositoryName: repository.name,
					repositoryDescription: repository.description,
				} );
			} catch ( fetchError ) {
				setError(
					fetchError.message ||
						__( 'The repository could not be refreshed.', 'rune' )
				);
			} finally {
				setIsLoading( false );
			}
		},
		[ setAttributes ]
	);

	useEffect( () => {
		if ( firstUrl.current === attributes.repositoryUrl ) {
			return undefined;
		}
		firstUrl.current = attributes.repositoryUrl;
		const timer = window.setTimeout(
			() => fetchRepository( attributes.repositoryUrl ),
			500
		);
		return () => window.clearTimeout( timer );
	}, [ attributes.repositoryUrl, fetchRepository ] );

	const hasSnapshot =
		attributes.repositoryUrl &&
		attributes.repositoryUrl === attributes.repositoryDataUrl &&
		attributes.repositoryName;

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Settings', 'rune' ) }>
					<div className="github-repository__inspector-controls">
						<TextControl
							label={ __( 'URL', 'rune' ) }
							placeholder="https://github.com/owner/repository"
							value={ attributes.repositoryUrl }
							onChange={ ( repositoryUrl ) =>
								setAttributes( { repositoryUrl } )
							}
						/>
						<Button
							icon="update"
							label={ __( 'Refresh Repository Data', 'rune' ) }
							showTooltip
							disabled={ isLoading || ! attributes.repositoryUrl }
							onClick={ () =>
								fetchRepository( attributes.repositoryUrl )
							}
						/>
					</div>
				</PanelBody>
			</InspectorControls>
			<div { ...useBlockProps( { className: 'github-repository' } ) }>
				{ error && (
					<Notice status="error" isDismissible={ false }>
						{ error }
					</Notice>
				) }
				{ isLoading && <Spinner /> }
				{ hasSnapshot ? (
					<>
						<a
							className="github-repository__link"
							href={ attributes.repositoryUrl }
							target="_blank"
							rel="noopener noreferrer"
						>
							{ githubIcon }
							<span className="github-repository__name">
								{ attributes.repositoryName }
							</span>
							<span className="screen-reader-text">
								{ __( 'opens in a new tab', 'rune' ) }
							</span>
						</a>
						{ attributes.repositoryDescription && (
							<p className="github-repository__description">
								{ attributes.repositoryDescription }
							</p>
						) }
					</>
				) : (
					<div className="github-repository__icon">
						{ githubIcon }
					</div>
				) }
			</div>
		</>
	);
}

registerBlockType( metadata.name, {
	icon: githubIcon,
	edit: Edit,
	save: () => null,
} );
