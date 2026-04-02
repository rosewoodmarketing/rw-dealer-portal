<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class RWDP_Login_Form_Widget extends \Elementor\Widget_Base {

	public function get_name() {
		return 'rwdp_login_form';
	}

	public function get_title() {
		return __( 'Dealer Login Form', 'rw-dealer-portal' );
	}

	public function get_icon() {
		return 'eicon-lock-user';
	}

	public function get_categories() {
		return [ 'rw-dealer-portal' ];
	}

	public function get_keywords() {
		return [ 'login', 'dealer', 'portal' ];
	}

	protected function register_controls() {
		// Controls will be added when building out the widget.
	}

	protected function render() {
		echo rwdp_login_form_shortcode( $this->get_settings_for_display() );
	}
}
