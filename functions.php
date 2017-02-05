<?php
if ( ! session_id() ) {
session_start();
}
//constants
define( 'CT_VERSION', '1.2.1' );
define( 'CT_DB_VERSION', '1.1' );
define( 'CT_TEMPLATE_DIRECTORY_URI', get_template_directory_uri() );
define( 'CT_IMAGE_URL', CT_TEMPLATE_DIRECTORY_URI . '/img' );
define( 'CT_INC_DIR', get_template_directory() . '/inc' );
define( 'CT_TAX_META_DIR_URL', CT_TEMPLATE_DIRECTORY_URI . '/inc/lib/tax-meta-class/' );
global $wpdb;
define( 'CT_HOTEL_VACANCIES_TABLE', $wpdb->prefix . 'ct_hotel_vacancies' );
define( 'CT_HOTEL_BOOKINGS_TABLE', $wpdb->prefix . 'ct_hotel_bookings' );
define( 'CT_REVIEWS_TABLE', $wpdb->prefix . 'ct_reviews' );
// define( 'CT_MODE', 'product' );
define( 'CT_ADD_SERVICES_TABLE', $wpdb->prefix . 'ct_add_services' );
define( 'CT_ADD_SERVICES_BOOKINGS_TABLE', $wpdb->prefix . 'ct_add_service_bookings' );
define( 'CT_TOUR_SCHEDULES_TABLE', $wpdb->prefix . 'ct_tour_schedules' );
define( 'CT_TOUR_SCHEDULE_META_TABLE', $wpdb->prefix . 'ct_tour_schedule_meta' );
define( 'CT_TOUR_BOOKINGS_TABLE', $wpdb->prefix . 'ct_tour_bookings' );
define( 'CT_CURRENCIES_TABLE', $wpdb->prefix . 'ct_currencies' );
define( 'CT_ORDER_TABLE', $wpdb->prefix . 'ct_order' );
define( 'CT_MODE', 'dev' );

if ( ! class_exists( 'ReduxFramework' ) ) {
    require_once( CT_INC_DIR . '/lib/redux-framework/ReduxCore/framework.php' );
}
if ( ! isset( $redux_demo ) ) {
    require_once( CT_INC_DIR . '/lib/redux-framework/config.php' );
}

//require files
if ( ! defined( 'RWMB_URL' ) ) {
	define( 'RWMB_URL', CT_TEMPLATE_DIRECTORY_URI . '/inc/lib/meta-box/' );
require_once( CT_INC_DIR . '/lib/meta-box/meta-box.php' );
}
require_once( CT_INC_DIR . '/lib/multiple_sidebars.php' );
require_once( CT_INC_DIR . '/functions/main.php' );
require_once( CT_INC_DIR . '/shortcode/init.php' );
require_once( CT_INC_DIR . '/js_composer/init.php' );
require_once( CT_INC_DIR . '/admin/main.php');
require_once( CT_INC_DIR . '/frontend/main.php');

// Translation
load_theme_textdomain('citytours', get_template_directory() . '/languages');

//theme supports
add_theme_support( 'automatic-feed-links' );
add_theme_support( 'post-thumbnails' );

global $wp_version;
if ( version_compare( $wp_version, '4.1', '>=' ) ) {
	add_theme_support( 'title-tag' );
	add_filter( 'wp_title', 'ct_wp_title', 10, 2 );
} else {
	add_filter( 'wp_title', 'ct_wp_title_old', 10, 2 );
}
if ( ! isset( $content_width ) ) $content_width = 900;

add_image_size( 'ct-list-thumb', 400, 267, true );
add_image_size( 'ct-map-thumb', 280, 140, true );
//add_image_size( 'ct-list-thumb', 800, 533, true );

//actions
add_action( 'init', 'ct_init' );
add_action( 'wp_enqueue_scripts', 'ct_enqueue_scripts' );
add_action( 'wp_footer', 'ct_inline_script' );
add_action( 'tgmpa_register', 'ct_register_required_plugins' );
add_action( 'admin_menu', 'ct_remove_redux_menu',12 );
add_action( 'widgets_init', 'ct_register_sidebar' );
// add_action( 'user_register', 'ct_user_register' );
add_action( 'wp_login_failed', 'ct_login_failed' );
add_action( 'lost_password', 'ct_lost_password' );
add_action( 'comment_form_before', 'ct_enqueue_comment_reply' );

add_filter( '404_template', 'ct_show404' );
add_filter( 'authenticate', 'ct_authenticate', 1, 3);
add_filter( 'get_default_comment_status', 'ct_open_comments_for_myposttype', 10, 3 );

remove_action( 'admin_enqueue_scripts', 'wp_auth_check_load' );
remove_filter('the_content', 'wpautop'); // disable auto para
