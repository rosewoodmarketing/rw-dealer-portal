<?php
/**
 * Plugin Name: RW Dealer Portal
 * Description: A dealer portal for businesses that operate with a dealer network. Dealers log in to access digital assets. A public-facing Dealer Finder lets visitors search and contact dealers.
 * Version: 1.0.8
 * Author: Rosewood Marketing
 * Plugin URI: https://github.com/rosewoodmarketing/rw-dealer-portal
 * Requires at least: 6.5
 * Requires PHP: 8.1
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * steps for auto-update:
 * 1. Update header Version: and RWDP_VERSION to new version number
 * 2. Commit and push changes to GitHub
 * 3. Create new release on GitHub with tag matching version number (e.g. v1.0.1)
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'RWDP_VERSION',    '1.0.8' );
define( 'RWDP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'RWDP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Core
require_once RWDP_PLUGIN_DIR . 'includes/roles.php';
require_once RWDP_PLUGIN_DIR . 'includes/post-types.php';
require_once RWDP_PLUGIN_DIR . 'includes/taxonomies.php';
require_once RWDP_PLUGIN_DIR . 'includes/setup.php';

// Admin
require_once RWDP_PLUGIN_DIR . 'includes/admin-menu.php';
require_once RWDP_PLUGIN_DIR . 'includes/admin-settings.php';
require_once RWDP_PLUGIN_DIR . 'includes/admin-users.php';
require_once RWDP_PLUGIN_DIR . 'includes/meta-fields.php';

// Dealer CPT + geocoding
require_once RWDP_PLUGIN_DIR . 'includes/geocoding.php';

// Auth & Access
require_once RWDP_PLUGIN_DIR . 'includes/auth.php';
require_once RWDP_PLUGIN_DIR . 'includes/registration.php';
require_once RWDP_PLUGIN_DIR . 'includes/access-control.php';

// Portal pages (shortcodes)
require_once RWDP_PLUGIN_DIR . 'includes/dashboard.php';
require_once RWDP_PLUGIN_DIR . 'includes/assets-page.php';
require_once RWDP_PLUGIN_DIR . 'includes/account-page.php';
require_once RWDP_PLUGIN_DIR . 'includes/dealer-profile.php';
require_once RWDP_PLUGIN_DIR . 'includes/submissions.php';

// Dealer Finder
require_once RWDP_PLUGIN_DIR . 'includes/dealer-finder.php';

// Fluent Forms integration
require_once RWDP_PLUGIN_DIR . 'includes/fluent-forms.php';

// Elementor Widgets
require_once RWDP_PLUGIN_DIR . 'elementor/elementor-manager.php';

// GitHub Updater
require_once RWDP_PLUGIN_DIR . 'includes/github-updater.php';
if ( is_admin() && class_exists( '\RW_Dealer_Portal\Updater' ) ) {
	new \RW_Dealer_Portal\Updater();
}

// Activation / deactivation hooks
register_activation_hook( __FILE__, 'rwdp_activate' );
register_deactivation_hook( __FILE__, 'rwdp_deactivate' );

function rwdp_activate() {
	rwdp_create_roles();
	rwdp_register_post_types();
	rwdp_register_taxonomies();
	rwdp_create_portal_pages();
	rwdp_create_protected_uploads_dir();
	flush_rewrite_rules();
}

function rwdp_deactivate() {
	flush_rewrite_rules();
}

/**
 * WooCommerce compatibility — remove conflicting hooks on product category pages.
 */
function rwdp_disable_on_product_categories() {
	if ( ! function_exists( 'is_product_category' ) || ! is_product_category() ) {
		return;
	}
	remove_filter( 'template_include', 'rwdp_load_single_dealer_template', 5 );
	remove_filter( 'template_include', 'rwdp_load_archive_dealer_template', 5 );
	remove_action( 'wp_enqueue_scripts', 'rwdp_enqueue_dealer_finder_assets' );
}
add_action( 'wp', 'rwdp_disable_on_product_categories', 0 );

/**
 * Return a "Please log in" notice for portal shortcodes when the user lacks access.
 *
 * @return string HTML string.
 */
function rwdp_portal_login_prompt() {
	$login_url = rwdp_get_page_url( 'login' ) ?: wp_login_url( get_permalink() );
	return '<div class="rwdp-notice rwdp-notice--warning">' .
	       sprintf(
	           wp_kses(
	               /* translators: %s: login URL */
	               __( 'Please <a href="%s">log in</a> to access this page.', 'rw-dealer-portal' ),
	               [ 'a' => [ 'href' => [] ] ]
	           ),
	           esc_url( $login_url )
	       ) .
	       '</div>';
}

/**
 * Load the plugin's single-rw_dealer.php template.
 *
 * @param string $template
 * @return string
 */
function rwdp_load_single_dealer_template( $template ) {
	if ( is_singular( 'rw_dealer' ) ) {
		$plugin_tpl = RWDP_PLUGIN_DIR . 'templates/single-rw_dealer.php';
		if ( file_exists( $plugin_tpl ) ) {
			return $plugin_tpl;
		}
	}
	return $template;
}
add_filter( 'template_include', 'rwdp_load_single_dealer_template', 5 );

/**
 * Load the plugin's archive-rw_dealer.php template.
 *
 * @param string $template
 * @return string
 */
function rwdp_load_archive_dealer_template( $template ) {
	if ( is_post_type_archive( 'rw_dealer' ) || is_tax( 'rw_dealer_type' ) ) {
		$plugin_tpl = RWDP_PLUGIN_DIR . 'templates/archive-rw_dealer.php';
		if ( file_exists( $plugin_tpl ) ) {
			return $plugin_tpl;
		}
	}
	return $template;
}
add_filter( 'template_include', 'rwdp_load_archive_dealer_template', 5 );

/**
 * Load the plugin's single-rw_asset.php template.
 *
 * @param string $template
 * @return string
 */
function rwdp_load_single_asset_template( $template ) {
	if ( is_singular( 'rw_asset' ) ) {
		$plugin_tpl = RWDP_PLUGIN_DIR . 'templates/single-rw_asset.php';
		if ( file_exists( $plugin_tpl ) ) {
			return $plugin_tpl;
		}
	}
	return $template;
}
add_filter( 'template_include', 'rwdp_load_single_asset_template', 5 );
