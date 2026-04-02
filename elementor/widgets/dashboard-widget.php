<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class RWDP_Dashboard_Widget extends \Elementor\Widget_Base {

	public function get_name() {
		return 'rwdp_dashboard';
	}

	public function get_title() {
		return __( 'Dealer Dashboard', 'rw-dealer-portal' );
	}

	public function get_icon() {
		return 'eicon-dashboard';
	}

	public function get_categories() {
		return [ 'rw-dealer-portal' ];
	}

	public function get_keywords() {
		return [ 'dashboard', 'dealer', 'portal' ];
	}

	protected function register_controls() {
		// Controls will be added when building out the widget.
	}

	protected function render() {
		echo rwdp_dashboard_shortcode( $this->get_settings_for_display() );
	}
}
