<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class RWDP_Tag_Website extends \Elementor\Core\DynamicTags\Tag {

	public function get_name() {
		return 'rwdp_dealer_website';
	}

	public function get_title() {
		return __( 'Dealer Website', 'rw-dealer-portal' );
	}

	public function get_group() {
		return 'rw-dealer-portal';
	}

	public function get_categories() {
		// TEXT_CATEGORY only: render() outputs the scheme-stripped display text
		// (e.g. "example.com"). For a clickable link, use the Dealer Directions
		// tag or wire the button URL directly to the _rwdp_website custom field.
		// URL_CATEGORY is intentionally omitted because render() would produce a
		// scheme-less relative URL and break any href that consumed it.
		return [ \Elementor\Modules\DynamicTags\Module::TEXT_CATEGORY ];
	}

	public function render() {
		$value = get_post_meta( get_the_ID(), '_rwdp_website', true );
		echo esc_html( preg_replace( '#^https?://(www\.)?#i', '', (string) $value ) );
	}
}
