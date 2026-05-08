<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class RWDP_Tag_Website_URL extends \Elementor\Core\DynamicTags\Data_Tag {

	public function get_name() {
		return 'rwdp_dealer_website_url';
	}

	public function get_title() {
		return __( 'Dealer Website URL', 'rw-dealer-portal' );
	}

	public function get_group() {
		return 'rw-dealer-portal';
	}

	public function get_categories() {
		return [ \Elementor\Modules\DynamicTags\Module::URL_CATEGORY ];
	}

	public function get_value( array $options = [] ) {
		$value = get_post_meta( get_the_ID(), '_rwdp_website', true );
		if ( empty( $value ) ) {
			return '';
		}
		// Ensure the stored value has a scheme (field auto-adds https:// on save,
		// but guard here as well so the href is always absolute).
		if ( ! preg_match( '#^https?://#i', $value ) ) {
			$value = 'https://' . $value;
		}
		return esc_url( $value );
	}
}
