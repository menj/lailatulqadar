<?php
/**
 * Front-end assets and colour scheme output.
 *
 * @package Lailatulqadar
 */

defined( 'ABSPATH' ) || exit;

/**
 * Enqueue the front-end stylesheet and the configured accent colours.
 */
function lq_enqueue_front() {
	wp_enqueue_style( 'lailatulqadar-front', LQ_URI . '/css/front.css', wp_style_is( 'twentytwentyfive-style', 'registered' ) ? array( 'twentytwentyfive-style' ) : array(), LQ_VERSION );

	$night = sanitize_hex_color( lq_setting( 'accent_night' ) );
	$dawn  = sanitize_hex_color( lq_setting( 'accent_dawn' ) );
	$css   = sprintf(
		':root{--lq-accent-night:%s;--lq-accent-dawn:%s;}',
		$night ? $night : '#c9a85c',
		$dawn ? $dawn : '#7a5a14'
	);
	wp_add_inline_style( 'lailatulqadar-front', $css );

	// Head script: applies a visitor's saved light/dark choice before paint.
	if ( 'hide' !== lq_setting( 'scheme_toggle' ) ) {
		wp_enqueue_script( 'lailatulqadar-scheme', LQ_URI . '/js/scheme.js', array(), LQ_VERSION, false );
	}
}
add_action( 'wp_enqueue_scripts', 'lq_enqueue_front', 20 );

/**
 * Add the colour scheme to the <html> element.
 *
 * @param string $output Existing language attributes.
 * @return string
 */
function lq_scheme_attribute( $output ) {
	if ( is_admin() ) {
		return $output;
	}
	$scheme = lq_setting( 'scheme' );
	if ( ! in_array( $scheme, array( 'night', 'dawn', 'auto' ), true ) ) {
		$scheme = 'night';
	}
	return $output . ' data-scheme="' . esc_attr( $scheme ) . '"';
}
add_filter( 'language_attributes', 'lq_scheme_attribute' );

/**
 * Whether the current view shows Qurʾānic text.
 *
 * @return bool
 */
function lq_has_quran() {
	if ( lq_is_home() ) {
		return true;
	}
	if ( ! is_singular() ) {
		return false;
	}
	$content = (string) get_post_field( 'post_content', get_queried_object_id() );
	return (bool) preg_match( '/lq-quran|lq-ayah|is-style-lq-quran|lailatulqadar\/front-/', $content );
}

/**
 * Preload the KFGQPC face where verses appear, so the verse renders in it
 * from the first paint.
 */
function lq_preload_quran_font() {
	if ( ! lq_has_quran() ) {
		return;
	}
	printf(
		'<link rel="preload" href="%s" as="font" type="font/ttf" crossorigin>' . "\n",
		esc_url( LQ_URI . '/fonts/kfgqpc/kfgqpc-hafs-uthmanic.ttf' )
	);
}
add_action( 'wp_head', 'lq_preload_quran_font', 2 );

/**
 * Favicon and touch icon from the logo mark, used until a Site Icon is set
 * in Settings > General (which then takes over, as WordPress intends).
 */
function lq_default_icons() {
	if ( has_site_icon() ) {
		return;
	}
	printf( '<link rel="icon" href="%s" type="image/svg+xml">' . "\n", esc_url( LQ_URI . '/images/lailatulqadar-mark.svg' ) );
	printf( '<link rel="icon" href="%s" sizes="32x32" type="image/png">' . "\n", esc_url( LQ_URI . '/images/favicon-32.png' ) );
	printf( '<link rel="apple-touch-icon" href="%s">' . "\n", esc_url( LQ_URI . '/images/apple-touch-icon.png' ) );
}
add_action( 'wp_head', 'lq_default_icons', 3 );
