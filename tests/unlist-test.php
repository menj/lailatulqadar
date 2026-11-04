<?php
/**
 * Stub test of unlisted posts (inc/unlist.php): listing queries,
 * adjacent links, sitemaps, robots and the import of the plugin's list.
 *
 * Run: php tests/unlist-test.php
 */
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
class WP_Query {
	public $vars = []; public $singular = false;
	public function get( $k ) { return $this->vars[ $k ] ?? ''; }
	public function set( $k, $v ) { $this->vars[ $k ] = $v; }
	public function is_singular() { return $this->singular; }
}
require dirname( __DIR__ ) . '/inc/unlist.php';

$fail = 0;
function check( $label, $ok ) { global $fail; echo ( $ok ? 'ok   ' : 'FAIL ' ) . $label . "\n"; if ( ! $ok ) $fail++; }

// Import from the plugin's option, once.
$GLOBALS['opt']['unlist_posts'] = [ '12', 7, 0, 'x', 12 ];
lailatulqadar_unlist_import_plugin_list();
check( 'plugin list imported and cleaned', [ 12, 7 ] === lailatulqadar_unlisted_ids() );
$GLOBALS['opt']['unlist_posts'] = [ 99 ];
lailatulqadar_unlist_import_plugin_list();
check( 'import runs once', [ 12, 7 ] === lailatulqadar_unlisted_ids() );

lailatulqadar_set_unlisted( 30, true );
lailatulqadar_set_unlisted( 7, false );
check( 'set and clear', [ 12, 30 ] === lailatulqadar_unlisted_ids() );

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

echo $fail ? "\n$fail failed\n" : "\nAll passed\n";
exit( $fail ? 1 : 0 );
