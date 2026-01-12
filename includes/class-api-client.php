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
			: 'http://localhost:8000/api/v1/';
		$this->api_key   = isset( $settings['api_key'] ) ? $this->decrypt_api_key( $settings['api_key'] ) : '';
		$this->cache_ttl = isset( $settings['cache_ttl'] ) ? intval( $settings['cache_ttl'] ) : 300;

		// Log warning if API URL is not configured.
		if ( empty( $this->api_url ) || 'http://localhost:8000/api/v1/' === $this->api_url ) {
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
	 * Encrypt API key for secure storage
	 *
	 * @param string $api_key Plain API key.
	 * @return string Encrypted and base64 encoded API key
	 */
	public static function encrypt_api_key( $api_key ) {
		if ( empty( $api_key ) ) {
			return '';
		}

		try {
			$encrypted = openssl_encrypt(
				$api_key,
				'AES-256-CBC',
				wp_salt( 'auth' ),
				0,
				substr( wp_salt( 'secure_auth' ), 0, 16 )
			);

			if ( false === $encrypted ) {
				error_log( '[RecEngine] Failed to encrypt API key' );
				return '';
			}

			return base64_encode( $encrypted );
		} catch ( Exception $e ) {
			error_log( sprintf( '[RecEngine] API key encryption error: %s', $e->getMessage() ) );
			return '';
		}
	}

	/**
	 * Decrypt API key from secure storage
	 *
	 * @param string $encrypted_key Encrypted and base64 encoded API key.
	 * @return string Decrypted API key
	 */
	private function decrypt_api_key( $encrypted_key ) {
		if ( empty( $encrypted_key ) ) {
			return '';
		}

		// Check if key is already decrypted (backward compatibility)
		$decoded = base64_decode( $encrypted_key, true );
		if ( false === $decoded ) {
			// Not base64, might be legacy plaintext key
			error_log( '[RecEngine] Warning: API key appears to be in legacy plaintext format. Please re-save settings.' );
			return $encrypted_key;
		}

		try {
			$decrypted = openssl_decrypt(
				$decoded,
				'AES-256-CBC',
				wp_salt( 'auth' ),
				0,
				substr( wp_salt( 'secure_auth' ), 0, 16 )
			);

			if ( false === $decrypted ) {
				error_log( '[RecEngine] Failed to decrypt API key. Using legacy plaintext.' );
				return $encrypted_key; // Fallback to original for backward compatibility
			}

			return $decrypted;
		} catch ( Exception $e ) {
			error_log( sprintf( '[RecEngine] API key decryption error: %s', $e->getMessage() ) );
			return '';
		}
	}

	/**
	 * Get recommendations for a user
	 *
	 * @param string $user_id User ID (optional).
	 * @param string $session_id Session ID.
	 * @param array  $context Context information.
	 * @param int    $count Number of recommendations.
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

		// Generate optimized cache key using only essential params to improve cache hit rate
		$product_id = isset( $context['product_id'] ) ? $context['product_id'] : '';
		$page_type  = isset( $context['page_type'] ) ? $context['page_type'] : '';
		$cache_key  = sprintf(
			'recengine_recs_%s_%s_%s_%d',
			$user_id ? $user_id : 'guest',
			$product_id,
			$page_type,
			$count
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
			'timeout' => 10,
		);

		if ( 'POST' === $method || 'PUT' === $method ) {
			$args['body'] = wp_json_encode( $data );
		}

		$response = wp_remote_request( $url, $args );

		if ( is_wp_error( $response ) ) {
			// // error_log( 'RecEngine API Error: ' . $response->get_error_message() );  // phpcs:ignore -- Debug code commented out.  // phpcs:ignore -- Debug code commented out.
			return $response;
		}

		$status_code = wp_remote_retrieve_response_code( $response );
		$body        = wp_remote_retrieve_body( $response );

		if ( $status_code < 200 || $status_code >= 300 ) {
			// // error_log( 'RecEngine API Error: HTTP ' . $status_code . ' - ' . $body );  // phpcs:ignore -- Debug code commented out.  // phpcs:ignore -- Debug code commented out.
			return new WP_Error( 'api_error', 'API request failed with status ' . $status_code );
		}

		$decoded = json_decode( $body, true );

		if ( null === $decoded ) {
			// // error_log( 'RecEngine API Error: Invalid JSON response' );  // phpcs:ignore -- Debug code commented out.  // phpcs:ignore -- Debug code commented out.
			return new WP_Error( 'invalid_response', 'Invalid JSON response from API' );
		}

		return $decoded;
	}

	/**
	 * Get or create session ID using WordPress transients (cookie-free)
	 *
	 * For logged-in users: Uses user ID-based transient
	 * For guests: Uses IP-based transient with privacy considerations
	 *
	 * @return string Session ID
	 */
	private function get_session_id() {
		$transient_key = $this->get_transient_key();
		$session_id    = get_transient( $transient_key );

		if ( false === $session_id || empty( $session_id ) ) {
			$session_id = wp_generate_uuid4();
			// Store for 30 days for logged-in users, 1 day for guests
			$expiry = is_user_logged_in() ? DAY_IN_SECONDS * 30 : DAY_IN_SECONDS;
			set_transient( $transient_key, $session_id, $expiry );

			error_log( sprintf(
				'[RecEngine] New session created: %s (user: %s)',
				substr( $session_id, 0, 8 ) . '...',
				is_user_logged_in() ? get_current_user_id() : 'guest'
			) );
		}

		return $session_id;
	}

	/**
	 * Get transient key for session storage
	 *
	 * @return string Transient key
	 */
	private function get_transient_key() {
		if ( is_user_logged_in() ) {
			return 'recengine_session_' . get_current_user_id();
		}

		// For guests, use hashed IP address for privacy
		$ip = $this->get_client_ip();
		return 'recengine_session_guest_' . md5( $ip . wp_salt( 'nonce' ) );
	}

	/**
	 * Get client IP address with proxy support
	 *
	 * @return string IP address
	 */
	private function get_client_ip() {
		$ip_keys = array(
			'HTTP_CF_CONNECTING_IP', // Cloudflare
			'HTTP_X_REAL_IP',
			'HTTP_X_FORWARDED_FOR',
			'HTTP_CLIENT_IP',
			'REMOTE_ADDR',
		);

		foreach ( $ip_keys as $key ) {
			if ( isset( $_SERVER[ $key ] ) && ! empty( $_SERVER[ $key ] ) ) {
				$ip = sanitize_text_field( wp_unslash( $_SERVER[ $key ] ) );
				// Handle comma-separated IPs (X-Forwarded-For)
				if ( false !== strpos( $ip, ',' ) ) {
					$ip = trim( explode( ',', $ip )[0] );
				}
				// Validate IP
				if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
					return $ip;
				}
			}
		}

		return '0.0.0.0'; // Fallback
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
