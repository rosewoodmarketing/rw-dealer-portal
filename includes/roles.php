<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Create custom roles on plugin activation.
 * Roles are only added if they don't already exist (idempotent).
 */
function rwdp_create_roles() {
	// Dealer role — portal access only, no WP admin
	if ( ! get_role( 'rwdp_dealer' ) ) {
		add_role(
			'rwdp_dealer',
			__( 'Dealer', 'rw-dealer-portal' ),
			[
				'read'                => true,
				'view_portal'         => true,
				'edit_dealer_profile' => true,
			]
		);
	}

	// Portal Manager — can manage dealers, assets, and pending users, but not WP core content
	if ( ! get_role( 'rwdp_portal_manager' ) ) {
		add_role(
			'rwdp_portal_manager',
			__( 'Portal Manager', 'rw-dealer-portal' ),
			[
				'read'               => true,
				'view_portal'        => true,

				// User management
				'list_users'         => true,
				'create_users'       => true,
				'edit_users'         => true,
				'promote_users'      => true,
				'delete_users'       => true,

				// Dealer CPT
				'edit_rw_dealer'              => true,
				'read_rw_dealer'              => true,
				'delete_rw_dealer'            => true,
				'edit_rw_dealers'             => true,
				'edit_others_rw_dealers'      => true,
				'edit_published_rw_dealers'   => true,
				'edit_private_rw_dealers'     => true,
				'publish_rw_dealers'          => true,
				'read_private_rw_dealers'     => true,
				'delete_rw_dealers'           => true,
				'delete_others_rw_dealers'    => true,
				'delete_published_rw_dealers' => true,
				'delete_private_rw_dealers'   => true,

				// Asset CPT
				'edit_rw_asset'              => true,
				'read_rw_asset'              => true,
				'delete_rw_asset'            => true,
				'edit_rw_assets'             => true,
				'edit_others_rw_assets'      => true,
				'edit_published_rw_assets'   => true,
				'edit_private_rw_assets'     => true,
				'publish_rw_assets'          => true,
				'read_private_rw_assets'     => true,
				'delete_rw_assets'           => true,
				'delete_others_rw_assets'    => true,
				'delete_published_rw_assets' => true,
				'delete_private_rw_assets'   => true,

				// Submissions (view only)
				'read_rw_submission'     => true,
				'read_private_rw_submissions' => true,

				// Custom
				'view_rwdp_submissions'  => true,
				'manage_rwdp_portal'     => true,
			]
		);
	}

	// Grant administrator all custom caps so CPT screens work normally
	$admin = get_role( 'administrator' );
	if ( $admin ) {
		$admin->add_cap( 'view_portal' );
		$admin->add_cap( 'manage_rwdp_portal' );
		$admin->add_cap( 'view_rwdp_submissions' );

		// Dealer CPT caps
		$admin->add_cap( 'edit_rw_dealer' );
		$admin->add_cap( 'read_rw_dealer' );
		$admin->add_cap( 'delete_rw_dealer' );
		$admin->add_cap( 'edit_rw_dealers' );
		$admin->add_cap( 'edit_others_rw_dealers' );
		$admin->add_cap( 'publish_rw_dealers' );
		$admin->add_cap( 'read_private_rw_dealers' );
		$admin->add_cap( 'delete_rw_dealers' );
		$admin->add_cap( 'delete_others_rw_dealers' );
		$admin->add_cap( 'delete_published_rw_dealers' );
		$admin->add_cap( 'delete_private_rw_dealers' );
		$admin->add_cap( 'edit_published_rw_dealers' );
		$admin->add_cap( 'edit_private_rw_dealers' );

		// Asset CPT caps
		$admin->add_cap( 'edit_rw_asset' );
		$admin->add_cap( 'read_rw_asset' );
		$admin->add_cap( 'delete_rw_asset' );
		$admin->add_cap( 'edit_rw_assets' );
		$admin->add_cap( 'edit_others_rw_assets' );
		$admin->add_cap( 'publish_rw_assets' );
		$admin->add_cap( 'read_private_rw_assets' );
		$admin->add_cap( 'delete_rw_assets' );
		$admin->add_cap( 'delete_others_rw_assets' );
		$admin->add_cap( 'delete_published_rw_assets' );
		$admin->add_cap( 'delete_private_rw_assets' );
		$admin->add_cap( 'edit_published_rw_assets' );
		$admin->add_cap( 'edit_private_rw_assets' );
	}
}

/**
 * Ensure the Portal Manager role always has the full set of CPT capabilities.
 * Runs on every page load so that changes to code are reflected without
 * deactivating/reactivating the plugin.
 */
function rwdp_sync_portal_manager_caps() {
	$role = get_role( 'rwdp_portal_manager' );
	if ( ! $role ) {
		return;
	}

	$caps = [
		// Dealer CPT
		'edit_rw_dealer',
		'read_rw_dealer',
		'delete_rw_dealer',
		'edit_rw_dealers',
		'edit_others_rw_dealers',
		'edit_published_rw_dealers',
		'edit_private_rw_dealers',
		'publish_rw_dealers',
		'read_private_rw_dealers',
		'delete_rw_dealers',
		'delete_others_rw_dealers',
		'delete_published_rw_dealers',
		'delete_private_rw_dealers',
		// Asset CPT
		'edit_rw_asset',
		'read_rw_asset',
		'delete_rw_asset',
		'edit_rw_assets',
		'edit_others_rw_assets',
		'edit_published_rw_assets',
		'edit_private_rw_assets',
		'publish_rw_assets',
		'read_private_rw_assets',
		'delete_rw_assets',
		'delete_others_rw_assets',
		'delete_published_rw_assets',
		'delete_private_rw_assets',
	];

	foreach ( $caps as $cap ) {
		if ( ! $role->has_cap( $cap ) ) {
			$role->add_cap( $cap );
		}
	}
}
add_action( 'plugins_loaded', 'rwdp_sync_portal_manager_caps' );

/**
 * Remove custom roles on plugin uninstall (called from uninstall.php).
 */
function rwdp_remove_roles() {
	remove_role( 'rwdp_dealer' );
	remove_role( 'rwdp_portal_manager' );

	$admin = get_role( 'administrator' );
	if ( $admin ) {
		$admin->remove_cap( 'view_portal' );
		$admin->remove_cap( 'manage_rwdp_portal' );
		$admin->remove_cap( 'view_rwdp_submissions' );
	}
}
