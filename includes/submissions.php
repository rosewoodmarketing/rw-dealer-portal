<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// Fire CSV export on admin_init before any HTML output is sent.
add_action( 'admin_init', 'rwdp_maybe_export_submissions_csv' );
function rwdp_maybe_export_submissions_csv() {
	if ( ! isset( $_GET['rwdp_export_csv'] ) ) {
		return;
	}
	if ( ! current_user_can( 'view_rwdp_submissions' ) ) {
		return;
	}
	if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ?? '' ) ), 'rwdp_export_submissions' ) ) {
		return;
	}
	rwdp_export_submissions_csv();
	exit;
}

add_shortcode( 'rwdp_my_requests', 'rwdp_my_requests_shortcode' );

function rwdp_my_requests_shortcode() {
	if ( ! rwdp_current_user_has_portal_access() ) {
		return rwdp_portal_login_prompt();
	}

	wp_enqueue_style( 'rwdp-portal', RWDP_PLUGIN_URL . 'assets/css/portal.css', [ 'dashicons' ], RWDP_VERSION );

	$user       = wp_get_current_user();
	$dealer_ids = array_values( array_filter( array_map( 'absint', (array) get_user_meta( $user->ID, '_rwdp_dealer_ids', true ) ) ) );

	if ( empty( $dealer_ids ) ) {
		return '<div class="rwdp-portal"><p>' . esc_html__( 'You are not linked to any dealer account. Contact your administrator.', 'rw-dealer-portal' ) . '</p></div>';
	}

	$settings = get_option( 'rwdp_settings', [] );
	$form_id  = absint( $settings['contact_form_id'] ?? 0 );

	if ( ! $form_id ) {
		return '<div class="rwdp-portal"><p>' . esc_html__( 'Contact form not configured. Contact your site administrator.', 'rw-dealer-portal' ) . '</p></div>';
	}

	global $wpdb;
	$paged    = max( 1, absint( get_query_var( 'paged' ) ?: ( $_GET['rwdp_page'] ?? 1 ) ) );
	$per_page = 20;
	$offset   = ( $paged - 1 ) * $per_page;
	$table_s  = $wpdb->prefix . 'fluentform_submissions';
	$table_m  = $wpdb->prefix . 'fluentform_submission_meta';

	$placeholders = implode( ',', array_fill( 0, count( $dealer_ids ), '%d' ) );

	// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$rows = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT s.id, s.form_id, s.response, s.created_at, CAST(m.value AS UNSIGNED) AS dealer_id
			 FROM {$table_s} s
			 INNER JOIN {$table_m} m ON m.response_id = s.id AND m.meta_key = 'rwdp_dealer_id'
			 WHERE s.form_id = %d AND CAST(m.value AS UNSIGNED) IN ({$placeholders})
			 ORDER BY s.created_at DESC
			 LIMIT %d OFFSET %d",
			array_merge( [ $form_id ], $dealer_ids, [ $per_page, $offset ] )
		)
	);

	$total       = (int) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT COUNT(*) FROM {$table_s} s
			 INNER JOIN {$table_m} m ON m.response_id = s.id AND m.meta_key = 'rwdp_dealer_id'
			 WHERE s.form_id = %d AND CAST(m.value AS UNSIGNED) IN ({$placeholders})",
			array_merge( [ $form_id ], $dealer_ids )
		)
	);
	// phpcs:enable

	$total_pages = (int) ceil( $total / $per_page );

	ob_start();
	?>
	<div class="rwdp-portal rwdp-requests">
		<h2><?php esc_html_e( 'Contact Requests', 'rw-dealer-portal' ); ?></h2>

		<?php if ( empty( $rows ) ) : ?>
			<p><?php esc_html_e( 'No contact requests yet.', 'rw-dealer-portal' ); ?></p>
		<?php else : ?>

			<table class="rwdp-table rwdp-requests-table">
				<thead>
					<tr>
						<th scope="col"><?php esc_html_e( 'Date', 'rw-dealer-portal' ); ?></th>
						<th scope="col"><?php esc_html_e( 'From', 'rw-dealer-portal' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Email', 'rw-dealer-portal' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Phone', 'rw-dealer-portal' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Message', 'rw-dealer-portal' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Dealer', 'rw-dealer-portal' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $rows as $row ) :
						$data       = json_decode( $row->response, true ) ?: [];
						$dealer_obj = $row->dealer_id ? get_post( (int) $row->dealer_id ) : null;
					?>
					<tr>
						<td><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $row->created_at ) ) ); ?></td>
						<td><?php echo esc_html( rwdp_ff_extract( $data, [ 'name', 'full_name', 'first_name', 'your_name', 'names' ] ) ?: '—' ); ?></td>
						<td>
							<?php
							$cust_email = sanitize_email( rwdp_ff_extract( $data, [ 'email', 'your_email', 'email_address' ] ) );
							echo $cust_email ? '<a href="mailto:' . esc_attr( $cust_email ) . '">' . esc_html( $cust_email ) . '</a>' : '—';
							?>
						</td>
						<td><?php echo esc_html( rwdp_ff_extract( $data, [ 'phone', 'phone_number', 'telephone', 'your_phone' ] ) ?: '—' ); ?></td>
						<td class="rwdp-requests-table__message"><?php echo wp_kses_post( wpautop( rwdp_ff_extract( $data, [ 'message', 'your_message', 'comment', 'inquiry' ] ) ?: '—' ) ); ?></td>
						<td><?php echo $dealer_obj ? esc_html( $dealer_obj->post_title ) : '—'; ?></td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>

			<?php if ( $total_pages > 1 ) :
				$base_url = add_query_arg( 'rwdp_page', '%#%', get_permalink() );
			?>
			<div class="rwdp-pagination">
				<?php for ( $i = 1; $i <= $total_pages; $i++ ) : ?>
					<a href="<?php echo esc_url( str_replace( '%25%23%25', $i, $base_url ) ); ?>"
					   class="rwdp-page-btn<?php echo $i === $paged ? ' active' : ''; ?>">
						<?php echo absint( $i ); ?>
					</a>
				<?php endfor; ?>
			</div>
			<?php endif; ?>

		<?php endif; ?>
	</div>
	<?php
	return ob_get_clean();
}

/**
 * Admin submissions page (called from admin-menu.php).
 */
function rwdp_admin_submissions_page() {
	if ( ! current_user_can( 'view_rwdp_submissions' ) ) {
		wp_die( esc_html__( 'Sorry, you do not have permission to access this page.', 'rw-dealer-portal' ) );
	}

	$settings      = get_option( 'rwdp_settings', [] );
	$form_id       = absint( $settings['contact_form_id'] ?? 0 );
	$dealer_filter = absint( $_GET['rwdp_dealer'] ?? 0 );
	$date_from     = sanitize_text_field( wp_unslash( $_GET['rwdp_date_from'] ?? '' ) );
	$date_to       = sanitize_text_field( wp_unslash( $_GET['rwdp_date_to']   ?? '' ) );
	$paged         = max( 1, absint( $_GET['paged'] ?? 1 ) );
	$per_page      = 30;
	$offset        = ( $paged - 1 ) * $per_page;

	$dealers     = get_posts( [ 'post_type' => 'rw_dealer', 'posts_per_page' => -1, 'orderby' => 'title', 'order' => 'ASC' ] );
	$rows        = [];
	$total_pages = 0;

	if ( $form_id ) {
		global $wpdb;
		$table_s = $wpdb->prefix . 'fluentform_submissions';
		$table_m = $wpdb->prefix . 'fluentform_submission_meta';

		// Each clause is individually prepared; table names come from $wpdb->prefix (safe).
		$where_clauses = [ $wpdb->prepare( 's.form_id = %d', $form_id ) ];
		if ( $dealer_filter ) {
			$where_clauses[] = $wpdb->prepare( 'CAST(m.value AS UNSIGNED) = %d', $dealer_filter );
		}
		if ( $date_from ) {
			$where_clauses[] = $wpdb->prepare( 'DATE(s.created_at) >= %s', $date_from );
		}
		if ( $date_to ) {
			$where_clauses[] = $wpdb->prepare( 'DATE(s.created_at) <= %s', $date_to );
		}
		$where_sql = 'WHERE ' . implode( ' AND ', $where_clauses );

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$total = (int) $wpdb->get_var(
			"SELECT COUNT(*) FROM {$table_s} s
			 INNER JOIN {$table_m} m ON m.response_id = s.id AND m.meta_key = 'rwdp_dealer_id'
			 {$where_sql}"
		);
		$rows  = $wpdb->get_results(
			"SELECT s.id, s.form_id, s.response, s.created_at, CAST(m.value AS UNSIGNED) AS dealer_id
			 FROM {$table_s} s
			 INNER JOIN {$table_m} m ON m.response_id = s.id AND m.meta_key = 'rwdp_dealer_id'
			 {$where_sql}
			 ORDER BY s.created_at DESC
			 LIMIT {$per_page} OFFSET {$offset}"
		);
		// phpcs:enable
		$total_pages = (int) ceil( $total / $per_page );
	}
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Contact Submissions', 'rw-dealer-portal' ); ?></h1>

		<?php if ( ! $form_id ) : ?>
			<div class="notice notice-warning"><p>
				<?php esc_html_e( 'No contact form is configured. Please set one in ', 'rw-dealer-portal' ); ?>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=rwdp-settings' ) ); ?>"><?php esc_html_e( 'RW Dealer Portal → Settings', 'rw-dealer-portal' ); ?></a>.
			</p></div>
		<?php endif; ?>

		<form method="get" class="rwdp-filters">
			<input type="hidden" name="page" value="rwdp-submissions" />

			<select name="rwdp_dealer">
				<option value=""><?php esc_html_e( 'All Dealers', 'rw-dealer-portal' ); ?></option>
				<?php foreach ( $dealers as $d ) : ?>
					<option value="<?php echo absint( $d->ID ); ?>" <?php selected( $dealer_filter, $d->ID ); ?>>
						<?php echo esc_html( $d->post_title ); ?>
					</option>
				<?php endforeach; ?>
			</select>

			<input type="date" name="rwdp_date_from" value="<?php echo esc_attr( $date_from ); ?>" placeholder="<?php esc_attr_e( 'From', 'rw-dealer-portal' ); ?>" />
			<input type="date" name="rwdp_date_to"   value="<?php echo esc_attr( $date_to ); ?>"   placeholder="<?php esc_attr_e( 'To', 'rw-dealer-portal' ); ?>" />
			<button type="submit" class="button"><?php esc_html_e( 'Filter', 'rw-dealer-portal' ); ?></button>

			<a href="<?php echo esc_url( wp_nonce_url(
				add_query_arg( [
					'page'            => 'rwdp-submissions',
					'rwdp_export_csv' => '1',
					'rwdp_dealer'     => $dealer_filter,
					'rwdp_date_from'  => $date_from,
					'rwdp_date_to'    => $date_to,
				], admin_url( 'admin.php' ) ),
				'rwdp_export_submissions'
			) ); ?>" class="button button-secondary"><?php esc_html_e( 'Export CSV', 'rw-dealer-portal' ); ?></a>
		</form>

		<?php if ( $form_id && empty( $rows ) ) : ?>
			<p><?php esc_html_e( 'No submissions found.', 'rw-dealer-portal' ); ?></p>
		<?php elseif ( ! empty( $rows ) ) : ?>
			<table class="wp-list-table widefat striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Date', 'rw-dealer-portal' ); ?></th>
						<th><?php esc_html_e( 'Name', 'rw-dealer-portal' ); ?></th>
						<th><?php esc_html_e( 'Email', 'rw-dealer-portal' ); ?></th>
						<th><?php esc_html_e( 'Phone', 'rw-dealer-portal' ); ?></th>
						<th><?php esc_html_e( 'Message', 'rw-dealer-portal' ); ?></th>
						<th><?php esc_html_e( 'Dealer', 'rw-dealer-portal' ); ?></th>
						<th><?php esc_html_e( 'Details', 'rw-dealer-portal' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $rows as $row ) :
						$data       = json_decode( $row->response, true ) ?: [];
						$dealer_obj = $row->dealer_id ? get_post( (int) $row->dealer_id ) : null;
						$entry_url  = admin_url( 'admin.php?page=fluent_forms&route=entries&form_id=' . absint( $row->form_id ) . '#/entries/' . absint( $row->id ) );
					?>
					<tr>
						<td><?php echo esc_html( date_i18n( 'Y-m-d H:i', strtotime( $row->created_at ) ) ); ?></td>
						<td><?php echo esc_html( rwdp_ff_extract( $data, [ 'name', 'full_name', 'first_name', 'your_name', 'names' ] ) ?: '—' ); ?></td>
						<td><?php
							$ce = sanitize_email( rwdp_ff_extract( $data, [ 'email', 'your_email', 'email_address' ] ) );
							echo $ce ? '<a href="mailto:' . esc_attr( $ce ) . '">' . esc_html( $ce ) . '</a>' : '—';
						?></td>
						<td><?php echo esc_html( rwdp_ff_extract( $data, [ 'phone', 'phone_number', 'telephone', 'your_phone' ] ) ?: '—' ); ?></td>
						<td><?php echo wp_kses_post( wpautop( rwdp_ff_extract( $data, [ 'message', 'your_message', 'comment', 'inquiry' ] ) ?: '—' ) ); ?></td>
						<td><?php echo $dealer_obj ? esc_html( $dealer_obj->post_title ) : '—'; ?></td>
						<td><a href="<?php echo esc_url( $entry_url ); ?>" class="button button-small" target="_blank" rel="noopener"><?php esc_html_e( 'View Entry', 'rw-dealer-portal' ); ?></a></td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>

			<div class="tablenav bottom">
				<div class="tablenav-pages">
					<?php
					echo wp_kses_post( paginate_links( [
						'base'      => add_query_arg( 'paged', '%#%' ),
						'format'    => '',
						'current'   => $paged,
						'total'     => $total_pages,
						'prev_text' => '&laquo;',
						'next_text' => '&raquo;',
					] ) ?? '' );
					?>
				</div>
			</div>
		<?php endif; ?>
	</div>
	<?php
}

/**
 * Stream submissions as CSV.
 */
function rwdp_export_submissions_csv() {
	if ( ! current_user_can( 'view_rwdp_submissions' ) ) {
		wp_die( esc_html__( 'Permission denied.', 'rw-dealer-portal' ) );
	}

	$settings      = get_option( 'rwdp_settings', [] );
	$form_id       = absint( $settings['contact_form_id'] ?? 0 );
	$dealer_filter = absint( $_GET['rwdp_dealer'] ?? 0 );
	$date_from     = sanitize_text_field( wp_unslash( $_GET['rwdp_date_from'] ?? '' ) );
	$date_to       = sanitize_text_field( wp_unslash( $_GET['rwdp_date_to']   ?? '' ) );

	$rows = [];

	if ( $form_id ) {
		global $wpdb;
		$table_s = $wpdb->prefix . 'fluentform_submissions';
		$table_m = $wpdb->prefix . 'fluentform_submission_meta';

		$where_clauses = [ $wpdb->prepare( 's.form_id = %d', $form_id ) ];
		if ( $dealer_filter ) {
			$where_clauses[] = $wpdb->prepare( 'CAST(m.value AS UNSIGNED) = %d', $dealer_filter );
		}
		if ( $date_from ) {
			$where_clauses[] = $wpdb->prepare( 'DATE(s.created_at) >= %s', $date_from );
		}
		if ( $date_to ) {
			$where_clauses[] = $wpdb->prepare( 'DATE(s.created_at) <= %s', $date_to );
		}
		$where_sql = 'WHERE ' . implode( ' AND ', $where_clauses );

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$rows = $wpdb->get_results(
			"SELECT s.id, s.form_id, s.response, s.created_at, CAST(m.value AS UNSIGNED) AS dealer_id
			 FROM {$table_s} s
			 INNER JOIN {$table_m} m ON m.response_id = s.id AND m.meta_key = 'rwdp_dealer_id'
			 {$where_sql}
			 ORDER BY s.created_at DESC"
		);
		// phpcs:enable
	}

	header( 'Content-Type: text/csv; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename="submissions-' . gmdate( 'Y-m-d' ) . '.csv"' );
	header( 'Cache-Control: no-cache, no-store, must-revalidate' );

	$out = fopen( 'php://output', 'w' );
	fputcsv( $out, [ 'Date', 'Name', 'Email', 'Phone', 'Message', 'Dealer', 'Entry ID', 'Entry URL' ] );

	foreach ( $rows as $row ) {
		$data       = json_decode( $row->response, true ) ?: [];
		$dealer_obj = $row->dealer_id ? get_post( (int) $row->dealer_id ) : null;
		$entry_url  = admin_url( 'admin.php?page=fluent_forms&route=entries&form_id=' . absint( $row->form_id ) . '#/entries/' . absint( $row->id ) );
		fputcsv( $out, [
			date_i18n( 'Y-m-d H:i', strtotime( $row->created_at ) ),
			rwdp_ff_extract( $data, [ 'name', 'full_name', 'first_name', 'your_name', 'names' ] ),
			rwdp_ff_extract( $data, [ 'email', 'your_email', 'email_address' ] ),
			rwdp_ff_extract( $data, [ 'phone', 'phone_number', 'telephone', 'your_phone' ] ),
			rwdp_ff_extract( $data, [ 'message', 'your_message', 'comment', 'inquiry' ] ),
			$dealer_obj ? $dealer_obj->post_title : '',
			$row->id,
			$entry_url,
		] );
	}

	fclose( $out ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- WP_Filesystem does not support php://output streams
}
