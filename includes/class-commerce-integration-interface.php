<?php
/**
 * Commerce Integration Interface
 * 
 * Abstract interface for e-commerce platform integrations
 * Provides a consistent API for different e-commerce platforms
 *
 * @package First8Marketing_Recommendation_Engine
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Commerce Integration Interface
 */
interface RecEngine_Commerce_Integration_Interface {
	/**
	 * Check if the e-commerce platform is available
	 *
	 * @return bool
	 */
	public function is_available();

	/**
	 * Get product information
	 *
	 * @param int|string $product_id Product ID
	 * @return array|null Product data or null if not found
	 */
	public function get_product( $product_id );

	/**
	 * Get current cart contents
	 *
	 * @return array Cart items
	 */
	public function get_cart();

	/**
	 * Get current user's order history
	 *
	 * @return array Order history
	 */
	public function get_order_history();

	/**
	 * Track product view event
	 *
	 * @param mixed $product Product data
	 * @return void
	 */
	public function track_product_view( $product );

	/**
	 * Track add to cart event
	 *
	 * @param mixed $product Product data
	 * @param int $quantity Quantity
	 * @return void
	 */
	public function track_add_to_cart( $product, $quantity );

	/**
	 * Track purchase event
	 *
	 * @param mixed $order Order data
	 * @return void
	 */
	public function track_purchase( $order );

	/**
	 * Get product recommendations context
	 *
	 * @return array Context data for recommendations
	 */
	public function get_recommendation_context();

	/**
	 * Render product recommendations
	 *
	 * @param array $recommendations Recommendations data
	 * @param string $context Display context
	 * @return string Rendered recommendations HTML
	 */
	public function render_recommendations( $recommendations, $context = 'general' );
}