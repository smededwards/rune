<?php
/**
 * Front-end code block copy controls.
 *
 * @package Rune
 */

namespace Rune\FrontEnd;

/**
 * Enhances native Code blocks without changing their saved markup.
 */
final class CodeCopy {
	/**
	 * Register the render filter and front-end assets.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ) );
		add_filter( 'render_block_core/code', array( $this, 'render' ), 10, 2 );
	}

	/**
	 * Register generated copy-control assets when they are available.
	 *
	 * @return void
	 */
	public function register_assets(): void {
		$base = dirname( __DIR__ ) . '/build/code-copy';
		if ( ! is_readable( $base . '.asset.php' ) ) {
			return;
		}

		$asset = require $base . '.asset.php';
		wp_register_script(
			'rune-code-copy',
			plugins_url( 'build/code-copy.js', dirname( __DIR__ ) . '/rune.php' ),
			$asset['dependencies'],
			$asset['version'],
			true
		);
		wp_register_style(
			'rune-code-copy',
			plugins_url( 'build/code-copy.css', dirname( __DIR__ ) . '/rune.php' ),
			array(),
			(string) filemtime( $base . '.css' )
		);
	}

	/**
	 * Add a copy control around a rendered native Code block.
	 *
	 * @param string $block_content Rendered Code block markup.
	 * @param array  $block         Parsed block data.
	 * @return string Enhanced block markup.
	 */
	public function render( string $block_content, array $block ): string {
		unset( $block );
		if ( '' === trim( wp_strip_all_tags( $block_content ) ) ) {
			return $block_content;
		}

		wp_enqueue_script( 'rune-code-copy' );
		wp_enqueue_style( 'rune-code-copy' );

		$button = sprintf(
			'<button class="rune-code-copy__button" type="button" data-rune-code-copy data-copy-label="%3$s" data-copied-label="%1$s" data-error-label="%2$s">%3$s</button>',
			esc_attr__( 'Copied', 'rune' ),
			esc_attr__( 'Unable to copy code.', 'rune' ),
			esc_html__( 'Copy', 'rune' )
		);
		$status = '<span class="rune-code-copy__status" role="status" aria-live="polite"></span>';

		return sprintf(
			'<div class="rune-code-copy">%1$s%2$s%3$s</div>',
			$button,
			$status,
			$block_content
		);
	}
}
