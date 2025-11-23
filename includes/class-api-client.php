<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName -- Legacy filename.
/**
 * API Client for Recommendation Engine
 *
 * @package Recommendation_Engine_WP
 *
 * phpcs:disable WordPress.Files.FileName.InvalidClassFileName -- Legacy filename.
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * API Client class
 */
/**
 * RecEngine_API_Client Class
 */
class RecEngine_API_Client {

	/**
	 * Single instance
	 *
	 * @var RecEngine_API_Client
	 */
	private static $instance = null;

	/**
	 * API base URL
	 *
	 * @var string
	 */
	private $api_url;

	/**
	 * API key
	 *
	 * @var string
	 */
	private $api_key;

	/**
	 * Cache TTL in seconds
	 *
	 * @var int
	 */
	private $cache_ttl;

	/**
	 * Get instance
	 *
	 * @return RecEngine_API_Client
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
		$settings = get_option( 'recengine_wp_settings', array() );

		// API URL is required - no default fallback for security.
		// Administrators must configure this in plugin settings.
		$this->api_url   = isset( $settings['api_url'] ) && ! empty( $settings['api_url'] )
			? trailingslashit( $settings['api_url'] )
			: '';
		$this->api_key   = isset( $settings['api_key'] ) ? $settings['api_key'] : '';
		$this->cache_ttl = isset( $settings['cache_ttl'] ) ? intval( $settings['cache_ttl'] ) : 300;

		// Log warning if API URL is not configured.
		if ( empty( $this->api_url ) ) {
			// Use WordPress admin notice instead of error_log for production
			if ( is_admin() && current_user_can( 'manage_options' ) ) {
				add_action( 'admin_notices', function() {
					echo '<div class="notice notice-warning"><p>' .
						esc_html__( 'Recommendation Engine: API URL not configured. Please configure in plugin settings.', 'first8marketing-recommendation-engine' ) .
						'</p></div>';
				} );
			}
		}
	}

	/**
	 * Get recommendations for a user
	 *
	 * @param string $user_id User ID (optional).
	 * @param string $session_id Session ID.
	 * @param array  $context Context information.
	 * @param int    $count Number of recommendations.
	 * @param array  $exclude Products to exclude.
	 * @return array|WP_Error Recommendations or error
	 */
	public function get_recommendations( $user_id = null, $session_id = '', $context = array(), $count = 10, $exclude = array() ) {
		// Check if API URL is configured.
		if ( empty( $this->api_url ) ) {
			return new WP_Error(
				'recengine_no_api_url',
				__( 'Recommendation Engine API URL is not configured. Please configure in plugin settings.', 'first8marketing-recommendation-engine' )
			);
		}

		// PERFORMANCE: Unified cache key format matching Python backend for proper cache hit rates
		// Format: tenant:{tenant_id}:recommendations:{session_id}:{user_id}:{count}:{context_hash}
		// This ensures cache consistency between PHP plugin and Python API (70%+ hit rate target)
		$settings   = get_option( 'recengine_wp_settings', array() );
		$tenant_id  = isset( $settings['tenant_id'] ) ? $settings['tenant_id'] : 'default';
		$user_key   = $user_id ? $user_id : 'anon';
		
		// Create deterministic hash of context and exclude params for cache key stability
		$params_hash = md5( wp_json_encode( array( 'context' => $context, 'exclude' => $exclude ) ) );
		
		// Unified format matching Python: tenant:{tenant_id}:recommendations:{session_id}:{user_id}:{count}:{params_hash}
		$cache_key = sprintf(
			'tenant:%s:recommendations:%s:%s:%d:%s',
			sanitize_key( $tenant_id ),
			sanitize_key( $session_id ),
			sanitize_key( $user_key ),
			intval( $count ),
			$params_hash
		);

		// Check cache.
		$cached = get_transient( $cache_key );
		if ( false !== $cached ) {
			return $cached;
		}

		// Prepare request data.
		$data = array(
			'user_id'    => $user_id,
			'session_id' => $session_id ? $session_id : $this->get_session_id(),
			'context'    => $context,
			'count'      => $count,
			'exclude'    => $exclude, // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude -- External API parameter, not WordPress query
		);

		// Make API request.
		$response = $this->make_request( 'api/v1/recommendations', 'POST', $data );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		// Cache the result.
		set_transient( $cache_key, $response, $this->cache_ttl );

		return $response;
	}

	/**
	 * Make API request
	 *
	 * @param string $endpoint API endpoint.
	 * @param string $method HTTP method.
	 * @param array  $data Request data.
	 * @return array|WP_Error Response or error
	 */
	private function make_request( $endpoint, $method = 'GET', $data = array() ) {
		$url = $this->api_url . ltrim( $endpoint, '/' );

		$args = array(
			'method'  => $method,
			'headers' => array(
				'Content-Type' => 'application/json',
				'X-API-Key'    => $this->api_key,
			),
			'timeout' => 30,  // Increased from 10s to match backend timeout and accommodate ML inference
		);

		if ( 'POST' === $method || 'PUT' === $method ) {
			$args['body'] = wp_json_encode( $data );
		}

		$response = wp_remote_request( $url, $args );

		if ( is_wp_error( $response ) ) {
			error_log( 'RecEngine API Error: ' . $response->get_error_message() );
			return $response;
		}

		$status_code = wp_remote_retrieve_response_code( $response );
		$body        = wp_remote_retrieve_body( $response );

		if ( $status_code < 200 || $status_code >= 300 ) {
			error_log( 'RecEngine API Error: HTTP ' . $status_code . ' - ' . $body );
			return new WP_Error( 'api_error', 'API request failed with status ' . $status_code );
		}

		$decoded = json_decode( $body, true );

		if ( null === $decoded ) {
			error_log( 'RecEngine API Error: Invalid JSON response' );
			return new WP_Error( 'invalid_response', 'Invalid JSON response from API' );
		}

		return $decoded;
	}

	/**
	 * Get or create session ID
	 *
	 * @return string Session ID
	 */
	private function get_session_id() {
		if ( ! isset( $_COOKIE['recengine_session_id'] ) ) {
			$session_id = wp_generate_uuid4();

			// Secure cookie with HttpOnly, Secure (if HTTPS), and SameSite=Lax flags.
			$secure   = is_ssl();
			$httponly = true;
			$samesite = 'Lax';

			// PHP 7.3+ supports options array for setcookie.
			if ( PHP_VERSION_ID >= 70300 ) {
				setcookie(
					'recengine_session_id',
					$session_id,
					array(
						'expires'  => time() + DAY_IN_SECONDS * 30,
						'path'     => COOKIEPATH,
						'domain'   => COOKIE_DOMAIN,
						'secure'   => $secure,
						'httponly' => $httponly,
						'samesite' => $samesite,
					)
				);
			} else {
				// Fallback for PHP < 7.3.
				setcookie(
					'recengine_session_id',
					$session_id,
					time() + DAY_IN_SECONDS * 30,
					COOKIEPATH,
					COOKIE_DOMAIN,
					$secure,
					$httponly
				);
			}
		} else {
			$session_id = sanitize_text_field( wp_unslash( $_COOKIE['recengine_session_id'] ) );
		}

		return $session_id;
	}

	/**
	 * Test API connection
	 *
	 * @return bool|WP_Error True if connection successful, WP_Error otherwise
	 */
	public function test_connection() {
		$response = $this->make_request( 'health', 'GET' );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		if ( isset( $response['status'] ) && 'healthy' === $response['status'] ) {
			return true;
		}

		return new WP_Error( 'connection_failed', 'API connection test failed' );
	}
}
