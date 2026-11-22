<?php
/**
 * Self-test for the theme's state-changing admin handlers: every
 * admin_post_* and wp_ajax_* callback checks a capability first, before it
 * checks a nonce or reads the request, then checks a nonce; every wp_die()
 * in the theme sends an HTTP status; no handler is open to visitors
 * (wp_ajax_nopriv_*, admin_post_nopriv_*); and no REST route lacks a
 * permission_callback. Static: reads the source, runs nothing.
 * Run: php tests/admin-actions-test.php
 *
 * @package Lailatulqadar
 * @since   1.22.1
 */

if ( 'cli' !== PHP_SAPI ) {
	exit;
}

$root  = dirname( __DIR__ );
$files = array_merge( glob( $root . '/inc/*.php' ), array( $root . '/functions.php' ) );
$src   = array();
foreach ( $files as $file ) {
	$src[ basename( $file ) ] = (string) file_get_contents( $file );
}
$all = implode( "\n", $src );

$fail = 0;
/**
 * Print one result.
 *
 * @param bool   $ok   Passed.
 * @param string $what Label.
 */
function check( $ok, $what ) {
	global $fail;
	echo ( $ok ? 'ok   ' : 'FAIL ' ) . $what . "\n";
	$fail += $ok ? 0 : 1;
}

// Helpers that check the capability and then the nonce themselves.
$guards = array( 'lq_sec_check', 'lq_bulk_page_status' );

// The body of a top-level function, from its declaration to the closing brace at column 0.
$body = static function ( $name ) use ( $all ) {
	return preg_match( '/^function ' . preg_quote( $name, '/' ) . '\(.*?^}$/ms', $all, $m ) ? $m[0] : '';
};

// Every callback hooked to admin_post_* or wp_ajax_* (with a named function).
preg_match_all( "/add_action\(\s*'((?:admin_post|wp_ajax)_[a-z0-9_]+)',\s*'([a-z0-9_]+)'/", $all, $hooks, PREG_SET_ORDER );
check( count( $hooks ) >= 10, 'found the admin handlers (' . count( $hooks ) . ')' );

foreach ( $hooks as $hook ) {
	list( , $action, $fn ) = $hook;
	$code = $body( $fn );
	if ( '' === $code ) {
		check( false, "$action: function $fn found" );
		continue;
	}
	// Strip comments so a word in a comment counts for nothing.
	$code = preg_replace( '#//[^\n]*|/\*.*?\*/#s', '', $code );

	$cap   = strpos( $code, 'current_user_can(' );
	$guard = false;
	foreach ( $guards as $g ) {
		$at = strpos( $code, $g . '(' );
		if ( false !== $at && ( false === $guard || $at < $guard ) ) {
			$guard = $at;
		}
	}
	$first_cap = false === $cap ? $guard : ( false === $guard ? $cap : min( $cap, $guard ) );
	check( false !== $first_cap, "$action: checks a capability" );

	$nonce = false;
	foreach ( array( 'check_admin_referer(', 'check_ajax_referer(', 'wp_verify_nonce(' ) as $n ) {
		$at = strpos( $code, $n );
		if ( false !== $at && ( false === $nonce || $at < $nonce ) ) {
			$nonce = $at;
		}
	}
	check( false !== $nonce || false !== $guard, "$action: checks a nonce" );
	if ( false !== $nonce && false !== $first_cap ) {
		check( $first_cap < $nonce, "$action: capability before nonce" );
	}

	$read = false;
	if ( preg_match( '/\$_(GET|POST|REQUEST|FILES)\b/', $code, $m, PREG_OFFSET_CAPTURE ) ) {
		$read = $m[0][1];
	}
	if ( false !== $read && false !== $first_cap ) {
		check( $first_cap < $read, "$action: capability before reading the request" );
	}
}

// Every wp_die() sends an HTTP status (a bare wp_die() answers 500).
preg_match_all( '/wp_die\((.*?)\);/s', $all, $dies );
$bare = array();
foreach ( $dies[1] as $args ) {
	if ( ! preg_match( '/,\s*(?:[1-5][0-9]{2}|array\()/', $args ) ) {
		$bare[] = trim( substr( $args, 0, 60 ) );
	}
}
check( ! $bare, 'every wp_die() sends a status' . ( $bare ? ': ' . implode( ' | ', $bare ) : '' ) );

// Nothing that changes state is open to visitors.
check( ! preg_match( "/'(wp_ajax_nopriv|admin_post_nopriv)_/", $all ), 'no wp_ajax_nopriv_ or admin_post_nopriv_ handler' );

// Any REST route carries a permission callback.
preg_match_all( '/register_rest_route\((.*?)\);/s', $all, $routes );
$open = 0;
foreach ( $routes[1] as $r ) {
	$open += false === strpos( $r, 'permission_callback' ) ? 1 : 0;
}
check( 0 === $open, 'every REST route has a permission_callback (' . count( $routes[1] ) . ' routes)' );

echo $fail ? "$fail failed\n" : "All checks passed\n";
exit( $fail ? 1 : 0 );
