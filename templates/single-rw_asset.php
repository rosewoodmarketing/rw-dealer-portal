<?php
/**
 * Template: Single rw_asset
 *
 * Displays one asset post with its gallery, video, and download sections.
 * Access is gated: only portal users may view.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// Access gate — must be a portal user.
if ( ! rwdp_current_user_has_portal_access() ) {
	$login_url = rwdp_get_page_url( 'login' ) ?: wp_login_url( get_permalink() );
	wp_safe_redirect( add_query_arg( 'redirect_to', urlencode( get_permalink() ), $login_url ) );
	exit;
}

// Enqueue assets needed on this page.
wp_enqueue_style( 'rwdp-portal', RWDP_PLUGIN_URL . 'assets/css/portal.css', [ 'dashicons' ], RWDP_VERSION );
wp_enqueue_script( 'rwdp-jszip', 'https://cdn.jsdelivr.net/npm/jszip@3.10.1/dist/jszip.min.js', [], '3.10.1', true );
wp_enqueue_script( 'rwdp-gallery', RWDP_PLUGIN_URL . 'assets/js/gallery-downloads.js', [ 'jquery', 'rwdp-jszip' ], RWDP_VERSION, true );
wp_localize_script( 'rwdp-gallery', 'rwdpGallery', [
	'ajaxUrl'         => admin_url( 'admin-ajax.php' ),
	'downloadingText' => __( 'Preparing ZIP…', 'rw-dealer-portal' ),
	'downloadText'    => __( 'Download ZIP', 'rw-dealer-portal' ),
] );

get_header();

while ( have_posts() ) :
	the_post();

	$asset_id          = get_the_ID();
	$parent_id         = wp_get_post_parent_id( $asset_id );

	// Check for child assets.
	$children = get_posts( [
		'post_type'      => 'rw_asset',
		'post_status'    => 'publish',
		'post_parent'    => $asset_id,
		'posts_per_page' => -1,
		'orderby'        => 'menu_order',
		'order'          => 'ASC',
	] );

	$gallery_sections  = get_post_meta( $asset_id, '_rwdp_gallery_sections',  true );
	$video_sections    = get_post_meta( $asset_id, '_rwdp_video_sections',    true );
	$download_sections = get_post_meta( $asset_id, '_rwdp_download_sections', true );

	$gallery_sections  = is_array( $gallery_sections )  ? $gallery_sections  : [];
	$video_sections    = is_array( $video_sections )    ? $video_sections    : [];
	$download_sections = is_array( $download_sections ) ? $download_sections : [];

	// Back link: go to parent asset if this is a child, otherwise to the portal assets page.
	$back_url   = $parent_id ? get_permalink( $parent_id ) : rwdp_get_page_url( 'assets' );
	$back_label = $parent_id ? get_the_title( $parent_id ) : __( 'Back to Assets', 'rw-dealer-portal' );
	?>
	<div class="rwdp-portal rwdp-asset-single">

		<?php if ( $back_url && $back_url !== home_url( '/' ) ) : ?>
			<a href="<?php echo esc_url( $back_url ); ?>" class="rwdp-asset-single__back">
				&larr; <?php echo esc_html( $back_label ); ?>
			</a>
		<?php endif; ?>

		<header class="rwdp-asset-single__header">
			<?php if ( has_post_thumbnail() ) : ?>
				<div class="rwdp-asset-single__thumb">
					<?php the_post_thumbnail( 'large', [ 'loading' => 'eager' ] ); ?>
				</div>
			<?php endif; ?>
			<h1 class="rwdp-asset-single__title"><?php the_title(); ?></h1>

			<?php
			$asset_description = get_post_meta( $asset_id, '_rwdp_asset_description', true );
			if ( $asset_description ) : ?>
				<div class="rwdp-asset-single__description">
					<?php echo wp_kses_post( $asset_description ); ?>
				</div>
			<?php endif; ?>
		</header>

		<?php if ( get_the_content() ) : ?>
			<div class="rwdp-asset-single__content">
				<?php the_content(); ?>
			</div>
		<?php endif; ?>

		<?php if ( $children ) : ?>
			<?php // ---- Child asset grid ---- ?>
			<div class="rwdp-assets__grid rwdp-asset-children">
				<?php foreach ( $children as $child ) : ?>
					<a href="<?php echo esc_url( get_permalink( $child->ID ) ); ?>"
					   class="rwdp-asset-card">
						<?php $thumb = get_the_post_thumbnail( $child->ID, 'medium' ); ?>
						<?php if ( $thumb ) : ?>
							<div class="rwdp-asset-card__thumb"><?php echo wp_kses_post( $thumb ); ?></div>
						<?php endif; ?>
						<div class="rwdp-asset-card__body">
							<h3 class="rwdp-asset-card__title"><?php echo esc_html( $child->post_title ); ?></h3>
							<?php $excerpt = get_the_excerpt( $child ); ?>
							<?php if ( $excerpt ) : ?>
								<p class="rwdp-asset-card__excerpt"><?php echo esc_html( $excerpt ); ?></p>
							<?php endif; ?>
							<span class="rwdp-btn rwdp-btn--outline rwdp-asset-card__cta"><?php esc_html_e( 'View Assets →', 'rw-dealer-portal' ); ?></span>
						</div>
					</a>
				<?php endforeach; ?>
			</div>
		<?php else : ?>
			<?php
		// ----------------------------------------------------------------
		// Gallery sections
		// ----------------------------------------------------------------
		if ( $gallery_sections ) :
			foreach ( $gallery_sections as $idx => $section ) :
				$images = ! empty( $section['images'] ) ? (array) $section['images'] : [];
				if ( ! $images ) continue;
				$section_title = ! empty( $section['title'] ) ? $section['title'] : __( 'Gallery', 'rw-dealer-portal' );
				$zip_nonce     = wp_create_nonce( 'rwdp_gallery_zip_' . $asset_id . '_' . $idx );
		?>
			<section class="rwdp-asset-section rwdp-asset-section--gallery">
				<h2 class="rwdp-asset-section__title"><?php echo esc_html( $section_title ); ?></h2>
				<div class="rwdp-gallery-grid" data-gallery-id="<?php echo absint( $asset_id ); ?>" data-section-index="<?php echo absint( $idx ); ?>">
					<?php foreach ( $images as $img_id ) :
						$img_id   = absint( $img_id );
						$full_url = wp_get_attachment_image_url( $img_id, 'full' );
						$caption  = wp_get_attachment_caption( $img_id );
						$alt      = get_post_meta( $img_id, '_wp_attachment_image_alt', true );
						$filename = basename( get_attached_file( $img_id ) );
						if ( ! $full_url ) continue;
						// Unique slideshow key groups all images in this section into one navigable gallery.
						$slideshow_id = 'rwdp-gallery-' . absint( $asset_id ) . '-' . absint( $idx );
					?>
						<div class="rwdp-gallery-item">
							<a href="<?php echo esc_url( $full_url ); ?>"
							   class="rwdp-gallery-item__trigger"
							   data-elementor-open-lightbox="yes"
							   data-elementor-lightbox-slideshow="<?php echo esc_attr( $slideshow_id ); ?>"
							   data-elementor-lightbox-title="<?php echo esc_attr( $caption ?: $alt ?: $filename ); ?>">
								<?php echo wp_get_attachment_image( $img_id, 'medium', false, [
									'class'   => 'rwdp-gallery-item__img',
									'loading' => 'lazy',
									'alt'     => esc_attr( $caption ?: $alt ?: $filename ),
								] ); ?>
							</a>
							<div class="rwdp-gallery-item__footer">
								<?php if ( $caption ) : ?>
									<span class="rwdp-gallery-item__caption"><?php echo esc_html( $caption ); ?></span>
								<?php endif; ?>
								<a href="<?php echo esc_url( $full_url ); ?>"
								   class="rwdp-gallery-item__download"
								   download="<?php echo esc_attr( $filename ); ?>"
								   title="<?php esc_attr_e( 'Download', 'rw-dealer-portal' ); ?>">
									<span class="dashicons dashicons-download" aria-hidden="true"></span>
								</a>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
				<button type="button"
				        class="rwdp-btn rwdp-btn--outline rwdp-zip-download"
				        data-gallery-id="<?php echo absint( $asset_id ); ?>"
				        data-section-index="<?php echo absint( $idx ); ?>"
				        data-nonce="<?php echo esc_attr( $zip_nonce ); ?>">
					<?php esc_html_e( 'Download All (ZIP)', 'rw-dealer-portal' ); ?>
				</button>
			</section>
		<?php
			endforeach;
		endif;

		// ----------------------------------------------------------------
		// Video sections
		// ----------------------------------------------------------------
		if ( $video_sections ) :
			foreach ( $video_sections as $section ) :
				$urls = ! empty( $section['urls'] ) ? (array) $section['urls'] : [];
				if ( ! $urls ) continue;
				$section_title = ! empty( $section['title'] ) ? $section['title'] : __( 'Videos', 'rw-dealer-portal' );
		?>
			<section class="rwdp-asset-section rwdp-asset-section--video">
				<h2 class="rwdp-asset-section__title"><?php echo esc_html( $section_title ); ?></h2>
				<?php foreach ( $urls as $url ) :
					$url = esc_url_raw( $url );
					if ( ! $url ) continue;
					$embed = wp_oembed_get( $url );
					if ( $embed ) :
				?>
					<div class="rwdp-video-embed"><?php echo wp_kses_post( $embed ); ?></div>
				<?php
					else :
				?>
					<p><a href="<?php echo esc_url( $url ); ?>" class="rwdp-btn rwdp-btn--outline" target="_blank" rel="noopener noreferrer">
						<?php esc_html_e( 'Watch Video', 'rw-dealer-portal' ); ?>
					</a></p>
				<?php
					endif;
				endforeach;
				?>
			</section>
		<?php
			endforeach;
		endif;

		// ----------------------------------------------------------------
		// Download sections
		// ----------------------------------------------------------------
		if ( $download_sections ) :
			foreach ( $download_sections as $section ) :
				$files = ! empty( $section['files'] ) ? (array) $section['files'] : [];
				if ( ! $files ) continue;
				$section_title = ! empty( $section['title'] ) ? $section['title'] : __( 'Downloads', 'rw-dealer-portal' );
		?>
			<section class="rwdp-asset-section rwdp-asset-section--downloads">
				<h2 class="rwdp-asset-section__title"><?php echo esc_html( $section_title ); ?></h2>
				<ul class="rwdp-download-list">
					<?php foreach ( $files as $file_id ) :
						$file_id   = absint( $file_id );
						$proxy_url = rwdp_protected_file_url( $file_id );
						$filename  = basename( get_attached_file( $file_id ) );
						if ( ! $filename ) continue;
					?>
						<li class="rwdp-download-list__item">
							<a href="<?php echo esc_url( $proxy_url ); ?>"
							   class="rwdp-btn rwdp-btn--outline"
							   download>
								<span class="dashicons dashicons-download" aria-hidden="true"></span>
								<?php echo esc_html( $filename ); ?>
							</a>
						</li>
					<?php endforeach; ?>
				</ul>
			</section>
		<?php
			endforeach;
		endif;
		?>

		<?php endif; // end if ( $children ) : ... else ?>

	</div><!-- .rwdp-asset-single -->
	<?php

endwhile;

get_footer();
