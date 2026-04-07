<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class RWDP_Dealer_Map_Widget extends \Elementor\Widget_Base {

	public function get_name() {
		return 'rwdp_dealer_map';
	}

	public function get_title() {
		return __( 'Dealer Map', 'rw-dealer-portal' );
	}

	public function get_icon() {
		return 'eicon-map-pin';
	}

	public function get_categories() {
		return [ 'rw-dealer-portal' ];
	}

	public function get_keywords() {
		return [ 'dealer', 'map', 'finder' ];
	}

	public function get_style_depends() {
		return [ 'rwdp-dealer-map' ];
	}

	public function get_script_depends() {
		$settings = get_option( 'rwdp_settings', [] );
		$deps     = [ 'rwdp-dealer-map' ];
		if ( ! empty( $settings['google_maps_api_key'] ) ) {
			$deps[] = 'google-maps';
		}
		return $deps;
	}

	protected function register_controls() {

		// ── Content: Popup Toggles ────────────────────────────────────────
		$this->start_controls_section( 'section_popup_toggles', [
			'label' => __( 'Popup Toggles', 'rw-dealer-portal' ),
		] );

		foreach ( [
			'show_logo'    => __( 'Show Logo',            'rw-dealer-portal' ),
			'show_phone'   => __( 'Show Phone',           'rw-dealer-portal' ),
			'show_website' => __( 'Show Website',         'rw-dealer-portal' ),
			'show_hours'   => __( 'Show Hours',           'rw-dealer-portal' ),
			'show_contact' => __( 'Show Contact Button',  'rw-dealer-portal' ),
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

		$this->add_control( 'contact_text', [
			'label'     => __( 'Contact Button Text (popup)', 'rw-dealer-portal' ),
			'type'      => \Elementor\Controls_Manager::TEXT,
			'default'   => __( 'Contact This Dealer', 'rw-dealer-portal' ),
			'condition' => [ 'show_contact' => 'yes' ],
		] );

		$this->end_controls_section();

		// ── Content: Popup — Directions Button ───────────────────────────
		$this->start_controls_section( 'section_popup_dir_btn', [
			'label' => __( 'Popup — Directions Button', 'rw-dealer-portal' ),
		] );

		$this->add_control( 'directions_text', [
			'label'   => __( 'Button Text', 'rw-dealer-portal' ),
			'type'    => \Elementor\Controls_Manager::TEXT,
			'default' => __( 'Get Directions', 'rw-dealer-portal' ),
		] );

		$this->add_control( 'popup_dir_icon', [
			'label' => __( 'Icon', 'rw-dealer-portal' ),
			'type'  => \Elementor\Controls_Manager::ICONS,
		] );

		$this->add_control( 'popup_dir_icon_position', [
			'label'     => __( 'Icon Position', 'rw-dealer-portal' ),
			'type'      => \Elementor\Controls_Manager::SELECT,
			'default'   => 'before',
			'options'   => [
				'before' => __( 'Before Text', 'rw-dealer-portal' ),
				'after'  => __( 'After Text', 'rw-dealer-portal' ),
			],
			'condition' => [ 'popup_dir_icon[value]!' => '' ],
		] );

		$this->end_controls_section();

		// ── Content: Map Settings ─────────────────────────────────────────
		$this->start_controls_section( 'section_map', [
			'label' => __( 'Map Settings', 'rw-dealer-portal' ),
		] );

		$this->add_control( 'map_height', [
			'label'      => __( 'Map Height', 'rw-dealer-portal' ),
			'type'       => \Elementor\Controls_Manager::SLIDER,
			'size_units' => [ 'px', 'vh' ],
			'range'      => [
				'px' => [ 'min' => 200, 'max' => 900, 'step' => 10 ],
				'vh' => [ 'min' => 20,  'max' => 100, 'step' => 1 ],
			],
			'default'   => [ 'size' => 450, 'unit' => 'px' ],
			'selectors' => [ '{{WRAPPER}} #rwdp-map' => 'height: {{SIZE}}{{UNIT}};' ],
		] );

		$this->end_controls_section();

		// ── Style: Popup Container ───────────────────────────────────────
		$this->start_controls_section( 'style_popup_container', [
			'label' => __( 'Popup Container', 'rw-dealer-portal' ),
			'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
		] );

		$this->add_control( 'popup_padding', [
			'label'      => __( 'Padding', 'rw-dealer-portal' ),
			'type'       => \Elementor\Controls_Manager::DIMENSIONS,
			'size_units' => [ 'px', 'em' ],
			'selectors'  => [ '{{WRAPPER}} .rwdp-infowindow' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ],
		] );

		$this->end_controls_section();

		// ── Style: Map Container ──────────────────────────────────────────
		$this->start_controls_section( 'style_map', [
			'label' => __( 'Map Container', 'rw-dealer-portal' ),
			'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
		] );

		$this->add_group_control( \Elementor\Group_Control_Border::get_type(), [
			'name'     => 'map_border',
			'selector' => '{{WRAPPER}} #rwdp-map',
		] );

		$this->add_control( 'map_border_radius', [
			'label'      => __( 'Border Radius', 'rw-dealer-portal' ),
			'type'       => \Elementor\Controls_Manager::DIMENSIONS,
			'size_units' => [ 'px', '%' ],
			'selectors'  => [ '{{WRAPPER}} #rwdp-map' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}; overflow: hidden;' ],
		] );

		$this->end_controls_section();

		// ── Style: Popup — Logo ───────────────────────────────────────────
		$this->start_controls_section( 'style_popup_logo', [
			'label' => __( 'Popup — Logo', 'rw-dealer-portal' ),
			'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
		] );

		$this->add_control( 'popup_logo_width', [
			'label'      => __( 'Width', 'rw-dealer-portal' ),
			'type'       => \Elementor\Controls_Manager::SLIDER,
			'size_units' => [ 'px', '%' ],
			'range'      => [ 'px' => [ 'min' => 20, 'max' => 300 ] ],
			'selectors'  => [ '{{WRAPPER}} .rwdp-infowindow__logo' => 'width: {{SIZE}}{{UNIT}};' ],
		] );

		$this->add_control( 'popup_logo_height', [
			'label'      => __( 'Height', 'rw-dealer-portal' ),
			'type'       => \Elementor\Controls_Manager::SLIDER,
			'size_units' => [ 'px' ],
			'range'      => [ 'px' => [ 'min' => 20, 'max' => 300 ] ],
			'selectors'  => [ '{{WRAPPER}} .rwdp-infowindow__logo' => 'height: {{SIZE}}{{UNIT}}; max-height: none;' ],
		] );

		$this->add_control( 'popup_logo_object_fit', [
			'label'     => __( 'Object Fit', 'rw-dealer-portal' ),
			'type'      => \Elementor\Controls_Manager::SELECT,
			'default'   => 'contain',
			'options'   => [
				'contain'    => __( 'Contain', 'rw-dealer-portal' ),
				'cover'      => __( 'Cover', 'rw-dealer-portal' ),
				'fill'       => __( 'Fill', 'rw-dealer-portal' ),
				'none'       => __( 'None', 'rw-dealer-portal' ),
				'scale-down' => __( 'Scale Down', 'rw-dealer-portal' ),
			],
			'selectors' => [ '{{WRAPPER}} .rwdp-infowindow__logo' => 'object-fit: {{VALUE}};' ],
		] );

		$this->end_controls_section();

		// ── Style: Popup — Title ──────────────────────────────────────────
		$this->start_controls_section( 'style_popup_title', [
			'label' => __( 'Popup — Title', 'rw-dealer-portal' ),
			'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
		] );

		$this->add_group_control( \Elementor\Group_Control_Typography::get_type(), [
			'name'     => 'popup_title_typography',
			'selector' => '{{WRAPPER}} .rwdp-infowindow__name',
		] );

		$this->add_control( 'popup_title_color', [
			'label'     => __( 'Color', 'rw-dealer-portal' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .rwdp-infowindow__name' => 'color: {{VALUE}};' ],
		] );

		$this->add_control( 'popup_title_padding', [
			'label'      => __( 'Padding', 'rw-dealer-portal' ),
			'type'       => \Elementor\Controls_Manager::DIMENSIONS,
			'size_units' => [ 'px', 'em' ],
			'selectors'  => [ '{{WRAPPER}} .rwdp-infowindow__name' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ],
		] );

		$this->end_controls_section();

		// ── Style: Popup — Details ────────────────────────────────────────
		$this->start_controls_section( 'style_popup_details', [
			'label' => __( 'Popup — Details', 'rw-dealer-portal' ),
			'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
		] );

		$this->add_group_control( \Elementor\Group_Control_Typography::get_type(), [
			'name'     => 'popup_details_typography',
			'selector' => '{{WRAPPER}} .rwdp-infowindow p, {{WRAPPER}} .rwdp-infowindow a',
		] );

		$this->add_control( 'popup_details_color', [
			'label'     => __( 'Color', 'rw-dealer-portal' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => [
				'{{WRAPPER}} .rwdp-infowindow p' => 'color: {{VALUE}};',
				'{{WRAPPER}} .rwdp-infowindow a' => 'color: {{VALUE}};',
			],
		] );

		$this->add_control( 'popup_details_padding', [
			'label'      => __( 'Padding', 'rw-dealer-portal' ),
			'type'       => \Elementor\Controls_Manager::DIMENSIONS,
			'size_units' => [ 'px', 'em' ],
			'selectors'  => [ '{{WRAPPER}} .rwdp-infowindow__details' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ],
		] );

		$this->add_control( 'popup_details_gap', [
			'label'      => __( 'Gap Between Items', 'rw-dealer-portal' ),
			'type'       => \Elementor\Controls_Manager::SLIDER,
			'size_units' => [ 'px' ],
			'range'      => [ 'px' => [ 'min' => 0, 'max' => 30 ] ],
			'selectors'  => [ '{{WRAPPER}} .rwdp-infowindow p' => 'margin-bottom: {{SIZE}}{{UNIT}};' ],
		] );

		$this->end_controls_section();

		// ── Style: Popup — Directions Button ─────────────────────────────
		$this->start_controls_section( 'style_popup_dir_btn', [
			'label' => __( 'Popup — Directions Button', 'rw-dealer-portal' ),
			'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
		] );

		$this->add_group_control( \Elementor\Group_Control_Typography::get_type(), [
			'name'     => 'popup_dir_btn_typography',
			'selector' => '{{WRAPPER}} .rwdp-infowindow .rwdp-popup-dir-btn',
		] );

		$this->add_control( 'popup_dir_icon_size', [
			'label'      => __( 'Icon Size', 'rw-dealer-portal' ),
			'type'       => \Elementor\Controls_Manager::SLIDER,
			'size_units' => [ 'px', 'em', 'rem' ],
			'range'      => [ 'px' => [ 'min' => 8, 'max' => 60 ] ],
			'condition'  => [ 'popup_dir_icon[value]!' => '' ],
			'selectors'  => [
				'{{WRAPPER}} .rwdp-infowindow .rwdp-popup-dir-btn i'   => 'font-size: {{SIZE}}{{UNIT}};',
				'{{WRAPPER}} .rwdp-infowindow .rwdp-popup-dir-btn svg' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
			],
		] );

		$this->add_control( 'popup_dir_icon_gap', [
			'label'      => __( 'Icon Gap', 'rw-dealer-portal' ),
			'type'       => \Elementor\Controls_Manager::SLIDER,
			'size_units' => [ 'px', 'em' ],
			'range'      => [ 'px' => [ 'min' => 0, 'max' => 30 ] ],
			'condition'  => [ 'popup_dir_icon[value]!' => '' ],
			'selectors'  => [
				'{{WRAPPER}} .rwdp-infowindow .rwdp-popup-dir-btn' => 'display: inline-flex; align-items: center; gap: {{SIZE}}{{UNIT}};',
			],
		] );

		$this->start_controls_tabs( 'popup_dir_btn_tabs' );

		$this->start_controls_tab( 'popup_dir_btn_normal', [
			'label' => __( 'Normal', 'rw-dealer-portal' ),
		] );

		$this->add_control( 'popup_dir_btn_color', [
			'label'     => __( 'Text Color', 'rw-dealer-portal' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .rwdp-infowindow .rwdp-popup-dir-btn' => 'color: {{VALUE}};' ],
		] );

		$this->add_control( 'popup_dir_btn_bg', [
			'label'     => __( 'Background Color', 'rw-dealer-portal' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .rwdp-infowindow .rwdp-popup-dir-btn' => 'background-color: {{VALUE}};' ],
		] );

		$this->end_controls_tab();

		$this->start_controls_tab( 'popup_dir_btn_hover', [
			'label' => __( 'Hover', 'rw-dealer-portal' ),
		] );

		$this->add_control( 'popup_dir_btn_color_hover', [
			'label'     => __( 'Text Color', 'rw-dealer-portal' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .rwdp-infowindow .rwdp-popup-dir-btn:hover' => 'color: {{VALUE}};' ],
		] );

		$this->add_control( 'popup_dir_btn_bg_hover', [
			'label'     => __( 'Background Color', 'rw-dealer-portal' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .rwdp-infowindow .rwdp-popup-dir-btn:hover' => 'background-color: {{VALUE}};' ],
		] );

		$this->end_controls_tab();
		$this->end_controls_tabs();

		$this->add_group_control( \Elementor\Group_Control_Border::get_type(), [
			'name'      => 'popup_dir_btn_border',
			'selector'  => '{{WRAPPER}} .rwdp-infowindow .rwdp-popup-dir-btn',
			'separator' => 'before',
		] );

		$this->add_control( 'popup_dir_btn_border_radius', [
			'label'      => __( 'Border Radius', 'rw-dealer-portal' ),
			'type'       => \Elementor\Controls_Manager::DIMENSIONS,
			'size_units' => [ 'px', '%', 'em' ],
			'selectors'  => [ '{{WRAPPER}} .rwdp-infowindow .rwdp-popup-dir-btn' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ],
		] );

		$this->add_control( 'popup_dir_btn_padding', [
			'label'      => __( 'Padding', 'rw-dealer-portal' ),
			'type'       => \Elementor\Controls_Manager::DIMENSIONS,
			'size_units' => [ 'px', 'em' ],
			'selectors'  => [ '{{WRAPPER}} .rwdp-infowindow .rwdp-popup-dir-btn' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ],
		] );

		$this->end_controls_section();

		// ── Style: Popup — Contact Button ─────────────────────────────────
		$this->start_controls_section( 'style_popup_btn', [
			'label' => __( 'Popup — Contact Button', 'rw-dealer-portal' ),
			'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
		] );

		$this->add_group_control( \Elementor\Group_Control_Typography::get_type(), [
			'name'     => 'popup_btn_typography',
			'selector' => '{{WRAPPER}} .rwdp-infowindow .rwdp-contact-trigger',
		] );

		$this->start_controls_tabs( 'popup_btn_tabs' );

		$this->start_controls_tab( 'popup_btn_normal', [
			'label' => __( 'Normal', 'rw-dealer-portal' ),
		] );

		$this->add_control( 'popup_btn_color', [
			'label'     => __( 'Text Color', 'rw-dealer-portal' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .rwdp-infowindow .rwdp-contact-trigger' => 'color: {{VALUE}};' ],
		] );

		$this->add_control( 'popup_btn_bg', [
			'label'     => __( 'Background Color', 'rw-dealer-portal' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .rwdp-infowindow .rwdp-contact-trigger' => 'background-color: {{VALUE}};' ],
		] );

		$this->end_controls_tab();

		$this->start_controls_tab( 'popup_btn_hover', [
			'label' => __( 'Hover', 'rw-dealer-portal' ),
		] );

		$this->add_control( 'popup_btn_color_hover', [
			'label'     => __( 'Text Color', 'rw-dealer-portal' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .rwdp-infowindow .rwdp-contact-trigger:hover' => 'color: {{VALUE}};' ],
		] );

		$this->add_control( 'popup_btn_bg_hover', [
			'label'     => __( 'Background Color', 'rw-dealer-portal' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .rwdp-infowindow .rwdp-contact-trigger:hover' => 'background-color: {{VALUE}};' ],
		] );

		$this->end_controls_tab();
		$this->end_controls_tabs();

		$this->add_group_control( \Elementor\Group_Control_Border::get_type(), [
			'name'      => 'popup_btn_border',
			'selector'  => '{{WRAPPER}} .rwdp-infowindow .rwdp-contact-trigger',
			'separator' => 'before',
		] );

		$this->add_control( 'popup_btn_border_radius', [
			'label'      => __( 'Border Radius', 'rw-dealer-portal' ),
			'type'       => \Elementor\Controls_Manager::DIMENSIONS,
			'size_units' => [ 'px', '%', 'em' ],
			'selectors'  => [ '{{WRAPPER}} .rwdp-infowindow .rwdp-contact-trigger' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ],
		] );

		$this->add_control( 'popup_btn_padding', [
			'label'      => __( 'Padding', 'rw-dealer-portal' ),
			'type'       => \Elementor\Controls_Manager::DIMENSIONS,
			'size_units' => [ 'px', 'em' ],
			'selectors'  => [ '{{WRAPPER}} .rwdp-infowindow .rwdp-contact-trigger' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ],
		] );

		$this->end_controls_section();
	}

	// -----------------------------------------------------------------------
	// Render
	// -----------------------------------------------------------------------
	protected function render() {
		$plugin_settings = get_option( 'rwdp_settings', [] );
		$s               = $this->get_settings_for_display();

		if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
			$height = $s['map_height']['size'] ?? 450;
			$unit   = $s['map_height']['unit'] ?? 'px';
			?>
			<div class="rwdp-editor-placeholder rwdp-editor-placeholder--map" style="height:<?php echo esc_attr( $height . $unit ); ?>">
				<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="48" height="48" aria-hidden="true">
					<path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/>
				</svg>
				<p>Dealer Map</p>
				<small>Map renders on the published page</small>
			</div>
			<?php
			return;
		}

		$toggles = [
			'logo'    => ( $s['show_logo']    ?? 'yes' ) === 'yes' ? '1' : '0',
			'phone'   => ( $s['show_phone']   ?? 'yes' ) === 'yes' ? '1' : '0',
			'website' => ( $s['show_website'] ?? 'yes' ) === 'yes' ? '1' : '0',
			'hours'   => ( $s['show_hours']   ?? 'yes' ) === 'yes' ? '1' : '0',
			'contact' => ( $s['show_contact'] ?? 'yes' ) === 'yes' ? '1' : '0',
		];

		$dir_icon_html = '';
		$dir_icon_pos  = $s['popup_dir_icon_position'] ?? 'before';
		if ( ! empty( $s['popup_dir_icon']['value'] ) ) {
			ob_start();
			\Elementor\Icons_Manager::render_icon( $s['popup_dir_icon'], [
				'aria-hidden' => 'true',
				'class'       => 'rwdp-dir-icon',
			] );
			$dir_icon_html = ob_get_clean();
		}
		?>
		<div class="rwdp-finder__map-wrap">
			<div id="rwdp-map" class="rwdp-finder__map"
			     data-show-logo="<?php echo esc_attr( $toggles['logo'] ); ?>"
			     data-show-phone="<?php echo esc_attr( $toggles['phone'] ); ?>"
			     data-show-website="<?php echo esc_attr( $toggles['website'] ); ?>"
			     data-show-hours="<?php echo esc_attr( $toggles['hours'] ); ?>"
			     data-show-contact="<?php echo esc_attr( $toggles['contact'] ); ?>"
			     data-contact-text="<?php echo esc_attr( $s['contact_text'] ?? __( 'Contact This Dealer', 'rw-dealer-portal' ) ); ?>"
			     data-directions-text="<?php echo esc_attr( $s['directions_text'] ?? __( 'Get Directions', 'rw-dealer-portal' ) ); ?>"
			     data-directions-icon="<?php echo esc_attr( $dir_icon_html ); ?>"
			     data-directions-icon-position="<?php echo esc_attr( $dir_icon_pos ); ?>"
			></div>
		</div>
		<?php if ( empty( $plugin_settings['google_maps_api_key'] ) ) : ?>
		<p class="rwdp-notice rwdp-notice--warning">
			<?php esc_html_e( 'Google Maps API key is not configured. Please add it in Dealer Portal → Settings.', 'rw-dealer-portal' ); ?>
		</p>
		<?php endif; ?>
		<?php
	}
}
