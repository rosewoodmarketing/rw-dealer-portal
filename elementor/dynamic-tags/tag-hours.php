<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class RWDP_Tag_Hours extends \Elementor\Core\DynamicTags\Tag {

	public function get_name() {
		return 'rwdp_dealer_hours';
	}

	public function get_title() {
		return __( 'Dealer Hours', 'rw-dealer-portal' );
	}

	public function get_group() {
		return 'rw-dealer-portal';
	}

	public function get_categories() {
		return [ \Elementor\Modules\DynamicTags\Module::TEXT_CATEGORY ];
	}

	public function render() {
		$value = get_post_meta( get_the_ID(), '_rwdp_hours', true );
		echo nl2br( esc_html( $value ) ); // phpcs:ignore WordPress.Security.EscapeOutput -- nl2br on escaped string
	}
}
