<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName -- Legacy filename.
/**
 * Conditional Pop-ups Handler
 *
 * @package First8Marketing_Recommendation_Engine
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * RecEngine_Popups Class
 */
class RecEngine_Popups {
	/**
	 * Single instance
	 *
	 * @var RecEngine_Popups
	 */
	private static $instance = null;

	/**
	 * Get instance
	 *
	 * @return RecEngine_Popups
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
		add_action( 'wp_footer', array( $this, 'render_popups' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_popup_scripts' ) );
		add_shortcode( 'recengine_popup', array( $this, 'popup_shortcode' ) );
	}

	/**
	 * Enqueue popup scripts
	 */
	public function enqueue_popup_scripts() {
		wp_enqueue_style(
			'recengine-popups',
			RECENGINE_WP_PLUGIN_URL . 'assets/css/popups.css',
			array(),
			RECENGINE_WP_VERSION
		);

		wp_enqueue_script(
			'recengine-popups',
			RECENGINE_WP_PLUGIN_URL . 'assets/js/popups.js',
			array( 'jquery' ),
			RECENGINE_WP_VERSION,
			true
		);
	}

	/**
	 * Render popups in footer
	 */
	public function render_popups() {
		$args = array(
			'post_type'      => 'recengine_trigger',
			'posts_per_page' => -1,
			'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Necessary for popup functionality.
				array(
					'key'   => '_recengine_popup_enabled',
					'value' => '1',
				),
			),
		);

		$popups = get_posts( $args );

		foreach ( $popups as $popup ) {
			$conditions = get_post_meta( $popup->ID, '_recengine_conditions', true );
			$delay      = get_post_meta( $popup->ID, '_recengine_popup_delay', true );
			$delay      = $delay ? intval( $delay ) : 0;

			require_once RECENGINE_WP_PLUGIN_DIR . 'includes/conditions/class-condition-evaluator.php';

			$evaluator = new RecEngine_Condition_Evaluator();

			// Check if conditions are met.
			if ( is_array( $conditions ) && isset( $conditions['type'] ) ) {
				$params = isset( $conditions['params'] ) ? json_decode( $conditions['params'], true ) : array();
				if ( ! is_array( $params ) ) {
					$params = array();
				}

				if ( $evaluator->evaluate( $conditions['type'], $params ) ) {
					$this->render_popup( $popup, $delay );
				}
			}
		}
	}

	/**
	 * Render a single popup
	 *
	 * @param WP_Post $popup Popup post object.
	 * @param int     $delay Delay in seconds.
	 */
	private function render_popup( $popup, $delay ) {
		?>
		<div class="recengine-popup" id="recengine-popup-<?php echo esc_attr( $popup->ID ); ?>" data-delay="<?php echo esc_attr( $delay ); ?>" style="display:none;">
			<div class="recengine-popup-overlay"></div>
			<div class="recengine-popup-content">
				<button class="recengine-popup-close">&times;</button>
				<div class="recengine-popup-body">
					<?php echo wp_kses_post( apply_filters( 'the_content', $popup->post_content ) ); ?>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Popup shortcode - trigger popup on click
	 *
	 * @param array  $atts    Shortcode attributes.
	 * @param string $content Shortcode content (unused).
	 * @return string
	 */
	public function popup_shortcode( $atts, $content = '' ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- WordPress shortcode signature.
		$atts = shortcode_atts(
			array(
				'id'    => 0,
				'text'  => __( 'Click here', 'first8marketing-recommendation-engine' ),
				'class' => 'recengine-popup-trigger',
			),
			$atts
		);

		$popup_id = intval( $atts['id'] );
		if ( ! $popup_id ) {
			return '';
		}

		$text  = sanitize_text_field( $atts['text'] );
		$class = sanitize_html_class( $atts['class'] );

		return sprintf(
			'<a href="#" class="%s" data-popup-id="%d">%s</a>',
			esc_attr( $class ),
			esc_attr( $popup_id ),
			esc_html( $text )
		);
	}
}

