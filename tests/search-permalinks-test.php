<?php
/**
 * Self-test for pretty search URLs (inc/search-permalinks.php): the 301
 * from ?s=, empty searches, pagination, the Malay language hint, the plugin
 * fallback base and the flush once per theme version.
 * Run: php tests/search-permalinks-test.php
 *
 * @package Lailatulqadar
 */

define( 'ABSPATH', __DIR__ );
define( 'LQ_OPTION', 'lailatulqadar_settings' );
$GLOBALS['ver']      = '1.17.0';
$GLOBALS['opt']      = array();
$GLOBALS['set']      = array( 'pretty_search' => '1', 'search_base' => 'search', 'secondary_slug' => 'ms' );
$GLOBALS['qv']       = array();
$GLOBALS['redirect'] = null;
$GLOBALS['flushes']  = 0;
function add_action() {}
function lq_setting( $k ) { return isset( $GLOBALS['set'][ $k ] ) ? $GLOBALS['set'][ $k ] : ''; }
function get_option( $k, $d = false ) { return array_key_exists( $k, $GLOBALS['opt'] ) ? $GLOBALS['opt'][ $k ] : $d; }
function update_option( $k, $v ) { $GLOBALS['opt'][ $k ] = $v; return true; }
function sanitize_title( $s ) { return trim( preg_replace( '/[^a-z0-9]+/', '-', strtolower( (string) $s ) ), '-' ); }
function sanitize_key( $s ) { return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $s ) ); }
function get_query_var( $k ) { return isset( $GLOBALS['qv'][ $k ] ) ? $GLOBALS['qv'][ $k ] : ''; }
function is_search() { return isset( $GLOBALS['qv']['s'] ); }
function is_admin() { return false; }
function esc_url_raw( $u ) { return (string) $u; }
function wp_unslash( $v ) { return $v; }
function wp_parse_url( $u, $c = -1 ) { return parse_url( $u, $c ); }
function home_url( $p = '' ) { return 'https://lq.test' . $p; }
function add_query_arg( $k, $v, $u ) { return $u . ( false === strpos( $u, '?' ) ? '?' : '&' ) . $k . '=' . $v; }
function flush_rewrite_rules( $hard = true ) { $GLOBALS['flushes']++; }
class LQ_Redirected extends Exception {}
function wp_safe_redirect( $url, $status ) { $GLOBALS['redirect'] = array( $url, $status ); throw new LQ_Redirected(); }
class WP_Rewrite { public $search_base = 'search'; public function using_permalinks() { return true; } }
$GLOBALS['wp_rewrite'] = new WP_Rewrite();
define( 'LQ_VERSION', '1.17.0' );
require dirname( __DIR__ ) . '/inc/search-permalinks.php';

$fail = 0;
function lq_check( $label, $ok ) {
	global $fail;
	echo ( $ok ? 'ok   ' : 'FAIL ' ) . $label . "\n";
	if ( ! $ok ) {
		$fail++;
	}
}
function lq_run( $uri, $qv, $get = array() ) {
	$_SERVER['REQUEST_URI'] = $uri;
	$_GET                   = $get;
	$GLOBALS['qv']          = $qv;
	$GLOBALS['redirect']    = null;
	try {
		lailatulqadar_pretty_search_redirect();
	} catch ( LQ_Redirected $e ) {
		unset( $e );
	}
	return $GLOBALS['redirect'];
}

lq_check( 'query-string search gets a 301', array( 'https://lq.test/search/odd%20nights/', 301 ) === lq_run( '/?s=odd+nights', array( 's' => 'odd nights' ) ) );
lq_check( 'empty search is left alone', null === lq_run( '/?s=', array( 's' => '' ) ) );
lq_check( 'blank search is left alone', null === lq_run( '/?s=+', array( 's' => ' ' ) ) );
lq_check( 'pretty URL is not redirected', null === lq_run( '/search/dua/', array( 's' => 'dua' ) ) );
lq_check( 'paged search keeps its page', array( 'https://lq.test/search/dua/page/2/', 301 ) === lq_run( '/?s=dua&paged=2', array( 's' => 'dua', 'paged' => 2 ) ) );
lq_check( 'pretty paged URL is not redirected', null === lq_run( '/search/dua/page/2/', array( 's' => 'dua', 'paged' => 2 ) ) );
lq_check( 'Malay search keeps ?lang=ms', array( 'https://lq.test/search/doa/?lang=ms', 301 ) === lq_run( '/?s=doa&lang=ms', array( 's' => 'doa' ), array( 'lang' => 'ms' ) ) );
lq_check( 'unknown lang value is dropped', array( 'https://lq.test/search/doa/', 301 ) === lq_run( '/?s=doa&lang=xx', array( 's' => 'doa' ), array( 'lang' => 'xx' ) ) );

$GLOBALS['opt']['wpseosearch_base'] = 'find';
lq_check( 'plugin base is the fallback', 'find' === lailatulqadar_search_base() );
$GLOBALS['set']['search_base'] = 'Cari Ini';
lq_check( 'theme base wins and is sanitised', 'cari-ini' === lailatulqadar_search_base() );
$GLOBALS['set']['search_base'] = 'search';
unset( $GLOBALS['opt']['wpseosearch_base'] );

$GLOBALS['set']['pretty_search'] = '0';
lq_check( 'switched off: no redirect', null === lq_run( '/?s=dua', array( 's' => 'dua' ) ) );
$GLOBALS['set']['pretty_search'] = '1';

lailatulqadar_search_maybe_flush();
lailatulqadar_search_maybe_flush();
lq_check( 'one flush for this version', 1 === $GLOBALS['flushes'] );
$GLOBALS['set']['search_base'] = 'cari';
lailatulqadar_search_maybe_flush();
lailatulqadar_search_maybe_flush();
lq_check( 'one more flush after the base changes', 2 === $GLOBALS['flushes'] );

echo $fail ? "\n$fail failed\n" : "\nAll passed\n";
exit( $fail ? 1 : 0 );
