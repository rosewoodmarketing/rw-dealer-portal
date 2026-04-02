<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Elementor integration for RW Dealer Portal.
 * Registers the custom widget category and all plugin widgets.
 * Only runs when Elementor is active.
 */
final class RWDP_Elementor_Manager {

	public static function init() {
		add_action( 'elementor/elements/categories_registered', [ __CLASS__, 'register_category' ] );
		add_action( 'elementor/widgets/register', [ __CLASS__, 'register_widgets' ] );
	}

	public static function register_category( $elements_manager ) {
		$elements_manager->add_category(
			'rw-dealer-portal',
			[
				'title' => __( 'RW Dealer Portal', 'rw-dealer-portal' ),
				'icon'  => 'fa fa-store',
			]
		);
	}

	public static function register_widgets( $widgets_manager ) {
		$widget_files = [
			'login-form-widget',
			'dashboard-widget',
			'my-account-widget',
			'edit-dealer-widget',
			'my-requests-widget',
			'assets-widget',
			'dealer-search-widget',
			'dealer-map-widget',
			'dealer-list-widget',
		];

		foreach ( $widget_files as $file ) {
			require_once RWDP_PLUGIN_DIR . 'elementor/widgets/' . $file . '.php';
		}

		$widget_classes = [
			'RWDP_Login_Form_Widget',
			'RWDP_Dashboard_Widget',
			'RWDP_My_Account_Widget',
			'RWDP_Edit_Dealer_Widget',
			'RWDP_My_Requests_Widget',
			'RWDP_Assets_Widget',
			'RWDP_Dealer_Search_Widget',
			'RWDP_Dealer_Map_Widget',
			'RWDP_Dealer_List_Widget',
		];

		foreach ( $widget_classes as $class ) {
			$widgets_manager->register( new $class() );
		}
	}
}

if ( did_action( 'elementor/loaded' ) ) {
	RWDP_Elementor_Manager::init();
} else {
	add_action( 'elementor/loaded', [ 'RWDP_Elementor_Manager', 'init' ] );
}
