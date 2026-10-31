<?php
/**
 * Editor canvas colour preference assets.
 *
 * @package Rune
 */

namespace Rune\Editor;

/**
 * Loads a SlotFill control and canvas-only styles in native block editors.
 */
final class ColourMode {
	/**
	 * Register separate editor-interface and canvas asset hooks.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'enqueue_block_editor_assets', array( $this, 'scripts' ) );
		add_action( 'enqueue_block_assets', array( $this, 'styles' ) );
	}

	/**
	 * Load the toolbar control with generated WordPress dependencies.
	 *
	 * @return void
	 */
	public function scripts(): void {
		$base = dirname( __DIR__ ) . '/build/editor-colour-mode';
		if ( ! is_readable( $base . '.asset.php' ) ) {
			return;
		}
		$asset = require $base . '.asset.php';
		wp_enqueue_script(
			'rune-editor-colour-mode',
			plugins_url( 'build/editor-colour-mode.js', dirname( __DIR__ ) . '/rune.php' ),
			$asset['dependencies'],
			$asset['version'],
			true
		);
		wp_set_script_translations( 'rune-editor-colour-mode', 'rune' );
	}

	/**
	 * Load preview styles in both iframed and non-iframed editor canvases.
	 *
	 * @return void
	 */
	public function styles(): void {
		$path = dirname( __DIR__ ) . '/build/editor-colour-mode.css';
		if ( ! is_admin() || ! is_readable( $path ) ) {
			return;
		}
		wp_enqueue_style(
			'rune-editor-colour-mode',
			plugins_url( 'build/editor-colour-mode.css', dirname( __DIR__ ) . '/rune.php' ),
			array(),
			(string) filemtime( $path )
		);
	}
}
