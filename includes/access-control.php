<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// ── Frontend page restriction ────────────────────────────────────────────────
add_action( 'template_redirect', 'rwdp_restrict_portal_pages' );

function rwdp_restrict_portal_pages() {
	// Gate portal pages (by ID/meta) and all single rw_asset posts.
	$is_asset_single = is_singular( 'rw_asset' );

	if ( ! $is_asset_single ) {
		if ( ! is_singular() && ! is_page() ) return;

		$post_id  = get_queried_object_id();
		$settings = get_option( 'rwdp_settings', [] );

		$restricted_ids = $settings['restricted_page_ids'] ?? [];
		$has_meta       = get_post_meta( $post_id, '_rwdp_restrict_access', true ) === '1';
		$in_list        = in_array( $post_id, array_map( 'absint', $restricted_ids ), true );

		if ( ! $has_meta && ! $in_list ) return;
	}

	if ( rwdp_current_user_has_portal_access() ) return;

	// Not logged in or no portal access — redirect to login.
	$settings  = $settings ?? get_option( 'rwdp_settings', [] );
	$login_id  = $settings['login_page_id'] ?? 0;
	$current   = is_singular( 'rw_asset' ) ? get_permalink( get_queried_object_id() ) : get_permalink( get_queried_object_id() );
	$login_url = $login_id ? get_permalink( $login_id ) : wp_login_url( $current );

	wp_safe_redirect( add_query_arg( 'redirect_to', rawurlencode( $current ), $login_url ) );
	exit;
}

/**
 * Check if the current user has portal access.
 * Admins, editors, shop managers, portal managers, and approved dealers all pass.
 *
 * @return bool
 */
function rwdp_current_user_has_portal_access() {
	if ( ! is_user_logged_in() ) return false;

	$user = wp_get_current_user();

	// Admins and editors always have access
	if ( current_user_can( 'edit_pages' ) || current_user_can( 'manage_options' ) ) return true;

	// Check for view_portal capability (dealers and portal managers have this)
	if ( current_user_can( 'view_portal' ) ) {

		// Extra check for dealers: must be approved (account_status = approved or not pending)
		if ( in_array( 'rwdp_dealer', (array) $user->roles, true ) ) {
			$status = get_user_meta( $user->ID, '_rwdp_account_status', true );
			return $status === 'approved';
		}

		return true;
	}

	// Shop Manager WooCommerce role also gets access
	if ( in_array( 'shop_manager', (array) $user->roles, true ) ) return true;

	return false;
}

// ── Block dealer + portal_manager roles from WP admin ───────────────────────
add_action( 'admin_init', 'rwdp_block_portal_roles_from_admin' );

function rwdp_block_portal_roles_from_admin() {
	if ( ! is_user_logged_in() ) return;
	if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) return;

	$user = wp_get_current_user();

	if ( in_array( 'rwdp_dealer', (array) $user->roles, true ) ) {
		wp_safe_redirect( rwdp_get_page_url( 'dashboard' ) );
		exit;
	}
}

// ── Hide admin bar for dealers ───────────────────────────────────────────────
add_action( 'after_setup_theme', 'rwdp_maybe_hide_admin_bar' );

function rwdp_maybe_hide_admin_bar() {
	if ( ! is_user_logged_in() ) return;
	$user = wp_get_current_user();
	if ( in_array( 'rwdp_dealer', (array) $user->roles, true ) ) {
		show_admin_bar( false );
	}
}

// ── Portal Manager: restrict admin capabilities ──────────────────────────────
add_filter( 'user_has_cap', 'rwdp_restrict_portal_manager_caps', 10, 4 );

function rwdp_restrict_portal_manager_caps( $allcaps, $caps, $args, $user ) {
	if ( ! in_array( 'rwdp_portal_manager', (array) $user->roles, true ) ) {
		return $allcaps;
	}

	// Block editing of core WordPress post types
	$blocked = [
		'edit_posts', 'edit_others_posts', 'publish_posts', 'delete_posts', 'delete_others_posts',
		'edit_pages', 'edit_others_pages', 'publish_pages', 'delete_pages',
		'activate_plugins', 'install_plugins', 'update_plugins',
		'manage_options',
		'edit_theme_options',
	];

	foreach ( $blocked as $cap ) {
		$allcaps[ $cap ] = false;
	}

	return $allcaps;
}

// ── Portal Manager: strip irrelevant admin menus ─────────────────────────────
add_action( 'admin_menu', 'rwdp_restrict_portal_manager_menus', 999 );

function rwdp_restrict_portal_manager_menus() {
	$user = wp_get_current_user();
	if ( ! in_array( 'rwdp_portal_manager', (array) $user->roles, true ) ) {
		return;
	}

	$remove_menus = [
		'index.php',           // Dashboard
		'edit.php',            // Posts
		'edit.php?post_type=page', // Pages
		'edit-comments.php',   // Comments
		'themes.php',          // Appearance
		'plugins.php',         // Plugins
		'tools.php',           // Tools
		'options-general.php', // Settings
	];

	foreach ( $remove_menus as $slug ) {
		remove_menu_page( $slug );
	}

	// Hide "New" items in admin bar
	add_action( 'admin_bar_menu', 'rwdp_clean_portal_manager_admin_bar', 999 );
}

function rwdp_clean_portal_manager_admin_bar( $wp_admin_bar ) {
	$wp_admin_bar->remove_node( 'new-content' );
	$wp_admin_bar->remove_node( 'comments' );
	$wp_admin_bar->remove_node( 'appearance' );
}

// ── Portal Manager: limit user editing to dealer/portal_manager roles only ───
add_filter( 'editable_roles', 'rwdp_limit_portal_manager_editable_roles' );

function rwdp_limit_portal_manager_editable_roles( $roles ) {
	$user = wp_get_current_user();
	if ( ! in_array( 'rwdp_portal_manager', (array) $user->roles, true ) ) {
		return $roles;
	}

	$allowed = [ 'rwdp_dealer', 'rwdp_portal_manager' ];
	foreach ( $roles as $role_slug => $role_data ) {
		if ( ! in_array( $role_slug, $allowed, true ) ) {
			unset( $roles[ $role_slug ] );
		}
	}
	return $roles;
}

// ── Redirect pending dealers who try to access the portal ────────────────────
add_action( 'template_redirect', 'rwdp_redirect_pending_dealers', 5 );

function rwdp_redirect_pending_dealers() {
	if ( ! is_user_logged_in() ) return;

	$user   = wp_get_current_user();
	$status = get_user_meta( $user->ID, '_rwdp_account_status', true );

	if ( in_array( 'rwdp_dealer', (array) $user->roles, true ) && $status === 'pending' ) {
		// Log out and show message — pending users should not retain a session
		wp_logout();
		wp_safe_redirect( add_query_arg( 'rwdp_pending', '1', rwdp_get_page_url( 'login' ) ) );
		exit;
	}
}
