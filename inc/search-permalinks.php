<?php
/**
 * Pretty search URLs: /?s=term becomes /search/term/.
 *
 * Native replacement for the Pretty Search Permalinks plugin (wp-seo-search
 * 1.3), set in Theme Options > Reading and sharing and, as the plugin
 * allowed, in Settings > Permalinks. Differences from the plugin: terms are
 * encoded with rawurlencode(), so a space is %20; an empty search is never
 * redirected; the redirect is a 301; ?paged=2 keeps its page
 * (/search/term/page/2/); and ?lang=ms stays on the address, so a Malay
 * search stays in Malay. Rewrite rules are flushed once per theme version.
 * The theme steps aside while the plugin is active.
 *
 * @package Lailatulqadar
 * @since   1.17.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Whether the theme rewrites searches.
 *
 * @return bool
 */
function lailatulqadar_pretty_search_active() {
	return '1' === lq_setting( 'pretty_search' ) && ! function_exists( 'wpseosearch_rewrite' );
}

/**
 * The search base. A base saved by the plugin is used while the theme
 * setting is still the default.
 *
 * @return string
 */
function lailatulqadar_search_base() {
	$base = sanitize_title( (string) lq_setting( 'search_base' ) );
	if ( 'search' === $base || '' === $base ) {
		$legacy = sanitize_title( (string) get_option( 'wpseosearch_base', '' ) );
		$base   = '' !== $legacy ? $legacy : 'search';
	}
	return $base;
}

/**
 * Tell WordPress which segment carries the search terms.
 */
function lailatulqadar_set_search_base() {
	global $wp_rewrite;
	if ( lailatulqadar_pretty_search_active() && $wp_rewrite instanceof WP_Rewrite ) {
		$wp_rewrite->search_base = lailatulqadar_search_base();
	}
}
add_action( 'init', 'lailatulqadar_set_search_base', 1 );

/**
 * 301 from the query-string form to the path form.
 */
function lailatulqadar_pretty_search_redirect() {
	global $wp_rewrite;
	if ( ! lailatulqadar_pretty_search_active() || ! is_search() || is_admin() || ! $wp_rewrite->using_permalinks() ) {
		return;
	}
	$term = get_query_var( 's' );
	if ( ! is_string( $term ) || '' === trim( $term ) ) {
		return;
	}
	$uri  = isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
	$path = (string) wp_parse_url( $uri, PHP_URL_PATH );
	$base = lailatulqadar_search_base();
	if ( false !== strpos( $path, '/' . $base . '/' ) ) {
		return;
	}
	$paged = max( 1, (int) get_query_var( 'paged' ) );
	$url   = home_url( '/' . $base . '/' . rawurlencode( $term ) . '/' . ( $paged > 1 ? 'page/' . $paged . '/' : '' ) );
	$lang  = isset( $_GET['lang'] ) ? sanitize_key( wp_unslash( $_GET['lang'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- language hint only.
	if ( '' !== $lang && lq_setting( 'secondary_slug' ) === $lang ) {
		$url = add_query_arg( 'lang', $lang, $url );
	}
	wp_safe_redirect( $url, 301 );
	exit;
}
add_action( 'template_redirect', 'lailatulqadar_pretty_search_redirect', 5 );

/**
 * Flush once per theme version, and once after the search settings change.
 * Never on an ordinary load.
 */
function lailatulqadar_search_maybe_flush() {
	$want = LQ_VERSION . '|' . ( lailatulqadar_pretty_search_active() ? lailatulqadar_search_base() : '' );
	if ( get_option( 'lailatulqadar_rewrite_version' ) !== $want ) {
		update_option( 'lailatulqadar_rewrite_version', $want );
		flush_rewrite_rules( false );
	}
}
add_action( 'init', 'lailatulqadar_search_maybe_flush', 99 );

/**
 * Search base field in Settings > Permalinks > Optional, as the plugin had.
 * It writes the same setting as Theme Options.
 */
function lailatulqadar_search_permalink_settings() {
	if ( ! current_user_can( 'manage_options' ) || function_exists( 'wpseosearch_rewrite' ) ) {
		return;
	}
	if ( isset( $_POST['lailatulqadar_search_base'] ) ) {
		check_admin_referer( 'update-permalink' );
		$base   = sanitize_title( wp_unslash( $_POST['lailatulqadar_search_base'] ) );
		$stored = wp_parse_args( (array) get_option( LQ_OPTION, array() ), lq_default_settings() );
		// Saved as the Reading and sharing tab would save it, so the
		// settings sanitiser merges this one key into the stored array.
		$stored['_tab']        = 'reading';
		$stored['search_base'] = '' !== $base ? $base : 'search';
		update_option( LQ_OPTION, $stored );
		lailatulqadar_set_search_base();
	}
	add_settings_field( 'lailatulqadar_search_base', __( 'Search base', 'lailatulqadar' ), 'lailatulqadar_search_permalink_input', 'permalink', 'optional' );
}
add_action( 'load-options-permalink.php', 'lailatulqadar_search_permalink_settings' );

/**
 * The Settings > Permalinks input.
 */
function lailatulqadar_search_permalink_input() {
	echo '<input type="text" name="lailatulqadar_search_base" id="lailatulqadar_search_base" class="regular-text code" value="' . esc_attr( lailatulqadar_search_base() ) . '">';
	echo '<p class="description">' . esc_html__( 'Search results open at /search-base/term/. Also set in Theme Options, Reading and sharing.', 'lailatulqadar' ) . '</p>';
}
