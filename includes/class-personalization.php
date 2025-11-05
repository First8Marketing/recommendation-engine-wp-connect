<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName -- Legacy filename.
/**
 * Personalization Handler
 *
 * @package First8Marketing_Recommendation_Engine
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * RecEngine_Personalization Class
 */
class RecEngine_Personalization {
	/**
	 * Single instance
	 *
	 * @var RecEngine_Personalization
	 */
	private static $instance = null;

	/**
	 * Get instance
	 *
	 * @return RecEngine_Personalization
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
		add_filter( 'the_content', array( $this, 'personalize_content' ), 20 );
		add_action( 'wp_head', array( $this, 'add_personalization_meta' ) );
	}

	/**
	 * Personalize Content
	 *
	 * @param string $content Post content.
	 * @return string
	 */
	public function personalize_content( $content ) {
		// Add personalized content blocks.
		if ( is_singular( 'post' ) || is_singular( 'page' ) ) {
			$recommendations = RecEngine_Recommendations::get_instance()->render_recommendations(
				array(
					'count' => 3,
					'title' => __( 'You Might Also Like', 'first8marketing-recommendation-engine' ),
				)
			);

			if ( ! empty( $recommendations ) ) {
				$content .= '<div class="recengine-personalized-content">' . $recommendations . '</div>';
			}
		}

		return $content;
	}

	/**
	 * Add Personalization Meta
	 */
	public function add_personalization_meta() {
		$user_id = is_user_logged_in() ? get_current_user_id() : 'anonymous';
		echo '<meta name="recengine-user-id" content="' . esc_attr( $user_id ) . '">' . "\n";
	}
}
