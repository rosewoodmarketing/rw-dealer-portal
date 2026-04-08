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
		'show_in_menu'      => false,
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

}
