<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) exit;

// Remove custom roles
$roles_to_remove = [ 'rwdp_dealer', 'rwdp_portal_manager' ];
foreach ( $roles_to_remove as $role ) {
	remove_role( $role );
}

// Remove capabilities added to the Administrator role
$admin = get_role( 'administrator' );
if ( $admin ) {
	$admin->remove_cap( 'view_portal' );
	$admin->remove_cap( 'manage_rwdp_portal' );
	$admin->remove_cap( 'view_rwdp_submissions' );
}

// Remove options
delete_option( 'rwdp_settings' );
delete_option( 'rwdp_page_ids' );
delete_option( 'rwdp_dealer_types_seeded' );
delete_transient( 'rwdp_github_updater' );

// Remove user meta for all users
global $wpdb;
$meta_keys = [
	'_rwdp_account_status',
	'_rwdp_dealer_ids',
	'_rwdp_company',
];
foreach ( $meta_keys as $key ) {
	$wpdb->delete( $wpdb->usermeta, [ 'meta_key' => $key ] );
}

// Flush rewrite rules
flush_rewrite_rules();
