<?php
/**
 * WooCommerce Integration
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class RecEngine_WooCommerce_Integration {
    private static $instance = null;
    
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // Add recommendations to product pages
        add_action( 'woocommerce_after_single_product_summary', array( $this, 'add_product_recommendations' ), 15 );
        
        // Add recommendations to cart page
        add_action( 'woocommerce_after_cart', array( $this, 'add_cart_recommendations' ) );
        
        // Personalize email content
        add_filter( 'woocommerce_email_footer_text', array( $this, 'personalize_email_footer' ), 10, 2 );
    }
    
    public function add_product_recommendations() {
        echo '<div class="recengine-product-recommendations">';
        echo RecEngine_Recommendations::get_instance()->render_recommendations( array(
            'title' => __( 'You May Also Like', 'recommendation-engine-wp' ),
            'count' => 4,
            'layout' => 'grid',
        ) );
        echo '</div>';
    }
    
    public function add_cart_recommendations() {
        echo '<div class="recengine-cart-recommendations">';
        echo RecEngine_Recommendations::get_instance()->render_recommendations( array(
            'title' => __( 'Complete Your Purchase', 'recommendation-engine-wp' ),
            'count' => 3,
            'layout' => 'grid',
        ) );
        echo '</div>';
    }
    
    public function personalize_email_footer( $footer_text, $email ) {
        if ( ! is_user_logged_in() ) {
            return $footer_text;
        }
        
        $recommendations = RecEngine_Recommendations::get_instance()->get_recommendations( array( 'count' => 3 ) );
        
        if ( empty( $recommendations ) ) {
            return $footer_text;
        }
        
        $personalized_content = '<h3>' . __( 'Recommended for You', 'recommendation-engine-wp' ) . '</h3>';
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
