<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class RWDP_My_Account_Widget extends \Elementor\Widget_Base {

	public function get_name() {
		return 'rwdp_my_account';
	}

	public function get_title() {
		return __( 'Dealer My Account', 'rw-dealer-portal' );
	}

	public function get_icon() {
		return 'eicon-person';
	}

	public function get_categories() {
		return [ 'rw-dealer-portal' ];
	}

	public function get_keywords() {
		return [ 'account', 'dealer', 'portal', 'profile' ];
	}

	protected function register_controls() {
		// Controls will be added when building out the widget.
	}

	protected function render() {
		echo rwdp_my_account_shortcode( $this->get_settings_for_display() );
	}
}
