<?php
/**
 * Archive template for rw_dealer post type.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

get_header();
?>
<div class="rwdp-dealer-archive">
	<header class="rwdp-dealer-archive__header">
		<h1><?php post_type_archive_title(); ?></h1>
		<?php if ( get_the_archive_description() ) : ?>
		<p class="rwdp-dealer-archive__desc"><?php echo wp_kses_post( get_the_archive_description() ); ?></p>
		<?php endif; ?>
	</header>

	<?php if ( have_posts() ) : ?>
	<div class="rwdp-dealer-archive__grid">
		<?php while ( have_posts() ) : the_post();
			$dealer_id    = get_the_ID();
			$city         = get_post_meta( $dealer_id, '_rwdp_city',         true );
			$state        = get_post_meta( $dealer_id, '_rwdp_state',        true );
			$phone        = get_post_meta( $dealer_id, '_rwdp_phone',        true );
			$logo_id      = get_post_meta( $dealer_id, '_rwdp_logo_id',      true );
			$dealer_types = get_the_terms( $dealer_id, 'rw_dealer_type' );
		?>
		<article id="post-<?php the_ID(); ?>" <?php post_class( 'rwdp-dealer-card' ); ?>>
			<?php if ( $logo_id ) : ?>
			<div class="rwdp-dealer-card__logo">
				<a href="<?php the_permalink(); ?>">
					<?php echo wp_get_attachment_image( $logo_id, 'medium', false, [ 'alt' => esc_attr( get_the_title() . ' logo' ) ] ); ?>
				</a>
			</div>
			<?php elseif ( has_post_thumbnail() ) : ?>
			<div class="rwdp-dealer-card__thumb">
				<a href="<?php the_permalink(); ?>">
					<?php the_post_thumbnail( 'medium', [ 'class' => 'rwdp-dealer-card__img' ] ); ?>
				</a>
			</div>
			<?php endif; ?>

			<div class="rwdp-dealer-card__body">
				<h2 class="rwdp-dealer-card__name">
					<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
				</h2>

				<?php if ( $dealer_types && ! is_wp_error( $dealer_types ) ) : ?>
				<div class="rwdp-dealer-card__types">
					<?php foreach ( $dealer_types as $type ) : ?>
					<span class="rwdp-tag"><?php echo esc_html( $type->name ); ?></span>
					<?php endforeach; ?>
				</div>
				<?php endif; ?>

				<?php if ( $city || $state ) : ?>
				<p class="rwdp-dealer-card__location">
					<?php echo esc_html( trim( "{$city}, {$state}" , ', ' ) ); ?>
				</p>
				<?php endif; ?>

				<?php if ( $phone ) : ?>
				<p class="rwdp-dealer-card__phone">
					<a href="tel:<?php echo esc_attr( preg_replace( '/[^\d+]/', '', $phone ) ); ?>"><?php echo esc_html( $phone ); ?></a>
				</p>
				<?php endif; ?>

				<a href="<?php the_permalink(); ?>" class="rwdp-btn rwdp-btn--outline rwdp-btn--sm">
					<?php esc_html_e( 'View Details', 'rw-dealer-portal' ); ?>
				</a>
			</div>
		</article>
		<?php endwhile; ?>
	</div>

	<div class="rwdp-dealer-archive__pagination">
		<?php the_posts_pagination( [
			'prev_text' => __( '&laquo; Previous', 'rw-dealer-portal' ),
			'next_text' => __( 'Next &raquo;',     'rw-dealer-portal' ),
		] ); ?>
	</div>

	<?php else : ?>
		<p><?php esc_html_e( 'No dealers found.', 'rw-dealer-portal' ); ?></p>
	<?php endif; ?>
</div>

<style>
.rwdp-dealer-archive{max-width:1100px;margin:0 auto;padding:20px 0}
.rwdp-dealer-archive__grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:24px;margin:24px 0}
.rwdp-dealer-card{border:1px solid #ddd;border-radius:6px;overflow:hidden;background:#fff}
.rwdp-dealer-card__logo,.rwdp-dealer-card__thumb{background:#f6f6f6;display:flex;align-items:center;justify-content:center;height:140px}
.rwdp-dealer-card__logo img{max-width:80%;max-height:100px;object-fit:contain}
.rwdp-dealer-card__img{width:100%;height:140px;object-fit:cover}
.rwdp-dealer-card__body{padding:16px}
.rwdp-dealer-card__name{font-size:1rem;font-weight:700;margin:0 0 8px}
.rwdp-dealer-card__name a{color:inherit;text-decoration:none}
.rwdp-dealer-card__name a:hover{color:#1a5276}
.rwdp-dealer-card__types{margin-bottom:8px}
.rwdp-dealer-card__location,.rwdp-dealer-card__phone{font-size:.875rem;color:#666;margin:4px 0}
.rwdp-dealer-archive__pagination{margin-top:32px}
</style>

<?php get_footer(); ?>
