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
 * Ensure the administrator role always has the full set of CPT capabilities.
 * Mirrors rwdp_sync_portal_manager_caps() so that sites where the activation
 * hook never fired (cloned DBs, file-only deploys, etc.) self-heal on the
 * next page load without requiring deactivate/reactivate.
 */
function rwdp_sync_admin_caps() {
	$admin = get_role( 'administrator' );
	if ( ! $admin ) {
		return;
	}

	$caps = [
		'view_portal',
		'manage_rwdp_portal',
		'view_rwdp_submissions',

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
		if ( ! $admin->has_cap( $cap ) ) {
			$admin->add_cap( $cap );
		}
	}
}
add_action( 'plugins_loaded', 'rwdp_sync_admin_caps' );

/**
 * Restrict Portal Managers to only see Dealer and Portal Manager users
 * in the admin Users list.
 */
add_action( 'pre_get_users', 'rwdp_limit_user_list_for_portal_manager' );
function rwdp_limit_user_list_for_portal_manager( $query ) {
	if ( ! is_admin() ) {
		return;
	}
	// Only restrict the WP core users list screen — not the pending registrations
	// page or any other internal get_users() call, which would filter out
	// pending users who have no role yet.
	if ( ( $GLOBALS['pagenow'] ?? '' ) !== 'users.php' ) {
		return;
	}
	// Admins are unaffected.
	if ( current_user_can( 'manage_options' ) ) {
		return;
	}
	if ( ! current_user_can( 'manage_rwdp_portal' ) ) {
		return;
	}
	$query->set( 'role__in', [ 'rwdp_dealer', 'rwdp_portal_manager' ] );
}

/**
 * Prevent Portal Managers from editing, deleting, or promoting users
 * who do not have the Dealer or Portal Manager role.
 */
add_filter( 'user_has_cap', 'rwdp_restrict_portal_manager_user_caps', 10, 4 );
function rwdp_restrict_portal_manager_user_caps( $allcaps, $caps, $args, $user ) {
	// Only apply to portal managers. Admins (manage_options) are unaffected.
	if ( empty( $user->roles ) || ! in_array( 'rwdp_portal_manager', (array) $user->roles, true ) ) {
		return $allcaps;
	}
	if ( ! empty( $allcaps['manage_options'] ) ) {
		return $allcaps;
	}

	$restricted_meta_caps = [ 'edit_user', 'delete_user', 'promote_user', 'remove_user' ];
	if ( empty( $args[0] ) || ! in_array( $args[0], $restricted_meta_caps, true ) ) {
		return $allcaps;
	}

	$target_user_id = isset( $args[2] ) ? (int) $args[2] : 0;
	// Always allow editing yourself.
	if ( ! $target_user_id || $target_user_id === (int) $user->ID ) {
		return $allcaps;
	}

	$target        = get_userdata( $target_user_id );
	$allowed_roles = [ 'rwdp_dealer', 'rwdp_portal_manager' ];
	if ( $target && empty( array_intersect( (array) $target->roles, $allowed_roles ) ) ) {
		// Allow acting on users who are in the plugin's own pending registration
		// queue — they haven't been assigned the dealer role yet.
		$is_pending = get_user_meta( $target_user_id, '_rwdp_account_status', true ) === 'pending';
		if ( ! $is_pending ) {
			// Target user is neither a dealer/manager nor a pending applicant — deny.
			foreach ( $caps as $cap ) {
				$allcaps[ $cap ] = false;
			}
		}
	}

	return $allcaps;
}

/**
 * Limit which roles a Portal Manager can assign when creating or editing users.
 * Only Dealer and Portal Manager appear in the role dropdown for them.
 */
add_filter( 'editable_roles', 'rwdp_restrict_portal_manager_editable_roles' );
function rwdp_restrict_portal_manager_editable_roles( $roles ) {
	if ( current_user_can( 'manage_options' ) ) {
		return $roles;
	}
	if ( ! current_user_can( 'manage_rwdp_portal' ) ) {
		return $roles;
	}
	return array_intersect_key( $roles, array_flip( [ 'rwdp_dealer', 'rwdp_portal_manager' ] ) );
}

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
