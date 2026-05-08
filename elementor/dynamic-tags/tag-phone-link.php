<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class RWDP_Tag_Phone_Link extends \Elementor\Core\DynamicTags\Data_Tag {

	public function get_name() {
		return 'rwdp_dealer_phone_link';
	}

	public function get_title() {
		return __( 'Dealer Phone Link', 'rw-dealer-portal' );
	}

	public function get_group() {
		return 'rw-dealer-portal';
	}

	public function get_categories() {
		return [ \Elementor\Modules\DynamicTags\Module::URL_CATEGORY ];
	}

	public function get_value( array $options = [] ) {
		$value = get_post_meta( get_the_ID(), '_rwdp_phone', true );
		if ( empty( $value ) ) {
			return '';
		}
		// Strip everything except digits and leading + for international numbers.
		$digits = preg_replace( '/[^\d+]/', '', (string) $value );
		return 'tel:' . $digits;
	}
}
