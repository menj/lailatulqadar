<?php
/**
 * Self-test for the reading-path chapters (lq_path_chapters(), lq_path_groups()).
 * Run: php tests/path-chapters-test.php
 *
 * @package Lailatulqadar
 */

define( 'ABSPATH', __DIR__ . '/' );
define( 'LQ_DIR', dirname( __DIR__ ) );
function add_shortcode() {}
require dirname( __DIR__ ) . '/inc/helpers.php';

$fail = 0;
function check( $ok, $what ) {
	global $fail;
	echo ( $ok ? 'ok   ' : 'FAIL ' ) . $what . "\n";
	$fail += $ok ? 0 : 1;
}
function items( $keys ) {
	return array_map(
		function ( $key ) {
			return array( 'key' => $key, 'title' => $key );
		},
		$keys
	);
}

// The path is every page in the "path" set of the registry, in its order.
$path = array();
foreach ( lq_page_definitions() as $key => $def ) {
	if ( in_array( 'path', $def['sets'], true ) ) {
		$path[] = $key;
	}
}
$chapter_keys = array_merge( ...array_map( function ( $c ) { return $c['keys']; }, lq_path_chapters() ) );
check( 4 === count( lq_path_chapters() ), 'path-chapters.json has 4 chapters' );
check( $chapter_keys === $path, 'The chapters cover the reading path exactly, in registry order' );

$groups = lq_path_groups( items( $path ), 'primary' );
check( 4 === count( $groups ), 'The full path groups into 4 chapters' );
check( array( 1, 4, 7, 10 ) === array_column( $groups, 'start' ), 'Each chapter starts where the last one ended' );
check( 'What the night is' === $groups[0]['heading'], 'English headings for the primary role' );
check( 'Mengenal Lailatulqadar' === lq_path_groups( items( $path ), 'secondary' )[0]['heading'], 'Malay headings for the secondary role' );

$missing = $path;
unset( $missing[1] );
check( array() === lq_path_groups( items( array_values( $missing ) ), 'primary' ), 'A page missing from the path gives the plain list' );
check( array() === lq_path_groups( items( array_merge( $path, array( 'extra' ) ) ), 'primary' ), 'A page added to the path gives the plain list' );
$swapped = $path;
list( $swapped[0], $swapped[1] ) = array( $swapped[1], $swapped[0] );
check( array() === lq_path_groups( items( $swapped ), 'primary' ), 'A path out of order gives the plain list' );

echo $fail ? "$fail failed\n" : "all passed\n";
exit( $fail ? 1 : 0 );
