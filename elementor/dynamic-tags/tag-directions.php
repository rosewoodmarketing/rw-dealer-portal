<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class RWDP_Tag_Directions extends \Elementor\Core\DynamicTags\Data_Tag {

	public function get_name() {
		return 'rwdp_dealer_directions';
	}

	public function get_title() {
		return __( 'Dealer Directions Link', 'rw-dealer-portal' );
	}

	public function get_group() {
		return 'rw-dealer-portal';
	}

	public function get_categories() {
		return [
			\Elementor\Modules\DynamicTags\Module::URL_CATEGORY,
			\Elementor\Modules\DynamicTags\Module::TEXT_CATEGORY,
		];
	}

	public function get_value( array $options = [] ) {
		$id      = get_the_ID();
		$address = get_post_meta( $id, '_rwdp_address', true );
		$city    = get_post_meta( $id, '_rwdp_city',    true );
		$state   = get_post_meta( $id, '_rwdp_state',   true );
		$zip     = get_post_meta( $id, '_rwdp_zip',     true );

		$parts = array_filter( [ $address, $city, $state, $zip ] );
		if ( empty( $parts ) ) {
			return '';
		}

		return 'https://www.google.com/maps/dir/?api=1&destination=' . rawurlencode( implode( ', ', $parts ) );
	}
}
