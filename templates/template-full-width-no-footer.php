<?php
 /*
 Template Name: Full Page Map Template
 */
get_header();

if ( have_posts() ) {
	while ( have_posts() ) : the_post();
		$post_id = get_the_ID();
		$content_class = 'post-content';
		?>

		<div class="<?php echo esc_attr( $content_class ); ?>">
			<div class="post nopadding clearfix">
				<?php the_content(); ?>
			</div><!-- end post -->
		</div>

<?php endwhile;
}
get_footer();