<?php
/**
 * Small hardening the server logs asked for (1.18.7), as in the sister themes.
 *
 * - The REST user list (/wp/v2/users and /wp/v2/users/<id>) is hidden from
 *   visitors who are not logged in. It names every account with posts and
 *   hands out a Gravatar hash of its email. Logged-in editors keep it: the
 *   block editor's author panel reads it. ?author=N for a visitor is a 404,
 *   where WordPress would redirect to /author/<username>/ and so name the
 *   account.
 * - Scanner probes are answered at once. The logs show thousands of
 *   requests a month for PHP files the site never had (admin.php, info.php,
 *   1.php, wp_filemanager.php and the like) and for .env and .git files.
 *   Each one rendered the full 404 page, about 41 KB and every database
 *   query of a page view. Now a short 404 goes out on init, before the
 *   query, the template and the 404 logging. WordPress's own entry points
 *   are never touched, and neither is a real file, such as a plugin's own
 *   PHP endpoint that loads WordPress itself.
 *
 * @package Lailatulqadar
 */

defined( 'ABSPATH' ) || exit;

/**
 * Drops the user routes for a visitor who is not logged in.
 *
 * @param array $endpoints REST routes.
 * @return array
 */
function lq_hide_rest_users( $endpoints ) {
	if ( is_user_logged_in() ) {
		return $endpoints;
	}
	foreach ( array_keys( (array) $endpoints ) as $route ) {
		if ( preg_match( '#^/wp/v2/users(/|$)#', (string) $route ) ) {
			unset( $endpoints[ $route ] );
		}
	}
	return $endpoints;
}
add_filter( 'rest_endpoints', 'lq_hide_rest_users', 99 );

/**
 * ?author=N for a visitor who is not logged in: a 404.
 *
 * @param array $query_vars Parsed query variables.
 * @return array
 */
function lq_block_author_query( $query_vars ) {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only check of the raw query.
	if ( is_admin() || ! isset( $_GET['author'] ) || is_user_logged_in() ) {
		return $query_vars;
	}
	unset( $query_vars['author'], $query_vars['author_name'] );
	$query_vars['error'] = '404';
	return $query_vars;
}
add_filter( 'request', 'lq_block_author_query', 99 );

/**
 * Whether a request path (below the home address) is a scanner probe: a
 * PHP file that is not one of WordPress's entry points, or a dot file such
 * as .env or .git/config. .well-known (certificate checks) is left alone,
 * apart from PHP files in it, which no certificate check asks for.
 *
 * @param string $path Request path, without the home address.
 * @return bool
 */
function lq_is_probe( $path ) {
	$path = ltrim( rawurldecode( (string) $path ), '/' );
	if ( '' === $path ) {
		return false;
	}
	if ( preg_match( '#(^|/)\.(?!well-known(/|$))[^/]#i', $path ) ) {
		return true;
	}
	if ( ! preg_match( '#\.php(/|$)#i', $path ) ) {
		return false;
	}
	// WordPress's own entry points, and the admin, exactly as WordPress names
	// them: /WP-LOGIN.PHP is no entry point on a case-sensitive server.
	$own = array( 'index.php', 'wp-login.php', 'wp-cron.php', 'wp-comments-post.php', 'wp-signup.php', 'wp-activate.php', 'wp-trackback.php', 'wp-mail.php', 'wp-links-opml.php', 'xmlrpc.php' );
	if ( in_array( strtok( $path, '/' ), $own, true ) || 0 === strpos( $path, 'wp-admin/' ) ) {
		return false;
	}
	return true;
}

/**
 * Sends the short 404 for a probe and stops.
 */
function lq_short_circuit_probe() {
	if ( is_admin() || wp_doing_ajax() || wp_doing_cron() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) || ( defined( 'WP_CLI' ) && WP_CLI ) || empty( $_SERVER['REQUEST_URI'] ) ) {
		return;
	}
	$path = (string) wp_parse_url( esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ), PHP_URL_PATH );
	$home = (string) wp_parse_url( home_url( '/' ), PHP_URL_PATH );
	if ( '' !== trim( $home, '/' ) && 0 === strpos( $path, $home ) ) {
		$path = substr( $path, strlen( $home ) );
	}
	if ( ! lq_is_probe( $path ) ) {
		return;
	}
	// Only a path with no file behind it, which WordPress's index.php is
	// answering: a plugin's own PHP endpoint that loads wp-load.php is a
	// real file, and runs as before.
	$root = isset( $_SERVER['DOCUMENT_ROOT'] ) ? rtrim( (string) $_SERVER['DOCUMENT_ROOT'], '/\\' ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	$full = (string) wp_parse_url( esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ), PHP_URL_PATH );
	if ( '' !== $root && file_exists( $root . rawurldecode( $full ) ) ) {
		return;
	}
	if ( '' === $root && file_exists( untrailingslashit( ABSPATH ) . '/' . ltrim( rawurldecode( $path ), '/' ) ) ) {
		return;
	}
	status_header( 404 );
	nocache_headers();
	header( 'Content-Type: text/plain; charset=utf-8' );
	header( 'X-Robots-Tag: noindex, nofollow' );
	echo 'Not found.';
	exit;
}
add_action( 'init', 'lq_short_circuit_probe', 0 );
