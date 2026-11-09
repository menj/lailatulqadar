<?php
/**
 * Stub test of superseded pages, renames, guards, the log and the dry-run rules (inc/supersede.php).
 *
 * Run: php tests/supersede-test.php
 *
 * @package Lailatulqadar
 */

if ( 'cli' !== PHP_SAPI ) {
	exit;
}
define( 'ABSPATH', '/' );
$dir = sys_get_temp_dir() . '/lq-supersede-' . uniqid();
mkdir( $dir . '/inc/data', 0777, true );
define( 'LQ_DIR', $dir );
$GLOBALS['opt']    = array();
$GLOBALS['posts']  = array();
$GLOBALS['meta']   = array();
$GLOBALS['map']    = array();
$GLOBALS['edited'] = array();
function add_action() {}
function add_filter() {}
function __( $s ) { return $s; }
function sanitize_key( $s ) { return preg_replace( '/[^a-z0-9_-]/', '', strtolower( (string) $s ) ); }
function sanitize_title( $s ) { return preg_replace( '/[^a-z0-9_-]+/', '-', strtolower( trim( (string) $s ) ) ); }
function get_option( $k, $d = false ) { return array_key_exists( $k, $GLOBALS['opt'] ) ? $GLOBALS['opt'][ $k ] : $d; }
function update_option( $k, $v ) { $GLOBALS['opt'][ $k ] = $v; return true; }
function get_post( $id ) { return $GLOBALS['posts'][ $id ] ?? null; }
function get_post_status( $id ) { return $GLOBALS['posts'][ $id ]->post_status ?? false; }
function get_post_meta( $id, $k ) { return $GLOBALS['meta'][ $id ][ $k ] ?? ''; }
function lq_page_map() { return $GLOBALS['map']; }
function lq_page_definitions() { return $GLOBALS['defs']; }
function lq_page_is_unedited( $post, $key, $role ) { return empty( $GLOBALS['edited'][ $post->ID ] ); }
function put_post( $id, $name, $status = 'publish', $lang = 'en' ) {
	$GLOBALS['posts'][ $id ] = (object) array( 'ID' => $id, 'post_name' => $name, 'post_status' => $status, 'post_title' => $name );
	$GLOBALS['meta'][ $id ]['_lq_lang'] = $lang;
}
require dirname( __DIR__ ) . '/inc/supersede.php';

$checks = 0;
function check( $ok, $message ) {
	$GLOBALS['checks']++;
	if ( ! $ok ) {
		fwrite( STDERR, 'FAIL: ' . $message . "\n" );
		exit( 1 );
	}
}

// The map is read and cleaned.
file_put_contents( $dir . '/inc/data/supersedes.json', json_encode( array( 'Old' => 'new', 'same' => 'same', '' => 'x', 'a b' => 'c' ) ) );
check( array( 'old' => 'new', 'ab' => 'c' ) === lq_supersedes_map(), 'The map is cleaned: lower case keys, no self replacement, no empty key.' );

// The guard.
check( '' === lq_removal_guard( false, 90, 100 ) && '' === lq_removal_guard( false, 5, 0 ), 'Ten percent fewer pages passes, and no baseline passes.' );
check( '' !== lq_removal_guard( false, 89, 100 ) && '' !== lq_removal_guard( true, 100, 100 ), 'More than ten percent fewer, or the kill switch, stops removals.' );
check( '' !== lq_removal_guard( false, 9, 11 ) && '' === lq_removal_guard( false, 10, 11 ), 'The threshold is not rounded down.' );

// Pairs: both published, old unedited, both languages.
$GLOBALS['map'] = array( 'primary' => array( 'old' => 1, 'new' => 2 ), 'secondary' => array( 'old' => 3, 'new' => 4 ) );
put_post( 1, 'old' ); put_post( 2, 'new' ); put_post( 3, 'lama', 'publish', 'ms' ); put_post( 4, 'baru', 'publish', 'ms' );
check( array( 1 => 2, 3 => 4 ) === lq_superseded_pairs( true ), 'A pair holds in each language when both pages are published and the old one is unedited.' );
check( array( 1, 3 ) === lq_superseded_ids(), 'The superseded IDs are the old pages.' );
$GLOBALS['edited'][1] = true;
check( array( 3 => 4 ) === lq_superseded_pairs( true ), 'An edited old page is not superseded.' );
$GLOBALS['edited'] = array(); $GLOBALS['meta'][3]['_lq_lang'] = '';
check( array( 1 => 2 ) === lq_superseded_pairs( true ), 'A page without the theme marker counts as the owner\'s.' );
$GLOBALS['meta'][3]['_lq_lang'] = 'ms'; $GLOBALS['posts'][4]->post_status = 'draft';
check( array( 1 => 2 ) === lq_superseded_pairs( true ), 'A draft replacement does not supersede anything yet.' );
check( true === lq_is_retired_key( 'old', 'primary' ) && false === lq_is_retired_key( 'old', 'secondary' ) && false === lq_is_retired_key( 'new', 'primary' ), 'A key is retired only where its replacement is published.' );
$GLOBALS['posts'][4]->post_status = 'publish';

// Renames in place.
$def = array( 'slug' => 'new-slug', 'title' => 'T', 'former' => array( 'old-slug' ) );
put_post( 10, 'old-slug' );
check( true === lq_can_rename_published( get_post( 10 ), 'k', 'primary', $def ), 'An unedited published page under a former slug may be renamed.' );
$GLOBALS['edited'][10] = true;
check( false === lq_can_rename_published( get_post( 10 ), 'k', 'primary', $def ), 'An edited page keeps its address.' );
$GLOBALS['edited'] = array(); put_post( 11, 'something-the-owner-chose' );
check( false === lq_can_rename_published( get_post( 11 ), 'k', 'primary', $def ), 'A slug the definition does not list is never renamed.' );
put_post( 12, 'old-slug', 'draft' );
check( false === lq_can_rename_published( get_post( 12 ), 'k', 'primary', $def ), 'Only published pages take this path.' );
put_post( 13, 'new-slug' );
check( false === lq_can_rename_published( get_post( 13 ), 'k', 'primary', $def ), 'A page already at the new slug is left alone.' );

// Old addresses.
lq_note_redirect( '/ms/lama/', 4 ); lq_note_redirect( 'old-slug', 10 );
check( array( 'ms/lama' => 4, 'old-slug' => 10 ) === $GLOBALS['opt'][ LQ_REDIRECTS_OPTION ], 'Old addresses are recorded without their slashes.' );

// The log keeps the last 100 lines.
for ( $i = 0; $i < 130; $i++ ) { lq_log( 'p' . $i, 'test' ); }
$log = $GLOBALS['opt'][ LQ_LOG_OPTION ];
check( 100 === count( $log ) && 'p129' === end( $log )['item'] && 'p30' === $log[0]['item'], 'The log keeps the last 100 lines.' );

// What the dry run says.
$base = array( 'defined' => true, 'exists' => true, 'trashed' => false, 'edited' => false, 'differs' => false, 'renamable' => false, 'retired' => false, 'blocked' => false );
check( 'current' === lq_classify( $base ), 'Matches the theme.' );
check( 'refresh' === lq_classify( array_merge( $base, array( 'differs' => true ) ) ), 'An unedited page with new shipped text refreshes.' );
check( 'kept' === lq_classify( array_merge( $base, array( 'differs' => true, 'edited' => true ) ) ), 'An edited page with new shipped text is kept.' );
check( 'current' === lq_classify( array_merge( $base, array( 'edited' => true ) ) ), 'An edited page the theme did not change is current.' );
check( 'create' === lq_classify( array_merge( $base, array( 'exists' => false ) ) ), 'A missing page is created.' );
check( 'create' === lq_classify( array_merge( $base, array( 'trashed' => true ) ) ), 'A page in the Trash is created again, as setup does.' );
check( 'rename' === lq_classify( array_merge( $base, array( 'renamable' => true ) ) ), 'A renamable page is renamed.' );
check( 'supersede' === lq_classify( array_merge( $base, array( 'retired' => true ) ) ), 'A retired page is superseded.' );
check( 'supersede-kept' === lq_classify( array_merge( $base, array( 'retired' => true, 'edited' => true ) ) ), 'An edited retired page stays.' );
check( 'held' === lq_classify( array_merge( $base, array( 'retired' => true, 'blocked' => true ) ) ), 'A guard holds the removal.' );
check( 'current' === lq_classify( array_merge( $base, array( 'retired' => true, 'trashed' => true ) ) ), 'A retired page in the Trash is not created again.' );
check( 'dropped' === lq_classify( array_merge( $base, array( 'defined' => false ) ) ), 'A dropped unedited page goes to the Trash.' );
check( 'dropped-kept' === lq_classify( array_merge( $base, array( 'defined' => false, 'edited' => true ) ) ), 'A dropped edited page stays.' );
check( 'held' === lq_classify( array_merge( $base, array( 'defined' => false, 'blocked' => true ) ) ), 'A guard holds a dropped page.' );
check( 'current' === lq_classify( array_merge( $base, array( 'defined' => false, 'trashed' => true ) ) ), 'A dropped page already in the Trash needs nothing.' );

define( 'LQ_DISABLE_SUPERSEDE', true );
check( lq_supersede_disabled() && array() === lq_superseded_pairs( true ) && false === lq_can_rename_published( get_post( 10 ), 'k', 'primary', $def ) && false === lq_is_retired_key( 'old', 'primary' ), 'The kill switch ends pairs, renames and retirement.' );

array_map( 'unlink', glob( $dir . '/inc/data/*' ) );
rmdir( $dir . '/inc/data' ); rmdir( $dir . '/inc' ); rmdir( $dir );
echo $checks . " superseded-page checks passed.\n";
