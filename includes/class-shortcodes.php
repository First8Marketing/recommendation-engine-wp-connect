<?php
/**
 * Shortcodes Handler
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class RecEngine_Shortcodes {
    private static $instance = null;
    
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_shortcode( 'recengine_recommendations', array( $this, 'recommendations_shortcode' ) );
        add_shortcode( 'recengine_personalized', array( $this, 'personalized_shortcode' ) );
    }
    
    public function recommendations_shortcode( $atts ) {
        $atts = shortcode_atts( array(
            'count' => 4,
            'title' => __( 'Recommended for You', 'recommendation-engine-wp' ),
            'layout' => 'grid',
        ), $atts );
        
        return RecEngine_Recommendations::get_instance()->render_recommendations( $atts );
    }
    
    public function personalized_shortcode( $atts, $content = '' ) {
        if ( ! is_user_logged_in() ) {
            return '';
        }
        
        return '<div class="recengine-personalized">' . do_shortcode( $content ) . '</div>';
    }
}
