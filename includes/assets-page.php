<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// =============================================================================
// HELPER: Protected file proxy URL
// All download links go through this endpoint — the real upload URL is never
// exposed in HTML, so direct-URL access by unauthenticated users is impossible.
// =============================================================================

/**
 * Return a proxied download URL for an attachment ID.
 *
 * @param int $attachment_id
 * @return string
 */
function rwdp_protected_file_url( $attachment_id ) {
	return add_query_arg( [
		'action' => 'rwdp_serve_file',
		'id'     => absint( $attachment_id ),
		'nonce'  => wp_create_nonce( 'rwdp_serve_file_' . absint( $attachment_id ) ),
	], admin_url( 'admin-ajax.php' ) );
}

// =============================================================================
// AJAX: Serve a protected file
// =============================================================================
add_action( 'wp_ajax_nopriv_rwdp_serve_file', 'rwdp_ajax_serve_file' );
add_action( 'wp_ajax_rwdp_serve_file',        'rwdp_ajax_serve_file' );

function rwdp_ajax_serve_file() {
	$id    = absint( $_GET['id'] ?? 0 );
	$nonce = sanitize_text_field( wp_unslash( $_GET['nonce'] ?? '' ) );

	if ( ! $id || ! wp_verify_nonce( $nonce, 'rwdp_serve_file_' . $id ) ) {
		status_header( 403 );
		exit( 'Forbidden' );
	}

	if ( ! rwdp_current_user_has_portal_access() ) {
		status_header( 403 );
		exit( 'Forbidden' );
	}

	$file_path = get_attached_file( $id );

	if ( ! $file_path || ! file_exists( $file_path ) ) {
		status_header( 404 );
		exit( 'Not Found' );
	}

	// Security: ensure the resolved path is inside the uploads directory.
	$upload_dir = wp_upload_dir();
	$real_file  = realpath( $file_path );
	$real_base  = realpath( $upload_dir['basedir'] );

	if ( $real_file === false || $real_base === false || strpos( $real_file, $real_base ) !== 0 ) {
		status_header( 403 );
		exit( 'Forbidden' );
	}

	$mime = get_post_mime_type( $id ) ?: 'application/octet-stream';
	$name = basename( $real_file );

	// Stream the file.
	nocache_headers();
	header( 'Content-Type: ' . $mime );
	header( 'Content-Disposition: attachment; filename="' . rawurlencode( $name ) . '"' );
	header( 'Content-Length: ' . filesize( $real_file ) );
	readfile( $real_file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_readfile
	exit;
}

// =============================================================================
// AJAX: Return image proxy URLs for a gallery section (ZIP builder)
// =============================================================================
add_action( 'wp_ajax_rwdp_get_gallery_images', 'rwdp_ajax_get_gallery_images' );

function rwdp_ajax_get_gallery_images() {
	$asset_id      = absint( $_POST['asset_id']      ?? 0 );
	$section_index = absint( $_POST['section_index'] ?? 0 );
	$nonce         = sanitize_text_field( wp_unslash( $_POST['nonce'] ?? '' ) );

	if ( ! wp_verify_nonce( $nonce, 'rwdp_gallery_zip_' . $asset_id . '_' . $section_index ) ) {
		wp_send_json_error( [ 'message' => __( 'Security check failed.', 'rw-dealer-portal' ) ] );
	}

	if ( ! rwdp_current_user_has_portal_access() ) {
		wp_send_json_error( [ 'message' => __( 'Access denied.', 'rw-dealer-portal' ) ] );
	}

	$sections = get_post_meta( $asset_id, '_rwdp_gallery_sections', true );
	$section  = is_array( $sections ) && isset( $sections[ $section_index ] ) ? $sections[ $section_index ] : null;

	if ( ! $section || empty( $section['images'] ) ) {
		wp_send_json_error( [ 'message' => __( 'No images found.', 'rw-dealer-portal' ) ] );
	}

	$images = [];
	foreach ( (array) $section['images'] as $img_id ) {
		$img_id = absint( $img_id );
		// Serve via proxy URL so the JS fetch also goes through auth.
		$url  = rwdp_protected_file_url( $img_id );
		$name = basename( get_attached_file( $img_id ) );
		if ( $url && $name ) {
			$images[] = [ 'url' => $url, 'name' => $name ];
		}
	}

	wp_send_json_success( [ 'images' => $images ] );
}

// =============================================================================
// SHORTCODE: [rwdp_assets] — clickable card grid
// =============================================================================

add_shortcode( 'rwdp_assets', 'rwdp_assets_shortcode' );

function rwdp_assets_shortcode() {
	if ( ! rwdp_current_user_has_portal_access() ) {
		return rwdp_portal_login_prompt();
	}

	wp_enqueue_style( 'rwdp-portal', RWDP_PLUGIN_URL . 'assets/css/portal.css', [ 'dashicons' ], RWDP_VERSION );

	// Get all asset categories (top-level only)
	$categories = get_terms( [
		'taxonomy'   => 'rw_asset_category',
		'hide_empty' => true,
		'parent'     => 0,
		'orderby'    => 'name',
		'order'      => 'ASC',
	] );

	$assets = get_posts( [
		'post_type'      => 'rw_asset',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'post_parent'    => 0,
		'orderby'        => 'menu_order',
		'order'          => 'ASC',
	] );

	ob_start();
	?>
	<div class="rwdp-portal rwdp-assets">
		<h2><?php esc_html_e( 'Digital Assets', 'rw-dealer-portal' ); ?></h2>
		<p class="rwdp-assets__intro"><?php esc_html_e( 'Browse and download assets for your dealership.', 'rw-dealer-portal' ); ?></p>

		<?php if ( ! empty( $categories ) && ! is_wp_error( $categories ) ) : ?>
			<nav class="rwdp-assets__tabs" aria-label="<?php esc_attr_e( 'Asset categories', 'rw-dealer-portal' ); ?>">
				<a href="#rwdp-asset-all" class="rwdp-assets__tab rwdp-assets__tab--active" data-target="all"><?php esc_html_e( 'All', 'rw-dealer-portal' ); ?></a>
				<?php foreach ( $categories as $cat ) : ?>
					<a href="#rwdp-asset-<?php echo absint( $cat->term_id ); ?>" class="rwdp-assets__tab" data-target="<?php echo absint( $cat->term_id ); ?>">
						<?php echo esc_html( $cat->name ); ?>
					</a>
				<?php endforeach; ?>
			</nav>
		<?php endif; ?>

		<?php if ( ! $assets ) : ?>
			<p><?php esc_html_e( 'No assets have been added yet. Check back soon.', 'rw-dealer-portal' ); ?></p>
		<?php else : ?>
			<div class="rwdp-assets__grid">
				<?php foreach ( $assets as $asset ) :
					$terms    = get_the_terms( $asset->ID, 'rw_asset_category' );
					$term_ids = ( $terms && ! is_wp_error( $terms ) ) ? array_map( function( $t ) { return absint( $t->term_id ); }, $terms ) : [];
					$term_str = implode( ' ', $term_ids );
					$thumb    = get_the_post_thumbnail( $asset->ID, 'medium' );
					$excerpt  = get_the_excerpt( $asset );
				?>
					<a href="<?php echo esc_url( get_permalink( $asset->ID ) ); ?>"
					   class="rwdp-asset-card"
					   data-categories="all <?php echo esc_attr( $term_str ); ?>">
						<?php if ( $thumb ) : ?>
							<div class="rwdp-asset-card__thumb"><?php echo $thumb; ?></div>
						<?php endif; ?>
						<div class="rwdp-asset-card__body">
							<h3 class="rwdp-asset-card__title"><?php echo esc_html( $asset->post_title ); ?></h3>
							<?php if ( $terms && ! is_wp_error( $terms ) ) : ?>
								<div class="rwdp-asset-card__cats">
									<?php foreach ( $terms as $term ) : ?>
										<span class="rwdp-tag"><?php echo esc_html( $term->name ); ?></span>
									<?php endforeach; ?>
								</div>
							<?php endif; ?>
							<?php if ( $excerpt ) : ?>
								<p class="rwdp-asset-card__excerpt"><?php echo esc_html( $excerpt ); ?></p>
							<?php endif; ?>
							<span class="rwdp-btn rwdp-btn--outline rwdp-asset-card__cta"><?php esc_html_e( 'View Assets →', 'rw-dealer-portal' ); ?></span>
						</div>
					</a>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</div>
	<?php
	return ob_get_clean();
}

// =============================================================================
// ADMIN META BOXES — Three repeater sections per asset post
// =============================================================================

add_action( 'add_meta_boxes',        'rwdp_register_asset_meta_boxes' );
add_action( 'save_post_rw_asset',    'rwdp_save_asset_meta', 10, 2 );
add_action( 'admin_footer-post.php', 'rwdp_asset_meta_admin_script' );
add_action( 'admin_footer-post-new.php', 'rwdp_asset_meta_admin_script' );

function rwdp_register_asset_meta_boxes() {
	$post_type = 'rw_asset';

	add_meta_box( 'rwdp_gallery_sections',  __( 'Gallery Sections', 'rw-dealer-portal' ),  'rwdp_render_gallery_sections_meta_box',  $post_type, 'normal', 'high' );
	add_meta_box( 'rwdp_video_sections',    __( 'Video Sections', 'rw-dealer-portal' ),    'rwdp_render_video_sections_meta_box',    $post_type, 'normal', 'default' );
	add_meta_box( 'rwdp_download_sections', __( 'Download Sections', 'rw-dealer-portal' ), 'rwdp_render_download_sections_meta_box', $post_type, 'normal', 'default' );
}

// ── Gallery Sections ──────────────────────────────────────────────────────────
function rwdp_render_gallery_sections_meta_box( $post ) {
	wp_nonce_field( 'rwdp_save_asset_meta', 'rwdp_asset_meta_nonce' );
	$sections = get_post_meta( $post->ID, '_rwdp_gallery_sections', true );
	$sections = is_array( $sections ) ? $sections : [];
	?>
	<p class="description"><?php esc_html_e( 'Each section has a title and a set of images. Dealers can download all images in a section as a ZIP.', 'rw-dealer-portal' ); ?></p>
	<div id="rwdp-gallery-repeater" class="rwdp-repeater">
		<?php foreach ( $sections as $i => $section ) : ?>
		<div class="rwdp-repeater__row" data-index="<?php echo absint( $i ); ?>">
			<div class="rwdp-repeater__handle">&#9776;</div>
			<div class="rwdp-repeater__fields">
				<div class="rwdp-repeater__field">
					<label><?php esc_html_e( 'Section Title', 'rw-dealer-portal' ); ?></label>
					<input type="text" name="rwdp_gallery_sections[<?php echo absint( $i ); ?>][title]"
						value="<?php echo esc_attr( $section['title'] ?? '' ); ?>" class="large-text" />
				</div>
				<div class="rwdp-repeater__field">
					<label><?php esc_html_e( 'Images', 'rw-dealer-portal' ); ?></label>
					<input type="hidden" name="rwdp_gallery_sections[<?php echo absint( $i ); ?>][images]"
						class="rwdp-image-ids"
						value="<?php echo esc_attr( implode( ',', array_map( 'absint', (array) ( $section['images'] ?? [] ) ) ) ); ?>" />
					<div class="rwdp-image-previews">
						<?php foreach ( (array) ( $section['images'] ?? [] ) as $img_id ) :
							$thumb = wp_get_attachment_image( absint( $img_id ), [ 60, 60 ] );
							if ( $thumb ) echo $thumb;
						endforeach; ?>
					</div>
					<button type="button" class="button rwdp-pick-images"><?php esc_html_e( 'Add / Change Images', 'rw-dealer-portal' ); ?></button>
				</div>
			</div>
			<button type="button" class="button-link rwdp-repeater__remove"><?php esc_html_e( '✕ Remove', 'rw-dealer-portal' ); ?></button>
		</div>
		<?php endforeach; ?>
	</div>
	<button type="button" class="button rwdp-repeater__add" data-repeater="rwdp-gallery-repeater" data-type="gallery">
		<?php esc_html_e( '+ Add Gallery Section', 'rw-dealer-portal' ); ?>
	</button>
	<?php
}

// ── Video Sections ────────────────────────────────────────────────────────────
function rwdp_render_video_sections_meta_box( $post ) {
	$sections = get_post_meta( $post->ID, '_rwdp_video_sections', true );
	$sections = is_array( $sections ) ? $sections : [];
	?>
	<p class="description"><?php esc_html_e( 'Each section has a title and a list of video URLs (YouTube or Vimeo), one per line.', 'rw-dealer-portal' ); ?></p>
	<div id="rwdp-video-repeater" class="rwdp-repeater">
		<?php foreach ( $sections as $i => $section ) : ?>
		<div class="rwdp-repeater__row" data-index="<?php echo absint( $i ); ?>">
			<div class="rwdp-repeater__handle">&#9776;</div>
			<div class="rwdp-repeater__fields">
				<div class="rwdp-repeater__field">
					<label><?php esc_html_e( 'Section Title', 'rw-dealer-portal' ); ?></label>
					<input type="text" name="rwdp_video_sections[<?php echo absint( $i ); ?>][title]"
						value="<?php echo esc_attr( $section['title'] ?? '' ); ?>" class="large-text" />
				</div>
				<div class="rwdp-repeater__field">
					<label><?php esc_html_e( 'Video URLs (one per line)', 'rw-dealer-portal' ); ?></label>
					<textarea name="rwdp_video_sections[<?php echo absint( $i ); ?>][urls]"
						rows="4" class="large-text"><?php echo esc_textarea( implode( "\n", (array) ( $section['urls'] ?? [] ) ) ); ?></textarea>
				</div>
			</div>
			<button type="button" class="button-link rwdp-repeater__remove"><?php esc_html_e( '✕ Remove', 'rw-dealer-portal' ); ?></button>
		</div>
		<?php endforeach; ?>
	</div>
	<button type="button" class="button rwdp-repeater__add" data-repeater="rwdp-video-repeater" data-type="video">
		<?php esc_html_e( '+ Add Video Section', 'rw-dealer-portal' ); ?>
	</button>
	<?php
}

// ── Download Sections ─────────────────────────────────────────────────────────
function rwdp_render_download_sections_meta_box( $post ) {
	$sections = get_post_meta( $post->ID, '_rwdp_download_sections', true );
	$sections = is_array( $sections ) ? $sections : [];
	?>
	<p class="description"><?php esc_html_e( 'Each section has a title and a set of downloadable files (PDFs, ZIPs, docs, etc.).', 'rw-dealer-portal' ); ?></p>
	<div id="rwdp-download-repeater" class="rwdp-repeater">
		<?php foreach ( $sections as $i => $section ) : ?>
		<div class="rwdp-repeater__row" data-index="<?php echo absint( $i ); ?>">
			<div class="rwdp-repeater__handle">&#9776;</div>
			<div class="rwdp-repeater__fields">
				<div class="rwdp-repeater__field">
					<label><?php esc_html_e( 'Section Title', 'rw-dealer-portal' ); ?></label>
					<input type="text" name="rwdp_download_sections[<?php echo absint( $i ); ?>][title]"
						value="<?php echo esc_attr( $section['title'] ?? '' ); ?>" class="large-text" />
				</div>
				<div class="rwdp-repeater__field">
					<label><?php esc_html_e( 'Files', 'rw-dealer-portal' ); ?></label>
					<input type="hidden" name="rwdp_download_sections[<?php echo absint( $i ); ?>][files]"
						class="rwdp-file-ids"
						value="<?php echo esc_attr( implode( ',', array_map( 'absint', (array) ( $section['files'] ?? [] ) ) ) ); ?>" />
					<ul class="rwdp-file-list">
						<?php foreach ( (array) ( $section['files'] ?? [] ) as $file_id ) :
							$fname = basename( get_attached_file( absint( $file_id ) ) );
							if ( $fname ) echo '<li>' . esc_html( $fname ) . '</li>';
						endforeach; ?>
					</ul>
					<button type="button" class="button rwdp-pick-files"><?php esc_html_e( 'Add / Change Files', 'rw-dealer-portal' ); ?></button>
				</div>
			</div>
			<button type="button" class="button-link rwdp-repeater__remove"><?php esc_html_e( '✕ Remove', 'rw-dealer-portal' ); ?></button>
		</div>
		<?php endforeach; ?>
	</div>
	<button type="button" class="button rwdp-repeater__add" data-repeater="rwdp-download-repeater" data-type="download">
		<?php esc_html_e( '+ Add Download Section', 'rw-dealer-portal' ); ?>
	</button>
	<?php
}

// ── Save ──────────────────────────────────────────────────────────────────────
function rwdp_save_asset_meta( $post_id, $post ) {
	if ( ! isset( $_POST['rwdp_asset_meta_nonce'] ) ||
	     ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['rwdp_asset_meta_nonce'] ) ), 'rwdp_save_asset_meta' ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
	if ( ! current_user_can( 'edit_rw_asset', $post_id ) ) return;

	// Gallery sections
	$raw_gallery = $_POST['rwdp_gallery_sections'] ?? [];
	$gallery_sections = [];
	if ( is_array( $raw_gallery ) ) {
		foreach ( $raw_gallery as $row ) {
			$title  = sanitize_text_field( wp_unslash( $row['title'] ?? '' ) );
			$images = array_filter( array_map( 'absint', explode( ',', sanitize_text_field( wp_unslash( $row['images'] ?? '' ) ) ) ) );
			$gallery_sections[] = [ 'title' => $title, 'images' => array_values( $images ) ];
		}
	}
	update_post_meta( $post_id, '_rwdp_gallery_sections', $gallery_sections );

	// Video sections
	$raw_video = $_POST['rwdp_video_sections'] ?? [];
	$video_sections = [];
	if ( is_array( $raw_video ) ) {
		foreach ( $raw_video as $row ) {
			$title = sanitize_text_field( wp_unslash( $row['title'] ?? '' ) );
			$lines = array_filter( array_map( 'esc_url_raw', explode( "\n", wp_unslash( $row['urls'] ?? '' ) ) ) );
			$video_sections[] = [ 'title' => $title, 'urls' => array_values( $lines ) ];
		}
	}
	update_post_meta( $post_id, '_rwdp_video_sections', $video_sections );

	// Download sections
	$raw_download = $_POST['rwdp_download_sections'] ?? [];
	$download_sections = [];
	if ( is_array( $raw_download ) ) {
		foreach ( $raw_download as $row ) {
			$title = sanitize_text_field( wp_unslash( $row['title'] ?? '' ) );
			$files = array_filter( array_map( 'absint', explode( ',', sanitize_text_field( wp_unslash( $row['files'] ?? '' ) ) ) ) );
			$download_sections[] = [ 'title' => $title, 'files' => array_values( $files ) ];
		}
	}
	update_post_meta( $post_id, '_rwdp_download_sections', $download_sections );
}

// ── Admin JS for repeaters ────────────────────────────────────────────────────
function rwdp_asset_meta_admin_script() {
	$screen = get_current_screen();
	if ( ! $screen || $screen->post_type !== 'rw_asset' ) return;
	?>
	<style>
	.rwdp-repeater { border:1px solid #ddd; border-radius:4px; padding:12px; margin-bottom:8px; background:#fafafa; }
	.rwdp-repeater__row { display:flex; gap:10px; align-items:flex-start; background:#fff; border:1px solid #e0e0e0; border-radius:3px; padding:10px; margin-bottom:8px; }
	.rwdp-repeater__handle { cursor:move; color:#aaa; font-size:18px; padding-top:4px; }
	.rwdp-repeater__fields { flex:1; display:flex; flex-direction:column; gap:8px; }
	.rwdp-repeater__field label { font-weight:600; display:block; margin-bottom:3px; }
	.rwdp-repeater__remove { color:#a00; align-self:flex-start; white-space:nowrap; padding-top:4px; }
	.rwdp-image-previews img { width:60px; height:60px; object-fit:cover; margin:2px; border:1px solid #ddd; }
	.rwdp-file-list { margin:4px 0 6px; padding-left:16px; }
	</style>
	<script>
	(function($){
		var rowTemplates = {
			gallery: function(idx) {
				return '<div class="rwdp-repeater__row" data-index="' + idx + '">' +
					'<div class="rwdp-repeater__handle">&#9776;</div>' +
					'<div class="rwdp-repeater__fields">' +
						'<div class="rwdp-repeater__field"><label><?php echo esc_js( __( 'Section Title', 'rw-dealer-portal' ) ); ?></label>' +
						'<input type="text" name="rwdp_gallery_sections[' + idx + '][title]" class="large-text" /></div>' +
						'<div class="rwdp-repeater__field"><label><?php echo esc_js( __( 'Images', 'rw-dealer-portal' ) ); ?></label>' +
						'<input type="hidden" name="rwdp_gallery_sections[' + idx + '][images]" class="rwdp-image-ids" value="" />' +
						'<div class="rwdp-image-previews"></div>' +
						'<button type="button" class="button rwdp-pick-images"><?php echo esc_js( __( 'Add / Change Images', 'rw-dealer-portal' ) ); ?></button></div>' +
					'</div>' +
					'<button type="button" class="button-link rwdp-repeater__remove"><?php echo esc_js( __( '✕ Remove', 'rw-dealer-portal' ) ); ?></button>' +
				'</div>';
			},
			video: function(idx) {
				return '<div class="rwdp-repeater__row" data-index="' + idx + '">' +
					'<div class="rwdp-repeater__handle">&#9776;</div>' +
					'<div class="rwdp-repeater__fields">' +
						'<div class="rwdp-repeater__field"><label><?php echo esc_js( __( 'Section Title', 'rw-dealer-portal' ) ); ?></label>' +
						'<input type="text" name="rwdp_video_sections[' + idx + '][title]" class="large-text" /></div>' +
						'<div class="rwdp-repeater__field"><label><?php echo esc_js( __( 'Video URLs (one per line)', 'rw-dealer-portal' ) ); ?></label>' +
						'<textarea name="rwdp_video_sections[' + idx + '][urls]" rows="4" class="large-text"></textarea></div>' +
					'</div>' +
					'<button type="button" class="button-link rwdp-repeater__remove"><?php echo esc_js( __( '✕ Remove', 'rw-dealer-portal' ) ); ?></button>' +
				'</div>';
			},
			download: function(idx) {
				return '<div class="rwdp-repeater__row" data-index="' + idx + '">' +
					'<div class="rwdp-repeater__handle">&#9776;</div>' +
					'<div class="rwdp-repeater__fields">' +
						'<div class="rwdp-repeater__field"><label><?php echo esc_js( __( 'Section Title', 'rw-dealer-portal' ) ); ?></label>' +
						'<input type="text" name="rwdp_download_sections[' + idx + '][title]" class="large-text" /></div>' +
						'<div class="rwdp-repeater__field"><label><?php echo esc_js( __( 'Files', 'rw-dealer-portal' ) ); ?></label>' +
						'<input type="hidden" name="rwdp_download_sections[' + idx + '][files]" class="rwdp-file-ids" value="" />' +
						'<ul class="rwdp-file-list"></ul>' +
						'<button type="button" class="button rwdp-pick-files"><?php echo esc_js( __( 'Add / Change Files', 'rw-dealer-portal' ) ); ?></button></div>' +
					'</div>' +
					'<button type="button" class="button-link rwdp-repeater__remove"><?php echo esc_js( __( '✕ Remove', 'rw-dealer-portal' ) ); ?></button>' +
				'</div>';
			}
		};

		// Add row
		$(document).on('click', '.rwdp-repeater__add', function(){
			var repeaterId = $(this).data('repeater');
			var type       = $(this).data('type');
			var $repeater  = $('#' + repeaterId);
			var idx        = $repeater.find('.rwdp-repeater__row').length;
			$repeater.append( rowTemplates[type](idx) );
		});

		// Remove row
		$(document).on('click', '.rwdp-repeater__remove', function(){
			$(this).closest('.rwdp-repeater__row').remove();
		});

		// Image picker
		$(document).on('click', '.rwdp-pick-images', function(){
			var $btn     = $(this);
			var $row     = $btn.closest('.rwdp-repeater__row');
			var $idsInput = $row.find('.rwdp-image-ids');
			var $previews = $row.find('.rwdp-image-previews');

			var frame = wp.media({
				title    : '<?php echo esc_js( __( 'Select Gallery Images', 'rw-dealer-portal' ) ); ?>',
				button   : { text: '<?php echo esc_js( __( 'Use selected images', 'rw-dealer-portal' ) ); ?>' },
				multiple : true,
				library  : { type: 'image' }
			});
			frame.on('select', function(){
				var ids    = [];
				var thumbs = '';
				frame.state().get('selection').each(function(a){
					ids.push( a.get('id') );
					thumbs += '<img src="' + ( a.get('sizes').thumbnail ? a.get('sizes').thumbnail.url : a.get('url') ) + '" />';
				});
				$idsInput.val( ids.join(',') );
				$previews.html( thumbs );
			});
			frame.open();
		});

		// File picker
		$(document).on('click', '.rwdp-pick-files', function(){
			var $btn      = $(this);
			var $row      = $btn.closest('.rwdp-repeater__row');
			var $idsInput = $row.find('.rwdp-file-ids');
			var $list     = $row.find('.rwdp-file-list');

			var frame = wp.media({
				title    : '<?php echo esc_js( __( 'Select Files', 'rw-dealer-portal' ) ); ?>',
				button   : { text: '<?php echo esc_js( __( 'Use selected files', 'rw-dealer-portal' ) ); ?>' },
				multiple : true
			});
			frame.on('select', function(){
				var ids   = [];
				var items = '';
				frame.state().get('selection').each(function(a){
					ids.push( a.get('id') );
					items += '<li>' + a.get('filename') + '</li>';
				});
				$idsInput.val( ids.join(',') );
				$list.html( items );
			});
			frame.open();
		});
	})(jQuery);
	</script>
	<?php
}

// =============================================================================
// FILE PROTECTION: Move download-section files into rwdp-protected/ on save
// Gallery images stay in regular uploads so thumbnails still render; only
// files listed in Download sections are relocated to the protected folder
// which Nginx blocks from direct HTTP access.
// =============================================================================
add_action( 'save_post_rw_asset', 'rwdp_protect_download_files', 20 );

function rwdp_protect_download_files( $post_id ) {
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
	if ( wp_is_post_revision( $post_id ) ) return;
	if ( ! current_user_can( 'edit_post', $post_id ) ) return;

	$download_sections = get_post_meta( $post_id, '_rwdp_download_sections', true );
	if ( ! is_array( $download_sections ) ) return;

	$file_ids = [];
	foreach ( $download_sections as $section ) {
		if ( ! empty( $section['files'] ) && is_array( $section['files'] ) ) {
			foreach ( $section['files'] as $id ) {
				$file_ids[] = absint( $id );
			}
		}
	}

	foreach ( array_unique( array_filter( $file_ids ) ) as $attachment_id ) {
		rwdp_move_attachment_to_protected( $attachment_id );
	}
}

/**
 * Move an attachment's physical file (and any WP-generated image sizes) into
 * the rwdp-protected/ uploads subdirectory, then update WP's stored metadata
 * so that get_attached_file() returns the new path and the proxy keeps working.
 *
 * @param int $attachment_id
 */
function rwdp_move_attachment_to_protected( $attachment_id ) {
	$file = get_attached_file( $attachment_id );
	if ( ! $file || ! file_exists( $file ) ) return;

	$upload_dir     = wp_upload_dir();
	$protected_base = trailingslashit( $upload_dir['basedir'] ) . 'rwdp-protected';

	// Already inside the protected folder — nothing to do.
	$real_file      = realpath( $file );
	$real_protected = file_exists( $protected_base ) ? realpath( $protected_base ) : null;
	if ( $real_file && $real_protected && strpos( $real_file, $real_protected ) === 0 ) return;

	if ( ! file_exists( $protected_base ) ) {
		wp_mkdir_p( $protected_base );
	}

	// Pick a unique filename inside the protected dir to avoid collisions.
	$filename    = wp_unique_filename( $protected_base, basename( $file ) );
	$target_path = trailingslashit( $protected_base ) . $filename;

	// phpcs:ignore WordPress.WP.AlternativeFunctions.rename_rename
	if ( ! @rename( $file, $target_path ) ) return;

	// Update _wp_attached_file (relative to uploads base dir).
	$new_relative = 'rwdp-protected/' . $filename;
	update_post_meta( $attachment_id, '_wp_attached_file', $new_relative );

	// Update attachment metadata so WP knows the new path.
	$meta = wp_get_attachment_metadata( $attachment_id );
	if ( is_array( $meta ) ) {
		// Move any generated image sizes if this attachment is an image.
		if ( ! empty( $meta['sizes'] ) && is_array( $meta['sizes'] ) ) {
			$old_dir = trailingslashit( dirname( $file ) );
			foreach ( $meta['sizes'] as &$size_data ) {
				$size_src = $old_dir . $size_data['file'];
				if ( file_exists( $size_src ) ) {
					// phpcs:ignore WordPress.WP.AlternativeFunctions.rename_rename
					@rename( $size_src, trailingslashit( $protected_base ) . $size_data['file'] );
				}
			}
			unset( $size_data );
		}
		$meta['file'] = $new_relative;
		wp_update_attachment_metadata( $attachment_id, $meta );
	}
}

