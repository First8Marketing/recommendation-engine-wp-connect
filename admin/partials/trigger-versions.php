<?php
/**
 * Trigger Versions Meta Box Template
 *
 * @package First8Marketing_Recommendation_Engine
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="recengine-trigger-versions">
	<p class="description">
		<?php esc_html_e( 'Add multiple content versions. The first matching condition will be displayed. The default content (post content) will be shown if no conditions match.', 'first8marketing-recommendation-engine' ); ?>
	</p>

	<div id="recengine-versions-container">
		<?php
		if ( ! empty( $versions ) ) {
			foreach ( $versions as $index => $version ) {
				?>
				<div class="recengine-version-item" data-index="<?php echo esc_attr( $index ); ?>">
					<h4>
						<?php
						/* translators: %d: Version number */
						echo esc_html( sprintf( __( 'Version %d', 'first8marketing-recommendation-engine' ), $index + 1 ) );
						?>
					</h4>
					
					<p>
						<label>
							<strong><?php esc_html_e( 'Condition:', 'first8marketing-recommendation-engine' ); ?></strong><br>
							<select name="recengine_versions[<?php echo esc_attr( $index ); ?>][condition]" class="regular-text">
								<option value="logged_in" <?php selected( $version['condition'], 'logged_in' ); ?>><?php esc_html_e( 'Logged In', 'first8marketing-recommendation-engine' ); ?></option>
								<option value="logged_out" <?php selected( $version['condition'], 'logged_out' ); ?>><?php esc_html_e( 'Logged Out', 'first8marketing-recommendation-engine' ); ?></option>
								<option value="user_role" <?php selected( $version['condition'], 'user_role' ); ?>><?php esc_html_e( 'User Role', 'first8marketing-recommendation-engine' ); ?></option>
								<option value="device_type" <?php selected( $version['condition'], 'device_type' ); ?>><?php esc_html_e( 'Device Type', 'first8marketing-recommendation-engine' ); ?></option>
								<option value="geolocation" <?php selected( $version['condition'], 'geolocation' ); ?>><?php esc_html_e( 'Geolocation', 'first8marketing-recommendation-engine' ); ?></option>
							</select>
						</label>
					</p>

					<p>
						<label>
							<strong><?php esc_html_e( 'Content:', 'first8marketing-recommendation-engine' ); ?></strong><br>
							<?php
							wp_editor(
								$version['content'],
								'recengine_version_content_' . $index,
								array(
									'textarea_name' => 'recengine_versions[' . $index . '][content]',
									'textarea_rows' => 5,
									'media_buttons' => true,
									'teeny'         => false,
								)
							);
							?>
						</label>
					</p>

					<p>
						<button type="button" class="button recengine-remove-version"><?php esc_html_e( 'Remove Version', 'first8marketing-recommendation-engine' ); ?></button>
					</p>
					<hr>
				</div>
				<?php
			}
		}
		?>
	</div>

	<p>
		<button type="button" id="recengine-add-version" class="button button-primary">
			<?php esc_html_e( 'Add Content Version', 'first8marketing-recommendation-engine' ); ?>
		</button>
	</p>
</div>

<script>
jQuery(document).ready(function($) {
	var versionIndex = <?php echo count( $versions ); ?>;

	$('#recengine-add-version').on('click', function() {
		var html = '<div class="recengine-version-item" data-index="' + versionIndex + '">' +
			'<h4><?php esc_html_e( 'Version', 'first8marketing-recommendation-engine' ); ?> ' + (versionIndex + 1) + '</h4>' +
			'<p><label><strong><?php esc_html_e( 'Condition:', 'first8marketing-recommendation-engine' ); ?></strong><br>' +
			'<select name="recengine_versions[' + versionIndex + '][condition]" class="regular-text">' +
			'<option value="logged_in"><?php esc_html_e( 'Logged In', 'first8marketing-recommendation-engine' ); ?></option>' +
			'<option value="logged_out"><?php esc_html_e( 'Logged Out', 'first8marketing-recommendation-engine' ); ?></option>' +
			'<option value="user_role"><?php esc_html_e( 'User Role', 'first8marketing-recommendation-engine' ); ?></option>' +
			'<option value="device_type"><?php esc_html_e( 'Device Type', 'first8marketing-recommendation-engine' ); ?></option>' +
			'<option value="geolocation"><?php esc_html_e( 'Geolocation', 'first8marketing-recommendation-engine' ); ?></option>' +
			'</select></label></p>' +
			'<p><label><strong><?php esc_html_e( 'Content:', 'first8marketing-recommendation-engine' ); ?></strong><br>' +
			'<textarea name="recengine_versions[' + versionIndex + '][content]" rows="5" class="large-text"></textarea>' +
			'</label></p>' +
			'<p><button type="button" class="button recengine-remove-version"><?php esc_html_e( 'Remove Version', 'first8marketing-recommendation-engine' ); ?></button></p>' +
			'<hr></div>';

		$('#recengine-versions-container').append(html);
		versionIndex++;
	});

	$(document).on('click', '.recengine-remove-version', function() {
		$(this).closest('.recengine-version-item').remove();
	});
});
</script>

<style>
.recengine-version-item {
	background: #f9f9f9;
	padding: 15px;
	margin-bottom: 15px;
	border: 1px solid #ddd;
	border-radius: 4px;
}
.recengine-version-item h4 {
	margin-top: 0;
	color: #2271b1;
}
</style>

