<?php
if ( ! defined( 'ABSPATH' ) ) exit;

add_shortcode( 'rwdp_dealer_finder', 'rwdp_dealer_finder_shortcode' );

// Register assets early so Elementor widgets can declare them as dependencies.
add_action( 'wp_enqueue_scripts', 'rwdp_register_dealer_finder_assets', 5 );

function rwdp_register_dealer_finder_assets() {
	$settings = get_option( 'rwdp_settings', [] );
	$maps_key = $settings['google_maps_api_key'] ?? '';

	wp_register_style( 'rwdp-dealer-map', RWDP_PLUGIN_URL . 'assets/css/dealer-map.css', [], RWDP_VERSION );

	wp_register_script( 'rwdp-dealer-map', RWDP_PLUGIN_URL . 'assets/js/dealer-map.js', [ 'jquery' ], RWDP_VERSION, true );

	// Localize at registration time — wp_localize_script only outputs data if the
	// script is actually enqueued, so this is safe and covers both the shortcode
	// and Elementor widget paths.
	wp_localize_script( 'rwdp-dealer-map', 'rwdpMap', [
		'ajaxUrl'        => admin_url( 'admin-ajax.php' ),
		'nonce'          => wp_create_nonce( 'rwdp_dealer_finder' ),
		'hasMapsKey'     => ! empty( $maps_key ),
		'noResults'      => __( 'No dealers found near that location.', 'rw-dealer-portal' ),
		'contactText'    => __( 'Contact This Dealer', 'rw-dealer-portal' ),
		'directionsText' => __( 'Get Directions', 'rw-dealer-portal' ),
		'viewOnMapText'  => __( 'View on Map', 'rw-dealer-portal' ),
		'moreInfoText'   => __( 'More Info', 'rw-dealer-portal' ),
	] );

	wp_register_script( 'rwdp-ff-helper', RWDP_PLUGIN_URL . 'assets/js/fluent-forms-helper.js', [ 'jquery', 'rwdp-dealer-map' ], RWDP_VERSION, true );

	if ( $maps_key ) {
		wp_register_script(
			'google-maps',
			'https://maps.googleapis.com/maps/api/js?key=' . rawurlencode( $maps_key ) . '&libraries=places&callback=rwdpInitMap',
			[ 'rwdp-dealer-map' ],
			null,
			true
		);
	}
}

// Enqueue for pages using the shortcode (Elementor widgets use get_script/style_depends instead).
add_action( 'wp_enqueue_scripts', 'rwdp_enqueue_dealer_finder_assets' );

function rwdp_enqueue_dealer_finder_assets() {
	global $post;
	if ( ! $post || ! has_shortcode( $post->post_content, 'rwdp_dealer_finder' ) ) {
		return;
	}

	$settings = get_option( 'rwdp_settings', [] );
	$maps_key = $settings['google_maps_api_key'] ?? '';

	wp_enqueue_style( 'rwdp-dealer-map' );
	wp_enqueue_script( 'rwdp-dealer-map' );
	wp_enqueue_script( 'rwdp-ff-helper' );

	if ( $maps_key ) {
		wp_enqueue_script( 'google-maps' );
	}
}

function rwdp_dealer_finder_shortcode( $atts ) {
	$atts = shortcode_atts( [
		'dealer_type' => '',  // optionally limit to a specific rw_dealer_type slug
	], $atts, 'rwdp_dealer_finder' );

	$settings = get_option( 'rwdp_settings', [] );
	$form_id  = absint( $settings['contact_form_id'] ?? 0 );

	// Store the raw dealer_type value for AJAX to resolve server-side.
	$locked_type_slug = sanitize_text_field( $atts['dealer_type'] );

	ob_start();
	?>
	<div class="rwdp-dealer-finder" id="rwdp-dealer-finder"
	     data-locked-type="<?php echo esc_attr( $locked_type_slug ); ?>">
		<div class="rwdp-finder__controls">
			<div class="rwdp-finder__search">
				<label for="rwdp-location-search" class="screen-reader-text"><?php esc_html_e( 'Search by ZIP or city', 'rw-dealer-portal' ); ?></label>
				<input type="text"
					id="rwdp-location-search"
					class="rwdp-finder__input"
					placeholder="<?php esc_attr_e( 'Enter ZIP code or city', 'rw-dealer-portal' ); ?>"
					autocomplete="postal-code"
					aria-label="<?php esc_attr_e( 'Enter ZIP code or city to find dealers', 'rw-dealer-portal' ); ?>"
				/>
				<button class="rwdp-btn rwdp-btn--primary" id="rwdp-search-btn" type="button">
					<?php esc_html_e( 'Find Dealers', 'rw-dealer-portal' ); ?>
				</button>
			</div>

			<div class="rwdp-finder__radius">
				<label for="rwdp-radius-select"><?php esc_html_e( 'Within:', 'rw-dealer-portal' ); ?></label>
				<select id="rwdp-radius-select">
					<option value="25">25 <?php esc_html_e( 'miles', 'rw-dealer-portal' ); ?></option>
					<option value="50" selected>50 <?php esc_html_e( 'miles', 'rw-dealer-portal' ); ?></option>
					<option value="100">100 <?php esc_html_e( 'miles', 'rw-dealer-portal' ); ?></option>
					<option value="250">250 <?php esc_html_e( 'miles', 'rw-dealer-portal' ); ?></option>
					<option value="0"><?php esc_html_e( 'All', 'rw-dealer-portal' ); ?></option>
				</select>
			</div>

	<?php if ( ! $locked_type_slug ) :
				$dealer_types = get_terms( [ 'taxonomy' => 'rw_dealer_type', 'hide_empty' => true ] );
				if ( $dealer_types && ! is_wp_error( $dealer_types ) ) :
			?>
			<div class="rwdp-finder__type-filter">
				<label for="rwdp-type-filter"><?php esc_html_e( 'Type:', 'rw-dealer-portal' ); ?></label>
				<select id="rwdp-type-filter">
					<option value=""><?php esc_html_e( 'All Types', 'rw-dealer-portal' ); ?></option>
					<?php foreach ( $dealer_types as $type ) : ?>
						<option value="<?php echo absint( $type->term_id ); ?>">
							<?php echo esc_html( $type->name ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</div>
			<?php endif; // $dealer_types
		endif; // ! $locked_type_slug
		?>
		</div>

		<div class="rwdp-finder__layout">
			<div class="rwdp-finder__map-wrap">
				<div id="rwdp-map" class="rwdp-finder__map"></div>
			</div>
			<div class="rwdp-finder__results" id="rwdp-results-list">
				<p class="rwdp-finder__hint"><?php esc_html_e( 'Enter your zip code or city to find dealers near you.', 'rw-dealer-portal' ); ?></p>
			</div>
		</div>

		<?php if ( $form_id ) : ?>
		<div class="rwdp-finder__contact-modal" id="rwdp-contact-modal" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="rwdp-modal-title">
			<div class="rwdp-modal__overlay" id="rwdp-modal-overlay"></div>
			<div class="rwdp-modal__content">
				<button type="button" class="rwdp-modal__close" id="rwdp-modal-close" aria-label="<?php esc_attr_e( 'Close', 'rw-dealer-portal' ); ?>">&times;</button>
				<h3 id="rwdp-modal-title" class="rwdp-modal__title"><?php esc_html_e( 'Contact', 'rw-dealer-portal' ); ?> <span id="rwdp-modal-dealer-name"></span></h3>
				<div id="rwdp-contact-form-wrap">
					<?php echo do_shortcode( '[fluentform id="' . $form_id . '"]' ); ?>
				</div>
			</div>
		</div>
		<?php endif; ?>
	</div>

	<?php if ( empty( $settings['google_maps_api_key'] ) ) : ?>
	<p class="rwdp-notice rwdp-notice--warning">
		<?php esc_html_e( 'Google Maps API key is not configured. Please add it in Dealer Portal → Settings.', 'rw-dealer-portal' ); ?>
	</p>
	<?php endif; ?>
	<?php
	return ob_get_clean();
}

/**
 * AJAX: Return all published dealers with geocoordinates + display data.
 * Publicly accessible (nopriv) — data is non-sensitive.
 */
add_action( 'wp_ajax_rwdp_get_dealers',        'rwdp_ajax_get_dealers' );
add_action( 'wp_ajax_nopriv_rwdp_get_dealers', 'rwdp_ajax_get_dealers' );

function rwdp_ajax_get_dealers() {
	check_ajax_referer( 'rwdp_dealer_finder', 'nonce' );

	$type_filter = 0;

	$allowed_sizes      = array_merge( [ 'full' ], array_keys( wp_get_registered_image_subsizes() ) );
	$raw_thumb_size     = sanitize_key( wp_unslash( $_POST['thumbnail_image_size'] ?? 'large' ) );
	$raw_logo_size      = sanitize_key( wp_unslash( $_POST['logo_image_size']      ?? 'large' ) );
	$thumbnail_img_size = in_array( $raw_thumb_size, $allowed_sizes, true ) ? $raw_thumb_size : 'large';
	$logo_img_size      = in_array( $raw_logo_size,  $allowed_sizes, true ) ? $raw_logo_size  : 'large';

	// If a locked type slug was sent from the shortcode, resolve it server-side
	// (try slug first, fall back to term name).
	$locked_type_slug = sanitize_text_field( wp_unslash( $_POST['locked_type'] ?? '' ) );
	if ( $locked_type_slug ) {
		$locked_term = get_term_by( 'slug', $locked_type_slug, 'rw_dealer_type' );
		if ( ! $locked_term || is_wp_error( $locked_term ) ) {
			$locked_term = get_term_by( 'name', $locked_type_slug, 'rw_dealer_type' );
		}
		if ( $locked_term && ! is_wp_error( $locked_term ) ) {
			$type_filter = (int) $locked_term->term_id;
		}
	} else {
		// Dropdown-based filter (regular shortcode, no locked type).
		$type_filter = absint( $_POST['type_id'] ?? 0 );
	}

	$args = [
		'post_type'      => 'rw_dealer',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'orderby'        => 'title',
		'order'          => 'ASC',
		'meta_query'     => [ [
			'key'     => '_rwdp_address_valid',
			'value'   => '1',
			'compare' => '=',
		] ],
	];

	if ( $type_filter ) {
		$args['tax_query'] = [ [
			'taxonomy' => 'rw_dealer_type',
			'field'    => 'term_id',
			'terms'    => $type_filter,
		] ];
	}

	$dealers = get_posts( $args );
	$data    = [];

	foreach ( $dealers as $dealer ) {
		$lat      = (float) get_post_meta( $dealer->ID, '_rwdp_lat',          true );
		$lng      = (float) get_post_meta( $dealer->ID, '_rwdp_lng',          true );
		$logo_id  = get_post_meta( $dealer->ID, '_rwdp_logo_id', true );
		$feat_img = get_the_post_thumbnail_url( $dealer->ID, $thumbnail_img_size );

		// Build address string for info window
		$city  = get_post_meta( $dealer->ID, '_rwdp_city',    true );
		$state = get_post_meta( $dealer->ID, '_rwdp_state',   true );
		$zip   = get_post_meta( $dealer->ID, '_rwdp_zip',     true );

		$types    = get_the_terms( $dealer->ID, 'rw_dealer_type' );
		$type_ids = $types && ! is_wp_error( $types ) ? wp_list_pluck( $types, 'term_id' ) : [];

		$data[] = [
			'id'         => $dealer->ID,
			'title'      => $dealer->post_title,
			'lat'        => $lat,
			'lng'        => $lng,
			'phone'      => get_post_meta( $dealer->ID, '_rwdp_phone',        true ),
			'website'    => get_post_meta( $dealer->ID, '_rwdp_website',      true ),
			'email'      => get_post_meta( $dealer->ID, '_rwdp_public_email', true ),
			'address'    => get_post_meta( $dealer->ID, '_rwdp_address',      true ),
			'city'       => $city,
			'state'      => $state,
			'zip'        => $zip,
			'hours'      => get_post_meta( $dealer->ID, '_rwdp_hours',        true ),
			'logo_url'   => $logo_id ? wp_get_attachment_image_url( $logo_id, $logo_img_size ) : '',
			'feat_img'   => $feat_img ?: '',
			'permalink'  => get_permalink( $dealer->ID ),
			'type_ids'   => array_map( 'absint', $type_ids ),
		];
	}

	wp_send_json_success( [ 'dealers' => $data ] );
}

/**
 * AJAX: Retrieve contact email for a dealer (nonce-protected, used to pre-fill hidden fields).
 */
add_action( 'wp_ajax_rwdp_get_dealer_contact_email',        'rwdp_ajax_get_dealer_contact_email' );
add_action( 'wp_ajax_nopriv_rwdp_get_dealer_contact_email', 'rwdp_ajax_get_dealer_contact_email' );

function rwdp_ajax_get_dealer_contact_email() {
	check_ajax_referer( 'rwdp_dealer_finder', 'nonce' );

	$dealer_id = absint( $_POST['dealer_id'] ?? 0 );
	if ( ! $dealer_id || get_post_type( $dealer_id ) !== 'rw_dealer' ) {
		wp_send_json_error();
	}

	$contact_emails = get_post_meta( $dealer_id, '_rwdp_contact_emails', true );
	wp_send_json_success( [ 'emails' => sanitize_text_field( $contact_emails ) ] );
}
