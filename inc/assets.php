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
		'<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin>' . "\n",
		esc_url( LQ_URI . '/fonts/kfgqpc/kfgqpc-hafs-uthmanic.woff2' )
	);
}
add_action( 'wp_head', 'lq_preload_quran_font', 2 );

/**
 * Favicon, touch icon, web app manifest and theme colour from the logo mark,
 * used until a Site Icon is set in Settings > General (which then takes over,
 * as WordPress intends). The site, the login screen (inc/login.php) and the admin.
 */
function lq_default_icons() {
	if ( has_site_icon() ) {
		return;
	}
	printf( '<link rel="icon" href="%s" type="image/svg+xml">' . "\n", esc_url( LQ_URI . '/images/favicon.svg?ver=' . LQ_VERSION ) );
	printf( '<link rel="icon" href="%s" sizes="32x32" type="image/png">' . "\n", esc_url( LQ_URI . '/images/favicon-32.png?ver=' . LQ_VERSION ) );
	printf( '<link rel="apple-touch-icon" href="%s">' . "\n", esc_url( LQ_URI . '/images/apple-touch-icon.png?ver=' . LQ_VERSION ) );
	printf( '<link rel="manifest" href="%s">' . "\n", esc_url( LQ_URI . '/images/site.webmanifest?ver=' . LQ_VERSION ) );
	echo '<meta name="theme-color" content="#141b2e">' . "\n";
}
add_action( 'wp_head', 'lq_default_icons', 3 );
add_action( 'admin_head', 'lq_default_icons' );

/**
 * Browsers and bots ask for /favicon.ico on their own. Until a Site Icon is
 * set, answer with images/favicon.ico: a day's cache and an ETag, with a 304
 * while it is unchanged. With a Site Icon, WordPress redirects to it.
 */
function lq_serve_favicon() {
	$file = LQ_DIR . '/images/favicon.ico';
	if ( has_site_icon() || ! is_readable( $file ) ) {
		return;
	}
	$etag = '"' . md5_file( $file ) . '"';
	header( 'Content-Type: image/x-icon' );
	header( 'Cache-Control: public, max-age=86400' );
	header( 'ETag: ' . $etag );
	// If-None-Match may list several validators, weak ones (W/"...") or *.
	$sent = array_map(
		static function ( $v ) {
			return preg_replace( '#^W/#', '', trim( $v ) );
		},
		explode( ',', wp_unslash( (string) ( $_SERVER['HTTP_IF_NONE_MATCH'] ?? '' ) ) ) // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	);
	if ( in_array( '*', $sent, true ) || in_array( $etag, $sent, true ) ) {
		status_header( 304 );
		exit;
	}
	status_header( 200 );
	header( 'Content-Length: ' . (string) filesize( $file ) );
	// A HEAD request gets the headers alone.
	if ( 'HEAD' !== ( $_SERVER['REQUEST_METHOD'] ?? 'GET' ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		readfile( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	}
	exit;
}
add_action( 'do_faviconico', 'lq_serve_favicon', 5 );

/**
 * WordPress ends a HEAD request before do_favicon, with an empty HTML answer.
 * template_redirect runs first, so a HEAD request gets the icon's headers too.
 */
function lq_favicon_head_request() {
	if ( is_favicon() ) {
		lq_serve_favicon();
	}
}
add_action( 'template_redirect', 'lq_favicon_head_request', 0 );
