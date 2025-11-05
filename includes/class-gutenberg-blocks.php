<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName -- Legacy filename.
/**
 * Gutenberg Blocks Handler
 *
 * @package First8Marketing_Recommendation_Engine
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * RecEngine_Gutenberg_Blocks Class
 */
class RecEngine_Gutenberg_Blocks {
	/**
	 * Single instance
	 *
	 * @var RecEngine_Gutenberg_Blocks
	 */
	private static $instance = null;

	/**
	 * Get instance
	 *
	 * @return RecEngine_Gutenberg_Blocks
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
		add_action( 'init', array( $this, 'register_blocks' ) );
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_block_editor_assets' ) );
	}

	/**
	 * Register Gutenberg blocks
	 */
	public function register_blocks() {
		// Register conditional content block.
		register_block_type(
			'recengine/conditional-content',
			array(
				'editor_script'   => 'recengine-blocks',
				'render_callback' => array( $this, 'render_conditional_block' ),
				'attributes'      => array(
					'condition'      => array(
						'type'    => 'string',
						'default' => 'logged_in',
					),
					'conditionValue' => array(
						'type'    => 'string',
						'default' => '',
					),
					'content'        => array(
						'type'    => 'string',
						'default' => '',
					),
				),
			)
		);

		// Register recommendations block.
		register_block_type(
			'recengine/recommendations',
			array(
				'editor_script'   => 'recengine-blocks',
				'render_callback' => array( $this, 'render_recommendations_block' ),
				'attributes'      => array(
					'count'  => array(
						'type'    => 'number',
						'default' => 4,
					),
					'title'  => array(
						'type'    => 'string',
						'default' => 'Recommended for You',
					),
					'layout' => array(
						'type'    => 'string',
						'default' => 'grid',
					),
				),
			)
		);
	}

	/**
	 * Enqueue block editor assets
	 */
	public function enqueue_block_editor_assets() {
		wp_enqueue_script(
			'recengine-blocks',
			RECENGINE_WP_PLUGIN_URL . 'blocks/build/index.js',
			array( 'wp-blocks', 'wp-element', 'wp-editor', 'wp-components', 'wp-i18n' ),
			RECENGINE_WP_VERSION,
			true
		);

		wp_enqueue_style(
			'recengine-blocks-editor',
			RECENGINE_WP_PLUGIN_URL . 'blocks/build/editor.css',
			array( 'wp-edit-blocks' ),
			RECENGINE_WP_VERSION
		);
	}

	/**
	 * Render conditional content block
	 *
	 * @param array $attributes Block attributes.
	 * @return string
	 */
	public function render_conditional_block( $attributes ) {
		$condition       = isset( $attributes['condition'] ) ? $attributes['condition'] : 'logged_in';
		$condition_value = isset( $attributes['conditionValue'] ) ? $attributes['conditionValue'] : '';
		$content         = isset( $attributes['content'] ) ? $attributes['content'] : '';

		require_once RECENGINE_WP_PLUGIN_DIR . 'includes/conditions/class-condition-evaluator.php';

		$evaluator  = new RecEngine_Condition_Evaluator();
		$conditions = array();

		// Build conditions array based on condition type.
		if ( 'user_role' === $condition ) {
			$conditions['roles'] = array( $condition_value );
		} elseif ( 'device_type' === $condition ) {
			$conditions['device'] = $condition_value;
		}

		if ( $evaluator->evaluate( $condition, $conditions ) ) {
			return do_shortcode( $content );
		}

		return '';
	}

	/**
	 * Render recommendations block
	 *
	 * @param array $attributes Block attributes.
	 * @return string
	 */
	public function render_recommendations_block( $attributes ) {
		$count  = isset( $attributes['count'] ) ? intval( $attributes['count'] ) : 4;
		$title  = isset( $attributes['title'] ) ? sanitize_text_field( $attributes['title'] ) : __( 'Recommended for You', 'first8marketing-recommendation-engine' );
		$layout = isset( $attributes['layout'] ) ? sanitize_text_field( $attributes['layout'] ) : 'grid';

		return RecEngine_Recommendations::get_instance()->render_recommendations(
			array(
				'count'  => $count,
				'title'  => $title,
				'layout' => $layout,
			)
		);
	}
}
