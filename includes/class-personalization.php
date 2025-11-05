<?php
/**
 * Personalization Handler
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class RecEngine_Personalization {
    private static $instance = null;
    
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_filter( 'the_content', array( $this, 'personalize_content' ), 20 );
        add_action( 'wp_head', array( $this, 'add_personalization_meta' ) );
    }
    
    public function personalize_content( $content ) {
        // Add personalized content blocks
        if ( is_singular( 'post' ) || is_singular( 'page' ) ) {
            $recommendations = RecEngine_Recommendations::get_instance()->render_recommendations( array(
                'count' => 3,
                'title' => __( 'You Might Also Like', 'recommendation-engine-wp' ),
            ) );
            
            if ( ! empty( $recommendations ) ) {
                $content .= '<div class="recengine-personalized-content">' . $recommendations . '</div>';
            }
        }
        
        return $content;
    }
    
    public function add_personalization_meta() {
        $user_id = is_user_logged_in() ? get_current_user_id() : 'anonymous';
        echo '<meta name="recengine-user-id" content="' . esc_attr( $user_id ) . '">' . "\n";
    }
}
