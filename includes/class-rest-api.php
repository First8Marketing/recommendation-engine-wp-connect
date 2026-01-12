<?php
/**
 * REST API Handler
 *
 * Provides REST API endpoints for the recommendation engine to fetch product data
 *
 * @package First8Marketing_Recommendation_Engine
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * RecEngine_REST_API Class
 */
class RecEngine_REST_API {
	/**
	 * Single instance
	 *
	 * @var RecEngine_REST_API
	 */
	private static $instance = null;

	/**
	 * API namespace
	 *
	 * @var string
	 */
	private $namespace = 'first8marketing/v1';

	/**
	 * Get instance
	 *
	 * @return RecEngine_REST_API
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
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Register REST API routes
	 */
	public function register_routes() {
		// Products endpoint
		register_rest_route(
			$this->namespace,
			'/products',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_products' ),
				'permission_callback' => array( $this, 'check_api_key' ),
				'args'                => array(
					'page'     => array(
						'default'           => 1,
						'sanitize_callback' => 'absint',
						'validate_callback' => function( $param ) {
							return is_numeric( $param ) && $param > 0;
						},
					),
					'per_page' => array(
						'default'           => 100,
						'sanitize_callback' => 'absint',
						'validate_callback' => function( $param ) {
							return is_numeric( $param ) && $param > 0 && $param <= 100;
						},
					),
				),
			)
		);
	}

	/**
	 * Check API key authentication
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return bool|WP_Error
	 */
	public function check_api_key( $request ) {
		$api_key = $request->get_header( 'X-API-Key' );

		if ( empty( $api_key ) ) {
			return new WP_Error(
				'rest_forbidden',
				__( 'API key is required', 'first8marketing-recommendation-engine' ),
				array( 'status' => 401 )
			);
		}

		// Get stored API key from settings
		$stored_api_key = get_option( 'recengine_api_key', '' );

		if ( empty( $stored_api_key ) ) {
			return new WP_Error(
				'rest_forbidden',
				__( 'API key not configured', 'first8marketing-recommendation-engine' ),
				array( 'status' => 500 )
			);
		}

		// Verify API key
		if ( ! hash_equals( $stored_api_key, $api_key ) ) {
			return new WP_Error(
				'rest_forbidden',
				__( 'Invalid API key', 'first8marketing-recommendation-engine' ),
				array( 'status' => 403 )
			);
		}

		return true;
	}

	/**
	 * Get products endpoint handler
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_products( $request ) {
		// Check if WooCommerce is active
		if ( ! class_exists( 'WooCommerce' ) ) {
			return new WP_Error(
				'woocommerce_not_active',
				__( 'WooCommerce is not active', 'first8marketing-recommendation-engine' ),
				array( 'status' => 503 )
			);
		}

		$page     = $request->get_param( 'page' );
		$per_page = $request->get_param( 'per_page' );

		// Query products
		$args = array(
			'post_type'      => 'product',
			'post_status'    => 'publish',
			'posts_per_page' => $per_page,
			'paged'          => $page,
			'orderby'        => 'ID',
			'order'          => 'ASC',
		);

		$query = new WP_Query( $args );

		$products = array();

		if ( $query->have_posts() ) {
			// Collect product IDs first to enable batch loading
			$product_ids = array();
			while ( $query->have_posts() ) {
				$query->the_post();
				$product_ids[] = get_the_ID();
			}
			wp_reset_postdata();

			// Batch load WooCommerce products to avoid N+1 queries
			$wc_products = wc_get_products(
				array(
					'include' => $product_ids,
					'limit'   => -1,
					'orderby' => 'include', // Maintain original order
				)
			);

			// Build product data array
			foreach ( $wc_products as $product ) {
				if ( ! $product ) {
					continue;
				}

				$product_id = $product->get_id();

				try {
					$product_data = array(
						'id'                => $product_id,
						'name'              => $product->get_name(),
						'slug'              => $product->get_slug(),
						'permalink'         => get_permalink( $product_id ),
						'type'              => $product->get_type(),
						'status'            => $product->get_status(),
						'description'       => $product->get_description(),
						'short_description' => $product->get_short_description(),
						'sku'               => $product->get_sku(),
						'price'             => $product->get_price(),
						'regular_price'     => $product->get_regular_price(),
						'sale_price'        => $product->get_sale_price(),
						'on_sale'           => $product->is_on_sale(),
						'stock_status'      => $product->get_stock_status(),
						'stock_quantity'    => $product->get_stock_quantity(),
						'categories'        => $this->get_product_categories( $product_id ),
						'tags'              => $this->get_product_tags( $product_id ),
						'images'            => $this->get_product_images( $product ),
						'attributes'        => $this->get_product_attributes( $product ),
						'date_created'      => $product->get_date_created() ? $product->get_date_created()->date( 'c' ) : null,
						'date_modified'     => $product->get_date_modified() ? $product->get_date_modified()->date( 'c' ) : null,
					);

					$products[] = $product_data;

				} catch ( Exception $e ) {
					error_log( sprintf(
						'[RecEngine REST API] Error processing product %d: %s',
						$product_id,
						$e->getMessage()
					) );
					continue;
				}
			}
		}

		// Add pagination headers
		$total_products = $query->found_posts;
		$total_pages    = $query->max_num_pages;

		$response = rest_ensure_response( $products );
		$response->header( 'X-WP-Total', $total_products );
		$response->header( 'X-WP-TotalPages', $total_pages );

		return $response;
	}

	/**
	 * Get product categories
	 *
	 * @param int $product_id Product ID.
	 * @return array
	 */
	private function get_product_categories( $product_id ) {
		$terms = get_the_terms( $product_id, 'product_cat' );
		if ( empty( $terms ) || is_wp_error( $terms ) ) {
			return array();
		}

		return array_map(
			function( $term ) {
				return array(
					'id'   => $term->term_id,
					'name' => $term->name,
					'slug' => $term->slug,
				);
			},
			$terms
		);
	}

	/**
	 * Get product tags
	 *
	 * @param int $product_id Product ID.
	 * @return array
	 */
	private function get_product_tags( $product_id ) {
		$terms = get_the_terms( $product_id, 'product_tag' );
		if ( empty( $terms ) || is_wp_error( $terms ) ) {
			return array();
		}

		return array_map(
			function( $term ) {
				return array(
					'id'   => $term->term_id,
					'name' => $term->name,
					'slug' => $term->slug,
				);
			},
			$terms
		);
	}

	/**
	 * Get product images
	 *
	 * @param WC_Product $product Product object.
	 * @return array
	 */
	private function get_product_images( $product ) {
		$images       = array();
		$image_id     = $product->get_image_id();
		$gallery_ids  = $product->get_gallery_image_ids();

		// Main image
		if ( $image_id ) {
			$images[] = array(
				'id'  => $image_id,
				'src' => wp_get_attachment_url( $image_id ),
				'alt' => get_post_meta( $image_id, '_wp_attachment_image_alt', true ),
			);
		}

		// Gallery images
		foreach ( $gallery_ids as $gallery_id ) {
			$images[] = array(
				'id'  => $gallery_id,
				'src' => wp_get_attachment_url( $gallery_id ),
				'alt' => get_post_meta( $gallery_id, '_wp_attachment_image_alt', true ),
			);
		}

		return $images;
	}

	/**
	 * Get product attributes
	 *
	 * @param WC_Product $product Product object.
	 * @return array
	 */
	private function get_product_attributes( $product ) {
		$attributes = array();
		foreach ( $product->get_attributes() as $attribute ) {
			$attributes[] = array(
				'name'    => $attribute->get_name(),
				'options' => $attribute->get_options(),
				'visible' => $attribute->get_visible(),
			);
		}
		return $attributes;
	}
}

