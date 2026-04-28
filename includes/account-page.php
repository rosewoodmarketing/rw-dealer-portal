<?php
if ( ! defined( 'ABSPATH' ) ) exit;

add_shortcode( 'rwdp_my_account', 'rwdp_my_account_shortcode' );

function rwdp_my_account_shortcode() {
	if ( ! rwdp_current_user_has_portal_access() ) {
		return rwdp_portal_login_prompt();
	}

	wp_enqueue_style( 'rwdp-portal', RWDP_PLUGIN_URL . 'assets/css/portal.css', [ 'dashicons' ], RWDP_VERSION );
	wp_enqueue_script( 'rwdp-portal', RWDP_PLUGIN_URL . 'assets/js/portal.js', [ 'jquery' ], RWDP_VERSION, true );
	wp_localize_script( 'rwdp-portal', 'rwdpPortal', [
		'ajaxUrl' => admin_url( 'admin-ajax.php' ),
		'nonce'   => wp_create_nonce( 'rwdp_account_update' ),
	] );

	$user       = wp_get_current_user();
	$dealer_ids = (array) get_user_meta( $user->ID, '_rwdp_dealer_ids', true );
	$dealer_ids = array_filter( array_map( 'absint', $dealer_ids ) );

	ob_start();
	?>
	<div class="rwdp-portal rwdp-account">
		<div class="rwdp-account-header">
			<h2><?php esc_html_e( 'My Account', 'rw-dealer-portal' ); ?></h2>
			<a href="<?php echo esc_url( wp_logout_url( home_url() ) ); ?>" class="rwdp-logout-btn">
				<span class="dashicons dashicons-exit"></span>
				<?php esc_html_e( 'Log Out', 'rw-dealer-portal' ); ?>
			</a>
		</div>

		<div id="rwdp-account-message" class="rwdp-notice" style="display:none;"></div>

		<form id="rwdp-account-form" class="rwdp-form" novalidate>
			<fieldset>
				<legend><?php esc_html_e( 'Profile Information', 'rw-dealer-portal' ); ?></legend>

				<div class="rwdp-form-row">
					<label for="rwdp_first_name"><?php esc_html_e( 'First Name', 'rw-dealer-portal' ); ?></label>
					<input type="text" id="rwdp_first_name" name="first_name" value="<?php echo esc_attr( $user->first_name ); ?>" autocomplete="given-name" />
				</div>
				<div class="rwdp-form-row">
					<label for="rwdp_last_name"><?php esc_html_e( 'Last Name', 'rw-dealer-portal' ); ?></label>
					<input type="text" id="rwdp_last_name" name="last_name" value="<?php echo esc_attr( $user->last_name ); ?>" autocomplete="family-name" />
				</div>
				<div class="rwdp-form-row">
					<label for="rwdp_email"><?php esc_html_e( 'Email Address', 'rw-dealer-portal' ); ?></label>
					<input type="email" id="rwdp_email" name="email" value="<?php echo esc_attr( $user->user_email ); ?>" autocomplete="email" />
				</div>
			</fieldset>

			<fieldset>
				<legend><?php esc_html_e( 'Change Password', 'rw-dealer-portal' ); ?></legend>
				<p class="description"><?php esc_html_e( 'Leave blank to keep your current password.', 'rw-dealer-portal' ); ?></p>
				<div class="rwdp-form-row">
					<label for="rwdp_current_password"><?php esc_html_e( 'Current Password', 'rw-dealer-portal' ); ?></label>
					<input type="password" id="rwdp_current_password" name="current_password" autocomplete="current-password" />
				</div>
				<div class="rwdp-form-row">
					<label for="rwdp_new_password"><?php esc_html_e( 'New Password', 'rw-dealer-portal' ); ?></label>
					<input type="password" id="rwdp_new_password" name="new_password" autocomplete="new-password" />
				</div>
				<div class="rwdp-form-row">
					<label for="rwdp_confirm_password"><?php esc_html_e( 'Confirm New Password', 'rw-dealer-portal' ); ?></label>
					<input type="password" id="rwdp_confirm_password" name="confirm_password" autocomplete="new-password" />
				</div>
			</fieldset>

			<?php if ( $dealer_ids ) : ?>
			<fieldset>
				<legend><?php esc_html_e( 'Linked Dealer(s)', 'rw-dealer-portal' ); ?></legend>
				<ul class="rwdp-dealer-list">
					<?php foreach ( $dealer_ids as $did ) :
						$dealer = get_post( $did );
						if ( $dealer ) :
					?>
						<li>
							<a href="<?php echo esc_url( get_permalink( $did ) ); ?>"><?php echo esc_html( $dealer->post_title ); ?></a>
						</li>
					<?php endif; endforeach; ?>
				</ul>
			</fieldset>
			<?php endif; ?>

			<button type="submit" class="rwdp-btn rwdp-btn--primary"><?php esc_html_e( 'Save Changes', 'rw-dealer-portal' ); ?></button>
		</form>
	</div>
	<?php
	return ob_get_clean();
}

/**
 * AJAX: Update account profile/password.
 */
add_action( 'wp_ajax_rwdp_update_account', 'rwdp_ajax_update_account' );

function rwdp_ajax_update_account() {
	check_ajax_referer( 'rwdp_account_update', 'nonce' );

	if ( ! is_user_logged_in() ) {
		wp_send_json_error( [ 'message' => __( 'Not logged in.', 'rw-dealer-portal' ) ] );
	}

	$user_id         = get_current_user_id();
	$first_name      = sanitize_text_field( wp_unslash( $_POST['first_name']       ?? '' ) );
	$last_name       = sanitize_text_field( wp_unslash( $_POST['last_name']        ?? '' ) );
	$email           = sanitize_email(       wp_unslash( $_POST['email']            ?? '' ) );
	$current_pass    = wp_unslash( $_POST['current_password'] ?? '' ); // not sanitized — checked raw
	$new_pass        = wp_unslash( $_POST['new_password']     ?? '' );
	$confirm_pass    = wp_unslash( $_POST['confirm_password'] ?? '' );

	if ( ! is_email( $email ) ) {
		wp_send_json_error( [ 'message' => __( 'Please enter a valid email address.', 'rw-dealer-portal' ) ] );
	}

	// Check email uniqueness (allow own email)
	$existing = get_user_by( 'email', $email );
	if ( $existing && $existing->ID !== $user_id ) {
		wp_send_json_error( [ 'message' => __( 'That email address is already in use.', 'rw-dealer-portal' ) ] );
	}

	$update_data = [
		'ID'           => $user_id,
		'first_name'   => $first_name,
		'last_name'    => $last_name,
		'display_name' => trim( $first_name . ' ' . $last_name ) ?: get_userdata( $user_id )->display_name,
		'user_email'   => $email,
	];

	// Password change
	if ( ! empty( $new_pass ) ) {
		if ( empty( $current_pass ) ) {
			wp_send_json_error( [ 'message' => __( 'Please enter your current password to set a new one.', 'rw-dealer-portal' ) ] );
		}
		$user = get_userdata( $user_id );
		if ( ! wp_check_password( $current_pass, $user->user_pass, $user_id ) ) {
			wp_send_json_error( [ 'message' => __( 'Your current password is incorrect.', 'rw-dealer-portal' ) ] );
		}
		if ( $new_pass !== $confirm_pass ) {
			wp_send_json_error( [ 'message' => __( 'New passwords do not match.', 'rw-dealer-portal' ) ] );
		}
		if ( strlen( $new_pass ) < 8 ) {
			wp_send_json_error( [ 'message' => __( 'New password must be at least 8 characters.', 'rw-dealer-portal' ) ] );
		}
		$update_data['user_pass'] = $new_pass;
	}

	$result = wp_update_user( $update_data );
	if ( is_wp_error( $result ) ) {
		wp_send_json_error( [ 'message' => $result->get_error_message() ] );
	}

	wp_send_json_success( [ 'message' => __( 'Your account has been updated.', 'rw-dealer-portal' ) ] );
}
