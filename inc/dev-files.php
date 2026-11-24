<?php
/**
 * Development files clean-up.
 *
 * The owner uploads the theme over FTP from a working copy, which brings the
 * repository (.git, .gitignore, .github, .claude and other dot entries) and
 * the development folders (bin/ with the build scripts, tests/ with the
 * self-tests, tools/ if one appears) to the live server. The theme folder's
 * .htaccess refuses them on Apache until this runs; on Nginx nothing does.
 *
 * On the first request after the theme version changes, and every hour
 * through WP-Cron (an upload can bring .git back at any time), this deletes
 * those entries at the top of the theme folder. .htaccess, .user.ini and
 * .well-known stay, as do docs/, inc/data/ and the rest of the theme.
 * Read-only files are made writable first; a symlink is removed itself and
 * never followed.
 *
 * One request at a time, through a lease in the option
 * lailatulqadar_devfiles_lease ("<unix time> <token>"):
 *
 * - A free lease is taken with one INSERT IGNORE, which only one request can
 *   win. (Core's add_option() is an INSERT ... ON DUPLICATE KEY UPDATE, so
 *   two requests that both miss the row would both "add" it.)
 * - The running clean-up renews it at most once a minute.
 * - A lease left unrenewed for five minutes belongs to a dead request and is
 *   taken over with one compare-and-set UPDATE, which only one request wins.
 * - It is released only while it still holds this request's value, in one
 *   DELETE.
 * - After taking it, the recorded version is read again from the database,
 *   not the cache, and the run stops if another request finished.
 *
 * The version (lailatulqadar_devfiles_version) is recorded only once nothing
 * is left. When something cannot be removed, the lease is kept, so the next
 * try comes after five minutes and never on every request, and the error log
 * gets one line a day.
 *
 * Nothing is removed on a local or development site
 * (wp_get_environment_type()), or where wp-config.php defines
 * LQ_KEEP_DOTFILES as true; the hourly event is not scheduled there.
 *
 * @package Lailatulqadar
 * @since   1.24.0
 */

defined( 'ABSPATH' ) || exit;

const LAILATULQADAR_DEVFILES_VERSION = 'lailatulqadar_devfiles_version';
const LAILATULQADAR_DEVFILES_LEASE   = 'lailatulqadar_devfiles_lease';
const LAILATULQADAR_DEVFILES_LOGGED  = 'lailatulqadar_devfiles_logged';
const LAILATULQADAR_DEVFILES_EVENT   = 'lailatulqadar_devfiles_hourly';
const LAILATULQADAR_DEVFILES_STALE   = 300;
const LAILATULQADAR_DEVFILES_RENEW   = 60;

/**
 * Whether this site keeps its development files: a local or development
 * site, or LQ_KEEP_DOTFILES set to true.
 *
 * @return bool
 */
function lailatulqadar_devfiles_keep() {
	if ( defined( 'LQ_KEEP_DOTFILES' ) && true === LQ_KEEP_DOTFILES ) {
		return true;
	}
	return in_array( wp_get_environment_type(), array( 'local', 'development' ), true );
}

/**
 * The development entries at the top of a theme folder: every name that
 * begins with a dot but .htaccess, .user.ini and .well-known, and the bin/,
 * tests/ and tools/ folders. Sorted, so runs are repeatable.
 *
 * @param string $dir Theme folder.
 * @return string[] Names, not paths.
 */
function lailatulqadar_devfiles_entries( $dir ) {
	$names = is_dir( $dir ) ? scandir( $dir ) : false;
	if ( false === $names ) {
		return array();
	}
	$keep   = array( '.', '..', '.htaccess', '.user.ini', '.well-known' );
	$folder = array( 'bin', 'tests', 'tools' );
	$out    = array();
	foreach ( $names as $name ) {
		if ( in_array( $name, $keep, true ) ) {
			continue;
		}
		$path = $dir . '/' . $name;
		if ( '.' === $name[0] || ( in_array( $name, $folder, true ) && ( is_dir( $path ) || is_link( $path ) ) ) ) {
			$out[] = $name;
		}
	}
	sort( $out );
	return $out;
}

/**
 * The lease's value as the database holds it now, past every cache.
 *
 * @param string $name Option name.
 * @return string|null Null when the row is absent.
 */
function lailatulqadar_devfiles_db_value( $name ) {
	global $wpdb;
	return $wpdb->get_var( $wpdb->prepare( "SELECT option_value FROM {$wpdb->options} WHERE option_name = %s LIMIT 1", $name ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
}

/**
 * Drop the cached copies of an option written with direct SQL.
 *
 * @param string $name Option name.
 */
function lailatulqadar_devfiles_flush( $name ) {
	wp_cache_delete( $name, 'options' );
	wp_cache_delete( 'notoptions', 'options' );
}

/**
 * A new lease value for this request.
 *
 * @param int $now Unix time.
 * @return string
 */
function lailatulqadar_devfiles_value( $now ) {
	return (int) $now . ' ' . bin2hex( random_bytes( 8 ) );
}

/**
 * Take the lease: free with one INSERT IGNORE, or stale (unrenewed for five
 * minutes) with one compare-and-set UPDATE. A fresh lease held by another
 * request costs one SELECT.
 *
 * @param int $now Unix time.
 * @return string This request's lease value, or '' when another holds it.
 */
function lailatulqadar_devfiles_take( $now ) {
	global $wpdb;
	$mine = lailatulqadar_devfiles_value( $now );
	$held = lailatulqadar_devfiles_db_value( LAILATULQADAR_DEVFILES_LEASE );
	if ( null !== $held ) {
		// Held: one read is all a request pays while the lease is fresh.
		return (int) $held > $now - LAILATULQADAR_DEVFILES_STALE ? '' : ( lailatulqadar_devfiles_swap( $held, $mine ) ? $mine : '' );
	}
	$won = (int) $wpdb->query( $wpdb->prepare( "INSERT IGNORE INTO {$wpdb->options} (option_name, option_value, autoload) VALUES (%s, %s, 'off')", LAILATULQADAR_DEVFILES_LEASE, $mine ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	lailatulqadar_devfiles_flush( LAILATULQADAR_DEVFILES_LEASE );
	// Another request inserted it between the read and the insert.
	return 1 === $won ? $mine : '';
}

/**
 * Replace the lease value only while it still holds $old: one UPDATE, so of
 * two requests that read the same value only one changes it.
 *
 * @param string $old Value read.
 * @param string $new Value to write.
 * @return bool Whether this request changed it.
 */
function lailatulqadar_devfiles_swap( $old, $new ) {
	global $wpdb;
	$done = (int) $wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->options} SET option_value = %s WHERE option_name = %s AND option_value = %s", $new, LAILATULQADAR_DEVFILES_LEASE, $old ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	lailatulqadar_devfiles_flush( LAILATULQADAR_DEVFILES_LEASE );
	return 1 === $done;
}

/**
 * Release the lease while it still holds this request's value, in one
 * DELETE.
 *
 * @param string $mine This request's lease value.
 */
function lailatulqadar_devfiles_release( $mine ) {
	global $wpdb;
	$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name = %s AND option_value = %s", LAILATULQADAR_DEVFILES_LEASE, $mine ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	lailatulqadar_devfiles_flush( LAILATULQADAR_DEVFILES_LEASE );
}

/**
 * Remove a path: a symlink itself, a folder with everything below it, a
 * file. Read-only entries are made writable first. $tick runs before each
 * removal and stops the work by returning false.
 *
 * @param string   $path Path.
 * @param callable $tick Lease keeper.
 * @return int What could not be removed (files and folders).
 */
function lailatulqadar_devfiles_remove( $path, $tick ) {
	if ( ! $tick() ) {
		return 1;
	}
	if ( is_link( $path ) ) {
		// unlink() removes the link; on Windows a link to a folder needs rmdir().
		return ( @unlink( $path ) || @rmdir( $path ) ) ? 0 : 1; // phpcs:ignore WordPress.PHP.NoSilencedErrors, WordPress.WP.AlternativeFunctions.unlink_unlink
	}
	if ( is_dir( $path ) ) {
		if ( ! is_writable( $path ) || ! is_readable( $path ) ) {
			@chmod( $path, 0755 ); // phpcs:ignore WordPress.PHP.NoSilencedErrors, WordPress.WP.AlternativeFunctions.file_system_operations_chmod
		}
		$names = @scandir( $path ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
		if ( false === $names ) {
			return 1;
		}
		$left = 0;
		foreach ( array_diff( $names, array( '.', '..' ) ) as $name ) {
			$left += lailatulqadar_devfiles_remove( $path . '/' . $name, $tick );
		}
		if ( $left ) {
			return $left + 1;
		}
		return @rmdir( $path ) ? 0 : 1; // phpcs:ignore WordPress.PHP.NoSilencedErrors, WordPress.WP.AlternativeFunctions.file_system_operations_rmdir
	}
	if ( ! file_exists( $path ) ) {
		return 0;
	}
	if ( ! is_writable( $path ) ) {
		// Git keeps its objects read-only; Windows refuses to delete those.
		@chmod( $path, 0644 ); // phpcs:ignore WordPress.PHP.NoSilencedErrors, WordPress.WP.AlternativeFunctions.file_system_operations_chmod
	}
	return @unlink( $path ) ? 0 : 1; // phpcs:ignore WordPress.PHP.NoSilencedErrors, WordPress.WP.AlternativeFunctions.unlink_unlink
}

/**
 * One line in the error log a day while files are left.
 *
 * @param int $left What could not be removed.
 * @param int $now  Unix time.
 */
function lailatulqadar_devfiles_log( $left, $now ) {
	$day = gmdate( 'Y-m-d', $now );
	if ( get_option( LAILATULQADAR_DEVFILES_LOGGED ) === $day ) {
		return;
	}
	update_option( LAILATULQADAR_DEVFILES_LOGGED, $day, false );
	error_log( sprintf( 'Lailatulqadar: %d development files or folders in the theme folder could not be removed; delete them by FTP.', $left ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions
}

/**
 * The clean-up, under the lease.
 *
 * @param string        $dir   Theme folder (LQ_DIR when empty).
 * @param callable|null $clock Returns the Unix time (time() when null).
 * @return string done, current (another request finished), busy (another
 *                holds the lease), lost (the lease was taken over), left
 *                (something could not be removed) or kept.
 */
function lailatulqadar_devfiles_run( $dir = '', $clock = null ) {
	if ( lailatulqadar_devfiles_keep() ) {
		return 'kept';
	}
	$dir   = '' === $dir ? LQ_DIR : $dir;
	$clock = null === $clock ? 'time' : $clock;
	$mine  = lailatulqadar_devfiles_take( (int) $clock() );
	if ( '' === $mine ) {
		return 'busy';
	}
	if ( lailatulqadar_devfiles_db_value( LAILATULQADAR_DEVFILES_VERSION ) === LQ_VERSION ) {
		lailatulqadar_devfiles_flush( LAILATULQADAR_DEVFILES_VERSION );
		lailatulqadar_devfiles_release( $mine );
		return 'current';
	}

	$lost = false;
	$tick = static function () use ( &$mine, &$lost, $clock ) {
		if ( $lost ) {
			return false;
		}
		$now = (int) $clock();
		if ( $now - (int) $mine < LAILATULQADAR_DEVFILES_RENEW ) {
			return true;
		}
		$next = lailatulqadar_devfiles_value( $now );
		if ( lailatulqadar_devfiles_swap( $mine, $next ) ) {
			$mine = $next;
			return true;
		}
		$lost = true;
		return false;
	};

	$left = 0;
	foreach ( lailatulqadar_devfiles_entries( $dir ) as $name ) {
		$left += lailatulqadar_devfiles_remove( $dir . '/' . $name, $tick );
	}
	clearstatcache();
	if ( $lost ) {
		return 'lost';
	}
	$still = count( lailatulqadar_devfiles_entries( $dir ) );
	if ( $left || $still ) {
		// The lease stays, so the next try waits until it is stale.
		lailatulqadar_devfiles_log( max( $left, $still ), (int) $clock() );
		return 'left';
	}
	update_option( LAILATULQADAR_DEVFILES_VERSION, LQ_VERSION, true );
	lailatulqadar_devfiles_release( $mine );
	return 'done';
}

/**
 * On every request: run once per theme version. The version option is
 * autoloaded, so the check costs no query.
 *
 * @return string What happened, or '' when nothing was due.
 */
function lailatulqadar_devfiles_maybe_run() {
	if ( lailatulqadar_devfiles_keep() || get_option( LAILATULQADAR_DEVFILES_VERSION ) === LQ_VERSION ) {
		return '';
	}
	return lailatulqadar_devfiles_run();
}
add_action( 'init', 'lailatulqadar_devfiles_maybe_run', 1 );

/**
 * Every hour: an upload can bring .git back after the clean-up has run.
 * Nothing to do while the folder has no development entries; otherwise the
 * recorded version is forgotten and the same clean-up runs under the same
 * lease.
 *
 * @param string        $dir   Theme folder (LQ_DIR when empty).
 * @param callable|null $clock Returns the Unix time.
 * @return string What happened, or '' when nothing was found.
 */
function lailatulqadar_devfiles_hourly( $dir = '', $clock = null ) {
	$dir = '' === $dir ? LQ_DIR : $dir;
	if ( lailatulqadar_devfiles_keep() || ! lailatulqadar_devfiles_entries( $dir ) ) {
		return '';
	}
	delete_option( LAILATULQADAR_DEVFILES_VERSION );
	return lailatulqadar_devfiles_run( $dir, $clock );
}
add_action( LAILATULQADAR_DEVFILES_EVENT, 'lailatulqadar_devfiles_hourly', 10, 0 );

/**
 * Schedule the hourly event, or clear it on a site that keeps its files.
 */
function lailatulqadar_devfiles_schedule() {
	$next = wp_next_scheduled( LAILATULQADAR_DEVFILES_EVENT );
	if ( lailatulqadar_devfiles_keep() ) {
		if ( $next ) {
			wp_clear_scheduled_hook( LAILATULQADAR_DEVFILES_EVENT );
		}
		return;
	}
	if ( ! $next ) {
		wp_schedule_event( time() + HOUR_IN_SECONDS, 'hourly', LAILATULQADAR_DEVFILES_EVENT );
	}
}
add_action( 'init', 'lailatulqadar_devfiles_schedule' );

/**
 * Clear the hourly event when the site leaves the theme.
 */
function lailatulqadar_devfiles_unschedule() {
	wp_clear_scheduled_hook( LAILATULQADAR_DEVFILES_EVENT );
}
add_action( 'switch_theme', 'lailatulqadar_devfiles_unschedule' );
