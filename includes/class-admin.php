<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName -- Legacy filename.
/**
 * Admin Settings
 *
 * @package First8Marketing_Recommendation_Engine
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * RecEngine_Admin Class
 */
class RecEngine_Admin {
	/**
	 * Single instance
	 *
	 * @var RecEngine_Admin
	 */
	private static $instance = null;

	/**
	 * Get instance
	 *
	 * @return RecEngine_Admin
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 *   Construct
	 */
	private function __construct() {
		add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
	}

	/**
	 * Add Settings Page
	 */
	public function add_settings_page() {
		add_options_page(
			__( 'Recommendation Engine Settings', 'first8marketing-recommendation-engine' ),
			__( 'Recommendation Engine', 'first8marketing-recommendation-engine' ),
			'manage_options',
			'recengine-wp-settings',
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Register Settings
	 */
	public function register_settings() {
		register_setting( 'recengine_wp_settings', 'recengine_wp_settings', array( $this, 'sanitize_settings' ) );

		add_settings_section(
			'recengine_wp_api',
			__( 'API Configuration', 'first8marketing-recommendation-engine' ),
			null,
			'recengine-wp-settings'
		);

		add_settings_field(
			'api_url',
			__( 'API URL', 'first8marketing-recommendation-engine' ),
			array( $this, 'render_text_field' ),
			'recengine-wp-settings',
			'recengine_wp_api',
			array(
				'field'       => 'api_url',
				'placeholder' => 'http://localhost:8000',
			)
		);

		add_settings_field(
			'api_key',
			__( 'API Key', 'first8marketing-recommendation-engine' ),
			array( $this, 'render_text_field' ),
			'recengine-wp-settings',
			'recengine_wp_api',
			array(
				'field' => 'api_key',
				'type'  => 'password',
			)
		);
	}

	/**
	 * Render Text Field
	 *
	 * @param array $args Field arguments.
	 */
	public function render_text_field( $args ) {
		$settings    = get_option( 'recengine_wp_settings', array() );
		$value       = isset( $settings[ $args['field'] ] ) ? $settings[ $args['field'] ] : '';
		$type        = isset( $args['type'] ) ? $args['type'] : 'text';
		$placeholder = isset( $args['placeholder'] ) ? $args['placeholder'] : '';

		printf(
			'<input type="%s" name="recengine_wp_settings[%s]" value="%s" placeholder="%s" class="regular-text">',
			esc_attr( $type ),
			esc_attr( $args['field'] ),
			esc_attr( $value ),
			esc_attr( $placeholder )
		);
	}

	/**
	 * Render Settings Page
	 */
	public function render_settings_page() {
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<form method="post" action="options.php">
				<?php
				settings_fields( 'recengine_wp_settings' );
				do_settings_sections( 'recengine-wp-settings' );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Sanitize Settings
	 *
	 * @param array $input Input settings.
	 * @return array
	 */
	public function sanitize_settings( $input ) {
		$sanitized = array();

		if ( isset( $input['api_url'] ) ) {
			$sanitized['api_url'] = esc_url_raw( $input['api_url'] );
		}

		if ( isset( $input['api_key'] ) ) {
			$sanitized['api_key'] = sanitize_text_field( $input['api_key'] );
		}

		return $sanitized;
	}
}
