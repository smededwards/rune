<?php
// phpcs:ignoreFile WordPress.Files.FileName.InvalidClassFileName
/**
 * GitHub repository block.
 *
 * @package Rune
 */

namespace Rune\Blocks;

use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * Fetches public repository details for an editor-owned snapshot.
 */
final class GitHubRepository {
	/**
	 * Register the repository REST endpoint.
	 *
	 * @return void
	 */
	public function register_routes(): void {
		add_action( 'rest_api_init', array( $this, 'routes' ) );
	}

	/**
	 * Register the repository REST endpoint.
	 *
	 * @return void
	 */
	public function routes(): void {
		register_rest_route(
			'rune/v1',
			'/github-repository',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'permission_callback' => array( $this, 'permission' ),
				'callback'            => array( $this, 'fetch' ),
			)
		);
	}

	/**
	 * Allow editors to refresh repository data.
	 *
	 * @return bool
	 */
	public function permission(): bool {
		return current_user_can( 'edit_posts' ) || current_user_can( 'edit_pages' );
	}

	/**
	 * Fetch repository details from GitHub.
	 *
	 * @param WP_REST_Request $request REST request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function fetch( WP_REST_Request $request ) {
		$url      = esc_url_raw( trim( (string) $request->get_param( 'url' ) ) );
		$segments = $this->repository_segments( $url );
		if ( ! $segments ) {
			return new WP_Error( 'rune_invalid_repository', __( 'Enter a valid public GitHub repository URL.', 'rune' ), array( 'status' => 400 ) );
		}

		$endpoint = sprintf( 'https://api.github.com/repos/%s/%s', rawurlencode( $segments[0] ), rawurlencode( $segments[1] ) );
		$response = wp_remote_get(
			$endpoint,
			array(
				'timeout' => 10,
				'headers' => array(
					'Accept'     => 'application/vnd.github+json',
					'User-Agent' => 'Rune GitHub Repository Block',
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			return new WP_Error( 'rune_github_unavailable', __( 'GitHub could not be reached. Existing repository data was kept.', 'rune' ), array( 'status' => 502 ) );
		}

		$status = wp_remote_retrieve_response_code( $response );
		$data   = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( $status !== 200 || ! is_array( $data ) || empty( $data['html_url'] ) ) {
			return new WP_Error( 'rune_repository_unavailable', __( 'That public GitHub repository could not be found. Existing repository data was kept.', 'rune' ), array( 'status' => $status === 404 ? 404 : 502 ) );
		}

		return new WP_REST_Response(
			array(
				'url'         => esc_url_raw( $data['html_url'] ),
				'name'        => sanitize_text_field( $data['name'] ?? '' ),
				'description' => sanitize_textarea_field( $data['description'] ?? '' ),
			),
			200
		);
	}

	/**
	 * Extract an owner and repository from a GitHub URL.
	 *
	 * @param string $url Repository URL.
	 * @return array{0:string,1:string}|null
	 */
	private function repository_segments( string $url ): ?array {
		$parts = wp_parse_url( $url );
		if ( ! is_array( $parts ) || strtolower( $parts['host'] ?? '' ) !== 'github.com' ) {
			return null;
		}

		$segments = array_values( array_filter( explode( '/', trim( $parts['path'] ?? '', '/' ) ) ) );
		if ( count( $segments ) !== 2 ) {
			return null;
		}

		$repository = preg_replace( '/\.git$/i', '', $segments[1] );
		return $repository ? array( sanitize_user( $segments[0], true ), sanitize_title( $repository ) ) : null;
	}

	/**
	 * Render the stored repository snapshot.
	 *
	 * @param array<string,mixed> $attributes Block attributes.
	 * @return string Rendered block markup.
	 */
	public function render( array $attributes ): string {
		$url         = esc_url( $attributes['repositoryUrl'] ?? '' );
		$data_url    = esc_url( $attributes['repositoryDataUrl'] ?? '' );
		$name        = sanitize_text_field( $attributes['repositoryName'] ?? '' );
		$description = sanitize_textarea_field( $attributes['repositoryDescription'] ?? '' );
		if ( ! $url || $url !== $data_url || ! $name ) {
			return '';
		}

		return sprintf(
			'<div %1$s><a class="github-repository__link" href="%2$s" target="_blank" rel="noopener noreferrer">%3$s<span class="github-repository__name">%4$s</span><span class="screen-reader-text">%5$s</span></a>%6$s</div>',
			get_block_wrapper_attributes( array( 'class' => 'github-repository' ) ),
			$url,
			$this->icon_markup(),
			esc_html( $name ),
			esc_html__( 'opens in a new tab', 'rune' ),
			$description ? sprintf( '<p class="github-repository__description">%s</p>', esc_html( $description ) ) : ''
		);
	}

	/**
	 * Return the GitHub mark used by the repository link.
	 *
	 * @return string SVG markup.
	 */
	private function icon_markup(): string {
		return '<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M12 .297c-6.63 0-12 5.373-12 12 0 5.303 3.438 9.8 8.205 11.385.6.113.82-.258.82-.577 0-.285-.01-1.04-.015-2.04-3.338.724-4.042-1.61-4.042-1.61-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.084-.729.084-.729 1.205.084 1.84 1.237 1.84 1.237 1.07 1.835 2.809 1.305 3.495.998.108-.776.418-1.305.762-1.605-2.665-.3-5.466-1.332-5.466-5.93 0-1.31.465-2.38 1.235-3.22-.135-.303-.54-1.523.105-3.176 0 0 1.005-.322 3.3 1.23.96-.267 1.98-.4 3-.405 1.02.005 2.04.138 3 .405 2.28-1.552 3.285-1.23 3.285-1.23.645 1.653.24 2.873.12 3.176.765.84 1.23 1.91 1.23 3.22 0 4.61-2.805 5.625-5.475 5.92.42.36.81 1.096.81 2.21 0 1.595-.015 2.88-.015 3.27 0 .315.21.69.825.57C20.565 22.092 24 17.592 24 12.297c0-6.627-5.373-12-12-12" /></svg>';
	}
}
