<?php
/**
 * Native inserter checks executed within WordPress.
 *
 * @package Rune
 */

$rune_simplified = 'simplified' === ( $args[0] ?? '' );
$rune_assert     = static function ( bool $condition, string $message ): void {
	if ( ! $condition ) {
		WP_CLI::error( $message );
	}
};

$rune_media_base = array(
	'enableOpenverseMediaCategory' => true,
	'rune_media_marker'            => 'preserved',
);
foreach ( array( true, false ) as $rune_hide ) {
	$rune_option = static fn () => array( 'hide_openverse' => $rune_hide );
	add_filter( 'pre_option_rune_editor_features', $rune_option );
	$rune_media  = apply_filters( 'block_editor_settings_all', $rune_media_base, new WP_Block_Editor_Context() );
	remove_filter( 'pre_option_rune_editor_features', $rune_option );
	$rune_assert( ! $rune_hide === $rune_media['enableOpenverseMediaCategory'], 'Openverse did not follow its preference.' );
	$rune_assert( 'preserved' === $rune_media['rune_media_marker'], 'An unrelated media setting changed.' );
}

$rune_registry = WP_Block_Type_Registry::get_instance();
foreach ( array( 'post', 'page', 'wp_block' ) as $rune_type ) {
	$rune_context = new WP_Block_Editor_Context(
		array(
			'post' => new WP_Post(
				(object) array(
					'ID'        => 0,
					'post_type' => $rune_type,
				)
			),
		)
	);
	$rune_allowed = get_allowed_block_types( $rune_context );
	if ( $rune_simplified ) {
		$rune_assert( is_array( $rune_allowed ) && ! in_array( 'core/embed', $rune_allowed, true ), 'The inserter was not simplified.' );
		foreach ( array( 'rune/experience-years', 'core/code', 'core/table', 'core/list-item', 'core/column', 'core/button', 'core/block', 'core/query', 'core/post-template', 'core/query-pagination', 'core/query-pagination-next', 'core/query-pagination-numbers', 'core/query-pagination-previous' ) as $rune_required ) {
			$rune_assert( in_array( $rune_required, $rune_allowed, true ), 'Required block missing: ' . $rune_required );
		}
		$rune_filter = static fn () => array( 'core/paragraph', 'core/embed' );
		add_filter( 'allowed_block_types_all', $rune_filter, 99 );
		$rune_assert( array( 'core/paragraph' ) === get_allowed_block_types( $rune_context ), 'Earlier restrictions were overridden.' );
		remove_filter( 'allowed_block_types_all', $rune_filter, 99 );
		add_filter( 'allowed_block_types_all', '__return_false', 99 );
		$rune_assert( false === get_allowed_block_types( $rune_context ), 'A disabled inserter was re-enabled.' );
		remove_filter( 'allowed_block_types_all', '__return_false', 99 );
	} else {
		$rune_assert( true === $rune_allowed, 'Turning simplification off did not restore the inserter.' );
	}
}

foreach ( array(
	null,
	new WP_Post(
		(object) array(
			'ID'        => 0,
			'post_type' => 'attachment',
		)
	),
) as $rune_post ) {
	$rune_assert( true === get_allowed_block_types( new WP_Block_Editor_Context( array( 'post' => $rune_post ) ) ), 'An unrelated editor was restricted.' );
}
$rune_assert( $rune_registry->is_registered( 'core/embed' ), 'Hidden blocks must remain registered for rendering.' );

if ( $rune_simplified ) {
	$rune_walk = static function ( array $blocks, array $allowed ) use ( &$rune_walk, $rune_assert ): void {
		foreach ( $blocks as $block ) {
			if ( $block['blockName'] ) {
				$rune_assert( in_array( $block['blockName'], $allowed, true ), 'Existing content uses an excluded block: ' . $block['blockName'] );
			}
			$rune_walk( $block['innerBlocks'], $allowed );
		}
	};
	foreach ( get_posts(
		array(
			'post_type'   => array( 'post', 'page', 'wp_block' ),
			'post_status' => 'any',
			'numberposts' => -1,
		)
	) as $rune_item ) {
		$rune_walk( parse_blocks( $rune_item->post_content ), get_allowed_block_types( new WP_Block_Editor_Context( array( 'post' => $rune_item ) ) ) );
	}
}

WP_CLI::success( 'Editor controls passed: ' . ( $args[0] ?? '' ) );
