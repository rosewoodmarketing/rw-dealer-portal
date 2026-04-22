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
	$dealer_count     = wp_count_posts( 'rw_dealer' )->publish ?? 0;
	$asset_count      = wp_count_posts( 'rw_asset' )->publish ?? 0;
	$submission_count = wp_count_posts( 'rw_submission' )->publish ?? 0;
	$pending_count    = rwdp_get_pending_user_count();

	$cards = [
		[
			'icon'        => 'dashicons-store',
			'title'       => __( 'Dealers', 'rw-dealer-portal' ),
			'count'       => absint( $dealer_count ),
			'count_label' => __( 'Total', 'rw-dealer-portal' ),
			'description' => __( 'Manage your network of authorized dealers.', 'rw-dealer-portal' ),
			'primary_url' => admin_url( 'edit.php?post_type=rw_dealer' ),
			'primary_txt' => __( 'Manage Dealers', 'rw-dealer-portal' ),
			'secondary_url' => admin_url( 'post-new.php?post_type=rw_dealer' ),
			'secondary_txt' => __( 'Add New Dealer', 'rw-dealer-portal' ),
			'alert'       => false,
		],
		[
			'icon'        => 'dashicons-media-document',
			'title'       => __( 'Assets', 'rw-dealer-portal' ),
			'count'       => absint( $asset_count ),
			'count_label' => __( 'Items', 'rw-dealer-portal' ),
			'description' => __( 'Control your digital brand asset library. Upload photos, brochures, videos, and more', 'rw-dealer-portal' ),
			'primary_url' => admin_url( 'edit.php?post_type=rw_asset' ),
			'primary_txt' => __( 'Manage Assets', 'rw-dealer-portal' ),
			'secondary_url' => admin_url( 'post-new.php?post_type=rw_asset' ),
			'secondary_txt' => __( 'Add New Asset', 'rw-dealer-portal' ),
			'alert'       => false,
		],
		[
			'icon'        => 'dashicons-feedback',
			'title'       => __( 'Form Submissions', 'rw-dealer-portal' ),
			'count'       => absint( $submission_count ),
			'count_label' => __( 'Total', 'rw-dealer-portal' ),
			'description' => __( 'Review customer inquiries and dealer contact requests submitted through the Dealer Finder.', 'rw-dealer-portal' ),
			'primary_url' => admin_url( 'admin.php?page=rwdp-submissions' ),
			'primary_txt' => __( 'View Submissions', 'rw-dealer-portal' ),
			'secondary_url' => admin_url( 'admin.php?page=rwdp-settings&tab=contact' ),
			'secondary_txt' => __( 'Contact Settings', 'rw-dealer-portal' ),
			'alert'       => false,
		],
		[
			'icon'        => 'dashicons-groups',
			'title'       => __( 'Pending Registrations', 'rw-dealer-portal' ),
			'count'       => absint( $pending_count ),
			'count_label' => __( 'Pending', 'rw-dealer-portal' ),
			'description' => __( 'Review and approve dealer account applications. Assign applicants to an existing dealer profile or create a new one.', 'rw-dealer-portal' ),
			'primary_url' => admin_url( 'admin.php?page=rwdp-pending-registrations' ),
			'primary_txt' => __( 'Review Applications', 'rw-dealer-portal' ),
			'secondary_url' => admin_url( 'user-new.php' ),
			'secondary_txt' => __( 'Add New User', 'rw-dealer-portal' ),
			'alert'       => $pending_count > 0,
		],
	];
	?>
	<div class="wrap rwdp-overview-wrap">
		<h1 class="rwdp-overview-heading">
			<span class="dashicons dashicons-store" style="font-size:28px;width:28px;height:28px;margin-right:8px;vertical-align:middle;"></span>
			<?php esc_html_e( 'Dealer Portal', 'rw-dealer-portal' ); ?>
		</h1>

		<div class="rwdp-overview-grid">
			<?php foreach ( $cards as $card ) : ?>
			<div class="rwdp-overview-card<?php echo $card['alert'] ? ' rwdp-overview-card--alert' : ''; ?>">

				<div class="rwdp-overview-card__header">
					<div class="rwdp-overview-card__icon-title">
						<span class="dashicons <?php echo esc_attr( $card['icon'] ); ?> rwdp-overview-card__icon"></span>
						<span class="rwdp-overview-card__title"><?php echo esc_html( $card['title'] ); ?></span>
					</div>
					<span class="rwdp-overview-card__badge<?php echo $card['alert'] ? ' rwdp-overview-card__badge--alert' : ''; ?>">
						<?php echo absint( $card['count'] ); ?> <?php echo esc_html( strtoupper( $card['count_label'] ) ); ?>
					</span>
				</div>

				<div class="rwdp-overview-card__divider"></div>

				<p class="rwdp-overview-card__desc"><?php echo esc_html( $card['description'] ); ?></p>

				<div class="rwdp-overview-card__actions">
					<a href="<?php echo esc_url( $card['primary_url'] ); ?>" class="rwdp-overview-btn rwdp-overview-btn--primary">
						<?php echo esc_html( $card['primary_txt'] ); ?>
					</a>
					<a href="<?php echo esc_url( $card['secondary_url'] ); ?>" class="rwdp-overview-btn rwdp-overview-btn--secondary">
						<?php echo esc_html( $card['secondary_txt'] ); ?>
					</a>
				</div>

			</div>
			<?php endforeach; ?>
		</div>

		<div class="rwdp-overview-footer">
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=rwdp-settings' ) ); ?>" class="rwdp-overview-settings-link">
				<span class="dashicons dashicons-admin-settings"></span>
				<?php esc_html_e( 'Portal Settings', 'rw-dealer-portal' ); ?>
			</a>
			<a href="<?php echo esc_url( admin_url( 'edit-tags.php?taxonomy=rw_dealer_type&post_type=rw_dealer' ) ); ?>" class="rwdp-overview-settings-link">
				<span class="dashicons dashicons-tag"></span>
				<?php esc_html_e( 'Dealer Types', 'rw-dealer-portal' ); ?>
			</a>
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
