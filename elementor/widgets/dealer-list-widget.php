<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class RWDP_Dealer_List_Widget extends \Elementor\Widget_Base {

	public function get_name() {
		return 'rwdp_dealer_list';
	}

	public function get_title() {
		return __( 'Dealer Results List', 'rw-dealer-portal' );
	}

	public function get_icon() {
		return 'eicon-bullet-list';
	}

	public function get_categories() {
		return [ 'rw-dealer-portal' ];
	}

	public function get_keywords() {
		return [ 'dealer', 'list', 'results', 'finder', 'grid' ];
	}

	public function get_style_depends() {
		return [ 'rwdp-dealer-map' ];
	}

	public function get_script_depends() {
		return [ 'rwdp-dealer-map', 'rwdp-ff-helper' ];
	}

	// -----------------------------------------------------------------------
	// Helpers
	// -----------------------------------------------------------------------
	private static function get_image_size_options(): array {
		$options = [ 'full' => __( 'Full Size', 'rw-dealer-portal' ) ];

		foreach ( wp_get_registered_image_subsizes() as $slug => $data ) {
			$label = ucwords( str_replace( [ '-', '_' ], ' ', $slug ) );
			if ( ! empty( $data['width'] ) && ! empty( $data['height'] ) ) {
				$label .= ' (' . $data['width'] . '×' . $data['height'] . ')';
			} elseif ( ! empty( $data['width'] ) ) {
				$label .= ' (' . $data['width'] . 'w)';
			} elseif ( ! empty( $data['height'] ) ) {
				$label .= ' (' . $data['height'] . 'h)';
			}
			$options[ $slug ] = $label;
		}

		return $options;
	}

	// -----------------------------------------------------------------------
	// Controls
	// -----------------------------------------------------------------------
	protected function register_controls() {

		// ── Content: List Settings ────────────────────────────────────────
		$this->start_controls_section( 'section_list', [
			'label' => __( 'List Settings', 'rw-dealer-portal' ),
		] );

		$this->add_control( 'hint_text', [
			'label'   => __( 'Initial Hint Text', 'rw-dealer-portal' ),
			'type'    => \Elementor\Controls_Manager::TEXT,
			'default' => __( 'Enter your zip code or city to find dealers near you.', 'rw-dealer-portal' ),
		] );

		$this->add_control( 'contact_text', [
			'label'   => __( 'Contact Button Text (card)', 'rw-dealer-portal' ),
			'type'    => \Elementor\Controls_Manager::TEXT,
			'default' => __( 'Contact This Dealer', 'rw-dealer-portal' ),
		] );

		$this->add_control( 'more_info_text', [
			'label'   => __( 'More Info Button Text', 'rw-dealer-portal' ),
			'type'    => \Elementor\Controls_Manager::TEXT,
			'default' => __( 'More Info', 'rw-dealer-portal' ),
		] );

		$this->add_control( 'view_on_map_text', [
			'label'     => __( 'View on Map Text', 'rw-dealer-portal' ),
			'type'      => \Elementor\Controls_Manager::TEXT,
			'default'   => __( 'View on Map', 'rw-dealer-portal' ),
			'condition' => [ 'show_view_on_map' => 'yes' ],
		] );

		$this->end_controls_section();

		// ── Content: Card Toggles ─────────────────────────────────────────
		$this->start_controls_section( 'section_toggles', [
			'label' => __( 'Card Field Toggles', 'rw-dealer-portal' ),
		] );

		foreach ( [
			'show_thumbnail'   => __( 'Show Thumbnail Image',    'rw-dealer-portal' ),
			'show_logo'        => __( 'Show Logo',                'rw-dealer-portal' ),
			'show_title'       => __( 'Show Title',               'rw-dealer-portal' ),
			'show_address'     => __( 'Show Address',             'rw-dealer-portal' ),
			'show_phone'       => __( 'Show Phone',               'rw-dealer-portal' ),
			'show_hours'       => __( 'Show Hours',               'rw-dealer-portal' ),
			'show_directions'  => __( 'Show Get Directions',      'rw-dealer-portal' ),
			'show_contact'     => __( 'Show Contact This Dealer', 'rw-dealer-portal' ),
			'show_more_info'   => __( 'Show More Info Button',    'rw-dealer-portal' ),
			'show_view_on_map' => __( 'Show View on Map Button', 'rw-dealer-portal' ),
		] as $key => $label ) {
			$this->add_control( $key, [
				'label'        => $label,
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => __( 'Show', 'rw-dealer-portal' ),
				'label_off'    => __( 'Hide', 'rw-dealer-portal' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			] );
		}

		$this->end_controls_section();

		// ── Content: Image Settings ──────────────────────────────────────
		$this->start_controls_section( 'section_images', [
			'label' => __( 'Image Settings', 'rw-dealer-portal' ),
		] );

		$image_size_options = self::get_image_size_options();

		$this->add_control( 'thumbnail_image_size', [
			'label'     => __( 'Thumbnail Resolution', 'rw-dealer-portal' ),
			'type'      => \Elementor\Controls_Manager::SELECT,
			'default'   => 'large',
			'options'   => $image_size_options,
			'condition' => [ 'show_thumbnail' => 'yes' ],
		] );

		$this->add_control( 'logo_image_size', [
			'label'     => __( 'Logo Resolution', 'rw-dealer-portal' ),
			'type'      => \Elementor\Controls_Manager::SELECT,
			'default'   => 'large',
			'options'   => $image_size_options,
			'condition' => [ 'show_logo' => 'yes' ],
		] );

		$this->end_controls_section();

		// ── Content: Directions Button ────────────────────────────────────
		$this->start_controls_section( 'section_directions_btn', [
			'label' => __( 'Directions Button', 'rw-dealer-portal' ),
		] );

		$this->add_control( 'directions_text', [
			'label'   => __( 'Button Text', 'rw-dealer-portal' ),
			'type'    => \Elementor\Controls_Manager::TEXT,
			'default' => __( 'Get Directions', 'rw-dealer-portal' ),
		] );

		$this->add_control( 'directions_icon', [
			'label'       => __( 'Icon', 'rw-dealer-portal' ),
			'type'        => \Elementor\Controls_Manager::ICONS,
			'skin'        => 'inline',
			'label_block' => false,
			'default'     => [ 'value' => '', 'library' => '' ],
		] );

		$this->add_control( 'directions_icon_position', [
			'label'     => __( 'Icon Position', 'rw-dealer-portal' ),
			'type'      => \Elementor\Controls_Manager::SELECT,
			'default'   => 'after',
			'options'   => [
				'before' => __( 'Before Text', 'rw-dealer-portal' ),
				'after'  => __( 'After Text', 'rw-dealer-portal' ),
			],
			'condition' => [ 'directions_icon[value]!' => '' ],
		] );

		$this->end_controls_section();

		// ── Style: Grid Layout ────────────────────────────────────────────
		$this->start_controls_section( 'style_grid', [
			'label' => __( 'Grid Layout', 'rw-dealer-portal' ),
			'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
		] );

		$this->add_responsive_control( 'grid_columns', [
			'label'          => __( 'Columns', 'rw-dealer-portal' ),
			'type'           => \Elementor\Controls_Manager::SLIDER,
			'range'          => [ 'px' => [ 'min' => 1, 'max' => 6, 'step' => 1 ] ],
			'default'        => [ 'size' => 3 ],
			'tablet_default' => [ 'size' => 2 ],
			'mobile_default' => [ 'size' => 1 ],
			'selectors'      => [
				'{{WRAPPER}} .rwdp-results-grid' => 'grid-template-columns: repeat({{SIZE}}, 1fr);',
			],
		] );

		$this->add_responsive_control( 'grid_gap', [
			'label'          => __( 'Gap', 'rw-dealer-portal' ),
			'type'           => \Elementor\Controls_Manager::SLIDER,
			'size_units'     => [ 'px', 'em', '%' ],
			'range'          => [ 'px' => [ 'min' => 0, 'max' => 80 ] ],
			'default'        => [ 'size' => 24, 'unit' => 'px' ],
			'tablet_default' => [ 'size' => 20, 'unit' => 'px' ],
			'mobile_default' => [ 'size' => 16, 'unit' => 'px' ],
			'selectors'      => [
				'{{WRAPPER}} .rwdp-results-grid' => 'gap: {{SIZE}}{{UNIT}};',
			],
		] );

		$this->end_controls_section();

		// ── Style: Card ───────────────────────────────────────────────────
		$this->start_controls_section( 'style_card', [
			'label' => __( 'Card', 'rw-dealer-portal' ),
			'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
		] );

		$this->add_control( 'card_bg', [
			'label'     => __( 'Background Color', 'rw-dealer-portal' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .rwdp-result-card' => 'background-color: {{VALUE}};' ],
		] );

		$this->add_group_control( \Elementor\Group_Control_Border::get_type(), [
			'name'     => 'card_border',
			'selector' => '{{WRAPPER}} .rwdp-result-card',
		] );

		$this->add_control( 'card_border_radius', [
			'label'      => __( 'Border Radius', 'rw-dealer-portal' ),
			'type'       => \Elementor\Controls_Manager::DIMENSIONS,
			'size_units' => [ 'px', '%' ],
			'selectors'  => [ '{{WRAPPER}} .rwdp-result-card' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ],
		] );

		$this->add_control( 'card_padding', [
			'label'      => __( 'Padding', 'rw-dealer-portal' ),
			'type'       => \Elementor\Controls_Manager::DIMENSIONS,
			'size_units' => [ 'px', 'em' ],
			'selectors'  => [ '{{WRAPPER}} .rwdp-result-card' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ],
		] );

		$this->add_group_control( \Elementor\Group_Control_Box_Shadow::get_type(), [
			'name'     => 'card_shadow',
			'selector' => '{{WRAPPER}} .rwdp-result-card',
		] );

		$this->end_controls_section();

		// ── Style: Thumbnail ──────────────────────────────────────────────
		$this->start_controls_section( 'style_thumbnail', [
			'label' => __( 'Thumbnail', 'rw-dealer-portal' ),
			'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
		] );

		$this->add_control( 'thumbnail_height', [
			'label'      => __( 'Height', 'rw-dealer-portal' ),
			'type'       => \Elementor\Controls_Manager::SLIDER,
			'size_units' => [ 'px', 'vh' ],
			'range'      => [ 'px' => [ 'min' => 50, 'max' => 500 ] ],
			'default'    => [ 'size' => 180, 'unit' => 'px' ],
			'selectors'  => [ '{{WRAPPER}} .rwdp-result-card__thumbnail' => 'height: {{SIZE}}{{UNIT}};' ],
		] );

		$this->add_control( 'thumbnail_object_fit', [
			'label'     => __( 'Object Fit', 'rw-dealer-portal' ),
			'type'      => \Elementor\Controls_Manager::SELECT,
			'default'   => 'cover',
			'options'   => [
				'cover'      => 'Cover',
				'contain'    => 'Contain',
				'fill'       => 'Fill',
				'none'       => 'None',
				'scale-down' => 'Scale Down',
			],
			'selectors' => [ '{{WRAPPER}} .rwdp-result-card__thumbnail' => 'object-fit: {{VALUE}};' ],
		] );

		$this->add_control( 'thumbnail_border_radius', [
			'label'      => __( 'Border Radius', 'rw-dealer-portal' ),
			'type'       => \Elementor\Controls_Manager::DIMENSIONS,
			'size_units' => [ 'px', '%' ],
			'selectors'  => [ '{{WRAPPER}} .rwdp-result-card__thumbnail' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ],
		] );

		$this->end_controls_section();

		// ── Style: Logo ───────────────────────────────────────────────────
		$this->start_controls_section( 'style_logo', [
			'label' => __( 'Logo', 'rw-dealer-portal' ),
			'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
		] );

		$this->add_control( 'logo_max_height', [
			'label'      => __( 'Max Height', 'rw-dealer-portal' ),
			'type'       => \Elementor\Controls_Manager::SLIDER,
			'size_units' => [ 'px' ],
			'range'      => [ 'px' => [ 'min' => 20, 'max' => 200 ] ],
			'default'    => [ 'size' => 60, 'unit' => 'px' ],
			'selectors'  => [ '{{WRAPPER}} .rwdp-result-card__logo' => 'max-height: {{SIZE}}{{UNIT}};' ],
		] );

		$this->add_control( 'logo_padding', [
			'label'      => __( 'Padding', 'rw-dealer-portal' ),
			'type'       => \Elementor\Controls_Manager::DIMENSIONS,
			'size_units' => [ 'px', 'em' ],
			'selectors'  => [ '{{WRAPPER}} .rwdp-result-card__logo-wrap' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ],
		] );

		$this->end_controls_section();

		// ── Style: Title ──────────────────────────────────────────────────
		$this->start_controls_section( 'style_title', [
			'label' => __( 'Title', 'rw-dealer-portal' ),
			'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
		] );

		$this->add_group_control( \Elementor\Group_Control_Typography::get_type(), [
			'name'     => 'title_typography',
			'selector' => '{{WRAPPER}} .rwdp-result-card__title',
		] );

		$this->add_control( 'title_color', [
			'label'     => __( 'Color', 'rw-dealer-portal' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .rwdp-result-card__title' => 'color: {{VALUE}};' ],
		] );

		$this->add_control( 'title_padding', [
			'label'      => __( 'Padding', 'rw-dealer-portal' ),
			'type'       => \Elementor\Controls_Manager::DIMENSIONS,
			'size_units' => [ 'px', 'em' ],
			'selectors'  => [ '{{WRAPPER}} .rwdp-result-card__title' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ],
		] );

		$this->end_controls_section();

		// ── Style: Address ────────────────────────────────────────────────
		$this->start_controls_section( 'style_address', [
			'label' => __( 'Address', 'rw-dealer-portal' ),
			'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
		] );

		$this->add_group_control( \Elementor\Group_Control_Typography::get_type(), [
			'name'     => 'address_typography',
			'selector' => '{{WRAPPER}} .rwdp-result-card__address',
		] );

		$this->add_control( 'address_color', [
			'label'     => __( 'Color', 'rw-dealer-portal' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .rwdp-result-card__address' => 'color: {{VALUE}};' ],
		] );

		$this->add_control( 'address_padding', [
			'label'      => __( 'Padding', 'rw-dealer-portal' ),
			'type'       => \Elementor\Controls_Manager::DIMENSIONS,
			'size_units' => [ 'px', 'em' ],
			'selectors'  => [ '{{WRAPPER}} .rwdp-result-card__address' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ],
		] );

		$this->end_controls_section();

		// ── Style: Phone ──────────────────────────────────────────────────
		$this->start_controls_section( 'style_phone', [
			'label' => __( 'Phone', 'rw-dealer-portal' ),
			'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
		] );

		$this->add_group_control( \Elementor\Group_Control_Typography::get_type(), [
			'name'     => 'phone_typography',
			'selector' => '{{WRAPPER}} .rwdp-result-card__phone',
		] );

		$this->add_control( 'phone_color', [
			'label'     => __( 'Color', 'rw-dealer-portal' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .rwdp-result-card__phone, {{WRAPPER}} .rwdp-result-card__phone a' => 'color: {{VALUE}};' ],
		] );

		$this->add_control( 'phone_padding', [
			'label'      => __( 'Padding', 'rw-dealer-portal' ),
			'type'       => \Elementor\Controls_Manager::DIMENSIONS,
			'size_units' => [ 'px', 'em' ],
			'selectors'  => [ '{{WRAPPER}} .rwdp-result-card__phone' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ],
		] );

		$this->end_controls_section();

		// ── Style: Hours ──────────────────────────────────────────────────
		$this->start_controls_section( 'style_hours', [
			'label' => __( 'Hours', 'rw-dealer-portal' ),
			'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
		] );

		$this->add_group_control( \Elementor\Group_Control_Typography::get_type(), [
			'name'     => 'hours_typography',
			'selector' => '{{WRAPPER}} .rwdp-result-card__hours',
		] );

		$this->add_control( 'hours_color', [
			'label'     => __( 'Color', 'rw-dealer-portal' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .rwdp-result-card__hours' => 'color: {{VALUE}};' ],
		] );

		$this->add_control( 'hours_padding', [
			'label'      => __( 'Padding', 'rw-dealer-portal' ),
			'type'       => \Elementor\Controls_Manager::DIMENSIONS,
			'size_units' => [ 'px', 'em' ],
			'selectors'  => [ '{{WRAPPER}} .rwdp-result-card__hours' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ],
		] );

		$this->end_controls_section();

		// ── Style: Buttons ────────────────────────────────────────────────
		// ── Style: View on Map Button ─────────────────────────────────────
		$this->start_controls_section( 'style_view_on_map', [
			'label' => __( 'View on Map Button', 'rw-dealer-portal' ),
			'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
		] );

		$this->add_group_control( \Elementor\Group_Control_Typography::get_type(), [
			'name'     => 'vom_typography',
			'selector' => '{{WRAPPER}} .rwdp-result-card__view-on-map',
		] );

		$this->start_controls_tabs( 'vom_tabs' );
		$this->start_controls_tab( 'vom_normal', [ 'label' => __( 'Normal', 'rw-dealer-portal' ) ] );
		$this->add_control( 'vom_color', [
			'label'     => __( 'Text Color', 'rw-dealer-portal' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .rwdp-result-card__view-on-map' => 'color: {{VALUE}};' ],
		] );
		$this->add_control( 'vom_bg', [
			'label'     => __( 'Background', 'rw-dealer-portal' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .rwdp-result-card__view-on-map' => 'background-color: {{VALUE}};' ],
		] );
		$this->end_controls_tab();
		$this->start_controls_tab( 'vom_hover', [ 'label' => __( 'Hover', 'rw-dealer-portal' ) ] );
		$this->add_control( 'vom_color_hover', [
			'label'     => __( 'Text Color', 'rw-dealer-portal' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .rwdp-result-card__view-on-map:hover' => 'color: {{VALUE}};' ],
		] );
		$this->add_control( 'vom_bg_hover', [
			'label'     => __( 'Background', 'rw-dealer-portal' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .rwdp-result-card__view-on-map:hover' => 'background-color: {{VALUE}};' ],
		] );
		$this->end_controls_tab();
		$this->end_controls_tabs();

		$this->add_group_control( \Elementor\Group_Control_Border::get_type(), [
			'name'      => 'vom_border',
			'selector'  => '{{WRAPPER}} .rwdp-result-card__view-on-map',
			'separator' => 'before',
		] );
		$this->add_control( 'vom_border_radius', [
			'label'      => __( 'Border Radius', 'rw-dealer-portal' ),
			'type'       => \Elementor\Controls_Manager::DIMENSIONS,
			'size_units' => [ 'px', '%' ],
			'selectors'  => [ '{{WRAPPER}} .rwdp-result-card__view-on-map' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ],
		] );
		$this->add_control( 'vom_padding', [
			'label'      => __( 'Padding', 'rw-dealer-portal' ),
			'type'       => \Elementor\Controls_Manager::DIMENSIONS,
			'size_units' => [ 'px', 'em' ],
			'selectors'  => [ '{{WRAPPER}} .rwdp-result-card__view-on-map' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ],
		] );

		$this->add_control( 'vom_offset_heading', [
			'label'     => __( 'Position Offset', 'rw-dealer-portal' ),
			'type'      => \Elementor\Controls_Manager::HEADING,
			'separator' => 'before',
		] );
		$this->add_control( 'vom_offset_top', [
			'label'      => __( 'Top', 'rw-dealer-portal' ),
			'type'       => \Elementor\Controls_Manager::SLIDER,
			'size_units' => [ 'px', 'em' ],
			'range'      => [ 'px' => [ 'min' => 0, 'max' => 100 ] ],
			'default'    => [ 'size' => 10, 'unit' => 'px' ],
			'selectors'  => [ '{{WRAPPER}} .rwdp-result-card__view-on-map' => 'top: {{SIZE}}{{UNIT}};' ],
		] );
		$this->add_control( 'vom_offset_right', [
			'label'      => __( 'Right', 'rw-dealer-portal' ),
			'type'       => \Elementor\Controls_Manager::SLIDER,
			'size_units' => [ 'px', 'em' ],
			'range'      => [ 'px' => [ 'min' => 0, 'max' => 100 ] ],
			'default'    => [ 'size' => 10, 'unit' => 'px' ],
			'selectors'  => [ '{{WRAPPER}} .rwdp-result-card__view-on-map' => 'right: {{SIZE}}{{UNIT}};' ],
		] );

		$this->end_controls_section();

		// ── Style: Get Directions Button ───────────────────────────────────
		$this->start_controls_section( 'style_directions', [
			'label' => __( 'Get Directions Button', 'rw-dealer-portal' ),
			'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
		] );

		$this->add_control( 'directions_icon_size', [
			'label'      => __( 'Icon Size', 'rw-dealer-portal' ),
			'type'       => \Elementor\Controls_Manager::SLIDER,
			'size_units' => [ 'px', 'em', 'rem' ],
			'range'      => [ 'px' => [ 'min' => 8, 'max' => 60 ] ],
			'condition'  => [ 'directions_icon[value]!' => '' ],
			'selectors'  => [
				'{{WRAPPER}} .rwdp-result-card__directions i'   => 'font-size: {{SIZE}}{{UNIT}};',
				'{{WRAPPER}} .rwdp-result-card__directions svg' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
			],
		] );

		$this->add_control( 'directions_icon_spacing', [
			'label'      => __( 'Icon Spacing', 'rw-dealer-portal' ),
			'type'       => \Elementor\Controls_Manager::SLIDER,
			'size_units' => [ 'px', 'em' ],
			'range'      => [ 'px' => [ 'min' => 0, 'max' => 20 ] ],
			'default'    => [ 'size' => 6, 'unit' => 'px' ],
			'selectors'  => [ '{{WRAPPER}} .rwdp-result-card__directions' => 'gap: {{SIZE}}{{UNIT}};' ],
		] );

		$this->add_control( 'directions_row_gap', [
			'label'      => __( 'Gap (Address ↔ Button)', 'rw-dealer-portal' ),
			'type'       => \Elementor\Controls_Manager::SLIDER,
			'size_units' => [ 'px', 'em' ],
			'range'      => [ 'px' => [ 'min' => 0, 'max' => 40 ] ],
			'default'    => [ 'size' => 8, 'unit' => 'px' ],
			'selectors'  => [ '{{WRAPPER}} .rwdp-result-card__address-row' => 'gap: {{SIZE}}{{UNIT}};' ],
		] );

		$this->add_group_control( \Elementor\Group_Control_Typography::get_type(), [
			'name'     => 'directions_typography',
			'selector' => '{{WRAPPER}} .rwdp-result-card__directions',
		] );

		$this->start_controls_tabs( 'directions_tabs' );
		$this->start_controls_tab( 'directions_normal', [ 'label' => __( 'Normal', 'rw-dealer-portal' ) ] );
		$this->add_control( 'directions_color', [
			'label'     => __( 'Text Color', 'rw-dealer-portal' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .rwdp-result-card__directions' => 'color: {{VALUE}};' ],
		] );
		$this->add_control( 'directions_bg', [
			'label'     => __( 'Background', 'rw-dealer-portal' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .rwdp-result-card__directions' => 'background-color: {{VALUE}};' ],
		] );
		$this->end_controls_tab();
		$this->start_controls_tab( 'directions_hover', [ 'label' => __( 'Hover', 'rw-dealer-portal' ) ] );
		$this->add_control( 'directions_color_hover', [
			'label'     => __( 'Text Color', 'rw-dealer-portal' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .rwdp-result-card__directions:hover' => 'color: {{VALUE}};' ],
		] );
		$this->add_control( 'directions_bg_hover', [
			'label'     => __( 'Background', 'rw-dealer-portal' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .rwdp-result-card__directions:hover' => 'background-color: {{VALUE}};' ],
		] );
		$this->end_controls_tab();
		$this->end_controls_tabs();

		$this->add_group_control( \Elementor\Group_Control_Border::get_type(), [
			'name'      => 'directions_border',
			'selector'  => '{{WRAPPER}} .rwdp-result-card__directions',
			'separator' => 'before',
		] );
		$this->add_control( 'directions_border_radius', [
			'label'      => __( 'Border Radius', 'rw-dealer-portal' ),
			'type'       => \Elementor\Controls_Manager::DIMENSIONS,
			'size_units' => [ 'px', '%' ],
			'selectors'  => [ '{{WRAPPER}} .rwdp-result-card__directions' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ],
		] );
		$this->add_control( 'directions_padding', [
			'label'      => __( 'Padding', 'rw-dealer-portal' ),
			'type'       => \Elementor\Controls_Manager::DIMENSIONS,
			'size_units' => [ 'px', 'em' ],
			'selectors'  => [ '{{WRAPPER}} .rwdp-result-card__directions' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ],
		] );

		$this->end_controls_section();

		// ── Style: Actions Row ─────────────────────────────────────────────
		$this->start_controls_section( 'style_actions', [
			'label' => __( 'Actions Row', 'rw-dealer-portal' ),
			'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
		] );

		$this->add_control( 'actions_gap', [
			'label'      => __( 'Gap Between Buttons', 'rw-dealer-portal' ),
			'type'       => \Elementor\Controls_Manager::SLIDER,
			'size_units' => [ 'px' ],
			'range'      => [ 'px' => [ 'min' => 0, 'max' => 40 ] ],
			'default'    => [ 'size' => 8, 'unit' => 'px' ],
			'selectors'  => [ '{{WRAPPER}} .rwdp-result-card__actions' => 'gap: {{SIZE}}{{UNIT}};' ],
		] );
		$this->add_control( 'actions_padding', [
			'label'      => __( 'Actions Area Padding', 'rw-dealer-portal' ),
			'type'       => \Elementor\Controls_Manager::DIMENSIONS,
			'size_units' => [ 'px', 'em' ],
			'selectors'  => [ '{{WRAPPER}} .rwdp-result-card__actions' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ],
		] );

		$this->end_controls_section();

		// ── Style: Contact Button ──────────────────────────────────────────
		$this->start_controls_section( 'style_contact', [
			'label' => __( 'Contact Button', 'rw-dealer-portal' ),
			'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
		] );

		$this->add_group_control( \Elementor\Group_Control_Typography::get_type(), [
			'name'     => 'contact_typography',
			'selector' => '{{WRAPPER}} .rwdp-result-card__contact',
		] );

		$this->start_controls_tabs( 'contact_tabs' );
		$this->start_controls_tab( 'contact_normal', [ 'label' => __( 'Normal', 'rw-dealer-portal' ) ] );
		$this->add_control( 'contact_color', [
			'label'     => __( 'Text Color', 'rw-dealer-portal' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .rwdp-result-card__contact' => 'color: {{VALUE}};' ],
		] );
		$this->add_control( 'contact_bg', [
			'label'     => __( 'Background', 'rw-dealer-portal' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .rwdp-result-card__contact' => 'background-color: {{VALUE}};' ],
		] );
		$this->end_controls_tab();
		$this->start_controls_tab( 'contact_hover', [ 'label' => __( 'Hover', 'rw-dealer-portal' ) ] );
		$this->add_control( 'contact_color_hover', [
			'label'     => __( 'Text Color', 'rw-dealer-portal' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .rwdp-result-card__contact:hover' => 'color: {{VALUE}};' ],
		] );
		$this->add_control( 'contact_bg_hover', [
			'label'     => __( 'Background', 'rw-dealer-portal' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .rwdp-result-card__contact:hover' => 'background-color: {{VALUE}};' ],
		] );
		$this->end_controls_tab();
		$this->end_controls_tabs();

		$this->add_group_control( \Elementor\Group_Control_Border::get_type(), [
			'name'      => 'contact_border',
			'selector'  => '{{WRAPPER}} .rwdp-result-card__contact',
			'separator' => 'before',
		] );
		$this->add_control( 'contact_border_radius', [
			'label'      => __( 'Border Radius', 'rw-dealer-portal' ),
			'type'       => \Elementor\Controls_Manager::DIMENSIONS,
			'size_units' => [ 'px', '%' ],
			'selectors'  => [ '{{WRAPPER}} .rwdp-result-card__contact' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ],
		] );
		$this->add_control( 'contact_padding', [
			'label'      => __( 'Padding', 'rw-dealer-portal' ),
			'type'       => \Elementor\Controls_Manager::DIMENSIONS,
			'size_units' => [ 'px', 'em' ],
			'selectors'  => [ '{{WRAPPER}} .rwdp-result-card__contact' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ],
		] );

		$this->end_controls_section();

		// ── Style: More Info Button ────────────────────────────────────────
		$this->start_controls_section( 'style_more_info', [
			'label' => __( 'More Info Button', 'rw-dealer-portal' ),
			'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
		] );

		$this->add_group_control( \Elementor\Group_Control_Typography::get_type(), [
			'name'     => 'more_info_typography',
			'selector' => '{{WRAPPER}} .rwdp-result-card__more-info',
		] );

		$this->start_controls_tabs( 'more_info_tabs' );
		$this->start_controls_tab( 'more_info_normal', [ 'label' => __( 'Normal', 'rw-dealer-portal' ) ] );
		$this->add_control( 'more_info_color', [
			'label'     => __( 'Text Color', 'rw-dealer-portal' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .rwdp-result-card__more-info' => 'color: {{VALUE}};' ],
		] );
		$this->add_control( 'more_info_bg', [
			'label'     => __( 'Background', 'rw-dealer-portal' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .rwdp-result-card__more-info' => 'background-color: {{VALUE}};' ],
		] );
		$this->end_controls_tab();
		$this->start_controls_tab( 'more_info_hover', [ 'label' => __( 'Hover', 'rw-dealer-portal' ) ] );
		$this->add_control( 'more_info_color_hover', [
			'label'     => __( 'Text Color', 'rw-dealer-portal' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .rwdp-result-card__more-info:hover' => 'color: {{VALUE}};' ],
		] );
		$this->add_control( 'more_info_bg_hover', [
			'label'     => __( 'Background', 'rw-dealer-portal' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .rwdp-result-card__more-info:hover' => 'background-color: {{VALUE}};' ],
		] );
		$this->end_controls_tab();
		$this->end_controls_tabs();

		$this->add_group_control( \Elementor\Group_Control_Border::get_type(), [
			'name'      => 'more_info_border',
			'selector'  => '{{WRAPPER}} .rwdp-result-card__more-info',
			'separator' => 'before',
		] );
		$this->add_control( 'more_info_border_radius', [
			'label'      => __( 'Border Radius', 'rw-dealer-portal' ),
			'type'       => \Elementor\Controls_Manager::DIMENSIONS,
			'size_units' => [ 'px', '%' ],
			'selectors'  => [ '{{WRAPPER}} .rwdp-result-card__more-info' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ],
		] );
		$this->add_control( 'more_info_padding', [
			'label'      => __( 'Padding', 'rw-dealer-portal' ),
			'type'       => \Elementor\Controls_Manager::DIMENSIONS,
			'size_units' => [ 'px', 'em' ],
			'selectors'  => [ '{{WRAPPER}} .rwdp-result-card__more-info' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ],
		] );

		$this->end_controls_section();

		// ── Style: Contact Modal ───────────────────────────────────────────
		$this->start_controls_section( 'style_contact_modal', [
			'label' => __( 'Contact Modal', 'rw-dealer-portal' ),
			'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
		] );

		$this->add_control( 'modal_heading_container', [
			'label'     => __( 'Container', 'rw-dealer-portal' ),
			'type'      => \Elementor\Controls_Manager::HEADING,
		] );

		$this->add_control( 'modal_bg', [
			'label'     => __( 'Background', 'rw-dealer-portal' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .rwdp-modal__content' => 'background-color: {{VALUE}};' ],
		] );
		$this->add_control( 'modal_padding', [
			'label'      => __( 'Padding', 'rw-dealer-portal' ),
			'type'       => \Elementor\Controls_Manager::DIMENSIONS,
			'size_units' => [ 'px', 'em', '%' ],
			'selectors'  => [ '{{WRAPPER}} .rwdp-modal__content' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ],
		] );
		$this->add_control( 'modal_border_radius', [
			'label'      => __( 'Border Radius', 'rw-dealer-portal' ),
			'type'       => \Elementor\Controls_Manager::DIMENSIONS,
			'size_units' => [ 'px', '%' ],
			'selectors'  => [ '{{WRAPPER}} .rwdp-modal__content' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ],
		] );

		$this->add_control( 'modal_heading_title', [
			'label'     => __( 'Title', 'rw-dealer-portal' ),
			'type'      => \Elementor\Controls_Manager::HEADING,
			'separator' => 'before',
		] );

		$this->add_group_control( \Elementor\Group_Control_Typography::get_type(), [
			'name'     => 'modal_title_typography',
			'selector' => '{{WRAPPER}} .rwdp-modal__title',
		] );
		$this->add_control( 'modal_title_color', [
			'label'     => __( 'Color', 'rw-dealer-portal' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .rwdp-modal__title' => 'color: {{VALUE}};' ],
		] );

		$this->add_control( 'modal_heading_close', [
			'label'     => __( 'Close Button', 'rw-dealer-portal' ),
			'type'      => \Elementor\Controls_Manager::HEADING,
			'separator' => 'before',
		] );

		$this->add_control( 'modal_close_size', [
			'label'      => __( 'Icon Size', 'rw-dealer-portal' ),
			'type'       => \Elementor\Controls_Manager::SLIDER,
			'size_units' => [ 'px', 'em', 'rem' ],
			'range'      => [ 'px' => [ 'min' => 12, 'max' => 64 ] ],
			'selectors'  => [ '{{WRAPPER}} .rwdp-modal__close' => 'font-size: {{SIZE}}{{UNIT}};' ],
		] );

		$this->start_controls_tabs( 'modal_close_tabs' );
		$this->start_controls_tab( 'modal_close_normal', [ 'label' => __( 'Normal', 'rw-dealer-portal' ) ] );
		$this->add_control( 'modal_close_color', [
			'label'     => __( 'Color', 'rw-dealer-portal' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .rwdp-modal__close' => 'color: {{VALUE}};' ],
		] );
		$this->add_control( 'modal_close_bg', [
			'label'     => __( 'Background', 'rw-dealer-portal' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .rwdp-modal__close' => 'background-color: {{VALUE}};' ],
		] );
		$this->end_controls_tab();
		$this->start_controls_tab( 'modal_close_hover', [ 'label' => __( 'Hover', 'rw-dealer-portal' ) ] );
		$this->add_control( 'modal_close_color_hover', [
			'label'     => __( 'Color', 'rw-dealer-portal' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .rwdp-modal__close:hover' => 'color: {{VALUE}};' ],
		] );
		$this->add_control( 'modal_close_bg_hover', [
			'label'     => __( 'Background', 'rw-dealer-portal' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .rwdp-modal__close:hover' => 'background-color: {{VALUE}};' ],
		] );
		$this->end_controls_tab();
		$this->end_controls_tabs();

		$this->add_group_control( \Elementor\Group_Control_Border::get_type(), [
			'name'      => 'modal_close_border',
			'selector'  => '{{WRAPPER}} .rwdp-modal__close',
			'separator' => 'before',
		] );
		$this->add_control( 'modal_close_border_radius', [
			'label'      => __( 'Border Radius', 'rw-dealer-portal' ),
			'type'       => \Elementor\Controls_Manager::DIMENSIONS,
			'size_units' => [ 'px', '%' ],
			'selectors'  => [ '{{WRAPPER}} .rwdp-modal__close' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ],
		] );
		$this->add_control( 'modal_close_padding', [
			'label'      => __( 'Padding', 'rw-dealer-portal' ),
			'type'       => \Elementor\Controls_Manager::DIMENSIONS,
			'size_units' => [ 'px', 'em' ],
			'selectors'  => [ '{{WRAPPER}} .rwdp-modal__close' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ],
		] );

		$this->end_controls_section();
	}

	// -----------------------------------------------------------------------
	// Render helpers
	// -----------------------------------------------------------------------
	private function render_mock_cards( array $s ) {
		$bool = static function( $val ) { return ( $val ?? 'yes' ) === 'yes'; };

		$dealers = [
			[ 'title' => 'Acme Equipment Co.',    'address' => '123 Main St', 'city' => 'Springfield', 'state' => 'IL', 'zip' => '62701', 'phone' => '(217) 555-0101', 'hours' => 'Mon–Fri  8am–5pm' ],
			[ 'title' => 'Lakeside Power Systems', 'address' => '456 Oak Ave',  'city' => 'Shelbyville', 'state' => 'IL', 'zip' => '62565', 'phone' => '(217) 555-0202', 'hours' => 'Mon–Sat  7am–6pm' ],
			[ 'title' => 'Rivertown Outdoors',     'address' => '789 Pine Rd',  'city' => 'Capital City', 'state' => 'IL', 'zip' => '62702', 'phone' => '(217) 555-0303', 'hours' => 'Mon–Fri  9am–5pm' ],
		];

		$show_thumbnail   = $bool( $s['show_thumbnail']   ?? 'yes' );
		$show_logo        = $bool( $s['show_logo']        ?? 'yes' );
		$show_title       = $bool( $s['show_title']       ?? 'yes' );
		$show_address     = $bool( $s['show_address']     ?? 'yes' );
		$show_phone       = $bool( $s['show_phone']       ?? 'yes' );
		$show_hours       = $bool( $s['show_hours']       ?? 'yes' );
		$show_directions  = $bool( $s['show_directions']  ?? 'yes' );
		$show_contact     = $bool( $s['show_contact']     ?? 'yes' );
		$show_more_info   = $bool( $s['show_more_info']   ?? 'yes' );
		$show_view_on_map = $bool( $s['show_view_on_map'] ?? 'yes' );

		echo '<div class="rwdp-results-grid">';

		foreach ( $dealers as $dealer ) {
			echo '<div class="rwdp-result-card">';

			if ( $show_view_on_map ) {
				echo '<button type="button" class="rwdp-result-card__view-on-map">' . esc_html( $s['view_on_map_text'] ?? __( 'View on Map', 'rw-dealer-portal' ) ) . '</button>';
			}

			if ( $show_thumbnail ) {
				echo '<div class="rwdp-result-card__thumbnail rwdp-placeholder-img" aria-hidden="true"></div>';
			}

			echo '<div class="rwdp-result-card__body">';

			if ( $show_logo ) {
				echo '<div class="rwdp-result-card__logo-wrap"><div class="rwdp-placeholder-logo" aria-hidden="true"></div></div>';
			}

			if ( $show_title ) {
				echo '<div class="rwdp-result-card__title">' . esc_html( $dealer['title'] ) . '</div>';
			}

			if ( $show_address ) {
				echo '<div class="rwdp-result-card__address-row">';
				echo '<div class="rwdp-result-card__address">' . esc_html( $dealer['address'] ) . '<br>' . esc_html( $dealer['city'] ) . ', ' . esc_html( $dealer['state'] ) . ' ' . esc_html( $dealer['zip'] ) . '</div>';
				if ( $show_directions ) {
					$icon_html = '';
					$icon_pos  = $s['directions_icon_position'] ?? 'after';
					$dir_text  = esc_html( $s['directions_text'] ?? __( 'Get Directions', 'rw-dealer-portal' ) );
					if ( ! empty( $s['directions_icon']['value'] ) ) {
						ob_start();
						\Elementor\Icons_Manager::render_icon( $s['directions_icon'], [ 'aria-hidden' => 'true' ] );
						$icon_html = ob_get_clean();
					}
					$dir_inner = $icon_pos === 'before' ? $icon_html . $dir_text : $dir_text . $icon_html;
					echo '<span class="rwdp-result-card__directions">' . $dir_inner . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput -- icon_html from Elementor, dir_text escaped above
				}
				echo '</div>';
			}

			if ( $show_phone ) {
				echo '<div class="rwdp-result-card__phone"><a href="#">' . esc_html( $dealer['phone'] ) . '</a></div>';
			}

			if ( $show_hours ) {
				echo '<div class="rwdp-result-card__hours">' . esc_html( $dealer['hours'] ) . '</div>';
			}

			if ( $show_contact || $show_more_info ) {
				echo '<div class="rwdp-result-card__actions">';
				if ( $show_contact ) {
					echo '<span class="rwdp-result-card__contact">' . esc_html( $s['contact_text'] ?? __( 'Contact This Dealer', 'rw-dealer-portal' ) ) . '</span>';
				}
				if ( $show_more_info ) {
					echo '<a href="#" class="rwdp-result-card__more-info">' . esc_html( $s['more_info_text'] ?? __( 'More Info', 'rw-dealer-portal' ) ) . '</a>';
				}
				echo '</div>';
			}

			echo '</div></div>'; // .body / .card
		}

		echo '</div>'; // .rwdp-results-grid
	}

	// -----------------------------------------------------------------------
	// Render
	// -----------------------------------------------------------------------
	protected function render() {
		$s        = $this->get_settings_for_display();
		$hint     = $s['hint_text'] ?? __( 'Enter your zip code or city to find dealers near you.', 'rw-dealer-portal' );
		$settings = get_option( 'rwdp_settings', [] );
		$form_id  = absint( $settings['contact_form_id'] ?? 0 );

		if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
			$this->render_mock_cards( $s );
			return;
		}

		$bool = static function( $val ) { return ( $val ?? 'yes' ) === 'yes' ? '1' : '0'; };

		// Capture directions icon HTML for JS injection
		$dir_icon_html = '';
		if ( ! empty( $s['directions_icon']['value'] ) ) {
			ob_start();
			\Elementor\Icons_Manager::render_icon( $s['directions_icon'], [ 'aria-hidden' => 'true', 'class' => 'rwdp-dir-icon' ] );
			$dir_icon_html = ob_get_clean();
		}

		$data_attrs = implode( ' ', [
			'data-show-thumbnail="'           . esc_attr( $bool( $s['show_thumbnail']   ?? 'yes' ) ) . '"',
			'data-show-logo="'                . esc_attr( $bool( $s['show_logo']        ?? 'yes' ) ) . '"',
			'data-show-title="'               . esc_attr( $bool( $s['show_title']       ?? 'yes' ) ) . '"',
			'data-show-address="'             . esc_attr( $bool( $s['show_address']     ?? 'yes' ) ) . '"',
			'data-show-phone="'               . esc_attr( $bool( $s['show_phone']       ?? 'yes' ) ) . '"',
			'data-show-hours="'               . esc_attr( $bool( $s['show_hours']       ?? 'yes' ) ) . '"',
			'data-show-directions="'          . esc_attr( $bool( $s['show_directions']  ?? 'yes' ) ) . '"',
			'data-show-contact="'             . esc_attr( $bool( $s['show_contact']     ?? 'yes' ) ) . '"',
			'data-show-more-info="'           . esc_attr( $bool( $s['show_more_info']   ?? 'yes' ) ) . '"',
			'data-show-view-on-map="'         . esc_attr( $bool( $s['show_view_on_map'] ?? 'yes' ) ) . '"',
			'data-directions-icon="'          . esc_attr( $dir_icon_html ) . '"',
			'data-directions-icon-position="' . esc_attr( $s['directions_icon_position'] ?? 'after' ) . '"',
			'data-directions-text="'          . esc_attr( $s['directions_text']  ?? __( 'Get Directions',    'rw-dealer-portal' ) ) . '"',
			'data-contact-text="'             . esc_attr( $s['contact_text']     ?? __( 'Contact This Dealer', 'rw-dealer-portal' ) ) . '"',
			'data-more-info-text="'           . esc_attr( $s['more_info_text']   ?? __( 'More Info',          'rw-dealer-portal' ) ) . '"',
			'data-view-on-map-text="'         . esc_attr( $s['view_on_map_text'] ?? __( 'View on Map',        'rw-dealer-portal' ) ) . '"',
			'data-thumbnail-image-size="'     . esc_attr( $s['thumbnail_image_size'] ?? 'large' ) . '"',
			'data-logo-image-size="'          . esc_attr( $s['logo_image_size']      ?? 'large' ) . '"',
		] );
		?>
		<div class="rwdp-finder__results" id="rwdp-results-list" <?php echo $data_attrs; // phpcs:ignore WordPress.Security.EscapeOutput -- attrs escaped above ?>>
			<p class="rwdp-finder__hint"><?php echo esc_html( $hint ); ?></p>
		</div>

		<?php if ( $form_id ) : ?>
		<div class="rwdp-finder__contact-modal" id="rwdp-contact-modal"
		     aria-hidden="true" role="dialog" aria-modal="true"
		     aria-labelledby="rwdp-modal-title">
			<div class="rwdp-modal__overlay" id="rwdp-modal-overlay"></div>
			<div class="rwdp-modal__content">
				<button type="button" class="rwdp-modal__close" id="rwdp-modal-close"
				        aria-label="<?php esc_attr_e( 'Close', 'rw-dealer-portal' ); ?>">&times;</button>
				<h3 id="rwdp-modal-title" class="rwdp-modal__title">
					<?php esc_html_e( 'Contact', 'rw-dealer-portal' ); ?>
					<span id="rwdp-modal-dealer-name"></span>
				</h3>
				<div id="rwdp-contact-form-wrap">
					<?php echo do_shortcode( '[fluentform id="' . $form_id . '"]' ); ?>
				</div>
			</div>
		</div>
		<?php endif; ?>
		<?php
	}
}
