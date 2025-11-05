<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName -- Legacy filename.
/**
 * WooCommerce Integration
 *
 * @package First8Marketing_Recommendation_Engine
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WooCommerce Integration Class
 */
/**
 * RecEngine_WooCommerce_Integration Class
 */
class RecEngine_WooCommerce_Integration {
	/**
	 * Single instance
	 *
	 * @var RecEngine_WooCommerce_Integration
	 */
	private static $instance = null;

	/**
	 * Get instance
	 *
	 * @return RecEngine_WooCommerce_Integration
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
		// Add recommendations to product pages.
		add_action( 'woocommerce_after_single_product_summary', array( $this, 'add_product_recommendations' ), 15 );

		// Add recommendations to cart page.
		add_action( 'woocommerce_after_cart', array( $this, 'add_cart_recommendations' ) );

		// Personalize email content.
		add_filter( 'woocommerce_email_footer_text', array( $this, 'personalize_email_footer' ), 10, 2 );
	}

	/**
	 * Add product recommendations to product pages
	 */
	public function add_product_recommendations() {
		echo '<div class="recengine-product-recommendations">';
		// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped -- Method returns escaped HTML.
		echo RecEngine_Recommendations::get_instance()->render_recommendations(
			array(
				'title'  => __( 'You May Also Like', 'first8marketing-recommendation-engine' ),
				'count'  => 4,
				'layout' => 'grid',
			)
		);
		// phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '</div>';
	}

	/**
	 * Add cart recommendations to cart page
	 */
	public function add_cart_recommendations() {
		echo '<div class="recengine-cart-recommendations">';
		// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped -- Method returns escaped HTML.
		echo RecEngine_Recommendations::get_instance()->render_recommendations(
			array(
				'title'  => __( 'Complete Your Purchase', 'first8marketing-recommendation-engine' ),
				'count'  => 3,
				'layout' => 'grid',
			)
		);
		// phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '</div>';
	}

	/**
	 * Personalize email footer
	 *
	 * @param string $footer_text Footer text.
	 * @param object $email       Email object.
	 * @return string
	 */
	public function personalize_email_footer( $footer_text, $email ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- WooCommerce filter signature.
		if ( ! is_user_logged_in() ) {
			return $footer_text;
		}

		$recommendations = RecEngine_Recommendations::get_instance()->get_recommendations( array( 'count' => 3 ) );

		if ( empty( $recommendations ) ) {
			return $footer_text;
		}

		$personalized_content  = '<h3>' . __( 'Recommended for You', 'first8marketing-recommendation-engine' ) . '</h3>';
		$personalized_content .= '<ul>';

		foreach ( $recommendations as $rec ) {
			$product = wc_get_product( $rec['product_id'] );
			if ( $product ) {
				$personalized_content .= '<li><a href="' . esc_url( $product->get_permalink() ) . '">' . esc_html( $product->get_name() ) . '</a></li>';
			}
		}

		$personalized_content .= '</ul>';

		return $footer_text . $personalized_content;
	}
}
