<?php
/**
 * Lailatulqadar child theme bootstrap.
 *
 * @package Lailatulqadar
 */

defined( 'ABSPATH' ) || exit;

define( 'LQ_VERSION', '1.18.12' );
define( 'LQ_DIR', get_stylesheet_directory() );
define( 'LQ_URI', get_stylesheet_directory_uri() );

// Every file the theme loads. The owner uploads over FTP one file at a time,
// so for a while one can be missing, or there but half written. Every file
// is checked before any loads, and they load inside a try: either case gives
// visitors a 503 "Back shortly" page instead of a fatal error
// (lq_incomplete_theme()); this file itself is parsed before any of it runs,
// so it cannot guard itself. inc/error-pages.php loads everywhere: the
// drop-ins are written on after_switch_theme, which can fire on a front-end
// request, and removed on switch_theme.
$lq_files = array(
	'/inc/helpers.php',
	'/inc/parent.php',
	'/inc/languages.php',
	'/inc/assets.php',
	'/inc/blocks.php',
	'/inc/images.php',
	'/inc/ramadan.php',
	'/inc/night-window.php',
	'/inc/seo.php',
	'/inc/contextual-links.php',
	'/inc/draft-links.php',
	'/inc/schema-content.php',
	'/inc/page-parts.php',
	'/inc/schema.php',
	'/inc/image-sitemap.php',
	'/inc/no-blog.php',
	'/inc/login.php',
	'/inc/security.php',
	'/inc/hardening.php',
	'/inc/setup.php',
	'/inc/slugs.php',
	'/inc/social-icons.php',
	'/inc/reading-progress.php',
	'/inc/unlist.php',
	'/inc/search-permalinks.php',
	'/inc/error-pages.php',
);

/**
 * The theme caught half uploaded: a 503 with Retry-After instead of a fatal
 * error, and one line in the error log per problem and ten minutes, not one
 * per request. The login screen, AJAX, cron and the command line go on, and
 * the admin gets the notice only when a file broke part-way through loading.
 *
 * @param string $what What is wrong, for the log.
 * @param bool   $all  Send the notice to the admin screens too.
 */
function lq_incomplete_theme( $what, $all = false ) {
	$key = 'lq_incomplete_' . md5( $what );
	if ( ! get_transient( $key ) ) {
		set_transient( $key, 1, 10 * MINUTE_IN_SECONDS );
		error_log( 'Lailatulqadar: the theme is incomplete or being uploaded (' . $what . '); visitors see the maintenance notice until it is whole.' ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions
	}
	// The login screen by its path, not a query that happens to hold the word.
	$path  = (string) wp_parse_url( (string) ( $_SERVER['REQUEST_URI'] ?? '' ), PHP_URL_PATH ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	$login = 'wp-login.php' === ( $GLOBALS['pagenow'] ?? '' ) || 'wp-login.php' === basename( $path );
	if ( wp_doing_ajax() || wp_doing_cron() || 'cli' === PHP_SAPI || $login || ( is_admin() && ! $all ) ) {
		return;
	}
	if ( ! headers_sent() ) {
		header( 'HTTP/1.1 503 Service Unavailable', true, 503 );
		header( 'Content-Type: text/html; charset=utf-8' );
		header( 'Retry-After: 300' );
		header( 'Cache-Control: no-store, max-age=0' );
		header( 'X-Robots-Tag: noindex' );
	}
	echo '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><meta name="robots" content="noindex"><title>Back shortly</title></head><body><h1>Back shortly</h1><p>The site is being updated. Please try again in a few minutes.</p><p lang="ms">Laman ini sedang dikemas kini. Sila cuba lagi dalam beberapa minit.</p></body></html>';
	exit;
}

// inc/settings.php loads in the admin only, below, and is checked with the rest.
foreach ( array_merge( $lq_files, array( '/inc/settings.php' ) ) as $lq_file ) {
	if ( ! is_readable( LQ_DIR . $lq_file ) ) {
		lq_incomplete_theme( ltrim( $lq_file, '/' ) . ' is missing' );
		return;
	}
}
try {
	foreach ( $lq_files as $lq_file ) {
		require_once LQ_DIR . $lq_file;
	}
} catch ( Throwable $lq_error ) {
	lq_incomplete_theme( $lq_error->getMessage() . ' in ' . basename( $lq_error->getFile() ), true );
	return;
}
unset( $lq_files, $lq_file );

if ( is_admin() ) {
	try {
		require_once LQ_DIR . '/inc/settings.php';
	} catch ( Throwable $lq_error ) {
		lq_incomplete_theme( $lq_error->getMessage() . ' in ' . basename( $lq_error->getFile() ), true );
		return;
	}
}

/**
 * Load translations, declare theme supports and add the editor styles.
 * Block themes get title-tag and automatic-feed-links from core; the theme
 * declares both itself as well. The retired blog still removes the feed
 * links from the head (inc/no-blog.php).
 */
function lq_theme_setup() {
	load_child_theme_textdomain( 'lailatulqadar', LQ_DIR . '/languages' );
	add_theme_support( 'title-tag' );
	add_theme_support( 'automatic-feed-links' );
	add_editor_style( array( 'css/front.css', 'css/editor.css' ) );
}
add_action( 'after_setup_theme', 'lq_theme_setup' );
