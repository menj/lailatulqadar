<?php
/**
 * Self-test for the retired blog (inc/no-blog.php).
 * Run: php tests/no-blog-test.php
 *
 * Checks that the blog templates and the posts-index pattern are gone, that
 * the template filter drops the retired templates, that every retired view
 * answers 301 to its language home, that pages and the 404 are left alone,
 * that posts leave the sitemaps, and that the migration runs once.
 *
 * Exit code 0 when every check passes.
 *
 * @package Lailatulqadar
 */

define( 'ABSPATH', __DIR__ );
$root = dirname( __DIR__ );
$fail = 0;

class LQ_Test_Exit extends Exception {}

$GLOBALS['v']    = array();
$GLOBALS['opts'] = array();
$GLOBALS['meta'] = array();
$GLOBALS['sent'] = null;

function lq_v( $k ) {
	return ! empty( $GLOBALS['v'][ $k ] );
}
function is_feed() { return lq_v( 'feed' ); }
function is_home() { return lq_v( 'home' ); }
function is_front_page() { return lq_v( 'front' ); }
function is_singular( $t = '' ) { return '' === $t ? lq_v( 'single_post' ) || lq_v( 'single_page' ) : lq_v( 'single_' . $t ); }
function is_date() { return lq_v( 'date' ); }
function is_author() { return lq_v( 'author' ); }
function is_category() { return lq_v( 'category' ); }
function is_tag() { return lq_v( 'tag' ); }
function is_post_type_archive( $t ) { return lq_v( 'pta' ); }
function is_admin() { return false; }
function is_404() { return lq_v( '404' ); }
function get_queried_object_id() { return 5; }
function is_page( $id ) { return lq_v( 'page_' . $id ); }
function lq_setting( $k ) { return 'ms'; }
function wp_parse_url( $u, $c ) { return parse_url( $u, $c ); }
function wp_unslash( $v ) { return $v; }
function lq_role_of( $id ) { return empty( $GLOBALS['v']['ms'] ) ? 'primary' : 'secondary'; }
function lq_test_map() { return array( 'primary' => array( 'home' => 1 ), 'secondary' => array( 'home' => 9 ) ); }
function lq_current_role() { return empty( $GLOBALS['v']['ms'] ) ? 'primary' : 'secondary'; }
function lq_page_map() { return lq_test_map(); }
function get_post_status( $id ) { return 'publish'; }
function get_permalink( $id ) { return 'https://lailatulqadar.test/ms/'; }
function home_url( $p ) { return 'https://lailatulqadar.test' . $p; }
function wp_safe_redirect( $url, $code ) {
	$GLOBALS['sent'] = array( $url, $code );
	throw new LQ_Test_Exit();
}
function add_filter() {}
function add_action() {}
function remove_action() {}
function get_option( $k, $d = false ) { return array_key_exists( $k, $GLOBALS['opts'] ) ? $GLOBALS['opts'][ $k ] : $d; }
function update_option( $k, $v ) { $GLOBALS['opts'][ $k ] = $v; }
function get_posts( $q ) { return array_keys( array_filter( $GLOBALS['meta'], static function ( $t ) use ( $q ) { return in_array( $t, $q['meta_value'], true ); } ) ); }
function delete_post_meta( $id ) { unset( $GLOBALS['meta'][ $id ] ); }

// The redirect calls exit, which the test cannot survive; load a copy that throws instead.
$src = str_replace( "\texit;\n", "", file_get_contents( $root . '/inc/no-blog.php' ) );
eval( '?>' . $src );

function check( $ok, $msg ) {
	global $fail;
	if ( ! $ok ) {
		++$fail;
		echo "FAIL: $msg\n";
	}
}

foreach ( array( 'templates/home.html', 'templates/archive.html', 'templates/single.html', 'patterns/blog-title.php' ) as $f ) {
	check( ! file_exists( $root . '/' . $f ), "$f still exists" );
}
check( false === strpos( file_get_contents( $root . '/templates/index.html' ), 'blog-title' ), 'index.html still shows the posts index' );
$theme = json_decode( file_get_contents( $root . '/theme.json' ), true );
foreach ( $theme['customTemplates'] as $t ) {
	check( ! in_array( $t['name'], lq_retired_template_slugs(), true ), 'theme.json offers ' . $t['name'] );
}

$list = array();
foreach ( array( 'home', 'archive', 'single', 'date', 'page', 'index', 'search', 'page-home' ) as $s ) {
	$list[] = (object) array( 'slug' => $s );
}
$kept = array_map( static function ( $t ) { return $t->slug; }, lq_drop_retired_templates( $list, array(), 'wp_template' ) );
check( array( 'page', 'index', 'search', 'page-home' ) === $kept, 'template filter kept ' . implode( ',', $kept ) );
check( 8 === count( lq_drop_retired_templates( $list, array(), 'wp_template_part' ) ), 'template parts were filtered' );
check( ! isset( lq_drop_retired_template_types( array( 'home' => 1, 'page' => 1 ) )['home'] ), 'home template type still offered' );

$en = 'https://lailatulqadar.test/';
$ms = 'https://lailatulqadar.test/ms/';
$cases = array(
	'blog home'      => array( array( 'home' => 1 ), $en ),
	'single post'    => array( array( 'single_post' => 1 ), $en ),
	'Malay post'     => array( array( 'single_post' => 1, 'ms' => 1 ), $ms ),
	'date archive'   => array( array( 'date' => 1 ), $en ),
	'author archive' => array( array( 'author' => 1, 'ms' => 1 ), $ms ),
	'category'       => array( array( 'category' => 1 ), $en ),
	'tag'            => array( array( 'tag' => 1 ), $en ),
	'feed'           => array( array( 'feed' => 1 ), $en ),
	'front page'     => array( array( 'home' => 1, 'front' => 1 ), null ),
	'guide page'     => array( array( 'single_page' => 1 ), null ),
	'unmarked post under /ms/' => array( array( 'single_post' => 1, 'uri' => '/ms/catatan-lama/' ), $ms ),
	'unmarked post elsewhere'  => array( array( 'single_post' => 1, 'uri' => '/mse/' ), $en ),
	'former posts page'        => array( array( 'single_page' => 1, 'page_42' => 1, 'fpp' => 42 ), $en ),
	'former Malay posts page'  => array( array( 'single_page' => 1, 'page_42' => 1, 'fpp' => 42, 'ms' => 1 ), $ms ),
	'guide page once posts page' => array( array( 'single_page' => 1, 'page_1' => 1, 'fpp' => 1 ), null ),
	'not found'      => array( array( '404' => 1, 'single_post' => 1 ), null ),
);
foreach ( $cases as $name => $c ) {
	$GLOBALS['v']    = $c[0];
	$GLOBALS['sent'] = null;
	$GLOBALS['opts'] = isset( $c[0]['fpp'] ) ? array( 'lailatulqadar_former_posts_page' => $c[0]['fpp'] ) : array();
	$_SERVER['REQUEST_URI'] = isset( $c[0]['uri'] ) ? $c[0]['uri'] : '/';
	try {
		lq_redirect_retired_views();
	} catch ( LQ_Test_Exit $e ) {
		unset( $e );
	}
	$want = null === $c[1] ? null : array( $c[1], 301 );
	check( $want === $GLOBALS['sent'], "$name: " . json_encode( $GLOBALS['sent'] ) );
}

check( ! isset( lq_sitemap_drop_posts( array( 'post' => 1, 'page' => 1 ) )['post'] ), 'core sitemap lists posts' );
check( true === lq_rank_math_drop_posts( false, 'post' ) && false === lq_rank_math_drop_posts( false, 'page' ), 'Rank Math post type filter' );
check( true === lq_rank_math_drop_post_terms( false, 'category' ), 'Rank Math category filter' );

$GLOBALS['opts'] = array( 'page_for_posts' => 42, 'show_on_front' => 'posts' );
$GLOBALS['meta'] = array( 42 => 'home', 43 => 'page-no-title', 44 => 'archive' );
check( true === lq_migrate_retire_blog(), 'migration did not run' );
check( 0 === $GLOBALS['opts']['page_for_posts'] && 'page' === $GLOBALS['opts']['show_on_front'], 'posts page still set' );
check( 42 === $GLOBALS['opts']['lailatulqadar_former_posts_page'], 'former posts page not recorded' );
check( array( 43 => 'page-no-title' ) === $GLOBALS['meta'], 'page templates: ' . json_encode( $GLOBALS['meta'] ) );
$GLOBALS['opts']['page_for_posts'] = 7;
check( false === lq_migrate_retire_blog() && 7 === $GLOBALS['opts']['page_for_posts'], 'migration ran twice' );

echo $fail ? "$fail failure(s)\n" : "No-blog test: all checks passed\n";
exit( $fail ? 1 : 0 );
