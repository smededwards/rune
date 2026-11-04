<?php
/**
 * Real WordPress checks for independent pattern controls.
 *
 * @package Rune
 */

$rune_mode       = $args[0] ?? '111';
$rune_pattern_id = (int) ( $args[1] ?? 0 );
$rune_flags      = 'default' === $rune_mode ? '111' : $rune_mode;
$rune_shortcut   = '1' === $rune_flags[0];
$rune_core       = '1' === $rune_flags[1];
$rune_remote     = '1' === $rune_flags[2];
$rune_settings   = new \Rune\Editor\Settings();
$rune_patterns   = new \Rune\Editor\Patterns( $rune_settings );
$rune_registry   = \WP_Block_Patterns_Registry::get_instance();
$rune_assert     = static function ( bool $condition, string $message ): void {
	if ( ! $condition ) {
		WP_CLI::error( $message );
	}
};

$rune_assert( $rune_settings->is_enabled( 'patterns_shortcut' ) === $rune_shortcut, 'Shortcut preference was ignored.' );
$rune_assert( $rune_registry->is_registered( 'core/query-standard-posts' ) === $rune_core, 'Bundled core patterns did not follow the preference.' );
$rune_assert( true === current_theme_supports( 'core-block-patterns' ), 'Pattern controls must not conflate core and remote theme support.' );
$rune_assert( apply_filters( 'should_load_remote_block_patterns', true ) === $rune_remote, 'Remote preference was ignored.' );
$rune_assert( false === apply_filters( 'should_load_remote_block_patterns', false ), 'Restrictions from another provider must be preserved.' );

foreach ( array( 'theme', 'plugin', 'pattern-directory/core' ) as $rune_source ) {
	register_block_pattern(
		'rune-verification/' . sanitize_title( $rune_source ),
		array(
			'title'   => 'Verification',
			'content' => '<!-- wp:paragraph --><p>Verification</p><!-- /wp:paragraph -->',
			'source'  => $rune_source,
		)
	);
}
$rune_patterns->filter_bundled_patterns();
foreach ( array( 'theme', 'plugin', 'pattern-directory/core' ) as $rune_source ) {
	$rune_assert( $rune_registry->is_registered( 'rune-verification/' . sanitize_title( $rune_source ) ), 'A non-bundled pattern source was removed.' );
}
$rune_assert( null !== get_post( $rune_pattern_id ), 'The saved user pattern was deleted.' );
$rune_assert( str_contains( do_blocks( '<!-- wp:block {"ref":' . $rune_pattern_id . '} /-->' ), 'Rune pattern verification.' ), 'Saved synced-pattern content stopped rendering.' );

require_once ABSPATH . 'wp-admin/includes/plugin.php';
require_once ABSPATH . 'wp-admin/includes/template.php';
$rune_users = get_users(
	array(
		'role'   => 'administrator',
		'number' => 1,
		'fields' => 'ID',
	)
);
wp_set_current_user( (int) $rune_users[0] );
remove_submenu_page( 'themes.php', \Rune\Editor\Patterns::MENU_SLUG );
$rune_patterns->add_shortcut();
$rune_assert( count( $GLOBALS['submenu']['themes.php'] ?? array() ) === ( $rune_shortcut ? 1 : 0 ), 'The shortcut did not follow its preference.' );
$rune_patterns->add_shortcut();
$rune_assert( count( $GLOBALS['submenu']['themes.php'] ?? array() ) === ( $rune_shortcut ? 1 : 0 ), 'Duplicate shortcuts were added.' );
remove_submenu_page( 'themes.php', \Rune\Editor\Patterns::MENU_SLUG );
add_submenu_page( 'themes.php', 'Patterns', 'Patterns', 'edit_theme_options', \Rune\Editor\Patterns::MENU_SLUG );
$rune_patterns->add_shortcut();
$rune_assert( 1 === count( $GLOBALS['submenu']['themes.php'] ?? array() ), 'A native shortcut was duplicated or removed.' );

$rune_settings->register_settings();
ob_start();
$rune_settings->render_page();
$rune_form = ob_get_clean();
foreach ( array_keys( \Rune\Editor\Settings::DEFAULTS ) as $rune_key ) {
	$rune_assert( str_contains( $rune_form, 'rune_editor_features[' . $rune_key . ']' ), 'A settings control is missing.' );
}

$rune_server  = rest_get_server();
$rune_request = new \WP_REST_Request( 'GET', '/wp/v2/pattern-directory/patterns' );
$rune_error   = new \WP_Error( 'earlier_restriction', 'Verification' );
$rune_assert( $rune_error === $rune_patterns->restrict_directory( $rune_error, $rune_server, $rune_request ), 'An earlier REST restriction was overwritten.' );
$rune_assert( null === $rune_patterns->restrict_directory( null, $rune_server, new \WP_REST_Request( 'GET', '/wp/v2/pages' ) ), 'An unrelated REST route was blocked.' );
if ( $rune_remote ) {
	$rune_assert( null === $rune_patterns->restrict_directory( null, $rune_server, $rune_request ), 'Enabled remote queries were blocked.' );
} else {
	$rune_response = rest_do_request( $rune_request );
	$rune_assert( 403 === $rune_response->get_status() && 'rune_remote_patterns_disabled' === $rune_response->get_data()['code'], 'Disabled directory requests were not blocked by Rune.' );
}

wp_set_current_user( 0 );
remove_submenu_page( 'themes.php', \Rune\Editor\Patterns::MENU_SLUG );
$rune_patterns->add_shortcut();
$rune_assert( array() === ( $GLOBALS['submenu']['themes.php'] ?? array() ), 'A guest received the pattern-manager shortcut.' );
WP_CLI::success( 'Pattern controls passed: ' . $rune_mode );
