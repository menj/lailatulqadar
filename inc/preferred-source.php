<?php
/**
 * Google preferred sources: a button that lets a reader add the site as a
 * preferred source in Google Search, so its pages show more often in Top
 * Stories and carry the "preferred" badge there and in AI Mode.
 *
 * Drawn by the theme in its own colours (a pill with the eight-pointed
 * star, gold on night, bronze on dawn) rather than Google's badge. Two
 * modes, set in Theme Options > Reading and sharing:
 *
 * - link (default): the button is a plain link to Google's source
 *   preferences tool for the site's domain. No script from Google loads.
 * - button: Google's preferred sources library (news.google.com) loads on
 *   the pages that show the button, with manual control, and the same
 *   button opens Google's flow in place, which returns the reader to the
 *   page. Until the library is ready, or if it fails, the link still works.
 *
 * Shown on guide pages beside the page details and in the footer; also the
 * Preferred source block and the [lailatulqadar_preferred_source]
 * shortcode. Google lists domains and subdomains only, so on a site that
 * lives in a subdirectory nothing prints.
 *
 * @package Lailatulqadar
 * @since   1.23.0
 */

defined( 'ABSPATH' ) || exit;

const LAILATULQADAR_PREFSRC_TOOL    = 'https://www.google.com/preferences/source';
const LAILATULQADAR_PREFSRC_LIBRARY = 'https://news.google.com/swg/js/v1/publisher.js';

/**
 * The mode: link, button or off.
 *
 * @return string
 */
function lailatulqadar_prefsrc_mode() {
	$mode = (string) lq_setting( 'prefsrc_mode' );
	return in_array( $mode, array( 'link', 'button', 'off' ), true ) ? $mode : 'link';
}

/**
 * The domain Google's tool takes, or '' when the site is not eligible (it
 * lives in a subdirectory, or the address has no host).
 *
 * @return string
 */
function lailatulqadar_prefsrc_domain() {
	$home = (string) home_url( '/' );
	$host = (string) wp_parse_url( $home, PHP_URL_HOST );
	$path = trim( (string) wp_parse_url( $home, PHP_URL_PATH ), '/' );
	$host = '' === $path ? strtolower( $host ) : '';
	return (string) apply_filters( 'lailatulqadar_preferred_source_domain', $host );
}

/**
 * The address of the site in Google's source preferences tool.
 *
 * @return string
 */
function lailatulqadar_prefsrc_url() {
	$domain = lailatulqadar_prefsrc_domain();
	return '' === $domain ? '' : add_query_arg( 'q', rawurlencode( $domain ), LAILATULQADAR_PREFSRC_TOOL );
}

/**
 * Whether the button prints in a place: footer, guide or inline (block
 * elsewhere, shortcode).
 *
 * @param string $place Place.
 * @return bool
 */
function lailatulqadar_prefsrc_shows( $place ) {
	if ( 'off' === lailatulqadar_prefsrc_mode() || '' === lailatulqadar_prefsrc_domain() ) {
		return false;
	}
	if ( 'footer' === $place ) {
		return '1' === lq_setting( 'prefsrc_footer' );
	}
	if ( 'guide' === $place ) {
		return '1' === lq_setting( 'prefsrc_guide' );
	}
	return true;
}

/**
 * The button, or '' where it does not show. Its words follow the page
 * language.
 *
 * @param string $place footer, guide or inline.
 * @return string
 */
function lailatulqadar_prefsrc_html( $place = 'inline' ) {
	$place = sanitize_key( $place );
	if ( ! lailatulqadar_prefsrc_shows( $place ) ) {
		return '';
	}
	$url  = lailatulqadar_prefsrc_url();
	$text = lailatulqadar_t( 'Add as a preferred source on Google', 'Tambah sebagai sumber pilihan di Google' );
	$star = '<svg class="lq-prefsrc__icon" width="16" height="16" viewBox="0 0 40 40" aria-hidden="true" focusable="false"><rect x="8" y="8" width="24" height="24" fill="none" stroke="currentColor" stroke-width="3"/><rect x="8" y="8" width="24" height="24" fill="none" stroke="currentColor" stroke-width="3" transform="rotate(45 20 20)"/></svg>';
	return '<a class="lq-prefsrc lq-prefsrc--' . esc_attr( $place ) . '" href="' . esc_url( $url ) . '" rel="noopener" data-lq-prefsrc>' . $star . '<span class="lq-prefsrc__text">' . esc_html( $text ) . '</span></a>';
}

/**
 * Whether Google's library should load on this view: button mode and a
 * place on the view that shows the button.
 *
 * @return bool
 */
function lailatulqadar_prefsrc_needs_library() {
	if ( is_admin() || 'button' !== lailatulqadar_prefsrc_mode() || '' === lailatulqadar_prefsrc_domain() ) {
		return false;
	}
	if ( lailatulqadar_prefsrc_shows( 'footer' ) ) {
		return true;
	}
	if ( lailatulqadar_prefsrc_shows( 'guide' ) && function_exists( 'lailatulqadar_current_guide_page' ) && lailatulqadar_current_guide_page() ) {
		return true;
	}
	if ( is_singular() ) {
		$post = get_post( get_queried_object_id() );
		return $post && ( has_shortcode( $post->post_content, 'lailatulqadar_preferred_source' ) || has_block( 'lailatulqadar/preferred-source', $post ) );
	}
	return false;
}

/**
 * In button mode, Google's library (manual control, so it draws no badge of
 * its own) and the theme's script that hands it the button.
 */
function lailatulqadar_prefsrc_enqueue() {
	if ( ! lailatulqadar_prefsrc_needs_library() ) {
		return;
	}
	wp_enqueue_script( 'lailatulqadar-prefsrc-google', LAILATULQADAR_PREFSRC_LIBRARY, array(), null, array( 'strategy' => 'async', 'in_footer' => false ) ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion -- Google's library, versioned by its address.
	wp_enqueue_script( 'lailatulqadar-prefsrc', LQ_URI . '/js/preferred-source.js', array(), LQ_VERSION, array( 'strategy' => 'defer', 'in_footer' => true ) );
}
add_action( 'wp_enqueue_scripts', 'lailatulqadar_prefsrc_enqueue', 25 );

/**
 * Mark Google's library for manual control.
 *
 * @param string $tag    Script tag.
 * @param string $handle Handle.
 * @return string
 */
function lailatulqadar_prefsrc_script_tag( $tag, $handle ) {
	if ( 'lailatulqadar-prefsrc-google' !== $handle || false !== strpos( $tag, 'preferred-sources-control' ) ) {
		return $tag;
	}
	return str_replace( ' src=', ' preferred-sources-control="manual" src=', $tag );
}
add_filter( 'script_loader_tag', 'lailatulqadar_prefsrc_script_tag', 10, 2 );

/**
 * [lailatulqadar_preferred_source]
 *
 * @return string
 */
function lailatulqadar_prefsrc_shortcode() {
	return lailatulqadar_prefsrc_html( 'inline' );
}
add_shortcode( 'lailatulqadar_preferred_source', 'lailatulqadar_prefsrc_shortcode' );

/**
 * The block.
 */
function lailatulqadar_prefsrc_register() {
	register_block_type( LQ_DIR . '/blocks/preferred-source' );
}
add_action( 'init', 'lailatulqadar_prefsrc_register' );
