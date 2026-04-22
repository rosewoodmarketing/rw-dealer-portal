<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class RWDP_Tag_Email extends \Elementor\Core\DynamicTags\Tag {

	public function get_name() {
		return 'rwdp_dealer_email';
	}

	public function get_title() {
		return __( 'Dealer Public Email', 'rw-dealer-portal' );
	}

	public function get_group() {
		return 'rw-dealer-portal';
	}

	public function get_categories() {
		return [ \Elementor\Modules\DynamicTags\Module::TEXT_CATEGORY ];
	}

	public function render() {
		$value = get_post_meta( get_the_ID(), '_rwdp_public_email', true );
		echo esc_html( $value );
	}
}
