<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class RWDP_Tag_Address extends \Elementor\Core\DynamicTags\Tag {

	public function get_name() {
		return 'rwdp_dealer_address';
	}

	public function get_title() {
		return __( 'Dealer Address', 'rw-dealer-portal' );
	}

	public function get_group() {
		return 'rw-dealer-portal';
	}

	public function get_categories() {
		return [ \Elementor\Modules\DynamicTags\Module::TEXT_CATEGORY ];
	}

	public function render() {
		$id      = get_the_ID();
		$street  = get_post_meta( $id, '_rwdp_address', true );
		$city    = get_post_meta( $id, '_rwdp_city',    true );
		$state   = get_post_meta( $id, '_rwdp_state',   true );
		$zip     = get_post_meta( $id, '_rwdp_zip',     true );

		$parts = [];

		if ( $street ) {
			$parts[] = esc_html( $street );
		}

		$line2 = implode( ', ', array_filter( [ $city, $state ] ) );
		if ( $zip ) {
			$line2 = trim( $line2 . ' ' . $zip );
		}
		if ( $line2 ) {
			$parts[] = esc_html( $line2 );
		}

		echo implode( '<br>', $parts ); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped above
	}
}
