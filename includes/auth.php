<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * If someone hits wp-login.php?action=rp (old links or direct access), redirect to the
 * portal login page so the custom password form is shown instead of the WP default.
 */
add_action( 'login_init', 'rwdp_redirect_wp_login_rp_to_portal' );
function rwdp_redirect_wp_login_rp_to_portal() {
	$action = sanitize_text_field( wp_unslash( $_GET['action'] ?? '' ) );
	if ( $action !== 'rp' && $action !== 'resetpass' ) return;

	$portal_login = rwdp_get_page_url( 'login' );
	if ( ! $portal_login || $portal_login === home_url( '/' ) ) return;

	$key   = sanitize_text_field( wp_unslash( $_GET['key']   ?? '' ) );
	$login = sanitize_text_field( wp_unslash( $_GET['login'] ?? '' ) );
	if ( ! $key || ! $login ) return;

	wp_safe_redirect( add_query_arg( [
		'action' => 'rp',
		'key'    => $key,
		'login'  => $login,
	], $portal_login ) );
	exit;
}

/**
 * AJAX: validate the reset key and set the user's password.
 * Available to both logged-in and logged-out users.
 */
add_action( 'wp_ajax_nopriv_rwdp_set_password', 'rwdp_handle_set_password' );
add_action( 'wp_ajax_rwdp_set_password', 'rwdp_handle_set_password' );

function rwdp_handle_set_password() {
	check_ajax_referer( 'rwdp_set_password', 'nonce' );

	$key      = sanitize_text_field( wp_unslash( $_POST['key']              ?? '' ) );
	$login    = sanitize_text_field( wp_unslash( $_POST['login']            ?? '' ) );
	$password = wp_unslash( $_POST['password']         ?? '' );
	$confirm  = wp_unslash( $_POST['password_confirm'] ?? '' );

	if ( empty( $key ) || empty( $login ) ) {
		wp_send_json_error( [ 'message' => __( 'Invalid password reset link.', 'rw-dealer-portal' ) ] );
	}

	if ( empty( $password ) ) {
		wp_send_json_error( [ 'message' => __( 'Please enter a password.', 'rw-dealer-portal' ) ] );
	}

	if ( strlen( $password ) < 8 ) {
		wp_send_json_error( [ 'message' => __( 'Password must be at least 8 characters.', 'rw-dealer-portal' ) ] );
	}

	if ( $password !== $confirm ) {
		wp_send_json_error( [ 'message' => __( 'Passwords do not match.', 'rw-dealer-portal' ) ] );
	}

	$user = check_password_reset_key( $key, $login );

	if ( is_wp_error( $user ) ) {
		wp_send_json_error( [ 'message' => __( 'This password reset link has expired or is invalid. Please contact support.', 'rw-dealer-portal' ) ] );
	}

	wp_set_password( $password, $user->ID );

	wp_send_json_success( [
		'redirect' => add_query_arg( 'rwdp_password_set', '1', rwdp_get_page_url( 'login' ) ),
	] );
}

/**
 * Redirect wp-login.php to the portal login page.
 */
add_filter( 'login_url', 'rwdp_custom_login_url', 10, 3 );
function rwdp_custom_login_url( $login_url, $redirect, $force_reauth ) {
	$page_id = rwdp_get_page_id( 'login' );
	if ( ! $page_id ) return $login_url;
	$url = get_permalink( $page_id );
	if ( $redirect ) {
		$url = add_query_arg( 'redirect_to', rawurlencode( $redirect ), $url );
	}
	return $url;
}

/**
 * Redirect password reset link to the portal login page.
 */
add_filter( 'lostpassword_url', 'rwdp_custom_lostpassword_url', 10, 2 );
function rwdp_custom_lostpassword_url( $lostpassword_url, $redirect ) {
	$page_id = rwdp_get_page_id( 'login' );
	if ( ! $page_id ) return $lostpassword_url;
	return add_query_arg( 'action', 'lostpassword', get_permalink( $page_id ) );
}

/**
 * After login, redirect dealers and portal managers to the portal dashboard.
 * Other users (admins, editors) go to their normal destination.
 */
add_filter( 'login_redirect', 'rwdp_login_redirect', 10, 3 );
function rwdp_login_redirect( $redirect_to, $requested_redirect_to, $user ) {
	if ( is_wp_error( $user ) ) return $redirect_to;

	if ( in_array( 'rwdp_dealer', (array) $user->roles, true ) ||
	     in_array( 'rwdp_portal_manager', (array) $user->roles, true ) ) {
		$page_id = rwdp_get_page_id( 'dashboard' );
		if ( $page_id ) return get_permalink( $page_id );
	}
	return $redirect_to;
}

/**
 * Shortcode: [rwdp_login_form]
 * Shows login form + register request tab with appropriate messages.
 */
add_shortcode( 'rwdp_login_form', 'rwdp_login_form_shortcode' );

function rwdp_login_form_shortcode( $settings = [] ) {
	// Normalise: shortcode atts come in as '' when no atts declared; Elementor passes an array.
	if ( ! is_array( $settings ) ) {
		$settings = [];
	}
	$show_labels          = ( $settings['show_labels'] ?? 'yes' ) === 'yes';
	$username_label       = sanitize_text_field( $settings['username_label']       ?? '' );
	$username_placeholder = sanitize_text_field( $settings['username_placeholder'] ?? '' );
	$password_label       = sanitize_text_field( $settings['password_label']       ?? '' );
	$password_placeholder = sanitize_text_field( $settings['password_placeholder'] ?? '' );
	$login_button_text    = sanitize_text_field( $settings['login_button_text']    ?? '' ) ?: __( 'Log In', 'rw-dealer-portal' );

	// Already logged in — redirect to dashboard via JS (headers already sent inside shortcode context).
	// Skip redirect inside Elementor editor / preview so admins can still edit the page.
	$is_elementor = defined( 'ELEMENTOR_VERSION' ) &&
		( \Elementor\Plugin::$instance->editor->is_edit_mode() ||
		  \Elementor\Plugin::$instance->preview->is_preview_mode() );

	if ( is_user_logged_in() && ! $is_elementor ) {
		$dashboard_url = esc_url( rwdp_get_page_url( 'dashboard' ) );
		return '<script>window.location.href=' . wp_json_encode( $dashboard_url ) . ';</script>';
	}

	wp_enqueue_style( 'rwdp-portal', RWDP_PLUGIN_URL . 'assets/css/portal.css', [ 'dashicons' ], RWDP_VERSION );
	wp_enqueue_script( 'rwdp-portal', RWDP_PLUGIN_URL . 'assets/js/portal.js', [ 'jquery' ], RWDP_VERSION, true );
	wp_localize_script( 'rwdp-portal', 'rwdpPortal', [
		'ajaxUrl'          => admin_url( 'admin-ajax.php' ),
		'nonce'            => wp_create_nonce( 'rwdp_registration' ),
		'setPasswordNonce' => wp_create_nonce( 'rwdp_set_password' ),
	] );

	// --- Set Password flow (dealer clicked the approval email link) ---
	$rp_action = sanitize_text_field( wp_unslash( $_GET['action'] ?? '' ) );
	if ( $rp_action === 'rp' ) {
		$rp_key   = sanitize_text_field( wp_unslash( $_GET['key']   ?? '' ) );
		$rp_login = sanitize_text_field( wp_unslash( $_GET['login'] ?? '' ) );
		$rp_user  = ( $rp_key && $rp_login ) ? check_password_reset_key( $rp_key, $rp_login ) : null;

		ob_start();
		if ( ! $rp_user || is_wp_error( $rp_user ) ) {
			?>
			<div class="rwdp-auth-wrap">
				<div class="rwdp-notice rwdp-notice--error">
					<?php esc_html_e( 'This password setup link has expired or is invalid. Please contact support.', 'rw-dealer-portal' ); ?>
				</div>
			</div>
			<?php
		} else {
			?>
			<div class="rwdp-auth-wrap">
				<h2 class="rwdp-auth-title"><?php esc_html_e( 'Set Your Password', 'rw-dealer-portal' ); ?></h2>
				<p><?php esc_html_e( 'Choose a password for your new dealer portal account.', 'rw-dealer-portal' ); ?></p>
				<form id="rwdp-set-password-form" class="rwdp-form" novalidate>
					<input type="hidden" name="key"   value="<?php echo esc_attr( $rp_key ); ?>" />
					<input type="hidden" name="login" value="<?php echo esc_attr( $rp_login ); ?>" />
					<div class="rwdp-form-row">
						<label for="rwdp_new_password"><?php esc_html_e( 'New Password', 'rw-dealer-portal' ); ?> <span class="required">*</span></label>
						<input type="password" id="rwdp_new_password" name="password" required minlength="8" autocomplete="new-password" />
					</div>
					<div class="rwdp-form-row">
						<label for="rwdp_confirm_password"><?php esc_html_e( 'Confirm Password', 'rw-dealer-portal' ); ?> <span class="required">*</span></label>
						<input type="password" id="rwdp_confirm_password" name="password_confirm" required minlength="8" autocomplete="new-password" />
					</div>
					<div class="rwdp-form-message rwdp-notice" style="display:none;"></div>
					<button type="submit" class="rwdp-btn rwdp-btn--primary"><?php esc_html_e( 'Set Password', 'rw-dealer-portal' ); ?></button>
				</form>
			</div>
			<?php
		}
		return ob_get_clean();
	}

	$message = '';

	if ( isset( $_GET['rwdp_approved'] ) ) {
		$message = '<div class="rwdp-notice rwdp-notice--success">' . esc_html__( 'Your account has been approved. Please log in.', 'rw-dealer-portal' ) . '</div>';
	}
	if ( isset( $_GET['rwdp_password_set'] ) ) {
		$message = '<div class="rwdp-notice rwdp-notice--success">' . esc_html__( 'Your password has been set. Please log in below.', 'rw-dealer-portal' ) . '</div>';
	}

	$redirect_to = isset( $_GET['redirect_to'] ) ? esc_url( wp_unslash( $_GET['redirect_to'] ) ) : rwdp_get_page_url( 'dashboard' );

	ob_start();
	?>
	<div class="rwdp-auth-wrap">
		<?php echo $message; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $message is built exclusively with esc_html__() literals above ?>

		<div class="rwdp-auth-panel">
			<?php
			$login_form_html = wp_login_form( [
				'echo'           => false,
				'redirect'       => $redirect_to,
				'label_username' => $username_label ?: __( 'Email Address or Username', 'rw-dealer-portal' ),
				'label_password' => $password_label ?: __( 'Password', 'rw-dealer-portal' ),
				'label_log_in'   => $login_button_text,
				'remember'       => true,
			] );
			if ( ! $show_labels ) {
				$login_form_html = preg_replace( '/<label\s+for="user_login"[^>]*>.*?<\/label>/s', '', $login_form_html );
				$login_form_html = preg_replace( '/<label\s+for="user_pass"[^>]*>.*?<\/label>/s', '', $login_form_html );
			}
			echo $login_form_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- sanitised by wp_login_form()
			// Inject placeholders via IIFE — DOMContentLoaded has already fired in Elementor editor.
			$placeholders = array_filter( [
				'user_login' => $username_placeholder,
				'user_pass'  => $password_placeholder,
			] );
			if ( $placeholders ) :
				?>
				<script>
				(function(){
					<?php foreach ( $placeholders as $field_id => $ph ) : ?>
					(function(){ var el=document.getElementById(<?php echo wp_json_encode( $field_id ); ?>); if(el) el.placeholder=<?php echo wp_json_encode( $ph ); ?>; })();
					<?php endforeach; ?>
				})();
				</script>
				<?php
			endif;
			?>
			<p class="rwdp-auth-lost-pass">
				<a href="<?php echo esc_url( wp_lostpassword_url() ); ?>"><?php esc_html_e( 'Forgot your password?', 'rw-dealer-portal' ); ?></a>
			</p>
		</div>
	</div>
	<?php
	return ob_get_clean();
}

add_shortcode( 'rwdp_request_access', 'rwdp_request_access_shortcode' );

function rwdp_request_access_shortcode( $settings = [] ) {
	// Normalise: shortcode atts come in as '' when no atts declared; Elementor passes an array.
	if ( ! is_array( $settings ) ) {
		$settings = [];
	}
	$show_labels                = ( $settings['show_labels'] ?? 'yes' ) === 'yes';
	$reg_intro_text             = sanitize_text_field( $settings['reg_intro_text']             ?? '' );
	$reg_first_name_label       = sanitize_text_field( $settings['reg_first_name_label']       ?? '' );
	$reg_first_name_placeholder = sanitize_text_field( $settings['reg_first_name_placeholder'] ?? '' );
	$reg_last_name_label        = sanitize_text_field( $settings['reg_last_name_label']        ?? '' );
	$reg_last_name_placeholder  = sanitize_text_field( $settings['reg_last_name_placeholder']  ?? '' );
	$reg_email_label            = sanitize_text_field( $settings['reg_email_label']            ?? '' );
	$reg_email_placeholder      = sanitize_text_field( $settings['reg_email_placeholder']      ?? '' );
	$reg_company_label          = sanitize_text_field( $settings['reg_company_label']          ?? '' );
	$reg_company_placeholder    = sanitize_text_field( $settings['reg_company_placeholder']    ?? '' );
	$register_button_text       = sanitize_text_field( $settings['register_button_text']       ?? '' ) ?: __( 'Request Access', 'rw-dealer-portal' );

	// Skip redirect inside Elementor editor / preview so admins can still edit the page.
	$is_elementor = defined( 'ELEMENTOR_VERSION' ) &&
		( \Elementor\Plugin::$instance->editor->is_edit_mode() ||
		  \Elementor\Plugin::$instance->preview->is_preview_mode() );

	if ( is_user_logged_in() && ! $is_elementor ) {
		$dashboard_url = esc_url( rwdp_get_page_url( 'dashboard' ) );
		return '<script>window.location.href=' . wp_json_encode( $dashboard_url ) . ';</script>';
	}

	if ( ! wp_style_is( 'rwdp-portal', 'enqueued' ) ) {
		wp_enqueue_style( 'rwdp-portal', RWDP_PLUGIN_URL . 'assets/css/portal.css', [ 'dashicons' ], RWDP_VERSION );
	}
	if ( ! wp_script_is( 'rwdp-portal', 'enqueued' ) ) {
		wp_enqueue_script( 'rwdp-portal', RWDP_PLUGIN_URL . 'assets/js/portal.js', [ 'jquery' ], RWDP_VERSION, true );
		wp_localize_script( 'rwdp-portal', 'rwdpPortal', [
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'rwdp_registration' ),
		] );
	}

	$message = '';
	if ( isset( $_GET['rwdp_registered'] ) ) {
		$message = '<div class="rwdp-notice rwdp-notice--success">' . esc_html__( 'Your request has been submitted. You will receive an email when your account is approved.', 'rw-dealer-portal' ) . '</div>';
	}

	ob_start();
	?>
	<div class="rwdp-request-access-wrap">
		<?php echo $message; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $message is built exclusively with esc_html__() literals above ?>

		<div class="rwdp-auth-panel<?php echo $show_labels ? '' : ' rwdp-auth-panel--no-labels'; ?>">
			<?php if ( $reg_intro_text ) : ?>
			<p><?php echo esc_html( $reg_intro_text ); ?></p>
			<?php else : ?>
			<p><?php esc_html_e( 'Fill in the form below to request access to the dealer portal. An administrator will review your request and send you login credentials.', 'rw-dealer-portal' ); ?></p>
			<?php endif; ?>
			<form id="rwdp-register-form" class="rwdp-form" novalidate>
				<div class="rwdp-form-row">
					<?php if ( $show_labels ) : ?>
					<label for="rwdp_reg_first_name"><?php echo esc_html( $reg_first_name_label ?: __( 'First Name', 'rw-dealer-portal' ) ); ?> <span class="required">*</span></label>
					<?php endif; ?>
					<input type="text" id="rwdp_reg_first_name" name="first_name" required autocomplete="given-name"<?php echo $reg_first_name_placeholder ? ' placeholder="' . esc_attr( $reg_first_name_placeholder ) . '"' : ''; ?> />
				</div>
				<div class="rwdp-form-row">
					<?php if ( $show_labels ) : ?>
					<label for="rwdp_reg_last_name"><?php echo esc_html( $reg_last_name_label ?: __( 'Last Name', 'rw-dealer-portal' ) ); ?> <span class="required">*</span></label>
					<?php endif; ?>
					<input type="text" id="rwdp_reg_last_name" name="last_name" required autocomplete="family-name"<?php echo $reg_last_name_placeholder ? ' placeholder="' . esc_attr( $reg_last_name_placeholder ) . '"' : ''; ?> />
				</div>
				<div class="rwdp-form-row">
					<?php if ( $show_labels ) : ?>
					<label for="rwdp_reg_email"><?php echo esc_html( $reg_email_label ?: __( 'Email Address', 'rw-dealer-portal' ) ); ?> <span class="required">*</span></label>
					<?php endif; ?>
					<input type="email" id="rwdp_reg_email" name="email" required autocomplete="email"<?php echo $reg_email_placeholder ? ' placeholder="' . esc_attr( $reg_email_placeholder ) . '"' : ''; ?> />
				</div>
				<div class="rwdp-form-row">
					<?php if ( $show_labels ) : ?>
					<label for="rwdp_reg_company"><?php echo esc_html( $reg_company_label ?: __( 'Company / Dealership Name', 'rw-dealer-portal' ) ); ?></label>
					<?php endif; ?>
					<input type="text" id="rwdp_reg_company" name="company" autocomplete="organization"<?php echo $reg_company_placeholder ? ' placeholder="' . esc_attr( $reg_company_placeholder ) . '"' : ''; ?> />
				</div>
				<div id="rwdp-register-message" class="rwdp-notice" style="display:none;"></div>
				<button type="submit" class="rwdp-btn rwdp-btn--primary"><?php echo esc_html( $register_button_text ); ?></button>
			</form>
		</div>
	</div>
	<?php
	return ob_get_clean();
}
