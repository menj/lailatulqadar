<?php
/**
 * Build the static error pages in errors/ from inc/error-page-html.php.
 * Run: php bin/build-error-pages.php (add --check to fail when a page is stale).
 *
 * @package lailatulqadar
 */

define( 'ABSPATH', dirname( __DIR__ ) . '/' );
require dirname( __DIR__ ) . '/inc/error-page-html.php';

$err_check = in_array( '--check', $argv, true );
$err_stale = 0;
if ( ! $err_check && ! is_dir( dirname( __DIR__ ) . '/errors' ) ) {
	mkdir( dirname( __DIR__ ) . '/errors' );
}
foreach ( array( '403', '500', '502', '503', '504' ) as $err_code ) {
	$err_path = dirname( __DIR__ ) . '/errors/' . $err_code . '.html';
	$err_html = lailatulqadar_error_page_html( $err_code, 'Lailatulqadar', '/' ) . "\n";
	if ( $err_check ) {
		if ( ! is_readable( $err_path ) || file_get_contents( $err_path ) !== $err_html ) {
			fwrite( STDERR, "stale: errors/$err_code.html\n" );
			++$err_stale;
		}
		continue;
	}
	if ( false === file_put_contents( $err_path, $err_html ) ) {
		fwrite( STDERR, "cannot write errors/$err_code.html\n" );
		exit( 1 );
	}
	echo "wrote errors/$err_code.html\n";
}
exit( $err_stale ? 1 : 0 );
