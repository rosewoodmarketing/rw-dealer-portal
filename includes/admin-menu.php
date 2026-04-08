<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Register the top-level admin menu and all submenus for RW Dealer Portal.
 * The menu is visible to admins and portal managers.
 */
add_action( 'admin_menu', 'rwdp_register_admin_menu' );

function rwdp_register_admin_menu() {
	$pending_count = rwdp_get_pending_user_count();
	$pending_badge = $pending_count ? ' <span class="awaiting-mod">' . absint( $pending_count ) . '</span>' : '';

	// Top-level menu
	add_menu_page(
		__( 'RW Dealer Portal', 'rw-dealer-portal' ),
		__( 'Dealer Portal', 'rw-dealer-portal' ),
		'manage_rwdp_portal',
		'rw-dealer-portal',
		'rwdp_admin_dashboard_page',
		'dashicons-store',
		25
	);

	// Dashboard (same as top-level)
	add_submenu_page(
		'rw-dealer-portal',
		__( 'Portal Overview', 'rw-dealer-portal' ),
		__( 'Overview', 'rw-dealer-portal' ),
		'manage_rwdp_portal',
		'rw-dealer-portal',
		'rwdp_admin_dashboard_page'
	);

	// Dealers CPT
	add_submenu_page(
		'rw-dealer-portal',
		__( 'Dealers', 'rw-dealer-portal' ),
		__( 'Dealers', 'rw-dealer-portal' ),
		'edit_rw_dealers',
		'edit.php?post_type=rw_dealer'
	);

	// Assets CPT
	add_submenu_page(
		'rw-dealer-portal',
		__( 'Assets', 'rw-dealer-portal' ),
		__( 'Assets', 'rw-dealer-portal' ),
		'edit_rw_assets',
		'edit.php?post_type=rw_asset'
	);

	// Contact Submissions
	add_submenu_page(
		'rw-dealer-portal',
		__( 'Contact Submissions', 'rw-dealer-portal' ),
		__( 'Contact Submissions', 'rw-dealer-portal' ),
		'view_rwdp_submissions',
		'rwdp-submissions',
		'rwdp_admin_submissions_page'
	);

	// Pending Registrations
	add_submenu_page(
		'rw-dealer-portal',
		__( 'Pending Registrations', 'rw-dealer-portal' ),
		__( 'Pending Registrations', 'rw-dealer-portal' ) . $pending_badge,
		'manage_rwdp_portal',
		'rwdp-pending-registrations',
		'rwdp_admin_pending_registrations_page'
	);

	// Settings
	add_submenu_page(
		'rw-dealer-portal',
		__( 'Portal Settings', 'rw-dealer-portal' ),
		__( 'Settings', 'rw-dealer-portal' ),
		'manage_options',
		'rwdp-settings',
		'rwdp_admin_settings_page'
	);

	// Dealer Types taxonomy
	add_submenu_page(
		'rw-dealer-portal',
		__( 'Dealer Types', 'rw-dealer-portal' ),
		__( 'Dealer Types', 'rw-dealer-portal' ),
		'manage_rwdp_portal',
		'edit-tags.php?taxonomy=rw_dealer_type&post_type=rw_dealer'
	);
}

/**
 * Keep "Dealer Portal" highlighted in the admin sidebar when managing Dealer Types.
 */
add_filter( 'parent_file', 'rwdp_dealer_types_parent_file' );
function rwdp_dealer_types_parent_file( $parent_file ) {
	$screen = get_current_screen();
	if ( $screen && 'rw_dealer_type' === $screen->taxonomy ) {
		$parent_file = 'rw-dealer-portal';
	}
	return $parent_file;
}

add_filter( 'submenu_file', 'rwdp_dealer_types_submenu_file' );
function rwdp_dealer_types_submenu_file( $submenu_file ) {
	$screen = get_current_screen();
	if ( $screen && 'rw_dealer_type' === $screen->taxonomy ) {
		$submenu_file = 'edit-tags.php?taxonomy=rw_dealer_type&post_type=rw_dealer';
	}
	return $submenu_file;
}

/**
 * Portal overview dashboard page.
 */
function rwdp_admin_dashboard_page() {
	$dealer_count   = wp_count_posts( 'rw_dealer' )->publish ?? 0;
	$asset_count    = wp_count_posts( 'rw_asset' )->publish ?? 0;
	$submission_count = wp_count_posts( 'rw_submission' )->publish ?? 0;
	$pending_count  = rwdp_get_pending_user_count();

	?>
	<div class="wrap rwdp-admin-wrap">
		<h1><?php esc_html_e( 'RW Dealer Portal', 'rw-dealer-portal' ); ?></h1>

		<div class="rwdp-stats-grid">
			<div class="rwdp-stat-card">
				<span class="rwdp-stat-number"><?php echo absint( $dealer_count ); ?></span>
				<span class="rwdp-stat-label"><?php esc_html_e( 'Dealers', 'rw-dealer-portal' ); ?></span>
				<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=rw_dealer' ) ); ?>" class="rwdp-stat-link"><?php esc_html_e( 'Manage Dealers', 'rw-dealer-portal' ); ?></a>
			</div>
			<div class="rwdp-stat-card">
				<span class="rwdp-stat-number"><?php echo absint( $asset_count ); ?></span>
				<span class="rwdp-stat-label"><?php esc_html_e( 'Assets', 'rw-dealer-portal' ); ?></span>
				<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=rw_asset' ) ); ?>" class="rwdp-stat-link"><?php esc_html_e( 'Manage Assets', 'rw-dealer-portal' ); ?></a>
			</div>
			<div class="rwdp-stat-card">
				<span class="rwdp-stat-number"><?php echo absint( $submission_count ); ?></span>
				<span class="rwdp-stat-label"><?php esc_html_e( 'Submissions', 'rw-dealer-portal' ); ?></span>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=rwdp-submissions' ) ); ?>" class="rwdp-stat-link"><?php esc_html_e( 'View Submissions', 'rw-dealer-portal' ); ?></a>
			</div>
			<div class="rwdp-stat-card<?php echo $pending_count ? ' rwdp-stat-card--alert' : ''; ?>">
				<span class="rwdp-stat-number"><?php echo absint( $pending_count ); ?></span>
				<span class="rwdp-stat-label"><?php esc_html_e( 'Pending Registrations', 'rw-dealer-portal' ); ?></span>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=rwdp-pending-registrations' ) ); ?>" class="rwdp-stat-link"><?php esc_html_e( 'Review', 'rw-dealer-portal' ); ?></a>
			</div>
		</div>

		<div class="rwdp-quick-actions">
			<h2><?php esc_html_e( 'Quick Actions', 'rw-dealer-portal' ); ?></h2>
			<a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=rw_dealer' ) ); ?>" class="button button-primary"><?php esc_html_e( 'Add New Dealer', 'rw-dealer-portal' ); ?></a>
			<a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=rw_asset' ) ); ?>" class="button button-primary"><?php esc_html_e( 'Add New Asset', 'rw-dealer-portal' ); ?></a>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=rwdp-settings' ) ); ?>" class="button"><?php esc_html_e( 'Settings', 'rw-dealer-portal' ); ?></a>
		</div>
	</div>
	<?php
}

/**
 * Helper: count users with pending status.
 */
function rwdp_get_pending_user_count() {
	$users = get_users( [
		'meta_key'   => '_rwdp_account_status',
		'meta_value' => 'pending',
		'count_total'=> true,
		'fields'     => 'ID',
		'number'     => -1,
	] );
	return count( $users );
}
