<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class RWDP_Tag_Phone extends \Elementor\Core\DynamicTags\Tag {

	public function get_name() {
		return 'rwdp_dealer_phone';
	}

	public function get_title() {
		return __( 'Dealer Phone', 'rw-dealer-portal' );
	}

	public function get_group() {
		return 'rw-dealer-portal';
	}

	public function get_categories() {
		return [ \Elementor\Modules\DynamicTags\Module::TEXT_CATEGORY ];
	}

	public function render() {
		$value = get_post_meta( get_the_ID(), '_rwdp_phone', true );
		echo esc_html( $value );
	}
}
