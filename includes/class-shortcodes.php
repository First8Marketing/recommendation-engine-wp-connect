<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName -- Legacy filename.
/**
 * Shortcodes Handler
 *
 * @package First8Marketing_Recommendation_Engine
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * RecEngine_Shortcodes Class
 */
class RecEngine_Shortcodes {
	/**
	 * Single instance
	 *
	 * @var RecEngine_Shortcodes
	 */
	private static $instance = null;

	/**
	 * Get instance
	 *
	 * @return RecEngine_Shortcodes
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 *   Construct
	 */
	private function __construct() {
		add_shortcode( 'recengine_recommendations', array( $this, 'recommendations_shortcode' ) );
		add_shortcode( 'recengine_personalized', array( $this, 'personalized_shortcode' ) );
	}

	/**
	 * Recommendations Shortcode
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public function recommendations_shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'count'  => 4,
				'title'  => __( 'Recommended for You', 'first8marketing-recommendation-engine' ),
				'layout' => 'grid',
			),
			$atts
		);

		return RecEngine_Recommendations::get_instance()->render_recommendations( $atts );
	}

	/**
	 * Personalized Shortcode
	 *
	 * @param array  $atts    Shortcode attributes.
	 * @param string $content Shortcode content.
	 * @return string
	 */
	public function personalized_shortcode( $atts, $content = '' ) {
		if ( ! is_user_logged_in() ) {
			return '';
		}

		return '<div class="recengine-personalized">' . do_shortcode( $content ) . '</div>';
	}
}
