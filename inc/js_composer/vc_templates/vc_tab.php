<?php

extract( shortcode_atts( array(
	'tab_id'	=> '',
	'active'=> '',
	'class' => ''
), $atts) );

$classes = array( 'tab-pane' );
if ( $active == 'true' ) {
	$classes[] = 'active';
}
if ( $class != '' )  {
	$classes[] = $class;
}
echo sprintf( '<div id="%s" class="%s">%s</div>',
	esc_attr( $tab_id ),
	esc_attr( implode(' ', $classes) ),
	wpb_js_remove_wpautop( $content )
);