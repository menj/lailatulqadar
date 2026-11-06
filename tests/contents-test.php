<?php
/**
 * Self-test for the placed contents list (inc/contents.php): levels, ids,
 * nesting and the [auto_toc] marker.
 * Run: php tests/contents-test.php
 *
 * @package Lailatulqadar
 */

if ( 'cli' !== PHP_SAPI ) {
	exit;
}

define( 'ABSPATH', __DIR__ . '/' );
define( 'LQ_URI', '' );
define( 'LQ_VERSION', '0' );
function add_action() {}
function add_filter() {}
function add_shortcode() {}
function esc_attr( $s ) {
	return htmlspecialchars( (string) $s, ENT_QUOTES, 'UTF-8' );
}
function esc_html( $s ) {
	return htmlspecialchars( (string) $s, ENT_QUOTES, 'UTF-8' );
}
function wp_strip_all_tags( $s ) {
	return strip_tags( (string) $s );
}
function remove_accents( $s ) {
	return strtr( (string) $s, array( 'ā' => 'a', 'ū' => 'u', 'ī' => 'i', 'ʿ' => '', 'ʾ' => '' ) );
}
function sanitize_title( $s ) {
	return trim( (string) preg_replace( '/[^a-z0-9]+/', '-', strtolower( (string) $s ) ), '-' );
}
function wp_json_encode( $d ) {
	return json_encode( $d );
}
function lailatulqadar_t( $en, $ms, $role = null ) {
	return 'secondary' === $role ? $ms : $en;
}
function is_feed() {
	return false;
}
function doing_filter() {
	return false;
}
function get_the_ID() {
	return 0;
}
require dirname( __DIR__ ) . '/inc/contents.php';

$fail = 0;
function check( $ok, $what ) {
	global $fail;
	echo ( $ok ? 'ok   ' : 'FAIL ' ) . $what . "\n";
	$fail += $ok ? 0 : 1;
}
function balanced( $html ) {
	return substr_count( $html, '<ol' ) === substr_count( $html, '</ol>' ) && substr_count( $html, '<li>' ) === substr_count( $html, '</li>' );
}

check( array( 2, 3 ) === lailatulqadar_toc_levels( 'h3, H2' ), 'levels sort and ignore case' );
check( array( 2, 3 ) === lailatulqadar_toc_levels( 'bogus,h9' ), 'invalid levels fall back to h2,h3' );
check( array( 1, 4 ) === lailatulqadar_toc_levels( array( '4', 'h1', 'h4' ) ), 'levels from an array, deduplicated' );

$text = '<h2>Night of Decree</h2><p>x</p><h3 id="own">Signs<sup class="fn">1</sup></h3><h2>Night of Decree</h2><h4>Deep</h4><header>no</header><h2 class="a">Laylat al-Qadr</h2>';
$ids  = lailatulqadar_toc_add_ids( $text, array( 2, 3, 4 ) );
check( false !== strpos( $ids, '<h2 id="night-of-decree">' ), 'id added from the text' );
check( false !== strpos( $ids, '<h2 id="night-of-decree-2">' ), 'duplicate heading numbered' );
check( false !== strpos( $ids, '<h3 id="own">' ) && 1 === substr_count( $ids, 'id="own"' ), 'existing id kept' );
check( false !== strpos( $ids, '<h2 id="laylat-al-qadr" class="a">' ), 'other attributes kept' );
check( false !== strpos( $ids, '<header>no</header>' ), '<header> left alone' );
$taken = lailatulqadar_toc_add_ids( '<p id="deep">a</p><h4>Deep</h4>', array( 4 ) );
check( false !== strpos( $taken, '<h4 id="deep-2">' ), 'id taken elsewhere on the page is not reused' );
$ar = '<h2>ليلة القدر</h2>';
check( false !== strpos( lailatulqadar_toc_add_ids( $ar, array( 2 ) ), 'ليلة القدر</h2>' ), 'Arabic heading text untouched' );

$items = lailatulqadar_toc_items( $ids, array( 2, 3 ) );
check( 4 === count( $items ), 'h4 left out of an h2,h3 list' );
check( 'Signs' === $items[1]['text'], 'footnote marker left out of the text' );

$list = lailatulqadar_toc_list( $items );
check( '<ol class="lq-contents__list"><li><a href="#night-of-decree">Night of Decree</a><ol><li><a href="#own">Signs</a></li></ol></li><li><a href="#night-of-decree-2">Night of Decree</a></li><li><a href="#laylat-al-qadr">Laylat al-Qadr</a></li></ol>' === $list, 'nested list exact' );

$deep = array();
foreach ( array( 2, 4, 3, 6, 2, 3, 4 ) as $n => $l ) {
	$deep[] = array( 'level' => $l, 'id' => "i$n", 'text' => "t$n" );
}
$out = lailatulqadar_toc_list( $deep );
check( balanced( $out ), 'deep jumps stay balanced' );
check( false !== strpos( $out, '<li><a href="#i1">t1</a></li><li><a href="#i2">t2</a>' ), 'h3 after h4 joins the nested list' );
$end = lailatulqadar_toc_list( array( array( 'level' => 2, 'id' => 'a', 'text' => 'a' ), array( 'level' => 3, 'id' => 'b', 'text' => 'b' ), array( 'level' => 4, 'id' => 'c', 'text' => 'c' ) ) );
check( balanced( $end ) && '</li></ol></li></ol></li></ol>' === substr( $end, -30 ), 'lists open at the end all close' );
check( '' === lailatulqadar_toc_list( array() ), 'no items, no list' );

check( false !== strpos( lailatulqadar_toc_html( $ids, array( 2 ), null, 'secondary' ), '>Kandungan halaman</p>' ), 'Malay default title' );
check( false !== strpos( lailatulqadar_toc_html( $ids, array( 2 ), '', 'primary' ), 'aria-label="On this page"' ), 'no title: nav keeps a name' );
check( false !== strpos( lailatulqadar_toc_html( $ids, array( 2 ), 'In <this> article' ), 'In &lt;this&gt; article' ), 'title escaped' );
check( '' === lailatulqadar_toc_html( '<p>none</p>', array( 2 ) ), 'text with no headings gives no list' );

$mark = lailatulqadar_toc_shortcode( array( 'tags' => 'h2', 'title' => 'x --> y' ) );
check( 1 === substr_count( $mark, '-->' ), 'title cannot end the marker comment' );
$page = "<p>$mark</p><h2>One</h2><h2>Two</h2>";
$done = lailatulqadar_toc_filter( $page );
check( 0 === strpos( $done, '<nav class="lq-contents lq-contents--placed"' ), 'paragraph around the marker removed' );
check( false !== strpos( $done, '>x --&gt; y</p>' ) && false !== strpos( $done, '<a href="#two">Two</a>' ), 'marker replaced with the list' );
check( '<p>plain</p>' === lailatulqadar_toc_filter( '<p>plain</p>' ), 'text without a marker untouched' );
check( false === strpos( lailatulqadar_toc_filter( lailatulqadar_toc_shortcode( '' ) . '<p>x</p>' ), '<!--' ), 'marker removed when no headings' );

echo $fail ? "$fail failed\n" : "All checks passed\n";
exit( $fail ? 1 : 0 );
