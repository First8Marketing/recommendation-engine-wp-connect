<?php
/**
 * Feature Detector
 * 
 * Detects available features and e-commerce platforms
 * Provides fallback mechanisms for missing functionality
 *
 * @package First8Marketing_Recommendation_Engine
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Feature Detector Class
 */
class RecEngine_Feature_Detector {
	/**
	 * Single instance
	 *
	 * @var RecEngine_Feature_Detector
	 */
	private static $instance = null;

	/**
	 * Get instance
	 *
	 *	@return RecEngine_Feature_Detector
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
		// Initialize feature detection
	}

	/**
	 * Check if WooCommerce is available
	 *
	 * @return bool
	 */
	public function has_woocommerce() {
		return class_exists( 'WooCommerce' ) && function_exists( 'wc_get_product' );
	}

	/**
	 * Check if any e-commerce platform is available
	 *
	 * @return bool
	 */
	public function has_ecommerce() {
		return $this->has_woocommerce() || 
			   apply_filters( 'recengine_has_ecommerce', false );
	}

	/**
	 * Check if analytics tracking is available
	 *
	 * @return bool
	 */
	public function has_analytics() {
		return class_exists( 'Umami_Tracker' ) || 
			   defined( 'FIRST8MARKETING_TRACK_ACTIVE' ) ||
			   apply_filters( 'recengine_has_analytics', false );
	}

	/**
	 * Check if recommendation engine is available
	 *
	 * @return bool
	 */
	public function has_recommendation_engine() {
		$api_url = get_option( 'recengine_wp_settings', array() );
		$api_url = ! empty( $api_url['api_url'] ) ? $api_url['api_url'] : '';
		
		return ! empty( $api_url ) && 
			   apply_filters( 'recengine_has_recommendation_engine', true );
	}

	/**
	 * Check if a specific feature is available
	 *
	 * @param string $feature Feature name
	 * @return bool
	 */
	public function has_feature( $feature ) {
		$features = array(
			'product_recommendations' => $this->has_ecommerce() && $this->has_recommendation_engine(),
			'content_recommendations' => $this->has_recommendation_engine(),
			'ecommerce_tracking' => $this->has_ecommerce() && $this->has_analytics(),
			'behavioral_tracking' => $this->has_analytics(),
			'personalization' => $this->has_recommendation_engine(),
			'ab_testing' => $this->has_recommendation_engine() && $this->has_analytics(),
		);

		return isset( $features[ $feature ] ) ? $features[ $feature ] : false;
	}

	/**
	 * Get available features
	 *
	 * @return array
	 */
	public function get_available_features() {
		return array_filter( array(
			'woocommerce' => $this->has_woocommerce(),
			'ecommerce' => $this->has_ecommerce(),
			'analytics' => $this->has_analytics(),
			'recommendation_engine' => $this->has_recommendation_engine(),
			'product_recommendations' => $this->has_feature( 'product_recommendations' ),
			'content_recommendations' => $this->has_feature( 'content_recommendations' ),
			'ecommerce_tracking' => $this->has_feature( 'ecommerce_tracking' ),
			'behavioral_tracking' => $this->has_feature( 'behavioral_tracking' ),
			'personalization' => $this->has_feature( 'personalization' ),
			'ab_testing' => $this->has_feature( 'ab_testing' ),
		) );
	}

	/**
	 * Get feature status report
	 *
	 * @return array
	 */
	public function get_feature_report() {
		$features = $this->get_available_features();
		$report = array();

		foreach ( $features as $feature => $available ) {
			$report[ $feature ] = array(
				'available' => $available,
				'status' => $available ? 'active' : 'inactive',
				'message' => $available ? 
					$this->get_feature_message( $feature, true ) :
					$this->get_feature_message( $feature, false ),
			);
		}

		return $report;
	}

	/**
	 * Get feature status message
	 *
	 * @param string $feature Feature name
	 * @param bool $available Whether feature is available
	 * @return string
	 */
	private function get_feature_message( $feature, $available ) {
		$messages = array(
			'woocommerce' => array(
				true => __( 'WooCommerce integration is active', 'first8marketing-recommendation-engine' ),
				false => __( 'WooCommerce is not installed or activated', 'first8marketing-recommendation-engine' ),
			),
			'ecommerce' => array(
				true => __( 'E-commerce platform detected', 'first8marketing-recommendation-engine' ),
				false => __( 'No e-commerce platform detected', 'first8marketing-recommendation-engine' ),
			),
			'analytics' => array(
				true => __( 'Analytics tracking is enabled', 'first8marketing-recommendation-engine' ),
				false => __( 'Analytics tracking is not configured', 'first8marketing-recommendation-engine' ),
			),
			'recommendation_engine' => array(
				true => __( 'Recommendation engine is connected', 'first8marketing-recommendation-engine' ),
				false => __( 'Recommendation engine is not configured', 'first8marketing-recommendation-engine' ),
			),
			'product_recommendations' => array(
				true => __( 'Product recommendations are available', 'first8marketing-recommendation-engine' ),
				false => __( 'Product recommendations require e-commerce and recommendation engine', 'first8marketing-recommendation-engine' ),
			),
			'content_recommendations' => array(
				true => __( 'Content recommendations are available', 'first8marketing-recommendation-engine' ),
				false => __( 'Content recommendations require recommendation engine', 'first8marketing-recommendation-engine' ),
			),
		);

		return isset( $messages[ $feature ] ) ? 
			$messages[ $feature ][ $available ] : 
			( $available ? 
				__( 'Feature is active', 'first8marketing-recommendation-engine' ) : 
				__( 'Feature is not available', 'first8marketing-recommendation-engine' ) );
	}

	/**
	 * Get fallback recommendations when e-commerce is not available
	 *
	 * @param string $context Recommendation context
	 * @return array Fallback recommendations
	 */
	public function get_fallback_recommendations( $context = 'general' ) {
		$fallbacks = array(
			'product' => array(
				array(
					'title' => __( 'Explore Our Content', 'first8marketing-recommendation-engine' ),
					'description' => __( 'Discover more articles and resources that might interest you', 'first8marketing-recommendation-engine' ),
					'url' => get_permalink( get_option( 'page_for_posts' ) ),
					'image' => get_template_directory_uri() . '/assets/images/fallback-content.jpg',
				),
			),
			'cart' => array(
				array(
					'title' => __( 'Continue Exploring', 'first8marketing-recommendation-engine' ),
					'description' => __( 'Check out these popular resources', 'first8marketing-recommendation-engine' ),
					'url' => home_url( '/resources/' ),
					'image' => get_template_directory_uri() . '/assets/images/fallback-resources.jpg',
				),
			),
			'general' => array(
				array(
					'title' => __( 'Featured Content', 'first8marketing-recommendation-engine' ),
					'description' => __( 'Popular content you might enjoy', 'first8marketing-recommendation-engine' ),
					'url' => home_url( '/featured/' ),
					'image' => get_template_directory_uri() . '/assets/images/fallback-featured.jpg',
				),
			),
		);

		$context = isset( $fallbacks[ $context ] ) ? $context : 'general';
		return apply_filters( 'recengine_fallback_recommendations', $fallbacks[ $context ], $context );
	}

	/**
	 * Check if we should use fallback mode
	 *
	 * @param string $feature Feature to check
	 * @return bool
	 */
	public function should_use_fallback( $feature ) {
		$fallback_conditions = array(
			'product_recommendations' => ! $this->has_feature( 'product_recommendations' ),
			'ecommerce_tracking' => ! $this->has_feature( 'ecommerce_tracking' ),
			'personalization' => ! $this->has_feature( 'personalization' ),
		);

		return isset( $fallback_conditions[ $feature ] ) ? $fallback_conditions[ $feature ] : false;
	}

	/**
	 * Get fallback implementation for a feature
	 *
	 * @param string $feature Feature name
	 * @return mixed Fallback implementation or null
	 */
	public function get_fallback_implementation( $feature ) {
		if ( ! $this->should_use_fallback( $feature ) ) {
			return null;
		}

		$fallbacks = array(
			'product_recommendations' => array( $this, 'get_fallback_recommendations' ),
			'ecommerce_tracking' => '__return_null',
			'personalization' => '__return_false',
		);

		return isset( $fallbacks[ $feature ] ) ? $fallbacks[ $feature ] : null;
	}
}