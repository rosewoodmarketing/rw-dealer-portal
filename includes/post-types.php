<?php
if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'init', 'rwdp_register_post_types' );

function rwdp_register_post_types() {

	// ── Dealer CPT ──────────────────────────────────────────────────────────
	register_post_type( 'rw_dealer', [
		'labels' => [
			'name'               => __( 'Dealers', 'rw-dealer-portal' ),
			'singular_name'      => __( 'Dealer', 'rw-dealer-portal' ),
			'add_new'            => __( 'Add New Dealer', 'rw-dealer-portal' ),
			'add_new_item'       => __( 'Add New Dealer', 'rw-dealer-portal' ),
			'edit_item'          => __( 'Edit Dealer', 'rw-dealer-portal' ),
			'new_item'           => __( 'New Dealer', 'rw-dealer-portal' ),
			'view_item'          => __( 'View Dealer', 'rw-dealer-portal' ),
			'search_items'       => __( 'Search Dealers', 'rw-dealer-portal' ),
			'not_found'          => __( 'No dealers found', 'rw-dealer-portal' ),
			'not_found_in_trash' => __( 'No dealers found in trash', 'rw-dealer-portal' ),
		],
		'public'             => true,
		'show_ui'            => true,
		'show_in_menu'       => false, // shown in custom admin menu
		'show_in_rest'       => true,
		'supports'           => [ 'title', 'thumbnail', 'editor' ],
		'has_archive'        => false,
		'rewrite'            => [ 'slug' => 'dealer', 'with_front' => false ],
		'capability_type'    => [ 'rw_dealer', 'rw_dealers' ],
		'map_meta_cap'       => true,
		'menu_icon'          => 'dashicons-store',
	] );

	// ── Asset CPT ───────────────────────────────────────────────────────────
	register_post_type( 'rw_asset', [
		'labels' => [
			'name'               => __( 'Assets', 'rw-dealer-portal' ),
			'singular_name'      => __( 'Asset', 'rw-dealer-portal' ),
			'add_new'            => __( 'Add New Asset', 'rw-dealer-portal' ),
			'add_new_item'       => __( 'Add New Asset', 'rw-dealer-portal' ),
			'edit_item'          => __( 'Edit Asset', 'rw-dealer-portal' ),
			'new_item'           => __( 'New Asset', 'rw-dealer-portal' ),
			'view_item'          => __( 'View Asset', 'rw-dealer-portal' ),
			'search_items'       => __( 'Search Assets', 'rw-dealer-portal' ),
			'not_found'          => __( 'No assets found', 'rw-dealer-portal' ),
			'not_found_in_trash' => __( 'No assets found in trash', 'rw-dealer-portal' ),
		],
		'public'             => true,
		'publicly_queryable' => true,
		'hierarchical'       => true,
		'show_ui'            => true,
		'show_in_menu'       => false,
		'show_in_rest'       => true,
		'supports'           => [ 'title', 'editor', 'thumbnail', 'excerpt', 'page-attributes' ],
		'has_archive'        => false,
		'rewrite'            => [ 'slug' => 'portal-asset', 'with_front' => false ],
		'capability_type'    => [ 'rw_asset', 'rw_assets' ],
		'map_meta_cap'       => true,
	] );

}
