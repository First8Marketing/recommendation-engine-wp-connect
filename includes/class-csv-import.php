<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName -- Legacy filename.
/**
 * CSV Import Handler
 *
 * @package First8Marketing_Recommendation_Engine
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * RecEngine_CSV_Import Class
 */
class RecEngine_CSV_Import {
	/**
	 * Single instance
	 *
	 * @var RecEngine_CSV_Import
	 */
	private static $instance = null;

	/**
	 * Get instance
	 *
	 * @return RecEngine_CSV_Import
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
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ), 20 );
		add_action( 'admin_post_recengine_import_csv', array( $this, 'handle_csv_import' ) );
	}

	/**
	 * Add admin menu
	 */
	public function add_admin_menu() {
		add_submenu_page(
			'recengine-settings',
			__( 'CSV Import', 'first8marketing-recommendation-engine' ),
			__( 'CSV Import', 'first8marketing-recommendation-engine' ),
			'manage_options',
			'recengine-csv-import',
			array( $this, 'render_import_page' )
		);
	}

	/**
	 * Render import page
	 */
	public function render_import_page() {
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'CSV Import - Bulk Trigger Management', 'first8marketing-recommendation-engine' ); ?></h1>

			<div class="card">
				<h2><?php esc_html_e( 'Import Triggers from CSV', 'first8marketing-recommendation-engine' ); ?></h2>
				<p><?php esc_html_e( 'Upload a CSV file to bulk create or update triggers.', 'first8marketing-recommendation-engine' ); ?></p>

				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data">
					<?php wp_nonce_field( 'recengine_csv_import', 'recengine_csv_nonce' ); ?>
					<input type="hidden" name="action" value="recengine_import_csv">

					<table class="form-table">
						<tr>
							<th scope="row">
								<label for="csv_file"><?php esc_html_e( 'CSV File', 'first8marketing-recommendation-engine' ); ?></label>
							</th>
							<td>
								<input type="file" name="csv_file" id="csv_file" accept=".csv" required>
								<p class="description">
									<?php esc_html_e( 'Upload a CSV file with columns: title, content, condition_type, condition_params', 'first8marketing-recommendation-engine' ); ?>
								</p>
							</td>
						</tr>
					</table>

					<p class="submit">
						<input type="submit" name="submit" id="submit" class="button button-primary" value="<?php esc_attr_e( 'Import CSV', 'first8marketing-recommendation-engine' ); ?>">
					</p>
				</form>

				<h3><?php esc_html_e( 'CSV Format Example', 'first8marketing-recommendation-engine' ); ?></h3>
				<pre>title,content,condition_type,condition_params
"Welcome Message","Welcome to our site!","logged_in",""
"VIP Content","Exclusive content for VIP members","user_role","{\"roles\":[\"administrator\"]}"
"Mobile Banner","Special mobile offer","device_type","{\"device\":\"mobile\"}"</pre>
			</div>

			<div class="card">
				<h2><?php esc_html_e( 'Export Triggers to CSV', 'first8marketing-recommendation-engine' ); ?></h2>
				<p><?php esc_html_e( 'Download all triggers as a CSV file.', 'first8marketing-recommendation-engine' ); ?></p>
				<p>
					<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=recengine_export_csv' ), 'recengine_csv_export', 'recengine_csv_nonce' ) ); ?>" class="button button-secondary">
						<?php esc_html_e( 'Export to CSV', 'first8marketing-recommendation-engine' ); ?>
					</a>
				</p>
			</div>
		</div>
		<?php
	}

	/**
	 * Handle CSV import
	 */
	public function handle_csv_import() {
		if ( ! isset( $_POST['recengine_csv_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['recengine_csv_nonce'] ) ), 'recengine_csv_import' ) ) {
			wp_die( esc_html__( 'Security check failed', 'first8marketing-recommendation-engine' ) );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Insufficient permissions', 'first8marketing-recommendation-engine' ) );
		}

		if ( ! isset( $_FILES['csv_file']['error'] ) || UPLOAD_ERR_OK !== $_FILES['csv_file']['error'] ) {
			wp_die( esc_html__( 'File upload failed', 'first8marketing-recommendation-engine' ) );
		}

		if ( ! isset( $_FILES['csv_file']['tmp_name'] ) ) {
			wp_die( esc_html__( 'File upload failed', 'first8marketing-recommendation-engine' ) );
		}

		$file     = sanitize_text_field( wp_unslash( $_FILES['csv_file']['tmp_name'] ) );
		$imported = $this->import_csv_file( $file );

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'     => 'recengine-csv-import',
					'imported' => $imported,
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/**
	 * Import CSV file
	 *
	 * @param string $file File path.
	 * @return int Number of imported triggers.
	 */
	private function import_csv_file( $file ) {
		$handle = fopen( $file, 'r' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen -- Temporary file upload.
		if ( ! $handle ) {
			return 0;
		}

		$imported = 0;
		$header   = fgetcsv( $handle );

		// phpcs:disable WordPress.CodeAnalysis.AssignmentInCondition.FoundInWhileCondition -- Standard CSV reading pattern.
		while ( ( $row = fgetcsv( $handle ) ) !== false ) {
			// phpcs:enable WordPress.CodeAnalysis.AssignmentInCondition.FoundInWhileCondition
			if ( count( $row ) < 4 ) {
				continue;
			}

			$title            = sanitize_text_field( $row[0] );
			$content          = wp_kses_post( $row[1] );
			$condition_type   = sanitize_text_field( $row[2] );
			$condition_params = sanitize_text_field( $row[3] );

			// Create trigger post.
			$post_id = wp_insert_post(
				array(
					'post_title'   => $title,
					'post_content' => $content,
					'post_type'    => 'recengine_trigger',
					'post_status'  => 'publish',
				)
			);

			if ( $post_id ) {
				// Save conditions.
				update_post_meta(
					$post_id,
					'_recengine_conditions',
					array(
						'type'   => $condition_type,
						'params' => $condition_params,
					)
				);

				++$imported;
			}
		}

		fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- Temporary file upload.

		return $imported;
	}
}

