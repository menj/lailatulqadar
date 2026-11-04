<?php
/**
 * Reading progress bar on single pages, set in Theme Options > Reading and
 * sharing: on or off, colour, height, top or bottom, and the post types.
 * Native replacement for the Reading Progress Bar plugin; the theme bar
 * steps aside while that plugin is active.
 *
 * The bar is aria-hidden. It repeats the scroll position the browser already
 * gives every reader, and a progressbar that changed on every scroll would
 * flood a screen reader with announcements.
 *
 * @package Lailatulqadar
 * @since   1.17.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Post types the bar may show on (public, not attachments).
 *
 * @return string[]
 */
function lailatulqadar_rpb_available_types() {
	$types = get_post_types( array( 'public' => true ), 'names' );
	unset( $types['attachment'] );
	return array_values( $types );
}

/**
 * Chosen post types.
 *
 * @return string[]
 */
function lailatulqadar_rpb_types() {
	return array_values( array_filter( array_map( 'sanitize_key', explode( ',', (string) lq_setting( 'rpb_types' ) ) ) ) );
}

/**
 * Whether the bar shows on this request. Not on the language homes.
 *
 * @return bool
 */
function lailatulqadar_rpb_applies() {
	if ( '1' !== lq_setting( 'rpb_enabled' ) || function_exists( 'rp_show_it' ) ) {
		return false;
	}
	if ( ! is_singular() || is_front_page() || lq_is_home() ) {
		return false;
	}
	return in_array( get_post_type(), lailatulqadar_rpb_types(), true );
}

/**
 * Assets, on the pages that show the bar only.
 */
function lailatulqadar_rpb_enqueue() {
	if ( ! lailatulqadar_rpb_applies() ) {
		return;
	}
	wp_enqueue_style( 'lailatulqadar-reading-progress', LQ_URI . '/css/reading-progress.css', array(), LQ_VERSION );
	wp_enqueue_script( 'lailatulqadar-reading-progress', LQ_URI . '/js/reading-progress.js', array(), LQ_VERSION, array( 'in_footer' => true, 'strategy' => 'defer' ) );
}
add_action( 'wp_enqueue_scripts', 'lailatulqadar_rpb_enqueue', 25 );

/**
 * Print the bar first thing in the body. An empty colour follows the
 * scheme's accent.
 */
function lailatulqadar_rpb_render() {
	if ( ! lailatulqadar_rpb_applies() ) {
		return;
	}
	$style  = '';
	$color  = sanitize_hex_color( (string) lq_setting( 'rpb_color' ) );
	$height = max( 2, min( 8, (int) lq_setting( 'rpb_height' ) ) );
	if ( $color ) {
		$style .= '--lq-rpb-color:' . $color . ';';
	}
	$style .= '--lq-rpb-height:' . $height . 'px;';
	$pos    = 'bottom' === lq_setting( 'rpb_position' ) ? 'bottom' : 'top';
	echo '<div class="lq-rpb lq-rpb--' . esc_attr( $pos ) . '" aria-hidden="true" style="' . esc_attr( $style ) . '"><div class="lq-rpb__fill"></div></div>';
}
add_action( 'wp_body_open', 'lailatulqadar_rpb_render', 5 );
