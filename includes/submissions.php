<?php
if ( ! defined( 'ABSPATH' ) ) exit;

add_shortcode( 'rwdp_my_requests', 'rwdp_my_requests_shortcode' );

function rwdp_my_requests_shortcode() {
	if ( ! rwdp_current_user_has_portal_access() ) {
		return rwdp_portal_login_prompt();
	}

	wp_enqueue_style( 'rwdp-portal', RWDP_PLUGIN_URL . 'assets/css/portal.css', [ 'dashicons' ], RWDP_VERSION );

	$user       = wp_get_current_user();
	$dealer_ids = (array) get_user_meta( $user->ID, '_rwdp_dealer_ids', true );
	$dealer_ids = array_filter( array_map( 'absint', $dealer_ids ) );

	if ( empty( $dealer_ids ) ) {
		return '<div class="rwdp-portal"><p>' . esc_html__( 'You are not linked to any dealer account. Contact your administrator.', 'rw-dealer-portal' ) . '</p></div>';
	}

	$paged = max( 1, absint( get_query_var( 'paged' ) ?: ( $_GET['rwdp_page'] ?? 1 ) ) );

	$query = new WP_Query( [
		'post_type'      => 'rw_submission',
		'post_status'    => 'publish',
		'posts_per_page' => 20,
		'paged'          => $paged,
		'orderby'        => 'date',
		'order'          => 'DESC',
		'meta_query'     => [ [
			'key'     => '_rwdp_dealer_id',
			'value'   => $dealer_ids,
			'compare' => 'IN',
			'type'    => 'NUMERIC',
		] ],
	] );

	ob_start();
	?>
	<div class="rwdp-portal rwdp-requests">
		<h2><?php esc_html_e( 'Contact Requests', 'rw-dealer-portal' ); ?></h2>

		<?php if ( ! $query->have_posts() ) : ?>
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
					<?php while ( $query->have_posts() ) : $query->the_post();
						$did    = (int) get_post_meta( get_the_ID(), '_rwdp_dealer_id', true );
						$dealer_obj = $did ? get_post( $did ) : null;
					?>
					<tr>
						<td><?php echo get_the_date(); ?></td>
						<td><?php echo esc_html( get_post_meta( get_the_ID(), '_rwdp_customer_name', true ) ); ?></td>
						<td>
							<?php
							$cust_email = get_post_meta( get_the_ID(), '_rwdp_customer_email', true );
							if ( $cust_email ) echo '<a href="mailto:' . esc_attr( $cust_email ) . '">' . esc_html( $cust_email ) . '</a>';
							?>
						</td>
						<td><?php echo esc_html( get_post_meta( get_the_ID(), '_rwdp_customer_phone', true ) ); ?></td>
						<td class="rwdp-requests-table__message"><?php echo wp_kses_post( wpautop( get_post_meta( get_the_ID(), '_rwdp_customer_message', true ) ) ); ?></td>
						<td><?php echo $dealer_obj ? esc_html( $dealer_obj->post_title ) : '—'; ?></td>
					</tr>
					<?php endwhile; wp_reset_postdata(); ?>
				</tbody>
			</table>

			<?php if ( $query->max_num_pages > 1 ) :
				$base_url = add_query_arg( 'rwdp_page', '%#%', get_permalink() );
			?>
			<div class="rwdp-pagination">
				<?php for ( $i = 1; $i <= $query->max_num_pages; $i++ ) : ?>
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

	// Handle CSV export
	if ( isset( $_GET['rwdp_export_csv'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ?? '' ) ), 'rwdp_export_submissions' ) ) {
		rwdp_export_submissions_csv();
		exit;
	}

	$dealer_filter = absint( $_GET['rwdp_dealer'] ?? 0 );
	$date_from     = sanitize_text_field( wp_unslash( $_GET['rwdp_date_from'] ?? '' ) );
	$date_to       = sanitize_text_field( wp_unslash( $_GET['rwdp_date_to']   ?? '' ) );

	$meta_query = [];
	if ( $dealer_filter ) {
		$meta_query[] = [
			'key'     => '_rwdp_dealer_id',
			'value'   => $dealer_filter,
			'compare' => '=',
			'type'    => 'NUMERIC',
		];
	}

	$date_query = [];
	if ( $date_from ) {
		$date_query[] = [ 'after' => $date_from, 'inclusive' => true ];
	}
	if ( $date_to ) {
		$date_query[] = [ 'before' => $date_to, 'inclusive' => true ];
	}

	$paged = max( 1, absint( $_GET['paged'] ?? 1 ) );

	$query = new WP_Query( [
		'post_type'      => 'rw_submission',
		'post_status'    => 'publish',
		'posts_per_page' => 30,
		'paged'          => $paged,
		'orderby'        => 'date',
		'order'          => 'DESC',
		'meta_query'     => $meta_query ?: [],
		'date_query'     => $date_query  ?: [],
	] );

	$dealers = get_posts( [ 'post_type' => 'rw_dealer', 'posts_per_page' => -1, 'orderby' => 'title', 'order' => 'ASC' ] );
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Contact Submissions', 'rw-dealer-portal' ); ?></h1>

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

		<?php if ( ! $query->have_posts() ) : ?>
			<p><?php esc_html_e( 'No submissions found.', 'rw-dealer-portal' ); ?></p>
		<?php else : ?>
			<table class="wp-list-table widefat striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Date', 'rw-dealer-portal' ); ?></th>
						<th><?php esc_html_e( 'Name', 'rw-dealer-portal' ); ?></th>
						<th><?php esc_html_e( 'Email', 'rw-dealer-portal' ); ?></th>
						<th><?php esc_html_e( 'Phone', 'rw-dealer-portal' ); ?></th>
						<th><?php esc_html_e( 'Message', 'rw-dealer-portal' ); ?></th>
						<th><?php esc_html_e( 'Dealer', 'rw-dealer-portal' ); ?></th>
						<th><?php esc_html_e( 'Entry ID', 'rw-dealer-portal' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php while ( $query->have_posts() ) : $query->the_post();
						$did        = (int) get_post_meta( get_the_ID(), '_rwdp_dealer_id', true );
						$dealer_obj = $did ? get_post( $did ) : null;
					?>
					<tr>
						<td><?php echo get_the_date( 'Y-m-d H:i' ); ?></td>
						<td><?php echo esc_html( get_post_meta( get_the_ID(), '_rwdp_customer_name',    true ) ); ?></td>
						<td><?php
							$ce = get_post_meta( get_the_ID(), '_rwdp_customer_email', true );
							if ( $ce ) echo '<a href="mailto:' . esc_attr( $ce ) . '">' . esc_html( $ce ) . '</a>';
						?></td>
						<td><?php echo esc_html( get_post_meta( get_the_ID(), '_rwdp_customer_phone',   true ) ); ?></td>
						<td><?php echo wp_kses_post( wpautop( get_post_meta( get_the_ID(), '_rwdp_customer_message', true ) ) ); ?></td>
						<td><?php echo $dealer_obj ? esc_html( $dealer_obj->post_title ) : '—'; ?></td>
						<td><?php echo absint( get_post_meta( get_the_ID(), '_rwdp_ff_entry_id', true ) ); ?></td>
					</tr>
					<?php endwhile; wp_reset_postdata(); ?>
				</tbody>
			</table>

			<div class="tablenav bottom">
				<div class="tablenav-pages">
					<?php
					echo paginate_links( [
						'base'      => add_query_arg( 'paged', '%#%' ),
						'format'    => '',
						'current'   => $paged,
						'total'     => $query->max_num_pages,
						'prev_text' => '&laquo;',
						'next_text' => '&raquo;',
					] );
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

	$dealer_filter = absint( $_GET['rwdp_dealer'] ?? 0 );
	$date_from     = sanitize_text_field( wp_unslash( $_GET['rwdp_date_from'] ?? '' ) );
	$date_to       = sanitize_text_field( wp_unslash( $_GET['rwdp_date_to']   ?? '' ) );

	$meta_query = [];
	if ( $dealer_filter ) {
		$meta_query[] = [
			'key'     => '_rwdp_dealer_id',
			'value'   => $dealer_filter,
			'compare' => '=',
			'type'    => 'NUMERIC',
		];
	}

	$date_query = [];
	if ( $date_from ) $date_query[] = [ 'after' => $date_from, 'inclusive' => true ];
	if ( $date_to )   $date_query[] = [ 'before' => $date_to,  'inclusive' => true ];

	$submissions = get_posts( [
		'post_type'      => 'rw_submission',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'orderby'        => 'date',
		'order'          => 'DESC',
		'meta_query'     => $meta_query ?: [],
		'date_query'     => $date_query  ?: [],
	] );

	header( 'Content-Type: text/csv; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename="submissions-' . date( 'Y-m-d' ) . '.csv"' );
	header( 'Cache-Control: no-cache, no-store, must-revalidate' );

	$out = fopen( 'php://output', 'w' );
	fputcsv( $out, [ 'Date', 'Name', 'Email', 'Phone', 'Message', 'Dealer', 'Entry ID' ] );

	foreach ( $submissions as $sub ) {
		$did  = (int) get_post_meta( $sub->ID, '_rwdp_dealer_id', true );
		$d    = $did ? get_post( $did ) : null;
		fputcsv( $out, [
			get_the_date( 'Y-m-d H:i', $sub ),
			get_post_meta( $sub->ID, '_rwdp_customer_name',    true ),
			get_post_meta( $sub->ID, '_rwdp_customer_email',   true ),
			get_post_meta( $sub->ID, '_rwdp_customer_phone',   true ),
			get_post_meta( $sub->ID, '_rwdp_customer_message', true ),
			$d ? $d->post_title : '',
			get_post_meta( $sub->ID, '_rwdp_ff_entry_id', true ),
		] );
	}

	fclose( $out );
}
