<?php
/**
 * Pretty search URLs: /?s=term becomes /search/term/.
 *
 * Native replacement for the Pretty Search Permalinks plugin (wp-seo-search
 * 1.3), set in Theme Options > Reading and sharing and, as the plugin
 * allowed, in Settings > Permalinks. Differences from the plugin: terms are
 * encoded with rawurlencode(), so a space is %20; an empty search is never
 * redirected; the redirect is a 301; ?paged=2 keeps its page
 * (/search/term/page/2/); and every other argument stays on the address,
 * ?lang=ms included, so a Malay search stays in Malay. Rewrite rules are flushed once per theme version.
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
 * Slugs a search base may not take: the language prefixes, every guide page
 * slug in the registry and the paths WordPress and the theme route.
 *
 * @return string[]
 */
function lailatulqadar_search_reserved_bases() {
	$reserved = array( 'page', 'feed', 'comments', 'wp-json', 'wp-admin', 'wp-content', 'wp-includes', 'wp-sitemap', 'image-sitemap', 'category', 'tag', 'author', 'embed', 'trackback', 'sitemap', lq_setting( 'primary_slug' ), lq_setting( 'secondary_slug' ) );
	if ( function_exists( 'lq_page_definitions' ) ) {
		foreach ( lq_page_definitions() as $def ) {
			foreach ( array( 'primary', 'secondary' ) as $role ) {
				if ( isset( $def[ $role ]['slug'] ) && is_string( $def[ $role ]['slug'] ) ) {
					$reserved[] = $def[ $role ]['slug'];
				}
			}
		}
	}
	return array_values( array_filter( array_map( 'strval', (array) apply_filters( 'lailatulqadar_search_reserved_bases', $reserved ) ) ) );
}

/**
 * A usable base from raw input: sanitize_key() (lowercase letters, digits,
 * hyphens and underscores) and never a reserved slug; '' otherwise.
 *
 * @param mixed $raw Raw value.
 * @return string
 */
function lailatulqadar_search_clean_base( $raw ) {
	$base = sanitize_key( is_scalar( $raw ) ? (string) $raw : '' );
	return ( '' === $base || in_array( $base, lailatulqadar_search_reserved_bases(), true ) ) ? '' : $base;
}

/**
 * The search base. A base saved by the plugin is used while the theme
 * setting is still the default.
 *
 * @return string
 */
function lailatulqadar_search_base() {
	$base = lailatulqadar_search_clean_base( lq_setting( 'search_base' ) );
	if ( 'search' === $base || '' === $base ) {
		$legacy = lailatulqadar_search_clean_base( get_option( 'wpseosearch_base', '' ) );
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
	// Every other argument stays: ?lang=ms keeps a Malay search in Malay, and
	// arrays such as post_type[] survive.
	$args = wp_unslash( $_GET ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- carried over to the new address, encoded below.
	unset( $args['s'], $args['paged'] );
	if ( $args ) {
		$url .= '?' . http_build_query( $args, '', '&', PHP_QUERY_RFC3986 );
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
	global $wp_rewrite;
	$want = LQ_VERSION . '|' . ( lailatulqadar_pretty_search_active() ? lailatulqadar_search_base() : '' );
	if ( get_option( 'lailatulqadar_rewrite_version' ) === $want ) {
		return;
	}
	// Plain permalinks keep no rules, so there is nothing to wait for.
	if ( ! ( $wp_rewrite instanceof WP_Rewrite ) || ! $wp_rewrite->using_permalinks() ) {
		update_option( 'lailatulqadar_rewrite_version', $want );
		return;
	}
	flush_rewrite_rules( false );
	// The marker is written only once the rules hold the search rule, so a
	// flush that failed is tried again on the next request.
	if ( lailatulqadar_search_rules_ready() ) {
		update_option( 'lailatulqadar_rewrite_version', $want );
	}
}

/**
 * Whether the stored rules contain the search rule for the current base, or
 * pretty search is off and needs none.
 *
 * @return bool
 */
function lailatulqadar_search_rules_ready() {
	if ( ! lailatulqadar_pretty_search_active() ) {
		return true;
	}
	$prefix = lailatulqadar_search_base() . '/(.+)';
	foreach ( array_keys( (array) get_option( 'rewrite_rules', array() ) ) as $rule ) {
		if ( 0 === strpos( (string) $rule, $prefix ) ) {
			return true;
		}
	}
	return false;
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
		$base   = lailatulqadar_search_clean_base( wp_unslash( $_POST['lailatulqadar_search_base'] ) );
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
	echo '<p class="description">' . esc_html__( 'Search results open at /search-base/term/. Lowercase letters, digits, hyphens and underscores; a language prefix or a guide page slug is refused. Also set in Theme Options, Reading and sharing.', 'lailatulqadar' ) . '</p>';
}
