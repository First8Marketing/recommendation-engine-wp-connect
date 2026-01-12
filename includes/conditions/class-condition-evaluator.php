<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName -- Legacy filename.
/**
 * Condition Evaluator
 *
 * @package First8Marketing_Recommendation_Engine
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * RecEngine_Condition_Evaluator Class
 */
class RecEngine_Condition_Evaluator {
	/**
	 * Evaluate a condition
	 *
	 * @param string $condition_type Condition type.
	 * @param array  $conditions     Condition parameters.
	 * @return bool
	 */
	public function evaluate( $condition_type, $conditions ) {
		// Whitelist of allowed condition types to prevent method injection.
		$allowed_conditions = array(
			'logged_in',
			'logged_out',
			'user_role',
			'device_type',
			'geolocation',
			'utm_parameter',
			'datetime',
			'woocommerce_cart',
			'referrer',
		);

		// Validate condition_type is in whitelist.
		if ( ! in_array( $condition_type, $allowed_conditions, true ) ) {
			return false;
		}

		$method = 'evaluate_' . sanitize_key( $condition_type );

		if ( method_exists( $this, $method ) ) {
			return $this->$method( $conditions );
		}

		return false;
	}

	/**
	 * Evaluate logged-in condition
	 *
	 * @param array $conditions Condition parameters (unused).
	 * @return bool
	 */
	private function evaluate_logged_in( $conditions ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found -- Consistent method signature.
		return is_user_logged_in();
	}

	/**
	 * Evaluate logged-out condition
	 *
	 * @param array $conditions Condition parameters (unused).
	 * @return bool
	 */
	private function evaluate_logged_out( $conditions ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found -- Consistent method signature.
		return ! is_user_logged_in();
	}

	/**
	 * Evaluate user role condition
	 *
	 * @param array $conditions Condition parameters.
	 * @return bool
	 */
	private function evaluate_user_role( $conditions ) {
		if ( ! is_user_logged_in() ) {
			return false;
		}

		$user  = wp_get_current_user();
		$roles = isset( $conditions['roles'] ) ? $conditions['roles'] : array();

		foreach ( $roles as $role ) {
			if ( in_array( $role, $user->roles, true ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Evaluate device type condition
	 *
	 * @param array $conditions Condition parameters.
	 * @return bool
	 */
	private function evaluate_device_type( $conditions ) {
		$device = isset( $conditions['device'] ) ? $conditions['device'] : '';

		if ( 'mobile' === $device ) {
			return wp_is_mobile();
		} elseif ( 'desktop' === $device ) {
			return ! wp_is_mobile();
		}

		return false;
	}

	/**
	 * Evaluate geolocation condition
	 *
	 * @param array $conditions Condition parameters.
	 * @return bool
	 */
	private function evaluate_geolocation( $conditions ) {
		// Get user's location from API or session.
		$user_location = $this->get_user_location();

		if ( ! $user_location ) {
			return false;
		}

		$country = isset( $conditions['country'] ) ? $conditions['country'] : '';
		$state   = isset( $conditions['state'] ) ? $conditions['state'] : '';
		$city    = isset( $conditions['city'] ) ? $conditions['city'] : '';

		if ( $country && $user_location['country'] !== $country ) {
			return false;
		}

		if ( $state && $user_location['state'] !== $state ) {
			return false;
		}

		if ( $city && $user_location['city'] !== $city ) {
			return false;
		}

		return true;
	}

	/**
	 * Evaluate UTM parameter condition
	 *
	 * @param array $conditions Condition parameters.
	 * @return bool
	 */
	private function evaluate_utm_parameter( $conditions ) {
		$param = isset( $conditions['param'] ) ? $conditions['param'] : '';
		$value = isset( $conditions['value'] ) ? $conditions['value'] : '';

		if ( ! $param ) {
			return false;
		}

		$utm_value = isset( $_GET[ $param ] ) ? sanitize_text_field( wp_unslash( $_GET[ $param ] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Reading URL parameter.

		return $utm_value === $value;
	}

	/**
	 * Evaluate date/time condition
	 *
	 * @param array $conditions Condition parameters.
	 * @return bool
	 */
	private function evaluate_datetime( $conditions ) {
		$start = isset( $conditions['start'] ) ? strtotime( $conditions['start'] ) : 0;
		$end   = isset( $conditions['end'] ) ? strtotime( $conditions['end'] ) : 0;
		$now   = current_time( 'timestamp' ); // phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp.Requested -- Needed for date comparison.

		if ( $start && $now < $start ) {
			return false;
		}

		if ( $end && $now > $end ) {
			return false;
		}

		return true;
	}

	/**
	 * Evaluate WooCommerce cart condition
	 *
	 * @param array $conditions Condition parameters.
	 * @return bool
	 */
	private function evaluate_woocommerce_cart( $conditions ) {
		if ( ! class_exists( 'WooCommerce' ) || ! function_exists( 'WC' ) ) {
			return false;
		}

		$cart = WC()->cart;
		if ( ! $cart ) {
			return false;
		}

		$product_ids = isset( $conditions['product_ids'] ) ? $conditions['product_ids'] : array();
		$min_total   = isset( $conditions['min_total'] ) ? floatval( $conditions['min_total'] ) : 0;

		// Check if specific products are in cart.
		if ( ! empty( $product_ids ) ) {
			foreach ( $cart->get_cart() as $cart_item ) {
				if ( in_array( $cart_item['product_id'], $product_ids, true ) ) {
					return true;
				}
			}
			return false;
		}

		// Check minimum cart total.
		if ( $min_total > 0 ) {
			return $cart->get_cart_contents_total() >= $min_total;
		}

		return ! $cart->is_empty();
	}

	/**
	 * Evaluate referrer condition
	 *
	 * @param array $conditions Condition parameters.
	 * @return bool
	 */
	private function evaluate_referrer( $conditions ) {
		$referrer = isset( $_SERVER['HTTP_REFERER'] ) ? esc_url_raw( wp_unslash( $_SERVER['HTTP_REFERER'] ) ) : '';
		$match    = isset( $conditions['match'] ) ? $conditions['match'] : '';

		if ( ! $referrer || ! $match ) {
			return false;
		}

		return false !== strpos( $referrer, $match );
	}

	/**
	 * Get user location
	 *
	 * @return array|false
	 */
	private function get_user_location() {
		// Check session first.
		if ( isset( $_SESSION ) && isset( $_SESSION['recengine_user_location'] ) ) {
			return map_deep( $_SESSION['recengine_user_location'], 'sanitize_text_field' );
		}

		// Get from IP geolocation API.
		$ip = $this->get_user_ip();
		if ( ! $ip ) {
			return false;
		}

		// Use a free geolocation API.
		$response = wp_remote_get( 'http://ip-api.com/json/' . $ip );
		if ( is_wp_error( $response ) ) {
			return false;
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( ! $data || 'success' !== $data['status'] ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- External API response.
			return false;
		}

		$location = array(
			'country' => isset( $data['country'] ) ? $data['country'] : '',
			'state'   => isset( $data['regionName'] ) ? $data['regionName'] : '',
			'city'    => isset( $data['city'] ) ? $data['city'] : '',
		);

		// Store in session.
		if ( ! isset( $_SESSION ) ) {
			session_start();
		}
		$_SESSION['recengine_user_location'] = $location;

		return $location;
	}

	/**
	 * Get user IP address
	 *
	 * @return string
	 */
	private function get_user_ip() {
		$ip = '';

		if ( isset( $_SERVER['HTTP_CLIENT_IP'] ) ) {
			$ip = sanitize_text_field( wp_unslash( $_SERVER['HTTP_CLIENT_IP'] ) );
		} elseif ( isset( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			$ip = sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) );
		} elseif ( isset( $_SERVER['REMOTE_ADDR'] ) ) {
			$ip = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
		}

		return $ip;
	}
}
