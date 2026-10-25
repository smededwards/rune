<?php
/**
 * Plugin Name: Rune
 * Description: Portfolio blocks foundation.
 * Version: 0.1.0
 * Requires PHP: 8.3
 * License: GPL-2.0-or-later
 * Text Domain: rune
 *
 * @package Rune
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register future blocks from their compiled metadata.
 *
 * @return void
 */
function rune_register_blocks() {
	foreach ( glob( __DIR__ . '/build/*/block.json' ) as $metadata ) {
		register_block_type( dirname( $metadata ) );
	}
}
add_action( 'init', 'rune_register_blocks' );
