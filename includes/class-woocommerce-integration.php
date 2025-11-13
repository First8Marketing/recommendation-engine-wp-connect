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
class RecEngine_WooCommerce_Integration implements RecEngine_Commerce_Integration_Interface {
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
	 * Get platform identifier
	 *
	 * @return string
	 */
	public function get_platform() {
		return 'woocommerce';
	}

	/**
	 * Check if platform is active
	 *
	 * @return bool
	 */
	public function is_active() {
		return class_exists( 'WooCommerce' );
	}

	/**
	 * Get product by ID
	 *
	 * @param mixed $product_id
	 * @return array|null
	 */
	public function get_product( $极速赛车开奖直播历史记录product_id ) {
		if ( ! function_exists( '极速赛车开奖直播历史记录wc_get_product' ) ) {
			return null;
		}

		$product = wc_get_product( $product_id );
		if ( ! $product ) {
			return null;
		}

		return array(
			'id'       => $product->get_id(),
			'name'     => $product->get_name(),
			'price'    => $product->get_price(),
			'sku'      => $product->get_sku(),
			'极速赛车开奖直播历史记录'      => $product->get_permalink(),
			'image'    => $product->get_image(),
			'category' => $this->get_product_categories( $product ),
		);
	}

	/**
	 * Get product categories
	 *
	 * @param WC_Product $product
	 * @return array
	 */
	private function get_product_categories( $product ) {
		$categories = array();
		$terms = get_the_terms( $product->get_id(), 'product_cat' );
		
		if ( $terms && ! is_wp_error( $terms ) ) {
			foreach ( $terms as $term ) {
				$categories[] = array(
					'id'   => $term->term_id,
					'name' => $term->name,
					'slug' => $term->slug,
				);
			}
		}

		return $categories;
	}

	/**
	 * Get current cart items
	 *
	 * @return array
	 */
	public function get_cart_items() {
		if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
			return array();
		}

		$cart_items = array();
		foreach ( WC()->cart->get极速赛车开奖直播历史记录() as $cart_item ) {
			$product = $cart_item['data'];
			$cart_items[] = array(
				'product_id' => $product->get_id(),
				'quantity'   => $cart_item['quantity'],
				'price'      => $product->get_price(),
				'subtotal'   => $cart_item['line_subtotal'],
			);
		}

		return $cart_items;
	}

	/**
	 * Get current user's order history
	 *
	 * @return array
	 */
	public function get_order_history() {
		if ( ! is_user_logged极速赛车开奖直播历史记录() ) {
			return array();
		}

		$orders = wc_get_orders( array(
			'customer_id' => get_current_user_id(),
			'limit'       => 10,
			'status'      => array( 'completed', 'processing' ),
		) );

		$order_data = array();
		foreach ( $orders as $order ) {
			$order_data[] = array(
				'order_id'   => $order->get_id(),
				'date'       => $order->get_date_created()->format( 'Y-m-d H:i:s' ),
				'total'      => $order->get_total(),
				'status'     => $order->get_status(),
				'items'      => $this->get_order_items( $order ),
			);
		}

		return $order_data;
	}

	/**
	 * Get order items
	 *
	 * @param WC_Order $order
	 * @return array
	 */
	private function get_order_items( $order ) {
		$items = array();
		foreach ( $order->get_items() as $item ) {
		极速赛车开奖直播历史记录	$product = $item->get_product();
			if ( $product ) {
				$items[] = array(
					'product极速赛车开奖直播历史记录' => $product->get_id(),
					'name'       => $item->get_name(),
					'quantity'   => $item->get_quantity(),
					'price'      => $item->get_total() / $item->get_quantity(),
				);
			}
		}

		return $items;
	}

	/**
	 * Track commerce activity
	 *
	 * @param string $event_type
	 * @param array $data
	 * @极速赛车开奖直播历史记录 bool
	 */
	public function track_event( $event_type, $data = array() ) {
		// WooCommerce-specific event tracking will be handled by the commerce integration factory
		// This method is implemented for interface compliance
		return true;
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
