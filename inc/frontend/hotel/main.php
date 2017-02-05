<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

require_once( CT_INC_DIR . '/frontend/hotel/functions.php');
require_once( CT_INC_DIR . '/frontend/hotel/templates.php');
require_once( CT_INC_DIR . '/frontend/hotel/ajax.php');
require_once( CT_INC_DIR . '/frontend/hotel/class.order.php');
require_once( CT_INC_DIR . '/frontend/hotel/class.cart.php');

add_action( 'ct_hotel_booking_wrong_data', 'ct_redirect_home' );
add_action( 'ct_hotel_thankyou_wrong_data', 'ct_redirect_home' );
add_action( 'wp_ajax_ct_hotel_update_cart', 'ct_hotel_update_cart' );
add_action( 'wp_ajax_nopriv_ct_hotel_update_cart', 'ct_hotel_update_cart' );
add_action( 'wp_ajax_ct_hotel_submit_booking', 'ct_hotel_submit_booking' );
add_action( 'wp_ajax_nopriv_ct_hotel_submit_booking', 'ct_hotel_submit_booking' );