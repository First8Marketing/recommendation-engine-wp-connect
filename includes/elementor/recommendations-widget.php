<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName -- Elementor widget naming convention.
/**
 * Elementor Recommendations Widget
 *
 * @package First8Marketing_Recommendation_Engine
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * RecEngine_Elementor_Recommendations_Widget Class
 */
class RecEngine_Elementor_Recommendations_Widget extends \Elementor\Widget_Base {
	/**
	 * Get widget name
	 *
	 * @return string
	 */
	public function get_name() {
		return 'recengine_recommendations';
	}

	/**
	 * Get widget title
	 *
	 * @return string
	 */
	public function get_title() {
		return __( 'Recommendations', 'first8marketing-recommendation-engine' );
	}

	/**
	 * Get widget icon
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-products';
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
				'label' => __( 'Recommendation Settings', 'first8marketing-recommendation-engine' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'title',
			array(
				'label'   => __( 'Title', 'first8marketing-recommendation-engine' ),
				'type'    => \Elementor\Controls_Manager::TEXT,
				'default' => __( 'Recommended for You', 'first8marketing-recommendation-engine' ),
			)
		);

		$this->add_control(
			'count',
			array(
				'label'   => __( 'Number of Recommendations', 'first8marketing-recommendation-engine' ),
				'type'    => \Elementor\Controls_Manager::NUMBER,
				'default' => 4,
				'min'     => 1,
				'max'     => 12,
			)
		);

		$this->add_control(
			'layout',
			array(
				'label'   => __( 'Layout', 'first8marketing-recommendation-engine' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'default' => 'grid',
				'options' => array(
					'grid'     => __( 'Grid', 'first8marketing-recommendation-engine' ),
					'list'     => __( 'List', 'first8marketing-recommendation-engine' ),
					'carousel' => __( 'Carousel', 'first8marketing-recommendation-engine' ),
				),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Render widget output
	 */
	protected function render() {
		$settings = $this->get_settings_for_display();

		// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped -- Method returns escaped HTML.
		echo RecEngine_Recommendations::get_instance()->render_recommendations(
			array(
				'title'  => esc_html( $settings['title'] ),
				'count'  => intval( $settings['count'] ),
				'layout' => sanitize_text_field( $settings['layout'] ),
			)
		);
		// phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Render widget output in the editor
	 */
	protected function content_template() {
		?>
		<div class="recengine-recommendations-preview">
			<h3>{{{ settings.title }}}</h3>
			<p><?php esc_html_e( 'Recommendations will be displayed here', 'first8marketing-recommendation-engine' ); ?></p>
		</div>
		<?php
	}
}

