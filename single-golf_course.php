<?php
get_header();

if ( have_posts() ) {
	while ( have_posts() ) : the_post();

		//init variables
		$post_id = get_the_ID();
		$address = get_post_meta( $post_id, '_golf_address', true );
		$loc = get_post_meta( $post_id, '_golf_loc', true );
		$booking_link = get_post_meta( $post_id, '_golf_booking_link', true );
		$weekday_price = get_post_meta( $post_id, '_golf_weekday_price', true );
		$weekend_price = get_post_meta( $post_id, '_golf_weekend_price', true );

		$slider = get_post_meta( $post_id, '_golf_slider', true );

		$review = get_post_meta( $post_id, '_review', true );
		$review = ( ! empty( $review ) )?round( $review, 1 ):0;

		$is_fixed_sidebar = get_post_meta( $post_id, '_tour_fixed_sidebar', true );

		$tour_pos = get_post_meta( $post_id, '_golf_loc', true );
		if ( ! empty( $tour_pos ) ) {
			$tour_pos = explode( ',', $tour_pos );
		}

		$related_hotel = get_post_meta( $post_id, '_golf_related_hotel' );
		$related_tour = get_post_meta( $post_id, '_golf_related_tour' );

		$add_services = ct_get_add_services_by_postid( $post_id );
		$header_img_scr = ct_get_header_image_src( $post_id );
		if ( ! empty( $header_img_scr ) ) {
			$header_img_height = ct_get_header_image_height( $post_id );
			?>

			<section class="parallax-window" data-parallax="scroll" data-image-src="<?php echo esc_url( $header_img_scr ) ?>" data-natural-width="1400" data-natural-height="<?php echo esc_attr( $header_img_height ) ?>">
				<div class="parallax-content-2">
					<div class="container">
						<div class="row">
							<div class="col-md-8 col-sm-8">
								<h1><?php the_title() ?></h1>
								<span><?php echo esc_html( $address, 'citytours' ); ?></span>
								<span class="rating"><?php ct_rating_smiles( $review )?><small>(<?php echo esc_html( ct_get_review_count( $post_id ) ) ?>)</small></span>
							</div>
							<div class="col-md-4 col-sm-4">
								<div id="price_single_main">
									<?php echo esc_html__( 'from/per player', 'citytours' ) ?> <?php echo ct_price( $weekday_price, "special" ) ?>
								</div>
							</div>
						</div>
					</div>
				</div>
			</section><!-- End section -->
			<div id="position">

		<?php } else { ?>
			<div id="position" class="blank-parallax">
		<?php } ?>

			<div class="container"><?php ct_breadcrumbs(); ?></div>
		</div><!-- End Position -->

		<div class="collapse" id="collapseMap">
			<div id="map" class="map"></div>
		</div>

<div class="container margin_60">
	<div class="row">
		<div class="col-md-8" id="single_tour_desc">
					<p class="visible-sm visible-xs"><a class="btn_map" data-toggle="collapse" href="#collapseMap" aria-expanded="false" aria-controls="collapseMap">View on map</a></p>
			<?php if ( ! empty( $slider ) ) : ?>
				<?php echo do_shortcode( $slider ); ?>
				<hr>
			<?php endif; ?>

			<div class="row">
				<div class="col-md-2">
					<h3><?php echo esc_html__( 'Golf Course', 'citytours') ?></h3>
				</div>
				<div class="col-md-10">
						<?php the_content(); ?>
				</div>
			</div>

			<hr>
			<?php if ( ! empty( $related_hotel ) ) : ?>
			<div class="row">
				<div class="col-md-2">
					<h3><?php echo esc_html__( 'Hotel', 'citytours') ?></h3>
				</div>
				<div class="col-md-10">
					<?php
						foreach ( $related_hotel as $each_hotel ) {
								$hotel_price = get_post_meta( $each_hotel, '_hotel_price', true );
								$star_hotel = get_post_meta( $each_hotel, '_hotel_star', true );
								$hotel_url = esc_url( get_permalink( $each_hotel ));
								$review = get_post_meta( $each_hotel, '_review', true );
								$args = array(
									'post_type' => 'room_type',
									'posts_per_page' => -1,
									'meta_query' => array(
										array(
											'key' => '_room_hotel_id',
											'value' => array( $each_hotel )
										)
									),
									'post_status' => 'publish',
									'suppress_filters' => 0,
								);
								$room_types = get_posts( $args );
								?>
								<div class="box_style_1 expose hotel-default hotel_container" id="<?php echo $each_hotel; ?>">
									<div class="row">
										<div class="col-md-3 col-xs-6">
											<a href="<?php echo $hotel_url; ?>" target="_blank">
											<?php echo get_the_post_thumbnail( $each_hotel, array(150,150), array( 'class' => 'img-responsive', 'style' => 'margin: auto; display: block; height: 120px;' )); ?>
											</a>
										</div>
										<div class="col-md-5 col-xs-6" style="border-right: 1px solid #ededed;">
											<a href="<?php echo $hotel_url; ?>" target="_blank"><H5 class="hotel-name show"><?php echo get_the_title( $each_hotel ) ?></h5></a>
											<span class="hotel-select-id">Hanoi Hotel</span>
											<br>
											<span class="">Price per Night from <?php echo ct_price( $hotel_price )?></span>
											<span class="rating show star" style="font-size: 11px;">
											<?php
											for ( $i = 1; $i <= 5; $i++ ) {
												$class = ( $i <= $star_hotel ) ? 'icon-star voted' : 'icon-star-empty';
												echo '<i class="' . $class . '"></i>';
											}
											?>
											</span>
											<span class="rating show review" style="font-size: 11px;">
											<?php
											for ( $i = 1; $i <= 5; $i++ ) {
												$class = ( $i <= $review ) ? 'icon-smile voted' : 'icon-smile';
												echo '<i class="' . $class . '"></i>';
											}
											?>
											</span>
										</div>
										<div class="col-md-4 col-xs-12 text-center block-center">
											<p></p>
											<a href="<?php echo $hotel_url ?>" target="_blank" class="btn_1 outline btn_full details" type="button"><small>Details</small></a>

											<a class="btn_1 outline btn_full btn_select" type="button"><small>Select Another</small></a>
										</div>
									</div>
									<hr>
									<form method="get" id="booking-form" action="http://localhost/wp_golf_tour/hotel-cart-page/">
										<input type="hidden" name="hotel_id" value="217">
										<div class="row">
											<div class="col-md-6 col-sm-6">
												<div class="form-group">
													<label><i class="icon-calendar-7"></i> Check in</label>
													<input class="date-pick form-control" data-date-format="mm/dd/yyyy" type="text" name="date_from">
												</div>
											</div>
											<div class="col-md-6 col-sm-6">
												 <div class="form-group">
													<label><i class="icon-calendar-7"></i> Check out</label>
													<input class="date-pick form-control" data-date-format="mm/dd/yyyy" type="text" name="date_to">
												</div>
											</div>
										</div>
										<table class="table table-striped cart-list hotel add_bottom_30">
											<thead><tr>
												<th><?php echo esc_html__( 'Room Type', 'citytours' ) ?></th>
												<th><?php echo esc_html__( 'Quantity', 'citytours' ) ?></th>
												<th><?php echo esc_html__( 'Total', 'citytours' ) ?></th>
											</tr></thead>
											<tbody>
												<?php foreach( $room_types as $post ) : setup_postdata( $post );
													$room_id = get_the_ID();
												?>
													<tr>
														<td>
															<div class="thumb_cart">
																<a href="#" data-toggle="modal" data-target="#room-<?php echo esc_attr( $room_id ) ?>"><?php echo get_the_post_thumbnail( $room_id, 'thumbnail' ); ?></a>
															</div>
															 <span class="item_cart"><a href="#" data-toggle="modal" data-target="#room-<?php echo esc_attr( $room_id ) ?>"><?php echo esc_html( get_the_title( $room_id ) ); ?></a></span>
															 <input type="hidden" name="room_type_id[]" value="<?php echo esc_attr( $room_id ) ?>">
														</td>
														<td>
															<div class="numbers-row" data-min="0" data-max="10<?php //echo esc_attr( $available_rooms ) ?>">
																<input type="text" class="qty2 form-control room-quantity" name="rooms[<?php echo esc_attr( $room_id ) ?>]" value="<?php //echo esc_attr( $cart->get_room_field( $uid, $room_id, 'rooms' ) ) ?>">
															</div>
														</td>
														
														<td><strong><?php //$total = $cart->get_room_field( $uid, $room_id, 'total' ); if ( ! empty( $total ) ) echo ct_price( $cart->get_room_field( $uid, $room_id, 'total' ) ) ?></strong></td>
													</tr>
													<?php wp_reset_postdata(); ?>
												<?php endforeach; ?>
											</tbody>
											</table>
										<br>
										<button type="submit" class="btn_full book-now">Check Price</button>
									</form>
								</div>
								<hr>
								<?php if ( ! empty( $room_types ) ) : ?>
									<?php 
									$is_first = true;
									foreach( $room_types as $post ) : setup_postdata( $post ); ?>
										<?php if ( $is_first ) { $is_first = false; } else { echo '<hr>'; } ?>
										<h4><?php the_title() ?></h4>
										<?php the_content() ?>
										<ul class="list_icons">

											<?php $room_type_id = get_the_ID();
											$hotel_facilities = get_the_terms( $room_type_id, 'hotel_facility' );
											if ( ! $hotel_facilities || is_wp_error( $hotel_facilities ) ) $hotel_facilities = array();
											foreach ( $hotel_facilities as $hotel_term ) :
												$term_id = $hotel_term->term_id;
												$icon_class = get_tax_meta($term_id, 'ct_tax_icon_class', true);
												echo '<li>';
												if ( ! empty( $icon_class ) ) echo '<i class="' . esc_attr( $icon_class ) . '"></i>';
												echo esc_html( $hotel_term->name );
												echo '</li>';
											endforeach; ?>

										</ul>
										<?php $gallery_imgs = get_post_meta( $room_type_id, '_gallery_imgs' );?>
										<?php if ( ! empty( $gallery_imgs ) ) : ?>
											<div class="carousel magnific-gallery">
												<?php foreach ( $gallery_imgs as $gallery_img ) {
													echo '<div class="item"><a href="' . esc_url( wp_get_attachment_url( $gallery_img ) ) . '">' . wp_get_attachment_image( $gallery_img, 'full' ) . '</a></div>';
												} ?>
											</div>
										<?php endif; ?>
										<?php wp_reset_postdata(); ?>
									<?php endforeach ?>
								<?php endif; ?>
							<?php	} ?>
				</div>
			</div>
			<hr>
			<?php endif; ?>

			<?php
			global $ct_options;
			if ( ! empty( $ct_options['tour_review'] ) ) :
				$review_fields = ! empty( $ct_options['tour_review_fields'] ) ? explode( ",", $ct_options['tour_review_fields'] ) : array("Position", "Comfort", "Price", "Quality");
				$review = get_post_meta( ct_tour_org_id( $post_id ), '_review', true );
				$review = round( ( ! empty( $review ) ) ? (float) $review : 0, 1 );
				$review_detail = get_post_meta( ct_tour_org_id( $post_id ), '_review_detail', true );
				if ( ! empty( $review_detail ) ) {
					$review_detail = is_array( $review_detail ) ? $review_detail : unserialize( $review_detail );
				} else {
					$review_detail = array_fill( 0, count( $review_fields ), 0 );
				}
				?>
				<div class="row">
					<div class="col-md-3">
						<h3><?php echo esc_html__( 'Reviews', 'citytours') ?></h3>
						<a href="#" class="btn_1 add_bottom_15" data-toggle="modal" data-target="#myReview"><?php echo esc_html__( 'Leave a review', 'citytours') ?></a>
					</div>
					<div class="col-md-9">
						<div id="general_rating"><?php echo sprintf( esc_html__( '%d Reviews', 'citytours' ), ct_get_review_count( $post_id ) ) ?>
							<div class="rating"><?php echo ct_rating_smiles( $review ) ?></div>
						</div>
						<div class="row" id="rating_summary">
							<div class="col-md-6">
								<ul>
									<?php for ( $i = 0; $i < ( count( $review_fields ) / 2 ); $i++ ) { ?>
									<li><?php echo esc_html( $review_fields[ $i ], 'citytours' ); ?>
										<div class="rating"><?php echo ct_rating_smiles( $review_detail[ $i ] ) ?></div>
									</li>
									<?php } ?>
								</ul>
							</div>
							<div class="col-md-6">
								<ul>
									<?php for ( $i = $i; $i < count( $review_fields ); $i++ ) { ?>
									<li><?php echo esc_html( $review_fields[ $i ], 'citytours' ); ?>
										<div class="rating"><?php echo ct_rating_smiles( $review_detail[ $i ] ) ?></div>
									</li>
									<?php } ?>
								</ul>
							</div>
						</div><!-- End row -->
						<hr>
						<div class="guest-reviews">
							<?php
								$per_page = 10;
								$review_count = ct_get_review_html($post_id, 0, $per_page);
							?>
						</div>
						<?php if ( $review_count >= $per_page ) { ?>
							<a href="#" class="btn more-review" data-post_id="<?php echo esc_attr( $post_id ) ?>"><?php echo esc_html__( 'LOAD MORE REVIEWS', 'citytours' ) ?></a>
						<?php } ?>
					</div>
				</div>

			<?php  endif; ?>

		</div><!--End  single_tour_desc-->

				<aside class="col-md-4" <?php if ($is_fixed_sidebar) echo 'id="sidebar"'; ?>>

					<p class="hidden-sm hidden-xs">
						<a class="btn_map" data-toggle="collapse" href="#collapseMap" aria-expanded="false" aria-controls="collapseMap">View on map</a>
					</p>

					<?php if ( $is_fixed_sidebar ) : ?>
					<div class="theiaStickySidebar">
					<?php endif; ?>
		<div class="box_style_1 expose">
			<h3 class="inner">- <?php echo esc_html__( 'Booking', 'citytours' ) ?> -</h3>
			<form method="get" id="booking-form" action="<?php echo $booking_link; ?>">
				<input type="hidden" name="golf_course_id" value="<?php echo $post_id; ?>">
				<div class="row">
					<div class="col-md-6 col-sm-6">
						<div class="form-group">
							<label><i class="icon-calendar-7"></i> <?php echo esc_html__( 'Select a date', 'citytours' ) ?></label>
							<input class="date-pick form-control" data-date-format="<?php echo ct_get_date_format('html'); ?>" type="text" name="date" id="date_form_1">
						</div>
					</div>
					<div class="col-md-6 col-sm-6">
              <div class="form-group">
                  <label><i class=" icon-clock"></i> <?php echo esc_html__( 'T/O Times', 'citytours' ) ?></label>
                  <input class="time-pick form-control" value="6:00 AM" type="text" name="time" id="time_form_1">
              </div>
          </div>
				</div>
				<div class="row">
					<div class="col-md-6 col-sm-6">
						<div class="form-group">
							<label><i class="icon_set_1_icon-29"></i> <?php echo esc_html__( 'Players', 'citytours' ) ?></label>
							<div class="numbers-row" data-min="1">
								<input type="text" value="1" id="players" class="qty2 form-control" name="players">
							</div>
						</div>
					</div>
				</div>

				<br>
				<table class="table table_summary">
				<tbody>
				<tr>
					<td>
						<?php echo esc_html__( 'Players', 'citytours' ) ?>
					</td>
					<td class="text-right adults-number">
						1
					</td>
				</tr>
				<tr>
					<td>
						<?php echo esc_html__( 'Fee per Player at weekday', 'citytours' ) ?>
					</td>
					<td class="text-right weekday_price">
						<?php echo ct_price( $weekday_price ) ?>
					</td>
				</tr>
				<tr>
					<td>
						<?php echo esc_html__( 'Fee per Player at weekend', 'citytours' ) ?>
					</td>
					<td class="text-right weekend_price">
						<?php echo ct_price( $weekend_price ) ?>
					</td>
				</tr>
				<tr>
					<td>
						<?php echo esc_html__( 'Selected date', 'citytours' ) ?>
					</td>
					<td class="text-right selected_date">

					</td>
				</tr>
				<tr>
					<td>
						<?php echo esc_html__( 'Total amount', 'citytours' ) ?>
					</td>
					<td class="text-right">
						<span class="adults-number">1</span>x $<span class="fee-per-player"></span>
						<?php if ( ! empty( $child_price ) ) : ?>
							<span class="child-amount hide"> + <span class="children-number">0</span>x <?php echo ct_price( $child_price ) ?></span>
						<?php endif; ?>
					</td>
				</tr>

				</tbody>
				</table>
				<?php if ( ! empty( $add_services ) ) : ?>
				<table class="table table-striped options_booking">
				<thead>
				<tr>
					<th colspan="3">
						<?php echo esc_html__( 'Add options / Services', 'citytours' ) ?>
					</th>
				</tr>
				</thead>
				<tbody>
					<?php foreach ( $add_services as $service ) : ?>
						<?php $field_name = 'add_service_' . esc_attr( $service->id ); ?>
						<tr>
							<td width="6%">
								<i class="<?php echo esc_attr( $service->icon_class ); ?>"></i>
							</td>
							<td width="59%">
								<?php echo esc_attr( $service->title ); ?> <strong>+<?php echo ct_price( $service->price ) ?></strong>
							</td>
							<td width="35%">
								<label class="switch-light switch-ios pull-right">
								<input type="checkbox" name="<?php echo $field_name ?>" id="<?php echo $field_name ?>" value="<?php echo $service->price ?>" name_service="<?php echo esc_attr( $service->title ); ?>">
								<span>
								<span><?php echo esc_html__( 'No', 'citytours' ) ?></span>
								<span><?php echo esc_html__( 'Yes', 'citytours' ) ?></span>
								</span>
								<a></a>
								</label>
							</td>
						</tr>
					<?php endforeach ?>
				</tbody>
				</table>
				<input type="hidden" name="add_service" id="add_service_form_1" value="">
				<?php endif; ?>

				<table class="table table_summary">
				<tbody>
				<tr class="total">
					<td>
						<?php echo esc_html__( 'Total Fee', 'citytours' ) ?>
					</td>
					<td class="text-right total-cost">
						$0
					</td>
				</tr>
				</tbody>
				</table>
				<input type="hidden" name="total-cost" value="">
				<button type="submit" class="btn_full book-now"><?php echo esc_html__( 'Book now', 'citytours' ) ?></button>
				<?php if ( ! empty( $ct_options['wishlist'] ) ) : ?>
				<?php if ( is_user_logged_in() ) {
					$user_id = get_current_user_id();
					$wishlist = get_user_meta( $user_id, 'wishlist', true );
					if ( empty( $wishlist ) ) $wishlist = array(); ?>
						<a class="btn_full_outline btn-add-wishlist" href="#" data-label-add="<?php esc_html_e(  'Add to wishlist', 'citytours' ); ?>" data-label-remove="<?php esc_html_e(  'Remove from wishlist', 'citytours' ); ?>" data-post-id="<?php echo esc_attr( $post_id ) ?>"<?php echo ( in_array( ct_hotel_org_id( $post_id ), $wishlist) ) ? ' style="display:none;"' : '' ?>><i class=" icon-heart"></i> <?php echo esc_html__( 'Add to wishlist', 'citytours' ) ?></a>
						<a class="btn_full_outline btn-remove-wishlist" href="#" data-label-add="<?php esc_html_e(  'Add to wishlist', 'citytours' ); ?>" data-label-remove="<?php esc_html_e(  'Remove from wishlist', 'citytours' ); ?>" data-post-id="<?php echo esc_attr( $post_id ) ?>"<?php echo ( ! in_array( ct_hotel_org_id( $post_id ), $wishlist) ) ? ' style="display:none;"' : '' ?>><i class=" icon-heart"></i> <?php esc_html_e(  'Remove from wishlist', 'citytours' ); ?></a>
				<?php } else { ?>
						<div><?php esc_html_e(  'To save your wishlist please login.', 'citytours' ); ?></div>
						<?php if ( empty( $ct_options['login_page'] ) ) { ?>
							<a href="#" class="btn_full_outline"><?php esc_html_e(  'login', 'citytours' ); ?></a>
						<?php } else { ?>
							<a href="<?php echo esc_url( ct_get_permalink_clang( $ct_options['login_page'] ) ); ?>" class="btn_full_outline"><?php esc_html_e(  'login', 'citytours' ); ?></a>
						<?php } ?>
				<?php } ?>
				<?php endif; ?>
			</form>
			
			<?php if ( ! empty( $related_hotel ) ) : ?>
				<hr>
				<a class="btn_collapse collapsed" data-toggle="collapse" href="#collapseForm" aria-expanded="true" aria-controls="collapseForm">
					<i class="icon-plus-circled"></i>Booking A Hotel
				</a>
				<div class="collapse in" id="collapseForm" aria-expanded="true">
					<form method="get" id="booking-form" action="<?php echo esc_url( ct_get_hotel_cart_page() ); ?>">
					<input type="hidden" name="hotel_id" value="<?php echo esc_attr( $related_hotel[0] ) ?>">
					<div class="row">
						<div class="col-md-6 col-xs-6">
							<a href="<?php echo esc_url( get_permalink( $related_hotel[0] )); ?>" target="_blank">
							<?php echo get_the_post_thumbnail( $related_hotel[0], array(150,150), array( 'class' => 'img-responsive', 'style' => 'margin: auto; display: block; height: 120px;' )); ?>
							</a>
						</div>
						<div class="col-md-6 col-xs-6">
							<a href="<?php echo $hotel_url; ?>" target="_blank"><H5 class="hotel-name show"><?php echo get_the_title( $related_hotel[0] ) ?></h5></a>
							<span class="hotel-select-id">Hanoi Hotel</span>
							<br>
							<span class="">Price from <?php echo ct_price( get_post_meta( $related_hotel[0], '_hotel_price', true ) )?></span>
							<span class="rating show star" style="font-size: 11px;">
							<?php
							for ( $i = 1; $i <= 5; $i++ ) {
								$class = ( $i <= get_post_meta( $related_hotel[0], '_hotel_star', true ) ) ? 'icon-star voted' : 'icon-star-empty';
								echo '<i class="' . $class . '"></i>';
							}
							?>
							</span>
							<span class="rating show review" style="font-size: 11px;">
							<?php
							for ( $i = 1; $i <= 5; $i++ ) {
								$class = ( $i <= get_post_meta( $related_hotel[0], '_review', true ) ) ? 'icon-smile voted' : 'icon-smile';
								echo '<i class="' . $class . '"></i>';
							}
							?>
							</span>
						</div>
					</div>
					<br>
					<div class="row">
						<div class="col-md-6 col-sm-6">
							<div class="form-group">
								<label><i class="icon-calendar-7"></i> Check in</label>
								<input class="date-pick form-control" data-date-format="mm/dd/yyyy" type="text" name="date_from">
							</div>
						</div>
						<div class="col-md-6 col-sm-6">
							 <div class="form-group">
								<label><i class="icon-calendar-7"></i> Check out</label>
								<input class="date-pick form-control" data-date-format="mm/dd/yyyy" type="text" name="date_to">
							</div>
						</div>
					</div>
					<br>
					<input type="hidden" name="golf_course_id" value="<?php the_title(); ?>">
					<input type="hidden" name="date_golf" id="date_form_2">
					<input type="hidden" name="time_golf" id="time_form_2">
					<input type="hidden" name="player" id="players_form_2" value="">
					<input type="hidden" name="add_service" value="">
					<input type="hidden" name="total-cost" value="">
					<button type="submit" class="btn_full book-now"><i class=" icon-plus-6"> </i><?php echo esc_html__( 'Booking A Hotel', 'citytours' ) ?></button>
					</form>
				</div>
				<?php endif; ?>
		</div><!--/box_style_1 -->
		<?php if ( is_active_sidebar( 'sidebar-tour' ) ) : ?>
			<?php dynamic_sidebar( 'sidebar-tour' ); ?>
		<?php endif; ?>
					<?php if ( $is_fixed_sidebar ) : ?>
					</div>
					<?php endif; ?>

		</aside>
	</div><!--End row -->
</div><!--End container -->
<?php if ( ! empty( $ct_options['tour_review'] ) ) : ?>
<div class="modal fade" id="myReview" tabindex="-1" role="dialog" aria-labelledby="myReviewLabel" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">x</span></button>
				<h4 class="modal-title" id="myReviewLabel"><?php echo esc_html__( 'Write your review', 'citytours' ) ?></h4>
			</div>
			<div class="modal-body">
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ) ?>" name="review" id="review-form">
					<?php wp_nonce_field( 'post-' . $post_id, '_wpnonce', false ); ?>
					<input type="hidden" name="post_id" value="<?php echo esc_attr( $post_id ); ?>">
					<input type="hidden" name="action" value="submit_review">
					<div class="row">
						<div class="col-md-6">
							<div class="form-group">
								<input name="booking_no" id="booking_no" type="text" placeholder="<?php echo esc_html__( 'Booking No', 'citytours' ) ?>" class="form-control">
							</div>
						</div>
						<div class="col-md-6">
							<div class="form-group">
								<input name="pin_code" id="pin_code" type="text" placeholder="<?php echo esc_html__( 'Pin Code', 'citytours' ) ?>" class="form-control">
							</div>
						</div>
					</div>
					<!-- End row -->
					<hr>
					<div class="row">
						<?php for ( $i = 0; $i < ( count( $review_fields ) ); $i++ ) { ?>
							<div class="col-md-6">
								<div class="form-group">
									<label><?php echo esc_html( $review_fields[ $i ], 'citytours' ); ?></label>
									<select class="form-control" name="review_rating_detail[<?php echo esc_attr( $i ) ?>]">
										<option value="0"><?php esc_html_e( "Please review", 'citytours' ); ?></option>
										<option value="1"><?php esc_html_e( "Low", 'citytours' ); ?></option>
										<option value="2"><?php esc_html_e( "Sufficient", 'citytours' ); ?></option>
										<option value="3"><?php esc_html_e( "Good", 'citytours' ); ?></option>
										<option value="4"><?php esc_html_e( "Excellent", 'citytours' ); ?></option>
										<option value="5"><?php esc_html_e( "Super", 'citytours' ); ?></option>
									</select>
								</div>
							</div>
						<?php } ?>
					</div>
					<!-- End row -->
					<div class="form-group">
						<textarea name="review_text" id="review_text" class="form-control" style="height:100px" placeholder="<?php esc_html_e( "Write your review", 'citytours' ); ?>"></textarea>
					</div>
					<input type="submit" value="Submit" class="btn_1" id="submit-review">
				</form>
				<div id="message-review" class="alert alert-warning">
				</div>
			</div>
		</div>
	</div>
</div>
<?php endif; ?>
<?php
	$tour_start_date_milli_sec = 0;
	if ( ! empty( $tour_start_date ) ) {
		$tour_start_date_milli_sec = strtotime( $tour_start_date) * 1000;
	}

	$tour_end_date_milli_sec = 9999*365*24*60*60*1000;
	if ( ! empty( $tour_end_date ) ) {
		$tour_end_date_milli_sec = strtotime( $tour_end_date) * 1000;
	}

?>
<script>
$ = jQuery.noConflict();
var price_per_person = 0;
var exchange_rate = 1;
var weekday_price = 0;
var weekend_price = 0;
<?php if ( ! empty( $weekday_price ) ) : ?>
	weekday_price = <?php echo esc_js( $weekday_price ); ?>;
	weekend_price = <?php echo esc_js( $weekend_price ); ?>;
<?php endif; ?>
<?php if ( ! empty( $_SESSION['exchange_rate'] ) ) : ?>
	exchange_rate = <?php echo esc_js( $_SESSION['exchange_rate'] ); ?>;
<?php endif; ?>


$(document).ready(function(){
	$('input[type="checkbox"]').click(function(){
            update_tour_price();
        });
	if ( $('input.date-pick').length ) {
				$('input.date-pick').datepicker({
					startDate: "today"
				});
				$('input[name="date"]').datepicker( 'setDate', 'today' );
				$('input[name="date_from"]').datepicker( 'setDate', 'today' );
				$('input[name="date_to"]').datepicker( 'setDate', '+1d' );
			}
	$('.selected_date').text( $('input[name="date"]').datepicker('getDate').toDateString() );
	var daytmp = $('input[name="date"]').datepicker('getDate').getDay();
	if ( (daytmp == 6) || (daytmp == 0) ) {
		$('.fee-per-player').text(weekend_price);
		$('.total-cost').text('$' + weekend_price);
		$('input[name="total-cost"]').val(weekend_price);
	} else {
		$('.fee-per-player').text(weekday_price);
		$('.total-cost').text('$' + weekday_price);
		$('input[name="total-cost"]').val(weekday_price);
	}
	if ( $('input.time-pick').length ) {
		$('input.time-pick').timepicker({
			minuteStep: 30,
			showInpunts: false
		});
	}
	$('input#players').on('change', function(){
		$('.adults-number').html( $(this).val() );
		$('input#players_form_2').val( $(this).val() );
		update_tour_price();
	});
	//form 2
	$('input#players_form_2').val($('input#players').val());
	$('input#date_form_2').val($('input#date_form_1').val());
	$('input#time_form_2').val($('input#time_form_1').val());
	
	$('input[name="date"]').on('change', function(){
		$('.selected_date').html( $('input[name="date"]').datepicker('getDate').toDateString() );
		$('input#date_form_2').val( $(this).val() );
		update_tour_price();
	});
	$('input[name="time"]').on('change', function(){
		$('input#time_form_2').val( $(this).val() );
	});
	var validation_rules = {};
	if ( $('input.date-pick').length ) {
		validation_rules.date = { required: true};
	}
	//validation form
	$('#booking-form').validate({
		rules: validation_rules
	});
});

function update_tour_price() {
	var addservice = 0;
	var addservicesum = '';
	$('input[type=checkbox]').each(function () {
			if($(this).prop("checked") == true){
					addservice += parseInt( $(this).val() );
					addservicesum += $(this).attr("name_service") + '<br>';
					$('input[name="add_service"]').val(addservicesum);
			}
	});
	var day = $('input[name="date"]').datepicker('getDate').getDay();
	var adults = $('input#players').val();
	if ( (day == 6) || (day == 0) ) {
		var price = +( (adults * weekend_price ) * exchange_rate + addservice).toFixed(2);
		$('.fee-per-player').html(weekend_price);
	} else {
		var price = +( (adults * weekday_price ) * exchange_rate + addservice).toFixed(2);
		$('.fee-per-player').html(weekday_price);
	}

	var total_price = $('.total-cost').text().replace(/[\d\.\,]+/g, price);
	$('.total-cost').text( total_price );
	$('input[name="total-cost"]').val(total_price);
}
</script>
		<script>
			$('#sidebar').theiaStickySidebar({
				additionalMarginTop: 80
			});
		</script>
		

<?php endwhile;
}
get_footer();
