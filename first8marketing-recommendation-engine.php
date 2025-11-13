<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName -- Main plugin file.
/**
 * Plugin Name: First8 Marketing - Recommendation Engine
 * Plugin URI: https://first8marketing.com
 * Description: Hyper-personalized recommendation engine for dynamic content and product recommendations
 * Version: 1.0.0
 * Author: First8 Marketing
 * Author URI: https://first8marketing.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: first8marketing-recommendation-engine
 * Requires at least: 6.0
 * Requires PHP: 8.0
 *
 * @package First8Marketing_Recommendation_Engine
 *
 * phpcs:disable WordPress.Files.FileName.InvalidClassFileName -- Legacy filename.
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define plugin constants.
define( 'RECENGINE_WP_VERSION', '1.0.0' );
define( 'RECENGINE_WP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'RECENGINE_WP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'RECENGINE_WP_PLUGIN_FILE', __FILE__ );

/**
 * Main plugin class
 */
class Recommendation_Engine_WP_Connect {

	/**
	 * Single instance of the class
	 *
	 * @var Recommendation_Engine_WP_Connect
	 */
	private static $instance = null;

	/**
	 * Get single instance
	 *
	 * @return Recommendation_Engine_WP_Connect
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor
	 */
	private function __construct() {
		$this->load_dependencies();
		$this->init_hooks();
	}

	/**
	 * Load required dependencies.
	 */
	private function load_dependencies() {
		// Core classes.
		require_once RECENGINE_WP_PLUGIN_DIR . 'includes/class-api-client.php';
		require_once RECENGINE_WP_PLUGIN_DIR . 'includes/class-rest-api.php';
		require_once RECENGINE_WP_PLUGIN_DIR . 'includes/class-recommendations.php';
		require_once RECENGINE_WP_PLUGIN_DIR . 'includes/class-personalization.php';
		require_once RECENGINE_WP_PLUGIN_DIR . 'includes/class-admin.php';
		require_once RECENGINE_WP_PLUGIN_DIR . 'includes/class-shortcodes.php';
		require_once RECENGINE_WP_PLUGIN_DIR . 'includes/class-sso-client.php';

		// Feature detection and commerce abstraction
		require_once RECENGINE_WP_PLUGIN_DIR . 'includes/class-feature-detector.php';
		require_once RECENGINE_WP_PLUGIN_DIR . 'includes/class-commerce-integration-interface.php';
		require_once RECENGINE_WP_PLUGIN_DIR . 'includes/class-commerce-integ极速赛车开奖直播历史记录-factory.php';

		// If-So feature parity classes.
		require_once RECENGINE_WP_PLUGIN_DIR . 'includes/class-triggers.php';
		require_once RECENGINE_WP_PLUGIN_DIR . 'includes/class-dki.php';
		require_once RECENGINE_WP_PLUGIN_DIR . 'includes/class-audiences.php';
		require_once RECENGINE_WP_PLUGIN_DIR . 'includes/class-gutenberg-blocks.php';
		require_once RECENGINE_WP_PLUGIN_DIR . 'includes/class-popups.php';
		require_once RECENGINE_WP_PLUGIN_DIR . 'includes/class-csv-import.php';
		require_once RECENGINE_WP_PLUGIN_DIR . 'includes/class-analytics.php';

		// Elementor integration.
		if ( did_action( 'elementor/loaded' ) ) {
			require_once RECENGINE_WP_PLUGIN_DIR . 'includes/class-elementor-widgets.php';
		}

		// Commerce integrations (load all available implementations)
		require_once RECENGINE_WP_PLUGIN_DIR . 'includes/integrations/class-generic-commerce-integration.php';
		if ( class_exists( 'WooCommerce' ) ) {
			require_once RECENGINE_WP_PLUGIN_DIR . 'includes/integrations/class-woocommerce-integration.php';
		}
	}

	/**
	 * Initialize hooks.
	 */
	private function init_hooks() {
		// Activation/Deactivation.
		register_activation_hook( RECENGINE_WP_PLUGIN_FILE, array( $this, 'activate' ) );
		register_deactivation_hook( RECENGINE_WP_PLUGIN_FILE, array( $this, 'deactivate' ) );

		// Initialize components.
		add_action( 'plugins_loaded', array( $this, 'init' ) );


		// Enqueue scripts and styles.
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
	}

	/**
	 * Initialize plugin components.
	 */
	public function init() {
		// Initialize REST API.
		RecEngine_REST_API::get_instance();

		// Initialize API client.
		RecEngine_API_Client::get_instance();

		// Initialize SSO client.
		RecEngine_SSO_Client::get_instance();

		// Initialize recommendations.
		RecEngine_Recommendations::get_instance();

		// Initialize personalization.
		RecEngine_Personalization::get_instance();

		// Initialize admin.
		if ( is_admin() ) {
			RecEngine_Admin::get_instance();
		}

		// Initialize shortcodes.
		RecEngine_Shortcodes::get_instance();

		// Initialize If-So feature parity components.
		RecEngine_Triggers::get_instance();
		RecEngine_DKI::get_instance();
		RecEngine_Audiences::get_instance();
		RecEngine_Gutenberg_Blocks::get_instance();
		RecEngine_Popups::get_instance();
		RecEngine_CSV_Import::get_instance();
		RecEngine_Analytics::get_instance();

		// Initialize Elementor widgets.
		if ( did_action( 'elementor/loaded' ) ) {
			RecEngine_Elementor_Widgets::get_instance();
		}

		// Initialize commerce integration (automatically selects appropriate implementation)
		RecEngine_Commerce_Integration_Factory::get_instance();

		do_action( 'recengine_wp_init' );
	}


	/**
	 * Enqueue frontend scripts and styles
	 */
	public function enqueue_scripts() {
		wp_enqueue_style(
			'recengine-wp-frontend',
			RECENGINE_WP_PLUGIN_URL . 'assets/css/frontend.css',
			array(),
			RECENGINE_WP_VERSION
		);

		wp_enqueue_script(
			'recengine-wp-frontend',
			RECENGINE_WP_PLUGIN_URL . 'assets/js/frontend.js',
			array( 'jquery' ),
			RECENGINE_WP_VERSION,
			true
		);

		// Localize script.
		wp_localize_script(
			'recengine-wp-frontend',
			'recengineWP',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'recengine_wp_nonce' ),
			)
		);
	}

	/**
	 * Enqueue admin scripts and styles.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_admin_scripts( $hook ) {
		// Only load on plugin settings page.
		if ( 'settings_page_recengine-wp-settings' !== $hook ) {
			return;
		}

		wp_enqueue_style(
			'recengine-wp-admin',
			RECENGINE_WP_PLUGIN_URL . 'assets/css/admin.css',
			array(),
			RECENGINE_WP_VERSION
		);

		wp_enqueue_script(
			'recengine-wp-admin',
			RECENGINE_WP_PLUGIN_URL . 'assets/js/admin.js',
			array( 'jquery' ),
			RECENGINE_WP_VERSION,
			true
		);
	}

	/**
	 * Plugin activation.
	 */
	public function activate() {
		// Create analytics table.
		RecEngine_Analytics::create_analytics_table();

		// Set default options.
		$default_options = array(
			'api_url'                => 'http://localhost:8000',
			'api_key'                => '',
			'cache_ttl'              => 300,
			'enable_personalization' => true,
			'enable_recommendations' => true,
			'sso_issuer'             => 'https://sso.first8marketing.com',
			'sso_client_id'          => 'first8marketing-wordpress',
			'sso_redirect_uri'       => home_url( '/wp-admin/admin.php?page=recengine-sso-callback' ),
		);

		add_option( 'recengine_wp_settings', $default_options );

		// Flush rewrite rules.
		flush_rewrite_rules();
	}

	/**
	 * Plugin deactivation.
	 */
	public function deactivate() {
		// Flush rewrite rules.
		flush_rewrite_rules();
	}
}

// Initialize plugin.
Recommendation_Engine_WP_Connect::get_instance();
