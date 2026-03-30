<?php
if ( ! defined( 'ABSPATH' ) ) exit;

add_shortcode( 'rwdp_dashboard', 'rwdp_dashboard_shortcode' );

function rwdp_dashboard_shortcode() {
	if ( ! rwdp_current_user_has_portal_access() ) {
		return rwdp_portal_login_prompt();
	}

	wp_enqueue_style( 'rwdp-portal', RWDP_PLUGIN_URL . 'assets/css/portal.css', [ 'dashicons' ], RWDP_VERSION );

	$user       = wp_get_current_user();
	$dealer_ids = (array) get_user_meta( $user->ID, '_rwdp_dealer_ids', true );
	$dealer_ids = array_filter( array_map( 'absint', $dealer_ids ) );

	// Count pending submissions for this user's dealers
	$pending_count = 0;
	if ( $dealer_ids ) {
		$pending_count = (int) ( new WP_Query( [
			'post_type'      => 'rw_submission',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'meta_query'     => [ [
				'key'     => '_rwdp_dealer_id',
				'value'   => $dealer_ids,
				'compare' => 'IN',
				'type'    => 'NUMERIC',
			] ],
		] ) )->found_posts;
	}

	ob_start();
	?>
	<div class="rwdp-portal rwdp-dashboard">
		<div class="rwdp-dashboard-header">
			<h2><?php printf( esc_html__( 'Welcome back, %s', 'rw-dealer-portal' ), esc_html( $user->first_name ?: $user->display_name ) ); ?></h2>
		</div>

		<div class="rwdp-dashboard-nav">
			<a href="<?php echo esc_url( rwdp_get_page_url( 'assets' ) ); ?>" class="rwdp-nav-card">
				<span class="dashicons dashicons-admin-media"></span>
				<span class="rwdp-nav-card__title"><?php esc_html_e( 'Assets', 'rw-dealer-portal' ); ?></span>
				<span class="rwdp-nav-card__desc"><?php esc_html_e( 'Photos, PDFs, logos, videos', 'rw-dealer-portal' ); ?></span>
			</a>

			<a href="<?php echo esc_url( rwdp_get_page_url( 'requests' ) ); ?>" class="rwdp-nav-card<?php echo $pending_count ? ' rwdp-nav-card--alert' : ''; ?>">
				<span class="dashicons dashicons-email-alt"></span>
				<span class="rwdp-nav-card__title"><?php esc_html_e( 'Contact Requests', 'rw-dealer-portal' ); ?></span>
				<span class="rwdp-nav-card__desc">
					<?php if ( $pending_count ) {
						printf( esc_html__( '%d request(s) received', 'rw-dealer-portal' ), absint( $pending_count ) );
					} else {
						esc_html_e( 'View contact form submissions', 'rw-dealer-portal' );
					} ?>
				</span>
			</a>

			<?php if ( $dealer_ids ) : ?>
			<a href="<?php echo esc_url( rwdp_get_page_url( 'edit_dealer' ) ); ?>" class="rwdp-nav-card">
				<span class="dashicons dashicons-store"></span>
				<span class="rwdp-nav-card__title"><?php esc_html_e( 'Edit Dealer Profile', 'rw-dealer-portal' ); ?></span>
				<span class="rwdp-nav-card__desc"><?php esc_html_e( 'Update phone, email, and hours', 'rw-dealer-portal' ); ?></span>
			</a>
			<?php endif; ?>

			<a href="<?php echo esc_url( rwdp_get_page_url( 'account' ) ); ?>" class="rwdp-nav-card">
				<span class="dashicons dashicons-admin-users"></span>
				<span class="rwdp-nav-card__title"><?php esc_html_e( 'My Account', 'rw-dealer-portal' ); ?></span>
				<span class="rwdp-nav-card__desc"><?php esc_html_e( 'Update your name, email, and password', 'rw-dealer-portal' ); ?></span>
			</a>
		</div>
	</div>
	<?php
	return ob_get_clean();
}
