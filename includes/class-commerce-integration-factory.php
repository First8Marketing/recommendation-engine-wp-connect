<?php
/**
 * Commerce Integration Factory
 * 
 * Factory pattern for creating e-commerce platform integrations
 *
 * @package First8Marketing_Recommendation_Engine
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Commerce Integration Factory Class
 */
class RecEngine_Commerce_Integration_Factory {
	/**
	 * Single instance
	 *
	 * @var RecEngine_Commerce_Integration_Factory
	 */
	private static $instance = null;

	/**
	 * Get instance
	 *
	 * @return RecEngine_Commerce_Integration_Factory
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
		// Load available integrations
		$this->load_integrations();
	}

	/**
	 * Load available commerce integrations
	 */
	private function load_integrations() {
		// WooCommerce integration
		if ( $this->is_woocommerce_available() ) {
			require_once RECENGINE_WP_PLUGIN_DIR . 'includes/integrations/class-woocommerce-integration.php';
		}

		// Generic commerce integration (always available)
		require_once RECENGINE_WP_PLUGIN_DIR . 'includes/integrations/class-generic-commerce-integration.php';

		// Allow other integrations to be loaded via filter
		do_action( 'recengine_commerce_integrations_loaded' );
	}

	/**
	 * Check if WooCommerce is available
	 *
	 * @return bool
	 */
	private function is_woocommerce_available() {
		return class_exists( 'WooCommerce' ) && function_exists( 'wc_get_product' );
	}

	/**
	 * Get available commerce integrations
	 *
	 * @return array List of available integration class names
	 */
	public function get_available_integrations() {
		$integrations = array();

		if ( $this->is_woocommerce_available() ) {
			$integrations[] = 'RecEngine_WooCommerce_Integration';
		}

		// Generic integration is always available
		$integrations[] = 'RecEngine_Generic_Commerce_Integration';

		// Allow other integrations to be registered
		return apply_filters( 'recengine_available_commerce_integrations', $integrations );
	}

	/**
	 * Get the best available commerce integration
	 *
	 * @return RecEngine_Commerce_Integration_Interface
	 */
	public function get_integration() {
		$available = $this->get_available_integrations();

		// Return the first available (prioritized) integration
		foreach ( $available as $integration_class ) {
			if ( class_exists( $integration_class ) ) {
				return call_user_func( array( $integration_class, 'get_instance' ) );
			}
		}

		// Fallback to generic integration
		return RecEngine_Generic_Commerce_Integration::get_instance();
	}

	/**
	 * Check if any commerce integration is available
	 *
	 * @return bool
	 */
	public function has_commerce() {
		$available = $this->get_available_integrations();
		return count( $available ) > 1; // More than just generic integration
	}

	/**
	 * Get integration by platform name
	 *
	 * @param string $platform Platform name ('woocommerce', 'generic')
	 * @return RecEngine_Commerce_Integration_Interface|null
	 */
	public function get_integration_by_platform( $platform ) {
		$mapping = array(
			'woocommerce' => 'RecEngine_WooCommerce_Integration',
			'generic'     => 'RecEngine_Generic_Commerce_Integration',
		);

		$platform = strtolower( $platform );
		if ( isset( $mapping[ $platform ] ) && class_exists( $mapping[ $platform ] ) ) {
			return call_user_func( array( $mapping[ $platform ], 'get_instance' ) );
		}

		return null;
	}
}