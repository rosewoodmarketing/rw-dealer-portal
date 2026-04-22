<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class RWDP_Tag_Logo extends \Elementor\Core\DynamicTags\Data_Tag {

	public function get_name() {
		return 'rwdp_dealer_logo';
	}

	public function get_title() {
		return __( 'Dealer Logo', 'rw-dealer-portal' );
	}

	public function get_group() {
		return 'rw-dealer-portal';
	}

	public function get_categories() {
		return [ \Elementor\Modules\DynamicTags\Module::IMAGE_CATEGORY ];
	}

	public function get_value( array $options = [] ) {
		$logo_id = (int) get_post_meta( get_the_ID(), '_rwdp_logo_id', true );
		if ( ! $logo_id ) {
			return [];
		}
		$url = wp_get_attachment_url( $logo_id );
		if ( ! $url ) {
			// Attachment has been deleted from the media library.
			return [];
		}
		return [
			'id'  => $logo_id,
			'url' => $url,
		];
	}
}
