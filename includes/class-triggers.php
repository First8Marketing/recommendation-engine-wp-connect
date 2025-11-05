<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName -- Legacy filename.
/**
 * Triggers Custom Post Type Handler
 *
 * @package First8Marketing_Recommendation_Engine
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * RecEngine_Triggers Class
 */
class RecEngine_Triggers {
	/**
	 * Single instance
	 *
	 * @var RecEngine_Triggers
	 */
	private static $instance = null;

	/**
	 * Get instance
	 *
	 * @return RecEngine_Triggers
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
		add_action( 'init', array( $this, 'register_post_type' ) );
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
		add_action( 'save_post_recengine_trigger', array( $this, 'save_trigger_meta' ) );
		add_shortcode( 'recengine_trigger', array( $this, 'trigger_shortcode' ) );
	}

	/**
	 * Register Triggers Custom Post Type
	 */
	public function register_post_type() {
		$labels = array(
			'name'               => _x( 'Triggers', 'post type general name', 'first8marketing-recommendation-engine' ),
			'singular_name'      => _x( 'Trigger', 'post type singular name', 'first8marketing-recommendation-engine' ),
			'add_new'            => _x( 'Add New', 'trigger', 'first8marketing-recommendation-engine' ),
			'add_new_item'       => __( 'Add New Trigger', 'first8marketing-recommendation-engine' ),
			'edit_item'          => __( 'Edit Trigger', 'first8marketing-recommendation-engine' ),
			'new_item'           => __( 'New Trigger', 'first8marketing-recommendation-engine' ),
			'all_items'          => __( 'All Triggers', 'first8marketing-recommendation-engine' ),
			'view_item'          => __( 'View Trigger', 'first8marketing-recommendation-engine' ),
			'search_items'       => __( 'Search Triggers', 'first8marketing-recommendation-engine' ),
			'not_found'          => __( 'No Triggers found', 'first8marketing-recommendation-engine' ),
			'not_found_in_trash' => __( 'No Triggers found in Trash', 'first8marketing-recommendation-engine' ),
			'menu_name'          => __( 'Triggers', 'first8marketing-recommendation-engine' ),
		);

		$args = array(
			'labels'              => $labels,
			'description'         => __( 'Conditional content triggers', 'first8marketing-recommendation-engine' ),
			'public'              => true,
			'exclude_from_search' => true,
			'publicly_queryable'  => false,
			'show_ui'             => true,
			'show_in_menu'        => 'recengine-settings',
			'menu_position'       => 90,
			'menu_icon'           => 'dashicons-randomize',
			'supports'            => array( 'title', 'editor' ),
			'has_archive'         => false,
			'rewrite'             => false,
			'capability_type'     => 'post',
			'show_in_rest'        => true,
		);

		register_post_type( 'recengine_trigger', $args );
	}

	/**
	 * Add meta boxes
	 */
	public function add_meta_boxes() {
		add_meta_box(
			'recengine_trigger_conditions',
			__( 'Trigger Conditions', 'first8marketing-recommendation-engine' ),
			array( $this, 'render_conditions_meta_box' ),
			'recengine_trigger',
			'normal',
			'high'
		);

		add_meta_box(
			'recengine_trigger_versions',
			__( 'Content Versions', 'first8marketing-recommendation-engine' ),
			array( $this, 'render_versions_meta_box' ),
			'recengine_trigger',
			'normal',
			'high'
		);

		add_meta_box(
			'recengine_trigger_shortcode',
			__( 'Shortcode', 'first8marketing-recommendation-engine' ),
			array( $this, 'render_shortcode_meta_box' ),
			'recengine_trigger',
			'side',
			'high'
		);
	}

	/**
	 * Render conditions meta box
	 *
	 * @param WP_Post $post Post object.
	 */
	public function render_conditions_meta_box( $post ) {
		wp_nonce_field( 'recengine_trigger_meta', 'recengine_trigger_nonce' );

		$conditions = get_post_meta( $post->ID, '_recengine_conditions', true );
		if ( ! is_array( $conditions ) ) {
			$conditions = array();
		}

		include RECENGINE_WP_PLUGIN_DIR . 'admin/partials/trigger-conditions.php';
	}

	/**
	 * Render versions meta box
	 *
	 * @param WP_Post $post Post object.
	 */
	public function render_versions_meta_box( $post ) {
		$versions = get_post_meta( $post->ID, '_recengine_versions', true );
		if ( ! is_array( $versions ) ) {
			$versions = array();
		}

		include RECENGINE_WP_PLUGIN_DIR . 'admin/partials/trigger-versions.php';
	}

	/**
	 * Render shortcode meta box
	 *
	 * @param WP_Post $post Post object.
	 */
	public function render_shortcode_meta_box( $post ) {
		$shortcode = '[recengine_trigger id="' . $post->ID . '"]';
		echo '<p><strong>' . esc_html__( 'Use this shortcode:', 'first8marketing-recommendation-engine' ) . '</strong></p>';
		echo '<input type="text" readonly value="' . esc_attr( $shortcode ) . '" style="width:100%;" onclick="this.select();" />';
		echo '<p class="description">' . esc_html__( 'Copy and paste this shortcode anywhere to display conditional content.', 'first8marketing-recommendation-engine' ) . '</p>';
	}

	/**
	 * Save trigger meta
	 *
	 * @param int $post_id Post ID.
	 */
	public function save_trigger_meta( $post_id ) {
		if ( ! isset( $_POST['recengine_trigger_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['recengine_trigger_nonce'] ) ), 'recengine_trigger_meta' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// Save conditions.
		if ( isset( $_POST['recengine_conditions'] ) ) {
			$conditions = map_deep( wp_unslash( $_POST['recengine_conditions'] ), 'sanitize_text_field' );
			update_post_meta( $post_id, '_recengine_conditions', $conditions );
		}

		// Save versions.
		if ( isset( $_POST['recengine_versions'] ) ) {
			$versions     = array();
			$raw_versions = map_deep( wp_unslash( $_POST['recengine_versions'] ), 'sanitize_text_field' );
			foreach ( $raw_versions as $version ) {
				if ( ! is_array( $version ) ) {
					continue;
				}
				$versions[] = array(
					'content'   => isset( $version['content'] ) ? wp_kses_post( $version['content'] ) : '',
					'condition' => isset( $version['condition'] ) ? sanitize_text_field( $version['condition'] ) : '',
				);
			}
			update_post_meta( $post_id, '_recengine_versions', $versions );
		}
	}

	/**
	 * Trigger shortcode handler
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public function trigger_shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'id' => 0,
			),
			$atts
		);

		$trigger_id = intval( $atts['id'] );
		if ( ! $trigger_id ) {
			return '';
		}

		$trigger = get_post( $trigger_id );
		if ( ! $trigger || 'recengine_trigger' !== $trigger->post_type ) {
			return '';
		}

		// Get conditions and versions.
		$conditions = get_post_meta( $trigger_id, '_recengine_conditions', true );
		$versions   = get_post_meta( $trigger_id, '_recengine_versions', true );

		if ( ! is_array( $conditions ) || ! is_array( $versions ) ) {
			return $trigger->post_content;
		}

		// Evaluate conditions and return matching content.
		$content = $this->evaluate_trigger( $conditions, $versions, $trigger->post_content );

		// Track analytics.
		$this->track_trigger_view( $trigger_id, $content );

		return do_shortcode( $content );
	}

	/**
	 * Evaluate trigger conditions
	 *
	 * @param array  $conditions Trigger conditions.
	 * @param array  $versions   Content versions.
	 * @param string $default_content Default content.
	 * @return string
	 */
	private function evaluate_trigger( $conditions, $versions, $default_content ) {
		require_once RECENGINE_WP_PLUGIN_DIR . 'includes/conditions/class-condition-evaluator.php';

		$evaluator = new RecEngine_Condition_Evaluator();

		foreach ( $versions as $version ) {
			if ( isset( $version['condition'] ) && $evaluator->evaluate( $version['condition'], $conditions ) ) {
				return $version['content'];
			}
		}

		return $default_content;
	}

	/**
	 * Track trigger view for analytics
	 *
	 * @param int    $trigger_id Trigger ID.
	 * @param string $content    Content shown.
	 */
	private function track_trigger_view( $trigger_id, $content ) {
		$views = get_post_meta( $trigger_id, '_recengine_trigger_views', true );
		if ( ! is_array( $views ) ) {
			$views = array();
		}

		$content_hash = md5( $content );
		if ( ! isset( $views[ $content_hash ] ) ) {
			$views[ $content_hash ] = 0;
		}

		++$views[ $content_hash ];
		update_post_meta( $trigger_id, '_recengine_trigger_views', $views );
	}
}
