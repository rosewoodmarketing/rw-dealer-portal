<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Create the protected uploads directory on activation.
 * Files moved here are blocked from direct HTTP access via .htaccess (Apache)
 * or the Nginx rules added by the server admin. All access goes through the
 * rwdp_serve_file AJAX proxy which enforces auth + nonce.
 */
function rwdp_create_protected_uploads_dir() {
	$upload_dir   = wp_upload_dir();
	$protected    = trailingslashit( $upload_dir['basedir'] ) . 'rwdp-protected';
	$htaccess     = $protected . '/.htaccess';

	if ( ! file_exists( $protected ) ) {
		wp_mkdir_p( $protected );
	}

	if ( is_dir( $protected ) && ! file_exists( $htaccess ) ) {
		// Deny direct HTTP access on Apache. Nginx users need a similar rule
		// in their server config (already handled by Kinsta's default deny).
		file_put_contents( // phpcs:ignore WordPress.WP.AlternativeFunctions
			$htaccess,
			"# Block all direct access — files are served via PHP proxy.\n<IfModule mod_authz_core.c>\n    Require all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\n    Order deny,allow\n    Deny from all\n</IfModule>\n"
		);
	}
}

/**
 * Create the portal pages on activation (idempotent).
 * Checks for existing page IDs stored in rwdp_page_ids before creating.
 */
function rwdp_create_portal_pages() {
	$existing       = get_option( 'rwdp_page_ids', [] );
	$settings       = get_option( 'rwdp_settings', [] );
	$restricted_ids = isset( $settings['restricted_page_ids'] ) ? $settings['restricted_page_ids'] : [];

	// -------------------------------------------------------------------------
	// Step 1: Parent page — Dealer Portal (must exist before sub-pages are created)
	// -------------------------------------------------------------------------
	$dashboard_post = ! empty( $existing['dashboard'] ) ? get_post( $existing['dashboard'] ) : null;
	if ( ! $dashboard_post || $dashboard_post->post_status === 'trash' ) {
		if ( $dashboard_post && $dashboard_post->post_status === 'trash' ) {
			// Restore trashed page
			wp_update_post( [ 'ID' => $dashboard_post->ID, 'post_status' => 'publish' ] );
			$existing['dashboard'] = $dashboard_post->ID;
		} else {
			$existing_page = get_page_by_path( 'dealer-portal' );
			if ( $existing_page ) {
				$existing['dashboard'] = $existing_page->ID;
			} else {
				$page_id = wp_insert_post( [
					'post_title'     => 'Dealer Portal',
					'post_name'      => 'dealer-portal',
					'post_content'   => '[rwdp_dashboard]',
					'post_status'    => 'publish',
					'post_type'      => 'page',
					'comment_status' => 'closed',
					'ping_status'    => 'closed',
				] );
				if ( $page_id && ! is_wp_error( $page_id ) ) {
					$existing['dashboard'] = $page_id;
					update_post_meta( $page_id, '_rwdp_restrict_access', '1' );
					$restricted_ids[] = $page_id;
				}
			}
		}
	}
	$dashboard_id = ! empty( $existing['dashboard'] ) ? (int) $existing['dashboard'] : 0;

	// -------------------------------------------------------------------------
	// Step 2: Top-level pages (not children of Dealer Portal)
	// -------------------------------------------------------------------------
	$top_level_pages = [
		'login' => [
			'title'   => 'Portal Login',
			'slug'    => 'portal-login',
			'content' => implode( "\n\n", [
				'<!-- wp:paragraph -->',
				'<p>NOTE: This page uses 2 shortcodes by default but if you are using Elementor, you can replace the shortcodes with the <strong>Dealer Login Form</strong> and <strong>Dealer Request Access Form</strong> Elementor Widgets.</p>',
				'<!-- /wp:paragraph -->',
				'<!-- wp:heading -->',
				'<h2 class="wp-block-heading">Log In</h2>',
				'<!-- /wp:heading -->',
				'<!-- wp:shortcode -->',
				'[rwdp_login_form]',
				'<!-- /wp:shortcode -->',
				'<!-- wp:heading -->',
				'<h2 class="wp-block-heading">Request Access</h2>',
				'<!-- /wp:heading -->',
				'<!-- wp:shortcode -->',
				'[rwdp_request_access]',
				'<!-- /wp:shortcode -->',
			] ),
			'private' => false,
		],
		'finder' => [
			'title'   => 'Dealer Finder',
			'slug'    => 'dealer-finder',
			'content' => '[rwdp_dealer_finder]',
			'content' => implode( "\n\n", [
				'<!-- wp:paragraph -->',
				'<p>NOTE: This page uses a shortcode by default but if you are using Elementor, you can replace the shortcode with the <strong>Dealer Search Bar</strong>, <strong>Dealer Map</strong> and <strong>Dealer Results List</strong> Elementor Widgets.</p>',
				'<!-- /wp:paragraph -->',
				'<!-- wp:shortcode -->',
				'[rwdp_dealer_finder]',
				'<!-- /wp:shortcode -->',
			] ),
			'private' => false,
		],
	];

	foreach ( $top_level_pages as $key => $page ) {
		$existing_post = ! empty( $existing[ $key ] ) ? get_post( $existing[ $key ] ) : null;
		if ( $existing_post && $existing_post->post_status === 'trash' ) {
			wp_update_post( [ 'ID' => $existing_post->ID, 'post_status' => 'publish' ] );
			continue;
		}
		if ( $existing_post ) {
			continue;
		}
		$existing_page = get_page_by_path( $page['slug'] );
		if ( $existing_page ) {
			$existing[ $key ] = $existing_page->ID;
			continue;
		}
		$page_id = wp_insert_post( [
			'post_title'     => $page['title'],
			'post_name'      => $page['slug'],
			'post_content'   => $page['content'],
			'post_status'    => 'publish',
			'post_type'      => 'page',
			'comment_status' => 'closed',
			'ping_status'    => 'closed',
		] );
		if ( $page_id && ! is_wp_error( $page_id ) ) {
			$existing[ $key ] = $page_id;
		}
	}

	// -------------------------------------------------------------------------
	// Step 3: Sub-pages of Dealer Portal (/dealer-portal/*)
	// get_page_by_path() requires the full path for child pages to avoid
	// collisions if a top-level page with the same slug exists elsewhere.
	// -------------------------------------------------------------------------
	$sub_pages = [
		'assets' => [
			'title'   => 'Dealer Assets',
			'slug'    => 'dealer-assets',
			'content' => implode( "\n\n", [
				'<!-- wp:paragraph -->',
				'<p>This page uses the <strong>[[rwdp_assets]]</strong> shortcode by default. If you are using Elementor, you can replace the shortcode with a <strong>Loop Grid</strong> widget — set the Query Source to <strong>Custom Query</strong> and enter the Query ID: <strong>rwdp_top_level_assets</strong>. See the plugin docs for full setup instructions: <a href="/wp-content/plugins/rw-dealer-portal/docs/assets-elementor-loop-grid.md" target="_blank" rel="noopener">Creating Assets Pages with Elementor</a>.</p>',
				'<!-- /wp:paragraph -->',
				'<!-- wp:shortcode -->',
				'[rwdp_assets]',
				'<!-- /wp:shortcode -->',
			] ),
			'private' => true,
		],
		'account' => [
			'title'   => 'My Account',
			'slug'    => 'dealer-account',
			'content' => '[rwdp_my_account]',
			'private' => true,
		],
		'requests' => [
			'title'   => 'Contact Requests',
			'slug'    => 'dealer-requests',
			'content' => '[rwdp_my_requests]',
			'private' => true,
		],
		'edit_dealer' => [
			'title'   => 'Edit Dealer Profile',
			'slug'    => 'edit-dealer-profile',
			'content' => '[rwdp_edit_dealer]',
			'private' => true,
		],
	];

	foreach ( $sub_pages as $key => $page ) {
		$existing_post = ! empty( $existing[ $key ] ) ? get_post( $existing[ $key ] ) : null;
		if ( $existing_post && $existing_post->post_status === 'trash' ) {
			wp_update_post( [ 'ID' => $existing_post->ID, 'post_status' => 'publish', 'post_parent' => $dashboard_id ] );
			continue;
		}
		if ( $existing_post ) {
			continue;
		}
		// Use full path so get_page_by_path() finds the child, not a stale top-level page
		$full_path     = $dashboard_id ? 'dealer-portal/' . $page['slug'] : $page['slug'];
		$existing_page = get_page_by_path( $full_path );
		if ( $existing_page ) {
			$existing[ $key ] = $existing_page->ID;
			continue;
		}
		$page_id = wp_insert_post( [
			'post_title'     => $page['title'],
			'post_name'      => $page['slug'],
			'post_content'   => $page['content'],
			'post_status'    => 'publish',
			'post_type'      => 'page',
			'post_parent'    => $dashboard_id,
			'comment_status' => 'closed',
			'ping_status'    => 'closed',
		] );
		if ( $page_id && ! is_wp_error( $page_id ) ) {
			$existing[ $key ] = $page_id;
			update_post_meta( $page_id, '_rwdp_restrict_access', '1' );
			$restricted_ids[] = $page_id;
		}
	}

	update_option( 'rwdp_page_ids', $existing );

	// Wire login and dashboard IDs into settings
	if ( ! empty( $existing['login'] ) ) {
		$settings['login_page_id'] = $existing['login'];
	}
	if ( ! empty( $existing['dashboard'] ) ) {
		$settings['dashboard_page_id'] = $existing['dashboard'];
	}

	$settings['restricted_page_ids'] = array_unique( array_map( 'absint', $restricted_ids ) );

	update_option( 'rwdp_settings', $settings );
}

/**
 * Helper: get a portal page URL by key.
 *
 * @param string $key  One of: dashboard, login, assets, account, requests, edit_dealer, finder
 * @return string
 */
function rwdp_get_page_url( $key ) {
	$ids = get_option( 'rwdp_page_ids', [] );
	if ( ! empty( $ids[ $key ] ) ) {
		return get_permalink( $ids[ $key ] );
	}
	return home_url( '/' );
}

/**
 * Helper: get a portal page ID by key.
 *
 * @param string $key
 * @return int|0
 */
function rwdp_get_page_id( $key ) {
	$ids = get_option( 'rwdp_page_ids', [] );
	return ! empty( $ids[ $key ] ) ? absint( $ids[ $key ] ) : 0;
}
