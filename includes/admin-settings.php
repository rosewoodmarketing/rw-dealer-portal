<?php
if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'admin_init', 'rwdp_register_settings' );
add_action( 'admin_enqueue_scripts', 'rwdp_admin_enqueue_assets' );

/**
 * Register the settings group used by the settings page form.
 */
function rwdp_register_settings() {
	register_setting( 'rwdp_settings_group', 'rwdp_settings', [
		'sanitize_callback' => 'rwdp_sanitize_settings',
	] );
}

/**
 * Sanitize all settings values before saving.
 *
 * @param array $raw
 * @return array
 */
function rwdp_sanitize_settings( $raw ) {
	$clean = [];

	$clean['google_maps_api_key']    = sanitize_text_field( $raw['google_maps_api_key'] ?? '' );
	$clean['google_maps_server_key'] = sanitize_text_field( $raw['google_maps_server_key'] ?? '' );
	$clean['cc_emails']              = sanitize_textarea_field( $raw['cc_emails'] ?? '' );
	$clean['contact_form_id']       = absint( $raw['contact_form_id'] ?? 0 );
	$clean['login_page_id']         = absint( $raw['login_page_id'] ?? 0 );
	$clean['dashboard_page_id']     = absint( $raw['dashboard_page_id'] ?? 0 );
	$clean['enable_type_dropdown']  = ! empty( $raw['enable_type_dropdown'] ) ? 1 : 0;

	$mode = sanitize_key( $raw['filter_source_mode'] ?? 'acf_relationship_field' );
	$clean['filter_source_mode'] = 'acf_relationship_field' === $mode ? $mode : 'acf_relationship_field';

	$clean['filter_acf_field_name'] = sanitize_key( $raw['filter_acf_field_name'] ?? '' );

	// Array of page IDs to restrict to logged-in portal users
	$raw_ids = $raw['restricted_page_ids'] ?? [];
	if ( ! is_array( $raw_ids ) ) {
		$raw_ids = [];
	}
	$clean['restricted_page_ids'] = array_values( array_unique( array_map( 'absint', $raw_ids ) ) );

	return $clean;
}
/**
 * Render the settings page.
 */
function rwdp_admin_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to access this page.', 'rw-dealer-portal' ) );
	}

	$settings          = get_option( 'rwdp_settings', [] );
	$api_key           = $settings['google_maps_api_key'] ?? '';
	$server_key        = $settings['google_maps_server_key'] ?? '';
	$cc_emails         = $settings['cc_emails'] ?? '';
	$contact_form_id   = $settings['contact_form_id'] ?? 0;
	$login_page_id     = $settings['login_page_id'] ?? 0;
	$dashboard_page_id = $settings['dashboard_page_id'] ?? 0;
	$restricted_ids    = $settings['restricted_page_ids'] ?? [];
	$enable_dropdown   = ! empty( $settings['enable_type_dropdown'] );
	$filter_mode       = $settings['filter_source_mode'] ?? 'acf_relationship_field';
	$acf_field_name    = $settings['filter_acf_field_name'] ?? '';

	// Fluent Forms list
	$ff_forms = [];
	if ( function_exists( 'wpFluent' ) || class_exists( '\FluentForm\App\Models\Form' ) ) {
		global $wpdb;
		$ff_forms = $wpdb->get_results( "SELECT id, title FROM {$wpdb->prefix}fluentform_forms ORDER BY title ASC" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	}

	// All published pages
	$all_pages = get_pages( [ 'post_status' => 'publish', 'sort_column' => 'post_title' ] );

	$valid_tabs  = [ 'maps', 'contact', 'pages', 'restricted', 'dealer_finder' ];
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$current_tab = ( isset( $_GET['tab'] ) && in_array( $_GET['tab'], $valid_tabs, true ) )
		? sanitize_key( $_GET['tab'] ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		: 'maps';

	$tabs = [
		'maps'       => __( 'Maps API Keys', 'rw-dealer-portal' ),
		'contact'    => __( 'Contact Form', 'rw-dealer-portal' ),
		'pages'      => __( 'Portal Pages', 'rw-dealer-portal' ),
		'restricted' => __( 'Restricted Pages', 'rw-dealer-portal' ),
		'dealer_finder' => __( 'Dealer Finder', 'rw-dealer-portal' ),
	];

	$acf_mode_warning = '';
	$acf_mode_info    = '';
	$acf_mode_diagnostic = '';
	if ( 'dealer_finder' === $current_tab && $enable_dropdown && 'acf_relationship_field' === $filter_mode ) {
		$valid_field = false;
		if ( $acf_field_name && function_exists( 'get_field_object' ) ) {
			$sample_ids = get_posts( [
				'post_type'      => 'rw_dealer',
				'post_status'    => 'publish',
				'posts_per_page' => 1,
				'fields'         => 'ids',
			] );
			$sample_id = ! empty( $sample_ids ) ? (int) $sample_ids[0] : 0;
			if ( $sample_id ) {
				$field = get_field_object( $acf_field_name, $sample_id, false, false );
				$valid_field = is_array( $field ) && in_array( (string) ( $field['type'] ?? '' ), [ 'relationship', 'post_object' ], true );
			} else {
				$valid_field = true;
			}
		} elseif ( $acf_field_name ) {
			$valid_field = true;
		}

		if ( ! $acf_field_name ) {
			$acf_mode_warning = __( 'Relationship mode is enabled, but no ACF Relationship Field Name is set. Dealer Finder cannot build dropdown options until a field is provided.', 'rw-dealer-portal' );
		} elseif ( ! $valid_field ) {
			$acf_mode_warning = __( 'Relationship mode is enabled, but the configured field does not appear to be an ACF Relationship/Post Object field on rw_dealer.', 'rw-dealer-portal' );
		} else {
			$option_count = 0;
			if ( function_exists( 'rwdp_get_relationship_filter_options' ) ) {
				$option_count = count( rwdp_get_relationship_filter_options( $acf_field_name ) );
			}

			$acf_mode_info = sprintf(
				/* translators: %s: field name */
				__( 'Relationship mode configured successfully. Dealer Finder will use ACF field: %s.', 'rw-dealer-portal' ),
				$acf_field_name
			);

			$acf_mode_diagnostic = sprintf(
				/* translators: 1: detected option count, 2: field name */
				__( 'Diagnostic: %1$d filter option(s) detected from field "%2$s" across published dealers.', 'rw-dealer-portal' ),
				absint( $option_count ),
				$acf_field_name
			);
		}
	}

	?>
	<div class="wrap rwdp-admin-wrap">
		<h1><?php esc_html_e( 'Dealer Portal Settings', 'rw-dealer-portal' ); ?></h1>

		<?php settings_errors( 'rwdp_settings_group' ); ?>

		<?php if ( $acf_mode_warning ) : ?>
			<div class="notice notice-warning">
				<p><?php echo esc_html( $acf_mode_warning ); ?></p>
			</div>
		<?php endif; ?>

		<?php if ( $acf_mode_info ) : ?>
			<div class="notice notice-info">
				<p><?php echo esc_html( $acf_mode_info ); ?></p>
				<?php if ( $acf_mode_diagnostic ) : ?>
					<p><strong><?php echo esc_html( $acf_mode_diagnostic ); ?></strong></p>
				<?php endif; ?>
			</div>
		<?php endif; ?>

		<div class="rwdp-settings-layout">

			<nav class="rwdp-settings-nav" aria-label="<?php esc_attr_e( 'Settings sections', 'rw-dealer-portal' ); ?>">
				<?php foreach ( $tabs as $tab_key => $tab_label ) :
					$tab_url    = add_query_arg( 'tab', $tab_key, menu_page_url( 'rwdp-settings', false ) );
					$is_active  = $current_tab === $tab_key;
				?>
					<a href="<?php echo esc_url( $tab_url ); ?>"
					   class="rwdp-settings-nav__item<?php echo $is_active ? ' rwdp-settings-nav__item--active' : ''; ?>"
					   <?php echo $is_active ? 'aria-current="page"' : ''; ?>>
						<?php echo esc_html( $tab_label ); ?>
					</a>
				<?php endforeach; ?>
			</nav>

			<div class="rwdp-settings-content">
				<form method="post" action="options.php">
					<?php settings_fields( 'rwdp_settings_group' ); ?>

					<?php
					/*
					 * For tabs we are NOT currently on, output hidden inputs so their
					 * values are preserved when saving the active tab.
					 */
					?>

					<?php if ( 'maps' !== $current_tab ) : ?>
						<input type="hidden" name="rwdp_settings[google_maps_api_key]"    value="<?php echo esc_attr( $api_key ); ?>" />
						<input type="hidden" name="rwdp_settings[google_maps_server_key]" value="<?php echo esc_attr( $server_key ); ?>" />
					<?php endif; ?>

					<?php if ( 'contact' !== $current_tab ) : ?>
						<input type="hidden" name="rwdp_settings[cc_emails]"        value="<?php echo esc_attr( $cc_emails ); ?>" />
						<input type="hidden" name="rwdp_settings[contact_form_id]"  value="<?php echo absint( $contact_form_id ); ?>" />
					<?php endif; ?>

					<?php if ( 'pages' !== $current_tab ) : ?>
						<input type="hidden" name="rwdp_settings[login_page_id]"     value="<?php echo absint( $login_page_id ); ?>" />
						<input type="hidden" name="rwdp_settings[dashboard_page_id]" value="<?php echo absint( $dashboard_page_id ); ?>" />
					<?php endif; ?>

					<?php if ( 'restricted' !== $current_tab ) :
						foreach ( $restricted_ids as $rid ) : ?>
							<input type="hidden" name="rwdp_settings[restricted_page_ids][]" value="<?php echo absint( $rid ); ?>" />
						<?php endforeach;
					endif; ?>

					<?php if ( 'dealer_finder' !== $current_tab ) : ?>
						<input type="hidden" name="rwdp_settings[enable_type_dropdown]" value="<?php echo $enable_dropdown ? '1' : '0'; ?>" />
						<input type="hidden" name="rwdp_settings[filter_source_mode]" value="<?php echo esc_attr( $filter_mode ); ?>" />
						<input type="hidden" name="rwdp_settings[filter_acf_field_name]" value="<?php echo esc_attr( $acf_field_name ); ?>" />
					<?php endif; ?>

					<table class="form-table" role="presentation">

						<?php if ( 'maps' === $current_tab ) : ?>

							<tr>
								<th scope="row">
									<label for="rwdp_google_maps_api_key"><?php esc_html_e( 'Frontend API Key', 'rw-dealer-portal' ); ?></label>
								</th>
								<td>
									<input type="text" id="rwdp_google_maps_api_key" name="rwdp_settings[google_maps_api_key]"
										value="<?php echo esc_attr( $api_key ); ?>" class="regular-text" autocomplete="off" />
									<p class="description"><?php esc_html_e( 'Used for the Dealer Finder map embed and Places autocomplete. Restrict this key to HTTP referrers (your domain) in Google Cloud.', 'rw-dealer-portal' ); ?></p>
								</td>
							</tr>

							<tr>
								<th scope="row">
									<label for="rwdp_google_maps_server_key"><?php esc_html_e( 'Server Key (Geocoding)', 'rw-dealer-portal' ); ?></label>
								</th>
								<td>
									<input type="text" id="rwdp_google_maps_server_key" name="rwdp_settings[google_maps_server_key]"
										value="<?php echo esc_attr( $server_key ); ?>" class="regular-text" autocomplete="off" />
									<p class="description"><?php esc_html_e( 'Used server-side to geocode dealer addresses. Restrict this key to your server\'s IP in Google Cloud. Leave blank to use the Frontend key for geocoding.', 'rw-dealer-portal' ); ?></p>
								</td>
							</tr>

						<?php elseif ( 'contact' === $current_tab ) : ?>

							<tr>
								<th scope="row">
									<label for="rwdp_cc_emails"><?php esc_html_e( 'CC Email Address(es)', 'rw-dealer-portal' ); ?></label>
								</th>
								<td>
									<textarea id="rwdp_cc_emails" name="rwdp_settings[cc_emails]" rows="3" class="large-text"><?php echo esc_textarea( $cc_emails ); ?></textarea>
									<p class="description"><?php esc_html_e( 'Comma-separated email addresses that receive a copy of every dealer contact submission.', 'rw-dealer-portal' ); ?></p>
								</td>
							</tr>

							<tr>
								<th scope="row">
									<label for="rwdp_contact_form_id"><?php esc_html_e( 'Dealer Contact Form', 'rw-dealer-portal' ); ?></label>
								</th>
								<td>
									<?php if ( $ff_forms ) : ?>
										<select id="rwdp_contact_form_id" name="rwdp_settings[contact_form_id]">
											<option value="0"><?php esc_html_e( '— Select Fluent Form —', 'rw-dealer-portal' ); ?></option>
											<?php foreach ( $ff_forms as $form ) : ?>
												<option value="<?php echo absint( $form->id ); ?>" <?php selected( $contact_form_id, $form->id ); ?>>
													<?php echo esc_html( $form->title ); ?> (ID: <?php echo absint( $form->id ); ?>)
												</option>
											<?php endforeach; ?>
										</select>
									<?php else : ?>
										<input type="number" id="rwdp_contact_form_id" name="rwdp_settings[contact_form_id]"
											value="<?php echo absint( $contact_form_id ); ?>" class="small-text" min="0" />
										<p class="description"><?php esc_html_e( 'Fluent Forms is not active. Enter the form ID manually.', 'rw-dealer-portal' ); ?></p>
									<?php endif; ?>
									<p class="description">
										<?php esc_html_e( 'The Fluent Form shown on the Dealer Finder page. The form must contain a hidden field named ', 'rw-dealer-portal' ); ?>
										<code>rwdp_dealer_id</code>.
									</p>
								</td>
							</tr>

						<?php elseif ( 'pages' === $current_tab ) : ?>

							<tr>
								<th scope="row">
									<label for="rwdp_login_page_id"><?php esc_html_e( 'Portal Login Page', 'rw-dealer-portal' ); ?></label>
								</th>
								<td>
									<select id="rwdp_login_page_id" name="rwdp_settings[login_page_id]">
										<option value="0"><?php esc_html_e( '— Select Page —', 'rw-dealer-portal' ); ?></option>
										<?php foreach ( $all_pages as $page ) : ?>
											<option value="<?php echo absint( $page->ID ); ?>" <?php selected( $login_page_id, $page->ID ); ?>>
												<?php echo esc_html( $page->post_title ); ?>
											</option>
										<?php endforeach; ?>
									</select>
									<p class="description"><?php esc_html_e( 'Page containing the [rwdp_login_form] shortcode. Unauthenticated users are redirected here.', 'rw-dealer-portal' ); ?></p>
								</td>
							</tr>

							<tr>
								<th scope="row">
									<label for="rwdp_dashboard_page_id"><?php esc_html_e( 'Portal Dashboard Page', 'rw-dealer-portal' ); ?></label>
								</th>
								<td>
									<select id="rwdp_dashboard_page_id" name="rwdp_settings[dashboard_page_id]">
										<option value="0"><?php esc_html_e( '— Select Page —', 'rw-dealer-portal' ); ?></option>
										<?php foreach ( $all_pages as $page ) : ?>
											<option value="<?php echo absint( $page->ID ); ?>" <?php selected( $dashboard_page_id, $page->ID ); ?>>
												<?php echo esc_html( $page->post_title ); ?>
											</option>
										<?php endforeach; ?>
									</select>
									<p class="description"><?php esc_html_e( 'Dealers are sent here after logging in.', 'rw-dealer-portal' ); ?></p>
								</td>
							</tr>

						<?php elseif ( 'restricted' === $current_tab ) : ?>

							<tr>
								<th scope="row"><?php esc_html_e( 'Restricted Pages', 'rw-dealer-portal' ); ?></th>
								<td>
									<fieldset>
										<legend class="screen-reader-text"><?php esc_html_e( 'Restricted Pages', 'rw-dealer-portal' ); ?></legend>
										<div class="rwdp-restricted-pages-list">
											<?php foreach ( $all_pages as $page ) : ?>
												<label>
													<input type="checkbox"
														name="rwdp_settings[restricted_page_ids][]"
														value="<?php echo absint( $page->ID ); ?>"
														<?php checked( in_array( $page->ID, $restricted_ids, true ) ); ?> />
													<?php echo esc_html( $page->post_title ); ?>
												</label>
											<?php endforeach; ?>
										</div>
									</fieldset>
									<p class="description"><?php esc_html_e( 'Checked pages require the visitor to be logged in with a Dealer, Portal Manager, Editor, or Administrator account.', 'rw-dealer-portal' ); ?></p>
								</td>
							</tr>

						<?php elseif ( 'dealer_finder' === $current_tab ) : ?>

							<tr>
								<th scope="row"><?php esc_html_e( 'Enable Type Dropdown', 'rw-dealer-portal' ); ?></th>
								<td>
									<label for="rwdp_enable_type_dropdown">
										<input type="checkbox" id="rwdp_enable_type_dropdown" name="rwdp_settings[enable_type_dropdown]" value="1" <?php checked( $enable_dropdown ); ?> />
										<?php esc_html_e( 'Show type dropdown on the Dealer Finder map page.', 'rw-dealer-portal' ); ?>
									</label>
									<p class="description"><?php esc_html_e( 'If unchecked, the map page will not show a type dropdown. Locked shortcode filtering can still be used.', 'rw-dealer-portal' ); ?></p>
								</td>
							</tr>

							<tr>
								<th scope="row"><label for="rwdp_filter_source_mode"><?php esc_html_e( 'Filter Source Mode', 'rw-dealer-portal' ); ?></label></th>
								<td>
									<select id="rwdp_filter_source_mode" name="rwdp_settings[filter_source_mode]">
										<option value="acf_relationship_field" <?php selected( $filter_mode, 'acf_relationship_field' ); ?>><?php esc_html_e( 'ACF relationship field', 'rw-dealer-portal' ); ?></option>
									</select>
									<p class="description"><?php esc_html_e( 'Dropdown options are built from related posts selected in the ACF field below across published dealers.', 'rw-dealer-portal' ); ?></p>
								</td>
							</tr>

							<tr>
								<th scope="row"><label for="rwdp_filter_acf_field_name"><?php esc_html_e( 'ACF Relationship Field Name', 'rw-dealer-portal' ); ?></label></th>
								<td>
									<input type="text" id="rwdp_filter_acf_field_name" name="rwdp_settings[filter_acf_field_name]" value="<?php echo esc_attr( $acf_field_name ); ?>" class="regular-text" placeholder="service_types" />
									<p class="description"><?php esc_html_e( 'Field name/key on rw_dealer that stores related posts (ACF Relationship or Post Object). Example: service_types.', 'rw-dealer-portal' ); ?></p>
								</td>
							</tr>

						<?php endif; ?>

					</table>

					<?php submit_button(); ?>
				</form>
			</div><!-- .rwdp-settings-content -->

		</div><!-- .rwdp-settings-layout -->
	</div><!-- .wrap -->
	<?php
}

/**
 * Enqueue admin CSS/JS only on RWDP admin pages.
 */
function rwdp_admin_enqueue_assets( $hook ) {
	$rwdp_pages = [
		'toplevel_page_rw-dealer-portal',
		'dealer-portal_page_rwdp-submissions',
		'dealer-portal_page_rwdp-pending-registrations',
		'dealer-portal_page_rwdp-settings',
	];

	if ( in_array( $hook, $rwdp_pages, true ) || get_current_screen()->post_type === 'rw_dealer' || get_current_screen()->post_type === 'rw_asset' ) {
		wp_enqueue_style(
			'rwdp-admin',
			RWDP_PLUGIN_URL . 'assets/css/admin.css',
			[],
			RWDP_VERSION
		);
		wp_enqueue_media();
	}
}
