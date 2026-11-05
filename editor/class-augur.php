<?php
/**
 * Augur editor assistance.
 *
 * @package Rune
 */

namespace Rune\Editor;

use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * Provides the first Augur editor action without persisting provider content.
 */
final class Augur {

	private const MAX_LENGTH = 4000;

	/**
	 * Receive Rune editor settings.
	 *
	 * @param Settings $settings Editor preferences.
	 */
	public function __construct( private readonly Settings $settings ) {}

	/**
	 * Register the editor script and REST endpoint.
	 *
	 * @return void
	 */
	public function register(): void {
		if ( ! $this->settings->is_enabled( 'augur' ) ) {
			return;
		}

		add_action( 'enqueue_block_editor_assets', array( $this, 'scripts' ) );
		add_action( 'rest_api_init', array( $this, 'routes' ) );
	}

	/**
	 * Enqueue Augur's editor control when its build exists.
	 *
	 * @return void
	 */
	public function scripts(): void {
		$base = dirname( __DIR__ ) . '/build/augur';
		if ( ! is_readable( $base . '.asset.php' ) ) {
			return;
		}

		$asset = require $base . '.asset.php';
		wp_enqueue_script(
			'rune-augur',
			plugins_url( 'build/augur.js', dirname( __DIR__ ) . '/rune.php' ),
			$asset['dependencies'],
			$asset['version'],
			true
		);
		wp_set_script_translations( 'rune-augur', 'rune' );
	}

	/**
	 * Register Augur's editor-only REST route.
	 *
	 * @return void
	 */
	public function routes(): void {
		register_rest_route(
			'rune/v1',
			'/augur/improve-clarity',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'improve_clarity' ),
				'permission_callback' => array( $this, 'can_improve' ),
				'args'                => array(
					'postId' => array(
						'required'          => true,
						'sanitize_callback' => 'absint',
					),
					'text'   => array(
						'required'          => true,
						'sanitize_callback' => 'sanitize_textarea_field',
					),
				),
			)
		);
	}

	/**
	 * Check the editor-level user and target post capability.
	 *
	 * @param WP_REST_Request $request Current REST request.
	 * @return bool Whether the request may continue.
	 */
	public function can_improve( WP_REST_Request $request ): bool {
		$post_id = absint( $request->get_param( 'postId' ) );

		return $post_id > 0
			&& ( current_user_can( 'edit_others_posts' ) || current_user_can( 'edit_others_pages' ) )
			&& current_user_can( 'edit_post', $post_id );
	}

	/**
	 * Rewrite the supplied text for clarity through the configured provider.
	 *
	 * @param WP_REST_Request $request Current REST request.
	 * @return WP_REST_Response|WP_Error Provider response or a safe error.
	 */
	public function improve_clarity( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$text = trim( (string) $request->get_param( 'text' ) );
		if ( '' === $text ) {
			return new WP_Error( 'rune_augur_empty_text', __( 'Select some text to improve.', 'rune' ), array( 'status' => 400 ) );
		}

		if ( mb_strlen( $text ) > self::MAX_LENGTH ) {
			return new WP_Error( 'rune_augur_text_too_long', __( 'Select less text and try again.', 'rune' ), array( 'status' => 400 ) );
		}

		$provider = $this->setting( 'AUGUR_PROVIDER' );
		$model    = $this->setting( 'AUGUR_MODEL' );
		$key      = $this->setting( 'AUGUR_API_KEY' );
		if ( '' === $provider || '' === $model || '' === $key ) {
			return new WP_Error( 'rune_augur_not_configured', __( 'Augur is not configured for this environment yet.', 'rune' ), array( 'status' => 503 ) );
		}

		$provider = strtolower( $provider );
		if ( ! in_array( $provider, array( 'openai', 'gemini' ), true ) ) {
			return new WP_Error( 'rune_augur_provider_unsupported', __( 'Augur does not support this provider yet.', 'rune' ), array( 'status' => 503 ) );
		}

		$response = 'gemini' === $provider
			? $this->request_gemini( $key, $model, $text )
			: $this->request_openai( $key, $model, $text );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		return rest_ensure_response( array( 'text' => $response ) );
	}

	/**
	 * Send a clarity request to an OpenAI-compatible provider.
	 *
	 * @param string $key API key.
	 * @param string $model Provider model.
	 * @param string $text Selected editor text.
	 * @return string|WP_Error Revised text or an error.
	 */
	private function request_openai( string $key, string $model, string $text ): string|WP_Error {
		$response = wp_remote_post(
			'https://api.openai.com/v1/chat/completions',
			array(
				'timeout' => 20,
				'headers' => array(
					'Authorization' => 'Bearer ' . $key,
					'Content-Type'  => 'application/json',
				),
				'body'    => wp_json_encode( $this->request_body( $model, $text ) ),
			)
		);

		return $this->extract_openai_text( $response );
	}

	/**
	 * Send a clarity request to Gemini.
	 *
	 * @param string $key API key.
	 * @param string $model Provider model.
	 * @param string $text Selected editor text.
	 * @return string|WP_Error Revised text or an error.
	 */
	private function request_gemini( string $key, string $model, string $text ): string|WP_Error {
		$response = wp_remote_post(
			sprintf( 'https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent', rawurlencode( $model ) ),
			array(
				'timeout' => 20,
				'headers' => array(
					'Content-Type'   => 'application/json',
					'x-goog-api-key' => $key,
				),
				'body'    => wp_json_encode(
					array(
						'contents' => array(
							array( 'parts' => array( array( 'text' => $this->prompt( $text ) ) ) ),
						),
					)
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			return new WP_Error( 'rune_augur_provider_error', __( 'Augur could not reach its provider.', 'rune' ), array( 'status' => 502 ) );
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		$text = $body['candidates'][0]['content']['parts'][0]['text'] ?? '';
		return $this->response_text( $text, wp_remote_retrieve_response_code( $response ) );
	}

	/**
	 * Build the OpenAI-compatible request body.
	 *
	 * @param string $model Provider model.
	 * @param string $text Selected editor text.
	 * @return array<string, mixed> Request payload.
	 */
	private function request_body( string $model, string $text ): array {
		return array(
			'model'    => $model,
			'messages' => array(
				array(
					'role'    => 'system',
					'content' => 'Improve the clarity of the supplied text. Preserve its meaning, facts and tone. Return only the revised text.',
				),
				array(
					'role'    => 'user',
					'content' => $text,
				),
			),
		);
	}

	/**
	 * Return the concise editor prompt.
	 *
	 * @param string $text Selected editor text.
	 * @return string Prompt sent with the selected text.
	 */
	private function prompt( string $text ): string {
		return sprintf( 'Improve the clarity of the following text. Preserve its meaning, facts and tone. Return only the revised text.%1$s%2$s', "\n\n", $text );
	}

	/**
	 * Extract a response from an OpenAI-compatible endpoint.
	 *
	 * @param array|WP_Error $response HTTP response.
	 * @return string|WP_Error Revised text or an error.
	 */
	private function extract_openai_text( array|WP_Error $response ): string|WP_Error {
		if ( is_wp_error( $response ) ) {
			return new WP_Error( 'rune_augur_provider_error', __( 'Augur could not reach its provider.', 'rune' ), array( 'status' => 502 ) );
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		$text = $body['choices'][0]['message']['content'] ?? '';
		return $this->response_text( $text, wp_remote_retrieve_response_code( $response ) );
	}

	/**
	 * Validate a provider response before returning it to the editor.
	 *
	 * @param mixed $text Provider text.
	 * @param int   $status HTTP status code.
	 * @return string|WP_Error Revised text or an error.
	 */
	private function response_text( mixed $text, int $status ): string|WP_Error {
		if ( $status < 200 || $status >= 300 || ! is_string( $text ) || '' === trim( $text ) ) {
			return new WP_Error( 'rune_augur_provider_error', __( 'Augur returned no usable text.', 'rune' ), array( 'status' => 502 ) );
		}

		return trim( wp_strip_all_tags( $text ) );
	}

	/**
	 * Read a server-side Augur setting without exposing it to the browser.
	 *
	 * @param string $name Environment variable or constant name.
	 * @return string Configured value.
	 */
	private function setting( string $name ): string {
		if ( defined( $name ) ) {
			return (string) constant( $name );
		}

		$value = getenv( $name );
		return false === $value ? '' : trim( (string) $value );
	}
}
