<?php
/**
 * Recommendations Widget Template
 */

if ( ! defined( 'ABSPATH' ) ) exit;
?>

<div class="recengine-recommendations-widget recengine-layout-<?php echo esc_attr( $args['layout'] ); ?>">
    <?php if ( ! empty( $args['title'] ) ) : ?>
        <h3 class="recengine-widget-title"><?php echo esc_html( $args['title'] ); ?></h3>
    <?php endif; ?>
    
    <div class="recengine-recommendations-grid">
        <?php foreach ( $recommendations as $recommendation ) : ?>
            <?php
            $product_id = $recommendation['product_id'];
            $score = isset( $recommendation['score'] ) ? $recommendation['score'] : 0;
            $reason = isset( $recommendation['reason'] ) ? $recommendation['reason'] : '';
            
            // Get WooCommerce product if available
            if ( class_exists( 'WooCommerce' ) ) {
                $product = wc_get_product( $product_id );
                if ( ! $product ) continue;
                ?>
                <div class="recengine-recommendation-item" data-score="<?php echo esc_attr( $score ); ?>">
                    <a href="<?php echo esc_url( $product->get_permalink() ); ?>">
                        <?php echo $product->get_image( 'medium' ); ?>
                        <h4><?php echo esc_html( $product->get_name() ); ?></h4>
                        <?php if ( $args['show_price'] ) : ?>
                            <span class="price"><?php echo $product->get_price_html(); ?></span>
                        <?php endif; ?>
                        <?php if ( $args['show_rating'] && $product->get_average_rating() > 0 ) : ?>
                            <div class="rating"><?php echo wc_get_rating_html( $product->get_average_rating() ); ?></div>
                        <?php endif; ?>
                        <?php if ( ! empty( $reason ) ) : ?>
                            <p class="recengine-reason"><?php echo esc_html( $reason ); ?></p>
                        <?php endif; ?>
                    </a>
                </div>
                <?php
            }
            ?>
        <?php endforeach; ?>
    </div>
</div>
