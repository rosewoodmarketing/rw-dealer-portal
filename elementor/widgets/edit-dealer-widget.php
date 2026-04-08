<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class RWDP_Edit_Dealer_Widget extends \Elementor\Widget_Base {

	public function get_name() {
		return 'rwdp_edit_dealer';
	}

	public function get_title() {
		return __( 'Edit Dealer Profile', 'rw-dealer-portal' );
	}

	public function get_icon() {
		return 'eicon-edit';
	}

	public function get_categories() {
		return [ 'rw-dealer-portal' ];
	}

	public function get_keywords() {
		return [ 'dealer', 'edit', 'profile', 'portal' ];
	}

	protected function register_controls() {
		// Controls will be added when building out the widget.
	}

	protected function render() {
		echo rwdp_edit_dealer_shortcode( $this->get_settings_for_display() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- output escaping handled within the shortcode function
	}
}
