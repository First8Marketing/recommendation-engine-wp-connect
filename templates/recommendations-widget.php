<?php
/**
 * Recommendations Widget Template
 *
 * @package First8Marketing_Recommendation_Engine
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<?php
// Get commerce integration instance
$commerce_integration = RecEngine_Commerce_Integration_Factory::get_instance()->get_integration();
?>

<div class="recengine-recommendations-widget recengine-layout-<?php echo esc_attr( $args['layout'] ); ?>">
	<?php if ( ! empty( $args['title'] ) ) : ?>
		<h3 class="recengine-widget-title"><?php echo esc_html( $args['title'] ); ?></h3>
	<?php endif; ?>
	
	<div class="recengine-recommendations-grid">
		<?php foreach ( $recommendations as $recengine_recommendation ) : ?>
			<?php
			$recengine_product_id = $recengine_recommendation['product_id'];
			$recengine_score      = isset( $recengine_recommendation['score'] ) ? $recengine_recommendation['score'] : 0;
			$recengine_reason     = isset( $recengine_recommendation['reason'] ) ? $recengine_recommendation['reason'] : '';

			// Get WooCommerce product if available.
			if ( class_exists( 'WooCommerce' ) ) {
				$recengine_product = wc_get_product( $recengine_product_id );
				if ( ! $recengine_product ) {
					continue;
				}
				?>
				<div class="recengine-recommendation-item" data-score="<?php echo esc_attr( $recengine_score ); ?>">
					<a href="<?php echo esc_url( $recengine_product->get_permalink() ); ?>">
						<?php echo $recengine_product->get_image( 'medium' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- WooCommerce function returns escaped HTML. ?>
						<h4><?php echo esc_html( $recengine_product->get_name() ); ?></h4>
						<?php if ( $args['show_price'] ) : ?>
							<span class="price"><?php echo $recengine_product->get_price_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- WooCommerce function returns escaped HTML. ?></span>
						<?php endif; ?>
						<?php if ( $args['show_rating'] && $recengine_product->get_average_rating() > 0 ) : ?>
							<div class="rating"><?php echo wc_get_rating_html( $recengine_product->get_average_rating() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- WooCommerce function returns escaped HTML. ?></div>
						<?php endif; ?>
						<?php if ( ! empty( $recengine_reason ) ) : ?>
							<p class="recengine-reason"><?php echo esc_html( $recengine_reason ); ?></p>
						<?php endif; ?>
					</a>
				</div>
				<?php
			}
			?>
		<?php endforeach; ?>
	</div>
</div>