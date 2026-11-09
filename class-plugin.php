<?php
/**
 * Block registration.
 *
 * @package Rune
 */

namespace Rune;

use Rune\Blocks\ContactForm;
use Rune\Blocks\ExperienceYears;
use Rune\Editor\Augur;
use Rune\Editor\ColourMode;
use Rune\Editor\Features;
use Rune\Editor\Patterns;
use Rune\Editor\ProjectDetails;
use Rune\Editor\Settings;
use Rune\FrontEnd\CodeCopy;

/**
 * Registers Rune blocks and editor extensions using native APIs.
 */
final class Plugin {

	/**
	 * Register block hooks.
	 *
	 * @return void
	 */
	public function register(): void {
		$settings = new Settings();
		$settings->register();
		add_action( 'init', array( $this, 'blocks' ) );
		( new Features( $settings ) )->register();
		( new Patterns( $settings ) )->register();
		( new ColourMode() )->register();
		( new Augur( $settings ) )->register();
		( new ProjectDetails() )->register();
		( new CodeCopy() )->register();
	}

	/**
	 * Register the contact block and its dynamic renderer.
	 *
	 * @return void
	 */
	public function blocks(): void {
		register_block_type(
			__DIR__ . '/blocks/contact-form',
			array( 'render_callback' => array( new ContactForm(), 'render' ) )
		);
		register_block_type(
			__DIR__ . '/blocks/experience-years',
			array( 'render_callback' => array( new ExperienceYears(), 'render' ) )
		);
	}
}
