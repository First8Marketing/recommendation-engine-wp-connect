<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName -- Elementor widget naming convention.
/**
 * Elementor Conditional Content Widget
 *
 * @package First8Marketing_Recommendation_Engine
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * RecEngine_Elementor_Conditional_Content_Widget Class
 */
class RecEngine_Elementor_Conditional_Content_Widget extends \Elementor\Widget_Base {
	/**
	 * Get widget name
	 *
	 * @return string
	 */
	public function get_name() {
		return 'recengine_conditional_content';
	}

	/**
	 * Get widget title
	 *
	 * @return string
	 */
	public function get_title() {
		return __( 'Conditional Content', 'first8marketing-recommendation-engine' );
	}

	/**
	 * Get widget icon
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-code-highlight';
	}

	/**
	 * Get widget categories
	 *
	 * @return array
	 */
	public function get_categories() {
		return array( 'recengine' );
	}

	/**
	 * Register widget controls
	 */
	protected function register_controls() {
		$this->start_controls_section(
			'content_section',
			array(
				'label' => __( 'Condition Settings', 'first8marketing-recommendation-engine' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'condition_type',
			array(
				'label'   => __( 'Condition Type', 'first8marketing-recommendation-engine' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'default' => 'logged_in',
				'options' => array(
					'logged_in'        => __( 'Logged In', 'first8marketing-recommendation-engine' ),
					'logged_out'       => __( 'Logged Out', 'first8marketing-recommendation-engine' ),
					'user_role'        => __( 'User Role', 'first8marketing-recommendation-engine' ),
					'device_type'      => __( 'Device Type', 'first8marketing-recommendation-engine' ),
					'geolocation'      => __( 'Geolocation', 'first8marketing-recommendation-engine' ),
					'utm_parameter'    => __( 'UTM Parameter', 'first8marketing-recommendation-engine' ),
					'datetime'         => __( 'Date/Time', 'first8marketing-recommendation-engine' ),
					'woocommerce_cart' => __( 'WooCommerce Cart', 'first8marketing-recommendation-engine' ),
				),
			)
		);

		$this->add_control(
			'condition_value',
			array(
				'label'       => __( 'Condition Value (JSON)', 'first8marketing-recommendation-engine' ),
				'type'        => \Elementor\Controls_Manager::TEXTAREA,
				'default'     => '',
				'placeholder' => __( '{"key": "value"}', 'first8marketing-recommendation-engine' ),
				'description' => __( 'Enter condition parameters as JSON', 'first8marketing-recommendation-engine' ),
			)
		);

		$this->add_control(
			'content',
			array(
				'label'   => __( 'Content', 'first8marketing-recommendation-engine' ),
				'type'    => \Elementor\Controls_Manager::WYSIWYG,
				'default' => __( 'Conditional content goes here...', 'first8marketing-recommendation-engine' ),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Render widget output
	 */
	protected function render() {
		$settings = $this->get_settings_for_display();

		$condition_type  = $settings['condition_type'];
		$condition_value = $settings['condition_value'];
		$content         = $settings['content'];

		require_once RECENGINE_WP_PLUGIN_DIR . 'includes/conditions/class-condition-evaluator.php';

		$evaluator  = new RecEngine_Condition_Evaluator();
		$conditions = array();

		// Parse JSON condition value.
		if ( ! empty( $condition_value ) ) {
			$conditions = json_decode( $condition_value, true );
			if ( ! is_array( $conditions ) ) {
				$conditions = array();
			}
		}

		// Evaluate condition.
		if ( $evaluator->evaluate( $condition_type, $conditions ) ) {
			echo wp_kses_post( $content );
		}
	}

	/**
	 * Render widget output in the editor
	 */
	protected function content_template() {
		?>
		<div class="recengine-conditional-content">
			{{{ settings.content }}}
		</div>
		<?php
	}
}

