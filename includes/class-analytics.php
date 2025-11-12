<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName -- Legacy filename.
/**
 * Analytics Handler
 *
 * @package First8Marketing_Recommendation_Engine
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * RecEngine_Analytics Class
 */
class RecEngine_Analytics {
	/**
	 * Single instance
	 *
	 * @var RecEngine_Analytics
	 */
	private static $instance = null;

	/**
	 * Get instance
	 *
	 * @return RecEngine_Analytics
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
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ), 25 );
	}

	/**
	 * Add admin menu
	 */
	public function add_admin_menu() {
		add_submenu_page(
			'recengine-settings',
			__( 'Analytics', 'first8marketing-recommendation-engine' ),
			__( 'Analytics', 'first8marketing-recommendation-engine' ),
			'manage_options',
			'recengine-analytics',
			array( $this, 'render_analytics_page' )
		);
	}

	/**
	 * Render analytics page
	 */
	public function render_analytics_page() {
		global $wpdb;

		$table_name = $wpdb->prefix . 'recengine_analytics';

		// Get top performing triggers.
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom analytics table.
		$top_triggers = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT trigger_id, COUNT(*) as views
				FROM " . $wpdb->prefix . "recengine_analytics
				WHERE created_at > %s
				GROUP BY trigger_id
				ORDER BY views DESC
				LIMIT %d",
				gmdate( 'Y-m-d H:i:s', strtotime( '-30 days' ) ),
				10
			)
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Trigger Analytics', 'first8marketing-recommendation-engine' ); ?></h1>

			<div class="card">
				<h2><?php esc_html_e( 'Top Performing Triggers (Last 30 Days)', 'first8marketing-recommendation-engine' ); ?></h2>
				<table class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Trigger', 'first8marketing-recommendation-engine' ); ?></th>
							<th><?php esc_html_e( 'Views', 'first8marketing-recommendation-engine' ); ?></th>
							<th><?php esc_html_e( 'Actions', 'first8marketing-recommendation-engine' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php if ( $top_triggers ) : ?>
							<?php foreach ( $top_triggers as $trigger ) : ?>
								<?php $post = get_post( $trigger->trigger_id ); ?>
								<tr>
									<td>
										<?php
										if ( $post ) {
											echo esc_html( $post->post_title );
										} else {
											esc_html_e( 'Unknown Trigger', 'first8marketing-recommendation-engine' );
										}
										?>
									</td>
									<td><?php echo esc_html( number_format_i18n( $trigger->views ) ); ?></td>
									<td>
										<?php if ( $post ) : ?>
											<a href="<?php echo esc_url( get_edit_post_link( $trigger->trigger_id ) ); ?>" class="button button-small">
												<?php esc_html_e( 'Edit', 'first8marketing-recommendation-engine' ); ?>
											</a>
										<?php endif; ?>
									</td>
								</tr>
							<?php endforeach; ?>
						<?php else : ?>
							<tr>
								<td colspan="3"><?php esc_html_e( 'No analytics data available yet.', 'first8marketing-recommendation-engine' ); ?></td>
							</tr>
						<?php endif; ?>
					</tbody>
				</table>
			</div>

			<div class="card">
				<h2><?php esc_html_e( 'Analytics Settings', 'first8marketing-recommendation-engine' ); ?></h2>
				<p><?php esc_html_e( 'Analytics data is automatically collected when triggers are displayed.', 'first8marketing-recommendation-engine' ); ?></p>
				<p>
					<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=recengine_clear_analytics' ), 'recengine_clear_analytics', 'recengine_nonce' ) ); ?>" class="button button-secondary" onclick="return confirm('<?php esc_attr_e( 'Are you sure you want to clear all analytics data?', 'first8marketing-recommendation-engine' ); ?>');">
						<?php esc_html_e( 'Clear Analytics Data', 'first8marketing-recommendation-engine' ); ?>
					</a>
				</p>
			</div>
		</div>
		<?php
	}

	/**
	 * Create analytics table
	 */
	public static function create_analytics_table() {
		global $wpdb;

		$table_name      = $wpdb->prefix . 'recengine_analytics';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE IF NOT EXISTS $table_name (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			trigger_id bigint(20) NOT NULL,
			content_version varchar(255) DEFAULT '',
			user_id bigint(20) DEFAULT 0,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY trigger_id (trigger_id),
			KEY created_at (created_at)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}
}

