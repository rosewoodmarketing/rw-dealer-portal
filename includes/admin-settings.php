<?php
if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'admin_init', 'rwdp_register_settings' );
add_action( 'admin_enqueue_scripts', 'rwdp_admin_enqueue_assets' );
add_action( 'admin_post_rwdp_rebuild_pages', 'rwdp_handle_rebuild_pages' );

/**
 * Handle the "Rebuild Default Pages" button submission.
 */
function rwdp_handle_rebuild_pages() {
	check_admin_referer( 'rwdp_rebuild_pages', 'rwdp_rebuild_nonce' );
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to do this.', 'rw-dealer-portal' ) );
	}
	rwdp_create_portal_pages();
	wp_safe_redirect( add_query_arg( [
		'page'         => 'rwdp-settings',
		'tab'          => 'pages',
		'rwdp_rebuilt' => '1',
	], admin_url( 'admin.php' ) ) );
	exit;
}

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

	$raw_fields = $raw['active_filter_fields'] ?? [];
	if ( ! is_array( $raw_fields ) ) {
		$raw_fields = [];
	}
	$clean['active_filter_fields'] = array_values( array_filter( array_map( 'sanitize_key', $raw_fields ) ) );

	$raw_logic = sanitize_key( $raw['filter_logic'] ?? 'and' );
	$clean['filter_logic'] = in_array( $raw_logic, [ 'and', 'or' ], true ) ? $raw_logic : 'and';

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
	$active_filter_fields = $settings['active_filter_fields'] ?? [];
	if ( ! is_array( $active_filter_fields ) ) {
		$active_filter_fields = [];
	}
	$filter_logic         = $settings['filter_logic'] ?? 'and';
	$detected_acf_fields  = rwdp_detect_acf_relationship_fields();

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
		'dealer_finder' => __( 'ACF Relationships', 'rw-dealer-portal' ),
	];

	?>
	<div class="wrap rwdp-admin-wrap">
		<h1><?php esc_html_e( 'Dealer Portal Settings', 'rw-dealer-portal' ); ?></h1>

		<?php settings_errors( 'rwdp_settings_group' ); ?>

		<?php // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['rwdp_rebuilt'] ) && '1' === $_GET['rwdp_rebuilt'] ) : ?>
			<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Portal pages rebuilt successfully.', 'rw-dealer-portal' ); ?></p></div>
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

						<?php foreach ( $active_filter_fields as $field_key ) : ?>
							<input type="hidden" name="rwdp_settings[active_filter_fields][]" value="<?php echo esc_attr( $field_key ); ?>" />
						<?php endforeach; ?>
						<input type="hidden" name="rwdp_settings[filter_logic]" value="<?php echo esc_attr( $filter_logic ); ?>" />
					<?php endif; ?>

					<table class="form-table" role="presentation">

						<?php if ( 'maps' === $current_tab ) : ?>

							<tr>
								<th scope="row"><?php esc_html_e( 'Setup Instructions', 'rw-dealer-portal' ); ?></th>
								<td>
									<details>
										<summary style="cursor:pointer; font-weight:600;"><?php esc_html_e( 'Create Maps API Keys', 'rw-dealer-portal' ); ?></summary>
										<ol style="margin-top:10px; line-height:1.8;">
											<li><?php esc_html_e( 'Sign into the client\'s Google Account then go to', 'rw-dealer-portal' ); ?> <a href="https://console.cloud.google.com" target="_blank" rel="noopener noreferrer">console.cloud.google.com</a>. <?php esc_html_e( 'Create a new project if needed.', 'rw-dealer-portal' ); ?></li>
											<li>
												<?php esc_html_e( 'Go to APIs &amp; Services → Enable APIs and services — enable the following:', 'rw-dealer-portal' ); ?>
												<ul style="list-style:disc; margin:.4em 0 .4em 1.4em;">
													<li><strong>Maps JavaScript API</strong> — <?php esc_html_e( 'renders the map', 'rw-dealer-portal' ); ?></li>
													<li><strong>Places API</strong> — <?php esc_html_e( 'powers the location search/geocoding in the search bar', 'rw-dealer-portal' ); ?></li>
													<li><strong>Geocoding API</strong> — <?php esc_html_e( 'converts dealer addresses to lat/lng coordinates when a dealer post is saved', 'rw-dealer-portal' ); ?></li>
												</ul>
											</li>
											<li><?php esc_html_e( 'Go to APIs &amp; Services → Credentials → Create Credentials → API key — create two keys total:', 'rw-dealer-portal' ); ?>
												<ul style="list-style:disc; margin:.4em 0 .4em 1.4em;">
													<li>
														<strong><?php esc_html_e( 'Key 1 — Frontend/Browser key', 'rw-dealer-portal' ); ?></strong><br>
														<?php esc_html_e( 'Name: "Dealer Finder — Frontend"', 'rw-dealer-portal' ); ?><br>
														<?php esc_html_e( 'Application restrictions → HTTP referrers (Websites) → add https://yourdomain.com/* and https://www.yourdomain.com/*', 'rw-dealer-portal' ); ?><br>
														<?php esc_html_e( 'API restrictions → Restrict to: Maps JavaScript API + Places API + Geocoding API', 'rw-dealer-portal' ); ?><br>
														<?php esc_html_e( 'Paste into "Frontend API Key" below.', 'rw-dealer-portal' ); ?>
													</li>
													<li style="margin-top:.5em;">
														<strong><?php esc_html_e( 'Key 2 — Server/Geocoding key', 'rw-dealer-portal' ); ?></strong><br>
														<?php esc_html_e( 'Name: "Dealer Finder — Server"', 'rw-dealer-portal' ); ?><br>
														<?php esc_html_e( 'Application restrictions → IP addresses → add your hosting server\'s outbound IP (IP address for external connections in Kinsta)', 'rw-dealer-portal' ); ?><br>
														<?php esc_html_e( 'API restrictions → Restrict to: Geocoding API only', 'rw-dealer-portal' ); ?><br>
														<?php esc_html_e( 'Paste into "Server Key (Geocoding)" below.', 'rw-dealer-portal' ); ?>
													</li>
												</ul>
											</li>
										</ol>
									</details>
								</td>
							</tr>

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
									<p class="description"><?php esc_html_e( 'Page containing the [rwdp_dashboard] shortcode. Dealers are sent here after logging in.', 'rw-dealer-portal' ); ?></p>
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
							<th scope="row"><?php esc_html_e( 'ACF Relationship Filters', 'rw-dealer-portal' ); ?></th>
							<td>
								<?php if ( ! empty( $detected_acf_fields ) ) : ?>
									<fieldset>
										<legend class="screen-reader-text"><?php esc_html_e( 'ACF Relationship Filters', 'rw-dealer-portal' ); ?></legend>
										<?php foreach ( $detected_acf_fields as $acf_field ) : ?>
											<label style="display:block; margin-bottom:6px;">
												<input type="checkbox"
													name="rwdp_settings[active_filter_fields][]"
													value="<?php echo esc_attr( $acf_field['key'] ); ?>"
													<?php checked( in_array( $acf_field['key'], $active_filter_fields, true ) ); ?> />
												<strong><?php echo esc_html( $acf_field['label'] ); ?></strong>
												<code style="margin-left:6px;"><?php echo esc_html( $acf_field['key'] ); ?></code>
												<span class="description" style="margin-left:6px;">
													<?php
													$opt_count = count( rwdp_get_relationship_filter_options( $acf_field['key'] ) );
													printf(
														/* translators: %d: number of filter options */
														esc_html__( '— %d option(s) detected', 'rw-dealer-portal' ),
														absint( $opt_count )
													);
													?>
												</span>
											</label>
										<?php endforeach; ?>
									</fieldset>
									<p class="description"><?php esc_html_e( 'Check each ACF Relationship or Post Object field to expose as a filter dropdown on the Dealer Finder.', 'rw-dealer-portal' ); ?></p>
								<?php elseif ( ! function_exists( 'acf_get_field_groups' ) ) : ?>
									<p class="description"><?php esc_html_e( 'Advanced Custom Fields is not active. Install ACF and add Relationship or Post Object fields to the Dealer post type to enable ACF-based filters.', 'rw-dealer-portal' ); ?></p>
								<?php else : ?>
									<p class="description"><?php esc_html_e( 'No ACF Relationship or Post Object fields detected on the Dealer post type. Add them in ACF and they will appear here automatically.', 'rw-dealer-portal' ); ?></p>
								<?php endif; ?>
							</td>
						</tr>

						<tr>
							<th scope="row"><?php esc_html_e( 'Filter Logic', 'rw-dealer-portal' ); ?></th>
							<td>
								<fieldset>
									<legend class="screen-reader-text"><?php esc_html_e( 'Filter Logic', 'rw-dealer-portal' ); ?></legend>
									<label style="display:block; margin-bottom:6px;">
										<input type="radio" name="rwdp_settings[filter_logic]" value="and" <?php checked( $filter_logic, 'and' ); ?> />
										<strong><?php esc_html_e( 'AND', 'rw-dealer-portal' ); ?></strong> &mdash; <?php esc_html_e( 'Dealer must match all selected filters.', 'rw-dealer-portal' ); ?>
									</label>
									<label style="display:block;">
										<input type="radio" name="rwdp_settings[filter_logic]" value="or" <?php checked( $filter_logic, 'or' ); ?> />
										<strong><?php esc_html_e( 'OR', 'rw-dealer-portal' ); ?></strong> &mdash; <?php esc_html_e( 'Dealer matches if it satisfies any one of the selected filters.', 'rw-dealer-portal' ); ?>
									</label>
								</fieldset>
								<p class="description"><?php esc_html_e( 'Applies across all active filters (ACF fields and Dealer Type taxonomy).', 'rw-dealer-portal' ); ?></p>
						<?php endif; ?>

					</table>

					<?php submit_button(); ?>
				</form>

				<?php if ( 'pages' === $current_tab ) : ?>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin-top:16px; padding-top:16px; border-top:1px solid #f0f0f1;">
					<?php wp_nonce_field( 'rwdp_rebuild_pages', 'rwdp_rebuild_nonce' ); ?>
					<input type="hidden" name="action" value="rwdp_rebuild_pages" />
					<button type="submit" class="button button-secondary">
						<?php esc_html_e( 'Rebuild Default Pages', 'rw-dealer-portal' ); ?>
					</button>
					<p class="description" style="margin-top:6px;"><?php esc_html_e( 'Re-creates any missing portal pages (Login, Dashboard, Assets, Account, etc.). Existing pages are not affected.', 'rw-dealer-portal' ); ?></p>
				</form>
				<?php endif; ?>

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
