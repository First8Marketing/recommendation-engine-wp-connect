<?php
/**
 * Generic Commerce Integration
 * 
 * Fallback implementation for sites without e-commerce platforms
 * Provides empty implementations that gracefully handle missing e-commerce functionality
 *
 * @package First8Marketing_Recommendation_Engine
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Generic Commerce Integration Class
 */
class RecEngine_Generic_Commerce_Integration implements RecEngine_Commerce_Integration_Interface {
	/**
	 * Single instance
	 *
	 * @var RecEngine_Generic_Commerce_Integration
	 */
	private static $instance = null;

	/**
	 * Get instance
	 *
	 * @return RecEngine_Generic_Commerce_Integration
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
		// No initialization needed for generic integration
	}

	/**
	 * Check if the e-commerce platform is available
	 *
	 * @return bool Always false for generic integration
	 */
	public function is_available() {
		return false;
	}

	/**
	 * Get product information
	 *
	 * @param int|string $product_id Product ID
	 * @return array|null Always null for generic integration
	 */
	public function get_product( $product_id ) {
		return null;
	}

	/**
	 * Get current cart contents
	 *
	 * @return array Empty array for generic integration
	 */
	public function get_cart() {
		return array();
	}

	/**
	 * Get current user's order history
	 *
	 * @return array Empty array for generic integration
	 */
	public function get_order_history() {
		return array();
	}

	/**
	 * Track product view event
	 *
	 * @param mixed $product Product data
	 * @return void
	 */
	public function track_product_view( $product ) {
		// No tracking in generic mode
	}

	/**
	 * Track add to cart event
	 *
	 * @param mixed $product Product data
	 * @param int $quantity Quantity
	 * @return void
	 */
	public function track_add_to_cart( $product, $quantity ) {
		// No tracking in generic mode
	}

	/**
	 * Track purchase event
	 *
	 * @param mixed $order Order data
	 * @return void
	 */
	public function track_purchase( $order ) {
		// No tracking in generic mode
	}

	/**
	 * Get product recommendations context
	 *
	 * @return array Empty context data
	 */
	public function get_recommendation_context() {
		return array(
			'has_commerce' => false,
			'platform' => 'none',
			'cart_items' => array(),
			'recent_products' => array(),
		);
	}

	/**
	 * Render product recommendations
	 *
	 * @param array $recommendations Recommendations data
	 * @param string $context Display context
	 * @return string Empty string or fallback content
	 */
	public function render_recommendations( $recommendations, $context = 'general' ) {
		// For generic mode, we can still show content recommendations
		if ( ! empty( $recommendations ) && is_array( $recommendations ) ) {
			ob_start();
			?>
			<div class="recengine-generic-recommendations recengine-context-<?php echo esc_attr( $context ); ?>">
				<?php if ( 'product' === $context ) : ?>
					<h3><?php esc_html_e( 'You Might Also Like', 'first8marketing-recommendation-engine' ); ?></h3>
				<?php elseif ( 'cart' === $context ) : ?>
					<h3><?php esc_html_e( 'Complete Your Experience', 'first8marketing-recommendation-engine' ); ?></h3>
				<?php else : ?>
					<h3><?php esc_html_e( 'Recommended for You', 'first8marketing-recommendation-engine' ); ?></h3>
				<?php endif; ?>
				
				<div class="recengine-recommendations-grid">
					<?php foreach ( $recommendations as $index => $recommendation ) : ?>
						<?php if ( $index < 4 ) : // Limit to 4 recommendations ?>
							<div class="recengine-recommendation-item">
								<?php if ( ! empty( $recommendation['image'] ) ) : ?>
									<div class="recengine-image">
										<img src="<?php echo esc_url( $recommendation['image'] ); ?>" alt="<?php echo esc_attr( $recommendation['title'] ); ?>">
									</div>
								<?php endif; ?>
								<div class="recengine-content">
									<h4><?php echo esc_html( $recommendation['title'] ); ?></h4>
									<?php if ( ! empty( $recommendation['description'] ) ) : ?>
										<p><?php echo esc_html( $recommendation['description'] ); ?></p>
									<?php endif; ?>
									<?php if ( ! empty( $recommendation['url'] ) ) : ?>
										<a href="<?php echo esc_url( $recommendation['url'] ); ?>" class="recengine-cta-button">
											<?php esc_html_e( 'Learn More', 'first8marketing-recommendation-engine' ); ?>
										</a>
									<?php endif; ?>
								</div>
							</div>
						<?php endif; ?>
					<?php endforeach; ?>
				</div>
			</div>
			<?php
			return ob_get_clean();
		}

		return '';
	}

	/**
	 * Get integration platform name
	 *
	 * @return string
	 */
	public function get_platform_name() {
		return 'generic';
	}

	/**
	 * Get integration display name
	 *
	 * @return string
	 */
	public function get_display_name() {
		return __( 'Generic Content', 'first8marketing-recommendation-engine' );
	}
}