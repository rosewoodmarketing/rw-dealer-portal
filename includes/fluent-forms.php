<?php
if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'fluentform_submission_inserted', 'rwdp_handle_fluent_form_submission', 10, 3 );

/**
 * When a Fluent Forms submission is inserted, check if it is the configured contact
 * form and if so, tag the FF entry with the dealer ID and send CC emails.
 *
 * The entry is tagged by inserting a row into FF's own submission_meta table
 * (meta_key = 'rwdp_dealer_id'). No duplicate CPT post is created.
 *
 * @param int    $entry_id   Fluent Forms entry ID.
 * @param array  $form_data  Submitted form data (name → value pairs).
 * @param object $form       Fluent Forms form object.
 */
function rwdp_handle_fluent_form_submission( $entry_id, $form_data, $form ) {
	$settings    = get_option( 'rwdp_settings', [] );
	$target_form = absint( $settings['contact_form_id'] ?? 0 );

	if ( ! $target_form || absint( $form->id ) !== $target_form ) {
		return;
	}

	// Form must contain a hidden field named rwdp_dealer_id.
	$dealer_id = absint( rwdp_ff_extract( $form_data, 'rwdp_dealer_id' ) );
	if ( ! $dealer_id || get_post_type( $dealer_id ) !== 'rw_dealer' ) {
		return;
	}

	// Tag this FF entry with the dealer ID using FF's own submission_meta table.
	global $wpdb;
	$now = current_time( 'mysql', true ); // UTC
	$wpdb->insert(
		$wpdb->prefix . 'fluentform_submission_meta',
		[
			'response_id' => $entry_id,
			'form_id'     => absint( $form->id ),
			'meta_key'    => 'rwdp_dealer_id',
			'value'       => $dealer_id,
			'created_at'  => $now,
			'updated_at'  => $now,
		],
		[ '%d', '%d', '%s', '%d', '%s', '%s' ]
	);

	// Send CC notification emails — best-effort field extraction for email body.
	$name    = sanitize_text_field( rwdp_ff_extract( $form_data, [ 'name', 'full_name', 'first_name', 'your_name', 'names' ] ) );
	$email   = sanitize_email(      rwdp_ff_extract( $form_data, [ 'email', 'your_email', 'email_address' ] ) );
	$phone   = sanitize_text_field( rwdp_ff_extract( $form_data, [ 'phone', 'phone_number', 'telephone', 'your_phone' ] ) );
	$message = sanitize_textarea_field( rwdp_ff_extract( $form_data, [ 'message', 'your_message', 'comment', 'inquiry' ] ) );

	rwdp_send_submission_cc( $dealer_id, [
		'name'    => $name,
		'email'   => $email,
		'phone'   => $phone,
		'message' => $message,
	] );
}

/**
 * Extract a value from Fluent Forms $form_data by field name(s).
 * Handles the nested array structure Fluent Forms uses.
 *
 * @param array        $form_data
 * @param string|array $keys  One key or list of possible keys to try.
 * @return string
 */
function rwdp_ff_extract( $form_data, $keys ) {
	if ( ! is_array( $keys ) ) {
		$keys = [ $keys ];
	}

	foreach ( $keys as $key ) {
		if ( isset( $form_data[ $key ] ) ) {
			$val = $form_data[ $key ];
			// Fluent Forms name fields can be arrays: ['first_name' => ..., 'last_name' => ...]
			if ( is_array( $val ) ) {
				$parts = array_filter( array_values( $val ), 'is_scalar' );
				return implode( ' ', $parts );
			}
			if ( is_scalar( $val ) ) {
				return (string) $val;
			}
		}
	}
	return '';
}

/**
 * Send CC notification emails when a new submission is stored.
 *
 * @param int   $dealer_id
 * @param array $data  { name, email, phone, message }
 */
function rwdp_send_submission_cc( $dealer_id, $data ) {
	$settings       = get_option( 'rwdp_settings', [] );
	$global_cc      = array_filter( array_map( 'sanitize_email', preg_split( '/[\s,;]+/', $settings['cc_emails'] ?? '' ) ) );
	$dealer_emails  = get_post_meta( $dealer_id, '_rwdp_contact_emails', true );
	$dealer_emails  = array_filter( array_map( 'sanitize_email', preg_split( '/[\s,;]+/', $dealer_emails ) ) );

	$all_recipients = array_unique( array_merge( $global_cc, $dealer_emails ) );
	if ( empty( $all_recipients ) ) {
		return;
	}

	$dealer_name = get_the_title( $dealer_id );
	/* translators: 1: site name, 2: visitor's name */
	$subject     = sprintf( __( '[%1$s] New contact request from %2$s', 'rw-dealer-portal' ), get_bloginfo( 'name' ), $data['name'] ?: __( 'a visitor', 'rw-dealer-portal' ) );

	$body  = sprintf( "Dealer: %s\n\n", $dealer_name );
	$body .= sprintf( "Name:    %s\n", $data['name'] );
	$body .= sprintf( "Email:   %s\n", $data['email'] );
	$body .= sprintf( "Phone:   %s\n", $data['phone'] );
	$body .= sprintf( "\nMessage:\n%s\n", $data['message'] );

	$headers = [ 'Content-Type: text/plain; charset=UTF-8' ];
	if ( $data['email'] ) {
		$reply_name     = $data['name'] ?: $data['email'];
		$headers[]      = 'Reply-To: ' . $reply_name . ' <' . $data['email'] . '>';
	}

	wp_mail( $all_recipients, $subject, $body, $headers );
}
