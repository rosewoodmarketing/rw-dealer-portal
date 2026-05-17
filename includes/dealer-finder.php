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
	$map_localized_data = [
		'ajaxUrl'        => admin_url( 'admin-ajax.php' ),
		'nonce'          => wp_create_nonce( 'rwdp_dealer_finder' ),
		'hasMapsKey'     => ! empty( $maps_key ),
		'noResults'      => __( 'No dealers found near that location.', 'rw-dealer-portal' ),
		'contactText'    => __( 'Contact This Dealer', 'rw-dealer-portal' ),
		'directionsText' => __( 'Get Directions', 'rw-dealer-portal' ),
		'viewOnMapText'  => __( 'View on Map', 'rw-dealer-portal' ),
		'moreInfoText'   => __( 'More Info', 'rw-dealer-portal' ),
	];

	$map_localized_data = apply_filters( 'rwdp_map_localized_data', $map_localized_data );

	wp_localize_script( 'rwdp-dealer-map', 'rwdpMap', $map_localized_data );

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

/**
 * Return the active dealer finder filter settings with safe defaults.
 *
 * @return array{enabled:bool,active_fields:array<string>,filter_logic:string,acf_field:string}
 */
function rwdp_get_dealer_filter_settings() {
	$settings = get_option( 'rwdp_settings', [] );

	$active_fields = $settings['active_filter_fields'] ?? [];
	if ( ! is_array( $active_fields ) ) {
		$active_fields = [];
	}
	$active_fields = array_values( array_filter( array_map( 'sanitize_key', $active_fields ) ) );

	// Backward compat: migrate old single-field setting if new array is empty.
	if ( empty( $active_fields ) ) {
		$legacy = sanitize_key( $settings['filter_acf_field_name'] ?? '' );
		if ( $legacy ) {
			$active_fields = [ $legacy ];
		}
	}

	$filter_logic = sanitize_key( $settings['filter_logic'] ?? 'and' );
	if ( ! in_array( $filter_logic, [ 'and', 'or' ], true ) ) {
		$filter_logic = 'and';
	}

	return [
		'active_fields' => $active_fields,
		'filter_logic'  => $filter_logic,
		'acf_field'     => ! empty( $active_fields ) ? $active_fields[0] : '',
	];
}

/**
 * Auto-detect all ACF Relationship/Post Object fields on the rw_dealer post type.
 * Results are cached for one hour and busted on ACF field group updates.
 *
 * @return array<int, array{key:string,label:string,type:string}>
 */
function rwdp_detect_acf_relationship_fields() {
	$cached = get_transient( 'rwdp_acf_rel_fields' );
	if ( false !== $cached ) {
		return $cached;
	}

	$fields = [];
	if ( function_exists( 'acf_get_field_groups' ) && function_exists( 'acf_get_fields' ) ) {
		$groups = acf_get_field_groups( [ 'post_type' => 'rw_dealer' ] );
		foreach ( $groups as $group ) {
			$group_fields = acf_get_fields( $group['key'] );
			if ( ! is_array( $group_fields ) ) {
				continue;
			}
			foreach ( $group_fields as $field ) {
				if ( in_array( $field['type'] ?? '', [ 'relationship', 'post_object' ], true ) ) {
					$fields[] = [
						'key'   => sanitize_key( $field['name'] ),
						'label' => sanitize_text_field( $field['label'] ),
						'type'  => $field['type'],
					];
				}
			}
		}
	}

	set_transient( 'rwdp_acf_rel_fields', $fields, HOUR_IN_SECONDS );
	return $fields;
}
add_action( 'acf/update_field_group', function() {
	delete_transient( 'rwdp_acf_rel_fields' );
} );

/**
 * Normalize possible ACF relationship/post object values to post IDs.
 *
 * @param mixed $value
 * @return array<int>
 */
function rwdp_normalize_related_post_ids( $value ) {
	if ( empty( $value ) ) {
		return [];
	}

	if ( is_string( $value ) ) {
		$maybe = maybe_unserialize( $value );
		if ( is_array( $maybe ) || is_object( $maybe ) ) {
			$value = $maybe;
		}
	}

	if ( is_object( $value ) && isset( $value->ID ) ) {
		$value = [ $value ];
	} elseif ( ! is_array( $value ) ) {
		$value = [ $value ];
	}

	$ids = [];
	foreach ( $value as $item ) {
		if ( is_object( $item ) && isset( $item->ID ) ) {
			$ids[] = absint( $item->ID );
			continue;
		}

		$item_id = absint( $item );
		if ( $item_id ) {
			$ids[] = $item_id;
		}
	}

	$ids = array_values( array_unique( array_filter( $ids ) ) );
	return array_map( 'absint', $ids );
}

/**
 * Return relationship-based dropdown options from rw_dealer posts.
 *
 * @param string $field_name
 * @return array<int, array{id:int,label:string,slug:string}>
 */
function rwdp_get_relationship_filter_options( $field_name ) {
	if ( ! $field_name ) {
		return [];
	}

	$transient_key = 'rwdp_filter_opts_' . sanitize_key( $field_name );
	$cached = get_transient( $transient_key );
	if ( false !== $cached ) {
		return $cached;
	}

	$dealers = get_posts( [
		'post_type'      => 'rw_dealer',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'fields'         => 'ids',
	] );

	if ( empty( $dealers ) ) {
		return [];
	}

	$related_ids = [];
	foreach ( $dealers as $dealer_id ) {
		$field_value = null;
		if ( function_exists( 'get_field' ) ) {
			$field_value = get_field( $field_name, $dealer_id, false );
			if ( empty( $field_value ) ) {
				$field_value = get_field( $field_name, $dealer_id, true );
			}
		}
		if ( empty( $field_value ) ) {
			$field_value = get_post_meta( $dealer_id, $field_name, true );
		}

		$related_ids = array_merge( $related_ids, rwdp_normalize_related_post_ids( $field_value ) );
	}

	$related_ids = array_values( array_unique( array_filter( array_map( 'absint', $related_ids ) ) ) );
	if ( empty( $related_ids ) ) {
		return [];
	}

	$related_posts = get_posts( [
		'post_type'      => 'any',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'post__in'       => $related_ids,
		'orderby'        => 'title',
		'order'          => 'ASC',
	] );

	$options = [];
	foreach ( $related_posts as $post ) {
		$options[] = [
			'id'    => absint( $post->ID ),
			'label' => $post->post_title,
			'slug'  => $post->post_name,
		];
	}

	set_transient( $transient_key, $options, HOUR_IN_SECONDS );

	return $options;
}

/**
 * Resolve a locked shortcode filter value to relationship post ID.
 * Supports optional "field_key:value" syntax to target a specific field;
 * otherwise searches all provided option sets.
 *
 * @param string $locked_value Raw value from shortcode attribute.
 * @param array<string, array<int, array{id:int,label:string,slug:string}>> $all_options Keyed by field key.
 * @return array{field:string,id:int} Matching field key and post ID, or field='' and id=0.
 */
function rwdp_resolve_locked_relationship_id( $locked_value, $all_options ) {
	$locked_value = trim( (string) $locked_value );
	if ( '' === $locked_value ) {
		return [ 'field' => '', 'id' => 0 ];
	}

	// Support "field_key:value" explicit targeting.
	$search_fields = $all_options;
	if ( strpos( $locked_value, ':' ) !== false ) {
		[ $explicit_key, $locked_value ] = explode( ':', $locked_value, 2 );
		$explicit_key = sanitize_key( $explicit_key );
		if ( isset( $all_options[ $explicit_key ] ) ) {
			$search_fields = [ $explicit_key => $all_options[ $explicit_key ] ];
		}
	}

	$needle = strtolower( $locked_value );
	foreach ( $search_fields as $field_key => $options ) {
		if ( ctype_digit( $locked_value ) ) {
			$candidate_id = absint( $locked_value );
			foreach ( $options as $option ) {
				if ( absint( $option['id'] ) === $candidate_id ) {
					return [ 'field' => $field_key, 'id' => $candidate_id ];
				}
			}
		}
		foreach ( $options as $option ) {
			if ( strtolower( (string) $option['slug'] ) === $needle || strtolower( (string) $option['label'] ) === $needle ) {
				return [ 'field' => $field_key, 'id' => absint( $option['id'] ) ];
			}
		}
	}

	return [ 'field' => '', 'id' => 0 ];
}

/**
 * Render filter dropdown controls (ACF relationship fields + taxonomy).
 * Used by both the shortcode and the Elementor Search widget so rendering
 * is always in sync.
 *
 * @param array  $active_fields     Array of ACF field keys to expose as dropdowns.
 * @param string $filter_logic      'and' or 'or'.
 * @param string $locked_type_slug  When non-empty, no dropdowns are rendered (locked mode).
 * @return string HTML string.
 */
function rwdp_render_filter_dropdowns( $active_fields, $filter_logic, $locked_type_slug ) {
	if ( $locked_type_slug ) {
		return '';
	}

	ob_start();

	// One dropdown per active ACF field.
	foreach ( $active_fields as $field_key ) {
		$options = rwdp_get_relationship_filter_options( $field_key );
		if ( empty( $options ) ) {
			continue;
		}

		// Get the human-readable label from ACF field detection.
		$field_label = '';
		$detected    = rwdp_detect_acf_relationship_fields();
		foreach ( $detected as $df ) {
			if ( $df['key'] === $field_key ) {
				$field_label = $df['label'];
				break;
			}
		}
		if ( ! $field_label ) {
			$field_label = ucwords( str_replace( [ '_', '-' ], ' ', $field_key ) );
		}

		$select_id = 'rwdp-filter-' . esc_attr( $field_key );
		?>
		<div class="rwdp-finder__type-filter">
			<label for="<?php echo esc_attr( $select_id ); ?>"><?php echo esc_html( $field_label ) . ':'; ?></label>
			<select id="<?php echo esc_attr( $select_id ); ?>"
			        class="rwdp-acf-filter"
			        data-field-key="<?php echo esc_attr( $field_key ); ?>">
				<option value=""><?php
					/* translators: %s: filter label */
					printf( esc_html__( 'All %s', 'rw-dealer-portal' ), esc_html( $field_label ) );
				?></option>
				<?php foreach ( $options as $option ) : ?>
					<option value="<?php echo absint( $option['id'] ); ?>">
						<?php echo esc_html( $option['label'] ); ?>
					</option>
				<?php endforeach; ?>
			</select>
		</div>
		<?php
	}

	// Taxonomy dropdown.
	$taxonomy_options = get_terms( [ 'taxonomy' => 'rw_dealer_type', 'hide_empty' => true ] );
	if ( $taxonomy_options && ! is_wp_error( $taxonomy_options ) ) {
		?>
		<div class="rwdp-finder__type-filter">
			<label for="rwdp-tax-filter"><?php esc_html_e( 'Dealer Type:', 'rw-dealer-portal' ); ?></label>
			<select id="rwdp-tax-filter">
				<option value=""><?php esc_html_e( 'All Dealer Types', 'rw-dealer-portal' ); ?></option>
				<?php foreach ( $taxonomy_options as $type_term ) : ?>
					<option value="<?php echo absint( $type_term->term_id ); ?>">
						<?php echo esc_html( $type_term->name ); ?>
					</option>
				<?php endforeach; ?>
			</select>
		</div>
		<?php
	}

	return (string) ob_get_clean();
}

function rwdp_dealer_finder_shortcode( $atts ) {
	$atts = shortcode_atts( [
		'dealer_type' => '',  // optionally lock to a specific type (bare slug/id, or field_key:value)
	], $atts, 'rwdp_dealer_finder' );

	$settings         = get_option( 'rwdp_settings', [] );
	$form_id          = absint( $settings['contact_form_id'] ?? 0 );
	$filter_settings  = rwdp_get_dealer_filter_settings();
	$locked_type_slug = sanitize_text_field( $atts['dealer_type'] );

	ob_start();
	?>
	<div class="rwdp-dealer-finder" id="rwdp-dealer-finder"
	     data-locked-type="<?php echo esc_attr( $locked_type_slug ); ?>"
	     data-filter-logic="<?php echo esc_attr( $filter_settings['filter_logic'] ); ?>">
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

			<?php
			echo rwdp_render_filter_dropdowns(
				$filter_settings['active_fields'],
				$filter_settings['filter_logic'],
				$locked_type_slug
			);
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
		<?php esc_html_e( 'Google Maps API key is not configured. Please add it in Dealer Portal â Settings.', 'rw-dealer-portal' ); ?>
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

	$filter_settings = rwdp_get_dealer_filter_settings();
	$active_fields   = $filter_settings['active_fields'];
	$filter_logic    = $filter_settings['filter_logic'];

	$allowed_sizes      = array_merge( [ 'full' ], array_keys( wp_get_registered_image_subsizes() ) );
	$raw_thumb_size     = sanitize_key( wp_unslash( $_POST['thumbnail_image_size'] ?? 'large' ) );
	$raw_logo_size      = sanitize_key( wp_unslash( $_POST['logo_image_size']      ?? 'large' ) );
	$thumbnail_img_size = in_array( $raw_thumb_size, $allowed_sizes, true ) ? $raw_thumb_size : 'large';
	$logo_img_size      = in_array( $raw_logo_size,  $allowed_sizes, true ) ? $raw_logo_size  : 'large';

	// --- Resolve active filter selections ---

	// acf_filters: JSON object of { field_key: selected_post_id } from JS.
	// Validate every key against the admin-configured active fields (security).
	$raw_acf_filters = json_decode( wp_unslash( $_POST['acf_filters'] ?? '{}' ), true );
	$acf_filters = [];  // [ field_key => post_id ]
	if ( is_array( $raw_acf_filters ) ) {
		foreach ( $raw_acf_filters as $key => $val ) {
			$key = sanitize_key( $key );
			if ( in_array( $key, $active_fields, true ) && absint( $val ) > 0 ) {
				$acf_filters[ $key ] = absint( $val );
			}
		}
	}

	$taxonomy_filter = absint( wp_unslash( $_POST['tax_type_id'] ?? 0 ) );

	// Locked type: resolve to { field, id } across all active fields,
	// with a fallback to the rw_dealer_type taxonomy if no ACF match is found.
	$locked_type_slug = sanitize_text_field( wp_unslash( $_POST['locked_type'] ?? '' ) );
	$locked_resolved  = false;
	if ( $locked_type_slug && ! empty( $active_fields ) ) {
		$all_options = [];
		foreach ( $active_fields as $fk ) {
			$all_options[ $fk ] = rwdp_get_relationship_filter_options( $fk );
		}
		$resolved = rwdp_resolve_locked_relationship_id( $locked_type_slug, $all_options );
		if ( $resolved['id'] && $resolved['field'] ) {
			$acf_filters[ $resolved['field'] ] = $resolved['id'];
			$locked_resolved = true;
		}
	}

	// Taxonomy fallback: if locked_type was not resolved via ACF fields, treat it as
	// an rw_dealer_type taxonomy term slug (the documented use-case in the widget).
	if ( $locked_type_slug && ! $locked_resolved && ! $taxonomy_filter ) {
		$term = ctype_digit( $locked_type_slug )
			? get_term( (int) $locked_type_slug, 'rw_dealer_type' )
			: get_term_by( 'slug', $locked_type_slug, 'rw_dealer_type' );
		if ( $term && ! is_wp_error( $term ) ) {
			$taxonomy_filter = $term->term_id;
		}
	}

	// --- Build query / queries ---

	$base_query = [
		'post_type'      => 'rw_dealer',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'orderby'        => 'title',
		'order'          => 'ASC',
		'fields'         => 'ids',
	];

	$has_acf_filter = ! empty( $acf_filters );
	$has_tax_filter = $taxonomy_filter > 0;

	if ( ! $has_acf_filter && ! $has_tax_filter ) {
		// No filters selected â return all dealers with valid addresses.
		$base_query['meta_query'] = [ [
			'key'     => '_rwdp_address_valid',
			'value'   => '1',
			'compare' => '=',
		] ];
		$dealer_ids = get_posts( $base_query );

	} elseif ( 'or' === $filter_logic ) {
		// OR: run one query per active filter, union all results.
		$union_ids = [];

		foreach ( $acf_filters as $field_key => $post_id ) {
			$q = $base_query;
			$q['meta_query'] = [
				'relation' => 'AND',
				[
					'key'     => '_rwdp_address_valid',
					'value'   => '1',
					'compare' => '=',
				],
				[
					'key'     => $field_key,
					'value'   => '"' . $post_id . '"',
					'compare' => 'LIKE',
				],
			];
			$union_ids = array_merge( $union_ids, get_posts( $q ) );
		}

		if ( $has_tax_filter ) {
			$q = $base_query;
			$q['meta_query'] = [ [
				'key'     => '_rwdp_address_valid',
				'value'   => '1',
				'compare' => '=',
			] ];
			$q['tax_query'] = [ [
				'taxonomy' => 'rw_dealer_type',
				'field'    => 'term_id',
				'terms'    => $taxonomy_filter,
			] ];
			$union_ids = array_merge( $union_ids, get_posts( $q ) );
		}

		$dealer_ids = array_values( array_unique( $union_ids ) );
		// Re-sort alphabetically by requerying the unioned IDs.
		if ( ! empty( $dealer_ids ) ) {
			$dealer_ids = get_posts( array_merge( $base_query, [
				'post__in'   => $dealer_ids,
				'meta_query' => [],  // already validated above
			] ) );
		}

	} else {
		// AND: single WP_Query, all conditions must match.
		$meta_clauses = [
			'relation' => 'AND',
			[
				'key'     => '_rwdp_address_valid',
				'value'   => '1',
				'compare' => '=',
			],
		];
		foreach ( $acf_filters as $field_key => $post_id ) {
			$meta_clauses[] = [
				'key'     => $field_key,
				'value'   => '"' . $post_id . '"',
				'compare' => 'LIKE',
			];
		}
		$q = $base_query;
		$q['meta_query'] = $meta_clauses;
		if ( $has_tax_filter ) {
			$q['tax_query'] = [ [
				'taxonomy' => 'rw_dealer_type',
				'field'    => 'term_id',
				'terms'    => $taxonomy_filter,
			] ];
		}
		$dealer_ids = get_posts( $q );
	}

	// --- Build response data ---

	$dealers = empty( $dealer_ids ) ? [] : get_posts( [
		'post_type'      => 'rw_dealer',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'post__in'       => $dealer_ids,
		'orderby'        => 'post__in',
	] );

	$data = [];
	foreach ( $dealers as $dealer ) {
		$lat      = (float) get_post_meta( $dealer->ID, '_rwdp_lat',          true );
		$lng      = (float) get_post_meta( $dealer->ID, '_rwdp_lng',          true );
		$logo_id  = get_post_meta( $dealer->ID, '_rwdp_logo_id', true );
		$feat_img = get_the_post_thumbnail_url( $dealer->ID, $thumbnail_img_size );
		$city     = get_post_meta( $dealer->ID, '_rwdp_city',    true );
		$state    = get_post_meta( $dealer->ID, '_rwdp_state',   true );
		$zip      = get_post_meta( $dealer->ID, '_rwdp_zip',     true );

		// Collect all ACF relationship IDs per active field.
		$type_ids = [];
		foreach ( $active_fields as $fk ) {
			$field_value = null;
			if ( function_exists( 'get_field' ) ) {
				$field_value = get_field( $fk, $dealer->ID, false );
				if ( empty( $field_value ) ) {
					$field_value = get_field( $fk, $dealer->ID, true );
				}
			}
			if ( empty( $field_value ) ) {
				$field_value = get_post_meta( $dealer->ID, $fk, true );
			}
			$type_ids = array_merge( $type_ids, rwdp_normalize_related_post_ids( $field_value ) );
		}
		$type_ids = array_values( array_unique( array_map( 'absint', $type_ids ) ) );

		$taxonomy_terms    = get_the_terms( $dealer->ID, 'rw_dealer_type' );
		$taxonomy_type_ids = ( $taxonomy_terms && ! is_wp_error( $taxonomy_terms ) )
			? array_map( 'absint', wp_list_pluck( $taxonomy_terms, 'term_id' ) )
			: [];

		$dealer_data = [
			'id'               => $dealer->ID,
			'title'            => $dealer->post_title,
			'lat'              => $lat,
			'lng'              => $lng,
			'phone'            => get_post_meta( $dealer->ID, '_rwdp_phone',        true ),
			'website'          => get_post_meta( $dealer->ID, '_rwdp_website',      true ),
			'email'            => get_post_meta( $dealer->ID, '_rwdp_public_email', true ),
			'address'          => get_post_meta( $dealer->ID, '_rwdp_address',      true ),
			'city'             => $city,
			'state'            => $state,
			'zip'              => $zip,
			'hours'            => get_post_meta( $dealer->ID, '_rwdp_hours',        true ),
			'logo_url'         => $logo_id ? wp_get_attachment_image_url( $logo_id, $logo_img_size ) : '',
			'feat_img'         => $feat_img ?: '',
			'permalink'        => get_permalink( $dealer->ID ),
			'type_ids'         => $type_ids,
			'taxonomy_type_ids' => $taxonomy_type_ids,
			'has_contact_email' => ! empty( get_post_meta( $dealer->ID, '_rwdp_contact_emails', true ) ),
		];

		$dealer_data = apply_filters( 'rwdp_ajax_dealer_data', $dealer_data, $dealer );

		$data[] = $dealer_data;
	}

	wp_send_json_success( [ 'dealers' => $data ] );
}
