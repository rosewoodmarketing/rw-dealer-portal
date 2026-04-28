<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// =============================================================================
// Elementor Custom Query: Top-Level Assets
//
// Query ID: rwdp_top_level_assets
//
// Usage in Elementor:
//   Loop Grid widget → Query tab → Source → "Custom Query"
//   → Query ID: rwdp_top_level_assets
// =============================================================================

add_action(
	'elementor/query/rwdp_top_level_assets',
	function ( $query ) {
		$query->set( 'post_type',      'rw_asset' );
		$query->set( 'post_status',    'publish' );
		$query->set( 'post_parent',    0 );          // Top-level only
		$query->set( 'orderby',        'menu_order' );
		$query->set( 'order',          'ASC' );
		$query->set( 'posts_per_page', -1 );
	}
);

// =============================================================================
// Elementor Custom Query: Child Assets of Current Post
//
// Query ID: rwdp_child_assets
//
// Usage in Elementor:
//   On a single rw_asset template, Loop Grid widget → Query tab
//   → Source → "Custom Query" → Query ID: rwdp_child_assets
// =============================================================================

add_action(
	'elementor/query/rwdp_child_assets',
	function ( $query ) {
		$current_id = get_queried_object_id();
		if ( ! $current_id ) {
			return;
		}
		$query->set( 'post_type',      'rw_asset' );
		$query->set( 'post_status',    'publish' );
		$query->set( 'post_parent',    $current_id );
		$query->set( 'orderby',        'menu_order' );
		$query->set( 'order',          'ASC' );
		$query->set( 'posts_per_page', -1 );
	}
);
