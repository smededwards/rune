<?php
/**
 * Project metadata and Block Bindings API checks executed within WordPress.
 *
 * @package Rune
 */

$rune_assert = static function ( bool $condition, string $message ): void {
	if ( ! $condition ) {
		WP_CLI::error( $message );
	}
};

$rune_meta = get_registered_meta_keys( 'post', 'page' );
foreach ( \Rune\Editor\ProjectDetails::META_KEYS as $rune_key ) {
	$rune_assert( isset( $rune_meta[ $rune_key ] ) && true === $rune_meta[ $rune_key ]['show_in_rest'], 'Project metadata was not registered for the REST API.' );
}

$rune_source = get_block_bindings_source( \Rune\Editor\ProjectDetails::SOURCE );
$rune_assert( null !== $rune_source, 'The project metadata binding source was not registered.' );

$rune_page_id = wp_insert_post(
	array(
		'post_title'  => 'Rune project details verification',
		'post_type'   => 'page',
		'post_status' => 'publish',
	)
);
$rune_assert( $rune_page_id > 0, 'The verification page could not be created.' );

update_post_meta( $rune_page_id, 'rune_project_role', 'Senior WordPress Engineer' );
update_post_meta( $rune_page_id, 'rune_project_year', '2026' );
update_post_meta( $rune_page_id, 'rune_project_technologies', 'WordPress, PHP, React' );

$rune_block          = new WP_Block(
	array(
		'blockName'    => 'core/paragraph',
		'attrs'        => array(),
		'innerBlocks'  => array(),
		'innerHTML'    => '',
		'innerContent' => array(),
	),
	array(
		'postId'   => $rune_page_id,
		'postType' => 'page',
	)
);
$rune_block->context = array(
	'postId'   => $rune_page_id,
	'postType' => 'page',
);

$rune_assert( 'Senior WordPress Engineer' === $rune_source->get_value( array( 'field' => 'role' ), $rune_block, 'content' ), 'The role binding returned the wrong value.' );
$rune_assert( '2026' === $rune_source->get_value( array( 'field' => 'year' ), $rune_block, 'content' ), 'The year binding returned the wrong value.' );
$rune_assert( null === $rune_source->get_value( array( 'field' => 'unknown' ), $rune_block, 'content' ), 'Unknown binding fields were not rejected.' );

wp_delete_post( $rune_page_id, true );
WP_CLI::success( 'Project details bindings passed.' );
