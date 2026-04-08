<?php
if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'add_meta_boxes', 'rwdp_register_dealer_meta_boxes' );
add_action( 'save_post_rw_dealer', 'rwdp_save_dealer_meta', 10, 2 );
add_action( 'admin_footer-post.php', 'rwdp_dealer_meta_media_script' );
add_action( 'admin_footer-post-new.php', 'rwdp_dealer_meta_media_script' );

function rwdp_register_dealer_meta_boxes() {
	add_meta_box(
		'rwdp_dealer_info',
		__( 'Dealer Information', 'rw-dealer-portal' ),
		'rwdp_render_dealer_info_meta_box',
		'rw_dealer',
		'normal',
		'high'
	);

	add_meta_box(
		'rwdp_dealer_images',
		__( 'Dealer Logo', 'rw-dealer-portal' ),
		'rwdp_render_dealer_logo_meta_box',
		'rw_dealer',
		'side',
		'default'
	);

	add_meta_box(
		'rwdp_dealer_geo',
		__( 'Geocoding Status', 'rw-dealer-portal' ),
		'rwdp_render_dealer_geo_meta_box',
		'rw_dealer',
		'side',
		'default'
	);
}

/**
 * Dealer Information meta box — address, phone, email, hours, contact emails.
 */
function rwdp_render_dealer_info_meta_box( $post ) {
	wp_nonce_field( 'rwdp_save_dealer_meta', 'rwdp_dealer_meta_nonce' );

	$address        = get_post_meta( $post->ID, '_rwdp_address', true );
	$city           = get_post_meta( $post->ID, '_rwdp_city', true );
	$state          = get_post_meta( $post->ID, '_rwdp_state', true );
	$zip            = get_post_meta( $post->ID, '_rwdp_zip', true );
	$phone          = get_post_meta( $post->ID, '_rwdp_phone', true );
	$public_email   = get_post_meta( $post->ID, '_rwdp_public_email', true );
	$contact_emails = get_post_meta( $post->ID, '_rwdp_contact_emails', true );
	$hours          = get_post_meta( $post->ID, '_rwdp_hours', true );
	$website        = get_post_meta( $post->ID, '_rwdp_website', true );
	?>
	<style>
		.rwdp-meta-row { display: grid; grid-template-columns: 140px 1fr; align-items: start; gap: 8px 12px; margin-bottom: 12px; }
		.rwdp-meta-row label { font-weight: 600; padding-top: 4px; }
		.rwdp-meta-row input[type="text"], .rwdp-meta-row input[type="email"], .rwdp-meta-row input[type="url"], .rwdp-meta-row textarea { width: 100%; }
		.rwdp-meta-section-title { font-weight: 700; font-size: 13px; margin: 16px 0 8px; border-bottom: 1px solid #ddd; padding-bottom: 4px; }
	</style>

	<p class="rwdp-meta-section-title"><?php esc_html_e( 'Address', 'rw-dealer-portal' ); ?></p>

	<div class="rwdp-meta-row">
		<label for="rwdp_address"><?php esc_html_e( 'Street Address', 'rw-dealer-portal' ); ?></label>
		<input type="text" id="rwdp_address" name="rwdp_address" value="<?php echo esc_attr( $address ); ?>" />
	</div>
	<div class="rwdp-meta-row">
		<label for="rwdp_city"><?php esc_html_e( 'City', 'rw-dealer-portal' ); ?></label>
		<input type="text" id="rwdp_city" name="rwdp_city" value="<?php echo esc_attr( $city ); ?>" />
	</div>
	<div class="rwdp-meta-row">
		<label for="rwdp_state"><?php esc_html_e( 'State', 'rw-dealer-portal' ); ?></label>
		<input type="text" id="rwdp_state" name="rwdp_state" value="<?php echo esc_attr( $state ); ?>" style="max-width:80px;" />
	</div>
	<div class="rwdp-meta-row">
		<label for="rwdp_zip"><?php esc_html_e( 'ZIP Code', 'rw-dealer-portal' ); ?></label>
		<input type="text" id="rwdp_zip" name="rwdp_zip" value="<?php echo esc_attr( $zip ); ?>" style="max-width:120px;" />
	</div>

	<p class="rwdp-meta-section-title"><?php esc_html_e( 'Contact', 'rw-dealer-portal' ); ?></p>

	<div class="rwdp-meta-row">
		<label for="rwdp_phone"><?php esc_html_e( 'Phone', 'rw-dealer-portal' ); ?></label>
		<input type="text" id="rwdp_phone" name="rwdp_phone" value="<?php echo esc_attr( $phone ); ?>" />
	</div>
	<div class="rwdp-meta-row">
		<label for="rwdp_website"><?php esc_html_e( 'Website', 'rw-dealer-portal' ); ?></label>
		<input type="url" id="rwdp_website" name="rwdp_website" value="<?php echo esc_attr( $website ); ?>" placeholder="https://" />
	</div>
	<div class="rwdp-meta-row">
		<label for="rwdp_public_email"><?php esc_html_e( 'Public Email', 'rw-dealer-portal' ); ?></label>
		<input type="email" id="rwdp_public_email" name="rwdp_public_email" value="<?php echo esc_attr( $public_email ); ?>" />
	</div>
	<div class="rwdp-meta-row">
		<label for="rwdp_contact_emails"><?php esc_html_e( 'Request Email(s)', 'rw-dealer-portal' ); ?></label>
		<div>
			<input type="text" id="rwdp_contact_emails" name="rwdp_contact_emails" value="<?php echo esc_attr( $contact_emails ); ?>" />
			<p class="description"><?php esc_html_e( 'Comma-separated. These addresses receive contact form submissions sent to this dealer.', 'rw-dealer-portal' ); ?></p>
		</div>
	</div>

	<p class="rwdp-meta-section-title"><?php esc_html_e( 'Business Hours', 'rw-dealer-portal' ); ?></p>

	<div class="rwdp-meta-row">
		<label for="rwdp_hours"><?php esc_html_e( 'Hours', 'rw-dealer-portal' ); ?></label>
		<textarea id="rwdp_hours" name="rwdp_hours" rows="4"><?php echo esc_textarea( $hours ); ?></textarea>
	</div>
	<?php
}

/**
 * Dealer Logo meta box — stores a media attachment ID separate from the featured image.
 */
function rwdp_render_dealer_logo_meta_box( $post ) {
	$logo_id  = (int) get_post_meta( $post->ID, '_rwdp_logo_id', true );
	$logo_src = $logo_id ? wp_get_attachment_image_url( $logo_id, 'medium' ) : '';
	?>
	<div id="rwdp-logo-container">
		<?php if ( $logo_src ) : ?>
			<img id="rwdp-logo-preview" src="<?php echo esc_url( $logo_src ); ?>" style="max-width:100%; margin-bottom:8px; display:block;" alt="" />
		<?php else : ?>
			<img id="rwdp-logo-preview" src="" style="max-width:100%; margin-bottom:8px; display:none;" alt="" />
		<?php endif; ?>
	</div>
	<input type="hidden" id="rwdp_logo_id" name="rwdp_logo_id" value="<?php echo absint( $logo_id ); ?>" />
	<button type="button" id="rwdp-upload-logo" class="button"><?php esc_html_e( 'Upload / Change Logo', 'rw-dealer-portal' ); ?></button>
	<?php if ( $logo_id ) : ?>
		<button type="button" id="rwdp-remove-logo" class="button" style="margin-left:4px;"><?php esc_html_e( 'Remove Logo', 'rw-dealer-portal' ); ?></button>
	<?php else : ?>
		<button type="button" id="rwdp-remove-logo" class="button" style="margin-left:4px; display:none;"><?php esc_html_e( 'Remove Logo', 'rw-dealer-portal' ); ?></button>
	<?php endif; ?>
	<p class="description" style="margin-top:8px;"><?php esc_html_e( 'Dealer logo (separate from the Featured Image, which should be a location/storefront photo).', 'rw-dealer-portal' ); ?></p>
	<?php
}

/**
 * Geocoding status meta box — shows current lat/lng and address validation flag.
 */
function rwdp_render_dealer_geo_meta_box( $post ) {
	$lat   = get_post_meta( $post->ID, '_rwdp_lat', true );
	$lng   = get_post_meta( $post->ID, '_rwdp_lng', true );
	$valid = get_post_meta( $post->ID, '_rwdp_address_valid', true );

	if ( $lat && $lng ) {
		echo '<p style="color:green;">&#10003; ' . esc_html__( 'Address geocoded', 'rw-dealer-portal' ) . '</p>';
		echo '<p><strong>' . esc_html__( 'Latitude:', 'rw-dealer-portal' ) . '</strong> ' . esc_html( $lat ) . '</p>';
		echo '<p><strong>' . esc_html__( 'Longitude:', 'rw-dealer-portal' ) . '</strong> ' . esc_html( $lng ) . '</p>';
		echo '<p class="description">' . esc_html__( 'Geocoding runs automatically when the post is saved. Edit the address fields and re-save to update.', 'rw-dealer-portal' ) . '</p>';
	} elseif ( $valid === '0' ) {
		$geo_error = get_post_meta( $post->ID, '_rwdp_geo_error', true );
		echo '<p style="color:red;">&#10007; ' . esc_html__( 'Address could not be geocoded. Check the address and save again.', 'rw-dealer-portal' ) . '</p>';
		if ( $geo_error ) {
			$hints = [
				'REQUEST_DENIED'       => __( 'API key is missing, invalid, or the Geocoding API is not enabled for this key.', 'rw-dealer-portal' ),
				'ZERO_RESULTS'        => __( 'Address was not found. Check spelling/ZIP and try again.', 'rw-dealer-portal' ),
				'OVER_DAILY_LIMIT'    => __( 'Google API daily quota exceeded.', 'rw-dealer-portal' ),
				'OVER_QUERY_LIMIT'    => __( 'Google API rate limit hit. Wait a moment and re-save.', 'rw-dealer-portal' ),
				'INVALID_REQUEST'     => __( 'Invalid request sent to Google. Check address fields.', 'rw-dealer-portal' ),
				'NO_RESPONSE'         => __( 'Could not reach the Google API. Check server outbound HTTP access.', 'rw-dealer-portal' ),
			];
			$hint = $hints[ $geo_error ] ?? '';
			echo '<p class="description"><strong>' . esc_html( $geo_error ) . '</strong>';
			if ( $hint ) echo ': ' . esc_html( $hint );
			echo '</p>';
		}
	} else {
		echo '<p class="description">' . esc_html__( 'No geocoding data yet. Fill in the address and save.', 'rw-dealer-portal' ) . '</p>';
	}
}

/**
 * Save all dealer meta on post save.
 */
function rwdp_save_dealer_meta( $post_id, $post ) {
	// Nonce check
	if ( ! isset( $_POST['rwdp_dealer_meta_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['rwdp_dealer_meta_nonce'] ) ), 'rwdp_save_dealer_meta' ) ) {
		return;
	}

	// Autosave / capability guards
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
	if ( ! current_user_can( 'edit_rw_dealer', $post_id ) ) return;

	$fields = [
		'_rwdp_address'        => 'rwdp_address',
		'_rwdp_city'           => 'rwdp_city',
		'_rwdp_state'          => 'rwdp_state',
		'_rwdp_zip'            => 'rwdp_zip',
		'_rwdp_phone'          => 'rwdp_phone',
		'_rwdp_website'        => 'rwdp_website',
		'_rwdp_public_email'   => 'rwdp_public_email',
		'_rwdp_contact_emails' => 'rwdp_contact_emails',
		'_rwdp_hours'          => 'rwdp_hours',
	];

	foreach ( $fields as $meta_key => $post_key ) {
		if ( $meta_key === '_rwdp_hours' ) {
			$value = sanitize_textarea_field( wp_unslash( $_POST[ $post_key ] ?? '' ) );
		} elseif ( $meta_key === '_rwdp_public_email' ) {
			$value = sanitize_email( wp_unslash( $_POST[ $post_key ] ?? '' ) );
		} elseif ( $meta_key === '_rwdp_website' ) {
			$value = esc_url_raw( wp_unslash( $_POST[ $post_key ] ?? '' ) );
		} else {
			$value = sanitize_text_field( wp_unslash( $_POST[ $post_key ] ?? '' ) );
		}
		update_post_meta( $post_id, $meta_key, $value );
	}

	// Logo attachment ID
	$logo_id = absint( $_POST['rwdp_logo_id'] ?? 0 );
	update_post_meta( $post_id, '_rwdp_logo_id', $logo_id );

	// Trigger geocoding whenever address fields change
	$full_address = implode( ', ', array_filter( [
		sanitize_text_field( wp_unslash( $_POST['rwdp_address'] ?? '' ) ),
		sanitize_text_field( wp_unslash( $_POST['rwdp_city']    ?? '' ) ),
		sanitize_text_field( wp_unslash( $_POST['rwdp_state']   ?? '' ) ),
		sanitize_text_field( wp_unslash( $_POST['rwdp_zip']     ?? '' ) ),
	] ) );

	if ( $full_address ) {
		rwdp_geocode_and_store( $post_id, $full_address );
	}
}

/**
 * JS for the media uploader (logo field).
 */
function rwdp_dealer_meta_media_script() {
	$screen = get_current_screen();
	if ( ! $screen || $screen->post_type !== 'rw_dealer' ) return;
	?>
	<script>
	(function($){
		var frame;
		$('#rwdp-upload-logo').on('click', function(e){
			e.preventDefault();
			if ( frame ) { frame.open(); return; }
			frame = wp.media({
				title: '<?php echo esc_js( __( 'Select Dealer Logo', 'rw-dealer-portal' ) ); ?>',
				button: { text: '<?php echo esc_js( __( 'Use this logo', 'rw-dealer-portal' ) ); ?>' },
				multiple: false
			});
			frame.on('select', function(){
				var attachment = frame.state().get('selection').first().toJSON();
				$('#rwdp_logo_id').val( attachment.id );
				$('#rwdp-logo-preview').attr('src', attachment.url).show();
				$('#rwdp-remove-logo').show();
			});
			frame.open();
		});
		$('#rwdp-remove-logo').on('click', function(e){
			e.preventDefault();
			$('#rwdp_logo_id').val('0');
			$('#rwdp-logo-preview').attr('src','').hide();
			$(this).hide();
		});
	})(jQuery);
	</script>
	<?php
}
