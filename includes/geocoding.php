<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Geocode a full address string using the Google Geocoding API.
 * Returns an array with 'lat' and 'lng' keys, or false on failure.
 *
 * @param string $address Full address string.
 * @return array|false
 */
function rwdp_geocode_address( $address ) {
	$settings = get_option( 'rwdp_settings', [] );
	// Prefer the dedicated server key; fall back to the frontend key for single-key setups.
	$api_key  = ! empty( $settings['google_maps_server_key'] )
		? $settings['google_maps_server_key']
		: ( $settings['google_maps_api_key'] ?? '' );

	if ( empty( $api_key ) || empty( $address ) ) {
		return false;
	}

	// Note: add_query_arg handles URL encoding internally — do NOT pre-encode $address.
	$url = add_query_arg( [
		'address' => $address,
		'key'     => $api_key,
	], 'https://maps.googleapis.com/maps/api/geocode/json' );

	$response = wp_remote_get( $url, [ 'timeout' => 10 ] );

	if ( is_wp_error( $response ) ) {
		return false;
	}

	$body = json_decode( wp_remote_retrieve_body( $response ), true );

	if ( empty( $body['results'][0]['geometry']['location'] ) ) {
		// Return the API status so callers can store it for diagnostics.
		return [ 'error' => $body['status'] ?? 'UNKNOWN' ];
	}

	$location = $body['results'][0]['geometry']['location'];

	return [
		'lat' => (float) $location['lat'],
		'lng' => (float) $location['lng'],
	];
}

/**
 * Geocode the dealer's address and store lat/lng in post meta.
 * Called from meta-fields.php after saving dealer meta.
 *
 * @param int    $post_id   The dealer post ID.
 * @param string $address   Full address string to geocode.
 */
function rwdp_geocode_and_store( $post_id, $address ) {
	$result = rwdp_geocode_address( $address );

	if ( $result && isset( $result['lat'] ) ) {
		update_post_meta( $post_id, '_rwdp_lat', $result['lat'] );
		update_post_meta( $post_id, '_rwdp_lng', $result['lng'] );
		update_post_meta( $post_id, '_rwdp_address_valid', '1' );
		delete_post_meta( $post_id, '_rwdp_geo_error' );
	} else {
		$error = ( $result && isset( $result['error'] ) ) ? $result['error'] : 'NO_RESPONSE';
		update_post_meta( $post_id, '_rwdp_geo_error', sanitize_text_field( $error ) );

		// Only mark address invalid for definitive failures (address not found).
		// Transient errors (rate limits, network issues, bad API key) should not
		// strip a dealer from search results — preserve the existing valid status.
		$definitive_failures = [ 'ZERO_RESULTS', 'INVALID_REQUEST' ];
		if ( in_array( $error, $definitive_failures, true ) ) {
			update_post_meta( $post_id, '_rwdp_address_valid', '0' );
		}
		// For all other errors (REQUEST_DENIED, OVER_QUERY_LIMIT, NO_RESPONSE, etc.)
		// leave _rwdp_address_valid and existing lat/lng untouched.
	}
}
