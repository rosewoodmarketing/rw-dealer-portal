<?php
if ( ! defined( 'ABSPATH' ) ) exit;

add_shortcode( 'rwdp_edit_dealer', 'rwdp_edit_dealer_shortcode' );

function rwdp_edit_dealer_shortcode() {
	if ( ! rwdp_current_user_has_portal_access() ) {
		return rwdp_portal_login_prompt();
	}

	wp_enqueue_style( 'rwdp-portal', RWDP_PLUGIN_URL . 'assets/css/portal.css', [ 'dashicons' ], RWDP_VERSION );
	wp_enqueue_script( 'rwdp-portal', RWDP_PLUGIN_URL . 'assets/js/portal.js', [ 'jquery' ], RWDP_VERSION, true );
	wp_localize_script( 'rwdp-portal', 'rwdpPortal', [
		'ajaxUrl' => admin_url( 'admin-ajax.php' ),
		'nonce'   => wp_create_nonce( 'rwdp_edit_dealer' ),
	] );

	$user       = wp_get_current_user();
	$dealer_ids = (array) get_user_meta( $user->ID, '_rwdp_dealer_ids', true );
	$dealer_ids = array_filter( array_map( 'absint', $dealer_ids ) );

	if ( empty( $dealer_ids ) ) {
		return '<div class="rwdp-portal">' .
		       '<p>' . esc_html__( 'You are not currently linked to any dealer. Please contact your portal administrator.', 'rw-dealer-portal' ) . '</p></div>';
	}

	ob_start();
	?>
	<div class="rwdp-portal rwdp-edit-dealer">
		<h2><?php esc_html_e( 'Edit Dealer Profile', 'rw-dealer-portal' ); ?></h2>

		<?php foreach ( $dealer_ids as $dealer_id ) :
			$dealer = get_post( $dealer_id );
			if ( ! $dealer || $dealer->post_type !== 'rw_dealer' ) continue;

			$phone  = get_post_meta( $dealer_id, '_rwdp_phone', true );
			$email  = get_post_meta( $dealer_id, '_rwdp_public_email', true );
			$hours  = get_post_meta( $dealer_id, '_rwdp_hours', true );
		?>
		<div class="rwdp-dealer-edit-panel" data-dealer-id="<?php echo absint( $dealer_id ); ?>">
			<h3><?php echo esc_html( $dealer->post_title ); ?></h3>

			<div id="rwdp-edit-dealer-msg-<?php echo absint( $dealer_id ); ?>" class="rwdp-notice" style="display:none;"></div>

			<form class="rwdp-form rwdp-dealer-edit-form" data-dealer-id="<?php echo absint( $dealer_id ); ?>" novalidate>
				<div class="rwdp-form-row">
					<label for="rwdp_phone_<?php echo absint( $dealer_id ); ?>"><?php esc_html_e( 'Phone Number', 'rw-dealer-portal' ); ?></label>
					<input type="text" id="rwdp_phone_<?php echo absint( $dealer_id ); ?>" name="phone"
						value="<?php echo esc_attr( $phone ); ?>" autocomplete="tel" />
				</div>
				<div class="rwdp-form-row">
					<label for="rwdp_email_<?php echo absint( $dealer_id ); ?>"><?php esc_html_e( 'Public Email', 'rw-dealer-portal' ); ?></label>
					<input type="email" id="rwdp_email_<?php echo absint( $dealer_id ); ?>" name="public_email"
						value="<?php echo esc_attr( $email ); ?>" autocomplete="email" />
				</div>
				<div class="rwdp-form-row">
					<label for="rwdp_hours_<?php echo absint( $dealer_id ); ?>"><?php esc_html_e( 'Business Hours', 'rw-dealer-portal' ); ?></label>
					<textarea id="rwdp_hours_<?php echo absint( $dealer_id ); ?>" name="hours" rows="4"><?php echo esc_textarea( $hours ); ?></textarea>
				</div>
				<button type="submit" class="rwdp-btn rwdp-btn--primary"><?php esc_html_e( 'Save Changes', 'rw-dealer-portal' ); ?></button>
			</form>
		</div>
		<?php endforeach; ?>
	</div>
	<?php
	return ob_get_clean();
}

/**
 * AJAX: Save editable dealer fields (frontend, ownership-checked).
 */
add_action( 'wp_ajax_rwdp_save_dealer_profile', 'rwdp_ajax_save_dealer_profile' );

function rwdp_ajax_save_dealer_profile() {
	check_ajax_referer( 'rwdp_edit_dealer', 'nonce' );

	if ( ! is_user_logged_in() ) {
		wp_send_json_error( [ 'message' => __( 'Not logged in.', 'rw-dealer-portal' ) ] );
	}

	$user_id   = get_current_user_id();
	$dealer_id = absint( $_POST['dealer_id'] ?? 0 );

	// Ownership check — user must be linked to this dealer
	$linked_ids = (array) get_user_meta( $user_id, '_rwdp_dealer_ids', true );
	$linked_ids = array_map( 'absint', $linked_ids );

	if ( ! in_array( $dealer_id, $linked_ids, true ) ) {
		wp_send_json_error( [ 'message' => __( 'You do not have permission to edit this dealer.', 'rw-dealer-portal' ) ] );
	}

	if ( get_post_type( $dealer_id ) !== 'rw_dealer' ) {
		wp_send_json_error( [ 'message' => __( 'Invalid dealer.', 'rw-dealer-portal' ) ] );
	}

	$phone  = sanitize_text_field( wp_unslash( $_POST['phone']        ?? '' ) );
	$email  = sanitize_email(       wp_unslash( $_POST['public_email'] ?? '' ) );
	$hours  = sanitize_textarea_field( wp_unslash( $_POST['hours']    ?? '' ) );

	if ( $email && ! is_email( $email ) ) {
		wp_send_json_error( [ 'message' => __( 'Please enter a valid email address.', 'rw-dealer-portal' ) ] );
	}

	update_post_meta( $dealer_id, '_rwdp_phone', $phone );
	update_post_meta( $dealer_id, '_rwdp_public_email', $email );
	update_post_meta( $dealer_id, '_rwdp_hours', $hours );

	wp_send_json_success( [ 'message' => __( 'Dealer profile updated.', 'rw-dealer-portal' ) ] );
}
