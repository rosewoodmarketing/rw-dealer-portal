<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class RWDP_Dealer_Search_Widget extends \Elementor\Widget_Base {

	public function get_name() {
		return 'rwdp_dealer_search';
	}

	public function get_title() {
		return __( 'Dealer Search Bar', 'rw-dealer-portal' );
	}

	public function get_icon() {
		return 'eicon-search';
	}

	public function get_categories() {
		return [ 'rw-dealer-portal' ];
	}

	public function get_keywords() {
		return [ 'dealer', 'search', 'finder', 'zip' ];
	}

	public function get_style_depends() {
		return [ 'rwdp-dealer-map' ];
	}

	public function get_script_depends() {
		$settings = get_option( 'rwdp_settings', [] );
		$deps     = [ 'rwdp-dealer-map', 'rwdp-ff-helper' ];
		if ( ! empty( $settings['google_maps_api_key'] ) ) {
			$deps[] = 'google-maps';
		}
		return $deps;
	}

	// -----------------------------------------------------------------------
	// Controls
	// -----------------------------------------------------------------------
	protected function register_controls() {

		// ── Content: Search Settings ─────────────────────────────────────
		$this->start_controls_section( 'section_search', [
			'label' => __( 'Search Settings', 'rw-dealer-portal' ),
		] );

		$this->add_control( 'dealer_type', [
			'label'       => __( 'Lock to Dealer Type', 'rw-dealer-portal' ),
			'type'        => \Elementor\Controls_Manager::TEXT,
			'placeholder' => __( 'e.g. installer', 'rw-dealer-portal' ),
			'description' => __( 'Enter a dealer type slug to restrict results to that type. Leave blank to show all.', 'rw-dealer-portal' ),
		] );

		$this->add_control( 'placeholder_text', [
			'label'   => __( 'Input Placeholder', 'rw-dealer-portal' ),
			'type'    => \Elementor\Controls_Manager::TEXT,
			'default' => __( 'Enter ZIP code or city', 'rw-dealer-portal' ),
		] );

		$this->add_control( 'button_text', [
			'label'   => __( 'Button Label', 'rw-dealer-portal' ),
			'type'    => \Elementor\Controls_Manager::TEXT,
			'default' => __( 'Search', 'rw-dealer-portal' ),
		] );

		$this->add_control( 'button_icon', [
			'label' => __( 'Button Icon', 'rw-dealer-portal' ),
			'type'  => \Elementor\Controls_Manager::ICONS,
		] );

		$this->add_control( 'icon_position', [
			'label'     => __( 'Icon Position', 'rw-dealer-portal' ),
			'type'      => \Elementor\Controls_Manager::SELECT,
			'default'   => 'before',
			'options'   => [
				'before' => __( 'Before Text', 'rw-dealer-portal' ),
				'after'  => __( 'After Text', 'rw-dealer-portal' ),
			],
			'condition' => [ 'button_icon[value]!' => '' ],
		] );

		$this->add_control( 'icon_spacing', [
			'label'      => __( 'Icon Spacing', 'rw-dealer-portal' ),
			'type'       => \Elementor\Controls_Manager::SLIDER,
			'size_units' => [ 'px' ],
			'range'      => [ 'px' => [ 'min' => 0, 'max' => 30 ] ],
			'default'    => [ 'size' => 6, 'unit' => 'px' ],
			'condition'  => [ 'button_icon[value]!' => '' ],
			'selectors'  => [
				'{{WRAPPER}} #rwdp-search-btn .rwdp-btn-icon--before' => 'margin-right: {{SIZE}}{{UNIT}};',
				'{{WRAPPER}} #rwdp-search-btn .rwdp-btn-icon--after'  => 'margin-left: {{SIZE}}{{UNIT}};',
			],
		] );

		$this->end_controls_section();

		// ── Content: Radius & Filters ─────────────────────────────────────
		$this->start_controls_section( 'section_filters', [
			'label' => __( 'Radius & Filters', 'rw-dealer-portal' ),
		] );

		$this->add_control( 'radius', [
			'label'       => __( 'Radius', 'rw-dealer-portal' ),
			'type'        => \Elementor\Controls_Manager::SELECT,
			'default'     => '50',
			'options'     => [
				'25'  => __( '25 miles', 'rw-dealer-portal' ),
				'50'  => __( '50 miles', 'rw-dealer-portal' ),
				'100' => __( '100 miles', 'rw-dealer-portal' ),
				'250' => __( '250 miles', 'rw-dealer-portal' ),
				'0'   => __( 'All', 'rw-dealer-portal' ),
			],
			'description' => __( 'When the radius filter is visible this is the pre-selected default. When hidden it becomes the fixed radius used for all searches.', 'rw-dealer-portal' ),
		] );

		$this->add_control( 'show_radius', [
			'label'        => __( 'Show Radius Filter', 'rw-dealer-portal' ),
			'type'         => \Elementor\Controls_Manager::SWITCHER,
			'label_on'     => __( 'Show', 'rw-dealer-portal' ),
			'label_off'    => __( 'Hide', 'rw-dealer-portal' ),
			'return_value' => 'yes',
			'default'      => 'yes',
		] );

		$this->add_control( 'show_type_filter', [
			'label'        => __( 'Show Type Filter', 'rw-dealer-portal' ),
			'type'         => \Elementor\Controls_Manager::SWITCHER,
			'label_on'     => __( 'Show', 'rw-dealer-portal' ),
			'label_off'    => __( 'Hide', 'rw-dealer-portal' ),
			'return_value' => 'yes',
			'default'      => 'yes',
			'description'  => __( 'Only applies when "Lock to Dealer Type" is empty.', 'rw-dealer-portal' ),
		] );

		$this->end_controls_section();

		// ── Style: Search Row ─────────────────────────────────────────────
		$this->start_controls_section( 'style_search_row', [
			'label' => __( 'Search Row', 'rw-dealer-portal' ),
			'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
		] );

		$this->add_control( 'search_row_gap', [
			'label'      => __( 'Gap', 'rw-dealer-portal' ),
			'type'       => \Elementor\Controls_Manager::SLIDER,
			'size_units' => [ 'px' ],
			'range'      => [ 'px' => [ 'min' => 0, 'max' => 60 ] ],
			'default'    => [ 'size' => 8, 'unit' => 'px' ],
			'selectors'  => [ '{{WRAPPER}} .rwdp-finder__search' => 'gap: {{SIZE}}{{UNIT}};' ],
		] );

		$this->end_controls_section();

		// ── Style: Search Input ───────────────────────────────────────────
		$this->start_controls_section( 'style_input', [
			'label' => __( 'Search Input', 'rw-dealer-portal' ),
			'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
		] );

		$this->add_group_control( \Elementor\Group_Control_Typography::get_type(), [
			'name'     => 'input_typography',
			'selector' => '{{WRAPPER}} .rwdp-finder__input',
		] );

		$this->add_control( 'input_text_color', [
			'label'     => __( 'Text Color', 'rw-dealer-portal' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .rwdp-finder__input' => 'color: {{VALUE}};' ],
		] );

		$this->add_control( 'input_placeholder_color', [
			'label'     => __( 'Placeholder Color', 'rw-dealer-portal' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .rwdp-finder__input::placeholder' => 'color: {{VALUE}};' ],
		] );

		$this->add_control( 'input_bg_color', [
			'label'     => __( 'Background Color', 'rw-dealer-portal' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .rwdp-finder__input' => 'background-color: {{VALUE}};' ],
		] );

		$this->add_group_control( \Elementor\Group_Control_Border::get_type(), [
			'name'     => 'input_border',
			'selector' => '{{WRAPPER}} .rwdp-finder__input',
		] );

		$this->add_control( 'input_border_radius', [
			'label'      => __( 'Border Radius', 'rw-dealer-portal' ),
			'type'       => \Elementor\Controls_Manager::DIMENSIONS,
			'size_units' => [ 'px', '%', 'em' ],
			'selectors'  => [ '{{WRAPPER}} .rwdp-finder__input' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ],
		] );

		$this->add_control( 'input_padding', [
			'label'      => __( 'Padding', 'rw-dealer-portal' ),
			'type'       => \Elementor\Controls_Manager::DIMENSIONS,
			'size_units' => [ 'px', 'em' ],
			'selectors'  => [ '{{WRAPPER}} .rwdp-finder__input' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ],
		] );

		$this->end_controls_section();

		// ── Style: Search Button ──────────────────────────────────────────
		$this->start_controls_section( 'style_button', [
			'label' => __( 'Search Button', 'rw-dealer-portal' ),
			'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
		] );

		$this->add_group_control( \Elementor\Group_Control_Typography::get_type(), [
			'name'     => 'button_typography',
			'selector' => '{{WRAPPER}} #rwdp-search-btn',
		] );

		$this->start_controls_tabs( 'button_tabs' );

		$this->start_controls_tab( 'button_normal', [
			'label' => __( 'Normal', 'rw-dealer-portal' ),
		] );

		$this->add_control( 'button_text_color', [
			'label'     => __( 'Text Color', 'rw-dealer-portal' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} #rwdp-search-btn' => 'color: {{VALUE}};' ],
		] );

		$this->add_control( 'button_bg_color', [
			'label'     => __( 'Background Color', 'rw-dealer-portal' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} #rwdp-search-btn' => 'background-color: {{VALUE}};' ],
		] );

		$this->end_controls_tab();

		$this->start_controls_tab( 'button_hover', [
			'label' => __( 'Hover', 'rw-dealer-portal' ),
		] );

		$this->add_control( 'button_text_color_hover', [
			'label'     => __( 'Text Color', 'rw-dealer-portal' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} #rwdp-search-btn:hover' => 'color: {{VALUE}};' ],
		] );

		$this->add_control( 'button_bg_color_hover', [
			'label'     => __( 'Background Color', 'rw-dealer-portal' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} #rwdp-search-btn:hover' => 'background-color: {{VALUE}};' ],
		] );

		$this->end_controls_tab();
		$this->end_controls_tabs();

		$this->add_group_control( \Elementor\Group_Control_Border::get_type(), [
			'name'      => 'button_border',
			'selector'  => '{{WRAPPER}} #rwdp-search-btn',
			'separator' => 'before',
		] );

		$this->add_control( 'button_border_radius', [
			'label'      => __( 'Border Radius', 'rw-dealer-portal' ),
			'type'       => \Elementor\Controls_Manager::DIMENSIONS,
			'size_units' => [ 'px', '%', 'em' ],
			'selectors'  => [ '{{WRAPPER}} #rwdp-search-btn' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ],
		] );

		$this->add_control( 'button_padding', [
			'label'      => __( 'Padding', 'rw-dealer-portal' ),
			'type'       => \Elementor\Controls_Manager::DIMENSIONS,
			'size_units' => [ 'px', 'em' ],
			'selectors'  => [ '{{WRAPPER}} #rwdp-search-btn' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ],
		] );

		$this->end_controls_section();

		// ── Style: Filters ────────────────────────────────────────────────
		$this->start_controls_section( 'style_filters', [
			'label' => __( 'Filters', 'rw-dealer-portal' ),
			'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
		] );

		$this->add_control( 'filter_label_heading', [
			'label' => __( 'Labels', 'rw-dealer-portal' ),
			'type'  => \Elementor\Controls_Manager::HEADING,
		] );

		$this->add_group_control( \Elementor\Group_Control_Typography::get_type(), [
			'name'     => 'filter_label_typography',
			'selector' => '{{WRAPPER}} .rwdp-finder__radius label, {{WRAPPER}} .rwdp-finder__type-filter label',
		] );

		$this->add_control( 'filter_label_color', [
			'label'     => __( 'Label Color', 'rw-dealer-portal' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => [
				'{{WRAPPER}} .rwdp-finder__radius label'      => 'color: {{VALUE}};',
				'{{WRAPPER}} .rwdp-finder__type-filter label' => 'color: {{VALUE}};',
			],
		] );

		$this->add_control( 'filter_select_heading', [
			'label'     => __( 'Select Dropdowns', 'rw-dealer-portal' ),
			'type'      => \Elementor\Controls_Manager::HEADING,
			'separator' => 'before',
		] );

		$this->add_group_control( \Elementor\Group_Control_Typography::get_type(), [
			'name'     => 'filter_select_typography',
			'selector' => '{{WRAPPER}} #rwdp-radius-select, {{WRAPPER}} #rwdp-type-filter',
		] );

		$this->add_control( 'filter_select_text_color', [
			'label'     => __( 'Text Color', 'rw-dealer-portal' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => [
				'{{WRAPPER}} #rwdp-radius-select' => 'color: {{VALUE}};',
				'{{WRAPPER}} #rwdp-type-filter'   => 'color: {{VALUE}};',
			],
		] );

		$this->add_control( 'filter_select_bg_color', [
			'label'     => __( 'Background Color', 'rw-dealer-portal' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => [
				'{{WRAPPER}} #rwdp-radius-select' => 'background-color: {{VALUE}};',
				'{{WRAPPER}} #rwdp-type-filter'   => 'background-color: {{VALUE}};',
			],
		] );

		$this->add_group_control( \Elementor\Group_Control_Border::get_type(), [
			'name'     => 'filter_select_border',
			'selector' => '{{WRAPPER}} #rwdp-radius-select, {{WRAPPER}} #rwdp-type-filter',
		] );

		$this->add_control( 'filter_select_border_radius', [
			'label'      => __( 'Border Radius', 'rw-dealer-portal' ),
			'type'       => \Elementor\Controls_Manager::DIMENSIONS,
			'size_units' => [ 'px', '%', 'em' ],
			'selectors'  => [
				'{{WRAPPER}} #rwdp-radius-select' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				'{{WRAPPER}} #rwdp-type-filter'   => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
			],
		] );

		$this->add_control( 'filter_select_padding', [
			'label'      => __( 'Padding', 'rw-dealer-portal' ),
			'type'       => \Elementor\Controls_Manager::DIMENSIONS,
			'size_units' => [ 'px', 'em' ],
			'selectors'  => [
				'{{WRAPPER}} #rwdp-radius-select' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				'{{WRAPPER}} #rwdp-type-filter'   => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
			],
		] );

		$this->end_controls_section();
	}

	// -----------------------------------------------------------------------
	// Render
	// -----------------------------------------------------------------------
	protected function render() {
		$s              = $this->get_settings_for_display();
		$dealer_type    = sanitize_text_field( $s['dealer_type'] ?? '' );
		$placeholder    = $s['placeholder_text'] ?: __( 'Enter ZIP code or city', 'rw-dealer-portal' );
		$button_text    = $s['button_text'] ?: __( 'Search', 'rw-dealer-portal' );
		$show_radius    = ( $s['show_radius'] ?? 'yes' ) === 'yes';
		$radius         = $s['radius'] ?? '50';
		$show_type      = ( $s['show_type_filter'] ?? 'yes' ) === 'yes';
		$has_icon       = ! empty( $s['button_icon']['value'] );
		$icon_position  = $s['icon_position'] ?? 'before';

		// Build button inner HTML with optional icon
		$icon_html   = '';
		if ( $has_icon ) {
			ob_start();
			\Elementor\Icons_Manager::render_icon( $s['button_icon'], [
				'aria-hidden' => 'true',
				'class'       => 'rwdp-btn-icon--' . esc_attr( $icon_position ),
			] );
			$icon_html = ob_get_clean();
		}

		$button_label = esc_html( $button_text );
		$button_inner = ( $has_icon && $icon_position === 'before' )
			? $icon_html . '<span>' . $button_label . '</span>'
			: '<span>' . $button_label . '</span>' . $icon_html;
		?>
		<div class="rwdp-dealer-finder" id="rwdp-dealer-finder"
		     data-locked-type="<?php echo esc_attr( $dealer_type ); ?>">
			<div class="rwdp-finder__controls">

				<div class="rwdp-finder__search">
					<label for="rwdp-location-search" class="screen-reader-text">
						<?php esc_html_e( 'Search by ZIP or city', 'rw-dealer-portal' ); ?>
					</label>
					<input type="text"
					       id="rwdp-location-search"
					       class="rwdp-finder__input"
					       placeholder="<?php echo esc_attr( $placeholder ); ?>"
					       autocomplete="postal-code"
					       aria-label="<?php echo esc_attr( $placeholder ); ?>"
					/>
					<button class="rwdp-btn rwdp-btn--primary" id="rwdp-search-btn" type="button">
						<?php echo $button_inner; // phpcs:ignore WordPress.Security.EscapeOutput -- escaped above ?>
					</button>
				</div>

				<?php if ( $show_radius ) : ?>
				<div class="rwdp-finder__radius">
					<label for="rwdp-radius-select"><?php esc_html_e( 'Within:', 'rw-dealer-portal' ); ?></label>
					<select id="rwdp-radius-select">
						<?php
						$radius_options = [
							'25'  => __( '25 miles', 'rw-dealer-portal' ),
							'50'  => __( '50 miles', 'rw-dealer-portal' ),
							'100' => __( '100 miles', 'rw-dealer-portal' ),
							'250' => __( '250 miles', 'rw-dealer-portal' ),
							'0'   => __( 'All', 'rw-dealer-portal' ),
						];
						foreach ( $radius_options as $val => $label ) {
							printf(
								'<option value="%s"%s>%s</option>',
								esc_attr( $val ),
								selected( $val, $radius, false ),
								esc_html( $label )
							);
						}
						?>
					</select>
				</div>
				<?php else : ?>
				<select id="rwdp-radius-select" hidden aria-hidden="true">
					<option value="<?php echo esc_attr( $radius ); ?>" selected><?php echo esc_html( $radius ); ?></option>
				</select>
				<?php endif; ?>

				<?php
				if ( $show_type && ! $dealer_type ) :
					$dealer_types = get_terms( [ 'taxonomy' => 'rw_dealer_type', 'hide_empty' => true ] );
					if ( $dealer_types && ! is_wp_error( $dealer_types ) ) :
				?>
				<div class="rwdp-finder__type-filter">
					<label for="rwdp-type-filter"><?php esc_html_e( 'Type:', 'rw-dealer-portal' ); ?></label>
					<select id="rwdp-type-filter">
						<option value=""><?php esc_html_e( 'All Types', 'rw-dealer-portal' ); ?></option>
						<?php foreach ( $dealer_types as $type ) : ?>
							<option value="<?php echo absint( $type->term_id ); ?>">
								<?php echo esc_html( $type->name ); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</div>
				<?php
					endif;
				endif;
				?>

			</div>
		</div>
		<?php
	}
}
