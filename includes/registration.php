<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * AJAX handler: submit a new dealer portal registration request.
 * No authentication required (nopriv).
 */
add_action( 'wp_ajax_nopriv_rwdp_register_request', 'rwdp_handle_register_request' );
add_action( 'wp_ajax_rwdp_register_request', 'rwdp_handle_register_request' ); // logged-in edge case

function rwdp_handle_register_request() {
	check_ajax_referer( 'rwdp_registration', 'nonce' );

	$first_name = sanitize_text_field( wp_unslash( $_POST['first_name'] ?? '' ) );
	$last_name  = sanitize_text_field( wp_unslash( $_POST['last_name']  ?? '' ) );
	$email      = sanitize_email( wp_unslash( $_POST['email']      ?? '' ) );
	$company    = sanitize_text_field( wp_unslash( $_POST['company']    ?? '' ) );

	if ( empty( $first_name ) || empty( $last_name ) || empty( $email ) ) {
		wp_send_json_error( [ 'message' => __( 'Please fill in all required fields.', 'rw-dealer-portal' ) ] );
	}

	if ( ! is_email( $email ) ) {
		wp_send_json_error( [ 'message' => __( 'Please enter a valid email address.', 'rw-dealer-portal' ) ] );
	}

	if ( email_exists( $email ) ) {
		wp_send_json_error( [ 'message' => __( 'An account with this email address already exists. Please log in or contact us if you need help.', 'rw-dealer-portal' ) ] );
	}

	// Create the user with no role (pending)
	$username = rwdp_generate_username( $first_name, $last_name, $email );
	$password = wp_generate_password( 16, true );

	$user_id = wp_create_user( $username, $password, $email );

	if ( is_wp_error( $user_id ) ) {
		wp_send_json_error( [ 'message' => $user_id->get_error_message() ] );
	}

	// Store registration data
	wp_update_user( [
		'ID'         => $user_id,
		'first_name' => $first_name,
		'last_name'  => $last_name,
		'role'       => '', // no role until approved
	] );

	update_user_meta( $user_id, '_rwdp_account_status', 'pending' );
	update_user_meta( $user_id, '_rwdp_company', $company );
	update_user_meta( $user_id, '_rwdp_dealer_ids', [] );

	// Send notifications
	rwdp_send_pending_notification_to_admin( $user_id, $first_name, $last_name, $email, $company );

	wp_send_json_success( [ 'message' => __( 'Your request has been submitted. You will receive an email when your account is approved.', 'rw-dealer-portal' ) ] );
}

/**
 * Generate a unique username from first/last name or email.
 */
function rwdp_generate_username( $first_name, $last_name, $email ) {
	$base     = sanitize_user( strtolower( $first_name . '.' . $last_name ), true );
	$username = $base ?: sanitize_user( strstr( $email, '@', true ), true );
	$i        = 1;
	$try      = $username;
	while ( username_exists( $try ) ) {
		$try = $username . $i;
		$i++;
	}
	return $try;
}

/**
 * Email site admin and portal managers about a pending registration.
 */
function rwdp_send_pending_notification_to_admin( $user_id, $first_name, $last_name, $email, $company ) {
	$admin_email = get_option( 'admin_email' );
	$site_name   = get_bloginfo( 'name' );
	$review_url  = admin_url( 'admin.php?page=rwdp-pending-registrations' );

	$subject = sprintf(
		/* translators: %s: site name */
		__( '[%s] New Dealer Portal Registration Request', 'rw-dealer-portal' ),
		$site_name
	);

	$body = sprintf(
		/* translators: 1: first name, 2: last name, 3: email, 4: company, 5: review url */
		__(
			"A new dealer portal registration request has been submitted.\n\n" .
			"Name: %1\$s %2\$s\n" .
			"Email: %3\$s\n" .
			"Company: %4\$s\n\n" .
			"Review and approve or deny this request:\n%5\$s",
			'rw-dealer-portal'
		),
		$first_name,
		$last_name,
		$email,
		$company ?: __( 'Not provided', 'rw-dealer-portal' ),
		$review_url
	);

	wp_mail( $admin_email, $subject, $body );
}

/**
 * AJAX: Approve a pending registration.
 */
add_action( 'wp_ajax_rwdp_approve_registration', 'rwdp_approve_registration' );

function rwdp_approve_registration() {
	check_ajax_referer( 'rwdp_manage_registrations', 'nonce' );

	if ( ! current_user_can( 'manage_rwdp_portal' ) ) {
		wp_send_json_error( [ 'message' => __( 'Permission denied.', 'rw-dealer-portal' ) ] );
	}

	$user_id   = absint( $_POST['user_id'] ?? 0 );
	$dealer_id = absint( $_POST['dealer_id'] ?? 0 );

	if ( ! $user_id ) {
		wp_send_json_error( [ 'message' => __( 'Invalid user.', 'rw-dealer-portal' ) ] );
	}

	$user = get_userdata( $user_id );
	if ( ! $user ) {
		wp_send_json_error( [ 'message' => __( 'User not found.', 'rw-dealer-portal' ) ] );
	}

	// Assign dealer role
	$user->set_role( 'rwdp_dealer' );
	update_user_meta( $user_id, '_rwdp_account_status', 'approved' );

	// Link to a dealer post if specified
	if ( $dealer_id && get_post_type( $dealer_id ) === 'rw_dealer' ) {
		$current_ids   = (array) get_user_meta( $user_id, '_rwdp_dealer_ids', true );
		$current_ids[] = $dealer_id;
		update_user_meta( $user_id, '_rwdp_dealer_ids', array_unique( array_map( 'absint', $current_ids ) ) );
	}

	// Generate a password reset key and send approval email
	$key       = get_password_reset_key( $user );
	$login_url = rwdp_get_page_url( 'login' );

	if ( ! is_wp_error( $key ) ) {
		// Point the reset link to the portal login page, not wp-login.php.
		$reset_url = add_query_arg( [
			'action' => 'rp',
			'key'    => $key,
			'login'  => $user->user_login,
		], $login_url );
		rwdp_send_approval_email( $user, $reset_url, $login_url );
	}

	wp_send_json_success( [ 'message' => __( 'User approved.', 'rw-dealer-portal' ) ] );
}

/**
 * AJAX: Deny a pending registration (deletes the user).
 */
add_action( 'wp_ajax_rwdp_deny_registration', 'rwdp_deny_registration' );

function rwdp_deny_registration() {
	check_ajax_referer( 'rwdp_manage_registrations', 'nonce' );

	if ( ! current_user_can( 'manage_rwdp_portal' ) ) {
		wp_send_json_error( [ 'message' => __( 'Permission denied.', 'rw-dealer-portal' ) ] );
	}

	$user_id = absint( $_POST['user_id'] ?? 0 );

	if ( ! $user_id ) {
		wp_send_json_error( [ 'message' => __( 'Invalid user.', 'rw-dealer-portal' ) ] );
	}

	$user = get_userdata( $user_id );
	if ( ! $user ) {
		wp_send_json_error( [ 'message' => __( 'User not found.', 'rw-dealer-portal' ) ] );
	}

	// Optionally notify the user
	rwdp_send_denial_email( $user );

	require_once ABSPATH . 'wp-admin/includes/user.php';
	wp_delete_user( $user_id );

	wp_send_json_success( [ 'message' => __( 'User denied and removed.', 'rw-dealer-portal' ) ] );
}

/**
 * Send approval email with a password setup link.
 */
function rwdp_send_approval_email( $user, $reset_url, $login_url ) {
	$site_name = get_bloginfo( 'name' );

	$subject = sprintf(
		/* translators: %s: site name */
		__( '[%s] Your dealer portal access has been approved', 'rw-dealer-portal' ),
		$site_name
	);

	$body = sprintf(
		/* translators: 1: first name, 2: site name, 3: reset url, 4: login url */
		__(
			"Hi %1\$s,\n\n" .
			"Your request for access to the %2\$s dealer portal has been approved!\n\n" .
			"Please set your password using the link below:\n%3\$s\n\n" .
			"Once your password is set, you can log in here:\n%4\$s\n\n" .
			"If you have any questions, please reply to this email.\n\n" .
			"Thank you,\nThe %2\$s Team",
			'rw-dealer-portal'
		),
		$user->first_name ?: $user->display_name,
		$site_name,
		$reset_url,
		$login_url
	);

	wp_mail( $user->user_email, $subject, $body );
}

/**
 * Send denial email.
 */
function rwdp_send_denial_email( $user ) {
	$site_name = get_bloginfo( 'name' );

	$subject = sprintf(
		/* translators: %s: site name */
		__( '[%s] Dealer portal access request', 'rw-dealer-portal' ),
		$site_name
	);

	$body = sprintf(
		/* translators: 1: first name, 2: site name */
		__(
			"Hi %1\$s,\n\n" .
			"Thank you for your interest in the %2\$s dealer portal.\n\n" .
			"Unfortunately, we were unable to approve your request at this time. " .
			"Please contact us if you believe this is in error.\n\n" .
			"Thank you,\nThe %2\$s Team",
			'rw-dealer-portal'
		),
		$user->first_name ?: $user->display_name,
		$site_name
	);

	wp_mail( $user->user_email, $subject, $body );
}
