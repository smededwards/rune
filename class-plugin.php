<?php
/**
 * Block registration.
 *
 * @package Rune
 */

namespace Rune;

use Rune\Blocks\ContactForm;

/** Registers Rune blocks and editor extensions using native APIs. */
final class Plugin {

	/** Register block hooks. */
	public function register(): void {
		add_action( 'init', array( $this, 'blocks' ) );
		( new \Rune\Editor\ColourMode() )->register();
	}

	/** Register the contact block and its dynamic renderer. */
	public function blocks(): void {
		register_block_type(
			__DIR__ . '/blocks/contact-form',
			array( 'render_callback' => array( new ContactForm(), 'render' ) )
		);
	}
}
