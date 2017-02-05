<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
if ( ! function_exists( 'ct_get_hotel_cart_page' ) ) {
	function ct_get_hotel_cart_page() {
		global $ct_options;
		if ( ! empty( $ct_options['hotel_cart_page'] ) ) {
			return ct_get_permalink_clang( $ct_options['hotel_cart_page'] );
		}
		return false;
	}
}
if ( ! function_exists( 'ct_get_hotel_checkout_page' ) ) {
	function ct_get_hotel_checkout_page() {
		global $ct_options;
		if ( ! empty( $ct_options['hotel_checkout_page'] ) ) {
			return ct_get_permalink_clang( $ct_options['hotel_checkout_page'] );
		}
		return false;
	}
}
if ( ! function_exists( 'ct_get_hotel_thankyou_page' ) ) {
	function ct_get_hotel_thankyou_page() {
		global $ct_options;
		if ( ! empty( $ct_options['hotel_thankyou_page'] ) ) {
			return ct_get_permalink_clang( $ct_options['hotel_thankyou_page'] );
		}
		return false;
	}
}

/*
 * Return matched hotels to given data. It is used for check availability function
 */
if ( ! function_exists( 'ct_hotel_get_available_rooms' ) ) {
	function ct_hotel_get_available_rooms( $hotel_id, $from_date, $to_date, $adults=1, $kids=0 ) {

		// validation
		if ( empty( $hotel_id ) || 'hotel' != get_post_type( $hotel_id ) ) return esc_html__( 'Invalide Hotel ID.', 'citytours' ); //invalid data
		$hotel_id = esc_sql( ct_hotel_org_id( $hotel_id ) );
		if ( ! ct_strtotime( $from_date ) || ! ct_strtotime( $to_date ) || ( ct_strtotime( $from_date ) >= ct_strtotime( $to_date ) ) ) {
			return esc_html__( 'Invalid date. Please check your booking date again.', 'citytours' ); //invalid data
		}

		$minimum_stay = get_post_meta( $hotel_id, '_hotel_minimum_stay', true );
		if ( ! empty( $minimum_stay ) && ( ct_strtotime( $from_date .' + ' . $minimum_stay . ' days' ) > ct_strtotime( $to_date) ) ) {
			return sprintf( esc_html__( 'Minimum stay for this hotel is %d nights. Have another look at your dates and try again.', 'citytours' ), $minimum_stay );
		}

		// initiate variables
		global $wpdb;

		$sql = "SELECT pm0.post_id FROM " . $wpdb->postmeta . " as pm0 INNER JOIN " . $wpdb->posts . " AS room ON (pm0.post_id = room.ID) AND (room.post_status = 'publish') AND (room.post_type = 'room_type') WHERE meta_key = '_room_hotel_id' AND meta_value = " . esc_sql( $hotel_id );
		$bookable_room_ids = $wpdb->get_col( $sql );
		if ( empty( $bookable_room_ids ) ){
			if ( is_user_logged_in() ) {
				return esc_html__( 'This hotel does not have any rooms. Please create and set rooms in admin panel.', 'citytours' );
			} else {
				return esc_html__( 'No Rooms Available In This Hotel.', 'citytours' );
			}
		}

		// get available hotel room_type_id and price based on date
		// initiate variables
		$check_dates = array();
		$availability_data = array();

		// prepare date for loop
		$from_date_obj = new DateTime( '@' . ct_strtotime( $from_date ) );
		$to_date_obj = new DateTime( '@' . ct_strtotime( $to_date ) );
		$date_interval = DateInterval::createFromDateString('1 day');
		$period = new DatePeriod($from_date_obj, $date_interval, $to_date_obj);

		foreach ( $period as $dt ) {
			$check_date = esc_sql( $dt->format( "Y-m-d" ) );
			$check_dates[] = $check_date;

			$sql = "SELECT vacancies.room_type_id, vacancies.rooms - IFNULL(bookings.rooms,0) as available_rooms
					FROM (SELECT room_type_id, rooms, price_per_room, price_per_person, price_per_child
							FROM " . CT_HOTEL_VACANCIES_TABLE . " 
							WHERE 1=1 AND hotel_id='" . $hotel_id . "' AND room_type_id IN (" . implode( ',', $bookable_room_ids ) . ") AND date_from <= '" . $check_date . "'  AND date_to > '" . $check_date . "' ) AS vacancies
					LEFT JOIN (SELECT hotel_booking.room_type_id, SUM(hotel_booking.rooms) AS rooms 
							FROM " . CT_HOTEL_BOOKINGS_TABLE . " AS hotel_booking 
							INNER JOIN " . CT_ORDER_TABLE . " as hotel_order on hotel_order.id = hotel_booking.order_id
							WHERE 1=1 AND hotel_order.status!='cancelled' AND hotel_order.post_id='" . $hotel_id . "' AND hotel_order.date_to > '" . $check_date . "'  AND hotel_order.date_from <= '" . $check_date . "'" . " GROUP BY hotel_booking.room_type_id
					) AS bookings ON vacancies.room_type_id = bookings.room_type_id
					WHERE vacancies.rooms - IFNULL(bookings.rooms,0) >= 1;";

			$results = $wpdb->get_results( $sql ); // object (room_type_id, price_per_room, price_per_person, price_per_child, available_rooms)

			if ( empty( $results ) ) { //if no available rooms on selected date
				return esc_html__( 'No Rooms Available. Please have another look at booking date.', 'citytours' );
			}

			$day_on_room_ids = array();

			foreach ( $results as $result ) {
				$day_on_room_ids[] = $result->room_type_id;
				$availability_data[ $result->room_type_id ][ $check_date ] = $result->available_rooms;
			}

			$bookable_room_ids = $day_on_room_ids;
		}

		$return_value = array();
		foreach ( $bookable_room_ids as $room_id ) {
			$return_value[ $room_id ] = min( $availability_data[ $room_id ] );
		}
		return $return_value;
	}
}

/*
 * Calculate the price of selected hotel room and return price array data
 */
if ( ! function_exists( 'ct_hotel_calc_room_price' ) ) {
	function ct_hotel_calc_room_price( $hotel_id, $room_type_id, $from_date, $to_date, $rooms=1, $adults=1, $kids=0 ) {
		global $wpdb;

		$hotel_id = ct_hotel_org_id( $hotel_id );
		$room_type_id = ct_room_org_id( $room_type_id );
		//validation
		$room_hotel_id = get_post_meta( $room_type_id, '_room_hotel_id', true );
		if ( $room_hotel_id != $hotel_id ) return esc_html__( 'Room Type Id is not matched.', 'citytours' );

		$max_adults = get_post_meta( $room_type_id, '_room_max_adults', true ); if ( empty($max_adults) ) $max_adults = 0;
		$max_kids = get_post_meta( $room_type_id, '_room_max_kids', true ); if ( empty($max_adults) ) $max_kids = 0;
		$avg_adults = ceil( $adults / $rooms );
		$avg_kids = ceil( $kids / $rooms );
		if ( ( $avg_adults > $max_adults ) || ( ( $avg_adults + $avg_kids ) > ( $max_adults + $max_kids ) ) ) return esc_html__( 'Exceeds Max Guests.', 'citytours' );

		if ( ( time()-( 60*60*24 ) ) > ct_strtotime( $from_date ) ) return esc_html__( 'Wrong Check In date. Please check again.', 'citytours' );
		$minimum_stay = get_post_meta( $hotel_id, '_hotel_minimum_stay', true );
		$minimum_stay = is_numeric($minimum_stay)?$minimum_stay:0;
		if ( ! ct_strtotime( $from_date ) || ! ct_strtotime( $to_date ) || ( ct_strtotime( $from_date ) >= ct_strtotime( $to_date ) ) || ( ct_strtotime( $from_date .' + ' . $minimum_stay . ' days' ) > ct_strtotime( $to_date) ) ) return esc_html__( 'Wrong Booking Date. Please check again.', 'citytours' );

		$from_date_obj = new DateTime( '@' . ct_strtotime( $from_date ) );
		$to_date_obj = new DateTime( '@' . ct_strtotime( $to_date ) );
		$date_interval = DateInterval::createFromDateString('1 day');
		$period = new DatePeriod($from_date_obj, $date_interval, $to_date_obj);

		$price_data = array();
		$total_price = 0.0;

		$hotel_id = esc_sql( $hotel_id );
		$room_type_id = esc_sql( $room_type_id );
		$rooms = esc_sql( $rooms );
		$adults = esc_sql( $adults );
		$kids = esc_sql( $kids );

		foreach ( $period as $dt ) {

			$check_date = esc_sql( $dt->format( "Y-m-d" ) );
			$check_dates[] = $check_date;

			$sql = "SELECT vacancies.room_type_id, vacancies.price_per_room , vacancies.price_per_person, vacancies.price_per_child
					FROM (SELECT room_type_id, rooms, price_per_room, price_per_person, price_per_child
							FROM " . CT_HOTEL_VACANCIES_TABLE . " 
							WHERE 1=1 AND hotel_id='" . $hotel_id . "' AND room_type_id = '" . $room_type_id . "' AND date_from <= '" . $check_date . "'  AND date_to > '" . $check_date . "' ) AS vacancies
					LEFT JOIN (SELECT hotel_booking.room_type_id, SUM(hotel_booking.rooms) AS rooms 
							FROM " . CT_HOTEL_BOOKINGS_TABLE . " AS hotel_booking 
							INNER JOIN " . CT_ORDER_TABLE . " as hotel_order on hotel_order.id = hotel_booking.order_id
							WHERE 1=1 AND hotel_order.status!='cancelled' AND hotel_order.post_id='" . $hotel_id . "' AND hotel_booking.room_type_id = '" . $room_type_id . "' AND hotel_order.date_to > '" . $check_date . "' AND hotel_order.date_from <= '" . $check_date . "'" . ( ( empty( $except_booking_no ) || empty( $pin_code ) )?"":( " AND NOT ( hotel_order.booking_no = '" . $except_booking_no . "' AND hotel_order.pin_code = '" . $pin_code . "' )" ) ) . "
					) AS bookings ON vacancies.room_type_id = bookings.room_type_id
					WHERE vacancies.rooms - IFNULL(bookings.rooms,0) >= " . $rooms . ";";

			$result = $wpdb->get_row( $sql ); // object (room_type_id, price_per_room, price_per_person, price_per_child)

			if ( empty( $result ) ) { //if no available rooms on selected date
				return esc_html__( 'Sorry, The room you are booking now is just taken by another customer. Please have another look.', 'citytours' );
			} else {
				$price_per_room = (float) $result->price_per_room;
				$price_per_person = (float) $result->price_per_person;
				$price_per_child = (float) $result->price_per_child;

				$day_price = $price_per_room * $rooms + $price_per_person * $adults + $price_per_child * $kids;
				$price_data[ $check_date ] = array(
					'ppr' => $price_per_room,
					'ppp' => $price_per_person,
					'ppc' => $price_per_child,
					'total' => $day_price
				);
				$total_price += $day_price;
			}
		}

		$return_value = array(
			'check_dates' => $check_dates,
			'prices'      => $price_data,
			'total_price' => $total_price
		);

		return $return_value;
	}
}

/*
 * send booking confirmation email function
 */
if ( ! function_exists( 'ct_hotel_generate_conf_mail' ) ) {
	function ct_hotel_generate_conf_mail( $order, $type='new' ) {
		global $wpdb, $ct_options;
		$order_data = $order->get_order_info();
		if ( ! empty( $order_data ) ) {
			// server variables
			$admin_email = get_option('admin_email');
			$home_url = esc_url( home_url('/') );
			$site_name = $_SERVER['SERVER_NAME'];
			$logo_url = esc_url( ct_logo_url() );
			$order_data['hotel_id'] = ct_hotel_clang_id( $order_data['post_id'] );

			// hotel info
			$hotel_name = get_the_title( $order_data['hotel_id'] );
			$hotel_url = esc_url( ct_get_permalink_clang( $order_data['hotel_id'] ) );
			$hotel_thumbnail = get_the_post_thumbnail( $order_data['hotel_id'], 'medium' );
			$hotel_address = get_post_meta( $order_data['hotel_id'], '_hotel_address', true );
			$hotel_email = get_post_meta( $order_data['hotel_id'], '_hotel_email', true );
			$hotel_phone = get_post_meta( $order_data['hotel_id'], '_hotel_phone', true );

			// room info
			$booking_rooms = '<table><tbody><tr><th>' . esc_html__( 'Room Name', 'citytours' ) . '</th><th>' . esc_html__( 'Rooms', 'citytours' ) . '</th><th>' . esc_html__( 'Adults', 'citytours' ) . '</th><th>' . esc_html__( 'Kids', 'citytours' ) . '</th><th>' . esc_html__( 'Total Price', 'citytours' ) . '</th></tr>';
			$rooms_booking_data = $order->get_rooms();
			if ( ! empty( $rooms_booking_data ) ) {
				foreach ( $rooms_booking_data as $key => $room_data ) {
					$room_type_id = ct_room_clang_id( $room_data['room_type_id'] );
					$room_type_title = esc_html( get_the_title( $room_type_id ) );
					$booking_rooms .= '<tr><td>' . $room_type_title . '</td><td>' . $room_data['rooms'] . '</td><td>' . $room_data['adults'] . '</td><td>' . $room_data['kids'] . '</td><td>' . $room_data['total_price'] . '</td></tr>';
				}
			}
			$booking_rooms .= '</tbody></table>';

			// services info
			$booking_services = '<table><tbody><tr><th>' . esc_html__( 'Service Name', 'citytours' ) . '</th><th>' . esc_html__( 'Quantity', 'citytours' ) . '</th><th>' . esc_html__( 'Total Price', 'citytours' ) . '</th></tr>';
			$services_booking_data = $order->get_services();
			if ( ! empty( $services_booking_data ) ) {
				foreach ( $services_booking_data as $key => $service_booking_data ) {
					$service_data = ct_get_add_service( $service_booking_data['add_service_id'] );
					$service_quantity = $service_booking_data['qty'];
					$service_total_price = $service_booking_data['total_price'];
					$booking_services .= '<tr><td>' . $service_data->title . '</td><td>' . $service_quantity . '</td><td>' . $service_total_price . '</td></tr>';
				}
			}
			$booking_services .= '</tbody></table>';

			// booking info
			$date_from = new DateTime( $order_data['date_from'] );
			$date_to = new DateTime( $order_data['date_to'] );
			$number1 = $date_from->format('U');
			$number2 = $date_to->format('U');
			$booking_nights = ($number2 - $number1)/(3600*24);
			$booking_from_date = date( 'j F Y', strtotime( $order_data['date_from'] ) );
			$booking_to_date = date( 'j F Y', strtotime( $order_data['date_to'] ) );
			$booking_adults = $order_data['total_adults'];
			$booking_kids = $order_data['total_kids'];
			$booking_total_price = esc_html( ct_price( $order_data['total_price'] * $order_data['exchange_rate'], "", $order_data['currency_code'], 0 ) );
			$booking_deposit_price = esc_html( $order_data['deposit_price'] . $order_data['currency_code'] );
			$booking_deposit_paid = esc_html( empty( $order_data['deposit_paid'] ) ? 'No' : 'Yes' );
			$booking_no = $order_data['booking_no'];
			$booking_pincode = $order_data['pin_code'];

			// customer info
			$customer_first_name = $order_data['first_name'];
			$customer_last_name = $order_data['last_name'];
			$customer_email = $order_data['email'];
			$customer_country_code = $order_data['country'];
			$customer_phone = $order_data['phone'];
			$customer_address1 = $order_data['address1'];
			$customer_address2 = $order_data['address2'];
			$customer_city = $order_data['city'];
			$customer_zip = $order_data['zip'];
			$customer_country = $order_data['country'];
			$customer_special_requirements = $order_data['special_requirements'];

			$variables = array( 'home_url',
								'site_name',
								'logo_url',
								'hotel_name',
								'hotel_url',
								'hotel_thumbnail',
								'hotel_address',
								'hotel_email',
								'hotel_phone',
								'booking_rooms',
								'booking_services',
								'booking_no',
								'booking_pincode',
								'booking_from_date',
								'booking_to_date',
								'booking_nights',
								'booking_adults',
								'booking_kids',
								'booking_total_price',
								'booking_deposit_paid',
								'booking_deposit_price',
								'customer_first_name',
								'customer_last_name',
								'customer_email',
								'customer_country_code',
								'customer_phone',
								'customer_address1',
								'customer_address2',
								'customer_city',
								'customer_zip',
								'customer_country',
								'customer_special_requirements',
							);

			if ( empty( $subject ) ) {
				$subject = empty( $ct_options['hotel_confirm_email_subject'] ) ? 'Booking Confirmation Email Subject' : $ct_options['hotel_confirm_email_subject'];
			}

			if ( empty( $description ) ) {
				$description = empty( $ct_options['hotel_confirm_email_description'] ) ? 'Booking Confirmation Email Description' : $ct_options['hotel_confirm_email_description'];
			}

			foreach ( $variables as $variable ) {
				$subject = str_replace( "[" . $variable . "]", $$variable, $subject );
				$description = str_replace( "[" . $variable . "]", $$variable, $description );
			}

			$mail_sent = ct_send_mail( $site_name, $admin_email, $customer_email, $subject, $description );

			/* mailing function to admin */
			if ( ! empty( $ct_options['hotel_booked_notify_admin'] ) ) {
				$subject = empty( $ct_options['hotel_admin_email_subject'] ) ? 'You received a booking' : $ct_options['hotel_admin_email_subject'];
				$description = empty( $ct_options['hotel_admin_email_description'] ) ? 'Booking Details' : $ct_options['hotel_admin_email_description'];

				foreach ( $variables as $variable ) {
					$subject = str_replace( "[" . $variable . "]", $$variable, $subject );
					$description = str_replace( "[" . $variable . "]", $$variable, $description );
				}

				ct_send_mail( $site_name, $admin_email, $admin_email, $subject, $description );
			}
			return true;
		}
		return false;
	}
}


if ( ! function_exists( 'ct_hotel_get_search_result_count' ) ) {
	function ct_hotel_get_search_result_count( $args ) {
		global $ct_options, $wpdb;
		$district = array();
		$price_filter = array();
		$star_filter = array();
		$rating_filter = array();
		$facility_filter = array();
		extract( $args );
		$tbl_posts = esc_sql( $wpdb->posts );
		$tbl_postmeta = esc_sql( $wpdb->postmeta );
		$tbl_terms = esc_sql( $wpdb->prefix . 'terms' );
		$tbl_term_taxonomy = esc_sql( $wpdb->prefix . 'term_taxonomy' );
		$tbl_term_relationships = esc_sql( $wpdb->prefix . 'term_relationships' );
		$temp_tbl_name = ct_get_temp_table_name();
		$result = array();

		if ( $by == 'star' ) {

			$sql = "SELECT IFNULL( meta_star.meta_value, 0 ) as star, COUNT(*) AS counts FROM {$temp_tbl_name} as t1
					INNER JOIN {$tbl_posts} post_s1 ON (t1.hotel_id = post_s1.ID) AND (post_s1.post_status = 'publish') AND (post_s1.post_type = 'hotel') 
					LEFT JOIN {$tbl_postmeta} AS meta_star ON t1.hotel_id = meta_star.post_id AND meta_star.meta_key = '_hotel_star'";
			$where = ' 1=1';


			// district filter
			if ( ! empty( $district ) && trim( implode( '', $district ) ) != "" ) {
				$sql .= " INNER JOIN {$tbl_term_relationships} AS tr ON tr.object_id = t1.hotel_id 
						INNER JOIN {$tbl_term_taxonomy} AS tt ON tt.term_taxonomy_id = tr.term_taxonomy_id";
				$where .= " AND tt.taxonomy = 'district' AND tt.term_id IN (" . esc_sql( implode( ',', $district ) ) . ")";
			}

			// price filter
			if ( ! empty( $price_filter ) && trim( implode( '', $price_filter ) ) != "" ) {
				$sql .= " LEFT JOIN {$tbl_postmeta} AS meta_price ON t1.hotel_id = meta_price.post_id AND meta_price.meta_key = '_hotel_price'";
				$price_where = array();
				$price_steps = empty( $ct_options['hotel_price_filter_steps'] ) ? '50,80,100' : $ct_options['hotel_price_filter_steps'];
				$step_arr = explode( ',', $price_steps );
				array_unshift($step_arr, 0);
				foreach ( $price_filter as $index ) {
					if ( $index < count( $step_arr ) -1 ) {
						// 80 ~ 100 case
						$price_where[] = "( cast(meta_price.meta_value as unsigned) BETWEEN " . esc_sql( $step_arr[$index] ) . " AND " . esc_sql( $step_arr[$index+1] ) . " )";
					} else {
						// 200+ case
						$price_where[] = "( cast(meta_price.meta_value as unsigned) >= " . esc_sql( $step_arr[$index] ) . " )";
					}
				}
				$where .= " AND ( " . implode( ' OR ', $price_where ) . " )";
			}

			// hotel star filter

			// review filter
			if ( ! empty( $rating_filter ) && trim( implode( '', $rating_filter ) ) != "" ) {
				$sql .= " LEFT JOIN {$tbl_postmeta} AS meta_rating ON t1.hotel_id = meta_rating.post_id AND meta_rating.meta_key = '_review'";
				$where .= " AND round( cast( IFNULL( meta_rating.meta_value, 0 ) AS decimal(2,1) ) ) IN ( " . esc_sql( implode( ',', $rating_filter) ) . " )";
			}

			// facility filter
			if ( ! empty( $facility_filter ) && trim( implode( '', $facility_filter ) ) != "" ) {
				$where .= " AND (( SELECT COUNT(1) FROM {$tbl_term_relationships} AS tr1 
						INNER JOIN {$tbl_term_taxonomy} AS tt1 ON ( tr1.term_taxonomy_id= tt1.term_taxonomy_id )
						WHERE tt1.taxonomy = 'hotel_facility' AND tt1.term_id IN (" . esc_sql( implode( ',', $facility_filter ) ) . ") AND tr1.object_id = t1.hotel_id ) = " . count( $facility_filter ) . ")";
			}

			$sql .= " WHERE {$where} GROUP BY meta_star.meta_value";

			$result = $wpdb->get_results( $sql, ARRAY_A );
			$keys = array_map( function($a){return $a['star'];}, $result );
			$values = array_map( function($a){return $a['counts'];}, $result );
			$result = array_combine( $keys , $values );
		}
		return $result;
	}
}

/*
 * Get search result
 */
if ( ! function_exists( 'ct_hotel_get_search_result' ) ) {
	function ct_hotel_get_search_result( $args ) {
		global $ct_options, $wpdb;
		$s = '';
		$date_from = '';
		$date_to = '';
		$rooms = 1;
		$adults = 1;
		$kids = 0;
		$district = array();
		$price_filter = array();
		$star_filter = array();
		$rating_filter = array();
		$facility_filter = array();
		$order_by = '';
		$order = '';
		$last_no = 0;
		$per_page = ( isset( $ct_options['hotel_posts'] ) && is_numeric($ct_options['hotel_posts']) )?$ct_options['hotel_posts']:6;
		extract( $args );

		$order_array = array( 'ASC', 'DESC' );
		$order_by_array = array(
				'' => '',
				'price' => 'convert(meta_price.meta_value, decimal)',
				'rating' => 'meta_rating.meta_value'
			);
		if ( ! array_key_exists( $order_by , $order_by_array) ) $order_by = '';
		if ( ! in_array( $order , $order_array) ) $order = 'ASC';

		$tbl_posts = esc_sql( $wpdb->posts );
		$tbl_postmeta = esc_sql( $wpdb->postmeta );
		$tbl_terms = esc_sql( $wpdb->prefix . 'terms' );
		$tbl_term_taxonomy = esc_sql( $wpdb->prefix . 'term_taxonomy' );
		$tbl_term_relationships = esc_sql( $wpdb->prefix . 'term_relationships' );
		$tbl_icl_translations = esc_sql( $wpdb->prefix . 'icl_translations' );

		$temp_tbl_name = ct_get_temp_table_name();

		$from_date_obj = date_create_from_format( ct_get_date_format('php'), $date_from );
		$to_date_obj = date_create_from_format( ct_get_date_format('php'), $date_to );

		$s_query = ''; // sql for search keyword
		$c_query = ''; // sql for conditions ( review, avg_price, user_rating )
		$v_query = ''; // sql for vacancy check

		$s_query = "SELECT post_s1.ID AS hotel_id FROM {$tbl_posts} AS post_s1 
					WHERE (post_s1.post_status = 'publish') AND (post_s1.post_type = 'hotel')";
		if ( ! empty( $s ) ) {
			//mysql escape sting and like escape
			if ( floatval( get_bloginfo( 'version' ) ) >= 4.0 ) { $s = esc_sql( $wpdb->esc_like( $s ) ); } else { $s = esc_sql( like_escape( $s ) ); }
			$s_query .= " AND ((post_s1.post_title LIKE '%{$s}%') OR (post_s1.post_content LIKE '%{$s}%') )";
		}

		// if wpml is enabled do search by default language post
		if ( defined('ICL_LANGUAGE_CODE') && ( ct_get_lang_count() > 1 ) && ( ct_get_default_language() != ICL_LANGUAGE_CODE ) ) {
			$s_query = "SELECT DISTINCT it2.element_id AS hotel_id FROM ({$s_query}) AS t0
						INNER JOIN {$tbl_icl_translations} it1 ON (it1.element_type = 'post_hotel') AND it1.element_id = t0.hotel_id
						INNER JOIN {$tbl_icl_translations} it2 ON (it2.element_type = 'post_hotel') AND it2.language_code='" . ct_get_default_language() . "' AND it2.trid = it1.trid ";
		}

		$c_query = "SELECT t1.*, meta_c1.post_id AS room_id, meta_c2.meta_value AS max_adults, meta_c3.meta_value AS max_kids, meta_c4.meta_value AS minimum_stay
					FROM ( {$s_query} ) AS t1
					INNER JOIN {$tbl_postmeta} AS meta_c1 ON (meta_c1.meta_key = '_room_hotel_id') AND (t1.hotel_id = meta_c1.meta_value)
					INNER JOIN {$tbl_postmeta} AS meta_c2 ON (meta_c1.post_id = meta_c2.post_id) AND (meta_c2.meta_key='_room_max_adults')
					LEFT JOIN {$tbl_postmeta} AS meta_c3 ON (meta_c1.post_id = meta_c3.post_id) AND (meta_c3.meta_key='_room_max_kids')
					LEFT JOIN {$tbl_postmeta} AS meta_c4 ON (t1.hotel_id = meta_c4.post_id) AND (meta_c4.meta_key='_hotel_minimum_stay')";

		// if this searh has specified date then check vacancy and booking data, but if it doesn't have specified date then only check other search factors
		if ( $from_date_obj && $to_date_obj ) {
			// has specified date
			$date_interval = DateInterval::createFromDateString('1 day');
			$period = new DatePeriod( $from_date_obj, $date_interval, $to_date_obj );
			$sql_check_date_parts = array();
			$days = 0;
			foreach ( $period as $dt ) {
				$check_date = $dt->format( "Y-m-d" );
				$sql_check_date_parts[] = "SELECT '{$check_date}' AS check_date";
				$days++;
			}
			$sql_check_date = implode( ' UNION ', $sql_check_date_parts );

			$v_query = "SELECT t3.hotel_id, t3.room_id, t3.max_adults, t3.max_kids, t3.minimum_stay, MIN(rooms) AS min_rooms FROM (
							SELECT t2.*, (IFNULL(vacancies.rooms,0) - IFNULL(SUM(bookings.rooms),0)) AS rooms, check_dates.check_date 
							FROM ({$c_query}) AS t2
							JOIN ( {$sql_check_date} ) AS check_dates
							LEFT JOIN " . CT_HOTEL_VACANCIES_TABLE . " AS vacancies ON (vacancies.room_type_id = t2.room_id) AND (vacancies.date_from <= check_dates.check_date AND vacancies.date_to > check_dates.check_date)
							LEFT JOIN ( SELECT hotel_booking.*, hotel_order.date_from, hotel_order.date_to FROM " . CT_HOTEL_BOOKINGS_TABLE . " AS hotel_booking INNER JOIN " . CT_ORDER_TABLE . " as hotel_order ON hotel_order.id = hotel_booking.order_id AND hotel_order.status!='cancelled' ) AS bookings ON (bookings.room_type_id = t2.room_id) AND (bookings.date_from <= check_dates.check_date AND bookings.date_to > check_dates.check_date)
							GROUP BY t2.room_id, check_dates.check_date
						  ) AS t3
						  GROUP BY t3.room_id";

			// if rooms == 1 do specific search and if rooms > 1 do overal search for vacancies
			if ( $rooms == 1 ) {
				$sql = "SELECT t4.hotel_id, SUM(t4.min_rooms) AS rooms FROM ({$v_query}) AS t4
					WHERE ((t4.minimum_stay IS NULL) OR (t4.minimum_stay <= {$days}))
					  AND (t4.max_adults >= {$adults})
					  AND (t4.max_adults + IFNULL(t4.max_kids,0) >= {$adults} + {$kids})
					GROUP BY t4.hotel_id
					HAVING rooms >= {$rooms}";
			} else {
				$sql = "SELECT t4.hotel_id, SUM(t4.min_rooms) AS rooms, SUM(IFNULL(t4.max_adults,0) * t4.min_rooms) as hotel_max_adults, SUM(IFNULL(t4.max_kids,0) * t4.min_rooms) as hotel_max_kids FROM ({$v_query}) AS t4
					WHERE ((t4.minimum_stay IS NULL) OR (t4.minimum_stay <= {$days}))
					GROUP BY t4.hotel_id
					HAVING rooms >= {$rooms} AND hotel_max_adults >= {$adults} AND hotel_max_kids >= {$kids}";
			}

		} else {
			// without specified date
			$avg_adults = ceil( $adults / $rooms );
			$avg_kids = ceil( $kids / $rooms );
			$sql = "{$c_query} WHERE (meta_c2.meta_value >= {$avg_adults}) AND (meta_c2.meta_value + IFNULL(meta_c3.meta_value,0) >= {$avg_adults} + {$avg_kids}) GROUP BY hotel_id";
		}

		// if wpml is enabled return current language posts
		if ( defined('ICL_LANGUAGE_CODE') && ( ct_get_lang_count() > 1 ) && ( ct_get_default_language() != ICL_LANGUAGE_CODE ) ) {
			$sql = "SELECT it4.element_id AS hotel_id FROM ({$sql}) AS t5
					INNER JOIN {$tbl_icl_translations} it3 ON (it3.element_type = 'post_hotel') AND it3.element_id = t5.hotel_id
					INNER JOIN {$tbl_icl_translations} it4 ON (it4.element_type = 'post_hotel') AND it4.language_code='" . ICL_LANGUAGE_CODE . "' AND it4.trid = it3.trid";
		}

		// var_dump($sql);
		$sql = "CREATE TEMPORARY TABLE IF NOT EXISTS {$temp_tbl_name} AS " . $sql;
		$wpdb->query( $sql );

		$sql = " FROM {$temp_tbl_name} as t1
				INNER JOIN {$tbl_posts} post_s1 ON (t1.hotel_id = post_s1.ID) AND (post_s1.post_status = 'publish') AND (post_s1.post_type = 'hotel')";
		$where = ' 1=1';


		// district filter
		if ( ! empty( $district ) && trim( implode( '', $district ) ) != "" ) {
			$sql .= " INNER JOIN {$tbl_term_relationships} AS tr ON tr.object_id = post_s1.ID 
					INNER JOIN {$tbl_term_taxonomy} AS tt ON tt.term_taxonomy_id = tr.term_taxonomy_id";
			$where .= " AND tt.taxonomy = 'district' AND tt.term_id IN (" . esc_sql( implode( ',', $district ) ) . ")";
		}

		// price filter
		$sql .= " LEFT JOIN {$tbl_postmeta} AS meta_price ON post_s1.ID = meta_price.post_id AND meta_price.meta_key = '_hotel_price'";
		if ( ! empty( $price_filter ) && trim( implode( '', $price_filter ) ) != "" ) {
			$price_where = array();
			$price_steps = empty( $ct_options['hotel_price_filter_steps'] ) ? '50,80,100' : $ct_options['hotel_price_filter_steps'];
			$step_arr = explode( ',', $price_steps );
			array_unshift($step_arr, 0);
			foreach ( $price_filter as $index ) {
				if ( $index < count( $step_arr ) -1 ) {
					// 80 ~ 100 case
					$price_where[] = "( cast(meta_price.meta_value as unsigned) BETWEEN " . esc_sql( $step_arr[$index] ) . " AND " . esc_sql( $step_arr[$index+1] ) . " )";
				} else {
					// 200+ case
					$price_where[] = "( cast(meta_price.meta_value as unsigned) >= " . esc_sql( $step_arr[$index] ) . " )";
				}
			}
			$where .= " AND ( " . implode( ' OR ', $price_where ) . " )";
		}

		// hotel star filter
		if ( ! empty( $star_filter ) && trim( implode( '', $star_filter ) ) != "" ) {
			$sql .= " LEFT JOIN {$tbl_postmeta} AS meta_star ON post_s1.ID = meta_star.post_id AND meta_star.meta_key = '_hotel_star'";
			$where .= " AND IFNULL( meta_star.meta_value, 0 ) IN ( " . esc_sql( implode( ',', $star_filter) ) . " )";
		}

		// review filter
		$sql .= " LEFT JOIN {$tbl_postmeta} AS meta_rating ON post_s1.ID = meta_rating.post_id AND meta_rating.meta_key = '_review'";
		if ( ! empty( $rating_filter ) && trim( implode( '', $rating_filter ) ) != "" ) {
			$where .= " AND round( cast( IFNULL( meta_rating.meta_value, 0 ) AS decimal(2,1) ) ) IN ( " . esc_sql( implode( ',', $rating_filter) ) . " )";
		}

		// facility filter
		if ( ! empty( $facility_filter ) && trim( implode( '', $facility_filter ) ) != "" ) {
			$where .= " AND (( SELECT COUNT(1) FROM {$tbl_term_relationships} AS tr1 
					INNER JOIN {$tbl_term_taxonomy} AS tt1 ON ( tr1.term_taxonomy_id= tt1.term_taxonomy_id )
					WHERE tt1.taxonomy = 'hotel_facility' AND tt1.term_id IN (" . esc_sql( implode( ',', $facility_filter ) ) . ") AND tr1.object_id = post_s1.ID ) = " . count( $facility_filter ) . ")";
		}

		$sql .= " WHERE {$where}";

		$count_sql = "SELECT COUNT(DISTINCT t1.hotel_id)" . $sql;
		$count = $wpdb->get_var( $count_sql );

		if ( ! empty( $order_by ) ) {
			$sql .= " ORDER BY " . $order_by_array[$order_by] . " " . $order;
		}

		$sql .= " LIMIT {$last_no}, {$per_page};";

		$main_sql = "SELECT DISTINCT t1.hotel_id AS hotel_id" . $sql;

		$ids = $wpdb->get_results( $main_sql, ARRAY_A );

		return array( 'count' => $count, 'ids' => $ids );
	}
}

/*
 * Get hotels from ids
 */
if ( ! function_exists( 'ct_hotel_get_hotels_from_id' ) ) {
	function ct_hotel_get_hotels_from_id( $ids ) {
		if ( ! is_array( $ids ) ) return false;
		$results = array();
		foreach( $ids as $id ) {
			$result = get_post( $id );
			if ( ! empty( $result ) && ! is_wp_error( $result ) ) {
				if ( $result->post_type == 'hotel' ) $results[] = $result;
			}
		}
		return $results;
	}
}


/*
 * Get special( latest or featured ) hotels and return data
 */
if ( ! function_exists( 'ct_hotel_get_special_hotels' ) ) {
	function ct_hotel_get_special_hotels( $type='latest', $count=6, $exclude_ids, $district=array() ) {
		$args = array(
				'post_type'  => 'hotel',
				'suppress_filters' => 0,
				'posts_per_page' => $count,
				'post_status' => 'publish',
			);

		if ( ! empty( $exclude_ids ) ) {
			$args['post__not_in'] = $exclude_ids;
		}

		if ( ! empty( $district ) ) {
			if ( is_numeric( $district[0] ) ) {
			$args['tax_query'] = array(
					array(
						'taxonomy' => 'district',
						'field' => 'term_id',
							'terms' => $tour_districttype
							)
					);
			} else {
				$args['tax_query'] = array(
						array(
							'taxonomy' => 'district',
							'field' => 'name',
						'terms' => $district
						)
				);
		}
		}

		if ( $type == 'featured'  ) {
			$args = array_merge( $args, array(
				'orderby'    => 'rand',
				'meta_key'     => '_hotel_featured',
				'meta_value'   => '1',
			) );
			return get_posts( $args );
		} elseif ( $type == 'latest' ) {
			$args = array_merge( $args, array(
				'orderby' => 'post_date',
				'order' => 'DESC',
			) );
			return get_posts( $args );
		} elseif ( $type == 'popular' ) {
			global $wpdb;
			$tbl_postmeta = esc_sql( $wpdb->prefix . 'postmeta' );
			$tbl_terms = esc_sql( $wpdb->prefix . 'terms' );
			$tbl_term_taxonomy = esc_sql( $wpdb->prefix . 'term_taxonomy' );
			$tbl_term_relationships = esc_sql( $wpdb->prefix . 'term_relationships' );

			$date = date( 'Y-m-d', strtotime( '-30 days' ) );
			$sql = 'SELECT hotel_id, COUNT(*) AS booking_count FROM ' . CT_HOTEL_BOOKINGS_TABLE . ' AS booking
			INNER JOIN ' . CT_ORDER_TABLE . ' as _order ON _order.id = booking.order_id';
			$where = ' WHERE (_order.status <> "cancelled") AND (_order.created > %s)';
			if ( ! empty( $district ) ) {
				$sql .= " INNER JOIN {$tbl_term_relationships} AS tr ON tr.object_id = t1.hotel_id 
						INNER JOIN {$tbl_term_taxonomy} AS tt ON tt.term_taxonomy_id = tr.term_taxonomy_id";
				$where .= " AND tt.taxonomy = 'district' AND tt.term_id =" . esc_sql( $district ) . ")";
			}
			$sql .= $where . ' GROUP BY booking.hotel_id ORDER BY booking_count desc LIMIT %d';
			$popular_hotel = $wpdb->get_results( $wpdb->prepare( $sql, $date, $count ) );
			$result = array();
			if ( ! empty( $popular_hotel ) ) {
				foreach ( $popular_hotel as $hotel ) {
					$result[] = get_post( ct_hotel_clang_id( $hotel->hotel_id ) );
				}
			}
			// if booked room number in last month is smaller than count then add latest hotel
			if ( count( $popular_hotel ) < $count ) {
				foreach ( $popular_hotel as $hotel ) {
					$exclude_ids[] = ct_hotel_clang_id( $hotel->hotel_id );
				}
				$result = array_merge( $result, ct_hotel_get_special_hotels( 'latest', $count - count( $popular_hotel ), $exclude_ids, $district ) );
			}
			return $result;
		}
	}
}