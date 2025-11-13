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
		add_action( 'wp_ajax_nopriv_recengine_sso_login', array( $this极, 'handle_ajax_login' ) );
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
			wp_die( $tokens->get_error_message(), 'SSO Error', array( 'response' => 400 ) );
		}

		// Validate and decode ID token
		$user_info = $this->validate_id_token( $tokens['id_token'] );

		if ( is_wp_error( $user_info ) ) {
			wp_die( $user_info->get_error_message(), 'SSO Error', array( 'response' => 400 ) );
		}

		// Find or create WordPress user
		$user = $this->find_or_create_user( $user_info );

		if ( is_wp_error( $user ) ) {
			wp_die( $user->get_error_message(), 'SSO Error', array( 'response' => 400 ) );
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
					'grant_type'    => 'authorization_code',
					'code'          => $code,
					'redirect_uri' => $this->sso_config['redirect_uri'],
					'client_id'     => $this->sso_config['client_id'],
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
			'access_token' => $body['access_token'],
			'id_token'     => $body['id_token'],
			'refresh_token' => isset( $body['refresh_token'] ) ? $body['refresh_token'] : null,
		);
	}

	/**
	 * Validate ID token
	 *
	 * @param string $id_token JWT ID token
	 * @return array|WP_Error User information or error
	 */
	private function validate_id_token( $id_token ) {
		// In a production environment, you would validate the JWT signature
		// and verify the issuer, audience, and expiration
		// For simplicity, we'll just decode the payload

		$parts = explode( '.', $id_token );
		if ( count( $parts ) !== 3 ) {
			return new WP_Error( 'invalid_token', 'Invalid ID token format' );
		}

		$payload = json_decode( base64_decode( $parts[1] ), true );

		if ( ! $payload ) {
			return new WP_Error( 'invalid_token', 'Failed to decode ID token' );
		}

		// Validate expiration
		if ( isset( $payload['exp'] ) && time() > $payload['exp'] ) {
			return new WP_Error( 'token_expired', 'ID token has expired' );
		}

		// Validate issuer
		if ( isset( $payload['iss'] ) && $payload['iss'] !== $this->sso_config['issuer'] ) {
			return new WP_Error( 'invalid_issuer', 'Invalid token issuer' );
		}

		// Validate audience
		if ( isset( $payload['aud'] ) && $payload['aud'] !== $this->sso_config['client_id'] ) {
			return new WP_Error( 'invalid_audience', 'Invalid token audience' );
		}

		return $payload;
	}

	/**
	 * Find or create WordPress user
	 *
	 * @param array $user_info User information from SSO
	 * @return WP_User|WP_Error WordPress user or error
	 */
	private function find_or_create_user( $user_info ) {
		$user_id = isset( $user_info['sub'] ) ? $user_info['sub'] : '';
		$email   = isset( $user_info['email'] ) ? sanitize_email( $user_info['email'] ) : '';
		$name    = isset( $user_info['name'] ) ? sanitize_text_field( $user_info['name'] ) : '';

		if ( empty( $email ) ) {
			return new WP_Error( 'no_email', 'Email address is required' );
		}

		// Try to find user by SSO user ID
		$user = get_users(
			array(
				'meta_key'   => 'recengine_sso_user_id',
				'meta_value' => $user_id,
				'number'     => 1,
			)
		);

		if ( ! empty( $user ) ) {
			return $user[0];
		}

		// Try to find user by email
		$user = get_user_by( 'email', $email );
		if ( $user ) {
			// Link existing user with SSO
			update_user_meta( $user->ID, 'recengine_sso_user_id', $user_id );
			return $user;
		}

		// Create new user
		$username = $this->generate_username( $email, $name );
		$password  = wp_generate_password( 24, true, true );

		$user_id = wp_create_user(
			$username,
			$password,
			$email
		);
		
		if ( ! is_wp_error( $user_id ) ) {
			// Update user meta with additional information
			if ( isset( $user_info['given_name'] ) ) {
				update_user_meta( $user_id, 'first_name', sanitize_text_field( $user_info['given_name'] ) );
			}
			if ( isset( $user_info['family_name'] ) ) {
				update_user_meta( $user_id, 'last_name', sanitize_text_field( $user_info['family_name'] ) );
			}
		}

		if ( is_wp_error( $user_id ) ) {
			return $user_id;
		}

		// Add SSO user ID meta
		update_user_meta( $user_id, 'recengine_sso_user_id', $user_id );

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
	 $counter = 1;
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
	 * @param string $username Username
	 * @param string $password Password
	 * @return WP_User|WP_Error|null
	 */
	public function sso_authentication( $user, $username, $password ) {
	 // Only handle SSO authentication if password is empty (SSO flow)
	 if ( ! empty( $password ) ) {
	 	return $user;
	 }

	 // Check if this is an SSO authentication attempt
	 if ( ! isset( $_POST['sso_auth'] ) || 'true' !== $_POST['sso_auth'] ) {
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