<?php
/**
 * SSO Client for Recommendation Engine WordPress Plugin
 *
 * @package Recommendation_Engine_WP
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Load Composer autoloader for JWT library
if ( file_exists( plugin_dir_path( __FILE__ ) . '../vendor/autoload.php' ) ) {
	require_once plugin_dir_path( __FILE__ ) . '../vendor/autoload.php';
}

use Firebase\JWT\JWT;
use Firebase\JWT\JWK;
use Firebase\JWT\Key;

/**
 * RecEngine_SSO_Client Class
 */
class RecEngine_SSO_Client {

	/**
	 * Single instance
	 *
	 * @var RecEngine_SSO_Client
	 */
	private static $instance = null;

	/**
	 * SSO configuration
	 *
	 * @var array
	 */
	private $sso_config;

	/**
	 * Get instance
	 *
	 * @return RecEngine_SSO_Client
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

		$this->sso_config = array(
			'issuer'        => isset( $settings['sso_issuer'] ) ? $settings['sso_issuer'] : 'https://sso.first8marketing.com',
			'client_id'     => isset( $settings['sso_client_id'] ) ? $settings['sso_client_id'] : 'first8marketing-wordpress',
			'redirect_uri'  => isset( $settings['sso_redirect_uri'] ) ? $settings['sso_redirect_uri'] : home_url( '/wp-admin/admin.php?page=recengine-sso-callback' ),
			'scope'         => 'openid profile email',
			'response_type' => 'code',
		);

		// Initialize SSO hooks
		$this->init_hooks();
	}

	/**
	 * Initialize SSO hooks
	 */
	private function init_hooks() {
		add_action( 'init', array( $this, 'handle_sso_callback' ) );
		add_action( 'wp_ajax_recengine_sso_login', array( $this, 'handle_ajax_login' ) );
		add_action( 'wp_ajax_nopriv_recengine_sso_login', array( $this, 'handle_ajax_login' ) );
		add_filter( 'authenticate', array( $this, 'sso_authentication' ), 10, 3 );
		add_action( 'wp_logout', array( $this, 'handle_logout' ) );
		add_action( 'admin_menu', array( $this, 'add_sso_callback_page' ) );
	}

	/**
	 * Handle SSO callback
	 */
	public function handle_sso_callback() {
		// Check if this is an SSO callback
		if ( ! isset( $_GET['code'] ) || ! isset( $_GET['state'] ) ) {
			return;
		}

		// Verify state to prevent CSRF
		$state = sanitize_text_field( wp_unslash( $_GET['state'] ) );
		if ( ! wp_verify_nonce( $state, 'recengine_sso_login' ) ) {
			wp_die( 'Invalid state parameter', 'SSO Error', array( 'response' => 400 ) );
		}

		$code = sanitize_text_field( wp_unslash( $_GET['code'] ) );

		// Exchange code for tokens
		$tokens = $this->exchange_code_for_tokens( $code );

		if ( is_wp_error( $tokens ) ) {
			wp_die( esc_html( $tokens->get_error_message() ), 'SSO Error', array( 'response' => 400 ) );
		}

		// Validate and decode ID token
		$user_info = $this->validate_id_token( $tokens['id_token'] );

		if ( is_wp_error( $user_info ) ) {
			wp_die( esc_html( $user_info->get_error_message() ), 'SSO Error', array( 'response' => 400 ) );
		}

		// Find or create WordPress user
		$user = $this->find_or_create_user( $user_info );

		if ( is_wp_error( $user ) ) {
			wp_die( esc_html( $user->get_error_message() ), 'SSO Error', array( 'response' => 400 ) );
		}

		// Log the user in
		wp_set_current_user( $user->ID );
		wp_set_auth_cookie( $user->ID, true );

		// Redirect to admin dashboard or original destination
		$redirect_to = isset( $_GET['redirect_to'] ) ? esc_url_raw( wp_unslash( $_GET['redirect_to'] ) ) : admin_url();
		wp_safe_redirect( $redirect_to );
		exit;
	}

	/**
	 * Handle AJAX login request
	 */
	public function handle_ajax_login() {
		check_ajax_referer( 'recengine_sso_login', 'nonce' );

		$auth_url = $this->get_authorization_url();
		wp_send_json_success( array( 'redirect_url' => $auth_url ) );
	}

	/**
	 * Get authorization URL
	 *
	 * @return string Authorization URL
	 */
	public function get_authorization_url() {
		$auth_url = add_query_arg(
			array(
				'client_id'     => $this->sso_config['client_id'],
				'redirect_uri'  => urlencode( $this->sso_config['redirect_uri'] ),
				'response_type' => $this->sso_config['response_type'],
				'scope'         => urlencode( $this->sso_config['scope'] ),
				'state'         => wp_create_nonce( 'recengine_sso_login' ),
			),
			$this->sso_config['issuer'] . '/authorize'
		);

		return $auth_url;
	}

	/**
	 * Exchange authorization code for tokens
	 *
	 * @param string $code Authorization code
	 * @return array|WP_Error Tokens or error
	 */
	private function exchange_code_for_tokens( $code ) {
		$response = wp_remote_post(
			$this->sso_config['issuer'] . '/token',
			array(
				'body' => array(
					'grant_type'   => 'authorization_code',
					'code'         => $code,
					'redirect_uri' => $this->sso_config['redirect_uri'],
					'client_id'    => $this->sso_config['client_id'],
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( ! isset( $body['access_token'] ) || ! isset( $body['id_token'] ) ) {
			return new WP_Error( 'sso_token_error', 'Failed to obtain tokens from SSO provider' );
		}

		return array(
			'access_token'  => $body['access_token'],
			'id_token'      => $body['id_token'],
			'refresh_token' => isset( $body['refresh_token'] ) ? $body['refresh_token'] : null,
		);
	}

	/**
	 * Validate ID token with proper JWT signature verification
	 *
	 * SECURITY: Uses firebase/php-jwt to properly verify JWT signatures.
	 * This prevents token forgery and ensures authenticity.
	 *
	 * @param string $id_token JWT ID token
	 * @return array|WP_Error User information or error
	 */
	private function validate_id_token( $id_token ) {
		// Check if JWT library is available
		if ( ! class_exists( 'Firebase\JWT\JWT' ) ) {
			error_log( 'RecEngine SSO: firebase/php-jwt library not found. Run: composer install' );
			return new WP_Error(
				'jwt_library_missing',
				'JWT library not installed. Please run composer install in the plugin directory.'
			);
		}

		try {
			// Get JWKS (JSON Web Key Set) from SSO provider
			$jwks = $this->fetch_jwks();
			if ( is_wp_error( $jwks ) ) {
				return $jwks;
			}

			// Parse JWKS and get signing keys
			$keys = JWK::parseKeySet( $jwks );

			// Decode and verify JWT signature
			$decoded = JWT::decode( $id_token, $keys );

			// Convert to array for further processing
			$payload = (array) $decoded;

			// Validate issuer
			if ( empty( $payload['iss'] ) || $payload['iss'] !== $this->sso_config['issuer'] ) {
				return new WP_Error( 'invalid_issuer', 'Invalid token issuer: ' . ( $payload['iss'] ?? 'none' ) );
			}

			// Validate audience
			if ( empty( $payload['aud'] ) || $payload['aud'] !== $this->sso_config['client_id'] ) {
				return new WP_Error( 'invalid_audience', 'Invalid token audience' );
			}

			// Validate expiration (JWT library already checks this, but double check)
			if ( empty( $payload['exp'] ) || $payload['exp'] < time() ) {
				return new WP_Error( 'token_expired', 'ID token has expired' );
			}

			// Validate issued at time (not in the future)
			if ( ! empty( $payload['iat'] ) && $payload['iat'] > time() + 60 ) {
				return new WP_Error( 'invalid_iat', 'Token issued in the future' );
			}

			// Log successful validation
			error_log( 'RecEngine SSO: Token validated successfully for subject: ' . ( $payload['sub'] ?? 'unknown' ) );

			return $payload;

		} catch ( Firebase\JWT\ExpiredException $e ) {
			error_log( 'RecEngine SSO: Token expired - ' . $e->getMessage() );
			return new WP_Error( 'token_expired', 'ID token has expired' );
		} catch ( Firebase\JWT\SignatureInvalidException $e ) {
			error_log( 'RecEngine SSO: Invalid signature - ' . $e->getMessage() );
			return new WP_Error( 'invalid_signature', 'Token signature verification failed' );
		} catch ( Firebase\JWT\BeforeValidException $e ) {
			error_log( 'RecEngine SSO: Token not yet valid - ' . $e->getMessage() );
			return new WP_Error( 'token_not_yet_valid', 'Token is not yet valid' );
		} catch ( Exception $e ) {
			error_log( 'RecEngine SSO: JWT validation error - ' . $e->getMessage() );
			return new WP_Error( 'jwt_validation_error', 'Failed to validate token: ' . $e->getMessage() );
		}
	}

	/**
	 * Fetch JWKS (JSON Web Key Set) from SSO provider
	 *
	 * @return array|WP_Error JWKS data or error
	 */
	private function fetch_jwks() {
		// Build JWKS URL from issuer
		$jwks_url = trailingslashit( $this->sso_config['issuer'] ) . '.well-known/jwks.json';

		// Try to get from cache first (cache for 1 hour)
		$cache_key = 'recengine_sso_jwks_' . md5( $jwks_url );
		$cached = get_transient( $cache_key );
		if ( false !== $cached ) {
			return $cached;
		}

		// Fetch JWKS from provider
		$response = wp_remote_get(
			$jwks_url,
			array(
				'timeout' => 10,
				'headers' => array(
					'Accept' => 'application/json',
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			error_log( 'RecEngine SSO: Failed to fetch JWKS - ' . $response->get_error_message() );
			return new WP_Error( 'jwks_fetch_failed', 'Failed to fetch JWKS from SSO provider' );
		}

		$status_code = wp_remote_retrieve_response_code( $response );
		if ( 200 !== $status_code ) {
			error_log( 'RecEngine SSO: JWKS endpoint returned status ' . $status_code );
			return new WP_Error( 'jwks_bad_response', 'JWKS endpoint returned status ' . $status_code );
		}

		$body = wp_remote_retrieve_body( $response );
		$jwks = json_decode( $body, true );

		if ( json_last_error() !== JSON_ERROR_NONE ) {
			error_log( 'RecEngine SSO: Invalid JWKS JSON - ' . json_last_error_msg() );
			return new WP_Error( 'jwks_invalid_json', 'Invalid JWKS JSON response' );
		}

		if ( empty( $jwks['keys'] ) || ! is_array( $jwks['keys'] ) ) {
			error_log( 'RecEngine SSO: JWKS missing keys array' );
			return new WP_Error( 'jwks_invalid_format', 'JWKS does not contain keys array' );
		}

		// Cache JWKS for 1 hour
		set_transient( $cache_key, $jwks, HOUR_IN_SECONDS );

		return $jwks;
	}

	/**
	 * Find or create WordPress user
	 *
	 * @param array $user_info User information from SSO
	 * @return WP_User|WP_Error WordPress user or error
	 */
	private function find_or_create_user( $user_info ) {
		$sso_user_id = isset( $user_info['sub'] ) ? $user_info['sub'] : '';
		$email       = isset( $user_info['email'] ) ? sanitize_email( $user_info['email'] ) : '';
		$name        = isset( $user_info['name'] ) ? sanitize_text_field( $user_info['name'] ) : '';

		if ( empty( $email ) ) {
			return new WP_Error( 'no_email', 'Email address is required' );
		}

		// Try to find user by email first (indexed lookup, much faster)
		$user = get_user_by( 'email', $email );
		
		if ( $user ) {
			// Check if user already has SSO ID linked
			$existing_sso_id = get_user_meta( $user->ID, 'recengine_sso_user_id', true );
			
			// Link or verify SSO user ID
			if ( empty( $existing_sso_id ) ) {
				update_user_meta( $user->ID, 'recengine_sso_user_id', $sso_user_id );
			} elseif ( $existing_sso_id !== $sso_user_id ) {
				// SSO ID mismatch - possible security issue
				return new WP_Error( 'sso_mismatch', 'Email is already associated with a different SSO account' );
			}
			
			return $user;
		}

		// Create new user if not found
		$username = $this->generate_username( $email, $name );
		$password = wp_generate_password( 24, true, true );

		$user_id = wp_create_user( $username, $password, $email );

		if ( is_wp_error( $user_id ) ) {
			return $user_id;
		}

		// Update user meta with additional information
		update_user_meta( $user_id, 'recengine_sso_user_id', $sso_user_id );
		
		if ( isset( $user_info['given_name'] ) ) {
			update_user_meta( $user_id, 'first_name', sanitize_text_field( $user_info['given_name'] ) );
		}
		if ( isset( $user_info['family_name'] ) ) {
			update_user_meta( $user_id, 'last_name', sanitize_text_field( $user_info['family_name'] ) );
		}

		return get_user_by( 'id', $user_id );
	}

	/**
	 * Generate username from email or name
	 *
	 * @param string $email Email address
	 * @param string $name Full name
	 * @return string Username
	 */
	private function generate_username( $email, $name ) {
		// Try to use the part before @ in email
		$username = strstr( $email, '@', true );
		
		// If that doesn't work, use a sanitized version of the name
		if ( empty( $username ) || username_exists( $username ) ) {
			$username = sanitize_user( str_replace( ' ', '', $name ), true );
		}

		// If still empty or exists, generate a random username
		if ( empty( $username ) || username_exists( $username ) ) {
			$username = 'user_' . wp_generate_password( 8, false, false );
		}

		// Ensure uniqueness
		$counter           = 1;
		$original_username = $username;
		while ( username_exists( $username ) ) {
			$username = $original_username . $counter;
			$counter++;
		}

		return $username;
	}

	/**
	 * SSO authentication filter
	 *
	 * @param WP_User|WP_Error|null $user User object or error
	 * @param string                $username Username
	 * @param string                $password Password
	 * @return WP_User|WP_Error|null
	 */
	public function sso_authentication( $user, $username, $password ) {
		// Only handle SSO authentication if password is empty (SSO flow)
		if ( ! empty( $password ) ) {
			return $user;
		}

		// Check if this is an SSO authentication attempt with nonce verification
		if ( ! isset( $_POST['sso_auth'] ) || 'true' !== $_POST['sso_auth'] || ! isset( $_POST['sso_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['sso_nonce'] ) ), 'recengine_sso_auth' ) ) {
			return $user;
		}

		// This would be handled by the callback flow instead
		return new WP_Error( 'sso_redirect', 'Redirecting to SSO provider...' );
	}

	/**
	 * Handle logout
	 */
	public function handle_logout() {
		// Clear any SSO-specific session data
		if ( isset( $_COOKIE['recengine_sso_token'] ) ) {
			setcookie( 'recengine_sso_token', '', time() - 3600, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true );
		}
	}

	/**
	 * Add SSO callback admin page
	 */
	public function add_sso_callback_page() {
		add_submenu_page(
			null, // No parent menu
			'SSO Callback',
			'SSO Callback',
			'read',
			'recengine-sso-callback',
			array( $this, 'render_sso_callback_page' )
		);
	}

	/**
	 * Render SSO callback page
	 */
	public function render_sso_callback_page() {
		// This page should not be accessed directly - it's for processing the OAuth callback
		// The actual processing happens in handle_sso_callback() via init hook
		echo '<div class="wrap">';
		echo '<h1>SSO Authentication</h1>';
		echo '<p>Processing Single Sign-On authentication...</p>';
		echo '</div>';
	}
}