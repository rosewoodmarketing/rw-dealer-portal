<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Register the top-level admin menu and all submenus for RW Dealer Portal.
 * The menu is visible to admins and portal managers.
 */
add_action( 'admin_menu', 'rwdp_register_admin_menu' );

function rwdp_register_admin_menu() {
	$pending_count = rwdp_get_pending_user_count();
	$pending_badge = $pending_count ? ' <span class="awaiting-mod">' . absint( $pending_count ) . '</span>' : '';

	// Top-level menu
	add_menu_page(
		__( 'RW Dealer Portal', 'rw-dealer-portal' ),
		__( 'Dealer Portal', 'rw-dealer-portal' ),
		'manage_rwdp_portal',
		'rw-dealer-portal',
		'rwdp_admin_dashboard_page',
		'dashicons-store',
		25
	);

	// Dashboard (same as top-level)
	add_submenu_page(
		'rw-dealer-portal',
		__( 'Portal Overview', 'rw-dealer-portal' ),
		__( 'Overview', 'rw-dealer-portal' ),
		'manage_rwdp_portal',
		'rw-dealer-portal',
		'rwdp_admin_dashboard_page'
	);

	// Dealers CPT
	add_submenu_page(
		'rw-dealer-portal',
		__( 'Dealers', 'rw-dealer-portal' ),
		__( 'Dealers', 'rw-dealer-portal' ),
		'edit_rw_dealers',
		'edit.php?post_type=rw_dealer'
	);

	add_submenu_page(
		'rw-dealer-portal',
		__( 'Add New Dealer', 'rw-dealer-portal' ),
		__( 'Add New Dealer', 'rw-dealer-portal' ),
		'edit_rw_dealers',
		'post-new.php?post_type=rw_dealer'
	);

	// Assets CPT
	add_submenu_page(
		'rw-dealer-portal',
		__( 'Assets', 'rw-dealer-portal' ),
		__( 'Assets', 'rw-dealer-portal' ),
		'edit_rw_assets',
		'edit.php?post_type=rw_asset'
	);

	add_submenu_page(
		'rw-dealer-portal',
		__( 'Add New Asset', 'rw-dealer-portal' ),
		__( 'Add New Asset', 'rw-dealer-portal' ),
		'edit_rw_assets',
		'post-new.php?post_type=rw_asset'
	);

	// Contact Submissions
	add_submenu_page(
		'rw-dealer-portal',
		__( 'Contact Submissions', 'rw-dealer-portal' ),
		__( 'Contact Submissions', 'rw-dealer-portal' ),
		'view_rwdp_submissions',
		'rwdp-submissions',
		'rwdp_admin_submissions_page'
	);

	// Pending Registrations
	add_submenu_page(
		'rw-dealer-portal',
		__( 'Pending Registrations', 'rw-dealer-portal' ),
		__( 'Pending Registrations', 'rw-dealer-portal' ) . $pending_badge,
		'manage_rwdp_portal',
		'rwdp-pending-registrations',
		'rwdp_admin_pending_registrations_page'
	);

	// Help / Docs
	add_submenu_page(
		'rw-dealer-portal',
		__( 'Help & Docs', 'rw-dealer-portal' ),
		__( 'Help & Docs', 'rw-dealer-portal' ),
		'manage_rwdp_portal',
		'rwdp-docs',
		'rwdp_admin_docs_page'
	);

	// Settings
	add_submenu_page(
		'rw-dealer-portal',
		__( 'Portal Settings', 'rw-dealer-portal' ),
		__( 'Settings', 'rw-dealer-portal' ),
		'manage_options',
		'rwdp-settings',
		'rwdp_admin_settings_page'
	);

	// Dealer Types taxonomy — admin only
	add_submenu_page(
		'rw-dealer-portal',
		__( 'Dealer Types', 'rw-dealer-portal' ),
		__( 'Dealer Types', 'rw-dealer-portal' ),
		'manage_options',
		'edit-tags.php?taxonomy=rw_dealer_type&post_type=rw_dealer'
	);
}

/**
 * Keep "Dealer Portal" highlighted in the admin sidebar when managing Dealer Types.
 */
add_filter( 'parent_file', 'rwdp_dealer_types_parent_file' );
function rwdp_dealer_types_parent_file( $parent_file ) {
	$screen = get_current_screen();
	if ( $screen && 'rw_dealer_type' === $screen->taxonomy ) {
		$parent_file = 'rw-dealer-portal';
	}
	return $parent_file;
}

add_filter( 'submenu_file', 'rwdp_dealer_types_submenu_file' );
function rwdp_dealer_types_submenu_file( $submenu_file ) {
	$screen = get_current_screen();
	if ( $screen && 'rw_dealer_type' === $screen->taxonomy ) {
		$submenu_file = 'edit-tags.php?taxonomy=rw_dealer_type&post_type=rw_dealer';
	}
	return $submenu_file;
}

/**
 * Portal overview dashboard page.
 */
function rwdp_admin_dashboard_page() {
	$dealer_count     = wp_count_posts( 'rw_dealer' )->publish ?? 0;
	$asset_count      = wp_count_posts( 'rw_asset' )->publish ?? 0;
	$submission_count = wp_count_posts( 'rw_submission' )->publish ?? 0;
	$pending_count    = rwdp_get_pending_user_count();

	$cards = [
		[
			'icon'        => 'dashicons-store',
			'title'       => __( 'Dealers', 'rw-dealer-portal' ),
			'count'       => absint( $dealer_count ),
			'count_label' => __( 'Total', 'rw-dealer-portal' ),
			'description' => __( 'Manage your network of authorized dealers.', 'rw-dealer-portal' ),
			'primary_url' => admin_url( 'edit.php?post_type=rw_dealer' ),
			'primary_txt' => __( 'Manage Dealers', 'rw-dealer-portal' ),
			'secondary_url' => admin_url( 'post-new.php?post_type=rw_dealer' ),
			'secondary_txt' => __( 'Add New Dealer', 'rw-dealer-portal' ),
			'alert'       => false,
		],
		[
			'icon'        => 'dashicons-media-document',
			'title'       => __( 'Assets', 'rw-dealer-portal' ),
			'count'       => absint( $asset_count ),
			'count_label' => __( 'Items', 'rw-dealer-portal' ),
			'description' => __( 'Control your digital brand asset library. Upload photos, brochures, videos, and more', 'rw-dealer-portal' ),
			'primary_url' => admin_url( 'edit.php?post_type=rw_asset' ),
			'primary_txt' => __( 'Manage Assets', 'rw-dealer-portal' ),
			'secondary_url' => admin_url( 'post-new.php?post_type=rw_asset' ),
			'secondary_txt' => __( 'Add New Asset', 'rw-dealer-portal' ),
			'alert'       => false,
		],
		[
			'icon'        => 'dashicons-feedback',
			'title'       => __( 'Form Submissions', 'rw-dealer-portal' ),
			'count'       => absint( $submission_count ),
			'count_label' => __( 'Total', 'rw-dealer-portal' ),
			'description' => __( 'Review customer inquiries and dealer contact requests submitted through the Dealer Finder.', 'rw-dealer-portal' ),
			'primary_url' => admin_url( 'admin.php?page=rwdp-submissions' ),
			'primary_txt' => __( 'View Submissions', 'rw-dealer-portal' ),
			'secondary_url' => admin_url( 'admin.php?page=rwdp-settings&tab=contact' ),
			'secondary_txt' => __( 'Contact Settings', 'rw-dealer-portal' ),
			'alert'       => false,
		],
		[
			'icon'        => 'dashicons-groups',
			'title'       => __( 'Pending Registrations', 'rw-dealer-portal' ),
			'count'       => absint( $pending_count ),
			'count_label' => __( 'Pending', 'rw-dealer-portal' ),
			'description' => __( 'Review and approve dealer account applications. Assign applicants to an existing dealer profile or create a new one.', 'rw-dealer-portal' ),
			'primary_url' => admin_url( 'admin.php?page=rwdp-pending-registrations' ),
			'primary_txt' => __( 'Review Applications', 'rw-dealer-portal' ),
			'secondary_url' => admin_url( 'user-new.php' ),
			'secondary_txt' => __( 'Add New User', 'rw-dealer-portal' ),
			'alert'       => $pending_count > 0,
		],
	];
	?>
	<div class="wrap rwdp-overview-wrap">
		<h1 class="rwdp-overview-heading">
			<span class="dashicons dashicons-store" style="font-size:28px;width:28px;height:28px;margin-right:8px;vertical-align:middle;"></span>
			<?php esc_html_e( 'Dealer Portal', 'rw-dealer-portal' ); ?>
		</h1>

		<div class="rwdp-overview-grid">
			<?php foreach ( $cards as $card ) : ?>
			<div class="rwdp-overview-card<?php echo $card['alert'] ? ' rwdp-overview-card--alert' : ''; ?>">

				<div class="rwdp-overview-card__header">
					<div class="rwdp-overview-card__icon-title">
						<span class="dashicons <?php echo esc_attr( $card['icon'] ); ?> rwdp-overview-card__icon"></span>
						<span class="rwdp-overview-card__title"><?php echo esc_html( $card['title'] ); ?></span>
					</div>
					<span class="rwdp-overview-card__badge<?php echo $card['alert'] ? ' rwdp-overview-card__badge--alert' : ''; ?>">
						<?php echo absint( $card['count'] ); ?> <?php echo esc_html( strtoupper( $card['count_label'] ) ); ?>
					</span>
				</div>

				<div class="rwdp-overview-card__divider"></div>

				<p class="rwdp-overview-card__desc"><?php echo esc_html( $card['description'] ); ?></p>

				<div class="rwdp-overview-card__actions">
					<a href="<?php echo esc_url( $card['primary_url'] ); ?>" class="rwdp-overview-btn rwdp-overview-btn--primary">
						<?php echo esc_html( $card['primary_txt'] ); ?>
					</a>
					<a href="<?php echo esc_url( $card['secondary_url'] ); ?>" class="rwdp-overview-btn rwdp-overview-btn--secondary">
						<?php echo esc_html( $card['secondary_txt'] ); ?>
					</a>
				</div>

			</div>
			<?php endforeach; ?>
		</div>

		<?php if ( current_user_can( 'manage_options' ) ) : ?>
		<div class="rwdp-overview-footer">
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=rwdp-settings' ) ); ?>" class="rwdp-overview-settings-link">
				<span class="dashicons dashicons-admin-settings"></span>
				<?php esc_html_e( 'Portal Settings', 'rw-dealer-portal' ); ?>
			</a>
			<a href="<?php echo esc_url( admin_url( 'edit-tags.php?taxonomy=rw_dealer_type&post_type=rw_dealer' ) ); ?>" class="rwdp-overview-settings-link">
				<span class="dashicons dashicons-tag"></span>
				<?php esc_html_e( 'Dealer Types', 'rw-dealer-portal' ); ?>
			</a>
		</div>
		<?php endif; ?>
	</div>
	<?php
}

/**
 * Helper: count users with pending status.
 */
function rwdp_get_pending_user_count() {
	$users = get_users( [
		'meta_key'   => '_rwdp_account_status',
		'meta_value' => 'pending',
		'count_total'=> true,
		'fields'     => 'ID',
		'number'     => -1,
	] );
	return count( $users );
}

/**
 * Plugin docs page in wp-admin.
 */
function rwdp_admin_docs_page() {
	$guides = rwdp_get_docs_guides();
	if ( empty( $guides ) ) {
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'RW Dealer Portal - Help & Docs', 'rw-dealer-portal' ); ?></h1>
			<div class="notice notice-warning"><p><?php esc_html_e( 'No docs guides were found.', 'rw-dealer-portal' ); ?></p></div>
		</div>
		<?php
		return;
	}

	$active_slug  = rwdp_get_active_docs_guide_slug( $guides );
	$active_guide = $guides[ $active_slug ];
	$raw_markdown = file_get_contents( $active_guide['path'] ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
	$raw_markdown = is_string( $raw_markdown ) ? $raw_markdown : '';
	$guide_html   = rwdp_render_markdown_for_admin_docs( $raw_markdown );
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'RW Dealer Portal - Help & Docs', 'rw-dealer-portal' ); ?></h1>
		<p><?php esc_html_e( 'Use these guides to configure Elementor and core plugin workflows.', 'rw-dealer-portal' ); ?></p>

		<div style="display:grid;grid-template-columns:280px 1fr;gap:16px;align-items:start;max-width:1300px;">
			<div class="card" style="padding:12px;position:sticky;top:32px;">
				<h2 style="margin:4px 0 12px;"><?php esc_html_e( 'Guides', 'rw-dealer-portal' ); ?></h2>
				<ul style="margin:0;padding-left:16px;">
					<?php foreach ( $guides as $slug => $guide ) : ?>
						<li style="margin:0 0 10px;">
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=rwdp-docs&guide=' . rawurlencode( $slug ) ) ); ?>"<?php echo $slug === $active_slug ? ' style="font-weight:600;"' : ''; ?>>
								<?php echo esc_html( $guide['title'] ); ?>
							</a>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>

			<div class="card" style="padding:18px;max-width:800px;width:100%;">
				<?php echo $guide_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- rendered via controlled markdown parser and wp_kses. ?>
			</div>
		</div>
	</div>
	<?php
}

/**
 * Discover docs guides from the plugin docs directory.
 *
 * @return array<string,array{title:string,path:string}>
 */
function rwdp_get_docs_guides() {
	$guides = [];
	$files  = glob( RWDP_PLUGIN_DIR . 'docs/*.md' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_glob

	if ( ! is_array( $files ) ) {
		return $guides;
	}

	sort( $files, SORT_NATURAL | SORT_FLAG_CASE );

	foreach ( $files as $path ) {
		$basename = basename( $path, '.md' );
		$slug     = sanitize_key( $basename );
		$title    = ucwords( str_replace( '-', ' ', $basename ) );

		$contents = file_get_contents( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		if ( is_string( $contents ) && preg_match( '/^#\s+(.+)$/m', $contents, $m ) ) {
			$title = trim( wp_strip_all_tags( $m[1] ) );
		}

		$guides[ $slug ] = [
			'title' => $title,
			'path'  => $path,
		];
	}

	return $guides;
}

/**
 * Resolve active guide slug from query string.
 *
 * @param array<string,array{title:string,path:string}> $guides Guides map.
 * @return string
 */
function rwdp_get_active_docs_guide_slug( array $guides ) {
	$requested = sanitize_key( wp_unslash( $_GET['guide'] ?? '' ) );
	if ( $requested && isset( $guides[ $requested ] ) ) {
		return $requested;
	}

	return (string) array_key_first( $guides );
}

/**
 * Convert markdown into safe admin HTML.
 * Supports headings, rules, blockquotes, ordered/unordered lists, tables,
 * and inline bold/code for plugin docs.
 *
 * @param string $markdown Raw markdown.
 * @return string
 */
function rwdp_render_markdown_for_admin_docs( $markdown ) {
	$markdown = str_replace( [ "\r\n", "\r" ], "\n", (string) $markdown );
	$lines    = explode( "\n", $markdown );
	$html     = '';
	$count    = count( $lines );
	$i        = 0;

	while ( $i < $count ) {
		$line    = $lines[ $i ];
		$trimmed = trim( $line );

		if ( '' === $trimmed ) {
			$i++;
			continue;
		}

		if ( preg_match( '/^(#{1,6})\s+(.+)$/', $trimmed, $m ) ) {
			$level = strlen( $m[1] );
			$html .= '<h' . $level . '>' . rwdp_docs_inline_markdown( $m[2] ) . '</h' . $level . '>';
			$i++;
			continue;
		}

		if ( preg_match( '/^---+$/', $trimmed ) ) {
			$html .= '<hr />';
			$i++;
			continue;
		}

		if ( preg_match( '/^>\s?(.+)$/', $trimmed ) ) {
			$quote_lines = [];
			while ( $i < $count && preg_match( '/^>\s?(.+)$/', trim( $lines[ $i ] ), $qm ) ) {
				$quote_lines[] = rwdp_docs_inline_markdown( $qm[1] );
				$i++;
			}
			$html .= '<blockquote><p>' . implode( ' ', $quote_lines ) . '</p></blockquote>';
			continue;
		}

		if ( preg_match( '/^\|.*\|$/', $trimmed ) && $i + 1 < $count && preg_match( '/^\|?\s*:?-{3,}:?\s*(\|\s*:?-{3,}:?\s*)+\|?$/', trim( $lines[ $i + 1 ] ) ) ) {
			$table_rows = [];
			while ( $i < $count && preg_match( '/^\|.*\|$/', trim( $lines[ $i ] ) ) ) {
				$table_rows[] = trim( $lines[ $i ] );
				$i++;
			}

			$header_cells = array_values( array_filter( array_map( 'trim', explode( '|', trim( $table_rows[0], '|' ) ) ), 'strlen' ) );
			$body_rows    = array_slice( $table_rows, 2 );

			$html .= '<table class="widefat striped"><thead><tr>';
			foreach ( $header_cells as $cell ) {
				$html .= '<th>' . rwdp_docs_inline_markdown( $cell ) . '</th>';
			}
			$html .= '</tr></thead><tbody>';

			foreach ( $body_rows as $row_line ) {
				$cells = array_values( array_filter( array_map( 'trim', explode( '|', trim( $row_line, '|' ) ) ), 'strlen' ) );
				if ( empty( $cells ) ) {
					continue;
				}
				$html .= '<tr>';
				foreach ( $cells as $cell ) {
					$html .= '<td>' . rwdp_docs_inline_markdown( $cell ) . '</td>';
				}
				$html .= '</tr>';
			}

			$html .= '</tbody></table>';
			continue;
		}

		if ( preg_match( '/^\d+\.\s+(.+)$/', $trimmed ) ) {
			$html .= '<ol>';
			while ( $i < $count && preg_match( '/^\d+\.\s+(.+)$/', trim( $lines[ $i ] ), $m2 ) ) {
				$html .= '<li>' . rwdp_docs_inline_markdown( $m2[1] ) . '</li>';
				$i++;
			}
			$html .= '</ol>';
			continue;
		}

		if ( preg_match( '/^-\s+(.+)$/', $trimmed ) ) {
			$html .= '<ul>';
			while ( $i < $count && preg_match( '/^-\s+(.+)$/', trim( $lines[ $i ] ), $m3 ) ) {
				$html .= '<li>' . rwdp_docs_inline_markdown( $m3[1] ) . '</li>';
				$i++;
			}
			$html .= '</ul>';
			continue;
		}

		$paragraph_lines = [];
		while ( $i < $count ) {
			$peek = trim( $lines[ $i ] );
			if ( '' === $peek ) {
				$i++;
				break;
			}
			if ( preg_match( '/^(#{1,6})\s+/', $peek ) || preg_match( '/^---+$/', $peek ) || preg_match( '/^>\s?/', $peek ) || preg_match( '/^\d+\.\s+/', $peek ) || preg_match( '/^-\s+/', $peek ) || preg_match( '/^\|.*\|$/', $peek ) ) {
				break;
			}
			$paragraph_lines[] = $peek;
			$i++;
		}

		if ( ! empty( $paragraph_lines ) ) {
			$html .= '<p>' . rwdp_docs_inline_markdown( implode( ' ', $paragraph_lines ) ) . '</p>';
		}
	}

	$allowed = [
		'h1'         => [],
		'h2'         => [],
		'h3'         => [],
		'h4'         => [],
		'h5'         => [],
		'h6'         => [],
		'p'          => [],
		'hr'         => [],
		'blockquote' => [],
		'ol'         => [],
		'ul'         => [],
		'li'         => [],
		'table'      => [ 'class' => [] ],
		'thead'      => [],
		'tbody'      => [],
		'tr'         => [],
		'th'         => [],
		'td'         => [],
		'strong'     => [],
		'code'       => [],
	];

	return wp_kses( $html, $allowed );
}

/**
 * Render inline markdown fragments safely.
 *
 * @param string $text Inline markdown text.
 * @return string
 */
function rwdp_docs_inline_markdown( $text ) {
	$text = esc_html( (string) $text );
	$text = preg_replace( '/\*\*(.+?)\*\*/', '<strong>$1</strong>', $text );
	$text = preg_replace( '/`([^`]+)`/', '<code>$1</code>', $text );
	return (string) $text;
}
