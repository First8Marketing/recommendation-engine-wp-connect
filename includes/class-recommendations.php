<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName -- Legacy filename.
/**
 * Recommendations Handler
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
 * Recommendations class
 */
/**
 * RecEngine_Recommendations Class
 */
class RecEngine_Recommendations {

	/**
	 * Single instance
	 *
	 * @var RecEngine_Recommendations
	 */
	private static $instance = null;

	/**
	 * API client
	 *
	 * @var RecEngine_API_Client
	 */
	private $api_client;

	/**
	 * Get instance
	 *
	 * @return RecEngine_Recommendations
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
		$this->api_client = RecEngine_API_Client::get_instance();
		$this->init_hooks();
	}

	/**
	 * Initialize hooks
	 */
	private function init_hooks() {
		// AJAX endpoints.
		add_action( 'wp_ajax_recengine_get_recommendations', array( $this, 'ajax_get_recommendations' ) );
		add_action( 'wp_ajax_nopriv_recengine_get_recommendations', array( $this, 'ajax_get_recommendations' ) );
	}

	/**
	 * Get recommendations for current user
	 *
	 * @param array $args Arguments.
	 * @return array Recommendations
	 */
	public function get_recommendations( $args = array() ) {
		$defaults = array(
			'count'   => 10,
			'context' => array(),
			'exclude' => array(), // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude -- External API parameter, not WordPress query
		);

		$args = wp_parse_args( $args, $defaults );

		// Get user ID.
		$user_id = is_user_logged_in() ? get_current_user_id() : null;

		// Add current page context.
		if ( is_singular() ) {
			$args['context']['page_type'] = 'single';
			$args['context']['post_id']   = get_the_ID();
			$args['context']['post_type'] = get_post_type();
		} elseif ( is_archive() ) {
			$args['context']['page_type'] = 'archive';
		} elseif ( is_home() ) {
			$args['context']['page_type'] = 'home';
		}

		// Add device context.
		$args['context']['device'] = wp_is_mobile() ? 'mobile' : 'desktop';

		// Get recommendations from API.
		$response = $this->api_client->get_recommendations(
			$user_id,
			'',
			$args['context'],
			$args['count'],
			$args['exclude']
		);

		if ( is_wp_error( $response ) ) {
			error_log( 'RecEngine: Failed to get recommendations - ' . $response->get_error_message() );
			return array();
		}

		if ( ! isset( $response['recommendations'] ) ) {
			return array();
		}

		return $response['recommendations'];
	}

	/**
	 * Render recommendations widget
	 *
	 * @param array $args Widget arguments.
	 * @return string HTML output
	 */
	public function render_recommendations( $args = array() ) {
		$defaults = array(
			'title'       => __( 'Recommended for You', 'first8marketing-recommendation-engine' ),
			'count'       => 4,
			'layout'      => 'grid', // Grid, list, carousel.
			'show_price'  => true,
			'show_rating' => true,
		);

		$args = wp_parse_args( $args, $defaults );

		$recommendations = $this->get_recommendations( array( 'count' => $args['count'] ) );

		if ( empty( $recommendations ) ) {
			return '';
		}

		ob_start();
		include RECENGINE_WP_PLUGIN_DIR . 'templates/recommendations-widget.php';
		return ob_get_clean();
	}

	/**
	 * AJAX handler for getting recommendations
	 */
	public function ajax_get_recommendations() {
		check_ajax_referer( 'recengine_wp_nonce', 'nonce' );

		$count   = isset( $_POST['count'] ) ? intval( $_POST['count'] ) : 10;
		$context = isset( $_POST['context'] ) ? (array) map_deep( wp_unslash( $_POST['context'] ), 'sanitize_text_field' ) : array();

		$recommendations = $this->get_recommendations(
			array(
				'count'   => $count,
				'context' => $context,
			)
		);

		wp_send_json_success(
			array(
				'recommendations' => $recommendations,
			)
		);
	}
}
