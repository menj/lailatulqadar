<?php
/**
 * Self-test for inc/hardening.php: the scanner probes the logs show are
 * short-circuited, WordPress's own entry points and the site's pages are
 * not, and visitors get no REST user routes. Run: php tests/hardening-test.php
 *
 * @package Lailatulqadar
 */

if ( 'cli' !== PHP_SAPI ) {
	exit;
}

define( 'ABSPATH', __DIR__ . '/' );
$GLOBALS['lq_logged_in'] = false;
function add_filter() {}
function add_action() {}
function is_user_logged_in() { return $GLOBALS['lq_logged_in']; }
require dirname( __DIR__ ) . '/inc/hardening.php';

$fail = 0;
function check( $ok, $what ) {
	global $fail;
	echo ( $ok ? 'ok   ' : 'FAIL ' ) . $what . "\n";
	$fail += $ok ? 0 : 1;
}

foreach ( array( '/admin.php', '/info.php', '/1.php', '/3PJcpMFsD8B.php', '/wp-content/plugins/hellopress/wp_filemanager.php', '/_phpinfo.php', '/.env', '/.env.production', '/api/.env', '/.git/config', '/.well-known/pki-validation/cloud.php', '/vendor/phpunit/phpunit/src/Util/PHP/eval-stdin.php', '/x.php/extra', '/%2eenv', '/WP-LOGIN.PHP', '/ADMIN.PHP', '/.ENV' ) as $p ) {
	check( lq_is_probe( $p ), "probe: $p" );
}
foreach ( array( '/', '/ms/', '/ms/peta-laman/', '/what-is-laylat-al-qadr/', '/index.php', '/wp-login.php', '/wp-cron.php', '/xmlrpc.php', '/wp-comments-post.php', '/wp-admin/', '/wp-admin/admin-ajax.php', '/wp-admin/post.php', '/.well-known/acme-challenge/token', '/sitemap.xml', '/wp-json/wp/v2/posts', '/image.webp' ) as $p ) {
	check( ! lq_is_probe( $p ), "not a probe: $p" );
}

$routes = array( '/' => 1, '/wp/v2/posts' => 1, '/wp/v2/users' => 1, '/wp/v2/users/(?P<id>[\d]+)' => 1, '/wp/v2/users/me' => 1, '/wp/v2/usersettings' => 1 );
$anon   = lq_hide_rest_users( $routes );
check( ! isset( $anon['/wp/v2/users'], $anon['/wp/v2/users/(?P<id>[\d]+)'], $anon['/wp/v2/users/me'] ) && isset( $anon['/wp/v2/posts'], $anon['/wp/v2/usersettings'] ), 'a visitor gets no user routes, and the others stay' );
$GLOBALS['lq_logged_in'] = true;
check( lq_hide_rest_users( $routes ) === $routes, 'a logged-in user keeps every route' );

echo $fail ? "$fail failed\n" : "all passed\n";
exit( $fail ? 1 : 0 );
