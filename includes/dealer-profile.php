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

			$phone          = get_post_meta( $dealer_id, '_rwdp_phone', true );
			$email          = get_post_meta( $dealer_id, '_rwdp_public_email', true );
			$hours          = get_post_meta( $dealer_id, '_rwdp_hours', true );
			$website        = get_post_meta( $dealer_id, '_rwdp_website', true );
			$contact_emails = get_post_meta( $dealer_id, '_rwdp_contact_emails', true );
			$logo_id        = (int) get_post_meta( $dealer_id, '_rwdp_logo_id', true );
			$logo_src       = $logo_id ? wp_get_attachment_image_url( $logo_id, 'medium' ) : '';
			$featured_id    = (int) get_post_thumbnail_id( $dealer_id );
			$featured_src   = $featured_id ? wp_get_attachment_image_url( $featured_id, 'medium' ) : '';
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
					<label for="rwdp_website_<?php echo absint( $dealer_id ); ?>"><?php esc_html_e( 'Website', 'rw-dealer-portal' ); ?></label>
					<input type="url" id="rwdp_website_<?php echo absint( $dealer_id ); ?>" name="website"
						value="<?php echo esc_attr( $website ); ?>" placeholder="https://" autocomplete="url" />
				</div>
				<div class="rwdp-form-row">
					<label for="rwdp_contact_emails_<?php echo absint( $dealer_id ); ?>"><?php esc_html_e( 'Contact Request Email(s)', 'rw-dealer-portal' ); ?></label>
					<input type="text" id="rwdp_contact_emails_<?php echo absint( $dealer_id ); ?>" name="contact_emails"
						value="<?php echo esc_attr( $contact_emails ); ?>" />
					<p class="rwdp-field-desc"><?php esc_html_e( 'Comma-separated. These addresses receive contact form submissions for this dealer.', 'rw-dealer-portal' ); ?></p>
				</div>
				<div class="rwdp-form-row">
					<label for="rwdp_hours_<?php echo absint( $dealer_id ); ?>"><?php esc_html_e( 'Business Hours', 'rw-dealer-portal' ); ?></label>
					<textarea id="rwdp_hours_<?php echo absint( $dealer_id ); ?>" name="hours" rows="4"><?php echo esc_textarea( $hours ); ?></textarea>
				</div>
				<button type="submit" class="rwdp-btn rwdp-btn--primary"><?php esc_html_e( 'Save Changes', 'rw-dealer-portal' ); ?></button>
			</form>

			<div class="rwdp-image-uploads" data-dealer-id="<?php echo absint( $dealer_id ); ?>">
				<div id="rwdp-image-msg-<?php echo absint( $dealer_id ); ?>" class="rwdp-notice" style="display:none;"></div>

				<div class="rwdp-form-row">
					<label><?php esc_html_e( 'Featured Image', 'rw-dealer-portal' ); ?></label>
					<div class="rwdp-image-upload-wrap">
						<img class="rwdp-image-preview" src="<?php echo $featured_src ? esc_url( $featured_src ) : ''; ?>" alt=""
						style="display:<?php echo $featured_src ? 'block' : 'none'; ?>;" />
						<label class="rwdp-btn rwdp-btn--outline" style="display:inline-block; cursor:pointer;">
							<?php esc_html_e( 'Upload New Image', 'rw-dealer-portal' ); ?>
							<input type="file" class="rwdp-image-upload" data-field="featured_image" accept="image/*" style="display:none;" />
						</label>
						<span class="rwdp-upload-spinner" style="display:none; margin-left:8px;"><?php esc_html_e( 'Uploading…', 'rw-dealer-portal' ); ?></span>
					</div>
					<p class="rwdp-field-desc"><?php esc_html_e( 'Main photo for this dealer location (storefront, showroom, etc.) Note: Depending on the website theme, this image may not be displayed publicly.', 'rw-dealer-portal' ); ?></p>
				</div>

				<div class="rwdp-form-row">
					<label><?php esc_html_e( 'Dealer Logo', 'rw-dealer-portal' ); ?></label>
					<div class="rwdp-image-upload-wrap">
						<img class="rwdp-image-preview" src="<?php echo $logo_src ? esc_url( $logo_src ) : ''; ?>" alt=""
						style="display:<?php echo $logo_src ? 'block' : 'none'; ?>;" />
						<label class="rwdp-btn rwdp-btn--outline" style="display:inline-block; cursor:pointer;">
							<?php esc_html_e( 'Upload New Logo', 'rw-dealer-portal' ); ?>
							<input type="file" class="rwdp-image-upload" data-field="logo" accept="image/*" style="display:none;" />
						</label>
						<span class="rwdp-upload-spinner" style="display:none; margin-left:8px;"><?php esc_html_e( 'Uploading…', 'rw-dealer-portal' ); ?></span>
					</div>
					<p class="rwdp-field-desc"><?php esc_html_e( 'Your Business logo. (Depending on the website theme, this logo may not be displayed publicly)', 'rw-dealer-portal' ); ?></p>
				</div>
			</div>
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

	$phone          = sanitize_text_field( wp_unslash( $_POST['phone']          ?? '' ) );
	$email          = sanitize_email(       wp_unslash( $_POST['public_email']   ?? '' ) );
	$hours          = sanitize_textarea_field( wp_unslash( $_POST['hours']       ?? '' ) );
	$contact_emails = sanitize_text_field( wp_unslash( $_POST['contact_emails'] ?? '' ) );

	$website_raw = trim( wp_unslash( $_POST['website'] ?? '' ) );
	if ( $website_raw !== '' && ! preg_match( '#^[a-z][a-z0-9+\-.]*://#i', $website_raw ) ) {
		$website_raw = 'https://' . $website_raw;
	}
	$website = esc_url_raw( $website_raw );

	if ( $email && ! is_email( $email ) ) {
		wp_send_json_error( [ 'message' => __( 'Please enter a valid email address.', 'rw-dealer-portal' ) ] );
	}

	update_post_meta( $dealer_id, '_rwdp_phone', $phone );
	update_post_meta( $dealer_id, '_rwdp_public_email', $email );
	update_post_meta( $dealer_id, '_rwdp_hours', $hours );
	update_post_meta( $dealer_id, '_rwdp_website', $website );
	update_post_meta( $dealer_id, '_rwdp_contact_emails', $contact_emails );

	wp_send_json_success( [ 'message' => __( 'Dealer profile updated.', 'rw-dealer-portal' ) ] );
}

/**
 * AJAX: Upload a dealer image (featured image or logo) from the frontend portal.
 */
add_action( 'wp_ajax_rwdp_upload_dealer_image', 'rwdp_ajax_upload_dealer_image' );

function rwdp_ajax_upload_dealer_image() {
	check_ajax_referer( 'rwdp_edit_dealer', 'nonce' );

	if ( ! is_user_logged_in() ) {
		wp_send_json_error( [ 'message' => __( 'Not logged in.', 'rw-dealer-portal' ) ] );
	}

	$user_id   = get_current_user_id();
	$dealer_id = absint( $_POST['dealer_id'] ?? 0 );
	$field     = sanitize_key( $_POST['field'] ?? '' );

	if ( ! in_array( $field, [ 'featured_image', 'logo' ], true ) ) {
		wp_send_json_error( [ 'message' => __( 'Invalid field.', 'rw-dealer-portal' ) ] );
	}

	// Ownership check
	$linked_ids = array_map( 'absint', (array) get_user_meta( $user_id, '_rwdp_dealer_ids', true ) );
	if ( ! in_array( $dealer_id, $linked_ids, true ) ) {
		wp_send_json_error( [ 'message' => __( 'Permission denied.', 'rw-dealer-portal' ) ] );
	}

	if ( get_post_type( $dealer_id ) !== 'rw_dealer' ) {
		wp_send_json_error( [ 'message' => __( 'Invalid dealer.', 'rw-dealer-portal' ) ] );
	}

	if ( empty( $_FILES['file'] ) || $_FILES['file']['error'] !== UPLOAD_ERR_OK ) {
		wp_send_json_error( [ 'message' => __( 'No file received or upload error.', 'rw-dealer-portal' ) ] );
	}

	// Only allow image MIME types
	$file_type = wp_check_filetype( sanitize_file_name( $_FILES['file']['name'] ) );
	if ( ! $file_type['type'] || strpos( $file_type['type'], 'image/' ) !== 0 ) {
		wp_send_json_error( [ 'message' => __( 'Only image files are allowed.', 'rw-dealer-portal' ) ] );
	}

	require_once ABSPATH . 'wp-admin/includes/image.php';
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';

	$attachment_id = media_handle_upload( 'file', $dealer_id );

	if ( is_wp_error( $attachment_id ) ) {
		wp_send_json_error( [ 'message' => $attachment_id->get_error_message() ] );
	}

	if ( $field === 'featured_image' ) {
		set_post_thumbnail( $dealer_id, $attachment_id );
	} else {
		update_post_meta( $dealer_id, '_rwdp_logo_id', $attachment_id );
	}

	$url = wp_get_attachment_image_url( $attachment_id, 'medium' );
	wp_send_json_success( [ 'url' => $url ] );
}
