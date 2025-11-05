<?php
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
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 8.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Define plugin constants
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
     * Load required dependencies
     */
    private function load_dependencies() {
        // Core classes
        require_once RECENGINE_WP_PLUGIN_DIR . 'includes/class-api-client.php';
        require_once RECENGINE_WP_PLUGIN_DIR . 'includes/class-recommendations.php';
        require_once RECENGINE_WP_PLUGIN_DIR . 'includes/class-personalization.php';
        require_once RECENGINE_WP_PLUGIN_DIR . 'includes/class-admin.php';
        require_once RECENGINE_WP_PLUGIN_DIR . 'includes/class-shortcodes.php';
        
        // WooCommerce integration
        if ( class_exists( 'WooCommerce' ) ) {
            require_once RECENGINE_WP_PLUGIN_DIR . 'includes/class-woocommerce-integration.php';
        }
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Activation/Deactivation
        register_activation_hook( RECENGINE_WP_PLUGIN_FILE, array( $this, 'activate' ) );
        register_deactivation_hook( RECENGINE_WP_PLUGIN_FILE, array( $this, 'deactivate' ) );
        
        // Initialize components
        add_action( 'plugins_loaded', array( $this, 'init' ) );
        
        // Load text domain
        add_action( 'init', array( $this, 'load_textdomain' ) );
        
        // Enqueue scripts and styles
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
    }
    
    /**
     * Initialize plugin components
     */
    public function init() {
        // Initialize API client
        RecEngine_API_Client::get_instance();
        
        // Initialize recommendations
        RecEngine_Recommendations::get_instance();
        
        // Initialize personalization
        RecEngine_Personalization::get_instance();
        
        // Initialize admin
        if ( is_admin() ) {
            RecEngine_Admin::get_instance();
        }
        
        // Initialize shortcodes
        RecEngine_Shortcodes::get_instance();
        
        // Initialize WooCommerce integration
        if ( class_exists( 'WooCommerce' ) ) {
            RecEngine_WooCommerce_Integration::get_instance();
        }
        
        do_action( 'recengine_wp_init' );
    }
    
    /**
     * Load plugin text domain
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'recommendation-engine-wp',
            false,
            dirname( plugin_basename( RECENGINE_WP_PLUGIN_FILE ) ) . '/languages'
        );
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
        
        // Localize script
        wp_localize_script( 'recengine-wp-frontend', 'recengineWP', array(
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'nonce' => wp_create_nonce( 'recengine_wp_nonce' ),
        ) );
    }
    
    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_scripts( $hook ) {
        // Only load on plugin settings page
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
     * Plugin activation
     */
    public function activate() {
        // Set default options
        $default_options = array(
            'api_url' => 'http://localhost:8000',
            'api_key' => '',
            'cache_ttl' => 300,
            'enable_personalization' => true,
            'enable_recommendations' => true,
        );
        
        add_option( 'recengine_wp_settings', $default_options );
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Flush rewrite rules
        flush_rewrite_rules();
    }
}

// Initialize plugin
Recommendation_Engine_WP_Connect::get_instance();

