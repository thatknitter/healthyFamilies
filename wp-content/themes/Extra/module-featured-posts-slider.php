<?php if ( $module_posts->have_posts() ) : ?>
<div class="module featured-posts-slider-module et_pb_extra_module <?php echo esc_attr( $module_class ); ?>" data-breadcrumbs="enabled"<?php if ( 'on' === $enable_autoplay ) { echo ' data-autoplay="' . esc_attr( $autoplay_speed ) . '"'; } ?>>
	<div class="posts-slider-module-items carousel-items et_pb_slides">
	<?php while ( $module_posts->have_posts() ) : $module_posts->the_post(); ?>
		<article id="post-<?php the_ID(); ?>" <?php post_class( 'post carousel-item et_pb_slide' ); ?> <?php et_thumb_as_style_background(); ?>>
			<div class="post-content-box">
				<div class="post-content">
					<h3><a href="<?php the_permalink(); ?>"><?php truncate_title( 50 ); ?></a></h3>
					<div class="post-meta">
						<?php
						$meta_args = array(
							'author_link'    => $show_author,
							'author_link_by' => __( 'Posted by %s', 'extra' ),
							'post_date'      => $show_date,
							'date_format'    => $date_format,
							'categories'     => $show_categories,
							'comment_count'  => $show_comments,
							'rating_stars'   => $show_rating,
						);
						?>
						<p><?php echo et_extra_display_post_meta( $meta_args ); ?>
					</div>
				</div>
			</div>
		</article>
	<?php endwhile; ?>
	<?php wp_reset_postdata(); ?>
	</div>
</div>
<?php endif;
