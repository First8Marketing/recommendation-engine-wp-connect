<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName -- Legacy filename.
/**
 * Dynamic Keyword Insertion (DKI) Handler
 *
 * @package First8Marketing_Recommendation_Engine
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * RecEngine_DKI Class
 */
class RecEngine_DKI {
	/**
	 * Single instance
	 *
	 * @var RecEngine_DKI
	 */
	private static $instance = null;

	/**
	 * Get instance
	 *
	 * @return RecEngine_DKI
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
		add_shortcode( 'recengine_dki', array( $this, 'dki_shortcode' ) );
	}

	/**
	 * DKI Shortcode Handler
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public function dki_shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'type'      => 'geo',
				'show'      => 'country',
				'parameter' => '',
				'fallback'  => '',
			),
			$atts
		);

		$type = sanitize_text_field( $atts['type'] );

		switch ( $type ) {
			case 'geo':
				return $this->get_geo_value( $atts );
			case 'querystring':
				return $this->get_querystring_value( $atts );
			case 'user':
				return $this->get_user_value( $atts );
			case 'time':
				return $this->get_time_value( $atts );
			default:
				return $atts['fallback'];
		}
	}

	/**
	 * Get geolocation value
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	private function get_geo_value( $atts ) {
		require_once RECENGINE_WP_PLUGIN_DIR . 'includes/conditions/class-condition-evaluator.php';

		$evaluator = new RecEngine_Condition_Evaluator();
		$location  = $this->get_user_location_via_reflection( $evaluator );

		if ( ! $location ) {
			return $atts['fallback'];
		}

		$show = sanitize_text_field( $atts['show'] );

		switch ( $show ) {
			case 'country':
				return isset( $location['country'] ) ? $location['country'] : $atts['fallback'];
			case 'state':
				return isset( $location['state'] ) ? $location['state'] : $atts['fallback'];
			case 'city':
				return isset( $location['city'] ) ? $location['city'] : $atts['fallback'];
			default:
				return $atts['fallback'];
		}
	}

	/**
	 * Get query string value
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	private function get_querystring_value( $atts ) {
		$parameter = sanitize_text_field( $atts['parameter'] );

		if ( ! $parameter ) {
			return $atts['fallback'];
		}

		$value = isset( $_GET[ $parameter ] ) ? sanitize_text_field( wp_unslash( $_GET[ $parameter ] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Reading URL parameter.

		return $value ? $value : $atts['fallback'];
	}

	/**
	 * Get user value
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	private function get_user_value( $atts ) {
		if ( ! is_user_logged_in() ) {
			return $atts['fallback'];
		}

		$user = wp_get_current_user();
		$show = sanitize_text_field( $atts['show'] );

		switch ( $show ) {
			case 'name':
				return $user->display_name;
			case 'firstname':
				return $user->first_name;
			case 'lastname':
				return $user->last_name;
			case 'email':
				return $user->user_email;
			case 'username':
				return $user->user_login;
			default:
				return $atts['fallback'];
		}
	}

	/**
	 * Get time value
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	private function get_time_value( $atts ) {
		$show = sanitize_text_field( $atts['show'] );

		switch ( $show ) {
			case 'date':
				return current_time( 'Y-m-d' );
			case 'time':
				return current_time( 'H:i:s' );
			case 'datetime':
				return current_time( 'Y-m-d H:i:s' );
			default:
				return current_time( 'Y-m-d H:i:s' );
		}
	}

	/**
	 * Get user location via reflection (access private method)
	 *
	 * @param RecEngine_Condition_Evaluator $evaluator Evaluator instance.
	 * @return array|false
	 */
	private function get_user_location_via_reflection( $evaluator ) {
		try {
			$reflection = new ReflectionClass( $evaluator );
			$method     = $reflection->getMethod( 'get_user_location' );
			$method->setAccessible( true );
			return $method->invoke( $evaluator );
		} catch ( Exception $e ) {
			return false;
		}
	}
}
