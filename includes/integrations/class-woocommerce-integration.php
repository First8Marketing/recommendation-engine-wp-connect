<?php
/**
 * WooCommerce Integration
 * 
 * WooCommerce-specific implementation of the commerce integration interface
 *
 * @package First8Marketing_Recommendation_Engine
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WooCommerce Integration Class
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
		// Initialize WooCommerce-specific hooks
		$this->init_hooks();
	}

	/**
	 * Initialize WooCommerce hooks
	 */
	private function init_hooks() {
		// Add recommendations to product pages
		add_action( 'woocommerce_after_single_product_summary', array( $this, 'add_product_recommendations' ), 15 );

		// Add recommendations to cart page
		add_action( 'woocommerce_after_cart', array( $this, 'add_cart_recommendations' ) );

		// Personalize email content
		add_filter( 'woocommerce_email_footer_text', array( $this, 'personalize_email_footer' ), 10, 2 );
	}

	/**
	 * Check if the e-commerce platform is available
	 *
	 * @return bool
	 */
	public function is_available() {
		return class_exists( 'WooCommerce' ) && function_exists( 'wc_get_product' );
	}

	/**
	 * Get product information
	 *
	 * @param int|string $product_id Product ID
	 * @return array|null Product data or null if not found
	 */
	public function get_product( $product_id ) {
		$product = wc_get_product( $product_id );
		
		if ( ! $product ) {
			return null;
		}

		return array(
			'id' => $product->get_id(),
			'name' => $product->get_name(),
			'price' => $product->get_price(),
			'regular_price' => $product->get_regular_price(),
			'sale_price' => $product->get_sale_price(),
			'description' => $product->get_description(),
			'short_description' => $product->get_short(),
			'sku' => $product->get_sku(),
			'stock_status' => $product->get_stock_status(),
			'stock_quantity' => $product->get_stock_quantity(),
			'weight' => $product->get_weight(),
			'dimensions' => $product->get_dimensions( false ),
			'categories' => $this->get_product_categories( $product ),
			'tags' => $this->get_product_tags( $product ),
			'attributes' => $this->get_product_attributes( $product ),
			'image_url' => wp_get_attachment_image_url( $product->get_image_id(), 'medium' ),
			'gallery_images' => $this->get_gallery_images( $product ),
			'rating_count' => $product->get_rating_count(),
			'average_rating' => $product->get_average_rating(),
			'review_count' => $product->get_review_count(),
			'permalink' => $product->get_permalink(),
			'type' => $product->get_type(),
			);
	}

	/**
	 * Get product categories
	 *
	 * @param WC_Product $product Product object
	 * @return array
	 */
	private function get_product_categories( $product ) {
		$categories = array();
		$terms = get_the_terms( $product->get_id(), 'product_cat' );

		if ( $terms && ! is_wp_error( $terms ) ) {
			foreach ( $terms as $term ) {
				$categories[] = array(
					'id' => $term->term_id,
					'name' => $term->name,
					'slug' => $term->slug,
				);
			}
		}

		return $categories;
	}

	/**
	 * Get product tags
	 *
	 * @param WC_Product $product Product object
	 * @return array
	 */
	private function get_product_tags( $product ) {
		$tags = array();
		$terms = get_the_terms( $product->get_id(), 'product_tag' );

		if ( $terms && ! is_wp_error( $terms ) ) {
			foreach ( $terms as $term ) {
				$tags[] = array(
					'id' => $term->term_id,
					'name' => $term->name,
					'slug' => $term->slug,
				);
			}
		}

		return $tags;
	}

	/**
	 * Get product attributes
	 *
	 * @param WC_Product $product Product object
	 * @return array
	 */
	private function get_product_attributes( $product ) {
		$attributes = array();
		$product_attributes = $product->get_attributes();

		foreach ( $product_attributes as $attribute ) {
			$attributes[] = array(
				'name' => $attribute->get_name(),
				'options' => $attribute->get_options(),
				'visible' => $attribute->get_visible(),
				'variation' => $attribute->get_variation(),
			);
		}

		return $attributes;
	}

	/**
	 * Get gallery images
	 *
	 * @param WC_Product $product Product object
	 * @return array
	 */
	private function get_gallery_images( $product ) {
		$gallery_images = array();
		$attachment_ids = $product->get_gallery_image_ids();

		foreach ( $attachment_ids as $attachment_id ) {
			$gallery_images[] = wp_get_attachment_image_url( $attachment_id, 'medium' );
		}

		return $gallery_images;
	}

	/**
	 * Get current cart contents
	 *
	 * @return array Cart items
	 */
	public function get_cart() {
		if ( ! WC()->cart ) {
			return array();
		}

		$cart_items = array();
		foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
			$product = $cart_item['data'];
			$cart_items[] = array(
				'key' => $cart_item_key,
				'product_id' => $product->get_id(),
				'quantity' => $cart_item['quantity'],
				'price' => $product->get_price(),
				'name' => $product->get_name(),
				'subtotal' => $cart_item['line_subtotal'],
				'total' => $cart_item['line_total'],
			);
		}

		return $cart_items;
	}

	/**
	 * Get current user's order history
	 *
	 * @return array Order history
	 */
	public function get_order_history() {
		if ( ! is_user_logged_in() ) {
			return array();
		}

		$orders = wc_get_orders( array(
			'customer_id' => get_current_user_id(),
			'limit' => 10,
			'orderby' => 'date',
			'order' => 'DESC',
		) );

		$order_history = array();
		foreach ( $orders as $order ) {
			$order_history[] = array(
				'id' => $order->get_id(),
				'date' => $order->get_date_created()->format( 'Y-m-d H:i:s' ),
				'total' => $order->get_total(),
				'status' => $order->get_status(),
				'items' => $this->get_order_items( $order ),
			);
		}

		return $order_history;
	}

	/**
	 * Get order items
	 *
	 * @param WC_Order $order Order object
	 * @return array
	 */
	private function get_order_items( $order ) {
		$items = array();
		foreach ( $order->get_items() as $item ) {
			$product = $item->get_product();
			$items[] = array(
				'product_id' => $item->get_product_id(),
				'name' => $item->get_name(),
				'quantity' => $item->get_quantity(),
				'price' => $item->get_price(),
				);
		}

		return $items;
	}

	/**
	 * Track product view event
	 *
	 * @param mixed $product Product data
	 * @return void
	 */
	public function track_product_view( $product ) {
		global $product;

		if ( ! $product ) {
			return;
		}

		$product_data = array(
			'product_id' => $product->get_id(),
			'product_name' => $product->get_name(),
			'product_price' => $product->get_price(),
			'product_type' => $product->get_type(),
			'categories' => $this->get_product_categories( $product ),
		);

		// Track via Umami if available
		if ( class_exists( 'Umami_Tracker' ) ) {
			Umami_Tracker::get_instance()->track_event( 'product_view', $product_data );
		}

		// Track via recommendation engine
		do_action( 'recengine_track_product_view', $product_data );
	}

	/**
	 * Track add to cart event
	 *
	 * @param mixed $product Product data
	 * @param int $quantity Quantity
	 * @return void
	 */
	public function track_add_to_cart( $product, $quantity ) {
		$event_data = array(
			'product_id' => $product->get_id(),
			'product_name' => $product->get_name(),
			'product_price' => $product->get_price(),
			'quantity' => $quantity,
			'cart_value' => $product->get_price() * $quantity,
			'categories' => $this->get_product_categories( $product ),
		);

		// Track via Umami if available
		if ( class_exists( 'Umami_Tracker' ) ) {
			Umami_Tracker::get_instance()->track_event( 'add_to_cart', $event_data );
		}

		// Track via recommendation engine
		do_action( 'recengine_track_add_to_cart', $event_data );
	}

	/**
	 * Track purchase event
	 *
	 * @param mixed $order Order data
	 * @return void
	 */
	public function track_purchase( $order ) {
		$event_data = array(
			'order_id' => $order->get_id(),
			'revenue' => $order->get_total(),
			'tax' => $order->get_total_tax(),
			'shipping' => $order->get_shipping_total(),
			'items_count' => $order->get_item_count(),
			'payment_method' => $order->get_payment_method(),
		);

		// Track via Umami if available
		if ( class_exists( 'Umami_Tracker' ) {
			Umami_Tracker::get_instance()->track_event( 'purchase', $event_data );
		}

		// Track via recommendation engine
		do_action( 'recengine_track_purchase', $event_data );
	}

	/**
	 * Get product recommendations context
	 *
	 * @return array Context data for recommendations
	 */
	public function get_recommendation_context() {
		$context = array(
			'has_commerce' => true,
			'platform' => 'woocommerce',
			'cart_items' => $this->get_cart(),
			'recent_products' => $this->get_recently_viewed_products(),
			'order_history' => $this->get_order_history(),
		);

		// Add current product context if on product page
		if ( is_product() ) {
			global $product;
			if ( $product ) {
				$context['current_product'] = array(
					'id' => $product->get_id(),
					'name' => $product->get_name(),
					'categories' => $this->get_product_categories( $product ),
				);
			}
		}

		return $context;
	}

	/**
	 * Get recently viewed products
	 *
	 * @return array
	 */
	private function get_recently_viewed_products() {
		$viewed_products = ! empty( $_COOKIE['woocommerce_recently_viewed'] ) ? 
			(array) explode( '|', wp_unslash( $_COOKIE['woocommerce_recently_viewed'] ) ) : 
			array();

		return array_filter( $viewed_products );
	}

	/**
	 * Add product recommendations to product pages
	 */
	public function add_product_recommendations() {
		echo '<div class="recengine-product-recommendations">';
		// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped -- Method returns escaped HTML.
		echo RecEngine_Recommendations::get_instance()->render_recommendations(
			array(
				'title' => __( 'You May Also Like', 'first8marketing-recommendation-engine' ),
				'count' => 4,
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
				'title' => __( 'Complete Your Purchase', 'first8marketing-recommendation-engine' ),
				'count' => 3,
				'layout' => 'grid',
			)
		);
		// phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '</div>';
	}

	/**
	 * Personalize email footer
	 *
	 * @param string $footer_text Footer text
	 * @param object $email Email object
	 * @return string
	 */
	public function personalize_email_footer( $footer_text, $email ) {
		if ( ! is_user_logged_in() ) {
			return $footer_text;
		}

		$recommendations = RecEngine_Recommendations::get_instance()->get_recommendations( array( 'count' => 3 ) );

		if ( empty( $recommendations ) ) {
			return $footer_text;
		}

		$personalized_content = '<h3>' . __( 'Recommended for You', 'first8marketing-recommendation-engine' ) . '</h3>';
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

	/**
	 * Render product recommendations
	 *
	 * @param array $recommendations Recommendations data
	 * @param string $context Display context
	 * @return string Rendered recommendations HTML
	 */
	public function render_recommendations( $recommendations, $context = 'general' ) {
		// Use the existing WooCommerce template
		ob_start();
		include RECENGINE_WP_PLUGIN_DIR . 'templates/recommendations-widget.php';
		return ob_get_clean();
	}

	/**
	 * Get integration platform name
	 *
	 * @return string
	 */
	public function get_platform_name() {
		return 'woocommerce';
	}

	/**
	 * Get integration display name
	 *
	 * @return string
	 */
	public function get_display_name() {
		return __( 'WooCommerce', 'first8marketing-recommendation-engine' );
	}
}