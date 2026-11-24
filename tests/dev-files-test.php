<?php
/**
 * Self-test of the development files clean-up (inc/dev-files.php). Builds a
 * temporary theme folder and a fake $wpdb whose INSERT IGNORE, compare-and-set
 * UPDATE and conditional DELETE behave as in MySQL (rows affected), with an
 * options cache kept apart from the table, so a stale cache can be shown.
 * Run: php tests/dev-files-test.php
 *
 * @package Lailatulqadar
 * @since   1.24.0
 */

if ( 'cli' !== PHP_SAPI ) {
	exit;
}
define( 'ABSPATH', '/' );
define( 'LQ_VERSION', '9.9.9' );
define( 'LQ_DIR', '/nonexistent' );
define( 'HOUR_IN_SECONDS', 3600 );
ini_set( 'error_log', sys_get_temp_dir() . '/lq-devfiles-test.log' );
@unlink( ini_get( 'error_log' ) );

$GLOBALS['env']   = 'production';
$GLOBALS['cache'] = array();
$GLOBALS['hooks'] = array();

/** Fake database: one options table, MySQL's rows-affected semantics. */
class LQ_Fake_DB {
	public $options = 'wp_options';
	public $rows    = array();
	public $log     = array();
	public function prepare( $sql, ...$args ) {
		foreach ( $args as $a ) {
			$sql = preg_replace( '/%s/', "'" . addslashes( (string) $a ) . "'", $sql, 1 );
		}
		return $sql;
	}
	public function get_var( $sql ) {
		$this->log[] = $sql;
		preg_match( "/option_name = '([^']*)'/", $sql, $m );
		return $this->rows[ $m[1] ] ?? null;
	}
	public function query( $sql ) {
		$this->log[] = $sql;
		if ( preg_match( "/^INSERT IGNORE .* VALUES \('([^']*)', '([^']*)'/", $sql, $m ) ) {
			if ( isset( $this->rows[ $m[1] ] ) ) {
				return 0;
			}
			$this->rows[ $m[1] ] = $m[2];
			return 1;
		}
		if ( preg_match( "/^UPDATE .* SET option_value = '([^']*)' WHERE option_name = '([^']*)' AND option_value = '([^']*)'/", $sql, $m ) ) {
			if ( ( $this->rows[ $m[2] ] ?? null ) !== $m[3] ) {
				return 0;
			}
			$this->rows[ $m[2] ] = $m[1];
			return 1;
		}
		if ( preg_match( "/^DELETE .* WHERE option_name = '([^']*)' AND option_value = '([^']*)'/", $sql, $m ) ) {
			if ( ( $this->rows[ $m[1] ] ?? null ) !== $m[2] ) {
				return 0;
			}
			unset( $this->rows[ $m[1] ] );
			return 1;
		}
		throw new RuntimeException( 'Unexpected SQL: ' . $sql );
	}
}
$GLOBALS['wpdb'] = new LQ_Fake_DB();

function add_action( $hook, $fn ) { $GLOBALS['hooks'][ $hook ][] = $fn; }
function wp_get_environment_type() { return $GLOBALS['env']; }
// get_option() reads the cache first, as with a persistent object cache.
function get_option( $k, $d = false ) {
	if ( array_key_exists( $k, $GLOBALS['cache'] ) ) {
		return $GLOBALS['cache'][ $k ];
	}
	return $GLOBALS['wpdb']->rows[ $k ] ?? $d;
}
function update_option( $k, $v ) { $GLOBALS['wpdb']->rows[ $k ] = $v; $GLOBALS['cache'][ $k ] = $v; return true; }
function delete_option( $k ) { unset( $GLOBALS['wpdb']->rows[ $k ], $GLOBALS['cache'][ $k ] ); return true; }
function wp_cache_delete( $k ) { unset( $GLOBALS['cache'][ $k ] ); return true; }
function wp_next_scheduled() { return false; }
function wp_schedule_event() {}
function wp_clear_scheduled_hook() {}

require dirname( __DIR__ ) . '/inc/dev-files.php';

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

/** Remove a tree the test made (links not followed). */
function lq_rm( $p ) {
	if ( is_link( $p ) || is_file( $p ) ) {
		@chmod( $p, 0644 );
		unlink( $p );
		return;
	}
	if ( is_dir( $p ) ) {
		@chmod( $p, 0755 );
		foreach ( array_diff( scandir( $p ), array( '.', '..' ) ) as $n ) {
			lq_rm( $p . '/' . $n );
		}
		rmdir( $p );
	}
}

/** A theme folder as an FTP upload of the working copy leaves it. */
function lq_theme( $base ) {
	$t = $base . '/theme';
	foreach ( array( '.git/objects/ab', '.git/refs/heads', '.github/workflows', '.claude', '.well-known', 'bin', 'tests/stubs', 'tools', 'docs', 'inc/data', 'data', 'css' ) as $d ) {
		mkdir( $t . '/' . $d, 0777, true );
	}
	$files = array( '.git/HEAD', '.git/config', '.git/refs/heads/main', '.github/workflows/ci.yml', '.claude/CLAUDE.md', '.gitignore', '.gitattributes', '.env', '.htaccess', '.user.ini', '.well-known/security.txt', 'bin/build.php', 'tests/a-test.php', 'tests/stubs/wp.php', 'tools/x.sh', 'docs/settings.md', 'inc/data/pages.json', 'data/x.json', 'css/front.css', 'functions.php', 'style.css' );
	foreach ( $files as $f ) {
		file_put_contents( $t . '/' . $f, $f );
	}
	// Git keeps its objects read-only, in read-only folders.
	file_put_contents( $t . '/.git/objects/ab/cdef', 'blob' );
	chmod( $t . '/.git/objects/ab/cdef', 0444 );
	chmod( $t . '/.git/objects/ab', 0555 );
	return $t;
}

/** Reset the fake site between cases. */
function lq_reset() {
	$GLOBALS['wpdb']->rows = array();
	$GLOBALS['wpdb']->log  = array();
	$GLOBALS['cache']      = array();
	$GLOBALS['env']        = 'production';
}

$base = sys_get_temp_dir() . '/lq-devfiles-' . uniqid();
mkdir( $base );
$dev  = array( '.claude', '.env', '.git', '.gitattributes', '.github', '.gitignore', 'bin', 'tests', 'tools' );
$now  = 1_800_000_000;

// 1. Listing.
$t = lq_theme( $base );
check( lailatulqadar_devfiles_entries( $t ) === $dev, 'lists the dot entries and bin/, tests/, tools/; not .htaccess, .user.ini, .well-known: ' . implode( ' ', lailatulqadar_devfiles_entries( $t ) ) );
check( array() === lailatulqadar_devfiles_entries( $base . '/missing' ), 'a missing folder lists nothing' );

// 2. Development and local sites keep them.
foreach ( array( 'development', 'local' ) as $lq_type ) {
	lq_reset();
	$GLOBALS['env'] = $lq_type;
	check( 'kept' === lailatulqadar_devfiles_run( $t ) && lailatulqadar_devfiles_entries( $t ) === $dev && ! $GLOBALS['wpdb']->log, "a $lq_type site keeps them and touches no lease" );
	check( '' === lailatulqadar_devfiles_maybe_run() && '' === lailatulqadar_devfiles_hourly( $t ) && lailatulqadar_devfiles_entries( $t ) === $dev, "on a $lq_type site neither the request hook nor the hourly event runs" );
}

// 3. A fresh lease held by another request keeps this one out.
lq_reset();
$GLOBALS['wpdb']->rows[ LAILATULQADAR_DEVFILES_LEASE ] = ( $now - 100 ) . ' other';
$r = lailatulqadar_devfiles_run( $t, fn() => $now );
check( 'busy' === $r && lailatulqadar_devfiles_entries( $t ) === $dev, 'a fresh lease (100 s old) keeps this request out; nothing removed' );
check( ( $now - 100 ) . ' other' === $GLOBALS['wpdb']->rows[ LAILATULQADAR_DEVFILES_LEASE ], 'the other request still holds its lease' );
check( 1 === count( $GLOBALS['wpdb']->log ), 'a request kept out pays one query' );

// 4. Two requests read the same stale lease: only one wins.
lq_reset();
$stale = ( $now - 301 ) . ' dead';
$GLOBALS['wpdb']->rows[ LAILATULQADAR_DEVFILES_LEASE ] = $stale;
$a = lailatulqadar_devfiles_swap( $stale, $now . ' a' );
$b = lailatulqadar_devfiles_swap( $stale, $now . ' b' );
check( $a && ! $b && $now . ' a' === $GLOBALS['wpdb']->rows[ LAILATULQADAR_DEVFILES_LEASE ], 'of two requests that read the same stale lease, only the first compare-and-set wins' );
lq_reset();
$x = lailatulqadar_devfiles_take( $now );
$y = lailatulqadar_devfiles_take( $now );
check( '' !== $x && '' === $y, 'of two requests that find no lease, only one takes it' );

// 5. A stale lease is taken over and the files go.
lq_reset();
$GLOBALS['wpdb']->rows[ LAILATULQADAR_DEVFILES_LEASE ] = $stale;
$r = lailatulqadar_devfiles_run( $t, fn() => $now );
check( 'done' === $r, 'a lease unrenewed for 301 s is taken over (' . $r . ')' );
check( array() === lailatulqadar_devfiles_entries( $t ) && ! file_exists( $t . '/.git' ) && ! file_exists( $t . '/tests' ) && ! file_exists( $t . '/bin' ) && ! file_exists( $t . '/tools' ), 'the development files are gone, read-only git objects included' );

// 6. The code, the data and .htaccess stay.
$stay = array( '.htaccess', '.user.ini', '.well-known/security.txt', 'docs/settings.md', 'inc/data/pages.json', 'data/x.json', 'css/front.css', 'functions.php', 'style.css' );
check( count( array_filter( $stay, fn( $f ) => is_file( $t . '/' . $f ) ) ) === count( $stay ), 'the code, data/, inc/data/, docs/, .htaccess, .user.ini and .well-known stay' );

// 7. The version is recorded and the lease freed.
check( LQ_VERSION === ( $GLOBALS['wpdb']->rows[ LAILATULQADAR_DEVFILES_VERSION ] ?? '' ), 'the version is recorded' );
check( ! isset( $GLOBALS['wpdb']->rows[ LAILATULQADAR_DEVFILES_LEASE ] ), 'the lease is freed' );

// 8. It does not run twice for one version.
$GLOBALS['wpdb']->log = array();
check( '' === lailatulqadar_devfiles_maybe_run() && ! $GLOBALS['wpdb']->log, 'the next request finds the version recorded and runs nothing, with no query' );
// Another request finished, but this one's cache still has the old version:
// after taking the lease it reads the table, stops and frees the lease.
lq_rm( $t );
$t = lq_theme( $base );
$GLOBALS['cache'][ LAILATULQADAR_DEVFILES_VERSION ] = '1.0.0';
$r = lailatulqadar_devfiles_run( $t, fn() => $now );
check( 'current' === $r && lailatulqadar_devfiles_entries( $t ) === $dev && ! isset( $GLOBALS['wpdb']->rows[ LAILATULQADAR_DEVFILES_LEASE ] ), 'with a stale cache, the version read from the table stops a second run and frees the lease (' . $r . ')' );

// 9. A running clean-up renews its lease (at most once a minute).
lq_reset();
$tick  = $now;
$clock = function () use ( &$tick ) {
	$tick += 7;
	return $tick;
};
$r       = lailatulqadar_devfiles_run( $t, $clock );
$renews  = count( array_filter( $GLOBALS['wpdb']->log, fn( $q ) => str_starts_with( $q, 'UPDATE' ) ) );
$elapsed = $tick - $now;
check( 'done' === $r && $renews >= 1 && $renews <= intdiv( $elapsed, 60 ) + 1, "a clean-up that runs $elapsed s renews its lease $renews times, at most once a minute" );
// A clean-up whose lease another request took over stops.
lq_rm( $t );
$t = lq_theme( $base );
lq_reset();
$tick  = $now;
$clock = function () use ( &$tick ) {
	$tick += 30;
	if ( $tick === $GLOBALS['now'] + 90 ) {
		$GLOBALS['wpdb']->rows[ LAILATULQADAR_DEVFILES_LEASE ] = $tick . ' thief';
	}
	return $tick;
};
$GLOBALS['now'] = $now;
$r = lailatulqadar_devfiles_run( $t, $clock );
check( 'lost' === $r && lailatulqadar_devfiles_entries( $t ) && $tick . ' thief' !== '' && str_ends_with( $GLOBALS['wpdb']->rows[ LAILATULQADAR_DEVFILES_LEASE ], ' thief' ) && ! isset( $GLOBALS['wpdb']->rows[ LAILATULQADAR_DEVFILES_VERSION ] ), 'a clean-up that loses its lease stops, records nothing and leaves the new holder alone (' . $r . ')' );

// 10. A symlink is removed; its target is not.
lq_rm( $t );
$t = lq_theme( $base );
lq_reset();
mkdir( $base . '/outside' );
file_put_contents( $base . '/outside/keep.txt', 'keep' );
symlink( $base . '/outside', $t . '/.linked' );
symlink( $base . '/outside', $t . '/.git/linked' );
symlink( $base . '/outside/keep.txt', $t . '/tests/keep-link' );
$r = lailatulqadar_devfiles_run( $t, fn() => $now );
check( 'done' === $r && ! is_link( $t . '/.linked' ) && ! file_exists( $t . '/.git' ), 'symlinks at the top and inside .git and tests/ are removed' );
check( is_file( $base . '/outside/keep.txt' ) && 'keep' === file_get_contents( $base . '/outside/keep.txt' ), 'their targets outside the theme are untouched' );

// 11. The hourly event.
$GLOBALS['wpdb']->log = array();
$v                    = $GLOBALS['wpdb']->rows[ LAILATULQADAR_DEVFILES_VERSION ];
check( '' === lailatulqadar_devfiles_hourly( $t, fn() => $now ) && ! $GLOBALS['wpdb']->log && $v === $GLOBALS['wpdb']->rows[ LAILATULQADAR_DEVFILES_VERSION ], 'the hourly event leaves a clean folder alone: no query, version kept' );
mkdir( $t . '/.git/objects', 0777, true );
file_put_contents( $t . '/.git/HEAD', 'ref' );
$r = lailatulqadar_devfiles_hourly( $t, fn() => $now + 3600 );
check( 'done' === $r && ! file_exists( $t . '/.git' ) && LQ_VERSION === $GLOBALS['wpdb']->rows[ LAILATULQADAR_DEVFILES_VERSION ] && ! isset( $GLOBALS['wpdb']->rows[ LAILATULQADAR_DEVFILES_LEASE ] ), 'the hourly event removes a .git uploaded after the version was recorded, records the version and frees the lease (' . $r . ')' );

// 12. Something that cannot be removed: the lease is kept, one log line a day.
// Root can delete anything but an immutable file (chattr +i).
$immutable = false;
if ( function_exists( 'posix_geteuid' ) && 0 === posix_geteuid() ) {
	mkdir( $t . '/.git' );
	file_put_contents( $t . '/.git/stuck', 'x' );
	exec( 'chattr +i ' . escapeshellarg( $t . '/.git/stuck' ) . ' 2>/dev/null', $o, $code );
	$immutable = 0 === $code;
} else {
	// Another user cannot delete a file in a folder it cannot write.
	mkdir( $t . '/.git/locked', 0777, true );
	file_put_contents( $t . '/.git/locked/stuck', 'x' );
	chmod( $t . '/.git', 0555 );
	chmod( $t . '/.git/locked', 0555 );
	$immutable = true;
}
if ( $immutable ) {
	lq_reset();
	$r1   = lailatulqadar_devfiles_run( $t, fn() => $now );
	$held = $GLOBALS['wpdb']->rows[ LAILATULQADAR_DEVFILES_LEASE ] ?? '';
	$r2   = lailatulqadar_devfiles_run( $t, fn() => $now + 60 );
	$r3   = lailatulqadar_devfiles_run( $t, fn() => $now + 301 );
	$log  = is_file( ini_get( 'error_log' ) ) ? file( ini_get( 'error_log' ) ) : array();
	check( 'left' === $r1 && '' !== $held && ! isset( $GLOBALS['wpdb']->rows[ LAILATULQADAR_DEVFILES_VERSION ] ), 'a file that cannot be removed leaves the version unrecorded and the lease held (' . $r1 . ')' );
	check( 'busy' === $r2 && 'left' === $r3, 'the next try waits five minutes: busy after 60 s, retried after 301 s' );
	check( 1 === count( $log ) && str_contains( $log[0], 'could not be removed; delete them by FTP' ), 'one log line for the day: ' . trim( $log[0] ?? '' ) );
	exec( 'chattr -i ' . escapeshellarg( $t . '/.git/stuck' ) . ' 2>/dev/null' );
} else {
	echo "skip the unremovable-file case (cannot make a file this user cannot delete)\n";
}

// 13. LQ_KEEP_DOTFILES (defined last: a constant cannot be undefined).
lq_rm( $t );
$t = lq_theme( $base );
lq_reset();
define( 'LQ_KEEP_DOTFILES', true );
check( 'kept' === lailatulqadar_devfiles_run( $t, fn() => $now ) && lailatulqadar_devfiles_entries( $t ) === $dev, 'LQ_KEEP_DOTFILES true keeps them on a production site' );

lq_rm( $base );
echo $fail ? "$fail failed\n" : "All checks passed\n";
exit( $fail ? 1 : 0 );
