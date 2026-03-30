<?php
/**
 * Template Name: Single Dealer
 *
 * Used for single rw_dealer posts.
 * Elementor-compatible: if Elementor has a saved template for this post, it takes over.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

get_header();

while ( have_posts() ) :
	the_post();

	$dealer_id    = get_the_ID();
	$address      = get_post_meta( $dealer_id, '_rwdp_address',      true );
	$city         = get_post_meta( $dealer_id, '_rwdp_city',         true );
	$state        = get_post_meta( $dealer_id, '_rwdp_state',        true );
	$zip          = get_post_meta( $dealer_id, '_rwdp_zip',          true );
	$phone        = get_post_meta( $dealer_id, '_rwdp_phone',        true );
	$website      = get_post_meta( $dealer_id, '_rwdp_website',      true );
	$public_email = get_post_meta( $dealer_id, '_rwdp_public_email', true );
	$hours        = get_post_meta( $dealer_id, '_rwdp_hours',        true );
	$logo_id      = get_post_meta( $dealer_id, '_rwdp_logo_id',      true );
	$lat          = get_post_meta( $dealer_id, '_rwdp_lat',          true );
	$lng          = get_post_meta( $dealer_id, '_rwdp_lng',          true );

	$dealer_types = get_the_terms( $dealer_id, 'rw_dealer_type' );

	$settings     = get_option( 'rwdp_settings', [] );
	$maps_key     = $settings['google_maps_api_key'] ?? '';
	?>
	<article id="post-<?php the_ID(); ?>" <?php post_class( 'rwdp-dealer-single' ); ?>>

		<?php if ( has_post_thumbnail() ) : ?>
		<div class="rwdp-dealer-single__hero">
			<?php the_post_thumbnail( 'full', [ 'class' => 'rwdp-dealer-single__hero-img', 'loading' => 'eager' ] ); ?>
		</div>
		<?php endif; ?>

		<div class="rwdp-dealer-single__inner">

			<div class="rwdp-dealer-single__header">
				<?php if ( $logo_id ) : ?>
				<div class="rwdp-dealer-single__logo">
					<?php echo wp_get_attachment_image( $logo_id, 'medium', false, [ 'alt' => esc_attr( get_the_title() ) . ' logo' ] ); ?>
				</div>
				<?php endif; ?>
				<div>
					<h1 class="rwdp-dealer-single__name"><?php the_title(); ?></h1>
					<?php if ( $dealer_types && ! is_wp_error( $dealer_types ) ) : ?>
					<div class="rwdp-dealer-single__types">
						<?php foreach ( $dealer_types as $type ) : ?>
						<span class="rwdp-tag"><?php echo esc_html( $type->name ); ?></span>
						<?php endforeach; ?>
					</div>
					<?php endif; ?>
				</div>
			</div>

			<div class="rwdp-dealer-single__columns">

				<div class="rwdp-dealer-single__contact-info">
					<?php if ( $address ) : ?>
					<p>
						<strong><?php esc_html_e( 'Address', 'rw-dealer-portal' ); ?></strong><br>
						<?php echo esc_html( $address ); ?><br>
						<?php echo esc_html( "{$city}, {$state} {$zip}" ); ?>
					</p>
					<?php endif; ?>

					<?php if ( $phone ) : ?>
					<p>
						<strong><?php esc_html_e( 'Phone', 'rw-dealer-portal' ); ?></strong><br>
						<a href="tel:<?php echo esc_attr( preg_replace( '/[^\d+]/', '', $phone ) ); ?>"><?php echo esc_html( $phone ); ?></a>
					</p>
					<?php endif; ?>

					<?php if ( $public_email ) : ?>
					<p>
						<strong><?php esc_html_e( 'Email', 'rw-dealer-portal' ); ?></strong><br>
						<a href="mailto:<?php echo esc_attr( $public_email ); ?>"><?php echo esc_html( $public_email ); ?></a>
					</p>
					<?php endif; ?>

					<?php if ( $website ) : ?>
					<p>
						<strong><?php esc_html_e( 'Website', 'rw-dealer-portal' ); ?></strong><br>
						<a href="<?php echo esc_url( $website ); ?>" target="_blank" rel="noopener noreferrer">
							<?php echo esc_html( preg_replace( '#^https?://(www\.)?#', '', $website ) ); ?>
						</a>
					</p>
					<?php endif; ?>

					<?php if ( $hours ) : ?>
					<p>
						<strong><?php esc_html_e( 'Hours', 'rw-dealer-portal' ); ?></strong><br>
						<?php echo nl2br( esc_html( $hours ) ); ?>
					</p>
					<?php endif; ?>
				</div>

				<?php if ( $lat && $lng && $maps_key ) : ?>
				<div class="rwdp-dealer-single__map">
					<div id="rwdp-single-map" style="width:100%;height:320px;border-radius:6px;"></div>
				</div>
				<script>
				function rwdpInitSingleMap() {
					var latLng = { lat: <?php echo (float) $lat; ?>, lng: <?php echo (float) $lng; ?> };
					var mapEl  = document.getElementById('rwdp-single-map');
					if (!mapEl) return;
					var map = new google.maps.Map(mapEl, { zoom: 14, center: latLng });
					new google.maps.Marker({ position: latLng, map: map, title: <?php echo wp_json_encode( get_the_title() ); ?> });
				}
				</script>
				<script src="https://maps.googleapis.com/maps/api/js?key=<?php echo rawurlencode( $maps_key ); ?>&callback=rwdpInitSingleMap" async defer></script>
				<?php endif; ?>

			</div>

			<?php if ( get_the_content() ) : ?>
			<div class="rwdp-dealer-single__description entry-content">
				<?php the_content(); ?>
			</div>
			<?php endif; ?>

		</div>
	</article>
	<?php
endwhile;

get_footer();
