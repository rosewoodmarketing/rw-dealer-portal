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
	if ( empty( $existing['dashboard'] ) || ! get_post( $existing['dashboard'] ) ) {
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
	$dashboard_id = ! empty( $existing['dashboard'] ) ? (int) $existing['dashboard'] : 0;

	// -------------------------------------------------------------------------
	// Step 2: Top-level pages (not children of Dealer Portal)
	// -------------------------------------------------------------------------
	$top_level_pages = [
		'login' => [
			'title'   => 'Portal Login',
			'slug'    => 'portal-login',
			'content' => '[rwdp_login_form]',
			'private' => false,
		],
		'finder' => [
			'title'   => 'Dealer Finder',
			'slug'    => 'dealer-finder',
			'content' => '[rwdp_dealer_finder]',
			'private' => false,
		],
	];

	foreach ( $top_level_pages as $key => $page ) {
		if ( ! empty( $existing[ $key ] ) && get_post( $existing[ $key ] ) ) {
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
			'content' => '[rwdp_assets]',
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
		if ( ! empty( $existing[ $key ] ) && get_post( $existing[ $key ] ) ) {
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
