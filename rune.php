<?php
/**
 * Plugin Name: Rune
 * Plugin URI: https://github.com/smededwards/rune
 * Author: Michael Edwards
 * Author URI: https://smededwards.com
 * Description: Portfolio blocks foundation.
 * Version: 0.2.1
 * Requires PHP: 8.3
 * License: GPL-2.0-or-later
 * Text Domain: rune
 *
 * @package Rune
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Standalone installs use the plugin autoloader; Bedrock uses the site loader.
if ( ! class_exists( \Rune\Plugin::class ) && is_readable( __DIR__ . '/vendor/autoload.php' ) ) {
	require_once __DIR__ . '/vendor/autoload.php';
}
if ( class_exists( \Rune\Plugin::class ) ) {
	( new \Rune\Plugin() )->register();
} else {
	add_action(
		'admin_notices',
		static function () {
			if ( current_user_can( 'activate_plugins' ) ) {
				printf( '<div class="notice notice-error"><p>%s</p></div>', esc_html__( 'Rune requires its Composer autoloader. Run Composer install for the site or plugin.', 'rune' ) );
			}
		}
	);
}
