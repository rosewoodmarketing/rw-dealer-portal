<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class RWDP_Assets_Widget extends \Elementor\Widget_Base {

	public function get_name() {
		return 'rwdp_assets';
	}

	public function get_title() {
		return __( 'Dealer Digital Assets', 'rw-dealer-portal' );
	}

	public function get_icon() {
		return 'eicon-folder';
	}

	public function get_categories() {
		return [ 'rw-dealer-portal' ];
	}

	public function get_keywords() {
		return [ 'assets', 'downloads', 'dealer', 'portal' ];
	}

	protected function register_controls() {
		// Controls will be added when building out the widget.
	}

	protected function render() {
		echo rwdp_assets_shortcode( $this->get_settings_for_display() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- output escaping handled within the shortcode function
	}
}
