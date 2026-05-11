<?php
if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'admin_menu', 'rwdp_register_dealer_import_page' );
add_action( 'admin_footer-edit.php', 'rwdp_add_dealer_import_header_button' );
add_action( 'admin_post_rwdp_import_dealers', 'rwdp_handle_dealer_csv_import' );

/**
 * Register a hidden admin page for dealer CSV imports.
 */
function rwdp_register_dealer_import_page() {
	add_submenu_page(
		null,
		__( 'Import Dealers', 'rw-dealer-portal' ),
		__( 'Import Dealers', 'rw-dealer-portal' ),
		'edit_rw_dealers',
		'rwdp-import-dealers',
		'rwdp_render_import_dealers_page'
	);
}

/**
 * Add an "Import CSV" button next to the "Add New Dealer" page title action.
 */
function rwdp_add_dealer_import_header_button() {
	if ( ! current_user_can( 'edit_rw_dealers' ) ) {
		return;
	}

	$screen = get_current_screen();
	if ( ! $screen || 'edit-rw_dealer' !== $screen->id ) {
		return;
	}

	$import_url = esc_url( admin_url( 'admin.php?page=rwdp-import-dealers' ) );
	$label      = esc_html__( 'Import CSV', 'rw-dealer-portal' );
	?>
	<script>
	(function() {
		var addNewButton = document.querySelector('.wrap .page-title-action');
		if (!addNewButton || document.getElementById('rwdp-import-csv-button')) {
			return;
		}

		var importButton = document.createElement('a');
		importButton.id = 'rwdp-import-csv-button';
		importButton.className = 'page-title-action';
		importButton.href = <?php echo wp_json_encode( $import_url ); ?>;
		importButton.textContent = <?php echo wp_json_encode( $label ); ?>;

		addNewButton.insertAdjacentElement('afterend', importButton);
	})();
	</script>
	<?php
}

/**
 * Render the dealer import admin page.
 */
function rwdp_render_import_dealers_page() {
	if ( ! current_user_can( 'edit_rw_dealers' ) ) {
		wp_die( esc_html__( 'You do not have permission to import dealers.', 'rw-dealer-portal' ) );
	}

	$results = null;
	if ( isset( $_GET['imported'] ) && '1' === sanitize_text_field( wp_unslash( $_GET['imported'] ) ) ) {
		$transient_key = 'rwdp_import_results_' . get_current_user_id();
		$results       = get_transient( $transient_key );
		delete_transient( $transient_key );
	}
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Import Dealers from CSV', 'rw-dealer-portal' ); ?></h1>

		<p>
			<?php esc_html_e( 'Upload a CSV file to create dealer records. Duplicate dealer titles are skipped.', 'rw-dealer-portal' ); ?>
		</p>

		<?php if ( is_array( $results ) ) : ?>
			<div class="notice notice-info"><p>
				<?php
				echo esc_html(
					sprintf(
						/* translators: 1: processed rows, 2: imported count, 3: skipped count, 4: error count, 5: warning count */
						__( 'Processed: %1$d. Imported: %2$d. Skipped: %3$d. Errors: %4$d. Warnings: %5$d.', 'rw-dealer-portal' ),
						absint( $results['processed'] ?? 0 ),
						absint( $results['imported'] ?? 0 ),
						absint( $results['skipped'] ?? 0 ),
						absint( $results['errors'] ?? 0 ),
						absint( $results['warnings'] ?? 0 )
					)
				);
				?>
			</p></div>

			<?php if ( ! empty( $results['error_rows'] ) ) : ?>
				<h2><?php esc_html_e( 'Rows with Errors', 'rw-dealer-portal' ); ?></h2>
				<table class="widefat striped" style="max-width:1000px;">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Row', 'rw-dealer-portal' ); ?></th>
							<th><?php esc_html_e( 'Issue', 'rw-dealer-portal' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $results['error_rows'] as $error_row ) : ?>
							<tr>
								<td><?php echo absint( $error_row['row'] ?? 0 ); ?></td>
								<td><?php echo esc_html( $error_row['message'] ?? '' ); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>

			<?php if ( ! empty( $results['skipped_rows'] ) ) : ?>
				<h2><?php esc_html_e( 'Skipped Rows', 'rw-dealer-portal' ); ?></h2>
				<table class="widefat striped" style="max-width:1000px;">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Row', 'rw-dealer-portal' ); ?></th>
							<th><?php esc_html_e( 'Reason', 'rw-dealer-portal' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $results['skipped_rows'] as $skipped_row ) : ?>
							<tr>
								<td><?php echo absint( $skipped_row['row'] ?? 0 ); ?></td>
								<td><?php echo esc_html( $skipped_row['message'] ?? '' ); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>

			<?php if ( ! empty( $results['warning_rows'] ) ) : ?>
				<h2><?php esc_html_e( 'Imported with Warnings', 'rw-dealer-portal' ); ?></h2>
				<table class="widefat striped" style="max-width:1000px;">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Row', 'rw-dealer-portal' ); ?></th>
							<th><?php esc_html_e( 'Warning', 'rw-dealer-portal' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $results['warning_rows'] as $warning_row ) : ?>
							<tr>
								<td><?php echo absint( $warning_row['row'] ?? 0 ); ?></td>
								<td><?php echo esc_html( $warning_row['message'] ?? '' ); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		<?php endif; ?>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data" style="margin-top:16px;">
			<input type="hidden" name="action" value="rwdp_import_dealers" />
			<?php wp_nonce_field( 'rwdp_import_dealers', 'rwdp_import_dealers_nonce' ); ?>

			<table class="form-table" role="presentation">
				<tr>
					<th scope="row">
						<label for="rwdp_dealer_csv_file"><?php esc_html_e( 'CSV File', 'rw-dealer-portal' ); ?></label>
					</th>
					<td>
						<input type="file" id="rwdp_dealer_csv_file" name="rwdp_dealer_csv_file" accept=".csv,text/csv" required />
						<p class="description">
							<?php esc_html_e( 'Required column: title. Optional columns: address, city, state, zip, phone, public_email, contact_emails, hours, website, dealer_type, featured_image_url, logo_url, lat, lng.', 'rw-dealer-portal' ); ?>
						</p>
					</td>
				</tr>
			</table>

			<?php submit_button( __( 'Import Dealers CSV', 'rw-dealer-portal' ) ); ?>
		</form>
	</div>
	<?php
}

/**
 * Handle dealer CSV upload and import.
 */
function rwdp_handle_dealer_csv_import() {
	if ( ! current_user_can( 'edit_rw_dealers' ) ) {
		wp_die( esc_html__( 'You do not have permission to import dealers.', 'rw-dealer-portal' ) );
	}

	$nonce = sanitize_text_field( wp_unslash( $_POST['rwdp_import_dealers_nonce'] ?? '' ) );
	if ( ! wp_verify_nonce( $nonce, 'rwdp_import_dealers' ) ) {
		wp_die( esc_html__( 'Security check failed.', 'rw-dealer-portal' ) );
	}

	if ( empty( $_FILES['rwdp_dealer_csv_file'] ) || ! is_array( $_FILES['rwdp_dealer_csv_file'] ) ) {
		rwdp_import_redirect_with_results( [
			'processed'    => 0,
			'imported'     => 0,
			'skipped'      => 0,
			'errors'       => 1,
			'warnings'     => 0,
			'error_rows'   => [ [ 'row' => 0, 'message' => __( 'No CSV file was uploaded.', 'rw-dealer-portal' ) ] ],
			'skipped_rows' => [],
			'warning_rows' => [],
		] );
	}

	$file = $_FILES['rwdp_dealer_csv_file'];

	if ( ! empty( $file['error'] ) ) {
		rwdp_import_redirect_with_results( [
			'processed'    => 0,
			'imported'     => 0,
			'skipped'      => 0,
			'errors'       => 1,
			'warnings'     => 0,
			'error_rows'   => [ [ 'row' => 0, 'message' => sprintf( __( 'Upload failed with error code %d.', 'rw-dealer-portal' ), absint( $file['error'] ) ) ] ],
			'skipped_rows' => [],
			'warning_rows' => [],
		] );
	}

	$filename = sanitize_file_name( $file['name'] ?? '' );
	$ext      = strtolower( pathinfo( $filename, PATHINFO_EXTENSION ) );

	if ( 'csv' !== $ext ) {
		rwdp_import_redirect_with_results( [
			'processed'    => 0,
			'imported'     => 0,
			'skipped'      => 0,
			'errors'       => 1,
			'warnings'     => 0,
			'error_rows'   => [ [ 'row' => 0, 'message' => __( 'Only .csv files are allowed.', 'rw-dealer-portal' ) ] ],
			'skipped_rows' => [],
			'warning_rows' => [],
		] );
	}

	$fh = fopen( $file['tmp_name'], 'r' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
	if ( false === $fh ) {
		rwdp_import_redirect_with_results( [
			'processed'    => 0,
			'imported'     => 0,
			'skipped'      => 0,
			'errors'       => 1,
			'warnings'     => 0,
			'error_rows'   => [ [ 'row' => 0, 'message' => __( 'Unable to read the uploaded CSV file.', 'rw-dealer-portal' ) ] ],
			'skipped_rows' => [],
			'warning_rows' => [],
		] );
	}

	$header_row = fgetcsv( $fh );
	if ( false === $header_row || ! is_array( $header_row ) ) {
		fclose( $fh ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
		rwdp_import_redirect_with_results( [
			'processed'    => 0,
			'imported'     => 0,
			'skipped'      => 0,
			'errors'       => 1,
			'warnings'     => 0,
			'error_rows'   => [ [ 'row' => 0, 'message' => __( 'The CSV appears to be empty.', 'rw-dealer-portal' ) ] ],
			'skipped_rows' => [],
			'warning_rows' => [],
		] );
	}

	$headers = array_map( 'rwdp_normalize_import_header', $header_row );

	if ( ! in_array( 'title', $headers, true ) ) {
		fclose( $fh ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
		rwdp_import_redirect_with_results( [
			'processed'    => 0,
			'imported'     => 0,
			'skipped'      => 0,
			'errors'       => 1,
			'warnings'     => 0,
			'error_rows'   => [ [ 'row' => 0, 'message' => __( 'The CSV must contain a title column.', 'rw-dealer-portal' ) ] ],
			'skipped_rows' => [],
			'warning_rows' => [],
		] );
	}

	if ( ! function_exists( 'media_sideload_image' ) ) {
		require_once ABSPATH . 'wp-admin/includes/media.php';
	}
	if ( ! function_exists( 'wp_handle_sideload' ) ) {
		require_once ABSPATH . 'wp-admin/includes/file.php';
	}
	if ( ! function_exists( 'wp_generate_attachment_metadata' ) ) {
		require_once ABSPATH . 'wp-admin/includes/image.php';
	}

	$results = [
		'processed'    => 0,
		'imported'     => 0,
		'skipped'      => 0,
		'errors'       => 0,
		'warnings'     => 0,
		'error_rows'   => [],
		'skipped_rows' => [],
		'warning_rows' => [],
	];

	$row_number = 1;
	while ( ( $row = fgetcsv( $fh ) ) !== false ) {
		$row_number++;

		if ( ! is_array( $row ) ) {
			continue;
		}

		if ( rwdp_import_row_is_empty( $row ) ) {
			continue;
		}

		$row_data = rwdp_map_import_row( $headers, $row );
		$results['processed']++;

		$title = sanitize_text_field( $row_data['title'] ?? '' );
		if ( '' === $title ) {
			$results['errors']++;
			$results['error_rows'][] = [
				'row'     => $row_number,
				'message' => __( 'Missing required title value.', 'rw-dealer-portal' ),
			];
			continue;
		}

		$existing_id = rwdp_find_existing_dealer_by_title( $title );
		if ( $existing_id > 0 ) {
			$results['skipped']++;
			$results['skipped_rows'][] = [
				'row'     => $row_number,
				'message' => sprintf(
					/* translators: %s: dealer title */
					__( 'Dealer "%s" already exists (matched by title).', 'rw-dealer-portal' ),
					$title
				),
			];
			continue;
		}

		$post_id = wp_insert_post( [
			'post_type'   => 'rw_dealer',
			'post_title'  => $title,
			'post_status' => 'publish',
		] );

		if ( is_wp_error( $post_id ) || ! $post_id ) {
			$results['errors']++;
			$results['error_rows'][] = [
				'row'     => $row_number,
				'message' => is_wp_error( $post_id ) ? $post_id->get_error_message() : __( 'Unknown insert failure.', 'rw-dealer-portal' ),
			];
			continue;
		}

		rwdp_apply_imported_dealer_meta( $post_id, $row_data );
		rwdp_apply_imported_dealer_type( $post_id, $row_data );

		$row_warnings = [];

		$featured_image_url = esc_url_raw( $row_data['featured_image_url'] ?? '' );
		if ( '' !== $featured_image_url ) {
			$attachment_id = rwdp_import_sideload_image_id( $featured_image_url, $post_id );
			if ( is_wp_error( $attachment_id ) ) {
				$row_warnings[] = sprintf(
					/* translators: 1: dealer title, 2: error message */
					__( 'Featured image failed for "%1$s": %2$s', 'rw-dealer-portal' ),
					$title,
					$attachment_id->get_error_message()
				);
			} elseif ( $attachment_id > 0 ) {
				set_post_thumbnail( $post_id, $attachment_id );
			}
		}

		$logo_url = esc_url_raw( $row_data['logo_url'] ?? '' );
		if ( '' !== $logo_url ) {
			$logo_id = rwdp_import_sideload_image_id( $logo_url, $post_id );
			if ( is_wp_error( $logo_id ) ) {
				$row_warnings[] = sprintf(
					/* translators: 1: dealer title, 2: error message */
					__( 'Logo image failed for "%1$s": %2$s', 'rw-dealer-portal' ),
					$title,
					$logo_id->get_error_message()
				);
			} elseif ( $logo_id > 0 ) {
				update_post_meta( $post_id, '_rwdp_logo_id', $logo_id );
			}
		}

		rwdp_apply_imported_geocode( $post_id, $row_data );

		if ( ! empty( $row_warnings ) ) {
			$results['warnings']++;
			$results['warning_rows'][] = [
				'row'     => $row_number,
				'message' => implode( ' ', array_map( 'sanitize_text_field', $row_warnings ) ),
			];
		}

		$results['imported']++;
	}

	fclose( $fh ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose

	rwdp_import_redirect_with_results( $results );
}

/**
 * Normalize import headers so CSV labels can be user-friendly.
 */
function rwdp_normalize_import_header( $value ) {
	$value = strtolower( trim( (string) $value ) );
	$value = preg_replace( '/[^a-z0-9_]+/', '_', $value );
	$value = trim( (string) $value, '_' );

	return (string) $value;
}

/**
 * Map one CSV row to keyed data from normalized headers.
 */
function rwdp_map_import_row( $headers, $row ) {
	$data = [];
	foreach ( $headers as $index => $header ) {
		if ( '' === $header ) {
			continue;
		}
		$data[ $header ] = isset( $row[ $index ] ) ? trim( (string) $row[ $index ] ) : '';
	}

	$aliases = [
		'email'          => 'public_email',
		'postal_code'    => 'zip',
		'zipcode'        => 'zip',
		'contact_email'  => 'contact_emails',
		'featured_image' => 'featured_image_url',
		'logo'           => 'logo_url',
		'dealer_types'   => 'dealer_type',
	];

	foreach ( $aliases as $alias_key => $canonical_key ) {
		if ( isset( $data[ $alias_key ] ) && ! isset( $data[ $canonical_key ] ) ) {
			$data[ $canonical_key ] = $data[ $alias_key ];
		}
	}

	return $data;
}

/**
 * Determine whether a parsed CSV row is effectively empty.
 */
function rwdp_import_row_is_empty( $row ) {
	foreach ( $row as $cell ) {
		if ( '' !== trim( (string) $cell ) ) {
			return false;
		}
	}

	return true;
}

/**
 * Find an existing dealer by exact post title.
 */
function rwdp_find_existing_dealer_by_title( $title ) {
	global $wpdb;

	return (int) $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->prepare(
			"SELECT ID FROM {$wpdb->posts} WHERE post_type = %s AND post_status IN ('publish','pending','draft','private','future') AND post_title = %s LIMIT 1",
			'rw_dealer',
			$title
		)
	);
}

/**
 * Save dealer meta fields from CSV data.
 */
function rwdp_apply_imported_dealer_meta( $post_id, $row_data ) {
	$meta_map = [
		'_rwdp_address'        => 'address',
		'_rwdp_city'           => 'city',
		'_rwdp_state'          => 'state',
		'_rwdp_zip'            => 'zip',
		'_rwdp_phone'          => 'phone',
		'_rwdp_public_email'   => 'public_email',
		'_rwdp_contact_emails' => 'contact_emails',
		'_rwdp_hours'          => 'hours',
		'_rwdp_website'        => 'website',
	];

	foreach ( $meta_map as $meta_key => $csv_key ) {
		if ( ! array_key_exists( $csv_key, $row_data ) ) {
			continue;
		}

		$raw = trim( (string) $row_data[ $csv_key ] );
		if ( '_rwdp_hours' === $meta_key ) {
			$value = sanitize_textarea_field( $raw );
		} elseif ( '_rwdp_public_email' === $meta_key ) {
			$value = sanitize_email( $raw );
		} elseif ( '_rwdp_website' === $meta_key ) {
			if ( '' !== $raw && ! preg_match( '#^[a-z][a-z0-9+\-.]*://#i', $raw ) ) {
				$raw = 'https://' . $raw;
			}
			$value = esc_url_raw( $raw );
		} else {
			$value = sanitize_text_field( $raw );
		}

		update_post_meta( $post_id, $meta_key, $value );
	}

	if ( isset( $row_data['lat'] ) && isset( $row_data['lng'] ) ) {
		$lat = trim( (string) $row_data['lat'] );
		$lng = trim( (string) $row_data['lng'] );

		if ( '' !== $lat && '' !== $lng ) {
			update_post_meta( $post_id, '_rwdp_lat', sanitize_text_field( $lat ) );
			update_post_meta( $post_id, '_rwdp_lng', sanitize_text_field( $lng ) );
			update_post_meta( $post_id, '_rwdp_address_valid', '1' );
		}
	}
}

/**
 * Assign dealer type taxonomy terms from CSV.
 */
function rwdp_apply_imported_dealer_type( $post_id, $row_data ) {
	if ( empty( $row_data['dealer_type'] ) ) {
		return;
	}

	$terms = preg_split( '/[|,]/', (string) $row_data['dealer_type'] );
	$terms = array_values( array_filter( array_map( 'trim', (array) $terms ) ) );

	if ( ! empty( $terms ) ) {
		wp_set_object_terms( $post_id, $terms, 'rw_dealer_type' );
	}
}

/**
 * Geocode imported dealer if lat/lng were not explicitly provided.
 */
function rwdp_apply_imported_geocode( $post_id, $row_data ) {
	$has_lat_lng = ! empty( $row_data['lat'] ) && ! empty( $row_data['lng'] );
	if ( $has_lat_lng ) {
		return;
	}

	$address_parts = [
		sanitize_text_field( $row_data['address'] ?? '' ),
		sanitize_text_field( $row_data['city'] ?? '' ),
		sanitize_text_field( $row_data['state'] ?? '' ),
		sanitize_text_field( $row_data['zip'] ?? '' ),
	];
	$address_parts = array_values( array_filter( $address_parts ) );

	if ( empty( $address_parts ) ) {
		return;
	}

	$full_address = implode( ', ', $address_parts );
	rwdp_geocode_and_store( $post_id, $full_address );
}

/**
 * Sideload one remote image and return attachment ID.
 */
function rwdp_import_sideload_image_id( $url, $post_id ) {
	if ( '' === trim( (string) $url ) ) {
		return 0;
	}

	$attachment_id = media_sideload_image( $url, $post_id, null, 'id' );
	if ( is_wp_error( $attachment_id ) ) {
		return $attachment_id;
	}

	return absint( $attachment_id );
}

/**
 * Persist import results and redirect back to the import page.
 */
function rwdp_import_redirect_with_results( $results ) {
	$transient_key = 'rwdp_import_results_' . get_current_user_id();
	set_transient( $transient_key, $results, 60 );

	wp_safe_redirect( admin_url( 'admin.php?page=rwdp-import-dealers&imported=1' ) );
	exit;
}
