<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class RWDP_Request_Access_Widget extends \Elementor\Widget_Base {

	public function get_name() {
		return 'rwdp_request_access';
	}

	public function get_title() {
		return __( 'Dealer Request Access Form', 'rw-dealer-portal' );
	}

	public function get_icon() {
		return 'eicon-form-horizontal';
	}

	public function get_categories() {
		return [ 'rw-dealer-portal' ];
	}

	public function get_keywords() {
		return [ 'registration', 'request', 'access', 'dealer', 'portal' ];
	}

	protected function register_controls() {

		// ── Form Options ──────────────────────────────────────────────────────
		$this->start_controls_section( 'section_content', [
			'label' => __( 'Form Options', 'rw-dealer-portal' ),
			'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
		] );

		$this->add_control( 'show_labels', [
			'label'        => __( 'Show Field Labels', 'rw-dealer-portal' ),
			'type'         => \Elementor\Controls_Manager::SWITCHER,
			'label_on'     => __( 'Show', 'rw-dealer-portal' ),
			'label_off'    => __( 'Hide', 'rw-dealer-portal' ),
			'return_value' => 'yes',
			'default'      => 'yes',
		] );

		$this->end_controls_section();

		// ── Request Access Form ──────────────────────────────────────────────
		$this->start_controls_section( 'section_register', [
			'label' => __( 'Request Access Form', 'rw-dealer-portal' ),
			'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
		] );

		$this->add_control( 'reg_intro_text', [
			'label'       => __( 'Intro Text', 'rw-dealer-portal' ),
			'type'        => \Elementor\Controls_Manager::TEXTAREA,
			'placeholder' => __( 'Fill in the form below to request access…', 'rw-dealer-portal' ),
			'rows'        => 3,
		] );

		$this->add_control( 'reg_first_name_heading', [
			'label'     => __( 'First Name Field', 'rw-dealer-portal' ),
			'type'      => \Elementor\Controls_Manager::HEADING,
			'separator' => 'before',
		] );

		$this->add_control( 'reg_first_name_label', [
			'label'       => __( 'Label', 'rw-dealer-portal' ),
			'type'        => \Elementor\Controls_Manager::TEXT,
			'placeholder' => __( 'First Name', 'rw-dealer-portal' ),
			'condition'   => [ 'show_labels' => 'yes' ],
		] );

		$this->add_control( 'reg_first_name_placeholder', [
			'label' => __( 'Placeholder', 'rw-dealer-portal' ),
			'type'  => \Elementor\Controls_Manager::TEXT,
		] );

		$this->add_control( 'reg_last_name_heading', [
			'label'     => __( 'Last Name Field', 'rw-dealer-portal' ),
			'type'      => \Elementor\Controls_Manager::HEADING,
			'separator' => 'before',
		] );

		$this->add_control( 'reg_last_name_label', [
			'label'       => __( 'Label', 'rw-dealer-portal' ),
			'type'        => \Elementor\Controls_Manager::TEXT,
			'placeholder' => __( 'Last Name', 'rw-dealer-portal' ),
			'condition'   => [ 'show_labels' => 'yes' ],
		] );

		$this->add_control( 'reg_last_name_placeholder', [
			'label' => __( 'Placeholder', 'rw-dealer-portal' ),
			'type'  => \Elementor\Controls_Manager::TEXT,
		] );

		$this->add_control( 'reg_email_heading', [
			'label'     => __( 'Email Address Field', 'rw-dealer-portal' ),
			'type'      => \Elementor\Controls_Manager::HEADING,
			'separator' => 'before',
		] );

		$this->add_control( 'reg_email_label', [
			'label'       => __( 'Label', 'rw-dealer-portal' ),
			'type'        => \Elementor\Controls_Manager::TEXT,
			'placeholder' => __( 'Email Address', 'rw-dealer-portal' ),
			'condition'   => [ 'show_labels' => 'yes' ],
		] );

		$this->add_control( 'reg_email_placeholder', [
			'label' => __( 'Placeholder', 'rw-dealer-portal' ),
			'type'  => \Elementor\Controls_Manager::TEXT,
		] );

		$this->add_control( 'reg_company_heading', [
			'label'     => __( 'Company / Dealership Field', 'rw-dealer-portal' ),
			'type'      => \Elementor\Controls_Manager::HEADING,
			'separator' => 'before',
		] );

		$this->add_control( 'reg_company_label', [
			'label'       => __( 'Label', 'rw-dealer-portal' ),
			'type'        => \Elementor\Controls_Manager::TEXT,
			'placeholder' => __( 'Company / Dealership Name', 'rw-dealer-portal' ),
			'condition'   => [ 'show_labels' => 'yes' ],
		] );

		$this->add_control( 'reg_company_placeholder', [
			'label' => __( 'Placeholder', 'rw-dealer-portal' ),
			'type'  => \Elementor\Controls_Manager::TEXT,
		] );

		$this->add_control( 'reg_button_heading', [
			'label'     => __( 'Button', 'rw-dealer-portal' ),
			'type'      => \Elementor\Controls_Manager::HEADING,
			'separator' => 'before',
		] );

		$this->add_control( 'register_button_text', [
			'label'       => __( 'Button Text', 'rw-dealer-portal' ),
			'type'        => \Elementor\Controls_Manager::TEXT,
			'placeholder' => __( 'Request Access', 'rw-dealer-portal' ),
		] );

		$this->end_controls_section();

		// ── Style: Form ───────────────────────────────────────────────────
		$this->start_controls_section( 'style_form', [
			'label' => __( 'Form', 'rw-dealer-portal' ),
			'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
		] );

		$this->add_control( 'rows_gap', [
			'label'      => __( 'Rows Gap', 'rw-dealer-portal' ),
			'type'       => \Elementor\Controls_Manager::SLIDER,
			'size_units' => [ 'px' ],
			'range'      => [ 'px' => [ 'min' => 0, 'max' => 60 ] ],
			'default'    => [ 'size' => 13, 'unit' => 'px' ],
			'selectors'  => [
				'{{WRAPPER}} .rwdp-form-row' => 'margin-bottom: {{SIZE}}{{UNIT}};',
			],
		] );

		$this->end_controls_section();

		// ── Style: Label ──────────────────────────────────────────────────
		$this->start_controls_section( 'style_label', [
			'label' => __( 'Label', 'rw-dealer-portal' ),
			'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
		] );

		$this->add_control( 'label_spacing', [
			'label'      => __( 'Spacing', 'rw-dealer-portal' ),
			'type'       => \Elementor\Controls_Manager::SLIDER,
			'size_units' => [ 'px' ],
			'range'      => [ 'px' => [ 'min' => 0, 'max' => 40 ] ],
			'selectors'  => [
				'{{WRAPPER}} .rwdp-form-row label' => 'margin-bottom: {{SIZE}}{{UNIT}};',
			],
		] );

		$this->add_control( 'label_color', [
			'label'     => __( 'Text Color', 'rw-dealer-portal' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => [
				'{{WRAPPER}} .rwdp-form-row label' => 'color: {{VALUE}};',
			],
		] );

		$this->add_group_control( \Elementor\Group_Control_Typography::get_type(), [
			'name'     => 'label_typography',
			'selector' => '{{WRAPPER}} .rwdp-form-row label',
		] );

		$this->end_controls_section();

		// ── Style: Fields ─────────────────────────────────────────────────
		$this->start_controls_section( 'style_fields', [
			'label' => __( 'Fields', 'rw-dealer-portal' ),
			'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
		] );

		$this->add_control( 'field_text_color', [
			'label'     => __( 'Text Color', 'rw-dealer-portal' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => [
				'{{WRAPPER}} .rwdp-form input[type="text"]'     => 'color: {{VALUE}};',
				'{{WRAPPER}} .rwdp-form input[type="email"]'    => 'color: {{VALUE}};',
			],
		] );

		$this->add_group_control( \Elementor\Group_Control_Typography::get_type(), [
			'name'     => 'field_typography',
			'selector' => '{{WRAPPER}} .rwdp-form input[type="text"], {{WRAPPER}} .rwdp-form input[type="email"]',
		] );

		$this->add_control( 'field_bg_color', [
			'label'     => __( 'Background Color', 'rw-dealer-portal' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'separator' => 'before',
			'selectors' => [
				'{{WRAPPER}} .rwdp-form input[type="text"]'  => 'background-color: {{VALUE}};',
				'{{WRAPPER}} .rwdp-form input[type="email"]' => 'background-color: {{VALUE}};',
			],
		] );

		$this->add_group_control( \Elementor\Group_Control_Border::get_type(), [
			'name'      => 'field_border',
			'separator' => 'before',
			'selector'  => '{{WRAPPER}} .rwdp-form input[type="text"], {{WRAPPER}} .rwdp-form input[type="email"]',
		] );

		$this->add_control( 'field_border_radius', [
			'label'      => __( 'Border Radius', 'rw-dealer-portal' ),
			'type'       => \Elementor\Controls_Manager::DIMENSIONS,
			'size_units' => [ 'px', '%', 'em' ],
			'selectors'  => [
				'{{WRAPPER}} .rwdp-form input[type="text"]'  => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				'{{WRAPPER}} .rwdp-form input[type="email"]' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
			],
		] );

		$this->end_controls_section();

		// ── Style: Button ─────────────────────────────────────────────────
		$this->start_controls_section( 'style_button', [
			'label' => __( 'Button', 'rw-dealer-portal' ),
			'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
		] );

		$this->add_group_control( \Elementor\Group_Control_Typography::get_type(), [
			'name'     => 'btn_typography',
			'selector' => '{{WRAPPER}} .rwdp-btn--primary',
		] );

		$this->start_controls_tabs( 'btn_tabs' );

		$this->start_controls_tab( 'btn_normal', [
			'label' => __( 'Normal', 'rw-dealer-portal' ),
		] );

		$this->add_control( 'btn_text_color', [
			'label'     => __( 'Text Color', 'rw-dealer-portal' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => [
				'{{WRAPPER}} .rwdp-btn--primary' => 'color: {{VALUE}};',
			],
		] );

		$this->add_group_control( \Elementor\Group_Control_Background::get_type(), [
			'name'     => 'btn_bg',
			'types'    => [ 'classic', 'gradient' ],
			'selector' => '{{WRAPPER}} .rwdp-btn--primary',
		] );

		$this->end_controls_tab();

		$this->start_controls_tab( 'btn_hover', [
			'label' => __( 'Hover', 'rw-dealer-portal' ),
		] );

		$this->add_control( 'btn_text_color_hover', [
			'label'     => __( 'Text Color', 'rw-dealer-portal' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => [
				'{{WRAPPER}} .rwdp-btn--primary:hover' => 'color: {{VALUE}};',
			],
		] );

		$this->add_group_control( \Elementor\Group_Control_Background::get_type(), [
			'name'     => 'btn_bg_hover',
			'types'    => [ 'classic', 'gradient' ],
			'selector' => '{{WRAPPER}} .rwdp-btn--primary:hover',
		] );

		$this->end_controls_tab();
		$this->end_controls_tabs();

		$this->add_group_control( \Elementor\Group_Control_Border::get_type(), [
			'name'      => 'btn_border',
			'separator' => 'before',
			'selector'  => '{{WRAPPER}} .rwdp-btn--primary',
		] );

		$this->add_control( 'btn_border_radius', [
			'label'      => __( 'Border Radius', 'rw-dealer-portal' ),
			'type'       => \Elementor\Controls_Manager::DIMENSIONS,
			'size_units' => [ 'px', '%', 'em' ],
			'selectors'  => [
				'{{WRAPPER}} .rwdp-btn--primary' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
			],
		] );

		$this->add_control( 'btn_padding', [
			'label'      => __( 'Text Padding', 'rw-dealer-portal' ),
			'type'       => \Elementor\Controls_Manager::DIMENSIONS,
			'size_units' => [ 'px', 'em' ],
			'selectors'  => [
				'{{WRAPPER}} .rwdp-btn--primary' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
			],
		] );

		$this->end_controls_section();
	}

	protected function render() {
		echo rwdp_request_access_shortcode( $this->get_settings_for_display() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- output escaping handled within the shortcode function
	}
}
