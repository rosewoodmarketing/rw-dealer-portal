<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Admin page: Pending Registrations.
 */
function rwdp_admin_pending_registrations_page() {
	if ( ! current_user_can( 'manage_rwdp_portal' ) ) {
		wp_die( esc_html__( 'Permission denied.', 'rw-dealer-portal' ) );
	}

	wp_enqueue_style( 'rwdp-admin', RWDP_PLUGIN_URL . 'assets/css/admin.css', [], RWDP_VERSION );
	wp_enqueue_script( 'rwdp-admin-pending', RWDP_PLUGIN_URL . 'assets/js/portal.js', [ 'jquery' ], RWDP_VERSION, true );
	wp_localize_script( 'rwdp-admin-pending', 'rwdpAdmin', [
		'ajaxUrl' => admin_url( 'admin-ajax.php' ),
		'nonce'   => wp_create_nonce( 'rwdp_manage_registrations' ),
	] );

	$pending_users = get_users( [
		'meta_key'   => '_rwdp_account_status',
		'meta_value' => 'pending',
		'orderby'    => 'registered',
		'order'      => 'ASC',
	] );

	// All dealers for the "link to dealer" dropdown
	$dealers = get_posts( [
		'post_type'      => 'rw_dealer',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'orderby'        => 'title',
		'order'          => 'ASC',
	] );

	?>
	<div class="wrap rwdp-admin-wrap">
		<h1><?php esc_html_e( 'Pending Registrations', 'rw-dealer-portal' ); ?></h1>

		<?php if ( empty( $pending_users ) ) : ?>
			<p><?php esc_html_e( 'No pending registrations at this time.', 'rw-dealer-portal' ); ?></p>
		<?php else : ?>
			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Name', 'rw-dealer-portal' ); ?></th>
						<th><?php esc_html_e( 'Email', 'rw-dealer-portal' ); ?></th>
						<th><?php esc_html_e( 'Company', 'rw-dealer-portal' ); ?></th>
						<th><?php esc_html_e( 'Registered', 'rw-dealer-portal' ); ?></th>
						<th><?php esc_html_e( 'Link to Dealer', 'rw-dealer-portal' ); ?></th>
						<th><?php esc_html_e( 'Actions', 'rw-dealer-portal' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $pending_users as $user ) : ?>
					<tr id="rwdp-pending-row-<?php echo absint( $user->ID ); ?>">
						<td><?php echo esc_html( $user->first_name . ' ' . $user->last_name ); ?></td>
						<td><?php echo esc_html( $user->user_email ); ?></td>
						<td><?php echo esc_html( get_user_meta( $user->ID, '_rwdp_company', true ) ?: '—' ); ?></td>
						<td><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $user->user_registered ) ) ); ?></td>
						<td>
							<select class="rwdp-dealer-select" data-user="<?php echo absint( $user->ID ); ?>">
								<option value="0"><?php esc_html_e( '— No dealer yet —', 'rw-dealer-portal' ); ?></option>
								<?php foreach ( $dealers as $dealer ) : ?>
									<option value="<?php echo absint( $dealer->ID ); ?>"><?php echo esc_html( $dealer->post_title ); ?></option>
								<?php endforeach; ?>
							</select>
						</td>
						<td>
							<button type="button" class="button button-primary rwdp-approve-btn"
								data-user="<?php echo absint( $user->ID ); ?>">
								<?php esc_html_e( 'Approve', 'rw-dealer-portal' ); ?>
							</button>
							<button type="button" class="button rwdp-deny-btn"
								data-user="<?php echo absint( $user->ID ); ?>"
								style="margin-left:4px; color:#b32d2e; border-color:#b32d2e;">
								<?php esc_html_e( 'Deny', 'rw-dealer-portal' ); ?>
							</button>
							<span class="rwdp-action-result" data-user="<?php echo absint( $user->ID ); ?>" style="margin-left:8px; display:none;"></span>
						</td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			<script>
			(function($){
				$('.rwdp-approve-btn').on('click', function(){
					var userId   = $(this).data('user');
					var dealerId = $('.rwdp-dealer-select[data-user="' + userId + '"]').val();
					var $row     = $('#rwdp-pending-row-' + userId);
					var $result  = $('.rwdp-action-result[data-user="' + userId + '"]');

					$.post(rwdpAdmin.ajaxUrl, {
						action:    'rwdp_approve_registration',
						nonce:     rwdpAdmin.nonce,
						user_id:   userId,
						dealer_id: dealerId
					}, function(response){
						if ( response.success ) {
							$result.text(response.data.message).show();
							setTimeout(function(){ $row.fadeOut(); }, 1500);
						} else {
							$result.text(response.data.message).css('color','red').show();
						}
					});
				});

				$('.rwdp-deny-btn').on('click', function(){
					if ( ! confirm('<?php echo esc_js( __( 'Deny and delete this user?', 'rw-dealer-portal' ) ); ?>') ) return;
					var userId  = $(this).data('user');
					var $row    = $('#rwdp-pending-row-' + userId);
					var $result = $('.rwdp-action-result[data-user="' + userId + '"]');

					$.post(rwdpAdmin.ajaxUrl, {
						action:  'rwdp_deny_registration',
						nonce:   rwdpAdmin.nonce,
						user_id: userId
					}, function(response){
						if ( response.success ) {
							$result.text(response.data.message).show();
							setTimeout(function(){ $row.fadeOut(); }, 1500);
						} else {
							$result.text(response.data.message).css('color','red').show();
						}
					});
				});
			})(jQuery);
			</script>
		<?php endif; ?>
	</div>
	<?php
}

/**
 * Add a "Dealer Links" meta box to the user profile edit screen.
 * Lets admins/managers assign dealer post IDs to a user.
 */
add_action( 'show_user_profile', 'rwdp_user_dealer_meta_box' );
add_action( 'edit_user_profile', 'rwdp_user_dealer_meta_box' );
add_action( 'personal_options_update', 'rwdp_save_user_dealer_links' );
add_action( 'edit_user_profile_update', 'rwdp_save_user_dealer_links' );

function rwdp_user_dealer_meta_box( $user ) {
	if ( ! current_user_can( 'manage_rwdp_portal' ) && ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$linked_ids = (array) get_user_meta( $user->ID, '_rwdp_dealer_ids', true );
	$linked_ids = array_filter( array_map( 'absint', $linked_ids ) );

	$dealers = get_posts( [
		'post_type'      => 'rw_dealer',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'orderby'        => 'title',
		'order'          => 'ASC',
	] );

	?>
	<h2><?php esc_html_e( 'Linked Dealers', 'rw-dealer-portal' ); ?></h2>
	<table class="form-table" role="presentation">
		<tr>
			<th scope="row"><?php esc_html_e( 'Dealer(s)', 'rw-dealer-portal' ); ?></th>
			<td>
				<?php wp_nonce_field( 'rwdp_save_user_dealers', 'rwdp_user_dealers_nonce' ); ?>
				<?php if ( $dealers ) : ?>
					<?php foreach ( $dealers as $dealer ) : ?>
						<label style="display:block; margin-bottom:4px;">
							<input type="checkbox" name="rwdp_dealer_ids[]"
								value="<?php echo absint( $dealer->ID ); ?>"
								<?php checked( in_array( $dealer->ID, $linked_ids, true ) ); ?> />
							<?php echo esc_html( $dealer->post_title ); ?>
						</label>
					<?php endforeach; ?>
				<?php else : ?>
					<p><?php esc_html_e( 'No dealers found. Create dealers first.', 'rw-dealer-portal' ); ?></p>
				<?php endif; ?>
				<p class="description"><?php esc_html_e( 'Check all dealer locations this user manages. They will see contact submissions for all linked dealers.', 'rw-dealer-portal' ); ?></p>
			</td>
		</tr>
	</table>
	<?php
}

function rwdp_save_user_dealer_links( $user_id ) {
	if ( ! isset( $_POST['rwdp_user_dealers_nonce'] ) ||
	     ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['rwdp_user_dealers_nonce'] ) ), 'rwdp_save_user_dealers' ) ) {
		return;
	}

	if ( ! current_user_can( 'manage_rwdp_portal' ) && ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$raw_ids     = $_POST['rwdp_dealer_ids'] ?? [];
	$dealer_ids  = is_array( $raw_ids ) ? array_values( array_unique( array_map( 'absint', $raw_ids ) ) ) : [];
	update_user_meta( $user_id, '_rwdp_dealer_ids', $dealer_ids );
}
