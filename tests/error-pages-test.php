<?php
/**
 * Stub test of the error pages (inc/error-pages.php, inc/error-page-html.php): each
 * drop-in carries its own page, status and headers and runs on its own; the
 * installer keeps files it did not write and reports installed, outdated and
 * foreign; the static pages in errors/ are current.
 *
 * Run: php tests/error-pages-test.php
 */
define( 'ABSPATH', __DIR__ . '/' );
define( 'LQ_VERSION', '9.9.9' );
// A fresh directory of its own, so the cleanup at the end removes only what this test wrote.
$err_tmp = sys_get_temp_dir() . '/err-test-' . getmypid() . '-' . bin2hex( random_bytes( 6 ) );
if ( ! mkdir( $err_tmp, 0700 ) ) {
	fwrite( STDERR, "cannot create $err_tmp\n" );
	exit( 1 );
}
define( 'WP_CONTENT_DIR', $err_tmp );
$GLOBALS['err_opt'] = array();
function add_action() {}
function __( $s ) { return $s; }
function get_bloginfo() { return 'Lailatulqadar'; }
function wp_specialchars_decode( $s ) { return $s; }
function wp_parse_url( $u, $c = -1 ) { return parse_url( $u, $c ); }
function home_url( $p = '' ) { return 'https://example.test' . $p; }
function untrailingslashit( $s ) { return rtrim( $s, '/' ); }
function get_stylesheet_directory_uri() { return 'https://example.test/wp-content/themes/lailatulqadar'; }
function update_option( $k, $v ) { $GLOBALS['err_opt'][ $k ] = $v; return true; }
function get_option( $k, $d = false ) { return $GLOBALS['err_opt'][ $k ] ?? $d; }
function delete_option( $k ) { unset( $GLOBALS['err_opt'][ $k ] ); }
function wp_delete_file( $p ) { @unlink( $p ); }
function is_multisite() { return false; }
function current_user_can() { return true; }

require dirname( __DIR__ ) . '/inc/error-pages.php';

$err_fail = 0;
function err_check( $ok, $label ) {
	global $err_fail;
	echo ( $ok ? 'ok   ' : 'FAIL ' ) . $label . "\n";
	$err_fail += $ok ? 0 : 1;
}

$err_src = array();
foreach ( lailatulqadar_error_dropins() as $err_name => $err_kind ) {
	$err_src[ $err_name ] = lailatulqadar_error_dropin_source( $err_kind );
	err_check( false !== strpos( $err_src[ $err_name ], LAILATULQADAR_ERR_MARKER . ' (' . $err_kind . ')' ), "$err_name carries the marker and its kind" );
	err_check( false !== strpos( $err_src[ $err_name ], 'X-Robots-Tag: noindex' ) && false !== strpos( $err_src[ $err_name ], '<meta name="robots" content="noindex">' ) && false !== strpos( $err_src[ $err_name ], 'no-store' ), "$err_name sends noindex and no-store" );
	err_check( false === strpos( $err_src[ $err_name ], '<link' ) && false === strpos( $err_src[ $err_name ], '<script' ), "$err_name loads no file" );
	preg_match( '~<title>([^<]*)</title>~', $err_src[ $err_name ], $err_m );
	err_check( isset( $err_m[1] ) && mb_strlen( html_entity_decode( $err_m[1], ENT_QUOTES, 'UTF-8' ) ) < 60 && false !== strpos( $err_m[1], ' | ' ), "$err_name title is <page> | <site>, under 60" );
	$err_file = "$err_tmp/run-$err_name";
	file_put_contents( $err_file, $err_src[ $err_name ] );
	$err_out = (string) shell_exec( escapeshellarg( PHP_BINARY ) . ' -d display_errors=1 -d error_reporting=-1 ' . escapeshellarg( $err_file ) . ' 2>&1' );
	err_check( 0 === strpos( ltrim( $err_out ), '<!DOCTYPE html>' ) && false === stripos( $err_out, 'error:' ) && false === stripos( $err_out, 'warning' ), "$err_name runs standalone, without WordPress" );
	unlink( $err_file );
}
err_check( false !== strpos( $err_src['maintenance.php'], "503 Service Unavailable', true, 503" ) && false !== strpos( $err_src['maintenance.php'], 'Retry-After' ), 'maintenance.php sends 503 and Retry-After' );
err_check( false !== strpos( $err_src['php-error.php'], "500 Internal Server Error', true, 500" ) && false === strpos( $err_src['php-error.php'], 'Retry-After' ) && false !== strpos( $err_src['php-error.php'], 'Something went wrong' ), 'php-error.php sends 500, "Something went wrong"' );
err_check( false !== strpos( $err_src['db-error.php'], "503 Service Unavailable', true, 503" ) && false !== strpos( $err_src['db-error.php'], 'Retry-After' ) && false !== strpos( $err_src['db-error.php'], 'We cannot reach our database' ), 'db-error.php sends 503 and Retry-After, names the database' );
err_check( false === stripos( $err_src['php-error.php'] . $err_src['db-error.php'], 'maintenance' ), 'php-error.php and db-error.php never say maintenance' );

err_check( 'missing' === lailatulqadar_error_state( 'php-error.php' ), 'missing file reads as missing' );
file_put_contents( "$err_tmp/db-error.php", "<?php // Somebody else's page\n" );
err_check( 'foreign' === lailatulqadar_error_state( 'db-error.php' ), 'foreign file reads as foreign' );
$err_res = lailatulqadar_error_install();
err_check( 'written' === $err_res['php-error.php'] && 'written' === $err_res['maintenance.php'], 'installer writes missing files' );
err_check( 'kept' === $err_res['db-error.php'] && "<?php // Somebody else's page\n" === file_get_contents( "$err_tmp/db-error.php" ), 'installer keeps a foreign file' );
err_check( 'installed' === lailatulqadar_error_state( 'php-error.php' ) && 'unchanged' === lailatulqadar_error_install()['php-error.php'], 'current file reads as installed and is left' );
file_put_contents( "$err_tmp/maintenance.php", str_replace( 'Back shortly', 'Old words', $err_src['maintenance.php'] ) );
err_check( 'outdated' === lailatulqadar_error_state( 'maintenance.php' ), 'an older copy of the theme reads as outdated' );
err_check( 'written' === lailatulqadar_error_install()['maintenance.php'] && file_get_contents( "$err_tmp/maintenance.php" ) === $err_src['maintenance.php'], 'installer refreshes an outdated copy' );
file_put_contents( "$err_tmp/php-error.php", $err_src['maintenance.php'] );
err_check( 'outdated' === lailatulqadar_error_state( 'php-error.php' ) && 'written' === lailatulqadar_error_install()['php-error.php'] && file_get_contents( "$err_tmp/php-error.php" ) === $err_src['php-error.php'], 'the maintenance page in php-error.php is replaced by the right page' );
file_put_contents( "$err_tmp/maintenance.php", "<?php\n// Paulus error page drop-in 2.77.3. Written by the Paulus theme; safe to delete.\n" );
err_check( 'theme' === lailatulqadar_error_state( 'maintenance.php' ), 'another theme\'s marked leftover reads as theme' );
err_check( 'written' === lailatulqadar_error_install()['maintenance.php'] && file_get_contents( "$err_tmp/maintenance.php" ) === $err_src['maintenance.php'], 'installer replaces another theme\'s marked leftover' );
err_check( lailatulqadar_error_needs_install(), 'with nothing remembered, the files need writing' );
lailatulqadar_error_auto_install();
err_check( ! lailatulqadar_error_needs_install(), 'after an automatic install nothing is pending' );
file_put_contents( "$err_tmp/maintenance.php", str_replace( 'Back shortly', 'Edited', (string) file_get_contents( "$err_tmp/maintenance.php" ) ) );
err_check( lailatulqadar_error_needs_install(), 'an edited copy of its own is noticed with the fingerprint current' );
lailatulqadar_error_maybe_install();
err_check( 'installed' === lailatulqadar_error_state( 'maintenance.php' ), 'an administrator\'s visit repairs it' );
unlink( "$err_tmp/php-error.php" );
err_check( lailatulqadar_error_needs_install(), 'a missing file is noticed' );
lailatulqadar_error_maybe_install();
err_check( 'installed' === lailatulqadar_error_state( 'php-error.php' ), 'an administrator\'s visit writes it back' );
lailatulqadar_error_remove();
err_check( 'missing' === lailatulqadar_error_state( 'maintenance.php' ) && 'missing' === lailatulqadar_error_state( 'php-error.php' ) && "<?php // Somebody else's page\n" === file_get_contents( "$err_tmp/db-error.php" ), 'switching away removes only the theme\'s own files' );
foreach ( glob( "$err_tmp/*.tmp" ) as $err_left ) {
	err_check( false, "temporary file left: $err_left" );
}

exec( escapeshellarg( PHP_BINARY ) . ' ' . escapeshellarg( dirname( __DIR__ ) . '/bin/build-error-pages.php' ) . ' --check 2>&1', $err_o, $err_code );
err_check( 0 === $err_code, 'errors/*.html match the builder' );
foreach ( array( '403', '500', '502', '503', '504' ) as $err_c ) {
	$err_h = (string) @file_get_contents( dirname( __DIR__ ) . "/errors/$err_c.html" );
	err_check( false !== strpos( $err_h, 'noindex' ) && false === strpos( $err_h, '<?php' ), "errors/$err_c.html is static and noindex" );
}
err_check( false !== strpos( lailatulqadar_error_htaccess_snippet(), 'ErrorDocument 503 /wp-content/themes/lailatulqadar/errors/503.html' ) && false !== strpos( lailatulqadar_error_htaccess_snippet(), 'ErrorDocument 504 /wp-content/themes/lailatulqadar/errors/504.html' ), '.htaccess lines point at errors/' );
$err_404 = (string) file_get_contents( dirname( __DIR__ ) . '/templates/404.html' );
err_check( false !== strpos( $err_404, '<p class="lq-404__alt" lang="ms">Halaman tidak ditemui. Alamatnya mungkin telah berubah. Cari dalam panduan atau pilih salah satu halaman di bawah.</p>' ), 'the 404 page carries the Malay line' );

array_map( 'unlink', glob( "$err_tmp/*" ) );
rmdir( $err_tmp );
echo $err_fail ? "$err_fail failed\n" : "all passed\n";
exit( $err_fail ? 1 : 0 );
