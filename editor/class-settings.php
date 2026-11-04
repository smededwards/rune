<?php
/**
 * Rune editor feature settings.
 *
 * @package Rune
 */

namespace Rune\Editor;

/**
 * Stores editor preferences and migrates the former Aeon controls.
 */
final class Settings {
	// Settings API option name.
	public const OPTION = 'rune_editor_features';

	// Preserve the current editor defaults after the feature move.
	public const DEFAULTS = array(
		'core_patterns'     => true,
		'hide_openverse'    => true,
		'patterns_shortcut' => true,
		'remote_patterns'   => true,
		'simplify_blocks'   => true,
	);

	/**
	 * Register settings, migration and the Rune settings page.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'init', array( $this, 'migrate' ), 0 );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
	}

	/**
	 * Read a known editor preference, falling back to its former Aeon value.
	 *
	 * @param string $feature Preference key.
	 * @return bool Whether the preference is enabled.
	 */
	public function is_enabled( string $feature ): bool {
		if ( ! array_key_exists( $feature, self::DEFAULTS ) ) {
			return false;
		}

		$options = get_option( self::OPTION, false );
		if ( false === $options ) {
			$options = get_option( 'aeon_core_features', array() );
		}

		if ( ! is_array( $options ) || ! array_key_exists( $feature, $options ) ) {
			return self::DEFAULTS[ $feature ];
		}

		return in_array( $options[ $feature ], array( true, 1, '1' ), true );
	}

	/**
	 * Copy former Aeon editor preferences without overwriting Rune settings.
	 *
	 * @return void
	 */
	public function migrate(): void {
		if ( false !== get_option( self::OPTION, false ) ) {
			return;
		}

		$legacy = get_option( 'aeon_core_features', array() );
		$values = array();
		foreach ( self::DEFAULTS as $feature => $default ) {
			$values[ $feature ] = is_array( $legacy ) && array_key_exists( $feature, $legacy )
				? in_array( $legacy[ $feature ], array( true, 1, '1' ), true )
				: $default;
		}

		add_option( self::OPTION, $values );
	}

	/**
	 * Register Rune's editor settings fields.
	 *
	 * @return void
	 */
	public function register_settings(): void {
		register_setting(
			'rune_editor',
			self::OPTION,
			array(
				'type'              => 'array',
				'default'           => self::DEFAULTS,
				'sanitize_callback' => array( $this, 'sanitise' ),
				'show_in_rest'      => false,
			)
		);
		add_settings_section( 'rune_editor_features', __( 'Block Editor', 'rune' ), '__return_null', 'rune-editor' );
		add_settings_section( 'rune_patterns', __( 'Patterns', 'rune' ), '__return_null', 'rune-editor' );

		$fields = array(
			'simplify_blocks'   => array(
				'section'     => 'rune_editor_features',
				'title'       => __( 'Available Blocks', 'rune' ),
				'label'       => __( 'Simplify Available Blocks', 'rune' ),
				'description' => __( 'Limits the Posts, Pages and saved patterns inserter to essential content and layout blocks.', 'rune' ),
			),
			'hide_openverse'    => array(
				'section'     => 'rune_editor_features',
				'title'       => __( 'Media Sources', 'rune' ),
				'label'       => __( 'Hide Openverse in the Editor', 'rune' ),
				'description' => __( 'Removes Openverse from editor media sources. Uploads and the Media Library remain available.', 'rune' ),
			),
			'core_patterns'     => array(
				'section'     => 'rune_patterns',
				'title'       => __( 'Bundled Core Patterns', 'rune' ),
				'label'       => __( 'Show WordPress’s Bundled Patterns', 'rune' ),
				'description' => __( 'Controls built-in core layouts. Theme, plugin, remote and saved user patterns are preserved.', 'rune' ),
			),
			'patterns_shortcut' => array(
				'section'     => 'rune_patterns',
				'title'       => __( 'Patterns Shortcut', 'rune' ),
				'label'       => __( 'Show Appearance → Patterns', 'rune' ),
				'description' => __( 'Adds a direct link to the native pattern manager.', 'rune' ),
			),
			'remote_patterns'   => array(
				'section'     => 'rune_patterns',
				'title'       => __( 'Remote Patterns', 'rune' ),
				'label'       => __( 'Allow Patterns from WordPress.org', 'rune' ),
				'description' => __( 'Controls remote patterns and directory queries. Saved patterns and existing content are preserved.', 'rune' ),
			),
		);

		foreach ( $fields as $key => $field ) {
			add_settings_field(
				'rune_' . $key,
				$field['title'],
				array( $this, 'render_field' ),
				'rune-editor',
				$field['section'],
				array(
					'key'         => $key,
					'label'       => $field['label'],
					'description' => $field['description'],
				)
			);
		}
	}

	/**
	 * Add the editor settings page under Settings.
	 *
	 * @return void
	 */
	public function add_settings_page(): void {
		add_options_page( __( 'Rune', 'rune' ), __( 'Rune', 'rune' ), 'manage_options', 'rune-editor', array( $this, 'render_page' ) );
	}

	/**
	 * Render the settings form.
	 *
	 * @return void
	 */
	public function render_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to manage Rune settings.', 'rune' ), '', array( 'response' => 403 ) );
		}
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Rune', 'rune' ); ?></h1>
			<form action="options.php" method="post">
				<?php
				settings_fields( 'rune_editor' );
				do_settings_sections( 'rune-editor' );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Render a checkbox field and its description.
	 *
	 * @param array $args Field key, label and description.
	 * @return void
	 */
	public function render_field( array $args ): void {
		$key = $args['key'];
		printf(
			'<input type="hidden" name="%1$s[%2$s]" value="0"><label for="rune-%2$s"><input id="rune-%2$s" type="checkbox" name="%1$s[%2$s]" value="1" %3$s> %4$s</label><p class="description">%5$s</p>',
			esc_attr( self::OPTION ),
			esc_attr( $key ),
			checked( $this->is_enabled( $key ), true, false ),
			esc_html( $args['label'] ),
			esc_html( $args['description'] )
		);
	}

	/**
	 * Sanitise known values and preserve preferences omitted by older forms.
	 *
	 * @param mixed $input Submitted settings.
	 * @return array<string, bool> Sanitised preferences.
	 */
	public function sanitise( mixed $input ): array {
		$clean = array();
		foreach ( array_keys( self::DEFAULTS ) as $feature ) {
			$clean[ $feature ] = is_array( $input ) && array_key_exists( $feature, $input )
				? in_array( $input[ $feature ], array( true, 1, '1' ), true )
				: $this->is_enabled( $feature );
		}
		return $clean;
	}
}
