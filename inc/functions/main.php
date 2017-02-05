<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

require_once CT_INC_DIR . '/lib/class-tgm-plugin-activation.php';
require_once CT_INC_DIR . '/lib/payment/main.php';
require_once CT_INC_DIR . '/functions/db.php';
require_once CT_INC_DIR . '/functions/functions.php';
require_once CT_INC_DIR . '/functions/template-functions.php';
require_once CT_INC_DIR . '/functions/metaboxes.php';
require_once CT_INC_DIR . '/functions/taxonomy-meta.php';
require_once CT_INC_DIR . '/functions/widget.php';
require_once CT_INC_DIR . '/functions/wpml.php';
require_once CT_INC_DIR . '/functions/currency.php';

add_filter( 'template_include', 'ct_template_chooser', 99 );
add_action( 'wp_ajax_get_more_reviews', 'ct_ajax_get_more_reviews' );
add_action( 'wp_ajax_nopriv_get_more_reviews', 'ct_ajax_get_more_reviews' );
add_action( 'wp_ajax_submit_review', 'ct_ajax_submit_review' );
add_action( 'wp_ajax_nopriv_submit_review', 'ct_ajax_submit_review' );
add_action( 'switch_theme', 'ct_switch_theme' );
add_action( 'after_switch_theme', 'ct_after_switch_theme' );
add_action( 'get_header', 'ct_init_currency' );

add_action( 'wp_ajax_tnd_get_hotel_room_list', 'tnd_get_hotel_room_list' );
add_action( 'wp_ajax_nopriv_tnd_get_hotel_room_list', 'tnd_get_hotel_room_list' );
add_action( 'wp_ajax_tnd_ajax_get_hotel_room_price', 'tnd_ajax_get_hotel_room_price' );
add_action( 'wp_ajax_nopriv_tnd_ajax_get_hotel_room_price', 'tnd_ajax_get_hotel_room_price' );