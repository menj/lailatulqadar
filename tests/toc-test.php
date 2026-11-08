<?php
/**
 * Self-test for the WP TOC glue (inc/toc.php) and the footnote reference
 * ids (inc/footnotes.php). WP TOC itself has its own suite in its repo.
 * Run: php tests/toc-test.php
 *
 * @package Lailatulqadar
 */

if ( 'cli' !== PHP_SAPI ) {
	exit;
}

define( 'ABSPATH', __DIR__ . '/' );
define( 'LQ_URI', '' );
define( 'LQ_VERSION', '0' );
$GLOBALS['filters'] = array();
function add_filter( $hook, $cb ) {
	$GLOBALS['filters'][ $hook ][] = $cb;
}
function add_action() {}
function apply_filters( $hook, $value ) {
	return $value;
}
function esc_attr( $s ) {
	return htmlspecialchars( (string) $s, ENT_QUOTES, 'UTF-8' );
}
function esc_attr__( $s ) {
	return esc_attr( $s );
}
function esc_html( $s ) {
	return htmlspecialchars( (string) $s, ENT_QUOTES, 'UTF-8' );
}
function esc_html__( $s ) {
	return esc_html( $s );
}
function lq_seo_plugin() {
	return $GLOBALS['seo'] ?? '';
}
$GLOBALS['shortcodes'] = array();
function shortcode_exists( $tag ) {
	return isset( $GLOBALS['shortcodes'][ $tag ] );
}
function __return_empty_string() {
	return '';
}
function add_shortcode( $tag, $cb ) {
	$GLOBALS['shortcodes'][ $tag ] = $cb;
}
function get_the_ID() {
	return $GLOBALS['post_id'] ?? 0;
}
function lq_role_of( $id ) {
	return 2 === $id ? 'secondary' : 'primary';
}
function lailatulqadar_t( $en, $ms, $role = null ) {
	return 'secondary' === $role ? $ms : $en;
}
function lailatulqadar_is_guide_page( $id ) {
	return in_array( (int) $id, array( 1, 2 ), true );
}
require dirname( __DIR__ ) . '/inc/toc.php';
require dirname( __DIR__ ) . '/inc/footnotes.php';

$fail = 0;
function check( $ok, $what ) {
	global $fail;
	echo ( $ok ? 'ok   ' : 'FAIL ' ) . $what . "\n";
	$fail += $ok ? 0 : 1;
}

// Automatic table of contents: guide pages only, H2, at least three.
$on  = lailatulqadar_toc_auto_insert( array( 'enabled' => true, 'post_types' => array( 'post', 'page' ), 'atts' => array( 'min' => '3' ) ), 1 );
$off = lailatulqadar_toc_auto_insert( array( 'enabled' => true ), 9 );
check( true === $on['enabled'] && false === $off['enabled'], 'automatic list on guide pages only' );
check( array( 'page' ) === $on['post_types'] && 'before_first_heading' === $on['position'], 'pages only, before the first heading' );
check( 'h2' === $on['atts']['tags'] && '3' === $on['atts']['min'], 'H2 headings, three at least' );
check( false === lailatulqadar_toc_auto_insert( array(), 0 )['enabled'], 'no post, no list' );

// Title by language.
$GLOBALS['post_id'] = 1;
check( 'On this page' === lailatulqadar_toc_default_atts( array( 'title' => 'Table of Contents' ) )['title'], 'English title' );
$GLOBALS['post_id'] = 2;
check( 'Kandungan halaman' === lailatulqadar_toc_default_atts( array() )['title'], 'Malay title' );
$named = lailatulqadar_toc_name( '<nav aria-label="Table of Contents"><meta itemprop="name" content="Table of Contents"></nav>' );
check( 2 === substr_count( $named, 'Kandungan halaman' ) && false === strpos( $named, 'Table of Contents' ), 'untitled list named in the page language' );

// JSON-LD: none of its own; the sections join the theme graph on the same WebPage @id.
$ld = array(
	'@type'   => 'WebPage',
	'@id'     => 'https://x.test/p/#webpage',
	'hasPart' => array( array( '@type' => 'WebPageElement', '@id' => 'https://x.test/p/#a', 'name' => 'A' ) ),
);
check( array() === lailatulqadar_toc_schema( $ld ), 'WP TOC prints no JSON-LD of its own' );
$graph = lailatulqadar_toc_graph(
	array(
		array( '@type' => 'WebSite', '@id' => 'https://x.test/#website' ),
		array( '@type' => 'WebPage', '@id' => 'https://x.test/p/#webpage', 'hasPart' => array( array( '@id' => 'https://x.test/p/#table-1' ) ) ),
	)
);
check( 2 === count( $graph[1]['hasPart'] ) && 'https://x.test/p/#a' === $graph[1]['hasPart'][1]['@id'], 'sections added to the WebPage node, existing parts kept' );
check( ! isset( $graph[0]['hasPart'] ), 'other nodes untouched' );
$single = lailatulqadar_toc_graph( array( array( '@id' => 'https://x.test/p/#webpage', 'hasPart' => array( '@id' => 'https://x.test/p/#only' ) ) ) );
check( 2 === count( $single[0]['hasPart'] ) && 'https://x.test/p/#only' === $single[0]['hasPart'][0]['@id'], 'a single hasPart node becomes a list' );
check( isset( $GLOBALS['filters']['wp_toc_load_css'] ), 'WP TOC stylesheet replaced by the theme one' );

// Rank Math with a custom canonical: WP TOC's @id is the canonical's, the theme node is found by its url.
$canon = array( '@type' => 'WebPage', '@id' => 'https://x.test/canon/#webpage', 'hasPart' => array( array( '@type' => 'WebPageElement', '@id' => 'https://x.test/q/#b' ) ) );
lailatulqadar_toc_schema( $canon );
$byurl = lailatulqadar_toc_graph( array( array( '@type' => 'WebPage', '@id' => 'https://x.test/q/#webpage', 'url' => 'https://x.test/q/' ) ) );
check( isset( $byurl[0]['hasPart'][0]['@id'] ) && 'https://x.test/q/#b' === $byurl[0]['hasPart'][0]['@id'], 'sections found a page by url when the @id differs' );
$none = lailatulqadar_toc_graph( array( array( '@type' => 'WebPage', '@id' => 'https://x.test/other/#webpage', 'url' => 'https://x.test/other/' ) ) );
check( ! isset( $none[0]['hasPart'] ), 'no matching page, nothing added' );

// With another SEO plugin the theme prints no graph, so WP TOC keeps its JSON-LD.
$GLOBALS['seo'] = 'Yoast SEO';
check( $ld === lailatulqadar_toc_schema( $ld ), 'Yoast: WP TOC keeps its own JSON-LD' );
$GLOBALS['seo'] = 'Rank Math';
check( array() === lailatulqadar_toc_schema( $ld ), 'Rank Math: sections go to the merged graph' );
$GLOBALS['seo'] = '';

// The visible summary of an untitled collapsible list, in Malay.
$GLOBALS['post_id'] = 2;
$sum = lailatulqadar_toc_name( '<summary class="wp-toc-summary"><span class="wp-toc-title auto-toc-title">Table of Contents</span></summary>' );
check( false !== strpos( $sum, '>Kandungan halaman</span>' ), 'collapsible summary in the page language' );

// Below PHP 8.4 (no WP_TOC class): both shortcodes exist and print nothing.
lailatulqadar_toc_fallback_shortcodes();
check( isset( $GLOBALS['shortcodes']['wp_toc'], $GLOBALS['shortcodes']['auto_toc'] ) && '' === call_user_func( $GLOBALS['shortcodes']['wp_toc'] ), 'fallback shortcodes print nothing' );

// Footnote references cited more than once get unique ids.
$fn  = '<p>a<sup data-fn="u1" class="fn"><a href="#u1" id="u1-link">1</a></sup> b<sup data-fn="u1" class="fn"><a href="#u1" id="u1-link">1</a></sup> c<sup data-fn="u1" class="fn"><a href="#u1" id="u1-link">1</a></sup><sup data-fn="u2" class="fn"><a href="#u2" id="u2-link">2</a></sup></p>';
$fnd = lailatulqadar_footnote_ref_ids( $fn );
check( 1 === substr_count( $fnd, 'id="u1-link"' ) && false !== strpos( $fnd, 'id="u1-link-2"' ) && false !== strpos( $fnd, 'id="u1-link-3"' ), 'repeated footnote references get unique ids' );
check( 1 === substr_count( $fnd, 'id="u2-link"' ) && 4 === substr_count( $fnd, 'href="#u' ), 'single references and the links stay as they are' );
check( '<p>x</p>' === lailatulqadar_footnote_ref_ids( '<p>x</p>' ), 'text without footnotes untouched' );

echo $fail ? "$fail failed\n" : "All checks passed\n";
exit( $fail ? 1 : 0 );
