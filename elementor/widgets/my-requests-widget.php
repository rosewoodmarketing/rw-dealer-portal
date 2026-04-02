<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class RWDP_My_Requests_Widget extends \Elementor\Widget_Base {

	public function get_name() {
		return 'rwdp_my_requests';
	}

	public function get_title() {
		return __( 'Dealer My Requests', 'rw-dealer-portal' );
	}

	public function get_icon() {
		return 'eicon-mail';
	}

	public function get_categories() {
		return [ 'rw-dealer-portal' ];
	}

	public function get_keywords() {
		return [ 'requests', 'submissions', 'dealer', 'portal' ];
	}

	protected function register_controls() {
		// Controls will be added when building out the widget.
	}

	protected function render() {
		echo rwdp_my_requests_shortcode( $this->get_settings_for_display() );
	}
}
