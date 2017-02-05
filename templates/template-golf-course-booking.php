<?php
 /*
 Template Name: Full Width Template
 */
get_header();

if ( have_posts() ) {
	while ( have_posts() ) : the_post();
		$post_id = get_the_ID();
		$golf_course_id = $_REQUEST['golf_course_id'];
		$date_golf = $_REQUEST['date'];
		$time_golf = $_REQUEST['time'];
		$players_golf = $_REQUEST['players'];
		$add_service_golf = $_REQUEST['add_service'];
		$total_cost_golf = $_REQUEST['total-cost'];
		$related_hotel = get_post_meta( $golf_course_id, '_golf_related_hotel' );
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

		<div id="position" <?php if ( empty( $header_img_scr ) ) echo 'class="blank-parallax"' ?>>
			<div class="container"><?php ct_breadcrumbs(); ?></div>
		</div><!-- End Position -->
		<div class="container margin_60">
			<div class="post-content">
				<div class="post nopadding clearfix">
					<div class="row">
						<div class="col-md-8">
						<div id="hotel-check">
						</div>
						<div id="hotel-price">
						</div>
						<?php if ( ! empty( $related_hotel ) ) : ?>
							<div class="row">
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
											<div class="box_style_1 expose hotel_container" id="<?php echo $each_hotel; ?>">
												<div class="row">
													<div class="col-md-3 col-xs-6">
														<a href="<?php echo $hotel_url; ?>" target="_blank">
														<?php echo get_the_post_thumbnail( $each_hotel, array(150,150), array( 'class' => 'img-responsive', 'style' => 'margin: auto; display: block; height: 120px;' )); ?>
														</a>
													</div>
													<div class="col-md-3 col-xs-6">
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
													<div class="col-md-6 col-xs-12 block-center">
														<form method="get" class="hotel-form" action="tnd_get_hotel_room_list">
															<input type="hidden" name="action" value="tnd_get_hotel_room_list">
															<input type="hidden" name="hotel_id" value="<?php echo $each_hotel ?>">
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
															<a class="btn_full_outline view_room_btn" href="#"><i class="icon_set_1_icon-54"></i> <?php echo esc_html__( 'View Rooms', 'citytours' ) ?></a>
														</form>
													</div>
												</div>
											</div>
								<?php	} ?>
							</div>
							<?php endif; ?>
						</div>
						<aside class="col-md-4">
							<div class="box_style_1">
							<h3 class="inner">- Summary -</h3>
							<table class="table table_summary">
							<tbody>
							<tr>
							<td>Golf Course</td>
							<td class="text-right" id="golf_course_summary"><?php echo get_the_title($golf_course_id )?></td>
							</tr>
							<tr>
							<td>Date</td>
							<td class="text-right" id="date_summary"><?php echo $date_golf ?></td>
							</tr>
							<tr>
							<td>T/O Time</td>
							<td class="text-right" id="time_summary"><?php echo $time_golf ?></td>
							</tr>
							<tr>
							<td>Players</td>
							<td class="text-right" id="player_summary"><?php echo $players_golf ?></td>
							</tr>
							<tr>
							<td>Add Services</td>
							<td class="text-right" id="add_service_summary"><?php echo $add_service_golf ?></td>
							</tr>
							<tr class="total">
							<td>Total cost</td>
							<td class="text-right" id="total-cost_summary"><?php echo $total_cost_golf ?></td>
							</tr>
							</tbody>
							</table>
							<p><button type="submit" class="btn_full book-now-btn">Book now</button><span class="wpcf7-form-control-wrap golf_course"><input type="hidden" name="golf_course" value="Test golf course" size="40" class="wpcf7-form-control wpcf7dtx-dynamictext wpcf7-dynamichidden" aria-invalid="false"></span><span class="wpcf7-form-control-wrap date"><input type="hidden" name="date" value="02/03/2017" size="40" class="wpcf7-form-control wpcf7dtx-dynamictext wpcf7-dynamichidden" aria-invalid="false"></span><span class="wpcf7-form-control-wrap time"><input type="hidden" name="time" value="6:00 AM" size="40" class="wpcf7-form-control wpcf7dtx-dynamictext wpcf7-dynamichidden" aria-invalid="false"></span><span class="wpcf7-form-control-wrap players"><input type="hidden" name="players" value="1" size="40" class="wpcf7-form-control wpcf7dtx-dynamictext wpcf7-dynamichidden" aria-invalid="false"></span><span class="wpcf7-form-control-wrap add_service"><input type="hidden" name="add_service" value="" size="40" class="wpcf7-form-control wpcf7dtx-dynamictext wpcf7-dynamichidden" aria-invalid="false"></span><span class="wpcf7-form-control-wrap total-cost"><input type="hidden" name="total-cost" value="72" size="40" class="wpcf7-form-control wpcf7dtx-dynamictext wpcf7-dynamichidden" aria-invalid="false"></span></p>
							</div>
						</aside>
					</div>
				</div><!-- end post -->
			</div>
		</div>
		<script>
		$ = jQuery.noConflict();
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
			
		});
		$(document).on('change', '.numbers-row input', function(){
			if ( $(this).parent().attr("data-max") && $(this).val() > $(this).parent().data('max') ) {
			$(this).val( $(this).parent().data('max') );
			}
			if ( $(this).parent().attr("data-min") && $(this).val() < $(this).parent().data('min') ) {
				$(this).val( $(this).parent().data('min') );
			}
		});
		$(document).on('click', '.button_inc', function(){
			var $button = $(this);
			var oldValue = $button.parent().find("input").val();

			if ($button.text() == "+") {
				var max_val = 9999;
				if ( $(this).parent().attr("data-max") ) {
					max_val = $(this).parent().data("max");
				}
				if (oldValue < max_val) {
					var newVal = parseFloat(oldValue) + 1;
				} else {
					newVal = max_val;
				}
			} else {
				// Don't allow decrementing below zero
				var min_val = 0;
				if ( $(this).parent().attr("data-min") ) {
					min_val = $(this).parent().data("min");
				}
				if (oldValue > min_val) {
					var newVal = parseFloat(oldValue) - 1;
				} else {
					if ( $(this).parent() )
					newVal = min_val;
				}
			}
			$button.parent().find("input").val(newVal).change();
		});
		$(document).on('change', '.room-adults', function(){
			var $quantity = $(this).closest('tr').find('.room-quantity');
			var adults = parseInt($(this).val(),10);
			var max_adults = 0;
			if ( $(this).parent('.numbers-row').attr('data-per-room') ) {
				max_adults = $(this).parent('.numbers-row').data('per-room');
				if ( ( max_adults * $quantity.val() < adults ) ) $quantity.val( Math.ceil(adults / max_adults) );
			}
		});
		$(document).on('change', '.room-kids', function(){
			var $quantity = $(this).closest('tr').find('.room-quantity');
			var kids = parseInt($(this).val(),10);
			var max_kids = 0;
			if ( $(this).parent('.numbers-row').attr('data-per-room') ) {
				max_kids = $(this).parent('.numbers-row').data('per-room');
				if ( ( max_kids * $quantity.val() < kids ) ) $quantity.val( Math.ceil(kids / max_kids) );
			}
		});
		$(document).on('change', '.room-quantity', function(){
			var $adults = $(this).closest('tr').find('.room-adults');
			var $kids = $(this).closest('tr').find('.room-kids');
			var max_adults = 0, max_kids = 0;
			if ( $adults.parent('.numbers-row').attr('data-per-room') ) max_adults = $adults.parent('.numbers-row').data('per-room');
			if ( $kids.parent('.numbers-row').attr('data-per-room') ) max_kids = $kids.parent('.numbers-row').data('per-room');
			var rooms = parseInt($(this).val(),10);
			if ( max_adults > 0 && ( max_adults * rooms < parseInt($adults.val(),10) ) ) $adults.val( max_adults * rooms );
			if ( max_kids > 0 && ( max_kids * rooms < parseInt($kids.val(),10) ) ) $kids.val( max_kids * rooms );
		});
		$('.view_room_btn').click(function(e){
			$('#hotel-price').html('');
			e.preventDefault();
			$('#overlay').fadeIn();
			$.ajax({
				url: ajaxurl,
				type: "POST",
				data: $(this).parent().serialize(),
				success: function(response){
					if (response == '') {
						location.reload();
					} else {
						//$(this).parentsUntil( ".hotel_container" ).append(response);
						//$('.hotel-form').append(response);
						//console.log($(this).parent().serialize());
						console.log(response);
						//$(this).parent().text(response);
						$('#hotel-check').html(response);
						$('#overlay').fadeOut();
					}
				}
			});
			$('body,html').animate({scrollTop: $('#hotel-check').offset().top },500);
			return false;
		});
		$(document).on('click', '.check_price_btn', function(){
			//e.preventDefault();
			$('#overlay').fadeIn();
			$.ajax({
				url: ajaxurl,
				type: "POST",
				data: $(this).parent().serialize(),
				success: function(response){
					if (response == '') {
						location.reload();
					} else {
						console.log(response);
						var cart_hotel = JSON.parse( response );
						var html = '<div class="panel panel-default"><div class="panel-heading" style=" background-color: #fff; padding: 20px;"><h5 class="panel-title text-center"><strong>' + 'Check In: ' + cart_hotel.date_from + ' - Check Out: ' + cart_hotel.date_to +'</strong></h3></div><div class="panel-body"><div class="table-responsive"><table class="table table-condensed"><thead><tr><td><strong>Room</strong></td><td class="text-center"><strong>Quantity</strong></td><td class="text-center"><strong>Adult</strong></td><td class="text-center"><strong>Child</strong></td><td class="text-right"><strong>Price</strong></td></tr></thead><tbody>';
						$.each(cart_hotel.room, function(key,value) {
							html += '<tr><td>' + value.room_name + '</td><td class="text-center">' + value.rooms + '</td><td class="text-center">' + value.adults + '</td><td class="text-center">' + value.kids + '</td><td class="text-right">$' + value.total + '</td></tr>';
						}); 
						html += '<tr><td class="no-line"></td><td class="no-line"></td><td class="no-line"></td><td class="no-line text-center"><strong>Total</strong></td><td class="no-line text-right">$' + cart_hotel.total_price +'</td></tr></tbody></table></div><a href="#" class="btn_1 green pull-right add_hotel_cart_btn"><i class="icon-cart"></i> Add Cart</a></div></div>';
						$('#hotel-price').html(html);
						$('#overlay').fadeOut();
					}
				}
			});
			return false;
		});
		$(document).on('click', '.add_hotel_cart_btn', function(){
			
		});
		</script>
<?php endwhile;
}
get_footer();