<?php
/**
 * Security: remote-publishing lockdown, spam gate on new content, clean-up
 * tools and 410 Gone for removed spam. Settings live in lailatulqadar_settings
 * (sec_* keys, all on by default) and appear on the Security tab of
 * Appearance > Theme Options. Ported from the Abrahamic theme (2.87.0) after
 * the October 2026 attack on abrahamic-religions.com.
 *
 * Emergency switch: define( 'LQ_SECURITY_LOCK', false ); in wp-config.php turns
 * the XML-RPC, application password and REST locks off without touching the options.
 *
 * @package Lailatulqadar
 */

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/security-detector.php';

define( 'LQ_SEC_LOG', 'lq_security_log' );
define( 'LQ_SEC_GONE', 'lq_security_gone' );
define( 'LQ_SEC_LOG_MAX', 200 );
define( 'LQ_SEC_GONE_MAX', 5000 );

/**
 * Whether a Security toggle is on.
 *
 * @param string $key Option key.
 * @return bool
 */
function lq_sec_on( $key ) {
	$locks = array( 'sec_xmlrpc_off', 'sec_app_passwords_off', 'sec_rest_lock' );
	if ( in_array( $key, $locks, true ) && defined( 'LQ_SECURITY_LOCK' ) && ! LQ_SECURITY_LOCK ) {
		return false;
	}
	return '1' === (string) lq_setting( $key );
}

/**
 * Address of the Security tab, with optional query arguments.
 *
 * @param array $args Query arguments.
 * @return string
 */
function lq_sec_url( $args = array() ) {
	return add_query_arg( $args, admin_url( 'themes.php?page=lailatulqadar&tab=security' ) );
}

/* -------------------------------------------------------------------------
 * 1. Remote-publishing lockdown
 * ---------------------------------------------------------------------- */

/**
 * XML-RPC off: no methods, no pingback header, xmlrpc.php answers 403.
 */
function lq_sec_xmlrpc() {
	if ( ! lq_sec_on( 'sec_xmlrpc_off' ) ) {
		return;
	}
	add_filter( 'xmlrpc_enabled', '__return_false' );
	add_filter( 'xmlrpc_methods', '__return_empty_array', 999 );
	add_filter( 'pings_open', '__return_false', 999 );
	add_filter(
		'wp_headers',
		function ( $headers ) {
			unset( $headers['X-Pingback'], $headers['x-pingback'] );
			return $headers;
		}
	);
	remove_action( 'wp_head', 'rsd_link' );
	if ( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST ) {
		// Counted, not logged one by one: the October 2026 attack sent over
		// 17,000 requests in twelve days from 701 addresses.
		lq_sec_count( 'xmlrpc' );
		if ( lq_sec_fake_jetpack() ) {
			lq_sec_count( 'fake-jetpack' );
		}
		status_header( 403 );
		header( 'Content-Type: text/plain; charset=utf-8' );
		echo 'XML-RPC is disabled on this site.';
		exit;
	}
}
add_action( 'after_setup_theme', 'lq_sec_xmlrpc', 1 );

/**
 * Lines that deny xmlrpc.php at the web server, for Apache 2.4 and 2.2. The
 * marker name is shared by all seven sister themes, so switching between
 * them never writes the block twice.
 *
 * @return string[]
 */
function lq_sec_htaccess_lines() {
	return array(
		'<Files xmlrpc.php>',
		'<IfModule mod_authz_core.c>',
		'Require all denied',
		'</IfModule>',
		'<IfModule !mod_authz_core.c>',
		'Order Deny,Allow',
		'Deny from all',
		'</IfModule>',
		'</Files>',
	);
}

/**
 * Path of the site's .htaccess, or '' when the server is not Apache or the
 * file cannot be written.
 *
 * @return string
 */
function lq_sec_htaccess_file() {
	if ( ! function_exists( 'get_home_path' ) ) {
		require_once ABSPATH . 'wp-admin/includes/file.php';
	}
	if ( ! function_exists( 'insert_with_markers' ) ) {
		require_once ABSPATH . 'wp-admin/includes/misc.php';
	}
	global $is_apache;
	if ( empty( $is_apache ) ) {
		return '';
	}
	$file = get_home_path() . '.htaccess';
	if ( file_exists( $file ) ? ! wp_is_writable( $file ) : ! wp_is_writable( dirname( $file ) ) ) {
		return '';
	}
	return $file;
}

/**
 * Keep the server-level block in step with the XML-RPC switch: written while
 * the switch is on, removed when it is off. Runs in the admin, at most once an
 * hour, so a lost or overwritten .htaccess is repaired without any action.
 *
 * @param bool $force Skip the hourly throttle.
 */
function lq_sec_sync_htaccess( $force = false ) {
	if ( ! $force && get_transient( 'lq_sec_htaccess_checked' ) ) {
		return;
	}
	set_transient( 'lq_sec_htaccess_checked', 1, HOUR_IN_SECONDS );
	$file = lq_sec_htaccess_file();
	if ( '' === $file ) {
		update_option( 'lq_security_htaccess', 'unavailable', false );
		return;
	}
	$want    = lq_sec_on( 'sec_xmlrpc_off' );
	$current = function_exists( 'extract_from_markers' ) ? extract_from_markers( $file, 'Block xmlrpc.php' ) : array();
	if ( $want && $current !== lq_sec_htaccess_lines() ) {
		insert_with_markers( $file, 'Block xmlrpc.php', lq_sec_htaccess_lines() );
	} elseif ( ! $want && $current ) {
		insert_with_markers( $file, 'Block xmlrpc.php', array() );
	}
	update_option( 'lq_security_htaccess', $want ? 'written' : 'off', false );
}
add_action( 'admin_init', 'lq_sec_sync_htaccess' );
add_action( 'after_switch_theme', function () { lq_sec_sync_htaccess( true ); } );
add_action( 'update_option_' . LQ_OPTION, function () { lq_sec_sync_htaccess( true ); } );

/**
 * Take the block out when the theme is switched to one outside the sister
 * themes. A sister theme writes the same block back on its first admin load.
 */
function lq_sec_remove_htaccess() {
	$file = lq_sec_htaccess_file();
	if ( '' !== $file ) {
		insert_with_markers( $file, 'Block xmlrpc.php', array() );
	}
	delete_transient( 'lq_sec_htaccess_checked' );
}
add_action( 'switch_theme', 'lq_sec_remove_htaccess' );

/**
 * Application passwords off.
 *
 * @param bool $available Core value.
 * @return bool
 */
function lq_sec_app_passwords( $available ) {
	return lq_sec_on( 'sec_app_passwords_off' ) ? false : $available;
}
add_filter( 'wp_is_application_passwords_available', 'lq_sec_app_passwords', 99 );

/**
 * Whether this REST request comes from a logged-in browser session with a
 * valid wp_rest nonce. Core sets $wp_rest_auth_cookie when the logged-in cookie
 * validates, and rest_cookie_check_errors() has already logged the user out
 * if the cookie came without a valid nonce.
 *
 * @param WP_REST_Request $request Request.
 * @return bool
 */
function lq_sec_cookie_session( $request ) {
	if ( true !== ( isset( $GLOBALS['wp_rest_auth_cookie'] ) ? $GLOBALS['wp_rest_auth_cookie'] : null ) ) {
		return false;
	}
	if ( ! empty( $GLOBALS['wp_rest_application_password_uuid'] ) ) {
		return false;
	}
	$nonce = $request->get_header( 'X-WP-Nonce' );
	if ( ! $nonce && isset( $_REQUEST['_wpnonce'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- the nonce is checked here.
		$nonce = sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	}
	return $nonce && false !== wp_verify_nonce( $nonce, 'wp_rest' );
}

/**
 * REST lock: a request that writes (POST, PUT, PATCH, DELETE) as a logged-in
 * user must come from a cookie session with a valid nonce. Basic auth,
 * application passwords and any other header-based login are refused for
 * writes in every namespace. Reads and anonymous requests are unchanged, so
 * public GET output, contact forms and comment forms behave as before. The
 * block editor, Rank Math and the media modal send X-WP-Nonce with cookies
 * and pass.
 *
 * @param mixed           $result  Earlier result.
 * @param WP_REST_Server  $server  Server.
 * @param WP_REST_Request $request Request.
 * @return mixed
 */
function lq_sec_rest_guard( $result, $server, $request ) {
	if ( null !== $result || ! lq_sec_on( 'sec_rest_lock' ) || ! ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
		return $result;
	}
	if ( in_array( strtoupper( $request->get_method() ), array( 'GET', 'HEAD', 'OPTIONS' ), true ) ) {
		return $result;
	}
	$route = (string) $request->get_route();
	/**
	 * Route prefixes that may write with non-cookie authentication, for
	 * example '/jetpack/'. Empty by default.
	 */
	foreach ( (array) apply_filters( 'lq_sec_rest_allow', array() ) as $prefix ) {
		if ( $prefix && 0 === strpos( $route, $prefix ) ) {
			return $result;
		}
	}
	$core = (bool) preg_match( '#^/(wp/v2|batch/v1)(/|$)#', $route ) && 0 !== strpos( $route, '/wp/v2/comments' );
	if ( ! is_user_logged_in() && ! $core ) {
		return $result;
	}
	if ( ! is_user_logged_in() ) {
		// Core refuses anonymous writes to these routes itself; nothing to add.
		return $result;
	}
	if ( lq_sec_cookie_session( $request ) ) {
		return $result;
	}
	lq_sec_log(
		array(
			'event' => 'rest-blocked',
			'title' => $request->get_method() . ' ' . $route,
		)
	);
	return new WP_Error( 'lq_rest_locked', __( 'Changes through the REST API need a logged-in browser session on this site.', 'lailatulqadar' ), array( 'status' => 403 ) );
}
add_filter( 'rest_pre_dispatch', 'lq_sec_rest_guard', 5, 3 );

/* -------------------------------------------------------------------------
 * 1b. Signals from the October 2026 attack
 *
 * The access logs showed the pattern: password guessing through xmlrpc.php
 * from 15 October, sent with forged "Jetpack by WordPress.com" user agents;
 * the first correct password on 20 October; from 22 October a post every 18
 * to 20 minutes, each one a login, a read of the site and a wp.newPost call,
 * every request from a different address. The login page itself was never
 * reached. These checks answer each step.
 * ---------------------------------------------------------------------- */

define( 'LQ_SEC_COUNTS', 'lq_security_counts' );
define( 'LQ_SEC_FAILED', 'lq_security_failed' );

/**
 * Add one to today's counter for an event. Thirty days are kept.
 *
 * @param string $key Event key.
 */
function lq_sec_count( $key ) {
	$counts = get_option( LQ_SEC_COUNTS, array() );
	$counts = is_array( $counts ) ? $counts : array();
	$day    = gmdate( 'Y-m-d' );
	if ( ! isset( $counts[ $day ] ) ) {
		$counts[ $day ] = array();
		krsort( $counts );
		$counts = array_slice( $counts, 0, 30, true );
	}
	$counts[ $day ][ $key ] = ( isset( $counts[ $day ][ $key ] ) ? (int) $counts[ $day ][ $key ] : 0 ) + 1;
	update_option( LQ_SEC_COUNTS, $counts, false );
}

/**
 * Whether the request claims to come from Jetpack or WordPress.com while the
 * Jetpack plugin is not active. Real Jetpack traffic only reaches sites that
 * run Jetpack, so on this site the claim is forged.
 *
 * @return bool
 */
function lq_sec_fake_jetpack() {
	$agent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';
	if ( ! preg_match( '/jetpack|wordpress\.com/i', $agent ) ) {
		return false;
	}
	return ! class_exists( 'Jetpack' ) && ! defined( 'JETPACK__VERSION' );
}

/**
 * Whether this request is a remote, non-browser call: XML-RPC, or REST with
 * an application password or other header login.
 *
 * @return bool
 */
function lq_sec_is_remote() {
	if ( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST ) {
		return true;
	}
	if ( defined( 'REST_REQUEST' ) && REST_REQUEST && is_user_logged_in() ) {
		return true !== ( isset( $GLOBALS['wp_rest_auth_cookie'] ) ? $GLOBALS['wp_rest_auth_cookie'] : null );
	}
	return false;
}

/**
 * Password logins through XML-RPC are refused even when XML-RPC is switched
 * back on (for Jetpack, say). Jetpack signs its own requests and needs no
 * password, so it keeps working.
 *
 * @param WP_User|WP_Error|null $user Result so far.
 * @return WP_User|WP_Error|null
 */
function lq_sec_refuse_remote_password( $user ) {
	if ( ! ( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST ) || ! lq_sec_on( 'sec_xmlrpc_off' ) && ! lq_sec_on( 'sec_rest_lock' ) ) {
		return $user;
	}
	if ( $user instanceof WP_User ) {
		lq_sec_count( 'remote-password' );
		lq_sec_log(
			array(
				'event' => 'remote-password-refused',
				'title' => $user->user_login . ': ' . __( 'the correct password was sent through XML-RPC. Change it now.', 'lailatulqadar' ),
				'user'  => $user->ID,
			)
		);
	}
	return new WP_Error( 'lq_remote_password', __( 'Password logins through XML-RPC are not accepted on this site.', 'lailatulqadar' ) );
}
add_filter( 'authenticate', 'lq_sec_refuse_remote_password', 100 );

/**
 * Count failed logins per account name over a rolling day. The attack came
 * from a different address every time, so counting by address shows nothing;
 * counting by account name shows which account is under attack.
 *
 * @param string $login Account name or email tried.
 */
function lq_sec_failed_login( $login ) {
	lq_sec_count( 'failed-login' );
	$login  = sanitize_user( (string) $login, true );
	$failed = get_option( LQ_SEC_FAILED, array() );
	$failed = is_array( $failed ) ? $failed : array();
	$now    = time();
	foreach ( $failed as $name => $row ) {
		if ( $now - (int) $row['last'] > DAY_IN_SECONDS ) {
			unset( $failed[ $name ] );
		}
	}
	if ( '' === $login ) {
		$login = '(empty)';
	}
	if ( ! isset( $failed[ $login ] ) && count( $failed ) >= 50 ) {
		return;
	}
	$row = isset( $failed[ $login ] ) ? $failed[ $login ] : array( 'n' => 0, 'first' => $now, 'last' => $now, 'ips' => array() );
	$row['n']++;
	$row['last'] = $now;
	$ip          = lq_sec_ip();
	if ( $ip && ! in_array( $ip, $row['ips'], true ) && count( $row['ips'] ) < 1000 ) {
		$row['ips'][] = $ip;
	}
	$failed[ $login ] = $row;
	update_option( LQ_SEC_FAILED, $failed, false );
}
add_action( 'wp_login_failed', 'lq_sec_failed_login' );

/**
 * Admin notice when an existing account is being guessed at: ten or more
 * failures in a day, or a correct password refused on the remote route.
 */
function lq_sec_attack_notice() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$failed = get_option( LQ_SEC_FAILED, array() );
	$hit    = array();
	foreach ( (array) $failed as $name => $row ) {
		if ( (int) $row['n'] >= 10 && ( username_exists( $name ) || email_exists( $name ) ) ) {
			$hit[] = sprintf( '%s (%d, %d)', $name, (int) $row['n'], count( (array) $row['ips'] ) );
		}
	}
	$counts = get_option( LQ_SEC_COUNTS, array() );
	$today  = isset( $counts[ gmdate( 'Y-m-d' ) ]['remote-password'] ) ? (int) $counts[ gmdate( 'Y-m-d' ) ]['remote-password'] : 0;
	if ( ! $hit && ! $today ) {
		return;
	}
	$url = lq_sec_url();
	echo '<div class="notice notice-error"><p><strong>' . esc_html__( 'Security:', 'lailatulqadar' ) . '</strong> ';
	if ( $today ) {
		esc_html_e( 'Someone sent a correct password through XML-RPC today. The login was refused, but the password is known: change it now.', 'lailatulqadar' );
		echo ' ';
	}
	if ( $hit ) {
		/* translators: %s: account names with failure and address counts. */
		echo esc_html( sprintf( __( 'Passwords are being guessed for these accounts in the last day (failures, addresses): %s.', 'lailatulqadar' ), implode( ', ', $hit ) ) ) . ' ';
	}
	echo '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Open the Security tab', 'lailatulqadar' ) . '</a></p></div>';
}
add_action( 'admin_notices', 'lq_sec_attack_notice' );

/* -------------------------------------------------------------------------
 * 2. Spam gate
 * ---------------------------------------------------------------------- */

/**
 * How this request reached WordPress.
 *
 * @return string
 */
function lq_sec_request_type() {
	if ( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST ) {
		return 'XML-RPC';
	}
	if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
		return ! empty( $GLOBALS['wp_rest_application_password_uuid'] ) ? 'REST (application password)' : 'REST';
	}
	if ( defined( 'WP_CLI' ) && WP_CLI ) {
		return 'WP-CLI';
	}
	if ( wp_doing_cron() ) {
		return 'cron';
	}
	if ( wp_doing_ajax() ) {
		return 'admin AJAX';
	}
	return is_admin() ? 'admin' : 'front end';
}

/**
 * Visitor IP address, as the web server saw it.
 *
 * @return string
 */
function lq_sec_ip() {
	$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
	return filter_var( $ip, FILTER_VALIDATE_IP ) ? $ip : '';
}

/**
 * Add a line to the capped log.
 *
 * @param array $entry Fields: event, title, post_id, reasons.
 */
function lq_sec_log( $entry ) {
	$log   = get_option( LQ_SEC_LOG, array() );
	$log   = is_array( $log ) ? $log : array();
	$log[] = array_merge(
		array(
			'time'    => time(),
			'event'   => '',
			'title'   => '',
			'post_id' => 0,
			'reasons' => array(),
			'user'    => get_current_user_id(),
			'type'    => lq_sec_request_type(),
			'ip'      => lq_sec_ip(),
		),
		$entry
	);
	update_option( LQ_SEC_LOG, array_slice( $log, -LQ_SEC_LOG_MAX ), false );
}

/**
 * Whether a post is content the theme seeds itself. Setup marks every guide
 * page it creates or adopts with _lq_lang and _lq_key, and every page it fills
 * with _lq_seed_hash. A page not yet saved (setup creating it) is matched by
 * its title and slug in lq_page_definitions().
 *
 * @param int    $post_id Post ID, 0 for a new post.
 * @param string $title   Title.
 * @param string $slug    Slug.
 * @return bool
 */
function lq_sec_is_seeded( $post_id, $title = '', $slug = '' ) {
	if ( $post_id ) {
		foreach ( array( '_lq_seed_hash', '_lq_key', '_lq_lang' ) as $meta ) {
			if ( '' !== (string) get_post_meta( $post_id, $meta, true ) ) {
				return true;
			}
		}
	}
	static $index = null;
	if ( null === $index ) {
		$index = array();
		if ( function_exists( 'lq_page_definitions' ) ) {
			foreach ( lq_page_definitions() as $def ) {
				foreach ( array( 'primary', 'secondary' ) as $role ) {
					if ( isset( $def[ $role ]['title'], $def[ $role ]['slug'] ) ) {
						$index[ $def[ $role ]['title'] . '|' . $def[ $role ]['slug'] ] = true;
					}
				}
			}
		}
	}
	return isset( $index[ $title . '|' . $slug ] );
}

/**
 * Post types the gate and the scan look at. Menus, attachments, templates and
 * blocks are never touched.
 *
 * @return string[]
 */
function lq_sec_types() {
	return array( 'post', 'page' );
}

/**
 * Hold suspected spam: any post going public whose title, slug or content
 * scores as gambling spam is saved as Pending instead.
 *
 * @param array $data    Sanitised post data.
 * @param array $postarr Raw post data.
 * @return array
 */
function lq_sec_gate( $data, $postarr ) {
	if ( ! lq_sec_on( 'sec_spam_gate' ) || ! in_array( $data['post_type'], lq_sec_types(), true ) ) {
		return $data;
	}
	$public = in_array( $data['post_status'], array( 'publish', 'future', 'private' ), true );
	$id     = isset( $postarr['ID'] ) ? (int) $postarr['ID'] : 0;
	$title  = wp_unslash( $data['post_title'] );
	$slug   = $data['post_name'];
	if ( lq_sec_is_seeded( $id, $title, $slug ) ) {
		return $data;
	}
	$verdict = lq_spam_score( $title, $slug, wp_unslash( $data['post_content'] ) );
	if ( ! $verdict['spam'] && $public && lq_sec_is_remote() ) {
		// The spam arrived through wp.newPost: anything published from
		// outside the editor waits for review, whatever it says.
		$verdict = array( 'spam' => true, 'reasons' => array( __( 'published from outside the editor', 'lailatulqadar' ) . ' (' . lq_sec_request_type() . ')' ) );
	}
	if ( ! $verdict['spam'] ) {
		return $data;
	}
	if ( $public ) {
		$data['post_status'] = 'pending';
	}
	$GLOBALS['lq_sec_held'] = array(
		'event'   => $public ? 'held' : 'flagged-draft',
		'title'   => $title,
		'reasons' => $verdict['reasons'],
	);
	return $data;
}
add_filter( 'wp_insert_post_data', 'lq_sec_gate', 99, 2 );

/**
 * Log what the gate held, now that the post has an ID.
 *
 * @param int $post_id Post ID.
 */
function lq_sec_gate_log( $post_id ) {
	if ( empty( $GLOBALS['lq_sec_held'] ) ) {
		return;
	}
	$entry                   = $GLOBALS['lq_sec_held'];
	$GLOBALS['lq_sec_held'] = null;
	$entry['post_id']        = (int) $post_id;
	update_post_meta( $post_id, '_lq_spam_held', time() );
	lq_sec_log( $entry );
}
add_action( 'wp_insert_post', 'lq_sec_gate_log', 1 );

/**
 * Keep held posts away from search engines while they wait: Rank Math and the
 * core sitemap only list published items, and a held post is never published,
 * so Instant Indexing has nothing to submit.
 */

/* -------------------------------------------------------------------------
 * 3. 410 Gone for removed spam
 * ---------------------------------------------------------------------- */

/**
 * Path of a URL, without slashes at either end.
 *
 * @param string $url URL.
 * @return string
 */
function lq_sec_path( $url ) {
	$path = (string) wp_parse_url( $url, PHP_URL_PATH );
	return trim( strtolower( rawurldecode( $path ) ), '/' );
}

/**
 * Remember the address of a post moved to the trash as spam.
 *
 * @param WP_Post $post Post.
 */
function lq_sec_remember_gone( $post ) {
	$gone = get_option( LQ_SEC_GONE, array() );
	$gone = is_array( $gone ) ? $gone : array();
	foreach ( array( get_permalink( $post ), home_url( '/?p=' . $post->ID ) ) as $url ) {
		$path = lq_sec_path( $url );
		if ( '' !== $path ) {
			$gone[ $path ] = (int) $post->ID;
		}
	}
	if ( $post->post_name ) {
		$gone[ strtolower( rawurldecode( $post->post_name ) ) ] = (int) $post->ID;
	}
	update_option( LQ_SEC_GONE, array_slice( $gone, -LQ_SEC_GONE_MAX, null, true ), false );
}

/**
 * Answer 410 Gone, in place of 404, on the address of removed spam. A post
 * restored from the trash and published again is served as normal, since the
 * request is then no longer a 404.
 */
function lq_sec_send_gone() {
	if ( ! is_404() || ! lq_sec_on( 'sec_gone' ) ) {
		return;
	}
	$gone = get_option( LQ_SEC_GONE, array() );
	if ( ! $gone || ! is_array( $gone ) ) {
		return;
	}
	$uri  = isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
	$path = lq_sec_path( $uri );
	$last = basename( $path );
	if ( isset( $gone[ $path ] ) || ( '' !== $last && isset( $gone[ $last ] ) && false === strpos( $path, '/' ) ) ) {
		status_header( 410 );
		nocache_headers();
		header( 'X-Robots-Tag: noindex', true );
	}
}
add_action( 'template_redirect', 'lq_sec_send_gone', 0 );

/* -------------------------------------------------------------------------
 * 4. Login record for the report
 * ---------------------------------------------------------------------- */

/**
 * Last login time and IP, kept from this version on.
 *
 * @param string  $login Login name.
 * @param WP_User $user  User.
 */
function lq_sec_record_login( $login, $user ) {
	update_user_meta( $user->ID, '_lq_last_login', array( 'time' => time(), 'ip' => lq_sec_ip() ) );
}
add_action( 'wp_login', 'lq_sec_record_login', 10, 2 );

/* -------------------------------------------------------------------------
 * 5. Admin actions
 * ---------------------------------------------------------------------- */

/**
 * Stop unless the user may manage options and the nonce is valid.
 *
 * @param string $action Nonce action.
 */
function lq_sec_check( $action ) {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to do that.', 'lailatulqadar' ), 403 );
	}
	check_admin_referer( $action );
}

/**
 * Back to the Security tab with a notice.
 *
 * @param string $notice Notice code.
 * @param int    $count  Count.
 */
function lq_sec_back( $notice, $count = 0 ) {
	wp_safe_redirect( lq_sec_url( array( 'lq-sec' => $notice, 'lq-count' => (int) $count ) ) );
	exit;
}

/**
 * Revoke every application password of every user.
 */
function lq_sec_handle_revoke() {
	lq_sec_check( 'lq_sec_revoke' );
	$count = 0;
	if ( class_exists( 'WP_Application_Passwords' ) ) {
		foreach ( get_users( array( 'fields' => 'ID' ) ) as $user_id ) {
			$deleted = WP_Application_Passwords::delete_all_application_passwords( (int) $user_id );
			if ( ! is_wp_error( $deleted ) ) {
				$count += (int) $deleted;
			}
		}
	}
	lq_sec_log( array( 'event' => 'app-passwords-revoked', 'title' => (string) $count ) );
	lq_sec_back( 'revoked', $count );
}
add_action( 'admin_post_lq_sec_revoke', 'lq_sec_handle_revoke' );

/**
 * End every login session of every user, this one included.
 */
function lq_sec_handle_logout() {
	lq_sec_check( 'lq_sec_logout' );
	lq_sec_log( array( 'event' => 'sessions-ended', 'title' => '' ) );
	foreach ( get_users( array( 'fields' => 'ID' ) ) as $user_id ) {
		WP_Session_Tokens::get_instance( (int) $user_id )->destroy_all();
	}
	wp_clear_auth_cookie();
	wp_safe_redirect( wp_login_url( lq_sec_url() ) );
	exit;
}
add_action( 'admin_post_lq_sec_logout', 'lq_sec_handle_logout' );

/**
 * Category slugs the theme seeds. Lailatulqadar seeds pages only, never
 * categories, so the list is empty.
 *
 * @return string[]
 */
function lq_sec_seeded_terms() {
	return array();
}

/**
 * Categories spam created: a spam-scoring name or one of the names seen in the
 * incident, never a seeded or default category, and only when nothing but
 * trashed posts uses it.
 *
 * @return WP_Term[]
 */
function lq_sec_spam_categories() {
	$known   = array( 'casino', 'casinos', 'review', 'reviews', 'betting', 'gambling', 'bonus', 'slots', 'sportsbook', 'kasyno', 'kazino' );
	$seeded  = lq_sec_seeded_terms();
	$default = (int) get_option( 'default_category' );
	$out     = array();
	foreach ( get_terms( array( 'taxonomy' => 'category', 'hide_empty' => false ) ) as $term ) {
		if ( (int) $term->term_id === $default || in_array( $term->slug, $seeded, true ) || 'uncategorized' === $term->slug ) {
			continue;
		}
		$verdict = lq_spam_score( $term->name, $term->slug, '' );
		if ( ! in_array( $term->slug, $known, true ) && ! $verdict['spam'] ) {
			continue;
		}
		$used = get_posts(
			array(
				'post_type'      => 'any',
				'post_status'    => array( 'publish', 'pending', 'draft', 'future', 'private' ),
				'category'       => $term->term_id,
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'no_found_rows'  => true,
			)
		);
		$term->lq_in_use = ! empty( $used );
		$out[]            = $term;
	}
	return $out;
}

/**
 * Delete spam categories that are empty.
 */
function lq_sec_handle_delete_categories() {
	lq_sec_check( 'lq_sec_categories' );
	$count = 0;
	foreach ( lq_sec_spam_categories() as $term ) {
		if ( ! $term->lq_in_use && ! is_wp_error( wp_delete_term( $term->term_id, 'category' ) ) ) {
			++$count;
		}
	}
	lq_sec_log( array( 'event' => 'categories-deleted', 'title' => (string) $count ) );
	lq_sec_back( 'categories', $count );
}
add_action( 'admin_post_lq_sec_categories', 'lq_sec_handle_delete_categories' );

/**
 * Clear the gate log.
 */
function lq_sec_handle_clear_log() {
	lq_sec_check( 'lq_sec_clear_log' );
	delete_option( LQ_SEC_LOG );
	lq_sec_back( 'log-cleared' );
}
add_action( 'admin_post_lq_sec_clear_log', 'lq_sec_handle_clear_log' );

/**
 * AJAX: one batch of the scan.
 */
function lq_sec_ajax_scan() {
	check_ajax_referer( 'lq_sec_ajax', 'nonce' );
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => __( 'You do not have permission to do that.', 'lailatulqadar' ) ), 403 );
	}
	$offset = isset( $_POST['offset'] ) ? absint( $_POST['offset'] ) : 0;
	$batch  = 100;
	$query  = new WP_Query(
		array(
			'post_type'              => lq_sec_types(),
			'post_status'            => array( 'publish', 'pending', 'draft', 'future', 'private' ),
			'posts_per_page'         => $batch,
			'offset'                 => $offset,
			'orderby'                => 'ID',
			'order'                  => 'DESC',
			'no_found_rows'          => false,
			'update_post_term_cache' => false,
			'suppress_filters'       => true,
		)
	);
	$rows = array();
	foreach ( $query->posts as $post ) {
		if ( lq_sec_is_seeded( $post->ID, $post->post_title, $post->post_name ) ) {
			continue;
		}
		$verdict = lq_spam_score( $post->post_title, $post->post_name, $post->post_content );
		if ( ! $verdict['spam'] ) {
			continue;
		}
		$author = get_userdata( (int) $post->post_author );
		$rows[] = array(
			'id'      => (int) $post->ID,
			'title'   => '' !== $post->post_title ? wp_strip_all_tags( $post->post_title ) : __( '(no title)', 'lailatulqadar' ),
			'date'    => get_the_date( 'Y-m-d H:i', $post ),
			'status'  => $post->post_status,
			'author'  => $author ? $author->user_login : '',
			'reasons' => $verdict['reasons'],
			'edit'    => (string) get_edit_post_link( $post->ID, 'raw' ),
			'view'    => 'publish' === $post->post_status ? (string) get_permalink( $post ) : '',
		);
	}
	$next = $offset + count( $query->posts );
	wp_send_json_success(
		array(
			'rows'  => $rows,
			'next'  => $next,
			'total' => (int) $query->found_posts,
			'done'  => count( $query->posts ) < $batch,
		)
	);
}
add_action( 'wp_ajax_lq_sec_scan', 'lq_sec_ajax_scan' );

/**
 * AJAX: move a batch of posts to the trash. Each one is scored again here, so
 * only posts that still read as spam and are not starter content go.
 */
function lq_sec_ajax_trash() {
	check_ajax_referer( 'lq_sec_ajax', 'nonce' );
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => __( 'You do not have permission to do that.', 'lailatulqadar' ) ), 403 );
	}
	$ids     = isset( $_POST['ids'] ) ? array_slice( array_map( 'absint', (array) wp_unslash( $_POST['ids'] ) ), 0, 100 ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- absint on each.
	$trashed = array();
	$skipped = array();
	foreach ( array_filter( $ids ) as $id ) {
		$post = get_post( $id );
		if ( ! $post || ! in_array( $post->post_type, lq_sec_types(), true ) || 'trash' === $post->post_status || ! current_user_can( 'delete_post', $id ) || lq_sec_is_seeded( $id, $post->post_title, $post->post_name ) ) {
			$skipped[] = $id;
			continue;
		}
		$verdict = lq_spam_score( $post->post_title, $post->post_name, $post->post_content );
		if ( ! $verdict['spam'] ) {
			$skipped[] = $id;
			continue;
		}
		if ( 'publish' === $post->post_status ) {
			lq_sec_remember_gone( $post );
		}
		if ( wp_trash_post( $id ) ) {
			$trashed[] = $id;
		} else {
			$skipped[] = $id;
		}
	}
	if ( $trashed ) {
		lq_sec_log( array( 'event' => 'trashed', 'title' => (string) count( $trashed ) ) );
	}
	wp_send_json_success( array( 'trashed' => $trashed, 'skipped' => $skipped ) );
}
add_action( 'wp_ajax_lq_sec_trash', 'lq_sec_ajax_trash' );

/* -------------------------------------------------------------------------
 * 6. Screen
 * ---------------------------------------------------------------------- */

/**
 * Assets for the Security tab, enqueued by lq_admin_assets() on that tab.
 */
function lq_sec_assets() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	wp_enqueue_style( 'lailatulqadar-security', LQ_URI . '/css/security.css', array( 'lailatulqadar-admin' ), LQ_VERSION );
	wp_enqueue_script( 'lailatulqadar-security', LQ_URI . '/js/security.js', array(), LQ_VERSION, true );
	wp_localize_script(
		'lailatulqadar-security',
		'lqSecurity',
		array(
			'ajax'    => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'lq_sec_ajax' ),
			'i18n'    => array(
				'scanning'     => __( 'Scanning: %1$d of %2$d checked, %3$d found.', 'lailatulqadar' ),
				'done'         => __( 'Scan finished: %1$d checked, %2$d found.', 'lailatulqadar' ),
				'none'         => __( 'No suspected spam found.', 'lailatulqadar' ),
				'trashing'     => __( 'Moving to the trash: %1$d of %2$d.', 'lailatulqadar' ),
				'trashed'      => __( '%1$d moved to the trash, %2$d skipped. Posts > Trash can restore them.', 'lailatulqadar' ),
				'confirmTrash' => __( 'Move the selected posts to the trash? They can be restored from Posts > Trash.', 'lailatulqadar' ),
				'error'        => __( 'The request failed. Reload the page and try again.', 'lailatulqadar' ),
				'edit'         => __( 'Edit', 'lailatulqadar' ),
				'view'         => __( 'View', 'lailatulqadar' ),
				'select'       => __( 'Select', 'lailatulqadar' ),
			),
		)
	);
}

/**
 * Notices after Security actions.
 */
function lq_sec_notice() {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- display only.
	$code = isset( $_GET['lq-sec'] ) ? sanitize_key( wp_unslash( $_GET['lq-sec'] ) ) : '';
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- display only.
	$n    = isset( $_GET['lq-count'] ) ? absint( $_GET['lq-count'] ) : 0;
	$map  = array(
		/* translators: %d: number of application passwords. */
		'revoked'     => sprintf( __( 'Application passwords revoked: %d.', 'lailatulqadar' ), $n ),
		/* translators: %d: number of categories. */
		'categories'  => sprintf( __( 'Empty spam categories deleted: %d.', 'lailatulqadar' ), $n ),
		'log-cleared' => __( 'Security log cleared.', 'lailatulqadar' ),
	);
	if ( isset( $map[ $code ] ) ) {
		printf( '<div class="lq-callout lq-callout--ok"><p>%s</p></div>', esc_html( $map[ $code ] ) );
	}
}

/**
 * One Security switch, in the theme's checkbox markup.
 *
 * @param string $key   Setting key.
 * @param string $label Label.
 * @param string $help  Help text.
 */
function lq_sec_checkbox( $key, $label, $help ) {
	?>
	<div class="lq-field lq-field--check">
		<input type="hidden" name="<?php echo esc_attr( lq_field_name( $key ) ); ?>" value="0">
		<label><input type="checkbox" name="<?php echo esc_attr( lq_field_name( $key ) ); ?>" value="1" <?php checked( lq_setting( $key ), '1' ); ?>> <?php echo esc_html( $label ); ?></label>
	</div>
	<p class="lq-help"><?php echo esc_html( $help ); ?></p>
	<?php
}

/**
 * Security tab: the switches, shown inside the settings form.
 */
function lq_render_security_tab() {
	?>
	<section class="lq-section">
		<h2><?php esc_html_e( 'Remote publishing', 'lailatulqadar' ); ?></h2>
		<p class="lq-help"><?php esc_html_e( 'Spam tools publish from outside the site with a stored password. These switches close the doors they use. Writing in the editor is not affected, and visitors see no change.', 'lailatulqadar' ); ?></p>
		<?php
		lq_sec_checkbox( 'sec_xmlrpc_off', __( 'Turn off XML-RPC', 'lailatulqadar' ), __( 'xmlrpc.php answers 403 and the pingback header goes. On Apache the theme also blocks it in .htaccess, and removes that block when this is switched off. The Jetpack plugin and old mobile apps need XML-RPC.', 'lailatulqadar' ) );
		$state = get_option( 'lq_security_htaccess', '' );
		if ( 'written' === $state ) {
			echo '<p class="lq-callout lq-callout--ok">' . esc_html__( 'The web server also refuses xmlrpc.php: the theme keeps a "Block xmlrpc.php" section in .htaccess and repairs it if it goes missing.', 'lailatulqadar' ) . '</p>';
		} elseif ( 'unavailable' === $state ) {
			echo '<p class="lq-callout lq-callout--warn">' . esc_html__( 'The theme could not write .htaccess (the server is not Apache, or the file is read-only). WordPress still refuses xmlrpc.php; ask the host to deny it at the server as well.', 'lailatulqadar' ) . '</p>';
		}
		lq_sec_checkbox( 'sec_app_passwords_off', __( 'Turn off application passwords', 'lailatulqadar' ), __( 'Stored application passwords stop working at once. Revoke them as well, below, so they cannot return if this is switched off.', 'lailatulqadar' ) );
		lq_sec_checkbox( 'sec_rest_lock', __( 'Allow REST API changes only from a logged-in browser', 'lailatulqadar' ), __( 'Any request that creates, changes or deletes content while logged in must come from a browser session on this site. Basic authentication and passwords sent in a header are refused. Reading through the API, and anything visitors send without logging in, is unchanged.', 'lailatulqadar' ) );
		?>
	</section>

	<section class="lq-section">
		<h2><?php esc_html_e( 'New content', 'lailatulqadar' ); ?></h2>
		<?php
		lq_sec_checkbox( 'sec_spam_gate', __( 'Hold suspected gambling spam as Pending', 'lailatulqadar' ), __( 'A post or page whose title, address or text reads as casino or betting spam, or that is published from outside the editor, is saved as Pending, never published, and logged below. The guide pages the theme ships are exempt.', 'lailatulqadar' ) );
		lq_sec_checkbox( 'sec_gone', __( 'Answer 410 Gone on the address of removed spam', 'lailatulqadar' ), __( 'Search engines drop a 410 address faster than a 404 one. Only addresses of spam moved to the trash from this tab are affected.', 'lailatulqadar' ) );
		if ( defined( 'LQ_SECURITY_LOCK' ) && ! LQ_SECURITY_LOCK ) {
			echo '<p class="lq-callout lq-callout--warn">' . esc_html__( 'LQ_SECURITY_LOCK is false in wp-config.php, so the three remote-publishing switches are off whatever they show.', 'lailatulqadar' ) . '</p>';
		}
		?>
	</section>
	<?php
}

/**
 * Small action form posting to admin-post.php.
 *
 * @param string $action  Action and nonce name.
 * @param string $label   Button label.
 * @param string $confirm Confirmation text.
 * @param string $class   Button class.
 */
function lq_sec_button( $action, $label, $confirm, $class = 'secondary' ) {
	?>
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="lq-sec-form" data-lq-sec-confirm="<?php echo esc_attr( $confirm ); ?>">
		<input type="hidden" name="action" value="<?php echo esc_attr( $action ); ?>">
		<?php wp_nonce_field( $action ); ?>
		<?php submit_button( $label, $class, 'submit', false ); ?>
	</form>
	<?php
}

/**
 * Administrators with last login and application passwords.
 *
 * @return array
 */
function lq_sec_admin_rows() {
	$rows = array();
	foreach ( get_users( array( 'role' => 'administrator' ) ) as $user ) {
		$last     = get_user_meta( $user->ID, '_lq_last_login', true );
		$sessions = get_user_meta( $user->ID, 'session_tokens', true );
		$time     = is_array( $last ) && ! empty( $last['time'] ) ? (int) $last['time'] : 0;
		$ip       = is_array( $last ) && ! empty( $last['ip'] ) ? $last['ip'] : '';
		if ( is_array( $sessions ) ) {
			foreach ( $sessions as $session ) {
				if ( ! empty( $session['login'] ) && (int) $session['login'] > $time ) {
					$time = (int) $session['login'];
					$ip   = isset( $session['ip'] ) ? $session['ip'] : $ip;
				}
			}
		}
		$apps = class_exists( 'WP_Application_Passwords' ) ? WP_Application_Passwords::get_user_application_passwords( $user->ID ) : array();
		$used = 0;
		foreach ( $apps as $app ) {
			$used = max( $used, (int) $app['last_used'] );
		}
		$rows[] = array(
			'user'     => $user,
			'login'    => $time,
			'ip'       => $ip,
			'sessions' => is_array( $sessions ) ? count( $sessions ) : 0,
			'apps'     => $apps,
			'app_used' => $used,
		);
	}
	return $rows;
}

/**
 * Posts per day and author over the last 30 days, and hours with bursts.
 *
 * @return array { days: array date => array login => count, bursts: array }
 */
function lq_sec_activity() {
	global $wpdb;
	$since = gmdate( 'Y-m-d H:i:s', time() - 30 * DAY_IN_SECONDS );
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery -- report query, read only.
	$rows = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT post_author, DATE(post_date) AS d, HOUR(post_date) AS h, COUNT(*) AS n FROM {$wpdb->posts} WHERE post_type = 'post' AND post_status NOT IN ('auto-draft','inherit') AND post_date_gmt >= %s GROUP BY post_author, d, h",
			$since
		)
	);
	$days   = array();
	$bursts = array();
	foreach ( (array) $rows as $row ) {
		$user  = get_userdata( (int) $row->post_author );
		$login = $user ? $user->user_login : '#' . (int) $row->post_author;
		$days[ $row->d ][ $login ] = ( isset( $days[ $row->d ][ $login ] ) ? $days[ $row->d ][ $login ] : 0 ) + (int) $row->n;
		if ( (int) $row->n > 2 ) {
			$bursts[] = array( 'login' => $login, 'when' => sprintf( '%s %02d:00', $row->d, (int) $row->h ), 'n' => (int) $row->n );
		}
	}
	krsort( $days );
	return array( 'days' => $days, 'bursts' => $bursts );
}

/**
 * Starter items whose content now carries gambling links.
 *
 * @return WP_Post[]
 */
function lq_sec_tainted_seeds() {
	$out   = array();
	$posts = get_posts(
		array(
			'post_type'      => array( 'post', 'page' ),
			'post_status'    => 'any',
			'posts_per_page' => 500,
			'meta_key'       => '_lq_lang', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_query_meta_key
			'no_found_rows'  => true,
		)
	);
	foreach ( $posts as $post ) {
		$hosts = lq_spam_links( $post->post_content );
		if ( $hosts ) {
			$post->lq_hosts = $hosts;
			$out[]           = $post;
		}
	}
	return $out;
}

/**
 * Whether Rank Math Instant Indexing is on.
 *
 * @return bool
 */
function lq_sec_instant_indexing() {
	return class_exists( '\RankMath\Helper' ) && method_exists( '\RankMath\Helper', 'is_module_active' ) && \RankMath\Helper::is_module_active( 'instant-indexing' );
}

/**
 * Tools and reports, shown under the form while the Security tab is open.
 */
function lq_sec_render_tools() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$format = get_option( 'date_format' ) . ' ' . get_option( 'time_format' );
	$admins = lq_sec_admin_rows();
	$total  = 0;
	foreach ( $admins as $a ) {
		$total += count( $a['apps'] );
	}
	?>
	<section class="lq-section lq-sec" id="lq-security-tools">
		<?php lq_sec_notice(); ?>

		<h2><?php esc_html_e( 'Clean up spam', 'lailatulqadar' ); ?></h2>
		<p class="lq-help"><?php esc_html_e( 'The scan reads every post and page that is published, pending, scheduled, private or a draft, 100 at a time, and lists those that read as gambling spam. The guide pages the theme ships are never listed. Nothing changes until you move posts to the trash, and the trash can restore them.', 'lailatulqadar' ); ?></p>
		<div class="lq-sec-bar">
			<button type="button" class="button button-primary" id="lq-sec-scan"><?php esc_html_e( 'Scan for spam', 'lailatulqadar' ); ?></button>
			<button type="button" class="button" id="lq-sec-trash" disabled><?php esc_html_e( 'Move selected to Trash', 'lailatulqadar' ); ?></button>
			<span class="lq-sec-status" id="lq-sec-status" role="status" aria-live="polite"></span>
		</div>
		<div class="lq-sec-progress" hidden><span id="lq-sec-progress"></span></div>
		<div class="lq-sec-scroll">
			<table class="widefat striped lq-sec-table" id="lq-sec-results" hidden>
				<thead>
					<tr>
						<td class="check-column"><input type="checkbox" id="lq-sec-all" aria-label="<?php esc_attr_e( 'Select all', 'lailatulqadar' ); ?>"></td>
						<th scope="col"><?php esc_html_e( 'Title', 'lailatulqadar' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Date', 'lailatulqadar' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Status', 'lailatulqadar' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Author', 'lailatulqadar' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Reasons', 'lailatulqadar' ); ?></th>
					</tr>
				</thead>
				<tbody></tbody>
			</table>
		</div>
		<p class="lq-help">
			<?php
			esc_html_e( 'A trashed post answers 404, or 410 Gone when that switch is on. Resubmit the sitemap in Google Search Console afterwards.', 'lailatulqadar' );
			echo ' ';
			echo lq_sec_instant_indexing()
				? esc_html__( 'Rank Math Instant Indexing is on. Held spam is never published, so it is never submitted.', 'lailatulqadar' )
				: esc_html__( 'Rank Math Instant Indexing is off.', 'lailatulqadar' );
			?>
		</p>

		<h2><?php esc_html_e( 'Spam categories', 'lailatulqadar' ); ?></h2>
		<?php $cats = lq_sec_spam_categories(); ?>
		<?php if ( $cats ) : ?>
			<ul class="lq-sec-list">
				<?php foreach ( $cats as $term ) : ?>
					<li>
						<?php
						echo esc_html( $term->name . ' (' . $term->slug . ')' );
						echo ' ';
						echo $term->lq_in_use ? esc_html__( 'still used by posts outside the trash, kept', 'lailatulqadar' ) : esc_html__( 'empty, can be deleted', 'lailatulqadar' );
						?>
					</li>
				<?php endforeach; ?>
			</ul>
			<?php lq_sec_button( 'lq_sec_categories', __( 'Delete empty spam categories', 'lailatulqadar' ), __( 'Delete the empty spam categories listed? Categories the theme seeds are never deleted.', 'lailatulqadar' ) ); ?>
		<?php else : ?>
			<p class="lq-help"><?php esc_html_e( 'No spam categories found.', 'lailatulqadar' ); ?></p>
		<?php endif; ?>

		<h2><?php esc_html_e( 'Passwords and sessions', 'lailatulqadar' ); ?></h2>
		<p class="lq-help">
			<?php
			/* translators: %d: number of application passwords. */
			echo esc_html( sprintf( __( 'Application passwords stored on administrator accounts: %d.', 'lailatulqadar' ), $total ) );
			?>
		</p>
		<div class="lq-sec-bar">
			<?php
			lq_sec_button( 'lq_sec_revoke', __( 'Revoke all application passwords', 'lailatulqadar' ), __( 'Revoke every application password of every user? Tools that use them stop working.', 'lailatulqadar' ), 'delete' );
			lq_sec_button( 'lq_sec_logout', __( 'Log out all sessions', 'lailatulqadar' ), __( 'End every login session of every user, yours included? You will need to log in again.', 'lailatulqadar' ), 'delete' );
			?>
		</div>

		<h3><?php esc_html_e( 'Administrator accounts', 'lailatulqadar' ); ?></h3>
		<div class="lq-sec-scroll">
			<table class="widefat striped lq-sec-table">
				<thead><tr><th scope="col"><?php esc_html_e( 'Account', 'lailatulqadar' ); ?></th><th scope="col"><?php esc_html_e( 'Last login', 'lailatulqadar' ); ?></th><th scope="col"><?php esc_html_e( 'Sessions', 'lailatulqadar' ); ?></th><th scope="col"><?php esc_html_e( 'Application passwords', 'lailatulqadar' ); ?></th></tr></thead>
				<tbody>
					<?php foreach ( $admins as $a ) : ?>
						<tr>
							<td><a href="<?php echo esc_url( get_edit_user_link( $a['user']->ID ) ); ?>"><?php echo esc_html( $a['user']->user_login ); ?></a><br><span class="lq-help"><?php echo esc_html( $a['user']->user_email ); ?></span></td>
							<td><?php echo $a['login'] ? esc_html( wp_date( $format, $a['login'] ) . ( $a['ip'] ? ' (' . $a['ip'] . ')' : '' ) ) : esc_html__( 'Not recorded', 'lailatulqadar' ); ?></td>
							<td><?php echo esc_html( (string) $a['sessions'] ); ?></td>
							<td>
								<?php
								echo esc_html( (string) count( $a['apps'] ) );
								foreach ( $a['apps'] as $app ) {
									echo '<br><span class="lq-help">' . esc_html( $app['name'] . ': ' . ( $app['last_used'] ? wp_date( $format, (int) $app['last_used'] ) . ( $app['last_ip'] ? ' (' . $app['last_ip'] . ')' : '' ) : __( 'never used', 'lailatulqadar' ) ) ) . '</span>';
								}
								?>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>

		<h2><?php esc_html_e( 'Attacks seen', 'lailatulqadar' ); ?></h2>
		<?php
		$counts = get_option( LQ_SEC_COUNTS, array() );
		$counts = is_array( $counts ) ? $counts : array();
		krsort( $counts );
		$labels = array(
			'xmlrpc'          => __( 'XML-RPC requests refused', 'lailatulqadar' ),
			'fake-jetpack'    => __( 'of them posing as Jetpack', 'lailatulqadar' ),
			'remote-password' => __( 'correct passwords refused on XML-RPC', 'lailatulqadar' ),
			'failed-login'    => __( 'failed logins', 'lailatulqadar' ),
		);
		?>
		<p class="lq-help"><?php esc_html_e( 'Counted per day, in UTC, for the last 30 days. A correct password refused on XML-RPC means the password is known to someone else: change it.', 'lailatulqadar' ); ?></p>
		<?php if ( $counts ) : ?>
			<div class="lq-sec-scroll">
				<table class="widefat striped lq-sec-table">
					<thead><tr><th scope="col"><?php esc_html_e( 'Day', 'lailatulqadar' ); ?></th><?php foreach ( $labels as $label ) : ?><th scope="col"><?php echo esc_html( $label ); ?></th><?php endforeach; ?></tr></thead>
					<tbody>
						<?php foreach ( $counts as $day => $row ) : ?>
							<tr><td><?php echo esc_html( $day ); ?></td><?php foreach ( array_keys( $labels ) as $key ) : ?><td><?php echo esc_html( (string) ( isset( $row[ $key ] ) ? (int) $row[ $key ] : 0 ) ); ?></td><?php endforeach; ?></tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		<?php else : ?>
			<p class="lq-help"><?php esc_html_e( 'Nothing counted yet.', 'lailatulqadar' ); ?></p>
		<?php endif; ?>
		<h3><?php esc_html_e( 'Accounts being guessed, last 24 hours', 'lailatulqadar' ); ?></h3>
		<p class="lq-help"><?php esc_html_e( 'Attackers change address on every try, so failures are counted by account name. An account of yours in this list should get a new, long password, and ideally a new name that is not the site name.', 'lailatulqadar' ); ?></p>
		<?php
		$failed = get_option( LQ_SEC_FAILED, array() );
		$failed = is_array( $failed ) ? $failed : array();
		uasort(
			$failed,
			function ( $x, $y ) {
				return (int) $y['n'] - (int) $x['n'];
			}
		);
		?>
		<?php if ( $failed ) : ?>
			<ul class="lq-sec-list">
				<?php foreach ( array_slice( $failed, 0, 20, true ) as $name => $row ) : ?>
					<li>
						<?php
						$real = username_exists( $name ) || email_exists( $name );
						/* translators: 1: account name, 2: failures, 3: addresses, 4: whether the account exists. */
						echo esc_html( sprintf( __( '%1$s: %2$d failures from %3$d addresses%4$s', 'lailatulqadar' ), $name, (int) $row['n'], count( (array) $row['ips'] ), $real ? __( ', a real account on this site', 'lailatulqadar' ) : '' ) );
						?>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php else : ?>
			<p class="lq-help"><?php esc_html_e( 'None.', 'lailatulqadar' ); ?></p>
		<?php endif; ?>

		<h2><?php esc_html_e( 'Activity', 'lailatulqadar' ); ?></h2>
		<?php $activity = lq_sec_activity(); ?>
		<h3><?php esc_html_e( 'Posts per day, last 30 days', 'lailatulqadar' ); ?></h3>
		<?php if ( $activity['days'] ) : ?>
			<div class="lq-sec-scroll">
				<table class="widefat striped lq-sec-table lq-sec-days">
					<thead><tr><th scope="col"><?php esc_html_e( 'Day', 'lailatulqadar' ); ?></th><th scope="col"><?php esc_html_e( 'Account and count', 'lailatulqadar' ); ?></th></tr></thead>
					<tbody>
						<?php foreach ( $activity['days'] as $day => $counts ) : ?>
							<tr>
								<td><?php echo esc_html( $day ); ?></td>
								<td>
									<?php
									$parts = array();
									foreach ( $counts as $login => $n ) {
										$parts[] = $login . ': ' . $n;
									}
									echo esc_html( implode( ', ', $parts ) );
									?>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		<?php else : ?>
			<p class="lq-help"><?php esc_html_e( 'No posts in the last 30 days.', 'lailatulqadar' ); ?></p>
		<?php endif; ?>
		<h3><?php esc_html_e( 'Burst publishing', 'lailatulqadar' ); ?></h3>
		<p class="lq-help"><?php esc_html_e( 'Hours in which one account added more than two posts. A report only: nothing is held or trashed for this.', 'lailatulqadar' ); ?></p>
		<?php if ( $activity['bursts'] ) : ?>
			<ul class="lq-sec-list">
				<?php foreach ( array_slice( $activity['bursts'], 0, 50 ) as $b ) : ?>
					<li><?php echo esc_html( sprintf( '%s, %s: %d', $b['login'], $b['when'], $b['n'] ) ); ?></li>
				<?php endforeach; ?>
			</ul>
		<?php else : ?>
			<p class="lq-help"><?php esc_html_e( 'None.', 'lailatulqadar' ); ?></p>
		<?php endif; ?>

		<h3><?php esc_html_e( 'Guide pages with gambling links', 'lailatulqadar' ); ?></h3>
		<?php $tainted = lq_sec_tainted_seeds(); ?>
		<?php if ( $tainted ) : ?>
			<p class="lq-help"><?php esc_html_e( 'These guide pages link to gambling sites. Nothing is changed here: open each one and remove the links by hand.', 'lailatulqadar' ); ?></p>
			<ul class="lq-sec-list">
				<?php foreach ( $tainted as $post ) : ?>
					<li><a href="<?php echo esc_url( (string) get_edit_post_link( $post->ID ) ); ?>"><?php echo esc_html( $post->post_title ); ?></a> <span class="lq-help"><?php echo esc_html( implode( ', ', $post->lq_hosts ) ); ?></span></li>
				<?php endforeach; ?>
			</ul>
		<?php else : ?>
			<p class="lq-help"><?php esc_html_e( 'None: no guide page links to a gambling site.', 'lailatulqadar' ); ?></p>
		<?php endif; ?>

		<h2><?php esc_html_e( 'Security log', 'lailatulqadar' ); ?></h2>
		<?php
		$log = get_option( LQ_SEC_LOG, array() );
		$log = is_array( $log ) ? array_reverse( $log ) : array();
		?>
		<?php if ( $log ) : ?>
			<div class="lq-sec-scroll lq-sec-scroll--tall">
				<table class="widefat striped lq-sec-table">
					<thead><tr><th scope="col"><?php esc_html_e( 'Time', 'lailatulqadar' ); ?></th><th scope="col"><?php esc_html_e( 'Event', 'lailatulqadar' ); ?></th><th scope="col"><?php esc_html_e( 'Detail', 'lailatulqadar' ); ?></th><th scope="col"><?php esc_html_e( 'Account', 'lailatulqadar' ); ?></th><th scope="col"><?php esc_html_e( 'Route', 'lailatulqadar' ); ?></th><th scope="col"><?php esc_html_e( 'IP', 'lailatulqadar' ); ?></th></tr></thead>
					<tbody>
						<?php foreach ( $log as $e ) : ?>
							<?php $u = ! empty( $e['user'] ) ? get_userdata( (int) $e['user'] ) : false; ?>
							<tr>
								<td><?php echo esc_html( wp_date( $format, (int) $e['time'] ) ); ?></td>
								<td><?php echo esc_html( $e['event'] ); ?></td>
								<td>
									<?php
									if ( ! empty( $e['post_id'] ) && get_post( (int) $e['post_id'] ) ) {
										echo '<a href="' . esc_url( (string) get_edit_post_link( (int) $e['post_id'] ) ) . '">' . esc_html( $e['title'] ) . '</a>';
									} else {
										echo esc_html( $e['title'] );
									}
									if ( ! empty( $e['reasons'] ) ) {
										echo '<br><span class="lq-help">' . esc_html( implode( '; ', (array) $e['reasons'] ) ) . '</span>';
									}
									?>
								</td>
								<td><?php echo esc_html( $u ? $u->user_login : '' ); ?></td>
								<td><?php echo esc_html( $e['type'] ); ?></td>
								<td><?php echo esc_html( $e['ip'] ); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
			<?php lq_sec_button( 'lq_sec_clear_log', __( 'Clear the log', 'lailatulqadar' ), __( 'Clear the security log?', 'lailatulqadar' ) ); ?>
		<?php else : ?>
			<p class="lq-help"><?php esc_html_e( 'Nothing logged yet.', 'lailatulqadar' ); ?></p>
		<?php endif; ?>
	</section>
	<?php
}
