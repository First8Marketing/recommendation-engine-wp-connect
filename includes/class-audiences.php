<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName -- Legacy filename.
/**
 * Audiences/Segments Handler
 *
 * @package First8Marketing_Recommendation_Engine
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * RecEngine_Audiences Class
 */
class RecEngine_Audiences {
	/**
	 * Single instance
	 *
	 * @var RecEngine_Audiences
	 */
	private static $instance = null;

	/**
	 * Get instance
	 *
	 * @return RecEngine_Audiences
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
		add_action( 'init', array( $this, 'register_taxonomy' ) );
		add_shortcode( 'recengine_audience', array( $this, 'audience_shortcode' ) );
		add_action( 'wp_ajax_recengine_assign_audience', array( $this, 'ajax_assign_audience' ) );
		add_action( 'wp_ajax_nopriv_recengine_assign_audience', array( $this, 'ajax_assign_audience' ) );
	}

	/**
	 * Register Audiences Taxonomy
	 */
	public function register_taxonomy() {
		$labels = array(
			'name'              => _x( 'Audiences', 'taxonomy general name', 'first8marketing-recommendation-engine' ),
			'singular_name'     => _x( 'Audience', 'taxonomy singular name', 'first8marketing-recommendation-engine' ),
			'search_items'      => __( 'Search Audiences', 'first8marketing-recommendation-engine' ),
			'all_items'         => __( 'All Audiences', 'first8marketing-recommendation-engine' ),
			'parent_item'       => __( 'Parent Audience', 'first8marketing-recommendation-engine' ),
			'parent_item_colon' => __( 'Parent Audience:', 'first8marketing-recommendation-engine' ),
			'edit_item'         => __( 'Edit Audience', 'first8marketing-recommendation-engine' ),
			'update_item'       => __( 'Update Audience', 'first8marketing-recommendation-engine' ),
			'add_new_item'      => __( 'Add New Audience', 'first8marketing-recommendation-engine' ),
			'new_item_name'     => __( 'New Audience Name', 'first8marketing-recommendation-engine' ),
			'menu_name'         => __( 'Audiences', 'first8marketing-recommendation-engine' ),
		);

		$args = array(
			'hierarchical'      => true,
			'labels'            => $labels,
			'show_ui'           => true,
			'show_admin_column' => true,
			'query_var'         => true,
			'rewrite'           => array( 'slug' => 'audience' ),
			'show_in_rest'      => true,
		);

		register_taxonomy( 'recengine_audience', array( 'recengine_trigger' ), $args );
	}

	/**
	 * Assign user to audience
	 *
	 * @param int    $user_id     User ID.
	 * @param string $audience_id Audience ID.
	 */
	public function assign_user_to_audience( $user_id, $audience_id ) {
		$audiences = get_user_meta( $user_id, 'recengine_audiences', true );
		if ( ! is_array( $audiences ) ) {
			$audiences = array();
		}

		if ( ! in_array( $audience_id, $audiences, true ) ) {
			$audiences[] = $audience_id;
			update_user_meta( $user_id, 'recengine_audiences', $audiences );
		}
	}

	/**
	 * Remove user from audience
	 *
	 * @param int    $user_id     User ID.
	 * @param string $audience_id Audience ID.
	 */
	public function remove_user_from_audience( $user_id, $audience_id ) {
		$audiences = get_user_meta( $user_id, 'recengine_audiences', true );
		if ( ! is_array( $audiences ) ) {
			return;
		}

		$key = array_search( $audience_id, $audiences, true );
		if ( false !== $key ) {
			unset( $audiences[ $key ] );
			update_user_meta( $user_id, 'recengine_audiences', array_values( $audiences ) );
		}
	}

	/**
	 * Check if user is in audience
	 *
	 * @param int    $user_id     User ID.
	 * @param string $audience_id Audience ID.
	 * @return bool
	 */
	public function is_user_in_audience( $user_id, $audience_id ) {
		$audiences = get_user_meta( $user_id, 'recengine_audiences', true );
		if ( ! is_array( $audiences ) ) {
			return false;
		}

		return in_array( $audience_id, $audiences, true );
	}

	/**
	 * Audience shortcode - show content only to specific audience
	 *
	 * @param array  $atts    Shortcode attributes.
	 * @param string $content Shortcode content.
	 * @return string
	 */
	public function audience_shortcode( $atts, $content = '' ) {
		$atts = shortcode_atts(
			array(
				'id' => '',
			),
			$atts
		);

		if ( ! is_user_logged_in() ) {
			return '';
		}

		$user_id     = get_current_user_id();
		$audience_id = sanitize_text_field( $atts['id'] );

		if ( ! $audience_id || ! $this->is_user_in_audience( $user_id, $audience_id ) ) {
			return '';
		}

		return do_shortcode( $content );
	}

	/**
	 * AJAX handler for assigning user to audience
	 */
	public function ajax_assign_audience() {
		check_ajax_referer( 'recengine_wp_nonce', 'nonce' );

		if ( ! is_user_logged_in() ) {
			wp_send_json_error( array( 'message' => __( 'User not logged in', 'first8marketing-recommendation-engine' ) ) );
		}

		$user_id     = get_current_user_id();
		$audience_id = isset( $_POST['audience_id'] ) ? sanitize_text_field( wp_unslash( $_POST['audience_id'] ) ) : '';

		if ( ! $audience_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid audience ID', 'first8marketing-recommendation-engine' ) ) );
		}

		$this->assign_user_to_audience( $user_id, $audience_id );

		wp_send_json_success( array( 'message' => __( 'User assigned to audience', 'first8marketing-recommendation-engine' ) ) );
	}
}
