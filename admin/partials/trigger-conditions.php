<?php
/**
 * Trigger Conditions Meta Box Template
 *
 * @package First8Marketing_Recommendation_Engine
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="recengine-trigger-conditions">
	<p class="description">
		<?php esc_html_e( 'Configure the conditions for this trigger. Content versions will be evaluated in order.', 'first8marketing-recommendation-engine' ); ?>
	</p>

	<table class="form-table">
		<tr>
			<th scope="row">
				<label for="recengine_condition_type">
					<?php esc_html_e( 'Primary Condition Type', 'first8marketing-recommendation-engine' ); ?>
				</label>
			</th>
			<td>
				<select name="recengine_conditions[type]" id="recengine_condition_type" class="regular-text">
					<option value="logged_in" <?php selected( isset( $conditions['type'] ) ? $conditions['type'] : '', 'logged_in' ); ?>>
						<?php esc_html_e( 'Logged In', 'first8marketing-recommendation-engine' ); ?>
					</option>
					<option value="logged_out" <?php selected( isset( $conditions['type'] ) ? $conditions['type'] : '', 'logged_out' ); ?>>
						<?php esc_html_e( 'Logged Out', 'first8marketing-recommendation-engine' ); ?>
					</option>
					<option value="user_role" <?php selected( isset( $conditions['type'] ) ? $conditions['type'] : '', 'user_role' ); ?>>
						<?php esc_html_e( 'User Role', 'first8marketing-recommendation-engine' ); ?>
					</option>
					<option value="device_type" <?php selected( isset( $conditions['type'] ) ? $conditions['type'] : '', 'device_type' ); ?>>
						<?php esc_html_e( 'Device Type', 'first8marketing-recommendation-engine' ); ?>
					</option>
					<option value="geolocation" <?php selected( isset( $conditions['type'] ) ? $conditions['type'] : '', 'geolocation' ); ?>>
						<?php esc_html_e( 'Geolocation', 'first8marketing-recommendation-engine' ); ?>
					</option>
					<option value="utm_parameter" <?php selected( isset( $conditions['type'] ) ? $conditions['type'] : '', 'utm_parameter' ); ?>>
						<?php esc_html_e( 'UTM Parameter', 'first8marketing-recommendation-engine' ); ?>
					</option>
					<option value="datetime" <?php selected( isset( $conditions['type'] ) ? $conditions['type'] : '', 'datetime' ); ?>>
						<?php esc_html_e( 'Date/Time', 'first8marketing-recommendation-engine' ); ?>
					</option>
					<option value="woocommerce_cart" <?php selected( isset( $conditions['type'] ) ? $conditions['type'] : '', 'woocommerce_cart' ); ?>>
						<?php esc_html_e( 'WooCommerce Cart', 'first8marketing-recommendation-engine' ); ?>
					</option>
					<option value="referrer" <?php selected( isset( $conditions['type'] ) ? $conditions['type'] : '', 'referrer' ); ?>>
						<?php esc_html_e( 'Referrer', 'first8marketing-recommendation-engine' ); ?>
					</option>
				</select>
				<p class="description">
					<?php esc_html_e( 'Select the primary condition type for this trigger.', 'first8marketing-recommendation-engine' ); ?>
				</p>
			</td>
		</tr>

		<tr>
			<th scope="row">
				<label for="recengine_condition_params">
					<?php esc_html_e( 'Condition Parameters', 'first8marketing-recommendation-engine' ); ?>
				</label>
			</th>
			<td>
				<textarea name="recengine_conditions[params]" id="recengine_condition_params" rows="5" class="large-text code"><?php echo isset( $conditions['params'] ) ? esc_textarea( $conditions['params'] ) : ''; ?></textarea>
				<p class="description">
					<?php esc_html_e( 'Enter condition parameters as JSON. Example: {"country": "US", "state": "CA"}', 'first8marketing-recommendation-engine' ); ?>
				</p>
			</td>
		</tr>
	</table>

	<h3><?php esc_html_e( 'Available Condition Types', 'first8marketing-recommendation-engine' ); ?></h3>
	<ul class="recengine-condition-help">
		<li><strong><?php esc_html_e( 'Logged In:', 'first8marketing-recommendation-engine' ); ?></strong> <?php esc_html_e( 'Show content only to logged-in users', 'first8marketing-recommendation-engine' ); ?></li>
		<li><strong><?php esc_html_e( 'Logged Out:', 'first8marketing-recommendation-engine' ); ?></strong> <?php esc_html_e( 'Show content only to logged-out users', 'first8marketing-recommendation-engine' ); ?></li>
		<li><strong><?php esc_html_e( 'User Role:', 'first8marketing-recommendation-engine' ); ?></strong> <?php esc_html_e( 'Show content based on user role (params: {"roles": ["administrator", "editor"]})', 'first8marketing-recommendation-engine' ); ?></li>
		<li><strong><?php esc_html_e( 'Device Type:', 'first8marketing-recommendation-engine' ); ?></strong> <?php esc_html_e( 'Show content based on device (params: {"device": "mobile"} or {"device": "desktop"})', 'first8marketing-recommendation-engine' ); ?></li>
		<li><strong><?php esc_html_e( 'Geolocation:', 'first8marketing-recommendation-engine' ); ?></strong> <?php esc_html_e( 'Show content based on location (params: {"country": "US", "state": "CA", "city": "Los Angeles"})', 'first8marketing-recommendation-engine' ); ?></li>
		<li><strong><?php esc_html_e( 'UTM Parameter:', 'first8marketing-recommendation-engine' ); ?></strong> <?php esc_html_e( 'Show content based on URL parameter (params: {"param": "utm_source", "value": "google"})', 'first8marketing-recommendation-engine' ); ?></li>
		<li><strong><?php esc_html_e( 'Date/Time:', 'first8marketing-recommendation-engine' ); ?></strong> <?php esc_html_e( 'Show content during specific time period (params: {"start": "2024-01-01", "end": "2024-12-31"})', 'first8marketing-recommendation-engine' ); ?></li>
		<li><strong><?php esc_html_e( 'WooCommerce Cart:', 'first8marketing-recommendation-engine' ); ?></strong> <?php esc_html_e( 'Show content based on cart (params: {"product_ids": [123, 456]} or {"min_total": 100})', 'first8marketing-recommendation-engine' ); ?></li>
		<li><strong><?php esc_html_e( 'Referrer:', 'first8marketing-recommendation-engine' ); ?></strong> <?php esc_html_e( 'Show content based on referrer (params: {"match": "google.com"})', 'first8marketing-recommendation-engine' ); ?></li>
	</ul>
</div>

<style>
.recengine-condition-help {
	background: #f0f0f1;
	padding: 15px 20px;
	border-left: 4px solid #2271b1;
	margin-top: 10px;
}
.recengine-condition-help li {
	margin-bottom: 8px;
}
</style>

