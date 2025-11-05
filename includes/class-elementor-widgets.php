<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName -- Legacy filename.
/**
 * Elementor Widgets Handler
 *
 * @package First8Marketing_Recommendation_Engine
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * RecEngine_Elementor_Widgets Class
 */
class RecEngine_Elementor_Widgets {
	/**
	 * Single instance
	 *
	 * @var RecEngine_Elementor_Widgets
	 */
	private static $instance = null;

	/**
	 * Get instance
	 *
	 * @return RecEngine_Elementor_Widgets
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
		add_action( 'elementor/widgets/register', array( $this, 'register_widgets' ) );
		add_action( 'elementor/elements/categories_registered', array( $this, 'add_elementor_category' ) );
	}

	/**
	 * Add Elementor category
	 *
	 * @param object $elements_manager Elementor elements manager.
	 */
	public function add_elementor_category( $elements_manager ) {
		$elements_manager->add_category(
			'recengine',
			array(
				'title' => __( 'First8 Marketing', 'first8marketing-recommendation-engine' ),
				'icon'  => 'fa fa-plug',
			)
		);
	}

	/**
	 * Register Elementor widgets
	 *
	 * @param object $widgets_manager Elementor widgets manager.
	 */
	public function register_widgets( $widgets_manager ) {
		require_once RECENGINE_WP_PLUGIN_DIR . 'includes/elementor/conditional-content-widget.php';
		require_once RECENGINE_WP_PLUGIN_DIR . 'includes/elementor/recommendations-widget.php';

		$widgets_manager->register( new RecEngine_Elementor_Conditional_Content_Widget() );
		$widgets_manager->register( new RecEngine_Elementor_Recommendations_Widget() );
	}
}
