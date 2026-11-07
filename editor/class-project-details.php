<?php
/**
 * Project metadata bindings and editor controls.
 *
 * @package Rune
 */

namespace Rune\Editor;

/**
 * Exposes project metadata to core blocks through the Block Bindings API.
 */
final class ProjectDetails {
	public const SOURCE = 'rune/project-meta';

	public const META_KEYS = array(
		'role'         => 'rune_project_role',
		'year'         => 'rune_project_year',
		'technologies' => 'rune_project_technologies',
	);

	/**
	 * Register metadata, bindings and the editor panel.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'init', array( $this, 'register_meta' ) );
		add_action( 'init', array( $this, 'register_bindings' ) );
		add_action( 'enqueue_block_editor_assets', array( $this, 'scripts' ) );
	}

	/**
	 * Register project fields for page REST responses and editor saves.
	 *
	 * @return void
	 */
	public function register_meta(): void {
		foreach ( self::META_KEYS as $key ) {
			register_post_meta(
				'page',
				$key,
				array(
					'auth_callback'     => array( $this, 'can_edit' ),
					'sanitize_callback' => 'sanitize_text_field',
					'show_in_rest'      => true,
					'single'            => true,
					'type'              => 'string',
				)
			);
		}
	}

	/**
	 * Check whether the current user may edit a page's project details.
	 *
	 * @param mixed  $allowed Existing metadata permission.
	 * @param string $meta_key Metadata key.
	 * @param int    $post_id Page ID.
	 * @param int    $user_id Current user ID.
	 * @return bool Whether the page may be edited.
	 */
	public function can_edit( mixed $allowed, string $meta_key, int $post_id, int $user_id ): bool {
		unset( $allowed, $meta_key, $user_id );
		return current_user_can( 'edit_post', $post_id );
	}

	/**
	 * Register Rune's project metadata binding source when supported.
	 *
	 * @return void
	 */
	public function register_bindings(): void {
		if ( ! function_exists( 'register_block_bindings_source' ) ) {
			return;
		}

		register_block_bindings_source(
			self::SOURCE,
			array(
				'label'              => __( 'Rune Project Details', 'rune' ),
				'get_value_callback' => array( $this, 'get_value' ),
				'uses_context'       => array( 'postId', 'postType' ),
			)
		);
	}

	/**
	 * Return one project field for a bound core block attribute.
	 *
	 * @param array     $source_args    Binding arguments.
	 * @param \WP_Block $block_instance Bound block instance.
	 * @param string    $attribute_name Bound block attribute name.
	 * @return string|null Project value or null when unavailable.
	 */
	public function get_value( array $source_args, $block_instance, string $attribute_name ): ?string {
		unset( $attribute_name );
		$field   = (string) ( $source_args['field'] ?? '' );
		$post_id = (int) ( $block_instance->context['postId'] ?? 0 );
		$post    = $post_id ? get_post( $post_id ) : null;

		if ( ! $post || 'page' !== $post->post_type || ! isset( self::META_KEYS[ $field ] ) ) {
			return null;
		}

		if ( ! is_post_publicly_viewable( $post ) && ! current_user_can( 'read_post', $post_id ) ) {
			return null;
		}

		if ( post_password_required( $post ) ) {
			return null;
		}

		return (string) get_post_meta( $post_id, self::META_KEYS[ $field ], true );
	}

	/**
	 * Enqueue the project details editor panel.
	 *
	 * @return void
	 */
	public function scripts(): void {
		$base = dirname( __DIR__ ) . '/build/project-details';
		if ( ! is_readable( $base . '.asset.php' ) ) {
			return;
		}

		$asset = require $base . '.asset.php';
		wp_enqueue_script(
			'rune-project-details',
			plugins_url( 'build/project-details.js', dirname( __DIR__ ) . '/rune.php' ),
			array_unique( array_merge( array( 'wp-editor' ), $asset['dependencies'] ) ),
			$asset['version'],
			true
		);
		wp_set_script_translations( 'rune-project-details', 'rune' );
	}
}
