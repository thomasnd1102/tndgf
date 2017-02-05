<?php
get_header();
if ( have_posts() ) {
	while ( have_posts() ) : the_post();
		$post_id = get_the_ID();
		$sidebar_position = ct_get_sidebar_position( $post_id );
		$content_class = 'post-content';
		if ( 'no' != $sidebar_position ) $content_class .= ' col-md-9';

		$slider_active = get_post_meta( $post_id, '_rev_slider', true );
		$slider        = ( $slider_active == '' ) ? 'Deactivated' : $slider_active;
		if ( class_exists( 'RevSlider' ) && $slider != 'Deactivated' ) {
			echo '<div id="slideshow">';
			putRevSlider( $slider );
			echo '</div>';
		} else {
			$header_img_scr = ct_get_header_image_src( $post_id );
			if ( ! empty( $header_img_scr ) ) {
				$header_img_height = ct_get_header_image_height( $post_id );
				$header_content = get_post_meta( $post_id, '_header_content', true );
				?>

				<section class="parallax-window" data-parallax="scroll" data-image-src="<?php echo esc_url( $header_img_scr ) ?>" data-natural-width="1400" data-natural-height="<?php echo esc_attr( $header_img_height ) ?>">
					<div class="parallax-content-1">
						<div class="animated fadeInDown">
						<h1 class="page-title"><?php the_title(); ?></h1>
						<?php echo balancetags( $header_content ); ?>
						</div>
					</div>
				</section><!-- End section -->
			<?php } ?>

			<?php if ( ! is_front_page() ) : ?>
				<div id="position" <?php if ( empty( $header_img_scr ) ) echo 'class="blank-parallax"' ?>>
					<div class="container"><?php ct_breadcrumbs(); ?></div>
				</div><!-- End Position -->
			<?php endif; ?>
		<?php } ?>

		<div class="container margin_60">
			<div class="<?php if ( 'no' != $sidebar_position ) echo 'row' ?>">
				<?php if ( 'left' == $sidebar_position ) : ?>
					<aside class="col-md-3 add_bottom_30"><?php generated_dynamic_sidebar(); ?></aside><!-- End aside -->
				<?php endif; ?>
				<div class="<?php echo esc_attr( $content_class ); ?>">
					<?php if ( has_post_thumbnail() ) { the_post_thumbnail( 'full', array('class' => 'img-responsive') ); } ?>
					<?php the_content(); ?>
					<?php wp_link_pages('before=<div class="page-links">&after=</div>'); ?>
					<?php if ( comments_open() || get_comments_number() ) {
						comments_template();
					} ?>
				</div>
				<?php if ( 'right' == $sidebar_position ) : ?>
					<aside class="col-md-3 add_bottom_30"><?php generated_dynamic_sidebar(); ?></aside><!-- End aside -->
				<?php endif; ?>
			</div>
		</div><!-- end post -->

<?php endwhile;
}
get_footer();