<?php
/**
 * Stub test of unlisted posts (inc/unlist.php): listing queries,
 * adjacent links, sitemaps, robots and the import of the plugin's list.
 *
 * Run: php tests/unlist-test.php
 */

if ( 'cli' !== PHP_SAPI ) {
	exit;
}
define( 'ABSPATH', '/' );
$GLOBALS['opt']   = [];
$GLOBALS['env']   = [ 'admin' => false, 'ajax' => false, 'can' => false, 'singular' => false, 'qid' => 0 ];
$GLOBALS['hooks'] = [];
function add_action( $h, $f ) { $GLOBALS['hooks'][ $h ][] = $f; }
function add_filter( $h, $f ) { $GLOBALS['hooks'][ $h ][] = $f; }
function apply_filters( $h, $v ) { return $v; }
function get_option( $k, $d = false ) { return array_key_exists( $k, $GLOBALS['opt'] ) ? $GLOBALS['opt'][ $k ] : $d; }
function update_option( $k, $v ) { $GLOBALS['opt'][ $k ] = $v; return true; }
function is_admin() { return $GLOBALS['env']['admin']; }
function wp_doing_ajax() { return $GLOBALS['env']['ajax']; }
function current_user_can( $c ) { return $GLOBALS['env']['can']; }
function wp_get_referer() { return 'https://ce.test/wp-admin/edit.php'; }
function admin_url() { return 'https://ce.test/wp-admin/'; }
function wp_parse_url( $u ) { return parse_url( $u ); }
function trailingslashit( $s ) { return rtrim( $s, '/' ) . '/'; }
function is_singular() { return $GLOBALS['env']['singular']; }
function get_queried_object_id() { return $GLOBALS['env']['qid']; }
$GLOBALS['meta'] = [];
function update_post_meta( $id, $k, $v ) { $GLOBALS['meta'][ $id ][ $k ] = $v; }
function delete_post_meta( $id, $k ) { unset( $GLOBALS['meta'][ $id ][ $k ] ); }
function wp_json_encode( $v ) { return json_encode( $v ); }
class WPDB_Stub {
	public $postmeta = 'wp_postmeta';
	public function prepare( $q, ...$a ) { return $q; }
	public function get_col( $q ) {
		$out = [];
		foreach ( $GLOBALS['meta'] as $id => $m ) { if ( ( $m['_lq_unlisted'] ?? '' ) === '1' ) $out[] = $id; }
		return $out;
	}
}
$GLOBALS['wpdb'] = new WPDB_Stub();
class WP_Query {
	public $vars = []; public $singular = false;
	public function get( $k ) { return $this->vars[ $k ] ?? ''; }
	public function set( $k, $v ) { $this->vars[ $k ] = $v; }
	public function is_singular() { return $this->singular; }
}
require dirname( __DIR__ ) . '/inc/unlist.php';

function lailatulqadar_unlist_sync_legacy_reset() { unset( $GLOBALS['lailatulqadar_unlist_synced'] ); }
$fail = 0;
function check( $label, $ok ) { global $fail; echo ( $ok ? 'ok   ' : 'FAIL ' ) . $label . "\n"; if ( ! $ok ) $fail++; }

// First import adds the plugin's list to what the theme holds.
$GLOBALS['opt']['unlist_posts'] = [ '12', 7, 0, 'x', 12 ];
check( 'plugin list imported lazily on first read, cleaned', [ 7, 12 ] === lailatulqadar_unlisted_ids() );
check( 'flags stored per post', '1' === $GLOBALS['meta'][12]['_lq_unlisted'] && '1' === $GLOBALS['meta'][7]['_lq_unlisted'] );

lailatulqadar_set_unlisted( 30, true );
lailatulqadar_set_unlisted( 7, false );
check( 'set and clear', [ 12, 30 ] === lailatulqadar_unlisted_ids() );
check( 'plugin option kept in sync', [ 12, 30 ] === $GLOBALS['opt']['unlist_posts'] );

// A stale cache cannot drop another editor's change: the list is rebuilt from the flags.
$GLOBALS['opt']['lailatulqadar_unlisted_posts'] = [ 12 ];
lailatulqadar_set_unlisted( 40, true );
check( 'rebuild from flags keeps every change', [ 12, 30, 40 ] === lailatulqadar_unlisted_ids() );

// The plugin changes its list later: the theme mirrors it on the next request.
$GLOBALS['opt']['unlist_posts'] = [ 30, 55 ];
lailatulqadar_unlist_sync_legacy_reset();
check( 'plugin list change re-synced exactly', [ 30, 55 ] === lailatulqadar_unlisted_ids() );
lailatulqadar_set_unlisted( 40, false );
lailatulqadar_set_unlisted( 55, false );
lailatulqadar_set_unlisted( 12, true );

// Listing query on the front end.
$q = new WP_Query(); $q->set( 'post__not_in', [ 5 ] );
lailatulqadar_unlist_pre_get_posts( $q );
check( 'search, feed and list query excludes unlisted', [ 5, 12, 30 ] === $q->get( 'post__not_in' ) );

$q = new WP_Query(); $q->singular = true;
lailatulqadar_unlist_pre_get_posts( $q );
check( 'singular view stays reachable', '' === $q->get( 'post__not_in' ) );

$GLOBALS['env']['admin'] = true;
$q = new WP_Query(); lailatulqadar_unlist_pre_get_posts( $q );
check( 'admin screens see everything', '' === $q->get( 'post__not_in' ) );

$GLOBALS['env']['ajax'] = true;
$q = new WP_Query(); lailatulqadar_unlist_pre_get_posts( $q );
check( 'front-end AJAX without edit rights is filtered', [ 12, 30 ] === $q->get( 'post__not_in' ) );
$GLOBALS['env']['can'] = true;
$q = new WP_Query(); lailatulqadar_unlist_pre_get_posts( $q );
check( 'AJAX from an admin page by an editor is not filtered', '' === $q->get( 'post__not_in' ) );
$GLOBALS['env'] = [ 'admin' => false, 'ajax' => false, 'can' => false, 'singular' => false, 'qid' => 0 ];

define( 'REST_REQUEST', true );
$q = new WP_Query(); lailatulqadar_unlist_pre_get_posts( $q );
check( 'REST listing for visitors excludes unlisted', [ 12, 30 ] === $q->get( 'post__not_in' ) );
$GLOBALS['env']['can'] = true;
$q = new WP_Query(); lailatulqadar_unlist_pre_get_posts( $q );
check( 'REST listing for editors is not filtered', '' === $q->get( 'post__not_in' ) );
$GLOBALS['env']['can'] = false;

check( 'adjacent links', "WHERE x AND p.ID NOT IN (12,30)" === lailatulqadar_unlist_adjacent_where( 'WHERE x' ) );
check( 'core sitemap', [ 1, 12, 30 ] === lailatulqadar_unlist_sitemap_args( [ 'post__not_in' => [ 1 ] ] )['post__not_in'] );
check( 'Rank Math sitemap drops unlisted', false === lailatulqadar_unlist_rankmath_sitemap_entry( [ 'loc' => 'u' ], 'post', (object) [ 'ID' => 12 ] ) );
check( 'Rank Math sitemap keeps listed', [ 'loc' => 'u' ] === lailatulqadar_unlist_rankmath_sitemap_entry( [ 'loc' => 'u' ], 'post', (object) [ 'ID' => 2 ] ) );
check( 'wp_list_pages excludes', [ 3, 12, 30 ] === lailatulqadar_unlist_list_pages_excludes( [ 3 ] ) );

$GLOBALS['env']['singular'] = true; $GLOBALS['env']['qid'] = 12;
$r = lailatulqadar_unlist_wp_robots( [ 'max-image-preview' => 'large' ] );
check( 'wp_robots noindex', ! empty( $r['noindex'] ) && ! isset( $r['max-image-preview'] ) );
check( 'Rank Math robots noindex', 'noindex' === lailatulqadar_unlist_rankmath_robots( [ 'index' => 'index' ] )['index'] );
$GLOBALS['env']['qid'] = 2;
check( 'listed post keeps its robots', [ 'index' => 'index' ] === lailatulqadar_unlist_rankmath_robots( [ 'index' => 'index' ] ) );

// Plugin active: the theme stands down on listings, but keeps sitemap exclusions with the plugin's IDs.
eval( 'class Unlist_Posts {}' );
$GLOBALS['opt']['unlist_posts'] = [ 66 ];
lailatulqadar_unlist_sync_legacy_reset();
$q = new WP_Query(); lailatulqadar_unlist_pre_get_posts( $q );
check( 'plugin active: no double filtering of listings', '' === $q->get( 'post__not_in' ) );
check( 'plugin active: core sitemap uses the plugin list', [ 66 ] === lailatulqadar_unlist_sitemap_args( [] )['post__not_in'] );
check( 'plugin active: Rank Math sitemap uses the plugin list', false === lailatulqadar_unlist_rankmath_sitemap_entry( [ 'loc' => 'u' ], 'post', (object) [ 'ID' => 66 ] ) );

echo $fail ? "\n$fail failed\n" : "\nAll passed\n";
exit( $fail ? 1 : 0 );
