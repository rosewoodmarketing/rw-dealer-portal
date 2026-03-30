<?php
if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'init', 'rwdp_register_taxonomies' );

function rwdp_register_taxonomies() {

	// ── Dealer Type ─────────────────────────────────────────────────────────
	register_taxonomy( 'rw_dealer_type', 'rw_dealer', [
		'labels' => [
			'name'              => __( 'Dealer Types', 'rw-dealer-portal' ),
			'singular_name'     => __( 'Dealer Type', 'rw-dealer-portal' ),
			'search_items'      => __( 'Search Dealer Types', 'rw-dealer-portal' ),
			'all_items'         => __( 'All Dealer Types', 'rw-dealer-portal' ),
			'edit_item'         => __( 'Edit Dealer Type', 'rw-dealer-portal' ),
			'update_item'       => __( 'Update Dealer Type', 'rw-dealer-portal' ),
			'add_new_item'      => __( 'Add New Dealer Type', 'rw-dealer-portal' ),
			'new_item_name'     => __( 'New Dealer Type Name', 'rw-dealer-portal' ),
			'menu_name'         => __( 'Dealer Types', 'rw-dealer-portal' ),
		],
		'hierarchical'      => true,
		'public'            => true,
		'show_ui'           => true,
		'show_in_menu'      => true,
		'show_in_rest'      => true,
		'show_admin_column' => true,
		'rewrite'           => [ 'slug' => 'dealer-type', 'with_front' => false ],
	] );

	// Seed default terms on first run
	if ( ! get_option( 'rwdp_dealer_types_seeded' ) ) {
		$defaults = [ 'Dealer', 'Distributor', 'Installer' ];
		foreach ( $defaults as $term ) {
			if ( ! term_exists( $term, 'rw_dealer_type' ) ) {
				wp_insert_term( $term, 'rw_dealer_type' );
			}
		}
		update_option( 'rwdp_dealer_types_seeded', true );
	}

	// ── Asset Category ──────────────────────────────────────────────────────
	register_taxonomy( 'rw_asset_category', 'rw_asset', [
		'labels' => [
			'name'              => __( 'Asset Categories', 'rw-dealer-portal' ),
			'singular_name'     => __( 'Asset Category', 'rw-dealer-portal' ),
			'search_items'      => __( 'Search Asset Categories', 'rw-dealer-portal' ),
			'all_items'         => __( 'All Asset Categories', 'rw-dealer-portal' ),
			'edit_item'         => __( 'Edit Asset Category', 'rw-dealer-portal' ),
			'update_item'       => __( 'Update Asset Category', 'rw-dealer-portal' ),
			'add_new_item'      => __( 'Add New Asset Category', 'rw-dealer-portal' ),
			'new_item_name'     => __( 'New Asset Category Name', 'rw-dealer-portal' ),
			'menu_name'         => __( 'Categories', 'rw-dealer-portal' ),
		],
		'hierarchical'      => true,
		'public'            => false,
		'show_ui'           => true,
		'show_in_menu'      => true,
		'show_in_rest'      => true,
		'show_admin_column' => true,
		'rewrite'           => false,
	] );

	// Seed default asset category terms on first run
	if ( ! get_option( 'rwdp_asset_categories_seeded' ) ) {
		$defaults = [ 'Photos', 'PDF Catalogs', 'Videos', 'Logos', 'Brand Manuals' ];
		foreach ( $defaults as $term ) {
			if ( ! term_exists( $term, 'rw_asset_category' ) ) {
				wp_insert_term( $term, 'rw_asset_category' );
			}
		}
		update_option( 'rwdp_asset_categories_seeded', true );
	}
}
