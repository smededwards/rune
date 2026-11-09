<?php
/**
 * Core pattern controls.
 *
 * @package Rune
 */

namespace Rune\Editor;

use WP_Block_Patterns_Registry;
use WP_Error;
use WP_REST_Request;
use WP_REST_Server;

/**
 * Reuses WordPress's pattern manager without changing saved patterns.
 */
final class Patterns {
	// Native pattern manager route; the core screen retains its permissions.
	public const MENU_SLUG = 'site-editor.php?p=/pattern';

	/**
	 * Receive Rune editor preferences.
	 *
	 * @param Settings $settings Editor preferences.
	 */
	public function __construct( private readonly Settings $settings ) {}

	/**
	 * Register independent controls for the shortcut and pattern sources.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'admin_menu', array( $this, 'add_shortcut' ), 20 );
		add_action( 'init', array( $this, 'filter_bundled_patterns' ), 100 );
		add_filter( 'should_load_remote_block_patterns', array( $this, 'allow_remote_patterns' ) );
		add_filter( 'rest_pre_dispatch', array( $this, 'restrict_directory' ), 10, 3 );
	}

	/**
	 * Link to the native manager without duplicating a core shortcut.
	 *
	 * @return void
	 */
	public function add_shortcut(): void {
		if ( ! $this->settings->is_enabled( 'patterns_shortcut' ) ) {
			return;
		}
		global $submenu;
		foreach ( $submenu['themes.php'] ?? array() as $item ) {
			if ( in_array( $item[2], array( self::MENU_SLUG, 'edit.php?post_type=wp_block' ), true ) ) {
				return;
			}
		}
		add_submenu_page( 'themes.php', __( 'Patterns', 'rune' ), __( 'Patterns', 'rune' ), 'edit_theme_options', self::MENU_SLUG );
	}

	/**
	 * Hide bundled core patterns while preserving other registered sources.
	 *
	 * @return void
	 */
	public function filter_bundled_patterns(): void {
		if ( $this->settings->is_enabled( 'core_patterns' ) ) {
			return;
		}
		foreach ( WP_Block_Patterns_Registry::get_instance()->get_all_registered() as $pattern ) {
			if ( 'core' === ( $pattern['source'] ?? '' ) ) {
				unregister_block_pattern( $pattern['name'] );
			}
		}
	}

	/**
	 * Respect Rune's preference and restrictions from other providers.
	 *
	 * @param bool $allowed Existing permission to load remote patterns.
	 * @return bool Whether remote patterns can load.
	 */
	public function allow_remote_patterns( bool $allowed ): bool {
		return $allowed && $this->settings->is_enabled( 'remote_patterns' );
	}

	/**
	 * Block manual pattern-directory requests when remote patterns are disabled.
	 *
	 * @param mixed           $result  Earlier short-circuit response.
	 * @param WP_REST_Server  $server  Active REST server.
	 * @param WP_REST_Request $request Requested route.
	 * @return mixed Response or error when blocked; otherwise the earlier result.
	 */
	public function restrict_directory( mixed $result, WP_REST_Server $server, WP_REST_Request $request ): mixed {
		if ( null !== $result || $this->settings->is_enabled( 'remote_patterns' ) ) {
			return $result;
		}
		if ( '/wp/v2/pattern-directory/patterns' !== untrailingslashit( $request->get_route() ) ) {
			return $result;
		}
		return new WP_Error( 'rune_remote_patterns_disabled', __( 'WordPress.org patterns are disabled in Rune settings.', 'rune' ), array( 'status' => 403 ) );
	}
}
